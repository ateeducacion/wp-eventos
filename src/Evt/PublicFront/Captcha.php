<?php
/**
 * ALTCHA proof-of-work check for signups made without a session.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

/**
 * La casilla «No soy un robot» de la inscripción pública (ADR-0040).
 *
 * ALTCHA no es un servicio: el navegador resuelve una prueba de trabajo y el
 * servidor la comprueba con una firma HMAC propia, sin cookies ni terceros.
 * Aquí vive el lado del servidor —crear el desafío y comprobar la solución—,
 * escrito a mano con `hash()` y `hash_hmac()` porque el formato es de cinco
 * campos y una librería de Composer no llega a producción por Code Snippets.
 * El componente del navegador viene de jsDelivr con SRI (ADR-0015).
 *
 * Solo se pide a quien no ha iniciado sesión: con sesión ya hay a quién
 * pedirle cuentas.
 */
final class Captcha {

	/**
	 * Form field that carries the solved payload.
	 */
	public const FIELD = 'altcha';

	/**
	 * REST namespace and route that hand out challenges.
	 */
	public const REST_NAMESPACE = 'evt/v1';
	public const REST_ROUTE     = '/altcha';

	/**
	 * Pinned widget build: the component and its Spanish strings.
	 *
	 * Módulos `.js` y no los `.umd.cjs`: jsDelivr sirve los `.cjs` como
	 * `application/node` con `nosniff` y el navegador se niega a ejecutarlos.
	 * Los hashes se calcularon pidiendo cada fichero al CDN, dos veces:
	 *
	 *   curl -s <url> | openssl dgst -sha384 -binary | openssl base64 -A
	 *
	 * La versión está clavada aquí, en las URL y en `package.json`: se suben
	 * las tres juntas o el SRI deja de cuadrar (ADR-0015).
	 */
	public const VERSION = '3.2.3';
	public const VENDOR  = array(
		'evt-altcha'    => array(
			'url' => 'https://cdn.jsdelivr.net/npm/altcha@3.2.3/dist/main/altcha.min.js',
			'sri' => 'sha384-MFz2FEOy9hhUgvaoYC2XcPN85++0YMPRCXPoGFrBDgBnivZCTF/z/hSq7DUtBEqe',
		),
		'evt-altcha-es' => array(
			'url' => 'https://cdn.jsdelivr.net/npm/altcha@3.2.3/dist/i18n/es-es.js',
			'sri' => 'sha384-JV8Gd/8Xtl4a9bi45uorsFEGRNblS/3tx1fXO0XhcuJillj2VR/Ai0wy8IeDDh6U',
		),
	);

	/**
	 * Upper bound of the secret number: the work a browser has to do.
	 *
	 * Con 100 000 un navegador corriente tarda menos de un segundo; un robot
	 * que quiera mandar mil inscripciones tiene que pagarlo mil veces.
	 */
	public const MAX_NUMBER = 100000;

