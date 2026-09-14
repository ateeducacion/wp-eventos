<?php
/**
 * Meta keys of what hangs from an event: speakers and programme activities.
 *
 * @package Evt
 */

namespace Evt\Meta;

/**
 * Las claves de meta de `evt_speaker` y `evt_activity`, y sus listas cerradas.
 *
 * Están aparte de {@see EventMetaKeys} porque son de otros dos tipos de
 * contenido: `all()` de aquella significa «las metas del evento» y mezclarlas
 * volvería esa lista una mentira.
 *
 * **Ni el ponente ni la actividad guardan a qué evento pertenecen en una
 * meta**: cuelgan del evento por `post_parent`, que es lo que hace que el
 * acotado por área y el cierre por histórico les alcancen sin una línea de
 * código más ({@see \Evt\Access\EventAccess::root_id()}).
 */
final class ProgrammeMetaKeys {

	/**
	 * What the speaker does: «Asesora de formación», «Catedrático de Secundaria».
	 */
	public const SPEAKER_ROLE = 'evt_speaker_role';

	/**
	 * Where the speaker comes from: centro, universidad, empresa.
	 */
	public const SPEAKER_ORG = 'evt_speaker_org';

	/**
	 * What kind of slot this is: ponencia, taller, mesa redonda…
	 */
	public const ACTIVITY_KIND = 'evt_activity_kind';

	/**
	 * Day of the activity, `Y-m-d`.
	 */
	public const ACTIVITY_DATE = 'evt_activity_date';

	/**
	 * Start time, `H:i`.
	 */
	public const ACTIVITY_START = 'evt_activity_start';

	/**
	 * End time, `H:i`. Empty means «hasta la siguiente».
	 */
	public const ACTIVITY_END = 'evt_activity_end';

	/**
	 * Sede of this activity.
	 *
	 * Es de la actividad y no del día: un mismo día puede tener dos sedes, y
	 * la parrilla agrupa por día y, dentro, por sede (ADR-0024). No hay
	 * ninguna pantalla donde «dar de alta una sede»: la lista de sedes de un
	 * evento sale de sus actividades.
	 */
	public const ACTIVITY_VENUE = 'evt_activity_venue';

	/**
	 * Room inside the sede: «Aula 2», «Salón de actos».
	 */
	public const ACTIVITY_ROOM = 'evt_activity_room';

	/**
	 * Seats of a workshop; 0 means «sin límite».
	 */
	public const ACTIVITY_SEATS = 'evt_activity_seats';

	/**
	 * Speakers of this activity: post IDs of `evt_speaker`, comma separated.
	 *
	 * Una sola meta y no una por ponente: se lee y se escribe entera, nunca
	 * se consulta por ella, y `register_post_meta()` con `single => true` es
	 * lo que deja la escritura pasando por un solo saneado.
	 */
	public const ACTIVITY_SPEAKERS = 'evt_activity_speakers';

	/**
	 * The kind that takes seats and enrolment.
	 */
	public const KIND_WORKSHOP = 'taller';

	/**
	 * All meta keys stored on a speaker.
	 *
	 * @return string[]
	 */
	public static function speaker_keys(): array {
		return array( self::SPEAKER_ROLE, self::SPEAKER_ORG );
	}

	/**
	 * All meta keys stored on an activity.
	 *
	 * @return string[]
	 */
	public static function activity_keys(): array {
		return array(
			self::ACTIVITY_KIND,
			self::ACTIVITY_DATE,
			self::ACTIVITY_START,
			self::ACTIVITY_END,
			self::ACTIVITY_VENUE,
			self::ACTIVITY_ROOM,
			self::ACTIVITY_SEATS,
			self::ACTIVITY_SPEAKERS,
		);
	}

	/**
	 * Closed vocabulary of activity kinds.
	 *
	 * En código y no en taxonomía, por la misma lección que los tipos de
	 * sección: una lista abierta acaba mezclando ejes (ADR-0004).
	 *
	 * @return array<string, string> slug => etiqueta.
	 */
	public static function activity_kinds(): array {
		return array(
			'ponencia'     => 'Ponencia',
			'taller'       => 'Taller',
			'mesa'         => 'Mesa redonda',
			'comunicacion' => 'Comunicación',
			'panel'        => 'Panel de experiencias',
			'inauguracion' => 'Inauguración',
			'clausura'     => 'Clausura',
			'descanso'     => 'Descanso',
			'otra'         => 'Otra',
		);
	}

	/**
	 * The label of one kind, or the fallback one.
	 *
	 * @param string $kind Kind slug.
	 * @return string
	 */
	public static function kind_label( string $kind ): string {
		$lista = self::activity_kinds();
		return $lista[ $kind ] ?? $lista['otra'];
	}
}
