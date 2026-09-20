<?php
/**
 * Snippet Name: EVT — Roles y perfiles
 * Description: Registra el rol de compatibilidad evt_organiser y el selector de Ámbito organizativo del perfil, reservado a administración. El Editor nativo es el actor recomendado. Las capacidades de los tipos de contenido las reparte el aplicativo.
 * Scope: global
 * Priority: 5
 *
 * @package Evt
 */

// Code Snippets evalúa esto, no lo incluye como fichero, así que aquí no hay
// «acceso directo» que valga. La guarda va igual porque no cuesta nada y porque
// el día que este código acabe en un fichero servido —una copia, un envoltorio,
// una carpeta de plugins— la diferencia entre volcar el código y no volcarlo es
// esta línea.
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'evt_role_slugs' ) ) {
	/**
	 * Return the product role slugs.
	 *
	 * @return string[]
	 */
	function evt_role_slugs(): array {
		return array( 'evt_organiser' );
	}
}

if ( ! function_exists( 'evt_role_definitions' ) ) {
	/**
	 * Role labels and the capabilities this snippet owns.
	 *
	 * Aquí solo van las capacidades propias del aplicativo. Las de los tipos de
	 * contenido (`edit_evt_events`, `publish_evt_events`, …) las reparte
	 * `Evt\PostType\EventPostType::grant_caps_to_roles()` en `init` prioridad
	 * 11, para no tener el mismo mapa escrito en dos sitios que se separan.
	 *
	 * @return array<string, array{label:string, caps:string[]}>
	 */
	function evt_role_definitions(): array {
		return array(
			'evt_organiser' => array(
				'label' => 'Organización de eventos',
				'caps'  => array(
					'read',
					'upload_files',
				),
			),
		);
	}
}

if ( ! function_exists( 'evt_forbidden_role_caps' ) ) {
	/**
	 * Capabilities no product role may ever hold.
	 *
	 * Es la raya del aplicativo, y no se mueve. Un área gestiona su evento
	 * entero —sus páginas, sus ponentes y sus actividades—, y justo por eso
	 * conviene tener escrito dónde acaba eso:
	 *
	 * - `evt_edit_custom_js` es el campo de JavaScript a medida, y es solo de
	 *   `administrator`. Se ejecuta en el navegador de cada visitante: es el
	 *   poder que WordPress protege con `unfiltered_html`, y es el error que
	 *   comete el sistema anterior, que la devuelve sin acotar, a quien ya la
	 *   tuviera en su rol, desde un
	 *   fragmento de código pegado a mano. El CSS **no** está en
	 *   esta lista: cambia cómo se ve una página y lo escribe también el área,
	 *   acotada a la suya (ADR-0014).
	 * - `evt_manage_app` abre los ajustes, el diagnóstico y el alta de términos
	 *   de las tres taxonomías. Quien organiza usa las áreas que hay; crearlas
	 *   es administrar el aplicativo, no organizar un evento.
	 *
	 * Están escritas aquí y no solo omitidas de `evt_role_definitions()`: lo
	 * que no se nombra no se comprueba, y esta lista es la que mira
	 * `evt_roles_status()` para avisar si alguien las concede a mano en WPFront.
	 * Este snippet nunca quita capacidades —es aditivo a propósito—, así que
	 * avisa y quien administra decide.
	 *
	 * @return string[]
	 */
	function evt_forbidden_role_caps(): array {
		return array( 'evt_edit_custom_js', 'evt_manage_app', 'evt_edit_all_areas', 'unfiltered_html' );
	}
}

if ( ! function_exists( 'evt_audited_role_definitions' ) ) {
	/** Product-owned roles plus the native editor that the product uses. */
	function evt_audited_role_definitions(): array {
		return array_merge(
			evt_role_definitions(),
			array(
				'editor' => array(
					'label' => 'Editor de eventos',
					'caps'  => array( 'edit_evt_events', 'publish_evt_events', 'edit_evt_speakers', 'edit_evt_activities', 'edit_evt_registrations', 'evt_edit_custom_css' ),
				),
			)
		);
	}
}

