---
id: SDD-0002
title: "Arquitectura de reemplazo: el aplicativo de eventos"
status: Aceptada
date: 2026-09-13
related:
  issues: []
  prs: []
  adrs: [ADR-0001, ADR-0002, ADR-0003, ADR-0004, ADR-0005, ADR-0006, ADR-0007, ADR-0008, ADR-0009, ADR-0010, ADR-0011, ADR-0012, ADR-0013, ADR-0014, ADR-0015, ADR-0022, ADR-0023]
  sdds: [SDD-0001]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# SDD-0002: Arquitectura de reemplazo: el aplicativo de eventos

## Estado

**Aceptada** como diseño de la fase 1, el 2026-09-12.

Aceptada no quiere decir desplegada. A esta fecha el sitio de destino sigue
funcionando exactamente como lo describe la investigación del sistema anterior
(`.local/`): un formulario único, la plantilla que interpola la página y la
taxonomía que lo mezcla todo. Lo que hay escrito en este repositorio son
**1.386 líneas en doce ficheros** de `src/Evt/` (`src/Evt/load-order.php`) más
el snippet suelto de roles, ninguna de ellas en producción.

Este documento distingue en cada apartado, y con estas palabras, entre **lo que
ya está en el código** (con su ruta y su línea) y **lo que las ADR fijan pero
todavía no existe**. Donde no está escrito, se dice.

## Resumen

El aplicativo baja el dominio «evento» a WordPress nativo: un tipo de contenido
jerárquico `evt_event` que es a la vez el evento y sus páginas satélite, dos
tipos auxiliares (`evt_speaker`, `evt_activity`) y tres taxonomías
(`evt_area`, `evt_type`, `evt_course`) que sustituyen a la única `convocatoria`
de hoy. El área organizadora deja de ser un rol y pasa a ser un ámbito, con lo
que por primera vez un evento tiene dueño y un permiso puede decir «los eventos
de esta área».

Todo se empaqueta con `make bundle` en un único Code Snippet
(`snippets/evt-eventos-app.bundle.php`), más un snippet suelto que registra los
dos roles. No hay plugin, no hay ficheros que desplegar
([ADR-0001](../adr/ADR-0001-repo-entorno-desarrollo-no-plugin.md)).

Lo que cambia de verdad para quien organiza un evento: rellenar un formulario
de 146 campos —de los cuales unos 25 son colores y tipografías— se convierte
en escribir un evento en el editor de WordPress y ponerle fecha, área y
tipología. Lo que cambia para quien mantiene el sitio: un diff.

## Contexto

Los hechos de partida están medidos sobre la investigación del sistema
anterior —la foto del 2026-09-12, que vive en `.local/` y no se publica
([ADR-0030](../adr/ADR-0030-el-repositorio-se-publica-sin-nada-de-nadie.md))— y
razonados en las catorce ADR. Los cinco que sostienen este diseño:

| Hecho | Medida | Fuente |
|---|---|---|
| El aplicativo entero es un formulario | un único formulario de **146 campos** | investigación (`.local/`) |
| La plantilla es un campo de texto | **42.165 caracteres** y **193 bloques `[if]`** dentro de un campo | investigación (`.local/`) |
| La jerarquía del sitio | **7 líneas** pegadas a mano que leen un identificador del `$_POST` sin comprobar nada | investigación (`.local/`) |
| No hay modelo, hay tecleo | **34 slugs distintos** con «programa» entre las páginas hijas | recuento sobre el WXR, [ADR-0003](../adr/ADR-0003-cpt-jerarquico-frente-a-paginas-generadas.md):57 |
| El área es un rol sin permisos de escritura | hay un rol por área organizadora y **ninguno tiene `edit_pages`** | investigación (`.local/`) |

## Problema

Quien organiza un evento no puede editarlo: rellena un formulario de 146
campos desde el frontal y una vista del sistema anterior fabrica la página.
Quien coordina no puede ver «los eventos de su área» porque el área es un rol y
un rol no tiene registros. Quien desarrolla no puede revisar un cambio porque
la plantilla vive en un `longtext` de la base de datos. Y una tercera sede no
cabe sin duplicar un bloque en cuatro sitios distintos
([ADR-0003](../adr/ADR-0003-cpt-jerarquico-frente-a-paginas-generadas.md):81-82).

## Objetivos

Comprobables, en este orden:

1. **Un evento es un post.** Tiene autoría, fecha, estado de publicación, área
   y URL, y se edita con el editor de WordPress.
2. **Un dato, un sitio.** La sede, el día y la fecha se leen del evento al
   pintar. Hoy viajan copiadas como atributos de shortcode en **31 páginas**.
3. **El área acota de verdad.** Una persona del área A no abre un evento del
   área B ni por el listado, ni por el enlace directo, ni por la REST.
4. **Fail-closed.** Sin área en el perfil y sin `evt_edit_all_areas`, no se
   edita nada.
5. **Un artefacto.** `make bundle` produce un fichero que se pega en Code
   Snippets, y el mismo código corre bajo PHPUnit.
6. **Las URL se conservan.** 163 páginas indexadas y enlazadas desde fuera.
   Este objetivo **no está cumplido todavía**: ver §Pendientes.

## No objetivos

La fase 1 **no toca las inscripciones ni los talleres**. Explícitamente
([ADR-0007](../adr/ADR-0007-las-inscripciones-siguen-en-el-sistema-anterior.md)):

- **No se registran `evt_registration` ni `evt_session`.** Los tipos son tres.
- Los **formularios de inscripción y selección de talleres** —con datos
  personales y consentimientos firmados dentro— siguen en el gestor de
  formularios del sistema anterior, y la página de inscripción sigue llevando
  su shortcode.
- Los **fragmentos de aforo** que hoy cuentan plazas se quedan donde están y
  **ninguno entra en el bundle**.
- **No hay certificados, ni check-in, ni firma digital, ni control de aforo.**
  Nada de eso existe hoy en el sitio y nadie lo ha pedido; aparece en
  §Pendientes como fase 3 con lo que se puede reusar, no como diseño.
- **No se rediseña la presentación.**
  [ADR-0010](../adr/ADR-0010-la-pagina-la-genera-codigo-versionado.md) fija que
  la plantilla vive en `src/Evt/`, no qué pinta. El módulo que sustituye a la
  plantilla antigua está por escribir.
- **No se migran los cuarenta y pico campos de diseño visual** del formulario
  antiguo. El detalle y el motivo, en §El modelo de datos.

## Especificación / Diseño propuesto

### 1. La anatomía

Doce ficheros, todos `final class` con métodos `static`, sin contenedor de
inyección, sin interfaces y sin capa de servicios: es lo que permite
concatenarlos en un fichero (`build/pack-snippet.php`).

| Fichero | Responsabilidad | Líneas |
|---|---|---:|
| `src/Evt/load-order.php` | Lista única y ordenada; la leen `bootstrap.php` y el empaquetador | 31 |
| `src/Evt/bootstrap.php` | `EVT_SRC_DIR`, requiere la lista si `App` no existe, `App::boot()` | 30 |
| `src/Evt/App.php` | Idempotente; engancha todo en `init` | 52 |
| `src/Evt/Meta/EventMetaKeys.php` | Claves de meta y listas cerradas | 108 |
| `src/Evt/Meta/EventMetaRegistration.php` | `register_post_meta` con tipo, sanitize y `auth_callback` | 103 |
| `src/Evt/Domain/EventInput.php` | Valida el alta y la edición. Pura | 90 |
| `src/Evt/Domain/EventState.php` | Estado derivado de las fechas. Pura | 63 |
| `src/Evt/Access/EventAccess.php` | El único guardián | 245 |
| `src/Evt/PostType/EventPostType.php` | CPT `evt_event` y el reparto de capacidades | 155 |
| `src/Evt/PostType/SpeakerPostType.php` | CPT `evt_speaker` | 68 |
| `src/Evt/PostType/ActivityPostType.php` | CPT `evt_activity` | 66 |
| `src/Evt/Taxonomy/EventTaxonomies.php` | `evt_area`, `evt_type`, `evt_course` | 91 |
| `src/Evt/Admin/EventAdmin.php` | Columnas y acotado del listado | 113 |
| `src/Evt/Admin/Settings.php` | Ajustes y diagnóstico | 171 |

El orden de arranque no es decorativo (`src/Evt/App.php`):

