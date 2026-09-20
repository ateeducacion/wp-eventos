<?php
/**
 * Signups of one event: store, read, and take a workshop seat.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

use Evt\Domain\RegistrationInput;
use Evt\Domain\SignupQuestions;
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
		$talleres  = array();
		foreach ( Programme::workshops( $event_id ) as $taller ) {
			$talleres[ $taller->ID ] = (string) $taller->post_title;
		}

		foreach ( self::all( $event_id ) as $inscripcion ) {
			$meta   = self::meta( $inscripcion->ID );
			$taller = (int) $meta[ RegistrationMetaKeys::REG_WORKSHOP ];

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
				'workshop'    => $talleres[ $taller ] ?? '',
				'date'        => get_the_date( 'Y-m-d H:i', $inscripcion ),
				'consent'     => self::consent_text( $meta ),
				'files'       => implode( ', ', wp_list_pluck( $documentos, 'name' ) ),
			);
			$fila[ Participants::KEY_FILES ] = $documentos;

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
		return 'Sí (v' . $meta[ RegistrationMetaKeys::REG_CONSENT_VERSION ] . ', ' . $cuando . ')';
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
		return array() !== self::all( $event_id );
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

	// ─── escribir ──────────────────────────────────────────────────────────

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
	 * Take a workshop seat, releasing the previous one, or say why not.
	 *
	 * Los tres pasos —contar, soltar y tomar— van **dentro del mismo candado**,
	 * y eso es lo que impide que alguien se quede sin ninguno de los dos
	 * talleres: o el cambio entero o nada (ADR-0033).
	 *
	 * Lo que **no** se hace, y se escribe para que no vuelva por parecer lo
	 * natural: contar en PHP, comparar y escribir sin candado. Dos peticiones
	 * leen 19 de 20 y las dos escriben.
	 *
	 * @param int $event_id        Event post ID.
	 * @param int $registration_id Registration post ID.
	 * @param int $activity_id     Workshop to take, 0 to just release.
	 * @return array{ok:bool, error:string}
	 */
	public static function seat( int $event_id, int $registration_id, int $activity_id ): array {
		if ( $event_id <= 0 || (int) get_post_field( 'post_parent', $registration_id ) !== $event_id ) {
			return array(
				'ok'    => false,
				'error' => 'no_es_de_este_evento',
			);
		}
		if ( $activity_id > 0 && ! self::is_workshop_of( $event_id, $activity_id ) ) {
			return array(
				'ok'    => false,
				'error' => 'no_es_un_taller_del_evento',
			);
		}

		if ( ! self::lock( $event_id ) ) {
			return array(
				'ok'    => false,
				'error' => 'ocupado',
			);
		}

		try {
			$actual = (int) get_post_meta( $registration_id, RegistrationMetaKeys::REG_WORKSHOP, true );
			if ( $actual === $activity_id ) {
				return array(
					'ok'    => true,
					'error' => '',
				);
			}

			if ( $activity_id > 0 && ! self::fits( $event_id, $activity_id, $registration_id ) ) {
				return array(
					'ok'    => false,
					'error' => 'sin_plazas',
				);
			}

			// Soltar y tomar, en el mismo turno: no existe el instante en el
			// que esta persona se ha quedado sin ninguno de los dos.
			update_post_meta( $registration_id, RegistrationMetaKeys::REG_WORKSHOP, $activity_id );

			return array(
				'ok'    => true,
				'error' => '',
			);
		} finally {
			self::unlock( $event_id );
		}
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
			if ( (int) get_post_meta( $inscripcion->ID, RegistrationMetaKeys::REG_WORKSHOP, true ) === $activity_id ) {
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
	 * @return array<int, array{id:int, title:string, seats:int, taken:int, mine:bool}>
	 */
	public static function choices( int $event_id, int $registration_id = 0 ): array {
		$mio = $registration_id > 0
			? (int) get_post_meta( $registration_id, RegistrationMetaKeys::REG_WORKSHOP, true )
			: 0;

		$out = array();
		foreach ( Programme::workshops( $event_id ) as $taller ) {
			$id      = (int) $taller->ID;
			$aforo   = (int) get_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_SEATS, true );
			$ocupado = self::taken( $event_id, $id );
			$suyo    = $id === $mio;

			if ( ! $suyo && $aforo > 0 && $ocupado >= $aforo ) {
				continue;
			}

			$out[] = array(
				'id'    => $id,
				'title' => (string) $taller->post_title,
				'seats' => $aforo,
				'taken' => $ocupado,
				'mine'  => $suyo,
			);
		}
		return $out;
	}

	/**
	 * Whether an activity is a workshop of this event.
	 *
	 * @param int $event_id    Event post ID.
	 * @param int $activity_id Activity.
	 * @return bool
	 */
	private static function is_workshop_of( int $event_id, int $activity_id ): bool {
		foreach ( Programme::workshops( $event_id ) as $taller ) {
			if ( (int) $taller->ID === $activity_id ) {
				return true;
			}
		}
		return false;
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
