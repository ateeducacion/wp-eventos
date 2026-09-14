---
id: ADR-0007
title: "Las inscripciones siguen en el sistema anterior en la fase 1"
status: Sustituida
date: 2026-09-12
related:
  issues: []
  prs: []
  sdds: [SDD-0001, SDD-0002]
  adrs: [ADR-0003, ADR-0008, ADR-0010]
supersedes: []
superseded_by: [ADR-0032]
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0007: Las inscripciones siguen en el sistema anterior en la fase 1

## Estado

**Sustituida** el 2026-09-14 por la
[ADR-0032](ADR-0032-la-inscripcion-es-un-contenido-del-evento.md), que lleva las
inscripciones a este aplicativo. Se conserva porque su contexto —cómo funcionan
hoy las inscripciones del sistema anterior, y por qué se decidió no tocarlas de
entrada— es la evidencia sobre la que se tomaron la ADR-0027, la ADR-0031 y la
propia ADR-0032. Lo que ya **no** vale es su decisión.

## Contexto

El aplicativo sustituye el alta y la publicación de eventos
([ADR-0003](ADR-0003-cpt-jerarquico-frente-a-paginas-generadas.md)). La
pregunta inmediata es hasta dónde llega: en el mismo gestor de formularios del
sistema anterior viven también las inscripciones, y son la mitad del sitio.

El inventario está medido sobre la foto del 2026-09-12 y el material está en
`.local/` (investigación del sistema anterior). Lo que dice, en corto:

- La mayoría de los formularios del sitio **son la inscripción o la selección
  de talleres de un evento concreto**, y cada uno arrastra una o dos vistas de
  gestión propias.
- En ellos viven las inscripciones vivas, con nombre, correo, centro y
  consentimiento firmado.
- El control de aforo por taller no es un mecanismo: es el mismo algoritmo
  copiado media docena de veces, una copia por evento con talleres.
- La gestión de entradas la hace un rol propio, con las capacidades del gestor
  de formularios.

El patrón es **un formulario nuevo por evento**. Cuando llega un evento, se
duplica el formulario del evento anterior; un fragmento de código pegado a
mano engancha el duplicado y duplica también sus vistas, renombra el slug,
reapunta el identificador del formulario y **reescribe los IDs de campo
emparejándolos por nombre**. Es la pieza que hace viable el modelo, y también
la que lo perpetúa.

Si el evento tiene talleres con aforo, se copia además otra cosa: un fragmento
de control de aforo nuevo con las constantes del formulario recién duplicado
—el identificador del formulario, los IDs de los campos de la matriz (uno por
sede o franja) y el aforo—. De todas las copias que existen, hoy solo está
activa la del último evento con talleres.

Y hay una cosa que no se puede mover a la ligera: entre los adjuntos del sitio
hay ficheros servidos desde una ruta protegida, con el nombre en base64, que
son **los consentimientos informados firmados** que sube cada persona
inscrita. La exportación del sistema anterior del 2026-09-12 se pidió
deliberadamente **sin registros** por esa razón.

Existe un intento previo de racionalizar esto dentro del propio sistema
anterior: una plantilla de formulario de inscripción, creada, rotulada «en
construcción» y nunca terminada, **sin una sola entrada**.

## Problema

¿La fase 1 sustituye también las inscripciones y la selección de talleres, o
las deja donde están?

## Factores de decisión

- **Qué duele hoy.** Lo que se ha descrito como problema es el **alta y la
  publicación del evento**: un formulario de más de cien campos, una plantilla
  de 42 KB dentro de un campo de texto y una jerarquía que depende de un
  fragmento de siete líneas, pegado a mano, que lee `$_POST` sin comprobar
  nada. La inscripción, con todos sus defectos, **funciona**: se rellena,
  valida el aforo y exporta a CSV.
- **Volumen y naturaleza del dato.** Los registros vivos llevan nombre,
  correo, centro y consentimientos firmados. El recuento está medido y vive en
  `.local/`; lo que importa aquí es que no son cuatro.
- **Protección de datos.** Mover esos registros a otro almacén es un
  tratamiento nuevo, con su análisis, su base jurídica y su plazo de
  conservación. No es una tarea de una fase de arquitectura.
- **Superficie.** Los formularios de inscripción, sus vistas de gestión y las
  copias del control de aforo. Sustituirlos es reimplementar aforo por taller,
  encuestas, exportación, PDF y firma.
- **Riesgo de alcance.** Una fase 1 que lo abarque todo no se entrega, y
  mientras tanto el problema que sí duele sigue sin resolverse.
