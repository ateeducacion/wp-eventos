<?php
/**
 * Rendering tests for the five panels of the event workshop.
 *
 * @package Evt
 */

use Evt\Meta\EventMetaKeys;
use Evt\Meta\ProgrammeMetaKeys;
use Evt\Meta\RegistrationMetaKeys;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\Programme;
use Evt\PublicFront\Registrations;
use Evt\PublicFront\View\EventWorkspaceView;

/**
 * Que las cinco pestañas del taller se pinten, y con datos dentro.
 *
 * `test-views.php` ya comprueba que la pantalla se monta; esto es lo otro: que
 * cada panel **enseña lo que tiene que enseñar** cuando el evento trae ponentes,
 * programa, talleres, inscripciones y preguntas. Son los paneles que más código
 * tienen y los que nadie mira hasta abrir el navegador: una clave que ya no está
 * en el modelo aquí es un error fatal, no un hueco de estilo.
 */
class Test_Workspace_Panels extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Con las páginas del aplicativo ya creadas.
	 */
	public function set_up() {
		parent::set_up();
		$this->pages();
	}

	/**
	 * El evento de estas pruebas, con todo lo que puede colgar de él.
	 *
	 * @return int
	 */
	private function evento(): int {
		$area   = $this->area( 'Innovación' );
		$autor  = $this->administrator();
		$evento = $this->event(
			$autor,
			array( $area ),
			array(
				EventMetaKeys::START_DATE => '2026-10-28',
				EventMetaKeys::END_DATE   => '2026-10-30',
			),
			array( 'post_title' => 'Jornadas de prueba' )
		);
		$this->acting_as( $autor );
		return $evento;
	}

	/**
	 * El modelo del taller abierto por una pestaña.
	 *
	 * @param int    $evento Event ID.
	 * @param string $panel  Panel key.
	 * @return array<string, mixed>
	 */
	private function modelo( int $evento, string $panel ): array {
		$_GET[ EventWorkspace::ARG_EVENT ] = (string) $evento;
		$_GET[ EventWorkspace::ARG_PANEL ] = $panel;
		return EventWorkspace::model();
	}

	/**
	 * Lo que se pinta de una pestaña.
	 *
	 * @param int    $evento Event ID.
	 * @param string $panel  Panel key.
	 * @return string
	 */
	private function pintar( int $evento, string $panel ): string {
		return EventWorkspaceView::html( $this->modelo( $evento, $panel ) );
	}

	/**
	 * Un ponente.
	 *
	 * @param int    $evento Event ID.
	 * @param string $nombre Name.
	 * @return int
	 */
	private function ponente( int $evento, string $nombre ): int {
		return Programme::save_speaker(
			$evento,
			0,
			array(
				'name' => $nombre,
				'role' => 'Asesora de formación',
				'org'  => 'Centro de formación Norte',
				'bio'  => 'Trabaja en formación del profesorado.',
			)
		);
	}

	/**
	 * Una actividad.
	 *
	 * @param int                  $evento  Event ID.
	 * @param array<string, mixed> $cambios Overrides.
	 * @return int
	 */
	private function actividad( int $evento, array $cambios = array() ): int {
		return Programme::save_activity(
			$evento,
			0,
			array_merge(
				array(
					'title'    => 'Ponencia inaugural',
					'kind'     => 'ponencia',
					'date'     => '2026-10-28',
					'start'    => '09:30',
					'end'      => '10:30',
					'venue'    => 'Centro de formación Norte',
					'room'     => 'Salón de actos',
					'seats'    => 0,
					'summary'  => 'Abre las jornadas.',
					'speakers' => array(),
				),
				$cambios
			)
		);
	}

	// ─── Ponentes ──────────────────────────────────────────────────────────

	/**
	 * La ficha de cada ponente, con sus botones de icono y su orden.
	 */
	public function test_the_speakers_panel_paints_its_cards() {
		$evento = $this->evento();
		$this->ponente( $evento, 'Ana Martín Cabrera' );
		$this->ponente( $evento, 'Luis Gómez Perdomo' );

		$html = $this->pintar( $evento, EventWorkspace::PANEL_SPEAKERS );

		$this->assertStringContainsString( 'Ana Martín Cabrera', $html );
		$this->assertStringContainsString( 'Luis Gómez Perdomo', $html );
		$this->assertStringContainsString( 'Asesora de formación', $html );
		// Los botones son de icono con bocadillo (ADR-0029): lo que dice qué
		// hacen es su texto accesible, no el dibujo.
		$this->assertStringContainsString( 'Editar', $html );
		$this->assertStringContainsString( 'papelera', $html );
		$this->assertStringContainsString( '<svg', $html, 'los iconos van en línea' );
		$this->assertStringContainsString( 'method="post"', $html );
	}

	/**
	 * Y el formulario de alta, con su lista de campos.
	 */
	public function test_the_speakers_panel_paints_the_form() {
		$evento = $this->evento();
		$html   = $this->pintar( $evento, EventWorkspace::PANEL_SPEAKERS );

		foreach ( array( 'evt_sp_name', 'evt_sp_role', 'evt_sp_org', 'evt_sp_bio' ) as $campo ) {
			$this->assertStringContainsString( 'name="' . $campo . '"', $html, 'falta ' . $campo );
		}
	}

	/**
	 * Sin ponentes, lo dice en vez de pintar una tabla vacía.
	 */
	public function test_the_speakers_panel_says_when_there_is_nobody() {
		$html = $this->pintar( $this->evento(), EventWorkspace::PANEL_SPEAKERS );
		$this->assertStringContainsString( 'ponente', $html );
		$this->assertStringNotContainsString( 'Ana Martín', $html );
	}

	// ─── Programa ──────────────────────────────────────────────────────────

	/**
	 * La parrilla agrupa por día y, dentro del día, por sede (ADR-0024).
	 */
	public function test_the_programme_panel_groups_by_day_and_venue() {
		$evento = $this->evento();
		$this->actividad( $evento );
		$this->actividad(
			$evento,
			array(
				'title' => 'Taller de radio escolar',
				'kind'  => ProgrammeMetaKeys::KIND_WORKSHOP,
				'start' => '16:00',
				'end'   => '18:00',
				'venue' => 'Instituto Sur',
				'seats' => 20,
			)
		);

		$html = $this->pintar( $evento, EventWorkspace::PANEL_PROGRAMME );

		$this->assertStringContainsString( 'Ponencia inaugural', $html );
		$this->assertStringContainsString( 'Taller de radio escolar', $html );
		// Dos sedes el mismo día: las dos tienen que salir con su rótulo.
		$this->assertStringContainsString( 'Centro de formación Norte', $html );
		$this->assertStringContainsString( 'Instituto Sur', $html );
		$this->assertStringContainsString( '09:30', $html );
	}

	/**
	 * El formulario de actividad trae la lista cerrada de tipos.
	 */
	public function test_the_programme_panel_offers_the_closed_list_of_kinds() {
		$html = $this->pintar( $this->evento(), EventWorkspace::PANEL_PROGRAMME );

		foreach ( ProgrammeMetaKeys::activity_kinds() as $rotulo ) {
			$this->assertStringContainsString( esc_html( $rotulo ), $html );
		}
		$this->assertStringContainsString( 'name="evt_ac_date"', $html );
		$this->assertStringContainsString( 'name="evt_ac_seats"', $html );
	}

	// ─── Talleres ──────────────────────────────────────────────────────────

	/**
	 * Los talleres salen con su aforo y su ocupación.
	 */
	public function test_the_workshops_panel_shows_seats_and_takers() {
		$evento = $this->evento();
		$taller = $this->actividad(
			$evento,
			array(
				'title' => 'Taller de robótica',
				'kind'  => ProgrammeMetaKeys::KIND_WORKSHOP,
				'seats' => 12,
			)
		);
		// Una actividad que no es taller no tiene por qué salir aquí.
		$this->actividad(
			$evento,
			array(
				'title' => 'Mesa redonda',
				'kind'  => 'mesa',
			)
		);

		$html = $this->pintar( $evento, EventWorkspace::PANEL_WORKSHOPS );

		$this->assertStringContainsString( 'Taller de robótica', $html );
		$this->assertStringContainsString( '12', $html, 'el aforo se enseña' );
		$this->assertStringNotContainsString( 'Mesa redonda', $html, 'solo talleres' );
		unset( $taller );
	}

	// ─── Participantes ─────────────────────────────────────────────────────

	/**
	 * La tabla de participantes, sus columnas y su exportación.
	 */
	public function test_the_participants_panel_paints_the_table() {
		$evento = $this->evento();
		$this->inscribir( $evento, 'Ana', 'Martín Cabrera', 'ana@example.org' );
		$this->inscribir( $evento, 'Luis', 'Gómez Perdomo', 'luis@example.org' );

		$html = $this->pintar( $evento, EventWorkspace::PANEL_PEOPLE );

		$this->assertStringContainsString( 'Ana Martín Cabrera', $html );
		$this->assertStringContainsString( 'luis@example.org', $html );
		foreach ( \Evt\PublicFront\Participants::columns() as $rotulo ) {
			$this->assertStringContainsString( esc_html( $rotulo ), $html );
		}
		// La exportación va por POST con nonce: un enlace en GET que descarga
		// la lista de personas es justo lo que no puede pegarse en un correo.
		$this->assertStringContainsString( EventWorkspace::OP_EXPORT, $html );
		$this->assertStringContainsString( 'method="post"', $html );
	}

	/**
	 * Y el buscador acota lo que se ve.
	 */
	public function test_the_participants_panel_filters() {
		$evento = $this->evento();
		$this->inscribir( $evento, 'Ana', 'Martín Cabrera', 'ana@example.org' );
		$this->inscribir( $evento, 'Luis', 'Gómez Perdomo', 'luis@example.org' );

		$_GET[ EventWorkspace::ARG_Q ] = 'perdomo';
		$html                          = $this->pintar( $evento, EventWorkspace::PANEL_PEOPLE );

		$this->assertStringContainsString( 'Luis Gómez Perdomo', $html );
		$this->assertStringNotContainsString( 'Ana Martín Cabrera', $html );
		unset( $_GET[ EventWorkspace::ARG_Q ] );
	}

	// ─── Inscripción ───────────────────────────────────────────────────────

	/**
	 * El panel de inscripción trae los plazos, el consentimiento y las preguntas.
	 */
	public function test_the_signup_panel_paints_the_settings_and_the_questions() {
		$evento = $this->evento();
		update_post_meta( $evento, RegistrationMetaKeys::SIGNUP_OPEN, true );
		update_post_meta( $evento, RegistrationMetaKeys::CONSENT_VERSION, 4 );
		update_post_meta(
			$evento,
			RegistrationMetaKeys::SIGNUP_QUESTIONS,
			wp_slash(
				(string) wp_json_encode(
					array(
						array(
							'id'       => 'qcomida000001',
							'label'    => 'Se queda a comer',
							'type'     => 'check',
							'options'  => array(),
							'required' => false,
						),
					)
				)
			)
		);

		$html = $this->pintar( $evento, EventWorkspace::PANEL_SIGNUP );

		$this->assertStringContainsString( 'name="evt_signup_open"', $html );
		$this->assertStringContainsString( 'name="evt_workshop_open"', $html );
		$this->assertStringContainsString( 'name="evt_consent_privacy"', $html );
		$this->assertStringContainsString( 'v4', $html, 'la versión del consentimiento está a la vista' );

		// La pregunta guardada, y la fila en blanco para añadir otra.
		$this->assertStringContainsString( 'Se queda a comer', $html );
		$this->assertStringContainsString( 'evt_q_label[0]', $html );
		$this->assertStringContainsString( 'evt_q_label[1]', $html, 'la fila en blanco del final' );
		$this->assertStringContainsString( 'Pregunta nueva', $html );

		// Y los cuatro tipos, que son lista cerrada.
		foreach ( RegistrationMetaKeys::question_types() as $rotulo ) {
			$this->assertStringContainsString( esc_html( $rotulo ), $html );
		}
	}

	/**
	 * Con gente inscrita, el panel avisa de lo que ya no se puede tocar.
	 */
	public function test_the_signup_panel_warns_once_somebody_signed_up() {
		$evento = $this->evento();
		$this->inscribir( $evento, 'Ana', 'Martín Cabrera', 'ana@example.org' );

		$html = $this->pintar( $evento, EventWorkspace::PANEL_SIGNUP );

		$this->assertStringContainsString( 'Ya hay personas inscritas', $html );
		$this->assertStringContainsString( 'no cambiar el tipo', $html );
	}

	/**
	 * Las cinco pestañas salen en la barra, con su recuento.
	 */
	public function test_every_tab_is_in_the_bar_with_its_count() {
		$evento = $this->evento();
		$this->ponente( $evento, 'Ana Martín Cabrera' );

		$html = $this->pintar( $evento, EventWorkspace::PANEL_SECTIONS );

		foreach ( array( 'Ponentes', 'Programa', 'Talleres', 'Inscripción', 'Participantes' ) as $rotulo ) {
			$this->assertStringContainsString( $rotulo, $html, 'falta la pestaña ' . $rotulo );
		}
	}

	/**
	 * Una inscripción de prueba.
	 *
	 * @param int    $evento    Event ID.
	 * @param string $nombre    Name.
	 * @param string $apellidos Surname.
	 * @param string $correo    Email.
	 * @return int
	 */
	private function inscribir( int $evento, string $nombre, string $apellidos, string $correo ): int {
		return Registrations::create(
			$evento,
			array(
				'tax_id'  => '12345678Z',
				'name'    => $nombre,
				'surname' => $apellidos,
				'email'   => $correo,
				'phone'   => '600000000',
				'centre'  => 'CEIP El Molino',
				'consent' => true,
			),
			array()
		);
	}
	// ─── el estado vacío y el desplegable de talleres ──────────────────────

	/**
	 * Sin nadie inscrito, la pestaña dice dónde se abre la inscripción.
	 *
	 * Y no deja una tabla sin filas, que parece un fallo del evento.
	 */
	public function test_the_participants_panel_explains_when_there_is_nobody() {
		$evento = $this->evento();
		$html   = $this->pintar( $evento, EventWorkspace::PANEL_PEOPLE );

		$this->assertStringContainsString( 'Todavía no hay nadie inscrito', $html );
		$this->assertStringContainsString( 'Inscripción', $html );
		$this->assertStringNotContainsString( 'por construir', $html, 'el formulario ya existe' );
	}

	/**
	 * Un evento que aún apunta a su formulario antiguo lo dice, y dice que no se lee.
	 */
	public function test_the_participants_panel_names_the_legacy_form_as_history() {
		$evento = $this->evento();
		update_post_meta( $evento, EventMetaKeys::SIGNUP_FORM_ID, '41' );

		$html = $this->pintar( $evento, EventWorkspace::PANEL_PEOPLE );

		$this->assertStringContainsString( '41', $html );
		$this->assertStringContainsString( 'histórico', strtolower( $html ) );
	}

	/**
	 * Con talleres, el filtro trae su desplegable.
	 */
	public function test_the_participants_panel_offers_the_workshop_filter() {
		$evento = $this->evento();
		$this->actividad(
			$evento,
			array(
				'title' => 'Taller de radio escolar',
				'kind'  => ProgrammeMetaKeys::KIND_WORKSHOP,
				'seats' => 20,
			)
		);
		$id = $this->inscribir( $evento, 'Ana', 'Martín Cabrera', 'ana@example.org' );
		Registrations::seat( $evento, $id, \Evt\PublicFront\Programme::workshops( $evento )[0]->ID );

		$html = $this->pintar( $evento, EventWorkspace::PANEL_PEOPLE );

		$this->assertStringContainsString( 'evt-people-taller', $html );
		$this->assertStringContainsString( 'Taller de radio escolar', $html );
		$this->assertStringContainsString( '>Todos<', $html );
	}
}
