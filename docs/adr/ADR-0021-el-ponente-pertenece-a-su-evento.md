---
id: ADR-0021
title: "El ponente pertenece a su evento: no se comparte, se copia"
status: Aceptada
date: 2026-09-13
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0003, ADR-0006, ADR-0008, ADR-0016, ADR-0017, ADR-0018, ADR-0023]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0021: El ponente pertenece a su evento: no se comparte, se copia

## Estado

Aceptada (2026-09-13).

Corrige el diseño que proponía el informe de investigación de la pestaña de
ponentes, cuya opción elegida —persona compartida más participación en
postmeta repetida— **queda descartada aquí**. El resto de aquel informe sigue
valiendo. La pantalla está diseñada en el artboard «4 · Ponentes»
(`.design/Ponentes.dc.html`) del lienzo publicado, cuya URL privada vive en
`.local/lienzo-de-diseno.md`.

## Contexto

Hoy los ponentes son un formulario del sistema anterior: una ficha por ponente
y por evento, con una media de catorce fichas en cada uno de los eventos que
los pintan. Cada ficha se teclea desde cero dentro del evento que la trae, con
su foto vuelta a subir. La propia documentación del CPT nuevo lo recoge como
el problema a resolver: la misma persona comunicadora vuelve en varias
ediciones, y hoy son fichas repetidas evento a evento
(`SpeakerPostType::register()`).

Esa frase presupone que la repetición es masiva. **Se midió, y no lo es.**

### La medición

La exportación del sistema anterior se pidió **sin registros**, así que no se
pueden comparar nombres. Se usó como sonda otra cosa: los **ficheros de foto**.
Si alguien se vuelve a dar de alta y sube la misma foto, WordPress crea
`nombre.jpg` y `nombre-1.jpg`.

La medida se hizo sobre el inventario de medios que hay en `.local/`, contando
los ficheros que subió ese formulario y normalizando sus títulos —sin tildes,
sin sufijo numérico y sin el `-WxH` de los recortes—:

| Qué | Medida |
|---|---|
| **Suelo** de personas dadas de alta dos veces | **≈ 5 %** |
| **Techo grosero** (títulos distintos sobre ficheros totales) | **≈ 12 %** |

El suelo es un suelo de verdad: solo detecta a quien resubió **el mismo
fichero con el mismo nombre**. Quien volvió con otra foto, con la foto
renombrada o subida por otra persona no aparece. **La cifra exacta no se puede
cerrar sin exportar las entradas del formulario**, y eso está dicho como lo
que es: no se sabe, y se sabe por qué no se sabe.

Lo que sí se puede afirmar con la medida en la mano: **entre el 88 % y el 95 %
de los ponentes aparecen en un solo evento.**

Compartir la ficha entre eventos es, por tanto, **diseñar para el caso raro y
pagarlo en el común**.

## Problema

¿Un ponente es una persona del sitio, que participa en varios eventos, o es
una ficha de **este** evento?

## Factores de decisión

- **El dato**: 88-95 % aparecen una sola vez.
- **Acotado por área**: el eje de permisos es `evt_area`
  ([ADR-0006](ADR-0006-el-area-es-un-ambito-no-un-rol.md)). Una ficha
  compartida entre dos áreas no tiene dueño claro.
- **Bloqueo de edición**: si dos personas abren a la vez el mismo evento, el
  bloqueo tiene que alcanzar a lo que cuelga de él
  ([ADR-0023](ADR-0023-bloqueo-de-edicion-con-el-de-wordpress.md)).
- **El pasado no se reescribe**: el programa de 2024 tiene que seguir diciendo
  lo que decía en 2024.
- **La interfaz tiene que ser explicable**: quien organiza una jornada no
  distingue «persona» de «participación».
- **Ediciones anuales**: hay terceras ediciones reales —alguna serie va por la
  tercera, y hay varias «II Jornadas» y «III Jornadas»—. Lo que se decida
  tiene que dar respuesta a eso.
- **Migración**: todas las fichas de hoy hay que traerlas
  ([ADR-0008](ADR-0008-migracion-de-los-eventos-historicos.md)).

## Alternativas consideradas

### Opción 1: seguir como hoy — ficha nueva por evento y sin pantalla

| Pros | Contras |
|------|---------|
| No cuesta nada: es lo que hay. | No existe ninguna pantalla que gestione los ponentes de un evento. |
| | Reordenarlos son cinco ediciones completas de un formulario de más de cien campos. |
| | Dar de alta está limitado a los dos roles escritos en las opciones del formulario: el área no puede. |

Descartada. **Pero conviene fijar bien qué parte de esto era el problema**: no
era la repetición —es el 5 %—, era que **no había pantalla** y que cualquier
cambio costaba una edición completa. Eso es lo que se arregla.

### Opción 2: la persona se comparte, y la participación es postmeta repetida

Era la opción elegida por el informe. `evt_speaker` es la persona, y la
participación se guarda como meta repetible sobre ella: `evt_speaker_event`
(repetido, un valor por evento), más `evt_speaker_role_<eventID>`,
`evt_speaker_featured_<eventID>` y `evt_speaker_order_<eventID>`.

