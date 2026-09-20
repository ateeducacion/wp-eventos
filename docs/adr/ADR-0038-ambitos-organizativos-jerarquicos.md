---
id: ADR-0038
title: "Ámbitos organizativos jerárquicos para la edición"
status: Aceptada
date: 2026-09-20
related:
  issues: []
  prs: [23]
  sdds: [SDD-0002]
  adrs: [ADR-0006, ADR-0012, ADR-0030]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Codex"
  model: "unknown"
---

# ADR-0038: Ámbitos organizativos jerárquicos para la edición

## Contexto

`evt_area` ya es una taxonomía jerárquica y la meta homónima del usuario admitía varios IDs de término (`src/Evt/Taxonomy/EventTaxonomies.php`, `src/Evt/Access/EventAccess.php`). La ADR-0006 separó área y rol; esa decisión sigue vigente. El requisito posterior incorpora al Editor nativo y acceso al nodo asignado y todos sus descendientes. La comparación directa en `EventAccess::can_open()` no lo concedía. `get_term_children()` expande el árbol y `map_meta_cap` protege cada objeto.

## Decisión

La interfaz y la documentación dirán **Ámbito organizativo**. Se conserva `evt_area` como taxonomía y meta persistentes. Cada usuario Editor tiene **un** término asignado, que concede ese nodo y todos sus descendientes. La meta conserva el formato de lista de IDs para compatibilidad de lectura, pero un perfil histórico con varios términos válidos queda sin acceso hasta que administración elija uno: no se escoge arbitrariamente. También se aceptan cadenas antiguas separadas por comas. Un término inexistente o un error al obtener descendientes no concede acceso. Sin ámbito, el resultado es vacío. Administración (`evt_manage_app` o `manage_options`, con `evt_edit_all_areas`) no está acotada.

Un `evt_event` puede tener **varios** ámbitos organizadores. Cualquier usuario cuyo ámbito efectivo cruce al menos uno de ellos puede editarlo; al guardar desde el taller, los ámbitos de otras ramas se conservan. Un evento nuevo de una persona editora recibe por defecto su único ámbito asignado si no se indicó otro permitido; la interfaz permite elegir varios ámbitos autorizados. Una persona editora acotada no puede crear ni dejar un evento sin organizadores. Administración conserva la posibilidad de crear, publicar o reparar contenido huérfano. Ponentes y actividades mantienen su asociación multivalor, y las páginas satélite heredan la del evento raíz.

El rol nativo `editor` recibe las mismas capacidades de CPT de trabajo que `evt_organiser`, además de `evt_edit_custom_css`. No recibe `manage_options`, `evt_manage_app`, `evt_edit_all_areas`, gestión de términos ni JavaScript a medida. `evt_organiser` queda como compatibilidad para perfiles existentes; no se borra ni se migra automáticamente porque una persona podría tener otros roles y una conversión sin inventario cambiaría permisos. El alta funcional nueva usa `editor`. Una retirada futura requerirá una migración única, auditada e idempotente, como la de `evt_coordinator` en `snippets/roles-and-profiles.php`.

Solo administración ve y guarda el selector de ámbito de un Editor. El perfil propio no autoriza autoasignación: el guardado exige `manage_options`, `edit_user` y nonce. La autorización operativa sigue preguntando por capacidades y ámbito en `EventAccess`, no por el nombre del rol. Un evento nuevo recibe el ámbito asignado a su autor; un objeto sin ámbito no se abre por mera autoría. `map_meta_cap` cierra los accesos por ID, editor normal y REST. Los términos solicitados al cambiar un evento han de pertenecer al ámbito efectivo, tanto en el taller como antes de guardar por REST o el editor clásico. Los listados y selectores usan el mismo conjunto efectivo; una página satélite hereda el ámbito de la raíz mediante `root_id()`.

El correo y la imagen del término se guardan como term meta nativa, sin dependencia de ACF. El correo se sanea como email y puede vaciarse; la imagen se guarda como ID de adjunto de imagen, nunca URL. Sus claves son nuevas en este aplicativo y se documentan en el código; no se migra ningún term meta previo de `evt_area` porque no existe tal contrato en el repositorio. El inventario y la conversión de datos de una instalación externa son un trabajo separado antes de su despliegue.

## Consecuencias

Un Editor situado en un ámbito puede editar contenidos asociados a ese nodo o sus descendientes, nunca por una rama hermana o superior. Un Editor sin ámbito no ve ni edita contenidos acotados. Los perfiles `evt_organiser` existentes conservan sus capacidades, sujetos a la misma cardinalidad de perfil. La auditoría de perfiles históricos con varios ámbitos y capacidades añadidas manualmente es necesaria antes del despliegue real. Esta ADR complementa la ADR-0006; prevalece solo en la elección del actor Editor y en las reglas posteriores de jerarquía, cardinalidad y guardado.

## Adenda — 2026-09-20

En REST, una persona editora acotada crea primero un borrador y luego lo publica: la transición directa a `publish`, `future` o `private` se rechaza porque `transition_post_status` ocurre antes de que WordPress asigne los términos REST. La demo sigue el mismo orden para no disparar hooks públicos con un evento huérfano. Al crear sin `evt_area`, REST usa el único ámbito directo del perfil; al actualizar sin ese parámetro, conserva los términos.

La asignación compartida se resuelve en `EventAccess::resolve_area_assignment()`: un Editor solo cambia términos de su subárbol, conserva los ajenos y puede retirar todos los propios si queda otro organizador. En tal caso pierde acceso al guardar y el taller lo comunica. Si el conjunto final queda vacío, se rechaza. Los organizadores ajenos existentes son visibles en solo lectura en el taller y en wp-admin.

El perfil interpreta de forma centralizada meta escalar, cadena separada por comas o lista. Un único ID válido resuelve el ámbito y se normaliza a una lista unitaria al guardar el perfil; varios válidos son ambiguos, y la mezcla de un ID válido con otro inexistente es inválida. Ambos estados bloquean acceso y su meta se preserva literalmente mientras administración no elija explícitamente un único ámbito o «Sin ámbito». Como aún no hay cuentas reales, no hace falta una migración masiva; Ajustes y diagnóstico enumera perfiles problemáticos antes del despliegue.
