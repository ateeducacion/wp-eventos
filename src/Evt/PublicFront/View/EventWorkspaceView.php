<?php
/**
 * The event workshop, painted from what EventWorkspace::model() decided.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

use Evt\PublicFront\Assets;
use Evt\PublicFront\EventWorkspace;
use Evt\PublicFront\Shell;
use Evt\Taxonomy\EventTaxonomies;

/**
 * Solo pinta: no lee la petición, no consulta, no decide.
 *
 * Esta clase es el marco del taller —la cabecera del evento, las pestañas
 * internas y el aviso de lo último que se hizo— y le pasa el turno al panel
 * que toque.
 */
final class EventWorkspaceView {

	/**
	 * Paint the workshop.
	 *
	 * @param array<string, mixed> $m What EventWorkspace::model() returned.
	 * @return string
	 */
	public static function html( array $m ): string {
		if ( '' !== (string) $m['aviso'] ) {
			return Shell::render(
				'Taller del evento',
				'',
				Shell::notice( (string) $m['aviso_tipo'], (string) $m['aviso'] ) . self::back_link( $m )
			);
		}

		$flash = (array) $m['flash'];
		$panel = (string) $m['panel'];

		// El alta es el taller con una sola pestaña: no hay páginas, ni
		// ponentes, ni programa que enseñar de un evento que todavía no
		// existe, y una fila de pestañas apagadas solo invita a pulsarlas.
		if ( true === $m['nuevo'] ) {
			return self::new_event( $m );
		}

		ob_start();
		echo self::head( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		echo self::tabs( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.

		if ( '' !== (string) $flash['texto'] ) {
			echo Shell::notice( (string) $flash['tipo'], (string) $flash['texto'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		}

		echo self::archived_notice( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.

		// El interruptor va fuera de lo que se apaga, y antes: si estuviera
		// dentro del panel bloqueado, marcar un evento sería un viaje sin
		// vuelta ni siquiera para quien administra.
		if ( EventWorkspace::PANEL_SETTINGS === $panel ) {
			echo self::archive_switch( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		}

		// Solo lectura: un `fieldset` desactivado apaga de una vez todos los
		// controles que tenga dentro —también los de los formularios que hay
		// en él—, así que ningún panel tiene que enterarse de nada. Lo que se
		// puede seguir haciendo son enlaces, y esos no se tocan: entrar,
		// consultar y exportar sigue funcionando.
		$cerrado = true !== $m['can_edit'];
		if ( $cerrado ) {
			echo '<fieldset class="evt-solo-lectura" disabled><legend class="screen-reader-text">Evento en solo lectura</legend>';
		}

		if ( EventWorkspace::PANEL_SPEAKERS === $panel ) {
			echo EventSpeakersPanel::html( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		} elseif ( EventWorkspace::PANEL_PROGRAMME === $panel ) {
			echo EventProgrammePanel::html( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		} elseif ( EventWorkspace::PANEL_WORKSHOPS === $panel ) {
			echo EventWorkshopsPanel::html( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		} elseif ( EventWorkspace::PANEL_SIGNUP === $panel ) {
			echo EventSignupPanel::html( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		} elseif ( EventWorkspace::PANEL_PEOPLE === $panel ) {
			echo EventParticipantsPanel::html( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		} elseif ( EventWorkspace::PANEL_SETTINGS === $panel ) {
			echo EventDataPanel::html( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		} elseif ( EventWorkspace::PANEL_LOOK === $panel ) {
			echo EventAppearancePanel::html( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		} elseif ( EventWorkspace::PANEL_CODE === $panel ) {
			// Aquí no hace falta volver a comprobar la capacidad: sin ella la
			// pestaña no está en `panels()` y `model()` ya cayó en «Secciones».
			echo EventCodePanel::html( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		} else {
			echo EventSectionsPanel::html( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		}

		if ( $cerrado ) {
			echo '</fieldset>';
		}

		return Shell::render( '', '', (string) ob_get_clean() );
	}

	/**
	 * The «create an event» screen.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function new_event( array $m ): string {
		$flash = (array) $m['flash'];

		ob_start();
		?>
		<p class="evt-sub"><a href="<?php echo esc_url( (string) $m['events_url'] ); ?>">&larr; Todos los eventos</a></p>
		<div class="evt-h1-fila">
			<h1 class="evt-h1">Crear evento</h1>
		</div>
		<p class="evt-sub">
			Con el título y la fecha de inicio basta para crearlo. Nace en
			borrador y no se ve fuera hasta que lo publique; sus páginas, sus
			ponentes y su programa se añaden después, con el evento ya abierto.
		</p>
		<?php
		if ( '' !== (string) $flash['texto'] ) {
			echo Shell::notice( (string) $flash['tipo'], (string) $flash['texto'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		}
		echo EventDataPanel::html( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.

		return Shell::render( '', '', (string) ob_get_clean() );
	}

	/**
	 * Why this workshop opens without a single «Guardar».
	 *
	 * Sale arriba y para todo el mundo, también para quien administra: si el
	 * evento está cerrado, lo primero que hay que leer al entrar es eso.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function archived_notice( array $m ): string {
		if ( true !== $m['archived'] ) {
			return '';
		}
		$texto = true === $m['can_edit']
			? 'Este evento está marcado como histórico: su área ya no puede editarlo. Usted sí, porque administra el aplicativo.'
			: 'Este evento está marcado como histórico: se puede consultar y exportar, pero ya no se edita. Para volver a abrirlo, pídalo a quien administre el aplicativo.';

		return Shell::notice( 'aviso', $texto );
	}

	/**
	 * The switch that closes the event for good, or opens it again.
	 *
	 * Las dos mitades no se pintan igual porque no son de la misma persona.
	 * Cerrar el evento es una acción normal del área que lo organiza —una cosa
	 * más de este evento, al lado de sus fechas—, así que va en una tarjeta
	 * corriente. Reabrirlo sí es de administración y solo lo ve ella, así que
	 * va en el recuadro amarillo, que es la convención del aplicativo para
	 * justamente eso y para nada más.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function archive_switch( array $m ): string {
		if ( true === $m['can_unarchive'] ) {
			return Shell::admin_box(
				'Volver a abrir el evento',
				self::archive_form( $m, false ),
				'Cerrar un evento lo hace su área; volver a abrirlo, solo quien administra el aplicativo.'
			);
		}
		if ( true !== $m['can_archive'] ) {
			return '';
		}

		ob_start();
		?>
		<section class="evt-tarjeta">
			<h2>Dar el evento por terminado</h2>
			<p>Cuando ya no quede nada que tocar —los vídeos subidos, las presentaciones colgadas, las erratas corregidas—, márquelo como histórico y quedará cerrado tal y como está. Seguirá entrando a consultarlo y a exportarlo, y la página pública se verá igual que siempre; lo que ya no podrá es cambiar nada, ni de él ni de sus secciones. <strong>Para volver a abrirlo tendrá que pedírselo a quien administre el aplicativo.</strong></p>
			<?php echo self::archive_form( $m, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The form of one half of the switch.
	 *
	 * El aviso de que no hay vuelta atrás va en el diálogo y no solo en el
	 * texto de al lado: es lo último que se lee antes de cerrar el evento, y
	 * el diálogo abre con «Cancelar» enfocado.
	 *
	 * @param array<string, mixed> $m      Model.
	 * @param bool                 $marcar Whether this is the marking half.
	 * @return string
	 */
	private static function archive_form( array $m, bool $marcar ): string {
		$op       = $marcar ? EventWorkspace::OP_ARCHIVE : EventWorkspace::OP_UNARCHIVE;
		$rotulo   = $marcar ? 'Marcar como histórico' : 'Volver a abrir el evento';
		$pregunta = $marcar
			? sprintf( '¿Marcar «%s» como histórico? Dejará de poder editarlo, a él y a todas sus secciones, y no hay vuelta atrás: solo quien administre el aplicativo puede volver a abrirlo. La página pública no cambia.', (string) $m['title'] )
			: '¿Volver a abrir este evento? Su área podrá editarlo otra vez.';

		ob_start();
		?>
		<?php if ( ! $marcar ) : ?>
			<p><?php echo esc_html( 'Ahora mismo está cerrado a edición: el área que lo organizó puede entrar, consultarlo y exportarlo, pero no cambiar nada. La página pública se ve igual que siempre.' ); ?></p>
		<?php endif; ?>
		<form class="evt-accion" method="post" action=""
			data-evt-confirm="<?php echo esc_attr( $pregunta ); ?>"
			data-evt-confirm-ok="<?php echo esc_attr( $rotulo ); ?>">
			<?php wp_nonce_field( EventWorkspace::nonce_action( $op ), EventWorkspace::nonce_name( $op ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( EventWorkspace::FIELD_EVENT ); ?>" value="<?php echo esc_attr( (string) (int) $m['event_id'] ); ?>" />
			<button type="submit" class="<?php echo esc_attr( Assets::button_class() ); ?>" title="<?php echo esc_attr( $rotulo ); ?>"><?php echo esc_html( $rotulo ); ?></button>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The name of the event, what state it is in, its área, and where to see it.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function head( array $m ): string {
		ob_start();
		?>
		<p class="evt-sub"><a href="<?php echo esc_url( (string) $m['events_url'] ); ?>">&larr; Todos los eventos</a></p>
		<div class="evt-h1-fila">
			<h1 class="evt-h1"><?php echo esc_html( '' !== (string) $m['title'] ? (string) $m['title'] : 'Evento sin título' ); ?></h1>
			<span class="<?php echo esc_attr( Assets::state_class( (string) $m['state'] ) ); ?>"><?php echo esc_html( (string) $m['state_label'] ); ?></span>
			<span class="<?php echo esc_attr( 'publish' === (string) $m['status'] ? 'evt-state evt-state-publish' : 'evt-state evt-state-draft' ); ?>"><?php echo esc_html( (string) $m['status_label'] ); ?></span>
			<span class="evt-acciones">
				<?php if ( '' !== (string) $m['view_url'] ) : ?>
					<a class="<?php echo esc_attr( Assets::button_class() ); ?>" href="<?php echo esc_url( (string) $m['view_url'] ); ?>">Ver la página</a>
				<?php endif; ?>
			</span>
		</div>
		<p class="evt-sub"><?php echo esc_html( 'Área: ' . self::area_names( (array) $m['area_ids'] ) ); ?></p>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The inner tabs of the workshop, each with its count.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function tabs( array $m ): string {
		$activa = (string) $m['panel'];

		ob_start();
		?>
		<nav class="evt-tabs" aria-label="Paneles del evento">
			<div class="evt-tabs-fila">
				<?php foreach ( (array) $m['panels'] as $clave => $panel ) : ?>
					<a class="evt-tab<?php echo $clave === $activa ? ' evt-tab-on' : ''; ?>"
						<?php echo $clave === $activa ? ' aria-current="page"' : ''; ?>
						href="<?php echo esc_url( (string) $panel['url'] ); ?>"><?php echo esc_html( (string) $panel['label'] ); ?>
						<?php
						// El recuento solo donde hay algo que contar: en «Ajustes»,
						// «Apariencia» y «Código» no significaría nada.
						if ( null !== ( $panel['count'] ?? null ) ) :
							?>
							<span class="evt-tab-n"><?php echo esc_html( (string) (int) $panel['count'] ); ?></span>
						<?php endif; ?></a>
				<?php endforeach; ?>
			</div>
		</nav>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The áreas of the event, written out.
	 *
	 * @param int[] $ids Term IDs of evt_area.
	 * @return string
	 */
	private static function area_names( array $ids ): string {
		$nombres = array();
		foreach ( $ids as $term_id ) {
			$term = get_term( (int) $term_id, EventTaxonomies::AREA );
			if ( $term instanceof \WP_Term ) {
				$nombres[] = $term->name;
			}
		}
		return array() === $nombres ? 'Sin área' : implode( ' · ', $nombres );
	}

	/**
	 * Way out when there is no event to work on.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function back_link( array $m ): string {
		$url = (string) $m['events_url'];
		if ( '' === $url ) {
			return '';
		}
		return '<p><a class="' . esc_attr( Assets::button_class( true ) ) . '" href="' . esc_url( $url ) . '">Ver mis eventos</a></p>';
	}
}
