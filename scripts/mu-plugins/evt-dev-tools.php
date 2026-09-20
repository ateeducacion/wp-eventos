<?php
/**
 * Plugin Name: EVT Dev Tools
 * Description: Herramientas de desarrollo del aplicativo de eventos: cambiar de usuario demo y mostrar las cuentas de prueba en wp-login.php. Solo para entornos de desarrollo, nunca se despliega.
 * Version: 1.1.0
 * Author: Equipo de desarrollo
 * License: GPL-2.0-or-later
 *
 * This mu-plugin is mounted by wp-env / Playground from scripts/mu-plugins.
 * User switching is delegated to WPFront User Role Editor.
 *
 * @package Evt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// wp-admin pide el avatar a secure.gravatar.com, y esperar al evento «load» de
// una página es esperar también a esa petición: cuando el runner de CI tarda en
// resolverla, un inicio de sesión de medio segundo se planta en los 30 s de
// Playwright y la comprobación se cae sin que falle nada del aplicativo. Es un
// filtro, no una opción: no toca la base de datos, así que sobrevive a
// `make destroy` y vale igual en Playground.
add_filter( 'pre_option_show_avatars', '__return_zero' );

if ( ! function_exists( 'evt_dev_demo_accounts' ) ) {
	/**
	 * Demo accounts used for role testing (see scripts/seed-demo.php).
	 *
	 * @return list<array{login:string,pass:string,label:string}>
	 */
	function evt_dev_demo_accounts(): array {
		return array(
			array(
				'login' => 'admin',
				'pass'  => 'password',
				'label' => 'Administración (lo ve todo)',
			),
			array(
				'login' => 'organizacion',
				'pass'  => 'password',
				'label' => 'Organización (Innovación)',
			),
			array(
				'login' => 'organizacion2',
				'pass'  => 'password',
				'label' => 'Organización 2 (Innovación)',
			),
			array(
				'login' => 'organizacion3',
				'pass'  => 'password',
				'label' => 'Organización 3 (Convivencia escolar)',
			),
		);
	}
}

if ( ! function_exists( 'evt_dev_demo_logins' ) ) {
	/**
	 * Demo logins for the admin-bar switcher.
	 *
	 * @return array<string, string> login => short label.
	 */
	function evt_dev_demo_logins(): array {
		$logins = array();
		foreach ( evt_dev_demo_accounts() as $account ) {
			$logins[ $account['login'] ] = $account['label'];
		}
		return $logins;
	}
}

if ( ! function_exists( 'evt_dev_wpfront_switching' ) ) {
	/**
	 * Whether WPFront User Role Editor's user switching is loaded.
	 *
	 * El mismo plugin que en producción: así el «Cambiar a…» de aquí hace lo
	 * que hace allí, y no hay un segundo mecanismo que mantener.
	 *
	 * @return bool
	 */
	function evt_dev_wpfront_switching(): bool {
		return class_exists( '\\WPFront\\URE\\User_Switching\\WPFront_User_Role_Editor_User_Switching' );
	}
}

if ( ! function_exists( 'evt_dev_ensure_switch_cap' ) ) {
	/**
	 * Make sure administration really holds `switch_users`.
	 *
	 * WPFront no concede esa capacidad al activarse: la añade al rol de
	 * administración a través del filtro
	 * `wpfront_ure_administrator_caps_to_process`, y ese filtro **solo corre
	 * cuando alguien entra en su interfaz del escritorio**. En un wp-env se
	 * entra tarde o temprano y por eso allí funciona; en **WordPress
	 * Playground**, que aterriza en el aplicativo y se aprovisiona sin abrir
	 * wp-admin, no entra nadie, la capacidad no llega nunca y el «Cambiar a…»
	 * responde «Permission denied» (su propio `wp_die`, 403).
	 *
	 * Así que se hace aquí lo mismo que haría el plugin: dársela al rol de
	 * administración, y a nadie más. Solo escribe cuando falta, así que en un
	 * entorno donde WPFront ya la puso no toca la base de datos.
	 *
	 * Esto es **solo desarrollo**: este mu-plugin no se despliega nunca.
	 *
	 * @return void
	 */
	function evt_dev_ensure_switch_cap(): void {
		if ( ! evt_dev_wpfront_switching() ) {
			return;
		}
		$rol = get_role( 'administrator' );
		if ( $rol instanceof WP_Role && ! $rol->has_cap( 'switch_users' ) ) {
			$rol->add_cap( 'switch_users' );
		}
	}
}

