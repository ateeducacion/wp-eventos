<?php
/**
 * The «Asistencia» tab of the event workshop.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

use Evt\PublicFront\Assets;
use Evt\PublicFront\Attendance;
use Evt\PublicFront\EventWorkspace;

/**
 * Los puntos de control con su QR, quién ha fichado y la hoja de firmas (ADR-0053).
 *
 * Tres cosas, en el orden en que se usan el día del evento: crear los puntos y
 * sacar sus QR; abrir cada punto cuando toca; y ver quién ha pasado, marcar a
 * mano a quien no pudo y sacar la hoja en papel.
 *
 * Solo pinta: no lee la petición, no consulta y no decide.
 */
final class EventAttendancePanel {

	/**
	 * The tab.
	 *
	 * @param array<string, mixed> $m What EventWorkspace::model() decided.
	 * @return string
	 */
	public static function html( array $m ): string {
		$datos = (array) $m['attendance'];

		$html = '<div class="evt-panel-cabecera"><div><h2 class="evt-panel-titulo">Asistencia</h2>'
			. '<p class="evt-sub">Cada punto de control tiene un código QR. Quien asiste lo escanea, entra con su cuenta y registra la hora. '
			. 'Un punto solo registra mientras está <strong>abierto</strong>: ábralo cuando lo proyecte y ciérrelo al terminar.</p></div></div>';

		if ( true !== ( $datos['has_page'] ?? false ) ) {
			$html .= '<p class="' . esc_attr( Assets::alert_class( 'warning' ) ) . '">El evento no tiene publicada su página de inscripción, '
				. 'y es la que abren los códigos QR. Publíquela en «Páginas» para que funcionen.</p>';
		}

		$html .= self::points( $m, (array) ( $datos['points'] ?? array() ) );
		$html .= self::marks( $m, $datos );
		return $html;
	}

	/**
	 * The checkpoints: name, open or not, and their QR; plus a blank row to add one.
	 *
	 * @param array<string, mixed>             $m      Model.
	 * @param array<int, array<string, mixed>> $puntos Checkpoints, with their URL.
	 * @return string
	 */
	private static function points( array $m, array $puntos ): string {
		$op    = EventWorkspace::PANEL_ATTENDANCE;
		$filas = $puntos;
		if ( count( $filas ) < Attendance::MAX_POINTS ) {
			$filas[] = array(
				'id'    => '',
				'label' => '',
				'open'  => false,
				'url'   => '',
			);
		}

		$html  = '<form class="evt-form" method="post" action="">';
		$html .= wp_nonce_field( EventWorkspace::nonce_action( $op ), EventWorkspace::nonce_name( $op ), false, false );
		$html .= sprintf(
			'<input type="hidden" name="%1$s" value="%2$s"><input type="hidden" name="%3$s" value="%4$d">',
			esc_attr( EventWorkspace::FIELD_DO ),
			esc_attr( $op ),
			esc_attr( EventWorkspace::FIELD_EVENT ),
			(int) $m['event_id']
		);
		$html .= '<section class="evt-tarjeta"><h3 class="evt-tarjeta__titulo">Puntos de control</h3>';
		if ( array() === $puntos ) {
			$html .= '<p class="evt-ayuda">Por ejemplo: «Entrada», «Mediodía» y «Salida». Escriba el primero y guarde.</p>';
		}
		$html .= '<div class="evt-puntos">';
		foreach ( $filas as $i => $punto ) {
			$html .= self::point( (int) $i, $punto, (string) ( $m['title'] ?? '' ) );
		}
		$html .= '</div>';
		if ( array() !== $puntos ) {
			$html .= self::help();
		}
		$html .= '<p class="evt-ayuda">Cambiar el nombre de un punto no cambia su código: el QR impreso sigue valiendo. '
			. 'Quitarlo lo invalida, y las marcas que tuviera dejan de verse.</p>';
		$html .= '<div class="evt-acciones"><button class="' . esc_attr( Assets::button_class( true ) ) . '" type="submit">Guardar los puntos</button></div>';
		$html .= '</section></form>';
		return $html;
	}

