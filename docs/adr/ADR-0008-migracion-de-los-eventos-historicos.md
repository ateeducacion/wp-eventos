---
id: ADR-0008
title: "Se migra el contenedor de los eventos históricos y se congela su contenido"
status: Aceptada
date: 2026-09-12
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0003, ADR-0004, ADR-0005, ADR-0006, ADR-0007, ADR-0010]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0008: Se migra el contenedor de los eventos históricos y se congela su contenido

## Estado

Aceptada

## Contexto

El día que `evt_event` entre en servicio, en el subsitio ya hay **163
páginas**: 45 raíces, 118 hijas, ninguna huérfana. De las raíces, unas **30
son eventos** y el resto son páginas de sistema. Todas son `page`
jerárquicas, y esa jerarquía es todo el modelo de datos: un evento es una
página raíz y sus secciones son páginas hijas. Está medido sobre el volcado
del sistema anterior que se conserva en `.local/`.

Lo que hay dentro de esas páginas no lo escribió nadie. Lo genera **la
plantilla del gestor de formularios que se sustituye**: 42.165 caracteres con
193 bloques condicionales anidados, que emiten shortcodes del tema —20
`et_pb_section`, 28 `et_pb_column`, 19 `et_pb_row`— y once llamadas que
insertan datos del formulario. El cuerpo de una página histórica no es
maquetación: son cuatro o cinco de esas llamadas, y todas llevan como
argumento el **ID de la propia página**.

Es decir: el contenido de cada página **depende de que el sistema anterior
siga instalado y de que el ID de la página no cambie**, porque el ID es la
clave con la que la plantilla busca los datos del evento.

Tres restricciones más, que son las que mandan:

- **El sitio es institucional, público y está indexado.** Las URL de esas 163
  páginas están enlazadas desde fuera, en circulares, en correos y en
  documentos publicados. Ninguna se puede romper.
- **Los slugs no siguen ninguna convención.** Muestra real de «Programa»:
  `programa-ed`, `programacomunicacion2021`, `cjle2021-programa`, `programa`,
  `programa_`, `programa-8616`, `programa-mnc26`, `lomloe-programa`… Son la
  firma de la creación manual, y no hay forma de derivarlos ni de
  regenerarlos.
- **El contenido histórico ya no se edita.** 32 páginas llevan el término
  `evento-finalizado`: son ediciones cerradas que nadie va a volver a
  maquetar.

## Problema

¿Qué se hace con las 163 páginas que ya existen el día que el aplicativo
entra en servicio?

## Factores de decisión

- **Conservar las URL.** Es la restricción dura. No 163 redirecciones: las
  mismas URL.
- **Un solo listado, un solo modelo de permisos, una sola copia de
  seguridad, una sola consulta en la portada.** Es exactamente lo que hoy no
  hay, y el motivo de todo el proyecto.
- **No reescribir contenido.** Traducir maquetación es la parte cara y la que
  más riesgo tiene.
- **Reversibilidad.** Mientras no se borre nada, un cambio de `post_type` se
  deshace.
- **El sistema anterior se queda instalado igualmente** durante la fase 1
  (ADR-0007), así que apoyarse en él para lo histórico no añade una
  dependencia nueva.

## Alternativas consideradas

### Opción 1: dejar los históricos como `page` y usar `evt_event` solo para lo nuevo

| Pros | Contras |
|---|---|
| Coste inmediato cero: no se toca nada de lo que ya funciona. | **Dos sistemas para siempre.** No es una fase de transición: es el estado final. |
| Ningún riesgo sobre las URL publicadas. | La portada tiene que unir dos consultas y ordenarlas juntas. |
| | Dos modelos de permisos: el histórico seguiría bajo los roles de área heredados, que no editan nada. |
| | Dos taxonomías vivas: `convocatoria` sobre `page` y las tres nuevas sobre `evt_event`. |
| | Buscar «todos los eventos de STEAM» exige dos búsquedas y unirlas a mano. |

Es exactamente la enfermedad que se viene a curar, con otro nombre.
Descartada.

### Opción 2: migrar también el contenido

Traducir el el tema y las llamadas a la plantilla vieja a la representación
nueva, de modo que las páginas históricas se pinten con el mismo código que
las nuevas.

| Pros | Contras |
|---|---|
| Un solo modo de pintar: se podría desinstalar el sistema anterior. | Son ~30 eventos con **maquetación distinta cada uno**: colores, fuentes, mosaicos y HTML de sustitución elegidos evento a evento, cada uno en su propio campo del formulario. |
| El contenido dejaría de depender de un ID. | La plantilla de origen ramifica en 193 bloques condicionales: reproducir su salida exacta es reimplementarla. |
| | Beneficio casi nulo: son páginas que ya nadie edita. Riesgo alto sobre contenido publicado. |

