---
id: REQ-0001
title: "Requisitos del aplicativo de eventos"
status: Aceptada
date: 2026-09-12
related:
  issues: []
  prs: []
  adrs: [ADR-0003, ADR-0004, ADR-0005, ADR-0006, ADR-0007, ADR-0008, ADR-0009, ADR-0010, ADR-0012]
  sdds: [SDD-0001, SDD-0002]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# REQ-0001 — Requisitos: aplicativo de eventos

**Estado:** vigente. Los requisitos marcados **F1** están aceptados: son el
alcance que fija la arquitectura decidida el 2026-09-12 y que desarrollan las
catorce ADR del [registro](../adr/registro.md). Los marcados **F2**, **F3** y
**F4** son **propuesta**: se escriben aquí para que el alcance de la fase 1 se
entienda por lo que deja fuera, y se cerrarán con su propia ADR cuando les
toque. Ninguno está entregado: a esta fecha no hay nada desplegado en el
subsitio `eventos`.
**Fecha:** 2026-09-12
**Fuentes:**

- Encargo verbal de la persona usuaria, recogido literal en §2.
- Foto del sistema que se sustituye, tomada el 2026-09-12. El método, el
  inventario y las cifras están en `.local/`, que no se versiona.
- El repositorio en el commit de la fase 0: `src/Evt/`,
  `snippets/roles-and-profiles.php`, `tests/unit/`.
- Real Decreto 1112/2018, de accesibilidad de los sitios web del sector
  público, que remite a la norma EN 301 549 (WCAG 2.1 nivel AA).

---

## 1. Propósito

Sustituir el aplicativo de eventos actual —un formulario de alta de **146
campos**, una plantilla de **42.165 caracteres** guardada dentro de un campo de
texto y unos roles de área sin una sola capacidad de escritura— por un
aplicativo en el que **cada área da de alta, edita y publica los eventos de su
ámbito** desde el escritorio de WordPress, con permisos que se pueden razonar,
código que se puede revisar y sin romper ninguna de las URL publicadas.

Lo que hoy duele, y que este documento traduce a requisitos:

| Síntoma que se cuenta | Lo que hay detrás, medido |
|---|---|
| «Es lento y engorroso» | 146 campos en una sola pantalla para dar de alta un evento; el contenido lo genera una plantilla con 193 bloques `[if]` anidados |
| «Las áreas no pueden tocar nada» | Los roles de área solo tienen `read` y variantes de lectura; **ninguno** tiene `edit_pages` ni `publish_pages`. Comprobado rol a rol; el inventario está en `.local/` |
| «Cada evento cuesta lo mismo que el anterior» | Más de la mitad de los formularios y de las plantillas del sistema anterior son de un evento concreto; si hay talleres, además un fragmento de código nuevo |
| «No hay forma de ver todos los eventos de un área» | La única taxonomía, `convocatoria`, mezcla cuatro dimensiones en un mismo vocabulario y no está expuesta en REST |

## 2. El encargo, literal, y lo que se deriva de él

> «serán las áreas las que podrán editar los eventos de su ámbito, publicar,
> despublicar, agregar páginas satélite, editar ponentes»

Seis piezas, y cada una obliga a algo distinto:

| Fragmento | Qué exige | Requisitos |
|---|---|---|
| «serán las áreas» | El sujeto del permiso es el área, no un rol por área. Hoy el área **es** un rol, y un rol no sabe decir «los eventos de esta área» ([ADR-0006](../adr/ADR-0006-el-area-es-un-ambito-no-un-rol.md)) | RF-ORG-01, RF-ORG-03 |
| «podrán editar» | Capacidad de escritura real sobre el contenido, que hoy no existe para ningún área | RF-ORG-05, RF-ORG-06 |
| «los eventos de su ámbito» | Acotado por área, y fallo en cerrado cuando el ámbito falta | RF-ORG-03, RF-ORG-04 |
| «publicar, despublicar» | Las dos direcciones, y despublicar sin perder la URL ni el contenido | RF-ORG-08, RF-ORG-09 |
| «agregar páginas satélite» | Alta de hijas del evento, con su tipo de sección y su orden | RF-ORG-11 … RF-ORG-14 |
| «editar ponentes» | Los ponentes son una entidad propia, reutilizable entre eventos | RF-ORG-15 … RF-ORG-18 |

## 3. Actores