| Hook | Prioridad | Qué corre |
|---|---:|---|
| `init` | 5 | `evt_register_roles()` — snippet suelto (`snippets/roles-and-profiles.php`) |
| `init` | 9 | `EventTaxonomies::register()` |
| `init` | 10 | Los tres `register_post_type()` |
| `init` | 11 | `EventPostType::grant_caps_to_roles()` |
| `init` | 12 | `EventMetaRegistration::register_meta()` |

Los roles tienen que existir antes de que el aplicativo les cuelgue las
capacidades del CPT: por eso el snippet de roles va a prioridad 5 en Code
Snippets y el bundle a 15. Si alguien invierte esas prioridades, el reparto de
capacidades no encuentra los roles y falla en silencio.

### 2. El modelo de datos

#### Los tres tipos

| Tipo | Slug | Jerárquico | Público | Sustituye a |
|---|---|:-:|:-:|---|
| Evento y sus páginas | `evt_event` | sí | sí | las `page` jerárquicas |
| Ponente | `evt_speaker` | no | **no** | el formulario de ponentes del sistema anterior |
| Actividad | `evt_activity` | no | **no** | el formulario de actividades del sistema anterior |

`evt_event` es jerárquico porque un evento es a la vez su portada
(`post_parent = 0`) y sus páginas satélite
(`src/Evt/PostType/EventPostType.php`). Soporta título, editor, autoría,
imagen destacada, extracto y atributos de página —de donde sale el orden—
(`supports`, en `EventPostType::register()`). Su `capability_type` es
`array( 'evt_event', 'evt_events' )` con `map_meta_cap => true`.

Ponentes y actividades son `public => false` con `show_ui => true` y el menú
colgado del de Eventos (`SpeakerPostType.php`,
`ActivityPostType.php`): se administran, pero no tienen URL propia porque
al público se le enseñan dentro de la página del evento. Una misma persona
comunicadora vuelve en varias ediciones —de ahí que sea un tipo y no una página
hija—, y hoy se reteclea evento a evento.

#### Las metas del evento

Todas se declaran en un solo sitio, `EventMetaRegistration::schema()`
(`src/Evt/Meta/EventMetaRegistration.php`), con su tipo, su
`sanitize_callback` y su `auth_callback`. Las del modelo de datos:

| Clave | Dónde | Qué guarda | Saneado |
|---|---|---|---|
| `evt_section_type` | hijas | qué es esta página dentro del evento; vacía en la raíz | lista cerrada, lo desconocido cae en `otra` |
| `evt_start_date` | raíz | primer día, `Y-m-d` | `checkdate()`; lo inválido se guarda vacío |
| `evt_end_date` | raíz | último día, `Y-m-d`; vacío si dura un día | ídem |
| `evt_venue` | raíz | sede tal y como se anuncia | `sanitize_text_field` |
| `evt_custom_css` | **las dos** | CSS a medida, en crudo | se le quitan las etiquetas y el `</style` que cerraría el suyo (`sanitize_custom_css()`) |
| `evt_custom_js` | **las dos** | JavaScript a medida, en crudo | **no se escapa** —es código—; solo se desactiva la fuga por `</script` (`sanitize_custom_js()`) |

Las de identidad, inscripción y apariencia que rellenan los paneles «Datos» y
«Apariencia» del taller —`evt_tagline`, `evt_hashtag`, `evt_intro`,
`evt_signup_*`, `evt_header_bg`, `evt_header_text`, `evt_title_font`,
`evt_body_font`, `evt_logo_id`, `evt_poster_id`, `evt_image_shape` y
`evt_separator`— salen del mismo `schema()` y su lista canónica está en
`src/Evt/Meta/EventMetaKeys.php`.

`auth_callback` exige por defecto `edit_post` sobre el propio evento
(`auth_edit_event()`), así que ninguna meta se puede escribir por una vía que
se salte el guardián. **Las dos de código tienen el suyo propio**: piden además
`evt_edit_custom_css` o `evt_edit_custom_js`
([ADR-0014](../adr/ADR-0014-css-del-area-javascript-de-administracion.md)).
Son las únicas que viven **tanto en el evento raíz como en cada página
satélite**: lo del raíz se aplica a todas las páginas del evento y lo de una
página va después, para poder afinarlo, y nunca sale de las páginas de su
evento (`src/Evt/PublicFront/CustomCode.php`).

El **estado no es una meta ni un término**: lo calcula
`EventState::of( $start, $end, $today )`
(`src/Evt/Domain/EventState.php`) y devuelve `proximo`, `abierto` o
`finalizado` ([ADR-0005](../adr/ADR-0005-el-estado-del-evento-se-deriva-de-las-fechas.md)).
Un dato que se puede calcular no se guarda: hoy el estado es un término más de
la taxonomía y se marca a mano, así que 32 páginas están en
«evento-finalizado» y 2 en «evento-abierto» porque nadie volvió a tocarlas.

#### Las tres taxonomías

Registradas sobre `evt_event`, jerárquicas y con columna en el listado
(`EventTaxonomies::register()`). Jerárquicas a propósito: así se presentan como casillas y un vocabulario cerrado no se amplía
por una errata al teclear.

| Taxonomía | Eje |
|---|---|
| `evt_area` | área organizadora — **es el eje de permisos** |
| `evt_type` | tipología (jornadas, encuentro, congreso, taller) |
| `evt_course` | curso escolar |

Cada una hereda los términos que le tocan al repartir la taxonomía única de hoy
([ADR-0004](../adr/ADR-0004-una-taxonomia-por-dimension.md)).

Las capacidades son capacidades que existen: `evt_manage_app` para administrar
términos y `edit_evt_events` para asignarlos (`EventTaxonomies::args()`). Es la
corrección directa del fragmento que registra hoy la taxonomía: pide dos
capacidades que no existen en el sitio, y por eso hoy nadie puede administrar
sus términos.

#### Correspondencia campo a campo con el formulario antiguo

Los 146 campos del formulario antiguo, agrupados como los agrupa la
investigación (`.local/`). La columna «Destino» dice **declarado** cuando la
clave existe hoy en el código, **ADR** cuando una ADR la fija pero no está
escrita, y **no se porta** cuando se pierde a propósito.

| Campo del formulario antiguo | Destino | Situación |
|---|---|---|
| Tipo de página | meta `evt_section_type` | declarado (`EventMetaKeys::section_types()`) |
| Identificador de la página | **desaparece**: el post *es* el registro | — |
| Título de la página | `post_title` | nativo |
| Slug | `post_name` | nativo |
| Orden | `menu_order` (`page-attributes`) | nativo (`EventPostType::register()`) |
| Contenido | `post_content` | nativo; disuelve la concesión global de `unfiltered_html` |
| Texto introductorio de cabecera | `post_excerpt` | nativo (`EventPostType::register()`) |
| Imagen destacada | `_thumbnail_id` | nativo (`EventPostType::register()`) |
| Estado de publicación en WordPress | `post_status` | nativo |
| ID de usuario | `post_author` | nativo (`EventPostType::register()`) |
| Categoría padre / evento padre / **identificador de la página padre** | `post_parent` | nativo; sustituye a las 7 líneas pegadas a mano |
| Tipología del evento | taxonomía `evt_type` | declarada (`EventTaxonomies::register()`) |
| Curso escolar | taxonomía `evt_course` | declarada (`EventTaxonomies::register()`) |
| Promociona | taxonomía `evt_area` | declarada (`EventTaxonomies::register()`); ofrece los términos de área, no otra dimensión ([ADR-0004](../adr/ADR-0004-una-taxonomia-por-dimension.md)) |
| Estado del evento | **nada**: derivado de las fechas | declarado (`EventState.php`) |
| Fechas del evento (texto libre) | `evt_start_date` + `evt_end_date` en `Y-m-d` | declarado (`EventMetaKeys::START_DATE` y `END_DATE`) |
| Sedes (texto libre, de corrido) | `evt_venue` hoy; el diseño real, en §4 | declarado, **insuficiente** |
| Programa de la sede 1 (siete campos) | sedes repetibles (§4) | **ADR**, sin declarar |
| Programa de la sede 2 (los mismos, repetidos) | ídem: es la misma sede, no otra clave | **ADR**, sin declarar |
| Contacto: correo, teléfono, dirección y coordenadas, dos veces | página `contacto` + las coordenadas dentro de cada sede | **ADR**, sin declarar |
| Inscripción: botón, texto, URL, botón II, autenticación | página `inscripcion` con el shortcode del formulario | [ADR-0007](../adr/ADR-0007-las-inscripciones-siguen-en-el-sistema-anterior.md) |
| Formulario incrustado: su ID y el de la vista de gestión | metas de vínculo evento → formulario | **ADR** ([ADR-0007](../adr/ADR-0007-las-inscripciones-siguen-en-el-sistema-anterior.md)), sin declarar |
| Nombre en clave / lema / hashtag | metas del evento | **ADR**, sin declarar |
| Logo de cabecera / cartel | metas de adjunto | **ADR**, sin declarar |
| Subida del programa | adjunto enlazado desde la página `programa` | **ADR**, sin declarar |
| Vídeo de promoción: mostrar, URL, subida, leyenda | metas del evento | **ADR**, sin declarar |
| Patrocinadores: 5 logos + 5 URL | metas repetibles | **ADR**, sin declarar |
| Protección de datos: textos y consentimiento | metas del evento | **ADR**, sin declarar |
| Convocante (4 opciones fijas) | lista cerrada en código, como los tipos de sección | **ADR**, sin declarar |
| Multimedia: galería y vídeos | página `multimedia` | **ADR**, sin declarar |
| Requerimientos de firma | **fuera de la fase 1** | — |
| Listados de admitidos (PDF provisional/definitivo) | **fuera de la fase 1**: es inscripciones | [ADR-0007](../adr/ADR-0007-las-inscripciones-siguen-en-el-sistema-anterior.md) |
| Plantilla de página | `_wp_page_template` si el tema la necesita | nativo, sin decidir |
| **Diseño de la página principal** (once campos) | **no se porta** | — |
| **Diseño de la satélite** (siete campos) | **no se porta** | — |
| **Opciones del constructor visual** (constructor, barra lateral, navegación, sharing) | **no se porta** | — |
| **Títulos de sección / CSS propio de la página** | **no se porta** | — |

