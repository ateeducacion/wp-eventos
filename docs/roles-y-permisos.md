---
id: ROLES-Y-PERMISOS
title: "Roles y permisos del aplicativo de eventos"
status: Aceptada
date: 2026-09-13
related:
  issues: []
  prs: []
  adrs: [ADR-0006, ADR-0003, ADR-0004, ADR-0008, ADR-0009, ADR-0012, ADR-0013, ADR-0014, ADR-0017]
  sdds: [SDD-0001, SDD-0002]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# Roles y permisos del aplicativo de eventos

Qué rol necesita cada persona, qué capacidades lleva cada rol y qué campo del
perfil hace falta para que vea y edite lo que le toca. Es la lista que hay que
reproducir en **WPFront User Role Editor 4.2.4** en el sitio donde se despliega
(Members 3.2.22, que también está instalado, enseña los mismos roles y las
mismas capacidades). La fuente de verdad en código está repartida en dos
sitios, y conviene saberlo antes de buscar:

| Qué | Dónde |
|---|---|
| El rol del aplicativo y sus capacidades propias | `evt_role_definitions()`, en `snippets/roles-and-profiles.php` |
| Lo que ningún rol del aplicativo puede tener nunca | `evt_forbidden_role_caps()`, en `snippets/roles-and-profiles.php` |
| Las capacidades de los tres tipos de contenido | `EventPostType::grant_caps_to_roles()`, en `src/Evt/PostType/EventPostType.php` |
| Quién edita qué evento, ponente o actividad en concreto | `EventAccess`, en `src/Evt/Access/EventAccess.php` |

Están separadas a propósito: el snippet de roles es un fichero suelto que se
activa antes que nada (`init` prioridad 5, `roles-and-profiles.php`) y el
mapa de capacidades del CPT lo escribe quien registra el CPT (`init`
prioridad 11, `src/Evt/App.php`), para no tener el mismo mapa escrito dos
veces en sitios que se separan.

**Los dos reponen capacidades en cada carga.** `evt_register_roles()`
(`roles-and-profiles.php`) crea el rol si falta y le añade la capacidad
si le falta; `grant_caps_to_roles()` (`EventPostType.php`) hace lo
mismo con las del CPT. Ninguno de los dos llama nunca a `remove_cap()`. Las
consecuencias, en las dos direcciones:

- **Quitar** una capacidad de esta lista a un rol hay que hacerlo **en el
  código**. Si se quita solo en WPFront o en Members, vuelve en la siguiente
  carga de la página y nadie se entera de por qué.
- **Añadir** capacidades extra desde WPFront **sí se respeta**: no hay
  revocación genérica que las borre. Es la vía para una concesión puntual a
  una persona o a un área.

---

## Parte A — De dónde se parte (2026-09-12)

Esta parte no describe el aplicativo: describe el sitio que se sustituye tal y
como estaba el día de la foto, leído perfil a perfil desde Members. Se conserva
porque es contra esto contra lo que hay que migrar, y porque explica por qué el
modelo nuevo es como es. El inventario completo, perfil a perfil y con el método
de lectura, está en `.local/`.

Lo que se encontró es una lista larga de perfiles dados de alta, con sus
capacidades leídas una a una. La mayoría no nombra una función sino un área, un
servicio o una dirección general; entre todos reúnen a un puñado de cuentas, y
el resto del censo del sitio son suscriptores.

**Tres conclusiones, que no son opinión sino lectura de ese inventario:**

1. **Ningún rol de área tiene una sola capacidad de escritura.** Ni
   `edit_pages`, ni `publish_pages`, ni `edit_others_pages`. Todos se quedan
   en `read`, `read_others_pages` y `read_others_posts` y, como mucho,
   `read_private_pages`. Las áreas **no editan WordPress**: rellenan desde el
   frontal el formulario del sistema anterior y el contenido de la página lo
   escribe en su nombre la vista que lo interpreta. El rol no les autoriza a
   nada; les da acceso a mirar.
2. **El área se modela como rol, y un rol no sabe decir «los eventos de esta
   área».** Un rol es un juego de permisos globales sobre el sitio; no expresa
   pertenencia. Cada área nueva obliga a crear un rol nuevo, y para saber de
   quién es un evento hay que mirar un dato **distinto** —el término de
   `convocatoria`—, que nadie garantiza que coincida con el rol de quien lo
   creó. Es el motivo de
   [ADR-0006](adr/ADR-0006-el-area-es-un-ambito-no-un-rol.md).
3. **Los nombres han derivado.** Hay perfiles cuyo slug dice una cosa y cuya
   etiqueta dice otra: el que se rotula «Edición de eventos» y el que se rotula
   «Gestión de inscripciones» no se llaman así por dentro, y ya nadie sabe cuál
   de los dos nombres es el bueno. Hay además slugs con los acentos mal
   transliterados, que es lo que pasa cuando el slug se genera a partir de un
   rótulo en castellano.

Un cuarto hecho que no sale del inventario de roles pero condiciona todo lo
anterior: hay un fragmento de código pegado a mano, activo en **todas** las
peticiones del sitio, que toca cómo se resuelve `unfiltered_html`, sin condición
de formulario.

