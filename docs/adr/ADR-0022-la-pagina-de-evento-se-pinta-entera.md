---
id: ADR-0022
title: "La página pública de un evento se pinta entera, sin el tema"
status: Aceptada
date: 2026-09-13
related:
  issues: []
  prs: []
  sdds: [SDD-0001, SDD-0002]
  adrs: [ADR-0001, ADR-0008, ADR-0010, ADR-0014, ADR-0015]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0022: La página pública de un evento se pinta entera, sin el tema

## Estado

Aceptada (2026-09-13).

**Qué sustituye.** Sustituye la decisión de pintar la vista pública **dentro
de `the_content`**, dejando cabecera, pie y assets al tema. Esa decisión
**no estaba en ninguna ADR**: vivía únicamente en el docblock de
`src/Evt/PublicFront/EventView.php`, redactada así —«la página pública del
evento sí es del sitio —cabecera institucional, menú y pie del tema— y lo
único nuestro es lo que va dentro del artículo»—. Por eso esta ADR no lleva
`supersedes` a ningún ID: no hay ADR a la que ponerle `superseded_by`, y el
docblock se ha reescrito con la decisión de aquí. Se deja constancia expresa
para que nadie busque una ADR-fantasma.

Lo que sí cierra es un punto que la
[ADR-0010](ADR-0010-la-pagina-la-genera-codigo-versionado.md) dejó abierto con
todas las letras: «el tema queda sin resolver. […] el código nuevo tendrá que
decidir si los emite, y esa decisión ata o desata el aplicativo del tema».
Esta ADR lo desata. La ADR-0010 sigue vigente y no se toca.

## Contexto

La ADR-0010 decidió **dónde vive la plantilla** de la página de un evento: en
`src/Evt/`, versionada, y no dentro de una vista del sistema anterior. No
decidió **hasta dónde llega** esa plantilla. Al implementarla se eligió lo
conservador —un envoltorio dentro de `the_content`— y al medir la página real
esa elección no se sostuvo:

- **El pie institucional no es del tema.** Es un `<div class="pie">` escrito a
  mano dentro de un módulo de código del maquetador del tema, con sus dos enlaces y sus teclas
  de acceso. Al salir del tema no se pierde, porque nunca fue suyo: está
  transcrito en `src/Evt/PublicFront/View/EventChrome::footer()`.
- **La navegación entre secciones y las tarjetas de la portada tampoco.** Ya
  eran Bootstrap escrito a mano en las vistas del sistema anterior.
- **Lo único que aportaba el tema era anidamiento y peso**: los niveles de
  `.et_pb_*` para llegar al texto, su hoja, y las hojas de Content Views,
  table-sorter y del gestor de formularios, que esa página no usa.

Y el aplicativo ya sabe pintar un documento entero: `Shell::render_standalone()`
lo hace en `template_redirect` para las pantallas de gestión. Mantener dos
mecanismos —uno para las pantallas y otro para la vista pública— era la
complejidad que había que justificar, y no se justificaba.

## Problema

¿La página pública de un evento la pinta el tema con nuestro contenido dentro,
o la pintamos nosotros entera, doctype incluido?

## Factores de decisión

- **Que la página se vea igual sin depender del tema con el que se despliegue.**
  Hoy es la versión que tenga el tema; mañana, lo que decida el multisitio.
- **Peso y número de peticiones**, que en un sitio institucional con conexiones
  malas se nota más que cualquier otra cosa.
- **Los eventos históricos, que son contenido del maquetador, congelado** ([ADR-0008](ADR-0008-migracion-de-los-eventos-historicos.md))
  y se romperían si se les quita el maquetador.
- **Que el aspecto lo pueda cambiar quien organiza** sin pelearse con la
  especificidad de la hoja del tema.
- **Lo que el tema da gratis** y pasaría a ser nuestro: idioma, `<title>`,
  descripción, Open Graph, canónica, icono del sitio, consentimiento de
  cookies y analítica.

## Alternativas consideradas

### Opción 1: seguir dentro de `the_content` (lo que había)

El tema pinta el documento y nosotros devolvemos el cuerpo del artículo.

- A favor: cero responsabilidad sobre el `<head>`, el pie del sitio, las
  cookies y la analítica; el día que el tema cambie de aspecto, la página del
  evento cambia con él sin tocar nada.
- En contra: el aspecto de la página depende de un tema que no controlamos y
  que ni siquiera es el mismo en el wp-env que en producción; se arrastran las
  hojas de tres plugins que la página no usa; y, sobre todo, **es imposible
  garantizar el resultado**: cualquier regla del tema puede pisar la nuestra y
  la única defensa es `!important`, que es justo lo contrario de un aspecto
  configurable por token.

