<?php
/**
 * The `inscripcion` block of the public page of an event.
 *
 * @package Evt
 */

namespace Evt\PublicFront\Block;

use Evt\Meta\RegistrationMetaKeys;
use Evt\PublicFront\Registrations;
use Evt\PublicFront\SignupForm;

/**
 * El formulario de inscripción, en la sección de tipo `inscripcion`.
 *
 * Solo pinta: no lee la petición para decidir nada, no escribe y no valida. De
 * eso va {@see \Evt\PublicFront\SignupForm}, y de guardar
 * {@see \Evt\PublicFront\Registrations}.
 *
 * Son los siete campos del núcleo más las preguntas del evento (ADR-0031), y
 * nada más: aquí no hay lógica condicional que esconda ni enseñe nada, porque
 * no la hay en el modelo. **Quien no se queda a comer ve igualmente la pregunta
 * de las intolerancias**, y eso está aceptado a sabiendas: la alternativa era
 * el constructor de formularios del que se sale.
 */
final class SignupBlock {

	/**
	 * Name of the block; also the CSS modifier of its section.
	 */
	public const NAME = 'inscripcion';

	/**
	 * Dónde pinta: detrás del texto y del cartel, que son la explicación, y
	 * delante de las tarjetas de las demás páginas.
	 */
	public const PRIORITY = 25;

	/**
	 * The block, or nothing when this is not a signup section.
	 *
	 * @param array<string, mixed> $m What EventView::model() decided.
	 * @return string
	 */
	public static function html( array $m ): string {
		if ( self::NAME !== ( $m['section_type'] ?? '' ) ) {
			return '';
		}

		$evento = (int) ( $m['event_id'] ?? 0 );
		if ( $evento <= 0 ) {
			return '';
		}

		$aviso = self::notice();
		$mia   = SignupForm::current( $evento );

		// Ya inscrita: lo que queda es su taller, si hay plazo.
		if ( $mia > 0 ) {
			return $aviso . self::mine( $evento, $mia );
		}

		if ( ! SignupForm::is_open( $evento ) ) {
			return $aviso . '<p class="evt-ins__cerrada">La inscripción de este evento no está abierta.</p>';
		}

		return $aviso . self::form( $evento );
	}

	/**
	 * What the last submit left to say.
	 *
	 * @return string
	 */
	private static function notice(): string {
		$aviso = SignupForm::notice();
		if ( null === $aviso ) {
			return '';
		}
		return sprintf(
			'<p class="evt-aviso evt-aviso--%1$s" role="alert">%2$s</p>',
			esc_attr( $aviso['level'] ),
			esc_html( $aviso['message'] )
		);
	}

	/**
	 * The signup form itself.
	 *
	 * @param int $evento Event post ID.
	 * @return string
	 */
	private static function form( int $evento ): string {
		$html  = '<form class="evt-ins" method="post">';
		$html .= self::hidden( $evento, SignupForm::OP_SIGNUP );

		$html .= '<fieldset class="evt-ins__nucleo"><legend>Sus datos</legend>';
		foreach ( self::core_fields() as $nombre => $campo ) {
			$html .= self::field( $nombre, $campo );
		}
		$html .= self::centre();
		$html .= '</fieldset>';

		$html .= self::questions( $evento );
		$html .= self::workshops( $evento, 0 );
		$html .= self::consent( $evento );

		$html .= '<p class="evt-ins__enviar"><button type="submit" class="evt-btn evt-btn--primario">Inscribirme</button></p>';
		$html .= '</form>';

		return $html;
	}

