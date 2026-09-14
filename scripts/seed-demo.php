<?php
/**
 * Demo users and demo events for the development environment.
 *
 * Idempotente: crea lo que falta y repone siempre el rol y el área de cada
 * cuenta, y los datos, la clasificación y la apariencia de cada evento. Corre
 * bajo `wp eval-file` y bajo Playground `runPHP`, así que nunca depende de
 * WP_CLI.
 *
 * Esta es la fuente de verdad de la tabla de usuarios de prueba del README.
 *
 * Usage:
 *   npx wp-env run cli wp eval-file wp-content/evt-dev/scripts/seed-demo.php
 *
 * @package Evt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Demo accounts: login => role, área slugs and label.
 *
 * `organizacion` y `organizacion2` comparten área a propósito: es lo que
 * demuestra que la organización edita también lo de sus compañeras. Y
 * `organizacion3` está en otra área, que es lo que demuestra el acotado.
 *
 * `coordinacion` conserva el nombre del rol retirado el 2026-09-13, pero hoy es
 * una organización más: la que pertenece a DOS áreas a la vez, que es el único
 * caso que enseña el filtro por área del listado. No tiene ámbito completo.
 *
 * El que trabaja sobre todas las áreas es `admin`, el administrador que ya crea
 * wp-env: el aplicativo tiene un solo rol propio y la administración es la
 * nativa de WordPress, así que no hay ninguna cuenta más que sembrar.
 *
 * @return array<string, array{role:string, areas:string[], label:string}>
 */
function evt_demo_accounts(): array {
	return array(
		'coordinacion'  => array(
			'role'  => 'evt_organiser',
			'areas' => array( 'innovacion', 'convivencia-escolar' ),
			'label' => 'Coordinación (dos áreas)',
		),
		'organizacion'  => array(
			'role'  => 'evt_organiser',
			'areas' => array( 'innovacion' ),
			'label' => 'Organización (Innovación)',
		),
		'organizacion2' => array(
			'role'  => 'evt_organiser',
			'areas' => array( 'innovacion' ),
			'label' => 'Organización 2 (Innovación)',
		),
		'organizacion3' => array(
			'role'  => 'evt_organiser',
			'areas' => array( 'convivencia-escolar' ),
			'label' => 'Organización 3 (Convivencia escolar)',
		),
	);
}

/**
 * School course slug of a date, as `setup-vocabulary.php` names them.
 *
 * @param string $date Date in Y-m-d.
 * @return string
 */
function evt_demo_course( string $date ): string {
	$year = (int) substr( $date, 0, 4 );
	// El curso escolar arranca en septiembre: enero de 2026 sigue siendo 2025-2026.
	if ( (int) substr( $date, 5, 2 ) < 9 ) {
		--$year;
	}
	return $year . '-' . ( $year + 1 );
}

/**
 * Demo events: the root plus its satellite pages.
 *
 * Las fechas son relativas al día de hoy y no literales: con fechas fijas los
 * dos eventos acaban «finalizados» a los pocos meses y las pantallas se ven sin
 * un solo evento próximo ni abierto, que es justo lo que hay que enseñar.
 *
 * @return array<int, array<string, mixed>>
 */
