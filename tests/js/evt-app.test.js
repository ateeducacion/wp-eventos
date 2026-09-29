/**
 * Unit tests for assets/js/evt-app.js.
 *
 * Each test builds the markup the server renders, loads the script on it and
 * drives it with DOM events. The confirmation before deleting (section 1) is
 * left to `npm run test:browser`, which checks it in a real browser.
 */
import { afterEach, describe, expect, it, vi } from 'vitest';

async function load() {
	vi.resetModules();
	await import( '../../assets/js/evt-app.js' );
}

function ready() {
	document.dispatchEvent( new Event( 'DOMContentLoaded' ) );
}

function type( field, value ) {
	field.value = value;
	field.dispatchEvent( new Event( 'input', { bubbles: true } ) );
}

function change( field ) {
	field.dispatchEvent( new Event( 'change', { bubbles: true } ) );
}

function click( node, init = {} ) {
	const event = new MouseEvent( 'click', { bubbles: true, cancelable: true, ...init } );
	node.dispatchEvent( event );
	return event;
}

function $( selector ) {
	return document.querySelector( selector );
}

afterEach( () => {
	delete window.wp;
	delete window.jQuery;
	delete window.bootstrap;
	delete navigator.sendBeacon;
	vi.useRealTimers();
} );

describe( 'slug proposal', () => {
	async function mount() {
		document.body.innerHTML = `<form>
			<input name="title" data-evt-slug-source>
			<input name="slug" data-evt-slug-target>
		</form>`;
		await load();
	}

	it( 'proposes a slug from the title, without accents or symbols', async () => {
		await mount();
		type( $( '[data-evt-slug-source]' ), '  Jornadas de Innovación ¡2026!  ' );
		expect( $( '[data-evt-slug-target]' ).value ).toBe( 'jornadas-de-innovacion-2026' );
	} );

	it( 'caps the slug at 80 characters', async () => {
		await mount();
		type( $( '[data-evt-slug-source]' ), 'a'.repeat( 100 ) );
		expect( $( '[data-evt-slug-target]' ).value ).toHaveLength( 80 );
	} );

	it( 'stops proposing once the slug has been typed by hand', async () => {
		await mount();
		type( $( '[data-evt-slug-target]' ), 'mi-slug' );
		type( $( '[data-evt-slug-source]' ), 'Otro título' );
		expect( $( '[data-evt-slug-target]' ).value ).toBe( 'mi-slug' );
	} );

	it( 'leaves a title outside a form with a slug field alone', async () => {
		document.body.innerHTML = '<input data-evt-slug-source><input data-evt-slug-target>';
		await load();
		type( $( '[data-evt-slug-source]' ), 'Título' );
		expect( $( '[data-evt-slug-target]' ).value ).toBe( '' );
	} );
} );

describe( 'header preview', () => {
	async function mount() {
		document.body.innerHTML = `<form>
			<input type="color" id="bg-picker" data-evt-color-for="bg" value="#000000">
			<input type="text" id="bg" name="evt_header_bg" value="#112233">
			<input type="color" id="fg-picker" data-evt-color-for="fg" value="#000000">
			<input type="text" id="fg" name="evt_header_text" value="#ffffff">
			<select name="evt_title_font"><option value="lato">Lato</option><option value="roboto" selected>Roboto</option></select>
			<select name="evt_body_font"><option value="" selected>Por defecto</option><option value="lato">Lato</option></select>
			<select name="evt_image_shape"><option value="square">Cuadrada</option><option value="circle">Redonda</option></select>
			<select name="evt_separator"><option value="">Ninguno</option><option value="wave" selected>Ola</option></select>
			<input name="evt_tagline" value="Lema del evento">
			<div data-evt-preview>
				<div data-evt-preview-header>
					<h1 data-evt-preview-title class="titulo evt-font-old">Título</h1>
					<p data-evt-preview-tagline></p>
				</div>
				<div data-evt-preview-body class="evt-font-old"></div>
				<span data-evt-preview-shape class="evt-shape-circle"></span>
				<div data-evt-preview-sep><svg data-sep="wave"></svg><svg data-sep="zigzag"></svg></div>
			</div>
		</form>`;
		await load();
		ready();
	}

	it( 'starts from the saved values', async () => {
		await mount();
		const header = $( '[data-evt-preview-header]' );
		expect( header.style.backgroundColor ).toBe( 'rgb(17, 34, 51)' );
		expect( header.style.color ).toBe( 'rgb(255, 255, 255)' );
		expect( $( '[data-evt-preview-title]' ).className ).toBe( 'titulo evt-font-roboto' );
		expect( $( '[data-evt-preview-body]' ).className ).toBe( '' );
		expect( $( '[data-evt-preview-tagline]' ).textContent ).toBe( 'Lema del evento' );
		expect( $( '[data-evt-preview-shape]' ).className ).toBe( 'evt-shape-square' );
		expect( $( '[data-sep="wave"]' ).hidden ).toBe( false );
		expect( $( '[data-sep="zigzag"]' ).hidden ).toBe( true );
	} );

	it( 'copies the colour picker into its hex field and repaints', async () => {
		await mount();
		type( $( '#bg-picker' ), '#abcdef' );
		expect( $( '#bg' ).value ).toBe( '#abcdef' );
		expect( $( '[data-evt-preview-header]' ).style.backgroundColor ).toBe( 'rgb(171, 205, 239)' );
	} );

	it( 'moves the colour picker only when a complete hex is typed', async () => {
		await mount();
		type( $( '#fg' ), '#12' );
		expect( $( '#fg-picker' ).value ).toBe( '#000000' );
		type( $( '#fg' ), '#123456' );
		expect( $( '#fg-picker' ).value ).toBe( '#123456' );
	} );

	it( 'follows the shape, separator and tagline as they change', async () => {
		await mount();
		type( $( '[name="evt_image_shape"]' ), 'circle' );
		type( $( '[name="evt_separator"]' ), '' );
		type( $( '[name="evt_tagline"]' ), 'Nuevo lema' );
		expect( $( '[data-evt-preview-shape]' ).className ).toBe( 'evt-shape-circle' );
		expect( $( '[data-sep="wave"]' ).hidden ).toBe( true );
		expect( $( '[data-evt-preview-tagline]' ).textContent ).toBe( 'Nuevo lema' );
	} );
} );

