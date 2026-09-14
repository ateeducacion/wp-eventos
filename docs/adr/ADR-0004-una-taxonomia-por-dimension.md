---
id: ADR-0004
title: "Una taxonomía por dimensión en lugar de la única convocatoria"
status: Aceptada
date: 2026-09-12
related:
  issues: []
  prs: []
  sdds: [SDD-0001, SDD-0002]
  adrs: [ADR-0003, ADR-0005, ADR-0006, ADR-0008]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0004: Una taxonomía por dimensión en lugar de la única `convocatoria`

## Estado

Aceptada

## Contexto

El sitio que se sustituye clasifica los eventos con **una sola taxonomía**,
`convocatoria`, registrada sobre `page` por un fragmento de código de quince
líneas pegado a mano en el escritorio, con esta particularidad:

```php
'capabilities' => array( 'assign_terms' => 'edit_guides',
                         'edit_terms'   => 'publish_guides' )
```

`edit_guides` y `publish_guides` **no existen en el sitio**: ningún rol de los
inventariados las tiene y ningún fragmento de código las concede. La
consecuencia es literal: **nadie puede asignar ni editar términos desde el
escritorio**. La única vía por la que un término llega a una página es el
formulario del sistema anterior, que los escribe al crear la página.

Esa taxonomía única tiene **50 términos** (recuento sobre el volcado de
contenidos, en `.local/`) y mezcla cuatro ejes distintos. La prueba no es una
interpretación: está en la acción que crea la página, que mapea **cuatro campos
del formulario contra la misma taxonomía** y los separa con cuatro listas de
exclusión de IDs de término mantenidas a mano.

| Eje real | Rótulo en el formulario | Vocabulario que ofrece |
|---|---|---|
| Tipología | «Tipología del evento» | `jornadas`, `encuentro`, `congreso`, `taller` |
| Curso escolar | «Curso escolar» | un término por curso académico |
| Estado | «Estado del evento» | `evento-abierto`, `evento-finalizado`, `evento-en-borrador` |
| **Área organizadora** | **«Promociona»** | los centros, servicios y direcciones de la organización |

Los cuatro conjuntos son disjuntos y agotan el vocabulario: ningún término
queda fuera de un eje y ninguno está en dos. Pero eso no lo dice ningún sitio;
solo se deduce leyendo las cuatro listas de exclusión, que además viven
**duplicadas**: los mismos IDs están en las opciones de cada campo y otra vez
en la acción. Entre las cuatro suman unos **150 IDs escritos a mano**, 300
entradas contando la duplicación. Cada término nuevo hay que excluirlo a mano
de las otras tres listas —en los dos sitios— o aparece en el desplegable
equivocado.

Dos observaciones más, medidas sobre el mismo volcado:

- El campo se llama «Promociona», pero lo que ofrece son exactamente los
  términos de área. El rótulo miente; el dato que guarda es el área
  organizadora. Es un ejemplo de libro de por qué una taxonomía usada para
  cuatro cosas acaba sin nombre para ninguna.
- De las 163 páginas, solo **36 llevan algún término**, y las 36 son páginas
  raíz (de 45). Las 118 hijas no llevan ninguno. Hay **151 asignaciones** de
  término en total y **6 páginas con más de un término de área**: un evento
  puede estar organizado por dos áreas, y eso hay que conservarlo.

Reconstruir de qué eje es un término requiere, hoy, leer cuatro listas de IDs
numéricos dentro de la acción que crea la página. No hay ningún sitio donde
ponga que un término es un área y otro una tipología.

### Nota sobre estas cifras

El reparto del vocabulario entre los cuatro ejes se recontó para esta ADR
cruzando las dos fuentes independientes que hay en `.local/`: las listas de
exclusión de la acción que crea la página y las opciones guardadas en cada uno
de los cuatro campos del formulario. Para cada campo, los términos que ofrece
son los del vocabulario menos los de su lista de exclusión. Las dos fuentes
coinciden término a término.

La primera foto del sistema anterior contó mal dos de las cuatro listas y, de
paso, leyó «Promociona» como un marcador de portada en lugar de como el área
organizadora. Esa lectura es la equivocada, y el recuento se rehízo dos veces,
una por cada fuente. Los demás documentos del repositorio se corrigieron contra
esta medida el 2026-09-12; la afirmación que quedaba en pie —«6 términos no
encajan en ninguna dimensión»— era el resto de una resta mal hecha sobre el
número de áreas, y ya no está en ninguno. La conclusión de fondo —cuatro ejes
en una taxonomía, separados solo por listas mantenidas a mano— no cambia con
ninguno de los dos recuentos.