- **Coste cero de no tocarlo.** El gestor de formularios, su módulo de vistas
  y su generador de PDF ya están instalados, pagados y funcionando.

## Alternativas consideradas

### Opción 1: llevar las inscripciones al aplicativo en la fase 1

CPT `evt_registration` (una inscripción) y `evt_session` (un taller con
aforo), con su formulario público, su validación de plazas, su exportación y
su tabla de gestión.

| Pros | Contras |
|---|---|
| Un solo modelo de datos: el evento y sus inscritos en el mismo sitio. | Multiplica el alcance de la fase 1 por tres, largo. |
| «Cuánta gente hay inscrita» sería una consulta. | Obliga a reimplementar aforo, encuestas, CSV, PDF y firma digital antes de tener el aplicativo en pie. |
| Se acaba el formulario por evento. | Exige **migrar todos los registros personales vivos** antes del primer despliegue, con su análisis de protección de datos. |
| | Los consentimientos firmados habría que moverlos o dejarlos donde están, lo que rompe el «un solo sitio» de todas formas. |

### Opción 2: puente — el sistema anterior guarda, el aplicativo lee

Las inscripciones siguen donde están y el aplicativo las consulta por la API
REST del gestor de formularios, que está instalada.

| Pros | Contras |
|---|---|
| El aplicativo podría contar inscritos y enseñarlos junto al evento. | Acopla el aplicativo a los IDs de campo de **cada** formulario duplicado. |
| No mueve ni un dato personal. | Esos IDs cambian con cada duplicado: la duplicación los reescribe emparejando por nombre, así que la única clave estable sería el **nombre del campo**, que nadie garantiza. |
| | Añade una dependencia dura del sistema anterior al aplicativo, justo cuando el objetivo es poder retirarlo. |

### Opción 3: dejarlas donde están, con criterios escritos de cuándo moverlas (elegida)

El aplicativo no toca las inscripciones. La página de inscripción sigue siendo
una página del evento con el shortcode del formulario dentro.

### Opción 4: sustituir solo el trozo que más duele, el control de aforo

Un único fragmento parametrizado, que resuelva el formulario por su clave
textual —estable, como ya hace algún otro fragmento del sistema anterior— en
lugar de por ID numérico, con el aforo en una opción.

Es plausible y es la primera candidata de la fase 3, pero **no está
comprobado** que los IDs de los campos de la matriz se puedan resolver por
nombre de forma fiable: es precisamente lo que hace la duplicación, y depende
de que nadie renombre un campo. Se anota como trabajo pendiente, no como
decisión tomada.

## Decisión

**En la fase 1 el aplicativo no toca las inscripciones.** Lo que eso
significa, punto por punto:

- **No se registran `evt_registration` ni `evt_session`.** Los tipos de
  contenido de la fase 1 son tres: `evt_event`, `evt_speaker` y
  `evt_activity`.
- **La página de inscripción sigue siendo una página del evento**, con
  `evt_section_type = inscripcion`
  (`EventMetaKeys::section_types()`) y el shortcode del formulario en su
  contenido, como hoy.
- **El vínculo evento → formulario se guarda como meta del evento**: el ID del
  formulario de inscripción y el de la vista de gestión, que hoy son dos
  campos más del formulario que se sustituye. A 2026-09-12 esas claves
  **todavía no están declaradas** en `EventMetaKeys`: las fija esta ADR y las
  implementa la **fase 2** de
  [PLAN-0001](../plan/PLAN-0001-implantacion-por-fases.md) (entregable 2.10).
  Llevar las inscripciones al aplicativo es la **fase 3**.
- **El gestor de formularios, su módulo de vistas y su generador de PDF siguen
  instalados** y actualizados. No son deuda que se pueda desinstalar: son
  parte del sistema en producción, y además los necesitan las páginas
  históricas ([ADR-0008](ADR-0008-migracion-de-los-eventos-historicos.md)).
- **El rol de gestión de eventos no se toca.** Quienes lo tienen siguen
  gestionando entradas con las capacidades del gestor de formularios
  ([ADR-0006](ADR-0006-el-area-es-un-ambito-no-un-rol.md)).
- **Las copias del control de aforo se quedan como están** y **ninguna entra
  en el bundle** del aplicativo. Siguen viviendo en Code Snippets.
- **No se versiona ningún formulario de inscripción.** Se duplican entre ellos,
  y versionarlos sería congelar una foto que caduca con el siguiente evento.

**Esto es deuda, y se reconoce como tal.** No se paga ahora porque el alta y
la publicación del evento son el problema que duele hoy, y porque mover
millares de registros con datos personales y consentimientos firmados es un
proyecto con su propio análisis de protección de datos. La fase 3 la paga.

