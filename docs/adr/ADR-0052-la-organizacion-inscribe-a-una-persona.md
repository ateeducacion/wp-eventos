---
id: ADR-0052
title: "La organización inscribe a una persona, y declara su consentimiento"
status: Propuesta
date: 2026-11-17
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0020, ADR-0031, ADR-0043, ADR-0051]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0052: La organización inscribe a una persona, y declara su consentimiento

## Estado

Propuesta

## Contexto

La [ADR-0043](ADR-0043-las-inscripciones-se-corrigen-y-se-borran-sin-lista-de-admitidos.md)
dejó que la organización corrija y borre inscripciones desde «Participantes».
Lo que no puede es **crearlas**.

Quien organiza una jornada para personal con poco manejo del ordenador espera
una avalancha de llamadas de gente que no consigue inscribirse, y pregunta si
puede apuntarla desde aquí. Hoy la única respuesta es pedirle que lo intente
otra vez.

La inscripción lleva un consentimiento con su versión y su momento
([ADR-0020](ADR-0020-el-consentimiento-es-una-casilla.md)). Esa casilla la
marca la persona. Si la inscribe otra, la casilla no la ha marcado ella.

## Problema

¿Puede la organización crear una inscripción en nombre de alguien, y qué queda
del consentimiento?

## Alternativas consideradas

- **No dejarlo, y que la organización inscriba con los datos de la persona
  desde el formulario público.** Descartada: es lo mismo pero sin dejar rastro
  de quién lo hizo, y con la casilla marcada por quien no es.
- **Crearla sin consentimiento.** Descartada: es una inscripción sin la base
  que todas las demás tienen.
- **Que la organización declare el consentimiento, y quede escrito que lo
  declaró ella** (elegida).

## Decisión

En «Participantes», **«Inscribir a una persona»** abre el mismo panel lateral
que corregir, vacío. El núcleo y las preguntas se validan igual que al
inscribirse; los talleres pasan por el mismo candado, el mismo aforo y las
mismas reglas ([ADR-0050](ADR-0050-varios-talleres-por-persona-sin-repetir-ni-solapar.md)),
y el documento no puede estar ya inscrito
([ADR-0051](ADR-0051-correo-de-confirmacion-y-volver-a-la-inscripcion.md)).

Para guardarla, quien organiza **marca que la persona conoce y acepta** los
textos de protección de datos del evento. Se guarda la versión y el momento,
como siempre, y **quién la inscribió** (`evt_reg_by`). En la lista y en el CSV
el consentimiento dice «declarado por la organización».

Por defecto se le manda el correo con su enlace
([ADR-0051](ADR-0051-correo-de-confirmacion-y-volver-a-la-inscripcion.md)):
puede entrar a revisar sus datos y a elegir taller, y se entera de que está
inscrita. Se puede desmarcar.

Se puede inscribir a alguien aunque el plazo esté cerrado: es la organización.
Un evento histórico no se toca, como el resto del taller.

Las preguntas de archivo no se piden: los documentos los aporta la persona.

## Consecuencias

### Positivas

- La llamada de quien no sabe inscribirse se resuelve en la misma llamada.
- Queda escrito quién hizo cada inscripción que no hizo la persona.

### Negativas

- **El consentimiento declarado pesa menos que el marcado.** Si alguien lo
  reclama, la organización tiene que poder decir cómo lo obtuvo. El aplicativo
  solo guarda que lo declaró y quién.
- **Se puede inscribir a quien no lo ha pedido.** El correo que le llega es el
  aviso.

## Referencias

- `src/Evt/PublicFront/EventWorkspace.php` (`save_registration()`),
  `src/Evt/PublicFront/View/EventParticipantsPanel.php`,
  `tests/unit/test-registration-admin.php`.
