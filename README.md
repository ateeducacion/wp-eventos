# Eventos — entorno de desarrollo

[![CI](https://github.com/ateeducacion/wp-eventos/actions/workflows/ci.yml/badge.svg)](https://github.com/ateeducacion/wp-eventos/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/ateeducacion/wp-eventos/graph/badge.svg?token=oYuGLf1luI)](https://codecov.io/gh/ateeducacion/wp-eventos)

Entorno de desarrollo del aplicativo de **eventos** (encuentros, jornadas y
congresos): WordPress + **Code Snippets** + **Members** + **WPFront User Role
Editor**, con el dominio en tres tipos de contenido —`evt_event`,
`evt_speaker`, `evt_activity`— y el código modular de `src/Evt/` empaquetado en
un único snippet con `make bundle`.

Dónde se despliega **no está en el repositorio**: va en el `.env`, que no se
sube ([ADR-0030](docs/adr/ADR-0030-el-repositorio-se-publica-sin-nada-de-nadie.md)).

> **Este repositorio NO es un plugin de WordPress.** No lleva cabecera de
> plugin, ni `readme.txt`, ni `register_activation_hook()`, ni rutas de plugin.
> Versiona, prueba y sincroniza el material que en producción se activa como
> Code Snippet(s). El artefacto de producción es un fichero PHP que acaba en
> Code Snippets —lo empuja `npm run snippets`—, porque es lo único que ese
> WordPress permite desplegar.

## Qué viene a sustituir

Conviene decirlo sin adornos, porque explica casi todas las decisiones de
diseño. Hoy el aplicativo de eventos es un **formulario de 146 campos** cuya
acción «Crear Página» genera una `page` jerárquica por cada sección del evento.
El contenido de esa página no lo escribe nadie: lo interpola una **vista del
gestor de formularios, 42 KB con 193 bloques condicionales anidados** —un motor
de plantillas dentro de un campo de texto, sin control de versiones, sin tests
y sin forma de revisar un cambio—. La jerarquía del sitio depende de un
fragmento de código de siete líneas, pegado a mano, que lee `post_parent` de
`$_POST` sin comprobar nada.

Alrededor de eso: 163 páginas sin convención de slugs (hay `programa-ed`,
`programacomunicacion2021`, `cjle2021-programa`, `programa_`, `programa-8616`…,
la firma de la creación manual); **una sola taxonomía**, `convocatoria`, con 50
términos que mezclan cuatro dimensiones distintas (estado, tipología, curso
escolar y área organizadora) y que se separan con cuatro listas de exclusión de
IDs mantenidas a mano; **el área modelada como rol**, de modo que cada área
nueva es un rol nuevo y ningún rol sabe expresar «los eventos de esta área»;
y ni uno solo de esos roles de área tiene una capacidad de escritura, porque
las áreas no editan WordPress: rellenan el formulario desde el frontal.

Este repositorio se lleva a `src/Evt/` **la estructura**: el evento y sus
páginas satélite pasan a `evt_event` jerárquico (mismo árbol, mismas URL), las
cuatro dimensiones de `convocatoria` se separan en `evt_area`, `evt_type` y
`evt_course`, y el área deja de ser un rol para ser un término asignado al
usuario, con un único guardián de permisos.

Lo que **no** se lleva, y con una ADR que lo justifica: las **inscripciones y
la selección de talleres siguen en el gestor de formularios que ya hay**. Hay
un formulario por evento y todas sus entradas están vivas; migrarlas no es
parte de la fase 1.

## Fuente de verdad

| Ruta | Contenido |
|------|-----------|
| `src/Evt/` | Aplicativo modular (editar aquí) |
| `src/Evt/load-order.php` | Orden de carga: lista única, la usan bootstrap y el bundler |
| `snippets/` | Snippets sueltos + `evt-eventos-app.bundle.php` generado (`make bundle`) |
| `docs/` | REQ / SDD / ADR |

## Modelo de datos (fase 1)

| Tipo de contenido | Qué es | Hoy |
|---|---|---|
| `evt_event` | **Jerárquico**: la raíz es el evento y las hijas son sus páginas satélite (programa, ponentes, inscripción, contacto…), con la sección en la meta `evt_section_type` | `page` jerárquica |
| `evt_speaker` | Ponente o persona comunicadora | Entradas de un formulario |
| `evt_activity` | Actividad del programa: ponencia, mesa redonda, comunicación, taller | Entradas de otro formulario |

| Taxonomía | Eje | Ejemplos |
|---|---|---|
| `evt_area` | Área organizadora. **Es el eje de permisos** | `innovacion`, `salud`, `steam` |
| `evt_type` | Tipología | `jornadas`, `encuentro`, `congreso`, `taller` |
| `evt_course` | Curso escolar | `2025-2026` |

Que `evt_event` sea jerárquico y sustituya 1:1 a la `page` de hoy es lo que
**conserva las URL** de los eventos ya publicados.

## Roles

Son **dos**, y solo uno lo crea el aplicativo:

| Rol | Etiqueta | Quién |
|---|---|---|
| `evt_organiser` | Organización de eventos | Personal de un área: **todo** lo de su área —eventos, secciones, ponentes, actividades, apariencia y el CSS a medida—, y marcar sus eventos como históricos |
| `administrator` | Administrador | Todo, en todas las áreas. Y lo único reservado: el **JavaScript** a medida, los ajustes del aplicativo y **desarchivar** |

**Por qué el CSS sí y el JavaScript no.** Porque el CSS cambia cómo se ve una
página y el JavaScript ejecuta código en el navegador de cada visitante. No son
el mismo riesgo, y por eso son dos capacidades distintas —`evt_edit_custom_css`
y `evt_edit_custom_js`— y no una
([ADR-0014](docs/adr/ADR-0014-css-del-area-javascript-de-administracion.md)).

El acotado por área es la user meta `evt_area` (uno o varios términos). Es
**fail-closed**: sin área asignada y sin `evt_edit_all_areas`, no se edita
nada. `evt_organiser` lo crea el snippet suelto
`snippets/roles-and-profiles.php`; `administrator` es el de siempre de
WordPress y solo se le cuelgan las capacidades del aplicativo. El detalle
completo, capacidad a capacidad, está en
[docs/roles-y-permisos.md](docs/roles-y-permisos.md).

## Requisitos

- **Docker** (wp-env)
- **Node.js 22** (ver `.nvmrc`)
- **PHP 8.1+** y **Composer** (lint / tests en el host)

El entorno de desarrollo y pruebas usa **PHP 8.3**, fijado en `.wp-env.json` y
en los workflows de CI; el mínimo de compatibilidad de Composer sigue en 8.1.

## Inicio rápido

> **¿Es tu primera vez y no vienes de desarrollo?** Empieza por
> [Empezar a trabajar en este proyecto](docs/desarrollo-para-empezar.md):
> qué instalar en macOS y en Windows, cómo levantar el entorno y cómo se
> propone un cambio (rama → `make check` → PR → revisión del equipo).

```bash
git clone https://github.com/ateeducacion/wp-eventos.git
cd wp-eventos
make install
make up
```

- Sitio: <http://localhost:8798> (`admin` / `password`)
- Eventos: <http://localhost:8798/wp-admin/edit.php?post_type=evt_event>
- Ponentes: <http://localhost:8798/wp-admin/edit.php?post_type=evt_speaker>
- Actividades: <http://localhost:8798/wp-admin/edit.php?post_type=evt_activity>

Los puertos son **8798** (desarrollo) y **8799** (tests), fuera de los que usan
por defecto otros entornos de la casa a propósito: así pueden estar varios
arriba a la vez.

### Usuarios de prueba

Los crea `scripts/seed-demo.php` (que es la fuente de verdad de esta tabla) en
`make provision`, `make up` y en los dos blueprints de Playground. La
contraseña es `password` en todos.

| Usuario | Rol | Área en el perfil |
|---------|-----|-------------------|
| `admin` | administrator | — (ve todo) |
| `coordinacion` | `evt_organiser` | Innovación **y** Convivencia escolar (dos áreas a la vez) |
| `organizacion` | `evt_organiser` | Innovación |
| `organizacion2` | `evt_organiser` | Innovación (segunda persona de la misma área) |
| `organizacion3` | `evt_organiser` | Convivencia escolar (otra área: sirve para ver el acotado) |

La cuenta `coordinacion` conserva el nombre de cuando existía un rol de
coordinación (`evt_coordinator`, retirado el 2026-09-13); hoy sirve para probar
el caso de una persona que pertenece a **dos** áreas. Quien necesite el ámbito
completo entra como `admin`.

Flujo sugerido: entra como `organizacion`, crea un evento y una página
satélite; entra como `organizacion3` y comprueba que el evento del área ajena
no aparece en el listado **ni se abre por enlace directo**.

Para cambiar de usuario sin cerrar sesión, **WPFront User Role Editor** (menú
«Switch To» en Usuarios), igual que en producción.

## Comandos

| Comando | Qué hace |
|---------|----------|
| `make help` | Lista los targets |
| `make install` | Composer + npm |
| `make up` | Arranca wp-env y lo provisiona |
| `make down` / `make destroy` | Para / destruye el entorno |
| `make clean` | Resetea desarrollo y tests y vuelve a provisionar |
| `make logs` / `make shell` | Logs del entorno / shell en el contenedor CLI |
| `make provision` | Bundle + snippets + roles + páginas + datos de demostración |
| `make bundle` | Regenera `snippets/evt-eventos-app.bundle.php` desde `src/Evt/` |
| `make sync-snippets` | Sincroniza `snippets/*.php` → Code Snippets |
| `make snippet-check` | Comprueba que los snippets sobreviven al guardado (doble eval) |
| `make test` | PHPUnit (admite `FILE=…` y `FILTER=…`) |
| `make skills-sync` | Copia `.agents/skills/` sobre `.claude/skills/` |
| `make check-plugin` | Pasa WordPress Plugin Check sobre el código de los snippets |
| `make test-browser` | Los tres escalones de la confirmación en un navegador real |
| `make coverage` | Cobertura de `src/Evt` (reinicia wp-env con Xdebug) |
| `make lint` / `make fix` | PHPCS / PHPCBF |
| `make phpmd` | PHP Mess Detector |
| `make check-provision` | Comprueba que la provisión propaga los fallos |
| `make check` | `lint` + `phpmd` + `check-provision` + `test` |
| `make release` | Etiqueta la versión del CHANGELOG y publica la release |
| `make playground` | WordPress Playground local, sin Docker |

### Flujo de desarrollo del aplicativo

```bash
# 1. Editar solo src/Evt/
# 2. Empaquetar y recargar en WordPress
make bundle && make sync-snippets
```

No edites a mano `snippets/*.bundle.php`: se regenera.

## Mapa del repositorio

| Ruta | Contenido |
|------|-----------|
| `src/Evt/` | Aplicativo: CPT, taxonomías, meta, acceso y escritorio |
| `build/pack-snippet.php` | Empaquetador `src/Evt/` → snippet único |
| `snippets/` | Snippets sueltos (roles) y el bundle generado |
| `scripts/` | Provisión idempotente (`wp eval-file` y Playground) |
| `scripts/mu-plugins/` | mu-plugin **solo de desarrollo** |
| `tests/` | PHPUnit sobre un WordPress vivo |
| `docs/adr/`, `docs/sdd/` | Decisiones y diseño |
| `.agents/skills/` | Skills de agentes (`.claude/skills/` las enlaza) |
| `CHANGELOG.md` | Versión y cambios; `make bundle` lee de ahí el `@version` |
| `blueprint.json` / `blueprint-local.json` | Playground remoto / local |
| `.wp-env.json` / `Makefile` | Docker / comandos |
| `.env.dist` | Plantilla de configuración de despliegue (copiar a `.env`) |
| `.local/` | Material descargado de producción. **No versionado**: lleva datos personales |

## Plugins del entorno

| Plugin | Uso |
|--------|-----|
| Code Snippets | Ejecuta el bundle y los snippets auxiliares |
| Members | Ver y ajustar `evt_organiser` y las capacidades `evt_*` del administrador |
| WPFront User Role Editor | Los mismos roles y el cambio de usuario para probar |
| SQL Buddy | Inspección de datos en local |

**El gestor de formularios no se instala en el entorno.** En producción
convive con el aplicativo (las inscripciones siguen ahí), pero nada de
`src/Evt/` depende de él y los tests no lo cargan.

## Cobertura

El suelo es el **90 %**, y bloquea en los dos ejes: el del parche —lo que se
toca en un PR va con sus tests— y el del proyecto —no se compensa tocando
poco—. Está en [`codecov.yml`](codecov.yml) y la decisión, con su porqué y lo
que se descubrió al subirla, en la
[ADR-0011](docs/adr/ADR-0011-ci-y-politica-de-pruebas.md).

En local se mide con `make coverage`, que reinicia wp-env con Xdebug.

[![Mapa de cobertura](https://codecov.io/gh/ateeducacion/wp-eventos/graphs/tree.svg?token=oYuGLf1luI)](https://codecov.io/gh/ateeducacion/wp-eventos)

Cada rectángulo es un fichero de `src/Evt` y su tamaño son sus líneas; el color
va de rojo a verde según lo cubierto. Sirve para lo que un porcentaje no dice:
**dónde** está lo que no se prueba. `src/Evt/App.php` no sale porque está
excluido de la medición —su cuerpo corre en el arranque, antes de que PHPUnit
empiece a medir, y lo que hace se comprueba en `test-load-order.php`—.

## Documentación

- [`docs/desarrollo-para-empezar.md`](docs/desarrollo-para-empezar.md) — guía
  paso a paso para quien no viene de desarrollo
- `docs/adr/registro.md` — índice de decisiones
- `docs/sdd/registro.md` — índice de diseño
- [`AGENTS.md`](AGENTS.md) — instrucciones para agentes de código y convenciones
