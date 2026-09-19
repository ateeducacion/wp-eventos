<?php
/**
 * Shared library to sync the versioned snippets/*.php files into the
 * Code Snippets plugin table.
 *
 * Used by scripts/sync-snippets.php, which runs under `wp eval-file` and under
 * Playground `runPHP`. Must never depend on WP_CLI.
 *
 * @package Evt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'evt_code_snippets_is_active' ) ) {
	/**
	 * Check whether the Code Snippets plugin API is available.
	 *
	 * The snippet-ops functions are loaded unconditionally when the plugin is
	 * active, so checking for them is enough.
	 *
	 * @return bool
	 */
	function evt_code_snippets_is_active(): bool {
		return function_exists( 'Code_Snippets\save_snippet' )
			&& function_exists( 'Code_Snippets\activate_snippet' )
			&& function_exists( 'Code_Snippets\get_snippets' )
			&& '' !== evt_code_snippets_model_class();
	}
}

if ( ! function_exists( 'evt_code_snippets_model_class' ) ) {
	/**
	 * Class name of a snippet, which moved namespace in Code Snippets 3.10.
	 *
	 * Checking only the old name made the sync skip itself with a warning that
	 * reads like a configuration problem, so the environment ran for a while with
	 * no snippets installed and the application simply was not there.
	 *
	 * @return string Fully qualified class name, or '' when the plugin is absent.
	 */
	function evt_code_snippets_model_class(): string {
		foreach ( array( 'Code_Snippets\Model\Snippet', 'Code_Snippets\Snippet' ) as $class ) {
			if ( class_exists( $class ) ) {
				return $class;
			}
		}
		return '';
	}
}

if ( ! function_exists( 'evt_force_activate_snippet' ) ) {
	/**
	 * Mark a snippet active in the table, bypassing the plugin's validator.
	 *
	 * Development only, and only as a fallback: the validator rejecting a snippet
	 * that PHP itself parses fine is a Code Snippets limitation, not a problem in
	 * the code, and an environment nobody can provision is worse than a warning.
	 *
	 * @param int $id Snippet ID.
	 * @return bool Whether the row was updated.
	 */
	function evt_force_activate_snippet( int $id ): bool {
		global $wpdb;
		if ( $id <= 0 ) {
			return false;
		}
		// The plugin's own accessors keep moving between versions (3.10 dropped
		// code_snippets() and renamed the Snippet class), so the table name is
		// built the way the plugin has always named it.
		//
		// En un subsitio de un multisitio `$wpdb->prefix` ya es `wp_<N>_`, que es
		// la tabla del sitio: la correcta, porque el aplicativo se instala en el
		// subsitio y no en la red. Los snippets activados en red viven en
		// `wp_ms_snippets` y esto no los toca a propósito.
		$table = $wpdb->prefix . 'snippets';
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return false;
		}
		$done = $wpdb->update( $table, array( 'active' => 1 ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );
		// phpcs:enable
		if ( function_exists( 'Code_Snippets\clean_snippets_cache' ) ) {
			\Code_Snippets\clean_snippets_cache( $table );
		}
		return false !== $done;
	}
}

if ( ! function_exists( 'evt_parse_snippet_header' ) ) {
	/**
	 * Parse the snippet file header (Snippet Name / Description / Scope / Priority).
	 *
	 * The header follows the plugin-header style, e.g.:
	 *
	 *     / **
	 *      * Snippet Name: EVT — Roles y perfiles
	 *      * Description: ...
	 *      * Scope: global
	 *      * Priority: 5
	 *      * /
	 *
	 * @param string $code Full contents of the snippet file.
	 * @return array{name:string,desc:string,scope:string,priority:int} Parsed header with defaults applied.
	 */
	function evt_parse_snippet_header( string $code ): array {
		$header = array(
			'name'     => '',
			'desc'     => '',
			'scope'    => 'global',
			'priority' => 10,
		);

		$labels = array(
			'name'     => 'Snippet Name',
			'desc'     => 'Description',
			'scope'    => 'Scope',
			'priority' => 'Priority',
		);

		foreach ( $labels as $key => $label ) {
			if ( preg_match( '/^[ \t\/*#@]*' . preg_quote( $label, '/' ) . ':\s*(.+)$/mi', $code, $matches ) ) {
				$header[ $key ] = trim( $matches[1] );
			}
		}

		$header['priority'] = (int) $header['priority'];

		if ( '' === $header['scope'] ) {
			$header['scope'] = 'global';
		}

		return $header;
	}
}