function evt_demo_events(): array {
	$hoy = gmdate( 'Y-m-d' );

	// Uno por delante y otro ocurriendo ahora mismo: así se ven los dos estados
	// que `Domain/EventState` deriva de las fechas.
	$proximo_inicio = gmdate( 'Y-m-d', strtotime( $hoy . ' +45 days' ) );
	$proximo_fin    = gmdate( 'Y-m-d', strtotime( $hoy . ' +47 days' ) );
	$abierto_inicio = gmdate( 'Y-m-d', strtotime( $hoy . ' -1 day' ) );
	$abierto_fin    = gmdate( 'Y-m-d', strtotime( $hoy . ' +1 day' ) );

	return array(
		array(
			'slug'      => 'jornadas-tecnologia-educativa',
			'title'     => 'III Jornadas de Tecnología Educativa',
			'author'    => 'organizacion',
			'area'      => 'innovacion',
			'type'      => 'jornadas',
			'start'     => $proximo_inicio,
			'end'       => $proximo_fin,
			'venue'     => 'Centro de formación Norte',
			'meta'      => array(
				'evt_tagline'          => 'Aprender con tecnología, enseñar con criterio',
				'evt_hashtag'          => 'JornadasTE',
				'evt_intro'            => 'Tres días de talleres, ponencias y experiencias de aula sobre tecnología educativa en los centros educativos.',
				'evt_signup_show'      => true,
				'evt_signup_label'     => 'Inscríbete en las jornadas',
				'evt_signup_url'       => 'https://www.example.org/inscripcion-demo/',
				// La inscripción del aplicativo, abierta, con el plazo de
				// talleres también abierto y sus dos preguntas de logística:
				// es el caso que describe la ADR-0031.
				'evt_signup_open'      => true,
				'evt_workshop_open'    => true,
				'evt_consent_privacy'  => '<p>Texto de ejemplo: quién trata sus datos, para qué, cuánto tiempo y cómo ejercer sus derechos.</p>',
				'evt_consent_image'    => '<p>Texto de ejemplo: grabación de las sesiones y publicación de imágenes del evento.</p>',
				'evt_consent_version'  => 1,
				'evt_signup_questions' => EVT_DEMO_QUESTIONS,
				'evt_header_bg'        => '#12395b',
				'evt_header_text'      => '#ffffff',
				'evt_title_font'       => 'montserrat',
				'evt_body_font'        => 'open-sans',
				'evt_image_shape'      => 'circle',
				'evt_separator'        => 'wave',
			),
			'sections'  => array(
				array(
					'slug'    => 'programa',
					'title'   => 'Programa',
					'type'    => 'programa',
					'order'   => 1,
					'status'  => 'publish',
					'content' => 'Programa provisional de las tres jornadas, por salas y franjas horarias.',
				),
				array(
					'slug'    => 'ponentes',
					'title'   => 'Ponentes',
					'type'    => 'ponentes',
					'order'   => 2,
					'status'  => 'publish',
					'content' => 'Quiénes intervienen y desde dónde: docentes, asesorías y personas invitadas.',
				),
				array(
					'slug'    => 'inscripcion',
					'title'   => 'Inscripción',
					'type'    => 'inscripcion',
					'order'   => 3,
					'status'  => 'publish',
					'content' => 'Plazo de inscripción, plazas por taller y criterios de admisión.',
				),
				array(
					'slug'    => 'multimedia',
					'title'   => 'Multimedia',
					'type'    => 'multimedia',
					'order'   => 4,
					'status'  => 'publish',
					'content' => 'Fotografías, grabaciones y materiales de las ediciones anteriores.',
					// Una sección con apariencia propia: es lo que el plegado
					// «Apariencia de esta sección» del formulario deja tocar.
					'meta'    => array(
						'evt_header_bg'   => '#1f6f5c',
						'evt_header_text' => '#ffffff',
					),
				),
				array(
					'slug'    => 'contacto',
					'title'   => 'Contacto',
					'type'    => 'contacto',
					'order'   => 5,
					'status'  => 'publish',
					'content' => 'A quién escribir para cualquier duda sobre las jornadas.',
				),
				array(
					'slug'    => 'actividades-paralelas',
					'title'   => 'Actividades paralelas',
					'type'    => 'actividades',
					'order'   => 6,
					// En borrador a propósito: es lo que hace visible la
					// columna «Estado» y el botón de publicar del taller.
					'status'  => 'draft',
					'content' => 'Visitas, mesas de trabajo y actividades fuera del programa principal.',
				),
			),
			'speakers'  => array(
				array(
					'name' => 'Ana Martín Cabrera',
					'role' => 'Asesora de Tecnología Educativa',
					'org'  => 'Centro de formación Norte',
					'bio'  => 'Acompaña a los centros en la integración de la tecnología en el aula.',
				),
				array(
					'name' => 'Luis Gómez Perdomo',
					'role' => 'Profesor de Secundaria',
					'org'  => 'Instituto Sur',
					'bio'  => 'Coordina el proyecto de radio escolar del centro desde 2019.',
				),
				array(
					'name' => 'Marta Ruiz Santana',
					'role' => 'Maestra de Primaria',
					'org'  => 'CEIP El Drago',
					'bio'  => 'Trabaja con robótica educativa en el segundo ciclo.',
				),
			),
			// Dos sedes el mismo día, a propósito: es el caso que ADR-0024
			// decidió que tenía que caber y el que parte la parrilla en dos
			// bloques dentro del primer día.
			'programme' => array(
				array(
					'title' => 'Inauguración de las jornadas',
					'kind'  => 'inauguracion',
					'date'  => $proximo_inicio,
					'start' => '09:30',
					'end'   => '10:00',
					'venue' => 'Centro de formación Norte',
					'room'  => 'Salón de actos',
				),
				array(
					'title'    => 'Enseñar con criterio en un aula con pantallas',
					'kind'     => 'ponencia',
					'date'     => $proximo_inicio,
					'start'    => '10:00',
					'end'      => '11:30',
					'venue'    => 'Centro de formación Norte',
					'room'     => 'Salón de actos',
					'speakers' => array( 'Ana Martín Cabrera' ),
					'summary'  => 'Qué decide de verdad el aprendizaje cuando la tecnología ya está puesta.',
				),
				array(
					'title'    => 'Taller de radio escolar',
					'kind'     => 'taller',
					'date'     => $proximo_inicio,
					'start'    => '16:00',
					'end'      => '18:00',
					'venue'    => 'Instituto Sur',
					'room'     => 'Aula 2',
					'seats'    => 20,
					'speakers' => array( 'Luis Gómez Perdomo' ),
					'summary'  => 'Montar una radio de centro con lo que ya hay: móviles, un micro y un plan.',
				),
				array(
					'title'    => 'Taller de robótica en Primaria',
					'kind'     => 'taller',
					'date'     => $proximo_inicio,
					'start'    => '16:00',
					'end'      => '18:00',
					'venue'    => 'Instituto Sur',
					'room'     => 'Aula 3',
					'seats'    => 15,
					'speakers' => array( 'Marta Ruiz Santana' ),
				),
				array(
					'title'    => 'Mesa redonda: qué pedimos a la tecnología educativa',
					'kind'     => 'mesa',
					'date'     => $proximo_fin,
					'start'    => '10:00',
					'end'      => '11:30',
					'venue'    => 'Centro de formación Norte',
					'room'     => 'Salón de actos',
					'speakers' => array( 'Ana Martín Cabrera', 'Luis Gómez Perdomo', 'Marta Ruiz Santana' ),
				),
				array(
					'title' => 'Clausura',
					'kind'  => 'clausura',
					'date'  => $proximo_fin,
					'start' => '13:00',
					'end'   => '13:30',
					'venue' => 'Centro de formación Norte',
					'room'  => 'Salón de actos',
				),
			),
		),
		array(
			'slug'     => 'encuentro-escuelas-rurales',
			'title'    => 'Encuentro de Escuelas Rurales',
			'author'   => 'organizacion3',
			'area'     => 'convivencia-escolar',
			'type'     => 'encuentro',
			'start'    => $abierto_inicio,
			'end'      => $abierto_fin,
			'venue'    => 'CEIP El Roque',
			'meta'     => array(
				'evt_tagline'     => 'La escuela pequeña, en el centro',
				'evt_hashtag'     => 'EscuelasRurales',
				'evt_intro'       => 'Encuentro de los centros rurales y las escuelas unitarias: qué funciona y qué hace falta.',
				'evt_signup_show' => false,
				'evt_header_bg'   => '#5b3a12',
				'evt_header_text' => '#fff8ef',
				'evt_title_font'  => 'merriweather',
				'evt_body_font'   => 'lato',
				'evt_image_shape' => 'square',
				'evt_separator'   => 'slant',
			),
			'sections' => array(
				array(
					'slug'    => 'programa',
					'title'   => 'Programa',
					'type'    => 'programa',
					'order'   => 1,
					'status'  => 'publish',
					'content' => 'Mañana de mesas de trabajo y tarde de visita a los centros de la isla.',
				),
				array(
					'slug'    => 'participacion',
					'title'   => 'Cómo participar',
					'type'    => 'participacion',
					'order'   => 2,
					'status'  => 'publish',
					'content' => 'Cómo llevar una experiencia de aula al encuentro y en qué formato.',
				),
				array(
					'slug'    => 'encuesta-de-valoracion',
					'title'   => 'Encuesta de valoración',
					'type'    => 'encuesta',
					'order'   => 3,
					'status'  => 'draft',
					'content' => 'Se abrirá al terminar el encuentro.',
				),
				array(
					'slug'    => 'contacto',
					'title'   => 'Contacto',
					'type'    => 'contacto',
					'order'   => 4,
					'status'  => 'publish',
					'content' => 'Organiza el área de Convivencia escolar con la colaboración del Centro de formación Oeste.',
				),
			),
		),
	);
}

