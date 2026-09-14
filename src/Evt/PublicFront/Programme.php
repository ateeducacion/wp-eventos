<?php
/**
 * Speakers and programme activities of one event: read and write.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

use Evt\Access\EventAccess;
use Evt\Meta\ProgrammeMetaKeys;
use Evt\PostType\ActivityPostType;
use Evt\PostType\SpeakerPostType;

/**
 * Lo que cuelga de un evento y no es una página: quién habla y qué pasa.
 *
 * **Cuelgan por `post_parent`, no por una meta**, y eso es la decisión de esta
 * clase. El propio guardián lo tenía anotado como el camino: «lo mismo valdría
 * para un ponente o una actividad colgados de un evento, si algún día los
 * cuelgan». Colgándolos, el acotado por área
 * ({@see EventAccess::post_areas()}) y el cierre por histórico
 * ({@see EventAccess::is_archived()}) los alcanzan sin una línea más, porque
 * los dos preguntan por `root_id()`.
 *
 * Aquí no se pinta nada y no se lee la petición: eso es de los paneles.
 */
final class Programme {

	/**
	 * Speakers of an event, in the order the área chose.
	 *
	 * @param int  $event_id Event post ID.
	 * @param bool $papelera Whether to list the trash instead.
	 * @return \WP_Post[]
	 */
	public static function speakers( int $event_id, bool $papelera = false ): array {
		return self::children( $event_id, SpeakerPostType::POST_TYPE, $papelera );
	}

	/**
	 * Activities of an event, sorted the way the parrilla reads them.
	 *
	 * El orden sale de tres datos que son metas —día, hora de inicio y hora de
	 * fin—, así que se ordena en PHP y no con un `meta_query`: un evento tiene
	 * decenas de actividades, no miles, y tres `meta_key` distintos en el
	 * `orderby` cuestan tres `JOIN` para ahorrar un `usort` sobre un puñado de
	 * filas. Lo que no tiene día se va al final, junto.
	 *
	 * @param int  $event_id Event post ID.
	 * @param bool $papelera Whether to list the trash instead.
	 * @return \WP_Post[]
	 */
	public static function activities( int $event_id, bool $papelera = false ): array {
		$filas = self::children( $event_id, ActivityPostType::POST_TYPE, $papelera );

		usort(
			$filas,
			static function ( \WP_Post $a, \WP_Post $b ) {
				$clave = static function ( \WP_Post $p ): array {
					$dia = (string) get_post_meta( $p->ID, ProgrammeMetaKeys::ACTIVITY_DATE, true );
					return array(
						'' === $dia ? '9999-99-99' : $dia,
						(string) get_post_meta( $p->ID, ProgrammeMetaKeys::ACTIVITY_START, true ),
						(int) $p->menu_order,
						(int) $p->ID,
					);
				};
				return $clave( $a ) <=> $clave( $b );
			}
		);

		return $filas;
	}

	/**
	 * The activities that take enrolment: the workshops.
	 *
	 * @param int $event_id Event post ID.
	 * @return \WP_Post[]
	 */
	public static function workshops( int $event_id ): array {
		$out = array();
		foreach ( self::activities( $event_id ) as $actividad ) {
			if ( ProgrammeMetaKeys::KIND_WORKSHOP === (string) get_post_meta( $actividad->ID, ProgrammeMetaKeys::ACTIVITY_KIND, true ) ) {
				$out[] = $actividad;
			}
		}
		return $out;
	}