#### Qué no se porta, y por qué

Los campos de presentación del formulario antiguo, contados uno a uno:

| Bloque | Cuántos |
|---|---:|
| Diseño de la página principal: logo acompañante, colores de fondo y de texto, dos familias tipográficas, mosaico, separador, HTML de sustitución, banner | 11 |
| Diseño de la página satélite: los mismos ajustes, repetidos para la hija | 7 |
| Opciones del constructor visual: activarlo, barra lateral, navegación, sharing | 4 |
| Títulos de sección y **CSS propio de la página** | 2 |
| **Total estricto de diseño visual** | **24** |
| Si se cuentan también los 10 de patrocinadores, los 4 de vídeo de promoción y los 2 de logo y cartel | **40** |

Conviene ser exacto: **los campos estrictamente de diseño son 24, no «más de
40»**. La cifra de cuarenta y pico se sostiene solo si se le suman los
patrocinadores, el vídeo y los logos, que son contenido y sí tienen destino
previsto en la tabla anterior.

Los 24 se pierden a propósito, por tres razones que se leen del propio
material:

1. **Son la mitad de la plantilla.** De sus 193 bloques `[if]`, 28 ramifican
   sobre los campos de tipografía y 19 sobre los de color; el resto de los
   condicionales de diseño cuelgan de otros tres campos de apariencia. Portar
   los campos obliga a portar la plantilla que los interpreta, que es
   justamente lo que
   [ADR-0010](../adr/ADR-0010-la-pagina-la-genera-codigo-versionado.md)
   desmonta.
2. **Un color por evento es una decisión de marca, no un dato del evento.** Lo
   que hoy da 30 combinaciones distintas de tipografía y color en un mismo
   subsitio se sustituye por el tema, que es donde vive el diseño de un sitio
   de WordPress.
3. **El CSS por página y el HTML de sustitución son código sin control de
   versiones** escrito en un campo de formulario, por cualquiera que rellene el
   formulario, y hoy se ejecutan gracias a que un fragmento pegado a mano
   devuelve `unfiltered_html` a quien ya la tendría, saltándose la regla con la
   que el núcleo la reserva en una red. Portarlos sería portar esa excepción.

**El coste, dicho con la misma dureza:** los eventos que hoy tienen su color y
su tipografía dejarán de tenerlos. Quien pida un evento «con su imagen» tendrá
que pedir una plantilla al equipo, no rellenar un campo. Lo que sí se conserva
es la lista corta y cerrada de ajustes por evento —cartel, logo de cabecera,
qué secciones se muestran— que fija
[ADR-0010](../adr/ADR-0010-la-pagina-la-genera-codigo-versionado.md) punto 4, y
que **todavía no está escrita**. Los eventos ya publicados no pierden nada: se
migran congelados y siguen pintándose con su el tema
([ADR-0008](../adr/ADR-0008-migracion-de-los-eventos-historicos.md)).

### 3. Las secciones satélite

Una lista cerrada de once valores, en `EventMetaKeys::section_types()`
(`src/Evt/Meta/EventMetaKeys.php`), calcada del campo «tipo de página» del
formulario antiguo:

| Valor | Etiqueta | Equivale hoy a |
|---|---|---|
| *(vacío)* | la raíz del evento | la opción «Principal» del campo de hoy |
| `programa` | Programa | 34 slugs distintos que contienen «programa» |
| `ponentes` | Ponentes | media docena de slugs distintos, casi uno por evento |
| `inscripcion` | Inscripción | la página con el shortcode del formulario |
| `multimedia` | Multimedia | galería y vídeos |
| `contacto` | Contacto | correo, teléfono, dirección |
| `actividades` | Actividades | las fichas de actividad de hoy |
| `encuesta` | Encuesta | los formularios de encuesta |
| `participacion` | Participación | — |
| `preguntas` | Preguntas | la página de preguntas y sus formularios |
| `directo` | Emisión en directo | «canal de streaming» |
| `otra` | Otra | todo lo demás |

**Qué sustituye exactamente.** El desorden de hoy tiene dos caras y la lista
cerrada solo arregla una:

- **El tipo de sección**: el campo de hoy ya ofrece catorce opciones y ya se
  rellena; aquí pasa a ser una meta saneada contra la lista, y lo desconocido
  cae en `otra` en vez de romper (`sanitize_section_type()`).
- **El slug**: la muestra recogida en la investigación (`.local/`) da **26
  slugs distintos** para la sección «Programa», y el recuento completo sobre el
  WXR da **34**
  ([ADR-0003](../adr/ADR-0003-cpt-jerarquico-frente-a-paginas-generadas.md):57).
  Es la firma de la creación manual: nada valida el slug y nada relaciona la
  sección con su tipo. Con la meta, la relación deja de depender del slug: una
  página es el programa porque `evt_section_type = programa`, se llame
  `programa-8616` o `mnc-programa`. **Los slugs existentes no se tocan**, para
  no romper 163 URL indexadas
  ([ADR-0008](../adr/ADR-0008-migracion-de-los-eventos-historicos.md)).

**Lo que la lista no cubre, y hay que decirlo.** En el sistema anterior se
observan diecisiete clases de sección y la lista cerrada tiene once. Estas se
pierden dentro de `otra`:

| Sección observada hoy | Dónde cae | Comentario |
|---|---|---|
| Agradecimientos | `otra` | contenido libre, no necesita tipo propio |
| Saluda | `otra` | ídem |
| Alojamiento | `otra` | ídem |
| «Prepara tu viaje» | `otra` | ídem |
| Datos personales | `otra` | ídem |
| Gamificación | `otra` | el campo de hoy sí tiene la opción; la lista cerrada no |
| Metaverso | `otra` | ídem |
| Canal de streaming | `directo` | se funde con «emisión en directo» |

Es una pérdida de información real: dos opciones del campo de hoy (gamificación
y metaverso) desaparecen como tipo y se vuelven indistinguibles de cualquier otra
página suelta. La alternativa —una lista de catorce— mantiene dos valores que
en la foto del 2026-09-12 no usa ni un evento vivo. Se prefiere la lista corta
y ampliar por código si vuelven; ampliar una lista cerrada es un diff de una
línea.

### 4. Las sedes

**El problema, medido.** Hoy hay dos juegos de campos duplicados uno a uno: un
subformulario para la sede 1 (siete campos: diseño, fecha, isla, municipio,
centro —tomado del catálogo de centros—, ruta y slug de ruta) y otro para la
sede 2 con los mismos, **con la errata copiada** en la etiqueta de los dos. El
contacto repite el patrón: dirección y coordenadas de la sede 1, y otra vez
dirección y coordenadas de la sede 2. Y la vista del programa está duplicada,
una por sede, más sus dos versiones en acordeón. **Una tercera sede obliga a
duplicar el bloque en cuatro sitios**: el formulario, la plantilla de la página
y las dos vistas del programa
([ADR-0003](../adr/ADR-0003-cpt-jerarquico-frente-a-paginas-generadas.md):81-82).

