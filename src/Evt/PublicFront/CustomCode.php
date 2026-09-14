<?php
/**
 * Custom CSS and JavaScript printed on the public page of an event.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

use Evt\Access\EventAccess;
use Evt\Meta\EventMetaKeys;
use Evt\Meta\EventMetaRegistration;
use Evt\PostType\EventPostType;

/**
 * Saca a la página pública el CSS y el JavaScript a medida del evento.
 *
 * Dos claves de meta que viven tanto en el evento raíz como en cada página
 * satélite ({@see EventMetaKeys::CUSTOM_CSS} y {@see EventMetaKeys::CUSTOM_JS}).
 * Lo del evento raíz viste todas sus páginas; lo de una página, solo esa, y va
 * **después** para poder afinarlo. Nada de esto sale fuera de las páginas de su
 * evento: ni en el resto del sitio, ni en el escritorio, ni en las pantallas del
 * aplicativo, que son entradas de tipo `page` con un shortcode y no `evt_event`
 * ({@see Shell::SHORTCODES}).
 *
 * Dónde se enganchan y por qué esas prioridades:
 *
 * - **El CSS, en `wp_head` con prioridad 999.** Tiene que poder pisar al tema, y
 *   quien gana entre reglas de la misma especificidad es la última que se lee.
 *   En la cabecera WordPress imprime las hojas encoladas en `wp_print_styles`
 *   (prioridad 8) y el «CSS adicional» del personalizador en `wp_custom_css_cb`
 *   (prioridad 101); 999 va detrás de los dos y sigue estando en el `<head>`,
 *   que es lo que evita el parpadeo de pintar primero sin estilos.
 * - **El JavaScript, en `wp_footer` con prioridad 999.** Al pie, y el último:
 *   cuando el navegador llega ahí el documento ya está montado —no hace falta
 *   envolver el código en ningún `DOMContentLoaded`, que además le cambiaría el
 *   ámbito a quien lo escribió— y las bibliotecas encoladas para el pie
 *   (`wp_print_footer_scripts`, prioridad 20) ya se han cargado.
 *
 * El saneado no se duplica: es el mismo de
 * {@see EventMetaRegistration::sanitize_custom_css()} y
 * {@see EventMetaRegistration::sanitize_custom_js()}, que corren al guardar y se
 * repiten aquí al imprimir porque son idempotentes y porque una meta escrita
 * antes de que existiera el registro —o a pelo contra la base de datos— no ha
 * pasado por ellos. El CSS sale sin etiquetas y sin poder cerrar su `</style`;
 * el JavaScript **no se escapa**, que es código y escaparlo lo rompe, pero se le
 * desactiva la fuga por `</script`.
 *
 * Quién puede escribir estas dos metas lo decide
 * {@see EventAccess::can_edit_custom_css()} y su hermana, y el `auth_callback`
 * de cada una lo hace cumplir venga el dato por donde venga. Aquí solo se
 * imprime lo que ya está guardado.
 */
final class CustomCode {

	/**
	 * Prioridad en `wp_head`: detrás de las hojas del tema y del personalizador.
	 */
	public const CSS_PRIORITY = 999;

	/**
	 * Prioridad en `wp_footer`: el último, con el documento ya montado.
	 */
	public const JS_PRIORITY = 999;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'wp_head', array( self::class, 'print_css' ), self::CSS_PRIORITY );
		add_action( 'wp_footer', array( self::class, 'print_js' ), self::JS_PRIORITY );
	}

	/**
	 * The custom stylesheet of the event page being viewed.
	 *
	 * @return void
	 */
	public static function print_css(): void {
		$css = self::css( self::current_page_id() );
		if ( '' === $css ) {
			return;
		}
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- es CSS, no texto: escaparlo lo rompería. Sale de sanitize_custom_css(), sin etiquetas y sin poder cerrar la suya.
		printf( "<style id=\"evt-custom-css\">\n%s\n</style>\n", $css );
	}

	/**
	 * The custom script of the event page being viewed.
	 *
	 * @return void
	 */
	public static function print_js(): void {
		$js = self::js( self::current_page_id() );
		if ( '' === $js ) {
			return;
		}
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- es código: sale de sanitize_custom_js(), que solo le desactiva la fuga por `</script`.
		printf( "<script id=\"evt-custom-js\">\n%s\n</script>\n", $js );
	}

	/**
	 * The custom CSS that applies to one page: the event's, then the page's.
	 *
	 * @param int $page_id Event root or satellite page (0 = nothing to print).
	 * @return string Empty when there is none: no se imprime ni la etiqueta.
	 */
	public static function css( int $page_id ): string {
		return self::code(
			$page_id,
			EventMetaKeys::CUSTOM_CSS,
			array( EventMetaRegistration::class, 'sanitize_custom_css' )
		);
	}

	/**
	 * The custom JavaScript that applies to one page, in the same order.
	 *
	 * @param int $page_id Event root or satellite page (0 = nothing to print).
	 * @return string Empty when there is none.
	 */
	public static function js( int $page_id ): string {
		return self::code(
			$page_id,
			EventMetaKeys::CUSTOM_JS,
			array( EventMetaRegistration::class, 'sanitize_custom_js' )
		);
	}

	/**
	 * One meta key gathered from the event root and then from the page.
	 *
	 * @param int      $page_id Page being viewed.
	 * @param string   $key     Meta key.
	 * @param callable $limpia  Its sanitiser, the same one that runs on save.
	 * @return string
	 */
	private static function code( int $page_id, string $key, callable $limpia ): string {
		$trozos = array();
		foreach ( self::chain( $page_id ) as $post_id ) {
			$trozo = trim( (string) call_user_func( $limpia, get_post_meta( $post_id, $key, true ) ) );
			if ( '' !== $trozo ) {
				$trozos[] = $trozo;
			}
		}
		return implode( "\n", $trozos );
	}

	/**
	 * Whose code applies here: the event root first, the page itself after.
	 *
	 * Ese orden es el que deja que una sección afine lo que el evento puso para
	 * todas sus páginas. En la portada del evento los dos son el mismo post y la
	 * lista tiene un solo elemento: el código no se imprime dos veces.
	 *
	 * @param int $page_id Page being viewed.
	 * @return int[]
	 */
	private static function chain( int $page_id ): array {
		if ( $page_id <= 0 ) {
			return array();
		}
		$event_id = EventAccess::root_id( $page_id );
		return $event_id === $page_id ? array( $page_id ) : array( $event_id, $page_id );
	}

	/**
	 * The event page being viewed, and only that.
	 *
	 * `is_singular()` pregunta por la consulta principal, así que una consulta
	 * secundaria de otro complemento no cuela su propio evento; y las pantallas
	 * del aplicativo son entradas de tipo `page`, de modo que su `wp_head()` y su
	 * `wp_footer()` —los imprime {@see Shell::render_standalone()}— salen limpios.
	 *
	 * @return int 0 outside the public page of an event.
	 */
	private static function current_page_id(): int {
		if ( is_admin() || ! is_singular( EventPostType::POST_TYPE ) ) {
			return 0;
		}
		return (int) get_queried_object_id();
	}
}
