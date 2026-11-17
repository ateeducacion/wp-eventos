---
id: ADR-0053
title: "Control de asistencia: puntos con QR que se marcan con la cuenta, y hoja de firmas"
status: Propuesta
date: 2026-11-17
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0015, ADR-0032, ADR-0051]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0053: Control de asistencia: puntos con QR que se marcan con la cuenta, y hoja de firmas

## Estado

Propuesta

## Contexto

Para reconocer las horas de formación de una jornada hace falta constancia de
**entrada y salida**, y conviene un control a mitad. En una edición anterior se
intentó con un enlace y un QR fuera del aplicativo: falló por la hora —se
guardaba en otra zona horaria— y no se había probado. El respaldo fue en papel,
y además la persona que daba cada taller pasaba lista.

Quien asiste tiene cuenta para iniciar sesión en el sitio: es personal de la
administración educativa.

## Problema

¿Cuál es el control de asistencia más sencillo que sirva para certificar, que no
se pueda hacer por otra persona ni desde casa, y que tenga respaldo cuando el
móvil falla?

## Alternativas consideradas

- **Un formulario con nombre y documento.** Descartada: cualquiera ficha por
  cualquiera, y teclear es lento en una cola.
- **Un QR personal por persona que lee la organización en la puerta.**
  Descartada: hace falta un lector y alguien en la puerta, que es lo que se
  quiere evitar.
- **Un sistema de asistencia externo.** Puede ser mejor para eventos grandes,
  pero obliga a cruzar listas entre dos sitios. No se descarta para más
  adelante.
- **Un QR por punto de control, que la persona escanea y marca con su cuenta**
  (elegida).

## Decisión

En el taller del evento, la pestaña **«Asistencia»** tiene:

1. **Puntos de control** —«Entrada», «Mediodía», «Salida»— con un nombre y un
   interruptor **abierto**. Cada punto tiene un testigo largo y aleatorio, y un
   enlace a la página de inscripción del evento con ese testigo, que se pinta
   como **QR** para imprimirlo o proyectarlo. Cambiar el nombre no cambia el
   testigo: lo impreso sigue valiendo.
2. Quien escanea, **inicia sesión** si no la tenía y pulsa **«Registrar mi
   asistencia»**. La inscripción se encuentra por la cuenta
   ([ADR-0051](ADR-0051-correo-de-confirmacion-y-volver-a-la-inscripcion.md)),
   así que no se marca por otra persona. **Solo marca si el punto está
   abierto**: se abre al proyectarlo y se cierra al acabar, y una foto del QR no
   sirve desde casa. La primera hora se queda: escanear dos veces no la mueve.
   La hora es la del sitio (`current_time()`), no la del servidor.
3. **Plan B**: una tabla con una casilla por persona y punto para marcar o
   quitar **a mano**; lo marcado a mano queda como tal. Una **hoja de firmas**
   para imprimir, de todo el evento o de un taller —la que se le da a quien lo
   imparte—, con una columna de firma por punto. Y la asistencia en **CSV**.

Las marcas se guardan en la inscripción (`evt_reg_attendance`, por punto: hora
y si fue por QR o a mano), y los puntos en el evento
(`evt_attendance_points`).

El QR lo pinta en el navegador `qrcode-generator`, fijado a una versión exacta,
desde jsDelivr con SRI y desde `node_modules` en desarrollo
([ADR-0015](ADR-0015-librerias-de-terceros-desde-cdn-con-sri.md)). Si no llega,
debajo está el enlace escrito.

## Consecuencias

### Positivas

- Fichar es escanear y pulsar un botón, sin teclear nada.
- No se ficha por otra persona ni desde casa, salvo que alguien deje un punto
  abierto.
- Lo que falla tiene respaldo en la misma pantalla y en papel.

### Negativas

- **Pide cuenta y saber la contraseña.** Quien no la sepa ese día va al plan B.
- **Un punto que se queda abierto** deja fichar desde fuera con una foto del
  QR. Depende de que la organización lo cierre.
- **Pide la página de inscripción publicada**, que es la que abre el QR. La
  pestaña lo avisa si no lo está.
- **Quitar un punto** deja sus marcas guardadas pero ocultas: ya no hay columna
  donde enseñarlas.
- Una librería más que mantener al día.

## Adenda — 2026-11-17

El QR de cada punto se **descarga como cartel** (PNG): el evento, el nombre del
punto, el código y los tres pasos. Lo pinta el navegador en un lienzo con la
misma librería; el servidor no genera imágenes. Tras bajarlo, la pestaña dice
qué hacer con él, y una ayuda desplegable explica dónde colocarlo y a qué
tamaño. El botón de la página que abre el QR se llama **«Confirmar
asistencia»**, que es como se le dice a esto en casi todas partes, y la página
deja cerrar la sesión a quien esté con la cuenta de otra persona.

## Referencias

- `src/Evt/PublicFront/Attendance.php`,
  `src/Evt/PublicFront/View/EventAttendancePanel.php`, `assets/js/evt-qr.js`,
  `tests/unit/test-attendance.php`, `tests/js/evt-qr.test.js`.
