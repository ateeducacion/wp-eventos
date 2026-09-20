<?php
/**
 * Admin settings and diagnostic screen for educational centres catalogue.
 *
 * @package Evt
 */

namespace Evt\Centre;

use Evt\PublicFront\ExitSignal;
use RuntimeException;

/**
 * Pantalla de diagnóstico y actualización manual del catálogo de centros educativos.
 *
 * Ubicada en Ajustes → Centros educativos. Permite consultar el estado de la
 * sincronización y forzar una actualización manual.
 */
final class CentreSettings {

	public const MENU_SLUG = 'evt-centres-settings';

	public const NONCE_MANUAL_SYNC = 'evt_centres_manual_sync';

	public const NONCE_SETTINGS = 'evt_centres_save_settings';

	/**
	 * Register admin menu and hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'add_menu_page' ) );
		add_action( 'admin_init', array( self::class, 'handle_actions' ) );
	}

	/**
	 * Add submenu page under Settings.
	 *
	 * @return string
	 */
	public static function add_menu_page(): string {
		$hook = add_options_page(
			'Centros educativos',
			'Centros educativos',
			'manage_options',
			self::MENU_SLUG,
			array( self::class, 'render_page' )
		);
		return is_string( $hook ) ? $hook : '';
	}

	/**
	 * Handle manual sync and settings post actions.
	 *
	 * @return void
	 */
	public static function handle_actions(): void {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// 1. Sincronización manual.
		if (
			isset( $_POST['evt_centres_action'] ) &&
			'sync_now' === $_POST['evt_centres_action'] &&
			check_admin_referer( self::NONCE_MANUAL_SYNC, '_evt_centres_nonce' )
		) {
			$redirect_args = array( 'page' => self::MENU_SLUG );
			try {
				$result = CentreCatalogueSync::sync( true );
				$msg    = sprintf(
					'Catálogo actualizado correctamente (%d centros, %d activos). SHA-256: %s',
					$result['records'],
					$result['active'],
					substr( $result['sha256'], 0, 12 ) . '…'
				);

				$redirect_args['updated'] = 'synced';
				$redirect_args['msg']     = rawurlencode( $msg );
			} catch ( RuntimeException $e ) {
				$redirect_args['error'] = rawurlencode( $e->getMessage() );
			}

			self::leave( add_query_arg( $redirect_args, admin_url( 'options-general.php' ) ) );
		}

		// 2. Guardar ajustes de URLs.
		if (
			isset( $_POST['evt_centres_action'] ) &&
			'save_settings' === $_POST['evt_centres_action'] &&
			check_admin_referer( self::NONCE_SETTINGS, '_evt_centres_nonce' )
		) {
			$manifest_url  = isset( $_POST['manifest_url'] ) ? esc_url_raw( wp_unslash( $_POST['manifest_url'] ) ) : '';
			$catalogue_url = isset( $_POST['catalogue_url'] ) ? esc_url_raw( wp_unslash( $_POST['catalogue_url'] ) ) : '';

			update_option( CentreCatalogueSync::OPTION_MANIFEST_URL, $manifest_url, false );
			update_option( CentreCatalogueSync::OPTION_CATALOGUE_URL, $catalogue_url, false );

			self::leave(
				add_query_arg(
					array(
						'page'    => self::MENU_SLUG,
						'updated' => 'saved',
					),
					admin_url( 'options-general.php' )
				)
			);
		}
	}

