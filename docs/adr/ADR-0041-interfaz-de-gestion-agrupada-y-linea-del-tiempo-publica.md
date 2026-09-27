---
id: ADR-0041
title: "La gestión se agrupa en un menú lateral y los eventos se publican en una línea del tiempo"
status: Propuesta
date: 2026-09-27
related:
  issues: []
  prs: []
  sdds: []
  adrs: [ADR-0018, ADR-0033, ADR-0039, ADR-0040]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0041: La gestión se agrupa en un menú lateral y los eventos se publican en una línea del tiempo

## Estado

Propuesta

## Contexto

La [ADR-0018](ADR-0018-el-taller-del-evento-es-una-sola-pantalla.md) organizó
el taller del evento en una sola pantalla con pestañas. Con las inscripciones
(ADR-0031 a ADR-0040) las pestañas llegaron a nueve en una fila: Páginas,
Ponentes, Programa, Talleres, Inscripción, Participantes, Ajustes, Apariencia y
Código.

Al recorrer la interfaz entera se vieron estos problemas:

- **Pestañas sin jerarquía.** Mezclan contenido, inscripción y configuración.
- **Talleres repite Programa.** Es una vista de solo lectura del mismo dato,
  ya que un taller es una actividad con plazas (ADR-0033).
- **Formularios de alta enormes.** En Ponentes y Programa ocupan media pantalla
  por encima de la lista que se viene a mirar.
- **Guardar queda muy abajo.** El botón está al final de páginas de 2.500 a
  3.200 px.
- **Lo irreversible, lo primero.** «Marcar como histórico», que no tiene vuelta
  atrás, abre la pestaña Ajustes.
- **La inscripción, repartida.** El botón de inscripción de la portada vive en
  Ajustes, separado del resto de la inscripción.
- **El listado sobra de filtros.** Tiene cinco filtros y cuatro cifras para
  áreas que tienen pocos eventos cada una.
- **No hay puerta pública.** Ninguna página enseña todos los eventos.

Hay un diseño revisado con la dirección del proyecto antes de escribir código.
El producto no tiene todavía una versión publicada, así que no hay que
conservar la interfaz anterior.

## Decisión

**Taller del evento:**

- Un **menú lateral en tres grupos** sustituye a las pestañas:
  - *Contenido*: Páginas, Ponentes, Programa y talleres;
  - *Inscripción*: Formulario y plazos, Participantes;
  - *Configuración*: Datos del evento, Apariencia, Código.
- **Talleres pasa a Programa.** Deja de ser pestaña. Cada taller enseña en su
  fila la barra de plazas ocupadas —el número del candado, ADR-0033—, y el
  filtro «Solo talleres» (`?solo=talleres`) deja solo los talleres.
- **Alta y edición en un panel lateral.** En Ponentes y Programa la lista va
  primero, y añadir o editar abre un panel lateral por la dirección
  (`?alta=1`, `?ficha=N`). Sin JavaScript se abre y se cierra igual. Un guardado
  fallido vuelve con el panel abierto y el error dentro.
- **Barra de guardar pegada abajo** en Datos, Formulario y plazos, Apariencia y
  Código. Con JavaScript dice «Hay cambios sin guardar», deja descartarlos y
  pide confirmación antes de salir de la página.
- **Formulario y plazos**:
  - arriba, un aviso de si la página acepta a alguien ahora mismo, y si no, por
    qué, con el mismo texto que lee quien la visita;
  - interruptores en lugar de casillas;
  - las preguntas plegadas, con un resumen;
  - la protección de datos y el botón de la portada, plegados. El botón pasa de
    Datos a esta pestaña.
- **«Marcar como histórico» va al final de Datos**, en una zona roja.
- **Apariencia en dos columnas**: los ajustes a la izquierda y la muestra, fija,
  a la derecha.

**Listado de eventos:**

- Solo dos filtros: el nombre, que filtra mientras se escribe e Intro busca en
  todas las páginas, y el ámbito.
- Cada evento lleva sus chapas: su estado por fechas, y además *Borrador* o
  *Histórico*.
- Se ve en **cuadrícula con el cartel** (por defecto) o en **lista**
  (`?evt_vista=lista`). Sin cartel, sale un bloque del color de la cabecera
  con el nombre.

**Página pública `/eventos/`** (shortcode `[evt_timeline]`, sin sesión y dentro
del tema):

- **Todos** los eventos publicados en una **línea del tiempo horizontal**, sin
  tope.
- Abre en el mes actual, con el anterior y el siguiente.
- Arrastrando hacia la derecha se llega a los pasados, históricos incluidos.
- Botones ‹ Hoy › para quien no arrastra.
- Los meses vacíos también salen, para que la línea se lea como tiempo.
- Septiembre rotula el curso escolar.
- Sin JavaScript es una tira que se desplaza como cualquier otra.

## Consecuencias

### Positivas

- Se ve de un vistazo qué parte del evento se está tocando, y los talleres se
  gestionan donde se crean.
- La lista que se viene a mirar está siempre arriba, y el formulario no la
  empuja fuera de la pantalla.
- Guardar está siempre a mano, y salir con cambios pendientes se avisa.
- La inscripción entera está en un sitio.
- Hay una puerta pública que enseña todos los eventos por fecha.

### Negativas

- **El panel lateral no conserva lo tecleado** si el guardado falla: vuelve
  abierto, con el error, pero vacío en el alta, que es lo que ya pasaba antes.
- **El aviso de cambios sin guardar depende de JavaScript.** Sin él, la barra
  es solo el botón.
- **El panel lateral se mueve al final del `<body>` con JavaScript**, porque la
  hoja del aplicativo se centra con `transform`. Sin JavaScript se abre dentro
  de la hoja.
- **Una línea del tiempo muy larga es mucho DOM.** Con pocos eventos por área no
  pesa. Si algún día pesa, se pagina por cursos.

### Neutras

- Las claves de los paneles no cambian (`ajustes` sigue siendo la de Datos).
  Desaparece `talleres`.
