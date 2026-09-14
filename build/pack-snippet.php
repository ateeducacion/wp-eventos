<?php
/**
 * Bundle src/Evt into a single Code Snippets-compatible PHP file.
 *
 * Usage (host):
 *   php build/pack-snippet.php
 *
 * Writes: snippets/evt-eventos-app.bundle.php
 *
 * @package Evt
 */

$root    = dirname( __DIR__ );
$src_dir = $root . '/src/Evt';
$out     = $root . '/snippets/evt-eventos-app.bundle.php';

// The changelog's top version heading is the single source of truth.
$changelog = (string) file_get_contents( $root . '/CHANGELOG.md' );
if ( ! preg_match( '/^## \[(\d+\.\d+\.\d+)\]/m', $changelog, $evt_version ) ) {
	fwrite( STDERR, "No version heading (## [X.Y.Z]) found in CHANGELOG.md\n" );
	exit( 1 );
}

$order = require $src_dir . '/load-order.php';

// Guard: every source file must be in the load order, or it silently would not ship.
$ignored = array( 'bootstrap.php', 'load-order.php' );
$files   = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $src_dir, FilesystemIterator::SKIP_DOTS ) );
foreach ( $files as $file ) {
	if ( 'php' !== $file->getExtension() ) {
		continue;
	}
	$rel = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $src_dir ) + 1 ) );
	if ( ! in_array( $rel, $ignored, true ) && ! in_array( $rel, $order, true ) ) {
		fwrite( STDERR, "Not in src/Evt/load-order.php: {$rel}\n" );
		exit( 1 );
	}
}

$header = <<<'HDR'
<?php
/**
 * Snippet Name: EVT — Aplicativo de eventos (CPT)
 * Description: CPT evt_event jerárquico (el evento y sus páginas satélite), evt_speaker y evt_activity, taxonomías evt_area / evt_type / evt_course, el acotado por área y las pantallas propias del aplicativo (portada, listado, taller del evento, formulario de sección y vista pública). Código generado desde src/Evt — no editar a mano; ejecutar php build/pack-snippet.php.
 * Scope: global
 * Priority: 15
 *
 * @package Evt
 * @version {{VERSION}}
 */

// phpcs:disable

HDR;

$header = str_replace( '{{VERSION}}', $evt_version[1], $header );

$body = '';
foreach ( $order as $rel ) {
	$path = $src_dir . '/' . $rel;
	if ( ! is_readable( $path ) ) {
		fwrite( STDERR, "Missing: {$path}\n" );
		exit( 1 );
	}
	$code = file_get_contents( $path );
	if ( false === $code ) {
		fwrite( STDERR, "Cannot read {$path}\n" );
		exit( 1 );
	}
	// Strip opening PHP tag from each unit.
	$code = preg_replace( '/^\s*<\?php\s*/', '', $code );
	// Code Snippets re-evaluates an active snippet when it is saved. PHP wants
	// `namespace` first, so the guard goes right after the first one. It checks
	// a constant, not class_exists( App ): parentless classes are early-bound
	// while the eval()'d code compiles, so App would already exist on the first
	// pass and the bundle would return before booting anything.
	if ( $rel === $order[0] ) {
		$code = preg_replace(
			'/^namespace [^;]+;/m',
			"$0\n\n// Code Snippets vuelve a evaluar un snippet activo al guardarlo: si el\n// bundle ya se cargó hay que salir, o el segundo eval() muere redeclarando\n// clases. Una constante, porque class_exists() ya es cierto al compilar.\nif ( \\defined( 'EVT_BUNDLE_LOADED' ) ) {\n\treturn;\n}\n\\define( 'EVT_BUNDLE_LOADED', true );",
			(string) $code,
			1,
			$n
		);
		if ( 1 !== $n ) {
			fwrite( STDERR, "First unit must start with a namespace: {$rel}\n" );
			exit( 1 );
		}
	}
	$body .= "\n// ---- src/Evt/{$rel} ----\n";
	$body .= trim( $code ) . "\n";
}

// Los assets viajan dentro del bundle: en producción no hay repositorio en
// disco ni URL de plugin, así que un `src` registrado daría 404 y el aplicativo
// se vería sin hoja de estilos. Véase src/Evt/PublicFront/Assets.php.
// La lista sale del directorio y no de aquí: escrita a mano, un fichero nuevo se
// olvida, `Assets::contents()` devuelve cadena vacía y el guion sencillamente no
// sale en la página, sin error que lo delate. El orden de este mapa da igual: es
// una búsqueda por ruta, y quien decide en qué orden se cargan es
// `Assets::register_assets()`.
$assets = array();
foreach ( array( 'css', 'js' ) as $tipo ) {
	$encontrados = glob( $root . '/assets/' . $tipo . '/*.' . $tipo );
	if ( false === $encontrados ) {
		fwrite( STDERR, "Cannot list assets/{$tipo}\n" );
		exit( 1 );
	}
	foreach ( $encontrados as $path ) {
		$assets[] = $tipo . '/' . basename( $path );
	}
}
sort( $assets );

$inlined = array();
foreach ( $assets as $rel ) {
	$path = $root . '/assets/' . $rel;
	$data = is_readable( $path ) ? file_get_contents( $path ) : false;
	if ( false === $data ) {
		fwrite( STDERR, "Cannot read asset: {$path}\n" );
		exit( 1 );
	}
	$inlined[ $rel ] = $data;
}

$body .= "\n// ---- assets/ (inlined) ----\n";
$body .= '\Evt\PublicFront\Assets::set_inline( ' . var_export( $inlined, true ) . " );\n";

$footer = <<<'FTR'

if ( \class_exists( \Evt\App::class ) ) {
	\Evt\App::boot();
}

// phpcs:enable

FTR;

if ( false === file_put_contents( $out, $header . $body . $footer ) ) {
	fwrite( STDERR, "Cannot write {$out}\n" );
	exit( 1 );
}

// The test suite loads the modular sources, never the bundle: a per-file legal
// `declare(strict_types=1)` becomes a fatal once concatenated. Lint the result.
$lint   = array();
$status = 0;
// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec -- Build script, host-only.
exec( escapeshellarg( PHP_BINARY ) . ' -l ' . escapeshellarg( $out ) . ' 2>&1', $lint, $status );
if ( 0 !== $status ) {
	fwrite( STDERR, implode( "\n", $lint ) . "\nBundle is not valid PHP; fix the source and rebuild.\n" );
	exit( 1 );
}

fwrite( STDOUT, "Wrote {$out} (" . filesize( $out ) . " bytes, syntax OK)\n" );
