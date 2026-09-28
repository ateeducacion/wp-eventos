<?php
/**
 * Meta keys and closed vocabularies for the evt_event CPT.
 *
 * @package Evt
 */

namespace Evt\Meta;

/**
 * Event post meta keys (never hardcode these strings elsewhere).
 *
 * Los valores de `section_types()` van en castellano a propósito: son el
 * vocabulario del campo «Tipo de página» del formulario del sistema anterior,
 * calcado 1:1 para que las páginas que ya existen se puedan migrar sin
 * traducir nada. Lo mismo con los estados, que hoy son términos de la
 * taxonomía `convocatoria` (`evento-abierto`, `evento-finalizado`) puestos a
 * mano.
 *
 * Las claves de apariencia sustituyen a los campos de diseño de ese formulario
 * que sí se portan: fondo y texto de la cabecera, las dos tipografías, la forma
 * de las imágenes y el separador. Los demás —el mosaico, el HTML de
 * sustitución, el CSS por página— no se portan, y por qué está escrito en
 * SDD-0002.
 */
final class EventMetaKeys {

	/**
	 * Qué es esta página dentro del evento. Vacía en la raíz del evento.
	 */
	public const SECTION_TYPE = 'evt_section_type';

	/**
	 * Primer día del evento, en Y-m-d.
	 */
	public const START_DATE = 'evt_start_date';

	/**
	 * Último día del evento, en Y-m-d. Vacío cuando dura un solo día.
	 */
	public const END_DATE = 'evt_end_date';

	/**
	 * Sede, tal y como se anuncia.
	 */
	public const VENUE = 'evt_venue';

	/**
	 * Lema del evento: la línea que acompaña al título.
	 */
	public const TAGLINE = 'evt_tagline';

	/**
	 * Etiqueta de redes, sin la almohadilla.
	 */
	public const HASHTAG = 'evt_hashtag';

	/**
	 * Texto introductorio de la portada del evento. Admite HTML de entrada.
	 */
	public const INTRO = 'evt_intro';

	/**
	 * Si la portada enseña el botón de inscripción.
	 */
	public const SIGNUP_SHOW = 'evt_signup_show';

	/**
	 * Rótulo del botón de inscripción.
	 */
	public const SIGNUP_LABEL = 'evt_signup_label';

	/**
	 * Dirección a la que lleva el botón de inscripción.
	 */
	public const SIGNUP_URL = 'evt_signup_url';

	/**
	 * HISTÓRICO: identificador del formulario de inscripción del sistema viejo.
	 *
	 * Existe solo para los eventos que vienen del sistema anterior, donde la
	 * inscripción era un formulario por evento del plugin que se sustituye. El
	 * aplicativo **no lo lee para nada más que para seguir pintando ese
	 * formulario en los eventos migrados**: los participantes pasan a
	 * gestionarse aquí, con formulario propio (ADR-0027).
	 *
	 * **Es previsible que desaparezca.** Cuando no quede ningún evento vivo que
	 * lo use, se retira esta clave, su campo en «Ajustes» y la rama de
	 * `EventView::has_form()` que lo consulta. No se le añadan usos nuevos.
	 */
	public const SIGNUP_FORM_ID = 'evt_signup_form_id';

	/**
	 * Color de fondo de la cabecera del evento, hexadecimal.
	 */
	public const HEADER_BG = 'evt_header_bg';

	/**
	 * Color del texto de la cabecera del evento, hexadecimal.
	 */
	public const HEADER_TEXT = 'evt_header_text';

	/**
	 * Color de acento, hexadecimal: títulos de las tarjetas, «Acerca de» y rayas.
	 */
	public const ACCENT = 'evt_accent';

	/**
	 * ID del adjunto que hace de fondo de la cabecera, detrás del color.
	 *
	 * No es el banner: el banner sustituye la cabecera entera de la portada;
	 * este fondo va debajo del título, de la entradilla y de la ilustración, y
	 * sale en todas las páginas del evento.
	 */
	public const HEADER_BG_IMAGE_ID = 'evt_header_bg_image_id';

	/**
	 * Tipografía de los títulos.
	 */
	public const TITLE_FONT = 'evt_title_font';

	/**
	 * Tipografía del cuerpo.
	 */
	public const BODY_FONT = 'evt_body_font';

	/**
	 * ID del adjunto con el logo acompañante de la cabecera.
	 */
	public const LOGO_ID = 'evt_logo_id';

