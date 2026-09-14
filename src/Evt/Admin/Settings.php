<?php
/**
 * Settings and diagnostics page for the application.
 *
 * @package Evt
 */

namespace Evt\Admin;

use Evt\Access\EventAccess;
use Evt\PostType\ActivityPostType;
use Evt\PostType\EventPostType;
use Evt\PostType\SpeakerPostType;
use Evt\Taxonomy\EventTaxonomies;

/**
 * Qué hay montado y qué falta, en una pantalla.
 *
 * De momento no guarda nada: la fase 1 no tiene ningún ajuste que decidir, y
 * lo que sí hace falta es poder mirar si los tipos, las taxonomías y los roles
 * están donde deberían sin abrir la base de datos.
 */
final class Settings {

	/**
	 * Menu slug.
	 */
	public const PAGE = 'evt-settings';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ) );
	}

	/**
	 * Add the submenu page.
	 *
	 * @return void
	 */
	public static function menu(): void {
		add_submenu_page(
			'edit.php?post_type=' . EventPostType::POST_TYPE,
			'Ajustes y diagnóstico de eventos',
			'Ajustes',
			EventAccess::CAP_MANAGE,
			self::PAGE,
			array( self::class, 'render' )
		);
	}

	/**
	 * Render the diagnostics page.
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! EventAccess::is_manager() ) {
			wp_die( esc_html( 'No tiene permiso para ver los ajustes del aplicativo de eventos.' ), '', array( 'response' => 403 ) );
		}

		$post_types = array(
			EventPostType::POST_TYPE    => 'Eventos y sus páginas',
			SpeakerPostType::POST_TYPE  => 'Ponentes',
			ActivityPostType::POST_TYPE => 'Actividades',
		);
		$taxonomies = array(
			EventTaxonomies::AREA   => 'Área organizadora',
			EventTaxonomies::TYPE   => 'Tipología',
			EventTaxonomies::COURSE => 'Curso escolar',
		);
		?>
		<div class="wrap">
			<h1>Ajustes y diagnóstico de eventos</h1>
			<p class="description" style="max-width:46rem">
				Esta pantalla no guarda nada todavía: cuenta lo que el aplicativo tiene montado
				en este sitio. Si algo sale «sin registrar», es que el snippet correspondiente
				no está activo.
			</p>

			<h2>Tipos de contenido</h2>
			<table class="widefat striped" style="max-width:46rem">
				<tbody>
				<?php foreach ( $post_types as $slug => $label ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $label ); ?></strong><br /><code><?php echo esc_html( $slug ); ?></code></td>
						<td><?php echo post_type_exists( $slug ) ? 'Registrado' : 'Sin registrar'; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<h2>Taxonomías</h2>
			<table class="widefat striped" style="max-width:46rem">
				<tbody>
				<?php foreach ( $taxonomies as $slug => $label ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $label ); ?></strong><br /><code><?php echo esc_html( $slug ); ?></code></td>
						<td>
							<?php
							if ( ! taxonomy_exists( $slug ) ) {
								echo 'Sin registrar';
							} else {
								$total = wp_count_terms(
									array(
										'taxonomy'   => $slug,
										'hide_empty' => false,
									)
								);
								echo esc_html( sprintf( 'Registrada, %d términos', is_wp_error( $total ) ? 0 : (int) $total ) );
							}
							?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<h2>Roles del aplicativo</h2>
			<?php if ( ! function_exists( 'evt_roles_status' ) ) : ?>
				<p>El snippet <code>EVT — Roles y perfiles</code> no está activo, así que no hay roles que revisar.</p>
			<?php else : ?>
				<table class="widefat striped" style="max-width:46rem">
					<tbody>
					<?php foreach ( evt_roles_status() as $slug => $estado ) : ?>
						<tr>
							<td><strong><?php echo esc_html( (string) $estado['label'] ); ?></strong><br /><code><?php echo esc_html( $slug ); ?></code></td>
							<td>
								<?php
								if ( empty( $estado['exists'] ) ) {
									echo 'Falta el rol';
								} elseif ( ! empty( $estado['missing'] ) ) {
									echo esc_html( 'Sin ' . implode( ', ', (array) $estado['missing'] ) );
								} else {
									echo 'Correcto';
								}
								?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<h2>Su acotado por área</h2>
			<?php
			$areas = EventAccess::user_areas();
			$names = array();
			foreach ( $areas as $term_id ) {
				$term = get_term( $term_id, EventTaxonomies::AREA );
				if ( $term instanceof \WP_Term ) {
					$names[] = $term->name;
				}
			}
			?>
			<p>
				<?php if ( EventAccess::can_edit_all_areas() ) : ?>
					Ve y edita los eventos de todas las áreas.
				<?php elseif ( array() === $names ) : ?>
					No tiene ningún área asignada en su perfil, así que no ve ni edita ningún evento.
				<?php else : ?>
					<?php echo esc_html( 'Acotado a: ' . implode( ', ', $names ) ); ?>
				<?php endif; ?>
			</p>
		</div>
		<?php
	}
}