| Pros | Contras |
|------|---------|
| Responde «¿en qué eventos ha estado X?» sin SQL a mano. | Tres claves de meta **con el ID del evento metido en el nombre de la clave**: no se pueden consultar ni ordenar entre eventos, y son ilegibles. |
| Una persona, un currículo, una foto. | El acotado por área pasa a ser «se puede editar si comparte alguna área con las tuyas»: difícil de explicar y más difícil de predecir. |
| Aprovecha `wp_postmeta` sin tabla nueva. | El bloqueo de edición se vuelve ambiguo: un ponente compartido entre dos áreas no se sabe de quién es al abrir un evento. |
| | En la interfaz aparecen **dos acciones que se parecen y no son lo mismo**: «quitar del evento» y «borrar la persona». Es un error esperando a ocurrir. |
| | **Reescribe el pasado**: actualizar la foto o la biografía cambia lo que dice el programa de 2024. |

Descartada.

### Opción 3: la persona se comparte, y la participación es un CPT

Un `evt_speaker_slot`: un post por participación, con su evento, su cargo y su
orden.

| Pros | Contras |
|------|---------|
| Modelo limpio: persona y participación son dos entidades, y lo parecen. | Un tipo de contenido más, con sus capacidades, su menú y su pantalla. |
| Consultable en las dos direcciones sin claves con sufijo. | Una participación **no es un post**: no tiene título, ni contenido, ni autor, ni papelera. |
| | Arrastra los mismos problemas de la opción 2 —área ambigua, bloqueo ambiguo, dos acciones parecidas— y encima cuesta más. |

Descartada por sobrepeso.

### Opción 4: el ponente pertenece a su evento (elegida)

`evt_speaker` cuelga de su evento por `post_parent`, igual que las páginas
satélite. Para las ediciones anuales, un botón que **copia** fichas de otro
evento.

| Pros | Contras |
|------|---------|
| Todo lo que se necesita ya existe: `post_parent`, `EventAccess::root_id()`, el acotado por área, el bloqueo y la papelera. | «¿En qué eventos ha estado X?» deja de poder contestarse. |
| El pasado no se toca: la ficha de 2024 es de 2024. | Actualizar a alguien que repite son N ediciones, una por evento. |
| Una sola acción de borrar, y significa una sola cosa. | La repetición del 5-12 % se queda tal cual, con sus erratas divergentes. |
| Ni tabla, ni CPT, ni claves con el ID del evento en el nombre. | Hay que escribir la copia, con su acotado. |

## Decisión

**Haremos la opción 4. Un ponente pertenece a un evento.** No se comparte
entre eventos: se da de alta dentro del evento que lo trae, con su foto, y ahí
se queda.

En el modelo:

- `evt_speaker` cuelga de su evento por **`post_parent`**, igual que las
  páginas satélite. **Sin meta de participación, sin tabla, sin nada más.**
- El **acotado por área** sale del evento padre con `EventAccess::root_id()`,
  que ya existe y ya se usa. Un ponente **no tiene área propia**: tiene la de
  su evento.
- El **bloqueo de edición** del evento alcanza ahora a sus ponentes sin tocar
  nada, porque `root_id()` ya lo resuelve. Eso cierra la duda que quedó
  abierta al portar el bloqueo
  ([ADR-0023](ADR-0023-bloqueo-de-edicion-con-el-de-wordpress.md)).
- El **cierre por «histórico»** alcanza igual a los ponentes, por la misma
  razón ([ADR-0017](ADR-0017-el-estado-historico-cierra-la-edicion.md)).
- Al enviar un evento a la papelera, **sus ponentes van con él**, como las
  páginas ([ADR-0016](ADR-0016-borrar-es-enviar-a-la-papelera.md)).

En la pantalla (`.design/Ponentes.dc.html`): una rejilla de fichas con foto,
nombre y cargo, con el distintivo «Portada» en quien sale destacado, y el
**orden es el que se ve en la página pública de ponentes**. Arriba, dos
botones: **«Añadir ponente»** —«Nombre, cargo y foto. El resto es
opcional.»— y **«Copiar de otro evento»**.

### «Copiar ponentes de otro evento»

Es la respuesta a las ediciones anuales, y es lo que hace innecesario
compartir:

> Se elige un evento del ámbito y se marcan las fichas a traer.

- **Copia, no enlaza.** Crea fichas nuevas en este evento. A partir de ahí son
  independientes: editar una no toca la otra.
- Solo se ofrecen **eventos que esa persona pueda ver**, con el mismo acotado
  por área de siempre.
- Se copian **nombre, cargo, biografía y foto**. El orden y el «sale en
  portada» se quedan por defecto: son de este evento.
- La foto **se reutiliza como adjunto** (mismo `_thumbnail_id`), no se duplica
  el fichero: la biblioteca de medios ya es grande y no hace falta engordarla.
  Pero **la ficha es nueva**.
- Se dice cuántas se han copiado, y se puede deshacer borrándolas como
  cualquier otra.

Se teclea una vez y nunca más. Eso es lo que la gente quería de «compartir»,
sin nada de lo que «compartir» costaba.