### Opción 2: plantilla de página del tema (`page-template`)

Registrar una plantilla en blanco y pedir que las páginas de evento la usen.

- A favor: se queda dentro del tema, así que el pie y la analítica siguen
  saliendo solos.
- En contra: es una plantilla **por tema**, y este aplicativo viaja en un Code
  Snippet ([ADR-0001](ADR-0001-repo-entorno-desarrollo-no-plugin.md)) que no
  puede añadir ficheros a un tema que no es suyo. Además hay que acordarse de
  asignarla a cada página; el aspecto pasaría a depender de un ajuste editorial.

### Opción 3: pintar el documento entero en `template_redirect` (elegida)

Lo mismo que ya hacen las pantallas del aplicativo.

- A favor: un solo mecanismo para todo el aplicativo; el documento es
  reproducible y testable de punta a punta; el peso es el que decidimos.
- En contra: nos hacemos responsables del documento entero, y de todo lo que
  el tema enganchaba en su `footer.php`.

## Decisión

**Haremos la opción 3.**

1. `EventView::render()` se engancha a `template_redirect` con prioridad 20 —la
   misma que el Shell— y sirve el documento que monta `EventLayout::render()`,
   desde `<!doctype html>` hasta `</html>`. Sale por `Shell::leave()`, que en
   los tests lanza `ExitSignal` en vez de terminar el proceso.
2. **Un evento con la meta `evt_legacy` se queda en el tema.** `takes_over()`
   devuelve `false` y esa página sigue el camino de siempre, con sus assets y
   sin que le toquemos nada. Es la misma estrategia que la ADR-0008: lo nuevo
   por el camino nuevo, lo viejo se respeta. Es una comprobación de una meta;
   el día que no quede un solo `evt_legacy` se borra y no queda rastro.
3. **El cuerpo son bloques con nombre.** `EventLayout::add_block( nombre,
   callable, prioridad )`; el armazón los recorre y envuelve cada uno en su
   `<section>`. Añadir el programa, los ponentes o los talleres no obliga a
   tocar el armazón, y un bloque que no tiene nada que decir no deja hueco.
4. **El aspecto son tokens**, escritos en un solo `<style id="evt-evento-tokens">`
   a partir de las metas del evento. Nada de colores en atributos `style`: un
   `style` en línea solo se pisa con `!important`.
5. **El orden de la cabecera es hoja → tokens → CSS a medida** (prioridades 9 y
   999 de `wp_head`), para que el CSS a medida de la persona sea siempre lo
   último que se lee ([ADR-0014](ADR-0014-css-del-area-javascript-de-administracion.md)).
6. **Lo que la página no usa se descarta** por la ruta desde la que se sirve
   —`/themes/`, `/et-cache/`, Content Views, table-sorter— y el CSS del gestor
   de formularios solo cuando la página no lleva formulario.
7. **Lo que el tema daba gratis lo escribimos nosotros**: idioma, `<title>`
   cuando no hay soporte de `title-tag`, descripción y Open Graph. La canónica
   y el icono del sitio siguen saliendo de `wp_head()`, que se mantiene.

## Cifras medidas

Medidas el **2026-09-13** en el wp-env de este repositorio (WordPress 7.1, tema
activo **Twenty Twenty-Five**, evento de demostración «III Jornadas de
Tecnología Educativa», ID 23, con Bootstrap 5.3.3 desde `node_modules`), con
Playwright sobre Chromium. El «antes» se obtiene marcando el mismo evento con
`evt_legacy`, que es literalmente el camino del tema; así las dos columnas
tienen el mismo contenido.

| | Camino del tema | Documento propio |
|---|---|---|
| KB del documento | 77,7 | **43,3** |
| Hojas `<link rel="stylesheet">` | 3 | **2** |
| `<style>` en línea | 24 | **8** |
| Guiones `<script src>` | 2 | **1** |
| `<script>` en línea | 5 | **2** |
| Nodos del DOM | 158 | **105** |
| Cabecera y pie del tema | sí | **no** |
| Pie institucional | no | **sí** |

**Honestidad sobre estas cifras, que es lo que las hace útiles:**

- El tema del wp-env **no es el de producción**, es Twenty Twenty-Five. La columna «camino
  del tema» es por tanto un **suelo**, no el «antes» de producción: el tema de producción pesa
  mucho más. La mejora real al desplegar será mayor que la de esta tabla, pero
  **no está medida** y aquí no se inventa.
