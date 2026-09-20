<?php
/**
 * Private documents attached to a signup: store, authorise and serve.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

use Evt\Access\EventAccess;
use Evt\Meta\RegistrationMetaKeys;
use Evt\PostType\RegistrationPostType;

/**
 * Los documentos que aporta quien se inscribe: guardarlos, autorizarlos y
 * servirlos.
 *
 * **Un documento privado no es un adjunto de WordPress** (ADR-0036). Aquí no
 * se llama a `wp_insert_attachment()`, ni a `media_handle_upload()`, ni a
 * `media_handle_sideload()`, y no se guarda ningún identificador de adjunto.
 * No es que se escondan después: es que **no existen**. Un adjunto trae de
 * serie la biblioteca de medios, su página propia, `wp/v2/media`, el AJAX de
 * medios y XML-RPC, y cada una de esas puertas habría que guardarla. La que no
 * se abre no se guarda.
 *
 * El reparto es el de la ADR-0036, y es lo único que hay que recordar:
 *
 * - **Cartel, logo, foto de ponente, imagen del evento** → adjunto normal,
 *   biblioteca de medios, URL pública. Eso no lo toca esta clase y sigue en
 *   {@see EventWorkspace}.
 * - **Documento aportado en una inscripción** → aquí, con nombre físico
 *   opaco, descriptor en la meta de su inscripción y descarga autorizada por
 *   el aplicativo.
 *
 * La política —qué tipos y qué tamaño— es **del aplicativo entero y no de cada
 * pregunta**: una pregunta de tipo `file` acepta un fichero y no configura
 * nada más. Está en {@see mimes()} y {@see max_bytes()}, en un solo sitio.
 */
final class RegistrationFiles {

	/**
	 * Field the signup form sends its files in, keyed by question ID.
	 */
	public const FIELD = 'evt_qf';

	/**
	 * Query argument carrying the registration a download is about.
	 */
	public const ARG_REG = 'evt_reg';

	/**
	 * Query argument carrying the opaque ID of the file being asked for.
	 */
	public const ARG_FILE = 'evt_file';

	/**
	 * Directory under `uploads/` where the private files live.
	 */
	public const DIR = 'evt-private';

	/**
	 * Hard ceiling for one file, in bytes.
	 *
	 * Diez mebibytes, y el límite real es el menor de este y el del servidor
	 * ({@see max_bytes()}): prometer más de lo que PHP acepta es un formulario
	 * que falla sin decir por qué. Es del aplicativo, no de la pregunta.
	 */
	public const MAX_BYTES = 10 * MB_IN_BYTES;

	/**
	 * Mode a stored file is left in: write, no read.
	 *
	 * El nombre aleatorio **no es la protección**, es lo que evita que el
	 * nombre cuente algo. Lo que protege es que el fichero no se pueda leer
	 * desde fuera del aplicativo, y para leerlo hay que abrirlo a propósito
	 * ({@see read()}).
	 */
	private const MODE_CLOSED = 0200;

	/**
	 * Mode while the application is reading one.
	 */
	private const MODE_OPEN = 0400;