if ( ! function_exists( 'evt_retire_coordinator_role' ) ) {
	/**
	 * Retire the legacy `evt_coordinator` role, once and only once.
	 *
	 * El aplicativo tuvo tres roles y ahora tiene dos: la organización de un
	 * área y la administración de WordPress. `evt_register_roles()` es aditiva
	 * a propósito, así que sin esto el rol viejo se quedaría para siempre en la
	 * base de datos de cada entorno ya aprovisionado —wp-env y producción— y
	 * seguiría concediendo `evt_edit_all_areas`, que es justo lo que se retira.
	 *
	 * Va con opción de guarda —se hace una vez y se anota—: quitarlo en cada
	 * carga desharía en silencio
	 * cualquier decisión posterior de quien administra en WPFront.
	 *
	 * A quien tuviera el rol se le pone antes `evt_organiser`, para que nadie
	 * se quede sin ningún rol —eso es una cuenta que entra y no puede hacer
	 * nada, y sin rastro de por qué—. Pierde el salto entre áreas, que es la
	 * decisión que se está tomando, y hasta que quien administra le ponga su
	 * área en el perfil no edita nada: el acotado falla en cerrado.
	 *
	 * @return void
	 */
	function evt_retire_coordinator_role(): void {
		if ( get_option( 'evt_coordinator_role_retired' ) ) {
			return;
		}

		foreach ( get_users(
			array(
				'role'   => 'evt_coordinator',
				'fields' => 'ID',
			)
		) as $user_id ) {
			$user = get_user_by( 'id', (int) $user_id );
			if ( $user instanceof WP_User ) {
				$user->add_role( 'evt_organiser' );
				$user->remove_role( 'evt_coordinator' );
			}
		}

		remove_role( 'evt_coordinator' );
		update_option( 'evt_coordinator_role_retired', 1 );
	}
}

if ( ! function_exists( 'evt_register_roles' ) ) {
	/**
	 * Idempotently register product roles and grant caps to administrators.
	 *
	 * Aditiva: crea el rol si falta y añade la capacidad si falta, nunca quita.
	 * Así lo que se conceda a mano en WPFront sigue ahí en la siguiente carga,
	 * y quitar una capacidad se hace en el código, que es donde se ve. La única
	 * excepción es la retirada del rol viejo, que corre una sola vez
	 * ({@see evt_retire_coordinator_role()}).
	 *
	 * @return void
	 */
	function evt_register_roles(): void {
		foreach ( evt_role_definitions() as $slug => $def ) {
			$role = get_role( $slug );
			if ( ! $role ) {
				add_role( $slug, $def['label'], array() );
				$role = get_role( $slug );
			}
			if ( ! $role ) {
				continue;
			}
			foreach ( $def['caps'] as $cap ) {
				if ( ! $role->has_cap( $cap ) ) {
					$role->add_cap( $cap );
				}
			}
		}

		// `evt_edit_custom_css` y `evt_edit_custom_js` no se reparten aquí: las
		// concede `Evt\PostType\EventPostType::grant_code_caps()` —el CSS
		// también al área, el JavaScript solo a `administrator` (ADR-0014)—.
		// Este snippet se limita a comprobar que ningún rol del aplicativo
		// tenga las prohibidas ({@see evt_forbidden_role_caps()}).
		// `evt_edit_all_areas` y `evt_manage_app` son de la administración y de
		// nadie más: salirse del área y tocar los ajustes del aplicativo no es
		// organizar un evento.
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( array( 'evt_manage_app', 'evt_edit_all_areas' ) as $cap ) {
				if ( ! $admin->has_cap( $cap ) ) {
					$admin->add_cap( $cap );
				}
			}
		}

		evt_retire_coordinator_role();
	}
}

