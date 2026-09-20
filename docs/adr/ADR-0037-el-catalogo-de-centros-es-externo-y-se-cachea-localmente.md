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

## Adenda — 2026-09-20

Tras la revisión del diseño inicial se introducen las siguientes precisiones y simplificaciones:

1. **Código oficial de exactamente 8 dígitos**:
   - Se fija el contrato formal con la expresión regular `^\d{8}$` en todas las capas del aplicativo (`RegistrationInput::is_centre_code()`, `CentreCatalogueSync`).
   - Se rechaza cualquier código que no tenga exactamente 8 dígitos (7 dígitos, 9 dígitos, letras o caracteres especiales), sin normalización con ceros a la izquierda.
   - En el formulario de inscripción, lo que envía el navegador es el código oficial (`centre_code`); nunca se confía en un nombre enviado por el cliente ni se acepta la denominación como identidad de una inscripción nueva.

2. **HTTPS obligatorio y prevención de SSRF**:
   - Las fuentes remotas del catálogo (`manifest.json` y `centros.min.json`) exigen obligatoriamente el esquema `https://`.
   - Se validan de forma estricta antes de realizar cualquier petición HTTP remota con `is_valid_https_url()`, rechazando `http://`, esquemas no seguros o URLs relativas.
   - Se elimina cualquier campo de texto editable en el escritorio para introducir URLs arbitrarias, reduciendo la superficie de ataque SSRF y evitando desconfiguraciones administrativas. Las URLs se configuran mediante constantes de entorno (`EVT_CENTRES_MANIFEST_URL`, `EVT_CENTRES_CATALOGUE_URL`), opciones programáticas o filtros de WordPress (`evt_centres_manifest_url`, `evt_centres_catalogue_url`).

3. **Candado de sincronización atómico con token**:
   - Se sustituye el mecanismo de transients (`get_transient`/`set_transient`) por un candado atómico basado en `add_option('evt_centres_sync_lock', ...)` (análogo a `Registrations::lock()`).
   - Almacena un token único aleatorio y la marca temporal. La liberación (`release_lock()`) requiere verificar la titularidad del token con `hash_equals()`, evitando que un proceso borre accidentalmente el candado adquirido por otro.
   - Si un candado caduca tras 300 segundos (proceso muerto), el mecanismo lo retira y lo vuelve a reclamar de forma atómica.

4. **Integración en Ajustes y diagnóstico (`Evt\Admin\Settings`)**:
   - Se descarta la pantalla independiente `CentreSettings` bajo `manage_options`.
   - La información de diagnóstico del catálogo se integra en la pantalla existente del aplicativo (`Ajustes y diagnóstico de eventos`), respetando la capacidad propia del aplicativo (`EventAccess::CAP_MANAGE`).
   - Se expone el estado, recuentos de registros, fechas, SHA-256 y un botón de actualización manual con nonce y control de acceso.

5. **Eliminación de WP-CLI**:
   - El entorno de producción no dispone de WP-CLI. Dado que el aplicativo ya cuenta con WP-Cron diario y sincronización manual desde la interfaz de diagnóstico, se elimina por completo `CentreCli` (`wp evt centres sync`) para no mantener código innecesario en el bundle.

6. **Decisión consciente sobre el CSV de participantes**:
   - Se mantiene la incorporación de la columna `Código de centro` en la exportación CSV (`Participants::columns()`), situándola junto a `Centro`.
   - `Código de centro` contiene `evt_reg_centre_code` y `Centro` contiene el snapshot de `evt_reg_centre`.
   - En inscripciones históricas sin código, `Código de centro` permanece vacío (`""`) sin intentar rellenarlo retrospectivamente a partir del catálogo actual.

### Revisión final de validación, transporte y concurrencia

1. **Fail-closed real sin catálogo**:
   - `RegistrationInput::core()` no acepta inscripciones con centro si el catálogo está vacío o no se proporciona (`array()`), eliminando cualquier semántica de bypass con `null`.
   - El contrato del filtro `evt_centres` y de la validación exige un mapa asociativo estricto `array<string, string>` indexado por el código oficial de 8 dígitos (`key = código 8 dígitos, value = denominación`). Se descartan listas de cadenas que no cumplan este contrato.

2. **Transporte seguro con `wp_safe_remote_get()` y HTTPS**:
   - Tanto `manifest.json` como `centros.min.json` se descargan exclusivamente mediante `wp_safe_remote_get()`, previniendo redirecciones o resoluciones a direcciones de bucle local o redes privadas internas (mitigación SSRF).
   - Se mantiene complementariamente la validación sintáctica estricta de `https://` y host no vacío (`is_valid_https_url()`).

3. **Recuperación segura de candado caducado (compare-and-delete)**:
   - La recuperación de un candado expirado (>300 s) y su posterior liberación ejecutan un compare-and-delete atómico en base de datos (`DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s`).
   - Esto evita condiciones de carrera donde un proceso lento intente eliminar un candado antiguo y borre accidentalmente el candado nuevo recién adquirido por otro proceso contemporáneo.


