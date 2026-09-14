<?php
/**
 * Tests for the four programme tabs: speakers, parrilla, workshops, people.
 *
 * @package Evt
 */

use Evt\Access\EventAccess;
use Evt\Domain\ActivityInput;
use Evt\Meta\EventMetaKeys;
use Evt\Meta\ProgrammeMetaKeys;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\Participants;
use Evt\PublicFront\Programme;

/**
 * Lo que cuelga de un evento: ponentes, actividades, talleres e inscripciones.
 */
class Test_Programme extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Tipos, taxonomías, roles y páginas del aplicativo.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		$this->pages();
	}

	/**
	 * Un ponente de prueba, ya colgado de su evento.
	 *
	 * @param int    $evento Event ID.
	 * @param string $nombre Speaker name.
	 * @return int
	 */
	private function speaker( int $evento, string $nombre ): int {
		return Programme::save_speaker(
			$evento,
			0,
			array(
				'name' => $nombre,
				'role' => 'Asesora',
				'org'  => 'Centro de formación',
				'bio'  => '',
			)
		);
	}

	/**
	 * Una actividad de prueba.
	 *
	 * @param int                  $evento Event ID.
	 * @param array<string, mixed> $campos What to override.
	 * @return int
	 */
	private function activity( int $evento, array $campos = array() ): int {
		$base = array(
			'title'    => 'Ponencia inaugural',
			'kind'     => 'ponencia',
			'date'     => '2026-10-28',
			'start'    => '09:30',
			'end'      => '10:30',
			'venue'    => 'Centro de formación Norte',
			'room'     => 'Salón de actos',
			'seats'    => 0,
			'summary'  => '',
			'speakers' => array(),
		);
		return Programme::save_activity( $evento, 0, array_merge( $base, $campos ) );
	}

	/**
	 * Un ponente cuelga de su evento, y por eso hereda su área y su cierre.
	 *
	 * Es la decisión que sostiene las cuatro pestañas: colgándolos por
	 * `post_parent`, el acotado por área y la marca de histórico los alcanzan
	 * sin una regla nueva que mantener.
	 */
	public function test_a_speaker_hangs_from_its_event_and_inherits_its_area() {
		$area   = $this->area( 'Tecnología Educativa' );
		$uid    = $this->organiser( array( $area ) );
		$evento = $this->event( $uid, array( $area ) );

		$ponente = $this->speaker( $evento, 'Ana Pérez' );

		$this->assertGreaterThan( 0, $ponente );
		$this->assertSame( $evento, (int) get_post_field( 'post_parent', $ponente ) );
		$this->assertSame( array( $area ), EventAccess::post_areas( $ponente ), 'el área sale de su evento' );
		$this->assertTrue( EventAccess::can_edit( $uid, $ponente ) );

		// Y el cierre del evento le llega sin que nadie lo copie.
		update_post_meta( $evento, EventMetaKeys::ARCHIVED, true );
		$this->assertTrue( EventAccess::is_archived( $ponente ) );
		$this->assertFalse( EventAccess::can_edit( $uid, $ponente ), 'con el evento cerrado, su ponente tampoco se edita' );
	}

	/**
	 * Un área no toca el ponente de otra área.
	 */
	public function test_another_area_cannot_edit_the_speaker() {
		$mia   = $this->area( 'Mía' );
		$suya  = $this->area( 'Suya' );
		$yo    = $this->organiser( array( $mia ) );
		$otra  = $this->organiser( array( $suya ) );
		$event = $this->event( $yo, array( $mia ) );

		$ponente = $this->speaker( $event, 'Ana Pérez' );

		$this->assertTrue( EventAccess::can_edit( $yo, $ponente ) );
		$this->assertFalse( EventAccess::can_edit( $otra, $ponente ) );
	}

	/**
	 * Guardar con el identificador de otro evento no escribe nada.
	 *
	 * El acotado por área diría que sí cuando las dos áreas coinciden, así que
	 * hace falta esta comprobación de pertenencia y no basta con el guardián.
	 */
	public function test_saving_cannot_reach_a_speaker_of_another_event() {
		$area = $this->area( 'Una' );
		$uid  = $this->organiser( array( $area ) );
		$uno  = $this->event( $uid, array( $area ) );
		$dos  = $this->event( $uid, array( $area ) );

		$ponente = $this->speaker( $uno, 'Ana Pérez' );

		$hecho = Programme::save_speaker(
			$dos,
			$ponente,
			array(
				'name' => 'Cambiado',
				'role' => '',
				'org'  => '',
				'bio'  => '',
			)
		);

		$this->assertSame( 0, $hecho, 'no se escribe' );
		$this->assertSame( 'Ana Pérez', get_post_field( 'post_title', $ponente ), 'y la ficha no cambia' );
	}

	/**
	 * Una actividad solo enlaza ponentes de su propio evento.
	 */
	public function test_an_activity_only_links_speakers_of_its_event() {
		$area  = $this->area( 'Una' );
		$uid   = $this->organiser( array( $area ) );
		$uno   = $this->event( $uid, array( $area ) );
		$dos   = $this->event( $uid, array( $area ) );
		$mio   = $this->speaker( $uno, 'Ana Pérez' );
		$ajeno = $this->speaker( $dos, 'Luis Gómez' );

		$actividad = $this->activity( $uno, array( 'speakers' => array( $mio, $ajeno ) ) );

		$this->assertSame( array( $mio ), Programme::speaker_ids( $actividad ) );
	}

	/**
	 * La parrilla agrupa por día y, dentro del día, por sede.
	 *
	 * Es ADR-0024: un mismo día puede tener dos sedes —la mañana en una sede y
	 * la tarde en un centro— y salen dos bloques dentro del mismo día.
	 */
	public function test_the_grid_groups_by_day_and_then_by_venue() {
		$area   = $this->area( 'Una' );
		$uid    = $this->organiser( array( $area ) );
		$evento = $this->event( $uid, array( $area ) );

		$this->activity(
			$evento,
			array(
				'title' => 'Mañana',
				'date'  => '2026-10-28',
				'start' => '09:30',
				'venue' => 'Centro de formación Norte',
			)
		);
		$this->activity(
			$evento,
			array(
				'title' => 'Tarde',
				'date'  => '2026-10-28',
				'start' => '16:00',
				'venue' => 'Instituto Sur',
			)
		);
		$this->activity(
			$evento,
			array(
				'title' => 'Día siguiente',
				'date'  => '2026-10-29',
				'start' => '09:00',
				'venue' => 'Centro de formación Norte',
			)
		);

		$parrilla = Programme::grid( $evento );

		$this->assertCount( 2, $parrilla, 'dos días' );
		$this->assertSame( '2026-10-28', $parrilla[0]['date'] );
		$this->assertCount( 2, $parrilla[0]['venues'], 'y el primer día, dos sedes' );
		$this->assertSame( 'Centro de formación Norte', $parrilla[0]['venues'][0]['venue'] );
		$this->assertSame( 'Mañana', $parrilla[0]['venues'][0]['rows'][0]['title'] );
		$this->assertSame( 'Instituto Sur', $parrilla[0]['venues'][1]['venue'] );

		$this->assertCount( 1, $parrilla[1]['venues'], 'el segundo día, una sola' );
	}

	/**
	 * Las actividades se ordenan por día y hora, y lo que no tiene día va al final.
	 */
	public function test_activities_are_sorted_by_day_and_time() {
		$area   = $this->area( 'Una' );
		$uid    = $this->organiser( array( $area ) );
		$evento = $this->event( $uid, array( $area ) );

		$this->activity(
			$evento,
			array(
				'title' => 'Segunda',
				'date'  => '2026-10-28',
				'start' => '12:00',
			)
		);
		$this->activity(
			$evento,
			array(
				'title' => 'Primera',
				'date'  => '2026-10-28',
				'start' => '09:00',
			)
		);
		// Sin día: `save_activity()` la guarda igual porque el saneado deja la
		// fecha vacía; quien teclea ve el error antes, en ActivityInput.
		$this->activity(
			$evento,
			array(
				'title' => 'Sin fecha',
				'date'  => '',
				'start' => '',
			)
		);

		$titulos = wp_list_pluck( Programme::activities( $evento ), 'post_title' );
		$this->assertSame( array( 'Primera', 'Segunda', 'Sin fecha' ), $titulos );
	}

	/**
	 * Un taller es una actividad del programa, no otro tipo de contenido.
	 */
	public function test_only_workshops_show_up_as_workshops() {
		$area   = $this->area( 'Una' );
		$uid    = $this->organiser( array( $area ) );
		$evento = $this->event( $uid, array( $area ) );

		$this->activity( $evento, array( 'title' => 'Ponencia' ) );
		$this->activity(
			$evento,
			array(
				'title' => 'Taller de robótica',
				'kind'  => 'taller',
				'seats' => 20,
			)
		);

		$talleres = Programme::workshops( $evento );
		$this->assertCount( 1, $talleres );
		$this->assertSame( 'Taller de robótica', $talleres[0]->post_title );
		$this->assertSame( 20, (int) get_post_meta( $talleres[0]->ID, ProgrammeMetaKeys::ACTIVITY_SEATS, true ) );
	}

	/**
	 * Las sedes se deducen de las actividades: no hay dónde darlas de alta.
	 */
	public function test_venues_are_derived_from_the_activities() {
		$area   = $this->area( 'Una' );
		$uid    = $this->organiser( array( $area ) );
		$evento = $this->event( $uid, array( $area ) );

		$this->activity( $evento, array( 'venue' => 'Centro de formación Norte' ) );
		$this->activity( $evento, array( 'venue' => 'Instituto Sur' ) );
		$this->activity( $evento, array( 'venue' => 'Centro de formación Norte' ) );

		$this->assertSame( array( 'Centro de formación Norte', 'Instituto Sur' ), Programme::venues( $evento ) );
	}

	/**
	 * Subir y bajar un ponente renumera la lista entera.
	 */
	public function test_speakers_can_be_reordered() {
		$area   = $this->area( 'Una' );
		$uid    = $this->organiser( array( $area ) );
		$evento = $this->event( $uid, array( $area ) );

		$uno = $this->speaker( $evento, 'Aaa' );
		$dos = $this->speaker( $evento, 'Bbb' );

		$this->assertTrue( Programme::reorder_speaker( $evento, $dos, -1 ) );
		$this->assertSame(
			array( $dos, $uno ),
			array_map( 'intval', wp_list_pluck( Programme::speakers( $evento ), 'ID' ) )
		);

		$this->assertFalse( Programme::reorder_speaker( $evento, $dos, -1 ), 'el primero no sube más' );
	}

	/**
	 * Borrar es enviar a la papelera, y de ahí se restaura (ADR-0016).
	 */
	public function test_a_ficha_goes_to_the_trash_and_comes_back() {
		$area   = $this->area( 'Una' );
		$uid    = $this->organiser( array( $area ) );
		$evento = $this->event( $uid, array( $area ) );
		$quien  = $this->speaker( $evento, 'Ana Pérez' );

		$this->assertTrue( Programme::trash( $evento, $quien ) );
		$this->assertSame( 'trash', get_post_status( $quien ) );
		$this->assertCount( 0, Programme::speakers( $evento ) );
		$this->assertCount( 1, Programme::speakers( $evento, true ) );

		$this->assertTrue( Programme::restore( $evento, $quien ) );
		$this->assertNotSame( 'trash', get_post_status( $quien ) );
		$this->assertCount( 1, Programme::speakers( $evento ) );
	}

	/**
	 * El taller no se borra desde el evento de al lado.
	 */
	public function test_trashing_cannot_reach_another_event() {
		$area = $this->area( 'Una' );
		$uid  = $this->organiser( array( $area ) );
		$uno  = $this->event( $uid, array( $area ) );
		$dos  = $this->event( $uid, array( $area ) );
		$mio  = $this->speaker( $uno, 'Ana Pérez' );

		$this->assertFalse( Programme::trash( $dos, $mio ) );
		$this->assertSame( 'publish', get_post_status( $mio ) );
	}

	/*
	 * -----------------------------------------------------------------------
	 * La validación, que es pura
	 * -----------------------------------------------------------------------
	 */

	/**
	 * Una actividad necesita título, tipo conocido y día.
	 */
	public function test_an_activity_needs_a_title_a_kind_and_a_day() {
		$vacia = ActivityInput::activity( array() );
		$this->assertFalse( $vacia['ok'] );
		$this->assertContains( 'title', $vacia['errors'] );
		$this->assertContains( 'kind', $vacia['errors'] );
		$this->assertContains( 'date', $vacia['errors'] );

		$mala = ActivityInput::activity(
			array(
				'title' => 'Algo',
				'kind'  => 'inventado',
				'date'  => '2026-02-30',
			)
		);
		$this->assertFalse( $mala['ok'] );
		$this->assertContains( 'kind', $mala['errors'], 'un tipo que no está en la lista' );
		$this->assertContains( 'date', $mala['errors'], 'el 30 de febrero no existe' );
	}

	/**
	 * La hora es opcional, pero si viene tiene que ser una hora y estar en orden.
	 */
	public function test_the_times_are_optional_but_have_to_make_sense() {
		$sin_hora = ActivityInput::activity(
			array(
				'title' => 'Por la tarde',
				'kind'  => 'ponencia',
				'date'  => '2026-10-28',
			)
		);
		$this->assertTrue( $sin_hora['ok'], 'hay actividades sin hora fijada' );

		$al_reves = ActivityInput::activity(
			array(
				'title' => 'Algo',
				'kind'  => 'ponencia',
				'date'  => '2026-10-28',
				'start' => '12:00',
				'end'   => '09:00',
			)
		);
		$this->assertFalse( $al_reves['ok'] );
		$this->assertContains( 'time_order', $al_reves['errors'] );

		$imposible = ActivityInput::activity(
			array(
				'title' => 'Algo',
				'kind'  => 'ponencia',
				'date'  => '2026-10-28',
				'start' => '25:99',
			)
		);
		$this->assertFalse( $imposible['ok'] );
		$this->assertContains( 'start', $imposible['errors'] );
		$this->assertNotContains( 'time_order', $imposible['errors'], 'el error que hay que enseñar es el de la hora, no un orden imposible' );
	}

	/**
	 * Un ponente solo necesita nombre.
	 */
	public function test_a_speaker_only_needs_a_name() {
		$this->assertFalse( ActivityInput::speaker( array( 'name' => '   ' ) )['ok'] );

		$bien = ActivityInput::speaker( array( 'name' => 'Ana Pérez' ) );
		$this->assertTrue( $bien['ok'] );
		$this->assertSame( 'Ana Pérez', $bien['data']['name'] );
		$this->assertSame( '', $bien['data']['role'], 'el cargo se puede dejar vacío' );
	}

	/*
	 * -----------------------------------------------------------------------
	 * Participantes: el filtro y el CSV, que son puros
	 * -----------------------------------------------------------------------
	 */

	/**
	 * Tres inscripciones de prueba.
	 *
	 * @return array<int, array<string, string>>
	 */
	private function people(): array {
		return Participants::clean(
			array(
				array(
					'name'     => 'Ana Martín',
					'email'    => 'ana@example.org',
					'centre'   => 'CEIP El Drago',
					'workshop' => 'Robótica',
					'date'     => '2026-09-01',
					'consent'  => 'Sí',
				),
				array(
					'name'     => 'Luis Gómez',
					'email'    => 'luis@example.org',
					'centre'   => 'Instituto Sur',
					'workshop' => 'Radio escolar',
					'date'     => '2026-09-02',
					'consent'  => 'Sí',
				),
				array(
					'name'     => 'Marta Ruiz',
					'email'    => 'marta@example.org',
					'centre'   => 'CEIP El Drago',
					'workshop' => 'Robótica',
					'date'     => '2026-09-03',
					'consent'  => 'Sí',
				),
			)
		);
	}

	/**
	 * Sin nadie que conteste al enganche, la lista está vacía y no se inventa.
	 */
	public function test_without_a_source_there_are_no_participants() {
		$area   = $this->area( 'Una' );
		$uid    = $this->organiser( array( $area ) );
		$evento = $this->event( $uid, array( $area ) );

		$this->assertSame( array(), Participants::rows( $evento ) );
	}

	/**
	 * Quien conteste al enganche llena la lista, y se normaliza al entrar.
	 */
	public function test_the_hook_fills_the_list_and_the_shape_is_fixed() {
		$area   = $this->area( 'Una' );
		$uid    = $this->organiser( array( $area ) );
		$evento = $this->event( $uid, array( $area ), array( EventMetaKeys::SIGNUP_FORM_ID => 10 ) );

		$visto = 0;
		add_filter(
			Participants::HOOK,
			static function ( $filas, $id ) use ( &$visto ) {
				$visto = $id;
				return array(
					array(
						'name'      => '  Ana Martín ',
						'inventado' => 'se ignora',
					),
				);
			},
			10,
			2
		);

		$filas = Participants::rows( $evento );

		$this->assertSame( $evento, $visto, 'el enganche recibe el evento y nada del sistema viejo' );
		$this->assertCount( 1, $filas );
		$this->assertSame( 'Ana Martín', $filas[0]['name'], 'se recorta' );
		$this->assertArrayNotHasKey( 'inventado', $filas[0], 'lo que sobra se queda fuera' );
		$this->assertSame( '', $filas[0]['email'], 'lo que falta sale vacío, no ausente' );
	}

	/**
	 * El filtro busca en todas las columnas y no distingue tildes.
	 */
	public function test_the_filter_searches_every_column() {
		$filas = $this->people();

		$this->assertCount( 3, Participants::filter( $filas ), 'sin filtro, todas' );
		$this->assertCount( 2, Participants::filter( $filas, 'drago' ), 'por centro, y sin mayúsculas' );
		$this->assertCount( 1, Participants::filter( $filas, 'martin' ), 'por apellido, y sin la tilde' );
		$this->assertCount( 2, Participants::filter( $filas, '', 'Robótica' ), 'acotado a un taller' );
		$this->assertCount( 1, Participants::filter( $filas, 'marta', 'Robótica' ), 'las dos cosas a la vez' );
		$this->assertSame( array( 'Radio escolar', 'Robótica' ), Participants::workshops( $filas ) );
	}

	/**
	 * El CSV abre en una hoja de cálculo en español y no ejecuta fórmulas.
	 */
	public function test_the_csv_opens_where_it_is_going_to_be_opened() {
		$csv = Participants::csv(
			Participants::clean(
				array(
					array(
						'name'     => 'Ana "Anita" Martín',
						'workshop' => '=SUM(1+1)',
					),
				)
			)
		);

		$this->assertStringStartsWith( "\xEF\xBB\xBF", $csv, 'BOM: sin él, las tildes salen rotas en Excel' );
		$this->assertStringContainsString( '"Nombre";"Correo"', $csv, 'punto y coma, que es lo que espera el Excel en español' );
		$this->assertStringContainsString( '"Ana ""Anita"" Martín"', $csv, 'las comillas se duplican' );
		$this->assertStringContainsString( '"\'=SUM(1+1)"', $csv, 'una fórmula se desactiva con una comilla delante' );
		$this->assertStringContainsString( "\r\n", $csv );
	}

	/**
	 * El nombre del fichero dice de qué evento es.
	 */
	public function test_the_export_is_named_after_the_event() {
		$this->assertStringStartsWith( 'participantes-iii-jornadas', Participants::filename( 'III Jornadas' ) );
		$this->assertStringEndsWith( '.csv', Participants::filename( 'III Jornadas' ) );
		$this->assertStringContainsString( 'participantes-evento-', Participants::filename( '' ), 'sin título, no se queda sin nombre' );
	}

	/*
	 * -----------------------------------------------------------------------
	 * Las pestañas
	 * -----------------------------------------------------------------------
	 */

	/**
	 * Las cuatro pestañas nuevas están, y traen su recuento.
	 */
	public function test_the_four_tabs_are_there_with_their_counts() {
		$area   = $this->area( 'Una' );
		$uid    = $this->organiser( array( $area ) );
		$evento = $this->event( $uid, array( $area ) );

		$this->speaker( $evento, 'Ana Pérez' );
		$this->activity( $evento, array( 'title' => 'Ponencia' ) );
		$this->activity(
			$evento,
			array(
				'title' => 'Taller',
				'kind'  => 'taller',
				'seats' => 10,
			)
		);

		$paneles = EventWorkspace::panels( $evento, $uid );

		$this->assertSame( 1, $paneles[ EventWorkspace::PANEL_SPEAKERS ]['count'] );
		$this->assertSame( 2, $paneles[ EventWorkspace::PANEL_PROGRAMME ]['count'], 'la parrilla cuenta las dos' );
		$this->assertSame( 1, $paneles[ EventWorkspace::PANEL_WORKSHOPS ]['count'] );
		$this->assertSame( 0, $paneles[ EventWorkspace::PANEL_PEOPLE ]['count'] );
		$this->assertNull( $paneles[ EventWorkspace::PANEL_SETTINGS ]['count'], 'en «Ajustes» un número no significaría nada' );
	}

	/**
	 * Guardar un ponente desde la pantalla, con su nonce, y volver a su pestaña.
	 */
	public function test_the_screen_saves_a_speaker() {
		$area   = $this->area( 'Una' );
		$uid    = $this->organiser( array( $area ) );
		$evento = $this->event( $uid, array( $area ) );
		$this->acting_as( $uid );

		$op = EventWorkspace::OP_SPEAKER;
		$this->post(
			array(
				EventWorkspace::FIELD_DO    => $op,
				EventWorkspace::FIELD_EVENT => (string) $evento,
				EventWorkspace::FIELD_ROW   => '0',
				'evt_sp_name'               => 'Ana Pérez',
				'evt_sp_role'               => 'Asesora',
				'evt_sp_org'                => 'Centro de formación Norte',
				'evt_sp_bio'                => 'Dos líneas.',
			),
			EventWorkspace::nonce_action( $op ),
			EventWorkspace::nonce_name( $op )
		);

		$url = $this->exit_url( array( EventWorkspace::class, 'handle' ) );

		$this->assertSame( EventWorkspace::PANEL_SPEAKERS, $this->query_arg( (string) $url, EventWorkspace::ARG_PANEL ) );
		$ponentes = Programme::speakers( $evento );
		$this->assertCount( 1, $ponentes );
		$this->assertSame( 'Ana Pérez', $ponentes[0]->post_title );
		$this->assertSame( 'Centro de formación Norte', get_post_meta( $ponentes[0]->ID, ProgrammeMetaKeys::SPEAKER_ORG, true ) );
	}

	/**
	 * Sin nonce no se guarda nada.
	 */
	public function test_without_a_nonce_nothing_is_written() {
		$area   = $this->area( 'Una' );
		$uid    = $this->organiser( array( $area ) );
		$evento = $this->event( $uid, array( $area ) );
		$this->acting_as( $uid );

		$this->post(
			array(
				EventWorkspace::FIELD_DO    => EventWorkspace::OP_SPEAKER,
				EventWorkspace::FIELD_EVENT => (string) $evento,
				EventWorkspace::FIELD_ROW   => '0',
				'evt_sp_name'               => 'Ana Pérez',
			)
		);

		$this->assertNull( $this->exit_url( array( EventWorkspace::class, 'handle' ) ), 'ni sale' );
		$this->assertCount( 0, Programme::speakers( $evento ), 'ni escribe' );
	}

	/**
	 * La exportación sirve el CSV de lo filtrado, no de la lista entera.
	 */
	public function test_the_export_serves_the_filtered_rows() {
		$area   = $this->area( 'Una' );
		$uid    = $this->organiser( array( $area ) );
		$evento = $this->event( $uid, array( $area ) );
		$this->acting_as( $uid );

		$gente = $this->people();
		add_filter(
			Participants::HOOK,
			static function () use ( $gente ) {
				return $gente;
			}
		);

		$op = EventWorkspace::OP_EXPORT;
		$this->post(
			array(
				EventWorkspace::FIELD_DO     => $op,
				EventWorkspace::FIELD_EVENT  => (string) $evento,
				EventWorkspace::ARG_Q        => '',
				EventWorkspace::ARG_WORKSHOP => 'Robótica',
			),
			EventWorkspace::nonce_action( $op ),
			EventWorkspace::nonce_name( $op )
		);

		$cuerpo = $this->served( array( EventWorkspace::class, 'handle' ) );

		$this->assertStringContainsString( 'Ana Martín', $cuerpo );
		$this->assertStringContainsString( 'Marta Ruiz', $cuerpo );
		$this->assertStringNotContainsString( 'Luis Gómez', $cuerpo, 'el filtro puesto también manda en la exportación' );
	}
}
