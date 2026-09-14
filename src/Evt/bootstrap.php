<?php
/**
 * Load modular EVT events application classes (development includes).
 *
 * The load order lives in load-order.php, shared with build/pack-snippet.php.
 * The Code Snippets bundle inlines the same files in that same order.
 *
 * @package Evt
 */

if ( ! defined( 'EVT_SRC_DIR' ) ) {
	define( 'EVT_SRC_DIR', __DIR__ );
}

// When the Code Snippets bundle already defined the classes, skip file loads
// (require_once is path-based; the bundle is a different file and would redeclare).
if ( ! class_exists( \Evt\App::class, false ) ) {
	$evt_app_files = require __DIR__ . '/load-order.php';

	foreach ( $evt_app_files as $evt_app_file ) {
		$evt_app_path = EVT_SRC_DIR . '/' . $evt_app_file;
		if ( is_readable( $evt_app_path ) ) {
			require_once $evt_app_path;
		}
	}
}

if ( class_exists( \Evt\App::class ) ) {
	\Evt\App::boot();
}
