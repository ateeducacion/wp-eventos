<?php
/**
 * Tests for the custom CSS and JavaScript printed on the public event pages.
 *
 * @package Evt
 */

use Evt\Meta\EventMetaKeys;
use Evt\Meta\EventMetaRegistration;
use Evt\PublicFront\CodeEditor;
use Evt\PublicFront\CustomCode;
use Evt\PublicFront\EventView;
use Evt\PublicFront\Shell;

/**
 * El código a medida: cómo se limpia, cómo se guarda y dónde sale.
 *
 * Tres cosas que no se pueden dar por hechas y por eso se prueban aquí:
 *
 * 1. Que ni el CSS ni el JavaScript pueden cerrar la etiqueta que los envuelve.
 *    Un `</style` dentro del `<style>` lo cierra y convierte el resto en HTML
 *    que escribió otro; un `</script` hace lo mismo con el `<script>`. Se
 *    prueba con mayúsculas, con espacios y con saltos de línea, que es como se
 *    cuela quien lo intenta a propósito.
 * 2. Que lo que se guarda se guarda **en crudo**. Es código: un `>` convertido
 *    en `&gt;` deja el CSS sin selectores de hijo directo y el JavaScript sin
 *    comparaciones. Aquí no vale `sanitize_text_field()`.
 * 3. Que no sale de las páginas de su evento. Ni en el resto del sitio, ni en
 *    las pantallas del aplicativo.
 */
