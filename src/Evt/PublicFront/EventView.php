<?php
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
