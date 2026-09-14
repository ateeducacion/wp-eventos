<?php
/**
 * The whole HTML document of the public page of an event.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

use Evt\Meta\EventMetaKeys;
use Evt\PublicFront\Block\ContentBlock;
use Evt\PublicFront\Block\PosterBlock;
use Evt\PublicFront\Block\SectionsBlock;
use Evt\PublicFront\Block\SignupBlock;
use Evt\PublicFront\View\EventChrome;

/**
 * Monta el documento entero de una página de evento: `<!doctype>` incluido.
 *
 * Antes esto era un envoltorio dentro de `the_content` y el resto lo ponía el
 * tema. Ya no: la página se pinta entera aquí, como hace
 * {@see Shell::render_standalone()} con las pantallas del aplicativo. Lo que el
 * tema daba gratis pasa a ser nuestro y está escrito en la ADR: idioma,
 * `<title>`, descripción, Open Graph, el pie institucional y —lo que no es
 * opcional en un sitio público— el aviso de cookies y la analítica, que salen
 * de {@see EventChrome::consent()} y {@see EventChrome::analytics()}.
 *
 * ## El cuerpo son BLOQUES CON NOMBRE
 *
 * El armazón no sabe qué hay dentro de la página: recorre los bloques
 * registrados y les pide su HTML. Añadir uno nuevo —programa, ponentes,
 * talleres, inscripción— **no obliga a tocar este fichero**:
 *
 * ```php
 * \Evt\PublicFront\EventLayout::add_block(
 *     'programa',                                   // nombre único del bloque
 *     array( ProgramBlock::class, 'html' ),         // callable( array $m ): string
 *     40                                            // orden; menor pinta antes
 * );
 * ```
 *
 * El callable recibe {@see EventView::model()} y devuelve HTML **ya escapado**,
 * o cadena vacía para no pintar nada. Lo que necesite en el modelo lo añade con
 * el filtro `evt_event_model`, que corre al final de `model()`. El armazón
 * envuelve cada bloque en `<section class="evt-ev__bloque evt-ev__bloque--NOMBRE">`
 * y no toca nada más. Los bloques empiezan en `<h2>`: el único `<h1>` de la
 * página es el título de la portada.
 *
 * Cada bloque vive en su propio fichero bajo `PublicFront/Block/`, con el
 * mismo trato que las vistas: solo pinta, y su CSS está en
 * `assets/css/evt-evento.css` escrito con los tokens del armazón.
 *
 * ## El aspecto son TOKENS
 *
 * Todo lo que se ve sale de propiedades personalizadas escritas en un solo
 * sitio ({@see tokens()}) a partir de las metas del evento. El CSS a medida
 * cambia un token y no pelea con la especificidad de nadie. Por eso los colores
 * NO van en atributos `style`: un `style` en línea solo se pisa con
 * `!important`, y eso es justo lo contrario de «fácilmente modificable».
 */
final class EventLayout {

	/**
	 * Blocks that make up the body, by name.
	 *
	 * @var array<string, array{render:callable, priority:int, order:int}>
	 */
	private static $blocks = array();

	/**
	 * How many blocks were registered, to keep ties in registration order.
	 *
	 * @var int
	 */
	private static $seq = 0;

	/**
	 * Register the blocks the skeleton itself ships. Idempotent.
	 *
	 * Tres, y los tres son los de hoy: el contenido de la página, el cartel del
	 * evento y la rejilla de tarjetas de sección. Los demás los registran las
	 * pantallas que los traigan.
	 *
	 * @return void
	 */
	public static function register(): void {
		self::add_block( ContentBlock::NAME, array( ContentBlock::class, 'html' ), ContentBlock::PRIORITY );
		self::add_block( PosterBlock::NAME, array( PosterBlock::class, 'html' ), PosterBlock::PRIORITY );
		self::add_block( SectionsBlock::NAME, array( SectionsBlock::class, 'html' ), SectionsBlock::PRIORITY );
		self::add_block( SignupBlock::NAME, array( SignupBlock::class, 'html' ), SignupBlock::PRIORITY );
	}

