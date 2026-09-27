<?php
/**
 * Tests for when a signup is accepted: window, state and session.
 *
 * @package Evt
 */

use Evt\Meta\EventMetaKeys;
use Evt\Meta\RegistrationMetaKeys;
use Evt\PublicFront\Block\SignupBlock;
use Evt\PublicFront\SignupForm;
use Evt\PublicFront\View\EventSignupPanel;

/**
 * Cuándo se acepta una inscripción, y quién tiene que iniciar sesión antes.
 *
 * Abierta es todo a la vez: el interruptor, el plazo, el evento publicado y
 * sin marcar como histórico. Y adjuntar un documento pide sesión siempre.
 */
class Test_Signup_Gates extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Un evento publicado con la inscripción abierta, sin fechas.
	 *
	 * @param array<string, mixed> $meta Meta to add.
	 * @return int
	 */
	private function evento( array $meta = array() ): int {
		return $this->event(
			$this->administrator(),
			array( $this->area() ),
			array_merge( array( RegistrationMetaKeys::SIGNUP_OPEN => true ), $meta )
		);
	}

	/**
	 * Una pregunta de archivo en el evento.
	 *
	 * @param int  $evento      Event post ID.
	 * @param bool $obligatoria Whether it is required.
	 * @return void
	 */
	private function con_archivo( int $evento, bool $obligatoria ): void {
		update_post_meta(
			$evento,
			RegistrationMetaKeys::SIGNUP_QUESTIONS,
			wp_slash(
				(string) wp_json_encode(
					array(
						array(
							'id'       => 'qdoc0001',
							'label'    => 'Autorización firmada',
							'type'     => 'file',
							'required' => $obligatoria,
						),
					)
				)
			)
		);
	}

	/**
	 * El bloque público de la inscripción de un evento.
	 *
	 * @param int $evento Event post ID.
	 * @return string
	 */
	private function bloque( int $evento ): string {
		add_filter( 'evt_centres', fn() => array( '38000001' => 'CEIP Ejemplo' ) );
		return SignupBlock::html(
			array(
				'section_type' => SignupBlock::NAME,
				'event_id'     => $evento,
			)
		);
	}

	/**
	 * Un borrador o un evento histórico no apuntan a nadie, aunque se quedara
	 * el interruptor puesto.
	 */
	public function test_a_draft_or_archived_event_is_closed() {
		$this->assertTrue( SignupForm::is_open( $this->evento() ) );

		$borrador = $this->evento();
		wp_update_post(
			array(
				'ID'          => $borrador,
				'post_status' => 'draft',
			)
		);
		$this->assertFalse( SignupForm::is_open( $borrador ) );

		$this->assertFalse( SignupForm::is_open( $this->evento( array( EventMetaKeys::ARCHIVED => true ) ) ) );
	}

	/**
	 * El plazo, cuando tiene fechas, manda; y la página dice cuándo.
	 */
	public function test_the_signup_window_is_enforced_and_explained() {
		$hoy    = current_time( 'Y-m-d' );
		$manana = gmdate( 'Y-m-d', strtotime( $hoy . ' +1 day' ) );
		$ayer   = gmdate( 'Y-m-d', strtotime( $hoy . ' -1 day' ) );

		$this->assertTrue(
			SignupForm::is_open(
				$this->evento(
					array(
						RegistrationMetaKeys::SIGNUP_START => $hoy,
						RegistrationMetaKeys::SIGNUP_END   => $hoy,
					)
				)
			)
		);

		$pronto = $this->evento( array( RegistrationMetaKeys::SIGNUP_START => $manana ) );
		$this->assertStringContainsString( 'se abre el', SignupForm::closed_because( $pronto ) );

		$tarde = $this->evento( array( RegistrationMetaKeys::SIGNUP_END => $ayer ) );
		$this->assertStringContainsString( 'terminó el', SignupForm::closed_because( $tarde ) );
		$this->assertStringContainsString( 'terminó el', $this->bloque( $tarde ) );
	}

	/**
	 * Sin inscripción pública, quien no ha entrado tiene que iniciar sesión.
	 */
	public function test_without_public_signup_anonymous_people_must_log_in() {
		$evento = $this->evento();
		$this->acting_as( 0 );

		$this->assertNotSame( '', SignupForm::login_needed( $evento ) );
		$html = $this->bloque( $evento );
		$this->assertStringContainsString( 'Iniciar sesión', $html );
		$this->assertStringNotContainsString( '<form', $html );

		$this->acting_as( (int) self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->assertSame( '', SignupForm::login_needed( $evento ) );
	}

	/**
	 * Con inscripción pública, el anónimo se inscribe pero no adjunta: la
	 * pregunta de archivo no sale y se le dice que inicie sesión para eso.
	 */
	public function test_public_signup_hides_file_questions_from_anonymous_people() {
		$evento = $this->evento( array( RegistrationMetaKeys::SIGNUP_PUBLIC => true ) );
		$this->con_archivo( $evento, false );
		$this->acting_as( 0 );

		$this->assertSame( '', SignupForm::login_needed( $evento ) );
		$html = $this->bloque( $evento );
		$this->assertStringContainsString( '<form', $html );
		$this->assertStringNotContainsString( 'type="file"', $html );
		$this->assertStringContainsString( 'inicie sesión', $html );
	}

	/**
	 * Si el documento es obligatorio, sin sesión no hay inscripción posible.
	 */
	public function test_a_required_file_sends_anonymous_people_to_log_in() {
		$evento = $this->evento( array( RegistrationMetaKeys::SIGNUP_PUBLIC => true ) );
		$this->con_archivo( $evento, true );
		$this->acting_as( 0 );

		$this->assertStringContainsString( 'adjuntar un documento', SignupForm::login_needed( $evento ) );
		$this->assertStringNotContainsString( '<form', $this->bloque( $evento ) );
	}

	/**
	 * La pestaña «Inscripción» avisa de que el anónimo no adjunta archivos.
	 */
	public function test_the_signup_tab_warns_when_public_signup_meets_a_file_question() {
		$modelo = array(
			'event_id'  => 1,
			'signup'    => array(
				'open'            => true,
				'start'           => '',
				'end'             => '',
				'public'          => true,
				'workshop_open'   => false,
				'workshop_start'  => '',
				'workshop_end'    => '',
				'consent_privacy' => '',
				'consent_image'   => '',
				'consent_version' => 1,
			),
			'questions' => array(
				array(
					'id'       => 'qdoc0001',
					'label'    => 'Autorización',
					'type'     => 'file',
					'options'  => array(),
					'required' => false,
				),
			),
			'q_types'   => array( 'file' => 'Archivo' ),
			'q_locked'  => false,
		);

		$html = EventSignupPanel::html( $modelo );
		$this->assertStringContainsString( 'name="evt_signup_public"', $html );
		$this->assertStringContainsString( 'class="' . \Evt\PublicFront\Assets::button_class( true ) . '">Guardar', $html, 'el botón es el de Bootstrap' );
		$this->assertStringContainsString( 'name="evt_signup_start"', $html );
		$this->assertStringContainsString( 'sin iniciar sesión no puede adjuntar', $html );

		$modelo['signup']['public'] = false;
		$this->assertStringNotContainsString( 'sin iniciar sesión no puede adjuntar', EventSignupPanel::html( $modelo ) );
	}
}