// Después de que WPFront se haya cargado —él engancha su propio `init` con
// prioridad 1— y antes de que ninguna pantalla pregunte por la capacidad.
add_action( 'init', 'evt_dev_ensure_switch_cap', 5 );

if ( ! function_exists( 'evt_dev_switch_to_user_url' ) ) {
	/**
	 * Build the WPFront switch-to-user URL, or null when the plugin is not there.
	 *
	 * Los mismos parámetros y el mismo nonce que pone WPFront en la lista de
	 * usuarios (`ure_switch_action=switch_to`); los procesa él en `init`.
	 *
	 * @param WP_User $user   Target user.
	 * @param string  $volver Where to land after the switch; '' for the default.
	 * @return string|null
	 */
	function evt_dev_switch_to_user_url( WP_User $user, string $volver = '' ): ?string {
		if ( ! evt_dev_wpfront_switching() ) {
			return null;
		}
		$args = array(
			'ure_switch_action' => 'switch_to',
			'user_id'           => $user->ID,
		);
		if ( '' !== $volver ) {
			$args['evt_back'] = rawurlencode( $volver );
		}
		return wp_nonce_url(
			add_query_arg( $args, admin_url( 'users.php' ) ),
			"switch_to_user_{$user->ID}"
		);
	}
}

if ( ! function_exists( 'evt_dev_front_url' ) ) {
	/**
	 * Where a switch lands when there is nowhere to go back to.
	 *
	 * El aplicativo tiene sus propias pantallas, así que el destino es «Mis
	 * eventos» y no el escritorio de WordPress: cambiar de perfil es para ver
	 * el aplicativo con otros ojos, y el escritorio no es el aplicativo.
	 *
	 * @return string
	 */
	function evt_dev_front_url(): string {
		if ( class_exists( '\\Evt\\PublicFront\\Shell' ) ) {
			$url = \Evt\PublicFront\Shell::url( 'events' );
			if ( '' !== $url ) {
				return $url;
			}
		}
		return admin_url( 'edit.php?post_type=evt_event' );
	}
}

if ( ! function_exists( 'evt_dev_switch_destination' ) ) {
	/**
	 * Where this switch has to land.
	 *
	 * **La pantalla en la que se estaba**, que es lo que se quiere al cambiar
	 * de perfil: se mira un evento, se cambia a organización y se sigue
	 * mirando el mismo evento con sus permisos. Rebotar a la lista obliga a
	 * volver a buscarlo, y es justo cuando se pierde lo que se iba a comprobar.
	 *
	 * La dirección se valida contra este sitio: viene de la petición, y un
	 * destino de fuera convertiría el cambio de usuario en un salto a cualquier
	 * parte.
	 *
	 * @return string
	 */
	function evt_dev_switch_destination(): string {
		$defecto = evt_dev_front_url();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- el nonce del cambio se comprobó en evt_dev_prepare_switch_redirect().
		$pedido = isset( $_GET['evt_back'] ) ? rawurldecode( sanitize_text_field( wp_unslash( $_GET['evt_back'] ) ) ) : '';
		if ( '' === $pedido ) {
			return $defecto;
		}
		return wp_validate_redirect( $pedido, $defecto );
	}
}

