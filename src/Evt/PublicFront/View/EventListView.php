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
		<?php if ( ! empty( $m['can_create'] ) ) : ?>
			<p class="evt-acciones">
				<a class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" href="<?php echo esc_url( (string) $m['create_url'] ); ?>">
					<?php echo Shell::icon_plus(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG literal. ?>
					Crear evento
				</a>
			</p>
		<?php endif; ?>
		<?php self::counts( $m ); ?>
		<?php self::trash_bar( $m ); ?>
		<?php self::filters( $m ); ?>
		<?php self::table( $m ); ?>
		<?php self::pagination( $m ); ?>
		<?php
		return Shell::render( 'Eventos', (string) $m['subtitle'], (string) ob_get_clean() );
	}

	/**
	 * The four figures that say how the área is doing.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return void
	 */
	private static function counts( array $m ): void {
		$fichas = array(
			'all'                         => 'Eventos',
			EventMetaKeys::STATE_UPCOMING => 'Próximos',
			EventMetaKeys::STATE_OPEN     => 'Abiertos',
			EventList::FILTER_DRAFT       => 'En borrador',
		);
		?>
		<ul class="evt-cifras">
			<?php foreach ( $fichas as $clave => $rotulo ) : ?>
				<li class="evt-cifra">
					<strong><?php echo esc_html( (string) ( $m['counts'][ $clave ] ?? 0 ) ); ?></strong>
					<span><?php echo esc_html( $rotulo ); ?></span>
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
	 * The filter bar: text, área, tipología, curso and state.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return void
	 */
	private static function filters( array $m ): void {
		$s      = $m['selection'];
		$listas = array(
			'area'   => array(
				'var'     => EventList::VAR_AREA,
				'label'   => 'Ámbito',
				'any'     => 'Todos los ámbitos',
				'choices' => $m['options']['area'],
			),
			'type'   => array(
				'var'     => EventList::VAR_TYPE,
				'label'   => 'Tipología',
				'any'     => 'Todas las tipologías',
				'choices' => $m['options']['type'],
			),
			'course' => array(
				'var'     => EventList::VAR_COURSE,
				'label'   => 'Curso escolar',
				'any'     => 'Todos los cursos',
				'choices' => $m['options']['course'],
			),
		);
		if ( empty( $m['area_filter'] ) ) {
			unset( $listas['area'] );
		}
		?>
		<form class="evt-form evt-tarjeta" method="get" action="">
			<?php if ( (int) $m['page_id'] > 0 ) : ?>
				<input type="hidden" name="page_id" value="<?php echo esc_attr( (string) $m['page_id'] ); ?>" />
			<?php endif; ?>
			<div class="evt-form-fila">
				<div>
					<label for="evt-buscar">Buscar</label>
					<input type="search" id="evt-buscar" name="<?php echo esc_attr( EventList::VAR_SEARCH ); ?>"
						value="<?php echo esc_attr( (string) $s['search'] ); ?>"
						placeholder="Título del evento…" autocomplete="off" />
				</div>
				<?php foreach ( $listas as $eje => $lista ) : ?>
					<div>
						<label for="evt-<?php echo esc_attr( $eje ); ?>"><?php echo esc_html( $lista['label'] ); ?></label>
						<select id="evt-<?php echo esc_attr( $eje ); ?>" name="<?php echo esc_attr( $lista['var'] ); ?>">
							<option value="0"><?php echo esc_html( $lista['any'] ); ?></option>
							<?php foreach ( $lista['choices'] as $term_id => $nombre ) : ?>
								<option value="<?php echo esc_attr( (string) $term_id ); ?>" <?php selected( (int) $s[ $eje ], (int) $term_id ); ?>>
									<?php echo esc_html( $nombre ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php endforeach; ?>
				<div>
					<label for="evt-estado">Estado</label>
					<select id="evt-estado" name="<?php echo esc_attr( EventList::VAR_STATE ); ?>">
						<?php foreach ( EventList::state_filters() as $clave => $rotulo ) : ?>
							<option value="<?php echo esc_attr( $clave ); ?>" <?php selected( (string) $s['state'], $clave ); ?>>
								<?php echo esc_html( $rotulo ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<p class="evt-acciones">
				<button class="<?php echo esc_attr( Assets::button_class() ); ?>" type="submit">Filtrar</button>
				<?php if ( '' !== (string) $m['reset_url'] ) : ?>
					<a href="<?php echo esc_url( (string) $m['reset_url'] ); ?>">Quitar los filtros</a>
				<?php endif; ?>
			</p>
		</form>
		<?php
	}

	/**
	 * The table itself, or why it is empty.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return void
	 */
	private static function table( array $m ): void {
		$estados = EventMetaKeys::states();
		$publica = EventList::status_labels();
		$dentro  = EventList::FILTER_TRASH === (string) $m['selection']['state'];
		?>
		<div class="evt-tabla-caja">
			<?php if ( array() === $m['rows'] ) : ?>
				<p class="evt-vacio"><?php echo esc_html( (string) $m['empty_text'] ); ?></p>
			<?php else : ?>
				<table class="evt-tabla">
					<thead>
						<tr>
							<th scope="col">Evento</th>
							<th scope="col">Ámbito</th>
							<th scope="col">Tipología</th>
							<th scope="col">Curso</th>
							<th scope="col">Fechas</th>
							<th scope="col">Estado</th>
							<th scope="col">Publicación</th>
							<th scope="col" class="evt-num">Secciones</th>
							<th scope="col">Acciones</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $m['rows'] as $row ) : ?>
							<tr>
								<td data-rotulo="Evento">
									<?php if ( '' !== (string) $row['url'] ) : ?>
										<a href="<?php echo esc_url( (string) $row['url'] ); ?>"><?php echo esc_html( (string) $row['title'] ); ?></a>
									<?php else : ?>
										<?php echo esc_html( (string) $row['title'] ); ?>
									<?php endif; ?>
								</td>
								<td data-rotulo="Ámbito"><?php echo esc_html( self::names( $row['areas'] ) ); ?></td>
								<td data-rotulo="Tipología"><?php echo esc_html( self::names( $row['types'] ) ); ?></td>
								<td data-rotulo="Curso"><?php echo esc_html( self::names( $row['courses'] ) ); ?></td>
								<td data-rotulo="Fechas"><?php echo esc_html( self::dates( (string) $row['start'], (string) $row['end'] ) ); ?></td>
								<td data-rotulo="Estado">
									<span class="<?php echo esc_attr( Assets::state_class( (string) $row['state'] ) ); ?>">
										<?php echo esc_html( $estados[ $row['state'] ] ?? '—' ); ?>
									</span>
									<?php if ( ! empty( $row['archived'] ) ) : ?>
										<span class="<?php echo esc_attr( Assets::state_class( EventList::FILTER_ARCHIVED ) ); ?>"
											title="Cerrado a edición: se consulta y se exporta, pero no se cambia.">Histórico</span>
									<?php endif; ?>
								</td>
								<td data-rotulo="Publicación">
									<?php if ( $dentro || true !== $row['can_pub'] ) : ?>
										<span class="<?php echo esc_attr( Assets::state_class( (string) $row['status'] ) ); ?>">
											<?php echo esc_html( $publica[ $row['status'] ] ?? (string) $row['status'] ); ?>
										</span>
									<?php else : ?>
										<?php echo self::publish_switch( (array) $row ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
									<?php endif; ?>
								</td>
								<td class="evt-num" data-rotulo="Secciones"><?php echo esc_html( (string) $row['sections'] ); ?></td>
								<td data-rotulo="Acciones">
									<?php if ( $dentro ) : ?>
										<?php echo self::restore_form( (int) $row['id'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
									<?php else : ?>
										<span class="evt-acciones">
											<?php
											echo PanelParts::icon_link( (string) $row['url'], 'lapiz', 'Editar este evento' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
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
			<?php endif; ?>
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
		<nav aria-label="Páginas de eventos">
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
