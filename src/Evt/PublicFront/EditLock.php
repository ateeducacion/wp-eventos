<?php
/**
 * Native WordPress edit locks, shared by the application and wp-admin.
 *
 * @package Evt
 */

namespace Evt\PublicFront;

use Evt\Access\EventAccess;

/**
 * Que dos personas no se pisen editando el mismo evento.
 *
 * No se inventa ningún bloqueo: se usa **el nativo de WordPress** —la meta
 * `_edit_lock`, `wp_check_post_lock()` y `wp_set_post_lock()`—, que es
 * exactamente el mismo que usa el escritorio. La ventaja es la razón de haberlo
 * elegido: si alguien abre el evento en el editor de WordPress y otra persona
 * en el taller del aplicativo, **se ven**. Un bloqueo propio no lo haría, y
 * habría dos verdades sobre quién está editando.
 *
 * El bloqueo es **del evento raíz**, no de la página que se tenga abierta: dos
 * personas tocando dos secciones distintas del mismo evento se pisan igual,
 * porque comparten la navegación, el orden y la apariencia. Por eso todo pasa
 * por {@see EventAccess::root_id()} y quien pregunta no tiene que acordarse.
 *
 * Las cuatro piezas:
 *
 * | {@see owner()}             | quién lo tiene, sin caducar               |
 * | {@see require_available()} | antes de escribir nada: si hay dueño, 409 |
 * | {@see claim()}             | tomarlo al abrir, o decir quién lo tiene  |
 * | {@see release()}           | soltar **solo el propio**                 |
 *
 * Y el aviso ({@see render()}), que dice el nombre de quien lo tiene —no
 * «alguien»— y ofrece dos salidas: consultarlo, o tomar posesión por POST con
 * su nonce.
 */
final class EditLock {

	/**
	 * Hidden field naming the operation, so nothing else picks the POST up.
	 */
	public const FIELD_DO = 'evt_lock_do';

	/**
	 * Hidden field with the post the takeover is about.
	 */
	public const FIELD_POST = 'evt_lock_post';

	/**
	 * Nonce field of the takeover form.
	 */
	public const NONCE_FIELD = 'evt_lock_nonce';

	/**
	 * The only operation this class accepts.
	 */
	public const OP_TAKEOVER = 'takeover';

	/**
	 * Nonce action of the takeover of one event.
	 *
	 * @param int $event_id Event post ID.
	 * @return string
	 */
	public static function nonce_action( int $event_id ): string {
		return 'evt_lock_takeover_' . $event_id;
	}

	/**
	 * Who else holds an unexpired lock on the event this post belongs to.
	 *
	 * `wp_check_post_lock()` contesta «otra persona», así que a quien tiene el
	 * bloqueo le dice que no hay ninguno: es justo lo que hace falta aquí.
	 *
	 * @param int $post_id Event, or any of its satellite pages.
	 * @return int User ID, 0 when the event is free.
	 */
	public static function owner( int $post_id ): int {
		$event_id = EventAccess::root_id( $post_id );
		if ( $event_id <= 0 ) {
			return 0;
		}
		require_once ABSPATH . 'wp-admin/includes/post.php';
		return (int) wp_check_post_lock( $event_id );
	}

	/**
	 * Reject the write before a single field, term or status can change.
	 *
	 * Va **antes** de escribir y no después, que es lo único que sirve: quien
	 * perdió el bloqueo tiene la pantalla vieja delante y su envío traería
	 * catorce campos con lo de hace media hora.
	 *
	 * @param int $post_id Event, or any of its satellite pages.
	 * @return void
	 */
	public static function require_available( int $post_id ): void {
		$owner = self::owner( $post_id );
		if ( $owner <= 0 ) {
			return;
		}
		wp_die(
			esc_html(
				sprintf(
					'%s está editando este evento ahora mismo, aquí o en el escritorio de WordPress. Vuelva atrás y tome posesión antes de guardar: así no se pisa lo que la otra persona esté escribiendo.',
					self::name( $owner )
				)
			),
			'Edición bloqueada',
			array(
				'response'  => 409,
				'back_link' => true,
			)
		);
	}

