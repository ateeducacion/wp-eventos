<?php
/**
 * The `secciones` block of the public page of an event.
 *
 * @package Evt
 */

namespace Evt\PublicFront\Block;

/**
 * La rejilla de tarjetas de sección de la portada.
 *
 * Solo pinta: no lee la petición, no consulta, no decide. Las
 * tarjetas llegan hechas en `$m['cards']`, con su título, su enlace, su imagen
 * y su texto —el propio de la sección o el que le toca por tipo, que decide
 * {@see \Evt\PublicFront\EventView::cards()}—.
 *
 * La rejilla es un `grid` de `auto-fit`, que mide el CONTENEDOR y no la
 * ventana. Con las `col-lg-3 col-md-4 col-sm-6` de Bootstrap que hay hoy en
 * producción salían cuatro tarjetas de una letra de ancho dentro de un
 * contenedor estrecho, porque las media queries miden el viewport. No se vuelve
 * atrás: el aspecto es el mismo y el comportamiento es el correcto.
 *
 * El evento sin secciones publicadas no pinta una rejilla vacía: devuelve la
 * cadena vacía y el armazón se salta el bloque.
 */
final class SectionsBlock {

	/**
	 * Name of the block; also the CSS modifier of its section.
	 */
	public const NAME = 'secciones';

	/**
	 * Dónde pinta: lo último del cuerpo de la portada.
	 */
	public const PRIORITY = 30;

	/**
	 * Paint the block.
	 *
	 * @param array<string, mixed> $m What EventView::model() decided.
	 * @return string HTML already escaped; empty when there are no sections.
	 */
	public static function html( array $m ): string {
		$cards = (array) $m['cards'];
		if ( array() === $cards ) {
			return '';
		}

		ob_start();
		?>
		<h2 class="screen-reader-text">Secciones de este evento</h2>
		<div class="evt-ev__rejilla">
			<?php foreach ( $cards as $card ) : ?>
				<div id="evt-seccion-<?php echo esc_attr( (string) $card['id'] ); ?>" class="evt-ev__tarjeta">
					<?php if ( '' !== (string) $card['image'] ) : ?>
						<img src="<?php echo esc_url( (string) $card['image'] ); ?>" alt="" loading="lazy" />
					<?php endif; ?>
					<h3>
						<a href="<?php echo esc_url( (string) $card['url'] ); ?>"><?php echo esc_html( (string) $card['title'] ); ?></a>
					</h3>
					<?php if ( '' !== trim( (string) $card['text'] ) ) : ?>
						<p><?php echo esc_html( wp_strip_all_tags( (string) $card['text'] ) ); ?></p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
