<?php
/**
 * Capability checks for events: who edits and publishes what, scoped by área.
 *
 * @package Evt
 */

namespace Evt\Access;

use Evt\Meta\EventMetaKeys;
use Evt\PostType\ActivityPostType;
use Evt\PostType\EventPostType;
use Evt\PostType\SpeakerPostType;
use Evt\Taxonomy\EventTaxonomies;

/**
 * El único guardián del aplicativo: aquí se responde «¿quién puede qué?».
 *
 * Falla en cerrado. Quien no tiene ningún área en su perfil y tampoco
 * `evt_edit_all_areas` no edita nada: abrir cuando falta el dato es como un
 * área acaba tocando los eventos de otra.
 */
final class EventAccess {

	/**
	 * User meta con uno o varios term_id de `evt_area`.
	 */
	public const USER_AREA_META = 'evt_area';

	/**
	 * Cap propia del aplicativo: ajustes y diagnóstico.
	 */
	public const CAP_MANAGE = 'evt_manage_app';

	/**
	 * Cap propia del aplicativo: saltarse el acotado por área.
	 */
	public const CAP_ALL_AREAS = 'evt_edit_all_areas';

	/**
	 * Cap propia del aplicativo: escribir el CSS a medida de un evento.
	 *
	 * La tienen `evt_organiser` —acotada a su área, como todo lo demás— y
	 * `administrator`. Dar aspecto a la jornada es parte de organizarla, y
	 * quien la organiza es quien sabe cómo tiene que verse.
	 */
	public const CAP_CUSTOM_CSS = 'evt_edit_custom_css';

	/**
	 * Cap propia del aplicativo: escribir el JavaScript a medida de un evento.
	 *
	 * Solo `administrator`, y distinta a propósito de la del CSS porque el
	 * riesgo no es el mismo: una hoja de estilos cambia cómo se ve una página;
	 * un guion ejecuta código en el navegador de cada persona que la visite.
	 * Ahí es donde está la raya, y es la única de las dos que no cruza el área.
	 */
	public const CAP_CUSTOM_JS = 'evt_edit_custom_js';

	/**
	 * Register filters.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'map_meta_cap', array( self::class, 'map_meta_cap' ), 10, 4 );
		add_action( 'save_post_' . SpeakerPostType::POST_TYPE, array( self::class, 'stamp_area' ) );
		add_action( 'save_post_' . ActivityPostType::POST_TYPE, array( self::class, 'stamp_area' ) );
	}

	/**
	 * The post types this guard scopes, each with the cap that opens its list.
	 *
	 * Los tres van juntos porque un área gestiona su evento entero: la portada,
	 * sus páginas, sus ponentes y sus actividades. Lo que cambia de uno a otro
	 * es la capacidad primitiva, no la regla.
	 *
	 * @return array<string, string> Post type => primitive cap.
	 */
	public static function scoped_types(): array {
		return array(
			EventPostType::POST_TYPE    => 'edit_evt_events',
			SpeakerPostType::POST_TYPE  => 'edit_evt_speakers',
			ActivityPostType::POST_TYPE => 'edit_evt_activities',
		);
	}

	/**
	 * Whether the user administers the application itself.
	 *
	 * @param int $user_id User ID (0 = current).
	 * @return bool
	 */
	public static function is_manager( int $user_id = 0 ): bool {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		return user_can( $user_id, self::CAP_MANAGE ) || user_can( $user_id, 'manage_options' );
	}

	/**
	 * Whether the user works across every área (administración).
	 *
	 * @param int $user_id User ID (0 = current).
	 * @return bool
	 */
	public static function can_edit_all_areas( int $user_id = 0 ): bool {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		return user_can( $user_id, self::CAP_ALL_AREAS ) || self::is_manager( $user_id );
	}

	/**
	 * Áreas the user belongs to.
	 *
	 * @param int $user_id User ID (0 = current).
	 * @return int[] Term IDs of evt_area; empty when the profile has none.
	 */
	public static function user_areas( int $user_id = 0 ): array {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		if ( $user_id <= 0 ) {
			return array();
		}
		$raw = get_user_meta( $user_id, self::USER_AREA_META, true );
		if ( ! is_array( $raw ) ) {
			$raw = '' === trim( (string) $raw ) ? array() : explode( ',', (string) $raw );
		}
		return self::clean_ids( $raw );
	}

