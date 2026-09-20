<?php
/**
 * Tests for the event workshop: its three panels and every mutation.
 *
 * @package Evt
 */

use Evt\Access\EventAccess;
use Evt\Meta\EventMetaKeys;
use Evt\PostType\EventPostType;
use Evt\PublicFront\EventWorkspace;
use Evt\Taxonomy\EventTaxonomies;

/**
 * El taller de un evento.
 *
 * El panel de secciones es la pantalla que hoy no existe: lista, ordena,
 * publica, despublica y borra. Todo por POST con su nonce y con `EventAccess`
 * comprobado, así que lo que se prueba no es solo el aviso —eso lo dice
 * cualquiera— sino el estado de la base de datos DESPUÉS de intentarlo.
 */
class Test_Event_Workspace extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Con las páginas del aplicativo ya creadas.
	 */
	public function set_up() {
		parent::set_up();
		$this->pages();
	}

	/**
	 * Mandar una operación del taller y devolver por dónde salió.
	 *
	 * @param int                   $uid        Who submits.
	 * @param string                $op         Operation.
	 * @param int                   $event_id   Event.
	 * @param int                   $section_id Satellite page, 0 for the panels.
	 * @param array<string, string> $extra      Extra fields.
	 * @param string|null           $nonce      Null for the good one, a string to forge it, '' for none.
	 * @return string|null
	 */
	private function submit( int $uid, string $op, int $event_id, int $section_id = 0, array $extra = array(), ?string $nonce = null ) {
		$this->acting_as( $uid );
		$campos = array_merge(
			array(
				EventWorkspace::FIELD_DO      => $op,
				EventWorkspace::FIELD_EVENT   => (string) $event_id,
				EventWorkspace::FIELD_SECTION => (string) $section_id,
			),
			$extra
		);
		if ( null === $nonce ) {
			$this->post( $campos, EventWorkspace::nonce_action( $op ), EventWorkspace::nonce_name( $op, $section_id ) );
		} else {
			$campos[ EventWorkspace::nonce_name( $op, $section_id ) ] = $nonce;
			$this->post( $campos );
		}
		return $this->exit_url( array( EventWorkspace::class, 'handle' ) );
	}

	/**
	 * Los identificadores de las páginas satélite, en su orden.
	 *
	 * @param int $event_id Event.
	 * @return int[]
	 */
	private function orden( int $event_id ): array {
		return array_map( 'intval', wp_list_pluck( EventWorkspace::children( $event_id ), 'ID' ) );
	}

	/**
	 * El aviso que la última acción dejó para alguien.
	 *
	 * @param int $uid User.
	 * @return array<string, mixed>
	 */
	private function flash( int $uid ): array {
		return (array) get_transient( 'evt_ws_flash_' . $uid );
	}

	// ─── el modelo ─────────────────────────────────────────────────────────

	/**
	 * Sin sesión, sin evento o con un evento de otro ámbito, no se abre el taller.
	 */
	public function test_the_workshop_does_not_open_without_permission() {
		$mia    = $this->area( 'Formación del Profesorado' );
		$otra   = $this->area( 'Innovación' );
		$evento = $this->event( $this->administrator(), array( $otra ) );

		$this->acting_as( 0 );
		$this->assertStringContainsString( 'Debe iniciar sesión', EventWorkspace::model()['aviso'] );

		// Sin `?evento=` esto no es «no hay taller»: es la pantalla de crear uno.
		$this->acting_as( $this->organiser( array( $mia ) ) );
		$this->assertTrue( EventWorkspace::model()['nuevo'], 'sin ?evento= se crea uno' );

		// Una página satélite no es un evento: el taller es del evento.
		$_GET[ EventWorkspace::ARG_EVENT ] = (string) $this->event_page( $evento, 'programa' );
		$this->assertStringContainsString( 'Elija uno en la lista', EventWorkspace::model()['aviso'] );

		$_GET[ EventWorkspace::ARG_EVENT ] = (string) $evento;
		$m                                 = EventWorkspace::model();
		$this->assertStringContainsString( 'de otro ámbito', $m['aviso'] );
		$this->assertSame( 'error', $m['aviso_tipo'] );
		$this->assertSame( array(), $m['sections'], 'ni se enumeran sus secciones' );
	}

	/**
	 * Los paneles del área, y el que se pide en la URL.
	 *
	 * Son los cinco cuerpos de trabajo de una jornada —páginas, ponentes,
	 * programa, talleres y participantes— más los dos de configuración y el de
	 * «Código», que la ve también quien organiza porque el CSS a medida de su
	 * evento lo escribe él (ADR-0014). Dentro solo está el CSS; el JavaScript
	 * se queda en administración.
	 */
	public function test_the_panels_of_the_area() {
		$area   = $this->area( 'Innovación' );
		$uid    = $this->organiser( array( $area ) );
		$evento = $this->event( $uid, array( $area ) );

		$this->acting_as( $uid );
		$_GET[ EventWorkspace::ARG_EVENT ] = (string) $evento;

		$m = EventWorkspace::model();
		$this->assertSame(
			array(
				EventWorkspace::PANEL_SECTIONS,
				EventWorkspace::PANEL_SPEAKERS,
				EventWorkspace::PANEL_PROGRAMME,
				EventWorkspace::PANEL_WORKSHOPS,
				EventWorkspace::PANEL_SIGNUP,
				EventWorkspace::PANEL_PEOPLE,
				EventWorkspace::PANEL_SETTINGS,
				EventWorkspace::PANEL_LOOK,
				EventWorkspace::PANEL_CODE,
			),
			array_keys( $m['panels'] )
		);
		$this->assertSame( EventWorkspace::PANEL_SECTIONS, $m['panel'], 'por defecto, las páginas' );
		$this->assertSame( $evento, $m['event_id'] );
		$this->assertSame( (string) get_permalink( $evento ), $m['view_url'] );
		$this->assertTrue( $m['can_publish'] );
		$this->assertFalse( $m['can_set_area'], 'quien no coordina no regala el evento a otra área' );

		$_GET[ EventWorkspace::ARG_PANEL ] = EventWorkspace::PANEL_LOOK;
		$this->assertSame( EventWorkspace::PANEL_LOOK, EventWorkspace::model()['panel'] );

		$_GET[ EventWorkspace::ARG_PANEL ] = 'inventado';
		$this->assertSame( EventWorkspace::PANEL_SECTIONS, EventWorkspace::model()['panel'] );
	}

	/**
	 * La tabla de secciones: su orden, su tipo, su estado y sus extremos.
	 */
	public function test_the_sections_panel_lists_the_satellite_pages() {
		$area   = $this->area( 'Innovación' );
		$uid    = $this->administrator();
		$evento = $this->event( $uid, array( $area ) );

		$primera = $this->event_page(
			$evento,
			'programa',
			array(
				'post_title' => 'Programa',
				'menu_order' => 10,
			)
		);
		$segunda = $this->event_page(
			$evento,
			'ponentes',
			array(
				'post_title'  => 'Ponentes',
				'post_status' => 'draft',
				'menu_order'  => 20,
			)
		);

		$this->acting_as( $uid );
		$_GET[ EventWorkspace::ARG_EVENT ] = (string) $evento;
		$filas                             = EventWorkspace::model()['sections'];

		$this->assertCount( 2, $filas );
		$this->assertSame( $primera, $filas[0]['id'] );
		$this->assertSame( 1, $filas[0]['order'] );
		$this->assertSame( 'Programa', $filas[0]['type_label'] );
		$this->assertTrue( $filas[0]['published'] );
		$this->assertTrue( $filas[0]['first'] );
		$this->assertFalse( $filas[0]['last'] );
		$this->assertStringContainsString( 'seccion=' . $primera, (string) $filas[0]['edit_url'] );

		$this->assertSame( $segunda, $filas[1]['id'] );
		$this->assertFalse( $filas[1]['published'] );
		$this->assertTrue( $filas[1]['last'] );
	}

	// ─── las mutaciones del panel de secciones ─────────────────────────────

	/**
	 * Subir y bajar renumeran la lista entera, que es lo que hace falta cuando
	 * las páginas heredadas traen el mismo número o ninguno.
	 */
	public function test_the_sections_can_be_reordered() {
		$area   = $this->area( 'Innovación' );
		$uid    = $this->administrator();
		$evento = $this->event( $uid, array( $area ) );
		$a      = $this->event_page( $evento, 'programa', array( 'post_title' => 'A' ) );
		$b      = $this->event_page( $evento, 'ponentes', array( 'post_title' => 'B' ) );
		$c      = $this->event_page( $evento, 'contacto', array( 'post_title' => 'C' ) );

		$this->assertSame( array( $a, $b, $c ), $this->orden( $evento ) );

		$destino = $this->submit( $uid, 'down', $evento, $a );
		$this->assertStringContainsString( 'panel=' . EventWorkspace::PANEL_SECTIONS, (string) $destino );
		$this->assertSame( array( $b, $a, $c ), $this->orden( $evento ) );

		$this->submit( $uid, 'up', $evento, $c );
		$this->assertSame( array( $b, $c, $a ), $this->orden( $evento ) );

		// En los extremos no se mueve nada, y no pasa nada.
		$this->submit( $uid, 'up', $evento, $b );
		$this->assertSame( array( $b, $c, $a ), $this->orden( $evento ) );
		$this->submit( $uid, 'down', $evento, $a );
		$this->assertSame( array( $b, $c, $a ), $this->orden( $evento ) );
	}

	/**
	 * Publicar, despublicar y borrar una sección.
	 */
	public function test_a_section_can_be_published_unpublished_and_trashed() {
		$area    = $this->area( 'Innovación' );
		$uid     = $this->administrator();
		$evento  = $this->event( $uid, array( $area ) );
		$seccion = $this->event_page( $evento, 'programa', array( 'post_status' => 'draft' ) );

		$this->submit( $uid, 'publish', $evento, $seccion );
		$this->assertSame( 'publish', get_post_status( $seccion ) );
		$this->assertStringContainsString( 'publicada', (string) $this->flash( $uid )['texto'] );

		$this->submit( $uid, 'unpublish', $evento, $seccion );
		$this->assertSame( 'draft', get_post_status( $seccion ) );

		// A la papelera, no borrada del todo: se recupera si el clic fue un error.
		$this->submit( $uid, 'delete', $evento, $seccion );
		$this->assertSame( 'trash', get_post_status( $seccion ) );
		$this->assertSame( array(), $this->orden( $evento ) );
	}

	/**
	 * La papelera: enviar no destruye, y restaurar devuelve la sección.
	 *
	 * Lo que se mira es el estado DESPUÉS, no el aviso: la entrada sigue
	 * existiendo con `post_status` `trash`, con su título y su contenido, y
	 * `wp_untrash_post()` la devuelve. Vuelve en borrador a propósito —es lo que
	 * hace WordPress desde la 5.6— para que una sección borrada por error no
	 * reaparezca publicada en el menú sin que nadie la mire.
	 */
	public function test_the_trash_does_not_destroy_and_restoring_brings_it_back() {
		$area    = $this->area( 'Innovación' );
		$uid     = $this->administrator();
		$evento  = $this->event( $uid, array( $area ) );
		$seccion = $this->event_page( $evento, 'programa', array( 'post_title' => 'Programa del jueves' ) );

		$this->submit( $uid, 'delete', $evento, $seccion );

		// Existe, con su título, y en la papelera.
		$post = get_post( $seccion );
		$this->assertInstanceOf( WP_Post::class, $post, 'la entrada sigue existiendo' );
		$this->assertSame( 'trash', $post->post_status );
		$this->assertSame( 'Programa del jueves', $post->post_title );

		// El listado normal no la enseña; la papelera, sí.
		$this->assertSame( array(), $this->orden( $evento ) );
		$this->assertSame(
			array( $seccion ),
			array_map( 'intval', wp_list_pluck( EventWorkspace::children( $evento, true ), 'ID' ) )
		);

		$this->submit( $uid, 'restore', $evento, $seccion );

		$this->assertSame( 'draft', get_post_status( $seccion ), 'vuelve en borrador, no publicada' );
		$this->assertSame( array( $seccion ), $this->orden( $evento ) );
		$this->assertSame( array(), EventWorkspace::children( $evento, true ) );
		$this->assertStringContainsString( 'restaurada', (string) $this->flash( $uid )['texto'] );
	}

	/**
	 * Sin permiso no se envía a la papelera NI se restaura.
	 */
	public function test_without_permission_nothing_is_trashed_or_restored() {
		$mia     = $this->area( 'Formación del Profesorado' );
		$otra    = $this->area( 'Innovación' );
		$dueno   = $this->organiser( array( $mia ) );
		$ajena   = $this->organiser( array( $otra ) );
		$evento  = $this->event( $dueno, array( $mia ) );
		$viva    = $this->event_page( $evento, 'programa' );
		$borrada = $this->event_page( $evento, 'ponentes' );

		$this->submit( $dueno, 'delete', $evento, $borrada );
		$this->assertSame( 'trash', get_post_status( $borrada ) );

		// Quien no es del área ni envía a la papelera ni saca de ella.
		$this->submit( $ajena, 'delete', $evento, $viva );
		$this->submit( $ajena, 'restore', $evento, $borrada );

		$this->assertSame( 'publish', get_post_status( $viva ), 'no la envió a la papelera' );
		$this->assertSame( 'trash', get_post_status( $borrada ), 'no la sacó de la papelera' );
	}

	/**
	 * Restaurar también lleva su nonce: sin él no vuelve nada.
	 */
	public function test_restoring_needs_its_own_nonce() {
		$area    = $this->area( 'Innovación' );
		$uid     = $this->administrator();
		$evento  = $this->event( $uid, array( $area ) );
		$seccion = $this->event_page( $evento, 'programa' );

		$this->submit( $uid, 'delete', $evento, $seccion );

		$this->assertNull( $this->submit( $uid, 'restore', $evento, $seccion, array(), 'basura' ) );
		$this->assertSame( 'trash', get_post_status( $seccion ) );

		$this->assertNull( $this->submit( $uid, 'restore', $evento, $seccion, array(), '' ) );
		$this->assertSame( 'trash', get_post_status( $seccion ) );
	}

	/**
	 * Enviar un EVENTO a la papelera NO manda a la papelera sus secciones.
	 *
	 * Comprobado contra WordPress 7.1, y en contra de lo que suele darse por
	 * hecho: `wp_trash_post()` solo le añade el sufijo `__trashed` al slug del
	 * propio evento y deja a las hijas exactamente como estaban. Una sección
	 * publicada de un evento en la papelera sigue publicada.
	 *
	 * Esto se documenta, no se arregla aquí: el aplicativo todavía no envía
	 * eventos a la papelera —solo secciones—, así que quien lo provoca es el
	 * escritorio de WordPress, y la cascada tendría que vivir con el registro
	 * del tipo de contenido y no en una pantalla.
	 */
	public function test_trashing_an_event_leaves_its_sections_alone() {
		$area    = $this->area( 'Innovación' );
		$uid     = $this->administrator();
		$evento  = $this->event( $uid, array( $area ) );
		$seccion = $this->event_page( $evento, 'programa' );

		wp_trash_post( $evento );

		$this->assertSame( 'trash', get_post_status( $evento ) );
		$this->assertStringEndsWith( '__trashed', (string) get_post_field( 'post_name', $evento ) );
		$this->assertSame( 'publish', get_post_status( $seccion ), 'la hija se queda como estaba' );

		wp_untrash_post( $evento );

		$this->assertSame( 'draft', get_post_status( $evento ), 'el evento vuelve en borrador' );
		$this->assertSame( 'publish', get_post_status( $seccion ) );
	}

	/**
	 * Sin permiso sobre el evento no se muta NADA: ni el orden, ni el estado.
	 */
	public function test_without_permission_nothing_is_mutated() {
		$mia     = $this->area( 'Formación del Profesorado' );
		$otra    = $this->area( 'Innovación' );
		$dueno   = $this->organiser( array( $mia ) );
		$ajena   = $this->organiser( array( $otra ) );
		$evento  = $this->event( $dueno, array( $mia ), array(), array( 'post_title' => 'Jornadas de mi área' ) );
		$primera = $this->event_page( $evento, 'programa', array( 'post_title' => 'A' ) );
		$segunda = $this->event_page( $evento, 'ponentes', array( 'post_title' => 'B' ) );

		foreach ( array( 'delete', 'unpublish', 'down' ) as $op ) {
			$this->submit( $ajena, $op, $evento, $primera );
		}
		$this->submit(
			$ajena,
			EventWorkspace::PANEL_SETTINGS,
			$evento,
			0,
			array(
				EventWorkspace::FIELD_TITLE => 'Secuestrado',
				EventMetaKeys::START_DATE   => '2026-03-10',
			)
		);
		$this->submit( $ajena, EventWorkspace::PANEL_LOOK, $evento, 0, array( EventMetaKeys::HEADER_BG => '#ff0000' ) );

		// El estado DESPUÉS: nada se movió, nada se despublicó, nada se borró.
		$this->assertSame( 'publish', get_post_status( $primera ) );
		$this->assertSame( array( $primera, $segunda ), $this->orden( $evento ) );
		$this->assertSame( 'Jornadas de mi área', get_the_title( $evento ) );
		$this->assertSame( '', (string) get_post_meta( $evento, EventMetaKeys::HEADER_BG, true ) );
		$this->assertStringContainsString( 'de otro ámbito', (string) $this->flash( $ajena )['texto'] );
	}

	/**
	 * Con el nonce mal, la operación ni siquiera se intenta.
	 */
	public function test_a_bad_nonce_mutates_nothing() {
		$area    = $this->area( 'Innovación' );
		$uid     = $this->administrator();
		$evento  = $this->event( $uid, array( $area ) );
		$seccion = $this->event_page( $evento, 'programa', array( 'post_status' => 'draft' ) );

		$this->assertNull( $this->submit( $uid, 'publish', $evento, $seccion, array(), 'basura' ) );
		$this->assertSame( 'draft', get_post_status( $seccion ) );

		// Sin nonce ninguno, lo mismo.
		$this->assertNull( $this->submit( $uid, 'delete', $evento, $seccion, array(), '' ) );
		$this->assertSame( 'draft', get_post_status( $seccion ) );

		// Y el nonce de otra acción no vale para esta.
		$this->acting_as( $uid );
		$this->post(
			array(
				EventWorkspace::FIELD_DO      => 'delete',
				EventWorkspace::FIELD_EVENT   => (string) $evento,
				EventWorkspace::FIELD_SECTION => (string) $seccion,
			),
			EventWorkspace::nonce_action( 'publish' ),
			EventWorkspace::nonce_name( 'delete', $seccion )
		);
		$this->assertNull( $this->exit_url( array( EventWorkspace::class, 'handle' ) ) );
		$this->assertSame( 'draft', get_post_status( $seccion ) );
	}

	/**
	 * Una operación que no es de las nuestras no se atiende.
	 */
	public function test_an_unknown_operation_is_ignored() {
		$area   = $this->area( 'Innovación' );
		$uid    = $this->administrator();
		$evento = $this->event( $uid, array( $area ) );

		$this->assertNull( $this->submit( $uid, 'formatear', $evento ) );
	}

	/**
	 * Una sección que no es de este evento no se toca.
	 */
	public function test_a_section_of_another_event_is_not_touched() {
		$area  = $this->area( 'Innovación' );
		$uid   = $this->administrator();
		$mio   = $this->event( $uid, array( $area ) );
		$otro  = $this->event( $uid, array( $area ) );
		$ajena = $this->event_page( $otro, 'programa' );

		$this->submit( $uid, 'delete', $mio, $ajena );

		$this->assertSame( 'publish', get_post_status( $ajena ) );
		$this->assertStringContainsString( 'no es de este evento', (string) $this->flash( $uid )['texto'] );
	}

	// ─── el panel de datos ─────────────────────────────────────────────────

	/**
	 * El panel «Datos» guarda el título, las metas y la clasificación.
	 */
	public function test_the_data_panel_saves_the_event() {
		$area   = $this->area( 'Innovación' );
		$uid    = $this->administrator();
		$evento = $this->event( $uid, array( $area ) );
		$tipo   = (int) self::factory()->term->create(
			array(
				'taxonomy' => EventTaxonomies::TYPE,
				'name'     => 'Jornadas',
			)
		);
		$curso  = (int) self::factory()->term->create(
			array(
				'taxonomy' => EventTaxonomies::COURSE,
				'name'     => '2025-2026',
			)
		);

		$destino = $this->submit(
			$uid,
			EventWorkspace::PANEL_SETTINGS,
			$evento,
			0,
			array(
				EventWorkspace::FIELD_TITLE   => 'Jornadas de Innovación 2026',
				EventWorkspace::FIELD_AREA    => (string) $area,
				EventWorkspace::FIELD_TYPE    => (string) $tipo,
				EventWorkspace::FIELD_COURSE  => (string) $curso,
				EventMetaKeys::TAGLINE        => 'Enseñar de otra manera',
				EventMetaKeys::HASHTAG        => '#EVT 2026',
				EventMetaKeys::INTRO          => '<p>Bienvenida.</p>',
				EventMetaKeys::START_DATE     => '2026-03-10',
				EventMetaKeys::END_DATE       => '2026-03-12',
				EventMetaKeys::VENUE          => 'Sede Central',
				EventMetaKeys::SIGNUP_SHOW    => '1',
				EventMetaKeys::SIGNUP_LABEL   => 'Inscríbase',
				EventMetaKeys::SIGNUP_URL     => 'https://example.org/inscripcion',
				EventMetaKeys::SIGNUP_FORM_ID => '10',
			)
		);

		$this->assertStringContainsString( 'panel=' . EventWorkspace::PANEL_SETTINGS, (string) $destino );
		$this->assertSame( 'Jornadas de Innovación 2026', get_the_title( $evento ) );
		$this->assertSame( 'Enseñar de otra manera', get_post_meta( $evento, EventMetaKeys::TAGLINE, true ) );
		$this->assertSame( 'EVT2026', get_post_meta( $evento, EventMetaKeys::HASHTAG, true ), 'ni almohadilla ni espacios' );
		$this->assertSame( '2026-03-10', get_post_meta( $evento, EventMetaKeys::START_DATE, true ) );
		$this->assertSame( '2026-03-12', get_post_meta( $evento, EventMetaKeys::END_DATE, true ) );
		$this->assertSame( 'Sede Central', get_post_meta( $evento, EventMetaKeys::VENUE, true ) );
		$this->assertSame( '10', (string) get_post_meta( $evento, EventMetaKeys::SIGNUP_FORM_ID, true ) );
		$this->assertNotEmpty( get_post_meta( $evento, EventMetaKeys::SIGNUP_SHOW, true ) );

		$this->assertSame( array( $tipo ), wp_get_post_terms( $evento, EventTaxonomies::TYPE, array( 'fields' => 'ids' ) ) );
		$this->assertSame( array( $curso ), wp_get_post_terms( $evento, EventTaxonomies::COURSE, array( 'fields' => 'ids' ) ) );
		$this->assertSame( 'ok', (string) $this->flash( $uid )['tipo'] );
	}

	/**
	 * Un dato mal no borra los otros trece: se repinta lo tecleado con el aviso.
	 */
	public function test_a_rejected_data_panel_repaints_what_was_typed() {
		$area   = $this->area( 'Innovación' );
		$uid    = $this->administrator();
		$evento = $this->event( $uid, array( $area ), array(), array( 'post_title' => 'Jornadas de prueba' ) );

		$this->submit(
			$uid,
			EventWorkspace::PANEL_SETTINGS,
			$evento,
			0,
			array(
				EventWorkspace::FIELD_TITLE => 'Nombre nuevo',
				EventMetaKeys::START_DATE   => '2026-03-10',
				EventMetaKeys::END_DATE     => '2026-03-01',
				EventMetaKeys::TAGLINE      => 'Un lema tecleado',
			)
		);

		$this->assertSame( 'Jornadas de prueba', get_the_title( $evento ), 'no se guarda nada a medias' );
		$this->assertSame( '', (string) get_post_meta( $evento, EventMetaKeys::TAGLINE, true ) );

		$_GET[ EventWorkspace::ARG_EVENT ] = (string) $evento;
		$m                                 = EventWorkspace::model();

		$this->assertSame( 'error', $m['flash']['tipo'] );
		$this->assertStringContainsString( 'fecha de fin', $m['flash']['texto'] );
		$this->assertSame( 'Nombre nuevo', $m['values'][ EventWorkspace::FIELD_TITLE ] );
		$this->assertSame( 'Un lema tecleado', $m['values'][ EventMetaKeys::TAGLINE ] );
	}

	/**
	 * Quien no coordina no puede regalarle el evento a otra área.
	 */
	public function test_the_area_cannot_be_given_away() {
		$mia    = $this->area( 'Formación del Profesorado' );
		$otra   = $this->area( 'Innovación' );
		$uid    = $this->organiser( array( $mia ) );
		$evento = $this->event( $uid, array( $mia ) );

		$this->assertTrue( EventWorkspace::may_set_area( $uid, $mia ) );
		$this->assertFalse( EventWorkspace::may_set_area( $uid, $otra ) );
		$this->assertTrue( EventWorkspace::may_set_area( $this->administrator(), $otra ) );

		$this->submit(
			$uid,
			EventWorkspace::PANEL_SETTINGS,
			$evento,
			0,
			array(
				EventWorkspace::FIELD_TITLE => 'Jornadas de prueba',
				EventWorkspace::FIELD_AREA  => (string) $otra,
				EventMetaKeys::START_DATE   => '2026-03-10',
			)
		);

		$this->assertSame(
			array( $mia ),
			wp_get_post_terms( $evento, EventTaxonomies::AREA, array( 'fields' => 'ids' ) )
		);
	}

	/** An editor can update a shared event without removing another branch. */
	public function test_shared_event_keeps_all_organising_scopes() {
		$mine    = $this->area( 'Ámbito 1' );
		$foreign = $this->area( 'Ámbito 2' );
		$editor  = (int) self::factory()->user->create( array( 'role' => 'editor' ) );
		update_user_meta( $editor, EventAccess::USER_AREA_META, array( $mine ) );
		$event = $this->event( $editor, array( $mine, $foreign ) );
		$this->submit(
			$editor,
			EventWorkspace::PANEL_SETTINGS,
			$event,
			0,
			array(
				EventWorkspace::FIELD_TITLE => 'Evento compartido',
				EventWorkspace::FIELD_AREA  => array( $mine ),
				EventMetaKeys::START_DATE   => '2026-10-01',
			)
		);
		$this->assertEqualsCanonicalizing( array( $mine, $foreign ), EventAccess::post_areas( $event ) );
		$this->acting_as( $editor );
		$_GET[ EventWorkspace::ARG_EVENT ] = (string) $event;
		$model                             = EventWorkspace::model();
		$this->assertEqualsCanonicalizing( array( $mine, $foreign ), array_map( 'intval', explode( ',', $model['values'][ EventWorkspace::FIELD_AREA ] ) ) );
	}

	// ─── el panel de apariencia ────────────────────────────────────────────

	/**
	 * El panel «Apariencia» guarda los colores y las tipografías de la lista cerrada.
	 */
	public function test_the_appearance_panel_saves_the_look() {
		$area   = $this->area( 'Innovación' );
		$uid    = $this->administrator();
		$evento = $this->event( $uid, array( $area ) );

		$destino = $this->submit(
			$uid,
			EventWorkspace::PANEL_LOOK,
			$evento,
			0,
			array(
				EventMetaKeys::HEADER_BG   => '#0a3d62',
				EventMetaKeys::HEADER_TEXT => '#ffffff',
				EventMetaKeys::TITLE_FONT  => 'lato',
				EventMetaKeys::BODY_FONT   => 'comic-sans',
				EventMetaKeys::IMAGE_SHAPE => EventMetaKeys::SHAPE_CIRCLE,
				EventMetaKeys::SEPARATOR   => 'wave',
			)
		);

		$this->assertStringContainsString( 'panel=' . EventWorkspace::PANEL_LOOK, (string) $destino );
		$this->assertSame( '#0a3d62', get_post_meta( $evento, EventMetaKeys::HEADER_BG, true ) );
		$this->assertSame( '#ffffff', get_post_meta( $evento, EventMetaKeys::HEADER_TEXT, true ) );
		$this->assertSame( 'lato', get_post_meta( $evento, EventMetaKeys::TITLE_FONT, true ) );
		$this->assertSame( '', get_post_meta( $evento, EventMetaKeys::BODY_FONT, true ), 'una tipografía de fuera de la lista no se guarda' );
		$this->assertSame( EventMetaKeys::SHAPE_CIRCLE, get_post_meta( $evento, EventMetaKeys::IMAGE_SHAPE, true ) );
		$this->assertSame( 'wave', get_post_meta( $evento, EventMetaKeys::SEPARATOR, true ) );
	}

	/**
	 * Un color que no es un color no se guarda.
	 */
	public function test_an_invalid_colour_is_not_stored() {
		$area   = $this->area( 'Innovación' );
		$uid    = $this->administrator();
		$evento = $this->event( $uid, array( $area ) );

		$this->submit(
			$uid,
			EventWorkspace::PANEL_LOOK,
			$evento,
			0,
			array(
				EventMetaKeys::HEADER_BG   => 'rojo bombero',
				EventMetaKeys::HEADER_TEXT => '#12345',
			)
		);

		$this->assertSame( '', get_post_meta( $evento, EventMetaKeys::HEADER_BG, true ) );
		$this->assertSame( '', get_post_meta( $evento, EventMetaKeys::HEADER_TEXT, true ) );
	}

	/**
	 * Un adjunto de la biblioteca, para elegirlo en el panel de apariencia.
	 *
	 * @param string $mime    MIME type.
	 * @param string $fichero File name.
	 * @param int    $autor   Who uploaded it.
	 * @return int Attachment ID.
	 */
	private function adjunto( string $mime = 'image/jpeg', string $fichero = 'cartel.jpg', int $autor = 0 ): int {
		return (int) wp_insert_attachment(
			array(
				'post_title'     => $fichero,
				'post_mime_type' => $mime,
				'post_status'    => 'inherit',
				'post_author'    => $autor,
			),
			'2026/03/' . $fichero
		);
	}

	/**
	 * El banner exige 1920 píxeles y un intento inválido conserva el anterior.
	 */
	public function test_the_header_banner_requires_a_minimum_width_of_1920_pixels() {
		$area     = $this->area( 'Innovación' );
		$uid      = $this->administrator();
		$evento   = $this->event( $uid, array( $area ) );
		$anterior = $this->adjunto( 'image/jpeg', 'banner-anterior.jpg', $uid );
		$estrecho = $this->adjunto( 'image/jpeg', 'banner-estrecho.jpg', $uid );
		$ancho    = $this->adjunto( 'image/jpeg', 'banner-ancho.jpg', $uid );

		wp_update_attachment_metadata(
			$anterior,
			array(
				'width'  => 1920,
				'height' => 600,
				'file'   => '2026/03/banner-anterior.jpg',
			)
		);
		wp_update_attachment_metadata(
			$estrecho,
			array(
				'width'  => 1919,
				'height' => 600,
				'file'   => '2026/03/banner-estrecho.jpg',
			)
		);
		wp_update_attachment_metadata(
			$ancho,
			array(
				'width'  => 2400,
				'height' => 750,
				'file'   => '2026/03/banner-ancho.jpg',
			)
		);
		update_post_meta( $evento, EventMetaKeys::HEADER_BANNER_ID, $anterior );

		$this->submit( $uid, EventWorkspace::PANEL_LOOK, $evento, 0, array( EventMetaKeys::HEADER_BANNER_ID => (string) $estrecho ) );
		$this->assertSame( $anterior, (int) get_post_meta( $evento, EventMetaKeys::HEADER_BANNER_ID, true ) );
		$this->assertSame( 'aviso', $this->flash( $uid )['tipo'] );

		$this->submit( $uid, EventWorkspace::PANEL_LOOK, $evento, 0, array( EventMetaKeys::HEADER_BANNER_ID => (string) $ancho ) );
		$this->assertSame( $ancho, (int) get_post_meta( $evento, EventMetaKeys::HEADER_BANNER_ID, true ) );
	}

	/**
	 * El selector de medios manda un identificador de adjunto, y se comprueba.
	 *
	 * El `hidden` que rellena el guion lo escribe el navegador, así que lo
	 * escribe cualquiera: si el número no es un adjunto de imagen, no se
	 * guarda Y no se pierde lo que ya había —perder el cartel del evento por
	 * un número tecleado a mano sería peor que no cambiarlo—.
	 */
	public function test_a_picked_id_must_really_be_an_image_attachment() {
		$area   = $this->area( 'Innovación' );
		$uid    = $this->administrator();
		$evento = $this->event( $uid, array( $area ) );
		$bueno  = $this->adjunto();

		$this->submit( $uid, EventWorkspace::PANEL_LOOK, $evento, 0, array( EventMetaKeys::LOGO_ID => (string) $bueno ) );
		$this->assertSame( $bueno, (int) get_post_meta( $evento, EventMetaKeys::LOGO_ID, true ), 'una imagen de la biblioteca sí se guarda' );

		$noes = array(
			'un identificador que no existe'  => 987654,
			'un post que no es un adjunto'    => $evento,
			'un adjunto que no es una imagen' => $this->adjunto( 'application/pdf', 'bases.pdf' ),
		);
		foreach ( $noes as $porque => $malo ) {
			$this->submit( $uid, EventWorkspace::PANEL_LOOK, $evento, 0, array( EventMetaKeys::LOGO_ID => (string) $malo ) );
			$this->assertSame( $bueno, (int) get_post_meta( $evento, EventMetaKeys::LOGO_ID, true ), $porque );
			$this->assertSame( 'aviso', $this->flash( $uid )['tipo'], $porque . ': y se avisa' );
		}

		// Un envío que ni siquiera trae el campo tampoco borra lo que hay: la
		// ausencia no es una orden de quitar la imagen.
		$this->submit( $uid, EventWorkspace::PANEL_LOOK, $evento, 0, array( EventMetaKeys::HEADER_BG => '#0a3d62' ) );
		$this->assertSame( $bueno, (int) get_post_meta( $evento, EventMetaKeys::LOGO_ID, true ) );

		// Y reutilizar lo que subió otra persona es justo lo que se pedía: la
		// comprobación mira que sea una imagen, no quién la subió.
		$ajeno = $this->adjunto( 'image/png', 'logo-de-otra-persona.png', $this->organiser( array( $area ) ) );
		$this->submit( $uid, EventWorkspace::PANEL_LOOK, $evento, 0, array( EventMetaKeys::LOGO_ID => (string) $ajeno ) );
		$this->assertSame( $ajeno, (int) get_post_meta( $evento, EventMetaKeys::LOGO_ID, true ) );
	}

	/**
	 * El mismo campo oculto es el que quita la imagen, con un cero.
	 */
	public function test_the_hidden_field_puts_and_clears_the_three_images() {
		$area   = $this->area( 'Innovación' );
		$uid    = $this->administrator();
		$evento = $this->event( $uid, array( $area ) );
		$imagen = $this->adjunto();

		$this->submit(
			$uid,
			EventWorkspace::PANEL_LOOK,
			$evento,
			0,
			array(
				EventMetaKeys::LOGO_ID   => (string) $imagen,
				EventMetaKeys::POSTER_ID => (string) $imagen,
				'evt_featured_id'        => (string) $imagen,
			)
		);

		$this->assertSame( $imagen, (int) get_post_meta( $evento, EventMetaKeys::LOGO_ID, true ) );
		$this->assertSame( $imagen, (int) get_post_meta( $evento, EventMetaKeys::POSTER_ID, true ) );
		$this->assertSame( $imagen, (int) get_post_thumbnail_id( $evento ), 'la destacada es el thumbnail de WordPress, no una meta nuestra' );
		$this->assertSame( 'ok', $this->flash( $uid )['tipo'] );

		$this->submit(
			$uid,
			EventWorkspace::PANEL_LOOK,
			$evento,
			0,
			array(
				EventMetaKeys::LOGO_ID   => '0',
				EventMetaKeys::POSTER_ID => '0',
				'evt_featured_id'        => '0',
			)
		);

		// Cero y no cadena vacía: las dos son metas declaradas como enteras
		// con defecto 0, así que 'vacía' se lee como 0 (`register_post_meta()`).
		$this->assertSame( 0, (int) get_post_meta( $evento, EventMetaKeys::LOGO_ID, true ) );
		$this->assertSame( 0, (int) get_post_meta( $evento, EventMetaKeys::POSTER_ID, true ) );
		$this->assertSame( 0, (int) get_post_thumbnail_id( $evento ) );
	}

	/**
	 * Quien no puede editar el evento no le cambia la imagen, ni con el ID bueno.
	 */
	public function test_someone_from_another_area_does_not_change_the_images() {
		$mia    = $this->area( 'Formación del Profesorado' );
		$otra   = $this->area( 'Innovación' );
		$dueno  = $this->organiser( array( $mia ) );
		$ajena  = $this->organiser( array( $otra ) );
		$evento = $this->event( $dueno, array( $mia ) );
		$imagen = $this->adjunto();

		$this->submit( $ajena, EventWorkspace::PANEL_LOOK, $evento, 0, array( EventMetaKeys::LOGO_ID => (string) $imagen ) );

		$this->assertSame( 0, (int) get_post_meta( $evento, EventMetaKeys::LOGO_ID, true ) );
		$this->assertSame( 'error', $this->flash( $ajena )['tipo'] );
	}

	// ─── direcciones y arranque ────────────────────────────────────────────

	/**
	 * Las direcciones del taller y de sus pestañas.
	 */
	public function test_the_urls_of_the_workshop() {
		$this->acting_as( $this->administrator() );

		$this->assertSame( '', EventWorkspace::url( 0 ) );

		$url = EventWorkspace::url( 12, EventWorkspace::PANEL_SETTINGS );
		$this->assertStringContainsString( 'evento=12', $url );
		$this->assertStringContainsString( 'panel=' . EventWorkspace::PANEL_SETTINGS, $url );

		$paneles = EventWorkspace::panels( 12 );
		$this->assertSame( 'Páginas', $paneles[ EventWorkspace::PANEL_SECTIONS ]['label'] );
		$this->assertStringContainsString( 'panel=' . EventWorkspace::PANEL_LOOK, $paneles[ EventWorkspace::PANEL_LOOK ]['url'] );

		// Un nonce por acción y, en las de fila, por fila.
		$this->assertNotSame(
			EventWorkspace::nonce_name( 'publish', 3 ),
			EventWorkspace::nonce_name( 'publish', 4 )
		);
		$this->assertNotSame( EventWorkspace::nonce_action( 'publish' ), EventWorkspace::nonce_action( 'delete' ) );
	}

	/**
	 * Sin evento en la dirección, el taller es la pantalla de crear uno.
	 *
	 * Es a donde lleva «Crear evento» del listado: antes se quedaba en «elija
	 * un evento en la lista», que es la pescadilla mordiéndose la cola.
	 */
	public function test_without_an_event_the_workshop_is_the_create_screen() {
		$area = $this->area( 'Una' );
		$uid  = $this->organiser( array( $area ) );
		$this->acting_as( $uid );
		$_GET = array();

		$m = EventWorkspace::model();

		$this->assertSame( '', $m['aviso'], 'no se queda en un aviso' );
		$this->assertTrue( $m['nuevo'] );
		$this->assertSame( EventWorkspace::PANEL_SETTINGS, $m['panel'] );
		$this->assertSame( array(), $m['panels'], 'ni una pestaña de un evento que no existe' );
	}

	/**
	 * Quien no puede crear eventos lee por qué, y no un formulario.
	 */
	public function test_without_the_capability_there_is_no_create_screen() {
		$uid = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->acting_as( $uid );
		$_GET = array();

		$m = EventWorkspace::model();

		$this->assertStringContainsString( 'no puede crear eventos', $m['aviso'] );
		$this->assertFalse( $m['nuevo'] );
	}

	/**
	 * Crear un evento: nace en borrador y abre su taller.
	 */
	public function test_the_form_creates_the_event_as_a_draft() {
		$area = $this->area( 'Una' );
		$uid  = $this->organiser( array( $area ) );
		$this->acting_as( $uid );

		$op = EventWorkspace::PANEL_SETTINGS;
		$this->post(
			array(
				EventWorkspace::FIELD_DO    => $op,
				EventWorkspace::FIELD_EVENT => '0',
				EventWorkspace::FIELD_TITLE => 'IV Jornadas',
				EventMetaKeys::START_DATE   => '2026-11-10',
				EventMetaKeys::END_DATE     => '2026-11-12',
				EventMetaKeys::VENUE        => 'Centro de formación Norte',
			),
			EventWorkspace::nonce_action( $op ),
			EventWorkspace::nonce_name( $op )
		);

		$url = $this->exit_url( array( EventWorkspace::class, 'handle' ) );

		// Un borrador **no recibe `post_name`** hasta que se publica, así que se
		// busca por título y no por slug.
		$creados = get_posts(
			array(
				'post_type'   => EventPostType::POST_TYPE,
				'title'       => 'IV Jornadas',
				'post_status' => 'any',
				'numberposts' => -1,
			)
		);
		$this->assertCount( 1, $creados );
		$creado = $creados[0];
		$this->assertSame( 'draft', $creado->post_status, 'en borrador: todavía no tiene nada dentro' );
		$this->assertSame( '2026-11-10', get_post_meta( $creado->ID, EventMetaKeys::START_DATE, true ) );
		$this->assertSame( array( $area ), EventAccess::post_areas( $creado->ID ), 'con el área de quien lo crea' );
		$this->assertSame( (string) $creado->ID, $this->query_arg( (string) $url, EventWorkspace::ARG_EVENT ), 'y abre su taller' );
	}

	/**
	 * Una fecha mal escrita devuelve el formulario con lo tecleado.
	 */
	public function test_a_bad_date_comes_back_with_what_was_typed() {
		$area = $this->area( 'Una' );
		$uid  = $this->organiser( array( $area ) );
		$this->acting_as( $uid );

		$op = EventWorkspace::PANEL_SETTINGS;
		$this->post(
			array(
				EventWorkspace::FIELD_DO    => $op,
				EventWorkspace::FIELD_EVENT => '0',
				EventWorkspace::FIELD_TITLE => 'Sin fecha buena',
				EventMetaKeys::START_DATE   => '2026-02-30',
			),
			EventWorkspace::nonce_action( $op ),
			EventWorkspace::nonce_name( $op )
		);

		$this->exit_url( array( EventWorkspace::class, 'handle' ) );

		$this->assertSame(
			array(),
			get_posts(
				array(
					'post_type'   => EventPostType::POST_TYPE,
					'title'       => 'Sin fecha buena',
					'post_status' => 'any',
					'numberposts' => -1,
				)
			),
			'no se crea a medias'
		);

		$_GET = array();
		$m    = EventWorkspace::model();
		$this->assertTrue( $m['nuevo'] );
		$this->assertSame( 'error', $m['flash']['tipo'] );
		$this->assertSame( 'Sin fecha buena', $m['values'][ EventWorkspace::FIELD_TITLE ], 'lo tecleado vuelve' );
	}

	/**
	 * El arranque engancha el shortcode y el que atiende los envíos.
	 */
	public function test_register_hooks_the_shortcode_and_the_handler() {
		EventWorkspace::register();

		$this->assertTrue( shortcode_exists( EventWorkspace::SHORTCODE ) );
		$this->assertSame( 20, has_action( 'init', array( EventWorkspace::class, 'handle' ) ) );
	}
}
