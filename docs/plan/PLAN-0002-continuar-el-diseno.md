---
id: PLAN-0002
title: "Continuar el diseño: dónde se ha quedado el trabajo y cómo seguir"
status: Propuesta
date: 2026-09-13
related:
  issues: []
  prs: []
  adrs: [ADR-0006, ADR-0007, ADR-0008, ADR-0012, ADR-0013, ADR-0014, ADR-0015, ADR-0016, ADR-0017, ADR-0018, ADR-0019, ADR-0020, ADR-0021, ADR-0022, ADR-0023]
  sdds: [SDD-0002]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# PLAN-0002 — Continuar el diseño

Este documento existe para que quien retome el trabajo **no tenga que hacer
arqueología**. Está escrito para alguien que no ha visto nada de esto: no da por
sabido ni el proyecto, ni las decisiones, ni dónde vive cada cosa.

No es un plan de fases —eso es
[PLAN-0001](PLAN-0001-implantacion-por-fases.md)—. Es la
foto del 2026-09-13 al cerrar la sesión: qué hay, qué falta, qué se decidió y
qué preguntas siguen abiertas.

**Las cifras de este documento están medidas el 2026-09-13 con los comandos que
se citan junto a cada una.** No están copiadas de ningún otro documento, y donde
no coinciden con lo que decía otro papel se dice también.

---

## 1. Dónde está todo

| Cosa | Dónde | Sobrevive |
|---|---|---|
| El código y la documentación | Este mismo repositorio, `wp-eventos` | Sí |
| El lienzo de diseño publicado | URL privada, en `.local/lienzo-de-diseno.md` | Sí |
| Las fuentes del diseño | `.design/*.dc.html`, `.design/canvas.json`, `.design/_base.css` | Sí |
| El entorno de desarrollo | `wp-env` en <http://localhost:8798> | Se levanta con `make up` |
| Los contratos de trabajo de la sesión | el *scratchpad* de la sesión | **NO** |
| Los informes de investigación | el *scratchpad*, en `research/` | **NO** |

**El aviso importante: el *scratchpad* no sobrevive a la sesión.** Es un
directorio temporal del anfitrión, se borra, y con él se van los documentos que
dirigieron el trabajo de hoy (`CONTRATO.md`, `CONTRATO-INTERFAZ.md`,
`CONTRATO-CODIGO.md`, `CONTRATO-VENDORS.md`, `CONTRATO-PARTICIPACION.md`,
`CONTRATO-PONENTES.md`, `CONTRATO-PUBLICO.md`, `CONTRATO-ROLES.md`,
`CONTRATO-EDICION.md`, `CONTRATO-ESQUELETO.md`, `CONTRATO-CIERRE.md`) y trece
informes de investigación (`research/arquitectura.md`, `docs.md`, `huecos.md`,
`packaging.md`, `roles.md`, `tests.md`, `snippets-eventos.md`,
`snippets-vecinos.md`, `PRODUCCION.md` y los cuatro `pestana-*.md` de ponentes,
programa, talleres y participantes).

**Lo que haga falta conservar de ahí, se copia a `docs/` antes de cerrar.** Lo
que ya está a salvo son las decisiones: viven en `docs/adr/`, que es
precisamente para lo que sirve una ADR. Lo que no está a salvo son las
mediciones de los informes de investigación de las cuatro pestañas pendientes;
si esas pestañas se van a construir con ese material, hay que copiarlo a
`docs/investigacion/` primero.

**Y hay que decirlo claro: esa copia NO se hizo, y no es un olvido.** Los cuatro
informes `research/pestana-*.md` —unas 2.000 líneas— están escritos a partir de
`.local/`, y citan rutas, ficheros y configuración del sitio real. Meterlos en
`docs/` es meterlos en un repositorio que el §3 va a publicar, y eso es
exactamente lo que avisa el §5.4. Así que **es una decisión, no una tarea**: hay
que leerlos y decidir qué se conserva y en qué forma, y quien lo haga tiene que
saber que si el *scratchpad* ya se borró, ese material **ya no está** y las
cuatro pestañas se construirán midiendo otra vez desde `.local/`. No es el fin
del mundo —las fuentes siguen ahí—, pero son días de trabajo repetidos.

