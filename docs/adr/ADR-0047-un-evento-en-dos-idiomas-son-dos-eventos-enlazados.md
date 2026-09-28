---
id: ADR-0047
title: "Un evento en dos idiomas son dos eventos enlazados"
status: Propuesta
date: 2026-09-28
related:
  issues: []
  prs: []
  sdds: []
  adrs: [ADR-0019, ADR-0031, ADR-0032, ADR-0042, ADR-0044]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0047: Un evento en dos idiomas son dos eventos enlazados

## Estado

Propuesta. **No hay código todavía.** Esta ADR fija la dirección para cuando
llegue el primer evento que la necesite, y se revisa entonces con el caso
delante.

## Contexto

En la reunión de demostración del 2026-09-28 salió un caso que ya ha pasado y
que se da por seguro que volverá: **un evento anunciado a la vez en castellano
y en inglés**, con las mismas secciones en los dos idiomas. Aquella vez se
resolvió a mano y con dificultad. Cómo quedó migrado no se ha mirado todavía, y
es lo primero que hay que hacer antes de pasar esta ADR a Aceptada.

Hoy el aplicativo no sabe nada de idiomas:

- **El documento** se escribe con `language_attributes()`
  (`src/Evt/PublicFront/EventLayout.php`), así que lleva el idioma del sitio
  entero. Una página en inglés se anuncia como castellano, y un lector de
  pantalla la lee con la voz equivocada.
- **Los rótulos fijos de la página pública** están en castellano en el
  código: «Inicio» en el menú (`EventView::nav()`), «Descargar programa»
  (`Block/ProgrammeBlock.php`), los textos por defecto de las tarjetas
  (`EventView::DEFAULT_INTRO`), el núcleo del formulario de inscripción
  ([ADR-0031](ADR-0031-el-formulario-de-inscripcion-es-nucleo-fijo-mas-preguntas.md)).
  No se han contado uno a uno.
- **Una sección tiene un tipo** que no se cambia
  ([ADR-0019](ADR-0019-el-tipo-de-pagina-se-elige-al-crear.md)) y **una
  dirección** que no se rompe
  ([ADR-0042](ADR-0042-los-eventos-conservan-sus-url-y-pintan-su-programa.md)).
- **Una inscripción cuelga de su evento**
  ([ADR-0032](ADR-0032-la-inscripcion-es-un-contenido-del-evento.md)), igual que
  las preguntas del formulario, los talleres y su aforo.

El sitio de destino no admite plugins nuevos: solo lo que se pega en Code
Snippets. Un plugin de traducción no es una opción que dependa de este
repositorio.

## Problema

¿Cómo se publica un mismo evento en dos idiomas, cada página con su idioma
declarado y su dirección propia, sin partir en dos las inscripciones ni
convertir cada pantalla del aplicativo en una pantalla bilingüe?

## Factores de decisión

- **Frecuencia:** es raro, uno o dos eventos por curso. Lo que se haga no
  puede encarecer los otros cien.
- **Inscripciones:** una sola lista de inscripciones y un solo aforo por
  taller. Dos listas son dos aforos, y el taller se llena dos veces.
- **Accesibilidad:** cada página declara el idioma en el que está escrita.
- **Direcciones:** cada versión tiene su dirección y se puede compartir.
- **Quien organiza** tiene que entender qué está editando sin saber nada de
  traducciones.
- **Sin plugins nuevos** en el sitio de destino.

## Alternativas consideradas

### Opción 1: un plugin de traducción (Polylang, WPML…)

- Pros: resuelve el problema entero, rótulos incluidos, y lo mantiene otro.
- Contras: el sitio de destino no admite instalarlo desde aquí. Además
  reinterpreta las consultas de todos los contenidos, y el acotado por área y
  el bloqueo de edición habría que volver a probarlos con él delante. Es la
  opción más cara para un caso de uno o dos eventos al año.

### Opción 2: un solo evento con secciones duplicadas por idioma

Cada sección lleva una meta de idioma, el menú enseña solo las del idioma de la
página y un selector cambia de uno a otro.