	/**
	 * Áreas a post belongs to.
	 *
	 * Las páginas satélite no llevan área propia: la del evento manda, que es
	 * lo que evita que una hija se quede huérfana de permisos al moverla. Un
	 * ponente y una actividad sí llevan la suya, y pueden llevar varias: son
	 * reutilizables entre ediciones, así que compartir un ponente con otra área
	 * es añadirle ese término, no duplicar la ficha. `root_id()` devuelve el
	 * propio post cuando no cuelga de nadie, que es siempre su caso.
	 *
	 * @param int $post_id Post ID.
	 * @return int[] Term IDs of evt_area.
	 */
	public static function post_areas( int $post_id ): array {
		$terms = get_the_terms( self::root_id( $post_id ), EventTaxonomies::AREA );
		if ( ! is_array( $terms ) ) {
			return array();
		}
		return self::clean_ids( wp_list_pluck( $terms, 'term_id' ) );
	}

	/**
	 * The event a page belongs to (itself when it is the event).
	 *
	 * @param int $post_id Post ID.
	 * @return int
	 */
	public static function root_id( int $post_id ): int {
		$guard = 0;
		while ( $post_id > 0 && $guard < 10 ) {
			$parent = (int) get_post_field( 'post_parent', $post_id );
			if ( $parent <= 0 ) {
				break;
			}
			$post_id = $parent;
			++$guard;
		}
		return $post_id;
	}

	/**
	 * Whether this event is closed for good: the «histórico» mark.
	 *
	 * Se mira siempre en la raíz, así que el cierre alcanza a las páginas
	 * satélite del evento sin que cada pantalla tenga que acordarse: quien
	 * pregunte por una sección recibe la respuesta de su evento. Lo mismo
	 * valdría para un ponente o una actividad colgados de un evento, si algún
	 * día los cuelgan: hoy no lo están, se acotan por área y `root_id()`
	 * devuelve el propio post.
	 *
	 * @param int $post_id Post ID; the event, or anything under it.
	 * @return bool
	 */
	public static function is_archived( int $post_id ): bool {
		if ( $post_id <= 0 ) {
			return false;
		}
		return (bool) get_post_meta( self::root_id( $post_id ), EventMetaKeys::ARCHIVED, true );
	}

	/**
	 * Whether the user may MARK this event as «histórico».
	 *
	 * Lo marca el área que lo organiza, que es quien sabe cuándo está
	 * terminado; no hace falta administrar el aplicativo para cerrar lo tuyo.
	 *
	 * La puerta es {@see can_open()} y no {@see can_edit()} a propósito, y no
	 * es un descuido: `can_edit()` lleva el cierre encima, así que preguntar
	 * por él aquí se mordería la cola —marcar exigiría poder editar, y lo
	 * primero que hace la marca es quitar esa posibilidad—. Lo que hay que
	 * comprobar es la regla del área, que es lo que `can_open()` responde.
	 *
	 * Que la marca ya esté puesta no lo comprueba esta función: eso es el
	 * estado, no el permiso, y quien decide qué botón se pinta y qué POST se
	 * acepta es la pantalla. Volver a marcar lo marcado no cambia nada.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Event ID.
	 * @return bool
	 */
	public static function can_archive( int $user_id, int $post_id ): bool {
		return self::can_open( $user_id, $post_id );
	}

	/**
	 * Whether the user may UNMARK an event as «histórico».
	 *
	 * Solo administración, y la asimetría con {@see can_archive()} es
	 * deliberada: cerrar lo tuyo es tuyo, reabrirlo necesita a otra persona.
	 * Si el área pudiera desmarcarlo sola, el cierre sería una preferencia y
	 * no un cierre.
	 *
	 * @param int $user_id User ID (0 = current).
	 * @return bool
	 */
	public static function can_unarchive( int $user_id = 0 ): bool {
		return self::is_manager( $user_id );
	}

	/**
	 * Whether the user may write the «histórico» mark as it stands today.
	 *
	 * La suma de las dos reglas de arriba, y la que contesta a la pregunta que
	 * de verdad se hace desde fuera de la pantalla —el `auth_callback` de la
	 * meta—, donde no se sabe qué valor se va a escribir: mientras el evento
	 * está abierto, la marca la toca su área; en cuanto está cerrado, solo la
	 * toca administración. Así el área no se desmarca el evento por la puerta
	 * de al lado con un `update_post_meta()`.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Event ID.
	 * @return bool
	 */
	public static function can_toggle_archived( int $user_id, int $post_id ): bool {
		return self::is_archived( $post_id )
			? self::can_unarchive( $user_id )
			: self::can_archive( $user_id, $post_id );
	}

