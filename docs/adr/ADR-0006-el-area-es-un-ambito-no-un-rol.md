---
id: ADR-0006
title: "El área es un ámbito, no un rol"
status: Aceptada
date: 2026-09-12
related:
  issues: []
  prs: []
  sdds: [SDD-0001, SDD-0002]
  adrs: [ADR-0003, ADR-0004, ADR-0009, ADR-0012]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0006: El área es un ámbito, no un rol

## Estado

Aceptada

## Contexto

En el sitio actual **el área organizadora es un rol de WordPress**. De los
perfiles dados de alta, la mayoría **nombra un área, un servicio o una
dirección general** en lugar de nombrar una función. Las capacidades de cada
uno se leyeron una a una desde Members el 2026-09-12 y están anotadas en
`.local/` (investigación del sistema anterior). De esa lectura salen tres
hechos que no son interpretación:

1. **Ninguno de esos roles de área tiene una sola capacidad de escritura.** Ni
   `edit_pages`, ni `publish_pages`, ni `edit_others_pages`. Todos se quedan
   en `read`, `read_others_pages`, `read_others_posts` y, como mucho,
   `read_private_pages`. **Las áreas no editan WordPress**: rellenan un
   formulario desde el frontal y el contenido lo escribe en su nombre una
   plantilla del sistema anterior. El rol no les da permiso para nada; les da
   acceso a mirar.
2. **Un rol no sabe expresar «los eventos de esta área».** Un rol es un juego
   de permisos globales sobre el sitio; no dice de quién es cada cosa. Con
   este modelo no hay forma de escribir la consulta «los eventos de tal área»
   más que buscando el término de `convocatoria`, que es un dato **distinto**
   del rol y que nadie garantiza que coincida.
3. **Los nombres han derivado, porque nombran organización y la organización
   cambia.** Hay roles cuya etiqueta describe una tarea y cuyo slug nombra una
   unidad que ya no se llama así, y slugs con los acentos mal transliterados.
   Un slug de rol es permanente en la práctica: renombrarlo desasigna a quien
   lo tenga.

Mientras tanto, el área **también** existe como dato: **dos de cada tres
términos de la taxonomía `convocatoria`** son áreas organizadoras. Es decir,
el área está modelada **dos veces**, en dos sitios que no se hablan, y solo
uno de los dos se puede consultar.

Y hay una tercera copia, escrita a mano: un shortcode del sistema anterior
lleva la lista de roles autorizados literal en el código, en un `array()` con
tres nombres de rol dentro. Cada área nueva obligaría a editar ese array.

## Problema

¿El área organizadora se expresa como **rol de la persona** o como **dato del
evento**, y de qué depende entonces quién puede editar qué?

## Factores de decisión

- **Pertenencia frente a permiso.** Lo que hace falta responder es «¿este
  evento es de mi área?». Un rol responde «¿esta persona puede editar
  páginas?». Son preguntas distintas.
- **Cardinalidad y crecimiento.** Hay decenas de áreas vivas hoy y la
  organización se reordena cada poco. Un rol por área es una lista que solo
  crece.
- **Quién mantiene el dato.** En este sitio nadie: los roles de área se
  asignan a mano desde WPFront o Members.
- **Personas en dos áreas.** Con roles hace falta un segundo rol; con un dato
  multivalor es marcar dos casillas.
- **Fail-closed.** Editar el evento de otra área tiene que ser imposible por
  defecto, no por olvido.
- **Lo que hay que conservar.** Ninguno de los roles de área concede
  escritura, así que no hay permisos que respetar: no se rompe nada al
  dejarlos de usar.
- **Las pantallas preguntan por capacidades, no por roles.** Es lo que permite
  cambiar el reparto de roles sin tocar una línea: quien lee una capacidad no
  tiene que saber quién la lleva puesta.

## Alternativas consideradas

### Opción 1: reutilizar los roles de área que ya existen

En lugar de inventar roles nuevos, colgar las capacidades del aplicativo de los
roles que ya existen en el sitio y ya tienen gente dentro. Es una buena decisión
**cuando el rol expresa un cargo y lo mantiene alguien que no somos nosotros**,
y conviene decir exactamente por qué aquí no se da ninguna de las dos
condiciones, porque la tentación de reutilizar es fuerte:

