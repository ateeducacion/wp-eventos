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

// ---- src/Evt/Meta/EventMetaKeys.php ----
/**
 * Meta keys and closed vocabularies for the evt_event CPT.
 *
 * @package Evt
 */

namespace Evt\Meta;

// Code Snippets vuelve a evaluar un snippet activo al guardarlo: si el
// bundle ya se cargó hay que salir, o el segundo eval() muere redeclarando
// clases. Una constante, porque class_exists() ya es cierto al compilar.
if ( \defined( 'EVT_BUNDLE_LOADED' ) ) {
	return;
}
\define( 'EVT_BUNDLE_LOADED', true );

/**
 * Event post meta keys (never hardcode these strings elsewhere).
 *
 * Los valores de `section_types()` van en castellano a propósito: son el
 * vocabulario del campo «Tipo de página» del formulario del sistema anterior,
 * calcado 1:1 para que las páginas que ya existen se puedan migrar sin
 * traducir nada. Lo mismo con los estados, que hoy son términos de la
 * taxonomía `convocatoria` (`evento-abierto`, `evento-finalizado`) puestos a
 * mano.
 *
 * Las claves de apariencia sustituyen a los campos de diseño de ese formulario
 * que sí se portan: fondo y texto de la cabecera, las dos tipografías, la forma
 * de las imágenes y el separador. Los demás —el mosaico, el HTML de
 * sustitución, el CSS por página— no se portan, y por qué está escrito en
 * SDD-0002.
 */
final class EventMetaKeys {

	/**
	 * Qué es esta página dentro del evento. Vacía en la raíz del evento.
	 */
	public const SECTION_TYPE = 'evt_section_type';

	/**
	 * Primer día del evento, en Y-m-d.
	 */
	public const START_DATE = 'evt_start_date';

	/**
	 * Último día del evento, en Y-m-d. Vacío cuando dura un solo día.
	 */
	public const END_DATE = 'evt_end_date';

	/**
	 * Sede, tal y como se anuncia.
	 */
	public const VENUE = 'evt_venue';

	/**
	 * Lema del evento: la línea que acompaña al título.
	 */
	public const TAGLINE = 'evt_tagline';

	/**
	 * Etiqueta de redes, sin la almohadilla.
	 */
	public const HASHTAG = 'evt_hashtag';

	/**
	 * Texto introductorio de la portada del evento. Admite HTML de entrada.
	 */
	public const INTRO = 'evt_intro';

	/**
	 * Si la portada enseña el botón de inscripción.
	 */
	public const SIGNUP_SHOW = 'evt_signup_show';

	/**
	 * Rótulo del botón de inscripción.
	 */
	public const SIGNUP_LABEL = 'evt_signup_label';

	/**
	 * Dirección a la que lleva el botón de inscripción.
	 */
	public const SIGNUP_URL = 'evt_signup_url';

	/**
	 * HISTÓRICO: identificador del formulario de inscripción del sistema viejo.
	 *
	 * Existe solo para los eventos que vienen del sistema anterior, donde la
	 * inscripción era un formulario por evento del plugin que se sustituye. El
	 * aplicativo **no lo lee para nada más que para seguir pintando ese
	 * formulario en los eventos migrados**: los participantes pasan a
	 * gestionarse aquí, con formulario propio (ADR-0027).
	 *
	 * **Es previsible que desaparezca.** Cuando no quede ningún evento vivo que
	 * lo use, se retira esta clave, su campo en «Ajustes» y la rama de
	 * `EventView::has_form()` que lo consulta. No se le añadan usos nuevos.
	 */
	public const SIGNUP_FORM_ID = 'evt_signup_form_id';

	/**
	 * Color de fondo de la cabecera del evento, hexadecimal.
	 */
	public const HEADER_BG = 'evt_header_bg';

	/**
	 * Color del texto de la cabecera del evento, hexadecimal.
	 */
	public const HEADER_TEXT = 'evt_header_text';

	/**
	 * Tipografía de los títulos.
	 */
	public const TITLE_FONT = 'evt_title_font';

	/**
	 * Tipografía del cuerpo.
	 */
	public const BODY_FONT = 'evt_body_font';

	/**
	 * ID del adjunto con el logo acompañante de la cabecera.
	 */
	public const LOGO_ID = 'evt_logo_id';

	/**
	 * ID del adjunto con el cartel del evento.
	 */
	public const POSTER_ID = 'evt_poster_id';

	/**
	 * Forma de las imágenes de personas: cuadrada o redonda.
	 */
	public const IMAGE_SHAPE = 'evt_image_shape';

	/**
	 * Separador al pie de la cabecera del evento.
	 */
	public const SEPARATOR = 'evt_separator';

	/**
	 * CSS a medida de esta página. Se guarda en crudo: es código, no texto.
	 *
	 * En la raíz del evento viste todas sus páginas; en una página satélite,
	 * solo esa, y va después del del evento para poder afinarlo. Nunca sale
	 * fuera de las páginas de su evento.
	 */
	public const CUSTOM_CSS = 'evt_custom_css';

	/**
	 * JavaScript a medida de esta página. También en crudo, y también acotado.
	 *
	 * Esto es ejecución de código en el navegador de cada visitante, así que
	 * su capacidad es propia y más estrecha que la del CSS
	 * ({@see \Evt\Access\EventAccess::can_edit_custom_js()}).
	 */
	public const CUSTOM_JS = 'evt_custom_js';

	/**
	 * Evento cerrado a edición para siempre: el estado «histórico».
	 *
	 * La marca la pone y la quita a mano quien administra el aplicativo
	 * (`evt_manage_app`), y con ella puesta el área deja de poder editar el
	 * evento y todo lo que cuelga de él. No es un despublicado: la página
	 * pública se sigue viendo igual, y el taller se sigue abriendo en solo
	 * lectura.
	 *
	 * No se confunde con `evt_legacy`, que la pone el guion de migración y
	 * significa otra cosa: que el contenido es contenido del maquetador, congelado del sistema
	 * viejo y no se regenera. La migración pone las dos; un evento nacido
	 * aquí puede recibir esta y nunca tendrá aquella.
	 */
	public const ARCHIVED = 'evt_archived';

	/**
	 * Sección que no encaja en ninguna de las demás.
	 */
	public const SECTION_OTHER = 'otra';

	/**
	 * Aún no ha empezado.
	 */
	public const STATE_UPCOMING = 'proximo';

	/**
	 * Está ocurriendo.
	 */
	public const STATE_OPEN = 'abierto';

	/**
	 * Ya pasó.
	 */
	public const STATE_FINISHED = 'finalizado';

	/**
	 * Imagen cuadrada. Es la forma por defecto.
	 */
	public const SHAPE_SQUARE = 'square';

	/**
	 * Imagen recortada en círculo.
	 */
	public const SHAPE_CIRCLE = 'circle';

	/**
	 * La tipografía del tema: ni se elige ni se carga nada.
	 */
	public const FONT_DEFAULT = '';

	/**
	 * All meta keys stored on an event post.
	 *
	 * @return string[]
	 */
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
			self::POSTER_ID,
			self::IMAGE_SHAPE,
			self::SEPARATOR,
			self::CUSTOM_CSS,
			self::CUSTOM_JS,
			self::ARCHIVED,
		);
	}

	/**
	 * The two keys that hold code instead of content.
	 *
	 * Se listan aparte porque en todas partes se tratan distinto: no se
	 * escapan al salir, no los escribe cualquiera que edite el evento y viven
	 * dentro del recuadro de «Solo administración».
	 *
	 * @return string[]
	 */
	public static function code_keys(): array {
		return array( self::CUSTOM_CSS, self::CUSTOM_JS );
	}

	/**
	 * Closed vocabulary of satellite page types.
	 *
	 * @return array<string, string> slug => etiqueta.
	 */
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

	/**
	 * Closed vocabulary of derived event states.
	 *
	 * @return array<string, string> slug => etiqueta.
	 */
	public static function states(): array {
		return array(
			self::STATE_UPCOMING => 'Próximo',
			self::STATE_OPEN     => 'Abierto',
			self::STATE_FINISHED => 'Finalizado',
		);
	}

	/**
	 * Closed vocabulary of typefaces.
	 *
	 * Hoy la tipografía sale de un desplegable con las 1.461 familias de Google
	 * Fonts, y el resultado —está medido, y el material está en `.local/`— son
	 * decenas de combinaciones distintas conviviendo en el mismo sitio. Aquí son
	 * seis, elegidas por legibilidad y por pares que combinan, y una de ellas es
	 * «la del tema»: el valor vacío, que no carga nada. El slug es el que va en
	 * la clave; la etiqueta, el nombre de la familia tal y como lo pide la hoja
	 * de estilos.
	 *
	 * @return array<string, string> slug => nombre de la familia.
	 */
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

	/**
	 * Closed vocabulary of image shapes for people's photographs.
	 *
	 * @return array<string, string> slug => etiqueta.
	 */
	public static function image_shapes(): array {
		return array(
			self::SHAPE_SQUARE => 'Cuadrada',
			self::SHAPE_CIRCLE => 'Redonda',
		);
	}

	/**
	 * Closed vocabulary of header separators.
	 *
	 * El formulario anterior ofrece hoy las veinticinco siluetas del tema. Se
	 * conservan las seis que se dibujan con una silueta propia y se usan de
	 * verdad; una que llegue de la migración fuera de esta lista se queda sin
	 * separador, que es el valor vacío y el aspecto por defecto.
	 *
	 * @return array<string, string> slug => etiqueta.
	 */
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

	/**
	 * Keep a value only when the closed list has it.
	 *
	 * @param mixed                 $value    Raw value.
	 * @param array<string, string> $allowed  One of the vocabularies above.
	 * @param string                $fallback What to return otherwise.
	 * @return string
	 */
	public static function in_list( $value, array $allowed, string $fallback = '' ): string {
		$value = trim( (string) $value );
		return isset( $allowed[ $value ] ) ? $value : $fallback;
	}
}

// ---- src/Evt/Meta/EventMetaRegistration.php ----
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

// ---- src/Evt/Meta/ProgrammeMetaKeys.php ----
/**
 * Meta keys of what hangs from an event: speakers and programme activities.
 *
 * @package Evt
 */

namespace Evt\Meta;

/**
 * Las claves de meta de `evt_speaker` y `evt_activity`, y sus listas cerradas.
 *
 * Están aparte de {@see EventMetaKeys} porque son de otros dos tipos de
 * contenido: `all()` de aquella significa «las metas del evento» y mezclarlas
 * volvería esa lista una mentira.
 *
 * **Ni el ponente ni la actividad guardan a qué evento pertenecen en una
 * meta**: cuelgan del evento por `post_parent`, que es lo que hace que el
 * acotado por área y el cierre por histórico les alcancen sin una línea de
 * código más ({@see \Evt\Access\EventAccess::root_id()}).
 */
final class ProgrammeMetaKeys {

	/**
	 * What the speaker does: «Asesora de formación», «Catedrático de Secundaria».
	 */
	public const SPEAKER_ROLE = 'evt_speaker_role';

	/**
	 * Where the speaker comes from: centro, universidad, empresa.
	 */
	public const SPEAKER_ORG = 'evt_speaker_org';

	/**
	 * What kind of slot this is: ponencia, taller, mesa redonda…
	 */
	public const ACTIVITY_KIND = 'evt_activity_kind';

	/**
	 * Day of the activity, `Y-m-d`.
	 */
	public const ACTIVITY_DATE = 'evt_activity_date';

	/**
	 * Start time, `H:i`.
	 */
	public const ACTIVITY_START = 'evt_activity_start';

	/**
	 * End time, `H:i`. Empty means «hasta la siguiente».
	 */
	public const ACTIVITY_END = 'evt_activity_end';

	/**
	 * Sede of this activity.
	 *
	 * Es de la actividad y no del día: un mismo día puede tener dos sedes, y
	 * la parrilla agrupa por día y, dentro, por sede (ADR-0024). No hay
	 * ninguna pantalla donde «dar de alta una sede»: la lista de sedes de un
	 * evento sale de sus actividades.
	 */
	public const ACTIVITY_VENUE = 'evt_activity_venue';

	/**
	 * Room inside the sede: «Aula 2», «Salón de actos».
	 */
	public const ACTIVITY_ROOM = 'evt_activity_room';

	/**
	 * Seats of a workshop; 0 means «sin límite».
	 */
	public const ACTIVITY_SEATS = 'evt_activity_seats';

	/**
	 * Speakers of this activity: post IDs of `evt_speaker`, comma separated.
	 *
	 * Una sola meta y no una por ponente: se lee y se escribe entera, nunca
	 * se consulta por ella, y `register_post_meta()` con `single => true` es
	 * lo que deja la escritura pasando por un solo saneado.
	 */
	public const ACTIVITY_SPEAKERS = 'evt_activity_speakers';

	/**
	 * The kind that takes seats and enrolment.
	 */
	public const KIND_WORKSHOP = 'taller';

	/**
	 * All meta keys stored on a speaker.
	 *
	 * @return string[]
	 */
	public static function speaker_keys(): array {
		return array( self::SPEAKER_ROLE, self::SPEAKER_ORG );
	}

	/**
	 * All meta keys stored on an activity.
	 *
	 * @return string[]
	 */
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

	/**
	 * Closed vocabulary of activity kinds.
	 *
	 * En código y no en taxonomía, por la misma lección que los tipos de
	 * sección: una lista abierta acaba mezclando ejes (ADR-0004).
	 *
	 * @return array<string, string> slug => etiqueta.
	 */
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

	/**
	 * The label of one kind, or the fallback one.
	 *
	 * @param string $kind Kind slug.
	 * @return string
	 */
	public static function kind_label( string $kind ): string {
		$lista = self::activity_kinds();
		return $lista[ $kind ] ?? $lista['otra'];
	}
}

// ---- src/Evt/Meta/ProgrammeMetaRegistration.php ----
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

// ---- src/Evt/Meta/RegistrationMetaKeys.php ----
/**
 * Meta keys of a registration, and of the signup settings of its event.
 *
 * @package Evt
 */

namespace Evt\Meta;

/**
 * Las claves de una inscripción y las de la inscripción de un evento.
 *
 * Dos grupos que no hay que confundir:
 *
 * - `REG_*` cuelgan de la **inscripción** (`evt_registration`): son los datos
 *   de una persona. Su `auth_callback` devuelve `false` siempre: no hay
 *   ninguna vía por la que se escriban desde fuera del aplicativo (ADR-0032).
 * - `SIGNUP_*` cuelgan del **evento**: son los ajustes de su inscripción —el
 *   plazo, las preguntas, los textos del consentimiento— y los edita quien
 *   organiza.
 *
 * El núcleo de la inscripción son los siete campos que fijó la ADR-0031, y
 * está aquí entero y en un solo sitio para que se vea que no crece solo.
 */
final class RegistrationMetaKeys {

	// ─── El núcleo de la inscripción (ADR-0031) ────────────────────────────

	public const REG_TAX_ID  = 'evt_reg_tax_id';
	public const REG_NAME    = 'evt_reg_name';
	public const REG_SURNAME = 'evt_reg_surname';
	public const REG_EMAIL   = 'evt_reg_email';
	public const REG_PHONE   = 'evt_reg_phone';
	public const REG_CENTRE  = 'evt_reg_centre';

	/**
	 * Which version of the consent texts this person accepted, and when.
	 *
	 * Las dos juntas son la constancia que pide la ADR-0020: una casilla sin
	 * saber qué texto se aceptó y cuándo no vale si alguien lo reclama.
	 */
	public const REG_CONSENT_VERSION = 'evt_reg_consent_version';
	public const REG_CONSENT_AT      = 'evt_reg_consent_at';

	/**
	 * The activity ID of the chosen workshop, 0 for none.
	 *
	 * Un identificador y no un título: el título se retoca y la elección
	 * seguiría apuntando al taller que es (ADR-0033).
	 */
	public const REG_WORKSHOP = 'evt_reg_workshop';

	/**
	 * Answers to the questions of the event, as JSON keyed by question ID.
	 *
	 * Por identificador y nunca por rótulo: reescribir el rótulo de una
	 * pregunta no puede desconectar lo que ya contestó nadie (ADR-0032).
	 */
	public const REG_ANSWERS = 'evt_reg_answers';

	/**
	 * The one-time token that lets this person back into their registration.
	 *
	 * No es una sesión: abre esta inscripción y nada más del sitio (ADR-0033).
	 */
	public const REG_TOKEN = 'evt_reg_token';

	// ─── Los ajustes de inscripción, en el evento ──────────────────────────

	/**
	 * Whether the signup form is open at all.
	 */
	public const SIGNUP_OPEN = 'evt_signup_open';

	/**
	 * The questions of this event, as JSON.
	 */
	public const SIGNUP_QUESTIONS = 'evt_signup_questions';

	/**
	 * The window in which workshops can be chosen, and its own switch.
	 *
	 * Tiene plazo propio porque se abre cuando quien organiza ha cerrado el
	 * programa, que casi nunca es cuando se abre la inscripción (ADR-0033).
	 */
	public const WORKSHOP_OPEN  = 'evt_workshop_open';
	public const WORKSHOP_START = 'evt_workshop_start';
	public const WORKSHOP_END   = 'evt_workshop_end';

	/**
	 * The two consent texts and the version they are on.
	 *
	 * La versión sube cuando se cambia cualquiera de los dos textos, y **no**
	 * reescribe lo que ya aceptó nadie (ADR-0020).
	 */
	public const CONSENT_PRIVACY = 'evt_consent_privacy';
	public const CONSENT_IMAGE   = 'evt_consent_image';
	public const CONSENT_VERSION = 'evt_consent_version';

	/**
	 * Every meta key that hangs off a registration.
	 *
	 * @return string[]
	 */
	public static function registration_keys(): array {
		return array(
			self::REG_TAX_ID,
			self::REG_NAME,
			self::REG_SURNAME,
			self::REG_EMAIL,
			self::REG_PHONE,
			self::REG_CENTRE,
			self::REG_CONSENT_VERSION,
			self::REG_CONSENT_AT,
			self::REG_WORKSHOP,
			self::REG_ANSWERS,
			self::REG_TOKEN,
		);
	}

	/**
	 * Every meta key of the signup settings of an event.
	 *
	 * @return string[]
	 */
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

	/**
	 * The four field types a question may have, with their label.
	 *
	 * Lista cerrada y corta a propósito: son las cuatro de la ADR-0031, y
	 * añadir una quinta es mover la raya que separa esto de un constructor de
	 * formularios.
	 *
	 * @return array<string, string>
	 */
	public static function question_types(): array {
		return array(
			'check' => 'Casilla',
			'one'   => 'Una opción',
			'many'  => 'Varias opciones',
			'text'  => 'Texto corto',
		);
	}

	/**
	 * Whether a question type carries a list of options.
	 *
	 * @param string $type Question type.
	 * @return bool
	 */
	public static function has_options( string $type ): bool {
		return 'one' === $type || 'many' === $type;
	}
}

// ---- src/Evt/Domain/DateRange.php ----
/**
 * A range of days written the way Spanish writes it.
 *
 * @package Evt
 */

namespace Evt\Domain;

/**
 * Escribe las fechas de un evento en castellano.
 *
 * Hasta ahora la cabecera pública pegaba dos fechas sueltas con un «al» y las
 * pintaba con el formato del ajuste `date_format` del sitio, que por defecto es
 * el inglés `F j, Y`. El resultado, verificado en la portada de un evento, era
 * «Del septiembre 12, 2026 al septiembre 14, 2026». Y no se arregla cambiando
 * el ajuste: un intervalo en castellano no es dos fechas sueltas, porque el mes
 * y el año se dicen una sola vez cuando los dos días los comparten.
 *
 *   un solo día ............. «12 de septiembre de 2026»
 *   mismo mes y año ......... «Del 12 al 14 de septiembre de 2026»
 *   mismo año, otro mes ..... «Del 30 de septiembre al 2 de octubre de 2026»
 *   otro año ................ «Del 30 de diciembre de 2026 al 2 de enero de 2027»
 *   solo el primer día ...... «Desde el 12 de septiembre de 2026»
 *   fecha que no es fecha ... cadena vacía, y quien la pinta se calla la línea
 *
 * Pura salvo por los nombres de mes, que salen de `WP_Locale` y nunca de un
 * array escrito a mano: traducirlos aquí sería tener dos veces lo que
 * WordPress ya tiene traducido.
 */
final class DateRange {

	/**
	 * A range of days, written out.
	 *
	 * @param string $start First day, Y-m-d.
	 * @param string $end   Last day, Y-m-d ('' when there is none yet).
	 * @return string Empty when the first day is missing or is not a real date.
	 */
	public static function of( string $start, string $end = '' ): string {
		$desde = self::parts( $start );
		if ( array() === $desde ) {
			return '';
		}

		$hasta = self::parts( $end );
		if ( array() === $hasta ) {
			return 'Desde el ' . self::day( $desde );
		}

		// Un último día anterior al primero es una errata: el intervalo no se
		// invierte, se escribe el primer día y ya. Igual que hace EventState.
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

	/**
	 * One day, written in full.
	 *
	 * @param array{n:int,y:int,m:int,d:int} $day What parts() returned.
	 * @return string
	 */
	private static function day( array $day ): string {
		return sprintf( '%d de %s de %d', $day['d'], self::month( $day['m'] ), $day['y'] );
	}

	/**
	 * Year, month and day of a Y-m-d string.
	 *
	 * `n` es la fecha como número comparable (20260912): las que llegan de la
	 * migración no siempre traen el cero de relleno, así que comparar cadenas
	 * pondría el 9 después del 10.
	 *
	 * @param string $ymd Day, Y-m-d.
	 * @return array{n:int,y:int,m:int,d:int}|array{} Empty when it is not a real date.
	 */
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

	/**
	 * The name of a month, as the locale writes it.
	 *
	 * En castellano los meses van en minúscula, y el resto de la frase —«Del»,
	 * «al», «de»— ya está en castellano, así que se pasa a minúscula lo que
	 * devuelva la locale.
	 *
	 * @param int $month Month, 1-12.
	 * @return string
	 */
	private static function month( int $month ): string {
		global $wp_locale;
		return mb_strtolower( (string) $wp_locale->get_month( $month ), 'UTF-8' );
	}
}

// ---- src/Evt/Domain/ActivityInput.php ----
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

// ---- src/Evt/Domain/SignupQuestions.php ----
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
 * obligatoria— y ninguna más (ADR-0031). No hay lógica condicional y no hay
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

// ---- src/Evt/Domain/RegistrationInput.php ----
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
	public static function core( array $raw, array $centres = array() ): array {
		$errors = array();

		$tax_id  = self::tax_id( self::text( $raw, 'tax_id' ) );
		$name    = self::text( $raw, 'name' );
		$surname = self::text( $raw, 'surname' );
		$email   = strtolower( self::text( $raw, 'email' ) );
		$phone   = self::phone( self::text( $raw, 'phone' ) );
		$centre  = self::text( $raw, 'centre' );
		$consent = ! empty( $raw['consent'] );

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
		// si hay catálogo, lo que llegue tiene que estar en él. Sin catálogo
		// cargado no se inventa una validación: se exige que venga algo y el
		// aviso lo da la pantalla.
		if ( '' === $centre || ( array() !== $centres && ! in_array( $centre, $centres, true ) ) ) {
			$errors[] = 'centre';
		}
		if ( ! $consent ) {
			$errors[] = 'consent';
		}

		return array(
			'ok'     => array() === $errors,
			'errors' => $errors,
			'data'   => array(
				'tax_id'  => $tax_id,
				'name'    => $name,
				'surname' => $surname,
				'email'   => $email,
				'phone'   => $phone,
				'centre'  => $centre,
				'consent' => $consent,
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

// ---- src/Evt/Domain/EventInput.php ----
/**
 * Pure validation for event form input.
 *
 * @package Evt
 */

namespace Evt\Domain;

use Evt\Meta\EventMetaKeys;

/**
 * Validates and normalises the raw fields of an event or of one of its pages.
 *
 * Pura: ni una llamada a WordPress, para que se pueda probar sin cargarlo.
 */
final class EventInput {

	/**
	 * Validate a submitted event payload.
	 *
	 * Una página satélite es el mismo tipo de contenido con `parent` y
	 * `section_type`: por eso valida las dos cosas el mismo método.
	 *
	 * @param array<string, mixed> $raw Raw fields.
	 * @return array{ok:bool, errors:string[], data:array<string, mixed>}
	 */
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

		// La raíz del evento necesita fechas; una página satélite las hereda del
		// evento y no las repite.
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

	/**
	 * Whether the string is a calendar date in Y-m-d form.
	 *
	 * @param string $date Date string.
	 * @return bool
	 */
	private static function is_valid_date( string $date ): bool {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return false;
		}
		$parts = array_map( 'intval', explode( '-', $date ) );
		return checkdate( $parts[1], $parts[2], $parts[0] );
	}
}

// ---- src/Evt/Domain/EventState.php ----
/**
 * Derived state of an event.
 *
 * @package Evt
 */

namespace Evt\Domain;

use Evt\Meta\EventMetaKeys;

/**
 * Deriva el estado del evento de sus fechas.
 *
 * Hoy en producción el estado es un término de la taxonomía `convocatoria`
 * que alguien marca a mano al crear el evento, y que nadie vuelve a tocar: de
 * 50 términos, 32 páginas están en «evento-finalizado» y 2 en «evento-abierto»
 * porque el resto se quedó sin actualizar. Un dato que se puede calcular no se
 * guarda.
 *
 * Pura: ni una llamada a WordPress.
 */
final class EventState {

	/**
	 * State of an event given its dates.
	 *
	 * @param string $start First day, Y-m-d ('' when unknown).
	 * @param string $end   Last day, Y-m-d ('' = same as the first).
	 * @param string $today Reference day, Y-m-d ('' = today, UTC).
	 * @return string One of EventMetaKeys::states().
	 */
	public static function of( string $start, string $end, string $today = '' ): string {
		$start = trim( $start );
		$end   = trim( $end );
		$today = '' !== trim( $today ) ? trim( $today ) : gmdate( 'Y-m-d' );

		// Un evento que todavía no tiene fechas se está preparando.
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

	/**
	 * Human label for a state.
	 *
	 * @param string $state State slug.
	 * @return string Empty when the slug is not one of ours.
	 */
	public static function label( string $state ): string {
		return (string) ( EventMetaKeys::states()[ $state ] ?? '' );
	}
}

// ---- src/Evt/Access/EventAccess.php ----
/**
 * Capability checks for events: who edits and publishes what, scoped by área.
 *
 * @package Evt
 */

namespace Evt\Access;

use Evt\Meta\EventMetaKeys;
use Evt\PostType\ActivityPostType;
use Evt\PostType\EventPostType;
use Evt\PostType\SpeakerPostType;
use Evt\Taxonomy\EventTaxonomies;

/**
 * El único guardián del aplicativo: aquí se responde «¿quién puede qué?».
 *
 * Falla en cerrado. Quien no tiene ningún área en su perfil y tampoco
 * `evt_edit_all_areas` no edita nada: abrir cuando falta el dato es como un
 * área acaba tocando los eventos de otra.
 */
final class EventAccess {

	/**
	 * User meta con uno o varios term_id de `evt_area`.
	 */
	public const USER_AREA_META = 'evt_area';

	/**
	 * Cap propia del aplicativo: ajustes y diagnóstico.
	 */
	public const CAP_MANAGE = 'evt_manage_app';

	/**
	 * Cap propia del aplicativo: saltarse el acotado por área.
	 */
	public const CAP_ALL_AREAS = 'evt_edit_all_areas';

	/**
	 * Cap propia del aplicativo: escribir el CSS a medida de un evento.
	 *
	 * La tienen `evt_organiser` —acotada a su área, como todo lo demás— y
	 * `administrator`. Dar aspecto a la jornada es parte de organizarla, y
	 * quien la organiza es quien sabe cómo tiene que verse.
	 */
	public const CAP_CUSTOM_CSS = 'evt_edit_custom_css';

	/**
	 * Cap propia del aplicativo: escribir el JavaScript a medida de un evento.
	 *
	 * Solo `administrator`, y distinta a propósito de la del CSS porque el
	 * riesgo no es el mismo: una hoja de estilos cambia cómo se ve una página;
	 * un guion ejecuta código en el navegador de cada persona que la visite.
	 * Ahí es donde está la raya, y es la única de las dos que no cruza el área.
	 */
	public const CAP_CUSTOM_JS = 'evt_edit_custom_js';

	/**
	 * Register filters.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'map_meta_cap', array( self::class, 'map_meta_cap' ), 10, 4 );
		add_action( 'save_post_' . SpeakerPostType::POST_TYPE, array( self::class, 'stamp_area' ) );
		add_action( 'save_post_' . ActivityPostType::POST_TYPE, array( self::class, 'stamp_area' ) );
	}

	/**
	 * The post types this guard scopes, each with the cap that opens its list.
	 *
	 * Los tres van juntos porque un área gestiona su evento entero: la portada,
	 * sus páginas, sus ponentes y sus actividades. Lo que cambia de uno a otro
	 * es la capacidad primitiva, no la regla.
	 *
	 * @return array<string, string> Post type => primitive cap.
	 */
	public static function scoped_types(): array {
		return array(
			EventPostType::POST_TYPE    => 'edit_evt_events',
			SpeakerPostType::POST_TYPE  => 'edit_evt_speakers',
			ActivityPostType::POST_TYPE => 'edit_evt_activities',
		);
	}

	/**
	 * Whether the user administers the application itself.
	 *
	 * @param int $user_id User ID (0 = current).
	 * @return bool
	 */
	public static function is_manager( int $user_id = 0 ): bool {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		return user_can( $user_id, self::CAP_MANAGE ) || user_can( $user_id, 'manage_options' );
	}

	/**
	 * Whether the user works across every área (administración).
	 *
	 * @param int $user_id User ID (0 = current).
	 * @return bool
	 */
	public static function can_edit_all_areas( int $user_id = 0 ): bool {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		return user_can( $user_id, self::CAP_ALL_AREAS ) || self::is_manager( $user_id );
	}

	/**
	 * Áreas the user belongs to.
	 *
	 * @param int $user_id User ID (0 = current).
	 * @return int[] Term IDs of evt_area; empty when the profile has none.
	 */
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

	/**
	 * Áreas a post belongs to.
	 *
	 * Las páginas satélite no llevan área propia: la del evento manda, que es
	 * lo que evita que una hija se quede huérfana de permisos al moverla. Un
	 * ponente y una actividad sí llevan la suya, y pueden llevar varias: son
	 * reutilizables entre ediciones, así que compartir un ponente con otra área
	 * es añadirle ese término, no duplicar la ficha. `root_id()` devuelve el
	 * propio post cuando no cuelga de nadie, que es siempre su caso.
	 *
	 * @param int $post_id Post ID.
	 * @return int[] Term IDs of evt_area.
	 */
	public static function post_areas( int $post_id ): array {
		$terms = get_the_terms( self::root_id( $post_id ), EventTaxonomies::AREA );
		if ( ! is_array( $terms ) ) {
			return array();
		}
		return self::clean_ids( wp_list_pluck( $terms, 'term_id' ) );
	}

	/**
	 * The event a page belongs to (itself when it is the event).
	 *
	 * @param int $post_id Post ID.
	 * @return int
	 */
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

	/**
	 * Whether this event is closed for good: the «histórico» mark.
	 *
	 * Se mira siempre en la raíz, así que el cierre alcanza a las páginas
	 * satélite del evento sin que cada pantalla tenga que acordarse: quien
	 * pregunte por una sección recibe la respuesta de su evento. Lo mismo
	 * valdría para un ponente o una actividad colgados de un evento, si algún
	 * día los cuelgan: hoy no lo están, se acotan por área y `root_id()`
	 * devuelve el propio post.
	 *
	 * @param int $post_id Post ID; the event, or anything under it.
	 * @return bool
	 */
	public static function is_archived( int $post_id ): bool {
		if ( $post_id <= 0 ) {
			return false;
		}
		return (bool) get_post_meta( self::root_id( $post_id ), EventMetaKeys::ARCHIVED, true );
	}

	/**
	 * Whether the user may MARK this event as «histórico».
	 *
	 * Lo marca el área que lo organiza, que es quien sabe cuándo está
	 * terminado; no hace falta administrar el aplicativo para cerrar lo tuyo.
	 *
	 * La puerta es {@see can_open()} y no {@see can_edit()} a propósito, y no
	 * es un descuido: `can_edit()` lleva el cierre encima, así que preguntar
	 * por él aquí se mordería la cola —marcar exigiría poder editar, y lo
	 * primero que hace la marca es quitar esa posibilidad—. Lo que hay que
	 * comprobar es la regla del área, que es lo que `can_open()` responde.
	 *
	 * Que la marca ya esté puesta no lo comprueba esta función: eso es el
	 * estado, no el permiso, y quien decide qué botón se pinta y qué POST se
	 * acepta es la pantalla. Volver a marcar lo marcado no cambia nada.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Event ID.
	 * @return bool
	 */
	public static function can_archive( int $user_id, int $post_id ): bool {
		return self::can_open( $user_id, $post_id );
	}

	/**
	 * Whether the user may UNMARK an event as «histórico».
	 *
	 * Solo administración, y la asimetría con {@see can_archive()} es
	 * deliberada: cerrar lo tuyo es tuyo, reabrirlo necesita a otra persona.
	 * Si el área pudiera desmarcarlo sola, el cierre sería una preferencia y
	 * no un cierre.
	 *
	 * @param int $user_id User ID (0 = current).
	 * @return bool
	 */
	public static function can_unarchive( int $user_id = 0 ): bool {
		return self::is_manager( $user_id );
	}

	/**
	 * Whether the user may write the «histórico» mark as it stands today.
	 *
	 * La suma de las dos reglas de arriba, y la que contesta a la pregunta que
	 * de verdad se hace desde fuera de la pantalla —el `auth_callback` de la
	 * meta—, donde no se sabe qué valor se va a escribir: mientras el evento
	 * está abierto, la marca la toca su área; en cuanto está cerrado, solo la
	 * toca administración. Así el área no se desmarca el evento por la puerta
	 * de al lado con un `update_post_meta()`.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Event ID.
	 * @return bool
	 */
	public static function can_toggle_archived( int $user_id, int $post_id ): bool {
		return self::is_archived( $post_id )
			? self::can_unarchive( $user_id )
			: self::can_archive( $user_id, $post_id );
	}

	/**
	 * Whether the user may open this event, even if only to read it.
	 *
	 * Es la regla del área, sin el cierre por encima: un evento marcado como
	 * histórico se sigue consultando y exportando desde su taller, y por eso
	 * el listado y el taller preguntan por aquí y no por {@see can_edit()}.
	 * Esconderlo sería peor: quien lo busque tiene que encontrarlo y entender
	 * por qué no puede tocarlo.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Post ID.
	 * @return bool
	 */
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
			// Recién creado y todavía sin área: lo edita quien lo creó, que es
			// quien tiene que ponérsela. Para los ponentes y las actividades es
			// una ventana muy corta, porque `stamp_area()` se la pone al
			// guardar; se queda abierta cuando quien lo crea no tiene área
			// propia —la administración—, y entonces solo lo
			// toca esa persona hasta que alguien le asigne un área.
			return (int) get_post_field( 'post_author', self::root_id( $post_id ) ) === $user_id;
		}

		return array() !== array_intersect( $mine, $theirs );
	}

	/**
	 * Whether the user may edit this event, page, speaker or activity.
	 *
	 * La misma regla para los tres tipos, que es lo que hace que un área
	 * gestione su evento entero: comparte alguna área con el contenido, o no
	 * lo toca. Y por encima de esa regla, el cierre: con el evento marcado
	 * como histórico no lo edita el área que lo organizó —tampoco sus
	 * secciones—, y sí administración, porque alguien tiene que
	 * poder corregir una errata o desmarcarlo.
	 *
	 * Las dos reglas conviven sin contradecirse porque responden a preguntas
	 * distintas: la de ADR-0012 mira el calendario y cierra sola cuando el
	 * evento termina; esta la pone una persona y no se abre con el tiempo.
	 * Basta una para decir «no», y esta es la de arriba.
	 *
	 * Este es el único sitio donde se junta todo: `map_meta_cap()` manda aquí
	 * `edit_post`, `delete_post` y `publish_post`, así que el cierre alcanza
	 * también al escritorio, a la edición rápida y a la REST.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function can_edit( int $user_id, int $post_id ): bool {
		if ( ! self::can_open( $user_id, $post_id ) ) {
			return false;
		}
		return ! self::is_archived( $post_id ) || self::is_manager( $user_id );
	}

	/**
	 * The primitive cap that scopes this post, '' when it is not ours.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private static function scoped_cap( int $post_id ): string {
		$tipos = self::scoped_types();
		$tipo  = (string) get_post_type( $post_id );
		return isset( $tipos[ $tipo ] ) ? $tipos[ $tipo ] : '';
	}

	/**
	 * Give a brand new speaker or activity the áreas of whoever created it.
	 *
	 * Sin esto un ponente nace sin área y solo lo toca quien lo tecleó, no sus
	 * compañeras: el área es el ámbito de trabajo, no la autoría (ADR-0006).
	 * Solo escribe cuando todavía no hay ninguna, así que compartir un ponente
	 * con otra área —añadirle su término— no se deshace en el siguiente
	 * guardado. Se mira el área de quien firma el post y no la de quien guarda,
	 * para que la migración y WP-CLI den el mismo resultado.
	 *
	 * @param int $post_id Speaker or activity being saved.
	 * @return void
	 */
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

	/**
	 * Whether the user may publish events, and this one in particular.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Post ID (0 = just the capability).
	 * @return bool
	 */
	public static function can_publish( int $user_id, int $post_id = 0 ): bool {
		if ( $user_id <= 0 || ! user_can( $user_id, 'publish_evt_events' ) ) {
			return false;
		}
		if ( $post_id <= 0 ) {
			return true;
		}
		return self::can_edit( $user_id, $post_id );
	}

	/**
	 * Whether the user may write the custom CSS of this event or page.
	 *
	 * Las dos condiciones, siempre: la capacidad propia y poder editar ese
	 * evento. Tener la capacidad no abre los eventos de otra área.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function can_edit_custom_css( int $user_id, int $post_id ): bool {
		return self::can_edit_code( $user_id, $post_id, self::CAP_CUSTOM_CSS );
	}

	/**
	 * Whether the user may write the custom JavaScript of this event or page.
	 *
	 * Lo mismo que el CSS y una condición más: en multisitio hace falta
	 * `unfiltered_html`. WordPress se la quita a propósito a quien administra
	 * un subsitio y se la reserva a la superadministración; esa decisión es de
	 * la plataforma y aquí se respeta en vez de rodearla.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function can_edit_custom_js( int $user_id, int $post_id ): bool {
		if ( ! self::can_edit_code( $user_id, $post_id, self::CAP_CUSTOM_JS ) ) {
			return false;
		}

		$suelto = ! is_multisite() || user_can( $user_id, 'unfiltered_html' );

		/**
		 * Filter the multisite rule for the custom JavaScript field.
		 *
		 * La válvula explícita para el caso legítimo en que haga falta abrirlo
		 * en una red: un `add_filter` que se ve en el repositorio y se audita,
		 * y no una concesión global y silenciosa de `unfiltered_html` como la
		 * del sistema anterior.
		 *
		 * Solo puede relajar la regla de multisitio. La capacidad
		 * `evt_edit_custom_js` y el acotado por área se comprueban antes y este
		 * filtro no los toca: quien no puede editar el evento sigue sin poder.
		 *
		 * @param bool $suelto  Whether the multisite rule is satisfied.
		 * @param int  $user_id User ID.
		 * @param int  $post_id Post ID.
		 */
		return (bool) apply_filters( 'evt_allow_custom_js', $suelto, $user_id, $post_id );
	}

	/**
	 * Shared rule of the two code fields: the capability and the event.
	 *
	 * @param int    $user_id User ID.
	 * @param int    $post_id Post ID.
	 * @param string $cap     Capability to require.
	 * @return bool
	 */
	private static function can_edit_code( int $user_id, int $post_id, string $cap ): bool {
		if ( $user_id <= 0 || ! user_can( $user_id, $cap ) ) {
			return false;
		}
		return self::can_edit( $user_id, $post_id );
	}

	/**
	 * Why the user cannot touch this event, in words.
	 *
	 * Vive aquí y no en cada pantalla para que todas lo cuenten igual.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Post ID.
	 * @return string Empty when there is nothing to explain.
	 */
	public static function why_not_editable( int $user_id, int $post_id ): string {
		if ( self::can_edit( $user_id, $post_id ) ) {
			return '';
		}
		// El cierre va primero porque es la regla de arriba: si el área podría
		// editarlo y no puede, es por esto y no por el área ni por el perfil.
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

	/**
	 * Turn the área policy into a denial for the core meta caps.
	 *
	 * Esta es la capa que de verdad protege: el acotado del listado solo
	 * esconde, y deja abiertos el enlace directo, la edición rápida y la REST.
	 *
	 * @param string[] $caps    Primitive caps.
	 * @param string   $cap     Meta cap.
	 * @param int      $user_id User ID.
	 * @param array    $args    Cap args (post ID in the first position).
	 * @return string[]
	 */
	public static function map_meta_cap( array $caps, string $cap, int $user_id, array $args ): array {
		if ( ! in_array( $cap, array( 'edit_post', 'delete_post', 'publish_post' ), true ) ) {
			return $caps;
		}
		$post_id = isset( $args[0] ) ? (int) $args[0] : 0;
		if ( $post_id <= 0 || ! isset( self::scoped_types()[ (string) get_post_type( $post_id ) ] ) ) {
			return $caps;
		}
		if ( ! self::can_edit( $user_id, $post_id ) ) {
			return array( 'do_not_allow' );
		}
		return $caps;
	}

	/**
	 * Keep positive integers only, without repeats.
	 *
	 * @param array<int, mixed> $values Raw values.
	 * @return int[]
	 */
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

// ---- src/Evt/PostType/EventPostType.php ----
/**
 * Register the evt_event custom post type.
 *
 * @package Evt
 */

namespace Evt\PostType;

use Evt\Access\EventAccess;

/**
 * CPT registration for events.
 *
 * Es jerárquico porque un evento es a la vez su portada (raíz) y sus páginas
 * satélite (hijas): programa, ponentes, inscripción… Así sustituye 1:1 a las
 * `page` jerárquicas de hoy y se conserva la forma de las URL.
 */
final class EventPostType {

	public const POST_TYPE = 'evt_event';

	/**
	 * Core meta key holding the theme template of a page.
	 */
	public const TEMPLATE_META = '_wp_page_template';

	/**
	 * Value that means «la que el tema use para esto», que es no elegir ninguna.
	 */
	public const TEMPLATE_DEFAULT = 'default';

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
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
				// Conservar las URL actuales (`/eventos/<slug>/`) pide reescritura
				// en la raíz del subsitio y su propia ADR; la fase 1 no la toca.
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

		// Un evento se pinta como una página, así que usa las plantillas que el
		// tema declara para `page`. Sin esto, `_wp_page_template` no se ofrece
		// en ningún sitio y la portada del evento sale con la cabecera, el pie
		// y el título del tema encima del nuestro.
		add_filter( 'theme_' . self::POST_TYPE . '_templates', array( self::class, 'theme_templates' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( self::class, 'set_blank_template' ) );
	}

	/**
	 * Offer events the page templates of the active theme.
	 *
	 * El tema del sitio puede declarar una plantilla —«Página en blanco», por
	 * ejemplo— solo para `page`; con este filtro WordPress la ofrece también en el evento, la
	 * enseña en «Atributos de página» y `get_single_template()` la honra —desde
	 * WP 4.7 `_wp_page_template` vale para cualquier tipo de contenido—.
	 *
	 * @param mixed $templates Templates already declared for evt_event.
	 * @return array<string, string> Fichero => rótulo.
	 */
	public static function theme_templates( $templates ): array {
		$propias = is_array( $templates ) ? $templates : array();
		// Lo que el tema declare con `Template Post Type: evt_event` manda.
		return array_merge( wp_get_theme()->get_page_templates( null, 'page' ), $propias );
	}

	/**
	 * Every template an event may be given, ready for a select.
	 *
	 * @return array<string, string> valor => rótulo.
	 */
	public static function page_templates(): array {
		return array_merge(
			array( self::TEMPLATE_DEFAULT => 'La del tema' ),
			wp_get_theme()->get_page_templates( null, self::POST_TYPE )
		);
	}

	/**
	 * The blank template of the active theme, when it offers one.
	 *
	 * No hay ningún nombre de fichero escrito a fuego: se busca en lo que el
	 * tema declara. el tema la llama `page-template-blank.php` / «Página en
	 * blanco»; un tema que no traiga ninguna —Twenty Twenty-Five— devuelve
	 * cadena vacía y el evento se queda con la plantilla por defecto.
	 *
	 * @return string Template file, '' when the theme offers none.
	 */
	public static function blank_template(): string {
		$plantillas = wp_get_theme()->get_page_templates( null, self::POST_TYPE );
		$encontrada = '';

		foreach ( $plantillas as $fichero => $rotulo ) {
			if ( preg_match( '/blank|en\s*blanco|vac[ií]a/iu', (string) $fichero . ' ' . (string) $rotulo ) ) {
				$encontrada = (string) $fichero;
				break;
			}
		}

		/**
		 * Filter which template a new event gets.
		 *
		 * El sitio que llame «Portada limpia» a la suya lo dice aquí, sin tocar
		 * el aplicativo.
		 *
		 * @param string                $encontrada Template file, '' for none.
		 * @param array<string, string> $plantillas Every template available to an event.
		 */
		return (string) apply_filters( 'evt_blank_page_template', $encontrada, $plantillas );
	}

	/**
	 * Give a brand new event —or section— the blank template of the theme.
	 *
	 * Va en `save_post` y no en la pantalla de alta: un evento entra por el
	 * taller, por el escritorio, por WP-CLI y por la migración, y así se
	 * resuelve una vez para todos. Solo escribe cuando la página todavía no
	 * tiene plantilla, así que quien elija otra después manda.
	 *
	 * @param int $post_id Event or section.
	 * @return void
	 */
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

	/**
	 * Custom capabilities for the CPT.
	 *
	 * No se declara `create_posts`: sin declararla, WordPress usa
	 * `edit_evt_events` para «Añadir nuevo», que es justo lo que tiene el rol
	 * de organización. Declarar una capacidad que nadie tiene es como se cierra
	 * el botón de crear sin querer.
	 *
	 * @return array<string, string>
	 */
	public static function capabilities(): array {
		return self::cap_map( 'evt_event', 'evt_events' );
	}

	/**
	 * Build the WordPress cap map for a singular/plural pair.
	 *
	 * @param string $one  Singular cap base.
	 * @param string $many Plural cap base.
	 * @return array<string, string>
	 */
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

	/**
	 * Grant the CPT caps of the three content types to the two roles.
	 *
	 * Idempotente y aditiva: crea lo que falta y nunca quita nada, para que lo
	 * que se conceda a mano en WPFront siga ahí en la siguiente carga.
	 *
	 * @return void
	 */
	public static function grant_caps_to_roles(): void {
		$maps = array(
			self::POST_TYPE                 => self::capabilities(),
			SpeakerPostType::POST_TYPE      => SpeakerPostType::capabilities(),
			ActivityPostType::POST_TYPE     => ActivityPostType::capabilities(),
			RegistrationPostType::POST_TYPE => RegistrationPostType::capabilities(),
		);

		$every = array_keys( self::capabilities() );

		// Del evento, la organización de un área publica y retira lo suyo y lo
		// de sus compañeras, pero no lo privado ni lo de otra persona: esas tres
		// se le reservan a la administración. Lo que la acota al área es
		// EventAccess, no la capacidad.
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

		// De los ponentes y de las actividades, en cambio, se lleva el juego
		// entero: un área gestiona su evento entero, y eso incluye dar de alta
		// a quien participa y montar el programa sin pedir permiso a nadie. El
		// acotado por área lo pone igualmente EventAccess.
		// De las inscripciones, el área lee y borra las de sus eventos —alguien
		// se da de baja, o pide que se le borre— pero no las publica: no las
		// escribe una persona, las escribe el aplicativo cuando alguien se
		// inscribe (ADR-0032).
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

	/**
	 * The two code capabilities: the CSS also to the área, the JavaScript never.
	 *
	 * Fuera del bucle de arriba a propósito: ese reparte las capacidades de los
	 * tipos de contenido, y estas dos no son de ningún tipo de contenido.
	 *
	 * La raya está donde el riesgo cambia de naturaleza: el CSS cambia **cómo
	 * se ve** una página, y quien organiza la jornada es quien sabe cómo tiene
	 * que verse; el JavaScript **ejecuta código** en el navegador de cada
	 * visitante, que es el poder que WordPress protege con `unfiltered_html` y
	 * el que devolvía sin acotar, a quien ya la tuviera en su rol, un fragmento del
	 * sistema anterior. Por eso son dos capacidades distintas y no
	 * una, que fue el acierto de la ADR-0014.
	 *
	 * Tener el CSS no abre el evento de al lado: el acotado por área lo sigue
	 * poniendo el guardián ({@see \Evt\Access\EventAccess::can_edit_custom_css()}),
	 * que exige las dos cosas.
	 *
	 * @return void
	 */
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

	/**
	 * The capabilities that guard the custom CSS and JavaScript fields.
	 *
	 * @return string[]
	 */
	public static function code_caps(): array {
		return array( EventAccess::CAP_CUSTOM_CSS, EventAccess::CAP_CUSTOM_JS );
	}

	/**
	 * The code capability that stays with administration, and only with it.
	 *
	 * Escrita aparte para que la comprueben las pantallas, los tests y el
	 * diagnóstico sin repetir la cadena en cada sitio.
	 *
	 * @return string[]
	 */
	public static function admin_only_code_caps(): array {
		return array( EventAccess::CAP_CUSTOM_JS );
	}
}

// ---- src/Evt/PostType/SpeakerPostType.php ----
/**
 * Register the evt_speaker custom post type.
 *
 * @package Evt
 */

namespace Evt\PostType;

use Evt\Taxonomy\EventTaxonomies;

/**
 * CPT registration for speakers.
 *
 * Es un tipo propio y no una página hija del evento porque la misma persona
 * comunicadora vuelve en varias ediciones: hoy son 483 entradas del formulario
 * 7 repetidas evento a evento.
 */
final class SpeakerPostType {

	public const POST_TYPE = 'evt_speaker';

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
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
				// Sin URL propia: al público se le enseña dentro de la página de
				// ponentes del evento, no en una ficha suelta.
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'edit.php?post_type=' . EventPostType::POST_TYPE,
				'supports'        => array( 'title', 'editor', 'thumbnail', 'author' ),
				'has_archive'     => false,
				// El área es el eje de permisos y aquí también: un ponente lleva
				// su término (o varios, que es reutilizarlo) y de ahí sale
				// quién puede tocarlo ({@see \Evt\Access\EventAccess}).
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

	/**
	 * Custom capabilities for the CPT.
	 *
	 * @return array<string, string>
	 */
	public static function capabilities(): array {
		return EventPostType::cap_map( 'evt_speaker', 'evt_speakers' );
	}
}

// ---- src/Evt/PostType/ActivityPostType.php ----
/**
 * Register the evt_activity custom post type.
 *
 * @package Evt
 */

namespace Evt\PostType;

use Evt\Taxonomy\EventTaxonomies;

/**
 * CPT registration for programme activities.
 *
 * Una actividad es lo que ocupa una franja del programa: ponencia, mesa
 * redonda, comunicación o taller. Hoy son entradas de un formulario del
 * sistema anterior.
 */
final class ActivityPostType {

	public const POST_TYPE = 'evt_activity';

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
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
				// Sin URL propia: se ven en la parrilla del programa del evento.
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'edit.php?post_type=' . EventPostType::POST_TYPE,
				'supports'        => array( 'title', 'editor', 'author' ),
				'has_archive'     => false,
				// El área es el eje de permisos y aquí también: una actividad lleva
				// su término (o varios, que es reutilizarla) y de ahí sale
				// quién puede tocarla ({@see \Evt\Access\EventAccess}).
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

	/**
	 * Custom capabilities for the CPT.
	 *
	 * @return array<string, string>
	 */
	public static function capabilities(): array {
		return EventPostType::cap_map( 'evt_activity', 'evt_activities' );
	}
}

// ---- src/Evt/PostType/RegistrationPostType.php ----
/**
 * Register the evt_registration custom post type.
 *
 * @package Evt
 */

namespace Evt\PostType;

/**
 * CPT registration for signups.
 *
 * Una inscripción cuelga de su evento por `post_parent`, como el ponente y la
 * actividad (ADR-0026): de ahí salen solos el área a la que pertenece, el
 * guardián que la acota y el cierre por histórico.
 *
 * **Se registra cerrado, y eso es la mitad de la decisión** (ADR-0032). Sin
 * escritorio, sin REST, sin búsqueda, sin URL y sin archivo. No es esconder:
 * esconder no protege, y la regla de la casa es que lo que protege es
 * `map_meta_cap`. Es **no abrir**: cada puerta que no se abre es una puerta que
 * no hay que guardar después, y aquí dentro hay datos personales de gente que
 * no trabaja aquí.
 *
 * El `post_title` es la **referencia** de la inscripción, no el nombre de la
 * persona. Es la diferencia entre un dato que sale en cualquier listado que
 * recorra `wp_posts` y uno al que hay que ir a buscar.
 */
final class RegistrationPostType {

	public const POST_TYPE = 'evt_registration';

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
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

	/**
	 * Custom capabilities for the CPT.
	 *
	 * @return array<string, string>
	 */
	public static function capabilities(): array {
		return EventPostType::cap_map( 'evt_registration', 'evt_registrations' );
	}
}

// ---- src/Evt/Meta/RegistrationMetaRegistration.php ----
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

// ---- src/Evt/Taxonomy/EventTaxonomies.php ----
/**
 * Register the three event taxonomies.
 *
 * @package Evt
 */

namespace Evt\Taxonomy;

use Evt\Access\EventAccess;
use Evt\PostType\EventPostType;

/**
 * Las tres taxonomías que sustituyen a la única `convocatoria` de hoy.
 *
 * `convocatoria` mezcla cuatro ejes —área, tipología, curso y estado— en 50
 * términos, y lo único que los separa son cuatro listas de exclusión de IDs
 * mantenidas a mano en el sistema anterior. Aquí cada eje es su propia
 * taxonomía, y el estado ni siquiera es una: lo calcula `EventState`.
 *
 * Las capacidades son las que existen de verdad: `evt_manage_app`, que crea el
 * snippet de roles, y `edit_evt_events`, que sale del CPT. El código heredado
 * exige capacidades que no existen en el sitio, y por eso nadie puede
 * administrar los términos.
 */
final class EventTaxonomies {

	/**
	 * Área, servicio o dirección general que organiza. Es el eje de permisos.
	 */
	public const AREA = 'evt_area';

	/**
	 * Tipología: jornadas, encuentro, congreso, taller.
	 */
	public const TYPE = 'evt_type';

	/**
	 * Curso escolar (2025-2026…).
	 */
	public const COURSE = 'evt_course';

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		register_taxonomy( self::AREA, EventPostType::POST_TYPE, self::args( 'Áreas organizadoras', 'Área organizadora' ) );
		register_taxonomy( self::TYPE, EventPostType::POST_TYPE, self::args( 'Tipologías', 'Tipología' ) );
		register_taxonomy( self::COURSE, EventPostType::POST_TYPE, self::args( 'Cursos escolares', 'Curso escolar' ) );
	}

	/**
	 * Shared taxonomy arguments.
	 *
	 * Las tres son jerárquicas para que salgan como casillas y no como campo
	 * libre: un vocabulario cerrado no se amplía por una errata al teclear.
	 *
	 * @param string $plural   Plural label.
	 * @param string $singular Singular label.
	 * @return array<string, mixed>
	 */
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

// ---- src/Evt/PublicFront/Assets.php ----
/**
 * Front-end asset registration for the events application.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

/**
 * La hoja y el guion del aplicativo, en línea.
 *
 * El único artefacto de producción es el bundle de Code Snippets: no hay
 * repositorio en disco ni URL de plugin desde la que servir un fichero, así
 * que el CSS y el JS viajan dentro del propio bundle como contenido en línea.
 * En desarrollo y en tests se leen de `assets/`, que sí está ahí.
 */
final class Assets {

	/**
	 * Asset contents inlined by the bundler, keyed by path relative to assets/.
	 *
	 * @var array<string, string>
	 */
	private static $inline = array();

	/**
	 * Receive the asset contents that `build/pack-snippet.php` inlined.
	 *
	 * @param array<string, string> $assets Map of path relative to assets/ => contents.
	 * @return void
	 */
	public static function set_inline( array $assets ): void {
		self::$inline = $assets;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		// Pronto: un tema de bloques pinta el shortcode antes de `wp_enqueue_scripts`.
		add_action( 'init', array( self::class, 'register_assets' ), 20 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_app' ), 100 );
		add_filter( 'body_class', array( self::class, 'body_class' ) );
	}

	/**
	 * Register (not always enqueue) our two assets. Idempotent.
	 *
	 * @return void
	 */
	public static function register_assets(): void {
		if ( wp_style_is( 'evt-app', 'registered' ) ) {
			return;
		}

		// phpcs:disable WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Inline-only handles have no URL to version.
		wp_register_style( 'evt-app', false, array(), null );
		wp_add_inline_style( 'evt-app', self::contents( 'css/evt-app.css' ) );

		wp_register_script( 'evt-app', false, array(), null, true );
		wp_add_inline_script( 'evt-app', self::contents( 'js/evt-app.js' ) );
		// phpcs:enable WordPress.WP.EnqueuedResourceParameters.MissingVersion
	}

	/**
	 * Enqueue the stylesheet and the script on our own pages.
	 *
	 * El shortcode llega después del encabezado: esperar a él repinta la
	 * página. Se encola en `wp_enqueue_scripts`, y {@see enqueue()} queda como
	 * respaldo para quien llame al shortcode desde otro sitio.
	 *
	 * @return void
	 */
	public static function enqueue_app(): void {
		if ( Shell::is_app_page() ) {
			self::enqueue();
		}
	}

	/**
	 * Enqueue the stylesheet and the script, registering them if needed.
	 *
	 * @return void
	 */
	public static function enqueue(): void {
		self::register_assets();
		wp_enqueue_style( 'evt-app' );
		wp_enqueue_script( 'evt-app' );
	}

	/**
	 * Whether Bootstrap 5 already styles the page.
	 *
	 * El subsitio de eventos carga **Bootstrap 4** en todas sus páginas desde
	 * un snippet antiguo, con el handle `bootstrap-css`. Así que mirar la cola
	 * de estilos era adivinar: un handle llamado «bootstrap» no dice de qué
	 * versión es, y dar por buena la 4 pintaría el aplicativo suponiendo una
	 * rejilla y unas utilidades que no están —bien en local, roto al
	 * desplegar—.
	 *
	 * La verdad la pone quien la conoce: `snippets/bootstrap5.php` fija este
	 * filtro cuando de verdad ha encolado Bootstrap 5 en esta página. Si no
	 * está, nuestra hoja pinta botones y pastillas decentes por su cuenta.
	 *
	 * @return bool
	 */
	public static function has_bootstrap(): bool {
		/**
		 * Filter whether Bootstrap 5 styles the page.
		 *
		 * @param bool $present Whether Bootstrap 5 was loaded for this page.
		 */
		return (bool) apply_filters( 'evt_has_bootstrap', false );
	}

	/**
	 * Mark the page when Bootstrap is absent, so our own skin applies.
	 *
	 * Una clase en el `body` y no un truco de orden de carga: con las dos hojas
	 * en juego, quién gana depende del orden de encolado, que no controlamos.
	 *
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public static function body_class( array $classes ): array {
		if ( ! self::has_bootstrap() ) {
			$classes[] = 'evt-sin-bootstrap';
		}
		return $classes;
	}

	/**
	 * Classes for a page-level notice.
	 *
	 * @param string $tono info|warning|success|danger.
	 * @return string
	 */
	public static function alert_class( string $tono = 'info' ): string {
		$tonos = array( 'info', 'warning', 'success', 'danger' );
		$tono  = in_array( $tono, $tonos, true ) ? $tono : 'info';
		return sprintf( 'evt-aviso evt-aviso-%1$s alert alert-%1$s', $tono );
	}

	/**
	 * Classes for a button or a button-styled link.
	 *
	 * Los dos vocabularios a la vez: `evt-btn` para nuestro CSS y las de
	 * Bootstrap para que el botón sea el del resto del subsitio. Sin Bootstrap,
	 * las clases sobrantes no hacen nada.
	 *
	 * @param bool $primary Whether this is the primary action.
	 * @return string
	 */
	public static function button_class( bool $primary = false ): string {
		return $primary
			? 'evt-btn evt-btn-primary btn btn-primary'
			: 'evt-btn btn btn-light';
	}

	/**
	 * Classes for the chip of a derived event state.
	 *
	 * Sin `text-bg-*` de Bootstrap: los tres estados son una línea de tiempo
	 * —próximo, abierto, finalizado— y el color lo ponen nuestras variables.
	 *
	 * @param string $estado One of EventMetaKeys::states().
	 * @return string
	 */
	public static function state_class( string $estado ): string {
		return sprintf(
			'evt-state evt-state-%s',
			sanitize_html_class( '' !== $estado ? $estado : 'na' )
		);
	}

	/**
	 * Contents of one asset: inlined by the bundler, or read from the repo.
	 *
	 * Pública porque la hoja de la página pública del evento
	 * (`css/evt-evento.css`) no se encola: se escribe en la cabecera para que el
	 * CSS a medida del evento pueda pisarla, y en producción el único artefacto
	 * es el bundle, donde no hay repositorio en disco del que leerla.
	 *
	 * @param string $rel Path relative to assets/, e.g. `js/evt-app.js`.
	 * @return string Empty when neither source is available.
	 */
	public static function contents( string $rel ): string {
		if ( isset( self::$inline[ $rel ] ) ) {
			return self::$inline[ $rel ];
		}

		$path = dirname( __DIR__, 3 ) . '/assets/' . $rel;
		if ( ! is_readable( $path ) ) {
			return '';
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local asset, not a remote request.
		return (string) file_get_contents( $path );
	}
}

// ---- src/Evt/PublicFront/ExitSignal.php ----
/**
 * Thrown instead of exit() under tests: says where the request was going.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

/**
 * Lo que Shell::leave() lanza cuando el filtro `evt_exit_throws` está puesto
 * —solo en tests—. Lleva la URL de vuelta, o nada si lo que se acaba de servir
 * es un documento.
 */
final class ExitSignal extends \RuntimeException {

	/**
	 * Where the request was being sent; empty after serving a document.
	 *
	 * @var string
	 */
	public $url = '';

	/**
	 * Constructor.
	 *
	 * @param string $url Return URL, or empty.
	 */
	public function __construct( string $url = '' ) {
		parent::__construct( '' === $url ? 'Salida sin redirección' : 'Redirección a ' . $url );
		$this->url = $url;
	}
}

// ---- src/Evt/PublicFront/Shell.php ----
/**
 * Chrome shared by every front-end page of the events application.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

use Evt\Access\EventAccess;
use Evt\Admin\Settings;
use Evt\PostType\EventPostType;
use Evt\Taxonomy\EventTaxonomies;

/**
 * La cabecera, las pestañas y el pie que comparten todas las pantallas.
 *
 * El aplicativo se pinta entero él mismo: cabecera con el escudo, una barra de
 * pestañas con lo que esa persona puede hacer y un pie de una línea. Sin barra
 * lateral —con dos secciones roba ancho y no agrupa nada— y sin depender del
 * tema: aquí el tema es el tema, y su cabecera, su pie y sus hojas no visten nada
 * nuestro, solo pesan.
 *
 * Una sola lista de secciones ({@see sections()}) la usan la portada, las
 * pestañas y los botones: si mañana hay una sección más, se añade en un sitio.
 */
final class Shell {

	/**
	 * Slugs de las páginas, por sección. En castellano, como las URL de hoy.
	 *
	 * @var array<string, string>
	 */
	public const SLUGS = array(
		'home'     => 'eventos-gestion',
		'events'   => 'mis-eventos',
		'event'    => 'evento',
		'section'  => 'seccion',
		'speakers' => 'ponentes-evento',
	);

	/**
	 * Shortcode que pinta cada pantalla, por sección.
	 *
	 * Se mira el shortcode y no el slug para saber si estamos en una página del
	 * aplicativo: las páginas las crea quien despliega y su dirección puede
	 * cambiar; el shortcode no. Y son cadenas, no clases: así el armazón no
	 * depende de que las pantallas estén cargadas.
	 *
	 * @var array<string, string>
	 */
	public const SHORTCODES = array(
		'home'     => 'evt_home',
		'events'   => 'evt_event_list',
		'event'    => 'evt_event_workspace',
		'section'  => 'evt_page_form',
		'speakers' => 'evt_speaker_list',
	);

	/**
	 * Hojas de WordPress que ninguna pantalla del aplicativo usa.
	 *
	 * @var string[]
	 */
	private const CORE_ASSETS = array(
		'wp-block-library',
		'wp-block-library-theme',
		'global-styles',
		'classic-theme-styles',
		'wp-emoji-styles',
	);

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'body_class', array( self::class, 'body_class' ) );
		add_filter( 'show_admin_bar', array( self::class, 'show_admin_bar' ), 100 );
		// Antes de pintar nada: en `template_redirect` aún se pueden mandar
		// cabeceras, y dentro del shortcode ya no.
		add_action( 'template_redirect', array( self::class, 'require_login' ) );
		// Después de mandar al acceso a quien no ha entrado, y antes de que el
		// tema empiece a pintar.
		add_action( 'template_redirect', array( self::class, 'render_standalone' ), 20 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'drop_theme_assets' ), 100 );
		// Y una red por si algo se encola más tarde: el tema imprime una hoja
		// «late» en el pie, y al escribir la etiqueta se descarta.
		add_filter( 'style_loader_tag', array( self::class, 'drop_theme_tag' ), 10, 3 );
		add_filter( 'script_loader_tag', array( self::class, 'drop_theme_tag' ), 10, 3 );
	}

	/**
	 * Send anonymous visitors of any application page to the login form.
	 *
	 * Todas las pantallas son de alguien. Sin sesión, lo que se pintaba era la
	 * página del tema con un «Debe iniciar sesión» dentro; ahora se va al
	 * formulario de acceso y se vuelve aquí. El aviso se queda en el modelo de
	 * cada pantalla: esa es la guarda de verdad, y sigue respondiendo a quien
	 * llame al shortcode por su cuenta.
	 *
	 * @return void
	 */
	public static function require_login(): void {
		if ( is_user_logged_in() || ! self::is_app_page() ) {
			return;
		}
		// Solo el permalink: los filtros de la consulta no se arrastran hasta el
		// formulario de acceso, y volver a la pantalla ya es lo que hace falta.
		$destino = (string) get_permalink();
		self::leave( wp_login_url( '' !== $destino ? $destino : home_url( '/' ) ) );
	}

	/**
	 * Las pantallas del aplicativo se sirven solas, sin la plantilla del tema.
	 *
	 * El tema no pinta nada nuestro: la cabecera, las pestañas y el pie son del
	 * aplicativo, y lo único que ponía el tema era su propia cabecera, su pie y
	 * una columna de contenido que había que deshacer a golpe de CSS.
	 *
	 * Se imprime el documento entero —`wp_head()` y `wp_footer()` incluidos, que
	 * son los que traen la barra de administración y lo que encolamos— y se sale
	 * por {@see leave()}, que en tests lanza su excepción en vez de terminar.
	 *
	 * @return void
	 */
	public static function render_standalone(): void {
		/**
		 * Filter whether the application renders its own page, without the theme.
		 *
		 * @param bool $solo Whether to bypass the theme template.
		 */
		if ( ! apply_filters( 'evt_standalone_page', true ) || ! self::is_app_page() ) {
			return;
		}

		// `is_app_page()` ya ha comprobado que hay una entrada singular.
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
		// El tema declara `title-tag` y `wp_head()` escribe el título; sin esa
		// declaración —o sin tema que la haga— lo escribimos nosotros.
		if ( ! current_theme_supports( 'title-tag' ) ) {
			echo '<title>' . esc_html( wp_get_document_title() ) . '</title>';
		}
		wp_head();
		?>
</head>
<body <?php body_class(); ?>>
		<?php
		wp_body_open();
		echo apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- el contenido de la página, filtrado como lo haría el tema.
		wp_footer();
		?>
</body>
</html>
		<?php
		echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- documento montado arriba.
		self::leave();
	}

	/**
	 * En nuestras páginas no se escribe ninguna etiqueta del tema.
	 *
	 * @param string $tag    The tag WordPress is about to print.
	 * @param string $handle Its handle.
	 * @param string $src    Its URL.
	 * @return string
	 */
	public static function drop_theme_tag( string $tag, string $handle, string $src ): string {
		unset( $handle );
		if ( ! apply_filters( 'evt_standalone_page', true ) || ! self::is_app_page() ) {
			return $tag;
		}
		return self::is_theme_asset( $src ) ? '' : $tag;
	}

	/**
	 * Lo del tema, fuera de la cola.
	 *
	 * @return void
	 */
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

		// Y lo que trae WordPress para lo que aquí no hay: el contenido de la
		// página es un shortcode, no hay bloques que vestir ni ajustes de tema
		// global que aplicar.
		foreach ( self::CORE_ASSETS as $handle ) {
			wp_dequeue_style( $handle );
		}
		// El detector de emoji son trece kilobytes de guion en línea para
		// sustituir caritas que no salen en ninguna pantalla.
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
	}

	/**
	 * Si un fichero viene del tema o de la caché de su constructor.
	 *
	 * Por la ruta y no por una lista de nombres, que cambiaría con cada versión
	 * del tema (`/et-cache/` es la suya).
	 *
	 * @param string $src Its URL.
	 * @return bool
	 */
	private static function is_theme_asset( string $src ): bool {
		return '' !== $src
			&& ( false !== strpos( $src, '/themes/' ) || false !== strpos( $src, '/et-cache/' ) );
	}

	/**
	 * Show the front-end toolbar only for administrators, including switched sessions.
	 *
	 * Cuando administración se cambia a otra persona con WPFront User Role
	 * Editor para comprobar qué ve, la sesión pasa a ser la de esa persona y la
	 * barra se iba con ella: sin barra no hay «Volver a mi cuenta», y había que
	 * salir a mano borrando la cookie. WPFront no expone su pila de
	 * suplantación, así que se lee su propia cookie, se descifra con su propia
	 * utilidad y se comprueba lo mismo que comprueba él: que la pila es de este
	 * sitio, que no ha pasado de sus 12 horas y quién empezó el cambio.
	 *
	 * Esto decide SOLO si se pinta la barra. No concede ninguna capacidad a la
	 * persona suplantada: sigue pudiendo exactamente lo suyo, que es justo lo
	 * que se está yendo a comprobar al suplantarla.
	 *
	 * Sin el plugin instalado su clase no existe: se responde que no y ya.
	 *
	 * @return bool
	 */
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
		// Descifrada, la pila es «COOKIEHASH-momento-usuarios-remember»; si no
		// tiene esa forma, no es nuestra y no se mira más.
		$sesion = explode( '-', (string) \WPFront\URE\WPFront_User_Role_Editor_Utils::decrypt( $cookie ), 4 );
		if ( count( $sesion ) < 3 || COOKIEHASH !== $sesion[0] || ! ctype_digit( $sesion[1] ) ) {
			return false;
		}
		$edad = time() - (int) $sesion[1];
		if ( $edad < 0 || $edad > 12 * HOUR_IN_SECONDS ) {
			return false;
		}
		// El primero de la pila es quien empezó el cambio: la barra es suya.
		$usuarios = explode( ',', $sesion[2] );
		return ctype_digit( $usuarios[0] ) && user_can( (int) $usuarios[0], 'manage_options' );
	}

	/**
	 * Mark the pages of the application, so the theme chrome can step aside.
	 *
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public static function body_class( array $classes ): array {
		if ( self::is_app_page() ) {
			$classes[] = 'evt-app';
		}
		return $classes;
	}

	/**
	 * Whether the post being viewed carries one of our shortcodes.
	 *
	 * @return bool
	 */
	public static function is_app_page(): bool {
		return '' !== self::current_section();
	}

	/**
	 * Which section is being viewed, by the shortcode the page carries.
	 *
	 * @return string Section key, or empty outside the application.
	 */
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

	/**
	 * URL of one of our pages, or empty when it does not exist.
	 *
	 * @param string               $section Section key, one of SLUGS.
	 * @param array<string, mixed> $args    Query arguments to append.
	 * @return string
	 */
	public static function url( string $section, array $args = array() ): string {
		$slug = self::SLUGS[ $section ] ?? '';
		if ( '' === $slug ) {
			return '';
		}

		/**
		 * Filter the page slug of one section.
		 *
		 * Quien despliega crea las páginas y les pone la dirección que quiera
		 * —o las cuelga de una madre, con la ruta entera—; esto permite
		 * reapuntarlas sin tocar el código.
		 *
		 * @param string $slug    Page path.
		 * @param string $section Section key.
		 */
		$slug = (string) apply_filters( 'evt_page_slug', $slug, $section );

		$page = get_page_by_path( $slug );
		if ( ! $page ) {
			return '';
		}
		$url = (string) get_permalink( $page );
		return array() === $args ? $url : add_query_arg( $args, $url );
	}

	/**
	 * Sections of whoever is looking, and where each one goes.
	 *
	 * Solo lo que se puede abrir sin contexto: el taller de un evento, el
	 * formulario de una sección y los ponentes piden un `?evento=<id>` y son
	 * destinos de un botón, no pestañas.
	 *
	 * El recuento (`badge`) va a `null` a propósito: la única cifra que
	 * importa —cuántos eventos hay en borrador— es una de las fichas de
	 * recuento de `EventList`, y ponerla también aquí es una consulta más en
	 * cada pantalla para repetir un número que ya está en la que se abre.
	 *
	 * @param int $user_id User ID (0 = current).
	 * @return array<string, array{label:string, url:string, badge:?int}>
	 */
	public static function sections( int $user_id = 0 ): array {
		// Ojo: **una sola sección, y a propósito**. «Ajustes» estuvo aquí y no
		// era hermana de «Eventos»: es administración del aplicativo, vive en
		// el escritorio de WordPress y solo la ve quien administra. Como
		// pestaña dejaba a administración con una barra de dos —una de ellas
		// saltando fuera— y a la portada con una barra sin nada activo, que es
		// una barra que no elige nada. Ahora está en el menú de la cuenta.
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

		// Una sección cuya página no existe todavía no se ofrece: una pestaña
		// que lleva a un 404 es peor que no tenerla.
		foreach ( $out as $clave => $seccion ) {
			if ( '' === $seccion['url'] ) {
				unset( $out[ $clave ] );
			}
		}

		return $out;
	}

	/**
	 * Whether this person organises events at all.
	 *
	 * Falla en cerrado, como `EventAccess`: sin área y sin
	 * `evt_edit_all_areas`, no hay nada que enseñar.
	 *
	 * @param int $user_id User ID (0 = current).
	 * @return bool
	 */
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

	/**
	 * Who is looking: role and área, for the header.
	 *
	 * Dos líneas y no un rótulo: en un aplicativo acotado por área, saber con
	 * qué área se está mirando es la mitad de la respuesta a «¿por qué no veo
	 * este evento?».
	 *
	 * @param int $user_id User ID (0 = current).
	 * @return array{cargo:string, area:string} Empty strings when there is no role.
	 */
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

	/**
	 * The áreas of a person, written out.
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
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

	/**
	 * Up to two initials of a display name, for the avatar.
	 *
	 * Se parte por lo que no es letra ni número, no por espacios: los nombres
	 * que se muestran traen el área entre paréntesis —«Organización (Innovación)»— y
	 * partiendo por espacios la segunda inicial salía «(».
	 *
	 * @param string $nombre Display name.
	 * @return string
	 */
	private static function initials( string $nombre ): string {
		$partes = preg_split( '/[^\p{L}\p{N}]+/u', trim( $nombre ), -1, PREG_SPLIT_NO_EMPTY );
		$partes = is_array( $partes ) ? $partes : array();
		$letras = '';
		foreach ( array_slice( $partes, 0, 2 ) as $parte ) {
			$letras .= mb_strtoupper( mb_substr( $parte, 0, 1 ) );
		}
		return $letras;
	}

	/**
	 * The whole page: header, tabs, the sheet the screen goes in, and the footer.
	 *
	 * @param string $title    Page heading.
	 * @param string $subtitle One line under the heading; empty for none.
	 * @param string $body     The screen, already escaped.
	 * @return string
	 */
	public static function render( string $title, string $subtitle, string $body ): string {
		/**
		 * Filter whether the application paints its own header and footer.
		 *
		 * @param bool $pintar Whether to render the chrome.
		 */
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
			<?php echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- la pantalla llega ya escapada. ?>
		</div>
		<?php
		return self::top() . self::tabs() . (string) ob_get_clean() . self::bottom();
	}

	/**
	 * The header: the badge, who owns it, «Eventos» and who is looking.
	 *
	 * El rótulo de la organización sale de la configuración del armazón
	 * ({@see \Evt\PublicFront\View\EventChrome::chrome()}) y **está vacío por
	 * defecto**: este aplicativo no lleva dentro la marca de nadie (ADR-0030).
	 *
	 * @return string
	 */
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

	/**
	 * The tab bar. With one single section there is nothing to choose from.
	 *
	 * @return string
	 */
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

	/**
	 * The one-line footer, always at the bottom.
	 *
	 * @return string
	 */
	private static function bottom(): string {
		/**
		 * Filter the links of the application footer.
		 *
		 * Son los del pie institucional del sitio, copiados aquí para que el pie
		 * sea uno y esté siempre abajo. Si cambian, se cambian con esto.
		 *
		 * @param array<string, string> $enlaces Rótulo => URL.
		 */
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

	/**
	 * The application entry page, or the site root when it does not exist yet.
	 *
	 * @return string
	 */
	private static function home_url(): string {
		$inicio = self::url( 'home' );
		return '' !== $inicio ? $inicio : home_url( '/' );
	}

	/**
	 * A one-line notice: what just happened, or why a screen is empty.
	 *
	 * @param string $type ok | aviso | error.
	 * @param string $text What to say, plain text.
	 * @return string
	 */
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

	/**
	 * Lo que se dice por defecto sobre por qué este recuadro solo lo ve una persona.
	 */
	public const ADMIN_BOX_WHY = 'Este recuadro solo lo ve quien administra el aplicativo. Ningún otro perfil lo ve ni puede cambiar lo que hay dentro.';

	/**
	 * The yellow box: what only the administration sees.
	 *
	 * Una convención del aplicativo, no un adorno de una pantalla: cualquier
	 * cosa que solo vea quien administra va aquí dentro, y así se reconoce a la
	 * primera sin leerla. Por eso vive en el armazón y se implementa una vez.
	 *
	 * El amarillo no es el único aviso —quien no distingue el color se
	 * quedaría sin él—: la etiqueta «Solo administración» va escrita, y debajo
	 * una línea que explica por qué.
	 *
	 * El cuerpo llega ya escapado, como en {@see render()}: quien lo pinta sabe
	 * si son campos, una tabla o un párrafo.
	 *
	 * @param string $titulo      Heading of the box.
	 * @param string $cuerpo      Its contents, already escaped.
	 * @param string $explicacion Why only this person sees it; the default one when empty.
	 * @return string
	 */
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
				<?php echo $cuerpo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- lo pinta quien llama, ya escapado. ?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Where to go back to after saving something.
	 *
	 * `wp_get_referer()` devuelve `false` justo cuando el referer coincide con
	 * la propia URL, que es siempre en un formulario con `action=""`. Se usa el
	 * referer crudo y, si no hay, una página del aplicativo: nunca la portada
	 * del sitio.
	 *
	 * @param string $section Section to fall back to.
	 * @return string
	 */
	public static function back_url( string $section = 'events' ): string {
		$destino = wp_validate_redirect( (string) wp_get_raw_referer(), '' );
		if ( '' !== $destino ) {
			return $destino;
		}
		$url = self::url( $section );
		return '' !== $url ? $url : home_url( '/' );
	}

	/**
	 * Send a response header, unless the response already started.
	 *
	 * En tests la salida ya empezó —PHPUnit escribe la suya— y `header()`
	 * avisa; aquí se salta, que es lo que hace `nocache_headers()` de
	 * WordPress. En producción se manda siempre.
	 *
	 * @param string $linea Header line, `Nombre: valor`.
	 * @return void
	 */
	public static function send_header( string $linea ): void {
		if ( ! headers_sent() ) {
			header( $linea );
		}
	}

	/**
	 * Leave the request: redirect if given a URL, then stop.
	 *
	 * El único `exit` del aplicativo. En tests, el filtro `evt_exit_throws` lo
	 * convierte en una excepción {@see ExitSignal} con la URL, que el test
	 * captura.
	 *
	 * @param string $url Where to go; empty when a document was just served.
	 * @return void
	 * @throws ExitSignal Under the tests filter, instead of leaving.
	 */
	public static function leave( string $url = '' ): void {
		if ( apply_filters( 'evt_exit_throws', false, $url ) ) {
			throw new ExitSignal( $url ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- la URL viaja para que el test la lea; no se imprime.
		}
		if ( '' !== $url ) {
			wp_safe_redirect( $url );
		}
		exit;
	}

	/**
	 * One of the app icons, inline.
	 *
	 * **En línea y no con la tipografía de iconos de Bootstrap** aunque esté
	 * cargada: el aplicativo tiene que seguir entendiéndose sin Bootstrap
	 * ({@see Assets::has_bootstrap()}), y un icono que no llega deja un botón
	 * sin nada dentro. Con `currentColor` heredan el color del botón.
	 *
	 * Van siempre con `aria-hidden`: lo que dice qué hace el botón es su texto,
	 * que va al lado en `.screen-reader-text` y en el `title`.
	 *
	 * @param string $nombre Icon name.
	 * @return string Empty when there is no such icon.
	 */
	public static function icon( string $nombre ): string {
		$caminos = array(
			// Lápiz: editar.
			'lapiz'     => '<path fill="currentColor" d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8.4 17.6l-3.9.9.9-3.9L16.5 3.5Z"/>',
			// Ojo: ver la página pública.
			'ojo'       => '<path fill="currentColor" d="M12 5c-5 0-9 4.5-9 7s4 7 9 7 9-4.5 9-7-4-7-9-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Zm0-2a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/>',
			// Papelera: enviar a la papelera.
			'papelera'  => '<path fill="currentColor" d="M9 3h6l1 2h4v2H4V5h4l1-2Zm-3 6h12l-1 11a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L6 9Zm4 2v9h1.5v-9H10Zm3.5 0v9H15v-9h-1.5Z"/>',
			// Flechas: subir y bajar una posición.
			'subir'     => '<path fill="currentColor" d="M12 4.5 18.5 11H14v8.5h-4V11H5.5L12 4.5Z"/>',
			'bajar'     => '<path fill="currentColor" d="M12 19.5 5.5 13H10V4.5h4V13h4.5L12 19.5Z"/>',
			// Flecha que vuelve: restaurar de la papelera.
			'restaurar' => '<path fill="currentColor" d="M12 5a7 7 0 1 1-6.7 9h2.2A4.8 4.8 0 1 0 12 7.2V10L7.5 6 12 2v3Z"/>',
		);
		if ( ! isset( $caminos[ $nombre ] ) ) {
			return '';
		}
		return '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">'
			. $caminos[ $nombre ] . '</svg>';
	}

	/**
	 * Plus sign, inline.
	 *
	 * @return string
	 */
	public static function icon_plus(): string {
		return '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">'
			. '<path fill="currentColor" d="M12 4a1 1 0 0 1 1 1v6h6a1 1 0 1 1 0 2h-6v6a1 1 0 1 1-2 0v-6H5a1 1 0 1 1 0-2h6V5a1 1 0 0 1 1-1Z"/>'
			. '</svg>';
	}
}

// ---- src/Evt/PublicFront/EditLock.php ----
/**
 * Native WordPress edit locks, shared by the application and wp-admin.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

use Evt\Access\EventAccess;

/**
 * Que dos personas no se pisen editando el mismo evento.
 *
 * No se inventa ningún bloqueo: se usa **el nativo de WordPress** —la meta
 * `_edit_lock`, `wp_check_post_lock()` y `wp_set_post_lock()`—, que es
 * exactamente el mismo que usa el escritorio. La ventaja es la razón de haberlo
 * elegido: si alguien abre el evento en el editor de WordPress y otra persona
 * en el taller del aplicativo, **se ven**. Un bloqueo propio no lo haría, y
 * habría dos verdades sobre quién está editando.
 *
 * El bloqueo es **del evento raíz**, no de la página que se tenga abierta: dos
 * personas tocando dos secciones distintas del mismo evento se pisan igual,
 * porque comparten la navegación, el orden y la apariencia. Por eso todo pasa
 * por {@see EventAccess::root_id()} y quien pregunta no tiene que acordarse.
 *
 * Las cuatro piezas:
 *
 * | {@see owner()}             | quién lo tiene, sin caducar               |
 * | {@see require_available()} | antes de escribir nada: si hay dueño, 409 |
 * | {@see claim()}             | tomarlo al abrir, o decir quién lo tiene  |
 * | {@see release()}           | soltar **solo el propio**                 |
 *
 * Y el aviso ({@see render()}), que dice el nombre de quien lo tiene —no
 * «alguien»— y ofrece dos salidas: consultarlo, o tomar posesión por POST con
 * su nonce.
 */
final class EditLock {

	/**
	 * Hidden field naming the operation, so nothing else picks the POST up.
	 */
	public const FIELD_DO = 'evt_lock_do';

	/**
	 * Hidden field with the post the takeover is about.
	 */
	public const FIELD_POST = 'evt_lock_post';

	/**
	 * Nonce field of the takeover form.
	 */
	public const NONCE_FIELD = 'evt_lock_nonce';

	/**
	 * The only operation this class accepts.
	 */
	public const OP_TAKEOVER = 'takeover';

	/**
	 * Nonce action of the takeover of one event.
	 *
	 * @param int $event_id Event post ID.
	 * @return string
	 */
	public static function nonce_action( int $event_id ): string {
		return 'evt_lock_takeover_' . $event_id;
	}

	/**
	 * Who else holds an unexpired lock on the event this post belongs to.
	 *
	 * `wp_check_post_lock()` contesta «otra persona», así que a quien tiene el
	 * bloqueo le dice que no hay ninguno: es justo lo que hace falta aquí.
	 *
	 * @param int $post_id Event, or any of its satellite pages.
	 * @return int User ID, 0 when the event is free.
	 */
	public static function owner( int $post_id ): int {
		$event_id = EventAccess::root_id( $post_id );
		if ( $event_id <= 0 ) {
			return 0;
		}
		require_once ABSPATH . 'wp-admin/includes/post.php';
		return (int) wp_check_post_lock( $event_id );
	}

	/**
	 * Reject the write before a single field, term or status can change.
	 *
	 * Va **antes** de escribir y no después, que es lo único que sirve: quien
	 * perdió el bloqueo tiene la pantalla vieja delante y su envío traería
	 * catorce campos con lo de hace media hora.
	 *
	 * @param int $post_id Event, or any of its satellite pages.
	 * @return void
	 */
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

	/**
	 * How the event stands right now: libre, o de quién es. No escribe nada.
	 *
	 * Esto es lo que llama el `model()` de cada pantalla, y por eso no toma el
	 * bloqueo: el modelo decide qué se pinta y no muta.
	 * Tomarlo es de {@see claim()}, que va en el `render()`, cuando la pantalla
	 * se le está enseñando a alguien de verdad.
	 *
	 * A quien solo puede consultar —un evento marcado como histórico, por
	 * ejemplo— no se le cuenta ningún bloqueo: no va a escribir, así que ni le
	 * estorba ni tiene por qué quitárselo a quien sí puede.
	 *
	 * @param int  $post_id  Event, or any of its satellite pages.
	 * @param bool $can_edit Whether this person may write here at all.
	 * @return array{event_id:int, owner:int, name:string, lock:string}
	 */
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
			// Lo que el Heartbeat renueva; lo rellena claim() y solo para quien
			// tenga el bloqueo, porque renovar uno ajeno no tendría sentido.
			'lock'     => '',
		);
	}

	/**
	 * Take the lock of a free event, so the next person sees it taken.
	 *
	 * Es lo mismo que hace el escritorio al abrir el editor, y es el mismo
	 * bloqueo: por eso las dos pantallas se ven.
	 *
	 * @param array{event_id:int, owner:int, name:string, lock:string} $lock What status() returned.
	 * @return array{event_id:int, owner:int, name:string, lock:string}
	 */
	public static function claim( array $lock ): array {
		if ( (int) $lock['event_id'] <= 0 || (int) $lock['owner'] > 0 ) {
			return $lock;
		}
		require_once ABSPATH . 'wp-admin/includes/post.php';
		$puesto       = wp_set_post_lock( (int) $lock['event_id'] );
		$lock['lock'] = is_array( $puesto ) ? implode( ':', $puesto ) : '';

		return $lock;
	}

	/**
	 * No lock to talk about: nothing to paint and nothing to renew.
	 *
	 * @return array{event_id:int, owner:int, name:string, lock:string}
	 */
	public static function none(): array {
		return array(
			'event_id' => 0,
			'owner'    => 0,
			'name'     => '',
			'lock'     => '',
		);
	}

	/**
	 * Release the lock, and only when it still is this person's own.
	 *
	 * Se compara el usuario que trae la meta antes de borrarla: si mientras
	 * tanto otra persona tomó posesión, el bloqueo es suyo y no se le quita.
	 * Se le pasa el valor exacto a `delete_post_meta()` por lo mismo, que es lo
	 * que hace la comprobación atómica en la base de datos.
	 *
	 * @param int $post_id Event, or any of its satellite pages.
	 * @return void
	 */
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

	/**
	 * Apply a «Tomar posesión» POST.
	 *
	 * Por POST y con su nonce, como toda mutación del aplicativo: tomarle el
	 * evento a otra persona por un enlace pegado en un correo no pasa. Y con
	 * `EventAccess::can_edit()` comprobado: el nonce dice que el envío salió de
	 * nuestra pantalla, no que quien lo manda pueda editar este evento.
	 *
	 * Lo engancha {@see EventWorkspace::register()}, que es de donde cuelga el
	 * resto de las mutaciones del taller.
	 *
	 * @return void
	 */
	public static function handle(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- se comprueba abajo, en cuanto se sabe de qué evento es.
		$op = sanitize_key( wp_unslash( (string) ( $_POST[ self::FIELD_DO ] ?? '' ) ) );
		if ( self::OP_TAKEOVER !== $op ) {
			return;
		}
		$post_id = absint( wp_unslash( $_POST[ self::FIELD_POST ] ?? 0 ) );
		$nonce   = sanitize_text_field( wp_unslash( (string) ( $_POST[ self::NONCE_FIELD ] ?? '' ) ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

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

		// A la misma pantalla desde la que se pidió: el aviso sale tanto en el
		// taller como en el formulario de una sección, y desde los dos se
		// vuelve a lo que se estaba haciendo.
		Shell::leave( Shell::back_url( 'events' ) );
	}

	/**
	 * The takeover notice: who has the event, and the two ways out.
	 *
	 * Un `<dialog>` nativo y no SweetAlert2, y la diferencia importa: esto no
	 * es una confirmación —lo de SweetAlert2 en este aplicativo—, es el estado
	 * de la pantalla, y tiene que verse **también sin JavaScript**. Un
	 * `<dialog open>` lo pinta el navegador solo; SweetAlert2 no existe hasta
	 * que carga su guion, y su degradación es el `confirm()` del navegador, que
	 * aquí sería un modal bloqueante sin salida. El mismo `<dialog>` es el que
	 * el Heartbeat abre con `showModal()` cuando el bloqueo se pierde sin
	 * recargar.
	 *
	 * @param array{event_id:int, owner:int, name:string, lock:string} $lock What claim() returned.
	 * @return string Empty when there is no event to lock.
	 */
	public static function render( array $lock ): string {
		$event_id = (int) $lock['event_id'];
		if ( $event_id <= 0 ) {
			return '';
		}
		$owner = (int) $lock['owner'];

		// El guion del bloqueo vive en `assets/js/evt-app.js` y necesita el
		// Heartbeat del núcleo, que no se encola en la web pública.
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

	/**
	 * Where «dejarlo» goes: the event list, or the site root as a last resort.
	 *
	 * @return string
	 */
	private static function events_url(): string {
		$url = Shell::url( 'events' );
		return '' !== $url ? $url : home_url( '/' );
	}

	/**
	 * The name of whoever holds the lock. Nunca «alguien».
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	private static function name( int $user_id ): string {
		$user = get_userdata( $user_id );
		if ( false === $user || '' === (string) $user->display_name ) {
			return 'Otra persona';
		}
		return (string) $user->display_name;
	}
}

// ---- src/Evt/PublicFront/CodeEditor.php ----
/**
 * The WordPress code editor (CodeMirror), wrapped for our own screens.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

/**
 * Un campo de código con el editor que ya trae WordPress.
 *
 * `wp_enqueue_code_editor()` y `wp.codeEditor.initialize()`, no CodeJar ni
 * ninguna otra biblioteca: ya está en el núcleo —cero bytes que empaquetar en
 * el bundle de Code Snippets—, trae CSSLint y JSHint, y es el mismo editor que
 * la persona conoce del «CSS adicional» del personalizador.
 *
 * Degrada, y esto no es opcional: `wp_enqueue_code_editor()` devuelve `false`
 * cuando quien mira ha desactivado el resaltado de sintaxis en su perfil. En
 * ese caso {@see field()} pinta un `<textarea>` normal y todo sigue
 * funcionando; lo único que se pierde es el coloreado y el avisador de
 * errores.
 *
 * Esta clase solo encola y pinta: quién puede escribir código lo decide
 * {@see \Evt\Access\EventAccess::can_edit_custom_css()} y su hermana, y qué se
 * guarda, {@see \Evt\Meta\EventMetaRegistration}.
 */
final class CodeEditor {

	/**
	 * Modo de hoja de estilos.
	 */
	public const MODE_CSS = 'css';

	/**
	 * Modo de guion. Se llama como el `type` de CodeMirror, no `js`.
	 */
	public const MODE_JS = 'javascript';

	/**
	 * MIME type each mode asks WordPress for.
	 *
	 * @var array<string, string>
	 */
	private const MIMES = array(
		self::MODE_CSS => 'text/css',
		self::MODE_JS  => 'text/javascript',
	);

	/**
	 * Load CodeMirror for one mode, and hand back whether it will be there.
	 *
	 * Idempotente: encolar dos veces el mismo guion no lo imprime dos veces, y
	 * ajustar los ajustes cuesta lo que cuesta leer un array.
	 *
	 * @param string $modo self::MODE_CSS or self::MODE_JS.
	 * @return bool False cuando WordPress no puede —modo desconocido, o el
	 *              resaltado desactivado en el perfil—, y toca el textarea pelado.
	 */
	public static function enqueue( string $modo ): bool {
		return array() !== self::settings( $modo );
	}

	/**
	 * One code field: its label, its help, and the textarea CodeMirror grows on.
	 *
	 * Argumentos, todos opcionales salvo `name`:
	 *
	 *   mode   string  self::MODE_CSS (por defecto) o self::MODE_JS.
	 *   name   string  Nombre del campo; es la clave de meta (`evt_custom_css`).
	 *   id     string  Identificador; se deriva del nombre si no se da.
	 *   label  string  Rótulo.
	 *   help   string  La ayuda, en castellano llano, debajo del campo.
	 *   value  string  Lo que hay guardado, en crudo.
	 *   rows   int     Altura del textarea sin CodeMirror. 12 por defecto.
	 *
	 * @param array<string, mixed> $args Lo de arriba.
	 * @return string
	 */
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

		// Se encola aquí y no solo fuera: quien pinta un campo no tiene que
		// acordarse de dos llamadas, y la respuesta de esta decide el aspecto.
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

	/**
	 * Ask WordPress for CodeMirror and its settings for one mode.
	 *
	 * @param string $modo self::MODE_CSS or self::MODE_JS.
	 * @return array<string, mixed> Empty array when there will be no CodeMirror.
	 */
	private static function settings( string $modo ): array {
		if ( ! isset( self::MIMES[ $modo ] ) ) {
			return array();
		}
		$ajustes = wp_enqueue_code_editor( array( 'type' => self::MIMES[ $modo ] ) );
		return is_array( $ajustes ) ? $ajustes : array();
	}
}

// ---- src/Evt/PublicFront/CustomCode.php ----
/**
 * Custom CSS and JavaScript printed on the public page of an event.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

use Evt\Access\EventAccess;
use Evt\Meta\EventMetaKeys;
use Evt\Meta\EventMetaRegistration;
use Evt\PostType\EventPostType;

/**
 * Saca a la página pública el CSS y el JavaScript a medida del evento.
 *
 * Dos claves de meta que viven tanto en el evento raíz como en cada página
 * satélite ({@see EventMetaKeys::CUSTOM_CSS} y {@see EventMetaKeys::CUSTOM_JS}).
 * Lo del evento raíz viste todas sus páginas; lo de una página, solo esa, y va
 * **después** para poder afinarlo. Nada de esto sale fuera de las páginas de su
 * evento: ni en el resto del sitio, ni en el escritorio, ni en las pantallas del
 * aplicativo, que son entradas de tipo `page` con un shortcode y no `evt_event`
 * ({@see Shell::SHORTCODES}).
 *
 * Dónde se enganchan y por qué esas prioridades:
 *
 * - **El CSS, en `wp_head` con prioridad 999.** Tiene que poder pisar al tema, y
 *   quien gana entre reglas de la misma especificidad es la última que se lee.
 *   En la cabecera WordPress imprime las hojas encoladas en `wp_print_styles`
 *   (prioridad 8) y el «CSS adicional» del personalizador en `wp_custom_css_cb`
 *   (prioridad 101); 999 va detrás de los dos y sigue estando en el `<head>`,
 *   que es lo que evita el parpadeo de pintar primero sin estilos.
 * - **El JavaScript, en `wp_footer` con prioridad 999.** Al pie, y el último:
 *   cuando el navegador llega ahí el documento ya está montado —no hace falta
 *   envolver el código en ningún `DOMContentLoaded`, que además le cambiaría el
 *   ámbito a quien lo escribió— y las bibliotecas encoladas para el pie
 *   (`wp_print_footer_scripts`, prioridad 20) ya se han cargado.
 *
 * El saneado no se duplica: es el mismo de
 * {@see EventMetaRegistration::sanitize_custom_css()} y
 * {@see EventMetaRegistration::sanitize_custom_js()}, que corren al guardar y se
 * repiten aquí al imprimir porque son idempotentes y porque una meta escrita
 * antes de que existiera el registro —o a pelo contra la base de datos— no ha
 * pasado por ellos. El CSS sale sin etiquetas y sin poder cerrar su `</style`;
 * el JavaScript **no se escapa**, que es código y escaparlo lo rompe, pero se le
 * desactiva la fuga por `</script`.
 *
 * Quién puede escribir estas dos metas lo decide
 * {@see EventAccess::can_edit_custom_css()} y su hermana, y el `auth_callback`
 * de cada una lo hace cumplir venga el dato por donde venga. Aquí solo se
 * imprime lo que ya está guardado.
 */
final class CustomCode {

	/**
	 * Prioridad en `wp_head`: detrás de las hojas del tema y del personalizador.
	 */
	public const CSS_PRIORITY = 999;

	/**
	 * Prioridad en `wp_footer`: el último, con el documento ya montado.
	 */
	public const JS_PRIORITY = 999;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'wp_head', array( self::class, 'print_css' ), self::CSS_PRIORITY );
		add_action( 'wp_footer', array( self::class, 'print_js' ), self::JS_PRIORITY );
	}

	/**
	 * The custom stylesheet of the event page being viewed.
	 *
	 * @return void
	 */
	public static function print_css(): void {
		$css = self::css( self::current_page_id() );
		if ( '' === $css ) {
			return;
		}
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- es CSS, no texto: escaparlo lo rompería. Sale de sanitize_custom_css(), sin etiquetas y sin poder cerrar la suya.
		printf( "<style id=\"evt-custom-css\">\n%s\n</style>\n", $css );
	}

	/**
	 * The custom script of the event page being viewed.
	 *
	 * @return void
	 */
	public static function print_js(): void {
		$js = self::js( self::current_page_id() );
		if ( '' === $js ) {
			return;
		}
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- es código: sale de sanitize_custom_js(), que solo le desactiva la fuga por `</script`.
		printf( "<script id=\"evt-custom-js\">\n%s\n</script>\n", $js );
	}

	/**
	 * The custom CSS that applies to one page: the event's, then the page's.
	 *
	 * @param int $page_id Event root or satellite page (0 = nothing to print).
	 * @return string Empty when there is none: no se imprime ni la etiqueta.
	 */
	public static function css( int $page_id ): string {
		return self::code(
			$page_id,
			EventMetaKeys::CUSTOM_CSS,
			array( EventMetaRegistration::class, 'sanitize_custom_css' )
		);
	}

	/**
	 * The custom JavaScript that applies to one page, in the same order.
	 *
	 * @param int $page_id Event root or satellite page (0 = nothing to print).
	 * @return string Empty when there is none.
	 */
	public static function js( int $page_id ): string {
		return self::code(
			$page_id,
			EventMetaKeys::CUSTOM_JS,
			array( EventMetaRegistration::class, 'sanitize_custom_js' )
		);
	}

	/**
	 * One meta key gathered from the event root and then from the page.
	 *
	 * @param int      $page_id Page being viewed.
	 * @param string   $key     Meta key.
	 * @param callable $limpia  Its sanitiser, the same one that runs on save.
	 * @return string
	 */
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

	/**
	 * Whose code applies here: the event root first, the page itself after.
	 *
	 * Ese orden es el que deja que una sección afine lo que el evento puso para
	 * todas sus páginas. En la portada del evento los dos son el mismo post y la
	 * lista tiene un solo elemento: el código no se imprime dos veces.
	 *
	 * @param int $page_id Page being viewed.
	 * @return int[]
	 */
	private static function chain( int $page_id ): array {
		if ( $page_id <= 0 ) {
			return array();
		}
		$event_id = EventAccess::root_id( $page_id );
		return $event_id === $page_id ? array( $page_id ) : array( $event_id, $page_id );
	}

	/**
	 * The event page being viewed, and only that.
	 *
	 * `is_singular()` pregunta por la consulta principal, así que una consulta
	 * secundaria de otro complemento no cuela su propio evento; y las pantallas
	 * del aplicativo son entradas de tipo `page`, de modo que su `wp_head()` y su
	 * `wp_footer()` —los imprime {@see Shell::render_standalone()}— salen limpios.
	 *
	 * @return int 0 outside the public page of an event.
	 */
	private static function current_page_id(): int {
		if ( is_admin() || ! is_singular( EventPostType::POST_TYPE ) ) {
			return 0;
		}
		return (int) get_queried_object_id();
	}
}

// ---- src/Evt/PublicFront/EventList.php ----
/**
 * The list of events someone may manage, with its filters and counts.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

use Evt\Access\EventAccess;
use Evt\Domain\EventState;
use Evt\Meta\EventMetaKeys;
use Evt\PostType\EventPostType;
use Evt\PublicFront\View\EventListView;
use Evt\Taxonomy\EventTaxonomies;

/**
 * Shortcode [evt_event_list]: la pantalla «Eventos».
 *
 * Es la pantalla en la que se empieza el día: qué eventos hay, en qué estado
 * están, cuántas secciones tiene cada uno y por dónde se entra a arreglarlos.
 * Hoy eso no existe —`/borradores/` es una rejilla sin acciones— y el estado
 * lo marca alguien a mano en una taxonomía que nadie vuelve a tocar.
 *
 * Aquí casi no se muta nada: se lee. Por eso todos sus parámetros van en la
 * URL. La única excepción es restaurar un evento de la papelera, que va por
 * POST con su nonce por fila y nunca por GET —un enlace pegado en un correo no
 * puede resucitar nada—. El guardián sigue siendo {@see EventAccess}: lo que no
 * se pueda editar no se enumera ni se restaura, y quien no tiene área no ve
 * nada (falla en cerrado).
 */
final class EventList {

	public const SHORTCODE = 'evt_event_list';

	/**
	 * Filas por página.
	 */
	public const PAGE_SIZE = 20;

	/**
	 * Filtro de los eventos que todavía no se han publicado.
	 *
	 * Va en la misma lista que los estados derivados porque para quien mira es
	 * lo mismo: una manera de acotar. Que uno salga de las fechas y el otro del
	 * estado de la entrada es cosa nuestra.
	 */
	public const FILTER_DRAFT = 'draft';

	/**
	 * Filtro de los eventos que están en la papelera.
	 *
	 * Va en la misma lista que los demás filtros porque para quien mira es una
	 * manera más de acotar, pero sale de otra consulta: los estados normales no
	 * incluyen `trash`, así que el listado de siempre nunca enseña lo enviado a
	 * la papelera.
	 */
	public const FILTER_TRASH = 'trash';

	/**
	 * Filtro de los eventos cerrados a edición: el estado «histórico».
	 *
	 * No sale de las fechas como los demás, sino de la meta `evt_archived`, y
	 * es transversal a ellos: un evento histórico es además, casi siempre, uno
	 * finalizado. Se lista como un estado más porque para quien mira eso es lo
	 * que es: una manera de acotar.
	 */
	public const FILTER_ARCHIVED = 'archived';

	/**
	 * Query var of the área filter.
	 *
	 * Ninguna se llama como una taxonomía: `evt_area`, `evt_type` y
	 * `evt_course` son variables de consulta públicas —las registra
	 * `register_taxonomy`— y usarlas aquí convertiría la página del listado en
	 * el archivo de un término, que ya no es una página singular ni lleva
	 * nuestro shortcode.
	 */
	public const VAR_AREA = 'evt_filter_area';

	/**
	 * Query var of the tipología filter.
	 */
	public const VAR_TYPE = 'evt_filter_type';

	/**
	 * Query var of the curso escolar filter.
	 */
	public const VAR_COURSE = 'evt_filter_course';

	/**
	 * Query var of the state filter.
	 */
	public const VAR_STATE = 'evt_filter_state';

	/**
	 * Query var of the title search.
	 */
	public const VAR_SEARCH = 'evt_search';

	/**
	 * Query var of the page number.
	 */
	public const VAR_PAGE = 'evt_page';

	/**
	 * Query var other screens use to say what just happened.
	 */
	public const VAR_NOTICE = 'evt_notice';

	/**
	 * Post statuses an event can be in. La papelera no es uno.
	 *
	 * @var string[]
	 */
	private const STATUSES = array( 'publish', 'future', 'draft', 'pending', 'private' );

	/**
	 * Hidden field naming the operation a POST asks for.
	 */
	public const FIELD_DO = 'evt_list_do';

	/**
	 * Hidden field with the event a POST is about.
	 */
	public const FIELD_EVENT = 'evt_list_event';

	/**
	 * Nonce action of the restore button.
	 */
	public const NONCE_ACTION = 'evt_list_restore';

	/**
	 * Register the shortcode and the restore handler.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'render' ) );
		// Después de que el CPT y sus capacidades estén registrados (init 10 y
		// 11), y antes de que se pinte nada: aquí todavía se puede redirigir.
		add_action( 'init', array( self::class, 'handle' ), 20 );
	}

	/**
	 * Name of the nonce field of the restore button of one row.
	 *
	 * Uno por fila: así el identificador que escribe `wp_nonce_field()` no se
	 * repite en la página y cada botón lleva el suyo.
	 *
	 * @param int $event_id Event post ID.
	 * @return string
	 */
	public static function nonce_name( int $event_id ): string {
		return 'evt_list_nonce_' . $event_id;
	}

	/**
	 * Bring one event back from the trash.
	 *
	 * Vuelve en borrador, y eso es a propósito: es lo que hace
	 * `wp_untrash_post()` desde WordPress 5.6 y es lo que aquí se quiere. Un
	 * evento que se borró por error no tiene por qué reaparecer publicado en la
	 * web sin que nadie lo mire.
	 *
	 * Ojo con lo que WordPress NO hace: enviar un evento a la papelera **no**
	 * manda a la papelera sus secciones satélite —comprobado en WordPress 7.1—,
	 * así que siguen donde estaban y restaurar el evento las deja tal cual.
	 *
	 * @return void
	 */
	public static function handle(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- se comprueba abajo, en cuanto se sabe de qué evento se habla.
		$op = sanitize_key( wp_unslash( (string) ( $_POST[ self::FIELD_DO ] ?? '' ) ) );
		if ( ! in_array( $op, array( 'restore', 'publish', 'unpublish' ), true ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- lo mismo: el nonce depende de esta fila.
		$event_id = absint( wp_unslash( $_POST[ self::FIELD_EVENT ] ?? 0 ) );
		$campo    = self::nonce_name( $event_id );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- esto es la comprobación del nonce.
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

	/**
	 * Publish an event, or send it back to draft.
	 *
	 * Es el interruptor del listado. Publicar **no toca las páginas satélite**:
	 * cada una tiene su estado y se publica desde el taller, que es lo que deja
	 * enseñar la portada de un evento con una sección todavía sin terminar.
	 *
	 * @param string $op       publish | unpublish.
	 * @param int    $event_id Event post ID.
	 * @return void
	 */
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

	/**
	 * Nonce action of one list operation.
	 *
	 * Una por acción: el nonce de restaurar no vale para publicar.
	 *
	 * @param string $op Operation.
	 * @return string
	 */
	public static function nonce_action( string $op = 'restore' ): string {
		return 'restore' === $op ? self::NONCE_ACTION : 'evt_list_' . $op;
	}

	/**
	 * Whether this person may bring that event back.
	 *
	 * Lo mismo que para editarlo, ni más ni menos: nadie restaura lo que no
	 * podría tocar. Y tiene que estar de verdad en la papelera y ser un evento
	 * raíz —una sección se restaura desde el taller de su evento—.
	 *
	 * @param int $user_id Who is asking.
	 * @param int $post_id Event post ID.
	 * @return bool
	 */
	private static function may_restore( int $user_id, int $post_id ): bool {
		return 'trash' === get_post_status( $post_id )
			&& EventPostType::POST_TYPE === get_post_type( $post_id )
			&& 0 === (int) get_post_field( 'post_parent', $post_id )
			&& EventAccess::can_edit( $user_id, $post_id );
	}

	/**
	 * Back to the list, on one filter and with one notice.
	 *
	 * @param string $state  State filter to land on.
	 * @param string $notice Key of flash().
	 * @return string
	 */
	private static function back_to( string $state, string $notice ): string {
		$url = self::url( self::no_filters(), array( 'state' => $state ) );
		if ( '' === $url ) {
			$url = Shell::back_url();
		}
		return add_query_arg( self::VAR_NOTICE, $notice, $url );
	}

	/**
	 * The state filter: predicates over what is already stored.
	 *
	 * @return array<string, string> clave => etiqueta.
	 */
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

	/**
	 * Human label of a post status.
	 *
	 * @return array<string, string> estado => etiqueta.
	 */
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

	/**
	 * Read one navigation parameter.
	 *
	 * @param string $key      Query var.
	 * @param string $fallback What to return when it is not there.
	 * @return string
	 */
	public static function input( string $key, string $fallback = '' ): string {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- filtros de lectura: esta pantalla no muta nada.
		return isset( $_GET[ $key ] ) && is_string( $_GET[ $key ] )
			? sanitize_text_field( wp_unslash( $_GET[ $key ] ) )
			: $fallback;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * What the URL is asking for.
	 *
	 * @return array{area:int, type:int, course:int, state:string, search:string, page:int}
	 */
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

	/**
	 * The list URL, keeping what is selected.
	 *
	 * @param array<string, mixed> $selection What selection() returned.
	 * @param array<string, mixed> $changes   What to change in it.
	 * @return string
	 */
	public static function url( array $selection, array $changes = array() ): string {
		// Cambiar un filtro devuelve a la primera página: la número siete de la
		// selección anterior casi nunca existe en la nueva.
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

	/**
	 * Everything the screen decides before painting.
	 *
	 * @return array<string, mixed>
	 */
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

		// Las cifras de los estados se cuentan solo sobre lo vivo: un evento en
		// la papelera no es un evento «próximo» ni uno «en borrador».
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

	/**
	 * The model of a screen with nothing on it yet.
	 *
	 * @return array<string, mixed>
	 */
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

	/**
	 * Why this person gets no list at all.
	 *
	 * @return string Empty when there is a list to show.
	 */
	private static function why_nothing(): string {
		if ( ! is_user_logged_in() ) {
			return 'Debe iniciar sesión con su usuario para gestionar eventos.';
		}

		$user_id = get_current_user_id();
		if ( Shell::can_use( $user_id ) ) {
			return '';
		}

		// Falta el permiso, o falta el área: no es lo mismo y no se arregla en
		// el mismo sitio.
		return user_can( $user_id, 'edit_evt_events' ) || EventAccess::is_manager( $user_id )
			? 'No tiene ningún área asignada en su perfil, así que todavía no puede gestionar eventos. El área la pone quien administra el aplicativo.'
			: 'Su usuario todavía no organiza eventos. Pídalo a quien administre el aplicativo.';
	}

	/**
	 * What the screen says around the table: aviso, ámbito y botón de crear.
	 *
	 * @param int  $user_id   User ID.
	 * @param bool $all_areas Whether this person works across every área.
	 * @return array<string, mixed>
	 */
	private static function chrome( int $user_id, bool $all_areas ): array {
		$crear = user_can( $user_id, 'edit_evt_events' ) ? Shell::url( 'event' ) : '';

		return array(
			'can_use'     => true,
			'notice'      => self::flash(),
			'page_id'     => self::hidden_page_id(),
			'scoped'      => ! $all_areas,
			// Con una sola área el desplegable no elige nada: siempre la misma.
			'area_filter' => $all_areas || count( EventAccess::user_areas( $user_id ) ) > 1,
			'subtitle'    => $all_areas
				? 'Todos los eventos, de todas las áreas.'
				: 'Solo los eventos de su área: los que organizan otras áreas no salen aquí.',
			'can_create'  => '' !== $crear,
			'create_url'  => $crear,
		);
	}

	/**
	 * The rows this person may manage, before any filter.
	 *
	 * @param int $user_id User ID.
	 * @return array<int, array<string, mixed>>
	 */
	private static function collect( int $user_id ): array {
		$rows = array();
		foreach ( self::scope( $user_id ) as $post ) {
			// El acotado de la consulta solo esconde; quien decide es siempre
			// EventAccess. Aquí se pregunta por `can_open()` y no por
			// `can_edit()` porque un evento marcado como histórico se sigue
			// consultando: esconderlo del listado sería perderlo de vista, y
			// lo que hace falta es encontrarlo y ver por qué está cerrado.
			if ( EventAccess::can_open( $user_id, (int) $post->ID ) ) {
				$rows[] = self::row( $post );
			}
		}
		return $rows;
	}

	/**
	 * Drop the filters that are no longer among the options.
	 *
	 * Un área que ya no tiene eventos aquí no acota nada, y dejarla puesta deja
	 * la pantalla vacía sin decir por qué.
	 *
	 * @param array<string, mixed>              $s       Selection.
	 * @param array<string, array<int, string>> $options Options of each dropdown.
	 * @return array<string, mixed>
	 */
	private static function sanitise( array $s, array $options ): array {
		foreach ( array_keys( $options ) as $eje ) {
			if ( $s[ $eje ] > 0 && ! isset( $options[ $eje ][ $s[ $eje ] ] ) ) {
				$s[ $eje ] = 0;
			}
		}
		return $s;
	}

	/**
	 * Apply the dropdowns and the text search.
	 *
	 * @param array<int, array<string, mixed>> $rows Rows.
	 * @param array<string, mixed>             $s    Selection.
	 * @return array<int, array<string, mixed>>
	 */
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

	/**
	 * How many rows each state filter would leave.
	 *
	 * Del mismo conjunto que la lista: lo que queda tras área, tipo, curso y
	 * búsqueda, antes de acotar por estado.
	 *
	 * @param array<int, array<string, mixed>> $rows Rows already narrowed.
	 * @return array<string, int>
	 */
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

	/**
	 * Apply the state filter.
	 *
	 * @param array<int, array<string, mixed>> $rows  Rows.
	 * @param string                           $state Filter key.
	 * @return array<int, array<string, mixed>>
	 */
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

	/**
	 * How many pages each of the rows being painted has.
	 *
	 * Solo las de la página: una consulta para las veinte filas, y no una por
	 * evento.
	 *
	 * @param array<int, array<string, mixed>> $rows Rows of this page.
	 * @return array<int, array<string, mixed>>
	 */
	private static function with_sections( array $rows ): array {
		$secciones = self::section_counts( array_map( 'intval', array_column( $rows, 'id' ) ) );

		foreach ( $rows as $i => $row ) {
			$rows[ $i ]['sections'] = $secciones[ $row['id'] ] ?? 0;
		}

		return $rows;
	}

	/**
	 * The screen, from its model.
	 *
	 * @param array<string, mixed> $model What model() returned.
	 * @return string
	 */
	public static function html( array $model ): string {
		return EventListView::html( $model );
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array<string, string>|string $atts Attributes.
	 * @return string
	 */
	public static function render( $atts = array() ): string {
		unset( $atts );
		Assets::enqueue();
		return self::html( self::model() );
	}

	/**
	 * The events this person may manage, before any filter.
	 *
	 * @param int $user_id User ID.
	 * @return \WP_Post[]
	 */
	private static function scope( int $user_id ): array {
		$base = array(
			'post_type'           => EventPostType::POST_TYPE,
			// Solo las raíces: las hijas son secciones y se cuentan aparte.
			'post_parent'         => 0,
			// La papelera viene en la misma consulta y se separa después: así
			// la cifra de «Papelera (N)» no cuesta una consulta más, y el
			// listado normal la deja fuera igual que antes.
			'post_status'         => array_merge( self::STATUSES, array( self::FILTER_TRASH ) ),
			// ponytail: el ámbito entero en memoria —hoy son unas decenas de
			// eventos, y el estado derivado no se puede pedir a la base de
			// datos—; si crece, guardar el estado en meta y paginar la consulta.
			'posts_per_page'      => 200, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- el listado filtra y pagina sobre el ámbito completo.
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
					'tax_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- acotar por área es el requisito, no una mejora opcional.
						array(
							'taxonomy' => EventTaxonomies::AREA,
							'field'    => 'term_id',
							'terms'    => $areas,
						),
					),
				)
			)
		);

		// Un evento recién creado todavía no tiene área, y quien lo creó es
		// justo quien tiene que ponérsela: sin esta segunda consulta se pierde
		// de vista en cuanto se guarda.
		$mios = get_posts( array_merge( $base, array( 'author' => $user_id ) ) );

		$unicos = array();
		foreach ( array_merge( $del_area, $mios ) as $post ) {
			$unicos[ (int) $post->ID ] = $post;
		}

		return array_values( $unicos );
	}

	/**
	 * One row of the table.
	 *
	 * @param \WP_Post $post Event.
	 * @return array<string, mixed>
	 */
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
			// Lo que está en la papelera no se abre para editarlo: primero se
			// restaura. Sin enlace, la vista lo pinta como texto.
			'url'      => self::FILTER_TRASH === $post->post_status ? '' : Shell::url( 'event', array( 'evento' => $id ) ),
			// Para la botonera: dónde se mira el evento y si quien mira puede
			// publicarlo. **Un borrador también se mira**: estando dentro y con
			// permiso, WordPress lo sirve en previsualización. Lo de la papelera
			// no: primero se restaura.
			'view_url' => self::FILTER_TRASH === $post->post_status
				? ''
				: ( 'publish' === $post->post_status ? (string) get_permalink( $id ) : (string) get_preview_post_link( $id ) ),
			'can_pub'  => EventAccess::can_publish( get_current_user_id(), $id ),
			'search'   => self::normalize( $titulo ),
		);
	}

	/**
	 * Terms of one taxonomy on one post.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $taxonomy Taxonomy.
	 * @return array<int, string> term_id => nombre.
	 */
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

	/**
	 * The options of each dropdown, taken from the rows themselves.
	 *
	 * Así no se ofrece nunca un filtro que dejaría la pantalla vacía, ni se
	 * enseña el nombre de un área en la que esta persona no tiene nada.
	 *
	 * @param array<int, array<string, mixed>> $rows Rows in scope.
	 * @return array<string, array<int, string>>
	 */
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

	/**
	 * Dropdowns and text search: everything but the state filter.
	 *
	 * @param array<string, mixed> $row Row.
	 * @param array<string, mixed> $s   Selection.
	 * @return bool
	 */
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

	/**
	 * The state filter, a predicate over one row.
	 *
	 * `vivos` no es un filtro de la pantalla: es el complementario de la
	 * papelera, y lo usa {@see model()} para partir el ámbito en dos.
	 *
	 * @param array<string, mixed> $row    Row.
	 * @param string               $filter Filter key.
	 * @return bool
	 */
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

	/**
	 * Newest first; what has no dates yet is what was just created.
	 *
	 * @param array<string, mixed> $a One row.
	 * @param array<string, mixed> $b Another.
	 * @return int
	 */
	private static function compare( array $a, array $b ): int {
		$ka = '' !== $a['start'] ? $a['start'] : '9999-12-31';
		$kb = '' !== $b['start'] ? $b['start'] : '9999-12-31';

		return $ka === $kb ? strnatcasecmp( $a['title'], $b['title'] ) : strcmp( $kb, $ka );
	}

	/**
	 * How many pages each of these events has.
	 *
	 * @param int[] $ids Event IDs.
	 * @return array<int, int> post ID => secciones.
	 */
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
				'posts_per_page'      => 500, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- las secciones de una página del listado, contadas de una vez.
				'fields'              => 'id=>parent',
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
			)
		);

		foreach ( $hijas as $hija ) {
			// Según la versión, `id=>parent` devuelve el padre o el objeto.
			$padre = is_object( $hija ) ? (int) $hija->post_parent : (int) $hija;
			if ( isset( $out[ $padre ] ) ) {
				++$out[ $padre ];
			}
		}

		return $out;
	}

	/**
	 * Whether anything is narrowing the list right now.
	 *
	 * @param array<string, mixed> $s Selection.
	 * @return bool
	 */
	private static function is_filtered( array $s ): bool {
		return $s['area'] > 0 || $s['type'] > 0 || $s['course'] > 0
			|| 'all' !== $s['state'] || '' !== $s['search'];
	}

	/**
	 * A selection with nothing narrowing it.
	 *
	 * @return array<string, mixed>
	 */
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

	/**
	 * What to say when the table has no rows.
	 *
	 * @param bool $filtered   Whether filters are narrowing the list.
	 * @param bool $all_areas  Whether this person works across every área.
	 * @return string
	 */
	private static function empty_text( bool $filtered, bool $all_areas ): string {
		if ( $filtered ) {
			return 'Ningún evento coincide con lo que ha pedido. Pruebe a quitar algún filtro o a buscar otra cosa.';
		}
		return $all_areas
			? 'Todavía no hay ningún evento. Cree el primero con «Crear evento».'
			: 'Todavía no hay ningún evento de su área. Aquí solo salen los eventos del área que los organiza; cree el primero con «Crear evento».';
	}

	/**
	 * What another screen left said on the way here.
	 *
	 * @return array{type:string, text:string}
	 */
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

	/**
	 * The page ID the filter form has to carry, or zero.
	 *
	 * Con enlaces permanentes sencillos la dirección de la página es
	 * `?page_id=12`, y un formulario `GET` sustituye la cadena de consulta
	 * entera: sin este campo oculto, filtrar llevaría a la portada del sitio.
	 *
	 * @return int
	 */
	private static function hidden_page_id(): int {
		return '' === (string) get_option( 'permalink_structure' ) ? (int) get_queried_object_id() : 0;
	}

	/**
	 * Normalise text so accents and capitals do not decide a search.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	private static function normalize( string $text ): string {
		return mb_strtolower( remove_accents( $text ), 'UTF-8' );
	}
}

// ---- src/Evt/PublicFront/View/EventListView.php ----
/**
 * «Eventos», painted from what EventList::model() decided.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

use Evt\Domain\DateRange;
use Evt\Meta\EventMetaKeys;
use Evt\PublicFront\Assets;
use Evt\PublicFront\EventList;
use Evt\PublicFront\Shell;

/**
 * Solo pinta: no lee la petición, no consulta y no decide.
 */
final class EventListView {

	/**
	 * The screen, from its model.
	 *
	 * @param array<string, mixed> $m What EventList::model() returned.
	 * @return string
	 */
	public static function html( array $m ): string {
		if ( empty( $m['can_use'] ) ) {
			return Shell::render( 'Eventos', '', Shell::notice( 'aviso', (string) $m['reason'] ) );
		}

		ob_start();
		?>
		<?php echo Shell::notice( (string) $m['notice']['type'], (string) $m['notice']['text'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shell::notice escapa su texto. ?>
		<?php if ( ! empty( $m['can_create'] ) ) : ?>
			<p class="evt-acciones">
				<a class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" href="<?php echo esc_url( (string) $m['create_url'] ); ?>">
					<?php echo Shell::icon_plus(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG literal. ?>
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

	/**
	 * The four figures that say how the área is doing.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return void
	 */
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

	/**
	 * «Papelera (N)»: el filtro de lo enviado a ella, y la vuelta.
	 *
	 * Cuando no hay nada en la papelera no se pinta: un enlace a una pantalla
	 * vacía no ayuda a nadie. El borrado definitivo no está aquí a propósito
	 * —lo hace quien pueda desde el escritorio de WordPress—, y se dice.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return void
	 */
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

	/**
	 * The filter bar: text, área, tipología, curso and state.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return void
	 */
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

	/**
	 * The table itself, or why it is empty.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return void
	 */
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
										<?php echo self::publish_switch( (array) $row ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
									<?php endif; ?>
								</td>
								<td class="evt-num" data-rotulo="Secciones"><?php echo esc_html( (string) $row['sections'] ); ?></td>
								<td data-rotulo="Acciones">
									<?php if ( $dentro ) : ?>
										<?php echo self::restore_form( (int) $row['id'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
									<?php else : ?>
										<span class="evt-acciones">
											<?php
											echo PanelParts::icon_link( (string) $row['url'], 'lapiz', 'Editar este evento' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
											$mirar = 'publish' === (string) $row['status']
												? 'Ver la página del evento'
												: 'Previsualizar el evento, que está en borrador';
											echo PanelParts::icon_link( (string) $row['view_url'], 'ojo', $mirar ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
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

	/**
	 * The publish state as a switch.
	 *
	 * Un interruptor y no un enlace: lo que se quiere saber de un vistazo es si
	 * el evento se ve fuera, y lo que se quiere hacer es cambiarlo. Con
	 * JavaScript se envía solo al soltarlo; sin JavaScript queda el botón de al
	 * lado, que hace exactamente lo mismo.
	 *
	 * Publicar el evento **no publica sus páginas**: cada una tiene su estado y
	 * se publica desde el taller.
	 *
	 * @param array<string, mixed> $row One row of the model.
	 * @return string
	 */
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

	/**
	 * Server-side pagination, with the filters kept.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return void
	 */
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

	/**
	 * «Restaurar»: su propio formulario POST, con su propio nonce.
	 *
	 * Por POST y no por enlace: un `GET` que resucita un evento se dispara
	 * desde cualquier sitio que pinte la dirección, incluido el prefetch del
	 * navegador.
	 *
	 * @param int $event_id Event post ID.
	 * @return string
	 */
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

	/**
	 * Term names of one cell.
	 *
	 * @param array<int, string> $terms term_id => nombre.
	 * @return string
	 */
	private static function names( array $terms ): string {
		return array() === $terms ? '—' : implode( ' · ', $terms );
	}

	/**
	 * The dates of an event, written the way they are announced.
	 *
	 * @param string $start First day, Y-m-d.
	 * @param string $end   Last day, Y-m-d; empty when there is none yet.
	 * @return string
	 */
	private static function dates( string $start, string $end ): string {
		$texto = DateRange::of( $start, $end );
		return '' !== $texto ? $texto : 'Sin fechas';
	}
}

// ---- src/Evt/PublicFront/Programme.php ----
/**
 * Speakers and programme activities of one event: read and write.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

use Evt\Access\EventAccess;
use Evt\Meta\ProgrammeMetaKeys;
use Evt\PostType\ActivityPostType;
use Evt\PostType\SpeakerPostType;

/**
 * Lo que cuelga de un evento y no es una página: quién habla y qué pasa.
 *
 * **Cuelgan por `post_parent`, no por una meta**, y eso es la decisión de esta
 * clase. El propio guardián lo tenía anotado como el camino: «lo mismo valdría
 * para un ponente o una actividad colgados de un evento, si algún día los
 * cuelgan». Colgándolos, el acotado por área
 * ({@see EventAccess::post_areas()}) y el cierre por histórico
 * ({@see EventAccess::is_archived()}) los alcanzan sin una línea más, porque
 * los dos preguntan por `root_id()`.
 *
 * Aquí no se pinta nada y no se lee la petición: eso es de los paneles.
 */
final class Programme {

	/**
	 * Speakers of an event, in the order the área chose.
	 *
	 * @param int  $event_id Event post ID.
	 * @param bool $papelera Whether to list the trash instead.
	 * @return \WP_Post[]
	 */
	public static function speakers( int $event_id, bool $papelera = false ): array {
		return self::children( $event_id, SpeakerPostType::POST_TYPE, $papelera );
	}

	/**
	 * Activities of an event, sorted the way the parrilla reads them.
	 *
	 * El orden sale de tres datos que son metas —día, hora de inicio y hora de
	 * fin—, así que se ordena en PHP y no con un `meta_query`: un evento tiene
	 * decenas de actividades, no miles, y tres `meta_key` distintos en el
	 * `orderby` cuestan tres `JOIN` para ahorrar un `usort` sobre un puñado de
	 * filas. Lo que no tiene día se va al final, junto.
	 *
	 * @param int  $event_id Event post ID.
	 * @param bool $papelera Whether to list the trash instead.
	 * @return \WP_Post[]
	 */
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

	/**
	 * The activities that take enrolment: the workshops.
	 *
	 * @param int $event_id Event post ID.
	 * @return \WP_Post[]
	 */
	public static function workshops( int $event_id ): array {
		$out = array();
		foreach ( self::activities( $event_id ) as $actividad ) {
			if ( ProgrammeMetaKeys::KIND_WORKSHOP === (string) get_post_meta( $actividad->ID, ProgrammeMetaKeys::ACTIVITY_KIND, true ) ) {
				$out[] = $actividad;
			}
		}
		return $out;
	}

	/**
	 * The parrilla: activities grouped by day and, inside a day, by sede.
	 *
	 * Es la forma que decidió ADR-0024: la sede es un dato de cada actividad y
	 * **un mismo día puede tener dos**. Un día con una sola sede devuelve un
	 * solo bloque, y el panel no le pone rótulo de sede porque no aporta nada;
	 * uno con dos devuelve dos, en el orden en que aparecen.
	 *
	 * @param int $event_id Event post ID.
	 * @return array<int, array{date:string, venues:array<int, array{venue:string, rows:array<int, array<string, mixed>>}>}>
	 */
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

		// Se pierden las claves a propósito: fuera de aquí la parrilla es una
		// lista ordenada de días y cada día una lista ordenada de sedes.
		$out = array();
		foreach ( $dias as $dia ) {
			$dia['venues'] = array_values( $dia['venues'] );
			$out[]         = $dia;
		}
		return $out;
	}

	/**
	 * The distinct sedes of an event, taken from its activities.
	 *
	 * No hay ninguna pantalla donde «gestionar las sedes»: la lista es
	 * derivada, y añadir una sede es escribirla en una actividad (ADR-0024).
	 * Sirve para proponer las ya usadas al teclear la siguiente.
	 *
	 * @param int $event_id Event post ID.
	 * @return string[]
	 */
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

	/**
	 * Create or update a speaker of this event.
	 *
	 * @param int                  $event_id   Event post ID.
	 * @param int                  $speaker_id 0 to create.
	 * @param array<string, mixed> $data       Validated payload.
	 * @return int The speaker post ID, 0 when it could not be written.
	 */
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

	/**
	 * Create or update an activity of this event.
	 *
	 * @param int                  $event_id    Event post ID.
	 * @param int                  $activity_id 0 to create.
	 * @param array<string, mixed> $data        Validated payload.
	 * @return int The activity post ID, 0 when it could not be written.
	 */
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
		// Solo los ponentes que son de este evento: una ficha de otro evento no
		// se enlaza aquí, que es lo que sostiene ADR-0021.
		update_post_meta(
			$id,
			ProgrammeMetaKeys::ACTIVITY_SPEAKERS,
			implode( ',', self::own_speakers( $event_id, (array) $data['speakers'] ) )
		);
		EventAccess::stamp_area( $id );

		return $id;
	}

	/**
	 * Send a speaker or an activity of this event to the trash.
	 *
	 * @param int $event_id Event post ID.
	 * @param int $post_id  What to trash.
	 * @return bool
	 */
	public static function trash( int $event_id, int $post_id ): bool {
		if ( ! self::belongs( $event_id, $post_id ) ) {
			return false;
		}
		return (bool) wp_trash_post( $post_id );
	}

	/**
	 * Bring one back from the trash.
	 *
	 * @param int $event_id Event post ID.
	 * @param int $post_id  What to restore.
	 * @return bool
	 */
	public static function restore( int $event_id, int $post_id ): bool {
		if ( ! self::belongs( $event_id, $post_id, true ) ) {
			return false;
		}
		return (bool) wp_untrash_post( $post_id );
	}

	/**
	 * Move a speaker one place up or down.
	 *
	 * Se renumera la lista entera por el mismo motivo que en las secciones:
	 * intercambiar dos `menu_order` no mueve nada cuando varias fichas
	 * comparten número, que es lo que pasa en cuanto se crean seguidas.
	 *
	 * @param int $event_id   Event post ID.
	 * @param int $speaker_id Speaker to move.
	 * @param int $delta      -1 up, 1 down.
	 * @return bool
	 */
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

	/*
	 * -----------------------------------------------------------------------
	 * Los modelos de fila que consumen los paneles
	 * -----------------------------------------------------------------------
	 */

	/**
	 * One speaker, as the panel reads it.
	 *
	 * @param \WP_Post $ponente Speaker post.
	 * @return array<string, mixed>
	 */
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

	/**
	 * One activity, as the parrilla and the workshops panel read it.
	 *
	 * @param \WP_Post $actividad Activity post.
	 * @return array<string, mixed>
	 */
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

	/**
	 * The speakers linked to one activity.
	 *
	 * @param int $activity_id Activity post ID.
	 * @return int[]
	 */
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

	/*
	 * -----------------------------------------------------------------------
	 * Lo de dentro
	 * -----------------------------------------------------------------------
	 */

	/**
	 * The posts of one type that hang from this event.
	 *
	 * @param int    $event_id  Event post ID.
	 * @param string $post_type Post type.
	 * @param bool   $papelera  Whether to list the trash instead.
	 * @return \WP_Post[]
	 */
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

	/**
	 * Insert or update, never touching a post of another event.
	 *
	 * @param int                  $post_id   0 to create.
	 * @param array<string, mixed> $campos    Post fields.
	 * @param string               $post_type Expected post type.
	 * @param int                  $event_id  Event the post has to hang from.
	 * @return int
	 */
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

	/**
	 * Whether this post really hangs from this event.
	 *
	 * Es la comprobación que impide que un identificador cambiado a mano en el
	 * envío toque el ponente de otro evento: el acotado por área diría que sí
	 * cuando las dos áreas coinciden.
	 *
	 * @param int  $event_id Event post ID.
	 * @param int  $post_id  Post ID.
	 * @param bool $papelera Whether the post is expected to be in the trash.
	 * @return bool
	 */
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

	/**
	 * Keep only the speaker IDs that belong to this event.
	 *
	 * @param int   $event_id Event post ID.
	 * @param int[] $ids      Submitted speaker IDs.
	 * @return int[]
	 */
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

// ---- src/Evt/PublicFront/Participants.php ----
/**
 * The people signed up to one event: read, filter and export.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

/**
 * Quién se ha inscrito a un evento, para verlo, filtrarlo y exportarlo.
 *
 * **Los participantes son de este aplicativo.** Se gestionarán aquí, con
 * formulario de inscripción propio; el sistema anterior no se lee y no se le
 * construye ningún puente (ADR-0027).
 *
 * Esta clase declara la **forma de una fila** y pregunta por ellas con el
 * filtro `evt_participants`. Quien contesta de serie es el propio aplicativo
 * con sus `evt_registration` ({@see Registrations::participants()}, ADR-0032);
 * la costura se queda puesta porque es lo que permite a un despliegue traer sus
 * participantes de otro sitio desde un snippet, y lo que deja probar el filtro
 * y el CSV sin ningún almacén detrás. Sin nadie que conteste, la lista está
 * vacía y el panel dice dónde se abre la inscripción, en vez de fingir que
 * nadie se ha apuntado.
 *
 * Lo que sí es de aquí, y es lo que se pidió: **el filtro y la exportación a
 * CSV**, puros y probados sin WordPress.
 */
final class Participants {

	/**
	 * Filter every source answers to fill the list of one event.
	 */
	public const HOOK = 'evt_participants';

	/**
	 * The columns of the table, in order, with their heading.
	 *
	 * Es el contrato con quien conteste al filtro: una fila es este array.
	 * Lo que traiga de más se ignora; lo que falte sale vacío.
	 *
	 * @return array<string, string> clave => rótulo.
	 */
	public static function columns(): array {
		return array(
			'name'     => 'Nombre',
			'email'    => 'Correo',
			'centre'   => 'Centro',
			'workshop' => 'Taller',
			'date'     => 'Fecha de inscripción',
			'consent'  => 'Consentimiento',
		);
	}

	/**
	 * The participants of one event, as whoever answers the filter has them.
	 *
	 * @param int $event_id Event post ID.
	 * @return array<int, array<string, string>>
	 */
	public static function rows( int $event_id ): array {
		if ( $event_id <= 0 ) {
			return array();
		}

		/**
		 * Filter the participants of one event.
		 *
		 * @param array<int, array<string, string>> $rows     Rows so far.
		 * @param int                               $event_id Event post ID.
		 */
		$filas = apply_filters( self::HOOK, array(), $event_id );

		return self::clean( is_array( $filas ) ? $filas : array() );
	}

	/**
	 * Keep only the declared columns, as strings.
	 *
	 * Lo que llega del filtro es de fuera: se normaliza antes de que lo vea
	 * nadie, para que el panel y el CSV trabajen siempre con la misma forma.
	 *
	 * @param array<int, mixed> $filas Raw rows.
	 * @return array<int, array<string, string>>
	 */
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
			$out[] = $limpia;
		}
		return $out;
	}

	/**
	 * The rows that match what was typed in the filter box.
	 *
	 * Pura y sin WordPress. Busca en todas las columnas a la vez porque es lo
	 * que se hace de verdad con una lista de inscripciones: se teclea un
	 * apellido, o un centro, o un taller, y se espera que salga.
	 *
	 * @param array<int, array<string, string>> $filas  Rows.
	 * @param string                            $texto  What was typed.
	 * @param string                            $taller Workshop to narrow to, '' for all.
	 * @return array<int, array<string, string>>
	 */
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
			if ( '' !== $buscado && false === strpos( self::fold( implode( ' ', $fila ) ), $buscado ) ) {
				continue;
			}
			$out[] = $fila;
		}
		return $out;
	}

	/**
	 * The distinct workshops the rows mention, to fill the narrowing select.
	 *
	 * @param array<int, array<string, string>> $filas Rows.
	 * @return string[]
	 */
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

	/**
	 * The rows as a CSV file, headings included.
	 *
	 * Separador **punto y coma** y BOM de UTF-8 al principio, que es lo que
	 * abre bien en el Excel en español sin pasar por el asistente de
	 * importación: con coma y sin BOM, un nombre con tilde sale roto y todo
	 * cae en la primera columna. Cada campo va entrecomillado y las comillas
	 * de dentro se duplican, que es lo que dice RFC 4180.
	 *
	 * Lo que empiece por `=`, `+`, `-` o `@` se escapa con una comilla simple
	 * delante: sin eso, una hoja de cálculo trata ese texto como fórmula y un
	 * campo copiado de un formulario se convierte en ejecución.
	 *
	 * @param array<int, array<string, string>> $filas Rows.
	 * @return string
	 */
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

		// CRLF y BOM: los dos son para que el fichero se abra donde se va a
		// abrir, que es una hoja de cálculo de escritorio y no un editor.
		return "\xEF\xBB\xBF" . implode( "\r\n", $lineas ) . "\r\n";
	}

	/**
	 * The file name an export gets.
	 *
	 * @param string $titulo Event title.
	 * @return string
	 */
	public static function filename( string $titulo ): string {
		$base = sanitize_title( $titulo );
		if ( '' === $base ) {
			$base = 'evento';
		}
		return 'participantes-' . $base . '-' . gmdate( 'Y-m-d' ) . '.csv';
	}

	/**
	 * One CSV line, quoted and escaped.
	 *
	 * @param string[] $campos Fields.
	 * @return string
	 */
	private static function csv_line( array $campos ): string {
		$fuera = array();
		foreach ( $campos as $campo ) {
			$fuera[] = '"' . str_replace( '"', '""', self::defuse( $campo ) ) . '"';
		}
		return implode( ';', $fuera );
	}

	/**
	 * Stop a spreadsheet from reading a field as a formula.
	 *
	 * @param string $campo Field.
	 * @return string
	 */
	private static function defuse( string $campo ): string {
		if ( '' === $campo ) {
			return '';
		}
		return false === strpos( "=+-@\t\r", $campo[0] ) ? $campo : "'" . $campo;
	}

	/**
	 * Lowercase and unaccented, so «Martín» matches «martin».
	 *
	 * @param string $texto Text.
	 * @return string
	 */
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

// ---- src/Evt/PublicFront/Registrations.php ----
/**
 * Signups of one event: store, read, and take a workshop seat.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

use Evt\Domain\RegistrationInput;
use Evt\Domain\SignupQuestions;
use Evt\Meta\ProgrammeMetaKeys;
use Evt\Meta\RegistrationMetaKeys;
use Evt\PostType\RegistrationPostType;

/**
 * Las inscripciones de un evento: guardarlas, leerlas y coger plaza.
 *
 * Una inscripción cuelga de su evento (ADR-0032). Aquí no se pinta nada y no
 * se lee la petición: eso es de las pantallas.
 *
 * **Lo único de todo el aplicativo donde dos personas hacen a la vez algo sobre
 * el mismo dato** es coger la última plaza de un taller. Por eso vive aquí
 * {@see seat()}, y por eso tiene un candado de verdad y no una comprobación
 * optimista (ADR-0033).
 */
final class Registrations {

	/**
	 * How long a held lock stays valid, in seconds.
	 *
	 * Corta a propósito: una petición que muera a mitad no puede dejar el
	 * evento bloqueado para siempre. Diez segundos son mucho más de lo que
	 * tarda contar unas plazas y escribir una meta, y poco para esperar.
	 */
	public const LOCK_TTL = 10;

	/**
	 * How long we keep trying to take the lock, in microseconds.
	 */
	private const LOCK_WAIT = 2000000;

	/**
	 * Prefix of the option that holds the lock of one event.
	 */
	private const LOCK_PREFIX = 'evt_seat_lock_';

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		// El aplicativo contesta a su propio filtro (ADR-0032): quien traiga
		// los participantes de otro sitio sigue pudiendo sustituirlos desde un
		// snippet, con más prioridad o quitando este.
		add_filter( Participants::HOOK, array( self::class, 'participants' ), 10, 2 );
	}

	// ─── leer ──────────────────────────────────────────────────────────────

	/**
	 * The registrations of one event.
	 *
	 * @param int $event_id Event post ID.
	 * @return \WP_Post[]
	 */
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

	/**
	 * Answer the participants filter with our own registrations.
	 *
	 * Llena la forma de fila que declara {@see Participants::columns()}, que es
	 * el contrato de la ADR-0027, y añade una columna por pregunta del evento
	 * con su rótulo por cabecera (ADR-0031).
	 *
	 * @param array<int, array<string, string>> $filas    Rows so far.
	 * @param int                               $event_id Event post ID.
	 * @return array<int, array<string, string>>
	 */
	public static function participants( array $filas, int $event_id ): array {
		$preguntas = self::questions( $event_id );
		$talleres  = array();
		foreach ( Programme::workshops( $event_id ) as $taller ) {
			$talleres[ $taller->ID ] = (string) $taller->post_title;
		}

		foreach ( self::all( $event_id ) as $inscripcion ) {
			$meta   = self::meta( $inscripcion->ID );
			$taller = (int) $meta[ RegistrationMetaKeys::REG_WORKSHOP ];

			$fila = array(
				'name'     => trim( $meta[ RegistrationMetaKeys::REG_NAME ] . ' ' . $meta[ RegistrationMetaKeys::REG_SURNAME ] ),
				'email'    => $meta[ RegistrationMetaKeys::REG_EMAIL ],
				'centre'   => $meta[ RegistrationMetaKeys::REG_CENTRE ],
				'workshop' => $talleres[ $taller ] ?? '',
				'date'     => get_the_date( 'Y-m-d H:i', $inscripcion ),
				'consent'  => self::consent_text( $meta ),
			);

			// Una columna por pregunta, con el rótulo por cabecera. La clave es
			// el identificador, así que reescribir el rótulo mueve la cabecera
			// y no descoloca ni un dato (ADR-0032).
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

	/**
	 * The questions of one event, normalised.
	 *
	 * @param int $event_id Event post ID.
	 * @return array<int, array<string, mixed>>
	 */
	public static function questions( int $event_id ): array {
		return SignupQuestions::read( get_post_meta( $event_id, RegistrationMetaKeys::SIGNUP_QUESTIONS, true ) );
	}

	/**
	 * The stored answers of one registration.
	 *
	 * @param int $registration_id Registration post ID.
	 * @return array<string, mixed>
	 */
	public static function answers( int $registration_id ): array {
		$crudo = get_post_meta( $registration_id, RegistrationMetaKeys::REG_ANSWERS, true );
		$datos = is_string( $crudo ) && '' !== $crudo ? json_decode( $crudo, true ) : $crudo;
		return is_array( $datos ) ? $datos : array();
	}

	/**
	 * Every core meta of one registration, as strings.
	 *
	 * @param int $registration_id Registration post ID.
	 * @return array<string, string>
	 */
	public static function meta( int $registration_id ): array {
		$out = array();
		foreach ( RegistrationMetaKeys::registration_keys() as $clave ) {
			$valor         = get_post_meta( $registration_id, $clave, true );
			$out[ $clave ] = is_scalar( $valor ) ? (string) $valor : '';
		}
		return $out;
	}

	/**
	 * How the consent shows in the list.
	 *
	 * @param array<string, string> $meta Registration metas.
	 * @return string
	 */
	private static function consent_text( array $meta ): string {
		$cuando = $meta[ RegistrationMetaKeys::REG_CONSENT_AT ];
		if ( '' === $cuando ) {
			return '';
		}
		return 'Sí (v' . $meta[ RegistrationMetaKeys::REG_CONSENT_VERSION ] . ', ' . $cuando . ')';
	}

	/**
	 * Whether anybody is signed up to this event yet.
	 *
	 * Es lo que cierra la edición de las preguntas ya contestadas
	 * ({@see SignupQuestions::refuse()}).
	 *
	 * @param int $event_id Event post ID.
	 * @return bool
	 */
	public static function has_any( int $event_id ): bool {
		return array() !== self::all( $event_id );
	}

	/**
	 * The registration a signup token opens, 0 for none.
	 *
	 * El testigo es largo y aleatorio, y abre **esta** inscripción y nada más
	 * del sitio: no es una sesión (ADR-0033). Se compara en tiempo constante
	 * para no filtrar por dónde se parece.
	 *
	 * @param int    $event_id Event post ID.
	 * @param string $token    Token from the link.
	 * @return int
	 */
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

	/**
	 * Whether a string has the shape of a token we issue.
	 *
	 * @param string $token Raw token.
	 * @return bool
	 */
	public static function is_token( string $token ): bool {
		return (bool) preg_match( '/^[a-f0-9]{40}$/', $token );
	}

	// ─── escribir ──────────────────────────────────────────────────────────

	/**
	 * Store one signup.
	 *
	 * El título es la **referencia**, no el nombre: quien mire `wp_posts` a
	 * pelo ve referencias y no personas (ADR-0032).
	 *
	 * @param int                  $event_id Event post ID.
	 * @param array<string, mixed> $core     Validated core fields.
	 * @param array<string, mixed> $answers  Validated answers, keyed by question ID.
	 * @return int Registration post ID, 0 on failure.
	 */
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
			RegistrationMetaKeys::REG_CONSENT_VERSION => (int) get_post_meta( $event_id, RegistrationMetaKeys::CONSENT_VERSION, true ),
			RegistrationMetaKeys::REG_CONSENT_AT      => current_time( 'mysql' ),
			// `wp_slash()` y no el JSON a pelo: `update_post_meta()` desescapa lo
			// que le llega, y sin esto una respuesta con una tilde o una comilla
			// llegaría rota a la base de datos.
			RegistrationMetaKeys::REG_ANSWERS         => wp_slash( (string) wp_json_encode( $answers ) ),
			RegistrationMetaKeys::REG_TOKEN           => self::new_token(),
		);
		foreach ( $metas as $clave => $valor ) {
			update_post_meta( $id, $clave, $valor );
		}

		return $id;
	}

	/**
	 * A fresh token.
	 *
	 * @return string
	 */
	public static function new_token(): string {
		return bin2hex( random_bytes( 20 ) );
	}

	/**
	 * The reference a new registration gets as its title.
	 *
	 * @param int $event_id Event post ID.
	 * @return string
	 */
	private static function reference( int $event_id ): string {
		return sprintf( 'INS-%d-%s', $event_id, strtoupper( substr( bin2hex( random_bytes( 4 ) ), 0, 8 ) ) );
	}

	// ─── el aforo, que es lo único con carrera dentro ──────────────────────

	/**
	 * Take a workshop seat, releasing the previous one, or say why not.
	 *
	 * Los tres pasos —contar, soltar y tomar— van **dentro del mismo candado**,
	 * y eso es lo que impide que alguien se quede sin ninguno de los dos
	 * talleres: o el cambio entero o nada (ADR-0033).
	 *
	 * Lo que **no** se hace, y se escribe para que no vuelva por parecer lo
	 * natural: contar en PHP, comparar y escribir sin candado. Dos peticiones
	 * leen 19 de 20 y las dos escriben.
	 *
	 * @param int $event_id        Event post ID.
	 * @param int $registration_id Registration post ID.
	 * @param int $activity_id     Workshop to take, 0 to just release.
	 * @return array{ok:bool, error:string}
	 */
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

			// Soltar y tomar, en el mismo turno: no existe el instante en el
			// que esta persona se ha quedado sin ninguno de los dos.
			update_post_meta( $registration_id, RegistrationMetaKeys::REG_WORKSHOP, $activity_id );

			return array(
				'ok'    => true,
				'error' => '',
			);
		} finally {
			self::unlock( $event_id );
		}
	}

	/**
	 * Whether one more person fits in a workshop.
	 *
	 * Un aforo de cero significa «sin límite», que es lo que ya hacía la
	 * pantalla de talleres y no hay razón para cambiarlo.
	 *
	 * @param int $event_id        Event post ID.
	 * @param int $activity_id     Workshop.
	 * @param int $registration_id Who is asking, so they do not count twice.
	 * @return bool
	 */
	public static function fits( int $event_id, int $activity_id, int $registration_id = 0 ): bool {
		$aforo = (int) get_post_meta( $activity_id, ProgrammeMetaKeys::ACTIVITY_SEATS, true );
		if ( $aforo <= 0 ) {
			return true;
		}
		return self::taken( $event_id, $activity_id, $registration_id ) < $aforo;
	}

	/**
	 * How many seats of a workshop are taken.
	 *
	 * @param int $event_id   Event post ID.
	 * @param int $activity_id Workshop.
	 * @param int $except      Registration not to count.
	 * @return int
	 */
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

	/**
	 * The workshops somebody may still choose from.
	 *
	 * Un taller lleno **no sale**. Que no salga no es el control: la pantalla
	 * se pudo pintar hace cinco minutos, y la comprobación que manda es la del
	 * candado, al guardar (ADR-0033). Quien lo tuviera ya elegido lo conserva:
	 * llenarse no echa a nadie.
	 *
	 * @param int $event_id        Event post ID.
	 * @param int $registration_id Who is choosing, 0 for a new signup.
	 * @return array<int, array{id:int, title:string, seats:int, taken:int, mine:bool}>
	 */
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

	/**
	 * Whether an activity is a workshop of this event.
	 *
	 * @param int $event_id    Event post ID.
	 * @param int $activity_id Activity.
	 * @return bool
	 */
	private static function is_workshop_of( int $event_id, int $activity_id ): bool {
		foreach ( Programme::workshops( $event_id ) as $taller ) {
			if ( (int) $taller->ID === $activity_id ) {
				return true;
			}
		}
		return false;
	}

	// ─── el candado ────────────────────────────────────────────────────────

	/**
	 * Take the lock of one event, waiting a little for it.
	 *
	 * **Uno por evento y no uno por taller**: un cambio toca dos talleres, y
	 * dos candados tomados en distinto orden por dos peticiones son un abrazo
	 * mortal que no falla hasta el día que falla (ADR-0033).
	 *
	 * Se toma con `add_option()`, que **falla si el nombre ya existe** porque
	 * la columna es única en la base de datos: eso es lo que lo convierte en un
	 * candado de verdad sin escribir SQL propio. `autoload` a `no` para no
	 * meterlo en la carga de opciones de cada petición.
	 *
	 * @param int $event_id Event post ID.
	 * @return bool
	 */
	public static function lock( int $event_id ): bool {
		$nombre  = self::LOCK_PREFIX . $event_id;
		$esperar = 0;

		do {
			wp_cache_delete( $nombre, 'options' );
			wp_cache_delete( 'notoptions', 'options' );

			if ( add_option( $nombre, (string) time(), '', 'no' ) ) {
				return true;
			}

			// Un candado caducado es de una petición que murió a mitad: se
			// retira y se vuelve a intentar. Se borra antes de reclamarlo para
			// que quien lo consiga sea quien gane el `add_option()`.
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

	/**
	 * Release the lock of one event.
	 *
	 * @param int $event_id Event post ID.
	 * @return void
	 */
	public static function unlock( int $event_id ): void {
		delete_option( self::LOCK_PREFIX . $event_id );
	}

	/**
	 * Validate a signup payload against the event, without writing anything.
	 *
	 * @param int                  $event_id Event post ID.
	 * @param array<string, mixed> $raw      Raw submitted fields.
	 * @param array<string, mixed> $answers  Raw submitted answers.
	 * @return array{ok:bool, errors:string[], core:array<string, mixed>, answers:array<string, mixed>}
	 */
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

	/**
	 * The catalogue of centres a person may pick from.
	 *
	 * El centro **nunca se teclea** (ADR-0031). El catálogo maestro no es de
	 * este aplicativo: se pregunta por un filtro, como los participantes, y
	 * quien lo tenga lo contesta. Sin nadie que conteste no hay catálogo y la
	 * pantalla lo dice, en vez de dejar teclear y fabricar variantes.
	 *
	 * @return string[]
	 */
	public static function centres(): array {
		/**
		 * Filter the catalogue of centres.
		 *
		 * @param string[] $centres Centre names.
		 */
		$centros = apply_filters( 'evt_centres', array() );
		return is_array( $centros ) ? array_values( array_filter( array_map( 'strval', $centros ) ) ) : array();
	}
}

// ---- src/Evt/PublicFront/SignupForm.php ----
/**
 * The public signup form of an event, and what it does when submitted.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

use Evt\Domain\RegistrationInput;
use Evt\Meta\RegistrationMetaKeys;

/**
 * El formulario de inscripción: quién lo puede ver, y qué pasa al enviarlo.
 *
 * Lee la petición y decide; **no pinta nada**, que es de
 * {@see \Evt\PublicFront\Block\SignupBlock}.
 *
 * Quien se inscribe **no tiene cuenta en el sitio y no la va a tener**
 * (ADR-0032). Así que:
 *
 * - Para volver a su inscripción —a cambiar de taller— entra con un **testigo**
 *   aleatorio que viaja en el enlace, no con su correo tecleado. Un dato que
 *   cualquiera puede escribir no es una credencial.
 * - El nonce sigue puesto contra el envío cruzado, pero **no puede ser lo único**:
 *   a una persona anónima WordPress le da el mismo nonce que a cualquier otra.
 *   Lo que protege de verdad es que un envío solo puede crear su propia
 *   inscripción, y que tocar una existente exige el testigo.
 */
final class SignupForm {

	/**
	 * The field that says this POST is ours, and what it wants.
	 */
	public const FIELD_OP = 'evt_signup_op';

	public const OP_SIGNUP   = 'inscribir';
	public const OP_WORKSHOP = 'taller';

	public const FIELD_EVENT   = 'evt_signup_event';
	public const FIELD_TOKEN   = 'evt_token';
	public const FIELD_ANSWERS = 'evt_q';

	public const NONCE_ACTION = 'evt_signup';
	public const NONCE_FIELD  = '_evt_signup_nonce';

	/**
	 * Query argument that carries the token back in a link.
	 */
	public const ARG_TOKEN = 'inscripcion';

	/**
	 * What the last submit left to say, for the block to paint.
	 *
	 * @var array{level:string, message:string}|null
	 */
	private static $notice = null;

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'maybe_handle_submit' ), 20 );
	}

	/**
	 * What the last submit left to say.
	 *
	 * @return array{level:string, message:string}|null
	 */
	public static function notice(): ?array {
		return self::$notice;
	}

	/**
	 * Take a submit, when it is ours.
	 *
	 * @return void
	 */
	public static function maybe_handle_submit(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- se comprueba justo debajo.
		$op = isset( $_POST[ self::FIELD_OP ] ) ? sanitize_key( wp_unslash( $_POST[ self::FIELD_OP ] ) ) : '';
		if ( self::OP_SIGNUP !== $op && self::OP_WORKSHOP !== $op ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- es la comprobación.
		$nonce = isset( $_POST[ self::NONCE_FIELD ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ) : '';
		if ( '' === $nonce || false === wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			self::$notice = array(
				'level'   => 'error',
				'message' => 'El formulario estuvo abierto demasiado tiempo y el envío caducó. Vuelva a enviarlo.',
			);
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- comprobado arriba.
		$raw      = (array) wp_unslash( $_POST );
		$event_id = isset( $raw[ self::FIELD_EVENT ] ) ? (int) $raw[ self::FIELD_EVENT ] : 0;

		if ( self::OP_SIGNUP === $op ) {
			self::signup( $event_id, $raw );
			return;
		}
		self::workshop( $event_id, $raw );
	}

	/**
	 * A new signup.
	 *
	 * @param int                  $event_id Event post ID.
	 * @param array<string, mixed> $raw      Submitted fields.
	 * @return void
	 */
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
		if ( ! $v['ok'] ) {
			self::$notice = array(
				'level'   => 'error',
				'message' => RegistrationInput::why( $v['errors'] ),
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

		// El taller, si se pidió uno y su plazo está abierto, va por el mismo
		// camino que un cambio: el candado y el aforo mandan igual (ADR-0033).
		$taller = isset( $raw['evt_workshop'] ) ? (int) $raw['evt_workshop'] : 0;
		if ( $taller > 0 && self::workshops_open( $event_id ) ) {
			Registrations::seat( $event_id, $id, $taller );
		}

		Shell::leave( self::link( $event_id, (string) get_post_meta( $id, RegistrationMetaKeys::REG_TOKEN, true ) ) );
	}

	/**
	 * A workshop chosen or changed from an existing registration.
	 *
	 * @param int                  $event_id Event post ID.
	 * @param array<string, mixed> $raw      Submitted fields.
	 * @return void
	 */
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

	/**
	 * Why a seat was refused, in Spanish.
	 *
	 * @param string $error Error code.
	 * @return string
	 */
	public static function why_no_seat( string $error ): string {
		$textos = array(
			'sin_plazas' => 'Ese taller se ha llenado mientras rellenaba la página. Elija otro de la lista, que está al día.',
			'ocupado'    => 'Hay varias personas eligiendo taller a la vez. Vuelva a intentarlo en unos segundos.',
		);
		return $textos[ $error ] ?? 'No se ha podido guardar la elección.';
	}

	/**
	 * The link that opens one registration again.
	 *
	 * @param int    $event_id Event post ID.
	 * @param string $token    Its token.
	 * @return string
	 */
	public static function link( int $event_id, string $token ): string {
		$pagina = self::page( $event_id );
		$url    = $pagina > 0 ? (string) get_permalink( $pagina ) : (string) get_permalink( $event_id );
		return '' === $token ? $url : add_query_arg( self::ARG_TOKEN, rawurlencode( $token ), $url );
	}

	/**
	 * The signup section of an event, 0 when it has none.
	 *
	 * @param int $event_id Event post ID.
	 * @return int
	 */
	public static function page( int $event_id ): int {
		$hijas = get_posts(
			array(
				'post_type'        => \Evt\PostType\EventPostType::POST_TYPE,
				'post_parent'      => $event_id,
				'post_status'      => 'publish',
				'numberposts'      => -1,
				'meta_key'         => \Evt\Meta\EventMetaKeys::SECTION_TYPE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'       => 'inscripcion', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'suppress_filters' => false,
			)
		);
		return is_array( $hijas ) && array() !== $hijas ? (int) $hijas[0]->ID : 0;
	}

	/**
	 * The registration the current request is looking at, 0 for none.
	 *
	 * @param int $event_id Event post ID.
	 * @return int
	 */
	public static function current( int $event_id ): int {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- un testigo de lectura, no una escritura.
		$token = isset( $_GET[ self::ARG_TOKEN ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::ARG_TOKEN ] ) ) : '';
		return '' === $token ? 0 : Registrations::by_token( $event_id, $token );
	}

	/**
	 * Whether the signup form of an event is open.
	 *
	 * @param int $event_id Event post ID.
	 * @return bool
	 */
	public static function is_open( int $event_id ): bool {
		return (bool) get_post_meta( $event_id, RegistrationMetaKeys::SIGNUP_OPEN, true );
	}

	/**
	 * Whether workshops can be chosen right now.
	 *
	 * Tiene plazo propio, con sus fechas, porque se abre cuando quien organiza
	 * ha cerrado el programa y eso casi nunca coincide con abrir la inscripción
	 * (ADR-0033). Las fechas son opcionales: sin ellas manda el interruptor.
	 *
	 * @param int $event_id Event post ID.
	 * @return bool
	 */
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

// ---- src/Evt/PublicFront/EventWorkspace.php ----
/**
 * The workshop of one event: its sections, its data and its appearance.
 *
 * @package Evt
 */

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

/**
 * Shortcode [evt_event_workspace]: el taller de un evento.
 *
 * Es la pantalla del encargo. Hoy, editar una página de un evento son 139
 * campos en un solo formulario de 5.671 píxeles de alto, el mismo para la
 * portada y para cada satélite, y no hay ninguna pantalla que liste las
 * secciones de un evento ni que permita ordenarlas. Aquí eso son tres
 * pestañas internas (`?panel=secciones|datos|apariencia`) y cada una enseña
 * lo suyo y nada más.
 *
 * Todo lo que muta va por POST con su nonce propio y con
 * {@see EventAccess::can_edit()} comprobado, y vuelve a la misma pestaña con
 * el aviso: nunca se muta en GET, que es lo que hace que un enlace pegado en
 * un correo borre una sección.
 */
final class EventWorkspace {

	public const SHORTCODE = 'evt_event_workspace';

	/**
	 * Query arg carrying the event this workshop is about.
	 */
	public const ARG_EVENT = 'evento';

	/**
	 * Query arg carrying the inner tab.
	 */
	public const ARG_PANEL = 'panel';

	/**
	 * Query arg carrying a satellite page, for the section form.
	 */
	public const ARG_SECTION = 'seccion';

	/**
	 * Query arg carrying the type of a new satellite page.
	 */
	public const ARG_TYPE = 'tipo';

	/**
	 * Query arg that asks for the trash of the sections table.
	 */
	public const ARG_TRASH = 'papelera';

	/**
	 * Tab listing the satellite pages of the event.
	 */
	public const PANEL_SECTIONS = 'secciones';

	/**
	 * Tab with what the event is: identity, dates, classification, sign-up.
	 */
	public const PANEL_SETTINGS = 'ajustes';

	/**
	 * Tab with how the event looks: colours, typefaces, images.
	 */
	public const PANEL_LOOK = 'apariencia';

	/**
	 * Tab with the speakers of the event.
	 */
	public const PANEL_SPEAKERS = 'ponentes';

	/**
	 * Tab with the programme: the parrilla of activities.
	 */
	public const PANEL_PROGRAMME = 'programa';

	/**
	 * Tab with the activities that take enrolment.
	 */
	public const PANEL_WORKSHOPS = 'talleres';

	/**
	 * Tab with who signed up.
	 */
	public const PANEL_PEOPLE = 'participantes';

	/**
	 * Tab where the signup form of the event is set up (ADR-0031, ADR-0032).
	 */
	public const PANEL_SIGNUP = 'inscripcion';

	/**
	 * Query arg carrying the speaker or activity being edited.
	 */
	public const ARG_ROW = 'ficha';

	/**
	 * Query arg carrying what was typed in the participants filter.
	 */
	public const ARG_Q = 'busca';

	/**
	 * Query arg narrowing the participants to one workshop.
	 */
	public const ARG_WORKSHOP = 'taller';

	/**
	 * Tab with the custom CSS and JavaScript of the event.
	 *
	 * La ve también el área, que es quien escribe el CSS de su evento; lo que
	 * cambia es qué hay dentro. No sale para todo el mundo: {@see panels()}
	 * solo la pone a quien tiene alguna de las dos capacidades de código.
	 */
	public const PANEL_CODE = 'codigo';

	/**
	 * Hidden field with the speaker or activity a POST is about.
	 */
	public const FIELD_ROW = 'evt_row';

	/**
	 * Hidden field naming the operation a POST asks for.
	 */
	public const FIELD_DO = 'evt_do';

	/**
	 * Hidden field with the event a POST is about.
	 */
	public const FIELD_EVENT = 'evt_event';

	/**
	 * Hidden field with the satellite page a row action is about.
	 */
	public const FIELD_SECTION = 'evt_section';

	/**
	 * Field carrying the event title in the data panel.
	 */
	public const FIELD_TITLE = 'evt_title';

	/**
	 * Field carrying the área term in the data panel.
	 */
	public const FIELD_AREA = 'evt_area_term';

	/**
	 * Field carrying the tipología term in the data panel.
	 */
	public const FIELD_TYPE = 'evt_type_term';

	/**
	 * Field carrying the curso escolar term in the data panel.
	 */
	public const FIELD_COURSE = 'evt_course_term';

	/**
	 * Operation that closes an event for good: the «histórico» mark.
	 */
	public const OP_ARCHIVE = 'archive';

	/**
	 * Operation that opens it again.
	 */
	public const OP_UNARCHIVE = 'unarchive';

	/**
	 * Operations that act on one row of the sections table.
	 *
	 * @var string[]
	 */
	private const ROW_OPS = array( 'up', 'down', 'publish', 'unpublish', 'delete', 'restore' );

	/**
	 * Operation that saves one speaker.
	 */
	public const OP_SPEAKER = 'speaker';

	/**
	 * Operation that saves one activity.
	 */
	public const OP_ACTIVITY = 'activity';

	/**
	 * Operation that exports the participants as CSV.
	 */
	public const OP_EXPORT = 'export';

	/**
	 * Operations that act on one speaker or activity.
	 *
	 * Van aparte de `ROW_OPS` porque no son de la tabla de secciones y porque
	 * la fila que mueven viene en otro campo: `FIELD_ROW`, no `FIELD_SECTION`.
	 *
	 * @var string[]
	 */
	private const PROGRAMME_OPS = array( 'sp_up', 'sp_down', 'row_delete', 'row_restore' );

	/**
	 * Every operation this screen accepts by POST.
	 *
	 * @var string[]
	 */
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

	/**
	 * What each row action says when it worked.
	 *
	 * @var array<string, string>
	 */
	private const ROW_DONE = array(
		'up'        => 'Cambiado el orden de las secciones.',
		'down'      => 'Cambiado el orden de las secciones.',
		'delete'    => 'Sección enviada a la papelera. Nada se ha perdido: está en «Papelera» y se restaura desde ahí.',
		'restore'   => 'Sección restaurada, en borrador: revísela y publíquela cuando esté lista.',
		'publish'   => 'Sección publicada: ya se ve en el menú del evento.',
		'unpublish' => 'Sección despublicada: vuelve a borrador y desaparece del menú.',
	);

	/**
	 * Image types the appearance panel accepts.
	 *
	 * @var array<string, string>
	 */
	private const IMAGE_MIMES = array(
		'jpg|jpeg|jpe' => 'image/jpeg',
		'png'          => 'image/png',
		'webp'         => 'image/webp',
		'gif'          => 'image/gif',
	);

	/**
	 * Register the shortcode and the POST handler.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'render' ) );
		// Después de que el CPT y sus capacidades estén registrados (init 10 y
		// 11), y antes de que se pinte nada: aquí todavía se puede redirigir.
		add_action( 'init', array( self::class, 'handle' ), 20 );
		// «Tomar posesión» sale de las dos pantallas que se bloquean —el taller
		// y el formulario de una sección—, así que se engancha una sola vez y
		// aquí, donde ya cuelga el resto de las mutaciones del evento.
		add_action( 'init', array( EditLock::class, 'handle' ), 20 );
		// El editor de código se pide aquí y no al pintar: el shortcode corre
		// dentro de `the_content`, cuando `wp_head()` ya se escribió, y la hoja
		// de CodeMirror encolada entonces no llegaría a imprimirse.
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_code_editor' ), 20 );
	}

	/**
	 * Load CodeMirror when this request really is the «Código» tab.
	 *
	 * @return void
	 */
	public static function enqueue_code_editor(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- lectura de la pestaña; mutar lleva su nonce.
		if ( 'event' !== Shell::current_section()
			|| self::PANEL_CODE !== sanitize_key( wp_unslash( (string) ( $_GET[ self::ARG_PANEL ] ?? '' ) ) ) ) {
			return;
		}
		$event_id = absint( wp_unslash( $_GET[ self::ARG_EVENT ] ?? 0 ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$user_id = get_current_user_id();
		if ( EventAccess::can_edit_custom_css( $user_id, $event_id ) ) {
			CodeEditor::enqueue( CodeEditor::MODE_CSS );
		}
		if ( EventAccess::can_edit_custom_js( $user_id, $event_id ) ) {
			CodeEditor::enqueue( CodeEditor::MODE_JS );
		}
	}

	/*
	 * -----------------------------------------------------------------------
	 * Direcciones
	 * -----------------------------------------------------------------------
	 */

	/**
	 * URL of the workshop of one event, on one of its tabs.
	 *
	 * @param int                  $event_id Event post ID.
	 * @param string               $panel    Tab slug; empty for the default one.
	 * @param array<string, mixed> $args     Extra query arguments.
	 * @return string Empty when there is no event or no page for the screen.
	 */
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

	/**
	 * The three inner tabs, with where each one goes.
	 *
	 * La de «Código» no está para todo el mundo: quien no puede escribir ni el
	 * CSS ni el JavaScript no la ve, y como {@see fill()} decide la pestaña
	 * activa mirando esta lista, tampoco la abre escribiendo la dirección a
	 * mano —cae en «Secciones»—.
	 *
	 * @param int $event_id Event post ID.
	 * @param int $user_id  Who is looking; 0 leaves out anything conditional.
	 * @return array<string, array{label:string, url:string}>
	 */
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

		// El recuento va en la pestaña porque es lo que contesta de un vistazo
		// «¿le falta algo a este evento?», que es la pregunta con la que se
		// abre esta pantalla. Cero se pinta igual que cualquier otro número:
		// esconderlo dejaría la pestaña indistinguible de una sin datos.
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

	/**
	 * How many things each tab has, for the little number beside its name.
	 *
	 * @param int $event_id Event post ID.
	 * @return array<string, int>
	 */
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

	/**
	 * Name of the nonce field of one operation.
	 *
	 * Un nonce por acción y, en las acciones de fila, por fila: así el
	 * identificador que escribe `wp_nonce_field()` no se repite en la página
	 * y cada botón lleva el suyo.
	 *
	 * @param string $op         Operation.
	 * @param int    $section_id Satellite page, 0 outside the row actions.
	 * @return string
	 */
	public static function nonce_name( string $op, int $section_id = 0 ): string {
		return 'evt_nonce_' . $op . '_' . $section_id;
	}

	/**
	 * Nonce action of one operation.
	 *
	 * @param string $op Operation.
	 * @return string
	 */
	public static function nonce_action( string $op ): string {
		return 'evt_ws_' . $op;
	}

	/*
	 * -----------------------------------------------------------------------
	 * Las mutaciones
	 * -----------------------------------------------------------------------
	 */

	/**
	 * Apply what a POST asked for, then go back to the same tab.
	 *
	 * Se mira `$_POST` y nada más: un `GET` no lo rellena, así que no hay
	 * forma de disparar esto desde un enlace pegado en un correo.
	 *
	 * @return void
	 */
	public static function handle(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- se comprueba en verify(), en cuanto se sabe qué acción es.
		$op = sanitize_key( wp_unslash( (string) ( $_POST[ self::FIELD_DO ] ?? '' ) ) );
		if ( ! in_array( $op, self::OPS, true ) ) {
			return;
		}
		$event_id   = absint( wp_unslash( $_POST[ self::FIELD_EVENT ] ?? 0 ) );
		$section_id = absint( wp_unslash( $_POST[ self::FIELD_SECTION ] ?? 0 ) );
		$row_id     = absint( wp_unslash( $_POST[ self::FIELD_ROW ] ?? 0 ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$fila     = in_array( $op, self::ROW_OPS, true );
		$programa = in_array( $op, self::PROGRAMME_OPS, true );
		// El nonce de una acción de fila lleva dentro la fila, para que dos
		// botones de la misma pantalla no compartan identificador.
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

		// Crear un evento va lo primero, antes de todo lo que da por hecho que
		// el evento existe: sin esto, el botón «Crear evento» del listado lleva
		// a una pantalla que dice «elija un evento en la lista», que es la
		// pescadilla mordiéndose la cola.
		if ( self::PANEL_SETTINGS === $op && $event_id <= 0 ) {
			self::create_event( $user_id );
			return;
		}

		$destino = self::url( $event_id, self::panel_of( $op ) );
		if ( '' === $destino ) {
			$destino = Shell::back_url();
		}

		// Lo primero de todo, antes de escribir una sola meta, un término o un
		// estado: si otra persona tiene abierto el evento, este envío trae la
		// pantalla de antes y pisaría lo que esté escribiendo. Responde 409 y
		// no vuelve. Vale también para la marca de histórico, que es una
		// escritura más.
		EditLock::require_available( $event_id );

		// Antes de la comprobación de edición y no después, y esto es lo que
		// evita que la comprobación se muerda la cola: marcar como histórico
		// es lo último que hace un área con su evento, y en cuanto la marca
		// está puesta `can_edit()` dice que no. Si esto fuera detrás, marcar
		// sería imposible y desmarcar también.
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

	/**
	 * Which tab an operation goes back to.
	 *
	 * @param string $op Operation.
	 * @return string
	 */
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
		// Borrar y restaurar una ficha vuelven a donde se pulsó, que puede ser
		// Ponentes o Programa: el panel viaja en el propio envío.
		if ( 'row_delete' === $op || 'row_restore' === $op ) {
			return self::asked_panel();
		}
		return $op;
	}

	/**
	 * The tab a row action was pressed on.
	 *
	 * @return string
	 */
	private static function asked_panel(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- se comprueba en verify(); esto solo elige a dónde volver.
		$panel = sanitize_key( wp_unslash( (string) ( $_POST[ self::ARG_PANEL ] ?? '' ) ) );
		return in_array( $panel, array( self::PANEL_SPEAKERS, self::PANEL_PROGRAMME, self::PANEL_WORKSHOPS ), true )
			? $panel
			: self::PANEL_SPEAKERS;
	}

	/**
	 * Mark or unmark the event as «histórico».
	 *
	 * Las dos mitades no piden lo mismo, y esa es la regla: marcar lo hace el
	 * área que organiza el evento; desmarcar, solo administración.
	 *
	 * @param string $op       archive | unarchive.
	 * @param int    $event_id Event post ID.
	 * @param int    $user_id  Who is asking.
	 * @param string $destino  Where to go back to.
	 * @return void
	 */
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

		// Cerrado el evento, aquí ya no queda nada que editar: se suelta el
		// bloqueo —solo el propio— para que administración pueda entrar a
		// corregir una errata sin esperar a que caduque.
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

	/**
	 * Whether the POST carries the nonce of this very operation.
	 *
	 * @param string $op         Operation.
	 * @param int    $section_id Satellite page, for row actions.
	 * @return bool
	 */
	private static function verify( string $op, int $section_id ): bool {
		$campo = self::nonce_name( $op, $section_id );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- esto es la comprobación del nonce.
		$valor = sanitize_text_field( wp_unslash( (string) ( $_POST[ $campo ] ?? '' ) ) );
		return false !== wp_verify_nonce( $valor, self::nonce_action( $op ) );
	}

	/**
	 * One of the row actions of the sections table.
	 *
	 * @param string $op         up | down | publish | unpublish | delete | restore.
	 * @param int    $event_id   Event post ID.
	 * @param int    $section_id Satellite page post ID.
	 * @param int    $user_id    Who is asking.
	 * @param string $destino    Where to go back to.
	 * @return void
	 */
	private static function run_row( string $op, int $event_id, int $section_id, int $user_id, string $destino ): void {
		if ( EventPostType::POST_TYPE !== get_post_type( $section_id )
			|| (int) get_post_field( 'post_parent', $section_id ) !== $event_id ) {
			self::set_flash( 'error', 'Esa sección no es de este evento.' );
			Shell::leave( $destino );
			return;
		}

		if ( 'delete' === $op ) {
			// A la papelera y no borrada del todo: una sección con contenido
			// escrito se restaura desde «Papelera» si el clic fue un error. El
			// borrado definitivo no lo hace el aplicativo: es del escritorio.
			wp_trash_post( $section_id );
		} elseif ( 'restore' === $op ) {
			// Vuelve en borrador, y eso es a propósito: es lo que hace
			// `wp_untrash_post()` desde WordPress 5.6 y es lo que aquí se
			// quiere. Una sección que se borró por error no tiene por qué
			// reaparecer publicada en el menú del evento sin que nadie la mire;
			// publicarla es un clic más, en su botón de siempre.
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

	/**
	 * Move one satellite page one place up or down.
	 *
	 * Se renumera la lista entera en vez de intercambiar dos `menu_order`: las
	 * páginas heredadas del sistema anterior traen muchas el mismo número —o
	 * ninguno—, así que un intercambio dejaría el orden igual que estaba. Son
	 * un puñado de filas por evento.
	 *
	 * @param int $event_id   Event post ID.
	 * @param int $section_id Satellite page post ID.
	 * @param int $delta      -1 to move up, 1 to move down.
	 * @return void
	 */
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

	/**
	 * One of the row actions of the speakers table or the parrilla.
	 *
	 * @param string $op       sp_up | sp_down | row_delete | row_restore.
	 * @param int    $event_id Event post ID.
	 * @param int    $row_id   Speaker or activity post ID.
	 * @param string $destino  Where to go back to.
	 * @return void
	 */
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

	/**
	 * Save one speaker of this event.
	 *
	 * @param int    $event_id Event post ID.
	 * @param int    $row_id   Speaker post ID; 0 to create.
	 * @param string $destino  Where to go back to.
	 * @return void
	 */
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

	/**
	 * Save one activity of this event.
	 *
	 * @param int    $event_id Event post ID.
	 * @param int    $row_id   Activity post ID; 0 to create.
	 * @param string $destino  Where to go back to.
	 * @return void
	 */
	private static function save_activity( int $event_id, int $row_id, string $destino ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- el nonce se comprobó en handle() y cada elemento pasa por absint() en la línea siguiente.
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

	/**
	 * Attach, replace or remove the photo of a speaker.
	 *
	 * Se reutiliza el mismo camino que la portada del evento: un identificador
	 * de la biblioteca, comprobado que es una imagen.
	 *
	 * @param int $speaker_id Speaker post ID.
	 * @return void
	 */
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

	/**
	 * Send the participants of this event down as a CSV file.
	 *
	 * Va por POST y con nonce como cualquier otra acción, aunque no escriba
	 * nada: un enlace en GET que descarga la lista entera de personas
	 * inscritas es justo lo que no se quiere que se pueda pegar en un correo.
	 *
	 * @param int $event_id Event post ID.
	 * @return void
	 */
	private static function export_participants( int $event_id ): void {
		$filas = Participants::filter(
			Participants::rows( $event_id ),
			self::field( self::ARG_Q ),
			self::field( self::ARG_WORKSHOP )
		);

		$cuerpo = Participants::csv( $filas );
		$nombre = Participants::filename( (string) get_post_field( 'post_title', $event_id ) );

		// `Shell::leave()` no vale aquí: esto no redirige, escribe el fichero.
		if ( ! headers_sent() ) {
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="' . $nombre . '"' );
			header( 'Content-Length: ' . strlen( $cuerpo ) );
			header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		}
		echo $cuerpo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- es un CSV, no HTML; Participants::csv() lo entrecomilla y desactiva las fórmulas.
		// Sin URL: esto no redirige, ya ha escrito el fichero.
		Shell::leave();
	}

	/**
	 * Create a brand new event from the «Datos» form, then open its workshop.
	 *
	 * La capacidad que se pide es la misma con la que el listado decide pintar
	 * el botón: `edit_evt_events`. No se pregunta por `can_edit()` porque
	 * todavía no hay nada que editar, y el acotado por área llega en cuanto el
	 * evento existe — {@see EventAccess::stamp_area()} le pone el área de quien
	 * lo crea si no eligió ninguna.
	 *
	 * Nace en **borrador**: un evento recién creado no tiene programa ni
	 * ponentes, y publicarlo por el mero hecho de crearlo es publicar una
	 * página vacía.
	 *
	 * @param int $user_id Who is asking.
	 * @return void
	 */
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
			// Lo tecleado vuelve con el aviso, igual que al editar: perder el
			// formulario entero por una fecha mal escrita es la forma más
			// rápida de que no se vuelva a intentar.
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

	/**
	 * Everything the «Datos» form sent, in the shape the flash and save use.
	 *
	 * @param array<string, mixed> $crudo What EventInput was given.
	 * @return array<string, string>
	 */
	private static function submitted_values( array $crudo ): array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- el nonce lo comprobó handle().
		$intro = wp_kses_post( wp_unslash( (string) ( $_POST[ EventMetaKeys::INTRO ] ?? '' ) ) );
		$show  = empty( $_POST[ EventMetaKeys::SIGNUP_SHOW ] ) ? '' : '1';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

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

	/**
	 * Save the «Datos» panel.
	 *
	 * @param int    $event_id Event post ID.
	 * @param int    $user_id  Who is asking.
	 * @param string $destino  Where to go back to.
	 * @return void
	 */
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
			// Se devuelve lo tecleado junto al aviso: perder catorce campos
			// por una fecha mal escrita es la forma más rápida de que no se
			// vuelva a intentar.
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

	/**
	 * Write the meta of the data panel.
	 *
	 * Los sanitize de `register_post_meta()` corren dentro de cada
	 * `update_post_meta()`: aquí no se vuelve a limpiar nada.
	 *
	 * @param int                   $event_id Event post ID.
	 * @param array<string, string> $valores  What the form submitted.
	 * @param array<string, mixed>  $limpio   What EventInput normalised.
	 * @return void
	 */
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

	/**
	 * File the event under its área, its tipología and its curso escolar.
	 *
	 * @param int                   $event_id Event post ID.
	 * @param int                   $user_id  Who is asking.
	 * @param array<string, string> $valores  What the form submitted.
	 * @return void
	 */
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

	/**
	 * Whether this person may file the event under that área.
	 *
	 * Quien no coordina solo puede ponerle un área de las suyas: si no, el
	 * desplegable es la forma de regalarle el evento a otra área —y de
	 * perderlo de vista para siempre— con dos clics.
	 *
	 * @param int $user_id Who is asking.
	 * @param int $area_id Term ID of evt_area.
	 * @return bool
	 */
	public static function may_set_area( int $user_id, int $area_id ): bool {
		return EventAccess::can_edit_all_areas( $user_id )
			|| ( $area_id > 0 && in_array( $area_id, EventAccess::user_areas( $user_id ), true ) );
	}

	/**
	 * Whether this person may write any of the two code fields of the event.
	 *
	 * Es la condición de que exista la pestaña «Código». Con «alguna» basta, y
	 * es el caso corriente y no el raro: el área escribe el CSS de su evento y
	 * nunca el JavaScript, así que esconder la pestaña entera por eso la
	 * dejaría sin hoja de estilos. Cada campo se vuelve a comprobar por su
	 * cuenta, al pintarlo y al guardarlo.
	 *
	 * @param int $user_id  Who is asking.
	 * @param int $event_id Event post ID.
	 * @return bool
	 */
	public static function may_edit_code( int $user_id, int $event_id ): bool {
		return EventAccess::can_edit_custom_css( $user_id, $event_id )
			|| EventAccess::can_edit_custom_js( $user_id, $event_id );
	}

	/**
	 * Save the «Código» panel: one field, one capability, one check.
	 *
	 * No se mira si la pestaña se pintó: se comprueba aquí otra vez y campo a
	 * campo, que es lo único que vale contra un POST escrito a mano. Quien
	 * puede el CSS y no el JavaScript guarda el CSS, y el JavaScript que
	 * viniera en el envío se ignora y se dice —no se guarda a medias en
	 * silencio—.
	 *
	 * Los dos valores van en crudo: el `sanitize_callback` que
	 * {@see \Evt\Meta\EventMetaRegistration} registró corre dentro de
	 * `update_post_meta()` y es el que quita la etiqueta del CSS y desactiva la
	 * fuga por `</script` del JavaScript. Limpiarlos aquí con
	 * `sanitize_text_field()` los rompería: convertiría cada `>` en `&gt;`.
	 *
	 * @param int    $event_id Event post ID.
	 * @param int    $user_id  Who is asking.
	 * @param string $destino  Where to go back to.
	 * @return void
	 */
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
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- el nonce lo comprobó handle().
			if ( ! isset( $_POST[ $clave ] ) ) {
				// El campo no vino: la ausencia no es una orden de vaciar.
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

	/**
	 * One submitted field, untouched: code is code.
	 *
	 * @param string $nombre Field name.
	 * @return string
	 */
	private static function raw( string $nombre ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- el nonce lo comprobó handle(), y de limpiar se encarga el sanitize_callback de la meta.
		$valor = wp_unslash( $_POST[ $nombre ] ?? '' );
		return is_string( $valor ) ? $valor : '';
	}

	/**
	 * Save the «Apariencia» panel.
	 *
	 * @param int    $event_id Event post ID.
	 * @param string $destino  Where to go back to.
	 * @return void
	 */
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

		// La imagen destacada no es una meta nuestra sino el `thumbnail` de
		// WordPress: eso dice la clave vacía.
		$subidas = array(
			self::save_image( $event_id, 'evt_logo', EventMetaKeys::LOGO_ID ),
			self::save_image( $event_id, 'evt_poster', EventMetaKeys::POSTER_ID ),
			self::save_image( $event_id, 'evt_featured', '' ),
		);

		if ( in_array( false, $subidas, true ) ) {
			self::set_flash( 'aviso', 'Se guardó la apariencia, pero alguna imagen no se pudo cambiar y se quedó como estaba. Revise que sea una imagen de la biblioteca —JPG, PNG, WEBP o GIF— y que no pese demasiado.' );
			Shell::leave( $destino );
			return;
		}
		self::set_flash( 'ok', 'Apariencia del evento guardada.' );
		Shell::leave( $destino );
	}

	/**
	 * Replace or clear one of the images of the event.
	 *
	 * Tres caminos, en este orden: un fichero recién subido gana; si no, la
	 * casilla de quitar del respaldo sin guion; y si no, el identificador de
	 * adjunto que trae el campo oculto que rellena el selector de medios. De
	 * ese identificador no se fía nadie —lo escribe el navegador—: si no es
	 * un adjunto de imagen legible, no se toca lo que ya había y se avisa.
	 * Cuando el campo ni siquiera viene, tampoco se toca nada: la ausencia no
	 * es una orden de borrar.
	 *
	 * @param int    $event_id Event post ID.
	 * @param string $campo    Field prefix, e.g. `evt_logo`.
	 * @param string $meta_key Where the attachment ID lives; empty = thumbnail.
	 * @return bool False when what was sent could not be stored.
	 */
	private static function save_image( int $event_id, string $campo, string $meta_key ): bool {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- el nonce lo comprobó handle().
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- del fichero se encarga media_handle_upload(); del identificador, absint().
		if ( ! empty( $_FILES[ $campo . '_file' ]['name'] ) ) {
			$subido = self::upload( $campo . '_file', $event_id );
			if ( $subido <= 0 ) {
				return false;
			}
			self::put_image( $event_id, $meta_key, $subido );
			return true;
		}

		if ( ! empty( $_POST[ $campo . '_clear' ] ) ) {
			self::put_image( $event_id, $meta_key, 0 );
			return true;
		}

		if ( ! isset( $_POST[ $campo . '_id' ] ) ) {
			return true;
		}
		$elegido = absint( wp_unslash( $_POST[ $campo . '_id' ] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( $elegido > 0 && ! self::is_image_attachment( $elegido ) ) {
			return false;
		}

		self::put_image( $event_id, $meta_key, $elegido );
		return true;
	}

	/**
	 * Point one image of the event at an attachment, or at nothing.
	 *
	 * Se quita siempre y se vuelve a poner si hay algo, que deja quitar y
	 * cambiar en el mismo camino. Con 0, `set_post_thumbnail()` no hace nada:
	 * es justo lo que se quiere después de haber quitado la destacada.
	 *
	 * @param int    $event_id      Event post ID.
	 * @param string $meta_key      Where the attachment ID lives; empty = thumbnail.
	 * @param int    $attachment_id Attachment ID; 0 to clear it.
	 * @return void
	 */
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

	/**
	 * Whether that ID really is an image in the media library, readable here.
	 *
	 * El campo oculto lo escribe el navegador, así que lo escribe cualquiera:
	 * sin esta comprobación, un número tecleado a mano pondría de cartel del
	 * evento un PDF, un borrador de otra persona o una página cualquiera. Que
	 * quien lo manda pueda editar ESTE evento ya lo comprobó {@see handle()}.
	 *
	 * @param int $attachment_id What the hidden field carried.
	 * @return bool
	 */
	private static function is_image_attachment( int $attachment_id ): bool {
		return 'attachment' === get_post_type( $attachment_id )
			&& wp_attachment_is_image( $attachment_id )
			&& current_user_can( 'read_post', $attachment_id );
	}

	/**
	 * Put an uploaded image in the media library, attached to the event.
	 *
	 * @param string $campo    Field name.
	 * @param int    $event_id Event post ID.
	 * @return int Attachment ID, or 0 when it could not be stored.
	 */
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
				// El formulario ya trae comprobado su nonce; la comprobación
				// propia de `wp_handle_upload` es la del escritorio.
				'test_form' => false,
				'mimes'     => self::IMAGE_MIMES,
			)
		);
		return is_wp_error( $id ) ? 0 : (int) $id;
	}

	/**
	 * Why the data panel could not be saved, in words.
	 *
	 * @param string[] $errores What EventInput::validate() returned.
	 * @return string
	 */
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

	/**
	 * One submitted text field, trimmed and sanitised.
	 *
	 * @param string $nombre Field name.
	 * @return string
	 */
	/**
	 * What the signup tab shows: the questions and the two windows.
	 *
	 * @param array<string, mixed> $m        Model so far.
	 * @param int                  $event_id Event post ID.
	 * @return array<string, mixed>
	 */
	private static function fill_signup( array $m, int $event_id ): array {
		$m['questions'] = Registrations::questions( $event_id );
		// Con gente ya inscrita, a una pregunta se le puede cambiar el rótulo y
		// añadir opciones, pero no el tipo ni quitar una opción (ADR-0032). La
		// pantalla lo dice antes de que alguien lo intente.
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

	/**
	 * Save the signup settings and the question list of the event.
	 *
	 * @param int    $event_id Event post ID.
	 * @param string $destino  Where to go back to.
	 * @return void
	 */
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

		// `wp_slash()`: `update_post_meta()` desescapa, y un rótulo con tilde o
		// con comillas llegaría roto al JSON guardado.
		update_post_meta( $event_id, RegistrationMetaKeys::SIGNUP_QUESTIONS, wp_slash( (string) wp_json_encode( $ahora ) ) );
		self::set_flash( 'ok', 'Inscripción del evento guardada.' );
		Shell::leave( $destino );
	}

	/**
	 * Store the two consent texts, bumping the version when they change.
	 *
	 * Cambiarlos crea una versión nueva y **no reescribe** la que ya aceptó
	 * nadie (ADR-0020): por eso la versión sube aquí y la inscripción guarda la
	 * suya al crearse.
	 *
	 * @param int $event_id Event post ID.
	 * @return void
	 */
	private static function save_consent( int $event_id ): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- el nonce lo comprobó handle().
		$privacidad = wp_kses_post( wp_unslash( (string) ( $_POST['evt_consent_privacy'] ?? '' ) ) );
		$imagen     = wp_kses_post( wp_unslash( (string) ( $_POST['evt_consent_image'] ?? '' ) ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

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

	/**
	 * The question rows as they come from the form.
	 *
	 * Campos paralelos, sin JavaScript: una fila por índice. La que llegue sin
	 * rótulo se cae sola en {@see SignupQuestions::read()}, y es lo que hace
	 * que la fila en blanco del final sirva para añadir una pregunta y también
	 * para no añadir ninguna.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function submitted_questions(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- el nonce lo comprobó handle(); son listas y cada elemento se sanea en el bucle de abajo.
		$ids       = (array) wp_unslash( $_POST['evt_q_id'] ?? array() );
		$rotulos   = (array) wp_unslash( $_POST['evt_q_label'] ?? array() );
		$tipos     = (array) wp_unslash( $_POST['evt_q_type'] ?? array() );
		$opciones  = (array) wp_unslash( $_POST['evt_q_options'] ?? array() );
		$obligadas = (array) wp_unslash( $_POST['evt_q_required'] ?? array() );
		// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

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

	/**
	 * One submitted field, trimmed and sanitised.
	 *
	 * @param string $nombre Field name.
	 * @return string
	 */
	private static function field( string $nombre ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- el nonce lo comprobó handle() antes de llamar aquí.
		return trim( sanitize_text_field( wp_unslash( (string) ( $_POST[ $nombre ] ?? '' ) ) ) );
	}

	/*
	 * -----------------------------------------------------------------------
	 * El aviso que sobrevive a la redirección
	 * -----------------------------------------------------------------------
	 */

	/**
	 * Where the notice of the last action waits, per person.
	 *
	 * @return string
	 */
	private static function flash_key(): string {
		return 'evt_ws_flash_' . get_current_user_id();
	}

	/**
	 * Leave a notice —and what was typed— for the screen we go back to.
	 *
	 * @param string                $tipo    ok | aviso | error.
	 * @param string                $texto   What to say.
	 * @param array<string, string> $valores What was submitted, to repaint it.
	 * @return void
	 */
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

	/**
	 * Read the notice of the last action, and forget it.
	 *
	 * @return array{tipo:string, texto:string, values:array<string, string>}
	 */
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

	/*
	 * -----------------------------------------------------------------------
	 * El modelo
	 * -----------------------------------------------------------------------
	 */

	/**
	 * The satellite pages of an event, in the order they are shown.
	 *
	 * La papelera se pide aparte y nunca se mezcla: el listado normal no enseña
	 * lo que se envió a ella, que es justo lo que hace que enviar algo a la
	 * papelera se note.
	 *
	 * @param int  $event_id Event post ID.
	 * @param bool $papelera True for what was sent to the trash instead.
	 * @return \WP_Post[]
	 */
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
				// phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts -- un evento con más de 200 páginas satélite no existe, y la tabla no se pagina a propósito.
				'numberposts'      => 200,
				'suppress_filters' => false,
			)
		);
	}

	/**
	 * Everything the workshop decides before painting anything.
	 *
	 * @return array<string, mixed>
	 */
	public static function model(): array {
		$m = self::blank();

		if ( ! is_user_logged_in() ) {
			$m['aviso'] = 'Debe iniciar sesión con su usuario para gestionar eventos.';
			return $m;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- lectura del evento y de la pestaña; mutar lleva su nonce.
		$event_id = absint( wp_unslash( $_GET[ self::ARG_EVENT ] ?? 0 ) );
		$pedido   = sanitize_key( wp_unslash( (string) ( $_GET[ self::ARG_PANEL ] ?? '' ) ) );
		// La papelera es una vista de la pestaña de secciones, no una pestaña
		// más: se pide con `?papelera=1` y se lee aquí, con el resto de la URL.
		$m['trash'] = '' !== sanitize_key( wp_unslash( (string) ( $_GET[ self::ARG_TRASH ] ?? '' ) ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// Sin evento en la dirección, esto es el alta: es a donde lleva «Crear
		// evento» del listado. La capacidad es la misma con la que ese botón se
		// pinta, así que quien llegue aquí a mano y no pueda crear lee por qué.
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
		// `can_open()` y no `can_edit()`: un evento marcado como histórico se
		// sigue abriendo, en solo lectura. Lo que no se puede es guardar, y de
		// eso se ocupa `handle()`, que sí pregunta por `can_edit()`.
		if ( ! EventAccess::can_open( $user_id, $event_id ) ) {
			$m['aviso']      = EventAccess::why_not_editable( $user_id, $event_id );
			$m['aviso_tipo'] = 'error';
			return $m;
		}

		return self::fill( $m, $evento, $user_id, $pedido );
	}

	/**
	 * The model of the «create an event» screen.
	 *
	 * Es el taller con una sola pestaña y sin nada que colgar de un evento que
	 * todavía no existe: lo único que hay que rellenar para que exista son el
	 * título y la fecha de inicio ({@see EventInput}), y lo demás se añade
	 * después, con el evento ya abierto.
	 *
	 * @param array<string, mixed> $m What blank() returned.
	 * @return array<string, mixed>
	 */
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

	/**
	 * The model of a workshop that cannot be opened.
	 *
	 * @return array<string, mixed>
	 */
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

	/**
	 * Everything the workshop shows once the event is known and allowed.
	 *
	 * @param array<string, mixed> $m       What blank() returned.
	 * @param \WP_Post             $evento  The event.
	 * @param int                  $user_id Who is looking.
	 * @param string               $pedido  Tab asked for in the URL.
	 * @return array<string, mixed>
	 */
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
		// Abrir el taller toma el bloqueo del evento, igual que abrir el editor
		// del escritorio: es el mismo bloqueo. Y si ya lo tiene otra persona,
		// el taller pasa a solo lectura sin que ningún panel se entere —la
		// vista ya apaga sus controles cuando `can_edit` dice que no—.
		$m['lock'] = EditLock::status( $event_id, (bool) $m['can_edit'] );
		if ( (int) $m['lock']['owner'] > 0 ) {
			$m['can_edit'] = false;
		}
		// Los dos son «puede hacerlo ahora», no «tiene el permiso»: el estado
		// va dentro para que la vista no tenga que combinarlos otra vez y para
		// que no se pinten los dos botones a la vez. El bloqueo entra en la
		// cuenta porque el interruptor de histórico vive FUERA del panel que la
		// vista apaga, y cerrar un evento es la escritura menos reversible que
		// hay aquí.
		$libre              = 0 === (int) $m['lock']['owner'];
		$m['can_archive']   = $libre && ! $m['archived'] && EventAccess::can_archive( $user_id, $event_id );
		$m['can_unarchive'] = $libre && $m['archived'] && EventAccess::can_unarchive( $user_id );
		$m['can_publish']   = EventAccess::can_publish( $user_id, $event_id );
		$m['can_set_area']  = EventAccess::can_edit_all_areas( $user_id );
		$m['can_upload']    = current_user_can( 'upload_files' );
		$m['can_edit_css']  = $css_ok;
		$m['can_edit_js']   = $js_ok;
		// Lo guardado solo se devuelve a quien puede escribirlo: el modelo no
		// es un sitio donde el código se asome a quien no le corresponde.
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
			'logo'     => (int) self::meta( $event_id, EventMetaKeys::LOGO_ID ),
			'poster'   => (int) self::meta( $event_id, EventMetaKeys::POSTER_ID ),
			'featured' => (int) get_post_thumbnail_id( $event_id ),
		);

		return self::fill_signup( self::fill_programme( $m, $event_id ), $event_id );
	}

	/**
	 * What the four tabs of the programme need, once the event is known.
	 *
	 * Se llena siempre y no solo en la pestaña abierta: los recuentos de las
	 * pestañas los pide `panels()` de todas formas, y partir el modelo en dos
	 * caminos por pestaña es la clase de ahorro que se paga en el primer fallo
	 * que solo aparece en una de ellas.
	 *
	 * @param array<string, mixed> $m        Model so far.
	 * @param int                  $event_id Event post ID.
	 * @return array<string, mixed>
	 */
	private static function fill_programme( array $m, int $event_id ): array {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- lectura: qué ficha se edita y qué se busca. Mutar lleva su nonce.
		$m['edit_row']      = absint( wp_unslash( $_GET[ self::ARG_ROW ] ?? 0 ) );
		$m['people_q']      = sanitize_text_field( wp_unslash( (string) ( $_GET[ self::ARG_Q ] ?? '' ) ) );
		$m['people_filter'] = sanitize_text_field( wp_unslash( (string) ( $_GET[ self::ARG_WORKSHOP ] ?? '' ) ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

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
			// Las plazas ocupadas se cuentan por el título del taller, que es lo
			// que trae la inscripción mientras la inscripción no sea de este
			// aplicativo: no hay identificador compartido (ADR-0027). Si el
			// título cambia, deja de cuadrar, y por eso la pantalla dice de
			// dónde sale el número.
			$fila['taken']    = self::seats_taken( $inscritos, (string) $fila['title'] );
			$fila['free']     = $fila['seats'] > 0 ? max( 0, (int) $fila['seats'] - (int) $fila['taken'] ) : null;
			$m['workshops'][] = $fila;
		}

		$m['grid']   = Programme::grid( $event_id );
		$m['venues'] = Programme::venues( $event_id );

		// La papelera de fichas es una sola: ponentes y actividades juntos,
		// porque lo que se busca ahí es «lo que borré sin querer» y no de qué
		// tipo era.
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

	/**
	 * How many of the people signed up chose this workshop.
	 *
	 * @param array<int, array<string, string>> $inscritos Participant rows.
	 * @param string                            $titulo    Workshop title.
	 * @return int
	 */
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

	/**
	 * The speaker or activity the edit form has to open with.
	 *
	 * Vacío cuando se está creando, y vacío también cuando el identificador
	 * pedido no es de este evento: la pantalla enseña el formulario de alta en
	 * vez de un error, que es lo que pasa de verdad cuando alguien vuelve con
	 * el botón de atrás a una ficha ya borrada.
	 *
	 * @param int    $event_id Event post ID.
	 * @param int    $row_id   Requested row.
	 * @param string $panel    Tab being painted.
	 * @return array<string, mixed>
	 */
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

	/**
	 * The human name of a post status.
	 *
	 * @param string $status Post status.
	 * @return string
	 */
	private static function status_label( string $status ): string {
		$objeto = get_post_status_object( $status );
		return null !== $objeto ? (string) $objeto->label : $status;
	}

	/**
	 * One meta value of the event, as a string.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $clave   Meta key.
	 * @return string
	 */
	private static function meta( int $post_id, string $clave ): string {
		return (string) get_post_meta( $post_id, $clave, true );
	}

	/**
	 * What every form field shows: what was typed, or what is stored.
	 *
	 * @param int                   $event_id Event post ID.
	 * @param array<string, string> $tecleado What the failed submit carried.
	 * @return array<string, string>
	 */
	private static function values( int $event_id, array $tecleado ): array {
		$guardado = array(
			// Con 0, `get_the_title()` cae en el post global —que en esta
			// pantalla es la página «Evento» del aplicativo— y el alta abriría
			// con el título ya escrito. Al crear, el título está vacío.
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

		// Lo tecleado manda sobre lo guardado, pero solo en los campos que
		// existen: del POST no entra ninguna clave nueva.
		return array_merge( $guardado, array_intersect_key( $tecleado, $guardado ) );
	}

	/**
	 * The first term of one taxonomy on the event.
	 *
	 * @param int    $event_id Event post ID.
	 * @param string $taxonomy Taxonomy name.
	 * @return int Term ID, or 0.
	 */
	private static function first_term( int $event_id, string $taxonomy ): int {
		$ids = wp_get_post_terms( $event_id, $taxonomy, array( 'fields' => 'ids' ) );
		return is_array( $ids ) ? (int) ( $ids[0] ?? 0 ) : 0;
	}

	/**
	 * The three dropdowns of the classification card.
	 *
	 * @param int $user_id  Who is looking.
	 * @param int $area_now Área the event has now, so it never disappears.
	 * @return array<string, array<int, string>>
	 */
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

	/**
	 * Terms of one taxonomy, optionally narrowed to a handful.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @param int[]  $solo     Term IDs to keep; empty for all of them.
	 * @return array<int, string> term_id => nombre.
	 */
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

	/**
	 * The rows of the sections table.
	 *
	 * @param int  $event_id Event post ID.
	 * @param bool $papelera True for the rows of the trash.
	 * @return array<int, array<string, mixed>>
	 */
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
				// Una página en borrador **también se mira**: estando dentro, con
				// permiso para editarla, WordPress la sirve en previsualización.
				// Lo que cambia es la dirección, no que se pueda ver.
				'view_url'     => 'publish' === (string) $hija->post_status
					? (string) get_permalink( $hija )
					: (string) get_preview_post_link( $hija ),
				'first'        => 0 === $i,
				'last'         => $i === $total - 1,
			);
		}
		return $filas;
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array<string, string>|string $atts Attributes.
	 * @return string
	 */
	public static function render( $atts = array() ): string {
		unset( $atts );
		Assets::enqueue();
		$m = self::model();

		// El selector de medios solo en la pestaña que tiene imágenes: son
		// unos cuantos guiones de WordPress y en las demás pantallas no hay
		// nada que elegir. Quien no puede subir tampoco puede consultar la
		// biblioteca por AJAX, así que a esa persona solo se le enseña
		// «Quitar» y no se carga nada.
		if ( self::PANEL_LOOK === (string) $m['panel'] && true === $m['can_upload'] ) {
			wp_enqueue_media( array( 'post' => (int) $m['event_id'] ) );
		}

		// El aviso va delante de la pantalla y no dentro: cuando hay dueño se
		// pinta como `<dialog open>` y es lo primero que se lee al entrar.
		return EditLock::render( EditLock::claim( (array) $m['lock'] ) ) . EventWorkspaceView::html( $m );
	}
}

// ---- src/Evt/PublicFront/View/PanelParts.php ----
/**
 * Pieces the programme panels share: row actions and the ficha trash.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

use Evt\PublicFront\Assets;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\Shell;

/**
 * Lo que repiten «Ponentes», «Programa» y «Talleres».
 *
 * Está aparte para que el botón de borrar de los tres sea **el mismo botón**:
 * mismo nonce por fila, misma confirmación y misma vuelta a la pestaña desde
 * la que se pulsó. Tres copias se separan a la tercera corrección.
 *
 * Solo pinta: no lee la petición, no consulta, no decide.
 */
final class PanelParts {

	/**
	 * What `wp_kses()` lets through for an inline icon.
	 *
	 * Los iconos los escribe {@see Shell::icon()} y no vienen de fuera, pero
	 * pasan por `wp_kses()` igual: es una lista corta y deja la regla de «toda
	 * la salida escapada» sin excepciones que alguien tenga que recordar.
	 *
	 * @var array<string, array<string, bool>>
	 */
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

	/**
	 * One row action: its own little POST form, with its own nonce.
	 *
	 * @param array<string, mixed> $m         Model.
	 * @param int                  $id        Speaker or activity post ID.
	 * @param string               $op        Operation.
	 * @param string               $icono     Icon name for {@see Shell::icon()}.
	 * @param string               $titulo    What the button does, in Spanish.
	 * @param string               $clases    Button classes.
	 * @param string               $panel     Tab to come back to.
	 * @param bool                 $apagado   Whether the button is disabled.
	 * @param string               $confirmar Question to ask before submitting.
	 * @return string
	 */
	public static function action( array $m, int $id, string $op, string $icono, string $titulo, string $clases, string $panel, bool $apagado = false, string $confirmar = '' ): string {
		$pregunta = '' !== $confirmar ? ' data-evt-confirm="' . esc_attr( $confirmar ) . '"' : '';

		ob_start();
		?>
		<form class="evt-accion" method="post" action=""<?php echo $pregunta; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado arriba. ?>>
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

	/**
	 * An icon link, with the same globe as the icon buttons.
	 *
	 * @param string $url    Where it goes.
	 * @param string $icono  Icon name for {@see Shell::icon()}.
	 * @param string $titulo What it does, in Spanish.
	 * @param string $clases Extra classes.
	 * @return string
	 */
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

	/**
	 * «Papelera (N)» with what was sent to it, when there is something.
	 *
	 * Ponentes y actividades comparten papelera porque lo que se busca ahí es
	 * «lo que borré sin querer», no de qué tipo era.
	 *
	 * @param array<string, mixed> $m     Model.
	 * @param string               $panel Tab to come back to.
	 * @return string
	 */
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
									echo $boton; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
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

	/**
	 * A day written the way it is read out loud.
	 *
	 * @param string $fecha Date in Y-m-d, or empty.
	 * @return string
	 */
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

	/**
	 * The time slot of an activity: «09:30 – 11:00», or just the start.
	 *
	 * @param string $start Start time.
	 * @param string $end   End time.
	 * @return string
	 */
	public static function slot( string $start, string $end ): string {
		if ( '' === $start ) {
			return '—';
		}
		return '' === $end ? $start : $start . ' – ' . $end;
	}
}

// ---- src/Evt/PublicFront/View/EventSpeakersPanel.php ----
/**
 * The «Ponentes» panel of the event workshop.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

use Evt\PublicFront\Assets;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\Shell;

/**
 * Los ponentes de este evento: su ficha, su orden y su foto.
 *
 * Son fichas **de este evento** y no de un catálogo compartido: la repetición
 * real entre eventos está entre el 5 % y el 12 %, y en un evento terminado no
 * se quiere que la foto y la biografía cambien solas (ADR-0021).
 *
 * Solo pinta: no lee la petición, no consulta, no decide.
 */
final class EventSpeakersPanel {

	/**
	 * Paint the panel.
	 *
	 * @param array<string, mixed> $m What EventWorkspace::model() returned.
	 * @return string
	 */
	public static function html( array $m ): string {
		$filas = (array) $m['speakers'];

		ob_start();
		?>
		<p class="evt-sub">
			Quién interviene en este evento. El orden es el que sale en la página
			de ponentes y en el programa. Cada ficha es de este evento: editarla
			no toca la de ninguna otra edición.
		</p>

		<?php echo PanelParts::trash_link( $m, EventWorkspace::PANEL_SPEAKERS ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		<?php echo self::form( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>

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
							<?php echo self::row( $m, (array) $fila, (int) $indice + 1 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The form that adds a speaker, or edits the one asked for.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
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

			<?php echo self::photo( $m, $valores ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>

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

	/**
	 * The photo field: what there is, and how to change it.
	 *
	 * Sin selector de medios cuando quien mira no puede subir: se le enseña lo
	 * que hay y nada más, porque la biblioteca tampoco se le abriría.
	 *
	 * @param array<string, mixed> $m       Model.
	 * @param array<string, mixed> $valores Speaker being edited.
	 * @return string
	 */
	private static function photo( array $m, array $valores ): string {
		$url = (string) ( $valores['photo'] ?? '' );
		$id  = (int) ( $valores['photo_id'] ?? 0 );

		ob_start();
		?>
		<div class="evt-form-campo evt-media">
			<span class="evt-media-rotulo">Foto</span>
			<div class="evt-media-ficha">
				<?php if ( '' !== $url ) : ?>
					<img class="evt-media-miniatura" src="<?php echo esc_url( $url ); ?>" alt="" width="96" height="96" />
				<?php else : ?>
					<p class="evt-media-vacia">Sin foto. La ficha se ve igual, con las iniciales.</p>
				<?php endif; ?>
				<?php if ( true === $m['can_upload'] ) : ?>
					<div class="evt-media-botones">
						<button type="button" class="<?php echo esc_attr( Assets::button_class() . ' evt-mini' ); ?>"
							data-evt-media="evt-sp-photo" data-evt-media-titulo="Elegir la foto del ponente">Elegir imagen</button>
						<button type="button" class="<?php echo esc_attr( Assets::button_class() . ' evt-mini' ); ?>"
							data-evt-media-quitar="evt-sp-photo">Quitar</button>
					</div>
				<?php endif; ?>
			</div>
			<input type="hidden" id="evt-sp-photo" name="evt_sp_photo" value="<?php echo esc_attr( (string) $id ); ?>" />
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * One speaker row, with its actions.
	 *
	 * @param array<string, mixed> $m       Model.
	 * @param array<string, mixed> $fila    Speaker row.
	 * @param int                  $posicion 1-based position.
	 * @return string
	 */
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
					echo $editar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
					?>
					<?php echo $subir; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
					<?php echo $bajar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
					<?php echo $borrar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				</span>
			</td>
		</tr>
		<?php
		return (string) ob_get_clean();
	}
}

// ---- src/Evt/PublicFront/View/EventProgrammePanel.php ----
/**
 * The «Programa» panel of the event workshop: the parrilla.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

use Evt\PublicFront\Assets;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\Shell;

/**
 * La parrilla del evento: **por día y, dentro del día, por sede**.
 *
 * Esa forma es la decisión de ADR-0024: la sede es un dato de cada actividad y
 * un mismo día puede tener dos —la mañana en una sede y la tarde en un centro
 * educativo—. Un día con una sola sede se pinta como un bloque **y sin rótulo
 * de sede**, que no aportaría nada; uno con dos, como dos bloques con su
 * rótulo. Y no hay ninguna pantalla donde «dar de alta una sede»: la lista
 * sale de las actividades, y añadir una es escribirla.
 *
 * Solo pinta: no lee la petición, no consulta, no decide.
 */
final class EventProgrammePanel {

	/**
	 * Paint the panel.
	 *
	 * @param array<string, mixed> $m What EventWorkspace::model() returned.
	 * @return string
	 */
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

		<?php echo PanelParts::trash_link( $m, EventWorkspace::PANEL_PROGRAMME ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		<?php echo self::form( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>

		<?php if ( array() === $dias ) : ?>
			<div class="evt-tabla-caja">
				<p class="evt-vacio">El programa está vacío. Añada la primera actividad arriba.</p>
			</div>
		<?php else : ?>
			<?php foreach ( $dias as $dia ) : ?>
				<?php echo self::day( $m, (array) $dia ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			<?php endforeach; ?>
		<?php endif; ?>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * One day of the parrilla, with its one or more sedes.
	 *
	 * @param array<string, mixed> $m   Model.
	 * @param array<string, mixed> $dia One day of $m['grid'].
	 * @return string
	 */
	private static function day( array $m, array $dia ): string {
		$sedes = (array) $dia['venues'];
		// Con una sola sede el rótulo sobra: repetir el mismo sitio encima de
		// cada bloque es ruido, y ADR-0024 lo dice con esas palabras.
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
								<?php echo self::row( $m, (array) $fila ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endforeach; ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * One activity row.
	 *
	 * @param array<string, mixed> $m    Model.
	 * @param array<string, mixed> $fila Activity row.
	 * @return string
	 */
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
					echo $editar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
					?>
					<?php echo $borrar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				</span>
			</td>
		</tr>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The form that adds an activity, or edits the one asked for.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
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

			<?php echo self::speakers( $m, $suyos ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>

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

	/**
	 * Which speakers of this event take part in this activity.
	 *
	 * Casillas y no un desplegable múltiple: se marcan varias sin saber que
	 * hay que dejar pulsada una tecla, que es el fallo de siempre del
	 * `<select multiple>`.
	 *
	 * @param array<string, mixed> $m     Model.
	 * @param int[]                $suyos Speaker IDs already linked.
	 * @return string
	 */
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

// ---- src/Evt/PublicFront/View/EventWorkshopsPanel.php ----
/**
 * The «Talleres» panel of the event workshop.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

use Evt\PublicFront\Assets;
use Evt\PublicFront\EventWorkspace;

/**
 * Los talleres del evento y **cuántas plazas quedan**.
 *
 * Un taller no es otro tipo de contenido: es una actividad del programa cuyo
 * tipo es «taller», y por eso se crea y se edita en «Programa». Aquí se ven
 * juntos, con su aforo, porque esa es la pregunta que se hace el día antes:
 * «¿cuál se ha llenado?».
 *
 * Solo pinta: no lee la petición, no consulta, no decide.
 */
final class EventWorkshopsPanel {

	/**
	 * Paint the panel.
	 *
	 * @param array<string, mixed> $m What EventWorkspace::model() returned.
	 * @return string
	 */
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
			<?php echo self::counters( $m, $filas ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
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
							<?php echo self::row( $m, (array) $fila ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
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

	/**
	 * The three numbers that answer «¿cómo va esto?».
	 *
	 * @param array<string, mixed> $m     Model.
	 * @param array<int, mixed>    $filas Workshop rows.
	 * @return string
	 */
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

	/**
	 * One workshop row.
	 *
	 * @param array<string, mixed> $m    Model.
	 * @param array<string, mixed> $fila Workshop row.
	 * @return string
	 */
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
			<td data-rotulo="Plazas"><?php echo self::seats( $fila ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></td>
			<td data-rotulo="Acciones">
				<span class="evt-acciones">
					<?php
					$editar = PanelParts::icon_link(
						EventWorkspace::url( (int) $m['event_id'], EventWorkspace::PANEL_PROGRAMME, array( EventWorkspace::ARG_ROW => $id ) ),
						'lapiz',
						'Editar este taller en el programa'
					);
					echo $editar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
					?>
				</span>
			</td>
		</tr>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * How full one workshop is, written so it reads without the table head.
	 *
	 * @param array<string, mixed> $fila Workshop row.
	 * @return string
	 */
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

// ---- src/Evt/PublicFront/View/EventParticipantsPanel.php ----
/**
 * The «Participantes» panel of the event workshop.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

use Evt\PublicFront\Assets;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\Participants;

/**
 * Quién se ha inscrito: filtro y exportación a CSV.
 *
 * **De dónde salen las filas no lo decide esta pantalla.** Los participantes
 * son de este aplicativo y se gestionarán aquí, con formulario de inscripción
 * propio; mientras ese formulario no exista, las filas entran por el filtro
 * `evt_participants` (ADR-0027). Cuando nadie contesta, aquí no se finge una
 * lista vacía: se dice dónde se abre la inscripción, en vez de dejar una tabla
 * sin filas que parece un fallo.
 *
 * Solo pinta: no lee la petición, no consulta, no decide.
 */
final class EventParticipantsPanel {

	/**
	 * Paint the panel.
	 *
	 * @param array<string, mixed> $m What EventWorkspace::model() returned.
	 * @return string
	 */
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

		<?php echo self::counters( $m, $filas ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		<?php echo self::filter( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>

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
										<?php echo esc_html( '' !== (string) ( $fila[ $clave ] ?? '' ) ? (string) $fila[ $clave ] : '—' ); ?>
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

	/**
	 * How many there are, and how many the filter left.
	 *
	 * @param array<string, mixed> $m     Model.
	 * @param array<int, mixed>    $filas Filtered rows.
	 * @return string
	 */
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

	/**
	 * The filter box and the export button.
	 *
	 * El filtro va por GET —es una consulta, y así la dirección filtrada se
	 * puede guardar y compartir— y la exportación por POST con su nonce,
	 * porque un enlace que descarga la lista entera de personas inscritas no
	 * debe poder pegarse en un correo.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function filter( array $m ): string {
		$op   = EventWorkspace::OP_EXPORT;
		$base = EventWorkspace::url( (int) $m['event_id'], EventWorkspace::PANEL_PEOPLE );

		// El formulario de filtro es un GET a esta misma pantalla: lo que ya
		// viaja en la dirección se vuelve a poner como campos ocultos.
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

	/**
	 * What to say when no source answered.
	 *
	 * No es una tabla vacía: una tabla vacía se lee como «no se ha inscrito
	 * nadie», y lo que pasa es que las inscripciones están en otro sitio.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
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

// ---- src/Evt/PublicFront/View/EventSignupPanel.php ----
/**
 * The «Inscripción» tab of the event workshop.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

use Evt\PublicFront\EventWorkspace;

/**
 * Dónde quien organiza abre la inscripción y redacta sus preguntas.
 *
 * Solo pinta: no lee la petición, no consulta y no decide.
 *
 * La pantalla es corta a propósito, y eso es la decisión: **no es un
 * constructor de formularios**. El núcleo del formulario —documento, nombre,
 * apellidos, correo, teléfono, centro y consentimiento— no sale aquí porque no
 * se edita: está en código, medido, y es el mismo en todos los eventos
 * (ADR-0031). Lo único que se redacta son las tres o cuatro preguntas propias
 * del evento, y una pregunta tiene cuatro cosas: rótulo, tipo, opciones y si es
 * obligatoria. Ni condiciones, ni reglas.
 *
 * Sin JavaScript: las filas son campos paralelos y la última va en blanco. Se
 * escribe encima y se guarda; se borra el rótulo y la pregunta se va.
 */
final class EventSignupPanel {

	/**
	 * The tab.
	 *
	 * @param array<string, mixed> $m What EventWorkspace::model() decided.
	 * @return string
	 */
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

	/**
	 * The two windows: the signup one and the workshop one.
	 *
	 * @param array<string, mixed> $a Signup settings.
	 * @return string
	 */
	private static function windows( array $a ): string {
		$html  = '<fieldset class="evt-campos"><legend>Plazos</legend>';
		$html .= self::toggle( 'evt_signup_open', 'La inscripción está abierta', (bool) $a['open'] );
		$html .= '<p class="evt-ayuda">Mientras esté cerrada, la página de inscripción lo dice y no acepta a nadie.</p>';

		// El plazo del taller es propio porque se abre cuando el programa está
		// cerrado, y eso casi nunca coincide con abrir la inscripción (ADR-0033).
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

	/**
	 * The two consent texts.
	 *
	 * @param array<string, mixed> $a Signup settings.
	 * @return string
	 */
	private static function consent( array $a ): string {
		$html  = '<fieldset class="evt-campos"><legend>Protección de datos</legend>';
		$html .= '<p class="evt-campo"><label for="evt-consent-privacidad">Información sobre el tratamiento de sus datos</label>'
			. '<textarea id="evt-consent-privacidad" name="evt_consent_privacy" rows="6">'
			. esc_textarea( (string) $a['consent_privacy'] ) . '</textarea></p>';
		$html .= '<p class="evt-campo"><label for="evt-consent-imagen">Consentimiento informado</label>'
			. '<textarea id="evt-consent-imagen" name="evt_consent_image" rows="6">'
			. esc_textarea( (string) $a['consent_image'] ) . '</textarea></p>';

		// Cambiar un texto sube la versión y **no reescribe** la que ya aceptó
		// nadie: por eso el número está a la vista (ADR-0020).
		$html .= '<p class="evt-ayuda">Versión actual: <strong>v' . (int) $a['consent_version'] . '</strong>. '
			. 'Cambiar cualquiera de los dos textos crea una versión nueva; lo que ya aceptó alguien no se reescribe, '
			. 'y en la lista de participantes se ve qué versión aceptó cada persona.</p>';

		return $html . '</fieldset>';
	}

	/**
	 * The question list, plus one blank row to add another.
	 *
	 * @param array<int, array<string, mixed>> $preguntas Questions.
	 * @param array<string, string>            $tipos     Question types.
	 * @param bool                             $locked    Whether anybody signed up already.
	 * @return string
	 */
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
		// La fila en blanco del final es cómo se añade una pregunta sin
		// JavaScript: se escribe su rótulo y se guarda. Si se deja vacía, no
		// añade nada, porque una pregunta sin rótulo se cae al normalizar.
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

	/**
	 * One question row.
	 *
	 * @param int                   $i         Row index.
	 * @param array<string, mixed>  $p         Question.
	 * @param array<string, string> $tipos     Question types.
	 * @return string
	 */
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

	/**
	 * One switch.
	 *
	 * @param string $nombre Field name.
	 * @param string $rotulo Label.
	 * @param bool   $puesto Whether it is on.
	 * @return string
	 */
	private static function toggle( string $nombre, string $rotulo, bool $puesto ): string {
		return sprintf(
			'<p class="evt-campo evt-campo--casilla"><label for="%1$s"><input type="checkbox" id="%1$s" name="%1$s" value="1"%2$s> %3$s</label></p>',
			esc_attr( $nombre ),
			checked( true, $puesto, false ),
			esc_html( $rotulo )
		);
	}
}

// ---- src/Evt/PublicFront/View/EventSectionsPanel.php ----
/**
 * The «Secciones» panel of the event workshop.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

use Evt\PublicFront\Assets;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\Shell;

/**
 * La pantalla que hoy no existe: las páginas satélite de un evento, en una
 * tabla, con su orden y sus acciones.
 *
 * Hoy, para saber qué páginas tiene un evento hay que abrir el menú del
 * propio evento en la web pública y contarlas, y para cambiar el orden hay
 * que reabrir el formulario enorme de cada una y teclear a mano su número de
 * orden. Aquí el orden es `menu_order` y se cambia con dos flechas.
 *
 * Solo pinta: no lee la petición, no consulta, no decide.
 */
final class EventSectionsPanel {

	/**
	 * What `wp_kses()` lets through for an inline icon.
	 *
	 * @var array<string, array<string, bool>>
	 */
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

	/**
	 * The publish state of one section, as a switch.
	 *
	 * El mismo interruptor que el listado de eventos, y por el mismo motivo:
	 * de un vistazo se ve si la página está en el menú del evento, y se cambia
	 * tocándolo. Sin JavaScript queda el botón de al lado.
	 *
	 * @param array<string, mixed> $m    Model.
	 * @param array<string, mixed> $fila One row.
	 * @return string
	 */
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

	/**
	 * Paint the panel: the sections of the event, or its trash.
	 *
	 * @param array<string, mixed> $m What EventWorkspace::model() returned.
	 * @return string
	 */
	public static function html( array $m ): string {
		return true === $m['trash'] ? self::trash( $m ) : self::live( $m );
	}

	/**
	 * The sections of the event: what is not in the trash.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function live( array $m ): string {
		$filas = (array) $m['sections'];

		ob_start();
		?>
		<p class="evt-sub">
			Las páginas de este evento, en el orden en que salen en su menú. Cada
			una es una página propia con su dirección: al despublicarla desaparece
			del menú, pero no se pierde nada de lo escrito.
		</p>

		<?php echo self::trash_link( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		<?php echo self::add_form( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>

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
							<?php echo self::row( $m, (array) $fila ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * «Papelera (N)»: el enlace a lo que se envió a ella, si hay algo.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function trash_link( array $m ): string {
		$cuantas = count( (array) $m['trashed'] );
		if ( 0 === $cuantas ) {
			return '';
		}

		return '<p class="evt-acciones"><a href="'
			. esc_url( EventWorkspace::url( (int) $m['event_id'], EventWorkspace::PANEL_SECTIONS, array( EventWorkspace::ARG_TRASH => 1 ) ) )
			. '">' . esc_html( sprintf( 'Papelera (%d)', $cuantas ) ) . '</a></p>';
	}

	/**
	 * The trash of the event: what was sent to it, and how to bring it back.
	 *
	 * El borrado definitivo no está aquí a propósito: lo hace quien pueda desde
	 * el escritorio de WordPress. Desde el aplicativo nada se destruye.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
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
										echo $boton; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
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

	/**
	 * «Añadir sección»: se elige el tipo y se abre el formulario de la sección.
	 *
	 * Va por GET porque no muta nada: la sección se crea al guardar, en su
	 * propia pantalla y con su propio nonce.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function add_form( array $m ): string {
		$base = (string) $m['section_url'];
		if ( '' === $base ) {
			return '<p class="' . esc_attr( Assets::alert_class( 'warning' ) ) . '">'
				. esc_html( 'Todavía no existe la página del formulario de secciones, así que no se pueden añadir ni editar. Lo resuelve quien despliega el aplicativo.' )
				. '</p>';
		}

		// Sin enlaces bonitos el permalink es un `?page_id=<n>`, y un
		// `<form method="get">` tira lo que ya lleva la dirección: se vuelve a
		// poner como campos ocultos.
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

	/**
	 * One row of the table, with its actions.
	 *
	 * @param array<string, mixed> $m    Model.
	 * @param array<string, mixed> $fila One row of $m['sections'].
	 * @return string
	 */
	private static function row( array $m, array $fila ): string {
		$id     = (int) $fila['id'];
		$titulo = self::title_of( $fila );
		$mini   = Assets::button_class() . ' evt-mini';

		// Los botones se arman antes de la plantilla, cada uno con su nonce.
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
					<?php echo $estado; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
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
					echo $acciones; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
					?>
					<?php echo $subir; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
					<?php echo $bajar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
					<?php echo $borrar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				</span>
			</td>
		</tr>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * One row action: its own little POST form, with its own nonce.
	 *
	 * @param array<string, mixed> $m         Model.
	 * @param int                  $id        Satellite page ID.
	 * @param string               $op        Operation.
	 * @param string               $icono     Icon name for {@see Shell::icon()}.
	 * @param string               $titulo    What it does, in Spanish.
	 * @param string               $clases    Button classes.
	 * @param bool                 $apagado   Whether the button is disabled.
	 * @param string               $confirmar Question to ask before submitting.
	 * @return string
	 */
	private static function action_form( array $m, int $id, string $op, string $icono, string $titulo, string $clases, bool $apagado = false, string $confirmar = '' ): string {
		$pregunta = '' !== $confirmar ? ' data-evt-confirm="' . esc_attr( $confirmar ) . '"' : '';

		ob_start();
		?>
		<form class="evt-accion" method="post" action=""<?php echo $pregunta; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado arriba. ?>>
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

	/**
	 * The title of a section, or a stand-in when it has none.
	 *
	 * @param array<string, mixed> $fila One row.
	 * @return string
	 */
	private static function title_of( array $fila ): string {
		$titulo = trim( (string) $fila['title'] );
		return '' !== $titulo ? $titulo : '(sin título)';
	}
}

// ---- src/Evt/PublicFront/View/EventDataPanel.php ----
/**
 * The «Datos» panel of the event workshop.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

use Evt\Meta\EventMetaKeys;
use Evt\PublicFront\Assets;
use Evt\PublicFront\EventWorkspace;

/**
 * Qué es el evento: identidad, cuándo y dónde, clasificación e inscripción.
 *
 * Hoy son cinco secciones sueltas dentro de un formulario de 139 campos que
 * sirve además para cada página satélite. Aquí son cuatro tarjetas, con lo
 * que va junto junto, y bajo cada campo una línea en castellano llano que
 * dice para qué sirve: sin ella, «Lema» y «Hashtag» se rellenan a ojo y luego
 * salen en la cabecera del evento.
 *
 * Solo pinta: no lee la petición, no consulta, no decide.
 */
final class EventDataPanel {

	/**
	 * Paint the panel.
	 *
	 * @param array<string, mixed> $m What EventWorkspace::model() returned.
	 * @return string
	 */
	public static function html( array $m ): string {
		$v      = (array) $m['values'];
		$listas = (array) $m['terms'];

		// Los desplegables se arman antes de la plantilla: dentro de cada
		// ayudante la salida ya va escapada, y así cada `echo` de la plantilla
		// es de una sola línea.
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
					<div><?php echo $sel_area; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></div>
					<div><?php echo $sel_tipo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></div>
					<div><?php echo $sel_curso; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></div>
				</div>
			</fieldset>

			<?php echo $inscripcion; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>

			<p class="evt-acciones">
				<button class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" type="submit">
					<?php echo esc_html( true === ( $m['nuevo'] ?? false ) ? 'Crear el evento' : 'Guardar los datos' ); ?>
				</button>
			</p>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The «Inscripción» card.
	 *
	 * @param array<string, string> $v Field values.
	 * @return string
	 */
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

	/**
	 * One taxonomy dropdown, with its help line.
	 *
	 * @param string             $id      Field id.
	 * @param string             $nombre  Field name.
	 * @param string             $rotulo  Label.
	 * @param array<int, string> $terminos term_id => nombre.
	 * @param int                $elegido Selected term ID.
	 * @param string             $ayuda   Help text.
	 * @return string
	 */
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

// ---- src/Evt/PublicFront/View/EventAppearancePanel.php ----
/**
 * The «Apariencia» panel of the event workshop.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

use Evt\Meta\EventMetaKeys;
use Evt\PublicFront\Assets;
use Evt\PublicFront\EventWorkspace;

/**
 * Cómo se ve el evento: los dos colores, las dos tipografías y las imágenes.
 *
 * Hoy esto son sesenta muestras de color en una parrilla, un desplegable con
 * las 1.461 familias de Google Fonts y veinticinco siluetas de separador, y
 * para ver el resultado hay que guardar y abrir la página en otra pestaña.
 * Aquí son un selector de color nativo con su hexadecimal al lado, seis
 * tipografías, seis separadores y una vista previa de la cabecera que se mueve
 * mientras se elige —la pinta el guion de la espina leyendo los `name` de
 * estos mismos controles—.
 *
 * Solo pinta: no lee la petición, no consulta, no decide.
 */
final class EventAppearancePanel {

	/**
	 * A stand-in portrait, so the shape of the photographs can be previewed.
	 *
	 * Va como `data:` y no como fichero porque en producción no hay
	 * repositorio en disco desde el que servir una imagen: el único artefacto
	 * es el bundle de Code Snippets.
	 */
	private const SHAPE_SAMPLE = 'data:image/svg+xml;charset=utf8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2272%22%20height%3D%2272%22%3E%3Crect%20width%3D%2272%22%20height%3D%2272%22%20fill%3D%22%23c3cad2%22%2F%3E%3Ccircle%20cx%3D%2236%22%20cy%3D%2226%22%20r%3D%2213%22%20fill%3D%22%238a97a6%22%2F%3E%3Cpath%20d%3D%22M8%2072c0-16%2012-26%2028-26s28%2010%2028%2026z%22%20fill%3D%22%238a97a6%22%2F%3E%3C%2Fsvg%3E';

	/**
	 * Paint the panel.
	 *
	 * @param array<string, mixed> $m What EventWorkspace::model() returned.
	 * @return string
	 */
	public static function html( array $m ): string {
		$v      = (array) $m['values'];
		$subir  = (bool) $m['can_upload'];
		$medios = (array) $m['media'];

		// Sin color guardado, el selector nativo caería a negro y pintaría una
		// cabecera que nadie eligió: se arranca del azul del sitio.
		$v[ EventMetaKeys::HEADER_BG ]   = self::color( (string) $v[ EventMetaKeys::HEADER_BG ], '#1b4f8a' );
		$v[ EventMetaKeys::HEADER_TEXT ] = self::color( (string) $v[ EventMetaKeys::HEADER_TEXT ], '#ffffff' );

		// Los trozos se arman antes de la plantilla y se escapan dentro de cada
		// ayudante: así la plantilla es HTML de leer y cada `echo` es de una
		// sola línea, que es lo que el linter sabe comprobar.
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

			<?php echo $vista; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>

			<fieldset class="evt-tarjeta">
				<legend>Colores de la cabecera</legend>
				<p>Los dos colores de la banda de arriba de todas las páginas del evento. Elíjalos con contraste: el texto tiene que leerse sobre el fondo.</p>

				<div class="evt-form-fila">
					<div><?php echo $color_bg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></div>
					<div><?php echo $color_txt; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></div>
				</div>
			</fieldset>

			<fieldset class="evt-tarjeta">
				<legend>Tipografías</legend>
				<p>Dos y no más: una para los títulos y otra para el texto. «La del tema» no carga ninguna fuente y es la opción más rápida de cargar.</p>

				<div class="evt-form-fila">
					<div><?php echo $sel_titulo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></div>
					<div><?php echo $sel_cuerpo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></div>
				</div>
			</fieldset>

			<fieldset class="evt-tarjeta">
				<legend>Forma y remate</legend>
				<p>Detalles que se aplican a todas las páginas del evento.</p>

				<div class="evt-form-fila">
					<div><?php echo $sel_forma; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></div>
					<div><?php echo $sel_sep; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></div>
				</div>
			</fieldset>

			<fieldset class="evt-tarjeta">
				<legend>Imágenes</legend>
				<p>
					Las tres salen de la biblioteca de medios del sitio: «Elegir imagen» abre la
					biblioteca, donde puede reutilizar una que ya esté subida o subir una nueva
					(JPG, PNG, WEBP o GIF). Nada cambia hasta que pulse «Guardar la apariencia».
				</p>

				<?php if ( ! $subir ) : ?>
					<p class="<?php echo esc_attr( Assets::alert_class( 'warning' ) ); ?>">
						Su perfil no puede subir ficheros, así que las imágenes solo se pueden quitar. Pídalo a quien administre el aplicativo.
					</p>
				<?php endif; ?>

				<?php echo $img_logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<?php echo $img_cartel; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<?php echo $img_destacada; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			</fieldset>

			<p class="evt-acciones">
				<button class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" type="submit">Guardar la apariencia</button>
			</p>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The live preview of the event header.
	 *
	 * El guion de la espina la repinta al vuelo: busca `[data-evt-preview]`
	 * dentro de este mismo formulario y lee los controles por su `name`.
	 * Sin guion se sigue viendo, con lo último guardado.
	 *
	 * @param array<string, mixed>  $m Model.
	 * @param array<string, string> $v Field values, with the display defaults.
	 * @return string
	 */
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
					<?php // La muestra es una silueta fija dentro de la propia hoja: escapada con esc_attr porque es una constante de esta clase, no una URL de nadie. ?>
					<img src="<?php echo esc_attr( self::SHAPE_SAMPLE ); ?>" width="72" height="72" alt="Ejemplo de la forma de las fotografías" />
				</span>
				Así se verá la cabecera del evento y así se recortarán las fotos de las personas. Es una muestra: no se guarda nada hasta que pulse «Guardar la apariencia».
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * A stored colour, or the fallback when there is none.
	 *
	 * @param string $valor    Stored value.
	 * @param string $fallback Colour to use instead.
	 * @return string
	 */
	private static function color( string $valor, string $fallback ): string {
		$limpio = sanitize_hex_color( $valor );
		return is_string( $limpio ) && '' !== $limpio ? $limpio : $fallback;
	}

	/**
	 * What is known about one image of the event.
	 *
	 * El nombre es el del fichero y no el título del adjunto: en una
	 * biblioteca de 4.597 adjuntos, «Captura de pantalla 2025-11-04» no
	 * distingue nada y `cartel-jornadas-2026.jpg` sí.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array{id:int, url:string, name:string, width:int, height:int}
	 */
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
			// El identificador guardado apunta a un adjunto que ya no está:
			// se enseña el hueco, no una imagen rota.
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

	/**
	 * A colour: the native picker and its hexadecimal, writing the same value.
	 *
	 * El que se envía es el campo de texto: quien tenga el guion bloqueado, o
	 * un navegador sin selector de color, sigue pudiendo teclear el código.
	 *
	 * @param string $id     Field id of the hexadecimal input.
	 * @param string $nombre Field name (the meta key).
	 * @param string $rotulo Label.
	 * @param string $valor  Current colour.
	 * @param string $ayuda  Help text.
	 * @return string
	 */
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

	/**
	 * One dropdown out of a closed vocabulary.
	 *
	 * @param string                $id      Field id.
	 * @param string                $nombre  Field name.
	 * @param string                $rotulo  Label.
	 * @param array<string, string> $lista   slug => etiqueta.
	 * @param string                $elegido Current value.
	 * @param string                $ayuda   Help text.
	 * @return string
	 */
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

	/**
	 * One image: what there is now, how to reuse one, and how to remove it.
	 *
	 * Lo que viaja en el POST es el `hidden` con el identificador del adjunto
	 * (`evt_logo_id`, `evt_poster_id`, `evt_featured_id`), que rellena el
	 * selector de medios de WordPress. Los botones nacen ocultos y los enseña
	 * el guion: sin guion no hay ninguno que no haga nada, y el respaldo del
	 * `<noscript>` —subir un fichero, o marcar la casilla de quitar— sigue
	 * siendo la forma de cambiar la imagen.
	 *
	 * @param string               $campo    Field prefix, e.g. `evt_logo`.
	 * @param string               $rotulo   Label.
	 * @param array<string, mixed> $imagen   What model() knows about it.
	 * @param string               $ayuda    Help text.
	 * @param bool                 $can_load Whether this person may upload files.
	 * @return string
	 */
	private static function image_field( string $campo, string $rotulo, array $imagen, string $ayuda, bool $can_load ): string {
		$url    = (string) ( $imagen['url'] ?? '' );
		$id     = sanitize_html_class( $campo );
		$puesta = '' !== $url;
		$ancho  = (int) ( $imagen['width'] ?? 0 );
		$alto   = (int) ( $imagen['height'] ?? 0 );

		ob_start();
		?>
		<div class="evt-form-campo evt-media" data-evt-media data-evt-media-title="<?php echo esc_attr( $rotulo ); ?>">
			<span class="evt-media-rotulo"><?php echo esc_html( $rotulo ); ?></span>

			<?php // Lo único que se envía: el identificador del adjunto. El servidor lo vuelve a comprobar. ?>
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

			<p class="evt-acciones evt-media-botones" data-evt-media-actions hidden>
				<?php if ( $can_load ) : ?>
					<button type="button" class="<?php echo esc_attr( Assets::button_class() ); ?>"
						data-evt-media-pick aria-label="<?php echo esc_attr( 'Elegir imagen para: ' . $rotulo ); ?>">
						Elegir imagen
					</button>
				<?php endif; ?>
				<button type="button" class="<?php echo esc_attr( Assets::button_class() ); ?>"
					data-evt-media-clear aria-label="<?php echo esc_attr( 'Quitar la imagen de: ' . $rotulo ); ?>"
					<?php echo esc_attr( $puesta ? '' : 'hidden' ); ?>>
					Quitar
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

// ---- src/Evt/PublicFront/View/EventCodePanel.php ----
/**
 * The «Código» panel of the event workshop.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

use Evt\Meta\EventMetaKeys;
use Evt\PublicFront\Assets;
use Evt\PublicFront\CodeEditor;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\Shell;

/**
 * El CSS y el JavaScript del evento: los del raíz, los de todas sus páginas.
 *
 * Los dos campos no se pintan igual porque no son la misma cosa. El CSS es un
 * campo corriente de la pestaña, como el color de la cabecera de «Apariencia»:
 * lo escribe también el área, acotada a la suya. El JavaScript va en el
 * recuadro amarillo —la convención del aplicativo para lo que solo ve quien
 * administra, {@see Shell::admin_box()}—, porque se ejecuta en el navegador de
 * cada visitante y esa es la única raya que el área no cruza.
 *
 * Quien no ve un campo no se queda con la duda de si falta algo: en su sitio se
 * dice en una línea por qué no está. Un hueco callado se lee como una avería.
 *
 * Solo pinta: no lee la petición, no consulta, no decide.
 */
final class EventCodePanel {

	/**
	 * Why the yellow box is there, for the JavaScript field.
	 */
	private const WHY_JS = 'Este recuadro solo lo ve quien administra el aplicativo. El CSS de arriba lo escribe también quien organiza el evento; esto no, porque no cambia cómo se ve una página: ejecuta un programa en el navegador de quien la visite.';

	/**
	 * Paint the panel.
	 *
	 * @param array<string, mixed> $m What EventWorkspace::model() returned.
	 * @return string
	 */
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

			<?php echo $bloque_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			<?php echo $bloque_js; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>

			<?php if ( $css_ok || $js_ok ) : ?>
				<p class="evt-acciones">
					<button class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" type="submit">Guardar el código</button>
				</p>
			<?php endif; ?>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The CSS field, as a plain card of the tab.
	 *
	 * @param string $valor What is stored, raw.
	 * @return string
	 */
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
			<?php echo $campo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		</fieldset>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The JavaScript field, in its yellow box, with the warning it deserves.
	 *
	 * @param string $valor What is stored, raw.
	 * @return string
	 */
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

	/**
	 * What is said instead of the CSS field when it is not allowed.
	 *
	 * Es el caso raro —el CSS lo escriben el área y la administración—, así que
	 * basta con decirlo y no repetir por qué.
	 *
	 * @return string
	 */
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

	/**
	 * The one line that replaces the JavaScript field for whoever may not write it.
	 *
	 * Una línea y no un recuadro amarillo: el amarillo es para lo que se ve, y
	 * aquí justamente no hay nada que ver. Lo que hace falta es que quien
	 * organiza el evento sepa que la pestaña está completa y que el campo que
	 * no está no le falta a nadie.
	 *
	 * @return string
	 */
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

// ---- src/Evt/PublicFront/View/EventWorkspaceView.php ----
/**
 * The event workshop, painted from what EventWorkspace::model() decided.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

use Evt\PublicFront\Assets;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\Shell;
use Evt\Taxonomy\EventTaxonomies;

/**
 * Solo pinta: no lee la petición, no consulta, no decide.
 *
 * Esta clase es el marco del taller —la cabecera del evento, las pestañas
 * internas y el aviso de lo último que se hizo— y le pasa el turno al panel
 * que toque.
 */
final class EventWorkspaceView {

	/**
	 * Paint the workshop.
	 *
	 * @param array<string, mixed> $m What EventWorkspace::model() returned.
	 * @return string
	 */
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

		// El alta es el taller con una sola pestaña: no hay páginas, ni
		// ponentes, ni programa que enseñar de un evento que todavía no
		// existe, y una fila de pestañas apagadas solo invita a pulsarlas.
		if ( true === $m['nuevo'] ) {
			return self::new_event( $m );
		}

		ob_start();
		echo self::head( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		echo self::tabs( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.

		if ( '' !== (string) $flash['texto'] ) {
			echo Shell::notice( (string) $flash['tipo'], (string) $flash['texto'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		}

		echo self::archived_notice( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.

		// El interruptor va fuera de lo que se apaga, y antes: si estuviera
		// dentro del panel bloqueado, marcar un evento sería un viaje sin
		// vuelta ni siquiera para quien administra.
		if ( EventWorkspace::PANEL_SETTINGS === $panel ) {
			echo self::archive_switch( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		}

		// Solo lectura: un `fieldset` desactivado apaga de una vez todos los
		// controles que tenga dentro —también los de los formularios que hay
		// en él—, así que ningún panel tiene que enterarse de nada. Lo que se
		// puede seguir haciendo son enlaces, y esos no se tocan: entrar,
		// consultar y exportar sigue funcionando.
		$cerrado = true !== $m['can_edit'];
		if ( $cerrado ) {
			echo '<fieldset class="evt-solo-lectura" disabled><legend class="screen-reader-text">Evento en solo lectura</legend>';
		}

		if ( EventWorkspace::PANEL_SPEAKERS === $panel ) {
			echo EventSpeakersPanel::html( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		} elseif ( EventWorkspace::PANEL_PROGRAMME === $panel ) {
			echo EventProgrammePanel::html( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		} elseif ( EventWorkspace::PANEL_WORKSHOPS === $panel ) {
			echo EventWorkshopsPanel::html( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		} elseif ( EventWorkspace::PANEL_SIGNUP === $panel ) {
			echo EventSignupPanel::html( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		} elseif ( EventWorkspace::PANEL_PEOPLE === $panel ) {
			echo EventParticipantsPanel::html( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		} elseif ( EventWorkspace::PANEL_SETTINGS === $panel ) {
			echo EventDataPanel::html( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		} elseif ( EventWorkspace::PANEL_LOOK === $panel ) {
			echo EventAppearancePanel::html( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		} elseif ( EventWorkspace::PANEL_CODE === $panel ) {
			// Aquí no hace falta volver a comprobar la capacidad: sin ella la
			// pestaña no está en `panels()` y `model()` ya cayó en «Secciones».
			echo EventCodePanel::html( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		} else {
			echo EventSectionsPanel::html( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		}

		if ( $cerrado ) {
			echo '</fieldset>';
		}

		return Shell::render( '', '', (string) ob_get_clean() );
	}

	/**
	 * The «create an event» screen.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
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
			echo Shell::notice( (string) $flash['tipo'], (string) $flash['texto'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		}
		echo EventDataPanel::html( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.

		return Shell::render( '', '', (string) ob_get_clean() );
	}

	/**
	 * Why this workshop opens without a single «Guardar».
	 *
	 * Sale arriba y para todo el mundo, también para quien administra: si el
	 * evento está cerrado, lo primero que hay que leer al entrar es eso.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function archived_notice( array $m ): string {
		if ( true !== $m['archived'] ) {
			return '';
		}
		$texto = true === $m['can_edit']
			? 'Este evento está marcado como histórico: su área ya no puede editarlo. Usted sí, porque administra el aplicativo.'
			: 'Este evento está marcado como histórico: se puede consultar y exportar, pero ya no se edita. Para volver a abrirlo, pídalo a quien administre el aplicativo.';

		return Shell::notice( 'aviso', $texto );
	}

	/**
	 * The switch that closes the event for good, or opens it again.
	 *
	 * Las dos mitades no se pintan igual porque no son de la misma persona.
	 * Cerrar el evento es una acción normal del área que lo organiza —una cosa
	 * más de este evento, al lado de sus fechas—, así que va en una tarjeta
	 * corriente. Reabrirlo sí es de administración y solo lo ve ella, así que
	 * va en el recuadro amarillo, que es la convención del aplicativo para
	 * justamente eso y para nada más.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
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
			<?php echo self::archive_form( $m, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The form of one half of the switch.
	 *
	 * El aviso de que no hay vuelta atrás va en el diálogo y no solo en el
	 * texto de al lado: es lo último que se lee antes de cerrar el evento, y
	 * el diálogo abre con «Cancelar» enfocado.
	 *
	 * @param array<string, mixed> $m      Model.
	 * @param bool                 $marcar Whether this is the marking half.
	 * @return string
	 */
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

	/**
	 * The name of the event, what state it is in, its área, and where to see it.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
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

	/**
	 * The inner tabs of the workshop, each with its count.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
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
						// El recuento solo donde hay algo que contar: en «Ajustes»,
						// «Apariencia» y «Código» no significaría nada.
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

	/**
	 * The áreas of the event, written out.
	 *
	 * @param int[] $ids Term IDs of evt_area.
	 * @return string
	 */
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

	/**
	 * Way out when there is no event to work on.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function back_link( array $m ): string {
		$url = (string) $m['events_url'];
		if ( '' === $url ) {
			return '';
		}
		return '<p><a class="' . esc_attr( Assets::button_class( true ) ) . '" href="' . esc_url( $url ) . '">Ver mis eventos</a></p>';
	}
}

// ---- src/Evt/PublicFront/PageForm.php ----
/**
 * Front-end editor of one satellite page of an event.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

use Evt\Access\EventAccess;
use Evt\Domain\EventInput;
use Evt\Meta\EventMetaKeys;
use Evt\PostType\EventPostType;
use Evt\PublicFront\View\PageFormView;

/**
 * Shortcode [evt_page_form]: alta y edición de una sección del evento.
 *
 * Hoy esto es el formulario del sistema anterior entero —un solo formulario de
 * más de cinco mil píxeles de scroll, está medido y el material está en
 * `.local/`— y el mismo para la portada del evento y para cada sección
 * satélite. Aquí son cinco campos y un plegado: qué sección es, cómo se llama,
 * dónde vive, en qué orden sale y qué cuenta. Lo que se lee de la petición:
 *
 * - `?evento=<id>` — alta de una sección que cuelga de ese evento.
 * - `?seccion=<id>` — edición de esa sección; el evento sale de su `post_parent`
 *   y no de la petición, para que nadie cuelgue una sección ajena de su área.
 *
 * El plegado «Apariencia de esta sección» son los siete campos de diseño que
 * el formulario anterior repite para la hija: fondo de cabecera, texto de
 * cabecera, separador, logo acompañante, forma de las imágenes y las dos
 * tipografías. Lo que se deja en blanco no se guarda: así se distingue «no
 * elegido» —se hereda del evento— de «elegido vacío».
 *
 * La sección nace en borrador. Publicarla es una acción del panel de secciones
 * del evento, no un desplegable perdido en un formulario largo.
 *
 * Al final, el CSS y el JavaScript de ESTA página, que se aplican solo a ella y
 * después de los del evento: el CSS como un bloque más —lo escribe también
 * quien organiza el evento, acotado a su área— y el JavaScript en el recuadro
 * amarillo de «Solo administración». Sin la capacidad no se pinta —y si los
 * campos llegan igual en el envío, se ignoran sin tocar lo que hubiera
 * guardado.
 */
final class PageForm {

	public const SHORTCODE = 'evt_page_form';

	/**
	 * Nonce action of the form.
	 */
	public const NONCE_ACTION = 'evt_page_save';

	/**
	 * Nonce field name.
	 */
	public const NONCE_FIELD = 'evt_page_nonce';

	/**
	 * The seven appearance settings a satellite page carries of its own.
	 *
	 * @var string[]
	 */
	public const LOOK_KEYS = array(
		EventMetaKeys::HEADER_BG,
		EventMetaKeys::HEADER_TEXT,
		EventMetaKeys::SEPARATOR,
		EventMetaKeys::LOGO_ID,
		EventMetaKeys::IMAGE_SHAPE,
		EventMetaKeys::TITLE_FONT,
		EventMetaKeys::BODY_FONT,
	);

	/**
	 * Why the last submit did not go through, and which fields to mark.
	 *
	 * Un envío rechazado no redirige: la misma petición vuelve a pintar el
	 * formulario, así que el motivo no tiene que sobrevivir a nada y no hace
	 * falta guardarlo fuera.
	 *
	 * @var array{message:string, errors:string[]}
	 */
	private static $rejected = array(
		'message' => '',
		'errors'  => array(),
	);

	/**
	 * Register the shortcode and the POST handler.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'render' ) );
		// Antes de pintar: si el envío entra, se sale por `Shell::leave()` sin
		// llegar a montar la pantalla.
		add_action( 'init', array( self::class, 'maybe_handle_submit' ), 20 );
	}

	/**
	 * Take the form submit: nonce, permission over the parent event, and save.
	 *
	 * @return void
	 */
	public static function maybe_handle_submit(): void {
		if ( ! self::is_our_submit() || ! is_user_logged_in() ) {
			return;
		}
		if ( ! self::nonce_ok() ) {
			self::$rejected['message'] = 'El formulario estuvo abierto demasiado tiempo y el envío caducó. Vuelva a enviarlo.';
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- comprobado en la línea de arriba.
		$raw      = wp_unslash( $_POST );
		$user_id  = get_current_user_id();
		$page_id  = self::int_of( $raw, 'evt_page_id' );
		$event_id = self::target_event( $raw, $page_id );

		if ( ! self::is_event_root( $event_id ) ) {
			self::$rejected['message'] = 'No se sabe de qué evento cuelga esta sección. Ábrala desde su evento.';
			return;
		}
		// El guardián, sobre el evento padre: sin permiso sobre él no se crea ni
		// se toca nada, aunque el identificador venga escrito a mano en el envío.
		if ( ! EventAccess::can_edit( $user_id, $event_id ) ) {
			self::$rejected['message'] = EventAccess::why_not_editable( $user_id, $event_id );
			return;
		}
		// Antes de escribir nada, y con el evento padre y no con la sección: el
		// bloqueo es del evento entero. Si lo tiene otra persona, esto responde
		// 409 y no vuelve.
		EditLock::require_available( $event_id );

		$fields = self::submitted_fields( $raw );
		// Al editar, el tipo es el que ya tiene la sección y no el que venga en
		// el envío: se eligió al crearla y no se cambia (ADR-0019). Se valida
		// igual, porque un valor guardado también puede haberse corrompido.
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

	/**
	 * Back to the same screen, with the notice of what just happened.
	 *
	 * @param int $page_id Page that was being edited, 0 when it was created now.
	 * @param int $saved   Page ID after saving.
	 * @return void
	 */
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

	/**
	 * The event this submit is about.
	 *
	 * Al editar, el evento sale del `post_parent` de la sección y no de la
	 * petición: si viniera del envío, cambiarlo a mano movería una sección de
	 * un evento a otro —de un área a otra— con solo tener permiso sobre el
	 * evento de destino.
	 *
	 * @param array<string, mixed> $raw     Unslashed $_POST.
	 * @param int                  $page_id Page being edited, 0 when new.
	 * @return int
	 */
	private static function target_event( array $raw, int $page_id ): int {
		return $page_id > 0 ? self::parent_of( $page_id ) : self::int_of( $raw, 'evt_page_event' );
	}

	/**
	 * A positive integer from a submitted field.
	 *
	 * @param array<string, mixed> $raw Unslashed $_POST.
	 * @param string               $key Field name.
	 * @return int
	 */
	private static function int_of( array $raw, string $key ): int {
		return isset( $raw[ $key ] ) ? max( 0, (int) $raw[ $key ] ) : 0;
	}

	/**
	 * Write the page and its meta.
	 *
	 * @param int                  $user_id  Who is saving.
	 * @param int                  $event_id Parent event.
	 * @param int                  $page_id  Page being edited, 0 when new.
	 * @param array<string, mixed> $data     What EventInput normalised.
	 * @param array<string, mixed> $fields   What the form carried.
	 * @return int|\WP_Error Page ID.
	 */
	private static function save( int $user_id, int $event_id, int $page_id, array $data, array $fields ) {
		$title   = (string) $data['title'];
		$postarr = array(
			'post_type'    => EventPostType::POST_TYPE,
			'post_parent'  => $event_id,
			'post_title'   => $title,
			// Sin slug tecleado, el del título: una sección sin dirección no se
			// enlaza, y el aplicativo vive de sus direcciones.
			'post_name'    => sanitize_title( '' !== $fields['slug'] ? $fields['slug'] : $title ),
			'post_content' => $fields['content'],
			'menu_order'   => $fields['menu_order'],
		);

		if ( $page_id > 0 ) {
			$postarr['ID'] = $page_id;
			$result        = wp_update_post( wp_slash( $postarr ), true );
		} else {
			// Nace en borrador, y el estado no vuelve a tocarse aquí: publicar y
			// retirar son acciones del panel de secciones del evento.
			$postarr['post_status'] = 'draft';
			$postarr['post_author'] = $user_id;
			$result                 = wp_insert_post( wp_slash( $postarr ), true );
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$id = (int) $result;

		// El tipo se escribe UNA vez, al crear. Cambiarlo después dejaría la
		// sección con los elementos de otro tipo: una página de programa y una
		// de contacto no llevan lo mismo dentro (ADR-0019). Aunque alguien
		// mande el campo a mano en el POST de una edición, aquí no se toca.
		//
		// Esto es redundante a propósito con el `$tipo` de `maybe_handle_submit()`,
		// que ya sustituye lo enviado por lo guardado: comprobado que cada una
		// de las dos basta por separado y que el test solo cae si se quitan las
		// dos. La de arriba protege la validación y esta la escritura; quien
		// mañana toque una, no se lleva la otra por delante.
		if ( 0 === $page_id ) {
			update_post_meta( $id, EventMetaKeys::SECTION_TYPE, (string) $data['section_type'] );
		}

		foreach ( self::LOOK_KEYS as $clave ) {
			$valor = (string) ( $fields['look'][ $clave ] ?? '' );
			if ( '' === $valor || '0' === $valor ) {
				// Sin elegir: se hereda del evento, y eso se guarda no guardando.
				delete_post_meta( $id, $clave );
				continue;
			}
			update_post_meta( $id, $clave, $valor );
		}

		self::save_code( $user_id, $event_id, $id, $fields );

		return $id;
	}

	/**
	 * Write the custom CSS and JavaScript of this page, field by field.
	 *
	 * Se comprueba por campo y no una vez: las dos capacidades son distintas a
	 * propósito —una hoja de estilos afea una página, un guion ejecuta código
	 * en el navegador de cada visitante— y quien tiene una puede no tener la
	 * otra. El campo que no se puede escribir no se toca: si llega en el envío
	 * es que alguien lo escribió a mano, y lo guardado se queda como estaba.
	 *
	 * @param int                  $user_id  Who is saving.
	 * @param int                  $event_id Parent event.
	 * @param int                  $page_id  Page just written.
	 * @param array<string, mixed> $fields   What the form carried.
	 * @return void
	 */
	private static function save_code( int $user_id, int $event_id, int $page_id, array $fields ): void {
		foreach ( self::code_allowed( $user_id, $event_id ) as $clave => $puede ) {
			if ( ! $puede ) {
				continue;
			}
			// En crudo: es código, y limpiarlo lo rompería. De desactivar la
			// fuga de etiqueta se encargan los sanitize de
			// `register_post_meta()`, que corren dentro de esta llamada.
			// `wp_slash()` porque `update_post_meta()` desescapa antes de
			// guardar, y sin eso un `\2014` del CSS o un `\d` del JavaScript
			// perderían la barra.
			update_post_meta( $page_id, $clave, wp_slash( (string) ( $fields['code'][ $clave ] ?? '' ) ) );
		}
	}

	/**
	 * Which of the two code fields this person may write here.
	 *
	 * Se pregunta por el evento padre y no por la página: es donde vive el
	 * área, y al crear una sección todavía no hay página por la que preguntar.
	 *
	 * @param int $user_id  Who is asking.
	 * @param int $event_id Parent event.
	 * @return array<string, bool> Meta key => whether it may be written.
	 */
	private static function code_allowed( int $user_id, int $event_id ): array {
		return array(
			EventMetaKeys::CUSTOM_CSS => EventAccess::can_edit_custom_css( $user_id, $event_id ),
			EventMetaKeys::CUSTOM_JS  => EventAccess::can_edit_custom_js( $user_id, $event_id ),
		);
	}

	/**
	 * Everything the screen decides before painting.
	 *
	 * @return array<string, mixed>
	 */
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
		// Abrir la sección toma el bloqueo DEL EVENTO: dos personas en dos
		// secciones distintas del mismo evento se pisan igual, porque comparten
		// la navegación, el orden y la apariencia.
		$m['lock'] = EditLock::status( $event_id, true );

		if ( $page_id > 0 ) {
			$m['status'] = (string) get_post_status( $page_id );
			$m['values'] = self::stored_values( $page_id );
		}

		// Lo que se acaba de teclear manda sobre lo guardado: al corregir, la
		// sección todavía tiene lo viejo y repintarlo borraría la corrección.
		$enviado = self::submitted_values();
		if ( null !== $enviado ) {
			$m['values'] = $enviado;
		}

		$m['values']['code'] = self::readable_code( $m['code'], (array) $m['values']['code'] );

		return $m;
	}

	/**
	 * Blank out the code this person may not write.
	 *
	 * El campo que no se puede escribir tampoco se repinta, venga del envío o
	 * de lo guardado: su formulario no lo lleva, y el modelo no le pasa a la
	 * vista nada que la vista no deba pintar.
	 *
	 * @param array<string, bool>   $puede   Meta key => whether it may be written.
	 * @param array<string, string> $valores Meta key => value.
	 * @return array<string, string>
	 */
	private static function readable_code( array $puede, array $valores ): array {
		foreach ( $puede as $clave => $ok ) {
			if ( ! $ok ) {
				$valores[ $clave ] = '';
			}
		}
		return $valores;
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array<string, string>|string $atts Attributes.
	 * @return string
	 */
	public static function render( $atts = array() ): string {
		unset( $atts );
		Assets::enqueue();
		$m    = self::model();
		$lock = (array) $m['lock'];
		$html = PageFormView::html( $m );

		// Un `fieldset` desactivado apaga de una vez todos los controles que
		// tenga dentro, así que la vista no tiene que enterarse de nada. Es la
		// misma pieza con la que el taller pinta un evento en solo lectura.
		if ( (int) $lock['owner'] > 0 ) {
			$html = '<fieldset class="evt-solo-lectura" disabled>'
				. '<legend class="screen-reader-text">Sección en solo lectura: otra persona tiene abierto este evento</legend>'
				. $html . '</fieldset>';
		}

		return EditLock::render( EditLock::claim( $lock ) ) . $html;
	}

	/**
	 * Empty form values.
	 *
	 * @return array<string, mixed>
	 */
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

	/**
	 * Form values of a page already saved.
	 *
	 * @param int $page_id Page ID.
	 * @return array<string, mixed>
	 */
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

	/**
	 * The fields a submit carries, in the shape the form and the saver share.
	 *
	 * Una sola lectura de la petición para los dos que la miran: el que guarda
	 * y el que repinta. Con dos, acabarían interpretando distinto el mismo
	 * campo.
	 *
	 * @param array<string, mixed> $raw Unslashed $_POST.
	 * @return array<string, mixed>
	 */
	private static function submitted_fields( array $raw ): array {
		$look = array();
		foreach ( self::LOOK_KEYS as $clave ) {
			$look[ $clave ] = isset( $raw[ $clave ] ) ? sanitize_text_field( (string) $raw[ $clave ] ) : '';
		}
		$code = array();
		foreach ( EventMetaKeys::code_keys() as $clave ) {
			// Tal cual llega: es código. Quien decide si se guarda es
			// {@see save_code()}, y quien lo limpia, su sanitize de meta.
			$code[ $clave ] = isset( $raw[ $clave ] ) ? (string) $raw[ $clave ] : '';
		}

		return array(
			'title'        => isset( $raw['evt_title'] ) ? sanitize_text_field( (string) $raw['evt_title'] ) : '',
			'slug'         => isset( $raw['evt_slug'] ) ? sanitize_title( (string) $raw['evt_slug'] ) : '',
			'section_type' => isset( $raw[ EventMetaKeys::SECTION_TYPE ] ) ? sanitize_key( (string) $raw[ EventMetaKeys::SECTION_TYPE ] ) : '',
			'menu_order'   => isset( $raw['evt_order'] ) ? (int) $raw['evt_order'] : 0,
			// Nadie escribe HTML sin filtrar, tampoco quien organiza.
			'content'      => isset( $raw['evt_content'] ) ? wp_kses_post( (string) $raw['evt_content'] ) : '',
			'look'         => $look,
			'code'         => $code,
		);
	}

	/**
	 * Form values from the last submit, or null when the request is not one.
	 *
	 * @return array<string, mixed>|null
	 */
	private static function submitted_values(): ?array {
		if ( ! self::is_our_submit() || ! self::nonce_ok() ) {
			return null;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- comprobado en la línea de arriba.
		return self::submitted_fields( wp_unslash( $_POST ) );
	}

	/**
	 * Whether this request is this form being submitted.
	 *
	 * @return bool
	 */
	private static function is_our_submit(): bool {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_METHOD'] ) ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- solo mira si el envío es nuestro; el nonce se comprueba aparte.
		return 'POST' === $method && ! empty( $_POST['evt_page_form'] );
	}

	/**
	 * Whether the submit carries our valid nonce.
	 *
	 * @return bool
	 */
	private static function nonce_ok(): bool {
		$nonce = isset( $_POST[ self::NONCE_FIELD ] )
			? sanitize_text_field( wp_unslash( (string) $_POST[ self::NONCE_FIELD ] ) )
			: '';
		return '' !== $nonce && false !== wp_verify_nonce( $nonce, self::NONCE_ACTION );
	}

	/**
	 * A positive ID from the query string.
	 *
	 * @param string $name Query argument.
	 * @return int
	 */
	private static function query_id( string $name ): int {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo elige qué se pinta; el envío revalida nonce y permisos.
		$value = isset( $_GET[ $name ] ) ? (int) $_GET[ $name ] : 0;
		return max( 0, $value );
	}

	/**
	 * The one-line notice of what just got saved.
	 *
	 * @return string
	 */
	private static function done_notice(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo elige un rótulo de una lista cerrada.
		$que    = isset( $_GET['evt_hecho'] ) ? sanitize_key( wp_unslash( (string) $_GET['evt_hecho'] ) ) : '';
		$textos = array(
			'creada'   => 'Sección creada, en borrador: todavía no la ve nadie. Escriba el contenido y publíquela desde el evento cuando esté lista.',
			'guardada' => 'Cambios guardados.',
		);
		return (string) ( $textos[ $que ] ?? '' );
	}

	/**
	 * Why the submit was rejected, in words.
	 *
	 * @param string[] $codes Error codes from EventInput.
	 * @return string
	 */
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

	/**
	 * The event a page hangs from, 0 when it is not one of our pages.
	 *
	 * @param int $page_id Page ID.
	 * @return int
	 */
	private static function parent_of( int $page_id ): int {
		if ( $page_id <= 0 || EventPostType::POST_TYPE !== get_post_type( $page_id ) ) {
			return 0;
		}
		return (int) get_post_field( 'post_parent', $page_id );
	}

	/**
	 * Whether this post is an event itself, and not one of its pages.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	private static function is_event_root( int $post_id ): bool {
		if ( $post_id <= 0 || EventPostType::POST_TYPE !== get_post_type( $post_id ) ) {
			return false;
		}
		return 0 === (int) get_post_field( 'post_parent', $post_id );
	}
}

// ---- src/Evt/PublicFront/View/PageFormView.php ----
/**
 * The satellite page form, painted from what PageForm::model() decided.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

use Evt\Meta\EventMetaKeys;
use Evt\PublicFront\Assets;
use Evt\PublicFront\CodeEditor;
use Evt\PublicFront\PageForm;
use Evt\PublicFront\Shell;

/**
 * Solo pinta: no lee la petición, no consulta, no decide.
 */
final class PageFormView {

	/**
	 * The whole screen.
	 *
	 * @param array<string, mixed> $m What PageForm::model() returned.
	 * @return string
	 */
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

	/**
	 * One line under the heading: of which event, and how it stands.
	 *
	 * @param array<string, mixed> $m     Model.
	 * @param bool                 $nueva Whether the page is being created.
	 * @return string
	 */
	private static function subtitle( array $m, bool $nueva ): string {
		if ( $nueva ) {
			return sprintf( 'Del evento «%s». Nace en borrador: no la ve nadie hasta que la publique.', (string) $m['event'] );
		}
		$estado = 'publish' === (string) $m['status'] ? 'Publicada' : 'En borrador';
		return sprintf( 'Del evento «%s» · %s', (string) $m['event'], $estado );
	}

	/**
	 * The form itself.
	 *
	 * @param array<string, mixed> $m     Model.
	 * @param bool                 $nueva Whether the page is being created.
	 * @return string
	 */
	private static function form( array $m, bool $nueva ): string {
		$valores = (array) $m['values'];
		$errores = (array) $m['errors'];

		ob_start();
		echo Shell::notice( 'ok', (string) $m['hecho'] );    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- llega escapado.
		echo Shell::notice( 'error', (string) $m['error'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- llega escapado.
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
				// El editor de WordPress, el mismo que en el escritorio. El HTML se
				// filtra al guardar, aquí no hay `unfiltered_html`.
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

			<?php echo self::look( (array) $valores['look'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>

			<?php echo self::code( (array) $m['code'], (array) $valores['code'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>

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

	/**
	 * The folded «Apariencia de esta sección» block.
	 *
	 * Plegado porque casi nunca se toca: lo normal es que la sección se vea
	 * como su evento, y lo que se deja en blanco es justamente eso.
	 *
	 * @param array<string, string> $look Current appearance values, by meta key.
	 * @return string
	 */
	private static function look( array $look ): string {
		ob_start();
		?>
		<details class="evt-tarjeta">
			<summary>Apariencia de esta sección</summary>
			<p>Lo que deje en blanco se hereda del evento. Solo hace falta tocarlo cuando esta sección tenga que verse distinta.</p>

			<div class="evt-form-fila">
				<?php
				echo self::color_field( EventMetaKeys::HEADER_BG, 'Color de fondo de la cabecera', (string) ( $look[ EventMetaKeys::HEADER_BG ] ?? '' ) );    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
				echo self::color_field( EventMetaKeys::HEADER_TEXT, 'Color del texto de la cabecera', (string) ( $look[ EventMetaKeys::HEADER_TEXT ] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
				?>
			</div>

			<div class="evt-form-fila">
				<?php
				echo self::select_field( EventMetaKeys::TITLE_FONT, 'Tipografía de los títulos', EventMetaKeys::fonts(), (string) ( $look[ EventMetaKeys::TITLE_FONT ] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
				echo self::select_field( EventMetaKeys::BODY_FONT, 'Tipografía del cuerpo', EventMetaKeys::fonts(), (string) ( $look[ EventMetaKeys::BODY_FONT ] ?? '' ) );      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
				?>
			</div>

			<div class="evt-form-fila">
				<?php
				echo self::select_field( EventMetaKeys::IMAGE_SHAPE, 'Forma de las imágenes de personas', EventMetaKeys::image_shapes(), (string) ( $look[ EventMetaKeys::IMAGE_SHAPE ] ?? '' ), 'Sin elegir' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
				echo self::select_field( EventMetaKeys::SEPARATOR, 'Separador al pie de la cabecera', EventMetaKeys::separators(), (string) ( $look[ EventMetaKeys::SEPARATOR ] ?? '' ) );                       // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
				?>
			</div>

			<?php echo self::logo_field( (int) ( $look[ EventMetaKeys::LOGO_ID ] ?? 0 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		</details>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The code of this page: the CSS as a plain block, the JavaScript in yellow.
	 *
	 * Cada campo por separado y cada uno con su forma, porque son dos permisos
	 * distintos y dos riesgos distintos. El CSS es un bloque corriente de la
	 * sección —lo escribe también quien organiza el evento, acotado a su área—;
	 * el JavaScript va en el recuadro amarillo de «Solo administración» porque
	 * se ejecuta en el navegador de quien visite la página.
	 *
	 * El campo que no se puede escribir no se pinta: esconder el contenido y
	 * dejar el campo no es esconder nada. Pero a quien sí escribe el CSS se le
	 * dice en una línea por qué no está el otro, para que no lo busque.
	 *
	 * @param array<string, bool>   $puede   Meta key => whether this person may write it.
	 * @param array<string, string> $valores Meta key => what is stored, raw.
	 * @return string
	 */
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
				<?php echo $campo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
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

	/**
	 * A colour: the native picker next to the hexadecimal it writes.
	 *
	 * El que viaja es el campo de texto; el selector no lleva `name` a
	 * propósito, para que el dato sea uno solo y quien tenga el guion
	 * bloqueado siga pudiendo teclear el hexadecimal.
	 *
	 * @param string $key   Meta key, which is also the field name.
	 * @param string $label What it says.
	 * @param string $value Current value.
	 * @return string
	 */
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

	/**
	 * A closed list.
	 *
	 * @param string                $key     Meta key, which is also the field name.
	 * @param string                $label   What it says.
	 * @param array<string, string> $options slug => rótulo.
	 * @param string                $value   Current value.
	 * @param string                $blank   Label of the empty option; '' when the list already has one.
	 * @return string
	 */
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

	/**
	 * The accompanying logo of this page, by attachment ID.
	 *
	 * @param int $logo_id Attachment ID, 0 when there is none.
	 * @return string
	 */
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

	/**
	 * Inline error next to a field the last submit flagged.
	 *
	 * @param string[] $errors Error codes.
	 * @param string[] $codes  Codes attached to this field.
	 * @param string   $text   What to say.
	 * @return void Echoes the escaped message.
	 */
	private static function field_error( array $errors, array $codes, string $text ): void {
		if ( array() === array_intersect( $codes, $errors ) ) {
			return;
		}
		echo '<span class="evt-error" role="alert">' . esc_html( $text ) . '</span>';
	}

	/**
	 * A way out when there is no form to paint.
	 *
	 * @return string
	 */
	private static function back_to_events(): string {
		$url = Shell::url( 'events' );
		if ( '' === $url ) {
			return '';
		}
		return '<p class="evt-acciones"><a class="' . esc_attr( Assets::button_class() ) . '" href="'
			. esc_url( $url ) . '">Ir a mis eventos</a></p>';
	}
}

// ---- src/Evt/PublicFront/Block/ContentBlock.php ----
/**
 * The `contenido` block of the public page of an event.
 *
 * @package Evt
 */

namespace Evt\PublicFront\Block;

/**
 * El texto de la página, tal y como lo escribió quien la redactó.
 *
 * Solo pinta: no lee la petición, no consulta, no decide. El
 * contenido llega en el modelo ya pasado por `the_content`, así que aquí no se
 * vuelve a filtrar: se sanea la salida y se devuelve.
 *
 * Es el bloque que sustituye al `<div class="evt-ev-cuerpo">` de la vista
 * vieja. El envoltorio —`<section class="evt-ev__bloque evt-ev__bloque--contenido">`—
 * lo pone {@see \Evt\PublicFront\EventLayout::body()}, y por eso aquí no hay
 * ningún `<div>` de más: una sección de evento tenía diez niveles de
 * anidamiento antes de llegar al texto y ese era justo el problema.
 *
 * Una página sin texto no deja hueco, y eso no se comprueba aquí:
 * {@see \Evt\PublicFront\EventLayout::body()} se salta todo bloque cuyo HTML
 * llegue vacío, y esa guarda vale para los tres y para los que vengan. Cada
 * bloque repitiéndola sería la misma condición escrita cuatro veces.
 */
final class ContentBlock {

	/**
	 * Name of the block; also the CSS modifier of its section.
	 */
	public const NAME = 'contenido';

	/**
	 * Dónde pinta: lo primero del cuerpo.
	 */
	public const PRIORITY = 10;

	/**
	 * Paint the block.
	 *
	 * @param array<string, mixed> $m What EventView::model() decided.
	 * @return string HTML already escaped; empty when there is nothing to say.
	 */
	public static function html( array $m ): string {
		return wp_kses_post( (string) $m['content'] );
	}
}

// ---- src/Evt/PublicFront/Block/PosterBlock.php ----
/**
 * The `cartel` block of the public page of an event.
 *
 * @package Evt
 */

namespace Evt\PublicFront\Block;

/**
 * El cartel del evento, en su portada y solo ahí.
 *
 * Solo pinta: no lee la petición, no consulta, no decide.
 *
 * Se enseña reducido y enlaza al original: es la pieza que la gente se descarga
 * y comparte, y la miniatura no sirve para imprimir. En cada sección sería el
 * mismo cartel repetido cinco veces, así que se queda en la portada; y el
 * evento que no tiene cartel no deja hueco.
 *
 * El alt: si nadie escribió uno en la biblioteca de medios, «Cartel de <el
 * evento>», que es lo que diría una persona. Vacío nunca: la imagen es
 * información, no adorno.
 */
final class PosterBlock {

	/**
	 * Name of the block; also the CSS modifier of its section.
	 */
	public const NAME = 'cartel';

	/**
	 * Dónde pinta: detrás del texto y delante de las secciones.
	 */
	public const PRIORITY = 20;

	/**
	 * Paint the block.
	 *
	 * @param array<string, mixed> $m What EventView::model() decided.
	 * @return string HTML already escaped; empty when there is no poster.
	 */
	public static function html( array $m ): string {
		$look = (array) $m['appearance'];
		if ( empty( $m['is_root'] ) || '' === (string) $look['poster'] ) {
			return '';
		}

		$alt = (string) $look['poster_alt'];
		if ( '' === $alt ) {
			/* translators: %s: nombre del evento. */
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

// ---- src/Evt/PublicFront/Block/SectionsBlock.php ----
/**
 * The `secciones` block of the public page of an event.
 *
 * @package Evt
 */

namespace Evt\PublicFront\Block;

/**
 * La rejilla de tarjetas de sección de la portada.
 *
 * Solo pinta: no lee la petición, no consulta, no decide. Las
 * tarjetas llegan hechas en `$m['cards']`, con su título, su enlace, su imagen
 * y su texto —el propio de la sección o el que le toca por tipo, que decide
 * {@see \Evt\PublicFront\EventView::cards()}—.
 *
 * La rejilla es un `grid` de `auto-fit`, que mide el CONTENEDOR y no la
 * ventana. Con las `col-lg-3 col-md-4 col-sm-6` de Bootstrap que hay hoy en
 * producción salían cuatro tarjetas de una letra de ancho dentro de un
 * contenedor estrecho, porque las media queries miden el viewport. No se vuelve
 * atrás: el aspecto es el mismo y el comportamiento es el correcto.
 *
 * El evento sin secciones publicadas no pinta una rejilla vacía: devuelve la
 * cadena vacía y el armazón se salta el bloque.
 */
final class SectionsBlock {

	/**
	 * Name of the block; also the CSS modifier of its section.
	 */
	public const NAME = 'secciones';

	/**
	 * Dónde pinta: lo último del cuerpo de la portada.
	 */
	public const PRIORITY = 30;

	/**
	 * Paint the block.
	 *
	 * @param array<string, mixed> $m What EventView::model() decided.
	 * @return string HTML already escaped; empty when there are no sections.
	 */
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

// ---- src/Evt/PublicFront/Block/SignupBlock.php ----
/**
 * The `inscripcion` block of the public page of an event.
 *
 * @package Evt
 */

namespace Evt\PublicFront\Block;

use Evt\Meta\RegistrationMetaKeys;
use Evt\PublicFront\Registrations;
use Evt\PublicFront\SignupForm;

/**
 * El formulario de inscripción, en la sección de tipo `inscripcion`.
 *
 * Solo pinta: no lee la petición para decidir nada, no escribe y no valida. De
 * eso va {@see \Evt\PublicFront\SignupForm}, y de guardar
 * {@see \Evt\PublicFront\Registrations}.
 *
 * Son los siete campos del núcleo más las preguntas del evento (ADR-0031), y
 * nada más: aquí no hay lógica condicional que esconda ni enseñe nada, porque
 * no la hay en el modelo. **Quien no se queda a comer ve igualmente la pregunta
 * de las intolerancias**, y eso está aceptado a sabiendas: la alternativa era
 * el constructor de formularios del que se sale.
 */
final class SignupBlock {

	/**
	 * Name of the block; also the CSS modifier of its section.
	 */
	public const NAME = 'inscripcion';

	/**
	 * Dónde pinta: detrás del texto y del cartel, que son la explicación, y
	 * delante de las tarjetas de las demás páginas.
	 */
	public const PRIORITY = 25;

	/**
	 * The block, or nothing when this is not a signup section.
	 *
	 * @param array<string, mixed> $m What EventView::model() decided.
	 * @return string
	 */
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

		// Ya inscrita: lo que queda es su taller, si hay plazo.
		if ( $mia > 0 ) {
			return $aviso . self::mine( $evento, $mia );
		}

		if ( ! SignupForm::is_open( $evento ) ) {
			return $aviso . '<p class="evt-ins__cerrada">La inscripción de este evento no está abierta.</p>';
		}

		return $aviso . self::form( $evento );
	}

	/**
	 * What the last submit left to say.
	 *
	 * @return string
	 */
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

	/**
	 * The signup form itself.
	 *
	 * @param int $evento Event post ID.
	 * @return string
	 */
	private static function form( int $evento ): string {
		$html  = '<form class="evt-ins" method="post">';
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

	/**
	 * What somebody already signed up sees.
	 *
	 * @param int $evento Event post ID.
	 * @param int $mia    Their registration.
	 * @return string
	 */
	private static function mine( int $evento, int $mia ): string {
		$meta   = Registrations::meta( $mia );
		$nombre = trim( $meta[ RegistrationMetaKeys::REG_NAME ] . ' ' . $meta[ RegistrationMetaKeys::REG_SURNAME ] );

		$html  = '<div class="evt-ins evt-ins--mia">';
		$html .= '<p class="evt-ins__hecha">Su inscripción está registrada, ' . esc_html( $nombre ) . '.</p>';

		if ( ! SignupForm::workshops_open( $evento ) ) {
			$html .= '<p class="evt-ins__cerrada">El plazo para elegir taller no está abierto.</p>';
			return $html . '</div>';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo para devolverlo en el formulario; la escritura lleva su nonce.
		$token = isset( $_GET[ SignupForm::ARG_TOKEN ] ) ? sanitize_text_field( wp_unslash( $_GET[ SignupForm::ARG_TOKEN ] ) ) : '';

		$html .= '<form class="evt-ins__taller" method="post">';
		$html .= self::hidden( $evento, SignupForm::OP_WORKSHOP );
		$html .= sprintf( '<input type="hidden" name="%1$s" value="%2$s">', esc_attr( SignupForm::FIELD_TOKEN ), esc_attr( $token ) );
		$html .= self::workshops( $evento, $mia );
		$html .= '<p class="evt-ins__enviar"><button type="submit" class="evt-btn evt-btn--primario">Guardar el taller</button></p>';
		$html .= '</form></div>';

		return $html;
	}

	/**
	 * The hidden fields every submit carries.
	 *
	 * @param int    $evento Event post ID.
	 * @param string $op     Operation.
	 * @return string
	 */
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

	/**
	 * The six typed fields of the core, with their label and input type.
	 *
	 * @return array<string, array{label:string, type:string, required:bool, autocomplete:string}>
	 */
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

	/**
	 * One field of the core.
	 *
	 * @param string                                                               $nombre Field name.
	 * @param array{label:string, type:string, required:bool, autocomplete:string} $campo  Its shape.
	 * @return string
	 */
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

	/**
	 * The centre, from the catalogue and never typed.
	 *
	 * Sin catálogo no se deja teclear: un centro tecleado es el mismo centro
	 * escrito de cinco maneras, y a partir de ahí no hay recuento ni cruce que
	 * valga (ADR-0031). Se dice, en vez de fabricar variantes en silencio.
	 *
	 * @return string
	 */
	private static function centre(): string {
		$centros = Registrations::centres();
		if ( array() === $centros ) {
			return '<p class="evt-aviso evt-aviso--error">No hay catálogo de centros configurado, '
				. 'así que no se puede completar la inscripción. Avise a quien organiza el evento.</p>';
		}

		$html = '<p class="evt-campo"><label for="evt-ins-centre">Centro <span class="evt-campo__obl" aria-hidden="true">*</span></label>'
			. '<select id="evt-ins-centre" name="centre" required><option value="">Elija su centro</option>';
		foreach ( $centros as $centro ) {
			$html .= sprintf( '<option value="%1$s">%1$s</option>', esc_attr( $centro ) );
		}
		return $html . '</select></p>';
	}

	/**
	 * The questions of this event, if it has any.
	 *
	 * @param int $evento Event post ID.
	 * @return string
	 */
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

	/**
	 * One question, by its type.
	 *
	 * @param array<string, mixed> $p Normalised question.
	 * @return string
	 */
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

	/**
	 * The workshops still choosable, when the window is open.
	 *
	 * Un taller lleno no sale. Que no salga **no es el control**: la página se
	 * pudo pintar hace cinco minutos y la comprobación que manda es la del
	 * candado al guardar (ADR-0033).
	 *
	 * @param int $evento Event post ID.
	 * @param int $mia    Registration choosing, 0 for a new signup.
	 * @return string
	 */
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

	/**
	 * The consent: the two texts and one checkbox (ADR-0020).
	 *
	 * @param int $evento Event post ID.
	 * @return string
	 */
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

// ---- src/Evt/PublicFront/EventLayout.php ----
/**
 * The whole HTML document of the public page of an event.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

use Evt\Meta\EventMetaKeys;
use Evt\PublicFront\Block\ContentBlock;
use Evt\PublicFront\Block\PosterBlock;
use Evt\PublicFront\Block\SectionsBlock;
use Evt\PublicFront\Block\SignupBlock;
use Evt\PublicFront\View\EventChrome;

/**
 * Monta el documento entero de una página de evento: `<!doctype>` incluido.
 *
 * Antes esto era un envoltorio dentro de `the_content` y el resto lo ponía el
 * tema. Ya no: la página se pinta entera aquí, como hace
 * {@see Shell::render_standalone()} con las pantallas del aplicativo. Lo que el
 * tema daba gratis pasa a ser nuestro y está escrito en la ADR: idioma,
 * `<title>`, descripción, Open Graph, el pie institucional y —lo que no es
 * opcional en un sitio público— el aviso de cookies y la analítica, que salen
 * de {@see EventChrome::consent()} y {@see EventChrome::analytics()}.
 *
 * ## El cuerpo son BLOQUES CON NOMBRE
 *
 * El armazón no sabe qué hay dentro de la página: recorre los bloques
 * registrados y les pide su HTML. Añadir uno nuevo —programa, ponentes,
 * talleres, inscripción— **no obliga a tocar este fichero**:
 *
 * ```php
 * \Evt\PublicFront\EventLayout::add_block(
 *     'programa',                                   // nombre único del bloque
 *     array( ProgramBlock::class, 'html' ),         // callable( array $m ): string
 *     40                                            // orden; menor pinta antes
 * );
 * ```
 *
 * El callable recibe {@see EventView::model()} y devuelve HTML **ya escapado**,
 * o cadena vacía para no pintar nada. Lo que necesite en el modelo lo añade con
 * el filtro `evt_event_model`, que corre al final de `model()`. El armazón
 * envuelve cada bloque en `<section class="evt-ev__bloque evt-ev__bloque--NOMBRE">`
 * y no toca nada más. Los bloques empiezan en `<h2>`: el único `<h1>` de la
 * página es el título de la portada.
 *
 * Cada bloque vive en su propio fichero bajo `PublicFront/Block/`, con el
 * mismo trato que las vistas: solo pinta, y su CSS está en
 * `assets/css/evt-evento.css` escrito con los tokens del armazón.
 *
 * ## El aspecto son TOKENS
 *
 * Todo lo que se ve sale de propiedades personalizadas escritas en un solo
 * sitio ({@see tokens()}) a partir de las metas del evento. El CSS a medida
 * cambia un token y no pelea con la especificidad de nadie. Por eso los colores
 * NO van en atributos `style`: un `style` en línea solo se pisa con
 * `!important`, y eso es justo lo contrario de «fácilmente modificable».
 */
final class EventLayout {

	/**
	 * Blocks that make up the body, by name.
	 *
	 * @var array<string, array{render:callable, priority:int, order:int}>
	 */
	private static $blocks = array();

	/**
	 * How many blocks were registered, to keep ties in registration order.
	 *
	 * @var int
	 */
	private static $seq = 0;

	/**
	 * Register the blocks the skeleton itself ships. Idempotent.
	 *
	 * Tres, y los tres son los de hoy: el contenido de la página, el cartel del
	 * evento y la rejilla de tarjetas de sección. Los demás los registran las
	 * pantallas que los traigan.
	 *
	 * @return void
	 */
	public static function register(): void {
		self::add_block( ContentBlock::NAME, array( ContentBlock::class, 'html' ), ContentBlock::PRIORITY );
		self::add_block( PosterBlock::NAME, array( PosterBlock::class, 'html' ), PosterBlock::PRIORITY );
		self::add_block( SectionsBlock::NAME, array( SectionsBlock::class, 'html' ), SectionsBlock::PRIORITY );
		self::add_block( SignupBlock::NAME, array( SignupBlock::class, 'html' ), SignupBlock::PRIORITY );
	}

	/**
	 * Register one named block of the body.
	 *
	 * @param string   $name     Unique name; also the CSS modifier of its section.
	 * @param callable $render   `function( array $model ): string`, HTML already escaped.
	 * @param int      $priority Lower paints first; ties keep registration order.
	 * @return void
	 */
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

	/**
	 * Forget one block.
	 *
	 * @param string $name Block name.
	 * @return void
	 */
	public static function remove_block( string $name ): void {
		unset( self::$blocks[ sanitize_key( $name ) ] );
	}

	/**
	 * The blocks of the body, in the order they are painted.
	 *
	 * @return array<string, array{render:callable, priority:int, order:int}>
	 */
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

		/**
		 * Filter the blocks of the body, already sorted.
		 *
		 * Para quitar uno, reordenarlos o quedarse con unos pocos sin tener que
		 * desregistrar nada.
		 *
		 * @param array<string, array{render:callable, priority:int, order:int}> $bloques Blocks.
		 */
		return (array) apply_filters( 'evt_event_blocks', $bloques );
	}

	/**
	 * The body: every block that has something to say.
	 *
	 * @param array<string, mixed> $m What EventView::model() decided.
	 * @return string
	 */
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

	/**
	 * The whole document, from the doctype to the closing tag.
	 *
	 * @param array<string, mixed> $m What EventView::model() decided.
	 * @return string
	 */
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
		// `wp_head()` escribe el `<title>` cuando el tema declara `title-tag`;
		// sin tema que lo declare —o sin tema— lo escribimos nosotros. La
		// canónica, el icono del sitio y las hojas encoladas también salen de
		// ahí, y por eso `wp_head()` sigue estando aunque el documento sea
		// nuestro.
		if ( ! current_theme_supports( 'title-tag' ) ) {
			echo '<title>' . esc_html( wp_get_document_title() ) . "</title>\n";
		}
		echo self::meta( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		wp_head();
		// El aviso de cookies va al final de la cabecera, que es donde lo ponía
		// el tema, y detrás de `wp_head()` a propósito: es de terceros y no
		// tiene que competir con nuestras hojas.
		echo EventChrome::consent(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- URL escapadas dentro.
		?>
</head>
<body <?php body_class( $clases ); ?>>
		<?php
		wp_body_open();
		echo '<a class="evt-ev__saltar visually-hidden-focusable" href="#contenido">Saltar al contenido</a>';
		echo EventChrome::header( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		?>
	<main id="contenido" class="evt-ev__main evt-ev__ancho" tabindex="-1">
		<?php echo self::body( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- cada bloque llega escapado. ?>
	</main>
		<?php
		echo EventChrome::footer(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		wp_footer();
		echo EventChrome::analytics(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido en analytics().
		?>
</body>
</html>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Description and Open Graph: what a link to this page looks like elsewhere.
	 *
	 * Esto lo ponía el tema y ahora es nuestro. Sin `og:image` no hay tarjeta en
	 * ninguna red, y el cartel del evento es exactamente la imagen que hay que
	 * enseñar.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
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

	/**
	 * The appearance of the event, as CSS custom properties, in ONE place.
	 *
	 * Solo se escriben los tokens que el evento cambia: los demás se quedan con
	 * el valor por defecto de `assets/css/evt-evento.css`, que es donde vive la
	 * lista entera y documentada.
	 *
	 * El color del texto pasa por {@see EventChrome::readable_ink()}: el
	 * contraste se comprueba, no se supone.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string A `<style>` element, empty when there is nothing to say.
	 */
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

		/**
		 * Filter the CSS custom properties of one event.
		 *
		 * El sitio que quiera fijar el ancho, el gutter o el radio por evento lo
		 * hace aquí, y sigue siendo un token y no una regla nueva.
		 *
		 * @param array<string, string> $tokens Custom property => value; empty values are dropped.
		 * @param array<string, mixed>  $m      The model.
		 */
		$tokens = (array) apply_filters( 'evt_event_tokens', $tokens, $m );

		$reglas = '';
		foreach ( $tokens as $propiedad => $valor ) {
			$valor = trim( (string) $valor );
			// Ni llaves ni punto y coma ni cierre de etiqueta: un token es un
			// valor, no un trozo de hoja de estilos.
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

// ---- src/Evt/PublicFront/EventView.php ----
/**
 * Public view of an event and of each of its satellite pages.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

use Evt\Access\EventAccess;
use Evt\Domain\DateRange;
use Evt\Domain\EventState;
use Evt\Meta\EventMetaKeys;
use Evt\PostType\EventPostType;

/**
 * Lo que ve quien visita un evento.
 *
 * No es una pantalla del aplicativo: no lleva la cabecera ni las pestañas del
 * {@see Shell}, y mantiene el aspecto reconocible de las páginas de evento de
 * hoy. Reproduce en PHP lo que en el sistema anterior hacen tres vistas
 * interpoladas: la cabecera del evento con el cuerpo de cada tipo de página, la
 * navegación entre secciones y la rejilla de tarjetas de la portada.
 *
 * ## Se pinta el documento entero, en `template_redirect`
 *
 * Esto **sustituye** la decisión anterior, y está escrito en la ADR-0022
 * (`docs/adr/ADR-0022-la-pagina-de-evento-se-pinta-entera.md`), que es donde
 * viven las cifras y las consecuencias. Antes se enganchaba en `the_content`
 * y el docblock lo argumentaba así: «la página pública del evento sí es del
 * sitio —cabecera institucional, menú y pie del tema— y lo único nuestro es lo
 * que va dentro del artículo». Medida la página real, eso no era cierto:
 *
 * - El pie institucional **no es del tema**: es un `<div class="pie">` escrito
 *   a mano dentro de un módulo de código del maquetador del tema. Al quitar el tema no se pierde,
 *   porque nunca fue suyo; se transcribe en {@see View\EventChrome::footer()}.
 *   La navegación y las tarjetas tampoco: ya eran Bootstrap escrito a mano.
 * - Lo único que aportaba el tema eran **diez niveles de anidamiento** y una hoja
 *   de 4.000 líneas para llegar a `.et_pb_text_inner`, más Content Views,
 *   table-sorter y la hoja del formulario de inscripción, que esa página no
 *   usa.
 *
 * Así que la página se pinta entera aquí, en `template_redirect` con prioridad
 * {@see PRIORITY}, exactamente como {@see Shell::render_standalone()} hace con
 * las pantallas del aplicativo, y se descarta lo que no hace falta
 * ({@see drop_page_assets()}). El documento lo monta {@see EventLayout}.
 *
 * A cambio nos hacemos responsables de lo que el tema daba gratis: el idioma,
 * el `<title>`, la descripción, Open Graph, la canónica y el icono del sitio
 * —los tres últimos siguen saliendo de `wp_head()`—. El consentimiento de
 * cookies y la analítica dependen de dónde los enganche el sitio: si es en
 * `wp_head` o `wp_footer`, siguen; si estaban en el `footer.php` del tema, hay
 * que reponerlos.
 *
 * ## Los eventos legacy siguen como hoy
 *
 * Un contenido migrado lleva la meta `evt_legacy` (ADR-0008) y **es el tema
 * congelado**: sus párrafos son `[et_pb_section]…` y su aspecto lo pinta la
 * hoja del tema. Pintarlo con el esqueleto nuevo, sin el maquetador, lo dejaría roto.
 * Por eso {@see takes_over()} responde que no y esa página sigue el camino de
 * siempre: la plantilla del tema, con sus assets y sin que toquemos nada. Es
 * una comprobación de una meta, no dos aplicativos: el día que no quede ni un
 * `evt_legacy`, se borra la comprobación y no queda rastro.
 */
final class EventView {

	/**
	 * Prioridad en `template_redirect`: la misma que usa el Shell.
	 *
	 * Después de que WordPress haya resuelto la consulta y antes de que el
	 * tema empiece a pintar. En `template_redirect` todavía se pueden mandar
	 * cabeceras, que es lo que pide servir un documento propio.
	 */
	public const PRIORITY = 20;

	/**
	 * Prioridad en `wp_head` de la hoja del evento y de sus tokens.
	 *
	 * Detrás de `wp_print_styles` (8), que es quien escribe Bootstrap 5, para
	 * que nuestra hoja pueda con él; y muy por delante del CSS a medida del
	 * evento ({@see CustomCode::CSS_PRIORITY}, 999), que tiene que ser el
	 * último de la cabecera. Entre reglas de la misma especificidad gana la
	 * última que se lee, y la última tiene que ser la de la persona.
	 */
	public const HEAD_PRIORITY = 9;

	/**
	 * Prioridad al descartar assets: después de que todo el mundo haya encolado.
	 */
	public const DROP_PRIORITY = 100;

	/**
	 * Query arg con el que el taller del evento recibe el evento.
	 */
	public const MANAGE_ARG = 'evento';

	/**
	 * Meta que marca un contenido migrado del sistema viejo (ADR-0008).
	 *
	 * Vive aquí y no en {@see EventMetaKeys} porque el guion de migración —que
	 * es quien la pone— todavía no está escrito y nadie más la lee. Cuando se
	 * escriba, su sitio es `EventMetaKeys`, junto a `ARCHIVED`, que es la otra
	 * marca y significa otra cosa.
	 */
	public const LEGACY_META = 'evt_legacy';

	/**
	 * Rótulo del botón de inscripción cuando el evento no pone otro.
	 */
	public const SIGNUP_LABEL = 'Inscríbete';

	/**
	 * Cuántos caracteres como mucho en la descripción y en Open Graph.
	 */
	private const DESCRIPTION_CHARS = 155;

	/**
	 * Assets that no event page needs, by the path they are served from.
	 *
	 * Por la ruta y no por el nombre del handle, que cambia con cada versión.
	 * `/et-cache/` es la caché del constructor del tema.
	 *
	 * @var string[]
	 */
	private const DROP_PATHS = array(
		'/themes/',
		'/et-cache/',
		'/plugins/content-views-query-and-display-post-page/',
		'/plugins/pt-content-views-pro/',
		'/plugins/table-sorter/',
	);

	/**
	 * Texto de la tarjeta cuando la sección no trae introducción.
	 *
	 * Calcados de los que trae hoy la portada del sistema anterior.
	 *
	 * @var array<string, string>
	 */
	private const DEFAULT_INTRO = array(
		'ponentes'    => 'Conoce los detalles de las personas comunicadoras.',
		'programa'    => 'Consulta el programa oficial.',
		'actividades' => 'Consulta los detalles de las actividades.',
		'contacto'    => 'Información de contacto con la organización del evento.',
	);

	/**
	 * Published satellite pages, by event, for this request.
	 *
	 * La navegación y las tarjetas piden la misma lista; sin esto son dos
	 * consultas iguales en cada portada.
	 *
	 * @var array<int, \WP_Post[]>
	 */
	private static $sections = array();

	/**
	 * The model of the page being served, computed once per request.
	 *
	 * Lo piden `wp_head` —para los tokens— y el propio documento.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private static $models = array();

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		EventLayout::register();
		add_action( 'template_redirect', array( self::class, 'render' ), self::PRIORITY );
		add_action( 'wp_head', array( self::class, 'print_stylesheet' ), self::HEAD_PRIORITY );
		add_action( 'wp_enqueue_scripts', array( self::class, 'drop_page_assets' ), self::DROP_PRIORITY );
		// Y una red por si algo se encola más tarde: el tema imprime una hoja
		// «late» en el pie, y al escribir la etiqueta se descarta.
		add_filter( 'style_loader_tag', array( self::class, 'drop_page_tag' ), 10, 3 );
		add_filter( 'script_loader_tag', array( self::class, 'drop_page_tag' ), 10, 3 );
	}

	/**
	 * Whether we paint this request ourselves instead of the theme.
	 *
	 * @return bool
	 */
	public static function takes_over(): bool {
		if ( is_admin() || ! is_singular( EventPostType::POST_TYPE ) ) {
			return false;
		}
		$post_id = (int) get_queried_object_id();
		if ( self::is_legacy( $post_id ) ) {
			return false;
		}

		/**
		 * Filter whether the event page is painted by us, without the theme.
		 *
		 * @param bool $solo    Whether to bypass the theme template.
		 * @param int  $post_id Page being viewed.
		 */
		return (bool) apply_filters( 'evt_event_standalone', true, $post_id );
	}

	/**
	 * Whether this page is frozen el tema migrated from the old system (ADR-0008).
	 *
	 * Se mira la página y también su evento: la marca la pone el guion en cada
	 * contenido migrado, y si a alguna hija se le escapó, lo que manda es que
	 * el evento sea legacy. Ante la duda se queda en el tema, que es lo que
	 * hoy funciona.
	 *
	 * @param int $post_id Page being viewed.
	 * @return bool
	 */
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

	/**
	 * Serve the whole document of an event page.
	 *
	 * Se sale por {@see Shell::leave()}, que en tests lanza su excepción en vez
	 * de terminar el proceso.
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! self::takes_over() ) {
			return;
		}

		status_header( 200 );
		Shell::send_header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );

		echo EventLayout::render( self::current_model() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- documento montado escapado.
		Shell::leave();
	}

	/**
	 * The stylesheet of the event page and its tokens, in the head.
	 *
	 * Los dos van juntos y en este orden: primero la hoja, que trae los valores
	 * por defecto de todos los tokens, y detrás los del evento, que solo pisan
	 * los que ha cambiado. El CSS a medida va después de los dos.
	 *
	 * @return void
	 */
	public static function print_stylesheet(): void {
		// La misma guarda que el documento: un evento legacy se queda con el
		// tema y no se le mete una hoja que no espera.
		if ( ! self::takes_over() ) {
			return;
		}

		$hoja = self::stylesheet();
		if ( '' !== $hoja ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- es CSS, no texto: escaparlo lo rompería.
			echo '<style id="evt-evento-css">' . $hoja . "</style>\n";
		}
		echo EventLayout::tokens( self::current_model() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido en tokens(), que filtra los valores.
	}

	/**
	 * The contents of `assets/css/evt-evento.css`.
	 *
	 * No se encola: se escribe en la cabecera, entre las hojas encoladas y el
	 * CSS a medida del evento, que tiene que ser el último. Y se pide a
	 * {@see Assets::contents()} y no al disco: en producción el único artefacto
	 * es el bundle de Code Snippets y ahí no hay repositorio que leer, solo el
	 * mapa que el empaquetador inlinea.
	 *
	 * @return string Empty when neither source is available.
	 */
	public static function stylesheet(): string {
		/**
		 * Filter the stylesheet of the public event page.
		 *
		 * @param string $css The stylesheet.
		 */
		return (string) apply_filters( 'evt_event_stylesheet', Assets::contents( 'css/evt-evento.css' ) );
	}

	/**
	 * Drop from the queue what no event page needs.
	 *
	 * El mismo patrón que {@see Shell::drop_theme_assets()}, con dos añadidos
	 * que en las pantallas del aplicativo no hacían falta: los complementos que
	 * esta página no usa —Content Views y table-sorter— y la hoja del
	 * formulario de inscripción cuando no hay ningún formulario que vestir.
	 *
	 * @return void
	 */
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

		// Trece kilobytes de guion en línea para sustituir caritas que no salen
		// en ninguna página de evento. `wp-block-library` y `global-styles` sí
		// se quedan: el contenido de la página lo escribe el editor y puede
		// llevar bloques.
		wp_dequeue_style( 'wp-emoji-styles' );
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
	}

	/**
	 * En nuestras páginas no se escribe ninguna de esas etiquetas.
	 *
	 * @param string $tag    The tag WordPress is about to print.
	 * @param string $handle Its handle.
	 * @param string $src    Its URL.
	 * @return string
	 */
	public static function drop_page_tag( string $tag, string $handle, string $src ): string {
		unset( $handle );
		if ( ! self::takes_over() ) {
			return $tag;
		}
		return self::is_droppable( $src ) ? '' : $tag;
	}

	/**
	 * Whether one asset URL is one of the ones we discard.
	 *
	 * @param string $src Its URL.
	 * @return bool
	 */
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

	/**
	 * Asset paths that belong to the sign-up form, dropped when there is no form.
	 *
	 * Vacía a propósito. Qué complemento sirve el formulario de inscripción del
	 * sistema anterior, y desde qué ruta, es cosa de cada despliegue y no se
	 * escribe aquí: quien arrastre uno lo declara con el filtro, y quien no
	 * arrastre ninguno no tiene nada que descartar.
	 *
	 * @return string[]
	 */
	private static function form_asset_paths(): array {
		/**
		 * Filter the asset paths of the legacy sign-up form.
		 *
		 * @param string[] $paths URL fragments, empty by default.
		 */
		$rutas = (array) apply_filters( 'evt_form_asset_paths', array() );
		return array_map( 'strval', $rutas );
	}

	/**
	 * Whether this page shows a sign-up form at all.
	 *
	 * Las inscripciones de los eventos que vienen del sistema anterior siguen
	 * en su formulario (ADR-0007), así que la página que lleve uno necesita su
	 * hoja; la que no, no. El identificador guardado basta para los eventos que
	 * lo traen. Si en algún despliegue el formulario va pegado en el contenido
	 * en vez de guardado, lo dice el filtro: cómo se reconoce depende de qué
	 * complemento sea, y eso no se codifica aquí.
	 *
	 * @param int $post_id Page being viewed.
	 * @return bool
	 */
	private static function has_form( int $post_id ): bool {
		if ( $post_id <= 0 ) {
			return false;
		}
		$evento = EventAccess::root_id( $post_id );
		if ( (int) get_post_meta( $evento, EventMetaKeys::SIGNUP_FORM_ID, true ) > 0 ) {
			return true;
		}

		/**
		 * Filter whether this page shows a legacy sign-up form.
		 *
		 * @param bool $has     Whether it does.
		 * @param int  $post_id Page being viewed.
		 */
		return (bool) apply_filters( 'evt_page_has_form', false, $post_id );
	}

	/**
	 * The model of the page being served, computed once per request.
	 *
	 * @return array<string, mixed>
	 */
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

	/**
	 * Everything the public view decides before painting.
	 *
	 * @param int    $post_id Page being viewed (0 = the one in the loop).
	 * @param string $content Its content, already filtered.
	 * @return array<string, mixed>
	 */
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
			// El documento es nuestro: nadie más escribe el título, así que la
			// cabecera lo escribe siempre. Antes dependía de que la página
			// llevara la plantilla en blanco del tema; ese apaño sobra.
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

		/**
		 * Filter the model of the public event page.
		 *
		 * La costura por la que un bloque nuevo —programa, ponentes, talleres,
		 * inscripción— mete lo suyo en el modelo sin tocar el armazón. Añade
		 * claves; no cambies las que ya están.
		 *
		 * @param array<string, mixed> $m       The model.
		 * @param int                  $post_id Page being viewed.
		 */
		return (array) apply_filters( 'evt_event_model', $m, $post_id );
	}

	/**
	 * The model of a page that is not an event: paint the content and nothing else.
	 *
	 * @param string $content Its content.
	 * @return array<string, mixed>
	 */
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

	/**
	 * One line describing the page, for the meta description and Open Graph.
	 *
	 * @param int $post_id  Page being viewed.
	 * @param int $event_id Its event.
	 * @return string
	 */
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

	/**
	 * The sign-up button of the event, when there is one to show.
	 *
	 * @param int $event_id Event (0 = none).
	 * @return array{show:bool, label:string, url:string}
	 */
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

	/**
	 * Link to the workshop of this event, for whoever may open it.
	 *
	 * `can_open()` y no `can_edit()`: un evento marcado como histórico se sigue
	 * consultando y exportando desde su taller (ADR-0017), y esconderle el
	 * botón a quien lo organizó es dejarlo sin la puerta. El taller ya se abre
	 * en solo lectura él solo.
	 *
	 * @param int $event_id Event.
	 * @return string Empty when this person cannot open it, or the page is not created yet.
	 */
	private static function manage_url( int $event_id ): string {
		if ( $event_id <= 0 || ! EventAccess::can_open( get_current_user_id(), $event_id ) ) {
			return '';
		}
		return Shell::url( 'event', array( self::MANAGE_ARG => $event_id ) );
	}

	/**
	 * The appearance of the event, ready to become CSS custom properties.
	 *
	 * @param int $event_id Event (0 = none, everything by default).
	 * @return array<string, string>
	 */
	private static function appearance( int $event_id ): array {
		$vacia = array(
			'bg'          => '',
			'fg'          => '',
			'title_font'  => '',
			'body_font'   => '',
			'logo'        => '',
			'logo_alt'    => '',
			'poster'      => '',
			'poster_full' => '',
			'poster_alt'  => '',
			'shape'       => EventMetaKeys::SHAPE_SQUARE,
			'separator'   => '',
		);
		if ( $event_id <= 0 ) {
			return $vacia;
		}

		$bg     = sanitize_hex_color( (string) get_post_meta( $event_id, EventMetaKeys::HEADER_BG, true ) );
		$fg     = sanitize_hex_color( (string) get_post_meta( $event_id, EventMetaKeys::HEADER_TEXT, true ) );
		$logo   = (int) get_post_meta( $event_id, EventMetaKeys::LOGO_ID, true );
		$cartel = (int) get_post_meta( $event_id, EventMetaKeys::POSTER_ID, true );

		return array(
			'bg'          => is_string( $bg ) ? $bg : '',
			'fg'          => is_string( $fg ) ? $fg : '',
			'title_font'  => self::font_stack( (string) get_post_meta( $event_id, EventMetaKeys::TITLE_FONT, true ) ),
			'body_font'   => self::font_stack( (string) get_post_meta( $event_id, EventMetaKeys::BODY_FONT, true ) ),
			'logo'        => $logo > 0 ? (string) wp_get_attachment_image_url( $logo, 'medium' ) : '',
			'logo_alt'    => $logo > 0 ? (string) get_post_meta( $logo, '_wp_attachment_image_alt', true ) : '',
			// El cartel se enseña reducido y enlaza al original: es la pieza que
			// la gente se descarga y comparte, y para eso hace falta el tamaño real.
			'poster'      => $cartel > 0 ? (string) wp_get_attachment_image_url( $cartel, 'large' ) : '',
			'poster_full' => $cartel > 0 ? (string) wp_get_attachment_image_url( $cartel, 'full' ) : '',
			'poster_alt'  => $cartel > 0 ? (string) get_post_meta( $cartel, '_wp_attachment_image_alt', true ) : '',
			'shape'       => EventMetaKeys::in_list(
				get_post_meta( $event_id, EventMetaKeys::IMAGE_SHAPE, true ),
				EventMetaKeys::image_shapes(),
				EventMetaKeys::SHAPE_SQUARE
			),
			'separator'   => EventMetaKeys::in_list(
				get_post_meta( $event_id, EventMetaKeys::SEPARATOR, true ),
				EventMetaKeys::separators()
			),
		);
	}

	/**
	 * A CSS font stack from one of the six typefaces of the closed list.
	 *
	 * @param string $slug Stored value.
	 * @return string Empty for «la del tema», which loads nothing.
	 */
	private static function font_stack( string $slug ): string {
		$fuentes = EventMetaKeys::fonts();
		$slug    = EventMetaKeys::in_list( $slug, $fuentes, EventMetaKeys::FONT_DEFAULT );
		if ( EventMetaKeys::FONT_DEFAULT === $slug ) {
			return '';
		}
		return sprintf( "'%s', 'Open Sans', Arial, sans-serif", $fuentes[ $slug ] );
	}

	/**
	 * The navigation of the event: its front page and every published section.
	 *
	 * Lo que hoy hace una vista aparte del sistema anterior. El rótulo es el
	 * título de la página y no el tipo de sección: el título lo escribe quien la
	 * crea, el tipo es un slug.
	 *
	 * @param int $event_id   Event.
	 * @param int $current_id Page being viewed.
	 * @return array<int, array{label:string, url:string, current:bool}>
	 */
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

	/**
	 * The section cards of the front page.
	 *
	 * Lo que hoy hace la portada del sistema anterior, con sus textos por
	 * defecto por tipo.
	 *
	 * @param int $event_id Event.
	 * @return array<int, array{id:int, title:string, url:string, image:string, text:string}>
	 */
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

	/**
	 * The image of one card: the featured image of the section.
	 *
	 * Hoy, cuando no hay imagen, se apunta a un fichero por tipo subido a la
	 * biblioteca (`<tipo>-default.png`). Esa ruta es de un sitio concreto y no
	 * se codifica aquí: quien despliegue la pone con el filtro.
	 *
	 * @param int    $post_id Section.
	 * @param string $type    Section type.
	 * @return string Empty when there is none: la tarjeta sale sin imagen.
	 */
	private static function card_image( int $post_id, string $type ): string {
		$url = (string) get_the_post_thumbnail_url( $post_id, 'medium' );
		if ( '' !== $url ) {
			return $url;
		}

		/**
		 * Filter the fallback card image of a section without featured image.
		 *
		 * @param string $url     Image URL, empty for none.
		 * @param string $type    Section type slug.
		 * @param int    $post_id Section post ID.
		 */
		return (string) apply_filters( 'evt_section_default_image', '', $type, $post_id );
	}

	/**
	 * Published satellite pages of an event, in the order they were given.
	 *
	 * @param int $event_id Event.
	 * @return \WP_Post[]
	 */
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

// ---- src/Evt/PublicFront/View/EventChrome.php ----
/**
 * Header, navigation, separator and footer of the public page of an event.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

/**
 * El marco de la página de un evento: lo que rodea a los bloques.
 *
 * Solo pinta: no lee la petición, no consulta, no decide.
 * Todo lo que sale de aquí llega ya escapado y lo monta
 * {@see \Evt\PublicFront\EventLayout}.
 *
 * Cuatro piezas: la navegación entre secciones, la portada de color con el
 * logo, el título y las fechas, la silueta que las separa del cuerpo, y el pie
 * con quien sea dueño del sitio.
 *
 * Y dos que no se ven pero que un tema serviría y el esqueleto tuvo que
 * reponer: el aviso de cookies ({@see consent()}) y la analítica
 * ({@see analytics()}).
 *
 * **Ninguna de las tres últimas trae nada dentro.** El pie, las cookies y la
 * analítica son de quien despliega y se configuran con el filtro
 * {@see chrome()}; sin configurar, no se pintan (ADR-0030).
 *
 * El `<h1>` de la página es el título de la portada, y es el único: los
 * bloques del cuerpo empiezan en `<h2>`.
 */
final class EventChrome {

	/**
	 * Filter that supplies the chrome of the public page.
	 *
	 * **Este repositorio no trae ni una dirección institucional.** El pie, el
	 * aviso de cookies y la analítica son de quien despliega, no del
	 * aplicativo: se rellenan desde fuera con este filtro y, sin nadie que
	 * conteste, **no se pinta ninguno de los tres**. Un aplicativo libre no
	 * puede llevar dentro el portal, el dominio ni el identificador de
	 * analítica de una organización concreta (ADR-0030).
	 */
	public const HOOK = 'evt_chrome';

	/**
	 * The cookie the notice writes, when there is a notice.
	 *
	 * Es el único valor con defecto porque no identifica a nadie: es el nombre
	 * que usa la biblioteca de avisos de cookies más extendida, y quien ponga
	 * otra lo cambia con el filtro.
	 */
	public const CONSENT_COOKIE = 'cookieconsent_status';

	/**
	 * Everything the page needs from whoever deploys it.
	 *
	 * Todo vacío por defecto, y eso es la decisión: lo que no se configura no
	 * se pinta. Así el aplicativo se publica sin llevar dentro nada de nadie y
	 * una instalación nueva no envía datos a ningún sitio sin decirlo.
	 *
	 * @return array<string, mixed>
	 */
	public static function chrome(): array {
		$defecto = array(
			// Pie y cabecera: de quién es el sitio y quién lo hizo.
			'owner'          => '',
			'owner_url'      => '',
			'org'            => '',
			'credit'         => '',
			'footer_links'   => array(),
			// Aviso de cookies: las tres piezas que lo pintan.
			'consent_css'    => '',
			'consent_js'     => '',
			'consent_init'   => '',
			'consent_cookie' => self::CONSENT_COOKIE,
			// Analítica: sin esto no se carga nada y no se envía ni una visita.
			'matomo_api'     => '',
			'matomo_js'      => '',
			'matomo_site'    => 0,
		);

		/**
		 * Filter the chrome of the public event page.
		 *
		 * @param array<string, mixed> $chrome Empty defaults.
		 */
		$puesto = apply_filters( self::HOOK, $defecto );

		return is_array( $puesto ) ? array_merge( $defecto, $puesto ) : $defecto;
	}

	/**
	 * Minimum contrast ratio for normal text (WCAG 2.1 AA).
	 */
	public const MIN_CONTRAST = 4.5;

	/**
	 * Outlines of the cover separators, on a 1200×60 canvas.
	 *
	 * Pública porque la vista previa del panel de apariencia pinta estas mismas
	 * siluetas: si tuviera las suyas, enseñaría una cabecera que no es la que
	 * se va a publicar. Vivían en `EventViewHtml`, que era la vista vieja de la
	 * página pública; al portarla al esqueleto de bloques se retiró aquel
	 * fichero y las siluetas se quedaron donde se dibujan.
	 *
	 * @var array<string, string>
	 */
	public const SEPARATORS = array(
		'slant'    => 'M0,60 L1200,0 L1200,60 Z',
		'ramp'     => 'M0,60 L1200,24 L1200,60 Z',
		'curve'    => 'M0,60 Q600,-10 1200,60 Z',
		'wave'     => 'M0,38 C300,68 900,-6 1200,38 L1200,60 L0,60 Z',
		'triangle' => 'M0,60 L600,0 L1200,60 Z',
	);

	/**
	 * The whole header: navigation, cover and separator.
	 *
	 * @param array<string, mixed> $m What EventView::model() decided.
	 * @return string
	 */
	public static function header( array $m ): string {
		return '<header class="evt-ev__cabecera">'
			. self::nav( (array) $m['nav'] )
			. self::cover( $m )
			. '</header>';
	}

	/**
	 * The navigation between the sections of the event.
	 *
	 * Con una sola entrada no hay entre qué navegar y no se pinta: un menú de
	 * un elemento es ruido.
	 *
	 * @param array<int, array{label:string, url:string, current:bool}> $items Menu entries.
	 * @return string
	 */
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

	/**
	 * The cover: logo, title, tagline, dates, venue, state and sign-up.
	 *
	 * Los colores no van en un atributo `style`: son los tokens `--evt-fondo` y
	 * `--evt-texto`, que escribe {@see \Evt\PublicFront\EventLayout::tokens()}
	 * en un solo sitio. Así el CSS a medida de un evento cambia el token y no
	 * tiene que ganarle a un `style` en línea, que no hay forma de pisar sin
	 * `!important`.
	 *
	 * @param array<string, mixed> $m What EventView::model() decided.
	 * @return string
	 */
	public static function cover( array $m ): string {
		$look   = (array) $m['appearance'];
		$signup = (array) $m['signup'];

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
			<?php echo self::separator( (string) $look['separator'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- silueta de la lista cerrada, escapada dentro. ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The outline at the foot of the cover.
	 *
	 * Las siluetas son las de {@see self::SEPARATORS}: son las mismas que
	 * enseña la vista previa del panel de apariencia, y tener dos juegos sería
	 * enseñar una cabecera distinta de la que se publica.
	 *
	 * @param string $shape One of EventMetaKeys::separators().
	 * @return string Empty for «sin separador».
	 */
	public static function separator( string $shape ): string {
		$trazo = self::SEPARATORS[ $shape ] ?? '';
		if ( '' === $trazo ) {
			return '';
		}
		return '<svg class="evt-ev__separador" viewBox="0 0 1200 60" preserveAspectRatio="none" aria-hidden="true" focusable="false">'
			. '<path d="' . esc_attr( $trazo ) . '" fill="var(--evt-papel)"/></svg>';
	}

	/**
	 * The footer: who owns the site, and its legal notices.
	 *
	 * **Vacío salvo que alguien lo configure** ({@see chrome()}). El pie es de
	 * quien despliega, no del aplicativo.
	 *
	 * @return string
	 */
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

	/**
	 * The cookie notice, for the head of the document.
	 *
	 * Al pintar el documento entero, lo que el tema metía antes de `</head>`
	 * deja de ponerse solo. En un sitio público de una administración el aviso
	 * de cookies no es opcional, así que hay dónde reponerlo — pero **las URL
	 * son de quien despliega** ({@see chrome()}) y aquí no hay ninguna.
	 *
	 * El orden importa: la hoja, la biblioteca y, detrás, el guion que la
	 * inicializa.
	 *
	 * **Si esas URL no responden, la página no se rompe.** Los dos guiones van
	 * con `defer`: no bloquean el análisis del documento y, si uno no llega, el
	 * fallo se queda dentro de él. Y si la hoja no llega, el aviso se pinta sin
	 * estilo pero se puede cerrar.
	 *
	 * @return string
	 */
	public static function consent(): string {
		$chrome = self::chrome();
		$html   = '';

		// phpcs:disable WordPress.WP.EnqueuedResources -- el documento de la página de evento lo escribimos nosotros entero: el aviso legal no puede depender de que nadie lo saque de la cola.
		if ( '' !== (string) $chrome['consent_css'] ) {
			$html .= '<link rel="stylesheet" href="' . esc_url( (string) $chrome['consent_css'] ) . '" />' . "\n";
		}
		if ( '' !== (string) $chrome['consent_js'] ) {
			$html .= '<script src="' . esc_url( (string) $chrome['consent_js'] ) . '" defer></script>' . "\n";
		}
		if ( '' !== (string) $chrome['consent_init'] ) {
			$html .= '<script src="' . esc_url( (string) $chrome['consent_init'] ) . '" defer></script>' . "\n";
		}
		// phpcs:enable WordPress.WP.EnqueuedResources

		return $html;
	}

	/**
	 * Matomo, for the end of the body, and only when it counts for real.
	 *
	 * **No se emite nada salvo que se configure** ({@see chrome()}): un
	 * aplicativo libre no lleva dentro a qué servidor de analítica envía las
	 * visitas de nadie.
	 *
	 * **Y respeta el consentimiento**, que hoy no lo hace: con
	 * `requireCookieConsent` Matomo cuenta la visita sin escribir ni una
	 * cookie, y solo las escribe cuando el aviso institucional dice que sí. La
	 * única fuente de verdad es la cookie del propio aviso, así que no hay dos
	 * sitios donde mirar ni un segundo registro de consentimiento que mantener.
	 *
	 * Fuera de producción tampoco se emite: en desarrollo mandaría visitas de
	 * `localhost` a la estadística de verdad.
	 *
	 * @return string Empty when there is no site to count for.
	 */
	public static function analytics(): string {
		$chrome = self::chrome();
		$site   = (int) $chrome['matomo_site'];
		$api    = (string) $chrome['matomo_api'];
		$js_url = (string) $chrome['matomo_js'];

		/**
		 * Filter whether the analytics snippet is emitted at all.
		 *
		 * Por defecto solo en producción: en desarrollo mandaría visitas de
		 * `localhost` a la estadística de verdad. Es un filtro y no una
		 * comprobación suelta para que se pueda apagar en producción sin tocar
		 * la configuración, y encender en un entorno de pruebas a propósito.
		 *
		 * @param bool $encendida Whether to emit the analytics snippet.
		 */
		$encendida = (bool) apply_filters( 'evt_analytics_enabled', 'production' === wp_get_environment_type() );

		// Sin las tres cosas configuradas no se emite nada, y apagada tampoco.
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

		// phpcs:disable WordPress.WP.EnqueuedResources -- el documento de la página de evento lo escribimos nosotros entero: el aviso legal no puede depender de que nadie lo saque de la cola.
		$html = '<script id="evt-matomo">' . $js . '</script>' . "\n"
			. '<script src="' . esc_url( $js_url ) . '" async defer></script>' . "\n";
		// phpcs:enable WordPress.WP.EnqueuedResources
		return $html;
	}

	/**
	 * Contrast ratio between two colours, WCAG 2.1 relative luminance.
	 *
	 * Pública porque el panel de apariencia tiene que avisar cuando la
	 * combinación que se está eligiendo no llega a 4,5:1, y el aviso y lo que
	 * se publica tienen que salir del mismo cálculo.
	 *
	 * @param string $uno Hex colour, `#rgb` or `#rrggbb`.
	 * @param string $dos Hex colour.
	 * @return float 1.0 when either colour is not a hex colour: sin dato no se acusa a nadie.
	 */
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

	/**
	 * The ink to write on a background: the chosen one, or a readable one.
	 *
	 * El contraste se comprueba, no se supone: si la combinación que eligió el
	 * evento no llega a 4,5:1 se escribe en blanco o en negro, el que gane
	 * sobre ese fondo. Vale más una cabecera con el color de texto cambiado que
	 * un título que no se lee. El panel de apariencia avisa antes de guardar,
	 * pero esto tiene que aguantar lo que ya está guardado y lo que llegue de
	 * la migración.
	 *
	 * @param string $fondo Background colour, hex.
	 * @param string $texto Chosen text colour, hex; empty to decide from scratch.
	 * @return string Hex colour.
	 */
	public static function readable_ink( string $fondo, string $texto ): string {
		if ( '' !== $texto && self::contrast_ratio( $fondo, $texto ) >= self::MIN_CONTRAST ) {
			return $texto;
		}
		return self::contrast_ratio( $fondo, '#ffffff' ) >= self::contrast_ratio( $fondo, '#000000' )
			? '#ffffff'
			: '#000000';
	}

	/**
	 * WCAG relative luminance of a hex colour.
	 *
	 * @param string $hex `#rgb` or `#rrggbb`.
	 * @return float -1.0 when it is not a hex colour.
	 */
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

// ---- src/Evt/PublicFront/Home.php ----
/**
 * Landing page of the events application.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

use Evt\Access\EventAccess;

/**
 * Shortcode [evt_home]: la puerta de entrada.
 *
 * Una sola dirección que publicar en el menú, y detrás cada persona entra
 * directamente en su pantalla en vez de en un saludo con enlaces. Los accesos
 * en tarjeta se siguen pintando para quien llega sin sitio a donde ir: sin
 * permisos todavía, o con el perfil a medio rellenar.
 */
final class Home {

	public const SHORTCODE = 'evt_home';

	/**
	 * Register the shortcode and the redirection.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'render' ) );
		add_action( 'template_redirect', array( self::class, 'send_to_landing' ) );
	}

	/**
	 * Send whoever lands on the entry page to their first screen.
	 *
	 * @return void
	 */
	public static function send_to_landing(): void {
		if ( is_admin() || ! is_singular() || ! is_user_logged_in() ) {
			return;
		}
		$post = get_post();
		if ( ! $post instanceof \WP_Post || ! has_shortcode( (string) $post->post_content, self::SHORTCODE ) ) {
			return;
		}

		$destino = self::landing();
		// Si la portada es además la pantalla de destino, no hay a dónde ir.
		if ( '' === $destino || untrailingslashit( $destino ) === untrailingslashit( (string) get_permalink( $post ) ) ) {
			return;
		}

		Shell::leave( $destino );
	}

	/**
	 * First screen of whoever is looking.
	 *
	 * @return string Empty when there is none.
	 */
	public static function landing(): string {
		foreach ( Shell::sections() as $clave => $seccion ) {
			// «Ajustes» es el escritorio de WordPress, no una pantalla en la
			// que empezar el día.
			if ( 'settings' === $clave ) {
				continue;
			}
			return (string) $seccion['url'];
		}
		return '';
	}

	/**
	 * What the landing page has to say.
	 *
	 * @return array{logged_in:bool, name:string, sections:array<string, array{label:string, url:string, badge:?int}>, reason:string}
	 */
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

	/**
	 * The landing page, from its model.
	 *
	 * @param array{logged_in:bool, name:string, sections:array<string, array{label:string, url:string, badge:?int}>, reason:string} $model What model() returned.
	 * @return string
	 */
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

	/**
	 * Render the shortcode.
	 *
	 * @param array<string, string>|string $atts Attributes.
	 * @return string
	 */
	public static function render( $atts = array() ): string {
		unset( $atts );
		Assets::enqueue();
		return self::html( self::model() );
	}
}

// ---- src/Evt/Admin/EventAdmin.php ----
/**
 * Admin list table for events: área scoping and columns.
 *
 * @package Evt
 */

namespace Evt\Admin;

use Evt\Access\EventAccess;
use Evt\Domain\EventState;
use Evt\Meta\EventMetaKeys;
use Evt\PostType\ActivityPostType;
use Evt\PostType\EventPostType;
use Evt\PostType\SpeakerPostType;
use Evt\Taxonomy\EventTaxonomies;

/**
 * Lo que ve cada área en el escritorio.
 *
 * El acotado de aquí solo esconde filas; quien de verdad cierra la puerta es
 * `EventAccess::map_meta_cap()`. Las dos capas hacen falta: sin esta, el
 * listado enseña los eventos de todas las áreas aunque no se puedan abrir.
 */
final class EventAdmin {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'pre_get_posts', array( self::class, 'scope_admin_query' ) );
		add_filter( 'manage_' . EventPostType::POST_TYPE . '_posts_columns', array( self::class, 'columns' ) );
		add_action( 'manage_' . EventPostType::POST_TYPE . '_posts_custom_column', array( self::class, 'column_content' ), 10, 2 );
		add_action( 'admin_menu', array( self::class, 'add_new_submenus' ), 20 );
	}

	/**
	 * Post types that hang off the events menu instead of having their own.
	 *
	 * @return array<int, string>
	 */
	public static function submenu_post_types(): array {
		return array( SpeakerPostType::POST_TYPE, ActivityPostType::POST_TYPE );
	}

	/**
	 * Register the «Añadir …» entry of the two CPT that live under Eventos.
	 *
	 * Ponentes y actividades cuelgan del menú de Eventos (`show_in_menu` con
	 * la dirección del padre, no `true`). Para esos, WordPress registra en el
	 * submenú **solo** el listado: `_add_post_type_submenus()` añade
	 * `edit.php?post_type=…` y nada más. Y `post-new.php` comprueba luego que
	 * la pantalla esté en el menú de quien la pide
	 * (`user_can_access_admin_page()`), así que el alta quedaba cerrada para
	 * todo el que no fuera administración: el botón «Añadir ponente» llevaba a
	 * «Lo siento, no tienes permisos para acceder a esta página» aunque las
	 * capacidades estuvieran todas concedidas. Comprobado con `evt_organiser`
	 * en el wp-env del proyecto.
	 *
	 * La capacidad que se exige es la misma que pide el propio WordPress al
	 * entrar (`cap->create_posts`), para no inventar aquí una segunda regla
	 * que mañana no coincida con la de `map_meta_cap`.
	 *
	 * @return void
	 */
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

	/**
	 * Limit the events list to the áreas of whoever is looking.
	 *
	 * @param \WP_Query $query Query.
	 * @return void
	 */
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
			// Falla en cerrado: sin área en el perfil, ni una fila. Un listado
			// completo por un campo sin rellenar es como un área lee la de otra.
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

	/**
	 * Add the área and state columns after the title.
	 *
	 * @param array<string, string> $columns Columns.
	 * @return array<string, string>
	 */
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

	/**
	 * Render the custom column cells.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
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
			// El cierre se dice también aquí: si no, en el escritorio una fila
			// que no se deja editar no explica por qué.
			echo esc_html(
				EventAccess::is_archived( $post_id )
					? EventState::label( $state ) . ' · Histórico'
					: EventState::label( $state )
			);
		}
	}
}

// ---- src/Evt/Admin/Settings.php ----
/**
 * Settings and diagnostics page for the application.
 *
 * @package Evt
 */

namespace Evt\Admin;

use Evt\Access\EventAccess;
use Evt\PostType\ActivityPostType;
use Evt\PostType\EventPostType;
use Evt\PostType\SpeakerPostType;
use Evt\Taxonomy\EventTaxonomies;

/**
 * Qué hay montado y qué falta, en una pantalla.
 *
 * De momento no guarda nada: la fase 1 no tiene ningún ajuste que decidir, y
 * lo que sí hace falta es poder mirar si los tipos, las taxonomías y los roles
 * están donde deberían sin abrir la base de datos.
 */
final class Settings {

	/**
	 * Menu slug.
	 */
	public const PAGE = 'evt-settings';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ) );
	}

	/**
	 * Add the submenu page.
	 *
	 * @return void
	 */
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

	/**
	 * Render the diagnostics page.
	 *
	 * @return void
	 */
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
				Esta pantalla no guarda nada todavía: cuenta lo que el aplicativo tiene montado
				en este sitio. Si algo sale «sin registrar», es que el snippet correspondiente
				no está activo.
			</p>

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

// ---- src/Evt/App.php ----
/**
 * Application bootstrap for the events CPT app.
 *
 * @package Evt
 */

namespace Evt;

use Evt\Access\EventAccess;
use Evt\Admin\EventAdmin;
use Evt\Admin\Settings;
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
use Evt\PublicFront\Registrations;
use Evt\PublicFront\SignupForm;
use Evt\PublicFront\Shell;
use Evt\Taxonomy\EventTaxonomies;

/**
 * Wires hooks for the modular events application.
 *
 * Se llama `App` y no `Plugin` a propósito: el validador de Code Snippets
 * compara los nombres declarados ignorando el namespace, así que un
 * `Evt\Plugin` chocaría con el `Code_Snippets\Plugin` del propio plugin y el
 * snippet se rechazaría con «code did not pass validation».
 */
final class App {

	/**
	 * Option holding the parent page the application pages hang from.
	 *
	 * La escribe `scripts/setup-pages.php` y la lee {@see page_slug()}: sin
	 * ella `get_page_by_path()` buscaría «mis-eventos» en la raíz del sitio y
	 * no encontraría la hija de «/eventos/mis-eventos».
	 */
	public const PAGES_PARENT = 'evt_pages_parent';

	/**
	 * Boot the application (idempotent).
	 *
	 * @return void
	 */
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

		// Las páginas cuelgan de una madre si quien despliega lo pidió, y los
		// enlaces del armazón tienen que llevar la ruta entera.
		add_filter( 'evt_page_slug', array( self::class, 'page_slug' ), 10, 2 );

		// El armazón primero: manda al acceso a quien no ha entrado (y lo hace
		// antes de que `Home` redirija a nadie a ninguna pantalla), y deja
		// registrada la hoja de estilos que las pantallas dan por puesta.
		Assets::register();
		Shell::register();

		// Cada pantalla engancha su propio shortcode y, si muta, su manejador
		// de POST en `init` 20: después de los tipos y sus capacidades.
		Registrations::register();
		SignupForm::register();
		EventList::register();
		EventWorkspace::register();
		PageForm::register();
		Home::register();

		// La vista pública del evento no es una pantalla del aplicativo: no
		// lleva ni la cabecera ni las pestañas del armazón. Se pinta el
		// documento entero en `template_redirect`, como las pantallas, y un
		// evento migrado se queda en el tema (ADR-0022).
		EventView::register();

		// El CSS y el JavaScript a medida se imprimen en la página pública del
		// evento y en ninguna otra: `wp_head` y `wp_footer`, los dos al final
		// de su cola. Quién los puede escribir lo deciden `EventAccess` y el
		// `auth_callback` de sus dos metas; aquí solo se enchufa la salida.
		CustomCode::register();

		EventAdmin::register();
		Settings::register();
	}

	/**
	 * Prefix a section slug with the parent page, when there is one.
	 *
	 * @param string $slug    Page path proposed by the shell.
	 * @param string $section Section key.
	 * @return string
	 */
	public static function page_slug( string $slug, string $section = '' ): string {
		unset( $section );
		$padre = trim( (string) get_option( self::PAGES_PARENT, '' ), '/' );
		if ( '' === $padre || '' === $slug || 0 === strpos( $slug, $padre . '/' ) ) {
			return $slug;
		}
		return $padre . '/' . $slug;
	}
}

// ---- assets/ (inlined) ----
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

/* --- las tres imágenes del evento ---------------------------------------- */

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

	/* --- 4. Elegir una imagen de la biblioteca --------------------------- */

	/*
	 * Cada imagen del panel de apariencia es un `[data-evt-media]` con:
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
			mini.src = chica ? chica.url : adjunto.url;
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
		var marco = window.wp.media( {
			title: caja.getAttribute( \'data-evt-media-title\' ) || \'Elegir imagen\',
			button: { text: \'Usar esta imagen\' },
			library: { type: \'image\' },
			multiple: false
		} );
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
