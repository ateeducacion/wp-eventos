<?php
/**
 * The satellite page form, painted from what PageForm::model() decided.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

use Evt\Meta\EventMetaKeys;
use Evt\PublicFront\Assets;
use Evt\PublicFront\CodeEditor;
use Evt\PublicFront\PageForm;
use Evt\PublicFront\Shell;

/**
 * Solo pinta: no lee la petición, no consulta, no decide.
 */
final class PageFormView {

	/**
	 * The whole screen.
	 *
	 * @param array<string, mixed> $m What PageForm::model() returned.
	 * @return string
	 */
	public static function html( array $m ): string {
		if ( '' !== (string) $m['aviso'] ) {
			return Shell::render(
				'Sección del evento',
				'',
				Shell::notice( 'aviso', (string) $m['aviso'] ) . self::back_to_events()
			);
		}

		$nueva = 0 === (int) $m['page_id'];

		return Shell::render(
			$nueva ? 'Nueva sección' : 'Editar la sección',
			self::subtitle( $m, $nueva ),
			self::form( $m, $nueva )
		);
	}

	/**
	 * One line under the heading: of which event, and how it stands.
	 *
	 * @param array<string, mixed> $m     Model.
	 * @param bool                 $nueva Whether the page is being created.
	 * @return string
	 */
	private static function subtitle( array $m, bool $nueva ): string {
		if ( $nueva ) {
			return sprintf( 'Del evento «%s». Nace en borrador: no la ve nadie hasta que la publique.', (string) $m['event'] );
		}
		$estado = 'publish' === (string) $m['status'] ? 'Publicada' : 'En borrador';
		return sprintf( 'Del evento «%s» · %s', (string) $m['event'], $estado );
	}