if ( ! function_exists( 'evt_strip_php_tags' ) ) {
	/**
	 * Remove the opening `<?php` tag and any trailing close tag from snippet code.
	 *
	 * Code Snippets stores snippet code without PHP tags; save_snippet() also
	 * strips them, but we normalise here so the stored code is predictable.
	 *
	 * @param string $code Full contents of the snippet file.
	 * @return string Code ready to store in the snippets table.
	 */
	function evt_strip_php_tags( string $code ): string {
		$code = (string) preg_replace( '/^\s*<\?php\s*/', '', $code );
		$code = (string) preg_replace( '/\?>\s*$/', '', $code );

		return trim( $code ) . "\n";
	}
}

if ( ! function_exists( 'evt_snippet_managed_state' ) ) {
	/**
	 * Build the canonical, comparable state of a snippet's repository-managed fields.
	 *
	 * Both sides of a comparison — the file on disk and the row already in the
	 * table — go through this same normalisation before they are compared or
	 * hashed (`evt_snippet_fingerprint()`), so a difference that means nothing
	 * (an extra `<?php`, a trailing newline, tags in another order) never reads
	 * as a real change. See ADR-0035.
	 *
	 * @param string        $code     Snippet code, with or without PHP tags.
	 * @param string        $desc     Snippet description.
	 * @param string        $scope    Snippet scope.
	 * @param int           $priority Snippet priority.
	 * @param array<string> $tags     Snippet tags, in any order.
	 * @return array{code:string,desc:string,scope:string,priority:int,tags:array<string>} Canonical managed state.
	 */
	function evt_snippet_managed_state( string $code, string $desc, string $scope, int $priority, array $tags ): array {
		$tags = array_values( array_unique( array_map( 'strval', $tags ) ) );
		sort( $tags, SORT_STRING );

		return array(
			'code'     => evt_strip_php_tags( $code ),
			'desc'     => $desc,
			'scope'    => $scope,
			'priority' => $priority,
			'tags'     => $tags,
		);
	}
}

