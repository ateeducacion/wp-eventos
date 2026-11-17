/*
 * evt-qr.js — los códigos QR de los puntos de asistencia (ADR-0053).
 *
 * Cada `[data-evt-qr]` lleva la dirección que tiene que abrir el móvil. Con la
 * librería `qrcode-generator` se pinta el código dentro, en SVG, para que se
 * imprima o se proyecte nítido a cualquier tamaño. Si la librería no llega, no
 * se pinta nada: debajo está la dirección escrita, que se puede copiar o
 * pegar en cualquier generador de QR.
 *
 * Cada punto tiene además un botón `[data-evt-qr-descargar]` que baja un
 * cartel en PNG —el evento, el punto, el código y los tres pasos— listo para
 * imprimir o proyectar, y enseña después qué hacer con él. El botón nace
 * oculto y solo aparece si hay con qué pintar el cartel.
 */
( function () {
	'use strict';

	var ANCHO = 1240; // Un A4 a 150 ppp: se imprime nítido y pesa poco.
	var MARGEN = 90;

	function codigo( url ) {
		// Tipo 0: el más pequeño en el que quepa la dirección. Corrección
		// M: aguanta un proyector con reflejos sin hacer el código enorme.
		var qr = window.qrcode( 0, 'M' );
		qr.addData( url );
		qr.make();
		return qr;
	}

	// Parte un texto en las líneas que caben en `ancho`.
	function lineas( ctx, texto, ancho ) {
		var out = [];
		var linea = '';
		String( texto ).split( /\s+/ ).forEach( function ( palabra ) {
			var prueba = linea ? linea + ' ' + palabra : palabra;
			if ( linea && ctx.measureText( prueba ).width > ancho ) {
				out.push( linea );
				linea = palabra;
			} else {
				linea = prueba;
			}
		} );
		if ( linea ) {
			out.push( linea );
		}
		return out;
	}

	// El cartel: título del evento, nombre del punto, el código y los pasos.
	function cartel( url, evento, titulo, pasos ) {
		var lienzo = document.createElement( 'canvas' );
		var ctx = lienzo.getContext ? lienzo.getContext( '2d' ) : null;
		if ( ! ctx ) {
			return null;
		}
		var util = ANCHO - 2 * MARGEN;
		var fuente = 'system-ui, -apple-system, "Segoe UI", Arial, sans-serif';
		var bloques = [];
		var y = MARGEN;

		function texto( cadena, px, negrita, interlinea ) {
			if ( ! cadena ) {
				return;
			}
			ctx.font = ( negrita ? 'bold ' : '' ) + px + 'px ' + fuente;
			lineas( ctx, cadena, util ).forEach( function ( l ) {
				y += px * interlinea;
				bloques.push( { font: ctx.font, texto: l, y: y } );
			} );
		}

		texto( evento, 44, false, 1.3 );
		y += 20;
		texto( titulo, 68, true, 1.2 );
		y += 50;
		var qr = codigo( url );
		var n = qr.getModuleCount();
		var celda = Math.floor( ( util - 160 ) / ( n + 8 ) ); // 4 módulos de margen por lado.
		var lado = celda * ( n + 8 );
		// En píxeles enteros: con decimales cada módulo sale con el borde gris.
		var qrY = Math.round( y );
		y = qrY + lado + 50;
		pasos.forEach( function ( paso ) {
			texto( paso, 40, false, 1.5 );
		} );

		lienzo.width = ANCHO;
		lienzo.height = y + MARGEN;
		ctx.fillStyle = '#fff';
		ctx.fillRect( 0, 0, lienzo.width, lienzo.height );
		ctx.fillStyle = '#000';
		ctx.textAlign = 'center';
		bloques.forEach( function ( b ) {
			ctx.font = b.font;
			// El ancho máximo encoge una palabra que sola no quepa, en vez de cortarla.
			ctx.fillText( b.texto, ANCHO / 2, b.y, util );
		} );
		var qrX = Math.round( ( ANCHO - lado ) / 2 );
		for ( var f = 0; f < n; f++ ) {
			for ( var c = 0; c < n; c++ ) {
				if ( qr.isDark( f, c ) ) {
					ctx.fillRect( qrX + ( c + 4 ) * celda, qrY + ( f + 4 ) * celda, celda, celda );
				}
			}
		}
		return lienzo;
	}

	// «Entrada principal» → «asistencia-entrada-principal.png».
	function nombre( punto ) {
		var limpio = String( punto )
			.normalize( 'NFD' )
			.replace( /[̀-ͯ]/g, '' )
			.toLowerCase()
			.replace( /[^a-z0-9]+/g, '-' )
			.replace( /^-+|-+$/g, '' );
		return 'asistencia-' + ( limpio || 'qr' ) + '.png';
	}

	function descargar( boton ) {
		var caja = boton.closest( '.evt-punto' );
		var qr = caja ? caja.querySelector( '[data-evt-qr]' ) : null;
		if ( ! qr ) {
			return;
		}
		var punto = qr.getAttribute( 'data-evt-qr-label' ) || '';
		// Los textos vienen del servidor: el cartel dice lo mismo que la página.
		var lienzo = cartel(
			qr.getAttribute( 'data-evt-qr' ),
			qr.getAttribute( 'data-evt-qr-event' ) || '',
			qr.getAttribute( 'data-evt-qr-heading' ) || punto,
			( qr.getAttribute( 'data-evt-qr-steps' ) || '' ).split( '|' ).filter( Boolean )
		);
		if ( ! lienzo ) {
			return;
		}
		var enlace = document.createElement( 'a' );
		enlace.href = lienzo.toDataURL( 'image/png' );
		enlace.download = nombre( punto );
		document.body.appendChild( enlace );
		enlace.click();
		enlace.remove();

		// Lo que viene después de tenerlo: dónde ponerlo y qué no olvidar.
		var despues = caja.querySelector( '[data-evt-qr-despues]' );
		if ( despues ) {
			despues.hidden = false;
		}
	}

	function pintar() {
		if ( typeof window.qrcode !== 'function' ) {
			return;
		}
		document.querySelectorAll( '[data-evt-qr]' ).forEach( function ( caja ) {
			var url = caja.getAttribute( 'data-evt-qr' );
			if ( ! url ) {
				return;
			}
			caja.innerHTML = codigo( url ).createSvgTag( {
				cellSize: 8,
				margin: 4,
				scalable: true,
				alt: caja.getAttribute( 'data-evt-qr-alt' ) || 'Código QR',
			} );
			caja.classList.add( 'evt-qr--listo' );
		} );

		// Sin lienzo no hay cartel que bajar, y el botón se queda oculto.
		var prueba = document.createElement( 'canvas' );
		if ( ! prueba.getContext || ! prueba.getContext( '2d' ) ) {
			return;
		}
		document.querySelectorAll( '[data-evt-qr-descargar]' ).forEach( function ( boton ) {
			boton.hidden = false;
			boton.addEventListener( 'click', function () {
				descargar( boton );
			} );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', pintar );
	} else {
		pintar();
	}
}() );