| | Donde reutilizar funciona | Este aplicativo (esta ADR) |
|---|---|---|
| **Qué significa el rol** | Un **cargo**: una función que se ejerce. Y una función es justo lo que un rol sabe expresar. | Una **pertenencia organizativa**: «soy de tal área». Un rol no sabe expresar de quién es un contenido. |
| **Quién lo mantiene** | Una integración con el directorio corporativo **lo añade y lo retira en cada inicio de sesión**. Se mantiene solo. | Nadie. Se asignan a mano y se quedan como estén. |
| **Cuántos son** | Un puñado, estables. | **Uno por área, y decenas de áreas en la taxonomía**, subiendo con cada reorganización. |
| **Qué traen puesto** | Capacidades coherentes con lo que se les va a pedir; el aplicativo solo **añade** las suyas. | **Ninguna capacidad de escritura.** «Reutilizarlos» sería reescribirlos enteros. |
| **Coste de un alta nueva** | Cero: el rol lo pone la integración. | Un rol nuevo, creado a mano, más su línea en cada lista de roles escrita a mano dentro de un shortcode. |

Dicho corto: reutilizar un rol sale bien cuando el rol lo pone otro y significa
un cargo; aquí lo pondríamos nosotros y significaría un organigrama. Descartada.

### Opción 2: un rol por área, creado por el aplicativo

Un `evt_area_<slug>` por cada área, con las capacidades del CPT repartidas
por rol.

- A favor: no hay que tocar el perfil de nadie; el área se ve en la columna
  «Rol» de la lista de usuarios.
- En contra: **sigue sin expresar de quién es el evento**. Habría que
  comprobar el rol de quien edita contra el término del evento, que es
  exactamente lo que hace la opción elegida, pero con un vocabulario
  duplicado y decenas de roles de más. Cada reorganización sería un despliegue
  del bundle. Descartada.

### Opción 3: user meta libre y post meta copiada

Un vocabulario cerrado escrito en el código, el ámbito de la persona en una user
meta y el del registro congelado en una post meta al crearlo. Es un patrón
conocido y funciona bien con pocos valores.

- A favor: funciona, está probado, y tiene una virtud que conviene copiar
  (congelar el ámbito en el registro para que un cambio de área no reescriba
  el histórico).
- En contra: ese patrón encaja cuando el vocabulario son **un puñado de
  valores que cambian una vez al año** y cada uno arrastra consigo una
  plantilla, así que un valor nuevo es un despliegue de todos modos. Aquí son
  **decenas de áreas** que no arrastran nada, y el vocabulario **ya existe poblado** en `convocatoria`: habría que
  copiarlo a un array y volver a copiarlo con cada alta. Además es plano:
  pierde la jerarquía Dirección General → Servicio → Área, que en la
  organización es real. Descartada.

### Opción 4: control de lectura post a post con `_members_access_role`

El patrón que ya vive en varios sitios de la instalación de destino: una post
meta multivalor con los roles que pueden ver cada contenido.

- A favor: existe, está en uso y hay interfaz de edición en lote.
- En contra: controla **lectura**, no edición; no acota un listado del
  escritorio; no restringe la creación; y hay que acordarse de marcarlo en
  cada evento nuevo. Sirve como complemento para esconder una satélite
  concreta, no como modelo de ámbito. Descartada.

### Opción 5: un rol intermedio que coordine todas las áreas

Un `evt_coordinator` idéntico al de área salvo por `evt_edit_all_areas`, para
quien coordina eventos de varias áreas sin administrar el sitio.

Descartada, y conviene dejar escrito por qué, porque es la opción que vuelve
sola cada vez que alguien mira la tabla de roles:

1. **Un rol que se distingue de otro por una sola capacidad, y esa capacidad es
   «saltarse el ámbito», no describe una función: describe *no tener ámbito*.**
   Y no tener ámbito ya tenía nombre en WordPress antes de que existiera este
   aplicativo, y es `administrator`.
2. **Un rol menos es una fila menos que mantener en cuatro sitios a la vez** —el
   snippet de roles, el reparto de capacidades de los tres tipos de contenido,
   el editor de roles y esta documentación—, y los cuatro se desincronizan por
   separado.
3. **Coordinar no es una función distinta de administrar, aquí.** Quien coordina
   todas las áreas del sitio y quien lo administra son, en la práctica, el
   mismo puñado de personas. Es el mismo argumento con el que esta ADR
   descarta un tercer rol para `evt_manage_app`.
4. **Un rol intermedio invita a repartir mal.** Con tres roles, la pregunta «¿a
   quién le doy esto?» tiene una respuesta cómoda y equivocada en medio. Con
   dos hay que decidir: o es del área, y entonces se acota por `evt_area`, o es
   de la plataforma, y entonces es de administración.

Si en algún sitio hiciera falta de verdad el escalón intermedio, la vía **no**
es reinventar el rol: es conceder `evt_edit_all_areas` a una persona concreta
desde WPFront, que el snippet respeta porque nunca revoca nada.

### Opción 6: taxonomía `evt_area` + user meta `evt_area` + un rol de área (elegida)

El área es un término; la pertenencia de una persona es un dato de su perfil; y
el aplicativo aporta **un** rol, el del área. Lo que está por encima del área ya
lo nombra WordPress: `administrator`.

