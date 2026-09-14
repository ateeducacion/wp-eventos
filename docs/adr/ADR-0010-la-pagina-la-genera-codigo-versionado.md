---
id: ADR-0010
title: "La página la genera código versionado, no una plantilla guardada en la base de datos"
status: Aceptada
date: 2026-09-12
related:
  issues: []
  prs: []
  sdds: [SDD-0001, SDD-0002]
  adrs: [ADR-0001, ADR-0003, ADR-0008]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0010: La página la genera código versionado, no una plantilla guardada en la base de datos

## Estado

Aceptada (2026-09-12).

## Contexto

Hoy, el contenido de la página de un evento no lo escribe nadie.

La acción que crea la página desde el formulario del sistema que se sustituye
la guarda con el cuerpo **vacío** y delega todo el contenido en una plantilla
del propio gestor de formularios: un campo de texto que vive en la base de
datos y se edita desde el escritorio. La configuración de esa acción está en
el volcado del sistema anterior que se conserva en `.local/`.

Esa plantilla, medida sobre la copia de `.local/`:

| Magnitud | Valor |
|---|---|
| Tamaño de la plantilla | 42.165 caracteres |
| Bloques condicionales anidados | 193 |
| `et_pb_section` / `et_pb_row` / `et_pb_column` | 20 / 19 / 28 |
| `et_pb_text` / `et_pb_image` | 20 / 8 |
| Llamadas que insertan datos del formulario | 11 |

Ramifica sobre el campo «Tipo de página» —`Principal`, `programa`, `ponentes`,
`contacto`, `actividades`—, sobre los ajustes de diseño (28 condiciones de
fuente, 19 de tamaño, las de color), sobre las dos sedes y sobre las opciones
de inscripción, patrocinio y protección de datos. Es un motor de plantillas
escrito dentro de un campo de texto.

Un cambio ahí **no tiene diff, ni revisión, ni prueba, ni vuelta atrás**. Se
guarda y ya está publicado en las 163 páginas del subsitio.

Y tiene una segunda consecuencia, esta medible en el contenido. Como la
plantilla interpola una sola vez, en el momento de crear o actualizar la
página, los datos acaban copiados dentro del contenido: la página «Programa»
de un evento guarda en su cuerpo una llamada que lleva escritos como atributos
la sede y las fechas, tal y como estaban el día en que se creó.

La sede y la fecha viven a la vez en la entrada del formulario y en el
contenido de la página. **Cambiar la fecha en el formulario no cambia lo que
se ve.**

## Problema

¿Dónde vive la plantilla que convierte los datos de un evento en la página
que ve el público, y quién puede tocarla?

## Factores de decisión

- **Revisabilidad**: un cambio en la plantilla tiene que poder leerse antes
  de aplicarse.
- **Vuelta atrás**: hoy no existe. Guardar la plantilla es publicar.
- **Prueba**: hoy la única prueba es abrir la página y mirarla.
- **El dato en un solo sitio**: que la fecha del evento sea la fecha que se
  ve.
- **Quién la toca hoy**: hay personas sin PHP que ajustan la plantilla desde
  el admin. Es una capacidad real y la decisión se la quita.
- **el tema**: el tema es la versión que tenga el tema y lo que hay en las páginas son sus
  shortcodes. Lo que se decida tiene que convivir con eso.

## Alternativas consideradas

### Opción 1: seguir con la plantilla del sistema anterior

- Pros: no cuesta nada, funciona, quien la mantiene sabe hacerlo, y el
  aplicativo nuevo se ahorra reescribir 42 KB de plantilla.
- Contras: 193 bloques condicionales anidados que son el punto único de fallo
  de las 163 páginas, sin diff, sin revisión, sin test y sin vuelta atrás. El
  dato duplicado no es un riesgo futuro: ya está duplicado. Descartada.

### Opción 2: la plantilla sigue mandando, pero se versiona una copia del HTML

- Pros: hay algo que diferenciar.
- Contras: dos copias que se desincronizan en la primera edición desde el
  admin, y nada que lo detecte. Es exactamente lo que ya son las plantillas
  volcadas en `.local/`: una foto fechada, útil para leer, inútil como fuente
  de verdad. Documenta el problema en vez de arreglarlo. Descartada.

### Opción 3: el contenido lo escribe una persona y el resto lo pinta código PHP versionado

- Pros: diff, revisión, test y `git revert`. El código lee el dato de donde
  está guardado, así que deja de haber copias. Es el mismo mecanismo que ya
  usa todo lo demás del aplicativo.
- Contras: hay que reescribir en PHP lo que la plantilla hace, y quien no
  escriba PHP deja de poder tocarlo.

### Opción 4: un constructor visual (plantilla del tema, patrón sincronizado)

- Pros: quien hoy retoca la plantilla podría retocarla con una interfaz
  parecida.
