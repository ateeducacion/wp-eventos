<?php
/**
 * The «Ponentes» panel of the event workshop.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

use Evt\PublicFront\Assets;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\Shell;

/**
 * Los ponentes de este evento: su ficha, su orden y su foto.
 *
 * Son fichas **de este evento** y no de un catálogo compartido: la repetición
 * real entre eventos está entre el 5 % y el 12 %, y en un evento terminado no
 * se quiere que la foto y la biografía cambien solas (ADR-0021).
 *
 * Solo pinta: no lee la petición, no consulta, no decide.
 */
final class EventSpeakersPanel {

	/**
	 * Paint the panel.
	 *
	 * @param array<string, mixed> $m What EventWorkspace::model() returned.
	 * @return string
	 */
	public static function html( array $m ): string {
		$filas = (array) $m['speakers'];

		ob_start();
		?>
		<p class="evt-sub">
			Quién interviene en este evento. El orden es el que sale en la página
			de ponentes y en el programa. Cada ficha es de este evento: editarla
			no toca la de ninguna otra edición.
		</p>

		<?php echo PanelParts::trash_link( $m, EventWorkspace::PANEL_SPEAKERS ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		<?php echo self::form( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>

		<?php if ( array() === $filas ) : ?>
			<div class="evt-tabla-caja">
				<p class="evt-vacio">Este evento todavía no tiene ponentes. Añada el primero arriba.</p>
			</div>
		<?php else : ?>
			<div class="evt-tabla-caja">
				<table class="evt-tabla">
					<thead>
						<tr>
							<th scope="col">Orden</th>
							<th scope="col">Foto</th>
							<th scope="col">Nombre</th>
							<th scope="col">Cargo</th>
							<th scope="col">Entidad</th>
							<th scope="col">Acciones</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $filas as $indice => $fila ) : ?>
							<?php echo self::row( $m, (array) $fila, (int) $indice + 1 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The form that adds a speaker, or edits the one asked for.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function form( array $m ): string {
		$valores = (array) $m['edit_values'];
		$id      = isset( $valores['id'] ) ? (int) $valores['id'] : 0;
		$editar  = $id > 0;
		$op      = EventWorkspace::OP_SPEAKER;

		ob_start();
		?>
		<form class="evt-form evt-tarjeta" method="post" action="">
			<h2><?php echo esc_html( $editar ? 'Editar ponente' : 'Añadir ponente' ); ?></h2>
			<?php wp_nonce_field( EventWorkspace::nonce_action( $op ), EventWorkspace::nonce_name( $op ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_EVENT ); ?>" value="<?php echo esc_attr( (string) (int) $m['event_id'] ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_ROW ); ?>" value="<?php echo esc_attr( (string) $id ); ?>" />

			<div class="evt-form-fila">
				<div class="evt-form-campo">
					<label for="evt-sp-name">Nombre y apellidos</label>
					<input type="text" id="evt-sp-name" name="evt_sp_name" required
						value="<?php echo esc_attr( (string) ( $valores['name'] ?? '' ) ); ?>" />
					<small>Como quiera que salga en la web del evento.</small>
				</div>
				<div class="evt-form-campo">
					<label for="evt-sp-role">Cargo</label>
					<input type="text" id="evt-sp-role" name="evt_sp_role"
						value="<?php echo esc_attr( (string) ( $valores['role'] ?? '' ) ); ?>" />
					<small>«Asesora de formación», «Catedrático de Secundaria»… Se puede dejar vacío.</small>
				</div>
				<div class="evt-form-campo">
					<label for="evt-sp-org">Entidad o centro</label>
					<input type="text" id="evt-sp-org" name="evt_sp_org"
						value="<?php echo esc_attr( (string) ( $valores['org'] ?? '' ) ); ?>" />
				</div>
			</div>

			<div class="evt-form-campo">
				<label for="evt-sp-bio">Biografía</label>
				<textarea id="evt-sp-bio" name="evt_sp_bio" rows="4"><?php echo esc_textarea( (string) ( $valores['bio'] ?? '' ) ); ?></textarea>
				<small>Unas líneas. Sale debajo del nombre en la página de ponentes.</small>
			</div>

			<?php echo self::photo( $m, $valores ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>

			<div class="evt-acciones">
				<button class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" type="submit">
					<?php echo $editar ? '' : wp_kses_post( Shell::icon_plus() ); ?>
					<?php echo esc_html( $editar ? 'Guardar ponente' : 'Añadir ponente' ); ?>
				</button>
				<?php if ( $editar ) : ?>
					<a class="<?php echo esc_attr( Assets::button_class() ); ?>"
						href="<?php echo esc_url( EventWorkspace::url( (int) $m['event_id'], EventWorkspace::PANEL_SPEAKERS ) ); ?>">Cancelar</a>
				<?php endif; ?>
			</div>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The photo field: what there is, and how to change it.
	 *
	 * Sin selector de medios cuando quien mira no puede subir: se le enseña lo
	 * que hay y nada más, porque la biblioteca tampoco se le abriría.
	 *
	 * @param array<string, mixed> $m       Model.
	 * @param array<string, mixed> $valores Speaker being edited.
	 * @return string
	 */
	private static function photo( array $m, array $valores ): string {
		$url = (string) ( $valores['photo'] ?? '' );
		$id  = (int) ( $valores['photo_id'] ?? 0 );

		ob_start();
		?>
		<div class="evt-form-campo evt-media" data-evt-media data-evt-media-type="image"
			data-evt-media-title="Elegir la foto del ponente" data-evt-media-button="Usar esta imagen">
			<span class="evt-media-rotulo">Foto</span>
			<input type="hidden" id="evt-sp-photo" name="evt_sp_photo" value="<?php echo esc_attr( (string) $id ); ?>" data-evt-media-value />
			<div class="evt-media-ficha" data-evt-media-card <?php echo esc_attr( '' !== $url ? '' : 'hidden' ); ?>>
				<img class="evt-media-miniatura" src="<?php echo esc_url( $url ); ?>" alt="" width="96" height="96" data-evt-media-thumb />
				<span class="evt-media-datos"><strong data-evt-media-name>Foto del ponente</strong><small data-evt-media-size></small></span>
			</div>
			<p class="evt-media-vacia" data-evt-media-empty <?php echo esc_attr( '' !== $url ? 'hidden' : '' ); ?>>Sin foto. La ficha se ve igual, con las iniciales.</p>
			<?php if ( true === $m['can_upload'] ) : ?>
				<p class="evt-media-drop">Arrastre una imagen hasta este campo o selecciónela en la biblioteca.</p>
				<p class="evt-media-estado" data-evt-media-status aria-live="polite"></p>
				<p class="evt-acciones evt-media-botones" data-evt-media-actions hidden>
					<button type="button" class="<?php echo esc_attr( Assets::button_class() . ' evt-mini' ); ?>" data-evt-media-pick>Seleccionar o subir</button>
					<button type="button" class="<?php echo esc_attr( Assets::button_class() . ' evt-mini' ); ?>" data-evt-media-clear <?php echo esc_attr( '' !== $url ? '' : 'hidden' ); ?>>Eliminar del campo</button>
				</p>
			<?php endif; ?>
			<small>La ventana de WordPress permite elegir una imagen existente, previsualizarla o arrastrar una nueva desde su equipo.</small>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * One speaker row, with its actions.
	 *
	 * @param array<string, mixed> $m       Model.
	 * @param array<string, mixed> $fila    Speaker row.
	 * @param int                  $posicion 1-based position.
	 * @return string
	 */
	private static function row( array $m, array $fila, int $posicion ): string {
		$id     = (int) $fila['id'];
		$nombre = '' !== trim( (string) $fila['name'] ) ? (string) $fila['name'] : '(sin nombre)';
		$mini   = Assets::button_class() . ' evt-mini';

		$subir  = PanelParts::action( $m, $id, 'sp_up', 'subir', 'Subir una posición', $mini, EventWorkspace::PANEL_SPEAKERS, (bool) $fila['first'] );
		$bajar  = PanelParts::action( $m, $id, 'sp_down', 'bajar', 'Bajar una posición', $mini, EventWorkspace::PANEL_SPEAKERS, (bool) $fila['last'] );
		$borrar = PanelParts::action(
			$m,
			$id,
			'row_delete',
			'papelera',
			'Enviar a la papelera',
			$mini . ' evt-btn-borrar',
			EventWorkspace::PANEL_SPEAKERS,
			false,
			sprintf( '¿Enviar a «%s» a la papelera? Dejará de salir en el evento y en las actividades donde esté.', $nombre )
		);

		ob_start();
		?>
		<tr>
			<td class="evt-num" data-rotulo="Orden"><?php echo esc_html( (string) $posicion ); ?></td>
			<td data-rotulo="Foto">
				<?php if ( '' !== (string) $fila['photo'] ) : ?>
					<img class="evt-media-miniatura" src="<?php echo esc_url( (string) $fila['photo'] ); ?>" alt="" width="48" height="48" />
				<?php else : ?>
					<span class="evt-sub">—</span>
				<?php endif; ?>
			</td>
			<td data-rotulo="Nombre"><?php echo esc_html( $nombre ); ?></td>
			<td data-rotulo="Cargo"><?php echo esc_html( '' !== (string) $fila['role'] ? (string) $fila['role'] : '—' ); ?></td>
			<td data-rotulo="Entidad"><?php echo esc_html( '' !== (string) $fila['org'] ? (string) $fila['org'] : '—' ); ?></td>
			<td data-rotulo="Acciones">
				<span class="evt-acciones">
					<?php
					$editar = PanelParts::icon_link(
						EventWorkspace::url( (int) $m['event_id'], EventWorkspace::PANEL_SPEAKERS, array( EventWorkspace::ARG_ROW => $id ) ),
						'lapiz',
						'Editar este ponente'
					);
					echo $editar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
					?>
					<?php echo $subir; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
					<?php echo $bajar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
					<?php echo $borrar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				</span>
			</td>
		</tr>
		<?php
		return (string) ob_get_clean();
	}
}
