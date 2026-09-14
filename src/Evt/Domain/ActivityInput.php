<?php
/**
 * Pure validation for speaker and activity form input.
 *
 * @package Evt
 */

namespace Evt\Domain;

use Evt\Meta\ProgrammeMetaKeys;

/**
 * Valida y normaliza lo que se teclea en «Ponentes» y en «Programa».
 *
 * Pura: ni una llamada a WordPress, para que se pueda probar sin cargarlo,
 * igual que {@see EventInput}.
 */
final class ActivityInput {

	/**
	 * Validate a submitted speaker payload.
	 *
	 * El nombre es lo único obligatorio. El cargo y la entidad se enseñan
	 * debajo del nombre en la página pública y muchas fichas no los traen.
	 *
	 * @param array<string, mixed> $raw Raw fields.
	 * @return array{ok:bool, errors:string[], data:array<string, string>}
	 */
	public static function speaker( array $raw ): array {
		$name   = self::text( $raw, 'name' );
		$errors = '' === $name ? array( 'name' ) : array();

		return array(
			'ok'     => array() === $errors,
			'errors' => $errors,
			'data'   => array(
				'name' => $name,
				'role' => self::text( $raw, 'role' ),
				'org'  => self::text( $raw, 'org' ),
				'bio'  => isset( $raw['bio'] ) ? trim( (string) $raw['bio'] ) : '',
			),
		);
	}

	/**
	 * Validate a submitted activity payload.
	 *
	 * Lo obligatorio es el título y el día: sin día una actividad no cabe en
	 * ninguna parrilla y se quedaría fuera de la pantalla sin decir por qué.
	 * La hora no lo es —hay actividades «por la tarde», sin hora fijada—, pero
	 * si viene tiene que ser una hora.
	 *
	 * @param array<string, mixed> $raw Raw fields.
	 * @return array{ok:bool, errors:string[], data:array<string, mixed>}
	 */
	public static function activity( array $raw ): array {
		$errors = array();

		$title = self::text( $raw, 'title' );
		$kind  = self::text( $raw, 'kind' );
		$date  = self::text( $raw, 'date' );
		$start = self::text( $raw, 'start' );
		$end   = self::text( $raw, 'end' );
		$seats = isset( $raw['seats'] ) ? (int) $raw['seats'] : 0;

		if ( '' === $title ) {
			$errors[] = 'title';
		}
		if ( '' === $kind || ! isset( ProgrammeMetaKeys::activity_kinds()[ $kind ] ) ) {
			$errors[] = 'kind';
		}
		if ( ! self::is_date( $date ) ) {
			$errors[] = 'date';
		}
		if ( '' !== $start && ! self::is_time( $start ) ) {
			$errors[] = 'start';
		}
		if ( '' !== $end && ! self::is_time( $end ) ) {
			$errors[] = 'end';
		}
		// Solo se comparan cuando las dos son horas: si una está mal escrita,
		// el error que hay que enseñar es el suyo y no un orden imposible.
		if ( self::is_time( $start ) && self::is_time( $end ) && $end < $start ) {
			$errors[] = 'time_order';
		}
		if ( $seats < 0 ) {
			$errors[] = 'seats';
		}

		return array(
			'ok'     => array() === $errors,
			'errors' => $errors,
			'data'   => array(
				'title'    => $title,
				'kind'     => isset( ProgrammeMetaKeys::activity_kinds()[ $kind ] ) ? $kind : 'otra',
				'date'     => self::is_date( $date ) ? $date : '',
				'start'    => self::is_time( $start ) ? $start : '',
				'end'      => self::is_time( $end ) ? $end : '',
				'venue'    => self::text( $raw, 'venue' ),
				'room'     => self::text( $raw, 'room' ),
				'seats'    => max( 0, $seats ),
				'summary'  => isset( $raw['summary'] ) ? trim( (string) $raw['summary'] ) : '',
				'speakers' => self::ids( $raw['speakers'] ?? array() ),
			),
		);
	}

	/**
	 * Why a payload was refused, in Spanish.
	 *
	 * @param string[] $errors Error codes.
	 * @return string
	 */
	public static function why( array $errors ): string {
		$textos = array(
			'name'       => 'el nombre',
			'title'      => 'el título',
			'kind'       => 'el tipo de actividad',
			'date'       => 'el día (con el formato AAAA-MM-DD)',
			'start'      => 'la hora de inicio',
			'end'        => 'la hora de fin',
			'time_order' => 'la hora de fin, que es anterior a la de inicio',
			'seats'      => 'el aforo, que no puede ser negativo',
		);
		$faltan = array();
		foreach ( $errors as $codigo ) {
			if ( isset( $textos[ $codigo ] ) ) {
				$faltan[] = $textos[ $codigo ];
			}
		}
		if ( array() === $faltan ) {
			return 'No se ha podido guardar: revise lo escrito.';
		}
		return 'No se ha podido guardar. Revise ' . implode( ', ', $faltan ) . '.';
	}

	/**
	 * One trimmed field of the payload.
	 *
	 * @param array<string, mixed> $raw   Raw fields.
	 * @param string               $clave Field name.
	 * @return string
	 */
	private static function text( array $raw, string $clave ): string {
		return isset( $raw[ $clave ] ) ? trim( (string) $raw[ $clave ] ) : '';
	}

	/**
	 * A list of post IDs, cleaned of everything that is not one.
	 *
	 * @param mixed $valor Raw value.
	 * @return int[]
	 */
	private static function ids( $valor ): array {
		if ( is_string( $valor ) ) {
			$valor = explode( ',', $valor );
		}
		if ( ! is_array( $valor ) ) {
			return array();
		}
		$out = array();
		foreach ( $valor as $uno ) {
			$id = (int) $uno;
			if ( $id > 0 && ! in_array( $id, $out, true ) ) {
				$out[] = $id;
			}
		}
		return $out;
	}

	/**
	 * Whether the string is a calendar date in Y-m-d form.
	 *
	 * @param string $date Date string.
	 * @return bool
	 */
	private static function is_date( string $date ): bool {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return false;
		}
		$parts = array_map( 'intval', explode( '-', $date ) );
		return checkdate( $parts[1], $parts[2], $parts[0] );
	}

	/**
	 * Whether the string is a time of day in H:i form.
	 *
	 * @param string $time Time string.
	 * @return bool
	 */
	private static function is_time( string $time ): bool {
		if ( ! preg_match( '/^([01]\d|2[0-3]):([0-5]\d)$/', $time ) ) {
			return false;
		}
		return true;
	}
}
