<?php
/**
 * Rendering smoke tests for the views of the application.
 *
 * @package Evt
 */

use Evt\Meta\EventMetaKeys;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\PageForm;
use Evt\PublicFront\View\EventAppearancePanel;
use Evt\PublicFront\View\EventChrome;
use Evt\PublicFront\View\EventDataPanel;
use Evt\PublicFront\View\EventSectionsPanel;
use Evt\PublicFront\View\EventWorkspaceView;
use Evt\PublicFront\View\PageFormView;

/**
 * Que cada pantalla se pinte de verdad.
 *
 * Los tests de las pantallas comprueban lo que decide `model()`; estos
 * comprueban lo que sale por la otra punta. Una constante mal escrita o una
 * clave que ya no está en el modelo son un error fatal que solo aparece al
 * pintar, y sin esto no lo veía nadie hasta abrir la página en el navegador.
 */
class Test_Views extends WP_UnitTestCase {

	use Evt_Fixtures;

	/**
	 * Con las páginas del aplicativo ya creadas.
	 */
	public function set_up() {
		parent::set_up();
		$this->pages();
	}

	/**
	 * An event with its appearance and one satellite page, opened in a panel.
	 *
	 * @param string $panel Panel key.
	 * @return array<string, mixed> The model of the workshop.
	 */
	private function taller( string $panel ): array {
		$area   = $this->area( 'Innovación' );
		$autor  = $this->administrator();
		$evento = $this->event(
			$autor,
			array( $area ),
			array(
				EventMetaKeys::START_DATE  => '2026-03-10',
				EventMetaKeys::END_DATE    => '2026-03-12',
				EventMetaKeys::TAGLINE     => 'Enseñar de otra manera',
				EventMetaKeys::HEADER_BG   => '#0a3d62',
				EventMetaKeys::HEADER_TEXT => '#ffffff',
				EventMetaKeys::TITLE_FONT  => 'montserrat',
				EventMetaKeys::SEPARATOR   => 'wave',
			)
		);
		$this->event_page( $evento, 'programa' );

		$this->acting_as( $autor );
		$_GET[ EventWorkspace::ARG_EVENT ] = (string) $evento;
		$_GET[ EventWorkspace::ARG_PANEL ] = $panel;

		return EventWorkspace::model();
	}

	/**
	 * El panel de secciones pinta la tabla y sus botones de mutación.
	 */
	public function test_the_sections_panel_paints_its_table() {
		$m    = $this->taller( EventWorkspace::PANEL_SECTIONS );
		$html = EventWorkspaceView::html( $m );

		$this->assertStringContainsString( 'Añadir sección', $html );
		$this->assertStringContainsString( 'Enviar a la papelera', $html );
		$this->assertStringContainsString( 'method="post"', $html );
		$this->assertStringContainsString( 'evt_nonce_delete_', $html, 'cada fila lleva su nonce' );
		$this->assertStringContainsString( 'Programa', $html );
		$this->assertStringContainsString( 'Añadir sección', EventSectionsPanel::html( $m ) );
	}

	/**
	 * La papelera del panel de secciones: el enlace, la tabla y «Restaurar».
	 */
	public function test_the_sections_panel_paints_its_trash() {
		$area    = $this->area( 'Innovación' );
		$autor   = $this->administrator();
		$evento  = $this->event( $autor, array( $area ) );
		$seccion = $this->event_page( $evento, 'programa', array( 'post_title' => 'Programa del jueves' ) );
		wp_trash_post( $seccion );

		$this->acting_as( $autor );
		$_GET[ EventWorkspace::ARG_EVENT ] = (string) $evento;
		$_GET[ EventWorkspace::ARG_PANEL ] = EventWorkspace::PANEL_SECTIONS;

		// Desde el listado normal: el enlace con la cifra, y ni rastro de la fila.
		$html = EventSectionsPanel::html( EventWorkspace::model() );
		$this->assertStringContainsString( 'Papelera (1)', $html );
		$this->assertStringNotContainsString( 'Programa del jueves', $html );

		// Y dentro de la papelera: la fila, su botón y su nonce.
		$_GET[ EventWorkspace::ARG_TRASH ] = '1';
		$html                              = EventSectionsPanel::html( EventWorkspace::model() );
		$this->assertStringContainsString( 'Programa del jueves', $html );
		$this->assertStringContainsString( 'Restaurar', $html );
		$this->assertStringContainsString( EventWorkspace::nonce_name( 'restore', $seccion ), $html );
		$this->assertStringContainsString( 'escritorio de WordPress', $html );
		$this->assertStringNotContainsString( 'Añadir sección', $html, 'en la papelera no se añade nada' );
	}

