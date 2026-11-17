<?php
/**
 * Tests for the notice of an event that is not published yet.
 *
 * @package Evt
 */

use Evt\PostType\EventPostType;
use Evt\PublicFront\DraftNotice;

/**
 * Un evento en borrador dice que lo está, y no «no se encontró la página» (ADR-0055).
 */
class Test_Draft_Notice extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Visit an address and serve what the notice serves, '' when it lets it through.
	 *
	 * @param string $url Address.
	 * @return string
	 */
	private function visitar( string $url ): string {
		$this->go_to( $url );
		return $this->served( array( DraftNotice::class, 'maybe_show' ) );
	}

	/**
	 * Sin sesión, un borrador dice que lo es, sin su título, con el acceso para responsables.
	 */
	public function test_a_draft_says_so_and_offers_to_log_in() {
		$evento = $this->event(
			$this->administrator(),
			array( $this->area() ),
			array(),
			array(
				'post_status' => 'draft',
				'post_title'  => 'Jornadas secretas',
			)
		);
		wp_set_current_user( 0 );

		$html = $this->visitar(
			add_query_arg(
				array(
					'post_type' => EventPostType::POST_TYPE,
					'p'         => $evento,
				),
				home_url( '/' )
			)
		);

		$this->assertStringContainsString( 'modo borrador', $html );
		$this->assertStringContainsString( 'Acceso para responsables', $html );
		$this->assertStringContainsString( 'wp-login.php', $html );
		$this->assertStringNotContainsString( 'Jornadas secretas', $html, 'un borrador no cuenta lo que lleva' );
	}

	/**
	 * Con sesión y sin permiso, dice con qué cuenta se entró y que esa no puede verlo.
	 */
	public function test_a_logged_in_account_without_permission_is_told_so() {
		$evento = $this->event( $this->administrator(), array( $this->area() ), array(), array( 'post_status' => 'draft' ) );
		$quien  = (int) self::factory()->user->create(
			array(
				'role'         => 'subscriber',
				'display_name' => 'Persona sin permiso',
			)
		);
		wp_set_current_user( $quien );

		$html = $this->visitar(
			add_query_arg(
				array(
					'post_type' => EventPostType::POST_TYPE,
					'p'         => $evento,
				),
				home_url( '/' )
			)
		);

		$this->assertStringContainsString( 'Persona sin permiso', $html );
		$this->assertStringContainsString( 'no puede ver este borrador', $html );
		$this->assertStringContainsString( 'Entrar con otra cuenta', $html );
	}

	/**
	 * Quien puede verlo lo ve; lo publicado y lo que está en la papelera no pasan por aquí.
	 */
	public function test_it_only_answers_events_being_prepared() {
		$admin  = $this->administrator();
		$evento = $this->event( $admin, array( $this->area() ), array(), array( 'post_status' => 'draft' ) );
		$url    = add_query_arg(
			array(
				'post_type' => EventPostType::POST_TYPE,
				'p'         => $evento,
			),
			home_url( '/' )
		);

		wp_set_current_user( $admin );
		$this->assertSame( '', $this->visitar( $url ), 'quien lo prepara ve el evento, no el aviso' );

		wp_set_current_user( 0 );
		wp_update_post(
			array(
				'ID'          => $evento,
				'post_status' => 'publish',
			)
		);
		$this->assertSame( '', $this->visitar( $url ), 'publicado, no hay aviso' );

		wp_trash_post( $evento );
		$this->assertFalse( DraftNotice::is_being_prepared( $evento ), 'en la papelera, el 404 de siempre' );
	}

	/**
	 * Una sección publicada de un evento en borrador tampoco se ve, y avisa igual.
	 */
	public function test_a_section_of_a_draft_event_gets_the_notice() {
		$evento  = $this->event( $this->administrator(), array( $this->area() ), array(), array( 'post_status' => 'draft' ) );
		$seccion = $this->event_page( $evento, 'programa', array( 'post_name' => 'programa' ) );
		wp_set_current_user( 0 );

		// El enlace de una sección de un borrador es por número (EventPostType::draft_link()).
		$html = $this->visitar(
			add_query_arg(
				array(
					'post_type' => EventPostType::POST_TYPE,
					'p'         => $seccion,
				),
				home_url( '/' )
			)
		);

		$this->assertStringContainsString( 'Este evento todavía no se ha publicado', $html );
	}
}
