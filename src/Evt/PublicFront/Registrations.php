<?php
/**
 * Signups of one event: store, read, and take a workshop seat.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

use Evt\Domain\RegistrationInput;
use Evt\Domain\SignupQuestions;
use Evt\Domain\WorkshopChoice;
use Evt\Meta\ProgrammeMetaKeys;
use Evt\Meta\RegistrationMetaKeys;
use Evt\PostType\RegistrationPostType;

/**
 * Las inscripciones de un evento: guardarlas, leerlas y coger plaza.
 *
 * Una inscripción cuelga de su evento (ADR-0032). Aquí no se pinta nada y no
 * se lee la petición: eso es de las pantallas.
 *
 * **Lo único de todo el aplicativo donde dos personas hacen a la vez algo sobre
 * el mismo dato** es coger la última plaza de un taller. Por eso vive aquí
 * {@see seat()}, y por eso tiene un candado de verdad y no una comprobación
 * optimista (ADR-0033).
 */
final class Registrations {

	/**
	 * How long a held lock stays valid, in seconds.
	 *
	 * Corta a propósito: una petición que muera a mitad no puede dejar el
	 * evento bloqueado para siempre. Diez segundos son mucho más de lo que
	 * tarda contar unas plazas y escribir una meta, y poco para esperar.
	 */
	public const LOCK_TTL = 10;

	/**
	 * How long we keep trying to take the lock, in microseconds.
	 */
	private const LOCK_WAIT = 2000000;

