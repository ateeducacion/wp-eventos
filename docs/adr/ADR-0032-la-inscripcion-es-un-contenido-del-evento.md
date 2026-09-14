---
id: ADR-0032
title: "La inscripción es un contenido del evento, y no se enseña en el escritorio"
status: Aceptada
date: 2026-09-14
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0006, ADR-0007, ADR-0017, ADR-0020, ADR-0026, ADR-0027, ADR-0031]
supersedes: [ADR-0007]
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0032: La inscripción es un contenido del evento, y no se enseña en el escritorio

## Estado

Aceptada (2026-09-14). **Sustituye a la
[ADR-0007](ADR-0007-las-inscripciones-siguen-en-el-sistema-anterior.md)**, que
dejaba las inscripciones en el gestor de formularios anterior durante la fase 1.

## Contexto

Quedaban tres decisiones encadenadas y solo estaban tomadas las dos primeras:

1. La [ADR-0027](ADR-0027-los-participantes-son-nuestros.md) decidió que los
   participantes son de este aplicativo y que **el sistema anterior no se lee**.
   Declaró la forma de una fila (`Participants::columns()`) y dejó el filtro
   `evt_participants` como puerta de entrada mientras no hubiera formulario.
2. La [ADR-0031](ADR-0031-el-formulario-de-inscripcion-es-nucleo-fijo-mas-preguntas.md)
   decidió **qué lleva dentro** ese formulario: un núcleo fijo de siete campos
   más una lista corta de preguntas propias del evento. Y dijo explícitamente
   que **dónde se guardan las respuestas no lo decidía ella**.
3. Falta esta: **dónde vive una inscripción**.

Mientras no se conteste, no hay una línea que escribir: el formulario no tiene
dónde guardar, la pestaña «Participantes» sigue preguntando a un filtro que en
desarrollo contesta el mu-plugin con datos inventados, y la
[ADR-0007](ADR-0007-las-inscripciones-siguen-en-el-sistema-anterior.md) sigue
diciendo por escrito lo contrario que la ADR-0027.

Hay además un obstáculo formal: `AGENTS.md` lleva como **regla dura** «no
registrar `evt_registration` ni `evt_session`». Es la regla que fijó la ADR-0007
y hay que levantarla aquí, o cualquier agente que lea el repositorio tiene
prohibido escribir esto.

Y hay una cosa que no es un detalle: **una inscripción son datos personales de
alguien que no trabaja aquí**. Nombre, identificador fiscal, correo, teléfono,
centro y, a través de las preguntas del evento, categorías que el código no
conoce —«intolerancias alimentarias» es un dato de salud, y la propia ADR-0031
lo dejó escrito como consecuencia negativa—. Dónde se guarda decide quién puede
verlo, y en WordPress lo decide sobre todo **qué puertas quedan abiertas**.

## Problema

¿Dónde vive una inscripción: en un tipo de contenido propio, en una tabla
propia, o colgando del contenido que ya existe? ¿Y cómo se evita que los datos
personales de quien se inscribe acaben alcanzables por caminos que nadie ha
mirado —el escritorio, la REST API, la búsqueda, las acciones en bloque—?

## Factores de decisión

- **El aplicativo se despliega pegando código.** No hay instalación, no hay
  `dbDelta()` en un hook de activación y no hay migraciones
  ([ADR-0001](ADR-0001-repo-entorno-desarrollo-no-plugin.md)). Cualquier
  solución que necesite crear una tabla necesita además contestar quién la crea,
  cuándo y qué pasa si falla.
- **Colgar del evento ya resuelve tres cosas.** Ponentes y actividades cuelgan
  por `post_parent` ([ADR-0026](ADR-0026-ponentes-y-actividades-cuelgan-del-evento.md)),
  y de ahí salen gratis el área a la que pertenece el contenido, el guardián que
  lo acota ([ADR-0006](ADR-0006-el-area-es-un-ambito-no-un-rol.md)) y el cierre
  de la edición cuando el evento pasa a histórico
  ([ADR-0017](ADR-0017-el-estado-historico-cierra-la-edicion.md)).
