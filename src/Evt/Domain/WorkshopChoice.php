<?php
/**
 * The rules of choosing workshops: how many, never the same twice, never at once.
 *
 * @package Evt
 */

namespace Evt\Domain;

/**
 * Las reglas de elegir taller, sin WordPress (ADR-0050).
 *
 * Un evento dice **cuántos** talleres puede elegir cada persona —uno, si no
 * dice nada—. Y hay dos cosas que no se pueden hacer nunca, se elijan los que
 * se elijan:
 *
 * - **Repetir taller.** Un taller que se da dos veces son dos actividades con
 *   el **mismo título** y distinta hora. Nadie entra en las dos sesiones:
 *   se apunta a una y deja la plaza de la otra a quien no la tiene.
 * - **Estar en dos sitios a la vez.** Dos talleres del mismo día cuyas horas se
 *   pisan no se eligen juntos.
 *
 * Que el mismo taller se reconozca por el título y no por un campo «es otra
 * sesión de» es la decisión: no hay nada nuevo que rellenar, y el título es lo
 * que la persona ve y lo que la organización ya escribe igual.
 *
 * El aforo no está aquí: contar plazas pide la base de datos y un candado, y
 * es de {@see \Evt\PublicFront\Registrations::seat()}.
 */
final class WorkshopChoice {

	/**
	 * Too many workshops for this event.
	 */
	public const TOO_MANY = 'demasiados';

	/**
	 * Two sessions of the same workshop.
	 */
	public const REPEATED = 'repetido';

	/**
	 * Two workshops at the same time.
	 */
	public const CLASH = 'solapa';

	/**
	 * Not a workshop of this event.
	 */
	public const NOT_HERE = 'no_es_un_taller_del_evento';

	/**
	 * The workshop IDs a stored or submitted value holds, in order, without repeats.
	 *
	 * Admite lo que se guardaba antes —un solo número— y lo que se guarda ahora
	 * —una lista separada por comas—, y lo que llega de un formulario: un
	 * número, o una lista de ellos. El 0 de «ninguno» no es un taller.
	 *
	 * @param mixed $value Stored or submitted value.
	 * @return int[]
	 */
	public static function ids( $value ): array {
		if ( is_array( $value ) ) {
			$trozos = $value;
		} elseif ( is_scalar( $value ) ) {
			$trozos = explode( ',', (string) $value );
		} else {
			$trozos = array();
		}

		$out = array();
		foreach ( $trozos as $trozo ) {
			$id = is_scalar( $trozo ) ? (int) trim( (string) $trozo ) : 0;
			if ( $id > 0 && ! in_array( $id, $out, true ) ) {
				$out[] = $id;
			}
		}
		return $out;
	}

	/**
	 * A list of workshop IDs as it is stored.
	 *
	 * @param int[] $ids Workshop IDs.
	 * @return string
	 */
	public static function store( array $ids ): string {
		return implode( ',', self::ids( $ids ) );
	}

	/**
	 * How many workshops one person may choose, from the stored setting.
	 *
	 * Sin ajuste, uno: es lo que había antes de que se pudiera elegir más.
	 *
	 * @param mixed $value Stored value.
	 * @return int At least 1.
	 */
	public static function max( $value ): int {
		$n = is_scalar( $value ) ? (int) $value : 0;
		return max( 1, $n );
	}

	/**
	 * Whether a choice is allowed, and if not, why.
	 *
	 * @param array<int, array<string, string>> $workshops Workshops of the event: id => {title, date, start, end}.
	 * @param int[]                             $chosen    Workshop IDs chosen.
	 * @param int                               $max       How many may be chosen.
	 * @return string '' when allowed; one of the error constants otherwise.
	 */
	public static function check( array $workshops, array $chosen, int $max ): string {
		$chosen = self::ids( $chosen );

		foreach ( $chosen as $id ) {
			if ( ! isset( $workshops[ $id ] ) ) {
				return self::NOT_HERE;
			}
		}
		if ( count( $chosen ) > max( 1, $max ) ) {
			return self::TOO_MANY;
		}

		$n = count( $chosen );
		for ( $i = 0; $i < $n; $i++ ) {
			for ( $j = $i + 1; $j < $n; $j++ ) {
				$a = $workshops[ $chosen[ $i ] ];
				$b = $workshops[ $chosen[ $j ] ];
				if ( self::key( (string) ( $a['title'] ?? '' ) ) === self::key( (string) ( $b['title'] ?? '' ) ) ) {
					return self::REPEATED;
				}
				if ( self::clash( $a, $b ) ) {
					return self::CLASH;
				}
			}
		}
		return '';
	}

