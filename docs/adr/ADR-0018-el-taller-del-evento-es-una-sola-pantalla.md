---
id: ADR-0018
title: "El taller del evento es una sola pantalla: las páginas a la izquierda, sus elementos a la derecha"
status: Aceptada
date: 2026-09-13
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0003, ADR-0010, ADR-0014, ADR-0016, ADR-0019, ADR-0022, ADR-0023]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0018: El taller del evento es una sola pantalla: las páginas a la izquierda, sus elementos a la derecha

## Estado

Aceptada (2026-09-13).

**De dónde sale esta decisión.** No es una deducción del equipo de desarrollo
a partir del código. Sale de una **sesión de diseño con la persona usuaria**
celebrada el 2026-09-13, en la que se dibujó la gestión de eventos sobre la
apariencia real del aplicativo y se fue corrigiendo pantalla a pantalla con
ella delante. El resultado está publicado como lienzo —su URL es privada y vive en
`.local/lienzo-de-diseno.md`— y sus fuentes se versionan en `.design/*.dc.html` con su `.design/canvas.json`. Lo
que sigue documenta lo que se decidió allí, no lo que nos parecía razonable
antes de enseñarlo.

## Contexto

El aplicativo ya tiene pantallas propias en el frontal
(`src/Evt/PublicFront/`), y una de ellas es el taller de un evento,
`EventWorkspace`. Hoy ese taller son **cuatro pestañas internas planas**,
seleccionadas con un parámetro de la URL:

| Pestaña | Slug | Qué enseña | Fuente |
|---|---|---|---|
| Secciones | `?panel=secciones` | Tabla de las páginas satélite, con orden, tipo, título, slug, estado y acciones | `src/Evt/PublicFront/EventWorkspace.php`, `src/Evt/PublicFront/View/EventSectionsPanel.php` |
| Datos | `?panel=ajustes` | Identidad, fechas, sedes, clasificación, inscripción | `EventWorkspace.php`, `View/EventDataPanel.php` |
| Apariencia | `?panel=apariencia` | Colores, tipografías, imágenes de la cabecera | `EventWorkspace.php`, `View/EventAppearancePanel.php` |
| Código | `?panel=codigo` | CSS a medida del área; JavaScript solo de administración ([ADR-0014](ADR-0014-css-del-area-javascript-de-administracion.md)) | `EventWorkspace.php`, `View/EventCodePanel.php` |

Eso ya es mucho mejor que lo que hay en el sistema que se sustituye. Medido
sobre él el 2026-09-13 —el material de la medición está en `.local/`, que no se
versiona—, editar **una sola página** de un evento significa:

| | |
|---|---|
| Secciones del formulario | 15 |
| Campos | 139 (68 visibles a la vez) |
| Controles de formulario | 374 |
| Altura del formulario | 5.671 px de scroll |

Y es el **mismo** formulario para la portada del evento y para cada página
satélite: qué campos aplican depende de un desplegable «Tipo de página» que
está arriba del todo. La navegación completa del aplicativo de hoy son dos
entradas —`Inicio` y `Administrar`— y, dentro de un evento, un engranaje con
tres enlaces de alta y un botón «Editar página» que reabre ese formulario.
No existe ninguna pantalla que liste las páginas de un evento, las reordene o
las publique.

Con eso delante, la sesión de diseño encontró dos cosas que las pestañas
planas no resuelven:

1. **No se aprecia que se está dentro de un evento.** Cuatro pestañas
   horizontales al mismo nivel obligan a recordar dónde estabas y de qué
   evento son. El contexto —qué evento, qué página— vive en la memoria de
   quien lo usa, no en la pantalla.
2. **No hay ningún sitio donde se vea qué lleva una página dentro.** La tabla
   de secciones dice que existe una página «Programa», su tipo y su estado.
   No dice que esa página lleva una parrilla, un texto de presentación, un
   PDF, un botón de inscripción y un aviso destacado, ni cuáles de esos
   elementos están puestos y cuáles no. Para saberlo hay que abrir el
   formulario de la página y bajar.

Quien organiza una jornada no piensa en «paneles» ni en «metadatos»: piensa
en «la página del programa lleva la parrilla y el PDF, y todavía no le he
puesto el texto de arriba».

