<?php
/**
 * Tests for the named blocks of the body of a public event page.
 *
 * @package Evt
 */

use Evt\Meta\EventMetaKeys;
use Evt\Meta\EventMetaRegistration;
use Evt\PublicFront\Block\ContentBlock;
use Evt\PublicFront\Block\PosterBlock;
use Evt\PublicFront\Block\SectionsBlock;
use Evt\PublicFront\Block\SignupBlock;
use Evt\PublicFront\EventLayout;
use Evt\PublicFront\EventView;

/**
 * Los tres bloques que trae el armazón, cada uno por su cuenta.
 *
 * Son lo que hasta ahora pintaba la vista vieja `EventViewHtml`, ya retirada:
 * el contenido de la página, el cartel del evento y la rejilla de tarjetas de
 * sección. Aquí se comprueba lo que dice el contrato de un bloque:
 * que se pinta solo si tiene algo que pintar, que empieza en `<h2>` y que su
 * aspecto no lleva ni un color a fuego.
 */
class Test_Event_Blocks extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Con las páginas del aplicativo y sin nada cacheado del test anterior.
	 */
	public function set_up() {
		parent::set_up();
		EventMetaRegistration::register_meta();
		$this->pages();
		$this->forget_sections();
		$this->bloques_de_serie();
	}

	/**
	 * Dejar los bloques como los deja el arranque, y solo esos.
	 *
	 * Otro test pudo registrar el suyo: los bloques son una lista estática y en
	 * los tests todas las peticiones son el mismo proceso.
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
	 * Vaciar la caché de secciones de la petición.
	 *
	 * @return void
	 */
	private function forget_sections(): void {
		$prop = new ReflectionProperty( EventView::class, 'sections' );
		$prop->setAccessible( true );
		$prop->setValue( null, array() );
	}

	/**
	 * Un evento cualquiera de un área cualquiera.
	 *
	 * @return int
	 */
	private function un_evento(): int {
		return $this->event(
			$this->administrator(),
			array( $this->area( 'Innovación' ) ),
			array(),
			array( 'post_title' => 'Escuelas rurales 2026' )
		);
	}

	// ─── el contenido ──────────────────────────────────────────────────────

	/**
	 * El contenido sale tal cual, y sin dejar hueco cuando no hay.
	 */
	public function test_the_content_block_paints_the_text_and_nothing_else() {
		$m = EventView::model( 0, '<p>Tres días para pensar la escuela rural.</p>' );

		$html = ContentBlock::html( $m );
		$this->assertStringContainsString( 'Tres días para pensar la escuela rural.', $html );
		$this->assertStringNotContainsString( '<div', $html, 'el envoltorio lo pone el armazón, no el bloque' );

		// Una página sin texto no deja hueco. La guarda es del armazón, que se
		// salta todo bloque vacío, y por eso se comprueba desde el armazón.
		$this->assertStringNotContainsString(
			'evt-ev__bloque--contenido',
			EventLayout::body( EventView::model( 0, "\n  \n" ) )
		);
	}

	/**
	 * Y lo que no puede llevar un artículo, no lo lleva.
	 */
	public function test_the_content_block_sanitises_what_it_paints() {
		$html = ContentBlock::html( EventView::model( 0, '<p>Hola</p><script>alert(1)</script>' ) );

		$this->assertStringContainsString( 'Hola', $html );
		$this->assertStringNotContainsString( '<script', $html );
	}

	// ─── el cartel ─────────────────────────────────────────────────────────

	/**
	 * El cartel enlaza al original y se describe aunque nadie escribiera el alt.
	 *
	 * Es la pieza que la gente se descarga y comparte: la miniatura no sirve
	 * para imprimir, así que lo que se ve es la versión reducida y lo que se
	 * abre es el fichero entero.
	 */
	public function test_the_poster_block_links_to_the_full_size_file() {
		$evento  = $this->un_evento();
		$adjunto = self::factory()->attachment->create_upload_object(
			DIR_TESTDATA . '/images/canola.jpg',
			$evento
		);
		update_post_meta( $evento, EventMetaKeys::POSTER_ID, $adjunto );

		$this->acting_as( 0 );
		$m    = EventView::model( $evento );
		$html = PosterBlock::html( $m );

		$this->assertStringContainsString( esc_url( (string) $m['appearance']['poster_full'] ), $html );
		$this->assertStringContainsString( 'loading="lazy"', $html );
		$this->assertStringContainsString(
			'alt="Cartel de Escuelas rurales 2026"',
			$html,
			'sin alt escrito, el que diría una persona'
		);
		$this->assertStringContainsString( 'Pulse el cartel para verlo a tamaño completo.', $html );
	}

	// ─── las secciones ─────────────────────────────────────────────────────

	/**
	 * Las tarjetas conservan los textos por defecto por tipo que ya tenían.
	 *
	 * Son los de siempre, y quien organiza una jornada cuenta con ellos:
	 * crea la sección «Ponentes» y la tarjeta ya dice de qué va sin escribir
	 * nada. El bloque los pinta; quien los decide es `EventView::cards()`.
	 */
	public function test_the_section_cards_keep_their_default_texts() {
		$evento = $this->un_evento();
		$this->event_page( $evento, 'ponentes', array( 'post_title' => 'Ponentes' ) );

		$this->acting_as( 0 );
		$html = SectionsBlock::html( EventView::model( $evento ) );

		$this->assertStringContainsString( 'Conoce los detalles de las personas comunicadoras.', $html );
		$this->assertStringContainsString( 'evt-ev__rejilla', $html );
		$this->assertStringContainsString( '<h3>', $html, 'el título de la tarjeta va por debajo del h2 del bloque' );
	}

	/**
	 * Un tipo sin texto por defecto sale sin párrafo, no con uno vacío.
	 */
	public function test_a_card_without_text_paints_no_empty_paragraph() {
		$evento = $this->un_evento();
		$this->event_page( $evento, EventMetaKeys::SECTION_OTHER, array( 'post_title' => 'Otra cosa' ) );

		$this->acting_as( 0 );
		$html = SectionsBlock::html( EventView::model( $evento ) );

		$this->assertStringContainsString( 'Otra cosa', $html );
		$this->assertStringNotContainsString( '<p></p>', $html );
	}

	/**
	 * Sin secciones publicadas no hay rejilla vacía.
	 */
	public function test_without_sections_there_is_no_empty_grid() {
		$evento = $this->un_evento();
		$this->acting_as( 0 );

		$this->assertSame( '', SectionsBlock::html( EventView::model( $evento ) ) );
	}

	// ─── la costura ────────────────────────────────────────────────────────

	/**
	 * Los tres están registrados en el armazón, en su orden y con su nombre.
	 *
	 * Es la firma con la que se registra cualquier bloque que venga después:
	 * `EventLayout::add_block( NOMBRE, callable( array $m ): string, PRIORIDAD )`.
	 */
	public function test_the_three_blocks_are_wired_into_the_skeleton() {
		$this->assertSame(
			array( ContentBlock::NAME, PosterBlock::NAME, SignupBlock::NAME, SectionsBlock::NAME ),
			array_keys( EventLayout::blocks() )
		);
		$this->assertLessThan( PosterBlock::PRIORITY, ContentBlock::PRIORITY );
		$this->assertLessThan( SignupBlock::PRIORITY, PosterBlock::PRIORITY );
		$this->assertLessThan( SectionsBlock::PRIORITY, SignupBlock::PRIORITY );
	}

	/**
	 * El aspecto de los bloques sale de los tokens: ni un color a fuego.
	 *
	 * Ni en el HTML —los colores no van en un atributo `style`, que solo se
	 * pisa con `!important`— ni en la hoja, donde toda regla de bloque se
	 * escribe con las propiedades personalizadas del armazón.
	 */
	public function test_the_look_of_the_blocks_is_all_tokens() {
		$css = EventView::stylesheet();
		$this->assertNotSame( '', $css );

		$bloques = strstr( $css, '─── el cuerpo: los bloques' );
		$this->assertIsString( $bloques, 'la hoja agrupa el CSS de los bloques' );
		$bloques = strstr( $bloques, '─── el pie institucional', true );
		$this->assertIsString( $bloques );

		$this->assertDoesNotMatchRegularExpression(
			'/:\s*(#[0-9a-fA-F]{3,8}|rgba?\()/',
			$bloques,
			'ni un color a fuego en el CSS de los bloques: todo sale de var(--evt-*)'
		);
		$this->assertStringContainsString( 'var(--evt-radio)', $bloques );
		$this->assertStringContainsString( 'var(--evt-espacio)', $bloques );
	}
}
