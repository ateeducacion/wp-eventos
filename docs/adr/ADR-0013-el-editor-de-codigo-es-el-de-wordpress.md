---
id: ADR-0013
title: "El editor de código es el que ya trae WordPress"
status: Aceptada
date: 2026-09-13
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0001, ADR-0010, ADR-0014, ADR-0015]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0013: El editor de código es el que ya trae WordPress

## Estado

Aceptada (2026-09-13).

## Contexto

El aplicativo estrena dos campos de código a medida por evento y por página
satélite, `evt_custom_css` y `evt_custom_js`
([ADR-0014](ADR-0014-css-del-area-javascript-de-administracion.md)). Un
campo de código no es un campo de texto: quien escribe CSS necesita ver dónde
se le ha quedado una llave sin cerrar, y quien escribe JavaScript, dónde falta
un paréntesis. Un `<textarea>` pelado no dice nada de eso hasta que la página
sale rota en producción.

La restricción que manda aquí es la de siempre:
[ADR-0001](ADR-0001-repo-entorno-desarrollo-no-plugin.md). Esto no es un
plugin. Todo el aplicativo viaja dentro de **un** snippet de Code Snippets, un
único fichero PHP de 375 KB que `build/pack-snippet.php` genera concatenando
`src/Evt/` y los ficheros de `assets/`. No hay carpeta en disco desde la que
servir un `.js`, no hay `plugins_url()` y no hay forma de encolar un fichero
propio. Una biblioteca de terceros solo tiene dos caminos: **inlinearla en el
bundle** —engordándolo con código ajeno que habría que actualizar a mano— o
**pedirla a un CDN**, que en este proyecto es el camino normal y no un problema:
jsDelivr con versión clavada y SRI, como se cargan Bootstrap 5 y sus iconos
([ADR-0015](ADR-0015-librerias-de-terceros-desde-cdn-con-sri.md)). Así que la
elección no se decide por ahí.

### Lo que ya hay en el sitio, medido

Comprobado por consola en el wp-env de este proyecto el 2026-09-13
(`npx wp-env run cli wp eval …`, WordPress 7.1):

```
WP 7.1
wp_enqueue_code_editor: SI
script wp-codemirror: registrado ver=5.65.20   -> /wp-includes/js/codemirror/codemirror.min.js
script code-editor:    registrado              -> /wp-admin/js/code-editor.js (deps: jquery, wp-codemirror, underscore)
script csslint:        registrado ver=1.0.5    -> /wp-includes/js/codemirror/csslint.js
script jshint:         registrado ver=2.9.5    -> /wp-includes/js/codemirror/fakejshint.js (deps: esprima)
style  wp-codemirror:  registrado ver=5.65.20
style  code-editor:    registrado
```

Es decir: CodeMirror 5.65.20, el envoltorio `wp.codeEditor` del núcleo, el
comprobador de CSS (CSSLint 1.0.5) y el de JavaScript (el envoltorio
`fakejshint` sobre Esprima, registrado con el número de versión histórico de
JSHint, 2.9.5) están **registrados y disponibles**, sin instalar nada. Es el
mismo editor del «CSS adicional» del personalizador y del editor de temas y
plugins.

Tamaño de esos ficheros, medido en el mismo contenedor:

| Fichero | En disco | Comprimido (gzip) |
|---|---:|---:|
| `codemirror.min.js` | 620.295 B | 197.636 B |
| `codemirror.min.css` | 16.488 B | 4.366 B |
| `csslint.js` | 374.004 B | 67.198 B |
| `fakejshint.js` + `esprima.js` | 1.280 B + 283.567 B | ~75 KB |

Abrir el campo de CSS cuesta unos **263 KB comprimidos** (1,0 MB sin
comprimir). No es poco, y se dice aquí y no en una nota al pie.

## Problema

¿Con qué editor se pintan los dos campos de código del aplicativo, dado que
todo tiene que caber en un Code Snippet?

