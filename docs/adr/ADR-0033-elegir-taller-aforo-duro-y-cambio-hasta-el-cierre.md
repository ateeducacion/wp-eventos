---
id: ADR-0033
title: "Elegir taller: aforo duro, cambio hasta el cierre y un solo candado por evento"
status: Aceptada
date: 2026-09-14
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0024, ADR-0026, ADR-0027, ADR-0031, ADR-0032]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0033: Elegir taller: aforo duro, cambio hasta el cierre y un solo candado por evento

## Estado

Aceptada (2026-09-14).

## Contexto

La [ADR-0031](ADR-0031-el-formulario-de-inscripcion-es-nucleo-fijo-mas-preguntas.md)
apartó esto a propósito, y con razón: «elegir taller con plazas limitadas no es
una pregunta del formulario: es otro mecanismo, con su concurrencia y su
decisión propia». Esta es esa decisión.

Lo que ya existe y no se toca:

- Una actividad tiene **aforo** (`ProgrammeMetaKeys::ACTIVITY_SEATS`) y el
  taller lo enseña junto a la ocupación (`EventWorkspace::seats_taken()`). Hoy
  es **informativo**: nadie puede elegir nada.
- Un día puede tener **dos sedes**
  ([ADR-0024](ADR-0024-un-dia-puede-tener-dos-sedes.md)), así que dos talleres
  simultáneos no son un caso raro: son el caso.
- Una inscripción es un `evt_registration` colgado del evento
  ([ADR-0032](ADR-0032-la-inscripcion-es-un-contenido-del-evento.md)), y la fila
  que ve quien gestiona ya tiene una columna `workshop`
  ([ADR-0027](ADR-0027-los-participantes-son-nuestros.md)).

Y lo que hace esto distinto de todo lo demás del aplicativo: **es lo único que
dos personas pueden hacer a la vez sobre el mismo dato**. Un evento lo edita
quien organiza, de uno en uno, y para eso está el bloqueo de edición. Una plaza
de taller la piden doscientas personas el día que se abre la inscripción, y la
última la piden dos a la vez. Eso no es una hipótesis de manual: es lo que pasa
el primer minuto.

El sistema anterior lo resolvía copiando el mismo algoritmo de control de aforo
una vez por evento, con las constantes de cada formulario duplicado dentro. De
todas las copias, solo una estaba activa. Ese es exactamente el patrón que este
repositorio existe para no repetir.

## Problema

¿Cuándo elige taller quien se inscribe, qué pasa cuando no quedan plazas, puede
cambiar después, y cómo se evita que dos personas se lleven la misma última
plaza?

## Factores de decisión

- **El aforo es un número que alguien ha calculado.** Un aula tiene las sillas
  que tiene. Si el mecanismo permite pasarse, el número deja de significar nada
  y quien organiza vuelve a llevar la cuenta a mano.
- **No hay sesión de usuario.** Quien se inscribe no tiene cuenta en el sitio y
  no la va a tener ([ADR-0032](ADR-0032-la-inscripcion-es-un-contenido-del-evento.md),
  opción 2 descartada). Volver a su inscripción tiene que funcionar sin iniciar
  sesión y sin abrirle la puerta a nadie más.
- **Cambiar de taller son dos operaciones**: soltar la plaza vieja y tomar la
  nueva. Si se hacen por separado, existe el instante en el que alguien se queda
  sin ninguna de las dos.
- **WordPress no da transacciones a mano.** `update_post_meta()` no es un
  `SELECT … FOR UPDATE`, y leer, contar y escribir desde PHP es la carrera de
  libro.
- **La escala es la de una jornada**, no la de una taquilla de conciertos:
  centenares de personas, no millones. La solución tiene que ser correcta a esa
  escala, no rápida a otra.
- **Una copia por evento está prohibida.** Sea lo que sea, es una sola pieza que
  sirve para todos los eventos.

## Alternativas consideradas

### El aforo

#### Opción A: se permite pasarse y quien organiza reconcilia

- Pros: nunca falla nada al guardar; quien organiza decide caso por caso.
- Contras: el aforo pasa a ser un adorno, y la reconciliación es trabajo manual
  que aparece siempre en el peor momento. **Descartada.**

#### Opción B: lista de espera

- Pros: nadie se queda fuera del todo; se aprovecha cada baja.
- Contras: hay que llevar orden de llegada, avisar a quien promociona, decidir
  cuánto tiempo tiene para confirmar y qué pasa si no contesta. Es una máquina
  entera, y nadie la ha pedido. **Descartada** —y si algún día hace falta, se
  añade encima de lo que aquí se decide, no en su lugar—.

