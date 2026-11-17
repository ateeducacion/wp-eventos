<?php
/**
 * Tests for the attendance checkpoints, the QR page and the paper sheet.
 *
 * @package Evt
 */

use Evt\Meta\EventMetaKeys;
use Evt\Meta\ProgrammeMetaKeys;
use Evt\Meta\RegistrationMetaKeys;
use Evt\PublicFront\Attendance;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\Programme;
use Evt\PublicFront\Registrations;
use Evt\PublicFront\View\EventWorkspaceView;

/**
 * El control de asistencia (ADR-0053).
 *
 * - Un punto conserva su testigo aunque se le cambie el nombre: el QR impreso
 *   sigue valiendo.
 * - Se marca con la cuenta, solo con el punto abierto, y la primera hora se
 *   queda.
 * - La organización marca y desmarca a mano, y queda como tal.
 * - La hoja de firmas y el CSV salen de lo mismo.
 */
class Test_Attendance extends WP_UnitTestCase {

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
	 * Un evento con su página de inscripción y catálogo.
	 */
	public function set_up(): void {
		parent::set_up();
		add_filter(
			'evt_centres',
			static function (): array {
				return array( '38000001' => 'CEIP El Molino' );
			}
		);
		add_filter( 'pre_wp_mail', '__return_true' );
		$this->app();
		$this->pages();
		$this->admin  = $this->administrator();
		$this->evento = $this->event( $this->admin, array( $this->area( 'Innovación' ) ) );
		$seccion      = $this->event_page( $this->evento, 'inscripcion', array( 'post_name' => 'inscripcion' ) );
		update_post_meta( $seccion, EventMetaKeys::SECTION_TYPE, 'inscripcion' );
	}

	/**
	 * Sin filtros de otro test.
	 */
	public function tear_down(): void {
		remove_all_filters( 'evt_centres' );
		remove_all_filters( 'pre_wp_mail' );
		parent::tear_down();
	}

	/**
	 * Una inscripción.
	 *
	 * @param string $nombre Name.
	 * @param string $dni    Tax ID.
	 * @param string $correo Email.
	 * @return int
	 */
	private function inscripcion( string $nombre, string $dni, string $correo ): int {
		$v = Registrations::validate(
			$this->evento,
			array(
				'tax_id'  => $dni,
				'name'    => $nombre,
				'surname' => 'Pérez',
				'email'   => $correo,
				'centre'  => '38000001',
				'consent' => '1',
			),
			array()
		);
		return (int) Registrations::add( $this->evento, $v['core'], $v['answers'] )['id'];
	}

	/**
	 * Guarda los puntos como lo haría la pestaña.
	 *
	 * @param array<int, array<string, mixed>> $filas Rows.
	 * @return array<int, array<string, mixed>>
	 */
	private function guardar_puntos( array $filas ): array {
		$puntos = Attendance::merge_points( Attendance::points( $this->evento ), $filas );
		update_post_meta( $this->evento, RegistrationMetaKeys::ATTENDANCE_POINTS, wp_slash( (string) wp_json_encode( $puntos ) ) );
		return Attendance::points( $this->evento );
	}

	/**
	 * Pulsar «Confirmar asistencia» desde el QR.
	 *
	 * @param array<string, mixed> $punto Checkpoint.
	 * @return string|null
	 */
	private function marcar( array $punto ): ?string {
		$this->post(
			array(
				Attendance::FIELD_OP    => Attendance::OP_MARK,
				Attendance::FIELD_EVENT => (string) $this->evento,
				Attendance::FIELD_TOKEN => $punto['token'],
			),
			Attendance::NONCE_ACTION,
			Attendance::NONCE_FIELD
		);
		return $this->exit_url( array( Attendance::class, 'maybe_handle_submit' ) );
	}

	/**
	 * Un punto nuevo tiene testigo; renombrarlo no se lo cambia; quitarlo lo borra.
	 */
	public function test_points_keep_their_token_when_renamed() {
		$puntos = $this->guardar_puntos(
			array(
				array( 'label' => 'Entrada' ),
				array( 'label' => '' ),
			)
		);
		$this->assertCount( 1, $puntos, 'la fila en blanco no añade nada' );
		$token = $puntos[0]['token'];
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{32}$/', $token );
		$this->assertFalse( $puntos[0]['open'], 'nace cerrado' );

		$puntos = $this->guardar_puntos(
			array(
				array(
					'id'    => $puntos[0]['id'],
					'label' => 'Acreditación',
					'open'  => true,
				),
				array( 'label' => 'Salida' ),
			)
		);
		$this->assertSame( 'Acreditación', $puntos[0]['label'] );
		$this->assertSame( $token, $puntos[0]['token'], 'el QR impreso sigue valiendo' );
		$this->assertTrue( $puntos[0]['open'] );
		$this->assertCount( 2, $puntos );

		$puntos = $this->guardar_puntos(
			array(
				array(
					'id'     => $puntos[0]['id'],
					'label'  => 'Acreditación',
					'remove' => true,
				),
				array(
					'id'    => $puntos[1]['id'],
					'label' => 'Salida',
				),
			)
		);
		$this->assertSame( array( 'Salida' ), wp_list_pluck( $puntos, 'label' ) );
		$this->assertNull( Attendance::point_by_token( $this->evento, $token ), 'quitado, su QR ya no abre nada' );
	}

