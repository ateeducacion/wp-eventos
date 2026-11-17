<?php
/**
 * The emails of a signup: the confirmation, with the personal link.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

use Evt\Meta\EventMetaKeys;
use Evt\Meta\RegistrationMetaKeys;
use Evt\Domain\DateRange;

/**
 * El único sitio del aplicativo que llama a `wp_mail()` (ADR-0051).
 *
 * Un solo correo, el de la inscripción, con el **enlace personal** que deja
 * volver a ella para elegir o cambiar taller. Sale en tres casos: al
 * inscribirse, cuando la organización inscribe a alguien y cuando la persona lo
 * pide otra vez porque lo perdió.
 *
 * **Texto plano**: sin plantilla que escapar, sin imágenes remotas y sin que
 * cada programa de correo lo pinte a su manera. El remitente es el de
 * WordPress —el que tenga configurado el sitio, con su extensión de correo si
 * la hay—; aquí no se escribe ninguna dirección, porque el repositorio es
 * público (ADR-0030). Quien necesite otra cosa la cambia con el filtro
 * `evt_mail`, que también puede devolver `false` para no mandar nada.
 *
 * Que el correo falle **no deshace la inscripción**: la persona está inscrita y
 * se le dice que guarde el enlace de la pantalla.
 */
final class SignupMail {

	/**
	 * Filter every email goes through before sending.
	 */
	public const HOOK = 'evt_mail';

	/**
	 * Why the email is sent.
	 */
	public const SIGNED_UP = 'alta';
	public const BY_ORG    = 'organizacion';
	public const RESENT    = 'recordatorio';

	/**
	 * How long a registration waits before its link can be resent, in seconds.
	 *
	 * Sin esto, el formulario de «recuperar el enlace» sería una forma de
	 * llenarle el buzón a cualquiera que esté inscrito.
	 */
	public const RESEND_WAIT = 600;

	/**
	 * Send the confirmation of one registration.
	 *
	 * @param int    $registration_id Registration post ID.
	 * @param string $why             One of SIGNED_UP, BY_ORG, RESENT.
	 * @return bool Whether it was handed to the mail server.
	 */
	public static function confirmation( int $registration_id, string $why = self::SIGNED_UP ): bool {
		$event_id = (int) get_post_field( 'post_parent', $registration_id );
		if ( $event_id <= 0 ) {
			return false;
		}
		$para = (string) get_post_meta( $registration_id, RegistrationMetaKeys::REG_EMAIL, true );
		if ( ! is_email( $para ) ) {
			return false;
		}
		$correo = self::message( $registration_id, $why );
		return self::send( $para, $correo['subject'], $correo['body'], $registration_id );
	}

	/**
	 * Resend the link to whoever owns these registrations, at most every few minutes.
	 *
	 * @param int[] $registration_ids Registrations found by the email typed.
	 * @return void
	 */
	public static function resend( array $registration_ids ): void {
		foreach ( $registration_ids as $id ) {
			$clave = 'evt_resent_' . (int) $id;
			if ( false !== get_transient( $clave ) ) {
				continue;
			}
			set_transient( $clave, 1, self::RESEND_WAIT );
			self::confirmation( (int) $id, self::RESENT );
		}
	}

