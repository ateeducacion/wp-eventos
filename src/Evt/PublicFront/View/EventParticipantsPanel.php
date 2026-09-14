<?php
/**
 * The «Participantes» panel of the event workshop.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

use Evt\PublicFront\Assets;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\Participants;

/**
 * Quién se ha inscrito: filtro y exportación a CSV.
 *
 * **De dónde salen las filas no lo decide esta pantalla.** Los participantes
 * son de este aplicativo y se gestionarán aquí, con formulario de inscripción
 * propio; mientras ese formulario no exista, las filas entran por el filtro
 * `evt_participants` (ADR-0027). Cuando nadie contesta, aquí no se finge una
 * lista vacía: se dice que la inscripción está por construir.
 *
 * Solo pinta: no lee la petición, no consulta, no decide.
 */
final class EventParticipantsPanel {

	/**
	 * Paint the panel.
	 *
	 * @param array<string, mixed> $m What EventWorkspace::model() returned.
	 * @return string
	 */
	public static function html( array $m ): string {
		if ( 0 === (int) $m['people_total'] ) {
			return self::empty_state( $m );
		}

		$filas = (array) $m['people'];

		ob_start();
		?>
		<p class="evt-sub">
			Quién se ha inscrito a este evento. Se puede filtrar por cualquier dato
			—un apellido, un centro, un taller— y exportar a CSV lo que quede
			filtrado, no la lista entera.
		</p>

		<?php echo self::counters( $m, $filas ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		<?php echo self::filter( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>

		<?php if ( array() === $filas ) : ?>
			<div class="evt-tabla-caja">
				<p class="evt-vacio">Ninguna inscripción encaja con el filtro. Vacíelo para verlas todas.</p>
			</div>
		<?php else : ?>
			<div class="evt-tabla-caja">
				<table class="evt-tabla">
					<thead>
						<tr>
							<?php foreach ( (array) $m['people_cols'] as $rotulo ) : ?>
								<th scope="col"><?php echo esc_html( (string) $rotulo ); ?></th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $filas as $fila ) : ?>
							<tr>
								<?php foreach ( (array) $m['people_cols'] as $clave => $rotulo ) : ?>
									<td data-rotulo="<?php echo esc_attr( (string) $rotulo ); ?>">
										<?php echo esc_html( '' !== (string) ( $fila[ $clave ] ?? '' ) ? (string) $fila[ $clave ] : '—' ); ?>
									</td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * How many there are, and how many the filter left.
	 *
	 * @param array<string, mixed> $m     Model.
	 * @param array<int, mixed>    $filas Filtered rows.
	 * @return string
	 */
	private static function counters( array $m, array $filas ): string {
		$total    = (int) $m['people_total'];
		$filtrado = count( $filas );

		ob_start();
		?>
		<ul class="evt-cifras">
			<li class="evt-cifra"><strong><?php echo esc_html( (string) $total ); ?></strong> inscripciones</li>
			<?php if ( $filtrado !== $total ) : ?>
				<li class="evt-cifra"><strong><?php echo esc_html( (string) $filtrado ); ?></strong> con el filtro puesto</li>
			<?php endif; ?>
			<li class="evt-cifra"><strong><?php echo esc_html( (string) count( (array) $m['people_tags'] ) ); ?></strong> talleres elegidos</li>
		</ul>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The filter box and the export button.
	 *
	 * El filtro va por GET —es una consulta, y así la dirección filtrada se
	 * puede guardar y compartir— y la exportación por POST con su nonce,
	 * porque un enlace que descarga la lista entera de personas inscritas no
	 * debe poder pegarse en un correo.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function filter( array $m ): string {
		$op   = EventWorkspace::OP_EXPORT;
		$base = EventWorkspace::url( (int) $m['event_id'], EventWorkspace::PANEL_PEOPLE );

		// El formulario de filtro es un GET a esta misma pantalla: lo que ya
		// viaja en la dirección se vuelve a poner como campos ocultos.
		$accion  = (string) strtok( $base, '?' );
		$ocultos = array();
		parse_str( (string) wp_parse_url( $base, PHP_URL_QUERY ), $ocultos );
		unset( $ocultos[ EventWorkspace::ARG_Q ], $ocultos[ EventWorkspace::ARG_WORKSHOP ] );

		ob_start();
		?>
		<div class="evt-filtro">
			<form class="evt-form" method="get" action="<?php echo esc_url( $accion ); ?>">
				<?php foreach ( $ocultos as $clave => $valor ) : ?>
					<input type="hidden" name="<?php echo esc_attr( (string) $clave ); ?>" value="<?php echo esc_attr( (string) $valor ); ?>" />
				<?php endforeach; ?>
				<div class="evt-form-campo">
					<label for="evt-people-q">Buscar</label>
					<input type="search" id="evt-people-q" name="<?php echo esc_attr( EventWorkspace::ARG_Q ); ?>"
						value="<?php echo esc_attr( (string) $m['people_q'] ); ?>" placeholder="Apellido, centro, correo…" />
				</div>
				<?php if ( array() !== (array) $m['people_tags'] ) : ?>
					<div class="evt-form-campo">
						<label for="evt-people-taller">Taller</label>
						<select id="evt-people-taller" name="<?php echo esc_attr( EventWorkspace::ARG_WORKSHOP ); ?>">
							<option value="">Todos</option>
							<?php foreach ( (array) $m['people_tags'] as $taller ) : ?>
								<option value="<?php echo esc_attr( (string) $taller ); ?>"
									<?php selected( (string) $m['people_filter'], (string) $taller ); ?>>
									<?php echo esc_html( (string) $taller ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php endif; ?>
				<div class="evt-acciones">
					<button class="<?php echo esc_attr( Assets::button_class() ); ?>" type="submit">Filtrar</button>
					<?php if ( '' !== (string) $m['people_q'] || '' !== (string) $m['people_filter'] ) : ?>
						<a class="<?php echo esc_attr( Assets::button_class() ); ?>" href="<?php echo esc_url( $base ); ?>">Quitar el filtro</a>
					<?php endif; ?>
				</div>
			</form>

			<form class="evt-form" method="post" action="">
				<?php wp_nonce_field( EventWorkspace::nonce_action( $op ), EventWorkspace::nonce_name( $op ), false ); ?>
				<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_EVENT ); ?>" value="<?php echo esc_attr( (string) (int) $m['event_id'] ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( EventWorkspace::ARG_Q ); ?>" value="<?php echo esc_attr( (string) $m['people_q'] ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( EventWorkspace::ARG_WORKSHOP ); ?>" value="<?php echo esc_attr( (string) $m['people_filter'] ); ?>" />
				<div class="evt-acciones">
					<button class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" type="submit">Exportar a CSV</button>
				</div>
			</form>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * What to say when no source answered.
	 *
	 * No es una tabla vacía: una tabla vacía se lee como «no se ha inscrito
	 * nadie», y lo que pasa es que las inscripciones están en otro sitio.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function empty_state( array $m ): string {
		$viejo = (int) $m['form_id'];

		ob_start();
		?>
		<section class="evt-tarjeta">
			<h2>Todavía no hay nadie inscrito</h2>
			<p>
				Los participantes de este evento se gestionan <strong>aquí</strong>:
				cuando alguien se inscriba saldrá en esta tabla, y desde ella podrá
				filtrar y exportar a CSV.
			</p>
			<p>
				<strong>El formulario de inscripción todavía está por construir.</strong>
				Hasta que lo esté, esta pestaña no tiene de dónde sacar a nadie y se
				queda así. No es un fallo del evento.
			</p>
			<?php if ( $viejo > 0 ) : ?>
				<p>
					Este evento viene del sistema anterior y todavía apunta a su
					<strong>formulario antiguo, el número <?php echo esc_html( (string) $viejo ); ?></strong>.
					Esas inscripciones se siguen consultando allí y
					<strong>no se traen a esta pantalla</strong>; el campo está en
					<a href="<?php echo esc_url( EventWorkspace::url( (int) $m['event_id'], EventWorkspace::PANEL_SETTINGS ) ); ?>">Ajustes</a>
					marcado como histórico, y desaparecerá.
				</p>
			<?php endif; ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}
