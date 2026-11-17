<?php
/**
 * What an event that is not published says to whoever cannot see it yet.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

use Evt\Access\EventAccess;
use Evt\PostType\EventPostType;
use Evt\PublicFront\View\EventChrome;

/**
 * Un evento en borrador no es un «no se encontró la página» (ADR-0055).
 *
 * El enlace de un evento que se está preparando circula antes de tiempo: se
 * comparte para revisarlo, o alguien lo encuentra. WordPress contesta con su
 * 404 de siempre, que dice que la página no existe —y sí existe— y no deja
 * entrar a quien la prepara. Aquí, en vez de eso, una página corta que dice
 * que el evento **está en borrador**, que se anunciará cuando se publique, y
 * un enlace de **acceso para responsables**.
 *
 * Con la sesión iniciada y sin permiso para verlo, lo dice también: «ha
 * entrado como X y esa cuenta no puede verlo». Es lo que separa «me falta
 * permiso» de «no he iniciado sesión» sin tener que preguntar a nadie.
 *
 * No dice el título ni nada del evento: un borrador puede llevar lo que
 * todavía no se puede contar. Lo que está en la papelera sigue dando un 404.
 */
final class DraftNotice {

	/**
	 * Statuses of something that is being prepared, not gone.
	 */
	public const STATUSES = array( 'draft', 'pending', 'future', 'private' );

	/**
	 * Before WordPress picks the 404 template, and before the event page takes over.
	 */
	public const PRIORITY = 1;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'template_redirect', array( self::class, 'maybe_show' ), self::PRIORITY );
	}

	/**
	 * Serve the notice instead of the 404 when the request is an event being prepared.
	 *
	 * @return void
	 */
	public static function maybe_show(): void {
		if ( is_admin() || ! is_404() ) {
			return;
		}
		$id = self::requested();
		if ( $id <= 0 || ! self::is_being_prepared( $id ) ) {
			return;
		}

		status_header( 404 );
		nocache_headers();
		Shell::send_header( 'Content-Type: text/html; charset=utf-8' );
		Shell::send_header( 'X-Robots-Tag: noindex' );
		echo self::document( $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		Shell::leave();
	}

	/**
	 * Whether this event or page exists and is still being prepared.
	 *
	 * La página o su evento sin publicar, y ninguno de los dos en la papelera.
	 *
	 * @param int $post_id Event or section.
	 * @return bool
	 */
	public static function is_being_prepared( int $post_id ): bool {
		if ( EventPostType::POST_TYPE !== get_post_type( $post_id ) ) {
			return false;
		}
		$raiz    = EventAccess::root_id( $post_id );
		$estados = array( (string) get_post_status( $post_id ), (string) get_post_status( $raiz ) );
		if ( in_array( 'trash', $estados, true ) ) {
			return false;
		}
		return array() !== array_intersect( $estados, self::STATUSES );
	}

	/**
	 * The event or section the 404 was about, 0 when it is not one.
	 *
	 * Tres formas de pedirlo: por número (`?post_type=evt_event&p=N`, que es
	 * el enlace de un borrador), por la ruta bajo `/evento/` y por la ruta en
	 * la raíz del sitio.
	 *
	 * @return int
	 */
	public static function requested(): int {
		foreach ( array( 'p', 'page_id' ) as $clave ) {
			$id = absint( get_query_var( $clave ) );
			if ( $id > 0 && EventPostType::POST_TYPE === get_post_type( $id ) ) {
				return $id;
			}
		}
		foreach ( array( EventPostType::POST_TYPE, 'pagename', 'name' ) as $clave ) {
			$ruta = trim( (string) get_query_var( $clave ), '/' );
			if ( '' === $ruta ) {
				continue;
			}
			$post = get_page_by_path( $ruta, OBJECT, EventPostType::POST_TYPE );
			if ( $post instanceof \WP_Post ) {
				return (int) $post->ID;
			}
		}
		return 0;
	}

	/**
	 * The whole notice, as its own small document.
	 *
	 * @param int $post_id Event or section.
	 * @return string
	 */
	public static function document( int $post_id ): string {
		$aqui   = self::here( $post_id );
		$pagina = EventAccess::root_id( $post_id ) === $post_id
			|| 'publish' !== get_post_status( EventAccess::root_id( $post_id ) );
		$que    = $pagina ? 'Este evento todavía no se ha publicado' : 'Esta página del evento todavía no se ha publicado';

		$cuerpo = '<p>' . esc_html( $que ) . ' y se encuentra en <strong>modo borrador</strong>.</p>';
		if ( is_user_logged_in() ) {
			$user    = wp_get_current_user();
			$cuerpo .= '<p>' . esc_html(
				sprintf(
					'Ha iniciado sesión como %s, y esa cuenta no puede ver este borrador. Si tiene que prepararlo, pida a la administración del sitio que le asigne el ámbito del evento.',
					'' !== (string) $user->display_name ? $user->display_name : $user->user_login
				)
			) . '</p>';
			$cuerpo .= sprintf(
				'<p><a href="%1$s">Entrar con otra cuenta</a></p>',
				esc_url( wp_logout_url( wp_login_url( $aqui ) ) )
			);
		} else {
			$cuerpo .= '<p>Se informará cuando lo esté, a través de los canales habituales y en la página de inicio de este sitio. '
				. sprintf( '(<a href="%1$s">Acceso para responsables</a>)', esc_url( wp_login_url( $aqui ) ) )
				. '</p>';
		}

		$chrome = EventChrome::chrome();
		$logo   = '' !== (string) $chrome['brand_logo']
			? sprintf(
				'<p class="logo"><img src="%1$s" alt="%2$s" width="300" /></p>',
				esc_url( (string) $chrome['brand_logo'] ),
				esc_attr( (string) $chrome['brand_alt'] )
			)
			: '';

		return '<!doctype html><html lang="es"><head><meta charset="utf-8" />'
			. '<meta name="viewport" content="width=device-width, initial-scale=1" />'
			. '<meta name="robots" content="noindex" />'
			. '<title>' . esc_html( 'Evento en borrador – ' . get_bloginfo( 'name' ) ) . '</title>'
			. '<style>'
			. 'body{background:#f0f0f1;color:#1d2327;font:16px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;margin:0;padding:2rem 1rem}'
			. 'main{background:#fff;max-width:40rem;margin:0 auto;padding:1.5rem 2rem;box-shadow:0 1px 3px rgba(0,0,0,.13)}'
			. '.logo img{max-width:100%;height:auto}a{color:#2271b1}'
			. '</style></head><body><main role="main">'
			. $logo
			. $cuerpo
			. '</main></body></html>';
	}

	/**
	 * The address to come back to after logging in.
	 *
	 * La del propio borrador: la de un evento sin publicar es la de número
	 * ({@see EventPostType::draft_link()}), que no se rompe al cambiarle el
	 * nombre ni al publicarlo.
	 *
	 * @param int $post_id Event or section.
	 * @return string
	 */
	private static function here( int $post_id ): string {
		return (string) get_permalink( $post_id );
	}
}