| Actor | Slug | Quién es | De dónde sale su ámbito |
|---|---|---|---|
| Organización de eventos | `evt_organiser` | Personal de un área o servicio que prepara los eventos de su área | User meta `evt_area`, uno o varios `term_id` (`EventAccess::USER_AREA_META`) |
| Administración del aplicativo | `administrator` | Quien administra el subsitio; coordina además todas las áreas | Capacidades `evt_edit_all_areas` y `evt_manage_app` (`evt_register_roles()`) |
| Gestión de inscripciones | `gestor_eventos` | Quien hoy gestiona las entradas de inscripción del sistema anterior | Las capacidades de ese sistema de formularios; **no cambia en la fase 1** |
| Persona visitante | — | Cualquiera que llega al sitio | — |
| Persona participante | — | Quien se inscribe y elige taller | Su entrada en el formulario del sistema anterior, en fase 1 |

Los roles de área que hay hoy, uno por área, **no se borran y no reciben
capacidades del aplicativo** (`evt_register_roles()` nunca revoca). Retirarlos
es una operación aparte, que exige repasar antes quién los tiene.

**Hubo un tercer actor y ya no lo hay.** «Coordinación de eventos»
(`evt_coordinator`) se retiró el 2026-09-13
([ADR-0006](../adr/ADR-0006-el-area-es-un-ambito-no-un-rol.md)): se
distinguía de la organización en una sola capacidad, `evt_edit_all_areas`, y un
rol que solo se diferencia en saltarse el ámbito no describe una función sino la
ausencia de ámbito, que en WordPress ya se llama `administrator`. Sus requisitos
siguen existiendo y **conservan sus identificadores** —`RF-COO-01` … `RF-COO-05`,
que no se reutilizan ni se renumeran— y ahora los cumple la administración
(§5.2).

## 4. Qué es un evento, pieza a pieza

Un evento no es una página: es un árbol. Esto es lo que hay hoy y adónde va
cada pieza.

| Pieza | Hoy | En el aplicativo | Fase |
|---|---|---|---|
| Portada del evento | `page` raíz, con el contenido interpolado por una plantilla del sistema anterior | `evt_event` con `post_parent = 0`, contenido en `post_content` | F1 |
| Páginas satélite | `page` hija, con el `post_parent` fijado desde `$_POST` por un fragmento de código pegado a mano | `evt_event` hija con meta `evt_section_type` | F1 |
| Programa | Filas de un formulario, pintadas por plantillas que llevan la sede y la fecha **copiadas** como atributos del shortcode | CPT `evt_activity`, leído por código | F1 alta, F2 pintado completo |
| Ponentes | Filas de otro formulario, pintadas por tres plantillas distintas | CPT `evt_speaker` | F1 |
| Inscripción | Un formulario **nuevo por evento**, incrustado en la página de inscripción | Sigue igual: la satélite lleva en su contenido el shortcode del formulario que le toca ([ADR-0007](../adr/ADR-0007-las-inscripciones-siguen-en-el-sistema-anterior.md)) | F1 sin cambios, F3 dentro del aplicativo |
| Selección de talleres y aforo | Un campo del formulario de inscripción más un fragmento de código copiado una vez por evento, con el aforo escrito dentro | Dentro del aplicativo, con el aforo como dato | F3 |
| Estado del evento | Término de `convocatoria` puesto a mano, evento a evento | Derivado de las fechas ([ADR-0005](../adr/ADR-0005-el-estado-del-evento-se-deriva-de-las-fechas.md)) | F1 |
| Clasificación | Una taxonomía con cuatro dimensiones mezcladas | `evt_area`, `evt_type`, `evt_course` ([ADR-0004](../adr/ADR-0004-una-taxonomia-por-dimension.md)) | F1 |

**Fase**: F1 alta, edición y publicación · F2 migración de lo histórico ·
F3 inscripciones y talleres · F4 retirada de lo viejo. El calendario y sus
criterios de salida están en
[PLAN-0001](../plan/PLAN-0001-implantacion-por-fases.md).

---

## 5. Requisitos por actor

Cada requisito lleva su fase y **cómo se comprueba que está hecho**. Un
requisito sin forma de verificarlo es una intención, no un requisito.

### 5.1 Organización de eventos (`evt_organiser`)

