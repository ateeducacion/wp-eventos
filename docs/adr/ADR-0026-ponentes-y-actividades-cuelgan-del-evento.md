---
id: ADR-0026
title: "Los ponentes y las actividades cuelgan del evento por post_parent"
status: Aceptada
date: 2026-09-14
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0003, ADR-0006, ADR-0017, ADR-0018, ADR-0021, ADR-0024]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0026: Los ponentes y las actividades cuelgan del evento por `post_parent`

## Estado

Aceptada (2026-09-14). Implementada: `PublicFront/Programme`,
`Meta/ProgrammeMetaKeys`, `Meta/ProgrammeMetaRegistration`,
`Domain/ActivityInput` y los paneles «Ponentes», «Programa» y «Talleres» del
taller.

## Contexto

Al construir las cuatro pestañas que faltaban
([ADR-0018](ADR-0018-el-taller-del-evento-es-una-sola-pantalla.md)) hubo que
contestar una pregunta que las ADR anteriores habían dejado abierta a
propósito: **cómo sabe un ponente a qué evento pertenece.**

Los tres CPT existían ya —`evt_event`, `evt_speaker`, `evt_activity`— pero los
dos últimos estaban sueltos: se acotaban por su propio término de `evt_area` y
no tenían ninguna relación con un evento. Con eso, ni la pantalla de ponentes
de un evento se puede construir, ni la parrilla del programa, ni el cierre por
histórico les alcanza.

Y había un antecedente escrito **dentro del propio código**, en el docblock de
`EventAccess::is_archived()`:

> «Lo mismo valdría para un ponente o una actividad colgados de un evento, si
> algún día los cuelgan: hoy no lo están, se acotan por área y `root_id()`
> devuelve el propio post.»

## Problema

¿Cómo se relaciona un ponente o una actividad con su evento, y qué hay que
escribir para que el acotado por área y el cierre por histórico les alcancen?

## Factores de decisión

- **Una sola regla de permisos.** El guardián es único
  ([ADR-0006](ADR-0006-el-area-es-un-ambito-no-un-rol.md)); una segunda forma
  de decidir el área es una segunda forma de equivocarse.
- **El cierre tiene que bajar solo.** La
  [ADR-0017](ADR-0017-el-estado-historico-cierra-la-edicion.md) prometió que en
  cuanto ponentes y actividades colgaran de un evento, `root_id()` los
  alcanzaría «sin tocar nada». O se cumple o se retira la promesa.
- **El ponente es de su evento**
  ([ADR-0021](ADR-0021-el-ponente-pertenece-a-su-evento.md)): no hay catálogo
  global, así que la relación es de uno a muchos y no de muchos a muchos.
- **Nada de capas nuevas.** Sin tabla propia: este repositorio no es un plugin
  y no tiene dónde correr un `dbDelta`
  ([ADR-0001](ADR-0001-repo-entorno-desarrollo-no-plugin.md)).

## Alternativas consideradas

### Opción 1: una meta `evt_event_id` en el ponente y en la actividad

Guardar el identificador del evento como meta y consultar por ella.

- Pros: explícito, y se lee de un vistazo en la base de datos.
- Contras: **`EventAccess` no lo sabría.** `post_areas()` y `is_archived()`
  preguntan por `root_id()`, que recorre `post_parent`; con una meta habría que
  enseñarles a mirar también ahí, en los dos sitios, y acordarse de hacerlo en
  el tercero que venga. Es una segunda regla de pertenencia conviviendo con la
  que ya hay. Descartada.

### Opción 2: una taxonomía «evento»

Un término por evento, y los ponentes etiquetados.

- Pros: consultas baratas, y un ponente podría estar en dos eventos.
- Contras: lo de «podría estar en dos eventos» es exactamente lo que la
  [ADR-0021](ADR-0021-el-ponente-pertenece-a-su-evento.md) decidió **no**
  hacer, medido: la repetición real está entre el 5 % y el 12 %, y en un evento
  terminado no se quiere que la ficha cambie sola. Además crea un vocabulario
  que crece con cada evento, que es la lección de `convocatoria`
  ([ADR-0004](ADR-0004-una-taxonomia-por-dimension.md)). Descartada.

### Opción 3: `post_parent`, como las páginas satélite — ELEGIDA

El mismo mecanismo que ya usa el evento con sus secciones
([ADR-0003](ADR-0003-cpt-jerarquico-frente-a-paginas-generadas.md)).

- Pros: **cero código de permisos**. `root_id()` ya recorre `post_parent`, así
  que `post_areas()` y `is_archived()` funcionan el día uno, sin tocarlos.
- Contras: `evt_speaker` y `evt_activity` no son jerárquicos, así que
  `post_parent` no sale en su pantalla del escritorio y quien mire por ahí no
  verá de qué evento es cada ficha.

## Decisión