### El fichero de 2,5 MB de `.design/`

`.design/gestion-de-eventos.html` son **2.617.011 bytes**: es el editor de
lienzo empaquetado, sembrado a partir de `canvas.json` y de los `.dc.html`.
No puede acabar en el repositorio, y **ya está ignorado**: se comprobó al
cerrar la sesión del 2026-09-13 que las dos reglas de `.gitignore` hacen lo que
dicen, la que ignora el sembrado y la que salva las fuentes.

```
$ git check-ignore -v .design/gestion-de-eventos.html
.gitignore:50:/.design/*.html	.design/gestion-de-eventos.html

$ git check-ignore -v .design/Main.dc.html
.gitignore:51:!/.design/*.dc.html	.design/Main.dc.html   ← la «!» es una excepción: NO se ignora

$ git status --porcelain -uall .design/ | wc -l
12        # las doce fuentes salen como pendientes de añadir; el sembrado, no
```

Lo que se versiona y lo que no:

| Fichero | Se versiona | Por qué |
|---|---|---|
| `.design/*.dc.html` | Sí | Son las fuentes del diseño, una por pantalla |
| `.design/canvas.json` | Sí | La colocación de las pantallas y las notas del lienzo |
| `.design/_base.css` | Sí | Los tokens, sacados de `assets/css/evt-app.css` |
| `.design/_build.py` | Sí | El módulo auxiliar que compone cada `.dc.html` |
| `.design/gestion-de-eventos.html` | **No** | Se regenera; son 2,5 MB de editor empaquetado |

**Cómo se regenera:** el paso a paso está en
[`.design/README.md`](../../.design/README.md), que es donde hay que mirarlo;
en corto, se retoca la fuente y se vuelve a sembrar el lienzo con la *skill*
`design` de Claude Code apuntando a ese directorio. No hace falta guardar el
sembrado: si se pierde, se siembra otra vez desde las fuentes. Ese README
avisa además de dos cosas que no son evidentes: que los `.dc.html` piden un
`./support.js` que lo aporta el editor al empaquetar, y que si alguien edita en
el lienzo publicado y le da a **Guardar**, la versión buena pasa a estar allí y
**no aquí**.

`_build.py` no es un guion ejecutable —es un módulo con las funciones `build()`
y `tabs()` y no tiene bloque principal—; compone la cabecera y las pestañas
comunes de cada pantalla.

---

## 2. Qué está hecho y verde

Medido el 2026-09-13, con el entorno levantado. Los comandos van en la tabla
para que se puedan repetir.

| Qué | Cuánto | Medido con |
|---|---|---|
| Tests | **310** | `make test` |
| Aserciones | **2.376** | `make test` |
| Cobertura de líneas | **91,24 %** (3.644 / 3.994) | `make coverage` |
| Cobertura de métodos | **76,74 %** (264 / 344) | `make coverage` |
| Cobertura de clases | **41,67 %** (15 / 36) | `make coverage` |
| Ficheros que revisa PHPCS | **86**, sin un solo error ni aviso | `./vendor/bin/phpcs` |
| ADR escritas | **25** | `ls docs/adr/ADR-*.md \| wc -l` |
| Snippets desplegables | **4** | `ls snippets/*.php` |
| Commits | **0** | `git rev-list --all --count` |

Los cuatro snippets son `evt-eventos-app.bundle.php` (el aplicativo entero
empaquetado), `roles-and-profiles.php`, `bootstrap5.php` y `sweetalert.php`.
Los tres últimos van sueltos y **no** entran en el bundle, a propósito.

Las 25 ADR son la 0001 a la 0025, **sin huecos de numeración**: los números
0020, 0021, 0022 y 0023, que estaban libres, se ocuparon al cerrar la sesión con
las cuatro decisiones de diseño del día (§4). Comprobado listando los ficheros y
buscando los números que faltasen entre el 1 y el 25: ninguno.