describe( 'media field', () => {
	const MEDIA = `<form><div data-evt-media data-evt-media-type="image" data-evt-media-title="Elegir el cartel" data-evt-media-min-width="800">
		<input type="hidden" data-evt-media-value value="0">
		<div data-evt-media-card hidden><img data-evt-media-thumb><span data-evt-media-name></span><span data-evt-media-size></span></div>
		<p data-evt-media-empty>Todavía no hay imagen</p>
		<div data-evt-media-actions hidden>
			<button type="button" data-evt-media-pick>Elegir imagen</button>
			<button type="button" data-evt-media-clear hidden>Quitar</button>
		</div>
		<p data-evt-media-status></p>
	</div></form>`;

	const ATTACHMENT = {
		id: 42,
		url: 'http://localhost/cartel.jpg',
		sizes: { medium: { url: 'http://localhost/cartel-300.jpg' } },
		alt: 'Cartel',
		filename: 'cartel.jpg',
		width: 1200,
		height: 630,
	};

	function fakeMediaFrame( attachment ) {
		const handlers = {};
		const frame = {
			on: ( name, fn ) => {
				handlers[ name ] = fn;
			},
			open: vi.fn(),
			state: () => ( { get: () => ( { first: () => attachment && { toJSON: () => attachment } } ) } ),
			select: () => handlers.select(),
		};
		window.wp = { media: vi.fn( () => frame ) };
		return frame;
	}

	it( 'reveals the buttons once the script runs', async () => {
		document.body.innerHTML = MEDIA;
		await load();
		expect( $( '[data-evt-media-actions]' ).hidden ).toBe( false );
	} );

	it( 'opens the media library filtered by type and shows what is picked', async () => {
		document.body.innerHTML = MEDIA;
		const frame = fakeMediaFrame( ATTACHMENT );
		await load();

		expect( click( $( '[data-evt-media-pick]' ) ).defaultPrevented ).toBe( true );
		expect( window.wp.media ).toHaveBeenCalledWith( {
			title: 'Elegir el cartel',
			button: { text: 'Usar este archivo' },
			multiple: false,
			library: { type: 'image' },
		} );
		expect( frame.open ).toHaveBeenCalled();

		frame.select();
		expect( $( '[data-evt-media-value]' ).value ).toBe( '42' );
		expect( $( '[data-evt-media-card]' ).hidden ).toBe( false );
		expect( $( '[data-evt-media-empty]' ).hidden ).toBe( true );
		expect( $( '[data-evt-media-clear]' ).hidden ).toBe( false );
		expect( $( '[data-evt-media-thumb]' ).src ).toBe( 'http://localhost/cartel-300.jpg' );
		expect( $( '[data-evt-media-thumb]' ).alt ).toBe( 'Cartel' );
		expect( $( '[data-evt-media-name]' ).textContent ).toBe( 'cartel.jpg' );
		expect( $( '[data-evt-media-size]' ).textContent ).toBe( '1200 × 630 px' );
	} );

	it( 'falls back to the full file and leaves the size empty without dimensions', async () => {
		document.body.innerHTML = MEDIA;
		const frame = fakeMediaFrame( { id: 7, url: 'http://localhost/doc.pdf', title: 'Documento' } );
		await load();
		click( $( '[data-evt-media-pick]' ) );
		frame.select();
		expect( $( '[data-evt-media-thumb]' ).src ).toBe( 'http://localhost/doc.pdf' );
		expect( $( '[data-evt-media-name]' ).textContent ).toBe( 'Documento' );
		expect( $( '[data-evt-media-size]' ).textContent ).toBe( '' );
	} );

	it( 'keeps the current file when nothing is selected', async () => {
		document.body.innerHTML = MEDIA;
		const frame = fakeMediaFrame( null );
		await load();
		click( $( '[data-evt-media-pick]' ) );
		frame.select();
		expect( $( '[data-evt-media-value]' ).value ).toBe( '0' );
	} );

	it( 'clears the file', async () => {
		document.body.innerHTML = MEDIA;
		const frame = fakeMediaFrame( ATTACHMENT );
		await load();
		click( $( '[data-evt-media-pick]' ) );
		frame.select();

		click( $( '[data-evt-media-clear]' ) );
		expect( $( '[data-evt-media-value]' ).value ).toBe( '0' );
		expect( $( '[data-evt-media-card]' ).hidden ).toBe( true );
		expect( $( '[data-evt-media-empty]' ).hidden ).toBe( false );
		expect( $( '[data-evt-media-clear]' ).hidden ).toBe( true );
	} );

	it( 'does nothing on pick when the media library is not loaded', async () => {
		document.body.innerHTML = MEDIA;
		await load();
		expect( () => click( $( '[data-evt-media-pick]' ) ) ).not.toThrow();
		expect( $( '[data-evt-media-value]' ).value ).toBe( '0' );
	} );

	describe( 'drag-and-drop upload', () => {
		function fakeUploader() {
			const uploaders = [];
			window.jQuery = () => {};
			window.wp = {
				Uploader: vi.fn( function ( options ) {
					uploaders.push( options );
				} ),
			};
			return uploaders;
		}

		function file( props ) {
			return { get: ( key ) => props[ key ], toJSON: () => props };
		}

		it( 'turns each field into an image drop zone once', async () => {
			document.body.innerHTML = MEDIA;
			const uploaders = fakeUploader();
			await load();
			ready();
			window.dispatchEvent( new Event( 'load' ) );

			expect( uploaders ).toHaveLength( 1 );
			expect( uploaders[ 0 ].container ).toBe( $( '[data-evt-media]' ) );
			expect( uploaders[ 0 ].dropzone ).toBe( $( '[data-evt-media]' ) );
			expect( uploaders[ 0 ].plupload.filters.mime_types[ 0 ].extensions ).toBe( 'jpg,jpeg,png,gif,webp' );
		} );

		it( 'reports progress and uses a wide enough upload', async () => {
			document.body.innerHTML = MEDIA;
			const uploaders = fakeUploader();
			await load();
			const uploader = uploaders[ 0 ];
			const status = $( '[data-evt-media-status]' );

			uploader.added( file( { filename: 'foto.jpg' } ) );
			expect( status.textContent ).toBe( 'Subiendo foto.jpg…' );
			uploader.progress( file( { percent: 40 } ) );
			expect( status.textContent ).toBe( 'Subiendo… 40%' );

			uploader.success( file( { ...ATTACHMENT, width: 1200 } ) );
			expect( $( '[data-evt-media-value]' ).value ).toBe( '42' );
			expect( status.textContent ).toBe( 'Archivo subido. Guarde el formulario para aplicar el cambio.' );
		} );

		it( 'refuses an upload narrower than the field asks for', async () => {
			document.body.innerHTML = MEDIA;
			const uploaders = fakeUploader();
			await load();

			uploaders[ 0 ].success( file( { ...ATTACHMENT, width: 640 } ) );
			expect( $( '[data-evt-media-value]' ).value ).toBe( '0' );
			expect( $( '[data-evt-media-status]' ).textContent ).toContain( 'necesita al menos 800 px de ancho' );
		} );

		it( 'shows the upload error, or a generic one', async () => {
			document.body.innerHTML = MEDIA;
			const uploaders = fakeUploader();
			await load();

			uploaders[ 0 ].error( 'Fichero demasiado grande.' );
			expect( $( '[data-evt-media-status]' ).textContent ).toBe( 'Fichero demasiado grande.' );
			uploaders[ 0 ].error( '' );
			expect( $( '[data-evt-media-status]' ).textContent ).toBe( 'No se pudo subir el archivo.' );
		} );
	} );
} );