	/**
	 * How long a challenge stays valid, in seconds.
	 *
	 * Lo bastante para rellenar el formulario con calma después de que la
	 * casilla pida el desafío.
	 */
	public const TTL = 1200;

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'rest_api_init', array( self::class, 'register_route' ) );
		add_filter( 'script_loader_tag', array( self::class, 'module_tag' ), 10, 3 );
	}

	/**
	 * Whether anonymous signups have to pass the check.
	 *
	 * @return bool
	 */
	public static function enabled(): bool {
		/**
		 * Filter whether anonymous signups are checked with ALTCHA.
		 *
		 * @param bool $enabled Default true.
		 */
		return (bool) apply_filters( 'evt_altcha_enabled', true );
	}

	/**
	 * The public route the widget asks for a fresh challenge.
	 *
	 * Una ruta y no el desafío escrito en la página: una página pública puede
	 * salir de una caché, y un desafío caducado dentro de ella no se podría
	 * resolver.
	 *
	 * @return void
	 */
	public static function register_route(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'rest_challenge' ),
				// Pública a propósito: entrega un desafío firmado, no lee nada.
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * REST callback: a new challenge, never cached.
	 *
	 * @return \WP_REST_Response
	 */
	public static function rest_challenge(): \WP_REST_Response {
		$respuesta = new \WP_REST_Response( self::challenge() );
		$respuesta->header( 'Cache-Control', 'no-store, max-age=0' );
		return $respuesta;
	}

	/**
	 * A new signed challenge, in the ALTCHA v1 format.
	 *
	 * @return array{algorithm:string, challenge:string, maxnumber:int, salt:string, signature:string}
	 */
	public static function challenge(): array {
		$salt      = bin2hex( random_bytes( 12 ) ) . '?expires=' . ( time() + self::TTL );
		$challenge = hash( 'sha256', $salt . random_int( 0, self::MAX_NUMBER ) );

		return array(
			'algorithm' => 'SHA-256',
			'challenge' => $challenge,
			'maxnumber' => self::MAX_NUMBER,
			'salt'      => $salt,
			'signature' => self::sign( $challenge ),
		);
	}

	/**
	 * Whether a submitted payload solves one of our challenges, once.
	 *
	 * Tiene que ser nuestro (la firma), estar resuelto (el hash), no haber
	 * caducado y no haberse usado antes: sin lo último, una misma solución
	 * valdría para mandar inscripciones en serie hasta que caducara.
	 *
	 * @param string $payload Base64 JSON sent by the widget.
	 * @return bool
	 */
	public static function verify( string $payload ): bool {
		$datos = json_decode( (string) base64_decode( $payload, true ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- el formato de ALTCHA es JSON en Base64.
		if ( ! is_array( $datos ) ) {
			return false;
		}
		foreach ( array( 'algorithm', 'challenge', 'number', 'salt', 'signature' ) as $clave ) {
			if ( ! isset( $datos[ $clave ] ) || ! is_scalar( $datos[ $clave ] ) ) {
				return false;
			}
		}

		$salt      = (string) $datos['salt'];
		$challenge = (string) $datos['challenge'];
		$numero    = (string) $datos['number'];
		if ( 'SHA-256' !== $datos['algorithm'] || ! ctype_digit( $numero ) || self::expires( $salt ) < time() ) {
			return false;
		}
		if ( ! hash_equals( self::sign( $challenge ), (string) $datos['signature'] )
			|| ! hash_equals( hash( 'sha256', $salt . $numero ), $challenge ) ) {
			return false;
		}

		$usado = 'evt_altcha_' . substr( $challenge, 0, 40 );
		if ( false !== get_transient( $usado ) ) {
			return false;
		}
		set_transient( $usado, 1, self::TTL );
		return true;
	}

	/**
	 * The widget, with its scripts enqueued.
	 *
	 * @return string
	 */
	public static function widget(): string {
		foreach ( self::VENDOR as $handle => $vendor ) {
			wp_enqueue_script( $handle, $vendor['url'], array(), self::VERSION, true );
		}
		return sprintf(
			'<p class="evt-campo evt-ins__robot"><altcha-widget name="%1$s" challenge="%2$s" language="es-es"></altcha-widget></p>'
				. '<noscript><p class="evt-aviso evt-aviso--aviso">Para inscribirse sin iniciar sesión hace falta tener JavaScript activado.</p></noscript>',
			esc_attr( self::FIELD ),
			esc_url( rest_url( self::REST_NAMESPACE . self::REST_ROUTE ) )
		);
	}

	/**
	 * Print our vendor scripts as modules, with their integrity.
	 *
	 * El SRI solo va mientras la URL sigue siendo la del CDN: en desarrollo el
	 * mu-plugin la reescribe a `node_modules` y ahí ni cuadra ni hace falta.
	 *
	 * @param string $tag    The tag WordPress is about to print.
	 * @param string $handle Its handle.
	 * @param string $src    Its URL.
	 * @return string
	 */
	public static function module_tag( $tag, $handle, $src ): string {
		if ( ! isset( self::VENDOR[ $handle ] ) ) {
			return (string) $tag;
		}
		$atributos = ' type="module"';
		if ( 0 === strpos( (string) $src, 'https://cdn.jsdelivr.net/' ) ) {
			$atributos .= ' integrity="' . esc_attr( self::VENDOR[ $handle ]['sri'] ) . '" crossorigin="anonymous"';
		}
		return (string) preg_replace( '/^<script(?: type=[\'"]text\/javascript[\'"])?/', '<script' . $atributos, (string) $tag, 1 );
	}

	/**
	 * When a salt stops being valid, 0 when it does not say.
	 *
	 * @param string $salt Salt with its `?expires=` query.
	 * @return int
	 */
	private static function expires( string $salt ): int {
		$pos = strpos( $salt, '?' );
		if ( false === $pos ) {
			return 0;
		}
		parse_str( substr( $salt, $pos + 1 ), $consulta );
		return isset( $consulta['expires'] ) && is_string( $consulta['expires'] ) && ctype_digit( $consulta['expires'] )
			? (int) $consulta['expires']
			: 0;
	}

	/**
	 * HMAC of a challenge with a key only this site knows.
	 *
	 * Sale de las sales de `wp-config.php`: no hay opción que guardar ni que
	 * se pueda leer desde la base de datos.
	 *
	 * @param string $challenge Challenge hash.
	 * @return string
	 */
	private static function sign( string $challenge ): string {
		return hash_hmac( 'sha256', $challenge, hash_hmac( 'sha256', 'evt_altcha', wp_salt( 'nonce' ) ) );
	}
}
