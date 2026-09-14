<?php
/**
 * Fixtures compartidas por los tests: los tipos y taxonomías del aplicativo,
 * las personas con su rol y su área, los eventos con sus páginas satélite, las
 * páginas del aplicativo y la mecánica de un POST que acaba en Shell::leave().
 *
 * @package Evt
 */

use Evt\Access\EventAccess;
use Evt\Meta\EventMetaKeys;
use Evt\PostType\ActivityPostType;
use Evt\PostType\EventPostType;
use Evt\PostType\SpeakerPostType;
use Evt\PublicFront\ExitSignal;
use Evt\PublicFront\Shell;
use Evt\Taxonomy\EventTaxonomies;

/**
 * Se usa con `use Evt_Fixtures;` dentro de un WP_UnitTestCase.
 */
trait Evt_Fixtures {

	/**
	 * Roles, capacidades, tipos de contenido y taxonomías del aplicativo.
	 *
	 * `init` ya lo registró todo al arrancar, pero la base de datos se deshace
	 * tras cada test y los roles se van con ella; volver a registrarlo es
	 * barato y deja cada test en pie por sí solo.
	 *
	 * @return void
	 */
	protected function app(): void {
		evt_register_roles();
		EventTaxonomies::register();
		EventPostType::register();
		SpeakerPostType::register();
		ActivityPostType::register();
		EventPostType::grant_caps_to_roles();
	}

	/**
	 * Un área organizadora.
	 *
	 * @param string $nombre Term name; empty for a generated one.
	 * @return int Term ID.
	 */
	protected function area( string $nombre = '' ): int {
		$this->app();
		$args = array( 'taxonomy' => EventTaxonomies::AREA );
		if ( '' !== $nombre ) {
			$args['name'] = $nombre;
		}
		return (int) self::factory()->term->create( $args );
	}

	/**
	 * Alguien de la organización de un área.
	 *
	 * Sin áreas es el caso que más importa: el perfil a medio rellenar con el
	 * que el acotado tiene que fallar en cerrado.
	 *
	 * @param int[] $areas Term IDs of evt_area.
	 * @return int User ID.
	 */
	protected function organiser( array $areas = array() ): int {
		$this->app();
		$id = (int) self::factory()->user->create( array( 'role' => 'evt_organiser' ) );
		update_user_meta( $id, EventAccess::USER_AREA_META, array_map( 'intval', $areas ) );
		return $id;
	}

	/**
	 * Administración: el rol nativo de WordPress, que trabaja sobre todas las áreas.
	 *
	 * Es el único perfil que se salta el acotado por área, así que también es el
	 * autor cómodo para los eventos de los tests que van de otra cosa.
	 *
	 * @return int User ID.
	 */
	protected function administrator(): int {
		$this->app();
		return (int) self::factory()->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Un evento publicado, con su área y sus fechas.
	 *
	 * @param int                   $autor Author user ID.
	 * @param int[]                 $areas Term IDs of evt_area; empty for an event still without one.
	 * @param array<string, string> $meta  Meta key => value.
	 * @param array<string, mixed>  $args  Post fields to override (título, estado…).
	 * @return int Post ID.
	 */
	protected function event( int $autor, array $areas = array(), array $meta = array(), array $args = array() ): int {
		$this->app();
		$id = (int) self::factory()->post->create(
			array_merge(
				array(
					'post_type'   => EventPostType::POST_TYPE,
					'post_status' => 'publish',
					'post_author' => $autor,
					'post_title'  => 'Jornadas de prueba',
				),
				$args
			)
		);
		if ( array() !== $areas ) {
			wp_set_object_terms( $id, array_map( 'intval', $areas ), EventTaxonomies::AREA );
		}
		foreach ( $meta as $clave => $valor ) {
			update_post_meta( $id, $clave, $valor );
		}
		return $id;
	}

	/**
	 * Una página satélite del evento: el mismo tipo, con padre y sección.
	 *
	 * @param int                  $evento  Parent event post ID.
	 * @param string               $seccion Section type slug.
	 * @param array<string, mixed> $args Post fields to override (título, estado, orden…).
	 * @return int Post ID.
	 */
	protected function event_page( int $evento, string $seccion = 'programa', array $args = array() ): int {
		$id = (int) self::factory()->post->create(
			array_merge(
				array(
					'post_type'   => EventPostType::POST_TYPE,
					'post_status' => 'publish',
					'post_parent' => $evento,
					'post_author' => (int) get_post_field( 'post_author', $evento ),
					'post_title'  => 'Página del evento',
				),
				$args
			)
		);
		update_post_meta( $id, EventMetaKeys::SECTION_TYPE, $seccion );
		return $id;
	}

	/**
	 * Las páginas del aplicativo, cada una con su shortcode dentro.
	 *
	 * `Shell::url()` las busca por su slug y `Shell::is_app_page()` mira el
	 * shortcode: sin ellas, media pantalla devuelve la cadena vacía.
	 *
	 * @return void
	 */
	protected function pages(): void {
		foreach ( Shell::SLUGS as $seccion => $slug ) {
			if ( get_page_by_path( $slug ) ) {
				continue;
			}
			self::factory()->post->create(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_name'    => $slug,
					'post_title'   => $slug,
					'post_content' => '[' . Shell::SHORTCODES[ $seccion ] . ']',
				)
			);
		}
	}

