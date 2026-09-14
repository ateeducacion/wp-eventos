<?php
/**
 * Tests for the meta registration of speakers, activities and registrations.
 *
 * @package Evt
 */

use Evt\Meta\ProgrammeMetaKeys;
use Evt\Meta\ProgrammeMetaRegistration;
use Evt\Meta\RegistrationMetaKeys;
use Evt\Meta\RegistrationMetaRegistration;
use Evt\PostType\ActivityPostType;
use Evt\PostType\RegistrationPostType;
use Evt\PostType\SpeakerPostType;

/**
 * Que cada meta esté declarada, y con el guardián que le toca.
 *
 * `register_post_meta()` no es decoración: el `sanitize_callback` es lo que
 * normaliza lo que entra, y el `auth_callback` es **la puerta**. Una meta que
 * se olvide de declarar se escribe igual, pero sin sanear y sin permiso, y eso
 * no lo ve nadie hasta que alguien lo usa.
 *
 * La raya que más importa está aquí: las metas de ponente y actividad se
 * editan a mano desde el taller, así que preguntan por `edit_post`; las de una
 * **inscripción** no las edita nadie —las escribe el aplicativo— y su
 * `auth_callback` dice que no siempre (ADR-0032).
 */
class Test_Meta_Registration extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Con el aplicativo arrancado.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
	}

	// ─── están declaradas ──────────────────────────────────────────────────

	/**
	 * Toda meta de ponente y de actividad está registrada en su tipo.
	 */
	public function test_every_programme_meta_is_registered() {
		$mapa = array(
			SpeakerPostType::POST_TYPE  => ProgrammeMetaRegistration::speaker_schema(),
			ActivityPostType::POST_TYPE => ProgrammeMetaRegistration::activity_schema(),
		);

		foreach ( $mapa as $tipo => $schema ) {
			$registradas = get_registered_meta_keys( 'post', $tipo );
			foreach ( array_keys( $schema ) as $clave ) {
				$this->assertArrayHasKey( $clave, $registradas, $clave . ' no está declarada' );
				$this->assertFalse( $registradas[ $clave ]['show_in_rest'], $clave . ' no sale por REST' );
			}
		}
	}

	/**
	 * Y toda meta de inscripción, y las de los ajustes de inscripción.
	 */
	public function test_every_registration_meta_is_registered() {
		$registradas = get_registered_meta_keys( 'post', RegistrationPostType::POST_TYPE );

		foreach ( RegistrationMetaKeys::registration_keys() as $clave ) {
			$this->assertArrayHasKey( $clave, $registradas, $clave . ' no está declarada' );
		}

		$del_evento = get_registered_meta_keys( 'post', \Evt\PostType\EventPostType::POST_TYPE );
		foreach ( RegistrationMetaKeys::signup_keys() as $clave ) {
			$this->assertArrayHasKey( $clave, $del_evento, $clave . ' no está declarada en el evento' );
		}
	}

	/**
	 * La puerta de una inscripción está cerrada para todo el mundo.
	 */
	public function test_a_registration_meta_is_closed_even_to_administration() {
		$registradas = get_registered_meta_keys( 'post', RegistrationPostType::POST_TYPE );
		$this->acting_as( $this->administrator() );

		foreach ( RegistrationMetaKeys::registration_keys() as $clave ) {
			$auth = $registradas[ $clave ]['auth_callback'];
			$this->assertFalse(
				(bool) call_user_func( $auth, false, $clave, 1, get_current_user_id(), 'edit_post_meta', array() ),
				$clave . ': las escribe el aplicativo, no una persona'
			);
		}
	}

	// ─── sanean lo que entra ───────────────────────────────────────────────

	/**
	 * El tipo de actividad se queda en la lista cerrada, o cae a «otra».
	 */
	public function test_the_activity_kind_stays_inside_the_closed_list() {
		$this->assertSame(
			ProgrammeMetaKeys::KIND_WORKSHOP,
			ProgrammeMetaRegistration::sanitize_kind( ProgrammeMetaKeys::KIND_WORKSHOP )
		);
		$this->assertSame( 'otra', ProgrammeMetaRegistration::sanitize_kind( 'inventada' ) );
		$this->assertSame( 'otra', ProgrammeMetaRegistration::sanitize_kind( array( 'ni', 'esto' ) ) );
	}

	/**
	 * Una hora es una hora, y lo que no lo es se queda en nada.
	 */
	public function test_a_time_is_a_time() {
		$this->assertSame( '09:30', ProgrammeMetaRegistration::sanitize_time( '09:30' ) );
		$this->assertSame( '', ProgrammeMetaRegistration::sanitize_time( '25:00' ) );
		$this->assertSame( '', ProgrammeMetaRegistration::sanitize_time( 'por la tarde' ) );
	}

	/**
	 * La lista de ponentes de una actividad son identificadores, y nada más.
	 */
	public function test_the_speaker_list_is_only_identifiers() {
		$limpia = ProgrammeMetaRegistration::sanitize_id_list( '4, 9, 0, cero, 9' );
		$this->assertSame( '4,9', $limpia, 'sin repetidos, sin ceros y sin texto' );
		$this->assertSame( '', ProgrammeMetaRegistration::sanitize_id_list( '' ) );
	}

	/**
	 * El documento se guarda normalizado: una persona, una forma.
	 */
	public function test_the_tax_id_is_stored_normalised() {
		$this->assertSame( '12345678Z', RegistrationMetaRegistration::sanitize_tax_id( ' 12.345.678-z ' ) );
		$this->assertSame( '', RegistrationMetaRegistration::sanitize_tax_id( array( 'no' ) ) );
	}

	/**
	 * Un testigo tiene la forma que emitimos, o no es un testigo.
	 */
	public function test_a_token_keeps_its_shape_or_is_dropped() {
		$bueno = str_repeat( 'a1', 20 );
		$this->assertSame( $bueno, RegistrationMetaRegistration::sanitize_token( strtoupper( $bueno ) ) );
		$this->assertSame( '', RegistrationMetaRegistration::sanitize_token( 'corto' ) );
		$this->assertSame( '', RegistrationMetaRegistration::sanitize_token( 'ana@example.org' ) );
	}

	/**
	 * Las respuestas se guardan por identificador de pregunta, y solo esas.
	 */
	public function test_the_answers_keep_only_real_question_ids() {
		$json  = RegistrationMetaRegistration::sanitize_answers(
			array(
				'qcomida000001' => true,
				'qalergia00001' => array( 'Gluten', 'Lactosa' ),
				'qlibre0000001' => '  algo  ',
				'no-es-un-id'   => 'fuera',
				'qmalo'         => 'también fuera',
			)
		);
		$datos = json_decode( $json, true );

		$this->assertSame(
			array( 'qcomida000001', 'qalergia00001', 'qlibre0000001' ),
			array_keys( $datos ),
			'lo que no tiene forma de identificador de pregunta no entra'
		);
		$this->assertTrue( $datos['qcomida000001'] );
		$this->assertSame( array( 'Gluten', 'Lactosa' ), $datos['qalergia00001'] );
		$this->assertSame( 'algo', $datos['qlibre0000001'] );
	}

	/**
	 * Y lo que no es una lista de respuestas no se guarda.
	 */
	public function test_anything_that_is_not_answers_is_dropped() {
		$this->assertSame( '', RegistrationMetaRegistration::sanitize_answers( 'esto no es json' ) );
		$this->assertSame( '', RegistrationMetaRegistration::sanitize_answers( 42 ) );
	}

	/**
	 * Las preguntas se guardan normalizadas, con su forma de siempre.
	 */
	public function test_the_questions_are_stored_normalised() {
		$json  = RegistrationMetaRegistration::sanitize_questions(
			array(
				array(
					'id'        => 'qcomida000001',
					'label'     => '  Se queda a comer  ',
					'type'      => 'check',
					'condition' => 'lo que no existe',
				),
				array( 'label' => '' ),
			)
		);
		$datos = json_decode( $json, true );

		$this->assertCount( 1, $datos, 'una pregunta sin rótulo no es una pregunta' );
		$this->assertSame( 'Se queda a comer', $datos[0]['label'] );
		$this->assertArrayNotHasKey( 'condition', $datos[0] );
	}

	/**
	 * Y una casilla es un booleano.
	 */
	public function test_a_checkbox_is_a_boolean() {
		$this->assertTrue( RegistrationMetaRegistration::sanitize_bool( '1' ) );
		$this->assertFalse( RegistrationMetaRegistration::sanitize_bool( '' ) );
		$this->assertFalse( RegistrationMetaRegistration::sanitize_bool( '0' ) );
	}
}
