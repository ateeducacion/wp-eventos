<?php
/**
 * Tests for the Shell: sections per role, URLs, chrome, notices and the exit seam.
 *
 * @package Evt
 */

use Evt\Access\EventAccess;
use Evt\PublicFront\ExitSignal;
use Evt\PublicFront\Shell;
use WPFront\URE\WPFront_User_Role_Editor_Utils as SwitchingUtils;

/**
 * El armazón que comparten todas las pantallas.
 *
 * Es la única lista de secciones del aplicativo, la única forma de resolver una
 * dirección y el único `exit`: si algo de esto se tuerce, se tuercen las cinco
 * pantallas a la vez.
 */
class Test_Shell extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Con las páginas del aplicativo ya creadas.
	 */
	public function set_up() {
		parent::set_up();
		$this->pages();
	}

	/**
	 * Cada perfil ve sus secciones, y quien no organiza eventos no ve ninguna.
	 */
	public function test_sections_per_role() {
		$this->acting_as( 0 );
		$this->assertSame( array(), Shell::sections(), 'sin sesión no hay secciones' );

		$this->acting_as( (int) self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->assertSame( array(), array_keys( Shell::sections() ) );

		// Falla en cerrado: con el rol pero sin área en el perfil, nada.
		$huerfano = $this->organiser();
		$this->acting_as( $huerfano );
		$this->assertFalse( Shell::can_use( $huerfano ) );
		$this->assertSame( array(), array_keys( Shell::sections() ) );

		$area = $this->area( 'Formación del Profesorado' );
		$this->acting_as( $this->organiser( array( $area ) ) );
		$this->assertSame( array( 'events' ), array_keys( Shell::sections() ) );

		$this->app();
		$this->acting_as( (int) self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$secciones = Shell::sections();
		// **Una sola sección, también para administración.** «Ajustes» no es
		// hermana de «Eventos»: es administración del aplicativo, vive en el
		// escritorio y ahora está en el menú de la cuenta. Como pestaña dejaba
		// una barra de dos con una saltando fuera del aplicativo.
		$this->assertSame( array( 'events' ), array_keys( $secciones ) );
		$this->assertNull( $secciones['events']['badge'], 'el recuento vive en las fichas del listado' );
	}

	/**
	 * La sección cuya página todavía no existe no se ofrece: llevaría a un 404.
	 */
	public function test_a_section_without_its_page_is_not_offered() {
		$area = $this->area( 'Innovación' );
		$this->acting_as( $this->organiser( array( $area ) ) );

		add_filter(
			'evt_page_slug',
			static function ( string $slug, string $seccion ): string {
				return 'events' === $seccion ? 'una-pagina-que-no-existe' : $slug;
			},
			10,
			2
		);

		$this->assertSame( '', Shell::url( 'events' ) );
		$this->assertSame( array(), array_keys( Shell::sections() ) );
	}

	/**
	 * `url()` resuelve por slug, admite parámetros y se puede reapuntar.
	 */
	public function test_url_resolves_the_page_of_a_section() {
		$pagina = get_page_by_path( Shell::SLUGS['events'] );
		$this->assertInstanceOf( WP_Post::class, $pagina );
		$this->assertSame( get_permalink( $pagina ), Shell::url( 'events' ) );

		$this->assertSame( '', Shell::url( 'no-existe' ), 'una sección inventada no tiene dirección' );

		$con_args = Shell::url( 'event', array( 'evento' => 7 ) );
		$this->assertStringContainsString( 'evento=7', $con_args );
		$this->assertStringContainsString( Shell::SLUGS['event'], $con_args );

		// Quien despliega puede colgar la página de otra dirección sin tocar código.
		$otra = (int) self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_name'   => 'gestion-de-eventos',
				'post_title'  => 'Gestión de eventos',
			)
		);
		add_filter(
			'evt_page_slug',
			static function ( string $slug, string $seccion ): string {
				return 'events' === $seccion ? 'gestion-de-eventos' : $slug;
			},
			10,
			2
		);
		$this->assertSame( get_permalink( $otra ), Shell::url( 'events' ) );
	}

	/**
	 * `render()` pinta la cabecera institucional, el título, el cuerpo y el pie.
	 */
	public function test_render_paints_the_chrome_around_the_screen() {
		$area = $this->area( 'Formación del Profesorado' );
		$this->acting_as( $this->organiser( array( $area ) ) );

		$html = Shell::render( 'Eventos', 'Solo los de su área.', '<p id="cuerpo">Contenido</p>' );

		// La cabecera dice lo que diga la configuración del armazón; el
		// mu-plugin de desarrollo pone una de ejemplo.
		$this->assertStringContainsString( 'Área de ejemplo', $html );
		$this->assertStringContainsString( 'Organización de eventos', $html );
		$this->assertStringContainsString( 'Formación del Profesorado', $html, 'la cabecera dice con qué área se mira' );
		$this->assertStringContainsString( '<h1 class="evt-h1">Eventos</h1>', $html );
		$this->assertStringContainsString( 'Solo los de su área.', $html );
		$this->assertStringContainsString( '<p id="cuerpo">Contenido</p>', $html );
		// El pie también sale de la configuración del armazón, no del código.
		$this->assertStringContainsString( 'Entorno de desarrollo', $html );
		$this->assertStringContainsString( 'example.org/aviso-legal', $html );

		// Con una sola sección no hay nada que elegir: no se pintan pestañas.
		$this->assertStringNotContainsString( 'evt-tabs', $html );

		// Tampoco a administración: lo suyo es un enlace del menú de la cuenta,
		// no una pestaña al lado de «Eventos».
		$this->app();
		$this->acting_as( (int) self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$admin = Shell::render( 'Eventos', '', '' );
		$this->assertStringNotContainsString( 'evt-tabs', $admin );
		$this->assertStringContainsString( 'Ajustes del aplicativo', $admin, 'pero sigue llegando' );
	}

	/**
	 * El título y el subtítulo se escapan, y sin ellos no se pinta su etiqueta.
	 */
	public function test_render_escapes_what_it_is_given() {
		$this->acting_as( $this->administrator() );

		$html = Shell::render( '<b>Título</b>', '', '<p>cuerpo</p>' );

		$this->assertStringContainsString( '&lt;b&gt;Título&lt;/b&gt;', $html );
		$this->assertStringNotContainsString( '<b>Título</b>', $html );
		$this->assertStringNotContainsString( 'evt-sub', $html, 'sin subtítulo no hay párrafo vacío' );
	}

	/**
	 * Con el filtro en «no», el armazón se aparta y solo queda la pantalla.
	 */
	public function test_the_chrome_can_be_switched_off() {
		$this->acting_as( $this->administrator() );
		add_filter( 'evt_show_chrome', '__return_false' );

		$html = Shell::render( 'Eventos', 'Algo', '<p>cuerpo</p>' );

		$this->assertSame( '<div class="evt-hoja"><p>cuerpo</p></div>', $html );
	}

	/**
	 * Los avisos: su tono, su texto escapado, y ninguno cuando no hay nada que decir.
	 */
	public function test_the_notices_carry_their_tone() {
		$this->assertStringContainsString( 'alert-success', Shell::notice( 'ok', 'Guardado.' ) );
		$this->assertStringContainsString( 'Guardado.', Shell::notice( 'ok', 'Guardado.' ) );
		$this->assertStringContainsString( 'alert-warning', Shell::notice( 'aviso', 'Cuidado.' ) );
		$this->assertStringContainsString( 'alert-danger', Shell::notice( 'error', 'No se pudo.' ) );
		$this->assertStringContainsString( 'alert-info', Shell::notice( 'lo-que-sea', 'Nota.' ) );

		$this->assertSame( '', Shell::notice( 'ok', '' ), 'sin texto no hay aviso' );
		$this->assertStringContainsString( '&lt;script&gt;', Shell::notice( 'ok', '<script>alert(1)</script>' ) );
	}

	/**
	 * `leave()` es la costura de salida: bajo el filtro lanza ExitSignal con la URL.
	 */
	public function test_leave_throws_the_exit_signal() {
		$destino = home_url( '/mis-eventos/?evt_notice=creado' );

		$this->assertSame(
			$destino,
			$this->exit_url(
				static function () use ( $destino ): void {
					Shell::leave( $destino );
				}
			)
		);

		// Sin URL: se acaba de servir un documento y no hay a dónde ir.
		$this->assertSame(
			'',
			$this->exit_url(
				static function (): void {
					Shell::leave();
				}
			)
		);

		// Y la excepción lleva la dirección para quien la mire de cerca.
		add_filter( 'evt_exit_throws', '__return_true' );
		try {
			Shell::leave( $destino );
			$this->fail( 'leave() tenía que haber lanzado ExitSignal' );
		} catch ( ExitSignal $e ) {
			$this->assertSame( $destino, $e->url );
		}
	}

	/**
	 * Sin sesión, cualquier pantalla del aplicativo manda al formulario de acceso.
	 */
	public function test_without_a_session_the_app_pages_send_you_to_the_login() {
		$pagina = get_page_by_path( Shell::SLUGS['events'] );
		$this->go_to( (string) get_permalink( $pagina ) );
		$this->acting_as( 0 );

		$this->assertTrue( Shell::is_app_page() );
		$this->assertSame( 'events', Shell::current_section() );
		$this->assertSame(
			wp_login_url( (string) get_permalink( $pagina ) ),
			$this->exit_url( array( Shell::class, 'require_login' ) )
		);

		// Con sesión no se va a ninguna parte.
		$this->acting_as( $this->administrator() );
		$this->assertNull( $this->exit_url( array( Shell::class, 'require_login' ) ) );
	}

	/**
	 * Lo que trae el tema no se encola en nuestras pantallas; lo demás, sí.
	 */
	public function test_theme_assets_are_dropped_on_app_pages() {
		$this->acting_as( $this->administrator() );
		$this->go_to( (string) get_permalink( get_page_by_path( Shell::SLUGS['events'] ) ) );

		wp_enqueue_style( 'un-tema', 'https://example.org/wp-content/themes/un-tema/style.css', array(), '1' );
		wp_enqueue_style( 'una-cache', 'https://example.org/wp-content/et-cache/1/et-tema.css', array(), '1' );
		wp_enqueue_style( 'un-plugin', 'https://example.org/wp-content/plugins/algo/estilo.css', array(), '1' );
		wp_enqueue_style( 'wp-block-library', 'https://example.org/wp-includes/css/dist/block-library/style.css', array(), '1' );
		add_action( 'wp_head', 'print_emoji_detection_script', 7 );

		Shell::drop_theme_assets();

		$this->assertFalse( wp_style_is( 'un-tema', 'enqueued' ) );
		$this->assertFalse( wp_style_is( 'una-cache', 'enqueued' ), 'la caché del tema también es del tema' );
		$this->assertFalse( wp_style_is( 'wp-block-library', 'enqueued' ) );
		$this->assertFalse( has_action( 'wp_head', 'print_emoji_detection_script' ) );
		$this->assertTrue( wp_style_is( 'un-plugin', 'enqueued' ), 'lo que no es del tema se queda' );

		// Y la red de al escribir la etiqueta, para lo que se encole más tarde.
		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- es la etiqueta que recibe el filtro, no una hoja que se cargue aquí.
		$etiqueta = '<link rel="stylesheet" href="x">';
		$this->assertSame( '', Shell::drop_theme_tag( $etiqueta, 'x', 'https://example.org/wp-content/et-cache/1/late.css' ) );
		$this->assertSame( $etiqueta, Shell::drop_theme_tag( $etiqueta, 'x', 'https://example.org/wp-content/plugins/algo/estilo.css' ) );
	}

	/**
	 * Fuera del aplicativo no se toca la cola de nadie.
	 */
	public function test_outside_the_app_nothing_is_dropped() {
		$this->acting_as( $this->administrator() );
		$otra = (int) self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => 'Una página del sitio.',
			)
		);
		$this->go_to( (string) get_permalink( $otra ) );

		$this->assertFalse( Shell::is_app_page() );
		$this->assertSame( '', Shell::current_section() );

		wp_enqueue_style( 'otro-tema', 'https://example.org/wp-content/themes/un-tema/otro.css', array(), '1' );
		Shell::drop_theme_assets();
		$this->assertTrue( wp_style_is( 'otro-tema', 'enqueued' ) );

		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- la etiqueta que recibe el filtro.
		$etiqueta = '<link rel="stylesheet" href="x">';
		$this->assertSame(
			$etiqueta,
			Shell::drop_theme_tag( $etiqueta, 'x', 'https://example.org/wp-content/themes/un-tema/otro.css' )
		);
		$this->assertNotContains( 'evt-app', Shell::body_class( array() ) );
	}

	/**
	 * El perfil de la cabecera: el cargo y el área con la que se está mirando.
	 */
	public function test_the_profile_says_the_role_and_the_area() {
		$this->acting_as( 0 );
		$this->assertSame(
			array(
				'cargo' => '',
				'area'  => '',
			),
			Shell::profile()
		);

		$area = $this->area( 'Innovación' );
		$this->acting_as( $this->organiser( array( $area ) ) );
		$this->assertSame( 'Organización de eventos', Shell::profile()['cargo'] );
		$this->assertSame( 'Innovación', Shell::profile()['area'] );

		$this->acting_as( $this->organiser() );
		$this->assertSame( 'Sin área asignada', Shell::profile()['area'] );

		$this->app();
		$this->acting_as( (int) self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->assertSame( 'Administración', Shell::profile()['cargo'] );
		$this->assertSame( 'Todas las áreas', Shell::profile()['area'] );
	}

	/**
	 * Las iniciales del avatar salen del nombre, no de los signos que lo rodean.
	 *
	 * Los nombres que se muestran traen el área entre paréntesis
	 * —«Organización (Innovación)»—, y partiendo por espacios la segunda inicial era
	 * el propio paréntesis: en la cabecera se leía «O(».
	 */
	public function test_the_avatar_initials_skip_the_punctuation() {
		$this->app();
		$area  = $this->area( 'Innovación' );
		$quien = $this->organiser( array( $area ) );
		wp_update_user(
			array(
				'ID'           => $quien,
				'display_name' => 'Organización (Innovación)',
			)
		);
		$this->acting_as( $quien );

		$html = Shell::render( 'Pantalla', '', '<p>cuerpo</p>' );
		$this->assertMatchesRegularExpression( '/evt-yo-ava[^>]*>\s*OI\s*</u', $html );
		$this->assertStringNotContainsString( '>O(<', $html );
	}

	/**
	 * `back_url()` nunca devuelve a la portada del sitio: vuelve al aplicativo.
	 */
	public function test_back_url_falls_back_to_the_application() {
		$this->acting_as( $this->administrator() );

		$this->assertSame( Shell::url( 'events' ), Shell::back_url( 'events' ) );

		$_REQUEST['_wp_http_referer'] = '/mis-eventos/?evt_page=2';
		$this->assertStringContainsString( 'evt_page=2', (string) Shell::back_url( 'events' ) );
	}

	/**
	 * El armazón engancha lo que sirve la pantalla sola y lo que quita el tema.
	 */
	public function test_register_hooks_the_standalone_render() {
		Shell::register();

		$this->assertNotFalse( has_action( 'template_redirect', array( Shell::class, 'render_standalone' ) ) );
		$this->assertNotFalse( has_action( 'template_redirect', array( Shell::class, 'require_login' ) ) );
		$this->assertNotFalse( has_action( 'wp_enqueue_scripts', array( Shell::class, 'drop_theme_assets' ) ) );
		$this->assertNotFalse( has_filter( 'style_loader_tag', array( Shell::class, 'drop_theme_tag' ) ) );
	}

	/**
	 * La pantalla se sirve sola: el documento entero, y sin la plantilla del tema.
	 */
	public function test_the_application_serves_its_own_document() {
		$this->acting_as( $this->administrator() );
		$pagina = get_page_by_path( Shell::SLUGS['home'] );
		$this->go_to( (string) get_permalink( $pagina ) );

		$documento = $this->served( array( Shell::class, 'render_standalone' ) );

		$this->assertStringContainsString( '<!doctype html>', $documento );
		$this->assertStringContainsString( 'evt-app', $documento, 'la clase que marca nuestras páginas' );
		$this->assertStringContainsString( 'evt-hoja', $documento, 'y la pantalla dentro' );

		// Fuera del aplicativo, la plantilla del tema sigue mandando.
		$otra = (int) self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);
		$this->go_to( (string) get_permalink( $otra ) );
		$this->assertNull( $this->exit_url( array( Shell::class, 'render_standalone' ) ) );

		// Y con el filtro en «no», tampoco se sirve solo.
		$this->go_to( (string) get_permalink( $pagina ) );
		add_filter( 'evt_standalone_page', '__return_false' );
		$this->assertNull( $this->exit_url( array( Shell::class, 'render_standalone' ) ) );
	}

	/**
	 * La barra de administración solo la ve quien administra.
	 */
	public function test_the_toolbar_is_only_for_administrators() {
		$this->acting_as( $this->organiser( array( $this->area( 'Innovación' ) ) ) );
		$this->assertFalse( Shell::show_admin_bar() );

		$this->app();
		$this->acting_as( (int) self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->assertTrue( Shell::show_admin_bar() );
		$this->assertTrue( EventAccess::is_manager() );
	}

	/**
	 * Suplantando, la barra es de quien empezó el cambio; los permisos, no.
	 *
	 * Es el caso por el que existe la lectura de la cookie de WPFront: sin
	 * barra, quien administra se queda dentro de la sesión ajena sin el enlace
	 * para volver a la suya.
	 */
	public function test_a_switched_administrator_keeps_the_toolbar_but_not_the_rights() {
		if ( ! class_exists( SwitchingUtils::class ) ) {
			$utils = glob( WP_PLUGIN_DIR . '/wpfront-user-role-editor*/includes/class-utils.php' );
			if ( empty( $utils ) ) {
				$this->markTestSkipped( 'Hace falta WPFront User Role Editor.' );
			}
			require_once $utils[0];
		}

		$admin    = (int) self::factory()->user->create( array( 'role' => 'administrator' ) );
		$organiza = $this->organiser( array( $this->area( 'Innovación' ) ) );
		$ajeno    = $this->event( $admin, array( $this->area( 'Formación del Profesorado' ) ) );

		$this->acting_as( $organiza );
		$this->assertFalse( Shell::show_admin_bar(), 'sin suplantación, la organización no ve la barra' );

		$cookie = 'wpfront_ure_user_switching_stack_' . COOKIEHASH;
		$casos  = array(
			// Quien empezó el cambio administra: la barra se queda.
			array( COOKIEHASH . '-' . time() . '-' . $admin . '-', true ),
			array( COOKIEHASH . '-' . time() . '-' . $admin . ',' . $organiza . '-remember', true ),
			// Y si no administra, o la pila no vale, no hay barra.
			array( COOKIEHASH . '-' . time() . '-' . $organiza . '-', false ),
			array( COOKIEHASH . '-' . ( time() - 13 * HOUR_IN_SECONDS ) . '-' . $admin . '-', false ),
			array( 'otro-sitio-' . time() . '-' . $admin . '-', false ),
			array( 'ni-esto-tiene-forma-de-pila', false ),
		);
		try {
			foreach ( $casos as list( $pila, $esperado ) ) {
				$_COOKIE[ $cookie ] = SwitchingUtils::encrypt( $pila );
				$this->assertSame( $esperado, apply_filters( 'show_admin_bar', false ), $pila );
				// La barra no trae permisos: se sigue siendo a quien se suplanta.
				$this->assertFalse( current_user_can( 'manage_options' ), $pila );
				$this->assertFalse( EventAccess::can_edit( $organiza, $ajeno ), $pila );
			}
			$_COOKIE[ $cookie ] = array();
			$this->assertFalse( Shell::show_admin_bar(), 'una cookie que no es una cadena, fuera' );
		} finally {
			unset( $_COOKIE[ $cookie ] );
		}
	}
}