Y conviene decir con precisión qué hace, porque es fácil contarlo peor de lo que
es: **sustituye el mapeo de la capacidad, no la concede**. A quien no la trae
como capacidad primitiva en su rol no le llega —comprobado ejecutándolo, con un
usuario de cada rol—. Lo que sí hace es devolvérsela a quien ya la tendría,
saltándose la regla con la que el núcleo la reserva en una red. Es **un salto de
una política del núcleo, no una puerta abierta al público**: el riesgo real es
el que se asume al dar permisos de publicación, no uno añadido. El
aplicativo nuevo no concede esa capacidad en ningún sitio, ni directa ni
indirectamente.

---

## Parte B — El modelo del aplicativo

### Roles: son dos, y solo uno es nuestro

| Rol (slug) | Etiqueta | Quién | Qué ve | Campo del perfil imprescindible |
|---|---|---|---|---|
| `evt_organiser` | Organización de eventos | Personal de un área que crea y publica los eventos de **su** área | Escritorio → Eventos, Ponentes, Actividades, acotado a su área | **`evt_area`**: una o varias áreas. Sin ninguna, no ve ni edita nada |
| `administrator` | Administrador | Administración del subsitio | Todo, en todas las áreas, más «Ajustes» del aplicativo | — |

**`evt_organiser` es nuevo**: ninguno de los perfiles de hoy se reutiliza,
porque ninguno expresa una función y ninguno tiene capacidades de escritura
(§A, conclusiones 1 y 2). Los perfiles de hoy se conservan mientras siga vivo el
formulario del sistema anterior y se retiran con él. Está definido en
`snippets/roles-and-profiles.php`.

**`administrator` no se crea**: es el rol de siempre de WordPress y el
aplicativo se limita a colgarle sus capacidades propias
(`evt_register_roles()`).

**Y no hay un tercer rol, ni de coordinación ni de administración del
aplicativo.** Lo hubo —`evt_coordinator`, «Coordinación de eventos»— y se retiró
al ordenar la documentación
([ADR-0006](adr/ADR-0006-el-area-es-un-ambito-no-un-rol.md), opción 5). El
motivo, en una línea: **la única diferencia entre coordinar y
organizar era una capacidad, `evt_edit_all_areas`**, y un rol que se distingue de
otro solo por saltarse el ámbito no está nombrando una función: está nombrando
*no tener ámbito*, que es lo que en WordPress ya se llama `administrator`. Es el
mismo argumento con el que nunca se creó un rol para `evt_manage_app`: quienes
administran el sitio ya lo pueden todo, y una fila más en la tabla de roles es
una fila más que se desincroniza en cuatro sitios a la vez.

Si en algún momento hace falta una persona que llegue a todas las áreas sin ser
administradora del subsitio, **la vía no es reinventar el rol**: es concederle
`evt_edit_all_areas` desde WPFront a esa cuenta concreta. El snippet nunca
revoca, así que la concesión se respeta y queda visible en el diagnóstico.

Una persona puede tener varios roles. Las pantallas preguntan siempre por
capacidad, nunca por nombre de rol, y por eso quitar un rol no ha costado ni una
línea de `EventAccess`.

### Capacidades por rol

Además de `read` y `upload_files`, que lleva `evt_organiser`. Las dos columnas
se leen «tiene la capacidad», no «puede hacerlo con cualquier evento»: el
acotado por área es una segunda comprobación y va en la sección siguiente.

| Capacidad | `evt_organiser` | `administrator` | Qué abre |
|---|:-:|:-:|---|
| `edit_evt_events` | ✓ | ✓ | Entrar a la lista de eventos y al botón «Añadir evento» |
| `edit_others_evt_events` | ✓ | ✓ | Editar el evento que creó otra persona |
| `edit_published_evt_events` | ✓ | ✓ | Tocar un evento ya publicado, y despublicarlo |
| `edit_private_evt_events` | | ✓ | Editar un evento en privado |
| `publish_evt_events` | ✓ | ✓ | Publicar |
| `read_private_evt_events` | ✓ | ✓ | Ver los eventos en privado |
| `delete_evt_events` | ✓ | ✓ | Enviar a la papelera lo propio en borrador |
| `delete_published_evt_events` | ✓ | ✓ | Enviar a la papelera lo ya publicado |
| `delete_others_evt_events` | | ✓ | Enviar a la papelera lo de otra persona |
| `delete_private_evt_events` | | ✓ | Enviar a la papelera lo privado |
| `evt_edit_custom_css` | ✓ | ✓ | El campo de **CSS a medida** del evento y de cada sección |
| `evt_edit_all_areas` | | ✓ | Saltarse el acotado por área |
| `evt_manage_app` | | ✓ | «Ajustes y diagnóstico»; crear y editar términos de las tres taxonomías; **desmarcar** un evento histórico |
| `evt_edit_custom_js` | | ✓ | El campo de **JavaScript a medida**; en multisitio exige además `unfiltered_html` |

Y las de ponentes y actividades, donde la organización **sí** se lleva el
juego entero. Un área gestiona su evento entero, y eso incluye dar de alta a
quien participa y montar el programa sin pedirle permiso a nadie:

