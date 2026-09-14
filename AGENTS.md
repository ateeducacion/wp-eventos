# AGENTS.md — Eventos: instrucciones para agentes

Este es el **fichero de instrucciones canónico** para todos los agentes de
código (GitHub Copilot, Claude Code, Gemini Code Assist, Codex, Aider y otros)
que trabajen en este repositorio. Los demás ficheros de agentes (`CLAUDE.md`)
apuntan aquí.

---

## Qué es este proyecto (y qué NO es)

**Eventos** es el repositorio de trabajo de un aplicativo que gestiona
encuentros, jornadas y congresos. En producción se activa con **Code
Snippets**, **Members** y **WPFront User Role Editor**; dónde, lo dice el
`.env` y **no el repositorio**
([ADR-0030](docs/adr/ADR-0030-el-repositorio-se-publica-sin-nada-de-nadie.md)).
El dominio son tres CPT —`evt_event`,
`evt_speaker`, `evt_activity`— y tres taxonomías —`evt_area`, `evt_type`,
`evt_course`—.

- **NO es un plugin de WordPress** instalable en producción. No crees fichero
  principal de plugin, ni `readme.txt`, ni `register_activation_hook()`, ni
  uses `plugin_dir_path()` / `plugin_dir_url()` / `plugins_url()`. Ese
  WordPress no admite despliegue de ficheros: solo pegar código en Code
  Snippets. Todo lo demás sale de ahí.
- Fuente de verdad:
  - `src/Evt/` → código del aplicativo (**editar aquí**);
  - `make bundle` → genera `snippets/evt-eventos-app.bundle.php`;
  - `snippets/*.php` → `make sync-snippets` los lleva al entorno local;
    `npm run snippets` (el cliente `@erseco/code-snippets-client`) los lleva al
    sitio de destino, con las credenciales del `.env`.
- El repo se monta en el contenedor en `wp-content/evt-dev`.
- `.local/` es material descargado de producción con datos personales: **no se
  versiona y no se toca**.

### Lo que sustituye, en una línea cada cosa

Hace falta saberlo para no reinventar los errores de la casa:

| Hoy en producción | Aquí |
|---|---|
| `page` jerárquica creada por la acción «Crear Página» de un formulario de 146 campos | CPT `evt_event` **jerárquico**: la raíz es el evento, las hijas son las secciones. Conserva las URL |
| El contenido de la página lo interpola una vista del gestor de formularios: 42 KB y 193 condicionales anidados dentro de un campo de texto | Código en `src/Evt/`, con tests y con diff |
| Un campo del formulario, «Tipo de página» | Meta `evt_section_type` con lista cerrada en PHP |
| Una taxonomía `convocatoria` con 50 términos que mezcla estado, tipología, curso y área, separados con listas de exclusión de IDs a mano | `evt_type` (tipología), `evt_course` (curso), `evt_area` (área). El estado **se deriva de las fechas**, no es un término |
| El área es un rol, y ningún rol de área tiene capacidades de escritura | El área es un término de `evt_area` asignado al usuario en la meta `evt_area`; los permisos los decide un único guardián |
| `post_parent` lo fija un snippet leyendo `$_POST` sin comprobar nada | `Domain/EventInput` valida y `Access/EventAccess` autoriza |

**Las inscripciones también se sustituyen**, y es lo último que quedaba fuera:
el formulario es un núcleo fijo más unas pocas preguntas por evento (ADR-0031),
una inscripción es un `evt_registration` colgado del evento (ADR-0032) y elegir
taller tiene aforo duro y cambio hasta el cierre (ADR-0033). **No se lee nada
del gestor de formularios anterior** ni se añade dependencia de él: las
inscripciones de los eventos ya celebrados se quedan donde están.

La pestaña «Participantes» **no rompe esa regla**: el aplicativo no lee el
gestor de formularios, **pregunta** con el filtro `evt_participants` y quien lo
tenga delante contesta desde un snippet suelto (ADR-0027). Lo que sí es del
aplicativo —el filtro y la exportación a CSV— es puro y se prueba sin ningún
gestor de formularios delante. En el wp-env contesta el mu-plugin de
desarrollo.

---

## Mapa del repositorio