	/**
	 * Shape a `stored` path may have, and no other.
	 *
	 * Se compone aquí —dos niveles de dos dígitos hexadecimales, el nombre y
	 * la extensión ya validada—, así que comprobarlo contra esta forma cierra
	 * el paso a `..`, a una ruta absoluta y a cualquier cosa que no hayamos
	 * escrito nosotros, sin depender de resolver enlaces.
	 */
	private const STORED_SHAPE = '#^[a-f0-9]{2}/[a-f0-9]{2}/[a-f0-9]{32}\.[a-z0-9]{1,8}$#';

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		// En `init` 20, como el resto de manejadores: los tipos y sus
		// capacidades ya están registrados y todavía se pueden mandar
		// cabeceras.
		add_action( 'init', array( self::class, 'handle' ), 20 );
		// Solo el borrado definitivo. La papelera **no** borra ficheros: una
		// inscripción en la papelera se puede restaurar, y restaurarla sin sus
		// documentos es restaurar otra cosa (ADR-0036).
		add_action( 'before_delete_post', array( self::class, 'on_delete' ), 10, 2 );
	}

	// ─── la política, en un solo sitio ─────────────────────────────────────

	/**
	 * The file types a participant may attach, extension => MIME.
	 *
	 * Lista cerrada y conservadora: documentos y fotografías, que es lo que se
	 * pide en una inscripción. **Nada que un navegador pueda ejecutar o
	 * interpretar** —SVG, HTML, JavaScript—, y nada empaquetado, que es un
	 * contenedor y no un documento.
	 *
	 * @return array<string, string>
	 */
	public static function mimes(): array {
		$mimes = array(
			'pdf'          => 'application/pdf',
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
			'docx'         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'odt'          => 'application/vnd.oasis.opendocument.text',
		);

		/**
		 * Filter the file types a participant may attach.
		 *
		 * Es la válvula para un despliegue que necesite uno más, y se audita
		 * porque se ve escrita. Lo que llegue se cruza igualmente con lo que
		 * diga `wp_check_filetype_and_ext()`.
		 *
		 * @param array<string, string> $mimes Extension pattern => MIME type.
		 */
		$mimes = apply_filters( 'evt_private_file_mimes', $mimes );
		return is_array( $mimes ) ? $mimes : array();
	}

	/**
	 * The biggest file that gets accepted, in bytes.
	 *
	 * @return int
	 */
	public static function max_bytes(): int {
		$servidor = (int) wp_max_upload_size();
		return $servidor > 0 ? min( self::MAX_BYTES, $servidor ) : self::MAX_BYTES;
	}

	/**
	 * Where the private files live, without a trailing slash.
	 *
	 * Un subdirectorio propio de `uploads/` y **nunca mezclado con los medios
	 * públicos**: así una copia de seguridad lo incluye sin pensar, y una
	 * regla del servidor que lo cierre es una sola regla.
	 *
	 * @return string Empty when `uploads/` is not usable.
	 */
	public static function root(): string {
		$uploads = wp_upload_dir();
		$base    = isset( $uploads['basedir'] ) && ! $uploads['error'] ? (string) $uploads['basedir'] : '';
		$raiz    = '' === $base ? '' : $base . '/' . self::DIR;

		/**
		 * Filter the directory the private registration files live in.
		 *
		 * @param string $raiz Absolute path, no trailing slash.
		 */
		return rtrim( (string) apply_filters( 'evt_private_files_dir', $raiz ), '/' );
	}

	// ─── lo que manda el formulario ────────────────────────────────────────

	/**
	 * The files this request brings for the `file` questions of the event.
	 *
	 * **El único sitio del aplicativo que lee `$_FILES`.** Lo de dentro se
	 * comprueba entero antes de crear nada: si una pregunta obligatoria viene
	 * sin fichero, o el que viene no pasa la política, no se llega a crear la
	 * inscripción (ADR-0036).
	 *
	 * @param array<int, array<string, mixed>> $preguntas Normalised questions.
	 * @return array{ok:bool, errors:string[], files:array<string, array<string, mixed>>}
	 */
	public static function submitted( array $preguntas ): array {
		$errores = array();
		$traidos = array();

		foreach ( $preguntas as $pregunta ) {
			if ( 'file' !== $pregunta['type'] ) {
				continue;
			}
			$id      = (string) $pregunta['id'];
			$fichero = self::from_request( $id );

			if ( null === $fichero ) {
				if ( ! empty( $pregunta['required'] ) ) {
					$errores[] = 'file_missing';
				}
				continue;
			}

			$porque = self::refuse( $fichero );
			if ( '' !== $porque ) {
				$errores[] = $porque;
				continue;
			}
			$traidos[ $id ] = $fichero;
		}

		return array(
			'ok'     => array() === $errores,
			'errors' => $errores,
			'files'  => $traidos,
		);
	}

	/**
	 * One entry of `$_FILES`, normalised, or nothing when none was sent.
	 *
	 * @param string $question_id Question ID.
	 * @return array{name:string, tmp_name:string, size:int, error:int}|null
	 */
	private static function from_request( string $question_id ): ?array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- el nonce lo comprobó SignupForm::maybe_handle_submit().
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- se sanea campo a campo justo debajo.
		$campo = isset( $_FILES[ self::FIELD ] ) && is_array( $_FILES[ self::FIELD ] ) ? $_FILES[ self::FIELD ] : array();
		if ( ! isset( $campo['name'][ $question_id ] ) || ! is_scalar( $campo['name'][ $question_id ] ) ) {
			return null;
		}

		$nombre = sanitize_text_field( (string) $campo['name'][ $question_id ] );
		$tmp    = isset( $campo['tmp_name'][ $question_id ] ) ? (string) $campo['tmp_name'][ $question_id ] : '';
		$error  = isset( $campo['error'][ $question_id ] ) ? (int) $campo['error'][ $question_id ] : UPLOAD_ERR_NO_FILE;
		$tamano = isset( $campo['size'][ $question_id ] ) ? (int) $campo['size'][ $question_id ] : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( '' === $nombre && UPLOAD_ERR_NO_FILE === $error ) {
			return null;
		}

		// `is_uploaded_file()` **no se llama aquí, y no es un olvido**: esta
		// función lee `$_FILES` y nada más, y `$_FILES` lo rellena PHP con el
		// cuerpo de la petición —no hay sintaxis con la que quien llama meta
		// una ruta suya en `tmp_name`—. Esa comprobación protege a las APIs
		// que reciben el array ya montado por otro código, que no es el caso:
		// esto es privado y solo lo llama {@see submitted()}.
		//
		// El nombre que llega sí es del navegador: se queda solo con la última
		// parte, así que «../../evil.pdf» es «evil.pdf» y nada más.
		return array(
			'name'     => basename( $nombre ),
			'tmp_name' => $tmp,
			'size'     => $tamano,
			'error'    => $error,
		);
	}

	/**
	 * Why one submitted file is not acceptable, '' when it is.
	 *
	 * Del `type` que manda el navegador **no se fía nadie**: lo dice quien
	 * sube el fichero. Quien decide es `wp_check_filetype_and_ext()`, que mira
	 * lo que hay dentro del fichero y lo cruza con la extensión, y por encima
	 * nuestra propia lista cerrada.
	 *
	 * @param array{name:string, tmp_name:string, size:int, error:int} $fichero Normalised upload.
	 * @return string Error code.
	 */
	public static function refuse( array $fichero ): string {
		if ( UPLOAD_ERR_INI_SIZE === $fichero['error'] || UPLOAD_ERR_FORM_SIZE === $fichero['error'] ) {
			return 'file_too_big';
		}
		if ( UPLOAD_ERR_OK !== $fichero['error'] || '' === $fichero['tmp_name'] ) {
			return 'file_broken';
		}
		if ( $fichero['size'] <= 0 || $fichero['size'] > self::max_bytes() ) {
			return 'file_too_big';
		}

		$mimes    = self::mimes();
		$revisado = wp_check_filetype_and_ext( $fichero['tmp_name'], $fichero['name'], $mimes );
		$tipo     = isset( $revisado['type'] ) && is_string( $revisado['type'] ) ? $revisado['type'] : '';
		$ext      = isset( $revisado['ext'] ) && is_string( $revisado['ext'] ) ? $revisado['ext'] : '';

		if ( '' === $tipo || '' === $ext || ! in_array( $tipo, array_values( $mimes ), true ) ) {
			return 'file_type';
		}
		return '';
	}

	// ─── guardar ───────────────────────────────────────────────────────────

	/**
	 * Store every file of one signup, or leave nothing behind.
	 *
	 * Todo o nada: si uno falla, se borran los que ya estaban guardados y se
	 * dice que no. Quien llama deshace la inscripción (ADR-0036); lo que no
	 * puede quedar es un descriptor apuntando a nada ni un fichero suelto sin
	 * inscripción.
	 *
	 * @param int                                 $registration_id Registration post ID.
	 * @param array<string, array<string, mixed>> $files           What submitted() returned.
	 * @return bool
	 */
	public static function store_all( int $registration_id, array $files ): bool {
		if ( $registration_id <= 0 ) {
			return false;
		}
		if ( array() === $files ) {
			return true;
		}

		$descriptores = array();
		foreach ( $files as $question_id => $fichero ) {
			$descriptor = self::store( $fichero );
			if ( null === $descriptor ) {
				foreach ( $descriptores as $hecho ) {
					self::erase( $hecho );
				}
				return false;
			}
			$descriptores[ (string) $question_id ] = $descriptor;
		}

		update_post_meta(
			$registration_id,
			RegistrationMetaKeys::REG_FILES,
			wp_slash( (string) wp_json_encode( $descriptores ) )
		);
		return true;
	}

	/**
	 * Put one validated file in the private store and describe it.
	 *
	 * El nombre físico es **aleatorio y nada más**: no lleva el nombre de la
	 * persona, ni su documento, ni su correo, ni el título del evento, ni el
	 * nombre original del fichero. La extensión sale del tipo ya validado, no
	 * de lo que venía escrito.
	 *
	 * @param array{name:string, tmp_name:string, size:int, error:int} $fichero Normalised upload.
	 * @return array<string, mixed>|null Descriptor, or null when it could not be stored.
	 */
	private static function store( array $fichero ): ?array {
		if ( '' !== self::refuse( $fichero ) ) {
			return null;
		}

		$raiz = self::root();
		$fs   = self::filesystem();
		if ( '' === $raiz || null === $fs ) {
			return null;
		}

		$revisado = wp_check_filetype_and_ext( $fichero['tmp_name'], $fichero['name'], self::mimes() );
		$ext      = strtolower( (string) $revisado['ext'] );
		$mime     = (string) $revisado['type'];

		$bytes = $fs->get_contents( $fichero['tmp_name'] );
		if ( ! is_string( $bytes ) || '' === $bytes ) {
			return null;
		}

		$opaco  = bin2hex( random_bytes( 16 ) );
		$stored = substr( $opaco, 0, 2 ) . '/' . substr( $opaco, 2, 2 ) . '/' . $opaco . '.' . $ext;
		$camino = $raiz . '/' . $stored;

		if ( ! self::prepare_dir( dirname( $camino ) ) ) {
			return null;
		}
		if ( ! $fs->put_contents( $camino, $bytes, self::MODE_CLOSED ) ) {
			return null;
		}
		// Y se comprueba que quedó cerrado de verdad: guardar y dejarlo
		// legible es peor que no guardarlo, porque no se nota.
		$fs->chmod( $camino, self::MODE_CLOSED );

		return array(
			'id'     => bin2hex( random_bytes( 16 ) ),
			'name'   => sanitize_file_name( $fichero['name'] ),
			'mime'   => $mime,
			'size'   => strlen( $bytes ),
			'sha256' => hash( 'sha256', $bytes ),
			'stored' => $stored,
		);
	}

	/**
	 * Create the directory of a file, with its deny rules the first time.
	 *
	 * El `.htaccess` es un cinturón, no el pantalón: **nginx no lo lee**. Lo
	 * que cierra el paso es el modo del fichero y que la descarga pase por el
	 * aplicativo; esto se pone porque en Apache es gratis (ADR-0036).
	 *
	 * @param string $dir Absolute directory.
	 * @return bool
	 */
	private static function prepare_dir( string $dir ): bool {
		$raiz = self::root();
		$fs   = self::filesystem();
		if ( '' === $raiz || null === $fs ) {
			return false;
		}

		if ( ! $fs->is_dir( $raiz ) && ! wp_mkdir_p( $raiz ) ) {
			return false;
		}
		if ( ! $fs->exists( $raiz . '/.htaccess' ) ) {
			$fs->put_contents(
				$raiz . '/.htaccess',
				"# Los documentos de las inscripciones no se sirven directamente (ADR-0036).\n"
					. "<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n"
					. "<IfModule !mod_authz_core.c>\n\tDeny from all\n</IfModule>\n",
				FS_CHMOD_FILE
			);
		}
		if ( ! $fs->exists( $raiz . '/index.php' ) ) {
			$fs->put_contents( $raiz . '/index.php', "<?php\n// Silence is golden.\n", FS_CHMOD_FILE );
		}

		return $fs->is_dir( $dir ) || wp_mkdir_p( $dir );
	}

	// ─── leer ──────────────────────────────────────────────────────────────

	/**
	 * The descriptors of one registration, keyed by question ID.
	 *
	 * @param int $registration_id Registration post ID.
	 * @return array<string, array<string, mixed>>
	 */
	public static function descriptors( int $registration_id ): array {
		if ( $registration_id <= 0 ) {
			return array();
		}
		$crudo = get_post_meta( $registration_id, RegistrationMetaKeys::REG_FILES, true );
		$datos = is_string( $crudo ) && '' !== $crudo ? json_decode( $crudo, true ) : $crudo;
		if ( ! is_array( $datos ) ) {
			return array();
		}

		$out = array();
		foreach ( $datos as $question_id => $descriptor ) {
			if ( is_array( $descriptor ) && isset( $descriptor['id'], $descriptor['stored'] ) ) {
				$out[ (string) $question_id ] = $descriptor;
			}
		}
		return $out;
	}

	/**
	 * The descriptor with this opaque ID, or nothing.
	 *
	 * @param int    $registration_id Registration post ID.
	 * @param string $file_id         Opaque file ID.
	 * @return array<string, mixed>|null
	 */
	public static function find( int $registration_id, string $file_id ): ?array {
		if ( ! (bool) preg_match( '/^[a-f0-9]{32}$/', $file_id ) ) {
			return null;
		}
		foreach ( self::descriptors( $registration_id ) as $descriptor ) {
			if ( hash_equals( (string) $descriptor['id'], $file_id ) ) {
				return $descriptor;
			}
		}
		return null;
	}

	/**
	 * The bytes of one stored file.
	 *
	 * Se abre a propósito y se vuelve a cerrar en el `finally`: el fichero
	 * pasa el resto de su vida sin permiso de lectura.
	 *
	 * @param array<string, mixed> $descriptor Stored descriptor.
	 * @return string|null Null when it is not there or cannot be read.
	 */
	public static function read( array $descriptor ): ?string {
		$camino = self::path( $descriptor );
		$fs     = self::filesystem();
		if ( '' === $camino || null === $fs || ! $fs->exists( $camino ) ) {
			return null;
		}

		try {
			$fs->chmod( $camino, self::MODE_OPEN );
			$bytes = $fs->get_contents( $camino );
		} finally {
			$fs->chmod( $camino, self::MODE_CLOSED );
		}
		return is_string( $bytes ) ? $bytes : null;
	}

	/**
	 * The absolute path of a descriptor, '' when it is not one of ours.
	 *
	 * Dos comprobaciones y las dos hacen falta: la **forma** de `stored`, que
	 * no admite ni `..` ni una ruta absoluta, y que lo compuesto siga colgando
	 * de la raíz privada.
	 *
	 * @param array<string, mixed> $descriptor Stored descriptor.
	 * @return string
	 */
	public static function path( array $descriptor ): string {
		$stored = isset( $descriptor['stored'] ) ? (string) $descriptor['stored'] : '';
		$raiz   = self::root();
		if ( '' === $raiz || ! (bool) preg_match( self::STORED_SHAPE, $stored ) ) {
			return '';
		}
		$camino = $raiz . '/' . $stored;
		return 0 === strpos( $camino, $raiz . '/' ) ? $camino : '';
	}

	// ─── borrar ────────────────────────────────────────────────────────────

	/**
	 * Take the private files of a registration with it, when it really goes.
	 *
	 * @param int           $post_id Post being deleted for good.
	 * @param \WP_Post|null $post Its object.
	 * @return void
	 */
	public static function on_delete( int $post_id, $post = null ): void {
		$tipo = $post instanceof \WP_Post ? (string) $post->post_type : (string) get_post_type( $post_id );
		if ( RegistrationPostType::POST_TYPE !== $tipo ) {
			return;
		}
		self::delete_all( $post_id );
	}

	/**
	 * Erase every private file of one registration, and its descriptors.
	 *
	 * @param int $registration_id Registration post ID.
	 * @return void
	 */
	public static function delete_all( int $registration_id ): void {
		foreach ( self::descriptors( $registration_id ) as $descriptor ) {
			self::erase( $descriptor );
		}
		delete_post_meta( $registration_id, RegistrationMetaKeys::REG_FILES );
	}

	/**
	 * Erase one stored file.
	 *
	 * @param array<string, mixed> $descriptor Stored descriptor.
	 * @return void
	 */
	private static function erase( array $descriptor ): void {
		$camino = self::path( $descriptor );
		$fs     = self::filesystem();
		if ( '' !== $camino && null !== $fs && $fs->exists( $camino ) ) {
			$fs->delete( $camino );
		}
	}

	// ─── la descarga ───────────────────────────────────────────────────────

	/**
	 * The application URL that serves one file.
	 *
	 * Nunca la del fichero: `stored` no se convierte en URL en ningún sitio,
	 * y esta dirección no dice dónde está nada (ADR-0036).
	 *
	 * @param int    $registration_id Registration post ID.
	 * @param string $file_id         Opaque file ID.
	 * @param string $token           Signup token, for somebody without an account.
	 * @return string
	 */
	public static function url( int $registration_id, string $file_id, string $token = '' ): string {
		$args = array(
			self::ARG_REG  => $registration_id,
			self::ARG_FILE => $file_id,
		);
		if ( '' !== $token ) {
			$args[ SignupForm::ARG_TOKEN ] = rawurlencode( $token );
		}
		return add_query_arg( $args, home_url( '/' ) );
	}

	/**
	 * Serve one private file, when whoever is asking may have it.
	 *
	 * Tres cosas se resuelven aquí y en ningún otro sitio: de qué inscripción
	 * es el fichero, de qué evento es la inscripción, y si quien pregunta
	 * puede abrir ese evento. El identificador del fichero no autoriza nada
	 * por sí solo: se comprueba siempre el testigo de **esa** inscripción o el
	 * permiso sobre **ese** evento.
	 *
	 * @return void
	 */
	public static function handle(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- es una lectura, y la autorización es el testigo o la capacidad, no un nonce.
		$file_id = isset( $_GET[ self::ARG_FILE ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::ARG_FILE ] ) ) : '';
		if ( '' === $file_id ) {
			return;
		}
		$registration_id = isset( $_GET[ self::ARG_REG ] ) ? absint( wp_unslash( $_GET[ self::ARG_REG ] ) ) : 0;
		$token           = isset( $_GET[ SignupForm::ARG_TOKEN ] ) ? sanitize_text_field( wp_unslash( $_GET[ SignupForm::ARG_TOKEN ] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( $registration_id <= 0
			|| RegistrationPostType::POST_TYPE !== (string) get_post_type( $registration_id ) ) {
			self::deny();
			return;
		}

		$event_id = (int) get_post_field( 'post_parent', $registration_id );
		if ( ! self::may_read( $registration_id, $event_id, $token ) ) {
			self::deny();
			return;
		}

		$descriptor = self::find( $registration_id, $file_id );
		$bytes      = null === $descriptor ? null : self::read( $descriptor );
		if ( null === $descriptor || null === $bytes ) {
			self::deny( 404, 'Ese documento ya no está.' );
			return;
		}

		self::send( $descriptor, $bytes );
	}

	/**
	 * Whether this request may read the documents of that registration.
	 *
	 * Dos caminos y solo dos: el **testigo de esa inscripción**, que es la
	 * credencial que ya tiene quien se inscribió y de la que no hace falta
	 * inventar otra (ADR-0033); o poder **abrir** ese evento.
	 *
	 * Abrir y no editar, a propósito: un evento marcado como histórico deja de
	 * editarse, y sus inscripciones y sus documentos se siguen consultando por
	 * quien corresponde.
	 *
	 * @param int    $registration_id Registration post ID.
	 * @param int    $event_id        Its event.
	 * @param string $token           Signup token from the link.
	 * @return bool
	 */
	public static function may_read( int $registration_id, int $event_id, string $token ): bool {
		if ( $registration_id <= 0 || $event_id <= 0 ) {
			return false;
		}
		if ( '' !== $token && Registrations::by_token( $event_id, $token ) === $registration_id ) {
			return true;
		}
		return EventAccess::can_open( get_current_user_id(), $event_id );
	}

	/**
	 * Write the file down the wire.
	 *
	 * Siempre como descarga y nunca en línea: el navegador no interpreta nada
	 * de lo que se sirve desde aquí. `nosniff` cierra el paso a que lo adivine
	 * por su cuenta, y `no-store` a que quede en una caché compartida.
	 *
	 * @param array<string, mixed> $descriptor Stored descriptor.
	 * @param string               $bytes      Its contents.
	 * @return void
	 */
	private static function send( array $descriptor, string $bytes ): void {
		foreach ( self::headers( $descriptor, strlen( $bytes ) ) as $linea ) {
			Shell::send_header( $linea );
		}

		echo $bytes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- es el fichero, no HTML: se sirve como descarga y con nosniff.
		Shell::leave();
	}

	/**
	 * The response headers of a download, in order.
	 *
	 * Aparte de {@see send()} para poder comprobarlas: en la línea de órdenes
	 * la salida ya empezó y `header()` no llega a mandarse, así que un test
	 * que mire las cabeceras de verdad no comprobaría nada.
	 *
	 * @param array<string, mixed> $descriptor Stored descriptor.
	 * @param int                  $bytes      Length of the body.
	 * @return string[]
	 */
	public static function headers( array $descriptor, int $bytes ): array {
		$nombre = sanitize_file_name( (string) ( $descriptor['name'] ?? '' ) );
		if ( '' === $nombre ) {
			$nombre = 'documento';
		}

		return array(
			'Content-Type: ' . sanitize_mime_type( (string) ( $descriptor['mime'] ?? '' ) ),
			'Content-Disposition: attachment; filename="' . $nombre . '"',
			'Content-Length: ' . $bytes,
			'X-Content-Type-Options: nosniff',
			'Cache-Control: private, no-store',
		);
	}

	/**
	 * Refuse, without saying whether the file exists.
	 *
	 * @param int    $codigo HTTP status.
	 * @param string $texto  What to say.
	 * @return void
	 */
	private static function deny( int $codigo = 403, string $texto = 'No puede descargar este documento.' ): void {
		status_header( $codigo );
		Shell::send_header( 'Content-Type: text/plain; charset=utf-8' );
		Shell::send_header( 'X-Content-Type-Options: nosniff' );
		Shell::send_header( 'Cache-Control: private, no-store' );
		echo esc_html( $texto );
		Shell::leave();
	}

	// ─── lo que se dice en pantalla ────────────────────────────────────────

	/**
	 * Why the files of a signup were refused, in Spanish.
	 *
	 * @param string[] $errors Error codes.
	 * @return string Empty when there is nothing to say.
	 */
	public static function why( array $errors ): string {
		$textos = array(
			'file_missing' => 'Falta un documento obligatorio.',
			'file_too_big' => 'El documento es demasiado grande: el máximo son ' . size_format( self::max_bytes() ) . '.',
			'file_type'    => 'Ese tipo de documento no se admite. Se aceptan PDF, JPG, PNG, DOCX y ODT.',
			'file_broken'  => 'El documento no ha llegado completo. Vuelva a adjuntarlo.',
		);

		foreach ( $errors as $error ) {
			if ( isset( $textos[ $error ] ) ) {
				return $textos[ $error ];
			}
		}
		return '';
	}

	/**
	 * The WordPress filesystem API, ready to use.
	 *
	 * @return \WP_Filesystem_Base|null
	 */
	private static function filesystem(): ?\WP_Filesystem_Base {
		global $wp_filesystem;
		if ( ! $wp_filesystem instanceof \WP_Filesystem_Base ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}
		return $wp_filesystem instanceof \WP_Filesystem_Base ? $wp_filesystem : null;
	}
}
