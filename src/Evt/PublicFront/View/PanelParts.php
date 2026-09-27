<?php
/**
 * Pieces the programme panels share: row actions and the ficha trash.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

use Evt\PublicFront\Assets;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\Shell;

/**
 * Lo que repiten «Ponentes», «Programa» y «Talleres».
 *
 * Está aparte para que el botón de borrar de los tres sea **el mismo botón**:
 * mismo nonce por fila, misma confirmación y misma vuelta a la pestaña desde
 * la que se pulsó. Tres copias se separan a la tercera corrección.
 *
 * Solo pinta: no lee la petición, no consulta, no decide.
 */
final class PanelParts {

	/**
	 * What `wp_kses()` lets through for an inline icon.
	 *
	 * Los iconos los escribe {@see Shell::icon()} y no vienen de fuera, pero
	 * pasan por `wp_kses()` igual: es una lista corta y deja la regla de «toda
	 * la salida escapada» sin excepciones que alguien tenga que recordar.
	 *
	 * @var array<string, array<string, bool>>
	 */
	public const SVG = array(
		'svg'  => array(
			'viewbox'     => true,
			'width'       => true,
			'height'      => true,
			'aria-hidden' => true,
			'focusable'   => true,
		),
		'path' => array(
			'fill' => true,
			'd'    => true,
		),
	);

