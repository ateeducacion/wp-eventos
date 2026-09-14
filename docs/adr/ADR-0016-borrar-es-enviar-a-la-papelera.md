---
id: ADR-0016
title: "Borrar es enviar a la papelera; el borrado definitivo se queda en el escritorio"
status: Aceptada
date: 2026-09-13
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0003, ADR-0006, ADR-0012]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0016: Borrar es enviar a la papelera; el borrado definitivo se queda en el escritorio

## Estado

Aceptada (2026-09-13).

## Contexto

El aplicativo tiene pantallas propias en el frontal: el listado de eventos
(`src/Evt/PublicFront/EventList.php`) y el taller de un evento
(`EventWorkspace::handle()`), con su panel de secciones
satélite. En las dos hay un botón de borrar, y quien lo pulsa no es
administración: es personal de un área con el rol `evt_organiser`, que entra
por una página del sitio y no por `wp-admin`.

Lo que se borra no es una fila de una tabla auxiliar. Por
[ADR-0003](ADR-0003-cpt-jerarquico-frente-a-paginas-generadas.md) un evento
es un `evt_event` **jerárquico**: la raíz es el evento y las hijas son sus
páginas satélite —programa, ponentes, inscripción, multimedia, contacto…—, y
esas páginas **conservan las URL** que hoy tiene el subsitio. Detrás de un
botón de borrar hay, entonces, texto escrito a lo largo de semanas, una
jerarquía y unas direcciones que están enlazadas desde fuera.

WordPress ya trae exactamente el mecanismo que hace falta, y este repositorio
tiene por norma no reimplementar lo que el núcleo hace
([ADR-0012](ADR-0012-politica-de-edicion-y-auditoria.md), sobre no rodear las
garantías de la plataforma): `wp_trash_post()` cambia el `post_status` a
`trash` y guarda el estado anterior en `_wp_trash_meta_status`;
`wp_untrash_post()` lo devuelve. La papelera se vacía sola a los
`EMPTY_TRASH_DAYS` días —comprobado en el wp-env del proyecto: **30**—, y el
borrado definitivo tiene su propia capacidad y su propia pantalla en el
escritorio.

## Problema

Cuando alguien pulsa «Borrar» en un evento o en una sección del aplicativo,
¿qué pasa con el contenido: desaparece o se puede recuperar? ¿Y qué pasa con
las secciones hijas de un evento que se manda a la papelera?

## Factores de decisión

- **El clic equivocado existe.** El botón de borrar de una fila está a un
  centímetro del de editar, y quien lo pulsa no siempre está mirando la fila
  que cree.
- **Lo que se pierde no es recuperable de otra manera.** No hay copia de
  seguridad al alcance de quien organiza un evento, y pedirla al servicio de
  informática por una sección borrada por error no es un plan.
- **Las URL están enlazadas desde fuera**
  ([ADR-0003](ADR-0003-cpt-jerarquico-frente-a-paginas-generadas.md)): un
  borrado definitivo rompe enlaces que hay en correos, en circulares y en otras
  webs, y eso no se arregla restaurando nada.
- **Quien borra no es administración.** El acotado por área
  ([ADR-0006](ADR-0006-el-area-es-un-ambito-no-un-rol.md)) dice qué puede tocar
  cada persona, pero no dice nada de cuánto daño puede hacer de un clic.
- **El núcleo ya lo resuelve.** Papelera, restaurar, purga automática y
  capacidad de borrado definitivo están escritos, probados y traducidos.
- **La acción tiene que ser reversible por quien la hizo**, sin abrir un
  ticket y sin entrar al escritorio, que es justo lo que este aplicativo existe
  para evitar.

## Comportamiento real, medido

Todo lo que sigue está **medido en el wp-env del proyecto**, no supuesto. Se
creó un evento raíz con tres hijas —una publicada, una en borrador y una que ya
estaba en la papelera—, se mandó el padre a la papelera, se miró, se restauró y
se volvió a mirar. WordPress **7.1**:

