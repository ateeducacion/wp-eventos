<?php
/**
 * Tests for the educational centres catalogue, synchronization and storage.
 *
 * @package Evt
 */

use Evt\Admin\Settings;
use Evt\Centre\CentreCatalog;
use Evt\Centre\CentreCatalogue;
use Evt\Centre\CentreCatalogueSync;
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
		delete_option( CentreCatalogueSync::OPTION_LOCK );
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
		delete_option( CentreCatalogueSync::OPTION_LOCK );
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
	 * @param string|WP_Error $manifest_body  Manifest response body or WP_Error.
	 * @param string|WP_Error $catalogue_body Catalogue response body or WP_Error.
	 * @param int             $manifest_code  HTTP code for manifest.
	 * @param int             $catalogue_code HTTP code for catalogue.
	 * @return void
	 */
	private function mock_http( $manifest_body, $catalogue_body, int $manifest_code = 200, int $catalogue_code = 200 ): void {
		add_filter(
			'pre_http_request',
			static function ( $pre, $parsed_args, $url ) use ( $manifest_body, $catalogue_body, $manifest_code, $catalogue_code ) {
				unset( $pre, $parsed_args );
				if ( false !== strpos( $url, 'manifest.json' ) ) {
					if ( is_wp_error( $manifest_body ) ) {
						return $manifest_body;
					}
					return array(
						'response' => array( 'code' => $manifest_code ),
						'body'     => (string) $manifest_body,
					);
				}
				if ( false !== strpos( $url, 'centros.min.json' ) ) {
					if ( is_wp_error( $catalogue_body ) ) {
						return $catalogue_body;
					}
					return array(
						'response' => array( 'code' => $catalogue_code ),
						'body'     => (string) $catalogue_body,
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

		// Métodos de conteo y consulta.
		$this->assertSame( 2, CentreCatalogue::count() );
		$this->assertSame( 1, CentreCatalogue::active_count() );
		$this->assertNotNull( CentreCatalogue::find( '38017731' ) );
		$this->assertNull( CentreCatalogue::find( '99999999' ) );
		$this->assertNull( CentreCatalogue::find( '' ) );
		$this->assertTrue( CentreCatalogue::is_active( '38017731' ) );
		$this->assertFalse( CentreCatalogue::is_active( '35000011' ) );
		$this->assertFalse( CentreCatalogue::is_active( '99999999' ) );
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
		$initial_status = array(
			'schema_version'       => 1,
			'sha256'               => 'prev_sha256',
			'catalogue_updated_at' => '2026-09-19',
			'last_checked_at'      => '2026-09-19 10:00:00',
			'last_success_at'      => '2026-09-19 10:00:00',
			'last_error'           => '',
			'record_count'         => 1,
			'active_count'         => 1,
		);
		update_option( CentreCatalogue::OPTION_STATUS, $initial_status, false );

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

		// El diagnóstico conserva los metadatos de éxito y solo actualiza last_checked_at y last_error.
		$status = CentreCatalogue::status();
		$this->assertSame( 'prev_sha256', $status['sha256'] );
		$this->assertSame( '2026-09-19 10:00:00', $status['last_success_at'] );
		$this->assertSame( 1, $status['record_count'] );
		$this->assertStringContainsString( 'HTTP 500', $status['last_error'] );
		$this->assertNotSame( '2026-09-19 10:00:00', $status['last_checked_at'] );
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

		$cols = Participants::columns();
		$this->assertArrayHasKey( 'centre_code', $cols );
		$this->assertArrayHasKey( 'centre', $cols );
		$this->assertSame( 'Código de centro', $cols['centre_code'] );
		$this->assertSame( 'Centro', $cols['centre'] );

		$csv = Participants::csv( $filas );
		$this->assertStringContainsString( 'Código de centro', $csv );
		$this->assertStringContainsString( 'Centro', $csv );
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
		$user_id = $this->factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$this->expectException( WPDieException::class );
		Settings::render();
	}

	/**
	 * 18. Settings::render renderiza correctamente para administrador.
	 */
	public function test_settings_centres_render_page_admin(): void {
		$admin = $this->administrator();
		$this->acting_as( $admin );

		// 1. Catálogo vacío con notices.
		$_GET['updated'] = 'synced';
		$_GET['msg']     = 'Catálogo actualizado';
		ob_start();
		Settings::render();
		$html = (string) ob_get_clean();
		$this->assertStringContainsString( 'Catálogo actualizado', $html );
		$this->assertStringContainsString( 'No disponible (catálogo vacío)', $html );
		$this->assertStringContainsString( 'Actualizar catálogo ahora', $html );

		// 2. Con error y catálogo poblado.
		$catalog = array(
			'38017731' => array(
				'code'         => '38017731',
				'name'         => 'CIFP Ejemplo',
				'island'       => 'Isla',
				'municipality' => 'Mun',
				'type'         => 'CIFP',
				'active'       => true,
			),
		);
		update_option( CentreCatalogue::OPTION_CATALOGUE, $catalog, false );
		update_option(
			CentreCatalogue::OPTION_STATUS,
			array(
				'schema_version'       => 1,
				'sha256'               => 'abcdef',
				'catalogue_updated_at' => '2026-09-20',
				'last_checked_at'      => '2026-09-20 12:00:00',
				'last_success_at'      => '2026-09-20 12:00:00',
				'last_error'           => 'Fallo previo',
				'record_count'         => 1,
				'active_count'         => 1,
			),
			false
		);

		unset( $_GET['msg'], $_GET['updated'] );
		$_GET['error'] = 'Error fatal simulado';
		ob_start();
		Settings::render();
		$html2 = (string) ob_get_clean();
		$this->assertStringContainsString( 'Error fatal simulado', $html2 );
		$this->assertStringContainsString( 'Fallo previo', $html2 );
		$this->assertStringContainsString( 'abcdef', $html2 );
		$this->assertStringContainsString( 'Disponible', $html2 );
	}

	/**
	 * 19. Settings::handle_actions maneja sync_centres.
	 */
	public function test_settings_handle_actions(): void {
		$admin = $this->administrator();
		$this->acting_as( $admin );
		set_current_screen( 'edit.php?post_type=evt_event' );

		// 1. sync_centres con éxito.
		$items   = array(
			array(
				'code'         => '38017731',
				'name'         => 'CIFP Test',
				'island'       => 'Isla',
				'municipality' => 'Mun',
				'type'         => 'CIFP',
				'active'       => true,
			),
		);
		$payload = $this->make_payload( $items );
		$this->mock_http( $payload['manifest_json'], $payload['catalogue_json'] );
		update_option( CentreCatalogueSync::OPTION_MANIFEST_URL, 'https://example.org/manifest.json', false );

		$this->post(
			array(
				'evt_action' => 'sync_centres',
			),
			Settings::NONCE_SYNC_CENTRES,
			'_evt_centres_nonce'
		);

		$url = $this->exit_url( array( Settings::class, 'handle_actions' ) );
		$this->assertNotNull( $url );
		$this->assertSame( 'synced', $this->query_arg( $url, 'updated' ) );

		// 2. sync_centres con excepción.
		$this->mock_http( '', '', 500, 500 );
		$this->post(
			array(
				'evt_action' => 'sync_centres',
			),
			Settings::NONCE_SYNC_CENTRES,
			'_evt_centres_nonce'
		);
		$url_err = $this->exit_url( array( Settings::class, 'handle_actions' ) );
		$this->assertNotNull( $url_err );
		$this->assertNotEmpty( $this->query_arg( $url_err, 'error' ) );

		// 3. Usuario sin permisos no ejecuta nada.
		$sub = $this->factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->acting_as( $sub );
		$this->post(
			array(
				'evt_action' => 'sync_centres',
			),
			Settings::NONCE_SYNC_CENTRES,
			'_evt_centres_nonce'
		);
		$url_sub = $this->exit_url( array( Settings::class, 'handle_actions' ) );
		$this->assertNull( $url_sub );
	}

	/**
	 * 20. CentreCli y CentreSettings han sido eliminados de la arquitectura.
	 */
	public function test_centre_cli_and_centre_settings_do_not_exist(): void {
		$this->assertFalse( class_exists( 'Evt\Centre\CentreCli' ) );
		$this->assertFalse( class_exists( 'Evt\Centre\CentreSettings' ) );
	}

	/**
	 * 22. CentreCatalog::register y CentreCatalog::centres.
	 */
	public function test_centre_catalog_filter_hook(): void {
		CentreCatalog::register();
		$this->assertNotFalse( has_filter( 'evt_centres', array( CentreCatalog::class, 'provide_centres' ) ) );

		// Con centros ya pasados, no los sobreescribe.
		$input    = array( 'custom' => 'Centro Propio' );
		$filtered = CentreCatalog::provide_centres( $input );
		$this->assertSame( $input, $filtered );

		// Con array vacío, devuelve active_options.
		$catalog = array(
			'38017731' => array(
				'code'         => '38017731',
				'name'         => 'CIFP Activo',
				'island'       => 'Isla',
				'municipality' => 'Mun',
				'type'         => 'CIFP',
				'active'       => true,
			),
		);
		update_option( CentreCatalogue::OPTION_CATALOGUE, $catalog, false );
		$options = CentreCatalog::provide_centres( array() );
		$this->assertArrayHasKey( '38017731', $options );
		$this->assertSame( 'CIFP Activo', $options['38017731'] );
	}

	/**
	 * 21. CentreCatalogueSync: WP-Cron, locks atómicos y URLs configurables.
	 */
	public function test_sync_cron_locks_and_urls(): void {
		// 1. Cron.
		CentreCatalogueSync::register_cron();
		$this->assertNotFalse( has_action( CentreCatalogueSync::CRON_HOOK, array( CentreCatalogueSync::class, 'cron_sync' ) ) );
		$this->assertNotFalse( wp_next_scheduled( CentreCatalogueSync::CRON_HOOK ) );

		// cron_sync() no propaga excepción si falla.
		$this->mock_http( '', '', 500, 500 );
		CentreCatalogueSync::cron_sync(); // No lanza excepción.

		// 2. Locks atómicos con token.
		$token = CentreCatalogueSync::acquire_lock();
		$this->assertIsString( $token );
		$this->assertNotEmpty( $token );
		$this->assertFalse( CentreCatalogueSync::acquire_lock() );
		$this->assertFalse( CentreCatalogueSync::release_lock( 'wrong-token' ) );
		$this->assertTrue( CentreCatalogueSync::release_lock( $token ) );

		// Expiración de candado: tras 300 segundos se recupera.
		$token1 = CentreCatalogueSync::acquire_lock();
		$this->assertIsString( $token1 );
		update_option(
			CentreCatalogueSync::OPTION_LOCK,
			array(
				'token' => $token1,
				'time'  => time() - 301,
			)
		);
		$token2 = CentreCatalogueSync::acquire_lock();
		$this->assertIsString( $token2 );
		$this->assertNotSame( $token1, $token2 );
		$this->assertTrue( CentreCatalogueSync::release_lock( $token2 ) );

		// 3. Carrera de recuperación de candado caducado: compare-and-delete atómico.
		$expired_token = 'token-antiguo';
		$expired_time  = time() - 305;
		$expired_lock  = array(
			'token' => $expired_token,
			'time'  => $expired_time,
		);
		update_option( CentreCatalogueSync::OPTION_LOCK, $expired_lock, false );

		// Simulamos que otro proceso B se adelanta y adquiere un candado nuevo legítimo.
		$new_lock = array(
			'token' => 'token-nuevo-proceso-b',
			'time'  => time(),
		);
		update_option( CentreCatalogueSync::OPTION_LOCK, $new_lock, false );

		// Proceso A, que tenía en memoria $expired_lock, no debe poder borrar el lock nuevo.
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Simulación de carrera en test.
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s",
				CentreCatalogueSync::OPTION_LOCK,
				maybe_serialize( $expired_lock )
			)
		);
		$this->assertSame( 0, $deleted );
		$current_lock = get_option( CentreCatalogueSync::OPTION_LOCK );
		$this->assertSame( 'token-nuevo-proceso-b', $current_lock['token'] );

		// 4. URLs.
		update_option( CentreCatalogueSync::OPTION_MANIFEST_URL, 'https://example.org/directorio/manifest.json', false );
		$this->assertSame( 'https://example.org/directorio/manifest.json', CentreCatalogueSync::manifest_url() );
		$this->assertSame( 'https://example.org/directorio/centros.min.json', CentreCatalogueSync::catalogue_url( 'https://example.org/directorio/manifest.json' ) );

		// Filtros de URL.
		add_filter(
			'evt_centres_manifest_url',
			static function () {
				return 'https://filtrado.org/manifest.json';
			}
		);
		add_filter(
			'evt_centres_catalogue_url',
			static function () {
				return 'https://filtrado.org/centros.min.json';
			}
		);
		$this->assertSame( 'https://filtrado.org/manifest.json', CentreCatalogueSync::manifest_url() );
		$this->assertSame( 'https://filtrado.org/centros.min.json', CentreCatalogueSync::catalogue_url() );
	}

	/**
	 * 22. CentreCatalogueSync: validación de URLs HTTPS obligatorias.
	 */
	public function test_https_validation(): void {
		$this->assertTrue( CentreCatalogueSync::is_valid_https_url( 'https://example.org/manifest.json' ) );
		$this->assertFalse( CentreCatalogueSync::is_valid_https_url( 'http://example.org/manifest.json' ) );
		$this->assertFalse( CentreCatalogueSync::is_valid_https_url( 'ftp://example.org/manifest.json' ) );
		$this->assertFalse( CentreCatalogueSync::is_valid_https_url( 'file:///etc/passwd' ) );
		$this->assertFalse( CentreCatalogueSync::is_valid_https_url( '//example.org/manifest.json' ) );
		$this->assertFalse( CentreCatalogueSync::is_valid_https_url( '/manifest.json' ) );
		$this->assertFalse( CentreCatalogueSync::is_valid_https_url( '' ) );
		$this->assertFalse( CentreCatalogueSync::is_valid_https_url( 'https://' ) );
	}

	/**
	 * 23. CentreCatalogueSync: ramas de error en sync.
	 */
	public function test_sync_error_branches(): void {
		// 1. Bloqueo ya adquirido.
		$token = CentreCatalogueSync::acquire_lock();
		$this->assertIsString( $token );
		try {
			CentreCatalogueSync::sync();
			$this->fail( 'Se esperaba fallo por bloqueo.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'otra sincronización de centros en curso', $e->getMessage() );
		}
		CentreCatalogueSync::release_lock( $token );

		// 2. URL de manifest vacía.
		delete_option( CentreCatalogueSync::OPTION_MANIFEST_URL );
		try {
			CentreCatalogueSync::sync();
			$this->fail( 'Se esperaba fallo por URL vacía.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'no utiliza HTTPS', $e->getMessage() );
		}

		// 3. URL de manifest no HTTPS.
		update_option( CentreCatalogueSync::OPTION_MANIFEST_URL, 'http://example.org/manifest.json', false );
		try {
			CentreCatalogueSync::sync();
			$this->fail( 'Se esperaba fallo por URL no HTTPS.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'no utiliza HTTPS', $e->getMessage() );
		}

		update_option( CentreCatalogueSync::OPTION_MANIFEST_URL, 'https://example.org/manifest.json', false );

		// 4. Error de red (WP_Error) al descargar manifest.
		$this->mock_http( new WP_Error( 'http_err', 'Fallo de conexión' ), '' );
		try {
			CentreCatalogueSync::sync();
			$this->fail( 'Se esperaba fallo por WP_Error en manifest.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'Fallo de conexión', $e->getMessage() );
		}

		// 5. Manifest con JSON inválido.
		$this->mock_http( 'esto no es json', '' );
		try {
			CentreCatalogueSync::sync();
			$this->fail( 'Se esperaba fallo por JSON inválido en manifest.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'JSON válido', $e->getMessage() );
		}

		// 6. Manifest sin sha256.
		$bad_manifest = (string) wp_json_encode(
			array(
				'schema_version' => 1,
				'files'          => array( 'centros.min.json' => array() ),
			)
		);
		$this->mock_http( $bad_manifest, '[]' );
		try {
			CentreCatalogueSync::sync();
			$this->fail( 'Se esperaba fallo por sha256 ausente en manifest.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'no declara la entrada de centros.min.json con su sha256', $e->getMessage() );
		}

		// 7. URL de catálogo no HTTPS.
		$payload = $this->make_payload( array() );
		$this->mock_http( $payload['manifest_json'], '[]' );
		add_filter(
			'evt_centres_catalogue_url',
			static function () {
				return 'http://inseguro.org/centros.min.json';
			}
		);
		try {
			CentreCatalogueSync::sync();
			$this->fail( 'Se esperaba fallo por catálogo no HTTPS.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'no utiliza HTTPS', $e->getMessage() );
		}
		remove_all_filters( 'evt_centres_catalogue_url' );

		// 8. Error de red (WP_Error) al descargar centros.min.json.
		$this->mock_http( $payload['manifest_json'], new WP_Error( 'cat_err', 'Error al descargar catálogo' ) );
		try {
			CentreCatalogueSync::sync();
			$this->fail( 'Se esperaba fallo por WP_Error en catálogo.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'Error al descargar catálogo', $e->getMessage() );
		}

		// 9. HTTP distinto de 200 en centros.min.json.
		$this->mock_http( $payload['manifest_json'], 'Error', 200, 403 );
		try {
			CentreCatalogueSync::sync();
			$this->fail( 'Se esperaba fallo por HTTP 403 en catálogo.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'HTTP 403', $e->getMessage() );
		}

		// 10. Discrepancia de recuento declarado vs contenido.
		$manifest_count_mismatch = array(
			'schema_version' => 1,
			'files'          => array(
				'centros.min.json' => array(
					'sha256'  => hash( 'sha256', '[]' ),
					'records' => 5,
				),
			),
		);
		$this->mock_http( (string) wp_json_encode( $manifest_count_mismatch ), '[]' );
		try {
			CentreCatalogueSync::sync();
			$this->fail( 'Se esperaba fallo por discrepancia de recuento.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'no coincide con el declarado en el manifest', $e->getMessage() );
		}

		// 11. Elemento del array no es un objeto.
		$bad_item_payload = $this->make_payload( array( 'no es un objeto' ) );
		$this->mock_http( $bad_item_payload['manifest_json'], $bad_item_payload['catalogue_json'] );
		try {
			CentreCatalogueSync::sync();
			$this->fail( 'Se esperaba fallo por registro no array.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'no es un objeto válido', $e->getMessage() );
		}

		// 12. Código de centro con formato inválido (no son 8 dígitos).
		$bad_code_payload = $this->make_payload(
			array(
				array(
					'code'   => '1234',
					'name'   => 'Test',
					'active' => true,
				),
			)
		);
		$this->mock_http( $bad_code_payload['manifest_json'], $bad_code_payload['catalogue_json'] );
		try {
			CentreCatalogueSync::sync();
			$this->fail( 'Se esperaba fallo por código no de 8 dígitos.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'debe tener exactamente 8 dígitos', $e->getMessage() );
		}

		// 13. Denominación vacía.
		$empty_name_payload = $this->make_payload(
			array(
				array(
					'code'   => '38017731',
					'name'   => '',
					'active' => true,
				),
			)
		);
		$this->mock_http( $empty_name_payload['manifest_json'], $empty_name_payload['catalogue_json'] );
		try {
			CentreCatalogueSync::sync();
			$this->fail( 'Se esperaba fallo por denominación vacía.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'Denominación vacía', $e->getMessage() );
		}

		// 14. Campo active no es booleano.
		$bad_active_payload = $this->make_payload(
			array(
				array(
					'code'   => '38017731',
					'name'   => 'CIFP Test',
					'active' => 'si',
				),
			)
		);
		$this->mock_http( $bad_active_payload['manifest_json'], $bad_active_payload['catalogue_json'] );
		try {
			CentreCatalogueSync::sync();
			$this->fail( 'Se esperaba fallo por active no booleano.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'debe ser booleano', $e->getMessage() );
		}
	}

	/**
	 * 24. Registrations::centres exige contrato de mapa asociativo [ código 8 dígitos => nombre ].
	 */
	public function test_registrations_centres_formats(): void {
		// 1. Sin filtro o devolviendo no array.
		add_filter( 'evt_centres', '__return_false' );
		$this->assertSame( array(), Registrations::centres() );
		remove_all_filters( 'evt_centres' );

		// 2. Lista de strings o claves que no son códigos de 8 dígitos se descartan.
		add_filter(
			'evt_centres',
			static function () {
				return array(
					'IES Uno'  => 'IES Uno',
					'123'      => 'Código Corto',
					'38017731' => 'CIFP Válido',
				);
			}
		);
		$filtered = Registrations::centres();
		$this->assertSame(
			array(
				'38017731' => 'CIFP Válido',
			),
			$filtered
		);
		remove_all_filters( 'evt_centres' );

		// 3. Mapa asociativo válido (código 8 dígitos => nombre).
		add_filter(
			'evt_centres',
			static function () {
				return array( '38017731' => 'CIFP Icod' );
			}
		);
		$map = Registrations::centres();
		$this->assertSame( array( '38017731' => 'CIFP Icod' ), $map );
		remove_all_filters( 'evt_centres' );
	}

	/**
	 * 25. RegistrationInput validación estricta de 8 dígitos y contrato de catálogo (fail-closed).
	 */
	public function test_registration_input_code_validation_and_resolution(): void {
		$this->assertTrue( RegistrationInput::is_centre_code( '38017731' ) );
		$this->assertFalse( RegistrationInput::is_centre_code( '3801773' ) );
		$this->assertFalse( RegistrationInput::is_centre_code( '380177310' ) );
		$this->assertFalse( RegistrationInput::is_centre_code( '12345' ) );
		$this->assertFalse( RegistrationInput::is_centre_code( 'abcdefgh' ) );
		$this->assertFalse( RegistrationInput::is_centre_code( '' ) );

		// Mensajes de error en why().
		$this->assertStringContainsString( 'el centro', RegistrationInput::why( array( 'centre' ) ) );
		$this->assertStringContainsString( 'el documento de identidad y el centro', RegistrationInput::why( array( 'tax_id', 'centre' ) ) );
		$this->assertStringContainsString( 'No se ha podido completar la inscripción', RegistrationInput::why( array() ) );

		$catalog = array(
			'38017731' => 'CIFP En Icod',
		);

		// Envío con código oficial válido resuelve código y snapshot de denominación.
		$val = RegistrationInput::core(
			array(
				'tax_id'  => '12345678Z',
				'name'    => 'Laura',
				'surname' => 'Gómez',
				'email'   => 'laura@example.org',
				'phone'   => '600111222',
				'centre'  => '38017731',
				'consent' => '1',
			),
			$catalog
		);
		$this->assertTrue( $val['ok'] );
		$this->assertSame( '38017731', $val['data']['centre_code'] );
		$this->assertSame( 'CIFP En Icod', $val['data']['centre'] );

		// Envío con denominación en vez de código debe ser rechazado (el navegador solo envía código).
		$val_name = RegistrationInput::core(
			array(
				'tax_id'  => '12345678Z',
				'name'    => 'Laura',
				'surname' => 'Gómez',
				'email'   => 'laura@example.org',
				'phone'   => '600111222',
				'centre'  => 'CIFP En Icod',
				'consent' => '1',
			),
			$catalog
		);
		$this->assertFalse( $val_name['ok'] );
		$this->assertContains( 'centre', $val_name['errors'] );

		// Envío con código no de 8 dígitos (7 dígitos).
		$val_7 = RegistrationInput::core(
			array(
				'tax_id'  => '12345678Z',
				'name'    => 'Laura',
				'surname' => 'Gómez',
				'email'   => 'laura@example.org',
				'phone'   => '600111222',
				'centre'  => '3801773',
				'consent' => '1',
			),
			$catalog
		);
		$this->assertFalse( $val_7['ok'] );
		$this->assertContains( 'centre', $val_7['errors'] );

		// Envío con código no de 8 dígitos (9 dígitos).
		$val_9 = RegistrationInput::core(
			array(
				'tax_id'  => '12345678Z',
				'name'    => 'Laura',
				'surname' => 'Gómez',
				'email'   => 'laura@example.org',
				'phone'   => '600111222',
				'centre'  => '380177310',
				'consent' => '1',
			),
			$catalog
		);
		$this->assertFalse( $val_9['ok'] );
		$this->assertContains( 'centre', $val_9['errors'] );

		// Sin catálogo cargado (vacío por defecto o []), se rechaza (fail-closed).
		$val_empty = RegistrationInput::core(
			array(
				'tax_id'  => '12345678Z',
				'name'    => 'Laura',
				'surname' => 'Gómez',
				'email'   => 'laura@example.org',
				'phone'   => '600111222',
				'centre'  => '38017731',
				'consent' => '1',
			)
		);
		$this->assertFalse( $val_empty['ok'] );
		$this->assertContains( 'centre', $val_empty['errors'] );

		$val_empty_array = RegistrationInput::core(
			array(
				'tax_id'  => '12345678Z',
				'name'    => 'Laura',
				'surname' => 'Gómez',
				'email'   => 'laura@example.org',
				'phone'   => '600111222',
				'centre'  => '38017731',
				'consent' => '1',
			),
			array()
		);
		$this->assertFalse( $val_empty_array['ok'] );
		$this->assertContains( 'centre', $val_empty_array['errors'] );
	}
}
