<?php
/**
 * Attendance: checkpoints with a QR, marking it with the session, and the paper sheet.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

use Evt\Access\EventAccess;
use Evt\Meta\RegistrationMetaKeys;
use Evt\PostType\EventPostType;

/**
 * El control de asistencia, lo más sencillo que sirve (ADR-0053).
 *
 * Quien organiza crea **puntos de control** —«Entrada», «Mediodía»,
 * «Salida»— y cada uno tiene un enlace con su QR, que se imprime o se proyecta.
 * Quien asiste lo escanea, entra **con su cuenta** y pulsa un botón: queda la
 * hora. No se teclea nada, y no se puede marcar por otra persona porque la
 * cuenta dice quién es ({@see Registrations::of_user()}).
 *
 * Un punto **solo marca mientras está abierto**: se abre cuando se proyecta y
 * se cierra al acabar, y así una foto del QR no sirve para fichar desde casa.
 *
 * Y el plan B, porque siempre hay a quien no le va el móvil: la organización
 * marca a mano desde la misma pestaña, y hay una **hoja de firmas** para
 * imprimir. Las marcas a mano quedan como tales.
 */
final class Attendance {

	/**
	 * Query argument that carries the checkpoint token in the QR link.
	 */
	public const ARG_TOKEN = 'asistencia';

	/**
	 * Query argument that asks the workshop for the printable sheet.
	 */
	public const ARG_SHEET = 'hoja';

	/**
	 * Fields and nonce of the «mark my attendance» button.
	 */
	public const FIELD_OP     = 'evt_attendance_op';
	public const OP_MARK      = 'marcar';
	public const FIELD_EVENT  = 'evt_attendance_event';
	public const FIELD_TOKEN  = 'evt_attendance_token';
	public const NONCE_ACTION = 'evt_attendance';
	public const NONCE_FIELD  = '_evt_attendance_nonce';

	/**
	 * How a mark was made.
	 */
	public const BY_QR     = 'qr';
	public const BY_MANUAL = 'manual';

	/**
	 * How many checkpoints an event may have. Tres es lo normal; diez sobra.
	 */
	public const MAX_POINTS = 10;