	/**
	 * Con la cuenta y el punto abierto se marca; la primera hora se queda.
	 */
	public function test_marking_with_the_account_while_open() {
		$uid = self::factory()->user->create( array( 'user_email' => 'ana@example.org' ) );
		$id  = $this->inscripcion( 'Ana', '12345678Z', 'ana@example.org' );

		$punto = $this->guardar_puntos(
			array(
				array(
					'label' => 'Entrada',
					'open'  => false,
				),
			)
		)[0];

		$this->acting_as( $uid );
		$_GET[ Attendance::ARG_TOKEN ] = $punto['token'];
		$this->assertStringContainsString( 'no está abierto', Attendance::block( $this->evento ) );
		$this->marcar( $punto );
		$this->assertSame( array(), Attendance::marks( $id ), 'cerrado, no marca' );

		$punto = $this->guardar_puntos(
			array(
				array(
					'id'    => $punto['id'],
					'label' => 'Entrada',
					'open'  => true,
				),
			)
		)[0];
		$this->assertStringContainsString( 'Confirmar asistencia', Attendance::block( $this->evento ) );
		$vuelta = $this->marcar( $punto );
		$this->assertSame( $punto['token'], $this->query_arg( (string) $vuelta, Attendance::ARG_TOKEN ), 'vuelve a la misma página' );

		$marcas = Attendance::marks( $id );
		$this->assertArrayHasKey( $punto['id'], $marcas );
		$this->assertSame( Attendance::BY_QR, $marcas[ $punto['id'] ]['by'] );

		update_post_meta(
			$id,
			RegistrationMetaKeys::REG_ATTENDANCE,
			wp_slash(
				(string) wp_json_encode(
					array(
						$punto['id'] => array(
							'at' => '2027-02-06 09:05:00',
							'by' => 'qr',
						),
					)
				)
			)
		);
		$this->marcar( $punto );
		$this->assertSame( '2027-02-06 09:05:00', Attendance::marks( $id )[ $punto['id'] ]['at'], 'escanear dos veces no mueve la hora' );
		$this->assertStringContainsString( 'Asistencia confirmada', Attendance::block( $this->evento ) );
		$this->assertStringContainsString( '9:05', Attendance::block( $this->evento ) );
	}

	/**
	 * Una cuenta sin inscripción en el evento no marca nada, y se le dice qué hacer.
	 */
	public function test_an_account_without_registration_marks_nothing() {
		$otra  = self::factory()->user->create( array( 'user_email' => 'nadie@example.org' ) );
		$id    = $this->inscripcion( 'Ana', '12345678Z', 'ana@example.org' );
		$punto = $this->guardar_puntos(
			array(
				array(
					'label' => 'Entrada',
					'open'  => true,
				),
			)
		)[0];

		$this->acting_as( $otra );
		$_GET[ Attendance::ARG_TOKEN ] = $punto['token'];
		$this->marcar( $punto );
		$this->assertSame( array(), Attendance::marks( $id ) );
		$this->assertStringContainsString( 'Avise a la organización', Attendance::block( $this->evento ) );

		$_GET[ Attendance::ARG_TOKEN ] = str_repeat( 'a', 32 );
		$this->assertStringContainsString( 'no es de este evento', Attendance::block( $this->evento ) );
	}