if ( ! function_exists( 'evt_snippet_fingerprint' ) ) {
	/**
	 * SHA-256 fingerprint of a snippet's managed state.
	 *
	 * A deterministic JSON encoding — fixed keys, no ambiguous concatenation —
	 * of the array `evt_snippet_managed_state()` returns. Two states with the
	 * same fingerprint are, for sync purposes, the same snippet; this is what
	 * decides `updated` versus `unchanged`, never the EVT application version.
	 *
	 * @param array{code:string,desc:string,scope:string,priority:int,tags:array<string>} $managed_state As returned by evt_snippet_managed_state().
	 * @return string Hex-encoded SHA-256 hash.
	 */
	function evt_snippet_fingerprint( array $managed_state ): string {
		return hash( 'sha256', (string) wp_json_encode( $managed_state, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
	}
}

if ( ! function_exists( 'evt_activate_snippet_with_fallback' ) ) {
	/**
	 * Activate a snippet, falling back to a forced activation on the plugin's own rejection.
	 *
	 * Shared by every path that ends in activation — creating, updating and
	 * reactivating an unchanged-but-inactive snippet all need the same
	 * fallback to `evt_force_activate_snippet()`.
	 *
	 * @param int $id Snippet ID.
	 * @return array{active:bool,error:string} Whether activation succeeded, and any warning/error message.
	 */
	function evt_activate_snippet_with_fallback( int $id ): array {
		// activate_snippet() returns the Snippet on success and an error
		// message string on failure (e.g. the code does not pass validation).
		$activation = \Code_Snippets\activate_snippet( $id );

		if ( ! is_string( $activation ) ) {
			return array(
				'active' => true,
				'error'  => '',
			);
		}

		// Code Snippets ≥ 3.10 validates by bare identifier name, ignoring
		// namespaces, and skips only `class` bodies. En este entorno de
		// desarrollo se activa igualmente: un entorno que nadie puede
		// provisionar es peor que un aviso. En producción el despliegue pasa
		// por la herramienta de sincronización, que sí mira el veredicto.
		if ( evt_force_activate_snippet( $id ) ) {
			return array(
				'active' => true,
				'error'  => 'activado saltando el validador de Code Snippets: ' . $activation,
			);
		}

		return array(
			'active' => false,
			'error'  => $activation,
		);
	}
}

if ( ! function_exists( 'evt_sync_snippets_from_dir' ) ) {
	/**
	 * Sync every *.php file in a directory into the Code Snippets table.
	 *
	 * Existing snippets are matched by exact name (the `Snippet Name:` header),
	 * so the sync is idempotent: re-running it updates instead of duplicating.
	 * An existing snippet is only saved again when its managed state actually
	 * changed (`evt_snippet_fingerprint()`); an unchanged-but-inactive snippet
	 * is only reactivated, never re-saved. After a save, the snippet is only
	 * activated when the state save_snippet() returns is not active already.
	 * A locked snippet whose code differs is reported as an error instead of
	 * being saved, because Code Snippets would silently keep the stored code.
	 * See ADR-0035.
	 *
	 * Result entries are keyed by file basename and contain:
	 * - name        (string) Snippet name from the header.
	 * - id          (int)    Snippet ID in the table (0 on save failure).
	 * - status      (string) 'created' | 'updated' | 'unchanged' | 'error'.
	 * - active      (bool)   Whether the snippet ends up active.
	 * - error       (string) Error message when something failed (in Spanish, UI-facing).
	 * - reactivated (bool)   Whether an unchanged-but-inactive snippet was actually reactivated.
	 *
	 * @param string $dir Absolute path to the directory holding the snippet files.
	 * @return array<string,array<string,mixed>> Result per snippet file; empty when Code Snippets is not active.
	 */
	function evt_sync_snippets_from_dir( string $dir ): array {
		$results = array();

		if ( ! evt_code_snippets_is_active() ) {
			return $results;
		}

		$files = glob( rtrim( $dir, '/' ) . '/*.php' );

		if ( false === $files ) {
			$files = array();
		}

		// Index the existing snippets by exact name.
		$existing = array();

		foreach ( \Code_Snippets\get_snippets() as $snippet ) {
			$existing[ (string) $snippet->name ] = $snippet;
		}

		foreach ( $files as $file ) {
			$basename = basename( $file );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local repo file read in a CLI/dev context.
			$code = file_get_contents( $file );

			if ( false === $code ) {
				$results[ $basename ] = array(
					'name'        => '',
					'id'          => 0,
					'status'      => 'error',
					'active'      => false,
					'error'       => 'No se pudo leer el fichero.',
					'reactivated' => false,
				);
				continue;
			}

			$header = evt_parse_snippet_header( $code );

			if ( '' === $header['name'] ) {
				$results[ $basename ] = array(
					'name'        => '',
					'id'          => 0,
					'status'      => 'error',
					'active'      => false,
					'error'       => 'Falta la cabecera "Snippet Name:" en el fichero.',
					'reactivated' => false,
				);
				continue;
			}

			$args = array(
				'name'     => $header['name'],
				'desc'     => $header['desc'],
				'code'     => evt_strip_php_tags( $code ),
				'tags'     => array( 'evt' ),
				'scope'    => $header['scope'],
				'priority' => $header['priority'],
			);

			$existing_snippet = $existing[ $header['name'] ] ?? null;

			// Existing and unchanged: never re-save. Saving rewrites the row,
			// updates `modified`, deactivates/reactivates the snippet, re-runs
			// its validation (which executes the code) and clears the snippet
			// caches — none of which represents an actual change. See ADR-0035.
			if ( null !== $existing_snippet ) {
				$desired_fingerprint = evt_snippet_fingerprint(
					evt_snippet_managed_state( $args['code'], $args['desc'], $args['scope'], (int) $args['priority'], $args['tags'] )
				);
				$current_fingerprint = evt_snippet_fingerprint(
					evt_snippet_managed_state(
						(string) $existing_snippet->code,
						(string) $existing_snippet->desc,
						(string) $existing_snippet->scope,
						(int) $existing_snippet->priority,
						(array) $existing_snippet->tags
					)
				);

				if ( hash_equals( $current_fingerprint, $desired_fingerprint ) ) {
					if ( $existing_snippet->active ) {
						$results[ $basename ] = array(
							'name'        => $header['name'],
							'id'          => (int) $existing_snippet->id,
							'status'      => 'unchanged',
							'active'      => true,
							'error'       => '',
							'reactivated' => false,
						);
						continue;
					}

					$activation           = evt_activate_snippet_with_fallback( (int) $existing_snippet->id );
					$results[ $basename ] = array(
						'name'        => $header['name'],
						'id'          => (int) $existing_snippet->id,
						'status'      => 'unchanged',
						'active'      => $activation['active'],
						'error'       => $activation['error'],
						'reactivated' => $activation['active'],
					);
					continue;
				}

				// Un snippet bloqueado conserva su código pase lo que pase:
				// save_snippet() lo restaura desde la fila cuando el snippet
				// estaba bloqueado y sigue estándolo. Guardar aquí diría
				// «actualizado» dejando el código viejo en la tabla, y la
				// siguiente sincronización encontraría otra vez la misma
				// diferencia. Se informa del conflicto sin tocar nada —ni el
				// candado, ni el código, ni la metadatos gestionada, que si no
				// quedaría aplicada a medias— y se deja que lo desbloquee
				// quien corresponda.
				if ( $existing_snippet->locked
					&& evt_strip_php_tags( (string) $existing_snippet->code ) !== $args['code'] ) {
					$results[ $basename ] = array(
						'name'        => $header['name'],
						'id'          => (int) $existing_snippet->id,
						'status'      => 'error',
						'active'      => (bool) $existing_snippet->active,
						'error'       => 'El snippet está bloqueado en Code Snippets y su código difiere del repositorio; desbloquéelo para poder actualizarlo.',
						'reactivated' => false,
					);
					continue;
				}

				// Changed: apply only the managed fields onto the already-loaded
				// object instead of building a fresh Snippet from $args. A fresh
				// object gets Snippet's defaults for everything else — active,
				// locked, condition_id, revision, cloud_id — and save_snippet()
				// writes those defaults straight over whatever the row had.
				$existing_snippet->set_fields( $args );
				$snippet = $existing_snippet;
			} else {
				// Always build a Snippet object: save_snippet() reads properties
				// before converting plain arrays, which warns on PHP 8.
				$class   = evt_code_snippets_model_class();
				$snippet = new $class( $args );
			}

			$saved = \Code_Snippets\save_snippet( $snippet );

			if ( ! $saved || ! $saved->id ) {
				$results[ $basename ] = array(
					'name'        => $header['name'],
					'id'          => 0,
					'status'      => 'error',
					'active'      => false,
					'error'       => 'No se pudo guardar el snippet en la base de datos.',
					'reactivated' => false,
				);
				continue;
			}

			// Manda el estado que devuelve el guardado, no el de antes:
			// save_snippet() revalida el código de un snippet activo y lo
			// desactiva si la validación falla, así que un `updated` puede
			// salir inactivo y hay que recuperarlo. Al revés también importa:
			// activar uno que ya está activo es un UPDATE de cero filas, que
			// Code Snippets da por fallido, y saldría un aviso de activación
			// que no corresponde a ningún problema.
			$activation = $saved->active
				? array(
					'active' => true,
					'error'  => '',
				)
				: evt_activate_snippet_with_fallback( (int) $saved->id );

			$results[ $basename ] = array(
				'name'        => $header['name'],
				'id'          => (int) $saved->id,
				'status'      => null !== $existing_snippet ? 'updated' : 'created',
				'active'      => $activation['active'],
				'error'       => $activation['error'],
				'reactivated' => false,
			);
		}

		return $results;
	}
}
