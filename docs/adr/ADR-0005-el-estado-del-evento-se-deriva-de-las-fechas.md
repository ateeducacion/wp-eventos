---
id: ADR-0005
title: "El estado del evento se deriva de las fechas"
status: Aceptada
date: 2026-09-12
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0003, ADR-0004, ADR-0008]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0005: El estado del evento se deriva de las fechas

## Estado

Aceptada

## Contexto

Hoy «abierto», «finalizado» y «en borrador» son **términos de una
taxonomía**. Un fragmento de código pegado a mano registra `convocatoria`
sobre `page`, y entre sus 50 términos hay tres que son el estado del evento.
El campo «Estado del evento» del formulario del sistema anterior escribe
directamente en esa taxonomía, y la acción que crea la página lo mapea contra
ella con una lista de **decenas de IDs de término excluidos**, mantenida a
mano, para que en el desplegable solo salgan los tres que son estado.

El reparto real de esos tres términos, medido sobre la exportación del
2026-09-12 (`.local/` (investigación del sistema anterior)):

| Término | Páginas | Qué debería significar |
|---|---:|---|
| `evento-finalizado` | 32 | El evento ya pasó |
| `evento-abierto` | 2 | El evento está en curso o admite inscripción |
| `evento-en-borrador` | 0 | Todavía no se publica |

Con unos 30 eventos en el sitio, esa tabla no describe el estado de los
eventos: describe **cuándo se tocó por última vez el desplegable**. No hay
ni un solo evento «próximo», y solo dos «abiertos», porque el término se
elige el día del alta y nadie vuelve a entrar al formulario cuando el evento
termina.

Dos observaciones más del mismo sitio:

- **El borrador ya tiene su sitio en WordPress.** El formulario trae además
  un campo «Estado de la publicación en WordPress» que alimenta `post_status`
  en esa misma acción. El término `evento-en-borrador` es un tercer
  vocabulario para lo que `post_status` ya dice, y por eso está a cero.
- **«Promociona» no es un estado.** Hay otro campo que escribe en
  `convocatoria`, con su propia lista de exclusiones, y sirve para destacar un
  evento en la portada. Es una decisión editorial —alguien decide qué se
  enseña primero—, no una posición en el calendario.
- **Las fechas de hoy no son fechas.** Los campos de fechas y de sedes son
  **texto libre de corrido** («del 6 al 7 de junio»), así que a día de hoy no
  hay de dónde derivar nada.

## Problema

¿El estado de un evento es un dato que alguien mantiene, o un cálculo sobre
sus fechas?

## Factores de decisión

- **Un estado que hay que acordarse de cambiar se queda obsoleto.** No es una
  hipótesis: 32 finalizados, 2 abiertos y 0 próximos es la prueba.
- **El dato de partida ya se pide.** Un evento sin fechas no se anuncia. Lo
  que falta no es el dato, es que sea una fecha y no un párrafo.
- **Destacar en portada no es cronología** y no puede vivir en el mismo
  vocabulario que el estado.
- **El borrador es `post_status`.** Duplicarlo en una taxonomía crea la
  posibilidad de que se contradigan.
- **Consultabilidad.** Un término se filtra con `tax_query`; un valor
  calculado no se filtra con nada. Hay que decir con qué se sustituye.
- **Coste de mantenimiento del vocabulario.** Cada término nuevo de
  `convocatoria` hay que excluirlo a mano de las otras tres listas de IDs, de
  decenas de entradas cada una, o aparece en el desplegable equivocado.

## Alternativas consideradas

### Opción 1: mantener el estado como término (statu quo)

Tres términos de `evt_area`… o, más honestamente, de la taxonomía que
sustituya a `convocatoria`, que alguien marca al crear y actualiza después.

