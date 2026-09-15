#!/usr/bin/env node
/*
 * Comprueba la propagación de errores de `make provision` sin tocar WordPress.
 *
 * `provision` encadena siete pasos y cada uno puede fallar. Lo que se comprueba
 * aquí es que **un fallo para la cadena** en vez de seguir a medias, y que el
 * único paso opcional —el idioma— avisa y continúa. Se hace con un ejecutable
 * de mentira en lugar de `wp-env`, así que no hace falta Docker ni un sitio.
 *
 * Node y sin dependencias: el stack de este repositorio es PHP y JavaScript, y
 * un tercer lenguaje es un requisito más que instalar en cada máquina y en CI.
 */

import { execFileSync, spawnSync } from 'node:child_process';
import { chmodSync, mkdtempSync, readdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import vm from 'node:vm';
import yaml from 'js-yaml';
import { tmpdir } from 'node:os';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );

/**
 * Trocea una orden como lo haría una shell, respetando las comillas.
 *
 * Lo justo para leer un `run:` de un workflow: comillas simples y dobles, sin
 * expansión de variables ni escapes, que en un workflow no hacen falta.
 *
 * @param {string} order The command line.
 * @return {string[]} Its words.
 */
function shellSplit( order ) {
	const words = [];
	let current = '';
	let quote = '';
	let open = false;

	for ( const ch of order ) {
		if ( quote ) {
			if ( ch === quote ) {
				quote = '';
			} else {
				current += ch;
			}
			continue;
		}
		if ( '"' === ch || "'" === ch ) {
			quote = ch;
			open = true;
			continue;
		}
		if ( /\s/.test( ch ) ) {
			if ( current || open ) {
				words.push( current );
				current = '';
				open = false;
			}
			continue;
		}
		current += ch;
	}
	if ( current || open ) {
		words.push( current );
	}
	return words;
}

/**
 * Para con un mensaje que dice qué se esperaba.
 *
 * @param {boolean} ok      Whether the expectation held.
 * @param {string}  message What was expected.
 * @param {string}  detail  What happened instead.
 */
function expect( ok, message, detail = '' ) {
	if ( ok ) {
		return;
	}
	console.error( `check-provision: ${ message }` );
	if ( detail ) {
		console.error( detail );
	}
	process.exit( 1 );
}

/*
 * Paso a paso, el target `provision` del Makefile. Si allí cambia el orden o el
 * número de pasos, esta lista tiene que cambiar con él: es justo lo que hace
 * que la comprobación sirva de algo.
 */
const STEPS = [
	'language core',
	'bundle',
	'sync-snippets',
	'provision-roles.php',
	'setup-vocabulary.php',
	'setup-pages.php',
	'seed-demo.php',
];

/**
 * Comprueba que los workflows son YAML válido y que su JavaScript compila.
 *
 * Los dos modos de fallar de un workflow son silenciosos en local:
 *
 * - **El fichero entero inválido.** GitHub no se salta el paso malo: rechaza el
 *   workflow y la ejecución sale en rojo **a los cero segundos**, sin un solo
 *   job que abrir y sin decir qué línea está mal.
 * - **El JavaScript de `github-script` con un error de sintaxis.** Va dentro de
 *   una cadena YAML, así que nadie lo compila hasta que el job corre — tres
 *   minutos después de empujar, y solo si el resto del job llegó hasta ahí.
 *
 * Las dos cosas se cazan aquí en un segundo. `js-yaml` entra como dependencia
 * declarada —venía de rebote con wp-env, y de rebote llegaba la 3, donde
 * `load()` no es la variante segura— y `vm` es de Node.
 *
 * @return {void}
 */
function comprobarWorkflows() {
	const dir = join( ROOT, '.github/workflows' );
	const ficheros = readdirSync( dir ).filter( ( f ) => f.endsWith( '.yml' ) || f.endsWith( '.yaml' ) );

	expect( ficheros.length > 0, 'no hay ningún workflow en .github/workflows' );

	let guiones = 0;

	for ( const nombre of ficheros ) {
		const ruta = join( dir, nombre );
		let workflow;
		try {
			workflow = yaml.load( readFileSync( ruta, 'utf8' ) );
		} catch ( error ) {
			expect( false, `.github/workflows/${ nombre } no es YAML válido`, error.message );
		}
		expect( workflow && workflow.jobs, `.github/workflows/${ nombre } no declara ningún job` );

		// El `script:` de actions/github-script es JavaScript dentro de YAML:
		// se compila sin ejecutarlo, que es lo que basta para ver un identificador
		// repetido o un paréntesis suelto.
		for ( const [ job, def ] of Object.entries( workflow.jobs ) ) {
			for ( const paso of def.steps || [] ) {
				const codigo = paso.with && paso.with.script;
				if ( 'string' !== typeof codigo || ! String( paso.uses || '' ).includes( 'github-script' ) ) {
					continue;
				}
				guiones += 1;
				try {
					// Como lo envuelve la propia acción: una función asíncrona.
					new vm.Script( `(async () => {\n${ codigo }\n})` );
				} catch ( error ) {
					expect(
						false,
						`${ nombre } → ${ job } → «${ paso.name || paso.uses }»: el script no compila`,
						error.message
					);
				}
			}
		}
	}

	console.log(
		`Workflows: ${ ficheros.length } fichero(s) con YAML válido y ${ guiones } guion(es) de github-script que compilan.`
	);
}

