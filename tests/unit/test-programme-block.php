<?php
/**
 * Tests for the public speakers and programme of an event.
 *
 * @package Evt
 */

use Evt\Meta\EventMetaKeys;
use Evt\Meta\ProgrammeMetaKeys;
use Evt\PostType\EventPostType;
use Evt\PublicFront\Block\ProgrammeBlock;
use Evt\PublicFront\EventView;
use Evt\PublicFront\Programme;

/**
 * Cada tipo de sección pinta lo suyo con los datos del evento (ADR-0042).
 */
class Test_Programme_Block extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * The event.
	 *
	 * @var int
	 */
	private $evento;

	/**
	 * Un evento con una ponente, una actividad y sus páginas.
	 */
	public function set_up() {
		parent::set_up();
		$this->evento = $this->event( $this->administrator(), array( $this->area() ), array(), array( 'post_title' => 'Congreso de prueba' ) );
		$this->acting_as( 0 );
		$prop = new ReflectionProperty( ProgrammeBlock::class, 'sections' );
		$prop->setAccessible( true );
		$prop->setValue( null, array() );
		set_query_var( EventPostType::ENTRY_VAR, 0 );
	}

	/**
	 * A speaker of the event.
	 *
	 * @param array<string, mixed> $extra Fields.
	 * @return int
	 */
	private function ponente( array $extra = array() ): int {
		return Programme::save_speaker(
			$this->evento,
			0,
			array_merge(
				array(
					'name'     => 'Ana Pérez',
					'role'     => 'Asesora',
					'org'      => 'Centro de formación',
					'bio'      => 'Treinta años en el aula.',
					'featured' => false,
				),
				$extra
			)
		);
	}

	/**
	 * An activity of the event.
	 *
	 * @param array<string, mixed> $extra Fields.
	 * @return int
	 */
	private function actividad( array $extra = array() ): int {
		return Programme::save_activity(
			$this->evento,
			0,
			array_merge(
				array(
					'title'    => 'Aulas del futuro',
					'kind'     => 'conferencia',
					'date'     => '2026-11-26',
					'start'    => '17:00',
					'end'      => '18:00',
					'venue'    => 'Agüimes',
					'room'     => 'Auditorio',
					'seats'    => 0,
					'summary'  => 'La escuela cambia.',
					'speakers' => array(),
					'video'    => '',
					'guests'   => '',
				),
				$extra
			)
		);
	}

	/**
	 * The model of one section page.
	 *
	 * @param int $pagina Section.
	 * @return array<string, mixed>
	 */
	private function modelo( int $pagina ): array {
		return EventView::model( $pagina );
	}

	/**
	 * La página de ponentes los lista, con su enlace a la ficha de siempre.
	 */
	public function test_the_speakers_page_lists_them_with_a_link_to_each_entry() {
		$ponente = $this->ponente();
		$pagina  = $this->event_page( $this->evento, 'ponentes' );

		$html = ProgrammeBlock::html( $this->modelo( $pagina ) );

		$this->assertStringContainsString( 'Ana Pérez', $html );
		$this->assertStringContainsString( 'Asesora – Centro de formación', $html );
		$this->assertStringContainsString( esc_url( EventPostType::entry_url( $pagina, $ponente ) ), $html );
		$this->set_permalink_structure( '/%postname%/' );
		$this->assertStringEndsWith( '/entry/' . $ponente . '/', EventPostType::entry_url( $pagina, $ponente ), 'la forma de hoy' );
		$this->set_permalink_structure( '' );
		$this->assertStringContainsString( 'evt-ev__retrato--vacio', $html, 'sin foto sale la silueta' );
	}

	/**
	 * Con `/entry/<N>/` sale la ficha, también por el número antiguo.
	 */
	public function test_an_entry_shows_the_detail_also_by_its_old_number() {
		$ponente = $this->ponente( array( 'bio' => 'Biografía completa y larga.' ) );
		update_post_meta( $ponente, ProgrammeMetaKeys::LEGACY_ENTRY, '4408' );
		$pagina = $this->event_page( $this->evento, 'ponentes' );

		set_query_var( EventPostType::ENTRY_VAR, $ponente );
		$this->assertStringContainsString( 'Biografía completa y larga.', ProgrammeBlock::html( $this->modelo( $pagina ) ) );

		set_query_var( EventPostType::ENTRY_VAR, 4408 );
		$html = ProgrammeBlock::html( $this->modelo( $pagina ) );
		$this->assertStringContainsString( 'evt-ev__ficha', $html );
		$this->assertStringContainsString( 'Todos los ponentes', $html );
	}

	/**
	 * Una ficha de otro evento no se enseña aquí: sale la lista.
	 */
	public function test_an_entry_of_another_event_is_not_shown() {
		$otro   = $this->event( $this->administrator(), array( $this->area() ) );
		$ajeno  = Programme::save_speaker(
			$otro,
			0,
			array(
				'name' => 'De fuera',
				'role' => '',
				'org'  => '',
				'bio'  => 'Secreto.',
			)
		);
		$pagina = $this->event_page( $this->evento, 'ponentes' );
		$this->ponente();

		set_query_var( EventPostType::ENTRY_VAR, $ajeno );
		$html = ProgrammeBlock::html( $this->modelo( $pagina ) );

		$this->assertStringNotContainsString( 'Secreto.', $html );
		$this->assertStringContainsString( 'Ana Pérez', $html );
	}

	/**
	 * El programa agrupa por sede y día, con su tipo y sus ponentes.
	 */
	public function test_the_programme_page_paints_the_grid() {
		$ponente = $this->ponente();
		$this->actividad(
			array(
				'speakers' => array( $ponente ),
				'guests'   => 'Luis Gil (moderador)',
			)
		);
		$pagina = $this->event_page( $this->evento, 'programa' );

		$html = ProgrammeBlock::html( $this->modelo( $pagina ) );

		$this->assertStringContainsString( 'Agüimes – 26 de ', $html );
		$this->assertStringContainsString( 'Conferencia', $html );
		$this->assertStringContainsString( '17:00–18:00', $html );
		$this->assertStringContainsString( 'Ana Pérez', $html );
		$this->assertStringContainsString( 'Luis Gil (moderador)', $html );
	}

	/**
	 * Un panel por día y sede, con la clase de siempre y el guion de las pestañas.
	 *
	 * La clase `programa-estandar` es la que esconde el CSS a medida de los
	 * eventos que escribieron su programa a mano: si se pierde, sale dos veces.
	 */
	public function test_the_programme_is_one_tab_per_day_and_keeps_its_old_class() {
		$this->actividad();
		$this->actividad( array( 'date' => '2026-11-27' ) );
		$pagina = $this->event_page( $this->evento, 'programa' );

		$html = ProgrammeBlock::html( $this->modelo( $pagina ) );

		$this->assertStringContainsString( 'programa-estandar', $html );
		$this->assertStringContainsString( 'data-evt-pestanas', $html );
		$this->assertSame( 2, substr_count( $html, 'class="evt-ev__dia-panel"' ) );
		$this->assertStringContainsString( '<script>', $html, 'el guion de las pestañas va con la parrilla' );
		$this->assertStringContainsString( 'evt-ev__hueco--conferencia', $html, 'el color va por tipo' );
	}

	/**
	 * En acordeón, un desplegable por día, el primero abierto y sin guion.
	 */
	public function test_the_accordion_layout_is_one_details_per_day() {
		update_post_meta( $this->evento, EventMetaKeys::PROGRAMME_LAYOUT, EventMetaKeys::LAYOUT_ACCORDION );
		$this->actividad();
		$this->actividad( array( 'date' => '2026-11-27' ) );
		$pagina = $this->event_page( $this->evento, 'programa' );

		$html = ProgrammeBlock::html( $this->modelo( $pagina ) );

		$this->assertSame( 2, substr_count( $html, '<details class="evt-ev__dia-panel" name="evt-programa"' ) );
		$this->assertSame( 1, substr_count( $html, ' open>' ), 'solo el primero abierto' );
		$this->assertStringNotContainsString( 'data-evt-pestanas', $html );
		$this->assertStringNotContainsString( '<script>', $html );
	}

	/**
	 * Lo que no tiene fecha no sale en el programa: no hay pestaña donde ponerlo.
	 */
	public function test_an_activity_without_a_date_is_not_in_the_programme() {
		$this->actividad(
			array(
				'title' => 'Taller suelto',
				'date'  => '',
			)
		);
		$pagina = $this->event_page( $this->evento, 'programa' );

		$this->assertSame( '', ProgrammeBlock::html( $this->modelo( $pagina ) ) );
	}

	/**
	 * Multimedia solo enseña las actividades que tienen vídeo.
	 */
	public function test_the_multimedia_page_only_lists_activities_with_video() {
		$this->actividad(
			array(
				'title' => 'Con grabación',
				'video' => 'https://example.org/video.mp4',
			)
		);
		$this->actividad( array( 'title' => 'Sin grabación' ) );
		$pagina = $this->event_page( $this->evento, 'multimedia' );

		$html = ProgrammeBlock::html( $this->modelo( $pagina ) );

		$this->assertStringContainsString( 'Con grabación', $html );
		$this->assertStringNotContainsString( 'Sin grabación', $html );
	}

	/**
	 * La portada enseña a los destacados, y a nadie más.
	 */
	public function test_the_front_page_shows_the_featured_speakers() {
		$this->ponente(
			array(
				'name'     => 'Destacada',
				'featured' => true,
			)
		);
		$this->ponente( array( 'name' => 'Del montón' ) );
		$this->event_page( $this->evento, 'ponentes' );

		$html = ProgrammeBlock::featured( $this->modelo( $this->evento ) );

		$this->assertStringContainsString( 'Personas comunicadoras', $html );
		$this->assertStringContainsString( 'Destacada', $html );
		$this->assertStringNotContainsString( 'Del montón', $html );
		$this->assertSame( '', ProgrammeBlock::html( $this->modelo( $this->evento ) ), 'en la portada no va la lista' );
	}

	/**
	 * La página de actividades las lista, y con `/entry/<N>/` enseña una entera.
	 */
	public function test_the_activities_page_lists_them_and_opens_one() {
		$ponente   = $this->ponente();
		$actividad = $this->actividad(
			array(
				'speakers' => array( $ponente ),
				'summary'  => 'Una descripción entera que en la lista va recortada.',
				'video'    => 'https://example.org/grabacion.mp4',
			)
		);
		$this->actividad(
			array(
				'title' => 'Sin fecha',
				'date'  => '',
			)
		);
		$pagina = $this->event_page( $this->evento, 'actividades' );
		$fichas = $this->event_page( $this->evento, 'ponentes' );

		$html = ProgrammeBlock::html( $this->modelo( $pagina ) );
		$this->assertStringContainsString( 'Aulas del futuro', $html );
		$this->assertStringContainsString( '(Asesora)', $html, 'en la lista, cada ponente con su cargo' );
		$this->assertStringContainsString( esc_url( EventPostType::entry_url( $fichas, $ponente ) ), $html );

		set_query_var( EventPostType::ENTRY_VAR, $actividad );
		$ficha = ProgrammeBlock::html( $this->modelo( $pagina ) );
		$this->assertStringContainsString( 'evt-ev__ficha--actividad', $ficha );
		$this->assertStringContainsString( '<video', $ficha, 'un mp4 se reproduce aquí mismo' );
		$this->assertStringContainsString( 'Volver', $ficha );

		// La ficha del ponente dice en qué participa.
		set_query_var( EventPostType::ENTRY_VAR, $ponente );
		$this->assertStringContainsString( 'Participa en', ProgrammeBlock::html( $this->modelo( $fichas ) ) );
	}

	/**
	 * Sin página de actividades, las fichas cuelgan del programa; y el vídeo se enseña como se puede.
	 */
	public function test_without_an_activities_page_entries_hang_from_the_programme() {
		$actividad = $this->actividad( array( 'video' => 'https://example.org/podcast.mp3' ) );
		$this->actividad(
			array(
				'title' => 'Otra',
				'video' => 'https://example.org/no-es-un-video',
			)
		);
		$programa = $this->event_page( $this->evento, 'programa' );
		$media    = $this->event_page( $this->evento, 'multimedia' );

		$this->assertStringContainsString( esc_url( EventPostType::entry_url( $programa, $actividad ) ), ProgrammeBlock::html( $this->modelo( $programa ) ) );

		add_filter( 'pre_oembed_result', '__return_false' );
		$html = ProgrammeBlock::html( $this->modelo( $media ) );
		remove_filter( 'pre_oembed_result', '__return_false' );
		$this->assertStringContainsString( '<audio', $html );
		$this->assertStringContainsString( 'Ver el vídeo', $html, 'lo que no se sabe incrustar, como enlace' );

		set_query_var( EventPostType::ENTRY_VAR, $actividad );
		$this->assertStringContainsString( 'evt-ev__ficha--actividad', ProgrammeBlock::html( $this->modelo( $media ) ) );
	}

	/**
	 * Lo que no es una sección con datos, o no hay datos, no pinta nada.
	 */
	public function test_nothing_to_paint_paints_nothing() {
		$this->assertSame( '', ProgrammeBlock::html( $this->modelo( $this->event_page( $this->evento, 'contacto' ) ) ) );
		foreach ( array( 'ponentes', 'programa', 'actividades', 'multimedia' ) as $tipo ) {
			$this->assertSame( '', ProgrammeBlock::html( $this->modelo( $this->event_page( $this->evento, $tipo ) ) ), $tipo );
		}
		$this->assertSame( '', ProgrammeBlock::featured( $this->modelo( $this->evento ) ), 'sin destacados, sin bloque' );
		$this->assertSame( '', ProgrammeBlock::featured( EventView::model( 0 ) ) );
	}

	/**
	 * Un borrador no sale en la web.
	 */
	public function test_drafts_are_not_public() {
		$ponente = $this->ponente();
		wp_update_post(
			array(
				'ID'          => $ponente,
				'post_status' => 'draft',
			)
		);
		$pagina = $this->event_page( $this->evento, 'ponentes' );

		$this->assertStringNotContainsString( 'Ana Pérez', ProgrammeBlock::html( $this->modelo( $pagina ) ) );
	}

	/**
	 * El vídeo tiene que ser una dirección.
	 */
	public function test_the_video_has_to_be_a_url() {
		$mal = \Evt\Domain\ActivityInput::activity(
			array(
				'title' => 'X',
				'kind'  => 'ponencia',
				'date'  => '2026-11-26',
				'video' => 'javascript:alert(1)',
			)
		);
		$this->assertContains( 'video', $mal['errors'] );
		$this->assertSame( 'Conferencia', ProgrammeMetaKeys::kind_label( 'conferencia' ) );
		unset( $mal );
		$this->assertArrayHasKey( 'encuentro', ProgrammeMetaKeys::activity_kinds() );
		$this->assertArrayHasKey( 'nosifer', EventMetaKeys::fonts() );
	}
}
