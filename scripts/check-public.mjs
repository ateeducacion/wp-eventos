#!/usr/bin/env node
/*
 * Comprueba que no se filtra información que no puede salir del repositorio.
 *
 * Este repositorio se publica como software libre. Eso significa que **nada de
 * lo que se versiona** puede decir de quién es el despliegue, dónde está, qué
 * infraestructura usa ni cómo era el sistema que se sustituye por dentro. Todo
 * eso vive en `.local/`, que está en el `.gitignore` (ADR-0030).
 *
 * No es una comprobación de estilo: es la que evita publicar la dirección del
 * servidor de analítica de una organización, el identificador de su sitio, o
 * el mapa de una instalación ajena.
 *
 * Node y sin dependencias, como el resto de las comprobaciones: el stack de
 * este repositorio es PHP y JavaScript, y un tercer lenguaje es un requisito
 * más que instalar en cada máquina y en CI.
 *
 *   node scripts/check-public.mjs           comprueba
 *   node scripts/check-public.mjs --list    además enseña cada línea
 */

import { execFileSync } from 'node:child_process';
import { readFileSync, statSync } from 'node:fs';
import { dirname, extname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );

/*
 * Qué no puede aparecer, y por qué. El consejo se le enseña a quien lo rompa,
 * así que dice qué hacer, no solo que está mal.
 */
const RULES = [
	{
		name: 'infraestructura de un despliegue concreto',
		pattern: /cau_ce|estadisticasweb|www\d*\.gobiernodecanarias|piwik/i,
		advice:
			'Las URL de cookies, analítica o servicios de una organización se configuran ' +
			'desde fuera (filtro `evt_chrome`), no se escriben aquí. Ver ADR-0030.',
	},
	{
		name: 'marca de una organización concreta',
		pattern: /Gobierno de Canarias|\bConsejer[íi]a\b|gobiernodecanarias\.org/i,
		advice:
			'El pie, la cabecera y los enlaces legales son de quien despliega: filtro ' +
			'`evt_chrome`, vacío por defecto. Ver ADR-0030.',
	},
	{
		name: 'nombre de la red o del subsitio de destino',
		pattern: /\becoescuela\b|\bmedusa\b/i,
		advice: 'Dónde se despliega no se versiona. Va en el `.env`, que no se sube.',
	},
	{
		name: 'detalle interno del sistema que se sustituye',
		pattern: /\bFormidable\b|formulario \d+|Vista \d+|campo \d{3}\b|snippet \d+|ate_code_snippets/i,
		advice:
			'Cómo era la instalación anterior por dentro —sus formularios, vistas, campos ' +
			'y fragmentos de código— es material de investigación: vive en `.local/`.',
	},
	{
		name: 'producto concreto del sistema que se sustituye',
		pattern: /\bDivi\b/i,
		advice:
			'El tema, el maquetador o el plugin que usa la instalación anterior se dicen ' +
			'en genérico —«el tema», «el maquetador»—: nombrarlos describe el stack de ' +
			'alguien. Ver ADR-0030.',
	},
	{
		name: 'unidad o centro concreto de una organización',
		pattern: /\bDGOEII\b|\bEnSe[ñn]as\b|\bLLEE\b|\bCEP [A-ZÁÉÍÓÚ]|centro de profesorado/i,
		advice:
			'Las áreas, servicios y centros del organigrama de alguien no se versionan: ' +
			'el vocabulario que siembra el entorno local es **de ejemplo** y cada instalación ' +
			'crea el suyo. Ver ADR-0030.',
	},
	{
		name: 'dónde se despliega, por su topónimo',
		pattern: /\bCanarias\b|\bTenerife\b|Gran Canaria|Las Palmas|La Laguna|El Hierro/i,
		advice:
			'Un nombre de lugar dice dónde está el despliegue. Los datos de demostración ' +
			'usan sedes inventadas —«Centro de formación Norte»—, no sitios reales.',
	},
	{
		name: 'repositorio privado de la casa',
		pattern: /wp-registro-visitas-centros|wp-documentate|\brvc_|\bRVC\b|documentate-app/i,
		advice:
			'Los demás aplicativos del equipo son repositorios privados: nombrarlos —o citar ' +
			'sus rutas, sus opciones o sus prefijos— publica código de otro. El argumento se ' +
			'escribe entero aquí y el mapa de piezas se queda en `.local/`. Ver ADR-0030.',
	},
	{
		name: 'cifras de la plantilla de una organización',
		pattern: /2\.?538|1\.?519|\b45 personas\b|\b14 personas\b|once áreas|11 áreas/i,
		advice:
			'Cuánta gente hay y en qué áreas no se publica. Si el dato sostiene una ' +
			'decisión, se escribe sin la cifra o se deja en `.local/`.',
	},
	{
		name: 'rutas locales de quien desarrolla',
		pattern: /\/Users\/|\/home\/[a-z]/,
		advice: 'Una ruta absoluta de un portátil no le sirve a nadie más y dice quién eres.',
	},
];

