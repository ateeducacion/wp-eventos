<?php
/**
 * Register the three event taxonomies.
 *
 * @package Evt
 */

namespace Evt\Taxonomy;

use Evt\Access\EventAccess;
use Evt\PostType\EventPostType;

/**
 * Las tres taxonomías que sustituyen a la única `convocatoria` de hoy.
 *
 * `convocatoria` mezcla cuatro ejes —área, tipología, curso y estado— en 50
 * términos, y lo único que los separa son cuatro listas de exclusión de IDs
 * mantenidas a mano en el sistema anterior. Aquí cada eje es su propia
 * taxonomía, y el estado ni siquiera es una: lo calcula `EventState`.
 *
 * Las capacidades son las que existen de verdad: `evt_manage_app`, que crea el
 * snippet de roles, y `edit_evt_events`, que sale del CPT. El código heredado
 * exige capacidades que no existen en el sitio, y por eso nadie puede
 * administrar los términos.
 */
final class EventTaxonomies {

	/**
	 * Área, servicio o dirección general que organiza. Es el eje de permisos.
	 */
	public const AREA = 'evt_area';

	/**
	 * Tipología: jornadas, encuentro, congreso, taller.
	 */
	public const TYPE = 'evt_type';

	/**
	 * Curso escolar (2025-2026…).
	 */
	public const COURSE = 'evt_course';

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		register_taxonomy( self::AREA, EventPostType::POST_TYPE, self::args( 'Áreas organizadoras', 'Área organizadora' ) );
		register_taxonomy( self::TYPE, EventPostType::POST_TYPE, self::args( 'Tipologías', 'Tipología' ) );
		register_taxonomy( self::COURSE, EventPostType::POST_TYPE, self::args( 'Cursos escolares', 'Curso escolar' ) );
	}

	/**
	 * Shared taxonomy arguments.
	 *
	 * Las tres son jerárquicas para que salgan como casillas y no como campo
	 * libre: un vocabulario cerrado no se amplía por una errata al teclear.
	 *
	 * @param string $plural   Plural label.
	 * @param string $singular Singular label.
	 * @return array<string, mixed>
	 */
	private static function args( string $plural, string $singular ): array {
		return array(
			'labels'             => array(
				'name'          => $plural,
				'singular_name' => $singular,
				'search_items'  => 'Buscar en ' . $plural,
				'all_items'     => 'Todas: ' . $plural,
				'edit_item'     => 'Editar ' . $singular,
				'update_item'   => 'Actualizar ' . $singular,
				'add_new_item'  => 'Añadir ' . $singular,
				'new_item_name' => 'Nombre de ' . $singular,
				'not_found'     => 'No se encontró ninguna coincidencia',
				'menu_name'     => $plural,
			),
			'public'             => true,
			'hierarchical'       => true,
			'show_admin_column'  => true,
			'show_in_rest'       => true,
			'show_in_quick_edit' => false,
			'capabilities'       => array(
				'manage_terms' => EventAccess::CAP_MANAGE,
				'edit_terms'   => EventAccess::CAP_MANAGE,
				'delete_terms' => EventAccess::CAP_MANAGE,
				'assign_terms' => 'edit_evt_events',
			),
		);
	}
}