describe( 'code editor', () => {
	it( 'initialises each field once with its own settings', async () => {
		document.body.innerHTML = `
			<textarea id="css" data-evt-code="css" data-evt-code-settings='{"codemirror":{"mode":"css"}}'></textarea>
			<textarea id="js" data-evt-code="javascript" data-evt-code-settings='{"codemirror":{"mode":"javascript"}}'></textarea>`;
		window.wp = { codeEditor: { initialize: vi.fn() } };
		await load();
		ready();
		window.dispatchEvent( new Event( 'load' ) );

		expect( window.wp.codeEditor.initialize ).toHaveBeenCalledTimes( 2 );
		expect( window.wp.codeEditor.initialize ).toHaveBeenCalledWith( $( '#css' ), { codemirror: { mode: 'css' } } );
		expect( window.wp.codeEditor.initialize ).toHaveBeenCalledWith( $( '#js' ), { codemirror: { mode: 'javascript' } } );
	} );

	it( 'leaves a field with broken settings as a plain textarea', async () => {
		document.body.innerHTML = '<textarea data-evt-code="css" data-evt-code-settings="{roto"></textarea>';
		window.wp = { codeEditor: { initialize: vi.fn() } };
		await load();
		expect( window.wp.codeEditor.initialize ).not.toHaveBeenCalled();
	} );
} );

