<?php
/**
 * Front-end editor of one satellite page of an event.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

use Evt\Access\EventAccess;
use Evt\Domain\EventInput;
use Evt\Meta\EventMetaKeys;
use Evt\PostType\EventPostType;
use Evt\PublicFront\View\PageFormView;

/**
 * Shortcode [evt_page_form]: alta y edición de una sección del evento.
 *
 * Hoy esto es el formulario del sistema anterior entero —un solo formulario de
 * más de cinco mil píxeles de scroll, está medido y el material está en
 * `.local/`— y el mismo para la portada del evento y para cada sección
 * satélite. Aquí son cinco campos y un plegado: qué sección es, cómo se llama,
 * dónde vive, en qué orden sale y qué cuenta. Lo que se lee de la petición:
 *
 * - `?evento=<id>` — alta de una sección que cuelga de ese evento.
 * - `?seccion=<id>` — edición de esa sección; el evento sale de su `post_parent`
 *   y no de la petición, para que nadie cuelgue una sección ajena de su área.
 *
 * El plegado «Apariencia de esta sección» son los siete campos de diseño que
 * el formulario anterior repite para la hija: fondo de cabecera, texto de
 * cabecera, separador, logo acompañante, forma de las imágenes y las dos
 * tipografías. Lo que se deja en blanco no se guarda: así se distingue «no
 * elegido» —se hereda del evento— de «elegido vacío».
 *
 * La sección nace en borrador. Publicarla es una acción del panel de secciones
 * del evento, no un desplegable perdido en un formulario largo.
 *
 * Al final, el CSS y el JavaScript de ESTA página, que se aplican solo a ella y
 * después de los del evento: el CSS como un bloque más —lo escribe también
 * quien organiza el evento, acotado a su área— y el JavaScript en el recuadro
 * amarillo de «Solo administración». Sin la capacidad no se pinta —y si los
 * campos llegan igual en el envío, se ignoran sin tocar lo que hubiera
 * guardado.
 */
final class PageForm {

	public const SHORTCODE = 'evt_page_form';

	/**
	 * Nonce action of the form.
	 */
	public const NONCE_ACTION = 'evt_page_save';

	/**
	 * Nonce field name.
	 */
	public const NONCE_FIELD = 'evt_page_nonce';

	/**
	 * The seven appearance settings a satellite page carries of its own.
	 *
	 * @var string[]
	 */
	public const LOOK_KEYS = array(
		EventMetaKeys::HEADER_BG,
		EventMetaKeys::HEADER_TEXT,
		EventMetaKeys::SEPARATOR,
		EventMetaKeys::LOGO_ID,
		EventMetaKeys::IMAGE_SHAPE,
		EventMetaKeys::TITLE_FONT,
		EventMetaKeys::BODY_FONT,
	);

	/**
	 * What each of the seven appearance settings is called on screen.
	 *
	 * @return array<string, string> Meta key => rótulo.
	 */
	public static function look_labels(): array {
		return array(
			EventMetaKeys::HEADER_BG   => 'color de fondo de la cabecera',
			EventMetaKeys::HEADER_TEXT => 'color del texto de la cabecera',
			EventMetaKeys::SEPARATOR   => 'separador',
			EventMetaKeys::LOGO_ID     => 'logo',
			EventMetaKeys::IMAGE_SHAPE => 'forma de las imágenes',
			EventMetaKeys::TITLE_FONT  => 'tipografía de los títulos',
			EventMetaKeys::BODY_FONT   => 'tipografía del cuerpo',
		);
	}

	/**
	 * What a section does not take from its event.
	 *
	 * Es lo que se pregunta en el taller para avisar: quien cambia el color
	 * del evento y no ve el cambio en una sección necesita saber que esa
	 * sección tiene el suyo.
	 *
	 * @param int $page_id Section.
	 * @return string[] Labels of the settings it has of its own; empty when it follows the event.
	 */
	public static function own_look( int $page_id ): array {
		$propios = array();
		foreach ( self::look_labels() as $clave => $rotulo ) {
			$valor = (string) get_post_meta( $page_id, $clave, true );
			if ( '' !== $valor && '0' !== $valor ) {
				$propios[] = $rotulo;
			}
		}
		return $propios;
	}

