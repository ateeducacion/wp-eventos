<?php
/**
 * Tests for correcting and deleting registrations from the workshop.
 *
 * @package Evt
 */

use Evt\Meta\ProgrammeMetaKeys;
use Evt\Meta\RegistrationMetaKeys;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\Participants;
use Evt\PublicFront\Programme;
use Evt\PublicFront\Registrations;
use Evt\PublicFront\View\EventWorkspaceView;

/**
 * Quien organiza corrige y borra inscripciones; borrar pide el correo (ADR-0043).
 */
class Test_Registration_Admin extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * The event.
	 *
	 * @var int
	 */
	private $evento;

	/**
	 * Who organises it.
	 *
	 * @var int
	 */
	private $admin;

	/**
	 * Un evento con catálogo de centros.
	 */
	public function set_up(): void {
		parent::set_up();
		add_filter(
			'evt_centres',
			static function (): array {
				return array(
					'38000001' => 'CEIP El Molino',
					'38000002' => 'IES El Mirador',
				);
			}
		);
		$this->app();
		$this->pages();
		$this->admin  = $this->administrator();
		$this->evento = $this->event( $this->admin, array( $this->area( 'Innovación' ) ) );
	}

	/**
	 * Sin filtros de otro test.
	 */
	public function tear_down(): void {
		remove_all_filters( 'evt_centres' );
		parent::tear_down();
	}

	/**
	 * A saved registration.
	 *
	 * @param string $correo Email.
	 * @return int
	 */
	private function inscripcion( string $correo = 'ana@example.org' ): int {
		$v = Registrations::validate(
			$this->evento,
			array(
				'tax_id'  => '12345678Z',
				'name'    => 'Ana',
				'surname' => 'Martín',
				'email'   => $correo,
				'phone'   => '',
				'centre'  => '38000001',
				'consent' => '1',
			),
			array()
		);
		return Registrations::create( $this->evento, $v['core'], $v['answers'] );
	}

	/**
	 * A workshop with seats.
	 *
	 * @param int $aforo Seats.
	 * @return int
	 */
	private function taller( int $aforo ): int {
		return Programme::save_activity(
			$this->evento,
			0,
			array(
				'title'    => 'Taller de radio',
				'kind'     => ProgrammeMetaKeys::KIND_WORKSHOP,
				'date'     => '2026-10-28',
				'start'    => '10:00',
				'end'      => '12:00',
				'venue'    => '',
				'room'     => '',
				'seats'    => $aforo,
				'summary'  => '',
				'speakers' => array(),
			)
		);
	}

	/**
	 * Send one registration operation and come back.
	 *
	 * @param string               $op    Operation.
	 * @param int                  $id    Registration.
	 * @param array<string, mixed> $extra Fields.
	 * @return string|null Where it went.
	 */
	private function enviar( string $op, int $id, array $extra = array() ): ?string {
		$this->acting_as( $this->admin );
		$this->post(
			array_merge(
				array(
					EventWorkspace::FIELD_DO    => $op,
					EventWorkspace::FIELD_EVENT => (string) $this->evento,
					EventWorkspace::FIELD_ROW   => (string) $id,
				),
				$extra
			),
			EventWorkspace::nonce_action( $op ),
			EventWorkspace::nonce_name( $op, $id )
		);
		return $this->exit_url( array( EventWorkspace::class, 'handle' ) );
	}

	/**
	 * The notice left for the admin.
	 *
	 * @return array<string, mixed>
	 */
	private function aviso(): array {
		return (array) get_transient( 'evt_ws_flash_' . $this->admin );
	}

	/**
	 * Borrar pide el correo tal cual; sin él, no se borra nada.
	 */
	public function test_deleting_needs_the_email_typed() {
		$id = $this->inscripcion( 'Ana@Example.org' );

		$this->enviar( EventWorkspace::OP_REG_DELETE, $id, array( EventWorkspace::FIELD_CONFIRM_EMAIL => 'otra@example.org' ) );
		$this->assertNotNull( get_post( $id ), 'con otro correo sigue ahí' );
		$this->assertSame( 'error', $this->aviso()['tipo'] );

		$this->enviar( EventWorkspace::OP_REG_DELETE, $id );
		$this->assertNotNull( get_post( $id ), 'sin correo, tampoco' );

		$this->enviar( EventWorkspace::OP_REG_DELETE, $id, array( EventWorkspace::FIELD_CONFIRM_EMAIL => ' ana@example.ORG ' ) );
		$this->assertNull( get_post( $id ), 'con su correo se borra del todo, sin papelera' );
		$this->assertSame( 'ok', $this->aviso()['tipo'] );
	}

	/**
	 * Una inscripción de otro evento no se borra desde este.
	 */
	public function test_a_registration_of_another_event_is_not_reachable() {
		$otro         = $this->event( $this->admin, array( $this->area() ) );
		$ajena        = $this->inscripcion();
		$this->evento = $otro;
		$this->assertSame( 'no_es_de_este_evento', Registrations::delete( $otro, $ajena, 'ana@example.org' ) );
		$this->assertFalse( Registrations::update( $otro, $ajena, array(), array() ) );
		$this->assertNotNull( get_post( $ajena ) );
	}

	/**
	 * Corregir cambia el núcleo y el taller, con su aforo, y no el consentimiento.
	 */
	public function test_correcting_changes_the_core_and_the_workshop() {
		$id      = $this->inscripcion();
		$lleno   = $this->taller( 1 );
		$libre   = $this->taller( 5 );
		$otra    = $this->inscripcion( 'otra@example.org' );
		$momento = get_post_meta( $id, RegistrationMetaKeys::REG_CONSENT_AT, true );
		Registrations::seat( $this->evento, $otra, $lleno );

		$campos = array(
			'evt_rg_tax_id'   => '12345678Z',
			'evt_rg_name'     => 'Ana María',
			'evt_rg_surname'  => 'Martín',
			'evt_rg_email'    => 'ana.maria@example.org',
			'evt_rg_phone'    => '',
			'evt_rg_centre'   => '38000002',
			'evt_rg_workshop' => (string) $lleno,
		);
		$this->enviar( EventWorkspace::OP_REG_SAVE, $id, $campos );
		$this->assertSame( 'Ana María', get_post_meta( $id, RegistrationMetaKeys::REG_NAME, true ), 'los datos se guardan' );
		$this->assertSame( 'IES El Mirador', get_post_meta( $id, RegistrationMetaKeys::REG_CENTRE, true ), 'el centro, del catálogo' );
		$this->assertSame( 0, (int) get_post_meta( $id, RegistrationMetaKeys::REG_WORKSHOP, true ), 'un taller completo no admite a nadie más' );
		$this->assertStringContainsString( 'completo', $this->aviso()['texto'] );
		$this->assertSame( $momento, get_post_meta( $id, RegistrationMetaKeys::REG_CONSENT_AT, true ), 'el consentimiento no se toca' );

		$campos['evt_rg_workshop'] = (string) $libre;
		$this->enviar( EventWorkspace::OP_REG_SAVE, $id, $campos );
		$this->assertSame( $libre, (int) get_post_meta( $id, RegistrationMetaKeys::REG_WORKSHOP, true ) );
		$this->assertSame( 'ok', $this->aviso()['tipo'] );

		// Un correo mal escrito no se guarda y vuelve al panel abierto.
		$campos['evt_rg_email'] = 'no-es-un-correo';
		$vuelta                 = $this->enviar( EventWorkspace::OP_REG_SAVE, $id, $campos );
		$this->assertSame( 'ana.maria@example.org', get_post_meta( $id, RegistrationMetaKeys::REG_EMAIL, true ) );
		$this->assertSame( (string) $id, $this->query_arg( (string) $vuelta, EventWorkspace::ARG_ROW ) );
	}

	/**
	 * Un centro que llegó sin código se conserva si no se escribe uno.
	 */
	public function test_a_centre_without_code_is_kept() {
		$id = $this->inscripcion();
		update_post_meta( $id, RegistrationMetaKeys::REG_CENTRE_CODE, '' );
		update_post_meta( $id, RegistrationMetaKeys::REG_CENTRE, 'Centro de antes' );

		$this->enviar(
			EventWorkspace::OP_REG_SAVE,
			$id,
			array(
				'evt_rg_tax_id'  => '12345678Z',
				'evt_rg_name'    => 'Ana',
				'evt_rg_surname' => 'Martín',
				'evt_rg_email'   => 'ana@example.org',
				'evt_rg_centre'  => '',
			)
		);

		$this->assertSame( 'ok', $this->aviso()['tipo'] );
		$this->assertSame( 'Centro de antes', get_post_meta( $id, RegistrationMetaKeys::REG_CENTRE, true ) );
	}

	/**
	 * Los caminos que no hacen nada: una inscripción que no es de este
	 * evento o ya no existe, y un taller que no es del evento.
	 */
	public function test_what_does_not_belong_is_refused() {
		$id    = $this->inscripcion();
		$ajena = 987654;

		$this->enviar( EventWorkspace::OP_REG_SAVE, $ajena, array( 'evt_rg_name' => 'X' ) );
		$this->assertSame( 'error', $this->aviso()['tipo'] );
		$this->enviar( EventWorkspace::OP_REG_DELETE, $ajena, array( EventWorkspace::FIELD_CONFIRM_EMAIL => 'ana@example.org' ) );
		$this->assertSame( 'error', $this->aviso()['tipo'] );

		// Una actividad que no es taller no se elige como taller.
		$charla = Programme::save_activity(
			$this->evento,
			0,
			array(
				'title'    => 'Charla',
				'kind'     => 'ponencia',
				'date'     => '2026-10-28',
				'start'    => '',
				'end'      => '',
				'venue'    => '',
				'room'     => '',
				'seats'    => 0,
				'summary'  => '',
				'speakers' => array(),
			)
		);
		$this->enviar(
			EventWorkspace::OP_REG_SAVE,
			$id,
			array(
				'evt_rg_tax_id'   => '12345678Z',
				'evt_rg_name'     => 'Ana',
				'evt_rg_surname'  => 'Martín',
				'evt_rg_email'    => 'ana@example.org',
				'evt_rg_centre'   => '38000001',
				'evt_rg_workshop' => (string) $charla,
			)
		);
		$this->assertStringContainsString( 'no es de este evento', $this->aviso()['texto'] );
		$this->assertSame( 0, (int) get_post_meta( $id, RegistrationMetaKeys::REG_WORKSHOP, true ) );
	}

	/**
	 * El panel lateral pinta cada tipo de pregunta con su respuesta, y el taller.
	 */
	public function test_the_form_paints_every_question_and_the_workshop() {
		update_post_meta(
			$this->evento,
			RegistrationMetaKeys::SIGNUP_QUESTIONS,
			wp_json_encode(
				array(
					array(
						'id'       => 'qcomer01',
						'label'    => 'Se queda a comer',
						'type'     => 'check',
						'options'  => array(),
						'required' => false,
					),
					array(
						'id'       => 'qetapa01',
						'label'    => 'Etapa',
						'type'     => 'one',
						'options'  => array( 'Infantil', 'Primaria' ),
						'required' => false,
					),
					array(
						'id'       => 'qalerg01',
						'label'    => 'Alergias',
						'type'     => 'many',
						'options'  => array( 'Gluten', 'Lactosa' ),
						'required' => false,
					),
					array(
						'id'       => 'qnotas01',
						'label'    => 'Observaciones',
						'type'     => 'text',
						'options'  => array(),
						'required' => false,
					),
					array(
						'id'       => 'qdocum01',
						'label'    => 'Documento',
						'type'     => 'file',
						'options'  => array(),
						'required' => false,
					),
				)
			)
		);
		$taller = $this->taller( 20 );
		$v      = Registrations::validate(
			$this->evento,
			array(
				'tax_id'  => '12345678Z',
				'name'    => 'Ana',
				'surname' => 'Martín',
				'email'   => 'ana@example.org',
				'centre'  => '38000001',
				'consent' => '1',
			),
			array(
				'qcomer01' => '1',
				'qetapa01' => 'Primaria',
				'qalerg01' => array( 'Gluten' ),
				'qnotas01' => 'Llega tarde',
			)
		);
		$id     = Registrations::create( $this->evento, $v['core'], $v['answers'] );
		Registrations::seat( $this->evento, $id, $taller );

		$this->acting_as( $this->admin );
		$_GET[ EventWorkspace::ARG_EVENT ] = (string) $this->evento;
		$_GET[ EventWorkspace::ARG_PANEL ] = EventWorkspace::PANEL_PEOPLE;
		$_GET[ EventWorkspace::ARG_ROW ]   = (string) $id;
		$html                              = EventWorkspaceView::html( EventWorkspace::model() );

		$this->assertMatchesRegularExpression( '/name="evt_rg_answers\[qcomer01\]" value="1"\s+checked/', $html, 'la casilla, marcada' );
		$this->assertMatchesRegularExpression( '/value="Primaria"\s+selected/', $html, 'la opción elegida' );
		$this->assertMatchesRegularExpression( '/value="Gluten"\s+checked/', $html, 'las varias opciones' );
		$this->assertStringContainsString( 'value="Llega tarde"', $html );
		$this->assertStringNotContainsString( 'evt_rg_answers[qdocum01]', $html, 'la de archivo no se corrige' );
		$this->assertMatchesRegularExpression( '/value="' . $taller . '"\s+selected/', $html, 'su taller, elegido' );
		$this->assertStringContainsString( 'Taller de radio (1 de 20)', $html );
	}

	/**
	 * El panel pinta el lápiz, el borrado con su correo y el panel lateral.
	 */
	public function test_the_panel_offers_correct_and_delete() {
		$id = $this->inscripcion();
		$this->acting_as( $this->admin );
		$filas = Participants::rows( $this->evento );
		$this->assertSame( $id, $filas[0][ Participants::KEY_REG ] );

		$_GET[ EventWorkspace::ARG_EVENT ] = (string) $this->evento;
		$_GET[ EventWorkspace::ARG_PANEL ] = EventWorkspace::PANEL_PEOPLE;
		$_GET[ EventWorkspace::ARG_ROW ]   = (string) $id;
		$html                              = EventWorkspaceView::html( EventWorkspace::model() );

		$this->assertStringContainsString( 'Corregir la inscripción', $html );
		$this->assertStringContainsString( 'data-evt-confirm-escribe="ana@example.org"', $html );
		$this->assertStringContainsString( 'name="' . EventWorkspace::FIELD_CONFIRM_EMAIL . '"', $html, 'sin guion, el campo va en el formulario' );
		$this->assertStringContainsString( 'value="' . EventWorkspace::OP_REG_SAVE . '"', $html, 'el panel lateral, abierto' );
		$this->assertStringContainsString( 'value="Ana"', $html );
	}
}
