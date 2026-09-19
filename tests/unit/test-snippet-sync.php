<?php
/**
 * Tests for the content-aware snippet synchronizer.
 *
 * @package Evt
 */

/**
 * `evt_sync_snippets_from_dir()` no puede volver a guardar un snippet
 * idéntico: guardarlo reescribe la fila, actualiza `modified`, revalida el
 * código —lo que puede llegar a ejecutarlo— y limpia cachés por un cambio que
 * no existe (ADR-0035). Estos tests cubren las cuatro salidas —created,
 * updated, unchanged, error—, que un `updated` conserva los campos que el
 * repositorio no gestiona, y que el fingerprint SHA-256 del estado
 * gestionado, no la versión EVT, es lo único que decide cuál toca.
 */
class Test_Snippet_Sync extends WP_UnitTestCase {

	/**
	 * Directorio temporal con los ficheros de la sincronización de cada test.
	 *
	 * @var string
	 */
	private string $dir = '';

	/**
	 * IDs de snippet que hay que borrar al terminar el test.
	 *
	 * @var int[]
	 */
	private array $ids = array();

	/**
	 * Si ya se ha activado Code Snippets para este proceso de tests.
	 *
	 * El wp-env de tests instala el plugin pero no lo activa —nada, hasta
	 * este test, llamaba a su API en PHPUnit—, así que hace falta activarlo
	 * aquí para que exista la tabla `wp_snippets` y sus funciones. Se activa
	 * una sola vez por proceso: `activate_plugin()` es idempotente y el resto
	 * de la suite no toca Code Snippets, así que no hay nada que aislar.
	 *
	 * @var bool
	 */
	private static bool $code_snippets_activated = false;

	/**
	 * Con Code Snippets activo y un directorio vacío para los ficheros del test.
	 */
	public function set_up() {
		parent::set_up();

		require_once dirname( __DIR__, 2 ) . '/scripts/lib/snippet-sync.php';

		if ( ! self::$code_snippets_activated ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			// wp-env nombra el directorio del plugin a partir del zip
			// («code-snippets.latest-stable»), no del slug, así que se busca
			// en vez de suponer la ruta.
			$plugin_files = (array) glob( WP_PLUGIN_DIR . '/code-snippets*/code-snippets.php' );
			$this->assertNotEmpty( $plugin_files, 'no se encuentra el plugin Code Snippets en wp-content/plugins' );
			activate_plugin( plugin_basename( $plugin_files[0] ) );
			self::$code_snippets_activated = true;
		}

		$this->dir = sys_get_temp_dir() . '/evt-snippet-sync-' . uniqid();
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- directorio temporal del test, fuera de WP_Filesystem.
		mkdir( $this->dir );
	}