## Problema

¿Cómo se organiza la pantalla de gestión de un evento para que, en todo
momento, se vea **qué evento** se está tocando, **qué página** de ese evento y
**qué lleva esa página dentro**, sin que haya que recordar nada ni abrir un
formulario para averiguarlo?

## Factores de decisión

- **Contexto permanente**: el evento y la página elegida tienen que estar a la
  vista, no recordados.
- **Una idea por pantalla**: quien organiza una jornada no distingue «panel de
  datos» de «panel de apariencia»; distingue «el evento» de «una de sus
  páginas».
- **Reordenar tiene que ser barato**: hoy reordenar es editar cada página
  entera y cambiarle un número de orden.
- **Menos campos a la vez**: la referencia a batir son 68 campos visibles
  simultáneamente.
- **Lo que se ve tiene que corresponderse con lo que se publica**: los
  elementos de una página son lo que pinta `EventView`
  ([ADR-0010](ADR-0010-la-pagina-la-genera-codigo-versionado.md),
  [ADR-0022](ADR-0022-la-pagina-de-evento-se-pinta-entera.md)).
- **Acotado por área**: nada de lo anterior puede saltarse `EventAccess`; quien
  no puede editar el evento no ve ni toca sus páginas.
- **Sin novedades de aspecto**: los colores, los botones y la tipografía son
  los que ya tiene el aplicativo en `assets/css/evt-app.css` (`--evt-pri:
  #1b4f8a`, botones píldora de 44 px de alto, radio 12). El diseño reutiliza
  esos tokens literalmente; no inventa un lenguaje visual nuevo.

## Alternativas consideradas

### Opción 1: seguir con las pestañas internas planas de hoy

Se conserva `?panel=secciones|datos|apariencia|codigo` y se mejoran las tablas
por dentro.

| Pros | Contras |
|------|---------|
| Ya está escrito, probado y verde. | Obliga a recordar en qué evento y en qué página se estaba: el contexto no está en pantalla. |
| El patrón de tabla con acciones ya existe y se reutiliza. | Sigue sin haber ningún sitio donde se vea qué elementos lleva una página. |
| Cada panel es una pantalla independiente y testable. | «Datos» y «Apariencia» son del evento, «Secciones» es de sus hijas: cuatro pestañas al mismo nivel mezclan dos cosas distintas. |
| | Reordenar sigue siendo editar cada página. |

**Encaja si** el aplicativo se queda en la fase 1 y nadie más que el equipo lo
usa. Descartada en la sesión de diseño: fue exactamente lo que la persona
usuaria señaló como confuso.

### Opción 2: una barra lateral global con el árbol de todos los eventos

Menú fijo a la izquierda con todos los eventos del ámbito, desplegando sus
páginas.

| Pros | Contras |
|------|---------|
| Se salta de un evento a otro sin volver al listado. | El evento concreto se pierde entre los demás: el problema que se quería resolver, agravado. |
| Patrón conocido de los gestores de contenido. | Un árbol de dos niveles por N eventos es largo, y con área de varios términos puede tener decenas de ramas. |
| | Contradice la forma que se elige para el taller: pestañas arriba, sin barra lateral. |

Descartada.

### Opción 3: cada página en su propia pantalla completa

Se mantiene la tabla de secciones y editar una página abre una pantalla
entera, como hoy hace `PageForm`.

| Pros | Contras |
|------|---------|
| Sitio de sobra para los campos de la página. | Es el modelo del formulario del sistema anterior: ir y volver, y perder de vista el evento en cada viaje. |
| Ya está implementado. | Los elementos de la página siguen sin verse hasta abrirla. |
| | Reordenar obliga a salir, y saber qué falta obliga a abrir cada página una por una. |

Descartada como pantalla principal. `PageForm` **no desaparece**: sigue siendo
donde se escribe el contenido de un elemento.

### Opción 4: una sola pantalla con la columna de páginas a la izquierda y sus elementos a la derecha (elegida)

Es lo que se diseñó y se validó en la sesión, y está dibujado en
`.design/Main.dc.html` («2 · El taller del evento», 1.260 × 1.180 px en
`canvas.json`).

