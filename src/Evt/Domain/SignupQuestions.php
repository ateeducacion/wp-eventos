<?php
/**
 * Pure handling of the per-event signup questions and their answers.
 *
 * @package Evt
 */

namespace Evt\Domain;

use Evt\Meta\RegistrationMetaKeys;

/**
 * Las preguntas propias de un evento, y lo que se contesta a ellas.
 *
 * Pura: ni una llamada a WordPress, como {@see EventInput} y
 * {@see ActivityInput}.
 *
 * Una pregunta tiene exactamente cuatro cosas —rótulo, tipo, opciones y si es
 * obligatoria— y ninguna más (ADR-0031). Vale igual para las de tipo `file`,
 * que la ADR-0036 añadió sin traerse nada consigo: ni tamaño propio, ni tipos
 * propios, ni varios ficheros. No hay lógica condicional y no hay
 * reglas de validación propias: **el tipo es toda la validación que existe**.
 * Si alguna vez se añade una de esas dos cosas, esto deja de ser una lista de
 * preguntas y pasa a ser el constructor de formularios del que se sale.
 *
 * Y lleva una quinta cosa que no se teclea: un **identificador inmutable** que
 * se genera al crear la pregunta. Las respuestas se guardan bajo él, así que
 * reescribir un rótulo no desconecta lo ya contestado (ADR-0032).
 */
final class SignupQuestions {

	/**
	 * Cuántas opciones como mucho en una pregunta.
	 *
	 * No es una regla del dominio: es el tope que impide que una lista de
	 * opciones pegada de un tirón se convierta en una meta enorme.
	 */
	public const MAX_OPTIONS = 50;

	/**
	 * Read a stored question list.
	 *
	 * Lo que hay guardado es JSON, y puede venir de una versión anterior o
	 * estar a medias: se normaliza siempre y lo que no se entiende se cae.
	 *
	 * @param mixed $stored Raw meta value.
	 * @return array<int, array{id:string, label:string, type:string, options:string[], required:bool}>
	 */
	public static function read( $stored ): array {
		if ( is_string( $stored ) ) {
			$stored = '' === trim( $stored ) ? array() : json_decode( $stored, true );
		}
		if ( ! is_array( $stored ) ) {
			return array();
		}

		$out    = array();
		$vistos = array();
		foreach ( $stored as $raw ) {
			if ( ! is_array( $raw ) ) {
				continue;
			}
			$pregunta = self::one( $raw );
			if ( '' === $pregunta['label'] || isset( $vistos[ $pregunta['id'] ] ) ) {
				continue;
			}
			$vistos[ $pregunta['id'] ] = true;
			$out[]                     = $pregunta;
		}
		return $out;
	}

	/**
	 * Normalise one question.
	 *
	 * @param array<string, mixed> $raw Raw question.
	 * @return array{id:string, label:string, type:string, options:string[], required:bool}
	 */
	private static function one( array $raw ): array {
		$type = isset( $raw['type'] ) ? (string) $raw['type'] : '';
		if ( ! isset( RegistrationMetaKeys::question_types()[ $type ] ) ) {
			$type = 'text';
		}

		$label = isset( $raw['label'] ) ? trim( (string) $raw['label'] ) : '';

		return array(
			'id'       => self::clean_id( isset( $raw['id'] ) ? (string) $raw['id'] : '' ),
			'label'    => $label,
			'type'     => $type,
			'options'  => RegistrationMetaKeys::has_options( $type ) ? self::options( $raw['options'] ?? array() ) : array(),
			'required' => ! empty( $raw['required'] ),
		);
	}

	/**
	 * Normalise a list of options: trimmed, no blanks, no repeats, capped.
	 *
	 * @param mixed $raw Raw options: an array or one per line.
	 * @return string[]
	 */
	public static function options( $raw ): array {
		if ( is_string( $raw ) ) {
			$raw = preg_split( '/\r\n|\r|\n/', $raw );
		}
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$out = array();
		foreach ( $raw as $opcion ) {
			if ( ! is_scalar( $opcion ) ) {
				continue;
			}
			$opcion = trim( (string) $opcion );
			if ( '' === $opcion || in_array( $opcion, $out, true ) ) {
				continue;
			}
			$out[] = $opcion;
			if ( count( $out ) >= self::MAX_OPTIONS ) {
				break;
			}
		}
		return $out;
	}

	/**
	 * Keep an ID to the shape we generate, or say it is missing.
	 *
	 * @param string $id Raw ID.
	 * @return string '' when it has to be generated.
	 */
	private static function clean_id( string $id ): string {
		$id = strtolower( trim( $id ) );
		return (bool) preg_match( '/^q[a-z0-9]{6,32}$/', $id ) ? $id : '';
	}

	/**
	 * Whether a question ID is one we could have generated.
	 *
	 * @param string $id Question ID.
	 * @return bool
	 */
	public static function is_id( string $id ): bool {
		return '' !== self::clean_id( $id );
	}

	/**
	 * Give every question without one an ID, using the supplied entropy.
	 *
	 * La entropía entra por parámetro para que esto siga siendo puro y para
	 * que un test pueda comprobar la forma sin depender del azar.
	 *
	 * @param array<int, array<string, mixed>> $preguntas Normalised questions.
	 * @param callable                         $entropy   Returns a random hex string.
	 * @return array<int, array<string, mixed>>
	 */
	public static function with_ids( array $preguntas, callable $entropy ): array {
		$vistos = array();
		foreach ( $preguntas as $i => $pregunta ) {
			$id = isset( $pregunta['id'] ) ? (string) $pregunta['id'] : '';
			while ( '' === $id || isset( $vistos[ $id ] ) ) {
				$id = 'q' . substr( strtolower( preg_replace( '/[^a-zA-Z0-9]/', '', (string) $entropy() ) ), 0, 12 );
				$id = self::is_id( $id ) ? $id : '';
			}
			$vistos[ $id ]         = true;
			$preguntas[ $i ]['id'] = $id;
		}
		return $preguntas;
	}

