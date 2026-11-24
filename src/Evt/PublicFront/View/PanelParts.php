<?php
/**
 * Pieces the programme panels share: row actions and the ficha trash.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

use Evt\PublicFront\Assets;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\Shell;

/**
 * Lo que repiten «Ponentes», «Programa» y «Talleres».
 *
 * Está aparte para que el botón de borrar de los tres sea **el mismo botón**:
 * mismo nonce por fila, misma confirmación y misma vuelta a la pestaña desde
 * la que se pulsó. Tres copias se separan a la tercera corrección.
 *
 * Solo pinta: no lee la petición, no consulta, no decide.
 */
final class PanelParts {

	/**
	 * What `wp_kses()` lets through for an inline icon.
	 *
	 * Los iconos los escribe {@see Shell::icon()} y no vienen de fuera, pero
	 * pasan por `wp_kses()` igual: es una lista corta y deja la regla de «toda
	 * la salida escapada» sin excepciones que alguien tenga que recordar.
	 *
	 * @var array<string, array<string, bool>>
	 */
	public const SVG = array(
		'svg'  => array(
			'viewbox'     => true,
			'width'       => true,
			'height'      => true,
			'aria-hidden' => true,
			'focusable'   => true,
		),
		'path' => array(
			'fill' => true,
			'd'    => true,
		),
	);