El aplicativo son **38 ficheros PHP** bajo `src/Evt/` y **35 ficheros de test**
bajo `tests/unit/`.

### Dos avisos honestos sobre estas cifras

**El suelo de tests es 310, y ese número es el de una pasada limpia.**
La primera pasada de `make test` de esta medición falló con 7 errores y 1 fallo,
y `make coverage` falló con 1 fallo. Ninguno de los dos era un fallo real del
código:

- Los 7 errores eran `Table 'tests-wordpress.wp_users' doesn't exist` y
  `Deadlock found when trying to get lock`: varias sesiones estaban usando el
  mismo contenedor de tests a la vez y se pisaban la base de datos.
- El fallo de `make coverage` era `Test_Adr_Registry`: `registro.md` ya citaba
  la ADR-0018 mientras el fichero se estaba escribiendo. En cuanto el fichero
  existió, dejó de fallar.

Conviene saberlo porque **el número de aserciones cambia entre pasadas**: en
esta medición se vieron 2.197, 2.216, 2.232, 2.343 y 2.345. Baja cuando una
pasada falla a medias, y sube porque durante toda la sesión hubo trabajo
añadiendo aserciones a tests que ya existían.

**El suelo subió de 306 a 310 en la propia revisión de cierre**, y conviene
decir por qué para que nadie busque un cambio de código que no hay: al repasar
el cierre se vio que dos cosas que se acababan de arreglar no las guardaba
ninguna prueba —que la numeración de las ADR no tenga huecos, y que el
`.gitignore` siga ignorando el lienzo sembrado sin llevarse por delante las
fuentes—, así que se añadieron cuatro tests que fallan si eso se rompe:
`Test_Adr_Registry::test_the_adr_numbering_has_no_gaps()` y los tres de
`tests/unit/test-design-canvas.php`. **310 es el suelo a partir de ahora**, y
`make test` y `make coverage` dieron la misma cifra en la última pasada.

**Si al retomar `make test` falla con errores de base de datos, no es el código:
es que hay otra cosa usando el contenedor de tests.** Se comprueba repitiendo la
pasada con el entorno para uno solo.

**La cobertura de clases (41,67 %) es mucho más baja que la de líneas
(91,24 %), y no es una contradicción**: PHPUnit solo cuenta una clase como
cubierta si están cubiertas **todas** sus líneas y **todos** sus métodos. Media
docena de vistas están al 95-98 % de líneas y cuentan como no cubiertas por un
puñado de ramas de presentación. El número que dice algo aquí es el de líneas.

---

## 3. Qué está encolado y en qué orden

**Las cuatro pestañas están hechas el 2026-09-14**, pero no enteras: lo que se
construyó es el cuerpo de cada una, y de las dos últimas queda fuera lo que
depende de traerse las inscripciones, que es fase 2. Aquí está separado, porque
dar por hecho lo que no está es la forma de que no se haga.

### Hecho

| Pestaña | Qué lleva hoy | Dónde |
|---|---|---|
| **Ponentes** | Alta, edición, orden, foto y papelera. Cuelgan del evento por `post_parent`, así que heredan su área y su cierre | [ADR-0026](../adr/ADR-0026-ponentes-y-actividades-cuelgan-del-evento.md) |
| **Programa** | La parrilla **por día y, dentro, por sede**; nueve tipos de actividad en lista cerrada; ponentes por casillas, solo los del evento; sedes derivadas, con propuesta de las ya usadas | [ADR-0024](../adr/ADR-0024-un-dia-puede-tener-dos-sedes.md), ADR-0026 |
| **Talleres** | Vista sobre el programa de las actividades de tipo «taller», con aforo, plazas ocupadas y libres, y sus tres cifras | ADR-0026 |
| **Participantes** | Tabla, **filtro que no distingue tildes** y **exportación a CSV de lo filtrado**, sobre el enganche `evt_participants` | [ADR-0027](../adr/ADR-0027-los-participantes-son-nuestros.md) |