| Ruta | Contenido |
|------|-----------|
| `src/Evt/` | Aplicativo (fuente; `make bundle`) |
| `src/Evt/load-order.php` | Orden de carga: lista única, la usan bootstrap y el bundler |
| `src/Evt/bootstrap.php` | Define `EVT_SRC_DIR`, recorre la lista y llama `App::boot()` |
| `build/pack-snippet.php` | Empaquetador `src/Evt/` → bundle |
| `snippets/` | Snippets sueltos + bundle generado; sync a Code Snippets |
| `scripts/` | Aprovisionamiento (`wp eval-file` / Playground) |
| `scripts/lib/snippet-sync.php` | Librería de sincronización con Code Snippets |
| `scripts/mu-plugins/` | mu-plugin solo de desarrollo |
| `tests/` | PHPUnit sobre un WordPress vivo |
| `docs/adr/`, `docs/sdd/` | ADR y SDD |
| `.agents/skills/` | Skills de agentes (`.claude/skills/` enlaza las propias) |
| `CHANGELOG.md` | Versión y cambios; `make bundle` lee de ahí el `@version` |
| `.env.dist` | Plantilla de configuración de despliegue (copiar a `.env`); la lee `npm run snippets` |
| `blueprint.json` / `blueprint-local.json` | Playground remoto / local |
| `.wp-env.json` / `Makefile` | Docker / comandos |
| `.local/` | Descargas de producción. **Ignorado. No lo abras ni lo cites en un commit** |

### Anatomía de `src/Evt/`

Todo son `final class` con métodos `static`. Sin contenedor de inyección, sin
interfaces, sin factorías, sin capa de servicios: es lo que permite
concatenarlo en un fichero.

```
src/Evt/
├── load-order.php                 Lista única y ordenada
├── bootstrap.php                  EVT_SRC_DIR + require de la lista + App::boot()
├── App.php                        Idempotente. Registra CPT, taxonomías y el register() de cada módulo
├── Meta/EventMetaKeys.php         Claves de meta y listas cerradas (tipos de sección, estados)
├── Meta/EventMetaRegistration.php register_post_meta con tipo, sanitize y auth_callback
├── Domain/EventInput.php          Valida y normaliza el alta/edición. Puro, sin WordPress
├── Domain/EventState.php          Estado derivado de las fechas: próximo | abierto | finalizado
├── Access/EventAccess.php         Único guardián: quién edita qué, acotado por área
├── Meta/ProgrammeMetaKeys.php     Claves de meta de ponentes y actividades, y los tipos de actividad
├── Domain/ActivityInput.php       Valida el alta/edición de ponentes y actividades. Puro
├── PublicFront/Programme.php      Ponentes y actividades de un evento: leer, escribir, la parrilla
├── PublicFront/Participants.php   Inscripciones: el enganche, el filtro y el CSV
├── PostType/EventPostType.php     CPT evt_event jerárquico + capacidades
├── PostType/SpeakerPostType.php   CPT evt_speaker
├── PostType/ActivityPostType.php  CPT evt_activity
├── Taxonomy/EventTaxonomies.php   evt_area, evt_type, evt_course
├── Admin/EventAdmin.php           Columnas, filtro por área y acotado del listado
└── Admin/Settings.php             Ajustes y diagnóstico (capacidad evt_manage_app)
```

Ni una capa más. Si crees que hace falta otra, escribe la ADR primero.

---

## Qué comando ejecutar

| Situación | Comando |
|-----------|---------|
| Primera vez | `make install && make up` |
| Cambio en `src/Evt/` | `make bundle && make sync-snippets` (+ `make lint` / `make test`) |
| Cambio en un snippet suelto | `make sync-snippets` |
| Tras `make bundle`, con wp-env arrancado | `make snippet-check` (los snippets sobreviven al guardado de Code Snippets) |
| Un test concreto | `make test FILE=tests/unit/test-event-access.php` o `make test FILTER=nombre_del_metodo` |
| Cambio en una confirmación o en `assets/js/evt-app.js` | `make test-browser` (los tres escalones: SweetAlert2, `confirm()` y sin JavaScript) |
| Ver la cobertura | `make coverage` (reinicia wp-env con Xdebug) |
| Entorno raro | `make clean` |
| Empezar de cero | `make destroy && make up` |
| Probar sin Docker | `make playground` |
| Antes de commit/PR | `make check` |
| Publicar una versión | `make release` (tras cerrar el bloque del CHANGELOG) |
| Llevar un snippet al sitio de destino | `npm run snippets -- push <id> --file snippets/… --dry-run` (sin `--yes` no escribe) |

Sitio local: <http://localhost:8798> (`admin` / `password`). Tests en el 8799.

---

## Cuando quien te dirige no viene de desarrollo

Parte del equipo trabaja en este repositorio **a través de un agente**, sin
experiencia previa en desarrollo. Para esa persona está
[`docs/desarrollo-para-empezar.md`](docs/desarrollo-para-empezar.md): instalación
en macOS y en Windows, `git pull` antes de empezar, `make install` / `make up`,
el ciclo rama → `make check` → `gh pr create` → **revisión de otra persona**, y
qué mirar cuando algo falla.