- Las cifras de producción que circulan en el encargo —**75 KB, 10 hojas de
  estilo y 20 guiones**— no las he medido yo: vienen de la foto de producción
  del encargo. Puestas al lado de las nuestras, **43,3 KB, 2 hojas y 1 guion**,
  la comparación es orientativa, no experimental.
- Las 2 hojas y el 1 guion que quedan son **Bootstrap 5 y sus iconos**, que
  carga `snippets/bootstrap5.php` ([ADR-0015](ADR-0015-librerias-de-terceros-desde-cdn-con-sri.md)).
  De nuestro código no sale ni una petición: la hoja del evento viaja inlineada
  en el bundle.

**Responsive.** `document.documentElement.scrollWidth <= window.innerWidth` en
**320, 400, 768 y 1440 px**, y cero elementos cuyo borde derecho pase del ancho
de la ventana en los cuatro anchos. Sin ninguna media query que decida una
rejilla: son `repeat(auto-fit, minmax(min(230px, 100%), 1fr))`, que mide el
contenedor y no la ventana.

**Contraste.** Con el fondo del evento de demostración (`#12395b`) y el texto
que le calcula `EventChrome::readable_ink()` (`#ffffff`), el ratio real medido
sobre los colores computados por el navegador es **11,92:1** — muy por encima
del 4,5:1 que exige WCAG AA. La comprobación no es decorativa: si la
combinación elegida no llega, `readable_ink()` cambia la tinta a negro.

**Sin Bootstrap.** Bloqueadas las tres peticiones de Bootstrap y sus iconos, la
página **sigue siendo legible**: cero reglas de Bootstrap aplicadas, la
cabecera conserva su color, las tarjetas apilan, no aparece scroll horizontal y
el texto sigue ahí. Nuestra hoja no da por supuesta la rejilla de nadie.

**Un token basta.** Con `:root { --evt-fondo: #7b1e3a; }` en el CSS a medida
del evento —una línea— el fondo computado de la portada pasa a
`rgb(123, 30, 58)`, sin un solo `!important`. El orden de los `<style>` en la
cabecera queda `evt-evento-css` → `evt-evento-tokens` → `evt-custom-css`, que
es exactamente el que hace falta.

**Consola y registro.** Cero errores de consola en los cuatro anchos y
`wp-content/debug.log` de 0 bytes.

## Consecuencias

### Positivas

- La página se ve igual **independientemente del tema** con el que se
  despliegue el subsitio, que era el punto que la ADR-0010 dejó abierto.
- Un documento entero es **testable entero**: `tests/unit/test-event-layout.php`
  y `tests/unit/test-event-skeleton.php` comprueban el doctype, el idioma, el
  único `<h1>`, el enlace de saltar al contenido, el pie institucional, que el
  tema no llega a pintar y que un evento legacy sí se queda en él.
- El aspecto es un puñado de tokens en un sitio. Cambiar el color de un evento
  es una línea de CSS a medida, no una guerra de especificidad.
- Menos peso y menos peticiones, con las cifras de arriba.

### Negativas

- **Somos responsables del documento entero.** El `<title>`, la descripción,
  Open Graph, el idioma y la accesibilidad del esqueleto ya no los pone nadie
  por nosotros. Un fallo aquí no lo tapa el tema: sale publicado.
- **El consentimiento de cookies y la analítica dependen de dónde los enganche
  el sitio.** Si es en `wp_head` o `wp_footer`, siguen funcionando, porque las
  dos se llaman. Si estaban en el `footer.php` del tema, **desaparecen de las
  páginas de evento** y hay que reponerlos. Esto **no está comprobado contra
  producción** y es lo primero que hay que mirar al desplegar.
- **El pie institucional es ahora una transcripción nuestra.** El día que
  quien despliega cambie su aviso legal o su política de privacidad, hay que
  cambiarlo aquí y no se entera nadie automáticamente.
- **Dos caminos conviviendo** mientras queden eventos `evt_legacy`: el nuevo y
  el del tema. Es una `if` de una meta, pero es una bifurcación real que hay
  que recordar al depurar «por qué esta página se ve distinta».
- Si el sitio añade algo global en el `<body>` del tema —una barra de avisos,
  un banner institucional—, las páginas de evento no lo tendrán.

### Neutras

- `wp_head()` y `wp_footer()` se siguen llamando: los complementos que se
  enganchan ahí no se enteran del cambio.
- La barra de administración de WordPress sigue saliendo para quien tenga
  sesión, porque la pinta `wp_footer()`.
- Los eventos históricos no cambian en absoluto (ADR-0008).
