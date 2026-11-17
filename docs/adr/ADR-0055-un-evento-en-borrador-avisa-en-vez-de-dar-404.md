---
id: ADR-0055
title: "Un evento en borrador avisa en vez de dar 404"
status: Propuesta
date: 2026-11-17
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0042, ADR-0044]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0055: Un evento en borrador avisa en vez de dar 404

## Estado

Propuesta

## Contexto

El enlace de un evento que se está preparando circula antes de publicarse: se
comparte para revisarlo, o alguien lo encuentra. Quien no puede verlo recibe el
404 del tema: «No se encontró la página. La página que buscas no existe o se ha
movido». Es falso —existe— y no deja entrar a quien lo prepara.

Con la sesión iniciada pasa lo mismo si la cuenta no tiene permiso para ese
borrador, y entonces no hay forma de saber si falta la sesión, el permiso o el
evento.

El sistema anterior contestaba con una página corta que decía que el contenido
estaba en modo borrador y ofrecía un acceso para responsables.

## Alternativas consideradas

- **Dejar el 404.** Descartada: es lo que se reporta como fallo.
- **Mandar a iniciar sesión siempre.** Descartada: a quien no tiene cuenta le
  enseña un formulario que no le sirve, y no le dice que el evento se publicará.
- **Un aviso propio, con el acceso para responsables** (elegida).

## Decisión

`PublicFront/DraftNotice` atiende el 404 de un evento —o de una de sus
páginas— que existe y no está publicado (borrador, pendiente, programado o
privado; no la papelera). En vez del 404 del tema sirve una página corta:

- dice que el evento **todavía no se ha publicado y está en modo borrador**;
- sin sesión, que se informará cuando lo esté y un enlace de **acceso para
  responsables**, que vuelve al evento tras iniciar sesión;
- con sesión y sin permiso, **con qué cuenta se ha entrado** y que esa cuenta no
  puede verlo, con un enlace para entrar con otra.

No dice el título ni nada del evento: un borrador puede llevar lo que todavía
no se puede contar. Responde con 404 y `noindex`. El logo es el que el
despliegue configura en `evt_chrome` (`brand_logo`); si no hay, no sale.

Quien puede ver el borrador lo sigue viendo como siempre: el aviso solo aparece
donde antes había un 404.

## Consecuencias

### Positivas

- Quien recibe el enlace antes de tiempo sabe que el evento existe y se
  publicará.
- Quien lo prepara entra desde la misma página.
- «No tengo permiso» se distingue de «no he iniciado sesión» sin preguntar a
  nadie.

### Negativas

- Confirma que hay un evento en preparación en esa dirección. No dice cuál.
- Una página más que no usa el armazón del aplicativo: si cambia el aspecto del
  sitio, esta no lo sigue.

## Referencias

- `src/Evt/PublicFront/DraftNotice.php`, `tests/unit/test-draft-notice.php`.
- `src/Evt/PostType/EventPostType.php` (`draft_link()`: el enlace de un
  borrador es por número).
