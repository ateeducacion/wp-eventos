<?php
/**
 * Header, navigation, separator and footer of the public page of an event.
 *
 * @package Evt
 */

namespace Evt\PublicFront\View;

/**
 * El marco de la página de un evento: lo que rodea a los bloques.
 *
 * Solo pinta: no lee la petición, no consulta, no decide.
 * Todo lo que sale de aquí llega ya escapado y lo monta
 * {@see \Evt\PublicFront\EventLayout}.
 *
 * Cuatro piezas: la navegación entre secciones, la portada de color con el
 * logo, el título y las fechas, la silueta que las separa del cuerpo, y el pie
 * con quien sea dueño del sitio.
 *
 * Y dos que no se ven pero que un tema serviría y el esqueleto tuvo que
 * reponer: el aviso de cookies ({@see consent()}) y la analítica
 * ({@see analytics()}).
 *
 * **Ninguna de las tres últimas trae nada dentro.** El pie, las cookies y la
 * analítica son de quien despliega y se configuran con el filtro
 * {@see chrome()}; sin configurar, no se pintan (ADR-0030).
 *
 * El `<h1>` de la página es el título de la portada, y es el único: los
 * bloques del cuerpo empiezan en `<h2>`.
 */
final class EventChrome {

	/**
	 * Filter that supplies the chrome of the public page.
	 *
	 * **Este repositorio no trae ni una dirección institucional.** El pie, el
	 * aviso de cookies y la analítica son de quien despliega, no del
	 * aplicativo: se rellenan desde fuera con este filtro y, sin nadie que
	 * conteste, **no se pinta ninguno de los tres**. Un aplicativo libre no
	 * puede llevar dentro el portal, el dominio ni el identificador de
	 * analítica de una organización concreta (ADR-0030).
	 */
	public const HOOK = 'evt_chrome';

	/**
	 * The cookie the notice writes, when there is a notice.
	 *
	 * Es el único valor con defecto porque no identifica a nadie: es el nombre
	 * que usa la biblioteca de avisos de cookies más extendida, y quien ponga
	 * otra lo cambia con el filtro.
	 */
	public const CONSENT_COOKIE = 'cookieconsent_status';

	/**
	 * Everything the page needs from whoever deploys it.
	 *
	 * Todo vacío por defecto, y eso es la decisión: lo que no se configura no
	 * se pinta. Así el aplicativo se publica sin llevar dentro nada de nadie y
	 * una instalación nueva no envía datos a ningún sitio sin decirlo.
	 *
	 * @return array<string, mixed>
	 */
	public static function chrome(): array {
		$defecto = array(
			// Pie y cabecera: de quién es el sitio y quién lo hizo.
			'owner'          => '',
			'owner_url'      => '',
			'org'            => '',
			'credit'         => '',
			'footer_links'   => array(),
			// Aviso de cookies: las tres piezas que lo pintan.
			'consent_css'    => '',
			'consent_js'     => '',
			'consent_init'   => '',
			'consent_cookie' => self::CONSENT_COOKIE,
			// Analítica: sin esto no se carga nada y no se envía ni una visita.
			'matomo_api'     => '',
			'matomo_js'      => '',
			'matomo_site'    => 0,
		);

		/**
		 * Filter the chrome of the public event page.
		 *
		 * @param array<string, mixed> $chrome Empty defaults.
		 */
		$puesto = apply_filters( self::HOOK, $defecto );

		return is_array( $puesto ) ? array_merge( $defecto, $puesto ) : $defecto;
	}

	/**
	 * Minimum contrast ratio for normal text (WCAG 2.1 AA).
	 */
	public const MIN_CONTRAST = 4.5;

