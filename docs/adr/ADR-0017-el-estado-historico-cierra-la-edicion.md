---
id: ADR-0017
title: "El estado «histórico» cierra la edición de un evento, y es una marca distinta de evt_legacy"
status: Aceptada
date: 2026-09-13
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0005, ADR-0006, ADR-0008, ADR-0012, ADR-0014, ADR-0016]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0017: El estado «histórico» cierra la edición de un evento, y es una marca distinta de `evt_legacy`

## Estado

Aceptada (2026-09-13). Implementada: `EventMetaKeys::ARCHIVED`,
`EventAccess::is_archived()` / `can_open()` / `can_edit()` / `can_archive()` /
`can_unarchive()` / `can_toggle_archived()`,
`EventMetaRegistration::auth_archived()` y los controles del taller.

## Contexto

Hasta aquí, quién podía tocar un evento lo decidía una sola cosa: **el área**
([ADR-0006](ADR-0006-el-area-es-un-ambito-no-un-rol.md)). Y se decidió a
propósito que **no hubiera cierre por fechas**, porque cuando una jornada
termina es cuando más se toca su página
([ADR-0012](ADR-0012-politica-de-edicion-y-auditoria.md), opción 3).

Eso deja un hueco que la persona usuaria ha señalado y que se ve en cuanto se
mira el subsitio real: hay eventos de hace años que se conservan porque su
página sigue enlazada desde circulares y desde otras webs, y que **no se
quieren tocar más**. Sin una marca, un evento de 2019 se sigue editando
indefinidamente. Que el trabajo de después no tenga fecha de caducidad es
deliberado; que no haya forma de decir «esto ya está» es el hueco.

La otra pieza del contexto es que **ya existe una marca para lo viejo**, y no
significa esto:
[ADR-0008](ADR-0008-migracion-de-los-eventos-historicos.md) creó `evt_legacy`,
que la pone el guion de migración y quiere decir «el contenido de esta página
es contenido del maquetador, congelado del sistema anterior: no se regenera, se sigue pintando como
está y el editor enseña un aviso en vez de los campos nuevos». Es una decisión
sobre **cómo se pinta el contenido**, no sobre **quién puede escribirlo**.

## Problema

¿Cómo se cierra un evento a la edición, de forma explícita y para siempre, sin
despublicarlo ni esconderlo, y sin confundirlo con la marca que ya distingue el
contenido migrado?

## Factores de decisión

- **Alguien tiene que poder cerrar la puerta a mano.** Ninguna regla automática
  distingue «terminó la semana pasada y hay que subir los vídeos» de «esto es de
  2019 y no se toca más»; esa diferencia solo la sabe quien organizó la jornada.
- **Y quien la cierra es quien la organizó.** Cerrar tu propio evento no debería
  ser un trámite que se le pide a otra persona.
- **Y alguien tiene que poder volver a abrirla.** Cerrado para siempre no puede
  significar cerrado también para quien tiene que arreglar una errata.
- **La página pública no se toca.** Es un cierre de edición, no un despublicado:
  la dirección sigue respondiendo y el visitante ve lo de siempre.
- **No esconder es mejor que esconder.** Quien busque un evento cerrado tiene
  que encontrarlo, entrar, consultarlo y **leer por qué** no puede tocarlo. Un
  evento que desaparece del listado se reporta como un fallo.
- **El cierre alcanza a todo lo que cuelga del evento**: sus secciones satélite,
  y el día que estén colgados, sus ponentes, actividades, talleres e
  inscripciones. Si solo cerrara la raíz, se editaría el evento por sus hijas.
- **Una marca, un significado.** `evt_legacy` ya existe y ya significa otra
  cosa; reutilizarla obligaría a que cada sitio que la consulta supiera cuál de
  los dos sentidos le toca.

## Comportamiento medido

