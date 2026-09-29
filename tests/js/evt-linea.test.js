/**
 * Unit tests for assets/js/evt-linea.js: the public timeline strip.
 *
 * jsdom does no layout, so the month width and the track width are given, and
 * the scroll calls are recorded instead of performed.
 */
import { afterEach, describe, expect, it, vi } from 'vitest';

const MONTH = 200;

async function mount( { current = 3, trackWidth = 700 } = {} ) {
	document.body.innerHTML = `<div class="evt-linea" data-actual="${ current }">
		<button data-evt-linea="atras">‹</button>
		<button data-evt-linea="hoy">Hoy</button>
		<button data-evt-linea="adelante">›</button>
		<div class="evt-linea__pista">${ '<a class="evt-linea__mes" href="#"></a>'.repeat( 6 ) }</div>
	</div>`;
	const track = document.querySelector( '.evt-linea__pista' );
	track.querySelector( '.evt-linea__mes' ).getBoundingClientRect = () => ( { width: MONTH } );
	Object.defineProperty( track, 'clientWidth', { value: trackWidth } );
	Object.defineProperty( track, 'scrollLeft', { value: 0, writable: true } );
	track.scrollTo = vi.fn();
	track.scrollBy = vi.fn();
	track.setPointerCapture = vi.fn();
	vi.resetModules();
	await import( '../../assets/js/evt-linea.js' );
	return track;
}

function pointer( target, type, props ) {
	target.dispatchEvent( Object.assign( new Event( type, { bubbles: true } ), { pointerType: 'mouse', button: 0, pointerId: 1, ...props } ) );
}

function line() {
	return document.querySelector( '.evt-linea' );
}

afterEach( () => {
	delete window.matchMedia;
} );

describe( 'timeline', () => {
	it( 'opens on the current month with the previous one in view when three fit', async () => {
		const track = await mount();
		expect( line().classList.contains( 'evt-linea--viva' ) ).toBe( true );
		expect( track.scrollTo ).toHaveBeenCalledWith( { left: 2 * MONTH, behavior: 'auto' } );
	} );

	it( 'opens on the current month itself when fewer than three fit', async () => {
		const track = await mount( { trackWidth: 500 } );
		expect( track.scrollTo ).toHaveBeenCalledWith( { left: 3 * MONTH, behavior: 'auto' } );
	} );

	it( 'moves one month with the arrows and back to today, smoothly unless motion is reduced', async () => {
		window.matchMedia = vi.fn( () => ( { matches: false } ) );
		const track = await mount();
		const button = ( name ) => document.querySelector( `[data-evt-linea="${ name }"]` );

		button( 'atras' ).click();
		expect( track.scrollBy ).toHaveBeenLastCalledWith( { left: -MONTH, behavior: 'smooth' } );
		button( 'adelante' ).click();
		expect( track.scrollBy ).toHaveBeenLastCalledWith( { left: MONTH, behavior: 'smooth' } );
		button( 'hoy' ).click();
		expect( track.scrollTo ).toHaveBeenLastCalledWith( { left: 2 * MONTH, behavior: 'smooth' } );
	} );

	it( 'drags with the mouse past a small threshold, and the closing click opens nothing', async () => {
		const track = await mount();
		track.scrollLeft = 100;
		pointer( track, 'pointerdown', { clientX: 300 } );
		pointer( track, 'pointermove', { clientX: 297 } );
		expect( track.scrollLeft ).toBe( 100 );
		expect( line().classList.contains( 'evt-linea--arrastrando' ) ).toBe( false );

		pointer( track, 'pointermove', { clientX: 250 } );
		expect( track.scrollLeft ).toBe( 150 );
		expect( line().classList.contains( 'evt-linea--arrastrando' ) ).toBe( true );
		expect( track.setPointerCapture ).toHaveBeenCalledWith( 1 );

		pointer( track, 'pointerup', {} );
		expect( line().classList.contains( 'evt-linea--arrastrando' ) ).toBe( false );
		const month = track.querySelector( 'a' );
		const closing = new MouseEvent( 'click', { bubbles: true, cancelable: true } );
		month.dispatchEvent( closing );
		expect( closing.defaultPrevented ).toBe( true );

		// Una vez cerrado el gesto, el siguiente clic vuelve a abrir el mes.
		const next = new MouseEvent( 'click', { bubbles: true, cancelable: true } );
		month.addEventListener( 'click', ( e ) => e.preventDefault() );
		const seen = vi.fn();
		track.addEventListener( 'click', seen );
		month.dispatchEvent( next );
		expect( seen ).toHaveBeenCalled();
	} );

	it( 'leaves touch and non-primary buttons to the browser', async () => {
		const track = await mount();
		track.scrollLeft = 100;
		pointer( track, 'pointerdown', { pointerType: 'touch', clientX: 300 } );
		pointer( track, 'pointermove', { clientX: 200 } );
		pointer( track, 'pointerdown', { button: 2, clientX: 300 } );
		pointer( track, 'pointermove', { clientX: 200 } );
		expect( track.scrollLeft ).toBe( 100 );
	} );

	it( 'does nothing on a timeline without months', async () => {
		document.body.innerHTML = '<div class="evt-linea"><div class="evt-linea__pista"></div></div>';
		vi.resetModules();
		await import( '../../assets/js/evt-linea.js' );
		expect( line().classList.contains( 'evt-linea--viva' ) ).toBe( false );
	} );
} );