	/**
	 * Whether the user may open this event, even if only to read it.
	 *
	 * Es la regla del área, sin el cierre por encima: un evento marcado como
	 * histórico se sigue consultando y exportando desde su taller, y por eso
	 * el listado y el taller preguntan por aquí y no por {@see can_edit()}.
	 * Esconderlo sería peor: quien lo busque tiene que encontrarlo y entender
	 * por qué no puede tocarlo.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function can_open( int $user_id, int $post_id ): bool {
		if ( $user_id <= 0 || $post_id <= 0 ) {
			return false;
		}
		$cap = self::scoped_cap( $post_id );
		if ( '' === $cap ) {
			return false;
		}
		if ( self::can_edit_all_areas( $user_id ) ) {
			return true;
		}
		if ( ! user_can( $user_id, $cap ) ) {
			return false;
		}

		$mine = self::user_areas( $user_id );
		if ( array() === $mine ) {
			return false;
		}

		$theirs = self::post_areas( $post_id );
		if ( array() === $theirs ) {
			// Recién creado y todavía sin área: lo edita quien lo creó, que es
			// quien tiene que ponérsela. Para los ponentes y las actividades es
			// una ventana muy corta, porque `stamp_area()` se la pone al
			// guardar; se queda abierta cuando quien lo crea no tiene área
			// propia —la administración—, y entonces solo lo
			// toca esa persona hasta que alguien le asigne un área.
			return (int) get_post_field( 'post_author', self::root_id( $post_id ) ) === $user_id;
		}

		return array() !== array_intersect( $mine, $theirs );
	}

	/**
	 * Whether the user may edit this event, page, speaker or activity.
	 *
	 * La misma regla para los tres tipos, que es lo que hace que un área
	 * gestione su evento entero: comparte alguna área con el contenido, o no
	 * lo toca. Y por encima de esa regla, el cierre: con el evento marcado
	 * como histórico no lo edita el área que lo organizó —tampoco sus
	 * secciones—, y sí administración, porque alguien tiene que
	 * poder corregir una errata o desmarcarlo.
	 *
	 * Las dos reglas conviven sin contradecirse porque responden a preguntas
	 * distintas: la de ADR-0012 mira el calendario y cierra sola cuando el
	 * evento termina; esta la pone una persona y no se abre con el tiempo.
	 * Basta una para decir «no», y esta es la de arriba.
	 *
	 * Este es el único sitio donde se junta todo: `map_meta_cap()` manda aquí
	 * `edit_post`, `delete_post` y `publish_post`, así que el cierre alcanza
	 * también al escritorio, a la edición rápida y a la REST.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function can_edit( int $user_id, int $post_id ): bool {
		if ( ! self::can_open( $user_id, $post_id ) ) {
			return false;
		}
		return ! self::is_archived( $post_id ) || self::is_manager( $user_id );
	}

	/**
	 * The primitive cap that scopes this post, '' when it is not ours.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private static function scoped_cap( int $post_id ): string {
		$tipos = self::scoped_types();
		$tipo  = (string) get_post_type( $post_id );
		return isset( $tipos[ $tipo ] ) ? $tipos[ $tipo ] : '';
	}

	/**
	 * Give a brand new speaker or activity the áreas of whoever created it.
	 *
	 * Sin esto un ponente nace sin área y solo lo toca quien lo tecleó, no sus
	 * compañeras: el área es el ámbito de trabajo, no la autoría (ADR-0006).
	 * Solo escribe cuando todavía no hay ninguna, así que compartir un ponente
	 * con otra área —añadirle su término— no se deshace en el siguiente
	 * guardado. Se mira el área de quien firma el post y no la de quien guarda,
	 * para que la migración y WP-CLI den el mismo resultado.
	 *
	 * @param int $post_id Speaker or activity being saved.
	 * @return void
	 */
	public static function stamp_area( int $post_id ): void {
		if ( array() !== self::post_areas( $post_id ) ) {
			return;
		}
		$areas = self::user_areas( (int) get_post_field( 'post_author', $post_id ) );
		if ( array() === $areas ) {
			return;
		}
		wp_set_object_terms( $post_id, $areas, EventTaxonomies::AREA );
	}

	/**
	 * Whether the user may publish events, and this one in particular.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Post ID (0 = just the capability).
	 * @return bool
	 */
	public static function can_publish( int $user_id, int $post_id = 0 ): bool {
		if ( $user_id <= 0 || ! user_can( $user_id, 'publish_evt_events' ) ) {
			return false;
		}
		if ( $post_id <= 0 ) {
			return true;
		}
		return self::can_edit( $user_id, $post_id );
	}

