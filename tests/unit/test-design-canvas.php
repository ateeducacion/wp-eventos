<?php
/**
 * Tests for the design canvas sources under `.design/`.
 *
 * @package Evt
 */

/**
 * Las fuentes del diseño se versionan; el lienzo sembrado, no.
 *
 * `.design/gestion-de-eventos.html` son 2,5 MB: el editor del lienzo
 * empaquetado con los bocetos dentro. Se regenera desde las fuentes, cada
 * guardado reescribe el fichero entero y no puede acabar en el repositorio.
 * Las fuentes —los `*.dc.html`, `canvas.json`, `_base.css` y `_build.py`— sí,
 * porque son lo único que se lee y se revisa.
 *
 * Eso son dos reglas de `.gitignore` que se contradicen a propósito: una ignora
 * todo `.design/*.html` y la siguiente rescata los `*.dc.html`. Si alguien
 * toca una y no la otra, el fallo no se nota hasta que el repositorio pesa
 * 2,5 MB de más o hasta que el diseño desaparece sin que nadie lo borre. Aquí
 * se nota antes.
 */
class Test_Design_Canvas extends WP_UnitTestCase {

	/**
	 * Repository root.
	 *
	 * @return string
	 */
	private function root(): string {
		return dirname( __DIR__, 2 ) . '/';
	}

	/**
	 * Contents of a file at the repository root.
	 *
	 * @param string $rel Relative path.
	 * @return string
	 */
	private function texto( string $rel ): string {
		// phpcs:ignore WordPress.WP.AlternativeFunctions -- se lee el propio repositorio.
		return (string) file_get_contents( $this->root() . $rel );
	}

	/**
	 * Las dos reglas de `.gitignore` siguen ahí, y en este orden.
	 *
	 * El orden importa: en `.gitignore` manda la última regla que encaja, así
	 * que la excepción tiene que ir DESPUÉS. Si se invierten, los `.dc.html`
	 * quedan ignorados y el diseño se pierde.
	 */
	public function test_the_seeded_canvas_is_ignored_and_the_sources_are_not() {
		$ignore = $this->texto( '.gitignore' );

		$sembrado = strpos( $ignore, '/.design/*.html' );
		$fuentes  = strpos( $ignore, '!/.design/*.dc.html' );

		$this->assertNotFalse( $sembrado, '.gitignore ya no ignora el lienzo sembrado de .design/' );
		$this->assertNotFalse( $fuentes, '.gitignore ya no rescata los .dc.html del diseño' );
		$this->assertLessThan(
			$fuentes,
			$sembrado,
			'la excepción de los .dc.html tiene que ir después de la regla que los ignora'
		);
	}

	/**
	 * Las fuentes del diseño están todas, y el sembrado no se ha colado.
	 */
	public function test_the_design_sources_are_all_there() {
		foreach ( array( 'canvas.json', '_base.css', 'README.md' ) as $fuente ) {
			$this->assertFileExists( $this->root() . '.design/' . $fuente );
		}

		$artboards = (array) glob( $this->root() . '.design/*.dc.html' );
		$this->assertCount( 8, $artboards, 'las ocho pantallas del lienzo tienen que estar' );
	}

	/**
	 * `canvas.json` cita exactamente las pantallas que hay en el directorio.
	 *
	 * Es el fallo típico al re-sembrar: se renombra un boceto y el lienzo se
	 * queda apuntando a un fichero que ya no está, o al revés.
	 */
	public function test_the_canvas_cites_the_artboards_that_exist() {
		$canvas = json_decode( $this->texto( '.design/canvas.json' ), true );
		$this->assertIsArray( $canvas, 'canvas.json no es JSON válido' );
		$this->assertArrayHasKey( 'artboards', $canvas );

		$citados = array();
		foreach ( (array) $canvas['artboards'] as $artboard ) {
			$this->assertArrayHasKey( 'file', (array) $artboard, 'un artboard de canvas.json no dice de qué fichero sale' );
			$citados[] = basename( (string) $artboard['file'] );
			$this->assertFileExists(
				$this->root() . '.design/' . basename( (string) $artboard['file'] ),
				'canvas.json cita un boceto que no existe'
			);
		}

		$presentes = array_map( 'basename', (array) glob( $this->root() . '.design/*.dc.html' ) );
		sort( $citados );
		sort( $presentes );
		$this->assertSame( $presentes, $citados, 'canvas.json y el directorio no listan las mismas pantallas' );
	}
}
