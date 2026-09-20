<?php
/**
 * The list of events someone may manage, with its filters and counts.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

use Evt\Access\EventAccess;
use Evt\Domain\EventState;
use Evt\Meta\EventMetaKeys;
use Evt\PostType\EventPostType;
use Evt\PublicFront\View\EventListView;
use Evt\Taxonomy\EventTaxonomies;

/**
 * Shortcode [evt_event_list]: la pantalla «Eventos».
 *
 * Es la pantalla en la que se empieza el día: qué eventos hay, en qué estado
 * están, cuántas secciones tiene cada uno y por dónde se entra a arreglarlos.
 * Hoy eso no existe —`/borradores/` es una rejilla sin acciones— y el estado
 * lo marca alguien a mano en una taxonomía que nadie vuelve a tocar.
 *
 * Aquí casi no se muta nada: se lee. Por eso todos sus parámetros van en la
 * URL. La única excepción es restaurar un evento de la papelera, que va por
 * POST con su nonce por fila y nunca por GET —un enlace pegado en un correo no
 * puede resucitar nada—. El guardián sigue siendo {@see EventAccess}: lo que no
 * se pueda editar no se enumera ni se restaura, y quien no tiene área no ve
 * nada (falla en cerrado).
 */
final class EventList {

	public const SHORTCODE = 'evt_event_list';

	/**
	 * Filas por página.
	 */
	public const PAGE_SIZE = 20;

	/**
	 * Filtro de los eventos que todavía no se han publicado.
	 *
	 * Va en la misma lista que los estados derivados porque para quien mira es
	 * lo mismo: una manera de acotar. Que uno salga de las fechas y el otro del
	 * estado de la entrada es cosa nuestra.
	 */
	public const FILTER_DRAFT = 'draft';

	/**
	 * Filtro de los eventos que están en la papelera.
	 *
	 * Va en la misma lista que los demás filtros porque para quien mira es una
	 * manera más de acotar, pero sale de otra consulta: los estados normales no
	 * incluyen `trash`, así que el listado de siempre nunca enseña lo enviado a
	 * la papelera.
	 */
	public const FILTER_TRASH = 'trash';

	/**
	 * Filtro de los eventos cerrados a edición: el estado «histórico».
	 *
	 * No sale de las fechas como los demás, sino de la meta `evt_archived`, y
	 * es transversal a ellos: un evento histórico es además, casi siempre, uno
	 * finalizado. Se lista como un estado más porque para quien mira eso es lo
	 * que es: una manera de acotar.
	 */
	public const FILTER_ARCHIVED = 'archived';

	/**
	 * Query var of the área filter.
	 *
	 * Ninguna se llama como una taxonomía: `evt_area`, `evt_type` y
	 * `evt_course` son variables de consulta públicas —las registra
	 * `register_taxonomy`— y usarlas aquí convertiría la página del listado en
	 * el archivo de un término, que ya no es una página singular ni lleva
	 * nuestro shortcode.
	 */
	public const VAR_AREA = 'evt_filter_area';

	/**
	 * Query var of the tipología filter.
	 */
	public const VAR_TYPE = 'evt_filter_type';

	/**
	 * Query var of the curso escolar filter.
	 */
	public const VAR_COURSE = 'evt_filter_course';

	/**
	 * Query var of the state filter.
	 */
	public const VAR_STATE = 'evt_filter_state';

	/**
	 * Query var of the title search.
	 */
	public const VAR_SEARCH = 'evt_search';

	/**
	 * Query var of the page number.
	 */
	public const VAR_PAGE = 'evt_page';

	/**
	 * Query var other screens use to say what just happened.
	 */
	public const VAR_NOTICE = 'evt_notice';

	/**
	 * Post statuses an event can be in. La papelera no es uno.
	 *
	 * @var string[]
	 */
	private const STATUSES = array( 'publish', 'future', 'draft', 'pending', 'private' );

	/**
	 * Hidden field naming the operation a POST asks for.
	 */
	public const FIELD_DO = 'evt_list_do';

	/**
	 * Hidden field with the event a POST is about.
	 */
	public const FIELD_EVENT = 'evt_list_event';

	/**
	 * Nonce action of the restore button.
	 */
	public const NONCE_ACTION = 'evt_list_restore';