En el código de hoy no hay solución: `EventMetaKeys::VENUE`
(`src/Evt/Meta/EventMetaKeys.php`) es **un solo texto libre**, que es el campo
de sedes de hoy con otro nombre. **Esto no está resuelto y este apartado es el
diseño, no la descripción de algo escrito.**

**Las opciones.**

| Opción | Cómo se guarda | Pros | Contras |
|---|---|---|---|
| 1. Dos juegos numerados (`evt_venue_1_island`, `evt_venue_2_island`…) | ~14 metas planas | Ninguno que no tenga la 3 | Es lo de hoy con otro prefijo: la tercera sede sigue sin caber |
| 2. Un texto libre | `evt_venue` (lo actual) | Cero código | No se puede filtrar, ordenar ni enlazar; una actividad no puede apuntar a una sede |
| 3. **Meta repetible en el evento raíz** | `register_post_meta( …, single => false )`, una fila por sede | N sedes sin tocar el esquema; el saneado corre por fila; nada nuevo que registrar | Una fila no tiene ID: la referencia estable hay que fabricarla (una clave por sede) y no se puede consultar con `meta_query` dentro del array |
| 4. Post hija de `evt_event` marcada como sede | un tercer tipo de hija | ID estable gratis | Rompe la dicotomía raíz/satélite del CPT y le da URL pública a algo que no la quiere |
| 5. Un cuarto CPT `evt_venue` | posts hijos del evento | ID estable, orden, permisos y pantalla propios | Un tipo de contenido, su juego de capacidades y su reparto para algo que no vive fuera de su evento; el CONTRATO fija tres tipos |
| 6. Taxonomía de sedes | términos compartidos | Reutilizable entre eventos | Un término no lleva fecha, ni ruta, ni coordenadas: la sede *de este evento* no es reutilizable aunque el centro lo sea |

**La decisión: opción 3.** Una meta repetible `evt_venue` sobre el evento raíz,
con `single => false` y una fila por sede. Cada fila lleva las claves que hoy
son los siete campos del subformulario de sede más las coordenadas del bloque
de contacto:

| Clave de la fila | Qué es |
|---|---|
| `key` | identificador estable, generado del nombre y nunca reescrito |
| `date` | día de esa sede, `Y-m-d` |
| `island` | isla |
| `town` | municipio |
| `center` | centro; hoy sale del catálogo de centros |
| `address` | dirección |
| `lat`, `lon` | coordenadas |
| `route`, `route_slug` | ruta e itinerario |

Y `evt_activity` gana una meta `evt_venue_key` que apunta a la `key` de la
sede, además de la del evento al que pertenece. Con eso la parrilla del
programa deja de necesitar dos vistas: es una sola que agrupa por sede.

**Por qué la 3 y no la 5**, que es la que da un ID de verdad: porque una sede no
tiene vida fuera de su evento —no se enlaza, no se busca, no se comparte entre
ediciones— y un CPT trae consigo un juego de trece capacidades, su reparto por
rol y su pantalla en el escritorio. El CONTRATO fija tres tipos de contenido y
esta necesidad no justifica un cuarto.

**El techo de la opción 3, dicho:** no se puede pedir a la base de datos «los
eventos de tal isla» con un `meta_query`, porque el valor de la fila va
serializado. El filtro por isla se resuelve en PHP sobre las sedes del evento
—son dos o tres por evento y unas decenas de eventos vivos—, y si algún día
hace falta ese filtro a nivel de consulta, se reconsidera la opción 5 o se
añade una meta plana `evt_venue_island` de solo lectura, escrita al guardar.
Es el único disparador que justificaría rehacerlo.

### 5. Los permisos: el acotado por área en cuatro capas

El área es un ámbito, no un rol
([ADR-0006](../adr/ADR-0006-el-area-es-un-ambito-no-un-rol.md)): un término de
`evt_area` en el evento y la user meta `evt_area` en la persona, con uno o
varios `term_id` (`EventAccess::USER_AREA_META`). Solo la puede escribir quien
administra (`evt_can_edit_admin_only_fields()`):
WordPress deja a cualquiera editar su propio perfil, así que un campo de área
que edita su dueño es la puerta de al lado.

**Las páginas satélite no llevan área propia**: manda la del evento raíz, que
`post_areas()` resuelve subiendo con `root_id()`
(`src/Evt/Access/EventAccess.php`). Así una hija no se queda
huérfana de permisos al moverla.

Las cuatro capas, de fuera hacia dentro:

| # | Capa | Dónde | Qué decide | Qué **no** protege |
|---|---|---|---|---|
| 1 | Capacidades del CPT | `EventPostType.php`, `cap_map()` y `grant_caps_to_roles()` | Qué **clase** de cosa puede hacer un rol: editar, publicar, borrar | Nada por evento: quien puede editar eventos puede editarlos todos |
| 2 | `map_meta_cap` | `EventAccess::map_meta_cap()` | Sobre **qué evento en concreto**. Interviene en `edit_post`, `delete_post` y `publish_post`, y **solo para denegar** (`do_not_allow`) | Nada del listado: la fila se ve aunque no se abra |
| 3 | `pre_get_posts` en el escritorio | `EventAdmin::scope_admin_query()` | Qué filas se ven en la lista de Eventos. Sin área, `post__in = array( 0 )`: ni una fila | **Nada.** Esconder no es proteger: deja abiertos el enlace directo a `post.php?post=N`, la edición rápida, las acciones en bloque y la REST |
| 4 | El guardián `EventAccess` | `can_edit()`, `can_publish()`, `why_not_editable()` | La política, en un único sitio. Las capas 2 y 3 la consultan; no la reimplementan | — |

Dos costuras más, que no son capas pero cierran la misma puerta:
el `auth_callback` de las metas, que exige `edit_post` sobre el evento
(`EventMetaRegistration::auth_edit_event()`), y las capacidades de las
taxonomías, que son capacidades reales (`EventTaxonomies::args()`).

**Fail-closed y su única excepción.** Sin ningún área en el perfil y sin
`evt_edit_all_areas`, `can_edit()` deniega (`can_open()`, en
`EventAccess.php`). La excepción está escrita y acotada: un evento recién
creado todavía no tiene área, y lo edita quien lo creó, que es quien tiene que
ponérsela (la rama de `post_author` de `can_open()`).

**La denegación se explica en castellano y desde un solo sitio**
(`why_not_editable()`), con tres motivos: el perfil no organiza
eventos, el perfil no tiene área, o el evento es de otra área.

#### Y una quinta capa que no es de permisos: el turno

Las cuatro capas responden a «¿puede esta persona editar este evento?». Falta la
otra pregunta, que es de **cuándo** y no de quién: dos personas del mismo área
pueden editar el mismo evento —`organizacion` y `organizacion2` comparten área a
propósito (`scripts/seed-demo.php`)— y el taller es una pantalla que se
abre, se rellena y se guarda un rato después.

Lo resuelve el **bloqueo de edición nativo de WordPress**
([ADR-0023](../adr/ADR-0023-bloqueo-de-edicion-con-el-de-wordpress.md)):
`src/Evt/PublicFront/EditLock.php`, sobre la meta `_edit_lock` del núcleo. Se
elige el nativo y no uno propio porque **se comparte con `wp-admin`**: un
`evt_event` se abre en `post.php` como cualquier entrada, y un bloqueo propio
dejaría esa puerta sin vigilar.

| Pieza | Dónde | Qué hace |
|---|---|---|
| `EditLock::owner()` | `EditLock.php` | `wp_check_post_lock()` sobre el **evento raíz** (`EventAccess::root_id()`) |
| `EditLock::require_available()` | `EditLock.php`, llamada desde `EventWorkspace.php` y `PageForm.php` | Antes de escribir una sola meta: si hay dueño, `409` con su nombre |
| `EditLock::claim()` | `EditLock.php`, desde `EventWorkspace.php` y `PageForm.php` | Toma el bloqueo al pintar la pantalla, no al construir el modelo |
| `EditLock::release()` | `EditLock.php`, desde `EventWorkspace.php` | Suelta **solo el propio**, comparando el usuario de la meta |
| `EditLock::handle()` | `EditLock.php`, en `init` 20 (`EventWorkspace.php`) | «Tomar posesión», por POST, con nonce **y** con `EventAccess::can_edit()` |
| El aviso | `EditLock::render()`, `EditLock.php` | Un `<dialog>` nativo, con el nombre de quien lo tiene; se ve sin JavaScript |
| El Heartbeat | `assets/js/evt-app.js` | `wp-refresh-post-lock` renueva y avisa sin recargar; una baliza lo suelta al cerrar la pestaña |

