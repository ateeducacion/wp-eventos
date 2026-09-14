---
id: ADR-0003
title: "Un CPT jerárquico en lugar de páginas creadas por un formulario"
status: Aceptada
date: 2026-09-12
related:
  issues: []
  prs: []
  sdds: [SDD-0001, SDD-0002]
  adrs: [ADR-0001, ADR-0002, ADR-0004, ADR-0007, ADR-0010]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0003: Un CPT jerárquico en lugar de páginas creadas por un formulario

## Estado

Aceptada

**Aceptada el 2026-09-12**: se adopta la **opción 2** —CPT `evt_event`
jerárquico, con el código en `src/Evt/` empaquetado como un único Code
Snippet—. El formulario de alta del sistema anterior y la vista que fabrica sus
páginas dejan de ser el aplicativo; siguen vivos mientras dure la convivencia, y
las inscripciones se quedan donde están
([ADR-0007](ADR-0007-las-inscripciones-siguen-en-el-sistema-anterior.md)).

## Contexto

Hoy el aplicativo de eventos no es un programa: es una cadena de piezas de
configuración dentro del escritorio de WordPress, montada sobre un plugin de
formularios. Crear una página de evento recorre este camino, y cada eslabón
está verificado:

| # | Pieza | Qué hace |
|---|---|---|
| 1 | La página de alta | Una página del maquetador cuyo contenido útil es un único shortcode: el que inserta el formulario |
| 2 | El formulario de alta | Más de cien campos que se rellenan de una vez |
| 3 | La acción «Crear Página» | Crea un `page` con el título, el slug y el estado sacados de tres campos, el `post_content` **vacío** y el `post_parent` **fijo a 0** |
| 4 | La vista de detalle | Genera el contenido de la página a partir de la entrada del formulario |
| 5 | Un fragmento de código pegado a mano | Tras crear la entrada, copia el `post_id` de vuelta a un campo del formulario con SQL interpolado |
| 6 | Otro fragmento | Corrige el `post_parent` leyendo `$_POST` |
| 7 | Un tercero | Quita el saneado de HTML de un campo y, de paso, devuelve `unfiltered_html` a quien la trae en su rol, saltándose la regla con la que el núcleo la reserva en una red |

Cada eslabón está comprobado sobre la exportación del sitio y el material de
investigación del sistema anterior, que vive en `.local/` y no se publica.

Y estas son las medidas que importan, todas tomadas sobre la foto del
2026-09-12 descrita en `.local/`:

| Qué | Medida | Cómo se mide |
|---|---|---|
| Formulario de alta | Más de **cien campos**, repartidos entre el formulario principal y cuatro subformularios embebidos | recuento de campos sobre la exportación del formulario |
| Vista de detalle | **42 KB** de plantilla y cerca de **doscientos bloques condicionales** anidados, que emiten una veintena de secciones y filas del maquetador | recuento sobre el fichero exportado |
| Jerarquía del sitio | **7 líneas** de PHP que leen `$_POST` sin comprobación alguna | el fragmento completo |
| Contenido | Tres de cada cuatro páginas del sitio son secciones hijas de un evento; casi todas incrustan los datos del formulario con un shortcode, y casi todas repiten la vista del menú principal | recuento sobre la exportación del sitio |
| Slugs | **34 slugs distintos** para la misma sección «programa» entre las páginas hijas: `programa`, `programa_`, `programa-er`, `mnc-programa`… | ídem |
| Dato copiado | Hay páginas que llevan la sede, el día y la fecha **como atributos del shortcode**, no leídos del evento | ídem |
| Sedes | Sede 1 y sede 2 son dos subformularios duplicados campo a campo; hasta la errata del rótulo está copiada de uno al otro | exportación del formulario |
| Vistas | **Más de la mitad** son la tabla de gestión o la tabla de inscripción **de un evento concreto** | exportación del sitio |
| Formularios | **Más de la mitad** son la inscripción o la selección de talleres **de un evento concreto** | exportación del formulario |

