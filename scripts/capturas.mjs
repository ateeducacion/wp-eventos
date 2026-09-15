/**
 * Guion de capturas del aplicativo de eventos.
 *
 * Recorre las pantallas con Playwright y deja un informe HTML con las capturas
 * comentadas. Sirve para ver de un vistazo cómo queda todo después de un
 * cambio, y para que un PR enseñe lo que toca sin que nadie tenga que levantar
 * el entorno.
 *
 * **Dos tamaños, y cada uno para lo suyo.** El aplicativo —el taller del
 * evento, el listado, el escritorio— se usa sentado delante de un ordenador, y
 * ahí es donde hay que mirarlo. Lo que sí llega en el móvil de cualquiera es la
 * **página pública de un evento y sus páginas satélite**, porque el enlace se
 * comparte por mensajería: esas van también en vertical.
 *
 * El guion es la constante ESCENAS. Cada escena dice quién entra, a dónde va,
 * qué se está enseñando y en qué tamaño: añadir una pantalla al informe es
 * añadir una escena, no tocar el motor.
 *
 * Uso:  make capturas                  (todo)
 *       make capturas SOLO=movil       (solo las del móvil)
 *       make capturas SOLO=escritorio  (solo las del ordenador)
 */

import { chromium, devices } from 'playwright';
import { mkdir, writeFile, rm } from 'node:fs/promises';
import path from 'node:path';

const BASE = process.env.EVT_URL || 'http://localhost:8798';
const OUT = process.env.EVT_CAPTURAS || 'capturas';
const SOLO = process.env.SOLO || '';

/** El evento de demostración que se enseña. */
const EVENTO = 'jornadas-tecnologia-educativa';

/** Quién entra en cada escena del aplicativo. */
const USUARIOS = {
	organizacion: { user: 'organizacion', pass: 'password', etiqueta: 'Organización (Innovación)' },
	admin: { user: 'admin', pass: 'password', etiqueta: 'Administración' },
};

/**
 * El guion.
 *
 * `pantalla` es `escritorio` o `movil`; `quien` vacío significa **sin entrar**,
 * que es como se ve una página pública y como tiene que verse.
 */
const ESCENAS = [
	// ── El aplicativo, en el ordenador ────────────────────────────────────
	{
		capitulo: 'El aplicativo',
		titulo: 'Mis eventos',
		nota: 'El listado con el que se abre: cada evento con su área, su estado derivado de las fechas y el interruptor de publicación.',
		quien: 'organizacion',
		ir: '/mis-eventos/',
		pantalla: 'escritorio',
	},
	{
		capitulo: 'El taller del evento',
		titulo: 'Páginas',
		nota: 'Las páginas satélite del evento, con su orden y sus acciones de fila.',
		quien: 'organizacion',
		ir: `/evento/?evento={ID}&panel=secciones`,
		pantalla: 'escritorio',
	},
	{
		capitulo: 'El taller del evento',
		titulo: 'Ponentes',
		nota: 'Las fichas de quien habla, con los botones de icono y el orden que decide el área.',
		quien: 'organizacion',
		ir: `/evento/?evento={ID}&panel=ponentes`,
		pantalla: 'escritorio',
	},
	{
		capitulo: 'El taller del evento',
		titulo: 'Programa',
		nota: 'La parrilla agrupada por día y, dentro del día, por sede: un mismo día puede tener dos.',
		quien: 'organizacion',
		ir: `/evento/?evento={ID}&panel=programa`,
		pantalla: 'escritorio',
	},
	{
		capitulo: 'El taller del evento',
		titulo: 'Talleres',
		nota: 'Solo las actividades de tipo taller, con su aforo y cuántas plazas van ocupadas.',
		quien: 'organizacion',
		ir: `/evento/?evento={ID}&panel=talleres`,
		pantalla: 'escritorio',
	},
	{
		capitulo: 'El taller del evento',
		titulo: 'Inscripción',
		nota: 'Los dos plazos, los textos del consentimiento con su versión, y las preguntas propias del evento.',
		quien: 'organizacion',
		ir: `/evento/?evento={ID}&panel=inscripcion`,
		pantalla: 'escritorio',
	},
	{
		capitulo: 'El taller del evento',
		titulo: 'Participantes',
		nota: 'Quién se ha inscrito, el buscador que no distingue tildes y la exportación a CSV por POST.',
		quien: 'organizacion',
		ir: `/evento/?evento={ID}&panel=participantes`,
		pantalla: 'escritorio',
	},
	{
		capitulo: 'El taller del evento',
		titulo: 'Ajustes',
		nota: 'Título, fechas, área, tipología y curso: lo que define el evento.',
		quien: 'organizacion',
		ir: `/evento/?evento={ID}&panel=ajustes`,
		pantalla: 'escritorio',
	},
	{
		capitulo: 'El taller del evento',
		titulo: 'Apariencia',
		nota: 'Los colores de la cabecera, las tipografías y las imágenes del evento.',
		quien: 'organizacion',
		ir: `/evento/?evento={ID}&panel=apariencia`,
		pantalla: 'escritorio',
	},

	// ── El escritorio de WordPress ────────────────────────────────────────
	{
		capitulo: 'El escritorio',
		titulo: 'Listado de eventos',
		nota: 'Las columnas propias, Área y Estado, justo detrás del título.',
		quien: 'admin',
		ir: '/wp-admin/edit.php?post_type=evt_event',
		pantalla: 'escritorio',
	},
	{
		capitulo: 'El escritorio',
		titulo: 'Ajustes y diagnóstico',
		nota: 'Qué hay montado en este sitio: tipos, taxonomías y roles. No guarda nada.',
		quien: 'admin',
		ir: '/wp-admin/edit.php?post_type=evt_event&page=evt-settings',
		pantalla: 'escritorio',
	},

	// ── La página pública, en el móvil ────────────────────────────────────
	{
		capitulo: 'El evento, en el móvil',
		titulo: 'Portada del evento',
		nota: 'Lo que ve quien recibe el enlace por mensajería: cabecera, fechas, sede y las tarjetas de las demás páginas.',
		quien: '',
		ir: `/evento/${EVENTO}/`,
		pantalla: 'movil',
	},
	{
		capitulo: 'El evento, en el móvil',
		titulo: 'Programa',
		nota: 'La página satélite del programa.',
		quien: '',
		ir: `/evento/${EVENTO}/programa/`,
		pantalla: 'movil',
	},
	{
		capitulo: 'El evento, en el móvil',
		titulo: 'Ponentes',
		nota: 'Quién habla, con su cargo y su entidad.',
		quien: '',
		ir: `/evento/${EVENTO}/ponentes/`,
		pantalla: 'movil',
	},
	{
		capitulo: 'El evento, en el móvil',
		titulo: 'Inscripción',
		nota: 'El formulario: el núcleo fijo, las preguntas del evento y el consentimiento.',
		quien: '',
		ir: `/evento/${EVENTO}/inscripcion/`,
		pantalla: 'movil',
	},
	{
		capitulo: 'El evento, en el móvil',
		titulo: 'Contacto',
		nota: 'La última de las satélite, para ver que una página sin nada especial también queda bien.',
		quien: '',
		ir: `/evento/${EVENTO}/contacto/`,
		pantalla: 'movil',
	},
];