	/**
	 * What somebody already signed up sees.
	 *
	 * @param int $evento Event post ID.
	 * @param int $mia    Their registration.
	 * @return string
	 */
	private static function mine( int $evento, int $mia ): string {
		$meta   = Registrations::meta( $mia );
		$nombre = trim( $meta[ RegistrationMetaKeys::REG_NAME ] . ' ' . $meta[ RegistrationMetaKeys::REG_SURNAME ] );

		$html  = '<div class="evt-ins evt-ins--mia">';
		$html .= '<p class="evt-ins__hecha">Su inscripción está registrada, ' . esc_html( $nombre ) . '.</p>';

		if ( ! SignupForm::workshops_open( $evento ) ) {
			$html .= '<p class="evt-ins__cerrada">El plazo para elegir taller no está abierto.</p>';
			return $html . '</div>';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo para devolverlo en el formulario; la escritura lleva su nonce.
		$token = isset( $_GET[ SignupForm::ARG_TOKEN ] ) ? sanitize_text_field( wp_unslash( $_GET[ SignupForm::ARG_TOKEN ] ) ) : '';

		$html .= '<form class="evt-ins__taller" method="post">';
		$html .= self::hidden( $evento, SignupForm::OP_WORKSHOP );
		$html .= sprintf( '<input type="hidden" name="%1$s" value="%2$s">', esc_attr( SignupForm::FIELD_TOKEN ), esc_attr( $token ) );
		$html .= self::workshops( $evento, $mia );
		$html .= '<p class="evt-ins__enviar"><button type="submit" class="evt-btn evt-btn--primario">Guardar el taller</button></p>';
		$html .= '</form></div>';

		return $html;
	}

	/**
	 * The hidden fields every submit carries.
	 *
	 * @param int    $evento Event post ID.
	 * @param string $op     Operation.
	 * @return string
	 */
	private static function hidden( int $evento, string $op ): string {
		return sprintf(
			'<input type="hidden" name="%1$s" value="%2$s"><input type="hidden" name="%3$s" value="%4$d">%5$s',
			esc_attr( SignupForm::FIELD_OP ),
			esc_attr( $op ),
			esc_attr( SignupForm::FIELD_EVENT ),
			$evento,
			wp_nonce_field( SignupForm::NONCE_ACTION, SignupForm::NONCE_FIELD, true, false )
		);
	}

	/**
	 * The six typed fields of the core, with their label and input type.
	 *
	 * @return array<string, array{label:string, type:string, required:bool, autocomplete:string}>
	 */
	private static function core_fields(): array {
		return array(
			'tax_id'  => array(
				'label'        => 'Documento de identidad',
				'type'         => 'text',
				'required'     => true,
				'autocomplete' => 'off',
			),
			'name'    => array(
				'label'        => 'Nombre',
				'type'         => 'text',
				'required'     => true,
				'autocomplete' => 'given-name',
			),
			'surname' => array(
				'label'        => 'Apellidos',
				'type'         => 'text',
				'required'     => true,
				'autocomplete' => 'family-name',
			),
			'email'   => array(
				'label'        => 'Correo electrónico',
				'type'         => 'email',
				'required'     => true,
				'autocomplete' => 'email',
			),
			'phone'   => array(
				'label'        => 'Teléfono',
				'type'         => 'tel',
				'required'     => false,
				'autocomplete' => 'tel',
			),
		);
	}

	/**
	 * One field of the core.
	 *
	 * @param string                                                               $nombre Field name.
	 * @param array{label:string, type:string, required:bool, autocomplete:string} $campo  Its shape.
	 * @return string
	 */
	private static function field( string $nombre, array $campo ): string {
		$id = 'evt-ins-' . $nombre;
		return sprintf(
			'<p class="evt-campo"><label for="%1$s">%2$s%3$s</label>'
				. '<input type="%4$s" id="%1$s" name="%5$s" autocomplete="%6$s"%7$s></p>',
			esc_attr( $id ),
			esc_html( $campo['label'] ),
			$campo['required'] ? ' <span class="evt-campo__obl" aria-hidden="true">*</span>' : '',
			esc_attr( $campo['type'] ),
			esc_attr( $nombre ),
			esc_attr( $campo['autocomplete'] ),
			$campo['required'] ? ' required' : ''
		);
	}

	/**
	 * The centre, from the catalogue and never typed.
	 *
	 * Sin catálogo no se deja teclear: un centro tecleado es el mismo centro
	 * escrito de cinco maneras, y a partir de ahí no hay recuento ni cruce que
	 * valga (ADR-0031). Se dice, en vez de fabricar variantes en silencio.
	 *
	 * @return string
	 */
	private static function centre(): string {
		$centros = Registrations::centres();
		if ( array() === $centros ) {
			return '<p class="evt-aviso evt-aviso--error">No hay catálogo de centros configurado, '
				. 'así que no se puede completar la inscripción. Avise a quien organiza el evento.</p>';
		}

		$html = '<p class="evt-campo"><label for="evt-ins-centre">Centro <span class="evt-campo__obl" aria-hidden="true">*</span></label>'
			. '<select id="evt-ins-centre" name="centre" required><option value="">Elija su centro</option>';
		foreach ( $centros as $centro ) {
			$html .= sprintf( '<option value="%1$s">%1$s</option>', esc_attr( $centro ) );
		}
		return $html . '</select></p>';
	}

	/**
	 * The questions of this event, if it has any.
	 *
	 * @param int $evento Event post ID.
	 * @return string
	 */
	private static function questions( int $evento ): string {
		$preguntas = Registrations::questions( $evento );
		if ( array() === $preguntas ) {
			return '';
		}

		$html = '<fieldset class="evt-ins__preguntas"><legend>Sobre este evento</legend>';
		foreach ( $preguntas as $pregunta ) {
			$html .= self::question( $pregunta );
		}
		return $html . '</fieldset>';
	}

	/**
	 * One question, by its type.
	 *
	 * @param array<string, mixed> $p Normalised question.
	 * @return string
	 */
	private static function question( array $p ): string {
		$id     = 'evt-q-' . $p['id'];
		$name   = SignupForm::FIELD_ANSWERS . '[' . $p['id'] . ']';
		$rotulo = esc_html( (string) $p['label'] ) . ( $p['required'] ? ' <span class="evt-campo__obl" aria-hidden="true">*</span>' : '' );

		if ( 'check' === $p['type'] ) {
			return sprintf(
				'<p class="evt-campo evt-campo--casilla"><label for="%1$s"><input type="checkbox" id="%1$s" name="%2$s" value="1"%3$s> %4$s</label></p>',
				esc_attr( $id ),
				esc_attr( $name ),
				$p['required'] ? ' required' : '',
				$rotulo
			);
		}

		if ( 'text' === $p['type'] ) {
			return sprintf(
				'<p class="evt-campo"><label for="%1$s">%2$s</label><input type="text" id="%1$s" name="%3$s" maxlength="250"%4$s></p>',
				esc_attr( $id ),
				$rotulo,
				esc_attr( $name ),
				$p['required'] ? ' required' : ''
			);
		}

		$varias = 'many' === $p['type'];
		$html   = '<fieldset class="evt-campo evt-campo--opciones"><legend>' . $rotulo . '</legend>';
		foreach ( (array) $p['options'] as $i => $opcion ) {
			$html .= sprintf(
				'<label class="evt-opcion"><input type="%1$s" name="%2$s" value="%3$s"> %3$s</label>',
				$varias ? 'checkbox' : 'radio',
				esc_attr( $varias ? $name . '[]' : $name ),
				esc_attr( $opcion )
			);
			unset( $i );
		}
		return $html . '</fieldset>';
	}

	/**
	 * The workshops still choosable, when the window is open.
	 *
	 * Un taller lleno no sale. Que no salga **no es el control**: la página se
	 * pudo pintar hace cinco minutos y la comprobación que manda es la del
	 * candado al guardar (ADR-0033).
	 *
	 * @param int $evento Event post ID.
	 * @param int $mia    Registration choosing, 0 for a new signup.
	 * @return string
	 */
	private static function workshops( int $evento, int $mia ): string {
		if ( ! SignupForm::workshops_open( $evento ) ) {
			return '';
		}

		$opciones = Registrations::choices( $evento, $mia );
		if ( array() === $opciones ) {
			return '<p class="evt-ins__sin-talleres">Ahora mismo no queda ningún taller con plazas libres.</p>';
		}

		$html  = '<fieldset class="evt-ins__talleres"><legend>Taller</legend>';
		$html .= '<label class="evt-opcion"><input type="radio" name="evt_workshop" value="0"' . ( 0 === $mia ? ' checked' : '' ) . '> No elijo taller</label>';
		foreach ( $opciones as $taller ) {
			$libres = $taller['seats'] > 0 ? max( 0, $taller['seats'] - $taller['taken'] ) : 0;
			$html  .= sprintf(
				'<label class="evt-opcion"><input type="radio" name="evt_workshop" value="%1$d"%2$s> %3$s%4$s</label>',
				$taller['id'],
				$taller['mine'] ? ' checked' : '',
				esc_html( $taller['title'] ),
				$taller['seats'] > 0
					? ' <span class="evt-plazas">(' . (int) $libres . ' plazas libres)</span>'
					: ''
			);
		}
		return $html . '</fieldset>';
	}

	/**
	 * The consent: the two texts and one checkbox (ADR-0020).
	 *
	 * @param int $evento Event post ID.
	 * @return string
	 */
	private static function consent( int $evento ): string {
		$privacidad = (string) get_post_meta( $evento, RegistrationMetaKeys::CONSENT_PRIVACY, true );
		$imagen     = (string) get_post_meta( $evento, RegistrationMetaKeys::CONSENT_IMAGE, true );

		$html = '<fieldset class="evt-ins__consentimiento"><legend>Protección de datos</legend>';
		foreach ( array(
			'Información sobre el tratamiento de sus datos' => $privacidad,
			'Consentimiento informado' => $imagen,
		) as $titulo => $texto ) {
			if ( '' === trim( $texto ) ) {
				continue;
			}
			$html .= '<details class="evt-consent__doc"><summary>' . esc_html( $titulo ) . '</summary>'
				. wp_kses_post( $texto ) . '</details>';
		}

		$html .= '<p class="evt-campo evt-campo--casilla"><label for="evt-ins-consent">'
			. '<input type="checkbox" id="evt-ins-consent" name="consent" value="1" required> '
			. 'He leído la información sobre el tratamiento de mis datos y el consentimiento informado, y los acepto '
			. '<span class="evt-campo__obl" aria-hidden="true">*</span></label>'
			. '<small>Obligatorio para inscribirse. Se guarda la versión exacta que ha aceptado y el momento.</small></p>';

		return $html . '</fieldset>';
	}
}
