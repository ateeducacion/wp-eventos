<?php
/**
 * Tests for the «Código» tab of the event workshop.
 *
 * @package Evt
 */

use Evt\Access\EventAccess;
use Evt\Meta\EventMetaKeys;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\View\EventCodePanel;

/**
 * El CSS y el JavaScript a medida del evento.
 *
 * Lo que se prueba no es el aviso —eso lo dice cualquiera— sino el estado
 * DESPUÉS de intentarlo: quien no tiene la capacidad no ve la pestaña, no la
 * abre escribiendo la dirección a mano y, aunque mande el POST, la meta se
 * queda exactamente como estaba.
 */
class Test_Event_Code_Panel extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Set up.
	 */
	public function set_up() {
		parent::set_up();
		// Mientras `load-order.php` no liste las dos clases del código a medida
		// —lo lleva el montaje—, se cargan aquí. `require_once` no estorba
		// cuando ya estén en la lista.
		require_once dirname( dirname( __DIR__ ) ) . '/src/Evt/PublicFront/CodeEditor.php';
		require_once dirname( dirname( __DIR__ ) ) . '/src/Evt/PublicFront/View/EventCodePanel.php';
		$this->pages();
	}

	/**
	 * Mandar el guardado del panel de código.
	 *
	 * @param int                   $uid      Who.
	 * @param int                   $event_id Event.
	 * @param array<string, string> $extra    Fields.
	 * @return string|null
	 */
	private function submit( int $uid, int $event_id, array $extra ) {
		$this->acting_as( $uid );
		$campos = array_merge(
			array(
				EventWorkspace::FIELD_DO    => EventWorkspace::PANEL_CODE,
				EventWorkspace::FIELD_EVENT => (string) $event_id,
			),
			$extra
		);
		$this->post( $campos, EventWorkspace::nonce_action( EventWorkspace::PANEL_CODE ), EventWorkspace::nonce_name( EventWorkspace::PANEL_CODE, 0 ) );
		return $this->exit_url( array( EventWorkspace::class, 'handle' ) );
	}

	/**
	 * La pestaña, el panel, el guardado y quién puede cada campo.
	 */
	public function test_the_code_tab_and_who_may_write_each_field() {
		$area  = $this->area( 'Innovación' );
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		update_user_meta( $admin, EventAccess::USER_AREA_META, array( $area ) );
		$evento = $this->event( $admin, array( $area ) );
		$org    = $this->organiser( array( $area ) );

		// La organización ve la pestaña, con el CSS dentro y sin el JavaScript.
		$this->acting_as( $org );
		$_GET[ EventWorkspace::ARG_EVENT ] = (string) $evento;
		$_GET[ EventWorkspace::ARG_PANEL ] = EventWorkspace::PANEL_CODE;
		$m                                 = EventWorkspace::model();
		$this->assertArrayHasKey( EventWorkspace::PANEL_CODE, $m['panels'] );
		$this->assertSame( EventWorkspace::PANEL_CODE, $m['panel'], 'y la URL directa la abre' );
		$this->assertTrue( $m['can_edit_css'] );
		$this->assertFalse( $m['can_edit_js'] );

		// Su CSS se guarda; el JavaScript que mande a mano, ni se toca.
		$this->submit(
			$org,
			$evento,
			array(
				EventMetaKeys::CUSTOM_CSS => 'body{color:red}',
				EventMetaKeys::CUSTOM_JS  => 'alert(1)',
			)
		);
		$this->assertSame( 'body{color:red}', (string) get_post_meta( $evento, EventMetaKeys::CUSTOM_CSS, true ) );
		$this->assertSame( '', (string) get_post_meta( $evento, EventMetaKeys::CUSTOM_JS, true ) );
		$this->assertSame( 'aviso', (string) get_transient( 'evt_ws_flash_' . $org )['tipo'] );

		// La administración sí.
		$this->acting_as( $admin );
		$m = EventWorkspace::model();
		$this->assertArrayHasKey( EventWorkspace::PANEL_CODE, $m['panels'] );
		$this->assertSame( 'Código', $m['panels'][ EventWorkspace::PANEL_CODE ]['label'] );
		$this->assertSame( EventWorkspace::PANEL_CODE, $m['panel'] );
		$this->assertTrue( $m['can_edit_css'] );
		$this->assertTrue( $m['can_edit_js'] );

		$html = EventCodePanel::html( $m );
		$this->assertStringContainsString( 'evt-solo-admin', $html );
		$this->assertStringContainsString( 'Solo administración', $html );
		$this->assertStringContainsString( 'name="evt_custom_css"', $html );
		$this->assertStringContainsString( 'name="evt_custom_js"', $html );
		$this->assertStringContainsString( 'navegador de quien visite', $html );
		$this->assertStringContainsString( EventWorkspace::nonce_name( EventWorkspace::PANEL_CODE ), $html );

		$destino = $this->submit(
			$admin,
			$evento,
			array(
				EventMetaKeys::CUSTOM_CSS => '.evt-hoja > p { color: #333; }',
				EventMetaKeys::CUSTOM_JS  => 'console.log("hola</script>");',
			)
		);
		$this->assertStringContainsString( 'panel=' . EventWorkspace::PANEL_CODE, (string) $destino );
		$this->assertSame( '.evt-hoja > p { color: #333; }', (string) get_post_meta( $evento, EventMetaKeys::CUSTOM_CSS, true ), 'el > sobrevive' );
		$this->assertStringNotContainsString( '</script', (string) get_post_meta( $evento, EventMetaKeys::CUSTOM_JS, true ) );
		$this->assertSame( 'ok', (string) get_transient( 'evt_ws_flash_' . $admin )['tipo'] );

		// Con el CSS pero sin el JavaScript: se guarda el CSS y se dice del JS.
		add_filter( 'evt_allow_custom_js', '__return_false' );
		$this->acting_as( $admin );
		$m = EventWorkspace::model();
		$this->assertTrue( $m['can_edit_css'] );
		$this->assertFalse( $m['can_edit_js'] );
		$this->assertSame( '', $m['code']['js'], 'ni se le devuelve lo guardado' );
		$this->assertArrayHasKey( EventWorkspace::PANEL_CODE, $m['panels'], 'con solo el CSS, la pestaña sigue' );

		$html = EventCodePanel::html( $m );
		$this->assertStringNotContainsString( 'name="evt_custom_js"', $html );
		$this->assertStringContainsString( 'no puede editar el JavaScript', $html );
		$this->assertStringContainsString( 'name="evt_custom_css"', $html );

		$antes = (string) get_post_meta( $evento, EventMetaKeys::CUSTOM_JS, true );
		$this->submit(
			$admin,
			$evento,
			array(
				EventMetaKeys::CUSTOM_CSS => 'p{margin:0}',
				EventMetaKeys::CUSTOM_JS  => 'alert(1)',
			)
		);
		$flash = (array) get_transient( 'evt_ws_flash_' . $admin );
		$this->assertSame( 'aviso', (string) $flash['tipo'] );
		$this->assertStringContainsString( 'el JavaScript', (string) $flash['texto'] );
		$this->assertSame( 'p{margin:0}', (string) get_post_meta( $evento, EventMetaKeys::CUSTOM_CSS, true ), 'el CSS sí' );
		$this->assertSame( $antes, (string) get_post_meta( $evento, EventMetaKeys::CUSTOM_JS, true ), 'el JS, intacto' );
		remove_filter( 'evt_allow_custom_js', '__return_false' );

		// Con la capacidad pero sin el área: la puerta es la misma que la del taller.
		$otra    = $this->area( 'Formación' );
		$ajeno   = $this->event( $admin, array( $otra ) );
		$acotado = self::factory()->user->create( array( 'role' => 'evt_organiser' ) );
		update_user_meta( $acotado, EventAccess::USER_AREA_META, array( $area ) );
		get_user_by( 'id', $acotado )->add_cap( EventAccess::CAP_CUSTOM_CSS );
		$this->assertFalse( EventAccess::can_edit_custom_css( $acotado, $ajeno ), 'la capacidad no abre otra área' );
		$this->submit( $acotado, $ajeno, array( EventMetaKeys::CUSTOM_CSS => 'x{}' ) );
		$this->assertSame( '', (string) get_post_meta( $ajeno, EventMetaKeys::CUSTOM_CSS, true ) );
	}

	/**
	 * Lo que ve la organización en la pestaña, pintado.
	 *
	 * El modelo ya dice que puede el CSS y no el JavaScript; esto comprueba lo
	 * que sale en pantalla, que es lo que decide si se entiende: el CSS como un
	 * campo corriente y SIN recuadro amarillo —el amarillo es para lo que solo
	 * ve la administración, y aquí no hay nada de eso—, el JavaScript sustituido
	 * por la línea que explica por qué no está, y el botón de guardar en su
	 * sitio, porque sí hay algo que guardar.
	 */
	public function test_the_organiser_sees_the_css_field_with_no_yellow_box() {
		$area  = $this->area( 'Innovación' );
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		update_user_meta( $admin, EventAccess::USER_AREA_META, array( $area ) );
		$evento = $this->event( $admin, array( $area ) );
		$org    = $this->organiser( array( $area ) );

		$this->acting_as( $org );
		$_GET[ EventWorkspace::ARG_EVENT ] = (string) $evento;
		$_GET[ EventWorkspace::ARG_PANEL ] = EventWorkspace::PANEL_CODE;

		$html = EventCodePanel::html( EventWorkspace::model() );

		$this->assertStringContainsString( 'name="evt_custom_css"', $html );
		$this->assertStringNotContainsString( 'name="evt_custom_js"', $html );
		$this->assertStringNotContainsString( 'evt-solo-admin', $html, 'ningún recuadro amarillo para quien organiza' );
		$this->assertStringNotContainsString( 'Solo administración', $html );
		$this->assertStringContainsString( 'no puede editar el JavaScript', $html, 'se dice por qué falta, no se deja el hueco' );
		$this->assertStringContainsString( 'Guardar el código', $html );
	}
}
