<?php
/**
 * Sync the versioned snippets/*.php files into the Code Snippets plugin.
 *
 * Runs under `wp eval-file` (Docker) and under Playground `runPHP`/require —
 * it must never depend on WP_CLI. Idempotent: existing snippets (matched by
 * exact name) are updated, never duplicated.
 *
 * Usage:
 *   npx wp-env run cli wp eval-file wp-content/evt-dev/scripts/sync-snippets.php
 *
 * @package Evt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/lib/snippet-sync.php';

if ( ! evt_code_snippets_is_active() ) {
	throw new RuntimeException( 'El plugin Code Snippets no está activo; no se pueden sincronizar los snippets.' );
}

$evt_snippets_dir = dirname( __DIR__ ) . '/snippets';

echo esc_html( sprintf( 'Sincronizando snippets desde %s ...', $evt_snippets_dir ) ) . "\n";

$evt_results = evt_sync_snippets_from_dir( $evt_snippets_dir );

if ( empty( $evt_results ) ) {
	throw new RuntimeException( 'No se ha encontrado ningún fichero de snippet que sincronizar.' );
}

$evt_created = 0;
$evt_updated = 0;
$evt_errors  = 0;

foreach ( $evt_results as $evt_file => $evt_result ) {
	if ( 'error' === $evt_result['status'] ) {
		++$evt_errors;
		echo esc_html( sprintf( '- %s: ERROR — %s', $evt_file, $evt_result['error'] ) ) . "\n";
		continue;
	}

	if ( 'created' === $evt_result['status'] ) {
		++$evt_created;
		$evt_action = 'creado';
	} else {
		++$evt_updated;
		$evt_action = 'actualizado';
	}

	if ( $evt_result['active'] ) {
		$evt_state = '' === $evt_result['error']
			? 'activado'
			: sprintf( 'activado con AVISO — %s', $evt_result['error'] );
	} else {
		++$evt_errors;
		$evt_state = sprintf( 'ERROR al activar: %s', $evt_result['error'] );
	}

	echo esc_html(
		sprintf(
			'- %1$s → «%2$s» (ID %3$d): %4$s, %5$s',
			$evt_file,
			$evt_result['name'],
			$evt_result['id'],
			$evt_action,
			$evt_state
		)
	) . "\n";
}

echo esc_html(
	sprintf(
		'Resumen: %1$d creado(s), %2$d actualizado(s), %3$d error(es).',
		$evt_created,
		$evt_updated,
		$evt_errors
	)
) . "\n";

if ( $evt_errors > 0 ) {
	throw new RuntimeException( 'La sincronización de snippets terminó con errores; revise el resumen anterior.' );
}
