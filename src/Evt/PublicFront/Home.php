<?php
/**
 * Landing page of the events application.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

use Evt\Access\EventAccess;

/**
 * Shortcode [evt_home]: la puerta de entrada.
 *
 * Una sola dirección que publicar en el menú, y detrás cada persona entra
 * directamente en su pantalla en vez de en un saludo con enlaces. Los accesos
 * en tarjeta se siguen pintando para quien llega sin sitio a donde ir: sin
 * permisos todavía, o con el perfil a medio rellenar.
 */
final class Home {

	public const SHORTCODE = 'evt_home';

	/**
	 * Register the shortcode and the redirection.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'render' ) );
		add_action( 'template_redirect', array( self::class, 'send_to_landing' ) );
	}

	/**
	 * Send whoever lands on the entry page to their first screen.
	 *
	 * @return void
	 */
	public static function send_to_landing(): void {
		if ( is_admin() || ! is_singular() || ! is_user_logged_in() ) {
			return;
		}
		$post = get_post();
		if ( ! $post instanceof \WP_Post || ! has_shortcode( (string) $post->post_content, self::SHORTCODE ) ) {
			return;
		}

		$destino = self::landing();
		// Si la portada es además la pantalla de destino, no hay a dónde ir.
		if ( '' === $destino || untrailingslashit( $destino ) === untrailingslashit( (string) get_permalink( $post ) ) ) {
			return;
		}

		Shell::leave( $destino );
	}

	/**
	 * First screen of whoever is looking.
	 *
	 * @return string Empty when there is none.
	 */
	public static function landing(): string {
		foreach ( Shell::sections() as $clave => $seccion ) {
			// «Ajustes» es el escritorio de WordPress, no una pantalla en la
			// que empezar el día.
			if ( 'settings' === $clave ) {
				continue;
			}
			return (string) $seccion['url'];
		}
		return '';
	}

	/**
	 * What the landing page has to say.
	 *
	 * @return array{logged_in:bool, name:string, sections:array<string, array{label:string, url:string, badge:?int}>, reason:string}
	 */
	public static function model(): array {
		if ( ! is_user_logged_in() ) {
			return array(
				'logged_in' => false,
				'name'      => '',
				'sections'  => array(),
				'reason'    => 'Debe iniciar sesión con su usuario para gestionar eventos.',
			);
		}

		$user_id   = get_current_user_id();
		$secciones = Shell::sections( $user_id );
		$motivo    = '';

		if ( array() === $secciones ) {
			$motivo = ! user_can( $user_id, 'edit_evt_events' ) && ! EventAccess::is_manager( $user_id )
				? 'Su usuario todavía no organiza eventos. Pídalo a quien administre el aplicativo.'
				: EventAccess::scope_assignment_message( $user_id );
		}

		return array(
			'logged_in' => true,
			'name'      => (string) wp_get_current_user()->display_name,
			'sections'  => $secciones,
			'reason'    => $motivo,
		);
	}

	/**
	 * The landing page, from its model.
	 *
	 * @param array{logged_in:bool, name:string, sections:array<string, array{label:string, url:string, badge:?int}>, reason:string} $model What model() returned.
	 * @return string
	 */
	public static function html( array $model ): string {
		if ( ! $model['logged_in'] ) {
			return Shell::render( 'Gestión de eventos', '', Shell::notice( 'aviso', $model['reason'] ) );
		}

		if ( '' !== $model['reason'] ) {
			return Shell::render(
				sprintf( 'Hola, %s', $model['name'] ),
				'',
				Shell::notice( 'aviso', $model['reason'] )
			);
		}

		ob_start();
		?>
		<div class="evt-inicio-accesos">
			<?php foreach ( $model['sections'] as $acceso ) : ?>
				<a class="evt-inicio-acceso" href="<?php echo esc_url( $acceso['url'] ); ?>">
					<span class="evt-inicio-titulo"><?php echo esc_html( $acceso['label'] ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
		<?php
		return Shell::render(
			sprintf( 'Hola, %s', $model['name'] ),
			'Esto es lo que puede hacer aquí.',
			(string) ob_get_clean()
		);
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array<string, string>|string $atts Attributes.
	 * @return string
	 */
	public static function render( $atts = array() ): string {
		unset( $atts );
		Assets::enqueue();
		return self::html( self::model() );
	}
}
