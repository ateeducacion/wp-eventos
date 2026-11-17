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
		// Un documento por persona: el mismo en dos inscripciones del evento
		// ya no se admite (ADR-0051).
		$numero = 'ana@example.org' === $correo ? 12345678 : abs( crc32( $correo ) ) % 100000000;
		$v      = Registrations::validate(
			$this->evento,
			array(
				'tax_id'  => sprintf( '%08d', $numero ) . substr( 'TRWAGMYFPDXBNJZSQVHLCKE', $numero % 23, 1 ),
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
	 * Si el máximo bajó después de elegir, corregir un dato no le quita un taller.
	 */
	public function test_lowering_the_maximum_does_not_drop_a_workshop_on_correction() {
		update_post_meta( $this->evento, RegistrationMetaKeys::WORKSHOP_MAX, 2 );
		$id = $this->inscripcion();
		$a  = $this->taller( 5 );
		$b  = $this->taller( 5 );
		wp_update_post(
			array(
				'ID'         => $b,
				'post_title' => 'Otro taller',
			)
		);
		update_post_meta( $b, ProgrammeMetaKeys::ACTIVITY_START, '19:00' );
		update_post_meta( $b, ProgrammeMetaKeys::ACTIVITY_END, '20:00' );
		$this->assertTrue( Registrations::seat( $this->evento, $id, array( $a, $b ) )['ok'] );
		update_post_meta( $this->evento, RegistrationMetaKeys::WORKSHOP_MAX, 1 );

		$this->enviar(
			EventWorkspace::OP_REG_SAVE,
			$id,
			array(
				'evt_rg_tax_id'   => '12345678Z',
				'evt_rg_name'     => 'Ana',
				'evt_rg_surname'  => 'Martín',
				'evt_rg_email'    => 'ana@example.org',
				'evt_rg_phone'    => '600000000',
				'evt_rg_centre'   => '38000001',
				'evt_rg_workshop' => array( (string) $a, (string) $b ),
			)
		);

		$this->assertSame( 'ok', $this->aviso()['tipo'] );
		$this->assertEqualsCanonicalizing( array( $a, $b ), Registrations::workshops_of( $id ) );
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
		$this->assertMatchesRegularExpression( '/value="' . $taller . '"\s+checked/', $html, 'su taller, elegido' );
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

	// ─── inscribir a alguien desde la organización (ADR-0052) ──────────────

	/**
	 * Los campos de una persona nueva.
	 *
	 * @param array<string, mixed> $cambios Overrides.
	 * @return array<string, mixed>
	 */
	private function nueva( array $cambios = array() ): array {
		return array_merge(
			array(
				'evt_rg_tax_id'  => '00000001R',
				'evt_rg_name'    => 'Paula',
				'evt_rg_surname' => 'Hernández',
				'evt_rg_email'   => 'paula@example.org',
				'evt_rg_phone'   => '',
				'evt_rg_centre'  => '38000002',
				'evt_rg_consent' => '1',
				'evt_rg_mail'    => '1',
			),
			$cambios
		);
	}

	/**
	 * La organización inscribe a alguien: queda quién lo hizo, el consentimiento declarado y sale el correo.
	 */
	public function test_the_organisation_signs_somebody_up() {
		$taller  = $this->taller( 5 );
		$correos = array();
		add_filter(
			'pre_wp_mail',
			static function ( $nulo, array $atts ) use ( &$correos ): bool {
				unset( $nulo );
				$correos[] = $atts;
				return true;
			},
			10,
			2
		);

		$this->enviar( EventWorkspace::OP_REG_SAVE, 0, $this->nueva( array( 'evt_rg_workshop' => (string) $taller ) ) );
		remove_all_filters( 'pre_wp_mail' );

		$todas = Registrations::all( $this->evento );
		$this->assertCount( 1, $todas );
		$id = (int) $todas[0]->ID;
		$this->assertSame( 'ok', $this->aviso()['tipo'], (string) ( $this->aviso()['texto'] ?? '' ) );
		$this->assertSame( $this->admin, (int) get_post_meta( $id, RegistrationMetaKeys::REG_BY, true ) );
		$this->assertNotSame( '', get_post_meta( $id, RegistrationMetaKeys::REG_CONSENT_AT, true ) );
		$this->assertSame( array( $taller ), Registrations::workshops_of( $id ) );
		$this->assertStringContainsString( 'declarado por la organización', Participants::rows( $this->evento )[0]['consent'] );

		$this->assertCount( 1, $correos );
		$this->assertSame( 'paula@example.org', $correos[0]['to'] );
		$this->assertStringContainsString( 'le ha inscrito', $correos[0]['message'] );
	}

	/**
	 * Sin declarar el consentimiento no se inscribe, y lo escrito vuelve al formulario.
	 */
	public function test_signing_somebody_up_needs_the_consent() {
		$vuelta = $this->enviar( EventWorkspace::OP_REG_SAVE, 0, $this->nueva( array( 'evt_rg_consent' => '' ) ) );

		$this->assertSame( array(), Registrations::all( $this->evento ) );
		$this->assertSame( 'error', $this->aviso()['tipo'] );
		$this->assertStringContainsString( 'protección de datos', $this->aviso()['texto'] );
		$this->assertSame( '1', $this->query_arg( (string) $vuelta, EventWorkspace::ARG_NEW ), 'vuelve al panel de alta' );
		$this->assertSame( 'Paula', $this->aviso()['values']['name'], 'con lo que se escribió' );
	}

	/**
	 * El documento de otra inscripción no se repite, ni al inscribir ni al corregir.
	 */
	public function test_a_tax_id_already_signed_up_is_refused() {
		$ana = $this->inscripcion();

		$this->enviar( EventWorkspace::OP_REG_SAVE, 0, $this->nueva( array( 'evt_rg_tax_id' => '12345678Z' ) ) );
		$this->assertCount( 1, Registrations::all( $this->evento ), 'no se inscribe dos veces' );
		$this->assertStringContainsString( 'documento de identidad', $this->aviso()['texto'] );

		$this->enviar( EventWorkspace::OP_REG_SAVE, 0, $this->nueva() );
		$paula = (int) Registrations::by_tax_id( $this->evento, '00000001R' );
		$this->assertGreaterThan( 0, $paula );

		$campos                  = $this->nueva( array( 'evt_rg_tax_id' => '12345678Z' ) );
		$campos['evt_rg_centre'] = '38000001';
		$this->enviar( EventWorkspace::OP_REG_SAVE, $paula, $campos );
		$this->assertSame( '00000001R', get_post_meta( $paula, RegistrationMetaKeys::REG_TAX_ID, true ), 'corregir a un documento ajeno, no' );
		$this->assertSame( 'error', $this->aviso()['tipo'] );
		unset( $ana );
	}

	/**
	 * El botón y el formulario de alta salen en el panel, también sin nadie inscrito.
	 */
	public function test_the_add_form_is_painted_even_with_nobody_signed_up() {
		$this->taller( 5 );
		$this->acting_as( $this->admin );
		$_GET[ EventWorkspace::ARG_EVENT ] = (string) $this->evento;
		$_GET[ EventWorkspace::ARG_PANEL ] = EventWorkspace::PANEL_PEOPLE;
		$html                              = EventWorkspaceView::html( EventWorkspace::model() );
		$this->assertStringContainsString( 'Inscribir a una persona', $html );

		$_GET[ EventWorkspace::ARG_NEW ] = '1';
		$html                            = EventWorkspaceView::html( EventWorkspace::model() );
		$this->assertStringContainsString( 'name="evt_rg_consent"', $html );
		$this->assertStringContainsString( 'list="evt-rg-centros"', $html, 'el centro, con el catálogo a mano' );
		$this->assertStringContainsString( 'IES El Mirador', $html );
		$this->assertStringNotContainsString( 'Borrar la inscripción', $html, 'no hay nada que borrar todavía' );
	}
}