| ID | Requisito | Fase | Cómo se verifica |
|---|---|---|---|
| RF-ORG-01 | Crea un evento desde el escritorio de WordPress, sin pasar por un formulario público ni por la página `crear-evento` (ID 31) | F1 | Una persona con el rol ve «Eventos → Añadir nuevo» y el alta termina en un `evt_event` publicado o en borrador |
| RF-ORG-02 | El alta obligatoria se limita a: título, área, tipología, curso escolar y fecha de inicio. Todo lo demás es opcional y se puede completar después | F1 | Contar los campos obligatorios de la pantalla de alta: **≤ 5**, frente a los 146 de la pantalla de alta anterior. Validación en `Domain/EventInput` con su test |
| RF-ORG-03 | Ve y edita únicamente los eventos cuya área está entre las de su perfil. El listado del escritorio queda acotado | F1 | Con dos áreas y dos personas de demostración, cada una ve solo lo suyo (`EventAdmin::scope_admin_query()`) |
| RF-ORG-04 | Sin ningún área en el perfil, no ve ni edita nada. El fallo es en cerrado, nunca en abierto | F1 | Usuario con el rol y sin `evt_area`: cero filas (`EventAdmin::scope_admin_query()`) y `can_edit()` falso |
| RF-ORG-05 | Edita el evento de su área lo haya creado quien lo haya creado, y también sus páginas satélite | F1 | `EventAccess::can_edit()` cierto para un evento de su área con otra autoría |
| RF-ORG-06 | Cuando no puede editar algo, la pantalla dice **por qué** en castellano, no «no tienes permisos» | F1 | `EventAccess::why_not_editable()` devuelve un motivo distinto por cada causa, y hay un test por motivo |
| RF-ORG-07 | No puede cambiar el área de su propio perfil ni la de nadie | F1 | El campo del perfil solo lo escribe quien administra (`evt_can_edit_admin_only_fields()`) |
| RF-ORG-08 | Publica los eventos de su área | F1 | `publish_evt_events` concedida al rol (`EventPostType::grant_caps_to_roles()`) y acotada por área |
| RF-ORG-09 | Despublica un evento: vuelve a borrador sin perder contenido, adjuntos ni slug, y al republicarlo la URL es la misma | F1 | Publicar → despublicar → republicar deja `post_name` y `ID` intactos |
| RF-ORG-10 | **Sigue editando el evento después de que termine**, que es cuando se suben los vídeos, las presentaciones y las fotos. Lo que cierra la edición es marcarlo como **histórico**, a mano, y eso solo lo reabre la administración | F1 | `can_edit()` no mira ninguna fecha; el cierre es la meta `evt_archived` ([ADR-0012](../adr/ADR-0012-politica-de-edicion-y-auditoria.md) y [ADR-0017](../adr/ADR-0017-el-estado-historico-cierra-la-edicion.md)). **Reemplaza al requisito anterior**, que fijaba un cierre por fechas que nunca se implementó |
| RF-ORG-23 | Escribe el **CSS a medida** de su evento y de cada una de sus páginas, cuando el panel «Apariencia» se le queda corto. El **JavaScript** a medida no: eso es de administración | F1 | Capacidad `evt_edit_custom_css` concedida a `evt_organiser` y acotada por área; `evt_edit_custom_js` no ([ADR-0014](../adr/ADR-0014-css-del-area-javascript-de-administracion.md)) |
| RF-ORG-24 | Marca su evento como **histórico** el día que decide que ya no se toca más, con un aviso de que no hay vuelta atrás sin administración | F1 | `EventAccess::can_archive()`, que es la regla del área, y confirmación con SweetAlert2 ([ADR-0017](../adr/ADR-0017-el-estado-historico-cierra-la-edicion.md)) |
| RF-ORG-11 | Agrega páginas satélite al evento, tantas como necesite | F1 | Crear una hija con `post_parent` del evento y verla colgando de él en el listado |
| RF-ORG-12 | Cada satélite lleva un tipo de sección de una **lista cerrada**, no un texto libre | F1 | `EventMetaKeys::section_types()` (`src/Evt/Meta/EventMetaKeys.php`) tiene once valores y el guardado rechaza lo que no esté |
| RF-ORG-13 | Ordena las satélites, y ese orden es el de la navegación del evento | F1 | El CPT soporta `page-attributes` (`EventPostType::register()`); cambiar el orden cambia el menú |
| RF-ORG-14 | La satélite **no** tiene área propia: manda la del evento raíz, incluso si la mueven de padre | F1 | `EventAccess::post_areas()` resuelve por `root_id()`, con test |
| RF-ORG-15 | Da de alta y edita ponentes como entidad propia, no como una fila de un formulario | F1 | CPT `evt_speaker` con su listado y sus capacidades |
| RF-ORG-16 | Un ponente se reutiliza en varios eventos sin volver a teclearlo | F1 | Asociar el mismo `evt_speaker` a dos eventos distintos |
| RF-ORG-17 | Da de alta y edita las actividades del programa (ponencia, mesa redonda, comunicación, taller) con su fecha, su sede y sus ponentes | F1 | CPT `evt_activity` con su listado |
| RF-ORG-18 | El programa que se ve **es** el dato guardado: cambiar la fecha de una actividad cambia lo que se publica, sin tocar el contenido de ninguna página | F1 pintado básico, F2 completo | Buscar `display-frm-data` en el contenido de un evento creado con el aplicativo: **cero** ([ADR-0010](../adr/ADR-0010-la-pagina-la-genera-codigo-versionado.md)) |
| RF-ORG-20 | La página de inscripción de un evento nuevo incrusta el formulario de inscripción que le corresponda | F1 | Satélite con `evt_section_type = inscripcion` y el shortcode de su formulario en el contenido |
| RF-ORG-21 | El vínculo evento → formulario de inscripción y pantalla de gestión deja de estar solo dentro del contenido y pasa a ser un dato del evento | F2 | Metas nuevas, herederas de los dos campos que hoy guardan ese vínculo dentro del formulario |
| RF-ORG-22 | Crea un evento copiando el del año anterior, que es como se trabaja hoy | F2 | Duplicar deja un borrador nuevo con las satélites y sin las fechas del anterior |