```
== 0. Antes de nada ==
  43 "MEDICION padre"               status=publish parent=0  meta_trash_status='' meta_trash_time=''
  44 "MEDICION hija publicada"      status=publish parent=43 meta_trash_status='' meta_trash_time=''
  45 "MEDICION hija borrador"       status=draft   parent=43 meta_trash_status='' meta_trash_time=''
  46 "MEDICION hija ya en papelera" status=trash   parent=43 meta_trash_status='publish' meta_trash_time='1789283044'
== 1. Tras wp_trash_post() del padre ==
  43 "MEDICION padre"               status=trash   parent=0  meta_trash_status='publish' meta_trash_time='1789283044'
  44 "MEDICION hija publicada"      status=publish parent=43 meta_trash_status='' meta_trash_time=''
  45 "MEDICION hija borrador"       status=draft   parent=43 meta_trash_status='' meta_trash_time=''
  46 "MEDICION hija ya en papelera" status=trash   parent=43 meta_trash_status='publish' meta_trash_time='1789283044'
== 2. Tras wp_untrash_post() del padre ==
  43 "MEDICION padre"               status=draft   parent=0  meta_trash_status='' meta_trash_time=''
  44 "MEDICION hija publicada"      status=publish parent=43 ...
  45 "MEDICION hija borrador"       status=draft   parent=43 ...
  46 "MEDICION hija ya en papelera" status=trash   parent=43 ...
```

Y, en una segunda medición, qué pasa con el slug y con la dirección de la hija:

```
0. padre slug=medicion-slug              hija url=/evento/medicion-slug/medicion-slug-hija/
1. papelera: padre slug=medicion-slug__trashed  estado hija=publish
             hija url=/evento/medicion-slug__trashed/medicion-slug-hija/
2. restaurado: padre slug=medicion-slug  estado padre=draft  estado hija=publish
             hija url=/evento/medicion-slug/medicion-slug-hija/
3. hija en papelera: vivas=0  en papelera=1
```

De ahí salen cinco hechos, y conviene no confundirlos:

1. **Mandar el evento a la papelera NO manda a la papelera sus secciones.**
   `wp_trash_post()` no toca a las hijas: ni el `post_status`, ni el
   `post_parent`, ni ninguna meta. Una sección publicada sigue publicada. No es
   un descuido de WordPress: quien reengancha o borra hijas es
   `wp_delete_post()`, no la papelera.
2. **Pero sus direcciones sí se mueven.** Al mandar el padre a la papelera,
   WordPress le añade el sufijo `__trashed` al slug para liberar el bueno. Como
   la dirección de una hija se construye con la del padre, la sección publicada
   deja de responder en su URL de siempre y pasa a responder en una con
   `__trashed` dentro. **El contenido no se pierde; el enlace, mientras el
   evento esté en la papelera, sí se rompe.**
3. **Restaurar el evento devuelve el slug bueno**, y con él las direcciones de
   las hijas. El daño del punto 2 dura lo que dure el evento en la papelera.
4. **El evento restaurado vuelve en borrador, no publicado.** Desde WordPress
   5.6 el valor por omisión de `wp_untrash_post_status` es `draft`
   (`wp-includes/post.php:4209` y su filtro en la 4226: «*Prior to WordPress
   5.6.0, restored posts were always assigned their original status*»). Se deja
   así a propósito y no se instala
   `wp_untrash_post_set_previous_status()`: un evento que se borró por error no
   tiene por qué reaparecer publicado en la web sin que nadie lo mire.
5. **Restaurar el evento no restaura nada más.** Una hija que ya estaba en la
   papelera sigue en la papelera; una publicada sigue publicada. Las dos
   papeleras son independientes, y esto hay que decirlo en pantalla porque no
   es lo que la gente espera.

## Alternativas consideradas

### Opción 1: `wp_trash_post()` en las cuatro entidades — ELEGIDA

Eventos, secciones satélite, ponentes y actividades. Con su vista de papelera y
su acción de restaurar en cada listado.

- Pros: el clic equivocado se deshace en un clic, por la misma persona y en la
  misma pantalla; el contenido sigue en la base de datos con su ID, así que las
  relaciones no se quedan colgando; las URL vuelven al restaurar; la purga a los
  30 días la hace el núcleo y no hay que escribirla; es el comportamiento que
  cualquiera que haya usado WordPress ya conoce.