Medido en el wp-env del proyecto (WordPress 7.1, `localhost:8798`) sobre el
evento de demostración 8 «III Jornadas de Tecnología Educativa» (área `ate`, 6
secciones hijas; la 9 es una de ellas), con las cuentas `admin`
(`administrator`) y `organizacion` (`evt_organiser` del área `ate`):

```
== 0. Antes ==
  archived(evento)=false  is_archived(seccion)=false
  admin         can_open=true can_edit=true  can_edit(sec)=true  user_can(edit_post)=true  can_archive=true can_unarchive=true  auth_meta=true
  organizacion  can_open=true can_edit=true  can_edit(sec)=true  user_can(edit_post)=true  can_archive=true can_unarchive=false auth_meta=true

== 1. Marcado como histórico ==
  archived(evento)=true   is_archived(seccion)=true
  admin         can_open=true can_edit=true  can_edit(sec)=true  user_can(edit_post)=true  can_archive=true can_unarchive=true  auth_meta=true
  organizacion  can_open=true can_edit=false can_edit(sec)=false user_can(edit_post)=false can_archive=true can_unarchive=false auth_meta=false

  post_status = publish   permalink = /evento/jornadas-tecnologia-educativa/
  HTML de la página pública (md5, pedido desde fuera del contenedor):
      antes=202ebfbcd565   marcado=202ebfbcd565   desmarcado=202ebfbcd565   ¿igual? SÍ

== 2. Desmarcado ==
  (las dos cuentas vuelven exactamente a la foto 0)
```

Cinco hechos, y ninguno es evidente antes de medirlo:

1. **`can_open` no cambia nunca.** Las dos siguen entrando al taller. El cierre
   se nota al guardar, no al mirar.
2. **El cierre baja a las hijas sin que nadie lo copie.** `is_archived()`
   pregunta siempre por la raíz (`EventAccess::root_id()`), así que la sección 9
   queda cerrada sin llevar meta ninguna.
3. **Alcanza también fuera del aplicativo.** Como la regla vive en `can_edit()`
   y `map_meta_cap` desemboca ahí, `user_can( 'edit_post', … )` devuelve `false`
   también para el escritorio, la edición rápida y la REST. No es una
   comprobación de pantalla.
4. **`can_archive` sigue en `true` para el área con el evento ya cerrado, y
   `auth_meta` pasa a `false`.** No es contradictorio: `can_archive()` responde
   «¿puede cerrar este evento?» y sigue siendo que sí; quien decide si se acepta
   el envío es `can_toggle_archived()`, que mira el estado de hoy y, con el
   evento cerrado, exige administración. Volver a marcar lo marcado no cambia
   nada, y desmarcar no le corresponde.
5. **La página pública es byte a byte la misma**, y el `post_status` sigue en
   `publish`.

## Alternativas consideradas

### Opción 1: una meta propia, `evt_archived`, que marca el área y desmarca administración — ELEGIDA

Marca booleana en el evento raíz, puesta a mano desde el taller por el área que
organiza el evento y quitada solo por quien administra el aplicativo.

- Pros: cada marca significa una cosa; el cierre es explícito, con fecha y con
  responsable; se deshace en un clic; y no toca la salida pública, que es lo que
  el encargo pide.
- Contras: es una marca más que consultar, y un evento migrado llevará dos.

### Opción 2: una sola marca para las dos cosas, reutilizando `evt_legacy` — DESCARTADA

Es la alternativa que había que evaluar en serio, porque a primera vista ahorra
una meta y porque **la migración pone las dos a la vez** a los eventos que trae
del sistema actual, que es justo lo que invita a pensar que sobra una.

- Pros: una meta menos; en la migración las dos coinciden, así que en el 100 %
  del contenido de hoy son indistinguibles; un solo sitio que consultar.
