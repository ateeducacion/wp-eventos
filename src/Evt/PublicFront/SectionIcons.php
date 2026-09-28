<?php
/**
 * The drawings of the section icons.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

use Evt\Meta\EventMetaKeys;

/**
 * Los iconos de las secciones: los del menú del evento y los de las tarjetas
 * de la portada que no tienen imagen.
 *
 * Dibujados aquí, en línea y con `currentColor`, para que tomen el color del
 * evento y no dependan de ninguna fuente de iconos: son catorce y caben en una
 * pantalla. Qué icono lleva cada sección se guarda por nombre en
 * `evt_section_icon`; vacío es el de su tipo
 * ({@see EventMetaKeys::default_icon()}).
 */
final class SectionIcons {

	/**
	 * What `wp_kses()` lets through of an icon.
	 *
	 * @var array<string, array<string, bool>>
	 */
	public const KSES = array(
		'svg'  => array(
			'class'       => true,
			'viewbox'     => true,
			'width'       => true,
			'height'      => true,
			'aria-hidden' => true,
			'focusable'   => true,
		),
		'path' => array(
			'fill'      => true,
			'fill-rule' => true,
			'd'         => true,
		),
	);

	/**
	 * One path per icon, on a 24 × 24 grid, filled with the even-odd rule.
	 *
	 * La regla par-impar es la que hace los huecos: un rectángulo dentro de
	 * otro deja un marco, y lo que se dibuja dentro del hueco vuelve a pintarse.
	 *
	 * @var array<string, string>
	 */
	private const PATHS = array(
		'calendar'  => 'M3 5h18v16H3zM5 10h14v9H5zM7 2h2v3H7zM15 2h2v3h-2zM7 12h4v4H7z',
		'people'    => 'M12.5 8a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0zM2 20a7 7 0 0 1 14 0zM19.5 9a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0zM16.5 13.6c3.3-.5 5.5 1.6 5.5 6.4H18c0-2.6-.5-4.7-1.5-6.4z',
		'form'      => 'M5 4h4v2h6V4h4v18H5zM7 8h10v12H7zM9 2h6v4H9zM9 10h6v1.8H9zM9 14h6v1.8H9z',
		'image'     => 'M3 4h18v16H3zM5 6h14v12H5zM6 17l4-5 3 3.5 2-2 3 3.5zM16.5 8a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3z',
		'mail'      => 'M2 5h20v14H2zM4 7h16v10H4zM4 7l8 6 8-6v2.4l-8 6-8-6z',
		'bulb'      => 'M12 2a7 7 0 0 1 4 12.75V17H8v-2.25A7 7 0 0 1 12 2zM9 18.5h6V20H9zM10 21h4v1h-4z',
		'chart'     => 'M4 20v-9h4v9zM10 20V4h4v16zM16 20v-6h4v6z',
		'megaphone' => 'M3 9h4l10-5v16L7 15H3zM6.5 16.5h3l1.5 4.5h-3zM19 9.5a3 3 0 0 1 0 5z',
		'chat'      => 'M3 4h18v12H10l-5 4v-4H3zM5 6h14v8H5zM7.5 9h2v2h-2zM11 9h2v2h-2zM14.5 9h2v2h-2z',
		'play'      => 'M2 4h20v14H2zM4 6h16v10H4zM10 8.5l5 2.5-5 2.5zM8 20h8v1.5H8z',
		'pin'       => 'M12 2a7 7 0 0 1 7 7c0 5-7 13-7 13S5 14 5 9a7 7 0 0 1 7-7zM14.5 9a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z',
		'star'      => 'M12 2.5l2.9 6 6.6.9-4.8 4.6 1.2 6.5L12 17.4l-5.9 3.1 1.2-6.5-4.8-4.6 6.6-.9z',
		'info'      => 'M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0zM11 10h2v7h-2zM11 6.5h2v2h-2z',
		'page'      => 'M6 2h8l5 5v15H6zM8 4h5v4h4v12H8zM10 12h5v1.5h-5zM10 15.5h5V17h-5z',
		// Solo para «Inicio» en el menú: no se elige para una sección.
		'home'      => 'M12 3l9 8h-3v9h-5v-6h-2v6H6v-9H3z',
	);

	/**
	 * The icon of a section: the one chosen, or the one of its type.
	 *
	 * @param int $post_id Section.
	 * @return string Icon slug.
	 */
	public static function of( int $post_id ): string {
		$elegido = EventMetaKeys::in_list( get_post_meta( $post_id, EventMetaKeys::SECTION_ICON, true ), EventMetaKeys::section_icons() );
		if ( '' !== $elegido ) {
			return $elegido;
		}
		return EventMetaKeys::default_icon( (string) get_post_meta( $post_id, EventMetaKeys::SECTION_TYPE, true ) );
	}

	/**
	 * The inline SVG of an icon.
	 *
	 * Siempre decorativo: al lado va el nombre de la sección, que es lo que se
	 * lee.
	 *
	 * @param string $slug  Icon slug.
	 * @param int    $size  Width and height in pixels.
	 * @param string $css_class CSS class of the `<svg>`.
	 * @return string Empty for an unknown icon.
	 */
	public static function svg( string $slug, int $size = 24, string $css_class = '' ): string {
		if ( ! isset( self::PATHS[ $slug ] ) ) {
			return '';
		}
		return '<svg' . ( '' !== $css_class ? ' class="' . esc_attr( $css_class ) . '"' : '' )
			. ' viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" aria-hidden="true" focusable="false">'
			. '<path fill="currentColor" fill-rule="evenodd" d="' . self::PATHS[ $slug ] . '"/></svg>';
	}
}
