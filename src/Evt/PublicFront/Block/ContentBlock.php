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
		return wp_kses( (string) $m['content'], self::allowed() );
	}

	/**
	 * What may come out of the content: the post tags, plus embeds and forms.
	 *
	 * `the_content` convierte una URL de YouTube o Vimeo en un `<iframe>`, y
	 * una página puede llevar un formulario pegado con su shortcode. Con
	 * `wp_kses_post()` a secas los dos desaparecían en silencio. Los
	 * `<script>` siguen fuera.
	 *
	 * @return array<string, array<string, bool>>
	 */
	public static function allowed(): array {
		$tags             = wp_kses_allowed_html( 'post' );
		$comunes          = array(
			'id'    => true,
			'class' => true,
			'name'  => true,
			'style' => true,
		);
		$campo            = $comunes + array(
			'type'         => true,
			'value'        => true,
			'checked'      => true,
			'selected'     => true,
			'disabled'     => true,
			'required'     => true,
			'placeholder'  => true,
			'multiple'     => true,
			'min'          => true,
			'max'          => true,
			'step'         => true,
			'maxlength'    => true,
			'rows'         => true,
			'cols'         => true,
			'aria-label'   => true,
			'autocomplete' => true,
		);
		$tags['iframe']   = array(
			'src'             => true,
			'width'           => true,
			'height'          => true,
			'title'           => true,
			'allow'           => true,
			'allowfullscreen' => true,
			'frameborder'     => true,
			'loading'         => true,
			'referrerpolicy'  => true,
			'class'           => true,
			'style'           => true,
		);
		$tags['form']     = $comunes + array(
			'action'     => true,
			'method'     => true,
			'enctype'    => true,
			'novalidate' => true,
		);
		$tags['input']    = $campo;
		$tags['select']   = $campo;
		$tags['option']   = $campo;
		$tags['textarea'] = $campo;
		$tags['button']   = $campo;
		$tags['label']    = ( $tags['label'] ?? array() ) + array( 'for' => true );
		$tags['fieldset'] = $comunes;
		$tags['legend']   = $comunes;
		return $tags;
	}
}
