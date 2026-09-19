---
id: ADR-0035
title: "Sincronización de snippets por contenido"
status: Aceptada
date: 2026-09-19
related:
  issues: []
  prs: []
  sdds: []
  adrs: [ADR-0002, ADR-0015]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-sonnet-5"
---

# ADR-0035: Sincronización de snippets por contenido

## Estado

Aceptada

## Contexto

Según [ADR-0002](ADR-0002-sincronizacion-snippets-eval-file.md), el
aplicativo se despliega como snippets de Code Snippets y
`scripts/lib/snippet-sync.php` los sincroniza desde `snippets/*.php` con la
API pública del plugin (`save_snippet()`, `activate_snippet()`,
`get_snippets()`), ejecutada por `scripts/sync-snippets.php` bajo `wp
eval-file`. Hasta ahora, `evt_sync_snippets_from_dir()` construye un objeto
`Snippet` para cada fichero y llama a `Code_Snippets\save_snippet()` **siempre**
que el snippet ya existe, sin comparar su contenido con lo que hay en la
tabla, y a continuación llama a `activate_snippet()` sobre el resultado.

`Code_Snippets\save_snippet()` (`snippet-ops.php` del plugin) no es una
operación neutra: en cada llamada actualiza `modified`
(`$snippet->update_modified()`), reevalúa el código con
`test_snippet_code()` si el snippet está activo —lo que lo **ejecuta**— y
limpia la caché de snippets del sitio (`clean_snippets_cache()`). Guardar un
snippet que no ha cambiado deja, por tanto, una revisión y una marca de
tiempo artificiales, y repite una ejecución del código que no tenía motivo
para repetirse.

El bundle principal ya elimina los comentarios PHP correctamente
(`build/pack-snippet.php`, con `token_get_all()`, cubierto por
`tests/unit/test-bundle.php`); esta decisión no toca ese mecanismo.

Las librerías de terceros —Bootstrap 5, Bootstrap Icons, SweetAlert2— siguen
una arquitectura distinta y ya resuelta: se cargan desde jsDelivr con versión
exacta y *Subresource Integrity*, y en desarrollo y en los tests se sirven de
`node_modules`
([ADR-0015](ADR-0015-librerias-de-terceros-desde-cdn-con-sri.md)). No entran
en el bundle PHP ni en la tabla de Code Snippets como código propio, y su
identidad de versión ya es el trío `package.json` + URL versionada + SRI,
comprobado en `tests/unit/test-bootstrap5.php` y
`tests/unit/test-sweetalert.php`.

## Problema

¿Cómo determinar si un snippet propio necesita realmente actualizarse, sin
usar la versión global del aplicativo EVT como sustituto del contenido, y sin
volver a guardar —ni por tanto revalidar, reejecutar ni marcar como
modificado— un snippet cuyo contenido no ha cambiado?

## Factores de decisión

- Guardar un snippet activo lo reejecuta: un guardado que no representa un
  cambio real es una ejecución de código sin motivo.
- `modified` y la revisión del snippet deben significar «esto cambió», no
  «esto se sincronizó».
- La comparación tiene que ser determinista y fácil de probar sin depender de
  un WordPress vivo para la parte de normalización.
- No se puede confundir la versión del aplicativo (`CHANGELOG.md`, que sigue
  fijando el `@version` del bundle) con si un snippet concreto cambió: son
  preguntas distintas.
- La normalización de código para comparar tiene que ser la misma que la que
  ya se aplica antes de guardar (`evt_strip_php_tags()`), o la comparación
  detecta diferencias que no son reales.
- No se introduce una segunda forma de fijar versión de librerías de
  terceros: la de la [ADR-0015](ADR-0015-librerias-de-terceros-desde-cdn-con-sri.md)
  ya resuelve ese caso y no comparte nada con este.

## Alternativas consideradas

### Opción 1: guardar siempre todos los snippets

Es el comportamiento actual.