	/**
	 * One checkpoint row.
	 *
	 * @param int                  $i     Row index.
	 * @param array<string, mixed> $punto Checkpoint; empty ID for the blank row.
	 * @param string               $event Event title, for the poster.
	 * @return string
	 */
	private static function point( int $i, array $punto, string $event = '' ): string {
		$nuevo = '' === (string) $punto['id'];
		$html  = '<div class="evt-punto evt-tarjeta">';
		$html .= sprintf( '<input type="hidden" name="evt_pt_id[%1$d]" value="%2$s">', $i, esc_attr( (string) $punto['id'] ) );
		$html .= sprintf(
			'<p class="evt-campo"><label for="evt-pt-%1$d">%3$s</label><input type="text" id="evt-pt-%1$d" name="evt_pt_label[%1$d]" value="%2$s" maxlength="60" placeholder="%4$s"></p>',
			$i,
			esc_attr( (string) $punto['label'] ),
			$nuevo ? 'Añadir un punto' : 'Nombre',
			$nuevo ? 'Entrada' : ''
		);
		if ( $nuevo ) {
			return $html . '</div>';
		}

		$html .= sprintf(
			'<div class="form-check form-switch evt-interruptor"><input class="form-check-input" type="checkbox" role="switch" id="evt-pt-open-%1$d" name="evt_pt_open[%1$d]" value="1"%2$s> <label class="form-check-label" for="evt-pt-open-%1$d">Abierto: registra asistencia ahora</label></div>',
			$i,
			checked( true, (bool) $punto['open'], false )
		);
		$html .= sprintf(
			'<div class="evt-qr" data-evt-qr="%1$s" data-evt-qr-alt="%2$s" data-evt-qr-label="%4$s" data-evt-qr-event="%5$s"></div>'
				. '<p class="evt-ayuda"><a href="%1$s" target="_blank" rel="noopener">%3$s</a></p>',
			esc_url( (string) $punto['url'] ),
			esc_attr( 'Código QR de asistencia: ' . (string) $punto['label'] ),
			esc_html( (string) $punto['url'] ),
			esc_attr( (string) $punto['label'] ),
			esc_attr( $event )
		);
		// El botón nace oculto: lo enseña el guion cuando puede pintar el
		// cartel. Sin JavaScript queda la dirección escrita, que es lo mismo.
		$html .= '<p class="evt-acciones"><button type="button" class="' . esc_attr( Assets::button_class() ) . '" data-evt-qr-descargar hidden>Descargar el QR</button></p>';
		$html .= '<div class="' . esc_attr( Assets::alert_class( 'success' ) ) . ' evt-qr-despues" data-evt-qr-despues role="status" hidden>'
			. '<p><strong>Cartel descargado.</strong> Y ahora:</p><ol>'
			. '<li>Imprímalo en A4 o proyéctelo a pantalla completa.</li>'
			. '<li>Pruébelo con su móvil antes de que llegue la gente.</li>'
			. '<li>Marque «Abierto» y guarde cuando empiece el control; desmárquelo al terminar.</li>'
			. '</ol></div>';
		$html .= sprintf(
			'<p class="evt-campo"><label class="evt-check"><input type="checkbox" name="evt_pt_remove[%1$d]" value="1"> Quitar este punto al guardar</label></p>',
			$i
		);
		return $html . '</div>';
	}