if ( ! function_exists( 'evt_dev_prepare_switch_redirect' ) ) {
	/**
	 * Keep WPFront's authenticated user switches inside the development app.
	 *
	 * @return void
	 */
	function evt_dev_prepare_switch_redirect(): void {
		$action = isset( $_GET['ure_switch_action'] ) ? sanitize_key( wp_unslash( $_GET['ure_switch_action'] ) ) : '';
		if ( ! evt_dev_wpfront_switching() || ! in_array( $action, array( 'switch_to', 'switch_back', 'clear' ), true ) ) {
			return;
		}
		$target = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;
		$nonce  = 'switch_to' === $action ? 'switch_to_user_' . $target : 'switch_back_user_' . get_current_user_id();
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), $nonce ) ) {
			return;
		}
		// WPFront sigue comprobando permisos y cambiando la sesión en init:1.
		// Solo sustituimos su destino final; el nonce pertenece al usuario inicial.
		add_filter(
			'wp_redirect',
			static function ( $location ) {
				return in_array( $location, array( admin_url(), home_url() ), true ) ? evt_dev_switch_destination() : $location;
			}
		);
	}
}
add_action( 'init', 'evt_dev_prepare_switch_redirect', 0 );

if ( ! function_exists( 'evt_dev_admin_bar_node' ) ) {
	/**
	 * Add the quick demo account switcher to the admin bar.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 * @return void
	 */
	function evt_dev_admin_bar_node( $wp_admin_bar ) {
		$can_manage = current_user_can( 'manage_options' );
		$can_switch = evt_dev_wpfront_switching() && current_user_can( 'switch_users' );

		if ( ! $can_switch && ! $can_manage ) {
			return;
		}

		$wp_admin_bar->add_node(
			array(
				'id'    => 'evt-switch-user',
				'title' => 'EVT: Cambiar a…',
				'href'  => false,
			)
		);

		$current_login = wp_get_current_user()->user_login;
		// Dónde se está ahora, para volver aquí con el otro perfil.
		$aqui = '';
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$aqui = home_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
		}

		foreach ( evt_dev_demo_logins() as $login => $label ) {
			$user = get_user_by( 'login', $login );
			if ( ! $user instanceof WP_User ) {
				$wp_admin_bar->add_node(
					array(
						'id'     => 'evt-switch-' . sanitize_key( $login ),
						'parent' => 'evt-switch-user',
						'title'  => $label . ' (no creado — make seed-demo)',
						'href'   => false,
						'meta'   => array( 'class' => 'evt-switch-missing' ),
					)
				);
				continue;
			}

			if ( $login === $current_login ) {
				$wp_admin_bar->add_node(
					array(
						'id'     => 'evt-switch-' . sanitize_key( $login ),
						'parent' => 'evt-switch-user',
						'title'  => '✓ ' . $label,
						'href'   => false,
					)
				);
				continue;
			}

			$url = evt_dev_switch_to_user_url( $user, $aqui );
			if ( null === $url ) {
				// Plugin not loaded yet: point to users list as last resort.
				$url = admin_url( 'users.php?s=' . rawurlencode( $login ) );
			}

			$wp_admin_bar->add_node(
				array(
					'id'     => 'evt-switch-' . sanitize_key( $login ),
					'parent' => 'evt-switch-user',
					'title'  => $label,
					'href'   => $url,
				)
			);
		}
	}
}
add_action( 'admin_bar_menu', 'evt_dev_admin_bar_node', 100 );

if ( ! function_exists( 'evt_dev_login_form_accounts' ) ) {
	/**
	 * Print the demo account list under the login submit button.
	 *
	 * Hooked on `login_form` (after the password field). CSS `order` moves the
	 * box below «Acceder» without leaving the form.
	 *
	 * @return void
	 */
	function evt_dev_login_form_accounts() {
		echo '<div class="evt-dev-login-accounts">';
		echo '<p class="evt-dev-login-accounts__title">Cuentas de prueba</p>';
		echo '<ul class="evt-dev-login-accounts__list">';

		foreach ( evt_dev_demo_accounts() as $account ) {
			echo '<li>';
			echo '<button type="button" class="evt-dev-fill-login" data-login="' . esc_attr( $account['login'] ) . '" data-pass="' . esc_attr( $account['pass'] ) . '">';
			echo esc_html( $account['login'] );
			echo '</button>';
			echo ' / <code>' . esc_html( $account['pass'] ) . '</code>';
			echo '<span class="evt-dev-login-accounts__role">' . esc_html( $account['label'] ) . '</span>';
			echo '</li>';
		}

		echo '</ul>';
		echo '<p class="evt-dev-login-accounts__hint">Clic en el usuario para rellenar el formulario.</p>';
		echo '</div>';
	}
}
add_action( 'login_form', 'evt_dev_login_form_accounts' );

