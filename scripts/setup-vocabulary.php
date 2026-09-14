<?php
/**
 * Snippet Name: EVT — Vocabulario base del aplicativo (una sola vez)
 * Description: Deja creados los términos de las tres taxonomías del aplicativo: áreas organizadoras, tipologías y cursos escolares. Idempotente: crea el término que falta por su slug y no toca el que ya está. En producción se pega en Code Snippets como snippet de ejecución única; en local lo llama `make provision`.
 *
 * Aquí las páginas del aplicativo son los propios eventos: `evt_event` es
 * jerárquico y cada evento se crea con sus hijas (programa, ponentes,
 * inscripción…). Lo que sí tiene que existir antes de que nadie cree el primer
 * evento es el vocabulario que lo clasifica, y sobre todo `evt_area`: es el eje
 * de permisos, y sin términos nadie puede tener área en su perfil, así que
 * `EventAccess` cierra la puerta a todo el mundo (fail-closed).
 *
 * Los términos de aquí son **de ejemplo**: sirven para que el entorno local
 * tenga con qué trabajar. Cada instalación crea los suyos, y la migración los
 * casa con los que ya existan en su sitio (ADR-0030: el organigrama de una
 * organización no se versiona).
 *
 * @package Evt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base vocabulary of the three taxonomies: taxonomy => (slug => name).
 *
 * @return array<string, array<string, string>>
 */
function evt_base_vocabulary(): array {
	return array(
		// Áreas, servicios y direcciones generales que organizan eventos.
		'evt_area'   => array(
			'innovacion'             => 'Innovación',
			'lenguas-extranjeras'    => 'Lenguas Extranjeras',
			'comunicacion'           => 'Comunicación',
			'convivencia-escolar'    => 'Convivencia escolar',
			'competencia-matematica' => 'Fomento de la competencia matemática',
			'salud'                  => 'Salud',
			'steam'                  => 'STEAM',
		),
		// Tipología del evento: los cuatro términos que existen hoy.
		'evt_type'   => array(
			'jornadas'  => 'Jornadas',
			'encuentro' => 'Encuentro',
			'congreso'  => 'Congreso',
			'taller'    => 'Taller',
		),
		// Curso escolar. Solo los vivos: los históricos llegarán con la
		// migración, que es la que sabe cuáles hacen falta de verdad.
		'evt_course' => array(
			'2024-2025' => 'Curso 2024-2025',
			'2025-2026' => 'Curso 2025-2026',
			'2026-2027' => 'Curso 2026-2027',
		),
	);
}

/**
 * Create the missing terms of the three taxonomies.
 *
 * @return void
 * @throws RuntimeException If a taxonomy is not registered or a term cannot be created.
 */
function evt_setup_vocabulary(): void {
	$evt_creados = 0;
	$evt_habia   = 0;

	foreach ( evt_base_vocabulary() as $evt_taxonomy => $evt_terms ) {
		if ( ! taxonomy_exists( $evt_taxonomy ) ) {
			throw new RuntimeException( esc_html( "La taxonomía «{$evt_taxonomy}» no está registrada: el aplicativo no está cargado. Ejecute antes `make bundle && make sync-snippets`." ) );
		}

		foreach ( $evt_terms as $evt_slug => $evt_name ) {
			$evt_term = get_term_by( 'slug', $evt_slug, $evt_taxonomy );
			if ( $evt_term instanceof WP_Term ) {
				++$evt_habia;
				continue;
			}

			$evt_created = wp_insert_term( $evt_name, $evt_taxonomy, array( 'slug' => $evt_slug ) );
			if ( is_wp_error( $evt_created ) ) {
				throw new RuntimeException( esc_html( "No se pudo crear «{$evt_name}» en {$evt_taxonomy}: " . $evt_created->get_error_message() ) );
			}

			echo esc_html( "Creado «{$evt_name}» ({$evt_taxonomy}/{$evt_slug})." ) . "\n";
			++$evt_creados;
		}
	}

	echo esc_html( sprintf( 'Vocabulario del aplicativo: %d término(s) creado(s), %d ya estaban.', $evt_creados, $evt_habia ) ) . "\n";
}

// En Code Snippets esto corre antes de `init`, y las taxonomías se registran
// en `init` prioridad 9: hay que esperar. Con `wp eval-file` init ya pasó, así
// que se ejecuta al momento.
if ( did_action( 'init' ) ) {
	evt_setup_vocabulary();
} else {
	add_action( 'init', 'evt_setup_vocabulary', 20 );
}
