<?php
/**
 * Catalog adapter providing centres from local cache to evt_centres filter.
 *
 * @package Evt
 */

namespace Evt\Centre;

/**
 * Conecta el catálogo local cacheado de centros con el filtro evt_centres.
 *
 * Mantiene el desacoplamiento: las pantallas del formulario de inscripción
 * solo consultan el filtro evt_centres y reciben un mapa [ codigo => denominacion ]
 * con los centros activos.
 */
final class CentreCatalog {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'evt_centres', array( self::class, 'provide_centres' ), 5, 1 );
	}

	/**
	 * Provide centres from the local cached catalogue if no other provider has answered.
	 *
	 * @param array<string|int, string> $centres Centres provided so far.
	 * @return array<string|int, string>
	 */
	public static function provide_centres( array $centres ): array {
		if ( array() !== $centres ) {
			return $centres;
		}

		return CentreCatalogue::active_options();
	}
}
