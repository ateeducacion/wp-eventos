---
id: REGISTRO-SDD
title: "Índice de estados de los SDD"
status: Aceptada
date: 2026-09-12
related:
  issues: []
  prs: []
  adrs: []
  sdds: [SDD-0001, SDD-0002]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# Índice de SDD

La tabla es el registro único de estados; no se duplican listas por estado.
Ver la [guía](README.md) y la [plantilla](plantilla.md).

Los dos SDD se leen en pareja y ninguno sustituye al otro todavía:
**SDD-0001** describe el sistema que hoy está en producción y **SDD-0002** el
que va a reemplazarlo. SDD-0001 no es un diseño propuesto sino un histórico
vivo: mientras el sitio que se sustituye siga funcionando con su formulario, la
vista que lo interpola y la taxonomía `convocatoria`, ese documento sigue
describiendo la realidad y se mantiene. Se pasará a `Sustituida` el día en que
el aplicativo tome el relevo, no antes.

El diseño vigente y los pendientes abiertos se consultan en **SDD-0002**; las
decisiones que lo sostienen están en el [índice de ADR](../adr/registro.md).

En la revisión documental del 2026-09-12 los dos SDD se cotejaron contra las
catorce ADR y contra el material de `.local/`. Se corrigieron tres cifras de
SDD-0001 —el número de inscripciones, cuántas vistas sirven a un solo evento y
cuántas áreas hay—: las tres están ahora medidas sobre ese material, y ahí, en
`.local/`, es donde se quedan. En SDD-0002 se corrigió la etiqueta de la fase
de inscripciones, que ahora es la **3** y no la 2. El detalle está en el
[índice de ADR](../adr/registro.md).

| ID | Contenido | Estado | Sustituido por |
|----|-----------|--------|----------------|
| `.local/` (investigación del sistema anterior) | Cómo funciona hoy el sistema de eventos: el formulario, la vista que interpola sus páginas, la taxonomía `convocatoria` y los roles de área | Aceptada (histórico vivo) | — |
| [SDD-0002](SDD-0002-arquitectura-de-reemplazo.md) | El aplicativo que lo sustituye: CPT `evt_event` jerárquico, tres taxonomías, ámbito por área | Aceptada | — |