| Pros | Contras |
|------|---------|
| El evento, la página elegida y sus elementos están a la vista a la vez. | Necesita anchura: la rejilla es `296px 1fr`, y por debajo de ~1.000 px hay que apilar. |
| Reordenar es arrastrar en la columna, sin abrir nada. | Arrastrar necesita JavaScript, y hace falta un camino sin JavaScript en paralelo. |
| Cada elemento enseña si está puesto y qué tiene dentro, sin abrir la página. | Cada elemento nuevo es un interruptor más y una clave de meta más. |
| Las pestañas de arriba quedan para lo que de verdad son cosas distintas. | «Datos», «Apariencia» y «Código» se van a «Ajustes», que se convierte en el cajón de lo que no cabe en otro sitio. |

## Decisión

**Haremos la opción 4.** El taller de un evento es **una sola pantalla**, con
esta forma:

**Arriba, la cabecera del evento.** Migas de pan «Mis eventos › III Jornadas
de Tecnología Educativa», el nombre del evento como título, y debajo, en una
línea, lo que lo identifica: «Del 28 al 30 de octubre de 2026 · Centro de
formación Norte · Área de Innovación». A la derecha, el estado de
publicación y «Ver la web». Nunca hay duda de qué evento se está tocando.

**Debajo, las pestañas de arriba**, que ahora separan cosas que de verdad son
distintas, cada una con su recuento: **Páginas · Ponentes · Programa ·
Talleres · Participantes · Ajustes**. No son cuatro vistas del mismo objeto:
son los cinco cuerpos de trabajo de una jornada, más los ajustes.

**A la izquierda, la columna de páginas del evento** (296 px). Una fila por
página, con su icono, su nombre, su estado —«Publicada», «Borrador»,
«Publicada · abierta»— y un asa de arrastre para **reordenar arrastrando**. La
página elegida se marca con fondo y una barra azul a la izquierda. Al pie de
la columna, «Añadir una página», con el recordatorio de que **se elige el tipo
al crearla** ([ADR-0019](ADR-0019-el-tipo-de-pagina-se-elige-al-crear.md)).

**A la derecha, la página elegida y sus elementos.** Una barra con el título
de la página, su dirección, su tipo y su estado; y debajo, una fila por
elemento, **cada uno con su interruptor**, su nombre y una línea en castellano
llano que dice qué es y qué tiene dentro. Para una página de programa:

| Elemento | Lo que se lee debajo |
|---|---|
| Parrilla del programa | «14 actividades en 2 días, con sus ponentes y su sala» |
| Texto de presentación | «Un párrafo antes de la parrilla» |
| Programa en PDF | «programa-jornadas-2026.pdf · 1,2 MB» |
| Botón de inscripción | «Repite aquí la llamada a inscribirse» |
| Aviso destacado | «Una franja de color sobre el contenido» |

Los que están puestos van con el interruptor en verde y un botón «Editar» o
«Cambiar»; los que no, en gris y sin más. **Se ve de un vistazo qué lleva la
página y qué le falta.**

**Abajo, la salida.** Un bloque aparte, «Enviar esta página a la papelera»,
con su explicación: «Deja de verse en el evento. No se pierde nada: se puede
restaurar». Es [ADR-0016](ADR-0016-borrar-es-enviar-a-la-papelera.md) dicha en
la pantalla, y la confirmación va con SweetAlert2.

Reglas que no cambian por esto:

1. Toda mutación —reordenar incluido— va por **POST con nonce** y con
   comprobación de `EventAccess`. Nunca en GET.
2. El acotado por área manda: la columna de páginas se construye desde el
   evento, y quien no puede editar el evento no ve la pantalla.
3. Toda la salida escapada; nada de `unfiltered_html`.
4. El bloqueo de edición sigue siendo el nativo de WordPress
   ([ADR-0023](ADR-0023-bloqueo-de-edicion-con-el-de-wordpress.md)) y alcanza a
   esta pantalla igual que a las de hoy.

## Consecuencias

### Positivas

- El contexto deja de estar en la memoria de quien trabaja: qué evento y qué
  página están escritos en la pantalla mientras se trabaja.