Y **la botonera**, que era el puesto 2 de la cola: botones de icono con
bocadillo —lápiz, ojo, flechas y papelera— en el listado de eventos y en las
páginas de un evento, y el estado de publicación como **interruptor**, que
además se puede cambiar desde la pantalla donde se ve; antes había que ir al
escritorio de WordPress. Un borrador también lleva el ojo, con enlace de
previsualización
([ADR-0029](../adr/ADR-0029-botones-de-icono-e-interruptor-de-publicacion.md)).

Y un hueco que no tenía ADR y se veía a la primera: **«Crear evento» no creaba
nada**. El botón del listado llevaba al taller sin evento y el taller contestaba
«elija un evento en la lista». Ahora el taller sin `?evento=` es la pantalla de
alta, el evento nace en borrador y al guardarlo se abre su taller.

### Lo que queda, y por qué no está

De la lista se cayó **«copiar ponentes de otro evento»**: era lo único que la
[ADR-0021](../adr/ADR-0021-el-ponente-pertenece-a-su-evento.md) había prometido
sin escribir, y al concretarlo se descartó
([ADR-0028](../adr/ADR-0028-no-se-copian-ponentes.md)). No está pendiente: está
decidido que no.

| # | Trabajo | Qué falta | Por qué va ahí |
|---|---|---|---|
| 1 | **Repositorio público + cliente npm** | Publicar el snippet con `@erseco/code-snippets-client` (`npm run snippets -- …`) en vez de depender de un checkout hermano con entorno Python; sacar del repositorio el nombre del sitio, del subsitio y la ruta de snippets, que pasan al `.env`; `.env.dist` con las claves **vacías**; una comprobación sin red en `make check`; `LICENSE`, `README`, `CONTRIBUTING` y `SECURITY.md` | Va primero porque **condiciona a todo lo demás**: cada documento que se escriba después habrá que revisarlo otra vez con el ojo de «esto va a ser público» |
| 2 | **Columna «Evento» en el escritorio** | `evt_speaker` y `evt_activity` no son jerárquicos, así que en `wp-admin` una ficha no dice de qué evento es. Es una columna en `EventAdmin` | Lo destapa la ADR-0026 y es la consecuencia negativa que dejó anotada |
| 3 | **Solapes en el programa** | `Domain/Schedule.php` puro: detectar **dos actividades a la vez en la misma sala**, y calcular duraciones. Hoy la parrilla ordena y agrupa, pero no avisa de un choque | Es una mejora sobre algo que ya funciona, no un requisito para que funcione |
| 4 | **El snippet que lee las inscripciones** | Lo que conteste al enganche `evt_participants` en producción. Sin él, la pestaña no enseña ninguna inscripción fuera del wp-env | Es la mitad que falta de la ADR-0027, y es trabajo de quien tenga delante el gestor de formularios donde viven las inscripciones |
| 5 | **Traerse las inscripciones (fase 2)** | CPT propio colgando de su evento; entrada por CAS —el centro se resuelve del catálogo por código, nunca del nombre— o inscripción manual **sin crear usuario de WordPress**; antibots con Altcha verificado en nuestro servidor; consentimiento con constancia; **aforo por turno con la carrera al contar resuelta**, ventana de selección y lista de espera | Es la más grande y la que más decisiones ajenas arrastra (protección de datos, CAS, catálogo de centros). **Sustituye a la ADR-0007 y a la ADR-0027**, no las enmienda |

---

## 4. Las decisiones de diseño del 2026-09-13

El día se dedicó a diseñar la interfaz de gestión **sobre la apariencia real del
aplicativo**: los colores, los botones y la tipografía del lienzo salieron de
`assets/css/evt-app.css`, no se inventaron. El resultado está en el lienzo
publicado (§1) y en ocho pantallas: crear el evento, el taller del evento,
añadir una página, ponentes, programa, talleres y aforo, participantes, y el
consentimiento.

Las decisiones se registraron como ADR, que es donde hay que leerlas:

