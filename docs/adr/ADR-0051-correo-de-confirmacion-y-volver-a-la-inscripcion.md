---
id: ADR-0051
title: "Correo de confirmación con el enlace, volver con la cuenta y un documento por evento"
status: Aceptada
date: 2026-11-17
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0030, ADR-0032, ADR-0033, ADR-0039, ADR-0048]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0051: Correo de confirmación con el enlace, volver con la cuenta y un documento por evento

## Estado

Aceptada (2026-11-17), al cotejar los estados con `main` en la v0.2.1.
Implementada en `src/Evt/PublicFront/SignupMail.php` y
`Registrations::of_user()`; pruebas en `tests/unit/test-signup-return.php`.
Salió en la v0.2.0.

## Contexto

La [ADR-0033](ADR-0033-elegir-taller-aforo-duro-y-cambio-hasta-el-cierre.md)
decidió que se vuelve a la propia inscripción con un **testigo** que «viaja en
el enlace del correo de confirmación», y dejó sin decidir el correo: «cómo se
manda, con qué plantilla y qué pasa si rebota es una decisión que no está
tomada».

Sin decidir, no existe: `src/Evt` no llamaba a `wp_mail()` en ningún sitio. El
enlace solo aparecía en la redirección que sigue a inscribirse, y quien cerraba
esa pestaña no tenía forma de volver. Con la elección de taller en un plazo
posterior al de inscripción —el caso que la propia ADR-0033 prevé—, eso deja a
la gente sin poder elegir.

Dos huecos más, del mismo sitio:

- **Con sesión**, la inscripción no quedaba unida a la cuenta
  ([ADR-0048](ADR-0048-con-sesion-la-inscripcion-toma-los-datos-de-la-cuenta.md)
  toma de ella los datos, pero no guarda quién era). Volver pedía igualmente el
  enlace.
- **Nada impedía inscribirse dos veces** con el mismo documento. El sistema
  anterior sí lo impedía —es lo único que limitaban sus formularios, según la
  [ADR-0043](ADR-0043-las-inscripciones-se-corrigen-y-se-borran-sin-lista-de-admitidos.md)—,
  y con plazas de taller, una inscripción repetida ocupa dos.

## Problema

¿Cómo recupera una persona su inscripción para elegir o cambiar taller, con y
sin sesión, sin que eso sirva para leer la de otra? ¿Y qué se hace con un
documento que ya está inscrito?

## Factores de decisión

- **El repositorio es público**
  ([ADR-0030](ADR-0030-el-repositorio-se-publica-sin-nada-de-nadie.md)): ni
  remitente, ni dirección, ni nombre de nadie en el código.
- **Un dato que cualquiera teclea no es una credencial** (ADR-0033). El correo
  escrito en un formulario no puede abrir una inscripción.
- **El correo falla**, y una inscripción guardada no puede depender de eso.
- **No hay cola ni servidor de correo propio**: es WordPress, con lo que tenga
  configurado el sitio.

## Alternativas consideradas

- **Correo en HTML con plantilla.** Descartada: hay que escapar la plantilla,
  trae imágenes remotas y cada programa de correo lo pinta a su manera. El
  contenido es una frase, unos datos y un enlace.
- **Recuperar la inscripción tecleando el documento.** Descartada: es leer la
  inscripción de otra persona sabiendo su DNI.
- **Recuperar tecleando el correo y enseñando la inscripción en pantalla.**
  Descartada por lo mismo. Lo que se hace es **mandar** el enlace a ese correo,
  que solo lo lee su dueño.

## Decisión

**Un correo en texto plano, una sola salida.** `src/Evt/PublicFront/SignupMail.php`
es lo único que llama a `wp_mail()`. Manda la confirmación con el enlace
personal en tres casos: al inscribirse, cuando la organización inscribe a
alguien ([ADR-0052](ADR-0052-la-organizacion-inscribe-a-una-persona.md)) y
cuando la persona pide de nuevo el enlace. El remitente es el de WordPress; el
filtro `evt_mail` deja cambiar el correo entero o no mandarlo (`false`).

**Si el correo no sale, la inscripción se queda.** La pantalla a la que se
vuelve dice que no se pudo enviar y que guarde la dirección de esa página, que
es el enlace.

**Recuperar el enlace** es un formulario plegado al final de la página de
inscripción —también con la inscripción cerrada—: se escribe el correo y, si
hay una inscripción con él en ese evento, se le manda el enlace **a esa
dirección**. La respuesta en pantalla es la misma haya o no inscripción, para
que no sirva para averiguar quién está inscrito. Cada inscripción recibe como
mucho un reenvío cada diez minutos.

**Con sesión, la cuenta encuentra su inscripción.** Al inscribirse con sesión se
guarda la cuenta (`evt_reg_user`). Con sesión, la inscripción propia es la que
hizo esa cuenta y, si no hay, la que lleva el correo de la cuenta —la que hizo
la organización, o la que se hizo sin sesión—. El correo de una cuenta de
WordPress no cambia sin confirmarlo desde la dirección nueva, y por eso vale
aquí como prueba y en un formulario no.

**Un documento, una inscripción por evento.** Se comprueba **dentro del candado
del evento**, junto con crearla, para que un doble clic no cree dos. Al
corregir una inscripción tampoco se le puede poner el documento de otra.

## Consecuencias

### Positivas

- Se puede volver a la inscripción días después, que es lo que necesita un
  plazo de talleres posterior.
- Con sesión no hace falta el correo para nada.
- Una persona, una plaza.

### Negativas

- **El enlace sigue siendo una credencial en un correo** (ya lo decía la
  ADR-0033): quien lo reenvíe da acceso a su inscripción. El correo lo dice.
- **El envío es síncrono**: un servidor de correo lento hace lenta la
  inscripción.
- **Un correo que no llega no deja rastro.** No hay registro ni reintento; si
  hace falta, se engancha a `wp_mail_failed` o al filtro.
- **Quien se equivocó de documento al inscribirse** bloquea ese documento en el
  evento hasta que la organización lo corrija o lo borre.
- **Encontrar por el correo de la cuenta** une a quien tenga esa dirección con
  esa inscripción. Si la organización escribe mal el correo de alguien, lo
  corrige y vuelve a enviarle el enlace.

### Neutras

- Lo que se manda en masa —avisos, despedidas— no lo hace el aplicativo: se
  exporta la lista a CSV y se escribe desde el correo de la organización.

## Referencias

- [ADR-0033](ADR-0033-elegir-taller-aforo-duro-y-cambio-hasta-el-cierre.md) —
  el testigo y el candado.
- `src/Evt/PublicFront/SignupMail.php`, `tests/unit/test-signup-return.php`.