- Pros: ninguno más allá de la sencillez de no comparar nada.
- Contras: reejecuta código, revalida y actualiza `modified` en cada
  sincronización, también cuando nada cambió. Descartada.

### Opción 2: comparar únicamente la versión del aplicativo EVT

Sincronizar solo si el `@version` del `CHANGELOG.md` cambió desde la última
sincronización.

- Pros: una sola comparación, sin tocar el contenido de cada snippet.
- Contras: la versión EVT es del **aplicativo**, no de cada snippet
  independiente; `snippets/bootstrap5.php`, `snippets/sweetalert.php` y
  `snippets/roles-and-profiles.php` no llevan versión propia y cambian sin
  que cambie el `CHANGELOG`. Y al revés: subir la versión EVT por un cambio
  en un solo fichero forzaría a revalidar y reejecutar los demás sin que
  hayan cambiado. Descartada explícitamente por el problema que plantea esta
  ADR.

### Opción 3: comparar únicamente el código

Comparar solo el cuerpo del snippet, ignorando `desc`, `scope`, `priority` y
`tags`.

- Pros: cubre el caso más común.
- Contras: un cambio de ámbito o de prioridad —que cambia **cuándo y dónde**
  se ejecuta el snippet— pasaría desapercibido y el snippet desplegado se
  quedaría con el ámbito o la prioridad viejos. Descartada: el estado
  gestionado por el repositorio es más que el código.

### Opción 4: comparar todo el estado gestionado directamente

Comparar código, `desc`, `scope`, `priority` y `tags` campo a campo, sin
fingerprint.

- Pros: no necesita una función de hash; el resultado es el mismo que la
  opción elegida.
- Contras: cada punto de comparación (la sincronización, y cualquier test que
  quiera comprobar «esto no cambió») repite la misma normalización a mano, y
  es fácil que dos comparaciones diverjan en un detalle —por ejemplo, que una
  compare el código ya normalizado y la otra no— sin que ningún test lo note.
  Descartada por no dejar un único sitio donde vive la normalización.

### Opción 5: fingerprint SHA-256 determinista del estado gestionado — ELEGIDA

Construir un array con claves fijas —`code`, `desc`, `scope`, `priority`,
`tags`— a partir de los mismos valores ya normalizados, codificarlo con
`wp_json_encode()` de forma determinista y calcular su SHA-256. Comparar
fingerprints con `hash_equals()`.

- Pros: una sola función de normalización (`evt_snippet_managed_state()`) y
  una sola de hash (`evt_snippet_fingerprint()`), reutilizables desde la
  sincronización y desde los tests; el fingerprint es una cadena corta y
  fácil de volcar en un mensaje de diagnóstico si hace falta; no depende de
  comparar objetos `Snippet` completos, que llevan campos que el repositorio
  no gestiona (`id`, `modified`, `revision`, `locked`...).
- Contras: una capa más de indirección sobre la opción 4; hay que mantener la
  lista de campos gestionados si algún día se gestiona alguno más.

## Decisión

Se adopta la opción 5.

- `evt_snippet_managed_state( string $code, string $desc, string $scope, int $priority, array $tags ): array`
  (`scripts/lib/snippet-sync.php`) construye el estado gestionado con claves
  fijas. El código pasa por `evt_strip_php_tags()` —la misma normalización
  que ya se aplicaba antes de guardar, reutilizada y no duplicada— y las
  etiquetas se ordenan, así que ni la etiqueta `<?php`, ni el cierre `?>`, ni
  un salto de línea final, ni el orden de las etiquetas cambian el estado
  gestionado.
- `evt_snippet_fingerprint( array $managed_state ): string` codifica ese
  array con `wp_json_encode( $managed_state, JSON_UNESCAPED_SLASHES |
  JSON_UNESCAPED_UNICODE )` y calcula su SHA-256. Dos estados iguales
  producen siempre el mismo fingerprint; cualquier campo gestionado que
  cambie produce uno distinto.
