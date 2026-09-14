<?php
/**
 * Tests for the «histórico» state: an event closed to editing for good.
 *
 * @package Evt
 */

use Evt\Access\EventAccess;
use Evt\Meta\EventMetaKeys;
use Evt\PublicFront\EventList;
use Evt\PublicFront\EventView;
use Evt\PublicFront\EventWorkspace;

/**
 * El cierre explícito de un evento, por encima de la regla del área.
 *
 * Lo que se prueba no es el aviso —eso lo dice cualquiera— sino el estado de
 * la base de datos DESPUÉS de mandar el POST: si el título cambió, si la
 * sección se fue a la papelera, si la marca se puso. Un mensaje bonito con la
 * fila ya borrada no vale de nada.
 */
class Test_Archived extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Con las páginas del aplicativo ya creadas.
	 */
	public function set_up() {
		parent::set_up();
		$this->pages();
	}

	/**
	 * Quien administra el aplicativo.
	 *
	 * @return int User ID.
	 */
	private function manager(): int {
		$this->app();
		return (int) self::factory()->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Marcar o desmarcar un evento a mano, sin pasar por la pantalla.
	 *
	 * @param int  $event_id Event.
	 * @param bool $marcado  Whether it is archived.
	 * @return void
	 */
	private function archivar( int $event_id, bool $marcado = true ): void {
		update_post_meta( $event_id, EventMetaKeys::ARCHIVED, $marcado );
	}

	/**
	 * Mandar una operación del taller y devolver por dónde salió.
	 *
	 * @param int                   $uid        Who submits.
	 * @param string                $op         Operation.
	 * @param int                   $event_id   Event.
	 * @param int                   $section_id Satellite page, 0 for the panels.
	 * @param array<string, string> $extra      Extra fields.
	 * @return string|null
	 */
	private function submit( int $uid, string $op, int $event_id, int $section_id = 0, array $extra = array() ) {
		$this->acting_as( $uid );
		$campos = array_merge(
			array(
				EventWorkspace::FIELD_DO      => $op,
				EventWorkspace::FIELD_EVENT   => (string) $event_id,
				EventWorkspace::FIELD_SECTION => (string) $section_id,
			),
			$extra
		);
		$this->post( $campos, EventWorkspace::nonce_action( $op ), EventWorkspace::nonce_name( $op, $section_id ) );
		return $this->exit_url( array( EventWorkspace::class, 'handle' ) );
	}

	// ─── marcar lo hace el área ────────────────────────────────────────────

	/**
	 * El área marca su propio evento: es suyo y sabe cuándo está terminado.
	 */
	public function test_the_area_marks_its_own_event() {
		$area   = $this->area( 'Formación del Profesorado' );
		$uid    = $this->organiser( array( $area ) );
		$evento = $this->event( $uid, array( $area ) );

		$this->submit( $uid, EventWorkspace::OP_ARCHIVE, $evento );

		$this->assertTrue( EventAccess::is_archived( $evento ) );
	}

	/**
	 * Y al marcarlo se cierra de verdad: el mismo POST no se muerde la cola.
	 *
	 * Es la trampa de esta regla. Marcar exige poder editar el evento y lo
	 * primero que hace la marca es quitar esa posibilidad, así que si la
	 * comprobación mirase `can_edit()` en vez de `can_open()`, o fuese después
	 * de la de edición, marcar sería imposible o solo funcionaría una vez y por
	 * casualidad.
	 */
	public function test_marking_closes_the_event_right_after_marking_it() {
		$area   = $this->area( 'Formación del Profesorado' );
		$uid    = $this->organiser( array( $area ) );
		$evento = $this->event( $uid, array( $area ) );

		$this->submit( $uid, EventWorkspace::OP_ARCHIVE, $evento );

		$this->assertTrue( EventAccess::is_archived( $evento ), 'la marca tenía que ponerse' );
		$this->assertFalse( EventAccess::can_edit( $uid, $evento ), 'y cerrar el evento acto seguido' );
		$this->assertTrue( EventAccess::can_open( $uid, $evento ), 'sin echar de su taller a quien lo organizó' );
	}

	/**
	 * Administración también, que trabaja sobre todas las áreas.
	 */
	public function test_the_administration_marks_the_event_too() {
		$area   = $this->area( 'Formación del Profesorado' );
		$evento = $this->event( $this->organiser( array( $area ) ), array( $area ) );

		$this->submit( $this->administrator(), EventWorkspace::OP_ARCHIVE, $evento );

		$this->assertTrue( EventAccess::is_archived( $evento ) );
	}

	/**
	 * Otra área no: el acotado por área manda también para esto.
	 */
	public function test_another_area_does_not_mark_the_event() {
		$suya   = $this->area( 'Formación del Profesorado' );
		$otra   = $this->area( 'Ordenación e Innovación' );
		$evento = $this->event( $this->organiser( array( $suya ) ), array( $suya ) );

		$this->submit( $this->organiser( array( $otra ) ), EventWorkspace::OP_ARCHIVE, $evento );

		$this->assertFalse( EventAccess::is_archived( $evento ) );
	}

	// ─── desmarcar solo lo hace administración ─────────────────────────────

	/**
	 * Administración desmarca: alguien tiene que poder reabrirlo.
	 */
	public function test_the_administration_unmarks_the_event() {
		$area   = $this->area( 'Formación del Profesorado' );
		$evento = $this->event( $this->organiser( array( $area ) ), array( $area ) );
		$this->archivar( $evento );

		$this->submit( $this->manager(), EventWorkspace::OP_UNARCHIVE, $evento );

		$this->assertFalse( EventAccess::is_archived( $evento ) );
	}

	/**
	 * El área no, aunque mande el POST a mano y con su nonce.
	 *
	 * Es el caso que importa: el botón no se le pinta, así que si desmarcase
	 * sería porque alguien ha escrito el formulario por su cuenta. Y es lo que
	 * hace que cerrar signifique algo: si el área lo reabriese sola, el cierre
	 * sería una preferencia y no un cierre.
	 */
	public function test_an_area_cannot_unmark_the_event_even_by_posting_by_hand() {
		$area   = $this->area( 'Formación del Profesorado' );
		$uid    = $this->organiser( array( $area ) );
		$evento = $this->event( $uid, array( $area ) );
		$this->archivar( $evento );

		$this->submit( $uid, EventWorkspace::OP_UNARCHIVE, $evento );

		$this->assertTrue( EventAccess::is_archived( $evento ) );
	}

	/**
	 * Y ver todas las áreas tampoco basta: la raya de desmarcar es administrar
	 * el aplicativo, no salirse del área.
	 *
	 * Son dos capacidades distintas y aquí se separan a mano, porque en el
	 * reparto normal las dos son de administración y así no se sabría cuál de
	 * las dos está abriendo la puerta.
	 */
	public function test_seeing_every_area_is_not_enough_to_unmark() {
		$area   = $this->area( 'Formación del Profesorado' );
		$uid    = $this->organiser( array( $area ) );
		$evento = $this->event( $uid, array( $area ) );
		$this->archivar( $evento );
		get_user_by( 'id', $uid )->add_cap( EventAccess::CAP_ALL_AREAS );

		$this->submit( $uid, EventWorkspace::OP_UNARCHIVE, $evento );

		$this->assertTrue( EventAccess::is_archived( $evento ) );
	}

	/**
	 * La misma asimetría por la puerta de al lado: el `auth_callback` de la meta.
	 *
	 * Con el evento abierto la marca la escribe su área; en cuanto está
	 * cerrado, solo administración. Sin esto, un área se desmarcaría el evento
	 * con un `update_post_meta()` desde la REST y volvería a poder editarlo.
	 */
	public function test_the_mark_follows_the_same_asymmetry_through_the_meta() {
		$area   = $this->area( 'Formación del Profesorado' );
		$uid    = $this->organiser( array( $area ) );
		$ajena  = $this->organiser( array( $this->area( 'Ordenación e Innovación' ) ) );
		$admin  = $this->manager();
		$evento = $this->event( $uid, array( $area ) );

		$this->assertTrue( user_can( $uid, 'edit_post_meta', $evento, EventMetaKeys::ARCHIVED ), 'su área la pone' );
		$this->assertFalse( user_can( $ajena, 'edit_post_meta', $evento, EventMetaKeys::ARCHIVED ), 'otra área no' );

		$this->archivar( $evento );

		$this->assertFalse( user_can( $uid, 'edit_post_meta', $evento, EventMetaKeys::ARCHIVED ), 'y ya no la quita' );
		$this->assertTrue( user_can( $admin, 'edit_post_meta', $evento, EventMetaKeys::ARCHIVED ), 'administración sí' );
	}

	/**
	 * La marca se pone en el evento, no en una de sus páginas.
	 */
	public function test_the_mark_is_refused_on_a_satellite_page() {
		$area    = $this->area( 'Formación del Profesorado' );
		$uid     = $this->organiser( array( $area ) );
		$evento  = $this->event( $uid, array( $area ) );
		$seccion = $this->event_page( $evento, 'programa' );

		$this->submit( $uid, EventWorkspace::OP_ARCHIVE, $seccion );

		$this->assertFalse( EventAccess::is_archived( $seccion ) );
	}

	// ─── con el evento marcado, el área no toca nada ───────────────────────

	/**
	 * Ni el evento, ni sus secciones, ni por el escritorio ni por la REST.
	 */
	public function test_the_area_edits_nothing_of_a_marked_event() {
		$area    = $this->area( 'Formación del Profesorado' );
		$uid     = $this->organiser( array( $area ) );
		$evento  = $this->event( $uid, array( $area ) );
		$seccion = $this->event_page( $evento, 'programa' );
		$this->archivar( $evento );

		$this->assertFalse( EventAccess::can_edit( $uid, $evento ), 'el evento' );
		$this->assertFalse( EventAccess::can_edit( $uid, $seccion ), 'la sección' );
		$this->assertFalse( EventAccess::can_publish( $uid, $evento ), 'publicar' );
		// Y la capa que de verdad cierra la puerta, la de las meta caps: sin
		// ella quedarían abiertas la edición rápida, el enlace directo y la REST.
		$this->assertFalse( user_can( $uid, 'edit_post', $evento ) );
		$this->assertFalse( user_can( $uid, 'edit_post', $seccion ) );
		$this->assertFalse( user_can( $uid, 'delete_post', $seccion ) );
		$this->assertFalse( user_can( $uid, 'edit_post_meta', $evento, EventMetaKeys::TAGLINE ) );
	}

	/**
	 * Quien ve todas las áreas tampoco: para el cierre es un área más, y quien
	 * lo abre es quien administra el aplicativo.
	 */
	public function test_seeing_every_area_does_not_open_a_marked_event() {
		$area   = $this->area( 'Formación del Profesorado' );
		$evento = $this->event( $this->organiser( array( $area ) ), array( $area ) );
		$this->archivar( $evento );
		$fuera = $this->organiser( array( $this->area( 'Ordenación e Innovación' ) ) );
		get_user_by( 'id', $fuera )->add_cap( EventAccess::CAP_ALL_AREAS );

		$this->assertTrue( EventAccess::can_open( $fuera, $evento ), 'lo abre para consultarlo' );
		$this->assertFalse( EventAccess::can_edit( $fuera, $evento ), 'pero no lo edita' );
		$this->assertTrue( EventAccess::can_edit( $this->manager(), $evento ), 'y administración sí' );
	}

	/**
	 * El POST del panel de datos no cambia el título.
	 */
	public function test_the_data_panel_does_not_save_on_a_marked_event() {
		$area   = $this->area( 'Formación del Profesorado' );
		$uid    = $this->organiser( array( $area ) );
		$evento = $this->event( $uid, array( $area ), array(), array( 'post_title' => 'Jornadas de otoño' ) );
		$this->archivar( $evento );

		$this->submit(
			$uid,
			EventWorkspace::PANEL_SETTINGS,
			$evento,
			0,
			array(
				EventWorkspace::FIELD_TITLE => 'Título nuevo',
				EventMetaKeys::START_DATE   => '2026-10-01',
				EventMetaKeys::TAGLINE      => 'Un lema cualquiera',
			)
		);

		$this->assertSame( 'Jornadas de otoño', (string) get_post_field( 'post_title', $evento ) );
		$this->assertSame( '', (string) get_post_meta( $evento, EventMetaKeys::TAGLINE, true ) );
	}

	/**
	 * Ni las acciones de fila: la sección ni se borra ni se despublica.
	 */
	public function test_the_row_actions_do_nothing_on_a_marked_event() {
		$area    = $this->area( 'Formación del Profesorado' );
		$uid     = $this->organiser( array( $area ) );
		$evento  = $this->event( $uid, array( $area ) );
		$seccion = $this->event_page( $evento, 'programa' );
		$this->archivar( $evento );

		$this->submit( $uid, 'delete', $evento, $seccion );
		$this->assertSame( 'publish', (string) get_post_status( $seccion ), 'no tenía que irse a la papelera' );

		$this->submit( $uid, 'unpublish', $evento, $seccion );
		$this->assertSame( 'publish', (string) get_post_status( $seccion ), 'no tenía que despublicarse' );
	}

	/**
	 * Administración sí edita: alguien tiene que poder corregir una errata.
	 */
	public function test_the_administration_still_edits_a_marked_event() {
		$area   = $this->area( 'Formación del Profesorado' );
		$evento = $this->event( $this->organiser( array( $area ) ), array( $area ), array(), array( 'post_title' => 'Con errata' ) );
		$admin  = $this->manager();
		$this->archivar( $evento );

		$this->assertTrue( EventAccess::can_edit( $admin, $evento ) );

		$this->submit(
			$admin,
			EventWorkspace::PANEL_SETTINGS,
			$evento,
			0,
			array(
				EventWorkspace::FIELD_TITLE => 'Sin errata',
				EventMetaKeys::START_DATE   => '2026-10-01',
			)
		);

		$this->assertSame( 'Sin errata', (string) get_post_field( 'post_title', $evento ) );
	}

	// ─── el taller sigue abierto, en solo lectura ──────────────────────────

	/**
	 * Se entra, se consulta y se lee por qué; no se esconde.
	 */
	public function test_the_workshop_opens_read_only() {
		$area   = $this->area( 'Formación del Profesorado' );
		$uid    = $this->organiser( array( $area ) );
		$evento = $this->event( $uid, array( $area ) );
		$this->event_page( $evento, 'programa' );
		$this->archivar( $evento );

		$this->acting_as( $uid );
		$_GET[ EventWorkspace::ARG_EVENT ] = (string) $evento;
		$m                                 = EventWorkspace::model();

		$this->assertSame( '', (string) $m['aviso'], 'el taller tiene que abrirse igualmente' );
		$this->assertSame( $evento, (int) $m['event_id'] );
		$this->assertTrue( (bool) $m['archived'] );
		$this->assertFalse( (bool) $m['can_edit'] );
		$this->assertFalse( (bool) $m['can_archive'], 'ya está marcado' );
		$this->assertFalse( (bool) $m['can_unarchive'], 'y desmarcarlo no es suyo' );
		$this->assertCount( 1, (array) $m['sections'], 'y con sus secciones a la vista' );

		$html = EventWorkspace::render();
		$this->assertStringContainsString( 'histórico', $html );
		$this->assertStringContainsString( '<fieldset class="evt-solo-lectura" disabled>', $html );
	}

	/**
	 * Al área se le pinta el botón de marcar, y como acción normal suya.
	 *
	 * Sin recuadro amarillo: el amarillo es la convención de lo que solo ve
	 * administración, y cerrar el evento ya no lo es.
	 */
	public function test_the_area_is_painted_the_switch_as_one_of_its_own_actions() {
		$area   = $this->area( 'Formación del Profesorado' );
		$uid    = $this->organiser( array( $area ) );
		$evento = $this->event( $uid, array( $area ) );

		$_GET[ EventWorkspace::ARG_EVENT ] = (string) $evento;
		$_GET[ EventWorkspace::ARG_PANEL ] = EventWorkspace::PANEL_SETTINGS;

		$this->acting_as( $uid );
		$html = EventWorkspace::render();

		$this->assertStringContainsString( 'Marcar como histórico', $html );
		$this->assertStringNotContainsString( 'evt-solo-admin', $html );
	}

	/**
	 * Y el diálogo avisa de que no hay vuelta atrás sin administración.
	 */
	public function test_the_dialog_warns_that_there_is_no_way_back() {
		$area   = $this->area( 'Formación del Profesorado' );
		$uid    = $this->organiser( array( $area ) );
		$evento = $this->event( $uid, array( $area ) );

		$_GET[ EventWorkspace::ARG_EVENT ] = (string) $evento;
		$_GET[ EventWorkspace::ARG_PANEL ] = EventWorkspace::PANEL_SETTINGS;

		$this->acting_as( $uid );
		$html = EventWorkspace::render();

		$this->assertMatchesRegularExpression(
			'/data-evt-confirm="[^"]*no hay vuelta atrás[^"]*administre el aplicativo/u',
			$html
		);
	}

	/**
	 * Otra área ni ve el botón: no es su evento.
	 */
	public function test_another_area_is_painted_no_switch_at_all() {
		$suya   = $this->area( 'Formación del Profesorado' );
		$evento = $this->event( $this->organiser( array( $suya ) ), array( $suya ) );

		$_GET[ EventWorkspace::ARG_EVENT ] = (string) $evento;
		$_GET[ EventWorkspace::ARG_PANEL ] = EventWorkspace::PANEL_SETTINGS;

		$this->acting_as( $this->organiser( array( $this->area( 'Ordenación e Innovación' ) ) ) );
		$html = EventWorkspace::render();

		$this->assertStringNotContainsString( 'Marcar como histórico', $html );
	}

	/**
	 * Reabrirlo sí es de administración, y por eso va en el recuadro amarillo.
	 */
	public function test_reopening_is_painted_only_for_the_administration() {
		$area   = $this->area( 'Formación del Profesorado' );
		$uid    = $this->organiser( array( $area ) );
		$evento = $this->event( $uid, array( $area ) );
		$this->archivar( $evento );

		$_GET[ EventWorkspace::ARG_EVENT ] = (string) $evento;
		$_GET[ EventWorkspace::ARG_PANEL ] = EventWorkspace::PANEL_SETTINGS;

		$this->acting_as( $this->manager() );
		$html = EventWorkspace::render();
		$this->assertStringContainsString( 'Volver a abrir el evento', $html );
		$this->assertStringContainsString( 'evt-solo-admin', $html );

		$this->acting_as( $uid );
		$suyo = EventWorkspace::render();
		$this->assertStringNotContainsString( 'Volver a abrir el evento', $suyo );
		$this->assertStringNotContainsString( 'Marcar como histórico', $suyo, 'ya está marcado' );
	}

	// ─── el listado ────────────────────────────────────────────────────────

	/**
	 * El listado lo sigue enseñando, marcado, y deja filtrar por ese estado.
	 */
	public function test_the_list_shows_it_as_one_more_state_and_filters_by_it() {
		$area  = $this->area( 'Formación del Profesorado' );
		$uid   = $this->organiser( array( $area ) );
		$viejo = $this->event( $uid, array( $area ), array(), array( 'post_title' => 'Jornadas de 2019' ) );
		$nuevo = $this->event( $uid, array( $area ), array(), array( 'post_title' => 'Jornadas de 2026' ) );
		$this->archivar( $viejo );
		$this->acting_as( $uid );

		$m   = EventList::model();
		$ids = array_map( 'intval', array_column( (array) $m['rows'], 'id' ) );
		$this->assertContains( $viejo, $ids, 'un evento histórico no desaparece del listado' );
		$this->assertContains( $nuevo, $ids );
		$this->assertSame( 1, (int) $m['counts'][ EventList::FILTER_ARCHIVED ] );

		$_GET[ EventList::VAR_STATE ] = EventList::FILTER_ARCHIVED;
		$filtrado                     = EventList::model();

		$this->assertSame(
			array( $viejo ),
			array_map( 'intval', array_column( (array) $filtrado['rows'], 'id' ) )
		);
		$this->assertTrue( (bool) $filtrado['rows'][0]['archived'] );
		$this->assertStringContainsString( 'Histórico', EventList::html( $filtrado ) );
	}

	// ─── la página pública y la otra regla ─────────────────────────────────

	/**
	 * La página pública no cambia: es un cierre de edición, no un despublicado.
	 */
	public function test_the_public_page_does_not_change() {
		$area   = $this->area( 'Formación del Profesorado' );
		$evento = $this->event(
			$this->organiser( array( $area ) ),
			array( $area ),
			array(
				EventMetaKeys::START_DATE => '2026-10-01',
				EventMetaKeys::TAGLINE    => 'Aprender juntos',
			)
		);
		$this->event_page( $evento, 'programa' );

		$this->acting_as( 0 );
		$antes = EventView::model( $evento, 'El contenido de siempre.' );
		$this->archivar( $evento );
		$despues = EventView::model( $evento, 'El contenido de siempre.' );

		$this->assertSame( $antes, $despues );
		$this->assertSame( 'publish', (string) get_post_status( $evento ) );
	}

	/**
	 * Las dos reglas conviven: la de ADR-0012 y el cierre explícito.
	 *
	 * Un evento que ya terminó lo sigue editando su área mientras nadie lo
	 * cierre; el cierre es una decisión aparte y va por encima, así que basta
	 * una de las dos para decir que no.
	 */
	public function test_a_finished_event_is_still_editable_until_it_is_marked() {
		$area   = $this->area( 'Formación del Profesorado' );
		$uid    = $this->organiser( array( $area ) );
		$ayer   = gmdate( 'Y-m-d', time() - ( 30 * DAY_IN_SECONDS ) );
		$evento = $this->event(
			$uid,
			array( $area ),
			array(
				EventMetaKeys::START_DATE => $ayer,
				EventMetaKeys::END_DATE   => $ayer,
			)
		);

		$this->assertTrue( EventAccess::can_edit( $uid, $evento ), 'finalizado no es cerrado' );

		$this->archivar( $evento );
		$this->assertFalse( EventAccess::can_edit( $uid, $evento ) );
		$this->assertStringContainsString( 'histórico', EventAccess::why_not_editable( $uid, $evento ) );
	}
}
