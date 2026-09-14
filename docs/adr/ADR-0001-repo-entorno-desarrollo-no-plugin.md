---
id: ADR-0001
title: "El repositorio es un entorno de desarrollo, no un plugin"
status: Aceptada
date: 2026-09-12
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0002, ADR-0003]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0001: El repositorio es un entorno de desarrollo, no un plugin

## Estado

Aceptada

## Contexto

El aplicativo de eventos —encuentros, jornadas y congresos— vive en un subsitio
de un multisitio ajeno: WordPress 6.9.5 con el tema la versión que tenga el tema, gestionado
entero desde el panel de administración. Todo lo que hoy hace de aplicativo
está dentro de ese panel:

- el formulario del sistema que se sustituye, con **146 campos** —119 propios
  más 27 repartidos en cuatro subformularios embebidos, recuento sobre el
  volcado que hay en `.local/`—;
- **38 fragmentos de código** en Code Snippets 3.9.6, 13 de ellos activos;
- **45 vistas** del gestor de formularios, una de las cuales genera el
  contenido de las páginas.

El detalle de esa foto —el inventario del sistema anterior, con sus
identificadores— es material de investigación y vive en `.local/`, que no se
versiona ([ADR-0030](ADR-0030-el-repositorio-se-publica-sin-nada-de-nadie.md)).

No existe despliegue de ficheros a ese WordPress. El canal real de puesta en
producción es pegar código en el formulario de Code Snippets del escritorio. La
herramienta que ya usa el equipo automatiza ese pegado por HTTP, con una sesión
autenticada del panel, pero no cambia la naturaleza del canal: sigue siendo el
admin, y sigue sin subir ficheros al servidor.

Ese material —el código del aplicativo y, mientras siga vivo, la definición
del formulario— necesita versionado, revisión, tests y un entorno local
reproducible. Hoy no tiene nada de eso.

## Problema

¿Cómo estructurar el repositorio para versionar, revisar y probar el código
de un aplicativo cuyo destino es **un subsitio de un multisitio ajeno**, sin
acceso al sistema de ficheros del servidor y sin más canal de despliegue que
el panel de administración?

## Factores de decisión

- Producción admite Code Snippets y el gestor de formularios desde el admin, y
  nada más. Esa restricción no está en nuestra mano cambiarla.
- El destino no es un WordPress propio: es **un subsitio entre otros muchos**,
  de proyectos que no tienen nada que ver con este. Cualquier cosa que se
  despliegue por fichero afecta a la red entera, no solo al sitio de eventos.
- Trazabilidad: el código debe poder revisarse en Git, con diffs, PR y CI.
- Reproducibilidad: cualquier persona debe levantar en local un entorno
  equivalente con un comando.
- No duplicar mecanismos que los plugins de producción ya proporcionan.

## Alternativas consideradas

### Opción 1: empaquetar el aplicativo como plugin propio

Convertir el repositorio en un plugin de WordPress instalable.

- Pros: estructura estándar, activación limpia, autoload de Composer,
  distribuible.
- Contras: **producción no instala plugins propios**. En un multisitio la
  instalación es además competencia de la administración de red, no del
  subsitio, así que ni siquiera es una gestión que el equipo pueda pedir en
  su propio ámbito. Duplicaría lo que Code Snippets ya hace y obligaría a
  montar un flujo de publicación que hoy no existe. Descartada.

### Opción 2: mu-plugin permanente

Desplegar el aplicativo como mu-plugin en `wp-content/mu-plugins`.

- Pros: siempre activo, sin pasos de activación, sin interfaz que tocar.
- Contras: dos, y cada uno basta por sí solo. Primero, requiere acceso al
  sistema de ficheros del servidor, que no existe. Segundo, y propio de este
  proyecto: en un multisitio `wp-content/mu-plugins` **es de la red**. Un
  mu-plugin de eventos se cargaría en todos los subsitios, registrando
  `evt_event` y las tres taxonomías en sitios que no tienen nada que ver con
  esto. Descartada.

### Opción 3: repositorio-entorno con `src/Evt/` y `snippets/` como fuente de verdad

El repositorio no contiene un plugin: contiene el código del aplicativo en
`src/Evt/`, los snippets sueltos en `snippets/*.php`, un entorno local
(wp-env / WordPress Playground) con los mismos plugins que producción y
scripts idempotentes que sincronizan repositorio ↔ WordPress. El artefacto de
despliegue es **un único snippet** generado por `build/pack-snippet.php`.