Falta el `RF-ORG-19`: era «marcar un evento como destacado para la portada», derivado de leer un campo del formulario anterior llamado «Promociona» como una marca de portada. Medido contra el vocabulario, ese campo ofrece los términos del eje de área y ninguno más: es el selector del área organizadora ([ADR-0004](../adr/ADR-0004-una-taxonomia-por-dimension.md)). El requisito se retira. Los identificadores no se reutilizan, así que el número queda vacío.

### 5.2 Coordinación de todas las áreas (`administrator`)

Estos cinco requisitos se escribieron para el rol «Coordinación de eventos»
(`evt_coordinator`), que se retiró el 2026-09-13. **Sus identificadores no se
reutilizan ni se renumeran**: siguen siendo `RF-COO-*` y lo que cambia es quién
los cumple, que ahora es la administración del subsitio.

| ID | Requisito | Fase | Cómo se verifica |
|---|---|---|---|
| RF-COO-01 | Hace todo lo de la organización en **cualquier** área | F1 | `evt_edit_all_areas` cortocircuita el acotado (`EventAccess::can_edit_all_areas()`) |
| RF-COO-02 | Edita también los eventos marcados como **históricos**, que son los que su área ya no puede tocar | F1 | Es lo que hace que «cerrado» signifique algo ([ADR-0017](../adr/ADR-0017-el-estado-historico-cierra-la-edicion.md)). El requisito decía «los ya finalizados», y el cierre por fechas se retiró ([ADR-0012](../adr/ADR-0012-politica-de-edicion-y-auditoria.md)) |
| RF-COO-03 | Ve el listado completo, sin acotar, y puede filtrarlo por área | F1 | `EventAdmin::scope_admin_query()` sale antes; el filtro por área está en la columna |
| RF-COO-04 | Reasigna el área de un evento cuando la organización se equivoca | F1 | Cambiar el término `evt_area` de un evento ajeno |
| RF-COO-05 | Da de alta el término del curso escolar nuevo cada año, y el área nueva cuando se crea un servicio | F1 | **Resuelto:** administrar términos exige `evt_manage_app` y quien cumple estos requisitos ya la tiene. Era un hueco mientras existía un rol de coordinación sin ella; al retirarse el rol, deja de haberlo |

### 5.3 Administración del aplicativo (`evt_manage_app`)

| ID | Requisito | Fase | Cómo se verifica |
|---|---|---|---|
| RF-ADM-01 | Asigna a cada persona sus áreas desde el perfil, con la jerarquía visible | F1 | Campo del perfil con las áreas sangradas (`evt_render_profile_fields()`) |
| RF-ADM-02 | Mantiene el vocabulario de las tres taxonomías | F1 | Alta de término en `evt_area`, `evt_type` y `evt_course` |
| RF-ADM-03 | Tiene una pantalla de diagnóstico que dice si los roles existen, qué capacidades les faltan y qué versión del bundle está cargada | F1 | `src/Evt/Admin/Settings.php` y `evt_roles_status()` |
| RF-ADM-04 | Consulta el registro de auditoría de un evento: quién cambió qué y cuándo | F1 | [ADR-0012](../adr/ADR-0012-politica-de-edicion-y-auditoria.md); meta del evento raíz con tope de entradas |

### 5.4 Gestión de inscripciones (`gestor_eventos`)

| ID | Requisito | Fase | Cómo se verifica |
|---|---|---|---|
| RF-INS-01 | **No cambia nada para quien gestiona inscripciones en la fase 1.** Sigue haciéndolo donde lo hace hoy, con las mismas pantallas y sin formación nueva | F1 | El bundle no toca ni las capacidades ni el rol del sistema de formularios; buscar su prefijo en `src/Evt/`: cero |
| RF-INS-02 | Ve los inscritos de un evento desde el propio evento, sin salir del aplicativo | F3 | Consulta única sobre el evento |
| RF-INS-03 | El aforo por taller es un dato configurable del evento, no un snippet copiado por evento | F3 | Un evento nuevo con talleres **no** exige tocar código |
| RF-INS-04 | La selección de talleres muestra las plazas libres y rechaza el taller lleno, en todas las filas del cuadro, no solo en la primera | F3 | Es el defecto real de la mitad de los fragmentos de código vigentes, revisados uno a uno (material en `.local/`) |
| RF-INS-05 | Exporta los inscritos a CSV, acotado por área | F3 | Descarga con el mismo acotado que el listado |
| RF-INS-06 | Responde «en qué se ha inscrito esta persona» con una consulta, no abriendo un formulario por evento | F3 | Es también un requisito legal, ver RNF-PDP-05 |

