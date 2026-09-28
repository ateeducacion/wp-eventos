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

	/**
	 * El menú «Eventos» del escritorio solo sale a administración.
	 *
	 * Quien organiza trabaja en el aplicativo; el escritorio es donde se
	 * guardan los datos, no donde se trabaja con ellos.
	 */
	public function test_only_the_administration_keeps_the_events_menu() {
		global $menu;
		$ruta = 'edit.php?post_type=' . EventPostType::POST_TYPE;

		foreach ( array(
			$this->organiser()     => false,
			$this->administrator() => true,
		) as $uid => $lo_ve ) {
			$menu = array( array( 'Eventos', 'edit_evt_events', $ruta ) );
			wp_set_current_user( $uid );
			EventAdmin::hide_desk_menu();
			$this->assertSame( $lo_ve, in_array( $ruta, wp_list_pluck( $menu, 2 ), true ) );
		}
	}

	/**
	 * Ni «+ Nuevo › Evento» en la barra de arriba.
	 */
	public function test_the_toolbar_new_items_are_only_for_the_administration() {
		require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';

		foreach ( array(
			$this->organiser()     => false,
			$this->administrator() => true,
		) as $uid => $lo_ve ) {
			$barra = new WP_Admin_Bar();
			foreach ( EventAdmin::desk_post_types() as $tipo ) {
				$barra->add_node(
					array(
						'id'    => 'new-' . $tipo,
						'title' => $tipo,
					)
				);
			}
			$barra->add_node(
				array(
					'id'    => 'new-post',
					'title' => 'Entrada',
				)
			);
			wp_set_current_user( $uid );
			EventAdmin::hide_desk_new_items( $barra );

			$this->assertSame( $lo_ve, null !== $barra->get_node( 'new-' . EventPostType::POST_TYPE ) );
			$this->assertNotNull( $barra->get_node( 'new-post' ), 'lo que no es de eventos no se toca' );
		}
	}

	/**
	 * Quien organiza y llega a una pantalla de eventos del escritorio va a «Mis eventos».
	 */
	public function test_organisers_are_sent_from_the_desk_screens_to_the_app() {
		$this->pages();

		wp_set_current_user( $this->organiser() );
		foreach ( array( 'edit-' . EventPostType::POST_TYPE, EventPostType::POST_TYPE, 'edit-' . SpeakerPostType::POST_TYPE ) as $id ) {
			$destino = $this->exit_url(
				static function () use ( $id ): void {
					EventAdmin::send_to_the_app( WP_Screen::get( $id ) );
				}
			);
			$this->assertSame( \Evt\PublicFront\Shell::url( 'events' ), $destino, $id );
		}
		$this->assertNull(
			$this->exit_url(
				static function (): void {
					EventAdmin::send_to_the_app( WP_Screen::get( 'upload' ) );
				}
			),
			'las demás pantallas del escritorio no se tocan'
		);

		wp_set_current_user( $this->administrator() );
		$this->assertNull(
			$this->exit_url(
				static function (): void {
					EventAdmin::send_to_the_app( WP_Screen::get( 'edit-' . EventPostType::POST_TYPE ) );
				}
			)
		);
	}

	/**
	 * El arranque engancha lo que esconde el escritorio a quien no administra.
	 *
	 * `register()` corre al arrancar, antes de que se mida nada: se vuelve a
	 * llamar aquí para comprobar que los tres enganches están.
	 */
	public function test_register_hooks_what_hides_the_desk() {
		EventAdmin::register();

		$this->assertSame( 999, has_action( 'admin_menu', array( EventAdmin::class, 'hide_desk_menu' ) ) );
		$this->assertSame( 999, has_action( 'admin_bar_menu', array( EventAdmin::class, 'hide_desk_new_items' ) ) );
		$this->assertSame( 10, has_action( 'current_screen', array( EventAdmin::class, 'send_to_the_app' ) ) );
	}
}