## Decisión

**El área es un ámbito: un término de `evt_area` en el evento y una user meta
`evt_area` en la persona. El aplicativo aporta un solo rol propio.**

**El área del evento** es un término de la taxonomía jerárquica `evt_area`
(`EventTaxonomies::AREA`), registrada sobre
`evt_event` y administrable con capacidades que existen de verdad — al
contrario que `convocatoria`, que se registró exigiendo
`edit_guides`/`publish_guides`, **dos capacidades que no existen en el sitio**,
razón por la cual hoy nadie puede administrar sus términos.

**La pertenencia de la persona** es la user meta `evt_area`, con uno o varios
`term_id` (`EventAccess::USER_AREA_META`, leída por `user_areas()`). Se edita
en el perfil, con las áreas sangradas por jerarquía
(`evt_render_profile_fields()`), y **solo la puede tocar quien
administra** (`evt_can_edit_admin_only_fields()`,
`snippets/roles-and-profiles.php`): WordPress deja a cualquiera editar su
propio perfil, así que un campo de área editable por su dueño es una puerta
para asignarse el área de otro.

**Quién hay, y son dos** (`snippets/roles-and-profiles.php`,
`evt_role_definitions()`):

| Slug | Etiqueta | Quién | Qué hace |
|---|---|---|---|
| `evt_organiser` | Organización de eventos | El personal de un área | Todo lo de **su área**: eventos, secciones, ponentes, actividades, talleres, participantes, apariencia y el **CSS a medida** ([ADR-0014](ADR-0014-css-del-area-javascript-de-administracion.md)). Marca sus eventos como históricos ([ADR-0017](ADR-0017-el-estado-historico-cierra-la-edicion.md)) |
| `administrator` | El de siempre de WordPress | Quien administra el sitio | Todo, en todas las áreas. Y lo único reservado: el **JavaScript a medida**, los ajustes del aplicativo y **desarchivar** |

`evt_edit_all_areas` y `evt_manage_app` son del `administrator` **y de nadie
más**: salirse del área y tocar los ajustes del aplicativo no es organizar un
evento.

El aplicativo aporta **un rol propio y no dos** porque lo que está por encima
del área ya lo nombra WordPress; el razonamiento entero está en la opción 5 de
las alternativas.

Las capacidades de los tres tipos de contenido las reparte el aplicativo, no el
snippet (`src/Evt/PostType/EventPostType.php`,
`grant_caps_to_roles()`), de modo que el rol declara **quién es** y el CPT
declara **qué se puede hacer**.

**Y lo importante, que es lo que hace barato cambiar de opinión sobre los
roles:** los permisos se preguntan siempre por **capacidad**, nunca por nombre
de rol. Quien lea `evt_edit_all_areas` en el código no tiene que saber quién la
lleva; por eso el acotado por área no cambia aunque cambie el reparto.

**Fail-closed.** `EventAccess::can_edit()` deniega a quien no tiene ningún
área en su perfil y no tiene `evt_edit_all_areas`
(`can_open()`, `src/Evt/Access/EventAccess.php`). Abrir cuando falta el dato es
justamente cómo un área acaba tocando los eventos de otra. La única excepción
está escrita y acotada: un evento **recién creado todavía no tiene área**, y
lo edita quien lo creó, que es quien tiene que ponérsela
(`src/Evt/Access/EventAccess.php`).

**Las páginas satélite no llevan área propia**: manda la del evento raíz
(`post_areas()` → `root_id()`, `src/Evt/Access/EventAccess.php`). Es
lo que evita que una hija quede huérfana de permisos al moverla.

**El rol `evt_coordinator` se retira una sola vez.** El aplicativo llegó a
tener un rol de coordinación y ya no lo tiene. Como el snippet de roles es
aditivo a propósito y nunca llama a `remove_cap()` ni a `remove_role()`, el rol
se quedaría para siempre en la base de datos de cualquier entorno ya
aprovisionado, **concediendo `evt_edit_all_areas`**, que es justo lo que se
retira. Lo quita `evt_retire_coordinator_role()`
(`snippets/roles-and-profiles.php`), con **opción de guarda**
—`evt_coordinator_role_retired`—: quitarlo en cada carga desharía en silencio
cualquier decisión posterior de quien administra desde WPFront, y convertiría el
snippet en destructivo. A quien lo tuviera se le pone antes `evt_organiser`,
para que nadie se quede sin ningún rol —una cuenta que entra y no puede hacer
nada, sin rastro de por qué—; hasta que se le ponga su área en el perfil no
edita nada, porque el acotado falla en cerrado.