Tres cosas que conviene tener presentes al añadir una pantalla nueva:

1. **El bloqueo es del evento raíz**, no de la página abierta. Como todo pasa por
   `root_id()`, una pantalla nueva queda cubierta sin acordarse de nada: medido
   el 2026-09-13, editar la sección 25 sale bloqueado y la sección **no lleva
   `_edit_lock` propia**.
2. **La pantalla bloqueada se pinta entera dentro de un `<fieldset disabled>`**,
   así que el navegador ni siquiera envía sus campos; el `409` es la segunda
   línea, la que atrapa una pantalla vieja.
3. **A quien solo puede consultar no se le cuenta ningún bloqueo**
   (`EditLock::status()` devuelve `none()` con `can_edit` falso): un evento
   marcado como histórico no le quita el turno a nadie.

#### Tabla rol × capacidad

Las trece capacidades del mapa (`EventPostType::cap_map()`) existen
en tres juegos: `evt_event`/`evt_events`, `evt_speaker`/`evt_speakers` y
`evt_activity`/`evt_activities`. La tabla se lee sobre eventos; los de ponentes y
actividades se reparten igual salvo que ahí el área **sí** se lleva también lo
privado y lo ajeno, porque son fichas de trabajo interno
(`grant_caps_to_roles()`).

| Capacidad | `evt_organiser` | `administrator` | Qué abre |
|---|:-:|:-:|---|
| `read` | ✓ | ✓ | entrar en el escritorio |
| `upload_files` | ✓ | ✓ | subir el cartel y los adjuntos |
| `edit_evt_event` | ✓ | ✓ | abrir un evento concreto (pasa por la capa 2) |
| `read_evt_event` | ✓ | ✓ | verlo |
| `delete_evt_event` | ✓ | ✓ | borrarlo (pasa por la capa 2) |
| `edit_evt_events` | ✓ | ✓ | ver el menú Eventos y «Añadir evento» |
| `edit_others_evt_events` | ✓ | ✓ | editar lo de una compañera del área |
| `publish_evt_events` | ✓ | ✓ | publicar |
| `read_private_evt_events` | ✓ | ✓ | ver borradores y privados del área |
| `delete_evt_events` | ✓ | ✓ | mandar a la papelera |
| `delete_published_evt_events` | ✓ | ✓ | retirar lo ya publicado |
| `edit_published_evt_events` | ✓ | ✓ | corregir lo ya publicado |
| `evt_edit_custom_css` | ✓ | ✓ | el campo de **CSS a medida** del evento y de cada sección |
| `delete_others_evt_events` | — | ✓ | borrar lo de otra persona |
| `edit_private_evt_events` | — | ✓ | editar privados ajenos |
| `delete_private_evt_events` | — | ✓ | borrar privados ajenos |
| `evt_edit_all_areas` | — | ✓ | **saltarse el acotado por área** |
| `evt_manage_app` | — | ✓ | Ajustes, diagnóstico, términos de las taxonomías y **desmarcar** el histórico |
| `evt_edit_custom_js` | — | ✓ | el campo de **JavaScript a medida**; en multisitio, además, `unfiltered_html` |

**Las columnas eran tres hasta el 2026-09-13.** Había un rol intermedio,
`evt_coordinator`, que se distinguía de `evt_organiser` en una sola capacidad
—`evt_edit_all_areas`— y se retiró justamente por eso: un rol que solo se
diferencia en «saltarse el ámbito» no nombra una función, nombra no tener
ámbito, y eso ya se llama `administrator`
([ADR-0006](../adr/ADR-0006-el-area-es-un-ambito-no-un-rol.md), opción 5). Sus
filas se leen en la columna de `administrator`.

Se lee así: **el área se lleva el juego de trabajo entero de su ámbito.** Lo
que le falta —borrar y editar lo privado y lo ajeno— es deliberado: dentro de
un área se colabora, pero retirar el trabajo de otra persona es una operación
de administración.

Las dos capacidades de código son **dos y no una** porque el riesgo no es el
mismo, y **no se reparten igual**
([ADR-0014](../adr/ADR-0014-css-del-area-javascript-de-administracion.md)): **el CSS cambia cómo se ve una página; el JavaScript ejecuta código
en el navegador de cada visitante**, con su sesión. Un CSS mal escrito deja la
página fea y se deshace recargando; un JavaScript mal copiado se lleva la sesión
de quien mira y es exactamente el poder que WordPress protege con
`unfiltered_html`. Así que el CSS es del área, que es quien maqueta, y el
JavaScript se queda en `administrator`, que además exige `unfiltered_html`
cuando el sitio es multisitio (`EventAccess::can_edit_custom_js()`) —lo
contrario de lo que hace hoy la concesión global del sistema anterior—.
Tener cualquiera de las dos no salta el acotado por área: `EventAccess` exige
también poder editar ese evento.

`evt_manage_app` no se concede a `evt_organiser`: solo al
`administrator` (`evt_register_roles()`). Es la capacidad que
abre Ajustes (`Settings::menu()`), la que administra los términos de las
tres taxonomías (`EventTaxonomies::args()`) y la que **desmarca** un evento
histórico.

El reparto es **aditivo e idempotente**: crea lo que falta y nunca quita nada
(`EventPostType::grant_caps_to_roles()`, `evt_register_roles()`), de modo
que lo que se conceda a mano en WPFront sigue ahí en la siguiente carga y
quitar una capacidad se hace en el código, que es donde se ve.

Los roles de área de hoy —uno por unidad organizadora— **no se tocan ni se
borran**, y quien los tiene conserva exactamente lo que tiene. Lo que cambia es
que quien deba organizar eventos recibe además `evt_organiser` y su área en el
perfil.

### 6. Las pantallas y el escritorio

**Lo que ve un área (`evt_organiser`).** Un menú «Eventos» con el listado
acotado a sus áreas por `tax_query` sobre `evt_area`, con `include_children`
(`EventAdmin.php`), y dos columnas añadidas tras el título: **Área** y
**Estado** (`EventAdmin::columns()`). El estado se calcula al pintar la fila
desde las fechas del evento raíz (`EventState::of()`): no hay casilla que
rellenar ni que
olvidar. Dentro del menú, los submenús de Ponentes y Actividades
(`SpeakerPostType::register()`, `ActivityPostType::register()`). No ve Ajustes.

Un área sin ningún término en su perfil ve **el listado vacío**, no el listado
completo (`EventAdmin::scope_admin_query()`). Es incómodo a propósito: un listado completo por un campo
sin rellenar es exactamente cómo un área acaba leyendo la de otra.

**Lo que ve administración.** Lo mismo sin el acotado —todas las
áreas, con la columna Área haciendo de filtro visual— más «Ajustes», que hoy **no guarda
nada** (`Settings::render()`): cuenta qué tipos y taxonomías están
registrados, cuántos términos tiene cada una, si los roles existen y qué
capacidades les faltan —preguntándoselo a WPFront cuando está instalado
(`evt_roles_status()`)— y a qué áreas está acotada la persona que mira
(`Settings::render()`). Es la pantalla que responde «¿está
el snippet activo?» sin abrir la base de datos.

**Lo que ve el público.** La raíz del evento y sus páginas satélite, que son
`public => true`. Ponentes y actividades no tienen URL propia y devuelven 404 si
se pide una: se ven dentro de la página del evento.

**Cómo se pinta la página pública, desde el 2026-09-13.** El módulo de
presentación existe y **pinta el documento entero**, sin el tema
([ADR-0022](../adr/ADR-0022-la-pagina-de-evento-se-pinta-entera.md)):
`PublicFront/EventView.php` se engancha a `template_redirect` con prioridad 20
—la misma que el armazón de las pantallas— y sirve lo que monta
`PublicFront/EventLayout.php`, desde `<!doctype html>` hasta `</html>`. La
cabecera y el pie los escribe `View/EventChrome.php`, y son de quien despliega:
vienen vacíos y se rellenan desde fuera
([ADR-0030](../adr/ADR-0030-el-repositorio-se-publica-sin-nada-de-nadie.md)). El
cuerpo son **bloques con nombre** (`EventLayout::add_block()`) y de serie hay
tres, en
`PublicFront/Block/`: el contenido de la página, el cartel del evento y la
rejilla de tarjetas de sección. Un bloque nuevo —programa, ponentes, talleres—
se registra y sale, sin tocar el armazón.