	/**
	 * The parrilla: activities grouped by day and, inside a day, by sede.
	 *
	 * Es la forma que decidió ADR-0024: la sede es un dato de cada actividad y
	 * **un mismo día puede tener dos**. Un día con una sola sede devuelve un
	 * solo bloque, y el panel no le pone rótulo de sede porque no aporta nada;
	 * uno con dos devuelve dos, en el orden en que aparecen.
	 *
	 * @param int $event_id Event post ID.
	 * @return array<int, array{date:string, venues:array<int, array{venue:string, rows:array<int, array<string, mixed>>}>}>
	 */
	public static function grid( int $event_id ): array {
		$dias = array();
		foreach ( self::activities( $event_id ) as $actividad ) {
			$fila  = self::activity_row( $actividad );
			$dia   = (string) $fila['date'];
			$sede  = (string) $fila['venue'];
			$clave = '' === $dia ? '' : $dia;

			if ( ! isset( $dias[ $clave ] ) ) {
				$dias[ $clave ] = array(
					'date'   => $dia,
					'venues' => array(),
				);
			}
			if ( ! isset( $dias[ $clave ]['venues'][ $sede ] ) ) {
				$dias[ $clave ]['venues'][ $sede ] = array(
					'venue' => $sede,
					'rows'  => array(),
				);
			}
			$dias[ $clave ]['venues'][ $sede ]['rows'][] = $fila;
		}

		// Se pierden las claves a propósito: fuera de aquí la parrilla es una
		// lista ordenada de días y cada día una lista ordenada de sedes.
		$out = array();
		foreach ( $dias as $dia ) {
			$dia['venues'] = array_values( $dia['venues'] );
			$out[]         = $dia;
		}
		return $out;
	}

	/**
	 * The distinct sedes of an event, taken from its activities.
	 *
	 * No hay ninguna pantalla donde «gestionar las sedes»: la lista es
	 * derivada, y añadir una sede es escribirla en una actividad (ADR-0024).
	 * Sirve para proponer las ya usadas al teclear la siguiente.
	 *
	 * @param int $event_id Event post ID.
	 * @return string[]
	 */
	public static function venues( int $event_id ): array {
		$out = array();
		foreach ( self::activities( $event_id ) as $actividad ) {
			$sede = trim( (string) get_post_meta( $actividad->ID, ProgrammeMetaKeys::ACTIVITY_VENUE, true ) );
			if ( '' !== $sede && ! in_array( $sede, $out, true ) ) {
				$out[] = $sede;
			}
		}
		sort( $out );
		return $out;
	}

	/**
	 * Create or update a speaker of this event.
	 *
	 * @param int                  $event_id   Event post ID.
	 * @param int                  $speaker_id 0 to create.
	 * @param array<string, mixed> $data       Validated payload.
	 * @return int The speaker post ID, 0 when it could not be written.
	 */
	public static function save_speaker( int $event_id, int $speaker_id, array $data ): int {
		$campos = array(
			'post_type'    => SpeakerPostType::POST_TYPE,
			'post_parent'  => $event_id,
			'post_title'   => (string) $data['name'],
			'post_content' => (string) $data['bio'],
			'post_status'  => 'publish',
		);

		$id = self::write( $speaker_id, $campos, SpeakerPostType::POST_TYPE, $event_id );
		if ( $id <= 0 ) {
			return 0;
		}

		update_post_meta( $id, ProgrammeMetaKeys::SPEAKER_ROLE, (string) $data['role'] );
		update_post_meta( $id, ProgrammeMetaKeys::SPEAKER_ORG, (string) $data['org'] );
		EventAccess::stamp_area( $id );

		return $id;
	}