### 5.5 Persona visitante y persona participante

| ID | Requisito | Fase | Cómo se verifica |
|---|---|---|---|
| RF-VIS-01 | Todas las URL publicadas hasta hoy siguen funcionando, sin redirección | F2 | RNF-URL-01 |
| RF-VIS-02 | Cada página del evento muestra la navegación entre sus secciones, como hoy hace una plantilla del sistema anterior | F1 | Un evento con tres satélites enseña las tres desde cualquiera de ellas |
| RF-VIS-03 | La portada lista los eventos desde **una** consulta, con los históricos y los nuevos juntos | F2 | Sustituye al shortcode de listados de un plugin de terceros |
| RF-VIS-04 | Puede filtrar por área, tipología y curso escolar | F2 | Las tres taxonomías son públicas y están en REST (`EventTaxonomies::args()`) |
| RF-VIS-05 | El estado que se anuncia es correcto sin que nadie lo actualice a mano | F1 | Un evento cuya fecha de fin pasó ayer aparece como finalizado hoy, sin tocar nada |
| RF-VIS-06 | Se inscribe igual que hoy, en el mismo formulario y con el mismo aspecto | F1 | La página de inscripción no cambia |
| RF-VIS-07 | Elige taller después de inscribirse, con las plazas libres a la vista | F3 | Hoy funciona así y no puede empeorar |

---

## 6. Requisitos no funcionales

### 6.1 Rendimiento

**Aviso de honestidad, antes de la tabla.** La queja de partida es que «el
sistema es lento y engorroso». **Nadie lo ha medido.** No hay ni un tiempo de
respuesta tomado en producción, ni un cronómetro sobre el alta de un evento,
ni una traza de consultas. Lo que sí está medido es el **volumen de trabajo**
que exige el sistema —146 campos, 193 bloques `[if]` anidados, y un formulario
y dos plantillas nuevas por cada evento—, y eso es lo que explica el
«engorroso». El «lento» no se puede afirmar ni negar con lo que hay. Por eso el
primer requisito de esta sección es medir.

| ID | Requisito | Fase | Cómo se verifica |
|---|---|---|---|
| RNF-REN-01 | Antes de sustituir nada se toma una **línea base** del sistema actual: tiempo de respuesta de la portada y de la página de un evento, tiempo real de dar de alta un evento de principio a fin, y número de pantallas y campos que hay que recorrer | F1 | Informe fechado en `docs/verificacion/RENDIMIENTO-AAAA-MM-DD-linea-base.md`. Sin él, ninguna afirmación de mejora es defendible |
| RNF-REN-02 | Dar de alta un evento nuevo exige **como mucho 5 campos obligatorios** (RF-ORG-02) y una sola pantalla | F1 | Recuento directo sobre la pantalla |
| RNF-REN-03 | El contenido de una página **no se genera interpretando una plantilla en el momento de guardar**. Se escribe una vez y se lee | F1 | Un evento nuevo no interpreta ninguna plantilla del sistema anterior al guardarse |
| RNF-REN-04 | El listado del escritorio y la portada resuelven con **una** `WP_Query`, sin consultas dentro del bucle | F1 escritorio, F2 portada | Query Monitor sobre el listado con los 163 contenidos migrados |
| RNF-REN-05 | Ninguna pantalla del aplicativo pide un recurso a un CDN externo | F1 | Búsqueda de `http` en el bundle: cero URL externas. Hoy uno de los fragmentos de código activos carga Bootstrap 4.5.2 desde **un CDN que ya no existe**: es una causa medible de lentitud y de fallos intermitentes |
| RNF-REN-06 | Con los 163 contenidos cargados, el listado del escritorio se sirve en menos de **1 s** de TTFB en el entorno de pruebas, y la página pública de un evento en menos de **500 ms** sin caché | F2 | Medición repetible con el mismo método de RNF-REN-01. **Las dos cifras son provisionales**: se confirman o se corrigen cuando exista la línea base |
| RNF-REN-07 | La mejora se demuestra comparando contra la línea base, no contra una impresión | F2 | Segundo informe en `docs/verificacion/`, con el mismo método |

### 6.2 Accesibilidad

El subsitio es de una administración pública. El **Real Decreto 1112/2018**
obliga a cumplir la norma **EN 301 549**, que remite a **WCAG 2.1 nivel AA**.
No es una buena práctica opcional: es el marco legal que aplica al sitio.