/**
 * The two demo questions of the first event, as stored JSON.
 *
 * Dos, de logística, que es el tamaño que la ADR-0031 dice que tiene esta
 * parte: lo que cambia de un evento a otro es pequeño y concreto.
 */
define(
	'EVT_DEMO_QUESTIONS',
	(string) wp_json_encode(
		array(
			array(
				'id'       => 'qcomida000001',
				'label'    => 'Se queda a comer',
				'type'     => 'check',
				'options'  => array(),
				'required' => false,
			),
			array(
				'id'       => 'qintoler00001',
				'label'    => 'Intolerancias o alergias alimentarias',
				'type'     => 'many',
				'options'  => array( 'Gluten', 'Lactosa', 'Frutos secos', 'Marisco' ),
				'required' => false,
			),
		)
	)
);

/**
 * Create or update one demo account and its área.
 *
 * @param string                                           $login   Login name.
 * @param array{role:string, areas:string[], label:string} $account Account definition.
 * @return int User ID.
 * @throws RuntimeException If the user cannot be created or an área is missing.
 */
function evt_seed_account( string $login, array $account ): int {
	$user = get_user_by( 'login', $login );

	if ( ! $user instanceof WP_User ) {
		$user_id = wp_insert_user(
			array(
				'user_login'   => $login,
				'user_pass'    => 'password',
				'user_email'   => $login . '@example.org',
				'display_name' => $account['label'],
				'role'         => $account['role'],
			)
		);
		if ( is_wp_error( $user_id ) ) {
			throw new RuntimeException( esc_html( "No se pudo crear el usuario «{$login}»: " . $user_id->get_error_message() ) );
		}
		$user = get_user_by( 'id', (int) $user_id );
		echo esc_html( "Creado el usuario «{$login}» ({$account['role']})." ) . "\n";
	}

	if ( ! $user instanceof WP_User ) {
		throw new RuntimeException( esc_html( "No se pudo leer el usuario «{$login}» después de crearlo." ) );
	}

	// El rol y el nombre se reponen siempre: si alguien los cambió probando —o si
	// cambiaron aquí—, la siguiente provisión devuelve el entorno a lo que dice
	// el README. Solo al crear la cuenta se quedaba el nombre viejo para siempre.
	$user->set_role( $account['role'] );

	if ( $account['label'] !== $user->display_name ) {
		wp_update_user(
			array(
				'ID'           => $user->ID,
				'display_name' => $account['label'],
			)
		);
	}

	$areas = array();
	foreach ( $account['areas'] as $slug ) {
		$term = get_term_by( 'slug', $slug, 'evt_area' );
		if ( ! $term instanceof WP_Term ) {
			throw new RuntimeException( esc_html( "No existe el área «{$slug}»: ejecute antes scripts/setup-vocabulary.php." ) );
		}
		$areas[] = (int) $term->term_id;
	}
	update_user_meta( $user->ID, 'evt_area', $areas );

	return (int) $user->ID;
}

