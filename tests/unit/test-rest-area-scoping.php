<?php
/**
 * El acotado por área, visto desde la REST.
 *
 * Los tipos del aplicativo salen por `wp/v2/…` porque el editor de bloques los
 * necesita, así que la REST es una puerta más a la que le vale el guardián y no
 * un caso aparte. Lo que se comprueba aquí es que un evento —o un ponente, o
 * una actividad— que todavía no es público solo lo lee su área, y que eso no se
 * lleva por delante ni lo publicado ni lo que ya funcionaba.
 *
 * @package Evt
 */

use Evt\Meta\EventMetaKeys;
use Evt\PostType\ActivityPostType;
use Evt\PostType\EventPostType;
use Evt\PostType\SpeakerPostType;
use Evt\Taxonomy\EventTaxonomies;

/**
 * REST route authorisation for the área-scoped post types.
 */
class Test_Evt_Rest_Area_Scoping extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Área that owns the fixtures.
	 *
	 * @var int
	 */
	private $area_owner = 0;

	/**
	 * A different área.
	 *
	 * @var int
	 */
	private $area_other = 0;

	/**
	 * Organiser of the owning área.
	 *
	 * @var int
	 */
	private $owner = 0;

	/**
	 * Organiser of the other área: the one that must not get in.
	 *
	 * @var int
	 */
	private $outsider = 0;

	/**
	 * Register the app and rebuild the REST routes for this test.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();

		// Los tipos se vuelven a registrar en cada test, así que el servidor
		// REST de antes tiene las rutas del mundo anterior.
		global $wp_rest_server;
		$wp_rest_server = null;
		rest_get_server();

		$this->area_owner = $this->area( 'Área que organiza' );
		$this->area_other = $this->area( 'Área de al lado' );
		$this->owner      = $this->organiser( array( $this->area_owner ) );
		$this->outsider   = $this->organiser( array( $this->area_other ) );
	}

	/**
	 * Dispatch a REST request as whoever is logged in.
	 *
	 * @param string               $method HTTP method.
	 * @param string               $route  Route.
	 * @param array<string, mixed> $params Query or body params.
	 * @return WP_REST_Response
	 */
	private function rest( string $method, string $route, array $params = array() ): WP_REST_Response {
		$request = new WP_REST_Request( $method, $route );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}
		return rest_get_server()->dispatch( $request );
	}

	/**
	 * A private event of the owning área.
	 *
	 * @param array<string, mixed> $args Post field overrides.
	 * @return int
	 */
	private function private_event( array $args = array() ): int {
		return $this->event(
			$this->owner,
			array( $this->area_owner ),
			array(),
			array_merge(
				array(
					'post_status' => 'private',
					'post_title'  => 'Jornada sin anunciar',
				),
				$args
			)
		);
	}

	/**
	 * A post of one of the two satellite types, in the owning área.
	 *
	 * @param string $post_type Speaker or activity.
	 * @param string $status    Post status.
	 * @param string $title     Post title.
	 * @return int
	 */
	private function scoped_post( string $post_type, string $status, string $title ): int {
		$id = (int) self::factory()->post->create(
			array(
				'post_type'   => $post_type,
				'post_status' => $status,
				'post_title'  => $title,
				'post_author' => $this->owner,
			)
		);
		wp_set_object_terms( $id, array( $this->area_owner ), EventTaxonomies::AREA );
		return $id;
	}

	/**
	 * The titles a collection response actually returned.
	 *
	 * @param WP_REST_Response $response Response.
	 * @return string[]
	 */
	private function titles( WP_REST_Response $response ): array {
		$out = array();
		foreach ( (array) $response->get_data() as $item ) {
			$out[] = (string) ( $item['title']['rendered'] ?? '' );
		}
		return $out;
	}

	/**
	 * Un área no lee por la REST el evento en privado de otra.
	 */
	public function test_outsider_cannot_read_a_private_event_item() {
		$event = $this->private_event();
		$this->acting_as( $this->outsider );

		$response = $this->rest( 'GET', '/wp/v2/evt_event/' . $event );

		$this->assertSame( 403, $response->get_status(), 'un área no lee lo privado de otra' );
		$this->assertStringNotContainsString( 'Jornada sin anunciar', wp_json_encode( $response->get_data() ) );
	}

	/**
	 * Y tampoco se lo encuentra pidiendo la colección en privado.
	 */
	public function test_outsider_cannot_see_a_private_event_in_the_collection() {
		$this->private_event();
		$this->acting_as( $this->outsider );

		$response = $this->rest( 'GET', '/wp/v2/evt_event', array( 'status' => 'private' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array(), $this->titles( $response ), 'la colección no devuelve lo de otra área' );
	}

	/**
	 * La misma regla para lo que cuelga del evento: ponentes y actividades.
	 */
	public function test_outsider_cannot_read_a_private_speaker_or_activity() {
		$casos = array(
			SpeakerPostType::POST_TYPE  => 'evt_speaker',
			ActivityPostType::POST_TYPE => 'evt_activity',
		);

		foreach ( $casos as $post_type => $base ) {
			$id = $this->scoped_post( $post_type, 'private', 'Ficha reservada' );
			$this->acting_as( $this->outsider );

			$response = $this->rest( 'GET', '/wp/v2/' . $base . '/' . $id );

			$this->assertSame( 403, $response->get_status(), $base );
			$this->assertStringNotContainsString( 'Ficha reservada', wp_json_encode( $response->get_data() ), $base );
		}
	}

	/**
	 * WordPress empareja la ruta sin distinguir mayúsculas, así que la variante
	 * en mayúsculas llega al mismo controlador y tiene que encontrarse el mismo
	 * «no».
	 */
	public function test_the_uppercase_route_variant_is_denied_the_same_way() {
		$event = $this->private_event();
		$this->acting_as( $this->outsider );

		$response = $this->rest( 'GET', '/WP/V2/evt_event/' . $event );

		$this->assertSame( 403, $response->get_status() );
		$this->assertStringNotContainsString( 'Jornada sin anunciar', wp_json_encode( $response->get_data() ) );
	}

	/**
	 * Sin entrar no se lee nada que no esté publicado.
	 */
	public function test_anonymous_cannot_read_a_private_event() {
		$event = $this->private_event();
		$this->acting_as( 0 );

		$response = $this->rest( 'GET', '/wp/v2/evt_event/' . $event );

		$this->assertSame( 401, $response->get_status() );
	}

	/**
	 * Su área sí lo lee, que es de lo que va el acotado.
	 */
	public function test_the_owning_area_still_reads_its_private_event() {
		$event = $this->private_event();
		$this->acting_as( $this->owner );

		$item       = $this->rest( 'GET', '/wp/v2/evt_event/' . $event );
		$collection = $this->rest( 'GET', '/wp/v2/evt_event', array( 'status' => 'private' ) );

		$this->assertSame( 200, $item->get_status() );
		$this->assertSame( 'Jornada sin anunciar', $item->get_data()['title']['rendered'] );
		$this->assertSame( array( 'Jornada sin anunciar' ), $this->titles( $collection ) );
	}

	/**
	 * El cierre por histórico quita la edición, no la consulta: por eso la
	 * puerta de la lectura es `can_open()` y no `can_edit()`.
	 */
	public function test_the_owning_area_still_reads_its_archived_private_event() {
		$event = $this->private_event();
		update_post_meta( $event, EventMetaKeys::ARCHIVED, true );
		$this->acting_as( $this->owner );

		$response = $this->rest( 'GET', '/wp/v2/evt_event/' . $event );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'Jornada sin anunciar', $response->get_data()['title']['rendered'] );
	}

	/**
	 * Administración se salta el acotado, como en todo lo demás.
	 */
	public function test_administration_reads_the_private_event_of_any_area() {
		$event = $this->private_event();
		$this->acting_as( $this->administrator() );

		$response = $this->rest( 'GET', '/wp/v2/evt_event/' . $event );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'Jornada sin anunciar', $response->get_data()['title']['rendered'] );
	}

	/**
	 * Lo publicado se sigue leyendo sin entrar: no se cierra de más.
	 */
	public function test_a_published_event_stays_public() {
		$event = $this->event(
			$this->owner,
			array( $this->area_owner ),
			array(),
			array( 'post_title' => 'Jornada anunciada' )
		);
		$this->acting_as( 0 );

		$item       = $this->rest( 'GET', '/wp/v2/evt_event/' . $event );
		$collection = $this->rest( 'GET', '/wp/v2/evt_event' );

		$this->assertSame( 200, $item->get_status() );
		$this->assertSame( 'Jornada anunciada', $item->get_data()['title']['rendered'] );
		$this->assertContains( 'Jornada anunciada', $this->titles( $collection ) );
	}

	/**
	 * Un ponente publicado se enseña dentro de la página del evento, así que
	 * sigue saliendo por la REST: la lectura acotada es la de lo que no es
	 * público todavía, no la de todo.
	 */
	public function test_a_published_speaker_stays_public() {
		$speaker = $this->scoped_post( SpeakerPostType::POST_TYPE, 'publish', 'Ficha anunciada' );
		$this->acting_as( 0 );

		$response = $this->rest( 'GET', '/wp/v2/evt_speaker/' . $speaker );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'Ficha anunciada', $response->get_data()['title']['rendered'] );
	}

	/**
	 * La escritura sigue donde estaba: ni se abre ni se cierra.
	 */
	public function test_writing_permissions_are_unchanged() {
		$event = $this->event(
			$this->owner,
			array( $this->area_owner ),
			array(),
			array( 'post_title' => 'Jornada anunciada' )
		);

		$this->acting_as( $this->outsider );
		$ajena = $this->rest( 'POST', '/wp/v2/evt_event/' . $event, array( 'title' => 'Secuestrada' ) );
		$this->assertSame( 403, $ajena->get_status(), 'otra área no escribe' );

		$borrada = $this->rest( 'DELETE', '/wp/v2/evt_event/' . $event, array( 'force' => true ) );
		$this->assertSame( 403, $borrada->get_status(), 'otra área no borra' );

		$this->acting_as( $this->owner );
		$propia = $this->rest( 'POST', '/wp/v2/evt_event/' . $event, array( 'title' => 'Jornada corregida' ) );
		$this->assertSame( 200, $propia->get_status(), 'su área sigue escribiendo' );
		$this->assertSame( 'Jornada corregida', get_post_field( 'post_title', $event ) );
	}

	/**
	 * La regla vive en el guardián y no en la REST, así que se comprueba
	 * también donde de verdad está.
	 */
	public function test_the_guard_denies_read_post_across_areas() {
		$event = $this->private_event();

		$this->assertFalse( user_can( $this->outsider, 'read_post', $event ) );
		$this->assertTrue( user_can( $this->owner, 'read_post', $event ) );
		$this->assertTrue(
			user_can( $this->outsider, 'read_private_evt_events' ),
			'la capacidad suelta la sigue teniendo: lo que la acota es el guardián'
		);
	}
}
