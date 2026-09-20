<?php
/**
 * WP-CLI command for educational centres catalogue synchronization.
 *
 * @package Evt
 */

namespace Evt\Centre;

use RuntimeException;
use WP_CLI;

/**
 * Gestiona la sincronización del catálogo de centros desde WP-CLI.
 */
final class CentreCli {

	/**
	 * Register the WP-CLI command if WP_CLI is available.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'evt centres sync', array( self::class, 'sync' ) );
		}
	}

	/**
	 * Synchronize educational centres from external source.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Force download even if SHA-256 is unchanged.
	 *
	 * ## EXAMPLES
	 *
	 *     wp evt centres sync
	 *     wp evt centres sync --force
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 * @return void
	 */
	public static function sync( array $args, array $assoc_args ): void {
		unset( $args );
		$force = isset( $assoc_args['force'] );

		WP_CLI::log( 'Iniciando sincronización del catálogo de centros...' );

		try {
			$result = CentreCatalogueSync::sync( $force );

			if ( 'unchanged' === $result['status'] ) {
				WP_CLI::success(
					sprintf(
						'El catálogo local ya está al día (%d centros, %d activos). SHA-256: %s',
						$result['records'],
						$result['active'],
						substr( $result['sha256'], 0, 12 ) . '…'
					)
				);
			} else {
				WP_CLI::success(
					sprintf(
						'Sincronización completada con éxito. %d centros almacenados (%d activos). SHA-256: %s',
						$result['records'],
						$result['active'],
						substr( $result['sha256'], 0, 12 ) . '…'
					)
				);
			}
		} catch ( RuntimeException $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}
}
