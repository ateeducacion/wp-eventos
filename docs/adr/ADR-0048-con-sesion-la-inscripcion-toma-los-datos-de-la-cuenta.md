---
id: ADR-0048
title: "Con sesión, la inscripción toma de la cuenta el nombre, el correo y el centro"
status: Propuesta
date: 2026-09-28
related:
  issues: []
  prs: []
  sdds: []
  adrs: [ADR-0031, ADR-0032, ADR-0037]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0048: Con sesión, la inscripción toma de la cuenta el nombre, el correo y el centro

## Estado

Propuesta

## Contexto

Una inscripción que no es pública pide iniciar sesión
(`SignupForm::login_needed()`, `src/Evt/PublicFront/SignupForm.php`). Quien
entra ya tiene en su cuenta de WordPress su nombre (`first_name`), sus
apellidos (`last_name`) y su correo (`user_email`), y en el sitio de destino
el inicio de sesión rellena además la meta de usuario `codigo` con el código
oficial de su centro. Aun así, el formulario le pedía teclearlo todo otra vez,
y el centro, buscarlo en un desplegable de todo el catálogo (ADR-0037).

Teclearlo es donde nacen las variantes: el mismo nombre escrito de dos
maneras, un correo personal en vez del de la cuenta, el centro equivocado.

## Problema

¿Qué hace el formulario con lo que la cuenta ya sabe de quien se inscribe?

## Factores de decisión

- Lo que se guarda tiene que ser fiable: es lo que sale en la lista de
  participantes, en el CSV y en los certificados.
- El aplicativo no puede depender de cómo rellene el sitio esa ficha: un
  sitio puede rellenarla de una forma, otro de otra, y otro no rellenarla.
- Un campo `readonly` lo cambia cualquiera desde el navegador.

## Alternativas consideradas

### Opción 1: rellenar los campos y dejarlos editables

Menos trabajo para quien se inscribe, pero no arregla nada: el dato que se
guarda sigue siendo el que llega en el envío.

### Opción 2: rellenar, bloquear en el navegador y volver a imponerlo en el servidor

Los campos que la cuenta trae salen rellenos y de solo lectura, y al recibir
el envío el servidor los sustituye por los de la cuenta, llegue lo que llegue.
Lo que la cuenta no trae —el documento de identidad, el teléfono, o un nombre
que la ficha tiene vacío— se sigue tecleando.

### Opción 3: que el aplicativo consulte el origen de los datos al iniciar sesión

Metería en el aplicativo el directorio de una organización concreta, que es
justo lo que no puede llevar (ADR-0030).

## Decisión

Haremos la **opción 2**. `Registrations::from_profile()` lee de la cuenta
`first_name`, `last_name`, `user_email` y la meta `codigo`
(`RegistrationMetaKeys::USER_CENTRE_CODE`). El centro solo cuenta si su
código está en el catálogo, y su nombre sale del catálogo, no de la ficha.
`SignupForm` impone esos valores sobre el envío.

Además, sin sesión, abrir la página de una inscripción abierta que la pide
lleva directamente al acceso y vuelve a ella. Una inscripción cerrada no
redirige: lo que hay que leer es por qué.

El aplicativo **no escribe** nunca esa ficha: la rellena, al iniciar sesión,
lo que el sitio de destino tenga para eso, fuera de este repositorio.

## Consecuencias

### Positivas

- Nombre, correo y centro de quien entra con su cuenta llegan tal como están
  en la cuenta, sin variantes.
- Sin nada en la ficha, el formulario es el de siempre: no hay nada que
  configurar para usarlo.

### Negativas

- Si la ficha trae un dato mal, quien se inscribe no lo puede corregir en el
  formulario: tiene que corregirse en la cuenta.
- El nombre de la meta, `codigo`, es el que usa el sitio de destino. Otro
  sitio que lo guarde con otro nombre tendría que copiarlo a este.

### Neutras

- La inscripción pública sin sesión no cambia.
