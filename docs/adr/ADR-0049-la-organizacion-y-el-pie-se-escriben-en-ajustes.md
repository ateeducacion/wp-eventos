---
id: ADR-0049
title: "La organización y los enlaces del pie se escriben en «Ajustes»"
status: Propuesta
date: 2026-09-29
related:
  issues: []
  prs: []
  sdds: []
  adrs: [ADR-0020, ADR-0030, ADR-0044]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0049: La organización y los enlaces del pie se escriben en «Ajustes»

## Estado

Propuesta

## Contexto

El armazón de las páginas —la cabecera del aplicativo y el pie de las páginas
públicas— sale de `EventChrome::chrome()`
(`src/Evt/PublicFront/View/EventChrome.php`), vacío por defecto y rellenado por
quien despliega con el filtro `evt_chrome`
([ADR-0030](ADR-0030-el-repositorio-se-publica-sin-nada-de-nadie.md)). Dos de
sus claves son las que se cambian más a menudo y las que quien administra
espera ver en pantalla:

- `org`: el rótulo que sale arriba a la izquierda del aplicativo, junto a
  «Eventos» (`Shell::top()`), que suele ser la dirección general que publica.
- `footer_links`: los enlaces legales del pie (`EventChrome::footer()`).

Con el filtro como única vía, cambiar un enlace del pie es editar un snippet
de PHP. En el despliegue de hoy, el snippet que contesta al filtro no las pone,
y ni el pie ni el rótulo salen.

A la vez, los dos textos de protección de datos
([ADR-0020](ADR-0020-el-consentimiento-es-una-casilla.md)) se escribían en un
`<textarea>` de HTML a mano, en «Ajustes» y en la «Inscripción» del evento, y
son textos legales con negritas, listas y enlaces.

## Problema

¿Dónde escribe quien administra el nombre de la organización y los enlaces del
pie sin editar código, sin que el repositorio lleve los datos de nadie?

## Factores de decisión

- Que se pueda cambiar sin tocar PHP.
- ADR-0030: nada de una organización concreta en lo que se versiona.
- Que lo que ya contesta al filtro siga valiendo.

## Alternativas consideradas

### Opción 1: solo el filtro (lo de hoy)

| Pros | Contras |
|---|---|
| Nada que construir. | Cambiar un enlace es editar y subir un snippet. |
| | En pantalla no hay rastro de dónde se cambia: se busca en «Ajustes» y no está. |

### Opción 2: dos campos en «Ajustes», debajo del filtro (elegida)

| Pros | Contras |
|---|---|
| Se cambian en la pantalla donde se buscan. | Una sección más en «Ajustes» y dos opciones más en la base de datos. |
| Los datos viven en la base de datos del sitio, no en el repositorio: ADR-0030 se cumple igual. | Dos sitios donde puede estar el mismo dato; se resuelve con una regla fija: manda el filtro. |
| El filtro sigue funcionando sin cambios. | |

### Opción 3: todo el armazón en «Ajustes»

Logo, colores, aviso de cookies y analítica también. Se descarta por ahora:
esos datos cambian una vez por instalación, y el aviso de cookies y la
analítica cargan guiones de fuera, que es mejor que pasen por un snippet
revisado que por un campo.

## Decisión

- «Ajustes» lleva una sección **«Cabecera y pie de las páginas»** con el
  **nombre de la organización** (`evt_chrome_org`) y una tabla de **enlaces del
  pie** (`evt_chrome_footer_links`, lista de `{label, url}`), con los que hay y
  dos huecos más. Una fila sin texto o sin una dirección `http(s)` no se guarda.
- Son los **valores por defecto** de `EventChrome::chrome()`; el filtro
  `evt_chrome` va encima y **manda** si pone la misma clave.
- Los dos textos de protección de datos se escriben con el **editor visual de
  WordPress** (`wp_editor()`, sin botón de medios) en «Ajustes» y en la
  «Inscripción» del evento. Se guardan como el contenido de una entrada, sin
  `<p>`, y el formulario público los pinta con `wpautop()` y `wp_kses_post()`:
  los textos que ya tenían `<p>` se ven igual.

## Consecuencias

### Positivas

- El pie y el rótulo se cambian sin desplegar nada.
- Lo que ya contesta al filtro no cambia de comportamiento.
- Los textos legales se escriben con formato sin saber HTML.

### Negativas

- Si el filtro pone una clave, el campo de «Ajustes» no hace nada, y la pantalla
  solo lo avisa con una línea. Quien despliega tiene que elegir una de las dos
  vías para cada dato.
- Cambiar el formato de un texto de protección de datos cuenta como cambiar el
  texto: sube su versión (ADR-0020), aunque las palabras sean las mismas.