| ID | Requisito | Fase | Cómo se verifica |
|---|---|---|---|
| RNF-ACC-01 | Las pantallas nuevas del aplicativo cumplen WCAG 2.1 AA | F1 | Auditoría automática (axe-core o equivalente) sin incidencias de nivel A o AA, más el repaso manual de RNF-ACC-02 y RNF-ACC-03, que ninguna herramienta automática cubre |
| RNF-ACC-02 | Todo el escritorio del aplicativo se maneja **solo con teclado**, con el foco siempre visible y en un orden que sigue al de la pantalla | F1 | Recorrido manual documentado con capturas en `docs/verificacion/` |
| RNF-ACC-03 | Cada campo tiene su `<label>` asociada; los errores se dicen **en texto**, junto al campo y enlazados con `aria-describedby`; el color nunca es el único portador de información | F1 | Revisión del marcado y prueba con el color desactivado |
| RNF-ACC-04 | Contraste mínimo 4,5:1 en texto normal y 3:1 en texto grande y en los indicadores de estado | F1 | Medición sobre la paleta usada |
| RNF-ACC-05 | Las páginas públicas del evento tienen un solo `<h1>`, jerarquía de encabezados correcta y alternativa textual en las imágenes | F2 | Auditoría sobre un evento real |
| RNF-ACC-06 | **La fecha, la sede y el plazo de inscripción existen como texto**, no solo dentro del cartel. Hoy el cartel es una imagen y en varios eventos es el único sitio donde figura la información | F1 | Un lector de pantalla llega a las fechas de cualquier evento nuevo |
| RNF-ACC-07 | Lo que **no** se hace: no se auditan ni se corrigen los **miles de PDF** de la biblioteca —los subidos al gestor de medios y los que sirve el sistema de formularios con el nombre codificado—, ni el contenido el tema de las páginas históricas. Queda dicho para que nadie lo dé por hecho | — | No aplica: exclusión declarada de alcance |

### 6.3 Protección de datos

| ID | Requisito | Fase | Cómo se verifica |
|---|---|---|---|
| RNF-PDP-01 | La fase 1 **no migra ni un solo dato personal**. Las inscripciones vivas y los consentimientos firmados no se tocan ([ADR-0007](../adr/ADR-0007-las-inscripciones-siguen-en-el-sistema-anterior.md)) | F1 | El bundle no lee ninguna tabla del sistema de formularios |
| RNF-PDP-02 | Los ponentes **sí** son datos personales: nombre, fotografía, biografía y a veces correo. Se guarda solo lo que se publica, y quien lo edita es el área que organiza | F1 | Revisión de las metas de `evt_speaker`: ninguna que no salga en la ficha pública |
| RNF-PDP-03 | El registro de auditoría guarda el `user_id`, no el nombre, y tiene un tope de entradas por evento | F1 | [ADR-0012](../adr/ADR-0012-politica-de-edicion-y-auditoria.md) |
| RNF-PDP-04 | El repositorio no contiene datos personales. El material crudo vive en `.local/`, con doble candado (`.local/.gitignore` con `*` y `/.local/` en la raíz) | F0 hecho | `git ls-files .local` vacío |
| RNF-PDP-05 | Antes de mover una sola inscripción hace falta el análisis del tratamiento: base jurídica, finalidad, plazo de conservación y quién accede. Sin ese documento, la fase 3 no arranca | F3 | Documento previo, referenciado desde la ADR de la fase 3 |
| RNF-PDP-06 | Una petición de acceso o de supresión se resuelve con una consulta. Hoy exige buscar a mano en un formulario por evento y en el directorio donde ese sistema deja los ficheros subidos, y es una obligación con plazo legal | F3 | Prueba con una persona inscrita en dos eventos |
| RNF-PDP-07 | El aplicativo **no reproduce** el patrón de servir ficheros por URL adivinable: hoy los consentimientos firmados se sirven desde una ruta pública con el nombre en base64, y base64 no es cifrado, es una codificación que revierte cualquiera | F3 | Descargar un fichero de inscripción exige comprobar capacidad |

### 6.4 Conservación de las URL

Es la restricción dura de todo el proyecto. El sitio es institucional,
público, está indexado y sus direcciones están enlazadas desde circulares,
correos y documentos publicados que nadie va a reeditar.

