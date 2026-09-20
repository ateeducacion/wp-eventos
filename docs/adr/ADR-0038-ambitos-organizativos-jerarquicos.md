---
id: ADR-0038
title: "Ámbitos organizativos jerárquicos para la edición"
status: Propuesta
date: 2026-09-20
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0006, ADR-0012, ADR-0030]
supersedes: [ADR-0006]
superseded_by: []
ai_assistance:
  tool: "Codex"
  model: "GPT-6"
---

# ADR-0038: Ámbitos organizativos jerárquicos para la edición

## Contexto

`evt_area` ya es una taxonomía jerárquica y la meta homónima del usuario admite varios IDs de término (`src/Evt/Taxonomy/EventTaxonomies.php`, `src/Evt/Access/EventAccess.php`). La ADR-0006 separó área y rol, pero eligió `evt_organiser` como actor. El requisito nuevo pide al Editor nativo y acceso al nodo asignado y todos sus descendientes. La comparación directa en `EventAccess::can_open()` no lo concedía. `get_term_children()` permite expandir el árbol; `map_meta_cap` ya es la barrera por objeto de este aplicativo.

## Decisión

La interfaz y la documentación dirán **Ámbito organizativo**. Se conserva `evt_area` como taxonomía y meta persistentes: cambiar sus claves rompería relaciones, perfiles y clientes sin mejorar la autorización. La taxonomía sigue jerárquica porque un servicio debe cubrir sus áreas presentes y futuras. La meta del usuario sigue siendo una lista de IDs; se aceptan cadenas antiguas separadas por comas y se unen los subárboles, sin duplicados. Un término inexistente o un error al obtener descendientes no concede acceso. Sin ámbito, el resultado es vacío. Administración (`evt_manage_app` o `manage_options`, con `evt_edit_all_areas`) no está acotada.

El rol nativo `editor` recibe las mismas capacidades de CPT de trabajo que `evt_organiser`, además de `evt_edit_custom_css`. No recibe `manage_options`, `evt_manage_app`, `evt_edit_all_areas`, gestión de términos ni JavaScript a medida. `evt_organiser` queda como compatibilidad para perfiles existentes; no se borra ni se migra automáticamente porque una persona podría tener otros roles y una conversión sin inventario cambiaría permisos. El alta funcional nueva usa `editor`. Una retirada futura requerirá una migración única, auditada e idempotente, como la de `evt_coordinator` en `snippets/roles-and-profiles.php`.

Solo administración ve y guarda el selector de ámbito de un Editor. El perfil propio no autoriza autoasignación: el guardado exige `manage_options`, `edit_user` y nonce. La autorización operativa sigue preguntando por capacidades y ámbito en `EventAccess`, no por el nombre del rol. Un evento nuevo recibe el ámbito asignado a su autor; un objeto sin ámbito no se abre por mera autoría. `map_meta_cap` cierra los accesos por ID, editor normal y REST. Los términos solicitados al cambiar un evento han de pertenecer al ámbito efectivo, tanto en el taller como antes de guardar por REST o el editor clásico. Los listados y selectores usan el mismo conjunto efectivo; una página satélite hereda el ámbito de la raíz mediante `root_id()`.

El correo y la imagen del término se guardan como term meta nativa, sin dependencia de ACF. El correo se sanea como email y puede vaciarse; la imagen se guarda como ID de adjunto de imagen, nunca URL. Sus claves son nuevas en este aplicativo y se documentan en el código; no se migra ningún term meta previo de `evt_area` porque no existe tal contrato en el repositorio. El inventario y la conversión de datos de una instalación externa son un trabajo separado antes de su despliegue.

## Consecuencias

Un Editor situado en un servicio puede editar contenidos de ese servicio y sus descendientes, nunca de sus padres ni de ramas hermanas. Un Editor sin ámbito no ve ni edita contenidos acotados. Los perfiles `evt_organiser` existentes conservan sus capacidades pero pasan a la misma semántica jerárquica. La auditoría de términos asignados y capacidades añadidas manualmente sigue siendo necesaria antes del despliegue real. Esta ADR sustituye la elección de rol de la ADR-0006 sin reescribir su texto histórico.
