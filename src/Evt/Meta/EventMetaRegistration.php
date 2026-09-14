<?php
/**
 * Formal registration of evt_event post meta (types, sanitisation, auth).
 *
 * @package Evt
 */

namespace Evt\Meta;

use Evt\Access\EventAccess;
use Evt\PostType\EventPostType;

/**
 * Registers every event meta key with register_post_meta().
 *
 * Los sanitize callbacks corren en cada update_post_meta() (sanitize_meta),
 * así que da igual por dónde entre el dato —formulario, escritorio, WP-CLI—:
 * siempre se normaliza en el mismo sitio.
 */
final class EventMetaRegistration {

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'register_meta' ), 12 );
	}

	/**
	 * Every meta key with its storage type, its sanitiser and, when it needs
	 * one of its own, its authorisation callback.
	 *
	 * Sin `auth` la clave la escribe quien pueda editar el evento
	 * ({@see auth_edit_event()}); las dos de código piden además su capacidad.
	 *
	 * @return array<string, array{type:string, sanitize:callable, auth?:callable}>
	 */
	public static function schema(): array {
		return array(
			EventMetaKeys::SECTION_TYPE   => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_section_type' ),
			),
			EventMetaKeys::START_DATE     => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_date' ),
			),
			EventMetaKeys::END_DATE       => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_date' ),
			),
			EventMetaKeys::VENUE          => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field',
			),
			EventMetaKeys::TAGLINE        => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field',
			),
			EventMetaKeys::HASHTAG        => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_hashtag' ),
			),
			EventMetaKeys::INTRO          => array(
				'type'     => 'string',
				'sanitize' => 'wp_kses_post',
			),
			EventMetaKeys::SIGNUP_SHOW    => array(
				'type'     => 'boolean',
				'sanitize' => array( self::class, 'sanitize_bool' ),
			),
			EventMetaKeys::SIGNUP_LABEL   => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field',
			),
			EventMetaKeys::SIGNUP_URL     => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_url' ),
			),
			EventMetaKeys::SIGNUP_FORM_ID => array(
				'type'     => 'integer',
				'sanitize' => array( self::class, 'sanitize_id' ),
			),
			EventMetaKeys::HEADER_BG      => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_color' ),
			),
			EventMetaKeys::HEADER_TEXT    => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_color' ),
			),
			EventMetaKeys::TITLE_FONT     => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_font' ),
			),
			EventMetaKeys::BODY_FONT      => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_font' ),
			),
			EventMetaKeys::LOGO_ID        => array(
				'type'     => 'integer',
				'sanitize' => array( self::class, 'sanitize_id' ),
			),
			EventMetaKeys::POSTER_ID      => array(
				'type'     => 'integer',
				'sanitize' => array( self::class, 'sanitize_id' ),
			),
			EventMetaKeys::IMAGE_SHAPE    => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_image_shape' ),
			),
			EventMetaKeys::SEPARATOR      => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_separator' ),
			),
			EventMetaKeys::CUSTOM_CSS     => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_custom_css' ),
				'auth'     => array( self::class, 'auth_custom_css' ),
			),
			EventMetaKeys::CUSTOM_JS      => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_custom_js' ),
				'auth'     => array( self::class, 'auth_custom_js' ),
			),
			EventMetaKeys::ARCHIVED       => array(
				'type'     => 'boolean',
				'sanitize' => array( self::class, 'sanitize_bool' ),
				'auth'     => array( self::class, 'auth_archived' ),
			),
		);
	}

	/**
	 * Register all event meta keys.
	 *
	 * @return void
	 */
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

	/**
	 * Only users who may edit the event can write its meta directly.
	 *
	 * @param bool   $allowed  Current permission.
	 * @param string $meta_key Meta key.
	 * @param int    $post_id  Post ID.
	 * @param int    $user_id  User ID.
	 * @return bool
	 */
	public static function auth_edit_event( bool $allowed, string $meta_key, int $post_id, int $user_id ): bool {
		unset( $allowed, $meta_key );
		return user_can( $user_id, 'edit_post', $post_id );
	}

	/**
	 * The custom CSS needs its own capability, on top of editing the event.
	 *
	 * @param bool   $allowed  Current permission.
	 * @param string $meta_key Meta key.
	 * @param int    $post_id  Post ID.
	 * @param int    $user_id  User ID.
	 * @return bool
	 */
	public static function auth_custom_css( bool $allowed, string $meta_key, int $post_id, int $user_id ): bool {
		unset( $allowed, $meta_key );
		return EventAccess::can_edit_custom_css( $user_id, $post_id );
	}

	/**
	 * The custom JavaScript needs its own, stricter, capability.
	 *
	 * Aquí y no solo en la pantalla: sin esto, la REST, la edición rápida y
	 * cualquier `update_post_meta()` de otro complemento serían la puerta de al
	 * lado de un campo que ejecuta código en el navegador de cada visitante.
	 *
	 * @param bool   $allowed  Current permission.
	 * @param string $meta_key Meta key.
	 * @param int    $post_id  Post ID.
	 * @param int    $user_id  User ID.
	 * @return bool
	 */
	public static function auth_custom_js( bool $allowed, string $meta_key, int $post_id, int $user_id ): bool {
		unset( $allowed, $meta_key );
		return EventAccess::can_edit_custom_js( $user_id, $post_id );
	}

	/**
	 * The «histórico» mark follows the same asymmetry by every route.
	 *
	 * Aquí no se sabe qué valor se va a escribir, así que la pregunta se hace
	 * sobre el estado de ahora: con el evento abierto la marca la pone su
	 * área; con el evento ya cerrado, solo la quita administración. Sin esto,
	 * un área que ya no puede editar el evento se lo desmarcaría con un
	 * `update_post_meta()` desde cualquier otro sitio y volvería a poder.
	 *
	 * @param bool   $allowed  Current permission.
	 * @param string $meta_key Meta key.
	 * @param int    $post_id  Post ID.
	 * @param int    $user_id  User ID.
	 * @return bool
	 */
	public static function auth_archived( bool $allowed, string $meta_key, int $post_id, int $user_id ): bool {
		unset( $allowed, $meta_key );
		return EventAccess::can_toggle_archived( $user_id, $post_id );
	}

	/**
	 * Custom CSS, stored raw: only the tag escape is taken away.
	 *
	 * No pasa por `wp_kses` ni por `sanitize_textarea_field`: los dos
	 * convierten `>` en `&gt;` y dejan el CSS sin selectores de hijo directo.
	 * Lo único que no puede quedar es una etiqueta, porque el CSS se imprime
	 * dentro de un `<style>` y un `</style` ahí dentro lo cierra y convierte el
	 * resto en HTML del visitante.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_custom_css( $value ): string {
		// `wp_strip_all_tags()` se lleva las etiquetas y el contenido de un
		// `<script>` o un `<style>` incrustados; el `preg_replace` remata el
		// cierre suelto que se queda sin `>` al final de la cadena.
		$css = wp_strip_all_tags( (string) $value );
		return (string) preg_replace( '#</\s*style#i', '', $css );
	}

	/**
	 * Custom JavaScript, stored raw: only the script closing tag is defused.
	 *
	 * El JavaScript **no se escapa**: es código, y escaparlo lo rompe. Lo único
	 * que se le hace es impedir la fuga: el analizador de HTML cierra un
	 * `<script>` en cuanto lee `</script`, esté donde esté —también dentro de
	 * una cadena—, así que esa secuencia se escribe con la barra escapada.
	 * `'</script>'` y `'<\/script>'` son la misma cadena para JavaScript, de
	 * modo que el código sigue haciendo lo mismo.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_custom_js( $value ): string {
		return (string) preg_replace( '#</(?=script)#i', '<\\\\/', (string) $value );
	}

	/**
	 * Keep only a valid Y-m-d calendar date.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_date( $value ): string {
		$value = trim( (string) $value );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
			return '';
		}
		$parts = array_map( 'intval', explode( '-', $value ) );
		return checkdate( $parts[1], $parts[2], $parts[0] ) ? $value : '';
	}

	/**
	 * Whitelist satellite page types ('' = the event root itself).
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_section_type( $value ): string {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}
		return isset( EventMetaKeys::section_types()[ $value ] ) ? $value : EventMetaKeys::SECTION_OTHER;
	}

	/**
	 * A hashtag without its hash and without spaces.
	 *
	 * El formulario del sistema anterior ya pide «no poner el símbolo #» y la
	 * gente lo pone igual.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_hashtag( $value ): string {
		$value = sanitize_text_field( (string) $value );
		$value = ltrim( trim( $value ), '#' );
		return (string) preg_replace( '/\s+/u', '', $value );
	}

	/**
	 * A checkbox: stored as a real boolean.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	public static function sanitize_bool( $value ): bool {
		if ( is_string( $value ) ) {
			return in_array( strtolower( trim( $value ) ), array( '1', 'on', 'true', 'si', 'sí', 'yes' ), true );
		}
		return (bool) $value;
	}

	/**
	 * A positive attachment or form ID; 0 when there is none.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	public static function sanitize_id( $value ): int {
		$id = (int) $value;
		return $id > 0 ? $id : 0;
	}

	/**
	 * An http(s) address, or nothing.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_url( $value ): string {
		$url = esc_url_raw( trim( (string) $value ), array( 'http', 'https' ) );
		return is_string( $url ) ? $url : '';
	}

	/**
	 * A hexadecimal colour, or nothing (which means «the default one»).
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_color( $value ): string {
		$color = sanitize_hex_color( trim( (string) $value ) );
		return is_string( $color ) ? $color : '';
	}

	/**
	 * One of the six typefaces, or the theme's.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_font( $value ): string {
		return EventMetaKeys::in_list( $value, EventMetaKeys::fonts(), EventMetaKeys::FONT_DEFAULT );
	}

	/**
	 * Square or circle; square when it is neither.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_image_shape( $value ): string {
		return EventMetaKeys::in_list( $value, EventMetaKeys::image_shapes(), EventMetaKeys::SHAPE_SQUARE );
	}

	/**
	 * One of the header separators; none when it is not one of ours.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_separator( $value ): string {
		return EventMetaKeys::in_list( $value, EventMetaKeys::separators(), '' );
	}
}
