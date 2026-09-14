---
id: ADR-0012
title: "Política de edición y auditoría"
status: Aceptada
date: 2026-09-12
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0005, ADR-0006, ADR-0008]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0012: Política de edición y auditoría

## Estado

Aceptada (2026-09-12). Decidida antes de implementarla: a esta fecha
`EventAccess` acota por área, y no existe ningún módulo de auditoría (ver
«Consecuencias»).

## Contexto

Hoy no hay política de edición, hay un shortcode.

- **Ningún rol de área tiene una sola capacidad de escritura.** Ni
  `edit_pages`, ni `publish_pages`. Las capacidades de todos los perfiles se
  leyeron una a una desde Members, y los roles de área se quedan en variantes
  de `read` y `read_others_pages` (`.local/`, investigación del sistema
  anterior). Las áreas no editan WordPress: rellenan un formulario desde el
  frontal.
- **Quién ve el enlace de edición lo decide un shortcode con tres nombres de
  rol escritos a mano** dentro de un fragmento de código pegado al sitio. Eso
  esconde el enlace; no protege nada.
- **Quién puede enviar el formulario lo decide el propio formulario**: la
  lista de quién puede rellenarlo y la de quién puede reabrir un envío están
  escritas en sus opciones, y en las dos hay dos roles.
- **La acción que crea la página se dispara también al actualizar**, no solo
  al enviar por primera vez. Reenviar el formulario reescribe la página.
- **No hay límite temporal**: un evento de hace cinco años se puede reeditar
  hoy.
- **No queda rastro de quién cambió qué.** WordPress guarda revisiones de
  `post_content`, pero el contenido lo genera una plantilla del sistema
  anterior ([ADR-0010](ADR-0010-la-pagina-la-genera-codigo-versionado.md)),
  así que una revisión dice que la plantilla se volvió a interpolar, no qué
  dato cambió.

El aplicativo nuevo sí reparte capacidades reales de escritura
([ADR-0006](ADR-0006-el-area-es-un-ambito-no-un-rol.md)), y eso obliga a
decidir hasta dónde llegan.

## Problema

¿Quién puede tocar un evento, hasta cuándo, y qué queda registrado de cada
cambio?

## Factores de decisión

- **Integridad de lo publicado.** Un evento publicado es un compromiso con
  quien se ha inscrito: fechas, sede, programa. Hay eventos con varios
  cientos de personas inscritas.
- **Autonomía del área.** Que un área publique lo suyo sin pedir turno es la
  razón de ser del aplicativo; una política que obligue a pedir permiso para
  todo lo desmonta.
- **Corregir una errata tiene que seguir siendo barato.** Es el caso más
  frecuente con diferencia.
- **El trabajo no acaba con el evento.** Los vídeos de las ponencias, las
  presentaciones y las fotos se suben *después*, y los sube el área.
- **Rendir cuentas.** Cuando la fecha de una jornada cambia, tiene que
  poderse decir quién la cambió y cuándo.
- **Una regla, no un motor de reglas.** Cada modo configurable es un camino
  más que probar y explicar.

## Alternativas consideradas

### Opción 1: acotado por área sin límite temporal, con un cierre explícito por encima — ELEGIDA

`EventAccess::can_edit()` compara las áreas del perfil con las del evento, y lo
único que cierra la puerta es una marca que pone una persona
([ADR-0017](ADR-0017-el-estado-historico-cierra-la-edicion.md)).

- Pros: mínimo código, máxima autonomía, ninguna regla que se dispare sola en
  el peor momento.
- Contras: un evento terminado se puede reescribir mientras nadie lo marque, y
  los eventos históricos migrados quedan abiertos hasta que se marquen. La
  respuesta a eso es la auditoría —saber **quién** lo tocó—, no un candado del
  calendario.

### Opción 2: bloqueo al publicar

- Pros: máxima integridad; lo publicado es inmutable.
- Contras: la errata del título obliga a molestar a administración. Fricción
  alta justo en el caso más común, y la experiencia dice que eso acaba en
  gente publicando en borrador «por si acaso». Descartada.

### Opción 3: el área manda mientras el evento no haya terminado

El corte lo marcaría el estado derivado de las fechas
([ADR-0005](ADR-0005-el-estado-del-evento-se-deriva-de-las-fechas.md)), sin
botón que nadie pueda olvidar pulsar.

- Pros: el corte cae donde parece que el trabajo cambia de naturaleza —antes
  preparación, después archivo—, es una sola regla y no hay nada que
  configurar.
- Contras, y son los que la descartan: **el corte cae justo donde empieza el
  trabajo**. Cuando una jornada termina es cuando **más** se toca su página: se
  suben los vídeos de las ponencias, se cuelgan las presentaciones, se añaden
  las fotos y se corrigen las erratas que se vieron el día del acto. Un cierre
  automático el sábado siguiente estorbaría en la semana de más faena y
  convertiría en cola de administración lo que un área puede hacer sola. La
  integridad que promete, mirada de cerca, protege poco: de un evento pasado lo
  que hay que defender no es que nadie lo toque, sino que **se sepa quién lo
  tocó**. Eso es la auditoría, no el calendario. Descartada.

### Opción 4: política configurable con varios modos

Una opción del aplicativo con cuatro modos —quién puede editar qué y hasta
cuándo—, elegible por quien administra. Es un patrón que ya se ha usado en la
casa.

- Pros: cada área puede ser más o menos estricta.
- Contras: cuatro modos son cuatro caminos que probar, documentar y explicar,
  y ninguna área ha pedido todavía uno distinto del que aquí se elige. Se
  descarta **por ahora**: si aparece la necesidad, es una opción
  `evt_edit_policy` y esta ADR se sustituye.

