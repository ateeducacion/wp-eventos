/**
 * Screenshot script for the events application.
 *
 * Recorre las pantallas con Playwright y deja un informe HTML con las capturas
 * comentadas. Sirve para ver de un vistazo cómo queda todo después de un
 * cambio, y para que un PR enseñe lo que toca sin que nadie tenga que levantar
 * el entorno.
 *
 * **Cada pantalla se captura donde se usa, y algunas se usan en los dos
 * sitios.** El aplicativo —el taller del evento, el listado, el escritorio— se
 * usa sentado delante de un ordenador, así que va solo en horizontal. La
 * **página pública de un evento** va en los dos: se proyecta en una sala y se
 * abre en el móvil de quien recibe el enlace por mensajería, y las dos formas
 * tienen que aguantar.
 *
 * El guion es la constante SCENES. Cada escena dice quién entra, a dónde va,
 * qué se está enseñando y en qué tamaños: añadir una pantalla al informe es
 * añadir una escena, no tocar el motor.
 *
 * Uso:  make capturas                (todo)
 *       make capturas ONLY=mobile    (solo las de móvil)
 *       make capturas ONLY=desktop   (solo las de ordenador)
 */

import { chromium, devices } from 'playwright';
import { mkdir, writeFile, rm } from 'node:fs/promises';
import path from 'node:path';

const BASE = process.env.EVT_URL || 'http://localhost:8798';
const OUT = process.env.EVT_SCREENSHOTS || 'capturas';
const ONLY = process.env.ONLY || '';

/** El evento de demostración que se enseña. */
const EVENT = 'jornadas-tecnologia-educativa';

/** Quién entra en cada escena del aplicativo. */
const USERS = {
	organizacion: { user: 'organizacion', pass: 'password' },
	admin: { user: 'admin', pass: 'password' },
};

const SCREENS = {
	desktop: { viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 },
	mobile: devices[ 'Pixel 7' ],
};

/**
 * El guion.
 *
 * `who` vacío significa **sin entrar**, que es como se ve una página pública y
 * como tiene que verse. `screens` dice en qué tamaños se captura esa escena.
 */
const SCENES = [
	// ── El aplicativo: solo ordenador ─────────────────────────────────────
	{
		chapter: 'El aplicativo',
		title: 'Mis eventos',
		note: 'El listado con el que se abre: cada evento con su área, su estado derivado de las fechas y el interruptor de publicación.',
		who: 'organizacion',
		go: '/mis-eventos/',
		screens: [ 'desktop' ],
	},
	{
		chapter: 'El taller del evento',
		title: 'Páginas',
		note: 'Las páginas satélite del evento, con su orden y sus acciones de fila.',
		who: 'organizacion',
		go: '/evento/?evento={ID}&panel=secciones',
		screens: [ 'desktop' ],
	},
	{
		chapter: 'El taller del evento',
		title: 'Ponentes',
		note: 'Las fichas de quien habla, con los botones de icono y el orden que decide el área.',
		who: 'organizacion',
		go: '/evento/?evento={ID}&panel=ponentes',
		screens: [ 'desktop' ],
	},
	{
		chapter: 'El taller del evento',
		title: 'Programa',
		note: 'La parrilla agrupada por día y, dentro del día, por sede: un mismo día puede tener dos.',
		who: 'organizacion',
		go: '/evento/?evento={ID}&panel=programa',
		screens: [ 'desktop' ],
	},
	{
		chapter: 'El taller del evento',
		title: 'Talleres',
		note: 'Solo las actividades de tipo taller, con su aforo y cuántas plazas van ocupadas.',
		who: 'organizacion',
		go: '/evento/?evento={ID}&panel=talleres',
		screens: [ 'desktop' ],
	},
	{
		chapter: 'El taller del evento',
		title: 'Inscripción',
		note: 'Los dos plazos, los textos del consentimiento con su versión, y las preguntas propias del evento.',
		who: 'organizacion',
		go: '/evento/?evento={ID}&panel=inscripcion',
		screens: [ 'desktop' ],
	},
	{
		chapter: 'El taller del evento',
		title: 'Participantes',
		note: 'Quién se ha inscrito, el buscador que no distingue tildes y la exportación a CSV por POST.',
		who: 'organizacion',
		go: '/evento/?evento={ID}&panel=participantes',
		screens: [ 'desktop' ],
	},
	{
		chapter: 'El taller del evento',
		title: 'Ajustes',
		note: 'Título, fechas, área, tipología y curso: lo que define el evento.',
		who: 'organizacion',
		go: '/evento/?evento={ID}&panel=ajustes',
		screens: [ 'desktop' ],
	},
	{
		chapter: 'El taller del evento',
		title: 'Apariencia',
		note: 'Los colores de la cabecera, las tipografías y las imágenes del evento.',
		who: 'organizacion',
		go: '/evento/?evento={ID}&panel=apariencia',
		screens: [ 'desktop' ],
	},
	{
		chapter: 'El escritorio',
		title: 'Listado de eventos',
		note: 'Las columnas propias, Área y Estado, justo detrás del título.',
		who: 'admin',
		go: '/wp-admin/edit.php?post_type=evt_event',
		screens: [ 'desktop' ],
	},
	{
		chapter: 'El escritorio',
		title: 'Ajustes y diagnóstico',
		note: 'Qué hay montado en este sitio: tipos, taxonomías y roles. No guarda nada.',
		who: 'admin',
		go: '/wp-admin/edit.php?post_type=evt_event&page=evt-settings',
		screens: [ 'desktop' ],
	},

	// ── La página pública: los dos tamaños ────────────────────────────────
	{
		chapter: 'El evento, público',
		title: 'Portada del evento',
		note: 'Cabecera, fechas, sede y las tarjetas de las demás páginas. Se proyecta en una sala y se abre en el móvil de quien recibe el enlace.',
		who: '',
		go: `/evento/${ EVENT }/`,
		screens: [ 'desktop', 'mobile' ],
	},
	{
		chapter: 'El evento, público',
		title: 'Programa',
		note: 'La parrilla pública, agrupada por día y por sede.',
		who: '',
		go: `/evento/${ EVENT }/programa/`,
		screens: [ 'desktop', 'mobile' ],
	},
	{
		chapter: 'El evento, público',
		title: 'Ponentes',
		note: 'Quién habla, con su cargo y su entidad.',
		who: '',
		go: `/evento/${ EVENT }/ponentes/`,
		screens: [ 'desktop', 'mobile' ],
	},
	{
		chapter: 'El evento, público',
		title: 'Inscripción',
		note: 'El formulario: el núcleo fijo, las preguntas del evento y el consentimiento.',
		who: '',
		go: `/evento/${ EVENT }/inscripcion/`,
		screens: [ 'desktop', 'mobile' ],
	},
	{
		chapter: 'El evento, público',
		title: 'Contacto',
		note: 'La última de las satélite: una página sin nada especial también tiene que quedar bien.',
		who: '',
		go: `/evento/${ EVENT }/contacto/`,
		screens: [ 'desktop', 'mobile' ],
	},
];