Descartada: mucho riesgo por un beneficio que solo se cobra el día que se
desinstale el sistema anterior, que no es este.

### Opción 3: archivar como HTML estático y borrar de WordPress

Volcar las 163 páginas renderizadas a ficheros y servirlas desde el
servidor.

- A favor: elimina de golpe la dependencia del sistema anterior y del tema para
  lo histórico.
- En contra: se pierden la búsqueda del sitio, la taxonomía y la posibilidad
  de reabrir una edición copiando la anterior — que es como se crean los
  eventos hoy. Y conservar las URL exige montar y mantener una capa aparte,
  que es más frágil que la de ahora. Descartada.

### Opción 4: migrar el contenedor y congelar el contenido (elegida)

Cambia el tipo de contenido, no el contenido.

## Decisión

**Se migra el contenedor de los eventos históricos y se congela su
contenido.**

**Qué se migra.** Las páginas que son un evento o una sección de un evento
pasan de `post_type = page` a `post_type = evt_event` **conservando ID,
`post_name`, `post_parent`, `post_content`, `post_date`, autoría y
adjuntos**. Cambia una columna de una fila. El `post_content` no se toca: los
shortcodes del tema y las llamadas a la plantilla vieja se quedan tal cual, y
siguen funcionando porque el ID —que es su clave— no cambia.

**Qué NO se migra.** Las páginas de sistema siguen siendo `page`:

| Página | Por qué se queda |
|---|---|
| Alta de evento | Es el formulario del sistema anterior incrustado; vive mientras viva el camino viejo |
| Portada del subsitio | Es la portada, no un evento |
| Borradores | Listado privado; lo sustituye el filtro del escritorio |
| Alta de ponente | Otro formulario del sistema anterior |
| Alta de actividad | Otro formulario del sistema anterior |
| Preguntas | Participación del público |
| Asistencia, y su hija | Hoja de registro de asistencia |
| Pruebas de PDF y de firma | Pruebas, no contenido publicado |

Son las nueve raíces de sistema que la investigación identificó. Con 45
raíces y unos 30 eventos, **quedan unas seis raíces sin clasificar**: hay que
mirarlas una a una antes de migrar, no adivinarlas.

**Los términos se reparten.** Los de `convocatoria` van a las tres taxonomías
nuevas según su eje
([ADR-0004](ADR-0004-una-taxonomia-por-dimension.md)): los de área a
`evt_area`, los de tipología a `evt_type`, los de curso escolar a
`evt_course`. Los de estado **no se migran**
([ADR-0005](ADR-0005-el-estado-del-evento-se-deriva-de-las-fechas.md)): el
estado se deriva de las fechas. El reparto está medido cruzando las listas de
exclusión que usaba el sistema anterior con el volcado de términos guardado en
`.local/`, y **cierra exacto**: no queda ningún término suelto ni ningún
solape. Ojo con no confundir esto con las seis raíces sin clasificar del
párrafo anterior: son dos pendientes distintos.

**Lo histórico se marca y se congela.** Cada página migrada lleva la meta
`evt_legacy`. Con ella puesta:

- el contenido el tema **no se toca** y se sigue pintando como hoy;
- **el sistema anterior y sus plantillas se quedan instalados en modo
  lectura**, que es lo que hace que las páginas sigan viéndose;
- **el editor muestra un aviso en lugar de los campos nuevos**: no se pide
  fecha de inicio, ni sede, ni tipo de sección para una página que no los usa
  y cuyo contenido no se va a regenerar.

**Los eventos nuevos usan el camino nuevo desde el primer día.** No hay
convivencia de dos formas de crear: se crea con el aplicativo. Y **cuando el
camino nuevo funcione, se desactiva la acción que crea la página** en el
formulario del sistema anterior. Se desactiva, no se borra: desactivarla es
reversible y deja intacto el histórico que ya creó.

**Por qué esta y no otra:**

1. **Las URL se conservan intactas.** Es un sitio institucional público,
   indexado y enlazado desde fuera; ninguna otra opción lo garantiza sin
   montar una capa de redirecciones.
2. **Hay UN listado, UN modelo de permisos, UNA copia de seguridad y UNA
   consulta en la portada.** Es justo lo que hoy no hay.
3. **No se reescribe ni una línea de contenido**, que es la parte cara y la
   arriesgada.
4. **El paso es reversible** mientras no se borre nada: devolver
   `post_type` a `page` deshace la migración.

**El guion de migración no está escrito.** Tiene que ser idempotente, con
ensayo en seco y copia de seguridad previa, y va en la fase 2 de
[PLAN-0001](../plan/PLAN-0001-implantacion-por-fases.md).

## Consecuencias

### Positivas

- Las 163 URL siguen siendo las mismas, con los mismos IDs, y los 4.597
  adjuntos siguen colgando de los mismos padres.
