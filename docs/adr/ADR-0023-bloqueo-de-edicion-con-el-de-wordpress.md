---
id: ADR-0023
title: "Que dos personas no se pisen: se usa el bloqueo de edición nativo de WordPress, no uno propio"
status: Aceptada
date: 2026-09-13
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0003, ADR-0006, ADR-0012, ADR-0016, ADR-0017]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0023: Que dos personas no se pisen: se usa el bloqueo de edición nativo de WordPress, no uno propio

## Estado

Aceptada (2026-09-13). Implementada en `src/Evt/PublicFront/EditLock.php`
(`owner()`, `require_available()`, `status()`, `claim()`, `release()`,
`handle()` y `render()`), enganchada desde
`EventWorkspace.php` (el POST de «Tomar posesión»)
(la comprobación antes de escribir) (soltar), y desde
`PageForm.php`. La parte de navegador está en
`assets/js/evt-app.js` y el aspecto del aviso en
`assets/css/evt-app.css`. Sus pruebas, en `tests/unit/test-edit-lock.php`.

## Contexto

El aplicativo ya decide **quién** puede editar un evento —el área
([ADR-0006](ADR-0006-el-area-es-un-ambito-no-un-rol.md)) y el cierre explícito
([ADR-0017](ADR-0017-el-estado-historico-cierra-la-edicion.md))— pero no dice
nada sobre **cuándo**. Y el taller es una pantalla larga: se abre, se rellenan
catorce campos, se sube un cartel, se reordenan las secciones y se guarda un
rato después.

Eso es exactamente el sitio donde dos personas del mismo área se pisan, y en
este aplicativo se pisan más que en otros por dos motivos propios:

- **Las cuentas de un área son varias y comparten todo.** `organizacion` y
  `organizacion2` tienen la misma área, ven los mismos eventos y pueden editar
  los mismos (`scripts/seed-demo.php` monta justo ese caso a propósito).
- **El evento y sus páginas son el mismo tipo de contenido**
  ([ADR-0003](ADR-0003-cpt-jerarquico-frente-a-paginas-generadas.md)), y el
  taller escribe cosas que son del evento entero: el orden de las secciones, la
  apariencia, la navegación. Dos personas en dos secciones distintas del mismo
  evento no están trabajando en cosas separadas.

Y hay un tercer camino que no es el aplicativo y que existe igual: **el
escritorio de WordPress**. Un evento es un `evt_event`, así que se puede abrir
en `post.php` como cualquier entrada. Sea cual sea la solución, tiene que
contar también con esa puerta.

## Problema

¿Cómo se evita que dos personas que pueden editar el mismo evento se sobrescriban
el trabajo, contando con que una de las dos puede estar en el escritorio de
WordPress y no en el aplicativo?

## Factores de decisión

- **No puede haber dos verdades sobre quién está editando.** Si el aplicativo
  lleva su cuenta y `wp-admin` lleva la suya, cada uno deja pasar lo que el otro
  bloquea, y el caso en que se pierde trabajo es justo ese.
- **La comprobación va antes de escribir, no después.** Quien perdió el turno
  tiene la pantalla de hace media hora delante; su envío trae catorce campos
  viejos y los escribiría todos.
- **Tiene que decir un nombre.** «Lo está editando otra persona» no sirve para
  nada: en un área de doce personas no se sabe a quién avisar.
- **Tiene que haber salida.** Quien se fue a comer con el evento abierto no
  puede dejarlo bloqueado hasta mañana. Hace falta caducidad y hace falta poder
  tomar posesión.
- **Tiene que verse sin JavaScript.** Es el estado de la pantalla, no una
  confirmación.
- **Cuanto menos código, mejor.** Esto es un problema resuelto en WordPress
  desde 2008; escribirlo otra vez es escribir sus errores otra vez.

## Qué hay que escribir

Poco, y esa es la señal de que el camino es el bueno. Un aplicativo de la casa
que ya resolvió lo mismo lo tiene en **menos de cien líneas** precisamente
porque no se inventa nada: apoyarse en el bloqueo del núcleo deja fuera la
caducidad, la renovación, la liberación y el nombre de quien tiene la posesión.
Lo que queda por escribir aquí son cuatro métodos —dueño, comprobación antes de
escribir, liberación y el HTML del aviso—, el encolado del guion del Heartbeat y
sus pruebas. El desglose pieza a pieza, con rutas, está en `.local/`
([ADR-0030](ADR-0030-el-repositorio-se-publica-sin-nada-de-nadie.md)).

