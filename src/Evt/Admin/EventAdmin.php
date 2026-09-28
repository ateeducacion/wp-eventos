<?php
/**
 * Admin list table for events: área scoping and columns.
 *
 * @package Evt
 */

namespace Evt\Admin;

use Evt\Access\EventAccess;
use Evt\Domain\EventState;
use Evt\Meta\EventMetaKeys;
use Evt\PostType\ActivityPostType;
use Evt\PostType\EventPostType;
use Evt\PostType\SpeakerPostType;
use Evt\PublicFront\Shell;
use Evt\Taxonomy\EventTaxonomies;

/**
 * Lo que ve cada área en el escritorio.
 *
 * El acotado de aquí solo esconde filas; quien de verdad cierra la puerta es
 * `EventAccess::map_meta_cap()`. Las dos capas hacen falta: sin esta, el
 * listado enseña los eventos de todas las áreas aunque no se puedan abrir.
 */
final class EventAdmin {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'pre_get_posts', array( self::class, 'scope_admin_query' ) );
		add_filter( 'manage_' . EventPostType::POST_TYPE . '_posts_columns', array( self::class, 'columns' ) );
		add_action( 'manage_' . EventPostType::POST_TYPE . '_posts_custom_column', array( self::class, 'column_content' ), 10, 2 );
		add_action( 'admin_menu', array( self::class, 'add_new_submenus' ), 20 );
		add_action( 'admin_menu', array( self::class, 'hide_desk_menu' ), 999 );
		add_action( 'admin_bar_menu', array( self::class, 'hide_desk_new_items' ), 999 );
		add_action( 'current_screen', array( self::class, 'send_to_the_app' ) );
	}

	/**
	 * Whether this person works with events from the WordPress desk.
	 *
	 * Solo administración. Quien organiza un evento lo gestiona entero desde
	 * el aplicativo —secciones, ponentes, actividades, inscripciones—, y el
	 * escritorio es donde se guardan esos datos, no donde se trabaja con ellos.
	 * Enseñárselo es invitar a tocar a mano lo que el aplicativo ordena.
	 *
	 * Esto **no protege nada**: esconde. Lo que se puede editar lo sigue
	 * decidiendo `EventAccess::map_meta_cap()`, se entre por donde se entre.
	 *
	 * @param int $user_id User ID, or current user.
	 * @return bool
	 */
	public static function uses_the_desk( int $user_id = 0 ): bool {
		return EventAccess::is_manager( $user_id );
	}

	/**
	 * Take the «Eventos» menu, and what hangs from it, off the desk of whoever does not administer.
	 *
	 * @return void
	 */
	public static function hide_desk_menu(): void {
		if ( ! self::uses_the_desk() ) {
			remove_menu_page( 'edit.php?post_type=' . EventPostType::POST_TYPE );
		}
	}

	/**
	 * Take «Evento», «Ponente» and «Actividad» off the «+ Nuevo» of the toolbar.
	 *
	 * @param \WP_Admin_Bar $barra Toolbar.
	 * @return void
	 */
	public static function hide_desk_new_items( $barra ): void {
		if ( ! ( $barra instanceof \WP_Admin_Bar ) || self::uses_the_desk() ) {
			return;
		}
		foreach ( self::desk_post_types() as $tipo ) {
			$barra->remove_node( 'new-' . $tipo );
		}
	}

	/**
	 * Send whoever does not administer from the desk screens of events to the app.
	 *
	 * Sin el menú, a esas pantallas solo se llega con un enlace guardado o
	 * tecleando la dirección. Se manda a «Mis eventos», que es donde está lo
	 * mismo, en vez de dejar una pantalla que ya no sale en ningún menú.
	 *
	 * @param \WP_Screen $pantalla Current screen.
	 * @return void
	 */
	public static function send_to_the_app( $pantalla ): void {
		if ( ! ( $pantalla instanceof \WP_Screen ) || self::uses_the_desk() ) {
			return;
		}
		if ( ! in_array( $pantalla->base, array( 'edit', 'post' ), true ) || ! in_array( $pantalla->post_type, self::desk_post_types(), true ) ) {
			return;
		}
		$destino = Shell::url( 'events' );
		Shell::leave( '' !== $destino ? $destino : home_url( '/' ) );
	}

	/**
	 * The post types whose desk screens only the administration uses.
	 *
	 * @return array<int, string>
	 */
	public static function desk_post_types(): array {
		return array( EventPostType::POST_TYPE, SpeakerPostType::POST_TYPE, ActivityPostType::POST_TYPE );
	}

	/**
	 * Post types that hang off the events menu instead of having their own.
	 *
	 * @return array<int, string>
	 */
	public static function submenu_post_types(): array {
		return array( SpeakerPostType::POST_TYPE, ActivityPostType::POST_TYPE );
	}

	/**
	 * Register the «Añadir …» entry of the two CPT that live under Eventos.
	 *
	 * Ponentes y actividades cuelgan del menú de Eventos (`show_in_menu` con
	 * la dirección del padre, no `true`). Para esos, WordPress registra en el
	 * submenú **solo** el listado: `_add_post_type_submenus()` añade
	 * `edit.php?post_type=…` y nada más. Y `post-new.php` comprueba luego que
	 * la pantalla esté en el menú de quien la pide
	 * (`user_can_access_admin_page()`), así que el alta quedaba cerrada para
	 * todo el que no fuera administración: el botón «Añadir ponente» llevaba a
	 * «Lo siento, no tienes permisos para acceder a esta página» aunque las
	 * capacidades estuvieran todas concedidas. Comprobado con `evt_organiser`
	 * en el wp-env del proyecto.
	 *
	 * La capacidad que se exige es la misma que pide el propio WordPress al
	 * entrar (`cap->create_posts`), para no inventar aquí una segunda regla
	 * que mañana no coincida con la de `map_meta_cap`.
	 *
	 * @return void
	 */
	public static function add_new_submenus(): void {
		foreach ( self::submenu_post_types() as $tipo ) {
			$objeto = get_post_type_object( $tipo );
			if ( null === $objeto || ! is_string( $objeto->show_in_menu ) ) {
				continue;
			}
			add_submenu_page(
				$objeto->show_in_menu,
				(string) $objeto->labels->add_new_item,
				(string) $objeto->labels->add_new_item,
				(string) $objeto->cap->create_posts,
				'post-new.php?post_type=' . $tipo
			);
		}
	}

	/**
	 * Limit the events list to the áreas of whoever is looking.
	 *
	 * @param \WP_Query $query Query.
	 * @return void
	 */
	public static function scope_admin_query( $query ): void {
		if ( ! is_admin() || ! ( $query instanceof \WP_Query ) || ! $query->is_main_query() ) {
			return;
		}
		if ( EventPostType::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}
		if ( EventAccess::can_edit_all_areas() ) {
			return;
		}

		$areas = EventAccess::scope_areas();
		if ( array() === $areas ) {
			// Falla en cerrado: sin área en el perfil, ni una fila. Un listado
			// completo por un campo sin rellenar es como un área lee la de otra.
			$query->set( 'post__in', array( 0 ) );
			return;
		}

		$tax_query   = (array) $query->get( 'tax_query' );
		$tax_query[] = array(
			'taxonomy'         => EventTaxonomies::AREA,
			'field'            => 'term_id',
			'terms'            => $areas,
			'include_children' => false,
		);
		$query->set( 'tax_query', $tax_query );
	}

	/**
	 * Add the área and state columns after the title.
	 *
	 * @param array<string, string> $columns Columns.
	 * @return array<string, string>
	 */
	public static function columns( array $columns ): array {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['evt_area']  = 'Ámbito';
				$new['evt_state'] = 'Estado';
			}
		}
		return $new;
	}

	/**
	 * Render the custom column cells.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public static function column_content( string $column, int $post_id ): void {
		if ( 'evt_area' === $column ) {
			$terms = get_the_terms( EventAccess::root_id( $post_id ), EventTaxonomies::AREA );
			echo is_array( $terms ) && array() !== $terms
				? esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) )
				: '—';
			return;
		}
		if ( 'evt_state' === $column ) {
			$root  = EventAccess::root_id( $post_id );
			$state = EventState::of(
				(string) get_post_meta( $root, EventMetaKeys::START_DATE, true ),
				(string) get_post_meta( $root, EventMetaKeys::END_DATE, true )
			);
			// El cierre se dice también aquí: si no, en el escritorio una fila
			// que no se deja editar no explica por qué.
			echo esc_html(
				EventAccess::is_archived( $post_id )
					? EventState::label( $state ) . ' · Histórico'
					: EventState::label( $state )
			);
		}
	}
}