/**
 * Log in with one of the demo accounts, or log out when there is nobody.
 *
 * @param {import('playwright').Page} page The tab.
 * @param {string}                    who  Key of USERS; '' to stay logged out.
 * @return {Promise<void>}
 */
async function logIn( page, who ) {
	if ( '' === who ) {
		return;
	}
	const { user, pass } = USERS[ who ];
	await page.goto( `${ BASE }/wp-login.php`, { waitUntil: 'domcontentloaded' } );
	await page.fill( '#user_login', user );
	await page.fill( '#user_pass', pass );
	await Promise.all( [
		page.waitForNavigation( { waitUntil: 'domcontentloaded' } ),
		page.click( '#wp-submit' ),
	] );
}

/**
 * The ID of the demo event, which the workshop URLs need.
 *
 * Se pregunta al sitio en vez de clavarlo: el número cambia con cada
 * reprovisión y una captura contra un evento que no existe no avisa, sale en
 * blanco.
 *
 * @param {import('playwright').Page} page The tab, already logged in.
 * @return {Promise<number>}
 */
async function eventId( page ) {
	await page.goto( `${ BASE }/mis-eventos/`, { waitUntil: 'domcontentloaded' } );
	const href = await page.getAttribute( 'a[href*="evento="]', 'href' );
	const id = href ? Number( new URL( href, BASE ).searchParams.get( 'evento' ) ) : 0;
	if ( ! id ) {
		throw new Error( 'No encuentro ningún evento en «Mis eventos»: ¿está provisionado el entorno?' );
	}
	return id;
}

/**
 * A file-name-safe slug.
 *
 * @param {string} text Any text.
 * @return {string}
 */
function slug( text ) {
	return text
		.toLowerCase()
		.normalize( 'NFD' )
		.replace( /[̀-ͯ]/g, '' )
		.replace( /[^a-z0-9]+/g, '-' )
		.replace( /^-|-$/g, '' );
}

/**
 * Take one screenshot of one scene at one screen size.
 *
 * @param {import('playwright').Browser} browser The browser.
 * @param {object}                       scene   The scene.
 * @param {string}                       screen  'desktop' or 'mobile'.
 * @param {number}                       id      Demo event ID.
 * @param {number}                       n       Scene number, for the file name.
 * @return {Promise<object>} The scene with its result.
 */
async function capture( browser, scene, screen, id, n ) {
	const context = await browser.newContext( { ...SCREENS[ screen ], locale: 'es-ES' } );
	const page = await context.newPage();
	// Ruta relativa a la raíz del informe, no solo el nombre: es la que usan
	// el HTML y el comentario del PR, y con una sola cadena no hay dos sitios
	// donde olvidarse del directorio.
	const img = `img/${ String( n ).padStart( 2, '0' ) }-${ screen }-${ slug( scene.title ) }.png`;
	const shot = { ...scene, screen, img, ok: true, error: '' };
	delete shot.screens;

	try {
		await logIn( page, scene.who );
		const target = scene.go.replace( '{ID}', String( id ) );
		const response = await page.goto( `${ BASE }${ target }`, { waitUntil: 'networkidle' } );
		if ( response && response.status() >= 400 ) {
			throw new Error( `la página respondió ${ response.status() }` );
		}
		// La barra de administración tapa la cabecera y no es del aplicativo.
		await page.addStyleTag( {
			content: '#wpadminbar { display: none !important; } html { margin-top: 0 !important; }',
		} );
		await page.screenshot( { path: path.join( OUT, img ), fullPage: true } );
	} catch ( error ) {
		shot.ok = false;
		shot.error = error.message;
		// Una captura de lo que haya sirve más que ninguna: enseña dónde murió.
		await page.screenshot( { path: path.join( OUT, img ) } ).catch( () => {} );
	} finally {
		await context.close();
	}

	return shot;
}

