<?php
/**
 * Tests that the demo accounts the README promises are the ones provisioning creates.
 *
 * @package Evt
 */

/**
 * Las cuentas de prueba: lo que dice el README y lo que siembra el guion.
 *
 * El README lo dice con todas las letras —«scripts/seed-demo.php es la fuente
 * de verdad de esta tabla»— y aun así las dos cosas se separaron: al retirar el
 * rol de coordinación se quitó la cuenta `coordinacion` del guion y la tabla se
 * quedó prometiéndola. Quien sigue la documentación entra con un usuario que no
 * existe y no sabe si ha roto algo. Eso se ve aquí y no en la revisión.
 */
class Test_Demo_Accounts extends WP_UnitTestCase {

	/**
	 * One file of this repository, as text.
	 *
	 * @param string $rel Path relative to the repository root.
	 * @return string
	 */
	private function source( string $rel ): string {
		// phpcs:ignore WordPress.WP.AlternativeFunctions -- se lee el propio repositorio, no el sistema de ficheros de un sitio.
		return (string) file_get_contents( dirname( __DIR__, 2 ) . $rel );
	}

	/**
	 * Logins listed in the README table of test users.
	 *
	 * @return string[]
	 */
	private function readme_logins(): array {
		$trozo = strstr( $this->source( '/README.md' ), '### Usuarios de prueba' );
		$trozo = is_string( $trozo ) ? substr( $trozo, 0, 1200 ) : '';

		preg_match_all( '/^\|\s*`([a-z0-9]+)`\s*\|/m', $trozo, $filas );
		$logins = array_values( array_diff( $filas[1], array( 'admin' ) ) );
		sort( $logins );
		return $logins;
	}

	/**
	 * The seeding script, as text.
	 *
	 * @return string
	 */
	private function accounts_block(): string {
		return $this->source( '/scripts/seed-demo.php' );
	}

	/**
	 * Logins the seeding script defines.
	 *
	 * `seed-demo.php` no se puede incluir: al final se ejecuta y crearía los
	 * eventos de demostración dentro del test. Se lee su fuente, que es donde
	 * está el dato.
	 *
	 * @return string[]
	 */
	private function seeded_logins(): array {
		$trozo = strstr( $this->accounts_block(), 'function evt_demo_accounts' );
		$trozo = is_string( $trozo ) ? substr( $trozo, 0, 1200 ) : '';

		preg_match_all( "/^\t\t'([a-z0-9]+)'\s*=>\s*array\(/m", $trozo, $filas );
		$logins = $filas[1];
		sort( $logins );
		return $logins;
	}

	/**
	 * La tabla del README y el guion nombran exactamente las mismas cuentas.
	 */
	public function test_the_readme_table_and_the_seeding_script_agree() {
		$readme = $this->readme_logins();

		$this->assertNotEmpty( $readme, 'la tabla de usuarios de prueba del README se lee' );
		$this->assertSame(
			$readme,
			$this->seeded_logins(),
			'la tabla del README promete cuentas que scripts/seed-demo.php no crea, o al revés'
		);
	}

	/**
	 * Sigue habiendo una cuenta con dos áreas: es el único caso que enseña el
	 * filtro por área del listado, y se perdió una vez al retirar el rol de
	 * coordinación.
	 */
	public function test_one_demo_account_belongs_to_two_areas() {
		$trozo = strstr( $this->accounts_block(), 'function evt_demo_accounts' );
		$trozo = is_string( $trozo ) ? substr( $trozo, 0, 1200 ) : '';

		preg_match_all( "/'areas'\s*=>\s*array\(([^)]*)\)/", $trozo, $areas );
		$cuantas = array_map(
			static function ( $lista ) {
				return count( array_filter( array_map( 'trim', explode( ',', (string) $lista ) ) ) );
			},
			$areas[1]
		);

		$this->assertNotEmpty( $cuantas, 'las cuentas declaran sus áreas' );
		$this->assertContains( 2, $cuantas, 'ninguna cuenta de demostración pertenece a dos áreas' );
		$this->assertContains( 1, $cuantas, 'ninguna cuenta de demostración está acotada a una sola área' );
	}
}
