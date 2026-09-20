<?php
/**
 * The workshop of one event: its sections, its data and its appearance.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

use Evt\Access\EventAccess;
use Evt\Domain\ActivityInput;
use Evt\Domain\EventInput;
use Evt\Domain\EventState;
use Evt\Domain\SignupQuestions;
use Evt\Meta\EventMetaKeys;
use Evt\Meta\ProgrammeMetaKeys;
use Evt\Meta\RegistrationMetaKeys;
use Evt\PostType\ActivityPostType;
use Evt\PostType\EventPostType;
use Evt\PostType\SpeakerPostType;
use Evt\PublicFront\View\EventWorkspaceView;
use Evt\Taxonomy\EventTaxonomies;

/**
 * Shortcode [evt_event_workspace]: el taller de un evento.
 *
 * Es la pantalla del encargo. Hoy, editar una página de un evento son 139
 * campos en un solo formulario de 5.671 píxeles de alto, el mismo para la
 * portada y para cada satélite, y no hay ninguna pantalla que liste las
 * secciones de un evento ni que permita ordenarlas. Aquí eso son tres
 * pestañas internas (`?panel=secciones|datos|apariencia`) y cada una enseña
 * lo suyo y nada más.
 *
 * Todo lo que muta va por POST con su nonce propio y con
 * {@see EventAccess::can_edit()} comprobado, y vuelve a la misma pestaña con
 * el aviso: nunca se muta en GET, que es lo que hace que un enlace pegado en
 * un correo borre una sección.
 */
final class EventWorkspace {

	public const SHORTCODE = 'evt_event_workspace';

	/**
	 * Query arg carrying the event this workshop is about.
	 */
	public const ARG_EVENT = 'evento';

	/**
	 * Query arg carrying the inner tab.
	 */
	public const ARG_PANEL = 'panel';

	/**
	 * Query arg carrying a satellite page, for the section form.
	 */
	public const ARG_SECTION = 'seccion';

	/**
	 * Query arg carrying the type of a new satellite page.
	 */
	public const ARG_TYPE = 'tipo';

	/**
	 * Query arg that asks for the trash of the sections table.
	 */
	public const ARG_TRASH = 'papelera';

	/**
	 * Tab listing the satellite pages of the event.
	 */
	public const PANEL_SECTIONS = 'secciones';

	/**
	 * Tab with what the event is: identity, dates, classification, sign-up.
	 */
	public const PANEL_SETTINGS = 'ajustes';

	/**
	 * Tab with how the event looks: colours, typefaces, images.
	 */
	public const PANEL_LOOK = 'apariencia';

	/**
	 * Tab with the speakers of the event.
	 */
	public const PANEL_SPEAKERS = 'ponentes';

	/**
	 * Tab with the programme: the parrilla of activities.
	 */
	public const PANEL_PROGRAMME = 'programa';

	/**
	 * Tab with the activities that take enrolment.
	 */
	public const PANEL_WORKSHOPS = 'talleres';

	/**
	 * Tab with who signed up.
	 */
	public const PANEL_PEOPLE = 'participantes';

	/**
	 * Tab where the signup form of the event is set up (ADR-0031, ADR-0032).
	 */
	public const PANEL_SIGNUP = 'inscripcion';

	/**
	 * Query arg carrying the speaker or activity being edited.
	 */
	public const ARG_ROW = 'ficha';

	/**
	 * Query arg carrying what was typed in the participants filter.
	 */
	public const ARG_Q = 'busca';

	/**
	 * Query arg narrowing the participants to one workshop.
	 */
	public const ARG_WORKSHOP = 'taller';

	/**
	 * Tab with the custom CSS and JavaScript of the event.
	 *
	 * La ve también el área, que es quien escribe el CSS de su evento; lo que
	 * cambia es qué hay dentro. No sale para todo el mundo: {@see panels()}
	 * solo la pone a quien tiene alguna de las dos capacidades de código.
	 */
	public const PANEL_CODE = 'codigo';

	/**
	 * Hidden field with the speaker or activity a POST is about.
	 */
	public const FIELD_ROW = 'evt_row';

	/**
	 * Hidden field naming the operation a POST asks for.
	 */
	public const FIELD_DO = 'evt_do';

	/**
	 * Hidden field with the event a POST is about.
	 */
	public const FIELD_EVENT = 'evt_event';

	/**
	 * Hidden field with the satellite page a row action is about.
	 */
	public const FIELD_SECTION = 'evt_section';

	/**
	 * Field carrying the event title in the data panel.
	 */
	public const FIELD_TITLE = 'evt_title';

	/**
	 * Field carrying the área term in the data panel.
	 */
	public const FIELD_AREA = 'evt_area_term';

	/**
	 * Field carrying the tipología term in the data panel.
	 */
	public const FIELD_TYPE = 'evt_type_term';

	/**
	 * Field carrying the curso escolar term in the data panel.
	 */
	public const FIELD_COURSE = 'evt_course_term';

	/**
	 * Operation that closes an event for good: the «histórico» mark.
	 */
	public const OP_ARCHIVE = 'archive';

	/**
	 * Operation that opens it again.
	 */
	public const OP_UNARCHIVE = 'unarchive';

	/**
	 * Operations that act on one row of the sections table.
	 *
	 * @var string[]
	 */
	private const ROW_OPS = array( 'up', 'down', 'publish', 'unpublish', 'delete', 'restore' );

	/**
	 * Operation that saves one speaker.
	 */
	public const OP_SPEAKER = 'speaker';

	/**
	 * Operation that saves one activity.
	 */
	public const OP_ACTIVITY = 'activity';

	/**
	 * Operation that exports the participants as CSV.
	 */
	public const OP_EXPORT = 'export';

	/**
	 * Operations that act on one speaker or activity.
	 *
	 * Van aparte de `ROW_OPS` porque no son de la tabla de secciones y porque
	 * la fila que mueven viene en otro campo: `FIELD_ROW`, no `FIELD_SECTION`.
	 *
	 * @var string[]
	 */
	private const PROGRAMME_OPS = array( 'sp_up', 'sp_down', 'row_delete', 'row_restore' );

	/**
	 * Every operation this screen accepts by POST.
	 *
	 * @var string[]
	 */
	private const OPS = array(
		self::PANEL_SETTINGS,
		self::PANEL_SIGNUP,
		self::PANEL_LOOK,
		self::PANEL_CODE,
		self::OP_ARCHIVE,
		self::OP_UNARCHIVE,
		'up',
		'down',
		'publish',
		'unpublish',
		'delete',
		'restore',
		self::OP_SPEAKER,
		self::OP_ACTIVITY,
		self::OP_EXPORT,
		'sp_up',
		'sp_down',
		'row_delete',
		'row_restore',
	);

	/**
	 * What each row action says when it worked.
	 *
	 * @var array<string, string>
	 */
	private const ROW_DONE = array(
		'up'        => 'Cambiado el orden de las secciones.',
		'down'      => 'Cambiado el orden de las secciones.',
		'delete'    => 'Sección enviada a la papelera. Nada se ha perdido: está en «Papelera» y se restaura desde ahí.',
		'restore'   => 'Sección restaurada, en borrador: revísela y publíquela cuando esté lista.',
		'publish'   => 'Sección publicada: ya se ve en el menú del evento.',
		'unpublish' => 'Sección despublicada: vuelve a borrador y desaparece del menú.',
	);

	/**
	 * Image types the appearance panel accepts.
	 *
	 * @var array<string, string>
	 */
	private const IMAGE_MIMES = array(
		'jpg|jpeg|jpe' => 'image/jpeg',
		'png'          => 'image/png',
		'webp'         => 'image/webp',
		'gif'          => 'image/gif',
	);