| Capacidad (`X` = `evt_speakers` o `evt_activities`) | `evt_organiser` | `administrator` | Qué abre |
|---|:-:|:-:|---|
| `edit_X` | ✓ | ✓ | Entrar a la lista y al botón «Añadir» |
| `edit_others_X` | ✓ | ✓ | Editar la ficha que escribió otra persona |
| `edit_published_X` | ✓ | ✓ | Tocar una ficha ya publicada |
| `edit_private_X` | ✓ | ✓ | Editar una ficha en privado |
| `publish_X` | ✓ | ✓ | Publicar |
| `read_private_X` | ✓ | ✓ | Ver las fichas en privado |
| `delete_X` | ✓ | ✓ | Enviar a la papelera lo propio en borrador |
| `delete_published_X` | ✓ | ✓ | Enviar a la papelera lo ya publicado |
| `delete_others_X` | ✓ | ✓ | Enviar a la papelera lo de otra persona |
| `delete_private_X` | ✓ | ✓ | Enviar a la papelera lo privado |

Las tres familias salen del mismo mapa, `EventPostType::cap_map()`
(`EventPostType.php`), y se reparten en el mismo bucle
(`EventPostType.php`); lo que cambia es la lista de claves que le toca
a cada rol en cada tipo (`EventPostType.php`). Son **39 capacidades**
de contenido en total (13 × 3) más las cuatro propias del aplicativo:
`evt_manage_app`, `evt_edit_all_areas`, `evt_edit_custom_css` y
`evt_edit_custom_js`.

**Por qué la organización tiene menos del evento que del ponente.** Del evento
se le reservan a la administración `delete_others_`, `edit_private_` y
`delete_private_`: un evento es la portada de una convocatoria pública, y
tirarle a la papelera el de una compañera es un estropicio con visitas. Un
ponente y una actividad son fichas de trabajo interno del área, y ahí la
puerta se abre entera: el acotado por área ya impide llegar a las de otra.

Cuatro precisiones sobre WordPress que es donde se falla, y una advertencia:

- **«Despublicar» no es una capacidad.** Pasar de `publish` a `draft` es un
  `edit_post` sobre un post publicado: pide `edit_published_evt_events`, y
  `edit_others_evt_events` si no es suyo. `publish_evt_events` solo hace falta
  para publicar. Un rol con `publish_` y sin `edit_published_` publica una vez
  y ya no puede volver a tocarlo.
- **No se declara `create_posts`** en el mapa (`EventPostType::register()`).
  Sin declararla, WordPress usa `edit_evt_events` para «Añadir nuevo». Es
  deliberado: declarar una capacidad que no se concede a nadie es la forma
  habitual de cerrar el botón de crear sin querer.
- **La papelera de un evento publicado** pide `delete_published_evt_events`,
  no `delete_evt_events`. Las dos están en la lista de la organización.
- **Tres capacidades del mapa son inertes.** `cap_map()` incluye las claves
  `edit_post`, `read_post` y `delete_post`, que se traducen a
  `edit_evt_event`, `read_evt_event` y `delete_evt_event` —en **singular**—, y
  `grant_caps_to_roles()` se las concede a los roles. Con
  `map_meta_cap => true` esas tres son *meta* capacidades: WordPress las
  resuelve a las primitivas y nunca las consulta contra el rol. Que estén en
  el rol no hace daño, pero no autoriza nada. Aparecerán en WPFront: son nueve
  filas (tres por tipo de contenido) que no significan lo que parecen.
- `evt_organiser` **no** lleva `evt_manage_app`, y no puede llevarla: está
  en `evt_forbidden_role_caps()`. Un área gestiona su evento entero,
  pero no entra en «Ajustes» ni crea términos de `evt_area`, `evt_type` o
  `evt_course`: las tres taxonomías exigen `evt_manage_app` para
  `manage_terms`, `edit_terms` y `delete_terms`
  (`EventTaxonomies::args()`). Asignar términos ya
  existentes sí lo puede hacer cualquiera con `edit_evt_events`
  (`assign_terms`). Es lo contrario de lo que pasa hoy, donde `convocatoria`
  pide `edit_guides`/`publish_guides`, dos capacidades que **no existen en el
  sitio**, y por eso nadie salvo el super administrador puede asignar un
  término (comprobado en el código del sistema anterior; el material está en
  `.local/`).

### La raya: dónde acaba «su evento entero»

Un área gestiona su evento entero, así que conviene tener escrito dónde acaba
«entero». Son **dos** capacidades, y ninguna de las dos llega a `evt_organiser`:

| Capacidad | Por qué se queda en `administrator` |
|---|---|
| `evt_edit_custom_js` | El campo de JavaScript a medida. **Se ejecuta en el navegador de cada visitante**, con su sesión. Es el poder que WordPress protege con `unfiltered_html` |
| `evt_manage_app` | «Ajustes y diagnóstico», el alta de términos de las tres taxonomías y **desmarcar** un evento histórico ([ADR-0017](adr/ADR-0017-el-estado-historico-cierra-la-edicion.md)). Quien organiza **usa** las áreas que hay; crearlas es administrar el aplicativo. Marcar un evento como histórico sí lo hace el área: lo que se reserva a administración es **reabrirlo** |

`evt_edit_custom_css` **no** está en esta lista
([ADR-0014](adr/ADR-0014-css-del-area-javascript-de-administracion.md)). El
apartado siguiente explica por qué la raya cae entre los dos campos de código y
no delante de los dos.

