<?php
/**
 * The «Participantes» panel of the event workshop.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

use Evt\PublicFront\Assets;
use Evt\PublicFront\Registrations;
use Evt\PublicFront\Shell;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\Participants;
use Evt\PublicFront\RegistrationFiles;

/**
 * Quién se ha inscrito: filtro y exportación a CSV.
 *
 * **De dónde salen las filas no lo decide esta pantalla.** Los participantes
 * son de este aplicativo y se gestionarán aquí, con formulario de inscripción
 * propio; mientras ese formulario no exista, las filas entran por el filtro
 * `evt_participants` (ADR-0027). Cuando nadie contesta, aquí no se finge una
 * lista vacía: se dice dónde se abre la inscripción, en vez de dejar una tabla
 * sin filas que parece un fallo.
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
		<div class="evt-panel-cabecera"><div>
			<h2 class="evt-panel-titulo">Participantes</h2>
			<p class="evt-sub">Se filtra por cualquier dato —un apellido, un centro, un taller— y se exporta a CSV lo que quede filtrado.</p>
		</div></div>

		<?php
		if ( array() !== (array) ( $m['edit_values'] ?? array() ) ) {
			$cajon = PanelParts::drawer(
				'Corregir inscripción',
				self::form( $m ),
				EventWorkspace::url( (int) $m['event_id'], EventWorkspace::PANEL_PEOPLE ),
				(array) $m['flash'],
				true,
				true
			);
			echo $cajon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		}
		?>
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
							<th scope="col"><span class="screen-reader-text">Acciones</span></th>
							<?php foreach ( (array) $m['people_cols'] as $rotulo ) : ?>
								<th scope="col"><?php echo esc_html( (string) $rotulo ); ?></th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $filas as $fila ) : ?>
							<tr>
								<?php // Las acciones, lo primero: la tabla es ancha y se desplaza. ?>
								<td data-rotulo="Acciones"><?php echo self::actions( $m, $fila ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></td>
								<?php foreach ( (array) $m['people_cols'] as $clave => $rotulo ) : ?>
									<td data-rotulo="<?php echo esc_attr( (string) $rotulo ); ?>">
										<?php if ( 'files' === $clave ) : ?>
											<?php echo self::downloads( $fila ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
										<?php else : ?>
											<?php echo esc_html( '' !== (string) ( $fila[ $clave ] ?? '' ) ? (string) $fila[ $clave ] : '—' ); ?>
										<?php endif; ?>
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
	 * Correct and delete, for the registrations that are ours.
	 *
	 * Una fila que llega de otro sitio por el filtro no trae identificador y
	 * se queda de solo lectura. Borrar no tiene papelera, así que se pide
	 * teclear el correo de la persona (ADR-0043): con SweetAlert2 en su
	 * diálogo, sin ella con `prompt()`, y sin guion en el campo que va en el
	 * propio formulario. Lo comprueba el servidor en los tres casos.
	 *
	 * @param array<string, mixed> $m    Model.
	 * @param array<string, mixed> $fila One row.
	 * @return string
	 */
	private static function actions( array $m, array $fila ): string {
		$id = (int) ( $fila[ Participants::KEY_REG ] ?? 0 );
		if ( $id <= 0 || ! isset( $m['event_id'] ) || ( true === ( $m['archived'] ?? false ) && true !== ( $m['can_unarchive'] ?? false ) ) ) {
			return '';
		}
		$nombre = '' !== (string) $fila['name'] ? (string) $fila['name'] : 'esta persona';

		ob_start();
		?>
		<span class="evt-acciones">
			<?php echo PanelParts::icon_link( EventWorkspace::url( (int) $m['event_id'], EventWorkspace::PANEL_PEOPLE, array( EventWorkspace::ARG_ROW => $id ) ), 'lapiz', 'Corregir la inscripción' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			<?php echo self::delete_form( $m, $id, $nombre, (string) $fila['email'], true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		</span>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The form that deletes one registration, asking for its email.
	 *
	 * @param array<string, mixed> $m      Model.
	 * @param int                  $id     Registration post ID.
	 * @param string               $nombre Name, for the question.
	 * @param string               $correo Email that has to be typed.
	 * @param bool                 $mini   Icon button, for the table.
	 * @return string
	 */
	private static function delete_form( array $m, int $id, string $nombre, string $correo, bool $mini ): string {
		$op       = EventWorkspace::OP_REG_DELETE;
		$pregunta = sprintf( '¿Borrar la inscripción de %s? Se borran sus datos y sus documentos, y no hay papelera ni vuelta atrás.', $nombre );

		ob_start();
		?>
		<form class="evt-accion evt-borrar-escrito" method="post" action=""
			data-evt-confirm="<?php echo esc_attr( $pregunta ); ?>"
			data-evt-confirm-ok="Borrar la inscripción"
			data-evt-confirm-escribe="<?php echo esc_attr( strtolower( $correo ) ); ?>">
			<?php wp_nonce_field( EventWorkspace::nonce_action( $op ), EventWorkspace::nonce_name( $op, $id ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_EVENT ); ?>" value="<?php echo esc_attr( (string) (int) $m['event_id'] ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_ROW ); ?>" value="<?php echo esc_attr( (string) $id ); ?>" />
			<label class="evt-escribe">
				<span class="<?php echo $mini ? 'screen-reader-text' : ''; ?>"><?php echo esc_html( 'Para borrar, escriba su correo: ' . $correo ); ?></span>
				<input type="email" name="<?php echo esc_attr( EventWorkspace::FIELD_CONFIRM_EMAIL ); ?>" autocomplete="off" placeholder="<?php echo esc_attr( $correo ); ?>" />
			</label>
			<?php if ( $mini ) : ?>
				<button type="submit" class="<?php echo esc_attr( Assets::button_class() . ' evt-mini evt-icono evt-btn-borrar' ); ?>" title="Borrar la inscripción" data-bs-toggle="tooltip">
					<?php echo wp_kses( Shell::icon( 'papelera' ), PanelParts::SVG ); ?>
					<span class="screen-reader-text">Borrar la inscripción</span>
				</button>
			<?php else : ?>
				<button type="submit" class="evt-btn btn btn-outline-danger evt-btn-borrar">Borrar la inscripción…</button>
			<?php endif; ?>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The form that corrects one registration, in the side panel.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function form( array $m ): string {
		$v         = (array) $m['edit_values'];
		$id        = (int) $v['id'];
		$op        = EventWorkspace::OP_REG_SAVE;
		$preguntas = Registrations::questions( (int) $m['event_id'] );
		$respuesta = (array) $v['answers'];
		$campos    = array(
			'tax_id'  => array( 'Documento de identidad', 'text' ),
			'name'    => array( 'Nombre', 'text' ),
			'surname' => array( 'Apellidos', 'text' ),
			'email'   => array( 'Correo electrónico', 'email' ),
			'phone'   => array( 'Teléfono', 'tel' ),
		);

		ob_start();
		?>
		<form class="evt-form" method="post" action="">
			<?php wp_nonce_field( EventWorkspace::nonce_action( $op ), EventWorkspace::nonce_name( $op, $id ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_EVENT ); ?>" value="<?php echo esc_attr( (string) (int) $m['event_id'] ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_ROW ); ?>" value="<?php echo esc_attr( (string) $id ); ?>" />

			<?php foreach ( $campos as $clave => $campo ) : ?>
				<div class="evt-form-campo">
					<label for="evt-rg-<?php echo esc_attr( $clave ); ?>"><?php echo esc_html( $campo[0] ); ?><?php echo 'phone' === $clave ? ' <span class="evt-opcional">(opcional)</span>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- literal. ?></label>
					<input type="<?php echo esc_attr( $campo[1] ); ?>" id="evt-rg-<?php echo esc_attr( $clave ); ?>" name="evt_rg_<?php echo esc_attr( $clave ); ?>"
						value="<?php echo esc_attr( (string) $v[ $clave ] ); ?>" <?php echo 'phone' === $clave ? '' : 'required'; ?> />
				</div>
			<?php endforeach; ?>

			<div class="evt-form-campo">
				<label for="evt-rg-centre">Código del centro</label>
				<input type="text" id="evt-rg-centre" name="evt_rg_centre" inputmode="numeric" pattern="\d{8}" <?php echo '' === (string) $v['centre_code'] && '' !== (string) $v['centre'] ? '' : 'required'; ?>
					value="<?php echo esc_attr( (string) $v['centre_code'] ); ?>" />
				<small><?php echo esc_html( '' !== (string) $v['centre'] ? 'Ahora: ' . (string) $v['centre'] . '.' : 'Los ocho dígitos del código oficial.' ); ?> El nombre sale del catálogo de centros.</small>
			</div>

			<?php foreach ( $preguntas as $pregunta ) : ?>
				<?php echo self::question( $pregunta, $respuesta[ $pregunta['id'] ] ?? null ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			<?php endforeach; ?>

			<?php if ( array() !== (array) $m['workshops'] ) : ?>
				<div class="evt-form-campo">
					<label for="evt-rg-workshop">Taller</label>
					<select id="evt-rg-workshop" name="evt_rg_workshop">
						<option value="0">Sin taller</option>
						<?php foreach ( (array) $m['workshops'] as $taller ) : ?>
							<option value="<?php echo esc_attr( (string) (int) $taller['id'] ); ?>" <?php selected( (int) $v['workshop'], (int) $taller['id'] ); ?>>
								<?php echo esc_html( (string) $taller['title'] . ( (int) $taller['seats'] > 0 ? sprintf( ' (%d de %d)', (int) $taller['taken'], (int) $taller['seats'] ) : '' ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<small>Con el mismo aforo que cuando lo elige la persona: un taller completo no admite a nadie más.</small>
				</div>
			<?php endif; ?>

			<p class="evt-sub">El consentimiento, su fecha y los documentos aportados no se corrigen: son lo que aceptó y entregó la persona.</p>

			<div class="evt-acciones">
				<button class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" type="submit">Guardar la inscripción</button>
				<a class="<?php echo esc_attr( Assets::button_class() ); ?>" href="<?php echo esc_url( EventWorkspace::url( (int) $m['event_id'], EventWorkspace::PANEL_PEOPLE ) ); ?>" data-evt-cerrar-cajon>Cancelar</a>
			</div>
		</form>

		<section class="evt-tarjeta evt-peligro">
			<h2>Borrar la inscripción</h2>
			<p>Se borran sus datos y sus documentos. No hay papelera ni vuelta atrás.</p>
			<?php echo self::delete_form( $m, $id, trim( (string) $v['name'] . ' ' . (string) $v['surname'] ), (string) $v['email'], false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * One question of the event, answered as it is stored.
	 *
	 * @param array<string, mixed> $pregunta  Normalised question.
	 * @param mixed                $respuesta Stored answer.
	 * @return string
	 */
	private static function question( array $pregunta, $respuesta ): string {
		if ( 'file' === $pregunta['type'] ) {
			return '';
		}
		$id     = 'evt-rg-q-' . sanitize_html_class( (string) $pregunta['id'] );
		$nombre = 'evt_rg_answers[' . (string) $pregunta['id'] . ']';

		ob_start();
		?>
		<div class="evt-form-campo">
			<?php if ( 'check' === $pregunta['type'] ) : ?>
				<label><input type="checkbox" name="<?php echo esc_attr( $nombre ); ?>" value="1" <?php checked( ! empty( $respuesta ) ); ?> /> <?php echo esc_html( (string) $pregunta['label'] ); ?></label>
			<?php elseif ( 'one' === $pregunta['type'] ) : ?>
				<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( (string) $pregunta['label'] ); ?></label>
				<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $nombre ); ?>">
					<option value="">—</option>
					<?php foreach ( (array) $pregunta['options'] as $opcion ) : ?>
						<option value="<?php echo esc_attr( (string) $opcion ); ?>" <?php selected( (string) $respuesta, (string) $opcion ); ?>><?php echo esc_html( (string) $opcion ); ?></option>
					<?php endforeach; ?>
				</select>
			<?php elseif ( 'many' === $pregunta['type'] ) : ?>
				<fieldset>
					<legend><?php echo esc_html( (string) $pregunta['label'] ); ?></legend>
					<?php foreach ( (array) $pregunta['options'] as $opcion ) : ?>
						<label><input type="checkbox" name="<?php echo esc_attr( $nombre ); ?>[]" value="<?php echo esc_attr( (string) $opcion ); ?>" <?php checked( in_array( (string) $opcion, (array) $respuesta, true ) ); ?> /> <?php echo esc_html( (string) $opcion ); ?></label>
					<?php endforeach; ?>
				</fieldset>
			<?php else : ?>
				<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( (string) $pregunta['label'] ); ?></label>
				<input type="text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $nombre ); ?>" value="<?php echo esc_attr( is_scalar( $respuesta ) ? (string) $respuesta : '' ); ?>" />
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The download links of the private documents of one row.
	 *
	 * El enlace se compone **aquí**, al pintar: es presentación, y por eso no
	 * viaja dentro de la fila ni acaba en el CSV (ADR-0036). Apunta al
	 * manejador del aplicativo, que vuelve a comprobar quién pregunta: este
	 * botón no autoriza nada.
	 *
	 * @param array<string, mixed> $fila One row.
	 * @return string
	 */
	private static function downloads( array $fila ): string {
		$documentos = isset( $fila[ Participants::KEY_FILES ] ) && is_array( $fila[ Participants::KEY_FILES ] )
			? $fila[ Participants::KEY_FILES ]
			: array();
		if ( array() === $documentos ) {
			return '—';
		}

		$enlaces = array();
		foreach ( $documentos as $documento ) {
			$nombre    = (string) ( $documento['name'] ?? '' );
			$enlaces[] = sprintf(
				'<a class="evt-descarga" href="%1$s" download>%2$s</a>',
				esc_url( RegistrationFiles::url( (int) ( $documento['reg'] ?? 0 ), (string) ( $documento['id'] ?? '' ) ) ),
				esc_html( '' !== $nombre ? $nombre : 'Descargar' )
			);
		}
		return implode( ' ', $enlaces );
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
				Si la inscripción de este evento está cerrada, nadie puede apuntarse
				todavía: se abre en la pestaña
				<a href="<?php echo esc_url( EventWorkspace::url( (int) $m['event_id'], EventWorkspace::PANEL_SIGNUP ) ); ?>">Inscripción</a>,
				junto con las preguntas propias del evento. No es un fallo del evento.
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