Tres consecuencias se leen directamente de esa tabla, sin interpretar nada:

1. **La vista de detalle es un motor de plantillas metido en un campo de
   texto.** 42 KB con cerca de doscientos condicionales, sin control de
   versiones, sin tests y sin forma de revisar un cambio: no hay diff posible
   de un campo `longtext` de la base de datos.
2. **El dato viaja copiado.** La vista interpola sede, día y fecha en el
   momento de crear o actualizar la página, así que el mismo dato vive en la
   entrada del formulario y en el `post_content`. Cambiar la fecha en el
   formulario no cambia lo que se ve hasta que alguien vuelve a guardar.
3. **No hay modelo, hay tecleo.** 34 formas de llamar a la página «Programa»
   es la firma de la creación manual: nada valida el slug, y nada relaciona la
   sección con su tipo salvo un campo de texto que se rellena a mano.

Añádase que sede 1 y sede 2 están duplicadas campo a campo y que una **tercera
sede no cabe** sin duplicar otra vez el bloque en el formulario, en la vista de
detalle y en las dos vistas del programa.

## Problema

¿Cuál debe ser la capa de persistencia y presentación del dominio «evento»:
seguir siendo entradas de un formulario que fabrican páginas, o bajar el
dominio a WordPress nativo con un tipo de contenido propio, de modo que el
desarrollo sea modular, testeable y versionable y el despliegue siga siendo
compatible con «solo admin más Code Snippets»
([ADR-0001](ADR-0001-repo-entorno-desarrollo-no-plugin.md))?

## Factores de decisión

1. **Modelo de producción**: no se puede instalar un plugin ni desplegar
   ficheros; Code Snippets sí
   ([ADR-0001](ADR-0001-repo-entorno-desarrollo-no-plugin.md)).
2. **Conservar las URL**: las páginas existentes están indexadas y enlazadas
   desde fuera. Lo que sustituya al `page` jerárquico tiene que ser también
   jerárquico.
3. **Versionado y revisión**: diffs legibles, PR, CI. Hoy no hay ninguno de
   los tres para las tres piezas que más pesan: el formulario, la vista y los
   fragmentos de código sueltos.
4. **Testabilidad**: poder probar el dominio sin cargar el plugin de
   formularios.
5. **Un solo dato en un solo sitio**: acabar con la copia sede/día/fecha.
6. **Permisos por área**: el acotado por área necesita un dueño y un ámbito
   por registro, cosa que una entrada de formulario no tiene
   ([ADR-0006](ADR-0006-el-area-es-un-ambito-no-un-rol.md)).
7. **Listados y filtros**: columnas, filtro por área y acciones en lote son
   nativos en el escritorio de un CPT; en el sistema anterior son vistas de
   pago.
8. **Coste de migración** de las páginas existentes
   ([ADR-0008](ADR-0008-migracion-de-los-eventos-historicos.md)).
9. **Quién lo mantiene**: el equipo sabe manejar el plugin de formularios; PHP
   de WordPress lo sabe menos gente.
10. **Empaquetado**: un artefacto activable en Code Snippets frente a decenas
    de fragmentos sueltos.

## Alternativas consideradas

### Opción 1: seguir como hoy — `page` + formulario + vista

Se conserva la cadena entera y se mejora por dentro: más campos en el
formulario, más ramas condicionales en la vista, más fragmentos de apoyo.

| Pros | Contras |
|------|---------|
| Coste inmediato cero: ya funciona y el equipo lo conoce. | La vista de detalle ya tiene cerca de doscientos condicionales; cada sección nueva la agranda y nadie puede revisarla. |
| El alta se maqueta en el escritorio, sin tocar código. | Ni el formulario, ni la vista, ni las acciones están en Git: no hay diff, ni PR, ni vuelta atrás. |
| El plugin de formularios y su módulo de vistas ya están pagados e instalados. | Una tercera sede obliga a duplicar bloques en cuatro sitios distintos. |
| Las inscripciones ya viven ahí. | El dato se copia al `post_content`: dos verdades para el mismo hecho. |
| | La jerarquía del sitio depende de 7 líneas que leen `$_POST` sin validar. |
| | Más de la mitad de las vistas y de los formularios existen solo porque cada evento trae los suyos: el crecimiento es lineal en eventos. |
| | No hay tests posibles. |