Si es tu caso, dos consecuencias para ti:

- **Explica en castellano llano qué has tocado y por qué**, y qué tiene que ver
  en pantalla para comprobarlo. Un resumen que solo entiende quien ya sabe no
  sirve de nada.
- **No propongas ni ejecutes nada que se salte la revisión**: ni `push` a
  `main`, ni fusionar el PR, ni desactivar una comprobación para que `make
  check` pase. Si `make check` está en rojo, se arregla; no se rodea.

---

## Añadir código al aplicativo

**¿Clase o función suelta?** Dominio de eventos → `src/Evt/` (acaba inlineado
en el bundle). Helpers que otros snippets puedan usar por su cuenta → un
`snippets/*.php` propio con su cabecero `Snippet Name: EVT — …`. El bundle
llama a los sueltos con `function_exists()`: si el snippet no está desplegado,
degrada en silencio, no peta. Los roles son el caso canónico:
`snippets/roles-and-profiles.php` **no entra en el bundle**.

**Fichero nuevo en `src/Evt/`** → añádelo a `src/Evt/load-order.php`, después
de todo lo que extienda o implemente. Es la única lista; `make bundle` falla si
un fichero de `src/Evt/` no está en ella. Sin esa guarda, el fichero
simplemente no llegaría a producción, en silencio.

**El primer fichero del load-order** tiene que abrir con `namespace`: ahí es
donde el bundler inyecta la guarda `EVT_BUNDLE_LOADED`, que evita que el
segundo `eval()` de Code Snippets muera con «Cannot redeclare class». Es una
constante y no un `class_exists()` a propósito: PHP resuelve pronto las clases
sin padre y `App` ya existiría en la primera pasada.

**El bundle no lo cubren los tests**: `tests/bootstrap.php` carga los módulos de
`src/Evt/` y se salta `*.bundle.php`. Por eso `make bundle` hace `php -l` del
resultado — un `declare(strict_types=1)` es legal por fichero y **fatal** al
concatenar. Por eso también está prohibido.

**Permisos**: toda decisión de «puede o no puede» pasa por
`Access/EventAccess`. Esconder algo en el listado con `pre_get_posts` **no es
protegerlo**: quedan abiertos el enlace directo a `post.php?post=N`, la edición
rápida, la REST API y las acciones en bloque. La capa que protege es
`map_meta_cap`. Se hacen las dos, en ese orden de importancia.

**Verlo funcionando:** `make bundle && make sync-snippets` y recarga
<http://localhost:8798/wp-admin/edit.php?post_type=evt_event>.

---

## Convenciones