	/**
	 * ID del adjunto que sustituye visualmente la cabecera de la portada.
	 *
	 * Los datos de la cabecera siguen guardados: quitar este banner los vuelve
	 * a mostrar sin tener que reconstruirlos.
	 */
	public const HEADER_BANNER_ID = 'evt_header_banner_id';

	/**
	 * ID del adjunto con el cartel del evento.
	 */
	public const POSTER_ID = 'evt_poster_id';

	/**
	 * Forma de las imágenes de personas: cuadrada o redonda.
	 */
	public const IMAGE_SHAPE = 'evt_image_shape';

	/**
	 * Separador al pie de la cabecera del evento.
	 */
	public const SEPARATOR = 'evt_separator';

	/**
	 * Cómo se reparte el programa por días: en pestañas o en acordeón.
	 */
	public const PROGRAMME_LAYOUT = 'evt_programme_layout';

	/**
	 * El programa en acordeón: un desplegable por día y sede.
	 */
	public const LAYOUT_ACCORDION = 'accordion';

	/**
	 * ID del adjunto con el programa en PDF, para el botón «Descargar programa».
	 */
	public const PROGRAMME_FILE_ID = 'evt_programme_file_id';

	/**
	 * Los logos corporativos del pie de la portada: JSON, `[{id, url}, …]`.
	 *
	 * Una lista y no cinco campos sueltos: hay eventos con dos logos y eventos
	 * con doce.
	 */
	public const SPONSORS = 'evt_sponsors';

	/**
	 * Página de contacto: dirección, una o varias sedes separadas por una línea en blanco.
	 */
	public const CONTACT_ADDRESS = 'evt_contact_address';

	/**
	 * Página de contacto: teléfonos, uno por línea.
	 */
	public const CONTACT_PHONE = 'evt_contact_phone';

	/**
	 * Página de contacto: correo.
	 */
	public const CONTACT_EMAIL = 'evt_contact_email';

	/**
	 * Página de contacto: enlace al mapa de la sede.
	 */
	public const CONTACT_MAP = 'evt_contact_map';

	/**
	 * Sección que no saca tarjeta en la portada del evento.
	 *
	 * Sigue en el menú y se sigue viendo: solo se quita de la rejilla. Es lo
	 * que se hacía antes con la página de contacto, que ya está arriba y no
	 * necesita tarjeta. Sin la marca, la tarjeta sale: así se ven igual las
	 * secciones que ya existen.
	 */
	public const HOME_HIDDEN = 'evt_home_hidden';

	/**
	 * Icono de la sección, de la lista cerrada. Vacío: el de su tipo.
	 */
	public const SECTION_ICON = 'evt_section_icon';

	/**
	 * CSS a medida de esta página. Se guarda en crudo: es código, no texto.
	 *
	 * En la raíz del evento viste todas sus páginas; en una página satélite,
	 * solo esa, y va después del del evento para poder afinarlo. Nunca sale
	 * fuera de las páginas de su evento.
	 */
	public const CUSTOM_CSS = 'evt_custom_css';

	/**
	 * JavaScript a medida de esta página. También en crudo, y también acotado.
	 *
	 * Esto es ejecución de código en el navegador de cada visitante, así que
	 * su capacidad es propia y más estrecha que la del CSS
	 * ({@see \Evt\Access\EventAccess::can_edit_custom_js()}).
	 */
	public const CUSTOM_JS = 'evt_custom_js';

	/**
	 * Evento cerrado a edición para siempre: el estado «histórico».
	 *
	 * La marca la pone y la quita a mano quien administra el aplicativo
	 * (`evt_manage_app`), y con ella puesta el área deja de poder editar el
	 * evento y todo lo que cuelga de él. No es un despublicado: la página
	 * pública se sigue viendo igual, y el taller se sigue abriendo en solo
	 * lectura.
	 *
	 * No se confunde con `evt_legacy`, que la pone el guion de migración y
	 * significa otra cosa: que el contenido es contenido del maquetador, congelado del sistema
	 * viejo y no se regenera. La migración pone las dos; un evento nacido
	 * aquí puede recibir esta y nunca tendrá aquella.
	 */
	public const ARCHIVED = 'evt_archived';

	/**
	 * Sección que no encaja en ninguna de las demás.
	 */
	public const SECTION_OTHER = 'otra';

	/**
	 * Aún no ha empezado.
	 */
	public const STATE_UPCOMING = 'proximo';

