# Cómo está hecho el aplicativo de eventos

Lo que el [README](../README.md) no cuenta para no ser largo: qué sustituye,
dónde está el código, el modelo de datos, los roles, los comandos y la
política de cobertura. Las reglas para trabajar en el repositorio —convenciones,
idiomas, qué no se publica— están en [AGENTS.md](../AGENTS.md).

## Qué viene a sustituir

Explica casi todas las decisiones de diseño, así que conviene decirlo sin
adornos.

Hoy cada evento es un **formulario enorme** cuya acción «Crear Página» genera
una `page` jerárquica por cada sección. El contenido de esas páginas no lo
escribe nadie: lo interpola **una vista del gestor de formularios llena de
condicionales anidados**, un motor de plantillas dentro de un campo de texto,
sin control de versiones, sin tests y sin forma de revisar un cambio. La
jerarquía del sitio depende de un fragmento de código pegado a mano que lee
`post_parent` de `$_POST` sin comprobar nada.

Alrededor de eso:

- Páginas **sin convención de slugs**, la firma de la creación manual.
- **Una sola taxonomía**, `convocatoria`, que mezcla cuatro ejes distintos
  —estado, tipología, curso escolar y área organizadora— y que se separa con
  listas de exclusión de IDs mantenidas a mano.
- **El área modelada como rol**: cada área nueva es un rol nuevo, ningún rol
  sabe expresar «los eventos de esta área» y ninguno tiene capacidades de
  escritura, porque las áreas no editan WordPress: rellenan el formulario.

El aplicativo se lleva a `src/Evt/` **la estructura**:

| Hoy | En el aplicativo |
|---|---|
| `page` jerárquica creada por un formulario | `evt_event` **jerárquico**: la raíz es el evento y las hijas, sus secciones. Mismo árbol, **mismas URL** (ADR-0042) |
| Una vista con el contenido interpolado | Código en `src/Evt/`, con tests y con diff |
| El tipo de página, un campo del formulario | La meta `evt_section_type`, con lista cerrada en PHP |
| `convocatoria`, cuatro ejes en una taxonomía | `evt_area` (ámbito), `evt_type` (tipología), `evt_course` (curso). El estado **se deriva de las fechas** |
| El área como rol | Un término de `evt_area` en el perfil de la persona, y un único guardián de permisos |
| `post_parent` leído de `$_POST` | `Domain/EventInput` valida y `Access/EventAccess` autoriza |
| Las inscripciones en el gestor de formularios | `evt_registration`, colgada del evento: un núcleo fijo más unas pocas preguntas por evento (ADR-0031, ADR-0032), y elegir taller con aforo duro (ADR-0033) |

Las inscripciones de los eventos ya celebrados se quedan donde están: el
aplicativo no lee el gestor de formularios. La pestaña «Participantes»
**pregunta** con el filtro `evt_participants` y quien lo tenga delante contesta
desde un snippet suelto (ADR-0027).

## Dónde está el código

**Este repositorio no es un plugin de WordPress.** En producción solo se puede
pegar código en Code Snippets, así que el artefacto es un único fichero PHP:

| Ruta | Contenido |
|------|-----------|
| `src/Evt/` | El aplicativo, por módulos: **se edita aquí** |
| `src/Evt/load-order.php` | Orden de carga: lista única, la usan el arranque y el empaquetador |
| `build/pack-snippet.php` | Empaquetador: `src/Evt/` → un solo snippet |
| `snippets/` | El bundle generado (`make bundle`) y los snippets sueltos, como los roles |
| `assets/` | Hojas y guiones; viajan dentro del bundle |
| `scripts/` | Provisión idempotente (`wp eval-file` y Playground) |
| `scripts/mu-plugins/` | mu-plugin **solo de desarrollo** |
| `tests/` | PHPUnit sobre un WordPress vivo; `tests/js/`, Vitest para los guiones de `assets/js` |
| `docs/` | Requisitos, ADR, SDD y planes |
| `blueprint.json` / `blueprint-local.json` | Playground remoto / local |
| `.env.dist` | Plantilla del destino de despliegue; el `.env` no se sube (ADR-0030) |
| `.local/` | Material descargado de producción. **No se versiona**: lleva datos personales |

El ciclo es editar `src/Evt/`, `make bundle && make sync-snippets` y recargar.
El bundle no se edita a mano: se regenera.

## Modelo de datos

| Tipo de contenido | Qué es |
|---|---|
| `evt_event` | **Jerárquico**: la raíz es el evento y las hijas, sus secciones (programa, ponentes, inscripción, contacto…), con el tipo en `evt_section_type` |
| `evt_speaker` | Ponente o persona comunicadora, colgada del evento |
| `evt_activity` | Actividad del programa: ponencia, mesa redonda, taller… Un taller es una actividad con plazas |
| `evt_registration` | Una inscripción, colgada del evento. Elegir taller es el ID de la actividad guardado en ella, no otro tipo de contenido |