if ( ! function_exists( 'evt_role_exists' ) ) {
	/**
	 * Whether a role exists, asking WPFront User Role Editor when it is there.
	 *
	 * Los roles se administran en WPFront: preguntarle a él es preguntar a la
	 * misma lista que ve quien los mantiene. Sin el plugin, el registro de
	 * WordPress dice lo mismo.
	 *
	 * @param string $slug Role slug.
	 * @return bool
	 */
	function evt_role_exists( string $slug ): bool {
		$helper = '\\WPFront\\URE\\WPFront_User_Role_Editor_Roles_Helper';
		if ( class_exists( $helper ) && method_exists( $helper, 'is_role' ) ) {
			return (bool) call_user_func( array( $helper, 'is_role' ), $slug );
		}
		return null !== get_role( $slug );
	}
}

if ( ! function_exists( 'evt_roles_status' ) ) {
	/**
	 * What is missing for each product role to work, and what it should not have.
	 *
	 * `missing` es lo que le falta; `forbidden`, lo que le sobra y es peligroso
	 * ({@see evt_forbidden_role_caps()}). Las dos listas vacías es lo correcto.
	 *
	 * @return array<string, array{label:string, exists:bool, missing:string[], forbidden:string[]}>
	 */
	function evt_roles_status(): array {
		$out = array();
		foreach ( evt_audited_role_definitions() as $slug => $def ) {
			$exists  = evt_role_exists( $slug );
			$role    = $exists ? get_role( $slug ) : null;
			$missing = array();
			foreach ( $def['caps'] as $cap ) {
				if ( ! $role || ! $role->has_cap( $cap ) ) {
					$missing[] = $cap;
				}
			}
			$sobran    = array();
			$forbidden = 'editor' === $slug ? array_diff( evt_forbidden_role_caps(), array( 'unfiltered_html' ) ) : evt_forbidden_role_caps();
			foreach ( $forbidden as $cap ) {
				if ( $role && $role->has_cap( $cap ) ) {
					$sobran[] = $cap;
				}
			}
			$out[ $slug ] = array(
				'label'     => $def['label'],
				'exists'    => $exists,
				'missing'   => $missing,
				'forbidden' => $sobran,
			);
		}
		return $out;
	}
}

if ( ! function_exists( 'evt_can_edit_admin_only_fields' ) ) {
	/**
	 * Whether the current user may set somebody's área.
	 *
	 * @return bool
	 */
	function evt_can_edit_admin_only_fields(): bool {
		return current_user_can( 'manage_options' );
	}
}