- **Esconder no es proteger.** Es la regla de la casa: lo que protege es
  `map_meta_cap`, no `pre_get_posts`. Pero al revés también vale: **una puerta
  que no se abre no hay que guardarla**. Un tipo de contenido que no se registra
  en el escritorio, ni en REST, ni en la búsqueda tiene menos superficie que
  vigilar que uno que sí.
- **La forma de la fila ya está declarada** y es un contrato
  ([ADR-0027](ADR-0027-los-participantes-son-nuestros.md)). Lo que se decida
  aquí tiene que seguir llenando esas seis columnas.
- **Una respuesta tiene que seguir significando lo mismo mañana.** La ADR-0031
  dejó abierto qué pasa cuando se retoca una pregunta con gente ya inscrita.
  Aquí se cierra, porque es una decisión de almacenamiento.
- **El filtro `evt_participants` no se retira.** Es la costura por la que un
  despliegue puede traer sus participantes de otro sitio, y es lo que permite
  que el panel y el CSV se prueben sin nada detrás.

## Alternativas consideradas

### Opción 1: una tabla propia

`wp_evt_registrations`, con sus columnas y sus índices.

| Pros | Contras |
|------|---------|
| Consultar por evento, contar y agrupar es una consulta, no un `meta_query`. | **Nadie puede crearla.** No hay activación de plugin; habría que crearla desde `init` comprobando en cada carga, y decidir qué pasa si el usuario de la base de datos no puede hacer `CREATE TABLE`. |
| El aforo se cuenta con un `COUNT(*)` barato. | Queda fuera de todo lo que WordPress ya sabe hacer: papelera, autor, fechas, capacidades, exportación de datos personales. |
| Los datos personales quedan lejos de `wp_posts`. | Cada cosa que el núcleo da hecha hay que escribirla: borrado, permisos, consultas. Es la capa que la ADR-0001 dice que no se añade sin escribir antes una ADR — y esta lo desaconseja. |

Descartada. El coste no está en crear la tabla: está en todo lo que deja de
venir de serie.

### Opción 2: un usuario de WordPress por persona inscrita

Cada inscripción crea una cuenta con rol mínimo.

| Pros | Contras |
|------|---------|
| La identidad es única de verdad: una persona, una cuenta. | **Multiplica el censo de cuentas del sitio por cada jornada**, y son cuentas que nadie va a usar ni a dar de baja. |
| Entrar a cambiar de taller sería iniciar sesión. | Una cuenta es acceso al sitio. Crear cuentas desde un formulario público es exactamente el tipo de puerta que no se abre sin una razón mayor. |
| | Una persona se inscribe a dos eventos: o se reutiliza la cuenta —y entonces hay que casar identidades y resolver qué pasa si cambió de centro— o se duplica. |

Descartada.

### Opción 3: guardarlo todo en una meta del evento

Una meta con la lista de inscripciones serializada.

| Pros | Contras |
|------|---------|
| Cero tipos de contenido nuevos. | **Dos inscripciones a la vez se pisan**: leer, añadir y escribir una meta no es atómico, y con la inscripción abierta eso pasa de verdad. |
| Se lee de una vez. | Una jornada de cuatrocientas personas es una meta que se lee entera para pintar una tabla y para contar una plaza. |
| | No hay papelera, ni autor, ni fecha, ni permiso por fila: no se puede borrar una sola inscripción sin reescribir el bloque. |

Descartada.

### Opción 4: un tipo de contenido que cuelga del evento, sin escritorio (elegida)

`evt_registration`, hijo del evento por `post_parent`, registrado **sin
interfaz de administración, sin REST, sin búsqueda y sin archivo**.

| Pros | Contras |
|------|---------|
| Hereda el área, el guardián y el cierre por histórico sin escribir nada. | Los datos personales viven en `wp_posts` y `wp_postmeta`, que es donde mira todo el mundo. |
| Papelera, autor, fecha y capacidades vienen de serie. | Contar plazas es un `meta_query`, no un `COUNT(*)`. |
| La única puerta es la pantalla del taller, y esa ya está guardada. | Un plugin de terceros que recorra tipos de contenido puede encontrarlo igual. |

