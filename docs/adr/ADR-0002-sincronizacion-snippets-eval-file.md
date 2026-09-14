---
id: ADR-0002
title: "Sincronización de snippets con wp eval-file y la API de Code Snippets"
status: Aceptada
date: 2026-09-12
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0001]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0002: Sincronización de snippets con `wp eval-file` y la API de Code Snippets

## Estado

Aceptada

## Contexto

Según [ADR-0001](ADR-0001-repo-entorno-desarrollo-no-plugin.md), el código
vive en `src/Evt/` y en `snippets/*.php`, pero Code Snippets lo almacena y lo
ejecuta desde su propia tabla en la base de datos. Hace falta un mecanismo que
lleve los ficheros del repositorio a esa tabla de forma reproducible, tanto en
wp-env como en WordPress Playground.

El destino lleva Code Snippets en su **versión gratuita**, que **no ofrece
comandos WP-CLI** para crear o actualizar snippets, pero sí una API PHP
pública en el espacio de nombres `\Code_Snippets`: `get_snippets()`,
`save_snippet()`, `activate_snippet()` y una clase modelo `Snippet`.

Hay dos particularidades que condicionan la solución y que no estaban en el
proyecto de referencia:

1. **El destino puede ser un subsitio de un multisitio.** Ahí `$wpdb->prefix` ya es
   `wp_<N>_`, así que la tabla del sitio es `wp_<N>_snippets`; los snippets
   activados en red viven aparte, en `wp_ms_snippets`. **No está verificado**
   si Code Snippets estaría activado en red o por sitio en una red de destino
   (queda anotado como pendiente en el material de investigación de `.local/`).
2. **Ya existe un cliente para el despliegue**: `@erseco/code-snippets-client`,
   un paquete de npm que habla por HTTP con el escritorio de WordPress —con
   contraseña de aplicación o con el inicio de sesión único del sitio—, lista,
   descarga y empuja snippets, y trata el subsitio como sitio y no como red.
   Entra en `package.json` como cualquier otra dependencia de desarrollo.

## Problema

¿Cómo sincronizar `snippets/*.php` con la tabla de Code Snippets de forma
idempotente y automatizable, sin depender de funcionalidad de pago, sin SQL
directo como camino principal, y funcionando igual bajo `wp eval-file` y bajo
Playground?

## Factores de decisión

- No hay comandos WP-CLI de Code Snippets en la versión gratuita 3.9.6.
- Idempotencia: reejecutar la sincronización no puede duplicar snippets.
- El mismo código debe correr bajo `wp eval-file` (Docker) y bajo
  `runPHP`/`require` (Playground), así que no puede depender de `WP_CLI`.
- Ciclo de iteración rápido: editar `src/Evt/`, empaquetar, ver el resultado.
- Evitar SQL directo contra tablas de un plugin de terceros.
- El aplicativo se instala **en el subsitio**, no en la red: lo que se
  sincroniza tiene que acabar en la tabla del sitio.

## Alternativas consideradas

### Opción 1: comandos WP-CLI de Code Snippets

- Contras: no existen en la versión gratuita, que es la de producción
  (3.9.6). Descartada.

### Opción 2: SQL directo contra la tabla del plugin

`INSERT` / `UPDATE` sobre `wp_<N>_snippets`.

- Pros: sin dependencia de la API del plugin.
- Contras: acoplado al esquema interno, se salta la caché y la validación del
  plugin, y en un multisitio obliga a resolver a mano red frente a sitio.
  Descartada como camino principal.

### Opción 3: importar y exportar a mano desde el escritorio

- Contras: manual, no automatizable en el aprovisionamiento ni en CI, y no
  idempotente. Descartada para desarrollo. Sigue siendo, por
  [ADR-0001](ADR-0001-repo-entorno-desarrollo-no-plugin.md), el canal de
  producción.

### Opción 4: script PHP sobre la API pública del plugin, ejecutado con `wp eval-file`

Una librería propia, `scripts/lib/snippet-sync.php`, que usa
`\Code_Snippets\get_snippets()`, la clase modelo, `save_snippet()` y
`activate_snippet()`.

- Pros: usa el camino oficial del plugin, es idempotente y el mismo código
  sirve para Docker y Playground.
- Contras: depende de una API interna que se mueve entre versiones.

### Opción 5: usar también en desarrollo la herramienta de despliegue

Reutilizar ese cliente HTTP para empujar los snippets al entorno local.

- Pros: una sola herramienta para local y producción.
- Contras: pide credenciales del despliegue y habla por HTTP contra el
  escritorio, así que no corre dentro de wp-env ni de Playground y no sirve en
  CI —donde no hay ni sitio ni credenciales que dar—. Descartada para
  desarrollo, **conservada para el despliegue**, que es para lo que está hecha.