	/**
	 * A side panel over the tab: the form to add or edit one row.
	 *
	 * Se abre por la dirección (`?alta=1`, `?ficha=N`) y se cierra con un
	 * enlace a la pestaña, así que no depende de JavaScript: sin guion se
	 * abre y se cierra igual, y con guion además se cierra con Escape.
	 * El error de un guardado fallido se enseña dentro, que es donde se mira.
	 *
	 * @param string               $titulo  Heading.
	 * @param string               $cuerpo  Built, escaped HTML of the form.
	 * @param string               $cerrar  URL that closes it.
	 * @param array<string, mixed> $flash   The last notice, shown when it is an error.
	 * @return string
	 */
	public static function drawer( string $titulo, string $cuerpo, string $cerrar, array $flash = array() ): string {
		ob_start();
		?>
		<a class="evt-cajon-fondo" href="<?php echo esc_url( $cerrar ); ?>" tabindex="-1" aria-hidden="true"></a>
		<section class="evt-cajon" role="dialog" aria-modal="true" aria-labelledby="evt-cajon-titulo" data-evt-cajon data-evt-cajon-cerrar="<?php echo esc_url( $cerrar ); ?>">
			<header class="evt-cajon__cabecera">
				<h2 class="evt-cajon__titulo" id="evt-cajon-titulo"><?php echo esc_html( $titulo ); ?></h2>
				<a class="evt-cajon__cerrar" href="<?php echo esc_url( $cerrar ); ?>" aria-label="Cerrar sin guardar">&times;</a>
			</header>
			<div class="evt-cajon__cuerpo">
				<?php if ( 'error' === (string) ( $flash['tipo'] ?? '' ) && '' !== (string) ( $flash['texto'] ?? '' ) ) : ?>
					<?php echo Shell::notice( 'error', (string) $flash['texto'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shell::notice escapa. ?>
				<?php endif; ?>
				<?php echo $cuerpo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado por quien llama. ?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The save bar that stays at the bottom of long forms.
	 *
	 * Pegada abajo mientras se baja por el formulario: el botón de guardar no
	 * se queda al final de una página de tres pantallas. Con guion dice
	 * además si hay cambios sin guardar y deja descartarlos.
	 *
	 * @param string $rotulo What the button says.
	 * @return string
	 */
	public static function save_bar( string $rotulo ): string {
		ob_start();
		?>
		<div class="evt-guardar" data-evt-guardar>
			<span class="evt-guardar__estado" data-evt-guardar-estado aria-live="polite"></span>
			<span class="evt-guardar__botones">
				<button class="<?php echo esc_attr( Assets::button_class() ); ?>" type="reset" data-evt-guardar-descartar hidden>Descartar</button>
				<button class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" type="submit"><?php echo esc_html( $rotulo ); ?></button>
			</span>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * One row action: its own little POST form, with its own nonce.
	 *
	 * @param array<string, mixed> $m         Model.
	 * @param int                  $id        Speaker or activity post ID.
	 * @param string               $op        Operation.
	 * @param string               $icono     Icon name for {@see Shell::icon()}.
	 * @param string               $titulo    What the button does, in Spanish.
	 * @param string               $clases    Button classes.
	 * @param string               $panel     Tab to come back to; empty for none.
	 * @param bool                 $apagado   Whether the button is disabled.
	 * @param string               $confirmar Question to ask before submitting.
	 * @param string               $id_field  Field that carries the row ID.
	 * @return string
	 */
	public static function action( array $m, int $id, string $op, string $icono, string $titulo, string $clases, string $panel, bool $apagado = false, string $confirmar = '', string $id_field = EventWorkspace::FIELD_ROW ): string {
		$pregunta = '' !== $confirmar ? ' data-evt-confirm="' . esc_attr( $confirmar ) . '"' : '';

		ob_start();
		?>
		<form class="evt-accion" method="post" action=""<?php echo $pregunta; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado arriba. ?>>
			<?php wp_nonce_field( EventWorkspace::nonce_action( $op ), EventWorkspace::nonce_name( $op, $id ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_EVENT ); ?>" value="<?php echo esc_attr( (string) (int) $m['event_id'] ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( $id_field ); ?>" value="<?php echo esc_attr( (string) $id ); ?>" />
			<?php if ( '' !== $panel ) : ?>
				<input type="hidden" name="<?php echo esc_attr( EventWorkspace::ARG_PANEL ); ?>" value="<?php echo esc_attr( $panel ); ?>" />
			<?php endif; ?>
			<button type="submit" class="<?php echo esc_attr( $clases . ' evt-icono' ); ?>"
				title="<?php echo esc_attr( $titulo ); ?>" data-bs-toggle="tooltip"
				<?php disabled( $apagado, true ); ?>>
				<?php echo wp_kses( Shell::icon( $icono ), self::SVG ); ?>
				<span class="screen-reader-text"><?php echo esc_html( $titulo ); ?></span>
			</button>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * An icon link, with the same globe as the icon buttons.
	 *
	 * @param string $url    Where it goes.
	 * @param string $icono  Icon name for {@see Shell::icon()}.
	 * @param string $titulo What it does, in Spanish.
	 * @param string $clases Extra classes.
	 * @return string
	 */
	public static function icon_link( string $url, string $icono, string $titulo, string $clases = '' ): string {
		if ( '' === $url ) {
			return '';
		}
		$clases = trim( Assets::button_class() . ' evt-mini evt-icono ' . $clases );

		return '<a class="' . esc_attr( $clases ) . '" href="' . esc_url( $url ) . '"'
			. ' title="' . esc_attr( $titulo ) . '" data-bs-toggle="tooltip">'
			. wp_kses( Shell::icon( $icono ), self::SVG )
			. '<span class="screen-reader-text">' . esc_html( $titulo ) . '</span></a>';
	}

	/**
	 * «Papelera (N)» with what was sent to it, when there is something.
	 *
	 * Ponentes y actividades comparten papelera porque lo que se busca ahí es
	 * «lo que borré sin querer», no de qué tipo era.
	 *
	 * @param array<string, mixed> $m     Model.
	 * @param string               $panel Tab to come back to.
	 * @return string
	 */
	public static function trash_link( array $m, string $panel ): string {
		$filas = (array) $m['row_trash'];
		if ( array() === $filas ) {
			return '';
		}
		$mini = Assets::button_class() . ' evt-mini';

		ob_start();
		?>
		<details class="evt-tarjeta">
			<summary><?php echo esc_html( sprintf( 'Papelera (%d)', count( $filas ) ) ); ?></summary>
			<p class="evt-sub">Nada se ha perdido. Al restaurar una ficha vuelve donde estaba, con sus datos.</p>
			<div class="evt-tabla-caja">
				<table class="evt-tabla">
					<thead>
						<tr>
							<th scope="col">Qué era</th>
							<th scope="col">Título</th>
							<th scope="col">Acciones</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $filas as $fila ) : ?>
							<tr>
								<td data-rotulo="Qué era"><?php echo esc_html( (string) $fila['kind'] ); ?></td>
								<td data-rotulo="Título"><?php echo esc_html( '' !== trim( (string) $fila['title'] ) ? (string) $fila['title'] : '(sin título)' ); ?></td>
								<td data-rotulo="Acciones">
									<?php
									$boton = self::action( $m, (int) $fila['id'], 'row_restore', 'restaurar', 'Restaurar esta ficha', $mini, $panel );
									echo $boton; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</details>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * A day written the way it is read out loud.
	 *
	 * @param string $fecha Date in Y-m-d, or empty.
	 * @return string
	 */
	public static function day( string $fecha ): string {
		if ( '' === $fecha ) {
			return 'Sin día asignado';
		}
		$marca = strtotime( $fecha . ' 12:00:00' );
		if ( false === $marca ) {
			return $fecha;
		}
		return (string) wp_date( 'l j \d\e F \d\e Y', $marca );
	}

	/**
	 * The time slot of an activity: «09:30 – 11:00», or just the start.
	 *
	 * @param string $start Start time.
	 * @param string $end   End time.
	 * @return string
	 */
	public static function slot( string $start, string $end ): string {
		if ( '' === $start ) {
			return '—';
		}
		return '' === $end ? $start : $start . ' – ' . $end;
	}
}
