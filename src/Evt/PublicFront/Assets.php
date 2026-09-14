<?php
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