| ID | Requisito | Fase | Cómo se verifica |
|---|---|---|---|
| RNF-URL-01 | Las **163** URL que hoy responden siguen respondiendo en la misma dirección y con código 200. No 163 redirecciones: las mismas URL | F2 | Lista de URL sacada antes de migrar y recorrida después; el diff tiene que ser vacío |
| RNF-URL-02 | Los **ID de post no cambian**. Son la clave con la que los shortcodes buscan los datos del evento y el `post_parent` de los 4.597 adjuntos | F2 | Comparar `ID`, `post_name` y `post_parent` antes y después |
| RNF-URL-03 | El CPT reescribe en la raíz del subsitio. **Hoy no lo hace**: se registra con `'rewrite' => array( 'slug' => 'evento', … )` y produce `/eventos/evento/<slug>/` (el `rewrite` de `EventPostType::register()`, reconocido en el comentario justo encima) | F1 resolver, F2 bloqueante | Un evento nuevo se sirve en `/eventos/<slug>/`. **Sin esto, la migración no puede empezar** |
| RNF-URL-04 | Los slugs históricos no se tocan, por incoherentes que sean (`programa_`, `programa-8616`, `programacomunicacion2021`). La convención nueva solo rige para lo nuevo | F2 | La migración no escribe `post_name` |
| RNF-URL-05 | Despublicar no libera ni cambia el slug: al republicar, la URL es la de antes | F1 | RF-ORG-09 |
| RNF-URL-06 | Las URL nuevas se escriben en castellano ([ADR-0009](../adr/ADR-0009-identificadores-internos-en-ingles.md)) | F1 | Revisión del slug propuesto al crear una satélite |

### 6.5 Coste de operación

No lo pidió nadie por escrito, pero es la mitad del problema y sale de las
cifras, así que se escribe como requisito y no como aspiración.

| ID | Requisito | Fase | Cómo se verifica |
|---|---|---|---|
| RNF-OPE-01 | Un evento nuevo **no cuesta código**. Hoy cuesta un formulario duplicado, una o dos plantillas y, si hay talleres, un fragmento de código nuevo | F3 | Dar de alta un evento con talleres sin escribir ni una línea |
| RNF-OPE-02 | Toda la lógica del aplicativo vive en un único fichero desplegable, generado desde el repositorio y con su versión visible | F0 hecho | `snippets/evt-eventos-app.bundle.php` y `make bundle` |

### 6.6 Seguridad

| ID | Requisito | Fase | Cómo se verifica |
|---|---|---|---|
| RNF-SEG-01 | El aplicativo **no concede `unfiltered_html`** ni lo menciona | F1 | Búsqueda en `src/Evt/` y en `snippets/roles-and-profiles.php`: cero coincidencias |
| RNF-SEG-02 | La autorización se decide en **un solo sitio** y sobre `map_meta_cap`, no escondiendo filas del listado: un enlace directo a `post.php?post=N`, la edición rápida y la REST API tienen que dar el mismo resultado que el listado | F1 | `EventAccess::map_meta_cap()` con test por cada camino |
| RNF-SEG-03 | La concesión global de `unfiltered_html` que hace hoy un fragmento de código suelto, activa para todas las peticiones del sitio, se acota mientras viva el formulario anterior y se retira con él | F4 | Es una tarea **en producción**, no en este repositorio |

---

## 7. Fuera de alcance

Lo que este aplicativo **no** hace, dicho para que no se dé por supuesto:

| Qué | Por qué |
|---|---|
| Certificados de asistencia | No existe nada de eso en el sitio hoy y nadie lo ha pedido. Si se pide, entra en el mismo diseño que la inscripción (F3) |
| Registro de asistencia / check-in | Ídem. Hoy es una página con un shortcode de ventana temporal |
| Firma digital | Lo único que hay es una prueba inactiva, sobre un formulario de test y sin uso real |
| Encuestas de valoración | Las recoge un formulario del sistema anterior y ahí se quedan, sin decisión propia |
| Preguntas del público | Igual: viven en formularios del sistema anterior, con sus plantillas |
| Maestro de centros educativos | Es otro subsistema de la misma instalación: comparten sitio, no aplicativo |
| Traducir la maquetación el tema de lo histórico | ~30 eventos con diseño distinto cada uno. Descartado con argumentos en [ADR-0008](../adr/ADR-0008-migracion-de-los-eventos-historicos.md) |
| Retirar el sistema de formularios anterior | Mientras haya páginas `evt_legacy` y formularios de inscripción vivos, se queda |

## 8. Criterios de aceptación de la fase 1

Ninguno está cumplido a 2026-09-12. La casilla se marca cuando hay evidencia,
no cuando hay código.

- [ ] Una persona con `evt_organiser` y un área en su perfil crea, edita y
      publica un evento de su área sin tocar el sistema anterior.
- [ ] Esa misma persona no ve ni una fila de otra área, ni por el listado, ni
      por enlace directo, ni por la REST API.
- [ ] Sin área en el perfil no ve nada, y la pantalla dice por qué.
- [ ] Agrega tres páginas satélite con su tipo de sección y las ordena; la
      navegación del evento refleja ese orden.
- [ ] Da de alta un ponente y lo reutiliza en un segundo evento.
- [ ] El estado del evento cambia solo al pasar la fecha, sin que nadie lo
      toque.