	/**
	 * Outlines of the cover separators, on a 1200×60 canvas.
	 *
	 * Pública porque la vista previa del panel de apariencia pinta estas mismas
	 * siluetas: si tuviera las suyas, enseñaría una cabecera que no es la que
	 * se va a publicar. Vivían en `EventViewHtml`, que era la vista vieja de la
	 * página pública; al portarla al esqueleto de bloques se retiró aquel
	 * fichero y las siluetas se quedaron donde se dibujan.
	 *
	 * @var array<string, string>
	 */
	public const SEPARATORS = array(
		'slant'    => 'M0,60 L1200,0 L1200,60 Z',
		'ramp'     => 'M0,60 L1200,24 L1200,60 Z',
		'curve'    => 'M0,60 Q600,-10 1200,60 Z',
		'wave'     => 'M0,38 C300,68 900,-6 1200,38 L1200,60 L0,60 Z',
		'triangle' => 'M0,60 L600,0 L1200,60 Z',
	);

	/**
	 * The whole header: navigation, cover and separator.
	 *
	 * @param array<string, mixed> $m What EventView::model() decided.
	 * @return string
	 */
	public static function header( array $m ): string {
		return '<header class="evt-ev__cabecera">'
			. self::nav( (array) $m['nav'] )
			. self::cover( $m )
			. '</header>';
	}

