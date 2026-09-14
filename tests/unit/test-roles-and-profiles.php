<?php
/**
 * Tests for the product roles and the área profile field.
 *
 * @package Evt
 */

/**
 * El snippet suelto de roles y perfiles.
 *
 * Es aditivo a propósito: crea lo que falta y no quita nada, para que lo que se
 * conceda a mano en WPFront siga ahí en la siguiente carga.
 */
class Test_Roles_And_Profiles extends WP_UnitTestCase {

	/**
	 * Que un test no herede la petición del anterior.
	 */
	public function tear_down() {
		$_POST = array();
		parent::tear_down();
	}

	/**
	 * El único rol que crea el aplicativo. El otro perfil es `administrator`,
	 * el nativo de WordPress, que no se crea: solo recibe capacidades.
	 */
	public function test_role_slugs_are_the_one_of_the_contract() {
		$this->assertSame( array( 'evt_organiser' ), evt_role_slugs() );
		$this->assertSame( evt_role_slugs(), array_keys( evt_role_definitions() ) );
	}

	/**
	 * El registro crea los roles con su etiqueta y sus capacidades.
	 */
	public function test_register_roles_creates_the_roles_with_their_capabilities() {
		evt_register_roles();

		$organiser = get_role( 'evt_organiser' );
		$this->assertNotNull( $organiser );
		$this->assertSame( 'Organización de eventos', wp_roles()->role_names['evt_organiser'] );
		$this->assertTrue( $organiser->has_cap( 'read' ) );
		$this->assertTrue( $organiser->has_cap( 'upload_files' ) );
		$this->assertFalse( $organiser->has_cap( 'evt_edit_all_areas' ), 'la organización se queda en su área' );
		$this->assertFalse( $organiser->has_cap( 'evt_manage_app' ), 'administrar el aplicativo no es organizar' );

		// Las dos capacidades propias del aplicativo, para quien lo administra
		// y para nadie más.
		$admin = get_role( 'administrator' );
		$this->assertTrue( $admin->has_cap( 'evt_manage_app' ) );
		$this->assertTrue( $admin->has_cap( 'evt_edit_all_areas' ) );
	}

	/**
	 * Registrar dos veces no añade nada: se llama en cada carga de `init`.
	 */
	public function test_register_roles_is_idempotent() {
		evt_register_roles();
		$capacidades = get_role( 'evt_organiser' )->capabilities;
		$roles       = array_keys( wp_roles()->roles );

		evt_register_roles();
		evt_register_roles();

		$this->assertSame( $capacidades, get_role( 'evt_organiser' )->capabilities );
		$this->assertSame( $roles, array_keys( wp_roles()->roles ), 'no aparece ningún rol nuevo' );
	}

	/**
	 * Y no pisa los roles que no son suyos.
	 */
	public function test_register_roles_does_not_touch_other_roles() {
		$antes = wp_roles()->roles;
		unset( $antes['evt_organiser'], $antes['administrator'] );

		evt_register_roles();

		$despues = wp_roles()->roles;
		unset( $despues['evt_organiser'], $despues['administrator'] );

		$this->assertSame( $antes, $despues );
	}

	/**
	 * Nunca quita una capacidad: quitar se hace en el código, que es donde se ve.
	 */
	public function test_register_roles_never_takes_a_capability_away() {
		evt_register_roles();
		get_role( 'evt_organiser' )->add_cap( 'moderate_comments' );

		evt_register_roles();

		$this->assertTrue( get_role( 'evt_organiser' )->has_cap( 'moderate_comments' ), 'lo concedido a mano sigue ahí' );
		get_role( 'evt_organiser' )->remove_cap( 'moderate_comments' );
	}

	/**
	 * La comprobación dice qué rol o capacidad falta, y calla cuando no falta nada.
	 */
	public function test_status_says_which_capability_is_missing() {
		evt_register_roles();
		$this->assertTrue( evt_role_exists( 'evt_organiser' ) );
		$this->assertFalse( evt_role_exists( 'inventado' ) );

		foreach ( evt_roles_status() as $slug => $estado ) {
			$this->assertTrue( $estado['exists'], $slug );
			$this->assertSame( array(), $estado['missing'], $slug );
		}

		get_role( 'evt_organiser' )->remove_cap( 'upload_files' );
		$this->assertSame( array( 'upload_files' ), evt_roles_status()['evt_organiser']['missing'] );

		evt_register_roles();
		$this->assertSame( array(), evt_roles_status()['evt_organiser']['missing'], 'el registro repone la capacidad' );
	}

	/**
	 * El rol viejo se retira una sola vez, y a quien lo tuviera se le deja
	 * organizando su área en vez de sin ningún rol.
	 */
	public function test_the_legacy_coordinator_role_is_retired_once() {
		delete_option( 'evt_coordinator_role_retired' );
		add_role( 'evt_coordinator', 'Coordinación de eventos', array( 'read' => true ) );
		$quien = (int) self::factory()->user->create( array( 'role' => 'evt_coordinator' ) );

		evt_register_roles();

		$this->assertNull( get_role( 'evt_coordinator' ), 'el rol viejo ya no está' );
		$this->assertSame( array( 'evt_organiser' ), get_userdata( $quien )->roles, 'nadie se queda sin rol' );
		$this->assertFalse( user_can( $quien, 'evt_edit_all_areas' ), 'y pierde el salto entre áreas' );
		$this->assertSame( '1', (string) get_option( 'evt_coordinator_role_retired' ) );
	}

	/**
	 * Y no se vuelve a retirar: la opción de guarda está para que una decisión
	 * posterior de quien administra no se deshaga sola en la siguiente carga.
	 */
	public function test_the_retirement_does_not_run_twice() {
		delete_option( 'evt_coordinator_role_retired' );
		evt_register_roles();

		add_role( 'evt_coordinator', 'Coordinación de eventos', array( 'read' => true ) );
		evt_register_roles();

		$this->assertNotNull( get_role( 'evt_coordinator' ), 'lo que se cree después es cosa de quien administra' );
		remove_role( 'evt_coordinator' );
	}

	/**
	 * El área no se la pone uno mismo: WordPress deja a cualquiera editar su
	 * propio perfil, y el campo que decide qué eventos toca sería la puerta de
	 * al lado.
	 */
	public function test_nobody_widens_their_own_area() {
		evt_register_roles();
		$organiser = (int) self::factory()->user->create( array( 'role' => 'evt_organiser' ) );
		wp_set_current_user( $organiser );

		$this->assertFalse( evt_can_edit_admin_only_fields() );

		$_POST = array(
			'evt_area_present' => '1',
			'evt_area'         => array( 99 ),
		);
		evt_save_profile_fields( $organiser );

		$this->assertSame( '', get_user_meta( $organiser, 'evt_area', true ) );

		$admin = (int) self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
		$this->assertTrue( evt_can_edit_admin_only_fields() );
	}
}