- [ ] Un evento creado con el aplicativo no contiene ni un `display-frm-data`.
- [ ] Un evento nuevo se sirve en `/eventos/<slug>/` (RNF-URL-03).
- [ ] La auditoría registra un cambio de prueba con actor, momento y valores.
- [ ] Existe la línea base de rendimiento (RNF-REN-01).
- [ ] La auditoría de accesibilidad de las pantallas nuevas no tiene
      incidencias A ni AA.
- [ ] `make check` en verde y el bundle regenerado sin diferencias.

## 9. Lo que no se sabe

Se escribe aquí para que no se pierda, y porque un requisito construido sobre
una suposición es una avería aplazada.

| # | Qué falta por saber | A qué requisito afecta | Cómo se cierra |
|---|---|---|---|
| 1 | Si el CPT puede reescribir en la raíz del subsitio conviviendo con `page`. Hay al menos dos caminos y **ninguno se ha probado** | RNF-URL-03, y con él toda la fase 2 | Prueba en el entorno local con las 163 rutas cargadas |
| 2 | Si el tema procesa los shortcodes `et_pb_*` en un CPT que no es `page`. Si no lo hace, las 163 páginas se pintan como texto con corchetes | RNF-URL-01 | Casilla de ajustes del tema, verificada en el ensayo en seco |
| 3 | ~~La coordinación no puede crear términos~~ **Cerrado el 2026-09-13.** El alta de términos es tarea de administración, y ya no hay un rol de coordinación que se quede a medias: `evt_coordinator` se retira ([ADR-0006](../adr/ADR-0006-el-area-es-un-ambito-no-un-rol.md)) | RF-COO-05 | Decidido: quien organiza **usa** las áreas que hay; crearlas es administrar el aplicativo |
| 4 | Cuántas de las páginas raíz de hoy son un evento y cuáles son páginas de sistema. La investigación clasificó la mayoría y **dejó unas pocas sin decidir** | RNF-URL-01 | Revisión una a una antes de migrar |
| 5 | Qué contenido de los eventos vivos depende de HTML sin filtrar fuera del envío del formulario anterior | RNF-SEG-03 | Comprobación en producción antes de acotar esa concesión |
| 6 | Si el rendimiento percibido como «lento» es del servidor, de la red o del número de pasos | RNF-REN-01 | La línea base |
| 7 | Si el aplicativo se despliega sobre el tema o sobre otro tema. Determina qué HTML emite el pintado | RF-ORG-18 | Decisión pendiente, anotada en [ADR-0010](../adr/ADR-0010-la-pagina-la-genera-codigo-versionado.md) |

## 10. Trazabilidad

| Bloque de requisitos | Decisión que lo sostiene |
|---|---|
| RF-ORG-01, RF-ORG-11 … RF-ORG-14 | [ADR-0003](../adr/ADR-0003-cpt-jerarquico-frente-a-paginas-generadas.md) |
| RF-VIS-04, RF-COO-05, RF-ADM-02 | [ADR-0004](../adr/ADR-0004-una-taxonomia-por-dimension.md) |
| RF-VIS-05, RF-ORG-10 | [ADR-0005](../adr/ADR-0005-el-estado-del-evento-se-deriva-de-las-fechas.md) |
| RF-ORG-03, RF-ORG-04, RF-ORG-07, RF-COO-01 | [ADR-0006](../adr/ADR-0006-el-area-es-un-ambito-no-un-rol.md) |
| RF-INS-01 … RF-INS-06, RNF-PDP-01 | [ADR-0007](../adr/ADR-0007-las-inscripciones-siguen-en-el-sistema-anterior.md) |
| RNF-URL-01 … RNF-URL-04, RF-VIS-01 | [ADR-0008](../adr/ADR-0008-migracion-de-los-eventos-historicos.md) |
| RNF-URL-06 | [ADR-0009](../adr/ADR-0009-identificadores-internos-en-ingles.md) |
| RF-ORG-18, RNF-REN-03 | [ADR-0010](../adr/ADR-0010-la-pagina-la-genera-codigo-versionado.md) |
| RF-ORG-06, RF-ORG-10, RF-ADM-04, RNF-PDP-03 | [ADR-0012](../adr/ADR-0012-politica-de-edicion-y-auditoria.md) |
| RF-ORG-23 | [ADR-0014](../adr/ADR-0014-css-del-area-javascript-de-administracion.md) |
| RF-ORG-24, RF-COO-02 | [ADR-0017](../adr/ADR-0017-el-estado-historico-cierra-la-edicion.md) |

El orden en que se construye todo esto, con sus criterios de salida, está en
[PLAN-0001](../plan/PLAN-0001-implantacion-por-fases.md).
