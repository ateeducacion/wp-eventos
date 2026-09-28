<?php
/**
 * The public page that lists every event on a horizontal timeline.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

use Evt\Access\EventAccess;
use Evt\Domain\DateRange;
use Evt\Domain\EventState;
use Evt\Meta\EventMetaKeys;
use Evt\PostType\EventPostType;
use Evt\Taxonomy\EventTaxonomies;
use Evt\PublicFront\View\EventChrome;
use Evt\PublicFront\View\TimelineView;

/**
 * Todos los eventos publicados en una línea del tiempo horizontal (ADR-0041).
 *
 * Es la puerta pública: no pide sesión. La página que la lleva se pinta
 * entera, como la de un evento: con su barra —el logo, «Acceder» o quién ha
 * entrado— y su pie, sin nada del tema. Abre en el mes actual, con el anterior y el
 * siguiente a los lados; hacia atrás quedan los pasados —también los
 * marcados como históricos— y hacia delante los que vienen. Sin JavaScript
 * es una tira que se desplaza como cualquier otra.
 *
 * Los meses vacíos también salen: si la línea saltara de octubre a marzo,
 * no se leería como tiempo.
 */
final class Timeline {

	public const SHORTCODE = 'evt_timeline';

	/**
	 * Slug of the page that carries the shortcode.
	 */
	public const SLUG = 'eventos';