if ( ! function_exists( 'evt_render_profile_fields' ) ) {
	/**
	 * Render the área field on the user profile screens.
	 *
	 * @param WP_User $user User being edited.
	 * @return void
	 */
	function evt_render_profile_fields( $user ): void {
		if ( ! ( $user instanceof WP_User ) || ! taxonomy_exists( 'evt_area' ) || ! evt_can_edit_admin_only_fields() || array() === array_intersect( array( 'editor', 'evt_organiser' ), $user->roles ) ) {
			return;
		}

		if ( ! class_exists( '\Evt\Taxonomy\EventTaxonomies' ) ) {
			return;
		}
		$terms = \Evt\Taxonomy\EventTaxonomies::area_options();

		$assignment = \Evt\Access\EventAccess::scope_assignment_state( $user->ID );
		$mine       = 'resolved' === $assignment['state'] ? $assignment['ids'][0] : 0;
		$unresolved = in_array( $assignment['state'], array( 'ambiguous', 'invalid' ), true );

		echo '<h2>Eventos</h2>';
		echo '<table class="form-table" role="presentation"><tr>';
		echo '<th><label for="evt_area">Ámbito organizativo</label></th><td>';
		wp_nonce_field( 'evt_profile_scope_' . $user->ID, 'evt_profile_scope_nonce' );

		echo '<input type="hidden" name="evt_area_present" value="1" />';
		echo '<select name="evt_area" id="evt_area" class="regular-text">';
		if ( $unresolved ) {
			echo '<option value="__keep_unresolved__" selected="selected">Pendiente de resolver (conservar datos)</option>';
		}
		printf( '<option value=""%s>Sin ámbito</option>', 'empty' === $assignment['state'] ? ' selected="selected"' : '' );
		foreach ( $terms as $term_id => $label ) {
			printf(
				'<option value="%1$d"%2$s>%3$s</option>',
				(int) $term_id,
				(int) $term_id === $mine ? ' selected="selected"' : '',
				esc_html( $label )
			);
		}
		echo '</select>';
		if ( $unresolved ) {
			$labels = \Evt\Taxonomy\EventTaxonomies::area_options( 0, true );
			$names  = array_map(
				static function ( $id ) use ( $labels ) {
					return $labels[ $id ] ?? (string) $id;
				},
				$assignment['ids']
			);
			if ( $assignment['invalid'] ) {
				$names[] = 'IDs inválidos: ' . implode( ', ', $assignment['invalid'] );
			}
			printf( '<p class="notice notice-warning">Este perfil conserva ámbitos históricos: %s. Debe elegir uno para recuperar el acceso; mientras tanto, el usuario no accede a contenidos acotados. Si guarda sin elegir, se conservarán los datos.</p>', esc_html( implode( ', ', $names ) ) );
		}
		echo '<p class="description">Un ámbito incluye sus descendientes. Sin ámbito, esta persona no ve ni edita ningún evento.</p>';
		echo '</td></tr></table>';
	}
}

if ( ! function_exists( 'evt_save_profile_fields' ) ) {
	/**
	 * Persist the área field.
	 *
	 * El área decide qué eventos toca cada persona, así que quien está acotado
	 * por ella no puede escribirla: WordPress deja a cualquiera editar su
	 * propio perfil, y eso convertiría el campo en la puerta de al lado.
	 *
	 * @param int $user_id User ID being saved.
	 * @return void
	 */
	function evt_save_profile_fields( int $user_id ): void {
		$user = get_user_by( 'id', $user_id );
		if ( ! ( $user instanceof WP_User ) || array() === array_intersect( array( 'editor', 'evt_organiser' ), $user->roles ) || ! current_user_can( 'edit_user', $user_id ) || ! evt_can_edit_admin_only_fields() ) {
			return;
		}
		if ( ! isset( $_POST['evt_profile_scope_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['evt_profile_scope_nonce'] ) ), 'evt_profile_scope_' . $user_id ) ) {
			return;
		}
		$post = wp_unslash( $_POST );
		if ( empty( $post['evt_area_present'] ) ) {
			return;
		}

		if ( ! array_key_exists( 'evt_area', $post ) ) {
			return;
		}
		$raw = $post['evt_area'];
		if ( is_array( $raw ) ) {
			return;
		}
		$raw = sanitize_text_field( (string) $raw );
		if ( '__keep_unresolved__' === $raw ) {
			return;
		}
		if ( '' !== $raw && ! ctype_digit( $raw ) ) {
			return;
		}
		$term_id = absint( $raw );
		$term    = $term_id > 0 ? get_term( $term_id, 'evt_area' ) : null;
		if ( $term_id > 0 && ! ( $term instanceof WP_Term ) ) {
			return;
		}
		update_user_meta( $user_id, 'evt_area', $term_id > 0 ? array( $term_id ) : array() );
	}
}

add_action( 'init', 'evt_register_roles', 5 );
add_action( 'show_user_profile', 'evt_render_profile_fields' );
add_action( 'edit_user_profile', 'evt_render_profile_fields' );
add_action( 'personal_options_update', 'evt_save_profile_fields' );
add_action( 'edit_user_profile_update', 'evt_save_profile_fields' );