- Contras: la papelera es estado que alguien tiene que mirar —un evento en la
  papelera sigue ocupando su slug con `__trashed` y sigue contando en las
  consultas que no filtren estado—; hay que pedir el `post_status` explícitamente
  en cada consulta o aparecen cosas que no deberían; y **el borrado no es
  inmediato**, lo que para un dato metido por error con nombre y apellidos es un
  matiz que hay que saber.

### Opción 2: `wp_delete_post( $id, true )`, borrado definitivo — DESCARTADA

- Pros: una sola pasada, sin estado intermedio; nada que purgar; una consulta
  menos de la que preocuparse; y si lo borrado eran datos personales, dejan de
  estar de verdad y en el acto.
- Contras, y por eso se descarta:
  - **No hay vuelta atrás y quien pulsa no es administración.** El daño máximo
    de un clic pasa a ser «un evento entero y su contenido», sin red.
  - **Rompe las URL para siempre.** Con
    [ADR-0003](ADR-0003-cpt-jerarquico-frente-a-paginas-generadas.md) esas
    direcciones son las que hoy están enlazadas desde fuera del sitio;
    restaurarlas no sería posible ni sabiendo qué se borró.
  - **Con un `evt_event` jerárquico el borrado definitivo hace algo peor que
    borrar**: `wp_delete_post()` sí toca a las hijas —les reasigna el
    `post_parent` al abuelo—, así que borrar un evento dejaría sus secciones
    sueltas y colgando de la raíz del sitio, publicadas y sin evento. Medido en
    el mismo wp-env:

    ```
    antes:   hija parent=49 estado=publish
    después de wp_delete_post(padre, true):
             la hija SIGUE EXISTIENDO, parent=0 estado='publish'
    ```

    Es exactamente el estado inconsistente que la jerarquía existe para no
    tener, y encima es silencioso: nada falla, simplemente aparecen páginas
    publicadas sin evento.
  - **Duplicaría una decisión que WordPress ya toma.** El borrado definitivo
    tiene su capacidad, su pantalla y su confirmación en el escritorio; que el
    frontal ofrezca un atajo que se las salta es rodear la plataforma, y eso
    aquí no se hace ([ADR-0012](ADR-0012-politica-de-edicion-y-auditoria.md)).
  - El argumento bueno a su favor —los datos personales— no aplica a estas
    cuatro entidades: un evento, una sección, un ponente y una actividad son
    contenido público del sitio. Cuando lleguen las inscripciones, que sí son
    datos de personas, esa decisión se toma aparte y no se hereda de aquí.

### Opción 3: borrado lógico propio, con una meta `evt_deleted` — DESCARTADA

- Pros: control total sobre qué significa «borrado» y sobre cuándo se purga.
- Contras: es reescribir la papelera del núcleo con menos funciones. Habría que
  filtrar esa meta en **todas** las consultas —las nuestras y las de WordPress:
  menús, sitemaps, búsqueda, REST, feeds— y el primer sitio que se olvide enseña
  contenido borrado. La papelera ya está integrada en todos esos sitios. No hay
  ninguna necesidad que la papelera no cubra. Descartada por reinventar.

### Opción 4: papelera solo para los eventos, definitivo para lo demás — DESCARTADA

- Pros: menos estado que vigilar en las entidades pequeñas.
- Contras: **dos comportamientos para el mismo botón**. El mismo icono, en la
  misma pantalla, unas veces recuperable y otras no; y la fila más fácil de
  borrar sin querer es justamente una sección, que es la que tendría el botón
  irreversible. Una interfaz que castiga el descuido según en qué tabla ocurra
  no es una interfaz, es una trampa. Descartada.

## Decisión

### 1. Borrar es `wp_trash_post()`, en las cuatro entidades

`evt_event` —tanto la raíz como las secciones satélite—, `evt_speaker` y
`evt_activity`. **`wp_delete_post()` no se llama desde el aplicativo.**

Implementado en `src/Evt/PublicFront/EventWorkspace.php`
(`run_row()`, operación `delete`) y, para el evento entero, en el listado.

### 2. Cada listado tiene su papelera y su «Restaurar»

- El listado de eventos: filtro **«Papelera (N)»**, que solo se pinta cuando hay
  algo dentro (`EventListView::counts()`), y un botón
  de restaurar por fila (`EventList::handle()`).
