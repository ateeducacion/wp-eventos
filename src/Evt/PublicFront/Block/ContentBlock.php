<?php
/**
 * The `contenido` block of the public page of an event.
 *
 * @package Evt
 */

namespace Evt\PublicFront\Block;

/**
 * El texto de la página, tal y como lo escribió quien la redactó.
 *
 * Solo pinta: no lee la petición, no consulta, no decide. El
 * contenido llega en el modelo ya pasado por `the_content`, así que aquí no se
 * vuelve a filtrar: se sanea la salida y se devuelve.
 *
 * Es el bloque que sustituye al `<div class="evt-ev-cuerpo">` de la vista
 * vieja. El envoltorio —`<section class="evt-ev__bloque evt-ev__bloque--contenido">`—
 * lo pone {@see \Evt\PublicFront\EventLayout::body()}, y por eso aquí no hay
 * ningún `<div>` de más: una sección de evento tenía diez niveles de
 * anidamiento antes de llegar al texto y ese era justo el problema.
 *
 * Una página sin texto no deja hueco, y eso no se comprueba aquí:
 * {@see \Evt\PublicFront\EventLayout::body()} se salta todo bloque cuyo HTML
 * llegue vacío, y esa guarda vale para los tres y para los que vengan. Cada
 * bloque repitiéndola sería la misma condición escrita cuatro veces.
 */
final class ContentBlock {

	/**
	 * Name of the block; also the CSS modifier of its section.
	 */
	public const NAME = 'contenido';

	/**
	 * Dónde pinta: lo primero del cuerpo.
	 */
	public const PRIORITY = 10;

	/**
	 * Paint the block.
	 *
	 * @param array<string, mixed> $m What EventView::model() decided.
	 * @return string HTML already escaped; empty when there is nothing to say.
	 */
	public static function html( array $m ): string {
		return wp_kses_post( (string) $m['content'] );
	}
}
