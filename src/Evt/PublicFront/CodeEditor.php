<?php
/**
 * The WordPress code editor (CodeMirror), wrapped for our own screens.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

/**
 * Un campo de código con el editor que ya trae WordPress.
 *
 * `wp_enqueue_code_editor()` y `wp.codeEditor.initialize()`, no CodeJar ni
 * ninguna otra biblioteca: ya está en el núcleo —cero bytes que empaquetar en
 * el bundle de Code Snippets—, trae CSSLint y JSHint, y es el mismo editor que
 * la persona conoce del «CSS adicional» del personalizador.
 *
 * Degrada, y esto no es opcional: `wp_enqueue_code_editor()` devuelve `false`
 * cuando quien mira ha desactivado el resaltado de sintaxis en su perfil. En
 * ese caso {@see field()} pinta un `<textarea>` normal y todo sigue
 * funcionando; lo único que se pierde es el coloreado y el avisador de
 * errores.
 *
 * Esta clase solo encola y pinta: quién puede escribir código lo decide
 * {@see \Evt\Access\EventAccess::can_edit_custom_css()} y su hermana, y qué se
 * guarda, {@see \Evt\Meta\EventMetaRegistration}.
 */
final class CodeEditor {

	/**
	 * Modo de hoja de estilos.
	 */
	public const MODE_CSS = 'css';

	/**
	 * Modo de guion. Se llama como el `type` de CodeMirror, no `js`.
	 */
	public const MODE_JS = 'javascript';

	/**
	 * MIME type each mode asks WordPress for.
	 *
	 * @var array<string, string>
	 */
	private const MIMES = array(
		self::MODE_CSS => 'text/css',
		self::MODE_JS  => 'text/javascript',
	);

	/**
	 * Load CodeMirror for one mode, and hand back whether it will be there.
	 *
	 * Idempotente: encolar dos veces el mismo guion no lo imprime dos veces, y
	 * ajustar los ajustes cuesta lo que cuesta leer un array.
	 *
	 * @param string $modo self::MODE_CSS or self::MODE_JS.
	 * @return bool False cuando WordPress no puede —modo desconocido, o el
	 *              resaltado desactivado en el perfil—, y toca el textarea pelado.
	 */
	public static function enqueue( string $modo ): bool {
		return array() !== self::settings( $modo );
	}

	/**
	 * One code field: its label, its help, and the textarea CodeMirror grows on.
	 *
	 * Argumentos, todos opcionales salvo `name`:
	 *
	 *   mode   string  self::MODE_CSS (por defecto) o self::MODE_JS.
	 *   name   string  Nombre del campo; es la clave de meta (`evt_custom_css`).
	 *   id     string  Identificador; se deriva del nombre si no se da.
	 *   label  string  Rótulo.
	 *   help   string  La ayuda, en castellano llano, debajo del campo.
	 *   value  string  Lo que hay guardado, en crudo.
	 *   rows   int     Altura del textarea sin CodeMirror. 12 por defecto.
	 *
	 * @param array<string, mixed> $args Lo de arriba.
	 * @return string
	 */
	public static function field( array $args ): string {
		$modo   = isset( self::MIMES[ (string) ( $args['mode'] ?? '' ) ] ) ? (string) $args['mode'] : self::MODE_CSS;
		$nombre = (string) ( $args['name'] ?? '' );
		if ( '' === $nombre ) {
			return '';
		}

		$id     = (string) ( $args['id'] ?? '' );
		$id     = '' !== $id ? $id : 'evt-code-' . str_replace( '_', '-', $nombre );
		$rotulo = (string) ( $args['label'] ?? '' );
		$ayuda  = (string) ( $args['help'] ?? '' );
		$valor  = (string) ( $args['value'] ?? '' );
		$filas  = max( 4, (int) ( $args['rows'] ?? 12 ) );

		// Se encola aquí y no solo fuera: quien pinta un campo no tiene que
		// acordarse de dos llamadas, y la respuesta de esta decide el aspecto.
		$ajustes  = self::settings( $modo );
		$ayuda_id = $id . '-ayuda';

		ob_start();
		?>
		<div class="evt-code evt-code-<?php echo esc_attr( $modo ); ?>">
			<?php if ( '' !== $rotulo ) : ?>
				<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $rotulo ); ?></label>
			<?php endif; ?>
			<textarea
				id="<?php echo esc_attr( $id ); ?>"
				name="<?php echo esc_attr( $nombre ); ?>"
				class="evt-code-area"
				rows="<?php echo esc_attr( (string) $filas ); ?>"
				spellcheck="false"
				autocapitalize="off"
				autocomplete="off"
				autocorrect="off"
				<?php if ( '' !== $ayuda ) : ?>
					aria-describedby="<?php echo esc_attr( $ayuda_id ); ?>"
				<?php endif; ?>
				<?php if ( array() !== $ajustes ) : ?>
					data-evt-code="<?php echo esc_attr( $modo ); ?>"
					data-evt-code-settings="<?php echo esc_attr( (string) wp_json_encode( $ajustes ) ); ?>"
				<?php endif; ?>
			><?php echo esc_textarea( $valor ); ?></textarea>
			<?php if ( '' !== $ayuda ) : ?>
				<small id="<?php echo esc_attr( $ayuda_id ); ?>"><?php echo esc_html( $ayuda ); ?></small>
			<?php endif; ?>
			<?php if ( array() === $ajustes ) : ?>
				<small class="evt-code-plano">
					El resaltado de código está desactivado en su perfil, así que este campo es un cuadro de texto normal. Se guarda igual.
				</small>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Ask WordPress for CodeMirror and its settings for one mode.
	 *
	 * @param string $modo self::MODE_CSS or self::MODE_JS.
	 * @return array<string, mixed> Empty array when there will be no CodeMirror.
	 */
	private static function settings( string $modo ): array {
		if ( ! isset( self::MIMES[ $modo ] ) ) {
			return array();
		}
		$ajustes = wp_enqueue_code_editor( array( 'type' => self::MIMES[ $modo ] ) );
		return is_array( $ajustes ) ? $ajustes : array();
	}
}