## Alternativas consideradas

### Opción 1: el bloqueo nativo de WordPress (`_edit_lock`) — ELEGIDA

`wp_check_post_lock()`, `wp_set_post_lock()` y la meta `_edit_lock`, que es el
mismo mecanismo que usa el editor del escritorio, más su renovación por
Heartbeat (`wp-refresh-post-lock`) y su liberación por baliza
(`wp-remove-post-lock`).

- Pros:
  - **Se comparte con `wp-admin`, y esa es la razón principal.** Si alguien abre
    el evento en el editor de WordPress y otra persona en el taller del
    aplicativo, **se ven**. Un bloqueo propio no lo haría: habría dos verdades y
    la puerta del escritorio quedaría abierta de par en par.
  - Cero mecanismo nuevo: caducidad, renovación, liberación y el propio nombre
    de quien lo tiene ya están escritos y probados en el núcleo.
  - El aviso del Heartbeat, con el nombre de quien tomó posesión, sale del
    propio `wp-refresh-post-lock`; no hay que inventar ningún canal.
- Contras: la caducidad y el intervalo son los de WordPress y no los nuestros
  (150 s y 15 s), y hay que aceptar la semántica del núcleo, que es «aviso
  fuerte», no «candado».

### Opción 2: un bloqueo propio, con su meta y su tabla — DESCARTADA

- Pros: se elige la caducidad, el mensaje y el alcance sin depender de nadie.
- Contras, y por eso se descarta:
  - **No lo vería `wp-admin`**, que es el caso que más duele: la persona del
    escritorio no aparecería en ningún sitio y pisaría sin enterarse.
  - Habría que escribir caducidad, renovación, liberación al cerrar la pestaña y
    aviso en caliente: exactamente lo que el núcleo ya trae.
  - Sería una meta más en el evento y un mecanismo más que explicar.
  - No ahorra nada: la versión nativa son unas 260 líneas aquí, casi todas
    comentarios y el HTML del aviso.

### Opción 3: no bloquear y resolver el choque al guardar — DESCARTADA

Comparar `post_modified` al enviar y avisar de que el evento cambió.

- Pros: nada que mantener mientras nadie choque.
- Contras: se entera **después** de haber escrito media hora, que es justo el
  trabajo que se quería no perder. Y no resuelve nada de lo que no es
  `post_modified`: los términos, las metas y el orden de las secciones no lo
  mueven.

### Opción 4: bloqueo por sección y no por evento — DESCARTADA

- Pros: dos personas podrían escribir dos secciones a la vez, que es el caso
  cómodo.
- Contras: **no es verdad que sean independientes**. El orden de las secciones,
  la navegación y la apariencia son del evento; dos personas en dos secciones se
  pisarían igual y con un bloqueo puesto que diría que no. Un candado que
  tranquiliza y no protege es peor que ninguno.

## Decisión

### 1. El bloqueo es el nativo, y por eso se comparte con el escritorio

`EditLock::owner()` es `wp_check_post_lock()` y `EditLock::claim()` es
`wp_set_post_lock()`, los dos sobre la meta `_edit_lock` del núcleo. No hay meta
propia ni tabla propia.

### 2. Es del EVENTO RAÍZ, siempre

Todo pasa por `EventAccess::root_id()`, de modo que quien pregunta no tiene que
acordarse. Editar la sección «Ponentes» toma y comprueba el bloqueo del evento
que la contiene, y una sección **no lleva `_edit_lock` propia**: medido abajo.

### 3. La comprobación va antes de escribir, y responde 409

`EditLock::require_available()` es lo primero de `EventWorkspace::handle()`
(`:346`) y de `PageForm::handle()` (`:131`): antes de una sola meta, un solo
término o un solo estado. Responde `409` con `wp_die()`, con el nombre de quien
lo tiene y un enlace de vuelta.

### 4. «Tomar posesión» va por POST y con su nonce, y además con `EventAccess`

El nonce dice que el envío salió de nuestra pantalla; no dice que quien lo manda
pueda editar este evento. Se comprueban las dos cosas
(`EditLock::handle()`), y sin la segunda se responde `403`.

### 5. Al soltar solo se suelta el propio

`EditLock::release()` compara el usuario que trae la meta y le pasa el valor
exacto a `delete_post_meta()`, que es lo que hace la comprobación atómica en la
base de datos. Si mientras tanto otra persona tomó posesión, el bloqueo es suyo
y no se le quita.

