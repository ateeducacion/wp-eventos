<?php
/**
 * Tests for the loose Bootstrap 5 snippet and the check that asks for it.
 *
 * @package Evt
 */

use Evt\PublicFront\Assets;
use Evt\PublicFront\Shell;

/**
 * De dónde sale Bootstrap 5, dónde se pone y quién dice que está.
 *
 * El subsitio carga Bootstrap 4.5.2 en todas sus páginas con el handle
 * `bootstrap-css`. Lo que se comprueba aquí es que el aplicativo no confunde
 * una cosa con la otra y que solo se cambia la versión en nuestras pantallas.
 */
class Test_Bootstrap5 extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * URL del Bootstrap 4 que el subsitio carga hoy en todas las páginas.
	 */
	private const BOOTSTRAP4_CSS = 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css';

	/**
	 * Con las páginas del aplicativo ya creadas.
	 */
	public function set_up() {
		parent::set_up();
		$this->pages();
	}

	/**
	 * Las colas de estilos y guiones son globales: se dejan como estaban.
	 */
	public function tear_down() {
		foreach ( array_keys( evt_bootstrap5_versions() ) as $handle ) {
			wp_dequeue_style( $handle );
			wp_deregister_style( $handle );
			wp_dequeue_script( $handle );
			wp_deregister_script( $handle );
		}
		parent::tear_down();
	}

	/**
	 * Encolar el Bootstrap 4 del subsitio, como hace el código heredado.
	 *
	 * @return void
	 */
	private function bootstrap4(): void {
		wp_enqueue_style( 'bootstrap-css', self::BOOTSTRAP4_CSS, array(), '4.5.2' );
		wp_enqueue_script( 'bootstrap-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js', array(), '4.5.2', true );
	}

	/**
	 * Un handle llamado «bootstrap-css» no es Bootstrap 5.
	 *
	 * Es el fallo que solo se veía al desplegar: en local no había ningún
	 * handle así y en producción lo pone el código heredado con la versión 4.
	 */
	public function test_a_handle_named_bootstrap_is_not_bootstrap_5() {
		$this->bootstrap4();

		$this->assertTrue( wp_style_is( 'bootstrap-css', 'enqueued' ), 'el Bootstrap 4 del subsitio está en la cola' );
		$this->assertFalse( Assets::has_bootstrap(), 'sin nuestro snippet no se da Bootstrap 5 por presente' );
		$this->assertContains( 'evt-sin-bootstrap', Assets::body_class( array() ) );
	}

	/**
	 * En una pantalla del aplicativo se retira la 4 y se pone la 5.
	 */
	public function test_on_an_app_page_bootstrap_4_is_swapped_for_bootstrap_5() {
		$this->acting_as( $this->administrator() );
		$this->go_to( (string) get_permalink( get_page_by_path( Shell::SLUGS['events'] ) ) );
		$this->assertTrue( Shell::is_app_page() );
		$this->bootstrap4();

		evt_bootstrap5_assets();

		$vendors = evt_bootstrap5_versions();
		$this->assertSame( $vendors['bootstrap-css']['url'], wp_styles()->registered['bootstrap-css']->src );
		$this->assertSame( '5.3.3', wp_styles()->registered['bootstrap-css']->ver );
		$this->assertSame( $vendors['bootstrap-js']['url'], wp_scripts()->registered['bootstrap-js']->src );
		$this->assertTrue( wp_style_is( 'bootstrap-icons', 'enqueued' ) );

		// Y ahora sí: la verdad la pone quien la carga.
		$this->assertTrue( Assets::has_bootstrap() );
		$this->assertNotContains( 'evt-sin-bootstrap', Assets::body_class( array() ) );
	}

	/**
	 * Las páginas del evento también son nuestras, aunque no lleven shortcode.
	 */
	public function test_the_event_pages_also_get_bootstrap_5() {
		$autor  = $this->administrator();
		$evento = $this->event( $autor, array( $this->area( 'Tecnología Educativa' ) ) );
		$this->go_to( (string) get_permalink( $evento ) );

		$this->assertTrue( is_singular( 'evt_event' ) );
		$this->assertFalse( Shell::is_app_page(), 'la ficha del evento no lleva shortcode del aplicativo' );
		$this->assertTrue( evt_is_bootstrap5_page() );

		evt_bootstrap5_assets();
		$this->assertTrue( wp_style_is( 'bootstrap-css', 'enqueued' ) );
		$this->assertTrue( Assets::has_bootstrap() );
	}

	/**
	 * Al resto del subsitio no se le toca el aspecto.
	 */
	public function test_outside_the_app_the_site_bootstrap_4_is_left_alone() {
		$otra = (int) self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => 'Una página cualquiera del subsitio.',
			)
		);
		$this->go_to( (string) get_permalink( $otra ) );
		$this->bootstrap4();

		evt_bootstrap5_assets();

		$this->assertFalse( evt_is_bootstrap5_page() );
		$this->assertSame( self::BOOTSTRAP4_CSS, wp_styles()->registered['bootstrap-css']->src, 'el Bootstrap 4 del subsitio sigue donde estaba' );
		$this->assertSame( '4.5.2', wp_styles()->registered['bootstrap-css']->ver );
		$this->assertFalse( wp_style_is( 'bootstrap-icons', 'registered' ), 'ni se añade nada nuestro' );
		$this->assertFalse( Assets::has_bootstrap() );
	}

	/**
	 * El filtro apaga el cambio sin desactivar el snippet.
	 */
	public function test_the_filter_switches_it_off_without_disabling_the_snippet() {
		$this->acting_as( $this->administrator() );
		$this->go_to( (string) get_permalink( get_page_by_path( Shell::SLUGS['events'] ) ) );
		$this->bootstrap4();
		add_filter( 'evt_load_bootstrap5', '__return_false' );

		evt_bootstrap5_assets();

		$this->assertSame( '4.5.2', wp_styles()->registered['bootstrap-css']->ver );
		$this->assertFalse( wp_style_is( 'bootstrap-icons', 'registered' ) );
		$this->assertFalse( Assets::has_bootstrap() );
	}

	/**
	 * El SRI se pone solo cuando la URL sigue siendo la del CDN.
	 */
	public function test_integrity_is_added_to_the_cdn_tags_only() {
		$vendors = evt_bootstrap5_versions();
		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- es la etiqueta que recibe el filtro, no una hoja que se cargue aquí.
		$etiqueta = "<link rel='stylesheet' id='bootstrap-css-css' href='" . $vendors['bootstrap-css']['url'] . "?ver=5.3.3' media='all' />";

		$con_sri = evt_bootstrap5_sri( $etiqueta, 'bootstrap-css', $vendors['bootstrap-css']['url'] );
		$this->assertStringContainsString( 'integrity="' . $vendors['bootstrap-css']['sri'] . '"', $con_sri );
		$this->assertStringContainsString( 'crossorigin="anonymous"', $con_sri );

		// El guion se marca en el `src`, no en el `href`.
		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- es la etiqueta que recibe el filtro, no un guion que se cargue aquí.
		$guion = "<script src='" . $vendors['bootstrap-js']['url'] . "?ver=5.3.3' id='bootstrap-js-js'></script>";
		$this->assertStringContainsString(
			'integrity="' . $vendors['bootstrap-js']['sri'] . '" crossorigin="anonymous" src=',
			evt_bootstrap5_sri( $guion, 'bootstrap-js', $vendors['bootstrap-js']['url'] )
		);

		// En desarrollo la URL es local: el hash ni cuadraría ni hace falta.
		$this->assertSame(
			$etiqueta,
			evt_bootstrap5_sri( $etiqueta, 'bootstrap-css', 'https://example.org/wp-content/evt-dev/node_modules/bootstrap/dist/css/bootstrap.min.css' )
		);

		// Y a lo que no es nuestro no se le toca la etiqueta.
		$this->assertSame( $etiqueta, evt_bootstrap5_sri( $etiqueta, 'otra-cosa', 'https://cdn.jsdelivr.net/npm/algo@1.0.0/algo.css' ) );
	}

	/**
	 * La misma versión en los tres sitios: package.json, la URL y el `$ver`.
	 *
	 * Es la pega que la ADR-0015 se apunta como negativa: sin esto, subir una
	 * versión en el PHP y no en `package.json` devuelve las pruebas al CDN sin
	 * que nadie se entere.
	 */
	public function test_the_pinned_versions_match_package_json() {
		$package = dirname( __DIR__, 2 ) . '/package.json';
		$this->assertFileIsReadable( $package );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- fichero del repositorio, no una petición remota.
		$datos = json_decode( (string) file_get_contents( $package ), true );
		$dev   = $datos['devDependencies'];

		$this->assertSame( '5.3.3', $dev['bootstrap'], 'versión exacta, sin ^ ni ~' );
		$this->assertSame( '1.11.3', $dev['bootstrap-icons'] );

		$paquetes = array(
			'bootstrap-css'   => 'bootstrap',
			'bootstrap-js'    => 'bootstrap',
			'bootstrap-icons' => 'bootstrap-icons',
		);
		foreach ( evt_bootstrap5_versions() as $handle => $vendor ) {
			$esperada = 'https://cdn.jsdelivr.net/npm/' . $paquetes[ $handle ] . '@' . $dev[ $paquetes[ $handle ] ] . '/';
			$this->assertStringStartsWith( $esperada, $vendor['url'], $handle );
			$this->assertSame( $dev[ $paquetes[ $handle ] ], $vendor['ver'], $handle );
			$this->assertStringStartsWith( 'sha384-', $vendor['sri'], $handle );
		}
	}
}