- `evt_sync_snippets_from_dir()` calcula el fingerprint del snippet tal y
  como quedaría (el fichero) y el del snippet tal y como está (la fila
  existente, si la hay) y los compara con `hash_equals()`:
  - **No existe** → se crea y se activa. Resultado `created`.
  - **Existe y el fingerprint difiere** → se guarda (`save_snippet()`) y se
    asegura activo. Resultado `updated`.
  - **Existe, el fingerprint coincide y está activo** → no se llama a
    `save_snippet()` ni a `activate_snippet()`. Resultado `unchanged`.
  - **Existe, el fingerprint coincide y está inactivo** → no se guarda; se
    llama solo a `Code_Snippets\activate_snippet()` (con la misma
    degradación a `evt_force_activate_snippet()` que ya existía para el
    validador del plugin). Resultado `unchanged`, con `reactivated => true`
    en el array de resultado.
- La versión del aplicativo EVT (`CHANGELOG.md`, `@version` del bundle) sigue
  existiendo y sigue siendo la fuente de verdad de **esa** versión —el bundle
  principal no cambia su política—, pero no interviene en absoluto en si un
  snippet independiente se sincroniza: eso lo decide únicamente el
  fingerprint de su propio estado gestionado.
- `scripts/sync-snippets.php` distingue las cuatro salidas en su resumen y en
  la línea de cada snippet (`sin cambios` / `sin cambios, reactivado` /
  `actualizado` / `creado`), y cuenta un `sin cambios` aparte de los
  `actualizado(s)`.
- Las librerías de terceros no entran en este mecanismo. Siguen la
  [ADR-0015](ADR-0015-librerias-de-terceros-desde-cdn-con-sri.md) sin
  cambios: versión exacta en `package.json`, URL versionada de jsDelivr y
  SRI. No se crea un bundle PHP de Bootstrap ni de SweetAlert2, ni un
  `Bundle Hash:` ni un segundo SHA-256 que duplique el SRI. El fingerprint de
  esta ADR identifica **código propio versionado en `snippets/*.php`**; el
  SRI identifica **un fichero de un tercero servido desde un CDN**. Son dos
  preguntas distintas —«¿cambió lo que escribimos?» frente a «¿es este el
  fichero que dice ser?»— y cada una tiene ya su respuesta.

## Consecuencias

### Positivas

- Menos escrituras en la tabla de snippets: una sincronización sin cambios
  reales no toca la base de datos salvo, como mucho, para reactivar.
- Menos revisiones y marcas de tiempo artificiales: `modified` vuelve a
  significar que el contenido cambió.
- Menos invalidaciones de caché de Code Snippets por sincronizaciones que no
  tenían nada que sincronizar.
- Menos revalidaciones y reejecuciones de código sin motivo: un snippet
  activo e inalterado no se vuelve a ejecutar por el mero hecho de
  sincronizar.
- `updated` vuelve a significar «esto cambió de verdad», tanto en la salida
  de `make sync-snippets` como en los resultados que consume cualquier otro
  script.
- Sincronizaciones más fáciles de auditar: el resumen distingue creado,
  actualizado, sin cambios y error, en vez de tratar «existe» y «cambió»
  como lo mismo.

### Negativas

- Hay una capa más de normalización y de fingerprint que mantener
  (`evt_snippet_managed_state()`, `evt_snippet_fingerprint()`).
- Hay que recordar qué campos forman el estado gestionado si algún día se
  gestiona alguno más (por ejemplo, si `snippets/*.php` empezara a declarar
  sus propias etiquetas en la cabecera): un campo que se use y no entre en el
  estado gestionado no se detectaría como cambio.
- Requiere tests específicos de la sincronización
  (`tests/unit/test-snippet-sync.php`), que no existían: hasta esta ADR
  ningún test de la suite ejercitaba la API de Code Snippets, y activarla
  para PHPUnit (el wp-env de tests instala el plugin pero no lo activa) fue
  parte del trabajo, acotado a esa única clase de test.