// Lo generado y lo que es, por definición, material de investigación.
const SKIP = [
	'.local/',
	'node_modules/',
	'vendor/',
	'snippets/evt-eventos-app.bundle.php',
	'scripts/check-public.mjs', // este fichero nombra lo que busca
];
const EXTENSIONS = new Set( [ '.php', '.md', '.js', '.mjs', '.css', '.html', '.json', '.yml', '.yaml', '.xml', '.dist', '.txt', '.py' ] );
const NO_EXTENSION = new Set( [ 'Makefile', 'Dockerfile' ] );

/**
 * Lo que git incluiría: se le pregunta a él, que ya conoce el `.gitignore`.
 *
 * @return {string[]} Rutas relativas a la raíz del repositorio.
 */
function tracked() {
	let out = '';
	try {
		out = execFileSync(
			'git',
			[ 'ls-files', '--cached', '--others', '--exclude-standard' ],
			{ cwd: ROOT, encoding: 'utf8', maxBuffer: 64 * 1024 * 1024 }
		);
	} catch {
		// Sin git no hay lista fiable de lo que se publicaría, y adivinarla
		// sería peor que decirlo: se para.
		console.error( 'No se ha podido preguntar a git qué ficheros se versionan.' );
		process.exit( 2 );
	}

	return out
		.split( '\n' )
		.filter( ( f ) => f && ! SKIP.some( ( x ) => f.includes( x ) ) )
		.filter( ( f ) => {
			const name = f.split( '/' ).pop();
			if ( ! EXTENSIONS.has( extname( f ) ) && ! NO_EXTENSION.has( name ) ) {
				return false;
			}
			try {
				return statSync( join( ROOT, f ) ).isFile();
			} catch {
				return false;
			}
		} );
}

const verbose = process.argv.includes( '--list' );
const found = new Map(); // nombre de la regla → apariciones

for ( const file of tracked() ) {
	let text;
	try {
		text = readFileSync( join( ROOT, file ), 'utf8' );
	} catch {
		continue;
	}
	const lines = text.split( '\n' );
	for ( let i = 0; i < lines.length; i++ ) {
		for ( const rule of RULES ) {
			if ( rule.pattern.test( lines[ i ] ) ) {
				if ( ! found.has( rule.name ) ) {
					found.set( rule.name, [] );
				}
				found.get( rule.name ).push( {
					file,
					line: i + 1,
					text: lines[ i ].trim().slice( 0, 120 ),
				} );
			}
		}
	}
}

if ( found.size === 0 ) {
	console.log( 'Publicación: nada que no pueda salir del repositorio.' );
	process.exit( 0 );
}

const total = [ ...found.values() ].reduce( ( n, v ) => n + v.length, 0 );
console.log( `Publicación: ${ total } aparición(es) que no pueden ir a un repositorio público.\n` );

for ( const rule of RULES ) {
	const hits = found.get( rule.name );
	if ( ! hits ) {
		continue;
	}
	const files = [ ...new Set( hits.map( ( c ) => c.file ) ) ].sort();
	console.log( `  ${ rule.name }: ${ hits.length } en ${ files.length } fichero(s)` );
	console.log( `    → ${ rule.advice }` );
	for ( const f of files.slice( 0, 10 ) ) {
		const count = hits.filter( ( c ) => c.file === f ).length;
		console.log( `      ${ String( count ).padStart( 4 ) }  ${ f }` );
	}
	if ( files.length > 10 ) {
		console.log( `      … y ${ files.length - 10 } fichero(s) más` );
	}
	if ( verbose ) {
		for ( const c of hits ) {
			console.log( `        ${ c.file }:${ c.line }: ${ c.text }` );
		}
	}
	console.log();
}

console.log( 'Con `--list` se ve cada línea. Lo que sea material de investigación va a `.local/`.' );
process.exit( 1 );
