<?php
/**
 * The public signup form of an event, and what it does when submitted.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

use Evt\Access\EventAccess;
use Evt\Domain\RegistrationInput;
use Evt\Meta\EventMetaKeys;
use Evt\Meta\RegistrationMetaKeys;
use Evt\PostType\EventPostType;
use Evt\PublicFront\Block\SignupBlock;

/**
 * El formulario de inscripción: quién lo puede ver, y qué pasa al enviarlo.
 *
 * Lee la petición y decide; **no pinta nada**, que es de
 * {@see \Evt\PublicFront\Block\SignupBlock}.
 *
 * Quien se inscribe **no tiene cuenta en el sitio y no la va a tener**
 * (ADR-0032). Así que:
 *
 * - Para volver a su inscripción —a cambiar de taller— entra con un **testigo**
 *   aleatorio que viaja en el enlace, no con su correo tecleado. Un dato que
 *   cualquiera puede escribir no es una credencial.
 * - El nonce sigue puesto contra el envío cruzado, pero **no puede ser lo único**:
 *   a una persona anónima WordPress le da el mismo nonce que a cualquier otra.
 *   Lo que protege de verdad es que un envío solo puede crear su propia
 *   inscripción, y que tocar una existente exige el testigo.
 */
final class SignupForm {

	/**
	 * The field that says this POST is ours, and what it wants.
	 */
	public const FIELD_OP = 'evt_signup_op';

	public const OP_SIGNUP   = 'inscribir';
	public const OP_WORKSHOP = 'taller';

	public const FIELD_EVENT   = 'evt_signup_event';
	public const FIELD_TOKEN   = 'evt_token';
	public const FIELD_ANSWERS = 'evt_q';

	public const NONCE_ACTION = 'evt_signup';
	public const NONCE_FIELD  = '_evt_signup_nonce';

	/**
	 * Query argument that carries the token back in a link.
	 */
	public const ARG_TOKEN = 'inscripcion';