- Un solo listado en el escritorio, con un solo modelo de permisos: los
  eventos históricos entran en el acotado por área
  ([ADR-0006](ADR-0006-el-area-es-un-ambito-no-un-rol.md)) igual que los
  nuevos.
- La portada puede pasar a una sola `WP_Query` sobre `evt_event`, en lugar
  del shortcode del plugin de listados que hoy la pinta.
- Un evento de 2021 y uno de 2026 salen en la misma búsqueda, con la misma
  taxonomía y en el mismo orden.
- Copiar la edición anterior para crear la siguiente sigue siendo posible,
  que es como se trabaja hoy.

### Negativas

- **Conservar las URL exige que el CPT reescriba en la raíz del subsitio, y
  eso no está resuelto.** A 2026-09-12 el CPT se registra con
  `'rewrite' => array( 'slug' => 'evento', 'with_front' => false )`
  (`EventPostType::register()`), lo que produce
  `/eventos/evento/<slug>/` y **no conserva las URL de hoy**. El propio
  fichero lo reconoce en un comentario, justo encima de `rewrite`. Hay al menos dos caminos
  —reescritura en la raíz resolviendo la colisión con `page`, o un filtro
  `post_type_link` más una regla propia— y **ninguno se ha probado**. Es un
  bloqueante de la migración, no un detalle de acabado: sin él, la razón
  principal de esta ADR no se cumple.
- **el tema tiene que tener el constructor habilitado para `evt_event`.** El
  contenido histórico son shortcodes `et_pb_*`; si el tema no procesa ese tipo de
  contenido, las 163 páginas se pintan como texto con corchetes. Es una
  casilla en los ajustes del tema, no código, pero hay que verificarla en el
  ensayo en seco antes de migrar nada.
- **Las plantillas de página dejan de aplicarse solas.** El formulario del
  sistema anterior escribe `_wp_page_template`, y una plantilla de tema solo
  se ofrece a los tipos de contenido que declara. Hay que comprobar qué
  plantillas usan las 163 páginas y si el tema las declara para `evt_event`.
- **Hay que inventariar todo lo que hoy pregunta «¿es una página?».** Al
  menos: el fragmento de código pegado a mano que quita `wpautop` con
  `is_page()`, que dejaría de aplicarse y con ello WordPress metería `<p>` en
  medio del el tema; la taxonomía `convocatoria`, registrada sobre `page` por
  otro de esos fragmentos; los 4 elementos de menú, que guardan el tipo de
  objeto al que apuntan; y la vista del plugin de listados de la portada.
  Esa lista hay que cerrarla antes de migrar, y hoy está abierta.
- **Mientras haya páginas `evt_legacy` hay que mantener el sistema anterior
  instalado y funcionando.** Durante la fase 1 no cuesta nada porque ya se
  queda por ADR-0007, que lo mantiene hasta que la fase 3 lleve las
  inscripciones al aplicativo. **A partir de la fase 4, `evt_legacy` será el
  único motivo** para conservarlo, y ese día habrá que decidir si se paga o si
  se afronta la opción 2.
- **El editor tiene dos comportamientos según `evt_legacy`.** Son dos caminos
  que probar, dos que documentar y una condición que alguien puede olvidar al
  añadir una pantalla nueva.
- **Los eventos históricos no tienen fecha en formato aprovechable**, porque
  en el sistema anterior es texto libre. Con el estado derivado
  ([ADR-0005](ADR-0005-el-estado-del-evento-se-deriva-de-las-fechas.md)), un
  evento sin fecha de inicio sale como «próximo»: o se les fija fecha al
  migrar, o `evt_legacy` los excluye de las consultas de estado. Si no se hace
  ninguna de las dos, la portada anuncia como próximos treinta eventos de
  2021.
- **Los slugs siguen siendo los que son.** `programa_`, `programa-8616`,
  `programacomunicacion2021`: la migración no los toca, porque tocarlos rompe
  las URL. La convención nueva solo se aplica a lo nuevo, así que la
  incoherencia de nombres se queda en el sitio durante años.
- **El guion no está escrito.** Idempotente, con ensayo en seco y copia
  previa: hasta que exista y se haya probado sobre una copia, esta decisión es
  una intención.

### Neutras

- Las páginas de sistema se quedan como `page`. Algunas —la de alta de evento,
  la de borradores— dejarán de tener sentido cuando el aplicativo tome el
  relevo, pero retirarlas es otra decisión y otra fase.
- Los 4.597 adjuntos no se tocan. Su `post_parent` sigue siendo válido porque
  los IDs no cambian.
- Los 11 items de tipo `project` del subsitio son ajenos al aplicativo de
  eventos y no entran en la migración.
- La migración no borra ningún término de `convocatoria` ni desregistra la
  taxonomía: la deja de usar. Retirarla es un paso posterior, cuando conste
  que nada la consulta.