	/**
	 * Borra los ficheros temporales y los snippets que haya creado el test.
	 */
	public function tear_down() {
		foreach ( (array) glob( $this->dir . '/*.php' ) as $file ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- fichero temporal del test, fuera de WP_Filesystem.
			unlink( $file );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- directorio temporal del test, fuera de WP_Filesystem.
		rmdir( $this->dir );

		foreach ( $this->ids as $id ) {
			\Code_Snippets\delete_snippet( $id );
		}
		$this->ids = array();

		parent::tear_down();
	}

	/**
	 * Escribe un fichero de snippet en el directorio temporal del test.
	 *
	 * @param string $basename Nombre del fichero.
	 * @param string $name     Cabecera «Snippet Name:».
	 * @param string $body     Cuerpo del snippet, ya en PHP.
	 * @param string $desc     Cabecera «Description:».
	 * @param string $scope    Cabecera «Scope:».
	 * @param int    $priority Cabecera «Priority:».
	 * @return void
	 */
	private function write( string $basename, string $name, string $body = '// no-op', string $desc = 'Descripción de prueba.', string $scope = 'global', int $priority = 10 ): void {
		$code = "<?php\n/**\n * Snippet Name: {$name}\n * Description: {$desc}\n * Scope: {$scope}\n * Priority: {$priority}\n */\n\n{$body}\n";
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- fichero temporal del test, fuera de WP_Filesystem.
		file_put_contents( $this->dir . '/' . $basename, $code );
	}

	/**
	 * Sincroniza el directorio temporal y recuerda los IDs para limpiarlos.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function sync(): array {
		$results = evt_sync_snippets_from_dir( $this->dir );
		foreach ( $results as $result ) {
			if ( ! empty( $result['id'] ) ) {
				$this->ids[] = (int) $result['id'];
			}
		}
		$this->ids = array_unique( $this->ids );
		return $results;
	}

	/**
	 * 1. Un snippet que no existe se crea y se activa.
	 */
	public function test_a_new_snippet_is_created_and_activated() {
		$this->write( 'a.php', 'EVT TEST — uno' );

		$results = $this->sync();

		$this->assertSame( 'created', $results['a.php']['status'] );
		$this->assertTrue( $results['a.php']['active'] );
		$this->assertGreaterThan( 0, $results['a.php']['id'] );
	}

	/**
	 * 2. Código distinto → updated.
	 */
	public function test_different_code_is_updated() {
		$this->write( 'a.php', 'EVT TEST — código', '// version uno' );
		$this->sync();

		$this->write( 'a.php', 'EVT TEST — código', '// version dos' );
		$results = $this->sync();

		$this->assertSame( 'updated', $results['a.php']['status'] );
	}

	/**
	 * 3. Descripción distinta → updated.
	 */
	public function test_different_description_is_updated() {
		$this->write( 'a.php', 'EVT TEST — desc', '// no-op', 'Primera descripción.' );
		$this->sync();

		$this->write( 'a.php', 'EVT TEST — desc', '// no-op', 'Segunda descripción.' );
		$results = $this->sync();

		$this->assertSame( 'updated', $results['a.php']['status'] );
	}

	/**
	 * 4. Ámbito distinto → updated.
	 */
	public function test_different_scope_is_updated() {
		$this->write( 'a.php', 'EVT TEST — scope', '// no-op', 'Descripción de prueba.', 'global' );
		$this->sync();

		$this->write( 'a.php', 'EVT TEST — scope', '// no-op', 'Descripción de prueba.', 'admin' );
		$results = $this->sync();

		$this->assertSame( 'updated', $results['a.php']['status'] );
	}

	/**
	 * 5. Prioridad distinta → updated.
	 */
	public function test_different_priority_is_updated() {
		$this->write( 'a.php', 'EVT TEST — priority', '// no-op', 'Descripción de prueba.', 'global', 10 );
		$this->sync();

		$this->write( 'a.php', 'EVT TEST — priority', '// no-op', 'Descripción de prueba.', 'global', 20 );
		$results = $this->sync();

		$this->assertSame( 'updated', $results['a.php']['status'] );
	}

	/**
	 * 6. Etiquetas distintas → updated.
	 *
	 * La cabecera no declara etiquetas: las pone la sincronización
	 * (`array('evt')`). Se simula un snippet que ya tenía otras —tocado a
	 * mano desde el escritorio de Code Snippets, por ejemplo— y se comprueba
	 * que la sincronización lo detecta como cambio y las deja como debe.
	 */
	public function test_different_tags_is_updated() {
		$this->write( 'a.php', 'EVT TEST — tags' );
		$created = $this->sync();
		$id      = (int) $created['a.php']['id'];

		$snippet       = \Code_Snippets\get_snippet( $id );
		$snippet->tags = array( 'otra-etiqueta' );
		\Code_Snippets\save_snippet( $snippet );

		$results = $this->sync();

		$this->assertSame( 'updated', $results['a.php']['status'] );
		$this->assertSame( array( 'evt' ), \Code_Snippets\get_snippet( $id )->tags );
	}

	/**
	 * 7. Snippet idéntico y activo → unchanged, sin reactivar.
	 */
	public function test_unchanged_and_active_is_left_alone() {
		$this->write( 'a.php', 'EVT TEST — unchanged' );
		$this->sync();

		$results = $this->sync();

		$this->assertSame( 'unchanged', $results['a.php']['status'] );
		$this->assertTrue( $results['a.php']['active'] );
		$this->assertFalse( $results['a.php']['reactivated'] );
	}

	/**
	 * 8. Snippet idéntico e inactivo → unchanged, reactivado.
	 */
	public function test_unchanged_and_inactive_is_reactivated_only() {
		$this->write( 'a.php', 'EVT TEST — inactive' );
		$created = $this->sync();
		\Code_Snippets\deactivate_snippet( (int) $created['a.php']['id'] );

		$results = $this->sync();

		$this->assertSame( 'unchanged', $results['a.php']['status'] );
		$this->assertTrue( $results['a.php']['active'] );
		$this->assertTrue( $results['a.php']['reactivated'] );
	}

	/**
	 * 9. Un `unchanged` no llama a `save_snippet()`.
	 *
	 * `save_snippet()` dispara siempre `code_snippets/update_snippet` al
	 * actualizar una fila existente; si no se llama, el gancho no se dispara.
	 */
	public function test_unchanged_does_not_call_save_snippet() {
		$this->write( 'a.php', 'EVT TEST — no-save' );
		$this->sync();

		$updates = 0;
		add_action(
			'code_snippets/update_snippet',
			function () use ( &$updates ) {
				++$updates;
			}
		);

		$results = $this->sync();

		$this->assertSame( 'unchanged', $results['a.php']['status'] );
		$this->assertSame( 0, $updates );
	}

	/**
	 * 10. Un `unchanged` activo no llama a `activate_snippet()`.
	 */
	public function test_unchanged_active_does_not_call_activate_snippet() {
		$this->write( 'a.php', 'EVT TEST — no-activate' );
		$this->sync();

		$activations = 0;
		add_action(
			'code_snippets/activate_snippet',
			function () use ( &$activations ) {
				++$activations;
			}
		);

		$results = $this->sync();

		$this->assertSame( 'unchanged', $results['a.php']['status'] );
		$this->assertFalse( $results['a.php']['reactivated'] );
		$this->assertSame( 0, $activations );
	}

	/**
	 * 11. Dos ejecuciones seguidas sin cambios dejan todo `unchanged`.
	 */
	public function test_two_runs_with_no_changes_stay_unchanged() {
		$this->write( 'a.php', 'EVT TEST — repetible uno' );
		$this->write( 'b.php', 'EVT TEST — repetible dos' );
		$this->sync();

		foreach ( array( $this->sync(), $this->sync() ) as $results ) {
			$this->assertSame( 'unchanged', $results['a.php']['status'] );
			$this->assertSame( 'unchanged', $results['b.php']['status'] );
		}
	}

	/**
	 * 12. El fingerprint es estable para entradas idénticas.
	 */
	public function test_fingerprint_is_stable_for_identical_input() {
		$state = evt_snippet_managed_state( "<?php\necho 1;\n", 'Descripción.', 'global', 10, array( 'evt' ) );

		$this->assertSame( evt_snippet_fingerprint( $state ), evt_snippet_fingerprint( $state ) );
	}

	/**
	 * 13. El fingerprint cambia si cambia cualquier campo gestionado.
	 */
	public function test_fingerprint_changes_with_any_managed_field() {
		$base = evt_snippet_fingerprint(
			evt_snippet_managed_state( "<?php\necho 1;\n", 'Descripción.', 'global', 10, array( 'evt' ) )
		);

		$variantes = array(
			evt_snippet_managed_state( "<?php\necho 2;\n", 'Descripción.', 'global', 10, array( 'evt' ) ),
			evt_snippet_managed_state( "<?php\necho 1;\n", 'Otra.', 'global', 10, array( 'evt' ) ),
			evt_snippet_managed_state( "<?php\necho 1;\n", 'Descripción.', 'admin', 10, array( 'evt' ) ),
			evt_snippet_managed_state( "<?php\necho 1;\n", 'Descripción.', 'global', 20, array( 'evt' ) ),
			evt_snippet_managed_state( "<?php\necho 1;\n", 'Descripción.', 'global', 10, array( 'otra' ) ),
		);

		foreach ( $variantes as $variante ) {
			$this->assertNotSame( $base, evt_snippet_fingerprint( $variante ) );
		}
	}

	/**
	 * 14. El orden de las etiquetas no importa.
	 */
	public function test_tag_order_does_not_affect_the_fingerprint() {
		$uno = evt_snippet_fingerprint(
			evt_snippet_managed_state( "<?php\necho 1;\n", 'Descripción.', 'global', 10, array( 'b', 'a' ) )
		);
		$dos = evt_snippet_fingerprint(
			evt_snippet_managed_state( "<?php\necho 1;\n", 'Descripción.', 'global', 10, array( 'a', 'b' ) )
		);

		$this->assertSame( $uno, $dos );
	}

	/**
	 * 15. Actualizar contenido gestionado conserva los campos no gestionados.
	 *
	 * `evt_sync_snippets_from_dir()` solo gestiona code/desc/scope/priority/
	 * tags. Un campo ajeno —aquí `condition_id`— fijado a mano tiene que
	 * sobrevivir a un `updated` en vez de volver al valor por defecto de
	 * `Snippet`, que es lo que pasaba al construir siempre un objeto nuevo.
	 */
	public function test_updating_managed_content_preserves_unmanaged_fields() {
		$this->write( 'a.php', 'EVT TEST — unmanaged', '// version uno' );
		$created = $this->sync();
		$id      = (int) $created['a.php']['id'];

		$snippet               = \Code_Snippets\get_snippet( $id );
		$snippet->condition_id = 42;
		\Code_Snippets\save_snippet( $snippet );

		$this->write( 'a.php', 'EVT TEST — unmanaged', '// version dos' );
		$results = $this->sync();

		$this->assertSame( 'updated', $results['a.php']['status'] );
		$this->assertSame( 42, \Code_Snippets\get_snippet( $id )->condition_id );
	}

	/**
	 * 16. `<?php`, cierre PHP y salto final no producen falsos `updated`.
	 *
	 * Es lo que quita `evt_strip_php_tags()`, y el fingerprint se calcula
	 * sobre el código ya normalizado.
	 */
	public function test_tag_and_trailing_newline_differences_do_not_change_the_fingerprint() {
		$sin_normalizar = evt_snippet_fingerprint(
			evt_snippet_managed_state( "<?php\necho 1;\n?>\n\n\n", 'Descripción.', 'global', 10, array( 'evt' ) )
		);
		$normalizado    = evt_snippet_fingerprint(
			evt_snippet_managed_state( 'echo 1;', 'Descripción.', 'global', 10, array( 'evt' ) )
		);

		$this->assertSame( $normalizado, $sin_normalizar );
	}
}