	/**
	 * Register the shortcode and the restore handler.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'render' ) );
		// Después de que el CPT y sus capacidades estén registrados (init 10 y
		// 11), y antes de que se pinte nada: aquí todavía se puede redirigir.
		add_action( 'init', array( self::class, 'handle' ), 20 );
	}

	/**
	 * Name of the nonce field of the restore button of one row.
	 *
	 * Uno por fila: así el identificador que escribe `wp_nonce_field()` no se
	 * repite en la página y cada botón lleva el suyo.
	 *
	 * @param int $event_id Event post ID.
	 * @return string
	 */
	public static function nonce_name( int $event_id ): string {
		return 'evt_list_nonce_' . $event_id;
	}

	/**
	 * Bring one event back from the trash.
	 *
	 * Vuelve en borrador, y eso es a propósito: es lo que hace
	 * `wp_untrash_post()` desde WordPress 5.6 y es lo que aquí se quiere. Un
	 * evento que se borró por error no tiene por qué reaparecer publicado en la
	 * web sin que nadie lo mire.
	 *
	 * Ojo con lo que WordPress NO hace: enviar un evento a la papelera **no**
	 * manda a la papelera sus secciones satélite —comprobado en WordPress 7.1—,
	 * así que siguen donde estaban y restaurar el evento las deja tal cual.
	 *
	 * @return void
	 */
	public static function handle(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- se comprueba abajo, en cuanto se sabe de qué evento se habla.
		$op = sanitize_key( wp_unslash( (string) ( $_POST[ self::FIELD_DO ] ?? '' ) ) );
		if ( ! in_array( $op, array( 'restore', 'publish', 'unpublish' ), true ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- lo mismo: el nonce depende de esta fila.
		$event_id = absint( wp_unslash( $_POST[ self::FIELD_EVENT ] ?? 0 ) );
		$campo    = self::nonce_name( $event_id );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- esto es la comprobación del nonce.
		$nonce = sanitize_text_field( wp_unslash( (string) ( $_POST[ $campo ] ?? '' ) ) );
		if ( false === wp_verify_nonce( $nonce, self::nonce_action( $op ) ) ) {
			return;
		}

		if ( 'restore' !== $op ) {
			self::switch_status( $op, $event_id );
			return;
		}

		if ( ! self::may_restore( get_current_user_id(), $event_id ) ) {
			Shell::leave( self::back_to( self::FILTER_TRASH, 'permiso' ) );
			return;
		}

		wp_untrash_post( $event_id );
		Shell::leave( self::back_to( 'all', 'restaurado' ) );
	}

	/**
	 * Publish an event, or send it back to draft.
	 *
	 * Es el interruptor del listado. Publicar **no toca las páginas satélite**:
	 * cada una tiene su estado y se publica desde el taller, que es lo que deja
	 * enseñar la portada de un evento con una sección todavía sin terminar.
	 *
	 * @param string $op       publish | unpublish.
	 * @param int    $event_id Event post ID.
	 * @return void
	 */
	private static function switch_status( string $op, int $event_id ): void {
		$user_id  = get_current_user_id();
		$publicar = 'publish' === $op;

		if ( EventPostType::POST_TYPE !== get_post_type( $event_id )
			|| 0 !== (int) get_post_field( 'post_parent', $event_id )
			|| 'trash' === get_post_status( $event_id )
			|| ! EventAccess::can_publish( $user_id, $event_id )
			|| ! EventAccess::can_edit( $user_id, $event_id ) ) {
			Shell::leave( self::back_to( 'all', 'permiso' ) );
			return;
		}

		wp_update_post(
			array(
				'ID'          => $event_id,
				'post_status' => $publicar ? 'publish' : 'draft',
			)
		);
		Shell::leave( self::back_to( 'all', $publicar ? 'publicado' : 'despublicado' ) );
	}

	/**
	 * Nonce action of one list operation.
	 *
	 * Una por acción: el nonce de restaurar no vale para publicar.
	 *
	 * @param string $op Operation.
	 * @return string
	 */
	public static function nonce_action( string $op = 'restore' ): string {
		return 'restore' === $op ? self::NONCE_ACTION : 'evt_list_' . $op;
	}

	/**
	 * Whether this person may bring that event back.
	 *
	 * Lo mismo que para editarlo, ni más ni menos: nadie restaura lo que no
	 * podría tocar. Y tiene que estar de verdad en la papelera y ser un evento
	 * raíz —una sección se restaura desde el taller de su evento—.
	 *
	 * @param int $user_id Who is asking.
	 * @param int $post_id Event post ID.
	 * @return bool
	 */
	private static function may_restore( int $user_id, int $post_id ): bool {
		return 'trash' === get_post_status( $post_id )
			&& EventPostType::POST_TYPE === get_post_type( $post_id )
			&& 0 === (int) get_post_field( 'post_parent', $post_id )
			&& EventAccess::can_edit( $user_id, $post_id );
	}

	/**
	 * Back to the list, on one filter and with one notice.
	 *
	 * @param string $state  State filter to land on.
	 * @param string $notice Key of flash().
	 * @return string
	 */
	private static function back_to( string $state, string $notice ): string {
		$url = self::url( self::no_filters(), array( 'state' => $state ) );
		if ( '' === $url ) {
			$url = Shell::back_url();
		}
		return add_query_arg( self::VAR_NOTICE, $notice, $url );
	}

	/**
	 * The state filter: predicates over what is already stored.
	 *
	 * @return array<string, string> clave => etiqueta.
	 */
	public static function state_filters(): array {
		return array(
			'all'                         => 'Todos',
			EventMetaKeys::STATE_UPCOMING => 'Próximos',
			EventMetaKeys::STATE_OPEN     => 'Abiertos',
			EventMetaKeys::STATE_FINISHED => 'Finalizados',
			self::FILTER_ARCHIVED         => 'Históricos',
			self::FILTER_DRAFT            => 'En borrador',
			self::FILTER_TRASH            => 'Papelera',
		);
	}

	/**
	 * Human label of a post status.
	 *
	 * @return array<string, string> estado => etiqueta.
	 */
	public static function status_labels(): array {
		return array(
			'publish' => 'Publicado',
			'future'  => 'Programado',
			'draft'   => 'Borrador',
			'pending' => 'Pendiente de revisión',
			'private' => 'Privado',
			'trash'   => 'En la papelera',
		);
	}

	/**
	 * Read one navigation parameter.
	 *
	 * @param string $key      Query var.
	 * @param string $fallback What to return when it is not there.
	 * @return string
	 */
	public static function input( string $key, string $fallback = '' ): string {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- filtros de lectura: esta pantalla no muta nada.
		return isset( $_GET[ $key ] ) && is_string( $_GET[ $key ] )
			? sanitize_text_field( wp_unslash( $_GET[ $key ] ) )
			: $fallback;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * What the URL is asking for.
	 *
	 * @return array{area:int, type:int, course:int, state:string, search:string, page:int}
	 */
	public static function selection(): array {
		$estado = self::input( self::VAR_STATE, 'all' );

		return array(
			'area'   => max( 0, (int) self::input( self::VAR_AREA ) ),
			'type'   => max( 0, (int) self::input( self::VAR_TYPE ) ),
			'course' => max( 0, (int) self::input( self::VAR_COURSE ) ),
			'state'  => isset( self::state_filters()[ $estado ] ) ? $estado : 'all',
			'search' => mb_substr( self::input( self::VAR_SEARCH ), 0, 120 ),
			'page'   => max( 1, (int) self::input( self::VAR_PAGE, '1' ) ),
		);
	}

	/**
	 * The list URL, keeping what is selected.
	 *
	 * @param array<string, mixed> $selection What selection() returned.
	 * @param array<string, mixed> $changes   What to change in it.
	 * @return string
	 */
	public static function url( array $selection, array $changes = array() ): string {
		// Cambiar un filtro devuelve a la primera página: la número siete de la
		// selección anterior casi nunca existe en la nueva.
		$s = array_merge( $selection, array( 'page' => 1 ), $changes );

		$args = array(
			self::VAR_AREA   => $s['area'] > 0 ? (string) $s['area'] : '',
			self::VAR_TYPE   => $s['type'] > 0 ? (string) $s['type'] : '',
			self::VAR_COURSE => $s['course'] > 0 ? (string) $s['course'] : '',
			self::VAR_STATE  => 'all' !== $s['state'] ? (string) $s['state'] : '',
			self::VAR_SEARCH => (string) $s['search'],
			self::VAR_PAGE   => $s['page'] > 1 ? (string) $s['page'] : '',
		);

		return Shell::url(
			'events',
			array_filter(
				$args,
				static function ( string $valor ): bool {
					return '' !== $valor;
				}
			)
		);
	}

	/**
	 * Everything the screen decides before painting.
	 *
	 * @return array<string, mixed>
	 */
	public static function model(): array {
		$m           = self::blank();
		$m['reason'] = self::why_nothing();

		if ( '' !== $m['reason'] ) {
			return $m;
		}

		$user_id = get_current_user_id();
		$todas   = EventAccess::can_edit_all_areas( $user_id );
		$m       = array_merge( $m, self::chrome( $user_id, $todas ) );

		$rows         = self::collect( $user_id );
		$m['options'] = self::options( $rows );

		$s        = self::sanitise( self::selection(), $m['options'] );
		$rows     = self::narrow( $rows, $s );
		$papelera = self::by_state( $rows, self::FILTER_TRASH );
		$vivos    = self::by_state( $rows, 'vivos' );

		// Las cifras de los estados se cuentan solo sobre lo vivo: un evento en
		// la papelera no es un evento «próximo» ni uno «en borrador».
		$m['counts']                       = self::count_states( $vivos );
		$m['counts'][ self::FILTER_TRASH ] = count( $papelera );

		$en_papelera = self::FILTER_TRASH === (string) $s['state'];
		$rows        = $en_papelera ? $papelera : self::by_state( $vivos, (string) $s['state'] );

		usort( $rows, array( self::class, 'compare' ) );

		$m['total'] = count( $rows );
		$m['pages'] = max( 1, (int) ceil( $m['total'] / self::PAGE_SIZE ) );
		$m['page']  = min( $m['pages'], (int) $s['page'] );
		$s['page']  = $m['page'];

		$m['rows']      = self::with_sections(
			array_slice( $rows, ( $m['page'] - 1 ) * self::PAGE_SIZE, self::PAGE_SIZE )
		);
		$m['selection'] = $s;

		if ( array() === $m['rows'] ) {
			$filtrando       = self::is_filtered( $s );
			$m['empty_text'] = $en_papelera
				? 'La papelera está vacía: no hay ningún evento esperando a que lo restauren.'
				: self::empty_text( $filtrando, $todas );
			$m['reset_url']  = $filtrando ? self::url( $s, self::no_filters() ) : '';
		}

		return $m;
	}

	/**
	 * The model of a screen with nothing on it yet.
	 *
	 * @return array<string, mixed>
	 */
	private static function blank(): array {
		return array(
			'can_use'     => false,
			'reason'      => '',
			'notice'      => array(
				'type' => '',
				'text' => '',
			),
			'subtitle'    => '',
			'selection'   => self::selection(),
			'options'     => array(
				'area'   => array(),
				'type'   => array(),
				'course' => array(),
			),
			'area_filter' => false,
			'scoped'      => false,
			'counts'      => array_fill_keys( array_keys( self::state_filters() ), 0 ),
			'total'       => 0,
			'page'        => 1,
			'pages'       => 1,
			'rows'        => array(),
			'can_create'  => false,
			'create_url'  => '',
			'empty_text'  => '',
			'reset_url'   => '',
			'page_id'     => 0,
		);
	}

	/**
	 * Why this person gets no list at all.
	 *
	 * @return string Empty when there is a list to show.
	 */
	private static function why_nothing(): string {
		if ( ! is_user_logged_in() ) {
			return 'Debe iniciar sesión con su usuario para gestionar eventos.';
		}

		$user_id = get_current_user_id();
		if ( Shell::can_use( $user_id ) ) {
			return '';
		}

		// Falta el permiso, o falta el área: no es lo mismo y no se arregla en
		// el mismo sitio.
		return user_can( $user_id, 'edit_evt_events' ) || EventAccess::is_manager( $user_id )
			? 'No tiene ningún ámbito asignado en su perfil, así que todavía no puede gestionar eventos. El ámbito lo pone quien administra el aplicativo.'
			: 'Su usuario todavía no organiza eventos. Pídalo a quien administre el aplicativo.';
	}

	/**
	 * What the screen says around the table: aviso, ámbito y botón de crear.
	 *
	 * @param int  $user_id   User ID.
	 * @param bool $all_areas Whether this person works across every área.
	 * @return array<string, mixed>
	 */
	private static function chrome( int $user_id, bool $all_areas ): array {
		$crear = user_can( $user_id, 'edit_evt_events' ) ? Shell::url( 'event' ) : '';

		return array(
			'can_use'     => true,
			'notice'      => self::flash(),
			'page_id'     => self::hidden_page_id(),
			'scoped'      => ! $all_areas,
			// Con una sola área el desplegable no elige nada: siempre la misma.
			'area_filter' => $all_areas || count( EventAccess::user_areas( $user_id ) ) > 1,
			'subtitle'    => $all_areas
				? 'Todos los eventos, de todos los ámbitos.'
				: 'Solo los eventos de su ámbito y sus descendientes.',
			'can_create'  => '' !== $crear,
			'create_url'  => $crear,
		);
	}

	/**
	 * The rows this person may manage, before any filter.
	 *
	 * @param int $user_id User ID.
	 * @return array<int, array<string, mixed>>
	 */
	private static function collect( int $user_id ): array {
		$rows = array();
		foreach ( self::scope( $user_id ) as $post ) {
			// El acotado de la consulta solo esconde; quien decide es siempre
			// EventAccess. Aquí se pregunta por `can_open()` y no por
			// `can_edit()` porque un evento marcado como histórico se sigue
			// consultando: esconderlo del listado sería perderlo de vista, y
			// lo que hace falta es encontrarlo y ver por qué está cerrado.
			if ( EventAccess::can_open( $user_id, (int) $post->ID ) ) {
				$rows[] = self::row( $post );
			}
		}
		return $rows;
	}

	/**
	 * Drop the filters that are no longer among the options.
	 *
	 * Un área que ya no tiene eventos aquí no acota nada, y dejarla puesta deja
	 * la pantalla vacía sin decir por qué.
	 *
	 * @param array<string, mixed>              $s       Selection.
	 * @param array<string, array<int, string>> $options Options of each dropdown.
	 * @return array<string, mixed>
	 */
	private static function sanitise( array $s, array $options ): array {
		foreach ( array_keys( $options ) as $eje ) {
			if ( $s[ $eje ] > 0 && ! isset( $options[ $eje ][ $s[ $eje ] ] ) ) {
				$s[ $eje ] = 0;
			}
		}
		return $s;
	}

	/**
	 * Apply the dropdowns and the text search.
	 *
	 * @param array<int, array<string, mixed>> $rows Rows.
	 * @param array<string, mixed>             $s    Selection.
	 * @return array<int, array<string, mixed>>
	 */
	private static function narrow( array $rows, array $s ): array {
		return array_values(
			array_filter(
				$rows,
				static function ( array $row ) use ( $s ): bool {
					return self::in_scope( $row, $s );
				}
			)
		);
	}

	/**
	 * How many rows each state filter would leave.
	 *
	 * Del mismo conjunto que la lista: lo que queda tras área, tipo, curso y
	 * búsqueda, antes de acotar por estado.
	 *
	 * @param array<int, array<string, mixed>> $rows Rows already narrowed.
	 * @return array<string, int>
	 */
	private static function count_states( array $rows ): array {
		$counts = array_fill_keys( array_keys( self::state_filters() ), 0 );

		foreach ( $rows as $row ) {
			foreach ( array_keys( $counts ) as $filtro ) {
				if ( self::matches( $row, $filtro ) ) {
					++$counts[ $filtro ];
				}
			}
		}

		return $counts;
	}

	/**
	 * Apply the state filter.
	 *
	 * @param array<int, array<string, mixed>> $rows  Rows.
	 * @param string                           $state Filter key.
	 * @return array<int, array<string, mixed>>
	 */
	private static function by_state( array $rows, string $state ): array {
		if ( 'all' === $state ) {
			return $rows;
		}

		return array_values(
			array_filter(
				$rows,
				static function ( array $row ) use ( $state ): bool {
					return self::matches( $row, $state );
				}
			)
		);
	}

	/**
	 * How many pages each of the rows being painted has.
	 *
	 * Solo las de la página: una consulta para las veinte filas, y no una por
	 * evento.
	 *
	 * @param array<int, array<string, mixed>> $rows Rows of this page.
	 * @return array<int, array<string, mixed>>
	 */
	private static function with_sections( array $rows ): array {
		$secciones = self::section_counts( array_map( 'intval', array_column( $rows, 'id' ) ) );

		foreach ( $rows as $i => $row ) {
			$rows[ $i ]['sections'] = $secciones[ $row['id'] ] ?? 0;
		}

		return $rows;
	}

	/**
	 * The screen, from its model.
	 *
	 * @param array<string, mixed> $model What model() returned.
	 * @return string
	 */
	public static function html( array $model ): string {
		return EventListView::html( $model );
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array<string, string>|string $atts Attributes.
	 * @return string
	 */
	public static function render( $atts = array() ): string {
		unset( $atts );
		Assets::enqueue();
		return self::html( self::model() );
	}

	/**
	 * The events this person may manage, before any filter.
	 *
	 * @param int $user_id User ID.
	 * @return \WP_Post[]
	 */
	private static function scope( int $user_id ): array {
		$base = array(
			'post_type'           => EventPostType::POST_TYPE,
			// Solo las raíces: las hijas son secciones y se cuentan aparte.
			'post_parent'         => 0,
			// La papelera viene en la misma consulta y se separa después: así
			// la cifra de «Papelera (N)» no cuesta una consulta más, y el
			// listado normal la deja fuera igual que antes.
			'post_status'         => array_merge( self::STATUSES, array( self::FILTER_TRASH ) ),
			// ponytail: el ámbito entero en memoria —hoy son unas decenas de
			// eventos, y el estado derivado no se puede pedir a la base de
			// datos—; si crece, guardar el estado en meta y paginar la consulta.
			'posts_per_page'      => 200, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- el listado filtra y pagina sobre el ámbito completo.
			'orderby'             => 'date',
			'order'               => 'DESC',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		);

		if ( EventAccess::can_edit_all_areas( $user_id ) ) {
			return get_posts( $base );
		}

		$areas = EventAccess::scope_areas( $user_id );
		if ( array() === $areas ) {
			return array();
		}

		return get_posts(
			array_merge(
				$base,
				array(
					'tax_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- acotar por área es el requisito, no una mejora opcional.
						array(
							'taxonomy'         => EventTaxonomies::AREA,
							'field'            => 'term_id',
							'terms'            => $areas,
							'include_children' => false,
						),
					),
				)
			)
		);
	}

	/**
	 * One row of the table.
	 *
	 * @param \WP_Post $post Event.
	 * @return array<string, mixed>
	 */
	private static function row( \WP_Post $post ): array {
		$id     = (int) $post->ID;
		$titulo = trim( (string) $post->post_title );
		$inicio = (string) get_post_meta( $id, EventMetaKeys::START_DATE, true );
		$fin    = (string) get_post_meta( $id, EventMetaKeys::END_DATE, true );
		$titulo = '' !== $titulo ? $titulo : 'Evento sin título';

		return array(
			'id'       => $id,
			'title'    => $titulo,
			'areas'    => self::terms( $id, EventTaxonomies::AREA ),
			'types'    => self::terms( $id, EventTaxonomies::TYPE ),
			'courses'  => self::terms( $id, EventTaxonomies::COURSE ),
			'start'    => $inicio,
			'end'      => $fin,
			'state'    => EventState::of( $inicio, $fin ),
			'archived' => EventAccess::is_archived( $id ),
			'status'   => (string) $post->post_status,
			'sections' => 0,
			// Lo que está en la papelera no se abre para editarlo: primero se
			// restaura. Sin enlace, la vista lo pinta como texto.
			'url'      => self::FILTER_TRASH === $post->post_status ? '' : Shell::url( 'event', array( 'evento' => $id ) ),
			// Para la botonera: dónde se mira el evento y si quien mira puede
			// publicarlo. **Un borrador también se mira**: estando dentro y con
			// permiso, WordPress lo sirve en previsualización. Lo de la papelera
			// no: primero se restaura.
			'view_url' => self::FILTER_TRASH === $post->post_status
				? ''
				: ( 'publish' === $post->post_status ? (string) get_permalink( $id ) : (string) get_preview_post_link( $id ) ),
			'can_pub'  => EventAccess::can_publish( get_current_user_id(), $id ),
			'search'   => self::normalize( $titulo ),
		);
	}

	/**
	 * Terms of one taxonomy on one post.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $taxonomy Taxonomy.
	 * @return array<int, string> term_id => nombre.
	 */
	private static function terms( int $post_id, string $taxonomy ): array {
		$terms = get_the_terms( $post_id, $taxonomy );
		if ( ! is_array( $terms ) ) {
			return array();
		}

		$out = array();
		foreach ( $terms as $term ) {
			if ( $term instanceof \WP_Term ) {
				$out[ (int) $term->term_id ] = (string) $term->name;
			}
		}
		return $out;
	}

	/**
	 * The options of each dropdown, taken from the rows themselves.
	 *
	 * Así no se ofrece nunca un filtro que dejaría la pantalla vacía, ni se
	 * enseña el nombre de un área en la que esta persona no tiene nada.
	 *
	 * @param array<int, array<string, mixed>> $rows Rows in scope.
	 * @return array<string, array<int, string>>
	 */
	private static function options( array $rows ): array {
		$out  = array(
			'area'   => array(),
			'type'   => array(),
			'course' => array(),
		);
		$ejes = array(
			'area'   => 'areas',
			'type'   => 'types',
			'course' => 'courses',
		);

		foreach ( $rows as $row ) {
			foreach ( $ejes as $eje => $clave ) {
				foreach ( $row[ $clave ] as $term_id => $nombre ) {
					$out[ $eje ][ $term_id ] = $nombre;
				}
			}
		}

		foreach ( $out as &$opciones ) {
			asort( $opciones );
		}
		unset( $opciones );

		return $out;
	}

	/**
	 * Dropdowns and text search: everything but the state filter.
	 *
	 * @param array<string, mixed> $row Row.
	 * @param array<string, mixed> $s   Selection.
	 * @return bool
	 */
	private static function in_scope( array $row, array $s ): bool {
		$ejes = array(
			'area'   => 'areas',
			'type'   => 'types',
			'course' => 'courses',
		);

		foreach ( $ejes as $eje => $clave ) {
			if ( $s[ $eje ] > 0 && ! isset( $row[ $clave ][ $s[ $eje ] ] ) ) {
				return false;
			}
		}

		return '' === $s['search']
			|| false !== strpos( $row['search'], self::normalize( $s['search'] ) );
	}

	/**
	 * The state filter, a predicate over one row.
	 *
	 * `vivos` no es un filtro de la pantalla: es el complementario de la
	 * papelera, y lo usa {@see model()} para partir el ámbito en dos.
	 *
	 * @param array<string, mixed> $row    Row.
	 * @param string               $filter Filter key.
	 * @return bool
	 */
	private static function matches( array $row, string $filter ): bool {
		if ( 'all' === $filter ) {
			return true;
		}
		if ( self::FILTER_TRASH === $filter ) {
			return self::FILTER_TRASH === $row['status'];
		}
		if ( 'vivos' === $filter ) {
			return self::FILTER_TRASH !== $row['status'];
		}
		if ( self::FILTER_ARCHIVED === $filter ) {
			return true === $row['archived'];
		}
		if ( self::FILTER_DRAFT === $filter ) {
			return 'draft' === $row['status'];
		}
		return $filter === $row['state'];
	}

	/**
	 * Newest first; what has no dates yet is what was just created.
	 *
	 * @param array<string, mixed> $a One row.
	 * @param array<string, mixed> $b Another.
	 * @return int
	 */
	private static function compare( array $a, array $b ): int {
		$ka = '' !== $a['start'] ? $a['start'] : '9999-12-31';
		$kb = '' !== $b['start'] ? $b['start'] : '9999-12-31';

		return $ka === $kb ? strnatcasecmp( $a['title'], $b['title'] ) : strcmp( $kb, $ka );
	}

	/**
	 * How many pages each of these events has.
	 *
	 * @param int[] $ids Event IDs.
	 * @return array<int, int> post ID => secciones.
	 */
	private static function section_counts( array $ids ): array {
		if ( array() === $ids ) {
			return array();
		}

		$out   = array_fill_keys( $ids, 0 );
		$hijas = get_posts(
			array(
				'post_type'           => EventPostType::POST_TYPE,
				'post_parent__in'     => $ids,
				'post_status'         => self::STATUSES,
				'posts_per_page'      => 500, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- las secciones de una página del listado, contadas de una vez.
				'fields'              => 'id=>parent',
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
			)
		);

		foreach ( $hijas as $hija ) {
			// Según la versión, `id=>parent` devuelve el padre o el objeto.
			$padre = is_object( $hija ) ? (int) $hija->post_parent : (int) $hija;
			if ( isset( $out[ $padre ] ) ) {
				++$out[ $padre ];
			}
		}

		return $out;
	}

	/**
	 * Whether anything is narrowing the list right now.
	 *
	 * @param array<string, mixed> $s Selection.
	 * @return bool
	 */
	private static function is_filtered( array $s ): bool {
		return $s['area'] > 0 || $s['type'] > 0 || $s['course'] > 0
			|| 'all' !== $s['state'] || '' !== $s['search'];
	}

	/**
	 * A selection with nothing narrowing it.
	 *
	 * @return array<string, mixed>
	 */
	private static function no_filters(): array {
		return array(
			'area'   => 0,
			'type'   => 0,
			'course' => 0,
			'state'  => 'all',
			'search' => '',
			'page'   => 1,
		);
	}

	/**
	 * What to say when the table has no rows.
	 *
	 * @param bool $filtered   Whether filters are narrowing the list.
	 * @param bool $all_areas  Whether this person works across every área.
	 * @return string
	 */
	private static function empty_text( bool $filtered, bool $all_areas ): string {
		if ( $filtered ) {
			return 'Ningún evento coincide con lo que ha pedido. Pruebe a quitar algún filtro o a buscar otra cosa.';
		}
		return $all_areas
			? 'Todavía no hay ningún evento. Cree el primero con «Crear evento».'
			: 'Todavía no hay ningún evento de su ámbito. Cree el primero con «Crear evento».';
	}

	/**
	 * What another screen left said on the way here.
	 *
	 * @return array{type:string, text:string}
	 */
	private static function flash(): array {
		$avisos = array(
			'creado'       => array( 'ok', 'Evento creado. Ya puede añadirle secciones.' ),
			'guardado'     => array( 'ok', 'Cambios guardados.' ),
			'borrado'      => array( 'ok', 'Evento enviado a la papelera. Nada se ha perdido: está en «Papelera» y se restaura desde ahí.' ),
			'restaurado'   => array( 'ok', 'Evento restaurado, en borrador: revíselo y publíquelo cuando esté listo.' ),
			'publicado'    => array( 'ok', 'Evento publicado: ya se ve en la web. Sus páginas se publican cada una desde el taller.' ),
			'despublicado' => array( 'ok', 'Evento devuelto a borrador: deja de verse en la web y no se pierde nada.' ),
			'permiso'      => array( 'error', 'Ese evento es de otro ámbito: solo lo edita su ámbito o quien administra el aplicativo.' ),
		);

		$aviso = $avisos[ self::input( self::VAR_NOTICE ) ] ?? array( '', '' );

		return array(
			'type' => $aviso[0],
			'text' => $aviso[1],
		);
	}

	/**
	 * The page ID the filter form has to carry, or zero.
	 *
	 * Con enlaces permanentes sencillos la dirección de la página es
	 * `?page_id=12`, y un formulario `GET` sustituye la cadena de consulta
	 * entera: sin este campo oculto, filtrar llevaría a la portada del sitio.
	 *
	 * @return int
	 */
	private static function hidden_page_id(): int {
		return '' === (string) get_option( 'permalink_structure' ) ? (int) get_queried_object_id() : 0;
	}

	/**
	 * Normalise text so accents and capitals do not decide a search.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	private static function normalize( string $text ): string {
		return mb_strtolower( remove_accents( $text ), 'UTF-8' );
	}
}