## Problema

¿Cómo se modela la clasificación de un evento —área organizadora, tipología,
curso escolar y estado— de forma que cada eje tenga su propio vocabulario, que
añadir un término no obligue a tocar listas de exclusión, y que se pueda
consultar y acotar por área sin depender de un formulario?

## Factores de decisión

- **Un término nuevo no debe obligar a editar nada más.** Hoy obliga a tocar
  hasta seis listas: la de cada campo y la del bloque correspondiente de la
  acción.
- **Cada eje necesita cardinalidad propia**: el área es multivalor (6 páginas
  lo demuestran), la tipología y el curso son de un valor.
- **El área es el eje de permisos**
  ([ADR-0006](ADR-0006-el-area-es-un-ambito-no-un-rol.md)): tiene que ser
  consultable con `tax_query` y asignable a personas.
- **Las capacidades declaradas deben existir.** El error del registro anterior
  no se repite.
- **Coste de migración**: hay 151 asignaciones que repartir entre los
  vocabularios nuevos
  ([ADR-0008](ADR-0008-migracion-de-los-eventos-historicos.md)).
- **Lo que se puede derivar, no se guarda**: un dato que se calcula no se
  teclea.

## Alternativas consideradas

### Opción 1: seguir con una taxonomía única y sus listas de exclusión

| Pros | Contras |
|------|---------|
| Coste cero: es lo que hay, y los términos ya existen con sus IDs. | 150 IDs mantenidos a mano, duplicados en dos sitios: 300 entradas que envejecen solas. |
| Una sola caja en el escritorio. | Un término nuevo hay que excluirlo de los otros tres ejes o sale donde no debe. |
| | No hay forma de saber a qué eje pertenece un término salvo mirando las listas. |
| | Un filtro por área, o por curso, tiene que enumerar a mano los IDs de su eje. |
| | Las capacidades declaradas no existen: la taxonomía es inadministrable por diseño. |

### Opción 2: una taxonomía por dimensión (elegida)

`evt_area`, `evt_type` y `evt_course`, cada una con su vocabulario cerrado, y
el estado fuera de las taxonomías.

| Pros | Contras |
|------|---------|
| El eje de un término es su taxonomía: no hay que deducirlo. | Tres cajas en la pantalla de edición en lugar de una. |
| Añadir un término no toca nada más. | La migración tiene que repartir 50 términos y 151 asignaciones en tres vocabularios. |
| `tax_query` por área, tipología o curso, sin listas de IDs. | Tres registros de taxonomía en lugar de uno (código mínimo, pero es código). |
| Cardinalidades distintas por eje, cada una con su interfaz. | |
| El área queda disponible como ámbito de permisos. | |

### Opción 3: `convocatoria` única y jerárquica, con un término raíz por eje

Mantener una taxonomía y colgar cada eje de un término padre —«Área»,
«Tipología», «Curso»—, filtrando por `parent`.

| Pros | Contras |
|------|---------|
| Un solo registro de taxonomía; migración más barata (basta reparentar). | Nada impide asignar el término padre, ni asignar dos ejes por error: la restricción no la hace WordPress, la haría código nuestro. |
| Compatible con los términos actuales sin renombrarlos. | Todo filtro tiene que saber qué raíz mirar: la lista de exclusión se cambia por una lista de padres. |
| | Los permisos son por taxonomía, no por rama: no se puede dejar que alguien administre las áreas y no las tipologías. |

### Opción 4: meta con listas cerradas en PHP, sin taxonomía

Guardar área, tipología y curso como `post_meta` validada contra listas
cerradas en código, igual que `evt_section_type`.

| Pros | Contras |
|------|---------|
| Vocabulario en Git, revisable, sin administración. | Añadir un área nueva exige desplegar código, y la lista de áreas crece. |
| Sin tablas de términos ni caché de taxonomías. | Se pierden archivo, filtros nativos y columnas del escritorio, gratis en una taxonomía. |
| Bien para vocabularios que no cambian. | El área no es de ese tipo: la crea la organización, no el equipo de desarrollo. |

## Decisión

**Haremos la opción 2.** La única `convocatoria` se parte en tres taxonomías,
y la cuarta dimensión deja de ser una taxonomía:

| Dimensión de hoy | Dónde va | Por qué |
|---|---|---|
| Tipología | `evt_type` | Vocabulario pequeño y estable que la organización sí amplía. |
| Curso escolar | `evt_course` | Un término por curso; se añade uno al año. |
| Área organizadora («Promociona») | `evt_area` | Es el eje de permisos y es multivalor. |
| Estado | **Ninguna**: se deriva de las fechas | Es la única dimensión que se puede calcular. |

