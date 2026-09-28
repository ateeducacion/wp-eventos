<?php
/**
 * The «Secciones» panel of the event workshop.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

use Evt\PublicFront\Assets;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\SectionIcons;
use Evt\PublicFront\Shell;

/**
 * La pantalla que hoy no existe: las páginas satélite de un evento, en una
 * tabla, con su orden y sus acciones.
 *
 * Hoy, para saber qué páginas tiene un evento hay que abrir el menú del
 * propio evento en la web pública y contarlas, y para cambiar el orden hay
 * que reabrir el formulario enorme de cada una y teclear a mano su número de
 * orden. Aquí el orden es `menu_order` y se cambia con dos flechas.
 *
 * Solo pinta: no lee la petición, no consulta, no decide.
 */
final class EventSectionsPanel {

	/**
	 * The publish state of one section, as a switch.
	 *
	 * El mismo interruptor que el listado de eventos, y por el mismo motivo:
	 * de un vistazo se ve si la página está en el menú del evento, y se cambia
	 * tocándolo. Sin JavaScript queda el botón de al lado.
	 *
	 * @param array<string, mixed> $m    Model.
	 * @param array<string, mixed> $fila One row.
	 * @return string
	 */
	private static function publish_switch( array $m, array $fila ): string {
		$id        = (int) $fila['id'];
		$publicada = (bool) $fila['published'];
		$op        = $publicada ? 'unpublish' : 'publish';
		$rotulo    = $publicada ? 'Despublicar esta página' : 'Publicar esta página';

		ob_start();
		?>
		<form class="evt-accion evt-switch" method="post" action="">
			<?php wp_nonce_field( EventWorkspace::nonce_action( $op ), EventWorkspace::nonce_name( $op, $id ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_EVENT ); ?>" value="<?php echo esc_attr( (string) (int) $m['event_id'] ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_SECTION ); ?>" value="<?php echo esc_attr( (string) $id ); ?>" />
			<label class="evt-switch-caja" title="<?php echo esc_attr( $rotulo ); ?>" data-bs-toggle="tooltip">
				<input type="checkbox" class="evt-switch-input" data-evt-switch <?php checked( $publicada, true ); ?> />
				<span class="evt-switch-pista" aria-hidden="true"></span>
				<span class="evt-switch-txt"><?php echo esc_html( (string) $fila['status_label'] ); ?></span>
				<span class="screen-reader-text"><?php echo esc_html( $rotulo ); ?></span>
			</label>
			<button type="submit" class="<?php echo esc_attr( Assets::button_class() . ' evt-mini evt-switch-boton' ); ?>"><?php echo esc_html( $publicada ? 'Despublicar' : 'Publicar' ); ?></button>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Paint the panel: the sections of the event, or its trash.
	 *
	 * @param array<string, mixed> $m What EventWorkspace::model() returned.
	 * @return string
	 */
	public static function html( array $m ): string {
		return true === $m['trash'] ? self::trash( $m ) : self::live( $m );
	}

	/**
	 * The sections of the event: what is not in the trash.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function live( array $m ): string {
		$filas = (array) $m['sections'];

		ob_start();
		?>
		<div class="evt-panel-cabecera"><div>
			<h2 class="evt-panel-titulo">Páginas</h2>
			<p class="evt-sub">En el orden en que salen en el menú del evento. Despublicar una la quita del menú sin perder nada de lo escrito. Para que una salga en el menú pero no en la portada, desmarque su tarjeta al editarla.</p>
		</div></div>

		<?php echo self::trash_link( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		<?php echo self::add_form( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>

		<?php if ( array() === $filas ) : ?>
			<div class="evt-tabla-caja">
				<p class="evt-vacio">Este evento todavía no tiene ninguna sección. Añada la primera arriba.</p>
			</div>
		<?php else : ?>
			<div class="evt-tabla-caja">
				<table class="evt-tabla">
					<thead>
						<tr>
							<th scope="col">Orden</th>
							<th scope="col">Tipo</th>
							<th scope="col">Título</th>
							<th scope="col">Dirección</th>
							<th scope="col">Estado</th>
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
		<?php endif; ?>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * «Papelera (N)»: el enlace a lo que se envió a ella, si hay algo.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function trash_link( array $m ): string {
		$cuantas = count( (array) $m['trashed'] );
		if ( 0 === $cuantas ) {
			return '';
		}

		return '<p class="evt-acciones"><a href="'
			. esc_url( EventWorkspace::url( (int) $m['event_id'], EventWorkspace::PANEL_SECTIONS, array( EventWorkspace::ARG_TRASH => 1 ) ) )
			. '">' . esc_html( sprintf( 'Papelera (%d)', $cuantas ) ) . '</a></p>';
	}

	/**
	 * The trash of the event: what was sent to it, and how to bring it back.
	 *
	 * El borrado definitivo no está aquí a propósito: lo hace quien pueda desde
	 * el escritorio de WordPress. Desde el aplicativo nada se destruye.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function trash( array $m ): string {
		$filas  = (array) $m['trashed'];
		$volver = EventWorkspace::url( (int) $m['event_id'], EventWorkspace::PANEL_SECTIONS );
		$clases = Assets::button_class() . ' evt-mini';

		ob_start();
		?>
		<p class="evt-sub">
			Las secciones de este evento que se enviaron a la papelera. Nada se ha
			perdido: al restaurar una vuelve en borrador, así que no reaparece en
			el menú del evento hasta que la publique. Borrar algo de verdad y para
			siempre lo hace quien administra, desde el escritorio de WordPress:
			desde aquí no se destruye nada.
		</p>

		<p class="evt-acciones">
			<a href="<?php echo esc_url( $volver ); ?>">Volver a las secciones</a>
		</p>

		<?php if ( array() === $filas ) : ?>
			<div class="evt-tabla-caja">
				<p class="evt-vacio">La papelera de este evento está vacía.</p>
			</div>
		<?php else : ?>
			<div class="evt-tabla-caja">
				<table class="evt-tabla">
					<thead>
						<tr>
							<th scope="col">Tipo</th>
							<th scope="col">Título</th>
							<th scope="col">Dirección</th>
							<th scope="col">Acciones</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $filas as $fila ) : ?>
							<?php $fila = (array) $fila; ?>
							<tr>
								<td data-rotulo="Tipo"><?php echo esc_html( (string) $fila['type_label'] ); ?></td>
								<td data-rotulo="Título"><?php echo esc_html( self::title_of( $fila ) ); ?></td>
								<td class="evt-slug" data-rotulo="Dirección"><?php echo esc_html( '' !== (string) $fila['slug'] ? (string) $fila['slug'] : '—' ); ?></td>
								<td data-rotulo="Acciones">
									<span class="evt-acciones">
										<?php
										$boton = self::action_form( $m, (int) $fila['id'], 'restore', 'restaurar', 'Restaurar la sección, en borrador', $clases );
										echo $boton; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
										?>
									</span>
								</td>
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
	 * «Añadir sección»: se elige el tipo y se abre el formulario de la sección.
	 *
	 * Va por GET porque no muta nada: la sección se crea al guardar, en su
	 * propia pantalla y con su propio nonce.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function add_form( array $m ): string {
		$base = (string) $m['section_url'];
		if ( '' === $base ) {
			return '<p class="' . esc_attr( Assets::alert_class( 'warning' ) ) . '">'
				. esc_html( 'Todavía no existe la página del formulario de secciones, así que no se pueden añadir ni editar. Lo resuelve quien despliega el aplicativo.' )
				. '</p>';
		}

		// Sin enlaces bonitos el permalink es un `?page_id=<n>`, y un
		// `<form method="get">` tira lo que ya lleva la dirección: se vuelve a
		// poner como campos ocultos.
		$accion  = (string) strtok( $base, '?' );
		$ocultos = array();
		parse_str( (string) wp_parse_url( $base, PHP_URL_QUERY ), $ocultos );
		$ocultos[ EventWorkspace::ARG_EVENT ] = (string) (int) $m['event_id'];

		ob_start();
		?>
		<form class="evt-form evt-tarjeta" method="get" action="<?php echo esc_url( $accion ); ?>" data-evt-marco>
			<h2>Añadir sección</h2>
			<p>Elija qué va a ser la página nueva. El tipo decide los textos por defecto y el icono con que sale en la portada del evento.</p>
			<label for="evt-add-tipo">Tipo de sección</label>
			<div class="evt-campo-con-boton">
				<select id="evt-add-tipo" name="<?php echo esc_attr( EventWorkspace::ARG_TYPE ); ?>">
					<?php foreach ( (array) $m['section_types'] as $slug => $rotulo ) : ?>
						<option value="<?php echo esc_attr( (string) $slug ); ?>"><?php echo esc_html( (string) $rotulo ); ?></option>
					<?php endforeach; ?>
				</select>
				<button class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" type="submit">
					<?php echo wp_kses( Shell::icon_plus(), PanelParts::SVG ); ?> Añadir sección
				</button>
			</div>
			<small>Si ninguna encaja, elija «Otra» y póngale el título que quiera.</small>
			<?php foreach ( $ocultos as $clave => $valor ) : ?>
				<input type="hidden" name="<?php echo esc_attr( (string) $clave ); ?>" value="<?php echo esc_attr( (string) $valor ); ?>" />
			<?php endforeach; ?>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * One row of the table, with its actions.
	 *
	 * @param array<string, mixed> $m    Model.
	 * @param array<string, mixed> $fila One row of $m['sections'].
	 * @return string
	 */
	private static function row( array $m, array $fila ): string {
		$id     = (int) $fila['id'];
		$titulo = self::title_of( $fila );
		$mini   = Assets::button_class() . ' evt-mini';

		// Los botones se arman antes de la plantilla, cada uno con su nonce.
		$subir  = self::action_form( $m, $id, 'up', 'subir', 'Subir una posición', $mini, (bool) $fila['first'] );
		$bajar  = self::action_form( $m, $id, 'down', 'bajar', 'Bajar una posición', $mini, (bool) $fila['last'] );
		$borrar = self::action_form(
			$m,
			$id,
			'delete',
			'papelera',
			'Enviar a la papelera',
			$mini . ' evt-btn-borrar',
			false,
			sprintf( '¿Enviar «%s» a la papelera? Dejará de verse en el evento.', $titulo )
		);
		$estado = '';
		if ( (bool) $m['can_publish'] ) {
			$estado = self::publish_switch( $m, $fila );
		}

		ob_start();
		?>
		<tr>
			<td class="evt-num" data-rotulo="Orden"><?php echo esc_html( (string) (int) $fila['order'] ); ?></td>
			<td data-rotulo="Tipo">
				<span class="evt-tipo-seccion">
					<?php echo wp_kses( SectionIcons::svg( (string) ( $fila['icon'] ?? '' ), 18 ), SectionIcons::KSES ); ?>
					<?php echo esc_html( (string) $fila['type_label'] ); ?>
				</span>
			</td>
			<td data-rotulo="Título">
				<?php if ( $fila['published'] && '' !== (string) $fila['view_url'] ) : ?>
					<a href="<?php echo esc_url( (string) $fila['view_url'] ); ?>"><?php echo esc_html( $titulo ); ?></a>
				<?php else : ?>
					<?php echo esc_html( $titulo ); ?>
				<?php endif; ?>
				<?php if ( ! empty( $fila['own_look'] ) ) : ?>
					<span class="evt-state evt-state-propia evt-marca-fila" title="<?php echo esc_attr( 'No sigue al evento en: ' . implode( ', ', (array) $fila['own_look'] ) ); ?>" data-bs-toggle="tooltip">Apariencia propia</span>
				<?php endif; ?>
				<?php if ( ! empty( $fila['home_hidden'] ) ) : ?>
					<span class="evt-state evt-marca-fila" title="Sale en el menú, pero no tiene tarjeta en la portada del evento" data-bs-toggle="tooltip">Sin tarjeta en la portada</span>
				<?php endif; ?>
			</td>
			<td class="evt-slug" data-rotulo="Dirección"><?php echo esc_html( '' !== (string) $fila['slug'] ? (string) $fila['slug'] : '—' ); ?></td>
			<td data-rotulo="Estado">
				<?php if ( '' !== $estado ) : ?>
					<?php echo $estado; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<?php else : ?>
					<span class="<?php echo esc_attr( $fila['published'] ? 'evt-state evt-state-publish' : 'evt-state evt-state-draft' ); ?>">
						<?php echo esc_html( (string) $fila['status_label'] ); ?>
					</span>
				<?php endif; ?>
			</td>
			<td data-rotulo="Acciones">
				<span class="evt-acciones">
					<?php
					$acciones = PanelParts::icon_link( (string) $fila['edit_url'], 'lapiz', 'Editar esta página', 'evt-abre-marco' )
						. PanelParts::icon_link(
							(string) $fila['view_url'],
							'ojo',
							$fila['published'] ? 'Ver esta página' : 'Previsualizar esta página, que está en borrador',
							'evt-abre-vista'
						);
					echo $acciones; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
					?>
					<?php echo $subir; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
					<?php echo $bajar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
					<?php echo $borrar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				</span>
			</td>
		</tr>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * One row action: its own little POST form, with its own nonce.
	 *
	 * @param array<string, mixed> $m         Model.
	 * @param int                  $id        Satellite page ID.
	 * @param string               $op        Operation.
	 * @param string               $icono     Icon name for {@see Shell::icon()}.
	 * @param string               $titulo    What it does, in Spanish.
	 * @param string               $clases    Button classes.
	 * @param bool                 $apagado   Whether the button is disabled.
	 * @param string               $confirmar Question to ask before submitting.
	 * @return string
	 */
	private static function action_form( array $m, int $id, string $op, string $icono, string $titulo, string $clases, bool $apagado = false, string $confirmar = '' ): string {
		// El mismo botón que las filas de ponentes y actividades; la fila viaja en su propio campo.
		return PanelParts::action( $m, $id, $op, $icono, $titulo, $clases, '', $apagado, $confirmar, EventWorkspace::FIELD_SECTION );
	}

	/**
	 * The title of a section, or a stand-in when it has none.
	 *
	 * @param array<string, mixed> $fila One row.
	 * @return string
	 */
	private static function title_of( array $fila ): string {
		$titulo = trim( (string) $fila['title'] );
		return '' !== $titulo ? $titulo : '(sin título)';
	}
}
