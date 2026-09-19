<?php
/**
 * The people signed up to one event: read, filter and export.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

/**
 * Quién se ha inscrito a un evento, para verlo, filtrarlo y exportarlo.
 *
 * **Los participantes son de este aplicativo.** Se gestionarán aquí, con
 * formulario de inscripción propio; el sistema anterior no se lee y no se le
 * construye ningún puente (ADR-0027).
 *
 * Esta clase declara la **forma de una fila** y pregunta por ellas con el
 * filtro `evt_participants`. Quien contesta de serie es el propio aplicativo
 * con sus `evt_registration` ({@see Registrations::participants()}, ADR-0032);
 * la costura se queda puesta porque es lo que permite a un despliegue traer sus
 * participantes de otro sitio desde un snippet, y lo que deja probar el filtro
 * y el CSV sin ningún almacén detrás. Sin nadie que conteste, la lista está
 * vacía y el panel dice dónde se abre la inscripción, en vez de fingir que
 * nadie se ha apuntado.
 *
 * Lo que sí es de aquí, y es lo que se pidió: **el filtro y la exportación a
 * CSV**, puros y probados sin WordPress.
 */
final class Participants {

	/**
	 * Filter every source answers to fill the list of one event.
	 */
	public const HOOK = 'evt_participants';

	/**
	 * Key a row may carry with its private documents, for the screen only.
	 *
	 * **No es una columna.** Es lo que la pantalla necesita para pintar un
	 * botón de descarga, y por eso no sale ni en el CSV ni en el filtro: ahí
	 * va el nombre del documento, en la columna `files`, y nunca una ruta ni
	 * una dirección (ADR-0036). Dentro hay `{reg, id, name}` por documento, y
	 * de ahí sale la URL del manejador del aplicativo, que la compone
	 * {@see RegistrationFiles::url()} al pintar.
	 */
	public const KEY_FILES = '_files';

	/**
	 * The columns of the table, in order, with their heading.
	 *
	 * Es el contrato con quien conteste al filtro: una fila es este array.
	 * Lo que traiga de más se ignora; lo que falte sale vacío.
	 *
	 * @return array<string, string> clave => rótulo.
	 */
	public static function columns(): array {
		return array(
			'name'     => 'Nombre',
			'email'    => 'Correo',
			'centre'   => 'Centro',
			'workshop' => 'Taller',
			'date'     => 'Fecha de inscripción',
			'consent'  => 'Consentimiento',
			'files'    => 'Documentos',
		);
	}

	/**
	 * The participants of one event, as whoever answers the filter has them.
	 *
	 * @param int $event_id Event post ID.
	 * @return array<int, array<string, string>>
	 */
	public static function rows( int $event_id ): array {
		if ( $event_id <= 0 ) {
			return array();
		}

		/**
		 * Filter the participants of one event.
		 *
		 * @param array<int, array<string, string>> $rows     Rows so far.
		 * @param int                               $event_id Event post ID.
		 */
		$filas = apply_filters( self::HOOK, array(), $event_id );

		return self::clean( is_array( $filas ) ? $filas : array() );
	}

	/**
	 * Keep only the declared columns, as strings.
	 *
	 * Lo que llega del filtro es de fuera: se normaliza antes de que lo vea
	 * nadie, para que el panel y el CSV trabajen siempre con la misma forma.
	 *
	 * @param array<int, mixed> $filas Raw rows.
	 * @return array<int, array<string, string>>
	 */
	public static function clean( array $filas ): array {
		$columnas = array_keys( self::columns() );
		$out      = array();
		foreach ( $filas as $fila ) {
			if ( ! is_array( $fila ) ) {
				continue;
			}
			$limpia = array();
			foreach ( $columnas as $clave ) {
				$valor            = $fila[ $clave ] ?? '';
				$limpia[ $clave ] = is_scalar( $valor ) ? trim( (string) $valor ) : '';
			}
			// Y lo único que no es columna: los documentos, para el botón de
			// descarga. Se copia tal cual y no lo mira nadie más que la
			// pantalla.
			if ( isset( $fila[ self::KEY_FILES ] ) && is_array( $fila[ self::KEY_FILES ] ) ) {
				$limpia[ self::KEY_FILES ] = array_values( $fila[ self::KEY_FILES ] );
			}
			$out[] = $limpia;
		}
		return $out;
	}