- Pros: encaja con el modelo real de producción; todo es versionable,
  revisable y testeable; el entorno local se levanta con `make up`.
- Contras: exige disciplina de sincronización en los dos sentidos, y el
  «despliegue» sigue siendo un copiar y pegar con supervisión humana.

## Decisión

Adoptamos la opción 3. Este repositorio **es un entorno de desarrollo, no un
plugin**:

- `src/Evt/` es la fuente de verdad del aplicativo y `snippets/*.php` la de
  los snippets sueltos (hoy `roles-and-profiles.php`, con
  `Snippet Name: EVT — Roles y perfiles` y `Priority: 5`).
- `build/pack-snippet.php` genera `snippets/evt-eventos-app.bundle.php`, un
  único fichero de **1.363 líneas** con la cabecera
  `Snippet Name: EVT — Aplicativo de eventos (CPT)` y `Priority: 15`
  (la cabecera que escribe `build/pack-snippet.php`). Ese fichero es lo que se
  pega en Code Snippets; no se edita a mano.
- wp-env monta el repositorio completo en `wp-content/evt-dev`
  (las `mappings` de `.wp-env.json`), en los puertos 8798 y 8799, con Code
  Snippets, Members, WPFront User Role Editor y SQL Buddy, **sin el gestor de
  formularios** (los `plugins` de `.wp-env.json`).
- La sincronización repositorio → Code Snippets se hace con `wp eval-file` y
  la API PHP del plugin; su justificación es
  [ADR-0002](ADR-0002-sincronizacion-snippets-eval-file.md).
- No hay cabecera de plugin en ningún fichero del repositorio
  (`grep -rn "Plugin Name:" --include=*.php` no devuelve nada), ni
  `readme.txt`, ni `register_activation_hook()`, ni `plugins_url()`. La
  prohibición está escrita en `AGENTS.md`, «Qué es este proyecto (y qué NO es)».

## Consecuencias

### Positivas

- El código del aplicativo es revisable en PR, pasa PHPCS y PHPMD y tiene
  tests unitarios en CI, cosa que hoy no ocurre con nada de lo que hay en
  producción: ni los 38 fragmentos de código, ni los 146 campos del
  formulario, ni los 42 KB de la vista que pinta las páginas tienen control de
  versiones.
- Entorno local reproducible con los mismos plugins de gestión de código y
  roles que producción.
- Un solo artefacto de despliegue, con su versión en la cabecera, en lugar de
  los 38 fragmentos sueltos de hoy.
- La demo pública en WordPress Playground se genera del mismo material
  versionado (`blueprint.json`).

### Negativas

- Doble paso permanente: lo que se cambia en el repositorio hay que
  sincronizarlo a WordPress, y lo que alguien cambie en el admin hay que
  volcarlo al repositorio. Riesgo real de divergencia; se mitiga con la
  política de fuente de verdad de [AGENTS.md](../../AGENTS.md).
- El despliegue a producción es pegar 1.363 líneas en un `textarea` del
  escritorio. No hay firma, ni checksum, ni forma automática de comprobar que
  lo pegado es exactamente el bundle de un commit concreto: la comprobación es
  humana.
- El entorno local es un sitio único y el destino es un subsitio de un
  multisitio. Todo lo que construya un nombre de tabla o invoque `wp eval-file`
  sin `--url=` funciona en local y puede no funcionar allí
  (`AGENTS.md`, «Reglas duras»; consecuencias detalladas en
  [ADR-0002](ADR-0002-sincronizacion-snippets-eval-file.md)).
- Estructura de plugin sin las ventajas de un plugin: no hay autoload en
  producción, así que el orden de carga se mantiene a mano en
  `src/Evt/load-order.php` y `declare(strict_types=1)` queda prohibido porque
  es fatal al concatenar (`AGENTS.md`, sección de estilo).

### Neutras

- El repositorio no publica ningún artefacto instalable. Si algún día el
  multisitio admitiera desplegar ficheros, el mismo `src/Evt/` se empaquetaría
  como plugin sin tocar el dominio: cambiaría el empaquetador, no el código.
- El gestor de formularios no se instala en el entorno local: el aplicativo
  nuevo no depende de él. Lo que sigue en el sistema anterior durante la fase 1
  —las inscripciones— se decide en la ADR-0007.