	/**
	 * A mano: lo marcado se marca ahora como manual, lo desmarcado se quita.
	 */
	public function test_manual_marks_from_the_tab() {
		$id     = $this->inscripcion( 'Ana', '12345678Z', 'ana@example.org' );
		$puntos = $this->guardar_puntos(
			array(
				array( 'label' => 'Entrada' ),
				array( 'label' => 'Salida' ),
			)
		);
		Attendance::mark( $id, $puntos[0]['id'], Attendance::BY_QR );

		$this->acting_as( $this->admin );
		$op = EventWorkspace::OP_ATT_MARKS;
		$this->post(
			array(
				EventWorkspace::FIELD_DO    => $op,
				EventWorkspace::FIELD_EVENT => (string) $this->evento,
				'evt_at_rows'               => array( (string) $id, '999999' ),
				'evt_at'                    => array( $id => array( $puntos[1]['id'] => '1' ) ),
			),
			EventWorkspace::nonce_action( $op ),
			EventWorkspace::nonce_name( $op )
		);
		$this->exit_url( array( EventWorkspace::class, 'handle' ) );

		$marcas = Attendance::marks( $id );
		$this->assertArrayNotHasKey( $puntos[0]['id'], $marcas, 'desmarcada a mano' );
		$this->assertSame( Attendance::BY_MANUAL, $marcas[ $puntos[1]['id'] ]['by'] );
	}

	/**
	 * El CSV y la hoja de firmas: una columna por punto, la gente por apellidos y, si se pide, solo un taller.
	 */
	public function test_the_csv_and_the_sheet() {
		$taller = Programme::save_activity(
			$this->evento,
			0,
			array(
				'title'    => 'Música',
				'kind'     => ProgrammeMetaKeys::KIND_WORKSHOP,
				'date'     => '2027-02-06',
				'start'    => '15:00',
				'end'      => '16:15',
				'venue'    => '',
				'room'     => '',
				'seats'    => 15,
				'summary'  => '',
				'speakers' => array(),
			)
		);
		$ana    = $this->inscripcion( 'Ana', '12345678Z', 'ana@example.org' );
		$luis   = $this->inscripcion( 'Luis', '00000001R', 'luis@example.org' );
		Registrations::seat( $this->evento, $luis, array( $taller ) );
		$puntos = $this->guardar_puntos(
			array(
				array( 'label' => 'Entrada' ),
				array( 'label' => 'Salida' ),
			)
		);
		Attendance::mark( $ana, $puntos[0]['id'], Attendance::BY_MANUAL );

		$csv = Attendance::csv( $this->evento );
		$this->assertStringContainsString( '"Entrada";"Salida"', $csv );
		$this->assertStringContainsString( '(a mano)', $csv );
		$this->assertStringContainsString( '"Música"', $csv );

		$hoja = Attendance::sheet( $this->evento );
		$this->assertStringContainsString( 'Pérez, Ana', $hoja );
		$this->assertStringContainsString( 'Pérez, Luis', $hoja );
		$this->assertStringContainsString( '<th>Salida</th>', $hoja );

		$solo = Attendance::sheet( $this->evento, $taller );
		$this->assertStringContainsString( 'Pérez, Luis', $solo );
		$this->assertStringNotContainsString( 'Pérez, Ana', $solo, 'la hoja del taller, solo con su gente' );
		$this->assertStringContainsString( 'Taller: Música', $solo );
	}

	/**
	 * La hoja de firmas solo se sirve a quien puede abrir el evento.
	 */
	public function test_the_sheet_needs_to_open_the_event() {
		$this->inscripcion( 'Ana', '12345678Z', 'ana@example.org' );
		$this->go_to( (string) get_permalink( get_page_by_path( 'evento' ) ) );
		$_GET[ Attendance::ARG_SHEET ]     = '1';
		$_GET[ EventWorkspace::ARG_EVENT ] = (string) $this->evento;

		$this->acting_as( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->assertSame( '', $this->served( array( Attendance::class, 'maybe_print_sheet' ) ) );

		$this->acting_as( $this->admin );
		$this->assertStringContainsString( 'Hoja de firmas', $this->served( array( Attendance::class, 'maybe_print_sheet' ) ) );
	}

	/**
	 * La pestaña pinta los puntos con su QR y la tabla con las casillas.
	 */
	public function test_the_tab_paints_points_and_marks() {
		$this->inscripcion( 'Ana', '12345678Z', 'ana@example.org' );
		$this->guardar_puntos(
			array(
				array(
					'label' => 'Entrada',
					'open'  => true,
				),
			)
		);
		$this->acting_as( $this->admin );
		$_GET[ EventWorkspace::ARG_EVENT ] = (string) $this->evento;
		$_GET[ EventWorkspace::ARG_PANEL ] = EventWorkspace::PANEL_ATTENDANCE;
		$html                              = EventWorkspaceView::html( EventWorkspace::model() );

		$this->assertStringContainsString( 'data-evt-qr="', $html );
		$this->assertStringContainsString( Attendance::ARG_TOKEN . '=', $html, 'el enlace del QR lleva el testigo' );
		$this->assertStringContainsString( 'Hoja de firmas para imprimir', $html );
		$this->assertStringContainsString( 'Pérez, Ana', $html );
		$this->assertStringContainsString( 'name="evt_at[', $html );
	}
}
