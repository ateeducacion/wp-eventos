---
id: ADR-0031
title: "El formulario de inscripción es un núcleo fijo más unas pocas preguntas por evento"
status: Aceptada
date: 2026-09-14
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0007, ADR-0018, ADR-0020, ADR-0027]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0031: El formulario de inscripción es un núcleo fijo más unas pocas preguntas por evento

## Estado

Aceptada (2026-09-14).

## Contexto

La [ADR-0027](ADR-0027-los-participantes-son-nuestros.md) decidió que los
participantes se gestionan en este aplicativo y que el sistema anterior no se
lee: habrá **formulario de inscripción propio**. Lo que esa ADR dejó sin
contestar es qué lleva dentro ese formulario, y quién lo decide. Mientras no se
conteste, la pestaña «Participantes» del taller
([ADR-0018](ADR-0018-el-taller-del-evento-es-una-sola-pantalla.md)) no tiene de
dónde sacar a nadie.

La pregunta no se contesta de memoria. Se han medido los formularios de
inscripción del sistema anterior —el material de investigación no se versiona,
así que aquí van las conclusiones y no las fuentes— y lo que dicen es esto:

**El núcleo común real son cinco cosas.** En todos los formularios de
inscripción aparecen, con otro rótulo pero el mismo dato: **identificador
fiscal, nombre, apellidos, correo y el consentimiento**. Añadiendo el
**teléfono** y el **bloque de centro** se cubren casi todos.

**El tamaño engaña.** Esos formularios tienen entre 28 y 48 campos, pero cerca
de **una cuarta parte de todos los campos no son datos**: son separadores de
sección, rótulos y botones. Lo que de verdad se le pide a una persona es
bastante menos de lo que el recuento sugiere. El bloque de centro, además, no
se teclea: sale de un **catálogo maestro ya normalizado**.

**Las preguntas de respuesta cerrada son pocas.** Casillas, una opción y varias
opciones suman **alrededor del 15 % de los campos**. No son la mayor parte del
formulario: son el añadido.

**Y lo que cambia de un evento a otro es pequeño y concreto.** Entre **tres y
seis preguntas de logística**: si se queda a comer, intolerancias alimentarias,
si es residente, traslados, si presenta una experiencia. No se repiten evento a
evento y **no se pueden anticipar**: quien organiza sabe que hay comida cuando
ya ha cerrado la sede.

Es decir: una parte grande, estable y medible, y una parte pequeña,
impredecible y que cambia cada vez. La decisión es qué forma se le da a cada
una.

## Problema

¿Cómo se define el formulario de inscripción de un evento: fijo en código,
construible por quien organiza, o algo intermedio? Y si es intermedio, ¿dónde
está exactamente la raya, de modo que no se deslice sola hacia el constructor
de formularios del que se quiere salir?

## Factores de decisión

- **La parte fija es de verdad fija.** Está medida y es la misma en todos los
  eventos. Ponerla en código permite validarla, normalizarla y probarla sin
  WordPress, como ya se hace con el filtro y la exportación de la
  [ADR-0027](ADR-0027-los-participantes-son-nuestros.md).
- **La parte variable es pequeña y es impredecible.** Pequeña, así que no
  justifica una herramienta grande. Impredecible, así que no se puede resolver
  con una lista escrita de antemano.
- **Quien organiza no despliega.** El aplicativo llega a producción pegando
  código; un evento que necesite un campo nuevo no puede esperar a una versión.
- **Un constructor de formularios es exactamente el sistema del que se sale.**
  Lógica condicional, reglas de validación por campo y un formulario nuevo por
  evento son el origen del problema, no su solución
  ([ADR-0007](ADR-0007-las-inscripciones-siguen-en-el-sistema-anterior.md)).
- **Las columnas tienen que ser estables.** El filtro y la exportación a CSV
  trabajan con una forma de fila declarada en código. Si cada evento inventa su
  estructura, no hay columna que sostener.
- **Son datos personales.** Lo que el código conoce, el código puede validar,
  minimizar y caducar. Lo que el código no conoce, no.