if ( ! function_exists( 'evt_dev_login_assets' ) ) {
	/**
	 * Styles and click-to-fill script for the demo account list on wp-login.php.
	 *
	 * @return void
	 */
	function evt_dev_login_assets() {
		global $action;

		if ( ! isset( $action ) || 'login' !== $action ) {
			return;
		}

		$css = <<<'CSS'
#loginform {
	display: flex;
	flex-direction: column;
}
.evt-dev-login-accounts {
	order: 20;
	margin-block-start: 1.25em;
	padding: 12px 14px;
	border: 1px solid #c3c4c7;
	background: #f6f7f7;
	box-sizing: border-box;
	font-size: 13px;
	line-height: 1.4;
}
.evt-dev-login-accounts__title {
	margin: 0 0 8px;
	font-weight: 600;
}
.evt-dev-login-accounts__list {
	margin: 0;
	padding: 0;
	list-style: none;
}
.evt-dev-login-accounts__list li + li {
	margin-block-start: 8px;
}
.evt-dev-fill-login {
	margin: 0;
	padding: 0;
	border: 0;
	background: none;
	color: #2271b1;
	cursor: pointer;
	font: inherit;
	font-family: Consolas, Monaco, monospace;
	text-decoration: underline;
}
.evt-dev-fill-login:focus-visible {
	outline: 2px solid #2271b1;
	outline-offset: 2px;
}
.evt-dev-login-accounts__role {
	display: block;
	color: #50575e;
}
.evt-dev-login-accounts__hint {
	margin: 8px 0 0;
	color: #646970;
}
CSS;

		wp_register_style( 'evt-dev-login', false, array(), '1.0.0' );
		wp_enqueue_style( 'evt-dev-login' );
		wp_add_inline_style( 'evt-dev-login', $css );

		$js = <<<'JS'
(function () {
	var box = document.querySelector(".evt-dev-login-accounts");
	var submit = document.querySelector("#loginform p.submit");
	if (box && submit && submit.parentNode) {
		submit.parentNode.insertBefore(box, submit.nextSibling);
	}
	document.querySelectorAll(".evt-dev-fill-login").forEach(function (button) {
		button.addEventListener("click", function () {
			var login = document.getElementById("user_login");
			var pass = document.getElementById("user_pass");
			if (login) {
				login.value = button.getAttribute("data-login") || "";
			}
			if (pass) {
				pass.value = button.getAttribute("data-pass") || "";
			}
			if (login) {
				login.focus();
			}
		});
	});
})();
JS;

		wp_register_script( 'evt-dev-login', false, array(), '1.0.0', true );
		wp_enqueue_script( 'evt-dev-login' );
		wp_add_inline_script( 'evt-dev-login', $js );
	}
}
add_action( 'login_enqueue_scripts', 'evt_dev_login_assets' );