## Decisión

**Una inscripción es un `evt_registration` que cuelga de su evento por
`post_parent`**, como ya cuelgan los ponentes y las actividades
([ADR-0026](ADR-0026-ponentes-y-actividades-cuelgan-del-evento.md)).

### Se registra cerrado, y eso es parte de la decisión

```php
'public'              => false,
'publicly_queryable'  => false,
'show_ui'             => false,
'show_in_menu'        => false,
'show_in_rest'        => false,
'exclude_from_search' => true,
'has_archive'         => false,
'rewrite'             => false,
'capability_type'     => array( 'evt_registration', 'evt_registrations' ),
'map_meta_cap'        => true,
```

No es esconder: es **no abrir**. Cada una de esas líneas es una puerta que no
hay que guardar después. La única pantalla desde la que se llega a una
inscripción es la pestaña «Participantes» del taller, y a esa se entra por
`EventAccess`, que acota por área
([ADR-0006](ADR-0006-el-area-es-un-ambito-no-un-rol.md)).

El `post_title` es **la referencia de la inscripción**, no el nombre de la
persona: el nombre es una meta como los demás campos. Es la diferencia entre un
dato que aparece en cualquier listado que recorra `wp_posts` y uno que hay que ir
a buscar.

### El núcleo son metas declaradas

Los siete campos de la [ADR-0031](ADR-0031-el-formulario-de-inscripcion-es-nucleo-fijo-mas-preguntas.md)
se declaran con `register_post_meta()`, con su tipo, su `sanitize_callback` y un
`auth_callback` que **devuelve `false`**: no hay ninguna vía por la que una meta
de inscripción se escriba desde fuera del aplicativo. La validación y la
normalización viven en `Domain/`, son puras y se prueban sin WordPress, como
`EventInput` y `ActivityInput`.

### Las preguntas están en el evento; las respuestas, en la inscripción

- La **lista de preguntas** es una meta del evento. Cada pregunta lleva un
  **identificador propio, inmutable, que se genera al crearla** y no vuelve a
  cambiar aunque se reescriba el rótulo.
- La **respuesta** se guarda en la inscripción **bajo ese identificador**, no
  bajo el rótulo.

Eso cierra lo que la ADR-0031 dejó abierto: **retocar el rótulo de una pregunta
no desconecta lo ya contestado**. Y se le pone la única regla que hace falta
para que una respuesta guardada siga significando lo mismo:

> Con la inscripción abierta, a una pregunta se le puede cambiar el rótulo y
> **añadir** opciones. No se le puede cambiar el tipo ni **quitar** una opción
> que alguien ya haya elegido.

Una pregunta borrada no borra las respuestas: dejan de exportarse y dejan de
verse. Recuperar la pregunta las devuelve.

### El aplicativo contesta su propio filtro

`evt_participants` **se queda**. Lo que cambia es que ahora hay alguien que lo
contesta de serie: el aplicativo devuelve sus `evt_registration` con la forma de
fila de `Participants::columns()`, y quien traiga los participantes de otro
sitio sigue pudiendo sustituirlos desde un snippet. El panel y el CSV no se
enteran de la diferencia, que es de lo que iba la
[ADR-0027](ADR-0027-los-participantes-son-nuestros.md).

### Lo que esta ADR levanta

- La [ADR-0007](ADR-0007-las-inscripciones-siguen-en-el-sistema-anterior.md)
  queda **sustituida**.
- La regla dura de `AGENTS.md` pasa a ser la contraria: `evt_registration` **se
  registra**; `evt_session` sigue sin existir, porque elegir taller no necesita
  un tipo de contenido propio —lo resuelve la
  [ADR-0033](ADR-0033-elegir-taller-aforo-duro-y-cambio-hasta-el-cierre.md)—.

## Consecuencias

### Positivas

- **Se acaba el formulario nuevo por evento.** Era el mecanismo que hacía crecer
  el problema con cada jornada, y desaparece: un evento se crea y ya tiene dónde
  inscribir.