	/**
	 * Why a new question list cannot replace the stored one.
	 *
	 * Es la única regla que hace falta para que una respuesta guardada siga
	 * significando lo mismo (ADR-0032): con gente ya inscrita, a una pregunta
	 * se le puede cambiar el rótulo y **añadir** opciones, pero no cambiarle el
	 * tipo ni **quitar** una opción. Borrar la pregunta entera sí se puede: no
	 * borra lo contestado, lo deja de enseñar.
	 *
	 * @param array<int, array<string, mixed>> $antes  Stored questions.
	 * @param array<int, array<string, mixed>> $ahora  Proposed questions.
	 * @param bool                             $locked Whether anybody signed up already.
	 * @return string[] Error codes; empty when it can be saved.
	 */
	public static function refuse( array $antes, array $ahora, bool $locked ): array {
		if ( ! $locked ) {
			return array();
		}

		$previas = array();
		foreach ( $antes as $pregunta ) {
			$previas[ (string) $pregunta['id'] ] = $pregunta;
		}

		$errores = array();
		foreach ( $ahora as $pregunta ) {
			$id = isset( $pregunta['id'] ) ? (string) $pregunta['id'] : '';
			if ( ! isset( $previas[ $id ] ) ) {
				continue;
			}
			$previa = $previas[ $id ];

			if ( $previa['type'] !== $pregunta['type'] ) {
				$errores[] = 'type_changed:' . $id;
			}
			$perdidas = array_diff( (array) $previa['options'], (array) $pregunta['options'] );
			if ( array() !== $perdidas ) {
				$errores[] = 'option_removed:' . $id;
			}
		}
		return $errores;
	}

	/**
	 * Validate the answers of one person against the questions of the event.
	 *
	 * El tipo es toda la validación que hay: una opción de la lista es una de
	 * la lista, y un texto corto es un texto corto.
	 *
	 * Las preguntas de tipo `file` **no pasan por aquí**, ni para validarse ni
	 * para guardarse. Esto es puro y no lee `$_FILES`; el fichero es del borde
	 * de la aplicación, se guarda en su propia meta y lo comprueba
	 * {@see \Evt\PublicFront\RegistrationFiles} (ADR-0036). Meterlo aquí
	 * obligaría a esta clase a saber de peticiones, que es justo lo que no
	 * sabe.
	 *
	 * @param array<int, array<string, mixed>> $preguntas Normalised questions.
	 * @param array<string, mixed>             $raw       Raw answers, keyed by question ID.
	 * @return array{ok:bool, errors:string[], data:array<string, mixed>}
	 */
	public static function answers( array $preguntas, array $raw ): array {
		$errores = array();
		$datos   = array();

		foreach ( $preguntas as $pregunta ) {
			$id    = (string) $pregunta['id'];
			$valor = $raw[ $id ] ?? null;

			if ( 'file' === $pregunta['type'] ) {
				continue;
			}

			switch ( $pregunta['type'] ) {
				case 'check':
					$datos[ $id ] = ! empty( $valor );
					if ( $pregunta['required'] && ! $datos[ $id ] ) {
						$errores[] = $id;
					}
					break;

				case 'one':
					$elegida      = is_scalar( $valor ) ? trim( (string) $valor ) : '';
					$datos[ $id ] = in_array( $elegida, (array) $pregunta['options'], true ) ? $elegida : '';
					if ( $pregunta['required'] && '' === $datos[ $id ] ) {
						$errores[] = $id;
					}
					break;

				case 'many':
					$elegidas = is_array( $valor ) ? $valor : array();
					$limpias  = array();
					foreach ( $elegidas as $una ) {
						$una = is_scalar( $una ) ? trim( (string) $una ) : '';
						if ( in_array( $una, (array) $pregunta['options'], true ) && ! in_array( $una, $limpias, true ) ) {
							$limpias[] = $una;
						}
					}
					$datos[ $id ] = $limpias;
					if ( $pregunta['required'] && array() === $limpias ) {
						$errores[] = $id;
					}
					break;

				default:
					$texto        = is_scalar( $valor ) ? trim( (string) $valor ) : '';
					$datos[ $id ] = mb_substr( $texto, 0, 250 );
					if ( $pregunta['required'] && '' === $datos[ $id ] ) {
						$errores[] = $id;
					}
			}
		}

		return array(
			'ok'     => array() === $errores,
			'errors' => $errores,
			'data'   => $datos,
		);
	}

	/**
	 * One stored answer, as the text that goes in a column.
	 *
	 * @param array<string, mixed> $pregunta  Normalised question.
	 * @param mixed                $respuesta Stored answer.
	 * @return string
	 */
	public static function as_text( array $pregunta, $respuesta ): string {
		if ( 'check' === $pregunta['type'] ) {
			return $respuesta ? 'Sí' : 'No';
		}
		if ( is_array( $respuesta ) ) {
			return implode( ', ', array_map( 'strval', $respuesta ) );
		}
		return is_scalar( $respuesta ) ? (string) $respuesta : '';
	}
}