El aspecto son **propiedades personalizadas** escritas en un solo `<style>` a
partir de las metas del evento; los valores por defecto viven en
`assets/css/evt-evento.css`, que viaja inlineada dentro del bundle. Por eso el
CSS a medida de un evento cambia un token y no pelea con la especificidad de
nadie ([ADR-0014](../adr/ADR-0014-css-del-area-javascript-de-administracion.md)).

Lo que sigue pintando el tema es **un evento con la meta `evt_legacy`**: su
contenido es contenido del maquetador, congelado y sin el maquetador se rompería
([ADR-0008](../adr/ADR-0008-migracion-de-los-eventos-historicos.md)). Es una
comprobación de una meta, y se borra el día que no quede ninguno.

Medido en el wp-env el 2026-09-13 sobre el mismo evento por los dos caminos, el
documento propio son **43,3 KB, 2 hojas de estilo y 1 guion** frente a **77,7
KB, 3 hojas y 2 guiones** del camino del tema; sin scroll horizontal a 320,
400, 768 ni 1440 px. Aviso de la propia ADR-0022: el tema del wp-env es Twenty
Twenty-Five y no el tema, así que esa columna es un suelo y no el «antes» de
producción.

**Lo que todavía no pinta.** La parrilla del programa y la ficha de ponentes
siguen sin portarse: son dos bloques que registrar, y no están escritos.

### 7. Cómo se despliega

Dos artefactos y dos snippets, nada más
([ADR-0001](../adr/ADR-0001-repo-entorno-desarrollo-no-plugin.md),
[ADR-0002](../adr/ADR-0002-sincronizacion-snippets-eval-file.md)):

| Artefacto | Cómo se genera | Snippet de destino | Prioridad |
|---|---|---|---:|
| `snippets/evt-eventos-app.bundle.php` | `make bundle` → `php build/pack-snippet.php` (el target `bundle`) | `EVT — Aplicativo de eventos (CPT)` | 15 |
| `snippets/roles-and-profiles.php` | se escribe a mano; **no entra en el bundle** | `EVT — Roles y perfiles` | 5 |
| `snippets/bootstrap5.php` | se escribe a mano; **no entra en el bundle** | `EVT — Bootstrap 5` | 20 |

El empaquetador concatena los ficheros en el orden de `load-order.php`
(`build/pack-snippet.php`), toma la versión de la primera cabecera del
CHANGELOG y la escribe en el `@version` del bundle, e inyecta la guarda
`EVT_BUNDLE_LOADED` justo después del primer `namespace`.
Esa guarda no es un adorno: Code Snippets **vuelve a evaluar** un snippet
activo al guardarlo, y sin ella el segundo `eval()` muere redeclarando clases.
El bundle no se edita nunca a mano.

En local, `make sync-snippets` empuja los dos ficheros a Code Snippets vía
`wp eval-file` (el target `sync-snippets`) y `make snippet-check` comprueba que
sobreviven al doble guardado (`scripts/snippet-check.php`). El repositorio se monta en
`wp-content/evt-dev` (las `mappings` de `.wp-env.json` y la variable `EVT_DEV` del `Makefile`).

**El matiz del multisitio.** El destino es un subsitio de un multisitio, y el
entorno local **no es multisitio**: `.wp-env.json` no lo configura.
Consecuencias concretas:

- Toda invocación de `wp eval-file` necesita `--url=` para caer en el subsitio
  correcto. El Makefile lo tiene previsto en la variable `WP_URL` del `Makefile`, que
  resuelve a `--url=$(EVT_URL)` cuando esa variable está puesta, pero está
  **vacía por defecto**: es lo correcto en local y lo que hay que rellenar
  contra la red.
- El aplicativo se instala **en el subsitio, no en la red**: activarlo en red lo
  cargaría en todos los subsitios que cuelguen de ella.
- **No está verificado** si Code Snippets está activado en red o por sitio en la
  red de destino, y por tanto si la tabla es `wp_N_snippets` o `wp_ms_snippets`.
  De eso depende la librería de sincronización, escrita para el caso «activado
  por sitio». Es el hueco de despliegue conocido y no resuelto.
- Un mu-plugin no es alternativa: en un multisitio `wp-content/mu-plugins` es
  de la red.

**De dónde salen las librerías de terceros**
([ADR-0015](../adr/ADR-0015-librerias-de-terceros-desde-cdn-con-sri.md)). El CSS
y el JavaScript **propios** viajan en línea dentro del bundle
(`Assets::set_inline()`); las librerías de terceros, no. En producción se piden a
`cdn.jsdelivr.net/npm/…` con la versión clavada en la URL, `integrity` y
`crossorigin`; en desarrollo y en los tests, el mu-plugin
`scripts/mu-plugins/evt-dev-tools.php` reescribe esas URL a `node_modules` con
tres condiciones —paquete
instalado, versión exacta y fichero presente—, para que la CI no dependa del DNS.
La versión es la misma en `package.json`, en la URL y en el `$ver` del encolado.
Si el CDN no contesta, la pantalla sigue siendo utilizable: la hoja propia pinta
el aspecto base.

**El caso de Bootstrap, que no es teórico.** El aplicativo se dibuja con
Bootstrap 5.3.3 y el subsitio de destino carga hoy **Bootstrap 4.5.2** desde un
CDN, con un fragmento pegado a mano, activo y de ámbito global (comprobado en
la investigación, `.local/`). Ese fragmento registra el handle `bootstrap-css`,
que es el primero que buscaba
`Assets::has_bootstrap()`: en producción habría devuelto `true`, el `body`
habría perdido la clase `evt-sin-bootstrap` y el aplicativo se habría dibujado
dando por hecho Bootstrap 5 sobre una página con Bootstrap 4 —perfecto en local
y roto al desplegar—. Por eso hay un snippet suelto, `snippets/bootstrap5.php`,
que retira esos handles y pone Bootstrap 5 **solo en las pantallas del
aplicativo y en las páginas de evento**: el resto del subsitio se queda como
está. Retirar el fragmento antiguo es P-13 del
[plan](../plan/PLAN-0001-implantacion-por-fases.md); mientras siga activo, esto
es convivencia, no solución.

### 8. Convivencia con lo legacy durante la transición

Los dos sistemas conviven, y esa convivencia tiene reglas
([ADR-0007](../adr/ADR-0007-las-inscripciones-siguen-en-el-sistema-anterior.md),
[ADR-0008](../adr/ADR-0008-migracion-de-los-eventos-historicos.md)):

| Pieza de hoy | Qué le pasa |
|---|---|
| El formulario de alta y la página desde la que se rellena | Siguen vivos mientras haya eventos que se creen por ahí |
| La plantilla que interpola la página | Sigue generando el contenido de lo que se cree por el camino viejo |
| Páginas de eventos ya publicadas | Cambian de `post_type` a `evt_event` conservando ID, slug, contenido, fecha, autoría y adjuntos. Llevan la meta `evt_legacy` y su el tema **no se toca** |
| Las páginas de sistema del subsitio | **Siguen siendo `page`** |
| Las pocas páginas raíz sin clasificar | Se miran una a una antes de migrar; no se adivinan |
| El gestor de formularios y sus extensiones | Siguen instalados y actualizados: sin ellos las páginas históricas dejan de verse |
| Los formularios de inscripción y sus vistas de gestión | Intactos ([ADR-0007](../adr/ADR-0007-las-inscripciones-siguen-en-el-sistema-anterior.md)) |
| Los fragmentos de aforo | Intactos, fuera del bundle |
| El rol que gestiona las inscripciones | Intacto, con sus capacidades del gestor de formularios |
| La concesión global de `unfiltered_html` | Se acota mientras el formulario antiguo viva; el aplicativo nuevo no lleva concesión ninguna |

**El riesgo de la convivencia, nombrado.** La acción «Crear Página» del
formulario antiguo se dispara al crear, al actualizar **y al importar** una
entrada: reabrir y guardar la entrada de un evento **ya migrado** volvería a
escribirle el título, el slug, el estado y —vía la plantilla— el contenido. La
regla operativa es simple y hay que escribirla en el runbook: **un evento
migrado no se vuelve a tocar desde el formulario antiguo.** Mientras las dos
vías estén abiertas, quien migre un evento debe cerrar o marcar su entrada del
formulario.

## Comportamiento ante errores y casos límite

