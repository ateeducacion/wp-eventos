<?php
/**
 * Tests for EventState: próximo / abierto / finalizado from the dates.
 *
 * @package Evt
 */

use Evt\Domain\EventState;
use Evt\Meta\EventMetaKeys;

/**
 * El estado del evento, que se calcula y no se guarda.
 *
 * En producción es hoy un término que alguien marca a mano y nadie vuelve a
 * tocar; aquí sale de las fechas, así que lo único que hay que probar bien son
 * los bordes.
 */
class Test_Event_State extends WP_UnitTestCase {

	/**
	 * Estado según las fechas, bordes incluidos.
	 */
	public function test_state_by_dates_including_the_edges() {
		$casos = array(
			// Principio, fin, día de referencia, estado esperado.
			array( '2026-03-10', '2026-03-12', '2026-03-09', EventMetaKeys::STATE_UPCOMING ),
			array( '2026-03-10', '2026-03-12', '2026-03-10', EventMetaKeys::STATE_OPEN ),
			array( '2026-03-10', '2026-03-12', '2026-03-11', EventMetaKeys::STATE_OPEN ),
			array( '2026-03-10', '2026-03-12', '2026-03-12', EventMetaKeys::STATE_OPEN ),
			array( '2026-03-10', '2026-03-12', '2026-03-13', EventMetaKeys::STATE_FINISHED ),
			// De un solo día: sin fecha de fin, el fin es el principio.
			array( '2026-03-10', '', '2026-03-09', EventMetaKeys::STATE_UPCOMING ),
			array( '2026-03-10', '', '2026-03-10', EventMetaKeys::STATE_OPEN ),
			array( '2026-03-10', '', '2026-03-11', EventMetaKeys::STATE_FINISHED ),
			// Un fin anterior al principio se ignora: el intervalo no se invierte.
			array( '2026-03-10', '2026-03-01', '2026-03-10', EventMetaKeys::STATE_OPEN ),
			array( '2026-03-10', '2026-03-01', '2026-03-11', EventMetaKeys::STATE_FINISHED ),
			// Espacios sobrantes en cualquiera de los tres.
			array( ' 2026-03-10 ', ' 2026-03-12 ', ' 2026-03-11 ', EventMetaKeys::STATE_OPEN ),
			// Todavía sin fechas: el evento se está preparando.
			array( '', '2026-03-12', '2026-03-30', EventMetaKeys::STATE_UPCOMING ),
			array( '', '', '2026-03-30', EventMetaKeys::STATE_UPCOMING ),
			// Cambio de año y de mes, por si alguien compara números y no cadenas.
			array( '2025-12-31', '2026-01-02', '2026-01-01', EventMetaKeys::STATE_OPEN ),
			array( '2025-12-31', '2026-01-02', '2026-01-03', EventMetaKeys::STATE_FINISHED ),
		);

		foreach ( $casos as $caso ) {
			$this->assertSame( $caso[3], EventState::of( $caso[0], $caso[1], $caso[2] ), implode( ' | ', $caso ) );
		}
	}

	/**
	 * Sin día de referencia, el día de referencia es hoy.
	 */
	public function test_today_is_the_default_reference_day() {
		$ahora  = time();
		$hoy    = gmdate( 'Y-m-d', $ahora );
		$ayer   = gmdate( 'Y-m-d', $ahora - DAY_IN_SECONDS );
		$manana = gmdate( 'Y-m-d', $ahora + DAY_IN_SECONDS );

		$this->assertSame( EventMetaKeys::STATE_OPEN, EventState::of( $hoy, $hoy ) );
		$this->assertSame( EventMetaKeys::STATE_OPEN, EventState::of( $ayer, $manana ) );
		$this->assertSame( EventMetaKeys::STATE_UPCOMING, EventState::of( $manana, $manana ) );
		$this->assertSame( EventMetaKeys::STATE_FINISHED, EventState::of( $ayer, $ayer ) );
	}

	/**
	 * Las etiquetas son las tres del vocabulario, y solo esas tres.
	 */
	public function test_labels_answer_only_for_our_own_states() {
		$this->assertSame( 'Próximo', EventState::label( EventMetaKeys::STATE_UPCOMING ) );
		$this->assertSame( 'Abierto', EventState::label( EventMetaKeys::STATE_OPEN ) );
		$this->assertSame( 'Finalizado', EventState::label( EventMetaKeys::STATE_FINISHED ) );

		// Los términos viejos de la taxonomía `convocatoria` no son estados.
		$this->assertSame( '', EventState::label( 'evento-abierto' ) );
		$this->assertSame( '', EventState::label( 'evento-finalizado' ) );
		$this->assertSame( '', EventState::label( '' ) );

		$this->assertSame(
			array( EventMetaKeys::STATE_UPCOMING, EventMetaKeys::STATE_OPEN, EventMetaKeys::STATE_FINISHED ),
			array_keys( EventMetaKeys::states() )
		);
	}
}