### Neutras

- No cambia la arquitectura de Code Snippets como destino de despliegue
  ([ADR-0002](ADR-0002-sincronizacion-snippets-eval-file.md)).
- No convierte el repositorio en un plugin de producción.
- No modifica la política de librerías de terceros
  ([ADR-0015](ADR-0015-librerias-de-terceros-desde-cdn-con-sri.md)): siguen
  con versión exacta y SRI, sin fingerprint propio.
- No despliega nada en producción por sí misma: sigue siendo
  `@erseco/code-snippets-client` (`npm run snippets`) quien empuja al sitio
  de destino, y ese cliente no forma parte de este cambio. Lo que mejora es
  la parte que sí controla este repositorio —la sincronización local con
  `wp eval-file`, usada en `make provision` y en el aprovisionamiento de
  Playground— detectando `unchanged` antes de escribir nada; no se afirma
  nada sobre si el cliente remoto evita ya escrituras idénticas, porque no se
  ha comprobado que lo haga.
- No cambia ninguna versión de dependencia.

## Adenda — 2026-09-19

Revisión de código sobre el PR de esta ADR encontró tres imprecisiones, ya
corregidas en `scripts/lib/snippet-sync.php` y `scripts/sync-snippets.php`:

- **«Reejecuta» era la descripción correcta del ahorro, pero imprecisa sobre
  el mecanismo.** `Code_Snippets\save_snippet()` no ejecuta el código por sí
  mismo: llama a `test_snippet_code()` —que valida con `Validator` y, si el
  snippet está activo y no pasa `code_error`, sí llega a ejecutarlo vía
  `execute_snippet()`— solo cuando `$snippet->active` es verdadero en el
  momento de guardar. El sincronizador construía siempre un objeto `Snippet`
  nuevo para el camino `updated`, y un objeto nuevo tiene `active = false`
  por defecto (valor de `Snippet::$default_values`), así que esa ejecución
  **no llegaba a producirse** en la ruta `updated` tal y como estaba escrito
  el sincronizador — el ahorro real de esa ruta ya era involuntario. La
  redacción correcta de lo que hace guardar un snippet activo: reescribe la
  fila, actualiza `modified`, revalida el código (lo que puede desactivar el
  snippet si `code_error`), incrementa la revisión si ya era mayor que 1 e
  invalida la caché de snippets del sitio.
- **`reactivated => true` no implicaba reactivación real.** Se fijaba así
  incondicionalmente en la rama «existe, coincide el fingerprint, está
  inactivo», aunque `evt_activate_snippet_with_fallback()` hubiera fallado.
  Podía imprimirse «sin cambios, reactivado» junto con «ERROR al activar»
  para el mismo snippet. Ahora `reactivated` es
  `$activation['active']`: solo es `true` cuando la reactivación tuvo éxito.
- **La ruta `updated` reconstruía un `Snippet` desde cero**, con
  `name`/`desc`/`code`/`tags`/`scope`/`priority` del fichero más el `id`
  existente, y dejaba el resto de campos —`active`, `locked`, `condition_id`,
  `revision`, `cloud_id`— en los valores por defecto de la clase. Como
  `save_snippet()` escribe esos campos sin más comprobación, actualizar el
  contenido de un snippet podía desactivarlo, desbloquearlo o resetear su
  `condition_id`/`revision`/`cloud_id` de fábrica. Corregido aplicando solo
  los campos gestionados (`set_fields()`) sobre el objeto `Snippet` ya
  cargado de la tabla, en vez de construir uno nuevo. Cubierto por
  `test_updating_managed_content_preserves_unmanaged_fields()` en
  `tests/unit/test-snippet-sync.php`.

Ningún cambio de estos afecta a la Decisión (opción 5, fingerprint SHA-256):
sigue siendo el fingerprint quien decide `updated` frente a `unchanged`. Lo
que cambia es únicamente cómo se aplica un `updated` a la fila existente.
