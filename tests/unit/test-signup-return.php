<?php
/**
 * Tests for coming back to a registration: the email, the session, and several workshops.
 *
 * @package Evt
 */

use Evt\Meta\EventMetaKeys;
use Evt\Meta\ProgrammeMetaKeys;
use Evt\Meta\RegistrationMetaKeys;
use Evt\PublicFront\Block\SignupBlock;
use Evt\PublicFront\Captcha;
use Evt\PublicFront\Participants;
use Evt\PublicFront\Programme;
use Evt\PublicFront\Registrations;
use Evt\PublicFront\SignupForm;
use Evt\PublicFront\SignupMail;

/**
 * Volver a la propia inscripción y elegir varios talleres (ADR-0050, ADR-0051).
 *
 * - Un documento, una inscripción por evento.
 * - El correo de confirmación lleva el enlace personal; si se pierde, se pide
 *   otra vez con el correo, y la respuesta no dice si existe.
 * - Con sesión, la cuenta encuentra su inscripción sin enlace.
 * - Un taller que se repite son dos sesiones con el mismo título, y nadie
 *   entra en las dos.
 */
class Test_Signup_Return extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Lo que se habría enviado por correo.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private $correos = array();

	/**
	 * Qué contesta el servidor de correo de mentira.
	 *
	 * @var bool
	 */
	private $servidor = true;

	/**
	 * Sin nadie dentro, y el correo capturado.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		$this->acting_as( 0 );
		$this->correos  = array();
		$this->servidor = true;
		add_filter( 'pre_wp_mail', array( $this, 'capturar' ), 10, 2 );
	}

	/**
	 * Sin filtros de otro test.
	 */
	public function tear_down() {
		remove_all_filters( 'evt_centres' );
		remove_all_filters( 'pre_wp_mail' );
		remove_all_filters( SignupMail::HOOK );
		parent::tear_down();
	}

	/**
	 * Captura un correo en vez de mandarlo.
	 *
	 * @param mixed                $nulo  Short-circuit value.
	 * @param array<string, mixed> $atts  What wp_mail got.
	 * @return bool
	 */
	public function capturar( $nulo, array $atts ): bool {
		unset( $nulo );
		$this->correos[] = $atts;
		return $this->servidor;
	}

	/**
	 * Un evento abierto, con su sección y dos talleres por persona.
	 *
	 * @return int
	 */
	private function evento(): int {
		$evento = $this->event(
			$this->administrator(),
			array( $this->area( 'Innovación' ) ),
			array(),
			array( 'post_title' => 'Jornadas del primer ciclo' )
		);
		update_post_meta( $evento, RegistrationMetaKeys::SIGNUP_OPEN, true );
		update_post_meta( $evento, RegistrationMetaKeys::SIGNUP_PUBLIC, true );
		update_post_meta( $evento, RegistrationMetaKeys::WORKSHOP_OPEN, true );
		update_post_meta( $evento, RegistrationMetaKeys::WORKSHOP_MAX, 2 );
		$seccion = $this->event_page(
			$evento,
			'inscripcion',
			array(
				'post_title' => 'Inscripción',
				'post_name'  => 'inscripcion',
			)
		);
		update_post_meta( $seccion, EventMetaKeys::SECTION_TYPE, 'inscripcion' );
		add_filter(
			'evt_centres',
			static function (): array {
				return array(
					'38000001' => 'CEIP El Molino',
					'38000002' => 'IES El Mirador',
				);
			}
		);
		return $evento;
	}

	/**
	 * Un taller.
	 *
	 * @param int    $evento Event ID.
	 * @param string $titulo Title.
	 * @param string $inicio Start.
	 * @param int    $aforo  Seats.
	 * @return int
	 */
	private function taller( int $evento, string $titulo, string $inicio, int $aforo = 15 ): int {
		$fin = gmdate( 'H:i', (int) strtotime( '2027-02-06 ' . $inicio ) + 75 * 60 );
		return Programme::save_activity(
			$evento,
			0,
			array(
				'title'    => $titulo,
				'kind'     => ProgrammeMetaKeys::KIND_WORKSHOP,
				'date'     => '2027-02-06',
				'start'    => $inicio,
				'end'      => $fin,
				'venue'    => 'Centro de formación Norte',
				'room'     => '',
				'seats'    => $aforo,
				'summary'  => '',
				'speakers' => array(),
			)
		);
	}

	/**
	 * Un envío del formulario público.
	 *
	 * @param int                  $evento Event ID.
	 * @param array<string, mixed> $campos Fields.
	 * @param string               $op     Operation.
	 * @return string|null
	 */
	private function enviar( int $evento, array $campos, string $op = SignupForm::OP_SIGNUP ): ?string {
		$this->post(
			array_merge(
				array(
					SignupForm::FIELD_OP    => $op,
					SignupForm::FIELD_EVENT => (string) $evento,
					Captcha::FIELD          => $this->altcha_payload(),
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
				'phone'   => '',
				'centre'  => '38000001',
				'consent' => '1',
			),
			$cambios
		);
	}

	/**
	 * El bloque de la sección de inscripción.
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

	// ─── varios talleres ───────────────────────────────────────────────────

	/**
	 * Dos talleres en dos franjas se guardan juntos, y la lista los enseña.
	 */
	public function test_two_workshops_in_two_slots_are_kept() {
		$evento  = $this->evento();
		$primero = $this->taller( $evento, 'Primeros auxilios', '15:00' );
		$musica  = $this->taller( $evento, 'Música', '16:30' );

		$this->enviar( $evento, $this->datos( array( SignupForm::FIELD_WORKSHOP => array( (string) $primero, (string) $musica ) ) ) );
		$id = (int) Registrations::all( $evento )[0]->ID;

		$this->assertSame( array( $primero, $musica ), Registrations::workshops_of( $id ) );
		$this->assertSame( 1, Registrations::taken( $evento, $primero ) );
		$this->assertSame( 1, Registrations::taken( $evento, $musica ) );

		$fila = Participants::rows( $evento )[0];
		$this->assertSame( 'Primeros auxilios' . Participants::SEPARATOR . 'Música', $fila['workshop'] );
		$this->assertCount( 1, Participants::filter( Participants::rows( $evento ), '', 'Música' ), 'se filtra por cualquiera de los dos' );
		$this->assertSame( array( 'Música', 'Primeros auxilios' ), Participants::workshops( Participants::rows( $evento ) ) );
	}

	/**
	 * Las dos sesiones del mismo taller no se eligen juntas, y no se crea nada.
	 */
	public function test_two_sessions_of_one_workshop_are_refused_before_signing_up() {
		$evento = $this->evento();
		$tres   = $this->taller( $evento, 'Primeros auxilios', '15:00' );
		$cuatro = $this->taller( $evento, 'Primeros auxilios', '16:30' );

		$this->enviar( $evento, $this->datos( array( SignupForm::FIELD_WORKSHOP => array( (string) $tres, (string) $cuatro ) ) ) );

		$this->assertSame( array(), Registrations::all( $evento ), 'no se crea una inscripción a medias' );
		$this->assertStringContainsString( 'mismo taller', (string) SignupForm::notice()['message'] );
	}

	/**
	 * Las dos sesiones salen en las listas con su hora, para distinguirlas.
	 */
	public function test_repeated_sessions_are_labelled_with_their_time() {
		$evento = $this->evento();
		$tres   = $this->taller( $evento, 'Primeros auxilios', '15:00' );
		$cuatro = $this->taller( $evento, 'Primeros auxilios', '16:30' );
		$musica = $this->taller( $evento, 'Música', '16:30' );

		$rotulos = Registrations::workshop_labels( $evento );
		$this->assertStringContainsString( '15:00', $rotulos[ $tres ] );
		$this->assertStringContainsString( '16:30', $rotulos[ $cuatro ] );
		$this->assertSame( 'Música', $rotulos[ $musica ], 'el que no se repite, con su título y nada más' );
	}

	/**
	 * Con varios, el formulario pinta casillas y las instrucciones.
	 */
	public function test_the_form_paints_checkboxes_and_the_instructions() {
		$evento = $this->evento();
		update_post_meta( $evento, RegistrationMetaKeys::WORKSHOP_HELP, 'Elija uno de mañana y otro de tarde.' );
		$this->taller( $evento, 'Primeros auxilios', '15:00' );

		$html = $this->pintar( $evento );
		$this->assertStringContainsString( 'type="checkbox" id="evt-taller-', $html );
		$this->assertStringContainsString( 'name="evt_workshop[]"', $html );
		$this->assertStringContainsString( 'hasta 2 talleres', $html );
		$this->assertStringContainsString( 'Elija uno de mañana y otro de tarde.', $html );
		$this->assertStringContainsString( '15:00', $html, 'cada taller con su hora' );
	}

	/**
	 * Lo guardado antes de poder elegir varios —un número— se sigue leyendo.
	 */
	public function test_a_single_stored_workshop_still_counts() {
		$evento = $this->evento();
		$taller = $this->taller( $evento, 'Música', '16:30' );
		$this->enviar( $evento, $this->datos() );
		$id = (int) Registrations::all( $evento )[0]->ID;
		update_post_meta( $id, RegistrationMetaKeys::REG_WORKSHOP, $taller );

		$this->assertSame( array( $taller ), Registrations::workshops_of( $id ) );
		$this->assertSame( 1, Registrations::taken( $evento, $taller ) );
	}

	// ─── un documento, una inscripción ─────────────────────────────────────

	/**
	 * El mismo documento no se inscribe dos veces en el mismo evento.
	 */
	public function test_the_same_tax_id_cannot_sign_up_twice() {
		$evento = $this->evento();
		$this->enviar( $evento, $this->datos() );
		$this->enviar( $evento, $this->datos( array( 'email' => 'otra@example.org' ) ) );

		$this->assertCount( 1, Registrations::all( $evento ) );
		$this->assertStringContainsString( 'documento de identidad', (string) SignupForm::notice()['message'] );
		$this->assertSame( 'repetida', Registrations::add( $evento, array( 'tax_id' => '12345678z' ), array() )['error'], 'da igual cómo se escriba' );
	}

	// ─── el correo ─────────────────────────────────────────────────────────

	/**
	 * Al inscribirse sale un correo en texto plano con el enlace personal y los talleres.
	 */
	public function test_signing_up_sends_the_personal_link() {
		$evento = $this->evento();
		$taller = $this->taller( $evento, 'Música', '16:30' );

		$vuelta = $this->enviar( $evento, $this->datos( array( SignupForm::FIELD_WORKSHOP => array( (string) $taller ) ) ) );
		$id     = (int) Registrations::all( $evento )[0]->ID;
		$token  = (string) get_post_meta( $id, RegistrationMetaKeys::REG_TOKEN, true );

		$this->assertCount( 1, $this->correos );
		$correo = $this->correos[0];
		$this->assertSame( 'ana@example.org', $correo['to'] );
		$this->assertStringContainsString( 'Jornadas del primer ciclo', $correo['subject'] );
		$this->assertStringContainsString( $token, $correo['message'], 'el enlace lleva el testigo' );
		$this->assertStringContainsString( 'Taller: Música', $correo['message'] );
		$this->assertContains( 'Content-Type: text/plain; charset=UTF-8', (array) $correo['headers'] );
		$this->assertSame( '', $this->query_arg( (string) $vuelta, SignupForm::ARG_MAIL ), 'salió bien: no se avisa de nada' );
	}

	/**
	 * Si el correo no sale, la inscripción se queda y se avisa de guardar el enlace.
	 */
	public function test_a_failed_email_keeps_the_signup_and_says_so() {
		$evento         = $this->evento();
		$this->servidor = false;

		$vuelta = $this->enviar( $evento, $this->datos() );
		$this->assertCount( 1, Registrations::all( $evento ) );
		$this->assertSame( '1', $this->query_arg( (string) $vuelta, SignupForm::ARG_MAIL ) );

		$_GET[ SignupForm::ARG_TOKEN ] = $this->query_arg( (string) $vuelta, SignupForm::ARG_TOKEN );
		$_GET[ SignupForm::ARG_MAIL ]  = '1';
		$this->assertStringContainsString( 'Guarde la dirección de esta página', $this->pintar( $evento ) );
	}

	/**
	 * El filtro `evt_mail` puede no mandar nada.
	 */
	public function test_the_filter_can_drop_the_email() {
		$evento = $this->evento();
		add_filter( SignupMail::HOOK, '__return_false' );
		$this->enviar( $evento, $this->datos() );
		$this->assertSame( array(), $this->correos );
	}

	/**
	 * Recuperar el enlace: solo a la dirección inscrita, la misma respuesta siempre, y no a cada clic.
	 */
	public function test_resending_the_link_says_the_same_and_waits() {
		$evento = $this->evento();
		$this->enviar( $evento, $this->datos() );
		$this->correos = array();

		$this->enviar( $evento, array( 'email' => 'nadie@example.org' ), SignupForm::OP_RESEND );
		$nadie = (string) SignupForm::notice()['message'];
		$this->assertSame( array(), $this->correos, 'a un correo no inscrito no se manda nada' );

		$this->enviar( $evento, array( 'email' => 'ANA@example.org' ), SignupForm::OP_RESEND );
		$this->assertSame( $nadie, (string) SignupForm::notice()['message'], 'la respuesta no dice quién está inscrito' );
		$this->assertCount( 1, $this->correos );
		$this->assertSame( 'ana@example.org', $this->correos[0]['to'] );
		$this->assertStringContainsString( 'de nuevo el enlace', $this->correos[0]['message'] );

		$this->enviar( $evento, array( 'email' => 'ana@example.org' ), SignupForm::OP_RESEND );
		$this->assertCount( 1, $this->correos, 'y no se vuelve a mandar enseguida' );
	}

	// ─── con sesión ────────────────────────────────────────────────────────

	/**
	 * Con sesión, la cuenta encuentra su inscripción sin enlace, y puede cambiar de taller.
	 */
	public function test_with_a_session_the_account_finds_its_registration() {
		$evento = $this->evento();
		$taller = $this->taller( $evento, 'Música', '16:30' );
		$uid    = self::factory()->user->create( array( 'user_email' => 'luis@example.org' ) );
		$this->acting_as( $uid );

		$this->enviar( $evento, $this->datos( array( 'email' => 'luis@example.org' ) ) );
		$id = (int) Registrations::all( $evento )[0]->ID;
		$this->assertSame( $uid, (int) get_post_meta( $id, RegistrationMetaKeys::REG_USER, true ) );
		$this->assertSame( $id, SignupForm::current( $evento ) );
		$this->assertStringContainsString( 'Su inscripción está registrada', $this->pintar( $evento ) );

		$this->enviar( $evento, array( SignupForm::FIELD_WORKSHOP => array( (string) $taller ) ), SignupForm::OP_WORKSHOP );
		$this->assertSame( array( $taller ), Registrations::workshops_of( $id ), 'sin testigo: la cuenta dice quién es' );
	}

	/**
	 * Una inscripción hecha sin sesión —o por la organización— la encuentra la cuenta con ese correo.
	 */
	public function test_an_account_finds_a_registration_by_its_email() {
		$evento = $this->evento();
		$this->enviar( $evento, $this->datos() );
		$id = (int) Registrations::all( $evento )[0]->ID;

		$suya = self::factory()->user->create( array( 'user_email' => 'Ana@Example.org' ) );
		$otra = self::factory()->user->create( array( 'user_email' => 'otra@example.org' ) );
		$this->assertSame( $id, Registrations::of_user( $evento, $suya ) );
		$this->assertSame( 0, Registrations::of_user( $evento, $otra ) );
	}
}
