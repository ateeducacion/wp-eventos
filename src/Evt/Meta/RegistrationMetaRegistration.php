<?php
/**
 * Register the meta keys of a registration and of the signup settings.
 *
 * @package Evt
 */

namespace Evt\Meta;

use Evt\Domain\SignupQuestions;
use Evt\PostType\RegistrationPostType;
use Evt\PostType\EventPostType;

/**
 * Lo mismo que {@see EventMetaRegistration} y {@see ProgrammeMetaRegistration},
 * para la inscripción y para los ajustes de inscripción del evento.
 *
 * La diferencia está en el `auth_callback` de las metas de la inscripción, y no
 * es un detalle: **devuelve `false` siempre**. Ponente y actividad se editan a
 * mano desde el taller, así que su `auth_callback` pregunta por `edit_post`;
 * una inscripción, en cambio, **no la edita nadie a mano**: la escribe el
 * aplicativo cuando alguien se inscribe. No hay una sola vía por la que una
 * meta con el documento de identidad de una persona se pueda escribir desde
 * fuera (ADR-0032).
 */
final class RegistrationMetaRegistration {

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'register_meta' ), 12 );
	}

	/**
	 * Registration meta keys with their type and sanitiser.
	 *
	 * @return array<string, array{type:string, sanitize:callable}>
	 */
	public static function registration_schema(): array {
		return array(
			RegistrationMetaKeys::REG_TAX_ID          => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_tax_id' ),
			),
			RegistrationMetaKeys::REG_NAME            => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field',
			),
			RegistrationMetaKeys::REG_SURNAME         => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field',
			),
			RegistrationMetaKeys::REG_EMAIL           => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_email',
			),
			RegistrationMetaKeys::REG_PHONE           => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field',
			),
			RegistrationMetaKeys::REG_CENTRE          => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field',
			),
			RegistrationMetaKeys::REG_CONSENT_VERSION => array(
				'type'     => 'integer',
				'sanitize' => array( EventMetaRegistration::class, 'sanitize_id' ),
			),
			RegistrationMetaKeys::REG_CONSENT_AT      => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field',
			),
			RegistrationMetaKeys::REG_WORKSHOP        => array(
				'type'     => 'integer',
				'sanitize' => array( EventMetaRegistration::class, 'sanitize_id' ),
			),
			RegistrationMetaKeys::REG_ANSWERS         => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_answers' ),
			),
			RegistrationMetaKeys::REG_TOKEN           => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_token' ),
			),
		);
	}

	/**
	 * Signup settings of an event, with their type and sanitiser.
	 *
	 * @return array<string, array{type:string, sanitize:callable}>
	 */
	public static function signup_schema(): array {
		return array(
			RegistrationMetaKeys::SIGNUP_OPEN      => array(
				'type'     => 'boolean',
				'sanitize' => array( self::class, 'sanitize_bool' ),
			),
			RegistrationMetaKeys::SIGNUP_QUESTIONS => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_questions' ),
			),
			RegistrationMetaKeys::WORKSHOP_OPEN    => array(
				'type'     => 'boolean',
				'sanitize' => array( self::class, 'sanitize_bool' ),
			),
			RegistrationMetaKeys::WORKSHOP_START   => array(
				'type'     => 'string',
				'sanitize' => array( EventMetaRegistration::class, 'sanitize_date' ),
			),
			RegistrationMetaKeys::WORKSHOP_END     => array(
				'type'     => 'string',
				'sanitize' => array( EventMetaRegistration::class, 'sanitize_date' ),
			),
			RegistrationMetaKeys::CONSENT_PRIVACY  => array(
				'type'     => 'string',
				'sanitize' => 'wp_kses_post',
			),
			RegistrationMetaKeys::CONSENT_IMAGE    => array(
				'type'     => 'string',
				'sanitize' => 'wp_kses_post',
			),
			RegistrationMetaKeys::CONSENT_VERSION  => array(
				'type'     => 'integer',
				'sanitize' => array( EventMetaRegistration::class, 'sanitize_id' ),
			),
		);
	}

	/**
	 * Register every registration and signup meta key.
	 *
	 * @return void
	 */
	public static function register_meta(): void {
		$defaults = array(
			'string'  => '',
			'integer' => 0,
			'boolean' => false,
		);

		foreach ( self::registration_schema() as $key => $spec ) {
			register_post_meta(
				RegistrationPostType::POST_TYPE,
				$key,
				array(
					'type'              => $spec['type'],
					'single'            => true,
					'default'           => $defaults[ $spec['type'] ],
					'sanitize_callback' => $spec['sanitize'],
					'auth_callback'     => '__return_false',
					'show_in_rest'      => false,
				)
			);
		}

		foreach ( self::signup_schema() as $key => $spec ) {
			register_post_meta(
				EventPostType::POST_TYPE,
				$key,
				array(
					'type'              => $spec['type'],
					'single'            => true,
					'default'           => $defaults[ $spec['type'] ],
					'sanitize_callback' => $spec['sanitize'],
					'auth_callback'     => array( EventMetaRegistration::class, 'auth_edit_event' ),
					'show_in_rest'      => false,
				)
			);
		}
	}

	/**
	 * Keep a tax ID upper case and without separators.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_tax_id( $value ): string {
		return \Evt\Domain\RegistrationInput::tax_id( is_scalar( $value ) ? (string) $value : '' );
	}

	/**
	 * Keep a token to the shape we issue, or nothing.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_token( $value ): string {
		$token = is_scalar( $value ) ? strtolower( trim( (string) $value ) ) : '';
		return (bool) preg_match( '/^[a-f0-9]{40}$/', $token ) ? $token : '';
	}

	/**
	 * Store answers as JSON, and nothing that is not answers.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_answers( $value ): string {
		if ( is_string( $value ) ) {
			$value = '' === trim( $value ) ? array() : json_decode( $value, true );
		}
		if ( ! is_array( $value ) ) {
			return '';
		}

		$out = array();
		foreach ( $value as $id => $respuesta ) {
			$id = (string) $id;
			if ( ! SignupQuestions::is_id( $id ) ) {
				continue;
			}
			if ( is_bool( $respuesta ) ) {
				$out[ $id ] = $respuesta;
			} elseif ( is_array( $respuesta ) ) {
				$out[ $id ] = array_values( array_map( 'sanitize_text_field', array_filter( $respuesta, 'is_scalar' ) ) );
			} elseif ( is_scalar( $respuesta ) ) {
				$out[ $id ] = sanitize_text_field( (string) $respuesta );
			}
		}

		return (string) wp_json_encode( $out );
	}

	/**
	 * Store the question list as JSON, normalised.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_questions( $value ): string {
		$preguntas = SignupQuestions::read( $value );
		foreach ( $preguntas as $i => $pregunta ) {
			$preguntas[ $i ]['label']   = sanitize_text_field( (string) $pregunta['label'] );
			$preguntas[ $i ]['options'] = array_map( 'sanitize_text_field', (array) $pregunta['options'] );
		}
		return (string) wp_json_encode( $preguntas );
	}

	/**
	 * A checkbox is a boolean.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	public static function sanitize_bool( $value ): bool {
		return (bool) $value;
	}
}