// Bootstrap y sus iconos se cargan desde jsDelivr (`snippets/bootstrap5.php`), y
// en desarrollo eso mete la red en mitad de cada prueba: si el CDN tarda o el DNS
// parpadea, la página se dibuja sin Bootstrap y la comprobación se cae midiendo
// una geometría que nunca se aplicó, señalando a un sitio que no tiene nada que
// ver. Aquí se sirven de la copia que `npm install` deja en node_modules, con la
// versión clavada: la misma que pide el aplicativo, o no se toca nada (ADR-0037).
//
// Solo desarrollo: este mu-plugin no se despliega. En producción siguen viniendo
// del CDN, con su SRI, que se añade mirando el `src` y por tanto deja de ponerse
// solo cuando la URL ya no es la del CDN.
if ( ! function_exists( 'evt_dev_local_cdn_src' ) ) {
	/**
	 * Serve a pinned jsDelivr asset from node_modules when it is installed.
	 *
	 * @param string $src Asset URL.
	 * @return string
	 */
	function evt_dev_local_cdn_src( $src ) {
		if ( ! is_string( $src ) || 0 !== strpos( $src, 'https://cdn.jsdelivr.net/npm/' ) ) {
			return $src;
		}

		// WordPress ya le ha pegado el `?ver=`; se aparta y se devuelve al final.
		$consulta = '';
		$posicion = strpos( $src, '?' );
		if ( false !== $posicion ) {
			$consulta = substr( $src, $posicion );
			$src      = substr( $src, 0, $posicion );
		}

		$patron = '~^https://cdn\.jsdelivr\.net/npm/((?:@[^/@]+/)?[^/@]+)@([^/]+)/(.+)$~';
		if ( ! preg_match( $patron, $src, $partes ) ) {
			return $src . $consulta;
		}
		list( , $paquete, $version, $fichero ) = $partes;

		// 1) El paquete está instalado.
		$base = WP_CONTENT_DIR . '/evt-dev/node_modules/' . $paquete;
		$meta = $base . '/package.json';
		if ( ! is_readable( $meta ) ) {
			return $src . $consulta;
		}

		// 2) Y su versión es exactamente la que pide la URL: otra probaría algo
		// distinto de lo que se despliega. Mejor seguir yendo al CDN y que se note.
		$datos = wp_json_file_decode( $meta, array( 'associative' => true ) );
		if ( ! is_array( $datos ) || ( $datos['version'] ?? '' ) !== $version ) {
			return $src . $consulta;
		}

		// 3) Y el fichero existe. jsDelivr minifica al vuelo, así que hay `.min`
		// que el paquete no trae: entonces vale el original.
		$candidatos = array( $fichero, (string) preg_replace( '~\.min\.(js|css)$~', '.$1', $fichero ) );
		foreach ( array_unique( $candidatos ) as $candidato ) {
			if ( is_readable( $base . '/' . $candidato ) ) {
				return content_url( '/evt-dev/node_modules/' . $paquete . '/' . $candidato ) . $consulta;
			}
		}
		return $src . $consulta;
	}
}

add_filter( 'script_loader_src', 'evt_dev_local_cdn_src' );
add_filter( 'style_loader_src', 'evt_dev_local_cdn_src' );

/*
 * -----------------------------------------------------------------------------
 * Inscripciones de mentira para la pestaña «Participantes»
 * -----------------------------------------------------------------------------
 *
 * En la fase 1 las inscripciones viven en el gestor de formularios del sistema
 * anterior, que no está en el wp-env y del que el aplicativo no depende:
 * pregunta por ellas con el enganche `evt_participants` y, si nadie contesta,
 * la pantalla dice dónde están (ADR-0007). Aquí se contesta **solo en
 * desarrollo**, para poder ver de verdad la tabla, el filtro y la exportación
 * a CSV.
 *
 * Es el mismo contrato que tendrá que cumplir el snippet suelto que lea esas
 * inscripciones el día del despliegue: una fila por inscripción, con las claves
 * que declara `Participants::columns()`.
 */
if ( ! function_exists( 'evt_dev_participants' ) ) {
	/**
	 * Demo sign-ups for one event, matched to its demo workshops.
	 *
	 * @param array<int, array<string, string>> $filas    Rows so far.
	 * @param int                               $event_id Event post ID.
	 * @return array<int, array<string, string>>
	 */
	function evt_dev_participants( array $filas, int $event_id ): array {
		if ( 'jornadas-tecnologia-educativa' !== get_post_field( 'post_name', $event_id ) ) {
			return $filas;
		}

		// Desde que el aplicativo tiene su propio formulario (ADR-0032) las
		// filas de verdad las contesta él. Estas son solo para que la pantalla
		// tenga algo que enseñar mientras no se ha inscrito nadie: en cuanto
		// hay una inscripción real, la demo se aparta y no la ensucia.
		if ( array() !== $filas ) {
			return $filas;
		}

		$centros  = array( 'CEIP El Drago', 'Instituto Sur', 'CEIP Valverde', 'IES El Mirador' );
		$talleres = array( 'Taller de radio escolar', 'Taller de robótica en Primaria', '' );
		$nombres  = array(
			'Ana Martín Cabrera',
			'Luis Gómez Perdomo',
			'Marta Ruiz Santana',
			'Jorge Delgado Rivero',
			'Nayra Hernández Bello',
			'Iván Padrón Mesa',
			'Lucía Afonso Quintero',
		);

		foreach ( $nombres as $i => $nombre ) {
			$filas[] = array(
				'name'     => $nombre,
				'email'    => sanitize_title( $nombre ) . '@example.org',
				'centre'   => $centros[ $i % count( $centros ) ],
				'workshop' => $talleres[ $i % count( $talleres ) ],
				'date'     => gmdate( 'Y-m-d', strtotime( '-' . ( 10 - $i ) . ' days' ) ),
				'consent'  => 'Aceptado',
			);
		}

		return $filas;
	}
}
add_filter( 'evt_participants', 'evt_dev_participants', 20, 2 );