	/**
	 * El panel de datos pinta las cuatro tarjetas con lo guardado.
	 */
	public function test_the_data_panel_paints_what_is_stored() {
		$m    = $this->taller( EventWorkspace::PANEL_SETTINGS );
		$html = EventDataPanel::html( $m );

		foreach ( array( 'Identidad', 'Cuándo y dónde', 'Clasificación', 'Inscripción' ) as $tarjeta ) {
			$this->assertStringContainsString( $tarjeta, $html );
		}
		$this->assertStringContainsString( 'Enseñar de otra manera', $html );
		$this->assertStringContainsString( '2026-03-10', $html );
		$this->assertStringContainsString( 'Guardar los datos', $html );
	}

	/** A shared event names foreign organisers without editable controls. */
	public function test_data_panel_shows_foreign_organiser_read_only() {
		$mine    = $this->area( 'Ámbito 1' );
		$foreign = $this->area( 'Ámbito 2' );
		$editor  = (int) self::factory()->user->create( array( 'role' => 'editor' ) );
		update_user_meta( $editor, 'evt_area', array( $mine ) );
		$event = $this->event( $editor, array( $mine, $foreign ) );
		$this->acting_as( $editor );
		$_GET[ EventWorkspace::ARG_EVENT ] = (string) $event;
		$_GET[ EventWorkspace::ARG_PANEL ] = EventWorkspace::PANEL_SETTINGS;
		$html                              = EventDataPanel::html( EventWorkspace::model() );
		$this->assertStringContainsString( 'Otros ámbitos organizadores (solo lectura)', $html );
		$this->assertStringContainsString( 'Ámbito 2', $html );
		$this->assertStringContainsString( 'value="' . $mine . '"', $html );
		$this->assertStringNotContainsString( 'value="' . $foreign . '"', $html );
	}

	/**
	 * El panel de apariencia pinta la vista previa con la silueta elegida.
	 *
	 * Y la pinta con la MISMA silueta que la página pública: si tuviera las
	 * suyas, la vista previa enseñaría una cabecera que no es la que se ve.
	 */
	public function test_the_appearance_panel_previews_the_real_outline() {
		$m    = $this->taller( EventWorkspace::PANEL_LOOK );
		$html = EventAppearancePanel::html( $m );

		$this->assertStringContainsString( 'data-evt-preview', $html );
		$this->assertStringContainsString( '#0a3d62', $html );
		$this->assertStringContainsString( 'Guardar la apariencia', $html );
		$this->assertStringContainsString(
			EventChrome::SEPARATORS['wave'],
			$html,
			'la onda de la vista previa es la de la página del evento'
		);
	}

