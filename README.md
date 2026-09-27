# Eventos

[![CI](https://github.com/ateeducacion/wp-eventos/actions/workflows/ci.yml/badge.svg)](https://github.com/ateeducacion/wp-eventos/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/ateeducacion/wp-eventos/graph/badge.svg?token=oYuGLf1luI)](https://codecov.io/gh/ateeducacion/wp-eventos)
[![Probar en WordPress Playground](https://img.shields.io/badge/Probar%20en%20WordPress%20Playground-3858E9?logo=wordpress&logoColor=white)](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/ateeducacion/wp-eventos/main/blueprint.json)

Un aplicativo para WordPress que gestiona **encuentros, jornadas y congresos**:
cada evento con su portada y sus secciones (programa, ponentes, inscripción,
contacto…), su programa por días y sedes, sus ponentes, las inscripciones con
elección de taller y aforo, y un taller de gestión donde cada ámbito organiza
solo lo suyo.

No es un plugin: se instala pegando un único **Code Snippet**, generado desde
`src/Evt/`.

## Pruébalo sin instalar nada

**[Abrir en WordPress Playground](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/ateeducacion/wp-eventos/main/blueprint.json)**:
WordPress entero corriendo en una pestaña del navegador. Tarda un par de
minutos, no toca nada de tu equipo y al cerrar la pestaña no queda rastro.

Monta el sitio con los plugins que hacen falta, carga el aplicativo, crea las
cuentas de prueba y varios eventos de demostración —con programa, ponentes e
inscripciones— y te deja en **«Gestión de eventos»**. Desde la barra superior
puedes **cambiar de cuenta** para ver el aplicativo con cada rol y cada ámbito.

Cada PR trae además su propio enlace, con el código de esa rama.

### Usuarios de prueba

La contraseña es `password` en todos. Los crea `scripts/seed-demo.php`, que es
la fuente de verdad de esta tabla.

| Usuario | Rol | Ámbito en el perfil |
|---------|-----|-------------------|
| `admin` | administrator | — (ve todo) |
| `coordinacion` | `evt_organiser` | Ámbito 1 (compatibilidad) |
| `organizacion` | `evt_organiser` | Ámbito 1 |
| `organizacion2` | `evt_organiser` | Ámbito 1 |
| `organizacion3` | `evt_organiser` | Ámbito 2 |
| `editor-ambito` | `editor` | Ámbito 1: edita los eventos de Subámbito 1 y Subámbito 2 |
| `editor-ambito2` | `editor` | Ámbito 2: edita el evento compartido, no los demás del Ámbito 1 |
| `editor-subambito` | `editor` | Subámbito 1: no edita Subámbito 2 |

## Requisitos

- **Docker** (wp-env)
- **Node.js 22** (ver `.nvmrc`)
- **PHP 8.1+** y **Composer**, para el lint y los tests en tu equipo

[![Mapa de cobertura](https://codecov.io/gh/ateeducacion/wp-eventos/graphs/tree.svg?token=oYuGLf1luI)](https://codecov.io/gh/ateeducacion/wp-eventos)

Cada rectángulo es un fichero de `src/Evt`, del tamaño de sus líneas, y el
color va de rojo a verde según lo cubierto por los tests. El suelo es el 90 %.

## En tu equipo

```bash
git clone https://github.com/ateeducacion/wp-eventos.git
cd wp-eventos
make install
make up
```

Sitio en <http://localhost:8798> (`admin` / `password`). Tras cambiar algo en
`src/Evt/`: `make bundle && make sync-snippets`. Antes de proponer un cambio:
`make check`.

> **¿No vienes de desarrollo?** Empieza por
> [Empezar a trabajar en este proyecto](docs/desarrollo-para-empezar.md): qué
> instalar en macOS y en Windows, cómo levantar el entorno y cómo se propone un
> cambio.

## Más documentación

- [Cómo está hecho](docs/arquitectura.md): qué sustituye, dónde está el código,
  el modelo de datos, los roles, los comandos y la cobertura.
- [Roles y permisos](docs/roles-y-permisos.md), capacidad a capacidad.
- [Decisiones (ADR)](docs/adr/registro.md) y [diseño (SDD)](docs/sdd/registro.md).
- [AGENTS.md](AGENTS.md): convenciones del repositorio, para personas y agentes.
- [CHANGELOG](CHANGELOG.md).
