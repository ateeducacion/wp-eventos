<?php
/**
 * The public timeline, painted from what Timeline::model() decided.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

use Evt\PublicFront\Timeline;

/**
 * Solo pinta: no lee la petición, no consulta, no decide.
 */
final class TimelineView {

	/**
	 * The whole page: bar, heading, filter, timeline and footer.
	 *
	 * La barra y el pie son los de la página de un evento
	 * ({@see EventChrome}), así que el logo, «Acceder» y los enlaces del pie
	 * salen igual.
	 *
	 * @param string               $title Page heading.
	 * @param array<string, mixed> $m     Model.
	 * @return string
	 */
	public static function document( string $title, array $m ): string {
		ob_start();
		?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
		<?php
		if ( ! current_theme_supports( 'title-tag' ) ) {
			echo '<title>' . esc_html( wp_get_document_title() ) . "</title>\n";
		}
		wp_head();
		echo EventChrome::consent(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- URL escapadas dentro.
		?>
</head>
<body <?php body_class( array( 'evt-ev', 'evt-ev--linea' ) ); ?>>
		<?php
		wp_body_open();
		echo '<a class="evt-ev__saltar visually-hidden-focusable" href="#contenido">Saltar al contenido</a>';
		echo '<header class="evt-ev__cabecera">' . EventChrome::nav( array() ) . '</header>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		?>
	<main id="contenido" class="evt-ev__main" tabindex="-1">
		<div class="evt-ev__ancho">
			<?php if ( '' !== $title ) : ?>
				<h1 class="evt-linea__h1"><?php echo esc_html( $title ); ?></h1>
			<?php endif; ?>
			<?php echo self::filter( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		</div>
		<?php echo self::html( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
	</main>
		<?php
		echo EventChrome::footer(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		wp_footer();
		echo EventChrome::analytics(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido en analytics().
		?>
</body>
</html>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The filter: a text search and the scope, by GET, so it works without
	 * JavaScript and the filtered link can be shared.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	public static function filter( array $m ): string {
		$filtros = (array) ( $m['filters'] ?? array() );
		$buscar  = (string) ( $filtros['search'] ?? '' );
		$ambito  = (int) ( $filtros['area'] ?? 0 );
		$aqui    = is_singular() ? (string) get_permalink( get_queried_object_id() ) : home_url( '/' );

		ob_start();
		?>
		<form class="evt-linea__filtro" method="get" action="<?php echo esc_url( $aqui ); ?>" role="search">
			<label class="evt-linea__campo">
				<span>Buscar</span>
				<input type="search" name="<?php echo esc_attr( Timeline::ARG_SEARCH ); ?>" value="<?php echo esc_attr( $buscar ); ?>" placeholder="Título, sede…" />
			</label>
			<?php if ( array() !== (array) ( $m['areas'] ?? array() ) ) : ?>
				<label class="evt-linea__campo">
					<span>Ámbito</span>
					<select name="<?php echo esc_attr( Timeline::ARG_AREA ); ?>">
						<option value="">Todos</option>
						<?php foreach ( (array) $m['areas'] as $area ) : ?>
							<option value="<?php echo esc_attr( (string) $area['id'] ); ?>"<?php selected( $ambito, (int) $area['id'] ); ?>><?php echo esc_html( str_repeat( '— ', (int) $area['depth'] ) . $area['name'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			<?php endif; ?>
			<button class="evt-linea__boton evt-linea__boton--filtrar" type="submit">Filtrar</button>
			<?php if ( ! empty( $m['filtered'] ) ) : ?>
				<p class="evt-linea__resultado" role="status">
					<?php echo esc_html( 1 === (int) $m['count'] ? '1 evento' : (int) $m['count'] . ' eventos' ); ?>
					· <a href="<?php echo esc_url( $aqui ); ?>">Quitar filtros</a>
				</p>
			<?php endif; ?>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The timeline.
	 *
	 * @param array{months: array<int, array<string, mixed>>, current: int} $m Model.
	 * @return string
	 */
	public static function html( array $m ): string {
		ob_start();
		?>
		<div class="evt-linea" data-actual="<?php echo esc_attr( (string) (int) $m['current'] ); ?>">
			<div class="evt-linea__barra">
				<p class="evt-linea__ayuda">Arrastre la línea hacia la derecha para ver los eventos que ya pasaron.</p>
				<div class="evt-linea__botones">
					<button class="evt-linea__boton" type="button" data-evt-linea="atras" aria-label="Mes anterior">&lsaquo;</button>
					<button class="evt-linea__boton evt-linea__boton--hoy" type="button" data-evt-linea="hoy">Hoy</button>
					<button class="evt-linea__boton" type="button" data-evt-linea="adelante" aria-label="Mes siguiente">&rsaquo;</button>
				</div>
			</div>
			<div class="evt-linea__pista" tabindex="0" role="region" aria-label="Línea del tiempo de eventos">
				<ol class="evt-linea__meses">
					<?php foreach ( (array) $m['months'] as $mes ) : ?>
						<?php echo self::month( (array) $mes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
					<?php endforeach; ?>
				</ol>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * One month: its label, its mark on the line and its events.
	 *
	 * @param array<string, mixed> $mes Month.
	 * @return string
	 */
	private static function month( array $mes ): string {
		$eventos = (array) $mes['events'];
		$clases  = array( 'evt-linea__mes' );
		if ( true === $mes['current'] ) {
			$clases[] = 'evt-linea__mes--actual';
		}
		if ( true === $mes['past'] ) {
			$clases[] = 'evt-linea__mes--pasado';
		}
		if ( array() !== $eventos ) {
			$clases[] = 'evt-linea__mes--con-eventos';
		}

		ob_start();
		?>
		<li class="<?php echo esc_attr( implode( ' ', $clases ) ); ?>" data-mes="<?php echo esc_attr( (string) $mes['key'] ); ?>">
			<h2 class="evt-linea__rotulo">
				<span class="evt-linea__nombre"><?php echo esc_html( (string) $mes['name'] ); ?></span>
				<span class="evt-linea__anio"><?php echo esc_html( (string) $mes['year'] ); ?></span>
				<?php if ( true === $mes['current'] ) : ?>
					<span class="evt-linea__chapa evt-linea__chapa--actual">Este mes</span>
				<?php endif; ?>
				<?php if ( '' !== (string) $mes['course'] ) : ?>
					<span class="evt-linea__chapa">Curso <?php echo esc_html( (string) $mes['course'] ); ?></span>
				<?php endif; ?>
			</h2>
			<span class="evt-linea__marca" aria-hidden="true"><span class="evt-linea__punto"></span><span class="evt-linea__trazo"></span></span>
			<?php if ( array() === $eventos ) : ?>
				<p class="evt-linea__vacio">Sin eventos este mes.</p>
			<?php else : ?>
				<ul class="evt-linea__eventos">
					<?php foreach ( $eventos as $ev ) : ?>
						<?php echo self::event( (array) $ev ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</li>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * One event card.
	 *
	 * @param array<string, mixed> $ev Event row.
	 * @return string
	 */
	private static function event( array $ev ): string {
		$clases = 'evt-linea__evento' . ( true === $ev['done'] ? ' evt-linea__evento--pasado' : '' );

		ob_start();
		?>
		<li class="<?php echo esc_attr( $clases ); ?>">
			<a class="evt-linea__cartel" href="<?php echo esc_url( (string) $ev['url'] ); ?>" tabindex="-1" aria-hidden="true" style="--evt-linea-color: <?php echo esc_attr( (string) $ev['color'] ); ?>; --evt-linea-tinta: <?php echo esc_attr( (string) ( $ev['ink'] ?? '#fff' ) ); ?>" draggable="false">
				<?php if ( '' !== (string) $ev['poster'] ) : ?>
					<img src="<?php echo esc_url( (string) $ev['poster'] ); ?>" alt="" loading="lazy" draggable="false">
				<?php else : ?>
					<span class="evt-linea__sin-cartel"><?php echo esc_html( (string) $ev['title'] ); ?></span>
				<?php endif; ?>
			</a>
			<div class="evt-linea__datos">
				<span class="evt-linea__fechas"><?php echo esc_html( (string) $ev['dates'] ); ?></span>
				<h3 class="evt-linea__titulo"><a href="<?php echo esc_url( (string) $ev['url'] ); ?>" draggable="false"><?php echo esc_html( (string) $ev['title'] ); ?></a></h3>
				<?php if ( '' !== (string) $ev['venue'] ) : ?>
					<span class="evt-linea__sede"><?php echo esc_html( (string) $ev['venue'] ); ?></span>
				<?php endif; ?>
				<span class="evt-linea__estados">
					<span class="evt-linea__estado evt-linea__estado--<?php echo esc_attr( (string) $ev['state'] ); ?>"><?php echo esc_html( (string) $ev['state_label'] ); ?></span>
					<?php if ( '' !== (string) $ev['signup_url'] ) : ?>
						<a class="evt-linea__inscribirse" href="<?php echo esc_url( (string) $ev['signup_url'] ); ?>" draggable="false">Inscribirse</a>
					<?php endif; ?>
				</span>
			</div>
		</li>
		<?php
		return (string) ob_get_clean();
	}
}