## Factores de decisión

- **Cero bytes propios que empaquetar** (ADR-0001): el bundle ya son 375 KB y
  cada KB añadido es código que mantenemos nosotros.
- **Comprobación de errores.** Quien maqueta un evento no es siempre quien
  sabe depurar CSS. Un comprobador que marca la línea mala en el margen vale
  más que el coloreado.
- **Que la persona lo reconozca.** El personalizador de WordPress ya tiene un
  campo de «CSS adicional» con este mismo editor.
- **Cuánto cuesta mantener la dependencia.** Una versión que anclar, un SRI
  que rehacer en cada subida y un aviso de seguridad que nadie recibe cuestan
  más, a lo largo del tiempo, que los bytes que ahorran.
- **Degradación obligatoria.** WordPress deja apagar el resaltado de sintaxis
  en el perfil de cada persona. El campo tiene que seguir funcionando cuando
  está apagado.
- **Accesibilidad.** El campo tiene que seguir siendo un `<textarea>` con su
  `<label>` y su `aria-describedby` por debajo, se le monte encima lo que se
  le monte.

## Alternativas consideradas

### Opción 1: CodeMirror del núcleo, vía `wp_enqueue_code_editor()` — ELEGIDA

Se llama a `wp_enqueue_code_editor( array( 'type' => 'text/css' ) )`, se
serializan los ajustes que devuelve en un `data-` del `<textarea>` y un puñado
de líneas de `assets/js/evt-app.js` llaman a `wp.codeEditor.initialize()` por
cada campo.

- Pros: cero bytes en el bundle; CSSLint y JSHint incluidos y ya cableados por
  el núcleo al margen del editor; el mismo aspecto y los mismos atajos que el
  «CSS adicional»; los ficheros los sirve el propio sitio, sin salir a
  internet; WordPress se encarga de la versión de CodeMirror y de sus parches
  de seguridad; el `<textarea>` sigue ahí debajo, así que el formulario se
  envía igual con o sin editor.
- Contras: es CodeMirror 5, una rama que ya no evoluciona y de la que
  dependemos al ritmo del núcleo; son ~263 KB comprimidos para un campo que
  mucha gente no usará; y la API `wp.codeEditor` es de `wp-admin`, de modo que
  la usamos en el frontal —funciona, porque son scripts registrados en
  `wp-includes` y `wp-admin/js/code-editor.js`, pero no es su escenario
  habitual y hay que probarlo en el frontal, no en el escritorio.

### Opción 2: CodeJar desde un CDN

Una biblioteca de ~2 KB, `contenteditable` más resaltado, cargada con un
`<script src="https://cdn…">`.

- Pros: minúscula —**~2 KB frente a los ~250 KB comprimidos** de CodeMirror y
  sus comprobadores, más de cien veces menos—; API de tres funciones; nada que
  empaquetar. Y **cargarla así sería perfectamente aceptable** por la política
  del proyecto: versión clavada en la URL, `integrity`, `crossorigin`, copia en
  `node_modules` para los tests
  ([ADR-0015](ADR-0015-librerias-de-terceros-desde-cdn-con-sri.md)). Eso es
  exactamente lo que se hace con Bootstrap, así que **el CDN no es lo que la
  descarta**.
- Contras, y son los dos que deciden: **no trae ningún comprobador de errores**
  —CodeJar colorea, y para colorear necesita además Prism o Highlight.js—, que
  es la mitad del valor de este campo; y **no es un `<textarea>` sino un
  `contenteditable`**, así que hay que sincronizar el valor a mano en el envío y
  el campo deja de comportarse como un campo de formulario para quien navega con
  teclado o lector de pantalla. Descartada.

### Opción 3: CodeJar inlineado en el bundle

Lo mismo, pero pegando la biblioteca dentro de `assets/js/` para que el
empaquetador la meta en el snippet.

