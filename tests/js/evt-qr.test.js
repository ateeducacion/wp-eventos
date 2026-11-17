/**
 * Unit tests for assets/js/evt-qr.js: the QR codes of the attendance checkpoints.
 *
 * The real library is loaded from node_modules, as in development.
 */
import { afterEach, describe, expect, it, vi } from 'vitest';
import { createRequire } from 'node:module';

const require = createRequire( import.meta.url );

async function load() {
	vi.resetModules();
	await import( '../../assets/js/evt-qr.js' );
}

afterEach( () => {
	document.body.innerHTML = '';
	delete window.qrcode;
} );

describe( 'evt-qr', () => {
	it( 'paints an SVG code in every box, with its text alternative', async () => {
		window.qrcode = require( 'qrcode-generator' );
		document.body.innerHTML =
			'<div data-evt-qr="https://example.org/evento/inscripcion/?asistencia=abc" data-evt-qr-alt="QR de Entrada"></div>';

		await load();

		const caja = document.querySelector( '[data-evt-qr]' );
		expect( caja.querySelector( 'svg' ) ).not.toBeNull();
		expect( caja.innerHTML ).toContain( 'QR de Entrada' );
		expect( caja.classList.contains( 'evt-qr--listo' ) ).toBe( true );
	} );

	it( 'downloads a poster of the checkpoint and then shows what to do with it', async () => {
		window.qrcode = require( 'qrcode-generator' );
		const ctx = {
			measureText: ( t ) => ( { width: t.length * 20 } ),
			fillRect: vi.fn(),
			fillText: vi.fn(),
		};
		const getContext = vi.spyOn( HTMLCanvasElement.prototype, 'getContext' ).mockReturnValue( ctx );
		const toDataURL = vi.spyOn( HTMLCanvasElement.prototype, 'toDataURL' ).mockReturnValue( 'data:image/png;base64,AAAA' );
		let bajado = null;
		const click = vi.spyOn( HTMLAnchorElement.prototype, 'click' ).mockImplementation( function () {
			bajado = { href: this.href, download: this.download };
		} );
		document.body.innerHTML =
			'<div class="evt-punto">' +
			'<div data-evt-qr="https://example.org/x/?asistencia=abc" data-evt-qr-label="Entrada principal" data-evt-qr-event="Jornada de prueba"></div>' +
			'<button type="button" data-evt-qr-descargar hidden>Descargar el QR</button>' +
			'<div data-evt-qr-despues hidden></div></div>';

		await load();

		const boton = document.querySelector( '[data-evt-qr-descargar]' );
		expect( boton.hidden ).toBe( false );
		boton.click();

		expect( bajado.download ).toBe( 'asistencia-entrada-principal.png' );
		expect( bajado.href ).toContain( 'data:image/png' );
		const escrito = ctx.fillText.mock.calls.map( ( c ) => c[ 0 ] ).join( ' ' );
		expect( escrito ).toContain( 'Jornada de prueba' );
		expect( escrito ).toContain( 'Control de asistencia: Entrada principal' );
		expect( escrito ).toContain( 'Confirmar asistencia' );
		expect( document.querySelector( '[data-evt-qr-despues]' ).hidden ).toBe( false );

		getContext.mockRestore();
		toDataURL.mockRestore();
		click.mockRestore();
	} );

	it( 'keeps the download button hidden when there is no canvas to draw on', async () => {
		window.qrcode = require( 'qrcode-generator' );
		const getContext = vi.spyOn( HTMLCanvasElement.prototype, 'getContext' ).mockReturnValue( null );
		document.body.innerHTML =
			'<div class="evt-punto"><div data-evt-qr="https://example.org/x"></div>' +
			'<button type="button" data-evt-qr-descargar hidden></button></div>';

		await load();

		expect( document.querySelector( '[data-evt-qr-descargar]' ).hidden ).toBe( true );
		getContext.mockRestore();
	} );

	it( 'does nothing without the library: the link below still works', async () => {
		document.body.innerHTML = '<div data-evt-qr="https://example.org/x"></div>';

		await load();

		expect( document.querySelector( '[data-evt-qr]' ).innerHTML ).toBe( '' );
	} );
} );
