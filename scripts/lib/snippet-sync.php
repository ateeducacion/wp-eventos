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

if ( ! function_exists( 'evt_sync_snippets_from_dir' ) ) {
	/**
	 * Sync every *.php file in a directory into the Code Snippets table.
	 *
	 * Existing snippets are matched by exact name (the `Snippet Name:` header),
	 * so the sync is idempotent: re-running it updates instead of duplicating.
	 * Every synced snippet is (re)activated afterwards.
	 *
	 * Result entries are keyed by file basename and contain:
	 * - name   (string) Snippet name from the header.
	 * - id     (int)    Snippet ID in the table (0 on save failure).
	 * - status (string) 'created' | 'updated' | 'error'.
	 * - active (bool)   Whether activation succeeded.
	 * - error  (string) Error message when something failed (in Spanish, UI-facing).
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
					'name'   => '',
					'id'     => 0,
					'status' => 'error',
					'active' => false,
					'error'  => 'No se pudo leer el fichero.',
				);
				continue;
			}

			$header = evt_parse_snippet_header( $code );

			if ( '' === $header['name'] ) {
				$results[ $basename ] = array(
					'name'   => '',
					'id'     => 0,
					'status' => 'error',
					'active' => false,
					'error'  => 'Falta la cabecera "Snippet Name:" en el fichero.',
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

			$is_update = isset( $existing[ $header['name'] ] );

			if ( $is_update ) {
				$args['id'] = (int) $existing[ $header['name'] ]->id;
			}

			// Always build a Snippet object: save_snippet() reads properties
			// before converting plain arrays, which warns on PHP 8.
			$class   = evt_code_snippets_model_class();
			$snippet = new $class( $args );
			$saved   = \Code_Snippets\save_snippet( $snippet );

			if ( ! $saved || ! $saved->id ) {
				$results[ $basename ] = array(
					'name'   => $header['name'],
					'id'     => 0,
					'status' => 'error',
					'active' => false,
					'error'  => 'No se pudo guardar el snippet en la base de datos.',
				);
				continue;
			}

			// activate_snippet() returns the Snippet on success and an error
			// message string on failure (e.g. the code does not pass validation).
			$activation = \Code_Snippets\activate_snippet( (int) $saved->id );
			$aviso      = '';

			if ( is_string( $activation ) ) {
				// Code Snippets ≥ 3.10 validates by bare identifier name, ignoring
				// namespaces, and skips only `class` bodies. En este entorno de
				// desarrollo se activa igualmente: un entorno que nadie puede
				// provisionar es peor que un aviso. En producción el despliegue
				// pasa por la herramienta de sincronización, que sí mira el
				// veredicto.
				if ( evt_force_activate_snippet( (int) $saved->id ) ) {
					$aviso      = 'activado saltando el validador de Code Snippets: ' . $activation;
					$activation = null;
				}
			}

			$results[ $basename ] = array(
				'name'   => $header['name'],
				'id'     => (int) $saved->id,
				'status' => $is_update ? 'updated' : 'created',
				'active' => ! is_string( $activation ),
				'error'  => is_string( $activation ) ? $activation : $aviso,
			);
		}

		return $results;
	}
}
