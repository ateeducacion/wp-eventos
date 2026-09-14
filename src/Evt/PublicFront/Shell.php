<?php
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