| ADR | Decisión | En una línea |
|---|---|---|
| [ADR-0018](../adr/ADR-0018-el-taller-del-evento-es-una-sola-pantalla.md) | El taller del evento es una sola pantalla | Columna de páginas a la izquierda (ordenable arrastrando), la página elegida y sus elementos a la derecha, cada uno con su interruptor. Las pestañas de arriba quedan para lo que de verdad son cosas distintas |
| [ADR-0019](../adr/ADR-0019-el-tipo-de-pagina-se-elige-al-crear.md) | El tipo de una página se elige al crear y no se cambia | Cada tipo trae sus elementos preparados; cambiarlo dejaría elementos huérfanos. La salida si alguien se equivoca es la papelera y crear la buena |
| [ADR-0020](../adr/ADR-0020-el-consentimiento-es-una-casilla.md) | El consentimiento es una casilla | Cambio de procedimiento: se lee, se marca «Acepto» y vale. Lo que **no** se simplifica es guardar qué se aceptó y cuándo |
| [ADR-0021](../adr/ADR-0021-el-ponente-pertenece-a-su-evento.md) | El ponente pertenece a su evento | Entre el 88 % y el 95 % de los ponentes salen en un solo evento (medido con los ficheros de foto). Compartir es diseñar para el caso raro |

El índice único de estados de todas ellas está en
[`docs/adr/registro.md`](../adr/registro.md).

El lienzo lleva además cinco notas al margen que explican el porqué de cada
pantalla; están en `.design/canvas.json` y se leen sin abrir el lienzo.

---

## 5. Las preguntas abiertas — CERRADAS el 2026-09-14

Este apartado listaba cuatro preguntas. **Ya están contestadas todas**, así que
lo que decía debajo está obsoleto y se ha retirado en vez de dejarlo engañando.
No se busque aquí el detalle: está en las ADR.

| Era | Cómo se cerró |
|---|---|
| **5.1 Las sedes** | Un día **sí puede tener dos**. La sede es un dato de la actividad y la parrilla agrupa por día y, dentro, por sede → [ADR-0024](../adr/ADR-0024-un-dia-puede-tener-dos-sedes.md) |
| **5.2 El histórico** | La asimetría —el área marca, la administración desmarca— ya estaba construida y probada; se confirmó tal cual |
| **5.3 El orden del trabajo** | Se implementó primero lo que toca código ya escrito ([ADR-0019](../adr/ADR-0019-el-tipo-de-pagina-se-elige-al-crear.md)); las cuatro pestañas siguen encoladas |
| **5.4 `unfiltered_html`** | **No era lo que se dijo.** Comprobado ejecutándolo: no alcanza a quien no tiene la capacidad primitiva en su rol, así que ni de lejos llega a los suscriptores. **Deja de bloquear la publicación del repositorio** |

Y una pregunta nueva que salió y se cerró el mismo día: **duplicar un evento**.
No se hace → [ADR-0025](../adr/ADR-0025-no-se-duplican-eventos.md).

## 6. La deuda documental — SALDADA el 2026-09-14

La pasada de consolidación estaba acordada para antes del primer commit. **Está
hecha.** Lo que sigue es lo que se hizo, para que nadie la repita ni la busque.

Seis ADR acumulaban una **adenda fechada** y una **nota de revisión de estado**
cada una —ADR-0006, ADR-0009, ADR-0012, ADR-0013, ADR-0014 y
ADR-0017—, de modo que la decisión vigente se leía en **tres sitios distintos**
del mismo fichero y había que leer los tres para saber qué estaba decidido hoy.
Ese, y no el número de adendas, era el motivo de consolidar.

Qué se hizo, ADR por ADR:

