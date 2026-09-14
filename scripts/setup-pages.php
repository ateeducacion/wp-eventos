<?php
/**
 * Snippet Name: EVT — Crear las páginas del aplicativo (una sola vez)
 * Description: Crea las páginas de las pantallas del aplicativo, cada una con su shortcode, y las cuelga de la página madre que se indique. Idempotente: a la que ya existe sin su shortcode se lo añade al final, y a la que lo tiene no la toca. En producción se pega en Code Snippets como snippet de ejecución única; en local lo llama `make provision`.
 *
 * Una sola lista de páginas, y no está aquí: los slugs son los de
 * `Shell::SLUGS` y los shortcodes los de `Shell::SHORTCODES`, que es lo que el
 * aplicativo resuelve luego con `get_page_by_path()`. Escribirlos otra vez en
 * este fichero sería una segunda fuente de verdad que se desincroniza en
 * silencio: la pantalla dejaría de tener página y su pestaña desaparecería sin
 * un solo error. Aquí solo se pone el título, que es lo único que el armazón no
 * sabe.
 *
 * La madre se guarda en la opción `evt_pages_parent`, que es de donde
 * `App::page_slug()` saca la ruta al construir los enlaces.
 *
 * @package Evt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─── Editar antes de pegarlo en Code Snippets ────────────────────────────────
// Página de la que cuelgan las del aplicativo. En el subsitio «eventos» lo
// normal es dejarlo vacío: las páginas van en la raíz del subsitio.
$evt_padre = '';

// El entorno local lo pasa por variable de entorno; en Code Snippets no la hay.
$evt_padre = '' !== $evt_padre ? $evt_padre : (string) getenv( 'EVT_PAGES_PARENT' );
$evt_padre = trim( $evt_padre, '/' );

/**
 * Page titles of every screen, by section key of `Shell::SLUGS`.
 *
 * @return array<string, string>
 */
function evt_page_titles(): array {
	return array(
		'home'     => 'Gestión de eventos',
		'events'   => 'Mis eventos',
		'event'    => 'Evento',
		'section'  => 'Sección',
		'speakers' => 'Ponentes del evento',
	);
}

/**
 * Create, complete and hang the application pages.
 *
 * @param string $evt_padre Parent page slug; empty for root pages.
 * @return void
 * @throws RuntimeException If the application is not loaded, the parent is missing or a page cannot be saved.
 */
