<?php
/**
 * Tests for the private documents of a signup.
 *
 * @package Evt
 */

use Evt\Domain\SignupQuestions;
use Evt\Meta\RegistrationMetaKeys;
use Evt\Meta\RegistrationMetaRegistration;
use Evt\PostType\RegistrationPostType;
use Evt\PublicFront\Block\SignupBlock;
use Evt\PublicFront\Participants;
use Evt\PublicFront\RegistrationFiles;
use Evt\PublicFront\Registrations;
use Evt\PublicFront\SignupForm;
use Evt\PublicFront\View\EventParticipantsPanel;

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

	// Con alias porque esta clase define su propio `tear_down()`: un método de
	// la clase gana al del trait, así que sin esto el de las fixtures **no se
	// llamaría** y `$_FILES` y `$_GET` se colarían de un test al siguiente.
	use Evt_Fixtures {
		tear_down as fixtures_tear_down;
	}

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
		// Y **todos**, no solo el nuestro: `almacen_roto()` engancha un cierre
		// anónimo que no se puede quitar por referencia, y dejarlo puesto
		// haría que el test siguiente escribiera en una raíz rota.
		remove_all_filters( 'evt_private_files_dir' );
		$this->fixtures_tear_down();
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

	// ─── lo que ve quien se inscribe ───────────────────────────────────────

	/**
	 * El formulario público pinta el campo de fichero, y viaja como fichero.
	 *
	 * Es lo único que ve quien se inscribe, así que es lo que hay que
	 * comprobar: sin `multipart/form-data` el documento no llega, y sin
	 * `<input type="file">` no hay dónde adjuntarlo.
	 */
	public function test_the_public_form_paints_a_file_field() {
		$datos = $this->evento_con_pregunta_de_fichero( true );
		add_filter( 'evt_centres', fn() => array( 'CEIP Ejemplo' ) );

		$html = SignupBlock::html(
			array(
				'section_type' => SignupBlock::NAME,
				'event_id'     => $datos['event'],
			)
		);

		$this->assertStringContainsString( 'enctype="multipart/form-data"', $html, 'Sin esto el fichero no sube.' );
		$this->assertStringContainsString( 'type="file"', $html );
		$this->assertStringContainsString(
			'name="' . RegistrationFiles::FIELD . '[' . $datos['question'] . ']"',
			$html,
			'El fichero viaja bajo el identificador de su pregunta.'
		);
		$this->assertStringContainsString( 'Autorización firmada', $html );
		$this->assertStringContainsString( 'required', $html );
		// Y dice lo que admite, que es la política del aplicativo.
		$this->assertStringContainsString( 'application/pdf', $html );
		$this->assertStringContainsString( esc_html( size_format( RegistrationFiles::max_bytes() ) ), $html );
		// Sin Base64 por ningún lado: sube como fichero HTTP normal.
		$this->assertStringNotContainsString( 'base64', strtolower( $html ) );
	}

	/**
	 * Una pregunta de texto sigue pintándose como texto.
	 */
	public function test_a_text_question_is_still_a_text_field() {
		$this->app();
		$evento = $this->event(
			$this->administrator(),
			array( $this->area( 'Innovación' ) ),
			array( RegistrationMetaKeys::SIGNUP_OPEN => true )
		);
		update_post_meta(
			$evento,
			RegistrationMetaKeys::SIGNUP_QUESTIONS,
			wp_slash(
				(string) wp_json_encode(
					array(
						array(
							'id'    => 'qtxt0001',
							'label' => 'Alergias',
							'type'  => 'text',
						),
					)
				)
			)
		);
		add_filter( 'evt_centres', fn() => array( 'CEIP Ejemplo' ) );

		$html = SignupBlock::html(
			array(
				'section_type' => SignupBlock::NAME,
				'event_id'     => $evento,
			)
		);

		$this->assertStringContainsString( 'name="' . SignupForm::FIELD_ANSWERS . '[qtxt0001]"', $html );
		$this->assertStringContainsString( 'maxlength="250"', $html );
		$this->assertStringNotContainsString( 'type="file"', $html );
	}

	/**
	 * La pestaña «Participantes» ofrece la descarga, y no una ruta.
	 */
	public function test_the_participants_tab_offers_the_download() {
		$this->pages();
		$d     = $this->un_documento_guardado();
		$filas = Participants::rows( $d['event'] );

		$html = EventParticipantsPanel::html(
			array(
				'event_id'      => $d['event'],
				'people'        => $filas,
				'people_total'  => count( $filas ),
				'people_cols'   => Participants::columns(),
				'people_tags'   => array(),
				'people_q'      => '',
				'people_filter' => '',
				'form_id'       => 0,
			)
		);

		$this->assertStringContainsString( 'Documentos', $html, 'La columna tiene su cabecera.' );
		$this->assertStringContainsString( 'autorizacion-de-Maria-Perez.pdf', $html );
		$this->assertStringContainsString( RegistrationFiles::ARG_FILE . '=' . $d['file'], $html );
		$this->assertStringContainsString( RegistrationFiles::ARG_REG . '=' . $d['reg'], $html );
		$this->assertStringContainsString( 'download', $html );
		// Y nunca la ruta física ni el testigo de nadie.
		$this->assertStringNotContainsString( 'evt-private', $html );
		$this->assertStringNotContainsString( $d['token'], $html );
	}

	/**
	 * Sin documentos, la celda dice que no hay y no pinta ningún enlace.
	 */
	public function test_a_row_without_documents_paints_no_link() {
		$this->pages();
		$datos = $this->evento_con_pregunta_de_fichero();
		$this->una_inscripcion( $datos['event'] );
		$filas = Participants::rows( $datos['event'] );

		$html = EventParticipantsPanel::html(
			array(
				'event_id'      => $datos['event'],
				'people'        => $filas,
				'people_total'  => count( $filas ),
				'people_cols'   => Participants::columns(),
				'people_tags'   => array(),
				'people_q'      => '',
				'people_filter' => '',
				'form_id'       => 0,
			)
		);

		$this->assertStringNotContainsString( RegistrationFiles::ARG_FILE . '=', $html );
	}

	/**
	 * Una fila que conteste otro origen, sin documentos, se pinta igual.
	 *
	 * La costura del filtro `evt_participants` sigue abierta (ADR-0027): un
	 * despliegue puede traer sus participantes desde un snippet, y esa fila no
	 * sabe nada de `evt_reg_files`. La pantalla no puede romperse por eso.
	 */
	public function test_a_row_from_another_source_paints_without_documents() {
		$this->pages();
		$datos = $this->evento_con_pregunta_de_fichero();

		add_filter(
			Participants::HOOK,
			static function ( array $filas ): array {
				$filas[] = array(
					'name'   => 'Alguien De Otro Sitio',
					'email'  => 'alguien@example.org',
					'centre' => 'CEIP Ejemplo',
				);
				return $filas;
			},
			20
		);

		$filas = Participants::rows( $datos['event'] );
		$this->assertCount( 1, $filas );
		$this->assertArrayNotHasKey( Participants::KEY_FILES, $filas[0], 'Ese origen no trae documentos.' );

		$html = EventParticipantsPanel::html(
			array(
				'event_id'      => $datos['event'],
				'people'        => $filas,
				'people_total'  => count( $filas ),
				'people_cols'   => Participants::columns(),
				'people_tags'   => array(),
				'people_q'      => '',
				'people_filter' => '',
				'form_id'       => 0,
			)
		);

		$this->assertStringContainsString( 'Alguien De Otro Sitio', $html );
		$this->assertStringNotContainsString( RegistrationFiles::ARG_FILE . '=', $html );
	}

	/**
	 * El enlace que se le da a quien se inscribe lleva su testigo.
	 *
	 * Es la única credencial que tiene esa persona (ADR-0033): sin el testigo
	 * en el enlace, no puede abrir su propio documento.
	 */
	public function test_the_link_for_the_participant_carries_the_token() {
		$d   = $this->un_documento_guardado();
		$url = RegistrationFiles::url( $d['reg'], $d['file'], $d['token'] );

		$this->assertSame( (string) $d['reg'], $this->query_arg( $url, RegistrationFiles::ARG_REG ) );
		$this->assertSame( $d['file'], $this->query_arg( $url, RegistrationFiles::ARG_FILE ) );
		$this->assertSame( $d['token'], $this->query_arg( $url, SignupForm::ARG_TOKEN ) );

		// Y sin testigo no lo lleva: el de la pantalla de gestión no filtra la
		// credencial de nadie.
		$this->assertSame( '', $this->query_arg( RegistrationFiles::url( $d['reg'], $d['file'] ), SignupForm::ARG_TOKEN ) );
	}

	// ─── el borde: peticiones que no son nuestras, o que vienen tocadas ────

	/**
	 * Una petición sin nuestro parámetro no la toca nadie.
	 *
	 * Esto corre en **cada carga de página del sitio**, así que tiene que
	 * salirse sin mirar nada más.
	 */
	public function test_a_request_without_our_argument_is_left_alone() {
		$_GET = array( 'otra' => 'cosa' );

		$this->assertSame( '', $this->served( array( RegistrationFiles::class, 'handle' ) ) );
	}

	/**
	 * Un identificador de inscripción tocado a mano no abre nada.
	 */
	public function test_a_tampered_registration_id_is_denied() {
		$d      = $this->un_documento_guardado();
		$evento = $d['event'];

		foreach ( array( 0, -1, $evento ) as $tocado ) {
			$_GET   = array(
				RegistrationFiles::ARG_REG  => $tocado,
				RegistrationFiles::ARG_FILE => $d['file'],
				SignupForm::ARG_TOKEN       => $d['token'],
			);
			$cuerpo = $this->served( array( RegistrationFiles::class, 'handle' ) );

			$this->assertStringNotContainsString( '%PDF', $cuerpo, 'Ni con el testigo bueno: ' . $tocado );
			$this->assertStringContainsString( 'No puede descargar', $cuerpo );
		}
	}

	/**
	 * Un identificador de fichero que no tiene nuestra forma no se busca.
	 */
	public function test_a_malformed_file_id_finds_nothing() {
		$d = $this->un_documento_guardado();

		foreach ( array( 'nope', '../../etc/passwd', str_repeat( 'z', 32 ), '' ) as $malo ) {
			$this->assertNull( RegistrationFiles::find( $d['reg'], $malo ) );
		}
	}

	/**
	 * Con el descriptor puesto y el fichero ya no en disco, se responde 404.
	 *
	 * Pasa de verdad: una restauración a medias, o una limpieza a mano. Lo que
	 * no puede pasar es que el aplicativo sirva basura o se caiga.
	 */
	public function test_a_descriptor_without_its_file_answers_that_it_is_gone() {
		$d = $this->un_documento_guardado();
		$this->fs()->delete( RegistrationFiles::path( RegistrationFiles::descriptors( $d['reg'] )['qdoc0001'] ) );

		$this->acting_as( 0 );
		$_GET   = array(
			RegistrationFiles::ARG_REG  => $d['reg'],
			RegistrationFiles::ARG_FILE => $d['file'],
			SignupForm::ARG_TOKEN       => $d['token'],
		);
		$cuerpo = $this->served( array( RegistrationFiles::class, 'handle' ) );

		$this->assertStringContainsString( 'ya no está', $cuerpo );
		$this->assertNull( RegistrationFiles::read( RegistrationFiles::descriptors( $d['reg'] )['qdoc0001'] ) );
	}

	/**
	 * Borrar cualquier otra cosa no dispara la limpieza.
	 *
	 * El gancho es global: cuelga de `before_delete_post` y lo recibe el
	 * borrado de cualquier entrada del sitio.
	 */
	public function test_deleting_anything_else_touches_no_file() {
		$d      = $this->un_documento_guardado();
		$camino = RegistrationFiles::path( RegistrationFiles::descriptors( $d['reg'] )['qdoc0001'] );

		wp_delete_post( (int) self::factory()->post->create( array( 'post_type' => 'post' ) ), true );

		$this->assertFileExists( $camino );
		$this->assertCount( 1, RegistrationFiles::descriptors( $d['reg'] ) );
	}

	/**
	 * Una pregunta que no es de fichero no aporta ni error ni fichero.
	 */
	public function test_questions_that_are_not_files_are_skipped() {
		$mezcla = SignupQuestions::read(
			array(
				array(
					'id'       => 'qtxt0001',
					'label'    => 'Alergias',
					'type'     => 'text',
					'required' => true,
				),
				array(
					'id'       => 'qchk0001',
					'label'    => 'Se queda a comer',
					'type'     => 'check',
					'required' => true,
				),
			)
		);

		$v = RegistrationFiles::submitted( $mezcla );

		$this->assertTrue( $v['ok'], 'Sin preguntas de fichero no hay nada que comprobar aquí.' );
		$this->assertSame( array(), $v['files'] );
	}

	/**
	 * Un campo de fichero que se deja vacío no es un error, si no es obligatorio.
	 *
	 * Es lo que manda el navegador de verdad cuando no se elige nada: el campo
	 * viaja igual, con el nombre en blanco y `UPLOAD_ERR_NO_FILE`.
	 */
	public function test_an_empty_file_field_is_not_a_file() {
		$datos                              = $this->evento_con_pregunta_de_fichero( false );
		$_FILES[ RegistrationFiles::FIELD ] = array(
			'name'     => array( $datos['question'] => '' ),
			'tmp_name' => array( $datos['question'] => '' ),
			'size'     => array( $datos['question'] => 0 ),
			'error'    => array( $datos['question'] => UPLOAD_ERR_NO_FILE ),
			'type'     => array( $datos['question'] => '' ),
		);

		$v = RegistrationFiles::submitted( Registrations::questions( $datos['event'] ) );

		$this->assertTrue( $v['ok'] );
		$this->assertSame( array(), $v['files'] );
	}

	// ─── el almacén que no se puede escribir ───────────────────────────────

	/**
	 * Dejar la raíz privada donde no se puede crear.
	 *
	 * Un fichero no es un directorio, así que `wp_mkdir_p()` no puede colgar
	 * nada de él: es la forma limpia de comprobar qué pasa cuando el almacén
	 * no está disponible, sin romper nada más.
	 *
	 * @return void
	 */
	private function almacen_roto(): void {
		$tapon = tempnam( get_temp_dir(), 'evtno' );
		$this->fs()->put_contents( $tapon, 'no soy un directorio', FS_CHMOD_FILE );
		$this->temporales[] = $tapon;

		remove_filter( 'evt_private_files_dir', array( $this, 'raiz_de_prueba' ) );
		add_filter(
			'evt_private_files_dir',
			static function () use ( $tapon ): string {
				return $tapon . '/dentro';
			}
		);
	}

	/**
	 * Sin almacén no se guarda nada, y se dice que no.
	 */
	public function test_without_a_usable_store_nothing_is_saved() {
		$datos = $this->evento_con_pregunta_de_fichero();
		$id    = $this->una_inscripcion( $datos['event'] );
		$this->almacen_roto();

		$this->assertFalse( RegistrationFiles::store_all( $id, array( $datos['question'] => $this->un_pdf() ) ) );
		$this->assertSame( array(), RegistrationFiles::descriptors( $id ) );
	}

	/**
	 * Y en el alta entera: si el documento no se puede guardar, no hay inscripción.
	 *
	 * Es la regla de la ADR-0036 comprobada de punta a punta y por el camino
	 * que de verdad se recorre: el formulario. No puede quedar una inscripción
	 * registrada cuyo documento obligatorio no está en ningún sitio.
	 */
	public function test_a_signup_whose_document_cannot_be_stored_leaves_no_registration() {
		$datos = $this->evento_con_pregunta_de_fichero( true );
		$this->en_files( array( $datos['question'] => $this->un_pdf() ) );
		$this->almacen_roto();

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

		$this->assertSame(
			array(),
			Registrations::all( $datos['event'] ),
			'La inscripción se deshace entera: fail closed (ADR-0036).'
		);
		$this->assertStringContainsString( 'no se ha registrado', SignupForm::notice()['message'] );
	}

	// ─── guardas pequeñas que se cruzan todos los días ─────────────────────

	/**
	 * Sin inscripción no hay descriptores ni hay nada que guardar.
	 */
	public function test_no_registration_means_nothing_to_store_or_read() {
		$this->app();

		$this->assertSame( array(), RegistrationFiles::descriptors( 0 ) );
		$this->assertFalse( RegistrationFiles::store_all( 0, array() ) );
		$this->assertTrue( RegistrationFiles::store_all( 1, array() ), 'Sin ficheros no hay nada que hacer.' );
		$this->assertFalse( RegistrationFiles::may_read( 0, 0, '' ), 'Falla en cerrado.' );
	}

	/**
	 * Un descriptor sin nombre se sirve igual, con uno genérico.
	 */
	public function test_a_descriptor_without_a_name_still_downloads() {
		$cabeceras = RegistrationFiles::headers( array( 'mime' => 'application/pdf' ), 0 );

		$this->assertContains( 'Content-Disposition: attachment; filename="documento"', $cabeceras );
	}

	/**
	 * Un error que no conocemos no inventa un mensaje.
	 */
	public function test_an_unknown_error_says_nothing() {
		$this->assertSame( '', RegistrationFiles::why( array( 'lo_que_sea' ) ) );
		$this->assertSame( '', RegistrationFiles::why( array() ) );
	}

	// ─── lo que se guarda en la meta ───────────────────────────────────────

	/**
	 * La meta tira lo que no tenga forma de descriptor.
	 *
	 * Es la última red antes de la base de datos: aunque algo llegue aquí por
	 * un camino que hoy no existe, lo que no sea un descriptor nuestro no se
	 * guarda. En particular, **una ruta absoluta o una URL no pasan**.
	 */
	public function test_the_meta_throws_away_anything_that_is_not_a_descriptor() {
		$bueno = array(
			'id'     => str_repeat( 'a', 32 ),
			'name'   => 'acta.pdf',
			'mime'   => 'application/pdf',
			'size'   => 10,
			'sha256' => str_repeat( 'b', 64 ),
			'stored' => 'aa/bb/' . str_repeat( 'a', 32 ) . '.pdf',
		);

		$limpio = json_decode(
			RegistrationMetaRegistration::sanitize_files(
				array(
					'qdoc0001'     => $bueno,
					// Una clave que no es de una pregunta nuestra.
					'no-es-una-id' => $bueno,
					// Un descriptor que no lo es.
					'qdoc0002'     => 'una cadena',
					// Ruta absoluta en `stored`.
					'qdoc0003'     => array_merge( $bueno, array( 'stored' => '/etc/passwd' ) ),
					// Una URL en `stored`.
					'qdoc0004'     => array_merge( $bueno, array( 'stored' => 'https://example.org/x.pdf' ) ),
					// Un identificador que no tiene nuestra forma.
					'qdoc0005'     => array_merge( $bueno, array( 'id' => 'corto' ) ),
					// Y un hash que tampoco.
					'qdoc0006'     => array_merge( $bueno, array( 'sha256' => 'nope' ) ),
				)
			),
			true
		);

		$this->assertSame( array( 'qdoc0001' ), array_keys( $limpio ) );
		$this->assertSame( $bueno, $limpio['qdoc0001'] );
		$this->assertSame( '', RegistrationMetaRegistration::sanitize_files( 'ni esto es una lista' ) );
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
