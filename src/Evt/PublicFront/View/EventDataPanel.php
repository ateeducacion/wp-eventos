<?php
/**
 * The «Datos» panel of the event workshop.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

use Evt\Meta\EventMetaKeys;
use Evt\PublicFront\Assets;
use Evt\PublicFront\EventWorkspace;

/**
 * Qué es el evento: identidad, cuándo y dónde, clasificación e inscripción.
 *
 * Hoy son cinco secciones sueltas dentro de un formulario de 139 campos que
 * sirve además para cada página satélite. Aquí son cuatro tarjetas, con lo
 * que va junto junto, y bajo cada campo una línea en castellano llano que
 * dice para qué sirve: sin ella, «Lema» y «Hashtag» se rellenan a ojo y luego
 * salen en la cabecera del evento.
 *
 * Solo pinta: no lee la petición, no consulta, no decide.
 */
final class EventDataPanel {

	/**
	 * Paint the panel.
	 *
	 * @param array<string, mixed> $m What EventWorkspace::model() returned.
	 * @return string
	 */
	public static function html( array $m ): string {
		$v      = (array) $m['values'];
		$listas = (array) $m['terms'];

		// Los desplegables se arman antes de la plantilla: dentro de cada
		// ayudante la salida ya va escapada, y así cada `echo` de la plantilla
		// es de una sola línea.
		$sel_area    = self::area_checks(
			'evt-area',
			EventWorkspace::FIELD_AREA,
			'Ámbitos organizativos',
			(array) ( $listas['area'] ?? array() ),
			(string) $v[ EventWorkspace::FIELD_AREA ],
			(array) ( $m['foreign_areas'] ?? array() ),
			(bool) $m['can_set_area']
				? 'Los ámbitos que organizan el evento. Cualquiera de ellos puede editarlo.'
				: 'Seleccione solo ámbitos dentro de su subárbol.'
		);
		$sel_tipo    = self::term_select(
			'evt-type',
			EventWorkspace::FIELD_TYPE,
			'Tipología',
			(array) ( $listas['type'] ?? array() ),
			(int) $v[ EventWorkspace::FIELD_TYPE ],
			'Jornadas, encuentro, congreso, taller… Sirve para agrupar eventos parecidos.'
		);
		$sel_curso   = self::term_select(
			'evt-course',
			EventWorkspace::FIELD_COURSE,
			'Curso escolar',
			(array) ( $listas['course'] ?? array() ),
			(int) $v[ EventWorkspace::FIELD_COURSE ],
			'El curso al que pertenece, en la forma 2025-2026.'
		);
		$inscripcion = self::signup_card( $v );

		ob_start();
		?>
		<form class="evt-form" method="post" action="">
			<?php wp_nonce_field( EventWorkspace::nonce_action( EventWorkspace::PANEL_SETTINGS ), EventWorkspace::nonce_name( EventWorkspace::PANEL_SETTINGS ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_DO ); ?>" value="<?php echo esc_attr( EventWorkspace::PANEL_SETTINGS ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_EVENT ); ?>" value="<?php echo esc_attr( (string) (int) $m['event_id'] ); ?>" />

			<fieldset class="evt-tarjeta">
				<legend>Identidad</legend>
				<p>Cómo se llama el evento y qué se lee de él antes de entrar.</p>

				<div class="evt-form-campo">
					<label for="evt-title">Título del evento</label>
					<input type="text" id="evt-title" name="<?php echo esc_attr( EventWorkspace::FIELD_TITLE ); ?>"
						required value="<?php echo esc_attr( (string) $v[ EventWorkspace::FIELD_TITLE ] ); ?>" />
					<small>El nombre completo, tal y como se anuncia. Es el que sale en grande en la cabecera.</small>
				</div>

				<div class="evt-form-fila">
					<div>
						<label for="evt-tagline">Lema</label>
						<input type="text" id="evt-tagline" name="<?php echo esc_attr( EventMetaKeys::TAGLINE ); ?>"
							value="<?php echo esc_attr( (string) $v[ EventMetaKeys::TAGLINE ] ); ?>" />
						<small>La línea corta que acompaña al título. Puede dejarse en blanco.</small>
					</div>
					<div>
						<label for="evt-hashtag">Etiqueta de redes</label>
						<input type="text" id="evt-hashtag" name="<?php echo esc_attr( EventMetaKeys::HASHTAG ); ?>"
							value="<?php echo esc_attr( (string) $v[ EventMetaKeys::HASHTAG ] ); ?>" />
						<small>Sin la almohadilla: escriba <code>jornadas25</code>, no <code>#jornadas25</code>.</small>
					</div>
				</div>

				<div class="evt-form-campo">
					<label for="evt-intro">Texto introductorio</label>
					<textarea id="evt-intro" name="<?php echo esc_attr( EventMetaKeys::INTRO ); ?>" rows="6"><?php echo esc_textarea( (string) $v[ EventMetaKeys::INTRO ] ); ?></textarea>
					<small>Dos o tres párrafos que expliquen de qué va y a quién se dirige. Es lo que se lee en la portada del evento, debajo de la cabecera.</small>
				</div>
			</fieldset>

			<fieldset class="evt-tarjeta">
				<legend>Cuándo y dónde</legend>
				<p>De estas fechas sale el estado del evento —próximo, abierto o finalizado—, así que no hay que marcarlo a mano en ningún sitio.</p>

				<div class="evt-form-fila">
					<div>
						<label for="evt-start">Fecha de inicio</label>
						<input type="date" id="evt-start" name="<?php echo esc_attr( EventMetaKeys::START_DATE ); ?>"
							required value="<?php echo esc_attr( (string) $v[ EventMetaKeys::START_DATE ] ); ?>" />
						<small>El primer día del evento.</small>
					</div>
					<div>
						<label for="evt-end">Fecha de fin</label>
						<input type="date" id="evt-end" name="<?php echo esc_attr( EventMetaKeys::END_DATE ); ?>"
							value="<?php echo esc_attr( (string) $v[ EventMetaKeys::END_DATE ] ); ?>" />
						<small>Déjela en blanco si el evento dura un solo día.</small>
					</div>
				</div>

				<div class="evt-form-campo">
					<label for="evt-venue">Sedes</label>
					<input type="text" id="evt-venue" name="<?php echo esc_attr( EventMetaKeys::VENUE ); ?>"
						value="<?php echo esc_attr( (string) $v[ EventMetaKeys::VENUE ] ); ?>" />
					<small>Dónde ocurre, tal y como se anuncia. Si son varias, sepárelas con comas; si es en línea, escríbalo así.</small>
				</div>
			</fieldset>

			<fieldset class="evt-tarjeta">
				<legend>Clasificación</legend>
				<p>Con qué se ordena y se busca el evento. Cada ámbito seleccionado puede editarlo.</p>

				<div class="evt-form-fila">
					<div><?php echo $sel_area; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></div>
					<div><?php echo $sel_tipo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></div>
					<div><?php echo $sel_curso; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></div>
				</div>
			</fieldset>

			<?php echo $inscripcion; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>

			<p class="evt-acciones">
				<button class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" type="submit">
					<?php echo esc_html( true === ( $m['nuevo'] ?? false ) ? 'Crear el evento' : 'Guardar los datos' ); ?>
				</button>
			</p>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The «Inscripción» card.
	 *
	 * @param array<string, string> $v Field values.
	 * @return string
	 */
	private static function signup_card( array $v ): string {
		ob_start();
		?>
			<fieldset class="evt-tarjeta">
				<legend>Inscripción</legend>
				<p>Las inscripciones siguen llevándose en el sistema anterior: aquí solo se dice si la portada enseña el botón y a dónde lleva.</p>

				<div class="evt-form-campo">
					<label for="evt-signup-show">
						<input type="checkbox" id="evt-signup-show" name="<?php echo esc_attr( EventMetaKeys::SIGNUP_SHOW ); ?>" value="1"
							<?php checked( '' !== (string) $v[ EventMetaKeys::SIGNUP_SHOW ] ); ?> />
						Mostrar el botón de inscripción en la portada del evento
					</label>
					<small>Desmárquelo cuando el plazo se cierre: el botón desaparece y no hay que tocar la página.</small>
				</div>

				<div class="evt-form-fila">
					<div>
						<label for="evt-signup-label">Texto del botón</label>
						<input type="text" id="evt-signup-label" name="<?php echo esc_attr( EventMetaKeys::SIGNUP_LABEL ); ?>"
							placeholder="Inscríbete" value="<?php echo esc_attr( (string) $v[ EventMetaKeys::SIGNUP_LABEL ] ); ?>" />
						<small>Lo que se lee dentro del botón. En blanco, pone «Inscríbete».</small>
					</div>
					<div>
						<label for="evt-signup-form">Formulario antiguo <span class="evt-state evt-state-draft">Histórico</span></label>
						<input type="number" id="evt-signup-form" name="<?php echo esc_attr( EventMetaKeys::SIGNUP_FORM_ID ); ?>"
							min="0" step="1" value="<?php echo esc_attr( (string) (int) $v[ EventMetaKeys::SIGNUP_FORM_ID ] ); ?>" />
						<small><strong>No lo rellene en un evento nuevo.</strong> Es el número del formulario de inscripción del sistema anterior, y está aquí solo para que los eventos migrados sigan viéndose igual. Los participantes de este aplicativo se gestionan en la pestaña «Participantes». <strong>Este campo desaparecerá.</strong></small>
					</div>
				</div>

				<div class="evt-form-campo">
					<label for="evt-signup-url">Dirección a la que lleva el botón</label>
					<input type="url" id="evt-signup-url" name="<?php echo esc_attr( EventMetaKeys::SIGNUP_URL ); ?>"
						placeholder="https://" value="<?php echo esc_attr( (string) $v[ EventMetaKeys::SIGNUP_URL ] ); ?>" />
					<small>Solo si la inscripción está fuera de este sitio. Con formulario propio, déjelo en blanco.</small>
				</div>
			</fieldset>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Multi-value scope choices, with their help line.
	 *
	 * @param string             $id      Field id.
	 * @param string             $nombre  Field name.
	 * @param string             $rotulo  Label.
	 * @param array<int, string> $terminos term_id => nombre.
	 * @param string             $elegidos Comma-separated selected term IDs.
	 * @param string[]           $foreign Read-only organiser labels.
	 * @param string             $ayuda   Help text.
	 * @return string
	 */
	private static function area_checks( string $id, string $nombre, string $rotulo, array $terminos, string $elegidos, array $foreign, string $ayuda ): string {
		$ids = array_map( 'absint', explode( ',', $elegidos ) );
		ob_start();
		?>
		<fieldset class="evt-ambitos"><legend><?php echo esc_html( $rotulo ); ?></legend>
			<input type="hidden" name="evt_area_present" value="1" />
			<?php foreach ( $terminos as $term_id => $texto ) : ?>
				<label><input type="checkbox" name="<?php echo esc_attr( $nombre ); ?>[]" value="<?php echo esc_attr( (string) $term_id ); ?>" <?php checked( in_array( (int) $term_id, $ids, true ) ); ?> /> <?php echo esc_html( $texto ); ?></label><br />
			<?php endforeach; ?>
			<?php if ( $foreign ) : ?>
				<p>Otros ámbitos organizadores (solo lectura):</p>
				<ul>
				<?php
				foreach ( $foreign as $label ) :
					?>
					<li><?php echo esc_html( $label ); ?></li><?php endforeach; ?></ul>
				<small>Se conservarán al guardar. Solo administración o una persona de ese ámbito puede modificar su participación.</small><br />
			<?php endif; ?>
			<small><?php echo esc_html( $ayuda ); ?></small>
		</fieldset>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * One taxonomy dropdown, with its help line.
	 *
	 * @param string             $id       Field id.
	 * @param string             $nombre   Field name.
	 * @param string             $rotulo   Label.
	 * @param array<int, string> $terminos Term labels.
	 * @param int                $elegido  Selected term ID.
	 * @param string             $ayuda    Help text.
	 * @return string
	 */
	private static function term_select( string $id, string $nombre, string $rotulo, array $terminos, int $elegido, string $ayuda ): string {
		ob_start();
		?>
		<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $rotulo ); ?></label>
		<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $nombre ); ?>">
			<option value="0">— Sin asignar —</option>
			<?php foreach ( $terminos as $term_id => $texto ) : ?>
				<option value="<?php echo esc_attr( (string) (int) $term_id ); ?>" <?php selected( (int) $term_id, $elegido ); ?>>
					<?php echo esc_html( (string) $texto ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<small><?php echo esc_html( $ayuda ); ?></small>
		<?php
		return (string) ob_get_clean();
	}
}
