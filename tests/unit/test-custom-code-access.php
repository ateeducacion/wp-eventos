<?php
/**
 * Tests for who may see and write the custom CSS and JavaScript.
 *
 * @package Evt
 */

use Evt\Access\EventAccess;
use Evt\Meta\EventMetaKeys;
use Evt\Meta\EventMetaRegistration;
use Evt\PostType\EventPostType;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\PageForm;
use Evt\PublicFront\Shell;
use Evt\PublicFront\View\EventCodePanel;
use Evt\PublicFront\View\PageFormView;

/**
 * Los permisos del código a medida, que es lo delicado de todo esto.
 *
 * La raya está entre las dos cosas, y no delante de las dos: el **CSS** cambia
 * cómo se ve una página y lo escribe también el área, acotada a la suya; el
 * **JavaScript** se ejecuta en el navegador de cada visitante —el poder que
 * WordPress protege con `unfiltered_html` y el que el sistema anterior
 * devolvía sin acotar a quien ya la tuviera en su rol— y se queda en administración.
 *
 * Aquí no se prueba el mensaje —eso lo dice cualquiera— sino **el estado
 * después del intento**: la meta se queda exactamente como estaba.
 *
 * Cuatro puertas, y todas tienen que decir lo mismo del mismo perfil: la
 * pestaña, la URL directa, el POST y el `auth_callback` de la meta.
 */
