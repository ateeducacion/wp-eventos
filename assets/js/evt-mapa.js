/*
 * evt-mapa.js — el mapa de la página de contacto de un evento (ADR-0046).
 *
 * Lee los puntos de `data-evt-mapa`, que escribe el servidor ya limpios, y los
 * pinta con Leaflet sobre las teselas que diga el mismo dato. Si Leaflet no
 * llega, no hace nada: debajo del mapa está la lista de puntos con su enlace,
 * que se lee igual.
 */
( function () {
	'use strict';

	if ( ! window.L ) {
		return;
	}

	document.querySelectorAll( '[data-evt-mapa]' ).forEach( function ( caja ) {
		var datos;
		try {
			datos = JSON.parse( caja.getAttribute( 'data-evt-mapa' ) );
		} catch ( e ) {
			return;
		}
		if ( ! datos || ! datos.points || ! datos.points.length || ! datos.tiles || ! datos.tiles.url ) {
			return;
		}

		// La altura, antes de crear el mapa: Leaflet mide la caja al centrarlo.
		caja.classList.add( 'evt-ev__mapa--listo' );
		var mapa = window.L.map( caja, { scrollWheelZoom: false } );
		window.L.tileLayer( datos.tiles.url, {
			maxZoom: 19,
			attribution: datos.tiles.attribution
		} ).addTo( mapa );

		var marcas = datos.points.map( function ( punto ) {
			var marca = window.L.marker( [ punto.lat, punto.lng ], { title: punto.text || '' } ).addTo( mapa );
			if ( punto.text || punto.url ) {
				// Se arma con nodos y no con HTML: el texto lo escribe quien organiza.
				var globo = document.createElement( 'div' );
				if ( punto.text ) {
					var titulo = document.createElement( 'strong' );
					titulo.textContent = punto.text;
					globo.appendChild( titulo );
				}
				if ( punto.url ) {
					var enlace = document.createElement( 'a' );
					enlace.href = punto.url;
					enlace.textContent = 'Más información';
					if ( punto.text ) {
						globo.appendChild( document.createElement( 'br' ) );
					}
					globo.appendChild( enlace );
				}
				marca.bindPopup( globo );
			}
			return marca;
		} );

		if ( 1 === marcas.length ) {
			mapa.setView( marcas[ 0 ].getLatLng(), 16 );
		} else {
			mapa.fitBounds( window.L.featureGroup( marcas ).getBounds(), { padding: [ 30, 30 ] } );
		}
	} );
}() );
