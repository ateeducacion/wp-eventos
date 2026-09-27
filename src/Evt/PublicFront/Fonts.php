<?php
/**
 * The typefaces an event page asks for, loaded from jsDelivr.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

use Evt\Meta\EventMetaKeys;

/**
 * Loads the two typefaces of an event: titles and body.
 *
 * Hasta aquí la tipografía se guardaba y se escribía en el token, pero ningún
 * fichero la cargaba: salía solo en el ordenador que la tuviera instalada.
 * Cada familia es un paquete de Fontsource servido por jsDelivr con su SRI,
 * como cualquier otra librería de terceros (ADR-0015), y solo el subconjunto
 * latino, que trae las tildes y la eñe. Versión, URL y `package.json` van a
 * la par; los hashes salen del propio fichero:
 *
 *   openssl dgst -sha384 -binary node_modules/@fontsource/<familia>/latin-400.css | openssl base64 -A
 */
final class Fonts {

	/**
	 * Pinned Fontsource version.
	 */
	public const VERSION = '5.3.0';

	/**
	 * Package of each slug and the SRI of each weight it ships.
	 *
	 * Las familias sin negrita propia solo traen la 400: el navegador la
	 * engruesa, que es lo mismo que pasa hoy.
	 */
	public const FILES = array(
		'open-sans'          => array(
			400 => 'sha384-iCbKvpR5A60/NS//7SB6FzVw0fJ34nxQdHMf36GZO9d2zYjLquC+gb/v2wO1S9sx',
			700 => 'sha384-wlxPc3ysysl4f7PNCFAB7q9aAhL3oOVyKNTmz7mvkb84YWAE774cpURVm3Fk/9HS',
		),
		'lato'               => array(
			400 => 'sha384-EaujSazvWAx+u1yLfcczolJEwfFibbIaUkuF4Bc9WgtVVtSIjTDTdyCHSh33Ld32',
			700 => 'sha384-LiO4ka88Z0/5dvuFGaDYviATzARaBcekjRhb0XIqdU2uOXMwo9BFs33m6bVWfxtZ',
		),
		'montserrat'         => array(
			400 => 'sha384-mn1OBOBK7ZfEdVTUvNRqUMCzfQ5i72/9STsbyKZfo84NCVTFn/QovqL5ozS882ux',
			700 => 'sha384-n+ukm2naeA9fWlaEeVoWDoSikLqyOvCDD5dA1cM8D5sg8m50ePn9tTHD8uCrqGQ3',
		),
		'source-serif-4'     => array(
			400 => 'sha384-Yhk5mLEkzKx29CSpJXzN4aMeXVEwyxBjv8GUrGqzfiYavqiHx6NrHs6jVkV6g0Zm',
			700 => 'sha384-cDIigMi+qIrJ9lZodeX8m/bxGSB6CGe2kzztQzlJmOPV1eAj9NAkvzBroQ4u0l+L',
		),
		'merriweather'       => array(
			400 => 'sha384-2+ULC2JeQ/mR5wT2tOuluI7YBLHqsfRzrpzd82ckQr+xOWUYnyBYBtsKHZZ60r8H',
			700 => 'sha384-kwlpJxNKEX1xllIBM384Oy4fLKrgFKpwTCF9woylWpqnldayzyxdX/uxnS5NeNPW',
		),
		'roboto'             => array(
			400 => 'sha384-PD1suwqU3uRAdjtf2uU7xLqFrAlKnev1il3ZXubKemhqyVRIaT7PVXNBAYeGu3+S',
			700 => 'sha384-C3a4//Qmer1umX7TCPDnkFGQXDRi4tfufFF+VyE0UpefaIrdKAFqsq2p57Bsy1r4',
		),
		'nunito'             => array(
			400 => 'sha384-jreD+rEyhWKSlZIOowvLj1tmy6gxhsvP3aXq5KHxykIr5hdfIYy9Sd0sy+h7uM14',
			700 => 'sha384-+Ygvr/KhFV8xPATC9iKazLXGnQz6R9PA1p3biwWVQ/cG/3tzv5I4n3vaLjWkZrWJ',
		),
		'cantarell'          => array(
			400 => 'sha384-eL6khcPbeTL1tpnGXFs655u0dhYlkDLmjTdBl44Q4xAQ0THyAvIwAcVsm/p1QVCv',
			700 => 'sha384-FG2yRzTFgufBEq4rYMR7+a32jbBLOz/oMJ7NJj+fknNeCQW4p9tOn/hr8UqI1wgw',
		),
		'dosis'              => array(
			400 => 'sha384-I2X7GNUWrqsvfyoj8sTEeHtta6xw2/E6VkP4dAF6xsELaPcCIfn/OBQCJYGKc6WF',
			700 => 'sha384-g7JSNrfftSeWUEx9EGB4t1rwkDBPcrn7X1Hu8HERpXFN3h59LVlVsg49avhDTgPH',
		),
		'oxanium'            => array(
			400 => 'sha384-EpSITMa1tmQZsqG8TwGKkZnJDh4dWw0MSH16kHuppnZOsz/o9t/i4mOAZa4JiVJl',
			700 => 'sha384-LuVs+5uf3I/9fhdRi1hcD4Bppm9QA1TrSz8AK+4RoePpLJ6JGeKOYU6yBvXfu5V5',
		),
		'cormorant-garamond' => array(
			400 => 'sha384-20o9vAMzu8LFmfep2AJ/twrjvl9mhxDeanzRrV7KpAGFXKp/a1QqIk//ttkdXioc',
			700 => 'sha384-q+3zdrb5PTuvZjOORmkjJ0b/m3RMaAIoNM562erkXAcC/aAOHTwP/pR+8WFjxiy/',
		),
		'alata'              => array(
			400 => 'sha384-ap+JGFOrkF2FMNLgopQqYEzQ3+vFLHWSxW8oczvpWnH2k0FEWygEc8eK0pZlFQPq',
		),
		'aboreto'            => array(
			400 => 'sha384-6e5ixGCYwRHVro6bH2FgfnsE7FQBTFoogPEUmXeIhf5uzIgzNXqplyqt6Rn+Ugp6',
		),
		'aldrich'            => array(
			400 => 'sha384-4+BeVf6PFtvHxt7oe5SQglP3XHzsQxZK9+L8ALFif/N2fTD3rr4oR4X+5q5BeT7N',
		),
		'dela-gothic-one'    => array(
			400 => 'sha384-5QQvdRDbvbu/vUDzqxYM9ckKYnBhMqU5LYc02xWeFWKLEUMjStKGPOSMBF5GLkET',
		),
		'fredoka-one'        => array(
			400 => 'sha384-xQIyJ+XxwlqWftTPfwlMXgDLQ9RfqxQXBKL1fNKKBV3z14Nhdr1/toXskgW5ykMI',
		),
		'new-tegomin'        => array(
			400 => 'sha384-zlT32DNBnSj9mP+vL+a3v5njKZJIB89iUPc5Pf/d7M2So78QjMmlL6t/yFFFYF1y',
		),
		'nosifer'            => array(
			400 => 'sha384-PjsTIqCWE24cW2MeP+RbhnFG68fkxd3oMP2yHDW14TxXf0TnOk58FPHqPRxrzLw7',
		),
		'oi'                 => array(
			400 => 'sha384-AjKStabBzNCXIwF2pD9sIn01vtDPX8TzHBbi+S0WLDo2OCTkLr/MGWWm2ays/HpG',
		),
		'patrick-hand'       => array(
			400 => 'sha384-bRv4Og6LOuoVz7IEU6hF9pVk7wTVhKPRODGWOxDww6VELTsApNMantCE1dpI9z8V',
		),
		'patua-one'          => array(
			400 => 'sha384-W2kSA0H/1aSxTNIhBfdCRC39nq0bPdBD1DxMusdkERjX3UXRlcZ8i5sdsJGrp8+n',
		),
	);

