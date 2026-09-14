<?php
/**
 * Tests for the public view of an event: navigation, cards and header.
 *
 * @package Evt
 */

use Evt\Meta\EventMetaKeys;
use Evt\Meta\EventMetaRegistration;
use Evt\PostType\EventPostType;
use Evt\PublicFront\Block\PosterBlock;
use Evt\PublicFront\Block\SectionsBlock;
use Evt\PublicFront\EventLayout;
use Evt\PublicFront\EventView;
use Evt\PublicFront\Shell;
use Evt\PublicFront\View\EventChrome;

/**
 * Lo que ve quien visita un evento.
 *
 * No es una pantalla del aplicativo: mantiene el aspecto de las páginas de hoy
 * y reproduce en PHP lo que hacen las tres vistas del sistema anterior —la
 * cabecera, la navegación entre secciones y las tarjetas de la portada—.
 */
class Test_Event_View extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Con las páginas del aplicativo, y sin las secciones que otro test cacheó.
	 */
	public function set_up() {
		parent::set_up();
		// El marco de tests desregistra los tipos de contenido entre test y
		// test y se lleva por delante el registro de metas; se rehace aquí,
		// igual que en test-appearance-meta.php.
		EventMetaRegistration::register_meta();
		$this->pages();
		$this->forget_sections();
	}

	/**
	 * Vaciar la caché de secciones de la petición.
	 *
	 * La navegación y las tarjetas piden la misma lista y se guarda una vez por
	 * petición; en producción eso es una consulta menos, y aquí las peticiones
	 * son el mismo proceso.
	 *
	 * @return void
	 */
	private function forget_sections(): void {
		$prop = new ReflectionProperty( EventView::class, 'sections' );
		$prop->setAccessible( true );
		$prop->setValue( null, array() );
	}

	/**
	 * Los rótulos de la navegación del evento.
	 *
	 * @param array<string, mixed> $m What model() returned.
	 * @return string[]
	 */
	private function menu( array $m ): array {
		return array_map( 'strval', array_column( (array) $m['nav'], 'label' ) );
	}

	/**
	 * Un evento con su apariencia puesta.
	 *
	 * @param int $autor Author.
	 * @param int $area  Área term ID.
	 * @return int Post ID.
	 */
	private function evento_con_aspecto( int $autor, int $area ): int {
		return $this->event(
			$autor,
			array( $area ),
			array(
				EventMetaKeys::START_DATE  => '2026-03-10',
				EventMetaKeys::END_DATE    => '2026-03-12',
				EventMetaKeys::VENUE       => 'Sede Central',
				EventMetaKeys::TAGLINE     => 'Enseñar de otra manera',
				EventMetaKeys::HEADER_BG   => '#0a3d62',
				EventMetaKeys::HEADER_TEXT => '#ffffff',
				EventMetaKeys::TITLE_FONT  => 'montserrat',
				EventMetaKeys::BODY_FONT   => 'merriweather',
				EventMetaKeys::SEPARATOR   => 'wave',
			),
			array( 'post_title' => 'Jornadas de Innovación' )
		);
	}

	// ─── navegación ────────────────────────────────────────────────────────

	/**
	 * La navegación lista el inicio y solo las secciones publicadas.
	 */
	public function test_the_navigation_only_lists_published_sections() {
		$area   = $this->area( 'Innovación' );
		$autor  = $this->administrator();
		$evento = $this->event( $autor, array( $area ), array(), array( 'post_title' => 'Jornadas' ) );

		$publicada = $this->event_page(
			$evento,
			'programa',
			array(
				'post_title' => 'Programa',
				'menu_order' => 10,
			)
		);
		$borrador  = $this->event_page(
			$evento,
			'ponentes',
			array(
				'post_title'  => 'Ponentes',
				'post_status' => 'draft',
				'menu_order'  => 20,
			)
		);

		$this->acting_as( 0 );
		$m = EventView::model( $evento );

		$this->assertSame( array( 'Inicio', 'Programa' ), $this->menu( $m ) );
		$this->assertTrue( $m['nav'][0]['current'], 'en la portada, «Inicio» va marcada' );
		$this->assertFalse( $m['nav'][1]['current'] );
		$this->assertNotContains( 'Ponentes', $this->menu( $m ), 'un borrador no sale en el menú' );

		// Dentro de una sección, la marcada es esa.
		$this->forget_sections();
		$m = EventView::model( $publicada );
		$this->assertFalse( $m['nav'][0]['current'] );
		$this->assertTrue( $m['nav'][1]['current'] );
		$this->assertFalse( $m['is_root'] );
		$this->assertSame( $evento, $m['event_id'] );
		$this->assertSame( 'Jornadas', $m['event_title'] );
		$this->assertSame( array(), $m['cards'], 'las tarjetas son de la portada' );

		// Y al publicar el borrador, aparece.
		wp_update_post(
			array(
				'ID'          => $borrador,
				'post_status' => 'publish',
			)
		);
		$this->forget_sections();
		$this->assertSame( array( 'Inicio', 'Programa', 'Ponentes' ), $this->menu( EventView::model( $evento ) ) );
	}

	// ─── tarjetas ──────────────────────────────────────────────────────────

	/**
	 * Las tarjetas usan el texto por defecto de su tipo cuando no hay introducción.
	 */
	public function test_the_cards_fall_back_to_the_default_text_of_their_type() {
		$area   = $this->area( 'Innovación' );
		$autor  = $this->administrator();
		$evento = $this->event( $autor, array( $area ) );

		$ponentes = $this->event_page(
			$evento,
			'ponentes',
			array(
				'post_title' => 'Ponentes',
				'menu_order' => 10,
			)
		);
		$programa = $this->event_page(
			$evento,
			'programa',
			array(
				'post_title' => 'Programa',
				'menu_order' => 20,
			)
		);
		$otra     = $this->event_page(
			$evento,
			EventMetaKeys::SECTION_OTHER,
			array(
				'post_title' => 'Otra cosa',
				'menu_order' => 30,
			)
		);
		update_post_meta( $programa, EventMetaKeys::INTRO, 'El programa de las tres jornadas.' );

		$this->acting_as( 0 );
		$tarjetas = EventView::model( $evento )['cards'];

		$this->assertCount( 3, $tarjetas );
		$this->assertSame( $ponentes, $tarjetas[0]['id'] );
		$this->assertSame( 'Conoce los detalles de las personas comunicadoras.', $tarjetas[0]['text'] );
		$this->assertSame( 'El programa de las tres jornadas.', $tarjetas[1]['text'], 'la introducción propia manda' );
		$this->assertSame( $otra, $tarjetas[2]['id'] );
		$this->assertSame( '', $tarjetas[2]['text'], 'un tipo sin texto por defecto se queda sin él' );
		$this->assertSame( '', $tarjetas[0]['image'], 'sin imagen destacada, la tarjeta sale sin imagen' );
	}

	/**
	 * La imagen de respaldo de una tarjeta la pone quien despliega, con su filtro.
	 */
	public function test_the_fallback_card_image_comes_from_a_filter() {
		$area   = $this->area( 'Innovación' );
		$evento = $this->event( $this->administrator(), array( $area ) );
		$this->event_page( $evento, 'contacto', array( 'post_title' => 'Contacto' ) );

		add_filter(
			'evt_section_default_image',
			static function ( string $url, string $tipo ): string {
				return 'https://example.org/' . $tipo . '-default.png';
			},
			10,
			2
		);

		$this->acting_as( 0 );
		$tarjetas = EventView::model( $evento )['cards'];

		$this->assertSame( 'https://example.org/contacto-default.png', $tarjetas[0]['image'] );
		$this->assertSame( 'Información de contacto con la organización del evento.', $tarjetas[0]['text'] );
	}

	/**
	 * La rejilla de tarjetas mide el contenedor, no la ventana.
	 *
	 * Las clases `col-*` de Bootstrap responden a media queries, que miden el
	 * viewport, y esta vista se engancha en `the_content`: vive dentro del
	 * contenedor del tema, que la estrecha a la mitad. Con `col-lg-3` a 1440 px
	 * de ventana y 645 px de contenedor salían cuatro tarjetas de una letra de
	 * ancho. La rejilla es un `grid` de `auto-fit`, que mide lo que hay.
	 */
	public function test_the_card_grid_measures_the_container_and_not_the_window() {
		$area   = $this->area( 'Innovación' );
		$evento = $this->event( $this->administrator(), array( $area ) );
		$this->event_page( $evento, 'programa', array( 'post_title' => 'Programa' ) );
		$this->event_page( $evento, 'ponentes', array( 'post_title' => 'Ponentes' ) );

		$this->acting_as( 0 );
		$html = SectionsBlock::html( EventView::model( $evento ) );
		// La hoja no viaja con el bloque: la escribe `EventView::print_stylesheet()`
		// en `wp_head`, para que el CSS a medida del evento pueda pisarla.
		$hoja = EventView::stylesheet();

		$this->assertStringContainsString( 'evt-ev__rejilla', $html, 'la portada pinta la rejilla' );
		$this->assertStringNotContainsString( '<style', $html, 'la hoja no viaja dentro del bloque' );
		$this->assertDoesNotMatchRegularExpression(
			'/class="[^"]*\bcol-(?:xs-|sm-|md-|lg-|xl-|xxl-|\d)/',
			$html,
			'la rejilla no lleva clases col-* de Bootstrap: miden la ventana y no el contenedor'
		);
		$this->assertStringNotContainsString( 'class="row ', $html, 'ni la fila de Bootstrap, que la haría flex' );
		$this->assertStringContainsString(
			'grid-template-columns: repeat(auto-fit,',
			$hoja,
			'la rejilla es un grid para todo el mundo, con Bootstrap o sin él'
		);
		$this->assertStringNotContainsString(
			'evt-sin-bootstrap .evt-ev__rejilla',
			$hoja,
			'un solo camino: sin regla de respaldo aparte'
		);
	}

	// ─── cabecera ──────────────────────────────────────────────────────────

	/**
	 * La cabecera aplica los colores y las tipografías guardados en las metas.
	 */
	public function test_the_header_applies_the_colours_and_the_typefaces() {
		$area   = $this->area( 'Innovación' );
		$evento = $this->evento_con_aspecto( $this->administrator(), $area );

		$this->acting_as( 0 );
		$m    = EventView::model( $evento );
		$look = $m['appearance'];

		$this->assertSame( '#0a3d62', $look['bg'] );
		$this->assertSame( '#ffffff', $look['fg'] );
		$this->assertStringContainsString( 'Montserrat', $look['title_font'] );
		$this->assertStringContainsString( 'Merriweather', $look['body_font'] );
		$this->assertSame( 'wave', $look['separator'] );
		$this->assertSame( 'Enseñar de otra manera', $m['tagline'] );
		$this->assertSame( 'Sede Central', $m['venue'] );
		$this->assertStringContainsString( 'Del ', $m['dates'] );

		// Los colores y las tipografías son TOKENS, en un solo sitio, y no un
		// atributo `style`: un `style` en línea solo se pisa con `!important`.
		$tokens = EventLayout::tokens( $m );
		$this->assertStringContainsString( '--evt-fondo:#0a3d62', $tokens );
		$this->assertStringContainsString( '--evt-texto:#ffffff', $tokens );
		$this->assertStringContainsString( 'Montserrat', $tokens );

		$html = EventChrome::cover( $m );
		$this->assertStringNotContainsString( 'style="background-color', $html );
		$this->assertStringContainsString( 'Jornadas de Innovación', $html );
		$this->assertStringContainsString( 'Enseñar de otra manera', $html );
		$this->assertStringContainsString( '<svg class="evt-ev__separador"', $html, 'el separador de la lista cerrada' );
	}

	/**
	 * Lo que no es un color ni una tipografía de la lista no viste nada.
	 */
	public function test_a_broken_appearance_paints_the_default_one() {
		$area   = $this->area( 'Innovación' );
		$evento = $this->event( $this->administrator(), array( $area ) );

		// Se escriben saltándose el registro de metas, que es como llegan las
		// que ya están puestas cuando se migra lo de hoy.
		$basura = array(
			EventMetaKeys::HEADER_BG  => 'rojo bombero',
			EventMetaKeys::TITLE_FONT => 'comic-sans',
			EventMetaKeys::SEPARATOR  => 'zigzag',
		);
		foreach ( $basura as $clave => $valor ) {
			unregister_post_meta( EventPostType::POST_TYPE, $clave );
			update_post_meta( $evento, $clave, $valor );
		}
		EventMetaRegistration::register_meta();
		$this->assertSame( 'comic-sans', get_post_meta( $evento, EventMetaKeys::TITLE_FONT, true ) );

		$this->acting_as( 0 );
		$look = EventView::model( $evento )['appearance'];

		$this->assertSame( '', $look['bg'] );
		$this->assertSame( '', $look['title_font'], 'sin tipografía elegida no se carga ninguna' );
		$this->assertSame( '', $look['separator'] );

		$m    = EventView::model( $evento );
		$html = EventChrome::cover( $m ) . EventLayout::tokens( $m );
		$this->assertStringNotContainsString( 'rojo bombero', $html );
		$this->assertStringNotContainsString( 'comic-sans', $html );
	}

	/**
	 * El cartel se enseña en la portada del evento, y solo ahí.
	 *
	 * El panel de apariencia lo recoge y promete que «se enseña en la portada
	 * del evento»; hasta ahora se guardaba y no se pintaba en ninguna parte.
	 */
	public function test_the_poster_is_painted_on_the_front_page_only() {
		$area    = $this->area( 'Innovación' );
		$evento  = $this->event( $this->administrator(), array( $area ) );
		$adjunto = self::factory()->attachment->create_upload_object(
			DIR_TESTDATA . '/images/canola.jpg',
			$evento
		);
		update_post_meta( $evento, EventMetaKeys::POSTER_ID, $adjunto );
		update_post_meta( $adjunto, '_wp_attachment_image_alt', 'Cartel de las jornadas' );

		$this->acting_as( 0 );

		$look = EventView::model( $evento )['appearance'];
		$this->assertNotSame( '', $look['poster'], 'la portada tiene que traer el cartel' );
		$this->assertNotSame( '', $look['poster_full'], 'y el original, que es el que se descarga' );

		$portada = PosterBlock::html( EventView::model( $evento ) );
		$this->assertStringContainsString( 'evt-ev__cartel', $portada );
		$this->assertStringContainsString( 'Cartel de las jornadas', $portada );
		$this->assertStringContainsString( esc_url( $look['poster_full'] ), $portada );

		// En una sección no se repite: sería el mismo cartel cinco veces.
		$seccion = $this->event_page( $evento, 'programa' );
		$this->assertSame( '', PosterBlock::html( EventView::model( $seccion ) ) );
	}

	/**
	 * Sin cartel no se deja hueco.
	 */
	public function test_without_a_poster_nothing_is_painted() {
		$area   = $this->area( 'Innovación' );
		$evento = $this->event( $this->administrator(), array( $area ) );

		$this->acting_as( 0 );

		$this->assertSame( '', EventView::model( $evento )['appearance']['poster'] );
		$this->assertSame( '', PosterBlock::html( EventView::model( $evento ) ), 'sin cartel no hay hueco' );
	}

	/**
	 * Un solo día no se escribe como un intervalo.
	 */
	public function test_a_one_day_event_is_not_written_as_a_range() {
		$area   = $this->area( 'Innovación' );
		$evento = $this->event(
			$this->administrator(),
			array( $area ),
			array( EventMetaKeys::START_DATE => '2026-03-10' )
		);

		$this->acting_as( 0 );
		$m = EventView::model( $evento );

		$this->assertStringNotContainsString( 'Del ', $m['dates'] );
		$this->assertNotSame( '', $m['dates'] );

		// Y sin fechas, no se escribe nada.
		delete_post_meta( $evento, EventMetaKeys::START_DATE );
		$this->assertSame( '', EventView::model( $evento )['dates'] );
	}

	// ─── el botón de gestión ───────────────────────────────────────────────

	/**
	 * El enlace al taller solo sale para quien puede editar el evento.
	 */
	public function test_the_manage_link_is_only_for_whoever_can_edit() {
		$mia    = $this->area( 'Formación del Profesorado' );
		$otra   = $this->area( 'Innovación' );
		$dueno  = $this->organiser( array( $mia ) );
		$ajena  = $this->organiser( array( $otra ) );
		$evento = $this->event( $dueno, array( $mia ) );

		$this->acting_as( 0 );
		$this->assertSame( '', EventView::model( $evento )['manage_url'] );

		$this->acting_as( $ajena );
		$this->assertSame( '', EventView::model( $evento )['manage_url'], 'de otra área, ni verlo' );

		$this->acting_as( $dueno );
		$url = EventView::model( $evento )['manage_url'];
		$this->assertSame( Shell::url( 'event', array( EventView::MANAGE_ARG => $evento ) ), $url );
		$this->assertStringContainsString( 'Gestionar este evento', EventChrome::cover( EventView::model( $evento ) ) );
	}

	// ─── el envoltorio ─────────────────────────────────────────────────────

	/**
	 * Fuera de un evento no se toca nada: la página del sitio sigue siendo del sitio.
	 */
	public function test_outside_an_event_the_content_is_left_alone() {
		$otra = (int) self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => 'Una página del sitio.',
			)
		);
		$this->go_to( (string) get_permalink( $otra ) );

		$this->assertFalse( EventView::takes_over(), 'una página que no es un evento la pinta el tema' );
		$this->assertNull( $this->exit_url( array( EventView::class, 'render' ) ), 'y no se sirve ningún documento' );

		// Y sin entrada, el modelo pinta el contenido y nada más.
		$m = EventView::model( 0, 'Solo el contenido.' );
		$this->assertSame( 0, $m['event_id'] );
		$this->assertSame( array(), $m['nav'] );
		$this->assertSame( array(), $m['cards'] );
		$this->assertSame( 'Solo el contenido.', $m['content'] );
	}

	/**
	 * La navegación no se pinta cuando el evento todavía no tiene secciones.
	 */
	public function test_without_sections_there_is_no_navigation_bar() {
		$area   = $this->area( 'Innovación' );
		$evento = $this->event( $this->administrator(), array( $area ) );

		$this->acting_as( 0 );
		$m = EventView::model( $evento );

		$this->assertSame( '', EventChrome::nav( (array) $m['nav'] ), 'con una sola entrada no hay entre qué navegar' );
		$this->assertSame( '', SectionsBlock::html( $m ), 'sin secciones no se pinta una rejilla vacía' );
	}

	/**
	 * El arranque sirve el documento entero y deja `the_content` en paz.
	 *
	 * Sustituye a la decisión anterior, que envolvía el artículo en
	 * `the_content` para conservar la cabecera y el pie del tema. Medida la
	 * página real, el tema no aportaba ninguna de las dos cosas: el pie
	 * institucional estaba escrito a mano dentro de un módulo del tema.
	 */
	public function test_register_serves_the_whole_document() {
		EventView::register();

		$this->assertSame(
			EventView::PRIORITY,
			has_action( 'template_redirect', array( EventView::class, 'render' ) )
		);
		$this->assertFalse(
			has_filter( 'the_content', array( EventView::class, 'render' ) ),
			'ya no se envuelve el artículo: se pinta el documento'
		);
		$this->assertNotFalse( has_action( 'wp_head', array( EventView::class, 'print_stylesheet' ) ) );
		$this->assertNotFalse( has_action( 'wp_enqueue_scripts', array( EventView::class, 'drop_page_assets' ) ) );
		$this->assertNotFalse( has_filter( 'style_loader_tag', array( EventView::class, 'drop_page_tag' ) ) );
	}
}
