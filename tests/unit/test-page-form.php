<?php
/**
 * Tests for the section form: creating, editing, validating and repainting.
 *
 * @package Evt
 */

use Evt\Meta\EventMetaKeys;
use Evt\PostType\EventPostType;
use Evt\PublicFront\PageForm;

/**
 * El alta y la edición de una página satélite.
 *
 * Hoy esto es un único formulario enorme, el mismo para la portada del evento
 * y para cada sección. Aquí son cinco campos y un plegado, y el
 * guardián es siempre el evento padre: sin permiso sobre él no se crea ni se
 * toca nada, aunque el identificador venga escrito a mano en el envío.
 */
class Test_Page_Form extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Con las páginas del aplicativo, y sin el rechazo del test anterior.
	 */
	public function set_up() {
		parent::set_up();
		$this->pages();
		$this->forget_rejection();
	}

	/**
	 * Olvidar por qué se rechazó el último envío.
	 *
	 * El motivo vive en una propiedad estática porque no tiene que sobrevivir a
	 * ninguna redirección: en producción cada petición empieza limpia, y aquí
	 * las peticiones son el mismo proceso.
	 *
	 * @return void
	 */
	private function forget_rejection(): void {
		$prop = new ReflectionProperty( PageForm::class, 'rejected' );
		$prop->setAccessible( true );
		$prop->setValue(
			null,
			array(
				'message' => '',
				'errors'  => array(),
			)
		);
	}

	/**
	 * Mandar el formulario y devolver por dónde salió.
	 *
	 * @param int                   $uid    Who submits.
	 * @param array<string, string> $campos Fields.
	 * @param string|null           $nonce  Null for the good one, a string to forge it.
	 * @return string|null
	 */
	private function submit( int $uid, array $campos, ?string $nonce = null ) {
		$this->forget_rejection();
		$this->acting_as( $uid );
		$campos = array_merge( array( 'evt_page_form' => '1' ), $campos );
		if ( null === $nonce ) {
			$this->post( $campos, PageForm::NONCE_ACTION, PageForm::NONCE_FIELD );
		} else {
			$campos[ PageForm::NONCE_FIELD ] = $nonce;
			$this->post( $campos );
		}
		return $this->exit_url( array( PageForm::class, 'maybe_handle_submit' ) );
	}

	/**
	 * Las páginas satélite de un evento, en cualquier estado.
	 *
	 * @param int $evento Event.
	 * @return int[]
	 */
	private function secciones( int $evento ): array {
		return array_map(
			'intval',
			(array) get_posts(
				array(
					'post_type'   => EventPostType::POST_TYPE,
					'post_parent' => $evento,
					'post_status' => 'any',
					'fields'      => 'ids',
					'numberposts' => 50,
				)
			)
		);
	}

	// ─── el modelo ─────────────────────────────────────────────────────────

	/**
	 * Sin sesión, sin evento padre o sobre un evento ajeno no hay formulario.
	 */
	public function test_the_form_does_not_open_without_a_parent_event_and_permission() {
		$mia   = $this->area( 'Formación del Profesorado' );
		$otra  = $this->area( 'Innovación' );
		$ajeno = $this->event( $this->administrator(), array( $otra ) );
		$yo    = $this->organiser( array( $mia ) );
		$mio   = $this->event( $yo, array( $mia ) );

		$this->acting_as( 0 );
		$this->assertStringContainsString( 'Debe iniciar sesión', PageForm::model()['aviso'] );

		$this->acting_as( $yo );
		$this->assertStringContainsString( 'desde el evento al que pertenece', PageForm::model()['aviso'] );

		$_GET['evento'] = (string) $ajeno;
		$this->assertStringContainsString( 'de otro ámbito', PageForm::model()['aviso'] );

		// La portada del evento no se edita aquí: se edita en su taller.
		$_GET['evento']  = '';
		$_GET['seccion'] = (string) $mio;
		$this->assertStringContainsString( 'la portada del evento', PageForm::model()['aviso'] );
	}

	/**
	 * Con el evento padre, el formulario se abre vacío y sabe de quién cuelga.
	 */
	public function test_a_new_section_opens_empty_under_its_event() {
		$area   = $this->area( 'Innovación' );
		$yo     = $this->organiser( array( $area ) );
		$evento = $this->event( $yo, array( $area ), array(), array( 'post_title' => 'Jornadas de Innovación' ) );

		$this->acting_as( $yo );
		$_GET['evento'] = (string) $evento;
		$m              = PageForm::model();

		$this->assertSame( '', $m['aviso'] );
		$this->assertSame( $evento, $m['event_id'] );
		$this->assertSame( 'Jornadas de Innovación', $m['event'] );
		$this->assertSame( 0, $m['page_id'] );
		$this->assertSame( '', $m['values']['title'] );
		$this->assertSame( 'draft', $m['status'] );
		$this->assertArrayHasKey( 'programa', $m['types'] );
		$this->assertStringContainsString( 'panel=secciones', $m['event_url'] );
	}

	// ─── alta ──────────────────────────────────────────────────────────────

	/**
	 * El alta crea la sección en borrador, con su tipo, su dirección y su orden.
	 */
	public function test_creating_a_section() {
		$area   = $this->area( 'Innovación' );
		$yo     = $this->organiser( array( $area ) );
		$evento = $this->event( $yo, array( $area ) );

		$destino = $this->submit(
			$yo,
			array(
				'evt_page_event'            => (string) $evento,
				'evt_title'                 => 'Programa de la jornada',
				'evt_slug'                  => 'Programa de la Jornada',
				EventMetaKeys::SECTION_TYPE => 'programa',
				'evt_order'                 => '30',
				'evt_content'               => '<p>Lo que pasa cada día.</p><script>alert(1)</script>',
				EventMetaKeys::HEADER_BG    => '#0a3d62',
				EventMetaKeys::TITLE_FONT   => 'lato',
			)
		);

		$ids = $this->secciones( $evento );
		$this->assertCount( 1, $ids );
		$nueva = $ids[0];

		$this->assertStringContainsString( 'seccion=' . $nueva, (string) $destino );
		$this->assertSame( 'creada', $this->query_arg( (string) $destino, 'evt_hecho' ) );

		$this->assertSame( 'Programa de la jornada', get_the_title( $nueva ) );
		$this->assertSame( 'programa-de-la-jornada', get_post_field( 'post_name', $nueva ) );
		$this->assertSame( 'draft', get_post_status( $nueva ), 'nace en borrador: publicar es del panel de secciones' );
		$this->assertSame( $evento, (int) get_post_field( 'post_parent', $nueva ) );
		$this->assertSame( 30, (int) get_post_field( 'menu_order', $nueva ) );
		$this->assertSame( 'programa', get_post_meta( $nueva, EventMetaKeys::SECTION_TYPE, true ) );
		$this->assertSame( $yo, (int) get_post_field( 'post_author', $nueva ) );

		// Nadie escribe HTML sin filtrar, tampoco quien organiza.
		$contenido = (string) get_post_field( 'post_content', $nueva );
		$this->assertStringContainsString( 'Lo que pasa cada día', $contenido );
		$this->assertStringNotContainsString( '<script', $contenido );

		// La apariencia propia de la sección, solo la que se eligió.
		$this->assertSame( '#0a3d62', get_post_meta( $nueva, EventMetaKeys::HEADER_BG, true ) );
		$this->assertSame( 'lato', get_post_meta( $nueva, EventMetaKeys::TITLE_FONT, true ) );
		$this->assertFalse(
			metadata_exists( 'post', $nueva, EventMetaKeys::SEPARATOR ),
			'lo que se deja en blanco se hereda del evento, y eso se guarda no guardando'
		);
	}

	/**
	 * Sin dirección tecleada, la del título: una sección sin dirección no se enlaza.
	 */
	public function test_the_slug_falls_back_to_the_title() {
		$area   = $this->area( 'Innovación' );
		$yo     = $this->organiser( array( $area ) );
		$evento = $this->event( $yo, array( $area ) );

		$this->submit(
			$yo,
			array(
				'evt_page_event'            => (string) $evento,
				'evt_title'                 => 'Ponentes y comunicaciones',
				EventMetaKeys::SECTION_TYPE => 'ponentes',
			)
		);

		$ids = $this->secciones( $evento );
		$this->assertSame( 'ponentes-y-comunicaciones', get_post_field( 'post_name', $ids[0] ) );
	}

	// ─── edición ───────────────────────────────────────────────────────────

	/**
	 * La edición cambia lo que se cambia, y ni el tipo ni el estado están entre
	 * eso: el tipo se fija al crear (ADR-0019) y publicar es del panel del evento.
	 */
	public function test_editing_a_section() {
		$area    = $this->area( 'Innovación' );
		$yo      = $this->organiser( array( $area ) );
		$evento  = $this->event( $yo, array( $area ) );
		$seccion = $this->event_page( $evento, 'programa', array( 'post_title' => 'Programa' ) );
		update_post_meta( $seccion, EventMetaKeys::HEADER_BG, '#000000' );

		$destino = $this->submit(
			$yo,
			array(
				'evt_page_id'               => (string) $seccion,
				'evt_title'                 => 'Programa definitivo',
				'evt_slug'                  => 'programa-definitivo',
				EventMetaKeys::SECTION_TYPE => 'actividades',
				'evt_order'                 => '20',
				'evt_content'               => 'Contenido nuevo.',
				EventMetaKeys::HEADER_BG    => '',
			)
		);

		$this->assertSame( 'guardada', $this->query_arg( (string) $destino, 'evt_hecho' ) );
		$this->assertSame( array( $seccion ), $this->secciones( $evento ), 'se edita, no se duplica' );
		$this->assertSame( 'Programa definitivo', get_the_title( $seccion ) );
		$this->assertSame( 'programa-definitivo', get_post_field( 'post_name', $seccion ) );
		$this->assertSame(
			'programa',
			get_post_meta( $seccion, EventMetaKeys::SECTION_TYPE, true ),
			'el tipo se eligió al crear y no se cambia, ni mandándolo a mano en el envío'
		);
		$this->assertSame( 20, (int) get_post_field( 'menu_order', $seccion ) );
		$this->assertSame( 'publish', get_post_status( $seccion ), 'el estado no se toca desde aquí' );
		$this->assertFalse(
			metadata_exists( 'post', $seccion, EventMetaKeys::HEADER_BG ),
			'dejarlo en blanco vuelve a heredar del evento'
		);
	}

	/**
	 * El tipo no se cambia al editar, ni siquiera mandándolo a mano.
	 *
	 * Es la regla de la ADR-0019 y su razón es de dominio: cada tipo trae sus
	 * elementos, así que cambiarlo dejaría la sección con los de otro. El
	 * formulario ya no lo ofrece al editar; esto comprueba lo que de verdad
	 * protege, que es el servidor.
	 */
	public function test_the_section_type_cannot_be_changed_by_editing() {
		$area    = $this->area( 'Innovación' );
		$yo      = $this->organiser( array( $area ) );
		$evento  = $this->event( $yo, array( $area ) );
		$seccion = $this->event_page( $evento, 'contacto', array( 'post_title' => 'Contacto' ) );

		foreach ( array( 'programa', 'ponentes', 'otra', 'inventado' ) as $intento ) {
			$this->submit(
				$yo,
				array(
					'evt_page_id'               => (string) $seccion,
					'evt_title'                 => 'Contacto',
					EventMetaKeys::SECTION_TYPE => $intento,
				)
			);
			$this->assertSame(
				'contacto',
				get_post_meta( $seccion, EventMetaKeys::SECTION_TYPE, true ),
				"mandar «{$intento}» en el envío no puede cambiar el tipo"
			);
		}
	}

	/**
	 * El formulario de una sección guardada llega con lo que hay dentro.
	 */
	public function test_the_form_of_a_saved_section_is_prefilled() {
		$area    = $this->area( 'Innovación' );
		$yo      = $this->organiser( array( $area ) );
		$evento  = $this->event( $yo, array( $area ) );
		$seccion = $this->event_page(
			$evento,
			'contacto',
			array(
				'post_title'   => 'Contacto',
				'post_name'    => 'contacto',
				'post_content' => 'Escríbanos.',
				'menu_order'   => 40,
			)
		);
		update_post_meta( $seccion, EventMetaKeys::BODY_FONT, 'merriweather' );

		$this->acting_as( $yo );
		$_GET['seccion']   = (string) $seccion;
		$_GET['evt_hecho'] = 'guardada';
		$m                 = PageForm::model();

		$this->assertSame( $seccion, $m['page_id'] );
		$this->assertSame( $evento, $m['event_id'], 'el evento sale del padre, no de la petición' );
		$this->assertSame( 'Contacto', $m['values']['title'] );
		$this->assertSame( 'contacto', $m['values']['slug'] );
		$this->assertSame( 'contacto', $m['values']['section_type'] );
		$this->assertSame( 40, $m['values']['menu_order'] );
		$this->assertSame( 'Escríbanos.', $m['values']['content'] );
		$this->assertSame( 'merriweather', $m['values']['look'][ EventMetaKeys::BODY_FONT ] );
		$this->assertSame( 'Cambios guardados.', $m['hecho'] );
	}

	// ─── validación ────────────────────────────────────────────────────────

	/**
	 * Un envío incompleto no crea nada y devuelve lo tecleado con el motivo.
	 */
	public function test_an_incomplete_submit_creates_nothing_and_repaints() {
		$area   = $this->area( 'Innovación' );
		$yo     = $this->organiser( array( $area ) );
		$evento = $this->event( $yo, array( $area ) );

		$salida = $this->submit(
			$yo,
			array(
				'evt_page_event'            => (string) $evento,
				'evt_title'                 => '',
				EventMetaKeys::SECTION_TYPE => 'programa',
				'evt_content'               => 'Un contenido ya escrito.',
			)
		);

		$this->assertNull( $salida, 'un envío rechazado no redirige: repinta' );
		$this->assertSame( array(), $this->secciones( $evento ) );

		$_GET['evento'] = (string) $evento;
		$m              = PageForm::model();

		$this->assertStringContainsString( 'título de la sección', $m['error'] );
		$this->assertContains( 'title', $m['errors'] );
		$this->assertSame( 'Un contenido ya escrito.', $m['values']['content'], 'no se pierde lo ya escrito' );
		$this->assertSame( 'programa', $m['values']['section_type'] );
	}

	/**
	 * Un tipo de sección que no es de la lista cerrada tampoco pasa.
	 */
	public function test_the_section_type_has_to_be_one_of_the_list() {
		$area   = $this->area( 'Innovación' );
		$yo     = $this->organiser( array( $area ) );
		$evento = $this->event( $yo, array( $area ) );

		$this->submit(
			$yo,
			array(
				'evt_page_event'            => (string) $evento,
				'evt_title'                 => 'Una sección',
				EventMetaKeys::SECTION_TYPE => 'lo-que-sea',
			)
		);

		$this->assertSame( array(), $this->secciones( $evento ) );

		$_GET['evento'] = (string) $evento;
		$this->assertStringContainsString( 'tipo de sección', PageForm::model()['error'] );
	}

	// ─── permisos ──────────────────────────────────────────────────────────

	/**
	 * Sin permiso sobre el evento padre no se crea nada, aunque el identificador
	 * venga escrito a mano en el envío.
	 */
	public function test_without_permission_over_the_parent_nothing_is_created() {
		$mia    = $this->area( 'Formación del Profesorado' );
		$otra   = $this->area( 'Innovación' );
		$dueno  = $this->organiser( array( $mia ) );
		$ajena  = $this->organiser( array( $otra ) );
		$evento = $this->event( $dueno, array( $mia ) );

		$salida = $this->submit(
			$ajena,
			array(
				'evt_page_event'            => (string) $evento,
				'evt_title'                 => 'Sección colada',
				EventMetaKeys::SECTION_TYPE => 'programa',
			)
		);

		$this->assertNull( $salida );
		$this->assertSame( array(), $this->secciones( $evento ) );

		$this->acting_as( $ajena );
		$_GET['evento'] = (string) $evento;
		$this->assertStringContainsString( 'de otro ámbito', PageForm::model()['error'] );
	}

	/**
	 * Y tampoco se edita una sección de otro ámbito.
	 */
	public function test_a_section_of_another_area_is_not_edited() {
		$mia     = $this->area( 'Formación del Profesorado' );
		$otra    = $this->area( 'Innovación' );
		$dueno   = $this->organiser( array( $mia ) );
		$ajena   = $this->organiser( array( $otra ) );
		$evento  = $this->event( $dueno, array( $mia ) );
		$seccion = $this->event_page( $evento, 'programa', array( 'post_title' => 'Programa' ) );

		$this->submit(
			$ajena,
			array(
				'evt_page_id'               => (string) $seccion,
				'evt_title'                 => 'Secuestrada',
				EventMetaKeys::SECTION_TYPE => 'programa',
			)
		);

		$this->assertSame( 'Programa', get_the_title( $seccion ) );
	}

	/**
	 * Cambiar el evento a mano no mueve la sección de un área a otra.
	 */
	public function test_a_section_cannot_be_moved_to_another_event_by_hand() {
		$area    = $this->area( 'Innovación' );
		$yo      = $this->organiser( array( $area ) );
		$origen  = $this->event( $yo, array( $area ) );
		$destino = $this->event( $yo, array( $area ) );
		$seccion = $this->event_page( $origen, 'programa', array( 'post_title' => 'Programa' ) );

		$this->submit(
			$yo,
			array(
				'evt_page_id'               => (string) $seccion,
				'evt_page_event'            => (string) $destino,
				'evt_title'                 => 'Programa',
				EventMetaKeys::SECTION_TYPE => 'programa',
			)
		);

		$this->assertSame( $origen, (int) get_post_field( 'post_parent', $seccion ) );
		$this->assertSame( array(), $this->secciones( $destino ) );
	}

	/**
	 * Sin nonce válido no se guarda nada, y se dice que el envío caducó.
	 */
	public function test_without_a_valid_nonce_nothing_is_saved() {
		$area   = $this->area( 'Innovación' );
		$yo     = $this->organiser( array( $area ) );
		$evento = $this->event( $yo, array( $area ) );

		$campos = array(
			'evt_page_event'            => (string) $evento,
			'evt_title'                 => 'Programa',
			EventMetaKeys::SECTION_TYPE => 'programa',
		);

		$this->assertNull( $this->submit( $yo, $campos, 'basura' ) );
		$this->assertSame( array(), $this->secciones( $evento ) );

		$_GET['evento'] = (string) $evento;
		$m              = PageForm::model();
		$this->assertStringContainsString( 'caducó', $m['error'] );
		// Y con el nonce mal, lo tecleado no se repinta: no es un envío de fiar.
		$this->assertSame( '', $m['values']['title'] );

		$this->assertNull( $this->submit( $yo, $campos, '' ) );
		$this->assertSame( array(), $this->secciones( $evento ) );
	}

	/**
	 * Sin sesión no se guarda nada, con nonce o sin él.
	 */
	public function test_without_a_session_nothing_is_saved() {
		$area   = $this->area( 'Innovación' );
		$yo     = $this->organiser( array( $area ) );
		$evento = $this->event( $yo, array( $area ) );

		$this->post(
			array(
				'evt_page_form'             => '1',
				'evt_page_event'            => (string) $evento,
				'evt_title'                 => 'Programa',
				EventMetaKeys::SECTION_TYPE => 'programa',
			),
			PageForm::NONCE_ACTION,
			PageForm::NONCE_FIELD
		);
		$this->acting_as( 0 );

		$this->assertNull( $this->exit_url( array( PageForm::class, 'maybe_handle_submit' ) ) );
		$this->assertSame( array(), $this->secciones( $evento ) );
	}

	/**
	 * El arranque engancha el shortcode y el que atiende los envíos.
	 */
	public function test_register_hooks_the_shortcode_and_the_handler() {
		PageForm::register();

		$this->assertTrue( shortcode_exists( PageForm::SHORTCODE ) );
		$this->assertSame( 20, has_action( 'init', array( PageForm::class, 'maybe_handle_submit' ) ) );
	}
}
