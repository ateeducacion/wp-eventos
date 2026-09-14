<?php
/**
 * Idempotent role registration for local provision (wp eval-file).
 *
 * Relies on the roles snippet functions after Code Snippets has activated
 * them, or loads the versioned file directly if functions are missing.
 *
 * @package Evt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$evt_snippet = dirname( __DIR__ ) . '/snippets/roles-and-profiles.php';
if ( ! function_exists( 'evt_register_roles' ) && is_readable( $evt_snippet ) ) {
	require_once $evt_snippet;
}

if ( ! function_exists( 'evt_register_roles' ) ) {
	throw new RuntimeException( 'No está disponible evt_register_roles().' );
}

evt_register_roles();

// Las capacidades de los tres tipos de contenido las reparte el aplicativo en
// `init` prioridad 11. En la primera provisión los roles todavía no existían
// cuando pasó ese `init`, así que se vuelve a llamar aquí: es idempotente y
// aditiva, y así los roles salen completos en la misma ejecución.
if ( class_exists( '\\Evt\\PostType\\EventPostType' ) ) {
	\Evt\PostType\EventPostType::grant_caps_to_roles();
} else {
	echo "AVISO: el bundle del aplicativo no está cargado; los roles quedan sin las capacidades de los tipos de contenido.\n";
}

$evt_slugs = function_exists( 'evt_role_slugs' ) ? evt_role_slugs() : array();
echo esc_html( 'Roles EVT asegurados: ' . implode( ', ', $evt_slugs ) ) . "\n";
foreach ( $evt_slugs as $evt_slug ) {
	$evt_role = get_role( $evt_slug );
	if ( ! $evt_role ) {
		throw new RuntimeException( esc_html( 'No se pudo crear el rol ' . $evt_slug . '.' ) );
	}
	// translators: 1: role slug, 2: capability count.
	echo esc_html( sprintf( '- %s: OK (%d caps)', $evt_slug, count( $evt_role->capabilities ) ) ) . "\n";
}