	/**
	 * Where to put the QR and how to run a checkpoint, folded away.
	 *
	 * @return string
	 */
	private static function help(): string {
		return '<details class="evt-ayuda-qr"><summary>Dónde colocar el QR y cómo usarlo</summary><ul>'
			. '<li><strong>Dónde.</strong> En la puerta de la sala o junto a la mesa de acreditación, a la altura de los ojos, con buena luz y sin reflejos. '
			. 'Si se espera mucha gente, ponga varias copias separadas para que no se forme cola.</li>'
			. '<li><strong>Tamaño.</strong> El código debe medir al menos una décima parte de la distancia desde la que se escanea: '
			. 'un A4 vale hasta unos dos metros; para una sala entera, proyéctelo.</li>'
			. '<li><strong>Cuándo.</strong> Cada punto solo registra mientras está abierto. Ábralo al empezar y ciérrelo al terminar: '
			. 'así una foto del código no sirve desde fuera.</li>'
			. '<li><strong>Qué hace quien asiste.</strong> Escanea con la cámara del móvil, inicia sesión con su cuenta y pulsa «Confirmar asistencia». Ve la hora registrada en pantalla.</li>'
			. '<li><strong>Si algo falla.</strong> Tenga impresa la hoja de firmas y marque después a mano en la tabla de abajo a quien no pudo con el móvil.</li>'
			. '</ul></details>';
	}