## Decisión

**Haremos la opción 1: el área manda en su área, sin límite de calendario.**
Lo que cierra un evento es un botón, no una fecha.

### Quién puede tocar un evento y hasta cuándo

| Quién | Qué puede hacer | Hasta cuándo |
|---|---|---|
| `evt_organiser` con el área del evento en su perfil | crear, editar y publicar el evento y sus páginas satélite, y su **CSS a medida** ([ADR-0014](ADR-0014-css-del-area-javascript-de-administracion.md)) | indefinidamente, mientras el evento no esté marcado como **histórico** ([ADR-0017](ADR-0017-el-estado-historico-cierra-la-edicion.md)) |
| `evt_organiser` de otra área | nada | — |
| `evt_organiser` sin área en el perfil | nada | — (fail-closed) |
| Autoría de un evento que todavía no tiene área | editarlo, para poder ponérsela | ídem |
| `administrator` | todo, en cualquier área, más el **JavaScript a medida**, los ajustes del aplicativo y **desmarcar** el histórico | siempre, incluido lo marcado como histórico |

**El estado derivado de las fechas no decide permisos.**
`EventState::of()` ([ADR-0005](ADR-0005-el-estado-del-evento-se-deriva-de-las-fechas.md))
se usa para pintar y para filtrar —la columna del escritorio, la cabecera
pública, el taller y el listado—, y `EventAccess::can_edit()` no lo consulta.
«Finalizado» no es «cerrado».

La regla se aplica en **`Access/EventAccess`, el único guardián**, y sobre
`map_meta_cap` (`src/Evt/Access/EventAccess.php`), no sobre el listado
del escritorio: esconder algo con `pre_get_posts` deja abiertos el enlace
directo a `post.php?post=N`, la edición rápida, las acciones en bloque y la
REST API. El acotado del listado se hace también, pero es comodidad, no
protección.

La denegación se explica en castellano y desde un solo sitio,
`EventAccess::why_not_editable()`, con tres motivos y ninguno temporal: el
evento está marcado como histórico, el perfil no tiene la capacidad, o el
evento es de otra área.

### Qué se audita

Toda mutación permitida sobre un `evt_event` —creación, edición, cambio de
estado de publicación, borrado— deja registrado:

| Dato | Qué es |
|---|---|
| Actor | `user_id` de quien la hizo |
| Momento | fecha y hora en UTC, formato ISO 8601 |
| Qué cambió | clave afectada, valor anterior y valor nuevo |

Se guarda en meta del **evento raíz** (`EventAccess::root_id()` ya resuelve
cuál es), no en una tabla propia: el repositorio no es un plugin y
no tiene sitio donde correr un `dbDelta`
([ADR-0001](ADR-0001-repo-entorno-desarrollo-no-plugin.md)). El registro está
acotado a un número máximo de entradas por evento; cuando se llena, se
descartan las más antiguas.

No se auditan las lecturas, ni los cambios que WordPress ya registra por su
cuenta en las revisiones de `post_content`.

## Consecuencias

### Positivas

- El área no pide turno para su trabajo del día a día, que es el 95 % del uso,
  **ni para lo que viene después del evento**, que es cuando más se toca la
  página.
- Cuando una fecha o una sede cambian, hay a quién preguntar y cuándo fue.
- Una sola regla: no hay opción que configurar, ni cuatro modos que probar, ni
  una ventana de gracia que calibrar.
- Quien cierra un evento lo hace a sabiendas, en un momento concreto y con un
  aviso delante, en vez de encontrárselo cerrado un lunes.

### Negativas

- **Un evento que nadie marca se edita indefinidamente.** Un evento de 2019
  sigue abierto para su área mientras nadie pulse el botón, y los botones se
  olvidan. Es la puerta abierta de esta política y se acepta a sabiendas: la
  alternativa era cerrar en el peor momento. Lo que la compensa es la
  auditoría, y la auditoría **todavía no está escrita**.
- **La auditoría hace más falta, no menos.** Si un evento se puede editar para
  siempre, saber quién lo editó es la única garantía que queda.
- **La auditoría en meta tiene tope y no es un archivo histórico.** Guarda los
  últimos cambios de cada evento, no todos. Si alguna vez hace falta
  conservarlos todos, hay que cambiar de almacén y eso es otra ADR.
- **Nada de esto está implementado a 2026-09-12.** `EventAccess::can_edit()`
  no mira el estado del evento (`src/Evt/Access/EventAccess.php`) y no
  hay ningún módulo de auditoría en `src/Evt/load-order.php`. Esta ADR
  es lo que falta por escribir, no lo que ya está hecho.
- **Administración es el cuello de botella para reabrir un evento cerrado.**
  Es poca cosa comparado con serlo de todo lo posterior a cada jornada, pero
  existe: quien se cierre su propio evento por error necesita a otra persona.

### Neutras

- Los eventos históricos migrados llegan con fecha pasada, pero **eso no los
  cierra**: lo que congela su contenido es `evt_legacy`
  ([ADR-0008](ADR-0008-migracion-de-los-eventos-historicos.md)), y cerrarlos
  a la edición es marcarlos, uno a uno o en la migración.
- La política no dice nada de las inscripciones, que siguen en el sistema
  anterior con sus propios permisos (ADR-0007).
- Mientras ese formulario siga vivo conviven dos políticas: la de esta ADR
  para lo que se crea en el aplicativo, y la del formulario —dos roles escritos
  en sus opciones— para lo que se crea allí.
