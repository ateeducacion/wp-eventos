<?php
/**
 * Settings and diagnostics page for the application.
 *
 * @package Evt
 */

namespace Evt\Admin;

use Evt\Access\EventAccess;
use Evt\Centre\CentreCatalogue;
use Evt\Centre\CentreCatalogueSync;
use Evt\Meta\RegistrationMetaKeys;
use Evt\PostType\ActivityPostType;
use Evt\PostType\EventPostType;
use Evt\PostType\SpeakerPostType;
use Evt\PublicFront\ExitSignal;
use Evt\Taxonomy\EventTaxonomies;
use RuntimeException;

/**
 * Qué hay montado y qué falta, en una pantalla.
 *
 * Muestra el estado de los tipos, taxonomías, roles, acotado y catálogo
 * de centros educativos, con capacidad para forzar la sincronización manual.
 */
final class Settings {

	/**
	 * Menu slug.
	 */
	public const PAGE = 'evt-settings';

	/**
	 * Nonce action for manual centre catalogue sync.
	 */
	public const NONCE_SYNC_CENTRES = 'evt_centres_manual_sync';

	/**
	 * Nonce action of the URL form.
	 */
	public const NONCE_URLS = 'evt_root_urls';

	/**
	 * Nonce action for the default consent texts.
	 */
	public const NONCE_CONSENT = 'evt_default_consent';

	/**
	 * The two consent texts every new event starts with.
	 *
	 * Se copian en el evento al crearlo; a partir de ahí son del evento y se
	 * cambian allí sin tocar estos (ADR-0020).
	 */
	public const OPTION_CONSENT_PRIVACY = 'evt_default_consent_privacy';
	public const OPTION_CONSENT_IMAGE   = 'evt_default_consent_image';

	/**
	 * Nonce action of the header and footer form.
	 */
	public const NONCE_CHROME = 'evt_chrome_settings';

	/**
	 * Who publishes, above «Eventos» in the header of the application.
	 *
	 * Es la clave `org` del armazón ({@see \Evt\PublicFront\View\EventChrome::chrome()}):
	 * vacía por defecto, y el filtro `evt_chrome` sigue mandando sobre ella (ADR-0049).
	 */
	public const OPTION_ORG = 'evt_chrome_org';

	/**
	 * The links of the footer: a list of `{label, url}`.
	 */
	public const OPTION_FOOTER_LINKS = 'evt_chrome_footer_links';