- **El centro ya está normalizado.** Existe un catálogo maestro; volver a
  teclear el nombre del centro es fabricar variantes del mismo dato.
- **El consentimiento ya está decidido.** Es una casilla, con constancia de
  versión y sello de tiempo
  ([ADR-0020](ADR-0020-el-consentimiento-es-una-casilla.md)).

## Alternativas consideradas

### Opción 1: todos los campos fijos en código

Un único formulario, idéntico para todos los eventos, definido en `src/Evt/`.

| Pros | Contras |
|------|---------|
| Todo se valida, se normaliza y se prueba. | **No cabe lo imprevisible, que es justo lo que hay.** El almuerzo, las intolerancias o los traslados no se anticipan: aparecen cuando el evento ya está montado. |
| Columnas estables por definición. | Cada evento con una necesidad nueva exige tocar código y desplegar, para una pregunta que se usa una vez. |
| Nada que aprender: el formulario es siempre el mismo. | Lo que no cabe se cuela igual: aparece un «Observaciones» de texto libre en el que la gente escribe la intolerancia, y entonces el dato existe, está sin estructurar y nadie lo controla. |

Descartada. Es la opción que parece limpia y acaba en un campo cajón de sastre.

### Opción 2: un constructor de formularios completo

Quien organiza compone el formulario entero: tipos de campo, orden, lógica
condicional, reglas de validación.

| Pros | Contras |
|------|---------|
| No hay caso que no quepa. | **Es rehacer el sistema del que se sale**, con sus mismas piezas: condicionales, reglas por campo y un formulario distinto por evento. |
| Quien organiza no depende de nadie. | La parte variable real es de tres a seis preguntas: se construiría una herramienta grande para resolver algo pequeño. |
| | Se acaban las columnas estables: el filtro y el CSV dejarían de tener una forma de fila que declarar. |
| | Multiplica la superficie que hay que mantener y asegurar —validación, permisos, versionado del formulario, migración cuando cambie— y nada de eso lo pidió nadie. |
| | Y la parte fija, que está medida y no cambia, quedaría también a merced de quien edite el formulario. |

Descartada. Es la respuesta completa a una pregunta que no se ha hecho.

### Opción 3: un catálogo cerrado de «extras» que se encienden por evento

El código define de antemano los añadidos posibles —comida, intolerancias,
residencia, traslado— y cada evento marca cuáles usa.

| Pros | Contras |
|------|---------|
| Todo sigue en código: validación, tipo y columna conocidos. | **Cubre los casos comunes y falla justo en los que no se pueden prever**, que son el motivo de que esta ADR exista. |
| Encender una casilla es más simple que redactar una pregunta. | El primer evento que pida algo fuera del catálogo vuelve a necesitar un despliegue, y ese evento llega siempre. |
| | El catálogo crece con cada excepción hasta convertirse en un constructor de formularios, pero peor: uno en el que añadir una opción cuesta una versión. |

Descartada. Aplaza el problema y además lo empeora despacio.

### Opción 4: núcleo fijo en código más una lista corta de preguntas por evento (elegida)

La parte medida va en código; la parte impredecible es una lista de preguntas
de forma cerrada que quien organiza redacta en el taller del evento.

| Pros | Contras |
|------|---------|
| Lo estable se valida y se prueba; lo variable no espera a un despliegue. | La raya entre «núcleo» y «pregunta» la decide quien escribe el código, y moverla es una versión. |
| Las columnas del núcleo siguen siendo estables. | Las respuestas a las preguntas son datos que el código no entiende. |
| La forma cerrada impide que la lista se convierta en un constructor. | Sin lógica condicional se ven preguntas que no aplican. |

## Decisión

**El formulario de inscripción es un núcleo fijo, definido en código, más una
lista corta de preguntas propias del evento.** Ni todo fijo ni un constructor
de formularios.

### El núcleo, en código