**Encaja si** el aplicativo se congela y solo se le hace mantenimiento
correctivo.

### Opción 2: CPT `evt_event` jerárquico, con `src/Evt` empaquetado como Code Snippet (elegida)

El dominio pasa a WordPress nativo:

- `evt_event` **jerárquico**: la raíz es el evento y las hijas son las
  secciones —programa, ponentes, inscripción…—, sustituyendo 1:1 al `page`
  jerárquico de hoy (`EventPostType::register()`).
- El tipo de sección deja de ser texto tecleado: meta `evt_section_type` con
  vocabulario cerrado en PHP, calcado del campo que hoy se rellena a mano
  (`EventMetaKeys::section_types()`).
- `evt_speaker` y `evt_activity` para lo que hoy son dos formularios sueltos,
  uno de ponentes y otro de actividades.
- Las cuatro dimensiones que hoy se mezclan en una sola taxonomía se separan en
  tres ([ADR-0004](ADR-0004-una-taxonomia-por-dimension.md)) y el estado se
  deriva de las fechas
  ([ADR-0005](ADR-0005-el-estado-del-evento-se-deriva-de-las-fechas.md)).
- El contenido de la página lo genera código versionado, no una vista guardada
  en la base de datos
  ([ADR-0010](ADR-0010-la-pagina-la-genera-codigo-versionado.md)).
- El repositorio se empaqueta en un fichero
  (`build/pack-snippet.php` → `snippets/evt-eventos-app.bundle.php`,
  1.363 líneas) que se activa en Code Snippets.

| Pros | Contras |
|------|---------|
| Dominio en APIs conocidas: `WP_Query`, meta, capacidades, `post_parent`. | Hay que escribir el alta y las pantallas: más PHP inicial. |
| Jerarquía nativa y explícita, sin leer `$_POST`. | Se pierde el constructor visual de formularios. |
| Vocabulario cerrado en código: el slug y el tipo de sección dejan de ser tecleo libre. | Desplegar exige empaquetar y pegar un bundle, y saber PHP para cambiarlo. |
| El dato vive una sola vez; la sede, la fecha y el día se leen del evento al pintar. | Hay que mantener el empaquetador y no editar nunca el bundle a mano. |
| Diffs, PR, PHPCS, PHPMD y PHPUnit sobre todo el dominio. | Migrar las páginas existentes tiene su propio coste ([ADR-0008](ADR-0008-migracion-de-los-eventos-historicos.md)). |
| Columnas, filtro por área y acotado en el escritorio, nativos. | El equipo tiene que aprender la estructura. |
| Un artefacto versionado en producción en lugar de decenas de fragmentos sueltos. | Mientras dure la convivencia hay dos sistemas vivos a la vez. |
| N sedes es una lista, no un bloque duplicado. | |

**Encaja si** esto es un pequeño producto con reglas, permisos y listados —que
es lo que describe un formulario de más de cien campos— y no un formulario.

### Opción 3: híbrido — el formulario para el alta, CPT para los datos

El alta sigue en el formulario del sistema anterior; un hook materializa un
`evt_event` y a partir de ahí los listados, los permisos y la presentación
operan sobre el CPT.

| Pros | Contras |
|------|---------|
| Conserva el constructor visual para el alta. | **Dos modelos de datos** que hay que mantener sincronizados: entrada y post, con sus borrados y sus divergencias. |
| Migración gradual, con marcha atrás. | Sigue dependiendo del plugin de formularios y de sus vistas de pago para el alta. |
| El equipo no cambia de herramienta el primer día. | Los más de cien campos del formulario siguen sin estar versionados: la fuente del alta continúa fuera de Git. |
| | Más superficie de fallo y necesidad de tests de integración con el plugin de formularios, que es justo lo que se quería quitar. |