	/**
	 * A side panel over the tab: the form to add or edit one row.
	 *
	 * Se abre por la dirección (`?alta=1`, `?ficha=N`) y se cierra con un
	 * enlace a la pestaña, así que no depende de JavaScript: sin guion se
	 * abre y se cierra igual, y con guion además se cierra con Escape.
	 * El error de un guardado fallido se enseña dentro, que es donde se mira.
	 *
	 * El de alta va siempre en la página, escondido: con guion, «Añadir» lo
	 * abre deslizándose sin recargar. El de edición llega abierto del
	 * servidor, porque trae los datos de la ficha.
	 *
	 * @param string               $titulo  Heading.
	 * @param string               $cuerpo  Built, escaped HTML of the form.
	 * @param string               $cerrar  URL that closes it.
	 * @param array<string, mixed> $flash   The last notice, shown when it is an error.
	 * @param bool                 $abierto Whether it is shown on arrival.
	 * @param bool                 $edita   Whether it edits a row: closing it then reloads the tab.
	 * @return string
	 */
	public static function drawer( string $titulo, string $cuerpo, string $cerrar, array $flash = array(), bool $abierto = true, bool $edita = false ): string {
		ob_start();
		?>
		<a class="evt-cajon-fondo" href="<?php echo esc_url( $cerrar ); ?>" tabindex="-1" aria-hidden="true" data-evt-cerrar-cajon <?php echo $abierto ? '' : 'hidden'; ?>></a>
		<section class="evt-cajon" role="dialog" aria-modal="true" aria-labelledby="evt-cajon-titulo" data-evt-cajon<?php echo $edita ? ' data-evt-cajon-edita' : ''; ?> <?php echo $abierto ? '' : 'hidden'; ?>>
			<header class="evt-cajon__cabecera">
				<h2 class="evt-cajon__titulo" id="evt-cajon-titulo"><?php echo esc_html( $titulo ); ?></h2>
				<a class="evt-cajon__cerrar" href="<?php echo esc_url( $cerrar ); ?>" aria-label="Cerrar sin guardar" data-evt-cerrar-cajon>&times;</a>
			</header>
			<div class="evt-cajon__cuerpo">
				<?php if ( 'error' === (string) ( $flash['tipo'] ?? '' ) && '' !== (string) ( $flash['texto'] ?? '' ) ) : ?>
					<?php echo Shell::notice( 'error', (string) $flash['texto'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shell::notice escapa. ?>
				<?php endif; ?>
				<?php echo $cuerpo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado por quien llama. ?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The save bar that stays at the bottom of long forms.
	 *
	 * Pegada abajo mientras se baja por el formulario: el botón de guardar no
	 * se queda al final de una página de tres pantallas. Con guion dice
	 * además si hay cambios sin guardar y deja descartarlos.
	 *
	 * @param string $rotulo What the button says.
	 * @return string
	 */
	public static function save_bar( string $rotulo ): string {
		ob_start();
		?>
		<div class="evt-guardar" data-evt-guardar>
			<span class="evt-guardar__estado" data-evt-guardar-estado aria-live="polite"></span>
			<span class="evt-guardar__botones">
				<button class="<?php echo esc_attr( Assets::button_class() ); ?>" type="reset" data-evt-guardar-descartar hidden>Descartar</button>
				<button class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" type="submit"><?php echo esc_html( $rotulo ); ?></button>
			</span>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * One row action: its own little POST form, with its own nonce.
	 *
	 * @param array<string, mixed> $m         Model.
	 * @param int                  $id        Speaker or activity post ID.
	 * @param string               $op        Operation.
	 * @param string               $icono     Icon name for {@see Shell::icon()}.
	 * @param string               $titulo    What the button does, in Spanish.
	 * @param string               $clases    Button classes.
	 * @param string               $panel     Tab to come back to; empty for none.
	 * @param bool                 $apagado   Whether the button is disabled.
	 * @param string               $confirmar Question to ask before submitting.
	 * @param string               $id_field  Field that carries the row ID.
	 * @return string
	 */
	public static function action( array $m, int $id, string $op, string $icono, string $titulo, string $clases, string $panel, bool $apagado = false, string $confirmar = '', string $id_field = EventWorkspace::FIELD_ROW ): string {
		$pregunta = '' !== $confirmar ? ' data-evt-confirm="' . esc_attr( $confirmar ) . '"' : '';

		ob_start();
		?>
		<form class="evt-accion" method="post" action=""<?php echo $pregunta; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado arriba. ?>>
			<?php wp_nonce_field( EventWorkspace::nonce_action( $op ), EventWorkspace::nonce_name( $op, $id ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_EVENT ); ?>" value="<?php echo esc_attr( (string) (int) $m['event_id'] ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( $id_field ); ?>" value="<?php echo esc_attr( (string) $id ); ?>" />
			<?php if ( '' !== $panel ) : ?>
				<input type="hidden" name="<?php echo esc_attr( EventWorkspace::ARG_PANEL ); ?>" value="<?php echo esc_attr( $panel ); ?>" />
			<?php endif; ?>
			<button type="submit" class="<?php echo esc_attr( $clases . ' evt-icono' ); ?>"
				title="<?php echo esc_attr( $titulo ); ?>" data-bs-toggle="tooltip"
				<?php disabled( $apagado, true ); ?>>
				<?php echo wp_kses( Shell::icon( $icono ), self::SVG ); ?>
				<span class="screen-reader-text"><?php echo esc_html( $titulo ); ?></span>
			</button>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * An icon link, with the same globe as the icon buttons.
	 *
	 * @param string $url    Where it goes.
	 * @param string $icono  Icon name for {@see Shell::icon()}.
	 * @param string $titulo What it does, in Spanish.
	 * @param string $clases Extra classes.
	 * @return string
	 */
	public static function icon_link( string $url, string $icono, string $titulo, string $clases = '' ): string {
		if ( '' === $url ) {
			return '';
		}
		$clases = trim( Assets::button_class() . ' evt-mini evt-icono ' . $clases );

		return '<a class="' . esc_attr( $clases ) . '" href="' . esc_url( $url ) . '"'
			. ' title="' . esc_attr( $titulo ) . '" data-bs-toggle="tooltip">'
			. wp_kses( Shell::icon( $icono ), self::SVG )
			. '<span class="screen-reader-text">' . esc_html( $titulo ) . '</span></a>';
	}

	/**
	 * The publish state of an event as a switch, with its form.
	 *
	 * Un interruptor y no un enlace: lo que se quiere saber de un vistazo es si
	 * el evento se ve fuera, y lo que se quiere hacer es cambiarlo. Con
	 * JavaScript se envía solo al tocarlo; sin JavaScript queda el botón de al
	 * lado, que hace exactamente lo mismo. Lo usan el listado, sus tarjetas y
	 * «Datos del evento»: cada uno pone su operación y su nonce.
	 *
	 * Publicar el evento **no publica sus páginas**: cada una tiene su estado y
	 * se publica desde el taller.
	 *
	 * Un evento histórico se queda como estaba: el interruptor sale apagado,
	 * sin formulario, y dice por qué.
	 *
	 * @param bool                  $publicado Whether it is published now.
	 * @param array<string, string> $campos    Hidden fields, name => value.
	 * @param string                $nonce     Nonce action.
	 * @param string                $campo     Nonce field name.
	 * @param bool                  $historico Whether the event is closed for good.
	 * @return string
	 */
	public static function publish_switch( bool $publicado, array $campos, string $nonce, string $campo, bool $historico = false ): string {
		$rotulo = $publicado ? 'Despublicar este evento' : 'Publicar este evento';
		if ( $historico ) {
			$motivo = 'Es histórico: se queda como está, publicado o en borrador.';
			return '<span class="evt-switch evt-switch--fijo"><label class="evt-switch-caja" title="' . esc_attr( $motivo ) . '" data-bs-toggle="tooltip">'
				. '<input type="checkbox" class="evt-switch-input" disabled' . ( $publicado ? ' checked' : '' ) . ' />'
				. '<span class="evt-switch-pista" aria-hidden="true"></span>'
				. '<span class="evt-switch-txt">' . esc_html( $publicado ? 'Publicado' : 'Borrador' ) . '</span>'
				. '<span class="screen-reader-text">' . esc_html( $motivo ) . '</span></label></span>';
		}

		ob_start();
		?>
		<form class="evt-accion evt-switch" method="post" action="">
			<?php wp_nonce_field( $nonce, $campo, false ); ?>
			<?php foreach ( $campos as $nombre => $valor ) : ?>
				<input type="hidden" name="<?php echo esc_attr( $nombre ); ?>" value="<?php echo esc_attr( $valor ); ?>" />
			<?php endforeach; ?>
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
	 * «Papelera (N)» with what was sent to it, when there is something.
	 *
	 * Ponentes y actividades comparten papelera porque lo que se busca ahí es
	 * «lo que borré sin querer», no de qué tipo era.
	 *
	 * @param array<string, mixed> $m     Model.
	 * @param string               $panel Tab to come back to.
	 * @return string
	 */
	public static function trash_link( array $m, string $panel ): string {
		$filas = (array) $m['row_trash'];
		if ( array() === $filas ) {
			return '';
		}
		$mini = Assets::button_class() . ' evt-mini';

		ob_start();
		?>
		<details class="evt-tarjeta">
			<summary><?php echo esc_html( sprintf( 'Papelera (%d)', count( $filas ) ) ); ?></summary>
			<p class="evt-sub">Nada se ha perdido. Al restaurar una ficha vuelve donde estaba, con sus datos.</p>
			<div class="evt-tabla-caja">
				<table class="evt-tabla">
					<thead>
						<tr>
							<th scope="col">Qué era</th>
							<th scope="col">Título</th>
							<th scope="col">Acciones</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $filas as $fila ) : ?>
							<tr>
								<td data-rotulo="Qué era"><?php echo esc_html( (string) $fila['kind'] ); ?></td>
								<td data-rotulo="Título"><?php echo esc_html( '' !== trim( (string) $fila['title'] ) ? (string) $fila['title'] : '(sin título)' ); ?></td>
								<td data-rotulo="Acciones">
									<?php
									$boton = self::action( $m, (int) $fila['id'], 'row_restore', 'restaurar', 'Restaurar esta ficha', $mini, $panel );
									echo $boton; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</details>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * A day written the way it is read out loud.
	 *
	 * @param string $fecha Date in Y-m-d, or empty.
	 * @return string
	 */
	public static function day( string $fecha ): string {
		if ( '' === $fecha ) {
			return 'Sin día asignado';
		}
		$marca = strtotime( $fecha . ' 12:00:00' );
		if ( false === $marca ) {
			return $fecha;
		}
		return (string) wp_date( 'l j \d\e F \d\e Y', $marca );
	}

	/**
	 * The time slot of an activity: «09:30 – 11:00», or just the start.
	 *
	 * @param string $start Start time.
	 * @param string $end   End time.
	 * @return string
	 */
	public static function slot( string $start, string $end ): string {
		if ( '' === $start ) {
			return '—';
		}
		return '' === $end ? $start : $start . ' – ' . $end;
	}

	/**
	 * The words of a media field that holds an image.
	 *
	 * Los textos de imagen y de PDF van en dos listas fijas y no en un
	 * condicional por cadena: así la pieza que pinta no tiene que decidir nada.
	 *
	 * @var array<string, string>
	 */
	private const MEDIA_IMAGE = array(
		'class'   => '',
		'what'    => 'la imagen',
		'button'  => 'Usar esta imagen',
		'choose'  => 'Elija una imagen',
		'drag'    => 'arrástrela',
		'formats' => 'JPG, PNG, WEBP o GIF',
		'empty'   => 'Todavía no hay ninguna imagen puesta.',
		'clear'   => 'Quitar esta imagen al guardar',
		'upload'  => 'Subir una imagen desde su equipo',
		'accept'  => 'image/jpeg,image/png,image/webp,image/gif',
	);

	/**
	 * The words of a media field that holds a PDF.
	 *
	 * @var array<string, string>
	 */
	private const MEDIA_PDF = array(
		'class'   => ' evt-media--fichero',
		'what'    => 'el PDF',
		'button'  => 'Usar este PDF',
		'choose'  => 'Elija un PDF',
		'drag'    => 'arrástrelo',
		'formats' => 'Un fichero PDF',
		'empty'   => 'Todavía no hay ningún PDF puesto.',
		'clear'   => 'Quitar este PDF al guardar',
		'upload'  => 'Subir el PDF desde su equipo',
		'accept'  => 'application/pdf',
	);

	/**
	 * One file from the media library: a drop zone when empty, a big preview when set.
	 *
	 * Es la misma pieza para las imágenes de «Apariencia» y para la foto del
	 * ponente, y se comporta como en cualquier aplicación web: vacía, la zona
	 * entera se pulsa o recibe el fichero arrastrado; puesta, la imagen se ve
	 * grande, con un aspa para quitarla y «Reemplazar» encima. Quitar no borra
	 * nada hasta guardar, así que se puede deshacer.
	 *
	 * Lo único que viaja es el `hidden` con el identificador del adjunto. Los
	 * botones nacen ocultos (`data-evt-media-actions`) y los enseña el guion:
	 * sin guion no hay ninguno que no haga nada, se ve el texto de «todavía no
	 * hay nada» y, si se pide, el respaldo del `<noscript>`.
	 *
	 * Claves de `$args`: `name` (el `hidden` con el ID), `id` (base de los
	 * identificadores), `label`, `help`, `file` (id, url, name, width,
	 * height), `can_load`, `can_clear`, `min_width`, `type` (`image` o
	 * `application/pdf`), `title` (de la ventana de la biblioteca), `empty`
	 * (lo que se lee sin nada y sin guion) y `fallback` (prefijo de los campos
	 * del `<noscript>`; vacío, sin respaldo).
	 *
	 * @param array<string, mixed> $args Field settings, as listed above.
	 * @return string
	 */
	public static function media_field( array $args ): string {
		$f = self::media_settings( $args );

		ob_start();
		?>
		<div class="<?php echo esc_attr( 'evt-form-campo evt-media' . $f['t']['class'] ); ?>" data-evt-media data-evt-media-type="<?php echo esc_attr( $f['type'] ); ?>"
			data-evt-media-min-width="<?php echo esc_attr( (string) $f['min_width'] ); ?>"
			data-evt-media-title="<?php echo esc_attr( $f['title'] ); ?>" data-evt-media-button="<?php echo esc_attr( $f['t']['button'] ); ?>">
			<span class="evt-media-rotulo"><?php echo esc_html( $f['label'] ); ?></span>
			<?php if ( '' !== $f['help'] ) : ?>
				<small class="evt-media-ayuda"><?php echo esc_html( $f['help'] ); ?></small>
			<?php endif; ?>

			<?php // Lo único que se envía: el identificador del adjunto. El servidor lo vuelve a comprobar. ?>
			<input type="hidden" id="<?php echo esc_attr( $f['id'] ); ?>" name="<?php echo esc_attr( $f['name'] ); ?>"
				value="<?php echo esc_attr( (string) $f['file_id'] ); ?>" autocomplete="off" data-evt-media-value />

			<div class="evt-media-marco">
				<?php echo self::media_card( $f ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<?php echo self::media_empty( $f ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<?php if ( $f['can_load'] ) : ?>
					<span class="evt-media-progreso" data-evt-media-progress hidden><span data-evt-media-bar></span></span>
				<?php endif; ?>
			</div>

			<?php if ( $f['can_load'] ) : ?>
				<p class="evt-media-estado" data-evt-media-status aria-live="polite"></p>
			<?php endif; ?>
			<?php if ( $f['can_clear'] ) : ?>
				<p class="evt-media-deshacer" data-evt-media-undo hidden>
					<span id="<?php echo esc_attr( $f['id'] . '-deshacer' ); ?>">Se quitará al guardar.</span>
					<button type="button" data-evt-media-restore aria-describedby="<?php echo esc_attr( $f['id'] . '-deshacer' ); ?>">Deshacer</button>
				</p>
			<?php endif; ?>

			<?php echo self::media_fallback( $f ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Media field settings with every key filled in and every word chosen.
	 *
	 * @param array<string, mixed> $args What the caller passed.
	 * @return array<string, mixed>
	 */
	private static function media_settings( array $args ): array {
		$f    = wp_parse_args(
			$args,
			array(
				'name'      => '',
				'id'        => '',
				'label'     => '',
				'help'      => '',
				'file'      => array(),
				'can_load'  => false,
				'can_clear' => false,
				'min_width' => 0,
				'type'      => 'image',
				'title'     => '',
				'empty'     => '',
				'fallback'  => '',
			)
		);
		$file = wp_parse_args(
			(array) $f['file'],
			array(
				'id'     => 0,
				'url'    => '',
				'name'   => '',
				'width'  => 0,
				'height' => 0,
			)
		);

		$t = 'application/pdf' === $f['type'] ? self::MEDIA_PDF : self::MEDIA_IMAGE;
		if ( (int) $f['min_width'] > 0 ) {
			$t['formats'] .= ' · al menos ' . (int) $f['min_width'] . ' px de ancho';
		}
		$own_empty = '' !== (string) $f['empty'];
		if ( $own_empty ) {
			$t['empty'] = (string) $f['empty'];
		}

		$size = '';
		if ( (int) $file['width'] > 0 && (int) $file['height'] > 0 ) {
			$size = (int) $file['width'] . ' × ' . (int) $file['height'] . ' px';
		}
		$id = '' !== (string) $f['id'] ? (string) $f['id'] : (string) $f['name'];

		return array(
			'name'      => (string) $f['name'],
			'id'        => sanitize_html_class( $id ),
			'label'     => (string) $f['label'],
			'help'      => (string) $f['help'],
			'title'     => '' !== (string) $f['title'] ? (string) $f['title'] : (string) $f['label'],
			'type'      => (string) $f['type'],
			'min_width' => (int) $f['min_width'],
			'can_load'  => (bool) $f['can_load'],
			'can_clear' => (bool) $f['can_clear'],
			'fallback'  => (string) $f['fallback'],
			'file_id'   => (int) $file['id'],
			'url'       => (string) $file['url'],
			'file_name' => (string) $file['name'],
			'size'      => $size,
			'set'       => '' !== (string) $file['url'],
			'own_empty' => $own_empty,
			't'         => $t,
		);
	}

	/**
	 * What there is now: the big preview, the cross and «Reemplazar».
	 *
	 * @param array<string, mixed> $f Settings from {@see self::media_settings()}.
	 * @return string
	 */
	private static function media_card( array $f ): string {
		$what = $f['t']['what'];

		ob_start();
		?>
		<div class="evt-media-ficha" data-evt-media-card <?php echo esc_attr( $f['set'] ? '' : 'hidden' ); ?>>
			<span class="evt-media-lienzo">
				<img class="evt-media-miniatura" data-evt-media-thumb src="<?php echo esc_url( $f['url'] ); ?>" alt="" />
				<?php if ( $f['can_clear'] ) : ?>
					<button type="button" class="evt-media-quitar" data-evt-media-clear data-evt-media-actions hidden
						title="<?php echo esc_attr( 'Quitar ' . $what ); ?>" aria-label="<?php echo esc_attr( 'Quitar ' . $what . ' de: ' . $f['label'] ); ?>">
						<?php echo wp_kses( Shell::icon( 'cerrar' ), self::SVG ); ?>
					</button>
				<?php endif; ?>
				<?php if ( $f['can_load'] ) : ?>
					<span class="evt-media-sobre">
						<button type="button" class="evt-media-reemplazar" data-evt-media-pick data-evt-media-actions hidden
							aria-label="<?php echo esc_attr( 'Reemplazar ' . $what . ' de: ' . $f['label'] ); ?>">
							<?php echo wp_kses( Shell::icon( 'reemplazar' ), self::SVG ); ?>
							Reemplazar
						</button>
					</span>
				<?php endif; ?>
			</span>
			<span class="evt-media-datos">
				<strong data-evt-media-name><?php echo esc_html( $f['file_name'] ); ?></strong>
				<small data-evt-media-size><?php echo esc_html( $f['size'] ); ?></small>
			</span>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The empty slot: the drop zone with script, the plain sentence without.
	 *
	 * @param array<string, mixed> $f Settings from {@see self::media_settings()}.
	 * @return string
	 */
	private static function media_empty( array $f ): string {
		ob_start();
		?>
		<div class="evt-media-hueco" data-evt-media-empty <?php echo esc_attr( $f['set'] ? 'hidden' : '' ); ?>>
			<?php if ( $f['can_load'] ) : ?>
				<?php // El nombre del botón es su texto visible —se puede decir en voz alta— y el campo va oculto detrás. ?>
				<button type="button" class="evt-media-zona" data-evt-media-pick data-evt-media-actions hidden>
					<span class="evt-media-zona-icono"><?php echo wp_kses( Shell::icon( 'cargar' ), self::SVG ); ?></span>
					<span><strong><?php echo esc_html( $f['t']['choose'] ); ?></strong> <?php echo esc_html( 'o ' . $f['t']['drag'] . ' aquí' ); ?><span class="screen-reader-text"><?php echo esc_html( ' (' . $f['label'] . ')' ); ?></span></span>
					<small><?php echo esc_html( $f['t']['formats'] ); ?></small>
				</button>
			<?php endif; ?>
			<p class="<?php echo esc_attr( 'evt-media-vacia' . ( $f['own_empty'] ? ' evt-media-vacia--propia' : '' ) ); ?>"><?php echo esc_html( $f['t']['empty'] ); ?></p>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The `<noscript>` way: upload a file, or tick to remove it.
	 *
	 * @param array<string, mixed> $f Settings from {@see self::media_settings()}.
	 * @return string Empty when the field asks for no fallback.
	 */
	private static function media_fallback( array $f ): string {
		if ( '' === $f['fallback'] ) {
			return '';
		}
		$id = $f['id'];

		ob_start();
		?>
		<noscript>
			<?php if ( $f['set'] && $f['can_clear'] ) : ?>
				<label for="<?php echo esc_attr( $id . '-clear' ); ?>">
					<input type="checkbox" id="<?php echo esc_attr( $id . '-clear' ); ?>" name="<?php echo esc_attr( $f['fallback'] . '_clear' ); ?>" value="1" />
					<?php echo esc_html( $f['t']['clear'] ); ?>
				</label>
			<?php endif; ?>
			<label for="<?php echo esc_attr( $id . '-file' ); ?>"><?php echo esc_html( $f['t']['upload'] ); ?></label>
			<input type="file" id="<?php echo esc_attr( $id . '-file' ); ?>" name="<?php echo esc_attr( $f['fallback'] . '_file' ); ?>"
				accept="<?php echo esc_attr( $f['t']['accept'] ); ?>" <?php disabled( ! $f['can_load'], true ); ?> />
		</noscript>
		<?php
		return (string) ob_get_clean();
	}
}
