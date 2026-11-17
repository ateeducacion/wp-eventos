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
	 * @param array<string, mixed>  $raw     Raw fields.
	 * @param array<string, string> $centres Valid centre map [code => name].
	 * @return array{ok:bool, errors:string[], data:array<string, mixed>}
	 */
	public static function core( array $raw, array $centres = array() ): array {
		$errors = array();

		$tax_id      = self::tax_id( self::text( $raw, 'tax_id' ) );
		$name        = self::text( $raw, 'name' );
		$surname     = self::text( $raw, 'surname' );
		$email       = strtolower( self::text( $raw, 'email' ) );
		$phone       = self::phone( self::text( $raw, 'phone' ) );
		$centre      = self::text( $raw, 'centre' );
		$centre_code = '';
		$centre_name = '';
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
		// El centro se elige de un catálogo por su código oficial de 8 dígitos (ADR-0031, ADR-0037).
		// Lo que envía el navegador es el código oficial; la denominación se obtiene del catálogo.
		// Fail-closed: si no hay catálogo o el código no está en él, se rechaza.
		if (
			'' !== $centre &&
			self::is_centre_code( $centre ) &&
			! empty( $centres ) &&
			isset( $centres[ $centre ] )
		) {
			$centre_code = $centre;
			$centre_name = (string) $centres[ $centre ];
		} else {
			$errors[] = 'centre';
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
	 * de dos maneras. Un DNI o un NIE al que le faltan los ceros de delante
	 * —«1234567L» por «01234567L»— se completa, pero **solo si la letra de
	 * control cuadra** con el número completo: así no se toca un pasaporte
	 * que por casualidad tenga la misma forma.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public static function tax_id( string $value ): string {
		$valor = strtoupper( (string) preg_replace( '/[\s.\-]/', '', $value ) );
		if ( preg_match( '/^(\d{1,7})([A-Z])$/', $valor, $m ) ) {
			$completo = str_pad( $m[1], 8, '0', STR_PAD_LEFT );
			return self::dni_letter( $completo ) === $m[2] ? $completo . $m[2] : $valor;
		}
		if ( preg_match( '/^([XYZ])(\d{1,6})([A-Z])$/', $valor, $m ) ) {
			$completo = str_pad( $m[2], 7, '0', STR_PAD_LEFT );
			$numero   = (string) strpos( 'XYZ', $m[1] ) . $completo;
			return self::dni_letter( $numero ) === $m[3] ? $m[1] . $completo . $m[3] : $valor;
		}
		return $valor;
	}

	/**
	 * The control letter of a Spanish DNI number (a NIE maps X/Y/Z to 0/1/2 first).
	 *
	 * @param string $digits Digits.
	 * @return string
	 */
	private static function dni_letter( string $digits ): string {
		return substr( 'TRWAGMYFPDXBNJZSQVHLCKE', (int) $digits % 23, 1 );
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
	 * Solo dígitos, longitud exacta de 8 caracteres.
	 *
	 * @param string $value Raw value.
	 * @return bool
	 */
	public static function is_centre_code( string $value ): bool {
		return (bool) preg_match( '/^\d{8}$/', trim( $value ) );
	}

	/**
	 * Whether an address looks like an address.
	 *
	 * Sin `filter_var()` para que la clase siga siendo pura y el resultado sea
	 * el mismo en cualquier PHP: algo, una arroba, algo, un punto y algo.
	 *
	 * **Solo ASCII.** Al guardarla, `sanitize_email()` quita lo que no lo es:
	 * «josé@ejemplo.es» quedaría en «jos@ejemplo.es», y el enlace personal de
	 * la inscripción iría a otra persona. Mejor pedir que se revise.
	 *
	 * @param string $value Raw value.
	 * @return bool
	 */
	public static function is_email( string $value ): bool {
		return (bool) preg_match( '/^[^@\s\x80-\xFF]+@[^@\s.\x80-\xFF]+(\.[^@\s.\x80-\xFF]+)+$/', $value );
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