	/**
	 * Está ocurriendo.
	 */
	public const STATE_OPEN = 'abierto';

	/**
	 * Ya pasó.
	 */
	public const STATE_FINISHED = 'finalizado';

	/**
	 * Imagen cuadrada. Es la forma por defecto.
	 */
	public const SHAPE_SQUARE = 'square';

	/**
	 * Imagen recortada en círculo.
	 */
	public const SHAPE_CIRCLE = 'circle';

	/**
	 * La tipografía del tema: ni se elige ni se carga nada.
	 */
	public const FONT_DEFAULT = '';

	/**
	 * All meta keys stored on an event post.
	 *
	 * @return string[]
	 */
	public static function all(): array {
		return array(
			self::SECTION_TYPE,
			self::START_DATE,
			self::END_DATE,
			self::VENUE,
			self::TAGLINE,
			self::HASHTAG,
			self::INTRO,
			self::SIGNUP_SHOW,
			self::SIGNUP_LABEL,
			self::SIGNUP_URL,
			self::SIGNUP_FORM_ID,
			self::HEADER_BG,
			self::HEADER_TEXT,
			self::ACCENT,
			self::HEADER_BG_IMAGE_ID,
			self::TITLE_FONT,
			self::BODY_FONT,
			self::LOGO_ID,
			self::HEADER_BANNER_ID,
			self::POSTER_ID,
			self::IMAGE_SHAPE,
			self::SEPARATOR,
			self::PROGRAMME_LAYOUT,
			self::PROGRAMME_FILE_ID,
			self::SPONSORS,
			self::CONTACT_ADDRESS,
			self::CONTACT_PHONE,
			self::CONTACT_EMAIL,
			self::CONTACT_MAP,
			self::HOME_HIDDEN,
			self::SECTION_ICON,
			self::CUSTOM_CSS,
			self::CUSTOM_JS,
			self::ARCHIVED,
		);
	}

	/**
	 * The two keys that hold code instead of content.
	 *
	 * Se listan aparte porque en todas partes se tratan distinto: no se
	 * escapan al salir, no los escribe cualquiera que edite el evento y viven
	 * dentro del recuadro de «Solo administración».
	 *
	 * @return string[]
	 */
	public static function code_keys(): array {
		return array( self::CUSTOM_CSS, self::CUSTOM_JS );
	}

	/**
	 * Closed vocabulary of satellite page types.
	 *
	 * @return array<string, string> slug => etiqueta.
	 */
	public static function section_types(): array {
		return array(
			'programa'          => 'Programa',
			'ponentes'          => 'Ponentes',
			'inscripcion'       => 'Inscripción',
			'multimedia'        => 'Multimedia',
			'contacto'          => 'Contacto',
			'actividades'       => 'Actividades',
			'encuesta'          => 'Encuesta',
			'participacion'     => 'Participación',
			'preguntas'         => 'Preguntas',
			'directo'           => 'Emisión en directo',
			self::SECTION_OTHER => 'Otra',
		);
	}

	/**
	 * Closed vocabulary of section icons.
	 *
	 * Los dibujos están en {@see \Evt\PublicFront\SectionIcons}; aquí solo el
	 * nombre, que es lo que se guarda.
	 *
	 * @return array<string, string> slug => etiqueta.
	 */
	public static function section_icons(): array {
		return array(
			'calendar'  => 'Calendario',
			'people'    => 'Personas',
			'form'      => 'Formulario',
			'image'     => 'Imagen',
			'mail'      => 'Sobre',
			'bulb'      => 'Bombilla',
			'chart'     => 'Gráfica',
			'megaphone' => 'Megáfono',
			'chat'      => 'Bocadillo',
			'play'      => 'Vídeo',
			'pin'       => 'Ubicación',
			'star'      => 'Estrella',
			'info'      => 'Información',
			'page'      => 'Página',
		);
	}

	/**
	 * The icon a section gets from its type until someone picks another.
	 *
	 * @param string $type Section type slug.
	 * @return string Icon slug.
	 */
	public static function default_icon( string $type ): string {
		$por_tipo = array(
			'programa'      => 'calendar',
			'ponentes'      => 'people',
			'inscripcion'   => 'form',
			'multimedia'    => 'image',
			'contacto'      => 'mail',
			'actividades'   => 'bulb',
			'encuesta'      => 'chart',
			'participacion' => 'megaphone',
			'preguntas'     => 'chat',
			'directo'       => 'play',
		);
		return $por_tipo[ $type ] ?? 'page';
	}

