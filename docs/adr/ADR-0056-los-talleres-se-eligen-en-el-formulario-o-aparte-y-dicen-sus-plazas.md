---
id: ADR-0056
title: "Los talleres se eligen en el formulario o aparte, y cada uno dice sus plazas libres"
status: Propuesta
date: 2026-11-18
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0033, ADR-0050, ADR-0051]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0056: Los talleres se eligen en el formulario o aparte, y cada uno dice sus plazas libres

## Estado

Propuesta

## Contexto

Hasta ahora, con el plazo de talleres abierto, los talleres salían **siempre**
en el formulario de inscripción y, después, en la inscripción ya hecha, para
cambiarlos ([ADR-0033](ADR-0033-elegir-taller-aforo-duro-y-cambio-hasta-el-cierre.md)).

Hay jornadas en que la organización quiere separar los dos pasos: primero se
inscribe la gente y, cuando el programa está cerrado, cada persona elige sus
talleres con el enlace de su correo
([ADR-0051](ADR-0051-correo-de-confirmacion-y-volver-a-la-inscripcion.md)).
Con el plazo de talleres cerrado al inscribirse se conseguía a medias: el
formulario no los enseñaba, pero tampoco decía que se elegirían después.

Además, el número de plazas libres solo salía en los talleres con aforo, como
un paréntesis discreto, y en el taller propio contaba la plaza de la propia
persona como ocupada sin decírselo.

## Alternativas consideradas

- **Dejarlo en manos de las fechas del plazo de talleres.** Descartada: no se
  puede abrir el plazo para quien ya está inscrito sin que salga también en el
  formulario.
- **Una página de elección de talleres aparte, como sección del evento.**
  Descartada: la elección ya vive en la inscripción, a la que se vuelve con el
  enlace o con la cuenta; otra página sería otra puerta que mantener.
- **Un ajuste: en el formulario o aparte** (elegida).

## Decisión

En «Formulario y plazos», **«Dónde se eligen los talleres»**
(`evt_workshop_where`):

- **En el formulario de inscripción** (`inscripcion`, por defecto): como hasta
  ahora.
- **Aparte, después de inscribirse** (`aparte`): el formulario no lleva
  talleres y lo dice; se eligen desde la inscripción ya hecha —con el enlace
  del correo o con la cuenta— mientras el plazo de talleres esté abierto. Un
  taller que llegue en el envío del formulario se ignora: lo que manda es el
  servidor, no lo que pinte la página.

Cada taller lleva un **contador** en los dos sitios:

- «Quedan N de M plazas»; «Queda 1 plaza de M», resaltado cuando quedan pocas.
- En el taller propio, «Usted ocupa una de las M plazas».
- Sin aforo, «Sin límite de plazas».

El contador informa; el control sigue siendo el candado al guardar
([ADR-0033](ADR-0033-elegir-taller-aforo-duro-y-cambio-hasta-el-cierre.md)).

## Consecuencias

### Positivas

- La organización decide si la inscripción y la elección de talleres son un
  paso o dos.
- Quien elige ve cuánto margen tiene antes de decidir.

### Negativas

- Con «aparte», quien se inscribe y no vuelve se queda sin taller: depende de
  que abra el enlace del correo.
- El contador es el de cuando se pintó la página: puede haber cambiado al
  guardar, y entonces el aviso de taller lleno lo explica.

## Referencias

- `src/Evt/PublicFront/SignupForm.php` (`workshop_where()`,
  `workshops_in_form()`), `src/Evt/PublicFront/Block/SignupBlock.php`
  (`seats()`, `workshops_later()`), `src/Evt/PublicFront/View/EventSignupPanel.php`.
- `tests/unit/test-signup-form.php`, `tests/unit/test-event-workspace.php`.