Las dos están **nombradas** en `evt_forbidden_role_caps()`
(`roles-and-profiles.php`) y no solo omitidas del reparto: lo que no se
nombra no se comprueba. `evt_roles_status()` (`roles-and-profiles.php`)
las busca en cada rol del aplicativo y devuelve en `forbidden` las que
encuentre, y «Eventos → Ajustes» lo pinta. El snippet nunca llama a
`remove_cap()` —es aditivo a propósito—, así que avisa y quien administra
decide. `unfiltered_html` va en la misma lista por lo mismo.

### El CSS y el JavaScript a medida: por qué son dos capacidades y no una

Las dos capacidades
([ADR-0014](adr/ADR-0014-css-del-area-javascript-de-administracion.md)) abren los dos campos de código del evento y de cada
sección, `evt_custom_css` y `evt_custom_js`. Y **no se reparten igual**:

| Capacidad | `evt_organiser` | `administrator` |
|---|:-:|:-:|
| `evt_edit_custom_css` | ✓ (en su área) | ✓ |
| `evt_edit_custom_js` | | ✓ |

**Este es el apartado que hay que poder repetir sin mirarlo**, porque es la
pregunta que va a hacer todo el mundo:

> **El CSS cambia cómo se ve una página. El JavaScript ejecuta código en el
> navegador de cada visitante.**

No son el mismo riesgo, y por eso son dos capacidades y no una. Lo que cambia
cuando algo va mal:

| | CSS a medida | JavaScript a medida |
|---|---|---|
| Qué puede hacer como mucho | dejar la página fea, torcida o ilegible | leer la sesión de quien mira, mandar a otro servidor lo que teclea, cambiar adónde va un formulario |
| A quién alcanza | a quien mira esa página, mientras la mira | a quien mira esa página **y a su cuenta**, también después |
| Cómo se deshace | vaciando el campo; el daño se va con la recarga | vaciando el campo, y además averiguando qué llegó a hacer mientras estuvo puesto |
| Se puede revisar leyéndolo | sí, aunque sea largo | no de forma fiable: se ofusca, se minifica y puede traerse más código de fuera |
| Qué protege WordPress con `unfiltered_html` | nada de esto | exactamente esto |

Quien organiza una jornada y se equivoca escribiendo CSS **rompe su propia
página y lo ve al recargar**. Quien copia y pega JavaScript de un foro puede
comprometer a cualquiera que visite el evento sin que nadie se entere. Por eso
el CSS es del área —que es quien maqueta, y a quien el panel «Apariencia» se le
queda corto un día de cada tantos— y el JavaScript no.

Se reparten en `EventPostType::grant_code_caps()` (`EventPostType.php`),
que está **fuera** del bucle que reparte las del tipo de contenido a propósito:
ese reparte la misma lista a todos, y estas dos no van a los mismos sitios.

**En la pantalla se nota así**: el CSS es un campo normal de la pestaña
«Código», con su ayuda; el JavaScript va dentro del recuadro amarillo de «Solo
administración», que a partir de ahora envuelve **solo** ese campo. La pestaña
«Código» la ve también el área —lo que cambia es qué hay dentro—, y a quien no
ve el campo de JavaScript se le dice en una línea por qué no está, en vez de
dejarle un hueco que se lee como una avería.

Cuatro precisiones, todas comprobables:

- **La capacidad no basta.** `EventAccess::can_edit_custom_css()`
  (`src/Evt/Access/EventAccess.php`) exige además poder editar ese evento,
  con su acotado por área. Tenerla no abre los eventos de otra área.
- **En multisitio, el JavaScript pide también `unfiltered_html`**
  (`EventAccess.php`). WordPress se la quita a quien administra un
  subsitio y se la reserva a la superadministración de red: aquí se respeta esa
  decisión en vez de rodearla. En un subsitio eso significa que se puede tener
  el CSS y **no** el JavaScript, y la pantalla lo explica en vez de dejar un
  hueco.
- **La excepción se abre con un filtro, no con una casilla.**
  `apply_filters( 'evt_allow_custom_js', $suelto, $user_id, $post_id )` es la
  única vía de relajar la regla del multisitio, y solo esa regla: la capacidad y
  el área se comprueban antes. Es un `add_filter` que se ve en el repositorio y
  se audita. **Marcar `unfiltered_html` a un rol en WPFront o en Members no es
  la vía**: concede mucho más que este campo y reabre la concesión global que el
  aplicativo no hace en ningún sitio.
- **Se comprueba en tres sitios.** La pestaña «Código» del taller no existe para
  quien no tiene ninguna de las dos (`EventWorkspace::panels()`), el manejador
  del POST vuelve a comprobar capacidad por capacidad
  (`EventWorkspace.php`, `PageForm.php`) y el `auth_callback` de
  las dos metas pregunta lo mismo (`auth_custom_css()` y `auth_custom_js()`), de
  modo que tampoco se escriben por REST.

Retirar una de las dos capacidades **no borra el código ya guardado**: sigue
aplicándose. Si hay que quitarlo, se vacía el campo.

### El estado «histórico»: el cierre que va por encima del área

Un evento marcado como **histórico** no lo edita su área, ni nadie con
`evt_edit_all_areas` concedida a mano: solo administración
([ADR-0017](adr/ADR-0017-el-estado-historico-cierra-la-edicion.md)). Es la
única regla de este documento que se pone **por encima** del acotado por área, y
conviene tenerla clara antes de buscar por qué alguien «que debería poder» no
puede guardar.

