<?php
/**
 * «Eventos», painted from what EventList::model() decided.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

use Evt\Domain\DateRange;
use Evt\Meta\EventMetaKeys;
use Evt\PublicFront\Assets;
use Evt\PublicFront\EventList;
use Evt\PublicFront\Shell;

/**
 * Solo pinta: no lee la petición, no consulta y no decide.
 */
final class EventListView {

	/**
	 * The screen, from its model.
	 *
	 * @param array<string, mixed> $m What EventList::model() returned.
	 * @return string
	 */
	public static function html( array $m ): string {
		if ( empty( $m['can_use'] ) ) {
			return Shell::render( 'Eventos', '', Shell::notice( 'aviso', (string) $m['reason'] ) );
		}

		ob_start();
		?>
		<?php echo Shell::notice( (string) $m['notice']['type'], (string) $m['notice']['text'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shell::notice escapa su texto. ?>
		<?php self::toolbar( $m ); ?>
		<?php self::trash_bar( $m ); ?>
		<?php if ( array() === $m['rows'] ) : ?>
			<div class="evt-vacio evt-tarjeta">
				<p><?php echo esc_html( (string) $m['empty_text'] ); ?></p>
				<?php if ( '' !== (string) $m['reset_url'] ) : ?>
					<a class="<?php echo esc_attr( Assets::button_class() ); ?>" href="<?php echo esc_url( (string) $m['reset_url'] ); ?>">Quitar el filtro</a>
				<?php endif; ?>
			</div>
		<?php elseif ( EventList::VIEW_LIST === (string) $m['selection']['view'] ) : ?>
			<?php self::table( $m ); ?>
		<?php else : ?>
			<?php self::grid( $m ); ?>
		<?php endif; ?>
		<p class="evt-vacio evt-tarjeta" data-evt-filtro-vacio hidden>Ningún evento de esta página se llama así. Pulse Intro para buscar en todos.</p>
		<?php self::pagination( $m ); ?>
		<?php
		return Shell::render( 'Eventos', (string) $m['subtitle'], (string) ob_get_clean() );
	}

	/**
	 * The one line of tools: filter by name, by área, the layout and «Crear».
	 *
	 * Pocos eventos por área: con el nombre y el ámbito basta. El nombre filtra
	 * mientras se escribe (JavaScript) y, al pulsar Intro, busca en todas las
	 * páginas (servidor); los dos caminos llegan a lo mismo.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return void
	 */
	private static function toolbar( array $m ): void {
		$s     = $m['selection'];
		$vista = (string) $s['view'];
		?>
		<div class="evt-herramientas">
			<form class="evt-herramientas__filtros" method="get" action="" role="search">
				<?php if ( (int) $m['page_id'] > 0 ) : ?>
					<input type="hidden" name="page_id" value="<?php echo esc_attr( (string) $m['page_id'] ); ?>" />
				<?php endif; ?>
				<?php if ( EventList::VIEW_LIST === $vista ) : ?>
					<input type="hidden" name="<?php echo esc_attr( EventList::VAR_VIEW ); ?>" value="<?php echo esc_attr( EventList::VIEW_LIST ); ?>" />
				<?php endif; ?>
				<?php if ( EventList::FILTER_TRASH === (string) $s['state'] ) : ?>
					<input type="hidden" name="<?php echo esc_attr( EventList::VAR_STATE ); ?>" value="<?php echo esc_attr( EventList::FILTER_TRASH ); ?>" />
				<?php endif; ?>
				<label class="screen-reader-text" for="evt-buscar">Filtrar por nombre</label>
				<input class="form-control evt-herramientas__buscar" type="search" id="evt-buscar" name="<?php echo esc_attr( EventList::VAR_SEARCH ); ?>"
					value="<?php echo esc_attr( (string) $s['search'] ); ?>"
					placeholder="Filtrar por nombre" autocomplete="off" data-evt-filtro />
				<?php if ( ! empty( $m['area_filter'] ) ) : ?>
					<label class="screen-reader-text" for="evt-area">Ámbito</label>
					<select class="form-select evt-herramientas__ambito" id="evt-area" name="<?php echo esc_attr( EventList::VAR_AREA ); ?>" data-evt-autoenvio>
						<option value="0">Todos mis ámbitos</option>
						<?php foreach ( (array) $m['options']['area'] as $term_id => $nombre ) : ?>
							<option value="<?php echo esc_attr( (string) $term_id ); ?>" <?php selected( (int) $s['area'], (int) $term_id ); ?>><?php echo esc_html( $nombre ); ?></option>
						<?php endforeach; ?>
					</select>
				<?php endif; ?>
				<button class="<?php echo esc_attr( Assets::button_class() ); ?> evt-herramientas__aplicar" type="submit">Buscar</button>
			</form>
			<div class="btn-group evt-segmentos" role="group" aria-label="Cómo ver los eventos">
				<?php
				foreach ( array(
					EventList::VIEW_GRID => 'Cuadrícula',
					EventList::VIEW_LIST => 'Lista',
				) as $clave => $rotulo ) :
					$activa = $clave === $vista;
					?>
					<a class="btn btn-outline-primary evt-segmento<?php echo $activa ? ' active' : ''; ?>"
						href="
						<?php
						echo esc_url(
							EventList::url(
								$s,
								array(
									'view' => $clave,
									'page' => (int) $s['page'],
								)
							)
						);
						?>
								"
						<?php echo $activa ? 'aria-current="true"' : ''; ?>><?php echo esc_html( $rotulo ); ?></a>
				<?php endforeach; ?>
			</div>
			<?php if ( ! empty( $m['can_create'] ) ) : ?>
				<a class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" href="<?php echo esc_url( (string) $m['create_url'] ); ?>">
					<?php echo wp_kses( Shell::icon_plus(), PanelParts::SVG ); ?>
					Crear evento
				</a>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * The badges of one event: its state by the dates, and borrador / histórico.
	 *
	 * @param array<string, mixed> $row       Row.
	 * @param bool                 $con_marca Whether to add borrador / histórico / papelera.
	 * @return string
	 */
	private static function badges( array $row, bool $con_marca = true ): string {
		$estados = EventMetaKeys::states();
		$html    = sprintf(
			'<span class="%1$s">%2$s</span>',
			esc_attr( Assets::state_class( (string) $row['state'] ) ),
			esc_html( $estados[ $row['state'] ] ?? '—' )
		);
		// En la cuadrícula lo dice la banda de la esquina: no se repite.
		if ( ! $con_marca ) {
			return $html;
		}
		if ( ! empty( $row['draft'] ) ) {
			$html .= ' <span class="' . esc_attr( Assets::state_class( EventList::FILTER_DRAFT ) ) . '">Borrador</span>';
		}
		if ( ! empty( $row['archived'] ) ) {
			$html .= ' <span class="' . esc_attr( Assets::state_class( EventList::FILTER_ARCHIVED ) ) . '" title="Cerrado a edición: se consulta y se exporta, pero no se cambia.">Histórico</span>';
		}
		if ( EventList::FILTER_TRASH === (string) $row['status'] ) {
			$html .= ' <span class="' . esc_attr( Assets::state_class( EventList::FILTER_TRASH ) ) . '">En la papelera</span>';
		}
		return $html;
	}

	/**
	 * What sets an event apart from a live one: papelera, borrador or histórico.
	 *
	 * Uno solo, el que más pesa: lo de la papelera no se edita, y un borrador
	 * todavía no se ve fuera.
	 *
	 * @param array<string, mixed> $row Row.
	 * @return array{0:string, 1:string} CSS modifier and label; empty when it is live.
	 */
	private static function mark( array $row ): array {
		if ( EventList::FILTER_TRASH === (string) $row['status'] ) {
			return array( 'papelera', 'En la papelera' );
		}
		if ( ! empty( $row['draft'] ) ) {
			return array( 'borrador', 'Borrador' );
		}
		if ( ! empty( $row['archived'] ) ) {
			return array( 'historico', 'Histórico' );
		}
		return array( '', '' );
	}

	/**
	 * The poster, or a block with the colour of the event and its name.
	 *
	 * @param array<string, mixed> $row   Row.
	 * @param string               $clase CSS class.
	 * @return string
	 */
	private static function poster( array $row, string $clase ): string {
		if ( '' !== (string) $row['poster'] ) {
			return sprintf( '<img class="%1$s" src="%2$s" alt="" loading="lazy">', esc_attr( $clase ), esc_url( (string) $row['poster'] ) );
		}
		return sprintf(
			'<span class="%1$s %1$s--vacio" style="--evt-cartel: %2$s; --evt-cartel-tinta: %4$s" aria-hidden="true"><span>%3$s</span></span>',
			esc_attr( $clase ),
			esc_attr( (string) $row['color'] ),
			esc_html( (string) $row['title'] ),
			esc_attr( (string) ( $row['ink'] ?? '#fff' ) )
		);
	}

	/**
	 * Cards with the poster.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return void
	 */
	private static function grid( array $m ): void {
		$dentro = EventList::FILTER_TRASH === (string) $m['selection']['state'];
		?>
		<ul class="evt-rejilla">
			<?php foreach ( $m['rows'] as $row ) : ?>
				<?php list( $marca, $rotulo ) = self::mark( (array) $row ); ?>
				<li class="evt-ficha<?php echo '' !== $marca ? ' evt-ficha--' . esc_attr( $marca ) : ''; ?>" data-evt-buscar="<?php echo esc_attr( (string) $row['search'] ); ?>">
					<?php if ( '' !== $marca ) : ?>
						<span class="evt-ficha__banda"><?php echo esc_html( $rotulo ); ?></span>
					<?php endif; ?>
					<?php echo self::poster( (array) $row, 'evt-ficha__cartel' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
					<div class="evt-ficha__cuerpo">
						<div class="evt-ficha__chapas"><?php echo self::badges( (array) $row, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></div>
						<h2 class="evt-ficha__titulo">
							<?php if ( '' !== (string) $row['url'] ) : ?>
								<a class="evt-ficha__enlace" href="<?php echo esc_url( (string) $row['url'] ); ?>"><?php echo esc_html( (string) $row['title'] ); ?></a>
							<?php else : ?>
								<?php echo esc_html( (string) $row['title'] ); ?>
							<?php endif; ?>
						</h2>
						<p class="evt-ficha__dato"><?php echo esc_html( self::dates( (string) $row['start'], (string) $row['end'] ) ); ?></p>
						<p class="evt-ficha__dato"><?php echo esc_html( self::names( $row['areas'] ) ); ?></p>
						<?php if ( $dentro ) : ?>
							<?php echo self::restore_form( (int) $row['id'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
						<?php elseif ( '' !== (string) $row['view_url'] ) : ?>
							<a class="evt-ficha__ver" href="<?php echo esc_url( (string) $row['view_url'] ); ?>"><?php echo esc_html( 'publish' === (string) $row['status'] ? 'Ver la página' : 'Previsualizar' ); ?></a>
						<?php endif; ?>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
	}

	/**
	 * «Papelera (N)»: el filtro de lo enviado a ella, y la vuelta.
	 *
	 * Cuando no hay nada en la papelera no se pinta: un enlace a una pantalla
	 * vacía no ayuda a nadie. El borrado definitivo no está aquí a propósito
	 * —lo hace quien pueda desde el escritorio de WordPress—, y se dice.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return void
	 */
	private static function trash_bar( array $m ): void {
		$cuantos = (int) ( $m['counts'][ EventList::FILTER_TRASH ] ?? 0 );
		$dentro  = EventList::FILTER_TRASH === (string) $m['selection']['state'];
		if ( 0 === $cuantos && ! $dentro ) {
			return;
		}
		?>
		<p class="evt-acciones">
			<?php if ( $dentro ) : ?>
				<a href="<?php echo esc_url( EventList::url( $m['selection'], array( 'state' => 'all' ) ) ); ?>">Volver al listado</a>
				<span>Restaurar devuelve el evento a borrador. Para borrar algo de verdad y para siempre hay que ir al escritorio de WordPress: desde aquí no se destruye nada.</span>
			<?php else : ?>
				<a href="<?php echo esc_url( EventList::url( $m['selection'], array( 'state' => EventList::FILTER_TRASH ) ) ); ?>">
					<?php echo esc_html( sprintf( 'Papelera (%d)', $cuantos ) ); ?>
				</a>
			<?php endif; ?>
		</p>
		<?php
	}

	/**
	 * The table: the same events, one per line, with the publish switch.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return void
	 */
	private static function table( array $m ): void {
		$publica = EventList::status_labels();
		$dentro  = EventList::FILTER_TRASH === (string) $m['selection']['state'];
		?>
		<div class="evt-tabla-caja">
			<table class="evt-tabla">
				<thead>
					<tr>
						<th scope="col"><span class="screen-reader-text">Cartel</span></th>
						<th scope="col">Evento</th>
						<th scope="col">Fechas</th>
						<th scope="col">Ámbito</th>
						<th scope="col">Publicación</th>
						<th scope="col" class="evt-num">Páginas</th>
						<th scope="col"><span class="screen-reader-text">Acciones</span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $m['rows'] as $row ) : ?>
						<?php list( $marca ) = self::mark( (array) $row ); ?>
						<tr<?php echo '' !== $marca ? ' class="evt-fila--' . esc_attr( $marca ) . '"' : ''; ?> data-evt-buscar="<?php echo esc_attr( (string) $row['search'] ); ?>">
							<td class="evt-tabla__cartel"><?php echo self::poster( (array) $row, 'evt-mini-cartel' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></td>
							<td data-rotulo="Evento">
								<?php if ( '' !== (string) $row['url'] ) : ?>
									<a class="evt-tabla__titulo" href="<?php echo esc_url( (string) $row['url'] ); ?>"><?php echo esc_html( (string) $row['title'] ); ?></a>
								<?php else : ?>
									<span class="evt-tabla__titulo"><?php echo esc_html( (string) $row['title'] ); ?></span>
								<?php endif; ?>
								<div class="evt-ficha__chapas"><?php echo self::badges( (array) $row ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></div>
							</td>
							<td data-rotulo="Fechas"><?php echo esc_html( self::dates( (string) $row['start'], (string) $row['end'] ) ); ?></td>
							<td data-rotulo="Ámbito"><?php echo esc_html( self::names( $row['areas'] ) ); ?></td>
							<td data-rotulo="Publicación">
								<?php if ( $dentro || true !== $row['can_pub'] ) : ?>
									<span class="<?php echo esc_attr( Assets::state_class( (string) $row['status'] ) ); ?>">
										<?php echo esc_html( $publica[ $row['status'] ] ?? (string) $row['status'] ); ?>
									</span>
								<?php else : ?>
									<?php echo self::publish_switch( (array) $row ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
								<?php endif; ?>
							</td>
							<td class="evt-num" data-rotulo="Páginas"><?php echo esc_html( (string) $row['sections'] ); ?></td>
							<td data-rotulo="Acciones">
								<?php if ( $dentro ) : ?>
									<?php echo self::restore_form( (int) $row['id'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
								<?php else : ?>
									<span class="evt-acciones">
										<?php
										echo PanelParts::icon_link( (string) $row['url'], 'lapiz', 'Abrir el taller de este evento' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
										$mirar = 'publish' === (string) $row['status']
											? 'Ver la página del evento'
											: 'Previsualizar el evento, que está en borrador';
										echo PanelParts::icon_link( (string) $row['view_url'], 'ojo', $mirar ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
										?>
									</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * The publish state as a switch.
	 *
	 * Un interruptor y no un enlace: lo que se quiere saber de un vistazo es si
	 * el evento se ve fuera, y lo que se quiere hacer es cambiarlo. Con
	 * JavaScript se envía solo al soltarlo; sin JavaScript queda el botón de al
	 * lado, que hace exactamente lo mismo.
	 *
	 * Publicar el evento **no publica sus páginas**: cada una tiene su estado y
	 * se publica desde el taller.
	 *
	 * @param array<string, mixed> $row One row of the model.
	 * @return string
	 */
	private static function publish_switch( array $row ): string {
		$id        = (int) $row['id'];
		$publicado = 'publish' === (string) $row['status'];
		$op        = $publicado ? 'unpublish' : 'publish';
		$rotulo    = $publicado ? 'Despublicar este evento' : 'Publicar este evento';

		ob_start();
		?>
		<form class="evt-accion evt-switch" method="post" action="">
			<?php wp_nonce_field( EventList::nonce_action( $op ), EventList::nonce_name( $id ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( EventList::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventList::FIELD_EVENT ); ?>" value="<?php echo esc_attr( (string) $id ); ?>" />
			<label class="evt-switch-caja" title="<?php echo esc_attr( $rotulo ); ?>" data-bs-toggle="tooltip">
				<input type="checkbox" class="evt-switch-input" data-evt-switch
					<?php checked( $publicado, true ); ?> />
				<span class="evt-switch-pista" aria-hidden="true"></span>
				<span class="evt-switch-txt"><?php echo esc_html( $publicado ? 'Publicado' : 'Borrador' ); ?></span>
				<span class="screen-reader-text"><?php echo esc_html( $rotulo ); ?></span>
			</label>
			<button type="submit" class="<?php echo esc_attr( Assets::button_class() . ' evt-mini evt-switch-boton' ); ?>"><?php echo esc_html( $publicado ? 'Despublicar' : 'Publicar' ); ?></button>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Server-side pagination, with the filters kept.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return void
	 */
	private static function pagination( array $m ): void {
		if ( (int) $m['pages'] < 2 ) {
			return;
		}
		$s      = $m['selection'];
		$pagina = (int) $m['page'];
		?>
		<nav class="evt-paginas" aria-label="Páginas de eventos">
			<p class="evt-acciones">
				<?php if ( $pagina > 1 ) : ?>
					<a class="<?php echo esc_attr( Assets::button_class() ); ?>"
						href="<?php echo esc_url( EventList::url( $s, array( 'page' => $pagina - 1 ) ) ); ?>">Anterior</a>
				<?php endif; ?>
				<span>
					<?php
					echo esc_html(
						sprintf(
							'Página %1$d de %2$d · %3$d eventos',
							$pagina,
							(int) $m['pages'],
							(int) $m['total']
						)
					);
					?>
				</span>
				<?php if ( $pagina < (int) $m['pages'] ) : ?>
					<a class="<?php echo esc_attr( Assets::button_class() ); ?>"
						href="<?php echo esc_url( EventList::url( $s, array( 'page' => $pagina + 1 ) ) ); ?>">Siguiente</a>
				<?php endif; ?>
			</p>
		</nav>
		<?php
	}

	/**
	 * «Restaurar»: su propio formulario POST, con su propio nonce.
	 *
	 * Por POST y no por enlace: un `GET` que resucita un evento se dispara
	 * desde cualquier sitio que pinte la dirección, incluido el prefetch del
	 * navegador.
	 *
	 * @param int $event_id Event post ID.
	 * @return string
	 */
	private static function restore_form( int $event_id ): string {
		ob_start();
		?>
		<form class="evt-accion" method="post" action="">
			<?php wp_nonce_field( EventList::NONCE_ACTION, EventList::nonce_name( $event_id ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( EventList::FIELD_DO ); ?>" value="restore" />
			<input type="hidden" name="<?php echo esc_attr( EventList::FIELD_EVENT ); ?>" value="<?php echo esc_attr( (string) $event_id ); ?>" />
			<button type="submit" class="<?php echo esc_attr( Assets::button_class() ); ?>">Restaurar</button>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Term names of one cell.
	 *
	 * @param array<int, string> $terms term_id => nombre.
	 * @return string
	 */
	private static function names( array $terms ): string {
		return array() === $terms ? '—' : implode( ' · ', $terms );
	}

	/**
	 * The dates of an event, written the way they are announced.
	 *
	 * @param string $start First day, Y-m-d.
	 * @param string $end   Last day, Y-m-d; empty when there is none yet.
	 * @return string
	 */
	private static function dates( string $start, string $end ): string {
		$texto = DateRange::of( $start, $end );
		return '' !== $texto ? $texto : 'Sin fechas';
	}
}
