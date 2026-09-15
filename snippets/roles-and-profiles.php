<?php
/**
 * Snippet Name: EVT — Roles y perfiles
 * Description: Registra el rol del aplicativo de eventos (evt_organiser), sus capacidades propias y el campo de perfil «Área» que acota lo que cada persona ve y edita. La administración es el rol nativo de WordPress y solo recibe capacidades. Las capacidades de los tipos de contenido las reparte el aplicativo, no este snippet. Los roles se revisan en WPFront User Role Editor.
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
		return array( 'evt_edit_custom_js', 'evt_manage_app', 'unfiltered_html' );
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
		foreach ( evt_role_definitions() as $slug => $def ) {
			$exists  = evt_role_exists( $slug );
			$role    = $exists ? get_role( $slug ) : null;
			$missing = array();
			foreach ( $def['caps'] as $cap ) {
				if ( ! $role || ! $role->has_cap( $cap ) ) {
					$missing[] = $cap;
				}
			}
			$sobran = array();
			foreach ( evt_forbidden_role_caps() as $cap ) {
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
		return current_user_can( 'evt_manage_app' ) || current_user_can( 'edit_users' );
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
		if ( ! ( $user instanceof WP_User ) || ! taxonomy_exists( 'evt_area' ) ) {
			return;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'evt_area',
				'hide_empty' => false,
			)
		);
		if ( is_wp_error( $terms ) ) {
			return;
		}

		$mine = get_user_meta( $user->ID, 'evt_area', true );
		$mine = is_array( $mine ) ? array_map( 'intval', $mine ) : array();

		echo '<h2>Eventos</h2>';
		echo '<table class="form-table" role="presentation"><tr>';
		echo '<th><label for="evt_area">Área organizadora</label></th><td>';

		if ( ! evt_can_edit_admin_only_fields() ) {
			$names = array();
			foreach ( $terms as $term ) {
				if ( in_array( (int) $term->term_id, $mine, true ) ) {
					$names[] = $term->name;
				}
			}
			echo esc_html( array() === $names ? 'Sin área asignada.' : implode( ', ', $names ) );
			echo '<p class="description">El área la asigna quien administra el aplicativo: es la que decide qué eventos ve y edita.</p>';
			echo '</td></tr></table>';
			return;
		}

		echo '<input type="hidden" name="evt_area_present" value="1" />';
		echo '<select name="evt_area[]" id="evt_area" multiple size="8" class="regular-text">';
		foreach ( $terms as $term ) {
			printf(
				'<option value="%1$d"%2$s>%3$s</option>',
				(int) $term->term_id,
				in_array( (int) $term->term_id, $mine, true ) ? ' selected="selected"' : '',
				esc_html( str_repeat( '— ', max( 0, count( get_ancestors( (int) $term->term_id, 'evt_area', 'taxonomy' ) ) ) ) . $term->name )
			);
		}
		echo '</select>';
		echo '<p class="description">Una o varias áreas. Sin ninguna, esta persona no ve ni edita ningún evento.</p>';
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
		if ( ! current_user_can( 'edit_user', $user_id ) || ! evt_can_edit_admin_only_fields() ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- el formulario de perfil usa el nonce del núcleo.
		$post = wp_unslash( $_POST );
		if ( empty( $post['evt_area_present'] ) ) {
			return;
		}

		$raw   = isset( $post['evt_area'] ) ? (array) $post['evt_area'] : array();
		$areas = array();
		foreach ( $raw as $term_id ) {
			$term_id = (int) $term_id;
			$term    = $term_id > 0 ? get_term( $term_id, 'evt_area' ) : null;
			if ( $term instanceof WP_Term ) {
				$areas[] = $term_id;
			}
		}

		update_user_meta( $user_id, 'evt_area', array_values( array_unique( $areas ) ) );
	}
}

add_action( 'init', 'evt_register_roles', 5 );
add_action( 'show_user_profile', 'evt_render_profile_fields' );
add_action( 'edit_user_profile', 'evt_render_profile_fields' );
add_action( 'personal_options_update', 'evt_save_profile_fields' );
add_action( 'edit_user_profile_update', 'evt_save_profile_fields' );