/*
 * -----------------------------------------------------------------------------
 * El catálogo de centros, para poder probar la inscripción
 * -----------------------------------------------------------------------------
 *
 * El centro se elige de un catálogo y **nunca se teclea** (ADR-0031), y el
 * catálogo maestro no es de este aplicativo: se pregunta con `evt_centres` y lo
 * contesta quien lo tenga. Aquí contesta el entorno de desarrollo con una lista
 * corta e inventada, para que el formulario se pueda probar.
 */
if ( ! function_exists( 'evt_dev_centres' ) ) {
	/**
	 * A short made-up catalogue of centres.
	 *
	 * @param string[] $centros Centres so far.
	 * @return string[]
	 */
	function evt_dev_centres( array $centros ): array {
		if ( array() !== $centros ) {
			return $centros;
		}
		return array(
			'CEIP El Molino',
			'CEIP La Vega',
			'CEIP El Roque',
			'CEO Las Dunas',
			'IES El Mirador',
			'Instituto Sur',
		);
	}
}
add_filter( 'evt_centres', 'evt_dev_centres' );

/*
 * -----------------------------------------------------------------------------
 * El armazón de la página pública, para poder verlo en desarrollo
 * -----------------------------------------------------------------------------
 *
 * El aplicativo **no trae dentro el pie, las cookies ni la analítica de nadie**
 * (ADR-0030): sin configurar, no se pintan. Aquí se rellenan con valores de
 * ejemplo —`example.org`, y **ninguna analítica**— para que en el wp-env se vea
 * un pie y un aviso de cookies y se puedan probar.
 *
 * Quien despliegue pone los suyos en un snippet suelto, fuera del aplicativo.
 */
if ( ! function_exists( 'evt_dev_chrome' ) ) {
	/**
	 * Demo chrome for the development environment.
	 *
	 * @param array<string, mixed> $chrome Empty defaults.
	 * @return array<string, mixed>
	 */
	function evt_dev_chrome( array $chrome ): array {
		return array_merge(
			$chrome,
			array(
				'owner'        => 'Organización de ejemplo',
				'owner_url'    => 'https://www.example.org/',
				'org'          => 'Área de ejemplo · entorno de desarrollo',
				'credit'       => 'Entorno de desarrollo',
				'footer_links' => array(
					array(
						'label' => 'Aviso legal',
						'url'   => 'https://www.example.org/aviso-legal/',
						'title' => 'Aviso legal (tecla de acceso: l)',
					),
					array(
						'label' => 'Política de privacidad',
						'url'   => 'https://www.example.org/privacidad/',
						'title' => 'Política de privacidad (tecla de acceso: p)',
					),
				),
				'consent_css'  => 'https://www.example.org/cookies/cookieconsent.min.css',
				'consent_js'   => 'https://www.example.org/cookies/cookieconsent.min.js',
				'consent_init' => 'https://www.example.org/cookies/init.js',
				// Analítica **apagada** a propósito: en desarrollo no se cuenta
				// nada en ningún sitio, y así se ve que sin configurar no sale.
			)
		);
	}
}
add_filter( 'evt_chrome', 'evt_dev_chrome' );