	/**
	 * How the event stands right now: libre, o de quién es. No escribe nada.
	 *
	 * Esto es lo que llama el `model()` de cada pantalla, y por eso no toma el
	 * bloqueo: el modelo decide qué se pinta y no muta.
	 * Tomarlo es de {@see claim()}, que va en el `render()`, cuando la pantalla
	 * se le está enseñando a alguien de verdad.
	 *
	 * A quien solo puede consultar —un evento marcado como histórico, por
	 * ejemplo— no se le cuenta ningún bloqueo: no va a escribir, así que ni le
	 * estorba ni tiene por qué quitárselo a quien sí puede.
	 *
	 * @param int  $post_id  Event, or any of its satellite pages.
	 * @param bool $can_edit Whether this person may write here at all.
	 * @return array{event_id:int, owner:int, name:string, lock:string}
	 */
	public static function status( int $post_id, bool $can_edit ): array {
		$event_id = EventAccess::root_id( $post_id );
		if ( $event_id <= 0 || ! $can_edit ) {
			return self::none();
		}
		$owner = self::owner( $event_id );

		return array(
			'event_id' => $event_id,
			'owner'    => $owner,
			'name'     => $owner > 0 ? self::name( $owner ) : '',
			// Lo que el Heartbeat renueva; lo rellena claim() y solo para quien
			// tenga el bloqueo, porque renovar uno ajeno no tendría sentido.
			'lock'     => '',
		);
	}

	/**
	 * Take the lock of a free event, so the next person sees it taken.
	 *
	 * Es lo mismo que hace el escritorio al abrir el editor, y es el mismo
	 * bloqueo: por eso las dos pantallas se ven.
	 *
	 * @param array{event_id:int, owner:int, name:string, lock:string} $lock What status() returned.
	 * @return array{event_id:int, owner:int, name:string, lock:string}
	 */
	public static function claim( array $lock ): array {
		if ( (int) $lock['event_id'] <= 0 || (int) $lock['owner'] > 0 ) {
			return $lock;
		}
		require_once ABSPATH . 'wp-admin/includes/post.php';
		$puesto       = wp_set_post_lock( (int) $lock['event_id'] );
		$lock['lock'] = is_array( $puesto ) ? implode( ':', $puesto ) : '';

		return $lock;
	}

	/**
	 * No lock to talk about: nothing to paint and nothing to renew.
	 *
	 * @return array{event_id:int, owner:int, name:string, lock:string}
	 */
	public static function none(): array {
		return array(
			'event_id' => 0,
			'owner'    => 0,
			'name'     => '',
			'lock'     => '',
		);
	}

	/**
	 * Release the lock, and only when it still is this person's own.
	 *
	 * Se compara el usuario que trae la meta antes de borrarla: si mientras
	 * tanto otra persona tomó posesión, el bloqueo es suyo y no se le quita.
	 * Se le pasa el valor exacto a `delete_post_meta()` por lo mismo, que es lo
	 * que hace la comprobación atómica en la base de datos.
	 *
	 * @param int $post_id Event, or any of its satellite pages.
	 * @return void
	 */
	public static function release( int $post_id ): void {
		$event_id = EventAccess::root_id( $post_id );
		if ( $event_id <= 0 ) {
			return;
		}
		$lock  = (string) get_post_meta( $event_id, '_edit_lock', true );
		$trozo = explode( ':', $lock );
		if ( isset( $trozo[1] ) && get_current_user_id() === (int) $trozo[1] ) {
			delete_post_meta( $event_id, '_edit_lock', $lock );
		}
	}