	/**
	 * Query arguments of the filter. Not `s`: on the front page WordPress
	 * would take it as a search and stop serving the page.
	 */
	public const ARG_SEARCH = 'buscar';
	public const ARG_AREA   = 'ambito';

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue' ) );
		add_action( 'template_redirect', array( self::class, 'render_page' ), EventView::PRIORITY );
		add_action( 'wp_head', array( self::class, 'print_head' ), EventView::HEAD_PRIORITY );
	}

	/**
	 * Whether this request is the page that carries the timeline, and we paint it.
	 *
	 * @return bool
	 */
	public static function takes_over(): bool {
		if ( is_admin() || ! is_page() ) {
			return false;
		}
		$post = get_post( get_queried_object_id() );
		if ( ! $post instanceof \WP_Post || ! has_shortcode( (string) $post->post_content, self::SHORTCODE ) ) {
			return false;
		}
		/** This filter is documented in src/Evt/PublicFront/Shell.php */
		return (bool) apply_filters( 'evt_standalone_page', true );
	}

	/**
	 * Serve the whole document of the timeline page.
	 *
	 * @return void
	 */
	public static function render_page(): void {
		if ( ! self::takes_over() ) {
			return;
		}
		$post = get_post( get_queried_object_id() );

		status_header( 200 );
		Shell::send_header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );

		self::enqueue_assets();
		echo TimelineView::document( (string) $post->post_title, self::model( '', self::filters() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- documento montado escapado.
		Shell::leave();
	}

	/**
	 * The stylesheet of the public pages and the footer colour, in the head.
	 *
	 * Es la misma hoja que la página de un evento: la barra y el pie son los
	 * mismos, y así se ven iguales.
	 *
	 * @return void
	 */
	public static function print_head(): void {
		if ( ! self::takes_over() ) {
			return;
		}
		$hoja = EventView::stylesheet();
		if ( '' !== $hoja ) {
			echo '<style id="evt-evento-css">' . $hoja . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- es CSS, no texto.
		}
		// Sin evento no hay apariencia: solo sale el color del pie.
		$vacia = array_fill_keys( array( 'bg', 'fg', 'accent', 'header_bg_image', 'title_font', 'body_font', 'shape' ), '' );
		echo EventLayout::tokens( array( 'appearance' => $vacia ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido en tokens().
	}

	/**
	 * The filter of this request: what to search and in which scope.
	 *
	 * @return array{search: string, area: int}
	 */
	public static function filters(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- un filtro público de lectura, sin efectos.
		$buscar = isset( $_GET[ self::ARG_SEARCH ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::ARG_SEARCH ] ) ) : '';
		$ambito = isset( $_GET[ self::ARG_AREA ] ) ? absint( $_GET[ self::ARG_AREA ] ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		return array(
			'search' => trim( $buscar ),
			'area'   => $ambito,
		);
	}

	/**
	 * Enqueue our stylesheet and script on the page that carries the timeline.
	 *
	 * Van en línea, como el resto del aplicativo: en producción no hay fichero
	 * que servir ({@see Assets}).
	 *
	 * @return void
	 */
	public static function enqueue(): void {
		if ( is_admin() || ! is_singular() ) {
			return;
		}
		$post = get_post();
		if ( ! $post instanceof \WP_Post || ! has_shortcode( (string) $post->post_content, self::SHORTCODE ) ) {
			return;
		}
		self::enqueue_assets();
	}

	/**
	 * Register and enqueue the inline stylesheet and script. Idempotent.
	 *
	 * @return void
	 */
	public static function enqueue_assets(): void {
		// phpcs:disable WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Inline-only handles have no URL to version.
		if ( ! wp_style_is( 'evt-linea', 'registered' ) ) {
			wp_register_style( 'evt-linea', false, array(), null );
			wp_add_inline_style( 'evt-linea', Assets::contents( 'css/evt-linea.css' ) );
			wp_register_script( 'evt-linea', false, array(), null, true );
			wp_add_inline_script( 'evt-linea', Assets::contents( 'js/evt-linea.js' ) );
		}
		// phpcs:enable WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_enqueue_style( 'evt-linea' );
		wp_enqueue_script( 'evt-linea' );
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array<string, string>|string $atts Attributes.
	 * @return string
	 */
	public static function render( $atts = array() ): string {
		unset( $atts );
		self::enqueue_assets();
		return TimelineView::html( self::model() );
	}

	/**
	 * Everything the timeline paints.
	 *
	 * Con un filtro puesto, la línea abre en el mes con resultados más cercano
	 * a hoy: abrir en un mes vacío escondería justo lo que se ha buscado.
	 *
	 * @param string                             $today   Reference day, Y-m-d ('' = today in the site's zone).
	 * @param array{search?: string, area?: int} $filters What to search and in which scope.
	 * @return array<string, mixed>
	 */
	public static function model( string $today = '', array $filters = array() ): array {
		$today   = '' !== $today ? $today : current_time( 'Y-m-d' );
		$filters = array(
			'search' => (string) ( $filters['search'] ?? '' ),
			'area'   => (int) ( $filters['area'] ?? 0 ),
		);
		$eventos = self::events( $today, $filters );

		$actual = substr( $today, 0, 7 );
		$claves = array_merge( array( self::shift( $actual, -1 ), self::shift( $actual, 1 ) ), array_keys( $eventos ) );
		sort( $claves );
		$desde = (string) reset( $claves );
		$hasta = (string) end( $claves );

		$meses = array();
		for ( $mes = $desde; $mes <= $hasta; $mes = self::shift( $mes, 1 ) ) {
			$meses[] = array(
				'key'     => $mes,
				'name'    => self::month_name( (int) substr( $mes, 5, 2 ) ),
				'year'    => substr( $mes, 0, 4 ),
				'current' => $mes === $actual,
				'past'    => $mes < $actual,
				// El curso escolar empieza en septiembre: ahí se rotula, para no
				// perderse al arrastrar por varios.
				'course'  => '09' === substr( $mes, 5, 2 ) ? substr( $mes, 0, 4 ) . '-' . ( (int) substr( $mes, 0, 4 ) + 1 ) : '',
				'events'  => $eventos[ $mes ] ?? array(),
			);
		}

		$indice = 0;
		foreach ( $meses as $i => $mes ) {
			if ( $mes['current'] ) {
				$indice = $i;
			}
		}

		$filtrado = '' !== $filters['search'] || $filters['area'] > 0;
		if ( $filtrado && array() === $meses[ $indice ]['events'] ) {
			$cerca   = PHP_INT_MAX;
			$elegido = $indice;
			foreach ( $meses as $i => $mes ) {
				if ( array() !== $mes['events'] && abs( $i - $indice ) < $cerca ) {
					$cerca   = abs( $i - $indice );
					$elegido = $i;
				}
			}
			$indice = $elegido;
		}

		return array(
			'months'   => $meses,
			'current'  => $indice,
			'filters'  => $filters,
			'filtered' => $filtrado,
			'count'    => array_sum( array_map( 'count', $eventos ) ),
			'areas'    => self::area_options(),
		);
	}

	/**
	 * Every scope, in tree order, with its depth.
	 *
	 * @return array<int, array{id: int, name: string, depth: int}>
	 */
	public static function area_options(): array {
		$terminos = get_terms(
			array(
				'taxonomy'   => EventTaxonomies::AREA,
				'hide_empty' => false,
				'orderby'    => 'name',
			)
		);
		if ( ! is_array( $terminos ) ) {
			return array();
		}
		$hijos = array();
		foreach ( $terminos as $t ) {
			$hijos[ (int) $t->parent ][] = $t;
		}
		$lista = array();
		$baja  = static function ( int $padre, int $nivel ) use ( &$baja, &$lista, $hijos ): void {
			foreach ( $hijos[ $padre ] ?? array() as $t ) {
				$lista[] = array(
					'id'    => (int) $t->term_id,
					'name'  => (string) $t->name,
					'depth' => $nivel,
				);
				$baja( (int) $t->term_id, $nivel + 1 );
			}
		};
		$baja( 0, 0 );
		return $lista;
	}

	/**
	 * Every published event with a date, grouped by the month it starts in.
	 *
	 * Todos, sin tope: un área tiene pocos eventos y la línea entera pesa
	 * poco. Solo las raíces: las páginas satélite no son eventos.
	 *
	 * Un ámbito trae también los eventos de los ámbitos que cuelgan de él: un
	 * servicio lleva los de sus áreas.
	 *
	 * @param string                           $today   Reference day, Y-m-d.
	 * @param array{search: string, area: int} $filters What to search and in which scope.
	 * @return array<string, array<int, array<string, mixed>>> Y-m => rows.
	 */
	private static function events( string $today, array $filters ): array {
		$extra = array();
		if ( '' !== $filters['search'] ) {
			$extra['s'] = $filters['search'];
		}
		if ( $filters['area'] > 0 ) {
			$extra['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- filtrar por ámbito es lo que se pide.
				array(
					'taxonomy'         => EventTaxonomies::AREA,
					'terms'            => $filters['area'],
					'include_children' => true,
				),
			);
		}
		$posts = get_posts(
			$extra + array(
				'post_type'              => EventPostType::POST_TYPE,
				'post_parent'            => 0,
				'post_status'            => 'publish',
				'numberposts'            => -1,
				'meta_key'               => EventMetaKeys::START_DATE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- ordenar por fecha es la vista entera.
				'orderby'                => 'meta_value',
				'order'                  => 'ASC',
				'update_post_term_cache' => false,
				'suppress_filters'       => false,
			)
		);
		if ( ! is_array( $posts ) ) {
			return array();
		}

		$carteles = array();
		foreach ( $posts as $post ) {
			$carteles[] = self::poster_id( (int) $post->ID );
		}
		$carteles = array_filter( $carteles );
		if ( $carteles ) {
			_prime_post_caches( $carteles, false, true );
		}

		$por_mes = array();
		foreach ( $posts as $post ) {
			$id     = (int) $post->ID;
			$inicio = (string) get_post_meta( $id, EventMetaKeys::START_DATE, true );
			if ( 1 !== preg_match( '/^\d{4}-\d{2}-\d{2}$/', $inicio ) ) {
				continue;
			}
			$fin = (string) get_post_meta( $id, EventMetaKeys::END_DATE, true );

			$por_mes[ substr( $inicio, 0, 7 ) ][] = self::row( $post, $inicio, $fin, $today );
		}
		return $por_mes;
	}

	/**
	 * One event on the line.
	 *
	 * @param \WP_Post $post   Event.
	 * @param string   $inicio Start date.
	 * @param string   $fin    End date.
	 * @param string   $today  Reference day.
	 * @return array<string, mixed>
	 */
	private static function row( \WP_Post $post, string $inicio, string $fin, string $today ): array {
		$id       = (int) $post->ID;
		$estado   = EventState::of( $inicio, $fin, $today );
		$historia = EventAccess::is_archived( $id );
		$cartel   = self::poster_id( $id );
		$abierta  = ! $historia && SignupForm::is_open( $id );
		$pagina   = $abierta ? SignupForm::page( $id ) : 0;
		$colores  = self::colours( $id );

		return array(
			'id'          => $id,
			'title'       => '' !== trim( (string) $post->post_title ) ? (string) $post->post_title : 'Evento sin título',
			'url'         => (string) get_permalink( $id ),
			'dates'       => DateRange::of( $inicio, $fin ),
			'venue'       => (string) get_post_meta( $id, EventMetaKeys::VENUE, true ),
			'state'       => $historia ? 'archived' : $estado,
			'state_label' => $historia ? 'Histórico' : EventState::label( $estado ),
			'done'        => $historia || EventMetaKeys::STATE_FINISHED === $estado,
			'poster'      => $cartel > 0 ? (string) wp_get_attachment_image_url( $cartel, 'medium' ) : '',
			'poster_alt'  => $cartel > 0 ? (string) get_post_meta( $cartel, '_wp_attachment_image_alt', true ) : '',
			'color'       => $colores[0],
			'ink'         => $colores[1],
			'signup_url'  => $pagina > 0 ? (string) get_permalink( $pagina ) : '',
		);
	}

	/**
	 * The image that stands for an event: its poster, or its featured image.
	 *
	 * Un cartel en PDF no se puede pintar en una tarjeta (ADR-0042): va la
	 * destacada, y si tampoco hay, el PDF, por si WordPress le sacó vista
	 * previa al subirlo.
	 *
	 * @param int $event_id Event.
	 * @return int Attachment ID, 0 for none.
	 */
	public static function poster_id( int $event_id ): int {
		$cartel = (int) get_post_meta( $event_id, EventMetaKeys::POSTER_ID, true );
		if ( $cartel > 0 && wp_attachment_is_image( $cartel ) ) {
			return $cartel;
		}
		$destacada = (int) get_post_thumbnail_id( $event_id );
		return $destacada > 0 ? $destacada : $cartel;
	}

	/**
	 * Background of an event without image, and the ink that reads on it.
	 *
	 * @param int $event_id Event.
	 * @return array{0:string, 1:string} Fondo y tinta.
	 */
	public static function colours( int $event_id ): array {
		$fondo = sanitize_hex_color( (string) get_post_meta( $event_id, EventMetaKeys::HEADER_BG, true ) );
		$fondo = is_string( $fondo ) && '' !== $fondo ? $fondo : '#12395b';
		$tinta = sanitize_hex_color( (string) get_post_meta( $event_id, EventMetaKeys::HEADER_TEXT, true ) );
		return array( $fondo, EventChrome::readable_ink( $fondo, is_string( $tinta ) ? $tinta : '' ) );
	}

	/**
	 * A Y-m key moved some months.
	 *
	 * @param string $ym    Year and month, Y-m.
	 * @param int    $delta Months to move.
	 * @return string
	 */
	private static function shift( string $ym, int $delta ): string {
		$total = (int) substr( $ym, 0, 4 ) * 12 + (int) substr( $ym, 5, 2 ) - 1 + $delta;
		return sprintf( '%04d-%02d', intdiv( $total, 12 ), $total % 12 + 1 );
	}

	/**
	 * Spanish name of a month.
	 *
	 * @param int $mes 1–12.
	 * @return string
	 */
	private static function month_name( int $mes ): string {
		$nombres = array( 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre' );
		return $nombres[ $mes - 1 ] ?? '';
	}
}