describe( 'edit lock', () => {
	const LOCK = `
		<div id="evt-edit-lock" data-lock="100:7" data-post-id="42" data-ajax-url="/wp-admin/admin-ajax.php" data-release-nonce="n0nce">
			<dialog id="evt-lock-dialog"><span id="evt-lock-owner"></span><form id="dentro"></form></dialog>
		</div>
		<form id="editor"><input value="Texto sin guardar"></form>`;

	// Only the `on()` the script uses: the handlers are called directly.
	function fakeJQuery() {
		const handlers = {};
		window.jQuery = () => ( {
			on: ( name, fn ) => {
				handlers[ name.split( '.' )[ 0 ] ] = fn;
			},
		} );
		return {
			send() {
				const data = {};
				handlers[ 'heartbeat-send' ]( {}, data );
				return data;
			},
			tick: ( data ) => handlers[ 'heartbeat-tick' ]( {}, data ),
		};
	}

	async function mount( html = LOCK ) {
		document.body.innerHTML = html;
		const heartbeat = fakeJQuery();
		window.wp = { heartbeat: { interval: vi.fn() } };
		navigator.sendBeacon = vi.fn();
		$( 'dialog' ).showModal = vi.fn();
		await load();
		ready();
		return heartbeat;
	}

	function submit( form ) {
		const event = new Event( 'submit', { bubbles: true, cancelable: true } );
		form.dispatchEvent( event );
		return event;
	}

	it( 'renews its own lock on every heartbeat, every 15 seconds', async () => {
		const heartbeat = await mount();
		expect( window.wp.heartbeat.interval ).toHaveBeenCalledWith( 15 );
		expect( heartbeat.send() ).toEqual( { 'wp-refresh-post-lock': { post_id: 42, lock: '100:7' } } );

		heartbeat.tick( {} );
		heartbeat.tick( { 'wp-refresh-post-lock': { new_lock: '200:7' } } );
		expect( heartbeat.send()[ 'wp-refresh-post-lock' ].lock ).toBe( '200:7' );
	} );

	it( 'releases the lock when the tab is closed', async () => {
		const heartbeat = await mount();
		heartbeat.tick( { 'wp-refresh-post-lock': { new_lock: '200:7' } } );
		window.dispatchEvent( new Event( 'pagehide' ) );

		const [ url, data ] = navigator.sendBeacon.mock.calls[ 0 ];
		expect( url ).toBe( '/wp-admin/admin-ajax.php' );
		expect( Object.fromEntries( data ) ).toEqual( {
			action: 'wp-remove-post-lock',
			_wpnonce: 'n0nce',
			post_ID: '42',
			active_post_lock: '200:7',
		} );
	} );

	it( 'keeps the lock when leaving because the form is being saved', async () => {
		await mount();
		expect( submit( $( '#editor' ) ).defaultPrevented ).toBe( false );
		window.dispatchEvent( new Event( 'pagehide' ) );
		expect( navigator.sendBeacon ).not.toHaveBeenCalled();
	} );

	it( 'freezes the screen without losing what was typed when someone else takes over', async () => {
		const heartbeat = await mount();
		heartbeat.tick( { 'wp-refresh-post-lock': { lock_error: { name: 'Ana Pérez' } } } );

		expect( $( '#editor' ).inert ).toBe( true );
		expect( $( '#dentro' ).inert ).toBeFalsy();
		expect( $( '#editor input' ).value ).toBe( 'Texto sin guardar' );
		expect( $( '#evt-lock-owner' ).textContent ).toBe( 'Ana Pérez' );
		expect( $( 'dialog' ).showModal ).toHaveBeenCalledTimes( 1 );

		expect( submit( $( '#editor' ) ).defaultPrevented ).toBe( true );
		const cancel = new Event( 'cancel', { cancelable: true } );
		$( 'dialog' ).dispatchEvent( cancel );
		expect( cancel.defaultPrevented ).toBe( true );

		expect( heartbeat.send() ).toEqual( {} );
		heartbeat.tick( { 'wp-refresh-post-lock': { new_lock: '300:7' } } );
		window.dispatchEvent( new Event( 'pagehide' ) );
		expect( navigator.sendBeacon ).not.toHaveBeenCalled();
	} );

	it( 'opens the notice as a plain open dialog where showModal is missing', async () => {
		const heartbeat = await mount();
		$( 'dialog' ).showModal = undefined;
		heartbeat.tick( { 'wp-refresh-post-lock': { lock_error: { name: 'Ana' } } } );
		expect( $( 'dialog' ).hasAttribute( 'open' ) ).toBe( true );
	} );

	it( 'renews nothing while someone else holds the lock', async () => {
		await mount( LOCK.replace( 'data-lock="100:7"', '' ) );
		expect( window.wp.heartbeat.interval ).not.toHaveBeenCalled();
	} );
} );