#### Opción C: aforo duro (elegida)

El taller lleno **no se puede elegir**: desaparece de las opciones. Quien llega
tarde elige otro.

- Pros: el número significa lo que dice, y el mecanismo es pequeño.
- Contras: quien llega tarde se queda fuera sin más recurso que mirar si alguien
  se cae.

### Los cambios

#### Opción D: la elección es definitiva

- Pros: nada que soltar, ninguna carrera al cambiar, superficie mínima.
- Contras: el error de un clic lo arregla quien organiza, a mano, uno a uno.
  **Descartada.**

#### Opción E: se puede cambiar hasta que cierre el plazo (elegida)

- Pros: quien se equivoca se corrige solo; quien organiza no hace de
  administrativo.
- Contras: hay que dejar volver a la inscripción sin sesión, y el cambio es la
  operación con la carrera dentro.

### La concurrencia

#### Opción F: contar en PHP y escribir

Leer las inscripciones, contar las de ese taller, comparar con el aforo y
guardar.

Es la carrera de libro: dos peticiones leen 19 de 20 y las dos escriben.
**Descartada**, y se escribe aquí precisamente para que no vuelva por parecer lo
natural.

#### Opción G: un candado por actividad

Un candado por cada taller, tomado antes de contar.

- Pros: dos talleres distintos no se estorban.
- Contras: **un cambio toca dos talleres**, así que hay que tomar dos candados,
  y dos candados tomados en distinto orden por dos peticiones es un abrazo
  mortal. Evitarlo pide ordenarlos siempre igual: correcto, pero es un detalle
  que se olvida y que no falla hasta el día que falla. **Descartada.**

#### Opción H: un solo candado por evento (elegida)

Un candado por evento, tomado alrededor de **toda** la operación —contar, soltar
la plaza vieja, tomar la nueva—.

- Pros: el cambio es atómico de una pieza; no hay dos candados, así que no hay
  abrazo mortal; una sola pieza para todos los eventos.
- Contras: serializa las elecciones de un mismo evento.

## Decisión

**Aforo duro, cambio hasta que cierre el plazo, y un único candado por evento
alrededor de la operación entera.**

### Cuándo se elige

La selección de talleres **tiene su propio plazo**, con su fecha de apertura y
de cierre en el evento. Si está abierta cuando alguien se inscribe, elegir es un
paso más del formulario; si se abre después, quien ya estaba inscrito entra a
elegir cuando se abra. Es el mismo mecanismo en los dos casos, y por eso no hay
dos caminos que probar.

### Cómo se vuelve sin tener cuenta

Al inscribirse se genera un **testigo de un solo uso por inscripción**, largo y
aleatorio, que viaja en el enlace del correo de confirmación. Ese enlace abre la
inscripción de esa persona y nada más. No es una sesión, no da acceso a ninguna
otra cosa del sitio, y se puede volver a emitir.

Lo que **no** se hace: identificar a alguien por su correo o por su
identificador fiscal escritos en un formulario. Un dato que cualquiera puede
teclear no es una credencial, y con eso se leen las inscripciones ajenas.

### El aforo, exactamente

Un taller con el aforo cubierto **no aparece como elegible**. Quien lo tuviera
ya elegido lo conserva: llenarse no echa a nadie. Un aforo de cero significa
«sin límite», que es lo que hace hoy `EventWorkspace::seats_taken()` y no hay
razón para cambiarlo.

Que no aparezca en la pantalla **no es el control**: la pantalla se puede haber
pintado hace cinco minutos. La comprobación que manda es la que se hace dentro
del candado, al guardar. Quien llegue tarde recibe un aviso de que esa plaza ya
no está y vuelve a elegir, con la lista al día.

### El candado

Un candado con nombre, **uno por evento**, alrededor de la operación completa:

1. Tomar el candado.
2. Contar las plazas ocupadas del taller que se pide.
3. Si no caben, soltar y avisar. **No se escribe nada.**
4. Si caben, soltar la plaza anterior —si la había— y escribir la nueva.
5. Soltar el candado.

Se toma con la **inserción atómica de una opción**: `add_option()` falla si el
nombre ya existe, porque la columna es única en la base de datos, y eso lo
convierte en un candado sin escribir SQL propio. Lleva **sello de tiempo y
caducidad corta**: una petición que muera a mitad no deja el evento bloqueado
para siempre.

