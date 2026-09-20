<?php
/**
 * Tests for the event list: área scoping, filters, counts and paging.
 *
 * @package Evt
 */

use Evt\Meta\EventMetaKeys;
use Evt\PostType\EventPostType;
use Evt\PublicFront\EventList;
use Evt\PublicFront\Shell;
use Evt\Taxonomy\EventTaxonomies;

/**
 * La pantalla «Eventos», que es donde se empieza el día.
 *
 * Lo que más importa es lo que NO sale: ni una fila que esta persona no pueda
 * editar. El acotado por área falla en cerrado, y quien no tiene área no ve
 * nada aunque tenga el rol.
 */
class Test_Event_List extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Con las páginas del aplicativo ya creadas.
	 */
	public function set_up() {
		parent::set_up();
		$this->pages();
	}

	/**
	 * Los títulos de las filas del modelo, en el orden en que se pintan.
	 *
	 * @param array<string, mixed> $m What model() returned.
	 * @return string[]
	 */
	private function titulos( array $m ): array {
		return array_map( 'strval', array_column( (array) $m['rows'], 'title' ) );
	}

	/**
	 * Los identificadores de las filas del modelo.
	 *
	 * @param array<string, mixed> $m What model() returned.
	 * @return int[]
	 */
	private function ids( array $m ): array {
		return array_map( 'intval', array_column( (array) $m['rows'], 'id' ) );
	}

	/**
	 * Una fecha a tantos días de hoy.
	 *
	 * @param int $dias Offset in days.
	 * @return string Y-m-d.
	 */
	private function dia( int $dias ): string {
		return gmdate( 'Y-m-d', time() + ( $dias * DAY_IN_SECONDS ) );
	}

	// ─── quién ve qué ──────────────────────────────────────────────────────

	/**
	 * Sin sesión no hay listado: hay un aviso.
	 */
	public function test_without_a_session_there_is_no_list() {
		$this->acting_as( 0 );

		$m = EventList::model();

		$this->assertFalse( $m['can_use'] );
		$this->assertSame( array(), $m['rows'] );
		$this->assertStringContainsString( 'Debe iniciar sesión', $m['reason'] );
	}

	/**
	 * Quien no tiene área no ve nada, aunque haya eventos y tenga el rol.
	 */
	public function test_without_an_area_nothing_is_listed() {
		$area = $this->area( 'Formación del Profesorado' );
		$this->event( $this->administrator(), array( $area ) );

		$this->acting_as( $this->organiser() );
		$m = EventList::model();

		$this->assertFalse( $m['can_use'] );
		$this->assertSame( array(), $m['rows'] );
		$this->assertSame( 0, $m['total'] );
		$this->assertStringContainsString( 'ningún ámbito asignado', $m['reason'] );

		// Y sin el rol, el motivo es otro: no se arregla en el mismo sitio.
		$this->acting_as( (int) self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->assertStringContainsString( 'todavía no organiza eventos', EventList::model()['reason'] );
	}

	/**
	 * El listado se acota por área: lo de otro ámbito no se enumera.
	 */
	public function test_the_list_is_scoped_by_area() {
		$mia   = $this->area( 'Formación del Profesorado' );
		$otra  = $this->area( 'Innovación' );
		$ajena = $this->organiser( array( $otra ) );

		$mio  = $this->event( $ajena, array( $mia ), array(), array( 'post_title' => 'Jornadas de mi área' ) );
		$suyo = $this->event( $ajena, array( $otra ), array(), array( 'post_title' => 'Jornadas ajenas' ) );

		$this->acting_as( $this->organiser( array( $mia ) ) );
		$m = EventList::model();

		$this->assertTrue( $m['can_use'] );
		$this->assertTrue( $m['scoped'] );
		$this->assertSame( array( $mio ), $this->ids( $m ) );
		$this->assertNotContains( $suyo, $this->ids( $m ) );
		$this->assertStringContainsString( 'Solo los eventos de su ámbito', $m['subtitle'] );

		// La administración ve las dos.
		$this->acting_as( $this->administrator() );
		$m = EventList::model();
		$this->assertFalse( $m['scoped'] );
		$this->assertEqualSets( array( $mio, $suyo ), $this->ids( $m ) );
		$this->assertStringContainsString( 'Todos los eventos', $m['subtitle'] );
	}

	/**
	 * Un evento recién creado todavía no tiene área, y quien lo creó lo sigue viendo.
	 */
	public function test_a_brand_new_event_stays_in_sight_of_whoever_created_it() {
		$mia    = $this->area( 'Formación del Profesorado' );
		$yo     = $this->organiser( array( $mia ) );
		$recien = $this->event( $yo );

		$this->acting_as( $yo );
		$this->assertSame( array( $recien ), $this->ids( EventList::model() ) );

		// Pero no la organización de otro ámbito.
		$this->acting_as( $this->organiser( array( $this->area( 'Innovación' ) ) ) );
		$this->assertSame( array(), $this->ids( EventList::model() ) );
	}

	/**
	 * Las páginas satélite no son filas: son el recuento de secciones de su evento.
	 */
	public function test_the_satellite_pages_are_counted_not_listed() {
		$area   = $this->area( 'Innovación' );
		$evento = $this->event( $this->administrator(), array( $area ) );
		$this->event_page( $evento, 'programa' );
		$this->event_page( $evento, 'ponentes' );

		$this->acting_as( $this->administrator() );
		$m = EventList::model();

		$this->assertSame( array( $evento ), $this->ids( $m ) );
		$this->assertSame( 2, $m['rows'][0]['sections'] );
		$this->assertStringContainsString( 'evento=' . $evento, (string) $m['rows'][0]['url'] );
	}

	// ─── recuentos y filtros ───────────────────────────────────────────────

	/**
	 * Las fichas de recuento cuentan cada estado del mismo conjunto que la tabla.
	 */
	public function test_the_counts_add_up_by_state() {
		$area  = $this->area( 'Innovación' );
		$coord = $this->administrator();

		$this->event(
			$coord,
			array( $area ),
			array(
				EventMetaKeys::START_DATE => $this->dia( 10 ),
				EventMetaKeys::END_DATE   => $this->dia( 12 ),
			),
			array( 'post_title' => 'Próximo' )
		);
		$this->event(
			$coord,
			array( $area ),
			array(
				EventMetaKeys::START_DATE => $this->dia( -1 ),
				EventMetaKeys::END_DATE   => $this->dia( 1 ),
			),
			array( 'post_title' => 'Abierto' )
		);
		$this->event(
			$coord,
			array( $area ),
			array(
				EventMetaKeys::START_DATE => $this->dia( -20 ),
				EventMetaKeys::END_DATE   => $this->dia( -18 ),
			),
			array( 'post_title' => 'Finalizado' )
		);
		$this->event(
			$coord,
			array( $area ),
			array(),
			array(
				'post_title'  => 'En borrador',
				'post_status' => 'draft',
			)
		);

		$this->acting_as( $coord );
		$m = EventList::model();

		$this->assertSame( 4, $m['total'] );
		$this->assertSame( 4, $m['counts']['all'] );
		// El borrador todavía no tiene fechas: se está preparando.
		$this->assertSame( 2, $m['counts'][ EventMetaKeys::STATE_UPCOMING ] );
		$this->assertSame( 1, $m['counts'][ EventMetaKeys::STATE_OPEN ] );
		$this->assertSame( 1, $m['counts'][ EventMetaKeys::STATE_FINISHED ] );
		$this->assertSame( 1, $m['counts'][ EventList::FILTER_DRAFT ] );

		$_GET[ EventList::VAR_STATE ] = EventMetaKeys::STATE_OPEN;
		$this->assertSame( array( 'Abierto' ), $this->titulos( EventList::model() ) );

		$_GET[ EventList::VAR_STATE ] = EventList::FILTER_DRAFT;
		$m                            = EventList::model();
		$this->assertSame( array( 'En borrador' ), $this->titulos( $m ) );
		$this->assertSame( 'draft', $m['rows'][0]['status'] );

		// Un estado inventado no acota nada: se cae a «todos».
		$_GET[ EventList::VAR_STATE ] = 'lo-que-sea';
		$this->assertSame( 'all', EventList::model()['selection']['state'] );
		$this->assertSame( 4, EventList::model()['total'] );
	}

	/**
	 * Los desplegables acotan por área, tipología y curso, y la búsqueda por título.
	 */
	public function test_the_dropdowns_and_the_search_narrow_the_list() {
		$area   = $this->area( 'Innovación' );
		$coord  = $this->administrator();
		$taller = (int) self::factory()->term->create(
			array(
				'taxonomy' => EventTaxonomies::TYPE,
				'name'     => 'Taller',
			)
		);
		$curso  = (int) self::factory()->term->create(
			array(
				'taxonomy' => EventTaxonomies::COURSE,
				'name'     => '2025-2026',
			)
		);

		$con = $this->event( $coord, array( $area ), array(), array( 'post_title' => 'Jornadas de Innovación' ) );
		$sin = $this->event( $coord, array( $area ), array(), array( 'post_title' => 'Encuentro de centros' ) );
		wp_set_object_terms( $con, array( $taller ), EventTaxonomies::TYPE );
		wp_set_object_terms( $con, array( $curso ), EventTaxonomies::COURSE );

		$this->acting_as( $coord );

		$m = EventList::model();
		$this->assertSame( array( $taller => 'Taller' ), $m['options']['type'] );
		$this->assertSame( array( $curso => '2025-2026' ), $m['options']['course'] );

		$_GET[ EventList::VAR_TYPE ] = (string) $taller;
		$this->assertSame( array( $con ), $this->ids( EventList::model() ) );

		$_GET[ EventList::VAR_TYPE ]   = '';
		$_GET[ EventList::VAR_COURSE ] = (string) $curso;
		$this->assertSame( array( $con ), $this->ids( EventList::model() ) );

		// Ni los acentos ni las mayúsculas deciden una búsqueda.
		$_GET[ EventList::VAR_COURSE ] = '';
		$_GET[ EventList::VAR_SEARCH ] = 'innovacion';
		$this->assertSame( array( $con ), $this->ids( EventList::model() ) );

		$_GET[ EventList::VAR_SEARCH ] = 'centros';
		$this->assertSame( array( $sin ), $this->ids( EventList::model() ) );

		// Nada coincide: se dice por qué y se ofrece quitar los filtros.
		$_GET[ EventList::VAR_SEARCH ] = 'congreso mundial';
		$m                             = EventList::model();
		$this->assertSame( array(), $m['rows'] );
		$this->assertStringContainsString( 'Ningún evento coincide', $m['empty_text'] );
		$this->assertNotSame( '', $m['reset_url'] );
	}

	/**
	 * Un filtro que ya no está entre las opciones se descarta en vez de vaciar la pantalla.
	 */
	public function test_a_filter_that_is_no_longer_an_option_is_dropped() {
		$area  = $this->area( 'Innovación' );
		$coord = $this->administrator();
		$this->event( $coord, array( $area ) );
		$fantasma = (int) self::factory()->term->create(
			array(
				'taxonomy' => EventTaxonomies::TYPE,
				'name'     => 'Congreso',
			)
		);

		$this->acting_as( $coord );
		$_GET[ EventList::VAR_TYPE ] = (string) $fantasma;

		$m = EventList::model();

		$this->assertSame( 0, $m['selection']['type'] );
		$this->assertSame( 1, $m['total'], 'el filtro imposible no deja la pantalla vacía' );
	}

	/**
	 * El desplegable de área solo se ofrece cuando hay algo que elegir.
	 */
	public function test_the_area_dropdown_only_shows_when_there_is_a_choice() {
		$una  = $this->area( 'Formación del Profesorado' );
		$otra = $this->area( 'Innovación' );

		$this->acting_as( $this->organiser( array( $una ) ) );
		$this->assertFalse( EventList::model()['area_filter'] );

		$this->acting_as( $this->organiser( array( $una, $otra ) ) );
		$this->assertTrue( EventList::model()['area_filter'] );

		$this->acting_as( $this->administrator() );
		$this->assertTrue( EventList::model()['area_filter'] );
	}

	// ─── paginación ────────────────────────────────────────────────────────

	/**
	 * La tabla se pagina, y una página que no existe cae en la última.
	 */
	public function test_the_table_is_paged() {
		$coord = $this->administrator();
		$area  = $this->area( 'Innovación' );
		$total = EventList::PAGE_SIZE + 2;

		for ( $i = 0; $i < $total; $i++ ) {
			$id = (int) self::factory()->post->create(
				array(
					'post_type'   => EventPostType::POST_TYPE,
					'post_status' => 'publish',
					'post_author' => $coord,
					'post_title'  => sprintf( 'Evento %02d', $i ),
				)
			);
			wp_set_object_terms( $id, array( $area ), EventTaxonomies::AREA );
		}

		$this->acting_as( $coord );

		$m = EventList::model();
		$this->assertSame( $total, $m['total'] );
		$this->assertSame( 2, $m['pages'] );
		$this->assertSame( 1, $m['page'] );
		$this->assertCount( EventList::PAGE_SIZE, $m['rows'] );

		$_GET[ EventList::VAR_PAGE ] = '2';
		$m                           = EventList::model();
		$this->assertSame( 2, $m['page'] );
		$this->assertCount( 2, $m['rows'] );

		// La página siete no existe: se enseña la última, no una pantalla vacía.
		$_GET[ EventList::VAR_PAGE ] = '7';
		$m                           = EventList::model();
		$this->assertSame( 2, $m['page'] );
		$this->assertCount( 2, $m['rows'] );
	}

	/**
	 * Cambiar un filtro devuelve a la primera página, y lo vacío no viaja en la URL.
	 */
	public function test_changing_a_filter_goes_back_to_the_first_page() {
		$this->acting_as( $this->administrator() );
		$seleccion = array(
			'area'   => 0,
			'type'   => 3,
			'course' => 0,
			'state'  => 'all',
			'search' => '',
			'page'   => 4,
		);

		$url = EventList::url( $seleccion, array( 'state' => EventMetaKeys::STATE_OPEN ) );

		$this->assertStringContainsString( EventList::VAR_STATE . '=' . EventMetaKeys::STATE_OPEN, $url );
		$this->assertStringContainsString( EventList::VAR_TYPE . '=3', $url );
		$this->assertStringNotContainsString( EventList::VAR_PAGE, $url, 'un filtro nuevo empieza por la primera página' );
		$this->assertStringNotContainsString( EventList::VAR_SEARCH, $url, 'lo vacío no viaja' );
	}

	// ─── lo que rodea a la tabla ───────────────────────────────────────────

	/**
	 * El botón de crear, el aviso de vuelta y el texto de la tabla vacía.
	 */
	public function test_the_chrome_around_the_table() {
		$this->acting_as( $this->organiser( array( $this->area( 'Innovación' ) ) ) );

		$m = EventList::model();
		$this->assertTrue( $m['can_create'] );
		$this->assertSame( Shell::url( 'event' ), $m['create_url'] );
		$this->assertStringContainsString( 'Todavía no hay ningún evento de su ámbito', $m['empty_text'] );
		$this->assertSame( '', $m['reset_url'], 'sin filtros no hay nada que quitar' );

		$_GET[ EventList::VAR_NOTICE ] = 'creado';
		$aviso                         = EventList::model()['notice'];
		$this->assertSame( 'ok', $aviso['type'] );
		$this->assertStringContainsString( 'Evento creado', $aviso['text'] );

		$_GET[ EventList::VAR_NOTICE ] = 'permiso';
		$this->assertSame( 'error', EventList::model()['notice']['type'] );

		$_GET[ EventList::VAR_NOTICE ] = 'lo-que-sea';
		$this->assertSame( '', EventList::model()['notice']['type'] );
	}

	/**
	 * La pantalla pintada enseña las filas y el botón, y nada de otro ámbito.
	 */
	public function test_the_screen_is_painted_from_its_model() {
		$mia  = $this->area( 'Formación del Profesorado' );
		$otra = $this->area( 'Innovación' );
		$this->event( $this->administrator(), array( $mia ), array(), array( 'post_title' => 'Jornadas de mi área' ) );
		$this->event( $this->administrator(), array( $otra ), array(), array( 'post_title' => 'Jornadas ajenas' ) );

		$this->acting_as( $this->organiser( array( $mia ) ) );
		$html = EventList::render();

		$this->assertStringContainsString( 'Jornadas de mi área', $html );
		$this->assertStringNotContainsString( 'Jornadas ajenas', $html );
		$this->assertStringContainsString( 'Crear evento', $html );
	}

	// ─── la papelera ───────────────────────────────────────────────────────

	/**
	 * Mandar el «Restaurar» de una fila y devolver por dónde salió.
	 *
	 * @param int         $uid      Who submits.
	 * @param int         $event_id Event.
	 * @param string|null $nonce    Null for the good one, a string to forge it, '' for none.
	 * @return string|null
	 */
	private function restaurar( int $uid, int $event_id, ?string $nonce = null ) {
		$this->acting_as( $uid );
		$campos = array(
			EventList::FIELD_DO    => 'restore',
			EventList::FIELD_EVENT => (string) $event_id,
		);
		if ( null === $nonce ) {
			$this->post( $campos, EventList::NONCE_ACTION, EventList::nonce_name( $event_id ) );
		} else {
			$campos[ EventList::nonce_name( $event_id ) ] = $nonce;
			$this->post( $campos );
		}
		return $this->exit_url( array( EventList::class, 'handle' ) );
	}

	/**
	 * El listado normal no enseña lo que está en la papelera; el filtro sí.
	 */
	public function test_the_normal_list_leaves_out_what_is_in_the_trash() {
		$area = $this->area( 'Innovación' );
		$uid  = $this->administrator();
		$viva = $this->event( $uid, array( $area ), array(), array( 'post_title' => 'Jornadas vivas' ) );
		$rota = $this->event( $uid, array( $area ), array(), array( 'post_title' => 'Jornadas borradas' ) );

		wp_trash_post( $rota );
		$this->acting_as( $uid );

		$m = EventList::model();
		$this->assertSame( array( $viva ), $this->ids( $m ), 'la papelera no sale en el listado' );
		$this->assertSame( 1, $m['counts'][ EventList::FILTER_TRASH ], 'pero se cuenta' );
		$this->assertSame( 1, $m['counts']['all'], 'y no infla las demás cifras' );

		$_GET[ EventList::VAR_STATE ] = EventList::FILTER_TRASH;
		$m                            = EventList::model();
		$this->assertSame( array( $rota ), $this->ids( $m ) );
		$this->assertSame( 'trash', (string) $m['rows'][0]['status'] );
		$this->assertSame( '', (string) $m['rows'][0]['url'], 'lo de la papelera no enlaza a su taller' );
	}

	/**
	 * Restaurar devuelve el evento, en borrador, y no lo destruye nunca.
	 */
	public function test_restoring_brings_the_event_back_as_a_draft() {
		$area   = $this->area( 'Innovación' );
		$uid    = $this->administrator();
		$evento = $this->event( $uid, array( $area ), array(), array( 'post_title' => 'Jornadas de otoño' ) );

		wp_trash_post( $evento );
		$this->assertInstanceOf( WP_Post::class, get_post( $evento ), 'la entrada sigue existiendo' );
		$this->assertSame( 'Jornadas de otoño', get_the_title( $evento ) );

		$url = $this->restaurar( $uid, $evento );

		$this->assertSame( 'draft', get_post_status( $evento ) );
		$this->assertSame( 'restaurado', $this->query_arg( (string) $url, EventList::VAR_NOTICE ) );

		$this->acting_as( $uid );
		$m = EventList::model();
		$this->assertSame( array( $evento ), $this->ids( $m ), 'y vuelve al listado normal' );
		$this->assertSame( 0, $m['counts'][ EventList::FILTER_TRASH ] );
	}

	/**
	 * Sin permiso —o sin nonce— no se restaura nada: se mira el estado después.
	 */
	public function test_without_permission_or_nonce_nothing_is_restored() {
		$mia    = $this->area( 'Formación del Profesorado' );
		$otra   = $this->area( 'Innovación' );
		$dueno  = $this->organiser( array( $mia ) );
		$ajena  = $this->organiser( array( $otra ) );
		$evento = $this->event( $dueno, array( $mia ) );

		wp_trash_post( $evento );

		$url = $this->restaurar( $ajena, $evento );
		$this->assertSame( 'trash', get_post_status( $evento ), 'el área ajena no lo saca' );
		$this->assertSame( 'permiso', $this->query_arg( (string) $url, EventList::VAR_NOTICE ) );

		$this->assertNull( $this->restaurar( $dueno, $evento, 'basura' ) );
		$this->assertSame( 'trash', get_post_status( $evento ), 'con el nonce mal, tampoco' );

		$this->assertNull( $this->restaurar( $dueno, $evento, '' ) );
		$this->assertSame( 'trash', get_post_status( $evento ) );

		// Y quien sí puede, sí.
		$this->restaurar( $dueno, $evento );
		$this->assertSame( 'draft', get_post_status( $evento ) );
	}

	/**
	 * Una sección satélite no se restaura desde aquí: se hace en su taller.
	 */
	public function test_a_satellite_page_is_not_restored_from_the_list() {
		$area    = $this->area( 'Innovación' );
		$uid     = $this->administrator();
		$evento  = $this->event( $uid, array( $area ) );
		$seccion = $this->event_page( $evento, 'programa' );

		wp_trash_post( $seccion );
		$this->restaurar( $uid, $seccion );

		$this->assertSame( 'trash', get_post_status( $seccion ) );
	}

	/**
	 * La pantalla pintada enseña «Papelera (N)» y el botón de restaurar.
	 */
	public function test_the_trash_is_painted_with_its_restore_button() {
		$area   = $this->area( 'Innovación' );
		$uid    = $this->administrator();
		$evento = $this->event( $uid, array( $area ), array(), array( 'post_title' => 'Jornadas borradas' ) );

		wp_trash_post( $evento );
		$this->acting_as( $uid );

		$html = EventList::render();
		$this->assertStringContainsString( 'Papelera (1)', $html );
		$this->assertStringNotContainsString( 'Jornadas borradas', $html, 'el listado normal no la enseña' );

		$_GET[ EventList::VAR_STATE ] = EventList::FILTER_TRASH;
		$html                         = EventList::render();
		$this->assertStringContainsString( 'Jornadas borradas', $html );
		$this->assertStringContainsString( 'Restaurar', $html );
		$this->assertStringContainsString( EventList::nonce_name( $evento ), $html, 'cada fila lleva su nonce' );
		$this->assertStringContainsString( 'escritorio de WordPress', $html, 'el borrado definitivo se remite al escritorio' );
	}

	/**
	 * El arranque engancha el shortcode y el «Restaurar».
	 */
	public function test_register_hooks_the_shortcode() {
		EventList::register();

		$this->assertTrue( shortcode_exists( EventList::SHORTCODE ) );
		$this->assertNotFalse( has_action( 'init', array( EventList::class, 'handle' ) ) );
	}
}
