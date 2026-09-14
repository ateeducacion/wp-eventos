<?php
/**
 * Tests for EventAccess: who edits what, scoped by área.
 *
 * @package Evt
 */

use Evt\Access\EventAccess;

/**
 * El acotado por área, que es el eje de permisos del aplicativo.
 *
 * Se prueban las dos capas a la vez: la respuesta de `EventAccess::can_edit()`
 * y la que de verdad cierra la puerta, `user_can( …, 'edit_post', … )`, porque
 * lo que protege la edición rápida y la REST es la segunda.
 */
class Test_Event_Access extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Tipos, taxonomías y roles registrados.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
	}

	/**
	 * Sin área en el perfil no se edita nada: falla en cerrado.
	 */
	public function test_without_an_area_nobody_edits() {
		$area     = $this->area( 'Formación del Profesorado' );
		$evento   = $this->event( $this->administrator(), array( $area ) );
		$huerfano = $this->organiser();

		$this->assertSame( array(), EventAccess::user_areas( $huerfano ) );
		$this->assertFalse( EventAccess::can_edit( $huerfano, $evento ) );
		$this->assertFalse( user_can( $huerfano, 'edit_post', $evento ) );
		$this->assertStringContainsString( 'No tiene ningún área asignada', EventAccess::why_not_editable( $huerfano, $evento ) );
	}

	/**
	 * Con su área edita lo suyo, aunque lo escribiera otra persona, y no lo ajeno.
	 */
	public function test_the_area_scopes_what_is_editable() {
		$mia  = $this->area( 'Formación del Profesorado' );
		$otra = $this->area( 'Innovación' );
		$yo   = $this->organiser( array( $mia ) );
		$ella = $this->organiser( array( $otra ) );

		// Autor ajeno a propósito: lo que decide es el área, no la autoría.
		$evento_mio  = $this->event( $ella, array( $mia ) );
		$evento_otro = $this->event( $ella, array( $otra ) );

		$this->assertTrue( EventAccess::can_edit( $yo, $evento_mio ) );
		$this->assertTrue( user_can( $yo, 'edit_post', $evento_mio ) );
		$this->assertSame( '', EventAccess::why_not_editable( $yo, $evento_mio ) );

		$this->assertFalse( EventAccess::can_edit( $yo, $evento_otro ) );
		$this->assertFalse( user_can( $yo, 'edit_post', $evento_otro ) );
		$this->assertFalse( user_can( $yo, 'delete_post', $evento_otro ) );
		$this->assertStringContainsString( 'de otra área', EventAccess::why_not_editable( $yo, $evento_otro ) );
	}

	/**
	 * `evt_edit_all_areas` se salta el acotado.
	 */
	public function test_editing_every_area_skips_the_scoping() {
		$otra   = $this->area( 'Innovación' );
		$evento = $this->event( $this->organiser( array( $otra ) ), array( $otra ) );
		$admin  = $this->administrator();

		$this->assertSame( array(), EventAccess::user_areas( $admin ), 'la administración no necesita área propia' );
		$this->assertTrue( EventAccess::can_edit_all_areas( $admin ) );
		$this->assertTrue( EventAccess::can_edit( $admin, $evento ) );
		$this->assertTrue( user_can( $admin, 'edit_post', $evento ) );
		$this->assertSame( '', EventAccess::why_not_editable( $admin, $evento ) );
	}

	/**
	 * Tener el área no basta sin la capacidad, y no hay usuario ni evento cero.
	 */
	public function test_it_fails_closed() {
		$area       = $this->area( 'Formación del Profesorado' );
		$evento     = $this->event( $this->administrator(), array( $area ) );
		$suscriptor = (int) self::factory()->user->create( array( 'role' => 'subscriber' ) );
		update_user_meta( $suscriptor, EventAccess::USER_AREA_META, array( $area ) );

		$this->assertFalse( EventAccess::can_edit( $suscriptor, $evento ) );
		$this->assertFalse( EventAccess::can_publish( $suscriptor, $evento ) );
		$this->assertFalse( user_can( $suscriptor, 'edit_post', $evento ) );
		$this->assertStringContainsString( 'Su perfil no organiza eventos', EventAccess::why_not_editable( $suscriptor, $evento ) );

		$this->assertFalse( EventAccess::can_edit( 0, $evento ) );
		$this->assertFalse( EventAccess::can_edit( $suscriptor, 0 ) );
	}

	/**
	 * Una página satélite se rige por el área de su evento, no por la suya ni
	 * por quién la escribió: así no se queda huérfana de permisos al moverla.
	 */
	public function test_a_satellite_page_inherits_the_area_of_its_event() {
		$mia    = $this->area( 'Formación del Profesorado' );
		$otra   = $this->area( 'Innovación' );
		$yo     = $this->organiser( array( $mia ) );
		$ella   = $this->organiser( array( $otra ) );
		$evento = $this->event( $ella, array( $mia ) );
		$pagina = $this->event_page( $evento, 'programa' );

		$this->assertSame( $evento, EventAccess::root_id( $pagina ) );
		$this->assertSame( array( $mia ), EventAccess::post_areas( $pagina ) );
		$this->assertTrue( EventAccess::can_edit( $yo, $pagina ) );
		$this->assertFalse( EventAccess::can_edit( $ella, $pagina ), 'ni siquiera quien lo creó, si el área ya está puesta' );
	}

	/**
	 * Un evento recién creado todavía no tiene área: lo edita quien lo creó,
	 * que es justo quien tiene que ponérsela.
	 */
	public function test_a_brand_new_event_belongs_to_whoever_created_it() {
		$mia    = $this->area( 'Formación del Profesorado' );
		$yo     = $this->organiser( array( $mia ) );
		$ajena  = $this->organiser( array( $this->area( 'Innovación' ) ) );
		$evento = $this->event( $yo );

		$this->assertSame( array(), EventAccess::post_areas( $evento ) );
		$this->assertTrue( EventAccess::can_edit( $yo, $evento ) );
		$this->assertFalse( EventAccess::can_edit( $ajena, $evento ) );
	}

	/**
	 * Publicar pide la capacidad y, si se dice cuál, también el área.
	 */
	public function test_publishing_needs_the_capability_and_the_area() {
		$mia         = $this->area( 'Formación del Profesorado' );
		$otra        = $this->area( 'Innovación' );
		$yo          = $this->organiser( array( $mia ) );
		$evento_mio  = $this->event( $yo, array( $mia ) );
		$evento_otro = $this->event( $yo, array( $otra ) );

		$this->assertTrue( EventAccess::can_publish( $yo ) );
		$this->assertTrue( EventAccess::can_publish( $yo, $evento_mio ) );
		$this->assertFalse( EventAccess::can_publish( $yo, $evento_otro ) );
		$this->assertFalse( EventAccess::can_publish( 0 ) );
	}

	/**
	 * El filtro no toca lo que no es un evento: ahí manda el permiso de siempre.
	 */
	public function test_only_events_are_scoped() {
		$pagina = (int) self::factory()->post->create( array( 'post_type' => 'page' ) );
		$coord  = $this->administrator();

		$this->assertFalse( EventAccess::can_edit( $coord, $pagina ), 'esto no es un evento' );
		$this->assertSame(
			array( 'edit_pages' ),
			EventAccess::map_meta_cap( array( 'edit_pages' ), 'edit_post', $coord, array( $pagina ) )
		);
		$this->assertSame(
			array( 'manage_options' ),
			EventAccess::map_meta_cap( array( 'manage_options' ), 'manage_options', $coord, array() )
		);
	}

	/**
	 * El área del perfil se lee tanto de una lista como de una cadena separada
	 * por comas, y lo que no es un identificador se cae.
	 */
	public function test_the_profile_area_is_read_from_a_list_or_a_string() {
		$uid = $this->organiser();
		$this->assertSame( array(), EventAccess::user_areas( $uid ) );

		update_user_meta( $uid, EventAccess::USER_AREA_META, array( 7, '7', '9', 0, 'x' ) );
		$this->assertSame( array( 7, 9 ), EventAccess::user_areas( $uid ) );

		update_user_meta( $uid, EventAccess::USER_AREA_META, '4,5' );
		$this->assertSame( array( 4, 5 ), EventAccess::user_areas( $uid ) );

		update_user_meta( $uid, EventAccess::USER_AREA_META, '   ' );
		$this->assertSame( array(), EventAccess::user_areas( $uid ) );
	}

	/**
	 * Quien administra el aplicativo pasa por encima de todo.
	 */
	public function test_the_manager_sees_every_area() {
		$otra   = $this->area( 'Innovación' );
		$evento = $this->event( $this->organiser( array( $otra ) ), array( $otra ) );
		$admin  = (int) self::factory()->user->create( array( 'role' => 'administrator' ) );

		$this->assertTrue( EventAccess::is_manager( $admin ) );
		$this->assertTrue( EventAccess::can_edit( $admin, $evento ) );
		$this->assertFalse( EventAccess::is_manager( $this->organiser( array( $otra ) ) ) );
	}
}
