<?php
/**
 * Tests for EventInput pure validation and normalisation.
 *
 * @package Evt
 */

use Evt\Domain\EventInput;
use Evt\Meta\EventMetaKeys;

/**
 * El alta y la edición de un evento y de sus páginas satélite.
 */
class Test_Event_Input extends WP_UnitTestCase {

	/**
	 * La raíz de un evento válida se acepta y se recorta.
	 */
	public function test_a_valid_event_root_is_accepted_and_trimmed() {
		$r = EventInput::validate(
			array(
				'title'      => '  Jornadas de ejemplo  ',
				'start_date' => ' 2026-03-10 ',
				'end_date'   => '2026-03-12',
				'venue'      => '  Centro de formación Norte  ',
			)
		);

		$this->assertTrue( $r['ok'] );
		$this->assertSame( array(), $r['errors'] );
		$this->assertSame( 'Jornadas de ejemplo', $r['data']['title'] );
		$this->assertSame( '2026-03-10', $r['data']['start_date'] );
		$this->assertSame( '2026-03-12', $r['data']['end_date'] );
		$this->assertSame( 'Centro de formación Norte', $r['data']['venue'] );
		$this->assertSame( '', $r['data']['section_type'], 'la raíz del evento no es una sección' );
		$this->assertSame( 0, $r['data']['parent'] );
	}

	/**
	 * La raíz necesita título y fecha de comienzo.
	 */
	public function test_the_root_needs_a_title_and_a_start_date() {
		$r = EventInput::validate( array() );

		$this->assertFalse( $r['ok'] );
		$this->assertContains( 'title', $r['errors'] );
		$this->assertContains( 'start_date', $r['errors'] );

		$r = EventInput::validate(
			array(
				'title'      => '   ',
				'start_date' => '2026-03-10',
			)
		);
		$this->assertFalse( $r['ok'] );
		$this->assertContains( 'title', $r['errors'] );
	}

	/**
	 * Lo que no es un día del calendario no es una fecha, en ninguno de los dos campos.
	 */
	public function test_it_rejects_anything_that_is_not_a_calendar_day() {
		$malas = array( '2026-02-30', '2026-13-01', '2026-00-10', '10-03-2026', '2026-3-10', '20260310', '2026-03-10T09:00', 'mañana' );
		foreach ( $malas as $mala ) {
			$r = EventInput::validate(
				array(
					'title'      => 'Jornadas',
					'start_date' => $mala,
				)
			);
			$this->assertFalse( $r['ok'], $mala );
			$this->assertContains( 'start_date', $r['errors'], $mala );
			$this->assertSame( '', $r['data']['start_date'], $mala );

			$r = EventInput::validate(
				array(
					'title'      => 'Jornadas',
					'start_date' => '2026-03-10',
					'end_date'   => $mala,
				)
			);
			$this->assertFalse( $r['ok'], $mala );
			$this->assertContains( 'end_date', $r['errors'], $mala );
			$this->assertSame( '', $r['data']['end_date'], $mala );
		}
	}

	/**
	 * El fin no puede ir antes del principio; el mismo día sí, y sin fin también.
	 */
	public function test_the_end_date_cannot_come_before_the_start() {
		$r = EventInput::validate(
			array(
				'title'      => 'Jornadas',
				'start_date' => '2026-03-12',
				'end_date'   => '2026-03-10',
			)
		);
		$this->assertFalse( $r['ok'] );
		$this->assertContains( 'date_order', $r['errors'] );

		$r = EventInput::validate(
			array(
				'title'      => 'Jornadas',
				'start_date' => '2026-03-10',
				'end_date'   => '2026-03-10',
			)
		);
		$this->assertTrue( $r['ok'], 'un evento de una sola jornada' );

		$r = EventInput::validate(
			array(
				'title'      => 'Jornadas',
				'start_date' => '2026-03-10',
				'end_date'   => '',
			)
		);
		$this->assertTrue( $r['ok'] );
		$this->assertSame( '', $r['data']['end_date'] );
	}

	/**
	 * Una página satélite hereda las fechas del evento pero declara su sección.
	 */
	public function test_a_satellite_page_needs_its_section_and_no_dates() {
		$r = EventInput::validate(
			array(
				'title'        => 'Programa',
				'parent'       => 44,
				'section_type' => 'programa',
			)
		);
		$this->assertTrue( $r['ok'] );
		$this->assertSame( 'programa', $r['data']['section_type'] );
		$this->assertSame( 44, $r['data']['parent'] );

		$r = EventInput::validate(
			array(
				'title'  => 'Programa',
				'parent' => 44,
			)
		);
		$this->assertFalse( $r['ok'] );
		$this->assertContains( 'section_type', $r['errors'] );
		$this->assertNotContains( 'start_date', $r['errors'], 'la página hereda las fechas del evento' );
	}

	/**
	 * El vocabulario de secciones está cerrado: es el del campo «Tipo de página»
	 * del sistema anterior, ni uno más.
	 */
	public function test_the_section_vocabulary_is_closed() {
		foreach ( array_keys( EventMetaKeys::section_types() ) as $seccion ) {
			$r = EventInput::validate(
				array(
					'title'        => 'Página del evento',
					'parent'       => 7,
					'section_type' => $seccion,
				)
			);
			$this->assertTrue( $r['ok'], $seccion );
			$this->assertSame( $seccion, $r['data']['section_type'], $seccion );
		}

		$r = EventInput::validate(
			array(
				'title'        => 'Talleres',
				'parent'       => 7,
				'section_type' => 'talleres',
			)
		);
		$this->assertFalse( $r['ok'] );
		$this->assertContains( 'section_type', $r['errors'] );
	}

	/**
	 * Un padre que no es un identificador es la raíz, y la raíz no lleva sección.
	 */
	public function test_a_negative_parent_is_the_root_and_drops_the_section() {
		$r = EventInput::validate(
			array(
				'title'        => 'Jornadas',
				'start_date'   => '2026-03-10',
				'parent'       => -3,
				'section_type' => 'programa',
			)
		);

		$this->assertTrue( $r['ok'] );
		$this->assertSame( 0, $r['data']['parent'] );
		$this->assertSame( '', $r['data']['section_type'] );
	}

	/**
	 * Y la raíz con una sección inventada tampoco pasa.
	 */
	public function test_the_root_rejects_an_invented_section() {
		$r = EventInput::validate(
			array(
				'title'        => 'Jornadas',
				'start_date'   => '2026-03-10',
				'section_type' => 'talleres',
			)
		);

		$this->assertFalse( $r['ok'] );
		$this->assertContains( 'section_type', $r['errors'] );
	}

	/**
	 * Lo que llega de un formulario no siempre es una cadena.
	 */
	public function test_it_survives_values_that_are_not_strings() {
		$r = EventInput::validate(
			array(
				'title'        => 0,
				'start_date'   => null,
				'venue'        => 12,
				'parent'       => '5',
				'section_type' => 'contacto',
			)
		);

		$this->assertTrue( $r['ok'] );
		$this->assertSame( '0', $r['data']['title'] );
		$this->assertSame( '12', $r['data']['venue'] );
		$this->assertSame( 5, $r['data']['parent'] );
		$this->assertSame( '', $r['data']['start_date'] );
	}
}