	/**
	 * Apply a «Tomar posesión» POST.
	 *
	 * Por POST y con su nonce, como toda mutación del aplicativo: tomarle el
	 * evento a otra persona por un enlace pegado en un correo no pasa. Y con
	 * `EventAccess::can_edit()` comprobado: el nonce dice que el envío salió de
	 * nuestra pantalla, no que quien lo manda pueda editar este evento.
	 *
	 * Lo engancha {@see EventWorkspace::register()}, que es de donde cuelga el
	 * resto de las mutaciones del taller.
	 *
	 * @return void
	 */
	public static function handle(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- se comprueba abajo, en cuanto se sabe de qué evento es.
		$op = sanitize_key( wp_unslash( (string) ( $_POST[ self::FIELD_DO ] ?? '' ) ) );
		if ( self::OP_TAKEOVER !== $op ) {
			return;
		}
		$post_id = absint( wp_unslash( $_POST[ self::FIELD_POST ] ?? 0 ) );
		$nonce   = sanitize_text_field( wp_unslash( (string) ( $_POST[ self::NONCE_FIELD ] ?? '' ) ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$event_id = EventAccess::root_id( $post_id );
		if ( $event_id <= 0 || false === wp_verify_nonce( $nonce, self::nonce_action( $event_id ) ) ) {
			return;
		}

		if ( ! EventAccess::can_edit( get_current_user_id(), $event_id ) ) {
			wp_die(
				esc_html( EventAccess::why_not_editable( get_current_user_id(), $event_id ) ),
				'Sin permiso',
				array(
					'response'  => 403,
					'back_link' => true,
				)
			);
		}

		require_once ABSPATH . 'wp-admin/includes/post.php';
		wp_set_post_lock( $event_id );

		// A la misma pantalla desde la que se pidió: el aviso sale tanto en el
		// taller como en el formulario de una sección, y desde los dos se
		// vuelve a lo que se estaba haciendo.
		Shell::leave( Shell::back_url( 'events' ) );
	}

	/**
	 * The takeover notice: who has the event, and the two ways out.
	 *
	 * Un `<dialog>` nativo y no SweetAlert2, y la diferencia importa: esto no
	 * es una confirmación —lo de SweetAlert2 en este aplicativo—, es el estado
	 * de la pantalla, y tiene que verse **también sin JavaScript**. Un
	 * `<dialog open>` lo pinta el navegador solo; SweetAlert2 no existe hasta
	 * que carga su guion, y su degradación es el `confirm()` del navegador, que
	 * aquí sería un modal bloqueante sin salida. El mismo `<dialog>` es el que
	 * el Heartbeat abre con `showModal()` cuando el bloqueo se pierde sin
	 * recargar.
	 *
	 * @param array{event_id:int, owner:int, name:string, lock:string} $lock What claim() returned.
	 * @return string Empty when there is no event to lock.
	 */
	public static function render( array $lock ): string {
		$event_id = (int) $lock['event_id'];
		if ( $event_id <= 0 ) {
			return '';
		}
		$owner = (int) $lock['owner'];

		// El guion del bloqueo vive en `assets/js/evt-app.js` y necesita el
		// Heartbeat del núcleo, que no se encola en la web pública.
		wp_enqueue_script( 'heartbeat' );

		ob_start();
		?>
		<div id="evt-edit-lock"
			data-post-id="<?php echo esc_attr( (string) $event_id ); ?>"
			data-lock="<?php echo esc_attr( (string) $lock['lock'] ); ?>"
			data-release-nonce="<?php echo esc_attr( wp_create_nonce( 'update-post_' . $event_id ) ); ?>"
			data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
			<dialog id="evt-lock-dialog" class="evt-dialogo evt-dialogo-bloqueo" aria-labelledby="evt-lock-title"<?php echo $owner > 0 ? ' open' : ''; ?>>
				<h2 id="evt-lock-title">Lo está editando otra persona</h2>
				<p>
					<strong id="evt-lock-owner"><?php echo esc_html( $owner > 0 ? (string) $lock['name'] : 'Otra persona' ); ?></strong>
					tiene abierto este evento ahora mismo, aquí o en el escritorio de WordPress.
				</p>
				<p>
					El bloqueo es del evento entero y no de una de sus páginas: las secciones, los
					datos y la apariencia se tocan a la vez, así que dos personas al mismo tiempo se
					pisarían.
				</p>
				<p>
					Puede esperar a que termine y consultarlo mientras tanto, o tomar posesión y
					editarlo usted: la otra persona dejará de poder guardar y verá este mismo aviso.
				</p>
				<form class="evt-accion" method="post" action="">
					<?php wp_nonce_field( self::nonce_action( $event_id ), self::NONCE_FIELD ); ?>
					<input type="hidden" name="<?php echo esc_attr( self::FIELD_DO ); ?>" value="<?php echo esc_attr( self::OP_TAKEOVER ); ?>" />
					<input type="hidden" name="<?php echo esc_attr( self::FIELD_POST ); ?>" value="<?php echo esc_attr( (string) $event_id ); ?>" />
					<a class="<?php echo esc_attr( Assets::button_class() ); ?>" href="<?php echo esc_url( self::events_url() ); ?>">Dejarlo y volver a mis eventos</a>
					<button type="submit" class="<?php echo esc_attr( Assets::button_class( true ) ); ?>">Tomar posesión</button>
				</form>
			</dialog>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Where «dejarlo» goes: the event list, or the site root as a last resort.
	 *
	 * @return string
	 */
	private static function events_url(): string {
		$url = Shell::url( 'events' );
		return '' !== $url ? $url : home_url( '/' );
	}

	/**
	 * The name of whoever holds the lock. Nunca «alguien».
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	private static function name( int $user_id ): string {
		$user = get_userdata( $user_id );
		if ( false === $user || '' === (string) $user->display_name ) {
			return 'Otra persona';
		}
		return (string) $user->display_name;
	}
}