	/**
	 * Who has marked what, with the manual boxes, the CSV and the sheet.
	 *
	 * @param array<string, mixed> $m     Model.
	 * @param array<string, mixed> $datos Attendance model.
	 * @return string
	 */
	private static function marks( array $m, array $datos ): string {
		$puntos   = (array) ( $datos['points'] ?? array() );
		$filas    = (array) ( $datos['rows'] ?? array() );
		$talleres = (array) ( $datos['workshops'] ?? array() );
		$taller   = (int) ( $datos['workshop'] ?? 0 );
		$event_id = (int) $m['event_id'];
		$base     = EventWorkspace::url( $event_id, EventWorkspace::PANEL_ATTENDANCE );

		$html = '<section class="evt-tarjeta"><h3 class="evt-tarjeta__titulo">Quién ha pasado</h3>';

		// Filtro por taller y la hoja de firmas, que sale de lo mismo: la hoja
		// de un taller es para quien lo imparte.
		$accion  = (string) strtok( $base, '?' );
		$ocultos = array();
		parse_str( (string) wp_parse_url( $base, PHP_URL_QUERY ), $ocultos );
		$html .= '<form class="evt-form evt-filtro" method="get" action="' . esc_url( $accion ) . '">';
		foreach ( $ocultos as $clave => $valor ) {
			$html .= '<input type="hidden" name="' . esc_attr( (string) $clave ) . '" value="' . esc_attr( (string) $valor ) . '">';
		}
		if ( array() !== $talleres ) {
			$html .= '<div class="evt-form-campo"><label for="evt-at-taller">Taller</label><select id="evt-at-taller" name="' . esc_attr( EventWorkspace::ARG_WORKSHOP ) . '"><option value="">Todas las personas</option>';
			foreach ( $talleres as $id => $rotulo ) {
				$html .= sprintf( '<option value="%1$d"%2$s>%3$s</option>', (int) $id, selected( $taller, (int) $id, false ), esc_html( (string) $rotulo ) );
			}
			$html .= '</select></div><div class="evt-acciones"><button class="' . esc_attr( Assets::button_class() ) . '" type="submit">Filtrar</button></div>';
		}
		$html .= '</form>';

		$hoja  = add_query_arg(
			array(
				Attendance::ARG_SHEET        => '1',
				EventWorkspace::ARG_WORKSHOP => $taller > 0 ? (string) $taller : '',
			),
			$base
		);
		$html .= '<div class="evt-acciones">';
		$html .= '<a class="' . esc_attr( Assets::button_class() ) . '" href="' . esc_url( $hoja ) . '" target="_blank" rel="noopener">Hoja de firmas para imprimir</a>';
		$html .= self::export_form( $event_id );
		$html .= '</div>';

		if ( array() === $filas ) {
			return $html . '<p class="evt-vacio">Todavía no hay nadie inscrito' . ( $taller > 0 ? ' en este taller' : '' ) . '.</p></section>';
		}
		if ( array() === $puntos ) {
			return $html . '<p class="evt-ayuda">Cree un punto de control arriba para empezar a registrar la asistencia.</p></section>';
		}

		$op    = EventWorkspace::OP_ATT_MARKS;
		$html .= '<form class="evt-form" method="post" action="">';
		$html .= wp_nonce_field( EventWorkspace::nonce_action( $op ), EventWorkspace::nonce_name( $op ), false, false );
		$html .= sprintf(
			'<input type="hidden" name="%1$s" value="%2$s"><input type="hidden" name="%3$s" value="%4$d"><input type="hidden" name="%5$s" value="%6$s">',
			esc_attr( EventWorkspace::FIELD_DO ),
			esc_attr( $op ),
			esc_attr( EventWorkspace::FIELD_EVENT ),
			$event_id,
			esc_attr( EventWorkspace::ARG_WORKSHOP ),
			$taller > 0 ? (string) $taller : ''
		);
		$html .= '<p class="evt-ayuda">Las casillas son para marcar a mano a quien no pudo con el QR, o quitar una marca. Lo marcado a mano queda como tal.</p>';
		$html .= '<div class="evt-tabla-caja"><table class="evt-tabla"><thead><tr><th scope="col">Persona</th><th scope="col">Centro</th>';
		foreach ( $puntos as $punto ) {
			$html .= '<th scope="col">' . esc_html( (string) $punto['label'] ) . '</th>';
		}
		$html .= '</tr></thead><tbody>';
		foreach ( $filas as $fila ) {
			$id    = (int) $fila['id'];
			$html .= '<tr><td data-rotulo="Persona">' . esc_html( trim( $fila['surname'] . ', ' . $fila['name'], ', ' ) )
				. '<input type="hidden" name="evt_at_rows[]" value="' . esc_attr( (string) $id ) . '"></td>'
				. '<td data-rotulo="Centro">' . esc_html( '' !== (string) $fila['centre'] ? (string) $fila['centre'] : '—' ) . '</td>';
			foreach ( $puntos as $punto ) {
				$marca = $fila['marks'][ $punto['id'] ] ?? null;
				$texto = null === $marca ? '' : Attendance::time( (string) $marca['at'] ) . ( Attendance::BY_MANUAL === $marca['by'] ? ' (a mano)' : '' );
				$html .= sprintf(
					'<td data-rotulo="%1$s"><label class="evt-check"><input type="checkbox" name="evt_at[%2$d][%3$s]" value="1"%4$s> %5$s</label></td>',
					esc_attr( (string) $punto['label'] ),
					$id,
					esc_attr( (string) $punto['id'] ),
					checked( null !== $marca, true, false ),
					esc_html( $texto )
				);
			}
			$html .= '</tr>';
		}
		$html .= '</tbody></table></div>';
		$html .= '<div class="evt-acciones"><button class="' . esc_attr( Assets::button_class( true ) ) . '" type="submit">Guardar la asistencia</button></div>';
		$html .= '</form></section>';
		return $html;
	}

	/**
	 * The «Exportar asistencia» button: a POST with its nonce, like the participants CSV.
	 *
	 * @param int $event_id Event post ID.
	 * @return string
	 */
	private static function export_form( int $event_id ): string {
		$op = EventWorkspace::OP_ATT_EXPORT;
		return '<form class="evt-form" method="post" action="">'
			. wp_nonce_field( EventWorkspace::nonce_action( $op ), EventWorkspace::nonce_name( $op ), false, false )
			. sprintf(
				'<input type="hidden" name="%1$s" value="%2$s"><input type="hidden" name="%3$s" value="%4$d">',
				esc_attr( EventWorkspace::FIELD_DO ),
				esc_attr( $op ),
				esc_attr( EventWorkspace::FIELD_EVENT ),
				$event_id
			)
			. '<button class="' . esc_attr( Assets::button_class() ) . '" type="submit">Exportar asistencia a CSV</button></form>';
	}
}