	/**
	 * The QR library, pinned: the same version here, in the URL and in `package.json`.
	 *
	 * Hash computed with: curl -s <url> | openssl dgst -sha384 -binary | openssl base64 -A
	 *
	 * @var array<string, array{url:string, sri:string}>
	 */
	public const VENDOR = array(
		'evt-qrcode' => array(
			'url' => 'https://cdn.jsdelivr.net/npm/qrcode-generator@2.0.4/dist/qrcode.js',
			'sri' => 'sha384-e9EFD6BGC90bkW9aDV5xbbBfzwN7G8YImHao2lfLVKV/hPB0E0go+H3I64h7oHtA',
		),
	);

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'maybe_handle_submit' ), 20 );
		// Antes de que se pinte la página del evento y antes de que el armazón
		// sirva la pantalla del taller.
		add_action( 'template_redirect', array( self::class, 'require_login' ), EventView::PRIORITY - 2 );
		add_action( 'template_redirect', array( self::class, 'maybe_print_sheet' ), 15 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_qr' ), 20 );
		add_filter( 'script_loader_tag', array( self::class, 'integrity' ), 10, 3 );
		add_filter( 'body_class', array( self::class, 'body_class' ) );
	}

	/**
	 * Mark the page opened from a QR, so it shows only the attendance box.
	 *
	 * Quien escanea está en una cola con el móvil en la mano: lo que necesita
	 * es el botón, no el texto de la página de inscripción ni el cartel. La
	 * hoja esconde los demás bloques de la sección con esta clase.
	 *
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public static function body_class( array $classes ): array {
		if ( '' !== self::token_in_request() && is_singular( EventPostType::POST_TYPE ) ) {
			$classes[] = 'evt-ev--asistencia';
		}
		return $classes;
	}

	// ─── leer ──────────────────────────────────────────────────────────────

	/**
	 * A clean list of checkpoints, from JSON or from an array.
	 *
	 * @param mixed $value Stored or submitted value.
	 * @return array<int, array{id:string, label:string, token:string, open:bool}>
	 */
	public static function read_points( $value ): array {
		$lista = is_array( $value ) ? $value : json_decode( is_scalar( $value ) ? (string) $value : '', true );
		$out   = array();
		foreach ( is_array( $lista ) ? $lista : array() as $punto ) {
			if ( ! is_array( $punto ) ) {
				continue;
			}
			$id    = strtolower( (string) ( $punto['id'] ?? '' ) );
			$token = strtolower( (string) ( $punto['token'] ?? '' ) );
			$label = mb_substr( trim( sanitize_text_field( (string) ( $punto['label'] ?? '' ) ) ), 0, 60 );
			if ( ! preg_match( '/^[a-f0-9]{8}$/', $id ) || ! preg_match( '/^[a-f0-9]{32}$/', $token ) || '' === $label ) {
				continue;
			}
			$out[] = array(
				'id'    => $id,
				'label' => $label,
				'token' => $token,
				'open'  => ! empty( $punto['open'] ),
			);
			if ( count( $out ) >= self::MAX_POINTS ) {
				break;
			}
		}
		return $out;
	}

	/**
	 * Clean attendance marks, from JSON or from an array.
	 *
	 * @param mixed $value Stored value.
	 * @return array<string, array{at:string, by:string}>
	 */
	public static function read_marks( $value ): array {
		$mapa = is_array( $value ) ? $value : json_decode( is_scalar( $value ) ? (string) $value : '', true );
		$out  = array();
		foreach ( is_array( $mapa ) ? $mapa : array() as $id => $marca ) {
			$id = strtolower( (string) $id );
			if ( ! preg_match( '/^[a-f0-9]{8}$/', $id ) || ! is_array( $marca ) ) {
				continue;
			}
			$cuando = (string) ( $marca['at'] ?? '' );
			if ( ! preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $cuando ) ) {
				continue;
			}
			$out[ $id ] = array(
				'at' => $cuando,
				'by' => self::BY_MANUAL === ( $marca['by'] ?? '' ) ? self::BY_MANUAL : self::BY_QR,
			);
		}
		return $out;
	}

	/**
	 * The checkpoints of an event.
	 *
	 * @param int $event_id Event post ID.
	 * @return array<int, array{id:string, label:string, token:string, open:bool}>
	 */
	public static function points( int $event_id ): array {
		return self::read_points( get_post_meta( $event_id, RegistrationMetaKeys::ATTENDANCE_POINTS, true ) );
	}

	/**
	 * The marks of one registration.
	 *
	 * @param int $registration_id Registration post ID.
	 * @return array<string, array{at:string, by:string}>
	 */
	public static function marks( int $registration_id ): array {
		return self::read_marks( get_post_meta( $registration_id, RegistrationMetaKeys::REG_ATTENDANCE, true ) );
	}

	/**
	 * The checkpoint a QR token opens, or null.
	 *
	 * @param int    $event_id Event post ID.
	 * @param string $token    Token from the link.
	 * @return array{id:string, label:string, token:string, open:bool}|null
	 */
	public static function point_by_token( int $event_id, string $token ): ?array {
		$token = strtolower( trim( $token ) );
		if ( ! preg_match( '/^[a-f0-9]{32}$/', $token ) ) {
			return null;
		}
		foreach ( self::points( $event_id ) as $punto ) {
			if ( hash_equals( $punto['token'], $token ) ) {
				return $punto;
			}
		}
		return null;
	}

	/**
	 * The link a checkpoint QR opens: the signup page of the event, with the token.
	 *
	 * @param int                  $event_id Event post ID.
	 * @param array<string, mixed> $punto    Checkpoint.
	 * @return string
	 */
	public static function url( int $event_id, array $punto ): string {
		return add_query_arg( self::ARG_TOKEN, (string) $punto['token'], SignupForm::link( $event_id, '' ) );
	}

	/**
	 * The checkpoint token the current request carries, '' for none.
	 *
	 * @return string
	 */
	public static function token_in_request(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- un testigo de lectura; marcar lleva su nonce.
		return isset( $_GET[ self::ARG_TOKEN ] ) ? sanitize_key( wp_unslash( $_GET[ self::ARG_TOKEN ] ) ) : '';
	}

	// ─── escribir ──────────────────────────────────────────────────────────

	/**
	 * Mark one checkpoint for one registration. The first mark stays.
	 *
	 * Escanear dos veces no mueve la hora: la entrada es cuando se entró la
	 * primera vez.
	 *
	 * @param int    $registration_id Registration post ID.
	 * @param string $point_id        Checkpoint ID.
	 * @param string $by              BY_QR or BY_MANUAL.
	 * @return string When it is marked, «Y-m-d H:i:s».
	 */
	public static function mark( int $registration_id, string $point_id, string $by = self::BY_QR ): string {
		$marcas = self::marks( $registration_id );
		if ( ! isset( $marcas[ $point_id ] ) ) {
			$marcas[ $point_id ] = array(
				'at' => current_time( 'mysql' ),
				'by' => self::BY_MANUAL === $by ? self::BY_MANUAL : self::BY_QR,
			);
			update_post_meta( $registration_id, RegistrationMetaKeys::REG_ATTENDANCE, wp_slash( (string) wp_json_encode( $marcas ) ) );
		}
		return $marcas[ $point_id ]['at'];
	}

	/**
	 * Apply the manual marks of the organisation for one registration.
	 *
	 * Lo marcado que no estaba se marca ahora, a mano; lo desmarcado se quita,
	 * venga del QR o de otra mano. Lo que ya estaba se queda con su hora.
	 *
	 * @param int      $registration_id Registration post ID.
	 * @param string[] $checked         Checkpoint IDs ticked.
	 * @param string[] $all             Every checkpoint ID of the event.
	 * @return void
	 */
	public static function set_manual( int $registration_id, array $checked, array $all ): void {
		$marcas = self::marks( $registration_id );
		$antes  = $marcas;
		foreach ( $all as $id ) {
			$puesto = in_array( $id, $checked, true );
			if ( $puesto && ! isset( $marcas[ $id ] ) ) {
				$marcas[ $id ] = array(
					'at' => current_time( 'mysql' ),
					'by' => self::BY_MANUAL,
				);
			} elseif ( ! $puesto ) {
				unset( $marcas[ $id ] );
			}
		}
		if ( $marcas !== $antes ) {
			update_post_meta( $registration_id, RegistrationMetaKeys::REG_ATTENDANCE, wp_slash( (string) wp_json_encode( $marcas ) ) );
		}
	}

	/**
	 * The checkpoints as the organiser left them in the form.
	 *
	 * Campos paralelos, sin JavaScript, como las preguntas: la fila en blanco del
	 * final añade un punto; una sin rótulo, o con «quitar», desaparece. Un punto
	 * que ya existía **conserva su testigo**, así que el QR impreso sigue
	 * sirviendo aunque se le cambie el nombre.
	 *
	 * @param array<int, array{id:string, label:string, token:string, open:bool}> $antes   Stored checkpoints.
	 * @param array<int, array<string, mixed>>                                    $filas   Submitted rows: id, label, open, remove.
	 * @return array<int, array{id:string, label:string, token:string, open:bool}>
	 */
	public static function merge_points( array $antes, array $filas ): array {
		$por_id = array();
		foreach ( $antes as $punto ) {
			$por_id[ $punto['id'] ] = $punto;
		}
		$out = array();
		foreach ( $filas as $fila ) {
			$rotulo = trim( (string) ( $fila['label'] ?? '' ) );
			if ( '' === $rotulo || ! empty( $fila['remove'] ) ) {
				continue;
			}
			$id    = (string) ( $fila['id'] ?? '' );
			$viejo = $por_id[ $id ] ?? null;
			$out[] = array(
				'id'    => null !== $viejo ? $viejo['id'] : bin2hex( random_bytes( 4 ) ),
				'label' => $rotulo,
				'token' => null !== $viejo ? $viejo['token'] : bin2hex( random_bytes( 16 ) ),
				'open'  => ! empty( $fila['open'] ),
			);
		}
		return self::read_points( $out );
	}

	// ─── la persona que escanea ────────────────────────────────────────────

	/**
	 * Send whoever opens a QR link without a session to log in, and back.
	 *
	 * @return void
	 */
	public static function require_login(): void {
		if ( is_user_logged_in() || '' === self::token_in_request() || ! is_singular( EventPostType::POST_TYPE ) ) {
			return;
		}
		$aqui = add_query_arg( self::ARG_TOKEN, self::token_in_request(), (string) get_permalink( get_queried_object_id() ) );
		Shell::leave( wp_login_url( $aqui ) );
	}

	/**
	 * Take the «mark my attendance» button, when it is ours.
	 *
	 * @return void
	 */
	public static function maybe_handle_submit(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- se comprueba justo debajo.
		if ( self::OP_MARK !== sanitize_key( wp_unslash( (string) ( $_POST[ self::FIELD_OP ] ?? '' ) ) ) ) {
			return;
		}
		$nonce    = sanitize_text_field( wp_unslash( (string) ( $_POST[ self::NONCE_FIELD ] ?? '' ) ) );
		$event_id = absint( wp_unslash( $_POST[ self::FIELD_EVENT ] ?? 0 ) );
		$token    = sanitize_key( wp_unslash( (string) ( $_POST[ self::FIELD_TOKEN ] ?? '' ) ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		if ( ! is_user_logged_in() || false === wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		$punto = self::point_by_token( $event_id, $token );
		$mia   = Registrations::of_user( $event_id );
		if ( null !== $punto && true === $punto['open'] && $mia > 0 ) {
			self::mark( $mia, $punto['id'], self::BY_QR );
		}
		// Se vuelve a la misma página y ella dice lo que ha pasado: marcado a
		// tal hora, cerrado o sin inscripción. Recargar no vuelve a enviar.
		Shell::leave( null !== $punto ? self::url( $event_id, $punto ) : SignupForm::link( $event_id, '' ) );
	}

	/**
	 * What the person who scanned a QR sees, instead of the signup form.
	 *
	 * @param int $event_id Event post ID.
	 * @return string
	 */
	public static function block( int $event_id ): string {
		return '<div class="evt-asistencia">' . self::block_inner( $event_id ) . '</div>';
	}

	/**
	 * What goes inside the attendance box, by state.
	 *
	 * @param int $event_id Event post ID.
	 * @return string
	 */
	private static function block_inner( int $event_id ): string {
		$punto = self::point_by_token( $event_id, self::token_in_request() );
		if ( null === $punto ) {
			return self::say( 'warning', 'Este código de asistencia no es de este evento, o ya no existe. Avise a la organización.' );
		}
		$html = '<p class="evt-asistencia__evento">' . esc_html( (string) get_the_title( $event_id ) ) . '</p>'
			. '<h2 class="evt-asistencia__titulo">Control de asistencia: ' . esc_html( $punto['label'] ) . '</h2>';

		if ( ! is_user_logged_in() ) {
			return $html . self::say( 'info', 'Para confirmar su asistencia, inicie sesión con su cuenta.' )
				. sprintf( '<p><a class="%1$s evt-asistencia__boton" href="%2$s">Iniciar sesión</a></p>', esc_attr( Assets::button_class( true ) ), esc_url( wp_login_url( self::url( $event_id, $punto ) ) ) );
		}

		$mia = Registrations::of_user( $event_id );
		if ( $mia <= 0 ) {
			$user = wp_get_current_user();
			return $html . self::say(
				'warning',
				'No encontramos ninguna inscripción en este evento hecha con su cuenta (' . $user->user_email . '). '
					. 'Avise a la organización: le registrarán la asistencia a mano.'
			);
		}

		$marcas = self::marks( $mia );
		if ( isset( $marcas[ $punto['id'] ] ) ) {
			// Grande y con la hora bien visible: es lo que se enseña a quien
			// pregunte en la puerta.
			return $html . sprintf(
				'<div class="%1$s evt-asistencia__hecha" role="status"><span class="evt-asistencia__tic" aria-hidden="true">✓</span>'
					. '<strong>Asistencia confirmada</strong><span class="evt-asistencia__hora">%2$s</span>'
					. '<span>%3$s. Ya puede cerrar esta página.</span></div>',
				esc_attr( Assets::alert_class( 'success' ) ),
				esc_html( self::time( $marcas[ $punto['id'] ]['at'] ) ),
				esc_html( $punto['label'] )
			);
		}
		if ( true !== $punto['open'] ) {
			return $html . self::say( 'info', 'Este control de asistencia no está abierto ahora. Vuelva a escanear el código cuando la organización lo indique.' );
		}

		$meta   = Registrations::meta( $mia );
		$nombre = trim( $meta[ RegistrationMetaKeys::REG_NAME ] . ' ' . $meta[ RegistrationMetaKeys::REG_SURNAME ] );

		return $html . '<p class="evt-asistencia__nombre">' . esc_html( $nombre ) . '</p>'
			. '<p class="evt-asistencia__ayuda">Pulse el botón para dejar constancia de que está aquí. Se guardará la hora.</p>'
			. '<form method="post" class="evt-asistencia">'
			. wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD, true, false )
			. sprintf(
				'<input type="hidden" name="%1$s" value="%2$s"><input type="hidden" name="%3$s" value="%4$d"><input type="hidden" name="%5$s" value="%6$s">',
				esc_attr( self::FIELD_OP ),
				esc_attr( self::OP_MARK ),
				esc_attr( self::FIELD_EVENT ),
				$event_id,
				esc_attr( self::FIELD_TOKEN ),
				esc_attr( $punto['token'] )
			)
			. '<p><button type="submit" class="' . esc_attr( Assets::button_class( true ) ) . ' btn-lg evt-asistencia__boton">Confirmar asistencia</button></p>'
			. '</form>'
			. '<p class="evt-asistencia__ayuda">¿No es usted? <a href="' . esc_url( wp_logout_url( self::url( $event_id, $punto ) ) ) . '">Cierre la sesión</a> y entre con su cuenta.</p>';
	}

	// ─── la organización ───────────────────────────────────────────────────

	/**
	 * One row per registration with its marks, for the tab, the CSV and the sheet.
	 *
	 * @param int $event_id    Event post ID.
	 * @param int $workshop_id Only the people of this workshop; 0 for everybody.
	 * @return array<int, array{id:int, name:string, surname:string, tax_id:string, centre:string, workshops:string, marks:array<string, array{at:string, by:string}>}>
	 */
	public static function rows( int $event_id, int $workshop_id = 0 ): array {
		$rotulos = Registrations::workshop_labels( $event_id );
		$out     = array();
		foreach ( Registrations::all( $event_id ) as $inscripcion ) {
			$id    = (int) $inscripcion->ID;
			$suyos = Registrations::workshops_of( $id );
			if ( $workshop_id > 0 && ! in_array( $workshop_id, $suyos, true ) ) {
				continue;
			}
			$meta  = Registrations::meta( $id );
			$out[] = array(
				'id'        => $id,
				'name'      => $meta[ RegistrationMetaKeys::REG_NAME ],
				'surname'   => $meta[ RegistrationMetaKeys::REG_SURNAME ],
				'tax_id'    => $meta[ RegistrationMetaKeys::REG_TAX_ID ],
				'centre'    => $meta[ RegistrationMetaKeys::REG_CENTRE ],
				'workshops' => implode( Participants::SEPARATOR, array_values( array_intersect_key( $rotulos, array_flip( $suyos ) ) ) ),
				'marks'     => self::marks( $id ),
			);
		}
		// Por apellidos, que es como se busca a alguien en una hoja impresa.
		usort(
			$out,
			static function ( array $a, array $b ): int {
				return strcoll( $a['surname'] . ' ' . $a['name'], $b['surname'] . ' ' . $b['name'] );
			}
		);
		return $out;
	}

	/**
	 * The attendance of the event as a CSV, one column per checkpoint.
	 *
	 * @param int $event_id Event post ID.
	 * @return string
	 */
	public static function csv( int $event_id ): string {
		$puntos    = self::points( $event_id );
		$cabeceras = array_merge( array( 'Apellidos', 'Nombre', 'Documento', 'Centro', 'Taller' ), wp_list_pluck( $puntos, 'label' ) );
		$filas     = array();
		foreach ( self::rows( $event_id ) as $fila ) {
			$linea = array( $fila['surname'], $fila['name'], $fila['tax_id'], $fila['centre'], $fila['workshops'] );
			foreach ( $puntos as $punto ) {
				$marca   = $fila['marks'][ $punto['id'] ] ?? null;
				$linea[] = null === $marca ? '' : $marca['at'] . ( self::BY_MANUAL === $marca['by'] ? ' (a mano)' : '' );
			}
			$filas[] = $linea;
		}
		return Participants::table_csv( $cabeceras, $filas );
	}

	/**
	 * Serve the printable sign-in sheet, when the workshop asks for it.
	 *
	 * Un documento suelto, sin el armazón, que se imprime con el diálogo del
	 * navegador. Con datos personales, así que la puerta es la misma que la de
	 * la pestaña: quien puede abrir el evento.
	 *
	 * @return void
	 */
	public static function maybe_print_sheet(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- lectura de quien ya puede abrir el evento; no escribe nada.
		if ( ! isset( $_GET[ self::ARG_SHEET ] ) || ! Shell::is_app_page() ) {
			return;
		}
		$event_id = absint( wp_unslash( $_GET[ EventWorkspace::ARG_EVENT ] ?? 0 ) );
		$taller   = absint( wp_unslash( $_GET[ EventWorkspace::ARG_WORKSHOP ] ?? 0 ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		if ( $event_id <= 0 || ! EventAccess::can_open( get_current_user_id(), $event_id ) ) {
			return;
		}
		nocache_headers();
		Shell::send_header( 'Content-Type: text/html; charset=utf-8' );
		echo self::sheet( $event_id, $taller ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		Shell::leave();
	}

	/**
	 * The sign-in sheet: one line per person, one signature column per checkpoint.
	 *
	 * @param int $event_id    Event post ID.
	 * @param int $workshop_id Only this workshop; 0 for everybody.
	 * @return string A whole HTML document.
	 */
	public static function sheet( int $event_id, int $workshop_id = 0 ): string {
		$puntos  = self::points( $event_id );
		$firmas  = array() !== $puntos ? wp_list_pluck( $puntos, 'label' ) : array( 'Firma' );
		$rotulos = Registrations::workshop_labels( $event_id );
		$titulo  = (string) get_the_title( $event_id );
		$sub     = $workshop_id > 0 && isset( $rotulos[ $workshop_id ] ) ? 'Taller: ' . $rotulos[ $workshop_id ] : 'Todas las personas inscritas';

		ob_start();
		?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8" />
<title><?php echo esc_html( 'Hoja de firmas — ' . $titulo ); ?></title>
<style>
	body { font: 11pt/1.35 system-ui, sans-serif; margin: 1.5cm; color: #000; }
	h1 { font-size: 15pt; margin: 0 0 .2em; }
	p { margin: 0 0 1em; }
	table { width: 100%; border-collapse: collapse; }
	th, td { border: 1px solid #000; padding: 6px 8px; text-align: left; vertical-align: top; }
	th { background: #eee; }
	td.firma { width: 14%; height: 1.4cm; }
	td.n { width: 2em; text-align: right; }
	.no-imprimir { margin-bottom: 1em; }
	@media print { .no-imprimir { display: none; } body { margin: 0; } thead { display: table-header-group; } tr { break-inside: avoid; } }
</style>
</head>
<body>
<p class="no-imprimir"><button type="button" onclick="window.print()">Imprimir</button></p>
<h1><?php echo esc_html( $titulo ); ?></h1>
<p><?php echo esc_html( $sub ); ?> · Hoja de firmas</p>
<table>
	<thead>
		<tr>
			<th class="n">N.º</th>
			<th>Apellidos y nombre</th>
			<th>Documento</th>
			<th>Centro</th>
			<?php foreach ( $firmas as $firma ) : ?>
				<th><?php echo esc_html( (string) $firma ); ?></th>
			<?php endforeach; ?>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( self::rows( $event_id, $workshop_id ) as $i => $fila ) : ?>
			<tr>
				<td class="n"><?php echo esc_html( (string) ( $i + 1 ) ); ?></td>
				<td><?php echo esc_html( trim( $fila['surname'] . ', ' . $fila['name'], ', ' ) ); ?></td>
				<td><?php echo esc_html( $fila['tax_id'] ); ?></td>
				<td><?php echo esc_html( $fila['centre'] ); ?></td>
				<?php foreach ( $firmas as $firma ) : ?>
					<td class="firma"></td>
				<?php endforeach; ?>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
</body>
</html>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Load the QR library on the attendance tab only.
	 *
	 * @return void
	 */
	public static function enqueue_qr(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo decide qué guion se carga.
		$panel = sanitize_key( wp_unslash( (string) ( $_GET[ EventWorkspace::ARG_PANEL ] ?? '' ) ) );
		if ( EventWorkspace::PANEL_ATTENDANCE !== $panel || ! Shell::is_app_page() ) {
			return;
		}
		// `null` de versión: la versión ya va en la URL, y el `?ver=` rompería el SRI.
		wp_enqueue_script( 'evt-qrcode', self::VENDOR['evt-qrcode']['url'], array(), null, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_add_inline_script( 'evt-qrcode', Assets::contents( 'js/evt-qr.js' ) );
	}

	/**
	 * Print the integrity of the library while it still comes from the CDN.
	 *
	 * @param string $tag    The tag WordPress is about to print.
	 * @param string $handle Its handle.
	 * @param string $src    Its URL.
	 * @return string
	 */
	public static function integrity( $tag, $handle, $src ): string {
		if ( ! isset( self::VENDOR[ $handle ] ) || 0 !== strpos( (string) $src, 'https://cdn.jsdelivr.net/' ) ) {
			return (string) $tag;
		}
		$atributos = ' integrity="' . esc_attr( self::VENDOR[ $handle ]['sri'] ) . '" crossorigin="anonymous"';
		return (string) preg_replace( '/^<script\b/', '<script' . $atributos, (string) $tag, 1 );
	}

	/**
	 * A stored time as people read it: «9:42».
	 *
	 * @param string $mysql «Y-m-d H:i:s» in the site time zone.
	 * @return string
	 */
	public static function time( string $mysql ): string {
		$fecha = \DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $mysql, wp_timezone() );
		return false === $fecha ? $mysql : wp_date( 'G:i', $fecha->getTimestamp() );
	}

	/**
	 * One notice.
	 *
	 * @param string $tono success | info | warning.
	 * @param string $text What to say.
	 * @return string
	 */
	private static function say( string $tono, string $text ): string {
		return '<p class="' . esc_attr( Assets::alert_class( $tono ) ) . '" role="status">' . esc_html( $text ) . '</p>';
	}
}