- Pros: un evento y una lista de inscripciones.
- Contras: la portada, que es la raíz del evento, tendría que llevar dos
  títulos, dos entradillas, dos lemas… Eso es duplicar a mano campos que hoy
  son uno. Además, el menú, las tarjetas, el orden y el taller entero tendrían
  que filtrar por idioma. Es tocar casi cada pantalla por un caso raro.

### Opción 3: dos eventos enlazados — PROPUESTA

Cada versión es un evento completo, con sus secciones, su dirección y su
apariencia. Una meta los empareja y cada uno declara su idioma. **Las
inscripciones son solo del evento principal**: el otro no tiene formulario
propio, y su botón de inscripción lleva al del principal, que es lo que ya hace
hoy el campo «Dirección, si la inscripción es en otro sitio» (`evt_signup_url`).

- Pros:
  - No toca nada de lo que ya funciona para un evento normal. Todo es aditivo:
    - una meta de idioma, `evt_lang`, en la raíz (vacía es el idioma del
      sitio);
    - una meta de pareja, `evt_translation_of`, que apunta al evento
      principal;
    - en la página pública, el atributo `lang` del documento y un enlace
      «English / Español» en la barra, junto a «Acceder».
  - Una sola lista de inscripciones y un solo aforo por taller.
  - Cada versión se edita como cualquier evento, y su área la edita igual.
- Contras:
  - **Lo que es igual en los dos se mantiene dos veces**: fechas, sede,
    colores, carteles. Se puede suavizar con una acción «Crear la versión en
    otro idioma» que copie el evento con sus secciones, pero eso es una copia
    y no un enlace: lo que se cambie después hay que cambiarlo en los dos.
  - **Los ponentes y el programa** también son de cada evento. Duplicarlos
    es duplicar los talleres, y el aforo de un taller solo cuenta en el
    principal. Hay que decidir si la versión secundaria pinta el programa del
    principal (lectura) o el suyo. Esta ADR propone lo primero.
  - **Los rótulos fijos siguen en castellano** mientras no haya una tabla de
    cadenas por idioma para la página pública. Hace falta al menos la del
    inglés, y es trabajo aparte.
  - **La línea del tiempo** de la portada tiene que enseñar solo el evento
    principal, o saldría dos veces.

### Opción 4: no hacer nada y escribir los dos idiomas en la misma página

- Pros: ya funciona hoy, sin una línea de código.
- Contras: páginas el doble de largas, un idioma declarado para un texto que
  está en dos y ninguna dirección que compartir por idioma. Es lo que resultó
  difícil la última vez.

## Decisión

Se propone la **opción 3**, y se deja en Propuesta hasta que llegue el primer
evento que la necesite. Antes de aceptarla hay que:

1. Mirar cómo quedó migrado el evento bilingüe que ya existe, para que esto
   sirva también para él.
2. Decidir con ese caso delante si la versión secundaria pinta el programa y
   los ponentes del principal o los suyos.
3. Medir cuántos rótulos fijos tiene la página pública, para dimensionar la
   tabla de cadenas en inglés.

Mientras tanto, la opción 4 sigue disponible sin cambiar nada.

## Consecuencias

### Positivas

- Un evento normal no paga nada: todo lo nuevo es opcional y aditivo.
- Una sola lista de inscripciones y un solo aforo, como exige
  [ADR-0032](ADR-0032-la-inscripcion-es-un-contenido-del-evento.md).
- Cada página declara su idioma, y cada versión tiene su dirección.

### Negativas

- Lo común a las dos versiones se mantiene dos veces, o se copia una vez y
  luego se desincroniza.
- Hace falta una tabla de cadenas en inglés para los rótulos fijos de la
  página pública y del formulario de inscripción. Sin ella, la versión inglesa
  mezcla idiomas.
- La línea del tiempo, el listado de «Mis eventos» y la búsqueda tienen que
  saber que dos eventos son uno.

### Neutras

- Si algún día el sitio de destino admite un plugin de traducción, esta ADR se
  sustituye y las dos metas se migran a lo que ese plugin espere.