const workdir = mkdtempSync( join( tmpdir(), 'evt-provision-' ) );

try {
	const stub = join( workdir, 'command' );
	const trace = join( workdir, 'trace' );

	// El doble de `wp-env` y de `make`: apunta lo que le piden y falla solo
	// cuando se le dice qué paso tiene que romper.
	writeFileSync(
		stub,
		'#!/bin/sh\n' +
			'echo "$*" >> "$EVT_PROVISION_TRACE"\n' +
			'[ -z "$EVT_FAIL_STEP" ] && exit 0\n' +
			'case "$*" in *"$EVT_FAIL_STEP"*) exit 1;; esac\n' +
			'exit 0\n'
	);
	chmodSync( stub, 0o700 );

	for ( const failing of [ '', ...STEPS ] ) {
		writeFileSync( trace, '' );

		const run = spawnSync( 'make', [ 'provision', `WP_ENV=${ stub }`, `MAKE=${ stub }` ], {
			cwd: ROOT,
			encoding: 'utf8',
			env: { ...process.env, EVT_FAIL_STEP: failing, EVT_PROVISION_TRACE: trace },
		} );

		const calls = readFileSync( trace, 'utf8' ).split( '\n' ).filter( Boolean );
		// El idioma es el único paso opcional: si falla, avisa y sigue.
		const optional = '' === failing || 'language core' === failing;
		const output = ( run.stdout || '' ) + ( run.stderr || '' );

		expect(
			( 0 === run.status ) === optional,
			`fallando «${ failing || 'nada' }», la provisión ${ optional ? 'tenía que seguir' : 'tenía que pararse' }`,
			output
		);

		const expected = optional ? STEPS : STEPS.slice( 0, STEPS.indexOf( failing ) + 1 );
		expect(
			calls.length === expected.length,
			`fallando «${ failing || 'nada' }» se esperaban ${ expected.length } pasos y se dieron ${ calls.length }`,
			calls.join( '\n' )
		);
		expect(
			expected.every( ( step, i ) => calls[ i ].includes( step ) ),
			`fallando «${ failing || 'nada' }», los pasos no salieron en el orden del Makefile`,
			calls.join( '\n' )
		);

		if ( 'language core' === failing ) {
			expect( output.includes( 'AVISO:' ), 'el idioma falla en silencio: tenía que avisar', output );
		}
	}

	console.log( 'Provisión: éxito y fallos de los siete pasos comprobados; solo el idioma es opcional.' );

	/*
	 * El workflow de PHPMD no ejecuta phpmd a mano: llama a `make phpmd`
	 * pasándole el formato y el fichero de informe. Si el target dejara de
	 * reenviarlos, la ejecución seguiría en verde escupiendo texto y el SARIF no
	 * se escribiría nunca, así que se comprueba que lo que ordena el workflow
	 * llega al comando.
	 */
	const workflow = readFileSync( join( ROOT, '.github/workflows/phpmd.yml' ), 'utf8' );
	const order = workflow
		.split( '\n' )
		.map( ( line ) => line.trim() )
		.find( ( line ) => line.includes( 'run:' ) && line.includes( 'make phpmd' ) );
	expect( Boolean( order ), 'el workflow de PHPMD ya no llama a `make phpmd`' );

	comprobarWorkflows();

	// Lo que va detrás de `run:`, sin la palabra `make`, es lo que se le pasa.
	// Se trocea como lo haría una shell y no por espacios: uno de los
	// argumentos viene entrecomillado y lleva espacios dentro.
	const args = shellSplit( order.split( 'run:' )[ 1 ].trim() ).slice( 1 );

	const rendered = execFileSync( 'make', [ '--dry-run', ...args ], {
		cwd: ROOT,
		encoding: 'utf8',
	} );
	expect(
		rendered.includes( 'phpmd.xml' ) && rendered.includes( 'sarif' ),
		'`make phpmd` no reenvía el formato que pide el workflow',
		rendered
	);
	expect(
		rendered.includes( '--reportfile phpmd-results.sarif' ),
		'`make phpmd` no reenvía el fichero de informe que pide el workflow',
		rendered
	);
	console.log( 'PHPMD: el target reenvía el formato y el fichero de informe que pide el workflow.' );
} finally {
	rmSync( workdir, { recursive: true, force: true } );
}