	/**
	 * The form itself.
	 *
	 * @param array<string, mixed> $m     Model.
	 * @param bool                 $nueva Whether the page is being created.
	 * @return string
	 */
	private static function form( array $m, bool $nueva ): string {
		$valores = (array) $m['values'];
		$errores = (array) $m['errors'];

		ob_start();
		echo Shell::notice( 'ok', (string) $m['hecho'] );    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- llega escapado.
		echo Shell::notice( 'error', (string) $m['error'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- llega escapado.
		?>
		<form class="evt-form" method="post" action="">
			<input type="hidden" name="evt_page_form" value="1" />
			<input type="hidden" name="evt_page_event" value="<?php echo esc_attr( (string) $m['event_id'] ); ?>" />
			<?php if ( ! $nueva ) : ?>
				<input type="hidden" name="evt_page_id" value="<?php echo esc_attr( (string) $m['page_id'] ); ?>" />
			<?php endif; ?>
			<?php wp_nonce_field( PageForm::NONCE_ACTION, PageForm::NONCE_FIELD ); ?>

			<fieldset class="evt-tarjeta">
				<legend>La sección</legend>

				<div class="evt-form-fila">
					<div>
						<?php if ( $nueva ) : ?>
							<label for="evt_section_type">Tipo de sección</label>
							<select id="evt_section_type" name="<?php echo esc_attr( EventMetaKeys::SECTION_TYPE ); ?>" required>
								<option value="">— Elija el tipo —</option>
								<?php foreach ( (array) $m['types'] as $slug => $rotulo ) : ?>
									<option value="<?php echo esc_attr( (string) $slug ); ?>" <?php selected( (string) $valores['section_type'], (string) $slug ); ?>>
										<?php echo esc_html( (string) $rotulo ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<small>Se elige ahora y no se cambia después: el tipo decide qué elementos
								lleva la sección.</small>
							<?php self::field_error( $errores, array( 'section_type' ), 'Elija el tipo de sección de la lista.' ); ?>
						<?php else : ?>
							<?php
							$tipos  = (array) $m['types'];
							$actual = (string) $valores['section_type'];
							$rotulo = (string) ( $tipos[ $actual ] ?? $actual );
							?>
							<span class="evt-rotulo">Tipo de sección</span>
							<p class="evt-fijo">
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
									stroke-width="2" aria-hidden="true" focusable="false"><rect x="3" y="11"
									width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
								<strong><?php echo esc_html( '' !== $rotulo ? $rotulo : 'Sin tipo' ); ?></strong>
							</p>
							<small>No se cambia: una sección de programa y una de contacto no llevan lo
								mismo dentro. Si se equivocó, envíe esta a la papelera y cree la que
								quería.</small>
						<?php endif; ?>
					</div>
					<div>
						<label for="evt_order">Orden</label>
						<input type="number" id="evt_order" name="evt_order" step="1" min="0"
							value="<?php echo esc_attr( (string) $valores['menu_order'] ); ?>" />
						<small>El lugar que ocupa en el menú del evento. El número más bajo va primero.</small>
					</div>
				</div>

				<div class="evt-form-campo">
					<label for="evt_title">Título de la sección</label>
					<input type="text" id="evt_title" name="evt_title" required data-evt-slug-source
						value="<?php echo esc_attr( (string) $valores['title'] ); ?>" />
					<?php self::field_error( $errores, array( 'title' ), 'Escriba el título de la sección.' ); ?>
				</div>

				<div class="evt-form-campo">
					<label for="evt_slug">Dirección de la página</label>
					<input type="text" id="evt_slug" name="evt_slug" data-evt-slug-target
						value="<?php echo esc_attr( (string) $valores['slug'] ); ?>" />
					<small>
						Es el final de la dirección de la sección, y se propone a partir del título mientras no
						la escriba usted. Cambiar la de una sección ya publicada rompe los enlaces que apuntan a ella.
					</small>
				</div>
			</fieldset>

			<fieldset class="evt-tarjeta">
				<legend>Contenido</legend>
				<?php
				// El editor de WordPress, el mismo que en el escritorio. El HTML se
				// filtra al guardar, aquí no hay `unfiltered_html`.
				wp_editor(
					(string) $valores['content'],
					'evt_page_content',
					array(
						'textarea_name' => 'evt_content',
						'textarea_rows' => 14,
						'media_buttons' => true,
					)
				);
				?>
			</fieldset>

			<?php echo self::look( (array) $valores['look'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>

			<?php echo self::code( (array) $m['code'], (array) $valores['code'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>

			<p class="evt-acciones">
				<button class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" type="submit">
					<?php echo esc_html( $nueva ? 'Crear la sección' : 'Guardar los cambios' ); ?>
				</button>
				<?php if ( '' !== (string) $m['event_url'] ) : ?>
					<a class="<?php echo esc_attr( Assets::button_class() ); ?>" href="<?php echo esc_url( (string) $m['event_url'] ); ?>">
						Volver a las secciones del evento
					</a>
				<?php endif; ?>
			</p>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The folded «Apariencia de esta sección» block.
	 *
	 * Plegado porque casi nunca se toca: lo normal es que la sección se vea
	 * como su evento, y lo que se deja en blanco es justamente eso.
	 *
	 * @param array<string, string> $look Current appearance values, by meta key.
	 * @return string
	 */
	private static function look( array $look ): string {
		ob_start();
		?>
		<details class="evt-tarjeta">
			<summary>Apariencia de esta sección</summary>
			<p>Lo que deje en blanco se hereda del evento. Solo hace falta tocarlo cuando esta sección tenga que verse distinta.</p>

			<div class="evt-form-fila">
				<?php
				echo self::color_field( EventMetaKeys::HEADER_BG, 'Color de fondo de la cabecera', (string) ( $look[ EventMetaKeys::HEADER_BG ] ?? '' ) );    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
				echo self::color_field( EventMetaKeys::HEADER_TEXT, 'Color del texto de la cabecera', (string) ( $look[ EventMetaKeys::HEADER_TEXT ] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
				?>
			</div>

			<div class="evt-form-fila">
				<?php
				echo self::select_field( EventMetaKeys::TITLE_FONT, 'Tipografía de los títulos', EventMetaKeys::fonts(), (string) ( $look[ EventMetaKeys::TITLE_FONT ] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
				echo self::select_field( EventMetaKeys::BODY_FONT, 'Tipografía del cuerpo', EventMetaKeys::fonts(), (string) ( $look[ EventMetaKeys::BODY_FONT ] ?? '' ) );      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
				?>
			</div>

			<div class="evt-form-fila">
				<?php
				echo self::select_field( EventMetaKeys::IMAGE_SHAPE, 'Forma de las imágenes de personas', EventMetaKeys::image_shapes(), (string) ( $look[ EventMetaKeys::IMAGE_SHAPE ] ?? '' ), 'Sin elegir' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
				echo self::select_field( EventMetaKeys::SEPARATOR, 'Separador al pie de la cabecera', EventMetaKeys::separators(), (string) ( $look[ EventMetaKeys::SEPARATOR ] ?? '' ) );                       // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
				?>
			</div>

			<?php echo self::logo_field( (int) ( $look[ EventMetaKeys::LOGO_ID ] ?? 0 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		</details>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The code of this page: the CSS as a plain block, the JavaScript in yellow.
	 *
	 * Cada campo por separado y cada uno con su forma, porque son dos permisos
	 * distintos y dos riesgos distintos. El CSS es un bloque corriente de la
	 * sección —lo escribe también quien organiza el evento, acotado a su área—;
	 * el JavaScript va en el recuadro amarillo de «Solo administración» porque
	 * se ejecuta en el navegador de quien visite la página.
	 *
	 * El campo que no se puede escribir no se pinta: esconder el contenido y
	 * dejar el campo no es esconder nada. Pero a quien sí escribe el CSS se le
	 * dice en una línea por qué no está el otro, para que no lo busque.
	 *
	 * @param array<string, bool>   $puede   Meta key => whether this person may write it.
	 * @param array<string, string> $valores Meta key => what is stored, raw.
	 * @return string
	 */
	private static function code( array $puede, array $valores ): string {
		$css_ok = ! empty( $puede[ EventMetaKeys::CUSTOM_CSS ] );
		$js_ok  = ! empty( $puede[ EventMetaKeys::CUSTOM_JS ] );
		$html   = '';

		if ( $css_ok ) {
			$campo = CodeEditor::field(
				array(
					'mode'  => CodeEditor::MODE_CSS,
					'name'  => EventMetaKeys::CUSTOM_CSS,
					'label' => 'CSS de esta sección',
					'help'  => 'Se aplica solo a esta página y después del CSS del evento, así que sirve para afinar lo que herede de él. No sale de las páginas de este evento.',
					'value' => (string) ( $valores[ EventMetaKeys::CUSTOM_CSS ] ?? '' ),
				)
			);

			ob_start();
			?>
			<fieldset class="evt-tarjeta">
				<legend>CSS a medida de esta sección</legend>
				<p>Para los retoques que «Apariencia de esta sección» no cubre. Déjelo vacío si no hace falta ninguno.</p>
				<?php echo $campo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<?php if ( ! $js_ok ) : ?>
					<p>
						El JavaScript a medida de esta sección no aparece aquí porque solo lo escribe quien
						administra el aplicativo: ejecuta un programa en el navegador de cada visitante. No le
						falta nada más.
					</p>
				<?php endif; ?>
			</fieldset>
			<?php
			$html .= (string) ob_get_clean();
		}

		if ( $js_ok ) {
			$html .= Shell::admin_box(
				'JavaScript a medida de esta sección',
				CodeEditor::field(
					array(
						'mode'  => CodeEditor::MODE_JS,
						'name'  => EventMetaKeys::CUSTOM_JS,
						'label' => 'JavaScript de esta sección',
						'help'  => 'Se ejecuta solo en esta página y después del JavaScript del evento. Lo ejecuta el navegador de cada persona que la visite: un error aquí le rompe la página a todo el mundo, así que pruébelo antes de publicar la sección.',
						'value' => (string) ( $valores[ EventMetaKeys::CUSTOM_JS ] ?? '' ),
					)
				),
				'El JavaScript a medida se ejecuta en el navegador de quien visite esta página, así que solo lo escribe quien administra el aplicativo.'
			);
		}

		return $html;
	}

	/**
	 * A colour: the native picker next to the hexadecimal it writes.
	 *
	 * El que viaja es el campo de texto; el selector no lleva `name` a
	 * propósito, para que el dato sea uno solo y quien tenga el guion
	 * bloqueado siga pudiendo teclear el hexadecimal.
	 *
	 * @param string $key   Meta key, which is also the field name.
	 * @param string $label What it says.
	 * @param string $value Current value.
	 * @return string
	 */
	private static function color_field( string $key, string $label, string $value ): string {
		ob_start();
		?>
		<div class="evt-form-campo">
			<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
			<div class="evt-color">
				<input type="color" aria-label="<?php echo esc_attr( sprintf( '%s: elegirlo con el selector', $label ) ); ?>"
					data-evt-color-for="<?php echo esc_attr( $key ); ?>"
					value="<?php echo esc_attr( '' !== $value ? $value : '#ffffff' ); ?>" />
				<input type="text" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>"
					inputmode="text" maxlength="7" pattern="#[0-9a-fA-F]{6}" placeholder="#1b4f8a"
					value="<?php echo esc_attr( $value ); ?>" />
			</div>
			<small>Hexadecimal, con la almohadilla. En blanco, el del evento.</small>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * A closed list.
	 *
	 * @param string                $key     Meta key, which is also the field name.
	 * @param string                $label   What it says.
	 * @param array<string, string> $options slug => rótulo.
	 * @param string                $value   Current value.
	 * @param string                $blank   Label of the empty option; '' when the list already has one.
	 * @return string
	 */
	private static function select_field( string $key, string $label, array $options, string $value, string $blank = '' ): string {
		ob_start();
		?>
		<div class="evt-form-campo">
			<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
			<select id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>">
				<?php if ( '' !== $blank ) : ?>
					<option value=""><?php echo esc_html( $blank ); ?></option>
				<?php endif; ?>
				<?php foreach ( $options as $slug => $rotulo ) : ?>
					<option value="<?php echo esc_attr( (string) $slug ); ?>" <?php selected( $value, (string) $slug ); ?>>
						<?php echo esc_html( (string) $rotulo ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The accompanying logo of this page, by attachment ID.
	 *
	 * @param int $logo_id Attachment ID, 0 when there is none.
	 * @return string
	 */
	private static function logo_field( int $logo_id ): string {
		$miniatura = $logo_id > 0 ? (string) wp_get_attachment_image_url( $logo_id, 'thumbnail' ) : '';

		ob_start();
		?>
		<div class="evt-form-campo">
			<label for="<?php echo esc_attr( EventMetaKeys::LOGO_ID ); ?>">Logo acompañante de esta sección</label>
			<input type="number" id="<?php echo esc_attr( EventMetaKeys::LOGO_ID ); ?>"
				name="<?php echo esc_attr( EventMetaKeys::LOGO_ID ); ?>" step="1" min="0"
				value="<?php echo esc_attr( (string) ( $logo_id > 0 ? $logo_id : '' ) ); ?>" />
			<small>
				Número del archivo en la biblioteca de medios. Se ve en la barra de direcciones al abrirlo
				en <a href="<?php echo esc_url( admin_url( 'upload.php' ) ); ?>">Medios</a>. En blanco, el del evento.
			</small>
			<?php if ( '' !== $miniatura ) : ?>
				<img class="evt-preview-logo" src="<?php echo esc_url( $miniatura ); ?>" alt="Logo elegido para esta sección" />
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Inline error next to a field the last submit flagged.
	 *
	 * @param string[] $errors Error codes.
	 * @param string[] $codes  Codes attached to this field.
	 * @param string   $text   What to say.
	 * @return void Echoes the escaped message.
	 */
	private static function field_error( array $errors, array $codes, string $text ): void {
		if ( array() === array_intersect( $codes, $errors ) ) {
			return;
		}
		echo '<span class="evt-error" role="alert">' . esc_html( $text ) . '</span>';
	}

	/**
	 * A way out when there is no form to paint.
	 *
	 * @return string
	 */
	private static function back_to_events(): string {
		$url = Shell::url( 'events' );
		if ( '' === $url ) {
			return '';
		}
		return '<p class="evt-acciones"><a class="' . esc_attr( Assets::button_class() ) . '" href="'
			. esc_url( $url ) . '">Ir a mis eventos</a></p>';
	}
}
