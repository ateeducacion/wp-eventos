<?php
/**
 * Register the evt_registration custom post type.
 *
 * @package Evt
 */

namespace Evt\PostType;

/**
 * CPT registration for signups.
 *
 * Una inscripción cuelga de su evento por `post_parent`, como el ponente y la
 * actividad (ADR-0026): de ahí salen solos el área a la que pertenece, el
 * guardián que la acota y el cierre por histórico.
 *
 * **Se registra cerrado, y eso es la mitad de la decisión** (ADR-0032). Sin
 * escritorio, sin REST, sin búsqueda, sin URL y sin archivo. No es esconder:
 * esconder no protege, y la regla de la casa es que lo que protege es
 * `map_meta_cap`. Es **no abrir**: cada puerta que no se abre es una puerta que
 * no hay que guardar después, y aquí dentro hay datos personales de gente que
 * no trabaja aquí.
 *
 * El `post_title` es la **referencia** de la inscripción, no el nombre de la
 * persona. Es la diferencia entre un dato que sale en cualquier listado que
 * recorra `wp_posts` y uno al que hay que ir a buscar.
 */
final class RegistrationPostType {

	public const POST_TYPE = 'evt_registration';

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'          => 'Inscripciones',
					'singular_name' => 'Inscripción',
					'menu_name'     => 'Inscripciones',
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'title' ),
				'capability_type'     => array( 'evt_registration', 'evt_registrations' ),
				'map_meta_cap'        => true,
				'capabilities'        => self::capabilities(),
				'delete_with_user'    => false,
			)
		);
	}

	/**
	 * Custom capabilities for the CPT.
	 *
	 * @return array<string, string>
	 */
	public static function capabilities(): array {
		return EventPostType::cap_map( 'evt_registration', 'evt_registrations' );
	}
}
