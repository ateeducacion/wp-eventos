---
id: ADR-0045
title: "Los eventos históricos se importan a un sitio limpio y el antiguo se conserva al lado"
status: Propuesta
date: 2026-09-28
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0006, ADR-0008, ADR-0022, ADR-0030, ADR-0031, ADR-0032, ADR-0033, ADR-0036, ADR-0038, ADR-0042]
supersedes: [ADR-0008]
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0045: Los eventos históricos se importan a un sitio limpio y el antiguo se conserva al lado

## Estado

Propuesta

## Contexto

La [ADR-0008](ADR-0008-migracion-de-los-eventos-historicos.md) decidió migrar
en el mismo sitio: cambiar `post_type` de `page` a `evt_event` sin tocar nada
más y congelar el contenido. Todo lo que la sostenía eran cuatro cosas que
había que conservar:

1. **Los IDs**, porque el contenido histórico llama a la plantilla del
   sistema anterior con el ID de la propia página.
2. **El sistema anterior instalado**, en modo lectura, para que esas llamadas
   sigan pintando algo.
3. **El tema**, porque el contenido son sus shortcodes.
4. **Los usuarios y sus roles**, que el cambio de tipo no tocaba.

Al preparar el primer despliegue, las cuatro han dejado de importar:

- **El contenido histórico ya no se congela: se recrea.** La
  [ADR-0042](ADR-0042-los-eventos-conservan-sus-url-y-pintan-su-programa.md)
  hizo posible pintar un evento migrado con sus ponentes, su programa, sus
  fichas y sus vídeos desde el aplicativo, y la
  [ADR-0022](ADR-0022-la-pagina-de-evento-se-pinta-entera.md) lo pinta sin el
  tema. Sin contenido congelado no hacen falta ni el ID, ni la plantilla vieja,
  ni el tema.
- **Las inscripciones ya son del aplicativo**
  ([ADR-0032](ADR-0032-la-inscripcion-es-un-contenido-del-evento.md)), así
  que las de los eventos ya celebrados pueden traerse como `evt_registration`
  en lugar de depender del gestor de formularios para consultarlas.
- **Las personas que gestionan los eventos cambian este curso**: las cuentas
  se crean de nuevo con los roles del aplicativo.
- **Al empezar el curso no hay ningún evento abierto**: todo lo que hay es
  histórico, así que no hay inscripciones en curso que partir en dos.

Y el destino es un **subsitio de un multisitio**, donde crear un sitio vacío
y renombrar el anterior es una operación de la administración de la red, sin
tocar ficheros ni base de datos a mano.

**Esa red sirve los ficheros por la ruta del sitio, no por su número.** Usa el
esquema antiguo de multisitio: cada sitio guarda en `blogs.dir/<número>/files/`
y sus ficheros se piden como `/<ruta del sitio>/files/AAAA/MM/<nombre>`. Está
medido el 2026-09-28, justo después de renombrar el sitio anterior: un fichero
suyo responde 200 bajo la ruta nueva y **404 bajo la de siempre**. Es decir,
renombrar el sitio ya ha roto los enlaces externos a sus carteles y PDF, y lo
que los arregla es que el sitio nuevo tenga ese mismo fichero en esa misma
ruta.

## Problema

¿Se despliega el aplicativo sobre el sitio que ya existe y se transforma su
contenido en su sitio, o se crea un sitio vacío en la misma ruta y se importa
a él lo que haga falta del anterior?

## Factores de decisión

- **Las URL bonitas de los eventos**, `/<evento>/<sección>/`, están enlazadas
  desde fuera y tienen que seguir valiendo.
- **Los ficheros publicados** —carteles, PDF, fotos— también están enlazados
  desde fuera.
- **Reversibilidad**: si la importación sale mal, tiene que poder repetirse
  desde cero sin haber perdido nada.
- **No arrastrar lo que se sustituye**: la taxonomía `convocatoria`, las
  opciones y tablas del gestor de formularios, los fragmentos de código
  pegados a mano, el tema y sus ajustes.
- **Minimizar datos personales**: no copiar lo que no hace falta. Las
  inscripciones sí hacen falta —son la memoria de quién asistió a qué—, pero
  solo con lo que el aplicativo sabe guardar.
- **Los ámbitos, bien hechos desde el principio**: el sitio anterior no los
  tiene como tales (el área era un rol,
  [ADR-0006](ADR-0006-el-area-es-un-ambito-no-un-rol.md)), y la jerarquía con
  correo y logo de la
  [ADR-0038](ADR-0038-ambitos-organizativos-jerarquicos.md) no existe en
  ningún sitio que se pueda copiar tal cual.

## Alternativas consideradas

