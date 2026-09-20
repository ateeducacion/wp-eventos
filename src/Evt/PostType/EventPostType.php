<?php
/**
 * Register the evt_event custom post type.
 *
 * @package Evt
 */

namespace Evt\PostType;

use Evt\Access\EventAccess;

/**
 * CPT registration for events.
 *
 * Es jerárquico porque un evento es a la vez su portada (raíz) y sus páginas
 * satélite (hijas): programa, ponentes, inscripción… Así sustituye 1:1 a las
 * `page` jerárquicas de hoy y se conserva la forma de las URL.
 */
final class EventPostType {

	public const POST_TYPE = 'evt_event';

	/**
	 * Core meta key holding the theme template of a page.
	 */
	public const TEMPLATE_META = '_wp_page_template';

	/**
	 * Value that means «la que el tema use para esto», que es no elegir ninguna.
	 */
	public const TEMPLATE_DEFAULT = 'default';

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
					'name'               => 'Eventos',
					'singular_name'      => 'Evento',
					'add_new'            => 'Añadir evento',
					'add_new_item'       => 'Añadir evento',
					'edit_item'          => 'Editar evento',
					'new_item'           => 'Nuevo evento',
					'view_item'          => 'Ver evento',
					'search_items'       => 'Buscar eventos',
					'not_found'          => 'No se encontraron eventos',
					'not_found_in_trash' => 'No hay eventos en la papelera',
					'parent_item_colon'  => 'Página del evento:',
					'menu_name'          => 'Eventos',
				),
				'public'          => true,
				'hierarchical'    => true,
				'show_in_menu'    => true,
				'menu_position'   => 21,
				'menu_icon'       => 'dashicons-calendar-alt',
				'supports'        => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'page-attributes' ),
				'has_archive'     => false,
				// Conservar las URL actuales (`/eventos/<slug>/`) pide reescritura
				// en la raíz del subsitio y su propia ADR; la fase 1 no la toca.
				'rewrite'         => array(
					'slug'       => 'evento',
					'with_front' => false,
				),
				'capability_type' => array( 'evt_event', 'evt_events' ),
				'map_meta_cap'    => true,
				'capabilities'    => self::capabilities(),
				'show_in_rest'    => true,
			)
		);

		// Un evento se pinta como una página, así que usa las plantillas que el
		// tema declara para `page`. Sin esto, `_wp_page_template` no se ofrece
		// en ningún sitio y la portada del evento sale con la cabecera, el pie
		// y el título del tema encima del nuestro.
		add_filter( 'theme_' . self::POST_TYPE . '_templates', array( self::class, 'theme_templates' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( self::class, 'set_blank_template' ) );
	}

	/**
	 * Offer events the page templates of the active theme.
	 *
	 * El tema del sitio puede declarar una plantilla —«Página en blanco», por
	 * ejemplo— solo para `page`; con este filtro WordPress la ofrece también en el evento, la
	 * enseña en «Atributos de página» y `get_single_template()` la honra —desde
	 * WP 4.7 `_wp_page_template` vale para cualquier tipo de contenido—.
	 *
	 * @param mixed $templates Templates already declared for evt_event.
	 * @return array<string, string> Fichero => rótulo.
	 */
	public static function theme_templates( $templates ): array {
		$propias = is_array( $templates ) ? $templates : array();
		// Lo que el tema declare con `Template Post Type: evt_event` manda.
		return array_merge( wp_get_theme()->get_page_templates( null, 'page' ), $propias );
	}

	/**
	 * Every template an event may be given, ready for a select.
	 *
	 * @return array<string, string> valor => rótulo.
	 */
	public static function page_templates(): array {
		return array_merge(
			array( self::TEMPLATE_DEFAULT => 'La del tema' ),
			wp_get_theme()->get_page_templates( null, self::POST_TYPE )
		);
	}

	/**
	 * The blank template of the active theme, when it offers one.
	 *
	 * No hay ningún nombre de fichero escrito a fuego: se busca en lo que el
	 * tema declara. el tema la llama `page-template-blank.php` / «Página en
	 * blanco»; un tema que no traiga ninguna —Twenty Twenty-Five— devuelve
	 * cadena vacía y el evento se queda con la plantilla por defecto.
	 *
	 * @return string Template file, '' when the theme offers none.
	 */
	public static function blank_template(): string {
		$plantillas = wp_get_theme()->get_page_templates( null, self::POST_TYPE );
		$encontrada = '';

		foreach ( $plantillas as $fichero => $rotulo ) {
			if ( preg_match( '/blank|en\s*blanco|vac[ií]a/iu', (string) $fichero . ' ' . (string) $rotulo ) ) {
				$encontrada = (string) $fichero;
				break;
			}
		}

		/**
		 * Filter which template a new event gets.
		 *
		 * El sitio que llame «Portada limpia» a la suya lo dice aquí, sin tocar
		 * el aplicativo.
		 *
		 * @param string                $encontrada Template file, '' for none.
		 * @param array<string, string> $plantillas Every template available to an event.
		 */
		return (string) apply_filters( 'evt_blank_page_template', $encontrada, $plantillas );
	}

	/**
	 * Give a brand new event —or section— the blank template of the theme.
	 *
	 * Va en `save_post` y no en la pantalla de alta: un evento entra por el
	 * taller, por el escritorio, por WP-CLI y por la migración, y así se
	 * resuelve una vez para todos. Solo escribe cuando la página todavía no
	 * tiene plantilla, así que quien elija otra después manda.
	 *
	 * @param int $post_id Event or section.
	 * @return void
	 */
	public static function set_blank_template( int $post_id ): void {
		if ( metadata_exists( 'post', $post_id, self::TEMPLATE_META ) ) {
			return;
		}

		$plantilla = self::blank_template();
		if ( '' === $plantilla ) {
			return;
		}

		update_post_meta( $post_id, self::TEMPLATE_META, $plantilla );
	}

	/**
	 * Custom capabilities for the CPT.
	 *
	 * No se declara `create_posts`: sin declararla, WordPress usa
	 * `edit_evt_events` para «Añadir nuevo», que es justo lo que tiene el rol
	 * de organización. Declarar una capacidad que nadie tiene es como se cierra
	 * el botón de crear sin querer.
	 *
	 * @return array<string, string>
	 */
	public static function capabilities(): array {
		return self::cap_map( 'evt_event', 'evt_events' );
	}

	/**
	 * Build the WordPress cap map for a singular/plural pair.
	 *
	 * @param string $one  Singular cap base.
	 * @param string $many Plural cap base.
	 * @return array<string, string>
	 */
	public static function cap_map( string $one, string $many ): array {
		return array(
			'edit_post'              => 'edit_' . $one,
			'read_post'              => 'read_' . $one,
			'delete_post'            => 'delete_' . $one,
			'edit_posts'             => 'edit_' . $many,
			'edit_others_posts'      => 'edit_others_' . $many,
			'publish_posts'          => 'publish_' . $many,
			'read_private_posts'     => 'read_private_' . $many,
			'delete_posts'           => 'delete_' . $many,
			'delete_private_posts'   => 'delete_private_' . $many,
			'delete_published_posts' => 'delete_published_' . $many,
			'delete_others_posts'    => 'delete_others_' . $many,
			'edit_private_posts'     => 'edit_private_' . $many,
			'edit_published_posts'   => 'edit_published_' . $many,
		);
	}

	/**
	 * Grant the CPT caps of the three content types to the two roles.
	 *
	 * Idempotente y aditiva: crea lo que falta y nunca quita nada, para que lo
	 * que se conceda a mano en WPFront siga ahí en la siguiente carga.
	 *
	 * @return void
	 */
	public static function grant_caps_to_roles(): void {
		$maps = array(
			self::POST_TYPE                 => self::capabilities(),
			SpeakerPostType::POST_TYPE      => SpeakerPostType::capabilities(),
			ActivityPostType::POST_TYPE     => ActivityPostType::capabilities(),
			RegistrationPostType::POST_TYPE => RegistrationPostType::capabilities(),
		);

		$every = array_keys( self::capabilities() );

		// Del evento, la organización de un área publica y retira lo suyo y lo
		// de sus compañeras, pero no lo privado ni lo de otra persona: esas tres
		// se le reservan a la administración. Lo que la acota al área es
		// EventAccess, no la capacidad.
		$organiser_events = array(
			'edit_post',
			'read_post',
			'delete_post',
			'edit_posts',
			'edit_others_posts',
			'publish_posts',
			'read_private_posts',
			'delete_posts',
			'delete_published_posts',
			'edit_published_posts',
		);

		// De los ponentes y de las actividades, en cambio, se lleva el juego
		// entero: un área gestiona su evento entero, y eso incluye dar de alta
		// a quien participa y montar el programa sin pedir permiso a nadie. El
		// acotado por área lo pone igualmente EventAccess.
		// De las inscripciones, el área lee y borra las de sus eventos —alguien
		// se da de baja, o pide que se le borre— pero no las publica: no las
		// escribe una persona, las escribe el aplicativo cuando alguien se
		// inscribe (ADR-0032).
		$organiser_registrations = array(
			'edit_post',
			'read_post',
			'delete_post',
			'edit_posts',
			'edit_others_posts',
			'read_private_posts',
			'delete_posts',
			'delete_others_posts',
			'delete_private_posts',
			'edit_private_posts',
		);

		$by_role = array(
			'editor'        => array(
				self::POST_TYPE                 => $organiser_events,
				SpeakerPostType::POST_TYPE      => $every,
				ActivityPostType::POST_TYPE     => $every,
				RegistrationPostType::POST_TYPE => $organiser_registrations,
			),
			'evt_organiser' => array(
				self::POST_TYPE                 => $organiser_events,
				SpeakerPostType::POST_TYPE      => $every,
				ActivityPostType::POST_TYPE     => $every,
				RegistrationPostType::POST_TYPE => $organiser_registrations,
			),
			'administrator' => array_fill_keys( array_keys( $maps ), $every ),
		);

		foreach ( $by_role as $slug => $por_tipo ) {
			$role = get_role( $slug );
			if ( ! $role ) {
				continue;
			}
			foreach ( $maps as $tipo => $map ) {
				foreach ( $por_tipo[ $tipo ] as $key ) {
					if ( isset( $map[ $key ] ) && ! $role->has_cap( $map[ $key ] ) ) {
						$role->add_cap( $map[ $key ] );
					}
				}
			}
		}

		self::grant_code_caps();
	}

	/**
	 * The two code capabilities: the CSS also to the área, the JavaScript never.
	 *
	 * Fuera del bucle de arriba a propósito: ese reparte las capacidades de los
	 * tipos de contenido, y estas dos no son de ningún tipo de contenido.
	 *
	 * La raya está donde el riesgo cambia de naturaleza: el CSS cambia **cómo
	 * se ve** una página, y quien organiza la jornada es quien sabe cómo tiene
	 * que verse; el JavaScript **ejecuta código** en el navegador de cada
	 * visitante, que es el poder que WordPress protege con `unfiltered_html` y
	 * el que devolvía sin acotar, a quien ya la tuviera en su rol, un fragmento del
	 * sistema anterior. Por eso son dos capacidades distintas y no
	 * una, que fue el acierto de la ADR-0014.
	 *
	 * Tener el CSS no abre el evento de al lado: el acotado por área lo sigue
	 * poniendo el guardián ({@see \Evt\Access\EventAccess::can_edit_custom_css()}),
	 * que exige las dos cosas.
	 *
	 * @return void
	 */
	public static function grant_code_caps(): void {
		$por_rol = array(
			'editor'        => array( EventAccess::CAP_CUSTOM_CSS ),
			'evt_organiser' => array( EventAccess::CAP_CUSTOM_CSS ),
			'administrator' => self::code_caps(),
		);

		foreach ( $por_rol as $slug => $caps ) {
			$role = get_role( $slug );
			if ( ! $role ) {
				continue;
			}
			foreach ( $caps as $cap ) {
				if ( ! $role->has_cap( $cap ) ) {
					$role->add_cap( $cap );
				}
			}
		}
	}

	/**
	 * The capabilities that guard the custom CSS and JavaScript fields.
	 *
	 * @return string[]
	 */
	public static function code_caps(): array {
		return array( EventAccess::CAP_CUSTOM_CSS, EventAccess::CAP_CUSTOM_JS );
	}

	/**
	 * The code capability that stays with administration, and only with it.
	 *
	 * Escrita aparte para que la comprueben las pantallas, los tests y el
	 * diagnóstico sin repetir la cadena en cada sitio.
	 *
	 * @return string[]
	 */
	public static function admin_only_code_caps(): array {
		return array( EventAccess::CAP_CUSTOM_JS );
	}
}
