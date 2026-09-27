/*
 * Los tres escalones de la confirmación, en un navegador de verdad.
 *
 *   npm run test:browser
 *
 * La degradación es una promesa que solo se puede comprobar aquí: PHP ve el
 * atributo en el formulario, pero que Escape cierre el diálogo, que el foco no
 * se escape de él y que la acción se haga igual cuando la librería no llegó
 * son cosas del navegador. Así que:
 *
 *   1. con SweetAlert2 —el de `node_modules`, la misma versión que el CDN—;
 *   2. sin él, con el `confirm()` de siempre;
 *   3. sin JavaScript en absoluto.
 *
 * No toca wp-env ni la red: monta la página a mano —el mismo `evt-app.js`, la
 * misma hoja y el mismo HTML que pinta `EventSectionsPanel`— y sirve tanto la
 * pantalla como el destino del envío desde el propio Playwright.
 */
const RAIZ = require( 'path' ).resolve( __dirname, '../..' );
const { chromium } = require( 'playwright' );
const fs = require( 'fs' );

const APP = fs.readFileSync( RAIZ + '/assets/js/evt-app.js', 'utf8' );
const CSS = fs.readFileSync( RAIZ + '/assets/css/evt-app.css', 'utf8' );
const SWAL = fs.readFileSync( RAIZ + '/node_modules/sweetalert2/dist/sweetalert2.all.min.js', 'utf8' );

const FORM = `<!doctype html><meta charset="utf-8">
<body class="evt-app">
<form id="f" class="evt-accion" method="post" action="/enviado"
      data-evt-confirm="¿Enviar «Programa» a la papelera? Dejará de verse en el evento.">
  <input type="hidden" name="evt_do" value="delete" />
  <button id="b" type="submit" class="evt-btn evt-mini evt-btn-borrar" title="Enviar a la papelera">
    <span aria-hidden="true">Borrar</span>
    <span class="screen-reader-text">Enviar a la papelera</span>
  </button>
</form>
<form id="fe" class="evt-accion evt-borrar-escrito" method="post" action="/enviado"
      data-evt-confirm="¿Borrar la inscripción de Ana Martín? No hay papelera."
      data-evt-confirm-ok="Borrar la inscripción"
      data-evt-confirm-escribe="ana@example.org">
  <input type="hidden" name="evt_do" value="reg_delete" />
  <label class="evt-escribe"><span>Para borrar, escriba su correo: ana@example.org</span>
    <input id="fe-correo" type="email" name="evt_confirm_email" /></label>
  <button id="be" type="submit" class="evt-btn evt-mini evt-btn-borrar">Borrar</button>
</form>
<a id="fuera" href="#">un enlace fuera del diálogo</a>
<a id="editar" class="evt-abre-marco" href="/pagina">Editar la página</a>
</body>`;

let fallos = 0;
function ok( cond, texto ) {
	console.log( ( cond ? '  OK   ' : '  FALLA' ) + '  ' + texto );
	if ( ! cond ) { fallos++; }
}

function html( conSwal ) {
	return '<style>' + CSS + '</style>' + FORM +
		( conSwal ? '<script>' + SWAL + '</scr' + 'ipt>' : '' ) +
		'<script>' + APP + '</scr' + 'ipt>';
}

async function abrir( ctx, conSwal ) {
	const page = await ctx.newPage();
	await page.route( 'https://evt.test/**', ( route ) => {
		const url = route.request().url();
		route.fulfill( {
			status: 200,
			contentType: 'text/html; charset=utf-8',
			body: url.includes( '/enviado' )
				? '<!doctype html><h1>ENVIADO</h1><pre id="datos">' + ( route.request().postData() || '' ) + '</pre>'
				: html( conSwal ),
		} );
	} );
	await page.goto( 'https://evt.test/panel' );
	return page;
}

