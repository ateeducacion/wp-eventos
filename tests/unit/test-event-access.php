<?php
/**
 * Tests for EventAccess: who edits what, scoped by área.
 *
 * @package Evt
 */

use Evt\Access\EventAccess;
use Evt\Taxonomy\EventTaxonomies;

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
		$this->assertStringContainsString( 'No tiene ningún ámbito asignado', EventAccess::why_not_editable( $huerfano, $evento ) );
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
		$this->assertStringContainsString( 'de otro ámbito', EventAccess::why_not_editable( $yo, $evento_otro ) );
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
	 * A new event inherits the creator's assigned scope immediately.
	 */
	public function test_a_brand_new_event_belongs_to_whoever_created_it() {
		$mia    = $this->area( 'Formación del Profesorado' );
		$yo     = $this->organiser( array( $mia ) );
		$ajena  = $this->organiser( array( $this->area( 'Innovación' ) ) );
		$evento = $this->event( $yo );

		$this->assertSame( array( $mia ), EventAccess::post_areas( $evento ) );
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

		$first  = $this->area( 'Primer ámbito' );
		$second = $this->area( 'Segundo ámbito' );
		update_user_meta( $uid, EventAccess::USER_AREA_META, array( $first, (string) $first, $second, 0, 'x' ) );
		$this->assertSame( array(), EventAccess::user_areas( $uid ), 'dos ámbitos históricos no eligen uno por su cuenta' );

		update_user_meta( $uid, EventAccess::USER_AREA_META, $first . ',' . $second );
		$this->assertSame( array(), EventAccess::user_areas( $uid ) );

		update_user_meta( $uid, EventAccess::USER_AREA_META, array( $first ) );
		$this->assertSame( array( $first ), EventAccess::user_areas( $uid ) );

		update_user_meta( $uid, EventAccess::USER_AREA_META, '   ' );
		$this->assertSame( array(), EventAccess::user_areas( $uid ) );
	}

	/**
	 * An editor reaches descendants, never ancestors or a sibling branch.
	 */
	public function test_editor_scope_includes_only_assigned_subtrees() {
		$dg      = $this->area( 'Dirección general' );
		$service = $this->area( 'Servicio A' );
		$area    = $this->area( 'Área A1' );
		$team    = $this->area( 'Equipo A1.1' );
		$sibling = $this->area( 'Área A2' );
		$other   = $this->area( 'Servicio B' );
		wp_update_term( $service, EventTaxonomies::AREA, array( 'parent' => $dg ) );
		wp_update_term( $area, EventTaxonomies::AREA, array( 'parent' => $service ) );
		wp_update_term( $team, EventTaxonomies::AREA, array( 'parent' => $area ) );
		wp_update_term( $sibling, EventTaxonomies::AREA, array( 'parent' => $service ) );
		wp_update_term( $other, EventTaxonomies::AREA, array( 'parent' => $dg ) );

		$editor = (int) self::factory()->user->create( array( 'role' => 'editor' ) );
		update_user_meta( $editor, EventAccess::USER_AREA_META, array( $service ) );
		$this->assertEqualsCanonicalizing( array( $service, $area, $team, $sibling ), EventAccess::scope_areas( $editor ) );
		foreach ( array( $service, $area, $team, $sibling ) as $term ) {
			$this->assertTrue( EventAccess::may_assign_areas( array( $term ), $editor ) );
			$this->assertTrue( user_can( $editor, 'assign_term', $term ) );
		}
		foreach ( array( $dg, $other ) as $term ) {
			$this->assertFalse( EventAccess::may_assign_areas( array( $term ), $editor ) );
			$this->assertFalse( user_can( $editor, 'assign_term', $term ) );
		}
		$new_child = $this->area( 'Nueva área' );
		wp_update_term( $new_child, EventTaxonomies::AREA, array( 'parent' => $service ) );
		$this->assertContains( $new_child, EventAccess::scope_areas( $editor ) );
		wp_update_term( $new_child, EventTaxonomies::AREA, array( 'parent' => $other ) );
		$this->assertNotContains( $new_child, EventAccess::scope_areas( $editor ) );
		foreach ( array( $service, $area, $team, $sibling ) as $term ) {
			$this->assertTrue( EventAccess::can_edit( $editor, $this->event( $this->administrator(), array( $term ) ) ) );
		}
		foreach ( array( $dg, $other ) as $term ) {
			$foreign = $this->event( $this->administrator(), array( $term ) );
			$this->assertFalse( EventAccess::can_edit( $editor, $foreign ) );
			$this->acting_as( $editor );
			$this->assertFalse( current_user_can( 'edit_post', $foreign ) );
		}
		update_user_meta( $editor, EventAccess::USER_AREA_META, array( $area ) );
		$this->assertEqualsCanonicalizing( array( $area, $team ), EventAccess::scope_areas( $editor ) );
		wp_delete_term( $area, EventTaxonomies::AREA );
		$this->assertSame( array(), EventAccess::scope_areas( $editor ) );
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

	/** A scoped editor may withdraw only their own organiser terms. */
	public function test_resolve_shared_area_assignment() {
		$service = $this->area( 'Ámbito 1' );
		$a1      = $this->area( 'Subámbito 1' );
		$a2      = $this->area( 'Subámbito 2' );
		$foreign = $this->area( 'Ámbito 2' );
		wp_update_term( $a1, EventTaxonomies::AREA, array( 'parent' => $service ) );
		wp_update_term( $a2, EventTaxonomies::AREA, array( 'parent' => $service ) );
		$editor = (int) self::factory()->user->create( array( 'role' => 'editor' ) );
		update_user_meta( $editor, EventAccess::USER_AREA_META, array( $service ) );
		$event = $this->event( $this->administrator(), array( $a1, $foreign ) );
		$this->assertEqualsCanonicalizing( array( $a1, $foreign ), EventAccess::resolve_area_assignment( $event, array( $a1 ), $editor ) );
		$this->assertEqualsCanonicalizing( array( $a2, $foreign ), EventAccess::resolve_area_assignment( $event, array( $a2 ), $editor ) );
		$this->assertEqualsCanonicalizing( array( $a1, $a2, $foreign ), EventAccess::resolve_area_assignment( $event, array( $a1, $a2 ), $editor ) );
		$this->assertSame( array( $foreign ), EventAccess::resolve_area_assignment( $event, array(), $editor ) );
		$this->assertWPError( EventAccess::resolve_area_assignment( $event, array( $foreign ), $editor ) );
		$this->assertWPError( EventAccess::resolve_area_assignment( $event, array( 99999999 ), $editor ) );
		$this->assertWPError( EventAccess::resolve_area_assignment( $event, array( $a1, 99999999 ), $editor ) );
		$own = $this->event( $this->administrator(), array( $a1 ) );
		$this->assertWPError( EventAccess::resolve_area_assignment( $own, array(), $editor ) );
		$this->assertEqualsCanonicalizing( array( $a2, $foreign ), EventAccess::resolve_area_assignment( $event, array( $a2, $foreign ), $this->administrator() ) );
	}

	/** A historical profile is classified once and never widens runtime access. */
	public function test_historical_scope_states() {
		$first = $this->area( 'Ámbito 1' );
		$other = $this->area( 'Ámbito 2' );
		$user  = $this->organiser();
		foreach ( array( (string) $first, array( $first ) ) as $raw ) {
			update_user_meta( $user, EventAccess::USER_AREA_META, $raw );
			$this->assertSame( 'resolved', EventAccess::scope_assignment_state( $user )['state'] );
			$this->assertSame( array( $first ), EventAccess::user_areas( $user ) );
		}
		foreach ( array( array( $first, $other ), $first . ',' . $other ) as $raw ) {
			update_user_meta( $user, EventAccess::USER_AREA_META, $raw );
			$this->assertSame( 'ambiguous', EventAccess::scope_assignment_state( $user )['state'] );
			$this->assertSame( array(), EventAccess::user_areas( $user ) );
		}
		foreach ( array( array( $first, 99999999 ), '99999999' ) as $raw ) {
			update_user_meta( $user, EventAccess::USER_AREA_META, $raw );
			$this->assertSame( 'invalid', EventAccess::scope_assignment_state( $user )['state'] );
			$this->assertSame( array(), EventAccess::user_areas( $user ) );
		}
	}

	/** Administrators retain the explicit orphan repair path; editors do not. */
	public function test_admin_can_repair_an_unscoped_event() {
		$admin  = $this->administrator();
		$editor = (int) self::factory()->user->create( array( 'role' => 'editor' ) );
		$area   = $this->area( 'Ámbito 1' );
		update_user_meta( $editor, EventAccess::USER_AREA_META, array( $area ) );
		$event = $this->event( $admin, array( $area ) );
		$this->assertSame( array(), EventAccess::resolve_area_assignment( $event, array(), $admin ) );
		$this->assertWPError( EventAccess::resolve_area_assignment( $event, array(), $editor ) );
	}
}
