<?php
/**
 * Tests for the public signup form: what it paints and what it does.
 *
 * @package Evt
 */

use Evt\Meta\EventMetaKeys;
use Evt\Meta\ProgrammeMetaKeys;
use Evt\Meta\RegistrationMetaKeys;
use Evt\PublicFront\Block\SignupBlock;
use Evt\PublicFront\Programme;
use Evt\PublicFront\Registrations;
use Evt\PublicFront\SignupForm;

/**
 * El formulario público, de punta a punta.
 *
 * Lo que se comprueba aquí y en ningún otro sitio: que una persona **sin cuenta
 * en el sitio** puede inscribirse, que vuelve a su inscripción con un testigo y
 * no con su correo tecleado, y que el formulario no enseña el núcleo como algo
 * editable, porque no lo es (ADR-0031).
 */
class Test_Signup_Form extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Sin nadie dentro: quien se inscribe no tiene cuenta.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		$this->acting_as( 0 );
	}

	/**
	 * Un evento con la inscripción abierta y su sección.
	 *
	 * @return array{0:int, 1:int}
	 */
	private function evento_abierto(): array {
		$evento = $this->event(
			$this->administrator(),
			array( $this->area( 'Innovación' ) ),
			array(),
			array( 'post_title' => 'Jornadas de prueba' )
		);
		update_post_meta( $evento, RegistrationMetaKeys::SIGNUP_OPEN, true );
		update_post_meta( $evento, RegistrationMetaKeys::CONSENT_VERSION, 3 );

		$seccion = $this->event_page(
			$evento,
			'inscripcion',
			array(
				'post_title' => 'Inscripción',
				'post_name'  => 'inscripcion',
			)
		);
		update_post_meta( $seccion, EventMetaKeys::SECTION_TYPE, 'inscripcion' );

		// El catálogo de centros lo contesta quien lo tenga; aquí, el test.
		add_filter(
			'evt_centres',
			static function (): array {
				return array(
					'38000001' => 'CEIP El Molino',
					'38000002' => 'IES El Mirador',
				);
			}
		);

		return array( $evento, $seccion );
	}

	/**
	 * El bloque de una sección de inscripción.
	 *
	 * @param int $evento Event ID.
	 * @return string
	 */
	private function pintar( int $evento ): string {
		return SignupBlock::html(
			array(
				'section_type' => 'inscripcion',
				'event_id'     => $evento,
			)
		);
	}

	/**
	 * Un envío del formulario.
	 *
	 * @param int                  $evento Event ID.
	 * @param array<string, mixed> $campos Fields.
	 * @return string|null Return URL.
	 */
	private function enviar( int $evento, array $campos ): ?string {
		$this->post(
			array_merge(
				array(
					SignupForm::FIELD_OP    => SignupForm::OP_SIGNUP,
					SignupForm::FIELD_EVENT => (string) $evento,
				),
				$campos
			),
			SignupForm::NONCE_ACTION,
			SignupForm::NONCE_FIELD
		);
		return $this->exit_url( array( SignupForm::class, 'maybe_handle_submit' ) );
	}

	/**
	 * Los datos que pasan.
	 *
	 * @param array<string, mixed> $cambios Overrides.
	 * @return array<string, mixed>
	 */
	private function datos( array $cambios = array() ): array {
		return array_merge(
			array(
				'tax_id'  => '12345678Z',
				'name'    => 'Ana',
				'surname' => 'Martín Cabrera',
				'email'   => 'ana@example.org',
				'phone'   => '600000000',
				'centre'  => '38000001',
				'consent' => '1',
			),
			$cambios
		);
	}

	// ─── lo que se pinta ───────────────────────────────────────────────────

	/**
	 * El formulario sale con el núcleo y el consentimiento, y sin más.
	 */
	public function test_the_form_shows_the_core_and_the_consent() {
		list( $evento ) = $this->evento_abierto();
		$html           = $this->pintar( $evento );

		foreach ( array( 'tax_id', 'name', 'surname', 'email', 'phone' ) as $campo ) {
			$this->assertStringContainsString( 'name="' . $campo . '"', $html, 'falta ' . $campo );
		}
		$this->assertStringContainsString( 'name="centre"', $html );
		$this->assertStringContainsString( 'name="consent"', $html );
		$this->assertStringContainsString( 'CEIP El Molino', $html, 'el centro sale del catálogo' );
		$this->assertStringContainsString( '<select', $html, 'y se elige, no se teclea' );
	}

	/**
	 * Sin catálogo de centros no se deja teclear: se dice.
	 */
	public function test_without_a_catalogue_the_centre_is_not_typed() {
		list( $evento ) = $this->evento_abierto();
		remove_all_filters( 'evt_centres' );

		$html = $this->pintar( $evento );
		$this->assertStringContainsString( 'No hay catálogo de centros', $html );
		$this->assertStringNotContainsString( 'name="centre"', $html );
	}

	/**
	 * Cerrada, no se pinta ningún formulario.
	 */
	public function test_a_closed_signup_paints_no_form() {
		list( $evento ) = $this->evento_abierto();
		delete_post_meta( $evento, RegistrationMetaKeys::SIGNUP_OPEN );

		$html = $this->pintar( $evento );
		$this->assertStringContainsString( 'no está abierta', $html );
		$this->assertStringNotContainsString( '<form', $html );
	}

	/**
	 * Las preguntas del evento salen con su tipo, y ninguna trae condiciones.
	 */
	public function test_the_questions_of_the_event_are_painted_by_type() {
		list( $evento ) = $this->evento_abierto();
		update_post_meta(
			$evento,
			RegistrationMetaKeys::SIGNUP_QUESTIONS,
			wp_slash(
				(string) wp_json_encode(
					array(
						array(
							'id'       => 'qcomida000001',
							'label'    => '¿Se queda a comer?',
							'type'     => 'check',
							'required' => false,
						),
						array(
							'id'      => 'qturno0000001',
							'label'   => 'Turno',
							'type'    => 'one',
							'options' => array( 'Primero', 'Segundo' ),
						),
					)
				)
			)
		);

		$html = $this->pintar( $evento );
		$this->assertStringContainsString( '¿Se queda a comer?', $html );
		$this->assertStringContainsString( 'type="checkbox"', $html );
		$this->assertStringContainsString( 'type="radio"', $html );
		$this->assertStringContainsString( 'Segundo', $html );
	}

	// ─── lo que hace ───────────────────────────────────────────────────────

	/**
	 * Una persona sin cuenta se inscribe, y sale con su enlace de vuelta.
	 */
	public function test_somebody_without_an_account_signs_up() {
		list( $evento ) = $this->evento_abierto();

		$url = $this->enviar( $evento, $this->datos() );

		$inscripciones = Registrations::all( $evento );
		$this->assertCount( 1, $inscripciones );

		$id   = (int) $inscripciones[0]->ID;
		$meta = Registrations::meta( $id );
		$this->assertSame( 'ana@example.org', $meta[ RegistrationMetaKeys::REG_EMAIL ] );
		$this->assertSame( '3', $meta[ RegistrationMetaKeys::REG_CONSENT_VERSION ], 'se guarda la versión que aceptó' );
		$this->assertNotSame( '', $meta[ RegistrationMetaKeys::REG_CONSENT_AT ], 'y cuándo' );

		// Y vuelve con su testigo en el enlace, no con su correo.
		$this->assertNotNull( $url );
		$this->assertSame(
			$meta[ RegistrationMetaKeys::REG_TOKEN ],
			$this->query_arg( (string) $url, SignupForm::ARG_TOKEN )
		);
	}

	/**
	 * Con un dato mal, no se guarda nada y se dice cuál.
	 */
	public function test_a_bad_field_saves_nothing_and_says_which() {
		list( $evento ) = $this->evento_abierto();

		$url = $this->enviar( $evento, $this->datos( array( 'email' => 'esto-no-es-un-correo' ) ) );

		$this->assertNull( $url, 'no redirige: se queda en la página con el aviso' );
		$this->assertSame( array(), Registrations::all( $evento ) );
		$aviso = SignupForm::notice();
		$this->assertNotNull( $aviso );
		$this->assertStringContainsString( 'correo', $aviso['message'] );
	}

	/**
	 * Sin nonce no entra nada.
	 */
	public function test_without_a_nonce_nothing_is_stored() {
		list( $evento ) = $this->evento_abierto();

		$this->post(
			array_merge(
				array(
					SignupForm::FIELD_OP    => SignupForm::OP_SIGNUP,
					SignupForm::FIELD_EVENT => (string) $evento,
				),
				$this->datos()
			)
		);
		$this->exit_url( array( SignupForm::class, 'maybe_handle_submit' ) );

		$this->assertSame( array(), Registrations::all( $evento ) );
	}

	/**
	 * Cerrada, un envío directo tampoco entra: la comprobación es del servidor.
	 */
	public function test_a_closed_signup_refuses_a_direct_post() {
		list( $evento ) = $this->evento_abierto();
		delete_post_meta( $evento, RegistrationMetaKeys::SIGNUP_OPEN );

		$this->enviar( $evento, $this->datos() );

		$this->assertSame( array(), Registrations::all( $evento ) );
	}

	// ─── el taller ─────────────────────────────────────────────────────────

	/**
	 * Con el plazo de talleres abierto, se elige al inscribirse y se cambia luego.
	 */
	public function test_the_workshop_is_chosen_and_then_changed() {
		list( $evento ) = $this->evento_abierto();
		update_post_meta( $evento, RegistrationMetaKeys::WORKSHOP_OPEN, true );

		$uno = $this->taller( $evento, 'Taller de radio' );
		$dos = $this->taller( $evento, 'Taller de robótica' );

		$this->enviar( $evento, $this->datos( array( 'evt_workshop' => (string) $uno ) ) );

		$id = (int) Registrations::all( $evento )[0]->ID;
		$this->assertSame( $uno, (int) get_post_meta( $id, RegistrationMetaKeys::REG_WORKSHOP, true ) );

		// Y vuelve con su testigo a cambiarlo.
		$token = (string) get_post_meta( $id, RegistrationMetaKeys::REG_TOKEN, true );
		$this->post(
			array(
				SignupForm::FIELD_OP    => SignupForm::OP_WORKSHOP,
				SignupForm::FIELD_EVENT => (string) $evento,
				SignupForm::FIELD_TOKEN => $token,
				'evt_workshop'          => (string) $dos,
			),
			SignupForm::NONCE_ACTION,
			SignupForm::NONCE_FIELD
		);
		$this->exit_url( array( SignupForm::class, 'maybe_handle_submit' ) );

		$this->assertSame( $dos, (int) get_post_meta( $id, RegistrationMetaKeys::REG_WORKSHOP, true ) );
		$this->assertSame( 0, Registrations::taken( $evento, $uno ), 'la plaza vieja se soltó' );
	}

	/**
	 * Sin testigo válido no se le cambia el taller a nadie.
	 */
	public function test_without_a_valid_token_nobody_changes_anybody() {
		list( $evento ) = $this->evento_abierto();
		update_post_meta( $evento, RegistrationMetaKeys::WORKSHOP_OPEN, true );
		$taller = $this->taller( $evento, 'Taller de radio' );

		$this->enviar( $evento, $this->datos() );
		$id = (int) Registrations::all( $evento )[0]->ID;

		$this->post(
			array(
				SignupForm::FIELD_OP    => SignupForm::OP_WORKSHOP,
				SignupForm::FIELD_EVENT => (string) $evento,
				SignupForm::FIELD_TOKEN => 'ana@example.org',
				'evt_workshop'          => (string) $taller,
			),
			SignupForm::NONCE_ACTION,
			SignupForm::NONCE_FIELD
		);
		$this->exit_url( array( SignupForm::class, 'maybe_handle_submit' ) );

		$this->assertSame( 0, (int) get_post_meta( $id, RegistrationMetaKeys::REG_WORKSHOP, true ) );
	}

	/**
	 * Con el plazo cerrado, ni se pinta la elección ni se acepta.
	 */
	public function test_with_the_window_closed_no_workshop_is_chosen() {
		list( $evento ) = $this->evento_abierto();
		$this->taller( $evento, 'Taller de radio' );

		$this->assertFalse( SignupForm::workshops_open( $evento ) );
		$this->assertStringNotContainsString( 'evt_workshop', $this->pintar( $evento ) );
	}

	/**
	 * Un taller del evento, con aforo de sobra.
	 *
	 * @param int    $evento Event ID.
	 * @param string $titulo Title.
	 * @return int
	 */
	private function taller( int $evento, string $titulo ): int {
		return Programme::save_activity(
			$evento,
			0,
			array(
				'title'    => $titulo,
				'kind'     => ProgrammeMetaKeys::KIND_WORKSHOP,
				'date'     => '2026-10-28',
				'start'    => '10:00',
				'end'      => '12:00',
				'venue'    => 'Sede Central',
				'room'     => 'Aula 1',
				'seats'    => 20,
				'summary'  => '',
				'speakers' => array(),
			)
		);
	}
	// ─── la pantalla de quien ya está inscrita ─────────────────────────────

	/**
	 * Con su enlace, ve su inscripción y el taller que puede elegir.
	 */
	public function test_with_their_link_they_see_their_registration() {
		list( $evento ) = $this->evento_abierto();
		update_post_meta( $evento, RegistrationMetaKeys::WORKSHOP_OPEN, true );
		$taller = $this->taller( $evento, 'Taller de radio escolar' );

		$this->enviar( $evento, $this->datos() );
		$id    = (int) Registrations::all( $evento )[0]->ID;
		$token = (string) get_post_meta( $id, RegistrationMetaKeys::REG_TOKEN, true );

		$_GET[ SignupForm::ARG_TOKEN ] = $token;
		$html                          = $this->pintar( $evento );
		unset( $_GET[ SignupForm::ARG_TOKEN ] );

		$this->assertStringContainsString( 'Su inscripción está registrada', $html );
		$this->assertStringContainsString( 'Ana Martín Cabrera', $html );
		$this->assertStringContainsString( 'Guardar el taller', $html );
		$this->assertStringContainsString( 'Taller de radio escolar', $html );
		$this->assertStringContainsString( SignupForm::OP_WORKSHOP, $html );
		// Y no se le vuelve a pedir el documento: ya está inscrita.
		$this->assertStringNotContainsString( 'name="tax_id"', $html );
		unset( $taller );
	}

	/**
	 * Con el plazo de talleres cerrado, se lo dice y no pinta formulario.
	 */
	public function test_with_the_window_closed_they_are_told_so() {
		list( $evento ) = $this->evento_abierto();
		$this->enviar( $evento, $this->datos() );
		$id = (int) Registrations::all( $evento )[0]->ID;

		$_GET[ SignupForm::ARG_TOKEN ] = (string) get_post_meta( $id, RegistrationMetaKeys::REG_TOKEN, true );
		$html                          = $this->pintar( $evento );
		unset( $_GET[ SignupForm::ARG_TOKEN ] );

		$this->assertStringContainsString( 'Su inscripción está registrada', $html );
		$this->assertStringContainsString( 'no está abierto', $html );
		$this->assertStringNotContainsString( 'Guardar el taller', $html );
	}

	/**
	 * Los dos textos del consentimiento salen para leerlos ahí mismo.
	 */
	public function test_the_two_consent_texts_are_there_to_be_read() {
		list( $evento ) = $this->evento_abierto();
		update_post_meta( $evento, RegistrationMetaKeys::CONSENT_PRIVACY, '<p>Quién trata sus datos.</p>' );
		update_post_meta( $evento, RegistrationMetaKeys::CONSENT_IMAGE, '<p>Grabación de las sesiones.</p>' );

		$html = $this->pintar( $evento );

		$this->assertStringContainsString( 'Información sobre el tratamiento de sus datos', $html );
		$this->assertStringContainsString( 'Consentimiento informado', $html );
		$this->assertStringContainsString( 'Quién trata sus datos.', $html );
		$this->assertStringContainsString( 'Grabación de las sesiones.', $html );
		// Se leen sin salir de la inscripción.
		$this->assertStringContainsString( '<details', $html );
	}

	/**
	 * Y sin plazas libres en ningún taller, se dice en vez de dejar la lista sola.
	 */
	public function test_when_no_workshop_has_room_it_says_so() {
		list( $evento ) = $this->evento_abierto();
		update_post_meta( $evento, RegistrationMetaKeys::WORKSHOP_OPEN, true );

		$taller = Programme::save_activity(
			$evento,
			0,
			array(
				'title'    => 'Taller lleno',
				'kind'     => ProgrammeMetaKeys::KIND_WORKSHOP,
				'date'     => '2026-10-28',
				'start'    => '10:00',
				'end'      => '12:00',
				'venue'    => 'Sede Central',
				'room'     => 'Aula 1',
				'seats'    => 1,
				'summary'  => '',
				'speakers' => array(),
			)
		);
		$this->enviar( $evento, $this->datos( array( 'evt_workshop' => (string) $taller ) ) );

		$html = $this->pintar( $evento );
		$this->assertStringContainsString( 'no queda ningún taller con plazas libres', $html );
	}
}
