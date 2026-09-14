<?php
/**
 * The «Inscripción» tab of the event workshop.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

use Evt\PublicFront\EventWorkspace;

/**
 * Dónde quien organiza abre la inscripción y redacta sus preguntas.
 *
 * Solo pinta: no lee la petición, no consulta y no decide.
 *
 * La pantalla es corta a propósito, y eso es la decisión: **no es un
 * constructor de formularios**. El núcleo del formulario —documento, nombre,
 * apellidos, correo, teléfono, centro y consentimiento— no sale aquí porque no
 * se edita: está en código, medido, y es el mismo en todos los eventos
 * (ADR-0031). Lo único que se redacta son las tres o cuatro preguntas propias
 * del evento, y una pregunta tiene cuatro cosas: rótulo, tipo, opciones y si es
 * obligatoria. Ni condiciones, ni reglas.
 *
 * Sin JavaScript: las filas son campos paralelos y la última va en blanco. Se
 * escribe encima y se guarda; se borra el rótulo y la pregunta se va.
 */
final class EventSignupPanel {

	/**
	 * The tab.
	 *
	 * @param array<string, mixed> $m What EventWorkspace::model() decided.
	 * @return string
	 */
	public static function html( array $m ): string {
		$event_id  = (int) $m['event_id'];
		$ajustes   = (array) $m['signup'];
		$preguntas = (array) $m['questions'];

		$op    = EventWorkspace::PANEL_SIGNUP;
		$html  = '<form class="evt-form evt-panel--inscripcion" method="post" action="">';
		$html .= wp_nonce_field( EventWorkspace::nonce_action( $op ), EventWorkspace::nonce_name( $op ), false, false );
		$html .= sprintf(
			'<input type="hidden" name="%1$s" value="%2$s"><input type="hidden" name="%3$s" value="%4$d">',
			esc_attr( EventWorkspace::FIELD_DO ),
			esc_attr( $op ),
			esc_attr( EventWorkspace::FIELD_EVENT ),
			$event_id
		);

		$html .= self::windows( $ajustes );
		$html .= self::consent( $ajustes );
		$html .= self::questions( $preguntas, (array) $m['q_types'], (bool) $m['q_locked'] );

		$html .= '<p class="evt-panel__enviar"><button type="submit" class="evt-btn evt-btn--primario">Guardar</button></p>';
		$html .= '</form>';

		return $html;
	}

	/**
	 * The two windows: the signup one and the workshop one.
	 *
	 * @param array<string, mixed> $a Signup settings.
	 * @return string
	 */
	private static function windows( array $a ): string {
		$html  = '<fieldset class="evt-campos"><legend>Plazos</legend>';
		$html .= self::toggle( 'evt_signup_open', 'La inscripción está abierta', (bool) $a['open'] );
		$html .= '<p class="evt-ayuda">Mientras esté cerrada, la página de inscripción lo dice y no acepta a nadie.</p>';

		// El plazo del taller es propio porque se abre cuando el programa está
		// cerrado, y eso casi nunca coincide con abrir la inscripción (ADR-0033).
		$html .= self::toggle( 'evt_workshop_open', 'Se puede elegir taller', (bool) $a['workshop_open'] );
		$html .= '<p class="evt-campo evt-campo--fecha"><label for="evt-ws-desde">Desde</label>'
			. '<input type="date" id="evt-ws-desde" name="evt_workshop_start" value="' . esc_attr( (string) $a['workshop_start'] ) . '"></p>';
		$html .= '<p class="evt-campo evt-campo--fecha"><label for="evt-ws-hasta">Hasta</label>'
			. '<input type="date" id="evt-ws-hasta" name="evt_workshop_end" value="' . esc_attr( (string) $a['workshop_end'] ) . '"></p>';
		$html .= '<p class="evt-ayuda">Las fechas son opcionales: sin ellas manda el interruptor. '
			. 'Quien ya se inscribió puede cambiar de taller mientras el plazo siga abierto, '
			. 'y un taller lleno deja de poder elegirse.</p>';

		return $html . '</fieldset>';
	}

	/**
	 * The two consent texts.
	 *
	 * @param array<string, mixed> $a Signup settings.
	 * @return string
	 */
	private static function consent( array $a ): string {
		$html  = '<fieldset class="evt-campos"><legend>Protección de datos</legend>';
		$html .= '<p class="evt-campo"><label for="evt-consent-privacidad">Información sobre el tratamiento de sus datos</label>'
			. '<textarea id="evt-consent-privacidad" name="evt_consent_privacy" rows="6">'
			. esc_textarea( (string) $a['consent_privacy'] ) . '</textarea></p>';
		$html .= '<p class="evt-campo"><label for="evt-consent-imagen">Consentimiento informado</label>'
			. '<textarea id="evt-consent-imagen" name="evt_consent_image" rows="6">'
			. esc_textarea( (string) $a['consent_image'] ) . '</textarea></p>';

		// Cambiar un texto sube la versión y **no reescribe** la que ya aceptó
		// nadie: por eso el número está a la vista (ADR-0020).
		$html .= '<p class="evt-ayuda">Versión actual: <strong>v' . (int) $a['consent_version'] . '</strong>. '
			. 'Cambiar cualquiera de los dos textos crea una versión nueva; lo que ya aceptó alguien no se reescribe, '
			. 'y en la lista de participantes se ve qué versión aceptó cada persona.</p>';

		return $html . '</fieldset>';
	}

