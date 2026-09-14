---
id: ADR-0029
title: "Botones de icono con bocadillo, y la publicación como interruptor"
status: Aceptada
date: 2026-09-14
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0014, ADR-0015, ADR-0016, ADR-0018, ADR-0022]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0029: Botones de icono con bocadillo, y la publicación como interruptor

## Estado

Aceptada (2026-09-14). Implementada en el listado de eventos y en los cuatro
paneles del taller: `Shell::icon()`, `View/PanelParts`, `.evt-icono` y
`.evt-switch` en `assets/css/evt-app.css`, y los apartados 5 y 6 de
`assets/js/evt-app.js`.

## Contexto

Las filas de estas pantallas llevan cuatro o cinco acciones —editar, ver,
subir, bajar, borrar— y con el rótulo escrito no caben: la columna se parte en
dos líneas y la tabla se lee peor cuanto más se puede hacer en ella.

La persona usuaria lo pidió con estas palabras: **botones de icono con
bocadillo, la papelera para borrar y el lápiz para editar**. Y para el estado de publicación, **un
interruptor**, porque «está más claro».

Hasta ahora el listado de eventos no tenía ni un botón —solo el título
enlazado—, la publicación era una etiqueta de color que no se podía tocar, y el
taller mezclaba flechas de texto (`↑`, `↓`) con botones escritos.

## Problema

¿Con qué se pintan las acciones de una fila, y cómo se cambia el estado de
publicación?

## Factores de decisión

- **Un icono no dice qué hace.** Quien no lo reconozca tiene que poder
  averiguarlo, y quien navegue con lector de pantalla, oírlo.
- **El aplicativo se entiende sin Bootstrap**
  ([ADR-0022](ADR-0022-la-pagina-de-evento-se-pinta-entera.md)): Bootstrap
  mejora la pantalla, no la sostiene.
- **Sin JavaScript todo se sigue pudiendo hacer**, que es la regla de las
  confirmaciones ([ADR-0016](ADR-0016-borrar-es-enviar-a-la-papelera.md)).
- **Toda mutación va por POST con nonce**, también la que se dispara sola.
- **El color no puede ser el único indicador**, igual que en el recuadro de
  administración ([ADR-0014](ADR-0014-css-del-area-javascript-de-administracion.md)).

## Alternativas consideradas

### Opción 1: seguir con los rótulos escritos

- Pros: no hay nada que explicar; cero código.
- Contras: cinco acciones escritas no caben en una columna de tabla y empujan
  el resto de las columnas. Es lo que había y es lo que se pidió cambiar.
  Descartada.

### Opción 2: la tipografía de iconos de Bootstrap

`bootstrap-icons` ya se carga desde el CDN
([ADR-0015](ADR-0015-librerias-de-terceros-desde-cdn-con-sri.md)), así que
`<i class="bi bi-pencil">` sale gratis.

- Pros: cero bytes propios y un catálogo enorme.
- Contras: **si la tipografía no llega, el botón se queda vacío.** Un botón sin
  nada dentro no es un botón degradado: es un botón perdido, y las acciones de
  una fila incluyen borrar. El aplicativo está escrito para seguir
  entendiéndose sin Bootstrap, y esto lo rompería justo donde más duele.
  Descartada.

### Opción 3: iconos en línea, con el texto al lado para quien lo necesite — ELEGIDA

- Pros: llegan siempre, heredan el color del botón con `currentColor` y no
  añaden ninguna petición.
- Contras: hay que dibujarlos y mantenerlos aquí; son seis y no hay catálogo.

## Decisión

### 1. Los iconos van en línea, y son seis

`Shell::icon( $nombre )` devuelve el SVG: **lápiz** (editar), **ojo** (ver),
**papelera** (enviar a la papelera), **flecha arriba** y **flecha abajo**
(orden) y **flecha que vuelve** (restaurar). Todos con `aria-hidden`, porque lo
que dice qué hace el botón es su texto.

### 2. Cada botón dice lo que hace, tres veces

| Para quién | Cómo |
|---|---|
| Quien mira | El icono |
| Quien duda | El **bocadillo**: `title` + `data-bs-toggle="tooltip"`. Con Bootstrap sale el suyo; sin Bootstrap, el del navegador. **Nunca se queda sin ninguno** |
| Quien no mira | Un `.screen-reader-text` dentro del botón con la acción escrita: «Enviar a la papelera», «Previsualizar esta página, que está en borrador» |

El objetivo táctil no baja de **36 px**, que es lo que se acierta con el dedo.

### 3. La publicación es un interruptor

Una casilla con pista y bolita, el rótulo **escrito al lado** —«Publicado» /
«Borrador»— y el formulario detrás. Se usa igual en el listado de eventos y en
las páginas de un evento.

- **Con JavaScript**, cambiarla envía el formulario: se toca y pasa algo.
- **Sin JavaScript**, al lado queda un botón normal que hace lo mismo. Se
  esconde **desde el guion** y no desde el CSS, a propósito: si el guion no
  llega, el botón se ve.
- El rótulo está escrito porque **el color y la posición no pueden ser el único
  indicador**, y el foco se dibuja sobre la pista para que con teclado se vea
  dónde está.

Publicar un evento **no publica sus páginas**: cada una tiene su estado y su
interruptor. Es lo que deja enseñar la portada de un evento con una sección
todavía sin terminar.

### 4. Un borrador también se mira

El botón del ojo **sale siempre**, también en lo que está en borrador: estando
dentro y con permiso para editarlo, WordPress lo sirve en previsualización. Lo
que cambia es la dirección —`get_preview_post_link()` en vez del enlace
permanente— y el texto del bocadillo, que dice que es una previsualización.

Lo único sin ojo es lo que está en la papelera: primero se restaura.

## Consecuencias

### Positivas

- **Las cinco acciones caben en una línea** y la tabla se lee.
- **Publicar y despublicar se hace desde donde se ve el estado**, que antes no
  se podía hacer en ninguna pantalla del aplicativo: había que ir al escritorio
  de WordPress.
- La convención es una sola pieza —`PanelParts::action()` y
  `PanelParts::icon_link()`— y no seis copias que se separan a la tercera
  corrección.
- **Nada depende de que llegue una librería.** Sin Bootstrap hay icono y
  `title`; sin JavaScript hay botón.

### Negativas

- **Un icono se reconoce o no se reconoce**, y el bocadillo tarda en salir.
  Para quien no reconozca el ojo, la primera vez es peor que la palabra «Ver».
  Se acepta porque la alternativa era que las acciones no cupieran.
- **Seis SVG que mantener aquí**, sin catálogo detrás. Cuando haga falta el
  séptimo hay que dibujarlo.
- **El interruptor se envía al tocarlo**, sin confirmación. Es reversible con
  un segundo toque y no destruye nada, pero despublicar un evento en marcha se
  nota fuera. Si alguna vez molesta, la confirmación de SweetAlert2 ya está
  cableada y se le pone encima.

### Neutras

- Los iconos pasan por `wp_kses()` con una lista de dos etiquetas aunque los
  escriba el propio aplicativo: deja la regla de «toda la salida escapada» sin
  excepciones que alguien tenga que recordar.
- La flecha de bajar del último elemento y la de subir del primero se pintan
  **apagadas**, no se esconden: una fila con menos botones que la de arriba se
  lee como una fila distinta.
