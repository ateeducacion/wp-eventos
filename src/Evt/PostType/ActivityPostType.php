<?php
/**
 * Register the evt_activity custom post type.
 *
 * @package Evt
 */

namespace Evt\PostType;

use Evt\Taxonomy\EventTaxonomies;

/**
 * CPT registration for programme activities.
 *
 * Una actividad es lo que ocupa una franja del programa: ponencia, mesa
 * redonda, comunicación o taller. Hoy son entradas de un formulario del
 * sistema anterior.
 */
final class ActivityPostType {

	public const POST_TYPE = 'evt_activity';

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
					'name'               => 'Actividades',
					'singular_name'      => 'Actividad',
					'add_new'            => 'Añadir actividad',
					'add_new_item'       => 'Añadir actividad',
					'edit_item'          => 'Editar actividad',
					'new_item'           => 'Nueva actividad',
					'view_item'          => 'Ver actividad',
					'search_items'       => 'Buscar actividades',
					'not_found'          => 'No se encontraron actividades',
					'not_found_in_trash' => 'No hay actividades en la papelera',
					'menu_name'          => 'Actividades',
				),
				// Sin URL propia: se ven en la parrilla del programa del evento.
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'edit.php?post_type=' . EventPostType::POST_TYPE,
				'supports'        => array( 'title', 'editor', 'author' ),
				'has_archive'     => false,
				// El área es el eje de permisos y aquí también: una actividad lleva
				// su término (o varios, que es reutilizarla) y de ahí sale
				// quién puede tocarla ({@see \Evt\Access\EventAccess}).
				'taxonomies'      => array( EventTaxonomies::AREA ),
				'rewrite'         => false,
				'query_var'       => false,
				'capability_type' => array( 'evt_activity', 'evt_activities' ),
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
		return EventPostType::cap_map( 'evt_activity', 'evt_activities' );
	}
}