- El taller de un evento: la papelera es una **vista** de la pestaña de
  secciones, no una pestaña más; se pide con `?papelera=1`
  (`EventWorkspace::ARG_TRASH`) y las dos consultas nunca se mezclan
  (`EventWorkspace::children()`).

Restaurar exige lo mismo que editar: quien no podría tocar el evento tampoco lo
saca de la papelera (`EventList::may_restore()`).

### 3. Lo restaurado vuelve en borrador

Se acepta el valor por omisión de WordPress 5.6+ y **no** se instala
`wp_untrash_post_set_previous_status()`. Volver a publicarlo es un clic más, en
su botón de siempre; reaparecer publicado sin que nadie lo mire, no tiene
arreglo.

### 4. Las secciones de un evento en la papelera se quedan como están

No se «arrastra» la papelera hacia abajo ni se restaura hacia abajo. Es lo que
hace WordPress y es lo que se quiere: las hijas tienen su propia papelera y su
propio estado, y borrar el contenedor no es una orden sobre el contenido.

La contrapartida está medida y hay que decirla en pantalla: mientras el evento
esté en la papelera, **las direcciones de sus secciones no responden** —el slug
del padre lleva `__trashed`—, aunque las secciones sigan publicadas. Al
restaurar el evento, vuelven.

### 5. El borrado definitivo es del escritorio de WordPress

Y solo para quien tenga la capacidad. El aplicativo no ofrece ningún atajo para
saltárselo, ni un «vaciar papelera», ni un modificador de teclado.

### 6. La confirmación se pide, y se pide bien

El botón de borrar confirma antes de enviar, con SweetAlert2 y con el verbo en
el botón —«Enviar a la papelera», no «Aceptar»—, degradando a `confirm()` y, sin
JavaScript, enviando el formulario igual
([ADR-0015](ADR-0015-librerias-de-terceros-desde-cdn-con-sri.md) para la carga
de la librería). El mensaje de vuelta dice dónde ha ido lo borrado: «Sección
enviada a la papelera. Nada se ha perdido: está en “Papelera” y se restaura
desde ahí.» (`EventWorkspace::handle()`).

## Consecuencias

### Positivas

- **El error se deshace donde se cometió**, por la misma persona, sin escribir a
  nadie y sin entrar al escritorio.
- **Las URL sobreviven al susto.** Se rompen mientras el evento está en la
  papelera y vuelven al restaurarlo; con el borrado definitivo no volverían.
- **No hay huérfanos.** Al no llamar nunca a `wp_delete_post()`, ninguna sección
  se queda colgando de la raíz del sitio con su evento desaparecido.
- **Cero código de purga.** Los 30 días de `EMPTY_TRASH_DAYS` los cuenta el
  núcleo.
- **Un solo comportamiento para el mismo botón** en las cuatro entidades: lo que
  se aprende en una pantalla vale en las demás.

### Negativas

- **Hay estado que mirar.** Toda consulta de eventos o de secciones tiene que
  decir qué `post_status` quiere; la que no lo diga acabará enseñando algo de la
  papelera. En el aplicativo se hace en `EventWorkspace::children()` y en el
  listado, y es el tipo de detalle que se olvida en la consulta número doce.
- **Un evento en la papelera sigue ocupando su slug**, con el sufijo
  `__trashed`. Crear otro evento con el mismo título mientras tanto funciona,
  pero el slug del que vuelva de la papelera puede acabar con un número detrás.
- **La papelera del evento y la de sus secciones son dos**, y eso hay que
  explicarlo en pantalla o la gente supondrá que restaurar el evento devuelve
  todo.
- **Borrar no borra.** Si algún día se mete por error un dato personal en un
  ponente, mandarlo a la papelera no lo elimina: hay que vaciarla desde el
  escritorio. Es una consecuencia real de esta decisión y hay que conocerla.

### Neutras

- Esta ADR no dice nada de las **inscripciones**, que sí contienen datos de
  personas. Cuando el aplicativo las recoja, su borrado se decide en su propia
  ADR y no se hereda de esta.
- `EMPTY_TRASH_DAYS` no se toca. Si algún día se quisiera una papelera más larga
  o más corta, es una constante de `wp-config.php` del sitio, no código del
  aplicativo.
