<?php
/**
 * Tests for the three custom post types and their capabilities.
 *
 * @package Evt
 */

use Evt\PostType\ActivityPostType;
use Evt\PostType\EventPostType;
use Evt\PostType\SpeakerPostType;

/**
 * Los tipos de contenido tal y como los pide el contrato.
 */
class Test_Post_Types extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Tipos, taxonomías y roles registrados.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
	}

	/**
	 * El evento es jerárquico: es a la vez su portada y sus páginas satélite,
	 * que es lo que conserva la forma de las URL de hoy.
	 */
	public function test_the_event_is_hierarchical() {
		$tipo = get_post_type_object( EventPostType::POST_TYPE );

		$this->assertNotNull( $tipo );
		$this->assertTrue( $tipo->hierarchical );
		$this->assertTrue( $tipo->public );
		$this->assertTrue( $tipo->map_meta_cap );
		$this->assertTrue( post_type_supports( EventPostType::POST_TYPE, 'page-attributes' ), 'sin esto no se elige el evento padre' );
		$this->assertTrue( post_type_supports( EventPostType::POST_TYPE, 'author' ) );
		// Se registra como array( singular, plural ), pero WP_Post_Type::set_props()
		// deriva de ahí todas las capacidades y acto seguido se queda solo con el
		// primer elemento. Lo que sobrevive en el objeto es el singular; que el plural
		// se usó de verdad lo demuestra el mapa de capacidades, no esta propiedad.
		$this->assertSame( 'evt_event', $tipo->capability_type );
		$this->assertSame( 'edit_evt_events', $tipo->cap->edit_posts );
		$this->assertSame( 'publish_evt_events', $tipo->cap->publish_posts );
	}

	/**
	 * Y una página satélite es de verdad hija del evento, no otra cosa.
	 */
	public function test_a_satellite_page_hangs_from_its_event() {
		$evento = $this->event( $this->administrator() );
		$pagina = $this->event_page( $evento, 'programa' );

		$this->assertSame( $evento, (int) get_post_field( 'post_parent', $pagina ) );
		$this->assertSame( EventPostType::POST_TYPE, get_post_type( $pagina ) );
	}

	/**
	 * Las capacidades del evento son exactamente las del contrato.
	 */
	public function test_the_event_capabilities_are_the_contract_ones() {
		$cap = get_post_type_object( EventPostType::POST_TYPE )->cap;

		$esperadas = array(
			'edit_post'              => 'edit_evt_event',
			'read_post'              => 'read_evt_event',
			'delete_post'            => 'delete_evt_event',
			'edit_posts'             => 'edit_evt_events',
			'edit_others_posts'      => 'edit_others_evt_events',
			'publish_posts'          => 'publish_evt_events',
			'read_private_posts'     => 'read_private_evt_events',
			'delete_posts'           => 'delete_evt_events',
			'delete_others_posts'    => 'delete_others_evt_events',
			'edit_published_posts'   => 'edit_published_evt_events',
			'delete_published_posts' => 'delete_published_evt_events',
		);
		foreach ( $esperadas as $clave => $valor ) {
			$this->assertSame( $valor, $cap->$clave, $clave );
		}

		// Sin `create_posts` propia: crear un evento es editarlos, que es lo que
		// tiene la organización. Declarar una capacidad que nadie tiene es como
		// se cierra el botón de «Añadir nuevo» sin querer.
		$this->assertSame( 'edit_evt_events', $cap->create_posts );
	}

	/**
	 * Ponentes y actividades existen, con sus propias capacidades y sin URL
	 * pública: se enseñan dentro del evento, no en una ficha suelta.
	 */
	public function test_speakers_and_activities_exist_on_their_own() {
		$tipos = array(
			SpeakerPostType::POST_TYPE  => 'evt_speakers',
			ActivityPostType::POST_TYPE => 'evt_activities',
		);
		foreach ( $tipos as $slug => $plural ) {
			$this->assertTrue( post_type_exists( $slug ), $slug );

			$tipo = get_post_type_object( $slug );
			$this->assertFalse( $tipo->hierarchical, $slug );
			$this->assertFalse( $tipo->public, $slug );
			$this->assertTrue( $tipo->show_ui, $slug );
			$this->assertSame( 'edit.php?post_type=' . EventPostType::POST_TYPE, $tipo->show_in_menu, $slug );
			$this->assertSame( 'edit_' . $plural, $tipo->cap->edit_posts, $slug );
			$this->assertSame( 'publish_' . $plural, $tipo->cap->publish_posts, $slug );
			$this->assertSame( 'edit_others_' . $plural, $tipo->cap->edit_others_posts, $slug );
		}
	}

	/**
	 * Las capacidades no son papel mojado: el reparto se las da al rol de
	 * organización para los tres tipos, y le deja fuera las que no le tocan.
	 */
	public function test_the_organiser_role_really_holds_those_capabilities() {
		$organiser = $this->organiser();

		$tiene = array(
			'edit_evt_events',
			'edit_others_evt_events',
			'publish_evt_events',
			'read_private_evt_events',
			'delete_evt_events',
			'delete_published_evt_events',
			'edit_published_evt_events',
			'edit_evt_speakers',
			'publish_evt_speakers',
			'edit_evt_activities',
			'publish_evt_activities',
		);
		foreach ( $tiene as $cap ) {
			$this->assertTrue( user_can( $organiser, $cap ), $cap );
		}

		$no_tiene = array( 'delete_others_evt_events', 'edit_private_evt_events', 'evt_edit_all_areas', 'evt_manage_app' );
		foreach ( $no_tiene as $cap ) {
			$this->assertFalse( user_can( $organiser, $cap ), $cap );
		}
	}

	/**
	 * El reparto es aditivo: lo concedido a mano en WPFront sigue ahí después.
	 */
	public function test_granting_capabilities_never_takes_one_away() {
		get_role( 'evt_organiser' )->add_cap( 'delete_others_evt_events' );

		EventPostType::grant_caps_to_roles();

		$this->assertTrue( get_role( 'evt_organiser' )->has_cap( 'delete_others_evt_events' ) );
		get_role( 'evt_organiser' )->remove_cap( 'delete_others_evt_events' );
	}
}
