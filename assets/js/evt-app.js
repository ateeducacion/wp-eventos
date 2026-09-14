/*
 * Lo mínimo que las pantallas del aplicativo no pueden hacer sin guion.
 *
 * Cuatro cosas, y las cuatro son mejora sobre algo que ya funciona sin ellas:
 * si el guion no llega, el formulario se envía igual, el slug se escribe a
 * mano, la apariencia se ve al guardar y las imágenes se suben con el campo de
 * fichero del `<noscript>`. Nada aquí valida ni autoriza: eso está en el
 * servidor, con su nonce y su comprobación de EventAccess.
 *
 * Delegado en `document`: las pantallas repintan trozos y un `addEventListener`
 * por nodo se quedaría atrás.
 */
( function () {
	'use strict';

	/* --- 1. Confirmar antes de borrar ------------------------------------ */

	/*
	 * `data-evt-confirm="¿Seguro que…?"` en el formulario que borra. Se
	 * pregunta al enviar, que es el único momento en que se pierde algo.
	 *
	 * Tres escalones, y los tres hacen la acción:
	 *   1. Con SweetAlert2 (`snippets/sweetalert.php`), un diálogo en
	 *      castellano cuyo botón dice el verbo —«Enviar a la papelera»— y no
	 *      «OK». Atrapa el foco y se cierra con Escape; lo hace la librería.
	 *   2. Sin ella —el CDN no contesta, el SRI no cuadra—, el `confirm()` del
	 *      navegador de siempre.
	 *   3. Sin guion, el botón envía el formulario y la acción se hace.
	 * Nunca se pierde una acción porque una librería no llegara.
	 *
	 * El verbo sale de `data-evt-confirm-ok` si el formulario lo pone y, si no,
	 * del `title` del botón, que ya es la acción escrita («Enviar a la
	 * papelera») porque es lo que lee quien navega con lector de pantalla.
	 */
	function verbo( form, boton ) {
		return form.getAttribute( 'data-evt-confirm-ok' ) ||
			( boton && boton.getAttribute( 'title' ) ) ||
			'Sí, continuar';
	}

	document.addEventListener( 'submit', function ( e ) {
		var form = e.target;
		// La marca la pone el envío que sale del diálogo: se deja pasar.
		if ( ! ( form instanceof HTMLFormElement ) || '1' === form.dataset.evtConfirmado ) {
			return;
		}
		var pregunta = form.getAttribute( 'data-evt-confirm' );
		if ( ! pregunta ) {
			return;
		}

		if ( ! window.Swal ) {
			if ( ! window.confirm( pregunta ) ) {
				e.preventDefault();
			}
			return;
		}

		// SweetAlert2 contesta con una promesa, así que el envío se para y se
		// repite luego; el `confirm()` de arriba, no, y por eso va aparte.
		e.preventDefault();
		var boton = e.submitter || form.querySelector( '[type="submit"]' );
		// «¿Enviar «X» a la papelera? Dejará de verse.» → titular y detalle.
		var corte = pregunta.indexOf( '? ' );
		window.Swal.fire( {
			title: -1 === corte ? pregunta : pregunta.slice( 0, corte + 1 ),
			text: -1 === corte ? '' : pregunta.slice( corte + 2 ),
			icon: 'warning',
			showCancelButton: true,
			confirmButtonText: verbo( form, boton ),
			cancelButtonText: 'Cancelar',
			// Lo que no tiene vuelta atrás no se confirma sin querer.
			focusCancel: true,
			reverseButtons: true,
			// Sin tocar el alto del `html`: la barra de pestañas es pegajosa.
			heightAuto: false,
			// Los botones son los de la página, no los de la librería.
			buttonsStyling: false,
			customClass: {
				confirmButton: 'evt-btn evt-btn-borrar btn',
				cancelButton: 'evt-btn btn btn-light'
			}
		} ).then( function ( respuesta ) {
			if ( ! respuesta.isConfirmed ) {
				return;
			}
			form.dataset.evtConfirmado = '1';
			if ( form.requestSubmit ) {
				// Con el mismo botón: si algún día lleva `name`, sigue viajando.
				form.requestSubmit( boton || undefined );
			} else {
				form.submit();
			}
		} );
	} );

	/* --- 2. Proponer el slug desde el título ----------------------------- */

	/*
	 * `data-evt-slug-source` en el campo del título y `data-evt-slug-target`
	 * en el del slug, dentro del mismo formulario. Se propone y no se impone:
	 * en cuanto alguien escribe en el slug, se deja de tocar, y una sección
	 * que ya tiene slug —la que se está editando— no se reescribe nunca.
	 */
	function slugify( texto ) {
		return texto
			.normalize( 'NFD' )
			.replace( /[̀-ͯ]/g, '' )
			.toLowerCase()
			.replace( /[^a-z0-9]+/g, '-' )
			.replace( /^-+|-+$/g, '' )
			.slice( 0, 80 );
	}

	document.addEventListener( 'input', function ( e ) {
		var origen = e.target;
		if ( ! origen || ! origen.hasAttribute || ! origen.hasAttribute( 'data-evt-slug-source' ) ) {
			return;
		}
		var form = origen.form;
		var destino = form && form.querySelector( '[data-evt-slug-target]' );
		if ( ! destino || destino.dataset.evtTocado === '1' ) {
			return;
		}
		destino.value = slugify( origen.value );
	} );

	document.addEventListener( 'input', function ( e ) {
		if ( e.target && e.target.hasAttribute && e.target.hasAttribute( 'data-evt-slug-target' ) ) {
			e.target.dataset.evtTocado = '1';
		}
	} );

	/* --- 3. La vista previa de la cabecera ------------------------------- */

	/*
	 * El panel de apariencia lleva un `[data-evt-preview]` dentro del mismo
	 * formulario que los controles. No hace falta marcar cada control: se
	 * leen por su `name`, que es la clave de meta (`evt_header_bg`…).
	 *
	 * Dentro de la vista previa:
	 *   [data-evt-preview-header]  el bloque que toma los dos colores
	 *   [data-evt-preview-title]   el título, que toma la tipografía de títulos
	 *   [data-evt-preview-body]    el cuerpo, que toma la del texto
	 *   [data-evt-preview-tagline] el lema
	 *   [data-evt-preview-shape]   la muestra que toma la forma de imagen
	 *   [data-evt-preview-sep]     con un hijo `[data-sep="<slug>"]` por silueta
	 */
	var CAMPOS = [
		'evt_header_bg',
		'evt_header_text',
		'evt_title_font',
		'evt_body_font',
		'evt_image_shape',
		'evt_separator',
		'evt_tagline'
	];

	function valor( form, nombre ) {
		var campo = form.elements[ nombre ];
		return campo && typeof campo.value === 'string' ? campo.value : '';
	}

	function familia( nodo, slug ) {
		if ( ! nodo ) {
			return;
		}
		nodo.className = nodo.className.replace( /\bevt-font-\S+/g, '' ).trim();
		if ( slug ) {
			nodo.classList.add( 'evt-font-' + slug );
		}
	}

	function pintar( form ) {
		var vista = form.querySelector( '[data-evt-preview]' );
		if ( ! vista ) {
			return;
		}

		var cabecera = vista.querySelector( '[data-evt-preview-header]' );
		if ( cabecera ) {
			cabecera.style.backgroundColor = valor( form, 'evt_header_bg' );
			cabecera.style.color = valor( form, 'evt_header_text' );
		}

		familia( vista.querySelector( '[data-evt-preview-title]' ), valor( form, 'evt_title_font' ) );
		familia( vista.querySelector( '[data-evt-preview-body]' ), valor( form, 'evt_body_font' ) );

		var lema = vista.querySelector( '[data-evt-preview-tagline]' );
		if ( lema && form.elements.evt_tagline ) {
			lema.textContent = valor( form, 'evt_tagline' );
		}

		var muestra = vista.querySelector( '[data-evt-preview-shape]' );
		if ( muestra ) {
			muestra.classList.remove( 'evt-shape-square', 'evt-shape-circle' );
			muestra.classList.add( 'evt-shape-' + ( 'circle' === valor( form, 'evt_image_shape' ) ? 'circle' : 'square' ) );
		}

		var sep = vista.querySelector( '[data-evt-preview-sep]' );
		if ( sep ) {
			var elegido = valor( form, 'evt_separator' );
			Array.prototype.forEach.call( sep.querySelectorAll( '[data-sep]' ), function ( silueta ) {
				silueta.hidden = silueta.getAttribute( 'data-sep' ) !== elegido;
			} );
		}
	}

	/*
	 * El `<input type="color">` y su campo hexadecimal escriben el mismo dato:
	 * `data-evt-color-for="<id del campo de texto>"` los empareja. El que se
	 * envía es el de texto, así que quien tenga el selector de color bloqueado
	 * sigue pudiendo teclear el hexadecimal.
	 */
	document.addEventListener( 'input', function ( e ) {
		var control = e.target;
		if ( ! control || ! control.hasAttribute ) {
			return;
		}

		var pareja = control.getAttribute( 'data-evt-color-for' );
		if ( pareja ) {
			var texto = document.getElementById( pareja );
			if ( texto ) {
				texto.value = control.value;
			}
		} else if ( 'evt_header_bg' === control.name || 'evt_header_text' === control.name ) {
			// Al revés: el hexadecimal escrito a mano mueve la muestra de color.
			var muestra = document.querySelector( '[data-evt-color-for="' + control.id + '"]' );
			if ( muestra && /^#[0-9a-fA-F]{6}$/.test( control.value ) ) {
				muestra.value = control.value;
			}
		}

		if ( control.form && ( pareja || CAMPOS.indexOf( control.name ) !== -1 ) ) {
			pintar( control.form );
		}
	} );

	// Y una primera pasada, para que la vista previa arranque con lo guardado.
	document.addEventListener( 'DOMContentLoaded', function () {
		Array.prototype.forEach.call( document.querySelectorAll( '[data-evt-preview]' ), function ( vista ) {
			if ( vista.form || vista.closest( 'form' ) ) {
				pintar( vista.closest( 'form' ) );
			}
		} );
	} );

	/* --- 4. Elegir una imagen de la biblioteca --------------------------- */

	/*
	 * Cada imagen del panel de apariencia es un `[data-evt-media]` con:
	 *
	 *   [data-evt-media-value]   el `hidden` con el ID del adjunto: lo único que viaja
	 *   [data-evt-media-card]    la ficha de lo que hay puesto ahora
	 *   [data-evt-media-thumb]   su miniatura
	 *   [data-evt-media-name]    su nombre de fichero
	 *   [data-evt-media-size]    sus dimensiones
	 *   [data-evt-media-empty]   la línea de «todavía no hay nada»
	 *   [data-evt-media-actions] la fila de botones, oculta hasta que este guion llega
	 *   [data-evt-media-pick]    «Elegir imagen»
	 *   [data-evt-media-clear]   «Quitar»
	 *
	 * Sin guion no se ve ningún botón —uno que no abre nada solo estorba— y el
	 * respaldo del `<noscript>` sigue siendo la forma de subir o de quitar. Y
	 * el ID que se escribe aquí no autoriza nada: el servidor comprueba que
	 * sea un adjunto de imagen y que quien lo manda edite el evento.
	 */
	function poner( caja, adjunto ) {
		var oculto = caja.querySelector( '[data-evt-media-value]' );
		var ficha = caja.querySelector( '[data-evt-media-card]' );
		var vacia = caja.querySelector( '[data-evt-media-empty]' );
		var quitar = caja.querySelector( '[data-evt-media-clear]' );
		var mini = caja.querySelector( '[data-evt-media-thumb]' );
		var nombre = caja.querySelector( '[data-evt-media-name]' );
		var medidas = caja.querySelector( '[data-evt-media-size]' );
		var hay = !! ( adjunto && adjunto.id );

		if ( oculto ) {
			oculto.value = hay ? String( adjunto.id ) : '0';
		}
		if ( ficha ) {
			ficha.hidden = ! hay;
		}
		if ( vacia ) {
			vacia.hidden = hay;
		}
		if ( quitar ) {
			quitar.hidden = ! hay;
		}
		if ( ! hay ) {
			return;
		}

		// La miniatura de la biblioteca si la hay, y si no el fichero entero.
		var chica = adjunto.sizes && ( adjunto.sizes.medium || adjunto.sizes.thumbnail );
		if ( mini ) {
			mini.src = chica ? chica.url : adjunto.url;
			mini.alt = adjunto.alt || '';
		}
		if ( nombre ) {
			nombre.textContent = adjunto.filename || adjunto.title || '';
		}
		if ( medidas ) {
			medidas.textContent = adjunto.width && adjunto.height
				? adjunto.width + ' × ' + adjunto.height + ' px'
				: '';
		}
	}

	function elegir( caja ) {
		if ( ! window.wp || ! window.wp.media ) {
			return;
		}
		var marco = window.wp.media( {
			title: caja.getAttribute( 'data-evt-media-title' ) || 'Elegir imagen',
			button: { text: 'Usar esta imagen' },
			library: { type: 'image' },
			multiple: false
		} );
		marco.on( 'select', function () {
			var elegido = marco.state().get( 'selection' ).first();
			if ( elegido ) {
				poner( caja, elegido.toJSON() );
			}
		} );
		marco.open();
	}

	document.addEventListener( 'click', function ( e ) {
		var boton = e.target && e.target.closest
			? e.target.closest( '[data-evt-media-pick], [data-evt-media-clear]' )
			: null;
		var caja = boton && boton.closest( '[data-evt-media]' );
		if ( ! caja ) {
			return;
		}
		e.preventDefault();
		if ( boton.hasAttribute( 'data-evt-media-clear' ) ) {
			poner( caja, null );
		} else {
			elegir( caja );
		}
	} );

	function mostrarBotones() {
		Array.prototype.forEach.call( document.querySelectorAll( '[data-evt-media-actions]' ), function ( fila ) {
			fila.hidden = false;
		} );
	}

	// El guion va en el pie, así que el panel ya está leído; el segundo aviso
	// es por si algún día sube a la cabecera.
	mostrarBotones();
	document.addEventListener( 'DOMContentLoaded', mostrarBotones );

	/* --- 5. El editor de código ------------------------------------------ */

	/*
	 * `CodeEditor::field()` pinta cada `<textarea data-evt-code="css|javascript">`
	 * con sus ajustes en `data-evt-code-settings`. Un atributo por campo y no
	 * `wp_localize_script`: en la misma pantalla hay dos editores —CSS y
	 * JavaScript—, con ajustes distintos, y `wp_localize_script` escribe UNA
	 * variable por identificador de guion, así que la segunda llamada pisa a
	 * la primera. Con el atributo, cada campo se describe a sí mismo y aquí no
	 * hay que emparejar nada.
	 *
	 * Si `wp.codeEditor` no está —porque quien mira desactivó el resaltado en
	 * su perfil, y entonces el atributo tampoco está— el textarea se queda como
	 * está y se sigue escribiendo y guardando igual.
	 *
	 * Se intenta varias veces (al leer el documento y al terminar de cargar)
	 * porque `code-editor` se encola al pintar el contenido, después que este
	 * guion, y en el pie se imprime detrás. La marca `data-evt-code-on` hace
	 * que las pasadas de más no cuesten nada.
	 *
	 * La pasada inmediata solo corre si el documento ya está leído: con él
	 * todavía cargando, `wp.codeEditor.initialize()` avisa por consola de que
	 * «ran too early», y no aporta nada porque `DOMContentLoaded` llega
	 * enseguida. Si el guion se carga tarde (`defer`, o inyectado), esa pasada
	 * inmediata sigue siendo la que arranca los editores.
	 */
	function arrancarEditores() {
		if ( ! window.wp || ! window.wp.codeEditor ) {
			return;
		}
		Array.prototype.forEach.call( document.querySelectorAll( '[data-evt-code]' ), function ( area ) {
			if ( '1' === area.dataset.evtCodeOn ) {
				return;
			}
			var ajustes;
			try {
				ajustes = JSON.parse( area.getAttribute( 'data-evt-code-settings' ) || '{}' );
			} catch ( e ) {
				return;
			}
			area.dataset.evtCodeOn = '1';
			// `CodeMirror.fromTextArea` engancha el `submit` del formulario y
			// devuelve lo escrito al textarea, así que lo que viaja en el POST
			// es siempre lo que se ve.
			window.wp.codeEditor.initialize( area, ajustes );
		} );
	}

	if ( 'loading' !== document.readyState ) {
		arrancarEditores();
	}
	document.addEventListener( 'DOMContentLoaded', arrancarEditores );
	window.addEventListener( 'load', arrancarEditores );

	/* --- 6. El bloqueo de edición, con el Heartbeat de WordPress --------- */

	/*
	 * `EditLock::render()` pinta un `#evt-edit-lock` con el evento, el bloqueo
	 * que se tiene ahora (`data-lock`) y el nonce para soltarlo. Todo lo de
	 * aquí es el mecanismo NATIVO del escritorio, sin inventar nada:
	 *
	 *   - `wp-refresh-post-lock` viaja en cada latido del Heartbeat: renueva el
	 *     bloqueo propio y, si otra persona tomó posesión, contesta con su
	 *     nombre. Es la misma llamada que hace el editor de WordPress, así que
	 *     los dos sitios se enteran el uno del otro.
	 *   - `wp-remove-post-lock` suelta el bloqueo al cerrar la pestaña.
	 *
	 * Sin guion no se pierde nada: el aviso ya viene pintado como
	 * `<dialog open>` desde el servidor cuando el evento está cogido, y el
	 * servidor vuelve a comprobarlo con un 409 antes de escribir. Esto solo
	 * evita el susto de estar media hora escribiendo algo que ya no se puede
	 * guardar.
	 *
	 * En `DOMContentLoaded` porque el guion del aplicativo se imprime en el pie
	 * sin depender de jQuery ni del Heartbeat, y a esas alturas los dos ya
	 * están cargados.
	 */
	document.addEventListener( 'DOMContentLoaded', function () {
		var caja = document.getElementById( 'evt-edit-lock' );
		// Sin `data-lock` el bloqueo lo tiene otra persona: no hay nada que
		// renovar, y pedirlo sería pedir la renovación de un bloqueo ajeno.
		if ( ! caja || ! caja.dataset.lock || ! window.jQuery || ! window.wp || ! window.wp.heartbeat ) {
			return;
		}

		var $ = window.jQuery;
		var dialogo = document.getElementById( 'evt-lock-dialog' );
		var cerradura = caja.dataset.lock;
		var perdido = false;
		var enviando = false;

		// Todo lo de la pantalla menos el propio aviso, que es lo único que
		// tiene que seguir funcionando cuando el bloqueo se pierde.
		function fuera( nodo ) {
			return ! caja.contains( nodo );
		}

		$( document ).on( 'heartbeat-send.evtLock', function ( e, data ) {
			if ( ! perdido ) {
				data['wp-refresh-post-lock'] = {
					post_id: Number( caja.dataset.postId ),
					lock: cerradura
				};
			}
		} );

		$( document ).on( 'heartbeat-tick.evtLock', function ( e, data ) {
			var respuesta = data['wp-refresh-post-lock'];
			if ( ! respuesta || perdido ) {
				return;
			}
			if ( respuesta.lock_error ) {
				perdido = true;
				// Congelado, no borrado: lo escrito sigue en pantalla para
				// poder copiarlo antes de tomar posesión o de irse.
				Array.prototype.forEach.call( document.querySelectorAll( 'form' ), function ( form ) {
					if ( fuera( form ) ) {
						form.inert = true;
					}
				} );
				document.getElementById( 'evt-lock-owner' ).textContent = respuesta.lock_error.name;
				if ( dialogo.showModal ) {
					dialogo.showModal();
				} else {
					dialogo.setAttribute( 'open', '' );
				}
			} else if ( respuesta.new_lock ) {
				cerradura = respuesta.new_lock;
			}
		} );

		// El aviso no se cierra con Escape: no es una confirmación, es que no
		// se puede editar, y cerrarlo devolvería una pantalla que miente.
		dialogo.addEventListener( 'cancel', function ( e ) {
			e.preventDefault();
		} );

		// En captura, antes que la confirmación de SweetAlert2: si el bloqueo
		// ya se perdió, el envío no sale ni siquiera al servidor.
		document.addEventListener( 'submit', function ( e ) {
			if ( perdido && fuera( e.target ) ) {
				e.preventDefault();
				e.stopImmediatePropagation();
			}
		}, true );

		// Como el editor clásico: al enviar NO se suelta el bloqueo, que a
		// partir de ahí es del servidor. Soltarlo aquí volvería a crear con la
		// baliza el bloqueo que el propio guardado acaba de borrar.
		window.addEventListener( 'submit', function ( e ) {
			if ( fuera( e.target ) && ! e.defaultPrevented ) {
				enviando = true;
			}
		} );

		window.addEventListener( 'pagehide', function () {
			if ( perdido || enviando || ! navigator.sendBeacon || ! caja.dataset.ajaxUrl ) {
				return;
			}
			var datos = new FormData();
			datos.append( 'action', 'wp-remove-post-lock' );
			datos.append( '_wpnonce', caja.dataset.releaseNonce );
			datos.append( 'post_ID', caja.dataset.postId );
			datos.append( 'active_post_lock', cerradura );
			navigator.sendBeacon( caja.dataset.ajaxUrl, datos );
		} );

		// Una página restaurada del historial trae campos viejos y un bloqueo
		// que ya puede ser de otra persona: se vuelve a preguntar al servidor.
		window.addEventListener( 'pageshow', function ( e ) {
			if ( e.persisted ) {
				window.location.reload();
			}
		} );

		window.wp.heartbeat.interval( 15 );
	} );

	/* --- 5. Bocadillos en los botones de icono --------------------------- */

	/*
	 * Un botón de icono no dice qué hace. Lo dice su `title`, y el navegador ya
	 * lo enseña al posarse encima: **esto es mejora, no requisito**. Con
	 * Bootstrap cargado se cambia por su bocadillo, que sale antes y se lee
	 * mejor; sin Bootstrap —o sin guion— queda el `title` de siempre.
	 *
	 * El texto de verdad para quien navega con lector de pantalla no es el
	 * `title` sino el `.screen-reader-text` que va dentro del botón.
	 */
	function bocadillos( raiz ) {
		if ( ! window.bootstrap || ! window.bootstrap.Tooltip ) {
			return;
		}
		var nodos = ( raiz || document ).querySelectorAll( '[data-bs-toggle="tooltip"]' );
		Array.prototype.forEach.call( nodos, function ( nodo ) {
			if ( ! window.bootstrap.Tooltip.getInstance( nodo ) ) {
				new window.bootstrap.Tooltip( nodo );
			}
		} );
	}
	document.addEventListener( 'DOMContentLoaded', function () {
		bocadillos( document );
	} );

	/* --- 6. El interruptor de publicación -------------------------------- */

	/*
	 * `data-evt-switch` en la casilla. Al cambiarla se envía su formulario, que
	 * es lo que se espera de un interruptor: se toca y pasa algo.
	 *
	 * El botón de al lado hace lo mismo y es el que queda sin guion; con guion
	 * se esconde, porque teniendo el interruptor sobra. Se esconde **desde
	 * aquí** y no en el CSS a propósito: si el guion no llega, el botón se ve.
	 */
	document.addEventListener( 'change', function ( e ) {
		var casilla = e.target.closest ? e.target.closest( '[data-evt-switch]' ) : null;
		if ( ! casilla ) {
			return;
		}
		var form = casilla.form;
		if ( form ) {
			casilla.disabled = true;
			form.submit();
		}
	} );
	document.addEventListener( 'DOMContentLoaded', function () {
		var botones = document.querySelectorAll( '.evt-switch-boton' );
		Array.prototype.forEach.call( botones, function ( boton ) {
			boton.hidden = true;
		} );
	} );
}() );