	/**
	 * Closed vocabulary of derived event states.
	 *
	 * @return array<string, string> slug => etiqueta.
	 */
	public static function states(): array {
		return array(
			self::STATE_UPCOMING => 'Próximo',
			self::STATE_OPEN     => 'Abierto',
			self::STATE_FINISHED => 'Finalizado',
		);
	}

	/**
	 * Closed vocabulary of typefaces.
	 *
	 * El formulario anterior ofrece las 1.461 familias de Google Fonts. Aquí
	 * la lista sigue cerrada: las cinco elegidas por legibilidad, más las que
	 * usan de verdad los eventos publicados —está medido, y el material está
	 * en `.local/`—, para que un evento migrado se vea igual. «La del tema» es
	 * el valor vacío y no carga nada; «Times New Roman» es del sistema y
	 * tampoco. Las demás se cargan con {@see \Evt\PublicFront\Fonts}.
	 *
	 * @return array<string, string> slug => nombre de la familia.
	 */
	public static function fonts(): array {
		return array(
			self::FONT_DEFAULT   => 'La del tema',
			'open-sans'          => 'Open Sans',
			'lato'               => 'Lato',
			'montserrat'         => 'Montserrat',
			'source-serif'       => 'Source Serif 4',
			'merriweather'       => 'Merriweather',
			'roboto'             => 'Roboto',
			'nunito'             => 'Nunito',
			'cantarell'          => 'Cantarell',
			'dosis'              => 'Dosis',
			'oxanium'            => 'Oxanium',
			'cormorant-garamond' => 'Cormorant Garamond',
			'alata'              => 'Alata',
			'aboreto'            => 'Aboreto',
			'aldrich'            => 'Aldrich',
			'dela-gothic-one'    => 'Dela Gothic One',
			'fredoka-one'        => 'Fredoka One',
			'new-tegomin'        => 'New Tegomin',
			'nosifer'            => 'Nosifer',
			'oi'                 => 'Oi',
			'patrick-hand'       => 'Patrick Hand',
			'patua-one'          => 'Patua One',
			'times'              => 'Times New Roman',
		);
	}

	/**
	 * Closed vocabulary of image shapes for people's photographs.
	 *
	 * @return array<string, string> slug => etiqueta.
	 */
	public static function image_shapes(): array {
		return array(
			self::SHAPE_SQUARE => 'Cuadrada',
			self::SHAPE_CIRCLE => 'Redonda',
		);
	}

	/**
	 * Closed vocabulary of header separators.
	 *
	 * El formulario anterior ofrece hoy las veinticinco siluetas del tema. Se
	 * conservan las que usan de verdad los eventos publicados, cada una con su
	 * silueta propia; las variantes de una misma forma se quedan en la forma.
	 * Una que llegue de la migración fuera de esta lista se queda sin
	 * separador, que es el valor vacío y el aspecto por defecto.
	 *
	 * @return array<string, string> slug => etiqueta.
	 */
	public static function separators(): array {
		return array(
			''          => 'Sin separador',
			'slant'     => 'Diagonal',
			'ramp'      => 'Rampa',
			'curve'     => 'Curva',
			'wave'      => 'Onda',
			'triangle'  => 'Triángulo',
			'waves'     => 'Ondas',
			'clouds'    => 'Nubes',
			'mountains' => 'Montañas',
			'graph'     => 'Gráfica',
			'arrow'     => 'Flecha',
		);
	}

	/**
	 * Closed vocabulary of programme layouts.
	 *
	 * Las dos que ofrece hoy el formulario anterior. El valor vacío son las
	 * pestañas, que es lo que usan casi todos los eventos.
	 *
	 * @return array<string, string> slug => etiqueta.
	 */
	public static function programme_layouts(): array {
		return array(
			''                     => 'Pestañas por día',
			self::LAYOUT_ACCORDION => 'Acordeón',
		);
	}

	/**
	 * Keep a value only when the closed list has it.
	 *
	 * @param mixed                 $value    Raw value.
	 * @param array<string, string> $allowed  One of the vocabularies above.
	 * @param string                $fallback What to return otherwise.
	 * @return string
	 */
	public static function in_list( $value, array $allowed, string $fallback = '' ): string {
		$value = trim( (string) $value );
		return isset( $allowed[ $value ] ) ? $value : $fallback;
	}
}