## Decisión

Adoptamos la opción 4 para desarrollo y CI, y mantenemos la opción 5 como
canal de producción.

- `scripts/lib/snippet-sync.php` define
  `evt_sync_snippets_from_dir( string $dir ): array`, que parsea la cabecera de cada
  `snippets/*.php` (`evt_parse_snippet_header()`), quita las etiquetas PHP
  (`evt_strip_php_tags()`), hace **match por nombre exacto** contra los
  snippets existentes y crea, actualiza y activa con la API del plugin.
- La disponibilidad del plugin se comprueba antes de nada
  (`evt_code_snippets_is_active()`), y el nombre de la clase modelo se
  resuelve probando `Code_Snippets\Model\Snippet` y luego
  `Code_Snippets\Snippet` (`evt_code_snippets_model_class()`), porque cambió de
  espacio de nombres en la
  versión 3.10: comprobar solo uno deja el entorno sin snippets con un aviso
  que parece un problema de configuración.
- `scripts/sync-snippets.php` es el envoltorio que ejecuta `wp eval-file`
  (`npx wp-env run cli wp eval-file wp-content/evt-dev/scripts/sync-snippets.php`)
  y lanza `RuntimeException` cuando algo falla, para que la provisión se
  detenga en lugar de seguir a ciegas.
- `scripts/snippet-check.php` reproduce la validación de guardado del propio
  plugin sobre cada snippet EVT: guardar un snippet activo hace que su código
  se evalúe dos veces en la misma petición, y sin la guarda de doble carga el
  snippet se desactiva en silencio o mata la petición.
- El bundle se sincroniza como un snippet más: `make bundle` lo regenera y
  `make sync-snippets` lo sube. Las prioridades importan y son deliberadas:
  `EVT — Roles y perfiles` con `Priority: 5` y
  `EVT — Aplicativo de eventos (CPT)` con `Priority: 15`, de modo que los
  roles existen antes de que el aplicativo les cuelgue capacidades.
- En el multisitio, cualquier invocación de `wp eval-file` necesita `--url=`
  para caer en el subsitio correcto (`AGENTS.md`, «Referencia de herramientas»). Los scripts no lo
  añaden por su cuenta: es responsabilidad de quien invoca.

## Consecuencias

### Positivas

- Sincronización idempotente y automatizable —provisión, CI, blueprints de
  Playground— sin SQL directo como camino principal.
- Un único código de sincronización para Makefile y Playground.
- El fallo se propaga: `scripts/sync-snippets.php` lanza `RuntimeException`
  si Code Snippets no está activo o si no hay nada que sincronizar, en vez de
  terminar con éxito sin haber hecho nada.
- `snippet-check.php` detecta antes de desplegar el fallo más caro de este
  modelo: el snippet que se desactiva solo al guardarse.

### Negativas

- El match por nombre exige no renombrar snippets a la ligera: renombrar crea
  uno nuevo y deja el viejo, que hay que borrar a mano en el escritorio.
- Dependencia de una API interna que ya ha cambiado una vez (la clase modelo
  en 3.10). Producción está en 3.9.6 y el entorno local instala la última
  estable, así que se desarrolla contra una versión y se despliega contra
  otra.
- Queda una vía de SQL directo: `evt_force_activate_snippet()`
  (`scripts/lib/snippet-sync.php`) escribe en `$wpdb->prefix . 'snippets'`
  cuando el validador del plugin rechaza un código que PHP sí acepta. Es
  desarrollo únicamente, pero es una excepción a «nada de SQL directo» y
  conviene que se vea.
- **La parte multisitio no está probada.** El entorno local es un sitio único
  y `.wp-env.json` no configura multisitio; no se ha verificado si Code
  Snippets está activado en red en el destino ni, por tanto, si la tabla del
  subsitio existe. La librería está escrita para el caso «activado por
  sitio»; si resultara estar activado en red, habrá que revisarla.
- Nada de esto valida el despliegue real: al destino llega por el cliente
  (`npm run snippets`), con sus credenciales y contra un WordPress vivo. La
  sincronización por `eval-file` solo garantiza el entorno local.

### Neutras

- Los snippets se guardan sin la etiqueta `<?php` inicial, tal y como espera
  Code Snippets; la librería la quita antes de guardar
  (`evt_strip_php_tags()`).
- El aviso de activación forzada se imprime en la salida de la provisión: el
  entorno queda usable y el motivo queda escrito, en lugar de callarse.
