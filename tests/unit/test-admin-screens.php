<?php
/**
 * Tests for the two screens the application adds to the WordPress desktop.
 *
 * @package Evt
 */

use Evt\Access\EventAccess;
use Evt\Admin\EventAdmin;
use Evt\Admin\Settings;
use Evt\Meta\EventMetaKeys;
use Evt\PostType\ActivityPostType;
use Evt\PostType\EventPostType;
use Evt\PostType\SpeakerPostType;

/**
 * El escritorio: las columnas del listado y la pantalla de diagnóstico.
 *
 * Son las dos pantallas que se quedaron sin probar porque «son de wp-admin y
 * ahí ya no vivimos». Pero la de diagnóstico es justo la que se abre cuando
 * algo no está donde debería, y una columna que revienta tumba el listado
 * entero del escritorio. Las dos son código que corre en producción.
 */
class Test_Admin_Screens extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Con el aplicativo arrancado.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
	}

	// ─── Las columnas del listado ──────────────────────────────────────────

	/**
	 * Área y estado entran justo detrás del título, y no al final.
	 *
	 * Al final de la fila nadie las mira: la pregunta que contestan —de quién
	 * es esto y si está vivo— se hace leyendo el título.
	 */
	public function test_the_columns_go_right_after_the_title() {
		$columnas = EventAdmin::columns(
			array(
				'cb'     => '',
				'title'  => 'Título',
				'author' => 'Autor',
				'date'   => 'Fecha',
			)
		);

		$this->assertSame(
			array( 'cb', 'title', 'evt_area', 'evt_state', 'author', 'date' ),
			array_keys( $columnas )
		);
		$this->assertSame( 'Área', $columnas['evt_area'] );
		$this->assertSame( 'Estado', $columnas['evt_state'] );
	}

	/**
	 * La columna de área enseña los términos del evento raíz.
	 */
	public function test_the_area_column_reads_the_root_event() {
		$evento  = $this->event( $this->administrator(), array( $this->area( 'Innovación' ) ) );
		$seccion = $this->event_page( $evento, 'programa' );

		// También en una página satélite: el área es del evento, no de la hija.
		foreach ( array( $evento, $seccion ) as $post_id ) {
			$this->assertSame( 'Innovación', $this->celda( 'evt_area', $post_id ) );
		}
	}

	/**
	 * Sin área, una raya y no un hueco: un hueco parece que falta el dato.
	 */
	public function test_an_event_without_an_area_shows_a_dash() {
		$evento = $this->event( $this->administrator(), array() );
		$this->assertSame( '—', $this->celda( 'evt_area', $evento ) );
	}

	/**
	 * El estado sale derivado de las fechas, y dice si está cerrado.
	 */
	public function test_the_state_column_derives_from_the_dates_and_says_archived() {
		$evento = $this->event(
			$this->administrator(),
			array( $this->area( 'Innovación' ) ),
			array(
				EventMetaKeys::START_DATE => '2020-01-01',
				EventMetaKeys::END_DATE   => '2020-01-02',
			)
		);

		$estado = $this->celda( 'evt_state', $evento );
		$this->assertNotSame( '', $estado );
		$this->assertStringNotContainsString( 'Histórico', $estado );

		update_post_meta( $evento, EventMetaKeys::ARCHIVED, '1' );
		$this->assertStringContainsString( 'Histórico', $this->celda( 'evt_state', $evento ) );
	}

	/**
	 * Una columna que no es nuestra no pinta nada.
	 */
	public function test_a_column_of_somebody_else_prints_nothing() {
		$evento = $this->event( $this->administrator(), array() );
		$this->assertSame( '', $this->celda( 'author', $evento ) );
	}

	// ─── El acotado del listado ────────────────────────────────────────────

	/**
	 * Ponentes y actividades tienen su «Añadir» propio en el submenú.
	 *
	 * El evento no lo necesita: cuelga del menú con `show_in_menu` a `true` y
	 * WordPress ya le pone el alta. Los otros dos cuelgan de la dirección del
	 * padre, y ahí el núcleo registra **solo** el listado.
	 */
	public function test_the_two_nested_post_types_get_their_own_add_new() {
		$tipos = EventAdmin::submenu_post_types();

		$this->assertSame(
			array( SpeakerPostType::POST_TYPE, ActivityPostType::POST_TYPE ),
			$tipos
		);
		$this->assertNotContains( EventPostType::POST_TYPE, $tipos );
	}

	// ─── La pantalla de diagnóstico ────────────────────────────────────────

	/**
	 * Quien administra ve el diagnóstico, con los tipos y las taxonomías.
	 */
	public function test_the_diagnostics_screen_lists_what_is_registered() {
		$this->acting_as( $this->administrator() );
		$this->assertTrue( EventAccess::is_manager() );

		ob_start();
		Settings::render();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'Ajustes y diagnóstico de eventos', $html );
		foreach ( array( 'Eventos y sus páginas', 'Ponentes', 'Actividades' ) as $rotulo ) {
			$this->assertStringContainsString( $rotulo, $html );
		}
		foreach ( array( 'Área organizadora', 'Tipología', 'Curso escolar' ) as $rotulo ) {
			$this->assertStringContainsString( $rotulo, $html );
		}
		// Los tres tipos están registrados de verdad en este entorno, así que
		// ninguna celda puede decir que falta. Se mira la celda y no el texto:
		// el párrafo de arriba explica qué significa «sin registrar» y saldría
		// siempre.
		$this->assertStringContainsString( '<td>Registrado</td>', $html );
		$this->assertStringNotContainsString( '<td>Sin registrar</td>', $html );
	}

	/**
	 * La pantalla está en el menú, y colgando del listado de eventos.
	 */
	public function test_the_diagnostics_screen_hangs_from_the_events_menu() {
		global $submenu;
		$this->acting_as( $this->administrator() );

		$submenu = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- se restaura solo entre tests.
		Settings::menu();

		$padre = 'edit.php?post_type=' . EventPostType::POST_TYPE;
		$this->assertArrayHasKey( $padre, $submenu );

		$slugs = wp_list_pluck( $submenu[ $padre ], 2 );
		$this->assertContains( Settings::PAGE, $slugs );
	}

	/**
	 * Lo que pinta una celda de la columna.
	 *
	 * @param string $columna Column key.
	 * @param int    $post_id Post ID.
	 * @return string
	 */
	private function celda( string $columna, int $post_id ): string {
		ob_start();
		EventAdmin::column_content( $columna, $post_id );
		return trim( (string) ob_get_clean() );
	}
}
