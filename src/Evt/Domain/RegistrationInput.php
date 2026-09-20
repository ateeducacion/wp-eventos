<?php
/**
 * Pure validation of the fixed core of the signup form.
 *
 * @package Evt
 */

namespace Evt\Domain;

/**
 * Valida y normaliza los siete campos del núcleo de la inscripción.
 *
 * Pura: ni una llamada a WordPress. Es la parte del formulario que **está
 * medida y no cambia** (ADR-0031), así que se escribe una vez, se valida una
 * vez y se corrige una vez, en vez de repetirse en el formulario de cada
 * evento como pasaba antes.
 *
 * Lo que aquí no está —las preguntas propias del evento— lo valida
 * {@see SignupQuestions}, y su validación es solo el tipo.
 */
final class RegistrationInput {

	/**
	 * Validate a submitted signup payload.
	 *
	 * Obligatorios: identificador fiscal, nombre, apellidos, correo, centro y
	 * el consentimiento. El teléfono no: hay quien no lo da, y exigirlo es
	 * fabricar teléfonos falsos.
	 *
	 * @param array<string, mixed> $raw     Raw fields.
	 * @param string[]             $centres Valid centre codes; empty means «no catalogue loaded».
	 * @return array{ok:bool, errors:string[], data:array<string, mixed>}
	 */
	public static function core( array $raw, ?array $centres = null ): array {
		$errors = array();

		$tax_id      = self::tax_id( self::text( $raw, 'tax_id' ) );
		$name        = self::text( $raw, 'name' );
		$surname     = self::text( $raw, 'surname' );
		$email       = strtolower( self::text( $raw, 'email' ) );
		$phone       = self::phone( self::text( $raw, 'phone' ) );
		$centre      = self::text( $raw, 'centre' );
		$centre_code = self::text( $raw, 'centre_code' );
		$centre_name = $centre;
		$consent     = ! empty( $raw['consent'] );

		if ( ! self::is_tax_id( $tax_id ) ) {
			$errors[] = 'tax_id';
		}
		if ( '' === $name ) {
			$errors[] = 'name';
		}
		if ( '' === $surname ) {
			$errors[] = 'surname';
		}
		if ( ! self::is_email( $email ) ) {
			$errors[] = 'email';
		}
		// El centro se elige de un catálogo y **nunca se teclea** (ADR-0031):
		// si se pasa catálogo, lo que llegue tiene que estar en él. Un catálogo
		// vacío falla en cerrado (ADR-0037).
		if ( '' === $centre ) {
			$errors[] = 'centre';
		} elseif ( is_array( $centres ) ) {
			if ( array() === $centres ) {
				$errors[] = 'centre';
			} elseif ( isset( $centres[ $centre ] ) ) {
				$matched_val = (string) $centres[ $centre ];
				if ( self::is_centre_code( $centre ) ) {
					$centre_code = $centre;
					$centre_name = $matched_val;
				} else {
					$centre_name = $matched_val;
				}
			} elseif ( in_array( $centre, $centres, true ) ) {
				$key = array_search( $centre, $centres, true );
				if ( false !== $key && self::is_centre_code( (string) $key ) ) {
					$centre_code = (string) $key;
				}
				$centre_name = $centre;
			} else {
				$errors[] = 'centre';
			}
		} elseif ( '' === $centre_code && self::is_centre_code( $centre ) ) {
			$centre_code = $centre;
		}
		if ( ! $consent ) {
			$errors[] = 'consent';
		}

		return array(
			'ok'     => array() === $errors,
			'errors' => $errors,
			'data'   => array(
				'tax_id'      => $tax_id,
				'name'        => $name,
				'surname'     => $surname,
				'email'       => $email,
				'phone'       => $phone,
				'centre'      => $centre_name,
				'centre_code' => $centre_code,
				'consent'     => $consent,
			),
		);
	}

	/**
	 * Why a signup was refused, in Spanish.
	 *
	 * @param string[] $errors Error codes.
	 * @return string
	 */
	public static function why( array $errors ): string {
		$textos = array(
			'tax_id'  => 'el documento de identidad',
			'name'    => 'el nombre',
			'surname' => 'los apellidos',
			'email'   => 'el correo electrónico',
			'centre'  => 'el centro',
			'consent' => 'la aceptación del tratamiento de datos',
		);

		$faltan = array();
		foreach ( $errors as $error ) {
			if ( isset( $textos[ $error ] ) && ! in_array( $textos[ $error ], $faltan, true ) ) {
				$faltan[] = $textos[ $error ];
			}
		}

		if ( array() === $faltan ) {
			return 'No se ha podido completar la inscripción.';
		}
		if ( 1 === count( $faltan ) ) {
			return 'Revise ' . $faltan[0] . '.';
		}

		$ultimo = array_pop( $faltan );
		return 'Revise ' . implode( ', ', $faltan ) . ' y ' . $ultimo . '.';
	}

	/**
	 * A tax ID as it gets stored: upper case, no spaces, no dashes.
	 *
	 * Normalizar es lo que evita que la misma persona entre dos veces escrita
	 * de dos maneras.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public static function tax_id( string $value ): string {
		return strtoupper( (string) preg_replace( '/[\s.\-]/', '', $value ) );
	}

	/**
	 * Whether a tax ID has a shape we accept.
	 *
	 * Se comprueba la **forma**, no el dígito de control: aquí se inscribe
	 * también gente con documento de otro país, y un validador nacional
	 * dejaría fuera a quien tiene que poder inscribirse. Ocho o nueve
	 * caracteres alfanuméricos, que es lo que distingue un documento de un
	 * campo relleno a lo loco.
	 *
	 * @param string $value Normalised value.
	 * @return bool
	 */
	public static function is_tax_id( string $value ): bool {
		return (bool) preg_match( '/^[A-Z0-9]{6,15}$/', $value );
	}

	/**
	 * Whether a string has the shape of an official centre code.
	 *
	 * Solo dígitos, longitud de 7 u 8 caracteres.
	 *
	 * @param string $value Raw value.
	 * @return bool
	 */
	public static function is_centre_code( string $value ): bool {
		return (bool) preg_match( '/^\d{7,8}$/', trim( $value ) );
	}

	/**
	 * Whether an address looks like an address.
	 *
	 * Sin `filter_var()` para que la clase siga siendo pura y el resultado sea
	 * el mismo en cualquier PHP: algo, una arroba, algo, un punto y algo.
	 *
	 * @param string $value Raw value.
	 * @return bool
	 */
	public static function is_email( string $value ): bool {
		return (bool) preg_match( '/^[^@\s]+@[^@\s.]+(\.[^@\s.]+)+$/', $value );
	}

	/**
	 * A phone number with the noise taken out.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public static function phone( string $value ): string {
		return trim( (string) preg_replace( '/[^\d+ ]/', '', $value ) );
	}

	/**
	 * One trimmed field.
	 *
	 * @param array<string, mixed> $raw Raw fields.
	 * @param string               $key Field name.
	 * @return string
	 */
	private static function text( array $raw, string $key ): string {
		$value = $raw[ $key ] ?? '';
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}
}
