<?php
/**
 * Derived state of an event.
 *
 * @package Evt
 */

namespace Evt\Domain;

use Evt\Meta\EventMetaKeys;

/**
 * Deriva el estado del evento de sus fechas.
 *
 * Hoy en producción el estado es un término de la taxonomía `convocatoria`
 * que alguien marca a mano al crear el evento, y que nadie vuelve a tocar: de
 * 50 términos, 32 páginas están en «evento-finalizado» y 2 en «evento-abierto»
 * porque el resto se quedó sin actualizar. Un dato que se puede calcular no se
 * guarda.
 *
 * Pura: ni una llamada a WordPress.
 */
final class EventState {

	/**
	 * State of an event given its dates.
	 *
	 * @param string $start First day, Y-m-d ('' when unknown).
	 * @param string $end   Last day, Y-m-d ('' = same as the first).
	 * @param string $today Reference day, Y-m-d ('' = today, UTC).
	 * @return string One of EventMetaKeys::states().
	 */
	public static function of( string $start, string $end, string $today = '' ): string {
		$start = trim( $start );
		$end   = trim( $end );
		$today = '' !== trim( $today ) ? trim( $today ) : gmdate( 'Y-m-d' );

		// Un evento que todavía no tiene fechas se está preparando.
		if ( '' === $start ) {
			return EventMetaKeys::STATE_UPCOMING;
		}
		if ( '' === $end || $end < $start ) {
			$end = $start;
		}
		if ( $today < $start ) {
			return EventMetaKeys::STATE_UPCOMING;
		}
		if ( $today > $end ) {
			return EventMetaKeys::STATE_FINISHED;
		}
		return EventMetaKeys::STATE_OPEN;
	}

	/**
	 * Human label for a state.
	 *
	 * @param string $state State slug.
	 * @return string Empty when the slug is not one of ours.
	 */
	public static function label( string $state ): string {
		return (string) ( EventMetaKeys::states()[ $state ] ?? '' );
	}
}