	/**
	 * The question list, plus one blank row to add another.
	 *
	 * @param array<int, array<string, mixed>> $preguntas Questions.
	 * @param array<string, string>            $tipos     Question types.
	 * @param bool                             $locked    Whether anybody signed up already.
	 * @return string
	 */
	private static function questions( array $preguntas, array $tipos, bool $locked ): string {
		$html = '<fieldset class="evt-campos evt-preguntas"><legend>Preguntas de este evento</legend>';

		$html .= '<p class="evt-ayuda">Tres o cuatro, las de logística: si se queda a comer, intolerancias, '
			. 'si es residente. El resto del formulario —documento, nombre, apellidos, correo, teléfono, centro '
			. 'y consentimiento— es siempre el mismo y no se toca desde aquí.</p>';

		if ( $locked ) {
			$html .= '<p class="evt-aviso evt-aviso--aviso">Ya hay personas inscritas. Puede reescribir un rótulo y '
				. '<strong>añadir</strong> opciones, pero no cambiar el tipo de una pregunta ni quitarle una opción: '
				. 'lo que ya se contestó dejaría de significar lo mismo.</p>';
		}

		$filas = $preguntas;
		// La fila en blanco del final es cómo se añade una pregunta sin
		// JavaScript: se escribe su rótulo y se guarda. Si se deja vacía, no
		// añade nada, porque una pregunta sin rótulo se cae al normalizar.
		$filas[] = array(
			'id'       => '',
			'label'    => '',
			'type'     => 'text',
			'options'  => array(),
			'required' => false,
		);

		foreach ( $filas as $i => $pregunta ) {
			$html .= self::row( (int) $i, $pregunta, $tipos );
		}

		$html .= '<p class="evt-ayuda">Para quitar una pregunta, borre su rótulo y guarde. '
			. 'Lo que ya hubiera contestado alguien no se borra: deja de verse, y vuelve si la pregunta vuelve.</p>';

		return $html . '</fieldset>';
	}

	/**
	 * One question row.
	 *
	 * @param int                   $i         Row index.
	 * @param array<string, mixed>  $p         Question.
	 * @param array<string, string> $tipos     Question types.
	 * @return string
	 */
	private static function row( int $i, array $p, array $tipos ): string {
		$nueva  = '' === (string) $p['id'];
		$rotulo = $nueva ? 'Pregunta nueva' : 'Pregunta ' . ( $i + 1 );

		$html  = '<div class="evt-pregunta">';
		$html .= '<h4 class="evt-pregunta__n">' . esc_html( $rotulo ) . '</h4>';
		$html .= sprintf( '<input type="hidden" name="evt_q_id[%1$d]" value="%2$s">', $i, esc_attr( (string) $p['id'] ) );

		$html .= sprintf(
			'<p class="evt-campo"><label for="evt-q-l-%1$d">Rótulo</label>'
				. '<input type="text" id="evt-q-l-%1$d" name="evt_q_label[%1$d]" value="%2$s" maxlength="200"></p>',
			$i,
			esc_attr( (string) $p['label'] )
		);

		$html .= '<p class="evt-campo"><label for="evt-q-t-' . $i . '">Tipo</label>'
			. '<select id="evt-q-t-' . $i . '" name="evt_q_type[' . $i . ']">';
		foreach ( $tipos as $valor => $nombre ) {
			$html .= sprintf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $valor ),
				selected( $valor, (string) $p['type'], false ),
				esc_html( $nombre )
			);
		}
		$html .= '</select></p>';

		$html .= sprintf(
			'<p class="evt-campo"><label for="evt-q-o-%1$d">Opciones, una por línea</label>'
				. '<textarea id="evt-q-o-%1$d" name="evt_q_options[%1$d]" rows="3">%2$s</textarea>'
				. '<small>Solo para «Una opción» y «Varias opciones».</small></p>',
			$i,
			esc_textarea( implode( "\n", (array) $p['options'] ) )
		);

		$html .= sprintf(
			'<p class="evt-campo evt-campo--casilla"><label for="evt-q-r-%1$d">'
				. '<input type="checkbox" id="evt-q-r-%1$d" name="evt_q_required[%1$d]" value="1"%2$s> Obligatoria</label></p>',
			$i,
			checked( true, (bool) $p['required'], false )
		);

		return $html . '</div>';
	}

	/**
	 * One switch.
	 *
	 * @param string $nombre Field name.
	 * @param string $rotulo Label.
	 * @param bool   $puesto Whether it is on.
	 * @return string
	 */
	private static function toggle( string $nombre, string $rotulo, bool $puesto ): string {
		return sprintf(
			'<p class="evt-campo evt-campo--casilla"><label for="%1$s"><input type="checkbox" id="%1$s" name="%1$s" value="1"%2$s> %3$s</label></p>',
			esc_attr( $nombre ),
			checked( true, $puesto, false ),
			esc_html( $rotulo )
		);
	}
}
