<?php
/**
 * Tests for the landing page: where each role is sent and what it is told.
 *
 * @package Evt
 */

use Evt\PublicFront\Home;
use Evt\PublicFront\Shell;

/**
 * La puerta de entrada del aplicativo.
 *
 * Es una sola dirección que publicar en el menú, y detrás cada persona entra
 * directamente en su pantalla. Lo que se prueba es a dónde manda a cada rol y
 * qué le cuenta a quien todavía no puede hacer nada.
 */
class Test_Home extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Con las páginas del aplicativo ya creadas.
	 */
	public function set_up() {
		parent::set_up();
		$this->pages();
	}

	/**
	 * Cada perfil aterriza en su primera pantalla; quien no puede nada, en ninguna.
	 */
	public function test_landing_per_role() {
		$this->acting_as( 0 );
		$this->assertSame( '', Home::landing() );

		$this->acting_as( (int) self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->assertSame( '', Home::landing() );

		// Con el rol pero sin área todavía no hay a dónde ir: falla en cerrado.
		$this->acting_as( $this->organiser() );
		$this->assertSame( '', Home::landing() );

		$this->acting_as( $this->organiser( array( $this->area( 'Formación del Profesorado' ) ) ) );
		$this->assertSame( Shell::url( 'events' ), Home::landing() );

		$this->acting_as( $this->administrator() );
		$this->assertSame( Shell::url( 'events' ), Home::landing() );

		// «Ajustes» es el escritorio de WordPress, no un sitio donde empezar el día.
		$this->app();
		$this->acting_as( (int) self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->assertSame( Shell::url( 'events' ), Home::landing() );
	}

	/**
	 * Abrir la portada lleva a la pantalla de quien entra.
	 */
	public function test_the_front_page_sends_you_to_your_first_screen() {
		$portada = get_page_by_path( Shell::SLUGS['home'] );
		$this->go_to( (string) get_permalink( $portada ) );

		// Sin sesión no se redirige desde aquí: de eso se ocupa `require_login`.
		$this->acting_as( 0 );
		$this->assertNull( $this->exit_url( array( Home::class, 'send_to_landing' ) ) );

		$this->acting_as( $this->organiser( array( $this->area( 'Innovación' ) ) ) );
		$this->assertSame( Shell::url( 'events' ), $this->exit_url( array( Home::class, 'send_to_landing' ) ) );

		// Quien no puede nada se queda en la portada, leyendo por qué.
		$this->acting_as( $this->organiser() );
		$this->assertNull( $this->exit_url( array( Home::class, 'send_to_landing' ) ) );
	}

	/**
	 * Si la portada es además la pantalla de destino, no hay a dónde ir.
	 */
	public function test_when_the_landing_is_the_front_page_itself_it_stays() {
		$portada = get_page_by_path( Shell::SLUGS['home'] );
		$this->go_to( (string) get_permalink( $portada ) );
		$this->acting_as( $this->administrator() );

		add_filter(
			'evt_page_slug',
			static function ( string $slug, string $seccion ): string {
				return 'events' === $seccion ? Shell::SLUGS['home'] : $slug;
			},
			10,
			2
		);

		$this->assertSame( (string) get_permalink( $portada ), Home::landing() );
		$this->assertNull( $this->exit_url( array( Home::class, 'send_to_landing' ) ) );
	}

	/**
	 * Fuera de la portada no se redirige a nadie.
	 */
	public function test_other_pages_are_left_alone() {
		$otra = (int) self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => 'Una página del sitio.',
			)
		);
		$this->go_to( (string) get_permalink( $otra ) );
		$this->acting_as( $this->administrator() );

		$this->assertNull( $this->exit_url( array( Home::class, 'send_to_landing' ) ) );
	}

	/**
	 * El modelo dice si hay sesión, quién es y por qué no hay nada que enseñar.
	 */
	public function test_the_model_explains_why_there_is_nothing() {
		$this->acting_as( 0 );
		$m = Home::model();
		$this->assertFalse( $m['logged_in'] );
		$this->assertSame( array(), $m['sections'] );
		$this->assertStringContainsString( 'Debe iniciar sesión', $m['reason'] );

		// Falta el permiso: se arregla dando el rol.
		$this->acting_as( (int) self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->assertStringContainsString( 'todavía no organiza eventos', Home::model()['reason'] );

		// Falta el área: se arregla en el perfil, y no es lo mismo.
		$this->acting_as( $this->organiser() );
		$this->assertStringContainsString( 'ningún ámbito asignado', Home::model()['reason'] );

		$this->acting_as( $this->organiser( array( $this->area( 'Innovación' ) ) ) );
		$m = Home::model();
		$this->assertTrue( $m['logged_in'] );
		$this->assertSame( '', $m['reason'] );
		$this->assertSame( array( 'events' ), array_keys( $m['sections'] ) );
	}

	/**
	 * La portada pintada: el saludo y los accesos, o el aviso y nada más.
	 */
	public function test_the_front_page_is_painted_from_its_model() {
		$this->acting_as( 0 );
		$this->assertStringContainsString( 'Debe iniciar sesión', Home::render() );

		$this->acting_as( $this->organiser() );
		$html = Home::render();
		$this->assertStringContainsString( 'ningún ámbito asignado', $html );
		$this->assertStringNotContainsString( 'evt-inicio-acceso', $html, 'sin nada que abrir, no hay tarjetas' );

		$uid = $this->organiser( array( $this->area( 'Innovación' ) ) );
		wp_update_user(
			array(
				'ID'           => $uid,
				'display_name' => 'Ana Pérez',
			)
		);
		$this->acting_as( 0 );
		$this->acting_as( $uid );

		$html = Home::render();
		$this->assertStringContainsString( 'Hola, Ana Pérez', $html );
		$this->assertStringContainsString( 'evt-inicio-acceso', $html );
		$this->assertStringContainsString( esc_url( Shell::url( 'events' ) ), $html );
	}

	/**
	 * El arranque engancha el shortcode y la redirección de la portada.
	 */
	public function test_register_hooks_the_shortcode_and_the_redirect() {
		Home::register();

		$this->assertTrue( shortcode_exists( Home::SHORTCODE ) );
		$this->assertNotFalse( has_action( 'template_redirect', array( Home::class, 'send_to_landing' ) ) );
	}
}
