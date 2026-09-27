/*
 * evt-evento.js — las pestañas del programa en la página pública de un evento.
 *
 * Sin guion, los días salen uno debajo de otro y se leen igual. Con él, cada
 * panel `.evt-ev__dia-panel` es una pestaña con el rótulo de su `<h2>`, y las
 * flechas, Inicio y Fin se mueven entre ellas como manda el patrón de pestañas
 * de WAI-ARIA.
 */
( function () {
	document.querySelectorAll( '[data-evt-pestanas]' ).forEach( function ( caja, n ) {
		var paneles = Array.prototype.slice.call( caja.querySelectorAll( ':scope > .evt-ev__dia-panel' ) );
		if ( paneles.length < 2 ) {
			return;
		}
		var lista = document.createElement( 'div' );
		lista.className = 'evt-ev__pestanas';
		lista.setAttribute( 'role', 'tablist' );
		lista.setAttribute( 'aria-label', 'Días del programa' );

		var botones = paneles.map( function ( panel, i ) {
			var rotulo = panel.querySelector( '.evt-ev__dia' );
			var boton = document.createElement( 'button' );
			boton.type = 'button';
			boton.id = 'evt-pestana-' + n + '-' + i;
			boton.textContent = rotulo ? rotulo.textContent : String( i + 1 );
			boton.setAttribute( 'role', 'tab' );
			boton.setAttribute( 'aria-controls', panel.id = panel.id || 'evt-dia-' + n + '-' + i );
			panel.setAttribute( 'role', 'tabpanel' );
			panel.setAttribute( 'aria-labelledby', boton.id );
			panel.tabIndex = 0;
			lista.appendChild( boton );
			return boton;
		} );

		function elige( i, foco ) {
			botones.forEach( function ( boton, j ) {
				boton.setAttribute( 'aria-selected', i === j ? 'true' : 'false' );
				boton.tabIndex = i === j ? 0 : -1;
				paneles[ j ].hidden = i !== j;
			} );
			if ( foco ) {
				botones[ i ].focus();
			}
		}

		lista.addEventListener( 'click', function ( e ) {
			var i = botones.indexOf( e.target.closest( '[role="tab"]' ) );
			if ( i >= 0 ) {
				elige( i, false );
			}
		} );
		lista.addEventListener( 'keydown', function ( e ) {
			var i = botones.indexOf( document.activeElement );
			var ultimo = botones.length - 1;
			var destino = { ArrowRight: i + 1, ArrowLeft: i - 1, Home: 0, End: ultimo }[ e.key ];
			if ( i < 0 || undefined === destino ) {
				return;
			}
			e.preventDefault();
			elige( ( destino + botones.length ) % botones.length, true );
		} );

		caja.classList.add( 'evt-ev__programa--pestanas' );
		caja.insertBefore( lista, caja.firstChild );
		elige( 0, false );
	} );
}() );
