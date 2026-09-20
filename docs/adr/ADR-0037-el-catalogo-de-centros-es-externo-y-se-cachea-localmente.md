---
id: ADR-0037
title: "El catálogo de centros educativos es un dato maestro externo y se cachea localmente"
status: Aceptada
date: 2026-09-20
related:
  issues: []
  prs: []
  sdds: []
  adrs: [ADR-0030, ADR-0031, ADR-0032]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Antigravity"
  model: "inherit"
---

# ADR-0037: El catálogo de centros educativos es externo y se cachea localmente

## Estado

Aceptada (2026-09-20).

## Contexto

El formulario de inscripción de eventos requiere que la persona participante elija
su centro educativo de procedencia ([ADR-0031](ADR-0031-el-formulario-de-inscripcion-es-nucleo-fijo-mas-preguntas.md)).
El centro **nunca se teclea**: debe seleccionarse de una lista canónica para evitar
que el mismo centro se registre con múltiples grafías y erratas.

Existe un catálogo maestro externo versionado que consolida, limpia y publica
el censo oficial de centros educativos mediante dos artefactos estáticos:
- `manifest.json`: metadatos de versión de esquema (`schema_version = 1`), fecha de
  actualización, recuento de registros y suma criptográfica SHA-256 del fichero de centros.
- `centros.min.json`: array JSON con los registros normalizados (`code`, `name`, `island`,
  `municipality`, `type`, `active`).

Históricamente el catálogo de centros se gestionaba de forma acoplada al gestor de
formularios anterior. Se planteó inicialmente replicar los ~1.500 centros dentro de
WordPress como un Custom Post Type (`ate_centre`) o mediante una tabla SQL propia.
Sin embargo, esa aproximación presenta inconvenientes importantes:
- Los centros educativos **no son contenido editorial de WordPress**: nadie los edita,
  comenta, traduce ni publica manualmente en este sitio.
- Replicar ~1.500 posts con múltiples filas de `wp_postmeta` por centro incrementa
  innecesariamente el volumen de la base de datos sin aportar valor relacional ni editorial.
- El aplicativo de eventos no es propietario de los centros; es un **consumidor** de un
  dato maestro de referencia.

## Decisión

1. **WordPress no replica el catálogo como CPT, taxonomía ni tabla SQL**:
   - No se crean posts, términos ni tablas para almacenar los centros.
   - El catálogo maestro reside fuera de WordPress y es consumido por los aplicativos.

2. **Copia local cacheada en opción de WordPress (`autoload = false`)**:
   - Para no realizar peticiones HTTP remotas durante el renderizado de los formularios de
     inscripción, WordPress mantiene una copia local del catálogo en la opción
     `evt_centres_catalogue` con `autoload = false`.
   - El catálogo se almacena como un array asociativo indexado por el código oficial de 8 dígitos:
     `[ '38017731' => [ 'code' => ..., 'name' => ..., 'island' => ..., 'municipality' => ..., 'type' => ..., 'active' => bool ] ]`.
   - Almacena todos los centros (tanto activos como inactivos) para permitir resolver códigos
     históricos en caso necesario.

3. **Sincronización segura mediante `manifest.json` y verificación SHA-256**:
   - La sincronización descarga primero `manifest.json`.
   - Si el hash SHA-256 remoto coincide con el local, no se descarga `centros.min.json`.
   - Si el hash ha cambiado, se descarga `centros.min.json`, se comprueba que su SHA-256
     calculado coincida exactamente con el declarado en el manifest y se valida la integridad
     del array y de cada registro (código de 8 dígitos, nombre no vacío, formato booleano en `active`,
     ausencia de códigos duplicados).
   - **Reemplazo atómico**: el catálogo local existente solo se sustituye si la nueva descarga ha
     superado todas las validaciones.
   - **Resiliencia ante fallos**: si la fuente remota no responde, devuelve HTTP distinto de 200
     o el JSON es inválido, el aplicativo conserva intacto el último catálogo local válido y
     registra el error en el estado de diagnóstico (`evt_centres_catalogue_status`).
   - Sincronización periódica automática mediante WP-Cron diario, y acción de sincronización manual
     protegida por nonce y capacidad administrativa en **Ajustes → Centros educativos**.

4. **Persistencia en la inscripción: código oficial + snapshot textual**:
   - El desplegable del formulario de inscripción muestra únicamente los centros **activos** (`active = true`),
     con `value` = código oficial de 8 dígitos y etiqueta visible = denominación oficial.
   - La validación en servidor comprueba que el código recibido existe en el catálogo local y
     está activo.
   - Al guardar la inscripción se almacenan dos datos:
     - `evt_reg_centre_code`: el código oficial (identidad inmutable).
     - `evt_reg_centre`: la denominación textual en el momento exacto de la inscripción (snapshot histórico).
   - Si un centro cambia de nombre en el futuro, o pasa a estar inactivo, las inscripciones pasadas
     conservan su fotografía histórica y no se alteran ni invalidan.
   - Las inscripciones antiguas que carecen de `evt_reg_centre_code` se siguen visualizando
     a partir de `evt_reg_centre` sin forzar una migración heurística por nombre.

## Consecuencias

### Positivas
- **Cero peticiones remotas** durante la navegación o envío de formularios de inscripción.
- **Base de datos limpia**: se evita insertar miles de filas en `wp_posts` y `wp_postmeta`.
- **Identidad formal y estable**: se utiliza el código oficial de 8 dígitos como identidad y se
  conserva la denominación como snapshot histórico.
- **Desacoplamiento total**: el aplicativo no depende de la disponibilidad inmediata de la fuente externa
  gracias a la caché local con reemplazo atómico.
- **Integración transparente**: el filtro `evt_centres` sigue devolviendo `[ codigo => denominacion ]`,
  permitiendo que otros snippets o proveedores sigan contestando si fuera necesario.

### Negativas
- La actualización del catálogo depende de la ejecución de WP-Cron o de la acción manual en ajustes.
- El tamaño del array en la opción `evt_centres_catalogue` es de ~150-200 KB en disco, pero al tener
  `autoload = false` solo se carga en memoria cuando se solicita el selector de centros o se valida una inscripción.