**La marca la pone el área y la quita administración**, y la asimetría es el
punto entero
([ADR-0017](adr/ADR-0017-el-estado-historico-cierra-la-edicion.md) §2):

| Acción | Quién | Por qué |
|---|---|---|
| **Marcar** como histórico | El área del evento: `evt_organiser` con esa área en su perfil | Es su evento y es quien sabe el día que ya no queda nada por subir |
| **Desmarcar** | Solo `evt_manage_app` | Un candado que abre quien lo cerró no es un candado |

Antes de marcar, el taller avisa con SweetAlert2 de que **no hay vuelta atrás
sin administración**. No es una confirmación de cortesía: es la única
oportunidad de enterarse antes de quedarse fuera del propio evento.

**Y no hay ningún cierre por fechas.** Un evento terminado se sigue editando
indefinidamente, que es cuando se suben los vídeos, las presentaciones y las
fotos. La regla de la
[ADR-0012](adr/ADR-0012-politica-de-edicion-y-auditoria.md) lo dice y lo
razona en su opción 3, que descarta: un cierre automático estorbaría en la
semana de más faena. **Nunca llegó a implementarse**:
`can_edit()` no mira ninguna fecha y `EventState::of()` solo se usa para pintar
y para filtrar.

| Pieza | Dónde |
|---|---|
| La marca | meta `evt_archived` en el evento raíz (`EventMetaKeys::ARCHIVED`) |
| Quién la pone | `EventAccess::can_archive()` (`EventAccess.php`), que es la regla del área |
| Quién la quita | `EventAccess::can_unarchive()`, que es `evt_manage_app` |
| Dónde se comprueba al guardar | `EventWorkspace::save_archived()` (`EventWorkspace.php`), antes que nada, con su nonce |
| Dónde se comprueba por cualquier otra vía | `EventMetaRegistration::auth_archived()`, que pregunta por `EventAccess::can_toggle_archived()` (`EventAccess.php`) |
| Quién queda cerrado | `EventAccess::can_edit()` (`EventAccess.php`) |
| Quién sigue entrando | `EventAccess::can_open()` (`EventAccess.php`) |
| El motivo en castellano | `EventAccess::why_not_editable()` (`EventAccess.php`) |

Cinco cosas que no se ven leyendo solo la marca:

1. **El cierre baja a las secciones sin que nadie lo copie.**
   `EventAccess::is_archived()` (`EventAccess.php`) pregunta siempre por la
   raíz con `root_id()`, así que una página satélite queda cerrada sin llevar
   meta ninguna. Una pantalla nueva que pregunte por `can_edit()` la hereda
   gratis.
2. **Alcanza también al escritorio y a la REST.** La regla vive en `can_edit()`
   y `map_meta_cap` desemboca ahí, así que `user_can( 'edit_post', … )` devuelve
   `false` también en `post.php`, en la edición rápida y por la API. Medido en el
   wp-env con los usuarios de demostración; la tabla está en la ADR-0017.
3. **`evt_edit_all_areas` no exime del cierre.** Quien la tenga concedida a mano
   llega a todas las áreas y aun así no edita un evento marcado. No es un
   descuido: es lo que hace que «cerrado» signifique algo. Lo único que exime es
   `evt_manage_app`, y para volver a abrirlo hay que pedírselo a administración.
4. **Marcar pregunta por `can_open()` y no por `can_edit()`**, porque
   `can_edit()` lleva el cierre encima y se mordería la cola: marcar exigiría
   poder editar, y lo primero que hace la marca es quitar esa posibilidad. Y
   **quitar la marca no se hereda de poder editar el evento** —eso sí sería
   circular—, sino de `evt_manage_app`.
5. **El `auth_callback` de la meta mira el estado de hoy, no el valor que se va
   a escribir.** Un `auth_callback` recibe la capacidad, la meta y el post, pero
   **no el valor**, así que no puede distinguir marcar de desmarcar
   preguntándoselo al dato. `can_toggle_archived()` lo saca del estado: con el
   evento abierto la marca la toca su área, con el evento cerrado solo la toca
   administración. Sale la misma asimetría por REST y por cualquier
   `update_post_meta()` que pase por la capacidad, y un área no se desmarca su
   evento por la puerta de al lado.

Y dos cosas que **no** hace: no despublica —la página pública se sigue viendo
igual, byte a byte— y no esconde el evento del listado ni del taller, que se
abren en solo lectura con el aviso arriba.

**No es `evt_legacy`.** Son dos marcas y significan cosas distintas:

| Marca | Quién la pone | Qué significa |
|---|---|---|
| `evt_legacy` ([ADR-0008](adr/ADR-0008-migracion-de-los-eventos-historicos.md)) | El guion de migración | El contenido es contenido del maquetador, congelado del sistema viejo y no se regenera |
| `evt_archived` ([ADR-0017](adr/ADR-0017-el-estado-historico-cierra-la-edicion.md)) | El área, a mano; solo administración la quita | El evento está cerrado a edición |

La migración pone las dos a los eventos que trae del sistema actual; un evento
nacido en el aplicativo puede recibir la segunda y nunca tendrá la primera.

### El ámbito por área

El área organizadora es un **término de la taxonomía `evt_area`** puesto en el
evento, y la pertenencia de la persona es un **user meta `evt_area`** con uno
o varios `term_id`. No es un rol:
[ADR-0006](adr/ADR-0006-el-area-es-un-ambito-no-un-rol.md).