const PANTALLAS = {
	escritorio: { viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 },
	movil: devices['Pixel 7'],
};

/**
 * Entra en el aplicativo con una cuenta, o sale si no hay que entrar.
 *
 * @param {import('playwright').Page} page   La pestaña.
 * @param {string}                    quien  Clave de USUARIOS; '' para salir.
 * @param {string}                    actual Quién está dentro ahora.
 * @return {Promise<string>} Quién queda dentro.
 */
async function entrar( page, quien, actual ) {
	if ( quien === actual ) {
		return actual;
	}
	await page.context().clearCookies();
	if ( '' === quien ) {
		return '';
	}
	const { user, pass } = USUARIOS[ quien ];
	await page.goto( `${ BASE }/wp-login.php`, { waitUntil: 'domcontentloaded' } );
	await page.fill( '#user_login', user );
	await page.fill( '#user_pass', pass );
	await Promise.all( [
		page.waitForNavigation( { waitUntil: 'domcontentloaded' } ),
		page.click( '#wp-submit' ),
	] );
	return quien;
}

/**
 * El identificador del evento de demostración, que el guion necesita para las
 * direcciones del taller.
 *
 * Se pregunta al sitio en vez de clavarlo: el número cambia con cada
 * reprovisión y una captura contra un evento que no existe no avisa, sale en
 * blanco.
 *
 * @param {import('playwright').Page} page La pestaña, ya dentro.
 * @return {Promise<number>}
 */
async function idDelEvento( page ) {
	await page.goto( `${ BASE }/mis-eventos/`, { waitUntil: 'domcontentloaded' } );
	const href = await page.getAttribute( 'a[href*="evento="]', 'href' );
	const id = href ? Number( new URL( href, BASE ).searchParams.get( 'evento' ) ) : 0;
	if ( ! id ) {
		throw new Error( 'No encuentro ningún evento en «Mis eventos»: ¿está provisionado el entorno?' );
	}
	return id;
}

/**
 * Captura una escena.
 *
 * @param {import('playwright').Browser} navegador El navegador.
 * @param {object}                       escena    La escena del guion.
 * @param {number}                       evento    ID del evento de demostración.
 * @param {number}                       n         Número de escena, para el nombre del fichero.
 * @return {Promise<object>} La escena con su resultado.
 */