	/**
	 * Handle prefix of our font stylesheets.
	 */
	private const HANDLE = 'evt-font-';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'style_loader_tag', array( self::class, 'integrity' ), 10, 3 );
	}

	/**
	 * Package of a stored slug; the first list called Source Serif 4 «source-serif».
	 *
	 * @param string $slug Stored slug.
	 * @return string
	 */
	public static function package( string $slug ): string {
		return 'source-serif' === $slug ? 'source-serif-4' : $slug;
	}

	/**
	 * URL of one weight of one family.
	 *
	 * @param string $slug   Stored slug.
	 * @param int    $weight Weight.
	 * @return string
	 */
	public static function url( string $slug, int $weight ): string {
		return sprintf( 'https://cdn.jsdelivr.net/npm/@fontsource/%s@%s/latin-%d.css', self::package( $slug ), self::VERSION, $weight );
	}

	/**
	 * Enqueue the stylesheets of these typefaces; a system one loads nothing.
	 *
	 * @param string[] $slugs Stored slugs.
	 * @return void
	 */
	public static function enqueue( array $slugs ): void {
		foreach ( array_unique( $slugs ) as $slug ) {
			$paquete = self::package( (string) EventMetaKeys::in_list( $slug, EventMetaKeys::fonts() ) );
			foreach ( self::FILES[ $paquete ] ?? array() as $peso => $sri ) {
				unset( $sri );
				// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- la versión va en la URL, clavada.
				wp_enqueue_style( self::HANDLE . $paquete . '-' . $peso, self::url( $paquete, (int) $peso ), array(), null );
			}
		}
	}

	/**
	 * Add the SRI to our font stylesheets while they still come from the CDN.
	 *
	 * En desarrollo el mu-plugin las reescribe a `node_modules`, y ahí sobra.
	 *
	 * @param string $tag    Tag.
	 * @param string $handle Handle.
	 * @param string $href   URL.
	 * @return string
	 */
	public static function integrity( $tag, $handle, $href ): string {
		$tag = (string) $tag;
		if ( 0 !== strpos( (string) $handle, self::HANDLE ) || 0 !== strpos( (string) $href, 'https://cdn.jsdelivr.net/' ) ) {
			return $tag;
		}
		if ( ! preg_match( '~@fontsource/([a-z0-9-]+)@[^/]+/latin-(\d+)\.css~', (string) $href, $m ) || ! isset( self::FILES[ $m[1] ][ (int) $m[2] ] ) ) {
			return $tag;
		}
		$sri = self::FILES[ $m[1] ][ (int) $m[2] ];
		return (string) preg_replace( '~<link ~', '<link integrity="' . esc_attr( $sri ) . '" crossorigin="anonymous" ', $tag, 1 );
	}
}
