---
id: ADR-0027
title: "Los participantes son nuestros: no se lee el sistema viejo"
status: Aceptada
date: 2026-09-14
related:
  issues: []
  prs: []
  sdds: [SDD-0001, SDD-0002]
  adrs: [ADR-0001, ADR-0007, ADR-0018, ADR-0020, ADR-0026]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0027: Los participantes son nuestros: no se lee el sistema viejo

## Estado

Aceptada (2026-09-14). Implementada a medias **y a propósito**:
`PublicFront/Participants` con el filtro y la exportación a CSV, y el panel
«Participantes» del taller. **El formulario de inscripción no está escrito**, y
hasta que lo esté esa pantalla no tiene de dónde sacar a nadie.

## Contexto

La pestaña «Participantes» ([ADR-0018](ADR-0018-el-taller-del-evento-es-una-sola-pantalla.md))
tiene que enseñar quién se ha inscrito, con **filtro y exportación a CSV**.

Lo primero que hubo que decidir fue de dónde salen esas filas, y la respuesta
obvia era la equivocada: en el sistema anterior la inscripción es un formulario
por evento de un plugin de terceros, con 2.500 entradas vivas
([ADR-0007](ADR-0007-las-inscripciones-siguen-en-el-sistema-anterior.md)), y lo fácil
era leerlo desde aquí.

**Se descartó por decisión de producto**: los participantes pasan a gestionarse
en este aplicativo, con formulario de inscripción propio. Lo del sistema
anterior es *legacy*, y lo legacy no se integra: se deja morir.

## Problema

¿De dónde saca la pantalla las inscripciones mientras el formulario propio no
exista, sin construir un puente al sistema que se sustituye?

## Factores de decisión

- **Lo que se pidió es el filtro y el CSV**, no el almacén. Eso es lo que tiene
  que funcionar y tiene que poder probarse.
- **El sistema anterior no se lee.** Ni con `class_exists()`, ni «solo para ver
  los datos»: un puente que funciona es un puente que se queda.
- **Sin `evt_registration` todavía.** Registrar el contenido de las
  inscripciones es la fase 2 y arrastra decisiones ajenas —protección de datos,
  CAS, catálogo de centros— que no se toman de pasada.
- **Una pantalla vacía tiene que decir la verdad.** «No se ha inscrito nadie» y
  «la inscripción está por construir» no son lo mismo.

## Alternativas consideradas

### Opción 1: leer el formulario del sistema anterior

Consultar sus entradas desde `src/Evt/`, con `class_exists()` para degradar.

- Pros: sale solo, sin nada que escribir después.
- Contras: **es un puente al sistema del que se quiere salir**, y los puentes
  que funcionan no se retiran. Mete el nombre de un plugin en el camino de
  producto, obliga a mapear campos por identificador numérico —el formulario
  original tiene 146, y el mapa es distinto en cada evento— y **no se puede
  probar**, porque ese plugin no está en el wp-env ni lo va a estar.
  Descartada.

### Opción 2: registrar ya el contenido de las inscripciones

Un CPT propio y su formulario, ahora.

- Pros: todo dentro, con una sola forma de leer, y la pantalla llena desde el
  primer día.
- Contras: es lo más grande que le queda al proyecto y arrastra lo que no
  decide este repositorio: qué se guarda y cuánto tiempo, cómo entra quien ya
  tiene cuenta, cómo se resuelve el centro, qué pone el consentimiento y cómo
  se evita el alta masiva. Hacerlo dentro de la pestaña habría sido decidir
  todo eso sin escribirlo. Descartada **ahora**, no en general: es el trabajo
  siguiente.

### Opción 3: declarar la forma y preguntar por las filas — ELEGIDA

El aplicativo dice **cómo es una inscripción** y pregunta por ellas con el
filtro `evt_participants`.

- Pros: el filtro y el CSV se escriben y se prueban hoy, enteros, sin depender
  de nada; la costura es la misma por la que entrarán las inscripciones propias
  cuando existan; y no se construye ningún puente al sistema viejo.
- Contras: **hasta que haya formulario propio, la pantalla está vacía.** Es una
  media pantalla, y hay que decirlo en ella.

## Decisión

**Los participantes son de este aplicativo.** Se gestionarán aquí, con
formulario de inscripción propio, y **el sistema anterior no se lee**.