	/**
	 * Las cuatro imágenes son el selector de medios, con respaldo sin guion.
	 *
	 * Lo que viaja es el `hidden` con el identificador del adjunto, no el
	 * fichero; el `input type="file"` se queda dentro del `<noscript>` para
	 * que subir siga siendo posible sin guion. Y la miniatura enseña el
	 * nombre del fichero y sus dimensiones: con el `file` pelado de antes no
	 * había forma de saber qué imagen había puesta.
	 */
	public function test_the_appearance_panel_offers_the_media_picker() {
		$m = $this->taller( EventWorkspace::PANEL_LOOK );

		$adjunto              = (int) wp_insert_attachment(
			array(
				'post_title'     => 'cartel-jornadas.jpg',
				'post_mime_type' => 'image/jpeg',
				'post_status'    => 'inherit',
			),
			'2026/03/cartel-jornadas.jpg'
		);
		$m['media']['poster'] = $adjunto;

		$html = EventAppearancePanel::html( $m );

		foreach ( array( 'evt_logo_id', 'evt_header_banner_id', 'evt_poster_id', 'evt_featured_id' ) as $campo ) {
			$this->assertStringContainsString( 'name="' . $campo . '"', $html, $campo );
		}
		$this->assertStringContainsString( 'data-evt-media-value', $html );
		$this->assertStringContainsString( 'Seleccionar o subir', $html );
		$this->assertStringContainsString( 'data-evt-media-type="image"', $html );
		$this->assertStringContainsString( 'Arrastre una imagen hasta este campo', $html );
		$this->assertStringContainsString( 'data-evt-media-status', $html );
		$this->assertStringContainsString( 'data-evt-media-min-width="1920"', $html, 'el banner publica su límite también al cargador directo' );
		$this->assertStringContainsString( 'cartel-jornadas.jpg', $html, 'el nombre del fichero que hay puesto' );
		$this->assertStringContainsString( '<noscript>', $html );
		$this->assertStringContainsString( 'name="evt_poster_file"', $html, 'el respaldo para subir sin guion' );
		$this->assertStringContainsString( 'value="' . $adjunto . '"', $html );
	}

	/**
	 * La foto de ponentes usa el mismo selector nativo que la apariencia.
	 */
	public function test_the_speaker_photo_uses_the_wordpress_media_picker() {
		$m    = $this->taller( EventWorkspace::PANEL_SPEAKERS );
		$html = EventWorkspaceView::html( $m );

		$this->assertStringContainsString( 'name="evt_sp_photo"', $html );
		$this->assertStringContainsString( 'data-evt-media-value', $html );
		$this->assertStringContainsString( 'data-evt-media-pick', $html );
		$this->assertStringContainsString( 'Seleccionar o subir', $html );
		$this->assertStringContainsString( 'Eliminar del campo', $html );
		$this->assertStringContainsString( 'arrastrar una nueva', $html );
		$this->assertStringContainsString( 'data-evt-media-status', $html );
	}

	/**
	 * El formulario de una sección se pinta con su nonce y su editor.
	 */
	public function test_the_section_form_paints_its_fields() {
		$area   = $this->area( 'Innovación' );
		$autor  = $this->administrator();
		$evento = $this->event( $autor, array( $area ) );

		$this->acting_as( $autor );
		$_GET['evento'] = (string) $evento;

		$html = PageFormView::html( PageForm::model() );

		$this->assertStringContainsString( 'Nueva sección', $html );
		$this->assertStringContainsString( PageForm::NONCE_FIELD, $html );
		$this->assertStringContainsString( 'Tipo de sección', $html );
		$this->assertStringContainsString( 'Apariencia de esta sección', $html );
		$this->assertStringContainsString( 'Crear la sección', $html );
	}

	/**
	 * Sin permiso, el taller no pinta ni la tabla ni un solo botón.
	 */
	public function test_without_permission_the_workshop_paints_no_buttons() {
		$mia    = $this->area( 'Formación del Profesorado' );
		$otra   = $this->area( 'Innovación' );
		$evento = $this->event( $this->organiser( array( $mia ) ), array( $mia ) );

		$this->acting_as( $this->organiser( array( $otra ) ) );
		$_GET[ EventWorkspace::ARG_EVENT ] = (string) $evento;

		$html = EventWorkspaceView::html( EventWorkspace::model() );

		$this->assertStringNotContainsString( 'Añadir sección', $html );
		$this->assertStringNotContainsString( 'method="post"', $html );
		$this->assertStringContainsString( 'Ver mis eventos', $html );
	}
}