- Pros: sigue siendo pequeña; el bundle se queda como está, más 2 KB.
- Contras: metemos código de terceros en un fichero que se pega en un cuadro
  de texto de Code Snippets y que revisa una persona; actualizarlo es copiarlo
  a mano y no hay nada que avise de una vulnerabilidad. Sigue sin comprobador
  de errores, que es la mitad del valor del campo, y sigue siendo un
  `contenteditable`. Y no ahorra nada frente a la opción 1, porque los 263 KB
  del núcleo **no los servimos nosotros**: ya están en el sitio, y en la caché
  de cualquiera que haya abierto el personalizador. Descartada.

### Opción 4: un `<textarea>` pelado

- Pros: cero bytes, cero dependencias, cero API que se rompa; accesible por
  construcción.
- Contras: sin números de línea, sin coloreado y **sin ningún aviso de
  error**. Una llave sin cerrar en el CSS del evento se descubre mirando la
  página pública rota. Se descarta como opción principal, pero **no se tira**:
  es exactamente lo que se pinta cuando el editor no está disponible, y por eso
  el campo se escribe primero como `<textarea>` y el editor se le monta encima.

## Decisión

**Se usa el editor de código de WordPress: `wp_enqueue_code_editor()` más
`wp.codeEditor.initialize()`.** No se trae CodeJar ni ninguna otra biblioteca,
ni desde un CDN ni inlineada.

Por tres motivos, y ninguno es evitar el CDN:

1. **Ya está en el servidor.** Cero peticiones, cero bytes que servir, ninguna
   versión que anclar y ningún SRI que mantener. Los ~263 KB comprimidos que
   cuesta abrir el campo salen de `wp-includes` y de `wp-admin`, están cacheados
   en cualquier navegador que haya abierto el personalizador, y sus parches de
   seguridad llegan con los de WordPress sin que nadie tenga que acordarse. El
   coste de mantenimiento de esta dependencia es **cero**, y eso no lo iguala
   ninguna librería, venga de donde venga.
2. **Trae comprobadores de errores, y CodeJar no.** CSSLint 1.0.5 marca en el
   margen del editor de CSS y el envoltorio de JSHint hace lo propio en el de
   JavaScript, ya cableados por el núcleo. En un campo cuyo valor principal es
   avisar de la llave sin cerrar antes de que la página salga rota, esto es la
   mitad del motivo.
3. **Es el mismo editor del «CSS adicional» del personalizador.** Mismos
   colores, mismos atajos, mismo comportamiento del tabulador. Quien lo abre ya
   lo ha visto.

**Si el criterio fuera solo el peso, ganaba CodeJar**, y por más de cien veces.
El criterio es lo que tiene que hacer el campo, y ahí gana el del núcleo.

Se implementa en una sola clase, `Evt\PublicFront\CodeEditor`
(`src/Evt/PublicFront/CodeEditor.php`), con dos métodos:

- `CodeEditor::enqueue( $modo )` pide el editor para `text/css` o
  `text/javascript` y devuelve si va a estar.
- `CodeEditor::field( $args )` pinta el `<label>`, el `<textarea>` y la ayuda,
  y cuelga del `<textarea>` los atributos `data-evt-code` y
  `data-evt-code-settings` con los ajustes que devolvió el núcleo, serializados
  con `wp_json_encode()`.

El arranque está en `assets/js/evt-app.js`: recorre
`document.querySelectorAll('[data-evt-code]')` y llama a
`wp.codeEditor.initialize()` por cada uno. Un atributo por campo, y no una
variable global, para que dos campos en la misma pantalla —el de CSS y el de
JavaScript— no se pisen los ajustes.

### La degradación no es opcional

`wp_enqueue_code_editor()` devuelve `false` en dos casos
(`wp-includes/general-template.php:4244-4253` de WordPress 7.1):

1. la persona ha desactivado «Resaltado de sintaxis» en su perfil
   (`'false' === wp_get_current_user()->syntax_highlighting`);
