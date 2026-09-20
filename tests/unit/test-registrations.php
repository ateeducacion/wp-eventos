<?php
/**
 * Tests for the signup form, its storage and the workshop seats.
 *
 * @package Evt
 */

use Evt\Domain\RegistrationInput;
use Evt\Domain\SignupQuestions;
use Evt\Meta\ProgrammeMetaKeys;
use Evt\Meta\RegistrationMetaKeys;
use Evt\PostType\RegistrationPostType;
use Evt\PublicFront\Participants;
use Evt\PublicFront\Programme;
use Evt\PublicFront\Registrations;

/**
 * La inscripción, de punta a punta.
 *
 * Tres cosas que se comprueban aquí y no en otra parte:
 *
 * 1. El **núcleo** se valida sin WordPress (ADR-0031): es la parte medida, la
 *    que se escribe una vez en vez de repetirse en el formulario de cada evento.
 * 2. Una respuesta sigue **unida a su pregunta** aunque se reescriba el rótulo,
 *    que es lo que la ADR-0032 cerró guardándolas por identificador.
 * 3. El **aforo es duro** y el cambio de taller es **todo o nada** (ADR-0033).
 *    Es lo único del aplicativo con una carrera dentro.
 */
class Test_Registrations extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Set up test environment before each test.
	 *
	 * @return void
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
	}

	/**
	 * Clean up test environment after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		remove_all_filters( 'evt_centres' );
		parent::tear_down();
	}

	/**
	 * Un evento con el aplicativo arrancado.
	 *
	 * @return int
	 */
	private function un_evento(): int {
		$this->app();
		return $this->event(
			$this->administrator(),
			array( $this->area( 'Innovación' ) ),
			array(),
			array( 'post_title' => 'Jornadas de prueba' )
		);
	}

	/**
	 * Un taller del evento, con su aforo.
	 *
	 * @param int    $evento Event ID.
	 * @param int    $aforo  Seats, 0 for no limit.
	 * @param string $titulo Title.
	 * @return int
	 */
	private function taller( int $evento, int $aforo, string $titulo = 'Taller de radio' ): int {
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
				'seats'    => $aforo,
				'summary'  => '',
				'speakers' => array(),
			)
		);
	}

	/**
	 * Los campos que pasan la validación del núcleo.
	 *
	 * @param array<string, mixed> $cambios What to override.
	 * @return array<string, mixed>
	 */
	private function nucleo( array $cambios = array() ): array {
		return array_merge(
			array(
				'tax_id'  => '12345678Z',
				'name'    => 'Ana',
				'surname' => 'Martín Cabrera',
				'email'   => 'ana@example.org',
				'phone'   => '600 000 000',
				'centre'  => '38000001',
				'consent' => '1',
			),
			$cambios
		);
	}

	/**
	 * Una inscripción guardada.
	 *
	 * @param int                  $evento  Event ID.
	 * @param array<string, mixed> $cambios Core overrides.
	 * @param array<string, mixed> $respuestas Answers.
	 * @return int
	 */
	private function inscribir( int $evento, array $cambios = array(), array $respuestas = array() ): int {
		$v = Registrations::validate( $evento, $this->nucleo( $cambios ), $respuestas );
		$this->assertTrue( $v['ok'], 'la inscripción de prueba tenía que validar: ' . implode( ', ', $v['errors'] ) );
		return Registrations::create( $evento, $v['core'], $v['answers'] );
	}

	// ─── el núcleo, sin WordPress ──────────────────────────────────────────

	/**
	 * Los seis obligatorios son obligatorios, y el teléfono no.
	 */
	public function test_the_core_demands_the_six_that_are_required() {
		$vacio = RegistrationInput::core( array() );
		$this->assertFalse( $vacio['ok'] );
		foreach ( array( 'tax_id', 'name', 'surname', 'email', 'centre', 'consent' ) as $campo ) {
			$this->assertContains( $campo, $vacio['errors'], $campo . ' tenía que ser obligatorio' );
		}

		$catalogo     = array( '38000001' => 'CEIP El Molino' );
		$sin_telefono = RegistrationInput::core( $this->nucleo( array( 'phone' => '' ) ), $catalogo );
		$this->assertTrue( $sin_telefono['ok'], 'el teléfono no se exige: exigirlo fabrica teléfonos falsos' );
	}

	/**
	 * El documento se normaliza para que la misma persona no entre dos veces.
	 */
	public function test_the_tax_id_is_normalised_so_one_person_is_one_person() {
		$this->assertSame( '12345678Z', RegistrationInput::tax_id( ' 12.345.678-z ' ) );
		$this->assertSame( '12345678Z', RegistrationInput::tax_id( '12345678z' ) );

		// La forma, no el dígito de control: aquí se inscribe gente con
		// documento de otro país y un validador nacional la dejaría fuera.
		$this->assertTrue( RegistrationInput::is_tax_id( 'X1234567L' ) );
		$this->assertTrue( RegistrationInput::is_tax_id( 'AB123456' ) );
		$this->assertFalse( RegistrationInput::is_tax_id( 'NO' ) );
	}

	/**
	 * El centro se elige del catálogo y nunca se teclea (ADR-0031).
	 */
	public function test_the_centre_has_to_be_one_of_the_catalogue() {
		$catalogo = array(
			'38000001' => 'CEIP El Molino',
			'38000002' => 'IES El Mirador',
		);

		$bueno = RegistrationInput::core( $this->nucleo(), $catalogo );
		$this->assertTrue( $bueno['ok'] );

		$tecleado = RegistrationInput::core( $this->nucleo( array( 'centre' => 'CEIP el molino' ) ), $catalogo );
		$this->assertFalse( $tecleado['ok'], 'un centro tecleado es el mismo centro escrito de cinco maneras' );
		$this->assertContains( 'centre', $tecleado['errors'] );
	}

	/**
	 * Sin consentimiento no hay inscripción, y la comprobación es del servidor.
	 */
	public function test_without_consent_there_is_no_signup() {
		$catalogo = array( '38000001' => 'CEIP El Molino' );
		$sin      = RegistrationInput::core( $this->nucleo( array( 'consent' => '' ) ), $catalogo );
		$this->assertFalse( $sin['ok'] );
		$this->assertContains( 'consent', $sin['errors'] );
		$this->assertStringContainsString( 'tratamiento de datos', RegistrationInput::why( $sin['errors'] ) );
	}

	// ─── las preguntas del evento ──────────────────────────────────────────

	/**
	 * Una pregunta tiene cuatro cosas y ninguna más, y el tipo es cerrado.
	 */
	public function test_a_question_has_four_things_and_a_closed_type() {
		$leidas = SignupQuestions::read(
			array(
				array(
					'id'        => 'qabcdef123456',
					'label'     => '¿Se queda a comer?',
					'type'      => 'check',
					'required'  => true,
					'condition' => 'algo',
					'pattern'   => '/x/',
				),
				array(
					'label' => 'Inventada',
					'type'  => 'firma-digital',
				),
			)
		);

		$this->assertCount( 2, $leidas );
		$this->assertSame( array( 'id', 'label', 'type', 'options', 'required' ), array_keys( $leidas[0] ) );
		$this->assertArrayNotHasKey( 'condition', $leidas[0], 'no hay lógica condicional' );
		$this->assertArrayNotHasKey( 'pattern', $leidas[0], 'no hay reglas de validación propias' );
		$this->assertSame( 'text', $leidas[1]['type'], 'un tipo que no está en la lista cae a texto corto' );
	}

	/**
	 * El tipo es toda la validación que hay.
	 */
	public function test_the_type_is_all_the_validation_there_is() {
		$preguntas = SignupQuestions::read(
			array(
				array(
					'id'       => 'qcomida000001',
					'label'    => 'Turno de comida',
					'type'     => 'one',
					'options'  => array( 'Primero', 'Segundo' ),
					'required' => true,
				),
				array(
					'id'      => 'qalergia00001',
					'label'   => 'Alergias',
					'type'    => 'many',
					'options' => array( 'Gluten', 'Lactosa' ),
				),
			)
		);

		$fuera = SignupQuestions::answers(
			$preguntas,
			array(
				'qcomida000001' => 'Tercero',
				'qalergia00001' => array( 'Gluten', 'Marisco', 'Gluten' ),
			)
		);

		$this->assertFalse( $fuera['ok'], 'una opción que no está en la lista no vale' );
		$this->assertContains( 'qcomida000001', $fuera['errors'] );
		$this->assertSame( array( 'Gluten' ), $fuera['data']['qalergia00001'], 'lo que no está en la lista se cae, y no se repite' );
	}

	/**
	 * Con gente inscrita se puede reescribir el rótulo y añadir opciones,
	 * pero no cambiar el tipo ni quitar una opción ya elegida (ADR-0032).
	 */
	public function test_a_stored_answer_keeps_meaning_the_same() {
		$antes = SignupQuestions::read(
			array(
				array(
					'id'      => 'qcomida000001',
					'label'   => 'Turno',
					'type'    => 'one',
					'options' => array( 'Primero', 'Segundo' ),
				),
			)
		);

		$rotulo = SignupQuestions::read(
			array(
				array(
					'id'      => 'qcomida000001',
					'label'   => 'Turno de comida',
					'type'    => 'one',
					'options' => array( 'Primero', 'Segundo', 'Tercero' ),
				),
			)
		);
		$this->assertSame( array(), SignupQuestions::refuse( $antes, $rotulo, true ), 'reescribir el rótulo y añadir opciones sí' );

		$tipo = SignupQuestions::read(
			array(
				array(
					'id'    => 'qcomida000001',
					'label' => 'Turno',
					'type'  => 'text',
				),
			)
		);
		$this->assertContains( 'type_changed:qcomida000001', SignupQuestions::refuse( $antes, $tipo, true ) );

		$menos = SignupQuestions::read(
			array(
				array(
					'id'      => 'qcomida000001',
					'label'   => 'Turno',
					'type'    => 'one',
					'options' => array( 'Primero' ),
				),
			)
		);
		$this->assertContains( 'option_removed:qcomida000001', SignupQuestions::refuse( $antes, $menos, true ) );

		// Sin nadie inscrito, la lista se toca entera.
		$this->assertSame( array(), SignupQuestions::refuse( $antes, $menos, false ) );
	}

	// ─── el almacén ────────────────────────────────────────────────────────

	/**
	 * La inscripción cuelga de su evento y no se enseña en ningún sitio.
	 */
	public function test_a_registration_hangs_from_its_event_and_opens_no_door() {
		$evento = $this->un_evento();
		$id     = $this->inscribir( $evento );

		$this->assertGreaterThan( 0, $id );
		$this->assertSame( $evento, (int) get_post_field( 'post_parent', $id ) );

		$tipo = get_post_type_object( RegistrationPostType::POST_TYPE );
		$this->assertFalse( $tipo->public, 'sin URL propia' );
		$this->assertFalse( $tipo->show_ui, 'sin escritorio' );
		$this->assertFalse( $tipo->show_in_rest, 'sin REST' );
		$this->assertTrue( $tipo->exclude_from_search, 'fuera de la búsqueda' );

		// El título es la referencia, no el nombre: quien mire `wp_posts` a
		// pelo ve referencias y no personas.
		$titulo = (string) get_post_field( 'post_title', $id );
		$this->assertStringStartsWith( 'INS-' . $evento . '-', $titulo );
		$this->assertStringNotContainsString( 'Martín', $titulo );
	}

	/**
	 * Las metas de una inscripción no las escribe nadie desde fuera.
	 */
	public function test_nobody_writes_a_registration_meta_from_outside() {
		$evento = $this->un_evento();
		$id     = $this->inscribir( $evento );
		$this->acting_as( $this->administrator() );

		foreach ( array( RegistrationMetaKeys::REG_TAX_ID, RegistrationMetaKeys::REG_EMAIL ) as $clave ) {
			$this->assertFalse(
				current_user_can( 'edit_post_meta', $id, $clave ),
				$clave . ' tenía que estar cerrada incluso para administración: la escribe el aplicativo'
			);
		}
	}

	/**
	 * El aplicativo contesta su propio filtro con la forma de fila de ADR-0027.
	 */
	public function test_the_application_answers_its_own_participants_filter() {
		$evento = $this->un_evento();
		$taller = $this->taller( $evento, 20 );
		$id     = $this->inscribir(
			$evento,
			array(),
			array()
		);
		Registrations::seat( $evento, $id, $taller );

		$filas = Participants::rows( $evento );
		$this->assertCount( 1, $filas );
		$this->assertSame( 'Ana Martín Cabrera', $filas[0]['name'] );
		$this->assertSame( 'ana@example.org', $filas[0]['email'] );
		$this->assertSame( 'Taller de radio', $filas[0]['workshop'] );
		$this->assertStringContainsString( 'Sí', $filas[0]['consent'] );
	}

	/**
	 * El testigo abre una inscripción y solo esa.
	 */
	public function test_a_token_opens_one_registration_and_only_that_one() {
		$evento = $this->un_evento();
		$una    = $this->inscribir( $evento );
		$otra   = $this->inscribir( $evento, array( 'email' => 'luis@example.org' ) );

		$suyo = (string) get_post_meta( $una, RegistrationMetaKeys::REG_TOKEN, true );
		$this->assertTrue( Registrations::is_token( $suyo ) );
		$this->assertSame( $una, Registrations::by_token( $evento, $suyo ) );
		$this->assertNotSame( $otra, Registrations::by_token( $evento, $suyo ) );

		// Un correo tecleado no es una credencial.
		$this->assertSame( 0, Registrations::by_token( $evento, 'ana@example.org' ) );
		$this->assertSame( 0, Registrations::by_token( $evento, str_repeat( 'a', 40 ) ) );
	}

	// ─── el aforo ──────────────────────────────────────────────────────────

	/**
	 * El aforo es duro: la plaza veintiuna no entra.
	 */
	public function test_a_full_workshop_takes_nobody_else() {
		$evento = $this->un_evento();
		$taller = $this->taller( $evento, 2 );

		$a = $this->inscribir( $evento, array( 'email' => 'a@example.org' ) );
		$b = $this->inscribir( $evento, array( 'email' => 'b@example.org' ) );
		$c = $this->inscribir( $evento, array( 'email' => 'c@example.org' ) );

		$this->assertTrue( Registrations::seat( $evento, $a, $taller )['ok'] );
		$this->assertTrue( Registrations::seat( $evento, $b, $taller )['ok'] );

		$tarde = Registrations::seat( $evento, $c, $taller );
		$this->assertFalse( $tarde['ok'] );
		$this->assertSame( 'sin_plazas', $tarde['error'] );
		$this->assertSame( 0, (int) get_post_meta( $c, RegistrationMetaKeys::REG_WORKSHOP, true ), 'y no se escribe nada' );

		// Un aforo de cero es «sin límite», que es lo que ya hacía la pantalla.
		$libre = $this->taller( $evento, 0, 'Taller sin aforo' );
		$this->assertTrue( Registrations::seat( $evento, $c, $libre )['ok'] );
	}

	/**
	 * Un taller lleno desaparece de lo elegible, pero no echa a quien está.
	 */
	public function test_a_full_workshop_disappears_but_keeps_its_people() {
		$evento = $this->un_evento();
		$taller = $this->taller( $evento, 1 );
		$otro   = $this->taller( $evento, 5, 'Taller de robótica' );

		$a = $this->inscribir( $evento, array( 'email' => 'a@example.org' ) );
		$b = $this->inscribir( $evento, array( 'email' => 'b@example.org' ) );
		Registrations::seat( $evento, $a, $taller );

		$para_b = wp_list_pluck( Registrations::choices( $evento, $b ), 'id' );
		$this->assertNotContains( $taller, $para_b, 'el lleno no sale' );
		$this->assertContains( $otro, $para_b );

		$para_a = wp_list_pluck( Registrations::choices( $evento, $a ), 'id' );
		$this->assertContains( $taller, $para_a, 'llenarse no echa a quien ya lo tenía' );
	}

	/**
	 * Cambiar de taller es todo o nada: nadie se queda sin ninguno.
	 */
	public function test_changing_workshop_is_all_or_nothing() {
		$evento = $this->un_evento();
		$suyo   = $this->taller( $evento, 5 );
		$lleno  = $this->taller( $evento, 1, 'Taller lleno' );

		$a = $this->inscribir( $evento, array( 'email' => 'a@example.org' ) );
		$b = $this->inscribir( $evento, array( 'email' => 'b@example.org' ) );

		Registrations::seat( $evento, $a, $suyo );
		Registrations::seat( $evento, $b, $lleno );

		// A intenta cambiarse al que ya está lleno.
		$fallido = Registrations::seat( $evento, $a, $lleno );
		$this->assertFalse( $fallido['ok'] );
		$this->assertSame(
			$suyo,
			(int) get_post_meta( $a, RegistrationMetaKeys::REG_WORKSHOP, true ),
			'si el cambio no cabe, se conserva el que había: no existe el instante sin ninguno'
		);

		// Y un cambio que sí cabe libera la plaza anterior.
		$tercero = $this->taller( $evento, 5, 'Taller de radio escolar' );
		$this->assertTrue( Registrations::seat( $evento, $a, $tercero )['ok'] );
		$this->assertSame( 0, Registrations::taken( $evento, $suyo ), 'la plaza vieja queda libre' );
		$this->assertSame( 1, Registrations::taken( $evento, $tercero ) );
	}

	/**
	 * No se coge plaza en un taller de otro evento.
	 */
	public function test_nobody_takes_a_seat_in_another_event() {
		$evento = $this->un_evento();
		$ajeno  = $this->event( $this->administrator(), array( $this->area( 'Salud' ) ) );
		$taller = $this->taller( $ajeno, 10 );

		$id  = $this->inscribir( $evento );
		$res = Registrations::seat( $evento, $id, $taller );

		$this->assertFalse( $res['ok'] );
		$this->assertSame( 'no_es_un_taller_del_evento', $res['error'] );
	}

	// ─── el candado ────────────────────────────────────────────────────────

	/**
	 * El candado lo tiene uno solo, y el segundo no pasa.
	 *
	 * Es la pieza que impide la carrera: `add_option()` falla si el nombre ya
	 * existe, porque la columna es única en la base de datos (ADR-0033).
	 */
	public function test_only_one_holds_the_lock() {
		$evento = $this->un_evento();

		$this->assertTrue( Registrations::lock( $evento ) );
		$this->assertFalse( Registrations::lock( $evento ), 'el segundo se queda fuera' );

		Registrations::unlock( $evento );
		$this->assertTrue( Registrations::lock( $evento ), 'soltado, vuelve a estar libre' );
		Registrations::unlock( $evento );
	}

	/**
	 * Y con el candado cogido, coger plaza no espera para siempre: avisa.
	 */
	public function test_a_held_lock_refuses_instead_of_hanging() {
		$evento = $this->un_evento();
		$taller = $this->taller( $evento, 10 );
		$id     = $this->inscribir( $evento );

		Registrations::lock( $evento );
		$res = Registrations::seat( $evento, $id, $taller );
		Registrations::unlock( $evento );

		$this->assertFalse( $res['ok'] );
		$this->assertSame( 'ocupado', $res['error'] );
	}

	/**
	 * Un candado de una petición que murió caduca y no bloquea el evento.
	 */
	public function test_a_stale_lock_expires() {
		$evento = $this->un_evento();
		$taller = $this->taller( $evento, 10 );
		$id     = $this->inscribir( $evento );

		// Como lo dejaría una petición muerta hace un buen rato.
		add_option( 'evt_seat_lock_' . $evento, (string) ( time() - Registrations::LOCK_TTL - 60 ), '', 'no' );

		$this->assertTrue( Registrations::seat( $evento, $id, $taller )['ok'] );
	}

	/**
	 * Y el candado se suelta aunque la operación salga por la puerta de atrás.
	 */
	public function test_the_lock_is_released_even_when_the_seat_is_refused() {
		$evento = $this->un_evento();
		$lleno  = $this->taller( $evento, 1, 'Taller lleno' );

		$a = $this->inscribir( $evento, array( 'email' => 'a@example.org' ) );
		$b = $this->inscribir( $evento, array( 'email' => 'b@example.org' ) );
		Registrations::seat( $evento, $a, $lleno );
		Registrations::seat( $evento, $b, $lleno );

		$this->assertTrue( Registrations::lock( $evento ), 'el candado no se quedó cogido al rechazar' );
		Registrations::unlock( $evento );
	}
	// ─── los bordes que quedaban sin pisar ─────────────────────────────────

	/**
	 * Una lista de preguntas ilegible no rompe nada: se queda en nada.
	 */
	public function test_unreadable_questions_read_as_none() {
		$this->assertSame( array(), SignupQuestions::read( 'esto no es json' ) );
		$this->assertSame( array(), SignupQuestions::read( '' ) );
		$this->assertSame( array(), SignupQuestions::read( 42 ) );
		$this->assertSame( array(), SignupQuestions::read( array( 'ni', 'esto' ) ) );
	}

	/**
	 * Las opciones se escriben una por línea, y se limpian al leerlas.
	 */
	public function test_the_options_are_read_one_per_line() {
		$this->assertSame(
			array( 'Gluten', 'Lactosa' ),
			SignupQuestions::options( "Gluten\r\n  Lactosa  \n\n Gluten \n" ),
			'sin vacías, sin repetidas y sin espacios de sobra'
		);
		$this->assertSame( array(), SignupQuestions::options( 42 ) );

		// Y hay un tope, para que una lista pegada de un tirón no se convierta
		// en una meta enorme.
		$muchas = SignupQuestions::options( range( 1, SignupQuestions::MAX_OPTIONS + 20 ) );
		$this->assertCount( SignupQuestions::MAX_OPTIONS, $muchas );
	}

	/**
	 * Una pregunta sin identificador recibe uno, y no se repite.
	 */
	public function test_a_question_without_an_id_gets_one() {
		$n         = 0;
		$preguntas = SignupQuestions::with_ids(
			array(
				array(
					'id'    => '',
					'label' => 'Una',
				),
				array(
					'id'    => '',
					'label' => 'Otra',
				),
				array(
					'id'    => 'qyatengo00001',
					'label' => 'Ya tenía',
				),
			),
			static function () use ( &$n ): string {
				++$n;
				return str_pad( (string) $n, 12, 'abcdef' );
			}
		);

		$this->assertTrue( SignupQuestions::is_id( $preguntas[0]['id'] ) );
		$this->assertTrue( SignupQuestions::is_id( $preguntas[1]['id'] ) );
		$this->assertNotSame( $preguntas[0]['id'], $preguntas[1]['id'] );
		$this->assertSame( 'qyatengo00001', $preguntas[2]['id'], 'el que ya tenía no se toca' );
	}

	/**
	 * Cada tipo de respuesta se lee como el texto que va en su columna.
	 */
	public function test_an_answer_reads_as_the_text_of_its_column() {
		$casilla = array( 'type' => 'check' );
		$this->assertSame( 'Sí', SignupQuestions::as_text( $casilla, true ) );
		$this->assertSame( 'No', SignupQuestions::as_text( $casilla, false ) );

		$varias = array( 'type' => 'many' );
		$this->assertSame( 'Gluten, Lactosa', SignupQuestions::as_text( $varias, array( 'Gluten', 'Lactosa' ) ) );

		$texto = array( 'type' => 'text' );
		$this->assertSame( 'algo', SignupQuestions::as_text( $texto, 'algo' ) );
		$this->assertSame( '', SignupQuestions::as_text( $texto, null ) );
	}

	/**
	 * Un texto corto se corta: una meta no es un sitio donde pegar un libro.
	 */
	public function test_a_short_text_is_cut() {
		$preguntas = SignupQuestions::read(
			array(
				array(
					'id'    => 'qlibre0000001',
					'label' => 'Observaciones',
					'type'  => 'text',
				),
			)
		);
		$r         = SignupQuestions::answers( $preguntas, array( 'qlibre0000001' => str_repeat( 'a', 400 ) ) );

		$this->assertSame( 250, mb_strlen( $r['data']['qlibre0000001'] ) );
	}

	/**
	 * Soltar el taller sin coger otro deja la plaza libre.
	 */
	public function test_releasing_without_taking_frees_the_seat() {
		$evento = $this->un_evento();
		$taller = $this->taller( $evento, 5 );
		$id     = $this->inscribir( $evento );

		Registrations::seat( $evento, $id, $taller );
		$this->assertSame( 1, Registrations::taken( $evento, $taller ) );

		$this->assertTrue( Registrations::seat( $evento, $id, 0 )['ok'] );
		$this->assertSame( 0, Registrations::taken( $evento, $taller ) );
	}

	/**
	 * Y pedir el que ya se tiene no hace nada, ni falla.
	 */
	public function test_asking_for_the_same_workshop_is_a_no_op() {
		$evento = $this->un_evento();
		$taller = $this->taller( $evento, 1 );
		$id     = $this->inscribir( $evento );

		$this->assertTrue( Registrations::seat( $evento, $id, $taller )['ok'] );
		// Aunque esté lleno —lo llena esta misma persona—, repetir vale.
		$this->assertTrue( Registrations::seat( $evento, $id, $taller )['ok'] );
		$this->assertSame( 1, Registrations::taken( $evento, $taller ) );
	}

	/**
	 * Una inscripción de otro evento no coge plaza aquí.
	 */
	public function test_a_registration_of_another_event_takes_nothing() {
		$evento = $this->un_evento();
		$otro   = $this->event( $this->administrator(), array( $this->area( 'Salud' ) ) );
		$taller = $this->taller( $evento, 5 );
		$ajena  = $this->inscribir( $otro );

		$res = Registrations::seat( $evento, $ajena, $taller );
		$this->assertFalse( $res['ok'] );
		$this->assertSame( 'no_es_de_este_evento', $res['error'] );
	}
}
