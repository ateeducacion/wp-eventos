<?php
/**
 * Register the meta keys of speakers and programme activities.
 *
 * @package Evt
 */

namespace Evt\Meta;

use Evt\PostType\ActivityPostType;
use Evt\PostType\SpeakerPostType;

/**
 * Lo mismo que hace {@see EventMetaRegistration} con el evento, para los dos
 * tipos que cuelgan de él.
 *
 * El `auth_callback` es el de siempre —`edit_post` sobre la ficha—, y eso
 * basta: como el ponente y la actividad cuelgan del evento por `post_parent`,
 * `edit_post` desemboca en {@see \Evt\Access\EventAccess::can_edit()} con las
 * áreas del evento y con su cierre por histórico. No hay una segunda regla que
 * mantener al día.
 */
final class ProgrammeMetaRegistration {

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'register_meta' ), 12 );
	}

	/**
	 * Speaker meta keys with their type and sanitiser.
	 *
	 * @return array<string, array{type:string, sanitize:callable}>
	 */
	public static function speaker_schema(): array {
		return array(
			ProgrammeMetaKeys::SPEAKER_ROLE => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field',
			),
			ProgrammeMetaKeys::SPEAKER_ORG  => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * Activity meta keys with their type and sanitiser.
	 *
	 * @return array<string, array{type:string, sanitize:callable}>
	 */
	public static function activity_schema(): array {
		return array(
			ProgrammeMetaKeys::ACTIVITY_KIND     => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_kind' ),
			),
			ProgrammeMetaKeys::ACTIVITY_DATE     => array(
				'type'     => 'string',
				'sanitize' => array( EventMetaRegistration::class, 'sanitize_date' ),
			),
			ProgrammeMetaKeys::ACTIVITY_START    => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_time' ),
			),
			ProgrammeMetaKeys::ACTIVITY_END      => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_time' ),
			),
			ProgrammeMetaKeys::ACTIVITY_VENUE    => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field',
			),
			ProgrammeMetaKeys::ACTIVITY_ROOM     => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field',
			),
			ProgrammeMetaKeys::ACTIVITY_SEATS    => array(
				'type'     => 'integer',
				'sanitize' => array( EventMetaRegistration::class, 'sanitize_id' ),
			),
			ProgrammeMetaKeys::ACTIVITY_SPEAKERS => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_id_list' ),
			),
		);
	}

	/**
	 * Register every speaker and activity meta key.
	 *
	 * @return void
	 */
	public static function register_meta(): void {
		$defaults = array(
			'string'  => '',
			'integer' => 0,
		);
		$mapa     = array(
			SpeakerPostType::POST_TYPE  => self::speaker_schema(),
			ActivityPostType::POST_TYPE => self::activity_schema(),
		);

		foreach ( $mapa as $post_type => $schema ) {
			foreach ( $schema as $key => $spec ) {
				register_post_meta(
					$post_type,
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
	}

	/**
	 * Keep the kind inside the closed list; anything else becomes «otra».
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_kind( $value ): string {
		// El mapa entero y no sus claves: `in_list()` pregunta con `isset()`
		// sobre el array que recibe, así que una lista posicional haría que
		// todo cayera en «otra» sin que nada avisara.
		return EventMetaKeys::in_list(
			is_scalar( $value ) ? (string) $value : '',
			ProgrammeMetaKeys::activity_kinds(),
			'otra'
		);
	}

	/**
	 * Keep a time of day, or nothing.
	 *
	 * Una hora mal escrita se guarda vacía en vez de romper el guardado, igual
	 * que hace `sanitize_date()` con las fechas. Quien teclea ve el aviso de
	 * {@see \Evt\Domain\ActivityInput}, que sí lo marca como error antes de
	 * llegar aquí; esto es la red de debajo, para REST y WP-CLI.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_time( $value ): string {
		$texto = is_scalar( $value ) ? trim( (string) $value ) : '';
		if ( ! preg_match( '/^([01]\d|2[0-3]):([0-5]\d)$/', $texto ) ) {
			return '';
		}
		return $texto;
	}

	/**
	 * Keep a comma separated list of post IDs, and nothing else.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_id_list( $value ): string {
		if ( is_array( $value ) ) {
			$partes = $value;
		} else {
			$partes = explode( ',', is_scalar( $value ) ? (string) $value : '' );
		}
		$ids = array();
		foreach ( $partes as $uno ) {
			$id = absint( $uno );
			if ( $id > 0 && ! in_array( $id, $ids, true ) ) {
				$ids[] = $id;
			}
		}
		return implode( ',', $ids );
	}
}