class Test_Custom_Code_Access extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Set up.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		EventMetaRegistration::register_meta();
		$this->pages();
	}

	/**
	 * Un área, un evento suyo y quien administra, que sí puede todo.
	 *
	 * @return array{area:int, admin:int, event:int}
	 */
	private function escenario(): array {
		$area  = $this->area( 'Innovación' );
		$admin = (int) self::factory()->user->create( array( 'role' => 'administrator' ) );
		update_user_meta( $admin, EventAccess::USER_AREA_META, array( $area ) );

		return array(
			'area'  => $area,
			'admin' => $admin,
			'event' => $this->event( $admin, array( $area ) ),
		);
	}

	/**
	 * Lo que ve esa persona al abrir el taller pidiendo la pestaña «Código».
	 *
	 * @param int $uid      Who.
	 * @param int $event_id Event.
	 * @return array<string, mixed>
	 */
	private function taller( int $uid, int $event_id ): array {
		$this->acting_as( $uid );
		$_GET[ EventWorkspace::ARG_EVENT ] = (string) $event_id;
		$_GET[ EventWorkspace::ARG_PANEL ] = EventWorkspace::PANEL_CODE;
		return EventWorkspace::model();
	}

	/**
	 * Mandar el guardado del panel de código con el nonce en regla.
	 *
	 * Con nonce y todo: lo que se prueba no es que falte el nonce, sino que
	 * aunque el envío esté impecable la capacidad manda.
	 *
	 * @param int                   $uid      Who.
	 * @param int                   $event_id Event.
	 * @param array<string, string> $extra    Fields.
	 * @return void
	 */
	private function submit( int $uid, int $event_id, array $extra ): void {
		$this->acting_as( $uid );
		$this->post(
			array_merge(
				array(
					EventWorkspace::FIELD_DO    => EventWorkspace::PANEL_CODE,
					EventWorkspace::FIELD_EVENT => (string) $event_id,
				),
				$extra
			),
			EventWorkspace::nonce_action( EventWorkspace::PANEL_CODE ),
			EventWorkspace::nonce_name( EventWorkspace::PANEL_CODE, 0 )
		);
		$this->exit_url( array( EventWorkspace::class, 'handle' ) );
	}

	// ─── el área y su CSS ──────────────────────────────────────────────────

	/**
	 * La organización escribe el CSS de su evento, y el JavaScript nunca.
	 *
	 * Las dos mitades del reparto en un solo sitio, porque son inseparables: si
	 * el CSS se abriera junto con el JavaScript, la raya no estaría en ninguna
	 * parte.
	 */
	public function test_the_organiser_writes_the_css_of_their_event_and_never_the_js() {
		$e   = $this->escenario();
		$uid = $this->organiser( array( $e['area'] ) );

		// Lo que había antes de JavaScript, puesto por quien sí puede.
		update_post_meta( $e['event'], EventMetaKeys::CUSTOM_JS, "console.log('antes');" );

		$this->assertTrue( user_can( $uid, EventAccess::CAP_CUSTOM_CSS ), 'el CSS es también del área' );
		$this->assertFalse( user_can( $uid, EventAccess::CAP_CUSTOM_JS ), 'el JavaScript no' );
		$this->assertTrue( EventAccess::can_edit_custom_css( $uid, $e['event'] ) );
		$this->assertFalse( EventAccess::can_edit_custom_js( $uid, $e['event'] ) );

		// La pestaña «Código» sale, y la URL directa la abre.
		$m = $this->taller( $uid, $e['event'] );
		$this->assertArrayHasKey( EventWorkspace::PANEL_CODE, $m['panels'], 'el área ve la pestaña' );
		$this->assertSame( EventWorkspace::PANEL_CODE, $m['panel'] );
		$this->assertTrue( $m['can_edit_css'] );
		$this->assertFalse( $m['can_edit_js'] );
		$this->assertSame( '', $m['code']['js'], 'lo guardado del JavaScript no se le devuelve' );

		// Dentro: el CSS como campo corriente —fuera del recuadro amarillo— y
		// una línea donde estaría el JavaScript, que no es un hueco callado.
		$html  = EventCodePanel::html( $m );
		$plano = wp_strip_all_tags( $html );
		$this->assertStringContainsString( 'name="' . EventMetaKeys::CUSTOM_CSS . '"', $html );
		$this->assertStringNotContainsString( 'name="' . EventMetaKeys::CUSTOM_JS . '"', $html );
		$this->assertStringNotContainsString( 'evt-solo-admin', $html, 'el CSS ya no vive en el recuadro amarillo' );
		$this->assertStringContainsString( 'no puede editar el JavaScript', $plano, 'y se le dice por qué no está' );

		// Y el guardado: el CSS entra; el JavaScript enviado a mano se ignora.
		$this->submit(
			$uid,
			$e['event'],
			array(
				EventMetaKeys::CUSTOM_CSS => '.evt-titulo{color:#c00}',
				EventMetaKeys::CUSTOM_JS  => 'fetch("//ejemplo.test/roba")',
			)
		);
		$this->assertSame( '.evt-titulo{color:#c00}', (string) get_post_meta( $e['event'], EventMetaKeys::CUSTOM_CSS, true ), 'el CSS suyo sí' );
		$this->assertSame( "console.log('antes');", (string) get_post_meta( $e['event'], EventMetaKeys::CUSTOM_JS, true ), 'el JavaScript, intacto' );
	}

	/**
	 * Y no el de otra área: la capacidad no se salta el acotado.
	 *
	 * Es la mitad que hace que abrir el CSS no sea abrirlo todo. Tener
	 * `evt_edit_custom_css` no es tener el evento: hacen falta las dos cosas
	 * ({@see EventAccess::can_edit_custom_css()}).
	 */
	public function test_the_organiser_does_not_write_the_css_of_another_area() {
		$e     = $this->escenario();
		$ajeno = $this->event( $e['admin'], array( $this->area( 'Formación' ) ) );
		$uid   = $this->organiser( array( $e['area'] ) );

		update_post_meta( $ajeno, EventMetaKeys::CUSTOM_CSS, '.suyo{color:red}' );

		$this->assertTrue( user_can( $uid, EventAccess::CAP_CUSTOM_CSS ), 'la capacidad la tiene' );
		$this->assertFalse( EventAccess::can_edit( $uid, $ajeno ), 'pero el evento no es suyo' );
		$this->assertFalse( EventAccess::can_edit_custom_css( $uid, $ajeno ) );

		$m = $this->taller( $uid, $ajeno );
		$this->assertArrayNotHasKey( EventWorkspace::PANEL_CODE, $m['panels'], 'ni pestaña' );

		$this->submit( $uid, $ajeno, array( EventMetaKeys::CUSTOM_CSS => '.colado{color:blue}' ) );
		$this->assertSame( '.suyo{color:red}', (string) get_post_meta( $ajeno, EventMetaKeys::CUSTOM_CSS, true ), 'el CSS de al lado, intacto' );
	}

	/**
	 * Quien administra sí: ve la pestaña, ve los dos campos y guarda.
	 */
	public function test_the_administrator_may() {
		$e = $this->escenario();

		$this->assertTrue( EventAccess::can_edit_custom_css( $e['admin'], $e['event'] ) );
		$this->assertTrue( EventAccess::can_edit_custom_js( $e['admin'], $e['event'] ) );

		$m = $this->taller( $e['admin'], $e['event'] );
		$this->assertArrayHasKey( EventWorkspace::PANEL_CODE, $m['panels'] );
		$this->assertSame( EventWorkspace::PANEL_CODE, $m['panel'] );

		$html = EventCodePanel::html( $m );
		$this->assertStringContainsString( 'name="' . EventMetaKeys::CUSTOM_CSS . '"', $html );
		$this->assertStringContainsString( 'name="' . EventMetaKeys::CUSTOM_JS . '"', $html );

		$this->submit(
			$e['admin'],
			$e['event'],
			array(
				EventMetaKeys::CUSTOM_CSS => '.evt-titulo { color: #c00; }',
				EventMetaKeys::CUSTOM_JS  => "console.log('EVT-marca');",
			)
		);
		$this->assertSame( '.evt-titulo { color: #c00; }', (string) get_post_meta( $e['event'], EventMetaKeys::CUSTOM_CSS, true ) );
		$this->assertSame( "console.log('EVT-marca');", (string) get_post_meta( $e['event'], EventMetaKeys::CUSTOM_JS, true ) );
	}

	// ─── una capacidad sí y la otra no ─────────────────────────────────────

	/**
	 * Con el JavaScript pero sin el CSS: el hueco callado tampoco vale aquí.
	 *
	 * Es el caso al revés que el del área, y llega en una red donde WordPress
	 * reserva `unfiltered_html` a la superadministración o cuando alguien
	 * concede una capacidad y no la otra a mano. Se prueba el segundo, que no
	 * depende de que la instalación sea multisitio.
	 */
	public function test_whoever_may_write_the_js_but_not_the_css_saves_only_the_js() {
		$e = $this->escenario();
		update_post_meta( $e['event'], EventMetaKeys::CUSTOM_CSS, '.antes{color:red}' );

		$solo_js = $this->organiser( array( $e['area'] ) );
		$usuario = get_user_by( 'id', $solo_js );
		$usuario->add_cap( EventAccess::CAP_CUSTOM_JS );
		// Y se le niega la del CSS, que la trae el rol: `add_cap( …, false )`
		// escribe la negación en el usuario, que gana sobre la del rol.
		$usuario->add_cap( EventAccess::CAP_CUSTOM_CSS, false );

		$this->assertFalse( EventAccess::can_edit_custom_css( $solo_js, $e['event'] ) );
		$this->assertTrue( EventAccess::can_edit_custom_js( $solo_js, $e['event'] ) );

		$m = $this->taller( $solo_js, $e['event'] );
		$this->assertArrayHasKey( EventWorkspace::PANEL_CODE, $m['panels'], 'la pestaña sale igual' );
		$this->assertSame( '', $m['code']['css'], 'lo guardado del CSS no se le enseña' );

		$html = EventCodePanel::html( $m );
		$this->assertStringNotContainsString( 'name="' . EventMetaKeys::CUSTOM_CSS . '"', $html );
		$this->assertStringContainsString( 'name="' . EventMetaKeys::CUSTOM_JS . '"', $html );
		$this->assertStringContainsString( 'no puede editar el CSS', wp_strip_all_tags( $html ) );

		$this->submit(
			$solo_js,
			$e['event'],
			array(
				EventMetaKeys::CUSTOM_CSS => '.colado{}',
				EventMetaKeys::CUSTOM_JS  => "console.log('suyo');",
			)
		);
		$this->assertSame( '.antes{color:red}', (string) get_post_meta( $e['event'], EventMetaKeys::CUSTOM_CSS, true ), 'el CSS, intacto' );
		$this->assertSame( "console.log('suyo');", (string) get_post_meta( $e['event'], EventMetaKeys::CUSTOM_JS, true ), 'el JavaScript sí' );
	}

	// ─── el auth_callback de las metas ─────────────────────────────────────

	/**
	 * Las dos metas se guardan igual venga por donde venga el guardado.
	 *
	 * La pantalla es una puerta; esta es la cerradura. `edit_post_meta` es lo
	 * que consultan la REST, la edición rápida y cualquier otro complemento, y
	 * el `auth_callback` que registró {@see EventMetaRegistration} es quien
	 * responde. Tiene que decir exactamente lo mismo que las pantallas: el área
	 * escribe el CSS de su evento y nunca el JavaScript.
	 */
	public function test_the_auth_callback_guards_the_two_metas() {
		$e         = $this->escenario();
		$seccion   = $this->event_page( $e['event'], 'programa' );
		$organiser = $this->organiser( array( $e['area'] ) );
		$suelta    = (int) self::factory()->user->create( array( 'role' => 'subscriber' ) );

		foreach ( array( EventMetaKeys::CUSTOM_CSS, EventMetaKeys::CUSTOM_JS ) as $clave ) {
			$this->assertTrue( registered_meta_key_exists( 'post', $clave, EventPostType::POST_TYPE ), $clave );

			// Quien administra, las dos, en el evento y en su sección.
			$this->assertTrue( user_can( $e['admin'], 'edit_post_meta', $e['event'], $clave ), $clave );
			$this->assertTrue( user_can( $e['admin'], 'edit_post_meta', $seccion, $clave ), $clave . ' en la sección' );

			// Y quien no organiza eventos, ninguna.
			$this->assertFalse( user_can( $suelta, 'edit_post_meta', $e['event'], $clave ), 'suscripción / ' . $clave );
			$this->assertFalse( user_can( $suelta, 'edit_post_meta', $seccion, $clave ), 'suscripción / ' . $clave . ' en la sección' );
		}

		// El área: el CSS sí, el JavaScript no, y en los dos sitios igual.
		$this->assertTrue( user_can( $organiser, 'edit_post_meta', $e['event'], EventMetaKeys::CUSTOM_CSS ), 'el CSS del evento' );
		$this->assertTrue( user_can( $organiser, 'edit_post_meta', $seccion, EventMetaKeys::CUSTOM_CSS ), 'el CSS de la sección' );
		$this->assertFalse( user_can( $organiser, 'edit_post_meta', $e['event'], EventMetaKeys::CUSTOM_JS ), 'el JavaScript del evento' );
		$this->assertFalse( user_can( $organiser, 'edit_post_meta', $seccion, EventMetaKeys::CUSTOM_JS ), 'el JavaScript de la sección' );

		// Y la capacidad tampoco abre el evento de otra área: las dos cosas.
		$ajeno = $this->event( $e['admin'], array( $this->area( 'Formación' ) ) );
		$this->assertFalse( user_can( $organiser, 'edit_post_meta', $ajeno, EventMetaKeys::CUSTOM_CSS ), 'la de al lado no' );
	}

	/**
	 * El reparto de las dos capacidades, escrito donde se comprueba.
	 *
	 * `evt_forbidden_role_caps()` es la lista que mira `evt_roles_status()`
	 * para avisar si alguien concede a mano en WPFront lo que no toca. El
	 * JavaScript sigue ahí; el CSS ya no, porque ahora es del área.
	 */
	public function test_only_the_javascript_capability_stays_with_administration() {
		$admin = get_role( 'administrator' );
		foreach ( EventPostType::code_caps() as $cap ) {
			$this->assertTrue( $admin->has_cap( $cap ), 'administración, las dos: ' . $cap );
		}

		$this->assertSame( array( EventAccess::CAP_CUSTOM_JS ), EventPostType::admin_only_code_caps() );

		foreach ( evt_role_slugs() as $slug ) {
			$rol = get_role( $slug );
			$this->assertNotNull( $rol, $slug );
			$this->assertTrue( $rol->has_cap( EventAccess::CAP_CUSTOM_CSS ), $slug . ': el CSS sí' );
			$this->assertFalse( $rol->has_cap( EventAccess::CAP_CUSTOM_JS ), $slug . ': el JavaScript no' );
		}

		$this->assertContains( EventAccess::CAP_CUSTOM_JS, evt_forbidden_role_caps(), 'el JavaScript, vigilado' );
		$this->assertNotContains( EventAccess::CAP_CUSTOM_CSS, evt_forbidden_role_caps(), 'el CSS ya no es una alarma' );

		// Y el diagnóstico no se queja de lo que el aplicativo acaba de repartir.
		foreach ( evt_roles_status() as $slug => $estado ) {
			$this->assertSame( array(), $estado['forbidden'], $slug );
		}
	}

	// ─── la sección satélite ───────────────────────────────────────────────

	/**
	 * En el formulario de una sección, la misma raya que en el evento.
	 *
	 * El permiso se pregunta por el evento padre, que es donde vive el área:
	 * al crear una sección todavía no hay página por la que preguntar.
	 */
	public function test_the_section_form_draws_the_same_line() {
		$e       = $this->escenario();
		$seccion = $this->event_page( $e['event'], 'programa' );
		update_post_meta( $seccion, EventMetaKeys::CUSTOM_CSS, '.solo-esta{color:red}' );

		$_GET['seccion'] = (string) $seccion;

		// Quien administra ve los dos, y solo el JavaScript va en amarillo.
		$this->acting_as( $e['admin'] );
		$html = PageFormView::html( PageForm::model() );
		$this->assertSame( 1, substr_count( $html, 'class="evt-solo-admin"' ), 'un recuadro amarillo, el del JavaScript' );
		$this->assertStringContainsString( 'name="' . EventMetaKeys::CUSTOM_CSS . '"', $html );
		$this->assertStringContainsString( 'name="' . EventMetaKeys::CUSTOM_JS . '"', $html );
		$this->assertStringContainsString( esc_textarea( '.solo-esta{color:red}' ), $html );
		$this->assertStringContainsString( 'Solo administración', wp_strip_all_tags( $html ) );

		// La organización ve el CSS, con lo guardado dentro, y nada amarillo.
		$this->acting_as( $this->organiser( array( $e['area'] ) ) );
		$html = PageFormView::html( PageForm::model() );
		$this->assertStringContainsString( 'Título', $html, 'el formulario sí se pinta' );
		$this->assertStringContainsString( 'name="' . EventMetaKeys::CUSTOM_CSS . '"', $html );
		$this->assertStringContainsString( esc_textarea( '.solo-esta{color:red}' ), $html, 'y lo que ya había' );
		$this->assertStringNotContainsString( 'evt-solo-admin', $html );
		$this->assertStringNotContainsString( 'name="' . EventMetaKeys::CUSTOM_JS . '"', $html );
		$this->assertStringContainsString( 'El JavaScript a medida de esta sección no aparece', wp_strip_all_tags( $html ) );
	}

	// ─── la convención del recuadro amarillo ───────────────────────────────

	/**
	 * El recuadro amarillo lleva «Solo administración» en texto, no solo en color.
	 *
	 * Quien no distingue el amarillo tiene que poder leerlo, así que se
	 * comprueba sobre el texto pelado: sin etiquetas, sin clases y sin el SVG
	 * del candado.
	 */
	public function test_the_yellow_box_says_it_in_words() {
		$caja  = Shell::admin_box( 'JavaScript a medida', '<p>cuerpo</p>', 'porque sí' );
		$texto = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $caja ) ) );

		$this->assertStringContainsString( 'Solo administración', $texto, 'la etiqueta, en texto' );
		$this->assertStringContainsString( 'JavaScript a medida', $texto );
		$this->assertStringContainsString( 'porque sí', $texto, 'y la línea que explica por qué' );
		$this->assertStringContainsString( 'class="evt-solo-admin"', $caja, 'con la clase que le da el amarillo' );

		// Y en el panel de código de verdad, uno solo: el del JavaScript. El
		// CSS es un campo corriente y ya no se anuncia como reservado.
		$e     = $this->escenario();
		$html  = EventCodePanel::html( $this->taller( $e['admin'], $e['event'] ) );
		$plano = wp_strip_all_tags( $html );

		$this->assertSame( 1, substr_count( $html, 'class="evt-solo-admin"' ), 'solo el del JavaScript' );
		$this->assertSame( 1, substr_count( $plano, 'Solo administración' ), 'y lo dice con letras' );
	}
}
