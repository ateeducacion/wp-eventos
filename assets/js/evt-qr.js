/*
 * evt-qr.js — los códigos QR de los puntos de asistencia (ADR-0053).
 *
 * Cada `[data-evt-qr]` lleva la dirección que tiene que abrir el móvil. Con la
 * librería `qrcode-generator` se pinta el código dentro, en SVG, para que se
 * imprima o se proyecte nítido a cualquier tamaño. Si la librería no llega, no
 * se pinta nada: debajo está la dirección escrita, que se puede copiar o
 * pegar en cualquier generador de QR.
 */
( function () {
	'use strict';

	function pintar() {
		if ( typeof window.qrcode !== 'function' ) {
			return;
		}
		document.querySelectorAll( '[data-evt-qr]' ).forEach( function ( caja ) {
			var url = caja.getAttribute( 'data-evt-qr' );
			if ( ! url ) {
				return;
			}
			// Tipo 0: el más pequeño en el que quepa la dirección. Corrección
			// M: aguanta un proyector con reflejos sin hacer el código enorme.
			var qr = window.qrcode( 0, 'M' );
			qr.addData( url );
			qr.make();
			caja.innerHTML = qr.createSvgTag( {
				cellSize: 8,
				margin: 4,
				scalable: true,
				alt: caja.getAttribute( 'data-evt-qr-alt' ) || 'Código QR',
			} );
			caja.classList.add( 'evt-qr--listo' );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', pintar );
	} else {
		pintar();
	}
}() );