- Contras, y por eso se descarta:
  - **Los dos conjuntos coinciden hoy y dejan de coincidir mañana, en las dos
    direcciones.** Un evento nacido en el aplicativo nuevo se cerrará al acabar
    —recibe `evt_archived`— y **nunca** tendrá contenido el tema que congelar. Y al
    revés: un evento migrado al que haya que corregirle una errata se desmarca
    como histórico y **sigue siendo** contenido contenido del maquetador, congelado. Con una sola
    marca, desmarcar para corregir un dato le devolvería al editor los campos
    nuevos sobre una página que no los usa, y regenerar su contenido borraría la
    maquetación.
  - **Quien las pone no es la misma persona ni el mismo momento.** `evt_legacy`
    la escribe un guion, una vez, en la migración; `evt_archived` la pone y la
    quita una persona, tantas veces como haga falta. Una marca que unas veces es
    de solo lectura para el código y otras es un botón de la pantalla es una
    marca con dos dueños.
  - **Responden a preguntas distintas y las consulta código distinto.** «¿Pinto
    el contenido tal cual o lo regenero?» la responde la vista pública; «¿puede
    esta persona guardar?» la responde `EventAccess`. Juntarlas ata la política
    de permisos al formato del contenido, y el primer evento que rompa la
    coincidencia obliga a desdoblarlas con datos ya en producción, que es el peor
    momento posible.
  - No ahorra prácticamente nada: una constante y un `get_post_meta`.

### Opción 3: derivarlo de las fechas, sin marca — DESCARTADA

Que un evento terminado hace más de N meses se cierre solo, sin que nadie
decida nada.

- Pros: cero campos nuevos y cero decisiones que alguien pueda olvidar tomar.
- Contras: un evento sin fechas —frecuente en lo migrado— no se cerraría nunca;
  un evento que sí las tiene se cerraría solo el día menos pensado, en mitad de
  la memoria posterior; y N sería un número arbitrario que habría que defender.
  Además convierte un cierre deliberado en un efecto del calendario, cuando lo
  que se pide es exactamente lo contrario: que alguien decida. Y parte de una
  regla temporal que la [ADR-0012](ADR-0012-politica-de-edicion-y-auditoria.md)
  tampoco quiere.

### Opción 4: despublicar el evento — DESCARTADA

- Pros: no hace falta nada nuevo, `post_status` ya existe.
- Contras: **cambia la página pública**, que es justo lo que no se quiere. Las
  direcciones de los eventos históricos están enlazadas desde fuera
  ([ADR-0003](ADR-0003-cpt-jerarquico-frente-a-paginas-generadas.md)) y
  despublicar es romperlas. Cerrar la edición y quitar la página de la web son
  dos decisiones distintas y no se pueden tomar con el mismo botón.

### Opción 5: bloquear con la papelera — DESCARTADA

- Contras: la papelera es reversible **y se vacía sola a los 30 días**
  ([ADR-0016](ADR-0016-borrar-es-enviar-a-la-papelera.md)). Un evento que se
  quiere conservar para siempre no puede vivir en el sitio del que WordPress
  borra cosas.

## Decisión

### 1. La marca es `evt_archived`, y va en el evento raíz

`EventMetaKeys::ARCHIVED`, booleana, registrada con su `sanitize` y su
`auth_callback` (`EventMetaRegistration::auth_archived()`). Se pone solo en la
raíz; las secciones no la llevan.

### 2. La marca el área; la desmarca administración

| Acción | Quién |
|---|---|
| **Marcar** un evento como histórico | El área del evento: `evt_organiser` con esa área en su perfil |
| **Desmarcar** | Solo `administrator` (`evt_manage_app`) |
| **Editar un evento ya marcado** | Solo `administrator` |

**Cerrar tu propio evento es tuyo.** Es el área quien organiza la jornada, quien
sube el último vídeo y quien sabe el día que ya no queda nada por subir; pedir
turno para decirlo convierte en trámite lo que es una decisión suya.

Reabrirlo, en cambio, necesita a otra persona, y **la asimetría es el punto
entero**: si el área pudiera desmarcar sola, el cierre sería una preferencia que
se cambia de opinión. **Un candado que abre quien lo cerró no es un candado.**

