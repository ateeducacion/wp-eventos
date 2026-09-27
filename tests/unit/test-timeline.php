<?php
/**
 * Tests for the public timeline of events.
 *
 * @package Evt
 */

use Evt\Meta\EventMetaKeys;
use Evt\PublicFront\Timeline;
use Evt\PublicFront\View\TimelineView;

/**
 * La puerta pública: todos los eventos publicados, mes a mes (ADR-0041).
 */
class Test_Timeline extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Un evento publicado que empieza ese día.
	 *
	 * @param string               $inicio Start date.
	 * @param array<string, mixed> $meta   Extra meta.
	 * @param array<string, mixed> $args   Post fields.
	 * @return int
	 */
	private function en( string $inicio, array $meta = array(), array $args = array() ): int {
		return $this->event(
			$this->administrator(),
			array( $this->area() ),
			array_merge( array( EventMetaKeys::START_DATE => $inicio ), $meta ),
			$args
		);
	}

	/**
	 * Los meses de un modelo, por su clave.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return array<string, array<string, mixed>>
	 */
	private function meses( array $m ): array {
		$out = array();
		foreach ( $m['months'] as $mes ) {
			$out[ $mes['key'] ] = $mes;
		}
		return $out;
	}

	/**
	 * Abre en el mes actual, con el anterior y el siguiente aunque estén vacíos.
	 */
	public function test_it_always_shows_the_months_around_today() {
		$m     = Timeline::model( '2026-09-27' );
		$meses = $this->meses( $m );

		$this->assertSame( array( '2026-08', '2026-09', '2026-10' ), array_keys( $meses ) );
		$this->assertTrue( $meses['2026-09']['current'] );
		$this->assertSame( 1, $m['current'] );
		$this->assertTrue( $meses['2026-08']['past'] );
		$this->assertSame( '2026-2027', $meses['2026-09']['course'], 'septiembre rotula el curso' );
	}

	/**
	 * Todos los eventos, también los pasados y los históricos, sin saltos.
	 */
	public function test_it_lists_every_event_without_gaps_between_months() {
		$viejo     = $this->en( '2025-11-12', array( EventMetaKeys::ARCHIVED => '1' ) );
		$siguiente = $this->en( '2027-03-04' );

		$meses = $this->meses( Timeline::model( '2026-09-27' ) );

		$this->assertArrayHasKey( '2025-11', $meses );
		$this->assertArrayHasKey( '2027-03', $meses );
		$this->assertCount( 17, $meses, 'de noviembre de 2025 a marzo de 2027, uno a uno' );
		$this->assertSame( array(), $meses['2026-01']['events'], 'un mes vacío también sale' );

		$this->assertSame( $viejo, $meses['2025-11']['events'][0]['id'] );
		$this->assertSame( 'Histórico', $meses['2025-11']['events'][0]['state_label'] );
		$this->assertTrue( $meses['2025-11']['events'][0]['done'] );
		$this->assertSame( $siguiente, $meses['2027-03']['events'][0]['id'] );
		$this->assertSame( 'Próximo', $meses['2027-03']['events'][0]['state_label'] );
	}

	/**
	 * Borradores, páginas satélite y eventos sin fecha no salen.
	 */
	public function test_only_published_root_events_with_a_date() {
		$borrador = $this->en( '2026-10-09', array(), array( 'post_status' => 'draft' ) );
		$evento   = $this->en( '2026-10-09' );
		$this->event_page( $evento );
		$this->event( $this->administrator(), array( $this->area() ) );

		$ids = wp_list_pluck( $this->meses( Timeline::model( '2026-09-27' ) )['2026-10']['events'], 'id' );

		$this->assertSame( array( $evento ), $ids );
		$this->assertNotContains( $borrador, $ids );
	}

	/**
	 * La vista lleva la tira, los botones y un enlace a cada evento.
	 */
	public function test_the_view_paints_the_strip_and_links_each_event() {
		$evento = $this->en( '2026-10-09', array(), array( 'post_title' => 'Encuentro de coordinación' ) );

		$html = TimelineView::html( Timeline::model( '2026-09-27' ) );

		$this->assertStringContainsString( 'class="evt-linea"', $html );
		$this->assertStringContainsString( 'data-actual="1"', $html );
		$this->assertStringContainsString( 'data-evt-linea="hoy"', $html );
		$this->assertStringContainsString( 'Encuentro de coordinación', $html );
		$this->assertStringContainsString( esc_url( get_permalink( $evento ) ), $html );
		$this->assertStringContainsString( 'Sin eventos este mes.', $html );
	}

	/**
	 * Sin sesión se ve: es la puerta pública, no una pantalla del aplicativo.
	 */
	public function test_it_is_public_and_registers_its_shortcode() {
		$this->app();
		Timeline::register();
		$this->acting_as( 0 );

		$this->assertTrue( shortcode_exists( Timeline::SHORTCODE ) );
		$this->assertStringContainsString( 'evt-linea', do_shortcode( '[' . Timeline::SHORTCODE . ']' ) );
	}
}
