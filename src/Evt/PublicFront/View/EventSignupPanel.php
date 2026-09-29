<?php
/**
 * The «Formulario y plazos» tab of the event workshop.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

use Evt\Meta\EventMetaKeys;
use Evt\Meta\RegistrationMetaKeys;
use Evt\PublicFront\Assets;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\Shell;

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
 * Arriba, lo que ve quien visita la página ahora mismo; debajo, los dos
 * plazos con interruptores; y lo que se toca poco —las preguntas, los textos
 * legales y el botón de la portada— plegado. Sin JavaScript todo se despliega
 * igual: son `<details>`. Las preguntas son campos paralelos y la última va en
 * blanco: se escribe encima y se guarda; se borra el rótulo y la pregunta se va.
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
		$html  = '<div class="evt-panel-cabecera"><div><h2 class="evt-panel-titulo">Formulario y plazos</h2>'
			. '<p class="evt-sub">Cuándo se acepta a alguien y qué se le pregunta además de sus datos.</p></div></div>';
		$html .= self::status( (array) ( $m['signup_status'] ?? array() ) );

		$html .= '<form class="evt-form evt-panel--inscripcion" method="post" action="" data-evt-cambios>';
		$html .= wp_nonce_field( EventWorkspace::nonce_action( $op ), EventWorkspace::nonce_name( $op ), false, false );
		$html .= sprintf(
			'<input type="hidden" name="%1$s" value="%2$s"><input type="hidden" name="%3$s" value="%4$d">',
			esc_attr( EventWorkspace::FIELD_DO ),
			esc_attr( $op ),
			esc_attr( EventWorkspace::FIELD_EVENT ),
			$event_id
		);

		$html .= '<div class="evt-dos-columnas">' . self::windows( $ajustes, $preguntas ) . self::workshops( $ajustes ) . '</div>';
		$html .= self::questions( $preguntas, (array) $m['q_types'], (bool) $m['q_locked'] );
		$html .= self::consent( $ajustes );
		$html .= self::button( (array) ( $m['values'] ?? array() ) );
		$html .= PanelParts::save_bar( 'Guardar la inscripción' );
		$html .= '</form>';

		return $html;
	}

	/**
	 * What a visitor of the signup page sees right now, in one line.
	 *
	 * @param array<string, mixed> $s Status: open, reason, public.
	 * @return string
	 */
	private static function status( array $s ): string {
		if ( array() === $s ) {
			return '';
		}
		if ( true === $s['open'] ) {
			$texto = true === $s['public']
				? 'La inscripción está aceptando gente, también sin iniciar sesión.'
				: 'La inscripción está aceptando gente con sesión iniciada.';
			return '<p class="' . esc_attr( Assets::alert_class( 'success' ) ) . '" role="status">' . esc_html( $texto ) . '</p>';
		}
		return '<p class="' . esc_attr( Assets::alert_class( 'info' ) ) . '" role="status">'
			. esc_html( 'Ahora mismo no acepta a nadie. Quien visite la página lee: «' . (string) $s['reason'] . '»' ) . '</p>';
	}

	/**
	 * The signup window and who may sign up.
	 *
	 * @param array<string, mixed>             $a         Signup settings.
	 * @param array<int, array<string, mixed>> $preguntas Questions of the event.
	 * @return string
	 */
	private static function windows( array $a, array $preguntas ): string {
		$html  = '<fieldset class="evt-tarjeta evt-campos"><legend>Inscripción</legend>';
		$html .= self::toggle( 'evt_signup_open', 'Abierta', (bool) $a['open'] );
		$html .= self::dates( 'evt_signup_start', 'evt_signup_end', 'evt-ins', (string) $a['start'], (string) $a['end'] );
		$html .= '<p class="evt-ayuda">Además, el evento tiene que estar publicado y sin marcar como histórico.</p>';
		$html .= self::toggle( 'evt_signup_public', 'Pública: también sin iniciar sesión', (bool) $a['public'] );
		$html .= '<p class="evt-ayuda">Sin sesión se pide la casilla «No soy un robot» y no se adjuntan archivos.</p>';
		if ( (bool) $a['public'] && self::has_file_question( $preguntas ) ) {
			$html .= '<p class="' . esc_attr( Assets::alert_class( 'warning' ) ) . '">Quien se inscriba <strong>sin iniciar sesión no puede adjuntar '
				. 'archivos</strong>: las preguntas de archivo no le salen, y si alguna es obligatoria tendrá que '
				. 'iniciar sesión para inscribirse.</p>';
		}
		return $html . '</fieldset>';
	}

	/**
	 * The workshop window: its own switch and dates (ADR-0033).
	 *
	 * @param array<string, mixed> $a Signup settings.
	 * @return string
	 */
	private static function workshops( array $a ): string {
		$html  = '<fieldset class="evt-tarjeta evt-campos"><legend>Elegir taller</legend>';
		$html .= self::toggle( 'evt_workshop_open', 'Se puede elegir taller', (bool) $a['workshop_open'] );
		$html .= self::dates( 'evt_workshop_start', 'evt_workshop_end', 'evt-ws', (string) $a['workshop_start'], (string) $a['workshop_end'] );
		$html .= '<p class="evt-ayuda">Sin fechas manda el interruptor. Quien ya se inscribió puede cambiar de taller '
			. 'mientras siga abierto, y un taller lleno deja de poder elegirse.</p>';
		return $html . '</fieldset>';
	}

	/**
	 * Two optional dates, side by side.
	 *
	 * @param string $desde  Name of the first field.
	 * @param string $hasta  Name of the second field.
	 * @param string $id     ID prefix.
	 * @param string $inicio Current first date.
	 * @param string $fin    Current last date.
	 * @return string
	 */
	private static function dates( string $desde, string $hasta, string $id, string $inicio, string $fin ): string {
		return sprintf(
			'<div class="evt-form-fila evt-form-fila--2">'
				. '<p class="evt-campo evt-campo--fecha"><label for="%1$s-desde">Desde</label><input type="date" id="%1$s-desde" name="%2$s" value="%3$s"></p>'
				. '<p class="evt-campo evt-campo--fecha"><label for="%1$s-hasta">Hasta</label><input type="date" id="%1$s-hasta" name="%4$s" value="%5$s"></p>'
				. '</div>',
			esc_attr( $id ),
			esc_attr( $desde ),
			esc_attr( $inicio ),
			esc_attr( $hasta ),
			esc_attr( $fin )
		);
	}

	/**
	 * Whether any question asks for a file.
	 *
	 * @param array<int, array<string, mixed>> $preguntas Questions.
	 * @return bool
	 */
	private static function has_file_question( array $preguntas ): bool {
		return in_array( 'file', array_column( $preguntas, 'type' ), true );
	}

	/**
	 * The question list, each one folded, plus a blank one to add another.
	 *
	 * @param array<int, array<string, mixed>> $preguntas Questions.
	 * @param array<string, string>            $tipos     Question types.
	 * @param bool                             $locked    Whether anybody signed up already.
	 * @return string
	 */
	private static function questions( array $preguntas, array $tipos, bool $locked ): string {
		$html  = '<section class="evt-tarjeta evt-preguntas"><h3 class="evt-tarjeta__titulo">Preguntas de este evento</h3>';
		$html .= '<p class="evt-ayuda">Tres o cuatro, las de logística. El documento, el nombre, los apellidos, el correo, '
			. 'el teléfono, el centro y el consentimiento van siempre y no se tocan desde aquí.</p>';

		if ( $locked ) {
			$html .= '<p class="' . esc_attr( Assets::alert_class( 'warning' ) ) . '">Ya hay personas inscritas: se puede reescribir un rótulo y '
				. '<strong>añadir</strong> opciones, pero no cambiar el tipo de una pregunta ni quitarle una opción.</p>';
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

		$html .= '<div class="evt-preguntas__lista" data-evt-preguntas>';
		foreach ( $filas as $i => $pregunta ) {
			$html .= self::row( (int) $i, $pregunta, $tipos, array() === $preguntas );
		}
		$html .= '</div>';

		// Con JavaScript, un botón que añade otra fila en blanco sin guardar
		// antes: la copia la hace el guion a partir de la última. Sin él, la
		// fila en blanco de siempre, y se añade de una en una.
		$html .= '<p class="evt-acciones"><button type="button" class="' . esc_attr( Assets::button_class() ) . '" data-evt-pregunta-nueva hidden>'
			. wp_kses( Shell::icon_plus(), PanelParts::SVG ) . ' Añadir otra pregunta</button></p>';

		$html .= '<p class="evt-ayuda">Para quitar una pregunta, ábrala y marque «Quitar esta pregunta al guardar». '
			. 'Lo que ya hubiera contestado alguien no se borra: deja de verse, y vuelve si la pregunta vuelve.</p>';

		return $html . '</section>';
	}

	/**
	 * One question, folded: its summary says what it is; opening it edits it.
	 *
	 * @param int                   $i       Row index.
	 * @param array<string, mixed>  $p       Question.
	 * @param array<string, string> $tipos   Question types.
	 * @param bool                  $abierta Whether to show it unfolded.
	 * @return string
	 */
	private static function row( int $i, array $p, array $tipos, bool $abierta ): string {
		$nueva   = '' === (string) $p['id'];
		$resumen = $nueva
			? '<span class="evt-pregunta__rotulo">Añadir una pregunta</span>'
			: sprintf(
				'<span class="evt-pregunta__rotulo">%1$s</span><span class="evt-pregunta__tipo">%2$s%3$s</span>',
				esc_html( (string) $p['label'] ),
				esc_html( (string) ( $tipos[ (string) $p['type'] ] ?? '' ) ),
				(bool) $p['required'] ? ' · obligatoria' : ''
			);

		$html  = '<details class="evt-pregunta' . ( $nueva ? ' evt-pregunta--nueva' : '' ) . '"' . ( $nueva && $abierta ? ' open' : '' ) . '>';
		$html .= '<summary>' . $resumen . '</summary><div class="evt-pregunta__cuerpo">';
		$html .= sprintf( '<input type="hidden" name="evt_q_id[%1$d]" value="%2$s">', $i, esc_attr( (string) $p['id'] ) );

		$html .= '<div class="evt-form-fila evt-form-fila--2">';
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
		$html .= '</select></p></div>';

		// Las opciones solo tienen sentido en las dos de elegir: el guion
		// esconde el campo en las demás y lo enseña al cambiar el tipo. Sin
		// guion se queda a la vista, con la nota que dice cuándo se usa.
		$html .= sprintf(
			'<p class="evt-campo" data-evt-q-opciones="%3$s"><label for="evt-q-o-%1$d">Opciones, una por línea</label>'
				. '<textarea id="evt-q-o-%1$d" name="evt_q_options[%1$d]" rows="3">%2$s</textarea>'
				. '<small>Solo para «Una opción» y «Varias opciones».</small></p>',
			$i,
			esc_textarea( implode( "\n", (array) $p['options'] ) ),
			esc_attr( implode( ' ', array_filter( array_keys( $tipos ), array( RegistrationMetaKeys::class, 'has_options' ) ) ) )
		);

		$html .= self::toggle( 'evt_q_required[' . $i . ']', 'Obligatoria', (bool) $p['required'], 'evt-q-r-' . $i );

		// Una casilla y no un botón: con Intro se envía el primer botón del
		// formulario, y no puede ser el de quitar una pregunta.
		if ( ! $nueva ) {
			$html .= sprintf(
				'<p class="evt-campo evt-pregunta__quitar"><label class="evt-check"><input type="checkbox" name="evt_q_remove[%1$d]" value="1"> Quitar esta pregunta al guardar</label></p>',
				$i
			);
		}

		return $html . '</div></details>';
	}

	/**
	 * The two consent texts, folded (ADR-0020).
	 *
	 * @param array<string, mixed> $a Signup settings.
	 * @return string
	 */
	private static function consent( array $a ): string {
		$html  = '<details class="evt-tarjeta evt-plegable"><summary>Protección de datos <span class="evt-state">versión '
			. (int) $a['consent_version'] . '</span></summary>';
		$html .= self::consent_editor( 'evt_consent_privacy', 'Información sobre el tratamiento de sus datos', (string) $a['consent_privacy'] );
		$html .= self::consent_editor( 'evt_consent_image', 'Consentimiento informado', (string) $a['consent_image'] );

		// Cambiar un texto sube la versión y **no reescribe** la que ya aceptó
		// nadie: por eso el número está a la vista (ADR-0020).
		$html .= '<p class="evt-ayuda">Cambiar cualquiera de los dos textos crea una versión nueva; lo que ya aceptó alguien '
			. 'no se reescribe, y en la lista de participantes se ve qué versión aceptó cada persona.</p>';

		return $html . '</details>';
	}

	/**
	 * One consent text, in the visual editor of WordPress.
	 *
	 * Son textos legales con negritas, listas y enlaces: se escriben como
	 * cualquier contenido de WordPress, sin botón de medios. El HTML se filtra
	 * al guardar con `wp_kses_post()`.
	 *
	 * @param string $name  Field name; also the editor ID.
	 * @param string $label Label.
	 * @param string $value Current text.
	 * @return string
	 */
	private static function consent_editor( string $name, string $label, string $value ): string {
		ob_start();
		wp_editor(
			$value,
			$name,
			array(
				'textarea_name' => $name,
				'textarea_rows' => 8,
				'media_buttons' => false,
			)
		);
		return '<div class="evt-campo"><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label>'
			. (string) ob_get_clean() . '</div>';
	}

	/**
	 * The button on the front page of the event, and signing up somewhere else.
	 *
	 * @param array<string, mixed> $v Stored values of the event.
	 * @return string
	 */
	private static function button( array $v ): string {
		$abierto = '' !== (string) ( $v[ EventMetaKeys::SIGNUP_URL ] ?? '' ) || '' !== (string) ( $v[ EventMetaKeys::SIGNUP_SHOW ] ?? '' );

		$html  = '<details class="evt-tarjeta evt-plegable"' . ( $abierto ? ' open' : '' ) . '><summary>Botón de la portada e inscripción en otro sitio</summary>';
		$html .= self::toggle( EventMetaKeys::SIGNUP_SHOW, 'Mostrar el botón de inscripción en la portada del evento', '' !== (string) ( $v[ EventMetaKeys::SIGNUP_SHOW ] ?? '' ), 'evt-signup-show' );
		$html .= '<div class="evt-form-fila evt-form-fila--2">';
		$html .= sprintf(
			'<p class="evt-campo"><label for="evt-signup-label">Texto del botón</label><input type="text" id="evt-signup-label" name="%1$s" placeholder="Inscríbete" value="%2$s"></p>',
			esc_attr( EventMetaKeys::SIGNUP_LABEL ),
			esc_attr( (string) ( $v[ EventMetaKeys::SIGNUP_LABEL ] ?? '' ) )
		);
		$html .= sprintf(
			'<p class="evt-campo"><label for="evt-signup-url">Dirección, si la inscripción es en otro sitio</label><input type="url" id="evt-signup-url" name="%1$s" placeholder="https://" value="%2$s"><small>Con el formulario de aquí, déjela en blanco.</small></p>',
			esc_attr( EventMetaKeys::SIGNUP_URL ),
			esc_attr( (string) ( $v[ EventMetaKeys::SIGNUP_URL ] ?? '' ) )
		);
		$html .= '</div>';
		$html .= sprintf(
			'<p class="evt-campo"><label for="evt-signup-form">Formulario antiguo <span class="evt-state">Histórico</span></label>'
				. '<input type="number" id="evt-signup-form" name="%1$s" min="0" step="1" value="%2$s">'
				. '<small><strong>No lo rellene en un evento nuevo.</strong> Es el número del formulario del sistema anterior, solo para eventos migrados.</small></p>',
			esc_attr( EventMetaKeys::SIGNUP_FORM_ID ),
			esc_attr( (string) (int) ( $v[ EventMetaKeys::SIGNUP_FORM_ID ] ?? 0 ) )
		);
		return $html . '</details>';
	}

	/**
	 * One switch.
	 *
	 * @param string $nombre Field name.
	 * @param string $rotulo Label.
	 * @param bool   $puesto Whether it is on.
	 * @param string $id     Element ID; the name when empty.
	 * @return string
	 */
	private static function toggle( string $nombre, string $rotulo, bool $puesto, string $id = '' ): string {
		$id = '' !== $id ? $id : $nombre;
		return sprintf(
			'<div class="form-check form-switch evt-interruptor"><input class="form-check-input" type="checkbox" role="switch" id="%1$s" name="%2$s" value="1"%3$s> <label class="form-check-label" for="%1$s">%4$s</label></div>',
			esc_attr( $id ),
			esc_attr( $nombre ),
			checked( true, $puesto, false ),
			esc_html( $rotulo )
		);
	}
}
