<?php
/**
 * Snippet Name: EVT — Aplicativo de eventos (CPT)
 * Description: CPT evt_event jerárquico (el evento y sus páginas satélite), evt_speaker y evt_activity, taxonomías evt_area / evt_type / evt_course, el acotado por área y las pantallas propias del aplicativo (portada, listado, taller del evento, formulario de sección y vista pública). Código generado desde src/Evt — no editar a mano; ejecutar php build/pack-snippet.php.
 * Scope: global
 * Priority: 15
 *
 * @package Evt
 * @version 0.1.0
 */

// phpcs:disable








namespace Evt\Meta;





defined( 'ABSPATH' ) || exit;




if ( \defined( 'EVT_BUNDLE_LOADED' ) ) {
	return;
}
\define( 'EVT_BUNDLE_LOADED', true );

















final class EventMetaKeys {




	public const SECTION_TYPE = 'evt_section_type';




	public const START_DATE = 'evt_start_date';




	public const END_DATE = 'evt_end_date';




	public const VENUE = 'evt_venue';




	public const TAGLINE = 'evt_tagline';




	public const HASHTAG = 'evt_hashtag';




	public const INTRO = 'evt_intro';




	public const SIGNUP_SHOW = 'evt_signup_show';




	public const SIGNUP_LABEL = 'evt_signup_label';




	public const SIGNUP_URL = 'evt_signup_url';














	public const SIGNUP_FORM_ID = 'evt_signup_form_id';




	public const HEADER_BG = 'evt_header_bg';




	public const HEADER_TEXT = 'evt_header_text';




	public const TITLE_FONT = 'evt_title_font';




	public const BODY_FONT = 'evt_body_font';




	public const LOGO_ID = 'evt_logo_id';







	public const HEADER_BANNER_ID = 'evt_header_banner_id';




	public const POSTER_ID = 'evt_poster_id';




	public const IMAGE_SHAPE = 'evt_image_shape';




	public const SEPARATOR = 'evt_separator';








	public const CUSTOM_CSS = 'evt_custom_css';








	public const CUSTOM_JS = 'evt_custom_js';















	public const ARCHIVED = 'evt_archived';




	public const SECTION_OTHER = 'otra';




	public const STATE_UPCOMING = 'proximo';




	public const STATE_OPEN = 'abierto';




	public const STATE_FINISHED = 'finalizado';




	public const SHAPE_SQUARE = 'square';




	public const SHAPE_CIRCLE = 'circle';




	public const FONT_DEFAULT = '';






	public static function all(): array {
		return array(
			self::SECTION_TYPE,
			self::START_DATE,
			self::END_DATE,
			self::VENUE,
			self::TAGLINE,
			self::HASHTAG,
			self::INTRO,
			self::SIGNUP_SHOW,
			self::SIGNUP_LABEL,
			self::SIGNUP_URL,
			self::SIGNUP_FORM_ID,
			self::HEADER_BG,
			self::HEADER_TEXT,
			self::TITLE_FONT,
			self::BODY_FONT,
			self::LOGO_ID,
			self::HEADER_BANNER_ID,
			self::POSTER_ID,
			self::IMAGE_SHAPE,
			self::SEPARATOR,
			self::CUSTOM_CSS,
			self::CUSTOM_JS,
			self::ARCHIVED,
		);
	}










	public static function code_keys(): array {
		return array( self::CUSTOM_CSS, self::CUSTOM_JS );
	}






	public static function section_types(): array {
		return array(
			'programa'          => 'Programa',
			'ponentes'          => 'Ponentes',
			'inscripcion'       => 'Inscripción',
			'multimedia'        => 'Multimedia',
			'contacto'          => 'Contacto',
			'actividades'       => 'Actividades',
			'encuesta'          => 'Encuesta',
			'participacion'     => 'Participación',
			'preguntas'         => 'Preguntas',
			'directo'           => 'Emisión en directo',
			self::SECTION_OTHER => 'Otra',
		);
	}






	public static function states(): array {
		return array(
			self::STATE_UPCOMING => 'Próximo',
			self::STATE_OPEN     => 'Abierto',
			self::STATE_FINISHED => 'Finalizado',
		);
	}














	public static function fonts(): array {
		return array(
			self::FONT_DEFAULT => 'La del tema',
			'open-sans'        => 'Open Sans',
			'lato'             => 'Lato',
			'montserrat'       => 'Montserrat',
			'source-serif'     => 'Source Serif 4',
			'merriweather'     => 'Merriweather',
		);
	}






	public static function image_shapes(): array {
		return array(
			self::SHAPE_SQUARE => 'Cuadrada',
			self::SHAPE_CIRCLE => 'Redonda',
		);
	}











	public static function separators(): array {
		return array(
			''         => 'Sin separador',
			'slant'    => 'Diagonal',
			'ramp'     => 'Rampa',
			'curve'    => 'Curva',
			'wave'     => 'Onda',
			'triangle' => 'Triángulo',
		);
	}









	public static function in_list( $value, array $allowed, string $fallback = '' ): string {
		$value = trim( (string) $value );
		return isset( $allowed[ $value ] ) ? $value : $fallback;
	}
}








namespace Evt\Meta;

use Evt\Access\EventAccess;
use Evt\PostType\EventPostType;








final class EventMetaRegistration {






	public static function register(): void {
		add_action( 'init', array( self::class, 'register_meta' ), 12 );
	}










	public static function schema(): array {
		return array(
			EventMetaKeys::SECTION_TYPE     => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_section_type' ),
			),
			EventMetaKeys::START_DATE       => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_date' ),
			),
			EventMetaKeys::END_DATE         => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_date' ),
			),
			EventMetaKeys::VENUE            => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field',
			),
			EventMetaKeys::TAGLINE          => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field',
			),
			EventMetaKeys::HASHTAG          => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_hashtag' ),
			),
			EventMetaKeys::INTRO            => array(
				'type'     => 'string',
				'sanitize' => 'wp_kses_post',
			),
			EventMetaKeys::SIGNUP_SHOW      => array(
				'type'     => 'boolean',
				'sanitize' => array( self::class, 'sanitize_bool' ),
			),
			EventMetaKeys::SIGNUP_LABEL     => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field',
			),
			EventMetaKeys::SIGNUP_URL       => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_url' ),
			),
			EventMetaKeys::SIGNUP_FORM_ID   => array(
				'type'     => 'integer',
				'sanitize' => array( self::class, 'sanitize_id' ),
			),
			EventMetaKeys::HEADER_BG        => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_color' ),
			),
			EventMetaKeys::HEADER_TEXT      => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_color' ),
			),
			EventMetaKeys::TITLE_FONT       => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_font' ),
			),
			EventMetaKeys::BODY_FONT        => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_font' ),
			),
			EventMetaKeys::LOGO_ID          => array(
				'type'     => 'integer',
				'sanitize' => array( self::class, 'sanitize_id' ),
			),
			EventMetaKeys::HEADER_BANNER_ID => array(
				'type'     => 'integer',
				'sanitize' => array( self::class, 'sanitize_id' ),
			),
			EventMetaKeys::POSTER_ID        => array(
				'type'     => 'integer',
				'sanitize' => array( self::class, 'sanitize_id' ),
			),
			EventMetaKeys::IMAGE_SHAPE      => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_image_shape' ),
			),
			EventMetaKeys::SEPARATOR        => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_separator' ),
			),
			EventMetaKeys::CUSTOM_CSS       => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_custom_css' ),
				'auth'     => array( self::class, 'auth_custom_css' ),
			),
			EventMetaKeys::CUSTOM_JS        => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_custom_js' ),
				'auth'     => array( self::class, 'auth_custom_js' ),
			),
			EventMetaKeys::ARCHIVED         => array(
				'type'     => 'boolean',
				'sanitize' => array( self::class, 'sanitize_bool' ),
				'auth'     => array( self::class, 'auth_archived' ),
			),
		);
	}






	public static function register_meta(): void {
		$defaults = array(
			'string'  => '',
			'boolean' => false,
			'integer' => 0,
		);

		foreach ( self::schema() as $key => $spec ) {
			register_post_meta(
				EventPostType::POST_TYPE,
				$key,
				array(
					'type'              => $spec['type'],
					'single'            => true,
					'default'           => $defaults[ $spec['type'] ],
					'sanitize_callback' => $spec['sanitize'],
					'auth_callback'     => $spec['auth'] ?? array( self::class, 'auth_edit_event' ),
					'show_in_rest'      => false,
				)
			);
		}
	}










	public static function auth_edit_event( bool $allowed, string $meta_key, int $post_id, int $user_id ): bool {
		unset( $allowed, $meta_key );
		return user_can( $user_id, 'edit_post', $post_id );
	}










	public static function auth_custom_css( bool $allowed, string $meta_key, int $post_id, int $user_id ): bool {
		unset( $allowed, $meta_key );
		return EventAccess::can_edit_custom_css( $user_id, $post_id );
	}














	public static function auth_custom_js( bool $allowed, string $meta_key, int $post_id, int $user_id ): bool {
		unset( $allowed, $meta_key );
		return EventAccess::can_edit_custom_js( $user_id, $post_id );
	}
















	public static function auth_archived( bool $allowed, string $meta_key, int $post_id, int $user_id ): bool {
		unset( $allowed, $meta_key );
		return EventAccess::can_toggle_archived( $user_id, $post_id );
	}













	public static function sanitize_custom_css( $value ): string {



		$css = wp_strip_all_tags( (string) $value );
		return (string) preg_replace( '#</\s*style#i', '', $css );
	}














	public static function sanitize_custom_js( $value ): string {
		return (string) preg_replace( '#</(?=script)#i', '<\\\\/', (string) $value );
	}







	public static function sanitize_date( $value ): string {
		$value = trim( (string) $value );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
			return '';
		}
		$parts = array_map( 'intval', explode( '-', $value ) );
		return checkdate( $parts[1], $parts[2], $parts[0] ) ? $value : '';
	}







	public static function sanitize_section_type( $value ): string {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}
		return isset( EventMetaKeys::section_types()[ $value ] ) ? $value : EventMetaKeys::SECTION_OTHER;
	}










	public static function sanitize_hashtag( $value ): string {
		$value = sanitize_text_field( (string) $value );
		$value = ltrim( trim( $value ), '#' );
		return (string) preg_replace( '/\s+/u', '', $value );
	}







	public static function sanitize_bool( $value ): bool {
		if ( is_string( $value ) ) {
			return in_array( strtolower( trim( $value ) ), array( '1', 'on', 'true', 'si', 'sí', 'yes' ), true );
		}
		return (bool) $value;
	}







	public static function sanitize_id( $value ): int {
		$id = (int) $value;
		return $id > 0 ? $id : 0;
	}







	public static function sanitize_url( $value ): string {
		$url = esc_url_raw( trim( (string) $value ), array( 'http', 'https' ) );
		return is_string( $url ) ? $url : '';
	}







	public static function sanitize_color( $value ): string {
		$color = sanitize_hex_color( trim( (string) $value ) );
		return is_string( $color ) ? $color : '';
	}







	public static function sanitize_font( $value ): string {
		return EventMetaKeys::in_list( $value, EventMetaKeys::fonts(), EventMetaKeys::FONT_DEFAULT );
	}







	public static function sanitize_image_shape( $value ): string {
		return EventMetaKeys::in_list( $value, EventMetaKeys::image_shapes(), EventMetaKeys::SHAPE_SQUARE );
	}







	public static function sanitize_separator( $value ): string {
		return EventMetaKeys::in_list( $value, EventMetaKeys::separators(), '' );
	}
}








namespace Evt\Meta;













final class ProgrammeMetaKeys {




	public const SPEAKER_ROLE = 'evt_speaker_role';




	public const SPEAKER_ORG = 'evt_speaker_org';




	public const ACTIVITY_KIND = 'evt_activity_kind';




	public const ACTIVITY_DATE = 'evt_activity_date';




	public const ACTIVITY_START = 'evt_activity_start';




	public const ACTIVITY_END = 'evt_activity_end';









	public const ACTIVITY_VENUE = 'evt_activity_venue';




	public const ACTIVITY_ROOM = 'evt_activity_room';




	public const ACTIVITY_SEATS = 'evt_activity_seats';








	public const ACTIVITY_SPEAKERS = 'evt_activity_speakers';




	public const KIND_WORKSHOP = 'taller';






	public static function speaker_keys(): array {
		return array( self::SPEAKER_ROLE, self::SPEAKER_ORG );
	}






	public static function activity_keys(): array {
		return array(
			self::ACTIVITY_KIND,
			self::ACTIVITY_DATE,
			self::ACTIVITY_START,
			self::ACTIVITY_END,
			self::ACTIVITY_VENUE,
			self::ACTIVITY_ROOM,
			self::ACTIVITY_SEATS,
			self::ACTIVITY_SPEAKERS,
		);
	}









	public static function activity_kinds(): array {
		return array(
			'ponencia'     => 'Ponencia',
			'taller'       => 'Taller',
			'mesa'         => 'Mesa redonda',
			'comunicacion' => 'Comunicación',
			'panel'        => 'Panel de experiencias',
			'inauguracion' => 'Inauguración',
			'clausura'     => 'Clausura',
			'descanso'     => 'Descanso',
			'otra'         => 'Otra',
		);
	}







	public static function kind_label( string $kind ): string {
		$lista = self::activity_kinds();
		return $lista[ $kind ] ?? $lista['otra'];
	}
}








namespace Evt\Meta;

use Evt\PostType\ActivityPostType;
use Evt\PostType\SpeakerPostType;











final class ProgrammeMetaRegistration {






	public static function register(): void {
		add_action( 'init', array( self::class, 'register_meta' ), 12 );
	}






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







	public static function sanitize_kind( $value ): string {



		return EventMetaKeys::in_list(
			is_scalar( $value ) ? (string) $value : '',
			ProgrammeMetaKeys::activity_kinds(),
			'otra'
		);
	}












	public static function sanitize_time( $value ): string {
		$texto = is_scalar( $value ) ? trim( (string) $value ) : '';
		if ( ! preg_match( '/^([01]\d|2[0-3]):([0-5]\d)$/', $texto ) ) {
			return '';
		}
		return $texto;
	}







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








namespace Evt\Meta;
















final class RegistrationMetaKeys {



	public const REG_TAX_ID      = 'evt_reg_tax_id';
	public const REG_NAME        = 'evt_reg_name';
	public const REG_SURNAME     = 'evt_reg_surname';
	public const REG_EMAIL       = 'evt_reg_email';
	public const REG_PHONE       = 'evt_reg_phone';
	public const REG_CENTRE      = 'evt_reg_centre';
	public const REG_CENTRE_CODE = 'evt_reg_centre_code';







	public const REG_CONSENT_VERSION = 'evt_reg_consent_version';
	public const REG_CONSENT_AT      = 'evt_reg_consent_at';







	public const REG_WORKSHOP = 'evt_reg_workshop';







	public const REG_ANSWERS = 'evt_reg_answers';










	public const REG_FILES = 'evt_reg_files';






	public const REG_TOKEN = 'evt_reg_token';






	public const SIGNUP_OPEN = 'evt_signup_open';




	public const SIGNUP_QUESTIONS = 'evt_signup_questions';







	public const WORKSHOP_OPEN  = 'evt_workshop_open';
	public const WORKSHOP_START = 'evt_workshop_start';
	public const WORKSHOP_END   = 'evt_workshop_end';







	public const CONSENT_PRIVACY = 'evt_consent_privacy';
	public const CONSENT_IMAGE   = 'evt_consent_image';
	public const CONSENT_VERSION = 'evt_consent_version';






	public static function registration_keys(): array {
		return array(
			self::REG_TAX_ID,
			self::REG_NAME,
			self::REG_SURNAME,
			self::REG_EMAIL,
			self::REG_PHONE,
			self::REG_CENTRE,
			self::REG_CENTRE_CODE,
			self::REG_CONSENT_VERSION,
			self::REG_CONSENT_AT,
			self::REG_WORKSHOP,
			self::REG_ANSWERS,
			self::REG_FILES,
			self::REG_TOKEN,
		);
	}






	public static function signup_keys(): array {
		return array(
			self::SIGNUP_OPEN,
			self::SIGNUP_QUESTIONS,
			self::WORKSHOP_OPEN,
			self::WORKSHOP_START,
			self::WORKSHOP_END,
			self::CONSENT_PRIVACY,
			self::CONSENT_IMAGE,
			self::CONSENT_VERSION,
		);
	}














	public static function question_types(): array {
		return array(
			'check' => 'Casilla',
			'one'   => 'Una opción',
			'many'  => 'Varias opciones',
			'text'  => 'Texto corto',
			'file'  => 'Archivo',
		);
	}







	public static function has_options( string $type ): bool {
		return 'one' === $type || 'many' === $type;
	}
}








namespace Evt\Domain;






















final class DateRange {








	public static function of( string $start, string $end = '' ): string {
		$desde = self::parts( $start );
		if ( array() === $desde ) {
			return '';
		}

		$hasta = self::parts( $end );
		if ( array() === $hasta ) {
			return 'Desde el ' . self::day( $desde );
		}



		if ( $hasta['n'] <= $desde['n'] ) {
			return self::day( $desde );
		}
		if ( $hasta['y'] !== $desde['y'] ) {
			return sprintf( 'Del %s al %s', self::day( $desde ), self::day( $hasta ) );
		}
		if ( $hasta['m'] !== $desde['m'] ) {
			return sprintf( 'Del %d de %s al %s', $desde['d'], self::month( $desde['m'] ), self::day( $hasta ) );
		}
		return sprintf( 'Del %d al %s', $desde['d'], self::day( $hasta ) );
	}







	private static function day( array $day ): string {
		return sprintf( '%d de %s de %d', $day['d'], self::month( $day['m'] ), $day['y'] );
	}











	private static function parts( string $ymd ): array {
		if ( 1 !== preg_match( '/^(\d{4})-(\d{1,2})-(\d{1,2})$/', trim( $ymd ), $trozos ) ) {
			return array();
		}

		$anio = (int) $trozos[1];
		$mes  = (int) $trozos[2];
		$dia  = (int) $trozos[3];
		if ( ! checkdate( $mes, $dia, $anio ) ) {
			return array();
		}

		return array(
			'n' => $anio * 10000 + $mes * 100 + $dia,
			'y' => $anio,
			'm' => $mes,
			'd' => $dia,
		);
	}











	private static function month( int $month ): string {
		global $wp_locale;
		return mb_strtolower( (string) $wp_locale->get_month( $month ), 'UTF-8' );
	}
}








namespace Evt\Domain;

use Evt\Meta\ProgrammeMetaKeys;







final class ActivityInput {










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








	private static function text( array $raw, string $clave ): string {
		return isset( $raw[ $clave ] ) ? trim( (string) $raw[ $clave ] ) : '';
	}







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







	private static function is_date( string $date ): bool {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return false;
		}
		$parts = array_map( 'intval', explode( '-', $date ) );
		return checkdate( $parts[1], $parts[2], $parts[0] );
	}







	private static function is_time( string $time ): bool {
		if ( ! preg_match( '/^([01]\d|2[0-3]):([0-5]\d)$/', $time ) ) {
			return false;
		}
		return true;
	}
}








namespace Evt\Domain;

use Evt\Meta\RegistrationMetaKeys;



















final class SignupQuestions {







	public const MAX_OPTIONS = 50;










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







	private static function clean_id( string $id ): string {
		$id = strtolower( trim( $id ) );
		return (bool) preg_match( '/^q[a-z0-9]{6,32}$/', $id ) ? $id : '';
	}







	public static function is_id( string $id ): bool {
		return '' !== self::clean_id( $id );
	}











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








namespace Evt\Domain;












final class RegistrationInput {












	public static function core( array $raw, ?array $centres = null ): array {
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


		if ( '' === $centre || ! self::is_centre_code( $centre ) ) {
			$errors[] = 'centre';
		} elseif ( is_array( $centres ) ) {
			if ( ! isset( $centres[ $centre ] ) ) {
				$errors[] = 'centre';
			} else {
				$centre_code = $centre;
				$centre_name = (string) $centres[ $centre ];
			}
		} else {
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










	public static function tax_id( string $value ): string {
		return strtoupper( (string) preg_replace( '/[\s.\-]/', '', $value ) );
	}













	public static function is_tax_id( string $value ): bool {
		return (bool) preg_match( '/^[A-Z0-9]{6,15}$/', $value );
	}









	public static function is_centre_code( string $value ): bool {
		return (bool) preg_match( '/^\d{8}$/', trim( $value ) );
	}










	public static function is_email( string $value ): bool {
		return (bool) preg_match( '/^[^@\s]+@[^@\s.]+(\.[^@\s.]+)+$/', $value );
	}







	public static function phone( string $value ): string {
		return trim( (string) preg_replace( '/[^\d+ ]/', '', $value ) );
	}








	private static function text( array $raw, string $key ): string {
		$value = $raw[ $key ] ?? '';
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}
}








namespace Evt\Domain;

use Evt\Meta\EventMetaKeys;






final class EventInput {










	public static function validate( array $raw ): array {
		$errors = array();

		$title   = isset( $raw['title'] ) ? trim( (string) $raw['title'] ) : '';
		$start   = isset( $raw['start_date'] ) ? trim( (string) $raw['start_date'] ) : '';
		$end     = isset( $raw['end_date'] ) ? trim( (string) $raw['end_date'] ) : '';
		$venue   = isset( $raw['venue'] ) ? trim( (string) $raw['venue'] ) : '';
		$section = isset( $raw['section_type'] ) ? trim( (string) $raw['section_type'] ) : '';
		$parent  = isset( $raw['parent'] ) ? (int) $raw['parent'] : 0;

		if ( '' === $title ) {
			$errors[] = 'title';
		}



		$is_root = $parent <= 0;

		if ( $is_root && ! self::is_valid_date( $start ) ) {
			$errors[] = 'start_date';
		}
		if ( '' !== $end && ! self::is_valid_date( $end ) ) {
			$errors[] = 'end_date';
		}
		if ( self::is_valid_date( $start ) && self::is_valid_date( $end ) && $end < $start ) {
			$errors[] = 'date_order';
		}

		if ( '' !== $section && ! isset( EventMetaKeys::section_types()[ $section ] ) ) {
			$errors[] = 'section_type';
		}
		if ( ! $is_root && '' === $section ) {
			$errors[] = 'section_type';
		}

		return array(
			'ok'     => array() === $errors,
			'errors' => $errors,
			'data'   => array(
				'title'        => $title,
				'start_date'   => self::is_valid_date( $start ) ? $start : '',
				'end_date'     => self::is_valid_date( $end ) ? $end : '',
				'venue'        => $venue,
				'section_type' => $is_root ? '' : $section,
				'parent'       => max( 0, $parent ),
			),
		);
	}







	private static function is_valid_date( string $date ): bool {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return false;
		}
		$parts = array_map( 'intval', explode( '-', $date ) );
		return checkdate( $parts[1], $parts[2], $parts[0] );
	}
}








namespace Evt\Domain;

use Evt\Meta\EventMetaKeys;












final class EventState {









	public static function of( string $start, string $end, string $today = '' ): string {
		$start = trim( $start );
		$end   = trim( $end );
		$today = '' !== trim( $today ) ? trim( $today ) : gmdate( 'Y-m-d' );


		if ( '' === $start ) {
			return EventMetaKeys::STATE_UPCOMING;
		}
		if ( '' === $end || $end < $start ) {
			$end = $start;
		}
		if ( $today < $start ) {
			return EventMetaKeys::STATE_UPCOMING;
		}
		if ( $today > $end ) {
			return EventMetaKeys::STATE_FINISHED;
		}
		return EventMetaKeys::STATE_OPEN;
	}







	public static function label( string $state ): string {
		return (string) ( EventMetaKeys::states()[ $state ] ?? '' );
	}
}








namespace Evt\Access;

use Evt\Meta\EventMetaKeys;
use Evt\PostType\ActivityPostType;
use Evt\PostType\EventPostType;
use Evt\PostType\SpeakerPostType;
use Evt\Taxonomy\EventTaxonomies;








final class EventAccess {




	public const USER_AREA_META = 'evt_area';




	public const CAP_MANAGE = 'evt_manage_app';




	public const CAP_ALL_AREAS = 'evt_edit_all_areas';








	public const CAP_CUSTOM_CSS = 'evt_edit_custom_css';









	public const CAP_CUSTOM_JS = 'evt_edit_custom_js';






	public static function register(): void {
		add_filter( 'map_meta_cap', array( self::class, 'map_meta_cap' ), 10, 4 );
		add_action( 'save_post_' . SpeakerPostType::POST_TYPE, array( self::class, 'stamp_area' ) );
		add_action( 'save_post_' . ActivityPostType::POST_TYPE, array( self::class, 'stamp_area' ) );
	}










	public static function scoped_types(): array {
		return array(
			EventPostType::POST_TYPE    => 'edit_evt_events',
			SpeakerPostType::POST_TYPE  => 'edit_evt_speakers',
			ActivityPostType::POST_TYPE => 'edit_evt_activities',
		);
	}







	public static function is_manager( int $user_id = 0 ): bool {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		return user_can( $user_id, self::CAP_MANAGE ) || user_can( $user_id, 'manage_options' );
	}







	public static function can_edit_all_areas( int $user_id = 0 ): bool {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		return user_can( $user_id, self::CAP_ALL_AREAS ) || self::is_manager( $user_id );
	}







	public static function user_areas( int $user_id = 0 ): array {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		if ( $user_id <= 0 ) {
			return array();
		}
		$raw = get_user_meta( $user_id, self::USER_AREA_META, true );
		if ( ! is_array( $raw ) ) {
			$raw = '' === trim( (string) $raw ) ? array() : explode( ',', (string) $raw );
		}
		return self::clean_ids( $raw );
	}














	public static function post_areas( int $post_id ): array {
		$terms = get_the_terms( self::root_id( $post_id ), EventTaxonomies::AREA );
		if ( ! is_array( $terms ) ) {
			return array();
		}
		return self::clean_ids( wp_list_pluck( $terms, 'term_id' ) );
	}







	public static function root_id( int $post_id ): int {
		$guard = 0;
		while ( $post_id > 0 && $guard < 10 ) {
			$parent = (int) get_post_field( 'post_parent', $post_id );
			if ( $parent <= 0 ) {
				break;
			}
			$post_id = $parent;
			++$guard;
		}
		return $post_id;
	}














	public static function is_archived( int $post_id ): bool {
		if ( $post_id <= 0 ) {
			return false;
		}
		return (bool) get_post_meta( self::root_id( $post_id ), EventMetaKeys::ARCHIVED, true );
	}





















	public static function can_archive( int $user_id, int $post_id ): bool {
		return self::can_open( $user_id, $post_id );
	}












	public static function can_unarchive( int $user_id = 0 ): bool {
		return self::is_manager( $user_id );
	}















	public static function can_toggle_archived( int $user_id, int $post_id ): bool {
		return self::is_archived( $post_id )
			? self::can_unarchive( $user_id )
			: self::can_archive( $user_id, $post_id );
	}














	public static function can_open( int $user_id, int $post_id ): bool {
		if ( $user_id <= 0 || $post_id <= 0 ) {
			return false;
		}
		$cap = self::scoped_cap( $post_id );
		if ( '' === $cap ) {
			return false;
		}
		if ( self::can_edit_all_areas( $user_id ) ) {
			return true;
		}
		if ( ! user_can( $user_id, $cap ) ) {
			return false;
		}

		$mine = self::user_areas( $user_id );
		if ( array() === $mine ) {
			return false;
		}

		$theirs = self::post_areas( $post_id );
		if ( array() === $theirs ) {






			return (int) get_post_field( 'post_author', self::root_id( $post_id ) ) === $user_id;
		}

		return array() !== array_intersect( $mine, $theirs );
	}
























	public static function can_edit( int $user_id, int $post_id ): bool {
		if ( ! self::can_open( $user_id, $post_id ) ) {
			return false;
		}
		return ! self::is_archived( $post_id ) || self::is_manager( $user_id );
	}







	private static function scoped_cap( int $post_id ): string {
		$tipos = self::scoped_types();
		$tipo  = (string) get_post_type( $post_id );
		return isset( $tipos[ $tipo ] ) ? $tipos[ $tipo ] : '';
	}














	public static function stamp_area( int $post_id ): void {
		if ( array() !== self::post_areas( $post_id ) ) {
			return;
		}
		$areas = self::user_areas( (int) get_post_field( 'post_author', $post_id ) );
		if ( array() === $areas ) {
			return;
		}
		wp_set_object_terms( $post_id, $areas, EventTaxonomies::AREA );
	}








	public static function can_publish( int $user_id, int $post_id = 0 ): bool {
		if ( $user_id <= 0 || ! user_can( $user_id, 'publish_evt_events' ) ) {
			return false;
		}
		if ( $post_id <= 0 ) {
			return true;
		}
		return self::can_edit( $user_id, $post_id );
	}











	public static function can_edit_custom_css( int $user_id, int $post_id ): bool {
		return self::can_edit_code( $user_id, $post_id, self::CAP_CUSTOM_CSS );
	}













	public static function can_edit_custom_js( int $user_id, int $post_id ): bool {
		if ( ! self::can_edit_code( $user_id, $post_id, self::CAP_CUSTOM_JS ) ) {
			return false;
		}

		$suelto = ! is_multisite() || user_can( $user_id, 'unfiltered_html' );

















		return (bool) apply_filters( 'evt_allow_custom_js', $suelto, $user_id, $post_id );
	}









	private static function can_edit_code( int $user_id, int $post_id, string $cap ): bool {
		if ( $user_id <= 0 || ! user_can( $user_id, $cap ) ) {
			return false;
		}
		return self::can_edit( $user_id, $post_id );
	}










	public static function why_not_editable( int $user_id, int $post_id ): string {
		if ( self::can_edit( $user_id, $post_id ) ) {
			return '';
		}


		if ( self::can_open( $user_id, $post_id ) ) {
			return 'Este evento está marcado como histórico: se puede consultar y exportar, pero ya no se edita. Para volver a abrirlo, pídalo a quien administre el aplicativo.';
		}
		$cap = self::scoped_cap( $post_id );
		if ( '' === $cap || ! user_can( $user_id, $cap ) ) {
			return 'Su perfil no organiza eventos. Si debería hacerlo, pídalo a quien administre el aplicativo.';
		}
		if ( array() === self::user_areas( $user_id ) ) {
			return 'No tiene ningún área asignada en su perfil, así que no puede editar eventos. El área la pone quien administra el aplicativo.';
		}
		$nombres = array(
			EventPostType::POST_TYPE    => 'Este evento',
			SpeakerPostType::POST_TYPE  => 'Este ponente',
			ActivityPostType::POST_TYPE => 'Esta actividad',
		);
		$que     = $nombres[ (string) get_post_type( $post_id ) ] ?? 'Esto';
		return $que . ' es de otra área. Solo lo edita el área que lo organiza o quien administra el aplicativo.';
	}



















	public static function map_meta_cap( array $caps, string $cap, int $user_id, array $args ): array {
		if ( ! in_array( $cap, array( 'edit_post', 'delete_post', 'publish_post', 'read_post' ), true ) ) {
			return $caps;
		}
		$post_id = isset( $args[0] ) ? (int) $args[0] : 0;
		if ( $post_id <= 0 || ! isset( self::scoped_types()[ (string) get_post_type( $post_id ) ] ) ) {
			return $caps;
		}
		if ( 'read_post' === $cap ) {
			return self::map_read_post( $caps, $user_id, $post_id );
		}
		if ( ! self::can_edit( $user_id, $post_id ) ) {
			return array( 'do_not_allow' );
		}
		return $caps;
	}


















	private static function map_read_post( array $caps, int $user_id, int $post_id ): array {
		$status = get_post_status_object( (string) get_post_status( $post_id ) );
		if ( null !== $status && $status->public ) {
			return $caps;
		}
		return self::can_open( $user_id, $post_id ) ? $caps : array( 'do_not_allow' );
	}







	private static function clean_ids( array $values ): array {
		$out = array();
		foreach ( $values as $value ) {
			$id = (int) $value;
			if ( $id > 0 ) {
				$out[] = $id;
			}
		}
		return array_values( array_unique( $out ) );
	}
}








namespace Evt\PostType;

use Evt\Access\EventAccess;








final class EventPostType {

	public const POST_TYPE = 'evt_event';




	public const TEMPLATE_META = '_wp_page_template';




	public const TEMPLATE_DEFAULT = 'default';






	public static function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'               => 'Eventos',
					'singular_name'      => 'Evento',
					'add_new'            => 'Añadir evento',
					'add_new_item'       => 'Añadir evento',
					'edit_item'          => 'Editar evento',
					'new_item'           => 'Nuevo evento',
					'view_item'          => 'Ver evento',
					'search_items'       => 'Buscar eventos',
					'not_found'          => 'No se encontraron eventos',
					'not_found_in_trash' => 'No hay eventos en la papelera',
					'parent_item_colon'  => 'Página del evento:',
					'menu_name'          => 'Eventos',
				),
				'public'          => true,
				'hierarchical'    => true,
				'show_in_menu'    => true,
				'menu_position'   => 21,
				'menu_icon'       => 'dashicons-calendar-alt',
				'supports'        => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'page-attributes' ),
				'has_archive'     => false,


				'rewrite'         => array(
					'slug'       => 'evento',
					'with_front' => false,
				),
				'capability_type' => array( 'evt_event', 'evt_events' ),
				'map_meta_cap'    => true,
				'capabilities'    => self::capabilities(),
				'show_in_rest'    => true,
			)
		);





		add_filter( 'theme_' . self::POST_TYPE . '_templates', array( self::class, 'theme_templates' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( self::class, 'set_blank_template' ) );
	}












	public static function theme_templates( $templates ): array {
		$propias = is_array( $templates ) ? $templates : array();

		return array_merge( wp_get_theme()->get_page_templates( null, 'page' ), $propias );
	}






	public static function page_templates(): array {
		return array_merge(
			array( self::TEMPLATE_DEFAULT => 'La del tema' ),
			wp_get_theme()->get_page_templates( null, self::POST_TYPE )
		);
	}











	public static function blank_template(): string {
		$plantillas = wp_get_theme()->get_page_templates( null, self::POST_TYPE );
		$encontrada = '';

		foreach ( $plantillas as $fichero => $rotulo ) {
			if ( preg_match( '/blank|en\s*blanco|vac[ií]a/iu', (string) $fichero . ' ' . (string) $rotulo ) ) {
				$encontrada = (string) $fichero;
				break;
			}
		}










		return (string) apply_filters( 'evt_blank_page_template', $encontrada, $plantillas );
	}












	public static function set_blank_template( int $post_id ): void {
		if ( metadata_exists( 'post', $post_id, self::TEMPLATE_META ) ) {
			return;
		}

		$plantilla = self::blank_template();
		if ( '' === $plantilla ) {
			return;
		}

		update_post_meta( $post_id, self::TEMPLATE_META, $plantilla );
	}











	public static function capabilities(): array {
		return self::cap_map( 'evt_event', 'evt_events' );
	}








	public static function cap_map( string $one, string $many ): array {
		return array(
			'edit_post'              => 'edit_' . $one,
			'read_post'              => 'read_' . $one,
			'delete_post'            => 'delete_' . $one,
			'edit_posts'             => 'edit_' . $many,
			'edit_others_posts'      => 'edit_others_' . $many,
			'publish_posts'          => 'publish_' . $many,
			'read_private_posts'     => 'read_private_' . $many,
			'delete_posts'           => 'delete_' . $many,
			'delete_private_posts'   => 'delete_private_' . $many,
			'delete_published_posts' => 'delete_published_' . $many,
			'delete_others_posts'    => 'delete_others_' . $many,
			'edit_private_posts'     => 'edit_private_' . $many,
			'edit_published_posts'   => 'edit_published_' . $many,
		);
	}









	public static function grant_caps_to_roles(): void {
		$maps = array(
			self::POST_TYPE                 => self::capabilities(),
			SpeakerPostType::POST_TYPE      => SpeakerPostType::capabilities(),
			ActivityPostType::POST_TYPE     => ActivityPostType::capabilities(),
			RegistrationPostType::POST_TYPE => RegistrationPostType::capabilities(),
		);

		$every = array_keys( self::capabilities() );





		$organiser_events = array(
			'edit_post',
			'read_post',
			'delete_post',
			'edit_posts',
			'edit_others_posts',
			'publish_posts',
			'read_private_posts',
			'delete_posts',
			'delete_published_posts',
			'edit_published_posts',
		);









		$organiser_registrations = array(
			'edit_post',
			'read_post',
			'delete_post',
			'edit_posts',
			'edit_others_posts',
			'read_private_posts',
			'delete_posts',
			'delete_others_posts',
			'delete_private_posts',
			'edit_private_posts',
		);

		$by_role = array(
			'evt_organiser' => array(
				self::POST_TYPE                 => $organiser_events,
				SpeakerPostType::POST_TYPE      => $every,
				ActivityPostType::POST_TYPE     => $every,
				RegistrationPostType::POST_TYPE => $organiser_registrations,
			),
			'administrator' => array_fill_keys( array_keys( $maps ), $every ),
		);

		foreach ( $by_role as $slug => $por_tipo ) {
			$role = get_role( $slug );
			if ( ! $role ) {
				continue;
			}
			foreach ( $maps as $tipo => $map ) {
				foreach ( $por_tipo[ $tipo ] as $key ) {
					if ( isset( $map[ $key ] ) && ! $role->has_cap( $map[ $key ] ) ) {
						$role->add_cap( $map[ $key ] );
					}
				}
			}
		}

		self::grant_code_caps();
	}





















	public static function grant_code_caps(): void {
		$por_rol = array(
			'evt_organiser' => array( EventAccess::CAP_CUSTOM_CSS ),
			'administrator' => self::code_caps(),
		);

		foreach ( $por_rol as $slug => $caps ) {
			$role = get_role( $slug );
			if ( ! $role ) {
				continue;
			}
			foreach ( $caps as $cap ) {
				if ( ! $role->has_cap( $cap ) ) {
					$role->add_cap( $cap );
				}
			}
		}
	}






	public static function code_caps(): array {
		return array( EventAccess::CAP_CUSTOM_CSS, EventAccess::CAP_CUSTOM_JS );
	}









	public static function admin_only_code_caps(): array {
		return array( EventAccess::CAP_CUSTOM_JS );
	}
}








namespace Evt\PostType;

use Evt\Taxonomy\EventTaxonomies;








final class SpeakerPostType {

	public const POST_TYPE = 'evt_speaker';






	public static function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'               => 'Ponentes',
					'singular_name'      => 'Ponente',
					'add_new'            => 'Añadir ponente',
					'add_new_item'       => 'Añadir ponente',
					'edit_item'          => 'Editar ponente',
					'new_item'           => 'Nuevo ponente',
					'view_item'          => 'Ver ponente',
					'search_items'       => 'Buscar ponentes',
					'not_found'          => 'No se encontraron ponentes',
					'not_found_in_trash' => 'No hay ponentes en la papelera',
					'menu_name'          => 'Ponentes',
				),


				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'edit.php?post_type=' . EventPostType::POST_TYPE,
				'supports'        => array( 'title', 'editor', 'thumbnail', 'author' ),
				'has_archive'     => false,



				'taxonomies'      => array( EventTaxonomies::AREA ),
				'rewrite'         => false,
				'query_var'       => false,
				'capability_type' => array( 'evt_speaker', 'evt_speakers' ),
				'map_meta_cap'    => true,
				'capabilities'    => self::capabilities(),
				'show_in_rest'    => true,
			)
		);
	}






	public static function capabilities(): array {
		return EventPostType::cap_map( 'evt_speaker', 'evt_speakers' );
	}
}








namespace Evt\PostType;

use Evt\Taxonomy\EventTaxonomies;








final class ActivityPostType {

	public const POST_TYPE = 'evt_activity';






	public static function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'               => 'Actividades',
					'singular_name'      => 'Actividad',
					'add_new'            => 'Añadir actividad',
					'add_new_item'       => 'Añadir actividad',
					'edit_item'          => 'Editar actividad',
					'new_item'           => 'Nueva actividad',
					'view_item'          => 'Ver actividad',
					'search_items'       => 'Buscar actividades',
					'not_found'          => 'No se encontraron actividades',
					'not_found_in_trash' => 'No hay actividades en la papelera',
					'menu_name'          => 'Actividades',
				),

				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'edit.php?post_type=' . EventPostType::POST_TYPE,
				'supports'        => array( 'title', 'editor', 'author' ),
				'has_archive'     => false,



				'taxonomies'      => array( EventTaxonomies::AREA ),
				'rewrite'         => false,
				'query_var'       => false,
				'capability_type' => array( 'evt_activity', 'evt_activities' ),
				'map_meta_cap'    => true,
				'capabilities'    => self::capabilities(),
				'show_in_rest'    => true,
			)
		);
	}






	public static function capabilities(): array {
		return EventPostType::cap_map( 'evt_activity', 'evt_activities' );
	}
}








namespace Evt\PostType;



















final class RegistrationPostType {

	public const POST_TYPE = 'evt_registration';






	public static function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'          => 'Inscripciones',
					'singular_name' => 'Inscripción',
					'menu_name'     => 'Inscripciones',
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'title' ),
				'capability_type'     => array( 'evt_registration', 'evt_registrations' ),
				'map_meta_cap'        => true,
				'capabilities'        => self::capabilities(),
				'delete_with_user'    => false,
			)
		);
	}






	public static function capabilities(): array {
		return EventPostType::cap_map( 'evt_registration', 'evt_registrations' );
	}
}








namespace Evt\PublicFront;






final class ExitSignal extends \RuntimeException {






	public $url = '';






	public function __construct( string $url = '' ) {
		parent::__construct( '' === $url ? 'Salida sin redirección' : 'Redirección a ' . $url );
		$this->url = $url;
	}
}








namespace Evt\Centre;








final class CentreCatalogue {

	public const OPTION_CATALOGUE = 'evt_centres_catalogue';

	public const OPTION_STATUS = 'evt_centres_catalogue_status';






	public static function all(): array {
		$raw = get_option( self::OPTION_CATALOGUE, array() );
		return is_array( $raw ) ? $raw : array();
	}






	public static function all_active(): array {
		$all = self::all();
		$out = array();
		foreach ( $all as $code => $centre ) {
			if ( ! empty( $centre['active'] ) ) {
				$out[ $code ] = $centre;
			}
		}
		return $out;
	}






	public static function active_options(): array {
		$active = self::all_active();
		$out    = array();
		foreach ( $active as $code => $centre ) {
			$name = isset( $centre['name'] ) ? trim( (string) $centre['name'] ) : '';
			if ( '' !== $name ) {
				$out[ (string) $code ] = $name;
			}
		}

		uasort(
			$out,
			static function ( string $a, string $b ): int {
				return strcoll( $a, $b );
			}
		);

		return $out;
	}







	public static function find( string $code ): ?array {
		$code = trim( $code );
		if ( '' === $code ) {
			return null;
		}
		$all = self::all();
		return isset( $all[ $code ] ) && is_array( $all[ $code ] ) ? $all[ $code ] : null;
	}







	public static function is_active( string $code ): bool {
		$centre = self::find( $code );
		return null !== $centre && ! empty( $centre['active'] );
	}






	public static function status(): array {
		$defaults = array(
			'schema_version'       => 0,
			'sha256'               => '',
			'catalogue_updated_at' => '',
			'last_checked_at'      => '',
			'last_success_at'      => '',
			'last_error'           => '',
			'record_count'         => 0,
			'active_count'         => 0,
		);

		$raw = get_option( self::OPTION_STATUS, array() );
		if ( ! is_array( $raw ) ) {
			return $defaults;
		}

		return array_merge( $defaults, $raw );
	}






	public static function count(): int {
		return count( self::all() );
	}






	public static function active_count(): int {
		return count( self::all_active() );
	}
}








namespace Evt\Centre;

use RuntimeException;








final class CentreCatalogueSync {

	public const OPTION_MANIFEST_URL = 'evt_centres_manifest_url';

	public const OPTION_CATALOGUE_URL = 'evt_centres_catalogue_url';

	public const OPTION_LOCK = 'evt_centres_sync_lock';

	public const LOCK_TTL = 300;

	public const CRON_HOOK = 'evt_centres_cron_sync';

	public const HTTP_TIMEOUT = 30;






	public static function register_cron(): void {
		add_action( self::CRON_HOOK, array( self::class, 'cron_sync' ) );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + 3600, 'daily', self::CRON_HOOK );
		}
	}






	public static function cron_sync(): void {
		try {
			self::sync( false );
		} catch ( RuntimeException $e ) {

			unset( $e );
		}
	}







	public static function is_valid_https_url( string $url ): bool {
		$url = trim( $url );
		if ( '' === $url ) {
			return false;
		}
		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
		$host   = wp_parse_url( $url, PHP_URL_HOST );
		return 'https' === strtolower( (string) $scheme ) && '' !== trim( (string) $host );
	}






	public static function manifest_url(): string {
		$url = '';
		if ( defined( 'EVT_CENTRES_MANIFEST_URL' ) ) {
			$url = (string) EVT_CENTRES_MANIFEST_URL;
		}
		if ( '' === $url ) {
			$url = (string) get_option( self::OPTION_MANIFEST_URL, '' );
		}





		$filtered = apply_filters( 'evt_centres_manifest_url', $url );
		return is_string( $filtered ) ? trim( $filtered ) : '';
	}







	public static function catalogue_url( string $manifest_url = '' ): string {
		$url = '';
		if ( defined( 'EVT_CENTRES_CATALOGUE_URL' ) ) {
			$url = (string) EVT_CENTRES_CATALOGUE_URL;
		}
		if ( '' === $url ) {
			$url = (string) get_option( self::OPTION_CATALOGUE_URL, '' );
		}
		if ( '' === $url && '' !== $manifest_url ) {
			$dir = dirname( $manifest_url );
			if ( 'http:' === $dir || 'https:' === $dir ) {
				$dir = $manifest_url;
			}
			$url = trailingslashit( $dir ) . 'centros.min.json';
		}





		$filtered = apply_filters( 'evt_centres_catalogue_url', $url );
		return is_string( $filtered ) ? trim( $filtered ) : '';
	}










	public static function acquire_lock() {
		$token   = wp_generate_password( 32, false );
		$payload = array(
			'token' => $token,
			'time'  => time(),
		);

		wp_cache_delete( self::OPTION_LOCK, 'options' );
		wp_cache_delete( 'notoptions', 'options' );

		if ( add_option( self::OPTION_LOCK, $payload, '', 'no' ) ) {
			return $token;
		}

		$current = get_option( self::OPTION_LOCK );
		if ( is_array( $current ) && isset( $current['time'] ) ) {
			$elapsed = time() - (int) $current['time'];
			if ( $elapsed > self::LOCK_TTL ) {
				delete_option( self::OPTION_LOCK );
				wp_cache_delete( self::OPTION_LOCK, 'options' );
				wp_cache_delete( 'notoptions', 'options' );

				if ( add_option( self::OPTION_LOCK, $payload, '', 'no' ) ) {
					return $token;
				}
			}
		}

		return false;
	}







	public static function release_lock( string $token ): bool {
		wp_cache_delete( self::OPTION_LOCK, 'options' );
		$current = get_option( self::OPTION_LOCK );
		if ( is_array( $current ) && isset( $current['token'] ) && hash_equals( (string) $current['token'], $token ) ) {
			return delete_option( self::OPTION_LOCK );
		}
		return false;
	}








	public static function sync( bool $force = false ): array {
		$token = self::acquire_lock();
		if ( false === $token ) {
			throw new RuntimeException( 'Hay otra sincronización de centros en curso. Espere a que finalice.' );
		}

		$status = CentreCatalogue::status();

		try {
			$manifest_url = self::manifest_url();
			if ( ! self::is_valid_https_url( $manifest_url ) ) {
				throw new RuntimeException( 'URL de manifest de centros inválida o no utiliza HTTPS.' );
			}


			$manifest_response = wp_remote_get(
				$manifest_url,
				array(
					'timeout'    => self::HTTP_TIMEOUT,
					'sslverify'  => true,
					'user-agent' => 'WordPress/Evt',
				)
			);

			if ( is_wp_error( $manifest_response ) ) {

				throw new RuntimeException( 'Error al descargar manifest.json: ' . $manifest_response->get_error_message() );
			}

			$code = wp_remote_retrieve_response_code( $manifest_response );
			if ( 200 !== $code ) {

				throw new RuntimeException( sprintf( 'El servidor devolvió HTTP %d al solicitar manifest.json.', $code ) );
			}

			$manifest_body = wp_remote_retrieve_body( $manifest_response );
			$manifest      = json_decode( $manifest_body, true );

			if ( ! is_array( $manifest ) ) {
				throw new RuntimeException( 'El archivo manifest.json no contiene un JSON válido.' );
			}


			if ( empty( $manifest['schema_version'] ) || 1 !== (int) $manifest['schema_version'] ) {
				throw new RuntimeException( 'Versión de esquema incompatible en manifest.json.' );
			}

			if ( empty( $manifest['files']['centros.min.json']['sha256'] ) ) {
				throw new RuntimeException( 'El manifest.json no declara la entrada de centros.min.json con su sha256.' );
			}

			$remote_sha256 = trim( (string) $manifest['files']['centros.min.json']['sha256'] );
			$remote_count  = isset( $manifest['files']['centros.min.json']['records'] )
				? (int) $manifest['files']['centros.min.json']['records']
				: 0;
			$updated_at    = isset( $manifest['catalogue_updated_at'] ) && is_scalar( $manifest['catalogue_updated_at'] )
				? trim( (string) $manifest['catalogue_updated_at'] )
				: '';


			if ( ! $force && $remote_sha256 === $status['sha256'] && ! empty( $status['sha256'] ) && CentreCatalogue::count() > 0 ) {
				$status['last_checked_at'] = current_time( 'mysql' );
				$status['last_error']      = '';
				update_option( CentreCatalogue::OPTION_STATUS, $status, false );

				return array(
					'status'  => 'unchanged',
					'sha256'  => $remote_sha256,
					'records' => $status['record_count'],
					'active'  => $status['active_count'],
				);
			}


			$catalogue_url = self::catalogue_url( $manifest_url );
			if ( ! self::is_valid_https_url( $catalogue_url ) ) {
				throw new RuntimeException( 'URL del catálogo de centros inválida o no utiliza HTTPS.' );
			}

			$cat_response = wp_remote_get(
				$catalogue_url,
				array(
					'timeout'    => 45,
					'sslverify'  => true,
					'user-agent' => 'WordPress/Evt',
				)
			);

			if ( is_wp_error( $cat_response ) ) {

				throw new RuntimeException( 'Error al descargar centros.min.json: ' . $cat_response->get_error_message() );
			}

			$cat_code = wp_remote_retrieve_response_code( $cat_response );
			if ( 200 !== $cat_code ) {

				throw new RuntimeException( sprintf( 'El servidor devolvió HTTP %d al solicitar centros.min.json.', $cat_code ) );
			}

			$cat_body = wp_remote_retrieve_body( $cat_response );


			$computed_sha256 = hash( 'sha256', $cat_body );
			if ( ! hash_equals( $remote_sha256, $computed_sha256 ) ) {
				throw new RuntimeException( 'El hash SHA-256 del archivo descargado no coincide con el declarado en manifest.json.' );
			}


			$items = json_decode( $cat_body, true );
			if ( ! is_array( $items ) ) {
				throw new RuntimeException( 'El archivo centros.min.json no contiene un array JSON válido.' );
			}

			if ( $remote_count > 0 && count( $items ) !== $remote_count ) {
				$msg = sprintf( 'El número de registros (%d) no coincide con el declarado en el manifest (%d).', count( $items ), $remote_count );

				throw new RuntimeException( $msg );
			}

			$indexed      = array();
			$active_count = 0;

			foreach ( $items as $idx => $item ) {
				if ( ! is_array( $item ) ) {
					$msg = sprintf( 'Registro en posición %d no es un objeto válido.', $idx );

					throw new RuntimeException( $msg );
				}

				$code = isset( $item['code'] ) && is_scalar( $item['code'] ) ? trim( (string) $item['code'] ) : '';
				if ( 1 !== preg_match( '/^\d{8}$/', $code ) ) {
					$msg = sprintf( 'Código de centro inválido en registro %d (debe tener exactamente 8 dígitos): "%s".', $idx, $code );

					throw new RuntimeException( $msg );
				}

				if ( isset( $indexed[ $code ] ) ) {
					$msg = sprintf( 'Código oficial duplicado en centros.min.json: "%s".', $code );

					throw new RuntimeException( $msg );
				}

				$name = isset( $item['name'] ) && is_scalar( $item['name'] ) ? trim( (string) $item['name'] ) : '';
				if ( '' === $name ) {
					$msg = sprintf( 'Denominación vacía para el centro con código "%s".', $code );

					throw new RuntimeException( $msg );
				}

				if ( ! isset( $item['active'] ) || ! is_bool( $item['active'] ) ) {
					$msg = sprintf( 'El campo "active" debe ser booleano para el centro con código "%s".', $code );

					throw new RuntimeException( $msg );
				}

				$is_active = (bool) $item['active'];
				if ( $is_active ) {
					++$active_count;
				}

				$indexed[ $code ] = array(
					'code'         => $code,
					'name'         => $name,
					'island'       => isset( $item['island'] ) && is_scalar( $item['island'] ) ? trim( (string) $item['island'] ) : '',
					'municipality' => isset( $item['municipality'] ) && is_scalar( $item['municipality'] ) ? trim( (string) $item['municipality'] ) : '',
					'type'         => isset( $item['type'] ) && is_scalar( $item['type'] ) ? trim( (string) $item['type'] ) : '',
					'active'       => $is_active,
				);
			}


			update_option( CentreCatalogue::OPTION_CATALOGUE, $indexed, false );

			$status = array(
				'schema_version'       => 1,
				'sha256'               => $remote_sha256,
				'catalogue_updated_at' => $updated_at,
				'last_checked_at'      => current_time( 'mysql' ),
				'last_success_at'      => current_time( 'mysql' ),
				'last_error'           => '',
				'record_count'         => count( $indexed ),
				'active_count'         => $active_count,
			);
			update_option( CentreCatalogue::OPTION_STATUS, $status, false );

			return array(
				'status'  => 'updated',
				'sha256'  => $remote_sha256,
				'records' => count( $indexed ),
				'active'  => $active_count,
			);
		} catch ( RuntimeException $e ) {
			$status['last_checked_at'] = current_time( 'mysql' );
			$status['last_error']      = $e->getMessage();
			update_option( CentreCatalogue::OPTION_STATUS, $status, false );

			throw $e;
		} finally {
			self::release_lock( $token );
		}
	}
}








namespace Evt\Centre;








final class CentreCatalog {






	public static function register(): void {
		add_filter( 'evt_centres', array( self::class, 'provide_centres' ), 5, 1 );
	}







	public static function provide_centres( array $centres ): array {
		if ( array() !== $centres ) {
			return $centres;
		}

		return CentreCatalogue::active_options();
	}
}








namespace Evt\Meta;

use Evt\Domain\SignupQuestions;
use Evt\PostType\RegistrationPostType;
use Evt\PostType\EventPostType;













final class RegistrationMetaRegistration {






	public static function register(): void {
		add_action( 'init', array( self::class, 'register_meta' ), 12 );
	}






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
			RegistrationMetaKeys::REG_CENTRE_CODE     => array(
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
			RegistrationMetaKeys::REG_FILES           => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_files' ),
			),
			RegistrationMetaKeys::REG_TOKEN           => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_token' ),
			),
		);
	}






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







	public static function sanitize_tax_id( $value ): string {
		return \Evt\Domain\RegistrationInput::tax_id( is_scalar( $value ) ? (string) $value : '' );
	}







	public static function sanitize_token( $value ): string {
		$token = is_scalar( $value ) ? strtolower( trim( (string) $value ) ) : '';
		return (bool) preg_match( '/^[a-f0-9]{40}$/', $token ) ? $token : '';
	}







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













	public static function sanitize_files( $value ): string {
		if ( is_string( $value ) ) {
			$value = '' === trim( $value ) ? array() : json_decode( $value, true );
		}
		if ( ! is_array( $value ) ) {
			return '';
		}

		$out = array();
		foreach ( $value as $id => $descriptor ) {
			$id = (string) $id;
			if ( ! SignupQuestions::is_id( $id ) || ! is_array( $descriptor ) ) {
				continue;
			}
			$opaco  = isset( $descriptor['id'] ) ? strtolower( (string) $descriptor['id'] ) : '';
			$stored = isset( $descriptor['stored'] ) ? (string) $descriptor['stored'] : '';
			$sha    = isset( $descriptor['sha256'] ) ? strtolower( (string) $descriptor['sha256'] ) : '';
			if ( ! preg_match( '/^[a-f0-9]{32}$/', $opaco )
				|| ! preg_match( '#^[a-f0-9]{2}/[a-f0-9]{2}/[a-f0-9]{32}\.[a-z0-9]{1,8}$#', $stored )
				|| ! preg_match( '/^[a-f0-9]{64}$/', $sha ) ) {
				continue;
			}

			$out[ $id ] = array(
				'id'     => $opaco,
				'name'   => sanitize_file_name( (string) ( $descriptor['name'] ?? '' ) ),
				'mime'   => sanitize_mime_type( (string) ( $descriptor['mime'] ?? '' ) ),
				'size'   => max( 0, (int) ( $descriptor['size'] ?? 0 ) ),
				'sha256' => $sha,
				'stored' => $stored,
			);
		}

		return (string) wp_json_encode( $out );
	}







	public static function sanitize_questions( $value ): string {
		$preguntas = SignupQuestions::read( $value );
		foreach ( $preguntas as $i => $pregunta ) {
			$preguntas[ $i ]['label']   = sanitize_text_field( (string) $pregunta['label'] );
			$preguntas[ $i ]['options'] = array_map( 'sanitize_text_field', (array) $pregunta['options'] );
		}
		return (string) wp_json_encode( $preguntas );
	}







	public static function sanitize_bool( $value ): bool {
		return (bool) $value;
	}
}








namespace Evt\Taxonomy;

use Evt\Access\EventAccess;
use Evt\PostType\EventPostType;














final class EventTaxonomies {




	public const AREA = 'evt_area';




	public const TYPE = 'evt_type';




	public const COURSE = 'evt_course';






	public static function register(): void {
		register_taxonomy( self::AREA, EventPostType::POST_TYPE, self::args( 'Áreas organizadoras', 'Área organizadora' ) );
		register_taxonomy( self::TYPE, EventPostType::POST_TYPE, self::args( 'Tipologías', 'Tipología' ) );
		register_taxonomy( self::COURSE, EventPostType::POST_TYPE, self::args( 'Cursos escolares', 'Curso escolar' ) );
	}











	private static function args( string $plural, string $singular ): array {
		return array(
			'labels'             => array(
				'name'          => $plural,
				'singular_name' => $singular,
				'search_items'  => 'Buscar en ' . $plural,
				'all_items'     => 'Todas: ' . $plural,
				'edit_item'     => 'Editar ' . $singular,
				'update_item'   => 'Actualizar ' . $singular,
				'add_new_item'  => 'Añadir ' . $singular,
				'new_item_name' => 'Nombre de ' . $singular,
				'not_found'     => 'No se encontró ninguna coincidencia',
				'menu_name'     => $plural,
			),
			'public'             => true,
			'hierarchical'       => true,
			'show_admin_column'  => true,
			'show_in_rest'       => true,
			'show_in_quick_edit' => false,
			'capabilities'       => array(
				'manage_terms' => EventAccess::CAP_MANAGE,
				'edit_terms'   => EventAccess::CAP_MANAGE,
				'delete_terms' => EventAccess::CAP_MANAGE,
				'assign_terms' => 'edit_evt_events',
			),
		);
	}
}








namespace Evt\PublicFront;









final class Assets {






	private static $inline = array();







	public static function set_inline( array $assets ): void {
		self::$inline = $assets;
	}






	public static function register(): void {

		add_action( 'init', array( self::class, 'register_assets' ), 20 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_app' ), 100 );
		add_filter( 'body_class', array( self::class, 'body_class' ) );
	}






	public static function register_assets(): void {
		if ( wp_style_is( 'evt-app', 'registered' ) ) {
			return;
		}


		wp_register_style( 'evt-app', false, array(), null );
		wp_add_inline_style( 'evt-app', self::contents( 'css/evt-app.css' ) );

		wp_register_script( 'evt-app', false, array(), null, true );
		wp_add_inline_script( 'evt-app', self::contents( 'js/evt-app.js' ) );

	}










	public static function enqueue_app(): void {
		if ( Shell::is_app_page() ) {
			self::enqueue();
		}
	}






	public static function enqueue(): void {
		self::register_assets();
		wp_enqueue_style( 'evt-app' );
		wp_enqueue_script( 'evt-app' );
	}

















	public static function has_bootstrap(): bool {





		return (bool) apply_filters( 'evt_has_bootstrap', false );
	}










	public static function body_class( array $classes ): array {
		if ( ! self::has_bootstrap() ) {
			$classes[] = 'evt-sin-bootstrap';
		}
		return $classes;
	}







	public static function alert_class( string $tono = 'info' ): string {
		$tonos = array( 'info', 'warning', 'success', 'danger' );
		$tono  = in_array( $tono, $tonos, true ) ? $tono : 'info';
		return sprintf( 'evt-aviso evt-aviso-%1$s alert alert-%1$s', $tono );
	}











	public static function button_class( bool $primary = false ): string {
		return $primary
			? 'evt-btn evt-btn-primary btn btn-primary'
			: 'evt-btn btn btn-light';
	}










	public static function state_class( string $estado ): string {
		return sprintf(
			'evt-state evt-state-%s',
			sanitize_html_class( '' !== $estado ? $estado : 'na' )
		);
	}












	public static function contents( string $rel ): string {
		if ( isset( self::$inline[ $rel ] ) ) {
			return self::$inline[ $rel ];
		}

		$path = dirname( __DIR__, 3 ) . '/assets/' . $rel;
		if ( ! is_readable( $path ) ) {
			return '';
		}


		return (string) file_get_contents( $path );
	}
}








namespace Evt\PublicFront;

use Evt\Access\EventAccess;
use Evt\Admin\Settings;
use Evt\PostType\EventPostType;
use Evt\Taxonomy\EventTaxonomies;













final class Shell {






	public const SLUGS = array(
		'home'     => 'eventos-gestion',
		'events'   => 'mis-eventos',
		'event'    => 'evento',
		'section'  => 'seccion',
		'speakers' => 'ponentes-evento',
	);











	public const SHORTCODES = array(
		'home'     => 'evt_home',
		'events'   => 'evt_event_list',
		'event'    => 'evt_event_workspace',
		'section'  => 'evt_page_form',
		'speakers' => 'evt_speaker_list',
	);






	private const CORE_ASSETS = array(
		'wp-block-library',
		'wp-block-library-theme',
		'global-styles',
		'classic-theme-styles',
		'wp-emoji-styles',
	);






	public static function register(): void {
		add_filter( 'body_class', array( self::class, 'body_class' ) );
		add_filter( 'show_admin_bar', array( self::class, 'show_admin_bar' ), 100 );


		add_action( 'template_redirect', array( self::class, 'require_login' ) );


		add_action( 'template_redirect', array( self::class, 'render_standalone' ), 20 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'drop_theme_assets' ), 100 );


		add_filter( 'style_loader_tag', array( self::class, 'drop_theme_tag' ), 10, 3 );
		add_filter( 'script_loader_tag', array( self::class, 'drop_theme_tag' ), 10, 3 );
	}












	public static function require_login(): void {
		if ( is_user_logged_in() || ! self::is_app_page() ) {
			return;
		}


		$destino = (string) get_permalink();
		self::leave( wp_login_url( '' !== $destino ? $destino : home_url( '/' ) ) );
	}














	public static function render_standalone(): void {





		if ( ! apply_filters( 'evt_standalone_page', true ) || ! self::is_app_page() ) {
			return;
		}


		$post = get_post();

		status_header( 200 );
		nocache_headers();
		self::send_header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );

		ob_start();
		?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
		<?php


		if ( ! current_theme_supports( 'title-tag' ) ) {
			echo '<title>' . esc_html( wp_get_document_title() ) . '</title>';
		}
		wp_head();
		?>
</head>
<body <?php body_class(); ?>>
		<?php
		wp_body_open();
		echo apply_filters( 'the_content', $post->post_content ); 
		wp_footer();
		?>
</body>
</html>
		<?php
		echo ob_get_clean(); 
		self::leave();
	}









	public static function drop_theme_tag( string $tag, string $handle, string $src ): string {
		unset( $handle );
		if ( ! apply_filters( 'evt_standalone_page', true ) || ! self::is_app_page() ) {
			return $tag;
		}
		return self::is_theme_asset( $src ) ? '' : $tag;
	}






	public static function drop_theme_assets(): void {
		if ( ! apply_filters( 'evt_standalone_page', true ) || ! self::is_app_page() ) {
			return;
		}

		foreach ( array( wp_styles(), wp_scripts() ) as $cola ) {
			foreach ( (array) $cola->queue as $handle ) {
				$src = isset( $cola->registered[ $handle ] ) ? (string) $cola->registered[ $handle ]->src : '';
				if ( '' !== $src && self::is_theme_asset( $src ) ) {
					$cola->dequeue( $handle );
				}
			}
		}




		foreach ( self::CORE_ASSETS as $handle ) {
			wp_dequeue_style( $handle );
		}


		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
	}










	private static function is_theme_asset( string $src ): bool {
		return '' !== $src
			&& ( false !== strpos( $src, '/themes/' ) || false !== strpos( $src, '/et-cache/' ) );
	}




















	public static function show_admin_bar(): bool {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		if ( ! is_user_logged_in() || ! class_exists( '\WPFront\URE\WPFront_User_Role_Editor_Utils' ) ) {
			return false;
		}

		$clave = 'wpfront_ure_user_switching_stack_' . COOKIEHASH;
		if ( ! isset( $_COOKIE[ $clave ] ) || ! is_string( $_COOKIE[ $clave ] ) ) {
			return false;
		}
		$cookie = sanitize_text_field( wp_unslash( $_COOKIE[ $clave ] ) );


		$sesion = explode( '-', (string) \WPFront\URE\WPFront_User_Role_Editor_Utils::decrypt( $cookie ), 4 );
		if ( count( $sesion ) < 3 || COOKIEHASH !== $sesion[0] || ! ctype_digit( $sesion[1] ) ) {
			return false;
		}
		$edad = time() - (int) $sesion[1];
		if ( $edad < 0 || $edad > 12 * HOUR_IN_SECONDS ) {
			return false;
		}

		$usuarios = explode( ',', $sesion[2] );
		return ctype_digit( $usuarios[0] ) && user_can( (int) $usuarios[0], 'manage_options' );
	}







	public static function body_class( array $classes ): array {
		if ( self::is_app_page() ) {
			$classes[] = 'evt-app';
		}
		return $classes;
	}






	public static function is_app_page(): bool {
		return '' !== self::current_section();
	}






	public static function current_section(): string {
		if ( is_admin() || ! is_singular() ) {
			return '';
		}
		$post = get_post();
		if ( ! $post instanceof \WP_Post ) {
			return '';
		}
		foreach ( self::SHORTCODES as $seccion => $codigo ) {
			if ( has_shortcode( (string) $post->post_content, $codigo ) ) {
				return $seccion;
			}
		}
		return '';
	}








	public static function url( string $section, array $args = array() ): string {
		$slug = self::SLUGS[ $section ] ?? '';
		if ( '' === $slug ) {
			return '';
		}











		$slug = (string) apply_filters( 'evt_page_slug', $slug, $section );

		$page = get_page_by_path( $slug );
		if ( ! $page ) {
			return '';
		}
		$url = (string) get_permalink( $page );
		return array() === $args ? $url : add_query_arg( $args, $url );
	}
















	public static function sections( int $user_id = 0 ): array {






		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		if ( $user_id <= 0 ) {
			return array();
		}

		$out = array();

		if ( self::can_use( $user_id ) ) {
			$out['events'] = array(
				'label' => 'Eventos',
				'url'   => self::url( 'events' ),
				'badge' => null,
			);
		}



		foreach ( $out as $clave => $seccion ) {
			if ( '' === $seccion['url'] ) {
				unset( $out[ $clave ] );
			}
		}

		return $out;
	}










	public static function can_use( int $user_id = 0 ): bool {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		if ( $user_id <= 0 ) {
			return false;
		}
		if ( EventAccess::can_edit_all_areas( $user_id ) ) {
			return true;
		}
		return user_can( $user_id, 'edit_evt_events' ) && array() !== EventAccess::user_areas( $user_id );
	}











	public static function profile( int $user_id = 0 ): array {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		if ( $user_id <= 0 ) {
			return array(
				'cargo' => '',
				'area'  => '',
			);
		}

		if ( EventAccess::is_manager( $user_id ) ) {
			return array(
				'cargo' => 'Administración',
				'area'  => 'Todas las áreas',
			);
		}
		if ( user_can( $user_id, 'edit_evt_events' ) ) {
			return array(
				'cargo' => 'Organización de eventos',
				'area'  => self::area_names( $user_id ),
			);
		}
		return array(
			'cargo' => '',
			'area'  => '',
		);
	}







	private static function area_names( int $user_id ): string {
		$nombres = array();
		foreach ( EventAccess::user_areas( $user_id ) as $term_id ) {
			$term = get_term( $term_id, EventTaxonomies::AREA );
			if ( $term instanceof \WP_Term ) {
				$nombres[] = $term->name;
			}
		}
		return array() === $nombres ? 'Sin área asignada' : implode( ' · ', $nombres );
	}











	private static function initials( string $nombre ): string {
		$partes = preg_split( '/[^\p{L}\p{N}]+/u', trim( $nombre ), -1, PREG_SPLIT_NO_EMPTY );
		$partes = is_array( $partes ) ? $partes : array();
		$letras = '';
		foreach ( array_slice( $partes, 0, 2 ) as $parte ) {
			$letras .= mb_strtoupper( mb_substr( $parte, 0, 1 ) );
		}
		return $letras;
	}









	public static function render( string $title, string $subtitle, string $body ): string {





		if ( ! apply_filters( 'evt_show_chrome', true ) ) {
			return '<div class="evt-hoja">' . $body . '</div>';
		}

		ob_start();
		?>
		<div class="evt-hoja">
			<?php if ( '' !== $title ) : ?>
				<h1 class="evt-h1"><?php echo esc_html( $title ); ?></h1>
			<?php endif; ?>
			<?php if ( '' !== $subtitle ) : ?>
				<p class="evt-sub"><?php echo esc_html( $subtitle ); ?></p>
			<?php endif; ?>
			<?php echo $body; ?>
		</div>
		<?php
		return self::top() . self::tabs() . (string) ob_get_clean() . self::bottom();
	}










	private static function top(): string {
		$perfil  = self::profile();
		$usuario = wp_get_current_user();
		$inicio  = self::home_url();
		$chrome  = \Evt\PublicFront\View\EventChrome::chrome();
		$duenio  = (string) $chrome['owner'];
		$rotulo  = (string) $chrome['org'];

		ob_start();
		?>
		<div class="evt-top">
			<div class="evt-top-fila">
				<?php if ( '' !== $duenio ) : ?>
					<span class="evt-logo" role="img" aria-label="<?php echo esc_attr( $duenio ); ?>"></span>
				<?php endif; ?>
				<?php if ( '' !== $rotulo ) : ?>
					<span class="evt-marca">
						<small><?php echo esc_html( $rotulo ); ?></small>
					</span>
				<?php endif; ?>
				<a class="evt-marca-app" href="<?php echo esc_url( $inicio ); ?>">Eventos</a>
				<?php if ( '' !== $perfil['cargo'] ) : ?>
					<details class="evt-yo">
						<summary>
							<span class="evt-yo-ava"><?php echo esc_html( self::initials( $usuario->display_name ) ); ?></span>
							<span class="evt-yo-txt">
								<span class="evt-yo-n"><?php echo esc_html( $usuario->display_name ); ?> <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg></span>
								<span class="evt-yo-r"><?php echo esc_html( $perfil['cargo'] ); ?></span>
								<span class="evt-yo-r evt-yo-a"><?php echo esc_html( $perfil['area'] ); ?></span>
							</span>
						</summary>
						<div class="evt-yo-menu">
							<?php if ( EventAccess::is_manager() ) : ?>
								<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . EventPostType::POST_TYPE . '&page=' . Settings::PAGE ) ); ?>">Ajustes del aplicativo</a>
							<?php endif; ?>
							<a href="<?php echo esc_url( wp_logout_url( $inicio ) ); ?>">Salir</a>
						</div>
					</details>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}






	private static function tabs(): string {
		$secciones = self::sections();
		if ( count( $secciones ) < 2 ) {
			return '';
		}
		$activa = self::current_section();

		ob_start();
		?>
		<nav class="evt-tabs" aria-label="Secciones">
			<div class="evt-tabs-fila">
				<?php foreach ( $secciones as $clave => $s ) : ?>
					<a class="evt-tab<?php echo $clave === $activa ? ' evt-tab-on' : ''; ?>"
						<?php echo $clave === $activa ? ' aria-current="page"' : ''; ?>
						href="<?php echo esc_url( $s['url'] ); ?>"><?php echo esc_html( $s['label'] ); ?>
						<?php if ( null !== $s['badge'] && $s['badge'] > 0 ) : ?>
							<span class="evt-tab-n"><?php echo esc_html( (string) $s['badge'] ); ?></span>
						<?php endif; ?></a>
				<?php endforeach; ?>
			</div>
		</nav>
		<?php
		return (string) ob_get_clean();
	}






	private static function bottom(): string {








		$chrome  = \Evt\PublicFront\View\EventChrome::chrome();
		$enlaces = array();
		foreach ( (array) $chrome['footer_links'] as $enlace ) {
			if ( isset( $enlace['label'], $enlace['url'] ) ) {
				$enlaces[ (string) $enlace['label'] ] = (string) $enlace['url'];
			}
		}
		$enlaces = (array) apply_filters( 'evt_footer_links', $enlaces );
		$duenio  = (string) $chrome['owner'];
		$hecho   = (string) $chrome['credit'];

		ob_start();
		?>
		<div class="evt-pie"><div>
			<span class="evt-pie-quien">
				<?php if ( '' !== $duenio ) : ?>
					<a href="<?php echo esc_url( self::home_url() ); ?>">&copy; <?php echo esc_html( $duenio ); ?></a>
				<?php endif; ?>
				<?php if ( '' !== $hecho ) : ?>
					<span class="evt-pie-ate"><?php echo esc_html( $hecho ); ?></span>
				<?php endif; ?>
			</span>
			<span class="evt-pie-enlaces">
				<?php foreach ( $enlaces as $rotulo => $url ) : ?>
					<a href="<?php echo esc_url( (string) $url ); ?>" rel="noopener"><?php echo esc_html( (string) $rotulo ); ?></a>
				<?php endforeach; ?>
			</span>
		</div></div>
		<?php
		return (string) ob_get_clean();
	}






	private static function home_url(): string {
		$inicio = self::url( 'home' );
		return '' !== $inicio ? $inicio : home_url( '/' );
	}








	public static function notice( string $type, string $text ): string {
		if ( '' === $text ) {
			return '';
		}
		$tonos = array(
			'ok'    => 'success',
			'aviso' => 'warning',
			'error' => 'danger',
		);
		$tono  = $tonos[ $type ] ?? 'info';
		return '<p class="' . esc_attr( Assets::alert_class( $tono ) ) . '">' . esc_html( $text ) . '</p>';
	}




	public const ADMIN_BOX_WHY = 'Este recuadro solo lo ve quien administra el aplicativo. Ningún otro perfil lo ve ni puede cambiar lo que hay dentro.';




















	public static function admin_box( string $titulo, string $cuerpo, string $explicacion = '' ): string {
		$porque = '' !== $explicacion ? $explicacion : self::ADMIN_BOX_WHY;

		ob_start();
		?>
		<section class="evt-solo-admin">
			<p class="evt-solo-admin-marca">
				<svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 1 3 5v6c0 5 3.8 9.7 9 11 5.2-1.3 9-6 9-11V5l-9-4Zm0 6a2 2 0 0 1 2 2v1h.5a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-.5.5h-5a.5.5 0 0 1-.5-.5v-4a.5.5 0 0 1 .5-.5H10V9a2 2 0 0 1 2-2Zm0 1.2A.8.8 0 0 0 11.2 9v1h1.6V9a.8.8 0 0 0-.8-.8Z"/></svg>
				Solo administración
			</p>
			<?php if ( '' !== $titulo ) : ?>
				<h3 class="evt-solo-admin-titulo"><?php echo esc_html( $titulo ); ?></h3>
			<?php endif; ?>
			<p class="evt-solo-admin-porque"><?php echo esc_html( $porque ); ?></p>
			<div class="evt-solo-admin-cuerpo">
				<?php echo $cuerpo; ?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}












	public static function back_url( string $section = 'events' ): string {
		$destino = wp_validate_redirect( (string) wp_get_raw_referer(), '' );
		if ( '' !== $destino ) {
			return $destino;
		}
		$url = self::url( $section );
		return '' !== $url ? $url : home_url( '/' );
	}











	public static function send_header( string $linea ): void {
		if ( ! headers_sent() ) {
			header( $linea );
		}
	}












	public static function leave( string $url = '' ): void {
		if ( apply_filters( 'evt_exit_throws', false, $url ) ) {
			throw new ExitSignal( $url ); 
		}
		if ( '' !== $url ) {
			wp_safe_redirect( $url );
		}
		exit;
	}















	public static function icon( string $nombre ): string {
		$caminos = array(

			'lapiz'     => '<path fill="currentColor" d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8.4 17.6l-3.9.9.9-3.9L16.5 3.5Z"/>',

			'ojo'       => '<path fill="currentColor" d="M12 5c-5 0-9 4.5-9 7s4 7 9 7 9-4.5 9-7-4-7-9-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Zm0-2a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/>',

			'papelera'  => '<path fill="currentColor" d="M9 3h6l1 2h4v2H4V5h4l1-2Zm-3 6h12l-1 11a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L6 9Zm4 2v9h1.5v-9H10Zm3.5 0v9H15v-9h-1.5Z"/>',

			'subir'     => '<path fill="currentColor" d="M12 4.5 18.5 11H14v8.5h-4V11H5.5L12 4.5Z"/>',
			'bajar'     => '<path fill="currentColor" d="M12 19.5 5.5 13H10V4.5h4V13h4.5L12 19.5Z"/>',

			'restaurar' => '<path fill="currentColor" d="M12 5a7 7 0 1 1-6.7 9h2.2A4.8 4.8 0 1 0 12 7.2V10L7.5 6 12 2v3Z"/>',
		);
		if ( ! isset( $caminos[ $nombre ] ) ) {
			return '';
		}
		return '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">'
			. $caminos[ $nombre ] . '</svg>';
	}






	public static function icon_plus(): string {
		return '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">'
			. '<path fill="currentColor" d="M12 4a1 1 0 0 1 1 1v6h6a1 1 0 1 1 0 2h-6v6a1 1 0 1 1-2 0v-6H5a1 1 0 1 1 0-2h6V5a1 1 0 0 1 1-1Z"/>'
			. '</svg>';
	}
}








namespace Evt\PublicFront;

use Evt\Access\EventAccess;



























final class EditLock {




	public const FIELD_DO = 'evt_lock_do';




	public const FIELD_POST = 'evt_lock_post';




	public const NONCE_FIELD = 'evt_lock_nonce';




	public const OP_TAKEOVER = 'takeover';







	public static function nonce_action( int $event_id ): string {
		return 'evt_lock_takeover_' . $event_id;
	}










	public static function owner( int $post_id ): int {
		$event_id = EventAccess::root_id( $post_id );
		if ( $event_id <= 0 ) {
			return 0;
		}
		require_once ABSPATH . 'wp-admin/includes/post.php';
		return (int) wp_check_post_lock( $event_id );
	}











	public static function require_available( int $post_id ): void {
		$owner = self::owner( $post_id );
		if ( $owner <= 0 ) {
			return;
		}
		wp_die(
			esc_html(
				sprintf(
					'%s está editando este evento ahora mismo, aquí o en el escritorio de WordPress. Vuelva atrás y tome posesión antes de guardar: así no se pisa lo que la otra persona esté escribiendo.',
					self::name( $owner )
				)
			),
			'Edición bloqueada',
			array(
				'response'  => 409,
				'back_link' => true,
			)
		);
	}

















	public static function status( int $post_id, bool $can_edit ): array {
		$event_id = EventAccess::root_id( $post_id );
		if ( $event_id <= 0 || ! $can_edit ) {
			return self::none();
		}
		$owner = self::owner( $event_id );

		return array(
			'event_id' => $event_id,
			'owner'    => $owner,
			'name'     => $owner > 0 ? self::name( $owner ) : '',


			'lock'     => '',
		);
	}










	public static function claim( array $lock ): array {
		if ( (int) $lock['event_id'] <= 0 || (int) $lock['owner'] > 0 ) {
			return $lock;
		}
		require_once ABSPATH . 'wp-admin/includes/post.php';
		$puesto       = wp_set_post_lock( (int) $lock['event_id'] );
		$lock['lock'] = is_array( $puesto ) ? implode( ':', $puesto ) : '';

		return $lock;
	}






	public static function none(): array {
		return array(
			'event_id' => 0,
			'owner'    => 0,
			'name'     => '',
			'lock'     => '',
		);
	}












	public static function release( int $post_id ): void {
		$event_id = EventAccess::root_id( $post_id );
		if ( $event_id <= 0 ) {
			return;
		}
		$lock  = (string) get_post_meta( $event_id, '_edit_lock', true );
		$trozo = explode( ':', $lock );
		if ( isset( $trozo[1] ) && get_current_user_id() === (int) $trozo[1] ) {
			delete_post_meta( $event_id, '_edit_lock', $lock );
		}
	}














	public static function handle(): void {

		$op = sanitize_key( wp_unslash( (string) ( $_POST[ self::FIELD_DO ] ?? '' ) ) );
		if ( self::OP_TAKEOVER !== $op ) {
			return;
		}
		$post_id = absint( wp_unslash( $_POST[ self::FIELD_POST ] ?? 0 ) );
		$nonce   = sanitize_text_field( wp_unslash( (string) ( $_POST[ self::NONCE_FIELD ] ?? '' ) ) );


		$event_id = EventAccess::root_id( $post_id );
		if ( $event_id <= 0 || false === wp_verify_nonce( $nonce, self::nonce_action( $event_id ) ) ) {
			return;
		}

		if ( ! EventAccess::can_edit( get_current_user_id(), $event_id ) ) {
			wp_die(
				esc_html( EventAccess::why_not_editable( get_current_user_id(), $event_id ) ),
				'Sin permiso',
				array(
					'response'  => 403,
					'back_link' => true,
				)
			);
		}

		require_once ABSPATH . 'wp-admin/includes/post.php';
		wp_set_post_lock( $event_id );




		Shell::leave( Shell::back_url( 'events' ) );
	}
















	public static function render( array $lock ): string {
		$event_id = (int) $lock['event_id'];
		if ( $event_id <= 0 ) {
			return '';
		}
		$owner = (int) $lock['owner'];



		wp_enqueue_script( 'heartbeat' );

		ob_start();
		?>
		<div id="evt-edit-lock"
			data-post-id="<?php echo esc_attr( (string) $event_id ); ?>"
			data-lock="<?php echo esc_attr( (string) $lock['lock'] ); ?>"
			data-release-nonce="<?php echo esc_attr( wp_create_nonce( 'update-post_' . $event_id ) ); ?>"
			data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
			<dialog id="evt-lock-dialog" class="evt-dialogo evt-dialogo-bloqueo" aria-labelledby="evt-lock-title"<?php echo $owner > 0 ? ' open' : ''; ?>>
				<h2 id="evt-lock-title">Lo está editando otra persona</h2>
				<p>
					<strong id="evt-lock-owner"><?php echo esc_html( $owner > 0 ? (string) $lock['name'] : 'Otra persona' ); ?></strong>
					tiene abierto este evento ahora mismo, aquí o en el escritorio de WordPress.
				</p>
				<p>
					El bloqueo es del evento entero y no de una de sus páginas: las secciones, los
					datos y la apariencia se tocan a la vez, así que dos personas al mismo tiempo se
					pisarían.
				</p>
				<p>
					Puede esperar a que termine y consultarlo mientras tanto, o tomar posesión y
					editarlo usted: la otra persona dejará de poder guardar y verá este mismo aviso.
				</p>
				<form class="evt-accion" method="post" action="">
					<?php wp_nonce_field( self::nonce_action( $event_id ), self::NONCE_FIELD ); ?>
					<input type="hidden" name="<?php echo esc_attr( self::FIELD_DO ); ?>" value="<?php echo esc_attr( self::OP_TAKEOVER ); ?>" />
					<input type="hidden" name="<?php echo esc_attr( self::FIELD_POST ); ?>" value="<?php echo esc_attr( (string) $event_id ); ?>" />
					<a class="<?php echo esc_attr( Assets::button_class() ); ?>" href="<?php echo esc_url( self::events_url() ); ?>">Dejarlo y volver a mis eventos</a>
					<button type="submit" class="<?php echo esc_attr( Assets::button_class( true ) ); ?>">Tomar posesión</button>
				</form>
			</dialog>
		</div>
		<?php
		return (string) ob_get_clean();
	}






	private static function events_url(): string {
		$url = Shell::url( 'events' );
		return '' !== $url ? $url : home_url( '/' );
	}







	private static function name( int $user_id ): string {
		$user = get_userdata( $user_id );
		if ( false === $user || '' === (string) $user->display_name ) {
			return 'Otra persona';
		}
		return (string) $user->display_name;
	}
}








namespace Evt\PublicFront;



















final class CodeEditor {




	public const MODE_CSS = 'css';




	public const MODE_JS = 'javascript';






	private const MIMES = array(
		self::MODE_CSS => 'text/css',
		self::MODE_JS  => 'text/javascript',
	);











	public static function enqueue( string $modo ): bool {
		return array() !== self::settings( $modo );
	}

















	public static function field( array $args ): string {
		$modo   = isset( self::MIMES[ (string) ( $args['mode'] ?? '' ) ] ) ? (string) $args['mode'] : self::MODE_CSS;
		$nombre = (string) ( $args['name'] ?? '' );
		if ( '' === $nombre ) {
			return '';
		}

		$id     = (string) ( $args['id'] ?? '' );
		$id     = '' !== $id ? $id : 'evt-code-' . str_replace( '_', '-', $nombre );
		$rotulo = (string) ( $args['label'] ?? '' );
		$ayuda  = (string) ( $args['help'] ?? '' );
		$valor  = (string) ( $args['value'] ?? '' );
		$filas  = max( 4, (int) ( $args['rows'] ?? 12 ) );



		$ajustes  = self::settings( $modo );
		$ayuda_id = $id . '-ayuda';

		ob_start();
		?>
		<div class="evt-code evt-code-<?php echo esc_attr( $modo ); ?>">
			<?php if ( '' !== $rotulo ) : ?>
				<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $rotulo ); ?></label>
			<?php endif; ?>
			<textarea
				id="<?php echo esc_attr( $id ); ?>"
				name="<?php echo esc_attr( $nombre ); ?>"
				class="evt-code-area"
				rows="<?php echo esc_attr( (string) $filas ); ?>"
				spellcheck="false"
				autocapitalize="off"
				autocomplete="off"
				autocorrect="off"
				<?php if ( '' !== $ayuda ) : ?>
					aria-describedby="<?php echo esc_attr( $ayuda_id ); ?>"
				<?php endif; ?>
				<?php if ( array() !== $ajustes ) : ?>
					data-evt-code="<?php echo esc_attr( $modo ); ?>"
					data-evt-code-settings="<?php echo esc_attr( (string) wp_json_encode( $ajustes ) ); ?>"
				<?php endif; ?>
			><?php echo esc_textarea( $valor ); ?></textarea>
			<?php if ( '' !== $ayuda ) : ?>
				<small id="<?php echo esc_attr( $ayuda_id ); ?>"><?php echo esc_html( $ayuda ); ?></small>
			<?php endif; ?>
			<?php if ( array() === $ajustes ) : ?>
				<small class="evt-code-plano">
					El resaltado de código está desactivado en su perfil, así que este campo es un cuadro de texto normal. Se guarda igual.
				</small>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}







	private static function settings( string $modo ): array {
		if ( ! isset( self::MIMES[ $modo ] ) ) {
			return array();
		}
		$ajustes = wp_enqueue_code_editor( array( 'type' => self::MIMES[ $modo ] ) );
		return is_array( $ajustes ) ? $ajustes : array();
	}
}








namespace Evt\PublicFront;

use Evt\Access\EventAccess;
use Evt\Meta\EventMetaKeys;
use Evt\Meta\EventMetaRegistration;
use Evt\PostType\EventPostType;








































final class CustomCode {




	public const CSS_PRIORITY = 999;




	public const JS_PRIORITY = 999;






	public static function register(): void {
		add_action( 'wp_head', array( self::class, 'print_css' ), self::CSS_PRIORITY );
		add_action( 'wp_footer', array( self::class, 'print_js' ), self::JS_PRIORITY );
	}






	public static function print_css(): void {
		$css = self::css( self::current_page_id() );
		if ( '' === $css ) {
			return;
		}

		printf( "<style id=\"evt-custom-css\">\n%s\n</style>\n", $css );
	}






	public static function print_js(): void {
		$js = self::js( self::current_page_id() );
		if ( '' === $js ) {
			return;
		}

		printf( "<script id=\"evt-custom-js\">\n%s\n</script>\n", $js );
	}







	public static function css( int $page_id ): string {
		return self::code(
			$page_id,
			EventMetaKeys::CUSTOM_CSS,
			array( EventMetaRegistration::class, 'sanitize_custom_css' )
		);
	}







	public static function js( int $page_id ): string {
		return self::code(
			$page_id,
			EventMetaKeys::CUSTOM_JS,
			array( EventMetaRegistration::class, 'sanitize_custom_js' )
		);
	}









	private static function code( int $page_id, string $key, callable $limpia ): string {
		$trozos = array();
		foreach ( self::chain( $page_id ) as $post_id ) {
			$trozo = trim( (string) call_user_func( $limpia, get_post_meta( $post_id, $key, true ) ) );
			if ( '' !== $trozo ) {
				$trozos[] = $trozo;
			}
		}
		return implode( "\n", $trozos );
	}











	private static function chain( int $page_id ): array {
		if ( $page_id <= 0 ) {
			return array();
		}
		$event_id = EventAccess::root_id( $page_id );
		return $event_id === $page_id ? array( $page_id ) : array( $event_id, $page_id );
	}











	private static function current_page_id(): int {
		if ( is_admin() || ! is_singular( EventPostType::POST_TYPE ) ) {
			return 0;
		}
		return (int) get_queried_object_id();
	}
}








namespace Evt\PublicFront;

use Evt\Access\EventAccess;
use Evt\Domain\EventState;
use Evt\Meta\EventMetaKeys;
use Evt\PostType\EventPostType;
use Evt\PublicFront\View\EventListView;
use Evt\Taxonomy\EventTaxonomies;
















final class EventList {

	public const SHORTCODE = 'evt_event_list';




	public const PAGE_SIZE = 20;








	public const FILTER_DRAFT = 'draft';









	public const FILTER_TRASH = 'trash';









	public const FILTER_ARCHIVED = 'archived';










	public const VAR_AREA = 'evt_filter_area';




	public const VAR_TYPE = 'evt_filter_type';




	public const VAR_COURSE = 'evt_filter_course';




	public const VAR_STATE = 'evt_filter_state';




	public const VAR_SEARCH = 'evt_search';




	public const VAR_PAGE = 'evt_page';




	public const VAR_NOTICE = 'evt_notice';






	private const STATUSES = array( 'publish', 'future', 'draft', 'pending', 'private' );




	public const FIELD_DO = 'evt_list_do';




	public const FIELD_EVENT = 'evt_list_event';




	public const NONCE_ACTION = 'evt_list_restore';






	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'render' ) );


		add_action( 'init', array( self::class, 'handle' ), 20 );
	}










	public static function nonce_name( int $event_id ): string {
		return 'evt_list_nonce_' . $event_id;
	}















	public static function handle(): void {

		$op = sanitize_key( wp_unslash( (string) ( $_POST[ self::FIELD_DO ] ?? '' ) ) );
		if ( ! in_array( $op, array( 'restore', 'publish', 'unpublish' ), true ) ) {
			return;
		}

		$event_id = absint( wp_unslash( $_POST[ self::FIELD_EVENT ] ?? 0 ) );
		$campo    = self::nonce_name( $event_id );

		$nonce = sanitize_text_field( wp_unslash( (string) ( $_POST[ $campo ] ?? '' ) ) );
		if ( false === wp_verify_nonce( $nonce, self::nonce_action( $op ) ) ) {
			return;
		}

		if ( 'restore' !== $op ) {
			self::switch_status( $op, $event_id );
			return;
		}

		if ( ! self::may_restore( get_current_user_id(), $event_id ) ) {
			Shell::leave( self::back_to( self::FILTER_TRASH, 'permiso' ) );
			return;
		}

		wp_untrash_post( $event_id );
		Shell::leave( self::back_to( 'all', 'restaurado' ) );
	}












	private static function switch_status( string $op, int $event_id ): void {
		$user_id  = get_current_user_id();
		$publicar = 'publish' === $op;

		if ( EventPostType::POST_TYPE !== get_post_type( $event_id )
			|| 0 !== (int) get_post_field( 'post_parent', $event_id )
			|| 'trash' === get_post_status( $event_id )
			|| ! EventAccess::can_publish( $user_id, $event_id )
			|| ! EventAccess::can_edit( $user_id, $event_id ) ) {
			Shell::leave( self::back_to( 'all', 'permiso' ) );
			return;
		}

		wp_update_post(
			array(
				'ID'          => $event_id,
				'post_status' => $publicar ? 'publish' : 'draft',
			)
		);
		Shell::leave( self::back_to( 'all', $publicar ? 'publicado' : 'despublicado' ) );
	}









	public static function nonce_action( string $op = 'restore' ): string {
		return 'restore' === $op ? self::NONCE_ACTION : 'evt_list_' . $op;
	}












	private static function may_restore( int $user_id, int $post_id ): bool {
		return 'trash' === get_post_status( $post_id )
			&& EventPostType::POST_TYPE === get_post_type( $post_id )
			&& 0 === (int) get_post_field( 'post_parent', $post_id )
			&& EventAccess::can_edit( $user_id, $post_id );
	}








	private static function back_to( string $state, string $notice ): string {
		$url = self::url( self::no_filters(), array( 'state' => $state ) );
		if ( '' === $url ) {
			$url = Shell::back_url();
		}
		return add_query_arg( self::VAR_NOTICE, $notice, $url );
	}






	public static function state_filters(): array {
		return array(
			'all'                         => 'Todos',
			EventMetaKeys::STATE_UPCOMING => 'Próximos',
			EventMetaKeys::STATE_OPEN     => 'Abiertos',
			EventMetaKeys::STATE_FINISHED => 'Finalizados',
			self::FILTER_ARCHIVED         => 'Históricos',
			self::FILTER_DRAFT            => 'En borrador',
			self::FILTER_TRASH            => 'Papelera',
		);
	}






	public static function status_labels(): array {
		return array(
			'publish' => 'Publicado',
			'future'  => 'Programado',
			'draft'   => 'Borrador',
			'pending' => 'Pendiente de revisión',
			'private' => 'Privado',
			'trash'   => 'En la papelera',
		);
	}








	public static function input( string $key, string $fallback = '' ): string {

		return isset( $_GET[ $key ] ) && is_string( $_GET[ $key ] )
			? sanitize_text_field( wp_unslash( $_GET[ $key ] ) )
			: $fallback;

	}






	public static function selection(): array {
		$estado = self::input( self::VAR_STATE, 'all' );

		return array(
			'area'   => max( 0, (int) self::input( self::VAR_AREA ) ),
			'type'   => max( 0, (int) self::input( self::VAR_TYPE ) ),
			'course' => max( 0, (int) self::input( self::VAR_COURSE ) ),
			'state'  => isset( self::state_filters()[ $estado ] ) ? $estado : 'all',
			'search' => mb_substr( self::input( self::VAR_SEARCH ), 0, 120 ),
			'page'   => max( 1, (int) self::input( self::VAR_PAGE, '1' ) ),
		);
	}








	public static function url( array $selection, array $changes = array() ): string {


		$s = array_merge( $selection, array( 'page' => 1 ), $changes );

		$args = array(
			self::VAR_AREA   => $s['area'] > 0 ? (string) $s['area'] : '',
			self::VAR_TYPE   => $s['type'] > 0 ? (string) $s['type'] : '',
			self::VAR_COURSE => $s['course'] > 0 ? (string) $s['course'] : '',
			self::VAR_STATE  => 'all' !== $s['state'] ? (string) $s['state'] : '',
			self::VAR_SEARCH => (string) $s['search'],
			self::VAR_PAGE   => $s['page'] > 1 ? (string) $s['page'] : '',
		);

		return Shell::url(
			'events',
			array_filter(
				$args,
				static function ( string $valor ): bool {
					return '' !== $valor;
				}
			)
		);
	}






	public static function model(): array {
		$m           = self::blank();
		$m['reason'] = self::why_nothing();

		if ( '' !== $m['reason'] ) {
			return $m;
		}

		$user_id = get_current_user_id();
		$todas   = EventAccess::can_edit_all_areas( $user_id );
		$m       = array_merge( $m, self::chrome( $user_id, $todas ) );

		$rows         = self::collect( $user_id );
		$m['options'] = self::options( $rows );

		$s        = self::sanitise( self::selection(), $m['options'] );
		$rows     = self::narrow( $rows, $s );
		$papelera = self::by_state( $rows, self::FILTER_TRASH );
		$vivos    = self::by_state( $rows, 'vivos' );



		$m['counts']                       = self::count_states( $vivos );
		$m['counts'][ self::FILTER_TRASH ] = count( $papelera );

		$en_papelera = self::FILTER_TRASH === (string) $s['state'];
		$rows        = $en_papelera ? $papelera : self::by_state( $vivos, (string) $s['state'] );

		usort( $rows, array( self::class, 'compare' ) );

		$m['total'] = count( $rows );
		$m['pages'] = max( 1, (int) ceil( $m['total'] / self::PAGE_SIZE ) );
		$m['page']  = min( $m['pages'], (int) $s['page'] );
		$s['page']  = $m['page'];

		$m['rows']      = self::with_sections(
			array_slice( $rows, ( $m['page'] - 1 ) * self::PAGE_SIZE, self::PAGE_SIZE )
		);
		$m['selection'] = $s;

		if ( array() === $m['rows'] ) {
			$filtrando       = self::is_filtered( $s );
			$m['empty_text'] = $en_papelera
				? 'La papelera está vacía: no hay ningún evento esperando a que lo restauren.'
				: self::empty_text( $filtrando, $todas );
			$m['reset_url']  = $filtrando ? self::url( $s, self::no_filters() ) : '';
		}

		return $m;
	}






	private static function blank(): array {
		return array(
			'can_use'     => false,
			'reason'      => '',
			'notice'      => array(
				'type' => '',
				'text' => '',
			),
			'subtitle'    => '',
			'selection'   => self::selection(),
			'options'     => array(
				'area'   => array(),
				'type'   => array(),
				'course' => array(),
			),
			'area_filter' => false,
			'scoped'      => false,
			'counts'      => array_fill_keys( array_keys( self::state_filters() ), 0 ),
			'total'       => 0,
			'page'        => 1,
			'pages'       => 1,
			'rows'        => array(),
			'can_create'  => false,
			'create_url'  => '',
			'empty_text'  => '',
			'reset_url'   => '',
			'page_id'     => 0,
		);
	}






	private static function why_nothing(): string {
		if ( ! is_user_logged_in() ) {
			return 'Debe iniciar sesión con su usuario para gestionar eventos.';
		}

		$user_id = get_current_user_id();
		if ( Shell::can_use( $user_id ) ) {
			return '';
		}



		return user_can( $user_id, 'edit_evt_events' ) || EventAccess::is_manager( $user_id )
			? 'No tiene ningún área asignada en su perfil, así que todavía no puede gestionar eventos. El área la pone quien administra el aplicativo.'
			: 'Su usuario todavía no organiza eventos. Pídalo a quien administre el aplicativo.';
	}








	private static function chrome( int $user_id, bool $all_areas ): array {
		$crear = user_can( $user_id, 'edit_evt_events' ) ? Shell::url( 'event' ) : '';

		return array(
			'can_use'     => true,
			'notice'      => self::flash(),
			'page_id'     => self::hidden_page_id(),
			'scoped'      => ! $all_areas,

			'area_filter' => $all_areas || count( EventAccess::user_areas( $user_id ) ) > 1,
			'subtitle'    => $all_areas
				? 'Todos los eventos, de todas las áreas.'
				: 'Solo los eventos de su área: los que organizan otras áreas no salen aquí.',
			'can_create'  => '' !== $crear,
			'create_url'  => $crear,
		);
	}







	private static function collect( int $user_id ): array {
		$rows = array();
		foreach ( self::scope( $user_id ) as $post ) {





			if ( EventAccess::can_open( $user_id, (int) $post->ID ) ) {
				$rows[] = self::row( $post );
			}
		}
		return $rows;
	}











	private static function sanitise( array $s, array $options ): array {
		foreach ( array_keys( $options ) as $eje ) {
			if ( $s[ $eje ] > 0 && ! isset( $options[ $eje ][ $s[ $eje ] ] ) ) {
				$s[ $eje ] = 0;
			}
		}
		return $s;
	}








	private static function narrow( array $rows, array $s ): array {
		return array_values(
			array_filter(
				$rows,
				static function ( array $row ) use ( $s ): bool {
					return self::in_scope( $row, $s );
				}
			)
		);
	}










	private static function count_states( array $rows ): array {
		$counts = array_fill_keys( array_keys( self::state_filters() ), 0 );

		foreach ( $rows as $row ) {
			foreach ( array_keys( $counts ) as $filtro ) {
				if ( self::matches( $row, $filtro ) ) {
					++$counts[ $filtro ];
				}
			}
		}

		return $counts;
	}








	private static function by_state( array $rows, string $state ): array {
		if ( 'all' === $state ) {
			return $rows;
		}

		return array_values(
			array_filter(
				$rows,
				static function ( array $row ) use ( $state ): bool {
					return self::matches( $row, $state );
				}
			)
		);
	}










	private static function with_sections( array $rows ): array {
		$secciones = self::section_counts( array_map( 'intval', array_column( $rows, 'id' ) ) );

		foreach ( $rows as $i => $row ) {
			$rows[ $i ]['sections'] = $secciones[ $row['id'] ] ?? 0;
		}

		return $rows;
	}







	public static function html( array $model ): string {
		return EventListView::html( $model );
	}







	public static function render( $atts = array() ): string {
		unset( $atts );
		Assets::enqueue();
		return self::html( self::model() );
	}







	private static function scope( int $user_id ): array {
		$base = array(
			'post_type'           => EventPostType::POST_TYPE,

			'post_parent'         => 0,



			'post_status'         => array_merge( self::STATUSES, array( self::FILTER_TRASH ) ),



			'posts_per_page'      => 200, 
			'orderby'             => 'date',
			'order'               => 'DESC',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		);

		if ( EventAccess::can_edit_all_areas( $user_id ) ) {
			return get_posts( $base );
		}

		$areas = EventAccess::user_areas( $user_id );
		if ( array() === $areas ) {
			return array();
		}

		$del_area = get_posts(
			array_merge(
				$base,
				array(
					'tax_query' => array( 
						array(
							'taxonomy' => EventTaxonomies::AREA,
							'field'    => 'term_id',
							'terms'    => $areas,
						),
					),
				)
			)
		);




		$mios = get_posts( array_merge( $base, array( 'author' => $user_id ) ) );

		$unicos = array();
		foreach ( array_merge( $del_area, $mios ) as $post ) {
			$unicos[ (int) $post->ID ] = $post;
		}

		return array_values( $unicos );
	}







	private static function row( \WP_Post $post ): array {
		$id     = (int) $post->ID;
		$titulo = trim( (string) $post->post_title );
		$inicio = (string) get_post_meta( $id, EventMetaKeys::START_DATE, true );
		$fin    = (string) get_post_meta( $id, EventMetaKeys::END_DATE, true );
		$titulo = '' !== $titulo ? $titulo : 'Evento sin título';

		return array(
			'id'       => $id,
			'title'    => $titulo,
			'areas'    => self::terms( $id, EventTaxonomies::AREA ),
			'types'    => self::terms( $id, EventTaxonomies::TYPE ),
			'courses'  => self::terms( $id, EventTaxonomies::COURSE ),
			'start'    => $inicio,
			'end'      => $fin,
			'state'    => EventState::of( $inicio, $fin ),
			'archived' => EventAccess::is_archived( $id ),
			'status'   => (string) $post->post_status,
			'sections' => 0,


			'url'      => self::FILTER_TRASH === $post->post_status ? '' : Shell::url( 'event', array( 'evento' => $id ) ),




			'view_url' => self::FILTER_TRASH === $post->post_status
				? ''
				: ( 'publish' === $post->post_status ? (string) get_permalink( $id ) : (string) get_preview_post_link( $id ) ),
			'can_pub'  => EventAccess::can_publish( get_current_user_id(), $id ),
			'search'   => self::normalize( $titulo ),
		);
	}








	private static function terms( int $post_id, string $taxonomy ): array {
		$terms = get_the_terms( $post_id, $taxonomy );
		if ( ! is_array( $terms ) ) {
			return array();
		}

		$out = array();
		foreach ( $terms as $term ) {
			if ( $term instanceof \WP_Term ) {
				$out[ (int) $term->term_id ] = (string) $term->name;
			}
		}
		return $out;
	}










	private static function options( array $rows ): array {
		$out  = array(
			'area'   => array(),
			'type'   => array(),
			'course' => array(),
		);
		$ejes = array(
			'area'   => 'areas',
			'type'   => 'types',
			'course' => 'courses',
		);

		foreach ( $rows as $row ) {
			foreach ( $ejes as $eje => $clave ) {
				foreach ( $row[ $clave ] as $term_id => $nombre ) {
					$out[ $eje ][ $term_id ] = $nombre;
				}
			}
		}

		foreach ( $out as &$opciones ) {
			asort( $opciones );
		}
		unset( $opciones );

		return $out;
	}








	private static function in_scope( array $row, array $s ): bool {
		$ejes = array(
			'area'   => 'areas',
			'type'   => 'types',
			'course' => 'courses',
		);

		foreach ( $ejes as $eje => $clave ) {
			if ( $s[ $eje ] > 0 && ! isset( $row[ $clave ][ $s[ $eje ] ] ) ) {
				return false;
			}
		}

		return '' === $s['search']
			|| false !== strpos( $row['search'], self::normalize( $s['search'] ) );
	}











	private static function matches( array $row, string $filter ): bool {
		if ( 'all' === $filter ) {
			return true;
		}
		if ( self::FILTER_TRASH === $filter ) {
			return self::FILTER_TRASH === $row['status'];
		}
		if ( 'vivos' === $filter ) {
			return self::FILTER_TRASH !== $row['status'];
		}
		if ( self::FILTER_ARCHIVED === $filter ) {
			return true === $row['archived'];
		}
		if ( self::FILTER_DRAFT === $filter ) {
			return 'draft' === $row['status'];
		}
		return $filter === $row['state'];
	}








	private static function compare( array $a, array $b ): int {
		$ka = '' !== $a['start'] ? $a['start'] : '9999-12-31';
		$kb = '' !== $b['start'] ? $b['start'] : '9999-12-31';

		return $ka === $kb ? strnatcasecmp( $a['title'], $b['title'] ) : strcmp( $kb, $ka );
	}







	private static function section_counts( array $ids ): array {
		if ( array() === $ids ) {
			return array();
		}

		$out   = array_fill_keys( $ids, 0 );
		$hijas = get_posts(
			array(
				'post_type'           => EventPostType::POST_TYPE,
				'post_parent__in'     => $ids,
				'post_status'         => self::STATUSES,
				'posts_per_page'      => 500, 
				'fields'              => 'id=>parent',
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
			)
		);

		foreach ( $hijas as $hija ) {

			$padre = is_object( $hija ) ? (int) $hija->post_parent : (int) $hija;
			if ( isset( $out[ $padre ] ) ) {
				++$out[ $padre ];
			}
		}

		return $out;
	}







	private static function is_filtered( array $s ): bool {
		return $s['area'] > 0 || $s['type'] > 0 || $s['course'] > 0
			|| 'all' !== $s['state'] || '' !== $s['search'];
	}






	private static function no_filters(): array {
		return array(
			'area'   => 0,
			'type'   => 0,
			'course' => 0,
			'state'  => 'all',
			'search' => '',
			'page'   => 1,
		);
	}








	private static function empty_text( bool $filtered, bool $all_areas ): string {
		if ( $filtered ) {
			return 'Ningún evento coincide con lo que ha pedido. Pruebe a quitar algún filtro o a buscar otra cosa.';
		}
		return $all_areas
			? 'Todavía no hay ningún evento. Cree el primero con «Crear evento».'
			: 'Todavía no hay ningún evento de su área. Aquí solo salen los eventos del área que los organiza; cree el primero con «Crear evento».';
	}






	private static function flash(): array {
		$avisos = array(
			'creado'       => array( 'ok', 'Evento creado. Ya puede añadirle secciones.' ),
			'guardado'     => array( 'ok', 'Cambios guardados.' ),
			'borrado'      => array( 'ok', 'Evento enviado a la papelera. Nada se ha perdido: está en «Papelera» y se restaura desde ahí.' ),
			'restaurado'   => array( 'ok', 'Evento restaurado, en borrador: revíselo y publíquelo cuando esté listo.' ),
			'publicado'    => array( 'ok', 'Evento publicado: ya se ve en la web. Sus páginas se publican cada una desde el taller.' ),
			'despublicado' => array( 'ok', 'Evento devuelto a borrador: deja de verse en la web y no se pierde nada.' ),
			'permiso'      => array( 'error', 'Ese evento es de otra área: solo lo edita el área que lo organiza o quien administra el aplicativo.' ),
		);

		$aviso = $avisos[ self::input( self::VAR_NOTICE ) ] ?? array( '', '' );

		return array(
			'type' => $aviso[0],
			'text' => $aviso[1],
		);
	}










	private static function hidden_page_id(): int {
		return '' === (string) get_option( 'permalink_structure' ) ? (int) get_queried_object_id() : 0;
	}







	private static function normalize( string $text ): string {
		return mb_strtolower( remove_accents( $text ), 'UTF-8' );
	}
}








namespace Evt\PublicFront\View;

use Evt\Domain\DateRange;
use Evt\Meta\EventMetaKeys;
use Evt\PublicFront\Assets;
use Evt\PublicFront\EventList;
use Evt\PublicFront\Shell;




final class EventListView {







	public static function html( array $m ): string {
		if ( empty( $m['can_use'] ) ) {
			return Shell::render( 'Eventos', '', Shell::notice( 'aviso', (string) $m['reason'] ) );
		}

		ob_start();
		?>
		<?php echo Shell::notice( (string) $m['notice']['type'], (string) $m['notice']['text'] ); ?>
		<?php if ( ! empty( $m['can_create'] ) ) : ?>
			<p class="evt-acciones">
				<a class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" href="<?php echo esc_url( (string) $m['create_url'] ); ?>">
					<?php echo Shell::icon_plus(); ?>
					Crear evento
				</a>
			</p>
		<?php endif; ?>
		<?php self::counts( $m ); ?>
		<?php self::trash_bar( $m ); ?>
		<?php self::filters( $m ); ?>
		<?php self::table( $m ); ?>
		<?php self::pagination( $m ); ?>
		<?php
		return Shell::render( 'Eventos', (string) $m['subtitle'], (string) ob_get_clean() );
	}







	private static function counts( array $m ): void {
		$fichas = array(
			'all'                         => 'Eventos',
			EventMetaKeys::STATE_UPCOMING => 'Próximos',
			EventMetaKeys::STATE_OPEN     => 'Abiertos',
			EventList::FILTER_DRAFT       => 'En borrador',
		);
		?>
		<ul class="evt-cifras">
			<?php foreach ( $fichas as $clave => $rotulo ) : ?>
				<li class="evt-cifra">
					<strong><?php echo esc_html( (string) ( $m['counts'][ $clave ] ?? 0 ) ); ?></strong>
					<span><?php echo esc_html( $rotulo ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
	}











	private static function trash_bar( array $m ): void {
		$cuantos = (int) ( $m['counts'][ EventList::FILTER_TRASH ] ?? 0 );
		$dentro  = EventList::FILTER_TRASH === (string) $m['selection']['state'];
		if ( 0 === $cuantos && ! $dentro ) {
			return;
		}
		?>
		<p class="evt-acciones">
			<?php if ( $dentro ) : ?>
				<a href="<?php echo esc_url( EventList::url( $m['selection'], array( 'state' => 'all' ) ) ); ?>">Volver al listado</a>
				<span>Restaurar devuelve el evento a borrador. Para borrar algo de verdad y para siempre hay que ir al escritorio de WordPress: desde aquí no se destruye nada.</span>
			<?php else : ?>
				<a href="<?php echo esc_url( EventList::url( $m['selection'], array( 'state' => EventList::FILTER_TRASH ) ) ); ?>">
					<?php echo esc_html( sprintf( 'Papelera (%d)', $cuantos ) ); ?>
				</a>
			<?php endif; ?>
		</p>
		<?php
	}







	private static function filters( array $m ): void {
		$s      = $m['selection'];
		$listas = array(
			'area'   => array(
				'var'     => EventList::VAR_AREA,
				'label'   => 'Área',
				'any'     => 'Todas las áreas',
				'choices' => $m['options']['area'],
			),
			'type'   => array(
				'var'     => EventList::VAR_TYPE,
				'label'   => 'Tipología',
				'any'     => 'Todas las tipologías',
				'choices' => $m['options']['type'],
			),
			'course' => array(
				'var'     => EventList::VAR_COURSE,
				'label'   => 'Curso escolar',
				'any'     => 'Todos los cursos',
				'choices' => $m['options']['course'],
			),
		);
		if ( empty( $m['area_filter'] ) ) {
			unset( $listas['area'] );
		}
		?>
		<form class="evt-form evt-tarjeta" method="get" action="">
			<?php if ( (int) $m['page_id'] > 0 ) : ?>
				<input type="hidden" name="page_id" value="<?php echo esc_attr( (string) $m['page_id'] ); ?>" />
			<?php endif; ?>
			<div class="evt-form-fila">
				<div>
					<label for="evt-buscar">Buscar</label>
					<input type="search" id="evt-buscar" name="<?php echo esc_attr( EventList::VAR_SEARCH ); ?>"
						value="<?php echo esc_attr( (string) $s['search'] ); ?>"
						placeholder="Título del evento…" autocomplete="off" />
				</div>
				<?php foreach ( $listas as $eje => $lista ) : ?>
					<div>
						<label for="evt-<?php echo esc_attr( $eje ); ?>"><?php echo esc_html( $lista['label'] ); ?></label>
						<select id="evt-<?php echo esc_attr( $eje ); ?>" name="<?php echo esc_attr( $lista['var'] ); ?>">
							<option value="0"><?php echo esc_html( $lista['any'] ); ?></option>
							<?php foreach ( $lista['choices'] as $term_id => $nombre ) : ?>
								<option value="<?php echo esc_attr( (string) $term_id ); ?>" <?php selected( (int) $s[ $eje ], (int) $term_id ); ?>>
									<?php echo esc_html( $nombre ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php endforeach; ?>
				<div>
					<label for="evt-estado">Estado</label>
					<select id="evt-estado" name="<?php echo esc_attr( EventList::VAR_STATE ); ?>">
						<?php foreach ( EventList::state_filters() as $clave => $rotulo ) : ?>
							<option value="<?php echo esc_attr( $clave ); ?>" <?php selected( (string) $s['state'], $clave ); ?>>
								<?php echo esc_html( $rotulo ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<p class="evt-acciones">
				<button class="<?php echo esc_attr( Assets::button_class() ); ?>" type="submit">Filtrar</button>
				<?php if ( '' !== (string) $m['reset_url'] ) : ?>
					<a href="<?php echo esc_url( (string) $m['reset_url'] ); ?>">Quitar los filtros</a>
				<?php endif; ?>
			</p>
		</form>
		<?php
	}







	private static function table( array $m ): void {
		$estados = EventMetaKeys::states();
		$publica = EventList::status_labels();
		$dentro  = EventList::FILTER_TRASH === (string) $m['selection']['state'];
		?>
		<div class="evt-tabla-caja">
			<?php if ( array() === $m['rows'] ) : ?>
				<p class="evt-vacio"><?php echo esc_html( (string) $m['empty_text'] ); ?></p>
			<?php else : ?>
				<table class="evt-tabla">
					<thead>
						<tr>
							<th scope="col">Evento</th>
							<th scope="col">Área</th>
							<th scope="col">Tipología</th>
							<th scope="col">Curso</th>
							<th scope="col">Fechas</th>
							<th scope="col">Estado</th>
							<th scope="col">Publicación</th>
							<th scope="col" class="evt-num">Secciones</th>
							<th scope="col">Acciones</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $m['rows'] as $row ) : ?>
							<tr>
								<td data-rotulo="Evento">
									<?php if ( '' !== (string) $row['url'] ) : ?>
										<a href="<?php echo esc_url( (string) $row['url'] ); ?>"><?php echo esc_html( (string) $row['title'] ); ?></a>
									<?php else : ?>
										<?php echo esc_html( (string) $row['title'] ); ?>
									<?php endif; ?>
								</td>
								<td data-rotulo="Área"><?php echo esc_html( self::names( $row['areas'] ) ); ?></td>
								<td data-rotulo="Tipología"><?php echo esc_html( self::names( $row['types'] ) ); ?></td>
								<td data-rotulo="Curso"><?php echo esc_html( self::names( $row['courses'] ) ); ?></td>
								<td data-rotulo="Fechas"><?php echo esc_html( self::dates( (string) $row['start'], (string) $row['end'] ) ); ?></td>
								<td data-rotulo="Estado">
									<span class="<?php echo esc_attr( Assets::state_class( (string) $row['state'] ) ); ?>">
										<?php echo esc_html( $estados[ $row['state'] ] ?? '—' ); ?>
									</span>
									<?php if ( ! empty( $row['archived'] ) ) : ?>
										<span class="<?php echo esc_attr( Assets::state_class( EventList::FILTER_ARCHIVED ) ); ?>"
											title="Cerrado a edición: se consulta y se exporta, pero no se cambia.">Histórico</span>
									<?php endif; ?>
								</td>
								<td data-rotulo="Publicación">
									<?php if ( $dentro || true !== $row['can_pub'] ) : ?>
										<span class="<?php echo esc_attr( Assets::state_class( (string) $row['status'] ) ); ?>">
											<?php echo esc_html( $publica[ $row['status'] ] ?? (string) $row['status'] ); ?>
										</span>
									<?php else : ?>
										<?php echo self::publish_switch( (array) $row ); ?>
									<?php endif; ?>
								</td>
								<td class="evt-num" data-rotulo="Secciones"><?php echo esc_html( (string) $row['sections'] ); ?></td>
								<td data-rotulo="Acciones">
									<?php if ( $dentro ) : ?>
										<?php echo self::restore_form( (int) $row['id'] ); ?>
									<?php else : ?>
										<span class="evt-acciones">
											<?php
											echo PanelParts::icon_link( (string) $row['url'], 'lapiz', 'Editar este evento' ); 
											$mirar = 'publish' === (string) $row['status']
												? 'Ver la página del evento'
												: 'Previsualizar el evento, que está en borrador';
											echo PanelParts::icon_link( (string) $row['view_url'], 'ojo', $mirar ); 
											?>
										</span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}















	private static function publish_switch( array $row ): string {
		$id        = (int) $row['id'];
		$publicado = 'publish' === (string) $row['status'];
		$op        = $publicado ? 'unpublish' : 'publish';
		$rotulo    = $publicado ? 'Despublicar este evento' : 'Publicar este evento';

		ob_start();
		?>
		<form class="evt-accion evt-switch" method="post" action="">
			<?php wp_nonce_field( EventList::nonce_action( $op ), EventList::nonce_name( $id ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( EventList::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventList::FIELD_EVENT ); ?>" value="<?php echo esc_attr( (string) $id ); ?>" />
			<label class="evt-switch-caja" title="<?php echo esc_attr( $rotulo ); ?>" data-bs-toggle="tooltip">
				<input type="checkbox" class="evt-switch-input" data-evt-switch
					<?php checked( $publicado, true ); ?> />
				<span class="evt-switch-pista" aria-hidden="true"></span>
				<span class="evt-switch-txt"><?php echo esc_html( $publicado ? 'Publicado' : 'Borrador' ); ?></span>
				<span class="screen-reader-text"><?php echo esc_html( $rotulo ); ?></span>
			</label>
			<button type="submit" class="<?php echo esc_attr( Assets::button_class() . ' evt-mini evt-switch-boton' ); ?>"><?php echo esc_html( $publicado ? 'Despublicar' : 'Publicar' ); ?></button>
		</form>
		<?php
		return (string) ob_get_clean();
	}







	private static function pagination( array $m ): void {
		if ( (int) $m['pages'] < 2 ) {
			return;
		}
		$s      = $m['selection'];
		$pagina = (int) $m['page'];
		?>
		<nav aria-label="Páginas de eventos">
			<p class="evt-acciones">
				<?php if ( $pagina > 1 ) : ?>
					<a class="<?php echo esc_attr( Assets::button_class() ); ?>"
						href="<?php echo esc_url( EventList::url( $s, array( 'page' => $pagina - 1 ) ) ); ?>">Anterior</a>
				<?php endif; ?>
				<span>
					<?php
					echo esc_html(
						sprintf(
							'Página %1$d de %2$d · %3$d eventos',
							$pagina,
							(int) $m['pages'],
							(int) $m['total']
						)
					);
					?>
				</span>
				<?php if ( $pagina < (int) $m['pages'] ) : ?>
					<a class="<?php echo esc_attr( Assets::button_class() ); ?>"
						href="<?php echo esc_url( EventList::url( $s, array( 'page' => $pagina + 1 ) ) ); ?>">Siguiente</a>
				<?php endif; ?>
			</p>
		</nav>
		<?php
	}











	private static function restore_form( int $event_id ): string {
		ob_start();
		?>
		<form class="evt-accion" method="post" action="">
			<?php wp_nonce_field( EventList::NONCE_ACTION, EventList::nonce_name( $event_id ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( EventList::FIELD_DO ); ?>" value="restore" />
			<input type="hidden" name="<?php echo esc_attr( EventList::FIELD_EVENT ); ?>" value="<?php echo esc_attr( (string) $event_id ); ?>" />
			<button type="submit" class="<?php echo esc_attr( Assets::button_class() ); ?>">Restaurar</button>
		</form>
		<?php
		return (string) ob_get_clean();
	}







	private static function names( array $terms ): string {
		return array() === $terms ? '—' : implode( ' · ', $terms );
	}








	private static function dates( string $start, string $end ): string {
		$texto = DateRange::of( $start, $end );
		return '' !== $texto ? $texto : 'Sin fechas';
	}
}








namespace Evt\PublicFront;

use Evt\Access\EventAccess;
use Evt\Meta\ProgrammeMetaKeys;
use Evt\PostType\ActivityPostType;
use Evt\PostType\SpeakerPostType;














final class Programme {








	public static function speakers( int $event_id, bool $papelera = false ): array {
		return self::children( $event_id, SpeakerPostType::POST_TYPE, $papelera );
	}














	public static function activities( int $event_id, bool $papelera = false ): array {
		$filas = self::children( $event_id, ActivityPostType::POST_TYPE, $papelera );

		usort(
			$filas,
			static function ( \WP_Post $a, \WP_Post $b ) {
				$clave = static function ( \WP_Post $p ): array {
					$dia = (string) get_post_meta( $p->ID, ProgrammeMetaKeys::ACTIVITY_DATE, true );
					return array(
						'' === $dia ? '9999-99-99' : $dia,
						(string) get_post_meta( $p->ID, ProgrammeMetaKeys::ACTIVITY_START, true ),
						(int) $p->menu_order,
						(int) $p->ID,
					);
				};
				return $clave( $a ) <=> $clave( $b );
			}
		);

		return $filas;
	}







	public static function workshops( int $event_id ): array {
		$out = array();
		foreach ( self::activities( $event_id ) as $actividad ) {
			if ( ProgrammeMetaKeys::KIND_WORKSHOP === (string) get_post_meta( $actividad->ID, ProgrammeMetaKeys::ACTIVITY_KIND, true ) ) {
				$out[] = $actividad;
			}
		}
		return $out;
	}












	public static function grid( int $event_id ): array {
		$dias = array();
		foreach ( self::activities( $event_id ) as $actividad ) {
			$fila  = self::activity_row( $actividad );
			$dia   = (string) $fila['date'];
			$sede  = (string) $fila['venue'];
			$clave = '' === $dia ? '' : $dia;

			if ( ! isset( $dias[ $clave ] ) ) {
				$dias[ $clave ] = array(
					'date'   => $dia,
					'venues' => array(),
				);
			}
			if ( ! isset( $dias[ $clave ]['venues'][ $sede ] ) ) {
				$dias[ $clave ]['venues'][ $sede ] = array(
					'venue' => $sede,
					'rows'  => array(),
				);
			}
			$dias[ $clave ]['venues'][ $sede ]['rows'][] = $fila;
		}



		$out = array();
		foreach ( $dias as $dia ) {
			$dia['venues'] = array_values( $dia['venues'] );
			$out[]         = $dia;
		}
		return $out;
	}











	public static function venues( int $event_id ): array {
		$out = array();
		foreach ( self::activities( $event_id ) as $actividad ) {
			$sede = trim( (string) get_post_meta( $actividad->ID, ProgrammeMetaKeys::ACTIVITY_VENUE, true ) );
			if ( '' !== $sede && ! in_array( $sede, $out, true ) ) {
				$out[] = $sede;
			}
		}
		sort( $out );
		return $out;
	}









	public static function save_speaker( int $event_id, int $speaker_id, array $data ): int {
		$campos = array(
			'post_type'    => SpeakerPostType::POST_TYPE,
			'post_parent'  => $event_id,
			'post_title'   => (string) $data['name'],
			'post_content' => (string) $data['bio'],
			'post_status'  => 'publish',
		);

		$id = self::write( $speaker_id, $campos, SpeakerPostType::POST_TYPE, $event_id );
		if ( $id <= 0 ) {
			return 0;
		}

		update_post_meta( $id, ProgrammeMetaKeys::SPEAKER_ROLE, (string) $data['role'] );
		update_post_meta( $id, ProgrammeMetaKeys::SPEAKER_ORG, (string) $data['org'] );
		EventAccess::stamp_area( $id );

		return $id;
	}









	public static function save_activity( int $event_id, int $activity_id, array $data ): int {
		$campos = array(
			'post_type'    => ActivityPostType::POST_TYPE,
			'post_parent'  => $event_id,
			'post_title'   => (string) $data['title'],
			'post_content' => (string) $data['summary'],
			'post_status'  => 'publish',
		);

		$id = self::write( $activity_id, $campos, ActivityPostType::POST_TYPE, $event_id );
		if ( $id <= 0 ) {
			return 0;
		}

		update_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_KIND, (string) $data['kind'] );
		update_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_DATE, (string) $data['date'] );
		update_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_START, (string) $data['start'] );
		update_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_END, (string) $data['end'] );
		update_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_VENUE, (string) $data['venue'] );
		update_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_ROOM, (string) $data['room'] );
		update_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_SEATS, (int) $data['seats'] );


		update_post_meta(
			$id,
			ProgrammeMetaKeys::ACTIVITY_SPEAKERS,
			implode( ',', self::own_speakers( $event_id, (array) $data['speakers'] ) )
		);
		EventAccess::stamp_area( $id );

		return $id;
	}








	public static function trash( int $event_id, int $post_id ): bool {
		if ( ! self::belongs( $event_id, $post_id ) ) {
			return false;
		}
		return (bool) wp_trash_post( $post_id );
	}








	public static function restore( int $event_id, int $post_id ): bool {
		if ( ! self::belongs( $event_id, $post_id, true ) ) {
			return false;
		}
		return (bool) wp_untrash_post( $post_id );
	}













	public static function reorder_speaker( int $event_id, int $speaker_id, int $delta ): bool {
		$ids   = wp_list_pluck( self::speakers( $event_id ), 'ID' );
		$ids   = array_map( 'intval', $ids );
		$desde = array_search( $speaker_id, $ids, true );
		if ( false === $desde ) {
			return false;
		}
		$hasta = (int) $desde + $delta;
		if ( $hasta < 0 || $hasta >= count( $ids ) ) {
			return false;
		}

		$movido        = $ids[ $desde ];
		$ids[ $desde ] = $ids[ $hasta ];
		$ids[ $hasta ] = $movido;

		foreach ( $ids as $posicion => $id ) {
			wp_update_post(
				array(
					'ID'         => $id,
					'menu_order' => ( $posicion + 1 ) * 10,
				)
			);
		}
		return true;
	}













	public static function speaker_row( \WP_Post $ponente ): array {
		$id = (int) $ponente->ID;
		return array(
			'id'       => $id,
			'name'     => (string) $ponente->post_title,
			'role'     => (string) get_post_meta( $id, ProgrammeMetaKeys::SPEAKER_ROLE, true ),
			'org'      => (string) get_post_meta( $id, ProgrammeMetaKeys::SPEAKER_ORG, true ),
			'bio'      => (string) $ponente->post_content,
			'photo'    => (string) get_the_post_thumbnail_url( $id, 'thumbnail' ),
			'photo_id' => (int) get_post_thumbnail_id( $id ),
		);
	}







	public static function activity_row( \WP_Post $actividad ): array {
		$id       = (int) $actividad->ID;
		$ponentes = array();
		foreach ( self::speaker_ids( $id ) as $speaker_id ) {
			$nombre = (string) get_post_field( 'post_title', $speaker_id );
			if ( '' !== $nombre ) {
				$ponentes[ $speaker_id ] = $nombre;
			}
		}

		return array(
			'id'          => $id,
			'title'       => (string) $actividad->post_title,
			'summary'     => (string) $actividad->post_content,
			'kind'        => (string) get_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_KIND, true ),
			'kind_label'  => ProgrammeMetaKeys::kind_label( (string) get_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_KIND, true ) ),
			'date'        => (string) get_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_DATE, true ),
			'start'       => (string) get_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_START, true ),
			'end'         => (string) get_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_END, true ),
			'venue'       => (string) get_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_VENUE, true ),
			'room'        => (string) get_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_ROOM, true ),
			'seats'       => (int) get_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_SEATS, true ),
			'speaker_ids' => array_keys( $ponentes ),
			'speakers'    => $ponentes,
		);
	}







	public static function speaker_ids( int $activity_id ): array {
		$bruto = (string) get_post_meta( $activity_id, ProgrammeMetaKeys::ACTIVITY_SPEAKERS, true );
		if ( '' === $bruto ) {
			return array();
		}
		$ids = array();
		foreach ( explode( ',', $bruto ) as $uno ) {
			$id = absint( $uno );
			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}
		return $ids;
	}















	private static function children( int $event_id, string $post_type, bool $papelera ): array {
		if ( $event_id <= 0 ) {
			return array();
		}
		$posts = get_posts(
			array(
				'post_type'        => $post_type,
				'post_parent'      => $event_id,
				'post_status'      => $papelera ? 'trash' : array( 'publish', 'draft', 'pending', 'private' ),
				'numberposts'      => -1,
				'orderby'          => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
				'suppress_filters' => false,
			)
		);
		return is_array( $posts ) ? $posts : array();
	}










	private static function write( int $post_id, array $campos, string $post_type, int $event_id ): int {
		if ( $post_id > 0 ) {
			if ( ! self::belongs( $event_id, $post_id ) || get_post_type( $post_id ) !== $post_type ) {
				return 0;
			}
			$campos['ID'] = $post_id;
			$hecho        = wp_update_post( $campos, true );
		} else {
			$hecho = wp_insert_post( $campos, true );
		}

		return is_wp_error( $hecho ) ? 0 : (int) $hecho;
	}













	private static function belongs( int $event_id, int $post_id, bool $papelera = false ): bool {
		if ( $event_id <= 0 || $post_id <= 0 ) {
			return false;
		}
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return false;
		}
		if ( ! in_array( $post->post_type, array( SpeakerPostType::POST_TYPE, ActivityPostType::POST_TYPE ), true ) ) {
			return false;
		}
		if ( ( 'trash' === $post->post_status ) !== $papelera ) {
			return false;
		}
		return (int) $post->post_parent === $event_id;
	}








	private static function own_speakers( int $event_id, array $ids ): array {
		$suyos = array_map( 'intval', wp_list_pluck( self::speakers( $event_id ), 'ID' ) );
		$out   = array();
		foreach ( $ids as $id ) {
			$id = (int) $id;
			if ( in_array( $id, $suyos, true ) ) {
				$out[] = $id;
			}
		}
		return $out;
	}
}








namespace Evt\PublicFront;




















final class Participants {




	public const HOOK = 'evt_participants';











	public const KEY_FILES = '_files';









	public static function columns(): array {
		return array(
			'name'        => 'Nombre',
			'email'       => 'Correo',
			'centre_code' => 'Código de centro',
			'centre'      => 'Centro',
			'workshop'    => 'Taller',
			'date'        => 'Fecha de inscripción',
			'consent'     => 'Consentimiento',
			'files'       => 'Documentos',
		);
	}







	public static function rows( int $event_id ): array {
		if ( $event_id <= 0 ) {
			return array();
		}







		$filas = apply_filters( self::HOOK, array(), $event_id );

		return self::clean( is_array( $filas ) ? $filas : array() );
	}










	public static function clean( array $filas ): array {
		$columnas = array_keys( self::columns() );
		$out      = array();
		foreach ( $filas as $fila ) {
			if ( ! is_array( $fila ) ) {
				continue;
			}
			$limpia = array();
			foreach ( $columnas as $clave ) {
				$valor            = $fila[ $clave ] ?? '';
				$limpia[ $clave ] = is_scalar( $valor ) ? trim( (string) $valor ) : '';
			}



			if ( isset( $fila[ self::KEY_FILES ] ) && is_array( $fila[ self::KEY_FILES ] ) ) {
				$limpia[ self::KEY_FILES ] = array_values( $fila[ self::KEY_FILES ] );
			}
			$out[] = $limpia;
		}
		return $out;
	}













	public static function filter( array $filas, string $texto = '', string $taller = '' ): array {
		$texto  = trim( $texto );
		$taller = trim( $taller );
		if ( '' === $texto && '' === $taller ) {
			return $filas;
		}

		$buscado = self::fold( $texto );
		$out     = array();
		foreach ( $filas as $fila ) {
			if ( '' !== $taller && ( $fila['workshop'] ?? '' ) !== $taller ) {
				continue;
			}


			$texto_fila = '';
			foreach ( array_keys( self::columns() ) as $clave ) {
				$texto_fila .= ( $fila[ $clave ] ?? '' ) . ' ';
			}
			if ( '' !== $buscado && false === strpos( self::fold( $texto_fila ), $buscado ) ) {
				continue;
			}
			$out[] = $fila;
		}
		return $out;
	}







	public static function workshops( array $filas ): array {
		$out = array();
		foreach ( $filas as $fila ) {
			$taller = trim( (string) ( $fila['workshop'] ?? '' ) );
			if ( '' !== $taller && ! in_array( $taller, $out, true ) ) {
				$out[] = $taller;
			}
		}
		sort( $out );
		return $out;
	}

















	public static function csv( array $filas ): string {
		$columnas = self::columns();
		$lineas   = array( self::csv_line( array_values( $columnas ) ) );

		foreach ( $filas as $fila ) {
			$campos = array();
			foreach ( array_keys( $columnas ) as $clave ) {
				$campos[] = (string) ( $fila[ $clave ] ?? '' );
			}
			$lineas[] = self::csv_line( $campos );
		}



		return "\xEF\xBB\xBF" . implode( "\r\n", $lineas ) . "\r\n";
	}







	public static function filename( string $titulo ): string {
		$base = sanitize_title( $titulo );
		if ( '' === $base ) {
			$base = 'evento';
		}
		return 'participantes-' . $base . '-' . gmdate( 'Y-m-d' ) . '.csv';
	}







	private static function csv_line( array $campos ): string {
		$fuera = array();
		foreach ( $campos as $campo ) {
			$fuera[] = '"' . str_replace( '"', '""', self::defuse( $campo ) ) . '"';
		}
		return implode( ';', $fuera );
	}







	private static function defuse( string $campo ): string {
		if ( '' === $campo ) {
			return '';
		}
		return false === strpos( "=+-@\t\r", $campo[0] ) ? $campo : "'" . $campo;
	}







	private static function fold( string $texto ): string {
		$texto = strtr(
			$texto,
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
		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $texto, 'UTF-8' ) : strtolower( $texto );
	}
}








namespace Evt\PublicFront;

use Evt\Domain\RegistrationInput;
use Evt\Domain\SignupQuestions;
use Evt\Meta\ProgrammeMetaKeys;
use Evt\Meta\RegistrationMetaKeys;
use Evt\PostType\RegistrationPostType;












final class Registrations {








	public const LOCK_TTL = 10;




	private const LOCK_WAIT = 2000000;




	private const LOCK_PREFIX = 'evt_seat_lock_';






	public static function register(): void {



		add_filter( Participants::HOOK, array( self::class, 'participants' ), 10, 2 );
	}









	public static function all( int $event_id ): array {
		if ( $event_id <= 0 ) {
			return array();
		}
		$posts = get_posts(
			array(
				'post_type'        => RegistrationPostType::POST_TYPE,
				'post_parent'      => $event_id,
				'post_status'      => array( 'publish', 'private' ),
				'numberposts'      => -1,
				'orderby'          => 'date',
				'order'            => 'ASC',
				'suppress_filters' => false,
			)
		);
		return is_array( $posts ) ? $posts : array();
	}












	public static function participants( array $filas, int $event_id ): array {
		$preguntas = self::questions( $event_id );
		$talleres  = array();
		foreach ( Programme::workshops( $event_id ) as $taller ) {
			$talleres[ $taller->ID ] = (string) $taller->post_title;
		}

		foreach ( self::all( $event_id ) as $inscripcion ) {
			$meta   = self::meta( $inscripcion->ID );
			$taller = (int) $meta[ RegistrationMetaKeys::REG_WORKSHOP ];





			$documentos = array();
			foreach ( RegistrationFiles::descriptors( (int) $inscripcion->ID ) as $descriptor ) {
				$documentos[] = array(
					'reg'  => (int) $inscripcion->ID,
					'id'   => (string) $descriptor['id'],
					'name' => (string) ( $descriptor['name'] ?? '' ),
				);
			}

			$fila                            = array(
				'name'        => trim( $meta[ RegistrationMetaKeys::REG_NAME ] . ' ' . $meta[ RegistrationMetaKeys::REG_SURNAME ] ),
				'email'       => $meta[ RegistrationMetaKeys::REG_EMAIL ],
				'centre_code' => $meta[ RegistrationMetaKeys::REG_CENTRE_CODE ] ?? '',
				'centre'      => $meta[ RegistrationMetaKeys::REG_CENTRE ],
				'workshop'    => $talleres[ $taller ] ?? '',
				'date'        => get_the_date( 'Y-m-d H:i', $inscripcion ),
				'consent'     => self::consent_text( $meta ),
				'files'       => implode( ', ', wp_list_pluck( $documentos, 'name' ) ),
			);
			$fila[ Participants::KEY_FILES ] = $documentos;




			$respuestas = self::answers( $inscripcion->ID );
			foreach ( $preguntas as $pregunta ) {
				$fila[ $pregunta['label'] ] = SignupQuestions::as_text(
					$pregunta,
					$respuestas[ $pregunta['id'] ] ?? null
				);
			}

			$filas[] = $fila;
		}

		return $filas;
	}







	public static function questions( int $event_id ): array {
		return SignupQuestions::read( get_post_meta( $event_id, RegistrationMetaKeys::SIGNUP_QUESTIONS, true ) );
	}







	public static function answers( int $registration_id ): array {
		$crudo = get_post_meta( $registration_id, RegistrationMetaKeys::REG_ANSWERS, true );
		$datos = is_string( $crudo ) && '' !== $crudo ? json_decode( $crudo, true ) : $crudo;
		return is_array( $datos ) ? $datos : array();
	}







	public static function meta( int $registration_id ): array {
		$out = array();
		foreach ( RegistrationMetaKeys::registration_keys() as $clave ) {
			$valor         = get_post_meta( $registration_id, $clave, true );
			$out[ $clave ] = is_scalar( $valor ) ? (string) $valor : '';
		}
		return $out;
	}







	private static function consent_text( array $meta ): string {
		$cuando = $meta[ RegistrationMetaKeys::REG_CONSENT_AT ];
		if ( '' === $cuando ) {
			return '';
		}
		return 'Sí (v' . $meta[ RegistrationMetaKeys::REG_CONSENT_VERSION ] . ', ' . $cuando . ')';
	}










	public static function has_any( int $event_id ): bool {
		return array() !== self::all( $event_id );
	}












	public static function by_token( int $event_id, string $token ): int {
		$token = trim( $token );
		if ( ! self::is_token( $token ) ) {
			return 0;
		}
		foreach ( self::all( $event_id ) as $inscripcion ) {
			$suyo = (string) get_post_meta( $inscripcion->ID, RegistrationMetaKeys::REG_TOKEN, true );
			if ( '' !== $suyo && hash_equals( $suyo, $token ) ) {
				return (int) $inscripcion->ID;
			}
		}
		return 0;
	}







	public static function is_token( string $token ): bool {
		return (bool) preg_match( '/^[a-f0-9]{40}$/', $token );
	}














	public static function create( int $event_id, array $core, array $answers ): int {
		if ( $event_id <= 0 ) {
			return 0;
		}

		$id = wp_insert_post(
			array(
				'post_type'   => RegistrationPostType::POST_TYPE,
				'post_parent' => $event_id,
				'post_status' => 'private',
				'post_title'  => self::reference( $event_id ),
			),
			true
		);
		if ( is_wp_error( $id ) || ! $id ) {
			return 0;
		}
		$id = (int) $id;

		$metas = array(
			RegistrationMetaKeys::REG_TAX_ID          => $core['tax_id'] ?? '',
			RegistrationMetaKeys::REG_NAME            => $core['name'] ?? '',
			RegistrationMetaKeys::REG_SURNAME         => $core['surname'] ?? '',
			RegistrationMetaKeys::REG_EMAIL           => $core['email'] ?? '',
			RegistrationMetaKeys::REG_PHONE           => $core['phone'] ?? '',
			RegistrationMetaKeys::REG_CENTRE          => $core['centre'] ?? '',
			RegistrationMetaKeys::REG_CENTRE_CODE     => $core['centre_code'] ?? '',
			RegistrationMetaKeys::REG_CONSENT_VERSION => (int) get_post_meta( $event_id, RegistrationMetaKeys::CONSENT_VERSION, true ),
			RegistrationMetaKeys::REG_CONSENT_AT      => current_time( 'mysql' ),



			RegistrationMetaKeys::REG_ANSWERS         => wp_slash( (string) wp_json_encode( $answers ) ),
			RegistrationMetaKeys::REG_TOKEN           => self::new_token(),
		);
		foreach ( $metas as $clave => $valor ) {
			update_post_meta( $id, $clave, $valor );
		}

		return $id;
	}






	public static function new_token(): string {
		return bin2hex( random_bytes( 20 ) );
	}







	private static function reference( int $event_id ): string {
		return sprintf( 'INS-%d-%s', $event_id, strtoupper( substr( bin2hex( random_bytes( 4 ) ), 0, 8 ) ) );
	}



















	public static function seat( int $event_id, int $registration_id, int $activity_id ): array {
		if ( $event_id <= 0 || (int) get_post_field( 'post_parent', $registration_id ) !== $event_id ) {
			return array(
				'ok'    => false,
				'error' => 'no_es_de_este_evento',
			);
		}
		if ( $activity_id > 0 && ! self::is_workshop_of( $event_id, $activity_id ) ) {
			return array(
				'ok'    => false,
				'error' => 'no_es_un_taller_del_evento',
			);
		}

		if ( ! self::lock( $event_id ) ) {
			return array(
				'ok'    => false,
				'error' => 'ocupado',
			);
		}

		try {
			$actual = (int) get_post_meta( $registration_id, RegistrationMetaKeys::REG_WORKSHOP, true );
			if ( $actual === $activity_id ) {
				return array(
					'ok'    => true,
					'error' => '',
				);
			}

			if ( $activity_id > 0 && ! self::fits( $event_id, $activity_id, $registration_id ) ) {
				return array(
					'ok'    => false,
					'error' => 'sin_plazas',
				);
			}



			update_post_meta( $registration_id, RegistrationMetaKeys::REG_WORKSHOP, $activity_id );

			return array(
				'ok'    => true,
				'error' => '',
			);
		} finally {
			self::unlock( $event_id );
		}
	}












	public static function fits( int $event_id, int $activity_id, int $registration_id = 0 ): bool {
		$aforo = (int) get_post_meta( $activity_id, ProgrammeMetaKeys::ACTIVITY_SEATS, true );
		if ( $aforo <= 0 ) {
			return true;
		}
		return self::taken( $event_id, $activity_id, $registration_id ) < $aforo;
	}









	public static function taken( int $event_id, int $activity_id, int $except = 0 ): int {
		$n = 0;
		foreach ( self::all( $event_id ) as $inscripcion ) {
			if ( (int) $inscripcion->ID === $except ) {
				continue;
			}
			if ( (int) get_post_meta( $inscripcion->ID, RegistrationMetaKeys::REG_WORKSHOP, true ) === $activity_id ) {
				++$n;
			}
		}
		return $n;
	}













	public static function choices( int $event_id, int $registration_id = 0 ): array {
		$mio = $registration_id > 0
			? (int) get_post_meta( $registration_id, RegistrationMetaKeys::REG_WORKSHOP, true )
			: 0;

		$out = array();
		foreach ( Programme::workshops( $event_id ) as $taller ) {
			$id      = (int) $taller->ID;
			$aforo   = (int) get_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_SEATS, true );
			$ocupado = self::taken( $event_id, $id );
			$suyo    = $id === $mio;

			if ( ! $suyo && $aforo > 0 && $ocupado >= $aforo ) {
				continue;
			}

			$out[] = array(
				'id'    => $id,
				'title' => (string) $taller->post_title,
				'seats' => $aforo,
				'taken' => $ocupado,
				'mine'  => $suyo,
			);
		}
		return $out;
	}








	private static function is_workshop_of( int $event_id, int $activity_id ): bool {
		foreach ( Programme::workshops( $event_id ) as $taller ) {
			if ( (int) $taller->ID === $activity_id ) {
				return true;
			}
		}
		return false;
	}


















	public static function lock( int $event_id ): bool {
		$nombre  = self::LOCK_PREFIX . $event_id;
		$esperar = 0;

		do {
			wp_cache_delete( $nombre, 'options' );
			wp_cache_delete( 'notoptions', 'options' );

			if ( add_option( $nombre, (string) time(), '', 'no' ) ) {
				return true;
			}




			$puesto = (int) get_option( $nombre, 0 );
			if ( $puesto > 0 && ( time() - $puesto ) > self::LOCK_TTL ) {
				delete_option( $nombre );
				continue;
			}

			usleep( 50000 );
			$esperar += 50000;
		} while ( $esperar < self::LOCK_WAIT );

		return false;
	}







	public static function unlock( int $event_id ): void {
		delete_option( self::LOCK_PREFIX . $event_id );
	}









	public static function validate( int $event_id, array $raw, array $answers ): array {
		$nucleo     = RegistrationInput::core( $raw, self::centres() );
		$preguntas  = self::questions( $event_id );
		$contestado = SignupQuestions::answers( $preguntas, $answers );

		return array(
			'ok'      => $nucleo['ok'] && $contestado['ok'],
			'errors'  => array_merge( $nucleo['errors'], $contestado['errors'] ),
			'core'    => $nucleo['data'],
			'answers' => $contestado['data'],
		);
	}














	public static function centres(): array {





		$centros = apply_filters( 'evt_centres', array() );
		if ( ! is_array( $centros ) ) {
			return array();
		}

		$out = array();
		if ( array_is_list( $centros ) ) {
			foreach ( $centros as $nombre ) {
				$nombre = trim( (string) $nombre );
				if ( '' !== $nombre ) {
					$out[ $nombre ] = $nombre;
				}
			}
		} else {
			foreach ( $centros as $codigo => $denominacion ) {
				$codigo       = trim( (string) $codigo );
				$denominacion = trim( (string) $denominacion );
				if ( '' !== $codigo && '' !== $denominacion ) {
					$out[ $codigo ] = $denominacion;
				}
			}
		}
		return $out;
	}
}








namespace Evt\PublicFront;

use Evt\Access\EventAccess;
use Evt\Meta\RegistrationMetaKeys;
use Evt\PostType\RegistrationPostType;


























final class RegistrationFiles {




	public const FIELD = 'evt_qf';




	public const ARG_REG = 'evt_reg';




	public const ARG_FILE = 'evt_file';




	public const DIR = 'evt-private';








	public const MAX_BYTES = 10 * MB_IN_BYTES;









	private const MODE_CLOSED = 0200;




	private const MODE_OPEN = 0400;









	private const STORED_SHAPE = '#^[a-f0-9]{2}/[a-f0-9]{2}/[a-f0-9]{32}\.[a-z0-9]{1,8}$#';






	public static function register(): void {



		add_action( 'init', array( self::class, 'handle' ), 20 );



		add_action( 'before_delete_post', array( self::class, 'on_delete' ), 10, 2 );
	}













	public static function mimes(): array {
		$mimes = array(
			'pdf'          => 'application/pdf',
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
			'docx'         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'odt'          => 'application/vnd.oasis.opendocument.text',
		);










		$mimes = apply_filters( 'evt_private_file_mimes', $mimes );
		return is_array( $mimes ) ? $mimes : array();
	}






	public static function max_bytes(): int {
		$servidor = (int) wp_max_upload_size();
		return $servidor > 0 ? min( self::MAX_BYTES, $servidor ) : self::MAX_BYTES;
	}










	public static function root(): string {
		$uploads = wp_upload_dir();
		$base    = isset( $uploads['basedir'] ) && ! $uploads['error'] ? (string) $uploads['basedir'] : '';
		$raiz    = '' === $base ? '' : $base . '/' . self::DIR;






		return rtrim( (string) apply_filters( 'evt_private_files_dir', $raiz ), '/' );
	}














	public static function submitted( array $preguntas ): array {
		$errores = array();
		$traidos = array();

		foreach ( $preguntas as $pregunta ) {
			if ( 'file' !== $pregunta['type'] ) {
				continue;
			}
			$id      = (string) $pregunta['id'];
			$fichero = self::from_request( $id );

			if ( null === $fichero ) {
				if ( ! empty( $pregunta['required'] ) ) {
					$errores[] = 'file_missing';
				}
				continue;
			}

			$porque = self::refuse( $fichero );
			if ( '' !== $porque ) {
				$errores[] = $porque;
				continue;
			}
			$traidos[ $id ] = $fichero;
		}

		return array(
			'ok'     => array() === $errores,
			'errors' => $errores,
			'files'  => $traidos,
		);
	}







	private static function from_request( string $question_id ): ?array {


		$campo = isset( $_FILES[ self::FIELD ] ) && is_array( $_FILES[ self::FIELD ] ) ? $_FILES[ self::FIELD ] : array();
		if ( ! isset( $campo['name'][ $question_id ] ) || ! is_scalar( $campo['name'][ $question_id ] ) ) {
			return null;
		}

		$nombre = sanitize_text_field( (string) $campo['name'][ $question_id ] );
		$tmp    = isset( $campo['tmp_name'][ $question_id ] ) ? (string) $campo['tmp_name'][ $question_id ] : '';
		$error  = isset( $campo['error'][ $question_id ] ) ? (int) $campo['error'][ $question_id ] : UPLOAD_ERR_NO_FILE;
		$tamano = isset( $campo['size'][ $question_id ] ) ? (int) $campo['size'][ $question_id ] : 0;


		if ( '' === $nombre && UPLOAD_ERR_NO_FILE === $error ) {
			return null;
		}










		return array(
			'name'     => basename( $nombre ),
			'tmp_name' => $tmp,
			'size'     => $tamano,
			'error'    => $error,
		);
	}












	public static function refuse( array $fichero ): string {
		if ( UPLOAD_ERR_INI_SIZE === $fichero['error'] || UPLOAD_ERR_FORM_SIZE === $fichero['error'] ) {
			return 'file_too_big';
		}
		if ( UPLOAD_ERR_OK !== $fichero['error'] || '' === $fichero['tmp_name'] ) {
			return 'file_broken';
		}
		if ( $fichero['size'] <= 0 || $fichero['size'] > self::max_bytes() ) {
			return 'file_too_big';
		}

		$mimes    = self::mimes();
		$revisado = wp_check_filetype_and_ext( $fichero['tmp_name'], $fichero['name'], $mimes );
		$tipo     = isset( $revisado['type'] ) && is_string( $revisado['type'] ) ? $revisado['type'] : '';
		$ext      = isset( $revisado['ext'] ) && is_string( $revisado['ext'] ) ? $revisado['ext'] : '';

		if ( '' === $tipo || '' === $ext || ! in_array( $tipo, array_values( $mimes ), true ) ) {
			return 'file_type';
		}
		return '';
	}















	public static function store_all( int $registration_id, array $files ): bool {
		if ( $registration_id <= 0 ) {
			return false;
		}
		if ( array() === $files ) {
			return true;
		}

		$descriptores = array();
		foreach ( $files as $question_id => $fichero ) {
			$descriptor = self::store( $fichero );
			if ( null === $descriptor ) {
				foreach ( $descriptores as $hecho ) {
					self::erase( $hecho );
				}
				return false;
			}
			$descriptores[ (string) $question_id ] = $descriptor;
		}

		update_post_meta(
			$registration_id,
			RegistrationMetaKeys::REG_FILES,
			wp_slash( (string) wp_json_encode( $descriptores ) )
		);
		return true;
	}












	private static function store( array $fichero ): ?array {
		if ( '' !== self::refuse( $fichero ) ) {
			return null;
		}

		$raiz = self::root();
		$fs   = self::filesystem();
		if ( '' === $raiz || null === $fs ) {
			return null;
		}

		$revisado = wp_check_filetype_and_ext( $fichero['tmp_name'], $fichero['name'], self::mimes() );
		$ext      = strtolower( (string) $revisado['ext'] );
		$mime     = (string) $revisado['type'];

		$bytes = $fs->get_contents( $fichero['tmp_name'] );
		if ( ! is_string( $bytes ) || '' === $bytes ) {
			return null;
		}

		$opaco  = bin2hex( random_bytes( 16 ) );
		$stored = substr( $opaco, 0, 2 ) . '/' . substr( $opaco, 2, 2 ) . '/' . $opaco . '.' . $ext;
		$camino = $raiz . '/' . $stored;

		if ( ! self::prepare_dir( dirname( $camino ) ) ) {
			return null;
		}
		if ( ! $fs->put_contents( $camino, $bytes, self::MODE_CLOSED ) ) {
			return null;
		}


		$fs->chmod( $camino, self::MODE_CLOSED );

		return array(
			'id'     => bin2hex( random_bytes( 16 ) ),
			'name'   => sanitize_file_name( $fichero['name'] ),
			'mime'   => $mime,
			'size'   => strlen( $bytes ),
			'sha256' => hash( 'sha256', $bytes ),
			'stored' => $stored,
		);
	}











	private static function prepare_dir( string $dir ): bool {
		$raiz = self::root();
		$fs   = self::filesystem();
		if ( '' === $raiz || null === $fs ) {
			return false;
		}

		if ( ! $fs->is_dir( $raiz ) && ! wp_mkdir_p( $raiz ) ) {
			return false;
		}
		if ( ! $fs->exists( $raiz . '/.htaccess' ) ) {
			$fs->put_contents(
				$raiz . '/.htaccess',
				"# Los documentos de las inscripciones no se sirven directamente (ADR-0036).\n"
					. "<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n"
					. "<IfModule !mod_authz_core.c>\n\tDeny from all\n</IfModule>\n",
				FS_CHMOD_FILE
			);
		}
		if ( ! $fs->exists( $raiz . '/index.php' ) ) {
			$fs->put_contents( $raiz . '/index.php', "<?php\n// Silence is golden.\n", FS_CHMOD_FILE );
		}

		return $fs->is_dir( $dir ) || wp_mkdir_p( $dir );
	}









	public static function descriptors( int $registration_id ): array {
		if ( $registration_id <= 0 ) {
			return array();
		}
		$crudo = get_post_meta( $registration_id, RegistrationMetaKeys::REG_FILES, true );
		$datos = is_string( $crudo ) && '' !== $crudo ? json_decode( $crudo, true ) : $crudo;
		if ( ! is_array( $datos ) ) {
			return array();
		}

		$out = array();
		foreach ( $datos as $question_id => $descriptor ) {
			if ( is_array( $descriptor ) && isset( $descriptor['id'], $descriptor['stored'] ) ) {
				$out[ (string) $question_id ] = $descriptor;
			}
		}
		return $out;
	}








	public static function find( int $registration_id, string $file_id ): ?array {
		if ( ! (bool) preg_match( '/^[a-f0-9]{32}$/', $file_id ) ) {
			return null;
		}
		foreach ( self::descriptors( $registration_id ) as $descriptor ) {
			if ( hash_equals( (string) $descriptor['id'], $file_id ) ) {
				return $descriptor;
			}
		}
		return null;
	}










	public static function read( array $descriptor ): ?string {
		$camino = self::path( $descriptor );
		$fs     = self::filesystem();
		if ( '' === $camino || null === $fs || ! $fs->exists( $camino ) ) {
			return null;
		}

		try {
			$fs->chmod( $camino, self::MODE_OPEN );
			$bytes = $fs->get_contents( $camino );
		} finally {
			$fs->chmod( $camino, self::MODE_CLOSED );
		}
		return is_string( $bytes ) ? $bytes : null;
	}











	public static function path( array $descriptor ): string {
		$stored = isset( $descriptor['stored'] ) ? (string) $descriptor['stored'] : '';
		$raiz   = self::root();
		if ( '' === $raiz || ! (bool) preg_match( self::STORED_SHAPE, $stored ) ) {
			return '';
		}
		$camino = $raiz . '/' . $stored;
		return 0 === strpos( $camino, $raiz . '/' ) ? $camino : '';
	}










	public static function on_delete( int $post_id, $post = null ): void {
		$tipo = $post instanceof \WP_Post ? (string) $post->post_type : (string) get_post_type( $post_id );
		if ( RegistrationPostType::POST_TYPE !== $tipo ) {
			return;
		}
		self::delete_all( $post_id );
	}







	public static function delete_all( int $registration_id ): void {
		foreach ( self::descriptors( $registration_id ) as $descriptor ) {
			self::erase( $descriptor );
		}
		delete_post_meta( $registration_id, RegistrationMetaKeys::REG_FILES );
	}







	private static function erase( array $descriptor ): void {
		$camino = self::path( $descriptor );
		$fs     = self::filesystem();
		if ( '' !== $camino && null !== $fs && $fs->exists( $camino ) ) {
			$fs->delete( $camino );
		}
	}














	public static function url( int $registration_id, string $file_id, string $token = '' ): string {
		$args = array(
			self::ARG_REG  => $registration_id,
			self::ARG_FILE => $file_id,
		);
		if ( '' !== $token ) {
			$args[ SignupForm::ARG_TOKEN ] = rawurlencode( $token );
		}
		return add_query_arg( $args, home_url( '/' ) );
	}












	public static function handle(): void {

		$file_id = isset( $_GET[ self::ARG_FILE ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::ARG_FILE ] ) ) : '';
		if ( '' === $file_id ) {
			return;
		}
		$registration_id = isset( $_GET[ self::ARG_REG ] ) ? absint( wp_unslash( $_GET[ self::ARG_REG ] ) ) : 0;
		$token           = isset( $_GET[ SignupForm::ARG_TOKEN ] ) ? sanitize_text_field( wp_unslash( $_GET[ SignupForm::ARG_TOKEN ] ) ) : '';


		if ( $registration_id <= 0
			|| RegistrationPostType::POST_TYPE !== (string) get_post_type( $registration_id ) ) {
			self::deny();
			return;
		}

		$event_id = (int) get_post_field( 'post_parent', $registration_id );
		if ( ! self::may_read( $registration_id, $event_id, $token ) ) {
			self::deny();
			return;
		}

		$descriptor = self::find( $registration_id, $file_id );
		$bytes      = null === $descriptor ? null : self::read( $descriptor );
		if ( null === $descriptor || null === $bytes ) {
			self::deny( 404, 'Ese documento ya no está.' );
			return;
		}

		self::send( $descriptor, $bytes );
	}

















	public static function may_read( int $registration_id, int $event_id, string $token ): bool {
		if ( $registration_id <= 0 || $event_id <= 0 ) {
			return false;
		}
		if ( '' !== $token && Registrations::by_token( $event_id, $token ) === $registration_id ) {
			return true;
		}
		return EventAccess::can_open( get_current_user_id(), $event_id );
	}












	private static function send( array $descriptor, string $bytes ): void {
		foreach ( self::headers( $descriptor, strlen( $bytes ) ) as $linea ) {
			Shell::send_header( $linea );
		}

		echo $bytes; 
		Shell::leave();
	}












	public static function headers( array $descriptor, int $bytes ): array {
		$nombre = sanitize_file_name( (string) ( $descriptor['name'] ?? '' ) );
		if ( '' === $nombre ) {
			$nombre = 'documento';
		}

		return array(
			'Content-Type: ' . sanitize_mime_type( (string) ( $descriptor['mime'] ?? '' ) ),
			'Content-Disposition: attachment; filename="' . $nombre . '"',
			'Content-Length: ' . $bytes,
			'X-Content-Type-Options: nosniff',
			'Cache-Control: private, no-store',
		);
	}








	private static function deny( int $codigo = 403, string $texto = 'No puede descargar este documento.' ): void {
		status_header( $codigo );
		Shell::send_header( 'Content-Type: text/plain; charset=utf-8' );
		Shell::send_header( 'X-Content-Type-Options: nosniff' );
		Shell::send_header( 'Cache-Control: private, no-store' );
		echo esc_html( $texto );
		Shell::leave();
	}









	public static function why( array $errors ): string {
		$textos = array(
			'file_missing' => 'Falta un documento obligatorio.',
			'file_too_big' => 'El documento es demasiado grande: el máximo son ' . size_format( self::max_bytes() ) . '.',
			'file_type'    => 'Ese tipo de documento no se admite. Se aceptan PDF, JPG, PNG, DOCX y ODT.',
			'file_broken'  => 'El documento no ha llegado completo. Vuelva a adjuntarlo.',
		);

		foreach ( $errors as $error ) {
			if ( isset( $textos[ $error ] ) ) {
				return $textos[ $error ];
			}
		}
		return '';
	}






	private static function filesystem(): ?\WP_Filesystem_Base {
		global $wp_filesystem;
		if ( ! $wp_filesystem instanceof \WP_Filesystem_Base ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}
		return $wp_filesystem instanceof \WP_Filesystem_Base ? $wp_filesystem : null;
	}
}








namespace Evt\PublicFront;

use Evt\Domain\RegistrationInput;
use Evt\Meta\RegistrationMetaKeys;


















final class SignupForm {




	public const FIELD_OP = 'evt_signup_op';

	public const OP_SIGNUP   = 'inscribir';
	public const OP_WORKSHOP = 'taller';

	public const FIELD_EVENT   = 'evt_signup_event';
	public const FIELD_TOKEN   = 'evt_token';
	public const FIELD_ANSWERS = 'evt_q';

	public const NONCE_ACTION = 'evt_signup';
	public const NONCE_FIELD  = '_evt_signup_nonce';




	public const ARG_TOKEN = 'inscripcion';






	private static $notice = null;






	public static function register(): void {
		add_action( 'init', array( self::class, 'maybe_handle_submit' ), 20 );
	}






	public static function notice(): ?array {
		return self::$notice;
	}






	public static function maybe_handle_submit(): void {

		$op = isset( $_POST[ self::FIELD_OP ] ) ? sanitize_key( wp_unslash( $_POST[ self::FIELD_OP ] ) ) : '';
		if ( self::OP_SIGNUP !== $op && self::OP_WORKSHOP !== $op ) {
			return;
		}


		$nonce = isset( $_POST[ self::NONCE_FIELD ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ) : '';
		if ( '' === $nonce || false === wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			self::$notice = array(
				'level'   => 'error',
				'message' => 'El formulario estuvo abierto demasiado tiempo y el envío caducó. Vuelva a enviarlo.',
			);
			return;
		}


		$raw      = (array) wp_unslash( $_POST );
		$event_id = isset( $raw[ self::FIELD_EVENT ] ) ? (int) $raw[ self::FIELD_EVENT ] : 0;

		if ( self::OP_SIGNUP === $op ) {
			self::signup( $event_id, $raw );
			return;
		}
		self::workshop( $event_id, $raw );
	}








	private static function signup( int $event_id, array $raw ): void {
		if ( ! self::is_open( $event_id ) ) {
			self::$notice = array(
				'level'   => 'error',
				'message' => 'La inscripción de este evento no está abierta.',
			);
			return;
		}

		$respuestas = isset( $raw[ self::FIELD_ANSWERS ] ) && is_array( $raw[ self::FIELD_ANSWERS ] )
			? $raw[ self::FIELD_ANSWERS ]
			: array();

		$v = Registrations::validate( $event_id, $raw, $respuestas );




		$ficheros = RegistrationFiles::submitted( Registrations::questions( $event_id ) );

		if ( ! $v['ok'] || ! $ficheros['ok'] ) {
			$porque       = RegistrationFiles::why( $ficheros['errors'] );
			self::$notice = array(
				'level'   => 'error',
				'message' => '' !== $porque && $v['ok'] ? $porque : RegistrationInput::why( $v['errors'] ),
			);
			return;
		}

		$id = Registrations::create( $event_id, $v['core'], $v['answers'] );
		if ( $id <= 0 ) {
			self::$notice = array(
				'level'   => 'error',
				'message' => 'No se ha podido guardar la inscripción. Vuelva a intentarlo.',
			);
			return;
		}





		if ( ! RegistrationFiles::store_all( $id, $ficheros['files'] ) ) {
			wp_delete_post( $id, true );
			self::$notice = array(
				'level'   => 'error',
				'message' => 'No se ha podido guardar el documento que adjuntó, así que la inscripción no se ha registrado. Vuelva a intentarlo.',
			);
			return;
		}



		$taller = isset( $raw['evt_workshop'] ) ? (int) $raw['evt_workshop'] : 0;
		if ( $taller > 0 && self::workshops_open( $event_id ) ) {
			Registrations::seat( $event_id, $id, $taller );
		}

		Shell::leave( self::link( $event_id, (string) get_post_meta( $id, RegistrationMetaKeys::REG_TOKEN, true ) ) );
	}








	private static function workshop( int $event_id, array $raw ): void {
		$token = isset( $raw[ self::FIELD_TOKEN ] ) ? (string) $raw[ self::FIELD_TOKEN ] : '';
		$id    = Registrations::by_token( $event_id, $token );

		if ( $id <= 0 ) {
			self::$notice = array(
				'level'   => 'error',
				'message' => 'Este enlace ya no sirve. Pida uno nuevo desde el correo de confirmación.',
			);
			return;
		}
		if ( ! self::workshops_open( $event_id ) ) {
			self::$notice = array(
				'level'   => 'error',
				'message' => 'El plazo para elegir taller está cerrado.',
			);
			return;
		}

		$res = Registrations::seat( $event_id, $id, isset( $raw['evt_workshop'] ) ? (int) $raw['evt_workshop'] : 0 );
		if ( ! $res['ok'] ) {
			self::$notice = array(
				'level'   => 'error',
				'message' => self::why_no_seat( $res['error'] ),
			);
			return;
		}

		Shell::leave( self::link( $event_id, $token ) );
	}







	public static function why_no_seat( string $error ): string {
		$textos = array(
			'sin_plazas' => 'Ese taller se ha llenado mientras rellenaba la página. Elija otro de la lista, que está al día.',
			'ocupado'    => 'Hay varias personas eligiendo taller a la vez. Vuelva a intentarlo en unos segundos.',
		);
		return $textos[ $error ] ?? 'No se ha podido guardar la elección.';
	}








	public static function link( int $event_id, string $token ): string {
		$pagina = self::page( $event_id );
		$url    = $pagina > 0 ? (string) get_permalink( $pagina ) : (string) get_permalink( $event_id );
		return '' === $token ? $url : add_query_arg( self::ARG_TOKEN, rawurlencode( $token ), $url );
	}







	public static function page( int $event_id ): int {
		$hijas = get_posts(
			array(
				'post_type'        => \Evt\PostType\EventPostType::POST_TYPE,
				'post_parent'      => $event_id,
				'post_status'      => 'publish',
				'numberposts'      => -1,
				'meta_key'         => \Evt\Meta\EventMetaKeys::SECTION_TYPE, 
				'meta_value'       => 'inscripcion', 
				'suppress_filters' => false,
			)
		);
		return is_array( $hijas ) && array() !== $hijas ? (int) $hijas[0]->ID : 0;
	}







	public static function current( int $event_id ): int {

		$token = isset( $_GET[ self::ARG_TOKEN ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::ARG_TOKEN ] ) ) : '';
		return '' === $token ? 0 : Registrations::by_token( $event_id, $token );
	}







	public static function is_open( int $event_id ): bool {
		return (bool) get_post_meta( $event_id, RegistrationMetaKeys::SIGNUP_OPEN, true );
	}











	public static function workshops_open( int $event_id ): bool {
		if ( ! get_post_meta( $event_id, RegistrationMetaKeys::WORKSHOP_OPEN, true ) ) {
			return false;
		}

		$hoy   = current_time( 'Y-m-d' );
		$desde = (string) get_post_meta( $event_id, RegistrationMetaKeys::WORKSHOP_START, true );
		$hasta = (string) get_post_meta( $event_id, RegistrationMetaKeys::WORKSHOP_END, true );

		return ( '' === $desde || $hoy >= $desde ) && ( '' === $hasta || $hoy <= $hasta );
	}
}








namespace Evt\PublicFront;

use Evt\Access\EventAccess;
use Evt\Domain\ActivityInput;
use Evt\Domain\EventInput;
use Evt\Domain\EventState;
use Evt\Domain\SignupQuestions;
use Evt\Meta\EventMetaKeys;
use Evt\Meta\ProgrammeMetaKeys;
use Evt\Meta\RegistrationMetaKeys;
use Evt\PostType\ActivityPostType;
use Evt\PostType\EventPostType;
use Evt\PostType\SpeakerPostType;
use Evt\PublicFront\View\EventWorkspaceView;
use Evt\Taxonomy\EventTaxonomies;
















final class EventWorkspace {

	public const SHORTCODE = 'evt_event_workspace';




	public const ARG_EVENT = 'evento';




	public const ARG_PANEL = 'panel';




	public const ARG_SECTION = 'seccion';




	public const ARG_TYPE = 'tipo';




	public const ARG_TRASH = 'papelera';




	public const PANEL_SECTIONS = 'secciones';




	public const PANEL_SETTINGS = 'ajustes';




	public const PANEL_LOOK = 'apariencia';




	public const PANEL_SPEAKERS = 'ponentes';




	public const PANEL_PROGRAMME = 'programa';




	public const PANEL_WORKSHOPS = 'talleres';




	public const PANEL_PEOPLE = 'participantes';




	public const PANEL_SIGNUP = 'inscripcion';




	public const ARG_ROW = 'ficha';




	public const ARG_Q = 'busca';




	public const ARG_WORKSHOP = 'taller';








	public const PANEL_CODE = 'codigo';




	public const FIELD_ROW = 'evt_row';




	public const FIELD_DO = 'evt_do';




	public const FIELD_EVENT = 'evt_event';




	public const FIELD_SECTION = 'evt_section';




	public const FIELD_TITLE = 'evt_title';




	public const FIELD_AREA = 'evt_area_term';




	public const FIELD_TYPE = 'evt_type_term';




	public const FIELD_COURSE = 'evt_course_term';




	public const OP_ARCHIVE = 'archive';




	public const OP_UNARCHIVE = 'unarchive';






	private const ROW_OPS = array( 'up', 'down', 'publish', 'unpublish', 'delete', 'restore' );




	public const OP_SPEAKER = 'speaker';




	public const OP_ACTIVITY = 'activity';




	public const OP_EXPORT = 'export';









	private const PROGRAMME_OPS = array( 'sp_up', 'sp_down', 'row_delete', 'row_restore' );






	private const OPS = array(
		self::PANEL_SETTINGS,
		self::PANEL_SIGNUP,
		self::PANEL_LOOK,
		self::PANEL_CODE,
		self::OP_ARCHIVE,
		self::OP_UNARCHIVE,
		'up',
		'down',
		'publish',
		'unpublish',
		'delete',
		'restore',
		self::OP_SPEAKER,
		self::OP_ACTIVITY,
		self::OP_EXPORT,
		'sp_up',
		'sp_down',
		'row_delete',
		'row_restore',
	);






	private const ROW_DONE = array(
		'up'        => 'Cambiado el orden de las secciones.',
		'down'      => 'Cambiado el orden de las secciones.',
		'delete'    => 'Sección enviada a la papelera. Nada se ha perdido: está en «Papelera» y se restaura desde ahí.',
		'restore'   => 'Sección restaurada, en borrador: revísela y publíquela cuando esté lista.',
		'publish'   => 'Sección publicada: ya se ve en el menú del evento.',
		'unpublish' => 'Sección despublicada: vuelve a borrador y desaparece del menú.',
	);






	private const IMAGE_MIMES = array(
		'jpg|jpeg|jpe' => 'image/jpeg',
		'png'          => 'image/png',
		'webp'         => 'image/webp',
		'gif'          => 'image/gif',
	);






	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'render' ) );


		add_action( 'init', array( self::class, 'handle' ), 20 );



		add_action( 'init', array( EditLock::class, 'handle' ), 20 );



		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_code_editor' ), 20 );
	}






	public static function enqueue_code_editor(): void {

		if ( 'event' !== Shell::current_section()
			|| self::PANEL_CODE !== sanitize_key( wp_unslash( (string) ( $_GET[ self::ARG_PANEL ] ?? '' ) ) ) ) {
			return;
		}
		$event_id = absint( wp_unslash( $_GET[ self::ARG_EVENT ] ?? 0 ) );


		$user_id = get_current_user_id();
		if ( EventAccess::can_edit_custom_css( $user_id, $event_id ) ) {
			CodeEditor::enqueue( CodeEditor::MODE_CSS );
		}
		if ( EventAccess::can_edit_custom_js( $user_id, $event_id ) ) {
			CodeEditor::enqueue( CodeEditor::MODE_JS );
		}
	}















	public static function url( int $event_id, string $panel = '', array $args = array() ): string {
		if ( $event_id <= 0 ) {
			return '';
		}
		$args[ self::ARG_EVENT ] = $event_id;
		if ( '' !== $panel ) {
			$args[ self::ARG_PANEL ] = $panel;
		}
		return Shell::url( 'event', $args );
	}













	public static function panels( int $event_id, int $user_id = 0 ): array {
		$rotulos = array(
			self::PANEL_SECTIONS  => 'Páginas',
			self::PANEL_SPEAKERS  => 'Ponentes',
			self::PANEL_PROGRAMME => 'Programa',
			self::PANEL_WORKSHOPS => 'Talleres',
			self::PANEL_SIGNUP    => 'Inscripción',
			self::PANEL_PEOPLE    => 'Participantes',
			self::PANEL_SETTINGS  => 'Ajustes',
			self::PANEL_LOOK      => 'Apariencia',
		);
		if ( self::may_edit_code( $user_id, $event_id ) ) {
			$rotulos[ self::PANEL_CODE ] = 'Código';
		}





		$cuentas = self::counts( $event_id );

		$out = array();
		foreach ( $rotulos as $slug => $rotulo ) {
			$out[ $slug ] = array(
				'label' => $rotulo,
				'url'   => self::url( $event_id, $slug ),
				'count' => $cuentas[ $slug ] ?? null,
			);
		}
		return $out;
	}







	private static function counts( int $event_id ): array {
		if ( $event_id <= 0 ) {
			return array();
		}
		return array(
			self::PANEL_SECTIONS  => count( self::children( $event_id ) ),
			self::PANEL_SPEAKERS  => count( Programme::speakers( $event_id ) ),
			self::PANEL_PROGRAMME => count( Programme::activities( $event_id ) ),
			self::PANEL_WORKSHOPS => count( Programme::workshops( $event_id ) ),
			self::PANEL_SIGNUP    => count( Registrations::questions( $event_id ) ),
			self::PANEL_PEOPLE    => count( Participants::rows( $event_id ) ),
		);
	}












	public static function nonce_name( string $op, int $section_id = 0 ): string {
		return 'evt_nonce_' . $op . '_' . $section_id;
	}







	public static function nonce_action( string $op ): string {
		return 'evt_ws_' . $op;
	}















	public static function handle(): void {

		$op = sanitize_key( wp_unslash( (string) ( $_POST[ self::FIELD_DO ] ?? '' ) ) );
		if ( ! in_array( $op, self::OPS, true ) ) {
			return;
		}
		$event_id   = absint( wp_unslash( $_POST[ self::FIELD_EVENT ] ?? 0 ) );
		$section_id = absint( wp_unslash( $_POST[ self::FIELD_SECTION ] ?? 0 ) );
		$row_id     = absint( wp_unslash( $_POST[ self::FIELD_ROW ] ?? 0 ) );


		$fila     = in_array( $op, self::ROW_OPS, true );
		$programa = in_array( $op, self::PROGRAMME_OPS, true );


		$nonce_row = 0;
		if ( $fila ) {
			$nonce_row = $section_id;
		} elseif ( $programa ) {
			$nonce_row = $row_id;
		}
		if ( ! self::verify( $op, $nonce_row ) ) {
			return;
		}

		$user_id = get_current_user_id();





		if ( self::PANEL_SETTINGS === $op && $event_id <= 0 ) {
			self::create_event( $user_id );
			return;
		}

		$destino = self::url( $event_id, self::panel_of( $op ) );
		if ( '' === $destino ) {
			$destino = Shell::back_url();
		}






		EditLock::require_available( $event_id );






		if ( self::OP_ARCHIVE === $op || self::OP_UNARCHIVE === $op ) {
			self::save_archived( $op, $event_id, $user_id, $destino );
			return;
		}

		if ( ! EventAccess::can_edit( $user_id, $event_id ) ) {
			self::set_flash( 'error', EventAccess::why_not_editable( $user_id, $event_id ) );
			Shell::leave( $destino );
			return;
		}

		if ( $fila ) {
			self::run_row( $op, $event_id, $section_id, $user_id, $destino );
			return;
		}
		if ( $programa ) {
			self::run_programme_row( $op, $event_id, $row_id, $destino );
			return;
		}
		if ( self::OP_SPEAKER === $op ) {
			self::save_speaker( $event_id, $row_id, $destino );
			return;
		}
		if ( self::OP_ACTIVITY === $op ) {
			self::save_activity( $event_id, $row_id, $destino );
			return;
		}
		if ( self::OP_EXPORT === $op ) {
			self::export_participants( $event_id );
			return;
		}
		if ( self::PANEL_SETTINGS === $op ) {
			self::save_data( $event_id, $user_id, $destino );
			return;
		}
		if ( self::PANEL_SIGNUP === $op ) {
			self::save_signup( $event_id, $destino );
			return;
		}
		if ( self::PANEL_CODE === $op ) {
			self::save_code( $event_id, $user_id, $destino );
			return;
		}
		self::save_look( $event_id, $destino );
	}







	private static function panel_of( string $op ): string {
		if ( in_array( $op, self::ROW_OPS, true ) ) {
			return self::PANEL_SECTIONS;
		}
		if ( self::OP_ARCHIVE === $op || self::OP_UNARCHIVE === $op ) {
			return self::PANEL_SETTINGS;
		}
		if ( self::OP_SPEAKER === $op || 'sp_up' === $op || 'sp_down' === $op ) {
			return self::PANEL_SPEAKERS;
		}
		if ( self::OP_ACTIVITY === $op ) {
			return self::PANEL_PROGRAMME;
		}
		if ( self::OP_EXPORT === $op ) {
			return self::PANEL_PEOPLE;
		}


		if ( 'row_delete' === $op || 'row_restore' === $op ) {
			return self::asked_panel();
		}
		return $op;
	}






	private static function asked_panel(): string {

		$panel = sanitize_key( wp_unslash( (string) ( $_POST[ self::ARG_PANEL ] ?? '' ) ) );
		return in_array( $panel, array( self::PANEL_SPEAKERS, self::PANEL_PROGRAMME, self::PANEL_WORKSHOPS ), true )
			? $panel
			: self::PANEL_SPEAKERS;
	}













	private static function save_archived( string $op, int $event_id, int $user_id, string $destino ): void {
		$marcar = self::OP_ARCHIVE === $op;
		$puede  = $marcar
			? EventAccess::can_archive( $user_id, $event_id )
			: EventAccess::can_unarchive( $user_id );
		if ( ! $puede ) {
			self::set_flash(
				'error',
				$marcar
					? 'Este evento no es suyo: solo lo marca como histórico el área que lo organiza.'
					: 'Volver a abrir un evento histórico solo lo hace quien administra el aplicativo.'
			);
			Shell::leave( $destino );
			return;
		}
		if ( EventPostType::POST_TYPE !== get_post_type( $event_id )
			|| (int) get_post_field( 'post_parent', $event_id ) > 0 ) {
			self::set_flash( 'error', 'Eso no es un evento: la marca de histórico se pone en el evento entero, no en una de sus páginas.' );
			Shell::leave( $destino );
			return;
		}

		update_post_meta( $event_id, EventMetaKeys::ARCHIVED, $marcar );




		if ( $marcar ) {
			EditLock::release( $event_id );
		}

		self::set_flash(
			'ok',
			$marcar
				? 'Evento marcado como histórico. Ya no se edita, ni él ni sus secciones; la página pública se sigue viendo igual. Para volver a abrirlo hay que pedírselo a quien administre el aplicativo.'
				: 'Evento desmarcado: su área vuelve a poder editarlo.'
		);
		Shell::leave( $destino );
	}








	private static function verify( string $op, int $section_id ): bool {
		$campo = self::nonce_name( $op, $section_id );

		$valor = sanitize_text_field( wp_unslash( (string) ( $_POST[ $campo ] ?? '' ) ) );
		return false !== wp_verify_nonce( $valor, self::nonce_action( $op ) );
	}











	private static function run_row( string $op, int $event_id, int $section_id, int $user_id, string $destino ): void {
		if ( EventPostType::POST_TYPE !== get_post_type( $section_id )
			|| (int) get_post_field( 'post_parent', $section_id ) !== $event_id ) {
			self::set_flash( 'error', 'Esa sección no es de este evento.' );
			Shell::leave( $destino );
			return;
		}

		if ( 'delete' === $op ) {



			wp_trash_post( $section_id );
		} elseif ( 'restore' === $op ) {





			wp_untrash_post( $section_id );
		} elseif ( 'up' === $op || 'down' === $op ) {
			self::reorder( $event_id, $section_id, 'up' === $op ? -1 : 1 );
		} elseif ( ! EventAccess::can_publish( $user_id, $event_id ) ) {
			self::set_flash( 'error', 'Su perfil no publica eventos: puede editar la sección, pero no publicarla.' );
			Shell::leave( $destino );
			return;
		} else {
			wp_update_post(
				array(
					'ID'          => $section_id,
					'post_status' => 'publish' === $op ? 'publish' : 'draft',
				)
			);
		}

		self::set_flash( 'ok', self::ROW_DONE[ $op ] );
		Shell::leave( $destino );
	}














	private static function reorder( int $event_id, int $section_id, int $delta ): void {
		$ids   = array_map( 'intval', wp_list_pluck( self::children( $event_id ), 'ID' ) );
		$desde = array_search( $section_id, $ids, true );
		if ( false === $desde ) {
			return;
		}
		$hasta = (int) $desde + $delta;
		if ( $hasta < 0 || $hasta >= count( $ids ) ) {
			return;
		}

		$movida        = $ids[ $desde ];
		$ids[ $desde ] = $ids[ $hasta ];
		$ids[ $hasta ] = $movida;

		foreach ( $ids as $posicion => $id ) {
			wp_update_post(
				array(
					'ID'         => $id,
					'menu_order' => ( $posicion + 1 ) * 10,
				)
			);
		}
	}










	private static function run_programme_row( string $op, int $event_id, int $row_id, string $destino ): void {
		$hecho = false;
		if ( 'sp_up' === $op || 'sp_down' === $op ) {
			$hecho = Programme::reorder_speaker( $event_id, $row_id, 'sp_up' === $op ? -1 : 1 );
		} elseif ( 'row_delete' === $op ) {
			$hecho = Programme::trash( $event_id, $row_id );
		} elseif ( 'row_restore' === $op ) {
			$hecho = Programme::restore( $event_id, $row_id );
		}

		if ( ! $hecho ) {
			self::set_flash( 'error', 'Esa ficha no es de este evento, o ya no está donde se esperaba.' );
			Shell::leave( $destino );
			return;
		}

		$dichos = array(
			'sp_up'       => 'Cambiado el orden de los ponentes.',
			'sp_down'     => 'Cambiado el orden de los ponentes.',
			'row_delete'  => 'Ficha enviada a la papelera. Nada se ha perdido: está en «Papelera» y se restaura desde ahí.',
			'row_restore' => 'Ficha restaurada.',
		);
		self::set_flash( 'ok', $dichos[ $op ] );
		Shell::leave( $destino );
	}









	private static function save_speaker( int $event_id, int $row_id, string $destino ): void {
		$check = ActivityInput::speaker(
			array(
				'name' => self::field( 'evt_sp_name' ),
				'role' => self::field( 'evt_sp_role' ),
				'org'  => self::field( 'evt_sp_org' ),
				'bio'  => self::field( 'evt_sp_bio' ),
			)
		);
		if ( true !== $check['ok'] ) {
			self::set_flash( 'error', ActivityInput::why( (array) $check['errors'] ) );
			Shell::leave( $destino );
			return;
		}

		$id = Programme::save_speaker( $event_id, $row_id, (array) $check['data'] );
		if ( $id <= 0 ) {
			self::set_flash( 'error', 'No se ha podido guardar el ponente: esa ficha no es de este evento.' );
			Shell::leave( $destino );
			return;
		}

		self::save_photo( $id );
		self::set_flash( 'ok', $row_id > 0 ? 'Ponente actualizado.' : 'Ponente añadido.' );
		Shell::leave( $destino );
	}









	private static function save_activity( int $event_id, int $row_id, string $destino ): void {

		$ponentes = isset( $_POST['evt_ac_speakers'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['evt_ac_speakers'] ) ) : array();

		$check = ActivityInput::activity(
			array(
				'title'    => self::field( 'evt_ac_title' ),
				'kind'     => self::field( 'evt_ac_kind' ),
				'date'     => self::field( 'evt_ac_date' ),
				'start'    => self::field( 'evt_ac_start' ),
				'end'      => self::field( 'evt_ac_end' ),
				'venue'    => self::field( 'evt_ac_venue' ),
				'room'     => self::field( 'evt_ac_room' ),
				'seats'    => self::field( 'evt_ac_seats' ),
				'summary'  => self::field( 'evt_ac_summary' ),
				'speakers' => $ponentes,
			)
		);
		if ( true !== $check['ok'] ) {
			self::set_flash( 'error', ActivityInput::why( (array) $check['errors'] ) );
			Shell::leave( $destino );
			return;
		}

		$id = Programme::save_activity( $event_id, $row_id, (array) $check['data'] );
		if ( $id <= 0 ) {
			self::set_flash( 'error', 'No se ha podido guardar la actividad: esa ficha no es de este evento.' );
			Shell::leave( $destino );
			return;
		}

		self::set_flash( 'ok', $row_id > 0 ? 'Actividad actualizada.' : 'Actividad añadida al programa.' );
		Shell::leave( $destino );
	}










	private static function save_photo( int $speaker_id ): void {
		$pedido = self::field( 'evt_sp_photo' );
		if ( '' === $pedido ) {
			return;
		}
		if ( '0' === $pedido ) {
			delete_post_thumbnail( $speaker_id );
			return;
		}
		$attachment_id = absint( $pedido );
		if ( $attachment_id > 0 && self::is_image_attachment( $attachment_id ) ) {
			set_post_thumbnail( $speaker_id, $attachment_id );
		}
	}











	private static function export_participants( int $event_id ): void {
		$filas = Participants::filter(
			Participants::rows( $event_id ),
			self::field( self::ARG_Q ),
			self::field( self::ARG_WORKSHOP )
		);

		$cuerpo = Participants::csv( $filas );
		$nombre = Participants::filename( (string) get_post_field( 'post_title', $event_id ) );


		if ( ! headers_sent() ) {
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="' . $nombre . '"' );
			header( 'Content-Length: ' . strlen( $cuerpo ) );
			header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		}
		echo $cuerpo; 

		Shell::leave();
	}

















	private static function create_event( int $user_id ): void {
		$volver = Shell::url( 'events' );

		if ( ! user_can( $user_id, 'edit_evt_events' ) ) {
			self::set_flash( 'error', 'Su perfil no puede crear eventos. Pídalo a quien administre el aplicativo.' );
			Shell::leave( $volver );
			return;
		}

		$crudo = array(
			'title'      => self::field( self::FIELD_TITLE ),
			'start_date' => self::field( EventMetaKeys::START_DATE ),
			'end_date'   => self::field( EventMetaKeys::END_DATE ),
			'venue'      => self::field( EventMetaKeys::VENUE ),
			'parent'     => 0,
		);

		$revisado = EventInput::validate( $crudo );
		if ( ! $revisado['ok'] ) {



			self::set_flash( 'error', self::why( $revisado['errors'] ), self::submitted_values( $crudo ) );
			Shell::leave( self::url( 0, self::PANEL_SETTINGS ) );
			return;
		}

		$id = wp_insert_post(
			array(
				'post_type'   => EventPostType::POST_TYPE,
				'post_title'  => $revisado['data']['title'],
				'post_status' => 'draft',
				'post_parent' => 0,
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			self::set_flash( 'error', 'No se ha podido crear el evento: ' . $id->get_error_message() );
			Shell::leave( $volver );
			return;
		}

		$id      = (int) $id;
		$valores = self::submitted_values( $crudo );
		self::save_meta( $id, $valores, $revisado['data'] );
		self::save_terms( $id, $user_id, $valores );
		EventAccess::stamp_area( $id );

		self::set_flash( 'ok', 'Evento creado, en borrador. Añada sus páginas, sus ponentes y su programa, y publíquelo cuando esté listo.' );
		Shell::leave( self::url( $id, self::PANEL_SECTIONS ) );
	}







	private static function submitted_values( array $crudo ): array {

		$intro = wp_kses_post( wp_unslash( (string) ( $_POST[ EventMetaKeys::INTRO ] ?? '' ) ) );
		$show  = empty( $_POST[ EventMetaKeys::SIGNUP_SHOW ] ) ? '' : '1';


		return array(
			self::FIELD_TITLE             => (string) $crudo['title'],
			self::FIELD_AREA              => (string) (int) self::field( self::FIELD_AREA ),
			self::FIELD_TYPE              => (string) (int) self::field( self::FIELD_TYPE ),
			self::FIELD_COURSE            => (string) (int) self::field( self::FIELD_COURSE ),
			EventMetaKeys::TAGLINE        => self::field( EventMetaKeys::TAGLINE ),
			EventMetaKeys::HASHTAG        => self::field( EventMetaKeys::HASHTAG ),
			EventMetaKeys::INTRO          => $intro,
			EventMetaKeys::START_DATE     => (string) $crudo['start_date'],
			EventMetaKeys::END_DATE       => (string) $crudo['end_date'],
			EventMetaKeys::VENUE          => (string) $crudo['venue'],
			EventMetaKeys::SIGNUP_SHOW    => $show,
			EventMetaKeys::SIGNUP_LABEL   => self::field( EventMetaKeys::SIGNUP_LABEL ),
			EventMetaKeys::SIGNUP_URL     => self::field( EventMetaKeys::SIGNUP_URL ),
			EventMetaKeys::SIGNUP_FORM_ID => (string) max( 0, (int) self::field( EventMetaKeys::SIGNUP_FORM_ID ) ),
		);
	}









	private static function save_data( int $event_id, int $user_id, string $destino ): void {
		$crudo = array(
			'title'      => self::field( self::FIELD_TITLE ),
			'start_date' => self::field( EventMetaKeys::START_DATE ),
			'end_date'   => self::field( EventMetaKeys::END_DATE ),
			'venue'      => self::field( EventMetaKeys::VENUE ),
			'parent'     => 0,
		);

		$valores  = self::submitted_values( $crudo );
		$revisado = EventInput::validate( $crudo );
		if ( ! $revisado['ok'] ) {



			self::set_flash( 'error', self::why( $revisado['errors'] ), $valores );
			Shell::leave( $destino );
			return;
		}

		wp_update_post(
			array(
				'ID'         => $event_id,
				'post_title' => $revisado['data']['title'],
			)
		);
		self::save_meta( $event_id, $valores, $revisado['data'] );
		self::save_terms( $event_id, $user_id, $valores );

		self::set_flash( 'ok', 'Datos del evento guardados.' );
		Shell::leave( $destino );
	}












	private static function save_meta( int $event_id, array $valores, array $limpio ): void {
		$meta = array(
			EventMetaKeys::TAGLINE        => $valores[ EventMetaKeys::TAGLINE ],
			EventMetaKeys::HASHTAG        => $valores[ EventMetaKeys::HASHTAG ],
			EventMetaKeys::INTRO          => $valores[ EventMetaKeys::INTRO ],
			EventMetaKeys::START_DATE     => (string) $limpio['start_date'],
			EventMetaKeys::END_DATE       => (string) $limpio['end_date'],
			EventMetaKeys::VENUE          => (string) $limpio['venue'],
			EventMetaKeys::SIGNUP_SHOW    => $valores[ EventMetaKeys::SIGNUP_SHOW ],
			EventMetaKeys::SIGNUP_LABEL   => $valores[ EventMetaKeys::SIGNUP_LABEL ],
			EventMetaKeys::SIGNUP_URL     => $valores[ EventMetaKeys::SIGNUP_URL ],
			EventMetaKeys::SIGNUP_FORM_ID => (int) $valores[ EventMetaKeys::SIGNUP_FORM_ID ],
		);
		foreach ( $meta as $clave => $valor ) {
			update_post_meta( $event_id, $clave, $valor );
		}
	}









	private static function save_terms( int $event_id, int $user_id, array $valores ): void {
		$area_id = (int) $valores[ self::FIELD_AREA ];
		$mapa    = array(
			EventTaxonomies::TYPE   => (int) $valores[ self::FIELD_TYPE ],
			EventTaxonomies::COURSE => (int) $valores[ self::FIELD_COURSE ],
		);
		if ( self::may_set_area( $user_id, $area_id ) ) {
			$mapa[ EventTaxonomies::AREA ] = $area_id;
		}
		foreach ( $mapa as $taxonomia => $term_id ) {
			wp_set_object_terms( $event_id, $term_id > 0 ? array( $term_id ) : array(), $taxonomia, false );
		}
	}












	public static function may_set_area( int $user_id, int $area_id ): bool {
		return EventAccess::can_edit_all_areas( $user_id )
			|| ( $area_id > 0 && in_array( $area_id, EventAccess::user_areas( $user_id ), true ) );
	}














	public static function may_edit_code( int $user_id, int $event_id ): bool {
		return EventAccess::can_edit_custom_css( $user_id, $event_id )
			|| EventAccess::can_edit_custom_js( $user_id, $event_id );
	}





















	private static function save_code( int $event_id, int $user_id, string $destino ): void {
		$permitido = array(
			EventMetaKeys::CUSTOM_CSS => EventAccess::can_edit_custom_css( $user_id, $event_id ),
			EventMetaKeys::CUSTOM_JS  => EventAccess::can_edit_custom_js( $user_id, $event_id ),
		);
		$nombres   = array(
			EventMetaKeys::CUSTOM_CSS => 'el CSS',
			EventMetaKeys::CUSTOM_JS  => 'el JavaScript',
		);

		if ( ! in_array( true, $permitido, true ) ) {
			self::set_flash( 'error', 'Su perfil no puede editar el código a medida de este evento.' );
			Shell::leave( $destino );
			return;
		}

		$ignorados = array();
		foreach ( $permitido as $clave => $puede ) {

			if ( ! isset( $_POST[ $clave ] ) ) {

				continue;
			}
			if ( ! $puede ) {
				$ignorados[] = $nombres[ $clave ];
				continue;
			}
			update_post_meta( $event_id, $clave, self::raw( $clave ) );
		}

		if ( array() !== $ignorados ) {
			self::set_flash(
				'aviso',
				'Se guardó lo que su perfil puede cambiar. Se ignoró ' . implode( ' y ', $ignorados )
					. ': su perfil no tiene esa capacidad, así que se quedó exactamente como estaba.'
			);
			Shell::leave( $destino );
			return;
		}

		self::set_flash( 'ok', 'Código del evento guardado. Recargue una página del evento para verlo aplicado.' );
		Shell::leave( $destino );
	}







	private static function raw( string $nombre ): string {

		$valor = wp_unslash( $_POST[ $nombre ] ?? '' );
		return is_string( $valor ) ? $valor : '';
	}








	private static function save_look( int $event_id, string $destino ): void {
		$listas = array(
			EventMetaKeys::TITLE_FONT  => array( EventMetaKeys::fonts(), EventMetaKeys::FONT_DEFAULT ),
			EventMetaKeys::BODY_FONT   => array( EventMetaKeys::fonts(), EventMetaKeys::FONT_DEFAULT ),
			EventMetaKeys::IMAGE_SHAPE => array( EventMetaKeys::image_shapes(), EventMetaKeys::SHAPE_SQUARE ),
			EventMetaKeys::SEPARATOR   => array( EventMetaKeys::separators(), '' ),
		);

		update_post_meta( $event_id, EventMetaKeys::HEADER_BG, self::field( EventMetaKeys::HEADER_BG ) );
		update_post_meta( $event_id, EventMetaKeys::HEADER_TEXT, self::field( EventMetaKeys::HEADER_TEXT ) );
		foreach ( $listas as $clave => $lista ) {
			update_post_meta( $event_id, $clave, EventMetaKeys::in_list( self::field( $clave ), $lista[0], $lista[1] ) );
		}



		$subidas = array(
			self::save_image( $event_id, 'evt_logo', EventMetaKeys::LOGO_ID ),
			self::save_image( $event_id, 'evt_header_banner', EventMetaKeys::HEADER_BANNER_ID, 1920 ),
			self::save_image( $event_id, 'evt_poster', EventMetaKeys::POSTER_ID ),
			self::save_image( $event_id, 'evt_featured', '' ),
		);

		if ( in_array( false, $subidas, true ) ) {
			self::set_flash( 'aviso', 'Se guardó la apariencia, pero alguna imagen no se pudo cambiar y se quedó como estaba. Revise que sea una imagen de la biblioteca —JPG, PNG, WEBP o GIF—, que no pese demasiado y, si es el banner, que tenga al menos 1920 píxeles de ancho.' );
			Shell::leave( $destino );
			return;
		}
		self::set_flash( 'ok', 'Apariencia del evento guardada.' );
		Shell::leave( $destino );
	}


















	private static function save_image( int $event_id, string $campo, string $meta_key, int $min_width = 0 ): bool {


		if ( ! empty( $_FILES[ $campo . '_file' ]['name'] ) ) {
			$subido = self::upload( $campo . '_file', $event_id );
			if ( $subido <= 0 ) {
				return false;
			}
			return self::validate_and_put_image( $event_id, $meta_key, $subido, $min_width );
		}

		if ( ! empty( $_POST[ $campo . '_clear' ] ) ) {
			self::put_image( $event_id, $meta_key, 0 );
			return true;
		}

		if ( ! isset( $_POST[ $campo . '_id' ] ) ) {
			return true;
		}
		$elegido = absint( wp_unslash( $_POST[ $campo . '_id' ] ) );


		return self::validate_and_put_image( $event_id, $meta_key, $elegido, $min_width );
	}










	private static function validate_and_put_image( int $event_id, string $meta_key, int $attachment_id, int $min_width ): bool {
		if ( $attachment_id > 0 && ! self::is_image_attachment( $attachment_id ) ) {
			return false;
		}
		if ( $attachment_id > 0 && ! self::image_meets_min_width( $attachment_id, $min_width ) ) {
			return false;
		}

		self::put_image( $event_id, $meta_key, $attachment_id );
		return true;
	}













	private static function put_image( int $event_id, string $meta_key, int $attachment_id ): void {
		if ( '' === $meta_key ) {
			delete_post_thumbnail( $event_id );
			set_post_thumbnail( $event_id, $attachment_id );
			return;
		}

		delete_post_meta( $event_id, $meta_key );
		if ( $attachment_id > 0 ) {
			update_post_meta( $event_id, $meta_key, $attachment_id );
		}
	}












	private static function is_image_attachment( int $attachment_id ): bool {
		return 'attachment' === get_post_type( $attachment_id )
			&& wp_attachment_is_image( $attachment_id )
			&& current_user_can( 'read_post', $attachment_id );
	}








	private static function image_meets_min_width( int $attachment_id, int $min_width ): bool {
		if ( $min_width <= 0 ) {
			return true;
		}
		$imagen = wp_get_attachment_image_src( $attachment_id, 'full' );
		return is_array( $imagen ) && (int) $imagen[1] >= $min_width;
	}








	private static function upload( string $campo, int $event_id ): int {
		if ( ! current_user_can( 'upload_files' ) ) {
			return 0;
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$id = media_handle_upload(
			$campo,
			$event_id,
			array(),
			array(


				'test_form' => false,
				'mimes'     => self::IMAGE_MIMES,
			)
		);
		return is_wp_error( $id ) ? 0 : (int) $id;
	}







	private static function why( array $errores ): string {
		$textos = array(
			'title'      => 'El evento necesita un título.',
			'date_order' => 'La fecha de fin no puede ser anterior a la de inicio.',
			'start_date' => 'Indique la fecha de inicio del evento, con día, mes y año.',
			'end_date'   => 'Revise la fecha de fin: no es una fecha del calendario.',
		);
		foreach ( $textos as $clave => $texto ) {
			if ( in_array( $clave, $errores, true ) ) {
				return $texto;
			}
		}
		return 'Revise los datos del evento: hay algo que no se puede guardar.';
	}














	private static function fill_signup( array $m, int $event_id ): array {
		$m['questions'] = Registrations::questions( $event_id );



		$m['q_locked'] = Registrations::has_any( $event_id );
		$m['signup']   = array(
			'open'            => (bool) get_post_meta( $event_id, RegistrationMetaKeys::SIGNUP_OPEN, true ),
			'workshop_open'   => (bool) get_post_meta( $event_id, RegistrationMetaKeys::WORKSHOP_OPEN, true ),
			'workshop_start'  => (string) get_post_meta( $event_id, RegistrationMetaKeys::WORKSHOP_START, true ),
			'workshop_end'    => (string) get_post_meta( $event_id, RegistrationMetaKeys::WORKSHOP_END, true ),
			'consent_privacy' => (string) get_post_meta( $event_id, RegistrationMetaKeys::CONSENT_PRIVACY, true ),
			'consent_image'   => (string) get_post_meta( $event_id, RegistrationMetaKeys::CONSENT_IMAGE, true ),
			'consent_version' => (int) get_post_meta( $event_id, RegistrationMetaKeys::CONSENT_VERSION, true ),
			'has_signups'     => $m['q_locked'],
		);
		return $m;
	}








	private static function save_signup( int $event_id, string $destino ): void {
		$abierta  = self::field( 'evt_signup_open' );
		$talleres = self::field( 'evt_workshop_open' );

		update_post_meta( $event_id, RegistrationMetaKeys::SIGNUP_OPEN, '' !== $abierta );
		update_post_meta( $event_id, RegistrationMetaKeys::WORKSHOP_OPEN, '' !== $talleres );
		update_post_meta( $event_id, RegistrationMetaKeys::WORKSHOP_START, self::field( 'evt_workshop_start' ) );
		update_post_meta( $event_id, RegistrationMetaKeys::WORKSHOP_END, self::field( 'evt_workshop_end' ) );

		self::save_consent( $event_id );

		$antes = Registrations::questions( $event_id );
		$ahora = SignupQuestions::with_ids(
			SignupQuestions::read( self::submitted_questions() ),
			static function (): string {
				return bin2hex( random_bytes( 6 ) );
			}
		);

		$rechazo = SignupQuestions::refuse( $antes, $ahora, Registrations::has_any( $event_id ) );
		if ( array() !== $rechazo ) {
			self::set_flash(
				'error',
				'Ya hay personas inscritas, así que a una pregunta se le puede cambiar el rótulo y añadir opciones, '
					. 'pero no cambiarle el tipo ni quitarle una opción: lo ya contestado dejaría de significar lo mismo. '
					. 'Lo demás no se ha guardado.'
			);
			Shell::leave( $destino );
			return;
		}



		update_post_meta( $event_id, RegistrationMetaKeys::SIGNUP_QUESTIONS, wp_slash( (string) wp_json_encode( $ahora ) ) );
		self::set_flash( 'ok', 'Inscripción del evento guardada.' );
		Shell::leave( $destino );
	}











	private static function save_consent( int $event_id ): void {

		$privacidad = wp_kses_post( wp_unslash( (string) ( $_POST['evt_consent_privacy'] ?? '' ) ) );
		$imagen     = wp_kses_post( wp_unslash( (string) ( $_POST['evt_consent_image'] ?? '' ) ) );


		$antes_privacidad = (string) get_post_meta( $event_id, RegistrationMetaKeys::CONSENT_PRIVACY, true );
		$antes_imagen     = (string) get_post_meta( $event_id, RegistrationMetaKeys::CONSENT_IMAGE, true );
		$cambia           = $antes_privacidad !== $privacidad || $antes_imagen !== $imagen;

		update_post_meta( $event_id, RegistrationMetaKeys::CONSENT_PRIVACY, $privacidad );
		update_post_meta( $event_id, RegistrationMetaKeys::CONSENT_IMAGE, $imagen );

		if ( $cambia ) {
			update_post_meta(
				$event_id,
				RegistrationMetaKeys::CONSENT_VERSION,
				1 + (int) get_post_meta( $event_id, RegistrationMetaKeys::CONSENT_VERSION, true )
			);
		}
	}











	private static function submitted_questions(): array {

		$ids       = (array) wp_unslash( $_POST['evt_q_id'] ?? array() );
		$rotulos   = (array) wp_unslash( $_POST['evt_q_label'] ?? array() );
		$tipos     = (array) wp_unslash( $_POST['evt_q_type'] ?? array() );
		$opciones  = (array) wp_unslash( $_POST['evt_q_options'] ?? array() );
		$obligadas = (array) wp_unslash( $_POST['evt_q_required'] ?? array() );


		$out = array();
		foreach ( $rotulos as $i => $rotulo ) {
			$out[] = array(
				'id'       => isset( $ids[ $i ] ) ? sanitize_text_field( (string) $ids[ $i ] ) : '',
				'label'    => sanitize_text_field( (string) $rotulo ),
				'type'     => isset( $tipos[ $i ] ) ? sanitize_key( (string) $tipos[ $i ] ) : 'text',
				'options'  => isset( $opciones[ $i ] ) ? sanitize_textarea_field( (string) $opciones[ $i ] ) : '',
				'required' => ! empty( $obligadas[ $i ] ),
			);
		}
		return $out;
	}







	private static function field( string $nombre ): string {

		return trim( sanitize_text_field( wp_unslash( (string) ( $_POST[ $nombre ] ?? '' ) ) ) );
	}












	private static function flash_key(): string {
		return 'evt_ws_flash_' . get_current_user_id();
	}









	private static function set_flash( string $tipo, string $texto, array $valores = array() ): void {
		set_transient(
			self::flash_key(),
			array(
				'tipo'   => $tipo,
				'texto'  => $texto,
				'values' => $valores,
			),
			MINUTE_IN_SECONDS
		);
	}






	private static function take_flash(): array {
		$vacio = array(
			'tipo'   => '',
			'texto'  => '',
			'values' => array(),
		);
		$flash = get_transient( self::flash_key() );
		delete_transient( self::flash_key() );
		return is_array( $flash ) ? array_merge( $vacio, array_intersect_key( $flash, $vacio ) ) : $vacio;
	}


















	public static function children( int $event_id, bool $papelera = false ): array {
		if ( $event_id <= 0 ) {
			return array();
		}
		return (array) get_posts(
			array(
				'post_type'        => EventPostType::POST_TYPE,
				'post_parent'      => $event_id,
				'post_status'      => $papelera ? array( 'trash' ) : array( 'publish', 'future', 'draft', 'pending', 'private' ),
				'orderby'          => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),

				'numberposts'      => 200,
				'suppress_filters' => false,
			)
		);
	}






	public static function model(): array {
		$m = self::blank();

		if ( ! is_user_logged_in() ) {
			$m['aviso'] = 'Debe iniciar sesión con su usuario para gestionar eventos.';
			return $m;
		}


		$event_id = absint( wp_unslash( $_GET[ self::ARG_EVENT ] ?? 0 ) );
		$pedido   = sanitize_key( wp_unslash( (string) ( $_GET[ self::ARG_PANEL ] ?? '' ) ) );


		$m['trash'] = '' !== sanitize_key( wp_unslash( (string) ( $_GET[ self::ARG_TRASH ] ?? '' ) ) );





		if ( $event_id <= 0 ) {
			return self::blank_event( $m );
		}

		$evento = get_post( $event_id );
		if ( ! $evento instanceof \WP_Post
			|| EventPostType::POST_TYPE !== $evento->post_type
			|| (int) $evento->post_parent > 0 ) {
			$m['aviso'] = 'Ese evento ya no existe. Elija uno en la lista para abrir su taller.';
			return $m;
		}

		$user_id = get_current_user_id();



		if ( ! EventAccess::can_open( $user_id, $event_id ) ) {
			$m['aviso']      = EventAccess::why_not_editable( $user_id, $event_id );
			$m['aviso_tipo'] = 'error';
			return $m;
		}

		return self::fill( $m, $evento, $user_id, $pedido );
	}












	private static function blank_event( array $m ): array {
		$user_id = get_current_user_id();
		if ( ! user_can( $user_id, 'edit_evt_events' ) ) {
			$m['aviso']      = 'Su perfil no puede crear eventos. Elija uno en la lista para abrir su taller.';
			$m['aviso_tipo'] = 'error';
			return $m;
		}

		$m['nuevo']        = true;
		$m['title']        = '';
		$m['panel']        = self::PANEL_SETTINGS;
		$m['panels']       = array();
		$m['flash']        = self::take_flash();
		$m['can_edit']     = true;
		$m['can_publish']  = false;
		$m['can_set_area'] = EventAccess::can_edit_all_areas( $user_id );
		$m['can_upload']   = current_user_can( 'upload_files' );
		$m['status']       = 'draft';
		$m['status_label'] = self::status_label( 'draft' );
		$m['values']       = self::values( 0, (array) $m['flash']['values'] );
		$m['terms']        = self::term_lists( $user_id, (int) $m['values'][ self::FIELD_AREA ] );

		return $m;
	}






	private static function blank(): array {
		return array(
			'aviso'         => '',
			'aviso_tipo'    => 'aviso',
			'nuevo'         => false,
			'event_id'      => 0,
			'title'         => '',
			'panel'         => self::PANEL_SECTIONS,
			'panels'        => array(),
			'flash'         => array(
				'tipo'   => '',
				'texto'  => '',
				'values' => array(),
			),
			'can_edit'      => false,
			'lock'          => EditLock::none(),
			'archived'      => false,
			'can_archive'   => false,
			'can_unarchive' => false,
			'can_publish'   => false,
			'can_set_area'  => false,
			'can_upload'    => false,
			'can_edit_css'  => false,
			'can_edit_js'   => false,
			'code'          => array(
				'css' => '',
				'js'  => '',
			),
			'state'         => '',
			'state_label'   => '',
			'status'        => '',
			'status_label'  => '',
			'area_ids'      => array(),
			'view_url'      => '',
			'events_url'    => Shell::url( 'events' ),
			'section_url'   => Shell::url( 'section' ),
			'sections'      => array(),
			'trashed'       => array(),
			'trash'         => false,
			'section_types' => EventMetaKeys::section_types(),
			'values'        => array(),
			'terms'         => array(),
			'media'         => array(),
			'speakers'      => array(),
			'activities'    => array(),
			'grid'          => array(),
			'workshops'     => array(),
			'venues'        => array(),
			'kinds'         => ProgrammeMetaKeys::activity_kinds(),
			'edit_row'      => 0,
			'edit_values'   => array(),
			'row_trash'     => array(),
			'people'        => array(),
			'people_total'  => 0,
			'people_q'      => '',
			'people_filter' => '',
			'people_tags'   => array(),
			'people_cols'   => Participants::columns(),
			'questions'     => array(),
			'q_types'       => RegistrationMetaKeys::question_types(),
			'q_locked'      => false,
			'signup'        => array(),
			'form_id'       => 0,
		);
	}










	private static function fill( array $m, \WP_Post $evento, int $user_id, string $pedido ): array {
		$event_id = (int) $evento->ID;
		$paneles  = self::panels( $event_id, $user_id );
		$css_ok   = EventAccess::can_edit_custom_css( $user_id, $event_id );
		$js_ok    = EventAccess::can_edit_custom_js( $user_id, $event_id );

		$m['event_id'] = $event_id;
		$m['title']    = (string) $evento->post_title;
		$m['panels']   = $paneles;
		$m['panel']    = isset( $paneles[ $pedido ] ) ? $pedido : self::PANEL_SECTIONS;
		$m['flash']    = self::take_flash();
		$m['can_edit'] = EventAccess::can_edit( $user_id, $event_id );
		$m['archived'] = EventAccess::is_archived( $event_id );




		$m['lock'] = EditLock::status( $event_id, (bool) $m['can_edit'] );
		if ( (int) $m['lock']['owner'] > 0 ) {
			$m['can_edit'] = false;
		}






		$libre              = 0 === (int) $m['lock']['owner'];
		$m['can_archive']   = $libre && ! $m['archived'] && EventAccess::can_archive( $user_id, $event_id );
		$m['can_unarchive'] = $libre && $m['archived'] && EventAccess::can_unarchive( $user_id );
		$m['can_publish']   = EventAccess::can_publish( $user_id, $event_id );
		$m['can_set_area']  = EventAccess::can_edit_all_areas( $user_id );
		$m['can_upload']    = current_user_can( 'upload_files' );
		$m['can_edit_css']  = $css_ok;
		$m['can_edit_js']   = $js_ok;


		$m['code']         = array(
			'css' => $css_ok ? self::meta( $event_id, EventMetaKeys::CUSTOM_CSS ) : '',
			'js'  => $js_ok ? self::meta( $event_id, EventMetaKeys::CUSTOM_JS ) : '',
		);
		$m['view_url']     = (string) get_permalink( $evento );
		$m['status']       = (string) $evento->post_status;
		$m['status_label'] = self::status_label( (string) $evento->post_status );
		$m['state']        = EventState::of(
			self::meta( $event_id, EventMetaKeys::START_DATE ),
			self::meta( $event_id, EventMetaKeys::END_DATE )
		);
		$m['state_label']  = EventState::label( (string) $m['state'] );
		$m['area_ids']     = EventAccess::post_areas( $event_id );
		$m['sections']     = self::section_rows( $event_id );
		$m['trashed']      = self::section_rows( $event_id, true );
		$m['values']       = self::values( $event_id, (array) $m['flash']['values'] );
		$m['terms']        = self::term_lists( $user_id, (int) $m['values'][ self::FIELD_AREA ] );
		$m['media']        = array(
			'logo'          => (int) self::meta( $event_id, EventMetaKeys::LOGO_ID ),
			'header_banner' => (int) self::meta( $event_id, EventMetaKeys::HEADER_BANNER_ID ),
			'poster'        => (int) self::meta( $event_id, EventMetaKeys::POSTER_ID ),
			'featured'      => (int) get_post_thumbnail_id( $event_id ),
		);

		return self::fill_signup( self::fill_programme( $m, $event_id ), $event_id );
	}













	private static function fill_programme( array $m, int $event_id ): array {

		$m['edit_row']      = absint( wp_unslash( $_GET[ self::ARG_ROW ] ?? 0 ) );
		$m['people_q']      = sanitize_text_field( wp_unslash( (string) ( $_GET[ self::ARG_Q ] ?? '' ) ) );
		$m['people_filter'] = sanitize_text_field( wp_unslash( (string) ( $_GET[ self::ARG_WORKSHOP ] ?? '' ) ) );


		foreach ( Programme::speakers( $event_id ) as $indice => $ponente ) {
			$fila            = Programme::speaker_row( $ponente );
			$fila['first']   = 0 === $indice;
			$fila['last']    = false;
			$m['speakers'][] = $fila;
		}
		$ultimo = count( (array) $m['speakers'] ) - 1;
		if ( $ultimo >= 0 ) {
			$m['speakers'][ $ultimo ]['last'] = true;
		}

		foreach ( Programme::activities( $event_id ) as $actividad ) {
			$m['activities'][] = Programme::activity_row( $actividad );
		}
		$inscritos = Participants::rows( $event_id );
		foreach ( Programme::workshops( $event_id ) as $taller ) {
			$fila = Programme::activity_row( $taller );





			$fila['taken']    = self::seats_taken( $inscritos, (string) $fila['title'] );
			$fila['free']     = $fila['seats'] > 0 ? max( 0, (int) $fila['seats'] - (int) $fila['taken'] ) : null;
			$m['workshops'][] = $fila;
		}

		$m['grid']   = Programme::grid( $event_id );
		$m['venues'] = Programme::venues( $event_id );




		foreach ( array_merge( Programme::speakers( $event_id, true ), Programme::activities( $event_id, true ) ) as $ficha ) {
			$m['row_trash'][] = array(
				'id'    => (int) $ficha->ID,
				'title' => (string) $ficha->post_title,
				'kind'  => SpeakerPostType::POST_TYPE === $ficha->post_type ? 'Ponente' : 'Actividad',
			);
		}

		$m['edit_values'] = self::row_values( $event_id, (int) $m['edit_row'], (string) $m['panel'] );

		$todos             = $inscritos;
		$m['people_total'] = count( $todos );
		$m['people_tags']  = Participants::workshops( $todos );
		$m['people']       = Participants::filter( $todos, (string) $m['people_q'], (string) $m['people_filter'] );
		$m['form_id']      = (int) self::meta( $event_id, EventMetaKeys::SIGNUP_FORM_ID );

		return $m;
	}








	private static function seats_taken( array $inscritos, string $titulo ): int {
		if ( '' === trim( $titulo ) ) {
			return 0;
		}
		$cuenta = 0;
		foreach ( $inscritos as $fila ) {
			if ( trim( (string) ( $fila['workshop'] ?? '' ) ) === trim( $titulo ) ) {
				++$cuenta;
			}
		}
		return $cuenta;
	}














	private static function row_values( int $event_id, int $row_id, string $panel ): array {
		if ( $row_id <= 0 ) {
			return array();
		}
		$post = get_post( $row_id );
		if ( ! $post instanceof \WP_Post || (int) $post->post_parent !== $event_id ) {
			return array();
		}
		if ( self::PANEL_SPEAKERS === $panel && SpeakerPostType::POST_TYPE === $post->post_type ) {
			return Programme::speaker_row( $post );
		}
		if ( ActivityPostType::POST_TYPE === $post->post_type ) {
			return Programme::activity_row( $post );
		}
		return array();
	}







	private static function status_label( string $status ): string {
		$objeto = get_post_status_object( $status );
		return null !== $objeto ? (string) $objeto->label : $status;
	}








	private static function meta( int $post_id, string $clave ): string {
		return (string) get_post_meta( $post_id, $clave, true );
	}








	private static function values( int $event_id, array $tecleado ): array {
		$guardado = array(



			self::FIELD_TITLE             => $event_id > 0 ? (string) get_the_title( $event_id ) : '',
			self::FIELD_AREA              => (string) self::first_term( $event_id, EventTaxonomies::AREA ),
			self::FIELD_TYPE              => (string) self::first_term( $event_id, EventTaxonomies::TYPE ),
			self::FIELD_COURSE            => (string) self::first_term( $event_id, EventTaxonomies::COURSE ),
			EventMetaKeys::TAGLINE        => self::meta( $event_id, EventMetaKeys::TAGLINE ),
			EventMetaKeys::HASHTAG        => self::meta( $event_id, EventMetaKeys::HASHTAG ),
			EventMetaKeys::INTRO          => self::meta( $event_id, EventMetaKeys::INTRO ),
			EventMetaKeys::START_DATE     => self::meta( $event_id, EventMetaKeys::START_DATE ),
			EventMetaKeys::END_DATE       => self::meta( $event_id, EventMetaKeys::END_DATE ),
			EventMetaKeys::VENUE          => self::meta( $event_id, EventMetaKeys::VENUE ),
			EventMetaKeys::SIGNUP_SHOW    => '' === self::meta( $event_id, EventMetaKeys::SIGNUP_SHOW ) ? '' : '1',
			EventMetaKeys::SIGNUP_LABEL   => self::meta( $event_id, EventMetaKeys::SIGNUP_LABEL ),
			EventMetaKeys::SIGNUP_URL     => self::meta( $event_id, EventMetaKeys::SIGNUP_URL ),
			EventMetaKeys::SIGNUP_FORM_ID => self::meta( $event_id, EventMetaKeys::SIGNUP_FORM_ID ),
			EventMetaKeys::HEADER_BG      => self::meta( $event_id, EventMetaKeys::HEADER_BG ),
			EventMetaKeys::HEADER_TEXT    => self::meta( $event_id, EventMetaKeys::HEADER_TEXT ),
			EventMetaKeys::TITLE_FONT     => self::meta( $event_id, EventMetaKeys::TITLE_FONT ),
			EventMetaKeys::BODY_FONT      => self::meta( $event_id, EventMetaKeys::BODY_FONT ),
			EventMetaKeys::IMAGE_SHAPE    => self::meta( $event_id, EventMetaKeys::IMAGE_SHAPE ),
			EventMetaKeys::SEPARATOR      => self::meta( $event_id, EventMetaKeys::SEPARATOR ),
		);



		return array_merge( $guardado, array_intersect_key( $tecleado, $guardado ) );
	}








	private static function first_term( int $event_id, string $taxonomy ): int {
		$ids = wp_get_post_terms( $event_id, $taxonomy, array( 'fields' => 'ids' ) );
		return is_array( $ids ) ? (int) ( $ids[0] ?? 0 ) : 0;
	}








	private static function term_lists( int $user_id, int $area_now ): array {
		$solo = EventAccess::can_edit_all_areas( $user_id )
			? array()
			: array_merge( EventAccess::user_areas( $user_id ), array( $area_now ) );

		return array(
			'area'   => self::term_options( EventTaxonomies::AREA, $solo ),
			'type'   => self::term_options( EventTaxonomies::TYPE ),
			'course' => self::term_options( EventTaxonomies::COURSE ),
		);
	}








	private static function term_options( string $taxonomy, array $solo = array() ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'fields'     => 'id=>name',
			)
		);
		if ( ! is_array( $terms ) ) {
			return array();
		}
		return array() === $solo ? $terms : array_intersect_key( $terms, array_flip( $solo ) );
	}








	private static function section_rows( int $event_id, bool $papelera = false ): array {
		$hijas = array_values( self::children( $event_id, $papelera ) );
		$total = count( $hijas );
		$tipos = EventMetaKeys::section_types();
		$filas = array();

		foreach ( $hijas as $i => $hija ) {
			$tipo = self::meta( (int) $hija->ID, EventMetaKeys::SECTION_TYPE );

			$filas[] = array(
				'id'           => (int) $hija->ID,
				'order'        => $i + 1,
				'type'         => $tipo,
				'type_label'   => (string) ( $tipos[ $tipo ] ?? 'Sin tipo' ),
				'title'        => (string) $hija->post_title,
				'slug'         => (string) $hija->post_name,
				'status'       => (string) $hija->post_status,
				'status_label' => self::status_label( (string) $hija->post_status ),
				'published'    => 'publish' === $hija->post_status,
				'edit_url'     => Shell::url(
					'section',
					array(
						self::ARG_EVENT   => $event_id,
						self::ARG_SECTION => (int) $hija->ID,
					)
				),



				'view_url'     => 'publish' === (string) $hija->post_status
					? (string) get_permalink( $hija )
					: (string) get_preview_post_link( $hija ),
				'first'        => 0 === $i,
				'last'         => $i === $total - 1,
			);
		}
		return $filas;
	}







	public static function render( $atts = array() ): string {
		unset( $atts );
		Assets::enqueue();
		$m = self::model();






		if ( in_array( (string) $m['panel'], array( self::PANEL_LOOK, self::PANEL_SPEAKERS ), true ) && true === $m['can_upload'] ) {
			wp_enqueue_media( array( 'post' => (int) $m['event_id'] ) );
		}



		return EditLock::render( EditLock::claim( (array) $m['lock'] ) ) . EventWorkspaceView::html( $m );
	}
}








namespace Evt\PublicFront\View;

use Evt\PublicFront\Assets;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\Shell;










final class PanelParts {










	private const SVG = array(
		'svg'  => array(
			'viewbox'     => true,
			'width'       => true,
			'height'      => true,
			'aria-hidden' => true,
			'focusable'   => true,
		),
		'path' => array(
			'fill' => true,
			'd'    => true,
		),
	);















	public static function action( array $m, int $id, string $op, string $icono, string $titulo, string $clases, string $panel, bool $apagado = false, string $confirmar = '' ): string {
		$pregunta = '' !== $confirmar ? ' data-evt-confirm="' . esc_attr( $confirmar ) . '"' : '';

		ob_start();
		?>
		<form class="evt-accion" method="post" action=""<?php echo $pregunta; ?>>
			<?php wp_nonce_field( EventWorkspace::nonce_action( $op ), EventWorkspace::nonce_name( $op, $id ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_EVENT ); ?>" value="<?php echo esc_attr( (string) (int) $m['event_id'] ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_ROW ); ?>" value="<?php echo esc_attr( (string) $id ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::ARG_PANEL ); ?>" value="<?php echo esc_attr( $panel ); ?>" />
			<button type="submit" class="<?php echo esc_attr( $clases . ' evt-icono' ); ?>"
				title="<?php echo esc_attr( $titulo ); ?>" data-bs-toggle="tooltip"
				<?php disabled( $apagado, true ); ?>>
				<?php echo wp_kses( Shell::icon( $icono ), self::SVG ); ?>
				<span class="screen-reader-text"><?php echo esc_html( $titulo ); ?></span>
			</button>
		</form>
		<?php
		return (string) ob_get_clean();
	}










	public static function icon_link( string $url, string $icono, string $titulo, string $clases = '' ): string {
		if ( '' === $url ) {
			return '';
		}
		$clases = trim( Assets::button_class() . ' evt-mini evt-icono ' . $clases );

		return '<a class="' . esc_attr( $clases ) . '" href="' . esc_url( $url ) . '"'
			. ' title="' . esc_attr( $titulo ) . '" data-bs-toggle="tooltip">'
			. wp_kses( Shell::icon( $icono ), self::SVG )
			. '<span class="screen-reader-text">' . esc_html( $titulo ) . '</span></a>';
	}











	public static function trash_link( array $m, string $panel ): string {
		$filas = (array) $m['row_trash'];
		if ( array() === $filas ) {
			return '';
		}
		$mini = Assets::button_class() . ' evt-mini';

		ob_start();
		?>
		<details class="evt-tarjeta">
			<summary><?php echo esc_html( sprintf( 'Papelera (%d)', count( $filas ) ) ); ?></summary>
			<p class="evt-sub">Nada se ha perdido. Al restaurar una ficha vuelve donde estaba, con sus datos.</p>
			<div class="evt-tabla-caja">
				<table class="evt-tabla">
					<thead>
						<tr>
							<th scope="col">Qué era</th>
							<th scope="col">Título</th>
							<th scope="col">Acciones</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $filas as $fila ) : ?>
							<tr>
								<td data-rotulo="Qué era"><?php echo esc_html( (string) $fila['kind'] ); ?></td>
								<td data-rotulo="Título"><?php echo esc_html( '' !== trim( (string) $fila['title'] ) ? (string) $fila['title'] : '(sin título)' ); ?></td>
								<td data-rotulo="Acciones">
									<?php
									$boton = self::action( $m, (int) $fila['id'], 'row_restore', 'restaurar', 'Restaurar esta ficha', $mini, $panel );
									echo $boton; 
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</details>
		<?php
		return (string) ob_get_clean();
	}







	public static function day( string $fecha ): string {
		if ( '' === $fecha ) {
			return 'Sin día asignado';
		}
		$marca = strtotime( $fecha . ' 12:00:00' );
		if ( false === $marca ) {
			return $fecha;
		}
		return (string) wp_date( 'l j \d\e F \d\e Y', $marca );
	}








	public static function slot( string $start, string $end ): string {
		if ( '' === $start ) {
			return '—';
		}
		return '' === $end ? $start : $start . ' – ' . $end;
	}
}








namespace Evt\PublicFront\View;

use Evt\PublicFront\Assets;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\Shell;










final class EventSpeakersPanel {







	public static function html( array $m ): string {
		$filas = (array) $m['speakers'];

		ob_start();
		?>
		<p class="evt-sub">
			Quién interviene en este evento. El orden es el que sale en la página
			de ponentes y en el programa. Cada ficha es de este evento: editarla
			no toca la de ninguna otra edición.
		</p>

		<?php echo PanelParts::trash_link( $m, EventWorkspace::PANEL_SPEAKERS ); ?>
		<?php echo self::form( $m ); ?>

		<?php if ( array() === $filas ) : ?>
			<div class="evt-tabla-caja">
				<p class="evt-vacio">Este evento todavía no tiene ponentes. Añada el primero arriba.</p>
			</div>
		<?php else : ?>
			<div class="evt-tabla-caja">
				<table class="evt-tabla">
					<thead>
						<tr>
							<th scope="col">Orden</th>
							<th scope="col">Foto</th>
							<th scope="col">Nombre</th>
							<th scope="col">Cargo</th>
							<th scope="col">Entidad</th>
							<th scope="col">Acciones</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $filas as $indice => $fila ) : ?>
							<?php echo self::row( $m, (array) $fila, (int) $indice + 1 ); ?>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
		<?php
		return (string) ob_get_clean();
	}







	private static function form( array $m ): string {
		$valores = (array) $m['edit_values'];
		$id      = isset( $valores['id'] ) ? (int) $valores['id'] : 0;
		$editar  = $id > 0;
		$op      = EventWorkspace::OP_SPEAKER;

		ob_start();
		?>
		<form class="evt-form evt-tarjeta" method="post" action="">
			<h2><?php echo esc_html( $editar ? 'Editar ponente' : 'Añadir ponente' ); ?></h2>
			<?php wp_nonce_field( EventWorkspace::nonce_action( $op ), EventWorkspace::nonce_name( $op ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_EVENT ); ?>" value="<?php echo esc_attr( (string) (int) $m['event_id'] ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_ROW ); ?>" value="<?php echo esc_attr( (string) $id ); ?>" />

			<div class="evt-form-fila">
				<div class="evt-form-campo">
					<label for="evt-sp-name">Nombre y apellidos</label>
					<input type="text" id="evt-sp-name" name="evt_sp_name" required
						value="<?php echo esc_attr( (string) ( $valores['name'] ?? '' ) ); ?>" />
					<small>Como quiera que salga en la web del evento.</small>
				</div>
				<div class="evt-form-campo">
					<label for="evt-sp-role">Cargo</label>
					<input type="text" id="evt-sp-role" name="evt_sp_role"
						value="<?php echo esc_attr( (string) ( $valores['role'] ?? '' ) ); ?>" />
					<small>«Asesora de formación», «Catedrático de Secundaria»… Se puede dejar vacío.</small>
				</div>
				<div class="evt-form-campo">
					<label for="evt-sp-org">Entidad o centro</label>
					<input type="text" id="evt-sp-org" name="evt_sp_org"
						value="<?php echo esc_attr( (string) ( $valores['org'] ?? '' ) ); ?>" />
				</div>
			</div>

			<div class="evt-form-campo">
				<label for="evt-sp-bio">Biografía</label>
				<textarea id="evt-sp-bio" name="evt_sp_bio" rows="4"><?php echo esc_textarea( (string) ( $valores['bio'] ?? '' ) ); ?></textarea>
				<small>Unas líneas. Sale debajo del nombre en la página de ponentes.</small>
			</div>

			<?php echo self::photo( $m, $valores ); ?>

			<div class="evt-acciones">
				<button class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" type="submit">
					<?php echo $editar ? '' : wp_kses_post( Shell::icon_plus() ); ?>
					<?php echo esc_html( $editar ? 'Guardar ponente' : 'Añadir ponente' ); ?>
				</button>
				<?php if ( $editar ) : ?>
					<a class="<?php echo esc_attr( Assets::button_class() ); ?>"
						href="<?php echo esc_url( EventWorkspace::url( (int) $m['event_id'], EventWorkspace::PANEL_SPEAKERS ) ); ?>">Cancelar</a>
				<?php endif; ?>
			</div>
		</form>
		<?php
		return (string) ob_get_clean();
	}











	private static function photo( array $m, array $valores ): string {
		$url = (string) ( $valores['photo'] ?? '' );
		$id  = (int) ( $valores['photo_id'] ?? 0 );

		ob_start();
		?>
		<div class="evt-form-campo evt-media" data-evt-media data-evt-media-type="image"
			data-evt-media-title="Elegir la foto del ponente" data-evt-media-button="Usar esta imagen">
			<span class="evt-media-rotulo">Foto</span>
			<input type="hidden" id="evt-sp-photo" name="evt_sp_photo" value="<?php echo esc_attr( (string) $id ); ?>" data-evt-media-value />
			<div class="evt-media-ficha" data-evt-media-card <?php echo esc_attr( '' !== $url ? '' : 'hidden' ); ?>>
				<img class="evt-media-miniatura" src="<?php echo esc_url( $url ); ?>" alt="" width="96" height="96" data-evt-media-thumb />
				<span class="evt-media-datos"><strong data-evt-media-name>Foto del ponente</strong><small data-evt-media-size></small></span>
			</div>
			<p class="evt-media-vacia" data-evt-media-empty <?php echo esc_attr( '' !== $url ? 'hidden' : '' ); ?>>Sin foto. La ficha se ve igual, con las iniciales.</p>
			<?php if ( true === $m['can_upload'] ) : ?>
				<p class="evt-media-drop">Arrastre una imagen hasta este campo o selecciónela en la biblioteca.</p>
				<p class="evt-media-estado" data-evt-media-status aria-live="polite"></p>
				<p class="evt-acciones evt-media-botones" data-evt-media-actions hidden>
					<button type="button" class="<?php echo esc_attr( Assets::button_class() . ' evt-mini' ); ?>" data-evt-media-pick>Seleccionar o subir</button>
					<button type="button" class="<?php echo esc_attr( Assets::button_class() . ' evt-mini' ); ?>" data-evt-media-clear <?php echo esc_attr( '' !== $url ? '' : 'hidden' ); ?>>Eliminar del campo</button>
				</p>
			<?php endif; ?>
			<small>La ventana de WordPress permite elegir una imagen existente, previsualizarla o arrastrar una nueva desde su equipo.</small>
		</div>
		<?php
		return (string) ob_get_clean();
	}









	private static function row( array $m, array $fila, int $posicion ): string {
		$id     = (int) $fila['id'];
		$nombre = '' !== trim( (string) $fila['name'] ) ? (string) $fila['name'] : '(sin nombre)';
		$mini   = Assets::button_class() . ' evt-mini';

		$subir  = PanelParts::action( $m, $id, 'sp_up', 'subir', 'Subir una posición', $mini, EventWorkspace::PANEL_SPEAKERS, (bool) $fila['first'] );
		$bajar  = PanelParts::action( $m, $id, 'sp_down', 'bajar', 'Bajar una posición', $mini, EventWorkspace::PANEL_SPEAKERS, (bool) $fila['last'] );
		$borrar = PanelParts::action(
			$m,
			$id,
			'row_delete',
			'papelera',
			'Enviar a la papelera',
			$mini . ' evt-btn-borrar',
			EventWorkspace::PANEL_SPEAKERS,
			false,
			sprintf( '¿Enviar a «%s» a la papelera? Dejará de salir en el evento y en las actividades donde esté.', $nombre )
		);

		ob_start();
		?>
		<tr>
			<td class="evt-num" data-rotulo="Orden"><?php echo esc_html( (string) $posicion ); ?></td>
			<td data-rotulo="Foto">
				<?php if ( '' !== (string) $fila['photo'] ) : ?>
					<img class="evt-media-miniatura" src="<?php echo esc_url( (string) $fila['photo'] ); ?>" alt="" width="48" height="48" />
				<?php else : ?>
					<span class="evt-sub">—</span>
				<?php endif; ?>
			</td>
			<td data-rotulo="Nombre"><?php echo esc_html( $nombre ); ?></td>
			<td data-rotulo="Cargo"><?php echo esc_html( '' !== (string) $fila['role'] ? (string) $fila['role'] : '—' ); ?></td>
			<td data-rotulo="Entidad"><?php echo esc_html( '' !== (string) $fila['org'] ? (string) $fila['org'] : '—' ); ?></td>
			<td data-rotulo="Acciones">
				<span class="evt-acciones">
					<?php
					$editar = PanelParts::icon_link(
						EventWorkspace::url( (int) $m['event_id'], EventWorkspace::PANEL_SPEAKERS, array( EventWorkspace::ARG_ROW => $id ) ),
						'lapiz',
						'Editar este ponente'
					);
					echo $editar; 
					?>
					<?php echo $subir; ?>
					<?php echo $bajar; ?>
					<?php echo $borrar; ?>
				</span>
			</td>
		</tr>
		<?php
		return (string) ob_get_clean();
	}
}








namespace Evt\PublicFront\View;

use Evt\PublicFront\Assets;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\Shell;













final class EventProgrammePanel {







	public static function html( array $m ): string {
		$dias = (array) $m['grid'];

		ob_start();
		?>
		<p class="evt-sub">
			Lo que pasa y cuándo. Se agrupa por día y, dentro de cada día, por
			sede: si una jornada tiene la mañana en un sitio y la tarde en otro,
			salen los dos bloques. La sede se escribe en cada actividad; no hay
			que darla de alta en ninguna parte.
		</p>

		<?php echo PanelParts::trash_link( $m, EventWorkspace::PANEL_PROGRAMME ); ?>
		<?php echo self::form( $m ); ?>

		<?php if ( array() === $dias ) : ?>
			<div class="evt-tabla-caja">
				<p class="evt-vacio">El programa está vacío. Añada la primera actividad arriba.</p>
			</div>
		<?php else : ?>
			<?php foreach ( $dias as $dia ) : ?>
				<?php echo self::day( $m, (array) $dia ); ?>
			<?php endforeach; ?>
		<?php endif; ?>
		<?php
		return (string) ob_get_clean();
	}








	private static function day( array $m, array $dia ): string {
		$sedes = (array) $dia['venues'];


		$rotular = count( $sedes ) > 1;

		ob_start();
		?>
		<section class="evt-tarjeta">
			<h2><?php echo esc_html( PanelParts::day( (string) $dia['date'] ) ); ?></h2>
			<?php foreach ( $sedes as $sede ) : ?>
				<?php if ( $rotular ) : ?>
					<h3 class="evt-sub">
						<?php echo esc_html( '' !== trim( (string) $sede['venue'] ) ? (string) $sede['venue'] : 'Sin sede indicada' ); ?>
					</h3>
				<?php endif; ?>
				<div class="evt-tabla-caja">
					<table class="evt-tabla">
						<thead>
							<tr>
								<th scope="col">Hora</th>
								<th scope="col">Tipo</th>
								<th scope="col">Actividad</th>
								<th scope="col">Ponentes</th>
								<th scope="col">Sala</th>
								<th scope="col">Acciones</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( (array) $sede['rows'] as $fila ) : ?>
								<?php echo self::row( $m, (array) $fila ); ?>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endforeach; ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}








	private static function row( array $m, array $fila ): string {
		$id     = (int) $fila['id'];
		$titulo = '' !== trim( (string) $fila['title'] ) ? (string) $fila['title'] : '(sin título)';
		$mini   = Assets::button_class() . ' evt-mini';
		$borrar = PanelParts::action(
			$m,
			$id,
			'row_delete',
			'papelera',
			'Enviar a la papelera',
			$mini . ' evt-btn-borrar',
			EventWorkspace::PANEL_PROGRAMME,
			false,
			sprintf( '¿Enviar «%s» a la papelera? Desaparecerá del programa.', $titulo )
		);

		ob_start();
		?>
		<tr>
			<td data-rotulo="Hora"><?php echo esc_html( PanelParts::slot( (string) $fila['start'], (string) $fila['end'] ) ); ?></td>
			<td data-rotulo="Tipo"><?php echo esc_html( (string) $fila['kind_label'] ); ?></td>
			<td data-rotulo="Actividad"><?php echo esc_html( $titulo ); ?></td>
			<td data-rotulo="Ponentes">
				<?php echo esc_html( array() === (array) $fila['speakers'] ? '—' : implode( ', ', (array) $fila['speakers'] ) ); ?>
			</td>
			<td data-rotulo="Sala"><?php echo esc_html( '' !== (string) $fila['room'] ? (string) $fila['room'] : '—' ); ?></td>
			<td data-rotulo="Acciones">
				<span class="evt-acciones">
					<?php
					$editar = PanelParts::icon_link(
						EventWorkspace::url( (int) $m['event_id'], EventWorkspace::PANEL_PROGRAMME, array( EventWorkspace::ARG_ROW => $id ) ),
						'lapiz',
						'Editar esta actividad'
					);
					echo $editar; 
					?>
					<?php echo $borrar; ?>
				</span>
			</td>
		</tr>
		<?php
		return (string) ob_get_clean();
	}







	private static function form( array $m ): string {
		$valores = (array) $m['edit_values'];
		$id      = isset( $valores['id'] ) ? (int) $valores['id'] : 0;
		$editar  = $id > 0;
		$op      = EventWorkspace::OP_ACTIVITY;
		$suyos   = isset( $valores['speaker_ids'] ) ? (array) $valores['speaker_ids'] : array();

		ob_start();
		?>
		<form class="evt-form evt-tarjeta" method="post" action="">
			<h2><?php echo esc_html( $editar ? 'Editar actividad' : 'Añadir actividad al programa' ); ?></h2>
			<?php wp_nonce_field( EventWorkspace::nonce_action( $op ), EventWorkspace::nonce_name( $op ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_EVENT ); ?>" value="<?php echo esc_attr( (string) (int) $m['event_id'] ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_ROW ); ?>" value="<?php echo esc_attr( (string) $id ); ?>" />

			<div class="evt-form-fila">
				<div class="evt-form-campo">
					<label for="evt-ac-title">Título</label>
					<input type="text" id="evt-ac-title" name="evt_ac_title" required
						value="<?php echo esc_attr( (string) ( $valores['title'] ?? '' ) ); ?>" />
				</div>
				<div class="evt-form-campo">
					<label for="evt-ac-kind">Tipo</label>
					<select id="evt-ac-kind" name="evt_ac_kind">
						<?php foreach ( (array) $m['kinds'] as $slug => $rotulo ) : ?>
							<option value="<?php echo esc_attr( (string) $slug ); ?>"
								<?php selected( (string) ( $valores['kind'] ?? 'ponencia' ), (string) $slug ); ?>>
								<?php echo esc_html( (string) $rotulo ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<small>«Taller» es el único que lleva aforo y aparece en la pestaña de Talleres.</small>
				</div>
			</div>

			<div class="evt-form-fila">
				<div class="evt-form-campo">
					<label for="evt-ac-date">Día</label>
					<input type="date" id="evt-ac-date" name="evt_ac_date" required
						value="<?php echo esc_attr( (string) ( $valores['date'] ?? '' ) ); ?>" />
				</div>
				<div class="evt-form-campo">
					<label for="evt-ac-start">Hora de inicio</label>
					<input type="time" id="evt-ac-start" name="evt_ac_start"
						value="<?php echo esc_attr( (string) ( $valores['start'] ?? '' ) ); ?>" />
					<small>Se puede dejar vacía si todavía no está fijada.</small>
				</div>
				<div class="evt-form-campo">
					<label for="evt-ac-end">Hora de fin</label>
					<input type="time" id="evt-ac-end" name="evt_ac_end"
						value="<?php echo esc_attr( (string) ( $valores['end'] ?? '' ) ); ?>" />
				</div>
			</div>

			<div class="evt-form-fila">
				<div class="evt-form-campo">
					<label for="evt-ac-venue">Sede</label>
					<input type="text" id="evt-ac-venue" name="evt_ac_venue" list="evt-sedes"
						value="<?php echo esc_attr( (string) ( $valores['venue'] ?? '' ) ); ?>" />
					<datalist id="evt-sedes">
						<?php foreach ( (array) $m['venues'] as $sede ) : ?>
							<option value="<?php echo esc_attr( (string) $sede ); ?>"></option>
						<?php endforeach; ?>
					</datalist>
					<small>Dónde ocurre esta actividad. Si repite la de otra, elíjala de la lista y el día saldrá en un solo bloque.</small>
				</div>
				<div class="evt-form-campo">
					<label for="evt-ac-room">Sala</label>
					<input type="text" id="evt-ac-room" name="evt_ac_room"
						value="<?php echo esc_attr( (string) ( $valores['room'] ?? '' ) ); ?>" />
					<small>«Aula 2», «Salón de actos»…</small>
				</div>
				<div class="evt-form-campo">
					<label for="evt-ac-seats">Aforo</label>
					<input type="number" id="evt-ac-seats" name="evt_ac_seats" min="0" step="1"
						value="<?php echo esc_attr( (string) (int) ( $valores['seats'] ?? 0 ) ); ?>" />
					<small>Solo para talleres. <strong>0 es sin límite.</strong></small>
				</div>
			</div>

			<?php echo self::speakers( $m, $suyos ); ?>

			<div class="evt-form-campo">
				<label for="evt-ac-summary">Descripción</label>
				<textarea id="evt-ac-summary" name="evt_ac_summary" rows="3"><?php echo esc_textarea( (string) ( $valores['summary'] ?? '' ) ); ?></textarea>
			</div>

			<div class="evt-acciones">
				<button class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" type="submit">
					<?php echo $editar ? '' : wp_kses_post( Shell::icon_plus() ); ?>
					<?php echo esc_html( $editar ? 'Guardar actividad' : 'Añadir actividad' ); ?>
				</button>
				<?php if ( $editar ) : ?>
					<a class="<?php echo esc_attr( Assets::button_class() ); ?>"
						href="<?php echo esc_url( EventWorkspace::url( (int) $m['event_id'], EventWorkspace::PANEL_PROGRAMME ) ); ?>">Cancelar</a>
				<?php endif; ?>
			</div>
		</form>
		<?php
		return (string) ob_get_clean();
	}












	private static function speakers( array $m, array $suyos ): string {
		$ponentes = (array) $m['speakers'];

		ob_start();
		?>
		<fieldset class="evt-form-campo">
			<legend>Ponentes</legend>
			<?php if ( array() === $ponentes ) : ?>
				<p class="evt-sub">
					Este evento todavía no tiene ponentes.
					<a href="<?php echo esc_url( EventWorkspace::url( (int) $m['event_id'], EventWorkspace::PANEL_SPEAKERS ) ); ?>">Añádalos en «Ponentes»</a>
					y vuelva: aquí solo salen los de este evento.
				</p>
			<?php else : ?>
				<?php foreach ( $ponentes as $ponente ) : ?>
					<?php $pid = (int) $ponente['id']; ?>
					<label class="evt-check">
						<input type="checkbox" name="evt_ac_speakers[]" value="<?php echo esc_attr( (string) $pid ); ?>"
							<?php checked( in_array( $pid, array_map( 'intval', $suyos ), true ), true ); ?> />
						<?php echo esc_html( (string) $ponente['name'] ); ?>
					</label>
				<?php endforeach; ?>
			<?php endif; ?>
		</fieldset>
		<?php
		return (string) ob_get_clean();
	}
}








namespace Evt\PublicFront\View;

use Evt\PublicFront\Assets;
use Evt\PublicFront\EventWorkspace;











final class EventWorkshopsPanel {







	public static function html( array $m ): string {
		$filas = (array) $m['workshops'];

		ob_start();
		?>
		<p class="evt-sub">
			Las actividades del programa marcadas como <strong>taller</strong>, con
			su aforo. Se crean y se editan en
			<a href="<?php echo esc_url( EventWorkspace::url( (int) $m['event_id'], EventWorkspace::PANEL_PROGRAMME ) ); ?>">Programa</a>:
			un taller es una actividad más, con plazas.
		</p>

		<?php if ( array() === $filas ) : ?>
			<div class="evt-tabla-caja">
				<p class="evt-vacio">
					Este evento no tiene talleres. Para crear uno, añada una actividad
					en «Programa» y elija el tipo «Taller».
				</p>
			</div>
		<?php else : ?>
			<?php echo self::counters( $m, $filas ); ?>
			<div class="evt-tabla-caja">
				<table class="evt-tabla">
					<thead>
						<tr>
							<th scope="col">Día</th>
							<th scope="col">Hora</th>
							<th scope="col">Taller</th>
							<th scope="col">Sede y sala</th>
							<th scope="col">Aforo</th>
							<th scope="col">Plazas</th>
							<th scope="col">Acciones</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $filas as $fila ) : ?>
							<?php echo self::row( $m, (array) $fila ); ?>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<p class="evt-sub">
				Las plazas ocupadas se cuentan cruzando el <strong>título del taller</strong>
				con lo que eligió cada persona al inscribirse, porque la inscripción
				todavía no es de este aplicativo y no hay un identificador común. Así
				que <strong>si le cambia el título a un taller ya empezado, la cuenta
				deja de cuadrar</strong>. Cuando el formulario de inscripción sea
				nuestro, se cruzará por identificador y esto dejará de pasar.
			</p>
		<?php endif; ?>
		<?php
		return (string) ob_get_clean();
	}








	private static function counters( array $m, array $filas ): string {
		unset( $m );
		$plazas   = 0;
		$ocupadas = 0;
		$llenos   = 0;
		foreach ( $filas as $fila ) {
			$fila      = (array) $fila;
			$plazas   += (int) $fila['seats'];
			$ocupadas += (int) $fila['taken'];
			if ( null !== $fila['free'] && 0 === (int) $fila['free'] ) {
				++$llenos;
			}
		}

		ob_start();
		?>
		<ul class="evt-cifras">
			<li class="evt-cifra"><strong><?php echo esc_html( (string) count( $filas ) ); ?></strong> talleres</li>
			<li class="evt-cifra"><strong><?php echo esc_html( (string) $ocupadas ); ?></strong> plazas ocupadas<?php echo $plazas > 0 ? esc_html( ' de ' . $plazas ) : ''; ?></li>
			<li class="evt-cifra"><strong><?php echo esc_html( (string) $llenos ); ?></strong> sin plazas libres</li>
		</ul>
		<?php
		return (string) ob_get_clean();
	}








	private static function row( array $m, array $fila ): string {
		$id    = (int) $fila['id'];
		$mini  = Assets::button_class() . ' evt-mini';
		$sede  = trim( (string) $fila['venue'] );
		$sala  = trim( (string) $fila['room'] );
		$donde = trim( $sede . ( '' !== $sede && '' !== $sala ? ' · ' : '' ) . $sala );

		ob_start();
		?>
		<tr>
			<td data-rotulo="Día"><?php echo esc_html( PanelParts::day( (string) $fila['date'] ) ); ?></td>
			<td data-rotulo="Hora"><?php echo esc_html( PanelParts::slot( (string) $fila['start'], (string) $fila['end'] ) ); ?></td>
			<td data-rotulo="Taller"><?php echo esc_html( '' !== trim( (string) $fila['title'] ) ? (string) $fila['title'] : '(sin título)' ); ?></td>
			<td data-rotulo="Sede y sala"><?php echo esc_html( '' !== $donde ? $donde : '—' ); ?></td>
			<td class="evt-num" data-rotulo="Aforo">
				<?php echo esc_html( (int) $fila['seats'] > 0 ? (string) (int) $fila['seats'] : 'Sin límite' ); ?>
			</td>
			<td data-rotulo="Plazas"><?php echo self::seats( $fila ); ?></td>
			<td data-rotulo="Acciones">
				<span class="evt-acciones">
					<?php
					$editar = PanelParts::icon_link(
						EventWorkspace::url( (int) $m['event_id'], EventWorkspace::PANEL_PROGRAMME, array( EventWorkspace::ARG_ROW => $id ) ),
						'lapiz',
						'Editar este taller en el programa'
					);
					echo $editar; 
					?>
				</span>
			</td>
		</tr>
		<?php
		return (string) ob_get_clean();
	}







	private static function seats( array $fila ): string {
		$ocupadas = (int) $fila['taken'];
		if ( null === $fila['free'] ) {
			return '<span class="evt-state">' . esc_html( $ocupadas . ' inscritas' ) . '</span>';
		}
		$libres = (int) $fila['free'];
		$clase  = 0 === $libres ? 'evt-state evt-state-finalizado' : 'evt-state evt-state-abierto';

		return '<span class="' . esc_attr( $clase ) . '">'
			. esc_html( 0 === $libres ? 'Completo' : $libres . ' libres' )
			. '</span> <span class="evt-sub">' . esc_html( '(' . $ocupadas . ' de ' . (int) $fila['seats'] . ')' ) . '</span>';
	}
}








namespace Evt\PublicFront\View;

use Evt\PublicFront\Assets;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\Participants;
use Evt\PublicFront\RegistrationFiles;













final class EventParticipantsPanel {







	public static function html( array $m ): string {
		if ( 0 === (int) $m['people_total'] ) {
			return self::empty_state( $m );
		}

		$filas = (array) $m['people'];

		ob_start();
		?>
		<p class="evt-sub">
			Quién se ha inscrito a este evento. Se puede filtrar por cualquier dato
			—un apellido, un centro, un taller— y exportar a CSV lo que quede
			filtrado, no la lista entera.
		</p>

		<?php echo self::counters( $m, $filas ); ?>
		<?php echo self::filter( $m ); ?>

		<?php if ( array() === $filas ) : ?>
			<div class="evt-tabla-caja">
				<p class="evt-vacio">Ninguna inscripción encaja con el filtro. Vacíelo para verlas todas.</p>
			</div>
		<?php else : ?>
			<div class="evt-tabla-caja">
				<table class="evt-tabla">
					<thead>
						<tr>
							<?php foreach ( (array) $m['people_cols'] as $rotulo ) : ?>
								<th scope="col"><?php echo esc_html( (string) $rotulo ); ?></th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $filas as $fila ) : ?>
							<tr>
								<?php foreach ( (array) $m['people_cols'] as $clave => $rotulo ) : ?>
									<td data-rotulo="<?php echo esc_attr( (string) $rotulo ); ?>">
										<?php if ( 'files' === $clave ) : ?>
											<?php echo self::downloads( $fila ); ?>
										<?php else : ?>
											<?php echo esc_html( '' !== (string) ( $fila[ $clave ] ?? '' ) ? (string) $fila[ $clave ] : '—' ); ?>
										<?php endif; ?>
									</td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
		<?php
		return (string) ob_get_clean();
	}












	private static function downloads( array $fila ): string {
		$documentos = isset( $fila[ Participants::KEY_FILES ] ) && is_array( $fila[ Participants::KEY_FILES ] )
			? $fila[ Participants::KEY_FILES ]
			: array();
		if ( array() === $documentos ) {
			return '—';
		}

		$enlaces = array();
		foreach ( $documentos as $documento ) {
			$nombre    = (string) ( $documento['name'] ?? '' );
			$enlaces[] = sprintf(
				'<a class="evt-descarga" href="%1$s" download>%2$s</a>',
				esc_url( RegistrationFiles::url( (int) ( $documento['reg'] ?? 0 ), (string) ( $documento['id'] ?? '' ) ) ),
				esc_html( '' !== $nombre ? $nombre : 'Descargar' )
			);
		}
		return implode( ' ', $enlaces );
	}








	private static function counters( array $m, array $filas ): string {
		$total    = (int) $m['people_total'];
		$filtrado = count( $filas );

		ob_start();
		?>
		<ul class="evt-cifras">
			<li class="evt-cifra"><strong><?php echo esc_html( (string) $total ); ?></strong> inscripciones</li>
			<?php if ( $filtrado !== $total ) : ?>
				<li class="evt-cifra"><strong><?php echo esc_html( (string) $filtrado ); ?></strong> con el filtro puesto</li>
			<?php endif; ?>
			<li class="evt-cifra"><strong><?php echo esc_html( (string) count( (array) $m['people_tags'] ) ); ?></strong> talleres elegidos</li>
		</ul>
		<?php
		return (string) ob_get_clean();
	}












	private static function filter( array $m ): string {
		$op   = EventWorkspace::OP_EXPORT;
		$base = EventWorkspace::url( (int) $m['event_id'], EventWorkspace::PANEL_PEOPLE );



		$accion  = (string) strtok( $base, '?' );
		$ocultos = array();
		parse_str( (string) wp_parse_url( $base, PHP_URL_QUERY ), $ocultos );
		unset( $ocultos[ EventWorkspace::ARG_Q ], $ocultos[ EventWorkspace::ARG_WORKSHOP ] );

		ob_start();
		?>
		<div class="evt-filtro">
			<form class="evt-form" method="get" action="<?php echo esc_url( $accion ); ?>">
				<?php foreach ( $ocultos as $clave => $valor ) : ?>
					<input type="hidden" name="<?php echo esc_attr( (string) $clave ); ?>" value="<?php echo esc_attr( (string) $valor ); ?>" />
				<?php endforeach; ?>
				<div class="evt-form-campo">
					<label for="evt-people-q">Buscar</label>
					<input type="search" id="evt-people-q" name="<?php echo esc_attr( EventWorkspace::ARG_Q ); ?>"
						value="<?php echo esc_attr( (string) $m['people_q'] ); ?>" placeholder="Apellido, centro, correo…" />
				</div>
				<?php if ( array() !== (array) $m['people_tags'] ) : ?>
					<div class="evt-form-campo">
						<label for="evt-people-taller">Taller</label>
						<select id="evt-people-taller" name="<?php echo esc_attr( EventWorkspace::ARG_WORKSHOP ); ?>">
							<option value="">Todos</option>
							<?php foreach ( (array) $m['people_tags'] as $taller ) : ?>
								<option value="<?php echo esc_attr( (string) $taller ); ?>"
									<?php selected( (string) $m['people_filter'], (string) $taller ); ?>>
									<?php echo esc_html( (string) $taller ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php endif; ?>
				<div class="evt-acciones">
					<button class="<?php echo esc_attr( Assets::button_class() ); ?>" type="submit">Filtrar</button>
					<?php if ( '' !== (string) $m['people_q'] || '' !== (string) $m['people_filter'] ) : ?>
						<a class="<?php echo esc_attr( Assets::button_class() ); ?>" href="<?php echo esc_url( $base ); ?>">Quitar el filtro</a>
					<?php endif; ?>
				</div>
			</form>

			<form class="evt-form" method="post" action="">
				<?php wp_nonce_field( EventWorkspace::nonce_action( $op ), EventWorkspace::nonce_name( $op ), false ); ?>
				<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_EVENT ); ?>" value="<?php echo esc_attr( (string) (int) $m['event_id'] ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( EventWorkspace::ARG_Q ); ?>" value="<?php echo esc_attr( (string) $m['people_q'] ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( EventWorkspace::ARG_WORKSHOP ); ?>" value="<?php echo esc_attr( (string) $m['people_filter'] ); ?>" />
				<div class="evt-acciones">
					<button class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" type="submit">Exportar a CSV</button>
				</div>
			</form>
		</div>
		<?php
		return (string) ob_get_clean();
	}










	private static function empty_state( array $m ): string {
		$viejo = (int) $m['form_id'];

		ob_start();
		?>
		<section class="evt-tarjeta">
			<h2>Todavía no hay nadie inscrito</h2>
			<p>
				Los participantes de este evento se gestionan <strong>aquí</strong>:
				cuando alguien se inscriba saldrá en esta tabla, y desde ella podrá
				filtrar y exportar a CSV.
			</p>
			<p>
				Si la inscripción de este evento está cerrada, nadie puede apuntarse
				todavía: se abre en la pestaña
				<a href="<?php echo esc_url( EventWorkspace::url( (int) $m['event_id'], EventWorkspace::PANEL_SIGNUP ) ); ?>">Inscripción</a>,
				junto con las preguntas propias del evento. No es un fallo del evento.
			</p>
			<?php if ( $viejo > 0 ) : ?>
				<p>
					Este evento viene del sistema anterior y todavía apunta a su
					<strong>formulario antiguo, el número <?php echo esc_html( (string) $viejo ); ?></strong>.
					Esas inscripciones se siguen consultando allí y
					<strong>no se traen a esta pantalla</strong>; el campo está en
					<a href="<?php echo esc_url( EventWorkspace::url( (int) $m['event_id'], EventWorkspace::PANEL_SETTINGS ) ); ?>">Ajustes</a>
					marcado como histórico, y desaparecerá.
				</p>
			<?php endif; ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}








namespace Evt\PublicFront\View;

use Evt\PublicFront\EventWorkspace;

















final class EventSignupPanel {







	public static function html( array $m ): string {
		$event_id  = (int) $m['event_id'];
		$ajustes   = (array) $m['signup'];
		$preguntas = (array) $m['questions'];

		$op    = EventWorkspace::PANEL_SIGNUP;
		$html  = '<form class="evt-form evt-panel--inscripcion" method="post" action="">';
		$html .= wp_nonce_field( EventWorkspace::nonce_action( $op ), EventWorkspace::nonce_name( $op ), false, false );
		$html .= sprintf(
			'<input type="hidden" name="%1$s" value="%2$s"><input type="hidden" name="%3$s" value="%4$d">',
			esc_attr( EventWorkspace::FIELD_DO ),
			esc_attr( $op ),
			esc_attr( EventWorkspace::FIELD_EVENT ),
			$event_id
		);

		$html .= self::windows( $ajustes );
		$html .= self::consent( $ajustes );
		$html .= self::questions( $preguntas, (array) $m['q_types'], (bool) $m['q_locked'] );

		$html .= '<p class="evt-panel__enviar"><button type="submit" class="evt-btn evt-btn--primario">Guardar</button></p>';
		$html .= '</form>';

		return $html;
	}







	private static function windows( array $a ): string {
		$html  = '<fieldset class="evt-campos"><legend>Plazos</legend>';
		$html .= self::toggle( 'evt_signup_open', 'La inscripción está abierta', (bool) $a['open'] );
		$html .= '<p class="evt-ayuda">Mientras esté cerrada, la página de inscripción lo dice y no acepta a nadie.</p>';



		$html .= self::toggle( 'evt_workshop_open', 'Se puede elegir taller', (bool) $a['workshop_open'] );
		$html .= '<p class="evt-campo evt-campo--fecha"><label for="evt-ws-desde">Desde</label>'
			. '<input type="date" id="evt-ws-desde" name="evt_workshop_start" value="' . esc_attr( (string) $a['workshop_start'] ) . '"></p>';
		$html .= '<p class="evt-campo evt-campo--fecha"><label for="evt-ws-hasta">Hasta</label>'
			. '<input type="date" id="evt-ws-hasta" name="evt_workshop_end" value="' . esc_attr( (string) $a['workshop_end'] ) . '"></p>';
		$html .= '<p class="evt-ayuda">Las fechas son opcionales: sin ellas manda el interruptor. '
			. 'Quien ya se inscribió puede cambiar de taller mientras el plazo siga abierto, '
			. 'y un taller lleno deja de poder elegirse.</p>';

		return $html . '</fieldset>';
	}







	private static function consent( array $a ): string {
		$html  = '<fieldset class="evt-campos"><legend>Protección de datos</legend>';
		$html .= '<p class="evt-campo"><label for="evt-consent-privacidad">Información sobre el tratamiento de sus datos</label>'
			. '<textarea id="evt-consent-privacidad" name="evt_consent_privacy" rows="6">'
			. esc_textarea( (string) $a['consent_privacy'] ) . '</textarea></p>';
		$html .= '<p class="evt-campo"><label for="evt-consent-imagen">Consentimiento informado</label>'
			. '<textarea id="evt-consent-imagen" name="evt_consent_image" rows="6">'
			. esc_textarea( (string) $a['consent_image'] ) . '</textarea></p>';



		$html .= '<p class="evt-ayuda">Versión actual: <strong>v' . (int) $a['consent_version'] . '</strong>. '
			. 'Cambiar cualquiera de los dos textos crea una versión nueva; lo que ya aceptó alguien no se reescribe, '
			. 'y en la lista de participantes se ve qué versión aceptó cada persona.</p>';

		return $html . '</fieldset>';
	}









	private static function questions( array $preguntas, array $tipos, bool $locked ): string {
		$html = '<fieldset class="evt-campos evt-preguntas"><legend>Preguntas de este evento</legend>';

		$html .= '<p class="evt-ayuda">Tres o cuatro, las de logística: si se queda a comer, intolerancias, '
			. 'si es residente. El resto del formulario —documento, nombre, apellidos, correo, teléfono, centro '
			. 'y consentimiento— es siempre el mismo y no se toca desde aquí.</p>';

		if ( $locked ) {
			$html .= '<p class="evt-aviso evt-aviso--aviso">Ya hay personas inscritas. Puede reescribir un rótulo y '
				. '<strong>añadir</strong> opciones, pero no cambiar el tipo de una pregunta ni quitarle una opción: '
				. 'lo que ya se contestó dejaría de significar lo mismo.</p>';
		}

		$filas = $preguntas;



		$filas[] = array(
			'id'       => '',
			'label'    => '',
			'type'     => 'text',
			'options'  => array(),
			'required' => false,
		);

		foreach ( $filas as $i => $pregunta ) {
			$html .= self::row( (int) $i, $pregunta, $tipos );
		}

		$html .= '<p class="evt-ayuda">Para quitar una pregunta, borre su rótulo y guarde. '
			. 'Lo que ya hubiera contestado alguien no se borra: deja de verse, y vuelve si la pregunta vuelve.</p>';

		return $html . '</fieldset>';
	}









	private static function row( int $i, array $p, array $tipos ): string {
		$nueva  = '' === (string) $p['id'];
		$rotulo = $nueva ? 'Pregunta nueva' : 'Pregunta ' . ( $i + 1 );

		$html  = '<div class="evt-pregunta">';
		$html .= '<h4 class="evt-pregunta__n">' . esc_html( $rotulo ) . '</h4>';
		$html .= sprintf( '<input type="hidden" name="evt_q_id[%1$d]" value="%2$s">', $i, esc_attr( (string) $p['id'] ) );

		$html .= sprintf(
			'<p class="evt-campo"><label for="evt-q-l-%1$d">Rótulo</label>'
				. '<input type="text" id="evt-q-l-%1$d" name="evt_q_label[%1$d]" value="%2$s" maxlength="200"></p>',
			$i,
			esc_attr( (string) $p['label'] )
		);

		$html .= '<p class="evt-campo"><label for="evt-q-t-' . $i . '">Tipo</label>'
			. '<select id="evt-q-t-' . $i . '" name="evt_q_type[' . $i . ']">';
		foreach ( $tipos as $valor => $nombre ) {
			$html .= sprintf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $valor ),
				selected( $valor, (string) $p['type'], false ),
				esc_html( $nombre )
			);
		}
		$html .= '</select></p>';

		$html .= sprintf(
			'<p class="evt-campo"><label for="evt-q-o-%1$d">Opciones, una por línea</label>'
				. '<textarea id="evt-q-o-%1$d" name="evt_q_options[%1$d]" rows="3">%2$s</textarea>'
				. '<small>Solo para «Una opción» y «Varias opciones».</small></p>',
			$i,
			esc_textarea( implode( "\n", (array) $p['options'] ) )
		);

		$html .= sprintf(
			'<p class="evt-campo evt-campo--casilla"><label for="evt-q-r-%1$d">'
				. '<input type="checkbox" id="evt-q-r-%1$d" name="evt_q_required[%1$d]" value="1"%2$s> Obligatoria</label></p>',
			$i,
			checked( true, (bool) $p['required'], false )
		);

		return $html . '</div>';
	}









	private static function toggle( string $nombre, string $rotulo, bool $puesto ): string {
		return sprintf(
			'<p class="evt-campo evt-campo--casilla"><label for="%1$s"><input type="checkbox" id="%1$s" name="%1$s" value="1"%2$s> %3$s</label></p>',
			esc_attr( $nombre ),
			checked( true, $puesto, false ),
			esc_html( $rotulo )
		);
	}
}








namespace Evt\PublicFront\View;

use Evt\PublicFront\Assets;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\Shell;












final class EventSectionsPanel {






	private const SVG = array(
		'svg'  => array(
			'viewbox'     => true,
			'width'       => true,
			'height'      => true,
			'aria-hidden' => true,
			'focusable'   => true,
		),
		'path' => array(
			'fill' => true,
			'd'    => true,
		),
	);












	private static function publish_switch( array $m, array $fila ): string {
		$id        = (int) $fila['id'];
		$publicada = (bool) $fila['published'];
		$op        = $publicada ? 'unpublish' : 'publish';
		$rotulo    = $publicada ? 'Despublicar esta página' : 'Publicar esta página';

		ob_start();
		?>
		<form class="evt-accion evt-switch" method="post" action="">
			<?php wp_nonce_field( EventWorkspace::nonce_action( $op ), EventWorkspace::nonce_name( $op, $id ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_EVENT ); ?>" value="<?php echo esc_attr( (string) (int) $m['event_id'] ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_SECTION ); ?>" value="<?php echo esc_attr( (string) $id ); ?>" />
			<label class="evt-switch-caja" title="<?php echo esc_attr( $rotulo ); ?>" data-bs-toggle="tooltip">
				<input type="checkbox" class="evt-switch-input" data-evt-switch <?php checked( $publicada, true ); ?> />
				<span class="evt-switch-pista" aria-hidden="true"></span>
				<span class="evt-switch-txt"><?php echo esc_html( (string) $fila['status_label'] ); ?></span>
				<span class="screen-reader-text"><?php echo esc_html( $rotulo ); ?></span>
			</label>
			<button type="submit" class="<?php echo esc_attr( Assets::button_class() . ' evt-mini evt-switch-boton' ); ?>"><?php echo esc_html( $publicada ? 'Despublicar' : 'Publicar' ); ?></button>
		</form>
		<?php
		return (string) ob_get_clean();
	}







	public static function html( array $m ): string {
		return true === $m['trash'] ? self::trash( $m ) : self::live( $m );
	}







	private static function live( array $m ): string {
		$filas = (array) $m['sections'];

		ob_start();
		?>
		<p class="evt-sub">
			Las páginas de este evento, en el orden en que salen en su menú. Cada
			una es una página propia con su dirección: al despublicarla desaparece
			del menú, pero no se pierde nada de lo escrito.
		</p>

		<?php echo self::trash_link( $m ); ?>
		<?php echo self::add_form( $m ); ?>

		<?php if ( array() === $filas ) : ?>
			<div class="evt-tabla-caja">
				<p class="evt-vacio">Este evento todavía no tiene ninguna sección. Añada la primera arriba.</p>
			</div>
		<?php else : ?>
			<div class="evt-tabla-caja">
				<table class="evt-tabla">
					<thead>
						<tr>
							<th scope="col">Orden</th>
							<th scope="col">Tipo</th>
							<th scope="col">Título</th>
							<th scope="col">Dirección</th>
							<th scope="col">Estado</th>
							<th scope="col">Acciones</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $filas as $fila ) : ?>
							<?php echo self::row( $m, (array) $fila ); ?>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
		<?php
		return (string) ob_get_clean();
	}







	private static function trash_link( array $m ): string {
		$cuantas = count( (array) $m['trashed'] );
		if ( 0 === $cuantas ) {
			return '';
		}

		return '<p class="evt-acciones"><a href="'
			. esc_url( EventWorkspace::url( (int) $m['event_id'], EventWorkspace::PANEL_SECTIONS, array( EventWorkspace::ARG_TRASH => 1 ) ) )
			. '">' . esc_html( sprintf( 'Papelera (%d)', $cuantas ) ) . '</a></p>';
	}










	private static function trash( array $m ): string {
		$filas  = (array) $m['trashed'];
		$volver = EventWorkspace::url( (int) $m['event_id'], EventWorkspace::PANEL_SECTIONS );
		$clases = Assets::button_class() . ' evt-mini';

		ob_start();
		?>
		<p class="evt-sub">
			Las secciones de este evento que se enviaron a la papelera. Nada se ha
			perdido: al restaurar una vuelve en borrador, así que no reaparece en
			el menú del evento hasta que la publique. Para borrar algo de verdad y
			para siempre hay que ir al escritorio de WordPress: desde aquí no se
			destruye nada.
		</p>

		<p class="evt-acciones">
			<a href="<?php echo esc_url( $volver ); ?>">Volver a las secciones</a>
		</p>

		<?php if ( array() === $filas ) : ?>
			<div class="evt-tabla-caja">
				<p class="evt-vacio">La papelera de este evento está vacía.</p>
			</div>
		<?php else : ?>
			<div class="evt-tabla-caja">
				<table class="evt-tabla">
					<thead>
						<tr>
							<th scope="col">Tipo</th>
							<th scope="col">Título</th>
							<th scope="col">Dirección</th>
							<th scope="col">Acciones</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $filas as $fila ) : ?>
							<?php $fila = (array) $fila; ?>
							<tr>
								<td data-rotulo="Tipo"><?php echo esc_html( (string) $fila['type_label'] ); ?></td>
								<td data-rotulo="Título"><?php echo esc_html( self::title_of( $fila ) ); ?></td>
								<td class="evt-slug" data-rotulo="Dirección"><?php echo esc_html( '' !== (string) $fila['slug'] ? (string) $fila['slug'] : '—' ); ?></td>
								<td data-rotulo="Acciones">
									<span class="evt-acciones">
										<?php
										$boton = self::action_form( $m, (int) $fila['id'], 'restore', 'Restaurar', 'Restaurar la sección, en borrador', $clases );
										echo $boton; 
										?>
									</span>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
		<?php
		return (string) ob_get_clean();
	}










	private static function add_form( array $m ): string {
		$base = (string) $m['section_url'];
		if ( '' === $base ) {
			return '<p class="' . esc_attr( Assets::alert_class( 'warning' ) ) . '">'
				. esc_html( 'Todavía no existe la página del formulario de secciones, así que no se pueden añadir ni editar. Lo resuelve quien despliega el aplicativo.' )
				. '</p>';
		}




		$accion  = (string) strtok( $base, '?' );
		$ocultos = array();
		parse_str( (string) wp_parse_url( $base, PHP_URL_QUERY ), $ocultos );
		$ocultos[ EventWorkspace::ARG_EVENT ] = (string) (int) $m['event_id'];

		ob_start();
		?>
		<form class="evt-form evt-tarjeta" method="get" action="<?php echo esc_url( $accion ); ?>">
			<h2>Añadir sección</h2>
			<p>Elija qué va a ser la página nueva. El tipo decide los textos por defecto y el icono con que sale en la portada del evento.</p>
			<div class="evt-form-fila">
				<div>
					<label for="evt-add-tipo">Tipo de sección</label>
					<select id="evt-add-tipo" name="<?php echo esc_attr( EventWorkspace::ARG_TYPE ); ?>">
						<?php foreach ( (array) $m['section_types'] as $slug => $rotulo ) : ?>
							<option value="<?php echo esc_attr( (string) $slug ); ?>"><?php echo esc_html( (string) $rotulo ); ?></option>
						<?php endforeach; ?>
					</select>
					<small>Si ninguna encaja, elija «Otra» y póngale el título que quiera.</small>
				</div>
				<div class="evt-acciones">
					<button class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" type="submit">
						<?php echo wp_kses_post( Shell::icon_plus() ); ?> Añadir sección
					</button>
				</div>
			</div>
			<?php foreach ( $ocultos as $clave => $valor ) : ?>
				<input type="hidden" name="<?php echo esc_attr( (string) $clave ); ?>" value="<?php echo esc_attr( (string) $valor ); ?>" />
			<?php endforeach; ?>
		</form>
		<?php
		return (string) ob_get_clean();
	}








	private static function row( array $m, array $fila ): string {
		$id     = (int) $fila['id'];
		$titulo = self::title_of( $fila );
		$mini   = Assets::button_class() . ' evt-mini';


		$subir  = self::action_form( $m, $id, 'up', 'subir', 'Subir una posición', $mini, (bool) $fila['first'] );
		$bajar  = self::action_form( $m, $id, 'down', 'bajar', 'Bajar una posición', $mini, (bool) $fila['last'] );
		$borrar = self::action_form(
			$m,
			$id,
			'delete',
			'papelera',
			'Enviar a la papelera',
			$mini . ' evt-btn-borrar',
			false,
			sprintf( '¿Enviar «%s» a la papelera? Dejará de verse en el evento.', $titulo )
		);
		$estado = '';
		if ( (bool) $m['can_publish'] ) {
			$estado = self::publish_switch( $m, $fila );
		}

		ob_start();
		?>
		<tr>
			<td class="evt-num" data-rotulo="Orden"><?php echo esc_html( (string) (int) $fila['order'] ); ?></td>
			<td data-rotulo="Tipo"><?php echo esc_html( (string) $fila['type_label'] ); ?></td>
			<td data-rotulo="Título">
				<?php if ( $fila['published'] && '' !== (string) $fila['view_url'] ) : ?>
					<a href="<?php echo esc_url( (string) $fila['view_url'] ); ?>"><?php echo esc_html( $titulo ); ?></a>
				<?php else : ?>
					<?php echo esc_html( $titulo ); ?>
				<?php endif; ?>
			</td>
			<td class="evt-slug" data-rotulo="Dirección"><?php echo esc_html( '' !== (string) $fila['slug'] ? (string) $fila['slug'] : '—' ); ?></td>
			<td data-rotulo="Estado">
				<?php if ( '' !== $estado ) : ?>
					<?php echo $estado; ?>
				<?php else : ?>
					<span class="<?php echo esc_attr( $fila['published'] ? 'evt-state evt-state-publish' : 'evt-state evt-state-draft' ); ?>">
						<?php echo esc_html( (string) $fila['status_label'] ); ?>
					</span>
				<?php endif; ?>
			</td>
			<td data-rotulo="Acciones">
				<span class="evt-acciones">
					<?php
					$acciones = PanelParts::icon_link( (string) $fila['edit_url'], 'lapiz', 'Editar esta página' )
						. PanelParts::icon_link(
							(string) $fila['view_url'],
							'ojo',
							$fila['published'] ? 'Ver esta página' : 'Previsualizar esta página, que está en borrador'
						);
					echo $acciones; 
					?>
					<?php echo $subir; ?>
					<?php echo $bajar; ?>
					<?php echo $borrar; ?>
				</span>
			</td>
		</tr>
		<?php
		return (string) ob_get_clean();
	}














	private static function action_form( array $m, int $id, string $op, string $icono, string $titulo, string $clases, bool $apagado = false, string $confirmar = '' ): string {
		$pregunta = '' !== $confirmar ? ' data-evt-confirm="' . esc_attr( $confirmar ) . '"' : '';

		ob_start();
		?>
		<form class="evt-accion" method="post" action=""<?php echo $pregunta; ?>>
			<?php wp_nonce_field( EventWorkspace::nonce_action( $op ), EventWorkspace::nonce_name( $op, $id ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_EVENT ); ?>" value="<?php echo esc_attr( (string) (int) $m['event_id'] ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_SECTION ); ?>" value="<?php echo esc_attr( (string) $id ); ?>" />
			<button type="submit" class="<?php echo esc_attr( $clases . ' evt-icono' ); ?>"
				title="<?php echo esc_attr( $titulo ); ?>" data-bs-toggle="tooltip"
				<?php disabled( $apagado, true ); ?>>
				<?php echo wp_kses( Shell::icon( $icono ), self::SVG ); ?>
				<span class="screen-reader-text"><?php echo esc_html( $titulo ); ?></span>
			</button>
		</form>
		<?php
		return (string) ob_get_clean();
	}







	private static function title_of( array $fila ): string {
		$titulo = trim( (string) $fila['title'] );
		return '' !== $titulo ? $titulo : '(sin título)';
	}
}








namespace Evt\PublicFront\View;

use Evt\Meta\EventMetaKeys;
use Evt\PublicFront\Assets;
use Evt\PublicFront\EventWorkspace;












final class EventDataPanel {







	public static function html( array $m ): string {
		$v      = (array) $m['values'];
		$listas = (array) $m['terms'];




		$sel_area    = self::term_select(
			'evt-area',
			EventWorkspace::FIELD_AREA,
			'Área organizadora',
			(array) ( $listas['area'] ?? array() ),
			(int) $v[ EventWorkspace::FIELD_AREA ],
			(bool) $m['can_set_area']
				? 'El área que organiza. Cambiarla cambia también quién puede editar el evento.'
				: 'El área que organiza. Solo puede elegir entre las suyas: para pasarlo a otra, pídalo a quien administra el aplicativo.'
		);
		$sel_tipo    = self::term_select(
			'evt-type',
			EventWorkspace::FIELD_TYPE,
			'Tipología',
			(array) ( $listas['type'] ?? array() ),
			(int) $v[ EventWorkspace::FIELD_TYPE ],
			'Jornadas, encuentro, congreso, taller… Sirve para agrupar eventos parecidos.'
		);
		$sel_curso   = self::term_select(
			'evt-course',
			EventWorkspace::FIELD_COURSE,
			'Curso escolar',
			(array) ( $listas['course'] ?? array() ),
			(int) $v[ EventWorkspace::FIELD_COURSE ],
			'El curso al que pertenece, en la forma 2025-2026.'
		);
		$inscripcion = self::signup_card( $v );

		ob_start();
		?>
		<form class="evt-form" method="post" action="">
			<?php wp_nonce_field( EventWorkspace::nonce_action( EventWorkspace::PANEL_SETTINGS ), EventWorkspace::nonce_name( EventWorkspace::PANEL_SETTINGS ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_DO ); ?>" value="<?php echo esc_attr( EventWorkspace::PANEL_SETTINGS ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_EVENT ); ?>" value="<?php echo esc_attr( (string) (int) $m['event_id'] ); ?>" />

			<fieldset class="evt-tarjeta">
				<legend>Identidad</legend>
				<p>Cómo se llama el evento y qué se lee de él antes de entrar.</p>

				<div class="evt-form-campo">
					<label for="evt-title">Título del evento</label>
					<input type="text" id="evt-title" name="<?php echo esc_attr( EventWorkspace::FIELD_TITLE ); ?>"
						required value="<?php echo esc_attr( (string) $v[ EventWorkspace::FIELD_TITLE ] ); ?>" />
					<small>El nombre completo, tal y como se anuncia. Es el que sale en grande en la cabecera.</small>
				</div>

				<div class="evt-form-fila">
					<div>
						<label for="evt-tagline">Lema</label>
						<input type="text" id="evt-tagline" name="<?php echo esc_attr( EventMetaKeys::TAGLINE ); ?>"
							value="<?php echo esc_attr( (string) $v[ EventMetaKeys::TAGLINE ] ); ?>" />
						<small>La línea corta que acompaña al título. Puede dejarse en blanco.</small>
					</div>
					<div>
						<label for="evt-hashtag">Etiqueta de redes</label>
						<input type="text" id="evt-hashtag" name="<?php echo esc_attr( EventMetaKeys::HASHTAG ); ?>"
							value="<?php echo esc_attr( (string) $v[ EventMetaKeys::HASHTAG ] ); ?>" />
						<small>Sin la almohadilla: escriba <code>jornadas25</code>, no <code>#jornadas25</code>.</small>
					</div>
				</div>

				<div class="evt-form-campo">
					<label for="evt-intro">Texto introductorio</label>
					<textarea id="evt-intro" name="<?php echo esc_attr( EventMetaKeys::INTRO ); ?>" rows="6"><?php echo esc_textarea( (string) $v[ EventMetaKeys::INTRO ] ); ?></textarea>
					<small>Dos o tres párrafos que expliquen de qué va y a quién se dirige. Es lo que se lee en la portada del evento, debajo de la cabecera.</small>
				</div>
			</fieldset>

			<fieldset class="evt-tarjeta">
				<legend>Cuándo y dónde</legend>
				<p>De estas fechas sale el estado del evento —próximo, abierto o finalizado—, así que no hay que marcarlo a mano en ningún sitio.</p>

				<div class="evt-form-fila">
					<div>
						<label for="evt-start">Fecha de inicio</label>
						<input type="date" id="evt-start" name="<?php echo esc_attr( EventMetaKeys::START_DATE ); ?>"
							required value="<?php echo esc_attr( (string) $v[ EventMetaKeys::START_DATE ] ); ?>" />
						<small>El primer día del evento.</small>
					</div>
					<div>
						<label for="evt-end">Fecha de fin</label>
						<input type="date" id="evt-end" name="<?php echo esc_attr( EventMetaKeys::END_DATE ); ?>"
							value="<?php echo esc_attr( (string) $v[ EventMetaKeys::END_DATE ] ); ?>" />
						<small>Déjela en blanco si el evento dura un solo día.</small>
					</div>
				</div>

				<div class="evt-form-campo">
					<label for="evt-venue">Sedes</label>
					<input type="text" id="evt-venue" name="<?php echo esc_attr( EventMetaKeys::VENUE ); ?>"
						value="<?php echo esc_attr( (string) $v[ EventMetaKeys::VENUE ] ); ?>" />
					<small>Dónde ocurre, tal y como se anuncia. Si son varias, sepárelas con comas; si es en línea, escríbalo así.</small>
				</div>
			</fieldset>

			<fieldset class="evt-tarjeta">
				<legend>Clasificación</legend>
				<p>Con qué se ordena y se busca el evento. El área es además quién lo edita: solo su área y quien administra el aplicativo.</p>

				<div class="evt-form-fila">
					<div><?php echo $sel_area; ?></div>
					<div><?php echo $sel_tipo; ?></div>
					<div><?php echo $sel_curso; ?></div>
				</div>
			</fieldset>

			<?php echo $inscripcion; ?>

			<p class="evt-acciones">
				<button class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" type="submit">
					<?php echo esc_html( true === ( $m['nuevo'] ?? false ) ? 'Crear el evento' : 'Guardar los datos' ); ?>
				</button>
			</p>
		</form>
		<?php
		return (string) ob_get_clean();
	}







	private static function signup_card( array $v ): string {
		ob_start();
		?>
			<fieldset class="evt-tarjeta">
				<legend>Inscripción</legend>
				<p>Las inscripciones siguen llevándose en el sistema anterior: aquí solo se dice si la portada enseña el botón y a dónde lleva.</p>

				<div class="evt-form-campo">
					<label for="evt-signup-show">
						<input type="checkbox" id="evt-signup-show" name="<?php echo esc_attr( EventMetaKeys::SIGNUP_SHOW ); ?>" value="1"
							<?php checked( '' !== (string) $v[ EventMetaKeys::SIGNUP_SHOW ] ); ?> />
						Mostrar el botón de inscripción en la portada del evento
					</label>
					<small>Desmárquelo cuando el plazo se cierre: el botón desaparece y no hay que tocar la página.</small>
				</div>

				<div class="evt-form-fila">
					<div>
						<label for="evt-signup-label">Texto del botón</label>
						<input type="text" id="evt-signup-label" name="<?php echo esc_attr( EventMetaKeys::SIGNUP_LABEL ); ?>"
							placeholder="Inscríbete" value="<?php echo esc_attr( (string) $v[ EventMetaKeys::SIGNUP_LABEL ] ); ?>" />
						<small>Lo que se lee dentro del botón. En blanco, pone «Inscríbete».</small>
					</div>
					<div>
						<label for="evt-signup-form">Formulario antiguo <span class="evt-state evt-state-draft">Histórico</span></label>
						<input type="number" id="evt-signup-form" name="<?php echo esc_attr( EventMetaKeys::SIGNUP_FORM_ID ); ?>"
							min="0" step="1" value="<?php echo esc_attr( (string) (int) $v[ EventMetaKeys::SIGNUP_FORM_ID ] ); ?>" />
						<small><strong>No lo rellene en un evento nuevo.</strong> Es el número del formulario de inscripción del sistema anterior, y está aquí solo para que los eventos migrados sigan viéndose igual. Los participantes de este aplicativo se gestionan en la pestaña «Participantes». <strong>Este campo desaparecerá.</strong></small>
					</div>
				</div>

				<div class="evt-form-campo">
					<label for="evt-signup-url">Dirección a la que lleva el botón</label>
					<input type="url" id="evt-signup-url" name="<?php echo esc_attr( EventMetaKeys::SIGNUP_URL ); ?>"
						placeholder="https://" value="<?php echo esc_attr( (string) $v[ EventMetaKeys::SIGNUP_URL ] ); ?>" />
					<small>Solo si la inscripción está fuera de este sitio. Con formulario propio, déjelo en blanco.</small>
				</div>
			</fieldset>
		<?php
		return (string) ob_get_clean();
	}












	private static function term_select( string $id, string $nombre, string $rotulo, array $terminos, int $elegido, string $ayuda ): string {
		ob_start();
		?>
		<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $rotulo ); ?></label>
		<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $nombre ); ?>">
			<option value="0">— Sin asignar —</option>
			<?php foreach ( $terminos as $term_id => $texto ) : ?>
				<option value="<?php echo esc_attr( (string) (int) $term_id ); ?>" <?php selected( (int) $term_id, $elegido ); ?>>
					<?php echo esc_html( (string) $texto ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<small><?php echo esc_html( $ayuda ); ?></small>
		<?php
		return (string) ob_get_clean();
	}
}








namespace Evt\PublicFront\View;

use Evt\Meta\EventMetaKeys;
use Evt\PublicFront\Assets;
use Evt\PublicFront\EventWorkspace;














final class EventAppearancePanel {








	private const SHAPE_SAMPLE = 'data:image/svg+xml;charset=utf8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2272%22%20height%3D%2272%22%3E%3Crect%20width%3D%2272%22%20height%3D%2272%22%20fill%3D%22%23c3cad2%22%2F%3E%3Ccircle%20cx%3D%2236%22%20cy%3D%2226%22%20r%3D%2213%22%20fill%3D%22%238a97a6%22%2F%3E%3Cpath%20d%3D%22M8%2072c0-16%2012-26%2028-26s28%2010%2028%2026z%22%20fill%3D%22%238a97a6%22%2F%3E%3C%2Fsvg%3E';







	public static function html( array $m ): string {
		$v      = (array) $m['values'];
		$subir  = (bool) $m['can_upload'];
		$medios = (array) $m['media'];



		$v[ EventMetaKeys::HEADER_BG ]   = self::color( (string) $v[ EventMetaKeys::HEADER_BG ], '#1b4f8a' );
		$v[ EventMetaKeys::HEADER_TEXT ] = self::color( (string) $v[ EventMetaKeys::HEADER_TEXT ], '#ffffff' );




		$vista         = self::preview( $m, $v );
		$color_bg      = self::color_field(
			'evt-header-bg',
			EventMetaKeys::HEADER_BG,
			'Color de fondo',
			(string) $v[ EventMetaKeys::HEADER_BG ],
			'Puede pulsar la muestra o escribir el código, por ejemplo #1b4f8a.'
		);
		$color_txt     = self::color_field(
			'evt-header-text',
			EventMetaKeys::HEADER_TEXT,
			'Color del texto',
			(string) $v[ EventMetaKeys::HEADER_TEXT ],
			'Sobre fondos oscuros, blanco (#ffffff); sobre claros, casi negro (#1b1b1b).'
		);
		$sel_titulo    = self::select(
			'evt-title-font',
			EventMetaKeys::TITLE_FONT,
			'Tipografía de los títulos',
			EventMetaKeys::fonts(),
			(string) $v[ EventMetaKeys::TITLE_FONT ],
			'La del nombre del evento y la de los encabezados.'
		);
		$sel_cuerpo    = self::select(
			'evt-body-font',
			EventMetaKeys::BODY_FONT,
			'Tipografía del texto',
			EventMetaKeys::fonts(),
			(string) $v[ EventMetaKeys::BODY_FONT ],
			'La de los párrafos. Una de palo seco se lee mejor en pantalla.'
		);
		$sel_forma     = self::select(
			'evt-image-shape',
			EventMetaKeys::IMAGE_SHAPE,
			'Forma de las fotos de personas',
			EventMetaKeys::image_shapes(),
			(string) $v[ EventMetaKeys::IMAGE_SHAPE ],
			'Afecta a las fotografías de ponentes. Redonda recorta la imagen en círculo.'
		);
		$sel_sep       = self::select(
			'evt-separator',
			EventMetaKeys::SEPARATOR,
			'Separador de la cabecera',
			EventMetaKeys::separators(),
			(string) $v[ EventMetaKeys::SEPARATOR ],
			'La silueta con la que termina la banda de arriba. «Sin separador» deja el corte recto.'
		);
		$img_logo      = self::image_field(
			'evt_logo',
			'Logo acompañante',
			self::image_of( (int) ( $medios['logo'] ?? 0 ) ),
			'Sale junto al título en la cabecera. Un PNG con fondo transparente queda mejor sobre el color de fondo.',
			$subir
		);
		$img_banner    = self::image_field(
			'evt_header_banner',
			'Banner de cabecera',
			self::image_of( (int) ( $medios['header_banner'] ?? 0 ) ),
			'Sustituye visualmente la cabecera completa solo en la portada del evento. Debe tener al menos 1920 píxeles de ancho. Al quitarla reaparecen el título, el lema, las fechas, la sede y las acciones guardadas; esos datos no se borran.',
			$subir,
			1920
		);
		$img_cartel    = self::image_field(
			'evt_poster',
			'Cartel del evento',
			self::image_of( (int) ( $medios['poster'] ?? 0 ) ),
			'El cartel completo. Se enseña en la portada del evento, y al pulsarlo se abre a tamaño completo para descargarlo o compartirlo.',
			$subir
		);
		$img_destacada = self::image_field(
			'evt_featured',
			'Imagen destacada',
			self::image_of( (int) ( $medios['featured'] ?? 0 ) ),
			'La que se ve cuando se comparte el enlace del evento y en los listados. Apaisada se recorta menos.',
			$subir
		);

		ob_start();
		?>
		<form class="evt-form" method="post" action="" enctype="multipart/form-data">
			<?php wp_nonce_field( EventWorkspace::nonce_action( EventWorkspace::PANEL_LOOK ), EventWorkspace::nonce_name( EventWorkspace::PANEL_LOOK ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_DO ); ?>" value="<?php echo esc_attr( EventWorkspace::PANEL_LOOK ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_EVENT ); ?>" value="<?php echo esc_attr( (string) (int) $m['event_id'] ); ?>" />

			<?php echo $vista; ?>

			<fieldset class="evt-tarjeta">
				<legend>Colores de la cabecera</legend>
				<p>Los dos colores de la banda de arriba de todas las páginas del evento. Elíjalos con contraste: el texto tiene que leerse sobre el fondo.</p>

				<div class="evt-form-fila">
					<div><?php echo $color_bg; ?></div>
					<div><?php echo $color_txt; ?></div>
				</div>
			</fieldset>

			<fieldset class="evt-tarjeta">
				<legend>Tipografías</legend>
				<p>Dos y no más: una para los títulos y otra para el texto. «La del tema» no carga ninguna fuente y es la opción más rápida de cargar.</p>

				<div class="evt-form-fila">
					<div><?php echo $sel_titulo; ?></div>
					<div><?php echo $sel_cuerpo; ?></div>
				</div>
			</fieldset>

			<fieldset class="evt-tarjeta">
				<legend>Forma y remate</legend>
				<p>Detalles que se aplican a todas las páginas del evento.</p>

				<div class="evt-form-fila">
					<div><?php echo $sel_forma; ?></div>
					<div><?php echo $sel_sep; ?></div>
				</div>
			</fieldset>

			<fieldset class="evt-tarjeta">
				<legend>Imágenes</legend>
				<p>
					Las cuatro salen de la biblioteca de medios del sitio: «Seleccionar o subir» abre la
					ventana nativa de WordPress, donde puede reutilizar una imagen, previsualizarla o
					arrastrar una nueva desde su equipo
					(JPG, PNG, WEBP o GIF). Nada cambia hasta que pulse «Guardar la apariencia».
				</p>

				<?php if ( ! $subir ) : ?>
					<p class="<?php echo esc_attr( Assets::alert_class( 'warning' ) ); ?>">
						Su perfil no puede subir ficheros, así que las imágenes solo se pueden quitar. Pídalo a quien administre el aplicativo.
					</p>
				<?php endif; ?>

				<?php echo $img_logo; ?>
				<?php echo $img_banner; ?>
				<?php echo $img_cartel; ?>
				<?php echo $img_destacada; ?>
			</fieldset>

			<p class="evt-acciones">
				<button class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" type="submit">Guardar la apariencia</button>
			</p>
		</form>
		<?php
		return (string) ob_get_clean();
	}












	private static function preview( array $m, array $v ): string {
		$logo   = self::image_of( (int) ( $m['media']['logo'] ?? 0 ) );
		$sep    = (string) $v[ EventMetaKeys::SEPARATOR ];
		$titulo = (string) $v[ EventWorkspace::FIELD_TITLE ];
		$fuente = (string) $v[ EventMetaKeys::TITLE_FONT ];
		$cuerpo = (string) $v[ EventMetaKeys::BODY_FONT ];
		$forma  = (string) $v[ EventMetaKeys::IMAGE_SHAPE ];

		ob_start();
		?>
		<div class="evt-preview" data-evt-preview>
			<div class="evt-preview-cabecera" data-evt-preview-header
				style="background-color:<?php echo esc_attr( (string) $v[ EventMetaKeys::HEADER_BG ] ); ?>;color:<?php echo esc_attr( (string) $v[ EventMetaKeys::HEADER_TEXT ] ); ?>">
				<?php if ( '' !== (string) ( $logo['url'] ?? '' ) ) : ?>
					<img class="evt-preview-logo" src="<?php echo esc_url( (string) $logo['url'] ); ?>" alt="" />
				<?php endif; ?>
				<p class="evt-preview-titulo<?php echo esc_attr( '' !== $fuente ? ' evt-font-' . $fuente : '' ); ?>" data-evt-preview-title>
					<?php echo esc_html( '' !== $titulo ? $titulo : 'Nombre del evento' ); ?>
				</p>
				<p class="evt-preview-lema" data-evt-preview-tagline><?php echo esc_html( (string) $v[ EventMetaKeys::TAGLINE ] ); ?></p>
				<span class="evt-preview-sep" data-evt-preview-sep>
					<?php foreach ( EventMetaKeys::separators() as $slug => $rotulo ) : ?>
						<span data-sep="<?php echo esc_attr( (string) $slug ); ?>" <?php echo esc_attr( (string) $slug === $sep ? '' : 'hidden' ); ?>>
							<?php if ( isset( EventChrome::SEPARATORS[ $slug ] ) ) : ?>
								<svg viewBox="0 0 1200 60" preserveAspectRatio="none" role="img" aria-label="<?php echo esc_attr( (string) $rotulo ); ?>">
									<path fill="currentColor" d="<?php echo esc_attr( EventChrome::SEPARATORS[ $slug ] ); ?>"></path>
								</svg>
							<?php endif; ?>
						</span>
					<?php endforeach; ?>
				</span>
			</div>
			<div class="evt-preview-cuerpo<?php echo esc_attr( '' !== $cuerpo ? ' evt-font-' . $cuerpo : '' ); ?>" data-evt-preview-body>
				<span class="<?php echo esc_attr( 'evt-shape-' . ( EventMetaKeys::SHAPE_CIRCLE === $forma ? 'circle' : 'square' ) ); ?>" data-evt-preview-shape>
					<?php ?>
					<img src="<?php echo esc_attr( self::SHAPE_SAMPLE ); ?>" width="72" height="72" alt="Ejemplo de la forma de las fotografías" />
				</span>
				Así se verá la cabecera del evento y así se recortarán las fotos de las personas. Es una muestra: no se guarda nada hasta que pulse «Guardar la apariencia».
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}








	private static function color( string $valor, string $fallback ): string {
		$limpio = sanitize_hex_color( $valor );
		return is_string( $limpio ) && '' !== $limpio ? $limpio : $fallback;
	}











	private static function image_of( int $attachment_id ): array {
		$nada = array(
			'id'     => 0,
			'url'    => '',
			'name'   => '',
			'width'  => 0,
			'height' => 0,
		);
		if ( $attachment_id <= 0 ) {
			return $nada;
		}

		$url = (string) wp_get_attachment_image_url( $attachment_id, 'medium' );
		if ( '' === $url ) {


			return $nada;
		}

		$entero  = wp_get_attachment_image_src( $attachment_id, 'full' );
		$fichero = (string) get_attached_file( $attachment_id );

		return array(
			'id'     => $attachment_id,
			'url'    => $url,
			'name'   => '' !== $fichero ? wp_basename( $fichero ) : (string) get_the_title( $attachment_id ),
			'width'  => is_array( $entero ) ? (int) $entero[1] : 0,
			'height' => is_array( $entero ) ? (int) $entero[2] : 0,
		);
	}














	private static function color_field( string $id, string $nombre, string $rotulo, string $valor, string $ayuda ): string {
		ob_start();
		?>
		<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $rotulo ); ?></label>
		<span class="evt-color">
			<input type="color" value="<?php echo esc_attr( $valor ); ?>"
				data-evt-color-for="<?php echo esc_attr( $id ); ?>"
				aria-label="<?php echo esc_attr( $rotulo . ': elegir con el selector' ); ?>" />
			<input type="text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $nombre ); ?>"
				value="<?php echo esc_attr( $valor ); ?>" pattern="#[0-9a-fA-F]{6}" placeholder="#1b4f8a"
				inputmode="text" spellcheck="false" />
		</span>
		<small><?php echo esc_html( $ayuda ); ?></small>
		<?php
		return (string) ob_get_clean();
	}












	private static function select( string $id, string $nombre, string $rotulo, array $lista, string $elegido, string $ayuda ): string {
		ob_start();
		?>
		<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $rotulo ); ?></label>
		<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $nombre ); ?>">
			<?php foreach ( $lista as $slug => $texto ) : ?>
				<option value="<?php echo esc_attr( (string) $slug ); ?>" <?php selected( (string) $slug, $elegido ); ?>>
					<?php echo esc_html( (string) $texto ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<small><?php echo esc_html( $ayuda ); ?></small>
		<?php
		return (string) ob_get_clean();
	}



















	private static function image_field( string $campo, string $rotulo, array $imagen, string $ayuda, bool $can_load, int $min_width = 0 ): string {
		$url    = (string) ( $imagen['url'] ?? '' );
		$id     = sanitize_html_class( $campo );
		$puesta = '' !== $url;
		$ancho  = (int) ( $imagen['width'] ?? 0 );
		$alto   = (int) ( $imagen['height'] ?? 0 );

		ob_start();
		?>
		<div class="evt-form-campo evt-media" data-evt-media data-evt-media-type="image"
			data-evt-media-min-width="<?php echo esc_attr( (string) $min_width ); ?>"
			data-evt-media-title="<?php echo esc_attr( $rotulo ); ?>" data-evt-media-button="Usar esta imagen">
			<span class="evt-media-rotulo"><?php echo esc_html( $rotulo ); ?></span>

			<?php ?>
			<input type="hidden" name="<?php echo esc_attr( $campo . '_id' ); ?>"
				value="<?php echo esc_attr( (string) (int) ( $imagen['id'] ?? 0 ) ); ?>" data-evt-media-value />

			<div class="evt-media-ficha" data-evt-media-card <?php echo esc_attr( $puesta ? '' : 'hidden' ); ?>>
				<img class="evt-media-miniatura" data-evt-media-thumb width="88" height="88"
					src="<?php echo esc_url( $url ); ?>" alt="" />
				<span class="evt-media-datos">
					<strong data-evt-media-name><?php echo esc_html( (string) ( $imagen['name'] ?? '' ) ); ?></strong>
					<small data-evt-media-size><?php echo esc_html( $ancho > 0 && $alto > 0 ? $ancho . ' × ' . $alto . ' px' : '' ); ?></small>
				</span>
			</div>
			<p class="evt-media-vacia" data-evt-media-empty <?php echo esc_attr( $puesta ? 'hidden' : '' ); ?>>
				Todavía no hay ninguna imagen puesta.
			</p>

			<?php if ( $can_load ) : ?>
				<p class="evt-media-drop">Arrastre una imagen hasta este campo o selecciónela en la biblioteca.</p>
				<p class="evt-media-estado" data-evt-media-status aria-live="polite"></p>
			<?php endif; ?>

			<p class="evt-acciones evt-media-botones" data-evt-media-actions hidden>
				<?php if ( $can_load ) : ?>
					<button type="button" class="<?php echo esc_attr( Assets::button_class() ); ?>"
						data-evt-media-pick aria-label="<?php echo esc_attr( 'Elegir imagen para: ' . $rotulo ); ?>">
						Seleccionar o subir
					</button>
				<?php endif; ?>
				<button type="button" class="<?php echo esc_attr( Assets::button_class() ); ?>"
					data-evt-media-clear aria-label="<?php echo esc_attr( 'Quitar la imagen de: ' . $rotulo ); ?>"
					<?php echo esc_attr( $puesta ? '' : 'hidden' ); ?>>
					Eliminar del campo
				</button>
			</p>

			<noscript>
				<?php if ( $puesta ) : ?>
					<label for="<?php echo esc_attr( $id . '-clear' ); ?>">
						<input type="checkbox" id="<?php echo esc_attr( $id . '-clear' ); ?>" name="<?php echo esc_attr( $campo . '_clear' ); ?>" value="1" />
						Quitar esta imagen al guardar
					</label>
				<?php endif; ?>
				<label for="<?php echo esc_attr( $id . '-file' ); ?>">Subir una imagen desde su equipo</label>
				<input type="file" id="<?php echo esc_attr( $id . '-file' ); ?>" name="<?php echo esc_attr( $campo . '_file' ); ?>"
					accept="image/jpeg,image/png,image/webp,image/gif" <?php disabled( ! $can_load, true ); ?> />
			</noscript>

			<small><?php echo esc_html( $ayuda ); ?></small>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}








namespace Evt\PublicFront\View;

use Evt\Meta\EventMetaKeys;
use Evt\PublicFront\Assets;
use Evt\PublicFront\CodeEditor;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\Shell;
















final class EventCodePanel {




	private const WHY_JS = 'Este recuadro solo lo ve quien administra el aplicativo. El CSS de arriba lo escribe también quien organiza el evento; esto no, porque no cambia cómo se ve una página: ejecuta un programa en el navegador de quien la visite.';







	public static function html( array $m ): string {
		$css_ok = (bool) $m['can_edit_css'];
		$js_ok  = (bool) $m['can_edit_js'];
		$codigo = (array) $m['code'];

		$bloque_css = $css_ok ? self::css_card( (string) ( $codigo['css'] ?? '' ) ) : self::missing_css();
		$bloque_js  = $js_ok ? self::js_box( (string) ( $codigo['js'] ?? '' ) ) : self::missing_js();

		ob_start();
		?>
		<form class="evt-form" method="post" action="">
			<?php wp_nonce_field( EventWorkspace::nonce_action( EventWorkspace::PANEL_CODE ), EventWorkspace::nonce_name( EventWorkspace::PANEL_CODE ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_DO ); ?>" value="<?php echo esc_attr( EventWorkspace::PANEL_CODE ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_EVENT ); ?>" value="<?php echo esc_attr( (string) (int) $m['event_id'] ); ?>" />

			<p>
				Lo que escriba aquí se aplica a <strong>todas</strong> las páginas de este evento: a la
				portada y a cada una de sus secciones. Nunca sale del evento, así que no afecta al resto
				del sitio. Cada sección puede añadir además el suyo propio, que va después de este y sirve
				para afinarlo.
			</p>

			<?php echo $bloque_css; ?>
			<?php echo $bloque_js; ?>

			<?php if ( $css_ok || $js_ok ) : ?>
				<p class="evt-acciones">
					<button class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" type="submit">Guardar el código</button>
				</p>
			<?php endif; ?>
		</form>
		<?php
		return (string) ob_get_clean();
	}







	private static function css_card( string $valor ): string {
		$campo = CodeEditor::field(
			array(
				'mode'  => CodeEditor::MODE_CSS,
				'name'  => EventMetaKeys::CUSTOM_CSS,
				'id'    => 'evt-custom-css',
				'label' => 'CSS del evento',
				'value' => $valor,
				'help'  => 'Reglas de estilo, tal cual las escribiría en una hoja: un selector, una llave y las propiedades dentro. No hace falta la etiqueta <style>, se pone sola. Deje el campo vacío para no aplicar ninguna.',
			)
		);

		ob_start();
		?>
		<fieldset class="evt-tarjeta">
			<legend>Hoja de estilos a medida</legend>
			<p>
				Para los retoques que «Apariencia» no cubre. Se imprime en la cabecera de cada página del
				evento, la última de todas, así que entre dos reglas iguales gana la suya. Si aun así algo
				no cambia, es que la regla del aplicativo apunta más fino: escriba la suya con el mismo
				detalle. Y si el editor no la acepta, mire su margen izquierdo: el comprobador señala ahí
				los errores de sintaxis.
			</p>
			<?php echo $campo; ?>
		</fieldset>
		<?php
		return (string) ob_get_clean();
	}







	private static function js_box( string $valor ): string {
		$campo = CodeEditor::field(
			array(
				'mode'  => CodeEditor::MODE_JS,
				'name'  => EventMetaKeys::CUSTOM_JS,
				'id'    => 'evt-custom-js',
				'label' => 'JavaScript del evento',
				'value' => $valor,
				'help'  => 'Código JavaScript, sin la etiqueta <script>: se pone sola. Se ejecuta al final de la página, con el documento ya cargado. Deje el campo vacío para no ejecutar nada.',
			)
		);

		ob_start();
		?>
		<p class="<?php echo esc_attr( Assets::alert_class( 'warning' ) ); ?>">
			<strong>Esto se ejecuta en el navegador de quien visite las páginas de este evento.</strong>
			No es un ajuste de aspecto: es un programa, y puede leer y cambiar lo que la persona ve, seguir
			lo que hace o pedir datos a otros sitios. Pegue aquí únicamente código que entienda y del que
			responda; si lo copia de un tercero, léalo entero antes.
		</p>
		<?php
		$aviso = (string) ob_get_clean();

		return Shell::admin_box( 'JavaScript a medida', $aviso . $campo, self::WHY_JS );
	}









	private static function missing_css(): string {
		ob_start();
		?>
		<fieldset class="evt-tarjeta">
			<legend>Hoja de estilos a medida</legend>
			<p>
				Su perfil no puede editar el CSS de este evento, así que el campo no se enseña. El que ya
				hubiera guardado sigue aplicándose tal cual: no se ha perdido nada.
			</p>
		</fieldset>
		<?php
		return (string) ob_get_clean();
	}











	private static function missing_js(): string {
		ob_start();
		?>
		<p>
			Su perfil no puede editar el JavaScript de este evento, así que ese campo no aparece: la
			pestaña está completa y no le falta nada. El JavaScript ejecuta un programa en el navegador de
			cada visitante, y por eso lo escribe solo quien administra el aplicativo. Si su evento
			necesita uno, pídalo indicando qué tiene que hacer.
		</p>
		<?php
		return (string) ob_get_clean();
	}
}








namespace Evt\PublicFront\View;

use Evt\PublicFront\Assets;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\Shell;
use Evt\Taxonomy\EventTaxonomies;








final class EventWorkspaceView {







	public static function html( array $m ): string {
		if ( '' !== (string) $m['aviso'] ) {
			return Shell::render(
				'Taller del evento',
				'',
				Shell::notice( (string) $m['aviso_tipo'], (string) $m['aviso'] ) . self::back_link( $m )
			);
		}

		$flash = (array) $m['flash'];
		$panel = (string) $m['panel'];




		if ( true === $m['nuevo'] ) {
			return self::new_event( $m );
		}

		ob_start();
		echo self::head( $m ); 
		echo self::tabs( $m ); 

		if ( '' !== (string) $flash['texto'] ) {
			echo Shell::notice( (string) $flash['tipo'], (string) $flash['texto'] ); 
		}

		echo self::archived_notice( $m ); 




		if ( EventWorkspace::PANEL_SETTINGS === $panel ) {
			echo self::archive_switch( $m ); 
		}






		$cerrado = true !== $m['can_edit'];
		if ( $cerrado ) {
			echo '<fieldset class="evt-solo-lectura" disabled><legend class="screen-reader-text">Evento en solo lectura</legend>';
		}

		if ( EventWorkspace::PANEL_SPEAKERS === $panel ) {
			echo EventSpeakersPanel::html( $m ); 
		} elseif ( EventWorkspace::PANEL_PROGRAMME === $panel ) {
			echo EventProgrammePanel::html( $m ); 
		} elseif ( EventWorkspace::PANEL_WORKSHOPS === $panel ) {
			echo EventWorkshopsPanel::html( $m ); 
		} elseif ( EventWorkspace::PANEL_SIGNUP === $panel ) {
			echo EventSignupPanel::html( $m ); 
		} elseif ( EventWorkspace::PANEL_PEOPLE === $panel ) {
			echo EventParticipantsPanel::html( $m ); 
		} elseif ( EventWorkspace::PANEL_SETTINGS === $panel ) {
			echo EventDataPanel::html( $m ); 
		} elseif ( EventWorkspace::PANEL_LOOK === $panel ) {
			echo EventAppearancePanel::html( $m ); 
		} elseif ( EventWorkspace::PANEL_CODE === $panel ) {


			echo EventCodePanel::html( $m ); 
		} else {
			echo EventSectionsPanel::html( $m ); 
		}

		if ( $cerrado ) {
			echo '</fieldset>';
		}

		return Shell::render( '', '', (string) ob_get_clean() );
	}







	private static function new_event( array $m ): string {
		$flash = (array) $m['flash'];

		ob_start();
		?>
		<p class="evt-sub"><a href="<?php echo esc_url( (string) $m['events_url'] ); ?>">&larr; Todos los eventos</a></p>
		<div class="evt-h1-fila">
			<h1 class="evt-h1">Crear evento</h1>
		</div>
		<p class="evt-sub">
			Con el título y la fecha de inicio basta para crearlo. Nace en
			borrador y no se ve fuera hasta que lo publique; sus páginas, sus
			ponentes y su programa se añaden después, con el evento ya abierto.
		</p>
		<?php
		if ( '' !== (string) $flash['texto'] ) {
			echo Shell::notice( (string) $flash['tipo'], (string) $flash['texto'] ); 
		}
		echo EventDataPanel::html( $m ); 

		return Shell::render( '', '', (string) ob_get_clean() );
	}










	private static function archived_notice( array $m ): string {
		if ( true !== $m['archived'] ) {
			return '';
		}
		$texto = true === $m['can_edit']
			? 'Este evento está marcado como histórico: su área ya no puede editarlo. Usted sí, porque administra el aplicativo.'
			: 'Este evento está marcado como histórico: se puede consultar y exportar, pero ya no se edita. Para volver a abrirlo, pídalo a quien administre el aplicativo.';

		return Shell::notice( 'aviso', $texto );
	}














	private static function archive_switch( array $m ): string {
		if ( true === $m['can_unarchive'] ) {
			return Shell::admin_box(
				'Volver a abrir el evento',
				self::archive_form( $m, false ),
				'Cerrar un evento lo hace su área; volver a abrirlo, solo quien administra el aplicativo.'
			);
		}
		if ( true !== $m['can_archive'] ) {
			return '';
		}

		ob_start();
		?>
		<section class="evt-tarjeta">
			<h2>Dar el evento por terminado</h2>
			<p>Cuando ya no quede nada que tocar —los vídeos subidos, las presentaciones colgadas, las erratas corregidas—, márquelo como histórico y quedará cerrado tal y como está. Seguirá entrando a consultarlo y a exportarlo, y la página pública se verá igual que siempre; lo que ya no podrá es cambiar nada, ni de él ni de sus secciones. <strong>Para volver a abrirlo tendrá que pedírselo a quien administre el aplicativo.</strong></p>
			<?php echo self::archive_form( $m, true ); ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}












	private static function archive_form( array $m, bool $marcar ): string {
		$op       = $marcar ? EventWorkspace::OP_ARCHIVE : EventWorkspace::OP_UNARCHIVE;
		$rotulo   = $marcar ? 'Marcar como histórico' : 'Volver a abrir el evento';
		$pregunta = $marcar
			? sprintf( '¿Marcar «%s» como histórico? Dejará de poder editarlo, a él y a todas sus secciones, y no hay vuelta atrás: solo quien administre el aplicativo puede volver a abrirlo. La página pública no cambia.', (string) $m['title'] )
			: '¿Volver a abrir este evento? Su área podrá editarlo otra vez.';

		ob_start();
		?>
		<?php if ( ! $marcar ) : ?>
			<p><?php echo esc_html( 'Ahora mismo está cerrado a edición: el área que lo organizó puede entrar, consultarlo y exportarlo, pero no cambiar nada. La página pública se ve igual que siempre.' ); ?></p>
		<?php endif; ?>
		<form class="evt-accion" method="post" action=""
			data-evt-confirm="<?php echo esc_attr( $pregunta ); ?>"
			data-evt-confirm-ok="<?php echo esc_attr( $rotulo ); ?>">
			<?php wp_nonce_field( EventWorkspace::nonce_action( $op ), EventWorkspace::nonce_name( $op ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_EVENT ); ?>" value="<?php echo esc_attr( (string) (int) $m['event_id'] ); ?>" />
			<button type="submit" class="<?php echo esc_attr( Assets::button_class() ); ?>" title="<?php echo esc_attr( $rotulo ); ?>"><?php echo esc_html( $rotulo ); ?></button>
		</form>
		<?php
		return (string) ob_get_clean();
	}







	private static function head( array $m ): string {
		ob_start();
		?>
		<p class="evt-sub"><a href="<?php echo esc_url( (string) $m['events_url'] ); ?>">&larr; Todos los eventos</a></p>
		<div class="evt-h1-fila">
			<h1 class="evt-h1"><?php echo esc_html( '' !== (string) $m['title'] ? (string) $m['title'] : 'Evento sin título' ); ?></h1>
			<span class="<?php echo esc_attr( Assets::state_class( (string) $m['state'] ) ); ?>"><?php echo esc_html( (string) $m['state_label'] ); ?></span>
			<span class="<?php echo esc_attr( 'publish' === (string) $m['status'] ? 'evt-state evt-state-publish' : 'evt-state evt-state-draft' ); ?>"><?php echo esc_html( (string) $m['status_label'] ); ?></span>
			<span class="evt-acciones">
				<?php if ( '' !== (string) $m['view_url'] ) : ?>
					<a class="<?php echo esc_attr( Assets::button_class() ); ?>" href="<?php echo esc_url( (string) $m['view_url'] ); ?>">Ver la página</a>
				<?php endif; ?>
			</span>
		</div>
		<p class="evt-sub"><?php echo esc_html( 'Área: ' . self::area_names( (array) $m['area_ids'] ) ); ?></p>
		<?php
		return (string) ob_get_clean();
	}







	private static function tabs( array $m ): string {
		$activa = (string) $m['panel'];

		ob_start();
		?>
		<nav class="evt-tabs" aria-label="Paneles del evento">
			<div class="evt-tabs-fila">
				<?php foreach ( (array) $m['panels'] as $clave => $panel ) : ?>
					<a class="evt-tab<?php echo $clave === $activa ? ' evt-tab-on' : ''; ?>"
						<?php echo $clave === $activa ? ' aria-current="page"' : ''; ?>
						href="<?php echo esc_url( (string) $panel['url'] ); ?>"><?php echo esc_html( (string) $panel['label'] ); ?>
						<?php


						if ( null !== ( $panel['count'] ?? null ) ) :
							?>
							<span class="evt-tab-n"><?php echo esc_html( (string) (int) $panel['count'] ); ?></span>
						<?php endif; ?></a>
				<?php endforeach; ?>
			</div>
		</nav>
		<?php
		return (string) ob_get_clean();
	}







	private static function area_names( array $ids ): string {
		$nombres = array();
		foreach ( $ids as $term_id ) {
			$term = get_term( (int) $term_id, EventTaxonomies::AREA );
			if ( $term instanceof \WP_Term ) {
				$nombres[] = $term->name;
			}
		}
		return array() === $nombres ? 'Sin área' : implode( ' · ', $nombres );
	}







	private static function back_link( array $m ): string {
		$url = (string) $m['events_url'];
		if ( '' === $url ) {
			return '';
		}
		return '<p><a class="' . esc_attr( Assets::button_class( true ) ) . '" href="' . esc_url( $url ) . '">Ver mis eventos</a></p>';
	}
}








namespace Evt\PublicFront;

use Evt\Access\EventAccess;
use Evt\Domain\EventInput;
use Evt\Meta\EventMetaKeys;
use Evt\PostType\EventPostType;
use Evt\PublicFront\View\PageFormView;






























final class PageForm {

	public const SHORTCODE = 'evt_page_form';




	public const NONCE_ACTION = 'evt_page_save';




	public const NONCE_FIELD = 'evt_page_nonce';






	public const LOOK_KEYS = array(
		EventMetaKeys::HEADER_BG,
		EventMetaKeys::HEADER_TEXT,
		EventMetaKeys::SEPARATOR,
		EventMetaKeys::LOGO_ID,
		EventMetaKeys::IMAGE_SHAPE,
		EventMetaKeys::TITLE_FONT,
		EventMetaKeys::BODY_FONT,
	);










	private static $rejected = array(
		'message' => '',
		'errors'  => array(),
	);






	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'render' ) );


		add_action( 'init', array( self::class, 'maybe_handle_submit' ), 20 );
	}






	public static function maybe_handle_submit(): void {
		if ( ! self::is_our_submit() || ! is_user_logged_in() ) {
			return;
		}
		if ( ! self::nonce_ok() ) {
			self::$rejected['message'] = 'El formulario estuvo abierto demasiado tiempo y el envío caducó. Vuelva a enviarlo.';
			return;
		}


		$raw      = wp_unslash( $_POST );
		$user_id  = get_current_user_id();
		$page_id  = self::int_of( $raw, 'evt_page_id' );
		$event_id = self::target_event( $raw, $page_id );

		if ( ! self::is_event_root( $event_id ) ) {
			self::$rejected['message'] = 'No se sabe de qué evento cuelga esta sección. Ábrala desde su evento.';
			return;
		}


		if ( ! EventAccess::can_edit( $user_id, $event_id ) ) {
			self::$rejected['message'] = EventAccess::why_not_editable( $user_id, $event_id );
			return;
		}



		EditLock::require_available( $event_id );

		$fields = self::submitted_fields( $raw );



		$tipo  = $page_id > 0
			? (string) get_post_meta( $page_id, EventMetaKeys::SECTION_TYPE, true )
			: $fields['section_type'];
		$check = EventInput::validate(
			array(
				'title'        => $fields['title'],
				'section_type' => $tipo,
				'parent'       => $event_id,
			)
		);
		if ( ! $check['ok'] ) {
			self::$rejected = array(
				'message' => self::error_message( $check['errors'] ),
				'errors'  => $check['errors'],
			);
			return;
		}

		$saved = self::save( $user_id, $event_id, $page_id, $check['data'], $fields );
		if ( is_wp_error( $saved ) ) {
			self::$rejected['message'] = $saved->get_error_message();
			return;
		}

		self::leave_saved( $page_id, (int) $saved );
	}








	private static function leave_saved( int $page_id, int $saved ): void {
		$destino = Shell::url(
			'section',
			array(
				'seccion'   => $saved,
				'evt_hecho' => 0 === $page_id ? 'creada' : 'guardada',
			)
		);
		Shell::leave( '' !== $destino ? $destino : Shell::back_url( 'events' ) );
	}













	private static function target_event( array $raw, int $page_id ): int {
		return $page_id > 0 ? self::parent_of( $page_id ) : self::int_of( $raw, 'evt_page_event' );
	}








	private static function int_of( array $raw, string $key ): int {
		return isset( $raw[ $key ] ) ? max( 0, (int) $raw[ $key ] ) : 0;
	}











	private static function save( int $user_id, int $event_id, int $page_id, array $data, array $fields ) {
		$title   = (string) $data['title'];
		$postarr = array(
			'post_type'    => EventPostType::POST_TYPE,
			'post_parent'  => $event_id,
			'post_title'   => $title,


			'post_name'    => sanitize_title( '' !== $fields['slug'] ? $fields['slug'] : $title ),
			'post_content' => $fields['content'],
			'menu_order'   => $fields['menu_order'],
		);

		if ( $page_id > 0 ) {
			$postarr['ID'] = $page_id;
			$result        = wp_update_post( wp_slash( $postarr ), true );
		} else {


			$postarr['post_status'] = 'draft';
			$postarr['post_author'] = $user_id;
			$result                 = wp_insert_post( wp_slash( $postarr ), true );
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$id = (int) $result;











		if ( 0 === $page_id ) {
			update_post_meta( $id, EventMetaKeys::SECTION_TYPE, (string) $data['section_type'] );
		}

		foreach ( self::LOOK_KEYS as $clave ) {
			$valor = (string) ( $fields['look'][ $clave ] ?? '' );
			if ( '' === $valor || '0' === $valor ) {

				delete_post_meta( $id, $clave );
				continue;
			}
			update_post_meta( $id, $clave, $valor );
		}

		self::save_code( $user_id, $event_id, $id, $fields );

		return $id;
	}
















	private static function save_code( int $user_id, int $event_id, int $page_id, array $fields ): void {
		foreach ( self::code_allowed( $user_id, $event_id ) as $clave => $puede ) {
			if ( ! $puede ) {
				continue;
			}






			update_post_meta( $page_id, $clave, wp_slash( (string) ( $fields['code'][ $clave ] ?? '' ) ) );
		}
	}











	private static function code_allowed( int $user_id, int $event_id ): array {
		return array(
			EventMetaKeys::CUSTOM_CSS => EventAccess::can_edit_custom_css( $user_id, $event_id ),
			EventMetaKeys::CUSTOM_JS  => EventAccess::can_edit_custom_js( $user_id, $event_id ),
		);
	}






	public static function model(): array {
		$m = array(
			'aviso'     => '',
			'hecho'     => '',
			'error'     => self::$rejected['message'],
			'errors'    => self::$rejected['errors'],
			'event_id'  => 0,
			'event'     => '',
			'event_url' => '',
			'page_id'   => 0,
			'status'    => 'draft',
			'types'     => EventMetaKeys::section_types(),
			'code'      => array_fill_keys( EventMetaKeys::code_keys(), false ),
			'lock'      => EditLock::none(),
			'values'    => self::defaults(),
		);

		if ( ! is_user_logged_in() ) {
			$m['aviso'] = 'Debe iniciar sesión con su usuario para editar una sección.';
			return $m;
		}

		$user_id = get_current_user_id();
		$page_id = self::query_id( 'seccion' );

		if ( $page_id > 0 && self::is_event_root( $page_id ) ) {
			$m['aviso'] = 'Eso es la portada del evento, no una de sus secciones: se edita en el taller del evento.';
			return $m;
		}

		$event_id = $page_id > 0 ? self::parent_of( $page_id ) : self::query_id( 'evento' );
		if ( ! self::is_event_root( $event_id ) ) {
			$m['aviso'] = 'Abra la sección desde el evento al que pertenece: así se sabe de cuál cuelga.';
			return $m;
		}
		if ( ! EventAccess::can_edit( $user_id, $event_id ) ) {
			$m['aviso'] = EventAccess::why_not_editable( $user_id, $event_id );
			return $m;
		}

		$m['event_id']  = $event_id;
		$m['event']     = (string) get_post_field( 'post_title', $event_id );
		$m['event_url'] = Shell::url(
			'event',
			array(
				'evento' => $event_id,
				'panel'  => 'secciones',
			)
		);
		$m['page_id']   = $page_id;
		$m['hecho']     = self::done_notice();
		$m['code']      = self::code_allowed( $user_id, $event_id );



		$m['lock'] = EditLock::status( $event_id, true );

		if ( $page_id > 0 ) {
			$m['status'] = (string) get_post_status( $page_id );
			$m['values'] = self::stored_values( $page_id );
		}



		$enviado = self::submitted_values();
		if ( null !== $enviado ) {
			$m['values'] = $enviado;
		}

		$m['values']['code'] = self::readable_code( $m['code'], (array) $m['values']['code'] );

		return $m;
	}












	private static function readable_code( array $puede, array $valores ): array {
		foreach ( $puede as $clave => $ok ) {
			if ( ! $ok ) {
				$valores[ $clave ] = '';
			}
		}
		return $valores;
	}







	public static function render( $atts = array() ): string {
		unset( $atts );
		Assets::enqueue();
		$m    = self::model();
		$lock = (array) $m['lock'];
		$html = PageFormView::html( $m );




		if ( (int) $lock['owner'] > 0 ) {
			$html = '<fieldset class="evt-solo-lectura" disabled>'
				. '<legend class="screen-reader-text">Sección en solo lectura: otra persona tiene abierto este evento</legend>'
				. $html . '</fieldset>';
		}

		return EditLock::render( EditLock::claim( $lock ) ) . $html;
	}






	private static function defaults(): array {
		$look = array();
		foreach ( self::LOOK_KEYS as $clave ) {
			$look[ $clave ] = '';
		}
		return array(
			'title'        => '',
			'slug'         => '',
			'section_type' => '',
			'menu_order'   => 0,
			'content'      => '',
			'look'         => $look,
			'code'         => array_fill_keys( EventMetaKeys::code_keys(), '' ),
		);
	}







	private static function stored_values( int $page_id ): array {
		$look = array();
		foreach ( self::LOOK_KEYS as $clave ) {
			$look[ $clave ] = (string) get_post_meta( $page_id, $clave, true );
		}
		$code = array();
		foreach ( EventMetaKeys::code_keys() as $clave ) {
			$code[ $clave ] = (string) get_post_meta( $page_id, $clave, true );
		}
		return array(
			'title'        => (string) get_post_field( 'post_title', $page_id ),
			'slug'         => (string) get_post_field( 'post_name', $page_id ),
			'section_type' => (string) get_post_meta( $page_id, EventMetaKeys::SECTION_TYPE, true ),
			'menu_order'   => (int) get_post_field( 'menu_order', $page_id ),
			'content'      => (string) get_post_field( 'post_content', $page_id ),
			'look'         => $look,
			'code'         => $code,
		);
	}











	private static function submitted_fields( array $raw ): array {
		$look = array();
		foreach ( self::LOOK_KEYS as $clave ) {
			$look[ $clave ] = isset( $raw[ $clave ] ) ? sanitize_text_field( (string) $raw[ $clave ] ) : '';
		}
		$code = array();
		foreach ( EventMetaKeys::code_keys() as $clave ) {


			$code[ $clave ] = isset( $raw[ $clave ] ) ? (string) $raw[ $clave ] : '';
		}

		return array(
			'title'        => isset( $raw['evt_title'] ) ? sanitize_text_field( (string) $raw['evt_title'] ) : '',
			'slug'         => isset( $raw['evt_slug'] ) ? sanitize_title( (string) $raw['evt_slug'] ) : '',
			'section_type' => isset( $raw[ EventMetaKeys::SECTION_TYPE ] ) ? sanitize_key( (string) $raw[ EventMetaKeys::SECTION_TYPE ] ) : '',
			'menu_order'   => isset( $raw['evt_order'] ) ? (int) $raw['evt_order'] : 0,

			'content'      => isset( $raw['evt_content'] ) ? wp_kses_post( (string) $raw['evt_content'] ) : '',
			'look'         => $look,
			'code'         => $code,
		);
	}






	private static function submitted_values(): ?array {
		if ( ! self::is_our_submit() || ! self::nonce_ok() ) {
			return null;
		}

		return self::submitted_fields( wp_unslash( $_POST ) );
	}






	private static function is_our_submit(): bool {

		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_METHOD'] ) ) ) : '';

		return 'POST' === $method && ! empty( $_POST['evt_page_form'] );
	}






	private static function nonce_ok(): bool {
		$nonce = isset( $_POST[ self::NONCE_FIELD ] )
			? sanitize_text_field( wp_unslash( (string) $_POST[ self::NONCE_FIELD ] ) )
			: '';
		return '' !== $nonce && false !== wp_verify_nonce( $nonce, self::NONCE_ACTION );
	}







	private static function query_id( string $name ): int {

		$value = isset( $_GET[ $name ] ) ? (int) $_GET[ $name ] : 0;
		return max( 0, $value );
	}






	private static function done_notice(): string {

		$que    = isset( $_GET['evt_hecho'] ) ? sanitize_key( wp_unslash( (string) $_GET['evt_hecho'] ) ) : '';
		$textos = array(
			'creada'   => 'Sección creada, en borrador: todavía no la ve nadie. Escriba el contenido y publíquela desde el evento cuando esté lista.',
			'guardada' => 'Cambios guardados.',
		);
		return (string) ( $textos[ $que ] ?? '' );
	}







	private static function error_message( array $codes ): string {
		$textos = array(
			'title'        => 'Escriba el título de la sección.',
			'section_type' => 'Elija el tipo de sección de la lista.',
		);
		$dichos = array();
		foreach ( $codes as $code ) {
			if ( isset( $textos[ $code ] ) ) {
				$dichos[] = $textos[ $code ];
			}
		}
		return array() === $dichos ? 'Revise los campos marcados.' : implode( ' ', $dichos );
	}







	private static function parent_of( int $page_id ): int {
		if ( $page_id <= 0 || EventPostType::POST_TYPE !== get_post_type( $page_id ) ) {
			return 0;
		}
		return (int) get_post_field( 'post_parent', $page_id );
	}







	private static function is_event_root( int $post_id ): bool {
		if ( $post_id <= 0 || EventPostType::POST_TYPE !== get_post_type( $post_id ) ) {
			return false;
		}
		return 0 === (int) get_post_field( 'post_parent', $post_id );
	}
}








namespace Evt\PublicFront\View;

use Evt\Meta\EventMetaKeys;
use Evt\PublicFront\Assets;
use Evt\PublicFront\CodeEditor;
use Evt\PublicFront\PageForm;
use Evt\PublicFront\Shell;




final class PageFormView {







	public static function html( array $m ): string {
		if ( '' !== (string) $m['aviso'] ) {
			return Shell::render(
				'Sección del evento',
				'',
				Shell::notice( 'aviso', (string) $m['aviso'] ) . self::back_to_events()
			);
		}

		$nueva = 0 === (int) $m['page_id'];

		return Shell::render(
			$nueva ? 'Nueva sección' : 'Editar la sección',
			self::subtitle( $m, $nueva ),
			self::form( $m, $nueva )
		);
	}








	private static function subtitle( array $m, bool $nueva ): string {
		if ( $nueva ) {
			return sprintf( 'Del evento «%s». Nace en borrador: no la ve nadie hasta que la publique.', (string) $m['event'] );
		}
		$estado = 'publish' === (string) $m['status'] ? 'Publicada' : 'En borrador';
		return sprintf( 'Del evento «%s» · %s', (string) $m['event'], $estado );
	}








	private static function form( array $m, bool $nueva ): string {
		$valores = (array) $m['values'];
		$errores = (array) $m['errors'];

		ob_start();
		echo Shell::notice( 'ok', (string) $m['hecho'] );    
		echo Shell::notice( 'error', (string) $m['error'] ); 
		?>
		<form class="evt-form" method="post" action="">
			<input type="hidden" name="evt_page_form" value="1" />
			<input type="hidden" name="evt_page_event" value="<?php echo esc_attr( (string) $m['event_id'] ); ?>" />
			<?php if ( ! $nueva ) : ?>
				<input type="hidden" name="evt_page_id" value="<?php echo esc_attr( (string) $m['page_id'] ); ?>" />
			<?php endif; ?>
			<?php wp_nonce_field( PageForm::NONCE_ACTION, PageForm::NONCE_FIELD ); ?>

			<fieldset class="evt-tarjeta">
				<legend>La sección</legend>

				<div class="evt-form-fila">
					<div>
						<?php if ( $nueva ) : ?>
							<label for="evt_section_type">Tipo de sección</label>
							<select id="evt_section_type" name="<?php echo esc_attr( EventMetaKeys::SECTION_TYPE ); ?>" required>
								<option value="">— Elija el tipo —</option>
								<?php foreach ( (array) $m['types'] as $slug => $rotulo ) : ?>
									<option value="<?php echo esc_attr( (string) $slug ); ?>" <?php selected( (string) $valores['section_type'], (string) $slug ); ?>>
										<?php echo esc_html( (string) $rotulo ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<small>Se elige ahora y no se cambia después: el tipo decide qué elementos
								lleva la sección.</small>
							<?php self::field_error( $errores, array( 'section_type' ), 'Elija el tipo de sección de la lista.' ); ?>
						<?php else : ?>
							<?php
							$tipos  = (array) $m['types'];
							$actual = (string) $valores['section_type'];
							$rotulo = (string) ( $tipos[ $actual ] ?? $actual );
							?>
							<span class="evt-rotulo">Tipo de sección</span>
							<p class="evt-fijo">
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
									stroke-width="2" aria-hidden="true" focusable="false"><rect x="3" y="11"
									width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
								<strong><?php echo esc_html( '' !== $rotulo ? $rotulo : 'Sin tipo' ); ?></strong>
							</p>
							<small>No se cambia: una sección de programa y una de contacto no llevan lo
								mismo dentro. Si se equivocó, envíe esta a la papelera y cree la que
								quería.</small>
						<?php endif; ?>
					</div>
					<div>
						<label for="evt_order">Orden</label>
						<input type="number" id="evt_order" name="evt_order" step="1" min="0"
							value="<?php echo esc_attr( (string) $valores['menu_order'] ); ?>" />
						<small>El lugar que ocupa en el menú del evento. El número más bajo va primero.</small>
					</div>
				</div>

				<div class="evt-form-campo">
					<label for="evt_title">Título de la sección</label>
					<input type="text" id="evt_title" name="evt_title" required data-evt-slug-source
						value="<?php echo esc_attr( (string) $valores['title'] ); ?>" />
					<?php self::field_error( $errores, array( 'title' ), 'Escriba el título de la sección.' ); ?>
				</div>

				<div class="evt-form-campo">
					<label for="evt_slug">Dirección de la página</label>
					<input type="text" id="evt_slug" name="evt_slug" data-evt-slug-target
						value="<?php echo esc_attr( (string) $valores['slug'] ); ?>" />
					<small>
						Es el final de la dirección de la sección, y se propone a partir del título mientras no
						la escriba usted. Cambiar la de una sección ya publicada rompe los enlaces que apuntan a ella.
					</small>
				</div>
			</fieldset>

			<fieldset class="evt-tarjeta">
				<legend>Contenido</legend>
				<?php


				wp_editor(
					(string) $valores['content'],
					'evt_page_content',
					array(
						'textarea_name' => 'evt_content',
						'textarea_rows' => 14,
						'media_buttons' => true,
					)
				);
				?>
			</fieldset>

			<?php echo self::look( (array) $valores['look'] ); ?>

			<?php echo self::code( (array) $m['code'], (array) $valores['code'] ); ?>

			<p class="evt-acciones">
				<button class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" type="submit">
					<?php echo esc_html( $nueva ? 'Crear la sección' : 'Guardar los cambios' ); ?>
				</button>
				<?php if ( '' !== (string) $m['event_url'] ) : ?>
					<a class="<?php echo esc_attr( Assets::button_class() ); ?>" href="<?php echo esc_url( (string) $m['event_url'] ); ?>">
						Volver a las secciones del evento
					</a>
				<?php endif; ?>
			</p>
		</form>
		<?php
		return (string) ob_get_clean();
	}










	private static function look( array $look ): string {
		ob_start();
		?>
		<details class="evt-tarjeta">
			<summary>Apariencia de esta sección</summary>
			<p>Lo que deje en blanco se hereda del evento. Solo hace falta tocarlo cuando esta sección tenga que verse distinta.</p>

			<div class="evt-form-fila">
				<?php
				echo self::color_field( EventMetaKeys::HEADER_BG, 'Color de fondo de la cabecera', (string) ( $look[ EventMetaKeys::HEADER_BG ] ?? '' ) );    
				echo self::color_field( EventMetaKeys::HEADER_TEXT, 'Color del texto de la cabecera', (string) ( $look[ EventMetaKeys::HEADER_TEXT ] ?? '' ) ); 
				?>
			</div>

			<div class="evt-form-fila">
				<?php
				echo self::select_field( EventMetaKeys::TITLE_FONT, 'Tipografía de los títulos', EventMetaKeys::fonts(), (string) ( $look[ EventMetaKeys::TITLE_FONT ] ?? '' ) ); 
				echo self::select_field( EventMetaKeys::BODY_FONT, 'Tipografía del cuerpo', EventMetaKeys::fonts(), (string) ( $look[ EventMetaKeys::BODY_FONT ] ?? '' ) );      
				?>
			</div>

			<div class="evt-form-fila">
				<?php
				echo self::select_field( EventMetaKeys::IMAGE_SHAPE, 'Forma de las imágenes de personas', EventMetaKeys::image_shapes(), (string) ( $look[ EventMetaKeys::IMAGE_SHAPE ] ?? '' ), 'Sin elegir' ); 
				echo self::select_field( EventMetaKeys::SEPARATOR, 'Separador al pie de la cabecera', EventMetaKeys::separators(), (string) ( $look[ EventMetaKeys::SEPARATOR ] ?? '' ) );                       
				?>
			</div>

			<?php echo self::logo_field( (int) ( $look[ EventMetaKeys::LOGO_ID ] ?? 0 ) ); ?>
		</details>
		<?php
		return (string) ob_get_clean();
	}


















	private static function code( array $puede, array $valores ): string {
		$css_ok = ! empty( $puede[ EventMetaKeys::CUSTOM_CSS ] );
		$js_ok  = ! empty( $puede[ EventMetaKeys::CUSTOM_JS ] );
		$html   = '';

		if ( $css_ok ) {
			$campo = CodeEditor::field(
				array(
					'mode'  => CodeEditor::MODE_CSS,
					'name'  => EventMetaKeys::CUSTOM_CSS,
					'label' => 'CSS de esta sección',
					'help'  => 'Se aplica solo a esta página y después del CSS del evento, así que sirve para afinar lo que herede de él. No sale de las páginas de este evento.',
					'value' => (string) ( $valores[ EventMetaKeys::CUSTOM_CSS ] ?? '' ),
				)
			);

			ob_start();
			?>
			<fieldset class="evt-tarjeta">
				<legend>CSS a medida de esta sección</legend>
				<p>Para los retoques que «Apariencia de esta sección» no cubre. Déjelo vacío si no hace falta ninguno.</p>
				<?php echo $campo; ?>
				<?php if ( ! $js_ok ) : ?>
					<p>
						El JavaScript a medida de esta sección no aparece aquí porque solo lo escribe quien
						administra el aplicativo: ejecuta un programa en el navegador de cada visitante. No le
						falta nada más.
					</p>
				<?php endif; ?>
			</fieldset>
			<?php
			$html .= (string) ob_get_clean();
		}

		if ( $js_ok ) {
			$html .= Shell::admin_box(
				'JavaScript a medida de esta sección',
				CodeEditor::field(
					array(
						'mode'  => CodeEditor::MODE_JS,
						'name'  => EventMetaKeys::CUSTOM_JS,
						'label' => 'JavaScript de esta sección',
						'help'  => 'Se ejecuta solo en esta página y después del JavaScript del evento. Lo ejecuta el navegador de cada persona que la visite: un error aquí le rompe la página a todo el mundo, así que pruébelo antes de publicar la sección.',
						'value' => (string) ( $valores[ EventMetaKeys::CUSTOM_JS ] ?? '' ),
					)
				),
				'El JavaScript a medida se ejecuta en el navegador de quien visite esta página, así que solo lo escribe quien administra el aplicativo.'
			);
		}

		return $html;
	}













	private static function color_field( string $key, string $label, string $value ): string {
		ob_start();
		?>
		<div class="evt-form-campo">
			<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
			<div class="evt-color">
				<input type="color" aria-label="<?php echo esc_attr( sprintf( '%s: elegirlo con el selector', $label ) ); ?>"
					data-evt-color-for="<?php echo esc_attr( $key ); ?>"
					value="<?php echo esc_attr( '' !== $value ? $value : '#ffffff' ); ?>" />
				<input type="text" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>"
					inputmode="text" maxlength="7" pattern="#[0-9a-fA-F]{6}" placeholder="#1b4f8a"
					value="<?php echo esc_attr( $value ); ?>" />
			</div>
			<small>Hexadecimal, con la almohadilla. En blanco, el del evento.</small>
		</div>
		<?php
		return (string) ob_get_clean();
	}











	private static function select_field( string $key, string $label, array $options, string $value, string $blank = '' ): string {
		ob_start();
		?>
		<div class="evt-form-campo">
			<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
			<select id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>">
				<?php if ( '' !== $blank ) : ?>
					<option value=""><?php echo esc_html( $blank ); ?></option>
				<?php endif; ?>
				<?php foreach ( $options as $slug => $rotulo ) : ?>
					<option value="<?php echo esc_attr( (string) $slug ); ?>" <?php selected( $value, (string) $slug ); ?>>
						<?php echo esc_html( (string) $rotulo ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
		return (string) ob_get_clean();
	}







	private static function logo_field( int $logo_id ): string {
		$miniatura = $logo_id > 0 ? (string) wp_get_attachment_image_url( $logo_id, 'thumbnail' ) : '';

		ob_start();
		?>
		<div class="evt-form-campo">
			<label for="<?php echo esc_attr( EventMetaKeys::LOGO_ID ); ?>">Logo acompañante de esta sección</label>
			<input type="number" id="<?php echo esc_attr( EventMetaKeys::LOGO_ID ); ?>"
				name="<?php echo esc_attr( EventMetaKeys::LOGO_ID ); ?>" step="1" min="0"
				value="<?php echo esc_attr( (string) ( $logo_id > 0 ? $logo_id : '' ) ); ?>" />
			<small>
				Número del archivo en la biblioteca de medios. Se ve en la barra de direcciones al abrirlo
				en <a href="<?php echo esc_url( admin_url( 'upload.php' ) ); ?>">Medios</a>. En blanco, el del evento.
			</small>
			<?php if ( '' !== $miniatura ) : ?>
				<img class="evt-preview-logo" src="<?php echo esc_url( $miniatura ); ?>" alt="Logo elegido para esta sección" />
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}









	private static function field_error( array $errors, array $codes, string $text ): void {
		if ( array() === array_intersect( $codes, $errors ) ) {
			return;
		}
		echo '<span class="evt-error" role="alert">' . esc_html( $text ) . '</span>';
	}






	private static function back_to_events(): string {
		$url = Shell::url( 'events' );
		if ( '' === $url ) {
			return '';
		}
		return '<p class="evt-acciones"><a class="' . esc_attr( Assets::button_class() ) . '" href="'
			. esc_url( $url ) . '">Ir a mis eventos</a></p>';
	}
}








namespace Evt\PublicFront\Block;



















final class ContentBlock {




	public const NAME = 'contenido';




	public const PRIORITY = 10;







	public static function html( array $m ): string {
		return wp_kses_post( (string) $m['content'] );
	}
}








namespace Evt\PublicFront\Block;















final class PosterBlock {




	public const NAME = 'cartel';




	public const PRIORITY = 20;







	public static function html( array $m ): string {
		$look = (array) $m['appearance'];
		if ( empty( $m['is_root'] ) || '' === (string) $look['poster'] ) {
			return '';
		}

		$alt = (string) $look['poster_alt'];
		if ( '' === $alt ) {

			$alt = sprintf( __( 'Cartel de %s', 'wp-eventos' ), (string) $m['event_title'] );
		}

		ob_start();
		?>
		<figure class="evt-ev__cartel">
			<a href="<?php echo esc_url( (string) $look['poster_full'] ); ?>">
				<img src="<?php echo esc_url( (string) $look['poster'] ); ?>"
					alt="<?php echo esc_attr( $alt ); ?>" loading="lazy" />
			</a>
			<figcaption><?php esc_html_e( 'Pulse el cartel para verlo a tamaño completo.', 'wp-eventos' ); ?></figcaption>
		</figure>
		<?php
		return (string) ob_get_clean();
	}
}








namespace Evt\PublicFront\Block;


















final class SectionsBlock {




	public const NAME = 'secciones';




	public const PRIORITY = 30;







	public static function html( array $m ): string {
		$cards = (array) $m['cards'];
		if ( array() === $cards ) {
			return '';
		}

		ob_start();
		?>
		<h2 class="screen-reader-text">Secciones de este evento</h2>
		<div class="evt-ev__rejilla">
			<?php foreach ( $cards as $card ) : ?>
				<div id="evt-seccion-<?php echo esc_attr( (string) $card['id'] ); ?>" class="evt-ev__tarjeta">
					<?php if ( '' !== (string) $card['image'] ) : ?>
						<img src="<?php echo esc_url( (string) $card['image'] ); ?>" alt="" loading="lazy" />
					<?php endif; ?>
					<h3>
						<a href="<?php echo esc_url( (string) $card['url'] ); ?>"><?php echo esc_html( (string) $card['title'] ); ?></a>
					</h3>
					<?php if ( '' !== trim( (string) $card['text'] ) ) : ?>
						<p><?php echo esc_html( wp_strip_all_tags( (string) $card['text'] ) ); ?></p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}








namespace Evt\PublicFront\Block;

use Evt\Meta\RegistrationMetaKeys;
use Evt\PublicFront\Registrations;
use Evt\PublicFront\RegistrationFiles;
use Evt\PublicFront\SignupForm;














final class SignupBlock {




	public const NAME = 'inscripcion';





	public const PRIORITY = 25;







	public static function html( array $m ): string {
		if ( self::NAME !== ( $m['section_type'] ?? '' ) ) {
			return '';
		}

		$evento = (int) ( $m['event_id'] ?? 0 );
		if ( $evento <= 0 ) {
			return '';
		}

		$aviso = self::notice();
		$mia   = SignupForm::current( $evento );


		if ( $mia > 0 ) {
			return $aviso . self::mine( $evento, $mia );
		}

		if ( ! SignupForm::is_open( $evento ) ) {
			return $aviso . '<p class="evt-ins__cerrada">La inscripción de este evento no está abierta.</p>';
		}

		return $aviso . self::form( $evento );
	}






	private static function notice(): string {
		$aviso = SignupForm::notice();
		if ( null === $aviso ) {
			return '';
		}
		return sprintf(
			'<p class="evt-aviso evt-aviso--%1$s" role="alert">%2$s</p>',
			esc_attr( $aviso['level'] ),
			esc_html( $aviso['message'] )
		);
	}







	private static function form( int $evento ): string {



		$html  = '<form class="evt-ins" method="post" enctype="multipart/form-data">';
		$html .= self::hidden( $evento, SignupForm::OP_SIGNUP );

		$html .= '<fieldset class="evt-ins__nucleo"><legend>Sus datos</legend>';
		foreach ( self::core_fields() as $nombre => $campo ) {
			$html .= self::field( $nombre, $campo );
		}
		$html .= self::centre();
		$html .= '</fieldset>';

		$html .= self::questions( $evento );
		$html .= self::workshops( $evento, 0 );
		$html .= self::consent( $evento );

		$html .= '<p class="evt-ins__enviar"><button type="submit" class="evt-btn evt-btn--primario">Inscribirme</button></p>';
		$html .= '</form>';

		return $html;
	}








	private static function mine( int $evento, int $mia ): string {
		$meta   = Registrations::meta( $mia );
		$nombre = trim( $meta[ RegistrationMetaKeys::REG_NAME ] . ' ' . $meta[ RegistrationMetaKeys::REG_SURNAME ] );

		$html  = '<div class="evt-ins evt-ins--mia">';
		$html .= '<p class="evt-ins__hecha">Su inscripción está registrada, ' . esc_html( $nombre ) . '.</p>';

		if ( ! SignupForm::workshops_open( $evento ) ) {
			$html .= '<p class="evt-ins__cerrada">El plazo para elegir taller no está abierto.</p>';
			return $html . '</div>';
		}


		$token = isset( $_GET[ SignupForm::ARG_TOKEN ] ) ? sanitize_text_field( wp_unslash( $_GET[ SignupForm::ARG_TOKEN ] ) ) : '';

		$html .= '<form class="evt-ins__taller" method="post">';
		$html .= self::hidden( $evento, SignupForm::OP_WORKSHOP );
		$html .= sprintf( '<input type="hidden" name="%1$s" value="%2$s">', esc_attr( SignupForm::FIELD_TOKEN ), esc_attr( $token ) );
		$html .= self::workshops( $evento, $mia );
		$html .= '<p class="evt-ins__enviar"><button type="submit" class="evt-btn evt-btn--primario">Guardar el taller</button></p>';
		$html .= '</form></div>';

		return $html;
	}








	private static function hidden( int $evento, string $op ): string {
		return sprintf(
			'<input type="hidden" name="%1$s" value="%2$s"><input type="hidden" name="%3$s" value="%4$d">%5$s',
			esc_attr( SignupForm::FIELD_OP ),
			esc_attr( $op ),
			esc_attr( SignupForm::FIELD_EVENT ),
			$evento,
			wp_nonce_field( SignupForm::NONCE_ACTION, SignupForm::NONCE_FIELD, true, false )
		);
	}






	private static function core_fields(): array {
		return array(
			'tax_id'  => array(
				'label'        => 'Documento de identidad',
				'type'         => 'text',
				'required'     => true,
				'autocomplete' => 'off',
			),
			'name'    => array(
				'label'        => 'Nombre',
				'type'         => 'text',
				'required'     => true,
				'autocomplete' => 'given-name',
			),
			'surname' => array(
				'label'        => 'Apellidos',
				'type'         => 'text',
				'required'     => true,
				'autocomplete' => 'family-name',
			),
			'email'   => array(
				'label'        => 'Correo electrónico',
				'type'         => 'email',
				'required'     => true,
				'autocomplete' => 'email',
			),
			'phone'   => array(
				'label'        => 'Teléfono',
				'type'         => 'tel',
				'required'     => false,
				'autocomplete' => 'tel',
			),
		);
	}








	private static function field( string $nombre, array $campo ): string {
		$id = 'evt-ins-' . $nombre;
		return sprintf(
			'<p class="evt-campo"><label for="%1$s">%2$s%3$s</label>'
				. '<input type="%4$s" id="%1$s" name="%5$s" autocomplete="%6$s"%7$s></p>',
			esc_attr( $id ),
			esc_html( $campo['label'] ),
			$campo['required'] ? ' <span class="evt-campo__obl" aria-hidden="true">*</span>' : '',
			esc_attr( $campo['type'] ),
			esc_attr( $nombre ),
			esc_attr( $campo['autocomplete'] ),
			$campo['required'] ? ' required' : ''
		);
	}










	private static function centre(): string {
		$centros = Registrations::centres();
		if ( array() === $centros ) {
			return '<p class="evt-aviso evt-aviso--error">No hay catálogo de centros configurado, '
				. 'así que no se puede completar la inscripción. Avise a quien organiza el evento.</p>';
		}

		$html = '<p class="evt-campo"><label for="evt-ins-centre">Centro <span class="evt-campo__obl" aria-hidden="true">*</span></label>'
			. '<select id="evt-ins-centre" name="centre" required><option value="">Elija su centro</option>';
		foreach ( $centros as $codigo => $denominacion ) {
			$html .= sprintf(
				'<option value="%1$s">%2$s</option>',
				esc_attr( (string) $codigo ),
				esc_html( (string) $denominacion )
			);
		}
		return $html . '</select></p>';
	}







	private static function questions( int $evento ): string {
		$preguntas = Registrations::questions( $evento );
		if ( array() === $preguntas ) {
			return '';
		}

		$html = '<fieldset class="evt-ins__preguntas"><legend>Sobre este evento</legend>';
		foreach ( $preguntas as $pregunta ) {
			$html .= self::question( $pregunta );
		}
		return $html . '</fieldset>';
	}







	private static function question( array $p ): string {
		$id     = 'evt-q-' . $p['id'];
		$name   = SignupForm::FIELD_ANSWERS . '[' . $p['id'] . ']';
		$rotulo = esc_html( (string) $p['label'] ) . ( $p['required'] ? ' <span class="evt-campo__obl" aria-hidden="true">*</span>' : '' );

		if ( 'check' === $p['type'] ) {
			return sprintf(
				'<p class="evt-campo evt-campo--casilla"><label for="%1$s"><input type="checkbox" id="%1$s" name="%2$s" value="1"%3$s> %4$s</label></p>',
				esc_attr( $id ),
				esc_attr( $name ),
				$p['required'] ? ' required' : '',
				$rotulo
			);
		}

		if ( 'file' === $p['type'] ) {
			return sprintf(
				'<p class="evt-campo evt-campo--fichero"><label for="%1$s">%2$s</label>'
					. '<input type="file" id="%1$s" name="%3$s" accept="%4$s"%5$s>'
					. '<small>Un solo documento, de hasta %6$s. Se admiten PDF, JPG, PNG, DOCX y ODT.</small></p>',
				esc_attr( $id ),
				$rotulo,
				esc_attr( RegistrationFiles::FIELD . '[' . $p['id'] . ']' ),
				esc_attr( implode( ',', array_values( RegistrationFiles::mimes() ) ) ),
				$p['required'] ? ' required' : '',
				esc_html( size_format( RegistrationFiles::max_bytes() ) )
			);
		}

		if ( 'text' === $p['type'] ) {
			return sprintf(
				'<p class="evt-campo"><label for="%1$s">%2$s</label><input type="text" id="%1$s" name="%3$s" maxlength="250"%4$s></p>',
				esc_attr( $id ),
				$rotulo,
				esc_attr( $name ),
				$p['required'] ? ' required' : ''
			);
		}

		$varias = 'many' === $p['type'];
		$html   = '<fieldset class="evt-campo evt-campo--opciones"><legend>' . $rotulo . '</legend>';
		foreach ( (array) $p['options'] as $i => $opcion ) {
			$html .= sprintf(
				'<label class="evt-opcion"><input type="%1$s" name="%2$s" value="%3$s"> %3$s</label>',
				$varias ? 'checkbox' : 'radio',
				esc_attr( $varias ? $name . '[]' : $name ),
				esc_attr( $opcion )
			);
			unset( $i );
		}
		return $html . '</fieldset>';
	}












	private static function workshops( int $evento, int $mia ): string {
		if ( ! SignupForm::workshops_open( $evento ) ) {
			return '';
		}

		$opciones = Registrations::choices( $evento, $mia );
		if ( array() === $opciones ) {
			return '<p class="evt-ins__sin-talleres">Ahora mismo no queda ningún taller con plazas libres.</p>';
		}

		$html  = '<fieldset class="evt-ins__talleres"><legend>Taller</legend>';
		$html .= '<label class="evt-opcion"><input type="radio" name="evt_workshop" value="0"' . ( 0 === $mia ? ' checked' : '' ) . '> No elijo taller</label>';
		foreach ( $opciones as $taller ) {
			$libres = $taller['seats'] > 0 ? max( 0, $taller['seats'] - $taller['taken'] ) : 0;
			$html  .= sprintf(
				'<label class="evt-opcion"><input type="radio" name="evt_workshop" value="%1$d"%2$s> %3$s%4$s</label>',
				$taller['id'],
				$taller['mine'] ? ' checked' : '',
				esc_html( $taller['title'] ),
				$taller['seats'] > 0
					? ' <span class="evt-plazas">(' . (int) $libres . ' plazas libres)</span>'
					: ''
			);
		}
		return $html . '</fieldset>';
	}







	private static function consent( int $evento ): string {
		$privacidad = (string) get_post_meta( $evento, RegistrationMetaKeys::CONSENT_PRIVACY, true );
		$imagen     = (string) get_post_meta( $evento, RegistrationMetaKeys::CONSENT_IMAGE, true );

		$html = '<fieldset class="evt-ins__consentimiento"><legend>Protección de datos</legend>';
		foreach ( array(
			'Información sobre el tratamiento de sus datos' => $privacidad,
			'Consentimiento informado' => $imagen,
		) as $titulo => $texto ) {
			if ( '' === trim( $texto ) ) {
				continue;
			}
			$html .= '<details class="evt-consent__doc"><summary>' . esc_html( $titulo ) . '</summary>'
				. wp_kses_post( $texto ) . '</details>';
		}

		$html .= '<p class="evt-campo evt-campo--casilla"><label for="evt-ins-consent">'
			. '<input type="checkbox" id="evt-ins-consent" name="consent" value="1" required> '
			. 'He leído la información sobre el tratamiento de mis datos y el consentimiento informado, y los acepto '
			. '<span class="evt-campo__obl" aria-hidden="true">*</span></label>'
			. '<small>Obligatorio para inscribirse. Se guarda la versión exacta que ha aceptado y el momento.</small></p>';

		return $html . '</fieldset>';
	}
}








namespace Evt\PublicFront;

use Evt\Meta\EventMetaKeys;
use Evt\PublicFront\Block\ContentBlock;
use Evt\PublicFront\Block\PosterBlock;
use Evt\PublicFront\Block\SectionsBlock;
use Evt\PublicFront\Block\SignupBlock;
use Evt\PublicFront\View\EventChrome;













































final class EventLayout {






	private static $blocks = array();






	private static $seq = 0;










	public static function register(): void {
		self::add_block( ContentBlock::NAME, array( ContentBlock::class, 'html' ), ContentBlock::PRIORITY );
		self::add_block( PosterBlock::NAME, array( PosterBlock::class, 'html' ), PosterBlock::PRIORITY );
		self::add_block( SectionsBlock::NAME, array( SectionsBlock::class, 'html' ), SectionsBlock::PRIORITY );
		self::add_block( SignupBlock::NAME, array( SignupBlock::class, 'html' ), SignupBlock::PRIORITY );
	}









	public static function add_block( string $name, callable $render, int $priority = 50 ): void {
		$name = sanitize_key( $name );
		if ( '' === $name ) {
			return;
		}
		self::$blocks[ $name ] = array(
			'render'   => $render,
			'priority' => $priority,
			'order'    => isset( self::$blocks[ $name ] ) ? self::$blocks[ $name ]['order'] : ++self::$seq,
		);
	}







	public static function remove_block( string $name ): void {
		unset( self::$blocks[ sanitize_key( $name ) ] );
	}






	public static function blocks(): array {
		$bloques = self::$blocks;
		uasort(
			$bloques,
			static function ( array $a, array $b ): int {
				return $a['priority'] === $b['priority']
					? $a['order'] <=> $b['order']
					: $a['priority'] <=> $b['priority'];
			}
		);









		return (array) apply_filters( 'evt_event_blocks', $bloques );
	}







	public static function body( array $m ): string {
		$html = '';
		foreach ( self::blocks() as $name => $bloque ) {
			$trozo = (string) call_user_func( $bloque['render'], $m );
			if ( '' === trim( $trozo ) ) {
				continue;
			}
			$html .= sprintf(
				'<section class="evt-ev__bloque evt-ev__bloque--%s">%s</section>',
				esc_attr( $name ),
				$trozo
			);
		}
		return $html;
	}







	public static function render( array $m ): string {
		$clases = array( 'evt-ev' );
		if ( '' !== (string) $m['section_type'] ) {
			$clases[] = 'evt-ev--' . sanitize_html_class( (string) $m['section_type'] );
		}

		ob_start();
		?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
		<?php





		if ( ! current_theme_supports( 'title-tag' ) ) {
			echo '<title>' . esc_html( wp_get_document_title() ) . "</title>\n";
		}
		echo self::meta( $m ); 
		wp_head();



		echo EventChrome::consent(); 
		?>
</head>
<body <?php body_class( $clases ); ?>>
		<?php
		wp_body_open();
		echo '<a class="evt-ev__saltar visually-hidden-focusable" href="#contenido">Saltar al contenido</a>';
		echo EventChrome::header( $m ); 
		?>
	<main id="contenido" class="evt-ev__main evt-ev__ancho" tabindex="-1">
		<?php echo self::body( $m ); ?>
	</main>
		<?php
		echo EventChrome::footer(); 
		wp_footer();
		echo EventChrome::analytics(); 
		?>
</body>
</html>
		<?php
		return (string) ob_get_clean();
	}











	private static function meta( array $m ): string {
		$etiquetas = array(
			array( 'name', 'description', (string) $m['description'] ),
			array( 'property', 'og:type', 'article' ),
			array( 'property', 'og:site_name', (string) get_bloginfo( 'name' ) ),
			array( 'property', 'og:title', (string) $m['title'] ),
			array( 'property', 'og:description', (string) $m['description'] ),
			array( 'property', 'og:url', (string) $m['page_url'] ),
			array( 'property', 'og:image', (string) $m['image'] ),
			array( 'name', 'twitter:card', '' !== (string) $m['image'] ? 'summary_large_image' : 'summary' ),
		);

		$html = '';
		foreach ( $etiquetas as $etiqueta ) {
			list( $tipo, $clave, $valor ) = $etiqueta;
			if ( '' === $valor ) {
				continue;
			}
			$html .= sprintf(
				"\t<meta %s=\"%s\" content=\"%s\" />\n",
				esc_attr( $tipo ),
				esc_attr( $clave ),
				esc_attr( $valor )
			);
		}
		return $html;
	}














	public static function tokens( array $m ): string {
		$look = (array) $m['appearance'];

		$fondo  = (string) $look['bg'];
		$tokens = array(
			'--evt-fondo'       => $fondo,
			'--evt-texto'       => '' !== $fondo ? EventChrome::readable_ink( $fondo, (string) $look['fg'] ) : (string) $look['fg'],
			'--evt-tipo-titulo' => (string) $look['title_font'],
			'--evt-tipo-texto'  => (string) $look['body_font'],
			'--evt-forma'       => EventMetaKeys::SHAPE_CIRCLE === (string) $look['shape'] ? '50%' : '',
		);










		$tokens = (array) apply_filters( 'evt_event_tokens', $tokens, $m );

		$reglas = '';
		foreach ( $tokens as $propiedad => $valor ) {
			$valor = trim( (string) $valor );


			if ( '' === $valor || preg_match( '/[{};<>]/', $valor ) ) {
				continue;
			}
			$reglas .= '--' . sanitize_key( ltrim( (string) $propiedad, '-' ) ) . ':' . $valor . ';';
		}

		if ( '' === $reglas ) {
			return '';
		}
		return '<style id="evt-evento-tokens">:root{' . $reglas . '}</style>' . "\n";
	}
}








namespace Evt\PublicFront;

use Evt\Access\EventAccess;
use Evt\Domain\DateRange;
use Evt\Domain\EventState;
use Evt\Meta\EventMetaKeys;
use Evt\PostType\EventPostType;


















































final class EventView {








	public const PRIORITY = 20;










	public const HEAD_PRIORITY = 9;




	public const DROP_PRIORITY = 100;




	public const MANAGE_ARG = 'evento';









	public const LEGACY_META = 'evt_legacy';




	public const SIGNUP_LABEL = 'Inscríbete';




	private const DESCRIPTION_CHARS = 155;









	private const DROP_PATHS = array(
		'/themes/',
		'/et-cache/',
		'/plugins/content-views-query-and-display-post-page/',
		'/plugins/pt-content-views-pro/',
		'/plugins/table-sorter/',
	);








	private const DEFAULT_INTRO = array(
		'ponentes'    => 'Conoce los detalles de las personas comunicadoras.',
		'programa'    => 'Consulta el programa oficial.',
		'actividades' => 'Consulta los detalles de las actividades.',
		'contacto'    => 'Información de contacto con la organización del evento.',
	);









	private static $sections = array();








	private static $models = array();






	public static function register(): void {
		EventLayout::register();
		add_action( 'template_redirect', array( self::class, 'render' ), self::PRIORITY );
		add_action( 'wp_head', array( self::class, 'print_stylesheet' ), self::HEAD_PRIORITY );
		add_action( 'wp_enqueue_scripts', array( self::class, 'drop_page_assets' ), self::DROP_PRIORITY );


		add_filter( 'style_loader_tag', array( self::class, 'drop_page_tag' ), 10, 3 );
		add_filter( 'script_loader_tag', array( self::class, 'drop_page_tag' ), 10, 3 );
	}






	public static function takes_over(): bool {
		if ( is_admin() || ! is_singular( EventPostType::POST_TYPE ) ) {
			return false;
		}
		$post_id = (int) get_queried_object_id();
		if ( self::is_legacy( $post_id ) ) {
			return false;
		}







		return (bool) apply_filters( 'evt_event_standalone', true, $post_id );
	}












	public static function is_legacy( int $post_id ): bool {
		if ( $post_id <= 0 ) {
			return false;
		}
		$propia = get_post_meta( $post_id, self::LEGACY_META, true );
		if ( '' !== (string) $propia ) {
			return (bool) $propia;
		}
		return (bool) get_post_meta( EventAccess::root_id( $post_id ), self::LEGACY_META, true );
	}









	public static function render(): void {
		if ( ! self::takes_over() ) {
			return;
		}

		status_header( 200 );
		Shell::send_header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );

		echo EventLayout::render( self::current_model() ); 
		Shell::leave();
	}










	public static function print_stylesheet(): void {


		if ( ! self::takes_over() ) {
			return;
		}

		$hoja = self::stylesheet();
		if ( '' !== $hoja ) {

			echo '<style id="evt-evento-css">' . $hoja . "</style>\n";
		}
		echo EventLayout::tokens( self::current_model() ); 
	}












	public static function stylesheet(): string {





		return (string) apply_filters( 'evt_event_stylesheet', Assets::contents( 'css/evt-evento.css' ) );
	}











	public static function drop_page_assets(): void {
		if ( ! self::takes_over() ) {
			return;
		}

		foreach ( array( wp_styles(), wp_scripts() ) as $cola ) {
			foreach ( (array) $cola->queue as $handle ) {
				$src = isset( $cola->registered[ $handle ] ) ? (string) $cola->registered[ $handle ]->src : '';
				if ( self::is_droppable( $src ) ) {
					$cola->dequeue( $handle );
				}
			}
		}





		wp_dequeue_style( 'wp-emoji-styles' );
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
	}









	public static function drop_page_tag( string $tag, string $handle, string $src ): string {
		unset( $handle );
		if ( ! self::takes_over() ) {
			return $tag;
		}
		return self::is_droppable( $src ) ? '' : $tag;
	}







	private static function is_droppable( string $src ): bool {
		if ( '' === $src ) {
			return false;
		}
		foreach ( self::DROP_PATHS as $trozo ) {
			if ( false !== strpos( $src, $trozo ) ) {
				return true;
			}
		}
		if ( self::has_form( (int) get_queried_object_id() ) ) {
			return false;
		}
		foreach ( self::form_asset_paths() as $trozo ) {
			if ( '' !== $trozo && false !== strpos( $src, $trozo ) ) {
				return true;
			}
		}
		return false;
	}











	private static function form_asset_paths(): array {





		$rutas = (array) apply_filters( 'evt_form_asset_paths', array() );
		return array_map( 'strval', $rutas );
	}














	private static function has_form( int $post_id ): bool {
		if ( $post_id <= 0 ) {
			return false;
		}
		$evento = EventAccess::root_id( $post_id );
		if ( (int) get_post_meta( $evento, EventMetaKeys::SIGNUP_FORM_ID, true ) > 0 ) {
			return true;
		}







		return (bool) apply_filters( 'evt_page_has_form', false, $post_id );
	}






	public static function current_model(): array {
		$post_id = (int) get_queried_object_id();
		if ( ! isset( self::$models[ $post_id ] ) ) {
			$contenido = $post_id > 0
				? (string) apply_filters( 'the_content', (string) get_post_field( 'post_content', $post_id ) )
				: '';

			self::$models[ $post_id ] = self::model( $post_id, $contenido );
		}
		return self::$models[ $post_id ];
	}








	public static function model( int $post_id = 0, string $content = '' ): array {
		$post_id = $post_id > 0 ? $post_id : (int) get_the_ID();
		if ( $post_id <= 0 || EventPostType::POST_TYPE !== get_post_type( $post_id ) ) {
			return self::empty_model( $content );
		}

		$event_id = EventAccess::root_id( $post_id );
		$start    = (string) get_post_meta( $event_id, EventMetaKeys::START_DATE, true );
		$end      = (string) get_post_meta( $event_id, EventMetaKeys::END_DATE, true );
		$is_root  = $event_id === $post_id;
		$estado   = EventState::of( $start, $end );
		$look     = self::appearance( $event_id );

		$m = array(
			'page_id'      => $post_id,
			'event_id'     => $event_id,
			'is_root'      => $is_root,
			'is_legacy'    => self::is_legacy( $post_id ),
			'title'        => (string) get_the_title( $post_id ),



			'show_title'   => true,
			'section_type' => $is_root ? '' : EventMetaKeys::in_list(
				get_post_meta( $post_id, EventMetaKeys::SECTION_TYPE, true ),
				EventMetaKeys::section_types()
			),
			'event_title'  => (string) get_the_title( $event_id ),
			'event_url'    => (string) get_permalink( $event_id ),
			'page_url'     => (string) get_permalink( $post_id ),
			'tagline'      => (string) get_post_meta( $event_id, EventMetaKeys::TAGLINE, true ),
			'hashtag'      => ltrim( trim( (string) get_post_meta( $event_id, EventMetaKeys::HASHTAG, true ) ), '#' ),
			'dates'        => DateRange::of( $start, $end ),
			'venue'        => (string) get_post_meta( $event_id, EventMetaKeys::VENUE, true ),
			'state'        => $estado,
			'state_label'  => EventState::label( $estado ),
			'appearance'   => $look,
			'signup'       => self::signup( $event_id ),
			'nav'          => self::nav( $event_id, $post_id ),
			'cards'        => $is_root ? self::cards( $event_id ) : array(),
			'content'      => $content,
			'description'  => self::description( $post_id, $event_id ),
			'image'        => '' !== (string) $look['poster_full'] ? (string) $look['poster_full'] : (string) get_the_post_thumbnail_url( $post_id, 'large' ),
			'manage_url'   => self::manage_url( $event_id ),
		);











		return (array) apply_filters( 'evt_event_model', $m, $post_id );
	}







	private static function empty_model( string $content ): array {
		return array(
			'page_id'      => 0,
			'event_id'     => 0,
			'is_root'      => false,
			'is_legacy'    => false,
			'title'        => '',
			'show_title'   => false,
			'section_type' => '',
			'event_title'  => '',
			'event_url'    => '',
			'page_url'     => '',
			'tagline'      => '',
			'hashtag'      => '',
			'dates'        => '',
			'venue'        => '',
			'state'        => '',
			'state_label'  => '',
			'appearance'   => self::appearance( 0 ),
			'signup'       => self::signup( 0 ),
			'nav'          => array(),
			'cards'        => array(),
			'content'      => $content,
			'description'  => '',
			'image'        => '',
			'manage_url'   => '',
		);
	}








	private static function description( int $post_id, int $event_id ): string {
		$candidatos = array(
			(string) get_post_field( 'post_excerpt', $post_id ),
			(string) get_post_meta( $event_id, EventMetaKeys::INTRO, true ),
			(string) get_post_meta( $event_id, EventMetaKeys::TAGLINE, true ),
		);
		foreach ( $candidatos as $texto ) {
			$texto = trim( wp_strip_all_tags( $texto ) );
			if ( '' !== $texto ) {
				return wp_html_excerpt( $texto, self::DESCRIPTION_CHARS, '…' );
			}
		}
		return '';
	}







	private static function signup( int $event_id ): array {
		$vacio = array(
			'show'  => false,
			'label' => '',
			'url'   => '',
		);
		if ( $event_id <= 0 || ! get_post_meta( $event_id, EventMetaKeys::SIGNUP_SHOW, true ) ) {
			return $vacio;
		}

		$url = esc_url_raw( (string) get_post_meta( $event_id, EventMetaKeys::SIGNUP_URL, true ) );
		if ( '' === $url ) {
			return $vacio;
		}
		$rotulo = trim( (string) get_post_meta( $event_id, EventMetaKeys::SIGNUP_LABEL, true ) );

		return array(
			'show'  => true,
			'label' => '' !== $rotulo ? $rotulo : self::SIGNUP_LABEL,
			'url'   => $url,
		);
	}












	private static function manage_url( int $event_id ): string {
		if ( $event_id <= 0 || ! EventAccess::can_open( get_current_user_id(), $event_id ) ) {
			return '';
		}
		return Shell::url( 'event', array( self::MANAGE_ARG => $event_id ) );
	}







	private static function appearance( int $event_id ): array {
		$vacia = array(
			'bg'                => '',
			'fg'                => '',
			'title_font'        => '',
			'body_font'         => '',
			'logo'              => '',
			'logo_alt'          => '',
			'header_banner'     => '',
			'header_banner_alt' => '',
			'poster'            => '',
			'poster_full'       => '',
			'poster_alt'        => '',
			'shape'             => EventMetaKeys::SHAPE_SQUARE,
			'separator'         => '',
		);
		if ( $event_id <= 0 ) {
			return $vacia;
		}

		$bg     = sanitize_hex_color( (string) get_post_meta( $event_id, EventMetaKeys::HEADER_BG, true ) );
		$fg     = sanitize_hex_color( (string) get_post_meta( $event_id, EventMetaKeys::HEADER_TEXT, true ) );
		$logo   = (int) get_post_meta( $event_id, EventMetaKeys::LOGO_ID, true );
		$banner = (int) get_post_meta( $event_id, EventMetaKeys::HEADER_BANNER_ID, true );
		$cartel = (int) get_post_meta( $event_id, EventMetaKeys::POSTER_ID, true );

		return array(
			'bg'                => is_string( $bg ) ? $bg : '',
			'fg'                => is_string( $fg ) ? $fg : '',
			'title_font'        => self::font_stack( (string) get_post_meta( $event_id, EventMetaKeys::TITLE_FONT, true ) ),
			'body_font'         => self::font_stack( (string) get_post_meta( $event_id, EventMetaKeys::BODY_FONT, true ) ),
			'logo'              => $logo > 0 ? (string) wp_get_attachment_image_url( $logo, 'medium' ) : '',
			'logo_alt'          => $logo > 0 ? (string) get_post_meta( $logo, '_wp_attachment_image_alt', true ) : '',
			'header_banner'     => $banner > 0 ? (string) wp_get_attachment_image_url( $banner, 'full' ) : '',
			'header_banner_alt' => $banner > 0 ? (string) get_post_meta( $banner, '_wp_attachment_image_alt', true ) : '',


			'poster'            => $cartel > 0 ? (string) wp_get_attachment_image_url( $cartel, 'large' ) : '',
			'poster_full'       => $cartel > 0 ? (string) wp_get_attachment_image_url( $cartel, 'full' ) : '',
			'poster_alt'        => $cartel > 0 ? (string) get_post_meta( $cartel, '_wp_attachment_image_alt', true ) : '',
			'shape'             => EventMetaKeys::in_list(
				get_post_meta( $event_id, EventMetaKeys::IMAGE_SHAPE, true ),
				EventMetaKeys::image_shapes(),
				EventMetaKeys::SHAPE_SQUARE
			),
			'separator'         => EventMetaKeys::in_list(
				get_post_meta( $event_id, EventMetaKeys::SEPARATOR, true ),
				EventMetaKeys::separators()
			),
		);
	}







	private static function font_stack( string $slug ): string {
		$fuentes = EventMetaKeys::fonts();
		$slug    = EventMetaKeys::in_list( $slug, $fuentes, EventMetaKeys::FONT_DEFAULT );
		if ( EventMetaKeys::FONT_DEFAULT === $slug ) {
			return '';
		}
		return sprintf( "'%s', 'Open Sans', Arial, sans-serif", $fuentes[ $slug ] );
	}












	private static function nav( int $event_id, int $current_id ): array {
		$menu = array(
			array(
				'label'   => 'Inicio',
				'url'     => (string) get_permalink( $event_id ),
				'current' => $event_id === $current_id,
			),
		);
		foreach ( self::sections( $event_id ) as $seccion ) {
			$menu[] = array(
				'label'   => (string) get_the_title( $seccion ),
				'url'     => (string) get_permalink( $seccion ),
				'current' => (int) $seccion->ID === $current_id,
			);
		}
		return $menu;
	}










	private static function cards( int $event_id ): array {
		$tarjetas = array();
		foreach ( self::sections( $event_id ) as $seccion ) {
			$id    = (int) $seccion->ID;
			$tipo  = EventMetaKeys::in_list(
				get_post_meta( $id, EventMetaKeys::SECTION_TYPE, true ),
				EventMetaKeys::section_types()
			);
			$texto = trim( (string) get_post_meta( $id, EventMetaKeys::INTRO, true ) );
			if ( '' === $texto ) {
				$texto = self::DEFAULT_INTRO[ $tipo ] ?? '';
			}

			$tarjetas[] = array(
				'id'    => $id,
				'title' => (string) get_the_title( $seccion ),
				'url'   => (string) get_permalink( $seccion ),
				'image' => self::card_image( $id, $tipo ),
				'text'  => $texto,
			);
		}
		return $tarjetas;
	}












	private static function card_image( int $post_id, string $type ): string {
		$url = (string) get_the_post_thumbnail_url( $post_id, 'medium' );
		if ( '' !== $url ) {
			return $url;
		}








		return (string) apply_filters( 'evt_section_default_image', '', $type, $post_id );
	}







	private static function sections( int $event_id ): array {
		if ( isset( self::$sections[ $event_id ] ) ) {
			return self::$sections[ $event_id ];
		}

		$paginas = get_posts(
			array(
				'post_type'        => EventPostType::POST_TYPE,
				'post_parent'      => $event_id,
				'post_status'      => 'publish',
				'orderby'          => 'menu_order title',
				'order'            => 'ASC',
				'numberposts'      => 100,
				'suppress_filters' => false,
			)
		);

		self::$sections[ $event_id ] = is_array( $paginas ) ? $paginas : array();
		return self::$sections[ $event_id ];
	}
}








namespace Evt\PublicFront\View;























final class EventChrome {











	public const HOOK = 'evt_chrome';








	public const CONSENT_COOKIE = 'cookieconsent_status';










	public static function chrome(): array {
		$defecto = array(

			'owner'          => '',
			'owner_url'      => '',
			'org'            => '',
			'credit'         => '',
			'footer_links'   => array(),

			'consent_css'    => '',
			'consent_js'     => '',
			'consent_init'   => '',
			'consent_cookie' => self::CONSENT_COOKIE,

			'matomo_api'     => '',
			'matomo_js'      => '',
			'matomo_site'    => 0,
		);






		$puesto = apply_filters( self::HOOK, $defecto );

		return is_array( $puesto ) ? array_merge( $defecto, $puesto ) : $defecto;
	}




	public const MIN_CONTRAST = 4.5;












	public const SEPARATORS = array(
		'slant'    => 'M0,60 L1200,0 L1200,60 Z',
		'ramp'     => 'M0,60 L1200,24 L1200,60 Z',
		'curve'    => 'M0,60 Q600,-10 1200,60 Z',
		'wave'     => 'M0,38 C300,68 900,-6 1200,38 L1200,60 L0,60 Z',
		'triangle' => 'M0,60 L600,0 L1200,60 Z',
	);







	public static function header( array $m ): string {
		return '<header class="evt-ev__cabecera">'
			. self::nav( (array) $m['nav'] )
			. self::cover( $m )
			. '</header>';
	}










	public static function nav( array $items ): string {
		if ( count( $items ) < 2 ) {
			return '';
		}

		ob_start();
		?>
		<nav class="evt-ev__nav navbar navbar-expand-lg" aria-label="Secciones del evento">
			<ul class="evt-ev__ancho nav">
				<?php foreach ( $items as $item ) : ?>
					<li class="nav-item">
						<a class="nav-link" href="<?php echo esc_url( (string) $item['url'] ); ?>"
							<?php echo ! empty( $item['current'] ) ? ' aria-current="page"' : ''; ?>><?php echo esc_html( (string) $item['label'] ); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>
		<?php
		return (string) ob_get_clean();
	}













	public static function cover( array $m ): string {
		$look   = (array) $m['appearance'];
		$signup = (array) $m['signup'];
		$banner = ! empty( $m['is_root'] ) ? (string) $look['header_banner'] : '';

		if ( '' !== $banner ) {
			ob_start();
			?>
			<div class="evt-ev__portada evt-ev__portada--banner">
				<img class="evt-ev__banner" src="<?php echo esc_url( $banner ); ?>"
					alt="<?php echo esc_attr( (string) $look['header_banner_alt'] ); ?>" />
				<div class="screen-reader-text">
					<h1><?php echo esc_html( (string) $m['title'] ); ?></h1>
					<?php
					if ( '' !== (string) $m['tagline'] ) :
						?>
						<p><?php echo esc_html( (string) $m['tagline'] ); ?></p><?php endif; ?>
					<?php
					if ( '' !== (string) $m['dates'] ) :
						?>
						<p><?php echo esc_html( (string) $m['dates'] ); ?></p><?php endif; ?>
					<?php
					if ( '' !== (string) $m['venue'] ) :
						?>
						<p><?php echo esc_html( (string) $m['venue'] ); ?></p><?php endif; ?>
				</div>
			</div>
			<?php
			return (string) ob_get_clean();
		}

		ob_start();
		?>
		<div class="evt-ev__portada">
			<div class="evt-ev__ancho">
				<?php if ( '' !== (string) $look['logo'] ) : ?>
					<img class="evt-ev__logo" src="<?php echo esc_url( (string) $look['logo'] ); ?>"
						alt="<?php echo esc_attr( (string) $look['logo_alt'] ); ?>" />
				<?php endif; ?>

				<?php if ( empty( $m['is_root'] ) && '' !== (string) $m['event_url'] ) : ?>
					<p class="evt-ev__madre">
						<a href="<?php echo esc_url( (string) $m['event_url'] ); ?>"><?php echo esc_html( (string) $m['event_title'] ); ?></a>
					</p>
				<?php endif; ?>

				<h1 class="evt-ev__titulo"><?php echo esc_html( (string) $m['title'] ); ?></h1>

				<?php if ( '' !== (string) $m['tagline'] ) : ?>
					<p class="evt-ev__lema"><?php echo esc_html( (string) $m['tagline'] ); ?></p>
				<?php endif; ?>

				<div class="evt-ev__linea" aria-hidden="true"></div>

				<?php if ( '' !== (string) $m['dates'] || '' !== (string) $m['venue'] ) : ?>
					<p class="evt-ev__datos">
						<?php if ( '' !== (string) $m['dates'] ) : ?>
							<span><?php echo esc_html( (string) $m['dates'] ); ?></span>
						<?php endif; ?>
						<?php if ( '' !== (string) $m['venue'] ) : ?>
							<span><?php echo esc_html( (string) $m['venue'] ); ?></span>
						<?php endif; ?>
					</p>
				<?php endif; ?>

				<p class="evt-ev__acciones">
					<?php if ( '' !== (string) $m['state_label'] ) : ?>
						<span class="evt-ev__estado"><?php echo esc_html( (string) $m['state_label'] ); ?></span>
					<?php endif; ?>
					<?php if ( '' !== (string) $m['hashtag'] ) : ?>
						<span class="evt-ev__hashtag">#<?php echo esc_html( (string) $m['hashtag'] ); ?></span>
					<?php endif; ?>
					<?php if ( '' !== (string) $signup['url'] ) : ?>
						<a class="evt-ev__boton" href="<?php echo esc_url( (string) $signup['url'] ); ?>"><?php echo esc_html( (string) $signup['label'] ); ?></a>
					<?php endif; ?>
					<?php if ( '' !== (string) $m['manage_url'] ) : ?>
						<a class="evt-ev__gestion" href="<?php echo esc_url( (string) $m['manage_url'] ); ?>">Gestionar este evento</a>
					<?php endif; ?>
				</p>
			</div>
			<?php echo self::separator( (string) $look['separator'] ); ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}











	public static function separator( string $shape ): string {
		$trazo = self::SEPARATORS[ $shape ] ?? '';
		if ( '' === $trazo ) {
			return '';
		}
		return '<svg class="evt-ev__separador" viewBox="0 0 1200 60" preserveAspectRatio="none" aria-hidden="true" focusable="false">'
			. '<path d="' . esc_attr( $trazo ) . '" fill="var(--evt-papel)"/></svg>';
	}









	public static function footer(): string {
		$chrome  = self::chrome();
		$enlaces = (array) $chrome['footer_links'];

		ob_start();
		?>
		<footer class="evt-ev__pie">
			<div class="evt-ev__ancho">
				<span class="evt-ev__pie-quien">
					<?php if ( '' !== (string) $chrome['owner'] ) : ?>
						<?php if ( '' !== (string) $chrome['owner_url'] ) : ?>
							<a href="<?php echo esc_url( (string) $chrome['owner_url'] ); ?>" target="_blank" rel="noopener">&copy; <?php echo esc_html( (string) $chrome['owner'] ); ?></a>
						<?php else : ?>
							<span>&copy; <?php echo esc_html( (string) $chrome['owner'] ); ?></span>
						<?php endif; ?>
					<?php endif; ?>
				</span>
				<span class="evt-ev__pie-enlaces">
					<?php foreach ( $enlaces as $enlace ) : ?>
						<a href="<?php echo esc_url( (string) $enlace['url'] ); ?>"
							title="<?php echo esc_attr( (string) $enlace['title'] ); ?>"
							target="_blank" rel="noopener"><?php echo esc_html( (string) $enlace['label'] ); ?></a>
					<?php endforeach; ?>
				</span>
			</div>
		</footer>
		<?php
		return (string) ob_get_clean();
	}



















	public static function consent(): string {
		$chrome = self::chrome();
		$html   = '';


		if ( '' !== (string) $chrome['consent_css'] ) {
			$html .= '<link rel="stylesheet" href="' . esc_url( (string) $chrome['consent_css'] ) . '" />' . "\n";
		}
		if ( '' !== (string) $chrome['consent_js'] ) {
			$html .= '<script src="' . esc_url( (string) $chrome['consent_js'] ) . '" defer></script>' . "\n";
		}
		if ( '' !== (string) $chrome['consent_init'] ) {
			$html .= '<script src="' . esc_url( (string) $chrome['consent_init'] ) . '" defer></script>' . "\n";
		}


		return $html;
	}



















	public static function analytics(): string {
		$chrome = self::chrome();
		$site   = (int) $chrome['matomo_site'];
		$api    = (string) $chrome['matomo_api'];
		$js_url = (string) $chrome['matomo_js'];











		$encendida = (bool) apply_filters( 'evt_analytics_enabled', 'production' === wp_get_environment_type() );


		if ( ! $encendida || $site <= 0 || '' === $api || '' === $js_url ) {
			return '';
		}

		$cookie = (string) $chrome['consent_cookie'];
		$js     = 'var _paq=window._paq=window._paq||[];'
			. '_paq.push(["requireCookieConsent"]);'
			. ( '' !== $cookie
				? 'if(/(^|;\s*)' . $cookie . '=(allow|dismiss)(;|$)/.test(document.cookie)){_paq.push(["setCookieConsentGiven"]);}'
				: '' )
			. '_paq.push(["setTrackerUrl",' . wp_json_encode( $api, JSON_UNESCAPED_SLASHES ) . ']);'
			. '_paq.push(["setSiteId",' . $site . ']);'
			. '_paq.push(["trackPageView"]);'
			. '_paq.push(["enableLinkTracking"]);';


		$html = '<script id="evt-matomo">' . $js . '</script>' . "\n"
			. '<script src="' . esc_url( $js_url ) . '" async defer></script>' . "\n";

		return $html;
	}












	public static function contrast_ratio( string $uno, string $dos ): float {
		$a = self::luminance( $uno );
		$b = self::luminance( $dos );
		if ( $a < 0 || $b < 0 ) {
			return 1.0;
		}
		$claro  = max( $a, $b );
		$oscuro = min( $a, $b );
		return ( $claro + 0.05 ) / ( $oscuro + 0.05 );
	}















	public static function readable_ink( string $fondo, string $texto ): string {
		if ( '' !== $texto && self::contrast_ratio( $fondo, $texto ) >= self::MIN_CONTRAST ) {
			return $texto;
		}
		return self::contrast_ratio( $fondo, '#ffffff' ) >= self::contrast_ratio( $fondo, '#000000' )
			? '#ffffff'
			: '#000000';
	}







	private static function luminance( string $hex ): float {
		$hex = ltrim( trim( $hex ), '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( ! preg_match( '/^[0-9a-fA-F]{6}$/', $hex ) ) {
			return -1.0;
		}

		$canales = array();
		foreach ( array( 0, 2, 4 ) as $desde ) {
			$c         = hexdec( substr( $hex, $desde, 2 ) ) / 255;
			$canales[] = $c <= 0.03928 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
		}

		return 0.2126 * $canales[0] + 0.7152 * $canales[1] + 0.0722 * $canales[2];
	}
}








namespace Evt\PublicFront;

use Evt\Access\EventAccess;









final class Home {

	public const SHORTCODE = 'evt_home';






	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'render' ) );
		add_action( 'template_redirect', array( self::class, 'send_to_landing' ) );
	}






	public static function send_to_landing(): void {
		if ( is_admin() || ! is_singular() || ! is_user_logged_in() ) {
			return;
		}
		$post = get_post();
		if ( ! $post instanceof \WP_Post || ! has_shortcode( (string) $post->post_content, self::SHORTCODE ) ) {
			return;
		}

		$destino = self::landing();

		if ( '' === $destino || untrailingslashit( $destino ) === untrailingslashit( (string) get_permalink( $post ) ) ) {
			return;
		}

		Shell::leave( $destino );
	}






	public static function landing(): string {
		foreach ( Shell::sections() as $clave => $seccion ) {


			if ( 'settings' === $clave ) {
				continue;
			}
			return (string) $seccion['url'];
		}
		return '';
	}






	public static function model(): array {
		if ( ! is_user_logged_in() ) {
			return array(
				'logged_in' => false,
				'name'      => '',
				'sections'  => array(),
				'reason'    => 'Debe iniciar sesión con su usuario para gestionar eventos.',
			);
		}

		$user_id   = get_current_user_id();
		$secciones = Shell::sections( $user_id );
		$motivo    = '';

		if ( array() === $secciones ) {
			$motivo = ! user_can( $user_id, 'edit_evt_events' ) && ! EventAccess::is_manager( $user_id )
				? 'Su usuario todavía no organiza eventos. Pídalo a quien administre el aplicativo.'
				: 'No tiene ningún área asignada en su perfil, así que todavía no puede gestionar eventos. El área la pone quien administra el aplicativo.';
		}

		return array(
			'logged_in' => true,
			'name'      => (string) wp_get_current_user()->display_name,
			'sections'  => $secciones,
			'reason'    => $motivo,
		);
	}







	public static function html( array $model ): string {
		if ( ! $model['logged_in'] ) {
			return Shell::render( 'Gestión de eventos', '', Shell::notice( 'aviso', $model['reason'] ) );
		}

		if ( '' !== $model['reason'] ) {
			return Shell::render(
				sprintf( 'Hola, %s', $model['name'] ),
				'',
				Shell::notice( 'aviso', $model['reason'] )
			);
		}

		ob_start();
		?>
		<div class="evt-inicio-accesos">
			<?php foreach ( $model['sections'] as $acceso ) : ?>
				<a class="evt-inicio-acceso" href="<?php echo esc_url( $acceso['url'] ); ?>">
					<span class="evt-inicio-titulo"><?php echo esc_html( $acceso['label'] ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
		<?php
		return Shell::render(
			sprintf( 'Hola, %s', $model['name'] ),
			'Esto es lo que puede hacer aquí.',
			(string) ob_get_clean()
		);
	}







	public static function render( $atts = array() ): string {
		unset( $atts );
		Assets::enqueue();
		return self::html( self::model() );
	}
}








namespace Evt\Admin;

use Evt\Access\EventAccess;
use Evt\Domain\EventState;
use Evt\Meta\EventMetaKeys;
use Evt\PostType\ActivityPostType;
use Evt\PostType\EventPostType;
use Evt\PostType\SpeakerPostType;
use Evt\Taxonomy\EventTaxonomies;








final class EventAdmin {






	public static function register(): void {
		add_action( 'pre_get_posts', array( self::class, 'scope_admin_query' ) );
		add_filter( 'manage_' . EventPostType::POST_TYPE . '_posts_columns', array( self::class, 'columns' ) );
		add_action( 'manage_' . EventPostType::POST_TYPE . '_posts_custom_column', array( self::class, 'column_content' ), 10, 2 );
		add_action( 'admin_menu', array( self::class, 'add_new_submenus' ), 20 );
	}






	public static function submenu_post_types(): array {
		return array( SpeakerPostType::POST_TYPE, ActivityPostType::POST_TYPE );
	}





















	public static function add_new_submenus(): void {
		foreach ( self::submenu_post_types() as $tipo ) {
			$objeto = get_post_type_object( $tipo );
			if ( null === $objeto || ! is_string( $objeto->show_in_menu ) ) {
				continue;
			}
			add_submenu_page(
				$objeto->show_in_menu,
				(string) $objeto->labels->add_new_item,
				(string) $objeto->labels->add_new_item,
				(string) $objeto->cap->create_posts,
				'post-new.php?post_type=' . $tipo
			);
		}
	}







	public static function scope_admin_query( $query ): void {
		if ( ! is_admin() || ! ( $query instanceof \WP_Query ) || ! $query->is_main_query() ) {
			return;
		}
		if ( EventPostType::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}
		if ( EventAccess::can_edit_all_areas() ) {
			return;
		}

		$areas = EventAccess::user_areas();
		if ( array() === $areas ) {


			$query->set( 'post__in', array( 0 ) );
			return;
		}

		$tax_query   = (array) $query->get( 'tax_query' );
		$tax_query[] = array(
			'taxonomy'         => EventTaxonomies::AREA,
			'field'            => 'term_id',
			'terms'            => $areas,
			'include_children' => true,
		);
		$query->set( 'tax_query', $tax_query );
	}







	public static function columns( array $columns ): array {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['evt_area']  = 'Área';
				$new['evt_state'] = 'Estado';
			}
		}
		return $new;
	}








	public static function column_content( string $column, int $post_id ): void {
		if ( 'evt_area' === $column ) {
			$terms = get_the_terms( EventAccess::root_id( $post_id ), EventTaxonomies::AREA );
			echo is_array( $terms ) && array() !== $terms
				? esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) )
				: '—';
			return;
		}
		if ( 'evt_state' === $column ) {
			$root  = EventAccess::root_id( $post_id );
			$state = EventState::of(
				(string) get_post_meta( $root, EventMetaKeys::START_DATE, true ),
				(string) get_post_meta( $root, EventMetaKeys::END_DATE, true )
			);


			echo esc_html(
				EventAccess::is_archived( $post_id )
					? EventState::label( $state ) . ' · Histórico'
					: EventState::label( $state )
			);
		}
	}
}








namespace Evt\Admin;

use Evt\Access\EventAccess;
use Evt\Centre\CentreCatalogue;
use Evt\Centre\CentreCatalogueSync;
use Evt\PostType\ActivityPostType;
use Evt\PostType\EventPostType;
use Evt\PostType\SpeakerPostType;
use Evt\PublicFront\ExitSignal;
use Evt\Taxonomy\EventTaxonomies;
use RuntimeException;







final class Settings {




	public const PAGE = 'evt-settings';




	public const NONCE_SYNC_CENTRES = 'evt_centres_manual_sync';






	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ) );
		add_action( 'admin_init', array( self::class, 'handle_actions' ) );
	}






	public static function menu(): void {
		add_submenu_page(
			'edit.php?post_type=' . EventPostType::POST_TYPE,
			'Ajustes y diagnóstico de eventos',
			'Ajustes',
			EventAccess::CAP_MANAGE,
			self::PAGE,
			array( self::class, 'render' )
		);
	}






	public static function handle_actions(): void {
		if ( ! is_admin() || ! EventAccess::is_manager() ) {
			return;
		}

		if (
			isset( $_POST['evt_action'] ) &&
			'sync_centres' === $_POST['evt_action'] &&
			check_admin_referer( self::NONCE_SYNC_CENTRES, '_evt_centres_nonce' )
		) {
			$redirect_args = array(
				'post_type' => EventPostType::POST_TYPE,
				'page'      => self::PAGE,
			);

			try {
				$result = CentreCatalogueSync::sync( true );
				$msg    = sprintf(
					'Catálogo actualizado correctamente (%d centros, %d activos). SHA-256: %s',
					$result['records'],
					$result['active'],
					substr( $result['sha256'], 0, 12 ) . '…'
				);

				$redirect_args['updated'] = 'synced';
				$redirect_args['msg']     = rawurlencode( $msg );
			} catch ( RuntimeException $e ) {
				$redirect_args['error'] = rawurlencode( $e->getMessage() );
			}

			self::leave( add_query_arg( $redirect_args, admin_url( 'edit.php' ) ) );
		}
	}








	private static function leave( string $url ): void {
		if ( apply_filters( 'evt_exit_throws', false, $url ) ) {

			throw new ExitSignal( $url );
		}
		wp_safe_redirect( $url );
		exit;
	}






	public static function render(): void {
		if ( ! EventAccess::is_manager() ) {
			wp_die( esc_html( 'No tiene permiso para ver los ajustes del aplicativo de eventos.' ), '', array( 'response' => 403 ) );
		}

		$post_types = array(
			EventPostType::POST_TYPE    => 'Eventos y sus páginas',
			SpeakerPostType::POST_TYPE  => 'Ponentes',
			ActivityPostType::POST_TYPE => 'Actividades',
		);
		$taxonomies = array(
			EventTaxonomies::AREA   => 'Área organizadora',
			EventTaxonomies::TYPE   => 'Tipología',
			EventTaxonomies::COURSE => 'Curso escolar',
		);
		?>
		<div class="wrap">
			<h1>Ajustes y diagnóstico de eventos</h1>
			<p class="description" style="max-width:46rem">
				Esta pantalla cuenta lo que el aplicativo tiene montado en este sitio.
				Si algo sale «sin registrar», es que el snippet correspondiente no está activo.
			</p>

			<?php if ( isset( $_GET['updated'] ) && 'synced' === $_GET['updated'] && ! empty( $_GET['msg'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( sanitize_text_field( wp_unslash( (string) $_GET['msg'] ) ) ); ?></p></div>
			<?php elseif ( isset( $_GET['error'] ) && ! empty( $_GET['error'] ) ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php echo esc_html( sanitize_text_field( wp_unslash( (string) $_GET['error'] ) ) ); ?></p></div>
			<?php endif; ?>

			<h2>Tipos de contenido</h2>
			<table class="widefat striped" style="max-width:46rem">
				<tbody>
				<?php foreach ( $post_types as $slug => $label ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $label ); ?></strong><br /><code><?php echo esc_html( $slug ); ?></code></td>
						<td><?php echo post_type_exists( $slug ) ? 'Registrado' : 'Sin registrar'; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<h2>Taxonomías</h2>
			<table class="widefat striped" style="max-width:46rem">
				<tbody>
				<?php foreach ( $taxonomies as $slug => $label ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $label ); ?></strong><br /><code><?php echo esc_html( $slug ); ?></code></td>
						<td>
							<?php
							if ( ! taxonomy_exists( $slug ) ) {
								echo 'Sin registrar';
							} else {
								$total = wp_count_terms(
									array(
										'taxonomy'   => $slug,
										'hide_empty' => false,
									)
								);
								echo esc_html( sprintf( 'Registrada, %d términos', is_wp_error( $total ) ? 0 : (int) $total ) );
							}
							?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<h2>Roles del aplicativo</h2>
			<?php if ( ! function_exists( 'evt_roles_status' ) ) : ?>
				<p>El snippet <code>EVT — Roles y perfiles</code> no está activo, así que no hay roles que revisar.</p>
			<?php else : ?>
				<table class="widefat striped" style="max-width:46rem">
					<tbody>
					<?php foreach ( evt_roles_status() as $slug => $estado ) : ?>
						<tr>
							<td><strong><?php echo esc_html( (string) $estado['label'] ); ?></strong><br /><code><?php echo esc_html( $slug ); ?></code></td>
							<td>
								<?php
								if ( empty( $estado['exists'] ) ) {
									echo 'Falta el rol';
								} elseif ( ! empty( $estado['missing'] ) ) {
									echo esc_html( 'Sin ' . implode( ', ', (array) $estado['missing'] ) );
								} else {
									echo 'Correcto';
								}
								?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<h2>Catálogo de centros educativos</h2>
			<?php
			$status       = CentreCatalogue::status();
			$total_count  = CentreCatalogue::count();
			$active_count = CentreCatalogue::active_count();
			$is_ready     = $total_count > 0;
			$has_source   = '' !== CentreCatalogueSync::manifest_url();
			?>
			<table class="widefat striped" style="max-width:46rem">
				<tbody>
					<tr>
						<th style="width:40%;">Estado</th>
						<td>
							<?php if ( $is_ready ) : ?>
								<span class="dashicons dashicons-yes-alt" style="color:#46b450;" aria-hidden="true"></span> Disponible
							<?php else : ?>
								<span class="dashicons dashicons-warning" style="color:#dc3232;" aria-hidden="true"></span> No disponible (catálogo vacío)
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th>Fuente configurada</th>
						<td><?php echo $has_source ? 'Sí' : 'No'; ?></td>
					</tr>
					<tr>
						<th>Registros totales</th>
						<td><strong><?php echo esc_html( (string) $total_count ); ?></strong></td>
					</tr>
					<tr>
						<th>Registros activos</th>
						<td><strong style="color:#46b450;"><?php echo esc_html( (string) $active_count ); ?></strong></td>
					</tr>
					<tr>
						<th>Registros inactivos</th>
						<td><?php echo esc_html( (string) ( $total_count - $active_count ) ); ?></td>
					</tr>
					<tr>
						<th>Fecha del catálogo</th>
						<td><?php echo esc_html( '' !== $status['catalogue_updated_at'] ? $status['catalogue_updated_at'] : '—' ); ?></td>
					</tr>
					<tr>
						<th>Última comprobación</th>
						<td><?php echo esc_html( '' !== $status['last_checked_at'] ? $status['last_checked_at'] : '—' ); ?></td>
					</tr>
					<tr>
						<th>Última actualización correcta</th>
						<td><?php echo esc_html( '' !== $status['last_success_at'] ? $status['last_success_at'] : '—' ); ?></td>
					</tr>
					<tr>
						<th>SHA-256 actual</th>
						<td>
							<?php if ( '' !== $status['sha256'] ) : ?>
								<code style="font-size:0.85em;"><?php echo esc_html( $status['sha256'] ); ?></code>
							<?php else : ?>
								<em>Ninguno</em>
							<?php endif; ?>
						</td>
					</tr>
					<?php if ( '' !== $status['last_error'] ) : ?>
						<tr>
							<th style="color:#dc3232;">Último error</th>
							<td style="color:#dc3232;"><code><?php echo esc_html( $status['last_error'] ); ?></code></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>

			<div style="margin-top:1rem;">
				<form method="post" action="">
					<?php wp_nonce_field( self::NONCE_SYNC_CENTRES, '_evt_centres_nonce' ); ?>
					<input type="hidden" name="evt_action" value="sync_centres" />
					<button type="submit" class="button button-secondary">
						Actualizar catálogo ahora
					</button>
				</form>
			</div>

			<h2>Su acotado por área</h2>
			<?php
			$areas = EventAccess::user_areas();
			$names = array();
			foreach ( $areas as $term_id ) {
				$term = get_term( $term_id, EventTaxonomies::AREA );
				if ( $term instanceof \WP_Term ) {
					$names[] = $term->name;
				}
			}
			?>
			<p>
				<?php if ( EventAccess::can_edit_all_areas() ) : ?>
					Ve y edita los eventos de todas las áreas.
				<?php elseif ( array() === $names ) : ?>
					No tiene ningún área asignada en su perfil, así que no ve ni edita ningún evento.
				<?php else : ?>
					<?php echo esc_html( 'Acotado a: ' . implode( ', ', $names ) ); ?>
				<?php endif; ?>
			</p>
		</div>
		<?php
	}
}








namespace Evt;

use Evt\Access\EventAccess;
use Evt\Admin\EventAdmin;
use Evt\Admin\Settings;
use Evt\Centre\CentreCatalog;
use Evt\Centre\CentreCatalogueSync;
use Evt\Meta\EventMetaRegistration;
use Evt\Meta\ProgrammeMetaRegistration;
use Evt\Meta\RegistrationMetaRegistration;
use Evt\PostType\ActivityPostType;
use Evt\PostType\EventPostType;
use Evt\PostType\RegistrationPostType;
use Evt\PostType\SpeakerPostType;
use Evt\PublicFront\Assets;
use Evt\PublicFront\CustomCode;
use Evt\PublicFront\EventList;
use Evt\PublicFront\EventView;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\Home;
use Evt\PublicFront\PageForm;
use Evt\PublicFront\RegistrationFiles;
use Evt\PublicFront\Registrations;
use Evt\PublicFront\SignupForm;
use Evt\PublicFront\Shell;
use Evt\Taxonomy\EventTaxonomies;









final class App {








	public const PAGES_PARENT = 'evt_pages_parent';






	public static function boot(): void {
		static $booted = false;
		if ( $booted ) {
			return;
		}
		$booted = true;

		add_action( 'init', array( EventTaxonomies::class, 'register' ), 9 );
		add_action( 'init', array( EventPostType::class, 'register' ), 10 );
		add_action( 'init', array( SpeakerPostType::class, 'register' ), 10 );
		add_action( 'init', array( ActivityPostType::class, 'register' ), 10 );
		add_action( 'init', array( RegistrationPostType::class, 'register' ), 10 );
		add_action( 'init', array( EventPostType::class, 'grant_caps_to_roles' ), 11 );

		EventMetaRegistration::register();
		ProgrammeMetaRegistration::register();
		RegistrationMetaRegistration::register();
		EventAccess::register();



		add_filter( 'evt_page_slug', array( self::class, 'page_slug' ), 10, 2 );




		Assets::register();
		Shell::register();



		Registrations::register();



		RegistrationFiles::register();
		SignupForm::register();
		EventList::register();
		EventWorkspace::register();
		PageForm::register();
		Home::register();





		EventView::register();





		CustomCode::register();

		EventAdmin::register();
		CentreCatalog::register();
		CentreCatalogueSync::register_cron();
		Settings::register();
	}








	public static function page_slug( string $slug, string $section = '' ): string {
		unset( $section );
		$padre = trim( (string) get_option( self::PAGES_PARENT, '' ), '/' );
		if ( '' === $padre || '' === $slug || 0 === strpos( $slug, $padre . '/' ) ) {
			return $slug;
		}
		return $padre . '/' . $slug;
	}
}


\Evt\PublicFront\Assets::set_inline( array (
  'css/evt-app.css' => '/* Aplicativo de eventos: armazón (cabecera, pestañas, pie) y las piezas que
   comparten sus pantallas —tarjetas, tablas, formularios y el panel de
   apariencia—. Una sola hoja: el aplicativo tiene cinco pantallas y partirla
   en tres solo añade un sitio más donde mirar.

   Se monta encima de Bootstrap 5, que en el subsitio de eventos lo carga un
   snippet aparte. Donde Bootstrap no está, `body.evt-sin-bootstrap` pinta lo
   imprescindible por su cuenta: botones, avisos y pastillas. */

:root {
  /* Un azul sobrio como primario: es el color con el que se dibujó el diseño
     (.design/) y el que usan las capturas de la documentación. Quien despliegue
     el aplicativo lo cambia aquí, o desde su propio CSS. */
  --evt-pri: #1b4f8a;
  --evt-pri-cont: #e3ecf7;
  --evt-sup: #fff;
  --evt-sup-2: #f4f6f9;
  --evt-fondo: #f7f8fa;
  --evt-texto: #1c2024;
  --evt-texto-2: #5a6672;
  --evt-pie-fondo: #05395c;
  --evt-linea: #e4e7eb;
  --evt-ok: #1f6b45;
  --evt-ok-cont: #e4f0e8;
  --evt-esp: #8a5a12;
  --evt-esp-cont: #f9efe0;
  --evt-mal: #a8342c;
  --evt-mal-cont: #fae9e7;
  --evt-inf: #1b4f8a;
  --evt-inf-cont: #e3ecf7;
  --evt-e1: 0 1px 2px rgba(16, 24, 40, .05), 0 1px 3px rgba(16, 24, 40, .07);
  --evt-e2: 0 2px 4px rgba(16, 24, 40, .05), 0 4px 12px rgba(16, 24, 40, .09);
  --evt-e3: 0 4px 8px rgba(16, 24, 40, .08), 0 8px 24px rgba(16, 24, 40, .14);
  --evt-r: 12px;
  /* El recuadro amarillo de «Solo administración». Los contrastes están
     medidos, no supuestos (fórmula de luminancia relativa de la WCAG 2.1):

       texto  #4a3a05 sobre fondo #fdf6dd → 10,22:1   (pide 4,5:1) ✔
       marca  #ffffff sobre fondo #6b5200 →  7,42:1   (pide 4,5:1) ✔
       borde  #a67c00 sobre la caja       →  3,52:1   (pide 3:1)   ✔
       borde  #a67c00 sobre la página     →  3,59:1   (pide 3:1)   ✔

     El amarillo no es el único indicador: la etiqueta «Solo administración»
     va escrita dentro del recuadro. */
  --evt-adm-fondo: #fdf6dd;
  --evt-adm-borde: #a67c00;
  --evt-adm-texto: #4a3a05;
  --evt-adm-marca: #6b5200;
  --evt-ancho: 1128px;
}

/* --- página plana -------------------------------------------------------- */

/* La cabecera y el pie los pintamos nosotros, así que los del tema sobran, y
   también el título de la entrada: lo repite nuestro `h1`. Solo en nuestras
   páginas (`body.evt-app`, que pone Shell) y por selector, no por
   `!important`: si un tema no trae estos elementos, no pasa nada.

   Algunos temas los llaman `#main-header` y `#main-footer`, y con su maquetador son
   plantillas propias (`.et-l--header`, `.et-l--footer`). Los temas de bloques
   los sacan en `wp-block-template-part`, que NO cuelga de `body`, así que aquí
   no vale el combinador `>`. */
body.evt-app #main-header,
body.evt-app #top-header,
body.evt-app .et-l--header,
body.evt-app .et-l--footer,
body.evt-app #main-footer,
body.evt-app .site-header,
body.evt-app .site-footer,
body.evt-app header.wp-block-template-part,
body.evt-app footer.wp-block-template-part,
body.evt-app .et_pb_title_container,
body.evt-app .entry-title,
body.evt-app .page-title,
body.evt-app .wp-block-post-title {
  display: none;
}

body.evt-app { background: var(--evt-fondo); overflow-x: clip; }
body.evt-app #page-container { padding-top: 0; }

/* Hay temas que reservan la columna de la barra lateral aunque no haya ninguna
   (`et_right_sidebar`): eso descentra el contenido, y con él la cabecera y las
   pestañas de ancho completo, que se calculan contra su contenedor. */
body.evt-app #main-content .container,
body.evt-app #main-content #content-area,
body.evt-app #main-content #left-area {
  width: 100%;
  max-width: none;
  padding: 0;
}
body.evt-app #main-content #sidebar { display: none; }
/* Y la raya vertical que la separaba, que algunos temas dibujan con un pseudoelemento
   absoluto y sobrevive a esconder la columna. */
body.evt-app #main-content .container::before { display: none; }

/* El tema mete el contenido en una columna estrecha con su propio relleno. La
   cabecera, las pestañas y el pie son de ancho completo, así que rompen esa
   columna y vuelven a centrarse por dentro (`--evt-ancho`).

   `left: 50%` + `translateX(-50%)` y no márgenes negativos: los márgenes se
   calculan contra el contenedor y fallan si el tema no lo tiene centrado. */
body.evt-app .evt-top,
body.evt-app .evt-tabs,
body.evt-app .evt-hoja,
body.evt-app .evt-pie {
  position: relative;
  left: 50%;
  transform: translateX(-50%);
  width: 100vw;
  max-width: 100vw;
}

/* El pie, abajo del todo: `sticky` con `top: 100vh` lo deja pegado al borde
   inferior mientras sobre sitio, y baja con la página cuando el contenido es
   largo. Con `sticky`, `left` ya no desplaza —marca el umbral de pegado—, así
   que el ancho completo lo dan los márgenes negativos de siempre. */
body.evt-app .evt-pie {
  position: sticky;
  top: 100vh;
  left: auto;
  transform: none;
  width: auto;
  margin-left: calc(50% - 50vw);
  margin-right: calc(50% - 50vw);
}

/* Sin la cabecera del tema, su hueco superior sobra. */
body.evt-app .wp-site-blocks,
body.evt-app .wp-site-blocks > main,
body.evt-app main.wp-block-group,
body.evt-app .site-main,
body.evt-app #content,
body.evt-app #main-content,
body.evt-app .wp-site-blocks > *,
body.evt-app .entry-content,
body.evt-app .wp-block-post-content {
  padding-top: 0;
  padding-bottom: 0;
  margin-top: 0;
  margin-bottom: 0;
}

body.evt-app .et_pb_row,
body.evt-app .et_pb_section,
body.evt-app .site-content,
body.evt-app #et-main-area {
  max-width: none;
  padding: 0;
  margin: 0;
}

/* El único `!important` del armazón, y con motivo: los temas de bloques le
   escriben al `<main>` un `style="margin-top: …"` en la propia plantilla, y
   contra un estilo en línea no gana ningún selector. */
body.evt-app main { margin-top: 0 !important; margin-bottom: 0 !important; }
body.evt-app main > .wp-block-group { padding-top: 0 !important; padding-bottom: 0 !important; }

/* Una tabla más ancha que la pantalla —aunque vaya en su caja con scroll—
   hacía crecer el viewport en el móvil. Va en <html>: en <body> no basta. */
html:has(> body.evt-app) { overflow-x: clip; }

/* --- cabecera ------------------------------------------------------------ */

/* El transform de ancho completo crea una capa: el menú de la persona debe
   quedar por encima de las pestañas y del contenido, que también la tienen. */
.evt-top { z-index: 1; background: var(--evt-sup); border-bottom: 1px solid var(--evt-linea); }

.evt-top-fila {
  max-width: var(--evt-ancho);
  margin: 0 auto;
  padding: 14px 24px;
  display: flex;
  align-items: center;
  gap: 14px;
  flex-wrap: wrap;
}

/* El escudo institucional. Va incrustado y no enlazado: el artefacto de
   producción es un Code Snippet, no hay carpeta desde la que servir un
   fichero. */
.evt-logo {
  display: block;
  width: 104px;
  height: 60px;
  flex: 0 0 auto;
  background: url(data:image/png;base64,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) center / contain no-repeat;
}

.evt-marca { line-height: 1.3; }
.evt-marca small {
  display: block;
  font-size: 11.5px;
  color: var(--evt-texto-2);
  max-width: 30ch;
  border-left: 1px solid var(--evt-linea);
  padding-left: 14px;
}

.evt-marca-app {
  margin-left: 12px;
  padding-left: 14px;
  border-left: 1px solid var(--evt-linea);
  font-size: 18px;
  font-weight: 700;
  letter-spacing: -.02em;
  color: var(--evt-pri);
  text-decoration: none;
}

/* --- quién eres y con qué área ------------------------------------------- */

/* Tres líneas —nombre, rol y área— y no una píldora: en un aplicativo acotado
   por área, saber con cuál se está mirando es media respuesta a «¿por qué no
   veo este evento?». El menú es un `details` nativo: sin guion, y se cierra
   solo al navegar. */
.evt-yo { margin-left: auto; position: relative; }
.evt-yo > summary {
  list-style: none;
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  border-radius: 12px;
  padding: 4px 6px;
}
.evt-yo > summary::-webkit-details-marker { display: none; }
.evt-yo > summary:hover, .evt-yo[open] > summary { background: var(--evt-sup-2); }
.evt-yo-ava {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  font-size: 13px;
  font-weight: 700;
  background: var(--evt-sup-2);
  color: var(--evt-texto);
}
.evt-yo-txt { display: flex; flex-direction: column; line-height: 1.25; text-align: left; }
.evt-yo-n { display: flex; align-items: center; gap: 6px; font-size: 14px; font-weight: 600; color: var(--evt-texto); }
.evt-yo-r { font-size: 12.5px; color: var(--evt-texto-2); }
.evt-yo-a { font-weight: 600; color: var(--evt-pri); }
.evt-yo-a::before { content: ""; display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: currentColor; margin-right: 6px; vertical-align: 1px; }
.evt-yo-menu {
  position: absolute;
  right: 0;
  top: calc(100% + 6px);
  min-width: 12rem;
  padding: 6px;
  background: var(--evt-sup);
  border: 1px solid var(--evt-linea);
  border-radius: 12px;
  box-shadow: var(--evt-e2);
  z-index: 30;
  display: grid;
}
.evt-yo-menu a { padding: 10px 12px; border-radius: 8px; color: var(--evt-texto); text-decoration: none; font-size: 14px; }
.evt-yo-menu a:hover { background: var(--evt-sup-2); color: var(--evt-pri); }

/* --- pestañas ------------------------------------------------------------ */

.evt-tabs { background: var(--evt-sup); border-bottom: 1px solid var(--evt-linea); }

.evt-tabs-fila {
  max-width: var(--evt-ancho);
  margin: 0 auto;
  padding: 0 24px;
  display: flex;
  gap: 4px;
  overflow-x: auto;
}

.evt-tab {
  display: flex;
  align-items: center;
  gap: 8px;
  min-height: 48px;
  padding: 0 16px;
  font-size: 14.5px;
  color: var(--evt-texto-2);
  border-bottom: 3px solid transparent;
  white-space: nowrap;
  text-decoration: none;
}

.evt-tab:hover { color: var(--evt-pri); text-decoration: none; }
.evt-tab-on { color: var(--evt-pri); font-weight: 700; border-bottom-color: var(--evt-pri); }

.evt-tab-n {
  padding: .05em .5em;
  border-radius: 999px;
  background: var(--evt-esp-cont);
  color: var(--evt-esp);
  font-size: 12px;
  font-weight: 700;
}

/* --- lienzo -------------------------------------------------------------- */

.evt-hoja { max-width: var(--evt-ancho); margin: 0 auto; padding: 28px 24px 44px; }

/* Al romper la columna del tema, la hoja pasa a ser de ancho completo: el
   centrado del contenido lo hace este relleno lateral. */
body.evt-app .evt-hoja {
  padding-left: max(24px, calc(50% - var(--evt-ancho) / 2));
  padding-right: max(24px, calc(50% - var(--evt-ancho) / 2));
}

.evt-hoja .evt-tabs { margin: 0 0 28px; }

.evt-h1 { font-size: 27px; font-weight: 700; letter-spacing: -.02em; margin: 0 0 4px; }
.evt-sub { margin: 0 0 22px; color: var(--evt-texto-2); font-size: 14.5px; max-width: 70ch; }

/* La fila del título con su acción principal a la derecha. */
.evt-h1-fila { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; margin: 0 0 4px; }
.evt-h1-fila .evt-h1 { margin: 0; }
.evt-h1-fila .evt-acciones { margin-left: auto; }

.evt-acciones { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

/* Dentro de una tabla, los botones de acción no se parten en varias líneas:
   la columna es estrecha y dos iconos apilados se leen como dos filas. Que la
   tabla se desplace en horizontal es lo que ya hace `.evt-tabla-caja`. */
.evt-tabla td .evt-acciones { flex-wrap: nowrap; }
.evt-tabla td .evt-acciones > * { flex: 0 0 auto; }

/* --- pie ----------------------------------------------------------------- */

.evt-pie { position: sticky; top: 100vh; background: var(--evt-pie-fondo); color: #fff; }

.evt-pie div {
  max-width: var(--evt-ancho);
  margin: 0 auto;
  padding: 18px 24px;
  display: flex;
  gap: 8px 24px;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: baseline;
  font-size: 13px;
}

.evt-pie-quien { display: flex; gap: 8px 18px; flex-wrap: wrap; align-items: baseline; }
.evt-pie-ate { color: rgba(255, 255, 255, .72); }
.evt-pie-enlaces { display: flex; gap: 18px; flex-wrap: wrap; }
.evt-pie a { color: #fff; text-decoration: underline; }
.evt-pie a:hover { color: #fff; text-decoration: none; }

/* --- la portada ---------------------------------------------------------- */

.evt-inicio-accesos { display: grid; gap: 14px; grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr)); }
.evt-inicio-acceso {
  display: block;
  padding: 20px;
  background: var(--evt-sup);
  border: 1px solid var(--evt-linea);
  border-radius: var(--evt-r);
  box-shadow: var(--evt-e1);
  text-decoration: none;
  color: var(--evt-texto);
}
.evt-inicio-acceso:hover { border-color: var(--evt-pri); color: var(--evt-pri); text-decoration: none; }
.evt-inicio-titulo { display: block; font-size: 17px; font-weight: 700; letter-spacing: -.01em; }
.evt-inicio-texto { display: block; margin-top: 4px; font-size: 13.5px; color: var(--evt-texto-2); }

/* --- fichas de recuento -------------------------------------------------- */

.evt-cifras { display: grid; gap: 14px; grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr)); margin: 0 0 22px; padding: 0; list-style: none; }
.evt-cifra {
  padding: 18px 20px;
  background: var(--evt-sup);
  border: 1px solid var(--evt-linea);
  border-radius: var(--evt-r);
  box-shadow: var(--evt-e1);
}
.evt-cifra strong { display: block; font-size: 30px; font-weight: 700; line-height: 1.1; letter-spacing: -.02em; font-variant-numeric: tabular-nums; }
.evt-cifra span { display: block; margin-top: 2px; font-size: 13.5px; color: var(--evt-texto-2); }

/* --- tarjetas y bloques de formulario ------------------------------------ */

.evt-tarjeta {
  padding: 20px;
  margin: 0 0 16px;
  background: var(--evt-sup);
  border: 1px solid var(--evt-linea);
  border-radius: var(--evt-r);
  box-shadow: var(--evt-e1);
}
.evt-tarjeta > legend,
.evt-tarjeta > h2 { float: none; width: auto; margin: 0 0 14px; padding: 0; font-size: 16px; font-weight: 700; letter-spacing: -.01em; }
.evt-tarjeta > p { margin: -8px 0 14px; font-size: 13.5px; color: var(--evt-texto-2); max-width: 68ch; }

.evt-form { max-width: 100%; }
.evt-form label { display: block; margin-bottom: 4px; font-weight: 600; font-size: 14px; }
.evt-form input[type="text"],
.evt-form input[type="url"],
.evt-form input[type="date"],
.evt-form input[type="number"],
.evt-form input[type="search"],
.evt-form select,
.evt-form textarea {
  width: 100%;
  min-height: 44px;
  padding: 8px 12px;
  font: inherit;
  color: var(--evt-texto);
  background: var(--evt-sup);
  border: 1px solid #c3cad2;
  border-radius: 8px;
}
.evt-form textarea { min-height: 8rem; line-height: 1.5; }
.evt-form input::placeholder, .evt-form textarea::placeholder { color: #767676; font-style: italic; opacity: 1; }
.evt-form small, .evt-form .evt-nota { display: block; margin-top: 4px; font-size: 12.5px; color: var(--evt-texto-2); max-width: 60ch; }

/* Los campos cortos caben en una línea y se apilan solos cuando no. */
.evt-form-fila {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr));
  gap: 14px;
  align-items: start;
  margin-bottom: 14px;
}
.evt-form-fila > * { min-width: 0; }
.evt-form-campo { margin-bottom: 14px; }

/* Casillas en columna, con el rótulo pegado a su casilla. En vertical y no en
   línea porque los nombres de los ponentes son largos y desiguales: en línea,
   el nombre de uno acaba al lado de la casilla del siguiente. */
.evt-check { display: flex; gap: 8px; align-items: baseline; margin: 0 0 6px; }

/* El rótulo de la segunda sede de un día: sin este aire queda pegado a la
   tabla de la sede anterior y parece su pie, no el encabezado del bloque
   siguiente. Solo aparece cuando el día tiene más de una sede (ADR-0024). */
.evt-tarjeta .evt-tabla-caja + h3 { margin-top: 22px; }

/* La fila del filtro de participantes: los dos formularios —el de buscar y el
   de exportar— alineados por abajo, para que «Filtrar» y «Exportar a CSV»
   queden a la misma altura. Con `evt-form-fila` el segundo se subía arriba,
   porque es más corto. */
/* --- la botonera ---------------------------------------------------------
   Botones de icono: cuadrados, con el icono centrado y el texto solo para
   lectores de pantalla. El bocadillo lo pone Bootstrap si está; si no, queda
   el `title` del navegador. El tamaño no baja de 36 px porque es el objetivo
   táctil mínimo que se puede acertar con el dedo. */
.evt-icono {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 36px;
  min-height: 36px;
  padding: 6px;
  line-height: 0;
}
.evt-icono svg { display: block; }

/* --- el interruptor de publicación --------------------------------------
   La casilla de verdad sigue ahí, invisible pero enfocable: el `:focus-visible`
   se dibuja sobre la pista, así que con teclado se ve dónde está. El rótulo va
   escrito al lado —«Publicado» / «Borrador»— porque el color y la posición no
   pueden ser el único indicador. */
.evt-switch { display: inline-flex; align-items: center; gap: 8px; }
.evt-switch-caja { display: inline-flex; align-items: center; gap: 8px; cursor: pointer; margin: 0; }
.evt-switch-input { position: absolute; opacity: 0; width: 36px; height: 20px; margin: 0; cursor: pointer; }
.evt-switch-pista {
  position: relative;
  flex: 0 0 auto;
  width: 36px;
  height: 20px;
  border-radius: 999px;
  background: var(--evt-linea);
  transition: background .15s ease-in-out;
}
.evt-switch-pista::after {
  content: "";
  position: absolute;
  top: 2px;
  left: 2px;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: #fff;
  box-shadow: var(--evt-e1);
  transition: transform .15s ease-in-out;
}
.evt-switch-input:checked + .evt-switch-pista { background: var(--evt-pri); }
.evt-switch-input:checked + .evt-switch-pista::after { transform: translateX(16px); }
.evt-switch-input:focus-visible + .evt-switch-pista { outline: 2px solid var(--evt-pri); outline-offset: 2px; }
.evt-switch-input:disabled + .evt-switch-pista { opacity: .6; }
.evt-switch-txt { font-size: 13px; color: var(--evt-texto-2); }

@media ( prefers-reduced-motion: reduce ) {
  .evt-switch-pista,
  .evt-switch-pista::after { transition: none; }
}

.evt-filtro { display: flex; flex-wrap: wrap; gap: 18px; align-items: flex-end; margin-bottom: 22px; }
.evt-filtro > form { flex: 1 1 22rem; margin: 0; }
.evt-filtro > form:last-child { flex: 0 0 auto; }
.evt-check input { margin: 0; }

/* Un evento cerrado se abre entero, pero sin poder guardar. El `fieldset`
   `disabled` ya apaga los controles; esto lo hace visible, porque un botón
   apagado sin señal se lee como una avería. El borde no es el único
   indicador: arriba hay un aviso escrito que dice por qué. */
.evt-solo-lectura { margin: 0; padding: 0; border: 0; opacity: .72; }
.evt-solo-lectura button,
.evt-solo-lectura input,
.evt-solo-lectura select,
.evt-solo-lectura textarea { cursor: not-allowed; }

/* Un dato que no se puede cambiar: se enseña como lo que es, un hecho, y no
   como un campo apagado. Un `<select disabled>` invita a intentarlo y no
   explica por qué no se puede; el candado y el `<small>` de al lado sí.
   El tipo de una sección es el caso (ADR-0019). */
.evt-rotulo { display: block; font-size: 13.5px; font-weight: 600; margin-bottom: 6px; }
.evt-fijo {
	display: inline-flex;
	align-items: center;
	gap: 8px;
	margin: 0;
	min-height: 44px;
	padding: 0 14px;
	border: 1px solid var(--evt-linea);
	border-radius: 10px;
	background: var(--evt-sup-2);
	color: var(--evt-texto);
}
.evt-fijo svg { color: var(--evt-texto-2); flex: none; }

.evt-error { display: block; margin-top: 4px; font-size: 13px; font-weight: 600; color: var(--evt-mal); }

/* Un anillo de foco visible en todo lo que se puede pulsar o escribir: sin él
   no se sabe dónde está el cursor al navegar con el tabulador. */
.evt-app a:focus-visible,
.evt-app button:focus-visible,
.evt-app input:focus-visible,
.evt-app select:focus-visible,
.evt-app textarea:focus-visible,
.evt-app summary:focus-visible {
  outline: 2px solid var(--evt-pri);
  outline-offset: 2px;
  border-radius: 4px;
}

/* --- tablas -------------------------------------------------------------- */

.evt-tabla-caja { overflow-x: auto; background: var(--evt-sup); border: 1px solid var(--evt-linea); border-radius: var(--evt-r); box-shadow: var(--evt-e1); }
.evt-tabla { width: 100%; border-collapse: collapse; font-size: 14px; }
.evt-tabla th, .evt-tabla td { padding: 12px 14px; text-align: left; vertical-align: top; border-bottom: 1px solid var(--evt-linea); }
.evt-tabla thead th { background: var(--evt-sup-2); font-size: 13px; font-weight: 700; white-space: nowrap; }
.evt-tabla tbody tr:last-child td { border-bottom: 0; }
.evt-tabla .evt-num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.evt-tabla .evt-slug { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 12.5px; color: var(--evt-texto-2); }
.evt-tabla .evt-acciones { justify-content: flex-start; }
.evt-tabla [hidden] { display: none !important; }

/* En pantalla estrecha la tabla pasa a tarjetas: cada fila, un bloque, y cada
   celda rotulada con el `data-rotulo` que pinta la pantalla. */
@media (max-width: 720px) {
  .evt-tabla-caja { border: 0; background: none; box-shadow: none; }
  .evt-tabla, .evt-tabla tbody, .evt-tabla tr, .evt-tabla td { display: block; width: auto; }
  .evt-tabla thead { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; }
  .evt-tabla tr {
    margin-bottom: 12px;
    padding: 6px 14px;
    background: var(--evt-sup);
    border: 1px solid var(--evt-linea);
    border-radius: var(--evt-r);
    box-shadow: var(--evt-e1);
  }
  .evt-tabla td { border-bottom: 0; padding: 6px 0; display: flex; gap: 12px; }
  .evt-tabla td::before {
    content: attr(data-rotulo);
    flex: 0 0 8rem;
    font-size: 12.5px;
    font-weight: 700;
    color: var(--evt-texto-2);
  }
  .evt-tabla td:empty { display: none; }
}

/* --- pastillas de estado ------------------------------------------------- */

.evt-state {
  display: inline-block;
  padding: .3em .72em;
  border-radius: 999px;
  font-size: .75rem;
  font-weight: 700;
  line-height: 1.4;
  white-space: nowrap;
  background: var(--evt-sup-2);
  color: var(--evt-texto-2);
}
.evt-state-abierto { background: var(--evt-ok-cont); color: var(--evt-ok); }
.evt-state-proximo { background: var(--evt-inf-cont); color: var(--evt-inf); }
.evt-state-finalizado { background: var(--evt-sup-2); color: var(--evt-texto-2); }
.evt-state-draft { background: var(--evt-esp-cont); color: var(--evt-esp); }
.evt-state-publish { background: var(--evt-ok-cont); color: var(--evt-ok); }

/* --- avisos -------------------------------------------------------------- */

.evt-aviso { margin: 0 0 18px; }
.evt-sin-bootstrap .evt-aviso {
  padding: 12px 16px;
  border-radius: 10px;
  background: var(--evt-inf-cont);
  color: #143a63;
  font-size: 14px;
}
.evt-sin-bootstrap .evt-aviso-warning { background: var(--evt-esp-cont); color: #5f3f0e; }
.evt-sin-bootstrap .evt-aviso-success { background: var(--evt-ok-cont); color: #175034; }
.evt-sin-bootstrap .evt-aviso-danger { background: var(--evt-mal-cont); color: #7d2620; }

.evt-vacio { padding: 40px 20px; text-align: center; color: var(--evt-texto-2); font-size: 14.5px; }

/* --- botones ------------------------------------------------------------- */

/* El aspecto lo ponemos nosotros solo donde no hay Bootstrap; donde lo hay,
   `Assets::button_class()` ya trae sus clases y el botón es el del sitio. */
.evt-sin-bootstrap .evt-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 44px;
  padding: 0 18px;
  border: 0;
  border-radius: 999px;
  background: var(--evt-pri-cont);
  color: var(--evt-pri);
  font: inherit;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
}
.evt-sin-bootstrap .evt-btn-primary { background: var(--evt-pri); color: #fff; }
.evt-sin-bootstrap .evt-btn:hover { text-decoration: none; filter: brightness(.95); }

/* Con Bootstrap: solo sus variables, y solo bajo `body.evt-app`. Fuera de
   estas páginas Bootstrap sigue siendo el de siempre. `.btn-primary` no lee
   `--bs-primary` —la 5.3 le escribe el color en `--bs-btn-bg`—, así que hay
   que tocar las del botón. */
body.evt-app {
  --bs-primary: #1b4f8a;
  --bs-primary-rgb: 27, 79, 138;
  --bs-link-color: #1b4f8a;
  --bs-link-hover-color: #123a68;
}
body.evt-app .btn { border-radius: 999px; min-height: 44px; font-weight: 600; }
body.evt-app .btn-primary {
  --bs-btn-bg: var(--evt-pri);
  --bs-btn-border-color: var(--evt-pri);
  --bs-btn-hover-bg: #163f6f;
  --bs-btn-hover-border-color: #163f6f;
  --bs-btn-active-bg: #163f6f;
  --bs-btn-active-border-color: #163f6f;
}
body.evt-app .btn-light {
  --bs-btn-bg: var(--evt-pri-cont);
  --bs-btn-border-color: var(--evt-pri-cont);
  --bs-btn-color: var(--evt-pri);
  --bs-btn-hover-bg: #d6e4f3;
  --bs-btn-hover-border-color: #d6e4f3;
  --bs-btn-hover-color: var(--evt-pri);
}
/* La acción destructiva se distingue sin gritar: tonal, no roja rellena. */
body.evt-app .evt-btn-borrar { --bs-btn-bg: var(--evt-mal-cont); --bs-btn-border-color: var(--evt-mal-cont); --bs-btn-color: var(--evt-mal); }
.evt-sin-bootstrap .evt-btn-borrar { background: var(--evt-mal-cont); color: var(--evt-mal); }

/* El diálogo de confirmación (SweetAlert2) se pinta al final del `body`, así
   que sus clases —`evt-app`, `evt-sin-bootstrap`— siguen valiendo y los botones
   del diálogo son exactamente los mismos que los de la página. Con
   `buttonsStyling: false` la librería no pone los suyos, y aquí solo queda
   devolverles la separación que ella daba y darle al cuadro nuestro radio.
   Si SweetAlert2 no llega, estas reglas no aplican a nada. */
.swal2-popup { border-radius: var(--evt-r); font: inherit; }
.swal2-popup .swal2-title { font-size: 20px; color: var(--evt-texto); }
.swal2-popup .swal2-html-container { font-size: 15px; color: var(--evt-texto-2); }
.swal2-popup .swal2-actions { gap: 8px; }

/* Los botoncitos de una fila de tabla: subir, bajar, editar, borrar. */
.evt-mini { min-height: 36px; padding: 0 12px; font-size: 13px; }
.evt-mini-icono { width: 36px; min-height: 36px; padding: 0; flex: 0 0 auto; }

/* --- panel de apariencia y su vista previa ------------------------------- */

/* Un `color` nativo al lado de su campo hexadecimal: los dos escriben el mismo
   dato y el guion los mantiene a la par. Quien tenga el guion bloqueado sigue
   pudiendo teclear el hexadecimal, que es el que se guarda. */
.evt-color { display: flex; align-items: center; gap: 8px; }
.evt-color input[type="color"] { width: 44px; height: 44px; padding: 2px; border: 1px solid #c3cad2; border-radius: 8px; background: var(--evt-sup); cursor: pointer; flex: 0 0 auto; }
.evt-color input[type="text"] { max-width: 10rem; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }

/* La vista previa de la cabecera del evento, con lo elegido y en la misma
   pantalla: es la única forma de decidir un color sin publicar y mirar. */
.evt-preview {
  margin: 0 0 16px;
  border: 1px solid var(--evt-linea);
  border-radius: var(--evt-r);
  overflow: hidden;
  box-shadow: var(--evt-e1);
}
.evt-preview-cabecera {
  position: relative;
  padding: 36px 24px 60px;
  text-align: center;
  background: var(--evt-pri);
  color: #fff;
}
.evt-preview-logo { max-height: 72px; width: auto; margin: 0 auto 14px; display: block; }
.evt-preview-titulo { margin: 0; font-size: 30px; font-weight: 700; line-height: 1.2; }
.evt-preview-lema { margin: 8px 0 0; font-size: 17px; opacity: .9; }
/* El color lo toma la silueta con `currentColor`: es el de la hoja que hay
   debajo, igual que en la página del evento. */
.evt-preview-sep { position: absolute; left: 0; right: 0; bottom: -1px; height: 44px; color: var(--evt-sup); }
.evt-preview-sep > span { display: block; height: 100%; }
.evt-preview-sep > span[hidden] { display: none; }
.evt-preview-sep svg { display: block; width: 100%; height: 100%; }
.evt-preview-cuerpo {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 18px 24px;
  background: var(--evt-sup);
  font-size: 14.5px;
  color: var(--evt-texto-2);
}
.evt-preview-cuerpo > span { flex: none; }

/* La forma de las imágenes de personas, la que elige el evento. */
.evt-shape-square img, img.evt-shape-square { border-radius: 8px; }
.evt-shape-circle img, img.evt-shape-circle { border-radius: 50%; aspect-ratio: 1; object-fit: cover; }

/* Las seis familias de `EventMetaKeys::fonts()`. Se declaran como clase y no
   en línea para que la salida siga escapada y sin `style` que revisar. La
   familia se carga fuera de aquí; si no llega, la pila de respaldo funciona. */
.evt-font-open-sans { font-family: "Open Sans", system-ui, sans-serif; }
.evt-font-lato { font-family: Lato, system-ui, sans-serif; }
.evt-font-montserrat { font-family: Montserrat, system-ui, sans-serif; }
.evt-font-source-serif { font-family: "Source Serif 4", Georgia, serif; }
.evt-font-merriweather { font-family: Merriweather, Georgia, serif; }

/* --- archivos gestionados con la biblioteca de medios -------------------- */

/* El selector de medios en vez de un `file` pelado: el sitio tiene 4.597
   adjuntos, y volver a subir el mismo cartel por no poder elegir el que ya
   está es lo que llena la biblioteca de duplicados. La ficha de arriba enseña
   lo que hay puesto —miniatura, nombre de fichero y dimensiones—, porque el
   campo de fichero no enseñaba nada y no se sabía qué había. */
.evt-media {
  padding: 14px;
  border: 1px solid var(--evt-linea);
  border-radius: 10px;
  background: var(--evt-sup);
}
.evt-media.supports-drag-drop { border-style: dashed; }
.evt-media.supports-drag-drop.drag-over {
  border-color: var(--evt-pri);
  box-shadow: 0 0 0 3px rgba(27, 79, 138, .25);
  background: var(--evt-sup-2);
}
.evt-media + .evt-media { margin-top: 12px; }
.evt-media-rotulo { display: block; margin-bottom: 10px; font-weight: 600; }
.evt-media-ficha { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
.evt-media-miniatura {
  flex: 0 0 auto;
  width: 88px;
  height: 88px;
  object-fit: contain;
  border: 1px solid var(--evt-linea);
  border-radius: 8px;
  background: var(--evt-sup-2);
}
.evt-media-datos { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.evt-media-datos strong { font-size: 13.5px; font-weight: 600; overflow-wrap: anywhere; }
.evt-media-datos small { font-size: 12.5px; color: var(--evt-texto-2); font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
.evt-media-vacia { margin: 0 0 10px; font-size: 13.5px; color: var(--evt-texto-2); }
.evt-media-drop { margin: 0 0 8px; font-size: 13.5px; color: var(--evt-texto-2); }
.evt-media-estado { min-height: 1.4em; margin: 0 0 8px; font-size: 13px; font-weight: 600; }
.evt-media-botones { margin: 0 0 4px; }
.evt-media noscript > label { display: block; margin-top: 8px; }
/* `hidden` lo pone y lo quita el guion, y `display:flex` le gana al valor por
   defecto del navegador: hay que decirlo aquí. */
.evt-media [hidden] { display: none; }

/* --- lo que solo ve la administración ------------------------------------ */

/* Una convención del aplicativo, no un adorno de una pantalla: todo lo que
   solo ve quien administra se pinta aquí dentro y se reconoce sin leerlo. Lo
   pinta `Shell::admin_box()`, en un sitio, para que cualquier pantalla futura
   lo reutilice. Los contrastes de estos cuatro colores están calculados arriba,
   en la paleta. */
.evt-solo-admin {
  margin: 0 0 18px;
  padding: 16px 18px 4px;
  background: var(--evt-adm-fondo);
  border: 1px solid var(--evt-adm-borde);
  border-left-width: 5px;
  border-radius: var(--evt-r);
  color: var(--evt-adm-texto);
}
.evt-solo-admin + .evt-solo-admin { margin-top: 18px; }

/* La etiqueta en texto, para quien no distingue el color. */
.evt-solo-admin-marca {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin: 0 0 10px;
  padding: 3px 10px;
  border-radius: 999px;
  background: var(--evt-adm-marca);
  color: #fff;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: .02em;
  text-transform: uppercase;
}
.evt-solo-admin-marca svg { flex: 0 0 auto; }

.evt-solo-admin-titulo { margin: 0 0 4px; font-size: 17px; font-weight: 700; color: var(--evt-adm-texto); }
.evt-solo-admin-porque { margin: 0 0 14px; font-size: 13.5px; max-width: 70ch; color: var(--evt-adm-texto); }

/* Dentro del recuadro el texto sigue siendo el oscuro sobre amarillo: si un
   rótulo o una ayuda heredara el gris de fuera, se caería del 4,5:1.

   La etiqueta «Solo administración» queda FUERA de esta regla: es un `<p>`, así
   que `.evt-solo-admin p` (0,2,0) le ganaba a `.evt-solo-admin-marca` (0,1,0) y
   la pintaba del marrón oscuro sobre su propio fondo marrón — medido en el
   navegador: 1,49:1, ilegible. Con el `:not()` no la toca y se queda con su
   blanco sobre `--evt-adm-marca`, que son 7,42:1. */
.evt-solo-admin label,
.evt-solo-admin small,
.evt-solo-admin p:not(.evt-solo-admin-marca),
.evt-solo-admin legend { color: var(--evt-adm-texto); }
.evt-solo-admin .evt-form-campo:last-child,
.evt-solo-admin .evt-code:last-child { margin-bottom: 14px; }

/* --- los campos de código ------------------------------------------------ */

/* El editor es el de WordPress (`wp_enqueue_code_editor()`, CodeMirror). Aquí
   solo se le da tamaño y se le pone el mismo marco que a los demás campos:
   su tema de color lo trae la hoja `code-editor` del núcleo. Sin él —quien
   desactiva el resaltado en su perfil— queda el `<textarea>`, y estas mismas
   reglas lo dejan legible. */
.evt-code { margin-bottom: 16px; }
.evt-code > label { display: block; margin-bottom: 4px; font-weight: 600; }

.evt-code-area {
  width: 100%;
  min-height: 12rem;
  padding: 10px 12px;
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  font-size: 13px;
  line-height: 1.55;
  tab-size: 2;
  color: var(--evt-texto);
  background: var(--evt-sup);
  border: 1px solid #c3cad2;
  border-radius: 8px;
  white-space: pre;
  overflow-wrap: normal;
  overflow-x: auto;
}

/* CodeMirror sustituye el textarea por su propio bloque: el marco y la altura
   se los pone aquí, que si no sale una tira de tres líneas sin borde. */
.evt-code .CodeMirror {
  height: 20rem;
  border: 1px solid #c3cad2;
  border-radius: 8px;
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  font-size: 13px;
  line-height: 1.55;
}
.evt-code .CodeMirror-focused { outline: 2px solid var(--evt-pri); outline-offset: 2px; }
/* La barra de errores de CSSLint y JSHint, debajo del editor. */
.evt-code .CodeMirror-lint-markers { width: 16px; }
.evt-code-plano { display: block; margin-top: 6px; font-size: 12.5px; font-style: italic; }

/* --- el aviso de que lo está editando otra persona ------------------------ */

/* El aviso del bloqueo (`EditLock::render()`) sale de dos maneras y tiene que
   verse igual en las dos: el servidor lo pinta como `<dialog open>` —sin
   JavaScript, que es justo el motivo de haber elegido un `<dialog>` y no
   SweetAlert2— y el Heartbeat lo abre con `showModal()` cuando el bloqueo se
   pierde sin recargar. Sin estilo, un `<dialog open>` es un bloque
   `position: absolute` sin capa ni sombreado, y la pantalla se le pinta encima:
   el aviso está en el HTML y no se lee. */
.evt-dialogo[open] {
  position: fixed;
  inset: 0;
  z-index: 100;
  width: min(560px, calc(100vw - 32px));
  max-height: calc(100vh - 32px);
  margin: auto;
  padding: 24px 26px;
  overflow: auto;
  border: 1px solid var(--evt-linea);
  border-radius: var(--evt-r);
  background: var(--evt-sup);
  color: var(--evt-texto);
  box-shadow: 0 18px 50px rgba(0, 0, 0, .28);
}

/* El sombreado del fondo lo pone el navegador en `::backdrop`, pero solo
   cuando el diálogo se abrió con `showModal()`. El que llega abierto desde el
   servidor no lo tiene, así que se le pinta a mano; `:not(:modal)` es
   exactamente ese caso y evita oscurecer dos veces. */
.evt-dialogo[open]:not(:modal) {
  box-shadow: 0 18px 50px rgba(0, 0, 0, .28), 0 0 0 100vmax rgba(15, 23, 42, .55);
}
.evt-dialogo::backdrop { background: rgba(15, 23, 42, .55); }
.evt-dialogo h2 { margin: 0 0 14px; font-size: 20px; line-height: 1.3; }
.evt-dialogo p { margin: 0 0 12px; font-size: 14.5px; line-height: 1.55; }
.evt-dialogo .evt-accion { display: flex; flex-wrap: wrap; gap: 10px; margin: 18px 0 0; }

/* --- móvil --------------------------------------------------------------- */

/* A 400 px de ancho tiene que seguir siendo usable: es la pantalla desde la
   que se corrige una sección a mitad de una jornada. */
@media (max-width: 640px) {
  .evt-top-fila { padding: 11px 16px; gap: 10px; flex-wrap: nowrap; }
  .evt-marca { display: none; }
  .evt-marca-app { margin-left: 0; padding-left: 0; border-left: 0; font-size: 16px; }
  .evt-yo-txt { display: none; }
  .evt-yo > summary { padding: 0; }
  .evt-yo-ava { width: 40px; height: 40px; }
  .evt-logo { width: 78px; height: 45px; }
  .evt-tabs-fila { padding: 0 16px; }
  body.evt-app .evt-hoja { padding: 18px 16px 40px; }
  .evt-h1 { font-size: 23px; }
  .evt-h1-fila .evt-acciones { margin-left: 0; width: 100%; }
  .evt-tarjeta { padding: 16px; }
  .evt-preview-cabecera { padding: 24px 16px; }
  .evt-preview-titulo { font-size: 23px; }
  .evt-solo-admin { padding: 14px 14px 2px; }
  .evt-code .CodeMirror { height: 15rem; }
  /* En el móvil el pie no cabe: la pantalla es para el trabajo, y lo legal
     está a un toque en cualquier otra página del sitio. */
  .evt-pie { display: none; }
}

/* Etiqueta solo para lectores de pantalla. La trae WordPress y casi todos los
   temas, pero el aplicativo no puede darla por hecha: sin ella los rótulos de
   las casillas salen escritos en pantalla. */
.evt-app .screen-reader-text {
  position: absolute;
  width: 1px;
  height: 1px;
  margin: -1px;
  padding: 0;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

/* Quien pide menos movimiento, menos movimiento. */
@media (prefers-reduced-motion: reduce) {
  .evt-app * { transition-duration: .01ms !important; animation-duration: .01ms !important; }
}
',
  'css/evt-evento.css' => '/*
 * evt-evento.css — la hoja de la página pública de un evento.
 *
 * Mobile-first y sin una sola media query que decida una rejilla: las rejillas
 * son `repeat(auto-fit, minmax(min(Npx,100%),1fr))`, que mide el CONTENEDOR y
 * no la ventana. Ya se corrigió una vez —las `col-*` de Bootstrap creían que
 * cabían cuatro columnas dentro de un contenedor de 645 px— y no se vuelve
 * atrás.
 *
 * TODO el aspecto sale de las propiedades personalizadas de abajo. El CSS a
 * medida de un evento cambia UN token y no pelea con la especificidad de nadie:
 *
 *     :root { --evt-fondo: #7b1e3a; --evt-radio: 0; }
 *
 * Los valores que el evento elige en el panel de apariencia los escribe
 * `EventLayout::tokens()` en un `<style>` propio, después de esta hoja y antes
 * del CSS a medida. Aquí solo están los valores por defecto.
 */

:root {
	/* Los colores de la cabecera, que es lo que cada evento elige. */
	--evt-fondo: #0a3d62;
	--evt-texto: #ffffff;

	/* Las dos tipografías. Vacías = las del navegador. */
	--evt-tipo-titulo: system-ui, -apple-system, "Segoe UI", Roboto, "Open Sans", Arial, sans-serif;
	--evt-tipo-texto: system-ui, -apple-system, "Segoe UI", Roboto, "Open Sans", Arial, sans-serif;

	/* La página. */
	--evt-papel: #ffffff;
	--evt-tinta: #1d2b36;
	--evt-suave: #f5f9fb;
	--evt-borde: rgba(0, 0, 0, 0.12);
	--evt-enlace: #1155aa;
	--evt-foco: #b8860b;
	--evt-sombra: 0 2px 12px rgba(0, 0, 0, 0.14);

	/* Medidas. El gutter se define AQUÍ y en ningún otro sitio. */
	--evt-ancho: 1080px;
	--evt-espacio: clamp(1rem, 4vw, 2rem);
	--evt-radio: 0.4rem;

	/* Forma de las fotos de personas: 0 cuadrada, 50% redonda. */
	--evt-forma: 0;

	/* Tipografía fluida: un solo sitio donde se decide cada escalón. */
	--evt-t-titulo: clamp(1.9rem, 1.2rem + 3.2vw, 2.9rem);
	--evt-t-lema: clamp(1.05rem, 0.95rem + 0.6vw, 1.35rem);
	--evt-t-h2: clamp(1.4rem, 1.2rem + 1vw, 1.9rem);
	--evt-t-texto: clamp(1rem, 0.97rem + 0.15vw, 1.08rem);
	--evt-t-menudo: 0.9rem;
}

/* ─── el documento ────────────────────────────────────────────────────── */

.evt-ev {
	margin: 0;
	background: var(--evt-papel);
	color: var(--evt-tinta);
	font-family: var(--evt-tipo-texto);
	font-size: var(--evt-t-texto);
	line-height: 1.65;
	/* A 320 px no se desborda nada: ni una palabra larga ni una URL pegada. */
	overflow-wrap: break-word;
}

.evt-ev * {
	box-sizing: border-box;
}

.evt-ev img,
.evt-ev svg,
.evt-ev video,
.evt-ev iframe {
	max-width: 100%;
	height: auto;
}

.evt-ev h1,
.evt-ev h2,
.evt-ev h3,
.evt-ev h4 {
	font-family: var(--evt-tipo-titulo);
	line-height: 1.25;
}

.evt-ev a {
	color: var(--evt-enlace);
}

/* Foco visible, siempre y sobre cualquier fondo: dos anillos, uno claro y
   otro oscuro, para que se vea igual sobre la cabecera de color y sobre el
   papel blanco. */
.evt-ev :focus-visible {
	outline: 3px solid var(--evt-foco);
	outline-offset: 2px;
	border-radius: 2px;
}

/* El único sitio donde se define el gutter lateral. */
.evt-ev__ancho {
	width: 100%;
	max-width: var(--evt-ancho);
	margin-inline: auto;
	padding-inline: var(--evt-espacio);
}

/* Saltar al contenido: fuera de la vista hasta que recibe el foco. */
.evt-ev__saltar {
	position: absolute;
	left: -9999px;
	top: 0;
	z-index: 100;
	padding: 0.6rem 1rem;
	background: var(--evt-papel);
	color: var(--evt-tinta);
	font-weight: 700;
	text-decoration: none;
}

.evt-ev__saltar:focus {
	left: 0;
}

/* Lo que solo lee quien usa un lector de pantalla. La define WordPress y la
   define Bootstrap con otro nombre; aquí también, para que la hoja aguante
   sola si no llega ninguna de las dos. */
.evt-ev .screen-reader-text {
	position: absolute;
	width: 1px;
	height: 1px;
	margin: -1px;
	padding: 0;
	border: 0;
	overflow: hidden;
	clip-path: inset(50%);
	white-space: nowrap;
}

/* ─── navegación entre secciones ──────────────────────────────────────── */

.evt-ev__nav {
	background: var(--evt-papel);
	border-bottom: 1px solid var(--evt-borde);
}

.evt-ev__nav ul {
	display: flex;
	flex-wrap: wrap;
	gap: 0.25rem;
	margin: 0;
	padding-block: 0.5rem;
	list-style: none;
}

.evt-ev__nav a {
	display: block;
	padding: 0.5rem 0.9rem;
	border-radius: var(--evt-radio);
	color: var(--evt-tinta);
	font-weight: 600;
	text-decoration: none;
}

.evt-ev__nav a:hover {
	background: var(--evt-suave);
}

.evt-ev__nav [aria-current="page"] {
	background: var(--evt-fondo);
	color: var(--evt-texto);
}

/* ─── portada de la cabecera ──────────────────────────────────────────── */

.evt-ev__portada {
	position: relative;
	background: var(--evt-fondo);
	color: var(--evt-texto);
	padding-block: calc(var(--evt-espacio) * 1.6) calc(var(--evt-espacio) * 2.4);
}

.evt-ev__portada a {
	color: inherit;
}

.evt-ev__portada--banner {
	padding: 0;
	background: transparent;
}

.evt-ev__banner {
	display: block;
	width: 100%;
	height: auto;
}

.evt-ev__logo {
	max-height: 90px;
	width: auto;
	margin-bottom: 1rem;
}

.evt-ev__madre {
	margin: 0 0 0.4rem;
	font-size: var(--evt-t-menudo);
	opacity: 0.9;
}

.evt-ev__titulo {
	margin: 0;
	font-size: var(--evt-t-titulo);
	font-weight: 500;
}

.evt-ev__lema {
	margin: 0.6rem 0 0;
	font-family: var(--evt-tipo-titulo);
	font-size: var(--evt-t-lema);
	font-weight: 400;
}

.evt-ev__linea {
	width: 90px;
	height: 2px;
	margin: 1.2rem 0;
	background: currentColor;
	opacity: 0.7;
}

.evt-ev__datos {
	display: flex;
	flex-wrap: wrap;
	gap: 0.25rem 0.9rem;
	margin: 0;
}

.evt-ev__acciones {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	gap: 0.6rem;
	margin: 1.1rem 0 0;
}

.evt-ev__estado {
	padding: 0.15rem 0.7rem;
	border: 1px solid currentColor;
	border-radius: 999px;
	font-size: var(--evt-t-menudo);
}

.evt-ev__hashtag {
	font-size: var(--evt-t-menudo);
	opacity: 0.9;
}

/* Los dos van con `.evt-ev__acciones` delante para poder con
   `.evt-ev__portada a{color:inherit}`, que es más específica que una clase
   sola y dejaba el botón de inscripción en blanco sobre blanco. */
.evt-ev__acciones .evt-ev__boton,
.evt-ev__acciones .evt-ev__gestion {
	display: inline-block;
	padding: 0.45rem 1rem;
	border: 1px solid currentColor;
	border-radius: var(--evt-radio);
	font-size: var(--evt-t-menudo);
	font-weight: 700;
	text-decoration: none;
}

.evt-ev__acciones .evt-ev__boton {
	background: var(--evt-texto);
	color: var(--evt-fondo);
}

/* La silueta del pie de la cabecera, pegada al borde de abajo. */
.evt-ev__separador {
	position: absolute;
	left: 0;
	bottom: -1px;
	display: block;
	width: 100%;
	height: 60px;
}

/* ─── el cuerpo: los bloques ──────────────────────────────────────────────
 *
 * Cada bloque con nombre —`contenido`, `cartel`, `secciones` y los que
 * registren las pantallas que vengan— sale envuelto en
 * `<section class="evt-ev__bloque evt-ev__bloque--NOMBRE">`. Lo que viste a
 * cada uno se escribe aquí y SOLO con los tokens de arriba: ni un color, ni un
 * espacio ni un radio a fuego, para que el CSS a medida de un evento cambie un
 * token y no tenga que pelearse con la especificidad de nadie.
 */

.evt-ev__main {
	display: block;
	padding-block: calc(var(--evt-espacio) * 1.4);
}

.evt-ev__bloque + .evt-ev__bloque {
	margin-top: calc(var(--evt-espacio) * 1.4);
}

.evt-ev__bloque > h2 {
	font-size: var(--evt-t-h2);
	margin-top: 0;
}

/* Toda rejilla de la página, con o sin Bootstrap: mide el contenedor. */
.evt-ev__rejilla {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(min(230px, 100%), 1fr));
	gap: var(--evt-espacio);
}

.evt-ev__tarjeta {
	display: flex;
	flex-direction: column;
	height: 100%;
	overflow: hidden;
	background: var(--evt-suave);
	border-radius: var(--evt-radio);
}

.evt-ev__tarjeta img {
	display: block;
	width: 100%;
}

.evt-ev__tarjeta h3 {
	margin: 0;
	padding: 0.9rem 1rem 0;
	font-size: 1.25rem;
	font-weight: 700;
	text-align: center;
}

.evt-ev__tarjeta h3 a {
	color: inherit;
	text-decoration: none;
}

.evt-ev__tarjeta p {
	margin: 0;
	padding: 0.6rem 1rem 1.2rem;
}

/* Las fotos de personas toman la forma que elija el evento. */
.evt-ev__retrato {
	border-radius: var(--evt-forma);
	aspect-ratio: 1;
	object-fit: cover;
}

.evt-ev__cartel {
	margin: 0 auto;
	max-width: min(520px, 100%);
	text-align: center;
}

.evt-ev__cartel img {
	border-radius: var(--evt-radio);
	box-shadow: var(--evt-sombra);
}

.evt-ev__cartel figcaption {
	margin-top: 0.5rem;
	font-size: var(--evt-t-menudo);
	opacity: 0.8;
}

/* Una tabla nunca empuja la página: se desplaza ella sola. */
.evt-ev__bloque table {
	display: block;
	max-width: 100%;
	overflow-x: auto;
	border-collapse: collapse;
}

/* ─── el pie institucional ────────────────────────────────────────────── */

.evt-ev__pie {
	background: var(--evt-suave);
	border-top: 1px solid var(--evt-borde);
	padding-block: var(--evt-espacio);
	font-size: var(--evt-t-menudo);
}

.evt-ev__pie > div {
	display: flex;
	flex-wrap: wrap;
	justify-content: space-between;
	gap: 0.5rem 1.5rem;
}

.evt-ev__pie a {
	color: var(--evt-tinta);
	text-decoration: none;
}

.evt-ev__pie a:hover,
.evt-ev__pie a:focus-visible {
	text-decoration: underline;
}

.evt-ev__pie-enlaces {
	display: flex;
	flex-wrap: wrap;
	gap: 0.5rem 1.2rem;
}

/* ─── impresión ───────────────────────────────────────────────────────── */

@media print {
	.evt-ev__nav,
	.evt-ev__saltar,
	.evt-ev__acciones {
		display: none;
	}

	.evt-ev__portada {
		color: #000;
		background: none;
	}
}
',
  'js/evt-app.js' => '/*
 * Lo mínimo que las pantallas del aplicativo no pueden hacer sin guion.
 *
 * Cuatro cosas, y las cuatro son mejora sobre algo que ya funciona sin ellas:
 * si el guion no llega, el formulario se envía igual, el slug se escribe a
 * mano, la apariencia se ve al guardar y las imágenes se suben con el campo de
 * fichero del `<noscript>`. Nada aquí valida ni autoriza: eso está en el
 * servidor, con su nonce y su comprobación de EventAccess.
 *
 * Delegado en `document`: las pantallas repintan trozos y un `addEventListener`
 * por nodo se quedaría atrás.
 */
( function () {
	\'use strict\';

	/* --- 1. Confirmar antes de borrar ------------------------------------ */

	/*
	 * `data-evt-confirm="¿Seguro que…?"` en el formulario que borra. Se
	 * pregunta al enviar, que es el único momento en que se pierde algo.
	 *
	 * Tres escalones, y los tres hacen la acción:
	 *   1. Con SweetAlert2 (`snippets/sweetalert.php`), un diálogo en
	 *      castellano cuyo botón dice el verbo —«Enviar a la papelera»— y no
	 *      «OK». Atrapa el foco y se cierra con Escape; lo hace la librería.
	 *   2. Sin ella —el CDN no contesta, el SRI no cuadra—, el `confirm()` del
	 *      navegador de siempre.
	 *   3. Sin guion, el botón envía el formulario y la acción se hace.
	 * Nunca se pierde una acción porque una librería no llegara.
	 *
	 * El verbo sale de `data-evt-confirm-ok` si el formulario lo pone y, si no,
	 * del `title` del botón, que ya es la acción escrita («Enviar a la
	 * papelera») porque es lo que lee quien navega con lector de pantalla.
	 */
	function verbo( form, boton ) {
		return form.getAttribute( \'data-evt-confirm-ok\' ) ||
			( boton && boton.getAttribute( \'title\' ) ) ||
			\'Sí, continuar\';
	}

	document.addEventListener( \'submit\', function ( e ) {
		var form = e.target;
		// La marca la pone el envío que sale del diálogo: se deja pasar.
		if ( ! ( form instanceof HTMLFormElement ) || \'1\' === form.dataset.evtConfirmado ) {
			return;
		}
		var pregunta = form.getAttribute( \'data-evt-confirm\' );
		if ( ! pregunta ) {
			return;
		}

		if ( ! window.Swal ) {
			if ( ! window.confirm( pregunta ) ) {
				e.preventDefault();
			}
			return;
		}

		// SweetAlert2 contesta con una promesa, así que el envío se para y se
		// repite luego; el `confirm()` de arriba, no, y por eso va aparte.
		e.preventDefault();
		var boton = e.submitter || form.querySelector( \'[type="submit"]\' );
		// «¿Enviar «X» a la papelera? Dejará de verse.» → titular y detalle.
		var corte = pregunta.indexOf( \'? \' );
		window.Swal.fire( {
			title: -1 === corte ? pregunta : pregunta.slice( 0, corte + 1 ),
			text: -1 === corte ? \'\' : pregunta.slice( corte + 2 ),
			icon: \'warning\',
			showCancelButton: true,
			confirmButtonText: verbo( form, boton ),
			cancelButtonText: \'Cancelar\',
			// Lo que no tiene vuelta atrás no se confirma sin querer.
			focusCancel: true,
			reverseButtons: true,
			// Sin tocar el alto del `html`: la barra de pestañas es pegajosa.
			heightAuto: false,
			// Los botones son los de la página, no los de la librería.
			buttonsStyling: false,
			customClass: {
				confirmButton: \'evt-btn evt-btn-borrar btn\',
				cancelButton: \'evt-btn btn btn-light\'
			}
		} ).then( function ( respuesta ) {
			if ( ! respuesta.isConfirmed ) {
				return;
			}
			form.dataset.evtConfirmado = \'1\';
			if ( form.requestSubmit ) {
				// Con el mismo botón: si algún día lleva `name`, sigue viajando.
				form.requestSubmit( boton || undefined );
			} else {
				form.submit();
			}
		} );
	} );

	/* --- 2. Proponer el slug desde el título ----------------------------- */

	/*
	 * `data-evt-slug-source` en el campo del título y `data-evt-slug-target`
	 * en el del slug, dentro del mismo formulario. Se propone y no se impone:
	 * en cuanto alguien escribe en el slug, se deja de tocar, y una sección
	 * que ya tiene slug —la que se está editando— no se reescribe nunca.
	 */
	function slugify( texto ) {
		return texto
			.normalize( \'NFD\' )
			.replace( /[̀-ͯ]/g, \'\' )
			.toLowerCase()
			.replace( /[^a-z0-9]+/g, \'-\' )
			.replace( /^-+|-+$/g, \'\' )
			.slice( 0, 80 );
	}

	document.addEventListener( \'input\', function ( e ) {
		var origen = e.target;
		if ( ! origen || ! origen.hasAttribute || ! origen.hasAttribute( \'data-evt-slug-source\' ) ) {
			return;
		}
		var form = origen.form;
		var destino = form && form.querySelector( \'[data-evt-slug-target]\' );
		if ( ! destino || destino.dataset.evtTocado === \'1\' ) {
			return;
		}
		destino.value = slugify( origen.value );
	} );

	document.addEventListener( \'input\', function ( e ) {
		if ( e.target && e.target.hasAttribute && e.target.hasAttribute( \'data-evt-slug-target\' ) ) {
			e.target.dataset.evtTocado = \'1\';
		}
	} );

	/* --- 3. La vista previa de la cabecera ------------------------------- */

	/*
	 * El panel de apariencia lleva un `[data-evt-preview]` dentro del mismo
	 * formulario que los controles. No hace falta marcar cada control: se
	 * leen por su `name`, que es la clave de meta (`evt_header_bg`…).
	 *
	 * Dentro de la vista previa:
	 *   [data-evt-preview-header]  el bloque que toma los dos colores
	 *   [data-evt-preview-title]   el título, que toma la tipografía de títulos
	 *   [data-evt-preview-body]    el cuerpo, que toma la del texto
	 *   [data-evt-preview-tagline] el lema
	 *   [data-evt-preview-shape]   la muestra que toma la forma de imagen
	 *   [data-evt-preview-sep]     con un hijo `[data-sep="<slug>"]` por silueta
	 */
	var CAMPOS = [
		\'evt_header_bg\',
		\'evt_header_text\',
		\'evt_title_font\',
		\'evt_body_font\',
		\'evt_image_shape\',
		\'evt_separator\',
		\'evt_tagline\'
	];

	function valor( form, nombre ) {
		var campo = form.elements[ nombre ];
		return campo && typeof campo.value === \'string\' ? campo.value : \'\';
	}

	function familia( nodo, slug ) {
		if ( ! nodo ) {
			return;
		}
		nodo.className = nodo.className.replace( /\\bevt-font-\\S+/g, \'\' ).trim();
		if ( slug ) {
			nodo.classList.add( \'evt-font-\' + slug );
		}
	}

	function pintar( form ) {
		var vista = form.querySelector( \'[data-evt-preview]\' );
		if ( ! vista ) {
			return;
		}

		var cabecera = vista.querySelector( \'[data-evt-preview-header]\' );
		if ( cabecera ) {
			cabecera.style.backgroundColor = valor( form, \'evt_header_bg\' );
			cabecera.style.color = valor( form, \'evt_header_text\' );
		}

		familia( vista.querySelector( \'[data-evt-preview-title]\' ), valor( form, \'evt_title_font\' ) );
		familia( vista.querySelector( \'[data-evt-preview-body]\' ), valor( form, \'evt_body_font\' ) );

		var lema = vista.querySelector( \'[data-evt-preview-tagline]\' );
		if ( lema && form.elements.evt_tagline ) {
			lema.textContent = valor( form, \'evt_tagline\' );
		}

		var muestra = vista.querySelector( \'[data-evt-preview-shape]\' );
		if ( muestra ) {
			muestra.classList.remove( \'evt-shape-square\', \'evt-shape-circle\' );
			muestra.classList.add( \'evt-shape-\' + ( \'circle\' === valor( form, \'evt_image_shape\' ) ? \'circle\' : \'square\' ) );
		}

		var sep = vista.querySelector( \'[data-evt-preview-sep]\' );
		if ( sep ) {
			var elegido = valor( form, \'evt_separator\' );
			Array.prototype.forEach.call( sep.querySelectorAll( \'[data-sep]\' ), function ( silueta ) {
				silueta.hidden = silueta.getAttribute( \'data-sep\' ) !== elegido;
			} );
		}
	}

	/*
	 * El `<input type="color">` y su campo hexadecimal escriben el mismo dato:
	 * `data-evt-color-for="<id del campo de texto>"` los empareja. El que se
	 * envía es el de texto, así que quien tenga el selector de color bloqueado
	 * sigue pudiendo teclear el hexadecimal.
	 */
	document.addEventListener( \'input\', function ( e ) {
		var control = e.target;
		if ( ! control || ! control.hasAttribute ) {
			return;
		}

		var pareja = control.getAttribute( \'data-evt-color-for\' );
		if ( pareja ) {
			var texto = document.getElementById( pareja );
			if ( texto ) {
				texto.value = control.value;
			}
		} else if ( \'evt_header_bg\' === control.name || \'evt_header_text\' === control.name ) {
			// Al revés: el hexadecimal escrito a mano mueve la muestra de color.
			var muestra = document.querySelector( \'[data-evt-color-for="\' + control.id + \'"]\' );
			if ( muestra && /^#[0-9a-fA-F]{6}$/.test( control.value ) ) {
				muestra.value = control.value;
			}
		}

		if ( control.form && ( pareja || CAMPOS.indexOf( control.name ) !== -1 ) ) {
			pintar( control.form );
		}
	} );

	// Y una primera pasada, para que la vista previa arranque con lo guardado.
	document.addEventListener( \'DOMContentLoaded\', function () {
		Array.prototype.forEach.call( document.querySelectorAll( \'[data-evt-preview]\' ), function ( vista ) {
			if ( vista.form || vista.closest( \'form\' ) ) {
				pintar( vista.closest( \'form\' ) );
			}
		} );
	} );

	/* --- 4. Elegir un archivo de la biblioteca ---------------------------- */

	/*
	 * Cada archivo gestionado con la biblioteca es un `[data-evt-media]`. La
	 * ventana nativa de WordPress aporta biblioteca, subida por arrastre y la
	 * previsualización del adjunto; la ficha conserva una vista rápida fuera.
	 *
	 *   [data-evt-media-value]   el `hidden` con el ID del adjunto: lo único que viaja
	 *   [data-evt-media-card]    la ficha de lo que hay puesto ahora
	 *   [data-evt-media-thumb]   su miniatura
	 *   [data-evt-media-name]    su nombre de fichero
	 *   [data-evt-media-size]    sus dimensiones
	 *   [data-evt-media-empty]   la línea de «todavía no hay nada»
	 *   [data-evt-media-actions] la fila de botones, oculta hasta que este guion llega
	 *   [data-evt-media-pick]    «Elegir imagen»
	 *   [data-evt-media-clear]   «Quitar»
	 *
	 * Sin guion no se ve ningún botón —uno que no abre nada solo estorba— y el
	 * respaldo del `<noscript>` sigue siendo la forma de subir o de quitar. Y
	 * el ID que se escribe aquí no autoriza nada: el servidor comprueba que
	 * sea un adjunto de imagen y que quien lo manda edite el evento.
	 */
	function poner( caja, adjunto ) {
		var oculto = caja.querySelector( \'[data-evt-media-value]\' );
		var ficha = caja.querySelector( \'[data-evt-media-card]\' );
		var vacia = caja.querySelector( \'[data-evt-media-empty]\' );
		var quitar = caja.querySelector( \'[data-evt-media-clear]\' );
		var mini = caja.querySelector( \'[data-evt-media-thumb]\' );
		var nombre = caja.querySelector( \'[data-evt-media-name]\' );
		var medidas = caja.querySelector( \'[data-evt-media-size]\' );
		var hay = !! ( adjunto && adjunto.id );

		if ( oculto ) {
			oculto.value = hay ? String( adjunto.id ) : \'0\';
		}
		if ( ficha ) {
			ficha.hidden = ! hay;
		}
		if ( vacia ) {
			vacia.hidden = hay;
		}
		if ( quitar ) {
			quitar.hidden = ! hay;
		}
		if ( ! hay ) {
			return;
		}

		// La miniatura de la biblioteca si la hay, y si no el fichero entero.
		var chica = adjunto.sizes && ( adjunto.sizes.medium || adjunto.sizes.thumbnail );
		if ( mini ) {
			mini.src = chica ? chica.url : ( adjunto.icon || adjunto.url );
			mini.alt = adjunto.alt || \'\';
		}
		if ( nombre ) {
			nombre.textContent = adjunto.filename || adjunto.title || \'\';
		}
		if ( medidas ) {
			medidas.textContent = adjunto.width && adjunto.height
				? adjunto.width + \' × \' + adjunto.height + \' px\'
				: \'\';
		}
	}

	function elegir( caja ) {
		if ( ! window.wp || ! window.wp.media ) {
			return;
		}
		var tipo = caja.getAttribute( \'data-evt-media-type\' );
		var opciones = {
			title: caja.getAttribute( \'data-evt-media-title\' ) || \'Elegir archivo\',
			button: { text: caja.getAttribute( \'data-evt-media-button\' ) || \'Usar este archivo\' },
			multiple: false
		};
		if ( tipo ) {
			opciones.library = { type: tipo };
		}
		var marco = window.wp.media( opciones );
		marco.on( \'select\', function () {
			var elegido = marco.state().get( \'selection\' ).first();
			if ( elegido ) {
				poner( caja, elegido.toJSON() );
			}
		} );
		marco.open();
	}

	document.addEventListener( \'click\', function ( e ) {
		var boton = e.target && e.target.closest
			? e.target.closest( \'[data-evt-media-pick], [data-evt-media-clear]\' )
			: null;
		var caja = boton && boton.closest( \'[data-evt-media]\' );
		if ( ! caja ) {
			return;
		}
		e.preventDefault();
		if ( boton.hasAttribute( \'data-evt-media-clear\' ) ) {
			poner( caja, null );
		} else {
			elegir( caja );
		}
	} );

	function mostrarBotones() {
		Array.prototype.forEach.call( document.querySelectorAll( \'[data-evt-media-actions]\' ), function ( fila ) {
			fila.hidden = false;
		} );
	}

	// El guion va en el pie, así que el panel ya está leído; el segundo aviso
	// es por si algún día sube a la cabecera.
	mostrarBotones();
	document.addEventListener( \'DOMContentLoaded\', mostrarBotones );

	/* El mismo cargador que usa «Medios» convierte cada campo en una zona de
	 * soltado. La subida crea el adjunto en WordPress inmediatamente; elegirlo
	 * para el evento sigue esperando a «Guardar la apariencia» o el ponente. */
	function arrancarSubidas() {
		if ( ! window.wp || ! window.wp.Uploader || ! window.jQuery ) {
			return;
		}
		Array.prototype.forEach.call( document.querySelectorAll( \'[data-evt-media]\' ), function ( caja ) {
			if ( \'1\' === caja.dataset.evtUploader ) {
				return;
			}
			caja.dataset.evtUploader = \'1\';
			var estado = caja.querySelector( \'[data-evt-media-status]\' );
			var tipo = caja.getAttribute( \'data-evt-media-type\' );
			var minimo = parseInt( caja.getAttribute( \'data-evt-media-min-width\' ) || \'0\', 10 );
			var opciones = {
				container: caja,
				dropzone: caja,
				plupload: { multi_selection: false },
				added: function ( adjunto ) {
					if ( estado ) {
						estado.textContent = \'Subiendo \' + ( adjunto.get( \'filename\' ) || \'el archivo\' ) + \'…\';
					}
				},
				progress: function ( adjunto ) {
					if ( estado ) {
						estado.textContent = \'Subiendo… \' + ( adjunto.get( \'percent\' ) || 0 ) + \'%\';
					}
				},
				success: function ( adjunto ) {
					var archivo = adjunto.toJSON();
					if ( minimo > 0 && ( ! archivo.width || archivo.width < minimo ) ) {
						if ( estado ) {
							estado.textContent = \'La imagen se subió a la biblioteca, pero no se puede usar aquí: necesita al menos \' + minimo + \' px de ancho.\';
						}
						return;
					}
					poner( caja, archivo );
					if ( estado ) {
						estado.textContent = \'Archivo subido. Guarde el formulario para aplicar el cambio.\';
					}
				},
				error: function ( mensaje ) {
					if ( estado ) {
						estado.textContent = mensaje || \'No se pudo subir el archivo.\';
					}
				}
			};
			if ( \'image\' === tipo ) {
				opciones.plupload.filters = {
					mime_types: [ { title: \'Imágenes\', extensions: \'jpg,jpeg,png,gif,webp\' } ]
				};
			}
			new window.wp.Uploader( opciones );
		} );
	}

	arrancarSubidas();
	document.addEventListener( \'DOMContentLoaded\', arrancarSubidas );
	window.addEventListener( \'load\', arrancarSubidas );

	/* --- 5. El editor de código ------------------------------------------ */

	/*
	 * `CodeEditor::field()` pinta cada `<textarea data-evt-code="css|javascript">`
	 * con sus ajustes en `data-evt-code-settings`. Un atributo por campo y no
	 * `wp_localize_script`: en la misma pantalla hay dos editores —CSS y
	 * JavaScript—, con ajustes distintos, y `wp_localize_script` escribe UNA
	 * variable por identificador de guion, así que la segunda llamada pisa a
	 * la primera. Con el atributo, cada campo se describe a sí mismo y aquí no
	 * hay que emparejar nada.
	 *
	 * Si `wp.codeEditor` no está —porque quien mira desactivó el resaltado en
	 * su perfil, y entonces el atributo tampoco está— el textarea se queda como
	 * está y se sigue escribiendo y guardando igual.
	 *
	 * Se intenta varias veces (al leer el documento y al terminar de cargar)
	 * porque `code-editor` se encola al pintar el contenido, después que este
	 * guion, y en el pie se imprime detrás. La marca `data-evt-code-on` hace
	 * que las pasadas de más no cuesten nada.
	 *
	 * La pasada inmediata solo corre si el documento ya está leído: con él
	 * todavía cargando, `wp.codeEditor.initialize()` avisa por consola de que
	 * «ran too early», y no aporta nada porque `DOMContentLoaded` llega
	 * enseguida. Si el guion se carga tarde (`defer`, o inyectado), esa pasada
	 * inmediata sigue siendo la que arranca los editores.
	 */
	function arrancarEditores() {
		if ( ! window.wp || ! window.wp.codeEditor ) {
			return;
		}
		Array.prototype.forEach.call( document.querySelectorAll( \'[data-evt-code]\' ), function ( area ) {
			if ( \'1\' === area.dataset.evtCodeOn ) {
				return;
			}
			var ajustes;
			try {
				ajustes = JSON.parse( area.getAttribute( \'data-evt-code-settings\' ) || \'{}\' );
			} catch ( e ) {
				return;
			}
			area.dataset.evtCodeOn = \'1\';
			// `CodeMirror.fromTextArea` engancha el `submit` del formulario y
			// devuelve lo escrito al textarea, así que lo que viaja en el POST
			// es siempre lo que se ve.
			window.wp.codeEditor.initialize( area, ajustes );
		} );
	}

	if ( \'loading\' !== document.readyState ) {
		arrancarEditores();
	}
	document.addEventListener( \'DOMContentLoaded\', arrancarEditores );
	window.addEventListener( \'load\', arrancarEditores );

	/* --- 6. El bloqueo de edición, con el Heartbeat de WordPress --------- */

	/*
	 * `EditLock::render()` pinta un `#evt-edit-lock` con el evento, el bloqueo
	 * que se tiene ahora (`data-lock`) y el nonce para soltarlo. Todo lo de
	 * aquí es el mecanismo NATIVO del escritorio, sin inventar nada:
	 *
	 *   - `wp-refresh-post-lock` viaja en cada latido del Heartbeat: renueva el
	 *     bloqueo propio y, si otra persona tomó posesión, contesta con su
	 *     nombre. Es la misma llamada que hace el editor de WordPress, así que
	 *     los dos sitios se enteran el uno del otro.
	 *   - `wp-remove-post-lock` suelta el bloqueo al cerrar la pestaña.
	 *
	 * Sin guion no se pierde nada: el aviso ya viene pintado como
	 * `<dialog open>` desde el servidor cuando el evento está cogido, y el
	 * servidor vuelve a comprobarlo con un 409 antes de escribir. Esto solo
	 * evita el susto de estar media hora escribiendo algo que ya no se puede
	 * guardar.
	 *
	 * En `DOMContentLoaded` porque el guion del aplicativo se imprime en el pie
	 * sin depender de jQuery ni del Heartbeat, y a esas alturas los dos ya
	 * están cargados.
	 */
	document.addEventListener( \'DOMContentLoaded\', function () {
		var caja = document.getElementById( \'evt-edit-lock\' );
		// Sin `data-lock` el bloqueo lo tiene otra persona: no hay nada que
		// renovar, y pedirlo sería pedir la renovación de un bloqueo ajeno.
		if ( ! caja || ! caja.dataset.lock || ! window.jQuery || ! window.wp || ! window.wp.heartbeat ) {
			return;
		}

		var $ = window.jQuery;
		var dialogo = document.getElementById( \'evt-lock-dialog\' );
		var cerradura = caja.dataset.lock;
		var perdido = false;
		var enviando = false;

		// Todo lo de la pantalla menos el propio aviso, que es lo único que
		// tiene que seguir funcionando cuando el bloqueo se pierde.
		function fuera( nodo ) {
			return ! caja.contains( nodo );
		}

		$( document ).on( \'heartbeat-send.evtLock\', function ( e, data ) {
			if ( ! perdido ) {
				data[\'wp-refresh-post-lock\'] = {
					post_id: Number( caja.dataset.postId ),
					lock: cerradura
				};
			}
		} );

		$( document ).on( \'heartbeat-tick.evtLock\', function ( e, data ) {
			var respuesta = data[\'wp-refresh-post-lock\'];
			if ( ! respuesta || perdido ) {
				return;
			}
			if ( respuesta.lock_error ) {
				perdido = true;
				// Congelado, no borrado: lo escrito sigue en pantalla para
				// poder copiarlo antes de tomar posesión o de irse.
				Array.prototype.forEach.call( document.querySelectorAll( \'form\' ), function ( form ) {
					if ( fuera( form ) ) {
						form.inert = true;
					}
				} );
				document.getElementById( \'evt-lock-owner\' ).textContent = respuesta.lock_error.name;
				if ( dialogo.showModal ) {
					dialogo.showModal();
				} else {
					dialogo.setAttribute( \'open\', \'\' );
				}
			} else if ( respuesta.new_lock ) {
				cerradura = respuesta.new_lock;
			}
		} );

		// El aviso no se cierra con Escape: no es una confirmación, es que no
		// se puede editar, y cerrarlo devolvería una pantalla que miente.
		dialogo.addEventListener( \'cancel\', function ( e ) {
			e.preventDefault();
		} );

		// En captura, antes que la confirmación de SweetAlert2: si el bloqueo
		// ya se perdió, el envío no sale ni siquiera al servidor.
		document.addEventListener( \'submit\', function ( e ) {
			if ( perdido && fuera( e.target ) ) {
				e.preventDefault();
				e.stopImmediatePropagation();
			}
		}, true );

		// Como el editor clásico: al enviar NO se suelta el bloqueo, que a
		// partir de ahí es del servidor. Soltarlo aquí volvería a crear con la
		// baliza el bloqueo que el propio guardado acaba de borrar.
		window.addEventListener( \'submit\', function ( e ) {
			if ( fuera( e.target ) && ! e.defaultPrevented ) {
				enviando = true;
			}
		} );

		window.addEventListener( \'pagehide\', function () {
			if ( perdido || enviando || ! navigator.sendBeacon || ! caja.dataset.ajaxUrl ) {
				return;
			}
			var datos = new FormData();
			datos.append( \'action\', \'wp-remove-post-lock\' );
			datos.append( \'_wpnonce\', caja.dataset.releaseNonce );
			datos.append( \'post_ID\', caja.dataset.postId );
			datos.append( \'active_post_lock\', cerradura );
			navigator.sendBeacon( caja.dataset.ajaxUrl, datos );
		} );

		// Una página restaurada del historial trae campos viejos y un bloqueo
		// que ya puede ser de otra persona: se vuelve a preguntar al servidor.
		window.addEventListener( \'pageshow\', function ( e ) {
			if ( e.persisted ) {
				window.location.reload();
			}
		} );

		window.wp.heartbeat.interval( 15 );
	} );

	/* --- 5. Bocadillos en los botones de icono --------------------------- */

	/*
	 * Un botón de icono no dice qué hace. Lo dice su `title`, y el navegador ya
	 * lo enseña al posarse encima: **esto es mejora, no requisito**. Con
	 * Bootstrap cargado se cambia por su bocadillo, que sale antes y se lee
	 * mejor; sin Bootstrap —o sin guion— queda el `title` de siempre.
	 *
	 * El texto de verdad para quien navega con lector de pantalla no es el
	 * `title` sino el `.screen-reader-text` que va dentro del botón.
	 */
	function bocadillos( raiz ) {
		if ( ! window.bootstrap || ! window.bootstrap.Tooltip ) {
			return;
		}
		var nodos = ( raiz || document ).querySelectorAll( \'[data-bs-toggle="tooltip"]\' );
		Array.prototype.forEach.call( nodos, function ( nodo ) {
			if ( ! window.bootstrap.Tooltip.getInstance( nodo ) ) {
				new window.bootstrap.Tooltip( nodo );
			}
		} );
	}
	document.addEventListener( \'DOMContentLoaded\', function () {
		bocadillos( document );
	} );

	/* --- 6. El interruptor de publicación -------------------------------- */

	/*
	 * `data-evt-switch` en la casilla. Al cambiarla se envía su formulario, que
	 * es lo que se espera de un interruptor: se toca y pasa algo.
	 *
	 * El botón de al lado hace lo mismo y es el que queda sin guion; con guion
	 * se esconde, porque teniendo el interruptor sobra. Se esconde **desde
	 * aquí** y no en el CSS a propósito: si el guion no llega, el botón se ve.
	 */
	document.addEventListener( \'change\', function ( e ) {
		var casilla = e.target.closest ? e.target.closest( \'[data-evt-switch]\' ) : null;
		if ( ! casilla ) {
			return;
		}
		var form = casilla.form;
		if ( form ) {
			casilla.disabled = true;
			form.submit();
		}
	} );
	document.addEventListener( \'DOMContentLoaded\', function () {
		var botones = document.querySelectorAll( \'.evt-switch-boton\' );
		Array.prototype.forEach.call( botones, function ( boton ) {
			boton.hidden = true;
		} );
	} );
}() );
',
) );

if ( \class_exists( \Evt\App::class ) ) {
	\Evt\App::boot();
}

// phpcs:enable
