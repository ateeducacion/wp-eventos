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

	// Lo que solo tiene sentido con guion se enseña o se esconde con esta clase.
	document.documentElement.classList.add( 'evt-app-js' );

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
		if ( form.hasAttribute( 'data-evt-confirm-escribe' ) ) {
			confirmarEscribiendo( e, form, pregunta );
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

	/*
	 * Lo que no tiene papelera —borrar una inscripción— pide además teclear
	 * un dato: `data-evt-confirm-escribe="<lo que hay que escribir>"` y, en el
	 * formulario, el campo `[name="evt_confirm_email"]`, que sin guion se ve y
	 * se rellena a mano. Los mismos tres escalones, y el servidor comprueba lo
	 * escrito en los tres: esto solo evita el viaje de ida y vuelta.
	 */
	function normaliza( texto ) {
		return String( texto || '' ).trim().toLowerCase();
	}

	function confirmarEscribiendo( e, form, pregunta ) {
		var esperado = normaliza( form.getAttribute( 'data-evt-confirm-escribe' ) );
		var campo = form.querySelector( '[name="evt_confirm_email"]' );
		e.preventDefault();

		function enviar( escrito ) {
			if ( campo ) {
				campo.value = escrito;
			}
			form.dataset.evtConfirmado = '1';
			form.submit();
		}

		if ( ! window.Swal ) {
			var escrito = window.prompt( pregunta + '\n\nPara confirmar, escriba su correo: ' + esperado );
			if ( null !== escrito && normaliza( escrito ) === esperado ) {
				enviar( escrito );
			}
			return;
		}

		var corte = pregunta.indexOf( '? ' );
		window.Swal.fire( {
			title: -1 === corte ? pregunta : pregunta.slice( 0, corte + 1 ),
			text: ( -1 === corte ? '' : pregunta.slice( corte + 2 ) + ' ' ) + 'Para confirmar, escriba su correo: ' + esperado,
			icon: 'warning',
			input: 'email',
			inputPlaceholder: esperado,
			inputAttributes: { autocomplete: 'off', 'aria-label': 'Correo de la persona' },
			validationMessage: 'Escriba un correo válido.',
			inputValidator: function ( valor ) {
				return normaliza( valor ) === esperado ? undefined : 'No coincide con el correo de esta inscripción.';
			},
			showCancelButton: true,
			confirmButtonText: form.getAttribute( 'data-evt-confirm-ok' ) || 'Borrar',
			cancelButtonText: 'Cancelar',
			focusCancel: false,
			reverseButtons: true,
			heightAuto: false,
			buttonsStyling: false,
			customClass: {
				confirmButton: 'evt-btn evt-btn-borrar btn',
				cancelButton: 'evt-btn btn btn-light'
			}
		} ).then( function ( respuesta ) {
			if ( respuesta.isConfirmed ) {
				enviar( respuesta.value );
			}
		} );
	}

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

	/* --- 4. Elegir un archivo de la biblioteca ---------------------------- */

	/*
	 * Cada archivo gestionado con la biblioteca es un `[data-evt-media]`. La
	 * ventana nativa de WordPress aporta biblioteca, subida por arrastre y la
	 * previsualización del adjunto; la ficha conserva una vista rápida fuera.
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
			mini.src = chica ? chica.url : ( adjunto.icon || adjunto.url );
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
		var tipo = caja.getAttribute( 'data-evt-media-type' );
		var opciones = {
			title: caja.getAttribute( 'data-evt-media-title' ) || 'Elegir archivo',
			button: { text: caja.getAttribute( 'data-evt-media-button' ) || 'Usar este archivo' },
			multiple: false
		};
		if ( tipo ) {
			opciones.library = { type: tipo };
		}
		var marco = window.wp.media( opciones );
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

	/* El mismo cargador que usa «Medios» convierte cada campo en una zona de
	 * soltado. La subida crea el adjunto en WordPress inmediatamente; elegirlo
	 * para el evento sigue esperando a «Guardar la apariencia» o el ponente. */
	function arrancarSubidas() {
		if ( ! window.wp || ! window.wp.Uploader || ! window.jQuery ) {
			return;
		}
		Array.prototype.forEach.call( document.querySelectorAll( '[data-evt-media]' ), function ( caja ) {
			if ( '1' === caja.dataset.evtUploader ) {
				return;
			}
			caja.dataset.evtUploader = '1';
			var estado = caja.querySelector( '[data-evt-media-status]' );
			var tipo = caja.getAttribute( 'data-evt-media-type' );
			var minimo = parseInt( caja.getAttribute( 'data-evt-media-min-width' ) || '0', 10 );
			var opciones = {
				container: caja,
				dropzone: caja,
				plupload: { multi_selection: false },
				added: function ( adjunto ) {
					if ( estado ) {
						estado.textContent = 'Subiendo ' + ( adjunto.get( 'filename' ) || 'el archivo' ) + '…';
					}
				},
				progress: function ( adjunto ) {
					if ( estado ) {
						estado.textContent = 'Subiendo… ' + ( adjunto.get( 'percent' ) || 0 ) + '%';
					}
				},
				success: function ( adjunto ) {
					var archivo = adjunto.toJSON();
					if ( minimo > 0 && ( ! archivo.width || archivo.width < minimo ) ) {
						if ( estado ) {
							estado.textContent = 'La imagen se subió a la biblioteca, pero no se puede usar aquí: necesita al menos ' + minimo + ' px de ancho.';
						}
						return;
					}
					poner( caja, archivo );
					if ( estado ) {
						estado.textContent = 'Archivo subido. Guarde el formulario para aplicar el cambio.';
					}
				},
				error: function ( mensaje ) {
					if ( estado ) {
						estado.textContent = mensaje || 'No se pudo subir el archivo.';
					}
				}
			};
			if ( 'image' === tipo ) {
				opciones.plupload.filters = {
					mime_types: [ { title: 'Imágenes', extensions: 'jpg,jpeg,png,gif,webp' } ]
				};
			}
			new window.wp.Uploader( opciones );
		} );
	}

	arrancarSubidas();
	document.addEventListener( 'DOMContentLoaded', arrancarSubidas );
	window.addEventListener( 'load', arrancarSubidas );

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

	/* --- 7. El listado de eventos: filtrar por nombre mientras se escribe -- */

	/*
	 * `data-evt-filtro` en el campo; `data-evt-buscar="<nombre normalizado>"`
	 * en cada tarjeta o fila. Se esconden las que no contienen lo escrito, sin
	 * tildes ni mayúsculas, que es como el servidor normaliza el nombre. Solo
	 * filtra la página que se ve: Intro envía el formulario y busca en todas.
	 */
	function normalizar( texto ) {
		return String( texto ).normalize( 'NFD' ).replace( /[̀-ͯ]/g, '' ).toLowerCase().trim();
	}

	document.addEventListener( 'input', function ( e ) {
		var campo = e.target.closest ? e.target.closest( '[data-evt-filtro]' ) : null;
		if ( ! campo ) {
			return;
		}
		var busca = normalizar( campo.value );
		var elementos = document.querySelectorAll( '[data-evt-buscar]' );
		var visibles = 0;
		Array.prototype.forEach.call( elementos, function ( el ) {
			var sale = '' === busca || -1 !== normalizar( el.getAttribute( 'data-evt-buscar' ) ).indexOf( busca );
			el.hidden = ! sale;
			if ( sale ) {
				visibles++;
			}
		} );
		var vacio = document.querySelector( '[data-evt-filtro-vacio]' );
		if ( vacio ) {
			vacio.hidden = 0 !== visibles || 0 === elementos.length;
		}
	} );

	/* Un desplegable con `data-evt-autoenvio` envía su formulario al cambiar. */
	document.addEventListener( 'change', function ( e ) {
		var lista = e.target.closest ? e.target.closest( '[data-evt-autoenvio]' ) : null;
		if ( lista && lista.form ) {
			lista.form.submit();
		}
	} );

	/* --- 8. El panel lateral: se abre y se cierra deslizándose ------------ */

	/*
	 * La hoja del aplicativo se centra con `transform`, y eso hace de ella la
	 * caja de referencia de todo lo `position: fixed` que lleve dentro: el
	 * panel quedaría debajo de la cabecera y del pie. Se lleva al final del
	 * `<body>`, que es donde un panel sobre la página tiene que estar.
	 *
	 * El de alta ya está en la página, escondido: «Añadir» lo abre sin
	 * recargar. Cerrar lo esconde; el de edición, que trae los datos de una
	 * ficha, recarga la pestaña para no dejar esos datos en el alta. Sin
	 * guion, los mismos enlaces lo hacen todo por la dirección.
	 */
	function piezasCajon() {
		return {
			cajon: document.querySelector( '[data-evt-cajon]' ),
			fondo: document.querySelector( '.evt-cajon-fondo' )
		};
	}

	function enfocarCajon( cajon ) {
		var primero = cajon.querySelector( 'input:not([type=hidden]), textarea, select' );
		if ( primero ) {
			primero.focus();
		}
	}

	function abrirCajon() {
		var p = piezasCajon();
		if ( ! p.cajon ) {
			return false;
		}
		p.cajon.classList.remove( 'evt-cajon--saliendo' );
		p.cajon.hidden = false;
		if ( p.fondo ) {
			p.fondo.classList.remove( 'evt-cajon--saliendo' );
			p.fondo.hidden = false;
		}
		enfocarCajon( p.cajon );
		return true;
	}

	function cerrarCajon( destino ) {
		var p = piezasCajon();
		if ( ! p.cajon || p.cajon.hidden ) {
			return;
		}
		var recargar = p.cajon.hasAttribute( 'data-evt-cajon-edita' );
		// Se cierra también pulsando el fondo, que es un `<div>` sin `href`:
		// la dirección de vuelta es siempre la del botón de cerrar del panel.
		var boton = p.cajon.querySelector( '.evt-cajon__cerrar' );
		var vuelta = ( destino && destino.href ) || ( boton && boton.href ) || window.location.href;
		var reducido = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
		p.cajon.classList.add( 'evt-cajon--saliendo' );
		if ( p.fondo ) {
			p.fondo.classList.add( 'evt-cajon--saliendo' );
		}
		window.setTimeout( function () {
			if ( recargar ) {
				window.location.assign( vuelta );
				return;
			}
			p.cajon.hidden = true;
			if ( p.fondo ) {
				p.fondo.hidden = true;
			}
			// La dirección deja de pedir el alta: recargar no la vuelve a abrir.
			if ( window.history && window.history.replaceState ) {
				window.history.replaceState( null, '', vuelta );
			}
		}, reducido ? 0 : 200 );
	}

	function prepararCajon() {
		var p = piezasCajon();
		if ( ! p.cajon ) {
			return;
		}
		if ( p.fondo ) {
			document.body.appendChild( p.fondo );
		}
		document.body.appendChild( p.cajon );
		if ( ! p.cajon.hidden ) {
			enfocarCajon( p.cajon );
		}
	}
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', prepararCajon );
	} else {
		prepararCajon();
	}

	document.addEventListener( 'click', function ( e ) {
		var abrir = e.target.closest ? e.target.closest( '[data-evt-abrir-cajon]' ) : null;
		if ( abrir && abrirCajon() ) {
			e.preventDefault();
			return;
		}
		var cerrar = e.target.closest ? e.target.closest( '[data-evt-cerrar-cajon]' ) : null;
		if ( cerrar ) {
			e.preventDefault();
			cerrarCajon( cerrar );
		}
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' !== e.key ) {
			return;
		}
		cerrarCajon( document.querySelector( '[data-evt-cajon] .evt-cajon__cerrar' ) );
	} );

	/*
	 * La edición de una página es su propia pantalla, con el editor de
	 * WordPress y el código. Se abre en el panel lateral dentro de un marco,
	 * pintada sin cabecera ni pie (`evt_marco=1`). Al cerrarlo se recarga la
	 * pestaña, que así enseña lo que se haya guardado. Sin guion, el enlace y
	 * el formulario abren la pantalla entera, como siempre.
	 */
	function abrirMarco( url, titulo, vista ) {
		var direccion = new URL( url, window.location.href );
		if ( direccion.origin !== window.location.origin ) {
			return false;
		}
		// La vista previa es la página pública tal cual, para navegarla; la
		// edición, la pantalla del aplicativo sin cabecera ni pie.
		if ( ! vista ) {
			direccion.searchParams.set( 'evt_marco', '1' );
		}

		var fondo = document.createElement( 'div' );
		fondo.className = 'evt-cajon-fondo';
		var cajon = document.createElement( 'section' );
		cajon.className = vista ? 'evt-cajon evt-cajon--vista' : 'evt-cajon evt-cajon--pagina';
		cajon.setAttribute( 'role', 'dialog' );
		cajon.setAttribute( 'aria-modal', 'true' );
		cajon.setAttribute( 'aria-label', titulo );
		cajon.setAttribute( 'data-evt-cajon', '' );
		// Cerrar la edición recarga la pestaña para enseñar lo guardado;
		// cerrar la vista previa no toca nada de lo que se estaba haciendo.
		if ( ! vista ) {
			cajon.setAttribute( 'data-evt-cajon-edita', '' );
		}

		var cabecera = document.createElement( 'header' );
		cabecera.className = 'evt-cajon__cabecera';
		var h2 = document.createElement( 'h2' );
		h2.className = 'evt-cajon__titulo';
		h2.textContent = titulo;
		var cerrar = document.createElement( 'a' );
		cerrar.className = 'evt-cajon__cerrar';
		cerrar.href = window.location.href;
		cerrar.setAttribute( 'aria-label', 'Cerrar' );
		cerrar.setAttribute( 'data-evt-cerrar-cajon', '' );
		cerrar.textContent = '×';
		fondo.setAttribute( 'data-evt-cerrar-cajon', '' );
		cabecera.appendChild( h2 );
		if ( vista ) {
			var fuera = document.createElement( 'a' );
			fuera.className = 'evt-cajon__fuera';
			fuera.href = direccion.toString();
			fuera.target = '_blank';
			fuera.rel = 'noopener';
			fuera.textContent = 'Abrir en otra pestaña';
			cabecera.appendChild( fuera );
		}
		cabecera.appendChild( cerrar );

		var marco = document.createElement( 'iframe' );
		marco.className = 'evt-cajon__marco';
		marco.title = titulo;
		marco.src = direccion.toString();

		cajon.appendChild( cabecera );
		cajon.appendChild( marco );
		// Otro panel que hubiera, fuera: solo hay uno a la vez.
		var viejos = document.querySelectorAll( '[data-evt-cajon], .evt-cajon-fondo' );
		Array.prototype.forEach.call( viejos, function ( viejo ) {
			viejo.parentNode.removeChild( viejo );
		} );
		document.body.appendChild( fondo );
		document.body.appendChild( cajon );
		return true;
	}

	document.addEventListener( 'click', function ( e ) {
		if ( e.metaKey || e.ctrlKey || e.shiftKey || ! e.target.closest ) {
			return;
		}
		var enlace = e.target.closest( 'a.evt-abre-marco' );
		if ( enlace && abrirMarco( enlace.href, 'Editar la página', false ) ) {
			e.preventDefault();
			return;
		}
		var ver = e.target.closest( 'a.evt-abre-vista' );
		if ( ver && abrirMarco( ver.href, 'Así se ve la página', true ) ) {
			e.preventDefault();
		}
	} );

	document.addEventListener( 'submit', function ( e ) {
		var form = e.target;
		if ( ! form || ! form.matches || ! form.matches( 'form[data-evt-marco]' ) ) {
			return;
		}
		var url = new URL( form.getAttribute( 'action' ) || window.location.href, window.location.href );
		new FormData( form ).forEach( function ( valor, clave ) {
			url.searchParams.set( clave, valor );
		} );
		if ( abrirMarco( url.toString(), 'Nueva página', false ) ) {
			e.preventDefault();
		}
	} );

	/* --- 9. La barra de guardar: avisa de los cambios sin guardar --------- */

	/*
	 * `data-evt-cambios` en el formulario. Al tocar un campo, la barra dice
	 * «Hay cambios sin guardar» y enseña «Descartar», que vuelve el formulario
	 * a como estaba. Salir de la página con cambios pide confirmación. Sin
	 * guion, la barra es solo el botón de guardar.
	 */
	function marcar( form, sucio ) {
		var barra = form.querySelector( '[data-evt-guardar]' );
		if ( ! barra ) {
			return;
		}
		form.evtSucio = sucio;
		barra.classList.toggle( 'evt-guardar--sucio', sucio );
		var estado = barra.querySelector( '[data-evt-guardar-estado]' );
		if ( estado ) {
			estado.textContent = sucio ? 'Hay cambios sin guardar' : '';
		}
		var descartar = barra.querySelector( '[data-evt-guardar-descartar]' );
		if ( descartar ) {
			descartar.hidden = ! sucio;
		}
	}

	function alCambiar( e ) {
		var form = e.target.closest ? e.target.closest( 'form[data-evt-cambios]' ) : null;
		if ( form ) {
			marcar( form, true );
		}
	}
	document.addEventListener( 'input', alCambiar );
	document.addEventListener( 'change', alCambiar );

	document.addEventListener( 'reset', function ( e ) {
		var form = e.target;
		if ( form && form.matches && form.matches( 'form[data-evt-cambios]' ) ) {
			window.setTimeout( function () {
				marcar( form, false );
				form.dispatchEvent( new Event( 'evt:descartado', { bubbles: true } ) );
			}, 0 );
		}
	} );

	document.addEventListener( 'submit', function ( e ) {
		if ( e.target && e.target.matches && e.target.matches( 'form[data-evt-cambios]' ) ) {
			e.target.evtSucio = false;
		}
	}, true );

	/* --- 10. Las preguntas de la inscripción ------------------------------ */

	/*
	 * Dos cosas, y las dos sobre un formulario que ya funciona sin ellas.
	 *
	 * Las opciones, una por línea, solo valen para «Una opción» y «Varias
	 * opciones»: se esconde el campo en las demás y se enseña al cambiar el
	 * tipo. Qué tipos llevan opciones lo dice el servidor en
	 * `data-evt-q-opciones`, para no repetir aquí la lista.
	 *
	 * «Añadir otra pregunta» copia la fila en blanco del final con el índice
	 * siguiente, la abre y pone el cursor en el rótulo: se pueden añadir varias
	 * antes de guardar. Sin guion, la fila en blanco de siempre, de una en una.
	 */
	function opcionesSegunTipo( fila ) {
		var campo = fila.querySelector( '[data-evt-q-opciones]' );
		var tipo = fila.querySelector( 'select[name^="evt_q_type"]' );
		if ( ! campo || ! tipo ) {
			return;
		}
		var llevan = campo.getAttribute( 'data-evt-q-opciones' ).split( ' ' );
		campo.hidden = -1 === llevan.indexOf( tipo.value );
	}

	document.addEventListener( 'change', function ( e ) {
		var tipo = e.target.closest ? e.target.closest( 'select[name^="evt_q_type"]' ) : null;
		var fila = tipo ? tipo.closest( '.evt-pregunta' ) : null;
		if ( fila ) {
			opcionesSegunTipo( fila );
		}
	} );

	function arrancarPreguntas() {
		Array.prototype.forEach.call( document.querySelectorAll( '.evt-pregunta' ), opcionesSegunTipo );

		var lista = document.querySelector( '[data-evt-preguntas]' );
		var boton = document.querySelector( '[data-evt-pregunta-nueva]' );
		var molde = lista ? lista.querySelector( '.evt-pregunta--nueva' ) : null;
		if ( ! lista || ! boton || ! molde ) {
			return;
		}
		// La copia se toma ahora, antes de que nadie escriba en la fila.
		molde = molde.cloneNode( true );
		var siguiente = lista.querySelectorAll( '.evt-pregunta' ).length;
		boton.hidden = false;

		boton.addEventListener( 'click', function () {
			var fila = molde.cloneNode( true );
			var i = siguiente++;
			Array.prototype.forEach.call( fila.querySelectorAll( '[name], [id], [for]' ), function ( nodo ) {
				[ 'name', 'id', 'for' ].forEach( function ( atributo ) {
					var valor = nodo.getAttribute( atributo );
					if ( valor ) {
						nodo.setAttribute( atributo, valor.replace( /\[\d+\]$/, '[' + i + ']' ).replace( /-\d+$/, '-' + i ) );
					}
				} );
			} );
			fila.open = true;
			lista.appendChild( fila );
			opcionesSegunTipo( fila );
			var rotulo = fila.querySelector( 'input[name^="evt_q_label"]' );
			if ( rotulo ) {
				rotulo.focus();
			}
		} );
	}
	document.addEventListener( 'DOMContentLoaded', arrancarPreguntas );

	/* --- 11. El árbol de ámbitos: plegar y desplegar ramas ---------------- */

	/*
	 * Sin guion, el árbol entero abierto y sangrado, que se lee igual. Con
	 * guion, cada rama tiene su flecha y arrancan abiertas solo las que llevan
	 * algo marcado dentro: lo que ya es del evento se ve sin buscarlo.
	 */
	function plegar( boton, abierta ) {
		var rama = boton.closest( '.evt-arbol__rama' );
		var hijos = rama ? rama.querySelector( ':scope > ul' ) : null;
		boton.setAttribute( 'aria-expanded', abierta ? 'true' : 'false' );
		if ( hijos ) {
			hijos.hidden = ! abierta;
		}
	}

	document.addEventListener( 'click', function ( e ) {
		var boton = e.target.closest ? e.target.closest( '[data-evt-arbol-plegar]' ) : null;
		if ( boton ) {
			plegar( boton, 'true' !== boton.getAttribute( 'aria-expanded' ) );
		}
	} );

	document.addEventListener( 'DOMContentLoaded', function () {
		Array.prototype.forEach.call( document.querySelectorAll( '[data-evt-arbol-plegar]' ), function ( boton ) {
			var rama = boton.closest( '.evt-arbol__rama' );
			boton.hidden = false;
			plegar( boton, !! ( rama && rama.querySelector( ':scope > ul input:checked' ) ) );
		} );
	} );

	/* --- 12. Filas que se repiten: «Añadir otro punto» ------------------ */

	/*
	 * `data-evt-filas` en la caja, `data-evt-fila` en cada fila y
	 * `data-evt-filas-nueva` en el botón, dentro del mismo campo. El botón
	 * copia la última fila vacía con el índice siguiente. Sin guion, la fila en
	 * blanco del final, de una en una.
	 */
	document.addEventListener( 'DOMContentLoaded', function () {
		Array.prototype.forEach.call( document.querySelectorAll( '[data-evt-filas-nueva]' ), function ( boton ) {
			var campo = boton.closest( '.evt-form-campo' );
			var caja = campo ? campo.querySelector( '[data-evt-filas]' ) : null;
			var filas = caja ? caja.querySelectorAll( '[data-evt-fila]' ) : [];
			if ( ! filas.length ) {
				return;
			}
			var molde = filas[ filas.length - 1 ].cloneNode( true );
			var siguiente = filas.length;
			boton.hidden = false;
			boton.addEventListener( 'click', function () {
				var fila = molde.cloneNode( true );
				var i = siguiente++;
				Array.prototype.forEach.call( fila.querySelectorAll( '[name], [id], [for]' ), function ( nodo ) {
					[ 'name', 'id', 'for' ].forEach( function ( atributo ) {
						var valor = nodo.getAttribute( atributo );
						if ( valor ) {
							nodo.setAttribute( atributo, valor.replace( /\[\d+\]$/, '[' + i + ']' ).replace( /-\d+$/, '-' + i ) );
						}
					} );
					if ( 'value' in nodo && 'INPUT' === nodo.tagName ) {
						nodo.value = '';
					}
				} );
				caja.appendChild( fila );
				var primero = fila.querySelector( 'input' );
				if ( primero ) {
					primero.focus();
				}
			} );
		} );
	} );

	window.addEventListener( 'beforeunload', function ( e ) {
		var formularios = document.querySelectorAll( 'form[data-evt-cambios]' );
		for ( var i = 0; i < formularios.length; i++ ) {
			if ( formularios[ i ].evtSucio ) {
				e.preventDefault();
				e.returnValue = '';
				return;
			}
		}
	} );
}() );
