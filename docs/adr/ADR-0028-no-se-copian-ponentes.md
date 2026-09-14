---
id: ADR-0028
title: "No se copian ponentes de otro evento: se vuelven a meter"
status: Aceptada
date: 2026-09-14
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0019, ADR-0025, ADR-0026]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0028: No se copian ponentes de otro evento

## Estado

Aceptada (2026-09-14). **Retira una promesa**, no añade código: el botón que
esta ADR descarta nunca llegó a escribirse.

## Contexto

La [ADR-0021](ADR-0021-el-ponente-pertenece-a-su-evento.md) decidió que el
ponente **pertenece a su evento**: no hay catálogo compartido, y cada evento
tiene sus fichas. La medición la sostiene —la repetición real entre eventos
está entre el 5 % y el 12 %— y el argumento que la cierra es que en un evento
terminado **no se quiere** que la foto y la biografía cambien solas.

Pero esa ADR dejó abierta una compensación: para las ediciones anuales
prometía un botón **«Copiar ponentes de otro evento»**, que traería fichas
nuevas reutilizando el adjunto de la foto. Quedó encolado y nunca se escribió.

Al construir la pestaña de Ponentes ([ADR-0026](ADR-0026-ponentes-y-actividades-cuelgan-del-evento.md))
tocaba escribirlo, y al mirarlo de cerca no se sostiene.

## Problema

¿Se copian los ponentes de otro evento, o se vuelven a meter?

## Decisión

**No se copian.** No habrá botón de copiar ponentes.

El motivo lo puso quien organiza los eventos, y es el mismo que zanjó lo de
duplicar eventos ([ADR-0025](ADR-0025-no-se-duplican-eventos.md)): **es más
fácil volver a meterlos**.

Y al concretarlo aparece lo que de verdad lo descarta:

- **Toda la fontanería para copiar, y al final hay que editar igual.** El
  cargo cambia, la entidad cambia, la biografía cita el curso pasado. Se copia
  para no teclear y se acaba tecleando encima de algo, que es más trabajo que
  teclear en un campo vacío.
- **Y lo que no se edita, rompe.** Una foto de otra edición en un evento con
  otra apariencia desentona; unos datos que ya no casan se publican con pinta
  de correctos. Es el mismo error silencioso de la ADR-0025: lo copiado que no
  se corrige es peor que lo que falta, porque nadie lo mira dos veces.
- **Un ponente son cuatro campos.** Nombre, cargo, entidad y unas líneas. El
  ahorro real de copiar es pequeño; el de no revisar lo copiado es negativo.

Se **pospone y casi se descarta**: no está en la lista de lo que falta por
hacer. Si algún día se mide que las ediciones anuales pierden tiempo de verdad
en esto, se reabre con una ADR nueva.

## Alternativas consideradas

### Opción 1: el botón que prometía la ADR-0021

Copiar fichas de otro evento, reutilizando el adjunto de la foto.

- Pros: lo prometido; y reutilizar el adjunto evita duplicar imágenes.
- Contras: los tres de arriba. Y uno más de implementación: reutilizar el
  adjunto **ata dos eventos por la foto**. Quien recorte o sustituya esa imagen
  en el evento nuevo se la cambia al viejo, que es exactamente lo que la
  ADR-0021 no quiere —«en un evento terminado no se quiere que la foto cambie
  sola»—. Copiar el adjunto en vez de reutilizarlo lo evita, y entonces la
  ventaja se va. Descartada.

### Opción 2: un catálogo de ponentes compartido

Fichas globales que los eventos enlazan.

- Ya descartada y medida en la [ADR-0021](ADR-0021-el-ponente-pertenece-a-su-evento.md).
  Aquí solo se deja anotado que **descartar el botón no la reabre**: el motivo
  de fondo sigue siendo el mismo.

### Opción 3: dejarlo encolado «para más adelante»

- Contras: una lista de pendientes con cosas que nadie va a hacer deja de
  servir para saber qué falta. Si la decisión es que no, se escribe que no.
  Descartada.

## Consecuencias

### Positivas

- **Una pantalla menos y ninguna ficha que arrastre datos de otra edición.**
- **La ADR-0021 queda entera y sin deuda.** Era su único punto sin implementar;
  ahora está decidido en vez de pendiente.
- Nadie tiene que mantener la regla de qué se copia y qué no, ni decidir qué
  pasa con la foto compartida.

### Negativas

- **Quien monta la quinta edición vuelve a escribir a sus ponentes.** Son
  cuatro campos por persona, y se acepta a sabiendas.
- **Se retira algo prometido por escrito.** Quien leyó la ADR-0021 y contaba
  con ese botón se encuentra con que no está. Por eso se escribe esta ADR y no
  se borra la frase de aquella.

### Neutras

- La foto se sigue eligiendo de la biblioteca de medios, así que una imagen ya
  subida se reutiliza sin copiar la ficha: lo que no hay es un botón que lo
  haga por usted.