async function capturar( navegador, escena, evento, n ) {
	const contexto = await navegador.newContext( {
		...PANTALLAS[ escena.pantalla ],
		locale: 'es-ES',
	} );
	const page = await contexto.newPage();
	const img = `${ String( n ).padStart( 2, '0' ) }-${ escena.pantalla }-${ escena.titulo
		.toLowerCase()
		.normalize( 'NFD' )
		.replace( /[̀-ͯ]/g, '' )
		.replace( /[^a-z0-9]+/g, '-' )
		.replace( /^-|-$/g, '' ) }.png`;

	const resultado = { ...escena, img, ok: true, error: '' };

	try {
		await entrar( page, escena.quien, '' );
		const destino = escena.ir.replace( '{ID}', String( evento ) );
		const respuesta = await page.goto( `${ BASE }${ destino }`, { waitUntil: 'networkidle' } );
		if ( respuesta && respuesta.status() >= 400 ) {
			throw new Error( `la página respondió ${ respuesta.status() }` );
		}
		// La barra de administración tapa la cabecera y no es del aplicativo.
		await page.addStyleTag( { content: '#wpadminbar { display: none !important; } html { margin-top: 0 !important; }' } );
		await page.screenshot( { path: path.join( OUT, 'img', img ), fullPage: true } );
	} catch ( error ) {
		resultado.ok = false;
		resultado.error = error.message;
		// Una captura de lo que haya sirve más que ninguna: enseña dónde murió.
		await page.screenshot( { path: path.join( OUT, 'img', img ) } ).catch( () => {} );
	} finally {
		await contexto.close();
	}

	return resultado;
}

/**
 * El informe HTML.
 *
 * @param {object[]} capturas Lo capturado.
 * @return {string}
 */
function informe( capturas ) {
	const escapar = ( s ) => String( s ).replace( /[&<>"]/g, ( c ) => ( { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[ c ] ) );
	let html = '';
	let capitulo = '';

	for ( const c of capturas ) {
		if ( c.capitulo !== capitulo ) {
			capitulo = c.capitulo;
			html += `<h2>${ escapar( capitulo ) }</h2>\n`;
		}
		html += `<figure class="${ c.pantalla }${ c.ok ? '' : ' mal' }">
	<figcaption><strong>${ escapar( c.titulo ) }</strong><span>${ escapar( c.nota ) }</span>${
		c.ok ? '' : `<em>✗ ${ escapar( c.error ) }</em>`
	}</figcaption>
	<a href="img/${ c.img }"><img src="img/${ c.img }" alt="${ escapar( c.titulo ) }"></a>
</figure>\n`;
	}

	const fallos = capturas.filter( ( c ) => ! c.ok ).length;

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
	figcaption span { color: #666; font-size: .9em; }
	figcaption em { color: #b00; font-style: normal; }
	img { max-width: 100%; border: 1px solid #ccc; border-radius: 4px; }
	figure.movil img { max-width: 380px; }
	figure.mal img { border-color: #b00; }
</style>
<h1>Capturas del aplicativo de eventos</h1>
<p class="resumen">${ capturas.length } capturas · ${
		fallos ? `<strong>${ fallos } sin completar</strong>` : 'todas completadas'
	} · ${ new Date().toISOString().slice( 0, 16 ).replace( 'T', ' ' ) }</p>
${ html }`;
}

const escenas = ESCENAS.filter( ( e ) => '' === SOLO || e.pantalla === SOLO );
if ( 0 === escenas.length ) {
	console.error( `SOLO=${ SOLO } no deja ninguna escena. Use «escritorio» o «movil».` );
	process.exit( 1 );
}

await rm( OUT, { recursive: true, force: true } );
await mkdir( path.join( OUT, 'img' ), { recursive: true } );

const navegador = await chromium.launch();
const capturas = [];

try {
	// El ID se pregunta una vez, con una sesión aparte: las escenas del móvil
	// no entran en el aplicativo y no podrían averiguarlo.
	const contexto = await navegador.newContext( PANTALLAS.escritorio );
	const page = await contexto.newPage();
	await entrar( page, 'organizacion', '' );
	const evento = await idDelEvento( page );
	await contexto.close();

	for ( const [ i, escena ] of escenas.entries() ) {
		const c = await capturar( navegador, escena, evento, i + 1 );
		capturas.push( c );
		console.log( `${ c.ok ? '✓' : '✗' } ${ String( i + 1 ).padStart( 2, '0' ) } ${ c.pantalla.padEnd( 11 ) } ${ c.titulo }${ c.ok ? '' : ` — ${ c.error }` }` );
	}
} finally {
	await navegador.close();
}

await writeFile( path.join( OUT, 'indice.json' ), JSON.stringify( capturas, null, 2 ) );
await writeFile( path.join( OUT, 'informe.html' ), informe( capturas ) );

const fallos = capturas.filter( ( c ) => ! c.ok );
console.log( `\n${ capturas.length } capturas en ${ OUT }/informe.html` );
if ( fallos.length ) {
	console.error( `${ fallos.length } escena(s) sin completar.` );
	process.exit( 1 );
}