/**
 * Term ID of one taxonomy term, by slug.
 *
 * @param string $taxonomy Taxonomy name.
 * @param string $slug     Term slug.
 * @return int
 * @throws RuntimeException If the term does not exist.
 */
function evt_demo_term( string $taxonomy, string $slug ): int {
	$term = get_term_by( 'slug', $slug, $taxonomy );
	if ( ! $term instanceof WP_Term ) {
		throw new RuntimeException( esc_html( "No existe «{$slug}» en {$taxonomy}: ejecute antes scripts/setup-vocabulary.php." ) );
	}
	return (int) $term->term_id;
}

/**
 * Create or complete one satellite page of a demo event.
 *
 * @param int                  $root    Event post ID.
 * @param int                  $author  Author user ID.
 * @param array<string, mixed> $section Section definition.
 * @return bool Whether the page was created.
 * @throws RuntimeException If the page cannot be created.
 */
function evt_seed_section( int $root, int $author, array $section ): bool {
	$meta = array_merge(
		array( 'evt_section_type' => $section['type'] ),
		isset( $section['meta'] ) ? (array) $section['meta'] : array()
	);

	$page = get_page_by_path( get_post_field( 'post_name', $root ) . '/' . $section['slug'], OBJECT, 'evt_event' );
	if ( $page instanceof WP_Post ) {
		// Ya estaba: se le repone el tipo, el orden, el texto y la apariencia, que
		// es lo que hace falta para que las pantallas se vean con datos aunque el
		// entorno se provisionase antes de que esto existiera. Sin reponer el
		// texto, un entorno viejo se queda enseñando el de la provisión de
		// entonces para siempre.
		wp_update_post(
			array(
				'ID'           => $page->ID,
				'menu_order'   => (int) $section['order'],
				'post_content' => $section['content'],
			)
		);
		foreach ( $meta as $clave => $valor ) {
			update_post_meta( (int) $page->ID, $clave, $valor );
		}
		return false;
	}

	$child = wp_insert_post(
		array(
			'post_type'    => 'evt_event',
			'post_status'  => $section['status'],
			'post_parent'  => $root,
			'post_name'    => $section['slug'],
			'post_title'   => $section['title'],
			'post_author'  => $author,
			'menu_order'   => (int) $section['order'],
			'post_content' => $section['content'],
			'meta_input'   => $meta,
		),
		true
	);
	if ( is_wp_error( $child ) ) {
		throw new RuntimeException( esc_html( "No se pudo crear la página «{$section['slug']}»: " . $child->get_error_message() ) );
	}

	return true;
}

