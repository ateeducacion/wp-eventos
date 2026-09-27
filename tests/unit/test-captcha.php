<?php
/**
 * Tests for the ALTCHA check of anonymous signups.
 *
 * @package Evt
 */

use Evt\Meta\RegistrationMetaKeys;
use Evt\PublicFront\Block\SignupBlock;
use Evt\PublicFront\Captcha;
use Evt\PublicFront\Registrations;
use Evt\PublicFront\SignupForm;

/**
 * La casilla «No soy un robot» (ADR-0040).
 *
 * Lo que tiene que valer: una solución nuestra, resuelta, sin caducar y una
 * sola vez. Y que solo se pida a quien no ha iniciado sesión.
 */
class Test_Captcha extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Un payload tocado: se decodifica, se cambia un campo y se vuelve a codificar.
	 *
	 * @param string               $payload Solved payload.
	 * @param array<string, mixed> $cambios Fields to override.
	 * @return string
	 */
	private function tocado( string $payload, array $cambios ): string {
		$datos = json_decode( base64_decode( $payload ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- el formato de ALTCHA.
		return base64_encode( (string) wp_json_encode( array_merge( $datos, $cambios ) ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- el formato de ALTCHA.
	}

	/**
	 * Un evento publicado con la inscripción abierta al público.
	 *
	 * @return int
	 */
	private function evento_publico(): int {
		add_filter( 'evt_centres', fn() => array( '38000001' => 'CEIP Ejemplo' ) );
		return $this->event(
			$this->administrator(),
			array( $this->area() ),
			array(
				RegistrationMetaKeys::SIGNUP_OPEN   => true,
				RegistrationMetaKeys::SIGNUP_PUBLIC => true,
			)
		);
	}

	/**
	 * Un envío de la inscripción, con los campos que se digan encima de los buenos.
	 *
	 * @param int                  $evento Event ID.
	 * @param array<string, mixed> $campos Extra fields.
	 * @return void
	 */
	private function enviar( int $evento, array $campos ): void {
		$this->post(
			array_merge(
				array(
					SignupForm::FIELD_OP    => SignupForm::OP_SIGNUP,
					SignupForm::FIELD_EVENT => (string) $evento,
					'tax_id'                => '12345678Z',
					'name'                  => 'Ana',
					'surname'               => 'Pérez',
					'email'                 => 'ana@example.org',
					'centre'                => '38000001',
					'consent'               => '1',
				),
				$campos
			),
			SignupForm::NONCE_ACTION,
			SignupForm::NONCE_FIELD
		);
		$this->exit_url( array( SignupForm::class, 'maybe_handle_submit' ) );
	}

	/**
	 * El desafío tiene el formato v1 que entiende el componente.
	 */
	public function test_the_challenge_has_the_v1_shape() {
		$reto = Captcha::challenge();

		$this->assertSame( 'SHA-256', $reto['algorithm'] );
		$this->assertSame( Captcha::MAX_NUMBER, $reto['maxnumber'] );
		$this->assertMatchesRegularExpression( '/^[0-9a-f]{64}$/', $reto['challenge'] );
		$this->assertMatchesRegularExpression( '/^[0-9a-f]{64}$/', $reto['signature'] );
		$this->assertMatchesRegularExpression( '/\?expires=\d+$/', $reto['salt'] );
	}

	/**
	 * Una solución buena vale, y vale una sola vez.
	 */
	public function test_a_solution_is_accepted_once() {
		$payload = $this->altcha_payload();

		$this->assertTrue( Captcha::verify( $payload ) );
		$this->assertFalse( Captcha::verify( $payload ), 'la misma solución no vale dos veces' );
	}

	/**
	 * Lo que no es nuestro o no está resuelto no vale.
	 */
	public function test_forged_or_unsolved_payloads_are_refused() {
		$payload = $this->altcha_payload();

		$this->assertFalse( Captcha::verify( '' ) );
		$this->assertFalse( Captcha::verify( 'no es base64 ni JSON' ) );
		$this->assertFalse( Captcha::verify( $this->tocado( $payload, array( 'signature' => str_repeat( 'a', 64 ) ) ) ), 'firma ajena' );
		$this->assertFalse( Captcha::verify( $this->tocado( $payload, array( 'number' => Captcha::MAX_NUMBER + 1 ) ) ), 'sin resolver' );
		$this->assertFalse( Captcha::verify( $this->tocado( $payload, array( 'algorithm' => 'SHA-1' ) ) ), 'otro algoritmo' );
		$this->assertTrue( Captcha::verify( $payload ), 'y la buena sigue valiendo' );
	}

	/**
	 * Un desafío caducado no vale aunque esté bien firmado y resuelto.
	 */
	public function test_an_expired_challenge_is_refused() {
		$salt      = 'abc?expires=' . ( time() - 1 );
		$challenge = hash( 'sha256', $salt . '7' );
		$firmar    = new ReflectionMethod( Captcha::class, 'sign' );
		$firmar->setAccessible( true );

		$payload = base64_encode( // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- el formato de ALTCHA.
			(string) wp_json_encode(
				array(
					'algorithm' => 'SHA-256',
					'challenge' => $challenge,
					'number'    => 7,
					'salt'      => $salt,
					'signature' => $firmar->invoke( null, $challenge ),
				)
			)
		);

		$this->assertFalse( Captcha::verify( $payload ) );
	}

	/**
	 * Sin la casilla resuelta, un anónimo no se inscribe; con ella, sí.
	 */
	public function test_anonymous_signups_need_the_check() {
		$evento = $this->evento_publico();
		$this->acting_as( 0 );

		$this->enviar( $evento, array() );
		$this->assertSame( array(), Registrations::all( $evento ) );
		$this->assertStringContainsString( 'No soy un robot', (string) SignupForm::notice()['message'] );

		$this->enviar( $evento, array( Captcha::FIELD => $this->altcha_payload() ) );
		$this->assertCount( 1, Registrations::all( $evento ) );
	}

	/**
	 * Con sesión no se pide.
	 */
	public function test_logged_in_signups_skip_the_check() {
		$evento = $this->evento_publico();
		$this->acting_as( (int) self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$this->enviar( $evento, array() );
		$this->assertCount( 1, Registrations::all( $evento ) );
	}

	/**
	 * El formulario lleva la casilla solo para quien no ha entrado.
	 */
	public function test_the_form_shows_the_widget_only_to_anonymous_people() {
		$evento = $this->evento_publico();
		$bloque = static function () use ( $evento ): string {
			return SignupBlock::html(
				array(
					'section_type' => SignupBlock::NAME,
					'event_id'     => $evento,
				)
			);
		};

		$this->acting_as( 0 );
		$html = $bloque();
		$this->assertStringContainsString( '<altcha-widget name="' . Captcha::FIELD . '"', $html );
		$this->assertStringContainsString( 'language="es-es"', $html );
		$this->assertStringContainsString( rest_url( Captcha::REST_NAMESPACE . Captcha::REST_ROUTE ), html_entity_decode( $html ) );
		$this->assertTrue( wp_script_is( 'evt-altcha', 'enqueued' ) );

		$this->acting_as( (int) self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->assertStringNotContainsString( '<altcha-widget', $bloque() );
	}

	/**
	 * La ruta entrega un desafío fresco a cualquiera y sin caché.
	 */
	public function test_the_rest_route_hands_out_uncached_challenges() {
		do_action( 'rest_api_init' );
		$respuesta = rest_do_request( new WP_REST_Request( 'GET', '/' . Captcha::REST_NAMESPACE . Captcha::REST_ROUTE ) );

		$this->assertSame( 200, $respuesta->get_status() );
		$this->assertSame( 'SHA-256', $respuesta->get_data()['algorithm'] );
		$this->assertStringContainsString( 'no-store', $respuesta->get_headers()['Cache-Control'] );
	}

	/**
	 * Los guiones del componente salen como módulos, con su SRI desde el CDN.
	 */
	public function test_vendor_scripts_are_modules_with_integrity() {
		$url = Captcha::VENDOR['evt-altcha']['url'];
		$tag = Captcha::module_tag( '<script src="' . $url . '" id="evt-altcha-js"></script>', 'evt-altcha', $url ); // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- es la etiqueta que imprime WordPress, para filtrarla.

		$this->assertStringContainsString( 'type="module"', $tag );
		$this->assertStringContainsString( 'integrity="' . Captcha::VENDOR['evt-altcha']['sri'] . '"', $tag );
		$this->assertStringContainsString( 'crossorigin="anonymous"', $tag );

		$local = Captcha::module_tag( '<script src="/node_modules/altcha.js"></script>', 'evt-altcha', '/node_modules/altcha.js' ); // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- es la etiqueta que imprime WordPress, para filtrarla.
		$this->assertStringContainsString( 'type="module"', $local );
		$this->assertStringNotContainsString( 'integrity', $local, 'en desarrollo no hay SRI' );
	}
}
