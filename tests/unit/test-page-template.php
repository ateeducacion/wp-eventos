<?php
/**
 * Tests for the theme page template of an event and of its sections.
 *
 * @package Evt
 */

use Evt\PostType\EventPostType;
use Evt\PublicFront\EventView;
use Evt\PublicFront\View\EventChrome;

/**
 * La portada del evento no lleva la cabecera y el pie del tema encima.
 *
 * En el sistema anterior las páginas de evento tienen `_wp_page_template` igual
 * a `page-template-blank.php` porque alguien lo eligió a mano, una a una, en el
 * formulario de alta. Aquí lo pone el aplicativo al crear la página, y cuando el
 * tema no ofrece ninguna plantilla el título no se pinta dos veces.
 */
class Test_Page_Template extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Los tipos y los roles del aplicativo.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
	}

	/**
	 * Fingir un tema que ofrece la «Página en blanco» del tema.
	 *
	 * El tema del entorno de pruebas no trae ninguna, y montar uno de mentira
	 * en disco solo para esto sería más código que el que se prueba: se declara
	 * por el mismo filtro que usa WordPress para leerlas.
	 *
	 * @return void
	 */
	private function theme_with_blank_template(): void {
		add_filter(
			'theme_' . EventPostType::POST_TYPE . '_templates',
			static function () {
				return array(
					'page-template-full.php'  => 'Ancho completo',
					'page-template-blank.php' => 'Página en blanco',
				);
			},
			99
		);
	}

	/**
	 * El evento admite plantilla de página: lo que el tema declara para `page`.
	 */
	public function test_an_event_takes_the_page_templates_of_the_theme() {
		add_filter(
			'theme_page_templates',
			static function () {
				return array( 'page-template-blank.php' => 'Página en blanco' );
			},
			99
		);

		$plantillas = wp_get_theme()->get_page_templates( null, EventPostType::POST_TYPE );

		$this->assertArrayHasKey( 'page-template-blank.php', $plantillas, 'sin esto no hay desplegable de plantilla en el evento' );
		$this->assertArrayHasKey( EventPostType::TEMPLATE_DEFAULT, EventPostType::page_templates(), 'se tiene que poder volver a la del tema' );
	}

	/**
	 * Y al crear el evento se le pone sola, sin que nadie se acuerde.
	 */
	public function test_a_new_event_gets_the_blank_template_of_the_theme() {
		$this->theme_with_blank_template();

		$evento  = $this->event( $this->administrator() );
		$seccion = $this->event_page( $evento, 'programa' );

		$this->assertSame( 'page-template-blank.php', get_page_template_slug( $evento ) );
		$this->assertSame( 'page-template-blank.php', get_page_template_slug( $seccion ), 'la sección también es una página del evento' );
	}

	/**
	 * Si el tema no ofrece ninguna, no se inventa nada.
	 */
	public function test_without_a_blank_template_the_event_keeps_the_default_one() {
		$evento = $this->event( $this->administrator() );

		$this->assertSame( '', EventPostType::blank_template() );
		$this->assertSame( '', get_page_template_slug( $evento ) );
	}

	/**
	 * Quien elija otra plantilla manda: no se le pisa en el siguiente guardado.
	 */
	public function test_a_chosen_template_is_never_overwritten() {
		$this->theme_with_blank_template();
		$evento = $this->event( $this->administrator() );

		update_post_meta( $evento, EventPostType::TEMPLATE_META, 'page-template-full.php' );
		wp_update_post(
			array(
				'ID'         => $evento,
				'post_title' => 'Jornadas con otra plantilla',
			)
		);

		$this->assertSame( 'page-template-full.php', get_page_template_slug( $evento ) );
	}

	/**
	 * Con la plantilla en blanco el título lo pinta la cabecera del evento.
	 */
	public function test_with_a_blank_template_the_header_writes_the_title() {
		$this->theme_with_blank_template();
		$evento = $this->event( $this->administrator(), array(), array(), array( 'post_title' => 'Jornadas de otoño' ) );

		$m = EventView::model( $evento );

		$this->assertTrue( (bool) $m['show_title'] );
		$this->assertStringContainsString( 'Jornadas de otoño', EventChrome::cover( $m ) );
	}

	/**
	 * Y sin ella también: el documento es nuestro y nadie más escribe el título.
	 *
	 * Antes dependía de la plantilla: con la del tema, el tema escribía el
	 * título y repetirlo era el defecto de la captura
	 * `08-vista-publica-portada-evento.png`. Ahora la página se pinta entera en
	 * `template_redirect` y el tema no escribe nada, así que el título es
	 * siempre de la cabecera del evento.
	 */
	public function test_without_a_blank_template_the_header_still_writes_the_title() {
		$evento = $this->event( $this->administrator(), array(), array(), array( 'post_title' => 'Jornadas de otoño' ) );

		$m = EventView::model( $evento );

		$this->assertSame( '', get_page_template_slug( $evento ), 'sin plantilla en blanco que ofrecer' );
		$this->assertTrue( (bool) $m['show_title'] );
		$this->assertStringContainsString( 'Jornadas de otoño', EventChrome::cover( $m ) );
	}

	/**
	 * El apaño de la plantilla en blanco queda inerte, y da igual quién la ponga.
	 *
	 * Se puso para que el tema no repitiera el título. Al montar el documento
	 * entero no hay tema que repita nada, así que la plantilla no decide ya
	 * nada de la vista pública: con ella y sin ella sale lo mismo.
	 */
	public function test_the_blank_template_no_longer_decides_anything() {
		$evento = $this->event( $this->administrator(), array(), array(), array( 'post_title' => 'Jornadas de otoño' ) );

		$sin = EventView::model( $evento )['show_title'];
		update_post_meta( $evento, EventPostType::TEMPLATE_META, 'page-template-blank.php' );
		$con = EventView::model( $evento )['show_title'];

		$this->assertTrue( (bool) $sin );
		$this->assertSame( $sin, $con, 'la plantilla ya no cambia nada' );
	}
}
