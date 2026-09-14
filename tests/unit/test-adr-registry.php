<?php
/**
 * Tests for the ADR set and its registry.
 *
 * @package Evt
 */

/**
 * Las decisiones se documentan, y el índice es el único registro de estados.
 *
 * Una ADR nueva que no entra en `registro.md` no la encuentra nadie, y una que
 * enlaza a un fichero que no existe manda a un 404 a quien la lee dentro de un
 * año. Las dos cosas se ven aquí y no en la revisión.
 */
class Test_Adr_Registry extends WP_UnitTestCase {

	/**
	 * Directory holding the ADR.
	 *
	 * @return string
	 */
	private function dir(): string {
		return dirname( __DIR__, 2 ) . '/docs/adr/';
	}

	/**
	 * ADR files, by ID.
	 *
	 * @return array<string, string> ID => file name.
	 */
	private function adrs(): array {
		$out = array();
		foreach ( (array) glob( $this->dir() . 'ADR-*.md' ) as $ruta ) {
			$nombre                         = basename( (string) $ruta );
			$out[ substr( $nombre, 0, 8 ) ] = $nombre;
		}
		ksort( $out );
		return $out;
	}

	/**
	 * Contents of a file under docs/adr/.
	 *
	 * @param string $nombre File name.
	 * @return string
	 */
	private function texto( string $nombre ): string {
		// phpcs:ignore WordPress.WP.AlternativeFunctions -- se lee la documentación del propio repositorio.
		return (string) file_get_contents( $this->dir() . $nombre );
	}

	/**
	 * Toda ADR del directorio está en la tabla del registro, con su enlace.
	 */
	public function test_every_adr_is_in_the_registry() {
		$registro = $this->texto( 'registro.md' );
		$adrs     = $this->adrs();

		$this->assertNotEmpty( $adrs, 'no hay ninguna ADR: algo va mal con la ruta' );
		foreach ( $adrs as $id => $nombre ) {
			$this->assertStringContainsString( '(' . $nombre . ')', $registro, $id . ' no está enlazada en registro.md' );
			$this->assertStringContainsString( '| [' . $id . ']', $registro, $id . ' no tiene fila en la tabla de registro.md' );
		}
	}

	/**
	 * Y al revés: el registro no cita ninguna ADR que no exista.
	 */
	public function test_the_registry_cites_no_missing_adr() {
		preg_match_all( '/\(((?:\.\.\/)*[A-Za-z0-9._\/-]+\.md)\)/', $this->texto( 'registro.md' ), $m );
		foreach ( array_unique( $m[1] ) as $rel ) {
			$this->assertFileExists( $this->dir() . $rel, 'registro.md enlaza a ' . $rel . ', que no existe' );
		}
	}

	/**
	 * Cada ADR abre con su frontmatter completo, y el `id` es el del nombre.
	 */
	public function test_every_adr_has_complete_frontmatter() {
		foreach ( $this->adrs() as $id => $nombre ) {
			$texto = $this->texto( $nombre );
			$this->assertStringStartsWith( "---\n", $texto, $nombre . ' no abre con frontmatter' );

			foreach ( array( 'title:', 'status:', 'date:', 'supersedes:', 'superseded_by:', 'ai_assistance:' ) as $clave ) {
				$this->assertStringContainsString( "\n" . $clave, $texto, $nombre . ' no declara ' . $clave );
			}
			$this->assertStringContainsString( "\nid: " . $id . "\n", $texto, $nombre . ' declara un id que no es el de su nombre' );
			$this->assertMatchesRegularExpression( '/\ndate: \d{4}-\d{2}-\d{2}\n/', $texto, $nombre . ' no lleva fecha AAAA-MM-DD' );
			$this->assertMatchesRegularExpression( '/\n  tool: "[^"]+"\n/', $texto, $nombre . ' no declara la herramienta de IA' );
			$this->assertMatchesRegularExpression( '/\n  model: "[^"]+"\n/', $texto, $nombre . ' no declara el modelo de IA' );
		}
	}

