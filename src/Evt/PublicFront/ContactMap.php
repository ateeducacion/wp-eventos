<?php
/**
 * The map of a contact page: its points, and the library that draws them.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

use Evt\Meta\EventMetaKeys;

/**
 * Los puntos del mapa de una página de contacto (ADR-0046).
 *
 * Cada punto son unas coordenadas, un texto y, si se quiere, un enlace: la
 * sede, el aparcamiento, la parada de guagua. Se guardan como una lista en
 * `evt_contact_points` y se pintan con Leaflet, que llega de jsDelivr con su
 * SRI como toda librería de terceros (ADR-0015), y solo en la página que tiene
 * puntos: las demás no piden nada a nadie.
 *
 * Sin guion, o si el CDN no contesta, los mismos puntos salen en una lista con
 * su enlace a OpenStreetMap: la dirección se sigue encontrando.
 */
final class ContactMap {

	/**
	 * Leaflet, clavada. La misma versión aquí, en las URL y en `package.json`.
	 *
	 * Los hashes se calcularon pidiendo cada fichero al CDN, dos veces:
	 *
	 *   curl -s <url> | openssl dgst -sha384 -binary | openssl base64 -A
	 */
	public const VERSION = '1.9.4';

	/**
	 * The two files of the library, by handle.
	 *
	 * @var array<string, array{url:string, sri:string}>
	 */
	public const VENDOR = array(
		'evt-leaflet'     => array(
			'url' => 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js',
			'sri' => 'sha384-cxOPjt7s7Iz04uaHJceBmS+qpjv2JkIHNVcuOrM+YHwZOmJGBXI00mdUXEq65HTH',
		),
		'evt-leaflet-css' => array(
			'url' => 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css',
			'sri' => 'sha384-sHL9NAb7lN7rfvG5lfHpm643Xkcjzp4jFvuavGOndn6pjVqS6ny56CAt3nsEVT4H',
		),
	);

