<?php
/**
 * Tests for the map of a contact page.
 *
 * @package Evt
 */

use Evt\Meta\EventMetaKeys;
use Evt\Meta\EventMetaRegistration;
use Evt\PublicFront\ContactMap;

/**
 * Los puntos del mapa de una página de contacto (ADR-0046).
 *
 * Lo que se guarda son coordenadas válidas con su texto y su enlace; lo que se
 * pinta es un mapa y, debajo, la misma lista, que es lo que queda sin guion.
 */
class Test_Contact_Map extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Con las metas registradas.
	 */
	public function set_up() {
		parent::set_up();
		EventMetaRegistration::register_meta();
	}

	/**
	 * Las coordenadas se leen como las copia un mapa de internet.
	 */
	public function test_coordinates_are_read_as_maps_copy_them() {
		$this->assertSame( array( 28.4636, -16.2518 ), ContactMap::parse_coordinates( '28.4636, -16.2518' ) );
		$this->assertSame( array( 28.4636, -16.2518 ), ContactMap::parse_coordinates( ' 28.4636 -16.2518 ' ) );
		$this->assertSame( array( 28.4636, -16.2518 ), ContactMap::parse_coordinates( '28,4636; -16,2518' ), 'coma decimal, separadas por punto y coma' );
		$this->assertNull( ContactMap::parse_coordinates( 'Calle Mayor, 1' ) );
		$this->assertNull( ContactMap::parse_coordinates( '28.4636' ) );
	}

	/**
	 * Lo que no son unas coordenadas válidas se cae, y el enlace solo puede ser web.
	 */
	public function test_only_valid_points_are_kept() {
		$limpios = ContactMap::clean(
			array(
				array(
					'lat'  => '28.4636',
					'lng'  => '-16.2518',
					'text' => '<b>Sede</b>',
					'url'  => 'javascript:alert(1)',
				),
				array(
					'lat' => 95,
					'lng' => 0,
				),
				array(
					'lat' => 0,
					'lng' => 0,
				),
				array( 'text' => 'Sin coordenadas' ),
				'no es un punto',
			)
		);

		$this->assertCount( 1, $limpios );
		$this->assertSame( 28.4636, $limpios[0]['lat'] );
		$this->assertSame( 'Sede', $limpios[0]['text'] );
		$this->assertSame( '', $limpios[0]['url'] );

		$muchos = array_fill(
			0,
			30,
			array(
				'lat' => 28,
				'lng' => -16,
			)
		);
		$this->assertCount( ContactMap::MAX_POINTS, ContactMap::clean( $muchos ) );
	}

	/**
	 * La meta se limpia la escriba quien la escriba.
	 */
	public function test_the_meta_is_cleaned_on_write() {
		$evento   = $this->event( $this->administrator(), array( $this->area() ) );
		$contacto = $this->event_page( $evento, 'contacto' );

		update_post_meta(
			$contacto,
			EventMetaKeys::CONTACT_POINTS,
			wp_slash(
				(string) wp_json_encode(
					array(
						array(
							'lat' => 28.4,
							'lng' => -16.2,
						),
						array(
							'lat' => 'x',
							'lng' => 1,
						),
					)
				)
			)
		);

		$this->assertCount( 1, ContactMap::points( $contacto ) );
	}

	/**
	 * El mapa lleva sus datos y, debajo, la lista que queda sin guion.
	 */
	public function test_the_map_comes_with_a_list_that_works_without_script() {
		$evento   = $this->event( $this->administrator(), array( $this->area() ) );
		$contacto = $this->event_page( $evento, 'contacto' );
		$this->assertSame( '', ContactMap::html( $contacto ), 'sin puntos, sin mapa' );

		update_post_meta(
			$contacto,
			EventMetaKeys::CONTACT_POINTS,
			wp_slash(
				(string) wp_json_encode(
					array(
						array(
							'lat'  => 28.4636,
							'lng'  => -16.2518,
							'text' => 'Sede',
							'url'  => 'https://example.org/sede',
						),
						array(
							'lat' => 28.47,
							'lng' => -16.25,
						),
					)
				)
			)
		);
		$html = ContactMap::html( $contacto );

		$this->assertStringContainsString( 'data-evt-mapa="', $html );
		$this->assertStringContainsString( 'tile.openstreetmap.org', html_entity_decode( $html ), 'las teselas y su atribución viajan con los puntos' );
		$this->assertStringContainsString( 'https://www.openstreetmap.org/?mlat=28.4636&#038;mlon=-16.2518', $html );
		$this->assertStringContainsString( '>Sede</a> · <a href="https://example.org/sede">Más información</a>', $html );
		$this->assertStringContainsString( '>Punto en el mapa</a>', $html, 'un punto sin texto también se lista' );
	}

	/**
	 * Las teselas se cambian con un filtro; solo HTTPS.
	 */
	public function test_the_tiles_can_be_changed_by_whoever_deploys() {
		$propias = static function (): array {
			return array(
				'url'         => 'https://teselas.example.org/{z}/{x}/{y}.png',
				'attribution' => 'Teselas propias <script>x</script>',
			);
		};
		add_filter( 'evt_map_tiles', $propias );
		$tiles = ContactMap::tiles();
		remove_filter( 'evt_map_tiles', $propias );

		$this->assertSame( 'https://teselas.example.org/{z}/{x}/{y}.png', $tiles['url'] );
		$this->assertStringNotContainsString( '<script', $tiles['attribution'] );
	}

	/**
	 * Leaflet lleva su integridad mientras viene del CDN, y ninguna otra cosa se toca.
	 */
	public function test_the_library_carries_its_integrity_from_the_cdn() {
		$js  = ContactMap::VENDOR['evt-leaflet']['url'];
		$tag = ContactMap::integrity( '<script src="' . $js . '"></script>', 'evt-leaflet', $js ); // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- es la etiqueta que filtra, no se imprime.
		$this->assertStringContainsString( 'integrity="' . ContactMap::VENDOR['evt-leaflet']['sri'] . '" crossorigin="anonymous"', $tag );

		$css = ContactMap::VENDOR['evt-leaflet-css']['url'];
		$tag = ContactMap::integrity( "<link rel='stylesheet' href='" . $css . "' />", 'evt-leaflet-css', $css ); // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- es la etiqueta que filtra, no se imprime.
		$this->assertStringStartsWith( '<link integrity=', $tag );

		$local = '/wp-content/evt-dev/node_modules/leaflet/dist/leaflet.js';
		$this->assertSame( '<script></script>', ContactMap::integrity( '<script></script>', 'evt-leaflet', $local ), 'en desarrollo no hay hash que cuadre' );
		$this->assertSame( '<script></script>', ContactMap::integrity( '<script></script>', 'otro', $js ) );
	}

	/**
	 * La versión clavada es la misma en la clase, en las URL y en package.json (ADR-0015).
	 */
	public function test_the_pinned_version_is_the_same_everywhere() {
		// phpcs:ignore WordPress.WP.AlternativeFunctions -- se lee el package.json del propio repositorio.
		$paquete = json_decode( (string) file_get_contents( dirname( __DIR__, 2 ) . '/package.json' ), true );
		$this->assertSame( ContactMap::VERSION, $paquete['devDependencies']['leaflet'] );
		foreach ( ContactMap::VENDOR as $fichero ) {
			$this->assertStringContainsString( '/leaflet@' . ContactMap::VERSION . '/', $fichero['url'] );
		}
	}
}