Lo define el aplicativo y no se edita por evento: **identificador fiscal,
nombre, apellidos, correo, teléfono, centro y consentimiento**. Su tipo, su
obligatoriedad, su normalización y su validación están escritas en `src/Evt/`,
son las mismas para todos los eventos y se prueban sin WordPress.

Dos de esas siete no son campos cualesquiera, y van en la decisión porque la
medición dice que son los dos errores que **no** hay que repetir:

- **El centro se elige de un catálogo, por código, y nunca se teclea.** El
  catálogo maestro ya existe y ya está normalizado. Un centro tecleado es el
  mismo centro escrito de cinco maneras, y a partir de ahí no hay recuento, ni
  filtro, ni cruce que valga.
- **El consentimiento es una casilla, no un documento firmado que se sube**
  ([ADR-0020](ADR-0020-el-consentimiento-es-una-casilla.md)). En el sistema
  anterior el fichero era obligatorio: por eso estaba **siempre** presente y
  por eso **no probaba nada**. Saber si de verdad estaba firmado exigía abrir
  uno a uno todos los ficheros. Una casilla con constancia de qué texto se
  aceptó y cuándo dice más, y dice la verdad.

### Las preguntas del evento

Quien organiza añade, en el taller de su evento, **unas pocas preguntas**. Una
pregunta tiene exactamente cuatro cosas, y ninguna más:

| Parte | Qué es |
|---|---|
| **Rótulo** | El texto que lee quien se inscribe. |
| **Tipo** | Uno de cuatro: casilla, una opción, varias opciones, texto corto. |
| **Opciones** | La lista de respuestas posibles, cuando el tipo las lleve. |
| **Obligatoria** | Sí o no. |

**Lo que una pregunta no tiene, y es la mitad de la decisión:**

- **No tiene lógica condicional.** Ninguna pregunta aparece o desaparece según
  lo que se conteste en otra.
- **No tiene reglas de validación propias.** El tipo es toda la validación que
  hay: una opción de la lista es una de la lista, y un texto corto es un texto
  corto.

Esas dos ausencias son lo que separa una lista de preguntas de un constructor
de formularios. Si alguna vez se añaden, esto deja de ser la opción 4 y pasa a
ser la opción 2, que está descartada por escrito y con sus motivos.

### Lo que sale por el otro lado

La exportación y el filtro de la
[ADR-0027](ADR-0027-los-participantes-son-nuestros.md) mantienen sus columnas
declaradas en código para el núcleo, y añaden **una columna por pregunta del
evento**, con el rótulo por cabecera.

## Consecuencias

### Positivas

- Un evento nuevo **no necesita despliegue ni duplicar nada**: se crea, se le
  redactan sus tres o cuatro preguntas y se abre la inscripción. Se acaba el
  formulario nuevo por evento, que es el mecanismo que hacía crecer el problema
  con cada jornada.
- La parte que se repite en todos los eventos se escribe **una vez**, se valida
  una vez y se corrige una vez. Un error en el correo o en el identificador
  fiscal se arregla en un sitio, no en el formulario de cada evento.
- El núcleo es código puro: se prueba sin WordPress, como ya se prueban el
  filtro y la exportación.
- El centro deja de teclearse. Se acaban las cinco grafías del mismo centro, y
  con ellas la imposibilidad de contar.
- Las columnas del núcleo son estables, así que la pantalla de participantes y
  el CSV tienen una forma que sostener.
- La superficie a mantener es pequeña: cuatro tipos de campo y ninguna regla.

### Negativas

- **La raya entre núcleo y pregunta la decide quien escribe el código, y moverla
  cuesta una versión.** Una pregunta que acabe apareciendo en todos los eventos
  seguirá siendo una pregunta hasta que alguien la suba al núcleo: mientras
  tanto se vuelve a redactar en cada evento, con un rótulo distinto cada vez, y
  sale en una columna distinta del CSV en cada evento. Nadie lo detecta salvo
  mirando.