**Antes de marcar hay que avisar.** El taller lo pide con SweetAlert2 y el
diálogo dice con todas las letras que **no hay vuelta atrás sin
administración**. No es una confirmación de cortesía: es la única oportunidad de
enterarse antes de quedarse fuera. Por eso el interruptor de marcar **no vive en
el recuadro amarillo** —esa convención es para lo que solo ve administración
([ADR-0014](ADR-0014-css-del-area-javascript-de-administracion.md))— sino que es
una acción normal del área. El recuadro amarillo se queda para desmarcar.

**Dónde se comprueba, y dos detalles que no son evidentes:**

| Camino | Qué comprueba |
|---|---|
| El manejador del POST del taller, al **marcar** | `EventAccess::can_archive()`, con su nonce |
| El manejador del POST del taller, al **desmarcar** | `EventAccess::can_unarchive()`, que es `evt_manage_app` |
| La pantalla | No pinta cada botón a quien no puede usarlo |
| El `auth_callback` de la meta | `can_toggle_archived()`, vía `EventMetaRegistration::auth_archived()` |

**Marcar pregunta por `can_open()`, no por `can_edit()`, y no es un descuido.**
`can_edit()` lleva el cierre encima, así que preguntar por él aquí se mordería
la cola: marcar exigiría poder editar, y lo primero que hace la marca es quitar
esa posibilidad. Lo que hay que comprobar es la regla del área, y eso es lo que
`can_open()` responde.

**El `auth_callback` mira el estado de hoy, no el valor que se va a escribir.**
Un `auth_callback` de `register_post_meta()` recibe la capacidad, la meta y el
post, pero **no el valor**, así que no puede distinguir marcar de desmarcar
preguntándoselo al dato. `can_toggle_archived()` lo saca del estado: con el
evento abierto la marca la toca su área; con el evento cerrado, solo
administración. Sale la asimetría exacta, y también por REST y por cualquier
`update_post_meta()` que pase por la capacidad: el área no se desmarca su evento
por la puerta de al lado.

### 3. Con la marca puesta, `can_edit()` dice que no

Y lo dice para el área que lo organizó y para cualquiera con
`evt_edit_all_areas`, incluido todo lo que cuelga del evento. Administración sí
puede, porque alguien tiene que poder corregir una errata y desmarcarlo. Lo que
exime del cierre es `evt_manage_app`, no llamarse de una manera: `can_edit()`
pregunta por `is_archived()` y por `can_open()`, y el cierre **nunca se definió
por rol**.

El motivo se da en castellano y desde un solo sitio
(`EventAccess::why_not_editable()`): «Este evento está marcado como histórico:
se puede consultar y exportar, pero ya no se edita. Para volver a abrirlo,
pídalo a quien administre el aplicativo.»

### 4. El taller se abre, en solo lectura

`can_open()` es la regla del área **sin** el cierre por encima, y es lo que
consultan el listado, el taller y el botón de marcar. Se entra, se consulta y se exporta; no hay
botones de guardar y arriba se lee el aviso. El listado tampoco lo esconde: lo
marca, y tiene un filtro «histórico» para encontrarlos.

### 5. La página pública no cambia

Ni el `post_status`, ni la dirección, ni el HTML. Medido arriba.

### 6. Convivencia con la ADR-0012

No hay dos reglas que convivan: hay **una** —el acotado por área— y **un cierre
explícito por encima**. La [ADR-0012](ADR-0012-politica-de-edicion-y-auditoria.md)
sigue vigente tal cual: el área edita su evento indefinidamente, sin límite de
calendario, y esta ADR añade la única puerta que lo cierra.

En `can_edit()` se evalúan en ese orden: primero el área (`can_open()`), después
el cierre. Basta que una diga «no» para que la respuesta sea «no».

Y conviene no confundir **«finalizado»** con **«cerrado»**: `EventState::of()`
([ADR-0005](ADR-0005-el-estado-del-evento-se-deriva-de-las-fechas.md)) dice si
el evento ya pasó, y eso se usa para pintar y filtrar, nunca para decidir
permisos.