| Pieza | Dónde |
|---|---|
| Área del evento | término de `evt_area` en el post raíz (`EventTaxonomies::AREA`) |
| Área del ponente y de la actividad | término de `evt_area` en la propia ficha (`SpeakerPostType::register()`, `ActivityPostType::register()`) |
| Área de la persona | user meta `evt_area`, array de `term_id` (`EventAccess.php`, `roles-and-profiles.php`) |
| Quién escribe ese campo | solo `evt_manage_app` o `edit_users` (`evt_can_edit_admin_only_fields()`) |
| Quién decide si se puede abrir | `EventAccess::can_open()` (`EventAccess.php`) |
| Quién decide si se puede editar | `EventAccess::can_edit()` (`EventAccess.php`), que es `can_open()` menos el cierre |
| Los tres tipos que acota | `EventAccess::scoped_types()` (`EventAccess.php`) |

Cinco reglas, todas comprobables en el código:

1. **Las páginas satélite no llevan área propia.** El área que manda es la del
   evento raíz; `EventAccess::post_areas()` sube por `post_parent` hasta la
   raíz antes de mirar los términos (`EventAccess.php`). Así una hija
   no se queda huérfana de permisos al moverla.
2. **Falla en cerrado.** Quien tiene `edit_evt_events` y **no** tiene ningún
   área en su perfil no edita nada (`EventAccess.php`), y su lista del
   escritorio sale vacía por `post__in => array( 0 )`
   (`EventAdmin::scope_admin_query()`). Abrir cuando falta el dato es
   exactamente cómo un área acaba tocando los eventos de otra.
3. **La excepción del evento recién creado.** Un evento al que todavía no se
   le ha puesto área lo edita **quien lo creó** (`EventAccess::can_open()`),
   porque si no, nadie podría ponérsela. Es una excepción real y conviene
   conocerla: durante ese rato el evento no está acotado por área, solo por
   autoría.
4. **El campo del perfil es de administración.** WordPress deja a cualquiera
   editar su propio perfil; si el campo fuera editable, autoasignarse un área
   sería colarse en otra. Por eso `evt_save_profile_fields()` comprueba
   `evt_can_edit_admin_only_fields()` antes de guardar
   (`roles-and-profiles.php`), y a quien no la tiene se le enseña el valor
   como texto, no como desplegable (`roles-and-profiles.php`).
5. **La misma regla vale para los tres tipos.** `can_edit()` ya no pregunta
   «¿es un `evt_event`?» sino «¿es uno de los tres tipos que acoto?»
   (`EventAccess::scoped_types()`), y cambia solo la capacidad primitiva que
   exige: `edit_evt_events`, `edit_evt_speakers` o `edit_evt_activities`. El
   resto —área de la persona, área del contenido, intersección— es idéntico.
   La sección siguiente explica de dónde sale el área de un ponente.

**Dónde se aplica de verdad.** Hay dos capas y hacen cosas distintas:

| Capa | Qué hace | Dónde |
|---|---|---|
| `pre_get_posts` | **Esconde** filas del listado del escritorio | `EventAdmin.php` |
| `map_meta_cap` | **Deniega** `edit_post`, `delete_post` y `publish_post` en los tres tipos | `EventAccess.php` |

La segunda es la que protege. La primera solo filtra la consulta principal:
el enlace directo a `post.php?post=N`, la edición rápida, la REST y las
acciones en bloque no pasan por ella. La escritura de las metas del evento
cuelga también de la segunda, porque el `auth_callback` de
`register_post_meta()` pregunta por `edit_post`
(`src/Evt/Meta/EventMetaRegistration.php`).

### El área de un ponente y de una actividad

Un ponente es **reutilizable entre ediciones**: es el motivo de que sea un tipo
de contenido propio y no una página hija del evento (`SpeakerPostType.php`;
hoy la ficha de quien ya vino se vuelve a teclear entera en cada evento). Así
que «el área de un ponente» no es obvia, y hubo que elegir. La regla, y por qué:

**Un ponente y una actividad llevan su propio término de `evt_area`, igual que
un evento, y pueden llevar varios. Se editan si comparten alguna área con las
tuyas.**

Es la misma pieza y la misma comparación que en los eventos, así que no hay un
segundo concepto que aprender ni un segundo sitio donde equivocarse. Y encaja
con la reutilización sin forzar nada: **compartir un ponente con otra área es
añadirle su término, no duplicar la ficha**, y a partir de ahí las dos áreas lo
editan. Eso es exactamente lo que se quiere de un ponente que vuelve en la
edición siguiente organizada por otro servicio.

Las dos alternativas que se descartaron, y por qué:

| Alternativa | Por qué no |
|---|---|
| «Se edita si puedes editar **algún evento** en el que participa» | Hoy no existe la relación ponente↔evento en el modelo: no hay de dónde leerla. Y cuando exista, un ponente **sin** evento todavía —el caso normal al darlo de alta— se quedaría sin nadie que pudiera tocarlo |
| «Se edita si lo creó alguien de tu área» (la autoría) | La autoría no es el ámbito: [ADR-0006](adr/ADR-0006-el-area-es-un-ambito-no-un-rol.md) decidió que el área es un término, no una propiedad de la persona. Y se rompe sola: quien lo creó cambia de área, o se va, y la ficha se va con ella |