	/**
	 * Whether the user may write the custom CSS of this event or page.
	 *
	 * Las dos condiciones, siempre: la capacidad propia y poder editar ese
	 * evento. Tener la capacidad no abre los eventos de otra área.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function can_edit_custom_css( int $user_id, int $post_id ): bool {
		return self::can_edit_code( $user_id, $post_id, self::CAP_CUSTOM_CSS );
	}

	/**
	 * Whether the user may write the custom JavaScript of this event or page.
	 *
	 * Lo mismo que el CSS y una condición más: en multisitio hace falta
	 * `unfiltered_html`. WordPress se la quita a propósito a quien administra
	 * un subsitio y se la reserva a la superadministración; esa decisión es de
	 * la plataforma y aquí se respeta en vez de rodearla.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function can_edit_custom_js( int $user_id, int $post_id ): bool {
		if ( ! self::can_edit_code( $user_id, $post_id, self::CAP_CUSTOM_JS ) ) {
			return false;
		}

		$suelto = ! is_multisite() || user_can( $user_id, 'unfiltered_html' );

		/**
		 * Filter the multisite rule for the custom JavaScript field.
		 *
		 * La válvula explícita para el caso legítimo en que haga falta abrirlo
		 * en una red: un `add_filter` que se ve en el repositorio y se audita,
		 * y no una concesión global y silenciosa de `unfiltered_html` como la
		 * del sistema anterior.
		 *
		 * Solo puede relajar la regla de multisitio. La capacidad
		 * `evt_edit_custom_js` y el acotado por área se comprueban antes y este
		 * filtro no los toca: quien no puede editar el evento sigue sin poder.
		 *
		 * @param bool $suelto  Whether the multisite rule is satisfied.
		 * @param int  $user_id User ID.
		 * @param int  $post_id Post ID.
		 */
		return (bool) apply_filters( 'evt_allow_custom_js', $suelto, $user_id, $post_id );
	}

	/**
	 * Shared rule of the two code fields: the capability and the event.
	 *
	 * @param int    $user_id User ID.
	 * @param int    $post_id Post ID.
	 * @param string $cap     Capability to require.
	 * @return bool
	 */
	private static function can_edit_code( int $user_id, int $post_id, string $cap ): bool {
		if ( $user_id <= 0 || ! user_can( $user_id, $cap ) ) {
			return false;
		}
		return self::can_edit( $user_id, $post_id );
	}

	/**
	 * Why the user cannot touch this event, in words.
	 *
	 * Vive aquí y no en cada pantalla para que todas lo cuenten igual.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Post ID.
	 * @return string Empty when there is nothing to explain.
	 */
	public static function why_not_editable( int $user_id, int $post_id ): string {
		if ( self::can_edit( $user_id, $post_id ) ) {
			return '';
		}
		// El cierre va primero porque es la regla de arriba: si el área podría
		// editarlo y no puede, es por esto y no por el área ni por el perfil.
		if ( self::can_open( $user_id, $post_id ) ) {
			return 'Este evento está marcado como histórico: se puede consultar y exportar, pero ya no se edita. Para volver a abrirlo, pídalo a quien administre el aplicativo.';
		}
		$cap = self::scoped_cap( $post_id );
		if ( '' === $cap || ! user_can( $user_id, $cap ) ) {
			return 'Su perfil no organiza eventos. Si debería hacerlo, pídalo a quien administre el aplicativo.';
		}
		if ( array() === self::user_areas( $user_id ) ) {
			return 'No tiene ningún área asignada en su perfil, así que no puede editar eventos. El área la pone quien administra el aplicativo.';
		}
		$nombres = array(
			EventPostType::POST_TYPE    => 'Este evento',
			SpeakerPostType::POST_TYPE  => 'Este ponente',
			ActivityPostType::POST_TYPE => 'Esta actividad',
		);
		$que     = $nombres[ (string) get_post_type( $post_id ) ] ?? 'Esto';
		return $que . ' es de otra área. Solo lo edita el área que lo organiza o quien administra el aplicativo.';
	}

	/**
	 * Turn the área policy into a denial for the core meta caps.
	 *
	 * Esta es la capa que de verdad protege: el acotado del listado solo
	 * esconde, y deja abiertos el enlace directo, la edición rápida y la REST.
	 *
	 * @param string[] $caps    Primitive caps.
	 * @param string   $cap     Meta cap.
	 * @param int      $user_id User ID.
	 * @param array    $args    Cap args (post ID in the first position).
	 * @return string[]
	 */
	public static function map_meta_cap( array $caps, string $cap, int $user_id, array $args ): array {
		if ( ! in_array( $cap, array( 'edit_post', 'delete_post', 'publish_post' ), true ) ) {
			return $caps;
		}
		$post_id = isset( $args[0] ) ? (int) $args[0] : 0;
		if ( $post_id <= 0 || ! isset( self::scoped_types()[ (string) get_post_type( $post_id ) ] ) ) {
			return $caps;
		}
		if ( ! self::can_edit( $user_id, $post_id ) ) {
			return array( 'do_not_allow' );
		}
		return $caps;
	}

	/**
	 * Keep positive integers only, without repeats.
	 *
	 * @param array<int, mixed> $values Raw values.
	 * @return int[]
	 */
	private static function clean_ids( array $values ): array {
		$out = array();
		foreach ( $values as $value ) {
			$id = (int) $value;
			if ( $id > 0 ) {
				$out[] = $id;
			}
		}
		return array_values( array_unique( $out ) );
	}
}