	/**
	 * Empty rows the footer form always offers after the stored links.
	 */
	public const FOOTER_BLANKS = 2;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ) );
		add_action( 'admin_init', array( self::class, 'handle_actions' ) );
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
	 * Handle manual actions like centre catalogue sync.
	 *
	 * @return void
	 */
	public static function handle_actions(): void {
		if ( ! is_admin() || ! EventAccess::is_manager() ) {
			return;
		}

		if (
			isset( $_POST['evt_action'] ) &&
			'root_urls' === $_POST['evt_action'] &&
			check_admin_referer( self::NONCE_URLS, '_evt_urls_nonce' )
		) {
			update_option( EventPostType::OPTION_ROOT_URLS, empty( $_POST['evt_root_urls'] ) ? '' : '1' );
			self::leave(
				add_query_arg(
					array(
						'post_type' => EventPostType::POST_TYPE,
						'page'      => self::PAGE,
						'updated'   => 'synced',
						'msg'       => rawurlencode( 'Guardada la forma de las direcciones de los eventos.' ),
					),
					admin_url( 'edit.php' )
				)
			);
		}

		if (
			isset( $_POST['evt_action'] ) &&
			'consent' === $_POST['evt_action'] &&
			check_admin_referer( self::NONCE_CONSENT, '_evt_consent_nonce' )
		) {
			update_option( self::OPTION_CONSENT_PRIVACY, wp_kses_post( wp_unslash( (string) ( $_POST['evt_consent_privacy'] ?? '' ) ) ), false );
			update_option( self::OPTION_CONSENT_IMAGE, wp_kses_post( wp_unslash( (string) ( $_POST['evt_consent_image'] ?? '' ) ) ), false );
			self::leave(
				add_query_arg(
					array(
						'post_type' => EventPostType::POST_TYPE,
						'page'      => self::PAGE,
						'updated'   => 'synced',
						'msg'       => rawurlencode( 'Guardados los textos de protección de datos de los eventos nuevos.' ),
					),
					admin_url( 'edit.php' )
				)
			);
		}

		if (
			isset( $_POST['evt_action'] ) &&
			'chrome' === $_POST['evt_action'] &&
			check_admin_referer( self::NONCE_CHROME, '_evt_chrome_nonce' )
		) {
			update_option( self::OPTION_ORG, sanitize_text_field( wp_unslash( (string) ( $_POST['evt_org'] ?? '' ) ) ) );
			$rotulos = isset( $_POST['evt_footer_label'] ) && is_array( $_POST['evt_footer_label'] ) ? wp_unslash( $_POST['evt_footer_label'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- se limpia en clean_links().
			$urls    = isset( $_POST['evt_footer_url'] ) && is_array( $_POST['evt_footer_url'] ) ? wp_unslash( $_POST['evt_footer_url'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- se limpia en clean_links().
			update_option( self::OPTION_FOOTER_LINKS, self::clean_links( $rotulos, $urls ) );
			self::leave(
				add_query_arg(
					array(
						'post_type' => EventPostType::POST_TYPE,
						'page'      => self::PAGE,
						'updated'   => 'synced',
						'msg'       => rawurlencode( 'Guardados la organización y los enlaces del pie.' ),
					),
					admin_url( 'edit.php' )
				)
			);
		}

		if (
			isset( $_POST['evt_action'] ) &&
			'sync_centres' === $_POST['evt_action'] &&
			check_admin_referer( self::NONCE_SYNC_CENTRES, '_evt_centres_nonce' )
		) {
			$redirect_args = array(
				'post_type' => EventPostType::POST_TYPE,
				'page'      => self::PAGE,
			);

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

			self::leave( add_query_arg( $redirect_args, admin_url( 'edit.php' ) ) );
		}
	}

	/**
	 * The footer links from the form: rows with a text and a web address, in order.
	 *
	 * Una fila a medias —sin texto o sin una dirección `http(s)` válida— se
	 * cae: un enlace sin destino o sin rótulo no sirve en el pie.
	 *
	 * @param array<int|string, mixed> $labels Texts, by row.
	 * @param array<int|string, mixed> $urls   Addresses, by row.
	 * @return array<int, array{label:string, url:string}>
	 */
	public static function clean_links( array $labels, array $urls ): array {
		$enlaces = array();
		foreach ( $labels as $fila => $rotulo ) {
			$rotulo = sanitize_text_field( is_string( $rotulo ) ? $rotulo : '' );
			$url    = esc_url_raw( trim( is_string( $urls[ $fila ] ?? null ) ? $urls[ $fila ] : '' ), array( 'http', 'https' ) );
			if ( '' !== $rotulo && '' !== $url ) {
				$enlaces[] = array(
					'label' => $rotulo,
					'url'   => $url,
				);
			}
		}
		return $enlaces;
	}

	/**
	 * The stored footer links.
	 *
	 * @return array<int, array{label:string, url:string}>
	 */
	public static function footer_links(): array {
		$guardados = get_option( self::OPTION_FOOTER_LINKS, array() );
		return is_array( $guardados ) ? array_values( array_filter( $guardados, 'is_array' ) ) : array();
	}

	/**
	 * The consent texts a new event starts with.
	 *
	 * Mientras administración no los guarde, se proponen los del evento más
	 * reciente que los tenga: son los que se vienen usando, y así nadie tiene
	 * que ir a buscarlos. Guardados —aunque sea en blanco—, mandan los guardados.
	 *
	 * @return array{privacy:string, image:string, from:int} `from` is the event they come from; 0 when saved.
	 */
	public static function default_consent(): array {
		$privacidad = get_option( self::OPTION_CONSENT_PRIVACY, null );
		$imagen     = get_option( self::OPTION_CONSENT_IMAGE, null );
		if ( null !== $privacidad || null !== $imagen ) {
			return array(
				'privacy' => (string) $privacidad,
				'image'   => (string) $imagen,
				'from'    => 0,
			);
		}

		$ultimo = get_posts(
			array(
				'post_type'        => EventPostType::POST_TYPE,
				'post_parent'      => 0,
				'post_status'      => 'any',
				'numberposts'      => 1,
				'orderby'          => 'date',
				'order'            => 'DESC',
				'fields'           => 'ids',
				'suppress_filters' => true,
				'meta_query'       => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- una sola vez, en ajustes y al crear un evento.
					array(
						'key'     => RegistrationMetaKeys::CONSENT_PRIVACY,
						'value'   => '',
						'compare' => '!=',
					),
				),
			)
		);
		$evento = (int) ( $ultimo[0] ?? 0 );
		return array(
			'privacy' => $evento > 0 ? (string) get_post_meta( $evento, RegistrationMetaKeys::CONSENT_PRIVACY, true ) : '',
			'image'   => $evento > 0 ? (string) get_post_meta( $evento, RegistrationMetaKeys::CONSENT_IMAGE, true ) : '',
			'from'    => $evento,
		);
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
			EventTaxonomies::AREA   => 'Ámbito organizativo',
			EventTaxonomies::TYPE   => 'Tipología',
			EventTaxonomies::COURSE => 'Curso escolar',
		);
		?>
		<div class="wrap">
			<h1>Ajustes y diagnóstico de eventos</h1>
			<p class="description" style="max-width:46rem">
				Esta pantalla cuenta lo que el aplicativo tiene montado en este sitio.
				Si algo sale «sin registrar», es que el snippet correspondiente no está activo.
			</p>

			<?php if ( isset( $_GET['updated'] ) && 'synced' === $_GET['updated'] && ! empty( $_GET['msg'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( sanitize_text_field( wp_unslash( (string) $_GET['msg'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?></p></div>
			<?php elseif ( isset( $_GET['error'] ) && ! empty( $_GET['error'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-error is-dismissible"><p><?php echo esc_html( sanitize_text_field( wp_unslash( (string) $_GET['error'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?></p></div>
			<?php endif; ?>

			<h2>Direcciones de los eventos</h2>
			<form method="post" action="" style="max-width:46rem">
				<?php wp_nonce_field( self::NONCE_URLS, '_evt_urls_nonce' ); ?>
				<input type="hidden" name="evt_action" value="root_urls" />
				<p>
					<label>
						<input type="checkbox" name="evt_root_urls" value="1" <?php checked( EventPostType::root_urls() ); ?> />
						Servir los eventos en la raíz del sitio: <code><?php echo esc_html( home_url( '/nombre-del-evento/' ) ); ?></code>
					</label>
				</p>
				<p class="description">
					Es la forma que tienen hoy las páginas de los eventos, y la que conservan al migrarlos.
					Sin marcar, van bajo <code><?php echo esc_html( home_url( '/evento/' ) ); ?></code>.
					Si en la raíz hay una página con la misma dirección, gana la página.
				</p>
				<?php submit_button( 'Guardar', 'secondary', 'submit', false ); ?>
			</form>

			<?php
			$enlaces = self::footer_links();
			$filas   = count( $enlaces ) + self::FOOTER_BLANKS;
			?>
			<h2>Cabecera y pie de las páginas</h2>
			<form method="post" action="" style="max-width:46rem">
				<?php wp_nonce_field( self::NONCE_CHROME, '_evt_chrome_nonce' ); ?>
				<input type="hidden" name="evt_action" value="chrome" />
				<p>
					<label for="evt-org"><strong>Nombre de la organización</strong></label><br />
					<input class="regular-text" type="text" id="evt-org" name="evt_org" value="<?php echo esc_attr( (string) get_option( self::OPTION_ORG, '' ) ); ?>" />
				</p>
				<p class="description">Sale arriba a la izquierda del aplicativo, junto a «Eventos». Por ejemplo, el nombre de la dirección general que publica.</p>
				<p><strong>Enlaces del pie</strong></p>
				<p class="description">Salen a la derecha del pie de cada página pública, en este orden: aviso legal, privacidad, accesibilidad… Una fila sin texto o sin dirección no se guarda; para añadir más, guarde y aparecerán huecos nuevos.</p>
				<table class="widefat striped">
					<thead><tr><th scope="col">Texto</th><th scope="col">Dirección</th></tr></thead>
					<tbody>
					<?php for ( $i = 0; $i < $filas; $i++ ) : ?>
						<tr>
							<td><input class="widefat" type="text" name="evt_footer_label[<?php echo (int) $i; ?>]" aria-label="<?php echo esc_attr( 'Texto del enlace ' . ( $i + 1 ) ); ?>" value="<?php echo esc_attr( (string) ( $enlaces[ $i ]['label'] ?? '' ) ); ?>" /></td>
							<td><input class="widefat" type="url" name="evt_footer_url[<?php echo (int) $i; ?>]" aria-label="<?php echo esc_attr( 'Dirección del enlace ' . ( $i + 1 ) ); ?>" placeholder="https://" value="<?php echo esc_attr( (string) ( $enlaces[ $i ]['url'] ?? '' ) ); ?>" /></td>
						</tr>
					<?php endfor; ?>
					</tbody>
				</table>
				<p class="description">Si un snippet contesta al filtro <code>evt_chrome</code> con estos mismos datos, manda el snippet.</p>
				<?php submit_button( 'Guardar', 'secondary', 'submit', false ); ?>
			</form>

			<?php $consent = self::default_consent(); ?>
			<h2>Protección de datos de los eventos nuevos</h2>
			<form method="post" action="" style="max-width:46rem">
				<?php wp_nonce_field( self::NONCE_CONSENT, '_evt_consent_nonce' ); ?>
				<input type="hidden" name="evt_action" value="consent" />
				<p class="description">
					Cada evento nuevo empieza con una copia de estos dos textos. Después se cambian en el propio
					evento, en «Inscripción», sin tocar estos; y cambiar estos no toca los eventos que ya existen.
				</p>
				<?php if ( $consent['from'] > 0 ) : ?>
					<div class="notice notice-info inline"><p>
						<?php echo esc_html( sprintf( 'Todavía no se han guardado: se proponen los de «%s», el evento más reciente que los tiene. Guárdelos para fijarlos.', get_the_title( $consent['from'] ) ) ); ?>
					</p></div>
				<?php endif; ?>
				<?php
				// El editor visual de WordPress, como en «Inscripción» del evento.
				$textos = array(
					'evt_consent_privacy' => array( 'Información sobre el tratamiento de sus datos', $consent['privacy'] ),
					'evt_consent_image'   => array( 'Consentimiento informado', $consent['image'] ),
				);
				?>
				<?php foreach ( $textos as $campo => $texto ) : ?>
					<p><label for="<?php echo esc_attr( $campo ); ?>"><strong><?php echo esc_html( $texto[0] ); ?></strong></label></p>
					<?php
					wp_editor(
						(string) $texto[1],
						$campo,
						array(
							'textarea_name' => $campo,
							'textarea_rows' => 8,
							'media_buttons' => false,
						)
					);
					?>
				<?php endforeach; ?>
				<?php submit_button( 'Guardar', 'secondary', 'submit', false ); ?>
			</form>

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

			<h2>Roles del aplicativo y Editor nativo</h2>
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
								} elseif ( ! empty( $estado['forbidden'] ) ) {
									echo esc_html( 'Capacidades indebidas: ' . implode( ', ', (array) $estado['forbidden'] ) );
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

			<h2>Ámbitos de los perfiles editores</h2>
			<p>Diagnóstico de solo lectura. Los perfiles ambiguos o inválidos no reciben acceso hasta que administración elija un ámbito en su perfil.</p>
			<table class="widefat striped" style="max-width:46rem">
				<thead><tr><th>Usuario</th><th>Estado</th><th>IDs válidos</th><th>IDs inválidos</th><th>Formato anterior</th></tr></thead>
				<tbody>
				<?php foreach ( EventAccess::scope_diagnostics() as $row ) : ?>
					<tr>
						<td><a href="<?php echo esc_url( get_edit_user_link( $row['user_id'] ) ); ?>"><?php echo esc_html( $row['login'] ); ?></a></td>
						<td><?php echo esc_html( $row['state'] ); ?></td>
						<td><?php echo esc_html( implode( ', ', $row['ids'] ) ); ?></td>
						<td><?php echo esc_html( implode( ', ', $row['invalid'] ) ); ?></td>
						<td><?php echo $row['legacy'] ? 'Sí' : 'No'; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<h2>Catálogo de centros educativos</h2>
			<?php
			$status       = CentreCatalogue::status();
			$total_count  = CentreCatalogue::count();
			$active_count = CentreCatalogue::active_count();
			$is_ready     = $total_count > 0;
			$has_source   = '' !== CentreCatalogueSync::manifest_url();
			?>
			<table class="widefat striped" style="max-width:46rem">
				<tbody>
					<tr>
						<th style="width:40%;">Estado</th>
						<td>
							<?php if ( $is_ready ) : ?>
								<span class="dashicons dashicons-yes-alt" style="color:#46b450;" aria-hidden="true"></span> Disponible
							<?php else : ?>
								<span class="dashicons dashicons-warning" style="color:#dc3232;" aria-hidden="true"></span> No disponible (catálogo vacío)
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th>Fuente configurada</th>
						<td><?php echo $has_source ? 'Sí' : 'No'; ?></td>
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
					<?php if ( '' !== $status['last_error'] ) : ?>
						<tr>
							<th style="color:#dc3232;">Último error</th>
							<td style="color:#dc3232;"><code><?php echo esc_html( $status['last_error'] ); ?></code></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>

			<div style="margin-top:1rem;">
				<form method="post" action="">
					<?php wp_nonce_field( self::NONCE_SYNC_CENTRES, '_evt_centres_nonce' ); ?>
					<input type="hidden" name="evt_action" value="sync_centres" />
					<button type="submit" class="button button-secondary">
						Actualizar catálogo ahora
					</button>
				</form>
			</div>

			<h2>Su ámbito organizativo</h2>
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
					Ve y edita los eventos de todos los ámbitos.
				<?php elseif ( array() === $names ) : ?>
					No tiene ningún ámbito asignado en su perfil, así que no ve ni edita ningún evento.
				<?php else : ?>
					<?php echo esc_html( 'Acotado a: ' . implode( ', ', $names ) ); ?>
				<?php endif; ?>
			</p>
		</div>
		<?php
	}
}
