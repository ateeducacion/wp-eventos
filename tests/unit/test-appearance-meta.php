<?php
/**
 * Tests for the appearance meta keys: sanitisation and who may write them.
 *
 * @package Evt
 */

use Evt\Meta\EventMetaKeys;
use Evt\Meta\EventMetaRegistration;
use Evt\PostType\EventPostType;

/**
 * Las claves de apariencia del evento.
 *
 * Sustituyen a los campos de diseño del formulario del sistema anterior que
 * sí se portan: los dos colores, las dos tipografías, la forma de las imágenes
 * y el separador. Donde hoy hay un desplegable con las 1.461 familias de
 * Google Fonts, aquí hay una lista cerrada de seis, y lo que no está en ella
 * no se guarda.
 *
 * El `sanitize_callback` corre dentro de cada `update_post_meta()`, así que da
 * igual por dónde entre el dato —formulario, escritorio, WP-CLI o migración—.
 */
class Test_Appearance_Meta extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Con los tipos registrados y las metas declaradas.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		EventMetaRegistration::register_meta();
	}

	/**
	 * Un evento sobre el que escribir.
	 *
	 * @return int Post ID.
	 */
	private function un_evento(): int {
		return $this->event( $this->administrator(), array( $this->area( 'Innovación' ) ) );
	}

	/**
	 * Todas las claves de apariencia están declaradas, con su tipo y su limpieza.
	 */
	public function test_every_appearance_key_is_registered() {
		$esquema = EventMetaRegistration::schema();

		$claves = array(
			EventMetaKeys::HEADER_BG,
			EventMetaKeys::HEADER_TEXT,
			EventMetaKeys::TITLE_FONT,
			EventMetaKeys::BODY_FONT,
			EventMetaKeys::LOGO_ID,
			EventMetaKeys::HEADER_BANNER_ID,
			EventMetaKeys::POSTER_ID,
			EventMetaKeys::IMAGE_SHAPE,
			EventMetaKeys::SEPARATOR,
		);
		foreach ( $claves as $clave ) {
			$this->assertArrayHasKey( $clave, $esquema, $clave );
			$this->assertIsCallable( $esquema[ $clave ]['sanitize'], $clave );
			$this->assertContains( $clave, EventMetaKeys::all(), $clave );
			$this->assertTrue( registered_meta_key_exists( 'post', $clave, EventPostType::POST_TYPE ), $clave );
		}
	}

	// ─── colores ───────────────────────────────────────────────────────────

	/**
	 * Un color que no es un color no se guarda.
	 */
	public function test_an_invalid_colour_is_not_stored() {
		$evento = $this->un_evento();

		foreach ( array( 'rojo bombero', '#12345', 'ffffff', '#ggghhh', '<script>', '' ) as $malo ) {
			update_post_meta( $evento, EventMetaKeys::HEADER_BG, $malo );
			$this->assertSame( '', get_post_meta( $evento, EventMetaKeys::HEADER_BG, true ), $malo );
		}

		update_post_meta( $evento, EventMetaKeys::HEADER_BG, '  #0A3D62  ' );
		$this->assertSame( '#0A3D62', get_post_meta( $evento, EventMetaKeys::HEADER_BG, true ) );

		update_post_meta( $evento, EventMetaKeys::HEADER_TEXT, '#fff' );
		$this->assertSame( '#fff', get_post_meta( $evento, EventMetaKeys::HEADER_TEXT, true ), 'la forma corta también es un color' );

		// Y un color válido guardado antes se borra al mandar uno que no vale.
		update_post_meta( $evento, EventMetaKeys::HEADER_TEXT, 'azul' );
		$this->assertSame( '', get_post_meta( $evento, EventMetaKeys::HEADER_TEXT, true ) );
	}

	// ─── tipografías ───────────────────────────────────────────────────────

	/**
	 * Una tipografía fuera de la lista cerrada tampoco se guarda.
	 */
	public function test_a_typeface_outside_the_list_is_not_stored() {
		$evento = $this->un_evento();

		$this->assertSame(
			array( EventMetaKeys::FONT_DEFAULT, 'open-sans', 'lato', 'montserrat', 'source-serif', 'merriweather' ),
			array_keys( EventMetaKeys::fonts() ),
			'seis, y una de ellas es «la del tema»'
		);

		foreach ( array( 'lato', 'montserrat', 'merriweather' ) as $buena ) {
			update_post_meta( $evento, EventMetaKeys::TITLE_FONT, $buena );
			$this->assertSame( $buena, get_post_meta( $evento, EventMetaKeys::TITLE_FONT, true ) );
		}

		foreach ( array( 'comic-sans', 'Lato', 'Open Sans', 'papyrus' ) as $mala ) {
			update_post_meta( $evento, EventMetaKeys::BODY_FONT, $mala );
			$this->assertSame( EventMetaKeys::FONT_DEFAULT, get_post_meta( $evento, EventMetaKeys::BODY_FONT, true ), $mala );
		}

		// Y una que estaba puesta se cae al mandar una que no existe.
		update_post_meta( $evento, EventMetaKeys::TITLE_FONT, 'papyrus' );
		$this->assertSame( EventMetaKeys::FONT_DEFAULT, get_post_meta( $evento, EventMetaKeys::TITLE_FONT, true ) );
	}

	// ─── formas y separadores ──────────────────────────────────────────────

	/**
	 * La forma de las imágenes es cuadrada o redonda; nada más.
	 */
	public function test_the_image_shape_is_square_or_circle() {
		$evento = $this->un_evento();

		update_post_meta( $evento, EventMetaKeys::IMAGE_SHAPE, EventMetaKeys::SHAPE_CIRCLE );
		$this->assertSame( EventMetaKeys::SHAPE_CIRCLE, get_post_meta( $evento, EventMetaKeys::IMAGE_SHAPE, true ) );

		update_post_meta( $evento, EventMetaKeys::IMAGE_SHAPE, 'hexagonal' );
		$this->assertSame(
			EventMetaKeys::SHAPE_SQUARE,
			get_post_meta( $evento, EventMetaKeys::IMAGE_SHAPE, true ),
			'lo que no es una forma nuestra cae en la de por defecto'
		);
	}

	/**
	 * Un separador de fuera de las seis siluetas se queda sin separador.
	 */
	public function test_an_unknown_separator_leaves_none() {
		$evento = $this->un_evento();

		update_post_meta( $evento, EventMetaKeys::SEPARATOR, 'wave' );
		$this->assertSame( 'wave', get_post_meta( $evento, EventMetaKeys::SEPARATOR, true ) );

		// De las veinticinco siluetas del tema solo se conservan seis.
		update_post_meta( $evento, EventMetaKeys::SEPARATOR, 'graph3' );
		$this->assertSame( '', get_post_meta( $evento, EventMetaKeys::SEPARATOR, true ) );
	}

	/**
	 * Los identificadores de las imágenes son enteros positivos, o cero.
	 */
	public function test_the_image_ids_are_positive_integers() {
		$evento = $this->un_evento();

		update_post_meta( $evento, EventMetaKeys::LOGO_ID, '42' );
		$this->assertSame( 42, (int) get_post_meta( $evento, EventMetaKeys::LOGO_ID, true ) );

		foreach ( array( '-3', 'x', '' ) as $malo ) {
			update_post_meta( $evento, EventMetaKeys::POSTER_ID, $malo );
			$this->assertSame( 0, (int) get_post_meta( $evento, EventMetaKeys::POSTER_ID, true ), $malo );
		}
	}

	// ─── los limpiadores, uno a uno ────────────────────────────────────────

	/**
	 * Los limpiadores, llamados directamente: la misma respuesta sin base de datos.
	 */
	public function test_the_sanitisers_on_their_own() {
		$this->assertSame( '', EventMetaRegistration::sanitize_color( 'rojo' ) );
		$this->assertSame( '#0a3d62', EventMetaRegistration::sanitize_color( ' #0a3d62 ' ) );

		$this->assertSame( '', EventMetaRegistration::sanitize_font( 'comic-sans' ) );
		$this->assertSame( 'lato', EventMetaRegistration::sanitize_font( 'lato' ) );

		$this->assertSame( EventMetaKeys::SHAPE_SQUARE, EventMetaRegistration::sanitize_image_shape( null ) );
		$this->assertSame( '', EventMetaRegistration::sanitize_separator( 'zigzag' ) );

		$this->assertSame( 0, EventMetaRegistration::sanitize_id( '-1' ) );
		$this->assertSame( 7, EventMetaRegistration::sanitize_id( '7' ) );

		// El sistema anterior ya pide «no ponga el símbolo #» y la gente lo pone igual.
		$this->assertSame( 'EVT2026', EventMetaRegistration::sanitize_hashtag( ' #EVT 2026 ' ) );

		$this->assertSame( '', EventMetaRegistration::sanitize_url( 'javascript:alert(1)' ) );
		$this->assertSame( 'https://example.org/a', EventMetaRegistration::sanitize_url( 'https://example.org/a' ) );

		$this->assertSame( '2026-03-10', EventMetaRegistration::sanitize_date( ' 2026-03-10 ' ) );
		$this->assertSame( '', EventMetaRegistration::sanitize_date( '2026-02-30' ), 'no es una fecha del calendario' );
		$this->assertSame( '', EventMetaRegistration::sanitize_date( '10/03/2026' ), 'ni tiene la forma que se guarda' );

		$this->assertTrue( EventMetaRegistration::sanitize_bool( 'sí' ) );
		$this->assertFalse( EventMetaRegistration::sanitize_bool( '0' ) );

		$this->assertSame( 'programa', EventMetaRegistration::sanitize_section_type( 'programa' ) );
		$this->assertSame( '', EventMetaRegistration::sanitize_section_type( '' ), 'la raíz del evento no es una sección' );
		$this->assertSame(
			EventMetaKeys::SECTION_OTHER,
			EventMetaRegistration::sanitize_section_type( 'lo-que-sea' ),
			'un tipo desconocido de la migración cae en «otra», no se pierde'
		);
	}

	// ─── quién escribe ─────────────────────────────────────────────────────

	/**
	 * Quien no puede editar el evento no escribe sus metas.
	 */
	public function test_whoever_cannot_edit_does_not_write_the_meta() {
		$mia    = $this->area( 'Formación del Profesorado' );
		$otra   = $this->area( 'Innovación' );
		$dueno  = $this->organiser( array( $mia ) );
		$ajena  = $this->organiser( array( $otra ) );
		$nadie  = (int) self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$evento = $this->event( $dueno, array( $mia ) );

		foreach ( array( EventMetaKeys::HEADER_BG, EventMetaKeys::TITLE_FONT, EventMetaKeys::SEPARATOR ) as $clave ) {
			$this->assertTrue(
				EventMetaRegistration::auth_edit_event( false, $clave, $evento, $dueno ),
				$clave
			);
			$this->assertFalse(
				EventMetaRegistration::auth_edit_event( true, $clave, $evento, $ajena ),
				$clave . ' de otra área'
			);
			$this->assertFalse( EventMetaRegistration::auth_edit_event( true, $clave, $evento, $nadie ), $clave );

			// Y la capacidad que de verdad cierra la puerta a la REST y a la
			// edición rápida dice lo mismo.
			$this->assertTrue( user_can( $dueno, 'edit_post_meta', $evento, $clave ), $clave );
			$this->assertFalse( user_can( $ajena, 'edit_post_meta', $evento, $clave ), $clave . ' de otra área' );
			$this->assertFalse( user_can( $nadie, 'edit_post_meta', $evento, $clave ), $clave );
		}
	}

	/**
	 * Una página satélite se rige por el área de su evento también para las metas.
	 */
	public function test_a_satellite_page_follows_the_area_of_its_event() {
		$mia     = $this->area( 'Formación del Profesorado' );
		$otra    = $this->area( 'Innovación' );
		$dueno   = $this->organiser( array( $mia ) );
		$ajena   = $this->organiser( array( $otra ) );
		$evento  = $this->event( $dueno, array( $mia ) );
		$seccion = $this->event_page( $evento, 'programa' );

		$this->assertTrue( user_can( $dueno, 'edit_post_meta', $seccion, EventMetaKeys::HEADER_BG ) );
		$this->assertFalse( user_can( $ajena, 'edit_post_meta', $seccion, EventMetaKeys::HEADER_BG ) );
	}

	/**
	 * Las metas no viajan a la REST: aquí no hay editor de bloques que las pida.
	 */
	public function test_the_meta_is_not_exposed_in_the_rest_api() {
		$registradas = get_registered_meta_keys( 'post', EventPostType::POST_TYPE );

		foreach ( EventMetaKeys::all() as $clave ) {
			$this->assertArrayHasKey( $clave, $registradas, $clave );
			$this->assertFalse( $registradas[ $clave ]['show_in_rest'], $clave );
			$this->assertTrue( $registradas[ $clave ]['single'], $clave );
		}
	}

	/**
	 * El arranque declara las metas después de que el tipo exista.
	 */
	public function test_register_declares_the_meta_after_the_post_type() {
		EventMetaRegistration::register();

		$this->assertSame( 12, has_action( 'init', array( EventMetaRegistration::class, 'register_meta' ) ) );
	}
}