- Contras: vuelve a poner la plantilla dentro de la base de datos, sin diff y
  sin vuelta atrás — el mismo problema con otro nombre — y ata el aplicativo
  al tema activo. Descartada.

## Decisión

**Haremos la opción 3.**

1. **El cuerpo de la página lo escribe una persona en el editor de
   WordPress.** El CPT lo soporta ya: `'supports' => array( 'title',
   'editor', … )` en `src/Evt/PostType/EventPostType.php`. Lo que hoy es el
   campo «Contenido» del formulario pasa a ser `post_content`, y con ello
   desaparece la razón por la que existe el fragmento de código que concede
   `unfiltered_html`.
2. **Lo que hoy interpola la plantilla alrededor del contenido** —la
   navegación del evento, el listado del programa, la ficha de ponentes, la
   cabecera— lo pinta código PHP que vive en `src/Evt/`, dado de alta en
   `src/Evt/load-order.php` como cualquier otro módulo, con su test y su sitio
   en el bundle.
3. **El código lee el dato de donde está guardado**: los meta del evento
   (las constantes de `EventMetaKeys`), los términos de sus taxonomías y
   los `evt_activity` y `evt_speaker` asociados. No hay atributos copiados en
   el contenido. Cambiar la fecha del evento cambia lo que se ve.
4. **Lo que una persona sin PHP puede cambiar deja de ser la plantilla y pasa
   a ser una lista corta de ajustes por evento** (cartel, logo, colores de
   cabecera, qué secciones se muestran). La lista es cerrada y vive en código,
   por la misma razón que los tipos de sección
   ([ADR-0004](ADR-0004-una-taxonomia-por-dimension.md)).

Esta ADR fija **dónde vive la plantilla**, no qué pinta. A 2026-09-12
`src/Evt/load-order.php` tiene doce ficheros y ninguno es de
presentación: el módulo que releva a la plantilla del sistema anterior está
por escribir, y su alcance —qué secciones, con qué ajustes— se detalla en
[SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md). Tampoco decide si el
HTML que se emita seguirá siendo shortcodes del tema o dejará de serlo; eso
depende del tema con el que se despliegue y no está resuelto.

## Consecuencias

### Positivas

- Un cambio en la plantilla es un diff que alguien lee antes de que se
  publique, con test y con `git revert` + `make bundle && make sync-snippets`
  como vuelta atrás.
- El dato deja de estar en dos sitios. La discrepancia entre la fecha del
  formulario y la fecha impresa en la página no puede volver a ocurrir por
  construcción.
- Se puede apagar la plantilla el día que muera el formulario del sistema
  anterior, y con ella un motor de plantillas de 42 KB que ningún control de
  versiones ha visto nunca.
- El contenido pasa por el editor de WordPress con las capacidades normales,
  lo que permite retirar la concesión de `unfiltered_html`.

### Negativas

- **Se pierde el retoque desde el admin, y hoy alguien lo usa.** Quien abre la
  plantilla y cambia un color, el texto de una cabecera o el orden de dos
  secciones lo hace sin pedirle permiso a nadie y lo ve publicado en el
  momento. A partir de esta decisión, ese mismo cambio es una rama, un PR, la
  CI de [ADR-0011](ADR-0011-ci-y-politica-de-pruebas.md), una revisión y un
  `make bundle && make sync-snippets`. Para quien no escribe PHP, la respuesta
  pasa de «lo hago yo» a «lo pido». Eso es una pérdida real de autonomía
  y no la compensa del todo lo que se ofrece a cambio.
- **Lo que se ofrece a cambio es más estrecho a propósito**: un conjunto
  acotado de ajustes por evento en lugar de una plantilla libre. Un ajuste que
  no esté en la lista no se puede hacer desde el admin, y ampliar la lista es
  tocar código. La lista todavía no está escrita, así que a fecha de hoy no se
  puede prometer que cubra lo que la plantilla actual permite.
- **Hay que leer la plantilla entera para portarla.** Es el único sitio donde
  está escrito qué hace, son 42.165 caracteres y 193 condiciones anidadas, y
  nadie la ha leído completa. El esfuerzo de esta decisión está casi todo ahí,
  y no está estimado.
- el tema queda sin resolver. Las páginas actuales son shortcodes del tema y los
  eventos migrados los conservan
  ([ADR-0008](ADR-0008-migracion-de-los-eventos-historicos.md)); el código
  nuevo tendrá que decidir si los emite, y esa decisión ata o desata el
  aplicativo del tema.

### Neutras

- Los eventos históricos no se repintan: ADR-0008 congela su contenido tal y
  como la plantilla lo dejó. Durante bastante tiempo convivirán páginas
  generadas por la plantilla vieja y páginas generadas por código.
- La plantilla sigue viva mientras viva el formulario que la invoca. Esta
  decisión no la apaga: decide que el aplicativo nuevo no la usa.
- El volcado de `.local/` conserva la plantilla actual como material de
  lectura. No se versiona: es investigación del sistema anterior.