- **Las respuestas son datos que el código no entiende.** «Intolerancias
  alimentarias» es un dato de salud y aquí se guarda como el texto de una
  pregunta cualquiera, sin distintivo. El aplicativo no puede aplicarle una
  minimización ni un plazo de conservación propios porque no sabe qué hay
  dentro. Quien redacte las preguntas está decidiendo, sin que nada se lo
  advierta, qué categorías de datos personales se recogen.
- **Sin lógica condicional se ven preguntas que no aplican.** A quien no se
  queda a comer se le sigue enseñando la pregunta de las intolerancias. Es
  ruido para quien se inscribe y respuestas vacías o absurdas para quien
  gestiona. Se acepta a sabiendas: la alternativa es la opción 2.
- **Sin reglas de validación, el texto corto es texto.** Si alguien crea una
  pregunta «Teléfono de contacto», nadie garantiza que lo que llegue sea un
  teléfono. La validación de verdad solo existe en el núcleo.
- **«Lista corta» es criterio, no límite.** Nada en el mecanismo impide que un
  evento acumule cuarenta preguntas y reconstruya, a mano y peor, el formulario
  gigante del que se salía. La forma cerrada limita lo que una pregunta puede
  hacer, no cuántas hay.
- **Comparar entre eventos no se puede.** La misma pregunta escrita de dos
  maneras son dos columnas distintas, y no hay manera automática de saber que
  hablan de lo mismo. Cualquier informe que cruce eventos se hace a mano.
- **Cambiar una pregunta cuando ya hay inscritos deja respuestas colgando.**
  Retocar un rótulo o quitar una opción no reescribe lo ya contestado, y a
  partir de ahí la respuesta guardada y la pregunta que se lee dejan de
  coincidir. Cómo se evita eso —bloquear la edición al abrirse la inscripción,
  versionar la pregunta, o asumirlo— **no lo cierra esta ADR**.
- **El catálogo de centros pasa a ser una dependencia.** Si le falta un centro,
  o no está disponible cuando alguien se inscribe, no hay forma de teclearlo:
  esa persona no se puede inscribir. Se gana normalización y se pierde la
  válvula de escape.

### Neutras

- **Dónde se guardan las respuestas no lo decide esta ADR.** Aquí se fija la
  **forma** del formulario; el tipo de contenido que almacena una inscripción,
  su ciclo de vida y su migración son de la ADR de participación, que también
  es la que sustituirá a la
  [ADR-0007](ADR-0007-las-inscripciones-siguen-en-el-sistema-anterior.md).
- **No dice nada del aforo ni de la selección de talleres.** Elegir taller con
  plazas limitadas no es una pregunta del formulario: es otro mecanismo, con su
  concurrencia y su decisión propia.
- Las inscripciones de los eventos ya celebrados **no se tocan**: siguen donde
  están, con la forma que tuvieran, mientras esos eventos sigan vivos.
- Que el núcleo sean siete campos no lo congela para siempre: ampliarlo es una
  versión del aplicativo, como cualquier otro cambio de código, y esta ADR no
  lo prohíbe. Lo que fija es **quién** puede hacerlo y por qué vía.

## Referencias

- [ADR-0027](ADR-0027-los-participantes-son-nuestros.md) — los participantes se
  gestionan aquí y el sistema anterior no se lee; declara la forma de una fila.
- [ADR-0020](ADR-0020-el-consentimiento-es-una-casilla.md) — el consentimiento
  es una casilla con constancia de versión y sello de tiempo.
- [ADR-0007](ADR-0007-las-inscripciones-siguen-en-el-sistema-anterior.md) —
  dónde viven las inscripciones hoy; pendiente de ser sustituida.
- [ADR-0018](ADR-0018-el-taller-del-evento-es-una-sola-pantalla.md) — la
  pantalla desde la que se redactan las preguntas del evento.
- [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) — arquitectura de
  reemplazo.
- Medición sobre los formularios de inscripción del sistema anterior. El
  material de investigación no se versiona
  ([ADR-0030](ADR-0030-el-repositorio-se-publica-sin-nada-de-nadie.md)).