### Opción 1: migrar en el mismo sitio ([ADR-0008](ADR-0008-migracion-de-los-eventos-historicos.md))

| Pros | Contras |
|---|---|
| Se conservan IDs, adjuntos y rutas de los ficheros sin hacer nada. | Todo lo que se sustituye se queda en la base de datos del sitio: taxonomía, opciones, tablas, roles de área, fragmentos. Limpiarlo es un trabajo aparte y a ciegas. |
| No hace falta la administración de la red. | Lo que se transforma se transforma sobre el original: la vuelta atrás es restaurar una copia de seguridad. |
| | Conservar los IDs ya no aporta nada: nada del contenido nuevo los usa. |

### Opción 2: sitio limpio e importación desde el anterior (elegida)

| Pros | Contras |
|---|---|
| El sitio empieza con lo que el aplicativo necesita y nada más. | **Cambian los IDs.** Los enlaces `?page_id=<N>` que circulen fuera dejan de llevar al evento. |
| El sitio anterior queda intacto al lado: es la copia de seguridad y la fuente de la importación a la vez. | Desde que se renombra el sitio anterior hasta que termina la importación, los enlaces externos a sus ficheros dan 404. |
| Repetir la importación es vaciar el sitio nuevo y volver a lanzarla. | Hace falta la administración de la red para crear el sitio, activarle el tema y los plugins, y cambiar las opciones que un subsitio no enseña. |
| En un multisitio, el importador puede leer el sitio anterior con `switch_to_blog()` sin exportar nada. | Los ajustes del sitio se hacen a mano, una vez. |

### Opción 3: sitio limpio e importación con el importador de WordPress (WXR)

Descartada: el fichero de exportación lleva páginas y adjuntos, pero no las
entradas del gestor de formularios, que es donde están los ponentes, las
actividades y las sedes. Haría falta el importador propio igualmente.

## Decisión

**Se despliega en un sitio vacío, en la ruta de siempre, y se importa a él lo
histórico desde el sitio anterior, que se conserva al lado con otra ruta.**

**El sitio anterior.** Se renombra y **no se borra**: es la copia de
seguridad y la fuente de la importación.

**El sitio nuevo.**

- El tema por defecto del núcleo: la página de un evento no lo usa
  ([ADR-0022](ADR-0022-la-pagina-de-evento-se-pinta-entera.md)), y es el tema
  del entorno local, que es donde se prueba el aplicativo. En un multisitio hay
  que habilitárselo al sitio desde la red.
- Code Snippets, Members y WPFront User Role Editor, activados **en el
  sitio**, no en la red.
- Sin carpetas por año y mes para **lo que se suba a partir de ahora**
  (`uploads_use_yearmonth_folders` a `0`). En un subsitio, Ajustes → Medios
  no enseña esa casilla; se cambia desde la edición del sitio en la red. No
  afecta a lo importado, que conserva su ruta, y no choca con ello: lo nuevo
  va a la raíz de `files/` y lo importado, a sus carpetas de fecha.
- Los eventos, en la raíz (`evt_root_urls`), para que respondan en sus URL de
  siempre.

**Qué se importa.**

| Del sitio anterior | Al sitio nuevo |
|---|---|
| Cada evento y cada una de sus secciones | `evt_event` raíz y sus hijas, **con los mismos slugs**, de modo que `/<evento>/<sección>/` sigue valiendo |
| Ponentes y actividades | `evt_speaker` y `evt_activity` del evento, con el número de su entrada antigua en `evt_legacy_entry`, que es lo que mantiene las fichas `…/entry/<N>/` ([ADR-0042](ADR-0042-los-eventos-conservan-sus-url-y-pintan-su-programa.md)) |
| Tipología y curso | Términos de `evt_type` y `evt_course` |
| Área | Términos de `evt_area`, colgados del árbol de ámbitos (abajo) |
| Inscripciones | `evt_registration` del evento ([ADR-0032](ADR-0032-la-inscripcion-es-un-contenido-del-evento.md)): el núcleo fijo con lo que haya, y el resto como respuestas a preguntas del evento ([ADR-0031](ADR-0031-el-formulario-de-inscripcion-es-nucleo-fijo-mas-preguntas.md)). El taller elegido, si lo hay, como ID de la actividad ([ADR-0033](ADR-0033-elegir-taller-aforo-duro-y-cambio-hasta-el-cierre.md)). Los documentos aportados van a `RegistrationFiles`, **no** a la biblioteca ([ADR-0036](ADR-0036-los-ficheros-de-una-inscripcion-no-son-adjuntos.md)) |
| Carteles, logos, fotos, PDF | **Copias** en la biblioteca de medios del sitio nuevo, **en la misma ruta relativa que tenían** (`AAAA/MM/<nombre>`), de modo que `/<ruta de siempre>/files/AAAA/MM/<nombre>` vuelve a responder. Son assets editoriales: adjuntos normales, con su URL pública ([ADR-0036](ADR-0036-los-ficheros-de-una-inscripcion-no-son-adjuntos.md) solo aparta los de las inscripciones) |