( async () => {
	const browser = await chromium.launch();
	const ctx = await browser.newContext();

	// --- 1. Sin SweetAlert: el confirm() de siempre --------------------------
	{
		const page = await abrir( ctx, false );
		let preguntado = null;
		page.on( 'dialog', async ( d ) => { preguntado = d.message(); await d.dismiss(); } );
		await page.click( '#b' );
		await page.waitForTimeout( 300 );
		ok( null !== preguntado, 'sin SweetAlert se pregunta con confirm()' );
		ok( '¿Enviar «Programa» a la papelera? Dejará de verse en el evento.' === preguntado,
			'y con el texto del atributo: ' + JSON.stringify( preguntado ) );
		ok( ! page.url().includes( 'enviado' ), 'al cancelar no se envía' );

		page.removeAllListeners( 'dialog' );
		page.on( 'dialog', ( d ) => d.accept() );
		await Promise.all( [ page.waitForURL( '**/enviado' ), page.click( '#b' ) ] );
		ok( page.url().includes( 'enviado' ), 'al aceptar el confirm() sí se envía' );
		await page.close();
	}

	// --- 2. Sin JavaScript ---------------------------------------------------
	{
		const sinJs = await browser.newContext( { javaScriptEnabled: false } );
		const page = await abrir( sinJs, true );
		await Promise.all( [ page.waitForURL( '**/enviado' ), page.click( '#b' ) ] );
		ok( page.url().includes( 'enviado' ), 'sin JavaScript el botón envía y la acción se hace' );
		await sinJs.close();
	}

	// --- 3. Con SweetAlert ---------------------------------------------------
	{
		const page = await abrir( ctx, true );
		page.on( 'dialog', async ( d ) => { ok( false, 'no debería salir el confirm() del navegador' ); await d.dismiss(); } );

		await page.click( '#b' );
		await page.waitForSelector( '.swal2-popup', { state: 'visible' } );
		ok( true, 'sale el diálogo de SweetAlert2' );
		const titulo = ( await page.textContent( '.swal2-title' ) ).trim();
		ok( '¿Enviar «Programa» a la papelera?' === titulo, 'titular: ' + titulo );
		const detalle = ( await page.textContent( '.swal2-html-container' ) ).trim();
		ok( 'Dejará de verse en el evento.' === detalle, 'detalle: ' + detalle );
		const confirmar = ( await page.textContent( '.swal2-confirm' ) ).trim();
		ok( 'Enviar a la papelera' === confirmar, 'el botón dice el verbo: ' + confirmar );
		ok( 'Cancelar' === ( await page.textContent( '.swal2-cancel' ) ).trim(), 'y hay botón de cancelar' );
		ok( await page.evaluate( () => document.activeElement.classList.contains( 'swal2-cancel' ) ),
			'el foco arranca en Cancelar, no en la acción destructiva' );

		let dentro = true;
		for ( let i = 0; i < 12; i++ ) {
			await page.keyboard.press( 'Tab' );
			dentro = dentro && await page.evaluate( () => null !== document.activeElement.closest( '.swal2-container' ) );
		}
		ok( dentro, 'el foco queda atrapado dentro del diálogo tras 12 tabuladores' );

		await page.keyboard.press( 'Escape' );
		await page.waitForSelector( '.swal2-popup', { state: 'hidden' } );
		ok( ! page.url().includes( 'enviado' ), 'Escape cierra el diálogo y no envía' );

		await page.click( '#b' );
		await page.waitForSelector( '.swal2-popup', { state: 'visible' } );
		await page.click( '.swal2-cancel' );
		await page.waitForSelector( '.swal2-popup', { state: 'hidden' } );
		ok( ! page.url().includes( 'enviado' ), 'Cancelar cierra el diálogo y no envía' );

		await page.click( '#b' );
		await page.waitForSelector( '.swal2-popup', { state: 'visible' } );
		await Promise.all( [ page.waitForURL( '**/enviado' ), page.click( '.swal2-confirm' ) ] );
		ok( page.url().includes( 'enviado' ), 'confirmar envía el formulario' );
		await page.close();
	}

	// --- 4. Borrar tecleando el correo (ADR-0043) ----------------------------
	const enviado = async ( page ) => decodeURIComponent( ( await page.textContent( '#datos' ) ) || '' );
	{
		// Sin SweetAlert: prompt(), que enseña el correo y lo compara.
		const page = await abrir( ctx, false );
		let texto = '';
		page.on( 'dialog', async ( d ) => { texto = d.message(); await d.accept( 'otra@example.org' ); } );
		await page.click( '#be' );
		await page.waitForTimeout( 300 );
		ok( texto.includes( 'ana@example.org' ), 'el prompt() enseña el correo que hay que escribir' );
		ok( ! page.url().includes( 'enviado' ), 'con otro correo no se envía' );
		page.removeAllListeners( 'dialog' );
		page.on( 'dialog', ( d ) => d.accept( 'ANA@example.org' ) );
		await Promise.all( [ page.waitForURL( '**/enviado' ), page.click( '#be' ) ] );
		ok( ( await enviado( page ) ).includes( 'evt_confirm_email=ANA@example.org' ), 'con su correo se envía, y viaja lo escrito' );
		await page.close();
	}
	{
		// Sin JavaScript: el campo se ve y se escribe a mano; el servidor compara.
		const sinJs = await browser.newContext( { javaScriptEnabled: false } );
		const page = await abrir( sinJs, true );
		ok( await page.isVisible( '#fe-correo' ), 'sin JavaScript el campo del correo se ve' );
		await page.fill( '#fe-correo', 'ana@example.org' );
		await Promise.all( [ page.waitForURL( '**/enviado' ), page.click( '#be' ) ] );
		ok( ( await enviado( page ) ).includes( 'evt_confirm_email=ana@example.org' ), 'y se envía con lo escrito' );
		await sinJs.close();
	}
	{
		// Con SweetAlert: el diálogo pide el correo y no deja pasar otro.
		const page = await abrir( ctx, true );
		ok( ! ( await page.isVisible( '#fe-correo' ) ), 'con guion, el campo del formulario sobra y no se ve' );
		await page.click( '#be' );
		await page.waitForSelector( '.swal2-input', { state: 'visible' } );
		const detalle = ( await page.textContent( '.swal2-html-container' ) ).trim();
		ok( detalle.includes( 'ana@example.org' ), 'el diálogo enseña el correo: ' + detalle );
		await page.fill( '.swal2-input', 'otra@example.org' );
		await page.click( '.swal2-confirm' );
		await page.waitForSelector( '.swal2-validation-message', { state: 'visible' } );
		ok( ! page.url().includes( 'enviado' ), 'con otro correo avisa y no envía' );
		await page.fill( '.swal2-input', 'ana@example.org' );
		await Promise.all( [ page.waitForURL( '**/enviado' ), page.click( '.swal2-confirm' ) ] );
		ok( ( await enviado( page ) ).includes( 'evt_confirm_email=ana@example.org' ), 'con su correo se envía' );
		await page.close();
	}

	// --- 5. Cerrar la edición de una página pulsando fuera --------------------
	{
		const page = await abrir( ctx, true );
		await page.click( '#editar' );
		await page.waitForSelector( '.evt-cajon--pagina', { state: 'visible' } );
		ok( true, 'la edición de la página se abre en el panel lateral' );
		await Promise.all( [ page.waitForNavigation(), page.mouse.click( 5, 300 ) ] );
		ok( ! page.url().includes( 'undefined' ), 'cerrar pulsando el fondo no lleva a …/undefined: ' + page.url() );
		ok( page.url().endsWith( '/panel' ), 'vuelve a la pantalla de la que salió' );
		await page.close();
	}

	await browser.close();
	console.log( fallos ? '\n' + fallos + ' FALLOS' : '\nTodo verde' );
	process.exit( fallos ? 1 : 0 );
} )();
