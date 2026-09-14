<?php
/**
 * The «Apariencia» panel of the event workshop.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

use Evt\Meta\EventMetaKeys;
use Evt\PublicFront\Assets;
use Evt\PublicFront\EventWorkspace;

/**
 * Cómo se ve el evento: los dos colores, las dos tipografías y las imágenes.
 *
 * Hoy esto son sesenta muestras de color en una parrilla, un desplegable con
 * las 1.461 familias de Google Fonts y veinticinco siluetas de separador, y
 * para ver el resultado hay que guardar y abrir la página en otra pestaña.
 * Aquí son un selector de color nativo con su hexadecimal al lado, seis
 * tipografías, seis separadores y una vista previa de la cabecera que se mueve
 * mientras se elige —la pinta el guion de la espina leyendo los `name` de
 * estos mismos controles—.
 *
 * Solo pinta: no lee la petición, no consulta, no decide.
 */
final class EventAppearancePanel {

	/**
	 * A stand-in portrait, so the shape of the photographs can be previewed.
	 *
	 * Va como `data:` y no como fichero porque en producción no hay
	 * repositorio en disco desde el que servir una imagen: el único artefacto
	 * es el bundle de Code Snippets.
	 */
	private const SHAPE_SAMPLE = 'data:image/svg+xml;charset=utf8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2272%22%20height%3D%2272%22%3E%3Crect%20width%3D%2272%22%20height%3D%2272%22%20fill%3D%22%23c3cad2%22%2F%3E%3Ccircle%20cx%3D%2236%22%20cy%3D%2226%22%20r%3D%2213%22%20fill%3D%22%238a97a6%22%2F%3E%3Cpath%20d%3D%22M8%2072c0-16%2012-26%2028-26s28%2010%2028%2026z%22%20fill%3D%22%238a97a6%22%2F%3E%3C%2Fsvg%3E';