	/**
	 * Whether two workshops happen at the same time.
	 *
	 * El mismo día y con las horas pisándose. Sin hora de fin, el taller dura
	 * un instante: solo choca con otro que empiece a la vez o lo contenga. Sin
	 * hora de inicio no se sabe, y lo que no se sabe no se prohíbe.
	 *
	 * @param array<string, string> $a One workshop: date, start, end.
	 * @param array<string, string> $b The other.
	 * @return bool
	 */
	public static function clash( array $a, array $b ): bool {
		if ( (string) ( $a['date'] ?? '' ) !== (string) ( $b['date'] ?? '' ) ) {
			return false;
		}
		$a_desde = self::minutes( (string) ( $a['start'] ?? '' ) );
		$b_desde = self::minutes( (string) ( $b['start'] ?? '' ) );
		if ( null === $a_desde || null === $b_desde ) {
			return false;
		}
		$a_hasta = self::minutes( (string) ( $a['end'] ?? '' ) ) ?? $a_desde;
		$b_hasta = self::minutes( (string) ( $b['end'] ?? '' ) ) ?? $b_desde;

		if ( $a_desde === $b_desde ) {
			return true;
		}
		return $a_desde < $b_hasta && $b_desde < $a_hasta;
	}

	/**
	 * The title of a workshop, folded so that two sessions compare equal.
	 *
	 * Sin mayúsculas, sin tildes y con los espacios en uno: «Primeros
	 * auxilios» y «primeros  Auxilios» son el mismo taller.
	 *
	 * @param string $title Title.
	 * @return string
	 */
	public static function key( string $title ): string {
		$title = strtr(
			$title,
			array(
				'á' => 'a',
				'é' => 'e',
				'í' => 'i',
				'ó' => 'o',
				'ú' => 'u',
				'ü' => 'u',
				'ñ' => 'n',
				'Á' => 'a',
				'É' => 'e',
				'Í' => 'i',
				'Ó' => 'o',
				'Ú' => 'u',
				'Ü' => 'u',
				'Ñ' => 'n',
			)
		);
		$title = function_exists( 'mb_strtolower' ) ? mb_strtolower( $title, 'UTF-8' ) : strtolower( $title );
		return trim( (string) preg_replace( '/\s+/u', ' ', $title ) );
	}

	/**
	 * Why a choice was refused, in Spanish, for the person choosing.
	 *
	 * @param string $error Error code.
	 * @param int    $max   How many may be chosen.
	 * @return string
	 */
	public static function why( string $error, int $max ): string {
		$textos = array(
			self::TOO_MANY => 1 === $max
				? 'En este evento solo se elige un taller.'
				: sprintf( 'En este evento se eligen como mucho %d talleres.', $max ),
			self::REPEATED => 'Ha elegido dos sesiones del mismo taller. Elija solo una: la otra plaza es para quien todavía no lo tiene.',
			self::CLASH    => 'Ha elegido dos talleres que coinciden en hora. Elija uno de los dos.',
			self::NOT_HERE => 'Ese taller no es de este evento.',
		);
		return $textos[ $error ] ?? '';
	}

	/**
	 * The instructions every choice of workshops shows, from the setting.
	 *
	 * @param int $max How many may be chosen.
	 * @return string
	 */
	public static function rules_text( int $max ): string {
		$cuantos = 1 === $max
			? 'Puede elegir un taller.'
			: sprintf( 'Puede elegir hasta %d talleres.', $max );
		return $cuantos . ' Si un taller se repite en dos horarios, apúntese solo a una sesión;'
			. ' y no puede elegir dos talleres que coincidan en hora.'
			. ' Mientras el plazo esté abierto puede cambiar su elección; un taller lleno deja de aparecer.';
	}

	/**
	 * A time «H:i» as minutes since midnight, or null.
	 *
	 * @param string $hhmm Time.
	 * @return int|null
	 */
	private static function minutes( string $hhmm ): ?int {
		if ( ! preg_match( '/^(\d{1,2}):(\d{2})/', trim( $hhmm ), $m ) ) {
			return null;
		}
		return (int) $m[1] * 60 + (int) $m[2];
	}
}