**Un ponente y una actividad cuelgan de su evento por `post_parent`.** No hay
meta que diga a qué evento pertenecen: lo dice el árbol.

De ahí sale, sin escribir una línea de permisos:

| Pregunta | Quién la contesta | Qué devuelve ahora |
|---|---|---|
| ¿De qué área es este ponente? | `EventAccess::post_areas()` | Las del **evento**, vía `root_id()` |
| ¿Se puede editar? | `EventAccess::can_edit()` | Lo mismo que su evento |
| ¿Está cerrado? | `EventAccess::is_archived()` | La marca del **evento** |

Comprobado con una prueba que cierra el evento y afirma que su ponente deja de
editarse (`tests/unit/test-programme.php`,
`test_a_speaker_hangs_from_its_event_and_inherits_its_area()`).

### Lo que el árbol no comprueba, y hay que comprobar aparte

**El acotado por área diría que sí al ponente de otro evento de la misma
área.** Dos eventos de un área comparten término, así que `can_edit()`
devuelve `true` para las fichas de los dos. Por eso toda escritura pasa antes
por `Programme::belongs()`, que exige que la ficha cuelgue **de este** evento.
Hay prueba de que guardar con el identificador de otro evento no escribe nada.

### Las metas, y dónde viven

En `Meta/ProgrammeMetaKeys`, aparte de las del evento: `all()` de
`EventMetaKeys` significa «las metas del evento» y mezclarlas la volvería una
mentira.

| Ficha | Metas |
|---|---|
| Ponente | `evt_speaker_role`, `evt_speaker_org`. El nombre es el título, la biografía el contenido y la foto la imagen destacada |
| Actividad | `evt_activity_kind`, `_date`, `_start`, `_end`, `_venue`, `_room`, `_seats`, `_speakers` |

**El tipo de actividad es una lista cerrada en PHP**, no una taxonomía, por la
lección de `convocatoria`: ponencia, taller, mesa redonda, comunicación, panel,
inauguración, clausura, descanso y otra.

**La sede es de la actividad**
([ADR-0024](ADR-0024-un-dia-puede-tener-dos-sedes.md)), y por eso `evt_venue`
del evento y `evt_activity_venue` de la actividad son dos metas distintas: la
primera es dónde se celebra el evento en general, la segunda dónde ocurre esta
actividad en concreto.

**Un taller no es otro tipo de contenido**: es una actividad cuyo `kind` es
`taller`. La pestaña «Talleres» es una vista sobre el programa, y por eso los
talleres se crean y se editan en «Programa» y allí no hay ningún botón de
«añadir taller».

## Consecuencias

### Positivas

- **El cierre por histórico alcanza al programa entero** sin una línea nueva, y
  la promesa de la ADR-0017 queda cumplida y probada.
- **Un área gestiona su evento entero**: páginas, ponentes, programa y
  talleres, con la misma regla de permisos para las cuatro cosas.
- Borrar un ponente es enviarlo a la papelera y se restaura desde la misma
  pantalla ([ADR-0016](ADR-0016-borrar-es-enviar-a-la-papelera.md)); ponentes y
  actividades comparten papelera porque lo que se busca ahí es «lo que borré
  sin querer».
- **Las sedes no se declaran**: la lista de un evento sale de sus actividades,
  y el formulario propone las ya usadas con un `datalist`.

### Negativas

- **En el escritorio de WordPress, un ponente no dice de qué evento es.**
  `evt_speaker` no es jerárquico, así que `post_parent` no se pinta en su
  listado. Quien entre por ahí ve fichas sueltas. Hace falta una columna
  «Evento» en `EventAdmin`, y **no está escrita**.
- **Los ponentes de un evento migrado habrá que colgarlos.** En el sistema
  anterior las fichas de ponente son entradas de un formulario suelto y no
  cuelgan de ningún evento; el guion de migración tendrá que resolverlo, y lo
  que no se pueda emparejar quedará huérfano y editable solo por quien lo
  importó.
- **Contar las plazas de un taller cruza títulos, no identificadores.** En la
  fase 1 la selección de talleres se queda en el gestor de formularios que ya
  existe y no hay ninguna clave común, así que cambiarle el título a un taller
  ya empezado descuadra la cuenta. La pantalla lo dice donde se lee el número,
  no en esta ADR.

### Neutras

- Las actividades se ordenan en PHP y no con un `meta_query`: el orden sale de
  tres metas y son decenas de filas por evento, así que tres `JOIN` para
  ahorrar un `usort` no salen a cuenta. Si un evento llegara a cientos de
  actividades, esto es lo primero que habría que mirar.
- `EventAccess::stamp_area()` sigue escribiendo el término en la ficha aunque
  ya no haga falta para los permisos. No estorba y se queda: retirarlo es un
  cambio de comportamiento que esta ADR no necesita.
