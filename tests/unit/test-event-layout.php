<?php
/**
 * Tests for the skeleton of the public event page: document, blocks and tokens.
 *
 * @package Evt
 */

use Evt\Meta\EventMetaKeys;
use Evt\Meta\EventMetaRegistration;
use Evt\PublicFront\EventLayout;
use Evt\PublicFront\EventView;
use Evt\PublicFront\Shell;
use Evt\PublicFront\View\EventChrome;

/**
 * El armazón: el documento entero, los bloques con nombre y los tokens.
 *
 * La página de un evento ya no es un envoltorio dentro de `the_content`: es un
 * documento completo servido en `template_redirect`, sin el maquetador y sin el tema.
 */
class Test_Event_Layout extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Con las páginas del aplicativo y sin nada cacheado del test anterior.
	 */
	public function set_up() {
		parent::set_up();
		EventMetaRegistration::register_meta();
		$this->pages();
		$this->forget( 'sections' );
		$this->forget( 'models' );
		$this->bloques_de_serie();
	}

	/**
	 * Dejar los bloques como los deja el arranque, y solo esos.
	 *
	 * @return void
	 */
	private function bloques_de_serie(): void {
		foreach ( array_keys( EventLayout::blocks() ) as $nombre ) {
			EventLayout::remove_block( (string) $nombre );
		}
		EventLayout::register();
	}

	/**
	 * Vaciar una de las cachés de petición de EventView.
	 *
	 * En producción cada petición es un proceso; aquí son todas el mismo.
	 *
	 * @param string $propiedad Static property name.
	 * @return void
	 */
	private function forget( string $propiedad ): void {
		$prop = new ReflectionProperty( EventView::class, $propiedad );
		$prop->setAccessible( true );
		$prop->setValue( null, array() );
	}

	/**
	 * Un evento con su apariencia y su cabecera puestas.
	 *
	 * @return int
	 */
	private function un_evento(): int {
		return $this->event(
			$this->administrator(),
			array( $this->area( 'Innovación' ) ),
			array(
				EventMetaKeys::START_DATE  => '2026-11-05',
				EventMetaKeys::END_DATE    => '2026-11-07',
				EventMetaKeys::VENUE       => 'Sede Central',
				EventMetaKeys::TAGLINE     => 'Enseñar de otra manera',
				EventMetaKeys::HASHTAG     => '#Rurales26',
				EventMetaKeys::HEADER_BG   => '#0a3d62',
				EventMetaKeys::HEADER_TEXT => '#ffffff',
				EventMetaKeys::INTRO       => 'Tres días para pensar la escuela rural.',
			),
			array(
				'post_title'   => 'Escuelas rurales 2026',
				// Sin extracto propio, para que la descripción salga del texto
				// introductorio del evento, que es el caso normal.
				'post_excerpt' => '',
			)
		);
	}

	/**
	 * El documento servido de un evento, entero.
	 *
	 * @param int $post_id Event or section.
	 * @return string
	 */
	private function documento( int $post_id ): string {
		$this->forget( 'models' );
		$this->go_to( (string) get_permalink( $post_id ) );
		return $this->served( array( EventView::class, 'render' ) );
	}

	// ─── el documento ──────────────────────────────────────────────────────

	/**
	 * Se sirve un documento completo y válido, con todo lo que ponía el tema.
	 */
	public function test_the_page_is_a_whole_valid_document() {
		$evento = $this->un_evento();
		$this->acting_as( 0 );

		$html = $this->documento( $evento );

		$this->assertStringContainsString( '<!doctype html>', $html );
		// El idioma lo declaramos nosotros: es del sitio, no del tema.
		$this->assertMatchesRegularExpression( '/<html [^>]*lang="[a-zA-Z-]+"/', $html );
		$this->assertStringContainsString( 'name="viewport"', $html );
		$this->assertStringContainsString( 'charset=', $html );
		$this->assertStringContainsString( 'Escuelas rurales 2026', $html );
		$this->assertStringContainsString( 'property="og:title"', $html, 'para cuando se comparte' );
		$this->assertStringContainsString( 'property="og:url"', $html );
		$this->assertStringContainsString( 'name="description"', $html );
		$this->assertStringContainsString( 'Tres días para pensar la escuela rural.', $html );
		$this->assertStringContainsString( '</html>', $html );
	}

	/**
	 * Un solo `<h1>`, saltar al contenido y el `<main>` al que apunta.
	 */
	public function test_the_document_is_accessible_by_construction() {
		$evento = $this->un_evento();
		$this->event_page( $evento, 'programa', array( 'post_title' => 'Programa' ) );
		$this->acting_as( 0 );

		$html = $this->documento( $evento );

		$this->assertSame( 1, substr_count( $html, '<h1' ), 'un solo h1 por página' );
		$this->assertStringContainsString( 'href="#contenido"', $html );
		$this->assertStringContainsString( 'Saltar al contenido', $html );
		$this->assertStringContainsString( 'id="contenido"', $html );
		$this->assertStringContainsString( '<nav class="evt-ev__nav', $html );

		// Y en una sección, la entrada actual va marcada.
		$seccion = $this->event_page( $evento, 'ponentes', array( 'post_title' => 'Ponentes' ) );
		$this->forget( 'sections' );
		$this->assertStringContainsString( 'aria-current="page"', $this->documento( $seccion ) );
	}

	/**
	 * El pie es el institucional de hoy, con sus teclas de acceso.
	 */
	public function test_the_footer_is_whatever_the_deployment_configured() {
		// Sin configurar no hay pie de nadie: el aplicativo no lleva dentro la
		// marca de ninguna organización (ADR-0030).
		remove_all_filters( EventChrome::HOOK );
		$vacio = EventChrome::footer();
		$this->assertStringNotContainsString( 'http', $vacio, 'ni un enlace' );

		add_filter(
			EventChrome::HOOK,
			static function ( array $chrome ): array {
				return array_merge(
					$chrome,
					array(
						'owner'        => 'Organización de ejemplo',
						'owner_url'    => 'https://www.example.org/',
						'footer_links' => array(
							array(
								'label' => 'Aviso legal',
								'url'   => 'https://www.example.org/aviso-legal/',
								'title' => 'Aviso legal (tecla de acceso: l)',
							),
						),
					)
				);
			}
		);

		$pie = EventChrome::footer();
		$this->assertStringContainsString( '© Organización de ejemplo', html_entity_decode( $pie ) );
		$this->assertStringContainsString( 'Aviso legal (tecla de acceso: l)', $pie );
		$this->assertStringContainsString( 'https://www.example.org/aviso-legal/', $pie );
	}

	// ─── bloques con nombre ────────────────────────────────────────────────

	/**
	 * Un bloque nuevo se registra y sale, sin tocar el armazón.
	 *
	 * Es la costura por la que entran las pestañas que se están construyendo en
	 * paralelo: programa, ponentes, talleres e inscripción.
	 */
	public function test_a_new_block_needs_no_change_to_the_skeleton() {
		$evento = $this->un_evento();
		$this->acting_as( 0 );

		EventLayout::add_block(
			'programa',
			static function ( array $m ): string {
				return '<h2>Programa de ' . esc_html( (string) $m['event_title'] ) . '</h2>';
			},
			5
		);

		$html = $this->documento( $evento );

		$this->assertStringContainsString( 'evt-ev__bloque--programa', $html );
		$this->assertStringContainsString( 'Programa de Escuelas rurales 2026', $html );

		// Y se puede quitar sin tocar nada más.
		EventLayout::remove_block( 'programa' );
		$this->assertArrayNotHasKey( 'programa', EventLayout::blocks() );
	}

	/**
	 * Los bloques salen por prioridad, y los empates por orden de registro.
	 */
	public function test_the_blocks_are_painted_in_order() {
		$pintar = static function ( string $marca ): callable {
			return static function () use ( $marca ): string {
				return $marca;
			};
		};

		EventLayout::add_block( 'tarde', $pintar( '[tarde]' ), 90 );
		EventLayout::add_block( 'pronto', $pintar( '[pronto]' ), 1 );

		$this->assertSame(
			array( 'pronto', 'contenido', 'cartel', 'inscripcion', 'secciones', 'tarde' ),
			array_keys( EventLayout::blocks() )
		);

		$cuerpo = EventLayout::body( EventView::model( 0, '' ) );
		$this->assertLessThan( strpos( $cuerpo, '[tarde]' ), strpos( $cuerpo, '[pronto]' ) );
	}

	/**
	 * Un bloque que no tiene nada que decir no deja hueco.
	 */
	public function test_an_empty_block_paints_nothing() {
		EventLayout::add_block(
			'vacio',
			static function (): string {
				return '   ';
			}
		);

		$this->assertStringNotContainsString( 'evt-ev__bloque--vacio', EventLayout::body( EventView::model( 0, '' ) ) );
	}

	// ─── tokens ────────────────────────────────────────────────────────────

	/**
	 * El aspecto son propiedades personalizadas, y en UN solo sitio.
	 *
	 * Ni un `style` en línea con los colores: un `style` solo se pisa con
	 * `!important`, y eso es lo contrario de «fácilmente modificable».
	 */
	public function test_the_appearance_is_custom_properties_in_one_place() {
		$evento = $this->un_evento();
		update_post_meta( $evento, EventMetaKeys::TITLE_FONT, 'montserrat' );
		update_post_meta( $evento, EventMetaKeys::IMAGE_SHAPE, EventMetaKeys::SHAPE_CIRCLE );
		$this->acting_as( 0 );

		$html = $this->documento( $evento );

		$this->assertSame( 1, substr_count( $html, 'id="evt-evento-tokens"' ), 'los tokens, en un solo sitio' );
		$this->assertStringContainsString( '--evt-fondo:#0a3d62', $html );
		$this->assertStringContainsString( '--evt-texto:#ffffff', $html );
		$this->assertStringContainsString( 'Montserrat', $html );
		$this->assertStringContainsString( '--evt-forma:50%', $html );
		$this->assertStringNotContainsString( 'style="background-color', $html, 'el color no va en un atributo style' );
	}

	/**
	 * Un token no puede colar una regla: es un valor, no un trozo de hoja.
	 */
	public function test_a_token_cannot_smuggle_a_rule() {
		$m = EventView::model( $this->un_evento() );

		add_filter(
			'evt_event_tokens',
			static function ( array $tokens ): array {
				$tokens['--evt-radio'] = '0}body{display:none';
				return $tokens;
			}
		);

		$estilo = EventLayout::tokens( $m );
		$this->assertStringNotContainsString( 'display:none', $estilo );
		$this->assertStringNotContainsString( '--evt-radio', $estilo );
	}

	/**
	 * El contraste se comprueba, no se supone.
	 */
	public function test_an_unreadable_combination_is_corrected() {
		$this->assertEqualsWithDelta( 21.0, EventChrome::contrast_ratio( '#000000', '#ffffff' ), 0.01 );
		$this->assertLessThan( EventChrome::MIN_CONTRAST, EventChrome::contrast_ratio( '#ffff00', '#ffffff' ) );

		// Amarillo con texto blanco no se lee: se escribe en negro.
		$this->assertSame( '#000000', EventChrome::readable_ink( '#ffff00', '#ffffff' ) );
		// Y una combinación que sí llega se respeta tal cual.
		$this->assertSame( '#ffffff', EventChrome::readable_ink( '#0a3d62', '#ffffff' ) );
		// Sin dato no se acusa a nadie.
		$this->assertSame( 1.0, EventChrome::contrast_ratio( 'no es un color', '#fff' ) );
	}

	/**
	 * Y el token que sale es el corregido, no el que estaba guardado.
	 */
	public function test_the_token_carries_the_corrected_ink() {
		$evento = $this->un_evento();
		update_post_meta( $evento, EventMetaKeys::HEADER_BG, '#ffff00' );
		update_post_meta( $evento, EventMetaKeys::HEADER_TEXT, '#ffffff' );
		$this->forget( 'models' );

		$estilo = EventLayout::tokens( EventView::model( $evento ) );

		$this->assertStringContainsString( '--evt-texto:#000000', $estilo );
	}

	// ─── mobile-first ──────────────────────────────────────────────────────

	/**
	 * La hoja es mobile-first: rejillas que miden el contenedor y un solo gutter.
	 */
	public function test_the_stylesheet_is_mobile_first() {
		$css = EventView::stylesheet();

		$this->assertNotSame( '', $css, 'la hoja del evento se lee' );
		$this->assertStringContainsString( 'repeat(auto-fit, minmax(min(230px, 100%), 1fr))', $css );
		$this->assertStringContainsString( 'clamp(', $css, 'tipografía fluida' );
		$this->assertStringContainsString( '--evt-espacio', $css, 'el gutter, definido una vez' );
		$this->assertSame( 1, substr_count( $css, 'padding-inline: var(--evt-espacio)' ), 'y aplicado en un solo sitio' );
		$this->assertStringNotContainsString( 'min-width:', str_replace( ' ', '', $css ) . 'x', 'ningún min-width que desborde a 320 px' );
		$this->assertStringContainsString( ':focus-visible', $css, 'foco visible' );
	}

	// ─── lo que no se carga ────────────────────────────────────────────────

	/**
	 * La ruta que declara el despliegue para la hoja del formulario.
	 *
	 * Qué complemento sirve el formulario de inscripción es de cada despliegue
	 * y no está escrito en el aplicativo: el filtro es la costura por la que
	 * entra, y el test la declara como lo haría quien lo despliegue.
	 */
	private const FORM_PATH = '/plugins/signup-form/css/';

	/**
	 * Declarar esa ruta, igual que haría un despliegue que arrastre el formulario.
	 *
	 * @return void
	 */
	private function con_formulario_declarado(): void {
		add_filter(
			'evt_form_asset_paths',
			static function (): array {
				return array( self::FORM_PATH );
			}
		);
	}

	/**
	 * Se descarta lo que la página no usa: el tema, Content Views y table-sorter.
	 */
	public function test_what_the_page_does_not_use_is_dropped() {
		$evento = $this->un_evento();
		$this->acting_as( 0 );
		$this->go_to( (string) get_permalink( $evento ) );
		$this->con_formulario_declarado();

		$etiqueta = '<link rel="stylesheet" href="x" />'; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- es la etiqueta que se le pasa al filtro, no una hoja que se encole.
		$fuera    = array(
			'https://example.org/wp-content/themes/un-tema/style-static.min.css',
			'https://example.org/wp-content/et-cache/1/late.css',
			'https://example.org/wp-content/plugins/content-views-query-and-display-post-page/public/assets/js/cv.js',
			'https://example.org/wp-content/plugins/pt-content-views-pro/public/assets/css/cvpro.min.css',
			'https://example.org/wp-content/plugins/table-sorter/jquery.tablesorter.min.js',
			'https://example.org/wp-content' . self::FORM_PATH . 'signup-form.css',
		);
		foreach ( $fuera as $src ) {
			$this->assertSame( '', EventView::drop_page_tag( $etiqueta, 'x', $src ), $src );
		}

		$this->assertSame(
			$etiqueta,
			EventView::drop_page_tag( $etiqueta, 'x', 'https://example.org/wp-includes/js/jquery/jquery.min.js' ),
			'lo del núcleo se queda'
		);
	}

	/**
	 * Pero la hoja del formulario se queda cuando la página sí lleva formulario.
	 */
	public function test_the_form_stylesheet_stays_when_there_is_a_form() {
		$evento = $this->un_evento();
		update_post_meta( $evento, EventMetaKeys::SIGNUP_FORM_ID, 42 );
		$this->go_to( (string) get_permalink( $evento ) );
		$this->con_formulario_declarado();

		$etiqueta = '<link rel="stylesheet" href="x" />'; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- es la etiqueta que se le pasa al filtro, no una hoja que se encole.
		$this->assertSame(
			$etiqueta,
			EventView::drop_page_tag( $etiqueta, 'x', 'https://example.org/wp-content' . self::FORM_PATH . 'signup-form.css' )
		);
		// Y el tema sigue fuera: eso no depende de que haya formulario.
		$this->assertSame(
			'',
			EventView::drop_page_tag( $etiqueta, 'x', 'https://example.org/wp-content/themes/un-tema/style-static.min.css' )
		);
	}

	// ─── los eventos legacy ────────────────────────────────────────────────

	/**
	 * Un evento migrado sigue como hoy: su contenido del maquetador, congelado, dentro del tema.
	 *
	 * Es lo más delicado del cambio. Su contenido son shortcodes del tema y su
	 * aspecto lo pinta su hoja: pintarlo con el esqueleto nuevo, sin el tema,
	 * lo dejaría roto (ADR-0008).
	 */
	public function test_a_legacy_event_is_left_to_the_theme() {
		$evento = $this->un_evento();
		update_post_meta( $evento, EventView::LEGACY_META, 1 );
		$this->go_to( (string) get_permalink( $evento ) );

		$this->assertTrue( EventView::is_legacy( $evento ) );
		$this->assertFalse( EventView::takes_over(), 'el tema lo pinta como hoy' );
		$this->assertNull( $this->exit_url( array( EventView::class, 'render' ) ) );

		// Y no se le quita ni una hoja: lo que lo viste es justo eso.
		$etiqueta = '<link rel="stylesheet" href="x" />'; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- es la etiqueta que se le pasa al filtro, no una hoja que se encole.
		$this->assertSame(
			$etiqueta,
			EventView::drop_page_tag( $etiqueta, 'x', 'https://example.org/wp-content/themes/un-tema/style-static.min.css' )
		);

		// Ni se le mete nuestra hoja en la cabecera.
		ob_start();
		EventView::print_stylesheet();
		$this->assertSame( '', (string) ob_get_clean() );
	}

	/**
	 * Y la marca alcanza a las secciones del evento migrado.
	 */
	public function test_the_legacy_mark_reaches_the_sections() {
		$evento  = $this->un_evento();
		$seccion = $this->event_page( $evento, 'programa', array( 'post_title' => 'Programa' ) );
		update_post_meta( $evento, EventView::LEGACY_META, 1 );

		$this->assertTrue( EventView::is_legacy( $seccion ), 'la del evento manda' );

		// Un evento nacido en el aplicativo no la lleva y se pinta entero.
		$nuevo = $this->event( $this->administrator(), array( $this->area( 'Formación' ) ) );
		$this->assertFalse( EventView::is_legacy( $nuevo ) );
	}

	// ─── el botón de gestión ───────────────────────────────────────────────

	/**
	 * A quien organiza un evento histórico no se le esconde el botón.
	 *
	 * Usaba `can_edit()`, que el cierre por «histórico» pone en falso, así que
	 * el botón desaparecía justo de la página pública del evento que esa
	 * persona organizó. El contrato dice lo contrario: no se esconde, y el
	 * taller ya se abre solo en lectura (ADR-0017).
	 */
	public function test_the_manage_button_survives_the_archived_mark() {
		$area   = $this->area( 'Formación del Profesorado' );
		$dueno  = $this->organiser( array( $area ) );
		$evento = $this->event( $dueno, array( $area ) );

		$this->acting_as( $dueno );
		$esperada = Shell::url( 'event', array( EventView::MANAGE_ARG => $evento ) );
		$this->assertSame( $esperada, EventView::model( $evento )['manage_url'] );

		update_post_meta( $evento, EventMetaKeys::ARCHIVED, 1 );
		$this->assertSame(
			$esperada,
			EventView::model( $evento )['manage_url'],
			'marcado como histórico, el botón sigue ahí'
		);

		// Pero de otra área, ni verlo.
		$this->acting_as( $this->organiser( array( $this->area( 'Innovación' ) ) ) );
		$this->assertSame( '', EventView::model( $evento )['manage_url'] );
	}
}