2. el tipo pedido no tiene modo de CodeMirror.

En los dos, `CodeEditor::field()` pinta el `<textarea>` sin los atributos
`data-`, el arranque de JavaScript no lo encuentra y no pasa nada más: el campo
se escribe, se envía y se guarda igual. Debajo aparece una línea que lo
explica —«El resaltado de código está desactivado en su perfil…»— para que la
diferencia no se lea como una avería. El JavaScript también comprueba
`window.wp && window.wp.codeEditor` antes de tocar nada, que es el mismo caso
visto desde el otro lado.

## Consecuencias

### Positivas

- **El bundle no crece.** El aplicativo entero sigue siendo un fichero y no
  contiene ni una línea de código de terceros.
- **Los dos campos llevan comprobador de errores gratis**: CSSLint marca en el
  margen del editor de CSS y el envoltorio de JSHint hace lo propio en el de
  JavaScript. Es lo que más se va a agradecer, y ninguna de las alternativas
  ligeras lo daba.
- **La dependencia no cuesta nada de mantener.** Ninguna versión que anclar,
  ningún SRI que rehacer, ningún aviso de seguridad que vigilar: llega con
  WordPress.
- **Es un editor que la gente ya ha visto** en el «CSS adicional» del
  personalizador: mismos colores, mismos atajos, mismo comportamiento del
  tabulador.
- **Las actualizaciones de CodeMirror llegan con las de WordPress**, sin que
  nadie tenga que acordarse.
- **El campo sigue siendo un `<textarea>`** con su `<label>` y su ayuda
  asociada: quitar el editor no rompe ni el formulario ni la navegación por
  teclado.

### Negativas

- **Pesa.** Unos 263 KB comprimidos (1,0 MB en disco) para abrir la pestaña
  «Código», frente a los ~2 KB de CodeJar. Se acepta con dos matices, que no lo
  anulan: esos bytes **no salen de nuestro bundle** y ya están cacheados en
  cualquier navegador que haya abierto el personalizador; y la pestaña solo la
  ve quien administra, un puñado de personas, no la multitud que entra a leer.
- **Es CodeMirror 5**, una rama congelada. El día que el núcleo salte a
  CodeMirror 6 o a otra cosa, `wp.codeEditor` cambiará y habrá que revisar
  `CodeEditor` y el arranque de `evt-app.js`. Es una dependencia del núcleo, que
  es la mejor clase de dependencia que podemos tener aquí, pero es una
  dependencia.
- **`wp.codeEditor` vive en `wp-admin`** y nosotros lo usamos en pantallas del
  frontal. Funciona —los scripts están registrados y `code-editor.js` no exige
  estar en el escritorio—, pero no es su terreno habitual: cualquier prueba
  tiene que hacerse en el frontal, y una versión futura de WordPress podría
  atar esos scripts a la administración.
- **Hay dos caminos que mantener**, el del editor y el del `<textarea>` pelado,
  y el segundo es el que nadie prueba. Se prueba: el resaltado se apaga en el
  perfil y se comprueba que el campo sigue guardando.

### Neutras

- CSSLint 1.0.5 y el envoltorio de JSHint son antiguos y avisan de cosas
  discutibles. Son avisos, no bloqueos: nada impide guardar código que el
  comprobador señala.
- El `jshint` que WordPress registra es en realidad `fakejshint.js` sobre
  Esprima, aunque el número de versión del registro siga diciendo 2.9.5. Para
  lo que aquí se necesita —marcar errores de sintaxis— da igual, pero conviene
  no buscar en la documentación de JSHint el porqué de un aviso concreto.
- La decisión no afecta al contenido de la página, que se sigue escribiendo en
  el editor de WordPress
  ([ADR-0010](ADR-0010-la-pagina-la-genera-codigo-versionado.md)). Esto es solo
  el campo de código.
