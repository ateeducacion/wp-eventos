<?php
/**
 * Pure validation for event form input.
 *
 * @package Evt
 */

namespace Evt\Domain;

use Evt\Meta\EventMetaKeys;

/**
 * Validates and normalises the raw fields of an event or of one of its pages.
 *
 * Pura: ni una llamada a WordPress, para que se pueda probar sin cargarlo.
 */
final class EventInput {

	/**
	 * Validate a submitted event payload.
	 *
	 * Una página satélite es el mismo tipo de contenido con `parent` y
	 * `section_type`: por eso valida las dos cosas el mismo método.
	 *
	 * @param array<string, mixed> $raw Raw fields.
	 * @return array{ok:bool, errors:string[], data:array<string, mixed>}
	 */
	public static function validate( array $raw ): array {
		$errors = array();

		$title   = isset( $raw['title'] ) ? trim( (string) $raw['title'] ) : '';
		$start   = isset( $raw['start_date'] ) ? trim( (string) $raw['start_date'] ) : '';
		$end     = isset( $raw['end_date'] ) ? trim( (string) $raw['end_date'] ) : '';
		$venue   = isset( $raw['venue'] ) ? trim( (string) $raw['venue'] ) : '';
		$section = isset( $raw['section_type'] ) ? trim( (string) $raw['section_type'] ) : '';
		$parent  = isset( $raw['parent'] ) ? (int) $raw['parent'] : 0;

		if ( '' === $title ) {
			$errors[] = 'title';
		}

		// La raíz del evento necesita fechas; una página satélite las hereda del
		// evento y no las repite.
		$is_root = $parent <= 0;

		if ( $is_root && ! self::is_valid_date( $start ) ) {
			$errors[] = 'start_date';
		}
		if ( '' !== $end && ! self::is_valid_date( $end ) ) {
			$errors[] = 'end_date';
		}
		if ( self::is_valid_date( $start ) && self::is_valid_date( $end ) && $end < $start ) {
			$errors[] = 'date_order';
		}

		if ( '' !== $section && ! isset( EventMetaKeys::section_types()[ $section ] ) ) {
			$errors[] = 'section_type';
		}
		if ( ! $is_root && '' === $section ) {
			$errors[] = 'section_type';
		}

		return array(
			'ok'     => array() === $errors,
			'errors' => $errors,
			'data'   => array(
				'title'        => $title,
				'start_date'   => self::is_valid_date( $start ) ? $start : '',
				'end_date'     => self::is_valid_date( $end ) ? $end : '',
				'venue'        => $venue,
				'section_type' => $is_root ? '' : $section,
				'parent'       => max( 0, $parent ),
			),
		);
	}

	/**
	 * Whether the string is a calendar date in Y-m-d form.
	 *
	 * @param string $date Date string.
	 * @return bool
	 */
	private static function is_valid_date( string $date ): bool {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return false;
		}
		$parts = array_map( 'intval', explode( '-', $date ) );
		return checkdate( $parts[1], $parts[2], $parts[0] );
	}
}