### El argumento que además va a favor

**En un evento terminado no se quiere que la foto ni la biografía cambien.**
El programa de 2024 tiene que seguir diciendo lo que decía en 2024. Un ponente
compartido reescribe el pasado cada vez que alguien actualiza su ficha —justo
en el caso, las ediciones anuales, que parecía justificar el compartir—.

## Consecuencias

### Positivas

- No hace falta ni una regla de permisos nueva: el área, el bloqueo, el cierre
  por histórico y la papelera salen del evento padre con `root_id()`, que ya
  está escrito y ya está probado.
- **Una sola acción de borrar.** Desaparece la pareja «quitar del evento» /
  «borrar la persona», que se parecen y no son lo mismo.
- El contenido publicado de un evento pasado queda congelado también en la
  parte de los ponentes.
- Se evita una tabla de participación, un CPT de participación, y tres claves
  de meta con el ID del evento metido en el nombre.
- La migración de las fichas de hoy es un guion sin decisiones: cada entrada,
  un `evt_speaker` colgado de su evento. **No hay que fusionar personas**, que
  era la parte peligrosa: una fusión mal hecha atribuye la biografía de alguien
  a otra persona en una página pública.

### Negativas

- **«¿En qué eventos ha estado X?» deja de tener respuesta.** No hay consulta
  que la conteste: habría que comparar nombres, que es justo lo que no se puede
  hacer con fiabilidad. Si algún día hace falta un directorio de personas
  comunicadoras del sitio, **hay que rehacer el modelo**, no ampliarlo.
- **Actualizar a quien repite son N ediciones.** Si alguien cambia de centro y
  está en tres eventos, hay que entrar en los tres. El botón de copiar alivia
  el alta, **no la actualización**.
- **La duplicación se queda.** Las fichas de hoy seguirán siendo las mismas, y
  ese 5-12 % de personas repetidas seguirá repetido, con sus nombres tecleados
  de dos formas distintas en dos eventos y nada que lo detecte. Esto es una
  decisión consciente de no arreglar algo, no un descuido.
- **La foto copiada se comparte de verdad.** Al reutilizar el `_thumbnail_id`,
  las dos fichas apuntan al **mismo adjunto**: si alguien borra ese adjunto
  desde la biblioteca de medios, la foto desaparece **en los dos eventos**, y
  uno de ellos puede ser un evento pasado que se quería congelado. La copia
  promete independencia y la da en todo menos en la imagen.
- **Quedan entradas sin padre en la migración.** Las fichas cuyo campo de
  evento venga vacío no tienen a qué colgarse. Esta ADR no decide qué hacer con
  ellas.
- **Hay que escribir la copia.** Con su selector de evento acotado por área, su
  nonce, su POST y su recuento. Es código nuevo que en la opción compartida no
  haría falta.

### Neutras

- El modelo de datos del informe sigue valiendo en todo lo demás —correo,
  enlaces, publicaciones, forma de la imagen, `evt_activity_speakers` como
  array de IDs y no de nombres—. Lo único que cae es la participación.
- La deduplicación por correo pierde su papel de mecanismo global: sigue siendo
  útil dentro de un evento, para no dar de alta dos veces a la misma persona en
  la misma jornada.
- **Dos docblocks quedan desmentidos por esta decisión y hay que reescribirlos**
  (fuera del alcance de esta ADR, que solo documenta):
  - `src/Evt/PostType/SpeakerPostType.php`, que justifica el tipo propio
    por la repetición entre ediciones. El motivo bueno es otro: no había
    pantalla donde gestionarlos.
  - `src/Evt/Access/EventAccess.php`, que dice que un ponente lleva su
    área propia y «es reutilizable entre ediciones, así que compartir un
    ponente con otra área es añadirle ese término». A partir de aquí, un
    ponente **no lleva área**: lleva la de su evento.

## Referencias

- **Lienzo del diseño**: su URL, que es privada, está en `.local/lienzo-de-diseno.md`.
- Fuente del diseño: `.design/Ponentes.dc.html` (artboard «4 · Ponentes»).
- Medición: el inventario de medios y la exportación del sistema anterior del
  2026-09-12, los dos en `.local/`. La exportación se pidió sin registros, de
  ahí el intervalo y no una cifra cerrada.
- ADR-0003 — el evento es jerárquico y lo suyo cuelga de él.
- [ADR-0006](ADR-0006-el-area-es-un-ambito-no-un-rol.md) — el área es el eje de
  permisos.
- [ADR-0016](ADR-0016-borrar-es-enviar-a-la-papelera.md) — la papelera arrastra
  lo que cuelga.
- [ADR-0017](ADR-0017-el-estado-historico-cierra-la-edicion.md) — el cierre de
  un evento terminado.
- [ADR-0018](ADR-0018-el-taller-del-evento-es-una-sola-pantalla.md) — dónde
  vive la pestaña «Ponentes».
- [ADR-0023](ADR-0023-bloqueo-de-edicion-con-el-de-wordpress.md) — el bloqueo
  que ahora alcanza a los ponentes.