	/**
	 * Register one named block of the body.
	 *
	 * @param string   $name     Unique name; also the CSS modifier of its section.
	 * @param callable $render   `function( array $model ): string`, HTML already escaped.
	 * @param int      $priority Lower paints first; ties keep registration order.
	 * @return void
	 */
	public static function add_block( string $name, callable $render, int $priority = 50 ): void {
		$name = sanitize_key( $name );
		if ( '' === $name ) {
			return;
		}
		self::$blocks[ $name ] = array(
			'render'   => $render,
			'priority' => $priority,
			'order'    => isset( self::$blocks[ $name ] ) ? self::$blocks[ $name ]['order'] : ++self::$seq,
		);
	}

	/**
	 * Forget one block.
	 *
	 * @param string $name Block name.
	 * @return void
	 */
	public static function remove_block( string $name ): void {
		unset( self::$blocks[ sanitize_key( $name ) ] );
	}

	/**
	 * The blocks of the body, in the order they are painted.
	 *
	 * @return array<string, array{render:callable, priority:int, order:int}>
	 */
	public static function blocks(): array {
		$bloques = self::$blocks;
		uasort(
			$bloques,
			static function ( array $a, array $b ): int {
				return $a['priority'] === $b['priority']
					? $a['order'] <=> $b['order']
					: $a['priority'] <=> $b['priority'];
			}
		);

		/**
		 * Filter the blocks of the body, already sorted.
		 *
		 * Para quitar uno, reordenarlos o quedarse con unos pocos sin tener que
		 * desregistrar nada.
		 *
		 * @param array<string, array{render:callable, priority:int, order:int}> $bloques Blocks.
		 */
		return (array) apply_filters( 'evt_event_blocks', $bloques );
	}

	/**
	 * The body: every block that has something to say.
	 *
	 * @param array<string, mixed> $m What EventView::model() decided.
	 * @return string
	 */
	public static function body( array $m ): string {
		$html = '';
		foreach ( self::blocks() as $name => $bloque ) {
			$trozo = (string) call_user_func( $bloque['render'], $m );
			if ( '' === trim( $trozo ) ) {
				continue;
			}
			$html .= sprintf(
				'<section class="evt-ev__bloque evt-ev__bloque--%s">%s</section>',
				esc_attr( $name ),
				$trozo
			);
		}
		return $html;
	}

