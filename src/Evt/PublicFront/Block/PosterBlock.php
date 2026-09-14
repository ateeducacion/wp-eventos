<?php
/**
 * The `cartel` block of the public page of an event.
 *
 * @package Evt
 */

namespace Evt\PublicFront\Block;

/**
 * El cartel del evento, en su portada y solo ahí.
 *
 * Solo pinta: no lee la petición, no consulta, no decide.
 *
 * Se enseña reducido y enlaza al original: es la pieza que la gente se descarga
 * y comparte, y la miniatura no sirve para imprimir. En cada sección sería el
 * mismo cartel repetido cinco veces, así que se queda en la portada; y el
 * evento que no tiene cartel no deja hueco.
 *
 * El alt: si nadie escribió uno en la biblioteca de medios, «Cartel de <el
 * evento>», que es lo que diría una persona. Vacío nunca: la imagen es
 * información, no adorno.
 */
final class PosterBlock {

	/**
	 * Name of the block; also the CSS modifier of its section.
	 */
	public const NAME = 'cartel';

	/**
	 * Dónde pinta: detrás del texto y delante de las secciones.
	 */
	public const PRIORITY = 20;

	/**
	 * Paint the block.
	 *
	 * @param array<string, mixed> $m What EventView::model() decided.
	 * @return string HTML already escaped; empty when there is no poster.
	 */
	public static function html( array $m ): string {
		$look = (array) $m['appearance'];
		if ( empty( $m['is_root'] ) || '' === (string) $look['poster'] ) {
			return '';
		}

		$alt = (string) $look['poster_alt'];
		if ( '' === $alt ) {
			/* translators: %s: nombre del evento. */
			$alt = sprintf( __( 'Cartel de %s', 'wp-eventos' ), (string) $m['event_title'] );
		}

		ob_start();
		?>
		<figure class="evt-ev__cartel">
			<a href="<?php echo esc_url( (string) $look['poster_full'] ); ?>">
				<img src="<?php echo esc_url( (string) $look['poster'] ); ?>"
					alt="<?php echo esc_attr( $alt ); ?>" loading="lazy" />
			</a>
			<figcaption><?php esc_html_e( 'Pulse el cartel para verlo a tamaño completo.', 'wp-eventos' ); ?></figcaption>
		</figure>
		<?php
		return (string) ob_get_clean();
	}
}