**El área se pone sola al crear.** `EventAccess::stamp_area()`
(`EventAccess.php`), enganchada en `save_post_evt_speaker` y
`save_post_evt_activity`, le pone a la ficha recién creada las áreas de quien
la firma, **solo si todavía no tiene ninguna**. Sin esto, un ponente nace sin
área y lo toca únicamente quien lo tecleó, no sus compañeras, que es lo
contrario de lo que se busca. Y como solo escribe cuando el campo está vacío,
compartir un ponente con otra área no se deshace en el siguiente guardado.

**El caso raro, y está probado.** Quien crea la ficha puede no tener área
propia: la administración no la necesita, porque
`evt_edit_all_areas` la exime. Entonces no hay nada que estampar, la ficha se
queda sin área y cae en la excepción de la regla 3 —la manda la autoría—, así
que **ninguna organización la hereda por descuido**: falla en cerrado hasta que
alguien le asigne un área a mano. Los dos casos, el compartido y el huérfano,
están en `tests/unit/test-speaker-activity-access.php`.

### Quién da de alta un ponente: hoy y a partir de ahora

Es el cambio que más se va a notar, así que conviene dejarlo escrito y
razonado.

**Hoy en producción no puede casi nadie.** Los dos formularios del sistema
anterior —el de ponentes y el de actividades— están cerrados por rol, y con la
misma lista los dos: los abren la administración del sitio y un único perfil de
edición, y nadie más. Ni el perfil de gestión de inscripciones ni ninguno de
los roles de área llega. Hay además un desajuste que se ve en pantalla: otro
fragmento de código pegado a mano le enseña el enlace «ponente» al perfil de
gestión de inscripciones, que el formulario no admite, así que esas personas
**ven el botón y al pulsarlo no pueden enviar**.

La consecuencia práctica es la que cuenta cualquiera que haya organizado unas
jornadas allí: el área que organiza el evento **le pide por correo a otra
persona** que dé de alta a sus ponentes y que le monte el programa. Un cuello
de botella de un puñado de cuentas para todas las fichas de ponente y de
actividad del sitio.

**Eso se acaba.** `evt_organiser` lleva el juego completo de
`…_evt_speakers` y `…_evt_activities`, y crea con `edit_evt_speakers`, que es
la que WordPress usa para «Añadir nuevo» al no declararse `create_posts`. Las
razones, por orden:

1. **Es quien tiene la información.** El área sabe quién viene a hablar y a qué
   hora; quien hoy teclea la ficha se lo está copiando de un correo.
2. **El acotado por área ya existe y ahora también las cubre.** Cerrar el
   formulario por rol era la única barrera posible, porque un ponente era una
   fila en la tabla de un formulario, sin dueño ni área. Ahora el ámbito es un
   término de `evt_area` comprobado en `map_meta_cap()`, que es una barrera
   mejor y más fina: no «esta persona no crea ponentes», sino «esta persona no
   toca los de otra área».
3. **La raya de verdad está en otro sitio.** Lo que sigue reservado a
   `administrator` es el **JavaScript** a medida y los ajustes del aplicativo.
   Dar de alta a quien participa nunca fue eso.

Los dos formularios del sistema anterior y su cierre por rol **siguen donde
están** mientras haya eventos antiguos que dependan de ellos; se retiran con la
migración
([ADR-0008](adr/ADR-0008-migracion-de-los-eventos-historicos.md)). Lo que se
acaba es que sean el sitio por el que se da de alta un ponente nuevo.

**Lo que este acotado todavía no cubre.** Se escribe aquí y no se disimula:

- **La lista del escritorio de ponentes y actividades no está acotada.**
  `map_meta_cap()` ya deniega la edición de los de otra área, que es la capa
  que de verdad protege, pero `EventAdmin::scope_admin_query()` solo filtra
  `post_type === evt_event` (`EventAdmin.php`): la lista los **enseña**
  todos aunque no se puedan abrir. Es ruido, no un agujero.
- **Los contadores de las pestañas del listado mienten.** «Todos |
  Publicados | Borradores» se cuentan por `views_edit-evt_event`, que no pasa
  por `pre_get_posts`. Una organización acotada verá el número de todo el
  sitio sobre una lista que solo enseña lo suyo.
- **No hay selector de área en el listado.** Ni filtro por área para quien no
  está acotado (`restrict_manage_posts`), ni columna ordenable: la columna
  «Área» existe (`EventAdmin::columns()`) pero es de solo lectura.
- **No hay acotado en el frontal.** El CPT es `public => true`
  (`EventPostType::register()`); un evento publicado lo lee cualquiera, que es lo
  que se quiere. Los borradores y los privados los cubre
  `read_private_evt_events`, no el área.

### Cómo crearlos en WPFront User Role Editor

1. **Roles → Add New** para `evt_organiser` («Organización de eventos»), que es
   el único que crea el aplicativo. Si el snippet `EVT — Roles y perfiles` ya
   está activo, ya existe y este paso sobra: `evt_register_roles()` lo crea en
   `init` prioridad 5. `administrator` no se toca: ya está.
   **Si en la lista aparece `evt_coordinator`**, es el rol retirado el
   2026-09-13 y el snippet todavía no ha corrido en ese sitio: en cuanto lo
   haga, lo retira él solo y una única vez; ver «Y qué pasa con
   `evt_coordinator`» al final.