**Los roles de área existentes no se borran.** El snippet de roles es aditivo
y nunca quita capacidades (`evt_register_roles()`,
`snippets/roles-and-profiles.php`): se quedan donde están, sin capacidades del
aplicativo, y decidir si se retiran es una operación aparte que exige repasar
quién los tiene. Lo mismo con el rol que hoy da acceso a las inscripciones,
que sigue haciendo falta mientras las inscripciones vivan en el sistema
anterior (ADR-0007).

**Los términos de área de `convocatoria` se migran a `evt_area`**
([ADR-0004](ADR-0004-una-taxonomia-por-dimension.md),
[ADR-0008](ADR-0008-migracion-de-los-eventos-historicos.md)).

## Consecuencias

### Positivas

- **«Los eventos de mi área» pasa a ser una consulta.** Hoy no se puede
  escribir; con un término en el evento es una `tax_query`, y es la misma que
  acota la lista del escritorio, la portada y las exportaciones.
- **Un área nueva es un término**, creado desde el escritorio en diez
  segundos. No es un rol, no es un despliegue del bundle y no es una línea
  más en el array de un shortcode.
- **Una persona puede pertenecer a dos áreas** marcando dos casillas
  (`evt_render_profile_fields()`). Con roles serían dos roles y dos
  juegos de permisos que se suman de formas que nadie revisa.
- La jerarquía Dirección General → Servicio → Área se puede expresar, porque
  la taxonomía es jerárquica (`EventTaxonomies::args()`),
  aunque los términos de hoy estén planos.
- El reparto de permisos deja de depender de listas de roles escritas a mano
  dentro de shortcodes: quien decide es una capacidad y un término.
- **Un rol propio en la lista de WPFront en lugar de uno por área**, y encima
  del área el `administrator` de siempre: quien administra ve de un vistazo
  qué hace cada uno, porque el nombre lo dice.
- **La raya se explica en una frase** —o es de tu área, o es de
  administración—, sin un escalón intermedio que nadie sabe cuándo usar.

### Negativas

- **El día del despliegue nadie edita nada hasta que se rellenen los
  perfiles.** Fail-closed es eso. Hay que repasar el perfil de todas las
  personas que hoy llevan un rol de área y ponerle a cada una su término. No
  hay atajo automático: es una tarea manual de la puesta en marcha, y si no se
  hace, el aplicativo parece roto.
- **La correspondencia rol → término no es 1:1 y hay que resolverla a
  mano.** Algunos roles tienen un término del mismo nombre y son fáciles.
  Otros están etiquetados por la tarea que hacen y no nombran ningún área, así
  que hay que averiguar a cuál pertenecen; y hay dos roles distintos que
  apuntan al mismo término. Eso lo decide una persona con el organigrama
  delante, no un guion de migración.
- **La user meta `evt_area` no la mantiene nadie automáticamente.** Donde
  reutilizar roles funciona, el rol lo pone el directorio corporativo en cada
  inicio de sesión; aquí, quien
  cambia de área conserva la suya hasta que alguien lo corrija, y quien se va del
  organismo conserva permiso de edición sobre los eventos de su antigua área
  hasta que se le retire la cuenta o el rol. Es una tarea humana recurrente y
  conviene decirlo antes de desplegar, no después.
- **El área deja de verse en la columna «Rol» de la lista de usuarios.**
  Hoy, con todos sus defectos, esa columna dice de qué área es cada persona.
  A partir de ahora hay que abrir el perfil, o añadir una columna propia, que
  no está escrita a 2026-09-12.
- **Quien coordine sin administrar el sitio necesita `administrator`**, que
  abre bastante más que `evt_edit_all_areas`. La salida sin reinventar el rol
  está escrita en la opción 5: conceder esa capacidad a una persona concreta
  desde WPFront.
- Un evento **puede quedarse sin área** si quien lo crea no la marca. La
  regla del evento recién creado (`EventAccess::can_open()`) lo hace editable
  solo por su autor, lo que limita el daño, pero un evento sin área no
  aparece en el listado acotado de nadie más. Hace falta un aviso en el
  editor y una comprobación al publicar; están pendientes en
  [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md).

### Neutras

- Los roles se siguen revisando en WPFront User Role Editor y en Members,
  como hoy. Nada cambia en el procedimiento de administración de usuarios,
  solo qué hay dentro de la lista.
- Administración queda exenta del acotado: `evt_manage_app` y
  `manage_options` pasan por encima (`EventAccess::is_manager()`).
  Es deliberado y es lo que permite arreglar un evento mal asignado.
- Los slugs de los roles nuevos van en inglés y sus etiquetas en castellano,
  según [ADR-0009](ADR-0009-identificadores-internos-en-ingles.md). Los roles
  viejos conservan sus slugs con acentos mal transliterados: no se renombran
  porque renombrar un slug desasigna a quien lo tenga.