**Encaja si** hiciera falta un puente temporal. Como estado final es peor que
la 1 y que la 2.

### Opción 4: plugin de WordPress propio

La misma estructura modular de la opción 2, pero desplegada como plugin.

| Pros | Contras |
|------|---------|
| Estándar de WordPress, autoload, actualizaciones claras, mejor experiencia de desarrollo. | **Descartada por [ADR-0001](ADR-0001-repo-entorno-desarrollo-no-plugin.md)**: no hay despliegue de ficheros, y en un multisitio la instalación es competencia de la red, no del subsitio. |

**Encaja si** algún día se abre el despliegue por ficheros. Sería la evolución
natural de la opción 2 con el mismo `src/Evt/`.

## Comparación resumida

| Criterio | 1 Seguir igual | 2 CPT + bundle | 3 Híbrido | 4 Plugin |
|---|---|---|---|---|
| Compatible con «solo admin» | Alta | Alta | Alta | **Nula hoy** |
| Conserva la jerarquía y las URL | Alta (es lo que hay) | Alta (CPT jerárquico) | Alta | Alta |
| Versionado, diff y PR del dominio | **Nulo** | Alto | Medio | Alto |
| Tests | **Nulos** | Altos | Medios | Altos |
| Un solo dato en un solo sitio | Bajo | Alto | Medio | Alto |
| Permisos por área | Bajo | Alto | Medio | Alto |
| Velocidad de maquetar el alta | Alta | Media | Alta | Media |
| Crecimiento por evento nuevo | Lineal (formulario + vistas) | Constante | Lineal | Constante |
| Esfuerzo inmediato | Nulo | Medio-alto | Medio | Alto + cambio de política |
| Complejidad a 12 meses | Alta | Media | Muy alta | Media |

## Decisión

**Haremos la opción 2**: el evento es un `evt_event` jerárquico y el
aplicativo es código en `src/Evt/`, empaquetado en un único Code Snippet.

- El CPT se registra con `'hierarchical' => true`, `page-attributes` entre los
  `supports` y capacidades propias mapeadas con
  `capability_type => array( 'evt_event', 'evt_events' )` y
  `map_meta_cap => true` (`EventPostType::register()`).
- La raíz es el evento; las hijas son las secciones, y lo que cada hija es se
  guarda en `evt_section_type`, con una lista cerrada de once valores en
  castellano copiados del campo que hoy se teclea a mano, para que la migración
  no tenga que traducir nada (`EventMetaKeys::section_types()`).
- `src/Evt/load-order.php` fija el orden de carga —doce ficheros, `App.php`
  el último— y es la lista que usan tanto `bootstrap.php` como el
  empaquetador. Un fichero que no esté en la lista hace fallar `make bundle`
  (`build/pack-snippet.php`).
- El formulario de alta y sus vistas **no se tocan** mientras dure la
  convivencia. Las inscripciones se quedan donde están durante la fase 1
  ([ADR-0007](ADR-0007-las-inscripciones-siguen-en-el-sistema-anterior.md)): son
  entradas vivas y no aportan nada a esta decisión.

### Lo que se pierde, dicho sin adornos

El sistema anterior no se eligió mal: se eligió por razones que siguen siendo
ciertas.

- **Se pierde el constructor visual.** Hoy, añadir un campo al alta de un
  evento es arrastrarlo en el escritorio, sin desplegar nada y sin saber PHP.
  Con el CPT, añadir un campo es editar `src/Evt/`, ejecutar `make bundle`,
  sincronizar y —en producción— pegar el bundle. Eso es más lento y necesita
  a alguien que programe.