### 7. `evt_archived` y `evt_legacy` son dos marcas, y significan cosas distintas

| Marca | Quién la pone | Qué significa | Qué código la consulta |
|---|---|---|---|
| `evt_legacy` ([ADR-0008](ADR-0008-migracion-de-los-eventos-historicos.md)) | El guion de migración, una vez | El contenido es contenido del maquetador, congelado del sistema viejo y **no se regenera** | La vista pública y el editor |
| `evt_archived` (esta ADR) | El área, a mano; solo administración lo quita | El evento está **cerrado a edición** | `EventAccess` |

La migración pone **las dos** a los eventos históricos que trae del sistema
actual. Un evento nacido en el aplicativo nuevo puede recibir `evt_archived`
cuando acabe, y nunca tendrá `evt_legacy`.

## Consecuencias

### Positivas

- **Se puede cerrar un evento y decirlo**, con responsable y con un motivo que
  se lee en pantalla, en vez de confiar en que nadie lo toque.
- **El cierre no se puede rodear.** Vive en `can_edit()`, por donde pasa
  `map_meta_cap`, así que alcanza al taller, al escritorio, a la edición rápida
  y a la REST con una sola línea.
- **El cierre baja solo a las secciones**, porque `is_archived()` pregunta por la
  raíz. No hay nada que replicar en cada pantalla ni que recordar al añadir una.
- **No se pierde nada de vista.** El evento se sigue listando, se sigue abriendo
  y se sigue exportando.
- **Es reversible en un clic**, por quien administra, sin tocar la base de datos.
- **El área cierra su propio evento sin pedir turno**, que es la razón de ser
  del aplicativo, y lo hace sabiendo lo que hace porque el aviso se lo dice.
- **Los eventos se cierran de verdad.** Una marca que solo pone administración
  es una marca que casi nadie pide y que casi nunca se pone.

### Negativas

- **Hay dos marcas parecidas** y quien lea el código tiene que saber cuál es
  cuál. De ahí la tabla del punto 7, y por eso está también en el docblock de
  `EventMetaKeys::ARCHIVED`.
- **Un área puede dejarse fuera de su propio evento por error**, y entonces
  necesita a administración. Es el precio de que «cerrado» signifique algo; por
  eso el aviso no es opcional y por eso `why_not_editable()` dice a quién hay
  que pedírselo.
- **`evt_edit_all_areas` no exime del cierre.** La capacidad existe y se puede
  conceder a mano desde WPFront, pero no abre un evento cerrado: lo que exime es
  `evt_manage_app`.
- **Es una decisión que alguien tiene que tomar.** Un evento que nadie marque no
  se cierra nunca, y el aplicativo no va a recordárselo a nadie: no hay aviso ni
  automatismo.
- **No queda registro de quién marcó ni cuándo.** La meta es un booleano, y con
  el área pudiendo marcar hay más gente que puede hacerlo. Cuando se implemente
  la auditoría de la [ADR-0012](ADR-0012-politica-de-edicion-y-auditoria.md)
  —que a esta fecha sigue sin escribirse—, marcar y desmarcar tienen que entrar
  en ella.

### Neutras

- La marca **no cambia la publicación**: un evento histórico puede estar
  publicado, en borrador o en la papelera, y eso lo decide su `post_status` como
  siempre.
- No se toca `EventState`: `finalizado` sigue saliendo de las fechas
  ([ADR-0005](ADR-0005-el-estado-del-evento-se-deriva-de-las-fechas.md)). El
  estado histórico es transversal a los tres estados derivados y por eso en el
  listado es un filtro aparte, no un cuarto valor.
- Lo que hoy no cuelga del evento —ponentes y actividades, que se acotan por
  área— no queda cerrado por esta marca. En cuanto cuelguen de un evento,
  `root_id()` los alcanzará sin tocar nada.