| ADR | Qué decía en tres sitios | Cómo quedó |
|---|---|---|
| [ADR-0006](../adr/ADR-0006-el-area-es-un-ambito-no-un-rol.md) | Dos roles propios; `evt_coordinator` retirado en la adenda | Un rol propio en el cuerpo, el rol intermedio como **opción 5 descartada** con sus cuatro motivos, y la retirada del rol huérfano documentada como lo que es: código en producción |
| [ADR-0009](../adr/ADR-0009-identificadores-internos-en-ingles.md) | Un ejemplo con un rol que ya no existe | Ejemplo corregido, y la regla que ilustraba —«un identificador es una dirección, no un resumen»— escrita como sección propia, con su raya en «publicado» |
| [ADR-0012](../adr/ADR-0012-politica-de-edicion-y-auditoria.md) | Decidía la opción 3 y la adenda la retiraba | Decide la **opción 1**, y la 3 queda descartada con el motivo entero: el corte caía justo donde empieza el trabajo |
| [ADR-0013](../adr/ADR-0013-el-editor-de-codigo-es-el-de-wordpress.md) | Descartaba CodeJar por el CDN, y la adenda decía que ese motivo era falso | El CDN deja de ser argumento: la opción 2 dice que cargarla así sería aceptable y la descartan los comprobadores y el `contenteditable` |
| [ADR-0014](../adr/ADR-0014-css-del-area-javascript-de-administracion.md) | Título y cuerpo decían «solo administración» para los dos campos | Título, fichero y cuerpo al día: **el CSS es del área**; la tabla del riesgo, que era el argumento entero, sube a los factores de decisión |
| [ADR-0017](../adr/ADR-0017-el-estado-historico-cierra-la-edicion.md) | Marcaba administración; dos adendas lo movían al área | La asimetría en el cuerpo, y el «Comportamiento medido» **vuelto a medir** con las dos cuentas de hoy, incluida la comparación byte a byte de la página pública |

Y una ADR menos: la de **preguntas abiertas** no decidía nada y quedó contestada
al día siguiente. Se retiró; sus alternativas descartadas ya vivían dentro de
las dos ADR que la cerraron, que pasaron a ser la ADR-0024 y la ADR-0025. La
numeración quedó **sin huecos y sin ninguna `Sustituida`**.

**Y las citas al código dejaron de llevar número de línea.** Se comprobaron una
a una contra el código y **habían derivado**: apuntaban a llaves de cierre, a
líneas de docblock y, en varios casos, al símbolo equivocado. Una cita que
miente es peor que ninguna, así que el número se retiró de las **215** citas a
ficheros de este repositorio y en su lugar quedan **fichero y símbolo**
—`EventAccess::can_open()`, `EventMetaKeys::section_types()`, el target
`bundle` del `Makefile`—, que no caducan al insertar una línea. **Las citas a
material externo conservan la línea**: los fragmentos de código de producción,
el núcleo de WordPress y el material de investigación de `.local/` son fotos
congeladas, y ahí la línea es la prueba.

**Por qué esto se puede hacer y no vuelve a poder hacerse.** Las ADR aceptadas
son *append-only* desde el momento en que se publican, y nada de esto se ha
publicado: no hay ni un commit. Mientras no lo haya, ningún identificador es
todavía la dirección de nadie y la corrección va en el propio texto —igual que
la revisión documental del 2026-09-12, que corrigió cuatro cifras en su sitio—.
**Después del primer commit, los números y el texto se congelan** y toda
corrección vuelve a ser una adenda o una ADR que sustituya. Queda escrito en
[`AGENTS.md`](../../AGENTS.md) y en la
[ADR-0009](../adr/ADR-0009-identificadores-internos-en-ingles.md).

---

## 7. Cómo retomar

**No hay ni un commit todavía.** `git rev-list --all --count` devuelve `0` y
`git log` responde literalmente «your current branch 'main' does not have any
commits yet». Es **a propósito**: mientras no haya historia se puede reescribir
cualquier cosa sin arqueología ni migraciones de documentación. La pasada de consolidación del §6 **ya está hecha**; el primer commit se hace
cuando la revisión de exposición del §3 (puesto 1) haya terminado.

Que no haya commits tiene una consecuencia práctica: **todo lo que hay en el
directorio de trabajo es lo único que hay.** No hay nada que recuperar de una
rama ni de un *stash*.