	/**
	 * The subject and the body of a confirmation, without sending it.
	 *
	 * @param int    $registration_id Registration post ID.
	 * @param string $why             One of SIGNED_UP, BY_ORG, RESENT.
	 * @return array{subject:string, body:string}
	 */
	public static function message( int $registration_id, string $why = self::SIGNED_UP ): array {
		$event_id = (int) get_post_field( 'post_parent', $registration_id );
		$evento   = self::plain( (string) get_the_title( $event_id ) );
		$meta     = Registrations::meta( $registration_id );
		$nombre   = trim( $meta[ RegistrationMetaKeys::REG_NAME ] );

		$lineas   = array();
		$lineas[] = '' !== $nombre ? 'Hola, ' . $nombre . ':' : 'Hola:';
		$lineas[] = '';
		if ( self::BY_ORG === $why ) {
			$lineas[] = 'La organización de «' . $evento . '» le ha inscrito en el evento.';
		} elseif ( self::RESENT === $why ) {
			$lineas[] = 'Nos ha pedido de nuevo el enlace de su inscripción en «' . $evento . '».';
		} else {
			$lineas[] = 'Su inscripción en «' . $evento . '» está registrada.';
		}
		$lineas[] = '';

		$lineas[] = 'Referencia: ' . (string) get_post_field( 'post_title', $registration_id );
		$fechas   = DateRange::of(
			(string) get_post_meta( $event_id, EventMetaKeys::START_DATE, true ),
			(string) get_post_meta( $event_id, EventMetaKeys::END_DATE, true )
		);
		if ( '' !== $fechas ) {
			$lineas[] = 'Fechas: ' . $fechas;
		}
		$rotulos  = Registrations::workshop_labels( $event_id );
		$talleres = array();
		foreach ( Registrations::workshops_of( $registration_id ) as $taller ) {
			if ( isset( $rotulos[ $taller ] ) ) {
				$talleres[] = self::plain( $rotulos[ $taller ] );
			}
		}
		if ( array() !== $talleres ) {
			$lineas[] = ( count( $talleres ) > 1 ? 'Talleres: ' : 'Taller: ' ) . implode( '; ', $talleres );
		}

		$lineas[] = '';
		$lineas[] = 'Para consultar su inscripción, y elegir o cambiar taller mientras el plazo esté abierto, entre en:';
		$lineas[] = SignupForm::link( $event_id, (string) get_post_meta( $registration_id, RegistrationMetaKeys::REG_TOKEN, true ) );
		$lineas[] = '';
		$lineas[] = 'El enlace es personal: quien lo tenga puede ver y cambiar su inscripción. No reenvíe este correo.';
		$lineas[] = '';
		$lineas[] = 'Este correo es un aviso automático: no hace falta responderlo.';

		return array(
			'subject' => 'Inscripción en «' . $evento . '»',
			'body'    => implode( "\n", $lineas ),
		);
	}

	/**
	 * Hand one plain-text email to WordPress.
	 *
	 * @param string $to              Recipient.
	 * @param string $subject         Subject.
	 * @param string $body            Body.
	 * @param int    $registration_id Registration it is about, for the filter.
	 * @return bool
	 */
	private static function send( string $to, string $subject, string $body, int $registration_id ): bool {
		$correo = array(
			'to'      => $to,
			// Una línea: un salto en el asunto es una cabecera inyectada.
			'subject' => trim( (string) preg_replace( '/[\r\n]+/', ' ', $subject ) ),
			'body'    => $body,
			'headers' => array( 'Content-Type: text/plain; charset=UTF-8' ),
		);

		/**
		 * Filter one email of the application, or return false to drop it.
		 *
		 * @param array{to:string, subject:string, body:string, headers:string[]}|false $correo          The email.
		 * @param int                                                                    $registration_id Registration it is about.
		 */
		$correo = apply_filters( self::HOOK, $correo, $registration_id );
		if ( ! is_array( $correo ) ) {
			return false;
		}

		try {
			return (bool) wp_mail(
				(string) ( $correo['to'] ?? '' ),
				(string) ( $correo['subject'] ?? '' ),
				(string) ( $correo['body'] ?? '' ),
				(array) ( $correo['headers'] ?? array() )
			);
		} catch ( \Throwable $e ) {
			// Un servidor de correo caído no tumba una inscripción ya guardada.
			return false;
		}
	}

	/**
	 * A title as plain text: no tags, no entities.
	 *
	 * @param string $texto Text.
	 * @return string
	 */
	private static function plain( string $texto ): string {
		return html_entity_decode( wp_strip_all_tags( $texto ), ENT_QUOTES, 'UTF-8' );
	}
}