	/**
	 * The navigation between the sections of the event.
	 *
	 * Con una sola entrada no hay entre qué navegar y no se pinta: un menú de
	 * un elemento es ruido.
	 *
	 * @param array<int, array{label:string, url:string, current:bool}> $items Menu entries.
	 * @return string
	 */
	public static function nav( array $items ): string {
		if ( count( $items ) < 2 ) {
			return '';
		}

		ob_start();
		?>
		<nav class="evt-ev__nav navbar navbar-expand-lg" aria-label="Secciones del evento">
			<ul class="evt-ev__ancho nav">
				<?php foreach ( $items as $item ) : ?>
					<li class="nav-item">
						<a class="nav-link" href="<?php echo esc_url( (string) $item['url'] ); ?>"
							<?php echo ! empty( $item['current'] ) ? ' aria-current="page"' : ''; ?>><?php echo esc_html( (string) $item['label'] ); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The cover: logo, title, tagline, dates, venue, state and sign-up.
	 *
	 * Los colores no van en un atributo `style`: son los tokens `--evt-fondo` y
	 * `--evt-texto`, que escribe {@see \Evt\PublicFront\EventLayout::tokens()}
	 * en un solo sitio. Así el CSS a medida de un evento cambia el token y no
	 * tiene que ganarle a un `style` en línea, que no hay forma de pisar sin
	 * `!important`.
	 *
	 * @param array<string, mixed> $m What EventView::model() decided.
	 * @return string
	 */
	public static function cover( array $m ): string {
		$look   = (array) $m['appearance'];
		$signup = (array) $m['signup'];
		$banner = ! empty( $m['is_root'] ) ? (string) $look['header_banner'] : '';

		if ( '' !== $banner ) {
			ob_start();
			?>
			<div class="evt-ev__portada evt-ev__portada--banner">
				<img class="evt-ev__banner" src="<?php echo esc_url( $banner ); ?>"
					alt="<?php echo esc_attr( (string) $look['header_banner_alt'] ); ?>" />
				<div class="screen-reader-text">
					<h1><?php echo esc_html( (string) $m['title'] ); ?></h1>
					<?php
					if ( '' !== (string) $m['tagline'] ) :
						?>
						<p><?php echo esc_html( (string) $m['tagline'] ); ?></p><?php endif; ?>
					<?php
					if ( '' !== (string) $m['dates'] ) :
						?>
						<p><?php echo esc_html( (string) $m['dates'] ); ?></p><?php endif; ?>
					<?php
					if ( '' !== (string) $m['venue'] ) :
						?>
						<p><?php echo esc_html( (string) $m['venue'] ); ?></p><?php endif; ?>
				</div>
			</div>
			<?php
			return (string) ob_get_clean();
		}

		ob_start();
		?>
		<div class="evt-ev__portada">
			<div class="evt-ev__ancho">
				<?php if ( '' !== (string) $look['logo'] ) : ?>
					<img class="evt-ev__logo" src="<?php echo esc_url( (string) $look['logo'] ); ?>"
						alt="<?php echo esc_attr( (string) $look['logo_alt'] ); ?>" />
				<?php endif; ?>

				<?php if ( empty( $m['is_root'] ) && '' !== (string) $m['event_url'] ) : ?>
					<p class="evt-ev__madre">
						<a href="<?php echo esc_url( (string) $m['event_url'] ); ?>"><?php echo esc_html( (string) $m['event_title'] ); ?></a>
					</p>
				<?php endif; ?>

				<h1 class="evt-ev__titulo"><?php echo esc_html( (string) $m['title'] ); ?></h1>

				<?php if ( '' !== (string) $m['tagline'] ) : ?>
					<p class="evt-ev__lema"><?php echo esc_html( (string) $m['tagline'] ); ?></p>
				<?php endif; ?>

				<div class="evt-ev__linea" aria-hidden="true"></div>

				<?php if ( '' !== (string) $m['dates'] || '' !== (string) $m['venue'] ) : ?>
					<p class="evt-ev__datos">
						<?php if ( '' !== (string) $m['dates'] ) : ?>
							<span><?php echo esc_html( (string) $m['dates'] ); ?></span>
						<?php endif; ?>
						<?php if ( '' !== (string) $m['venue'] ) : ?>
							<span><?php echo esc_html( (string) $m['venue'] ); ?></span>
						<?php endif; ?>
					</p>
				<?php endif; ?>

				<p class="evt-ev__acciones">
					<?php if ( '' !== (string) $m['state_label'] ) : ?>
						<span class="evt-ev__estado"><?php echo esc_html( (string) $m['state_label'] ); ?></span>
					<?php endif; ?>
					<?php if ( '' !== (string) $m['hashtag'] ) : ?>
						<span class="evt-ev__hashtag">#<?php echo esc_html( (string) $m['hashtag'] ); ?></span>
					<?php endif; ?>
					<?php if ( '' !== (string) $signup['url'] ) : ?>
						<a class="evt-ev__boton" href="<?php echo esc_url( (string) $signup['url'] ); ?>"><?php echo esc_html( (string) $signup['label'] ); ?></a>
					<?php endif; ?>
					<?php if ( '' !== (string) $m['manage_url'] ) : ?>
						<a class="evt-ev__gestion" href="<?php echo esc_url( (string) $m['manage_url'] ); ?>">Gestionar este evento</a>
					<?php endif; ?>
				</p>
			</div>
			<?php echo self::separator( (string) $look['separator'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- silueta de la lista cerrada, escapada dentro. ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The outline at the foot of the cover.
	 *
	 * Las siluetas son las de {@see self::SEPARATORS}: son las mismas que
	 * enseña la vista previa del panel de apariencia, y tener dos juegos sería
	 * enseñar una cabecera distinta de la que se publica.
	 *
	 * @param string $shape One of EventMetaKeys::separators().
	 * @return string Empty for «sin separador».
	 */
	public static function separator( string $shape ): string {
		$trazo = self::SEPARATORS[ $shape ] ?? '';
		if ( '' === $trazo ) {
			return '';
		}
		return '<svg class="evt-ev__separador" viewBox="0 0 1200 60" preserveAspectRatio="none" aria-hidden="true" focusable="false">'
			. '<path d="' . esc_attr( $trazo ) . '" fill="var(--evt-papel)"/></svg>';
	}

	/**
	 * The footer: who owns the site, and its legal notices.
	 *
	 * **Vacío salvo que alguien lo configure** ({@see chrome()}). El pie es de
	 * quien despliega, no del aplicativo.
	 *
	 * @return string
	 */
	public static function footer(): string {
		$chrome  = self::chrome();
		$enlaces = (array) $chrome['footer_links'];

		ob_start();
		?>
		<footer class="evt-ev__pie">
			<div class="evt-ev__ancho">
				<span class="evt-ev__pie-quien">
					<?php if ( '' !== (string) $chrome['owner'] ) : ?>
						<?php if ( '' !== (string) $chrome['owner_url'] ) : ?>
							<a href="<?php echo esc_url( (string) $chrome['owner_url'] ); ?>" target="_blank" rel="noopener">&copy; <?php echo esc_html( (string) $chrome['owner'] ); ?></a>
						<?php else : ?>
							<span>&copy; <?php echo esc_html( (string) $chrome['owner'] ); ?></span>
						<?php endif; ?>
					<?php endif; ?>
				</span>
				<span class="evt-ev__pie-enlaces">
					<?php foreach ( $enlaces as $enlace ) : ?>
						<a href="<?php echo esc_url( (string) $enlace['url'] ); ?>"
							title="<?php echo esc_attr( (string) $enlace['title'] ); ?>"
							target="_blank" rel="noopener"><?php echo esc_html( (string) $enlace['label'] ); ?></a>
					<?php endforeach; ?>
				</span>
			</div>
		</footer>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The cookie notice, for the head of the document.
	 *
	 * Al pintar el documento entero, lo que el tema metía antes de `</head>`
	 * deja de ponerse solo. En un sitio público de una administración el aviso
	 * de cookies no es opcional, así que hay dónde reponerlo — pero **las URL
	 * son de quien despliega** ({@see chrome()}) y aquí no hay ninguna.
	 *
	 * El orden importa: la hoja, la biblioteca y, detrás, el guion que la
	 * inicializa.
	 *
	 * **Si esas URL no responden, la página no se rompe.** Los dos guiones van
	 * con `defer`: no bloquean el análisis del documento y, si uno no llega, el
	 * fallo se queda dentro de él. Y si la hoja no llega, el aviso se pinta sin
	 * estilo pero se puede cerrar.
	 *
	 * @return string
	 */
	public static function consent(): string {
		$chrome = self::chrome();
		$html   = '';

		// phpcs:disable WordPress.WP.EnqueuedResources -- el documento de la página de evento lo escribimos nosotros entero: el aviso legal no puede depender de que nadie lo saque de la cola.
		if ( '' !== (string) $chrome['consent_css'] ) {
			$html .= '<link rel="stylesheet" href="' . esc_url( (string) $chrome['consent_css'] ) . '" />' . "\n";
		}
		if ( '' !== (string) $chrome['consent_js'] ) {
			$html .= '<script src="' . esc_url( (string) $chrome['consent_js'] ) . '" defer></script>' . "\n";
		}
		if ( '' !== (string) $chrome['consent_init'] ) {
			$html .= '<script src="' . esc_url( (string) $chrome['consent_init'] ) . '" defer></script>' . "\n";
		}
		// phpcs:enable WordPress.WP.EnqueuedResources

		return $html;
	}

	/**
	 * Matomo, for the end of the body, and only when it counts for real.
	 *
	 * **No se emite nada salvo que se configure** ({@see chrome()}): un
	 * aplicativo libre no lleva dentro a qué servidor de analítica envía las
	 * visitas de nadie.
	 *
	 * **Y respeta el consentimiento**, que hoy no lo hace: con
	 * `requireCookieConsent` Matomo cuenta la visita sin escribir ni una
	 * cookie, y solo las escribe cuando el aviso institucional dice que sí. La
	 * única fuente de verdad es la cookie del propio aviso, así que no hay dos
	 * sitios donde mirar ni un segundo registro de consentimiento que mantener.
	 *
	 * Fuera de producción tampoco se emite: en desarrollo mandaría visitas de
	 * `localhost` a la estadística de verdad.
	 *
	 * @return string Empty when there is no site to count for.
	 */
	public static function analytics(): string {
		$chrome = self::chrome();
		$site   = (int) $chrome['matomo_site'];
		$api    = (string) $chrome['matomo_api'];
		$js_url = (string) $chrome['matomo_js'];

		/**
		 * Filter whether the analytics snippet is emitted at all.
		 *
		 * Por defecto solo en producción: en desarrollo mandaría visitas de
		 * `localhost` a la estadística de verdad. Es un filtro y no una
		 * comprobación suelta para que se pueda apagar en producción sin tocar
		 * la configuración, y encender en un entorno de pruebas a propósito.
		 *
		 * @param bool $encendida Whether to emit the analytics snippet.
		 */
		$encendida = (bool) apply_filters( 'evt_analytics_enabled', 'production' === wp_get_environment_type() );

		// Sin las tres cosas configuradas no se emite nada, y apagada tampoco.
		if ( ! $encendida || $site <= 0 || '' === $api || '' === $js_url ) {
			return '';
		}

		$cookie = (string) $chrome['consent_cookie'];
		$js     = 'var _paq=window._paq=window._paq||[];'
			. '_paq.push(["requireCookieConsent"]);'
			. ( '' !== $cookie
				? 'if(/(^|;\s*)' . $cookie . '=(allow|dismiss)(;|$)/.test(document.cookie)){_paq.push(["setCookieConsentGiven"]);}'
				: '' )
			. '_paq.push(["setTrackerUrl",' . wp_json_encode( $api, JSON_UNESCAPED_SLASHES ) . ']);'
			. '_paq.push(["setSiteId",' . $site . ']);'
			. '_paq.push(["trackPageView"]);'
			. '_paq.push(["enableLinkTracking"]);';

		// phpcs:disable WordPress.WP.EnqueuedResources -- el documento de la página de evento lo escribimos nosotros entero: el aviso legal no puede depender de que nadie lo saque de la cola.
		$html = '<script id="evt-matomo">' . $js . '</script>' . "\n"
			. '<script src="' . esc_url( $js_url ) . '" async defer></script>' . "\n";
		// phpcs:enable WordPress.WP.EnqueuedResources
		return $html;
	}

	/**
	 * Contrast ratio between two colours, WCAG 2.1 relative luminance.
	 *
	 * Pública porque el panel de apariencia tiene que avisar cuando la
	 * combinación que se está eligiendo no llega a 4,5:1, y el aviso y lo que
	 * se publica tienen que salir del mismo cálculo.
	 *
	 * @param string $uno Hex colour, `#rgb` or `#rrggbb`.
	 * @param string $dos Hex colour.
	 * @return float 1.0 when either colour is not a hex colour: sin dato no se acusa a nadie.
	 */
	public static function contrast_ratio( string $uno, string $dos ): float {
		$a = self::luminance( $uno );
		$b = self::luminance( $dos );
		if ( $a < 0 || $b < 0 ) {
			return 1.0;
		}
		$claro  = max( $a, $b );
		$oscuro = min( $a, $b );
		return ( $claro + 0.05 ) / ( $oscuro + 0.05 );
	}

	/**
	 * The ink to write on a background: the chosen one, or a readable one.
	 *
	 * El contraste se comprueba, no se supone: si la combinación que eligió el
	 * evento no llega a 4,5:1 se escribe en blanco o en negro, el que gane
	 * sobre ese fondo. Vale más una cabecera con el color de texto cambiado que
	 * un título que no se lee. El panel de apariencia avisa antes de guardar,
	 * pero esto tiene que aguantar lo que ya está guardado y lo que llegue de
	 * la migración.
	 *
	 * @param string $fondo Background colour, hex.
	 * @param string $texto Chosen text colour, hex; empty to decide from scratch.
	 * @return string Hex colour.
	 */
	public static function readable_ink( string $fondo, string $texto ): string {
		if ( '' !== $texto && self::contrast_ratio( $fondo, $texto ) >= self::MIN_CONTRAST ) {
			return $texto;
		}
		return self::contrast_ratio( $fondo, '#ffffff' ) >= self::contrast_ratio( $fondo, '#000000' )
			? '#ffffff'
			: '#000000';
	}

	/**
	 * WCAG relative luminance of a hex colour.
	 *
	 * @param string $hex `#rgb` or `#rrggbb`.
	 * @return float -1.0 when it is not a hex colour.
	 */
	private static function luminance( string $hex ): float {
		$hex = ltrim( trim( $hex ), '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( ! preg_match( '/^[0-9a-fA-F]{6}$/', $hex ) ) {
			return -1.0;
		}

		$canales = array();
		foreach ( array( 0, 2, 4 ) as $desde ) {
			$c         = hexdec( substr( $hex, $desde, 2 ) ) / 255;
			$canales[] = $c <= 0.03928 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
		}

		return 0.2126 * $canales[0] + 0.7152 * $canales[1] + 0.0722 * $canales[2];
	}
}
