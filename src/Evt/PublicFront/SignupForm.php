<?php
/**
 * The public signup form of an event, and what it does when submitted.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

use Evt\Domain\RegistrationInput;
use Evt\Meta\RegistrationMetaKeys;

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
		if ( ! self::is_open( $event_id ) ) {
			self::$notice = array(
				'level'   => 'error',
				'message' => 'La inscripción de este evento no está abierta.',
			);
			return;
		}

		$respuestas = isset( $raw[ self::FIELD_ANSWERS ] ) && is_array( $raw[ self::FIELD_ANSWERS ] )
			? $raw[ self::FIELD_ANSWERS ]
			: array();

		$v = Registrations::validate( $event_id, $raw, $respuestas );
		if ( ! $v['ok'] ) {
			self::$notice = array(
				'level'   => 'error',
				'message' => RegistrationInput::why( $v['errors'] ),
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
				'numberposts'      => -1,
				'meta_key'         => \Evt\Meta\EventMetaKeys::SECTION_TYPE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'       => 'inscripcion', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'suppress_filters' => false,
			)
		);
		return is_array( $hijas ) && array() !== $hijas ? (int) $hijas[0]->ID : 0;
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
	 * @param int $event_id Event post ID.
	 * @return bool
	 */
	public static function is_open( int $event_id ): bool {
		return (bool) get_post_meta( $event_id, RegistrationMetaKeys::SIGNUP_OPEN, true );
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