/**
 * Create or complete one demo event with its satellite pages.
 *
 * Se vuelve a pasar por encima del evento que ya existía en vez de saltárselo:
 * lo que hay aquí son datos de demostración, y un entorno provisionado antes de
 * que el evento tuviera apariencia y secciones se quedaría sin ellas para
 * siempre.
 *
 * @param array<string, mixed> $event Event definition.
 * @return bool Whether the event root was created.
 * @throws RuntimeException If a post cannot be created.
 */
function evt_seed_event( array $event ): bool {
	$author = get_user_by( 'login', $event['author'] );
	$author = $author instanceof WP_User ? (int) $author->ID : 0;

	$meta = array_merge(
		array(
			'evt_start_date' => $event['start'],
			'evt_end_date'   => $event['end'],
			'evt_venue'      => $event['venue'],
		),
		(array) $event['meta']
	);

	$existente = get_page_by_path( $event['slug'], OBJECT, 'evt_event' );
	$nuevo     = ! $existente instanceof WP_Post;

	if ( $nuevo ) {
		$root = wp_insert_post(
			array(
				'post_type'    => 'evt_event',
				'post_status'  => 'publish',
				'post_name'    => $event['slug'],
				'post_title'   => $event['title'],
				'post_author'  => $author,
				'post_content' => 'Evento de demostración creado por scripts/seed-demo.php.',
				'meta_input'   => $meta,
			),
			true
		);
		if ( is_wp_error( $root ) ) {
			throw new RuntimeException( esc_html( "No se pudo crear el evento «{$event['slug']}»: " . $root->get_error_message() ) );
		}
		$root = (int) $root;
	} else {
		$root = (int) $existente->ID;
		foreach ( $meta as $clave => $valor ) {
			update_post_meta( $root, $clave, $valor );
		}
	}

	// Los términos van por ID: `wp_set_object_terms()` con una cadena en una
	// taxonomía jerárquica crearía un término nuevo si el slug no existiese, y
	// lo que hace falta es enterarse de que falta el vocabulario.
	wp_set_object_terms( $root, array( evt_demo_term( 'evt_area', $event['area'] ) ), 'evt_area' );
	wp_set_object_terms( $root, array( evt_demo_term( 'evt_type', $event['type'] ) ), 'evt_type' );
	wp_set_object_terms( $root, array( evt_demo_term( 'evt_course', evt_demo_course( $event['start'] ) ) ), 'evt_course' );

	$creadas = 0;
	foreach ( $event['sections'] as $section ) {
		$creadas += evt_seed_section( $root, $author, $section ) ? 1 : 0;
	}

	evt_seed_programme( $root, $event );

	echo esc_html(
		sprintf(
			'%1$s «%2$s» (ID %3$d): %4$d página(s) satélite nueva(s) de %5$d.',
			$nuevo ? 'Creado el evento' : 'Actualizado el evento',
			$event['slug'],
			$root,
			$creadas,
			count( $event['sections'] )
		)
	) . "\n";

	return $nuevo;
}

/**
 * Give one demo event its speakers and its programme.
 *
 * Idempotente por título, como todo lo demás de este guion: se vuelve a
 * ejecutar sin duplicar nada. El primer evento lleva **dos sedes el mismo
 * día** a propósito, que es el caso que ADR-0024 decidió que tenía que caber.
 *
 * @param int                  $root  Event post ID.
 * @param array<string, mixed> $event Demo event definition.
 * @return void
 */
