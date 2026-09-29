/**
 * Unit tests for assets/js/evt-mapa.js: the contact page map.
 *
 * Leaflet is replaced by a stand-in that records what the script asks of it.
 */
import { afterEach, describe, expect, it, vi } from 'vitest';

async function load() {
	vi.resetModules();
	await import( '../../assets/js/evt-mapa.js' );
}

function fakeLeaflet() {
	const map = { setView: vi.fn(), fitBounds: vi.fn() };
	const layer = { addTo: vi.fn() };
	const markers = [];
	window.L = {
		map: vi.fn( () => map ),
		tileLayer: vi.fn( () => layer ),
		marker: vi.fn( ( latLng, options ) => {
			const marker = { latLng, options, bindPopup: vi.fn(), getLatLng: () => latLng };
			marker.addTo = () => marker;
			markers.push( marker );
			return marker;
		} ),
		featureGroup: vi.fn( ( group ) => ( { getBounds: () => 'bounds of ' + group.length } ) ),
	};
	return { map, layer, markers };
}

function mount( data ) {
	document.body.innerHTML = '<div class="evt-ev__mapa"></div>';
	document.querySelector( 'div' ).setAttribute( 'data-evt-mapa', 'string' === typeof data ? data : JSON.stringify( data ) );
}

const TILES = { url: 'https://tiles.example/{z}/{x}/{y}.png', attribution: '© Colaboradores' };

afterEach( () => {
	delete window.L;
} );

describe( 'contact map', () => {
	it( 'centres a single point and labels it with its text and link', async () => {
		const { map, layer, markers } = fakeLeaflet();
		mount( { tiles: TILES, points: [ { lat: 28.46, lng: -16.25, text: 'Sede', url: 'https://example.org/sede' } ] } );
		await load();

		const box = document.querySelector( '[data-evt-mapa]' );
		expect( box.classList.contains( 'evt-ev__mapa--listo' ) ).toBe( true );
		expect( window.L.map ).toHaveBeenCalledWith( box, { scrollWheelZoom: false } );
		expect( window.L.tileLayer ).toHaveBeenCalledWith( TILES.url, { maxZoom: 19, attribution: TILES.attribution } );
		expect( layer.addTo ).toHaveBeenCalledWith( map );
		expect( markers[ 0 ].options ).toEqual( { title: 'Sede' } );
		expect( map.setView ).toHaveBeenCalledWith( [ 28.46, -16.25 ], 16 );

		const popup = markers[ 0 ].bindPopup.mock.calls[ 0 ][ 0 ];
		expect( popup.querySelector( 'strong' ).textContent ).toBe( 'Sede' );
		expect( popup.querySelector( 'br' ) ).not.toBeNull();
		expect( popup.querySelector( 'a' ).href ).toBe( 'https://example.org/sede' );
		expect( popup.querySelector( 'a' ).textContent ).toBe( 'Más información' );
	} );

	it( 'fits several points in view, with a popup only where there is something to say', async () => {
		const { map, markers } = fakeLeaflet();
		mount( { tiles: TILES, points: [ { lat: 1, lng: 2 }, { lat: 3, lng: 4, url: 'https://example.org' } ] } );
		await load();

		expect( map.fitBounds ).toHaveBeenCalledWith( 'bounds of 2', { padding: [ 30, 30 ] } );
		expect( markers[ 0 ].bindPopup ).not.toHaveBeenCalled();
		const popup = markers[ 1 ].bindPopup.mock.calls[ 0 ][ 0 ];
		expect( popup.querySelector( 'strong' ) ).toBeNull();
		expect( popup.querySelector( 'br' ) ).toBeNull();
	} );

	it( 'writes the organiser text as text, never as markup', async () => {
		const { markers } = fakeLeaflet();
		mount( { tiles: TILES, points: [ { lat: 1, lng: 2, text: '<img src=x onerror=alert(1)>' } ] } );
		await load();

		const popup = markers[ 0 ].bindPopup.mock.calls[ 0 ][ 0 ];
		expect( popup.querySelector( 'img' ) ).toBeNull();
		expect( popup.textContent ).toBe( '<img src=x onerror=alert(1)>' );
	} );

	it.each( [
		[ 'broken data', '{roto' ],
		[ 'no points', { tiles: TILES, points: [] } ],
		[ 'no tiles', { points: [ { lat: 1, lng: 2 } ] } ],
	] )( 'leaves the list of points alone with %s', async ( _label, data ) => {
		fakeLeaflet();
		mount( data );
		await load();
		expect( window.L.map ).not.toHaveBeenCalled();
		expect( document.querySelector( '.evt-ev__mapa--listo' ) ).toBeNull();
	} );

	it( 'does nothing when Leaflet did not load', async () => {
		mount( { tiles: TILES, points: [ { lat: 1, lng: 2 } ] } );
		await load();
		expect( document.querySelector( '.evt-ev__mapa--listo' ) ).toBeNull();
	} );
} );