	/**
	 * Create or update an activity of this event.
	 *
	 * @param int                  $event_id    Event post ID.
	 * @param int                  $activity_id 0 to create.
	 * @param array<string, mixed> $data        Validated payload.
	 * @return int The activity post ID, 0 when it could not be written.
	 */
	public static function save_activity( int $event_id, int $activity_id, array $data ): int {
		$campos = array(
			'post_type'    => ActivityPostType::POST_TYPE,
			'post_parent'  => $event_id,
			'post_title'   => (string) $data['title'],
			'post_content' => (string) $data['summary'],
			'post_status'  => 'publish',
		);

		$id = self::write( $activity_id, $campos, ActivityPostType::POST_TYPE, $event_id );
		if ( $id <= 0 ) {
			return 0;
		}

		update_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_KIND, (string) $data['kind'] );
		update_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_DATE, (string) $data['date'] );
		update_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_START, (string) $data['start'] );
		update_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_END, (string) $data['end'] );
		update_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_VENUE, (string) $data['venue'] );
		update_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_ROOM, (string) $data['room'] );
		update_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_SEATS, (int) $data['seats'] );
		// Solo los ponentes que son de este evento: una ficha de otro evento no
		// se enlaza aquí, que es lo que sostiene ADR-0021.
		update_post_meta(
			$id,
			ProgrammeMetaKeys::ACTIVITY_SPEAKERS,
			implode( ',', self::own_speakers( $event_id, (array) $data['speakers'] ) )
		);
		EventAccess::stamp_area( $id );

		return $id;
	}

	/**
	 * Send a speaker or an activity of this event to the trash.
	 *
	 * @param int $event_id Event post ID.
	 * @param int $post_id  What to trash.
	 * @return bool
	 */
	public static function trash( int $event_id, int $post_id ): bool {
		if ( ! self::belongs( $event_id, $post_id ) ) {
			return false;
		}
		return (bool) wp_trash_post( $post_id );
	}

	/**
	 * Bring one back from the trash.
	 *
	 * @param int $event_id Event post ID.
	 * @param int $post_id  What to restore.
	 * @return bool
	 */
	public static function restore( int $event_id, int $post_id ): bool {
		if ( ! self::belongs( $event_id, $post_id, true ) ) {
			return false;
		}
		return (bool) wp_untrash_post( $post_id );
	}

	/**
	 * Move a speaker one place up or down.
	 *
	 * Se renumera la lista entera por el mismo motivo que en las secciones:
	 * intercambiar dos `menu_order` no mueve nada cuando varias fichas
	 * comparten número, que es lo que pasa en cuanto se crean seguidas.
	 *
	 * @param int $event_id   Event post ID.
	 * @param int $speaker_id Speaker to move.
	 * @param int $delta      -1 up, 1 down.
	 * @return bool
	 */
	public static function reorder_speaker( int $event_id, int $speaker_id, int $delta ): bool {
		$ids   = wp_list_pluck( self::speakers( $event_id ), 'ID' );
		$ids   = array_map( 'intval', $ids );
		$desde = array_search( $speaker_id, $ids, true );
		if ( false === $desde ) {
			return false;
		}
		$hasta = (int) $desde + $delta;
		if ( $hasta < 0 || $hasta >= count( $ids ) ) {
			return false;
		}

		$movido        = $ids[ $desde ];
		$ids[ $desde ] = $ids[ $hasta ];
		$ids[ $hasta ] = $movido;

		foreach ( $ids as $posicion => $id ) {
			wp_update_post(
				array(
					'ID'         => $id,
					'menu_order' => ( $posicion + 1 ) * 10,
				)
			);
		}
		return true;
	}

	/*
	 * -----------------------------------------------------------------------
	 * Los modelos de fila que consumen los paneles
	 * -----------------------------------------------------------------------
	 */

	/**
	 * One speaker, as the panel reads it.
	 *
	 * @param \WP_Post $ponente Speaker post.
	 * @return array<string, mixed>
	 */
	public static function speaker_row( \WP_Post $ponente ): array {
		$id = (int) $ponente->ID;
		return array(
			'id'       => $id,
			'name'     => (string) $ponente->post_title,
			'role'     => (string) get_post_meta( $id, ProgrammeMetaKeys::SPEAKER_ROLE, true ),
			'org'      => (string) get_post_meta( $id, ProgrammeMetaKeys::SPEAKER_ORG, true ),
			'bio'      => (string) $ponente->post_content,
			'photo'    => (string) get_the_post_thumbnail_url( $id, 'thumbnail' ),
			'photo_id' => (int) get_post_thumbnail_id( $id ),
		);
	}

	/**
	 * One activity, as the parrilla and the workshops panel read it.
	 *
	 * @param \WP_Post $actividad Activity post.
	 * @return array<string, mixed>
	 */
	public static function activity_row( \WP_Post $actividad ): array {
		$id       = (int) $actividad->ID;
		$ponentes = array();
		foreach ( self::speaker_ids( $id ) as $speaker_id ) {
			$nombre = (string) get_post_field( 'post_title', $speaker_id );
			if ( '' !== $nombre ) {
				$ponentes[ $speaker_id ] = $nombre;
			}
		}

		return array(
			'id'          => $id,
			'title'       => (string) $actividad->post_title,
			'summary'     => (string) $actividad->post_content,
			'kind'        => (string) get_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_KIND, true ),
			'kind_label'  => ProgrammeMetaKeys::kind_label( (string) get_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_KIND, true ) ),
			'date'        => (string) get_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_DATE, true ),
			'start'       => (string) get_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_START, true ),
			'end'         => (string) get_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_END, true ),
			'venue'       => (string) get_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_VENUE, true ),
			'room'        => (string) get_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_ROOM, true ),
			'seats'       => (int) get_post_meta( $id, ProgrammeMetaKeys::ACTIVITY_SEATS, true ),
			'speaker_ids' => array_keys( $ponentes ),
			'speakers'    => $ponentes,
		);
	}

	/**
	 * The speakers linked to one activity.
	 *
	 * @param int $activity_id Activity post ID.
	 * @return int[]
	 */
	public static function speaker_ids( int $activity_id ): array {
		$bruto = (string) get_post_meta( $activity_id, ProgrammeMetaKeys::ACTIVITY_SPEAKERS, true );
		if ( '' === $bruto ) {
			return array();
		}
		$ids = array();
		foreach ( explode( ',', $bruto ) as $uno ) {
			$id = absint( $uno );
			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}
		return $ids;
	}

	/*
	 * -----------------------------------------------------------------------
	 * Lo de dentro
	 * -----------------------------------------------------------------------
	 */

	/**
	 * The posts of one type that hang from this event.
	 *
	 * @param int    $event_id  Event post ID.
	 * @param string $post_type Post type.
	 * @param bool   $papelera  Whether to list the trash instead.
	 * @return \WP_Post[]
	 */
	private static function children( int $event_id, string $post_type, bool $papelera ): array {
		if ( $event_id <= 0 ) {
			return array();
		}
		$posts = get_posts(
			array(
				'post_type'        => $post_type,
				'post_parent'      => $event_id,
				'post_status'      => $papelera ? 'trash' : array( 'publish', 'draft', 'pending', 'private' ),
				'numberposts'      => -1,
				'orderby'          => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
				'suppress_filters' => false,
			)
		);
		return is_array( $posts ) ? $posts : array();
	}

	/**
	 * Insert or update, never touching a post of another event.
	 *
	 * @param int                  $post_id   0 to create.
	 * @param array<string, mixed> $campos    Post fields.
	 * @param string               $post_type Expected post type.
	 * @param int                  $event_id  Event the post has to hang from.
	 * @return int
	 */
	private static function write( int $post_id, array $campos, string $post_type, int $event_id ): int {
		if ( $post_id > 0 ) {
			if ( ! self::belongs( $event_id, $post_id ) || get_post_type( $post_id ) !== $post_type ) {
				return 0;
			}
			$campos['ID'] = $post_id;
			$hecho        = wp_update_post( $campos, true );
		} else {
			$hecho = wp_insert_post( $campos, true );
		}

		return is_wp_error( $hecho ) ? 0 : (int) $hecho;
	}

	/**
	 * Whether this post really hangs from this event.
	 *
	 * Es la comprobación que impide que un identificador cambiado a mano en el
	 * envío toque el ponente de otro evento: el acotado por área diría que sí
	 * cuando las dos áreas coinciden.
	 *
	 * @param int  $event_id Event post ID.
	 * @param int  $post_id  Post ID.
	 * @param bool $papelera Whether the post is expected to be in the trash.
	 * @return bool
	 */
	private static function belongs( int $event_id, int $post_id, bool $papelera = false ): bool {
		if ( $event_id <= 0 || $post_id <= 0 ) {
			return false;
		}
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return false;
		}
		if ( ! in_array( $post->post_type, array( SpeakerPostType::POST_TYPE, ActivityPostType::POST_TYPE ), true ) ) {
			return false;
		}
		if ( ( 'trash' === $post->post_status ) !== $papelera ) {
			return false;
		}
		return (int) $post->post_parent === $event_id;
	}

	/**
	 * Keep only the speaker IDs that belong to this event.
	 *
	 * @param int   $event_id Event post ID.
	 * @param int[] $ids      Submitted speaker IDs.
	 * @return int[]
	 */
	private static function own_speakers( int $event_id, array $ids ): array {
		$suyos = array_map( 'intval', wp_list_pluck( self::speakers( $event_id ), 'ID' ) );
		$out   = array();
		foreach ( $ids as $id ) {
			$id = (int) $id;
			if ( in_array( $id, $suyos, true ) ) {
				$out[] = $id;
			}
		}
		return $out;
	}
}
