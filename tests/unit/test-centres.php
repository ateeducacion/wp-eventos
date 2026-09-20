<?php
/**
 * Tests for the educational centres catalogue, synchronization and storage.
 *
 * @package Evt
 */

use Evt\Centre\CentreCatalog;
use Evt\Centre\CentreCatalogue;
use Evt\Centre\CentreCatalogueSync;
use Evt\Centre\CentreSettings;
use Evt\Domain\RegistrationInput;
use Evt\Meta\RegistrationMetaKeys;
use Evt\PublicFront\Block\SignupBlock;
use Evt\PublicFront\Participants;
use Evt\PublicFront\Registrations;

/**
 * Pruebas unitarias del catálogo de centros educativos.
 */
class Test_Centres extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Set up test environment before each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();
		delete_option( CentreCatalogue::OPTION_CATALOGUE );
		delete_option( CentreCatalogue::OPTION_STATUS );
		delete_option( CentreCatalogueSync::OPTION_MANIFEST_URL );
		delete_option( CentreCatalogueSync::OPTION_CATALOGUE_URL );
		delete_transient( CentreCatalogueSync::LOCK_KEY );
	}

	/**
	 * Clean up test environment after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		delete_option( CentreCatalogue::OPTION_CATALOGUE );
		delete_option( CentreCatalogue::OPTION_STATUS );
		delete_option( CentreCatalogueSync::OPTION_MANIFEST_URL );
		delete_option( CentreCatalogueSync::OPTION_CATALOGUE_URL );
		delete_transient( CentreCatalogueSync::LOCK_KEY );
		remove_all_filters( 'pre_http_request' );
		remove_all_filters( 'evt_centres' );
		remove_all_filters( 'evt_centres_manifest_url' );
		remove_all_filters( 'evt_centres_catalogue_url' );
		parent::tear_down();
	}

	/**
	 * Helper to create a fake manifest and catalogue payload.
	 *
	 * @param array<int, array<string, mixed>> $items Records.
	 * @return array{manifest_json:string, catalogue_json:string, sha256:string}
	 */
	private function make_payload( array $items ): array {
		$catalogue_json = (string) wp_json_encode( $items );
		$sha256         = hash( 'sha256', $catalogue_json );
		$manifest       = array(
			'schema_version'       => 1,
			'catalogue_updated_at' => '2026-09-20T10:00:00Z',
			'files'                => array(
				'centros.min.json' => array(
					'sha256'  => $sha256,
					'records' => count( $items ),
					'bytes'   => strlen( $catalogue_json ),
				),
			),
		);
		$manifest_json  = (string) wp_json_encode( $manifest );

		return array(
			'manifest_json'  => $manifest_json,
			'catalogue_json' => $catalogue_json,
			'sha256'         => $sha256,
		);
	}

	/**
	 * Helper to mock HTTP responses for manifest and catalogue.
	 *
	 * @param string $manifest_body  Manifest response body.
	 * @param string $catalogue_body Catalogue response body.
	 * @param int    $manifest_code  HTTP code for manifest.
	 * @param int    $catalogue_code HTTP code for catalogue.
	 * @return void
	 */
	private function mock_http( string $manifest_body, string $catalogue_body, int $manifest_code = 200, int $catalogue_code = 200 ): void {
		add_filter(
			'pre_http_request',
			static function ( $pre, $parsed_args, $url ) use ( $manifest_body, $catalogue_body, $manifest_code, $catalogue_code ) {
				unset( $pre, $parsed_args );
				if ( false !== strpos( $url, 'manifest.json' ) ) {
					return array(
						'response' => array( 'code' => $manifest_code ),
						'body'     => $manifest_body,
					);
				}
				if ( false !== strpos( $url, 'centros.min.json' ) ) {
					return array(
						'response' => array( 'code' => $catalogue_code ),
						'body'     => $catalogue_body,
					);
				}
				return false;
			},
			10,
			3
		);
	}

	/**
	 * 1. Catálogo válido se almacena en la opción con su status.
	 */
	public function test_valid_catalogue_is_stored(): void {
		$items = array(
			array(
				'code'         => '38017731',
				'name'         => 'CIFP Ejemplo',
				'island'       => 'Isla Norte',
				'municipality' => 'Municipio 1',
				'type'         => 'CIFP',
				'active'       => true,
			),
			array(
				'code'         => '35000011',
				'name'         => 'CEIP Antiguo',
				'island'       => 'Isla Sur',
				'municipality' => 'Municipio 2',
				'type'         => 'CEIP',
				'active'       => false,
			),
		);

		$payload = $this->make_payload( $items );
		$this->mock_http( $payload['manifest_json'], $payload['catalogue_json'] );

		update_option( CentreCatalogueSync::OPTION_MANIFEST_URL, 'https://example.org/manifest.json', false );

		$result = CentreCatalogueSync::sync();

		$this->assertSame( 'updated', $result['status'] );
		$this->assertSame( 2, $result['records'] );
		$this->assertSame( 1, $result['active'] );

		$stored = CentreCatalogue::all();
		$this->assertCount( 2, $stored );
		$this->assertArrayHasKey( '38017731', $stored );
		$this->assertArrayHasKey( '35000011', $stored );
		$this->assertSame( 'CIFP Ejemplo', $stored['38017731']['name'] );
		$this->assertTrue( $stored['38017731']['active'] );
		$this->assertFalse( $stored['35000011']['active'] );

		$status = CentreCatalogue::status();
		$this->assertSame( 1, $status['schema_version'] );
		$this->assertSame( $payload['sha256'], $status['sha256'] );
		$this->assertSame( 2, $status['record_count'] );
		$this->assertSame( 1, $status['active_count'] );
		$this->assertEmpty( $status['last_error'] );
	}

	/**
	 * 2. Catálogo con hash incorrecto se rechaza y no sobreescribe.
	 */
	public function test_invalid_hash_is_rejected(): void {
		$items = array(
			array(
				'code'         => '38017731',
				'name'         => 'CIFP Bueno',
				'island'       => 'Isla Norte',
				'municipality' => 'Municipio 1',
				'type'         => 'CIFP',
				'active'       => true,
			),
		);

		// Manifest declara hash manipulado.
		$payload  = $this->make_payload( $items );
		$manifest = json_decode( $payload['manifest_json'], true );
		$manifest['files']['centros.min.json']['sha256'] = '0000000000000000000000000000000000000000000000000000000000000000';

		$bad_manifest = (string) wp_json_encode( $manifest );

		$this->mock_http( $bad_manifest, $payload['catalogue_json'] );
		update_option( CentreCatalogueSync::OPTION_MANIFEST_URL, 'https://example.org/manifest.json', false );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'El hash SHA-256 del archivo descargado no coincide' );

		CentreCatalogueSync::sync();
	}

	/**
	 * 3. JSON incorrecto en centros.min.json se rechaza.
	 */
	public function test_invalid_json_is_rejected(): void {
		$bad_body = '<html>404 Not Found</html>';
		$sha256   = hash( 'sha256', $bad_body );
		$manifest = (string) wp_json_encode(
			array(
				'schema_version' => 1,
				'files'          => array(
					'centros.min.json' => array(
						'sha256'  => $sha256,
						'records' => 1,
					),
				),
			)
		);

		$this->mock_http( $manifest, $bad_body );
		update_option( CentreCatalogueSync::OPTION_MANIFEST_URL, 'https://example.org/manifest.json', false );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'El archivo centros.min.json no contiene un array JSON válido' );

		CentreCatalogueSync::sync();
	}

	/**
	 * 4. Código oficial duplicado se rechaza.
	 */
	public function test_duplicate_code_is_rejected(): void {
		$items = array(
			array(
				'code'         => '38017731',
				'name'         => 'CIFP Uno',
				'island'       => 'Isla 1',
				'municipality' => 'Mun 1',
				'type'         => 'CIFP',
				'active'       => true,
			),
			array(
				'code'         => '38017731',
				'name'         => 'CIFP Duplicado',
				'island'       => 'Isla 2',
				'municipality' => 'Mun 2',
				'type'         => 'CIFP',
				'active'       => true,
			),
		);

		$payload = $this->make_payload( $items );
		$this->mock_http( $payload['manifest_json'], $payload['catalogue_json'] );
		update_option( CentreCatalogueSync::OPTION_MANIFEST_URL, 'https://example.org/manifest.json', false );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Código oficial duplicado en centros.min.json: "38017731"' );

		CentreCatalogueSync::sync();
	}

	/**
	 * 5. Manifest con versión incompatible se rechaza.
	 */
	public function test_incompatible_manifest_schema_is_rejected(): void {
		$manifest = (string) wp_json_encode(
			array(
				'schema_version' => 2,
				'files'          => array(
					'centros.min.json' => array(
						'sha256'  => 'abcdef',
						'records' => 1,
					),
				),
			)
		);

		$this->mock_http( $manifest, '[]' );
		update_option( CentreCatalogueSync::OPTION_MANIFEST_URL, 'https://example.org/manifest.json', false );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Versión de esquema incompatible' );

		CentreCatalogueSync::sync();
	}

	/**
	 * 6. Error HTTP conserva el último catálogo bueno local.
	 */
	public function test_http_error_preserves_last_good_catalogue(): void {
		$initial = array(
			'38017731' => array(
				'code'         => '38017731',
				'name'         => 'Centro Existente',
				'island'       => 'Isla',
				'municipality' => 'Mun',
				'type'         => 'CEIP',
				'active'       => true,
			),
		);
		update_option( CentreCatalogue::OPTION_CATALOGUE, $initial, false );

		$this->mock_http( '', '', 500, 500 );
		update_option( CentreCatalogueSync::OPTION_MANIFEST_URL, 'https://example.org/manifest.json', false );

		try {
			CentreCatalogueSync::sync();
			$this->fail( 'Se esperaba una excepción.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'HTTP 500', $e->getMessage() );
		}

		// El catálogo inicial sigue intacto.
		$stored = CentreCatalogue::all();
		$this->assertCount( 1, $stored );
		$this->assertSame( 'Centro Existente', $stored['38017731']['name'] );

		// El diagnóstico registra el error.
		$status = CentreCatalogue::status();
		$this->assertStringContainsString( 'HTTP 500', $status['last_error'] );
	}

	/**
	 * 7. Mismo hash no vuelve a descargar centros.min.json.
	 */
	public function test_same_hash_does_not_download_again(): void {
		$items   = array(
			array(
				'code'         => '38017731',
				'name'         => 'CIFP Ejemplo',
				'island'       => 'Isla',
				'municipality' => 'Mun',
				'type'         => 'CIFP',
				'active'       => true,
			),
		);
		$payload = $this->make_payload( $items );
		$this->mock_http( $payload['manifest_json'], $payload['catalogue_json'] );
		update_option( CentreCatalogueSync::OPTION_MANIFEST_URL, 'https://example.org/manifest.json', false );

		// Primera pasada: actualiza.
		$res1 = CentreCatalogueSync::sync();
		$this->assertSame( 'updated', $res1['status'] );

		// Cambiamos el mock para que centros.min.json devuelva un 500 si se pide.
		$this->mock_http( $payload['manifest_json'], '', 200, 500 );

		// Segunda pasada: detecta mismo hash y devuelve 'unchanged' sin pedir centros.min.json.
		$res2 = CentreCatalogueSync::sync();
		$this->assertSame( 'unchanged', $res2['status'] );
	}

	/**
	 * 8. Selector contiene únicamente centros activos.
	 */
	public function test_selector_contains_only_active_centres(): void {
		$catalog = array(
			'38017731' => array(
				'code'         => '38017731',
				'name'         => 'CIFP Activo',
				'island'       => 'Isla',
				'municipality' => 'Mun',
				'type'         => 'CIFP',
				'active'       => true,
			),
			'35000011' => array(
				'code'         => '35000011',
				'name'         => 'CEIP Cerrado',
				'island'       => 'Isla',
				'municipality' => 'Mun',
				'type'         => 'CEIP',
				'active'       => false,
			),
		);
		update_option( CentreCatalogue::OPTION_CATALOGUE, $catalog, false );

		$options = CentreCatalogue::active_options();
		$this->assertArrayHasKey( '38017731', $options );
		$this->assertSame( 'CIFP Activo', $options['38017731'] );
		$this->assertArrayNotHasKey( '35000011', $options );
	}

	/**
	 * 9. Desplegable de inscripción renderiza value=código y label=denominación.
	 */
	public function test_signup_select_renders_code_and_denomination(): void {
		add_filter(
			'evt_centres',
			static function () {
				return array(
					'38017731' => 'CIFP En Icod',
					'35000011' => 'CEIP José Sánchez',
				);
			}
		);

		$evento = $this->event( $this->administrator() );
		update_post_meta( $evento, RegistrationMetaKeys::SIGNUP_OPEN, 1 );

		$html = SignupBlock::html(
			array(
				'section_type' => 'inscripcion',
				'event_id'     => $evento,
			)
		);

		$this->assertStringContainsString( '<option value="38017731">CIFP En Icod</option>', $html );
		$this->assertStringContainsString( '<option value="35000011">CEIP José Sánchez</option>', $html );
	}

	/**
	 * 10 y 11. Código manipulado o inactivo se rechaza en servidor.
	 */
	public function test_manipulated_or_inactive_code_is_rejected(): void {
		$catalog = array(
			'38017731' => 'CIFP Activo',
		);

		// Código inexistente manipulado.
		$val_fake = RegistrationInput::core(
			array(
				'tax_id'  => '12345678Z',
				'name'    => 'Ana',
				'surname' => 'García',
				'email'   => 'ana@example.org',
				'phone'   => '600111222',
				'centre'  => '99999999',
				'consent' => '1',
			),
			$catalog
		);
		$this->assertFalse( $val_fake['ok'] );
		$this->assertContains( 'centre', $val_fake['errors'] );

		// Código inactivo no debe estar en $catalog activo, por lo que se rechaza igual.
		$val_inactive = RegistrationInput::core(
			array(
				'tax_id'  => '12345678Z',
				'name'    => 'Ana',
				'surname' => 'García',
				'email'   => 'ana@example.org',
				'phone'   => '600111222',
				'centre'  => '35000011',
				'consent' => '1',
			),
			$catalog
		);
		$this->assertFalse( $val_inactive['ok'] );
		$this->assertContains( 'centre', $val_inactive['errors'] );
	}

	/**
	 * 12. Nueva inscripción guarda código oficial en evt_reg_centre_code y snapshot en evt_reg_centre.
	 */
	public function test_new_registration_saves_code_and_name_snapshot(): void {
		$catalog = array(
			'38017731' => 'CIFP En Icod',
		);

		$val = RegistrationInput::core(
			array(
				'tax_id'  => '12345678Z',
				'name'    => 'María',
				'surname' => 'Pérez',
				'email'   => 'maria@example.org',
				'phone'   => '600123456',
				'centre'  => '38017731',
				'consent' => '1',
			),
			$catalog
		);

		$this->assertTrue( $val['ok'] );
		$this->assertSame( '38017731', $val['data']['centre_code'] );
		$this->assertSame( 'CIFP En Icod', $val['data']['centre'] );

		$evento = $this->event( $this->administrator() );
		$reg_id = Registrations::create( $evento, $val['data'], array() );

		$this->assertGreaterThan( 0, $reg_id );
		$this->assertSame( '38017731', get_post_meta( $reg_id, RegistrationMetaKeys::REG_CENTRE_CODE, true ) );
		$this->assertSame( 'CIFP En Icod', get_post_meta( $reg_id, RegistrationMetaKeys::REG_CENTRE, true ) );
	}

	/**
	 * 13. Inscripción histórica sin código se sigue visualizando por su denominación.
	 */
	public function test_historical_registration_without_code_displays_name(): void {
		$evento = $this->event( $this->administrator() );

		$reg_id = Registrations::create(
			$evento,
			array(
				'tax_id'      => '12345678Z',
				'name'        => 'Juan',
				'surname'     => 'Histórico',
				'email'       => 'juan@example.org',
				'phone'       => '600000000',
				'centre'      => 'Colegio Antiguo Sin Código',
				'centre_code' => '',
			),
			array()
		);

		$this->assertEmpty( get_post_meta( $reg_id, RegistrationMetaKeys::REG_CENTRE_CODE, true ) );
		$this->assertSame( 'Colegio Antiguo Sin Código', get_post_meta( $reg_id, RegistrationMetaKeys::REG_CENTRE, true ) );

		$filas = Registrations::participants( array(), $evento );
		$this->assertCount( 1, $filas );
		$this->assertSame( 'Colegio Antiguo Sin Código', $filas[0]['centre'] );
		$this->assertSame( '', $filas[0]['centre_code'] );

		$csv = Participants::csv( $filas );
		$this->assertStringContainsString( 'Colegio Antiguo Sin Código', $csv );
	}

	/**
	 * 14 y 15. Renombrar o desactivar un centro en el catálogo no altera snapshots existentes.
	 */
	public function test_renaming_or_deactivating_centre_does_not_modify_snapshots(): void {
		$evento = $this->event( $this->administrator() );
		$reg_id = Registrations::create(
			$evento,
			array(
				'tax_id'      => '12345678Z',
				'name'        => 'Pedro',
				'surname'     => 'Snapshot',
				'email'       => 'pedro@example.org',
				'phone'       => '600123123',
				'centre'      => 'CIFP Denominación Antigua',
				'centre_code' => '38017731',
			),
			array()
		);

		// Cambiar el catálogo (nuevo nombre e inactivo).
		$updated_catalog = array(
			'38017731' => array(
				'code'         => '38017731',
				'name'         => 'CIFP Denominación Nueva',
				'island'       => 'Isla',
				'municipality' => 'Mun',
				'type'         => 'CIFP',
				'active'       => false,
			),
		);
		update_option( CentreCatalogue::OPTION_CATALOGUE, $updated_catalog, false );

		// La inscripción histórica conserva intacto su snapshot.
		$this->assertSame( 'CIFP Denominación Antigua', get_post_meta( $reg_id, RegistrationMetaKeys::REG_CENTRE, true ) );
		$this->assertSame( '38017731', get_post_meta( $reg_id, RegistrationMetaKeys::REG_CENTRE_CODE, true ) );
	}

	/**
	 * 16. Catálogo vacío impide nueva inscripción.
	 */
	public function test_empty_catalogue_prevents_new_registration(): void {
		$val = RegistrationInput::core(
			array(
				'tax_id'  => '12345678Z',
				'name'    => 'María',
				'surname' => 'Pérez',
				'email'   => 'maria@example.org',
				'phone'   => '600123456',
				'centre'  => '38017731',
				'consent' => '1',
			),
			array() // Catálogo vacío.
		);

		$this->assertFalse( $val['ok'] );
		$this->assertContains( 'centre', $val['errors'] );
	}

	/**
	 * 17. Diagnóstico y actualización manual comprueban capability.
	 */
	public function test_manual_refresh_checks_capability(): void {
		// Usuario sin manage_options.
		$user_id = $this->factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$this->expectException( WPDieException::class );
		CentreSettings::render_page();
	}
}