- El acotado por área, el cierre por histórico y la papelera **no se escriben**:
  salen de colgar del evento.
- Borrar un evento se lleva sus inscripciones por delante, que es lo que tiene
  que pasar con los datos personales de un evento que ya no existe.
- El núcleo se valida en un sitio y se prueba sin WordPress.
- Una respuesta sigue unida a su pregunta aunque se reescriba el rótulo.

### Negativas

- **Los datos personales viven en `wp_posts` y `wp_postmeta`.** No se registra
  en el escritorio ni en REST, pero cualquier plugin que recorra tipos de
  contenido o haga una consulta directa los encuentra. Un tipo de contenido
  cerrado reduce las puertas conocidas; no hace invisible el dato.
- **No hay plazo de conservación.** Una inscripción se queda mientras exista su
  evento, y los eventos históricos no se borran
  ([ADR-0008](ADR-0008-migracion-de-los-eventos-historicos.md)). Quién borra qué
  y cuándo **esta ADR no lo decide**, y es una deuda con nombre: hasta que se
  decida, el aplicativo acumula datos personales sin fecha de caducidad.
- **Las respuestas a las preguntas siguen siendo datos que el código no
  entiende.** Guardarlas por identificador arregla que no se descoloquen; no
  arregla que el aplicativo no sepa que una de ellas es un dato de salud. La
  consecuencia negativa de la ADR-0031 sigue vigente entera.
- **Contar plazas es un `meta_query`.** Con una jornada de cientos de personas
  no se nota; con miles, se notará, y entonces habrá que medir antes de tocar
  nada.
- **La referencia como título hace la administración menos legible.** Quien mire
  la base de datos a pelo ve referencias, no personas. Es deliberado, y es
  incómodo.
- **Una persona inscrita no es una identidad.** Dos inscripciones con el mismo
  correo son dos filas sin relación. No hay «mis inscripciones», ni forma
  automática de saber que son la misma persona.
- **Sin escritorio, no hay red de seguridad.** Si la pantalla del taller falla o
  una capacidad está mal repartida, no queda una segunda vía por la que quien
  administra pueda mirar. Se gana superficie cerrada y se pierde el plan B.

### Neutras

- **Cómo se entra a cambiar de taller no lo decide esta ADR.** Que una persona
  vuelva a su inscripción sin tener cuenta en el sitio es de la
  [ADR-0033](ADR-0033-elegir-taller-aforo-duro-y-cambio-hasta-el-cierre.md).
- **Las inscripciones de los eventos ya celebrados no se tocan**: siguen donde
  estén, con la forma que tuvieran, mientras esos eventos sigan vivos
  ([ADR-0008](ADR-0008-migracion-de-los-eventos-historicos.md)).
- La meta histórica con el identificador del formulario anterior sigue donde
  está y **sigue marcada como histórica**: no se lee, y desaparecerá cuando
  desaparezca lo que la usa.

## Referencias

- [ADR-0007](ADR-0007-las-inscripciones-siguen-en-el-sistema-anterior.md) —
  sustituida por esta.
- [ADR-0027](ADR-0027-los-participantes-son-nuestros.md) — los participantes son
  nuestros; declara la forma de una fila y el filtro `evt_participants`.
- [ADR-0031](ADR-0031-el-formulario-de-inscripcion-es-nucleo-fijo-mas-preguntas.md)
  — qué lleva dentro el formulario.
- [ADR-0026](ADR-0026-ponentes-y-actividades-cuelgan-del-evento.md) — colgar del
  evento por `post_parent` y lo que eso resuelve solo.
- [ADR-0006](ADR-0006-el-area-es-un-ambito-no-un-rol.md) — el guardián único y el
  acotado por área.
- [ADR-0020](ADR-0020-el-consentimiento-es-una-casilla.md) — el consentimiento y
  su constancia.
- [ADR-0033](ADR-0033-elegir-taller-aforo-duro-y-cambio-hasta-el-cierre.md) —
  elegir taller, el aforo y la vuelta de quien ya se inscribió.