- **Se pierde conocimiento ya adquirido.** El equipo lleva años con ese plugin
  y sus vistas; con esto empieza de cero en la parte de código.
- **Se pierde inmediatez en el diseño de la página.** Cambiar un color o una
  tipografía es hoy un campo del formulario —hay once campos solo de diseño de
  la principal— y pasará a ser código.
- **Se gana lo contrario de todo eso**: nada de lo anterior es revisable,
  probable ni reversible, y ese es precisamente el motivo de la decisión.

### Criterios de aceptación de esta decisión

1. Un evento y sus secciones se crean y se editan en el escritorio sin tocar
   el sistema anterior.
2. Las URL de un evento migrado siguen resolviendo
   ([ADR-0008](ADR-0008-migracion-de-los-eventos-historicos.md)).
3. La sede, el día y la fecha se leen del evento en el momento de pintar: no
   quedan copiados en el contenido.
4. Añadir una tercera sede no obliga a duplicar ningún bloque.
5. `make check` pasa: PHPCS, PHPMD, la comprobación de provisión y PHPUnit.

## Consecuencias

### Positivas

- Un solo modelo mental: «un evento es un post, y sus secciones son sus
  hijos». Es el que ya tiene el sitio, solo que ahora escrito.
- El dominio queda cubierto por tests y por CI; hoy no hay ni una prueba.
- El escritorio da gratis lo que en el sistema anterior son vistas de pago:
  listados, columnas, filtros, búsqueda, papelera, revisiones y REST.
- El acotado por área tiene dónde apoyarse: un post tiene autor, taxonomía y
  capacidades; una entrada de formulario, no.
- El crecimiento deja de ser lineal en eventos: no hará falta un formulario y
  dos vistas nuevas por cada jornada.

### Negativas

- Trabajo inicial considerable: alta, edición, listados y presentación hay
  que escribirlos.
- Disciplina permanente: editar en `src/Evt/`, nunca en el bundle, y volver a
  empaquetar antes de sincronizar.
- Convivencia: durante la fase 1 hay dos sistemas vivos —el CPT y el
  formulario de alta— y dos sitios donde mirar cuando algo no cuadra.
- Dependencia de una persona con PHP para cualquier cambio que hoy se hacía
  arrastrando campos.
- La reescritura de URL no está resuelta en esta ADR: el CPT se registra hoy
  con `'rewrite' => array( 'slug' => 'evento' )`
  (`EventPostType::register()`), y conservar
  `/eventos/<slug>/` en la raíz del subsitio necesita su propia decisión. Está
  escrito como comentario en el código y anotado como pendiente en
  [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md); mientras no se
  cierre, la promesa «conserva las URL» está a medias.

### Neutras

- Los fragmentos de código sueltos de producción no desaparecen: los que
  sostienen el aplicativo dejan de hacer falta, pero los de control de aforo y
  los de centros siguen atados al sistema anterior mientras las inscripciones
  sigan allí.
- La vista de detalle se conserva como documentación del comportamiento
  actual, no como código a portar: es la referencia de qué pinta cada tipo de
  sección.
- el tema sigue siendo el tema. Esta ADR no decide cómo se pinta la página; eso
  es [ADR-0010](ADR-0010-la-pagina-la-genera-codigo-versionado.md).

## Referencias

- [ADR-0001](ADR-0001-repo-entorno-desarrollo-no-plugin.md) — el repositorio
  no es un plugin.
- [ADR-0004](ADR-0004-una-taxonomia-por-dimension.md) — una taxonomía por
  dimensión.
- [ADR-0007](ADR-0007-las-inscripciones-siguen-en-el-sistema-anterior.md) — las
  inscripciones se quedan donde están.
- [ADR-0010](ADR-0010-la-pagina-la-genera-codigo-versionado.md) — el
  contenido lo genera código versionado.
- `.local/` (investigación del sistema anterior) — la arquitectura de hoy,
  medida: de dónde sale cada cifra de esta ADR y cómo se vuelve a tomar.