2. Marcar las capacidades de la columna del rol. Las `evt_*` aparecen en el
   grupo de capacidades personalizadas **una vez que el snippet y el bundle
   han cargado al menos una vez**; si no salen, «Add/Remove Capability»
   permite escribirlas a mano. Recordar que las nueve en singular
   (`edit_evt_event`, `read_evt_event`, `delete_evt_event` y sus equivalentes
   de ponentes y actividades) son meta capacidades y no autorizan nada.
3. Asignar el rol a cada persona y **rellenar su área** en la sección
   «Eventos» de su ficha de usuario (`evt_render_profile_fields()`). Sin
   área, `evt_organiser` no ve ni un evento: no es un fallo, es la regla 2.

Quien tenga `evt_manage_app` ve el estado en **Eventos → Ajustes**
(`Settings::render()`), que pinta lo que devuelve `evt_roles_status()`: si cada rol existe,
qué capacidades le faltan y cuáles de las prohibidas le sobran. La
comprobación de existencia pregunta a WPFront
cuando el plugin está cargado y al registro de WordPress cuando no
(`roles-and-profiles.php`), para mirar la misma lista que ve quien
administra.

### Cambiar de usuario para probar

Lo hace el propio WPFront (**Users → Switch To**, capacidad `switch_users`,
que WPFront concede al administrador). Es la vía comprobada en el sitio de
destino, donde WPFront 4.2.4 ya está instalado. No se añade nada propio para
esto.

Para probar el acotado hacen falta al menos cuatro cuentas: una
`administrator`, una `evt_organiser` **con** área, otra `evt_organiser` **de
otra área** y una `evt_organiser` **sin ninguna**. La última es la que comprueba
la regla del fail-closed, que es la que más fácil se rompe al tocar
`EventAccess`; la tercera es la que comprueba que un área no ve lo de la otra.

### Qué pasa con los roles de hoy

Nada, todavía. Los perfiles de §A siguen donde están mientras el formulario del
sistema anterior y su vista sigan generando páginas
([ADR-0007](adr/ADR-0007-las-inscripciones-siguen-en-el-sistema-anterior.md)). Cada
rol de área se convierte en un término de `evt_area` y en el campo `evt_area`
del perfil de quienes lo tenían; el mapeo concreto y el momento de retirarlos
son parte de la migración
([ADR-0008](adr/ADR-0008-migracion-de-los-eventos-historicos.md)) y no están
resueltos en este documento. El perfil de gestión de inscripciones es el caso
que peor encaja: sus capacidades no son del aplicativo sino del gestor de
formularios, y sobreviven a la migración de los eventos, porque las
inscripciones se quedan allí.

### Y qué pasa con `evt_coordinator`, el rol que se retiró

Que **se retira solo, una vez**, y conviene saber exactamente qué hace esa vez.

El problema era real: `evt_register_roles()` es aditivo a propósito —crea el rol
si falta y le añade la capacidad si le falta, y **nunca** llama a `remove_role()`
ni a `remove_cap()`—, que es lo que permite que una concesión hecha a mano en
WPFront sobreviva a la siguiente carga. Con esa regla sola, el rol retirado se
habría quedado para siempre en cualquier sitio donde el snippet ya se hubiera
activado, con sus capacidades y con quien lo tuviera asignado.

De las dos salidas que había sobre la mesa se eligió la segunda:

| Salida | Qué implicaba |
|---|---|
| Dejarlo huérfano y avisar | «Eventos → Ajustes» lo detecta y lo dice. Nadie pierde nada por sorpresa, pero el rol sigue concediendo lo que concedía |
| **Retirarlo una sola vez, con opción de guarda** ← **elegida** | Un `remove_role()` que corre una vez y deja una opción puesta para no repetirse. Limpio, y con la fecha registrada |

Lo hace `evt_retire_coordinator_role()` (`snippets/roles-and-profiles.php`), que
corre en `init` con el resto y, si la opción `evt_coordinator_role_retired` no
está puesta:

1. a cada persona que tuviera `evt_coordinator` le pone antes `evt_organiser` y
   luego le quita el viejo, para que nadie se quede sin ningún rol —eso es una
   cuenta que entra y no puede hacer nada, y sin rastro de por qué—;
2. borra el rol con `remove_role()`;
3. pone `evt_coordinator_role_retired` a `1`, y a partir de ahí no vuelve a
   tocar nada. Si alguien crea después un rol con ese nombre, es cosa suya y el
   snippet lo respeta.

Comprobado en el wp-env el 2026-09-13, sobre una base que tenía el rol y una
cuenta con él:

```
ANTES   rol=evt_coordinator  evt_edit_all_areas=si  role_existe=si
DESPUES rol=evt_organiser    evt_edit_all_areas=no  role_existe=no  option='1'
```

Lo que **no** vale, y por eso la guarda: quitarlo en cada carga. Eso convertiría
el snippet en destructivo y se llevaría por delante cualquier concesión hecha
desde WPFront, que es justo lo que este documento promete que se respeta.

Quien tuviera el rol pierde `evt_edit_all_areas` —que es la decisión que se está
tomando— y **hasta que quien administra le rellene su área en el perfil no edita
nada**: el acotado falla en cerrado. Nunca tuvo `evt_manage_app` ni
`evt_edit_custom_js`, así que por ahí no pierde nada.
