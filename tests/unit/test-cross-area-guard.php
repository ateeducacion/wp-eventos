<?php
/**
 * Tests for the cross-area doors that map_meta_cap alone left open.
 *
 * @package Evt
 */

use Evt\PostType\EventPostType;
use Evt\PostType\RegistrationPostType;

/**
 * Lo que un área no puede hacer con lo de otra, por mucho que tenga la capacidad.
 *
 * Dos puertas: la inscripción, que no lleva área propia y la hereda de su
 * evento, y el padre, que `map_meta_cap` no mira porque comprueba el que el
 * contenido tiene y no el que se le pide.
 */
class Test_Cross_Area_Guard extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Tipos, taxonomías y roles registrados.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
	}

	/**
	 * Una inscripción colgada de un evento, como la guarda el aplicativo.
	 *
	 * @param int $evento Event post ID.
	 * @return int Post ID.
	 */
	private function inscripcion( int $evento ): int {
		return (int) self::factory()->post->create(
			array(
				'post_type'   => RegistrationPostType::POST_TYPE,
				'post_status' => 'private',
				'post_parent' => $evento,
			)
		);
	}

	/**
	 * La inscripción de otra área no se edita, ni se borra, ni se lee.
	 */
	public function test_a_registration_is_scoped_by_the_area_of_its_event() {
		$mia    = $this->area( 'Formación del Profesorado' );
		$otra   = $this->area( 'Innovación' );
		$yo     = $this->organiser( array( $mia ) );
		$ella   = $this->organiser( array( $otra ) );
		$suya   = $this->inscripcion( $this->event( $ella, array( $otra ) ) );
		$la_mia = $this->inscripcion( $this->event( $yo, array( $mia ) ) );

		foreach ( array( 'edit_post', 'delete_post', 'read_post' ) as $cap ) {
			$this->assertFalse( user_can( $yo, $cap, $suya ), $cap );
			$this->assertTrue( user_can( $yo, $cap, $la_mia ), $cap );
		}
	}

	/**
	 * Una página no se muda debajo del evento de otra área.
	 */
	public function test_a_page_cannot_be_moved_under_another_area_event() {
		$mia    = $this->area( 'Formación del Profesorado' );
		$otra   = $this->area( 'Innovación' );
		$yo     = $this->organiser( array( $mia ) );
		$ajeno  = $this->event( $this->organiser( array( $otra ) ), array( $otra ) );
		$propio = $this->event( $yo, array( $mia ) );
		$pagina = $this->event_page( $propio );

		$this->acting_as( $yo );
		wp_update_post(
			array(
				'ID'          => $pagina,
				'post_parent' => $ajeno,
			)
		);

		$this->assertSame( $propio, (int) get_post_field( 'post_parent', $pagina ) );
	}

	/**
	 * Ni se crea ya colgada de él.
	 */
	public function test_a_page_cannot_be_planted_under_another_area_event() {
		$otra  = $this->area( 'Innovación' );
		$yo    = $this->organiser( array( $this->area( 'Formación del Profesorado' ) ) );
		$ajeno = $this->event( $this->organiser( array( $otra ) ), array( $otra ) );

		$this->acting_as( $yo );
		$pagina = (int) wp_insert_post(
			array(
				'post_type'   => EventPostType::POST_TYPE,
				'post_status' => 'draft',
				'post_title'  => 'Intrusa',
				'post_parent' => $ajeno,
			)
		);

		$this->assertSame( 0, (int) get_post_field( 'post_parent', $pagina ) );
	}

	/**
	 * Entre dos eventos de la misma área, mover sigue valiendo.
	 */
	public function test_moving_between_events_of_the_same_area_still_works() {
		$mia    = $this->area( 'Formación del Profesorado' );
		$yo     = $this->organiser( array( $mia ) );
		$uno    = $this->event( $yo, array( $mia ) );
		$otro   = $this->event( $yo, array( $mia ) );
		$pagina = $this->event_page( $uno );

		$this->acting_as( $yo );
		wp_update_post(
			array(
				'ID'          => $pagina,
				'post_parent' => $otro,
			)
		);

		$this->assertSame( $otro, (int) get_post_field( 'post_parent', $pagina ) );
	}

	/**
	 * Una inscripción no cambia de evento; y se sigue creando en el de otra
	 * área, que es inscribirse.
	 */
	public function test_a_registration_never_changes_event_but_can_be_created_anywhere() {
		$mia   = $this->area( 'Formación del Profesorado' );
		$otra  = $this->area( 'Innovación' );
		$yo    = $this->organiser( array( $mia ) );
		$ajeno = $this->event( $this->organiser( array( $otra ) ), array( $otra ) );
		$mio   = $this->event( $yo, array( $mia ) );

		$this->acting_as( $yo );
		$inscripcion = (int) wp_insert_post(
			array(
				'post_type'   => RegistrationPostType::POST_TYPE,
				'post_status' => 'private',
				'post_title'  => 'REF',
				'post_parent' => $ajeno,
			)
		);
		$this->assertSame( $ajeno, (int) get_post_field( 'post_parent', $inscripcion ) );

		wp_update_post(
			array(
				'ID'          => $inscripcion,
				'post_parent' => $mio,
			)
		);
		$this->assertSame( $ajeno, (int) get_post_field( 'post_parent', $inscripcion ) );
	}
}