	/**
	 * Prefix of the option that holds the lock of one event.
	 */
	private const LOCK_PREFIX = 'evt_seat_lock_';

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		// El aplicativo contesta a su propio filtro (ADR-0032): quien traiga
		// los participantes de otro sitio sigue pudiendo sustituirlos desde un
		// snippet, con más prioridad o quitando este.
		add_filter( Participants::HOOK, array( self::class, 'participants' ), 10, 2 );
	}

	// ─── leer ──────────────────────────────────────────────────────────────

	/**
	 * The registrations of one event.
	 *
	 * @param int $event_id Event post ID.
	 * @return \WP_Post[]
	 */
	public static function all( int $event_id ): array {
		if ( $event_id <= 0 ) {
			return array();
		}
		$posts = get_posts(
			array(
				'post_type'        => RegistrationPostType::POST_TYPE,
				'post_parent'      => $event_id,
				'post_status'      => array( 'publish', 'private' ),
				'numberposts'      => -1,
				'orderby'          => 'date',
				'order'            => 'ASC',
				'suppress_filters' => false,
			)
		);
		return is_array( $posts ) ? $posts : array();
	}

	/**
	 * Answer the participants filter with our own registrations.
	 *
	 * Llena la forma de fila que declara {@see Participants::columns()}, que es
	 * el contrato de la ADR-0027, y añade una columna por pregunta del evento
	 * con su rótulo por cabecera (ADR-0031).
	 *
	 * @param array<int, array<string, string>> $filas    Rows so far.
	 * @param int                               $event_id Event post ID.
	 * @return array<int, array<string, string>>
	 */
	public static function participants( array $filas, int $event_id ): array {
		$preguntas = self::questions( $event_id );
		$talleres  = self::workshop_labels( $event_id );

		foreach ( self::all( $event_id ) as $inscripcion ) {
			$meta    = self::meta( $inscripcion->ID );
			$elegido = array();
			foreach ( WorkshopChoice::ids( $meta[ RegistrationMetaKeys::REG_WORKSHOP ] ) as $taller ) {
				if ( isset( $talleres[ $taller ] ) ) {
					$elegido[] = $talleres[ $taller ];
				}
			}

			// Los documentos privados: en la columna va su **nombre**, que es lo
			// que se lee y lo que se exporta; la descarga la compone la pantalla
			// con el identificador opaco. Ni una ruta ni una dirección entra
			// aquí (ADR-0036).
			$documentos = array();
			foreach ( RegistrationFiles::descriptors( (int) $inscripcion->ID ) as $descriptor ) {
				$documentos[] = array(
					'reg'  => (int) $inscripcion->ID,
					'id'   => (string) $descriptor['id'],
					'name' => (string) ( $descriptor['name'] ?? '' ),
				);
			}

			$fila                            = array(
				'name'        => trim( $meta[ RegistrationMetaKeys::REG_NAME ] . ' ' . $meta[ RegistrationMetaKeys::REG_SURNAME ] ),
				'email'       => $meta[ RegistrationMetaKeys::REG_EMAIL ],
				'centre_code' => $meta[ RegistrationMetaKeys::REG_CENTRE_CODE ] ?? '',
				'centre'      => $meta[ RegistrationMetaKeys::REG_CENTRE ],
				'workshop'    => implode( Participants::SEPARATOR, $elegido ),
				'date'        => get_the_date( 'Y-m-d H:i', $inscripcion ),
				'consent'     => self::consent_text( $meta ),
				'files'       => implode( ', ', wp_list_pluck( $documentos, 'name' ) ),
			);
			$fila[ Participants::KEY_FILES ] = $documentos;
			$fila[ Participants::KEY_REG ]   = (int) $inscripcion->ID;

			// Una columna por pregunta, con el rótulo por cabecera. La clave es
			// el identificador, así que reescribir el rótulo mueve la cabecera
			// y no descoloca ni un dato (ADR-0032).
			$respuestas = self::answers( $inscripcion->ID );
			foreach ( $preguntas as $pregunta ) {
				$fila[ $pregunta['label'] ] = SignupQuestions::as_text(
					$pregunta,
					$respuestas[ $pregunta['id'] ] ?? null
				);
			}

			$filas[] = $fila;
		}

		return $filas;
	}

	/**
	 * The questions of one event, normalised.
	 *
	 * @param int $event_id Event post ID.
	 * @return array<int, array<string, mixed>>
	 */
	public static function questions( int $event_id ): array {
		return SignupQuestions::read( get_post_meta( $event_id, RegistrationMetaKeys::SIGNUP_QUESTIONS, true ) );
	}

	/**
	 * The stored answers of one registration.
	 *
	 * @param int $registration_id Registration post ID.
	 * @return array<string, mixed>
	 */
	public static function answers( int $registration_id ): array {
		$crudo = get_post_meta( $registration_id, RegistrationMetaKeys::REG_ANSWERS, true );
		$datos = is_string( $crudo ) && '' !== $crudo ? json_decode( $crudo, true ) : $crudo;
		return is_array( $datos ) ? $datos : array();
	}

	/**
	 * Every core meta of one registration, as strings.
	 *
	 * @param int $registration_id Registration post ID.
	 * @return array<string, string>
	 */
	public static function meta( int $registration_id ): array {
		$out = array();
		foreach ( RegistrationMetaKeys::registration_keys() as $clave ) {
			$valor         = get_post_meta( $registration_id, $clave, true );
			$out[ $clave ] = is_scalar( $valor ) ? (string) $valor : '';
		}
		return $out;
	}

	/**
	 * How the consent shows in the list.
	 *
	 * @param array<string, string> $meta Registration metas.
	 * @return string
	 */
	private static function consent_text( array $meta ): string {
		$cuando = $meta[ RegistrationMetaKeys::REG_CONSENT_AT ];
		if ( '' === $cuando ) {
			return '';
		}
		$quien = (int) $meta[ RegistrationMetaKeys::REG_BY ] > 0 ? ', declarado por la organización' : '';
		return 'Sí (v' . $meta[ RegistrationMetaKeys::REG_CONSENT_VERSION ] . ', ' . $cuando . $quien . ')';
	}

	/**
	 * Whether anybody is signed up to this event yet.
	 *
	 * Es lo que cierra la edición de las preguntas ya contestadas
	 * ({@see SignupQuestions::refuse()}).
	 *
	 * @param int $event_id Event post ID.
	 * @return bool
	 */
	public static function has_any( int $event_id ): bool {
		if ( $event_id <= 0 ) {
			return false;
		}
		// Basta con saber si hay una: no hace falta traer todas con sus metas.
		$una = get_posts(
			array(
				'post_type'        => RegistrationPostType::POST_TYPE,
				'post_parent'      => $event_id,
				'post_status'      => array( 'publish', 'private' ),
				'numberposts'      => 1,
				'fields'           => 'ids',
				'suppress_filters' => false,
			)
		);
		return ! empty( $una );
	}

	/**
	 * The registration a signup token opens, 0 for none.
	 *
	 * El testigo es largo y aleatorio, y abre **esta** inscripción y nada más
	 * del sitio: no es una sesión (ADR-0033). Se compara en tiempo constante
	 * para no filtrar por dónde se parece.
	 *
	 * @param int    $event_id Event post ID.
	 * @param string $token    Token from the link.
	 * @return int
	 */
	public static function by_token( int $event_id, string $token ): int {
		$token = trim( $token );
		if ( ! self::is_token( $token ) ) {
			return 0;
		}
		foreach ( self::all( $event_id ) as $inscripcion ) {
			$suyo = (string) get_post_meta( $inscripcion->ID, RegistrationMetaKeys::REG_TOKEN, true );
			if ( '' !== $suyo && hash_equals( $suyo, $token ) ) {
				return (int) $inscripcion->ID;
			}
		}
		return 0;
	}

	/**
	 * Whether a string has the shape of a token we issue.
	 *
	 * @param string $token Raw token.
	 * @return bool
	 */
	public static function is_token( string $token ): bool {
		return (bool) preg_match( '/^[a-f0-9]{40}$/', $token );
	}

	/**
	 * The registration of this event with this tax ID, 0 for none.
	 *
	 * Un documento, una inscripción por evento (ADR-0051): es lo que ya hacía el
	 * sistema anterior, y la manera de que nadie se apunte dos veces y ocupe
	 * dos plazas.
	 *
	 * @param int    $event_id Event post ID.
	 * @param string $tax_id   Tax ID, as typed or stored.
	 * @param int    $except   Registration not to count (the one being corrected).
	 * @return int
	 */
	public static function by_tax_id( int $event_id, string $tax_id, int $except = 0 ): int {
		$tax_id = RegistrationInput::tax_id( $tax_id );
		if ( '' === $tax_id ) {
			return 0;
		}
		foreach ( self::all( $event_id ) as $inscripcion ) {
			if ( (int) $inscripcion->ID === $except ) {
				continue;
			}
			$suyo = RegistrationInput::tax_id( (string) get_post_meta( $inscripcion->ID, RegistrationMetaKeys::REG_TAX_ID, true ) );
			if ( $suyo === $tax_id ) {
				return (int) $inscripcion->ID;
			}
		}
		return 0;
	}

	/**
	 * The registrations of this event made with this email.
	 *
	 * @param int    $event_id Event post ID.
	 * @param string $email    Email.
	 * @return int[]
	 */
	public static function by_email( int $event_id, string $email ): array {
		$email = strtolower( trim( $email ) );
		if ( '' === $email ) {
			return array();
		}
		$out = array();
		foreach ( self::all( $event_id ) as $inscripcion ) {
			if ( strtolower( trim( (string) get_post_meta( $inscripcion->ID, RegistrationMetaKeys::REG_EMAIL, true ) ) ) === $email ) {
				$out[] = (int) $inscripcion->ID;
			}
		}
		return $out;
	}

	/**
	 * The registration of a logged-in person in this event, 0 for none.
	 *
	 * Primero la que hizo esa cuenta; si no hay, la que lleva el correo de la
	 * cuenta —la que le hizo la organización, o la que hizo sin sesión—. El
	 * correo de una cuenta no lo cambia nadie sin confirmarlo desde la
	 * dirección nueva, y por eso vale como prueba aquí y no en un formulario
	 * (ADR-0051).
	 *
	 * @param int $event_id Event post ID.
	 * @param int $user_id  User ID; 0 for the current one.
	 * @return int
	 */
	public static function of_user( int $event_id, int $user_id = 0 ): int {
		$user_id = $user_id > 0 ? $user_id : get_current_user_id();
		if ( $user_id <= 0 || $event_id <= 0 ) {
			return 0;
		}
		$todas = self::all( $event_id );
		foreach ( $todas as $inscripcion ) {
			if ( (int) get_post_meta( $inscripcion->ID, RegistrationMetaKeys::REG_USER, true ) === $user_id ) {
				return (int) $inscripcion->ID;
			}
		}
		$user = get_userdata( $user_id );
		if ( ! $user instanceof \WP_User || ! is_email( $user->user_email ) ) {
			return 0;
		}
		$suyas = self::by_email( $event_id, (string) $user->user_email );
		return array() === $suyas ? 0 : $suyas[0];
	}

	// ─── escribir ──────────────────────────────────────────────────────────

	/**
	 * Store one signup, unless that tax ID is already signed up to the event.
	 *
	 * Comprobar y crear van **dentro del candado del evento** (ADR-0033): sin
	 * él, dos envíos del mismo documento a la vez —el doble clic de siempre—
	 * leerían los dos «no hay ninguna» y crearían dos.
	 *
	 * @param int                  $event_id Event post ID.
	 * @param array<string, mixed> $core     Validated core fields.
	 * @param array<string, mixed> $answers  Validated answers, keyed by question ID.
	 * @param int                  $user_id  Account signing up, 0 for none.
	 * @param int                  $by       Organiser making it, 0 when the person does.
	 * @return array{id:int, error:string} error: '' | 'repetida' | 'ocupado' | 'no_guardada'.
	 */
	public static function add( int $event_id, array $core, array $answers, int $user_id = 0, int $by = 0 ): array {
		if ( ! self::lock( $event_id ) ) {
			return array(
				'id'    => 0,
				'error' => 'ocupado',
			);
		}
		try {
			if ( self::by_tax_id( $event_id, (string) ( $core['tax_id'] ?? '' ) ) > 0 ) {
				return array(
					'id'    => 0,
					'error' => 'repetida',
				);
			}
			$id = self::create( $event_id, $core, $answers );
			if ( $id <= 0 ) {
				return array(
					'id'    => 0,
					'error' => 'no_guardada',
				);
			}
			update_post_meta( $id, RegistrationMetaKeys::REG_USER, max( 0, $user_id ) );
			update_post_meta( $id, RegistrationMetaKeys::REG_BY, max( 0, $by ) );
			return array(
				'id'    => $id,
				'error' => '',
			);
		} finally {
			self::unlock( $event_id );
		}
	}

	/**
	 * Store one signup.
	 *
	 * El título es la **referencia**, no el nombre: quien mire `wp_posts` a
	 * pelo ve referencias y no personas (ADR-0032).
	 *
	 * @param int                  $event_id Event post ID.
	 * @param array<string, mixed> $core     Validated core fields.
	 * @param array<string, mixed> $answers  Validated answers, keyed by question ID.
	 * @return int Registration post ID, 0 on failure.
	 */
	public static function create( int $event_id, array $core, array $answers ): int {
		if ( $event_id <= 0 ) {
			return 0;
		}

		$id = wp_insert_post(
			array(
				'post_type'   => RegistrationPostType::POST_TYPE,
				'post_parent' => $event_id,
				'post_status' => 'private',
				'post_title'  => self::reference( $event_id ),
			),
			true
		);
		if ( is_wp_error( $id ) || ! $id ) {
			return 0;
		}
		$id = (int) $id;

		$metas = array(
			RegistrationMetaKeys::REG_TAX_ID          => $core['tax_id'] ?? '',
			RegistrationMetaKeys::REG_NAME            => $core['name'] ?? '',
			RegistrationMetaKeys::REG_SURNAME         => $core['surname'] ?? '',
			RegistrationMetaKeys::REG_EMAIL           => $core['email'] ?? '',
			RegistrationMetaKeys::REG_PHONE           => $core['phone'] ?? '',
			RegistrationMetaKeys::REG_CENTRE          => $core['centre'] ?? '',
			RegistrationMetaKeys::REG_CENTRE_CODE     => $core['centre_code'] ?? '',
			RegistrationMetaKeys::REG_CONSENT_VERSION => (int) get_post_meta( $event_id, RegistrationMetaKeys::CONSENT_VERSION, true ),
			RegistrationMetaKeys::REG_CONSENT_AT      => current_time( 'mysql' ),
			// `wp_slash()` y no el JSON a pelo: `update_post_meta()` desescapa lo
			// que le llega, y sin esto una respuesta con una tilde o una comilla
			// llegaría rota a la base de datos.
			RegistrationMetaKeys::REG_ANSWERS         => wp_slash( (string) wp_json_encode( $answers ) ),
			RegistrationMetaKeys::REG_TOKEN           => self::new_token(),
		);
		foreach ( $metas as $clave => $valor ) {
			update_post_meta( $id, $clave, $valor );
		}

		return $id;
	}

	/**
	 * Whether a registration belongs to this event and is still there.
	 *
	 * @param int $event_id        Event post ID.
	 * @param int $registration_id Registration post ID.
	 * @return bool
	 */
	public static function belongs( int $event_id, int $registration_id ): bool {
		return $event_id > 0
			&& RegistrationPostType::POST_TYPE === get_post_type( $registration_id )
			&& (int) get_post_field( 'post_parent', $registration_id ) === $event_id
			&& in_array( get_post_status( $registration_id ), array( 'publish', 'private' ), true );
	}

	/**
	 * What the organisation can change of a registration: core and answers.
	 *
	 * El consentimiento, su fecha, el testigo y los documentos no se tocan:
	 * son lo que aceptó y aportó la persona, no un dato que se corrige. Las
	 * respuestas de archivo se conservan tal cual.
	 *
	 * @param int                  $event_id        Event post ID.
	 * @param int                  $registration_id Registration post ID.
	 * @param array<string, mixed> $core            Validated core fields.
	 * @param array<string, mixed> $answers         Validated answers, keyed by question ID.
	 * @return bool False when it is not a registration of this event.
	 */
	public static function update( int $event_id, int $registration_id, array $core, array $answers ): bool {
		if ( ! self::belongs( $event_id, $registration_id ) ) {
			return false;
		}
		$campos = array(
			RegistrationMetaKeys::REG_TAX_ID      => 'tax_id',
			RegistrationMetaKeys::REG_NAME        => 'name',
			RegistrationMetaKeys::REG_SURNAME     => 'surname',
			RegistrationMetaKeys::REG_EMAIL       => 'email',
			RegistrationMetaKeys::REG_PHONE       => 'phone',
			RegistrationMetaKeys::REG_CENTRE      => 'centre',
			RegistrationMetaKeys::REG_CENTRE_CODE => 'centre_code',
		);
		foreach ( $campos as $clave => $origen ) {
			update_post_meta( $registration_id, $clave, (string) ( $core[ $origen ] ?? '' ) );
		}
		$respuestas = array_merge( self::answers( $registration_id ), $answers );
		update_post_meta( $registration_id, RegistrationMetaKeys::REG_ANSWERS, wp_slash( (string) wp_json_encode( $respuestas ) ) );
		return true;
	}

	/**
	 * Delete a registration for good, if whoever asks typed its email.
	 *
	 * Borrar una inscripción es borrar los datos de una persona —y sus
	 * documentos, que se van con ella (ADR-0036)—, y no tiene papelera: por
	 * eso se pide teclear su correo, que no se escribe sin mirar a quién se
	 * borra (ADR-0043). Su plaza de taller, si tenía, queda libre sola: las
	 * plazas se cuentan sobre las inscripciones que hay.
	 *
	 * @param int    $event_id        Event post ID.
	 * @param int    $registration_id Registration post ID.
	 * @param string $typed           The email typed to confirm.
	 * @return string '' when deleted; 'no_es_de_este_evento' or 'correo' otherwise.
	 */
	public static function delete( int $event_id, int $registration_id, string $typed ): string {
		if ( ! self::belongs( $event_id, $registration_id ) ) {
			return 'no_es_de_este_evento';
		}
		$correo = strtolower( trim( (string) get_post_meta( $registration_id, RegistrationMetaKeys::REG_EMAIL, true ) ) );
		if ( '' === $correo || strtolower( trim( $typed ) ) !== $correo ) {
			return 'correo';
		}
		wp_delete_post( $registration_id, true );
		return '';
	}

	/**
	 * A fresh token.
	 *
	 * @return string
	 */
	public static function new_token(): string {
		return bin2hex( random_bytes( 20 ) );
	}

	/**
	 * The reference a new registration gets as its title.
	 *
	 * @param int $event_id Event post ID.
	 * @return string
	 */
	private static function reference( int $event_id ): string {
		return sprintf( 'INS-%d-%s', $event_id, strtoupper( substr( bin2hex( random_bytes( 4 ) ), 0, 8 ) ) );
	}

	// ─── el aforo, que es lo único con carrera dentro ──────────────────────

	/**
	 * Take the workshop seats asked for, releasing the others, or say why not.
	 *
	 * Lo que se pide es **la elección entera**: la lista de talleres con la
	 * que se quiere quedar la persona. Uno solo, o el 0 de «ninguno», vale
	 * igual que antes de que se pudieran elegir varios.
	 *
	 * Las reglas —cuántos, sin repetir taller y sin solaparse (ADR-0050)— y
	 * las plazas van **dentro del mismo candado**, igual que soltar y tomar, y
	 * eso es lo que impide que alguien se quede sin ninguno: o la elección
	 * entera o nada (ADR-0033).
	 *
	 * Lo que **no** se hace, y se escribe para que no vuelva por parecer lo
	 * natural: contar en PHP, comparar y escribir sin candado. Dos peticiones
	 * leen 19 de 20 y las dos escriben.
	 *
	 * @param int       $event_id        Event post ID.
	 * @param int       $registration_id Registration post ID.
	 * @param int|int[] $activities      Workshops to keep; 0 or [] to just release.
	 * @return array{ok:bool, error:string}
	 */
	public static function seat( int $event_id, int $registration_id, $activities ): array {
		if ( $event_id <= 0 || (int) get_post_field( 'post_parent', $registration_id ) !== $event_id ) {
			return array(
				'ok'    => false,
				'error' => 'no_es_de_este_evento',
			);
		}
		$pedidos = WorkshopChoice::ids( $activities );
		$mapa    = self::workshop_map( $event_id );
		foreach ( $pedidos as $id ) {
			if ( ! isset( $mapa[ $id ] ) ) {
				return array(
					'ok'    => false,
					'error' => WorkshopChoice::NOT_HERE,
				);
			}
		}

		if ( ! self::lock( $event_id ) ) {
			return array(
				'ok'    => false,
				'error' => 'ocupado',
			);
		}

		try {
			$actuales = self::workshops_of( $registration_id );
			$iguales  = $pedidos;
			sort( $iguales );
			$antes = $actuales;
			sort( $antes );
			if ( $iguales === $antes ) {
				return array(
					'ok'    => true,
					'error' => '',
				);
			}

			$regla = WorkshopChoice::check( $mapa, $pedidos, self::max_workshops( $event_id ) );
			if ( '' !== $regla ) {
				return array(
					'ok'    => false,
					'error' => $regla,
				);
			}

			// Solo se cuentan las plazas de lo que se **añade**: lo que ya tenía
			// lo conserva aunque el taller se haya llenado después.
			foreach ( array_diff( $pedidos, $actuales ) as $nuevo ) {
				if ( ! self::fits( $event_id, (int) $nuevo, $registration_id ) ) {
					return array(
						'ok'    => false,
						'error' => 'sin_plazas',
					);
				}
			}

			// Soltar y tomar, en el mismo turno: no existe el instante en el
			// que esta persona se ha quedado sin ninguno de los suyos.
			update_post_meta( $registration_id, RegistrationMetaKeys::REG_WORKSHOP, WorkshopChoice::store( $pedidos ) );

			return array(
				'ok'    => true,
				'error' => '',
			);
		} finally {
			self::unlock( $event_id );
		}
	}

	/**
	 * The workshops a registration holds, as IDs.
	 *
	 * @param int $registration_id Registration post ID.
	 * @return int[]
	 */
	public static function workshops_of( int $registration_id ): array {
		return WorkshopChoice::ids( get_post_meta( $registration_id, RegistrationMetaKeys::REG_WORKSHOP, true ) );
	}

	/**
	 * How many workshops one person may choose in this event.
	 *
	 * @param int $event_id Event post ID.
	 * @return int At least 1.
	 */
	public static function max_workshops( int $event_id ): int {
		return WorkshopChoice::max( get_post_meta( $event_id, RegistrationMetaKeys::WORKSHOP_MAX, true ) );
	}

	/**
	 * Whether one more person fits in a workshop.
	 *
	 * Un aforo de cero significa «sin límite», que es lo que ya hacía la
	 * pantalla de talleres y no hay razón para cambiarlo.
	 *
	 * @param int $event_id        Event post ID.
	 * @param int $activity_id     Workshop.
	 * @param int $registration_id Who is asking, so they do not count twice.
	 * @return bool
	 */
	public static function fits( int $event_id, int $activity_id, int $registration_id = 0 ): bool {
		$aforo = (int) get_post_meta( $activity_id, ProgrammeMetaKeys::ACTIVITY_SEATS, true );
		if ( $aforo <= 0 ) {
			return true;
		}
		return self::taken( $event_id, $activity_id, $registration_id ) < $aforo;
	}

	/**
	 * How many seats of a workshop are taken.
	 *
	 * @param int $event_id   Event post ID.
	 * @param int $activity_id Workshop.
	 * @param int $except      Registration not to count.
	 * @return int
	 */
	public static function taken( int $event_id, int $activity_id, int $except = 0 ): int {
		$n = 0;
		foreach ( self::all( $event_id ) as $inscripcion ) {
			if ( (int) $inscripcion->ID === $except ) {
				continue;
			}
			if ( in_array( $activity_id, self::workshops_of( (int) $inscripcion->ID ), true ) ) {
				++$n;
			}
		}
		return $n;
	}

	/**
	 * The workshops somebody may still choose from.
	 *
	 * Un taller lleno **no sale**. Que no salga no es el control: la pantalla
	 * se pudo pintar hace cinco minutos, y la comprobación que manda es la del
	 * candado, al guardar (ADR-0033). Quien lo tuviera ya elegido lo conserva:
	 * llenarse no echa a nadie.
	 *
	 * @param int $event_id        Event post ID.
	 * @param int $registration_id Who is choosing, 0 for a new signup.
	 * @return array<int, array{id:int, title:string, label:string, when:string, venue:string, seats:int, taken:int, mine:bool}>
	 */
	public static function choices( int $event_id, int $registration_id = 0 ): array {
		$mios = $registration_id > 0 ? self::workshops_of( $registration_id ) : array();

		// Se cuentan todas las plazas de una pasada, no una vuelta por taller.
		$ocupados = array();
		foreach ( self::all( $event_id ) as $inscripcion ) {
			foreach ( self::workshops_of( (int) $inscripcion->ID ) as $taller ) {
				$ocupados[ $taller ] = ( $ocupados[ $taller ] ?? 0 ) + 1;
			}
		}

		$rotulos = self::workshop_labels( $event_id );
		$out     = array();
		foreach ( self::workshop_map( $event_id ) as $id => $taller ) {
			$aforo   = (int) $taller['seats'];
			$ocupado = $ocupados[ $id ] ?? 0;
			$suyo    = in_array( $id, $mios, true );

			if ( ! $suyo && $aforo > 0 && $ocupado >= $aforo ) {
				continue;
			}

			$out[] = array(
				'id'    => $id,
				'title' => (string) $taller['title'],
				'label' => (string) ( $rotulos[ $id ] ?? $taller['title'] ),
				'when'  => self::when( $taller ),
				'venue' => (string) $taller['venue'],
				'seats' => $aforo,
				'taken' => $ocupado,
				'mine'  => $suyo,
			);
		}
		return $out;
	}

	/**
	 * The workshops of an event with what the rules need: id => row.
	 *
	 * @param int $event_id Event post ID.
	 * @return array<int, array{title:string, date:string, start:string, end:string, venue:string, seats:int}>
	 */
	public static function workshop_map( int $event_id ): array {
		$out = array();
		foreach ( Programme::workshops( $event_id ) as $taller ) {
			$id         = (int) $taller->ID;
			$out[ $id ] = array(
				'title' => (string) $taller->post_title,
				'date'  => (string) get_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_DATE, true ),
				'start' => (string) get_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_START, true ),
				'end'   => (string) get_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_END, true ),
				'venue' => (string) get_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_VENUE, true ),
				'seats' => (int) get_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_SEATS, true ),
			);
		}
		return $out;
	}

	/**
	 * How each workshop is named in lists: its title, plus when if it repeats.
	 *
	 * Un taller que se da dos veces tiene dos sesiones con el mismo título; en
	 * la lista de participantes y en el CSV tienen que distinguirse, así que a
	 * esas —y solo a esas— se les añade el día y la hora.
	 *
	 * @param int $event_id Event post ID.
	 * @return array<int, string>
	 */
	public static function workshop_labels( int $event_id ): array {
		$mapa   = self::workshop_map( $event_id );
		$veces  = array();
		$claves = array();
		foreach ( $mapa as $id => $taller ) {
			$claves[ $id ]           = WorkshopChoice::key( $taller['title'] );
			$veces[ $claves[ $id ] ] = ( $veces[ $claves[ $id ] ] ?? 0 ) + 1;
		}
		$out = array();
		foreach ( $mapa as $id => $taller ) {
			$cuando     = self::when( $taller );
			$out[ $id ] = $veces[ $claves[ $id ] ] > 1 && '' !== $cuando
				? $taller['title'] . ' (' . $cuando . ')'
				: $taller['title'];
		}
		return $out;
	}

	/**
	 * When a workshop happens, as people read it: «6/2/2027, 15:00–16:15».
	 *
	 * @param array<string, mixed> $taller Row of {@see workshop_map()}.
	 * @return string
	 */
	public static function when( array $taller ): string {
		$partes = array();
		$fecha  = \DateTimeImmutable::createFromFormat( '!Y-m-d', (string) ( $taller['date'] ?? '' ), wp_timezone() );
		if ( false !== $fecha ) {
			$partes[] = wp_date( 'j/n/Y', $fecha->getTimestamp() );
		}
		$desde = (string) ( $taller['start'] ?? '' );
		$hasta = (string) ( $taller['end'] ?? '' );
		if ( '' !== $desde ) {
			$partes[] = '' !== $hasta ? $desde . '–' . $hasta : $desde;
		}
		return implode( ', ', $partes );
	}

	// ─── el candado ────────────────────────────────────────────────────────

	/**
	 * Take the lock of one event, waiting a little for it.
	 *
	 * **Uno por evento y no uno por taller**: un cambio toca dos talleres, y
	 * dos candados tomados en distinto orden por dos peticiones son un abrazo
	 * mortal que no falla hasta el día que falla (ADR-0033).
	 *
	 * Se toma con `add_option()`, que **falla si el nombre ya existe** porque
	 * la columna es única en la base de datos: eso es lo que lo convierte en un
	 * candado de verdad sin escribir SQL propio. `autoload` a `no` para no
	 * meterlo en la carga de opciones de cada petición.
	 *
	 * @param int $event_id Event post ID.
	 * @return bool
	 */
	public static function lock( int $event_id ): bool {
		$nombre  = self::LOCK_PREFIX . $event_id;
		$esperar = 0;

		do {
			wp_cache_delete( $nombre, 'options' );
			wp_cache_delete( 'notoptions', 'options' );

			if ( add_option( $nombre, (string) time(), '', 'no' ) ) {
				return true;
			}

			// Un candado caducado es de una petición que murió a mitad: se
			// retira y se vuelve a intentar. Se borra antes de reclamarlo para
			// que quien lo consiga sea quien gane el `add_option()`.
			$puesto = (int) get_option( $nombre, 0 );
			if ( $puesto > 0 && ( time() - $puesto ) > self::LOCK_TTL ) {
				delete_option( $nombre );
				continue;
			}

			usleep( 50000 );
			$esperar += 50000;
		} while ( $esperar < self::LOCK_WAIT );

		return false;
	}

	/**
	 * Release the lock of one event.
	 *
	 * @param int $event_id Event post ID.
	 * @return void
	 */
	public static function unlock( int $event_id ): void {
		delete_option( self::LOCK_PREFIX . $event_id );
	}

	/**
	 * The core fields the account of whoever is signing up already answers.
	 *
	 * Con sesión, el nombre, los apellidos, el correo y el centro salen de la
	 * ficha del usuario y no se teclean: el formulario los enseña de solo
	 * lectura y {@see SignupForm} los vuelve a poner al recibir el envío, que
	 * un `readonly` en el navegador no protege nada. Solo cuenta lo que la
	 * ficha trae relleno —y el centro, si su código está en el catálogo—: lo
	 * que falte se sigue escribiendo a mano.
	 *
	 * @param int $user_id User ID (0 = current).
	 * @return array<string, string> Subset of name, surname, email and centre (the code).
	 */
	public static function from_profile( int $user_id = 0 ): array {
		$user = get_userdata( $user_id > 0 ? $user_id : get_current_user_id() );
		if ( ! $user instanceof \WP_User ) {
			return array();
		}

		$out = array(
			'name'    => trim( $user->first_name ),
			'surname' => trim( $user->last_name ),
			'email'   => is_email( $user->user_email ) ? strtolower( $user->user_email ) : '',
			'centre'  => trim( (string) get_user_meta( $user->ID, RegistrationMetaKeys::USER_CENTRE_CODE, true ) ),
		);
		if ( ! isset( self::centres()[ $out['centre'] ] ) ) {
			$out['centre'] = '';
		}
		return array_filter( $out, 'strlen' );
	}

	/**
	 * Validate a signup payload against the event, without writing anything.
	 *
	 * @param int                  $event_id Event post ID.
	 * @param array<string, mixed> $raw      Raw submitted fields.
	 * @param array<string, mixed> $answers  Raw submitted answers.
	 * @return array{ok:bool, errors:string[], core:array<string, mixed>, answers:array<string, mixed>}
	 */
	public static function validate( int $event_id, array $raw, array $answers ): array {
		$nucleo     = RegistrationInput::core( $raw, self::centres() );
		$preguntas  = self::questions( $event_id );
		$contestado = SignupQuestions::answers( $preguntas, $answers );

		return array(
			'ok'      => $nucleo['ok'] && $contestado['ok'],
			'errors'  => array_merge( $nucleo['errors'], $contestado['errors'] ),
			'core'    => $nucleo['data'],
			'answers' => $contestado['data'],
		);
	}

	/**
	 * The catalogue of centres a person may pick from.
	 *
	 * El centro **nunca se teclea** (ADR-0031). El catálogo se consulta
	 * mediante el filtro `evt_centres`, cuyo contrato exige un mapa asociativo
	 * de código oficial de 8 dígitos a denominación.
	 *
	 * @return array<string, string> Map of 8-digit code => name.
	 */
	public static function centres(): array {
		/**
		 * Filter the catalogue of centres.
		 *
		 * Debe devolver un mapa asociativo de código oficial de 8 dígitos a denominación.
		 *
		 * @param array<string, string> $centres Map of 8-digit code => name.
		 */
		$centros = apply_filters( 'evt_centres', array() );
		if ( ! is_array( $centros ) ) {
			return array();
		}

		$out = array();
		foreach ( $centros as $codigo => $denominacion ) {
			$codigo       = trim( (string) $codigo );
			$denominacion = trim( (string) $denominacion );
			if ( 1 === preg_match( '/^\d{8}$/', $codigo ) && '' !== $denominacion ) {
				$out[ $codigo ] = $denominacion;
			}
		}
		return $out;
	}
}