	/**
	 * The contact details a contact page carries, and how each is cleaned.
	 *
	 * Campos y no HTML dentro del texto: quien organiza rellena la dirección,
	 * el teléfono y el correo, y la página los pinta en tres columnas.
	 *
	 * @var array<string, string>
	 */
	public const CONTACT_KEYS = array(
		EventMetaKeys::CONTACT_ADDRESS => 'sanitize_textarea_field',
		EventMetaKeys::CONTACT_PHONE   => 'sanitize_textarea_field',
		EventMetaKeys::CONTACT_EMAIL   => 'sanitize_email',
		EventMetaKeys::CONTACT_MAP     => 'esc_url_raw',
	);

	/**
	 * Why the last submit did not go through, and which fields to mark.
	 *
	 * Un envío rechazado no redirige: la misma petición vuelve a pintar el
	 * formulario, así que el motivo no tiene que sobrevivir a nada y no hace
	 * falta guardarlo fuera.
	 *
	 * @var array{message:string, errors:string[]}
	 */
	private static $rejected = array(
		'message' => '',
		'errors'  => array(),
	);

	/**
	 * Register the shortcode and the POST handler.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'render' ) );
		// Antes de pintar: si el envío entra, se sale por `Shell::leave()` sin
		// llegar a montar la pantalla.
		add_action( 'init', array( self::class, 'maybe_handle_submit' ), 20 );
	}

	/**
	 * Take the form submit: nonce, permission over the parent event, and save.
	 *
	 * @return void
	 */
	public static function maybe_handle_submit(): void {
		if ( ! self::is_our_submit() || ! is_user_logged_in() ) {
			return;
		}
		if ( ! self::nonce_ok() ) {
			self::$rejected['message'] = 'El formulario estuvo abierto demasiado tiempo y el envío caducó. Vuelva a enviarlo.';
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- comprobado en la línea de arriba.
		$raw      = wp_unslash( $_POST );
		$user_id  = get_current_user_id();
		$page_id  = self::int_of( $raw, 'evt_page_id' );
		$event_id = self::target_event( $raw, $page_id );

		if ( ! EventPostType::is_root( $event_id ) ) {
			self::$rejected['message'] = 'No se sabe de qué evento cuelga esta sección. Ábrala desde su evento.';
			return;
		}
		// El guardián, sobre el evento padre: sin permiso sobre él no se crea ni
		// se toca nada, aunque el identificador venga escrito a mano en el envío.
		if ( ! EventAccess::can_edit( $user_id, $event_id ) ) {
			self::$rejected['message'] = EventAccess::why_not_editable( $user_id, $event_id );
			return;
		}
		// Antes de escribir nada, y con el evento padre y no con la sección: el
		// bloqueo es del evento entero. Si lo tiene otra persona, esto responde
		// 409 y no vuelve.
		EditLock::require_available( $event_id );

		$fields = self::submitted_fields( $raw );
		// Al editar, el tipo es el que ya tiene la sección y no el que venga en
		// el envío: se eligió al crearla y no se cambia (ADR-0019). Se valida
		// igual, porque un valor guardado también puede haberse corrompido.
		$tipo  = $page_id > 0
			? (string) get_post_meta( $page_id, EventMetaKeys::SECTION_TYPE, true )
			: $fields['section_type'];
		$check = EventInput::validate(
			array(
				'title'        => $fields['title'],
				'section_type' => $tipo,
				'parent'       => $event_id,
			)
		);
		if ( ! $check['ok'] ) {
			self::$rejected = array(
				'message' => self::error_message( $check['errors'] ),
				'errors'  => $check['errors'],
			);
			return;
		}

		// Un punto del mapa con coordenadas que no se entienden no se tira en
		// silencio: se dice cuál, y el formulario vuelve con lo escrito.
		if ( 'contacto' === $tipo && '' !== $fields['points_bad'] ) {
			self::$rejected = array(
				'message' => sprintf( 'Las coordenadas de «%s» no se entienden. Escríbalas como las copia el mapa: 28.4636, -16.2518.', $fields['points_bad'] ),
				'errors'  => array( 'points' ),
			);
			return;
		}

		$saved = self::save( $user_id, $event_id, $page_id, $check['data'], $fields );
		if ( is_wp_error( $saved ) ) {
			self::$rejected['message'] = $saved->get_error_message();
			return;
		}

		// La imagen de la tarjeta es la destacada de la sección, con el mismo
		// campo y las mismas comprobaciones que las imágenes del evento. Va
		// después de guardar porque una sección nueva no tiene ID hasta ahora.
		$imagen = null === $fields['home_card'] || EventWorkspace::save_image( (int) $saved, 'evt_card_image', '' );

		self::leave_saved( $page_id, (int) $saved, $imagen );
	}

	/**
	 * Back to the same screen, with the notice of what just happened.
	 *
	 * @param int  $page_id Page that was being edited, 0 when it was created now.
	 * @param int  $saved   Page ID after saving.
	 * @param bool $image   Whether the card image could be stored.
	 * @return void
	 */
	private static function leave_saved( int $page_id, int $saved, bool $image = true ): void {
		$destino = Shell::url(
			'section',
			array_filter(
				array(
					'seccion'        => $saved,
					'evt_hecho'      => ! $image ? 'sin-imagen' : ( 0 === $page_id ? 'creada' : 'guardada' ),
					// Guardar dentro del panel lateral se queda en el panel.
					Shell::ARG_FRAME => Shell::framed() ? '1' : '',
				)
			)
		);
		Shell::leave( '' !== $destino ? $destino : Shell::back_url( 'events' ) );
	}

	/**
	 * The event this submit is about.
	 *
	 * Al editar, el evento sale del `post_parent` de la sección y no de la
	 * petición: si viniera del envío, cambiarlo a mano movería una sección de
	 * un evento a otro —de un área a otra— con solo tener permiso sobre el
	 * evento de destino.
	 *
	 * @param array<string, mixed> $raw     Unslashed $_POST.
	 * @param int                  $page_id Page being edited, 0 when new.
	 * @return int
	 */
	private static function target_event( array $raw, int $page_id ): int {
		return $page_id > 0 ? self::parent_of( $page_id ) : self::int_of( $raw, 'evt_page_event' );
	}

	/**
	 * A positive integer from a submitted field.
	 *
	 * @param array<string, mixed> $raw Unslashed $_POST.
	 * @param string               $key Field name.
	 * @return int
	 */
	private static function int_of( array $raw, string $key ): int {
		return isset( $raw[ $key ] ) ? max( 0, (int) $raw[ $key ] ) : 0;
	}

	/**
	 * Write the page and its meta.
	 *
	 * @param int                  $user_id  Who is saving.
	 * @param int                  $event_id Parent event.
	 * @param int                  $page_id  Page being edited, 0 when new.
	 * @param array<string, mixed> $data     What EventInput normalised.
	 * @param array<string, mixed> $fields   What the form carried.
	 * @return int|\WP_Error Page ID.
	 */
	private static function save( int $user_id, int $event_id, int $page_id, array $data, array $fields ) {
		$title   = (string) $data['title'];
		$postarr = array(
			'post_type'    => EventPostType::POST_TYPE,
			'post_parent'  => $event_id,
			'post_title'   => $title,
			// Sin slug tecleado, el del título: una sección sin dirección no se
			// enlaza, y el aplicativo vive de sus direcciones.
			'post_name'    => sanitize_title( '' !== $fields['slug'] ? $fields['slug'] : $title ),
			'post_content' => $fields['content'],
			'menu_order'   => $fields['menu_order'],
		);

		if ( $page_id > 0 ) {
			$postarr['ID'] = $page_id;
			$result        = wp_update_post( wp_slash( $postarr ), true );
		} else {
			// Nace en borrador, y el estado no vuelve a tocarse aquí: publicar y
			// retirar son acciones del panel de secciones del evento.
			$postarr['post_status'] = 'draft';
			$postarr['post_author'] = $user_id;
			$result                 = wp_insert_post( wp_slash( $postarr ), true );
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$id = (int) $result;

		// El tipo se escribe UNA vez, al crear. Cambiarlo después dejaría la
		// sección con los elementos de otro tipo: una página de programa y una
		// de contacto no llevan lo mismo dentro (ADR-0019). Aunque alguien
		// mande el campo a mano en el POST de una edición, aquí no se toca.
		//
		// Esto es redundante a propósito con el `$tipo` de `maybe_handle_submit()`,
		// que ya sustituye lo enviado por lo guardado: comprobado que cada una
		// de las dos basta por separado y que el test solo cae si se quitan las
		// dos. La de arriba protege la validación y esta la escritura; quien
		// mañana toque una, no se lleva la otra por delante.
		if ( 0 === $page_id ) {
			update_post_meta( $id, EventMetaKeys::SECTION_TYPE, (string) $data['section_type'] );
		}

		self::save_showcase( $id, $fields );

		foreach ( self::LOOK_KEYS as $clave ) {
			$valor = (string) ( $fields['look'][ $clave ] ?? '' );
			if ( '' === $valor || '0' === $valor ) {
				// Sin elegir: se hereda del evento, y eso se guarda no guardando.
				delete_post_meta( $id, $clave );
				continue;
			}
			update_post_meta( $id, $clave, $valor );
		}

		// Solo en una página de contacto: en las demás estos campos ni se pintan.
		if ( 'contacto' === (string) get_post_meta( $id, EventMetaKeys::SECTION_TYPE, true ) ) {
			foreach ( array_keys( self::CONTACT_KEYS ) as $clave ) {
				$valor = (string) ( $fields['contact'][ $clave ] ?? '' );
				if ( '' === $valor ) {
					delete_post_meta( $id, $clave );
					continue;
				}
				update_post_meta( $id, $clave, $valor );
			}
			$puntos = ContactMap::clean( $fields['points_ok'] );
			if ( array() === $puntos ) {
				delete_post_meta( $id, EventMetaKeys::CONTACT_POINTS );
			} else {
				update_post_meta( $id, EventMetaKeys::CONTACT_POINTS, wp_slash( (string) wp_json_encode( $puntos ) ) );
			}
		}

		self::save_code( $user_id, $event_id, $id, $fields );

		return $id;
	}

	/**
	 * Write whether the section has a card on the front page, and its icon.
	 *
	 * Solo si el envío trae el bloque: un envío sin él no dice nada de la
	 * tarjeta ni del icono, y lo guardado se queda como estaba.
	 *
	 * @param int                  $page_id Page just written.
	 * @param array<string, mixed> $fields  What the form carried.
	 * @return void
	 */
	private static function save_showcase( int $page_id, array $fields ): void {
		if ( null === $fields['home_card'] ) {
			return;
		}

		// Sale en la portada salvo que se diga lo contrario: la marca solo se
		// guarda para quitarla, y así las secciones que ya existen no cambian.
		if ( $fields['home_card'] ) {
			delete_post_meta( $page_id, EventMetaKeys::HOME_HIDDEN );
		} else {
			update_post_meta( $page_id, EventMetaKeys::HOME_HIDDEN, true );
		}
		if ( $fields['in_menu'] ) {
			delete_post_meta( $page_id, EventMetaKeys::MENU_HIDDEN );
		} else {
			update_post_meta( $page_id, EventMetaKeys::MENU_HIDDEN, true );
		}

		// Sin elegir, el de su tipo; y eso también se guarda no guardando, para
		// que el icono siga al tipo si mañana cambia el de la lista.
		if ( '' === $fields['icon'] ) {
			delete_post_meta( $page_id, EventMetaKeys::SECTION_ICON );
		} else {
			update_post_meta( $page_id, EventMetaKeys::SECTION_ICON, $fields['icon'] );
		}
	}

	/**
	 * Write the custom CSS and JavaScript of this page, field by field.
	 *
	 * Se comprueba por campo y no una vez: las dos capacidades son distintas a
	 * propósito —una hoja de estilos afea una página, un guion ejecuta código
	 * en el navegador de cada visitante— y quien tiene una puede no tener la
	 * otra. El campo que no se puede escribir no se toca: si llega en el envío
	 * es que alguien lo escribió a mano, y lo guardado se queda como estaba.
	 *
	 * @param int                  $user_id  Who is saving.
	 * @param int                  $event_id Parent event.
	 * @param int                  $page_id  Page just written.
	 * @param array<string, mixed> $fields   What the form carried.
	 * @return void
	 */
	private static function save_code( int $user_id, int $event_id, int $page_id, array $fields ): void {
		foreach ( self::code_allowed( $user_id, $event_id ) as $clave => $puede ) {
			if ( ! $puede ) {
				continue;
			}
			// En crudo: es código, y limpiarlo lo rompería. De desactivar la
			// fuga de etiqueta se encargan los sanitize de
			// `register_post_meta()`, que corren dentro de esta llamada.
			// `wp_slash()` porque `update_post_meta()` desescapa antes de
			// guardar, y sin eso un `\2014` del CSS o un `\d` del JavaScript
			// perderían la barra.
			update_post_meta( $page_id, $clave, wp_slash( (string) ( $fields['code'][ $clave ] ?? '' ) ) );
		}
	}

	/**
	 * Which of the two code fields this person may write here.
	 *
	 * Se pregunta por el evento padre y no por la página: es donde vive el
	 * área, y al crear una sección todavía no hay página por la que preguntar.
	 *
	 * @param int $user_id  Who is asking.
	 * @param int $event_id Parent event.
	 * @return array<string, bool> Meta key => whether it may be written.
	 */
	private static function code_allowed( int $user_id, int $event_id ): array {
		return array(
			EventMetaKeys::CUSTOM_CSS => EventAccess::can_edit_custom_css( $user_id, $event_id ),
			EventMetaKeys::CUSTOM_JS  => EventAccess::can_edit_custom_js( $user_id, $event_id ),
		);
	}

	/**
	 * Everything the screen decides before painting.
	 *
	 * @return array<string, mixed>
	 */
	public static function model(): array {
		$m = array(
			'aviso'      => '',
			'hecho'      => '',
			'hecho_tono' => 'ok',
			'can_upload' => false,
			'error'      => self::$rejected['message'],
			'errors'     => self::$rejected['errors'],
			'event_id'   => 0,
			'event'      => '',
			'event_url'  => '',
			'page_id'    => 0,
			'status'     => 'draft',
			'types'      => EventMetaKeys::section_types(),
			'code'       => array_fill_keys( EventMetaKeys::code_keys(), false ),
			'lock'       => EditLock::none(),
			'values'     => self::defaults(),
		);

		if ( ! is_user_logged_in() ) {
			$m['aviso'] = 'Debe iniciar sesión con su usuario para editar una sección.';
			return $m;
		}

		$user_id = get_current_user_id();
		$page_id = self::query_id( 'seccion' );

		if ( $page_id > 0 && EventPostType::is_root( $page_id ) ) {
			$m['aviso'] = 'Eso es la portada del evento, no una de sus secciones: se edita en el taller del evento.';
			return $m;
		}

		$event_id = $page_id > 0 ? self::parent_of( $page_id ) : self::query_id( 'evento' );
		if ( ! EventPostType::is_root( $event_id ) ) {
			$m['aviso'] = 'Abra la sección desde el evento al que pertenece: así se sabe de cuál cuelga.';
			return $m;
		}
		if ( ! EventAccess::can_edit( $user_id, $event_id ) ) {
			$m['aviso'] = EventAccess::why_not_editable( $user_id, $event_id );
			return $m;
		}

		$m['event_id']  = $event_id;
		$m['event']     = (string) get_post_field( 'post_title', $event_id );
		$m['event_url'] = Shell::url(
			'event',
			array(
				'evento' => $event_id,
				'panel'  => 'secciones',
			)
		);
		$m['page_id']   = $page_id;
		$m['hecho']     = self::done_notice();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo elige el color del aviso.
		$m['hecho_tono'] = isset( $_GET['evt_hecho'] ) && 'sin-imagen' === $_GET['evt_hecho'] ? 'aviso' : 'ok';
		$m['can_upload'] = current_user_can( 'upload_files' );
		$m['code']       = self::code_allowed( $user_id, $event_id );
		// Abrir la sección toma el bloqueo DEL EVENTO: dos personas en dos
		// secciones distintas del mismo evento se pisan igual, porque comparten
		// la navegación, el orden y la apariencia.
		$m['lock'] = EditLock::status( $event_id, true );

		if ( $page_id > 0 ) {
			$m['status'] = (string) get_post_status( $page_id );
			$m['values'] = self::stored_values( $page_id );
		} else {
			// El tipo se elige en «Añadir sección» y llega en la dirección: no se
			// vuelve a preguntar. Solo si es uno de la lista cerrada.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo elige qué se pinta; el envío revalida nonce y permisos.
			$tipo = isset( $_GET[ EventWorkspace::ARG_TYPE ] ) ? sanitize_key( wp_unslash( $_GET[ EventWorkspace::ARG_TYPE ] ) ) : '';
			if ( isset( EventMetaKeys::section_types()[ $tipo ] ) ) {
				$m['values']['section_type'] = $tipo;
			}
		}

		// Lo que se acaba de teclear manda sobre lo guardado: al corregir, la
		// sección todavía tiene lo viejo y repintarlo borraría la corrección.
		$enviado = self::submitted_values();
		if ( null !== $enviado ) {
			// El tipo no viaja al editar —no se cambia—: sin esto, el repintado
			// de un envío rechazado perdía los campos propios del tipo.
			if ( $page_id > 0 ) {
				$enviado['section_type'] = (string) $m['values']['section_type'];
			}
			$m['values'] = $enviado;
		}

		$m['values']['code'] = self::readable_code( $m['code'], (array) $m['values']['code'] );

		return $m;
	}

	/**
	 * Blank out the code this person may not write.
	 *
	 * El campo que no se puede escribir tampoco se repinta, venga del envío o
	 * de lo guardado: su formulario no lo lleva, y el modelo no le pasa a la
	 * vista nada que la vista no deba pintar.
	 *
	 * @param array<string, bool>   $puede   Meta key => whether it may be written.
	 * @param array<string, string> $valores Meta key => value.
	 * @return array<string, string>
	 */
	private static function readable_code( array $puede, array $valores ): array {
		foreach ( $puede as $clave => $ok ) {
			if ( ! $ok ) {
				$valores[ $clave ] = '';
			}
		}
		return $valores;
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array<string, string>|string $atts Attributes.
	 * @return string
	 */
	public static function render( $atts = array() ): string {
		unset( $atts );
		Assets::enqueue();
		$m = self::model();
		// El selector de la imagen de la tarjeta; quien no puede subir tampoco
		// puede consultar la biblioteca, y a esa persona solo le sale «Quitar».
		if ( ! empty( $m['can_upload'] ) && (int) $m['event_id'] > 0 ) {
			wp_enqueue_media( (int) $m['page_id'] > 0 ? array( 'post' => (int) $m['page_id'] ) : array() );
		}
		$lock = (array) $m['lock'];
		$html = PageFormView::html( $m );

		// Un `fieldset` desactivado apaga de una vez todos los controles que
		// tenga dentro, así que la vista no tiene que enterarse de nada. Es la
		// misma pieza con la que el taller pinta un evento en solo lectura.
		if ( (int) $lock['owner'] > 0 ) {
			$html = '<fieldset class="evt-solo-lectura" disabled>'
				. '<legend class="screen-reader-text">Sección en solo lectura: otra persona tiene abierto este evento</legend>'
				. $html . '</fieldset>';
		}

		return EditLock::render( EditLock::claim( $lock ) ) . $html;
	}

	/**
	 * Empty form values.
	 *
	 * @return array<string, mixed>
	 */
	private static function defaults(): array {
		$look = array();
		foreach ( self::LOOK_KEYS as $clave ) {
			$look[ $clave ] = '';
		}
		return array(
			'title'        => '',
			'slug'         => '',
			'section_type' => '',
			'menu_order'   => 0,
			'home_card'    => true,
			'in_menu'      => true,
			'icon'         => '',
			'card_image'   => 0,
			'content'      => '',
			'look'         => $look,
			'contact'      => array_fill_keys( array_keys( self::CONTACT_KEYS ), '' ),
			'points'       => array(),
			'code'         => array_fill_keys( EventMetaKeys::code_keys(), '' ),
		);
	}

	/**
	 * Form values of a page already saved.
	 *
	 * @param int $page_id Page ID.
	 * @return array<string, mixed>
	 */
	private static function stored_values( int $page_id ): array {
		$look = array();
		foreach ( self::LOOK_KEYS as $clave ) {
			$look[ $clave ] = (string) get_post_meta( $page_id, $clave, true );
		}
		$code = array();
		foreach ( EventMetaKeys::code_keys() as $clave ) {
			$code[ $clave ] = (string) get_post_meta( $page_id, $clave, true );
		}
		$contacto = array();
		foreach ( array_keys( self::CONTACT_KEYS ) as $clave ) {
			$contacto[ $clave ] = (string) get_post_meta( $page_id, $clave, true );
		}
		return array(
			'title'        => (string) get_post_field( 'post_title', $page_id ),
			'slug'         => (string) get_post_field( 'post_name', $page_id ),
			'section_type' => (string) get_post_meta( $page_id, EventMetaKeys::SECTION_TYPE, true ),
			'menu_order'   => (int) get_post_field( 'menu_order', $page_id ),
			'home_card'    => ! (bool) get_post_meta( $page_id, EventMetaKeys::HOME_HIDDEN, true ),
			'in_menu'      => ! (bool) get_post_meta( $page_id, EventMetaKeys::MENU_HIDDEN, true ),
			'icon'         => (string) get_post_meta( $page_id, EventMetaKeys::SECTION_ICON, true ),
			'card_image'   => (int) get_post_thumbnail_id( $page_id ),
			'content'      => (string) get_post_field( 'post_content', $page_id ),
			'look'         => $look,
			'contact'      => $contacto,
			'points'       => array_map(
				static function ( array $punto ): array {
					return array(
						'coords' => $punto['lat'] . ', ' . $punto['lng'],
						'text'   => $punto['text'],
						'url'    => $punto['url'],
					);
				},
				ContactMap::points( $page_id )
			),
			'code'         => $code,
		);
	}

	/**
	 * The fields a submit carries, in the shape the form and the saver share.
	 *
	 * Una sola lectura de la petición para los dos que la miran: el que guarda
	 * y el que repinta. Con dos, acabarían interpretando distinto el mismo
	 * campo.
	 *
	 * @param array<string, mixed> $raw Unslashed $_POST.
	 * @return array<string, mixed>
	 */
	private static function submitted_fields( array $raw ): array {
		// «Volver a la del evento» vacía los siete de una vez: es lo mismo que
		// dejarlos en blanco a mano, que es como se hereda.
		$heredar = ! empty( $raw['evt_look_reset'] );
		$look    = array();
		foreach ( self::LOOK_KEYS as $clave ) {
			$look[ $clave ] = ! $heredar && isset( $raw[ $clave ] ) ? sanitize_text_field( (string) $raw[ $clave ] ) : '';
		}
		$contacto = array();
		foreach ( self::CONTACT_KEYS as $clave => $limpia ) {
			$contacto[ $clave ] = isset( $raw[ $clave ] ) ? (string) call_user_func( $limpia, (string) $raw[ $clave ] ) : '';
		}
		list( $filas, $puntos, $mal ) = self::submitted_points( $raw );

		$code = array();
		foreach ( EventMetaKeys::code_keys() as $clave ) {
			// Tal cual llega: es código. Quien decide si se guarda es
			// {@see save_code()}, y quien lo limpia, su sanitize de meta.
			$code[ $clave ] = isset( $raw[ $clave ] ) ? (string) $raw[ $clave ] : '';
		}

		return array(
			'title'        => isset( $raw['evt_title'] ) ? sanitize_text_field( (string) $raw['evt_title'] ) : '',
			'slug'         => isset( $raw['evt_slug'] ) ? sanitize_title( (string) $raw['evt_slug'] ) : '',
			'section_type' => isset( $raw[ EventMetaKeys::SECTION_TYPE ] ) ? sanitize_key( (string) $raw[ EventMetaKeys::SECTION_TYPE ] ) : '',
			'menu_order'   => isset( $raw['evt_order'] ) ? (int) $raw['evt_order'] : 0,
			// Una casilla sin marcar no viaja: su ausencia es el «no», pero
			// solo si el bloque vino en el envío. Sin él, null: no se toca.
			'home_card'    => isset( $raw['evt_showcase'] ) ? ! empty( $raw['evt_home_card'] ) : null,
			'in_menu'      => isset( $raw['evt_showcase'] ) ? ! empty( $raw['evt_in_menu'] ) : null,
			'icon'         => isset( $raw[ EventMetaKeys::SECTION_ICON ] )
				? EventMetaKeys::in_list( sanitize_key( (string) $raw[ EventMetaKeys::SECTION_ICON ] ), EventMetaKeys::section_icons() )
				: '',
			// Solo para repintar lo elegido si el envío se rechaza; lo guarda y
			// lo comprueba {@see EventWorkspace::save_image()}.
			'card_image'   => isset( $raw['evt_card_image_id'] ) ? absint( $raw['evt_card_image_id'] ) : 0,
			// Nadie escribe HTML sin filtrar, tampoco quien organiza.
			'content'      => isset( $raw['evt_content'] ) ? wp_kses_post( (string) $raw['evt_content'] ) : '',
			'look'         => $look,
			'contact'      => $contacto,
			// Las filas tal cual, para repintar; los puntos, para guardar; y el
			// texto del primero que no se entiende, para decirlo.
			'points'       => $filas,
			'points_ok'    => $puntos,
			'points_bad'   => $mal,
			'code'         => $code,
		);
	}

	/**
	 * The map rows of a submit: as typed, as points, and the first one that does not parse.
	 *
	 * Campos paralelos, como las preguntas de la inscripción: una fila por
	 * índice. La fila sin nada escrito no cuenta; la que tiene texto o enlace
	 * pero unas coordenadas que no se entienden, sí, y se devuelve para
	 * decirlo.
	 *
	 * @param array<string, mixed> $raw Unslashed $_POST.
	 * @return array{0: array<int, array<string, string>>, 1: array<int, array<string, mixed>>, 2: string}
	 */
	private static function submitted_points( array $raw ): array {
		$coords = isset( $raw['evt_cp_coords'] ) ? (array) $raw['evt_cp_coords'] : array();
		$textos = isset( $raw['evt_cp_text'] ) ? (array) $raw['evt_cp_text'] : array();
		$urls   = isset( $raw['evt_cp_url'] ) ? (array) $raw['evt_cp_url'] : array();

		$filas  = array();
		$puntos = array();
		$mal    = '';
		foreach ( $coords as $i => $coordenadas ) {
			$fila = array(
				'coords' => sanitize_text_field( (string) $coordenadas ),
				'text'   => isset( $textos[ $i ] ) ? sanitize_text_field( (string) $textos[ $i ] ) : '',
				'url'    => isset( $urls[ $i ] ) ? esc_url_raw( trim( (string) $urls[ $i ] ) ) : '',
			);
			if ( '' === $fila['coords'] && '' === $fila['text'] && '' === $fila['url'] ) {
				continue;
			}
			$filas[] = $fila;

			$par = ContactMap::parse_coordinates( $fila['coords'] );
			if ( null === $par || array() === ContactMap::clean(
				array(
					array(
						'lat' => $par[0],
						'lng' => $par[1],
					),
				)
			) ) {
				$mal = '' !== $mal ? $mal : ( '' !== $fila['text'] ? $fila['text'] : $fila['coords'] );
				continue;
			}
			$puntos[] = array(
				'lat'  => $par[0],
				'lng'  => $par[1],
				'text' => $fila['text'],
				'url'  => $fila['url'],
			);
		}
		return array( $filas, $puntos, $mal );
	}

	/**
	 * Form values from the last submit, or null when the request is not one.
	 *
	 * @return array<string, mixed>|null
	 */
	private static function submitted_values(): ?array {
		if ( ! self::is_our_submit() || ! self::nonce_ok() ) {
			return null;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- comprobado en la línea de arriba.
		return self::submitted_fields( wp_unslash( $_POST ) );
	}

	/**
	 * Whether this request is this form being submitted.
	 *
	 * @return bool
	 */
	private static function is_our_submit(): bool {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_METHOD'] ) ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- solo mira si el envío es nuestro; el nonce se comprueba aparte.
		return 'POST' === $method && ! empty( $_POST['evt_page_form'] );
	}

	/**
	 * Whether the submit carries our valid nonce.
	 *
	 * @return bool
	 */
	private static function nonce_ok(): bool {
		$nonce = isset( $_POST[ self::NONCE_FIELD ] )
			? sanitize_text_field( wp_unslash( (string) $_POST[ self::NONCE_FIELD ] ) )
			: '';
		return '' !== $nonce && false !== wp_verify_nonce( $nonce, self::NONCE_ACTION );
	}

	/**
	 * A positive ID from the query string.
	 *
	 * @param string $name Query argument.
	 * @return int
	 */
	private static function query_id( string $name ): int {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo elige qué se pinta; el envío revalida nonce y permisos.
		$value = isset( $_GET[ $name ] ) ? (int) $_GET[ $name ] : 0;
		return max( 0, $value );
	}

	/**
	 * The one-line notice of what just got saved.
	 *
	 * @return string
	 */
	private static function done_notice(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo elige un rótulo de una lista cerrada.
		$que    = isset( $_GET['evt_hecho'] ) ? sanitize_key( wp_unslash( (string) $_GET['evt_hecho'] ) ) : '';
		$textos = array(
			'creada'     => 'Sección creada, en borrador: todavía no la ve nadie. Escriba el contenido y publíquela desde el evento cuando esté lista.',
			'guardada'   => 'Cambios guardados.',
			'sin-imagen' => 'Se guardó la sección, pero la imagen de la tarjeta no se pudo cambiar y se quedó como estaba. Revise que sea una imagen de la biblioteca —JPG, PNG, WEBP o GIF— y que no pese demasiado.',
		);
		return (string) ( $textos[ $que ] ?? '' );
	}

	/**
	 * Why the submit was rejected, in words.
	 *
	 * @param string[] $codes Error codes from EventInput.
	 * @return string
	 */
	private static function error_message( array $codes ): string {
		$textos = array(
			'title'        => 'Escriba el título de la sección.',
			'section_type' => 'Elija el tipo de sección de la lista.',
		);
		$dichos = array();
		foreach ( $codes as $code ) {
			if ( isset( $textos[ $code ] ) ) {
				$dichos[] = $textos[ $code ];
			}
		}
		return array() === $dichos ? 'Revise los campos marcados.' : implode( ' ', $dichos );
	}

	/**
	 * The event a page hangs from, 0 when it is not one of our pages.
	 *
	 * @param int $page_id Page ID.
	 * @return int
	 */
	private static function parent_of( int $page_id ): int {
		if ( $page_id <= 0 || EventPostType::POST_TYPE !== get_post_type( $page_id ) ) {
			return 0;
		}
		return (int) get_post_field( 'post_parent', $page_id );
	}
}
