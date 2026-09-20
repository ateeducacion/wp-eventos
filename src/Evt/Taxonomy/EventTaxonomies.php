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

	/** Term meta keys for scope contact details. */
	public const EMAIL    = 'evt_scope_email';
	public const IMAGE_ID = 'evt_scope_image_id';

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		$area_args                = self::args( 'Ámbitos organizativos', 'Ámbito organizativo' );
		$area_args['meta_box_cb'] = array( self::class, 'area_meta_box' );
		register_taxonomy( self::AREA, EventPostType::POST_TYPE, $area_args );
		register_taxonomy( self::TYPE, EventPostType::POST_TYPE, self::args( 'Tipologías', 'Tipología' ) );
		register_taxonomy( self::COURSE, EventPostType::POST_TYPE, self::args( 'Cursos escolares', 'Curso escolar' ) );
		register_term_meta(
			self::AREA,
			self::EMAIL,
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => 'sanitize_email',
				'auth_callback'     => array( self::class, 'can_edit_meta' ),
			)
		);
		register_term_meta(
			self::AREA,
			self::IMAGE_ID,
			array(
				'type'              => 'integer',
				'single'            => true,
				'sanitize_callback' => 'absint',
				'auth_callback'     => array( self::class, 'can_edit_meta' ),
			)
		);
		add_action( self::AREA . '_add_form_fields', array( self::class, 'add_fields' ) );
		add_action( self::AREA . '_edit_form_fields', array( self::class, 'edit_fields' ) );
		add_action( 'created_' . self::AREA, array( self::class, 'save_fields' ) );
		add_action( 'edited_' . self::AREA, array( self::class, 'save_fields' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_media' ) );
		add_action( 'admin_notices', array( self::class, 'scope_notice' ) );
		add_filter( 'rest_' . self::AREA . '_query', array( self::class, 'rest_area_query' ) );
	}

	/**
	 * Keep the block editor's term collection inside the editor's subtree.
	 *
	 * @param array<string, mixed> $args Term query arguments.
	 * @return array<string, mixed>
	 */
	public static function rest_area_query( array $args ): array {
		$user_id = get_current_user_id();
		if ( $user_id > 0 && user_can( $user_id, 'edit_evt_events' ) && ! EventAccess::can_edit_all_areas( $user_id ) ) {
			$allowed         = EventAccess::scope_areas( $user_id );
			$args['include'] = array() === $allowed ? array( 0 ) : $allowed;
		}
		return $args;
	}

	/**
	 * Named paths for scope selectors, restricted to the current user's tree.
	 *
	 * @param int  $user_id User ID, or current user.
	 * @param bool $all     Include all terms for read-only labels.
	 * @return array<int, string>
	 */
	public static function area_options( int $user_id = 0, bool $all = false ): array {
		$user_id = $user_id > 0 ? $user_id : get_current_user_id();
		$allowed = $all || EventAccess::can_edit_all_areas( $user_id ) ? null : EventAccess::scope_areas( $user_id );
		$terms   = get_terms(
			array(
				'taxonomy'   => self::AREA,
				'hide_empty' => false,
			)
		);
		if ( ! is_array( $terms ) ) {
			return array();
		}
		$names = array();
		foreach ( $terms as $term ) {
			$names[ (int) $term->term_id ] = $term->name;
		}
		$options = array();
		foreach ( $terms as $term ) {
			$id = (int) $term->term_id;
			if ( null !== $allowed && ! in_array( $id, $allowed, true ) ) {
				continue;
			}
			$path           = array_reverse( get_ancestors( $id, self::AREA, 'taxonomy' ) );
			$path[]         = $id;
			$options[ $id ] = implode(
				' › ',
				array_map(
					static function ( $part ) use ( $names ) {
						return $names[ $part ] ?? '';
					},
					$path
				)
			);
		}
		return $options;
	}

	/**
	 * Replace the native scope box with choices from the same access rule.
	 *
	 * @param \WP_Post $post Edited event.
	 * @return void
	 */
	public static function area_meta_box( $post ): void {
		$selected = $post instanceof \WP_Post ? EventAccess::post_areas( $post->ID ) : array();
		$allowed  = EventAccess::can_edit_all_areas() ? $selected : EventAccess::scope_areas();
		echo '<div class="inside"><p>Seleccione los ámbitos que organiza su perfil.</p><input type="hidden" name="evt_area_present" value="1" />';
		foreach ( self::area_options() as $id => $label ) {
			printf( '<label><input type="checkbox" name="tax_input[%1$s][]" value="%2$d"%3$s /> %4$s</label><br />', esc_attr( self::AREA ), (int) $id, in_array( $id, $selected, true ) ? ' checked="checked"' : '', esc_html( $label ) );
		}
		$foreign = array_diff( $selected, $allowed );
		if ( $foreign ) {
			$labels = self::area_options( 0, true );
			echo '<p>Otros ámbitos organizadores (solo lectura):</p><ul>';
			foreach ( $foreign as $id ) {
				printf( '<li>%s</li>', esc_html( $labels[ $id ] ?? '' ) );
			}
			echo '</ul><p>Se conservarán al guardar. Solo administración o una persona de ese ámbito puede modificar su participación.</p>';
		}
		echo '</div>';
	}

	/**
	 * Whether the current user may edit scope metadata.
	 *
	 * @return bool
	 */
	public static function can_edit_meta(): bool {
		return current_user_can( EventAccess::CAP_MANAGE ) || current_user_can( 'manage_options' );
	}

	/** Render fields on the new-term form. */
	public static function add_fields(): void {
		wp_nonce_field( 'evt_scope_meta', 'evt_scope_meta_nonce' );
		echo '<div class="form-field"><label for="evt_scope_email">Correo del ámbito</label><input type="email" id="evt_scope_email" name="evt_scope_email" value="" /></div>';
		echo '<div class="form-field"><label for="evt_scope_image_id">Imagen del ámbito</label><input type="hidden" id="evt_scope_image_id" name="evt_scope_image_id" value="0" /><button type="button" class="button evt-scope-choose-image">Elegir imagen</button> <button type="button" class="button evt-scope-remove-image">Quitar imagen</button><span class="evt-scope-image-name"></span></div>';
	}

	/**
	 * Render fields on the edit-term form.
	 *
	 * @param \WP_Term $term Edited term.
	 */
	public static function edit_fields( $term ): void {
		if ( ! ( $term instanceof \WP_Term ) || self::AREA !== $term->taxonomy ) {
			return;
		}
		wp_nonce_field( 'evt_scope_meta', 'evt_scope_meta_nonce' );
		printf( '<tr class="form-field"><th><label for="evt_scope_email">Correo del ámbito</label></th><td><input type="email" id="evt_scope_email" name="evt_scope_email" value="%s" /></td></tr>', esc_attr( (string) get_term_meta( $term->term_id, self::EMAIL, true ) ) );
		$image_id = absint( get_term_meta( $term->term_id, self::IMAGE_ID, true ) );
		printf( '<tr class="form-field"><th><label for="evt_scope_image_id">Imagen del ámbito</label></th><td><input type="hidden" id="evt_scope_image_id" name="evt_scope_image_id" value="%1$s" /><button type="button" class="button evt-scope-choose-image">Elegir imagen</button> <button type="button" class="button evt-scope-remove-image">Quitar imagen</button> <span class="evt-scope-image-name">%2$s</span></td></tr>', esc_attr( (string) $image_id ), $image_id ? esc_html( get_the_title( $image_id ) . ' (ID ' . $image_id . ')' ) : 'Sin imagen' );
	}

	/** Load the native media picker only on the scope taxonomy screen. */
	public static function enqueue_media(): void {
		$screen = get_current_screen();
		if ( ! $screen || self::AREA !== $screen->taxonomy ) {
			return;
		}
		wp_enqueue_media();
		wp_add_inline_script(
			'media-views',
			'document.addEventListener("click", function (event) { const choose = event.target.closest(".evt-scope-choose-image"); const remove = event.target.closest(".evt-scope-remove-image"); if (!choose && !remove) return; const field = document.getElementById("evt_scope_image_id"); const name = document.querySelector(".evt-scope-image-name"); if (remove) { field.value = "0"; name.textContent = ""; return; } const frame = wp.media({ title: "Imagen del ámbito", library: { type: "image" }, multiple: false }); frame.on("select", function () { const attachment = frame.state().get("selection").first().toJSON(); field.value = attachment.id; name.textContent = attachment.filename; }); frame.open(); });'
		);
	}

	/**
	 * Save validated scope metadata. Empty values delete existing metadata.
	 *
	 * @param int $term_id Edited term ID.
	 */
	public static function save_fields( int $term_id ): void {
		$term = get_term( $term_id, self::AREA );
		if ( ! ( $term instanceof \WP_Term ) || ! self::can_edit_meta() || ! isset( $_POST['evt_scope_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['evt_scope_meta_nonce'] ) ), 'evt_scope_meta' ) ) {
			return;
		}
		$errors = array();
		if ( isset( $_POST[ self::EMAIL ] ) ) {
			$raw_email = trim( sanitize_text_field( wp_unslash( $_POST[ self::EMAIL ] ) ) );
			$email     = sanitize_email( $raw_email );
			if ( '' === $raw_email ) {
				delete_term_meta( $term_id, self::EMAIL );
			} elseif ( is_email( $email ) ) {
				update_term_meta( $term_id, self::EMAIL, $email );
			} else {
				$errors[] = 'El correo del ámbito no es válido; se ha conservado el anterior.';
			}
		}
		if ( isset( $_POST[ self::IMAGE_ID ] ) ) {
			$raw_image = sanitize_text_field( wp_unslash( $_POST[ self::IMAGE_ID ] ) );
			$image_id  = absint( $raw_image );
			if ( '0' === $raw_image ) {
				delete_term_meta( $term_id, self::IMAGE_ID );
			} elseif ( wp_attachment_is_image( $image_id ) ) {
				update_term_meta( $term_id, self::IMAGE_ID, $image_id );
			} else {
				$errors[] = 'La imagen del ámbito no es válida; se ha conservado la anterior.';
			}
		}
		if ( $errors ) {
			set_transient( 'evt_scope_error_' . get_current_user_id(), implode( ' ', $errors ), 60 );
		}
	}

	/** Show validation errors after the taxonomy form redirects. */
	public static function scope_notice(): void {
		$screen = get_current_screen();
		if ( ! $screen || self::AREA !== $screen->taxonomy || ! self::can_edit_meta() ) {
			return;
		}
		$key     = 'evt_scope_error_' . get_current_user_id();
		$message = get_transient( $key );
		if ( ! is_string( $message ) ) {
			return;
		}
		delete_transient( $key );
		printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $message ) );
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
