/*
 * La línea del tiempo pública (`[evt_timeline]`, ADR-0041).
 *
 * Todo es mejora sobre algo que ya funciona sin guion: la tira se desplaza
 * con la barra, la rueda o el dedo. Aquí se añade que abra en el mes actual,
 * los botones ‹ Hoy › y arrastrar con el ratón.
 */
( function () {
	'use strict';

	var UMBRAL = 5;

	function iniciar( linea ) {
		var pista = linea.querySelector( '.evt-linea__pista' );
		var meses = linea.querySelectorAll( '.evt-linea__mes' );
		if ( ! pista || ! meses.length ) {
			return;
		}
		linea.classList.add( 'evt-linea--viva' );

		var actual = parseInt( linea.getAttribute( 'data-actual' ), 10 ) || 0;
		var suave = window.matchMedia && ! window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

		function ancho() {
			return meses[ 0 ].getBoundingClientRect().width || 1;
		}

		// El mes anterior a la izquierda: se abre viendo de dónde se viene.
		function irAHoy( animado ) {
			var destino = Math.max( 0, actual - ( ancho() * 3 <= pista.clientWidth ? 1 : 0 ) );
			pista.scrollTo( { left: destino * ancho(), behavior: animado && suave ? 'smooth' : 'auto' } );
		}

		linea.addEventListener( 'click', function ( evento ) {
			var boton = evento.target.closest( '[data-evt-linea]' );
			if ( ! boton ) {
				return;
			}
			var que = boton.getAttribute( 'data-evt-linea' );
			if ( 'hoy' === que ) {
				irAHoy( true );
				return;
			}
			pista.scrollBy( { left: ( 'atras' === que ? -1 : 1 ) * ancho(), behavior: suave ? 'smooth' : 'auto' } );
		} );

		/*
		 * Arrastrar con el ratón. Con el dedo ya se desplaza solo, así que
		 * solo se atiende al puntero de ratón. Un clic que no llega a mover
		 * nada sigue siendo un clic y abre el evento.
		 */
		var inicioX = 0;
		var inicioScroll = 0;
		var agarrado = false;
		var movido = false;

		pista.addEventListener( 'pointerdown', function ( evento ) {
			if ( 'mouse' !== evento.pointerType || 0 !== evento.button ) {
				return;
			}
			agarrado = true;
			movido = false;
			inicioX = evento.clientX;
			inicioScroll = pista.scrollLeft;
		} );

		pista.addEventListener( 'pointermove', function ( evento ) {
			if ( ! agarrado ) {
				return;
			}
			var dx = evento.clientX - inicioX;
			if ( ! movido && Math.abs( dx ) < UMBRAL ) {
				return;
			}
			if ( ! movido ) {
				movido = true;
				linea.classList.add( 'evt-linea--arrastrando' );
				pista.setPointerCapture( evento.pointerId );
			}
			pista.scrollLeft = inicioScroll - dx;
		} );

		function soltar() {
			agarrado = false;
			linea.classList.remove( 'evt-linea--arrastrando' );
		}
		pista.addEventListener( 'pointerup', soltar );
		pista.addEventListener( 'pointercancel', soltar );

		// Tras arrastrar, el clic que cierra el gesto no abre nada.
		pista.addEventListener( 'click', function ( evento ) {
			if ( movido ) {
				evento.preventDefault();
				evento.stopPropagation();
				movido = false;
			}
		}, true );

		irAHoy( false );
	}

	function arrancar() {
		var lineas = document.querySelectorAll( '.evt-linea' );
		for ( var i = 0; i < lineas.length; i++ ) {
			iniciar( lineas[ i ] );
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', arrancar );
	} else {
		arrancar();
	}
}() );