	/**
	 * Safe exit or throw ExitSignal under tests.
	 *
	 * @param string $url Target redirect URL.
	 * @throws ExitSignal When running under tests with exit filter.
	 * @return void
	 */
	private static function leave( string $url ): void {
		if ( apply_filters( 'evt_exit_throws', false, $url ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- la URL viaja para que el test la lea; no se imprime.
			throw new ExitSignal( $url );
		}
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Render settings and diagnostic screen.
	 *
	 * @return void
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tiene permisos suficientes para acceder a esta página.', 'default' ) );
		}

		$status       = CentreCatalogue::status();
		$total_count  = CentreCatalogue::count();
		$active_count = CentreCatalogue::active_count();
		$is_ready     = $total_count > 0;

		$manifest_url  = (string) get_option( CentreCatalogueSync::OPTION_MANIFEST_URL, '' );
		$catalogue_url = (string) get_option( CentreCatalogueSync::OPTION_CATALOGUE_URL, '' );

		$effective_manifest  = CentreCatalogueSync::manifest_url();
		$effective_catalogue = CentreCatalogueSync::catalogue_url( $effective_manifest );

		?>
		<div class="wrap">
			<h1>Centros educativos</h1>
			<p class="description" style="max-width:48rem">
				Catálogo de centros educativos cacheado localmente desde la fuente maestra externa.
				Este catálogo no es público y se utiliza para alimentar los selectores de centros y validar inscripciones.
			</p>

			<?php if ( isset( $_GET['updated'] ) && 'synced' === $_GET['updated'] && ! empty( $_GET['msg'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( sanitize_text_field( wp_unslash( (string) $_GET['msg'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?></p></div>
			<?php elseif ( isset( $_GET['updated'] ) && 'saved' === $_GET['updated'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p>Ajustes guardados correctamente.</p></div>
			<?php elseif ( isset( $_GET['error'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-error is-dismissible"><p><?php echo esc_html( sanitize_text_field( wp_unslash( (string) $_GET['error'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?></p></div>
			<?php endif; ?>

			<div style="display:flex; gap:2rem; flex-wrap:wrap; margin-top:1.5rem;">
				<!-- Diagnóstico del estado del catálogo -->
				<div style="flex:1; min-width:20rem; max-width:42rem;">
					<div class="card" style="margin:0; padding:1.2rem; max-width:none;">
						<h2>Estado del catálogo local</h2>
						<table class="widefat striped" style="margin-top:1rem;">
							<tbody>
								<tr>
									<th style="width:40%;">Catálogo configurado</th>
									<td>
										<?php if ( $is_ready ) : ?>
											<span class="dashicons dashicons-yes-alt" style="color:#46b450;" aria-hidden="true"></span> Sí
										<?php else : ?>
											<span class="dashicons dashicons-warning" style="color:#dc3232;" aria-hidden="true"></span> No (catálogo vacío)
										<?php endif; ?>
									</td>
								</tr>
								<tr>
									<th>Registros totales</th>
									<td><strong><?php echo esc_html( (string) $total_count ); ?></strong></td>
								</tr>
								<tr>
									<th>Registros activos</th>
									<td><strong style="color:#46b450;"><?php echo esc_html( (string) $active_count ); ?></strong></td>
								</tr>
								<tr>
									<th>Registros inactivos</th>
									<td><?php echo esc_html( (string) ( $total_count - $active_count ) ); ?></td>
								</tr>
								<tr>
									<th>SHA-256 actual</th>
									<td>
										<?php if ( '' !== $status['sha256'] ) : ?>
											<code style="font-size:0.85em;"><?php echo esc_html( $status['sha256'] ); ?></code>
										<?php else : ?>
											<em>Ninguno</em>
										<?php endif; ?>
									</td>
								</tr>
								<tr>
									<th>Versión de esquema</th>
									<td><?php echo esc_html( (string) $status['schema_version'] ); ?></td>
								</tr>
								<tr>
									<th>Fecha del catálogo</th>
									<td><?php echo esc_html( '' !== $status['catalogue_updated_at'] ? $status['catalogue_updated_at'] : '—' ); ?></td>
								</tr>
								<tr>
									<th>Última comprobación</th>
									<td><?php echo esc_html( '' !== $status['last_checked_at'] ? $status['last_checked_at'] : '—' ); ?></td>
								</tr>
								<tr>
									<th>Última actualización correcta</th>
									<td><?php echo esc_html( '' !== $status['last_success_at'] ? $status['last_success_at'] : '—' ); ?></td>
								</tr>
								<?php if ( '' !== $status['last_error'] ) : ?>
									<tr>
										<th style="color:#dc3232;">Último error</th>
										<td style="color:#dc3232;"><code><?php echo esc_html( $status['last_error'] ); ?></code></td>
									</tr>
								<?php endif; ?>
							</tbody>
						</table>

						<div style="margin-top:1.5rem; display:flex; gap:1rem; align-items:center;">
							<form method="post" action="">
								<?php wp_nonce_field( self::NONCE_MANUAL_SYNC, '_evt_centres_nonce' ); ?>
								<input type="hidden" name="evt_centres_action" value="sync_now" />
								<button type="submit" class="button button-primary">
									Actualizar catálogo ahora
								</button>
							</form>
						</div>
					</div>
				</div>

				<!-- Configuración de URLs -->
				<div style="flex:1; min-width:20rem; max-width:40rem;">
					<div class="card" style="margin:0; padding:1.2rem; max-width:none;">
						<h2>Configuración de la fuente externa</h2>
						<form method="post" action="">
							<?php wp_nonce_field( self::NONCE_SETTINGS, '_evt_centres_nonce' ); ?>
							<input type="hidden" name="evt_centres_action" value="save_settings" />

							<p>
								<label for="evt_manifest_url"><strong>URL de manifest.json:</strong></label><br />
								<input type="url" id="evt_manifest_url" name="manifest_url" value="<?php echo esc_attr( $manifest_url ); ?>" class="large-text" placeholder="https://ejemplo.org/.../manifest.json" />
								<span class="description">URL completa del archivo <code>manifest.json</code> externo.</span>
								<?php if ( '' !== $effective_manifest && $effective_manifest !== $manifest_url ) : ?>
									<br /><span class="description" style="color:#666;">URL efectiva (por filtro/constante): <code><?php echo esc_html( $effective_manifest ); ?></code></span>
								<?php endif; ?>
							</p>

							<p>
								<label for="evt_catalogue_url"><strong>URL de centros.min.json (opcional):</strong></label><br />
								<input type="url" id="evt_catalogue_url" name="catalogue_url" value="<?php echo esc_attr( $catalogue_url ); ?>" class="large-text" placeholder="https://ejemplo.org/.../centros.min.json" />
								<span class="description">Si se deja en blanco, se deduce de la ruta de <code>manifest.json</code>.</span>
								<?php if ( '' !== $effective_catalogue && $effective_catalogue !== $catalogue_url ) : ?>
									<br /><span class="description" style="color:#666;">URL efectiva: <code><?php echo esc_html( $effective_catalogue ); ?></code></span>
								<?php endif; ?>
							</p>

							<p style="margin-top:1.5rem;">
								<button type="submit" class="button button-secondary">
									Guardar configuración
								</button>
							</p>
						</form>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