	/**
	 * Ningún enlace relativo de una ADR apunta a un fichero que no está.
	 */
	public function test_no_adr_links_to_a_missing_file() {
		foreach ( $this->adrs() as $nombre ) {
			preg_match_all( '/\]\(((?:\.\.\/)*[A-Za-z0-9._\/-]+\.md)(?:#[^)]*)?\)/', $this->texto( $nombre ), $m );
			foreach ( array_unique( $m[1] ) as $rel ) {
				$this->assertFileExists( $this->dir() . $rel, $nombre . ' enlaza a ' . $rel . ', que no existe' );
			}
		}
	}

	/**
	 * La ADR del estado histórico existe, está aceptada y dice lo que decidió:
	 * que `evt_archived` no es `evt_legacy`.
	 */
	public function test_the_archived_state_decision_is_written_down() {
		$adrs = $this->adrs();
		$this->assertArrayHasKey( 'ADR-0017', $adrs );

		$texto = $this->texto( $adrs['ADR-0017'] );
		$this->assertStringContainsString( "\nstatus: Aceptada\n", $texto );
		$this->assertStringContainsString( 'evt_archived', $texto );
		$this->assertStringContainsString( 'evt_legacy', $texto );
		$this->assertStringContainsString( 'ADR-0012', $texto, 'tiene que decir cómo convive con la política de edición' );
		$this->assertStringContainsString( 'ADR-0008', $texto, 'tiene que enlazar la ADR de la migración' );
	}

	/**
	 * La ADR del bloqueo de edición existe, está aceptada, dice que el bloqueo
	 * es el nativo de WordPress y no esconde lo que se pierde con él.
	 *
	 * Las consecuencias negativas son la mitad que se cae de una ADR cuando
	 * alguien la retoca: aquí se fijan las tres que se decidió contar.
	 */
	public function test_the_edit_lock_decision_is_written_down() {
		$adrs = $this->adrs();
		$this->assertArrayHasKey( 'ADR-0023', $adrs );

		$texto = $this->texto( $adrs['ADR-0023'] );
		$this->assertStringContainsString( "\nstatus: Aceptada\n", $texto );
		$this->assertStringContainsString( '_edit_lock', $texto, 'tiene que decir que el bloqueo es el nativo' );
		$this->assertStringContainsString( 'wp-admin', $texto, 'tiene que decir por qué el nativo: se comparte con el escritorio' );
		$this->assertStringContainsString( 'root_id', $texto, 'tiene que decir de qué es el bloqueo: del evento raíz' );
		$this->assertStringContainsString( '409', $texto, 'tiene que decir con qué responde una escritura sin turno' );

		foreach ( array( 'caduca', 'Tomar posesión', 'baliza' ) as $perdida ) {
			$this->assertStringContainsString( $perdida, $texto, 'la consecuencia negativa «' . $perdida . '» no está contada' );
		}
	}

	/**
	 * La numeración de las ADR no tiene huecos.
	 *
	 * Un hueco no es un problema técnico: es que alguien buscará la ADR-0018
	 * porque otra la cita, no la encontrará, y no sabrá si es que falta o si es
	 * que nunca existió. Los identificadores de este proyecto ni se reutilizan
	 * ni se renombran (ADR-0009), así que la única forma de que la serie se
	 * lea es que esté entera.
	 */
	public function test_the_adr_numbering_has_no_gaps() {
		$numeros = array();
		foreach ( array_keys( $this->adrs() ) as $id ) {
			$numeros[] = (int) substr( (string) $id, 4 );
		}
		sort( $numeros );

		$this->assertNotEmpty( $numeros, 'no hay ninguna ADR: algo va mal con la ruta' );
		$this->assertSame( 1, $numeros[0], 'la serie de ADR no empieza en la 0001' );

		$esperados = range( 1, (int) max( $numeros ) );
		$faltan    = array_diff( $esperados, $numeros );
		$this->assertSame(
			array(),
			array_values( $faltan ),
			'faltan números en la serie de ADR: ' . implode( ', ', array_map( 'strval', $faltan ) )
		);
	}
}
