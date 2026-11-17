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

	it( 'does nothing without the library: the link below still works', async () => {
		document.body.innerHTML = '<div data-evt-qr="https://example.org/x"></div>';

		await load();

		expect( document.querySelector( '[data-evt-qr]' ).innerHTML ).toBe( '' );
	} );
} );
