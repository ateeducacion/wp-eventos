<?php
/**
 * Tests for the rules of choosing workshops.
 *
 * @package Evt
 */

use Evt\Domain\WorkshopChoice;

/**
 * Las reglas de elegir taller, sin WordPress (ADR-0050).
 */
class Test_Workshop_Choice extends WP_UnitTestCase {

	/**
	 * Un taller que se repite: dos sesiones con el mismo título.
	 *
	 * @return array<int, array<string, string>>
	 */
	private function talleres(): array {
		return array(
			10 => array(
				'title' => 'Primeros auxilios',
				'date'  => '2027-02-06',
				'start' => '15:00',
				'end'   => '16:15',
			),
			11 => array(
				'title' => 'primeros  Auxilios',
				'date'  => '2027-02-06',
				'start' => '16:30',
				'end'   => '17:45',
			),
			20 => array(
				'title' => 'Juego heurístico',
				'date'  => '2027-02-06',
				'start' => '15:00',
				'end'   => '16:15',
			),
			21 => array(
				'title' => 'Juego heurístico',
				'date'  => '2027-02-13',
				'start' => '16:30',
				'end'   => '17:45',
			),
			30 => array(
				'title' => 'Música',
				'date'  => '2027-02-06',
				'start' => '16:30',
				'end'   => '17:45',
			),
		);
	}

	/**
	 * Lo guardado antes —un número— y lo de ahora —una lista— se leen igual.
	 */
	public function test_ids_read_the_old_and_the_new_shape() {
		$this->assertSame( array( 12 ), WorkshopChoice::ids( 12 ) );
		$this->assertSame( array( 12 ), WorkshopChoice::ids( '12' ) );
		$this->assertSame( array( 12, 34 ), WorkshopChoice::ids( '12,34,12' ) );
		$this->assertSame( array( 12, 34 ), WorkshopChoice::ids( array( '12', 0, '34', 'x' ) ) );
		$this->assertSame( array(), WorkshopChoice::ids( '0' ), 'el 0 de «ninguno» no es un taller' );
		$this->assertSame( array(), WorkshopChoice::ids( '' ) );
		$this->assertSame( '12,34', WorkshopChoice::store( array( 12, 34 ) ) );
	}

	/**
	 * Sin ajuste se elige uno, que es lo que había antes.
	 */
	public function test_max_is_one_unless_set() {
		$this->assertSame( 1, WorkshopChoice::max( '' ) );
		$this->assertSame( 1, WorkshopChoice::max( 0 ) );
		$this->assertSame( 2, WorkshopChoice::max( '2' ) );
	}

	/**
	 * Dos talleres distintos en franjas distintas: se puede.
	 */
	public function test_two_different_workshops_at_different_times_pass() {
		$this->assertSame( '', WorkshopChoice::check( $this->talleres(), array( 10, 30 ), 2 ) );
		$this->assertSame( '', WorkshopChoice::check( $this->talleres(), array(), 2 ), 'no elegir ninguno vale' );
	}

	/**
	 * Las dos sesiones del mismo taller no, aunque el título cambie de mayúsculas o espacios.
	 */
	public function test_two_sessions_of_the_same_workshop_are_refused() {
		$this->assertSame( WorkshopChoice::REPEATED, WorkshopChoice::check( $this->talleres(), array( 10, 11 ), 2 ) );
		$this->assertSame( WorkshopChoice::REPEATED, WorkshopChoice::check( $this->talleres(), array( 20, 21 ), 2 ), 'aunque sea otro día' );
	}

	/**
	 * Dos talleres a la vez no.
	 */
	public function test_two_workshops_at_the_same_time_are_refused() {
		$this->assertSame( WorkshopChoice::CLASH, WorkshopChoice::check( $this->talleres(), array( 10, 20 ), 2 ) );
		$this->assertSame( WorkshopChoice::CLASH, WorkshopChoice::check( $this->talleres(), array( 11, 30 ), 2 ) );
	}

	/**
	 * Más de los que deja el evento, no; y lo que no es un taller del evento, tampoco.
	 */
	public function test_too_many_and_foreign_are_refused() {
		$this->assertSame( WorkshopChoice::TOO_MANY, WorkshopChoice::check( $this->talleres(), array( 10, 30 ), 1 ) );
		$this->assertSame( WorkshopChoice::NOT_HERE, WorkshopChoice::check( $this->talleres(), array( 99 ), 2 ) );
	}

	/**
	 * Solapar: pisarse las horas el mismo día; sin hora no se prohíbe.
	 */
	public function test_clash_needs_the_same_day_and_overlapping_hours() {
		$a = array(
			'date'  => '2027-02-06',
			'start' => '15:00',
			'end'   => '16:15',
		);
		$this->assertTrue(
			WorkshopChoice::clash(
				$a,
				array_merge(
					$a,
					array(
						'start' => '16:00',
						'end'   => '17:00',
					)
				)
			)
		);
		$this->assertFalse(
			WorkshopChoice::clash(
				$a,
				array_merge(
					$a,
					array(
						'start' => '16:15',
						'end'   => '17:00',
					)
				)
			),
			'uno acaba cuando empieza el otro'
		);
		$this->assertFalse( WorkshopChoice::clash( $a, array_merge( $a, array( 'date' => '2027-02-13' ) ) ) );
		$this->assertFalse( WorkshopChoice::clash( $a, array_merge( $a, array( 'start' => '' ) ) ), 'sin hora no se sabe' );
		$this->assertTrue( WorkshopChoice::clash( $a, array_merge( $a, array( 'end' => '' ) ) ), 'empiezan a la vez' );
	}

	/**
	 * Lo que se lee, en castellano y con el número del evento.
	 */
	public function test_the_texts_say_the_rules() {
		$this->assertStringContainsString( 'hasta 2 talleres', WorkshopChoice::rules_text( 2 ) );
		$this->assertStringContainsString( 'un taller', WorkshopChoice::rules_text( 1 ) );
		$this->assertStringContainsString( 'mismo taller', WorkshopChoice::why( WorkshopChoice::REPEATED, 2 ) );
		$this->assertStringContainsString( '2 talleres', WorkshopChoice::why( WorkshopChoice::TOO_MANY, 2 ) );
		$this->assertSame( '', WorkshopChoice::why( 'sin_plazas', 2 ) );
	}
}
