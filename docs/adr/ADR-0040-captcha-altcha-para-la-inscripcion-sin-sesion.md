---
id: ADR-0040
title: "La inscripción sin sesión pasa por ALTCHA, con el servidor escrito aquí"
status: Propuesta
date: 2026-09-27
related:
  issues: []
  prs: []
  sdds: []
  adrs: [ADR-0015, ADR-0030, ADR-0039]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0040: La inscripción sin sesión pasa por ALTCHA, con el servidor escrito aquí

## Estado

Propuesta

## Contexto

La [ADR-0039](ADR-0039-inscribirse-pide-sesion-salvo-que-el-evento-la-abra.md)
permite abrir la inscripción de un evento a quien no ha iniciado sesión, y deja
dicho en sus consecuencias que, sin captcha, esa inscripción sigue expuesta a
envíos automáticos. Esta ADR responde a eso.

Hay tres restricciones de la casa:

- el código solo llega a producción pegado en Code Snippets, así que una
  librería de Composer no se despliega;
- las librerías de navegador vienen de jsDelivr, con la versión clavada y SRI
  ([ADR-0015](ADR-0015-librerias-de-terceros-desde-cdn-con-sri.md));
- el repositorio no puede depender de la cuenta de ninguna organización en un
  servicio de terceros
  ([ADR-0030](ADR-0030-el-repositorio-se-publica-sin-nada-de-nadie.md)).

## Problema

¿Cómo se frena el envío automático de inscripciones públicas sin añadir un
servicio externo ni datos de terceros?

## Factores de decisión

- Sin cuentas, claves de terceros, cookies ni seguimiento de quien visita.
- Que se pueda desplegar con lo que hay: Code Snippets y el CDN con SRI.
- Que quien no ha iniciado sesión lo note poco, y quien sí la ha iniciado no lo
  note nada.

## Alternativas consideradas

### Opción 1: un servicio (Turnstile, hCaptcha, reCAPTCHA)

Obliga a tener una cuenta y unas claves en un tercero, y cada visita consulta
ese servicio. Choca con la ADR-0030 y con la protección de datos.

### Opción 2: un campo trampa (honeypot)

No tiene dependencias, pero cualquier robot hecho a propósito para este
formulario lo salta.

### Opción 3: ALTCHA con su librería PHP

`altcha-org/altcha` es una dependencia de Composer, y no llega a producción por
Code Snippets.

### Opción 4: ALTCHA, con el lado del servidor escrito aquí

El navegador resuelve una prueba de trabajo con el componente oficial, y el
servidor la comprueba con una firma HMAC propia. El formato v1 son cinco campos
y dos hashes, que caben en una clase sin dependencias.

## Decisión

Haremos la opción 4:

- `PublicFront/Captcha` crea desafíos en formato v1 de ALTCHA:
  - `SHA-256`, con número secreto hasta 100 000;
  - `salt` con `?expires=` a 20 minutos;
  - la firma es `hash_hmac( 'sha256', challenge, clave )`, con una clave
    derivada de las sales de `wp-config.php`, así que no hay nada que guardar
    en la base de datos.
- El componente pide el desafío a una ruta REST pública,
  `GET /wp-json/evt/v1/altcha`, con `Cache-Control: no-store`. No va escrito en
  la página: una página pública puede salir de una caché, y un desafío
  caducado dentro de ella no se podría resolver.
- `verify()` exige que la solución sea nuestra, esté resuelta, no haya caducado
  y **no se haya usado antes**. Cada solución aceptada queda marcada en un
  transient hasta que caduca.
- Solo se pide a quien no ha iniciado sesión.
- El filtro `evt_altcha_enabled` permite apagarlo sin tocar código.
- El componente es `altcha@3.2.3`: el módulo `dist/main/altcha.min.js` y los
  textos `dist/i18n/es-es.js`, desde jsDelivr con SRI, como `type="module"`.
  No se usan los `.umd.cjs` porque jsDelivr los sirve como `application/node`
  con `nosniff` y el navegador no los ejecuta. La versión 3 sigue aceptando
  el desafío v1; está comprobado en un navegador real contra el wp-env (PR del
  cambio).

## Consecuencias

### Positivas

- Sin terceros, sin cookies y sin claves que gestionar. El texto de pantalla
  está en castellano.
- Cada envío automático cuesta una prueba de trabajo, y una misma solución no
  sirve para mandar inscripciones en serie.
- Quien tiene sesión no ve nada distinto.

### Negativas

- Sin JavaScript no se puede enviar una inscripción pública. La página lo dice
  en un `<noscript>`. Con sesión no hace falta.
- La prueba de trabajo frena, pero no para: un atacante con máquina suficiente
  puede pagarla. Es un freno de coste, no una identificación.
- La solución se gasta al comprobarla, aunque luego la inscripción no se guarde
  por otro dato mal escrito. Quien reenvíe tiene que volver a marcar la casilla,
  que la página vuelve a pintar vacía.
- El protocolo v1 es el antiguo de ALTCHA. Si una versión futura del componente
  deja de aceptarlo, habrá que pasar a los desafíos nuevos (PBKDF2 y otros), y
  eso es otra ADR.

### Neutras

- La versión está clavada en tres sitios: `Captcha::VERSION` con sus URL,
  `package.json` y el SRI. Se suben juntas.
