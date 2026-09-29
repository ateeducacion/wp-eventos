/**
 * Unit tests for assets/js/evt-evento.js: the programme days as tabs.
 */
import { describe, expect, it, vi } from 'vitest';

async function load() {
	vi.resetModules();
	await import( '../../assets/js/evt-evento.js' );
}

const PROGRAMME = `<div data-evt-pestanas>
	<section class="evt-ev__dia-panel"><h2 class="evt-ev__dia">Lunes</h2></section>
	<section class="evt-ev__dia-panel" id="martes"><h2 class="evt-ev__dia">Martes</h2></section>
	<section class="evt-ev__dia-panel"><p>Sin rótulo</p></section>
</div>`;

function tabs() {
	return Array.from( document.querySelectorAll( '[role="tab"]' ) );
}

function panels() {
	return Array.from( document.querySelectorAll( '.evt-ev__dia-panel' ) );
}

function key( name ) {
	const event = new KeyboardEvent( 'keydown', { key: name, bubbles: true, cancelable: true } );
	document.activeElement.dispatchEvent( event );
	return event;
}

describe( 'programme tabs', () => {
	it( 'turns each day into a tab labelled by its heading, with the first one open', async () => {
		document.body.innerHTML = PROGRAMME;
		await load();

		const list = document.querySelector( '[role="tablist"]' );
		expect( list.parentElement.firstElementChild ).toBe( list );
		expect( list.getAttribute( 'aria-label' ) ).toBe( 'Días del programa' );
		expect( tabs().map( ( tab ) => tab.textContent ) ).toEqual( [ 'Lunes', 'Martes', '3' ] );
		expect( tabs()[ 1 ].getAttribute( 'aria-controls' ) ).toBe( 'martes' );
		expect( panels()[ 0 ].id ).toBe( 'evt-dia-0-0' );
		expect( panels()[ 0 ].getAttribute( 'aria-labelledby' ) ).toBe( tabs()[ 0 ].id );

		expect( tabs().map( ( tab ) => tab.getAttribute( 'aria-selected' ) ) ).toEqual( [ 'true', 'false', 'false' ] );
		expect( tabs().map( ( tab ) => tab.tabIndex ) ).toEqual( [ 0, -1, -1 ] );
		expect( panels().map( ( panel ) => panel.hidden ) ).toEqual( [ false, true, true ] );
	} );

	it( 'opens the day that is clicked', async () => {
		document.body.innerHTML = PROGRAMME;
		await load();
		tabs()[ 1 ].dispatchEvent( new MouseEvent( 'click', { bubbles: true } ) );
		expect( panels().map( ( panel ) => panel.hidden ) ).toEqual( [ true, false, true ] );
		expect( tabs()[ 1 ].getAttribute( 'aria-selected' ) ).toBe( 'true' );
	} );

	it( 'moves between days with the arrows, Home and End, wrapping around', async () => {
		document.body.innerHTML = PROGRAMME;
		await load();
		tabs()[ 0 ].focus();

		expect( key( 'ArrowLeft' ).defaultPrevented ).toBe( true );
		expect( document.activeElement ).toBe( tabs()[ 2 ] );
		expect( panels()[ 2 ].hidden ).toBe( false );
		key( 'ArrowRight' );
		expect( document.activeElement ).toBe( tabs()[ 0 ] );
		key( 'End' );
		expect( document.activeElement ).toBe( tabs()[ 2 ] );
		key( 'Home' );
		expect( document.activeElement ).toBe( tabs()[ 0 ] );
		expect( key( 'Enter' ).defaultPrevented ).toBe( false );
	} );

	it( 'leaves a single day as it is', async () => {
		document.body.innerHTML = '<div data-evt-pestanas><section class="evt-ev__dia-panel"></section></div>';
		await load();
		expect( tabs() ).toHaveLength( 0 );
		expect( panels()[ 0 ].hidden ).toBe( false );
	} );
} );