describe( 'icon button tooltips', () => {
	it( 'creates one Bootstrap tooltip per button that has none yet', async () => {
		document.body.innerHTML = '<button id="a" data-bs-toggle="tooltip" title="Editar"></button><button id="b" data-bs-toggle="tooltip" title="Ver"></button>';
		const Tooltip = vi.fn();
		Tooltip.getInstance = ( node ) => ( 'b' === node.id ? {} : null );
		window.bootstrap = { Tooltip };
		await load();
		ready();
		expect( Tooltip ).toHaveBeenCalledTimes( 1 );
		expect( Tooltip ).toHaveBeenCalledWith( $( '#a' ) );
	} );
} );

describe( 'publish switch and auto-submitting lists', () => {
	it( 'submits the form when the switch is toggled and hides the fallback button', async () => {
		document.body.innerHTML = '<form><input type="checkbox" data-evt-switch><button class="evt-switch-boton">Publicar</button></form>';
		$( 'form' ).submit = vi.fn();
		await load();
		ready();
		expect( $( '.evt-switch-boton' ).hidden ).toBe( true );

		change( $( '[data-evt-switch]' ) );
		expect( $( '[data-evt-switch]' ).disabled ).toBe( true );
		expect( $( 'form' ).submit ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'submits the form when a data-evt-autoenvio list changes', async () => {
		document.body.innerHTML = '<form><select data-evt-autoenvio><option>1</option></select></form>';
		$( 'form' ).submit = vi.fn();
		await load();
		change( $( 'select' ) );
		expect( $( 'form' ).submit ).toHaveBeenCalledTimes( 1 );
	} );
} );

describe( 'event list filter', () => {
	async function mount() {
		document.body.innerHTML = `
			<input data-evt-filtro>
			<article data-evt-buscar="Jornadas de Innovación Educativa"></article>
			<article data-evt-buscar="Congreso de Lectura"></article>
			<p data-evt-filtro-vacio hidden>Ningún evento coincide.</p>`;
		await load();
	}

	function visible() {
		return Array.from( document.querySelectorAll( '[data-evt-buscar]' ) ).filter( ( el ) => ! el.hidden ).length;
	}

	it( 'hides what does not match, ignoring accents and case', async () => {
		await mount();
		type( $( '[data-evt-filtro]' ), ' INNOVACION ' );
		expect( visible() ).toBe( 1 );
		expect( $( '[data-evt-buscar]' ).hidden ).toBe( false );
		expect( $( '[data-evt-filtro-vacio]' ).hidden ).toBe( true );
	} );

	it( 'says so when nothing matches, and shows everything again when cleared', async () => {
		await mount();
		type( $( '[data-evt-filtro]' ), 'zzz' );
		expect( visible() ).toBe( 0 );
		expect( $( '[data-evt-filtro-vacio]' ).hidden ).toBe( false );

		type( $( '[data-evt-filtro]' ), '' );
		expect( visible() ).toBe( 2 );
		expect( $( '[data-evt-filtro-vacio]' ).hidden ).toBe( true );
	} );
} );

describe( 'side panel', () => {
	const PANEL = `
		<div class="evt-hoja">
			<div class="evt-cajon-fondo" data-evt-cerrar-cajon hidden></div>
			<section data-evt-cajon hidden>
				<a class="evt-cajon__cerrar" href="http://localhost:3000/panel" data-evt-cerrar-cajon>×</a>
				<input type="hidden" name="nonce">
				<input name="nombre">
			</section>
			<a href="?alta=1" data-evt-abrir-cajon>Añadir</a>
		</div>`;

	it( 'moves the panel and its backdrop to the end of the body', async () => {
		document.body.innerHTML = PANEL;
		await load();
		expect( document.body.lastElementChild ).toBe( $( '[data-evt-cajon]' ) );
		expect( $( '[data-evt-cajon]' ).previousElementSibling ).toBe( $( '.evt-cajon-fondo' ) );
	} );

	it( 'opens without reloading and focuses the first field', async () => {
		document.body.innerHTML = PANEL;
		await load();
		expect( click( $( '[data-evt-abrir-cajon]' ) ).defaultPrevented ).toBe( true );
		expect( $( '[data-evt-cajon]' ).hidden ).toBe( false );
		expect( $( '.evt-cajon-fondo' ).hidden ).toBe( false );
		expect( document.activeElement ).toBe( $( '[name="nombre"]' ) );
	} );

	it( 'slides out and leaves the address of the screen it came from', async () => {
		document.body.innerHTML = PANEL;
		await load();
		click( $( '[data-evt-abrir-cajon]' ) );
		vi.useFakeTimers();
		const replaceState = vi.spyOn( window.history, 'replaceState' );

		click( $( '.evt-cajon__cerrar' ) );
		expect( $( '[data-evt-cajon]' ).classList.contains( 'evt-cajon--saliendo' ) ).toBe( true );
		expect( $( '[data-evt-cajon]' ).hidden ).toBe( false );

		vi.advanceTimersByTime( 200 );
		expect( $( '[data-evt-cajon]' ).hidden ).toBe( true );
		expect( $( '.evt-cajon-fondo' ).hidden ).toBe( true );
		expect( replaceState ).toHaveBeenCalledWith( null, '', 'http://localhost:3000/panel' );
	} );

	it( 'closes with Escape, and Escape on a closed panel does nothing', async () => {
		document.body.innerHTML = PANEL;
		await load();
		vi.useFakeTimers();
		document.dispatchEvent( new KeyboardEvent( 'keydown', { key: 'Escape' } ) );
		expect( $( '[data-evt-cajon]' ).classList.contains( 'evt-cajon--saliendo' ) ).toBe( false );

		click( $( '[data-evt-abrir-cajon]' ) );
		document.dispatchEvent( new KeyboardEvent( 'keydown', { key: 'Enter' } ) );
		expect( $( '[data-evt-cajon]' ).classList.contains( 'evt-cajon--saliendo' ) ).toBe( false );
		document.dispatchEvent( new KeyboardEvent( 'keydown', { key: 'Escape' } ) );
		vi.advanceTimersByTime( 200 );
		expect( $( '[data-evt-cajon]' ).hidden ).toBe( true );
	} );
} );

describe( 'page editor frame', () => {
	function panels() {
		return document.querySelectorAll( '[data-evt-cajon]' );
	}

	it( 'opens the page editor in the side panel, without header or footer', async () => {
		document.body.innerHTML = `<section data-evt-cajon>viejo</section>
			<a class="evt-abre-marco" href="/gestion/?evt_pagina=5">Editar</a>`;
		await load();

		expect( click( $( 'a.evt-abre-marco' ) ).defaultPrevented ).toBe( true );
		expect( panels() ).toHaveLength( 1 );
		const panel = panels()[ 0 ];
		expect( panel.className ).toBe( 'evt-cajon evt-cajon--pagina' );
		expect( panel.hasAttribute( 'data-evt-cajon-edita' ) ).toBe( true );
		expect( panel.getAttribute( 'aria-label' ) ).toBe( 'Editar la página' );
		const src = new URL( panel.querySelector( 'iframe' ).src );
		expect( src.pathname ).toBe( '/gestion/' );
		expect( src.searchParams.get( 'evt_pagina' ) ).toBe( '5' );
		expect( src.searchParams.get( 'evt_marco' ) ).toBe( '1' );
		expect( panel.querySelector( '.evt-cajon__fuera' ) ).toBeNull();
	} );

	it( 'shows the public page as it is, with a link to open it in another tab', async () => {
		document.body.innerHTML = '<a class="evt-abre-vista" href="/evento/jornadas/">Ver</a>';
		await load();

		expect( click( $( 'a.evt-abre-vista' ) ).defaultPrevented ).toBe( true );
		const panel = panels()[ 0 ];
		expect( panel.className ).toBe( 'evt-cajon evt-cajon--vista' );
		expect( panel.hasAttribute( 'data-evt-cajon-edita' ) ).toBe( false );
		expect( panel.querySelector( 'iframe' ).src ).toBe( 'http://localhost:3000/evento/jornadas/' );
		const outside = panel.querySelector( '.evt-cajon__fuera' );
		expect( outside.target ).toBe( '_blank' );
		expect( outside.rel ).toBe( 'noopener' );
	} );

	it( 'leaves links to another site and modified clicks to the browser', async () => {
		document.body.innerHTML = `<a id="fuera" class="evt-abre-marco" href="https://example.org/pagina">Fuera</a>
			<a id="dentro" class="evt-abre-marco" href="/gestion/?evt_pagina=5">Editar</a>`;
		await load();
		// Records whether the script took the click, then stops jsdom from
		// following the link, which it cannot do.
		const taken = [];
		window.addEventListener( 'click', ( e ) => {
			taken.push( e.defaultPrevented );
			e.preventDefault();
		} );

		click( $( '#fuera' ) );
		click( $( '#dentro' ), { ctrlKey: true } );
		expect( taken ).toEqual( [ false, false ] );
		expect( panels() ).toHaveLength( 0 );
	} );

	it( 'opens a new page form in the frame with its fields in the address', async () => {
		document.body.innerHTML = `<form data-evt-marco action="/gestion/">
			<input name="evt_accion" value="nueva">
			<input name="evt_titulo" value="Programa">
		</form>`;
		await load();
		const event = new Event( 'submit', { bubbles: true, cancelable: true } );
		$( 'form' ).dispatchEvent( event );

		expect( event.defaultPrevented ).toBe( true );
		const src = new URL( panels()[ 0 ].querySelector( 'iframe' ).src );
		expect( src.searchParams.get( 'evt_accion' ) ).toBe( 'nueva' );
		expect( src.searchParams.get( 'evt_titulo' ) ).toBe( 'Programa' );
		expect( panels()[ 0 ].getAttribute( 'aria-label' ) ).toBe( 'Nueva página' );
	} );
} );

describe( 'unsaved changes bar', () => {
	async function mount() {
		document.body.innerHTML = `<form data-evt-cambios>
			<input name="nombre" value="Jornadas">
			<div data-evt-guardar>
				<span data-evt-guardar-estado></span>
				<button type="reset" data-evt-guardar-descartar hidden>Descartar</button>
			</div>
		</form>`;
		await load();
	}

	function leave() {
		const event = new Event( 'beforeunload', { cancelable: true } );
		window.dispatchEvent( event );
		return event;
	}

	it( 'warns about unsaved changes and asks before leaving', async () => {
		await mount();
		expect( leave().defaultPrevented ).toBe( false );

		type( $( '[name="nombre"]' ), 'Jornadas 2026' );
		expect( $( '[data-evt-guardar]' ).classList.contains( 'evt-guardar--sucio' ) ).toBe( true );
		expect( $( '[data-evt-guardar-estado]' ).textContent ).toBe( 'Hay cambios sin guardar' );
		expect( $( '[data-evt-guardar-descartar]' ).hidden ).toBe( false );
		expect( leave().defaultPrevented ).toBe( true );
	} );

	it( 'lets the page go once the form is being saved', async () => {
		await mount();
		type( $( '[name="nombre"]' ), 'Jornadas 2026' );
		$( 'form' ).dispatchEvent( new Event( 'submit', { bubbles: true, cancelable: true } ) );
		expect( leave().defaultPrevented ).toBe( false );
	} );

	it( 'goes back to clean after discarding, and announces it', async () => {
		await mount();
		const discarded = vi.fn();
		document.addEventListener( 'evt:descartado', discarded );
		change( $( '[name="nombre"]' ) );

		$( 'form' ).reset();
		await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
		expect( $( '[data-evt-guardar]' ).classList.contains( 'evt-guardar--sucio' ) ).toBe( false );
		expect( $( '[data-evt-guardar-estado]' ).textContent ).toBe( '' );
		expect( $( '[data-evt-guardar-descartar]' ).hidden ).toBe( true );
		expect( discarded ).toHaveBeenCalledTimes( 1 );
		expect( leave().defaultPrevented ).toBe( false );
	} );
} );

describe( 'registration questions', () => {
	function question( i, typeValue, isNew ) {
		return `<details class="evt-pregunta${ isNew ? ' evt-pregunta--nueva' : '' }">
			<label for="evt-q-l-${ i }">Rótulo</label><input id="evt-q-l-${ i }" name="evt_q_label[${ i }]">
			<select id="evt-q-t-${ i }" name="evt_q_type[${ i }]">
				<option value="text"${ 'text' === typeValue ? ' selected' : '' }>Texto</option>
				<option value="one"${ 'one' === typeValue ? ' selected' : '' }>Una opción</option>
			</select>
			<p data-evt-q-opciones="one many"><textarea id="evt-q-o-${ i }" name="evt_q_options[${ i }]"></textarea></p>
		</details>`;
	}

	async function mount() {
		document.body.innerHTML = `<div data-evt-preguntas>${ question( 0, 'text' ) }${ question( 1, 'one', true ) }</div>
			<button type="button" data-evt-pregunta-nueva hidden>Añadir otra pregunta</button>`;
		await load();
		ready();
	}

	it( 'shows the options field only for the types that have options', async () => {
		await mount();
		const [ first, second ] = document.querySelectorAll( '.evt-pregunta' );
		expect( first.querySelector( '[data-evt-q-opciones]' ).hidden ).toBe( true );
		expect( second.querySelector( '[data-evt-q-opciones]' ).hidden ).toBe( false );

		first.querySelector( 'select' ).value = 'one';
		change( first.querySelector( 'select' ) );
		expect( first.querySelector( '[data-evt-q-opciones]' ).hidden ).toBe( false );
	} );

	it( 'adds blank questions with the next indexes and focuses their label', async () => {
		await mount();
		const add = $( '[data-evt-pregunta-nueva]' );
		expect( add.hidden ).toBe( false );

		click( add );
		click( add );
		const rows = document.querySelectorAll( '.evt-pregunta' );
		expect( rows ).toHaveLength( 4 );
		const last = rows[ 3 ];
		expect( last.open ).toBe( true );
		expect( last.querySelector( 'input' ).name ).toBe( 'evt_q_label[3]' );
		expect( last.querySelector( 'input' ).id ).toBe( 'evt-q-l-3' );
		expect( last.querySelector( 'label' ).getAttribute( 'for' ) ).toBe( 'evt-q-l-3' );
		expect( last.querySelector( 'textarea' ).name ).toBe( 'evt_q_options[3]' );
		expect( rows[ 2 ].querySelector( 'select' ).name ).toBe( 'evt_q_type[2]' );
		expect( document.activeElement ).toBe( last.querySelector( 'input' ) );
	} );
} );

describe( 'scope tree', () => {
	it( 'opens only the branches with something checked, and toggles them', async () => {
		document.body.innerHTML = `<ul class="evt-arbol">
			<li class="evt-arbol__rama" id="con"><div class="evt-arbol__fila"><button type="button" aria-expanded="true" hidden data-evt-arbol-plegar></button></div>
				<ul><li><input type="checkbox" checked></li></ul></li>
			<li class="evt-arbol__rama" id="sin"><div class="evt-arbol__fila"><button type="button" aria-expanded="true" hidden data-evt-arbol-plegar></button></div>
				<ul><li><input type="checkbox"></li></ul></li>
		</ul>`;
		await load();
		ready();

		const withChecked = $( '#con button' );
		const without = $( '#sin button' );
		expect( withChecked.hidden ).toBe( false );
		expect( withChecked.getAttribute( 'aria-expanded' ) ).toBe( 'true' );
		expect( $( '#con > ul' ).hidden ).toBe( false );
		expect( without.getAttribute( 'aria-expanded' ) ).toBe( 'false' );
		expect( $( '#sin > ul' ).hidden ).toBe( true );

		click( without );
		expect( without.getAttribute( 'aria-expanded' ) ).toBe( 'true' );
		expect( $( '#sin > ul' ).hidden ).toBe( false );
	} );
} );

describe( 'repeated rows', () => {
	it( 'adds an empty copy of the last row with the next index', async () => {
		document.body.innerHTML = `<div class="evt-form-campo">
			<div data-evt-filas>
				<div data-evt-fila>
					<label for="evt-cp-c-0">Coordenadas</label>
					<input id="evt-cp-c-0" name="evt_cp_coords[0]" value="28.4636, -16.2518">
				</div>
			</div>
			<button type="button" data-evt-filas-nueva hidden>Añadir otro punto</button>
		</div>`;
		await load();
		ready();
		const add = $( '[data-evt-filas-nueva]' );
		expect( add.hidden ).toBe( false );

		click( add );
		const rows = document.querySelectorAll( '[data-evt-fila]' );
		expect( rows ).toHaveLength( 2 );
		const input = rows[ 1 ].querySelector( 'input' );
		expect( input.name ).toBe( 'evt_cp_coords[1]' );
		expect( input.id ).toBe( 'evt-cp-c-1' );
		expect( input.value ).toBe( '' );
		expect( rows[ 1 ].querySelector( 'label' ).getAttribute( 'for' ) ).toBe( 'evt-cp-c-1' );
		expect( document.activeElement ).toBe( input );
		expect( rows[ 0 ].querySelector( 'input' ).value ).toBe( '28.4636, -16.2518' );
	} );

	it( 'leaves the button hidden when there is no row to copy', async () => {
		document.body.innerHTML = '<div class="evt-form-campo"><div data-evt-filas></div><button type="button" data-evt-filas-nueva hidden></button></div>';
		await load();
		ready();
		expect( $( '[data-evt-filas-nueva]' ).hidden ).toBe( true );
	} );
} );