| Situación | Qué hace hoy el código | Valoración |
|---|---|---|
| Perfil sin área, sin `evt_edit_all_areas` | Listado vacío (`EventAdmin::scope_admin_query()`) y `can_edit()` deniega (`EventAccess.php`), con motivo en castellano (`why_not_editable()`) | Correcto: fail-closed |
| Evento recién creado, todavía sin área | Lo edita su autoría (`EventAccess.php`) | Correcto, pero si nadie le pone el área el evento queda visible solo para su autoría y la administración, sin aviso |
| Fecha mal tecleada | `sanitize_date()` la guarda **vacía** (`EventMetaRegistration.php`) | **Discutible**: se pierde el dato en silencio y el evento queda «próximo» para siempre (`EventState.php`). Debería avisarse en la pantalla de edición |
| Fin anterior al inicio | `EventState` normaliza fin = inicio (`of()`); `EventInput` sí lo marca como error `date_order` (`EventInput::validate()`) | Las dos reglas conviven porque `EventInput` valida la entrada y `EventState` no puede fallar al pintar |
| Evento de un solo día | Fin vacío = fin igual a inicio (`EventState.php`) | Correcto: no obliga a rellenar dos campos |
| Tipo de sección desconocido | Cae en `otra` (`sanitize_section_type()`) | Correcto: nunca rompe el guardado |
| Página satélite sin tipo de sección | `EventInput` lo marca como error (`EventInput::validate()`) | **Hueco**: nada obliga a pasar por `EventInput` desde el escritorio, así que hoy la meta puede quedarse vacía en una hija |
| Bucle o jerarquía muy profunda | `root_id()` corta a los 10 saltos (`EventAccess.php`) | **Discutible**: un ciclo devuelve un ID intermedio en silencio, y el evento acaba tomando el área de otro. Debería registrarse |
| Petición sobre un post que no es `evt_event` | `map_meta_cap()` devuelve las caps sin tocar y `can_edit()` deniega | Correcto: el guardián no opina sobre lo que no es suyo |
| El bundle se guarda estando activo | La guarda `EVT_BUNDLE_LOADED` corta el segundo `eval()` (la guarda que inyecta `build/pack-snippet.php`) | Correcto, y comprobado por `make snippet-check` |
| El snippet de roles no está activo | Ajustes lo dice en vez de romper (`Settings::render()`) | Correcto |
| Las taxonomías no tienen términos | El perfil no ofrece ninguna área que asignar (`evt_render_profile_fields()`) y **todo el mundo queda fail-closed** | Correcto pero peligroso el día 1: el orden de provisión importa. Los términos van antes que las personas |
| Evento ya finalizado | **No pasa nada.** `can_edit()` no mira las fechas | **Correcto, y ya no es un hueco.** La [ADR-0012](../adr/ADR-0012-politica-de-edicion-y-auditoria.md) descarta el cierre por fechas en su opción 3: cuando una jornada acaba es cuando más se toca su página. Lo que cierra un evento es la marca de histórico ([ADR-0017](../adr/ADR-0017-el-estado-historico-cierra-la-edicion.md)) |
| Cualquier mutación | No se registra nada | **Hueco frente a [ADR-0012](../adr/ADR-0012-politica-de-edicion-y-auditoria.md)**: la auditoría (actor, momento UTC, clave, valor anterior y nuevo) está decidida y sin escribir |

## Estrategia de pruebas

Ocho ficheros bajo `tests/unit/`, ejecutados con PHPUnit sobre un WordPress
vivo levantado por wp-env:

| Fichero | Qué cubre |
|---|---|
| `tests/unit/test-load-order.php` | Que la lista y los ficheros de `src/Evt/` no se separan |
| `tests/unit/test-boot.php` | Que `App::boot()` es idempotente y deja enganchado lo que sostiene el aplicativo |
| `tests/unit/test-post-types.php` | Registro de los tres tipos, jerarquía, capacidades y reparto por rol |
| `tests/unit/test-taxonomies.php` | Registro de las tres taxonomías y sus capacidades |
| `tests/unit/test-event-state.php` | Los tres estados y las dos normalizaciones. Sin WordPress |
| `tests/unit/test-event-input.php` | Validación de alta y edición, raíz frente a satélite. Sin WordPress |
| `tests/unit/test-event-access.php` | El guardián: acotado por área, fail-closed, la excepción de la autoría y `map_meta_cap` |
| `tests/unit/test-roles-and-profiles.php` | Registro idempotente de roles y el campo de área del perfil |

Se ejecutan con `make test`; `make check` encadena `lint`, `phpmd`,
`check-provision` y `test` (el target `check`). En CI
([ADR-0011](../adr/ADR-0011-ci-y-politica-de-pruebas.md)) el suelo bloqueante es
el de Codecov sobre el parche —10 % de arranque, que sube en un PR propio que
diga por qué—, no el total del proyecto.

**Lo que no se prueba, y conviene saberlo:**

- **El bundle.** Los tests cargan `src/Evt/` y se saltan
  `snippets/*.bundle.php`. Lo único que lo cubre es `make snippet-check`, que
  comprueba la doble evaluación, no el comportamiento.
- **El multisitio.** El entorno local es un sitio único.
- **Producción.** Ningún workflow habla con el subsitio de destino.
- **La presentación**, porque todavía no existe.

## Criterios de aceptación

De la fase 1. Los tres primeros ya se cumplen sobre el código de hoy; el resto
son las condiciones de «hecho» que faltan.

- [x] `evt_event` está registrado, es jerárquico, soporta título, editor,
      autoría, imagen destacada, extracto y atributos de página, y usa
      `capability_type` propio con `map_meta_cap => true`.
- [x] Las tres taxonomías están registradas sobre `evt_event` con capacidades
      que existen en el sitio, y ninguna de ellas es el estado.
- [x] Una persona con `evt_organiser` y el área A no abre un evento del área B
      por el enlace directo: `map_meta_cap` devuelve `do_not_allow`.
- [x] Una persona sin área y sin `evt_edit_all_areas` ve el listado vacío y no
      edita nada.
- [ ] Las sedes son una lista: se puede añadir una tercera sede a un evento sin
      tocar el esquema ni duplicar ningún bloque, y una actividad apunta a la
      suya.
- [x] Un evento `finalizado` **lo sigue editando su área**, y el cierre lo pone
      la marca de histórico, no el calendario
      ([ADR-0012](../adr/ADR-0012-politica-de-edicion-y-auditoria.md) y
      [ADR-0017](../adr/ADR-0017-el-estado-historico-cierra-la-edicion.md)).
- [ ] Toda mutación de un `evt_event` deja actor, momento en UTC, clave, valor
      anterior y valor nuevo.
- [ ] Una página del evento pinta su navegación, su programa y sus ponentes
      **leyendo el dato del evento**: cambiar la fecha del evento cambia lo que
      se ve, sin volver a guardar nada
      ([ADR-0010](../adr/ADR-0010-la-pagina-la-genera-codigo-versionado.md)).
- [ ] Las URL de las 163 páginas migradas siguen respondiendo tal cual.
      **Hoy no se cumple**: el CPT reescribe en `/evento/<slug>/`
      (`EventPostType::register()`) y las páginas viven en `/eventos/<slug>/`.
- [ ] `make check` pasa en verde y `make bundle` deja el bundle sin diferencias
      respecto al versionado.
- [ ] El bundle y el snippet de roles se activan en el subsitio de destino con
      Code Snippets, en ese orden de prioridad (5 antes que 15), y la pantalla
      de Ajustes dice «Registrado» y «Correcto» en todas las filas.
- [ ] Ni `src/Evt/` ni el snippet de roles conceden `unfiltered_html`, ni lo
      mencionan.

## ADR requeridas o referenciadas

