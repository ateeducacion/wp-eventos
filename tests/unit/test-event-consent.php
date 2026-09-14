<?php
/**
 * Tests for the cookie notice and the analytics of the public event pages.
 *
 * @package Evt
 */

use Evt\PublicFront\EventView;
use Evt\PublicFront\View\EventChrome;

/**
 * Lo que el tema servía y el esqueleto nuevo tuvo que reponer.
 *
 * El aviso de cookies no lo ponía `wp_head()`: era el código de integración
 * del tema, escrito a mano justo antes de `</head>`. Al pintar nosotros el
 * documento entero desapareció, y en un sitio público de una administración
 * española eso no es un detalle de estilo. Estos tests son la alarma de que
 * vuelva a caerse: comprueban que sale en toda página de evento del esqueleto,
 * que no se cuela dondel tema sigue mandando, y que la analítica no escribe
 * cookies antes de que alguien diga que sí.
 */
class Test_Event_Consent extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Sin nada cacheado del test anterior.
	 */
	public function set_up() {
		parent::set_up();
		$this->forget_models();
	}

	/**
	 * Vaciar la caché de modelos por petición de EventView.
	 *
	 * @return void
	 */
	private function forget_models(): void {
		$prop = new ReflectionProperty( EventView::class, 'models' );
		$prop->setAccessible( true );
		$prop->setValue( null, array() );
	}

	/**
	 * Un evento cualquiera.
	 *
	 * @return int
	 */
	private function un_evento(): int {
		return $this->event(
			$this->administrator(),
			array( $this->area( 'Innovación' ) ),
			array(),
			array( 'post_title' => 'Jornadas de escuelas rurales' )
		);
	}

	/**
	 * El documento servido para una página.
	 *
	 * @param int $post_id Page to visit.
	 * @return string
	 */
	private function documento( int $post_id ): string {
		$this->acting_as( 0 );
		$this->go_to( (string) get_permalink( $post_id ) );
		$this->forget_models();
		return $this->served( array( EventView::class, 'render' ) );
	}

	// ─── el aviso sale donde tiene que salir ───────────────────────────────

	/**
	 * El aviso de cookies se sirve en la portada del evento y en cada sección.
	 *
	 * Las tres URL son las institucionales, las mismas que carga hoy el resto
	 * del sitio: la cookie `cookieconsent_status` es una sola para todo el
	 * portal y quien cierra el aviso una vez lo cierra en todas partes. Traer
	 * otra biblioteca sería enseñárselo dos veces.
	 */
	/**
	 * Un armazón de prueba: el aplicativo no trae ninguno (ADR-0030).
	 *
	 * @return void
	 */
	private function chrome(): void {
		add_filter(
			EventChrome::HOOK,
			static function ( array $chrome ): array {
				return array_merge(
					$chrome,
					array(
						'owner'        => 'Organización de ejemplo',
						'consent_css'  => self::CSS,
						'consent_js'   => self::JS,
						'consent_init' => self::INIT,
						'matomo_api'   => 'https://analitica.example.org/matomo.php',
						'matomo_js'    => 'https://analitica.example.org/matomo.js',
						'matomo_site'  => 7,
					)
				);
			}
		);
	}

	private const CSS  = 'https://www.example.org/cookies/cookieconsent.min.css';
	private const JS   = 'https://www.example.org/cookies/cookieconsent.min.js';
	private const INIT = 'https://www.example.org/cookies/init.js';

	/**
	 * Sin configurar, no se pinta ni una línea: ni cookies, ni analítica.
	 *
	 * Es la decisión de la ADR-0030: un aplicativo libre no lleva dentro a qué
	 * servidor manda las visitas de nadie, así que por defecto no manda ninguna.
	 */
	public function test_without_configuration_nothing_is_emitted() {
		// El mu-plugin de desarrollo rellena el armazón para que en el wp-env se
		// vea algo; aquí se comprueba el defecto del aplicativo, así que se
		// quita lo que haya puesto nadie.
		remove_all_filters( EventChrome::HOOK );
		$this->assertSame( '', EventChrome::consent(), 'sin configurar, no hay aviso de cookies' );
		$this->assertSame( '', EventChrome::analytics(), 'ni analítica' );
		$this->assertStringNotContainsString( 'example.org', EventChrome::footer(), 'ni pie con enlaces de nadie' );
	}

	/**
	 * Configurado, el aviso sale en cada página de evento y en su cabecera.
	 */
	public function test_the_cookie_notice_is_served_on_every_event_page() {
		$this->chrome();
		$evento  = $this->un_evento();
		$seccion = $this->event_page( $evento, 'programa', array( 'post_title' => 'Programa' ) );

		foreach ( array( $evento, $seccion ) as $post_id ) {
			$html = $this->documento( $post_id );

			$this->assertStringContainsString( '</html>', $html, 'el documento sale entero' );
			$this->assertStringContainsString( self::CSS, $html );
			$this->assertStringContainsString( self::JS, $html );
			$this->assertStringContainsString( self::INIT, $html );

			// Y en la cabecera, que es donde lo ponía el tema.
			$cabecera = substr( $html, 0, (int) strpos( $html, '</head>' ) );
			$this->assertStringContainsString( self::CSS, $cabecera );
			$this->assertStringContainsString( self::INIT, $cabecera );
		}
	}

	/**
	 * En este orden: la hoja, la biblioteca y el guion que la inicializa.
	 *
	 * `cauce_cookie.js` llama a `window.cookieconsent.initialise()`, así que si
	 * se adelanta a la biblioteca no hay aviso. Con `defer` el navegador
	 * respeta el orden del documento, y por eso los dos guiones lo llevan.
	 */
	public function test_the_notice_loads_in_order_and_never_blocks_the_page() {
		$this->chrome();
		$html = $this->documento( $this->un_evento() );

		$this->assertLessThan(
			(int) strpos( $html, self::INIT ),
			(int) strpos( $html, self::JS ),
			'la biblioteca antes que su inicialización'
		);

		// Si esas URL no responden, la página no se rompe: ningún guion
		// bloquea el análisis del documento.
		$this->assertStringContainsString( self::JS . '" defer>', $html );
		$this->assertStringContainsString( self::INIT . '" defer>', $html );
	}

	/**
	 * Y sale una sola vez: dos avisos serían dos ventanas encima de la página.
	 */
	public function test_the_notice_is_served_exactly_once() {
		$this->chrome();
		$html = $this->documento( $this->un_evento() );

		$this->assertSame( 1, substr_count( $html, self::CSS ) );
		$this->assertSame( 1, substr_count( $html, self::INIT ) );
	}

	// ─── y no sale donde no toca ───────────────────────────────────────────

	/**
	 * En un evento legacy no lo ponemos nosotros: lo sigue poniendo el tema.
	 *
	 * Un contenido migrado (ADR-0008) es contenido del maquetador, congelado y lo pinta la plantilla
	 * del tema, con su código de integración y su aviso. Si además lo
	 * escribiéramos aquí saldría dos veces.
	 */
	public function test_the_notice_does_not_reach_a_legacy_page() {
		$evento = $this->un_evento();
		update_post_meta( $evento, EventView::LEGACY_META, '1' );

		$html = $this->documento( $evento );

		$this->assertSame( '', $html, 'la página legacy la pinta el tema, no nosotros' );
	}

	/**
	 * Ni en lo que no es una página de evento.
	 */
	public function test_the_notice_does_not_reach_the_rest_of_the_site() {
		$pagina = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_title'  => 'Quiénes somos',
				'post_status' => 'publish',
			)
		);

		$this->assertSame( '', $this->documento( (int) $pagina ) );
	}

	// ─── la analítica ──────────────────────────────────────────────────────

	/**
	 * Fuera de producción no hay analítica: `localhost` no cuenta visitas.
	 */
	public function test_analytics_stays_out_of_development() {
		$this->assertSame( 'local', wp_get_environment_type(), 'el entorno de pruebas no es producción' );
		$this->assertSame( '', EventChrome::analytics() );
		$this->assertStringNotContainsString( 'matomo', $this->documento( $this->un_evento() ) );
	}

	/**
	 * Encendida, Matomo no escribe una cookie hasta que el aviso dice que sí.
	 *
	 * `requireCookieConsent` es la pieza: cuenta la visita sin cookie ninguna y
	 * solo las escribe cuando `cookieconsent_status` ya vale `allow` o
	 * `dismiss`. La única fuente de verdad es la cookie del propio aviso, así
	 * que no hay dos registros de consentimiento que puedan discrepar.
	 */
	public function test_analytics_waits_for_the_cookie_notice() {
		$this->chrome();
		add_filter( 'evt_analytics_enabled', '__return_true' );

		$html = $this->documento( $this->un_evento() );

		$this->assertStringContainsString( '_paq.push(["requireCookieConsent"]);', $html );
		$this->assertStringContainsString( EventChrome::CONSENT_COOKIE, $html, 'mira la cookie del aviso institucional' );
		$this->assertStringContainsString( '_paq.push(["setCookieConsentGiven"]);', $html );
		$this->assertStringContainsString( '_paq.push(["setSiteId",' . 7 . ']);', $html );
		$this->assertStringContainsString( 'https://analitica.example.org/matomo.php', $html );

		// El consentimiento se pide ANTES de que se cuente nada.
		$this->assertLessThan(
			(int) strpos( $html, 'trackPageView' ),
			(int) strpos( $html, 'requireCookieConsent' ),
			'primero el consentimiento, luego la visita'
		);

		// Y no vuelve la forma de cargarlo del sistema anterior: el guion del
		// rastreador se traía con `document.write()`, que en un documento
		// diferido borra la página.
		$this->assertStringNotContainsString( 'document.write', $html );
	}
}
