<?php
/**
 * The «Código» panel of the event workshop.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

use Evt\Meta\EventMetaKeys;
use Evt\PublicFront\Assets;
use Evt\PublicFront\CodeEditor;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\Shell;

/**
 * El CSS y el JavaScript del evento: los del raíz, los de todas sus páginas.
 *
 * Los dos campos no se pintan igual porque no son la misma cosa. El CSS es un
 * campo corriente de la pestaña, como el color de la cabecera de «Apariencia»:
 * lo escribe también el área, acotada a la suya. El JavaScript va en el
 * recuadro amarillo —la convención del aplicativo para lo que solo ve quien
 * administra, {@see Shell::admin_box()}—, porque se ejecuta en el navegador de
 * cada visitante y esa es la única raya que el área no cruza.
 *
 * Quien no ve un campo no se queda con la duda de si falta algo: en su sitio se
 * dice en una línea por qué no está. Un hueco callado se lee como una avería.
 *
 * Solo pinta: no lee la petición, no consulta, no decide.
 */
final class EventCodePanel {

	/**
	 * Why the yellow box is there, for the JavaScript field.
	 */
	private const WHY_JS = 'Este recuadro solo lo ve quien administra el aplicativo. El CSS de arriba lo escribe también quien organiza el evento; esto no, porque no cambia cómo se ve una página: ejecuta un programa en el navegador de quien la visite.';

	/**
	 * Paint the panel.
	 *
	 * @param array<string, mixed> $m What EventWorkspace::model() returned.
	 * @return string
	 */
	public static function html( array $m ): string {
		$css_ok = (bool) $m['can_edit_css'];
		$js_ok  = (bool) $m['can_edit_js'];
		$codigo = (array) $m['code'];

		$bloque_css = $css_ok ? self::css_card( (string) ( $codigo['css'] ?? '' ) ) : self::missing_css();
		$bloque_js  = $js_ok ? self::js_box( (string) ( $codigo['js'] ?? '' ) ) : self::missing_js();

		ob_start();
		?>
		<form class="evt-form" method="post" action="">
			<?php wp_nonce_field( EventWorkspace::nonce_action( EventWorkspace::PANEL_CODE ), EventWorkspace::nonce_name( EventWorkspace::PANEL_CODE ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_DO ); ?>" value="<?php echo esc_attr( EventWorkspace::PANEL_CODE ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_EVENT ); ?>" value="<?php echo esc_attr( (string) (int) $m['event_id'] ); ?>" />

			<p>
				Lo que escriba aquí se aplica a <strong>todas</strong> las páginas de este evento: a la
				portada y a cada una de sus secciones. Nunca sale del evento, así que no afecta al resto
				del sitio. Cada sección puede añadir además el suyo propio, que va después de este y sirve
				para afinarlo.
			</p>

			<?php echo $bloque_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			<?php echo $bloque_js; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>

			<?php if ( $css_ok || $js_ok ) : ?>
				<p class="evt-acciones">
					<button class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" type="submit">Guardar el código</button>
				</p>
			<?php endif; ?>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The CSS field, as a plain card of the tab.
	 *
	 * @param string $valor What is stored, raw.
	 * @return string
	 */
	private static function css_card( string $valor ): string {
		$campo = CodeEditor::field(
			array(
				'mode'  => CodeEditor::MODE_CSS,
				'name'  => EventMetaKeys::CUSTOM_CSS,
				'id'    => 'evt-custom-css',
				'label' => 'CSS del evento',
				'value' => $valor,
				'help'  => 'Reglas de estilo, tal cual las escribiría en una hoja: un selector, una llave y las propiedades dentro. No hace falta la etiqueta <style>, se pone sola. Deje el campo vacío para no aplicar ninguna.',
			)
		);

		ob_start();
		?>
		<fieldset class="evt-tarjeta">
			<legend>Hoja de estilos a medida</legend>
			<p>
				Para los retoques que «Apariencia» no cubre. Se imprime en la cabecera de cada página del
				evento, la última de todas, así que entre dos reglas iguales gana la suya. Si aun así algo
				no cambia, es que la regla del aplicativo apunta más fino: escriba la suya con el mismo
				detalle. Y si el editor no la acepta, mire su margen izquierdo: el comprobador señala ahí
				los errores de sintaxis.
			</p>
			<?php echo $campo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		</fieldset>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The JavaScript field, in its yellow box, with the warning it deserves.
	 *
	 * @param string $valor What is stored, raw.
	 * @return string
	 */
	private static function js_box( string $valor ): string {
		$campo = CodeEditor::field(
			array(
				'mode'  => CodeEditor::MODE_JS,
				'name'  => EventMetaKeys::CUSTOM_JS,
				'id'    => 'evt-custom-js',
				'label' => 'JavaScript del evento',
				'value' => $valor,
				'help'  => 'Código JavaScript, sin la etiqueta <script>: se pone sola. Se ejecuta al final de la página, con el documento ya cargado. Deje el campo vacío para no ejecutar nada.',
			)
		);

		ob_start();
		?>
		<p class="<?php echo esc_attr( Assets::alert_class( 'warning' ) ); ?>">
			<strong>Esto se ejecuta en el navegador de quien visite las páginas de este evento.</strong>
			No es un ajuste de aspecto: es un programa, y puede leer y cambiar lo que la persona ve, seguir
			lo que hace o pedir datos a otros sitios. Pegue aquí únicamente código que entienda y del que
			responda; si lo copia de un tercero, léalo entero antes.
		</p>
		<?php
		$aviso = (string) ob_get_clean();

		return Shell::admin_box( 'JavaScript a medida', $aviso . $campo, self::WHY_JS );
	}

	/**
	 * What is said instead of the CSS field when it is not allowed.
	 *
	 * Es el caso raro —el CSS lo escriben el área y la administración—, así que
	 * basta con decirlo y no repetir por qué.
	 *
	 * @return string
	 */
	private static function missing_css(): string {
		ob_start();
		?>
		<fieldset class="evt-tarjeta">
			<legend>Hoja de estilos a medida</legend>
			<p>
				Su perfil no puede editar el CSS de este evento, así que el campo no se enseña. El que ya
				hubiera guardado sigue aplicándose tal cual: no se ha perdido nada.
			</p>
		</fieldset>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The one line that replaces the JavaScript field for whoever may not write it.
	 *
	 * Una línea y no un recuadro amarillo: el amarillo es para lo que se ve, y
	 * aquí justamente no hay nada que ver. Lo que hace falta es que quien
	 * organiza el evento sepa que la pestaña está completa y que el campo que
	 * no está no le falta a nadie.
	 *
	 * @return string
	 */
	private static function missing_js(): string {
		ob_start();
		?>
		<p>
			Su perfil no puede editar el JavaScript de este evento, así que ese campo no aparece: la
			pestaña está completa y no le falta nada. El JavaScript ejecuta un programa en el navegador de
			cada visitante, y por eso lo escribe solo quien administra el aplicativo. Si su evento
			necesita uno, pídalo indicando qué tiene que hacer.
		</p>
		<?php
		return (string) ob_get_clean();
	}
}