	/**
	 * The rows that match what was typed in the filter box.
	 *
	 * Pura y sin WordPress. Busca en todas las columnas a la vez porque es lo
	 * que se hace de verdad con una lista de inscripciones: se teclea un
	 * apellido, o un centro, o un taller, y se espera que salga.
	 *
	 * @param array<int, array<string, string>> $filas  Rows.
	 * @param string                            $texto  What was typed.
	 * @param string                            $taller Workshop to narrow to, '' for all.
	 * @return array<int, array<string, string>>
	 */
	public static function filter( array $filas, string $texto = '', string $taller = '' ): array {
		$texto  = trim( $texto );
		$taller = trim( $taller );
		if ( '' === $texto && '' === $taller ) {
			return $filas;
		}

		$buscado = self::fold( $texto );
		$out     = array();
		foreach ( $filas as $fila ) {
			if ( '' !== $taller && ( $fila['workshop'] ?? '' ) !== $taller ) {
				continue;
			}
			// Se busca en las columnas declaradas y en ninguna otra clave: lo
			// que se pinta es lo que se filtra.
			$texto_fila = '';
			foreach ( array_keys( self::columns() ) as $clave ) {
				$texto_fila .= ( $fila[ $clave ] ?? '' ) . ' ';
			}
			if ( '' !== $buscado && false === strpos( self::fold( $texto_fila ), $buscado ) ) {
				continue;
			}
			$out[] = $fila;
		}
		return $out;
	}

	/**
	 * The distinct workshops the rows mention, to fill the narrowing select.
	 *
	 * @param array<int, array<string, string>> $filas Rows.
	 * @return string[]
	 */
	public static function workshops( array $filas ): array {
		$out = array();
		foreach ( $filas as $fila ) {
			$taller = trim( (string) ( $fila['workshop'] ?? '' ) );
			if ( '' !== $taller && ! in_array( $taller, $out, true ) ) {
				$out[] = $taller;
			}
		}
		sort( $out );
		return $out;
	}

	/**
	 * The rows as a CSV file, headings included.
	 *
	 * Separador **punto y coma** y BOM de UTF-8 al principio, que es lo que
	 * abre bien en el Excel en español sin pasar por el asistente de
	 * importación: con coma y sin BOM, un nombre con tilde sale roto y todo
	 * cae en la primera columna. Cada campo va entrecomillado y las comillas
	 * de dentro se duplican, que es lo que dice RFC 4180.
	 *
	 * Lo que empiece por `=`, `+`, `-` o `@` se escapa con una comilla simple
	 * delante: sin eso, una hoja de cálculo trata ese texto como fórmula y un
	 * campo copiado de un formulario se convierte en ejecución.
	 *
	 * @param array<int, array<string, string>> $filas Rows.
	 * @return string
	 */
	public static function csv( array $filas ): string {
		$columnas = self::columns();
		$lineas   = array( self::csv_line( array_values( $columnas ) ) );

		foreach ( $filas as $fila ) {
			$campos = array();
			foreach ( array_keys( $columnas ) as $clave ) {
				$campos[] = (string) ( $fila[ $clave ] ?? '' );
			}
			$lineas[] = self::csv_line( $campos );
		}

		// CRLF y BOM: los dos son para que el fichero se abra donde se va a
		// abrir, que es una hoja de cálculo de escritorio y no un editor.
		return "\xEF\xBB\xBF" . implode( "\r\n", $lineas ) . "\r\n";
	}

	/**
	 * The file name an export gets.
	 *
	 * @param string $titulo Event title.
	 * @return string
	 */
	public static function filename( string $titulo ): string {
		$base = sanitize_title( $titulo );
		if ( '' === $base ) {
			$base = 'evento';
		}
		return 'participantes-' . $base . '-' . gmdate( 'Y-m-d' ) . '.csv';
	}

	/**
	 * One CSV line, quoted and escaped.
	 *
	 * @param string[] $campos Fields.
	 * @return string
	 */
	private static function csv_line( array $campos ): string {
		$fuera = array();
		foreach ( $campos as $campo ) {
			$fuera[] = '"' . str_replace( '"', '""', self::defuse( $campo ) ) . '"';
		}
		return implode( ';', $fuera );
	}

	/**
	 * Stop a spreadsheet from reading a field as a formula.
	 *
	 * @param string $campo Field.
	 * @return string
	 */
	private static function defuse( string $campo ): string {
		if ( '' === $campo ) {
			return '';
		}
		return false === strpos( "=+-@\t\r", $campo[0] ) ? $campo : "'" . $campo;
	}

	/**
	 * Lowercase and unaccented, so «Martín» matches «martin».
	 *
	 * @param string $texto Text.
	 * @return string
	 */
	private static function fold( string $texto ): string {
		$texto = strtr(
			$texto,
			array(
				'á' => 'a',
				'é' => 'e',
				'í' => 'i',
				'ó' => 'o',
				'ú' => 'u',
				'ü' => 'u',
				'ñ' => 'n',
				'Á' => 'a',
				'É' => 'e',
				'Í' => 'i',
				'Ó' => 'o',
				'Ú' => 'u',
				'Ü' => 'u',
				'Ñ' => 'n',
			)
		);
		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $texto, 'UTF-8' ) : strtolower( $texto );
	}
}
