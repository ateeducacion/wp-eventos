---
id: ADR-0025
title: "No se duplican eventos: se tarda menos en crearlo que en corregir la copia"
status: Aceptada
date: 2026-09-14
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0016, ADR-0021]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0025: No se duplican eventos

## Estado

Aceptada

## Contexto

Muchos de estos eventos van por ediciones: unas jornadas que se repiten cada
curso, un encuentro que va por el segundo o el tercero. La tentación evidente es
un botón de **«duplicar evento»** que traiga las secciones, la apariencia, el
programa, los ponentes y los talleres de la edición anterior.

La pregunta quedó abierta al diseñar la pantalla del programa: si se duplica,
¿qué entra en la copia?

Hay un antecedente en el propio proyecto:
[ADR-0021](ADR-0021-el-ponente-pertenece-a-su-evento.md) ya resolvió el caso de
los ponentes con un **«copiar ponentes de otro evento»** acotado, que trae
fichas y nada más.

## Problema

¿Se duplica un evento entero, y si se duplica, qué se copia?

## Decisión

**No se duplican eventos.** No habrá botón de duplicar.

El motivo lo puso quien organiza los eventos, y es de los que zanjan una
discusión de diseño: **se tarda menos en generar el evento nuevo que en duplicar
el anterior e ir corrigiendo lo que no vale**.

Y encaja con cómo ha quedado el aplicativo: crear un evento son tres bloques y
nace ya con sus páginas, así que el trabajo que ahorraría la copia es pequeño.
Lo que de verdad cuesta teclear —los ponentes— ya tiene su copia acotada, que
trae exactamente lo que se quiere traer y nada más.

Se **pospone**, no se descarta para siempre: si algún día se mide que duplicar
ahorra trabajo de verdad, se reabre con una ADR nueva.

## Alternativas consideradas

### Opción 1: duplicar el evento entero

Traer secciones, apariencia, programa, ponentes y talleres, con las fechas
desplazadas. Descartada por el motivo de arriba, y por tres más que aparecen en
cuanto se intenta concretar:

- **Cada cosa copiada necesita su propia regla.** Las fechas hay que
  desplazarlas, ¿a partir de qué día? Los talleres traen aforo pero no
  inscripciones. El programa trae ponentes que quizá este año no vienen. No es
  un botón: son cinco decisiones que alguien tiene que tomar mirando una
  pantalla de opciones.
- **Lo copiado que no se corrige es peor que lo que falta.** Un programa vacío
  se nota; un programa del año pasado con las fechas cambiadas parece bueno y no
  lo es. El error silencioso es el caro.
- **Cuatro botones donde debería haber uno.** Sin esta decisión, cada pestaña
  acabaría añadiendo su propio «copiar de otro evento», y el aplicativo tendría
  cuatro maneras distintas de traer cosas de otro evento, cada una con sus
  reglas.

### Opción 2: duplicar solo el esqueleto

Secciones y apariencia, sin contenido. Descartada: es casi exactamente lo que ya
hace crear un evento, que nace con sus páginas elegidas. El ahorro sería
elegirlas, y eso son cuatro clics.

### Opción 3: plantillas de evento

Un evento marcado como plantilla del que nacen los demás. Descartada por ahora:
es más máquina que la opción 1 y resuelve un problema que nadie ha pedido. Si se
reabre lo de duplicar, esta es la que habría que mirar primero, porque al menos
separa «lo que se repite» de «lo del año pasado».

## Consecuencias

### Positivas

- Una pantalla menos, cinco reglas de copia menos y ningún evento nuevo que
  arrastre datos del anterior sin que nadie lo haya querido.
- El único «copiar de otro evento» del aplicativo sigue siendo el de ponentes,
  que hace una cosa y se entiende.

### Negativas

- **Quien monta la quinta edición de unas jornadas repite trabajo.** Volverá a
  escribir las secciones, la apariencia y el programa. Es una decisión
  consciente: se acepta ese coste a cambio de no arrastrar datos viejos.
- Si mañana alguien mide que ese trabajo es mayor de lo que se cree, esta ADR
  habrá que sustituirla. No hay medición: hay el criterio de quien organiza los
  eventos, que es mejor que ninguna, pero es criterio.

### Neutras

- La apariencia de un evento se puede seguir igualando a mano: son unos pocos
  ajustes en un panel.