	/**
	 * Cuántos puntos caben en un mapa. Una sede, su aparcamiento y poco más.
	 */
	public const MAX_POINTS = 20;

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue' ), 20 );
		add_filter( 'script_loader_tag', array( self::class, 'integrity' ), 10, 3 );
		add_filter( 'style_loader_tag', array( self::class, 'integrity' ), 10, 3 );
	}

	/**
	 * The points of a contact page.
	 *
	 * @param int $page_id Contact page.
	 * @return array<int, array{lat:float, lng:float, text:string, url:string}>
	 */
	public static function points( int $page_id ): array {
		return self::clean( (string) get_post_meta( $page_id, EventMetaKeys::CONTACT_POINTS, true ) );
	}

	/**
	 * A clean list of points, from JSON or from an array.
	 *
	 * Lo que no son unas coordenadas válidas se cae: un punto en el mar de
	 * Guinea por un campo vacío es peor que ningún punto.
	 *
	 * @param mixed $valor JSON string or list.
	 * @return array<int, array{lat:float, lng:float, text:string, url:string}>
	 */
	public static function clean( $valor ): array {
		$lista = is_array( $valor ) ? $valor : json_decode( (string) $valor, true );
		$out   = array();
		foreach ( is_array( $lista ) ? $lista : array() as $punto ) {
			if ( ! is_array( $punto ) || ! isset( $punto['lat'], $punto['lng'] ) || ! is_numeric( $punto['lat'] ) || ! is_numeric( $punto['lng'] ) ) {
				continue;
			}
			$lat = (float) $punto['lat'];
			$lng = (float) $punto['lng'];
			if ( $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 || ( 0.0 === $lat && 0.0 === $lng ) ) {
				continue;
			}
			$url   = esc_url_raw( trim( (string) ( $punto['url'] ?? '' ) ), array( 'http', 'https' ) );
			$out[] = array(
				'lat'  => round( $lat, 6 ),
				'lng'  => round( $lng, 6 ),
				'text' => mb_substr( sanitize_text_field( (string) ( $punto['text'] ?? '' ) ), 0, 200 ),
				'url'  => is_string( $url ) ? $url : '',
			);
			if ( count( $out ) >= self::MAX_POINTS ) {
				break;
			}
		}
		return $out;
	}

	/**
	 * Two numbers from what people paste: «28.4636, -16.2518».
	 *
	 * Es lo que copian los mapas de internet al pulsar sobre un sitio: latitud
	 * y longitud separadas por una coma, con punto decimal. También se admite
	 * separarlas con un espacio o un punto y coma, y la coma decimal cuando la
	 * separación es otra cosa.
	 *
	 * @param string $texto What was typed.
	 * @return array{0:float, 1:float}|null Null when it is not two numbers.
	 */
	public static function parse_coordinates( string $texto ): ?array {
		$texto = trim( $texto );
		if ( preg_match( '/^(-?\d{1,3}(?:\.\d+)?)\s*[,;\s]\s*(-?\d{1,3}(?:\.\d+)?)$/', $texto, $m ) ) {
			return array( (float) $m[1], (float) $m[2] );
		}
		// «28,4636; -16,2518»: comas decimales, separadas por punto y coma.
		if ( preg_match( '/^(-?\d{1,3}(?:,\d+)?)\s*;\s*(-?\d{1,3}(?:,\d+)?)$/', $texto, $m ) ) {
			return array( (float) str_replace( ',', '.', $m[1] ), (float) str_replace( ',', '.', $m[2] ) );
		}
		return null;
	}

	/**
	 * The tiles the map is drawn with, and whose they are.
	 *
	 * Por defecto, los de OpenStreetMap, que piden la atribución que va aquí.
	 * Quien despliegue con un servidor de teselas propio lo cambia con el
	 * filtro, sin tocar el código.
	 *
	 * @return array{url:string, attribution:string}
	 */
	public static function tiles(): array {
		/**
		 * Filter the tile layer of the contact maps.
		 *
		 * @param array{url:string, attribution:string} $tiles URL template and attribution HTML.
		 */
		$tiles = (array) apply_filters(
			'evt_map_tiles',
			array(
				'url'         => 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
				'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
			)
		);
		// Una plantilla y no una dirección: `esc_url_raw()` se llevaría las
		// llaves de `{z}/{x}/{y}`. Se comprueba la forma y, si no, nada.
		$url = trim( (string) ( $tiles['url'] ?? '' ) );
		if ( ! preg_match( '#^https://[a-z0-9.-]+(?::\d+)?/[a-z0-9._~{}/@%=&?-]*$#i', $url ) ) {
			$url = '';
		}
		return array(
			'url'         => $url,
			'attribution' => wp_kses( (string) ( $tiles['attribution'] ?? '' ), array( 'a' => array( 'href' => true ) ) ),
		);
	}

	/**
	 * The map and, under it, the same points as a list.
	 *
	 * La lista no es un sobrante: es lo que lee quien no ve el mapa y lo que
	 * queda si el guion no llega.
	 *
	 * @param int $page_id Contact page.
	 * @return string Empty when the page has no points.
	 */
	public static function html( int $page_id ): string {
		$puntos = self::points( $page_id );
		if ( array() === $puntos ) {
			return '';
		}

		$datos = array(
			'points' => $puntos,
			'tiles'  => self::tiles(),
		);

		$lista = '';
		foreach ( $puntos as $punto ) {
			$osm    = sprintf(
				'https://www.openstreetmap.org/?mlat=%1$s&mlon=%2$s#map=17/%1$s/%2$s',
				rawurlencode( (string) $punto['lat'] ),
				rawurlencode( (string) $punto['lng'] )
			);
			$nombre = '' !== $punto['text'] ? $punto['text'] : 'Punto en el mapa';
			$lista .= '<li><a href="' . esc_url( $osm ) . '">' . esc_html( $nombre ) . '</a>';
			if ( '' !== $punto['url'] ) {
				$lista .= ' · <a href="' . esc_url( $punto['url'] ) . '">Más información</a>';
			}
			$lista .= '</li>';
		}

		return '<div class="evt-ev__mapa-caja">'
			. '<div class="evt-ev__mapa" data-evt-mapa="' . esc_attr( (string) wp_json_encode( $datos ) ) . '" role="region" aria-label="Mapa de cómo llegar"></div>'
			. '<ul class="evt-ev__mapa-puntos">' . $lista . '</ul>'
			. '</div>';
	}

	/**
	 * Load Leaflet only on a contact page with points.
	 *
	 * @return void
	 */
	public static function enqueue(): void {
		if ( ! EventView::takes_over() ) {
			return;
		}
		$pagina = (int) get_queried_object_id();
		if ( 'contacto' !== (string) get_post_meta( $pagina, EventMetaKeys::SECTION_TYPE, true ) || array() === self::points( $pagina ) ) {
			return;
		}

		// `null` de versión: la versión ya va en la URL, y el `?ver=` rompería el SRI.
		wp_enqueue_style( 'evt-leaflet-css', self::VENDOR['evt-leaflet-css']['url'], array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_enqueue_script( 'evt-leaflet', self::VENDOR['evt-leaflet']['url'], array(), null, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_add_inline_script( 'evt-leaflet', Assets::contents( 'js/evt-mapa.js' ) );
	}

	/**
	 * Print the integrity of our two files while they still come from the CDN.
	 *
	 * En desarrollo el mu-plugin las reescribe a `node_modules`, y ahí el
	 * hash ni cuadra ni hace falta.
	 *
	 * @param string $tag    The tag WordPress is about to print.
	 * @param string $handle Its handle.
	 * @param string $src    Its URL.
	 * @return string
	 */
	public static function integrity( $tag, $handle, $src ): string {
		if ( ! isset( self::VENDOR[ $handle ] ) || 0 !== strpos( (string) $src, 'https://cdn.jsdelivr.net/' ) ) {
			return (string) $tag;
		}
		$atributos = ' integrity="' . esc_attr( self::VENDOR[ $handle ]['sri'] ) . '" crossorigin="anonymous"';
		return (string) preg_replace( '/^<(script|link)\b/', '<$1' . $atributos, (string) $tag, 1 );
	}
}
