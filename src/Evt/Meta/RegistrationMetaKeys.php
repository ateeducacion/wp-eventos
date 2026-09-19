<?php
/**
 * Meta keys of a registration, and of the signup settings of its event.
 *
 * @package Evt
 */

namespace Evt\Meta;

/**
 * Las claves de una inscripción y las de la inscripción de un evento.
 *
 * Dos grupos que no hay que confundir:
 *
 * - `REG_*` cuelgan de la **inscripción** (`evt_registration`): son los datos
 *   de una persona. Su `auth_callback` devuelve `false` siempre: no hay
 *   ninguna vía por la que se escriban desde fuera del aplicativo (ADR-0032).
 * - `SIGNUP_*` cuelgan del **evento**: son los ajustes de su inscripción —el
 *   plazo, las preguntas, los textos del consentimiento— y los edita quien
 *   organiza.
 *
 * El núcleo de la inscripción son los siete campos que fijó la ADR-0031, y
 * está aquí entero y en un solo sitio para que se vea que no crece solo.
 */
final class RegistrationMetaKeys {

	// ─── El núcleo de la inscripción (ADR-0031) ────────────────────────────

	public const REG_TAX_ID  = 'evt_reg_tax_id';
	public const REG_NAME    = 'evt_reg_name';
	public const REG_SURNAME = 'evt_reg_surname';
	public const REG_EMAIL   = 'evt_reg_email';
	public const REG_PHONE   = 'evt_reg_phone';
	public const REG_CENTRE  = 'evt_reg_centre';

	/**
	 * Which version of the consent texts this person accepted, and when.
	 *
	 * Las dos juntas son la constancia que pide la ADR-0020: una casilla sin
	 * saber qué texto se aceptó y cuándo no vale si alguien lo reclama.
	 */
	public const REG_CONSENT_VERSION = 'evt_reg_consent_version';
	public const REG_CONSENT_AT      = 'evt_reg_consent_at';

	/**
	 * The activity ID of the chosen workshop, 0 for none.
	 *
	 * Un identificador y no un título: el título se retoca y la elección
	 * seguiría apuntando al taller que es (ADR-0033).
	 */
	public const REG_WORKSHOP = 'evt_reg_workshop';

	/**
	 * Answers to the questions of the event, as JSON keyed by question ID.
	 *
	 * Por identificador y nunca por rótulo: reescribir el rótulo de una
	 * pregunta no puede desconectar lo que ya contestó nadie (ADR-0032).
	 */
	public const REG_ANSWERS = 'evt_reg_answers';

	/**
	 * The private documents of this registration, as JSON keyed by question ID.
	 *
	 * **Ni una ruta absoluta, ni una URL, ni un identificador de adjunto**: un
	 * descriptor con lo que hace falta para enseñarlo y para encontrarlo
	 * (ADR-0036). Va aparte de {@see REG_ANSWERS} a propósito: ahí viven las
	 * respuestas a las preguntas de lista cerrada, y un almacén de ficheros
	 * metido dentro las convertiría en otra cosa.
	 */
	public const REG_FILES = 'evt_reg_files';

	/**
	 * The one-time token that lets this person back into their registration.
	 *
	 * No es una sesión: abre esta inscripción y nada más del sitio (ADR-0033).
	 */
	public const REG_TOKEN = 'evt_reg_token';

	// ─── Los ajustes de inscripción, en el evento ──────────────────────────

	/**
	 * Whether the signup form is open at all.
	 */
	public const SIGNUP_OPEN = 'evt_signup_open';

	/**
	 * The questions of this event, as JSON.
	 */
	public const SIGNUP_QUESTIONS = 'evt_signup_questions';

	/**
	 * The window in which workshops can be chosen, and its own switch.
	 *
	 * Tiene plazo propio porque se abre cuando quien organiza ha cerrado el
	 * programa, que casi nunca es cuando se abre la inscripción (ADR-0033).
	 */
	public const WORKSHOP_OPEN  = 'evt_workshop_open';
	public const WORKSHOP_START = 'evt_workshop_start';
	public const WORKSHOP_END   = 'evt_workshop_end';

	/**
	 * The two consent texts and the version they are on.
	 *
	 * La versión sube cuando se cambia cualquiera de los dos textos, y **no**
	 * reescribe lo que ya aceptó nadie (ADR-0020).
	 */
	public const CONSENT_PRIVACY = 'evt_consent_privacy';
	public const CONSENT_IMAGE   = 'evt_consent_image';
	public const CONSENT_VERSION = 'evt_consent_version';

	/**
	 * Every meta key that hangs off a registration.
	 *
	 * @return string[]
	 */
	public static function registration_keys(): array {
		return array(
			self::REG_TAX_ID,
			self::REG_NAME,
			self::REG_SURNAME,
			self::REG_EMAIL,
			self::REG_PHONE,
			self::REG_CENTRE,
			self::REG_CONSENT_VERSION,
			self::REG_CONSENT_AT,
			self::REG_WORKSHOP,
			self::REG_ANSWERS,
			self::REG_FILES,
			self::REG_TOKEN,
		);
	}

	/**
	 * Every meta key of the signup settings of an event.
	 *
	 * @return string[]
	 */
	public static function signup_keys(): array {
		return array(
			self::SIGNUP_OPEN,
			self::SIGNUP_QUESTIONS,
			self::WORKSHOP_OPEN,
			self::WORKSHOP_START,
			self::WORKSHOP_END,
			self::CONSENT_PRIVACY,
			self::CONSENT_IMAGE,
			self::CONSENT_VERSION,
		);
	}

	/**
	 * The five field types a question may have, with their label.
	 *
	 * Lista cerrada y corta a propósito. Eran las cuatro de la ADR-0031 y son
	 * cinco desde la ADR-0036, que añadió `file` porque pedir un documento
	 * —una autorización, un justificante— era lo único que obligaba a mandar a
	 * la gente fuera del formulario. La raya sigue donde estaba: un `file`
	 * tiene rótulo, tipo y si es obligatorio, **y nada más**. Ni tamaño, ni
	 * tipos, ni varios ficheros por pregunta: eso es del aplicativo entero y
	 * vive en {@see \Evt\PublicFront\RegistrationFiles}.
	 *
	 * @return array<string, string>
	 */
	public static function question_types(): array {
		return array(
			'check' => 'Casilla',
			'one'   => 'Una opción',
			'many'  => 'Varias opciones',
			'text'  => 'Texto corto',
			'file'  => 'Archivo',
		);
	}

	/**
	 * Whether a question type carries a list of options.
	 *
	 * @param string $type Question type.
	 * @return bool
	 */
	public static function has_options( string $type ): bool {
		return 'one' === $type || 'many' === $type;
	}
}
