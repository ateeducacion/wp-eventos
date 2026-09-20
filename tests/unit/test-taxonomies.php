<?php
/**
 * Tests for the three event taxonomies.
 *
 * @package Evt
 */

use Evt\Access\EventAccess;
use Evt\PostType\ActivityPostType;
use Evt\PostType\EventPostType;
use Evt\PostType\SpeakerPostType;
use Evt\Taxonomy\EventTaxonomies;

/**
 * Las tres taxonomías que sustituyen a la única `convocatoria` de hoy.
 */
class Test_Taxonomies extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Tipos, taxonomías y roles registrados.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
	}

	/**
	 * Las tres están montadas sobre el evento. El área, además, sobre los
	 * ponentes y las actividades: es el eje de permisos de los tres tipos, que
	 * es lo que permite que un área gestione su evento entero. La tipología y
	 * el curso escolar describen al evento y solo a él.
	 */
	public function test_the_three_taxonomies_sit_on_the_event() {
		$sobre = array(
			EventTaxonomies::AREA   => array( EventPostType::POST_TYPE, SpeakerPostType::POST_TYPE, ActivityPostType::POST_TYPE ),
			EventTaxonomies::TYPE   => array( EventPostType::POST_TYPE ),
			EventTaxonomies::COURSE => array( EventPostType::POST_TYPE ),
		);
		foreach ( $sobre as $slug => $tipos ) {
			$this->assertTrue( taxonomy_exists( $slug ), $slug );

			$tax = get_taxonomy( $slug );
			$this->assertSame( $tipos, $tax->object_type, $slug );
			// Jerárquicas para que salgan como casillas: un vocabulario cerrado
			// no se amplía por una errata al teclear.
			$this->assertTrue( $tax->hierarchical, $slug );
			$this->assertTrue( $tax->show_in_rest, $slug );
		}
	}

	/**
	 * El estado no es una taxonomía: se calcula de las fechas.
	 */
	public function test_the_state_is_not_a_taxonomy() {
		$this->assertFalse( taxonomy_exists( 'evt_state' ) );
		$this->assertFalse( taxonomy_exists( 'convocatoria' ) );
	}

	/**
	 * Las capacidades de los términos son las del contrato y, sobre todo,
	 * existen de verdad en algún rol.
	 *
	 * Es justo lo que le falta al código heredado: exige
	 * `edit_guides`/`publish_guides`, que no las tiene nadie en el sitio, y por
	 * eso nadie puede administrar los términos.
	 */
	public function test_the_term_capabilities_exist_for_real() {
		$tax = get_taxonomy( EventTaxonomies::AREA );

		$this->assertSame( EventAccess::CAP_MANAGE, $tax->cap->manage_terms );
		$this->assertSame( EventAccess::CAP_MANAGE, $tax->cap->edit_terms );
		$this->assertSame( EventAccess::CAP_MANAGE, $tax->cap->delete_terms );
		$this->assertSame( 'edit_evt_events', $tax->cap->assign_terms );

		$admin = (int) self::factory()->user->create( array( 'role' => 'administrator' ) );
		foreach ( (array) $tax->cap as $clave => $cap ) {
			$this->assertTrue( user_can( $admin, $cap ), $clave . ' → ' . $cap );
		}

		// La organización marca el área de su evento, pero no inventa áreas.
		$organiser = $this->organiser();
		$this->assertTrue( user_can( $organiser, $tax->cap->assign_terms ) );
		$this->assertFalse( user_can( $organiser, $tax->cap->manage_terms ) );
	}

	/**
	 * Y un término se le puede poner de verdad a un evento.
	 */
	public function test_an_area_term_lands_on_the_event() {
		$area   = $this->area( 'Ordenación e Innovación Educativa' );
		$evento = $this->event( $this->administrator(), array( $area ) );

		$terminos = get_the_terms( $evento, EventTaxonomies::AREA );
		$this->assertIsArray( $terminos );
		$this->assertSame( array( $area ), array_map( 'intval', wp_list_pluck( $terminos, 'term_id' ) ) );
	}

	/**
	 * Only administration can write validated contact details on a scope.
	 */
	public function test_scope_email_and_image_metadata() {
		$area  = $this->area( 'Ámbito con contacto' );
		$admin = $this->administrator();
		$image = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		$this->assertTrue( wp_attachment_is_image( $image ) );
		$this->acting_as( $admin );
		$_POST = array(
			'evt_scope_meta_nonce'    => wp_create_nonce( 'evt_scope_meta' ),
			EventTaxonomies::EMAIL    => ' Contacto@Example.org ',
			EventTaxonomies::IMAGE_ID => (string) $image,
		);
		EventTaxonomies::save_fields( $area );
		$this->assertSame( 'Contacto@Example.org', get_term_meta( $area, EventTaxonomies::EMAIL, true ) );
		$this->assertSame( $image, (int) get_term_meta( $area, EventTaxonomies::IMAGE_ID, true ) );
		$_POST[ EventTaxonomies::EMAIL ]    = '@@@';
		$_POST[ EventTaxonomies::IMAGE_ID ] = 'not-an-image';
		EventTaxonomies::save_fields( $area );
		$this->assertSame( 'Contacto@Example.org', get_term_meta( $area, EventTaxonomies::EMAIL, true ) );
		$this->assertSame( $image, (int) get_term_meta( $area, EventTaxonomies::IMAGE_ID, true ) );

		$this->acting_as( $this->organiser() );
		$_POST[ EventTaxonomies::EMAIL ] = 'attacker@example.org';
		EventTaxonomies::save_fields( $area );
		$this->assertSame( 'Contacto@Example.org', get_term_meta( $area, EventTaxonomies::EMAIL, true ) );

		$this->acting_as( $admin );
		$_POST[ EventTaxonomies::EMAIL ]    = '';
		$_POST[ EventTaxonomies::IMAGE_ID ] = '0';
		EventTaxonomies::save_fields( $area );
		$this->assertFalse( metadata_exists( 'term', $area, EventTaxonomies::EMAIL ) );
		$this->assertFalse( metadata_exists( 'term', $area, EventTaxonomies::IMAGE_ID ) );
		$_POST = array();
	}

	/** Native selectors expose only effective descendants with unambiguous paths. */
	public function test_scope_options_follow_the_tree() {
		$root    = $this->area( 'Ámbito general' );
		$service = $this->area( 'Ámbito 1' );
		$child   = $this->area( 'Subámbito' );
		$other   = $this->area( 'Ámbito 2' );
		wp_update_term( $service, EventTaxonomies::AREA, array( 'parent' => $root ) );
		wp_update_term( $child, EventTaxonomies::AREA, array( 'parent' => $service ) );
		wp_update_term( $other, EventTaxonomies::AREA, array( 'parent' => $root ) );
		$editor = (int) self::factory()->user->create( array( 'role' => 'editor' ) );
		update_user_meta( $editor, EventAccess::USER_AREA_META, array( $service ) );
		$this->assertEqualsCanonicalizing( array( $service, $child ), array_keys( EventTaxonomies::area_options( $editor ) ) );
		$this->assertSame( 'Ámbito general › Ámbito 1 › Subámbito', EventTaxonomies::area_options( $editor )[ $child ] );
		$this->assertArrayNotHasKey( $other, EventTaxonomies::area_options( $editor ) );
		$this->acting_as( $editor );
		$this->assertEqualsCanonicalizing( array( $service, $child ), EventTaxonomies::rest_area_query( array() )['include'] );
	}
}
