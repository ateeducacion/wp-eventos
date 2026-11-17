---
id: ADR-0050
title: "Varios talleres por persona, sin repetir taller ni solaparse"
status: Aceptada
date: 2026-11-17
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0024, ADR-0033, ADR-0043]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0050: Varios talleres por persona, sin repetir taller ni solaparse

## Estado

Aceptada (2026-11-17), al cotejar los estados con `main` en la v0.2.1.
Implementada en `src/Evt/Domain/WorkshopChoice.php` y `Registrations::seat()`;
pruebas en `tests/unit/test-workshop-choice.php`. Salió en la v0.2.0.

## Contexto

La [ADR-0033](ADR-0033-elegir-taller-aforo-duro-y-cambio-hasta-el-cierre.md)
lo dejó escrito entre sus consecuencias negativas: «se elige un taller, no
varios. La columna `workshop` es una. Una jornada con talleres en tres franjas
no cabe en este modelo, y esa jornada existirá. Cuando llegue, es otra ADR».

Ha llegado. Un área prepara una jornada de un día, repetida en dos islas, con
una tarde de talleres de hora y cuarto en **dos franjas**. Cada persona hace
**dos talleres distintos**, y cada taller se imparte **dos veces**, una en cada
franja, con la misma persona dando las dos sesiones. El aforo es por sesión.

Lo que pide quien organiza son dos cosas: que nadie se apunte a las dos
sesiones del mismo taller —le quitaría la plaza a otra persona y se quedaría
sin hacer el segundo— y que nadie elija dos talleres que coinciden en hora.
Hasta hoy eso se hacía con una leyenda que pedía no hacerlo, y revisando a
mano después.

Hoy (`src/Evt/Meta/RegistrationMetaKeys.php`, `REG_WORKSHOP`) la inscripción
guarda **un** identificador de actividad, y
`src/Evt/PublicFront/Registrations.php` (`seat()`) toma o suelta esa única
plaza dentro del candado del evento.

## Problema

¿Cómo elige una persona varios talleres, y cómo sabe el aplicativo que dos
actividades son «el mismo taller» y que dos se solapan, sin convertir la
pantalla del programa en un modelo de franjas y sesiones?

## Factores de decisión

- **La organización no tiene que aprender nada nuevo.** Hoy un taller que se
  repite ya se escribe como dos actividades con el mismo título.
- **El candado de la ADR-0033 no se toca.** Sigue siendo uno por evento y
  alrededor de toda la operación.
- **Lo guardado no se migra.** Hay inscripciones con un taller guardado como
  número.
- **La regla tiene que poder probarse sin WordPress**, como el resto del
  dominio.

## Alternativas consideradas

### Opción A: franjas y sesiones explícitas

Declarar las franjas del evento, colgar de cada taller sus sesiones, y elegir
una sesión por franja.

- Pros: el modelo dice exactamente lo que pasa.
- Contras: dos pantallas nuevas, un tipo de dato más y una relación entre
  actividades que hay que mantener. Para la casuística real —dos franjas, un
  taller repetido— es mucho más de lo que hace falta. **Descartada**: es lo
  que se propuso primero y se rechazó en la reunión por complejo.

### Opción B: un campo «es otra sesión de» en la actividad

- Pros: la relación es explícita, no depende de cómo se escriba el título.
- Contras: un campo más que rellenar, y que se olvida. **Descartada**.

### Opción C: mismo título = mismo taller, y las horas dicen si se solapan (elegida)

- Pros: nada que rellenar; el título ya es lo que ve la persona; las horas ya
  están en cada actividad.
- Contras: dos talleres distintos que se llamen igual se tratarían como uno.

## Decisión

**Un evento dice cuántos talleres puede elegir cada persona** (meta
`evt_workshop_max`; sin valor, uno, que es lo de antes). Y dos reglas se
aplican siempre, se elijan los que se elijan:

1. **No se eligen dos actividades con el mismo título**, comparado sin
   mayúsculas, sin tildes y con los espacios en uno. Son sesiones del mismo
   taller.
2. **No se eligen dos actividades del mismo día cuyas horas se pisan.** Sin
   hora de inicio no se sabe, y lo que no se sabe no se prohíbe.

Las reglas viven en `src/Evt/Domain/WorkshopChoice.php`, puras, y
`Registrations::seat()` las aplica **dentro del candado**, junto con el aforo.
Lo que se pide al guardar es la **elección entera**; solo se cuentan plazas de
lo que se añade, y lo que la persona ya tenía lo conserva aunque se haya
llenado después, como decía la ADR-0033.

`evt_reg_workshop` guarda ahora una lista separada por comas. Un número suelto
—lo guardado antes— se lee como una lista de uno: no hay migración.

En la columna «Taller» de participantes y en el CSV van los talleres separados
por ` | `. Las sesiones de un taller repetido se distinguen añadiéndoles el día
y la hora; los demás salen solo con su título.

El formulario dice las reglas siempre, con las mismas palabras, y añade encima
las instrucciones que escriba la organización (`evt_workshop_help`). Con un
taller por persona sigue siendo una lista de opciones con «No elijo taller»;
con más, casillas.

## Consecuencias

### Positivas

- La jornada de dos franjas con talleres repetidos cabe sin pantallas nuevas.
- Elegir dos sesiones del mismo taller deja de ser algo que se revisa a mano:
  no se puede.
- Las inscripciones de antes siguen funcionando sin tocar la base de datos.

### Negativas

- **Dos talleres distintos con el mismo título se tratan como uno.** Se
  resuelve titulándolos distinto, y la pantalla de programa lo explica.
- **Escribir mal el título de una sesión rompe la regla 1**: «Primeros
  auxilios» y «Primeros auxilio» son dos talleres. La regla 2 sigue impidiendo
  coger los dos si coinciden en hora, pero no si van en franjas distintas.
- **El tope es por evento, no por franja.** Con dos talleres por persona,
  alguien puede elegir dos de la misma franja si no se solapan exactamente
  —por ejemplo, uno que acaba cuando empieza el otro—. Es lo que se pidió.
- La columna «Taller» deja de ser un valor y pasa a ser una lista en texto: un
  título con `|` se partiría al filtrar.

### Neutras

- El candado sigue siendo uno por evento; cambiar de dos talleres a otros dos
  es atómico igual que cambiar uno.
- La [ADR-0033](ADR-0033-elegir-taller-aforo-duro-y-cambio-hasta-el-cierre.md)
  sigue en vigor en todo lo demás.

## Referencias

- [ADR-0033](ADR-0033-elegir-taller-aforo-duro-y-cambio-hasta-el-cierre.md) —
  aforo duro y candado por evento.
- [ADR-0024](ADR-0024-un-dia-puede-tener-dos-sedes.md) — la sede y la hora son
  de cada actividad.
- `src/Evt/Domain/WorkshopChoice.php`, `tests/unit/test-workshop-choice.php`.