| Pros | Contras |
|---|---|
| Se filtra con `tax_query` sin escribir nada. | Es exactamente lo que ya se probó y falló. |
| No hace falta que las fechas sean fechas. | Obliga a una tarea recurrente que nadie tiene asignada. |
| Se puede poner un estado que las fechas no expresan. | Permite que el evento se contradiga: «abierto» con fecha de 2021. |

### Opción 2: estado guardado en meta, recalculado por un cron diario

El valor se guarda para poder consultarlo y una tarea programada lo pone al
día cada noche.

| Pros | Contras |
|---|---|
| Consultable con `meta_query`. | Dos fuentes de verdad para el mismo hecho. |
| No depende de que nadie se acuerde. | WP-Cron se dispara con las visitas: en un sitio con poco tráfico nocturno, el estado cambia tarde. |
| | Una pieza más que registrar, vigilar y explicar cuando no coincide. |

### Opción 3: derivarlo de las fechas, sin guardarlo (elegida)

Una función pura que recibe las dos fechas y el día de hoy.

| Pros | Contras |
|---|---|
| No se puede quedar obsoleto: no hay nada que actualizar. | No se filtra por estado: se filtra por fechas. |
| Una sola fuente de verdad, la que ya se publica en el cartel. | Exige que las fechas sean fechas y no texto. |
| Se prueba con PHPUnit sin cargar WordPress. | No puede expresar «cancelado» ni «aplazado». |

### Opción 4: estado editable que sobrescribe el derivado

Lo mismo que la opción 3, más una meta opcional que gana cuando está puesta,
para «cancelado» o «aplazado».

Es la respuesta correcta el día que alguien pida cancelar un evento sin
borrarlo. Hoy nadie lo ha pedido: entre los 50 términos de `convocatoria` no
hay ninguno que signifique eso, y en 163 páginas no hay rastro de un evento
cancelado. Se deja anotada, no implementada: la escotilla es añadir la meta
y una rama, no rehacer nada.

## Decisión

**El estado del evento se calcula. No se guarda, no es un término y no hay
desplegable que rellenar.**

`Evt\Domain\EventState::of( $start, $end, $today )` devuelve uno de los tres
valores de `EventMetaKeys::states()`, a partir de las metas `evt_start_date` y
`evt_end_date` (`EventMetaKeys::START_DATE` y `END_DATE`), ambas en `Y-m-d` y
saneadas al guardar (`EventMetaRegistration::sanitize_date()`):

| Condición | Estado | Etiqueta |
|---|---|---|
| Sin fecha de inicio | `proximo` | Próximo |
| `hoy < inicio` | `proximo` | Próximo |
| `inicio <= hoy <= fin` | `abierto` | Abierto |
| `hoy > fin` | `finalizado` | Finalizado |

Con dos normalizaciones escritas en el propio código: si la fecha de fin está
vacía o es anterior a la de inicio, **fin = inicio**
(`EventState::of()`), de modo que un evento de un día no necesita
rellenar dos campos; y un evento **sin fecha de inicio se considera
«próximo»**, porque un evento que todavía no tiene fecha es un evento que se
está preparando.

Consecuencias directas de la decisión:

- **Ninguna de las tres taxonomías nuevas es el estado.** `evt_area`,
  `evt_type` y `evt_course` (las constantes `AREA`, `TYPE` y `COURSE` de
  `EventTaxonomies`) cubren los otros tres ejes de `convocatoria`; los tres términos de estado
  **no se migran a ninguna parte**
  ([ADR-0004](ADR-0004-una-taxonomia-por-dimension.md),
  [ADR-0008](ADR-0008-migracion-de-los-eventos-historicos.md)).
- **El borrador es `post_status`.** No se registra ningún término ni ninguna
  meta para eso. La página privada que hoy hace de listado de borradores la
  sustituye el filtro estándar «Borradores» de la lista del escritorio.