- Las tres se registran sobre `evt_event`, jerárquicas y con
  `show_admin_column`, en `src/Evt/Taxonomy/EventTaxonomies.php`. Son
  jerárquicas a propósito, para que se presenten como casillas y no como campo
  libre: un vocabulario cerrado no debe ampliarse por una errata al teclear
  (`args()`, con `hierarchical` y `show_in_quick_edit => false`).
- Las capacidades son **capacidades que existen**: `evt_manage_app` para
  administrar, editar y borrar términos, y `edit_evt_events` para asignarlos
  (`src/Evt/Taxonomy/EventTaxonomies.php`). Es la corrección directa del fallo
  del registro anterior.
- **El estado no es un término.** Lo calcula `Domain/EventState` a partir de
  las fechas del evento, con tres valores cerrados —`proximo`, `abierto`,
  `finalizado`— en `src/Evt/Meta/EventMetaKeys.php`. El razonamiento
  completo, y qué pasa con los 32 eventos que hoy llevan
  `evento-finalizado` puesto a mano, está en
  [ADR-0005](ADR-0005-el-estado-del-evento-se-deriva-de-las-fechas.md).
- **«Promociona» no es una cuarta dimensión.** Medido contra el vocabulario, el
  campo ofrece exactamente los términos de área, así que su contenido migra a
  `evt_area`. Si además se quiso usar alguna vez para destacar un evento en la
  portada, eso es presentación derivada del estado y se trata en
  [ADR-0005](ADR-0005-el-estado-del-evento-se-deriva-de-las-fechas.md), no un
  eje del vocabulario.
- El área, una vez separada, es lo que sostiene los permisos: el acotado por
  área y su relación con los roles se deciden en
  [ADR-0006](ADR-0006-el-area-es-un-ambito-no-un-rol.md).

## Consecuencias

### Positivas

- Se acaban las listas de exclusión: 150 IDs a mano, duplicados en dos
  sitios, pasan a cero. Añadir un área o un curso es añadir un término.
- El eje de un término es evidente: está en su taxonomía, no en una lista de
  IDs dentro de la acción que crea la página.
- Filtrar los eventos de un área, de un curso o de una tipología es una
  `tax_query`, y la columna sale sola en el escritorio.
- Las taxonomías son administrables por alguien de verdad, cosa que hoy no
  ocurre: `evt_manage_app` existe y se concede; `edit_guides` no existía.
- El estado deja de poder mentir: hoy hay 32 páginas marcadas
  `evento-finalizado` y 2 `evento-abierto` porque alguien lo puso, no porque
  el calendario lo diga.

### Negativas

- Tres cajas en la pantalla de edición donde había una. Para quien solo
  rellenaba el formulario, es una pantalla nueva que aprender.
- La migración es la parte cara: hay que repartir 50 términos en tres
  vocabularios y rehacer 151 asignaciones, y de paso decidir qué se hace con
  los nombres heredados, que son un muestrario de criterios distintos: unos son
  siglas de tres o cuatro letras y otros, el nombre completo de una dirección
  general escrito entero en el slug. Se aborda en
  [ADR-0008](ADR-0008-migracion-de-los-eventos-historicos.md).
- `evt_course` crece un término al año y nadie lo recuerda hasta que falta.
  Es trabajo manual recurrente, pequeño pero real.
- Las 118 páginas hijas de hoy no llevan término, y el CPT **no impide** que
  una hija lleve el suyo propio: la taxonomía está registrada sobre
  `evt_event`, que es a la vez el evento y sus secciones. Mientras la
  clasificación viva en la raíz, toda consulta sobre una sección tiene que
  subir al padre. Ni el registro ni esta ADR lo resuelven; queda anotado como
  pendiente en [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md).
- Que las tres sean jerárquicas es una decisión de interfaz, no de modelo: hoy
  ninguna necesita jerarquía real. Si mañana se quisieran áreas anidadas —un
  centro dentro de una unidad mayor— el modelo lo permite, pero nadie lo ha
  pedido y nadie lo ha diseñado.

### Neutras

- Los 50 términos de `convocatoria` y sus 151 asignaciones siguen existiendo
  en producción hasta la migración: esta ADR no borra nada.
- El multivalor del área se conserva porque los datos lo exigen (6 páginas con
  dos áreas), no porque se haya diseñado así por si acaso.
- La taxonomía `convocatoria` seguirá registrada mientras siga activo el
  fragmento de código que la registra. Retirarlo es parte del plan de
  convivencia, no de esta decisión.
