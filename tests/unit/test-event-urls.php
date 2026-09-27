<?php
/**
 * Tests for the URLs events keep from the pages they replace.
 *
 * @package Evt
 */

use Evt\Meta\EventMetaKeys;
use Evt\PostType\EventPostType;
use Evt\PublicFront\EventView;
use Evt\PublicFront\Fonts;

/**
 * Las direcciones de hoy siguen valiendo (ADR-0042).
 */
class Test_Event_Urls extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Con enlaces bonitos, como en el sitio.
	 */
	public function set_up() {
		parent::set_up();
		$this->set_permalink_structure( '/%postname%/' );
	}

	/**
	 * Sin opción, nada cambia.
	 */
	public function tear_down() {
		delete_option( EventPostType::OPTION_ROOT_URLS );
		parent::tear_down();
	}

	/**
	 * Resolve a path the way WordPress would hand it to the filter.
	 *
	 * @param string              $ruta Requested path.
	 * @param array<string,mixed> $vars Parsed vars.
	 * @return array<string,mixed>
	 */
	private function resolver( string $ruta, array $vars ): array {
		$GLOBALS['wp']->request = $ruta;
		return (array) EventPostType::resolve_request( $vars );
	}

	/**
	 * En la raíz: `/<evento>/<sección>/` es el evento, y su enlace es ese.
	 */
	public function test_events_answer_at_the_root_when_asked() {
		$evento = $this->event( $this->administrator(), array( $this->area() ), array(), array( 'post_name' => 'congreso-digital' ) );
		$pagina = $this->event_page( $evento, 'ponentes', array( 'post_name' => 'ponentes-ed' ) );

		$this->assertStringContainsString( '/evento/congreso-digital/', (string) get_permalink( $evento ) );
		$this->assertSame( array( 'pagename' => 'congreso-digital' ), $this->resolver( 'congreso-digital', array( 'pagename' => 'congreso-digital' ) ), 'sin la opción no se toca' );

		update_option( EventPostType::OPTION_ROOT_URLS, '1' );

		$this->assertSame( home_url( '/congreso-digital/ponentes-ed/' ), get_permalink( $pagina ) );
		$vars = $this->resolver( 'congreso-digital/ponentes-ed', array( 'attachment' => 'ponentes-ed' ) );
		$this->assertSame( 'congreso-digital/ponentes-ed', $vars[ EventPostType::POST_TYPE ] );
		$this->assertSame( EventPostType::POST_TYPE, $vars['post_type'] );

		$ficha = $this->resolver( 'congreso-digital/ponentes-ed/entry/4408', array( 'pagename' => 'congreso-digital/ponentes-ed/entry/4408' ) );
		$this->assertSame( 'congreso-digital/ponentes-ed', $ficha[ EventPostType::POST_TYPE ] );
		$this->assertSame( 4408, $ficha[ EventPostType::ENTRY_VAR ] );
	}

	/**
	 * Una página de verdad con la misma ruta gana.
	 */
	public function test_a_real_page_wins() {
		update_option( EventPostType::OPTION_ROOT_URLS, '1' );
		$this->event( $this->administrator(), array( $this->area() ), array(), array( 'post_name' => 'contacto' ) );
		self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_name'   => 'contacto',
				'post_status' => 'publish',
			)
		);

		$this->assertSame( array( 'pagename' => 'contacto' ), $this->resolver( 'contacto', array( 'pagename' => 'contacto' ) ) );
	}

	/**
	 * `?page_id=<N>` de un evento lleva al evento.
	 */
	public function test_the_old_page_id_reaches_the_event() {
		$evento = $this->event( $this->administrator(), array( $this->area() ) );

		$vars = EventPostType::resolve_request( array( 'page_id' => (string) $evento ) );

		$this->assertSame( $evento, $vars['p'] );
		$this->assertSame( EventPostType::POST_TYPE, $vars['post_type'] );
		$this->assertArrayNotHasKey( 'page_id', $vars );
	}

	/**
	 * Bajo `/evento/`, la regla del tipo lee la ficha como paginación.
	 */
	public function test_the_entry_url_also_works_under_the_prefix() {
		$vars = EventPostType::resolve_request(
			array(
				EventPostType::POST_TYPE => 'congreso/ponentes-ed/entry',
				'page'                   => '/4408',
			)
		);

		$this->assertSame( 'congreso/ponentes-ed', $vars[ EventPostType::POST_TYPE ] );
		$this->assertSame( 4408, $vars[ EventPostType::ENTRY_VAR ] );
		$this->assertArrayNotHasKey( 'page', $vars );
	}

	/**
	 * La tipografía del evento se carga de verdad, con su SRI.
	 */
	public function test_the_fonts_of_the_event_are_loaded_with_integrity() {
		Fonts::enqueue( array( 'lato', 'times', '', 'no-existe' ) );

		$this->assertTrue( wp_style_is( 'evt-font-lato-400', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'evt-font-lato-700', 'enqueued' ) );
		$this->assertFalse( wp_style_is( 'evt-font-times-400', 'enqueued' ), 'la del sistema no carga nada' );

		$url = Fonts::url( 'lato', 400 );
		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- es la etiqueta que WordPress le pasa al filtro, no una hoja.
		$tag = Fonts::integrity( "<link rel='stylesheet' id='evt-font-lato-400-css' href='{$url}' media='all' />", 'evt-font-lato-400', $url );
		$this->assertStringContainsString( 'integrity="' . Fonts::FILES['lato'][400] . '"', $tag );
		$this->assertStringContainsString( 'crossorigin="anonymous"', $tag );
		$this->assertStringContainsString( '@fontsource/source-serif-4@', Fonts::url( 'source-serif', 400 ) );

		wp_dequeue_style( 'evt-font-lato-400' );
		wp_dequeue_style( 'evt-font-lato-700' );
	}

	/**
	 * Un cartel en PDF se ofrece para descargar y se ve la destacada.
	 */
	public function test_a_pdf_poster_is_offered_for_download() {
		$evento = $this->event( $this->administrator(), array( $this->area() ) );
		$pdf    = self::factory()->attachment->create(
			array(
				'post_mime_type' => 'application/pdf',
				'guid'           => 'https://example.org/cartel.pdf',
				'post_parent'    => $evento,
			)
		);
		update_post_meta( $pdf, '_wp_attached_file', 'cartel.pdf' );
		update_post_meta( $evento, EventMetaKeys::POSTER_ID, $pdf );

		$m = EventView::model( $evento );

		$this->assertStringContainsString( 'cartel.pdf', (string) $m['appearance']['poster_file'] );
		$this->assertStringContainsString( 'Descargar el cartel (PDF)', \Evt\PublicFront\Block\PosterBlock::html( $m ) );
	}
}
