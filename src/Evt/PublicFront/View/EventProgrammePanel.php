<?php
/**
 * The «Programa» panel of the event workshop: the parrilla.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

use Evt\PublicFront\Assets;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\Shell;

/**
 * La parrilla del evento: **por día y, dentro del día, por sede**.
 *
 * Esa forma es la decisión de ADR-0024: la sede es un dato de cada actividad y
 * un mismo día puede tener dos —la mañana en una sede y la tarde en un centro
 * educativo—. Un día con una sola sede se pinta como un bloque **y sin rótulo
 * de sede**, que no aportaría nada; uno con dos, como dos bloques con su
 * rótulo. Y no hay ninguna pantalla donde «dar de alta una sede»: la lista
 * sale de las actividades, y añadir una es escribirla.
 *
 * Solo pinta: no lee la petición, no consulta, no decide.
 */
final class EventProgrammePanel {

	/**
	 * Paint the panel.
	 *
	 * @param array<string, mixed> $m What EventWorkspace::model() returned.
	 * @return string
	 */
	public static function html( array $m ): string {
		$dias = (array) $m['grid'];

		ob_start();
		?>
		<p class="evt-sub">
			Lo que pasa y cuándo. Se agrupa por día y, dentro de cada día, por
			sede: si una jornada tiene la mañana en un sitio y la tarde en otro,
			salen los dos bloques. La sede se escribe en cada actividad; no hay
			que darla de alta en ninguna parte.
		</p>

		<?php echo PanelParts::trash_link( $m, EventWorkspace::PANEL_PROGRAMME ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		<?php echo self::form( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>

		<?php if ( array() === $dias ) : ?>
			<div class="evt-tabla-caja">
				<p class="evt-vacio">El programa está vacío. Añada la primera actividad arriba.</p>
			</div>
		<?php else : ?>
			<?php foreach ( $dias as $dia ) : ?>
				<?php echo self::day( $m, (array) $dia ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			<?php endforeach; ?>
		<?php endif; ?>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * One day of the parrilla, with its one or more sedes.
	 *
	 * @param array<string, mixed> $m   Model.
	 * @param array<string, mixed> $dia One day of $m['grid'].
	 * @return string
	 */
	private static function day( array $m, array $dia ): string {
		$sedes = (array) $dia['venues'];
		// Con una sola sede el rótulo sobra: repetir el mismo sitio encima de
		// cada bloque es ruido, y ADR-0024 lo dice con esas palabras.
		$rotular = count( $sedes ) > 1;

		ob_start();
		?>
		<section class="evt-tarjeta">
			<h2><?php echo esc_html( PanelParts::day( (string) $dia['date'] ) ); ?></h2>
			<?php foreach ( $sedes as $sede ) : ?>
				<?php if ( $rotular ) : ?>
					<h3 class="evt-sub">
						<?php echo esc_html( '' !== trim( (string) $sede['venue'] ) ? (string) $sede['venue'] : 'Sin sede indicada' ); ?>
					</h3>
				<?php endif; ?>
				<div class="evt-tabla-caja">
					<table class="evt-tabla">
						<thead>
							<tr>
								<th scope="col">Hora</th>
								<th scope="col">Tipo</th>
								<th scope="col">Actividad</th>
								<th scope="col">Ponentes</th>
								<th scope="col">Sala</th>
								<th scope="col">Acciones</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( (array) $sede['rows'] as $fila ) : ?>
								<?php echo self::row( $m, (array) $fila ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endforeach; ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * One activity row.
	 *
	 * @param array<string, mixed> $m    Model.
	 * @param array<string, mixed> $fila Activity row.
	 * @return string
	 */
	private static function row( array $m, array $fila ): string {
		$id     = (int) $fila['id'];
		$titulo = '' !== trim( (string) $fila['title'] ) ? (string) $fila['title'] : '(sin título)';
		$mini   = Assets::button_class() . ' evt-mini';
		$borrar = PanelParts::action(
			$m,
			$id,
			'row_delete',
			'papelera',
			'Enviar a la papelera',
			$mini . ' evt-btn-borrar',
			EventWorkspace::PANEL_PROGRAMME,
			false,
			sprintf( '¿Enviar «%s» a la papelera? Desaparecerá del programa.', $titulo )
		);

		ob_start();
		?>
		<tr>
			<td data-rotulo="Hora"><?php echo esc_html( PanelParts::slot( (string) $fila['start'], (string) $fila['end'] ) ); ?></td>
			<td data-rotulo="Tipo"><?php echo esc_html( (string) $fila['kind_label'] ); ?></td>
			<td data-rotulo="Actividad"><?php echo esc_html( $titulo ); ?></td>
			<td data-rotulo="Ponentes">
				<?php echo esc_html( array() === (array) $fila['speakers'] ? '—' : implode( ', ', (array) $fila['speakers'] ) ); ?>
			</td>
			<td data-rotulo="Sala"><?php echo esc_html( '' !== (string) $fila['room'] ? (string) $fila['room'] : '—' ); ?></td>
			<td data-rotulo="Acciones">
				<span class="evt-acciones">
					<?php
					$editar = PanelParts::icon_link(
						EventWorkspace::url( (int) $m['event_id'], EventWorkspace::PANEL_PROGRAMME, array( EventWorkspace::ARG_ROW => $id ) ),
						'lapiz',
						'Editar esta actividad'
					);
					echo $editar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
					?>
					<?php echo $borrar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				</span>
			</td>
		</tr>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The form that adds an activity, or edits the one asked for.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function form( array $m ): string {
		$valores = (array) $m['edit_values'];
		$id      = isset( $valores['id'] ) ? (int) $valores['id'] : 0;
		$editar  = $id > 0;
		$op      = EventWorkspace::OP_ACTIVITY;
		$suyos   = isset( $valores['speaker_ids'] ) ? (array) $valores['speaker_ids'] : array();

		ob_start();
		?>
		<form class="evt-form evt-tarjeta" method="post" action="">
			<h2><?php echo esc_html( $editar ? 'Editar actividad' : 'Añadir actividad al programa' ); ?></h2>
			<?php wp_nonce_field( EventWorkspace::nonce_action( $op ), EventWorkspace::nonce_name( $op ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_EVENT ); ?>" value="<?php echo esc_attr( (string) (int) $m['event_id'] ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_ROW ); ?>" value="<?php echo esc_attr( (string) $id ); ?>" />

			<div class="evt-form-fila">
				<div class="evt-form-campo">
					<label for="evt-ac-title">Título</label>
					<input type="text" id="evt-ac-title" name="evt_ac_title" required
						value="<?php echo esc_attr( (string) ( $valores['title'] ?? '' ) ); ?>" />
				</div>
				<div class="evt-form-campo">
					<label for="evt-ac-kind">Tipo</label>
					<select id="evt-ac-kind" name="evt_ac_kind">
						<?php foreach ( (array) $m['kinds'] as $slug => $rotulo ) : ?>
							<option value="<?php echo esc_attr( (string) $slug ); ?>"
								<?php selected( (string) ( $valores['kind'] ?? 'ponencia' ), (string) $slug ); ?>>
								<?php echo esc_html( (string) $rotulo ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<small>«Taller» es el único que lleva aforo y aparece en la pestaña de Talleres.</small>
				</div>
			</div>

			<div class="evt-form-fila">
				<div class="evt-form-campo">
					<label for="evt-ac-date">Día</label>
					<input type="date" id="evt-ac-date" name="evt_ac_date" required
						value="<?php echo esc_attr( (string) ( $valores['date'] ?? '' ) ); ?>" />
				</div>
				<div class="evt-form-campo">
					<label for="evt-ac-start">Hora de inicio</label>
					<input type="time" id="evt-ac-start" name="evt_ac_start"
						value="<?php echo esc_attr( (string) ( $valores['start'] ?? '' ) ); ?>" />
					<small>Se puede dejar vacía si todavía no está fijada.</small>
				</div>
				<div class="evt-form-campo">
					<label for="evt-ac-end">Hora de fin</label>
					<input type="time" id="evt-ac-end" name="evt_ac_end"
						value="<?php echo esc_attr( (string) ( $valores['end'] ?? '' ) ); ?>" />
				</div>
			</div>

			<div class="evt-form-fila">
				<div class="evt-form-campo">
					<label for="evt-ac-venue">Sede</label>
					<input type="text" id="evt-ac-venue" name="evt_ac_venue" list="evt-sedes"
						value="<?php echo esc_attr( (string) ( $valores['venue'] ?? '' ) ); ?>" />
					<datalist id="evt-sedes">
						<?php foreach ( (array) $m['venues'] as $sede ) : ?>
							<option value="<?php echo esc_attr( (string) $sede ); ?>"></option>
						<?php endforeach; ?>
					</datalist>
					<small>Dónde ocurre esta actividad. Si repite la de otra, elíjala de la lista y el día saldrá en un solo bloque.</small>
				</div>
				<div class="evt-form-campo">
					<label for="evt-ac-room">Sala</label>
					<input type="text" id="evt-ac-room" name="evt_ac_room"
						value="<?php echo esc_attr( (string) ( $valores['room'] ?? '' ) ); ?>" />
					<small>«Aula 2», «Salón de actos»…</small>
				</div>
				<div class="evt-form-campo">
					<label for="evt-ac-seats">Aforo</label>
					<input type="number" id="evt-ac-seats" name="evt_ac_seats" min="0" step="1"
						value="<?php echo esc_attr( (string) (int) ( $valores['seats'] ?? 0 ) ); ?>" />
					<small>Solo para talleres. <strong>0 es sin límite.</strong></small>
				</div>
			</div>

			<?php echo self::speakers( $m, $suyos ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>

			<div class="evt-form-campo">
				<label for="evt-ac-summary">Descripción</label>
				<textarea id="evt-ac-summary" name="evt_ac_summary" rows="3"><?php echo esc_textarea( (string) ( $valores['summary'] ?? '' ) ); ?></textarea>
			</div>

			<div class="evt-acciones">
				<button class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" type="submit">
					<?php echo $editar ? '' : wp_kses_post( Shell::icon_plus() ); ?>
					<?php echo esc_html( $editar ? 'Guardar actividad' : 'Añadir actividad' ); ?>
				</button>
				<?php if ( $editar ) : ?>
					<a class="<?php echo esc_attr( Assets::button_class() ); ?>"
						href="<?php echo esc_url( EventWorkspace::url( (int) $m['event_id'], EventWorkspace::PANEL_PROGRAMME ) ); ?>">Cancelar</a>
				<?php endif; ?>
			</div>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Which speakers of this event take part in this activity.
	 *
	 * Casillas y no un desplegable múltiple: se marcan varias sin saber que
	 * hay que dejar pulsada una tecla, que es el fallo de siempre del
	 * `<select multiple>`.
	 *
	 * @param array<string, mixed> $m     Model.
	 * @param int[]                $suyos Speaker IDs already linked.
	 * @return string
	 */
	private static function speakers( array $m, array $suyos ): string {
		$ponentes = (array) $m['speakers'];

		ob_start();
		?>
		<fieldset class="evt-form-campo">
			<legend>Ponentes</legend>
			<?php if ( array() === $ponentes ) : ?>
				<p class="evt-sub">
					Este evento todavía no tiene ponentes.
					<a href="<?php echo esc_url( EventWorkspace::url( (int) $m['event_id'], EventWorkspace::PANEL_SPEAKERS ) ); ?>">Añádalos en «Ponentes»</a>
					y vuelva: aquí solo salen los de este evento.
				</p>
			<?php else : ?>
				<?php foreach ( $ponentes as $ponente ) : ?>
					<?php $pid = (int) $ponente['id']; ?>
					<label class="evt-check">
						<input type="checkbox" name="evt_ac_speakers[]" value="<?php echo esc_attr( (string) $pid ); ?>"
							<?php checked( in_array( $pid, array_map( 'intval', $suyos ), true ), true ); ?> />
						<?php echo esc_html( (string) $ponente['name'] ); ?>
					</label>
				<?php endforeach; ?>
			<?php endif; ?>
		</fieldset>
		<?php
		return (string) ob_get_clean();
	}
}
