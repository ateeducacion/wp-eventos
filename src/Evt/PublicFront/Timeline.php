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
use Evt\PublicFront\View\TimelineView;

/**
 * Todos los eventos publicados en una línea del tiempo horizontal (ADR-0041).
 *
 * Es la puerta pública: no pide sesión y va dentro del tema del sitio, no
 * en el armazón del aplicativo. Abre en el mes actual, con el anterior y el
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
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue' ) );
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
	 * @param string $today Reference day, Y-m-d ('' = today in the site's zone).
	 * @return array{months: array<int, array<string, mixed>>, current: int}
	 */
	public static function model( string $today = '' ): array {
		$today   = '' !== $today ? $today : current_time( 'Y-m-d' );
		$eventos = self::events( $today );

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

		return array(
			'months'  => $meses,
			'current' => $indice,
		);
	}

	/**
	 * Every published event with a date, grouped by the month it starts in.
	 *
	 * Todos, sin tope: un área tiene pocos eventos y la línea entera pesa
	 * poco. Solo las raíces: las páginas satélite no son eventos.
	 *
	 * @param string $today Reference day, Y-m-d.
	 * @return array<string, array<int, array<string, mixed>>> Y-m => rows.
	 */
	private static function events( string $today ): array {
		$posts = get_posts(
			array(
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
		$fondo    = sanitize_hex_color( (string) get_post_meta( $id, EventMetaKeys::HEADER_BG, true ) );

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
			'color'       => is_string( $fondo ) && '' !== $fondo ? $fondo : '#12395b',
			'signup_url'  => $pagina > 0 ? (string) get_permalink( $pagina ) : '',
		);
	}

	/**
	 * The poster of an event, or its featured image when it has none.
	 *
	 * @param int $event_id Event.
	 * @return int Attachment ID, 0 for none.
	 */
	public static function poster_id( int $event_id ): int {
		$cartel = (int) get_post_meta( $event_id, EventMetaKeys::POSTER_ID, true );
		return $cartel > 0 ? $cartel : (int) get_post_thumbnail_id( $event_id );
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