- **Idiomas:** ver [más abajo](#idiomas); en corto, identificadores en
  **inglés** y todo lo que lee una persona en **castellano**.
- **Prefijo:** `evt_` / `EVT_`; namespace `Evt`; snippets `EVT — …`.
- **Estilo:** WPCS, tabuladores, Yoda conditions, escape de salida.
  `make lint` / `make fix`.
- **`declare(strict_types=1)` está prohibido** (ver arriba).
- **Aplicativo:** editar solo `src/Evt/`; no editar a mano
  `snippets/*.bundle.php` (regenerar con `make bundle`).
- **Librerías de terceros:** en producción desde **jsDelivr con SRI**
  (`cdn.jsdelivr.net/npm/<paquete>@<versión>/…`, con `integrity` y
  `crossorigin`); en desarrollo y en los tests desde **`node_modules`**, que el
  mu-plugin reescribe. Van en `package.json` con la versión **exacta**, y esa
  versión es la misma en los tres sitios: `package.json`, la URL y el `$ver` del
  encolado. **Nunca se inlinean en el bundle.** El CDN **no** es un problema en
  este proyecto: es el camino
  ([ADR-0015](docs/adr/ADR-0015-librerias-de-terceros-desde-cdn-con-sri.md)).
- **Scripts de `scripts/`:** idempotentes, **sin `WP_CLI`** (corren también
  bajo Playground), raíz con `dirname( __DIR__ )`, y lanzan `RuntimeException`
  cuando algo falla, para que `make provision` se caiga en vez de seguir a
  medias.
- **Listas cerradas en código, no en taxonomía:** los tipos de sección
  (`programa`, `ponentes`, `inscripcion`, `multimedia`, `contacto`,
  `actividades`, `encuesta`, `participacion`, `preguntas`, `directo`, `otra`) y
  los estados viven en `Meta/EventMetaKeys.php`. La lección de `convocatoria`
  es que una taxonomía abierta acaba mezclando cuatro ejes.

### Idiomas

Qué va en cada idioma. Si algo no está en esta tabla, va en castellano: es lo
que lee una persona.

| Qué | Idioma |
|--|--|
| Identificadores que se llaman: clases, métodos, funciones, nombres de fichero | **inglés** |
| Variables y parámetros locales | **inglés** |
| Slugs, claves de meta y sus valores, roles, opciones y hooks | **inglés** |
| Nombres de test | **inglés** (son funciones) |
| Docblocks (`/** … */`) | **inglés** |
| Comentarios sueltos (`// …`) | **castellano** si explican una regla del dominio o una decisión; inglés si son puramente técnicos |
| Cadenas de la interfaz, etiquetas, mensajes de error y avisos | **castellano** |
| ADR, SDD, requisitos, planes, `CHANGELOG`, `README`, este fichero | **castellano** |
| Mensajes de commit, títulos y descripciones de PR | **castellano** |
| `Makefile`: `help` y comentarios | **castellano** |
| URL de las páginas | **castellano** |

**La raya está entre lo que lee una máquina y lo que lee una persona**: en
inglés los slugs de los tipos de contenido y de las taxonomías, las claves de
meta y sus valores (`evt_section_type`, `programa`), los roles
(`evt_organiser`), las opciones, los hooks y las clases que los reflejan. En
castellano las cadenas, las etiquetas, los rótulos de rol y las URL de las
páginas, que se comparten y se teclean.

Los términos de `evt_area`, `evt_type` y `evt_course` son **datos**, no
identificadores: sus nombres van en castellano y sus slugs, como los escriba
quien los cree.

**Por qué los comentarios del dominio van en castellano:** quien los lee
—persona o agente— tiene que enlazarlos con el requisito y con la cadena que
sale en pantalla, y ambos están en castellano. Los docblocks no: describen la
API, sus etiquetas ya son inglesas y las revisa WPCS.

---

## Política ADR/SDD

Toda decisión no trivial de IA se registra en `docs/adr/` o `docs/sdd/` con
`ai_assistance` (tool, model). Plantillas e índices en esos directorios; el ID
es `max(existentes) + 1`, con ceros a la izquierda, y nunca se reutiliza.

Una ADR aceptada **no se reescribe**: se le añade una `## Adenda — AAAA-MM-DD`,
o se crea una ADR nueva con `supersedes` / `superseded_by` y se actualiza
`registro.md`.

La raya está en **publicado**. Mientras una ADR no haya salido del repositorio
—sin commit, sin enlace compartido— no es todavía la dirección de nadie y se
corrige en su propio texto; ahí es donde se consolida y se renumera. En cuanto
hay commit, el identificador y el texto se congelan y toda corrección es adenda
o ADR nueva.

Evidencia antes que preferencia: cada afirmación técnica lleva su fuente
verificable (ruta del repo con línea, documentación oficial, experimento
reproducible, PR o ADR previa). Los contras se escriben con la misma dureza
que los pros.

---

## Este repositorio se publica en abierto

Va a ser **software libre**, así que **nada de lo que se versiona puede decir
de quién es el despliegue, dónde está, qué infraestructura usa ni cómo era por
dentro el sistema que se sustituye**
([ADR-0030](docs/adr/ADR-0030-el-repositorio-se-publica-sin-nada-de-nadie.md)).

Lo que **no** se escribe en `src/`, `docs/`, `scripts/`, `tests/`, `README.md`
ni en este fichero:

| Qué | Dónde va |
|---|---|
| URL de analítica, de avisos de cookies o de servicios de una organización, y sus identificadores | Configuración: el filtro `evt_chrome`, vacío por defecto |
| El nombre, el escudo, el rótulo o los enlaces legales de una organización | Lo mismo |
| El nombre de la red, del sitio o del subsitio de destino | El `.env`, que no se sube. `.env.dist` los trae **vacíos** |
| Cómo era la instalación anterior por dentro: sus formularios, vistas, campos, fragmentos de código y sus números | `.local/`, que está en el `.gitignore` |
| Cuánta gente hay, en qué áreas y con qué rol | `.local/`, o se escribe la decisión sin la cifra |
| El nombre de otro repositorio del equipo, sus rutas, sus opciones o sus prefijos | `.local/`. **Son privados**: citarlos publica código de otro |
| Rutas absolutas de un portátil | En ningún sitio |

**La razón de una decisión se queda; el dato que la sostenía puede irse.** Se
puede escribir por qué se eligió algo sin publicar el inventario de una
instalación ajena. Vale igual para el argumento de autoridad: «se hace así en
tal repositorio» no es una razón, es una cita —**y además privada**—; lo que
convence es el motivo, escrito entero aquí. Cuando una ADR se apoye en una medición, se dice que está
medida y se deja el material en `.local/`.

**Se comprueba**, no se confía: `make check-public` recorre lo que git
versionaría y falla con el motivo escrito. Si añade una regla nueva, añádala
también a `scripts/check-public.mjs`: esa lista es donde vive lo aprendido.

---

## Reglas duras

- **No convertir el repo en plugin de producción.** Ni cabecera, ni
  `readme.txt`, ni hooks de activación, ni rutas de plugin.
- **Código del aplicativo en `src/Evt/`** → bundle → Code Snippets. No dejar
  cambios solo en el admin de Snippets.
- **Nada de `declare(strict_types=1)`.**
- **No tocar nada del WordPress de producción.** Este repositorio es de solo
  escritura hacia dentro: lo que se escribe, se escribe aquí.
- **No versionar ni citar `.local/`**: son datos personales reales y el mapa
  del sistema anterior.
- **Nada de ninguna organización concreta en lo que se versiona** (ADR-0030):
  ni marca, ni infraestructura, ni destino de despliegue. `make check-public`.
- `evt_registration` **sí** se registra: una inscripción es un contenido que
  cuelga de su evento (ADR-0032). `evt_session` **no**: elegir taller es el ID
  de la actividad guardado en la inscripción, no un tipo de contenido (ADR-0033).
- El mu-plugin de `scripts/mu-plugins/` es solo desarrollo.
- Diffs pequeños y enfocados. No inventar ficheros que nadie ha pedido.

---

## Definición de hecho

1. `make lint` sin errores.
2. `make test` sin fallos.
3. `make bundle` genera un bundle que pasa `php -l`, y `make snippet-check`
   pasa con el entorno arrancado.
4. `make up` / provisión OK (<http://localhost:8798>, `admin` / `password`).
5. Docs actualizadas si aplica; ADR/SDD con `ai_assistance` si hubo decisión.

---

## Referencia de herramientas

- `make help` y el `Makefile` son la referencia de targets.
- wp-env: **Code Snippets + Members + WPFront User Role Editor + SQL Buddy**
  (ningún gestor de formularios). Puertos `8798` / `8799`.
- El destino en producción **puede ser un subsitio de un multisitio**, a
  diferencia del entorno local, que es un sitio único. Cualquier cosa que
  construya el nombre de una tabla o invoque `wp eval-file` sin `--url=` merece
  una mirada antes de darla por buena.

---

## Skills (`.agents/skills/`)

Viven en:

- `.agents/skills/` — GitHub Copilot, Codex, Cursor y el resto de agentes que
  comparten esa ruta
- `.claude/skills/` — Claude Code

Las copias canónicas viven en `.agents/skills/`. Claude Code las ve en
`.claude/skills/` como **enlaces** a esas carpetas — `.gitignore` ignora
directorios reales ahí (`.claude/skills/*/`).

Skills propias: créalas en `.agents/skills/<nombre>/` y
`ln -s ../../.agents/skills/<nombre> .claude/skills/<nombre>`. Las de terceros,
instálalas solo para Copilot y enlázalas igual:

```bash
gh skill add WordPress/agent-skills wp-performance --agent github-copilot
ln -s ../../.agents/skills/wp-performance .claude/skills/wp-performance
gh skill update --all
```

`gh skill` mete la procedencia en el frontmatter del `SKILL.md`. No instales
`--agent claude-code` ni `--agent grok` en este repo. Las de terceros van
**verbatim**: no las reformatees, divergir de upstream complica
`gh skill update`.

### Compatibilidad de skills

Las skills genéricas de WordPress orientan la implementación, pero este
repositorio **no es un plugin distribuible**. La arquitectura del repositorio
siempre prevalece sobre las recomendaciones de una skill:

- No crear un fichero bootstrap de plugin ni cabeceras de plugin ni
  `readme.txt`.
- El código del aplicativo pertenece a `src/Evt/`.
- Los artefactos de producción son Code Snippets generados con `make bundle`.
- No introducir hooks de activación/desactivación de plugin.
- No asumir rutas o URL relativas a un plugin (`plugin_dir_path()`,
  `plugin_dir_url()`, `plugins_url()`, `register_activation_hook()`).

`wp-plugin-development` se usa para **patrones de desarrollo WordPress**
(hooks, CPT, admin, shortcodes, capabilities, enqueue), no para packaging ni
estructura de plugin.