	/**
	 * The whole document, from the doctype to the closing tag.
	 *
	 * @param array<string, mixed> $m What EventView::model() decided.
	 * @return string
	 */
	public static function render( array $m ): string {
		$clases = array( 'evt-ev' );
		if ( '' !== (string) $m['section_type'] ) {
			$clases[] = 'evt-ev--' . sanitize_html_class( (string) $m['section_type'] );
		}

		ob_start();
		?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
		<?php
		// `wp_head()` escribe el `<title>` cuando el tema declara `title-tag`;
		// sin tema que lo declare —o sin tema— lo escribimos nosotros. La
		// canónica, el icono del sitio y las hojas encoladas también salen de
		// ahí, y por eso `wp_head()` sigue estando aunque el documento sea
		// nuestro.
		if ( ! current_theme_supports( 'title-tag' ) ) {
			echo '<title>' . esc_html( wp_get_document_title() ) . "</title>\n";
		}
		echo self::meta( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		wp_head();
		// El aviso de cookies va al final de la cabecera, que es donde lo ponía
		// el tema, y detrás de `wp_head()` a propósito: es de terceros y no
		// tiene que competir con nuestras hojas.
		echo EventChrome::consent(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- URL escapadas dentro.
		?>
</head>
<body <?php body_class( $clases ); ?>>
		<?php
		wp_body_open();
		echo '<a class="evt-ev__saltar visually-hidden-focusable" href="#contenido">Saltar al contenido</a>';
		echo EventChrome::header( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		?>
	<main id="contenido" class="evt-ev__main evt-ev__ancho" tabindex="-1">
		<?php echo self::body( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- cada bloque llega escapado. ?>
	</main>
		<?php
		echo EventChrome::footer(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		wp_footer();
		echo EventChrome::analytics(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido en analytics().
		?>
</body>
</html>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Description and Open Graph: what a link to this page looks like elsewhere.
	 *
	 * Esto lo ponía el tema y ahora es nuestro. Sin `og:image` no hay tarjeta en
	 * ninguna red, y el cartel del evento es exactamente la imagen que hay que
	 * enseñar.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function meta( array $m ): string {
		$etiquetas = array(
			array( 'name', 'description', (string) $m['description'] ),
			array( 'property', 'og:type', 'article' ),
			array( 'property', 'og:site_name', (string) get_bloginfo( 'name' ) ),
			array( 'property', 'og:title', (string) $m['title'] ),
			array( 'property', 'og:description', (string) $m['description'] ),
			array( 'property', 'og:url', (string) $m['page_url'] ),
			array( 'property', 'og:image', (string) $m['image'] ),
			array( 'name', 'twitter:card', '' !== (string) $m['image'] ? 'summary_large_image' : 'summary' ),
		);

		$html = '';
		foreach ( $etiquetas as $etiqueta ) {
			list( $tipo, $clave, $valor ) = $etiqueta;
			if ( '' === $valor ) {
				continue;
			}
			$html .= sprintf(
				"\t<meta %s=\"%s\" content=\"%s\" />\n",
				esc_attr( $tipo ),
				esc_attr( $clave ),
				esc_attr( $valor )
			);
		}
		return $html;
	}

	/**
	 * The appearance of the event, as CSS custom properties, in ONE place.
	 *
	 * Solo se escriben los tokens que el evento cambia: los demás se quedan con
	 * el valor por defecto de `assets/css/evt-evento.css`, que es donde vive la
	 * lista entera y documentada.
	 *
	 * El color del texto pasa por {@see EventChrome::readable_ink()}: el
	 * contraste se comprueba, no se supone.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string A `<style>` element, empty when there is nothing to say.
	 */
	public static function tokens( array $m ): string {
		$look = (array) $m['appearance'];

		$fondo  = (string) $look['bg'];
		$tokens = array(
			'--evt-fondo'       => $fondo,
			'--evt-texto'       => '' !== $fondo ? EventChrome::readable_ink( $fondo, (string) $look['fg'] ) : (string) $look['fg'],
			'--evt-tipo-titulo' => (string) $look['title_font'],
			'--evt-tipo-texto'  => (string) $look['body_font'],
			'--evt-forma'       => EventMetaKeys::SHAPE_CIRCLE === (string) $look['shape'] ? '50%' : '',
		);

		/**
		 * Filter the CSS custom properties of one event.
		 *
		 * El sitio que quiera fijar el ancho, el gutter o el radio por evento lo
		 * hace aquí, y sigue siendo un token y no una regla nueva.
		 *
		 * @param array<string, string> $tokens Custom property => value; empty values are dropped.
		 * @param array<string, mixed>  $m      The model.
		 */
		$tokens = (array) apply_filters( 'evt_event_tokens', $tokens, $m );

		$reglas = '';
		foreach ( $tokens as $propiedad => $valor ) {
			$valor = trim( (string) $valor );
			// Ni llaves ni punto y coma ni cierre de etiqueta: un token es un
			// valor, no un trozo de hoja de estilos.
			if ( '' === $valor || preg_match( '/[{};<>]/', $valor ) ) {
				continue;
			}
			$reglas .= '--' . sanitize_key( ltrim( (string) $propiedad, '-' ) ) . ':' . $valor . ';';
		}

		if ( '' === $reglas ) {
			return '';
		}
		return '<style id="evt-evento-tokens">:root{' . $reglas . '}</style>' . "\n";
	}
}
