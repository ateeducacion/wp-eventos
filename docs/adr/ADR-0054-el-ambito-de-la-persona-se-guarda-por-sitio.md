---
id: ADR-0054
title: "El ámbito de la persona se guarda por sitio"
status: Propuesta
date: 2026-11-17
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0006, ADR-0038]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0054: El ámbito de la persona se guarda por sitio

## Estado

Propuesta

## Contexto

El ámbito de una persona es el término de `evt_area` que limita qué eventos
abre ([ADR-0006](ADR-0006-el-area-es-un-ambito-no-un-rol.md),
[ADR-0038](ADR-0038-ambitos-organizativos-jerarquicos.md)). Hasta ahora se
guardaba en la meta de usuario `evt_area`, con el número del término
(`src/Evt/Access/EventAccess.php`, `snippets/roles-and-profiles.php`).

El destino puede ser un subsitio de un multisitio. Ahí **las cuentas y su meta
son de toda la red**, y **los términos son de cada sitio**: cada subsitio
tiene sus propias tablas de términos. Con el ámbito en la meta de la red:

- Si el aplicativo está en dos subsitios, el mismo número es un ámbito en uno y
  otro distinto —o ninguno— en el otro. Una persona con «Innovación» en un
  sitio puede abrir los eventos de «Formación» en el otro.
- Cambiar el ámbito de alguien en un sitio lo cambia en todos.

## Alternativas consideradas

- **Dejarlo en la red.** Descartada: es exactamente el problema.
- **Poner el prefijo a mano en la clave.** Funciona, pero es reinventar lo que
  WordPress ya hace, y cada lectura tendría que acordarse de ponerlo.
- **Opción de usuario de WordPress, `update_user_option()`** (elegida). Guarda
  la meta con el prefijo de las tablas del sitio delante —`wp_evt_area`,
  `wp_3_evt_area`—, que es como WordPress guarda los roles de cada sitio. Es lo
  mismo que ya hacen otros aplicativos del equipo con sus datos por sitio.

## Decisión

El ámbito se escribe con `EventAccess::set_user_areas()`, que lo guarda por
sitio con `update_user_option()` y **borra la copia de la red**. Se lee de la
clave del sitio (`EventAccess::user_area_key()`); si no hay, de la meta de la
red de antes.

Así, desplegar esto **no deja a nadie sin ámbito**: lo que había se sigue
leyendo, y pasa al sitio la primera vez que administración guarda el perfil.
El diagnóstico de «Ajustes» marca en «Guardado en la red» a quien sigue con la
copia de antes.

Las búsquedas de personas por ámbito —el filtro de la lista de usuarios—
miran las dos claves.

## Consecuencias

### Positivas

- En un multisitio, cada sitio tiene el ámbito de cada persona, y cambiarlo en
  uno no toca los demás.
- Sin migración: el valor de antes vale hasta que se vuelve a guardar.

### Negativas

- **Mientras alguien siga con la copia de la red**, en un multisitio con el
  aplicativo en dos subsitios esa copia se lee en los dos, como antes. El
  diagnóstico lo enseña; se arregla abriendo el perfil y guardándolo en cada
  sitio.
- **Quién puede editar el perfil de otra persona en un multisitio no cambia**:
  WordPress reserva editar cuentas ajenas a la superadministración salvo que la
  red lo abra. Si un subsitio necesita que su administración asigne ámbitos,
  hace falta otra decisión; esta no la toma.
- La batería de pruebas corre en un sitio único: comprueba la clave con
  prefijo, no dos sitios a la vez.

## Referencias

- `src/Evt/Access/EventAccess.php` (`set_user_areas()`, `user_area_key()`,
  `scope_assignment_state()`), `snippets/roles-and-profiles.php`
  (`evt_save_profile_fields()`), `src/Evt/Admin/Settings.php`.
- `tests/unit/test-event-access.php` (`test_the_scope_is_stored_per_site`),
  `tests/unit/test-roles-and-profiles.php`.
- WordPress: [`update_user_option()`](https://developer.wordpress.org/reference/functions/update_user_option/)
  y [`get_user_option()`](https://developer.wordpress.org/reference/functions/get_user_option/).