class Test_Custom_Code extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Con los tipos registrados y las metas declaradas, que el saneado corre
	 * dentro de `update_post_meta()`.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		EventMetaRegistration::register_meta();
	}

	/**
	 * Lo que uno de los dos ganchos imprime ahora mismo.
	 *
	 * @param string $metodo `print_css` o `print_js`.
	 * @return string
	 */
	private function printed( string $metodo ): string {
		ob_start();
		call_user_func( array( CustomCode::class, $metodo ) );
		return (string) ob_get_clean();
	}

	/**
	 * Un evento publicado de un área, con su autoría.
	 *
	 * @return int Post ID.
	 */
	private function un_evento(): int {
		return $this->event( $this->administrator(), array( $this->area( 'Innovación' ) ) );
	}

	// ─── que no se pueda cerrar la etiqueta ────────────────────────────────

	/**
	 * El CSS no puede cerrar su `<style>`, se escriba como se escriba.
	 */
	public function test_the_css_cannot_close_its_own_tag() {
		$intentos = array(
			'en minúsculas'    => 'body{}</style>',
			'en mayúsculas'    => 'body{}</STYLE>',
			'a medias'         => 'body{}</StYlE>',
			'con un espacio'   => 'body{}</ style>',
			'con varios'       => "body{}</\t  style>",
			'con salto'        => "body{}</\nstyle>",
			'sin cerrar'       => 'body{}</style',
			'dentro de un php' => 'body{content:"</style>"}',
			'con la etiqueta'  => '<style>body{}</style>',
		);

		foreach ( $intentos as $como => $malo ) {
			$limpio = EventMetaRegistration::sanitize_custom_css( $malo );
			$this->assertSame( 0, preg_match( '#</\s*style#i', $limpio ), $como . ': ' . $limpio );
		}

		// Y lo mismo una vez impreso: en el documento tiene que haber
		// exactamente un cierre, el de verdad.
		$evento = $this->un_evento();
		update_post_meta( $evento, EventMetaKeys::CUSTOM_CSS, "body{}</STYLE>\n.a{}</ style>" );
		$this->go_to( (string) get_permalink( $evento ) );

		$salida = $this->printed( 'print_css' );
		$this->assertSame( 1, preg_match_all( '#</\s*style#i', $salida ), $salida );
		$this->assertStringContainsString( '<style id="evt-custom-css">', $salida );
	}

	/**
	 * El JavaScript no puede cerrar su `<script>`, y sigue siendo el mismo código.
	 */
	public function test_the_js_cannot_close_its_own_tag() {
		$intentos = array(
			'en minúsculas'   => 'var a = "</script>";',
			'en mayúsculas'   => 'var a = "</SCRIPT>";',
			'a medias'        => 'var a = "</ScRiPt>";',
			'con salto'       => "var a = \"</script\n>\";",
			'con atributo'    => 'var a = "</script foo=1>";',
			'sin cerrar'      => 'var a = "</script";',
			'con la etiqueta' => '<script>alert(1)</script>',
		);

		foreach ( $intentos as $como => $malo ) {
			$limpio = EventMetaRegistration::sanitize_custom_js( $malo );
			$this->assertSame( 0, preg_match( '#</script#i', $limpio ), $como . ': ' . $limpio );
			// Desactivada, no borrada: `'<\/script>'` y `'</script>'` son la
			// misma cadena para JavaScript, así que el código hace lo mismo.
			$this->assertStringContainsString( '<\\/', $limpio, $como );
		}

		$evento = $this->un_evento();
		update_post_meta( $evento, EventMetaKeys::CUSTOM_JS, 'var a = "</SCRIPT>"; var b = "</script>";' );
		$this->go_to( (string) get_permalink( $evento ) );

		$salida = $this->printed( 'print_js' );
		$this->assertSame( 1, preg_match_all( '#</script#i', $salida ), $salida );
		$this->assertStringContainsString( '<script id="evt-custom-js">', $salida );
	}

	// ─── que se guarde en crudo ────────────────────────────────────────────

	/**
	 * El CSS se guarda tal cual se escribió: ni entidades ni etiquetas de más.
	 */
	public function test_the_css_is_stored_raw() {
		$evento = $this->un_evento();
		$css    = ".evt-cabecera > h1 {\n\tcolor: #b0002a;\n\tfont-family: 'Merriweather', serif;\n\tcontent: \"—\";\n}\n"
			. '@media (max-width: 640px) { .evt-cabecera > h1 + p { font-size: 18px } }';

		update_post_meta( $evento, EventMetaKeys::CUSTOM_CSS, wp_slash( $css ) );
		$guardado = (string) get_post_meta( $evento, EventMetaKeys::CUSTOM_CSS, true );

		$this->assertSame( $css, $guardado, 'el CSS se guarda en crudo' );
		$this->assertStringNotContainsString( '&gt;', $guardado, 'el hijo directo sigue siendo >' );
		$this->assertStringNotContainsString( '&#039;', $guardado, 'las comillas no se convierten' );
		$this->assertStringContainsString( '>', $guardado );

		$this->go_to( (string) get_permalink( $evento ) );
		$this->assertStringContainsString( $css, $this->printed( 'print_css' ), 'y sale igual de crudo' );
	}

	/**
	 * El JavaScript se guarda tal cual: comparaciones, barras y acentos incluidos.
	 */
	public function test_the_js_is_stored_raw() {
		$evento = $this->un_evento();
		$js     = "const marca = 'EVT-ok';\n"
			. "if ( 1 < 2 && 3 > 2 ) {\n"
			. "\tconsole.log( 'año ' + marca );\n"
			. "}\n"
			. "document.querySelectorAll( 'h1' ).forEach( ( n ) => { n.dataset.evt = /\\d+/.test( n.textContent ); } );";

		update_post_meta( $evento, EventMetaKeys::CUSTOM_JS, wp_slash( $js ) );
		$guardado = (string) get_post_meta( $evento, EventMetaKeys::CUSTOM_JS, true );

		$this->assertSame( $js, $guardado, 'el JavaScript se guarda en crudo' );
		$this->assertStringNotContainsString( '&gt;', $guardado, 'la función flecha sobrevive' );
		$this->assertStringContainsString( '/\\d+/', $guardado, 'la barra de la expresión regular, también' );

		$this->go_to( (string) get_permalink( $evento ) );
		$this->assertStringContainsString( $js, $this->printed( 'print_js' ), 'y sale igual de crudo' );
	}

	// ─── el orden: primero el evento, después la página ────────────────────

	/**
	 * Lo del evento viste todas sus páginas; lo de la página va después.
	 */
	public function test_the_event_code_comes_first_and_the_page_after() {
		$evento  = $this->un_evento();
		$seccion = $this->event_page( $evento, 'programa' );

		update_post_meta( $evento, EventMetaKeys::CUSTOM_CSS, '.evt{color:red}' );
		update_post_meta( $seccion, EventMetaKeys::CUSTOM_CSS, '.evt{color:blue}' );
		update_post_meta( $evento, EventMetaKeys::CUSTOM_JS, "console.log('evento');" );
		update_post_meta( $seccion, EventMetaKeys::CUSTOM_JS, "console.log('seccion');" );

		$this->assertSame( ".evt{color:red}\n.evt{color:blue}", CustomCode::css( $seccion ), 'el del evento primero' );
		$this->assertSame( "console.log('evento');\nconsole.log('seccion');", CustomCode::js( $seccion ) );

		// En la portada del evento el raíz y la página son el mismo post: no se
		// imprime dos veces.
		$this->assertSame( '.evt{color:red}', CustomCode::css( $evento ) );
		$this->assertSame( "console.log('evento');", CustomCode::js( $evento ) );

		// Una sección sin código propio se queda con el del evento, y una
		// sección con código de un evento sin nada, solo con el suyo.
		$otra = $this->event_page( $evento, 'contacto' );
		$this->assertSame( '.evt{color:red}', CustomCode::css( $otra ) );

		$pelado = $this->event( $this->administrator(), array( $this->area( 'Formación' ) ) );
		$hija   = $this->event_page( $pelado, 'ponentes' );
		update_post_meta( $hija, EventMetaKeys::CUSTOM_CSS, '.solo-hija{}' );
		$this->assertSame( '.solo-hija{}', CustomCode::css( $hija ) );
	}

	// ─── sin código, ni la etiqueta ────────────────────────────────────────

	/**
	 * Sin código guardado no se imprime ni el `<style>` ni el `<script>` vacíos.
	 */
	public function test_nothing_at_all_is_printed_when_there_is_no_code() {
		$evento = $this->un_evento();
		$this->go_to( (string) get_permalink( $evento ) );

		$this->assertSame( '', $this->printed( 'print_css' ) );
		$this->assertSame( '', $this->printed( 'print_js' ) );

		// Con el meta puesto pero en blanco, lo mismo: espacios no son código.
		update_post_meta( $evento, EventMetaKeys::CUSTOM_CSS, "   \n\t" );
		update_post_meta( $evento, EventMetaKeys::CUSTOM_JS, "\n\n" );
		$this->assertSame( '', $this->printed( 'print_css' ) );
		$this->assertSame( '', $this->printed( 'print_js' ) );

		$this->assertSame( '', CustomCode::css( 0 ), 'sin página no hay nada que juntar' );
		$this->assertSame( '', CustomCode::js( 0 ) );
	}

	// ─── nunca fuera de las páginas del evento ─────────────────────────────

	/**
	 * El código no se asoma fuera de las páginas de su evento.
	 */
	public function test_it_never_leaves_the_pages_of_its_event() {
		$this->pages();

		$evento = $this->un_evento();
		update_post_meta( $evento, EventMetaKeys::CUSTOM_CSS, '.evt{color:red}' );
		update_post_meta( $evento, EventMetaKeys::CUSTOM_JS, "console.log('evento');" );

		// En su página, sí.
		$this->go_to( (string) get_permalink( $evento ) );
		$this->assertStringContainsString( '.evt{color:red}', $this->printed( 'print_css' ) );
		$this->assertStringContainsString( 'evento', $this->printed( 'print_js' ) );

		// En una entrada cualquiera del sitio, no.
		$suelta = self::factory()->post->create(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
			)
		);
		$this->go_to( (string) get_permalink( $suelta ) );
		$this->assertSame( '', $this->printed( 'print_css' ) );
		$this->assertSame( '', $this->printed( 'print_js' ) );

		// En una pantalla del aplicativo —una `page` con su shortcode—, tampoco.
		$this->go_to( (string) get_permalink( get_page_by_path( Shell::SLUGS['events'] ) ) );
		$this->assertSame( '', $this->printed( 'print_css' ) );
		$this->assertSame( '', $this->printed( 'print_js' ) );

		// En la portada del sitio, tampoco.
		$this->go_to( home_url( '/' ) );
		$this->assertSame( '', $this->printed( 'print_css' ) );
		$this->assertSame( '', $this->printed( 'print_js' ) );

		// Y el código de un evento no se cuela en otro.
		$ajeno = $this->event( $this->administrator(), array( $this->area( 'Formación' ) ) );
		$this->go_to( (string) get_permalink( $ajeno ) );
		$this->assertSame( '', $this->printed( 'print_css' ), 'el otro evento sale limpio' );
		$this->assertSame( '', $this->printed( 'print_js' ) );
	}

	/**
	 * En la cabecera, el CSS a medida va detrás de la hoja de la vista pública.
	 *
	 * La hoja de la vista pública se escribía dentro del artículo, o sea después
	 * de la cabecera, y con eso `.evt-ev__lema{font-size:1.3rem}` pisaba lo que
	 * la persona hubiera escrito para ese mismo selector: misma especificidad,
	 * gana la última. Medido en el navegador antes de arreglarlo: el
	 * `letter-spacing` a medida se aplicaba y el `font-size` no.
	 */
	public function test_the_custom_css_is_the_last_stylesheet_of_the_head() {
		$evento = $this->un_evento();
		update_post_meta( $evento, EventMetaKeys::CUSTOM_CSS, '.evt-ev-lema{font-size:55px}' );

		EventView::register();
		CustomCode::register();
		$this->go_to( (string) get_permalink( $evento ) );

		ob_start();
		do_action( 'wp_head' );
		$cabecera = (string) ob_get_clean();

		$hoja   = strpos( $cabecera, 'evt-evento-css' );
		$medida = strpos( $cabecera, 'evt-custom-css' );
		$this->assertIsInt( $hoja, 'la hoja de la vista pública sale en la cabecera' );
		$this->assertIsInt( $medida, 'y el CSS a medida también' );
		$this->assertLessThan( $medida, $hoja, 'primero la del aplicativo, después la de la persona' );
		$this->assertLessThan( CustomCode::CSS_PRIORITY, EventView::HEAD_PRIORITY );
	}

	// ─── el campo y su degradación ─────────────────────────────────────────

	/**
	 * El campo de código sale con CodeMirror, y sin él sigue funcionando.
	 *
	 * `wp_enqueue_code_editor()` devuelve `false` a quien ha desactivado el
	 * resaltado en su perfil, y eso no puede dejar la pantalla sin campo: se
	 * pinta el `<textarea>` de siempre, con lo guardado dentro, y se guarda
	 * igual.
	 */
	public function test_the_code_field_degrades_to_a_plain_textarea() {
		$admin = (int) self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->acting_as( $admin );

		$args = array(
			'mode'  => CodeEditor::MODE_CSS,
			'name'  => EventMetaKeys::CUSTOM_CSS,
			'label' => 'CSS del evento',
			'help'  => 'Reglas de estilo.',
			'value' => '.a > .b { color: red }',
		);

		// Con el resaltado puesto: el textarea lleva sus ajustes para que el
		// guion del aplicativo levante CodeMirror encima.
		$this->assertTrue( CodeEditor::enqueue( CodeEditor::MODE_JS ) );
		$con = CodeEditor::field( $args );
		$this->assertStringContainsString( 'data-evt-code="css"', $con );
		$this->assertStringContainsString( 'data-evt-code-settings="', $con );
		$this->assertStringNotContainsString( 'evt-code-plano', $con );

		// Sin él: el mismo campo, sin ajustes, y diciéndolo.
		update_user_meta( $admin, 'syntax_highlighting', 'false' );
		$this->acting_as( $admin );
		$this->assertFalse( CodeEditor::enqueue( CodeEditor::MODE_CSS ) );

		$sin = CodeEditor::field( $args );
		$this->assertStringNotContainsString( 'data-evt-code=', $sin );
		$this->assertStringContainsString( 'evt-code-plano', $sin );
		$this->assertStringContainsString( '<textarea', $sin );
		$this->assertStringContainsString( 'name="' . EventMetaKeys::CUSTOM_CSS . '"', $sin );
		$this->assertStringContainsString( esc_textarea( '.a > .b { color: red }' ), $sin, 'lo guardado sigue dentro' );
		$this->assertStringContainsString( 'Se guarda igual', $sin );

		// Sin nombre no hay campo, y un modo que no existe no encola nada.
		$this->assertSame( '', CodeEditor::field( array( 'mode' => CodeEditor::MODE_CSS ) ) );
		$this->assertFalse( CodeEditor::enqueue( 'perl' ) );
	}

	/**
	 * Los dos ganchos están puestos, y al final de la cabecera y del pie.
	 */
	public function test_the_hooks_are_the_last_ones() {
		CustomCode::register();
		$this->assertSame( CustomCode::CSS_PRIORITY, has_action( 'wp_head', array( CustomCode::class, 'print_css' ) ) );
		$this->assertSame( CustomCode::JS_PRIORITY, has_action( 'wp_footer', array( CustomCode::class, 'print_js' ) ) );
		// Detrás del «CSS adicional» del personalizador (101) y de las hojas
		// encoladas (8): quien va después, gana.
		$this->assertGreaterThan( 101, CustomCode::CSS_PRIORITY );
	}
}