Los pasos 3 y 4 están dentro del mismo candado a propósito: **es lo que impide
que alguien se quede sin ninguno de los dos talleres**. O el cambio entero o
nada.

`evt_session` **no se registra**. Una elección de taller es el identificador de
la actividad guardado en la inscripción; no necesita un tipo de contenido
propio, y ya que la columna `workshop` de
[ADR-0027](ADR-0027-los-participantes-son-nuestros.md) es una sola, tampoco
necesita una tabla de relación.

## Consecuencias

### Positivas

- **El aforo vuelve a significar lo que dice.** Nadie cuenta a mano, y no hay
  que reconciliar nada después.
- **Una sola pieza para todos los eventos.** Se acaba el algoritmo de aforo
  copiado una vez por evento, que es el patrón del que se sale.
- El cambio de taller es **todo o nada**: no existe el estado intermedio en el
  que alguien se ha quedado sin plaza.
- Quien se equivoca se corrige solo mientras haya plazo, y quien organiza no
  hace de administrativo.
- Un candado y no dos: no hay orden de adquisición que recordar, así que no hay
  abrazo mortal que depurar a las tres de la mañana.
- El enlace con testigo no abre nada más del sitio, y se puede reemitir.

### Negativas

- **Las elecciones de un mismo evento se serializan.** Con centenares de
  personas es imperceptible; el día que un evento sea mucho mayor, el candado es
  el cuello de botella, y se verá antes en la espera que en un error. Está
  escrito aquí para que quien lo mida sepa dónde mirar. *(`ponytail`: candado por
  evento; si hiciera falta, candado por actividad con orden fijo de
  adquisición.)*
- **Quien llega tarde se queda fuera y no hay lista de espera.** Si alguien se
  cae, la plaza vuelve a estar libre sin avisar a nadie: se la lleva quien pase
  por allí. Es la consecuencia directa de descartar la opción B.
- **El testigo es una credencial en un correo.** Quien reenvíe ese correo está
  dando acceso a su inscripción. Es la contrapartida de no pedir cuenta, y no
  hay manera de tenerlo todo.
- **Un candado colgado bloquea las elecciones de ese evento hasta que caduca.**
  La caducidad corta acota el daño; no lo elimina.
- **Se elige un taller, no varios.** La columna `workshop` es una. Una jornada
  con talleres en tres franjas no cabe en este modelo, y esa jornada existirá.
  Cuando llegue, es otra ADR —y probablemente el fin de la columna única—.
- **La pantalla puede mentir.** Entre que se pinta la lista y se pulsa, la plaza
  puede haberse ido. El aviso al guardar es correcto pero llega tarde, y quien
  lo recibe ya se había hecho a la idea.
- **Un aforo que se baja por debajo de lo ya ocupado no echa a nadie**, así que
  el taller queda con más gente que plazas y el número vuelve a mentir, esta vez
  por decisión de quien organiza. El aplicativo lo avisa; no lo impide.

### Neutras

- **Los correos de confirmación no los decide esta ADR.** Que haya que mandar un
  enlace implica que hay que mandar un correo, y cómo se manda, con qué
  plantilla y qué pasa si rebota es una decisión que no está tomada.
- **Quien organiza puede cambiar la elección de alguien** desde la pestaña de
  participantes, y pasa por el mismo candado: no hay una segunda vía que se
  salte la comprobación.
- Que hoy el aforo se enseñe y no se aplique deja de ser cierto en cuanto esto
  se implemente; hasta entonces, la pantalla de talleres sigue siendo
  informativa.

## Referencias

- [ADR-0032](ADR-0032-la-inscripcion-es-un-contenido-del-evento.md) — dónde vive
  una inscripción.
- [ADR-0031](ADR-0031-el-formulario-de-inscripcion-es-nucleo-fijo-mas-preguntas.md)
  — qué lleva el formulario, y por qué esto no es una pregunta suya.
- [ADR-0027](ADR-0027-los-participantes-son-nuestros.md) — la columna `workshop`
  de la fila de participantes.
- [ADR-0024](ADR-0024-un-dia-puede-tener-dos-sedes.md) — dos sedes el mismo día,
  que es lo que hace que haya talleres simultáneos.
- `ProgrammeMetaKeys::ACTIVITY_SEATS` y `EventWorkspace::seats_taken()` — el
  aforo y su recuento, hoy informativos.