	/**
	 * Register the shortcode and the POST handler.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'render' ) );
		// Después de que el CPT y sus capacidades estén registrados (init 10 y
		// 11), y antes de que se pinte nada: aquí todavía se puede redirigir.
		add_action( 'init', array( self::class, 'handle' ), 20 );
		// «Tomar posesión» sale de las dos pantallas que se bloquean —el taller
		// y el formulario de una sección—, así que se engancha una sola vez y
		// aquí, donde ya cuelga el resto de las mutaciones del evento.
		add_action( 'init', array( EditLock::class, 'handle' ), 20 );
		// El editor de código se pide aquí y no al pintar: el shortcode corre
		// dentro de `the_content`, cuando `wp_head()` ya se escribió, y la hoja
		// de CodeMirror encolada entonces no llegaría a imprimirse.
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_code_editor' ), 20 );
	}

	/**
	 * Load CodeMirror when this request really is the «Código» tab.
	 *
	 * @return void
	 */
	public static function enqueue_code_editor(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- lectura de la pestaña; mutar lleva su nonce.
		if ( 'event' !== Shell::current_section()
			|| self::PANEL_CODE !== sanitize_key( wp_unslash( (string) ( $_GET[ self::ARG_PANEL ] ?? '' ) ) ) ) {
			return;
		}
		$event_id = absint( wp_unslash( $_GET[ self::ARG_EVENT ] ?? 0 ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$user_id = get_current_user_id();
		if ( EventAccess::can_edit_custom_css( $user_id, $event_id ) ) {
			CodeEditor::enqueue( CodeEditor::MODE_CSS );
		}
		if ( EventAccess::can_edit_custom_js( $user_id, $event_id ) ) {
			CodeEditor::enqueue( CodeEditor::MODE_JS );
		}
	}

	/*
	 * -----------------------------------------------------------------------
	 * Direcciones
	 * -----------------------------------------------------------------------
	 */

	/**
	 * URL of the workshop of one event, on one of its tabs.
	 *
	 * @param int                  $event_id Event post ID.
	 * @param string               $panel    Tab slug; empty for the default one.
	 * @param array<string, mixed> $args     Extra query arguments.
	 * @return string Empty when there is no event or no page for the screen.
	 */
	public static function url( int $event_id, string $panel = '', array $args = array() ): string {
		if ( $event_id <= 0 ) {
			return '';
		}
		$args[ self::ARG_EVENT ] = $event_id;
		if ( '' !== $panel ) {
			$args[ self::ARG_PANEL ] = $panel;
		}
		return Shell::url( 'event', $args );
	}

	/**
	 * The three inner tabs, with where each one goes.
	 *
	 * La de «Código» no está para todo el mundo: quien no puede escribir ni el
	 * CSS ni el JavaScript no la ve, y como {@see fill()} decide la pestaña
	 * activa mirando esta lista, tampoco la abre escribiendo la dirección a
	 * mano —cae en «Secciones»—.
	 *
	 * @param int $event_id Event post ID.
	 * @param int $user_id  Who is looking; 0 leaves out anything conditional.
	 * @return array<string, array{label:string, url:string}>
	 */
	public static function panels( int $event_id, int $user_id = 0 ): array {
		$rotulos = array(
			self::PANEL_SECTIONS  => 'Páginas',
			self::PANEL_SPEAKERS  => 'Ponentes',
			self::PANEL_PROGRAMME => 'Programa',
			self::PANEL_WORKSHOPS => 'Talleres',
			self::PANEL_SIGNUP    => 'Inscripción',
			self::PANEL_PEOPLE    => 'Participantes',
			self::PANEL_SETTINGS  => 'Ajustes',
			self::PANEL_LOOK      => 'Apariencia',
		);
		if ( self::may_edit_code( $user_id, $event_id ) ) {
			$rotulos[ self::PANEL_CODE ] = 'Código';
		}

		// El recuento va en la pestaña porque es lo que contesta de un vistazo
		// «¿le falta algo a este evento?», que es la pregunta con la que se
		// abre esta pantalla. Cero se pinta igual que cualquier otro número:
		// esconderlo dejaría la pestaña indistinguible de una sin datos.
		$cuentas = self::counts( $event_id );

		$out = array();
		foreach ( $rotulos as $slug => $rotulo ) {
			$out[ $slug ] = array(
				'label' => $rotulo,
				'url'   => self::url( $event_id, $slug ),
				'count' => $cuentas[ $slug ] ?? null,
			);
		}
		return $out;
	}

	/**
	 * How many things each tab has, for the little number beside its name.
	 *
	 * @param int $event_id Event post ID.
	 * @return array<string, int>
	 */
	private static function counts( int $event_id ): array {
		if ( $event_id <= 0 ) {
			return array();
		}
		return array(
			self::PANEL_SECTIONS  => count( self::children( $event_id ) ),
			self::PANEL_SPEAKERS  => count( Programme::speakers( $event_id ) ),
			self::PANEL_PROGRAMME => count( Programme::activities( $event_id ) ),
			self::PANEL_WORKSHOPS => count( Programme::workshops( $event_id ) ),
			self::PANEL_SIGNUP    => count( Registrations::questions( $event_id ) ),
			self::PANEL_PEOPLE    => count( Participants::rows( $event_id ) ),
		);
	}

	/**
	 * Name of the nonce field of one operation.
	 *
	 * Un nonce por acción y, en las acciones de fila, por fila: así el
	 * identificador que escribe `wp_nonce_field()` no se repite en la página
	 * y cada botón lleva el suyo.
	 *
	 * @param string $op         Operation.
	 * @param int    $section_id Satellite page, 0 outside the row actions.
	 * @return string
	 */
	public static function nonce_name( string $op, int $section_id = 0 ): string {
		return 'evt_nonce_' . $op . '_' . $section_id;
	}

	/**
	 * Nonce action of one operation.
	 *
	 * @param string $op Operation.
	 * @return string
	 */
	public static function nonce_action( string $op ): string {
		return 'evt_ws_' . $op;
	}

	/*
	 * -----------------------------------------------------------------------
	 * Las mutaciones
	 * -----------------------------------------------------------------------
	 */

	/**
	 * Apply what a POST asked for, then go back to the same tab.
	 *
	 * Se mira `$_POST` y nada más: un `GET` no lo rellena, así que no hay
	 * forma de disparar esto desde un enlace pegado en un correo.
	 *
	 * @return void
	 */
	public static function handle(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- se comprueba en verify(), en cuanto se sabe qué acción es.
		$op = sanitize_key( wp_unslash( (string) ( $_POST[ self::FIELD_DO ] ?? '' ) ) );
		if ( ! in_array( $op, self::OPS, true ) ) {
			return;
		}
		$event_id   = absint( wp_unslash( $_POST[ self::FIELD_EVENT ] ?? 0 ) );
		$section_id = absint( wp_unslash( $_POST[ self::FIELD_SECTION ] ?? 0 ) );
		$row_id     = absint( wp_unslash( $_POST[ self::FIELD_ROW ] ?? 0 ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$fila     = in_array( $op, self::ROW_OPS, true );
		$programa = in_array( $op, self::PROGRAMME_OPS, true );
		// El nonce de una acción de fila lleva dentro la fila, para que dos
		// botones de la misma pantalla no compartan identificador.
		$nonce_row = 0;
		if ( $fila ) {
			$nonce_row = $section_id;
		} elseif ( $programa ) {
			$nonce_row = $row_id;
		}
		if ( ! self::verify( $op, $nonce_row ) ) {
			return;
		}

		$user_id = get_current_user_id();

		// Crear un evento va lo primero, antes de todo lo que da por hecho que
		// el evento existe: sin esto, el botón «Crear evento» del listado lleva
		// a una pantalla que dice «elija un evento en la lista», que es la
		// pescadilla mordiéndose la cola.
		if ( self::PANEL_SETTINGS === $op && $event_id <= 0 ) {
			self::create_event( $user_id );
			return;
		}

		$destino = self::url( $event_id, self::panel_of( $op ) );
		if ( '' === $destino ) {
			$destino = Shell::back_url();
		}

		// Lo primero de todo, antes de escribir una sola meta, un término o un
		// estado: si otra persona tiene abierto el evento, este envío trae la
		// pantalla de antes y pisaría lo que esté escribiendo. Responde 409 y
		// no vuelve. Vale también para la marca de histórico, que es una
		// escritura más.
		EditLock::require_available( $event_id );

		// Antes de la comprobación de edición y no después, y esto es lo que
		// evita que la comprobación se muerda la cola: marcar como histórico
		// es lo último que hace un área con su evento, y en cuanto la marca
		// está puesta `can_edit()` dice que no. Si esto fuera detrás, marcar
		// sería imposible y desmarcar también.
		if ( self::OP_ARCHIVE === $op || self::OP_UNARCHIVE === $op ) {
			self::save_archived( $op, $event_id, $user_id, $destino );
			return;
		}

		if ( ! EventAccess::can_edit( $user_id, $event_id ) ) {
			self::set_flash( 'error', EventAccess::why_not_editable( $user_id, $event_id ) );
			Shell::leave( $destino );
			return;
		}

		if ( $fila ) {
			self::run_row( $op, $event_id, $section_id, $user_id, $destino );
			return;
		}
		if ( $programa ) {
			self::run_programme_row( $op, $event_id, $row_id, $destino );
			return;
		}
		if ( self::OP_SPEAKER === $op ) {
			self::save_speaker( $event_id, $row_id, $destino );
			return;
		}
		if ( self::OP_ACTIVITY === $op ) {
			self::save_activity( $event_id, $row_id, $destino );
			return;
		}
		if ( self::OP_EXPORT === $op ) {
			self::export_participants( $event_id );
			return;
		}
		if ( self::PANEL_SETTINGS === $op ) {
			self::save_data( $event_id, $user_id, $destino );
			return;
		}
		if ( self::PANEL_SIGNUP === $op ) {
			self::save_signup( $event_id, $destino );
			return;
		}
		if ( self::PANEL_CODE === $op ) {
			self::save_code( $event_id, $user_id, $destino );
			return;
		}
		self::save_look( $event_id, $destino );
	}

	/**
	 * Which tab an operation goes back to.
	 *
	 * @param string $op Operation.
	 * @return string
	 */
	private static function panel_of( string $op ): string {
		if ( in_array( $op, self::ROW_OPS, true ) ) {
			return self::PANEL_SECTIONS;
		}
		if ( self::OP_ARCHIVE === $op || self::OP_UNARCHIVE === $op ) {
			return self::PANEL_SETTINGS;
		}
		if ( self::OP_SPEAKER === $op || 'sp_up' === $op || 'sp_down' === $op ) {
			return self::PANEL_SPEAKERS;
		}
		if ( self::OP_ACTIVITY === $op ) {
			return self::PANEL_PROGRAMME;
		}
		if ( self::OP_EXPORT === $op ) {
			return self::PANEL_PEOPLE;
		}
		// Borrar y restaurar una ficha vuelven a donde se pulsó, que puede ser
		// Ponentes o Programa: el panel viaja en el propio envío.
		if ( 'row_delete' === $op || 'row_restore' === $op ) {
			return self::asked_panel();
		}
		return $op;
	}

	/**
	 * The tab a row action was pressed on.
	 *
	 * @return string
	 */
	private static function asked_panel(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- se comprueba en verify(); esto solo elige a dónde volver.
		$panel = sanitize_key( wp_unslash( (string) ( $_POST[ self::ARG_PANEL ] ?? '' ) ) );
		return in_array( $panel, array( self::PANEL_SPEAKERS, self::PANEL_PROGRAMME, self::PANEL_WORKSHOPS ), true )
			? $panel
			: self::PANEL_SPEAKERS;
	}

	/**
	 * Mark or unmark the event as «histórico».
	 *
	 * Las dos mitades no piden lo mismo, y esa es la regla: marcar lo hace el
	 * área que organiza el evento; desmarcar, solo administración.
	 *
	 * @param string $op       archive | unarchive.
	 * @param int    $event_id Event post ID.
	 * @param int    $user_id  Who is asking.
	 * @param string $destino  Where to go back to.
	 * @return void
	 */
	private static function save_archived( string $op, int $event_id, int $user_id, string $destino ): void {
		$marcar = self::OP_ARCHIVE === $op;
		$puede  = $marcar
			? EventAccess::can_archive( $user_id, $event_id )
			: EventAccess::can_unarchive( $user_id );
		if ( ! $puede ) {
			self::set_flash(
				'error',
				$marcar
					? 'Este evento no es suyo: solo lo marca como histórico el ámbito que lo organiza.'
					: 'Volver a abrir un evento histórico solo lo hace quien administra el aplicativo.'
			);
			Shell::leave( $destino );
			return;
		}
		if ( EventPostType::POST_TYPE !== get_post_type( $event_id )
			|| (int) get_post_field( 'post_parent', $event_id ) > 0 ) {
			self::set_flash( 'error', 'Eso no es un evento: la marca de histórico se pone en el evento entero, no en una de sus páginas.' );
			Shell::leave( $destino );
			return;
		}

		update_post_meta( $event_id, EventMetaKeys::ARCHIVED, $marcar );

		// Cerrado el evento, aquí ya no queda nada que editar: se suelta el
		// bloqueo —solo el propio— para que administración pueda entrar a
		// corregir una errata sin esperar a que caduque.
		if ( $marcar ) {
			EditLock::release( $event_id );
		}

		self::set_flash(
			'ok',
			$marcar
				? 'Evento marcado como histórico. Ya no se edita, ni él ni sus secciones; la página pública se sigue viendo igual. Para volver a abrirlo hay que pedírselo a quien administre el aplicativo.'
				: 'Evento desmarcado: su ámbito vuelve a poder editarlo.'
		);
		Shell::leave( $destino );
	}

	/**
	 * Whether the POST carries the nonce of this very operation.
	 *
	 * @param string $op         Operation.
	 * @param int    $section_id Satellite page, for row actions.
	 * @return bool
	 */
	private static function verify( string $op, int $section_id ): bool {
		$campo = self::nonce_name( $op, $section_id );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- esto es la comprobación del nonce.
		$valor = sanitize_text_field( wp_unslash( (string) ( $_POST[ $campo ] ?? '' ) ) );
		return false !== wp_verify_nonce( $valor, self::nonce_action( $op ) );
	}

	/**
	 * One of the row actions of the sections table.
	 *
	 * @param string $op         up | down | publish | unpublish | delete | restore.
	 * @param int    $event_id   Event post ID.
	 * @param int    $section_id Satellite page post ID.
	 * @param int    $user_id    Who is asking.
	 * @param string $destino    Where to go back to.
	 * @return void
	 */
	private static function run_row( string $op, int $event_id, int $section_id, int $user_id, string $destino ): void {
		if ( EventPostType::POST_TYPE !== get_post_type( $section_id )
			|| (int) get_post_field( 'post_parent', $section_id ) !== $event_id ) {
			self::set_flash( 'error', 'Esa sección no es de este evento.' );
			Shell::leave( $destino );
			return;
		}

		if ( 'delete' === $op ) {
			// A la papelera y no borrada del todo: una sección con contenido
			// escrito se restaura desde «Papelera» si el clic fue un error. El
			// borrado definitivo no lo hace el aplicativo: es del escritorio.
			wp_trash_post( $section_id );
		} elseif ( 'restore' === $op ) {
			// Vuelve en borrador, y eso es a propósito: es lo que hace
			// `wp_untrash_post()` desde WordPress 5.6 y es lo que aquí se
			// quiere. Una sección que se borró por error no tiene por qué
			// reaparecer publicada en el menú del evento sin que nadie la mire;
			// publicarla es un clic más, en su botón de siempre.
			wp_untrash_post( $section_id );
		} elseif ( 'up' === $op || 'down' === $op ) {
			self::reorder( $event_id, $section_id, 'up' === $op ? -1 : 1 );
		} elseif ( ! EventAccess::can_publish( $user_id, $event_id ) ) {
			self::set_flash( 'error', 'Su perfil no publica eventos: puede editar la sección, pero no publicarla.' );
			Shell::leave( $destino );
			return;
		} else {
			wp_update_post(
				array(
					'ID'          => $section_id,
					'post_status' => 'publish' === $op ? 'publish' : 'draft',
				)
			);
		}

		self::set_flash( 'ok', self::ROW_DONE[ $op ] );
		Shell::leave( $destino );
	}

	/**
	 * Move one satellite page one place up or down.
	 *
	 * Se renumera la lista entera en vez de intercambiar dos `menu_order`: las
	 * páginas heredadas del sistema anterior traen muchas el mismo número —o
	 * ninguno—, así que un intercambio dejaría el orden igual que estaba. Son
	 * un puñado de filas por evento.
	 *
	 * @param int $event_id   Event post ID.
	 * @param int $section_id Satellite page post ID.
	 * @param int $delta      -1 to move up, 1 to move down.
	 * @return void
	 */
	private static function reorder( int $event_id, int $section_id, int $delta ): void {
		$ids   = array_map( 'intval', wp_list_pluck( self::children( $event_id ), 'ID' ) );
		$desde = array_search( $section_id, $ids, true );
		if ( false === $desde ) {
			return;
		}
		$hasta = (int) $desde + $delta;
		if ( $hasta < 0 || $hasta >= count( $ids ) ) {
			return;
		}

		$movida        = $ids[ $desde ];
		$ids[ $desde ] = $ids[ $hasta ];
		$ids[ $hasta ] = $movida;

		foreach ( $ids as $posicion => $id ) {
			wp_update_post(
				array(
					'ID'         => $id,
					'menu_order' => ( $posicion + 1 ) * 10,
				)
			);
		}
	}

	/**
	 * One of the row actions of the speakers table or the parrilla.
	 *
	 * @param string $op       sp_up | sp_down | row_delete | row_restore.
	 * @param int    $event_id Event post ID.
	 * @param int    $row_id   Speaker or activity post ID.
	 * @param string $destino  Where to go back to.
	 * @return void
	 */
	private static function run_programme_row( string $op, int $event_id, int $row_id, string $destino ): void {
		$hecho = false;
		if ( 'sp_up' === $op || 'sp_down' === $op ) {
			$hecho = Programme::reorder_speaker( $event_id, $row_id, 'sp_up' === $op ? -1 : 1 );
		} elseif ( 'row_delete' === $op ) {
			$hecho = Programme::trash( $event_id, $row_id );
		} elseif ( 'row_restore' === $op ) {
			$hecho = Programme::restore( $event_id, $row_id );
		}

		if ( ! $hecho ) {
			self::set_flash( 'error', 'Esa ficha no es de este evento, o ya no está donde se esperaba.' );
			Shell::leave( $destino );
			return;
		}

		$dichos = array(
			'sp_up'       => 'Cambiado el orden de los ponentes.',
			'sp_down'     => 'Cambiado el orden de los ponentes.',
			'row_delete'  => 'Ficha enviada a la papelera. Nada se ha perdido: está en «Papelera» y se restaura desde ahí.',
			'row_restore' => 'Ficha restaurada.',
		);
		self::set_flash( 'ok', $dichos[ $op ] );
		Shell::leave( $destino );
	}

	/**
	 * Save one speaker of this event.
	 *
	 * @param int    $event_id Event post ID.
	 * @param int    $row_id   Speaker post ID; 0 to create.
	 * @param string $destino  Where to go back to.
	 * @return void
	 */
	private static function save_speaker( int $event_id, int $row_id, string $destino ): void {
		$check = ActivityInput::speaker(
			array(
				'name' => self::field( 'evt_sp_name' ),
				'role' => self::field( 'evt_sp_role' ),
				'org'  => self::field( 'evt_sp_org' ),
				'bio'  => self::field( 'evt_sp_bio' ),
			)
		);
		if ( true !== $check['ok'] ) {
			self::set_flash( 'error', ActivityInput::why( (array) $check['errors'] ) );
			Shell::leave( $destino );
			return;
		}

		$id = Programme::save_speaker( $event_id, $row_id, (array) $check['data'] );
		if ( $id <= 0 ) {
			self::set_flash( 'error', 'No se ha podido guardar el ponente: esa ficha no es de este evento.' );
			Shell::leave( $destino );
			return;
		}

		self::save_photo( $id );
		self::set_flash( 'ok', $row_id > 0 ? 'Ponente actualizado.' : 'Ponente añadido.' );
		Shell::leave( $destino );
	}

	/**
	 * Save one activity of this event.
	 *
	 * @param int    $event_id Event post ID.
	 * @param int    $row_id   Activity post ID; 0 to create.
	 * @param string $destino  Where to go back to.
	 * @return void
	 */
	private static function save_activity( int $event_id, int $row_id, string $destino ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- el nonce se comprobó en handle() y cada elemento pasa por absint() en la línea siguiente.
		$ponentes = isset( $_POST['evt_ac_speakers'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['evt_ac_speakers'] ) ) : array();

		$check = ActivityInput::activity(
			array(
				'title'    => self::field( 'evt_ac_title' ),
				'kind'     => self::field( 'evt_ac_kind' ),
				'date'     => self::field( 'evt_ac_date' ),
				'start'    => self::field( 'evt_ac_start' ),
				'end'      => self::field( 'evt_ac_end' ),
				'venue'    => self::field( 'evt_ac_venue' ),
				'room'     => self::field( 'evt_ac_room' ),
				'seats'    => self::field( 'evt_ac_seats' ),
				'summary'  => self::field( 'evt_ac_summary' ),
				'speakers' => $ponentes,
			)
		);
		if ( true !== $check['ok'] ) {
			self::set_flash( 'error', ActivityInput::why( (array) $check['errors'] ) );
			Shell::leave( $destino );
			return;
		}

		$id = Programme::save_activity( $event_id, $row_id, (array) $check['data'] );
		if ( $id <= 0 ) {
			self::set_flash( 'error', 'No se ha podido guardar la actividad: esa ficha no es de este evento.' );
			Shell::leave( $destino );
			return;
		}

		self::set_flash( 'ok', $row_id > 0 ? 'Actividad actualizada.' : 'Actividad añadida al programa.' );
		Shell::leave( $destino );
	}

	/**
	 * Attach, replace or remove the photo of a speaker.
	 *
	 * Se reutiliza el mismo camino que la portada del evento: un identificador
	 * de la biblioteca, comprobado que es una imagen.
	 *
	 * @param int $speaker_id Speaker post ID.
	 * @return void
	 */
	private static function save_photo( int $speaker_id ): void {
		$pedido = self::field( 'evt_sp_photo' );
		if ( '' === $pedido ) {
			return;
		}
		if ( '0' === $pedido ) {
			delete_post_thumbnail( $speaker_id );
			return;
		}
		$attachment_id = absint( $pedido );
		if ( $attachment_id > 0 && self::is_image_attachment( $attachment_id ) ) {
			set_post_thumbnail( $speaker_id, $attachment_id );
		}
	}

	/**
	 * Send the participants of this event down as a CSV file.
	 *
	 * Va por POST y con nonce como cualquier otra acción, aunque no escriba
	 * nada: un enlace en GET que descarga la lista entera de personas
	 * inscritas es justo lo que no se quiere que se pueda pegar en un correo.
	 *
	 * @param int $event_id Event post ID.
	 * @return void
	 */
	private static function export_participants( int $event_id ): void {
		$filas = Participants::filter(
			Participants::rows( $event_id ),
			self::field( self::ARG_Q ),
			self::field( self::ARG_WORKSHOP )
		);

		$cuerpo = Participants::csv( $filas );
		$nombre = Participants::filename( (string) get_post_field( 'post_title', $event_id ) );

		// `Shell::leave()` no vale aquí: esto no redirige, escribe el fichero.
		if ( ! headers_sent() ) {
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="' . $nombre . '"' );
			header( 'Content-Length: ' . strlen( $cuerpo ) );
			header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		}
		echo $cuerpo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- es un CSV, no HTML; Participants::csv() lo entrecomilla y desactiva las fórmulas.
		// Sin URL: esto no redirige, ya ha escrito el fichero.
		Shell::leave();
	}

	/**
	 * Create a brand new event from the «Datos» form, then open its workshop.
	 *
	 * La capacidad que se pide es la misma con la que el listado decide pintar
	 * el botón: `edit_evt_events`. No se pregunta por `can_edit()` porque
	 * todavía no hay nada que editar, y el acotado por área llega en cuanto el
	 * evento existe — {@see EventAccess::stamp_area()} le pone el área de quien
	 * lo crea si no eligió ninguna.
	 *
	 * Nace en **borrador**: un evento recién creado no tiene programa ni
	 * ponentes, y publicarlo por el mero hecho de crearlo es publicar una
	 * página vacía.
	 *
	 * @param int $user_id Who is asking.
	 * @return void
	 */
	private static function create_event( int $user_id ): void {
		$volver = Shell::url( 'events' );

		if ( ! user_can( $user_id, 'edit_evt_events' ) ) {
			self::set_flash( 'error', 'Su perfil no puede crear eventos. Pídalo a quien administre el aplicativo.' );
			Shell::leave( $volver );
			return;
		}

		$crudo = array(
			'title'      => self::field( self::FIELD_TITLE ),
			'start_date' => self::field( EventMetaKeys::START_DATE ),
			'end_date'   => self::field( EventMetaKeys::END_DATE ),
			'venue'      => self::field( EventMetaKeys::VENUE ),
			'parent'     => 0,
		);

		$revisado = EventInput::validate( $crudo );
		if ( ! $revisado['ok'] ) {
			// Lo tecleado vuelve con el aviso, igual que al editar: perder el
			// formulario entero por una fecha mal escrita es la forma más
			// rápida de que no se vuelva a intentar.
			self::set_flash( 'error', self::why( $revisado['errors'] ), self::submitted_values( $crudo ) );
			Shell::leave( self::url( 0, self::PANEL_SETTINGS ) );
			return;
		}

		$valores = self::submitted_values( $crudo );
		if ( '' === $valores[ self::FIELD_AREA ] ) {
			$valores[ self::FIELD_AREA ] = implode( ',', EventAccess::user_areas( $user_id ) );
		}
		$area_ids = EventAccess::resolve_area_assignment( 0, '' === $valores[ self::FIELD_AREA ] ? array() : explode( ',', $valores[ self::FIELD_AREA ] ), $user_id );
		if ( is_wp_error( $area_ids ) ) {
			self::set_flash( 'error', $area_ids->get_error_message(), $valores );
			Shell::leave( self::url( 0, self::PANEL_SETTINGS ) );
			return;
		}

		$id = wp_insert_post(
			array(
				'post_type'   => EventPostType::POST_TYPE,
				'post_title'  => $revisado['data']['title'],
				'post_status' => 'draft',
				'post_parent' => 0,
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			self::set_flash( 'error', 'No se ha podido crear el evento: ' . $id->get_error_message() );
			Shell::leave( $volver );
			return;
		}

		$id = (int) $id;
		self::save_meta( $id, $valores, $revisado['data'] );
		self::save_terms( $id, $area_ids, $valores );
		EventAccess::stamp_area( $id );

		self::set_flash( 'ok', 'Evento creado, en borrador. Añada sus páginas, sus ponentes y su programa, y publíquelo cuando esté listo.' );
		Shell::leave( self::url( $id, self::PANEL_SECTIONS ) );
	}

	/**
	 * Everything the «Datos» form sent, in the shape the flash and save use.
	 *
	 * @param array<string, mixed> $crudo What EventInput was given.
	 * @return array<string, string>
	 */
	private static function submitted_values( array $crudo ): array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- el nonce lo comprobó handle().
		$intro = wp_kses_post( wp_unslash( (string) ( $_POST[ EventMetaKeys::INTRO ] ?? '' ) ) );
		$show  = empty( $_POST[ EventMetaKeys::SIGNUP_SHOW ] ) ? '' : '1';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		return array(
			self::FIELD_TITLE             => (string) $crudo['title'],
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- handle() verified the form nonce.
			self::FIELD_AREA              => implode( ',', array_map( 'sanitize_text_field', (array) wp_unslash( $_POST[ self::FIELD_AREA ] ?? array() ) ) ),
			self::FIELD_TYPE              => (string) (int) self::field( self::FIELD_TYPE ),
			self::FIELD_COURSE            => (string) (int) self::field( self::FIELD_COURSE ),
			EventMetaKeys::TAGLINE        => self::field( EventMetaKeys::TAGLINE ),
			EventMetaKeys::HASHTAG        => self::field( EventMetaKeys::HASHTAG ),
			EventMetaKeys::INTRO          => $intro,
			EventMetaKeys::START_DATE     => (string) $crudo['start_date'],
			EventMetaKeys::END_DATE       => (string) $crudo['end_date'],
			EventMetaKeys::VENUE          => (string) $crudo['venue'],
			EventMetaKeys::SIGNUP_SHOW    => $show,
			EventMetaKeys::SIGNUP_LABEL   => self::field( EventMetaKeys::SIGNUP_LABEL ),
			EventMetaKeys::SIGNUP_URL     => self::field( EventMetaKeys::SIGNUP_URL ),
			EventMetaKeys::SIGNUP_FORM_ID => (string) max( 0, (int) self::field( EventMetaKeys::SIGNUP_FORM_ID ) ),
		);
	}

	/**
	 * Save the «Datos» panel.
	 *
	 * @param int    $event_id Event post ID.
	 * @param int    $user_id  Who is asking.
	 * @param string $destino  Where to go back to.
	 * @return void
	 */
	private static function save_data( int $event_id, int $user_id, string $destino ): void {
		$crudo = array(
			'title'      => self::field( self::FIELD_TITLE ),
			'start_date' => self::field( EventMetaKeys::START_DATE ),
			'end_date'   => self::field( EventMetaKeys::END_DATE ),
			'venue'      => self::field( EventMetaKeys::VENUE ),
			'parent'     => 0,
		);

		$valores = self::submitted_values( $crudo );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- handle() verified the form nonce.
		if ( '' === $valores[ self::FIELD_AREA ] && ! isset( $_POST['evt_area_present'] ) ) {
			$mine                        = EventAccess::can_edit_all_areas( $user_id ) ? EventAccess::post_areas( $event_id ) : array_intersect( EventAccess::post_areas( $event_id ), EventAccess::scope_areas( $user_id ) );
			$valores[ self::FIELD_AREA ] = implode( ',', $mine );
		}
		$revisado = EventInput::validate( $crudo );
		if ( ! $revisado['ok'] ) {
			// Se devuelve lo tecleado junto al aviso: perder catorce campos
			// por una fecha mal escrita es la forma más rápida de que no se
			// vuelva a intentar.
			self::set_flash( 'error', self::why( $revisado['errors'] ), $valores );
			Shell::leave( $destino );
			return;
		}
		$area_ids = EventAccess::resolve_area_assignment( $event_id, '' === $valores[ self::FIELD_AREA ] ? array() : explode( ',', $valores[ self::FIELD_AREA ] ), $user_id );
		if ( is_wp_error( $area_ids ) ) {
			self::set_flash( 'error', $area_ids->get_error_message() . ' No se ha guardado ningún cambio.', $valores );
			Shell::leave( $destino );
			return;
		}
		$loses_access = ! EventAccess::can_edit_all_areas( $user_id ) && ! array_intersect( $area_ids, EventAccess::scope_areas( $user_id ) );

		wp_update_post(
			array(
				'ID'         => $event_id,
				'post_title' => $revisado['data']['title'],
			)
		);
		self::save_meta( $event_id, $valores, $revisado['data'] );
		self::save_terms( $event_id, $area_ids, $valores );

		if ( $loses_access ) {
			Shell::leave( add_query_arg( EventList::VAR_NOTICE, 'retirado', Shell::url( 'events' ) ) );
			return;
		}
		self::set_flash( 'ok', 'Datos del evento guardados.' );
		Shell::leave( $destino );
	}

	/**
	 * Write the meta of the data panel.
	 *
	 * Los sanitize de `register_post_meta()` corren dentro de cada
	 * `update_post_meta()`: aquí no se vuelve a limpiar nada.
	 *
	 * @param int                   $event_id Event post ID.
	 * @param array<string, string> $valores  What the form submitted.
	 * @param array<string, mixed>  $limpio   What EventInput normalised.
	 * @return void
	 */
	private static function save_meta( int $event_id, array $valores, array $limpio ): void {
		$meta = array(
			EventMetaKeys::TAGLINE        => $valores[ EventMetaKeys::TAGLINE ],
			EventMetaKeys::HASHTAG        => $valores[ EventMetaKeys::HASHTAG ],
			EventMetaKeys::INTRO          => $valores[ EventMetaKeys::INTRO ],
			EventMetaKeys::START_DATE     => (string) $limpio['start_date'],
			EventMetaKeys::END_DATE       => (string) $limpio['end_date'],
			EventMetaKeys::VENUE          => (string) $limpio['venue'],
			EventMetaKeys::SIGNUP_SHOW    => $valores[ EventMetaKeys::SIGNUP_SHOW ],
			EventMetaKeys::SIGNUP_LABEL   => $valores[ EventMetaKeys::SIGNUP_LABEL ],
			EventMetaKeys::SIGNUP_URL     => $valores[ EventMetaKeys::SIGNUP_URL ],
			EventMetaKeys::SIGNUP_FORM_ID => (int) $valores[ EventMetaKeys::SIGNUP_FORM_ID ],
		);
		foreach ( $meta as $clave => $valor ) {
			update_post_meta( $event_id, $clave, $valor );
		}
	}

	/**
	 * File the event under its área, its tipología and its curso escolar.
	 *
	 * @param int                   $event_id Event post ID.
	 * @param int[]                 $area_ids Final scope terms from EventAccess.
	 * @param array<string, string> $valores  What the form submitted.
	 * @return void
	 */
	private static function save_terms( int $event_id, array $area_ids, array $valores ): void {
		$mapa = array(
			EventTaxonomies::TYPE   => (int) $valores[ self::FIELD_TYPE ],
			EventTaxonomies::COURSE => (int) $valores[ self::FIELD_COURSE ],
		);
		wp_set_object_terms( $event_id, $area_ids, EventTaxonomies::AREA, false );
		foreach ( $mapa as $taxonomia => $term_id ) {
			wp_set_object_terms( $event_id, $term_id > 0 ? array( $term_id ) : array(), $taxonomia, false );
		}
	}

	/**
	 * Whether this person may file the event under that área.
	 *
	 * Quien no coordina solo puede ponerle un área de las suyas: si no, el
	 * desplegable es la forma de regalarle el evento a otra área —y de
	 * perderlo de vista para siempre— con dos clics.
	 *
	 * @param int $user_id Who is asking.
	 * @param int $area_id Term ID of evt_area.
	 * @return bool
	 */
	public static function may_set_area( int $user_id, int $area_id ): bool {
		return EventAccess::may_assign_areas( array( $area_id ), $user_id );
	}

	/**
	 * Whether this person may write any of the two code fields of the event.
	 *
	 * Es la condición de que exista la pestaña «Código». Con «alguna» basta, y
	 * es el caso corriente y no el raro: el área escribe el CSS de su evento y
	 * nunca el JavaScript, así que esconder la pestaña entera por eso la
	 * dejaría sin hoja de estilos. Cada campo se vuelve a comprobar por su
	 * cuenta, al pintarlo y al guardarlo.
	 *
	 * @param int $user_id  Who is asking.
	 * @param int $event_id Event post ID.
	 * @return bool
	 */
	public static function may_edit_code( int $user_id, int $event_id ): bool {
		return EventAccess::can_edit_custom_css( $user_id, $event_id )
			|| EventAccess::can_edit_custom_js( $user_id, $event_id );
	}

	/**
	 * Save the «Código» panel: one field, one capability, one check.
	 *
	 * No se mira si la pestaña se pintó: se comprueba aquí otra vez y campo a
	 * campo, que es lo único que vale contra un POST escrito a mano. Quien
	 * puede el CSS y no el JavaScript guarda el CSS, y el JavaScript que
	 * viniera en el envío se ignora y se dice —no se guarda a medias en
	 * silencio—.
	 *
	 * Los dos valores van en crudo: el `sanitize_callback` que
	 * {@see \Evt\Meta\EventMetaRegistration} registró corre dentro de
	 * `update_post_meta()` y es el que quita la etiqueta del CSS y desactiva la
	 * fuga por `</script` del JavaScript. Limpiarlos aquí con
	 * `sanitize_text_field()` los rompería: convertiría cada `>` en `&gt;`.
	 *
	 * @param int    $event_id Event post ID.
	 * @param int    $user_id  Who is asking.
	 * @param string $destino  Where to go back to.
	 * @return void
	 */
	private static function save_code( int $event_id, int $user_id, string $destino ): void {
		$permitido = array(
			EventMetaKeys::CUSTOM_CSS => EventAccess::can_edit_custom_css( $user_id, $event_id ),
			EventMetaKeys::CUSTOM_JS  => EventAccess::can_edit_custom_js( $user_id, $event_id ),
		);
		$nombres   = array(
			EventMetaKeys::CUSTOM_CSS => 'el CSS',
			EventMetaKeys::CUSTOM_JS  => 'el JavaScript',
		);

		if ( ! in_array( true, $permitido, true ) ) {
			self::set_flash( 'error', 'Su perfil no puede editar el código a medida de este evento.' );
			Shell::leave( $destino );
			return;
		}

		$ignorados = array();
		foreach ( $permitido as $clave => $puede ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- el nonce lo comprobó handle().
			if ( ! isset( $_POST[ $clave ] ) ) {
				// El campo no vino: la ausencia no es una orden de vaciar.
				continue;
			}
			if ( ! $puede ) {
				$ignorados[] = $nombres[ $clave ];
				continue;
			}
			update_post_meta( $event_id, $clave, self::raw( $clave ) );
		}

		if ( array() !== $ignorados ) {
			self::set_flash(
				'aviso',
				'Se guardó lo que su perfil puede cambiar. Se ignoró ' . implode( ' y ', $ignorados )
					. ': su perfil no tiene esa capacidad, así que se quedó exactamente como estaba.'
			);
			Shell::leave( $destino );
			return;
		}

		self::set_flash( 'ok', 'Código del evento guardado. Recargue una página del evento para verlo aplicado.' );
		Shell::leave( $destino );
	}

	/**
	 * One submitted field, untouched: code is code.
	 *
	 * @param string $nombre Field name.
	 * @return string
	 */
	private static function raw( string $nombre ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- el nonce lo comprobó handle(), y de limpiar se encarga el sanitize_callback de la meta.
		$valor = wp_unslash( $_POST[ $nombre ] ?? '' );
		return is_string( $valor ) ? $valor : '';
	}

	/**
	 * Save the «Apariencia» panel.
	 *
	 * @param int    $event_id Event post ID.
	 * @param string $destino  Where to go back to.
	 * @return void
	 */
	private static function save_look( int $event_id, string $destino ): void {
		$listas = array(
			EventMetaKeys::TITLE_FONT  => array( EventMetaKeys::fonts(), EventMetaKeys::FONT_DEFAULT ),
			EventMetaKeys::BODY_FONT   => array( EventMetaKeys::fonts(), EventMetaKeys::FONT_DEFAULT ),
			EventMetaKeys::IMAGE_SHAPE => array( EventMetaKeys::image_shapes(), EventMetaKeys::SHAPE_SQUARE ),
			EventMetaKeys::SEPARATOR   => array( EventMetaKeys::separators(), '' ),
		);

		update_post_meta( $event_id, EventMetaKeys::HEADER_BG, self::field( EventMetaKeys::HEADER_BG ) );
		update_post_meta( $event_id, EventMetaKeys::HEADER_TEXT, self::field( EventMetaKeys::HEADER_TEXT ) );
		foreach ( $listas as $clave => $lista ) {
			update_post_meta( $event_id, $clave, EventMetaKeys::in_list( self::field( $clave ), $lista[0], $lista[1] ) );
		}

		// La imagen destacada no es una meta nuestra sino el `thumbnail` de
		// WordPress: eso dice la clave vacía.
		$subidas = array(
			self::save_image( $event_id, 'evt_logo', EventMetaKeys::LOGO_ID ),
			self::save_image( $event_id, 'evt_header_banner', EventMetaKeys::HEADER_BANNER_ID, 1920 ),
			self::save_image( $event_id, 'evt_poster', EventMetaKeys::POSTER_ID ),
			self::save_image( $event_id, 'evt_featured', '' ),
		);

		if ( in_array( false, $subidas, true ) ) {
			self::set_flash( 'aviso', 'Se guardó la apariencia, pero alguna imagen no se pudo cambiar y se quedó como estaba. Revise que sea una imagen de la biblioteca —JPG, PNG, WEBP o GIF—, que no pese demasiado y, si es el banner, que tenga al menos 1920 píxeles de ancho.' );
			Shell::leave( $destino );
			return;
		}
		self::set_flash( 'ok', 'Apariencia del evento guardada.' );
		Shell::leave( $destino );
	}

	/**
	 * Replace or clear one of the images of the event.
	 *
	 * Tres caminos, en este orden: un fichero recién subido gana; si no, la
	 * casilla de quitar del respaldo sin guion; y si no, el identificador de
	 * adjunto que trae el campo oculto que rellena el selector de medios. De
	 * ese identificador no se fía nadie —lo escribe el navegador—: si no es
	 * un adjunto de imagen legible, no se toca lo que ya había y se avisa.
	 * Cuando el campo ni siquiera viene, tampoco se toca nada: la ausencia no
	 * es una orden de borrar.
	 *
	 * @param int    $event_id Event post ID.
	 * @param string $campo    Field prefix, e.g. `evt_logo`.
	 * @param string $meta_key Where the attachment ID lives; empty = thumbnail.
	 * @param int    $min_width Minimum width in pixels; 0 accepts any width.
	 * @return bool False when what was sent could not be stored.
	 */
	private static function save_image( int $event_id, string $campo, string $meta_key, int $min_width = 0 ): bool {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- el nonce lo comprobó handle().
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- del fichero se encarga media_handle_upload(); del identificador, absint().
		if ( ! empty( $_FILES[ $campo . '_file' ]['name'] ) ) {
			$subido = self::upload( $campo . '_file', $event_id );
			if ( $subido <= 0 ) {
				return false;
			}
			return self::validate_and_put_image( $event_id, $meta_key, $subido, $min_width );
		}

		if ( ! empty( $_POST[ $campo . '_clear' ] ) ) {
			self::put_image( $event_id, $meta_key, 0 );
			return true;
		}

		if ( ! isset( $_POST[ $campo . '_id' ] ) ) {
			return true;
		}
		$elegido = absint( wp_unslash( $_POST[ $campo . '_id' ] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		return self::validate_and_put_image( $event_id, $meta_key, $elegido, $min_width );
	}

	/**
	 * Validate an image chosen by either upload path and store it.
	 *
	 * @param int    $event_id      Event post ID.
	 * @param string $meta_key      Where the attachment ID lives; empty = thumbnail.
	 * @param int    $attachment_id Attachment ID; 0 to clear it.
	 * @param int    $min_width     Minimum width in pixels; 0 accepts any width.
	 * @return bool False when the attachment is not a usable image.
	 */
	private static function validate_and_put_image( int $event_id, string $meta_key, int $attachment_id, int $min_width ): bool {
		if ( $attachment_id > 0 && ! self::is_image_attachment( $attachment_id ) ) {
			return false;
		}
		if ( $attachment_id > 0 && ! self::image_meets_min_width( $attachment_id, $min_width ) ) {
			return false;
		}

		self::put_image( $event_id, $meta_key, $attachment_id );
		return true;
	}

	/**
	 * Point one image of the event at an attachment, or at nothing.
	 *
	 * Se quita siempre y se vuelve a poner si hay algo, que deja quitar y
	 * cambiar en el mismo camino. Con 0, `set_post_thumbnail()` no hace nada:
	 * es justo lo que se quiere después de haber quitado la destacada.
	 *
	 * @param int    $event_id      Event post ID.
	 * @param string $meta_key      Where the attachment ID lives; empty = thumbnail.
	 * @param int    $attachment_id Attachment ID; 0 to clear it.
	 * @return void
	 */
	private static function put_image( int $event_id, string $meta_key, int $attachment_id ): void {
		if ( '' === $meta_key ) {
			delete_post_thumbnail( $event_id );
			set_post_thumbnail( $event_id, $attachment_id );
			return;
		}

		delete_post_meta( $event_id, $meta_key );
		if ( $attachment_id > 0 ) {
			update_post_meta( $event_id, $meta_key, $attachment_id );
		}
	}

	/**
	 * Whether that ID really is an image in the media library, readable here.
	 *
	 * El campo oculto lo escribe el navegador, así que lo escribe cualquiera:
	 * sin esta comprobación, un número tecleado a mano pondría de cartel del
	 * evento un PDF, un borrador de otra persona o una página cualquiera. Que
	 * quien lo manda pueda editar ESTE evento ya lo comprobó {@see handle()}.
	 *
	 * @param int $attachment_id What the hidden field carried.
	 * @return bool
	 */
	private static function is_image_attachment( int $attachment_id ): bool {
		return 'attachment' === get_post_type( $attachment_id )
			&& wp_attachment_is_image( $attachment_id )
			&& current_user_can( 'read_post', $attachment_id );
	}

	/**
	 * Whether an image is wide enough for a field with a minimum resolution.
	 *
	 * @param int $attachment_id Image attachment ID.
	 * @param int $min_width     Minimum width in pixels; 0 means unrestricted.
	 * @return bool
	 */
	private static function image_meets_min_width( int $attachment_id, int $min_width ): bool {
		if ( $min_width <= 0 ) {
			return true;
		}
		$imagen = wp_get_attachment_image_src( $attachment_id, 'full' );
		return is_array( $imagen ) && (int) $imagen[1] >= $min_width;
	}

	/**
	 * Put an uploaded image in the media library, attached to the event.
	 *
	 * @param string $campo    Field name.
	 * @param int    $event_id Event post ID.
	 * @return int Attachment ID, or 0 when it could not be stored.
	 */
	private static function upload( string $campo, int $event_id ): int {
		if ( ! current_user_can( 'upload_files' ) ) {
			return 0;
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$id = media_handle_upload(
			$campo,
			$event_id,
			array(),
			array(
				// El formulario ya trae comprobado su nonce; la comprobación
				// propia de `wp_handle_upload` es la del escritorio.
				'test_form' => false,
				'mimes'     => self::IMAGE_MIMES,
			)
		);
		return is_wp_error( $id ) ? 0 : (int) $id;
	}

	/**
	 * Why the data panel could not be saved, in words.
	 *
	 * @param string[] $errores What EventInput::validate() returned.
	 * @return string
	 */
	private static function why( array $errores ): string {
		$textos = array(
			'title'      => 'El evento necesita un título.',
			'date_order' => 'La fecha de fin no puede ser anterior a la de inicio.',
			'start_date' => 'Indique la fecha de inicio del evento, con día, mes y año.',
			'end_date'   => 'Revise la fecha de fin: no es una fecha del calendario.',
		);
		foreach ( $textos as $clave => $texto ) {
			if ( in_array( $clave, $errores, true ) ) {
				return $texto;
			}
		}
		return 'Revise los datos del evento: hay algo que no se puede guardar.';
	}

	/**
	 * One submitted text field, trimmed and sanitised.
	 *
	 * @param string $nombre Field name.
	 * @return string
	 */
	/**
	 * What the signup tab shows: the questions and the two windows.
	 *
	 * @param array<string, mixed> $m        Model so far.
	 * @param int                  $event_id Event post ID.
	 * @return array<string, mixed>
	 */
	private static function fill_signup( array $m, int $event_id ): array {
		$m['questions'] = Registrations::questions( $event_id );
		// Con gente ya inscrita, a una pregunta se le puede cambiar el rótulo y
		// añadir opciones, pero no el tipo ni quitar una opción (ADR-0032). La
		// pantalla lo dice antes de que alguien lo intente.
		$m['q_locked'] = Registrations::has_any( $event_id );
		$m['signup']   = array(
			'open'            => (bool) get_post_meta( $event_id, RegistrationMetaKeys::SIGNUP_OPEN, true ),
			'workshop_open'   => (bool) get_post_meta( $event_id, RegistrationMetaKeys::WORKSHOP_OPEN, true ),
			'workshop_start'  => (string) get_post_meta( $event_id, RegistrationMetaKeys::WORKSHOP_START, true ),
			'workshop_end'    => (string) get_post_meta( $event_id, RegistrationMetaKeys::WORKSHOP_END, true ),
			'consent_privacy' => (string) get_post_meta( $event_id, RegistrationMetaKeys::CONSENT_PRIVACY, true ),
			'consent_image'   => (string) get_post_meta( $event_id, RegistrationMetaKeys::CONSENT_IMAGE, true ),
			'consent_version' => (int) get_post_meta( $event_id, RegistrationMetaKeys::CONSENT_VERSION, true ),
			'has_signups'     => $m['q_locked'],
		);
		return $m;
	}

	/**
	 * Save the signup settings and the question list of the event.
	 *
	 * @param int    $event_id Event post ID.
	 * @param string $destino  Where to go back to.
	 * @return void
	 */
	private static function save_signup( int $event_id, string $destino ): void {
		$abierta  = self::field( 'evt_signup_open' );
		$talleres = self::field( 'evt_workshop_open' );

		update_post_meta( $event_id, RegistrationMetaKeys::SIGNUP_OPEN, '' !== $abierta );
		update_post_meta( $event_id, RegistrationMetaKeys::WORKSHOP_OPEN, '' !== $talleres );
		update_post_meta( $event_id, RegistrationMetaKeys::WORKSHOP_START, self::field( 'evt_workshop_start' ) );
		update_post_meta( $event_id, RegistrationMetaKeys::WORKSHOP_END, self::field( 'evt_workshop_end' ) );

		self::save_consent( $event_id );

		$antes = Registrations::questions( $event_id );
		$ahora = SignupQuestions::with_ids(
			SignupQuestions::read( self::submitted_questions() ),
			static function (): string {
				return bin2hex( random_bytes( 6 ) );
			}
		);

		$rechazo = SignupQuestions::refuse( $antes, $ahora, Registrations::has_any( $event_id ) );
		if ( array() !== $rechazo ) {
			self::set_flash(
				'error',
				'Ya hay personas inscritas, así que a una pregunta se le puede cambiar el rótulo y añadir opciones, '
					. 'pero no cambiarle el tipo ni quitarle una opción: lo ya contestado dejaría de significar lo mismo. '
					. 'Lo demás no se ha guardado.'
			);
			Shell::leave( $destino );
			return;
		}

		// `wp_slash()`: `update_post_meta()` desescapa, y un rótulo con tilde o
		// con comillas llegaría roto al JSON guardado.
		update_post_meta( $event_id, RegistrationMetaKeys::SIGNUP_QUESTIONS, wp_slash( (string) wp_json_encode( $ahora ) ) );
		self::set_flash( 'ok', 'Inscripción del evento guardada.' );
		Shell::leave( $destino );
	}

	/**
	 * Store the two consent texts, bumping the version when they change.
	 *
	 * Cambiarlos crea una versión nueva y **no reescribe** la que ya aceptó
	 * nadie (ADR-0020): por eso la versión sube aquí y la inscripción guarda la
	 * suya al crearse.
	 *
	 * @param int $event_id Event post ID.
	 * @return void
	 */
	private static function save_consent( int $event_id ): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- el nonce lo comprobó handle().
		$privacidad = wp_kses_post( wp_unslash( (string) ( $_POST['evt_consent_privacy'] ?? '' ) ) );
		$imagen     = wp_kses_post( wp_unslash( (string) ( $_POST['evt_consent_image'] ?? '' ) ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$antes_privacidad = (string) get_post_meta( $event_id, RegistrationMetaKeys::CONSENT_PRIVACY, true );
		$antes_imagen     = (string) get_post_meta( $event_id, RegistrationMetaKeys::CONSENT_IMAGE, true );
		$cambia           = $antes_privacidad !== $privacidad || $antes_imagen !== $imagen;

		update_post_meta( $event_id, RegistrationMetaKeys::CONSENT_PRIVACY, $privacidad );
		update_post_meta( $event_id, RegistrationMetaKeys::CONSENT_IMAGE, $imagen );

		if ( $cambia ) {
			update_post_meta(
				$event_id,
				RegistrationMetaKeys::CONSENT_VERSION,
				1 + (int) get_post_meta( $event_id, RegistrationMetaKeys::CONSENT_VERSION, true )
			);
		}
	}

	/**
	 * The question rows as they come from the form.
	 *
	 * Campos paralelos, sin JavaScript: una fila por índice. La que llegue sin
	 * rótulo se cae sola en {@see SignupQuestions::read()}, y es lo que hace
	 * que la fila en blanco del final sirva para añadir una pregunta y también
	 * para no añadir ninguna.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function submitted_questions(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- el nonce lo comprobó handle(); son listas y cada elemento se sanea en el bucle de abajo.
		$ids       = (array) wp_unslash( $_POST['evt_q_id'] ?? array() );
		$rotulos   = (array) wp_unslash( $_POST['evt_q_label'] ?? array() );
		$tipos     = (array) wp_unslash( $_POST['evt_q_type'] ?? array() );
		$opciones  = (array) wp_unslash( $_POST['evt_q_options'] ?? array() );
		$obligadas = (array) wp_unslash( $_POST['evt_q_required'] ?? array() );
		// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$out = array();
		foreach ( $rotulos as $i => $rotulo ) {
			$out[] = array(
				'id'       => isset( $ids[ $i ] ) ? sanitize_text_field( (string) $ids[ $i ] ) : '',
				'label'    => sanitize_text_field( (string) $rotulo ),
				'type'     => isset( $tipos[ $i ] ) ? sanitize_key( (string) $tipos[ $i ] ) : 'text',
				'options'  => isset( $opciones[ $i ] ) ? sanitize_textarea_field( (string) $opciones[ $i ] ) : '',
				'required' => ! empty( $obligadas[ $i ] ),
			);
		}
		return $out;
	}

	/**
	 * One submitted field, trimmed and sanitised.
	 *
	 * @param string $nombre Field name.
	 * @return string
	 */
	private static function field( string $nombre ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- el nonce lo comprobó handle() antes de llamar aquí.
		return trim( sanitize_text_field( wp_unslash( (string) ( $_POST[ $nombre ] ?? '' ) ) ) );
	}

	/*
	 * -----------------------------------------------------------------------
	 * El aviso que sobrevive a la redirección
	 * -----------------------------------------------------------------------
	 */

	/**
	 * Where the notice of the last action waits, per person.
	 *
	 * @return string
	 */
	private static function flash_key(): string {
		return 'evt_ws_flash_' . get_current_user_id();
	}

	/**
	 * Leave a notice —and what was typed— for the screen we go back to.
	 *
	 * @param string                $tipo    ok | aviso | error.
	 * @param string                $texto   What to say.
	 * @param array<string, string> $valores What was submitted, to repaint it.
	 * @return void
	 */
	private static function set_flash( string $tipo, string $texto, array $valores = array() ): void {
		set_transient(
			self::flash_key(),
			array(
				'tipo'   => $tipo,
				'texto'  => $texto,
				'values' => $valores,
			),
			MINUTE_IN_SECONDS
		);
	}

	/**
	 * Read the notice of the last action, and forget it.
	 *
	 * @return array{tipo:string, texto:string, values:array<string, string>}
	 */
	private static function take_flash(): array {
		$vacio = array(
			'tipo'   => '',
			'texto'  => '',
			'values' => array(),
		);
		$flash = get_transient( self::flash_key() );
		delete_transient( self::flash_key() );
		return is_array( $flash ) ? array_merge( $vacio, array_intersect_key( $flash, $vacio ) ) : $vacio;
	}

	/*
	 * -----------------------------------------------------------------------
	 * El modelo
	 * -----------------------------------------------------------------------
	 */

	/**
	 * The satellite pages of an event, in the order they are shown.
	 *
	 * La papelera se pide aparte y nunca se mezcla: el listado normal no enseña
	 * lo que se envió a ella, que es justo lo que hace que enviar algo a la
	 * papelera se note.
	 *
	 * @param int  $event_id Event post ID.
	 * @param bool $papelera True for what was sent to the trash instead.
	 * @return \WP_Post[]
	 */
	public static function children( int $event_id, bool $papelera = false ): array {
		if ( $event_id <= 0 ) {
			return array();
		}
		return (array) get_posts(
			array(
				'post_type'        => EventPostType::POST_TYPE,
				'post_parent'      => $event_id,
				'post_status'      => $papelera ? array( 'trash' ) : array( 'publish', 'future', 'draft', 'pending', 'private' ),
				'orderby'          => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
				// phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts -- un evento con más de 200 páginas satélite no existe, y la tabla no se pagina a propósito.
				'numberposts'      => 200,
				'suppress_filters' => false,
			)
		);
	}

	/**
	 * Everything the workshop decides before painting anything.
	 *
	 * @return array<string, mixed>
	 */
	public static function model(): array {
		$m = self::blank();

		if ( ! is_user_logged_in() ) {
			$m['aviso'] = 'Debe iniciar sesión con su usuario para gestionar eventos.';
			return $m;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- lectura del evento y de la pestaña; mutar lleva su nonce.
		$event_id = absint( wp_unslash( $_GET[ self::ARG_EVENT ] ?? 0 ) );
		$pedido   = sanitize_key( wp_unslash( (string) ( $_GET[ self::ARG_PANEL ] ?? '' ) ) );
		// La papelera es una vista de la pestaña de secciones, no una pestaña
		// más: se pide con `?papelera=1` y se lee aquí, con el resto de la URL.
		$m['trash'] = '' !== sanitize_key( wp_unslash( (string) ( $_GET[ self::ARG_TRASH ] ?? '' ) ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// Sin evento en la dirección, esto es el alta: es a donde lleva «Crear
		// evento» del listado. La capacidad es la misma con la que ese botón se
		// pinta, así que quien llegue aquí a mano y no pueda crear lee por qué.
		if ( $event_id <= 0 ) {
			return self::blank_event( $m );
		}

		$evento = get_post( $event_id );
		if ( ! $evento instanceof \WP_Post
			|| EventPostType::POST_TYPE !== $evento->post_type
			|| (int) $evento->post_parent > 0 ) {
			$m['aviso'] = 'Ese evento ya no existe. Elija uno en la lista para abrir su taller.';
			return $m;
		}

		$user_id = get_current_user_id();
		// `can_open()` y no `can_edit()`: un evento marcado como histórico se
		// sigue abriendo, en solo lectura. Lo que no se puede es guardar, y de
		// eso se ocupa `handle()`, que sí pregunta por `can_edit()`.
		if ( ! EventAccess::can_open( $user_id, $event_id ) ) {
			$m['aviso']      = EventAccess::why_not_editable( $user_id, $event_id );
			$m['aviso_tipo'] = 'error';
			return $m;
		}

		return self::fill( $m, $evento, $user_id, $pedido );
	}

	/**
	 * The model of the «create an event» screen.
	 *
	 * Es el taller con una sola pestaña y sin nada que colgar de un evento que
	 * todavía no existe: lo único que hay que rellenar para que exista son el
	 * título y la fecha de inicio ({@see EventInput}), y lo demás se añade
	 * después, con el evento ya abierto.
	 *
	 * @param array<string, mixed> $m What blank() returned.
	 * @return array<string, mixed>
	 */
	private static function blank_event( array $m ): array {
		$user_id = get_current_user_id();
		if ( ! user_can( $user_id, 'edit_evt_events' ) ) {
			$m['aviso']      = 'Su perfil no puede crear eventos. Elija uno en la lista para abrir su taller.';
			$m['aviso_tipo'] = 'error';
			return $m;
		}

		$m['nuevo']        = true;
		$m['title']        = '';
		$m['panel']        = self::PANEL_SETTINGS;
		$m['panels']       = array();
		$m['flash']        = self::take_flash();
		$m['can_edit']     = true;
		$m['can_publish']  = false;
		$m['can_set_area'] = EventAccess::can_edit_all_areas( $user_id );
		$m['can_upload']   = current_user_can( 'upload_files' );
		$m['status']       = 'draft';
		$m['status_label'] = self::status_label( 'draft' );
		$m['values']       = self::values( 0, (array) $m['flash']['values'] );
		$m['terms']        = self::term_lists( $user_id );

		return $m;
	}

	/**
	 * The model of a workshop that cannot be opened.
	 *
	 * @return array<string, mixed>
	 */
	private static function blank(): array {
		return array(
			'aviso'         => '',
			'aviso_tipo'    => 'aviso',
			'nuevo'         => false,
			'event_id'      => 0,
			'title'         => '',
			'panel'         => self::PANEL_SECTIONS,
			'panels'        => array(),
			'flash'         => array(
				'tipo'   => '',
				'texto'  => '',
				'values' => array(),
			),
			'can_edit'      => false,
			'lock'          => EditLock::none(),
			'archived'      => false,
			'can_archive'   => false,
			'can_unarchive' => false,
			'can_publish'   => false,
			'can_set_area'  => false,
			'can_upload'    => false,
			'can_edit_css'  => false,
			'can_edit_js'   => false,
			'code'          => array(
				'css' => '',
				'js'  => '',
			),
			'state'         => '',
			'state_label'   => '',
			'status'        => '',
			'status_label'  => '',
			'area_ids'      => array(),
			'foreign_areas' => array(),
			'view_url'      => '',
			'events_url'    => Shell::url( 'events' ),
			'section_url'   => Shell::url( 'section' ),
			'sections'      => array(),
			'trashed'       => array(),
			'trash'         => false,
			'section_types' => EventMetaKeys::section_types(),
			'values'        => array(),
			'terms'         => array(),
			'media'         => array(),
			'speakers'      => array(),
			'activities'    => array(),
			'grid'          => array(),
			'workshops'     => array(),
			'venues'        => array(),
			'kinds'         => ProgrammeMetaKeys::activity_kinds(),
			'edit_row'      => 0,
			'edit_values'   => array(),
			'row_trash'     => array(),
			'people'        => array(),
			'people_total'  => 0,
			'people_q'      => '',
			'people_filter' => '',
			'people_tags'   => array(),
			'people_cols'   => Participants::columns(),
			'questions'     => array(),
			'q_types'       => RegistrationMetaKeys::question_types(),
			'q_locked'      => false,
			'signup'        => array(),
			'form_id'       => 0,
		);
	}

	/**
	 * Everything the workshop shows once the event is known and allowed.
	 *
	 * @param array<string, mixed> $m       What blank() returned.
	 * @param \WP_Post             $evento  The event.
	 * @param int                  $user_id Who is looking.
	 * @param string               $pedido  Tab asked for in the URL.
	 * @return array<string, mixed>
	 */
	private static function fill( array $m, \WP_Post $evento, int $user_id, string $pedido ): array {
		$event_id = (int) $evento->ID;
		$paneles  = self::panels( $event_id, $user_id );
		$css_ok   = EventAccess::can_edit_custom_css( $user_id, $event_id );
		$js_ok    = EventAccess::can_edit_custom_js( $user_id, $event_id );

		$m['event_id'] = $event_id;
		$m['title']    = (string) $evento->post_title;
		$m['panels']   = $paneles;
		$m['panel']    = isset( $paneles[ $pedido ] ) ? $pedido : self::PANEL_SECTIONS;
		$m['flash']    = self::take_flash();
		$m['can_edit'] = EventAccess::can_edit( $user_id, $event_id );
		$m['archived'] = EventAccess::is_archived( $event_id );
		// Abrir el taller toma el bloqueo del evento, igual que abrir el editor
		// del escritorio: es el mismo bloqueo. Y si ya lo tiene otra persona,
		// el taller pasa a solo lectura sin que ningún panel se entere —la
		// vista ya apaga sus controles cuando `can_edit` dice que no—.
		$m['lock'] = EditLock::status( $event_id, (bool) $m['can_edit'] );
		if ( (int) $m['lock']['owner'] > 0 ) {
			$m['can_edit'] = false;
		}
		// Los dos son «puede hacerlo ahora», no «tiene el permiso»: el estado
		// va dentro para que la vista no tenga que combinarlos otra vez y para
		// que no se pinten los dos botones a la vez. El bloqueo entra en la
		// cuenta porque el interruptor de histórico vive FUERA del panel que la
		// vista apaga, y cerrar un evento es la escritura menos reversible que
		// hay aquí.
		$libre              = 0 === (int) $m['lock']['owner'];
		$m['can_archive']   = $libre && ! $m['archived'] && EventAccess::can_archive( $user_id, $event_id );
		$m['can_unarchive'] = $libre && $m['archived'] && EventAccess::can_unarchive( $user_id );
		$m['can_publish']   = EventAccess::can_publish( $user_id, $event_id );
		$m['can_set_area']  = EventAccess::can_edit_all_areas( $user_id );
		$m['can_upload']    = current_user_can( 'upload_files' );
		$m['can_edit_css']  = $css_ok;
		$m['can_edit_js']   = $js_ok;
		// Lo guardado solo se devuelve a quien puede escribirlo: el modelo no
		// es un sitio donde el código se asome a quien no le corresponde.
		$m['code']          = array(
			'css' => $css_ok ? self::meta( $event_id, EventMetaKeys::CUSTOM_CSS ) : '',
			'js'  => $js_ok ? self::meta( $event_id, EventMetaKeys::CUSTOM_JS ) : '',
		);
		$m['view_url']      = (string) get_permalink( $evento );
		$m['status']        = (string) $evento->post_status;
		$m['status_label']  = self::status_label( (string) $evento->post_status );
		$m['state']         = EventState::of(
			self::meta( $event_id, EventMetaKeys::START_DATE ),
			self::meta( $event_id, EventMetaKeys::END_DATE )
		);
		$m['state_label']   = EventState::label( (string) $m['state'] );
		$m['area_ids']      = EventAccess::post_areas( $event_id );
		$foreign_ids        = EventAccess::can_edit_all_areas( $user_id ) ? array() : array_diff( $m['area_ids'], EventAccess::scope_areas( $user_id ) );
		$all_labels         = EventTaxonomies::area_options( 0, true );
		$m['foreign_areas'] = array_values( array_intersect_key( $all_labels, array_flip( $foreign_ids ) ) );
		$m['sections']      = self::section_rows( $event_id );
		$m['trashed']       = self::section_rows( $event_id, true );
		$m['values']        = self::values( $event_id, (array) $m['flash']['values'] );
		$m['terms']         = self::term_lists( $user_id );
		$m['media']         = array(
			'logo'          => (int) self::meta( $event_id, EventMetaKeys::LOGO_ID ),
			'header_banner' => (int) self::meta( $event_id, EventMetaKeys::HEADER_BANNER_ID ),
			'poster'        => (int) self::meta( $event_id, EventMetaKeys::POSTER_ID ),
			'featured'      => (int) get_post_thumbnail_id( $event_id ),
		);

		return self::fill_signup( self::fill_programme( $m, $event_id ), $event_id );
	}

	/**
	 * What the four tabs of the programme need, once the event is known.
	 *
	 * Se llena siempre y no solo en la pestaña abierta: los recuentos de las
	 * pestañas los pide `panels()` de todas formas, y partir el modelo en dos
	 * caminos por pestaña es la clase de ahorro que se paga en el primer fallo
	 * que solo aparece en una de ellas.
	 *
	 * @param array<string, mixed> $m        Model so far.
	 * @param int                  $event_id Event post ID.
	 * @return array<string, mixed>
	 */
	private static function fill_programme( array $m, int $event_id ): array {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- lectura: qué ficha se edita y qué se busca. Mutar lleva su nonce.
		$m['edit_row']      = absint( wp_unslash( $_GET[ self::ARG_ROW ] ?? 0 ) );
		$m['people_q']      = sanitize_text_field( wp_unslash( (string) ( $_GET[ self::ARG_Q ] ?? '' ) ) );
		$m['people_filter'] = sanitize_text_field( wp_unslash( (string) ( $_GET[ self::ARG_WORKSHOP ] ?? '' ) ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		foreach ( Programme::speakers( $event_id ) as $indice => $ponente ) {
			$fila            = Programme::speaker_row( $ponente );
			$fila['first']   = 0 === $indice;
			$fila['last']    = false;
			$m['speakers'][] = $fila;
		}
		$ultimo = count( (array) $m['speakers'] ) - 1;
		if ( $ultimo >= 0 ) {
			$m['speakers'][ $ultimo ]['last'] = true;
		}

		foreach ( Programme::activities( $event_id ) as $actividad ) {
			$m['activities'][] = Programme::activity_row( $actividad );
		}
		$inscritos = Participants::rows( $event_id );
		foreach ( Programme::workshops( $event_id ) as $taller ) {
			$fila = Programme::activity_row( $taller );
			// Las plazas ocupadas se cuentan por el título del taller, que es lo
			// que trae la inscripción mientras la inscripción no sea de este
			// aplicativo: no hay identificador compartido (ADR-0027). Si el
			// título cambia, deja de cuadrar, y por eso la pantalla dice de
			// dónde sale el número.
			$fila['taken']    = self::seats_taken( $inscritos, (string) $fila['title'] );
			$fila['free']     = $fila['seats'] > 0 ? max( 0, (int) $fila['seats'] - (int) $fila['taken'] ) : null;
			$m['workshops'][] = $fila;
		}

		$m['grid']   = Programme::grid( $event_id );
		$m['venues'] = Programme::venues( $event_id );

		// La papelera de fichas es una sola: ponentes y actividades juntos,
		// porque lo que se busca ahí es «lo que borré sin querer» y no de qué
		// tipo era.
		foreach ( array_merge( Programme::speakers( $event_id, true ), Programme::activities( $event_id, true ) ) as $ficha ) {
			$m['row_trash'][] = array(
				'id'    => (int) $ficha->ID,
				'title' => (string) $ficha->post_title,
				'kind'  => SpeakerPostType::POST_TYPE === $ficha->post_type ? 'Ponente' : 'Actividad',
			);
		}

		$m['edit_values'] = self::row_values( $event_id, (int) $m['edit_row'], (string) $m['panel'] );

		$todos             = $inscritos;
		$m['people_total'] = count( $todos );
		$m['people_tags']  = Participants::workshops( $todos );
		$m['people']       = Participants::filter( $todos, (string) $m['people_q'], (string) $m['people_filter'] );
		$m['form_id']      = (int) self::meta( $event_id, EventMetaKeys::SIGNUP_FORM_ID );

		return $m;
	}

	/**
	 * How many of the people signed up chose this workshop.
	 *
	 * @param array<int, array<string, string>> $inscritos Participant rows.
	 * @param string                            $titulo    Workshop title.
	 * @return int
	 */
	private static function seats_taken( array $inscritos, string $titulo ): int {
		if ( '' === trim( $titulo ) ) {
			return 0;
		}
		$cuenta = 0;
		foreach ( $inscritos as $fila ) {
			if ( trim( (string) ( $fila['workshop'] ?? '' ) ) === trim( $titulo ) ) {
				++$cuenta;
			}
		}
		return $cuenta;
	}

	/**
	 * The speaker or activity the edit form has to open with.
	 *
	 * Vacío cuando se está creando, y vacío también cuando el identificador
	 * pedido no es de este evento: la pantalla enseña el formulario de alta en
	 * vez de un error, que es lo que pasa de verdad cuando alguien vuelve con
	 * el botón de atrás a una ficha ya borrada.
	 *
	 * @param int    $event_id Event post ID.
	 * @param int    $row_id   Requested row.
	 * @param string $panel    Tab being painted.
	 * @return array<string, mixed>
	 */
	private static function row_values( int $event_id, int $row_id, string $panel ): array {
		if ( $row_id <= 0 ) {
			return array();
		}
		$post = get_post( $row_id );
		if ( ! $post instanceof \WP_Post || (int) $post->post_parent !== $event_id ) {
			return array();
		}
		if ( self::PANEL_SPEAKERS === $panel && SpeakerPostType::POST_TYPE === $post->post_type ) {
			return Programme::speaker_row( $post );
		}
		if ( ActivityPostType::POST_TYPE === $post->post_type ) {
			return Programme::activity_row( $post );
		}
		return array();
	}

	/**
	 * The human name of a post status.
	 *
	 * @param string $status Post status.
	 * @return string
	 */
	private static function status_label( string $status ): string {
		$objeto = get_post_status_object( $status );
		return null !== $objeto ? (string) $objeto->label : $status;
	}

	/**
	 * One meta value of the event, as a string.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $clave   Meta key.
	 * @return string
	 */
	private static function meta( int $post_id, string $clave ): string {
		return (string) get_post_meta( $post_id, $clave, true );
	}

	/**
	 * What every form field shows: what was typed, or what is stored.
	 *
	 * @param int                   $event_id Event post ID.
	 * @param array<string, string> $tecleado What the failed submit carried.
	 * @return array<string, string>
	 */
	private static function values( int $event_id, array $tecleado ): array {
		$guardado = array(
			// Con 0, `get_the_title()` cae en el post global —que en esta
			// pantalla es la página «Evento» del aplicativo— y el alta abriría
			// con el título ya escrito. Al crear, el título está vacío.
			self::FIELD_TITLE             => $event_id > 0 ? (string) get_the_title( $event_id ) : '',
			self::FIELD_AREA              => implode( ',', EventAccess::post_areas( $event_id ) ),
			self::FIELD_TYPE              => (string) self::first_term( $event_id, EventTaxonomies::TYPE ),
			self::FIELD_COURSE            => (string) self::first_term( $event_id, EventTaxonomies::COURSE ),
			EventMetaKeys::TAGLINE        => self::meta( $event_id, EventMetaKeys::TAGLINE ),
			EventMetaKeys::HASHTAG        => self::meta( $event_id, EventMetaKeys::HASHTAG ),
			EventMetaKeys::INTRO          => self::meta( $event_id, EventMetaKeys::INTRO ),
			EventMetaKeys::START_DATE     => self::meta( $event_id, EventMetaKeys::START_DATE ),
			EventMetaKeys::END_DATE       => self::meta( $event_id, EventMetaKeys::END_DATE ),
			EventMetaKeys::VENUE          => self::meta( $event_id, EventMetaKeys::VENUE ),
			EventMetaKeys::SIGNUP_SHOW    => '' === self::meta( $event_id, EventMetaKeys::SIGNUP_SHOW ) ? '' : '1',
			EventMetaKeys::SIGNUP_LABEL   => self::meta( $event_id, EventMetaKeys::SIGNUP_LABEL ),
			EventMetaKeys::SIGNUP_URL     => self::meta( $event_id, EventMetaKeys::SIGNUP_URL ),
			EventMetaKeys::SIGNUP_FORM_ID => self::meta( $event_id, EventMetaKeys::SIGNUP_FORM_ID ),
			EventMetaKeys::HEADER_BG      => self::meta( $event_id, EventMetaKeys::HEADER_BG ),
			EventMetaKeys::HEADER_TEXT    => self::meta( $event_id, EventMetaKeys::HEADER_TEXT ),
			EventMetaKeys::TITLE_FONT     => self::meta( $event_id, EventMetaKeys::TITLE_FONT ),
			EventMetaKeys::BODY_FONT      => self::meta( $event_id, EventMetaKeys::BODY_FONT ),
			EventMetaKeys::IMAGE_SHAPE    => self::meta( $event_id, EventMetaKeys::IMAGE_SHAPE ),
			EventMetaKeys::SEPARATOR      => self::meta( $event_id, EventMetaKeys::SEPARATOR ),
		);

		// Lo tecleado manda sobre lo guardado, pero solo en los campos que
		// existen: del POST no entra ninguna clave nueva.
		return array_merge( $guardado, array_intersect_key( $tecleado, $guardado ) );
	}

	/**
	 * The first term of one taxonomy on the event.
	 *
	 * @param int    $event_id Event post ID.
	 * @param string $taxonomy Taxonomy name.
	 * @return int Term ID, or 0.
	 */
	private static function first_term( int $event_id, string $taxonomy ): int {
		$ids = wp_get_post_terms( $event_id, $taxonomy, array( 'fields' => 'ids' ) );
		return is_array( $ids ) ? (int) ( $ids[0] ?? 0 ) : 0;
	}

	/**
	 * The three dropdowns of the classification card.
	 *
	 * @param int $user_id  Who is looking.
	 * @return array<string, array<int, string>>
	 */
	private static function term_lists( int $user_id ): array {
		$solo = EventAccess::can_edit_all_areas( $user_id )
			? array()
			: EventAccess::scope_areas( $user_id );

		return array(
			'area'   => self::term_options( EventTaxonomies::AREA, $solo ),
			'type'   => self::term_options( EventTaxonomies::TYPE ),
			'course' => self::term_options( EventTaxonomies::COURSE ),
		);
	}

	/**
	 * Terms of one taxonomy, optionally narrowed to a handful.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @param int[]  $solo     Term IDs to keep; empty for all of them.
	 * @return array<int, string> term_id => nombre.
	 */
	private static function term_options( string $taxonomy, array $solo = array() ): array {
		if ( EventTaxonomies::AREA === $taxonomy ) {
			return EventTaxonomies::area_options();
		}
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'fields'     => 'id=>name',
			)
		);
		if ( ! is_array( $terms ) ) {
			return array();
		}
		return array() === $solo ? $terms : array_intersect_key( $terms, array_flip( $solo ) );
	}

	/**
	 * The rows of the sections table.
	 *
	 * @param int  $event_id Event post ID.
	 * @param bool $papelera True for the rows of the trash.
	 * @return array<int, array<string, mixed>>
	 */
	private static function section_rows( int $event_id, bool $papelera = false ): array {
		$hijas = array_values( self::children( $event_id, $papelera ) );
		$total = count( $hijas );
		$tipos = EventMetaKeys::section_types();
		$filas = array();

		foreach ( $hijas as $i => $hija ) {
			$tipo = self::meta( (int) $hija->ID, EventMetaKeys::SECTION_TYPE );

			$filas[] = array(
				'id'           => (int) $hija->ID,
				'order'        => $i + 1,
				'type'         => $tipo,
				'type_label'   => (string) ( $tipos[ $tipo ] ?? 'Sin tipo' ),
				'title'        => (string) $hija->post_title,
				'slug'         => (string) $hija->post_name,
				'status'       => (string) $hija->post_status,
				'status_label' => self::status_label( (string) $hija->post_status ),
				'published'    => 'publish' === $hija->post_status,
				'edit_url'     => Shell::url(
					'section',
					array(
						self::ARG_EVENT   => $event_id,
						self::ARG_SECTION => (int) $hija->ID,
					)
				),
				// Una página en borrador **también se mira**: estando dentro, con
				// permiso para editarla, WordPress la sirve en previsualización.
				// Lo que cambia es la dirección, no que se pueda ver.
				'view_url'     => 'publish' === (string) $hija->post_status
					? (string) get_permalink( $hija )
					: (string) get_preview_post_link( $hija ),
				'first'        => 0 === $i,
				'last'         => $i === $total - 1,
			);
		}
		return $filas;
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
		$m = self::model();

		// El selector de medios solo en la pestaña que tiene imágenes: son
		// unos cuantos guiones de WordPress y en las demás pantallas no hay
		// nada que elegir. Quien no puede subir tampoco puede consultar la
		// biblioteca por AJAX, así que a esa persona solo se le enseña
		// «Quitar» y no se carga nada.
		if ( in_array( (string) $m['panel'], array( self::PANEL_LOOK, self::PANEL_SPEAKERS ), true ) && true === $m['can_upload'] ) {
			wp_enqueue_media( array( 'post' => (int) $m['event_id'] ) );
		}

		// El aviso va delante de la pantalla y no dentro: cuando hay dueño se
		// pinta como `<dialog open>` y es lo primero que se lee al entrar.
		return EditLock::render( EditLock::claim( (array) $m['lock'] ) ) . EventWorkspaceView::html( $m );
	}
}
