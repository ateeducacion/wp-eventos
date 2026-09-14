<?php
/**
 * Tests for the plumbing of the skeleton: packaging, theme bypass and tokens.
 *
 * @package Evt
 */

use Evt\Meta\EventMetaKeys;
use Evt\Meta\EventMetaRegistration;
use Evt\PublicFront\Assets;
use Evt\PublicFront\EventLayout;
use Evt\PublicFront\EventView;

/**
 * Lo que sostiene al armazón y no se ve hasta que falla en producción.
 *
 * `Test_Event_Layout` cubre el documento, los bloques y el contraste. Aquí
 * están las tres costuras que solo se rompen fuera del entorno de desarrollo:
 * que la hoja del evento viaje dentro del bundle, que la petición se corte
 * antes de que el tema pinte nada, y que los tokens salgan de las metas del
 * evento y no de ningún otro sitio.
 */
class Test_Event_Skeleton extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Con las metas registradas y sin nada cacheado del test anterior.
	 */
	public function set_up() {
		parent::set_up();
		EventMetaRegistration::register_meta();
		$this->forget_models();
	}

	/**
	 * Root of the repository.
	 *
	 * @return string
	 */
	private function raiz(): string {
		return dirname( __DIR__, 2 );
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
	 * Un evento sin ninguna meta de apariencia puesta.
	 *
	 * @return int
	 */
	private function un_evento(): int {
		return $this->event(
			$this->administrator(),
			array( $this->area( 'Innovación' ) ),
			array(),
			array( 'post_title' => 'Jornadas sin maquillar' )
		);
	}

	// ─── el empaquetado ────────────────────────────────────────────────────

	/**
	 * El bundle lleva dentro TODA la carpeta assets, hoja del evento incluida.
	 *
	 * En producción no hay repositorio en disco: el único artefacto es el
	 * snippet. Si la hoja no viaja inlineada, `Assets::contents()` devuelve
	 * cadena vacía, `EventView::print_stylesheet()` no escribe nada y la página
	 * sale sin estilos —sin un solo error que lo delate—. Se comprueba contra
	 * el directorio y no contra una lista escrita a mano: un asset nuevo que
	 * nadie empaquetó falla aquí, con su nombre.
	 */
	public function test_the_bundle_inlines_every_asset() {
		$bundle = $this->raiz() . '/snippets/evt-eventos-app.bundle.php';
		$this->assertFileIsReadable( $bundle, 'ejecuta php build/pack-snippet.php' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions -- se lee un artefacto del propio repositorio.
		$codigo = (string) file_get_contents( $bundle );
		$this->assertStringContainsString( 'Assets::set_inline(', $codigo );

		$assets = array();
		foreach ( array( 'css', 'js' ) as $tipo ) {
			foreach ( (array) glob( $this->raiz() . '/assets/' . $tipo . '/*.' . $tipo ) as $path ) {
				$assets[] = $tipo . '/' . basename( (string) $path );
			}
		}
		$this->assertContains( 'css/evt-evento.css', $assets, 'la hoja de la página pública del evento' );

		foreach ( $assets as $rel ) {
			$this->assertStringContainsString(
				"'" . $rel . "' =>",
				$codigo,
				$rel . ' no está inlineado en el bundle: ejecuta php build/pack-snippet.php'
			);
		}

		// Y no es una clave vacía: el contenido de la hoja está ahí de verdad.
		$this->assertStringContainsString( '--evt-fondo:', str_replace( ' ', '', $codigo ) );
		$this->assertNotSame( '', Assets::contents( 'css/evt-evento.css' ) );
	}

	// ─── ni cabecera ni pie del tema ───────────────────────────────────────

	/**
	 * La petición se corta en `template_redirect`: el tema no llega a pintar.
	 *
	 * Es el mecanismo entero, y por eso se comprueba y no se supone. Como
	 * `render()` sale por `Shell::leave()`, WordPress nunca carga la plantilla
	 * del tema, así que ni `get_header()` ni `get_footer()` llegan a correr y
	 * en el documento no hay una sola etiqueta que no hayamos escrito nosotros.
	 */
	public function test_the_request_ends_before_the_theme_paints() {
		$evento = $this->un_evento();
		$this->acting_as( 0 );
		$this->go_to( (string) get_permalink( $evento ) );

		$this->assertTrue( EventView::takes_over() );

		ob_start();
		$salida = $this->exit_url( array( EventView::class, 'render' ) );
		$html   = (string) ob_get_clean();

		$this->assertSame( '', $salida, 'se sirve el documento y se termina la petición ahí mismo' );
		$this->assertStringContainsString( '</html>', $html, 'y el documento sale entero' );

		// El pie que hay es el institucional, transcrito por nosotros; el del
		// tema no aparece porque el tema no ha llegado a correr.
		$this->assertStringContainsString( 'evt-ev__pie', $html );
		$this->assertSame( 1, substr_count( $html, '<body' ), 'un solo cuerpo, el nuestro' );
		$this->assertSame( 1, substr_count( $html, '</body>' ) );
	}

	/**
	 * Y lo que el tema hubiera encolado se cae de la cola de verdad.
	 *
	 * `drop_page_tag()` es la red de seguridad para lo que se encole tarde;
	 * la vía normal es esta, que saca el handle de la cola antes de imprimirla.
	 */
	public function test_the_theme_assets_leave_the_queue() {
		$evento = $this->un_evento();
		$this->go_to( (string) get_permalink( $evento ) );

		// phpcs:disable WordPress.WP.EnqueuedResourceParameters.MissingVersion -- assets de prueba, sin versión que anclar.
		wp_enqueue_style( 'tema-style', 'https://example.org/wp-content/themes/un-tema/style.css', array(), null );
		wp_enqueue_script( 'tema-custom', 'https://example.org/wp-content/themes/un-tema/js/custom.js', array(), null, true );
		wp_enqueue_style( 'evt-propia', 'https://example.org/wp-includes/css/dashicons.css', array(), null );
		// phpcs:enable WordPress.WP.EnqueuedResourceParameters.MissingVersion

		EventView::drop_page_assets();

		$this->assertFalse( wp_style_is( 'tema-style', 'enqueued' ), 'la hoja del tema, fuera' );
		$this->assertFalse( wp_script_is( 'tema-custom', 'enqueued' ), 'y su guion también' );
		$this->assertTrue( wp_style_is( 'evt-propia', 'enqueued' ), 'lo del núcleo se queda' );
	}

	// ─── los tokens salen de las metas ─────────────────────────────────────

	/**
	 * Cada token sale de la meta del evento, y sin meta no se escribe ninguno.
	 *
	 * Los valores por defecto viven en `assets/css/evt-evento.css` y en un solo
	 * sitio: un evento que no elige nada no tiene que escribir un `<style>` que
	 * repita lo que la hoja ya dice.
	 */
	public function test_the_tokens_come_from_the_event_metas() {
		$evento = $this->un_evento();

		$this->assertSame( '', EventLayout::tokens( EventView::model( $evento ) ), 'sin metas, ningún token' );

		update_post_meta( $evento, EventMetaKeys::HEADER_BG, '#7b1e3a' );
		update_post_meta( $evento, EventMetaKeys::HEADER_TEXT, '#ffffff' );
		update_post_meta( $evento, EventMetaKeys::IMAGE_SHAPE, EventMetaKeys::SHAPE_CIRCLE );
		$this->forget_models();

		$estilo = EventLayout::tokens( EventView::model( $evento ) );
		$this->assertStringContainsString( '--evt-fondo:#7b1e3a', $estilo );
		$this->assertStringContainsString( '--evt-texto:#ffffff', $estilo );
		$this->assertStringContainsString( '--evt-forma:50%', $estilo );

		// Cambiar la meta cambia el token, que es justo lo que hace usable el
		// panel de apariencia: un valor, no una regla nueva.
		update_post_meta( $evento, EventMetaKeys::HEADER_BG, '#0a3d62' );
		$this->forget_models();

		$estilo = EventLayout::tokens( EventView::model( $evento ) );
		$this->assertStringContainsString( '--evt-fondo:#0a3d62', $estilo );
		$this->assertStringNotContainsString( '#7b1e3a', $estilo );
	}

	/**
	 * Y una sección hereda los del evento: se eligen una vez, no en cada página.
	 */
	public function test_a_section_inherits_the_tokens_of_its_event() {
		$evento = $this->un_evento();
		update_post_meta( $evento, EventMetaKeys::HEADER_BG, '#7b1e3a' );
		$seccion = $this->event_page( $evento, 'programa', array( 'post_title' => 'Programa' ) );
		$this->forget_models();

		$this->assertStringContainsString(
			'--evt-fondo:#7b1e3a',
			EventLayout::tokens( EventView::model( $seccion ) )
		);
	}
}