function evt_setup_pages( string $evt_padre ): void {
	if ( ! class_exists( '\Evt\PublicFront\Shell' ) ) {
		throw new RuntimeException( 'El aplicativo no está cargado (falta Evt\PublicFront\Shell). Ejecute antes `make bundle && make sync-snippets`.' );
	}

	$evt_padre_id = 0;
	if ( '' !== $evt_padre ) {
		$evt_madre = get_page_by_path( $evt_padre );
		if ( ! $evt_madre instanceof WP_Post ) {
			throw new RuntimeException( esc_html( "No existe la página «{$evt_padre}»: créala antes o deja la madre vacía." ) );
		}
		$evt_padre_id = (int) $evt_madre->ID;
	}

	$evt_titulos     = evt_page_titles();
	$evt_creadas     = 0;
	$evt_habia       = 0;
	$evt_completadas = 0;
	$evt_saltadas    = 0;

	foreach ( \Evt\PublicFront\Shell::SLUGS as $evt_seccion => $evt_slug ) {
		$evt_shortcode = \Evt\PublicFront\Shell::SHORTCODES[ $evt_seccion ] ?? '';

		// Una pantalla que todavía no existe no tiene página: lo que se
		// publicaría es una página con el corchete del shortcode escrito a la
		// vista. Cuando la clase entre en el bundle, la siguiente provisión la
		// crea sola.
		if ( '' === $evt_shortcode || ! shortcode_exists( $evt_shortcode ) ) {
			echo esc_html( "La pantalla «{$evt_seccion}» no está en el aplicativo todavía: no se crea «{$evt_slug}»." ) . "\n";
			++$evt_saltadas;
			continue;
		}

		$evt_ruta = '' !== $evt_padre ? $evt_padre . '/' . $evt_slug : $evt_slug;
		$evt_hay  = get_page_by_path( $evt_ruta );

		if ( $evt_hay instanceof WP_Post && has_shortcode( (string) $evt_hay->post_content, $evt_shortcode ) ) {
			// La que ya está con su shortcode no se toca: puede llevar texto
			// propio alrededor, y esto se ejecuta también en producción.
			echo esc_html( "«{$evt_ruta}» ya existía (ID {$evt_hay->ID})." ) . "\n";
			++$evt_habia;
			continue;
		}

		if ( $evt_hay instanceof WP_Post ) {
			// Existe pero sin su shortcode —una página creada a mano como
			// marcador—: se le añade al final, sin tocar lo que ya tuviera.
			$evt_id = wp_update_post(
				array(
					'ID'           => $evt_hay->ID,
					'post_content' => rtrim( (string) $evt_hay->post_content ) . "\n\n<!-- wp:shortcode -->\n[{$evt_shortcode}]\n<!-- /wp:shortcode -->",
				),
				true
			);
			if ( is_wp_error( $evt_id ) ) {
				throw new RuntimeException( esc_html( "No se pudo completar «{$evt_ruta}»: " . $evt_id->get_error_message() ) );
			}
			echo esc_html( "«{$evt_ruta}» existía sin su shortcode (ID {$evt_hay->ID}): se le añadió [{$evt_shortcode}]." ) . "\n";
			++$evt_completadas;
			continue;
		}

		$evt_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_name'    => $evt_slug,
				'post_title'   => $evt_titulos[ $evt_seccion ] ?? $evt_slug,
				'post_parent'  => $evt_padre_id,
				'post_content' => "<!-- wp:shortcode -->\n[{$evt_shortcode}]\n<!-- /wp:shortcode -->",
			),
			true
		);
		if ( is_wp_error( $evt_id ) ) {
			throw new RuntimeException( esc_html( "No se pudo crear «{$evt_ruta}»: " . $evt_id->get_error_message() ) );
		}

		echo esc_html( "Creada «{$evt_ruta}» (ID {$evt_id}) con [{$evt_shortcode}]." ) . "\n";
		++$evt_creadas;
	}

	// El aplicativo construye sus enlaces con esta opción (App::page_slug()):
	// sin ella buscaría las páginas en la raíz y no las encontraría.
	update_option( \Evt\App::PAGES_PARENT, $evt_padre );

	echo esc_html(
		sprintf(
			'Páginas del aplicativo: %1$d creada(s), %2$d completada(s) con su shortcode, %3$d ya estaban, %4$d sin pantalla todavía. Madre: %5$s.',
			$evt_creadas,
			$evt_completadas,
			$evt_habia,
			$evt_saltadas,
			'' !== $evt_padre ? '/' . $evt_padre . '/' : 'la raíz del sitio'
		)
	) . "\n";

	// Las reglas de reescritura se guardaron al instalar WordPress, cuando el
	// aplicativo todavía no existía: sin refrescarlas, la URL pública de cada
	// evento —/evento/<slug>/, la que enseña el botón «Ver la página»— responde
	// «Página no encontrada» hasta que alguien entre en Ajustes → Enlaces
	// permanentes. Aquí es donde toca: acaban de cambiar las páginas del
	// aplicativo y ya están registrados los tres tipos de contenido.
	flush_rewrite_rules( true );
	echo "Reglas de enlaces permanentes regeneradas.\n";
}

// En Code Snippets esto corre antes de `init`, y crear páginas tan pronto
// revienta: los `wp_insert_post` disparan ganchos de otros plugins que todavía
// no se han enganchado. Con `wp eval-file` init ya pasó, así que se ejecuta al
// momento.
if ( did_action( 'init' ) ) {
	evt_setup_pages( $evt_padre );
} else {
	add_action(
		'init',
		static function () use ( $evt_padre ) {
			evt_setup_pages( $evt_padre );
		},
		20
	);
}