	/**
	 * Paint the panel.
	 *
	 * @param array<string, mixed> $m What EventWorkspace::model() returned.
	 * @return string
	 */
	public static function html( array $m ): string {
		$v      = (array) $m['values'];
		$subir  = (bool) $m['can_upload'];
		$medios = (array) $m['media'];

		// Sin color guardado, el selector nativo caería a negro y pintaría una
		// cabecera que nadie eligió: se arranca del azul del sitio.
		$v[ EventMetaKeys::HEADER_BG ]   = self::color( (string) $v[ EventMetaKeys::HEADER_BG ], '#1b4f8a' );
		$v[ EventMetaKeys::HEADER_TEXT ] = self::color( (string) $v[ EventMetaKeys::HEADER_TEXT ], '#ffffff' );

		// Los trozos se arman antes de la plantilla y se escapan dentro de cada
		// ayudante: así la plantilla es HTML de leer y cada `echo` es de una
		// sola línea, que es lo que el linter sabe comprobar.
		$vista         = self::preview( $m, $v );
		$color_bg      = self::color_field(
			'evt-header-bg',
			EventMetaKeys::HEADER_BG,
			'Color de fondo',
			(string) $v[ EventMetaKeys::HEADER_BG ],
			'Puede pulsar la muestra o escribir el código, por ejemplo #1b4f8a.'
		);
		$color_txt     = self::color_field(
			'evt-header-text',
			EventMetaKeys::HEADER_TEXT,
			'Color del texto',
			(string) $v[ EventMetaKeys::HEADER_TEXT ],
			'Sobre fondos oscuros, blanco (#ffffff); sobre claros, casi negro (#1b1b1b).'
		);
		$sel_titulo    = self::select(
			'evt-title-font',
			EventMetaKeys::TITLE_FONT,
			'Tipografía de los títulos',
			EventMetaKeys::fonts(),
			(string) $v[ EventMetaKeys::TITLE_FONT ],
			'La del nombre del evento y la de los encabezados.'
		);
		$sel_cuerpo    = self::select(
			'evt-body-font',
			EventMetaKeys::BODY_FONT,
			'Tipografía del texto',
			EventMetaKeys::fonts(),
			(string) $v[ EventMetaKeys::BODY_FONT ],
			'La de los párrafos. Una de palo seco se lee mejor en pantalla.'
		);
		$sel_forma     = self::select(
			'evt-image-shape',
			EventMetaKeys::IMAGE_SHAPE,
			'Forma de las fotos de personas',
			EventMetaKeys::image_shapes(),
			(string) $v[ EventMetaKeys::IMAGE_SHAPE ],
			'Afecta a las fotografías de ponentes. Redonda recorta la imagen en círculo.'
		);
		$sel_sep       = self::select(
			'evt-separator',
			EventMetaKeys::SEPARATOR,
			'Separador de la cabecera',
			EventMetaKeys::separators(),
			(string) $v[ EventMetaKeys::SEPARATOR ],
			'La silueta con la que termina la banda de arriba. «Sin separador» deja el corte recto.'
		);
		$img_logo      = self::image_field(
			'evt_logo',
			'Logo acompañante',
			self::image_of( (int) ( $medios['logo'] ?? 0 ) ),
			'Sale junto al título en la cabecera. Un PNG con fondo transparente queda mejor sobre el color de fondo.',
			$subir
		);
		$img_cartel    = self::image_field(
			'evt_poster',
			'Cartel del evento',
			self::image_of( (int) ( $medios['poster'] ?? 0 ) ),
			'El cartel completo. Se enseña en la portada del evento, y al pulsarlo se abre a tamaño completo para descargarlo o compartirlo.',
			$subir
		);
		$img_destacada = self::image_field(
			'evt_featured',
			'Imagen destacada',
			self::image_of( (int) ( $medios['featured'] ?? 0 ) ),
			'La que se ve cuando se comparte el enlace del evento y en los listados. Apaisada se recorta menos.',
			$subir
		);

		ob_start();
		?>
		<form class="evt-form" method="post" action="" enctype="multipart/form-data">
			<?php wp_nonce_field( EventWorkspace::nonce_action( EventWorkspace::PANEL_LOOK ), EventWorkspace::nonce_name( EventWorkspace::PANEL_LOOK ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_DO ); ?>" value="<?php echo esc_attr( EventWorkspace::PANEL_LOOK ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_EVENT ); ?>" value="<?php echo esc_attr( (string) (int) $m['event_id'] ); ?>" />

			<?php echo $vista; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>

			<fieldset class="evt-tarjeta">
				<legend>Colores de la cabecera</legend>
				<p>Los dos colores de la banda de arriba de todas las páginas del evento. Elíjalos con contraste: el texto tiene que leerse sobre el fondo.</p>

				<div class="evt-form-fila">
					<div><?php echo $color_bg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></div>
					<div><?php echo $color_txt; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></div>
				</div>
			</fieldset>

			<fieldset class="evt-tarjeta">
				<legend>Tipografías</legend>
				<p>Dos y no más: una para los títulos y otra para el texto. «La del tema» no carga ninguna fuente y es la opción más rápida de cargar.</p>

				<div class="evt-form-fila">
					<div><?php echo $sel_titulo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></div>
					<div><?php echo $sel_cuerpo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></div>
				</div>
			</fieldset>

			<fieldset class="evt-tarjeta">
				<legend>Forma y remate</legend>
				<p>Detalles que se aplican a todas las páginas del evento.</p>

				<div class="evt-form-fila">
					<div><?php echo $sel_forma; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></div>
					<div><?php echo $sel_sep; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></div>
				</div>
			</fieldset>

			<fieldset class="evt-tarjeta">
				<legend>Imágenes</legend>
				<p>
					Las tres salen de la biblioteca de medios del sitio: «Elegir imagen» abre la
					biblioteca, donde puede reutilizar una que ya esté subida o subir una nueva
					(JPG, PNG, WEBP o GIF). Nada cambia hasta que pulse «Guardar la apariencia».
				</p>

				<?php if ( ! $subir ) : ?>
					<p class="<?php echo esc_attr( Assets::alert_class( 'warning' ) ); ?>">
						Su perfil no puede subir ficheros, así que las imágenes solo se pueden quitar. Pídalo a quien administre el aplicativo.
					</p>
				<?php endif; ?>

				<?php echo $img_logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<?php echo $img_cartel; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<?php echo $img_destacada; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			</fieldset>

			<p class="evt-acciones">
				<button class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" type="submit">Guardar la apariencia</button>
			</p>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The live preview of the event header.
	 *
	 * El guion de la espina la repinta al vuelo: busca `[data-evt-preview]`
	 * dentro de este mismo formulario y lee los controles por su `name`.
	 * Sin guion se sigue viendo, con lo último guardado.
	 *
	 * @param array<string, mixed>  $m Model.
	 * @param array<string, string> $v Field values, with the display defaults.
	 * @return string
	 */
	private static function preview( array $m, array $v ): string {
		$logo   = self::image_of( (int) ( $m['media']['logo'] ?? 0 ) );
		$sep    = (string) $v[ EventMetaKeys::SEPARATOR ];
		$titulo = (string) $v[ EventWorkspace::FIELD_TITLE ];
		$fuente = (string) $v[ EventMetaKeys::TITLE_FONT ];
		$cuerpo = (string) $v[ EventMetaKeys::BODY_FONT ];
		$forma  = (string) $v[ EventMetaKeys::IMAGE_SHAPE ];

		ob_start();
		?>
		<div class="evt-preview" data-evt-preview>
			<div class="evt-preview-cabecera" data-evt-preview-header
				style="background-color:<?php echo esc_attr( (string) $v[ EventMetaKeys::HEADER_BG ] ); ?>;color:<?php echo esc_attr( (string) $v[ EventMetaKeys::HEADER_TEXT ] ); ?>">
				<?php if ( '' !== (string) ( $logo['url'] ?? '' ) ) : ?>
					<img class="evt-preview-logo" src="<?php echo esc_url( (string) $logo['url'] ); ?>" alt="" />
				<?php endif; ?>
				<p class="evt-preview-titulo<?php echo esc_attr( '' !== $fuente ? ' evt-font-' . $fuente : '' ); ?>" data-evt-preview-title>
					<?php echo esc_html( '' !== $titulo ? $titulo : 'Nombre del evento' ); ?>
				</p>
				<p class="evt-preview-lema" data-evt-preview-tagline><?php echo esc_html( (string) $v[ EventMetaKeys::TAGLINE ] ); ?></p>
				<span class="evt-preview-sep" data-evt-preview-sep>
					<?php foreach ( EventMetaKeys::separators() as $slug => $rotulo ) : ?>
						<span data-sep="<?php echo esc_attr( (string) $slug ); ?>" <?php echo esc_attr( (string) $slug === $sep ? '' : 'hidden' ); ?>>
							<?php if ( isset( EventChrome::SEPARATORS[ $slug ] ) ) : ?>
								<svg viewBox="0 0 1200 60" preserveAspectRatio="none" role="img" aria-label="<?php echo esc_attr( (string) $rotulo ); ?>">
									<path fill="currentColor" d="<?php echo esc_attr( EventChrome::SEPARATORS[ $slug ] ); ?>"></path>
								</svg>
							<?php endif; ?>
						</span>
					<?php endforeach; ?>
				</span>
			</div>
			<div class="evt-preview-cuerpo<?php echo esc_attr( '' !== $cuerpo ? ' evt-font-' . $cuerpo : '' ); ?>" data-evt-preview-body>
				<span class="<?php echo esc_attr( 'evt-shape-' . ( EventMetaKeys::SHAPE_CIRCLE === $forma ? 'circle' : 'square' ) ); ?>" data-evt-preview-shape>
					<?php // La muestra es una silueta fija dentro de la propia hoja: escapada con esc_attr porque es una constante de esta clase, no una URL de nadie. ?>
					<img src="<?php echo esc_attr( self::SHAPE_SAMPLE ); ?>" width="72" height="72" alt="Ejemplo de la forma de las fotografías" />
				</span>
				Así se verá la cabecera del evento y así se recortarán las fotos de las personas. Es una muestra: no se guarda nada hasta que pulse «Guardar la apariencia».
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * A stored colour, or the fallback when there is none.
	 *
	 * @param string $valor    Stored value.
	 * @param string $fallback Colour to use instead.
	 * @return string
	 */
	private static function color( string $valor, string $fallback ): string {
		$limpio = sanitize_hex_color( $valor );
		return is_string( $limpio ) && '' !== $limpio ? $limpio : $fallback;
	}

	/**
	 * What is known about one image of the event.
	 *
	 * El nombre es el del fichero y no el título del adjunto: en una
	 * biblioteca de 4.597 adjuntos, «Captura de pantalla 2025-11-04» no
	 * distingue nada y `cartel-jornadas-2026.jpg` sí.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array{id:int, url:string, name:string, width:int, height:int}
	 */
	private static function image_of( int $attachment_id ): array {
		$nada = array(
			'id'     => 0,
			'url'    => '',
			'name'   => '',
			'width'  => 0,
			'height' => 0,
		);
		if ( $attachment_id <= 0 ) {
			return $nada;
		}

		$url = (string) wp_get_attachment_image_url( $attachment_id, 'medium' );
		if ( '' === $url ) {
			// El identificador guardado apunta a un adjunto que ya no está:
			// se enseña el hueco, no una imagen rota.
			return $nada;
		}

		$entero  = wp_get_attachment_image_src( $attachment_id, 'full' );
		$fichero = (string) get_attached_file( $attachment_id );

		return array(
			'id'     => $attachment_id,
			'url'    => $url,
			'name'   => '' !== $fichero ? wp_basename( $fichero ) : (string) get_the_title( $attachment_id ),
			'width'  => is_array( $entero ) ? (int) $entero[1] : 0,
			'height' => is_array( $entero ) ? (int) $entero[2] : 0,
		);
	}

	/**
	 * A colour: the native picker and its hexadecimal, writing the same value.
	 *
	 * El que se envía es el campo de texto: quien tenga el guion bloqueado, o
	 * un navegador sin selector de color, sigue pudiendo teclear el código.
	 *
	 * @param string $id     Field id of the hexadecimal input.
	 * @param string $nombre Field name (the meta key).
	 * @param string $rotulo Label.
	 * @param string $valor  Current colour.
	 * @param string $ayuda  Help text.
	 * @return string
	 */
	private static function color_field( string $id, string $nombre, string $rotulo, string $valor, string $ayuda ): string {
		ob_start();
		?>
		<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $rotulo ); ?></label>
		<span class="evt-color">
			<input type="color" value="<?php echo esc_attr( $valor ); ?>"
				data-evt-color-for="<?php echo esc_attr( $id ); ?>"
				aria-label="<?php echo esc_attr( $rotulo . ': elegir con el selector' ); ?>" />
			<input type="text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $nombre ); ?>"
				value="<?php echo esc_attr( $valor ); ?>" pattern="#[0-9a-fA-F]{6}" placeholder="#1b4f8a"
				inputmode="text" spellcheck="false" />
		</span>
		<small><?php echo esc_html( $ayuda ); ?></small>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * One dropdown out of a closed vocabulary.
	 *
	 * @param string                $id      Field id.
	 * @param string                $nombre  Field name.
	 * @param string                $rotulo  Label.
	 * @param array<string, string> $lista   slug => etiqueta.
	 * @param string                $elegido Current value.
	 * @param string                $ayuda   Help text.
	 * @return string
	 */
	private static function select( string $id, string $nombre, string $rotulo, array $lista, string $elegido, string $ayuda ): string {
		ob_start();
		?>
		<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $rotulo ); ?></label>
		<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $nombre ); ?>">
			<?php foreach ( $lista as $slug => $texto ) : ?>
				<option value="<?php echo esc_attr( (string) $slug ); ?>" <?php selected( (string) $slug, $elegido ); ?>>
					<?php echo esc_html( (string) $texto ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<small><?php echo esc_html( $ayuda ); ?></small>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * One image: what there is now, how to reuse one, and how to remove it.
	 *
	 * Lo que viaja en el POST es el `hidden` con el identificador del adjunto
	 * (`evt_logo_id`, `evt_poster_id`, `evt_featured_id`), que rellena el
	 * selector de medios de WordPress. Los botones nacen ocultos y los enseña
	 * el guion: sin guion no hay ninguno que no haga nada, y el respaldo del
	 * `<noscript>` —subir un fichero, o marcar la casilla de quitar— sigue
	 * siendo la forma de cambiar la imagen.
	 *
	 * @param string               $campo    Field prefix, e.g. `evt_logo`.
	 * @param string               $rotulo   Label.
	 * @param array<string, mixed> $imagen   What model() knows about it.
	 * @param string               $ayuda    Help text.
	 * @param bool                 $can_load Whether this person may upload files.
	 * @return string
	 */
	private static function image_field( string $campo, string $rotulo, array $imagen, string $ayuda, bool $can_load ): string {
		$url    = (string) ( $imagen['url'] ?? '' );
		$id     = sanitize_html_class( $campo );
		$puesta = '' !== $url;
		$ancho  = (int) ( $imagen['width'] ?? 0 );
		$alto   = (int) ( $imagen['height'] ?? 0 );

		ob_start();
		?>
		<div class="evt-form-campo evt-media" data-evt-media data-evt-media-title="<?php echo esc_attr( $rotulo ); ?>">
			<span class="evt-media-rotulo"><?php echo esc_html( $rotulo ); ?></span>

			<?php // Lo único que se envía: el identificador del adjunto. El servidor lo vuelve a comprobar. ?>
			<input type="hidden" name="<?php echo esc_attr( $campo . '_id' ); ?>"
				value="<?php echo esc_attr( (string) (int) ( $imagen['id'] ?? 0 ) ); ?>" data-evt-media-value />

			<div class="evt-media-ficha" data-evt-media-card <?php echo esc_attr( $puesta ? '' : 'hidden' ); ?>>
				<img class="evt-media-miniatura" data-evt-media-thumb width="88" height="88"
					src="<?php echo esc_url( $url ); ?>" alt="" />
				<span class="evt-media-datos">
					<strong data-evt-media-name><?php echo esc_html( (string) ( $imagen['name'] ?? '' ) ); ?></strong>
					<small data-evt-media-size><?php echo esc_html( $ancho > 0 && $alto > 0 ? $ancho . ' × ' . $alto . ' px' : '' ); ?></small>
				</span>
			</div>
			<p class="evt-media-vacia" data-evt-media-empty <?php echo esc_attr( $puesta ? 'hidden' : '' ); ?>>
				Todavía no hay ninguna imagen puesta.
			</p>

			<p class="evt-acciones evt-media-botones" data-evt-media-actions hidden>
				<?php if ( $can_load ) : ?>
					<button type="button" class="<?php echo esc_attr( Assets::button_class() ); ?>"
						data-evt-media-pick aria-label="<?php echo esc_attr( 'Elegir imagen para: ' . $rotulo ); ?>">
						Elegir imagen
					</button>
				<?php endif; ?>
				<button type="button" class="<?php echo esc_attr( Assets::button_class() ); ?>"
					data-evt-media-clear aria-label="<?php echo esc_attr( 'Quitar la imagen de: ' . $rotulo ); ?>"
					<?php echo esc_attr( $puesta ? '' : 'hidden' ); ?>>
					Quitar
				</button>
			</p>

			<noscript>
				<?php if ( $puesta ) : ?>
					<label for="<?php echo esc_attr( $id . '-clear' ); ?>">
						<input type="checkbox" id="<?php echo esc_attr( $id . '-clear' ); ?>" name="<?php echo esc_attr( $campo . '_clear' ); ?>" value="1" />
						Quitar esta imagen al guardar
					</label>
				<?php endif; ?>
				<label for="<?php echo esc_attr( $id . '-file' ); ?>">Subir una imagen desde su equipo</label>
				<input type="file" id="<?php echo esc_attr( $id . '-file' ); ?>" name="<?php echo esc_attr( $campo . '_file' ); ?>"
					accept="image/jpeg,image/png,image/webp,image/gif" <?php disabled( ! $can_load, true ); ?> />
			</noscript>

			<small><?php echo esc_html( $ayuda ); ?></small>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
