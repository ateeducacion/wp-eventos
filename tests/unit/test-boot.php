<?php
/**
 * Tests for App::boot(): what it hooks and that it only hooks it once.
 *
 * @package Evt
 */

use Evt\Access\EventAccess;
use Evt\Admin\EventAdmin;
use Evt\Admin\Settings;
use Evt\App;
use Evt\Meta\EventMetaRegistration;
use Evt\PostType\ActivityPostType;
use Evt\PostType\EventPostType;
use Evt\PostType\SpeakerPostType;
use Evt\Taxonomy\EventTaxonomies;

/**
 * El arranque del aplicativo.
 *
 * `App::boot()` se llama desde `src/Evt/bootstrap.php` y desde el bundle: si no
 * fuese idempotente, tener los dos activos a la vez engancharía cada módulo dos
 * veces y cada `init` haría el trabajo por duplicado.
 */
class Test_Boot extends WP_UnitTestCase {

	/**
	 * How many callbacks hang off each hook the boot touches, across every priority.
	 *
	 * @return array<string, int>
	 */
	private function enganchadas(): array {
		global $wp_filter;

		$cuenta = array();
		foreach ( array( 'init', 'map_meta_cap', 'pre_get_posts', 'admin_menu' ) as $gancho ) {
			$total = 0;
			if ( isset( $wp_filter[ $gancho ] ) ) {
				foreach ( $wp_filter[ $gancho ]->callbacks as $prioridad ) {
					$total += count( $prioridad );
				}
			}
			$cuenta[ $gancho ] = $total;
		}
		return $cuenta;
	}

	/**
	 * Un segundo arranque no vuelve a enganchar nada.
	 */
	public function test_boot_is_idempotent() {
		$antes = $this->enganchadas();

		App::boot();
		App::boot();

		$this->assertSame( $antes, $this->enganchadas() );
	}

	/**
	 * Cada módulo cuelga de su gancho y en su prioridad: las taxonomías antes
	 * que los tipos, las capacidades después de los tipos, y las metas al final.
	 */
	public function test_boot_wired_every_module() {
		$this->assertSame( 9, has_action( 'init', array( EventTaxonomies::class, 'register' ) ) );
		$this->assertSame( 10, has_action( 'init', array( EventPostType::class, 'register' ) ) );
		$this->assertSame( 10, has_action( 'init', array( SpeakerPostType::class, 'register' ) ) );
		$this->assertSame( 10, has_action( 'init', array( ActivityPostType::class, 'register' ) ) );
		$this->assertSame( 11, has_action( 'init', array( EventPostType::class, 'grant_caps_to_roles' ) ) );
		$this->assertSame( 12, has_action( 'init', array( EventMetaRegistration::class, 'register_meta' ) ) );

		$this->assertSame( 10, has_filter( 'map_meta_cap', array( EventAccess::class, 'map_meta_cap' ) ) );
		$this->assertSame( 10, has_action( 'pre_get_posts', array( EventAdmin::class, 'scope_admin_query' ) ) );
		$this->assertSame( 10, has_action( 'admin_menu', array( Settings::class, 'menu' ) ) );
	}

	/**
	 * Y al terminar `init`, los tres tipos y las tres taxonomías están montados.
	 */
	public function test_boot_registered_the_post_types_and_the_taxonomies() {
		$tipos = array( EventPostType::POST_TYPE, SpeakerPostType::POST_TYPE, ActivityPostType::POST_TYPE );
		foreach ( $tipos as $slug ) {
			$this->assertTrue( post_type_exists( $slug ), $slug );
		}

		$taxonomias = array( EventTaxonomies::AREA, EventTaxonomies::TYPE, EventTaxonomies::COURSE );
		foreach ( $taxonomias as $slug ) {
			$this->assertTrue( taxonomy_exists( $slug ), $slug );
		}
	}
}
