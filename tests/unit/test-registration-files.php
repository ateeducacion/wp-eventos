<?php
/**
 * Tests for the private documents of a signup.
 *
 * @package Evt
 */

use Evt\Domain\SignupQuestions;
use Evt\Meta\RegistrationMetaKeys;
use Evt\PostType\RegistrationPostType;
use Evt\PublicFront\Participants;
use Evt\PublicFront\RegistrationFiles;
use Evt\PublicFront\Registrations;
use Evt\PublicFront\SignupForm;

/**
 * Un documento aportado en una inscripción **no es un adjunto de WordPress**.
 *
 * Es la invariante de la ADR-0036 y es lo que comprueba este fichero, de las
 * dos maneras que tiene sentido comprobarla:
 *
 * 1. Después de guardar un documento de participante, en `wp_posts` **no hay
 *    ni un adjunto más**. No hace falta filtrar nada para que no salga en la
 *    biblioteca de medios, en `wp/v2/media` ni en una página de adjunto:
 *    sencillamente no existe como adjunto.
 * 2. Y a la vez, un cartel del evento **sigue siendo** un adjunto normal con
 *    su URL pública. La privacidad la decide quién creó el fichero y para qué,
 *    no volver privada la biblioteca entera.
 *
 * Lo demás que se comprueba aquí es lo que sostiene esa decisión: la lista
 * cerrada de tipos, el tope de tamaño, el nombre físico que no cuenta nada, la
 * autorización antes de los bytes y que un fallo no deja nada a medias.
 */