| Decisión | ADR | Estado |
|----------|-----|--------|
| El repositorio es un entorno de desarrollo, no un plugin | [ADR-0001](../adr/ADR-0001-repo-entorno-desarrollo-no-plugin.md) | Aceptada |
| Sincronización de snippets con `wp eval-file` y la API de Code Snippets | [ADR-0002](../adr/ADR-0002-sincronizacion-snippets-eval-file.md) | Aceptada |
| Un CPT jerárquico en lugar de páginas creadas por un formulario | [ADR-0003](../adr/ADR-0003-cpt-jerarquico-frente-a-paginas-generadas.md) | Aceptada |
| Una taxonomía por dimensión en lugar de la única `convocatoria` | [ADR-0004](../adr/ADR-0004-una-taxonomia-por-dimension.md) | Aceptada |
| El estado del evento se deriva de las fechas | [ADR-0005](../adr/ADR-0005-el-estado-del-evento-se-deriva-de-las-fechas.md) | Aceptada |
| El área es un ámbito, no un rol | [ADR-0006](../adr/ADR-0006-el-area-es-un-ambito-no-un-rol.md) | Aceptada |
| Las inscripciones se quedan en el sistema anterior durante la fase 1 | [ADR-0007](../adr/ADR-0007-las-inscripciones-siguen-en-el-sistema-anterior.md) | Aceptada |
| Se migra el contenedor de los eventos históricos y se congela su contenido | [ADR-0008](../adr/ADR-0008-migracion-de-los-eventos-historicos.md) | Aceptada |
| Identificadores internos en inglés, lo que se ve en castellano | [ADR-0009](../adr/ADR-0009-identificadores-internos-en-ingles.md) | Aceptada |
| La página la genera código versionado, no una plantilla del sistema anterior | [ADR-0010](../adr/ADR-0010-la-pagina-la-genera-codigo-versionado.md) | Aceptada |
| Integración continua y política de pruebas | [ADR-0011](../adr/ADR-0011-ci-y-politica-de-pruebas.md) | Aceptada |
| Política de edición y auditoría | [ADR-0012](../adr/ADR-0012-politica-de-edicion-y-auditoria.md) | Aceptada |
| El editor de código es el que ya trae WordPress | [ADR-0013](../adr/ADR-0013-el-editor-de-codigo-es-el-de-wordpress.md) | Aceptada |
| El CSS a medida es del área; el JavaScript, solo de administración | [ADR-0014](../adr/ADR-0014-css-del-area-javascript-de-administracion.md) | Aceptada |
| Las librerías de terceros se cargan desde jsDelivr con SRI; en desarrollo, desde `node_modules` | [ADR-0015](../adr/ADR-0015-librerias-de-terceros-desde-cdn-con-sri.md) | Aceptada |
| La página pública de un evento se pinta entera, sin el tema | [ADR-0022](../adr/ADR-0022-la-pagina-de-evento-se-pinta-entera.md) | Aceptada |
| Que dos personas no se pisen: el bloqueo de edición es el nativo de WordPress | [ADR-0023](../adr/ADR-0023-bloqueo-de-edicion-con-el-de-wordpress.md) | Aceptada |
| Reescritura de URL: conservar `/eventos/<slug>/` en la raíz del subsitio | **ADR pendiente** | — |
| Diseño de las sedes repetibles (este documento, §4) | **ADR pendiente** | — |

## Pendientes y trabajo futuro

### Lo que falta para cerrar la fase 1

- [ ] **Las sedes** (§4): declarar `evt_venue` como meta repetible, la clave
      estable por sede y `evt_venue_key` en `evt_activity`. Hoy es un texto
      libre (`EventMetaKeys::VENUE`), que es el campo de sedes de hoy con otro
      nombre.
- [x] **El armazón de la vista pública**, que sustituye a la plantilla antigua
      y a la vista de la portada: documento entero, cabecera, navegación entre
      secciones, tarjetas de la portada y pie del sitio
      ([ADR-0022](../adr/ADR-0022-la-pagina-de-evento-se-pinta-entera.md)).
- [ ] **Los dos bloques que faltan** en ese armazón: la parrilla del programa y
      la ficha de ponentes, leyendo el dato del evento
      ([ADR-0010](../adr/ADR-0010-la-pagina-la-genera-codigo-versionado.md)).
- [ ] **La auditoría** de la
      [ADR-0012](../adr/ADR-0012-politica-de-edicion-y-auditoria.md), que sigue
      sin escribirse. El corte por evento finalizado que alguna vez se
      pensó **no es un pendiente**: esa ADR lo descarta, y no hay ningún cierre
      automático por fechas. La auditoría, en cambio, hace
      ahora más falta que antes, porque hay tres cosas que se hacen y no dejan
      rastro: marcar un evento como histórico
      ([ADR-0017](../adr/ADR-0017-el-estado-historico-cierra-la-edicion.md)),
      tomar posesión de un evento que otra persona estaba editando
      ([ADR-0023](../adr/ADR-0023-bloqueo-de-edicion-con-el-de-wordpress.md)) y
      mandar una sección a la papelera
      ([ADR-0016](../adr/ADR-0016-borrar-es-enviar-a-la-papelera.md)). Qué pieza
      del ecosistema lo resolvería, con su coste, está catalogado en `.local/`.
- [ ] **Las metas que las ADR fijan y no están declaradas**:
      el ID del formulario de inscripción y el de su vista de gestión
      ([ADR-0007](../adr/ADR-0007-las-inscripciones-siguen-en-el-sistema-anterior.md)),
      `evt_legacy` ([ADR-0008](../adr/ADR-0008-migracion-de-los-eventos-historicos.md))
      y la lista corta de ajustes de presentación por evento
      ([ADR-0010](../adr/ADR-0010-la-pagina-la-genera-codigo-versionado.md)).
- [ ] **La reescritura de URL**: decidir y escribir cómo se conserva
      `/eventos/<slug>/`. Es un criterio de aceptación que hoy no se cumple.
- [ ] **El script de migración** de las páginas a `evt_event` y del reparto de
      los términos de la taxonomía única entre las tres nuevas —área, tipología
      y curso, más los de estado, que desaparecen: reparto exacto, sin resto—,
      con la revisión a mano de las pocas páginas raíz todavía sin clasificar
      como evento o como sistema
      ([ADR-0008](../adr/ADR-0008-migracion-de-los-eventos-historicos.md)).
- [ ] **Verificar el despliegue en el subsitio**: si Code Snippets está
      activado en red o por sitio, y con qué tabla.
- [ ] **El acotado de la concesión global de `unfiltered_html`** mientras el
      formulario antiguo siga vivo.

### Fase 3: inscripciones, talleres, aforo y certificados

Nada de esto es un compromiso: son las piezas que la fase 3 tendría que
resolver y el código del ecosistema que ya las resuelve, para no empezar de
cero. Los disparadores que abren la fase 3 están en
[ADR-0007](../adr/ADR-0007-las-inscripciones-siguen-en-el-sistema-anterior.md).

| Pieza | Qué haría falta | Qué ya está resuelto y dónde |
|---|---|---|
| **Control de aforo por taller y turno** | Un solo mecanismo parametrizado en vez de un fragmento por evento | En la investigación (`.local/`) hay tres fragmentos que, juntos, dicen cómo se hace: uno pinta las plazas libres; otro pone la mitad que falta, validar en el guardado, que es lo que cierra la carrera entre dos inscripciones simultáneas —pintar la disponibilidad no basta—; y un tercero cuenta varios turnos por separado |
| **Inscripción** | Una plantilla única en lugar de un formulario nuevo por evento | En el sitio ya existe un formulario «plantilla de inscripción», sin estrenar |
| **Clonar la edición del año pasado** | Duplicar un evento con su programa | El sistema anterior ya duplica un formulario con sus vistas y remapea los campos por nombre (`.local/`) |
| **Caché del listado público** | Si la portada pesa | Un fragmento del sistema anterior (`.local/`): transients invalidados por «salt» al guardar y por TTL, sin cachear filtros |


Hay más piezas ya resueltas en otros aplicativos de la casa —certificados,
avisos por correo, exportaciones, calendario, adjuntos con datos personales—.
Están catalogadas **fuera de este repositorio**, en `.local/`, porque viven en
repositorios privados y sus rutas no pueden publicarse
([ADR-0030](../adr/ADR-0030-el-repositorio-se-publica-sin-nada-de-nadie.md)).
Un aviso sobre esa tabla: es material del ecosistema, no una dependencia. Se
lee y se adapta; no se copia sin leer.

## Referencias

- `.local/` (investigación del sistema anterior) — cómo funciona hoy y la foto
  del 2026-09-12 sobre la que están medidos los hechos de este documento. No se
  versiona
  ([ADR-0030](../adr/ADR-0030-el-repositorio-se-publica-sin-nada-de-nadie.md)).
- [Índice de ADR](../adr/registro.md) — las catorce decisiones y sus estados.
- [PLAN-0002](../plan/PLAN-0002-continuar-el-diseno.md) — dónde se ha quedado
  el trabajo, con qué cifras y qué queda encolado.
- `src/Evt/load-order.php` — la lista de lo que existe hoy.
- `snippets/roles-and-profiles.php` — los dos roles y el campo de área.
- `build/pack-snippet.php` — el empaquetador y la guarda del bundle.
- `Makefile` — `bundle`, `sync-snippets`, `provision`, `check`.
- `.local/` — el catálogo de piezas ya resueltas en otros aplicativos, con sus
  rutas. No se versiona.
