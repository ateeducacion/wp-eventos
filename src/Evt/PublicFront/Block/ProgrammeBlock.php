<?php
/**
 * Body block: the speakers and the programme of the event, in its sections.
 *
 * @package Evt
 */

namespace Evt\PublicFront\Block;

use Evt\Domain\DateRange;
use Evt\Meta\EventMetaKeys;
use Evt\Meta\ProgrammeMetaKeys;
use Evt\PostType\ActivityPostType;
use Evt\PostType\EventPostType;
use Evt\PostType\SpeakerPostType;
use Evt\PublicFront\Assets;
use Evt\PublicFront\Programme;

/**
 * What each kind of section paints from the data of the event.
 *
 * Hoy lo pinta una vista del gestor de formularios por cada tipo de página;
 * aquí es el tipo de sección el que decide:
 *
 * - `ponentes`: la lista de ponentes, y la ficha de uno;
 * - `programa`: la parrilla, por día y por sede;
 * - `actividades`: las actividades con su descripción, y la ficha de una;
 * - `multimedia`: las actividades que tienen vídeo, con el vídeo;
 * - `contacto`: dónde, teléfono y correo, en tres columnas.
 *
 * Y la portada, «Personas comunicadoras»: los ponentes destacados. Lo que la
 * sección tenga escrito sale antes, en el bloque de contenido.
 *
 * Las fichas cuelgan de su sección —`<sección>/entry/<N>/`— porque es la URL
 * que tienen hoy y la que está enlazada por ahí (ADR-0042).
 */
final class ProgrammeBlock {

	/**
	 * Block name.
	 */
	public const NAME = 'fichas';

	/**
	 * After the written content of the section.
	 */
	public const PRIORITY = 15;

	/**
	 * Name of the front-page block with the featured speakers.
	 */
	public const FEATURED_NAME = 'destacados';

	/**
	 * After the section cards, as today.
	 */
	public const FEATURED_PRIORITY = 35;

	/**
	 * Words of a biography or a description in a list.
	 */
	private const EXCERPT_WORDS = 22;

	/**
	 * First section of each type, per event.
	 *
	 * @var array<int, array<string, int>>
	 */
	private static $sections = array();

	/**
	 * The block of a section page.
	 *
	 * @param array<string, mixed> $m Page model.
	 * @return string
	 */
	public static function html( array $m ): string {
		$evento = (int) $m['event_id'];
		if ( $evento <= 0 || ! empty( $m['is_root'] ) ) {
			return '';
		}
		$pagina = (int) $m['page_id'];
		$ficha  = absint( get_query_var( EventPostType::ENTRY_VAR ) );

		switch ( (string) $m['section_type'] ) {
			case 'ponentes':
				$ponente = self::entry( $evento, $ficha, SpeakerPostType::POST_TYPE );
				return $ponente > 0 ? self::speaker( $evento, $ponente, $pagina ) : self::speakers( $evento, $pagina );
			case 'programa':
				return self::download( $evento ) . self::grid( $evento );
			case 'contacto':
				return self::contact( $pagina );
			case 'actividades':
				$actividad = self::entry( $evento, $ficha, ActivityPostType::POST_TYPE );
				return $actividad > 0 ? self::activity( $evento, $actividad, $pagina ) : self::activities( $evento, $pagina, false );
			case 'multimedia':
				$actividad = self::entry( $evento, $ficha, ActivityPostType::POST_TYPE );
				return $actividad > 0 ? self::activity( $evento, $actividad, $pagina ) : self::activities( $evento, $pagina, true );
		}
		return '';
	}