### Qué leer, y en este orden

1. **`README.md`** del repositorio y **`docs/desarrollo-para-empezar.md`** —
   qué es esto y cómo se levanta.
2. **[`docs/adr/registro.md`](../adr/registro.md)** — el índice único de
   estados de las ADR. Las cabeceras de ampliación explican por qué se escribió
   cada tanda y en qué orden se leen.
3. **[`docs/sdd/SDD-0002-arquitectura-de-reemplazo.md`](../sdd/SDD-0002-arquitectura-de-reemplazo.md)**
   — qué queda por hacer. El plan dice en qué orden; el SDD dice qué.
4. **Este documento, apartado 5** — las preguntas abiertas. Si alguna se ha
   respondido mientras tanto, la respuesta va a su ADR, no aquí.
5. **[ADR-0018](../adr/ADR-0018-el-taller-del-evento-es-una-sola-pantalla.md) y
   [ADR-0019](../adr/ADR-0019-el-tipo-de-pagina-se-elige-al-crear.md)** y el
   lienzo publicado (§1) — la forma que va a tener la pantalla.
6. **[`docs/requisitos/REQ-0001-aplicativo-de-eventos.md`](../requisitos/REQ-0001-aplicativo-de-eventos.md)**
   — solo si hace falta el detalle funcional.

### Qué comprobar antes de escribir la primera línea

```
make install   # composer install + npm install — LO PRIMERO: sin esto no hay
               # ./vendor/bin/phpcs ni ./vendor/bin/phpunit y todo lo demás falla
make up        # levanta wp-env en http://localhost:8798 (hace falta Docker en marcha)
make test      # tiene que dar 310 tests en verde; ese es el suelo
make check     # lint + phpmd + provisión + tests
```

**Y una trampa que no avisa sola:** `snippets/evt-eventos-app.bundle.php` es el
aplicativo entero empaquetado por `build/pack-snippet.php`, y se genera; **no se
edita a mano**. Ningún test comprueba que esté al día, así que después de tocar
cualquier cosa de `src/Evt/` hay que ejecutar `make bundle` antes de dar el
trabajo por cerrado, o lo que se despliegue será el código de ayer. Si se añade
un fichero nuevo a `src/Evt/`, además hay que darlo de alta en
`src/Evt/load-order.php`, que es la lista única que leen el arranque y el
empaquetador.

Si `make test` falla con `Table ... doesn't exist` o con `Deadlock`, **no es el
código**: es que algo más está usando el contenedor de tests (§2). Repetir la
pasada en solitario.

El entorno trae cuentas de demostración: `admin`, `coordinacion`,
`organizacion`, `organizacion2` y `organizacion3`, todas con contraseña
`password`. Los roles y quién puede qué están en
[`docs/roles-y-permisos.md`](../roles-y-permisos.md).

**Y un aviso sobre `coordinacion`, que confunde si nadie lo dice:** el rol
`evt_coordinator` **se retiró el 2026-09-13**
([ADR-0006](../adr/ADR-0006-el-area-es-un-ambito-no-un-rol.md), opción 5). Hoy solo hay dos
niveles: `evt_organiser`, que hace todo lo de **su área**, y el `administrator`
nativo, que hace todo en todas. La cuenta `coordinacion` conserva el nombre del
rol retirado pero **es un `evt_organiser` más**; se comprueba con
`wp user list --fields=user_login,roles`. Si algo del código, de un documento o
de un contrato habla de «coordinación» como un nivel intermedio, está hablando
de antes de esa fecha.

`organizacion` y `organizacion2` comparten área a propósito, y `organizacion3`
está en otra: es lo que deja probar el acotado por área sin montar nada.

### Y la regla que sostiene todo lo demás

Cada trabajo deja **más tests que los que se encontró y ninguno menos**, y
`./vendor/bin/phpcs` sin un solo error. Es lo que ha permitido llegar hasta
aquí con 310 tests y 91 % de líneas cubiertas sin haber hecho un solo commit.
