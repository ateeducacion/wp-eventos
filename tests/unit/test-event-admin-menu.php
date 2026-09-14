<?php
/**
 * Tests for the «Añadir …» submenus of speakers and activities.
 *
 * @package Evt
 */

use Evt\Admin\EventAdmin;
use Evt\PostType\ActivityPostType;
use Evt\PostType\EventPostType;
use Evt\PostType\SpeakerPostType;

/**
 * Que un área pueda dar de alta un ponente o una actividad.
 *
 * Ponentes y actividades cuelgan del menú de Eventos, y para esos WordPress
 * registra en el submenú solo el listado. `post-new.php` exige después que la
 * pantalla esté en el menú de quien la pide, así que sin esta entrada el alta
 * se cerraba para todo el que no fuera administración —con las capacidades
 * concedidas y todo—. Es el §1 del encargo: un área gestiona su evento entero.
 */
class Test_Event_Admin_Menu extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Tipos, taxonomías y roles registrados.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
	}

	/**
	 * Los dos tipos que cuelgan del menú de Eventos, y solo esos.
	 */
	public function test_only_the_two_hanging_post_types_get_an_entry() {
		$this->assertSame(
			array( SpeakerPostType::POST_TYPE, ActivityPostType::POST_TYPE ),
			EventAdmin::submenu_post_types()
		);
		foreach ( EventAdmin::submenu_post_types() as $tipo ) {
			$this->assertSame(
				'edit.php?post_type=' . EventPostType::POST_TYPE,
				get_post_type_object( $tipo )->show_in_menu,
				'Si dejara de colgar del menú de Eventos, WordPress ya pondría su propia entrada de alta.'
			);
		}
	}

	/**
	 * La entrada se registra bajo el menú de Eventos, con la etiqueta del tipo
	 * y con la misma capacidad que exige `post-new.php` al entrar.
	 */
	public function test_the_add_new_entry_is_registered_under_the_events_menu() {
		global $submenu;
		$submenu = array();
		// `add_submenu_page()` no apunta nada si quien mira no tiene la
		// capacidad: se prueba con quien organiza, que es para quien esto se
		// arregló.
		wp_set_current_user( $this->organiser() );
		EventAdmin::add_new_submenus();

		$padre = 'edit.php?post_type=' . EventPostType::POST_TYPE;
		$this->assertArrayHasKey( $padre, $submenu );

		$rutas = wp_list_pluck( $submenu[ $padre ], 2 );
		foreach ( EventAdmin::submenu_post_types() as $tipo ) {
			$ruta  = 'post-new.php?post_type=' . $tipo;
			$donde = array_search( $ruta, $rutas, true );
			$this->assertNotFalse( $donde, 'Sin esta entrada, «Añadir» responde «no tienes permisos».' );

			$fila   = $submenu[ $padre ][ $donde ];
			$objeto = get_post_type_object( $tipo );
			$this->assertSame( (string) $objeto->labels->add_new_item, $fila[0] );
			$this->assertSame( (string) $objeto->cap->create_posts, $fila[1] );
		}
	}

	/**
	 * Quien organiza tiene esa capacidad; es la que decide si ve la entrada.
	 */
	public function test_an_organiser_holds_the_capability_the_entry_asks_for() {
		wp_set_current_user( $this->organiser() );
		foreach ( EventAdmin::submenu_post_types() as $tipo ) {
			$this->assertTrue(
				current_user_can( (string) get_post_type_object( $tipo )->cap->create_posts ),
				'Un área da de alta sus ponentes y sus actividades.'
			);
		}
	}
}
