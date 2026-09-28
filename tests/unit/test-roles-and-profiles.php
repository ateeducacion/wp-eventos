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
		$editor = get_role( 'editor' );
		$this->assertTrue( $editor->has_cap( 'edit_evt_events' ) );
		$this->assertTrue( $editor->has_cap( 'publish_evt_events' ) );
		$this->assertTrue( $editor->has_cap( 'edit_evt_speakers' ) );
		$this->assertTrue( $editor->has_cap( 'edit_evt_activities' ) );
		$this->assertTrue( $editor->has_cap( 'edit_evt_registrations' ) );
		$this->assertTrue( $editor->has_cap( 'evt_edit_custom_css' ) );
		$this->assertFalse( $editor->has_cap( 'evt_edit_custom_js' ) );
		$this->assertFalse( $editor->has_cap( 'evt_manage_app' ) );
		$this->assertFalse( $editor->has_cap( 'evt_edit_all_areas' ) );
		$this->assertFalse( $editor->has_cap( 'manage_options' ) );
	}

	/** Editors cannot see or change their own authorization scope. */
	public function test_editor_scope_field_is_admin_only() {
		$area   = (int) self::factory()->term->create( array( 'taxonomy' => 'evt_area' ) );
		$other  = (int) self::factory()->term->create( array( 'taxonomy' => 'evt_area' ) );
		$editor = (int) self::factory()->user->create( array( 'role' => 'editor' ) );
		update_user_meta( $editor, 'evt_area', array( $area ) );
		wp_set_current_user( $editor );
		ob_start();
		evt_render_profile_fields( get_user_by( 'id', $editor ) );
		$this->assertSame( '', ob_get_clean() );
		$_POST = array(
			'evt_area_present'        => '1',
			'evt_profile_scope_nonce' => wp_create_nonce( 'evt_profile_scope_' . $editor ),
			'evt_area'                => array( $other ),
		);
		evt_save_profile_fields( $editor );
		$this->assertSame( array( $area ), get_user_meta( $editor, 'evt_area', true ) );
		$admin = (int) self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
		ob_start();
		evt_render_profile_fields( get_user_by( 'id', $editor ) );
		$html = ob_get_clean();
		$this->assertStringContainsString( 'name="evt_area"', $html );
		$this->assertStringNotContainsString( 'multiple', $html );
		$_POST = array(
			'evt_area_present'        => '1',
			'evt_profile_scope_nonce' => wp_create_nonce( 'evt_profile_scope_' . $editor ),
			'evt_area'                => array( $area, $other ),
		);
		evt_save_profile_fields( $editor );
		$this->assertSame( array( $area ), get_user_meta( $editor, 'evt_area', true ), 'un POST con varios ámbitos no cambia el perfil' );
		$_POST['evt_area'] = (string) $other;
		evt_save_profile_fields( $editor );
		$this->assertSame( array( $other ), get_user_meta( $editor, 'evt_area', true ) );
	}

	/** Updating unrelated profile fields must preserve unresolved historical values. */
	public function test_historical_profile_values_need_explicit_resolution() {
		$first  = (int) self::factory()->term->create(
			array(
				'taxonomy' => 'evt_area',
				'name'     => 'Ámbito 1',
			)
		);
		$second = (int) self::factory()->term->create(
			array(
				'taxonomy' => 'evt_area',
				'name'     => 'Ámbito 2',
			)
		);
		$editor = (int) self::factory()->user->create( array( 'role' => 'editor' ) );
		$admin  = (int) self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
		$_POST = array(
			'evt_area_present'        => '1',
			'evt_profile_scope_nonce' => wp_create_nonce( 'evt_profile_scope_' . $editor ),
			'evt_area'                => '__keep_unresolved__',
		);
		update_user_meta( $editor, 'evt_area', (string) $first );
		ob_start();
		evt_render_profile_fields( get_user_by( 'id', $editor ) );
		$html = ob_get_clean();
		$this->assertStringContainsString( 'value="' . $first . '" selected=', $html );
		$this->assertStringNotContainsString( '__keep_unresolved__', $html );
		$_POST['evt_area'] = (string) $first;
		evt_save_profile_fields( $editor );
		$this->assertSame( array( $first ), get_user_meta( $editor, 'evt_area', true ) );
		$_POST['evt_area'] = '__keep_unresolved__';
		foreach ( array( array( $first, $second ), $first . ',' . $second, array( $first, 99999999 ) ) as $raw ) {
			update_user_meta( $editor, 'evt_area', $raw );
			ob_start();
			evt_render_profile_fields( get_user_by( 'id', $editor ) );
			$html = ob_get_clean();
			$this->assertStringContainsString( '__keep_unresolved__', $html );
			evt_save_profile_fields( $editor );
			$this->assertSame( $raw, get_user_meta( $editor, 'evt_area', true ) );
		}
		$_POST['evt_area'] = (string) $first;
		evt_save_profile_fields( $editor );
		$this->assertSame( array( $first ), get_user_meta( $editor, 'evt_area', true ) );
		$_POST['evt_area'] = '';
		evt_save_profile_fields( $editor );
		$this->assertSame( array(), get_user_meta( $editor, 'evt_area', true ) );
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
		get_role( 'editor' )->add_cap( 'evt_edit_all_areas' );
		$this->assertContains( 'evt_edit_all_areas', evt_roles_status()['editor']['forbidden'] );
		get_role( 'editor' )->remove_cap( 'evt_edit_all_areas' );

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

	/**
	 * Tom Select: la versión, clavada igual aquí, en las URL y en package.json.
	 */
	public function test_the_scope_select_version_matches_package_json() {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- fichero del repositorio, no una petición remota.
		$datos    = json_decode( (string) file_get_contents( dirname( __DIR__, 2 ) . '/package.json' ), true );
		$esperada = $datos['devDependencies']['tom-select'];
		$vendor   = evt_scope_select_vendor();

		$this->assertSame( '2.6.2', $esperada, 'versión exacta, sin ^ ni ~' );
		$this->assertSame( $esperada, $vendor['ver'] );
		$this->assertStringStartsWith( 'https://cdn.jsdelivr.net/npm/tom-select@' . $esperada . '/', $vendor['js'] );
		$this->assertStringStartsWith( 'https://cdn.jsdelivr.net/npm/tom-select@' . $esperada . '/', $vendor['css'] );
		$this->assertStringStartsWith( 'sha384-', $vendor['js_sri'] );
		$this->assertStringStartsWith( 'sha384-', $vendor['css_sri'] );
	}

	/**
	 * Se carga en las pantallas de perfil y solo para quien puede fijar el ámbito.
	 */
	public function test_the_scope_select_loads_only_on_profiles_for_administration() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		evt_scope_select_assets( 'user-edit.php' );
		$this->assertFalse( wp_script_is( 'tom-select', 'registered' ), 'quien no administra no elige ámbito' );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		evt_scope_select_assets( 'edit.php' );
		$this->assertFalse( wp_script_is( 'tom-select', 'registered' ), 'fuera del perfil no hace falta' );

		evt_scope_select_assets( 'user-edit.php' );
		$this->assertTrue( wp_script_is( 'tom-select', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'tom-select', 'enqueued' ) );
		$this->assertStringContainsString( "getElementById('evt_area')", implode( '', (array) wp_scripts()->get_data( 'tom-select', 'after' ) ) );
	}

	/**
	 * El SRI va en las dos etiquetas del CDN, y en ninguna otra.
	 */
	public function test_the_scope_select_tags_carry_their_integrity() {
		$vendor = evt_scope_select_vendor();
		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- es la etiqueta que recibe el filtro.
		$guion = "<script src='" . $vendor['js'] . "' id='tom-select-js'></script>";
		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- es la etiqueta que recibe el filtro.
		$hoja = "<link rel='stylesheet' id='tom-select-css' href='" . $vendor['css'] . "' />";

		$this->assertStringContainsString( 'integrity="' . $vendor['js_sri'] . '" crossorigin="anonymous" src=', evt_scope_select_sri( $guion, 'tom-select', $vendor['js'] ) );
		$this->assertStringContainsString( 'integrity="' . $vendor['css_sri'] . '" crossorigin="anonymous" href=', evt_scope_select_sri( $hoja, 'tom-select', $vendor['css'] ) );
		$this->assertSame( $guion, evt_scope_select_sri( $guion, 'tom-select', 'https://example.org/wp-content/evt-dev/node_modules/tom-select/dist/js/tom-select.complete.min.js' ) );
		$this->assertSame( $guion, evt_scope_select_sri( $guion, 'otro', $vendor['js'] ) );
	}
}
