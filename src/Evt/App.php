<?php
/**
 * Application bootstrap for the events CPT app.
 *
 * @package Evt
 */

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
		// Los documentos privados de una inscripción: el manejador de descarga
		// y la limpieza al borrarla. **No son adjuntos de WordPress** y no
		// tocan la biblioteca de medios (ADR-0036).
		RegistrationFiles::register();
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
		CentreCatalog::register();
		CentreCatalogueSync::register_cron();
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