- **No se hereda ningún «destacado», porque hoy no existe.** El campo que se
  llama «Promociona», medido contra el vocabulario, ofrece los términos del
  eje de área y ninguno más: es el selector del área organizadora, no una
  marca de portada
  ([ADR-0004](ADR-0004-una-taxonomia-por-dimension.md)). Va a `evt_area`. La
  portada de hoy tampoco destaca nada: la pinta un listado por taxonomía. Si
  algún día hace falta destacar un evento, será un requisito nuevo con su
  propia decisión, no una migración.
- **Filtrar por estado es filtrar por fechas.** La equivalencia, para quien
  escriba una consulta:

  | Estado | `meta_query` |
  |---|---|
  | Próximo | `evt_start_date > hoy` (o vacía) |
  | Abierto | `evt_start_date <= hoy` **y** `evt_end_date >= hoy` |
  | Finalizado | `evt_end_date < hoy` |

  Para que las dos últimas sean una sola comparación, al guardar se escribe
  siempre `evt_end_date`, copiando `evt_start_date` cuando el evento dura un
  día. Es la misma normalización que hace `EventState::of()`, aplicada
  también al dato guardado: si solo viviera en la función, la consulta y la
  pantalla dirían cosas distintas.

## Consecuencias

### Positivas

- El estado no puede quedarse obsoleto, porque no hay nada que actualizar.
  Los 32 «finalizado» y los 2 «abierto» dejan de ser un dato que mentir.
- Desaparecen tres términos, un desplegable del formulario y una de las
  cuatro listas de exclusión de IDs que había que mantener a mano.
- El evento no puede contradecirse: no existe la combinación «abierto» con
  fecha de 2021.
- `EventState` es pura —no llama a WordPress (`EventState::of()`)— así que se prueba con PHPUnit sin
  levantar el sitio, incluido el día del cambio de estado, pasándole `$today`.

### Negativas

- **No se puede filtrar por estado con `tax_query`.** Toda consulta pasa por
  `meta_query` sobre dos claves, que es más largo de escribir y más lento que
  un término, y obliga a que ambas fechas existan siempre.
- **Las fechas tienen que dejar de ser texto libre.** Hoy esos dos campos
  admiten «del 6 al 7 de junio» y sedes de corrido. Esta decisión convierte
  ese texto en dos campos de fecha obligatorios, y eso es trabajo de
  migración: los 163 registros históricos **no traen fecha en un formato
  aprovechable**.
- **Un evento sin fecha sale como «próximo».** Es lo correcto para un evento
  que se está preparando y es **falso** para los eventos históricos: al
  migrar hay que fijarles la fecha o excluirlos de las consultas de estado
  con `evt_legacy`
  ([ADR-0008](ADR-0008-migracion-de-los-eventos-historicos.md)). Si no se hace
  ninguna de las dos, la portada anuncia como próximos treinta eventos de
  2021.
- **No hay «cancelado» ni «aplazado».** Un evento que se suspende hay que
  despublicarlo o cambiarle la fecha; ambas cosas pierden información. Es la
  opción 4, y hoy no está implementada.
- El cálculo usa `gmdate( 'Y-m-d' )` (`EventState::of()`), es decir UTC. En un
  despliegue cuya hora local vaya por delante de UTC, durante la última hora
  del día el estado se adelanta una jornada. Para un dato de día entero es
  irrelevante, pero conviene saberlo antes de perseguir el fantasma.

### Neutras

- El vocabulario cambia poco: «abierto» y «finalizado» se conservan y se
  añade «próximo», que hoy no existe en el sitio aunque sea el estado de todo
  evento antes de celebrarse.
- El estado no queda registrado en ninguna parte, así que no se puede saber
  qué estado tenía un evento un día concreto. Tampoco hace falta: las fechas
  sí se guardan, y con ellas el estado de cualquier día se recalcula.
- Los tres términos de estado siguen existiendo en la base de datos mientras
  `convocatoria` siga registrada. Esta ADR no los borra: deja de usarlos.