	/**
	 * «Personas comunicadoras» on the front page: the featured speakers.
	 *
	 * @param array<string, mixed> $m Page model.
	 * @return string
	 */
	public static function featured( array $m ): string {
		$evento = (int) $m['event_id'];
		if ( $evento <= 0 || empty( $m['is_root'] ) ) {
			return '';
		}
		$destacados = array_filter(
			Programme::speakers( $evento ),
			static function ( \WP_Post $p ): bool {
				return 'publish' === $p->post_status && (bool) get_post_meta( $p->ID, ProgrammeMetaKeys::SPEAKER_FEATURED, true );
			}
		);
		if ( array() === $destacados ) {
			return '';
		}
		$seccion = self::section( $evento, 'ponentes' );

		ob_start();
		?>
		<h2>Personas comunicadoras</h2>
		<p>Ponentes y personas comunicadoras que participan en <?php echo esc_html( (string) $m['event_title'] ); ?>.</p>
		<ul class="evt-ev__personas">
			<?php foreach ( $destacados as $ponente ) : ?>
				<?php $url = $seccion > 0 ? EventPostType::entry_url( $seccion, (int) $ponente->ID ) : ''; ?>
				<li class="evt-ev__persona">
					<?php echo self::photo( (int) $ponente->ID, $url ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
					<h3><?php echo self::link( get_the_title( $ponente ), $url ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></h3>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The speakers of the event.
	 *
	 * @param int $evento  Event.
	 * @param int $seccion Section being viewed.
	 * @return string
	 */
	private static function speakers( int $evento, int $seccion ): string {
		$ponentes = self::published( Programme::speakers( $evento ) );
		if ( array() === $ponentes ) {
			return '';
		}

		ob_start();
		?>
		<ul class="evt-ev__personas evt-ev__personas--fichas">
			<?php foreach ( $ponentes as $ponente ) : ?>
				<?php
				$fila = Programme::speaker_row( $ponente );
				$url  = EventPostType::entry_url( $seccion, (int) $fila['id'] );
				$bio  = wp_strip_all_tags( (string) $fila['bio'] );
				?>
				<li class="evt-ev__persona">
					<?php echo self::photo( (int) $fila['id'], $url ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
					<div>
						<h3><?php echo self::link( (string) $fila['name'], $url ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></h3>
						<?php echo self::role( $fila ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
						<?php if ( '' !== trim( $bio ) ) : ?>
							<p>
								<?php echo esc_html( wp_trim_words( $bio, self::EXCERPT_WORDS, '…' ) ); ?>
								<a href="<?php echo esc_url( $url ); ?>">Leer más<span class="screen-reader-text"> sobre <?php echo esc_html( (string) $fila['name'] ); ?></span></a>
							</p>
						<?php endif; ?>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The detail of one speaker, with the activities they take part in.
	 *
	 * @param int $evento  Event.
	 * @param int $id      Speaker.
	 * @param int $seccion Section being viewed.
	 * @return string
	 */
	private static function speaker( int $evento, int $id, int $seccion ): string {
		$fila     = Programme::speaker_row( get_post( $id ) );
		$suyas    = array();
		$programa = self::section( $evento, 'actividades' );
		foreach ( Programme::activities( $evento ) as $actividad ) {
			if ( 'publish' === $actividad->post_status && in_array( $id, Programme::speaker_ids( $actividad->ID ), true ) ) {
				$suyas[] = Programme::activity_row( $actividad );
			}
		}

		ob_start();
		?>
		<article class="evt-ev__ficha">
			<?php echo self::photo( $id, '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			<div>
				<h2><?php echo esc_html( (string) $fila['name'] ); ?></h2>
				<?php echo self::role( $fila ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<?php echo wp_kses_post( wpautop( (string) $fila['bio'] ) ); ?>
				<?php if ( array() !== $suyas ) : ?>
					<h3>Participa en</h3>
					<ul>
						<?php foreach ( $suyas as $actividad ) : ?>
							<li>
								<?php echo self::link( (string) $actividad['title'], $programa > 0 ? EventPostType::entry_url( $programa, (int) $actividad['id'] ) : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
								<?php echo self::when( $actividad ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</article>
		<p><a href="<?php echo esc_url( (string) get_permalink( $seccion ) ); ?>">← Todos los ponentes</a></p>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The parrilla: one panel per day and sede, in tabs or in an accordion.
	 *
	 * Como hoy: una pestaña por día y sede, o un desplegable si el evento eligió
	 * acordeón. Sin guion las pestañas son los días uno debajo de otro, que se
	 * leen igual; el acordeón es `<details>` y no necesita ninguno.
	 *
	 * Lo que no tiene fecha no sale: hoy no hay pestaña donde ponerlo.
	 *
	 * La clase `programa-estandar` es la que tenía la parrilla del sistema
	 * anterior. El CSS a medida de los eventos que escribieron su programa a
	 * mano la esconde, y tiene que seguir haciéndolo.
	 *
	 * @param int $evento Event.
	 * @return string
	 */
	private static function grid( int $evento ): string {
		$dias = array_filter(
			self::published_grid( Programme::grid( $evento ) ),
			static function ( array $dia ): bool {
				return '' !== (string) $dia['date'];
			}
		);
		if ( array() === $dias ) {
			return '';
		}
		$fichas     = self::section( $evento, 'actividades' );
		$acordeon   = EventMetaKeys::LAYOUT_ACCORDION === (string) get_post_meta( $evento, EventMetaKeys::PROGRAMME_LAYOUT, true );
		$primero    = true;
		$contenedor = $acordeon ? 'evt-ev__programa evt-ev__programa--acordeon' : 'evt-ev__programa';

		ob_start();
		?>
		<div class="<?php echo esc_attr( $contenedor . ' programa-estandar' ); ?>"<?php echo $acordeon ? '' : ' data-evt-pestanas'; ?>>
		<?php
		foreach ( $dias as $dia ) {
			foreach ( (array) $dia['venues'] as $sede ) {
				$rotulo = implode( ' – ', array_filter( array( (string) $sede['venue'], DateRange::of( (string) $dia['date'], (string) $dia['date'] ) ) ) );
				?>
				<?php if ( $acordeon ) : ?>
					<details class="evt-ev__dia-panel" name="evt-programa"<?php echo $primero ? ' open' : ''; ?>>
						<summary class="evt-ev__dia"><?php echo esc_html( $rotulo ); ?></summary>
				<?php else : ?>
					<section class="evt-ev__dia-panel">
						<h2 class="evt-ev__dia"><?php echo esc_html( $rotulo ); ?></h2>
				<?php endif; ?>
				<ol class="evt-ev__parrilla">
					<?php foreach ( (array) $sede['rows'] as $fila ) : ?>
						<li class="<?php echo esc_attr( 'evt-ev__hueco evt-ev__hueco--' . sanitize_html_class( (string) $fila['kind'] ) ); ?>">
							<span class="evt-ev__tipo">
								<?php echo esc_html( (string) $fila['kind_label'] ); ?>
								<?php if ( '' !== (string) $fila['room'] ) : ?>
									<span class="evt-ev__sala"><?php echo esc_html( (string) $fila['room'] ); ?></span>
								<?php endif; ?>
							</span>
							<div class="evt-ev__que">
								<h3><?php echo self::link( (string) $fila['title'], $fichas > 0 ? EventPostType::entry_url( $fichas, (int) $fila['id'] ) : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></h3>
								<?php echo self::people( $evento, $fila, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
							</div>
							<span class="evt-ev__hora"><?php echo esc_html( self::hours( $fila ) ); ?></span>
						</li>
					<?php endforeach; ?>
				</ol>
				<?php echo $acordeon ? '</details>' : '</section>'; ?>
				<?php
				$primero = false;
			}
		}
		?>
		</div>
		<?php
		return (string) ob_get_clean() . ( $acordeon ? '' : self::tabs_script() );
	}

	/**
	 * The contact details: where, phone and e-mail, each in its column.
	 *
	 * Tres columnas con su icono, como hoy. La que no tiene dato no sale.
	 *
	 * @param int $pagina Contact page.
	 * @return string Empty when nothing was filled in.
	 */
	private static function contact( int $pagina ): string {
		$dato = static function ( string $clave ) use ( $pagina ): string {
			return trim( (string) get_post_meta( $pagina, $clave, true ) );
		};
		// Una línea en blanco separa sedes; un salto, renglones.
		$parrafos = static function ( string $texto ): string {
			$html = '';
			foreach ( preg_split( '/\R\s*\R/', $texto ) as $bloque ) {
				$lineas = array_filter( array_map( 'trim', preg_split( '/\R/', $bloque ) ) );
				if ( array() !== $lineas ) {
					$html .= '<p>' . implode( '<br />', array_map( 'esc_html', $lineas ) ) . '</p>';
				}
			}
			return $html;
		};

		$columnas  = '';
		$direccion = $parrafos( $dato( EventMetaKeys::CONTACT_ADDRESS ) );
		$mapa      = $dato( EventMetaKeys::CONTACT_MAP );
		if ( '' !== $mapa ) {
			$direccion .= '<p><a href="' . esc_url( $mapa ) . '">Ver en el mapa</a></p>';
		}
		if ( '' !== $direccion ) {
			$columnas .= '<div class="evt-ev__contacto-lugar"><h3 class="screen-reader-text">Dirección</h3>' . $direccion . '</div>';
		}
		$telefono = $parrafos( $dato( EventMetaKeys::CONTACT_PHONE ) );
		if ( '' !== $telefono ) {
			$columnas .= '<div class="evt-ev__contacto-telefono"><h3 class="screen-reader-text">Teléfono</h3>' . $telefono . '</div>';
		}
		$correo = sanitize_email( $dato( EventMetaKeys::CONTACT_EMAIL ) );
		if ( '' !== $correo ) {
			$columnas .= '<div class="evt-ev__contacto-correo"><h3 class="screen-reader-text">Correo</h3><p><a href="' . esc_url( 'mailto:' . $correo ) . '">' . esc_html( $correo ) . '</a></p></div>';
		}

		return '' !== $columnas ? '<div class="evt-ev__contacto">' . $columnas . '</div>' : '';
	}

	/**
	 * «Descargar programa»: the PDF of the programme, after the written text.
	 *
	 * Como siempre: un botón grande con su icono, centrado, antes de la parrilla.
	 *
	 * @param int $evento Event.
	 * @return string Empty when there is no PDF.
	 */
	private static function download( int $evento ): string {
		$id  = (int) get_post_meta( $evento, EventMetaKeys::PROGRAMME_FILE_ID, true );
		$url = $id > 0 ? (string) wp_get_attachment_url( $id ) : '';
		if ( '' === $url ) {
			return '';
		}
		return '<p class="evt-ev__descarga"><a class="evt-ev__descargar" href="' . esc_url( $url ) . '" download>'
			. '<svg viewBox="0 -960 960 960" width="40" height="40" aria-hidden="true" focusable="false"><path fill="currentColor" d="M480-320 280-520l56-58 104 104v-326h80v326l104-104 56 58-200 200ZM240-160q-33 0-56.5-23.5T160-240v-120h80v120h480v-120h80v120q0 33-23.5 56.5T720-160H240Z"/></svg>'
			. '<span>Descargar programa</span></a></p>';
	}

	/**
	 * The few lines that turn the day panels into tabs.
	 *
	 * En línea y no encolado: el documento de la página del evento lo escribimos
	 * nosotros entero, y la hoja ya va igual. Con un solo panel no hay pestañas.
	 *
	 * @return string
	 */
	private static function tabs_script(): string {
		$js = Assets::contents( 'js/evt-evento.js' );
		return '' !== $js ? '<script>' . $js . '</script>' : '';
	}

	/**
	 * The activities with their description; only the ones with video for multimedia.
	 *
	 * @param int  $evento     Event.
	 * @param int  $seccion    Section being viewed.
	 * @param bool $solo_video Whether to list only activities with a recording.
	 * @return string
	 */
	private static function activities( int $evento, int $seccion, bool $solo_video ): string {
		$filas = array();
		foreach ( self::published( Programme::activities( $evento ) ) as $actividad ) {
			$fila = Programme::activity_row( $actividad );
			if ( ! $solo_video || '' !== (string) $fila['video'] ) {
				$filas[] = $fila;
			}
		}
		if ( array() === $filas ) {
			return '';
		}

		ob_start();
		?>
		<ul class="evt-ev__actividades">
			<?php foreach ( $filas as $fila ) : ?>
				<?php
				$url     = EventPostType::entry_url( $seccion, (int) $fila['id'] );
				$resumen = wp_strip_all_tags( (string) $fila['summary'] );
				?>
				<li class="evt-ev__actividad">
					<span class="evt-ev__tipo"><?php echo esc_html( (string) $fila['kind_label'] ); ?></span>
					<h3><?php echo self::link( (string) $fila['title'], $url ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></h3>
					<?php if ( $solo_video ) : ?>
						<?php echo self::video( (string) $fila['video'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- oEmbed de un proveedor admitido. ?>
					<?php elseif ( '' !== trim( $resumen ) ) : ?>
						<p><?php echo esc_html( wp_trim_words( $resumen, self::EXCERPT_WORDS, '…' ) ); ?></p>
					<?php endif; ?>
					<?php echo self::people( $evento, $fila, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
					<?php echo self::when( $fila ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The detail of one activity.
	 *
	 * @param int $evento  Event.
	 * @param int $id      Activity.
	 * @param int $seccion Section being viewed.
	 * @return string
	 */
	private static function activity( int $evento, int $id, int $seccion ): string {
		$fila = Programme::activity_row( get_post( $id ) );

		ob_start();
		?>
		<article class="evt-ev__ficha evt-ev__ficha--actividad">
			<div>
				<span class="evt-ev__tipo"><?php echo esc_html( (string) $fila['kind_label'] ); ?></span>
				<h2><?php echo esc_html( (string) $fila['title'] ); ?></h2>
				<?php echo self::when( $fila ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<?php echo self::video( (string) $fila['video'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- oEmbed de un proveedor admitido. ?>
				<?php echo wp_kses_post( wpautop( (string) $fila['summary'] ) ); ?>
				<?php echo self::people( $evento, $fila, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			</div>
		</article>
		<p><a href="<?php echo esc_url( (string) get_permalink( $seccion ) ); ?>">← Volver</a></p>
		<?php
		return (string) ob_get_clean();
	}

	/*
	 * -----------------------------------------------------------------------
	 * Piezas
	 * -----------------------------------------------------------------------
	 */

	/**
	 * The speaker or activity of this event that an entry number points at.
	 *
	 * Primero por ID; si no es de este evento, por el número que tenía en el
	 * sistema anterior.
	 *
	 * @param int    $evento    Event.
	 * @param int    $ficha     Entry number from the URL.
	 * @param string $post_type Speaker or activity.
	 * @return int 0 when none.
	 */
	private static function entry( int $evento, int $ficha, string $post_type ): int {
		if ( $ficha <= 0 ) {
			return 0;
		}
		$post = get_post( $ficha );
		if ( $post instanceof \WP_Post && $post_type === $post->post_type && (int) $post->post_parent === $evento && 'publish' === $post->post_status ) {
			return $ficha;
		}
		$ids = get_posts(
			array(
				'post_type'   => $post_type,
				'post_parent' => $evento,
				'post_status' => 'publish',
				'numberposts' => 1,
				'fields'      => 'ids',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- solo en las URL antiguas de una ficha, acotado a un evento.
				'meta_query'  => array(
					array(
						'key'   => ProgrammeMetaKeys::LEGACY_ENTRY,
						'value' => (string) $ficha,
					),
				),
			)
		);
		return $ids ? (int) $ids[0] : 0;
	}

	/**
	 * First published section of one type in this event.
	 *
	 * @param int    $evento Event.
	 * @param string $tipo   Section type.
	 * @return int 0 when there is none.
	 */
	private static function section( int $evento, string $tipo ): int {
		if ( ! isset( self::$sections[ $evento ] ) ) {
			self::$sections[ $evento ] = array();
			$hijas                     = get_posts(
				array(
					'post_type'   => EventPostType::POST_TYPE,
					'post_parent' => $evento,
					'post_status' => 'publish',
					'numberposts' => 100,
					'orderby'     => 'menu_order',
					'order'       => 'ASC',
				)
			);
			foreach ( $hijas as $hija ) {
				$suyo = (string) get_post_meta( $hija->ID, EventMetaKeys::SECTION_TYPE, true );
				if ( ! isset( self::$sections[ $evento ][ $suyo ] ) ) {
					self::$sections[ $evento ][ $suyo ] = (int) $hija->ID;
				}
			}
		}
		if ( isset( self::$sections[ $evento ][ $tipo ] ) ) {
			return self::$sections[ $evento ][ $tipo ];
		}
		// Sin página de actividades, la ficha de una actividad cuelga del programa.
		return 'actividades' === $tipo ? ( self::$sections[ $evento ]['programa'] ?? 0 ) : 0;
	}

	/**
	 * Only what is published: a draft speaker is not on the web.
	 *
	 * @param \WP_Post[] $posts Posts.
	 * @return \WP_Post[]
	 */
	private static function published( array $posts ): array {
		return array_values(
			array_filter(
				$posts,
				static function ( \WP_Post $p ): bool {
					return 'publish' === $p->post_status;
				}
			)
		);
	}

	/**
	 * The parrilla without anything that is not published.
	 *
	 * @param array<int, array<string, mixed>> $dias Grid from Programme::grid().
	 * @return array<int, array<string, mixed>>
	 */
	private static function published_grid( array $dias ): array {
		$out = array();
		foreach ( $dias as $dia ) {
			$sedes = array();
			foreach ( (array) $dia['venues'] as $sede ) {
				$sede['rows'] = array_values(
					array_filter(
						(array) $sede['rows'],
						static function ( array $fila ): bool {
							return 'publish' === get_post_status( (int) $fila['id'] );
						}
					)
				);
				if ( array() !== $sede['rows'] ) {
					$sedes[] = $sede;
				}
			}
			if ( array() !== $sedes ) {
				$dia['venues'] = $sedes;
				$out[]         = $dia;
			}
		}
		return $out;
	}

	/**
	 * A person's photo, or a neutral silhouette when there is none.
	 *
	 * @param int    $id  Speaker.
	 * @param string $url Where it links, '' for nowhere.
	 * @return string
	 */
	private static function photo( int $id, string $url ): string {
		$src = (string) get_the_post_thumbnail_url( $id, 'medium' );
		$img = '' !== $src
			? '<img class="evt-ev__retrato" src="' . esc_url( $src ) . '" alt="" loading="lazy" />'
			: '<span class="evt-ev__retrato evt-ev__retrato--vacio" aria-hidden="true"><svg viewBox="0 0 24 24" preserveAspectRatio="xMidYMax meet"><circle cx="12" cy="9.5" r="4.6"/><path d="M2.5 24c0-5.6 4.3-9.4 9.5-9.4s9.5 3.8 9.5 9.4z"/></svg></span>';
		return '' !== $url ? '<a href="' . esc_url( $url ) . '" tabindex="-1" aria-hidden="true">' . $img . '</a>' : $img;
	}

	/**
	 * A title, linked when there is somewhere to go.
	 *
	 * @param string $texto Text.
	 * @param string $url   URL, '' for none.
	 * @return string
	 */
	private static function link( string $texto, string $url ): string {
		return '' !== $url ? '<a href="' . esc_url( $url ) . '">' . esc_html( $texto ) . '</a>' : esc_html( $texto );
	}

	/**
	 * Role and organisation under a name.
	 *
	 * @param array<string, mixed> $fila Speaker row.
	 * @return string
	 */
	private static function role( array $fila ): string {
		$linea = implode( ' – ', array_filter( array( trim( (string) $fila['role'] ), trim( (string) $fila['org'] ) ) ) );
		return '' === $linea ? '' : '<p class="evt-ev__cargo">' . esc_html( $linea ) . '</p>';
	}

	/**
	 * Who takes part: the speakers, linked to their detail, and the guests.
	 *
	 * @param int                  $evento     Event.
	 * @param array<string, mixed> $fila       Activity row.
	 * @param bool                 $con_cargo  Whether to add each role.
	 * @return string
	 */
	private static function people( int $evento, array $fila, bool $con_cargo ): string {
		$fichas = self::section( $evento, 'ponentes' );
		$gente  = array();
		foreach ( (array) $fila['speakers'] as $id => $nombre ) {
			$cargo   = $con_cargo ? trim( (string) get_post_meta( (int) $id, ProgrammeMetaKeys::SPEAKER_ROLE, true ) ) : '';
			$gente[] = self::link( (string) $nombre, $fichas > 0 ? EventPostType::entry_url( $fichas, (int) $id ) : '' )
				. ( '' !== $cargo ? ' <span class="evt-ev__cargo">(' . esc_html( $cargo ) . ')</span>' : '' );
		}
		foreach ( preg_split( '/\r\n|\r|\n/', (string) $fila['guests'] ) as $invitado ) {
			if ( '' !== trim( $invitado ) ) {
				$gente[] = esc_html( trim( $invitado ) );
			}
		}
		if ( array() === $gente ) {
			return '';
		}
		return '<ul class="evt-ev__gente"><li>' . implode( '</li><li>', $gente ) . '</li></ul>';
	}

	/**
	 * «26 de noviembre de 2021 · 17:00–18:00 · Auditorio».
	 *
	 * @param array<string, mixed> $fila Activity row.
	 * @return string
	 */
	private static function when( array $fila ): string {
		$trozos = array_filter(
			array(
				'' !== (string) $fila['date'] ? DateRange::of( (string) $fila['date'], (string) $fila['date'] ) : '',
				self::hours( $fila ),
				implode( ', ', array_filter( array( (string) $fila['room'], (string) $fila['venue'] ) ) ),
			)
		);
		return array() === $trozos ? '' : '<p class="evt-ev__cuando">' . esc_html( implode( ' · ', $trozos ) ) . '</p>';
	}

	/**
	 * «17:00–18:00», «17:00» or ''.
	 *
	 * @param array<string, mixed> $fila Activity row.
	 * @return string
	 */
	private static function hours( array $fila ): string {
		return implode( '–', array_filter( array( (string) $fila['start'], (string) $fila['end'] ) ) );
	}

	/**
	 * The recording, embedded when WordPress knows the provider; a link otherwise.
	 *
	 * @param string $url Video URL.
	 * @return string
	 */
	private static function video( string $url ): string {
		if ( '' === $url ) {
			return '';
		}
		// Un fichero de vídeo o de audio se reproduce aquí mismo, con el
		// reproductor de WordPress; lo demás, si WordPress sabe incrustarlo.
		$tipo = wp_check_filetype( (string) wp_parse_url( $url, PHP_URL_PATH ) );
		if ( 0 === strpos( (string) $tipo['type'], 'video/' ) ) {
			return '<div class="evt-ev__video evt-ev__video--fichero">' . wp_video_shortcode( array( 'src' => $url ) ) . '</div>';
		}
		if ( 0 === strpos( (string) $tipo['type'], 'audio/' ) ) {
			return wp_audio_shortcode( array( 'src' => $url ) );
		}
		$embed = wp_oembed_get( $url );
		if ( is_string( $embed ) && '' !== $embed ) {
			return '<div class="evt-ev__video">' . $embed . '</div>';
		}
		return '<p><a href="' . esc_url( $url ) . '">Ver el vídeo</a></p>';
	}
}
