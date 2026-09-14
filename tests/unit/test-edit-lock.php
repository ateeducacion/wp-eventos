<?php
/**
 * Tests for the edit lock: two people, one event, and nobody stepping on
 * anybody.
 *
 * @package Evt
 */

use Evt\Meta\EventMetaKeys;
use Evt\PublicFront\EditLock;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\ExitSignal;
use Evt\PublicFront\PageForm;

/**
 * El bloqueo de edición, que es el NATIVO de WordPress.
 *
 * No se prueba un mecanismo propio: se prueba que el aplicativo usa la meta
 * `_edit_lock` del núcleo —la misma que el escritorio— y que la respeta en el
 * único sitio donde importa, que es antes de escribir. Por eso casi todas las
 * comprobaciones miran la base de datos DESPUÉS de intentarlo, y no el aviso.
 *
 * Y el bloqueo es del evento raíz: dos personas en dos secciones distintas del
 * mismo evento se pisan igual, así que tienen que verse.
 */
class Test_Edit_Lock extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * El área que organiza.
	 *
	 * @var int
	 */
	private $area;

	/**
	 * Quien abre el evento primero.
	 *
	 * @var int
	 */
	private $primera;

	/**
	 * Quien llega después, con el mismo permiso.
	 *
	 * @var int
	 */
	private $segunda;

	/**
	 * El evento.
	 *
	 * @var int
	 */
	private $evento;

	/**
	 * Dos personas del mismo área y un evento suyo.
	 */
	public function set_up() {
		parent::set_up();
		$this->pages();

		$this->area    = $this->area( 'Formación del Profesorado' );
		$this->primera = $this->organiser( array( $this->area ) );
		$this->segunda = $this->organiser( array( $this->area ) );
		wp_update_user(
			array(
				'ID'           => $this->primera,
				'display_name' => 'Primera persona',
			)
		);
		wp_update_user(
			array(
				'ID'           => $this->segunda,
				'display_name' => 'Segunda persona',
			)
		);

		$this->evento = $this->event( $this->primera, array( $this->area ), array(), array( 'post_title' => 'Jornadas originales' ) );
	}

	// ─── ayudas ────────────────────────────────────────────────────────────

	/**
	 * Abrir de verdad el taller del evento: es el `render()` quien toma el
	 * bloqueo, porque el `model()` decide y no muta.
	 *
	 * @param int $uid Who is looking.
	 * @return string La pantalla pintada.
	 */
	private function open_workshop( int $uid ): string {
		$this->acting_as( $uid );
		$_GET                              = array();
		$_GET[ EventWorkspace::ARG_EVENT ] = (string) $this->evento;
		return EventWorkspace::render();
	}

	/**
	 * Lo que el taller decide, sin llegar a pintarlo ni a tomar nada.
	 *
	 * @param int $uid Who is looking.
	 * @return array<string, mixed>
	 */
	private function workshop_model( int $uid ): array {
		$this->acting_as( $uid );
		$_GET                              = array();
		$_GET[ EventWorkspace::ARG_EVENT ] = (string) $this->evento;
		return EventWorkspace::model();
	}

	/**
	 * Abrir el formulario de una sección, que también toma el bloqueo.
	 *
	 * @param int $uid        Who is looking.
	 * @param int $section_id Satellite page.
	 * @return string
	 */
	private function open_section( int $uid, int $section_id ): string {
		$this->acting_as( $uid );
		$_GET            = array();
		$_GET['seccion'] = (string) $section_id;
		return PageForm::render();
	}

	/**
	 * Lo que el formulario de una sección decide, sin pintarlo.
	 *
	 * @param int $uid        Who is looking.
	 * @param int $section_id Satellite page.
	 * @return array<string, mixed>
	 */
	private function section_model( int $uid, int $section_id ): array {
		$this->acting_as( $uid );
		$_GET            = array();
		$_GET['seccion'] = (string) $section_id;
		return PageForm::model();
	}

	/**
	 * Quién tiene el bloqueo según la meta del núcleo.
	 *
	 * `wp_check_post_lock()` contesta «otra persona», así que a quien acaba de
	 * tomarlo le diría que no hay ninguno: aquí se lee la meta directamente,
	 * que es justo lo que hay que afirmar.
	 *
	 * @return int User ID, 0 when there is no lock.
	 */
	private function lock_owner(): int {
		$trozo = explode( ':', (string) get_post_meta( $this->evento, '_edit_lock', true ) );
		return isset( $trozo[1] ) ? (int) $trozo[1] : 0;
	}

	/**
	 * Mandar una operación del taller y devolver por dónde salió.
	 *
	 * @param int                   $uid   Who submits.
	 * @param string                $op    Operation.
	 * @param array<string, string> $extra Extra fields.
	 * @return string|null
	 */
	private function submit_workshop( int $uid, string $op, array $extra = array() ) {
		$this->acting_as( $uid );
		$this->post(
			array_merge(
				array(
					EventWorkspace::FIELD_DO      => $op,
					EventWorkspace::FIELD_EVENT   => (string) $this->evento,
					EventWorkspace::FIELD_SECTION => '0',
				),
				$extra
			),
			EventWorkspace::nonce_action( $op ),
			EventWorkspace::nonce_name( $op, 0 )
		);
		return $this->exit_url( array( EventWorkspace::class, 'handle' ) );
	}

	/**
	 * Preparar un «Tomar posesión».
	 *
	 * @param int         $uid   Who submits.
	 * @param string|null $nonce Null for the good one, a string to forge it.
	 * @return void
	 */
	private function post_takeover( int $uid, ?string $nonce = null ): void {
		$this->acting_as( $uid );
		$campos = array(
			EditLock::FIELD_DO   => EditLock::OP_TAKEOVER,
			EditLock::FIELD_POST => (string) $this->evento,
		);
		if ( null === $nonce ) {
			$this->post( $campos, EditLock::nonce_action( $this->evento ), EditLock::NONCE_FIELD );
		} else {
			$campos[ EditLock::NONCE_FIELD ] = $nonce;
			$this->post( $campos );
		}
	}

	/**
	 * Ejecutar algo que tiene que morir con `wp_die()` y devolver el motivo.
	 *
	 * @param callable $handler What to run.
	 * @return string
	 */
	private function died( callable $handler ): string {
		add_filter( 'evt_exit_throws', '__return_true' );
		try {
			$handler();
		} catch ( WPDieException $e ) {
			return $e->getMessage();
		} catch ( ExitSignal $e ) {
			$this->fail( 'Salió por una redirección en vez de rechazar la escritura: ' . $e->url );
		}
		$this->fail( 'La escritura no se rechazó.' );
	}

	// ─── tomar el bloqueo al abrir ─────────────────────────────────────────

	/**
	 * Abrir el taller toma el bloqueo NATIVO, el mismo que el escritorio.
	 *
	 * Esta es la ventaja de no inventarse uno: si alguien abre el evento en el
	 * editor de WordPress y otra persona en el taller, se ven.
	 */
	public function test_opening_the_workshop_takes_the_native_lock() {
		$this->assertSame( 0, (int) $this->workshop_model( $this->primera )['lock']['owner'], 'el evento estaba libre' );
		$this->assertSame( '', (string) get_post_meta( $this->evento, '_edit_lock', true ), 'y mirarlo no lo cogió' );

		$html = $this->open_workshop( $this->primera );

		$this->assertSame( $this->primera, $this->lock_owner() );
		$this->assertStringContainsString( 'id="evt-edit-lock"', $html );
		$this->assertMatchesRegularExpression( '/data-lock="\d+:' . $this->primera . '"/', $html, 'el Heartbeat necesita el bloqueo para renovarlo' );

		require_once ABSPATH . 'wp-admin/includes/post.php';
		$this->acting_as( $this->segunda );
		$this->assertSame( $this->primera, (int) wp_check_post_lock( $this->evento ), 'el escritorio ve el mismo bloqueo' );
	}

	/**
	 * A la segunda persona el taller se le abre en solo lectura y con el
	 * NOMBRE de quien lo tiene, no con un «alguien».
	 */
	public function test_the_second_person_sees_who_has_it_and_cannot_write() {
		$this->open_workshop( $this->primera );
		$m = $this->workshop_model( $this->segunda );

		$this->assertSame( $this->primera, (int) $m['lock']['owner'] );
		$this->assertSame( 'Primera persona', (string) $m['lock']['name'] );
		$this->assertFalse( $m['can_edit'], 'con el evento cogido, el taller no guarda' );
		$this->assertFalse( $m['can_archive'], 'ni se cierra el evento, que es lo menos reversible' );

		$html = $this->open_workshop( $this->segunda );
		$this->assertStringContainsString( 'Primera persona', $html );
		$this->assertStringContainsString( 'Tomar posesión', $html );
		$this->assertStringContainsString( 'data-lock=""', $html, 'no se renueva un bloqueo ajeno' );
		$this->assertStringContainsString( 'aria-labelledby="evt-lock-title" open', $html, 'el aviso se ve sin JavaScript' );
		$this->assertSame( $this->primera, $this->lock_owner(), 'y mirar no se lo quita a nadie' );
	}

	/**
	 * Y quien solo puede consultar no coge el bloqueo: se lo quitaría a quien
	 * sí puede editar.
	 */
	public function test_a_reader_does_not_take_the_lock() {
		$lock = EditLock::claim( EditLock::status( $this->evento, false ) );

		$this->assertSame( 0, $lock['event_id'] );
		$this->assertSame( '', (string) get_post_meta( $this->evento, '_edit_lock', true ) );
		$this->assertSame( '', EditLock::render( $lock ), 'sin evento no se pinta ningún aviso' );
	}

	// ─── antes de escribir, no después ─────────────────────────────────────

	/**
	 * El envío de quien perdió el bloqueo se rechaza con un 409 y ANTES de
	 * tocar nada: la pantalla que trae es la de hace media hora.
	 */
	public function test_a_stale_save_is_rejected_before_writing_anything() {
		$this->open_workshop( $this->primera );

		$motivo = $this->died(
			function () {
				$this->submit_workshop(
					$this->segunda,
					EventWorkspace::PANEL_SETTINGS,
					array( EventWorkspace::FIELD_TITLE => 'Sobrescrito' )
				);
			}
		);

		$this->assertStringContainsString( 'Primera persona', $motivo );
		$this->assertSame( 'Jornadas originales', get_the_title( $this->evento ) );
	}

	/**
	 * Tampoco se cierra el evento por la puerta de al lado: marcar como
	 * histórico es una escritura más y también pasa por el bloqueo.
	 */
	public function test_a_stale_archive_is_rejected_too() {
		$this->open_workshop( $this->primera );

		$this->died(
			function () {
				$this->submit_workshop( $this->segunda, EventWorkspace::OP_ARCHIVE );
			}
		);

		$this->assertSame( '', (string) get_post_meta( $this->evento, EventMetaKeys::ARCHIVED, true ) );
	}

	// ─── el bloqueo es del evento raíz ─────────────────────────────────────

	/**
	 * Dos personas en dos secciones distintas del mismo evento se pisan, así
	 * que el bloqueo que toma el formulario de una sección es el del evento.
	 */
	public function test_the_lock_of_a_section_is_the_lock_of_the_root_event() {
		$una  = $this->event_page( $this->evento, 'programa', array( 'post_title' => 'Programa' ) );
		$otra = $this->event_page( $this->evento, 'ponentes', array( 'post_title' => 'Ponentes' ) );

		$this->assertSame( $this->evento, (int) $this->section_model( $this->primera, $una )['lock']['event_id'], 'se bloquea el evento, no la página' );
		$this->open_section( $this->primera, $una );
		$this->assertSame( $this->primera, $this->lock_owner() );

		// La otra sección del mismo evento, y el taller, ven ese bloqueo.
		$m = $this->section_model( $this->segunda, $otra );
		$this->assertSame( $this->primera, (int) $m['lock']['owner'] );
		$this->assertStringContainsString( 'Primera persona', $this->open_section( $this->segunda, $otra ) );

		$m = $this->workshop_model( $this->segunda );
		$this->assertSame( $this->primera, (int) $m['lock']['owner'] );
	}

	/**
	 * Y guardar esa otra sección se rechaza igual, con el evento por delante.
	 */
	public function test_a_stale_section_save_is_rejected() {
		$seccion = $this->event_page( $this->evento, 'programa', array( 'post_title' => 'Programa' ) );
		$this->open_workshop( $this->primera );

		$this->acting_as( $this->segunda );
		$this->post(
			array(
				'evt_page_form'             => '1',
				'evt_page_id'               => (string) $seccion,
				'evt_title'                 => 'Sobrescrito',
				EventMetaKeys::SECTION_TYPE => 'programa',
			),
			PageForm::NONCE_ACTION,
			PageForm::NONCE_FIELD
		);

		$motivo = $this->died( array( PageForm::class, 'maybe_handle_submit' ) );

		$this->assertStringContainsString( 'Primera persona', $motivo );
		$this->assertSame( 'Programa', get_the_title( $seccion ) );
	}

	// ─── tomar posesión ────────────────────────────────────────────────────

	/**
	 * «Tomar posesión» se lo quita a la primera persona, y no guarda nada más.
	 */
	public function test_taking_over_moves_the_lock_and_writes_nothing_else() {
		$this->open_workshop( $this->primera );
		$this->post_takeover( $this->segunda );

		$this->assertNotNull( $this->exit_url( array( EditLock::class, 'handle' ) ), 'vuelve a la pantalla' );
		$this->assertSame( $this->segunda, $this->lock_owner() );
		$this->assertSame( 'Jornadas originales', get_the_title( $this->evento ) );
	}

	/**
	 * Sin su nonce no se toma nada: por POST y firmado, como toda mutación.
	 */
	public function test_taking_over_needs_its_nonce() {
		$this->open_workshop( $this->primera );
		$this->post_takeover( $this->segunda, 'inventado' );

		$this->assertNull( $this->exit_url( array( EditLock::class, 'handle' ) ), 'ni redirige ni hace nada' );
		$this->assertSame( $this->primera, $this->lock_owner() );
	}

	/**
	 * Y el nonce no es un permiso: quien no edita este evento no se lo queda.
	 */
	public function test_taking_over_needs_permission_over_the_event() {
		$this->open_workshop( $this->primera );
		$ajena = $this->organiser( array( $this->area( 'Innovación' ) ) );

		$this->post_takeover( $ajena );
		$this->died( array( EditLock::class, 'handle' ) );

		$this->assertSame( $this->primera, $this->lock_owner() );
	}

	// ─── soltar solo lo propio ─────────────────────────────────────────────

	/**
	 * Al soltar se borra SOLO el bloqueo propio: si otra persona ya tomó
	 * posesión, no se le quita.
	 */
	public function test_releasing_never_removes_someone_elses_lock() {
		$this->open_workshop( $this->primera );
		$this->post_takeover( $this->segunda );
		$this->exit_url( array( EditLock::class, 'handle' ) );
		$this->assertSame( $this->segunda, $this->lock_owner() );

		// La primera persona suelta lo que ya no es suyo: no pasa nada.
		$this->acting_as( $this->primera );
		EditLock::release( $this->evento );
		$this->assertSame( $this->segunda, $this->lock_owner(), 'el bloqueo de la otra persona sigue en pie' );

		// Y la suya sí se la lleva.
		$this->acting_as( $this->segunda );
		EditLock::release( $this->evento );
		$this->assertSame( '', (string) get_post_meta( $this->evento, '_edit_lock', true ) );
		$this->assertSame( 0, EditLock::owner( $this->evento ) );
	}

	/**
	 * Soltar una sección suelta el bloqueo de su evento, que es el que hay.
	 */
	public function test_releasing_a_section_releases_the_event() {
		$seccion = $this->event_page( $this->evento, 'programa' );
		$this->open_section( $this->primera, $seccion );
		$this->assertSame( $this->primera, $this->lock_owner() );

		EditLock::release( $seccion );

		$this->assertSame( '', (string) get_post_meta( $this->evento, '_edit_lock', true ) );
	}

	/**
	 * Cerrar el evento suelta el bloqueo: quien lo marcó ya no lo edita, y
	 * administración no tiene por qué esperar a que caduque para corregirlo.
	 */
	public function test_marking_the_event_as_historic_releases_the_lock() {
		$this->open_workshop( $this->primera );
		$this->assertSame( $this->primera, $this->lock_owner() );

		$this->submit_workshop( $this->primera, EventWorkspace::OP_ARCHIVE );

		$this->assertTrue( (bool) get_post_meta( $this->evento, EventMetaKeys::ARCHIVED, true ) );
		$this->assertSame( '', (string) get_post_meta( $this->evento, '_edit_lock', true ) );
	}
}