function evt_seed_programme( int $root, array $event ): void {
	if ( ! isset( $event['programme'] ) ) {
		return;
	}

	$ponentes = array();
	foreach ( (array) ( $event['speakers'] ?? array() ) as $ficha ) {
		$id = evt_seed_child( $root, 'evt_speaker', $ficha['name'], $ficha['bio'] ?? '' );
		update_post_meta( $id, 'evt_speaker_role', (string) ( $ficha['role'] ?? '' ) );
		update_post_meta( $id, 'evt_speaker_org', (string) ( $ficha['org'] ?? '' ) );
		$ponentes[ $ficha['name'] ] = $id;
	}

	foreach ( (array) $event['programme'] as $actividad ) {
		$id = evt_seed_child( $root, 'evt_activity', $actividad['title'], $actividad['summary'] ?? '' );
		update_post_meta( $id, 'evt_activity_kind', (string) $actividad['kind'] );
		update_post_meta( $id, 'evt_activity_date', (string) $actividad['date'] );
		update_post_meta( $id, 'evt_activity_start', (string) ( $actividad['start'] ?? '' ) );
		update_post_meta( $id, 'evt_activity_end', (string) ( $actividad['end'] ?? '' ) );
		update_post_meta( $id, 'evt_activity_venue', (string) ( $actividad['venue'] ?? '' ) );
		update_post_meta( $id, 'evt_activity_room', (string) ( $actividad['room'] ?? '' ) );
		update_post_meta( $id, 'evt_activity_seats', (int) ( $actividad['seats'] ?? 0 ) );

		$suyos = array();
		foreach ( (array) ( $actividad['speakers'] ?? array() ) as $nombre ) {
			if ( isset( $ponentes[ $nombre ] ) ) {
				$suyos[] = $ponentes[ $nombre ];
			}
		}
		update_post_meta( $id, 'evt_activity_speakers', implode( ',', $suyos ) );
	}

	echo esc_html(
		sprintf(
			'  … %1$d ponente(s) y %2$d actividad(es).',
			count( $ponentes ),
			count( (array) $event['programme'] )
		)
	) . "\n";
}

/**
 * One speaker or activity of an event, created once and updated after that.
 *
 * Cuelgan del evento por `post_parent`: de ahí les llega el área y el cierre
 * por histórico, sin ninguna meta que mantener al día.
 *
 * @param int    $root      Event post ID.
 * @param string $post_type evt_speaker | evt_activity.
 * @param string $titulo    Title.
 * @param string $cuerpo    Content.
 * @return int
 * @throws RuntimeException If the post cannot be written.
 */
function evt_seed_child( int $root, string $post_type, string $titulo, string $cuerpo ): int {
	$existentes = get_posts(
		array(
			'post_type'   => $post_type,
			'post_parent' => $root,
			'title'       => $titulo,
			'numberposts' => 1,
			'post_status' => 'any',
		)
	);
	if ( array() !== $existentes ) {
		return (int) $existentes[0]->ID;
	}

	$id = wp_insert_post(
		array(
			'post_type'    => $post_type,
			'post_parent'  => $root,
			'post_title'   => $titulo,
			'post_content' => $cuerpo,
			'post_status'  => 'publish',
			'post_author'  => (int) get_post_field( 'post_author', $root ),
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		throw new RuntimeException( esc_html( "No se pudo crear «{$titulo}»: " . $id->get_error_message() ) );
	}
	return (int) $id;
}

/**
 * Seed the whole demo dataset.
 *
 * @return void
 * @throws RuntimeException If the application is not loaded.
 */
function evt_seed_demo(): void {
	if ( ! post_type_exists( 'evt_event' ) || ! taxonomy_exists( 'evt_area' ) ) {
		throw new RuntimeException( 'El aplicativo no está cargado (falta evt_event o evt_area). Ejecute antes `make bundle && make sync-snippets`.' );
	}

	foreach ( evt_demo_accounts() as $login => $account ) {
		evt_seed_account( $login, $account );
	}

	$creados = 0;
	foreach ( evt_demo_events() as $event ) {
		$creados += evt_seed_event( $event ) ? 1 : 0;
	}

	echo esc_html(
		sprintf(
			'Datos de demostración: %1$d cuenta(s) al día, %2$d evento(s) creado(s). Contraseña: password.',
			count( evt_demo_accounts() ),
			$creados
		)
	) . "\n";
}

// Igual que setup-vocabulary.php: en Code Snippets esto correría antes de `init` y
// no habría ni tipos ni taxonomías; con `wp eval-file` init ya pasó.
if ( did_action( 'init' ) ) {
	evt_seed_demo();
} else {
	add_action( 'init', 'evt_seed_demo', 20 );
}