### 6. El aviso dice el nombre, y se ve sin JavaScript

Es un `<dialog>` nativo y no SweetAlert2, y la diferencia importa: esto no es
una confirmación —lo de SweetAlert2 en este aplicativo
([ADR-0016](ADR-0016-borrar-es-enviar-a-la-papelera.md))— es el estado de la
pantalla. El servidor lo pinta con `open` y el navegador lo enseña solo. Nunca
dice «alguien»: dice el `display_name`.

### 7. El Heartbeat sí se porta, y avisa sin recargar

`wp-refresh-post-lock` viaja en cada latido (`assets/js/evt-app.js`),
renueva el bloqueo propio y, cuando otra persona toma posesión, contesta con su
nombre: el aviso se abre con `showModal()`, se pone el nombre nuevo y se
`inert`an todos los formularios menos el del propio aviso. Lo escrito **no se
borra**: se congela en pantalla para poder copiarlo. Al cerrar la pestaña, una
baliza `wp-remove-post-lock` suelta el bloqueo.

### 8. Sin JavaScript no se pierde nada importante

El aviso ya viene pintado desde el servidor y el `409` vuelve a comprobarlo
antes de escribir. Lo que da el guion es no estar media hora escribiendo algo
que ya no se puede guardar.

## Comportamiento medido

Medido el 2026-09-13 en el wp-env del proyecto (WordPress 7.1,
`localhost:8798`), con **dos sesiones de navegador simultáneas y aisladas** —dos
contextos de navegador con sus propias cookies, equivalente a una ventana normal
y otra de incógnito— sobre el evento 23 «III Jornadas de Tecnología Educativa»
(área Innovación, 6 secciones): la sesión A entra como `organizacion` (usuario 3) y la
B como `organizacion2` (usuario 4), las dos del área Innovación.

```
1. A abre  /evento/?evento=23        _edit_lock(23) = 1789293802:3
2. B abre  /evento/?evento=23        ve el aviso: «Organización (Innovación) tiene abierto
                                     este evento ahora mismo», y toda la hoja dentro
                                     de un <fieldset class="evt-solo-lectura" disabled>
3. B fuerza el POST de «Datos» con el título cambiado (salta el fieldset,
   que es lo que haría una pantalla vieja o un envío a mano):
                                     HTTP 409  «Edición bloqueada»
                                     post_title  = «III Jornadas de Tecnología Educativa»  (sin tocar)
                                     post_modified = 2026-09-13 08:13:44                    (sin tocar)
4. B abre  /seccion/?seccion=25      mismo aviso, mismo nombre
                                     _edit_lock(25) = «»      ← la sección no lleva bloqueo
                                     _edit_lock(23) = …:3     ← lo lleva la raíz
5. B pulsa «Tomar posesión»          _edit_lock(23) = 1789294284:4
6. B guarda el lema                  evt_tagline = «Lema escrito por la segunda sesion»
7. A, SIN recargar (Heartbeat)       se abre el aviso con showModal(): «Organización 2 (Innovación)»
                                     los 25 formularios de la pantalla quedan inert; el del aviso, no
8. A fuerza su POST (pantalla vieja) HTTP 409  «Edición bloqueada»
9. A pulsa «Tomar posesión»          _edit_lock(23) = 1789294448:3, y B recibe el aviso a los ~15 s
10. Se cierra la pestaña de A        _edit_lock(23) = 1789294394:3, o sea 151 s en el pasado:
                                     wp_check_post_lock(23) visto por el usuario 4 = 0  ← libre
```

Consola del navegador: un único mensaje, el `JQMIGRATE: Migrate is installed
with logging active` que imprime WordPress. Cero errores nuestros.
`wp-content/debug.log`: 0 bytes.

Cinco hechos que no son evidentes antes de medirlos:

1. **La sección no tiene bloqueo propio.** El paso 4 lo enseña: `_edit_lock(25)`
   está vacía y sin embargo la pantalla de la sección 25 sale bloqueada, porque
   `root_id()` sube al evento 23. No hay nada que replicar al añadir una
   pantalla nueva.
2. **La pantalla bloqueada no se pinta a medias: se pinta entera y deshabilitada.**
   Toda la hoja va dentro de un `<fieldset disabled>`, así que el navegador ni
   siquiera envía sus campos. El `409` es la segunda línea, no la primera, y es
   la que atrapa la pantalla vieja del paso 3.