Mientras ese formulario no exista, las filas entran por el filtro
`evt_participants`, que recibe la lista vacía y el evento. La **forma de una
fila la declara `Participants::columns()`**, y es el contrato:

| Clave | Columna |
|---|---|
| `name` | Nombre |
| `email` | Correo |
| `centre` | Centro |
| `workshop` | Taller |
| `date` | Fecha de inscripción |
| `consent` | Consentimiento |

Lo que llegue se normaliza al entrar (`Participants::clean()`): lo que sobra se
ignora, lo que falta sale vacío, y todo pasa a texto. El panel y el CSV
trabajan siempre con la misma forma.

**Lo que sí es del aplicativo, y es lo que se pidió**, es puro y no toca
WordPress:

- **El filtro** busca en todas las columnas a la vez y **no distingue tildes ni
  mayúsculas**: se teclea un apellido, un centro o un taller y sale. Acotar por
  taller es aparte, con un desplegable.
- **La exportación a CSV** exporta **lo filtrado, no la lista entera**, y va
  **por POST con nonce**: un enlace en GET que descarga la lista de personas
  inscritas es justo lo que no debe poder pegarse en un correo.

### El CSV se escribe para donde se va a abrir

Que es una hoja de cálculo de escritorio, no un editor de texto:

- **Punto y coma** y **BOM de UTF-8**, que es lo que abre bien en el Excel en
  español sin pasar por el asistente de importación. Con coma y sin BOM, un
  apellido con tilde sale roto y todo cae en la primera columna.
- Cada campo **entrecomillado**, con las comillas de dentro duplicadas
  (RFC 4180), y **CRLF**.
- Lo que empiece por `=`, `+`, `-` o `@` lleva **una comilla simple delante**:
  sin eso, una hoja de cálculo trata ese texto como fórmula, y un campo
  copiado de un formulario público se convierte en ejecución.

### El campo del formulario antiguo queda como histórico

`evt_signup_form_id` guarda el identificador del formulario de inscripción del
sistema anterior. **Se queda solo para los eventos migrados**, que tienen que
seguir viéndose igual, y:

- está **marcado como histórico** en la pantalla, con el aviso de no rellenarlo
  en un evento nuevo;
- **no se lee para nada más**;
- **es previsible que desaparezca**. Cuando no quede ningún evento vivo que lo
  use, se retiran la clave, su campo y la rama de `EventView::has_form()` que lo
  consulta.

### Cuando no hay nadie, la pantalla lo dice

No una tabla vacía —que se lee como «no se ha inscrito nadie»— sino que **el
formulario de inscripción está por construir**, y que eso no es un fallo del
evento. Si el evento viene del sistema anterior, se dice además que sus
inscripciones se consultan allí y que no se traen aquí.

En el wp-env sí contesta alguien: el mu-plugin de desarrollo
(`scripts/mu-plugins/evt-dev-tools.php`), que **nunca se despliega** y sirve
para ver funcionando la tabla, el filtro y la exportación.

## Consecuencias

### Positivas

- **El filtro y el CSV se prueban sin WordPress y sin ningún plugin**, que es
  lo que hace que se pueda confiar en ellos.
- **No hay puente al sistema viejo.** El día que se apague, no hay nada que
  desmontar aquí.
- La pantalla no cambia cuando llegue el formulario propio: lo que cambia es
  quién contesta al enganche.

### Negativas

- **La pestaña está vacía hasta que haya formulario de inscripción.** Es media
  funcionalidad entregada, y se dice en la propia pantalla en vez de disimularlo.
- **El aforo de un taller se cruza por el título**, porque no hay identificador
  común con lo que eligió quien se inscribió. Cambiarle el título a un taller ya
  empezado descuadra la cuenta. Se arregla solo cuando la inscripción sea
  nuestra y el taller viaje por identificador.
- **Quien conteste al enganche decide qué se ve.** El aplicativo normaliza la
  forma, no comprueba la procedencia.

### Neutras

- El consentimiento es una columna más ([ADR-0020](ADR-0020-el-consentimiento-es-una-casilla.md)):
  qué texto se aceptó y cuándo se guardará con la inscripción cuando la haya.
- No hay paginación: el evento mayor medido tiene 446 inscripciones y cabe en
  una tabla. Si aparece uno de miles, es lo primero que habría que mirar.