### Criterios que disparan la fase 3

No se deja «para más adelante» sin decir cuándo. Cualquiera de estos basta:

| Disparador | Cómo se reconoce | Qué se hace entonces |
|---|---|---|
| Un evento nuevo con talleres | Habría que copiar **una vez más** el control de aforo | Parametrizar el aforo antes de copiarlo (opción 4) |
| El sitio pasa de 20 formularios de inscripción | Recuento en el gestor de formularios | Plantilla única de inscripción en el aplicativo |
| Alguien pide «en qué se ha inscrito esta persona» | Hoy exige abrir a mano el formulario de cada evento | Modelo propio de inscripción: es la consulta que el sistema anterior no puede responder |
| Llega una petición de acceso o supresión | Hay que buscar en el formulario de cada evento y en el almacén de adjuntos protegidos | Ídem, y con prioridad: es una obligación legal con plazo |
| Se piden certificados de asistencia | Hoy no existe nada de eso en el sitio | Entra en el mismo diseño que la inscripción |
| El módulo de vistas deja de renovarse o de mantenerse | Aviso de licencia o de compatibilidad | Migración forzada, y entonces ya no hay elección |

La primera vez que las inscripciones se lleven al aplicativo, esta ADR se
sustituye.

## Consecuencias

### Positivas

- La fase 1 cabe: el aplicativo se puede desplegar **sin migrar un solo dato
  personal**, lo que también significa sin bloquearse esperando un análisis de
  protección de datos.
- Las inscripciones vivas no corren ningún riesgo, porque no se tocan. Ni las
  de los eventos en curso ni los consentimientos ya firmados.
- El problema que de verdad duele —dar de alta y publicar un evento— se
  resuelve antes, y su solución se puede probar con eventos reales mientras
  las inscripciones siguen funcionando igual.
- Quien gestiona entradas hoy no nota ningún cambio: no hay que formar a nadie
  en una herramienta nueva a la vez que se cambia la otra.

### Negativas

- **El coste marginal por evento no baja en la fase 1.** Cada evento nuevo
  seguirá costando un formulario duplicado, una o dos vistas y, si hay
  talleres, otra copia del control de aforo. El aplicativo mejora el alta del
  evento y no mejora nada de lo que viene después.
- **Conviven dos modelos de datos sin integridad referencial entre ellos.** El
  evento vive en `wp_posts`; sus inscritos, en las tablas del gestor de
  formularios. El vínculo es un ID de formulario copiado en una meta: borrar
  un evento no borra nada del sistema anterior, y borrar un formulario deja la
  meta apuntando al vacío. Nadie lo detecta salvo mirando.
- **El aplicativo no puede responder «cuánta gente hay inscrita en este
  evento»** sin consultar el sistema anterior, y por la opción 2 se ha
  decidido no consultarlo. Ese dato sigue estando solo en la vista de gestión.
- **La superficie de protección de datos se queda donde está**, incluidos los
  consentimientos firmados servidos desde una ruta protegida con el nombre en
  base64. Base64 no es cifrado: es una codificación reversible por cualquiera.
- **Las copias del control de aforo siguen siendo copias, y no son copias
  iguales.** Las diferencias están medidas —el detalle, con fichero y línea,
  está en `.local/`— y son de calidad, no de estilo: la mitad de ellas valida
  en servidor solo la primera fila de la matriz, con lo que un segundo turno
  se puede sobrepasar, y esas mismas interpolan el ID del formulario en el SQL
  en lugar de usar `$wpdb->prepare`. La única copia activa hoy sí valida todas
  las filas y sí usa `prepare`. El riesgo no es teórico: **reactivar una de
  las viejas para un evento nuevo devuelve los dos defectos**.
- Mantener instalado el gestor de formularios mantiene también sus vías de
  entrada, y una de ellas ya está señalada como riesgo abierto: un fragmento
  pegado a mano devuelve `unfiltered_html` a quien la trae en su rol, saltándose
  la regla con la que el núcleo la reserva en una red. El aplicativo nuevo
  no concede esa capacidad en ningún sitio.

### Neutras

- La plantilla de inscripción que quedó «en construcción» sigue sin una sola
  entrada. No se borra ni se completa: si la fase 3 hace la plantilla, la hará
  en el aplicativo.
- La fase 3 tendrá su propia ADR, con su propio análisis de migración, y
  sustituirá a esta.
- Esta decisión no dice nada sobre las encuestas de valoración ni sobre las
  preguntas del público: siguen igualmente en el sistema anterior, por la
  misma razón y sin decisión propia.
