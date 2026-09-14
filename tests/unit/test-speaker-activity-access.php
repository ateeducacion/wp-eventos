<?php
/**
 * Tests for área scoping on speakers and activities.
 *
 * @package Evt
 */

use Evt\Access\EventAccess;
use Evt\PostType\ActivityPostType;
use Evt\PostType\SpeakerPostType;
use Evt\Taxonomy\EventTaxonomies;

/**
 * Un área gestiona su evento entero: sus páginas, sus ponentes y sus actividades.
 *
 * La regla del acotado es la misma que la del evento —compartir un término de
 * `evt_area`—, y aquí se prueban las dos capas: lo que responde
 * `EventAccess::can_edit()` y lo que de verdad cierra la puerta,
 * `user_can( …, 'edit_post', … )`, que es lo que protege la edición rápida y la
 * REST.
 */
class Test_Speaker_Activity_Access extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Tipos, taxonomías y roles registrados.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
	}

	/**
	 * Un ponente o una actividad, firmados por quien se diga.
	 *
	 * @param string $tipo  Post type.
	 * @param int    $autor Author user ID.
	 * @param int[]  $areas Term IDs of evt_area; empty to let stamp_area decide.
	 * @return int Post ID.
	 */
	private function participante( string $tipo, int $autor, array $areas = array() ): int {
		$id = (int) self::factory()->post->create(
			array(
				'post_type'   => $tipo,
				'post_status' => 'publish',
				'post_author' => $autor,
				'post_title'  => 'Quien participa',
			)
		);
		if ( array() !== $areas ) {
			wp_set_object_terms( $id, array_map( 'intval', $areas ), EventTaxonomies::AREA );
		}
		return $id;
	}

	/**
	 * Los dos tipos, para recorrerlos en los tests que valen para ambos.
	 *
	 * @return string[]
	 */
	private function tipos(): array {
		return array( SpeakerPostType::POST_TYPE, ActivityPostType::POST_TYPE );
	}

	/**
	 * El área es el eje de permisos también aquí, así que la taxonomía tiene
	 * que estar puesta en los dos tipos: sin eso no hay dónde escribirla.
	 */
	public function test_the_area_taxonomy_reaches_speakers_and_activities() {
		foreach ( $this->tipos() as $tipo ) {
			$this->assertContains( EventTaxonomies::AREA, get_object_taxonomies( $tipo ), $tipo );
		}
	}

	/**
	 * Quien organiza da de alta a quien participa, y lo que crea queda en su
	 * área sin tener que acordarse de ponérsela.
	 */
	public function test_the_organiser_creates_and_edits_the_ones_of_its_area() {
		$mia = $this->area( 'Formación del Profesorado' );
		$yo  = $this->organiser( array( $mia ) );

		foreach ( $this->tipos() as $tipo ) {
			// Puede crear: WordPress usa `edit_evt_speakers` para «Añadir nuevo»
			// porque el mapa no declara `create_posts`.
			$this->assertTrue( user_can( $yo, get_post_type_object( $tipo )->cap->create_posts ), $tipo );

			$suyo = $this->participante( $tipo, $yo );

			$this->assertSame( array( $mia ), EventAccess::post_areas( $suyo ), $tipo );
			$this->assertTrue( EventAccess::can_edit( $yo, $suyo ), $tipo );
			$this->assertTrue( user_can( $yo, 'edit_post', $suyo ), $tipo );
			$this->assertTrue( user_can( $yo, 'delete_post', $suyo ), $tipo );
			$this->assertSame( '', EventAccess::why_not_editable( $yo, $suyo ), $tipo );
		}
	}

	/**
	 * Y lo de la de al lado no lo toca, aunque tenga la capacidad.
	 */
	public function test_it_does_not_touch_the_ones_of_another_area() {
		$mia  = $this->area( 'Formación del Profesorado' );
		$otra = $this->area( 'Innovación' );
		$yo   = $this->organiser( array( $mia ) );
		$ella = $this->organiser( array( $otra ) );

		foreach ( $this->tipos() as $tipo ) {
			$suyo = $this->participante( $tipo, $ella );

			$this->assertSame( array( $otra ), EventAccess::post_areas( $suyo ), $tipo );
			$this->assertFalse( EventAccess::can_edit( $yo, $suyo ), $tipo );
			$this->assertFalse( user_can( $yo, 'edit_post', $suyo ), $tipo );
			$this->assertFalse( user_can( $yo, 'delete_post', $suyo ), $tipo );
			$this->assertStringContainsString( 'de otra área', EventAccess::why_not_editable( $yo, $suyo ), $tipo );
		}
	}

	/**
	 * El caso que hace falta explicar: un ponente es reutilizable entre
	 * ediciones, así que puede ser de dos áreas a la vez. Compartirlo es
	 * añadirle el término de la otra, no duplicar la ficha, y entonces las dos
	 * lo editan.
	 */
	public function test_a_shared_speaker_belongs_to_both_areas() {
		$mia        = $this->area( 'Formación del Profesorado' );
		$otra       = $this->area( 'Innovación' );
		$yo         = $this->organiser( array( $mia ) );
		$ella       = $this->organiser( array( $otra ) );
		$compartido = $this->participante( SpeakerPostType::POST_TYPE, $yo );

		$this->assertFalse( EventAccess::can_edit( $ella, $compartido ), 'todavía es solo de mi área' );

		wp_set_object_terms( $compartido, array( $mia, $otra ), EventTaxonomies::AREA );

		$this->assertTrue( EventAccess::can_edit( $yo, $compartido ) );
		$this->assertTrue( EventAccess::can_edit( $ella, $compartido ) );
		$this->assertTrue( user_can( $ella, 'edit_post', $compartido ) );
	}

	/**
	 * El otro caso raro: quien lo crea no tiene área propia —la administración—,
	 * así que no hay nada que estampar. Se queda sin área y
	 * lo toca solo quien lo creó, hasta que alguien le ponga una. Falla en
	 * cerrado: ninguna organización lo hereda por descuido.
	 */
	public function test_one_created_without_an_area_stays_closed() {
		$mia   = $this->area( 'Formación del Profesorado' );
		$yo    = $this->organiser( array( $mia ) );
		$coord = $this->administrator();

		$huerfano = $this->participante( SpeakerPostType::POST_TYPE, $coord );

		$this->assertSame( array(), EventAccess::post_areas( $huerfano ) );
		$this->assertTrue( EventAccess::can_edit( $coord, $huerfano ), 'la administración trabaja sobre todas las áreas' );
		$this->assertFalse( EventAccess::can_edit( $yo, $huerfano ) );
		$this->assertFalse( user_can( $yo, 'edit_post', $huerfano ) );

		wp_set_object_terms( $huerfano, array( $mia ), EventTaxonomies::AREA );
		$this->assertTrue( EventAccess::can_edit( $yo, $huerfano ) );
	}

	/**
	 * El área ya puesta no se pisa en el siguiente guardado: si se pisara,
	 * compartir un ponente duraría hasta que alguien le cambiara una coma.
	 */
	public function test_saving_again_never_overwrites_the_area() {
		$mia  = $this->area( 'Formación del Profesorado' );
		$otra = $this->area( 'Innovación' );
		$yo   = $this->organiser( array( $mia ) );

		$ponente = $this->participante( SpeakerPostType::POST_TYPE, $yo, array( $otra ) );
		wp_update_post(
			array(
				'ID'         => $ponente,
				'post_title' => 'Con el nombre corregido',
			)
		);

		$this->assertSame( array( $otra ), EventAccess::post_areas( $ponente ) );
	}

	/**
	 * Sin área en el perfil no se edita nada, tampoco aquí.
	 */
	public function test_without_an_area_nobody_edits() {
		$mia      = $this->area( 'Formación del Profesorado' );
		$huerfano = $this->organiser();
		$ponente  = $this->participante( SpeakerPostType::POST_TYPE, $this->organiser( array( $mia ) ) );

		$this->assertFalse( EventAccess::can_edit( $huerfano, $ponente ) );
		$this->assertFalse( user_can( $huerfano, 'edit_post', $ponente ) );
		$this->assertStringContainsString( 'No tiene ningún área asignada', EventAccess::why_not_editable( $huerfano, $ponente ) );
	}

	/**
	 * De ponentes y actividades el juego de capacidades es el completo: lo que
	 * acota es el área, no una capacidad que falte.
	 */
	public function test_the_organiser_holds_the_whole_set_for_speakers_and_activities() {
		$organiser = $this->organiser();

		foreach ( array( 'evt_speakers', 'evt_activities' ) as $plural ) {
			$caps = array(
				'edit_' . $plural,
				'edit_others_' . $plural,
				'edit_private_' . $plural,
				'edit_published_' . $plural,
				'publish_' . $plural,
				'read_private_' . $plural,
				'delete_' . $plural,
				'delete_others_' . $plural,
				'delete_private_' . $plural,
				'delete_published_' . $plural,
			);
			foreach ( $caps as $cap ) {
				$this->assertTrue( user_can( $organiser, $cap ), $cap );
			}
		}
	}

	/**
	 * Del evento, en cambio, la organización sigue sin lo de otra persona ni lo
	 * privado: esas se quedan en la administración.
	 */
	public function test_the_event_set_is_still_the_narrow_one() {
		$organiser = $this->organiser();

		foreach ( array( 'delete_others_evt_events', 'edit_private_evt_events', 'delete_private_evt_events' ) as $cap ) {
			$this->assertFalse( user_can( $organiser, $cap ), $cap );
		}
	}

	/**
	 * La raya que no se mueve: el JavaScript a medida y los ajustes.
	 *
	 * El CSS salió de esta lista —lo escribe también el área, acotada a la
	 * suya—, y justo por eso se comprueba aquí, con los ponentes y las
	 * actividades por medio: abrirle el CSS no le abrió nada más.
	 */
	public function test_the_line_that_does_not_move() {
		$prohibidas = array( 'evt_edit_custom_js', 'evt_manage_app' );
		$organiser  = $this->organiser( array( $this->area( 'Formación del Profesorado' ) ) );

		foreach ( $prohibidas as $cap ) {
			$this->assertFalse( user_can( $organiser, $cap ), $cap );
		}

		// Y están nombradas, para que «Ajustes» avise si alguien las concede a
		// mano en WPFront: lo que no se nombra no se comprueba.
		foreach ( $prohibidas as $cap ) {
			$this->assertContains( $cap, evt_forbidden_role_caps(), $cap );
		}
		foreach ( evt_roles_status() as $slug => $estado ) {
			$this->assertSame( array(), $estado['forbidden'], $slug );
		}
	}

	/**
	 * Y no se cuelan por la puerta de atrás: el guardián solo conoce los tres
	 * tipos del aplicativo y deja en paz lo demás.
	 */
	public function test_the_guard_leaves_the_rest_of_the_site_alone() {
		$this->assertSame(
			array( 'evt_event', 'evt_speaker', 'evt_activity' ),
			array_keys( EventAccess::scoped_types() )
		);

		$entrada = (int) self::factory()->post->create( array( 'post_type' => 'post' ) );
		$this->assertFalse( EventAccess::can_edit( $this->administrator(), $entrada ) );
		$this->assertTrue( user_can( (int) self::factory()->user->create( array( 'role' => 'editor' ) ), 'edit_post', $entrada ) );
	}
}