	/**
	 * Entrar como alguien.
	 *
	 * @param int $uid User ID.
	 * @return void
	 */
	protected function acting_as( int $uid ): void {
		wp_set_current_user( $uid );
	}

	/**
	 * Preparar un POST, con su nonce si la acción lo lleva.
	 *
	 * @param array<string, mixed> $campos       Fields.
	 * @param string               $accion_nonce Nonce action; empty for none.
	 * @param string               $campo_nonce  Field carrying the nonce.
	 * @return void
	 */
	protected function post( array $campos, string $accion_nonce = '', string $campo_nonce = '_wpnonce' ): void {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST                     = $campos;
		if ( '' !== $accion_nonce ) {
			$nonce                    = wp_create_nonce( $accion_nonce );
			$_POST[ $campo_nonce ]    = $nonce;
			$_REQUEST[ $campo_nonce ] = $nonce;
		}
	}

	/**
	 * Ejecutar un handler y devolver por dónde salió.
	 *
	 * La costura de salida: con `evt_exit_throws` puesto,
	 * `Shell::leave()` lanza {@see ExitSignal} con la URL en vez de terminar el
	 * proceso, así que el test puede leer a dónde iba.
	 *
	 * @param callable $handler What to run.
	 * @return string|null La URL de vuelta; '' si sirvió un documento; null si no salió.
	 */
	protected function exit_url( callable $handler ): ?string {
		add_filter( 'evt_exit_throws', '__return_true' );
		try {
			$handler();
		} catch ( ExitSignal $e ) {
			return $e->url;
		}
		return null;
	}

	/**
	 * Ejecutar algo que sirve un documento y devolver lo servido.
	 *
	 * @param callable $handler What to run.
	 * @return string Output until Shell::leave().
	 */
	protected function served( callable $handler ): string {
		add_filter( 'evt_exit_throws', '__return_true' );
		ob_start();
		try {
			$handler();
		} catch ( ExitSignal $e ) {
			unset( $e );
		} finally {
			$cuerpo = (string) ob_get_clean();
		}
		return $cuerpo;
	}

	/**
	 * El valor de un parámetro en la URL de vuelta.
	 *
	 * @param string $url   Return URL.
	 * @param string $clave Query arg.
	 * @return string Empty when absent.
	 */
	protected function query_arg( string $url, string $clave ): string {
		$args = wp_parse_args( (string) wp_parse_url( $url, PHP_URL_QUERY ) );
		return (string) ( $args[ $clave ] ?? '' );
	}

	/**
	 * Que un test no herede la petición del anterior.
	 */
	public function tear_down() {
		$_POST    = array();
		$_GET     = array();
		$_REQUEST = array();
		$_FILES   = array();
		unset( $_SERVER['REQUEST_METHOD'] );
		parent::tear_down();
	}
}
