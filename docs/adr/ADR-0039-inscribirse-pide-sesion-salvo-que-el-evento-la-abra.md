---
id: ADR-0039
title: "Inscribirse pide sesión salvo que el evento la abra, y adjuntar la pide siempre"
status: Propuesta
date: 2026-09-27
related:
  issues: []
  prs: []
  sdds: []
  adrs: [ADR-0017, ADR-0031, ADR-0032, ADR-0033, ADR-0036]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0039: Inscribirse pide sesión salvo que el evento la abra, y adjuntar la pide siempre

## Estado

Propuesta

## Contexto

El formulario de inscripción de la
[ADR-0031](ADR-0031-el-formulario-de-inscripcion-es-nucleo-fijo-mas-preguntas.md)
lo contestaba cualquiera, con sesión o sin ella. La única comprobación antes de
aceptar una inscripción era el interruptor `evt_signup_open`
(`src/Evt/PublicFront/SignupForm.php`, `is_open()`). No miraba si el evento
estaba publicado, si estaba marcado como histórico
([ADR-0017](ADR-0017-el-estado-historico-cierra-la-edicion.md)) ni si había un plazo.

Desde la [ADR-0036](ADR-0036-los-ficheros-de-una-inscripcion-no-son-adjuntos.md)
una pregunta puede pedir un archivo, así que cualquier persona anónima podía
subir hasta 10 MiB por pregunta y por envío, sin límite de frecuencia. La
revisión de seguridad lo dejó anotado (PR #31).

## Problema

¿Quién puede inscribirse en un evento, cuándo, y quién puede adjuntar archivos?

## Factores de decisión

- Un archivo subido ocupa disco y puede contener datos personales de terceros.
  Sin sesión no hay a quién pedirle cuentas de lo que se sube.
- Hay eventos que sí necesitan inscripción abierta al público: jornadas con
  participación externa, sin cuenta en el sitio.
- Un evento en borrador o marcado como histórico no debe apuntar a nadie,
  aunque alguien se dejara el interruptor puesto.
- El plazo de inscripción casi nunca coincide con el de elegir taller
  ([ADR-0033](ADR-0033-elegir-taller-aforo-duro-y-cambio-hasta-el-cierre.md)),
  que ya tenía fechas propias.

## Alternativas consideradas

### Opción 1: siempre pública, sin archivos para quien no tiene sesión

Es el comportamiento de hoy, con los archivos cerrados a quien no ha entrado.
No sirve a los eventos que solo quieren gente con cuenta, y deja abierto por
defecto lo que tendría que abrirse a propósito.

### Opción 2: siempre con sesión

Es lo más cerrado, pero deja fuera los eventos abiertos al público.

### Opción 3: interruptor por evento, apagado por defecto, y archivos solo con sesión

Quien organiza abre la inscripción al público cuando lo necesita. Adjuntar
archivos pide sesión en todos los casos.

### Opción 4: un captcha para quien se inscribe sin sesión

Frena envíos automáticos, pero no responde a quién se hace cargo de un archivo.
Además añade un servicio externo o una librería con su propia decisión. Se deja
para otra ADR.

## Decisión

Haremos la opción 3:

- **Abierta es todo a la vez**: el interruptor `evt_signup_open`, dentro del
  plazo (`evt_signup_start` / `evt_signup_end`, fechas `Y-m-d` opcionales como
  las del taller), el evento publicado y sin marcar como histórico.
  `SignupForm::closed_because()` dice cuál de esas condiciones falla, y la
  página de inscripción lo enseña.
- **Inscripción pública por evento** (`evt_signup_public`, apagada por
  defecto). Apagada, quien no ha entrado ve un botón para iniciar sesión, y el
  envío se rechaza también en el servidor.
- **Adjuntar pide sesión siempre.** A quien no ha entrado no le salen las
  preguntas de archivo, y el servidor no recoge ningún fichero aunque venga en
  la petición. Si una pregunta de archivo es obligatoria, se le manda a iniciar
  sesión. La pestaña «Inscripción» avisa de esto cuando el evento es público y
  tiene alguna pregunta de archivo.
- Cambiar de taller con el enlace de la inscripción no cambia: sigue su propio
  plazo.

## Consecuencias

### Positivas

- Ningún anónimo sube archivos, y queda cerrada la vía de llenar el disco sin
  sesión.
- Un borrador, un evento histórico o uno fuera de plazo no aceptan inscripciones
  aunque el interruptor siga puesto.
- La página dice por qué está cerrada y cuándo se abre o se cerró.

### Negativas

- **Cambia el comportamiento de los eventos que ya tenían la inscripción
  abierta**: pasan a pedir sesión hasta que alguien encienda «Inscripción
  pública» en cada uno.
- Una inscripción pública con una pregunta de archivo obligatoria deja de ser
  pública en la práctica. Es lo que se busca, pero hay que saberlo al
  configurarla, y por eso la pestaña lo avisa.
- Sin captcha, una inscripción pública sigue expuesta a envíos automáticos de
  texto.

### Neutras

- Las fechas se guardan y se comparan como las del plazo de taller
  (`current_time( 'Y-m-d' )`, fechas inclusivas).