| Taxonomía | Eje | Ejemplos |
|---|---|---|
| `evt_area` | Ámbito organizativo jerárquico. **Es el eje de los permisos** | `ambito-1`, `subambito-1` |
| `evt_type` | Tipología | `jornadas`, `encuentro`, `congreso` |
| `evt_course` | Curso escolar | `2025-2026` |

El catálogo de centros educativos no es un tipo de contenido: es un dato
maestro externo con una copia local cacheada. La inscripción guarda el código
oficial del centro y su nombre como foto de ese momento (ADR-0037).

Con sesión, el nombre, los apellidos, el correo y el centro salen de la cuenta
de quien se inscribe —`first_name`, `last_name`, `user_email` y la meta de
usuario `codigo`— y no se pueden cambiar en el formulario: el servidor los
vuelve a poner al recibirlo. Se da por hecho que esos datos de la cuenta son
correctos. El código de centro sale en el perfil como «Código de centro»: lo
cambia administración y los demás lo ven de solo lectura (ADR-0048).

Los documentos que aporta quien se inscribe **no son adjuntos de WordPress**:
se guardan aparte, con nombre opaco, y solo se descargan desde el aplicativo
(ADR-0036).

## Roles

| Rol | Quién |
|---|---|
| `editor` | El actor recomendado. Edita los eventos de su ámbito y sus descendientes, y puede compartir un evento con otros ámbitos |
| `evt_organiser` | Rol anterior, conservado para las cuentas que ya lo tienen, con el mismo acotado |
| `administrator` | Todo, en todos los ámbitos; asigna el ámbito de cada persona |

El ámbito de una persona es un término de `evt_area` en su perfil, más todos
sus descendientes. Un evento puede llevar varios ámbitos, y cualquiera de ellos
lo edita. Sin ámbito válido no se edita nada: el acotado **falla en cerrado**.
Toda decisión de «puede o no puede» pasa por `Access/EventAccess`.

El CSS a medida lo escribe el ámbito y el JavaScript solo administración: uno
cambia cómo se ve una página y el otro ejecuta código en el navegador de cada
visitante (ADR-0014).

El detalle, capacidad a capacidad: [roles-y-permisos.md](roles-y-permisos.md).

## El entorno

| Plugin | Uso |
|--------|-----|
| Code Snippets | Ejecuta el bundle y los snippets auxiliares |
| WPFront User Role Editor | Ver los roles y las capacidades `evt_*`, y cambiar de usuario para probar |
| SQL Buddy | Inspección de datos en local |

El gestor de formularios **no se instala**: nada de `src/Evt/` depende de él.

Puertos: **8798** (desarrollo, `admin` / `password`) y **8799** (tests), fuera
de los habituales para que convivan con otros entornos.

## Comandos

`make help` los lista todos. Los de cada día:

| Comando | Qué hace |
|---------|----------|
| `make install` / `make up` | Dependencias / arranca wp-env y lo provisiona |
| `make bundle && make sync-snippets` | Regenera el bundle y lo lleva a Code Snippets |
| `make test` | PHPUnit (admite `FILE=…` y `FILTER=…`) |
| `make test-js` | Tests unitarios de `assets/js` con Vitest; cobertura en `artifacts/coverage-js/` |
| `make lint` / `make fix` | PHPCS / PHPCBF |
| `make check` | Todo lo que mira el CI; **antes de cada PR** |
| `make check-plugin` | WordPress Plugin Check sobre el código de los snippets |
| `make snippet-check` | Los snippets sobreviven al guardado de Code Snippets |
| `make capturas` | Recorre las pantallas y deja `capturas/informe.html` |
| `make coverage` | Cobertura de `src/Evt` (reinicia wp-env con Xdebug) |
| `make profile` / `make profile-compare` | Perfil de rendimiento con SPX y comparación entre dos ramas |
| `make clean` / `make destroy` | Resetea el entorno / lo destruye |
| `make playground` | WordPress Playground local, sin Docker |
| `make release` | Etiqueta la versión del CHANGELOG y publica la release |

## Cobertura

El suelo es el **90 %** y bloquea en los dos ejes: el del parche —lo que se
toca en un PR va con sus tests— y el del proyecto —no se compensa tocando
poco—. Está en [`codecov.yml`](../codecov.yml), y la decisión, en la
[ADR-0011](adr/ADR-0011-ci-y-politica-de-pruebas.md).

Los guiones de `assets/js` se miden con Vitest (`make test-js`) y su informe
sube a Codecov con el flag `js`, sumado al de PHP: el suelo cuenta los dos
(ADR-0011, adendas del 2026-09-29).

`src/Evt/App.php` no sale en el mapa del README porque está excluido de la
medición: su cuerpo corre en el arranque, antes de que PHPUnit empiece a medir,
y lo que hace se comprueba en `test-load-order.php`.