**El árbol de ámbitos.** Se construye antes que nada, porque los eventos se
cuelgan de él. La raíz es el organismo; debajo, sus órganos directivos por
niveles —viceconsejerías, direcciones generales, servicios y lo que cuelgue de
ellos—, cada uno con su nombre bien escrito, su correo y su logo. La fuente es
el catálogo de ámbitos que ya mantiene otro sitio de la misma red, completado
con los que falten y anidado donde toque. Qué órganos son y cómo se llaman es
dato de una organización: vive en el importador, no aquí
([ADR-0030](ADR-0030-el-repositorio-se-publica-sin-nada-de-nadie.md)). Cada
área del sitio anterior se asigna a su nodo del árbol.

**Qué no se importa**: usuarios, roles, la taxonomía
`convocatoria`, el contenido de las páginas escrito con el tema, las opciones y
tablas del gestor de formularios ni ningún fragmento de código pegado a mano.

**El importador.** Por orden: ámbitos, ficheros, eventos y secciones,
ponentes y actividades, inscripciones.

- **No se versiona.** Lee por dentro el sistema anterior —sus formularios,
  sus campos, sus tablas—, y eso no puede publicarse
  ([ADR-0030](ADR-0030-el-repositorio-se-publica-sin-nada-de-nadie.md)). Vive
  en `.local/`, como el guion con el que se midió la ADR-0042.
- Corre **en el sitio nuevo** como un snippet de un solo uso de Code Snippets,
  y lee el anterior con `switch_to_blog()`. **Sobre el anterior solo lee.**
- **Idempotente**: un evento se reconoce por su ruta, y un ponente o una
  actividad o una inscripción por su `evt_legacy_entry`, y un ámbito por su
nombre bajo su padre. Lanzarlo dos veces no duplica nada.
- **Con ensayo en seco**: la primera pasada solo cuenta lo que haría, y se
  compara con el inventario del sitio anterior antes de escribir nada.
- Se prueba antes entero contra la copia local, que es donde ya se cargaron
  los eventos para la ADR-0042.

## Consecuencias

### Positivas

- El sitio nuevo no arrastra nada del sistema que se sustituye: ni su
  taxonomía, ni sus opciones, ni sus tablas, ni su tema.
- Si la importación sale mal, se vacía el sitio nuevo y se repite. El
  original no se ha tocado.
- Deja de haber dos modos de pintar un evento: no hay contenido congelado ni
  meta `evt_legacy` que distinga lo histórico en el editor.
- Se cierran los bloqueantes que la ADR-0008 dejó abiertos sobre el tema, las
  plantillas de página y lo que pregunta «¿es una página?», porque nada de eso
  llega al sitio nuevo.

### Negativas

- **Los enlaces `?page_id=<N>` que circulen fuera se rompen**: los IDs son
  otros. Se acepta: las URL bonitas, que son las que se comparten, se
  conservan.
- **Mientras no termine la importación, los enlaces externos a los ficheros
  dan 404**: el cambio de nombre ya los rompió. Por eso el importador copia
  primero los ficheros y después el contenido.
- Un fichero que el importador no copie se queda roto en su URL de siempre, y
  nada lo avisa salvo el recuento del ensayo en seco.
- **Los datos personales de las inscripciones pasan a estar en dos sitios**,
  el anterior y el nuevo. Se acepta porque el anterior queda como copia de
  seguridad, pero su acceso tiene que restringirse a administración.
- Cada fichero ocupa espacio dos veces, el original y su copia, mientras
  exista el sitio anterior.
- La importación **no se parece píxel a píxel** a lo publicado: es la misma
  limitación que ya asumía la ADR-0042.
- El sitio anterior sigue enlazando internamente a `/<ruta de siempre>/…`, que
  ahora es el sitio nuevo. Sirve de archivo, no para navegarlo.
- Configurar el sitio nuevo exige a alguien con administración de la red.
- El importador no tiene tests en el repositorio, porque no está en él. Su
  comprobación es el ensayo en seco y la revisión de una muestra de URL.

### Neutras

- `evt_legacy` deja de tener uso previsto. Quitarlo del código es otra
  decisión, cuando conste que nada lo lee.
- La ADR-0008 se conserva por su contexto, que es la evidencia de cómo estaba
  hecho lo histórico; lo que ya no vale es su decisión.