class Test_Registration_Files extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Los ficheros temporales que ha fabricado un test.
	 *
	 * @var string[]
	 */
	private $temporales = array();

	/**
	 * Cada test empieza con la raíz privada vacía y suya.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		add_filter( 'evt_private_files_dir', array( $this, 'raiz_de_prueba' ) );
	}

	/**
	 * Y se la lleva al terminar, con los temporales que fabricó.
	 *
	 * @return void
	 */
	public function tear_down() {
		$this->borrar_raiz();
		foreach ( $this->temporales as $ruta ) {
			$this->fs()->delete( $ruta );
		}
		$this->temporales = array();
		remove_filter( 'evt_private_files_dir', array( $this, 'raiz_de_prueba' ) );
		parent::tear_down();
	}

	/**
	 * La raíz privada de los tests, fuera de `uploads/`.
	 *
	 * @return string
	 */
	public function raiz_de_prueba(): string {
		return rtrim( get_temp_dir(), '/' ) . '/evt-private-test';
	}

	/**
	 * La API de ficheros de WordPress, que es con la que trabaja el aplicativo.
	 *
	 * @return \WP_Filesystem_Base
	 */
	private function fs(): \WP_Filesystem_Base {
		global $wp_filesystem;
		if ( ! $wp_filesystem instanceof \WP_Filesystem_Base ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}
		return $wp_filesystem;
	}

	/**
	 * Vaciar la raíz privada.
	 *
	 * @return void
	 */
	private function borrar_raiz(): void {
		$raiz = $this->raiz_de_prueba();
		if ( $this->fs()->is_dir( $raiz ) ) {
			// Recursivo: un fichero en modo 0200 se borra igual, porque el
			// permiso que hace falta para borrar es el del directorio.
			$this->fs()->delete( $raiz, true );
		}
	}

	// ─── utilidades ────────────────────────────────────────────────────────

	/**
	 * Un fichero temporal con ese contenido, listo para entrar por `$_FILES`.
	 *
	 * @param string $nombre    Original file name.
	 * @param string $contenido Bytes.
	 * @return array{name:string, tmp_name:string, size:int, error:int}
	 */
	private function fichero( string $nombre, string $contenido ): array {
		$tmp = tempnam( get_temp_dir(), 'evtq' );
		$this->fs()->put_contents( $tmp, $contenido, FS_CHMOD_FILE );
		$this->temporales[] = $tmp;

		return array(
			'name'     => $nombre,
			'tmp_name' => $tmp,
			'size'     => strlen( $contenido ),
			'error'    => UPLOAD_ERR_OK,
		);
	}

	/**
	 * Un PDF que `finfo` reconoce como tal.
	 *
	 * @return array{name:string, tmp_name:string, size:int, error:int}
	 */
	private function un_pdf(): array {
		$cuerpo = "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";
		return $this->fichero( 'autorizacion-de-Maria-Perez.pdf', $cuerpo );
	}

	/**
	 * Poner esos ficheros en `$_FILES`, como los manda el formulario.
	 *
	 * @param array<string, array<string, mixed>> $por_pregunta Question ID => file.
	 * @return void
	 */
	private function en_files( array $por_pregunta ): void {
		$campo = array(
			'name'     => array(),
			'tmp_name' => array(),
			'size'     => array(),
			'error'    => array(),
			'type'     => array(),
		);
		foreach ( $por_pregunta as $qid => $fichero ) {
			$campo['name'][ $qid ]     = $fichero['name'];
			$campo['tmp_name'][ $qid ] = $fichero['tmp_name'];
			$campo['size'][ $qid ]     = $fichero['size'];
			$campo['error'][ $qid ]    = $fichero['error'];
			// Lo que dice el navegador, que es justo de lo que no nos fiamos.
			$campo['type'][ $qid ] = 'application/pdf';
		}
		$_FILES[ RegistrationFiles::FIELD ] = $campo;
	}

	/**
	 * Un evento con la inscripción abierta y una pregunta de tipo `file`.
	 *
	 * @param bool $obligatoria Whether the file question is required.
	 * @return array{event:int, question:string}
	 */
	private function evento_con_pregunta_de_fichero( bool $obligatoria = true ): array {
		$this->app();
		$evento = $this->event(
			$this->administrator(),
			array( $this->area( 'Innovación' ) ),
			array( RegistrationMetaKeys::SIGNUP_OPEN => true )
		);

		$pregunta = array(
			'id'       => 'qdoc0001',
			'label'    => 'Autorización firmada',
			'type'     => 'file',
			'options'  => array(),
			'required' => $obligatoria,
		);
		update_post_meta(
			$evento,
			RegistrationMetaKeys::SIGNUP_QUESTIONS,
			wp_slash( (string) wp_json_encode( array( $pregunta ) ) )
		);

		return array(
			'event'    => $evento,
			'question' => 'qdoc0001',
		);
	}

	/**
	 * Una inscripción de ese evento.
	 *
	 * @param int $evento Event post ID.
	 * @return int
	 */
	private function una_inscripcion( int $evento ): int {
		return Registrations::create(
			$evento,
			array(
				'tax_id'  => '12345678Z',
				'name'    => 'María',
				'surname' => 'Pérez',
				'email'   => 'maria@example.org',
				'phone'   => '',
				'centre'  => 'CEIP Ejemplo',
			),
			array()
		);
	}

	/**
	 * Cuántos adjuntos hay ahora mismo en la base de datos.
	 *
	 * @return int
	 */
	private function cuantos_adjuntos(): int {
		return count(
			get_posts(
				array(
					'post_type'   => 'attachment',
					'post_status' => 'any',
					'numberposts' => -1,
					'fields'      => 'ids',
				)
			)
		);
	}

	// ─── el modelo ─────────────────────────────────────────────────────────

	/**
	 * `file` es un tipo válido, sin opciones y con su identificador intacto.
	 */
	public function test_file_is_a_type_without_options() {
		$leidas = SignupQuestions::read(
			array(
				array(
					'id'       => 'qdoc0001',
					'label'    => 'Autorización firmada',
					'type'     => 'file',
					'options'  => array( 'esto', 'sobra' ),
					'required' => true,
				),
			)
		);

		$this->assertCount( 1, $leidas );
		$this->assertSame( 'file', $leidas[0]['type'] );
		$this->assertSame( 'qdoc0001', $leidas[0]['id'], 'El identificador de una pregunta es inmutable.' );
		$this->assertSame( array(), $leidas[0]['options'], 'Un fichero no tiene opciones.' );
		$this->assertTrue( $leidas[0]['required'] );
		$this->assertArrayHasKey( 'file', RegistrationMetaKeys::question_types() );
	}

	/**
	 * Una pregunta de fichero no es una respuesta: ni dato ni error en el JSON.
	 */
	public function test_a_file_question_never_lands_in_the_answers() {
		$preguntas = SignupQuestions::read(
			array(
				array(
					'id'       => 'qdoc0001',
					'label'    => 'Autorización firmada',
					'type'     => 'file',
					'required' => true,
				),
				array(
					'id'       => 'qtxt0001',
					'label'    => 'Alergias',
					'type'     => 'text',
					'required' => false,
				),
			)
		);

		$v = SignupQuestions::answers( $preguntas, array( 'qtxt0001' => 'ninguna' ) );

		$this->assertTrue( $v['ok'], 'El fichero se comprueba en el borde, no aquí.' );
		$this->assertSame( array(), $v['errors'] );
		$this->assertArrayNotHasKey( 'qdoc0001', $v['data'] );
		$this->assertSame( 'ninguna', $v['data']['qtxt0001'] );
	}

	/**
	 * Con gente inscrita, a una pregunta de fichero tampoco se le cambia el tipo.
	 */
	public function test_a_file_question_cannot_change_type_once_answered() {
		$antes = SignupQuestions::read(
			array(
				array(
					'id'    => 'qdoc0001',
					'label' => 'Autorización',
					'type'  => 'file',
				),
			)
		);
		$ahora = SignupQuestions::read(
			array(
				array(
					'id'    => 'qdoc0001',
					'label' => 'Autorización',
					'type'  => 'text',
				),
			)
		);

		$this->assertSame( array( 'type_changed:qdoc0001' ), SignupQuestions::refuse( $antes, $ahora, true ) );
	}

	// ─── guardar ───────────────────────────────────────────────────────────

	/**
	 * La invariante: un documento de participante no crea ningún adjunto.
	 */
	public function test_a_participant_document_creates_no_wordpress_attachment() {
		$datos  = $this->evento_con_pregunta_de_fichero();
		$id     = $this->una_inscripcion( $datos['event'] );
		$cuando = $this->cuantos_adjuntos();

		$this->assertTrue(
			RegistrationFiles::store_all( $id, array( $datos['question'] => $this->un_pdf() ) )
		);

		$this->assertSame(
			$cuando,
			$this->cuantos_adjuntos(),
			'Guardar un documento privado no puede crear un adjunto de WordPress (ADR-0036).'
		);

		// Y no hay nada en `wp_posts` que apunte al fichero: ni adjunto, ni
		// página de adjunto, ni por tanto `wp/v2/media` ni XML-RPC de medios.
		$this->assertSame(
			array(),
			get_posts(
				array(
					'post_type'   => 'attachment',
					'post_status' => 'any',
					'numberposts' => -1,
					's'           => 'autorizacion',
					'fields'      => 'ids',
				)
			)
		);
	}

	/**
	 * El descriptor guardado es un descriptor, y no una ruta ni una dirección.
	 */
	public function test_the_descriptor_holds_no_path_and_no_url() {
		$datos = $this->evento_con_pregunta_de_fichero();
		$id    = $this->una_inscripcion( $datos['event'] );
		$pdf   = $this->un_pdf();

		$this->assertTrue( RegistrationFiles::store_all( $id, array( $datos['question'] => $pdf ) ) );

		$descriptores = RegistrationFiles::descriptors( $id );
		$this->assertArrayHasKey( $datos['question'], $descriptores );
		$d = $descriptores[ $datos['question'] ];

		$this->assertMatchesRegularExpression( '/^[a-f0-9]{32}$/', $d['id'] );
		$this->assertSame( 'autorizacion-de-Maria-Perez.pdf', $d['name'] );
		$this->assertSame( 'application/pdf', $d['mime'] );
		$this->assertSame( $pdf['size'], $d['size'] );
		$this->assertSame( hash( 'sha256', (string) $this->fs()->get_contents( $pdf['tmp_name'] ) ), $d['sha256'] );

		$this->assertMatchesRegularExpression(
			'#^[a-f0-9]{2}/[a-f0-9]{2}/[a-f0-9]{32}\.pdf$#',
			$d['stored'],
			'`stored` es relativo a la raíz privada, y nada más.'
		);
		$this->assertStringNotContainsString( 'http', wp_json_encode( $d ) );
		$this->assertStringNotContainsString( ABSPATH, wp_json_encode( $d ) );
		$this->assertStringNotContainsString( 'uploads', wp_json_encode( $d ) );
	}

	/**
	 * El nombre físico no cuenta quién es, ni de qué evento, ni cómo se llamaba.
	 */
	public function test_the_stored_name_says_nothing() {
		$datos = $this->evento_con_pregunta_de_fichero();
		$id    = $this->una_inscripcion( $datos['event'] );

		RegistrationFiles::store_all( $id, array( $datos['question'] => $this->un_pdf() ) );
		$d = RegistrationFiles::descriptors( $id )[ $datos['question'] ];

		foreach ( array( 'autorizacion', 'maria', 'perez', '12345678', 'jornadas', 'example.org' ) as $dato ) {
			$this->assertStringNotContainsStringIgnoringCase( $dato, $d['stored'] );
		}
	}

	/**
	 * Una imagen permitida se guarda igual.
	 */
	public function test_an_allowed_image_is_stored() {
		$datos = $this->evento_con_pregunta_de_fichero();
		$id    = $this->una_inscripcion( $datos['event'] );

		$png = $this->fichero( 'foto.png', (string) $this->fs()->get_contents( DIR_TESTDATA . '/images/test-image.png' ) );
		$this->assertTrue( RegistrationFiles::store_all( $id, array( $datos['question'] => $png ) ) );
		$this->assertSame( 'image/png', RegistrationFiles::descriptors( $id )[ $datos['question'] ]['mime'] );
	}

	/**
	 * Un tipo que no está en la lista no entra, se llame como se llame.
	 */
	public function test_a_forbidden_type_is_refused() {
		$prohibidos = array(
			$this->fichero( 'shell.php', "<?php echo 'hola';" ),
			$this->fichero( 'pagina.html', '<html><body>hola</body></html>' ),
			$this->fichero( 'dibujo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>' ),
			$this->fichero( 'guion.js', 'alert(1);' ),
			$this->fichero( 'paquete.zip', "PK\x03\x04" . str_repeat( "\0", 40 ) ),
			// El truco de siempre: extensión permitida, contenido que no lo es.
			$this->fichero( 'shell.pdf', "<?php echo 'hola';" ),
		);

		foreach ( $prohibidos as $fichero ) {
			$this->assertSame(
				'file_type',
				RegistrationFiles::refuse( $fichero ),
				'No se admite ' . $fichero['name'] . '.'
			);
		}
	}

	/**
	 * Un fichero por encima del tope no entra.
	 */
	public function test_a_file_over_the_limit_is_refused() {
		$grande         = $this->un_pdf();
		$grande['size'] = RegistrationFiles::max_bytes() + 1;
		$this->assertSame( 'file_too_big', RegistrationFiles::refuse( $grande ) );

		$del_servidor          = $this->un_pdf();
		$del_servidor['error'] = UPLOAD_ERR_INI_SIZE;
		$this->assertSame( 'file_too_big', RegistrationFiles::refuse( $del_servidor ) );

		$this->assertLessThanOrEqual( 10 * MB_IN_BYTES, RegistrationFiles::max_bytes() );
	}

	/**
	 * Un envío a medias no entra.
	 */
	public function test_a_broken_upload_is_refused() {
		$roto          = $this->un_pdf();
		$roto['error'] = UPLOAD_ERR_PARTIAL;
		$this->assertSame( 'file_broken', RegistrationFiles::refuse( $roto ) );
	}

	/**
	 * Si uno falla, no queda ni el que ya se había guardado ni el descriptor.
	 */
	public function test_a_failure_leaves_nothing_behind() {
		$datos = $this->evento_con_pregunta_de_fichero();
		$id    = $this->una_inscripcion( $datos['event'] );

		$ok = RegistrationFiles::store_all(
			$id,
			array(
				'qdoc0001' => $this->un_pdf(),
				'qdoc0002' => $this->fichero( 'shell.php', "<?php echo 'hola';" ),
			)
		);

		$this->assertFalse( $ok );
		$this->assertSame( array(), RegistrationFiles::descriptors( $id ), 'Ni un descriptor apuntando a nada.' );
		$this->assertSame(
			array(),
			glob( $this->raiz_de_prueba() . '/*/*/*' ),
			'Ni un fichero huérfano.'
		);
	}

	/**
	 * El fichero guardado no se puede leer directamente del disco.
	 */
	public function test_a_stored_file_is_not_readable_from_outside() {
		$datos = $this->evento_con_pregunta_de_fichero();
		$id    = $this->una_inscripcion( $datos['event'] );
		RegistrationFiles::store_all( $id, array( $datos['question'] => $this->un_pdf() ) );

		$d      = RegistrationFiles::descriptors( $id )[ $datos['question'] ];
		$camino = RegistrationFiles::path( $d );

		$this->assertFileExists( $camino );
		$this->assertSame( '0200', substr( sprintf( '%o', fileperms( $camino ) ), -4 ) );
		$this->assertFileExists( $this->raiz_de_prueba() . '/.htaccess' );
		// Y el aplicativo sí lo lee, abriéndolo y volviéndolo a cerrar.
		$this->assertSame( '%PDF', substr( (string) RegistrationFiles::read( $d ), 0, 4 ) );
		$this->assertSame( '0200', substr( sprintf( '%o', fileperms( $camino ) ), -4 ) );
	}

	/**
	 * Un `stored` que no tenga nuestra forma no resuelve a ninguna ruta.
	 */
	public function test_a_path_outside_the_private_root_resolves_to_nothing() {
		foreach ( array( '../../wp-config.php', '/etc/passwd', 'ab/cd/../../../x.pdf', 'ab/cd/ef.exe' ) as $malo ) {
			$this->assertSame( '', RegistrationFiles::path( array( 'stored' => $malo ) ) );
		}
	}

	// ─── el alta entera ────────────────────────────────────────────────────

	/**
	 * Sin el documento obligatorio no se crea ninguna inscripción.
	 */
	public function test_a_missing_required_file_creates_no_registration() {
		$datos = $this->evento_con_pregunta_de_fichero( true );

		$this->post(
			array(
				SignupForm::FIELD_OP    => SignupForm::OP_SIGNUP,
				SignupForm::FIELD_EVENT => $datos['event'],
				'tax_id'                => '12345678Z',
				'name'                  => 'María',
				'surname'               => 'Pérez',
				'email'                 => 'maria@example.org',
				'centre'                => 'CEIP Ejemplo',
				'consent'               => '1',
			),
			SignupForm::NONCE_ACTION,
			SignupForm::NONCE_FIELD
		);
		add_filter( 'evt_centres', fn() => array( 'CEIP Ejemplo' ) );

		SignupForm::maybe_handle_submit();

		$this->assertSame( array(), Registrations::all( $datos['event'] ), 'Sin documento no hay inscripción.' );
		$aviso = SignupForm::notice();
		$this->assertIsArray( $aviso );
		$this->assertStringContainsString( 'documento obligatorio', $aviso['message'] );
	}

	/**
	 * Un tipo prohibido tampoco crea inscripción, y lo dice.
	 */
	public function test_a_forbidden_file_creates_no_registration() {
		$datos = $this->evento_con_pregunta_de_fichero( true );
		$this->en_files( array( $datos['question'] => $this->fichero( 'shell.php', "<?php echo 'hola';" ) ) );

		$this->post(
			array(
				SignupForm::FIELD_OP    => SignupForm::OP_SIGNUP,
				SignupForm::FIELD_EVENT => $datos['event'],
				'tax_id'                => '12345678Z',
				'name'                  => 'María',
				'surname'               => 'Pérez',
				'email'                 => 'maria@example.org',
				'centre'                => 'CEIP Ejemplo',
				'consent'               => '1',
			),
			SignupForm::NONCE_ACTION,
			SignupForm::NONCE_FIELD
		);
		add_filter( 'evt_centres', fn() => array( 'CEIP Ejemplo' ) );

		SignupForm::maybe_handle_submit();

		$this->assertSame( array(), Registrations::all( $datos['event'] ) );
		$this->assertStringContainsString( 'no se admite', SignupForm::notice()['message'] );
	}

	/**
	 * El alta completa guarda la inscripción y su documento, y sigue sin adjuntos.
	 */
	public function test_a_whole_signup_stores_its_document_and_no_attachment() {
		$datos   = $this->evento_con_pregunta_de_fichero( true );
		$cuantos = $this->cuantos_adjuntos();
		$this->en_files( array( $datos['question'] => $this->un_pdf() ) );

		$this->post(
			array(
				SignupForm::FIELD_OP    => SignupForm::OP_SIGNUP,
				SignupForm::FIELD_EVENT => $datos['event'],
				'tax_id'                => '12345678Z',
				'name'                  => 'María',
				'surname'               => 'Pérez',
				'email'                 => 'maria@example.org',
				'centre'                => 'CEIP Ejemplo',
				'consent'               => '1',
			),
			SignupForm::NONCE_ACTION,
			SignupForm::NONCE_FIELD
		);
		add_filter( 'evt_centres', fn() => array( 'CEIP Ejemplo' ) );

		$this->exit_url( array( SignupForm::class, 'maybe_handle_submit' ) );

		$inscripciones = Registrations::all( $datos['event'] );
		$this->assertCount( 1, $inscripciones );
		$this->assertCount( 1, RegistrationFiles::descriptors( (int) $inscripciones[0]->ID ) );
		$this->assertSame( $cuantos, $this->cuantos_adjuntos() );
	}

	// ─── la autorización ───────────────────────────────────────────────────

	/**
	 * Servir un documento con el testigo de esa inscripción, y con ningún otro.
	 *
	 * @return array{event:int, reg:int, file:string, token:string}
	 */
	private function un_documento_guardado(): array {
		$datos = $this->evento_con_pregunta_de_fichero();
		$id    = $this->una_inscripcion( $datos['event'] );
		RegistrationFiles::store_all( $id, array( $datos['question'] => $this->un_pdf() ) );

		return array(
			'event' => $datos['event'],
			'reg'   => $id,
			'file'  => RegistrationFiles::descriptors( $id )[ $datos['question'] ]['id'],
			'token' => (string) get_post_meta( $id, RegistrationMetaKeys::REG_TOKEN, true ),
		);
	}

	/**
	 * Pedir un documento y devolver lo servido.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @return string
	 */
	private function pedir( array $args ): string {
		$_GET = $args;
		return $this->served( array( RegistrationFiles::class, 'handle' ) );
	}

	/**
	 * Con su testigo, la persona inscrita se descarga su documento.
	 */
	public function test_the_owner_token_downloads_the_file() {
		$d = $this->un_documento_guardado();
		$this->acting_as( 0 );

		$cuerpo = $this->pedir(
			array(
				RegistrationFiles::ARG_REG  => $d['reg'],
				RegistrationFiles::ARG_FILE => $d['file'],
				SignupForm::ARG_TOKEN       => $d['token'],
			)
		);

		$this->assertSame( '%PDF-1.4', substr( $cuerpo, 0, 8 ), 'Los bytes son los del fichero.' );
	}

	/**
	 * El testigo de otra inscripción no abre este documento.
	 */
	public function test_the_token_of_another_registration_opens_nothing() {
		$d    = $this->un_documento_guardado();
		$otra = $this->una_inscripcion( $d['event'] );
		$this->acting_as( 0 );

		$cuerpo = $this->pedir(
			array(
				RegistrationFiles::ARG_REG  => $d['reg'],
				RegistrationFiles::ARG_FILE => $d['file'],
				SignupForm::ARG_TOKEN       => (string) get_post_meta( $otra, RegistrationMetaKeys::REG_TOKEN, true ),
			)
		);

		$this->assertStringNotContainsString( '%PDF', $cuerpo );
		$this->assertStringContainsString( 'No puede descargar', $cuerpo );
	}

	/**
	 * Sin testigo y sin sesión, no.
	 */
	public function test_anonymous_without_a_token_gets_nothing() {
		$d = $this->un_documento_guardado();
		$this->acting_as( 0 );

		$cuerpo = $this->pedir(
			array(
				RegistrationFiles::ARG_REG  => $d['reg'],
				RegistrationFiles::ARG_FILE => $d['file'],
			)
		);

		$this->assertStringNotContainsString( '%PDF', $cuerpo );
	}

	/**
	 * Quien puede abrir el evento se lo descarga, aunque el evento sea histórico.
	 */
	public function test_whoever_can_open_the_event_downloads_it_even_when_archived() {
		$this->app();
		$area   = $this->area( 'Innovación' );
		$quien  = $this->organiser( array( $area ) );
		$evento = $this->event(
			$this->administrator(),
			array( $area ),
			array( RegistrationMetaKeys::SIGNUP_OPEN => true )
		);
		update_post_meta(
			$evento,
			RegistrationMetaKeys::SIGNUP_QUESTIONS,
			wp_slash(
				(string) wp_json_encode(
					array(
						array(
							'id'    => 'qdoc0001',
							'label' => 'Autorización',
							'type'  => 'file',
						),
					)
				)
			)
		);
		$id = $this->una_inscripcion( $evento );
		RegistrationFiles::store_all( $id, array( 'qdoc0001' => $this->un_pdf() ) );
		$file = RegistrationFiles::descriptors( $id )['qdoc0001']['id'];

		// Histórico: ya no se edita, pero se sigue consultando (ADR-0017).
		update_post_meta( $evento, \Evt\Meta\EventMetaKeys::ARCHIVED, true );
		$this->assertFalse( \Evt\Access\EventAccess::can_edit( $quien, $evento ) );

		$this->acting_as( $quien );
		$cuerpo = $this->pedir(
			array(
				RegistrationFiles::ARG_REG  => $id,
				RegistrationFiles::ARG_FILE => $file,
			)
		);

		$this->assertSame( '%PDF-1.4', substr( $cuerpo, 0, 8 ) );
	}

	/**
	 * Quien organiza otra área no lo abre.
	 */
	public function test_another_area_gets_nothing() {
		$d     = $this->un_documento_guardado();
		$otra  = $this->area( 'Otra área' );
		$ajena = $this->organiser( array( $otra ) );

		$this->acting_as( $ajena );
		$cuerpo = $this->pedir(
			array(
				RegistrationFiles::ARG_REG  => $d['reg'],
				RegistrationFiles::ARG_FILE => $d['file'],
			)
		);

		$this->assertStringNotContainsString( '%PDF', $cuerpo );
		$this->assertStringContainsString( 'No puede descargar', $cuerpo );
	}

	/**
	 * El identificador de un documento de otra inscripción no vale aquí.
	 */
	public function test_a_file_id_of_another_registration_is_not_found() {
		$uno = $this->un_documento_guardado();
		$dos = $this->una_inscripcion( $uno['event'] );
		RegistrationFiles::store_all( $dos, array( 'qdoc0001' => $this->un_pdf() ) );

		$this->assertNull(
			RegistrationFiles::find( $dos, $uno['file'] ),
			'Un identificador opaco solo vale dentro de su inscripción.'
		);
	}

	/**
	 * La respuesta va como descarga, con su tipo y sin caché compartida.
	 */
	public function test_the_response_headers_are_the_right_ones() {
		$cabeceras = RegistrationFiles::headers(
			array(
				'name' => 'autorizacion.pdf',
				'mime' => 'application/pdf',
			),
			1234
		);

		$this->assertSame(
			array(
				'Content-Type: application/pdf',
				'Content-Disposition: attachment; filename="autorizacion.pdf"',
				'Content-Length: 1234',
				'X-Content-Type-Options: nosniff',
				'Cache-Control: private, no-store',
			),
			$cabeceras
		);
	}

	// ─── el ciclo de vida ──────────────────────────────────────────────────

	/**
	 * Borrar definitivamente una inscripción se lleva sus documentos.
	 */
	public function test_deleting_a_registration_for_good_deletes_its_files() {
		$d      = $this->un_documento_guardado();
		$camino = RegistrationFiles::path( RegistrationFiles::descriptors( $d['reg'] )['qdoc0001'] );
		$this->assertFileExists( $camino );

		wp_delete_post( $d['reg'], true );

		$this->assertFileDoesNotExist( $camino, 'El borrado definitivo se lleva el fichero (ADR-0036).' );
	}

	/**
	 * La papelera no: una inscripción restaurable conserva sus documentos.
	 */
	public function test_trashing_a_registration_keeps_its_files() {
		$d      = $this->un_documento_guardado();
		$camino = RegistrationFiles::path( RegistrationFiles::descriptors( $d['reg'] )['qdoc0001'] );

		wp_trash_post( $d['reg'] );

		$this->assertFileExists( $camino, 'Restaurar una inscripción sin sus documentos es restaurar otra cosa.' );
	}

	// ─── la pestaña de participantes ───────────────────────────────────────

	/**
	 * El CSV lleva el nombre del documento y ninguna ruta ni dirección.
	 */
	public function test_the_csv_carries_the_name_and_never_a_path() {
		$d     = $this->un_documento_guardado();
		$filas = Participants::rows( $d['event'] );

		$this->assertCount( 1, $filas );
		$this->assertSame( 'autorizacion-de-Maria-Perez.pdf', $filas[0]['files'] );

		$csv = Participants::csv( $filas );
		$this->assertStringContainsString( 'autorizacion-de-Maria-Perez.pdf', $csv );
		foreach ( array( 'evt-private', 'wp-content/uploads', 'http', $d['file'], ABSPATH ) as $prohibido ) {
			$this->assertStringNotContainsString( $prohibido, $csv );
		}
	}

	/**
	 * Y la fila lleva aparte lo que la pantalla necesita para el botón.
	 */
	public function test_the_row_carries_the_documents_for_the_screen_only() {
		$d     = $this->un_documento_guardado();
		$filas = Participants::rows( $d['event'] );

		$this->assertArrayHasKey( Participants::KEY_FILES, $filas[0] );
		$this->assertSame( $d['file'], $filas[0][ Participants::KEY_FILES ][0]['id'] );
		$this->assertSame( $d['reg'], $filas[0][ Participants::KEY_FILES ][0]['reg'] );
		$this->assertArrayNotHasKey( 'stored', $filas[0][ Participants::KEY_FILES ][0] );

		// Y el filtro no busca dentro de eso: busca en las columnas.
		$this->assertSame( $filas, Participants::filter( $filas, 'autorizacion' ) );
		$this->assertSame( array(), Participants::filter( $filas, $d['file'] ) );
	}

	// ─── y lo público sigue público ────────────────────────────────────────

	/**
	 * Un cartel del evento sigue siendo un adjunto normal con su URL pública.
	 *
	 * Es la otra mitad de la ADR-0036: la privacidad la decide quién creó el
	 * fichero y para qué, no volver privada la biblioteca entera.
	 */
	public function test_a_public_event_image_is_still_a_normal_attachment() {
		$datos = $this->evento_con_pregunta_de_fichero();
		$id    = $this->una_inscripcion( $datos['event'] );
		RegistrationFiles::store_all( $id, array( $datos['question'] => $this->un_pdf() ) );

		$cartel = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg', $datos['event'] );

		$this->assertSame( 'attachment', get_post_type( $cartel ) );
		$this->assertNotFalse( wp_get_attachment_url( $cartel ), 'El cartel tiene su URL pública, como siempre.' );
		$this->assertTrue( wp_attachment_is_image( $cartel ) );
		$this->assertContains(
			$cartel,
			get_posts(
				array(
					'post_type'   => 'attachment',
					'post_status' => 'any',
					'numberposts' => -1,
					'fields'      => 'ids',
				)
			),
			'Y sigue en la biblioteca de medios.'
		);
	}

	/**
	 * La inscripción sigue sin abrir ninguna puerta, tampoco con ficheros.
	 */
	public function test_the_files_meta_is_as_closed_as_the_rest() {
		$this->app();
		$tipos = get_registered_meta_keys( 'post', RegistrationPostType::POST_TYPE );

		$this->assertArrayHasKey( RegistrationMetaKeys::REG_FILES, $tipos );
		$this->assertFalse( $tipos[ RegistrationMetaKeys::REG_FILES ]['show_in_rest'] );
		$this->assertFalse(
			call_user_func( $tipos[ RegistrationMetaKeys::REG_FILES ]['auth_callback'] ),
			'Ninguna vía escribe esta meta desde fuera del aplicativo (ADR-0032).'
		);
	}
}
