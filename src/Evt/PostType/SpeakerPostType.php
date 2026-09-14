<?php
/**
 * Register the evt_speaker custom post type.
 *
 * @package Evt
 */

namespace Evt\PostType;

use Evt\Taxonomy\EventTaxonomies;

/**
 * CPT registration for speakers.
 *
 * Es un tipo propio y no una página hija del evento porque la misma persona
 * comunicadora vuelve en varias ediciones: hoy son 483 entradas del formulario
 * 7 repetidas evento a evento.
 */
final class SpeakerPostType {

	public const POST_TYPE = 'evt_speaker';

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'               => 'Ponentes',
					'singular_name'      => 'Ponente',
					'add_new'            => 'Añadir ponente',
					'add_new_item'       => 'Añadir ponente',
					'edit_item'          => 'Editar ponente',
					'new_item'           => 'Nuevo ponente',
					'view_item'          => 'Ver ponente',
					'search_items'       => 'Buscar ponentes',
					'not_found'          => 'No se encontraron ponentes',
					'not_found_in_trash' => 'No hay ponentes en la papelera',
					'menu_name'          => 'Ponentes',
				),
				// Sin URL propia: al público se le enseña dentro de la página de
				// ponentes del evento, no en una ficha suelta.
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'edit.php?post_type=' . EventPostType::POST_TYPE,
				'supports'        => array( 'title', 'editor', 'thumbnail', 'author' ),
				'has_archive'     => false,
				// El área es el eje de permisos y aquí también: un ponente lleva
				// su término (o varios, que es reutilizarlo) y de ahí sale
				// quién puede tocarlo ({@see \Evt\Access\EventAccess}).
				'taxonomies'      => array( EventTaxonomies::AREA ),
				'rewrite'         => false,
				'query_var'       => false,
				'capability_type' => array( 'evt_speaker', 'evt_speakers' ),
				'map_meta_cap'    => true,
				'capabilities'    => self::capabilities(),
				'show_in_rest'    => true,
			)
		);
	}

	/**
	 * Custom capabilities for the CPT.
	 *
	 * @return array<string, string>
	 */
	public static function capabilities(): array {
		return EventPostType::cap_map( 'evt_speaker', 'evt_speakers' );
	}
}
