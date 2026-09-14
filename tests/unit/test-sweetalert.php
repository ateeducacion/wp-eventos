<?php
/**
 * Tests for the loose SweetAlert2 snippet and the confirmation it dresses up.
 *
 * @package Evt
 */

use Evt\Meta\EventMetaKeys;
use Evt\PublicFront\Assets;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\Shell;
use Evt\PublicFront\View\EventSectionsPanel;

/**
 * De dónde sale SweetAlert2, dónde se carga y qué pregunta el formulario.
 *
 * Lo que se puede comprobar en PHP es el encolado —versión, SRI y que solo
 * está donde corre el guion— y el contrato que el guion lee del HTML: el
 * atributo con la pregunta y el verbo del botón que el diálogo va a mostrar.
 * Lo que pasa dentro del diálogo es del navegador.
 */
class Test_Sweetalert extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Con las páginas del aplicativo ya creadas.
	 */
	public function set_up() {
		parent::set_up();
		$this->pages();
	}

	/**
	 * Las colas de guiones son globales: se dejan como estaban.
	 */
	public function tear_down() {
		wp_dequeue_script( 'sweetalert2' );
		wp_deregister_script( 'sweetalert2' );
		wp_dequeue_script( 'evt-app' );
		parent::tear_down();
	}

	/**
	 * Ponerse en una pantalla del aplicativo, con su guion ya encolado.
	 *
	 * @return void
	 */
	private function en_el_aplicativo(): void {
		$this->acting_as( $this->administrator() );
		$this->go_to( (string) get_permalink( get_page_by_path( Shell::SLUGS['events'] ) ) );
		$this->assertTrue( Shell::is_app_page() );
		Assets::enqueue();
	}

	/**
	 * La misma versión en los tres sitios: package.json, la URL y el `$ver`.
	 */
	public function test_the_pinned_version_matches_package_json() {
		$package = dirname( __DIR__, 2 ) . '/package.json';
		$this->assertFileIsReadable( $package );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- fichero del repositorio, no una petición remota.
		$datos    = json_decode( (string) file_get_contents( $package ), true );
		$esperada = $datos['devDependencies']['sweetalert2'];
		$vendor   = evt_sweetalert_vendor();

		$this->assertSame( '11.26.25', $esperada, 'versión exacta, sin ^ ni ~' );
		$this->assertSame( $esperada, $vendor['ver'] );
		$this->assertStringStartsWith( 'https://cdn.jsdelivr.net/npm/sweetalert2@' . $esperada . '/', $vendor['url'] );
		$this->assertStringStartsWith( 'sha384-', $vendor['sri'] );
	}

	/**
	 * Se carga donde corre el guion del aplicativo, y con su SRI.
	 */
	public function test_it_is_enqueued_where_the_app_script_runs() {
		$this->en_el_aplicativo();

		evt_sweetalert_assets();

		$vendor = evt_sweetalert_vendor();
		$this->assertTrue( wp_script_is( 'sweetalert2', 'enqueued' ) );
		$this->assertSame( $vendor['url'], wp_scripts()->registered['sweetalert2']->src );
		$this->assertSame( $vendor['ver'], wp_scripts()->registered['sweetalert2']->ver );
	}

	/**
	 * Y en ninguna otra página del subsitio.
	 */
	public function test_it_is_not_enqueued_outside_the_app() {
		$otra = (int) self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => 'Una página cualquiera del subsitio.',
			)
		);
		$this->go_to( (string) get_permalink( $otra ) );

		evt_sweetalert_assets();

		$this->assertFalse( wp_script_is( 'evt-app', 'enqueued' ) );
		$this->assertFalse( wp_script_is( 'sweetalert2', 'registered' ) );
	}

	/**
	 * El filtro apaga la librería sin desactivar el snippet.
	 *
	 * Y apagarla no quita la confirmación: el guion se va al `confirm()`.
	 */
	public function test_the_filter_switches_it_off_without_disabling_the_snippet() {
		$this->en_el_aplicativo();
		add_filter( 'evt_load_sweetalert', '__return_false' );

		evt_sweetalert_assets();

		$this->assertFalse( wp_script_is( 'sweetalert2', 'registered' ) );
	}

	/**
	 * El SRI se pone solo cuando la URL sigue siendo la del CDN.
	 */
	public function test_integrity_is_added_to_the_cdn_tag_only() {
		$vendor = evt_sweetalert_vendor();
		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- es la etiqueta que recibe el filtro, no un guion que se cargue aquí.
		$etiqueta = "<script src='" . $vendor['url'] . '?ver=' . $vendor['ver'] . "' id='sweetalert2-js'></script>";

		$this->assertStringContainsString(
			'integrity="' . $vendor['sri'] . '" crossorigin="anonymous" src=',
			evt_sweetalert_sri( $etiqueta, 'sweetalert2', $vendor['url'] )
		);

		// En desarrollo la URL es local: el hash ni cuadraría ni hace falta.
		$this->assertSame(
			$etiqueta,
			evt_sweetalert_sri( $etiqueta, 'sweetalert2', 'https://example.org/wp-content/evt-dev/node_modules/sweetalert2/dist/sweetalert2.all.min.js' )
		);

		// Y a lo que no es nuestro no se le toca la etiqueta.
		$this->assertSame( $etiqueta, evt_sweetalert_sri( $etiqueta, 'otra-cosa', 'https://cdn.jsdelivr.net/npm/algo@1.0.0/algo.js' ) );
	}

	/**
	 * El contrato que el guion lee del HTML: la pregunta y el verbo.
	 *
	 * `data-evt-confirm` es lo que el diálogo parte en titular y detalle, y el
	 * `title` del botón es el verbo del botón de confirmar. Si alguno cambia de
	 * nombre, el diálogo se queda en «Sí, continuar» y nadie se entera.
	 */
	public function test_the_delete_form_carries_the_question_and_the_verb() {
		$autor  = $this->administrator();
		$evento = $this->event(
			$autor,
			array( $this->area( 'Innovación' ) ),
			array( EventMetaKeys::START_DATE => '2026-03-10' )
		);
		$this->event_page( $evento, 'programa' );

		$this->acting_as( $autor );
		$_GET[ EventWorkspace::ARG_EVENT ] = (string) $evento;
		$_GET[ EventWorkspace::ARG_PANEL ] = EventWorkspace::PANEL_SECTIONS;

		$modelo = EventWorkspace::model();
		$html   = EventSectionsPanel::html( $modelo );

		$titulo = (string) $modelo['sections'][0]['title'];
		$this->assertStringContainsString(
			'data-evt-confirm="¿Enviar «' . $titulo . '» a la papelera? Dejará de verse en el evento."',
			$html
		);
		$this->assertStringContainsString( 'title="Enviar a la papelera"', $html );
		// Y la pregunta se parte por el primer «? »: titular y detalle.
		$this->assertStringContainsString( 'a la papelera? Dejará', $html );
	}
}
