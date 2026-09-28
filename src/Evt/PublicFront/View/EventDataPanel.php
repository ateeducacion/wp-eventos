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
use Evt\PublicFront\Shell;

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
		$sel_area  = self::area_checks(
			'evt-area',
			EventWorkspace::FIELD_AREA,
			'Ámbitos organizativos',
			(array) ( $listas['tree'] ?? array() ),
			(string) $v[ EventWorkspace::FIELD_AREA ],
			(array) ( $m['foreign_areas'] ?? array() ),
			(bool) $m['can_set_area']
				? 'Los ámbitos que organizan el evento. Cualquiera de ellos puede editarlo.'
				: 'Seleccione solo ámbitos dentro de su subárbol.',
			(bool) $m['can_set_area']
		);
		$sel_tipo  = self::term_select(
			'evt-type',
			EventWorkspace::FIELD_TYPE,
			'Tipología',
			(array) ( $listas['type'] ?? array() ),
			(int) $v[ EventWorkspace::FIELD_TYPE ],
			'Jornadas, encuentro, congreso, taller… Sirve para agrupar eventos parecidos.'
		);
		$sel_curso = self::term_select(
			'evt-course',
			EventWorkspace::FIELD_COURSE,
			'Curso escolar',
			(array) ( $listas['course'] ?? array() ),
			(int) $v[ EventWorkspace::FIELD_COURSE ],
			'El curso al que pertenece, en la forma 2025-2026.'
		);
		$nuevo     = true === ( $m['nuevo'] ?? false );

		ob_start();
		?>
		<?php if ( ! $nuevo ) : ?>
			<div class="evt-panel-cabecera"><div><h2 class="evt-panel-titulo">Datos del evento</h2><p class="evt-sub">Lo que se anuncia, y de dónde sale el estado: próximo, abierto o finalizado.</p></div></div>
		<?php endif; ?>
		<form class="evt-form" method="post" action="" data-evt-cambios>
			<?php wp_nonce_field( EventWorkspace::nonce_action( EventWorkspace::PANEL_SETTINGS ), EventWorkspace::nonce_name( EventWorkspace::PANEL_SETTINGS ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_DO ); ?>" value="<?php echo esc_attr( EventWorkspace::PANEL_SETTINGS ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_EVENT ); ?>" value="<?php echo esc_attr( (string) (int) $m['event_id'] ); ?>" />

			<fieldset class="evt-tarjeta">
				<legend>Identidad</legend>

				<div class="evt-form-campo">
					<label for="evt-title">Título del evento</label>
					<input type="text" id="evt-title" name="<?php echo esc_attr( EventWorkspace::FIELD_TITLE ); ?>"
						required value="<?php echo esc_attr( (string) $v[ EventWorkspace::FIELD_TITLE ] ); ?>" />
					<small>El nombre completo, tal y como se anuncia. Es el que sale en grande en la cabecera.</small>
				</div>

				<div class="evt-form-fila evt-form-fila--ancha">
					<div>
						<label for="evt-tagline">Lema <span class="evt-opcional">(opcional)</span></label>
						<input type="text" id="evt-tagline" name="<?php echo esc_attr( EventMetaKeys::TAGLINE ); ?>"
							value="<?php echo esc_attr( (string) $v[ EventMetaKeys::TAGLINE ] ); ?>" />
						<small>La línea corta que acompaña al título.</small>
					</div>
					<div>
						<label for="evt-hashtag">Etiqueta de redes</label>
						<input type="text" id="evt-hashtag" name="<?php echo esc_attr( EventMetaKeys::HASHTAG ); ?>"
							value="<?php echo esc_attr( (string) $v[ EventMetaKeys::HASHTAG ] ); ?>" />
						<small>Sin la almohadilla: <code>jornadas25</code>.</small>
					</div>
				</div>

				<div class="evt-form-campo">
					<label for="evt-intro">Texto introductorio</label>
					<textarea id="evt-intro" name="<?php echo esc_attr( EventMetaKeys::INTRO ); ?>" rows="6"><?php echo esc_textarea( (string) $v[ EventMetaKeys::INTRO ] ); ?></textarea>
					<small>Dos o tres párrafos: de qué va y a quién se dirige. Se lee en la portada, debajo de la cabecera.</small>
				</div>
			</fieldset>

			<fieldset class="evt-tarjeta">
				<legend>Cuándo y dónde</legend>

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

				<div class="evt-form-fila">
					<div><?php echo $sel_area; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></div>
					<div><?php echo $sel_tipo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></div>
					<div><?php echo $sel_curso; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></div>
				</div>
			</fieldset>

			<?php echo PanelParts::save_bar( $nuevo ? 'Crear el evento' : 'Guardar los datos' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Multi-value scope choices, as a tree, with their help line.
	 *
	 * Un árbol y no una lista de rutas: con «A › B › C» en cada casilla, el
	 * ámbito que se elige queda al final de una línea larga, y los de la
	 * misma rama no se ven juntos. Cada ámbito va sangrado bajo el suyo; los
	 * que están encima de lo que esta persona puede elegir salen sin casilla,
	 * como contexto. Con guion, las ramas se pliegan y se despliegan, y
	 * arrancan abiertas solo las que tienen algo marcado.
	 *
	 * @param string            $id      Field id.
	 * @param string            $nombre  Field name.
	 * @param string            $rotulo  Label.
	 * @param array<int, array> $terminos Rows of EventTaxonomies::area_tree_for().
	 * @param string            $elegidos Comma-separated selected term IDs.
	 * @param string[]          $foreign Read-only organiser labels.
	 * @param string            $ayuda   Help text.
	 * @param bool              $todos   Whether any scope may be chosen, which only the administration can.
	 * @return string
	 */
	private static function area_checks( string $id, string $nombre, string $rotulo, array $terminos, string $elegidos, array $foreign, string $ayuda, bool $todos = false ): string {
		$ids = array_map( 'absint', explode( ',', $elegidos ) );
		ob_start();
		?>
		<fieldset class="evt-ambitos"><legend><?php echo esc_html( $rotulo ); ?></legend>
			<input type="hidden" name="evt_area_present" value="1" />
			<?php echo self::area_tree( $nombre, $terminos, $ids ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			<?php if ( $todos ) : ?>
				<?php echo Shell::admin_note( 'Puede asignar cualquier ámbito, no solo los de su subárbol.' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			<?php endif; ?>
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
	 * The nested lists of the scope tree.
	 *
	 * Las filas llegan en orden de árbol con su profundidad; aquí se abren y
	 * se cierran las listas anidadas según sube o baja.
	 *
	 * @param string                           $nombre Field name.
	 * @param array<int, array<string, mixed>> $filas  Tree rows.
	 * @param int[]                            $ids    Selected term IDs.
	 * @return string
	 */
	private static function area_tree( string $nombre, array $filas, array $ids ): string {
		if ( array() === $filas ) {
			return '<p class="evt-ayuda">No hay ningún ámbito que pueda elegir. Pídalo a quien administre el aplicativo.</p>';
		}

		$html  = '<ul class="evt-arbol" data-evt-arbol>';
		$nivel = (int) $filas[0]['depth'];
		$total = count( $filas );
		foreach ( $filas as $i => $fila ) {
			$profundidad = (int) $fila['depth'];
			$tiene_hijos = $i + 1 < $total && (int) $filas[ $i + 1 ]['depth'] > $profundidad;

			$html .= '<li' . ( $tiene_hijos ? ' class="evt-arbol__rama"' : '' ) . '><div class="evt-arbol__fila">';
			if ( $tiene_hijos ) {
				$html .= '<button type="button" class="evt-arbol__plegar" aria-expanded="true" hidden data-evt-arbol-plegar>'
					. '<span class="screen-reader-text">' . esc_html( sprintf( 'Mostrar u ocultar lo que cuelga de %s', (string) $fila['name'] ) ) . '</span></button>';
			}
			if ( ! empty( $fila['selectable'] ) ) {
				$html .= sprintf(
					'<label title="%4$s"><input type="checkbox" name="%1$s[]" value="%2$d"%3$s /> %5$s</label>',
					esc_attr( $nombre ),
					(int) $fila['id'],
					checked( in_array( (int) $fila['id'], $ids, true ), true, false ),
					esc_attr( (string) $fila['path'] ),
					esc_html( (string) $fila['name'] )
				);
			} else {
				$html .= '<span class="evt-arbol__contexto" title="' . esc_attr( (string) $fila['path'] ) . '">'
					. esc_html( (string) $fila['name'] ) . '</span>';
			}
			$html .= '</div>';

			if ( $tiene_hijos ) {
				$html .= '<ul>';
				continue;
			}
			$html .= '</li>';

			// Al volver a un nivel de arriba se cierran las ramas que acaban aquí.
			$siguiente = $i + 1 < $total ? (int) $filas[ $i + 1 ]['depth'] : $nivel;
			for ( $d = $profundidad; $d > $siguiente; $d-- ) {
				$html .= '</ul></li>';
			}
		}
		return $html . '</ul>';
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
