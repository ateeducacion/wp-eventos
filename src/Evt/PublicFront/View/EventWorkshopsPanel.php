<?php
/**
 * The «Talleres» panel of the event workshop.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

use Evt\PublicFront\Assets;
use Evt\PublicFront\EventWorkspace;

/**
 * Los talleres del evento y **cuántas plazas quedan**.
 *
 * Un taller no es otro tipo de contenido: es una actividad del programa cuyo
 * tipo es «taller», y por eso se crea y se edita en «Programa». Aquí se ven
 * juntos, con su aforo, porque esa es la pregunta que se hace el día antes:
 * «¿cuál se ha llenado?».
 *
 * Solo pinta: no lee la petición, no consulta, no decide.
 */
final class EventWorkshopsPanel {

	/**
	 * Paint the panel.
	 *
	 * @param array<string, mixed> $m What EventWorkspace::model() returned.
	 * @return string
	 */
	public static function html( array $m ): string {
		$filas = (array) $m['workshops'];

		ob_start();
		?>
		<p class="evt-sub">
			Las actividades del programa marcadas como <strong>taller</strong>, con
			su aforo. Se crean y se editan en
			<a href="<?php echo esc_url( EventWorkspace::url( (int) $m['event_id'], EventWorkspace::PANEL_PROGRAMME ) ); ?>">Programa</a>:
			un taller es una actividad más, con plazas.
		</p>

		<?php if ( array() === $filas ) : ?>
			<div class="evt-tabla-caja">
				<p class="evt-vacio">
					Este evento no tiene talleres. Para crear uno, añada una actividad
					en «Programa» y elija el tipo «Taller».
				</p>
			</div>
		<?php else : ?>
			<?php echo self::counters( $m, $filas ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			<div class="evt-tabla-caja">
				<table class="evt-tabla">
					<thead>
						<tr>
							<th scope="col">Día</th>
							<th scope="col">Hora</th>
							<th scope="col">Taller</th>
							<th scope="col">Sede y sala</th>
							<th scope="col">Aforo</th>
							<th scope="col">Plazas</th>
							<th scope="col">Acciones</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $filas as $fila ) : ?>
							<?php echo self::row( $m, (array) $fila ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<p class="evt-sub">
				Las plazas ocupadas se cuentan cruzando el <strong>título del taller</strong>
				con lo que eligió cada persona al inscribirse, porque la inscripción
				todavía no es de este aplicativo y no hay un identificador común. Así
				que <strong>si le cambia el título a un taller ya empezado, la cuenta
				deja de cuadrar</strong>. Cuando el formulario de inscripción sea
				nuestro, se cruzará por identificador y esto dejará de pasar.
			</p>
		<?php endif; ?>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The three numbers that answer «¿cómo va esto?».
	 *
	 * @param array<string, mixed> $m     Model.
	 * @param array<int, mixed>    $filas Workshop rows.
	 * @return string
	 */
	private static function counters( array $m, array $filas ): string {
		unset( $m );
		$plazas   = 0;
		$ocupadas = 0;
		$llenos   = 0;
		foreach ( $filas as $fila ) {
			$fila      = (array) $fila;
			$plazas   += (int) $fila['seats'];
			$ocupadas += (int) $fila['taken'];
			if ( null !== $fila['free'] && 0 === (int) $fila['free'] ) {
				++$llenos;
			}
		}

		ob_start();
		?>
		<ul class="evt-cifras">
			<li class="evt-cifra"><strong><?php echo esc_html( (string) count( $filas ) ); ?></strong> talleres</li>
			<li class="evt-cifra"><strong><?php echo esc_html( (string) $ocupadas ); ?></strong> plazas ocupadas<?php echo $plazas > 0 ? esc_html( ' de ' . $plazas ) : ''; ?></li>
			<li class="evt-cifra"><strong><?php echo esc_html( (string) $llenos ); ?></strong> sin plazas libres</li>
		</ul>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * One workshop row.
	 *
	 * @param array<string, mixed> $m    Model.
	 * @param array<string, mixed> $fila Workshop row.
	 * @return string
	 */
	private static function row( array $m, array $fila ): string {
		$id    = (int) $fila['id'];
		$mini  = Assets::button_class() . ' evt-mini';
		$sede  = trim( (string) $fila['venue'] );
		$sala  = trim( (string) $fila['room'] );
		$donde = trim( $sede . ( '' !== $sede && '' !== $sala ? ' · ' : '' ) . $sala );

		ob_start();
		?>
		<tr>
			<td data-rotulo="Día"><?php echo esc_html( PanelParts::day( (string) $fila['date'] ) ); ?></td>
			<td data-rotulo="Hora"><?php echo esc_html( PanelParts::slot( (string) $fila['start'], (string) $fila['end'] ) ); ?></td>
			<td data-rotulo="Taller"><?php echo esc_html( '' !== trim( (string) $fila['title'] ) ? (string) $fila['title'] : '(sin título)' ); ?></td>
			<td data-rotulo="Sede y sala"><?php echo esc_html( '' !== $donde ? $donde : '—' ); ?></td>
			<td class="evt-num" data-rotulo="Aforo">
				<?php echo esc_html( (int) $fila['seats'] > 0 ? (string) (int) $fila['seats'] : 'Sin límite' ); ?>
			</td>
			<td data-rotulo="Plazas"><?php echo self::seats( $fila ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></td>
			<td data-rotulo="Acciones">
				<span class="evt-acciones">
					<?php
					$editar = PanelParts::icon_link(
						EventWorkspace::url( (int) $m['event_id'], EventWorkspace::PANEL_PROGRAMME, array( EventWorkspace::ARG_ROW => $id ) ),
						'lapiz',
						'Editar este taller en el programa'
					);
					echo $editar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
					?>
				</span>
			</td>
		</tr>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * How full one workshop is, written so it reads without the table head.
	 *
	 * @param array<string, mixed> $fila Workshop row.
	 * @return string
	 */
	private static function seats( array $fila ): string {
		$ocupadas = (int) $fila['taken'];
		if ( null === $fila['free'] ) {
			return '<span class="evt-state">' . esc_html( $ocupadas . ' inscritas' ) . '</span>';
		}
		$libres = (int) $fila['free'];
		$clase  = 0 === $libres ? 'evt-state evt-state-finalizado' : 'evt-state evt-state-abierto';

		return '<span class="' . esc_attr( $clase ) . '">'
			. esc_html( 0 === $libres ? 'Completo' : $libres . ' libres' )
			. '</span> <span class="evt-sub">' . esc_html( '(' . $ocupadas . ' de ' . (int) $fila['seats'] . ')' ) . '</span>';
	}
}