3. **El `409` no escribe nada, ni siquiera a medias.** `post_modified` se queda
   en el segundo exacto de antes del intento.
4. **Cerrar la pestaña libera el evento en el acto**, y no borrando la meta:
   `wp-remove-post-lock` le pone una fecha 151 segundos en el pasado, que es un
   segundo más que la ventana de caducidad. El efecto para quien mira es el
   mismo —libre— y el rastro de quién lo tuvo se conserva.
5. **La ventana de caducidad es de 150 s** (`wp_check_post_lock_window`, sin
   filtrar) y el Heartbeat late cada 15 s. Diez latidos de margen: hace falta
   perder diez seguidos para soltar el bloqueo sin querer.

## Consecuencias

### Positivas

- **Una sola verdad sobre quién está editando**, compartida con el escritorio de
  WordPress. Es el motivo de haber elegido el nativo y es lo que ningún bloqueo
  propio habría dado.
- **El evento entero, con una línea.** Como todo pasa por `root_id()`, las
  pantallas que vengan —ponentes, programa, talleres, participantes— quedan
  cubiertas sin acordarse de nada.
- **Se avisa antes de perder el trabajo, no después.** Quien pierde el bloqueo
  lo sabe en 15 segundos y con lo escrito todavía en pantalla.
- **Poco código propio y ninguna caducidad que mantener.**
- **Funciona sin JavaScript**, que es lo que se le pide al estado de una
  pantalla.

### Negativas

Y aquí conviene no adornar nada, porque las tres son reales:

- **Un bloqueo caduca, y esto no es un candado.** A los 150 segundos sin
  latidos, el evento queda libre aunque la otra persona siga con la pantalla
  abierta —basta con que se le duerma el portátil, se le vaya la red o el
  navegador le congele la pestaña en segundo plano—. Quien entre después no verá
  aviso ninguno y guardará encima. Es la semántica del núcleo y se acepta a
  sabiendas: la alternativa, un bloqueo que no caduca, deja eventos secuestrados
  por quien se fue de vacaciones.
- **«Tomar posesión» no pide permiso a nadie, y se puede perder trabajo no
  guardado.** Cualquiera que pueda editar el evento puede quitárselo a quien lo
  tenga, sin que a la otra persona le pregunten. Lo que esa persona haya escrito
  y no haya guardado **no se recupera**: el aviso lo congela en pantalla para que
  se pueda copiar a mano, y ahí se acaba la ayuda. Si cierra la pestaña, se
  pierde. Es deliberado —la alternativa es que una pantalla olvidada bloquee un
  evento hasta que alguien administre— pero es una pérdida de verdad y hay que
  contarla al formar a la gente, no descubrirla el día del acto.
- **Si se cierra el navegador, depende de una baliza.** Al cerrar la pestaña sale
  un `navigator.sendBeacon` que caduca el bloqueo en el acto (medido: paso 10).
  Pero la baliza es JavaScript y es «lo mejor que se pueda»: si el navegador se
  cierra de golpe, se queda sin batería, se corta la red o la persona tiene el
  JavaScript desactivado, **no sale**, y entonces el evento sigue apareciendo
  como ocupado hasta que caduquen los 150 segundos. Nunca más de dos minutos y
  medio, y con el botón de «Tomar posesión» a la vista todo ese rato; pero no es
  cero.

### Neutras

- **No hay registro de quién tomó posesión ni cuándo.** La meta `_edit_lock` es
  una fecha y un usuario, y se sobrescribe. Esto entra en la auditoría que la
  [ADR-0012](ADR-0012-politica-de-edicion-y-auditoria.md) dejó pendiente y que
  sigue sin escribirse; la pieza del ecosistema que la resolvería está
  catalogada en `.local/`.
- **La ventana y el intervalo son los de WordPress** (150 s y 15 s). Se pueden
  cambiar con `wp_check_post_lock_window` y con `wp.heartbeat.interval()`, pero
  no se cambian: los valores del núcleo son los que ya conoce quien use el
  escritorio, y tener dos cadencias distintas en las dos pantallas sería la
  clase de detalle que nadie recuerda haber configurado.
- **El bloqueo no sustituye a los permisos.** Quien no puede editar el evento no
  ve ningún aviso ni cuenta para nada
  (`EditLock::status()` devuelve `none()` cuando `can_edit` es falso): no va a
  escribir, así que ni le estorba ni le quita el turno a quien sí puede.