- Saber qué le falta a una página deja de ser abrir la página: se lee en la
  lista de elementos.
- Reordenar pasa de ser N ediciones completas a arrastrar una fila.
- Las pestañas de arriba pasan a significar algo: son las cinco cosas
  distintas que tiene una jornada, no cuatro vistas del mismo formulario.
- La cifra a batir —68 campos visibles a la vez— deja de tener sentido: en
  esta pantalla no hay campos, hay elementos con un interruptor.

### Negativas

- **La pantalla necesita anchura.** La rejilla del diseño es `296px 1fr` sobre
  un contenedor de 1.128 px. Por debajo de unos 1.000 px hay que apilar la
  columna sobre el panel, y entonces **se pierde justo lo que la decisión
  aportaba**: ver a la vez la lista de páginas y la elegida. El apilado no
  está diseñado. Está pendiente y hay que dibujarlo antes de escribirlo.
- **Arrastrar obliga a mantener dos mecanismos para lo mismo.** El arrastre es
  JavaScript; sin JavaScript tiene que seguir habiendo «subir» y «bajar» por
  POST, como hoy. Son dos caminos que hacen lo mismo, y los dos hay que
  probarlos.
- **«Ajustes» se convierte en un cajón de sastre.** Los paneles «Datos»,
  «Apariencia» y «Código» de hoy no desaparecen: se mudan ahí. Esa pestaña va
  a acumular lo que no cabe en ninguna otra, y nada en esta decisión impide que
  crezca hasta parecerse al formulario que se quiere jubilar.
- **Cada elemento nuevo es meta nueva.** Un interruptor por elemento es una
  clave de meta por elemento, con su registro, su saneado y su
  `auth_callback`. Cinco elementos hoy; nada garantiza que dentro de dos años
  no sean quince, y entonces la lista de elementos será el scroll que se
  quería quitar.
- **La pantalla central carga más.** Necesita el evento, sus páginas, el estado
  de cada una y los recuentos de las cinco pestañas —ponentes, actividades,
  talleres, participantes— en cada carga. Son más consultas que las de un panel
  de hoy, y no está medido cuántas.
- **Nada de esto está implementado.** Lo que hay es una maqueta HTML. Las
  cifras que aparecen en ella —6 páginas, 8 ponentes, 14 actividades, 182
  participantes— son de ejemplo, puestas para que el diseño se pueda leer. **No
  son medidas de producción** y no deben citarse como tales.

### Neutras

- El diseño reutiliza los tokens que ya están en `assets/css/evt-app.css`
  (`--evt-pri: #1b4f8a`, `--evt-ok`, `--evt-mal`, el amarillo de «Solo
  administración» y sus contrastes, radio 12, botones de 44 px). No hay
  lenguaje visual nuevo que aprender ni que mantener.
- `PageForm` sigue existiendo: editar un elemento sigue siendo una pantalla
  con su formulario. Lo que cambia es que se llega desde el elemento y no desde
  una tabla.
- Esta ADR decide la **forma** de la pantalla. Qué elementos lleva cada tipo de
  página lo decide
  [ADR-0019](ADR-0019-el-tipo-de-pagina-se-elige-al-crear.md); qué se publica,
  [ADR-0022](ADR-0022-la-pagina-de-evento-se-pinta-entera.md).

## Referencias

- **Lienzo del diseño**: su URL, que es privada, está en `.local/lienzo-de-diseno.md`.
- Fuentes del diseño: `.design/Main.dc.html` (artboard «2 · El taller del
  evento») y `.design/canvas.json`.
- [ADR-0003](ADR-0003-cpt-jerarquico-frente-a-paginas-generadas.md) — el
  evento es un CPT jerárquico y sus páginas son sus hijas.
- [ADR-0016](ADR-0016-borrar-es-enviar-a-la-papelera.md) — borrar es enviar a
  la papelera.
- [ADR-0019](ADR-0019-el-tipo-de-pagina-se-elige-al-crear.md) — el tipo de una
  página se elige al crear y no se cambia.
- [ADR-0022](ADR-0022-la-pagina-de-evento-se-pinta-entera.md) — cómo se ve el
  evento por fuera.
- [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) — la arquitectura de
  reemplazo.
