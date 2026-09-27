<?php
/**
 * The `logos` block of the public page of an event.
 *
 * @package Evt
 */

namespace Evt\PublicFront\Block;

use Evt\Meta\EventMetaKeys;

/**
 * Los logos corporativos del evento, abajo del todo en su portada.
 *
 * Como siempre: una fila de logos, cada uno con su enlace si lo tiene, después
 * de todo lo demás y antes del pie. Solo en la portada, igual que el cartel.
 *
 * El alt, el de la biblioteca de medios; si nadie lo escribió, «Logo»: vacío no,
 * porque un logo enlazado sin texto es un enlace sin nombre.
 */
final class SponsorsBlock {

	/**
	 * Name of the block; also the CSS modifier of its section.
	 */
	public const NAME = 'logos';

	/**
	 * Detrás de todo: de las secciones y de las personas comunicadoras.
	 */
	public const PRIORITY = 90;

	/**
	 * Paint the block.
	 *
	 * @param array<string, mixed> $m What EventView::model() decided.
	 * @return string HTML already escaped; empty when there are no logos.
	 */
	public static function html( array $m ): string {
		if ( empty( $m['is_root'] ) ) {
			return '';
		}
		$logos = json_decode( (string) get_post_meta( (int) $m['event_id'], EventMetaKeys::SPONSORS, true ), true );

		$piezas = '';
		foreach ( is_array( $logos ) ? $logos : array() as $logo ) {
			$id  = absint( $logo['id'] ?? 0 );
			$src = $id > 0 ? (string) wp_get_attachment_image_url( $id, 'medium' ) : '';
			if ( '' === $src ) {
				continue;
			}
			$alt     = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
			$imagen  = '<img src="' . esc_url( $src ) . '" alt="' . esc_attr( '' !== $alt ? $alt : 'Logo' ) . '" loading="lazy" />';
			$url     = (string) ( $logo['url'] ?? '' );
			$piezas .= '<li>' . ( '' !== $url ? '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . $imagen . '</a>' : $imagen ) . '</li>';
		}

		return '' !== $piezas ? '<ul class="evt-ev__logos">' . $piezas . '</ul>' : '';
	}
}