	/**
	 * What the last submit left to say, for the block to paint.
	 *
	 * @var array{level:string, message:string}|null
	 */
	private static $notice = null;

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'maybe_handle_submit' ), 20 );
		// Antes de que se pinte la página del evento.
		add_action( 'template_redirect', array( self::class, 'require_login' ), EventView::PRIORITY - 1 );
	}

	/**
	 * Send whoever opens a signup that needs a session straight to log in.
	 *
	 * Sin sesión, la página de inscripción de un evento que la pide no tiene
	 * nada que hacer más que decir «inicie sesión»; así se ahorra el clic y se
	 * vuelve aquí con el formulario ya relleno con los datos de la cuenta. Una
	 * inscripción cerrada no redirige: lo que hay que leer es por qué.
	 *
	 * @return void
	 */
	public static function require_login(): void {
		if ( is_user_logged_in() || ! is_singular( EventPostType::POST_TYPE ) ) {
			return;
		}
		$seccion = get_queried_object_id();
		if ( SignupBlock::NAME !== get_post_meta( $seccion, EventMetaKeys::SECTION_TYPE, true ) ) {
			return;
		}
		$evento = EventAccess::root_id( $seccion );
		if ( '' !== self::closed_because( $evento ) || '' === self::login_needed( $evento ) ) {
			return;
		}
		Shell::leave( wp_login_url( (string) get_permalink( $seccion ) ) );
	}

	/**
	 * What the last submit left to say.
	 *
	 * @return array{level:string, message:string}|null
	 */
	public static function notice(): ?array {
		return self::$notice;
	}

	/**
	 * Take a submit, when it is ours.
	 *
	 * @return void
	 */
	public static function maybe_handle_submit(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- se comprueba justo debajo.
		$op = isset( $_POST[ self::FIELD_OP ] ) ? sanitize_key( wp_unslash( $_POST[ self::FIELD_OP ] ) ) : '';
		if ( self::OP_SIGNUP !== $op && self::OP_WORKSHOP !== $op ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- es la comprobación.
		$nonce = isset( $_POST[ self::NONCE_FIELD ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ) : '';
		if ( '' === $nonce || false === wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			self::$notice = array(
				'level'   => 'error',
				'message' => 'El formulario estuvo abierto demasiado tiempo y el envío caducó. Vuelva a enviarlo.',
			);
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- comprobado arriba.
		$raw      = (array) wp_unslash( $_POST );
		$event_id = isset( $raw[ self::FIELD_EVENT ] ) ? (int) $raw[ self::FIELD_EVENT ] : 0;

		if ( self::OP_SIGNUP === $op ) {
			self::signup( $event_id, $raw );
			return;
		}
		self::workshop( $event_id, $raw );
	}

	/**
	 * A new signup.
	 *
	 * @param int                  $event_id Event post ID.
	 * @param array<string, mixed> $raw      Submitted fields.
	 * @return void
	 */
	private static function signup( int $event_id, array $raw ): void {
		$porque = self::closed_because( $event_id );
		if ( '' === $porque ) {
			$porque = self::login_needed( $event_id );
		}
		if ( '' === $porque && ! is_user_logged_in() && Captcha::enabled() && ! Captcha::verify( (string) ( $raw[ Captcha::FIELD ] ?? '' ) ) ) {
			$porque = 'Marque la casilla «No soy un robot» y espere a que diga «Verificado» antes de enviar.';
		}
		if ( '' !== $porque ) {
			self::$notice = array(
				'level'   => 'error',
				'message' => $porque,
			);
			return;
		}

		$respuestas = isset( $raw[ self::FIELD_ANSWERS ] ) && is_array( $raw[ self::FIELD_ANSWERS ] )
			? $raw[ self::FIELD_ANSWERS ]
			: array();

		// Lo que la ficha ya dice manda sobre lo que llegue en el envío.
		$raw = array_merge( $raw, Registrations::from_profile() );
		$v   = Registrations::validate( $event_id, $raw, $respuestas );

		// Los ficheros se comprueban **antes** de crear nada: si una pregunta
		// obligatoria viene sin documento, o el que viene no pasa la política,
		// no llega a existir ninguna inscripción (ADR-0036).
		// Sin sesión no se recoge ningún fichero, venga lo que venga en la petición.
		$ficheros = RegistrationFiles::submitted( is_user_logged_in() ? Registrations::questions( $event_id ) : array() );

		if ( ! $v['ok'] || ! $ficheros['ok'] ) {
			$porque       = RegistrationFiles::why( $ficheros['errors'] );
			self::$notice = array(
				'level'   => 'error',
				'message' => '' !== $porque && $v['ok'] ? $porque : RegistrationInput::why( $v['errors'] ),
			);
			return;
		}

		$id = Registrations::create( $event_id, $v['core'], $v['answers'] );
		if ( $id <= 0 ) {
			self::$notice = array(
				'level'   => 'error',
				'message' => 'No se ha podido guardar la inscripción. Vuelva a intentarlo.',
			);
			return;
		}

		// Y si guardar un documento falla con la inscripción ya creada, se
		// deshace entera: ni inscripción a medias, ni fichero huérfano, ni
		// descriptor apuntando a nada. `RegistrationFiles::store_all()` ya ha
		// borrado los que había guardado antes de fallar.
		if ( ! RegistrationFiles::store_all( $id, $ficheros['files'] ) ) {
			wp_delete_post( $id, true );
			self::$notice = array(
				'level'   => 'error',
				'message' => 'No se ha podido guardar el documento que adjuntó, así que la inscripción no se ha registrado. Vuelva a intentarlo.',
			);
			return;
		}

		// El taller, si se pidió uno y su plazo está abierto, va por el mismo
		// camino que un cambio: el candado y el aforo mandan igual (ADR-0033).
		$taller = isset( $raw['evt_workshop'] ) ? (int) $raw['evt_workshop'] : 0;
		if ( $taller > 0 && self::workshops_open( $event_id ) ) {
			Registrations::seat( $event_id, $id, $taller );
		}

		Shell::leave( self::link( $event_id, (string) get_post_meta( $id, RegistrationMetaKeys::REG_TOKEN, true ) ) );
	}

	/**
	 * A workshop chosen or changed from an existing registration.
	 *
	 * @param int                  $event_id Event post ID.
	 * @param array<string, mixed> $raw      Submitted fields.
	 * @return void
	 */
	private static function workshop( int $event_id, array $raw ): void {
		$token = isset( $raw[ self::FIELD_TOKEN ] ) ? (string) $raw[ self::FIELD_TOKEN ] : '';
		$id    = Registrations::by_token( $event_id, $token );

		if ( $id <= 0 ) {
			self::$notice = array(
				'level'   => 'error',
				'message' => 'Este enlace ya no sirve. Pida uno nuevo desde el correo de confirmación.',
			);
			return;
		}
		if ( ! self::workshops_open( $event_id ) ) {
			self::$notice = array(
				'level'   => 'error',
				'message' => 'El plazo para elegir taller está cerrado.',
			);
			return;
		}

		$res = Registrations::seat( $event_id, $id, isset( $raw['evt_workshop'] ) ? (int) $raw['evt_workshop'] : 0 );
		if ( ! $res['ok'] ) {
			self::$notice = array(
				'level'   => 'error',
				'message' => self::why_no_seat( $res['error'] ),
			);
			return;
		}

		Shell::leave( self::link( $event_id, $token ) );
	}

	/**
	 * Why a seat was refused, in Spanish.
	 *
	 * @param string $error Error code.
	 * @return string
	 */
	public static function why_no_seat( string $error ): string {
		$textos = array(
			'sin_plazas' => 'Ese taller se ha llenado mientras rellenaba la página. Elija otro de la lista, que está al día.',
			'ocupado'    => 'Hay varias personas eligiendo taller a la vez. Vuelva a intentarlo en unos segundos.',
		);
		return $textos[ $error ] ?? 'No se ha podido guardar la elección.';
	}

	/**
	 * The link that opens one registration again.
	 *
	 * @param int    $event_id Event post ID.
	 * @param string $token    Its token.
	 * @return string
	 */
	public static function link( int $event_id, string $token ): string {
		$pagina = self::page( $event_id );
		$url    = $pagina > 0 ? (string) get_permalink( $pagina ) : (string) get_permalink( $event_id );
		return '' === $token ? $url : add_query_arg( self::ARG_TOKEN, rawurlencode( $token ), $url );
	}

	/**
	 * The signup section of an event, 0 when it has none.
	 *
	 * @param int $event_id Event post ID.
	 * @return int
	 */
	public static function page( int $event_id ): int {
		$hijas = get_posts(
			array(
				'post_type'        => \Evt\PostType\EventPostType::POST_TYPE,
				'post_parent'      => $event_id,
				'post_status'      => 'publish',
				'numberposts'      => 1,
				'fields'           => 'ids',
				'meta_key'         => \Evt\Meta\EventMetaKeys::SECTION_TYPE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'       => 'inscripcion', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'suppress_filters' => false,
			)
		);
		return is_array( $hijas ) && array() !== $hijas ? (int) $hijas[0] : 0;
	}

	/**
	 * The registration the current request is looking at, 0 for none.
	 *
	 * @param int $event_id Event post ID.
	 * @return int
	 */
	public static function current( int $event_id ): int {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- un testigo de lectura, no una escritura.
		$token = isset( $_GET[ self::ARG_TOKEN ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::ARG_TOKEN ] ) ) : '';
		return '' === $token ? 0 : Registrations::by_token( $event_id, $token );
	}

	/**
	 * Whether the signup form of an event is open.
	 *
	 * Abierta es todo a la vez: el interruptor puesto, dentro de plazo si tiene
	 * fechas, el evento publicado y sin marcar como histórico. Un borrador o
	 * un evento cerrado no apuntan a nadie aunque se quedara el interruptor.
	 *
	 * @param int $event_id Event post ID.
	 * @return bool
	 */
	public static function is_open( int $event_id ): bool {
		return '' === self::closed_because( $event_id );
	}

	/**
	 * Why nobody may sign up to this event right now, '' when they may.
	 *
	 * @param int $event_id Event post ID.
	 * @return string Spanish message for the visitor.
	 */
	public static function closed_because( int $event_id ): string {
		$cerrada = 'La inscripción de este evento no está abierta.';
		if ( 'publish' !== get_post_status( $event_id )
			|| EventAccess::is_archived( $event_id )
			|| ! get_post_meta( $event_id, RegistrationMetaKeys::SIGNUP_OPEN, true ) ) {
			return $cerrada;
		}

		$hoy   = current_time( 'Y-m-d' );
		$desde = (string) get_post_meta( $event_id, RegistrationMetaKeys::SIGNUP_START, true );
		$hasta = (string) get_post_meta( $event_id, RegistrationMetaKeys::SIGNUP_END, true );
		if ( '' !== $desde && $hoy < $desde ) {
			return 'La inscripción de este evento se abre el ' . self::day( $desde ) . '.';
		}
		if ( '' !== $hasta && $hoy > $hasta ) {
			return 'El plazo de inscripción de este evento terminó el ' . self::day( $hasta ) . '.';
		}
		return '';
	}

	/**
	 * Whether people who have not logged in may sign up to this event.
	 *
	 * @param int $event_id Event post ID.
	 * @return bool
	 */
	public static function is_public( int $event_id ): bool {
		return (bool) get_post_meta( $event_id, RegistrationMetaKeys::SIGNUP_PUBLIC, true );
	}

	/**
	 * Why the current visitor has to log in first, '' when they need not.
	 *
	 * Adjuntar un documento pide sesión siempre: sin ella no hay a quién
	 * pedirle cuentas de lo que se sube. Por eso, en una inscripción pública,
	 * una pregunta de archivo **obligatoria** también manda a iniciar sesión.
	 *
	 * @param int $event_id Event post ID.
	 * @return string Spanish message for the visitor.
	 */
	public static function login_needed( int $event_id ): string {
		if ( is_user_logged_in() ) {
			return '';
		}
		if ( ! self::is_public( $event_id ) ) {
			return 'Para inscribirse en este evento hay que iniciar sesión.';
		}
		foreach ( Registrations::questions( $event_id ) as $pregunta ) {
			if ( 'file' === $pregunta['type'] && ! empty( $pregunta['required'] ) ) {
				return 'Esta inscripción pide adjuntar un documento, y para eso hay que iniciar sesión.';
			}
		}
		return '';
	}

	/**
	 * A stored Y-m-d date, as people read it.
	 *
	 * @param string $ymd Date.
	 * @return string
	 */
	private static function day( string $ymd ): string {
		$fecha = \DateTimeImmutable::createFromFormat( '!Y-m-d', $ymd, wp_timezone() );
		return false === $fecha ? $ymd : wp_date( 'j/n/Y', $fecha->getTimestamp() );
	}

	/**
	 * Whether workshops can be chosen right now.
	 *
	 * Tiene plazo propio, con sus fechas, porque se abre cuando quien organiza
	 * ha cerrado el programa y eso casi nunca coincide con abrir la inscripción
	 * (ADR-0033). Las fechas son opcionales: sin ellas manda el interruptor.
	 *
	 * @param int $event_id Event post ID.
	 * @return bool
	 */
	public static function workshops_open( int $event_id ): bool {
		if ( ! get_post_meta( $event_id, RegistrationMetaKeys::WORKSHOP_OPEN, true ) ) {
			return false;
		}

		$hoy   = current_time( 'Y-m-d' );
		$desde = (string) get_post_meta( $event_id, RegistrationMetaKeys::WORKSHOP_START, true );
		$hasta = (string) get_post_meta( $event_id, RegistrationMetaKeys::WORKSHOP_END, true );

		return ( '' === $desde || $hoy >= $desde ) && ( '' === $hasta || $hoy <= $hasta );
	}
}
