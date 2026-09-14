---
id: ADR-0024
title: "Un día puede tener dos sedes: la sede es de la actividad"
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

# ADR-0024: Un día puede tener dos sedes: la sede es de la actividad

## Estado

Aceptada

## Contexto

En el sistema que se sustituye, la sede no es un dato: son **dos juegos de
campos duplicados** en el formulario de alta, «sede 1» y «sede 2», cada uno con
su fecha, su isla, su municipio, su centro y su ruta. Cada juego tiene además su
propia Vista para pintar el programa, en dos diseños. Una tercera sede no cabe:
haría falta un tercer juego de campos y dos Vistas más.

Y hay una consecuencia peor que el límite: la llamada que pinta el programa
lleva la sede, el día y la fecha **copiados como atributos**, además de estar en
la entrada del formulario. El mismo dato en dos sitios, así que cambiar la fecha
no cambia lo que se ve.

Al diseñar la pantalla del programa quedó abierto si la sede va siempre con el
día o si un mismo día puede tener dos. Esto lo contesta.

## Problema

¿De quién es la sede: del día del evento, o de cada actividad?

## Decisión

**La sede es un dato de cada actividad, y un mismo día puede tener dos.**

De ahí se sigue todo lo demás:

- **La parrilla agrupa por día y, dentro del día, por sede.** Un día con una
  sola sede se pinta como un bloque y no se le pone rótulo de sede, que no
  aporta nada; uno con dos, como dos bloques.
- **Caben las sedes que hagan falta.** No hay «sede 1» ni «sede 2»: hay la sede
  que tenga cada actividad, y la lista de las usadas en el evento sale de sus
  actividades.
- **No se declara ninguna sede por adelantado.** Igual que los días, las sedes
  se deducen de lo que hay. Añadir una sede es escribirla en una actividad.
- **La sede no se copia a ninguna parte.** Se lee del dato al pintar.

## Alternativas consideradas

### Opción 1: la sede es del día

Un evento tendría una lista de días, cada uno con su sede, y las actividades
colgarían del día. Más simple de pintar, y probablemente cierto en la mayoría de
los eventos reales.

Descartada porque **el caso que excluye es real y no es raro**: una jornada que
por la mañana hace las ponencias en un centro del profesorado y por la tarde los
talleres en un centro educativo cercano es exactamente lo que este aplicativo
tiene que poder representar. Y porque obliga a declarar los días antes de
escribir nada, que es un paso más y una pantalla más.

### Opción 2: seguir con «sede 1» y «sede 2»

Conserva lo que hay y no obliga a pensar. Descartada: el límite de dos es
artificial, cuesta un juego de campos y dos Vistas por cada sede nueva, y es
justo el defecto que este aplicativo viene a quitar.

### Opción 3: la sede es del evento, una sola

Lo más simple de todo. Descartada: hay eventos vivos en el sistema actual con
dos sedes, así que no representa lo que ya existe.

## Consecuencias

### Positivas

- Caben tres sedes, o cinco, sin tocar código ni añadir campos.
- Cambiar la sede de una actividad cambia lo que se publica, que es lo que hoy
  no pasa.
- Una actividad lleva su sede consigo, así que moverla de día no la pierde.

### Negativas

- **Se teclea la sede en cada actividad.** En el caso común —un día, una sede—
  es repetir el mismo dato tantas veces como actividades tenga el día. Se
  compensa proponiendo la sede de la última actividad escrita, pero es una
  ayuda de la interfaz, no del modelo: si alguien la cambia a medias, el día
  queda partido en dos bloques sin querer.
- La parrilla tiene que saber agrupar por dos criterios, y decidir cuándo el
  rótulo de sede estorba. Es más código que agrupar solo por día.

### Neutras

- La lista de sedes de un evento es derivada: no hay dónde «gestionar las
  sedes», y quien busque esa pantalla no la va a encontrar.