/**
 * The HTML report.
 *
 * @param {object[]} shots What was captured.
 * @return {string}
 */
function report( shots ) {
	const esc = ( s ) =>
		String( s ).replace( /[&<>"]/g, ( c ) => ( { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[ c ] ) );
	let html = '';
	let chapter = '';

	for ( const s of shots ) {
		if ( s.chapter !== chapter ) {
			chapter = s.chapter;
			html += `<h2>${ esc( chapter ) }</h2>\n`;
		}
		html += `<figure class="${ s.screen }${ s.ok ? '' : ' mal' }">
	<figcaption><strong>${ esc( s.title ) }</strong> <small>${ esc( s.screen === 'mobile' ? 'móvil' : 'ordenador' ) }</small><span>${ esc(
			s.note
		) }</span>${ s.ok ? '' : `<em>✗ ${ esc( s.error ) }</em>` }</figcaption>
	<a href="${ s.img }"><img src="${ s.img }" alt="${ esc( s.title ) }"></a>
</figure>\n`;
	}

	const failed = shots.filter( ( s ) => ! s.ok ).length;

	return `<!doctype html>
<html lang="es">
<meta charset="utf-8">
<title>Capturas del aplicativo de eventos</title>
<style>
	:root { color-scheme: light dark; }
	body { font: 16px/1.5 system-ui, sans-serif; max-width: 1100px; margin: 2rem auto; padding: 0 1rem; }
	h1 { margin-bottom: .2rem; }
	.resumen { color: #666; margin-top: 0; }
	h2 { margin-top: 2.5rem; border-bottom: 1px solid #ccc; padding-bottom: .3rem; }
	figure { margin: 1.5rem 0; }
	figcaption { display: flex; flex-direction: column; gap: .15rem; margin-bottom: .5rem; }
	figcaption small { color: #888; font-weight: normal; }
	figcaption span { color: #666; font-size: .9em; }
	figcaption em { color: #b00; font-style: normal; }
	img { max-width: 100%; border: 1px solid #ccc; border-radius: 4px; }
	figure.mobile img { max-width: 380px; }
	figure.mal img { border-color: #b00; }
</style>
<h1>Capturas del aplicativo de eventos</h1>
<p class="resumen">${ shots.length } capturas · ${
		failed ? `<strong>${ failed } sin completar</strong>` : 'todas completadas'
	} · ${ new Date().toISOString().slice( 0, 16 ).replace( 'T', ' ' ) }</p>
${ html }`;
}

// Cada escena se despliega en una toma por tamaño, y el filtro se aplica sobre
// las tomas: ONLY=mobile deja las del móvil de una escena que tenga las dos.
const shots = SCENES.flatMap( ( scene ) =>
	scene.screens
		.filter( ( screen ) => '' === ONLY || screen === ONLY )
		.map( ( screen ) => ( { scene, screen } ) )
);

if ( 0 === shots.length ) {
	console.error( `ONLY=${ ONLY } no deja ninguna toma. Use «desktop» o «mobile».` );
	process.exit( 1 );
}

await rm( OUT, { recursive: true, force: true } );
await mkdir( path.join( OUT, 'img' ), { recursive: true } );

const browser = await chromium.launch();
const taken = [];

try {
	// El ID se pregunta una vez, con una sesión aparte: las tomas públicas no
	// entran en el aplicativo y no podrían averiguarlo.
	const context = await browser.newContext( SCREENS.desktop );
	const page = await context.newPage();
	await logIn( page, 'organizacion' );
	const id = await eventId( page );
	await context.close();

	for ( const [ i, { scene, screen } ] of shots.entries() ) {
		const shot = await capture( browser, scene, screen, id, i + 1 );
		taken.push( shot );
		console.log(
			`${ shot.ok ? '✓' : '✗' } ${ String( i + 1 ).padStart( 2, '0' ) } ${ screen.padEnd( 8 ) } ${ shot.title }${
				shot.ok ? '' : ` — ${ shot.error }`
			}`
		);
	}
} finally {
	await browser.close();
}

await writeFile( path.join( OUT, 'index.json' ), JSON.stringify( taken, null, 2 ) );
await writeFile( path.join( OUT, 'informe.html' ), report( taken ) );

const failed = taken.filter( ( s ) => ! s.ok );
console.log( `\n${ taken.length } capturas en ${ OUT }/informe.html` );
if ( failed.length ) {
	console.error( `${ failed.length } toma(s) sin completar.` );
	process.exit( 1 );
}
