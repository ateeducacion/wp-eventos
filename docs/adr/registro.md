---
id: REGISTRO-ADR
title: "Índice de estados de las ADR"
status: Aceptada
date: 2026-09-14
related:
  issues: []
  prs: []
  adrs: [ADR-0001, ADR-0002, ADR-0003, ADR-0004, ADR-0005, ADR-0006, ADR-0007, ADR-0008, ADR-0009, ADR-0010, ADR-0011, ADR-0012, ADR-0013, ADR-0014, ADR-0015, ADR-0016, ADR-0017, ADR-0018, ADR-0019, ADR-0020, ADR-0021, ADR-0022, ADR-0023, ADR-0024, ADR-0025, ADR-0026, ADR-0027, ADR-0028, ADR-0029, ADR-0030, ADR-0031, ADR-0032, ADR-0033, ADR-0034, ADR-0035, ADR-0036]
  sdds: [SDD-0001, SDD-0002]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# Índice de ADR

La tabla es el registro único de estados; no se duplican listas por estado.
Ver la [guía](README.md) y la [plantilla](plantilla.md).

Las doce primeras ADR de esta tabla se redactaron el 2026-09-12, sobre la
foto del sistema anterior tomada ese mismo día, que es material de
investigación y vive en `.local/`. Están en `Aceptada` porque fijan la
arquitectura acordada **antes** de escribir el código, no porque haya nada
desplegado: a esta fecha el sitio de destino sigue funcionando exactamente como
lo describe ese material, y no se ha tocado nada allí. Lo que se descubra al
implementar se recoge en una adenda o en una ADR que sustituya, nunca
reescribiendo el registro aceptado.

**Dos ADR se retiraron antes del primer commit.** Dos de las decisiones
redactadas aquel 2026-09-12 salieron del repositorio antes de publicarlo:
describían por dentro una instalación que no es nuestra, y eso no puede
versionarse en un repositorio público. Su texto se conserva como material de
investigación fuera del repositorio. No se dice aquí cuáles eran ni de qué
trataban: el resumen sería la misma fuga que la retirada evita. Como todavía no
había commit, los identificadores no estaban congelados
([ADR-0009](ADR-0009-identificadores-internos-en-ingles.md)) y la serie se
renumeró entera para que no quedaran huecos.

**Ampliación del 2026-09-13: ADR-0013 y ADR-0014.** Las dos salen de
construir las pantallas propias del aplicativo y no de la foto de producción:
la primera elige el editor con el que se escriben los dos campos de código a
medida —el que ya trae WordPress, medido en el wp-env del proyecto— y la
segunda decide quién puede escribirlos: **el CSS lo escribe el área, el
JavaScript solo administración**, porque el CSS cambia cómo se ve una página y
el JavaScript ejecuta código en el navegador de cada visitante. La ADR-0014
estrena dos campos de código que podrían haber necesitado una concesión global
de `unfiltered_html` y no la necesitan: ninguna se concede, ni directa ni
indirectamente.

**Ampliación del 2026-09-13: ADR-0015.** Escribir el primer snippet que carga
una librería de terceros obligó a poner por escrito de dónde salen: jsDelivr con
SRI en producción, `node_modules` en desarrollo y en los tests. Es la política
que ya siguen otros aplicativos de la casa, no una invención de este repositorio. **El CDN no es una concesión: es el camino.** De
paso deja claro lo que no decide a la
[ADR-0013](ADR-0013-el-editor-de-codigo-es-el-de-wordpress.md): el editor del
núcleo no se elige por evitar un CDN —eso habría sido perfectamente aceptable—
sino porque ya está en el servidor, trae los dos comprobadores de errores y es
el mismo que la gente ya ha visto en el personalizador.

**Ampliación del 2026-09-13: ADR-0016.** El aplicativo estrenó su botón de
borrar en el listado de eventos y en el panel de secciones, y hubo que decidir
qué significa. Sale de medir, no de suponer: en el wp-env del proyecto
(WordPress 7.1) se mandó a la papelera un evento con tres secciones hijas y se
restauró, anotando el estado de cada una en los tres momentos. De ahí los dos
hechos que la ADR documenta y que no son evidentes: enviar un evento a la
papelera **no** manda a la papelera sus secciones, y sin embargo **sí les rompe
la dirección** mientras dure, porque el slug del padre se queda con el sufijo
`__trashed`. La ADR-0016 se lee junto a la
[ADR-0003](ADR-0003-cpt-jerarquico-frente-a-paginas-generadas.md), que es la
que hace jerárquico al `evt_event` y, con ello, convierte el borrado definitivo
en algo peor que un borrado.

**Ampliación del 2026-09-13: ADR-0017, y la política de edición que la
acompaña.** Faltaba una forma de decir «este evento no se toca más». Quién
puede editar lo decide **solo el área**
([ADR-0006](ADR-0006-el-area-es-un-ambito-no-un-rol.md)), y la
[ADR-0012](ADR-0012-politica-de-edicion-y-auditoria.md) descarta expresamente
cerrar por fechas: cuando una jornada acaba es cuando **más** se toca su página
—los vídeos de las ponencias, las presentaciones, las fotos, las erratas que se
vieron el día del acto—, así que un cierre automático estorbaría en la semana de
más faena. Lo que hay que defender de un evento pasado no es que nadie lo toque,
sino que **se sepa quién lo tocó**: eso es la auditoría, que sigue pendiente, y
no un candado del calendario. El hueco que queda es el contrario: sin marca, un
evento de 2019 se edita indefinidamente. La ADR-0017 lo tapa con `evt_archived`,
y con una asimetría deliberada: **la marca el área** —es su evento y es quien
sabe el día que ya no queda nada por subir— y **solo administración la quita**,
porque un candado que abre quien lo cerró no es un candado. No toca la página
pública, que es byte a byte la misma, medido. Lo delicado de esta ADR no es la decisión sino la
alternativa que descarta: reutilizar `evt_legacy`
([ADR-0008](ADR-0008-migracion-de-los-eventos-historicos.md)) para las dos
cosas. Hoy los dos conjuntos coinciden —la migración pone las dos marcas—, pero
dejan de coincidir en las dos direcciones en cuanto haya un evento nacido en el
aplicativo nuevo o un evento migrado al que corregirle una errata; y `evt_legacy`
decide **cómo se pinta el contenido** mientras que `evt_archived` decide **quién
puede escribirlo**. Se leen las tres juntas: ADR-0008, ADR-0012 y ADR-0017.

**Ampliación del 2026-09-13: ADR-0018 a ADR-0021, las cuatro del diseño.** Las
anteriores decidían cómo se guarda un evento y quién lo toca; estas cuatro
salen de dibujar las pantallas con los colores y los controles reales del
aplicativo —el lienzo y sus fuentes están en [`.design/`](../../.design/README.md)—
y deciden **qué ve** quien organiza una jornada. Se leen juntas y en este orden:

La [ADR-0018](ADR-0018-el-taller-del-evento-es-una-sola-pantalla.md) recoge el
taller en una sola pantalla —la columna de páginas del evento a la izquierda y,
a la derecha, la página elegida con sus elementos— porque con pestañas planas
no se ve en ningún momento **qué evento** y **qué página** se está tocando. Es
la que da forma a lo que está medido sobre la pantalla del sistema anterior —15
secciones, 139 campos y 5.671 px de scroll para editar una sola página—, y esa
medición está en `.local/`.

La [ADR-0019](ADR-0019-el-tipo-de-pagina-se-elige-al-crear.md) la pidió la
persona usuaria: el tipo de una página se elige al crearla y **no se cambia
después**, porque cada tipo trae sus elementos preparados y cambiarlo dejaría
elementos huérfanos. Sustituye en la práctica al desplegable «Tipo de página»
del sistema anterior, que hoy se puede mover en cualquier momento, y asume su
coste honesto: quien se equivoque con la página ya escrita copia el contenido a
mano.

La [ADR-0020](ADR-0020-el-consentimiento-es-una-casilla.md) es **un cambio de
procedimiento**, no de código: el consentimiento informado deja de imprimirse,
firmarse a mano, escanearse y subirse, y pasa a leerse en la página y marcarse
«Acepto». Lo que **no** se simplifica es la constancia: se guarda la versión
exacta del texto vigente en ese momento y la marca de tiempo, porque un
consentimiento sin constancia de qué se aceptó no vale si alguien lo reclama.
De paso vacía el montón de ficheros personales que el SDD-0001 señala en el
almacén de adjuntos del sistema anterior. Se lee con la ADR de las
inscripciones.

La [ADR-0021](ADR-0021-el-ponente-pertenece-a-su-evento.md) es la única de las
cuatro que trae un dato medido: la repetición real de ponentes entre eventos
está **entre el 5 % y el 12 %** —entre el 88 % y el 95 % salen en un solo
evento—, así que el ponente cuelga de su evento y no de un catálogo global.
Descarta a sabiendas la tabla de participación y el CPT de participación, y
responde a las ediciones anuales con un botón «Copiar ponentes de otro evento».
El argumento que cierra la decisión no es el número sino este: en un evento
terminado **no se quiere** que la foto y la biografía cambien solas.

**Ampliación del 2026-09-13: ADR-0022.** Al construir la vista pública se
descubrió que la decisión de pintarla **dentro de `the_content`**, dejando
cabecera, pie y assets al tema, no estaba escrita en ninguna ADR: vivía en el
docblock de `src/Evt/PublicFront/EventView.php` y en ningún otro sitio. La
ADR-0022 la sustituye y la pone por escrito al revés: la página de un evento se
pinta entera, doctype incluido, en `template_redirect`. Como la decisión
sustituida no era una ADR, **no hay ningún `superseded_by` que rellenar**, y la
propia ADR-0022 lo dice en su apartado de estado para que nadie busque una
ADR-fantasma. Lo que sí cierra es el punto que la
[ADR-0010](ADR-0010-la-pagina-la-genera-codigo-versionado.md) dejó abierto
—«el tema queda sin resolver […] esa decisión ata o desata el aplicativo del
tema»—: lo desata, y la ADR-0010 sigue vigente sin tocarse. La otra ADR que se
lee con ella es la [ADR-0008](ADR-0008-migracion-de-los-eventos-historicos.md):
un evento con `evt_legacy` **se queda en el tema**, porque su contenido es el tema
congelado y sin el maquetador se rompería. Las cifras de la ADR están medidas en el
wp-env de este repositorio el 2026-09-13, con su aviso de que el tema local es
Twenty Twenty-Five y no el tema, así que la mejora real al desplegar será mayor
que la que ahí se documenta y **no está medida**.

**Ampliación del 2026-09-13: ADR-0023.** Hasta aquí las ADR decidían **quién**
edita un evento; ninguna decía nada de **cuándo**, y el taller es una pantalla
larga que dos personas del mismo área abren a la vez sin enterarse. La ADR-0023
resuelve el choque **sin inventar nada**: usa el bloqueo de edición nativo de
WordPress, la meta `_edit_lock` y su Heartbeat. La razón de elegir el nativo no
es ahorrar código, aunque también: es que **se comparte con `wp-admin`**. Un
`evt_event` se puede abrir en `post.php` como cualquier entrada
([ADR-0003](ADR-0003-cpt-jerarquico-frente-a-paginas-generadas.md)), así que
un bloqueo propio habría dejado dos verdades sobre quién está editando y la
puerta del escritorio abierta de par en par. El bloqueo es del
**evento raíz**, no de la página abierta: dos personas en dos secciones del
mismo evento se pisan igual, porque comparten navegación, orden y apariencia.
Está medido el mismo día con **dos sesiones de navegador simultáneas**, y lo que
la ADR no esconde son sus tres pérdidas: un bloqueo **caduca** a los 150
segundos, «Tomar posesión» **no pide permiso** y lo escrito sin guardar por la
otra persona **no se recupera**, y si el navegador se cierra de golpe la baliza
que libera el bloqueo puede no salir. Se lee junto a la
[ADR-0012](ADR-0012-politica-de-edicion-y-auditoria.md), cuya auditoría —todavía
sin escribir— es la que dejaría rastro de quién tomó posesión.

**Dos roles, y el CSS es del área.** Las dos cosas mueven la misma raya y se
leen juntas.

El aplicativo tiene **un solo rol propio**: `evt_organiser`, que hace todo lo de
**su área**, y por encima el `administrator` nativo de WordPress, que hace todo
en todas y se queda con `evt_edit_all_areas` y `evt_manage_app`. No hay un rol
de coordinación intermedio, y la
[ADR-0006](ADR-0006-el-area-es-un-ambito-no-un-rol.md) lo descarta en su opción
5 con el argumento que lo zanja: un rol que se distingue de otro solo por
«saltarse el ámbito» no describe una función, describe *no tener ámbito*, y eso
en WordPress ya se llama `administrator`. Los permisos se preguntan siempre por
**capacidad** y nunca por nombre de rol, así que `EventAccess` no depende de
este reparto. Lo que sí hace falta es retirar el rol que llegó a existir: el
snippet de roles nunca revoca nada, así que `evt_retire_coordinator_role()` lo
quita **una sola vez**, con opción de guarda, y a quien lo tuviera le deja
`evt_organiser` para que ninguna cuenta se quede sin rol.

Y **`evt_edit_custom_css` es del área** mientras `evt_edit_custom_js` se queda
solo en `administrator`. La decisión de fondo de la
[ADR-0014](ADR-0014-css-del-area-javascript-de-administracion.md) —dos
capacidades y no una— es la que lo hace posible, y la frase que la sostiene es
esta: **el CSS cambia cómo se ve una página; el JavaScript ejecuta código en el
navegador de cada visitante**. Un CSS mal escrito deja la página fea y se
deshace recargando; un JavaScript mal copiado se lleva la sesión de quien mira,
y es exactamente lo que WordPress protege con `unfiltered_html`. En
la pantalla: el CSS es un campo normal de la pestaña «Código», que ve también el
área; el recuadro amarillo de «Solo administración» envuelve **solo** el
JavaScript, que es lo único que de verdad ve únicamente administración.

**Revisión documental del 2026-09-12.** Después de redactarlas se cotejaron
las doce entre sí y contra el material de `.local/`. No cambió ninguna
decisión; sí cuatro cifras y cuatro etiquetas de fase, corregidas **en el
propio texto** y no como adenda, porque ninguna ADR se había publicado
todavía y todas llevan la misma fecha:

| Qué se corrigió | Dónde | Contra qué se midió |
|---|---|---|
| El recuento de términos de área | ADR-0006, ADR-0008, SDD-0001, PLAN-0001 | Las listas de exclusión del sistema anterior, cruzadas con los términos de la exportación ([ADR-0004](ADR-0004-una-taxonomia-por-dimension.md), «Nota sobre estas cifras») |
| Los recuentos de identificadores excluidos | ADR-0005 | Ídem, con las dos fuentes de `.local/` coincidiendo |
| «Hay términos que no encajan en ninguna dimensión» → **el reparto es exacto**, no sobra ni falta ninguno | PLAN-0001 (P-07, retirado), REQ-0001, SDD-0002 | Ídem |
| Números de fase | ADR-0005, ADR-0007, ADR-0008 | La numeración canónica de [PLAN-0001 §3.1](../plan/PLAN-0001-implantacion-por-fases.md) |

Sigue abierto, y no se ha tocado, el otro pendiente que salió del mismo cotejo:
**unas cuantas páginas raíz que nadie ha clasificado** como evento o como
página de sistema. Es un pendiente real (P-05 del plan) y no tiene nada que ver
con el anterior, aunque el parecido de las cifras invitaba a confundirlos.

Ninguna está en `Propuesta`: lo que sigue abierto se anota como pendiente en
[SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md), no como una ADR a
medias. Y hay una sustitución, la única hasta ahora:
[ADR-0007](ADR-0007-las-inscripciones-siguen-en-el-sistema-anterior.md) dejaba
las inscripciones en el gestor de formularios del sistema anterior, y la
[ADR-0032](ADR-0032-la-inscripcion-es-un-contenido-del-evento.md) las trae aquí.
Se conserva por su contexto, que es la evidencia de las tres que vinieron
después; lo que ya no vale es su decisión.

| ID | Título | Estado | Fecha original | SDD relacionada |
|----|--------|--------|----------------|-----------------|
| [ADR-0001](ADR-0001-repo-entorno-desarrollo-no-plugin.md) | El repositorio es un entorno de desarrollo, no un plugin | Aceptada | 2026-09-12 | [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0002](ADR-0002-sincronizacion-snippets-eval-file.md) | Sincronización de snippets con wp eval-file y la API de Code Snippets | Aceptada | 2026-09-12 | [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0003](ADR-0003-cpt-jerarquico-frente-a-paginas-generadas.md) | Un CPT jerárquico en lugar de páginas creadas por un formulario | Aceptada | 2026-09-12 | `.local/` (investigación del sistema anterior), [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0004](ADR-0004-una-taxonomia-por-dimension.md) | Una taxonomía por dimensión en lugar de la única convocatoria | Aceptada | 2026-09-12 | `.local/` (investigación del sistema anterior), [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0005](ADR-0005-el-estado-del-evento-se-deriva-de-las-fechas.md) | El estado del evento se deriva de las fechas | Aceptada | 2026-09-12 | [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0006](ADR-0006-el-area-es-un-ambito-no-un-rol.md) | El área es un ámbito, no un rol | Aceptada | 2026-09-12 | `.local/` (investigación del sistema anterior), [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0007](ADR-0007-las-inscripciones-siguen-en-el-sistema-anterior.md) | Las inscripciones siguen en el sistema anterior en la fase 1 | **Sustituida** por [ADR-0032](ADR-0032-la-inscripcion-es-un-contenido-del-evento.md) | 2026-09-12 | `.local/` (investigación del sistema anterior), [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0008](ADR-0008-migracion-de-los-eventos-historicos.md) | Se migra el contenedor de los eventos históricos y se congela su contenido | Aceptada | 2026-09-12 | [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0009](ADR-0009-identificadores-internos-en-ingles.md) | Identificadores internos en inglés, lo que se ve en castellano | Aceptada | 2026-09-12 | [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0010](ADR-0010-la-pagina-la-genera-codigo-versionado.md) | La página la genera código versionado, no una plantilla guardada en la base de datos | Aceptada | 2026-09-12 | `.local/` (investigación del sistema anterior), [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0011](ADR-0011-ci-y-politica-de-pruebas.md) | Integración continua y política de pruebas | Aceptada | 2026-09-12 | [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0012](ADR-0012-politica-de-edicion-y-auditoria.md) | Política de edición y auditoría | Aceptada | 2026-09-12 | [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0013](ADR-0013-el-editor-de-codigo-es-el-de-wordpress.md) | El editor de código es el que ya trae WordPress | Aceptada | 2026-09-13 | [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0014](ADR-0014-css-del-area-javascript-de-administracion.md) | El CSS a medida es del área; el JavaScript, solo de administración | Aceptada | 2026-09-13 | `.local/` (investigación del sistema anterior), [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0015](ADR-0015-librerias-de-terceros-desde-cdn-con-sri.md) | Las librerías de terceros se cargan desde jsDelivr con SRI; en desarrollo, desde node_modules | Aceptada | 2026-09-13 | `.local/` (investigación del sistema anterior), [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0016](ADR-0016-borrar-es-enviar-a-la-papelera.md) | Borrar es enviar a la papelera; el borrado definitivo se queda en el escritorio | Aceptada | 2026-09-13 | [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0017](ADR-0017-el-estado-historico-cierra-la-edicion.md) | El estado «histórico» cierra la edición de un evento, y es una marca distinta de evt_legacy | Aceptada | 2026-09-13 | [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0018](ADR-0018-el-taller-del-evento-es-una-sola-pantalla.md) | El taller del evento es una sola pantalla, no pestañas sueltas | Aceptada | 2026-09-13 | `.local/` (investigación del sistema anterior), [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0019](ADR-0019-el-tipo-de-pagina-se-elige-al-crear.md) | El tipo de una página se elige al crear y no se cambia | Aceptada | 2026-09-13 | `.local/` (investigación del sistema anterior), [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0020](ADR-0020-el-consentimiento-es-una-casilla.md) | El consentimiento es una casilla: simplificación administrativa | Aceptada | 2026-09-13 | `.local/` (investigación del sistema anterior), [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0021](ADR-0021-el-ponente-pertenece-a-su-evento.md) | El ponente pertenece a su evento, no a un catálogo global | Aceptada | 2026-09-13 | `.local/` (investigación del sistema anterior), [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0022](ADR-0022-la-pagina-de-evento-se-pinta-entera.md) | La página pública de un evento se pinta entera, sin el tema | Aceptada | 2026-09-13 | [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0023](ADR-0023-bloqueo-de-edicion-con-el-de-wordpress.md) | Que dos personas no se pisen: se usa el bloqueo de edición nativo de WordPress, no uno propio | Aceptada | 2026-09-13 | [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0024](ADR-0024-un-dia-puede-tener-dos-sedes.md) | Un día puede tener dos sedes: la sede es un dato de la actividad, no del día | Aceptada | 2026-09-14 | [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0025](ADR-0025-no-se-duplican-eventos.md) | No se duplican eventos: se tarda menos en crearlo que en corregir la copia | Aceptada | 2026-09-14 | [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0026](ADR-0026-ponentes-y-actividades-cuelgan-del-evento.md) | Los ponentes y las actividades cuelgan del evento por `post_parent` | Aceptada | 2026-09-14 | [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0027](ADR-0027-los-participantes-son-nuestros.md) | Los participantes son nuestros: no se lee el sistema viejo | Aceptada | 2026-09-14 | `.local/` (investigación del sistema anterior), [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0028](ADR-0028-no-se-copian-ponentes.md) | No se copian ponentes de otro evento: se vuelven a meter | Aceptada | 2026-09-14 | [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0029](ADR-0029-botones-de-icono-e-interruptor-de-publicacion.md) | Botones de icono con bocadillo, y la publicación como interruptor | Aceptada | 2026-09-14 | [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0030](ADR-0030-el-repositorio-se-publica-sin-nada-de-nadie.md) | El repositorio se publica como software libre y no lleva dentro nada de nadie | Aceptada | 2026-09-14 | [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0031](ADR-0031-el-formulario-de-inscripcion-es-nucleo-fijo-mas-preguntas.md) | El formulario de inscripción es un núcleo fijo más unas pocas preguntas por evento | Aceptada | 2026-09-14 | [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0032](ADR-0032-la-inscripcion-es-un-contenido-del-evento.md) | La inscripción es un contenido del evento, y no se enseña en el escritorio | Aceptada (sustituye a la [ADR-0007](ADR-0007-las-inscripciones-siguen-en-el-sistema-anterior.md)) | 2026-09-14 | [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0033](ADR-0033-elegir-taller-aforo-duro-y-cambio-hasta-el-cierre.md) | Elegir taller: aforo duro, cambio hasta el cierre y un solo candado por evento | Aceptada | 2026-09-14 | [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0034](ADR-0034-main-solo-se-mezcla-con-revision-y-ci-en-verde.md) | La rama `main` solo se mezcla con una revisión y el CI en verde | Aceptada | 2026-09-16 | [ADR-0011](ADR-0011-ci-y-politica-de-pruebas.md) |
| [ADR-0035](ADR-0035-sincronizacion-de-snippets-por-contenido.md) | Sincronización de snippets por contenido | Aceptada | 2026-09-19 | [SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) |
| [ADR-0036](ADR-0036-los-ficheros-de-una-inscripcion-no-son-adjuntos.md) | Los ficheros aportados en una inscripción no son adjuntos de WordPress | Propuesta | 2026-09-19 | [ADR-0031](ADR-0031-el-formulario-de-inscripcion-es-nucleo-fijo-mas-preguntas.md), [ADR-0032](ADR-0032-la-inscripcion-es-un-contenido-del-evento.md), [ADR-0033](ADR-0033-elegir-taller-aforo-duro-y-cambio-hasta-el-cierre.md) |

**Ampliación del 2026-09-14: ADR-0024 y ADR-0025.** Las dos cierran preguntas
que quedaron abiertas al implementar el diseño del día anterior. Un día **puede**
tener dos sedes, así que la sede es un dato de cada actividad y la parrilla
agrupa por día y, dentro, por sede
([ADR-0024](ADR-0024-un-dia-puede-tener-dos-sedes.md)): caben las que hagan
falta, no hay «sede 1» ni «sede 2», y ninguna se declara por adelantado. Y los
eventos **no se duplican**
([ADR-0025](ADR-0025-no-se-duplican-eventos.md)): el motivo lo puso quien
organiza los eventos —se tarda menos en crear el nuevo que en corregir la
copia— y lo refuerzan tres más, de los que el que pesa es que **un programa del
año pasado con las fechas cambiadas parece bueno y no lo es**. Se pospone, no se
cierra para siempre.

**Ampliación del 2026-09-14: ADR-0026 y ADR-0027, las cuatro pestañas.** Al
construir Ponentes, Programa, Talleres y Participantes
([ADR-0018](ADR-0018-el-taller-del-evento-es-una-sola-pantalla.md)) hubo que
contestar dos preguntas que las ADR anteriores habían dejado abiertas.

La primera: **cómo sabe un ponente a qué evento pertenece**. Cuelgan del evento
por **`post_parent`**, como las páginas satélite
([ADR-0026](ADR-0026-ponentes-y-actividades-cuelgan-del-evento.md)), y eso hace
que el acotado por área y el cierre por histórico les alcancen **sin una línea
de código de permisos**: `post_areas()` e `is_archived()` ya preguntan por
`root_id()`. Cumple la promesa que la
[ADR-0017](ADR-0017-el-estado-historico-cierra-la-edicion.md) había dejado
escrita —«en cuanto cuelguen de un evento, `root_id()` los alcanzará sin tocar
nada»— y hay una prueba que cierra el evento y comprueba que su ponente deja de
editarse. Lo que el árbol **no** comprueba, y se comprueba aparte, es que la
ficha sea de **este** evento: dos eventos de la misma área comparten término, y
el acotado diría que sí.

La segunda: **de dónde salen las inscripciones**. Y la respuesta obvia era la
equivocada: leer el formulario del sistema anterior. **Se descartó por decisión
de producto** ([ADR-0027](ADR-0027-los-participantes-son-nuestros.md)): los
participantes pasan a gestionarse aquí, con formulario propio, y lo viejo es
*legacy* —no se integra, se deja morir—. El campo que guarda su formulario,
`evt_signup_form_id`, queda **marcado como histórico** y es previsible que
desaparezca. Mientras el formulario propio no exista, las filas entran por el
enganche `evt_participants`, que es la costura por la que entrarán las
inscripciones el día que las haya; lo que sí es del aplicativo —el filtro, que
no distingue tildes, y la exportación a CSV de **lo filtrado**— es puro y se
prueba sin WordPress. La pestaña está vacía hasta entonces, **y lo dice**.

De paso se tapó un hueco que no tenía ADR y que se veía a la primera:
**«Crear evento» no creaba nada.** El botón del listado llevaba al taller sin
evento, y el taller contestaba «elija un evento en la lista». Ahora el taller
sin `?evento=` **es** la pantalla de alta: con el título y la fecha de inicio
basta, el evento **nace en borrador** —no tiene programa ni ponentes, y
publicarlo al crearlo sería publicar una página vacía— y al guardarlo se abre
su taller.

**Ampliación del 2026-09-14: ADR-0028 y ADR-0029.** Dos cosas más que salieron
de usar las pantallas.

**No se copian ponentes de otro evento**
([ADR-0028](ADR-0028-no-se-copian-ponentes.md)). Era lo único que la
[ADR-0021](ADR-0021-el-ponente-pertenece-a-su-evento.md) había prometido y no
se había escrito, y al concretarlo no se sostiene: es **más fácil volver a
meterlos**. Toda la fontanería para copiar y al final hay que editar igual —el
cargo cambia, la biografía cita el curso pasado—, y lo que no se edita rompe:
una foto de otra edición desentona con la apariencia del evento nuevo y unos
datos que ya no casan se publican con pinta de correctos. Un ponente son cuatro
campos. Se pospone y **casi se descarta**.

**Botones de icono con bocadillo, y la publicación como interruptor**
([ADR-0029](ADR-0029-botones-de-icono-e-interruptor-de-publicacion.md)). Cinco
acciones escritas no caben en una fila. Los iconos van **en línea y no con la
tipografía de Bootstrap**: si esa tipografía no llega, el botón se queda vacío,
y entre esos botones está el de borrar. Cada uno dice lo que hace tres veces
—icono, bocadillo y texto para lector de pantalla— y el bocadillo **nunca falta**,
porque sin Bootstrap queda el del navegador. El estado de publicación pasa a ser
un interruptor con el rótulo escrito al lado, y se puede cambiar desde la
pantalla donde se ve, que antes obligaba a ir al escritorio de WordPress. Y el
botón de ver **sale también en lo que está en borrador**: estando dentro y con
permiso, un borrador se previsualiza.

De paso, dos cosas de sitio: la pestaña «Datos» del taller pasa a llamarse
**«Ajustes»**, que es como se entiende, y **«Ajustes» deja de ser una pestaña
del aplicativo** al lado de «Eventos» —es administración, vive en el escritorio
y ahora está en el menú de la cuenta—, con lo que la barra de secciones
desaparece: con una sola sección no hay nada que elegir.

**Ampliación del 2026-09-14: ADR-0030, publicar en abierto.** El repositorio
va a publicarse como software libre, y hasta ahora se había escrito dando por
hecho lo contrario. Lo que había dentro eran tres cosas distintas: la
**infraestructura de un despliegue** —el servidor de analítica con su
identificador de sitio y las URL del aviso de cookies—, la **marca de una
organización** en la cabecera y el pie, y el **mapa del sistema que se
sustituye**. La decisión: el código no lleva nada de nadie —todo sale de
`EventChrome::chrome()`, **vacío por defecto**, así que una instalación recién
hecha no enseña la marca de nadie y **no envía ni una visita a ningún
servidor**—, dónde se despliega va al `.env`, y la investigación previa se va a
`.local/`. Y se comprueba en `scripts/check-public.mjs`, porque una regla que no
se comprueba dura hasta el documento siguiente. **Falta trabajo**: la
comprobación todavía encuentra 566 apariciones en las ADR anteriores y por eso
no está enganchada a `make check` todavía.

**Ampliación del 2026-09-14: ADR-0031.** La
[ADR-0027](ADR-0027-los-participantes-son-nuestros.md) prometió formulario de
inscripción propio y no dijo qué lleva dentro. La
[ADR-0031](ADR-0031-el-formulario-de-inscripcion-es-nucleo-fijo-mas-preguntas.md)
lo contesta con una raya medida: **un núcleo fijo en código más una lista corta
de preguntas por evento**. Lo fijo es lo que la medición encuentra en todos los
formularios de inscripción del sistema anterior —identificador fiscal, nombre,
apellidos, correo, teléfono, centro y consentimiento—; lo variable son las tres
a seis preguntas de logística que nadie puede anticipar: si se queda a comer,
intolerancias, traslados. Una pregunta tiene cuatro cosas —rótulo, tipo,
opciones y si es obligatoria— y, sobre todo, **no tiene lógica condicional ni
reglas de validación propias**: esas dos ausencias son lo único que separa esto
del constructor de formularios del que se sale, y por eso están escritas. Se
descartan por escrito las tres alternativas: todo fijo (no cabe lo
imprevisible, y acaba en un «Observaciones» de texto libre), el constructor
completo (rehacer el sistema anterior para resolver algo pequeño) y el catálogo
cerrado de extras (cubre lo común y falla justo en lo que no se puede prever).
De la medición salen además los dos errores que **no** se repiten: el centro se
elige de un catálogo por código y **nunca se teclea**, y el consentimiento es
una casilla ([ADR-0020](ADR-0020-el-consentimiento-es-una-casilla.md)) y no un
fichero subido —era obligatorio, así que estaba siempre y no probaba nada—. Lo
que la ADR no esconde es que las respuestas a esas preguntas son datos que el
código no entiende: «intolerancias alimentarias» es un dato de salud guardado
como texto de una pregunta cualquiera, sin plazo ni minimización propios.

**Consolidación previa al primer commit, 2026-09-14.** Antes de publicar nada se
ordenaron estos documentos: las correcciones que vivían en adendas fechadas se
integraron en el texto de su ADR, y la ADR de preguntas abiertas —que no decidía
nada y quedó contestada al día siguiente— se retiró, dejando sus alternativas
descartadas dentro de las dos ADR que la cerraron. Con ella se fue un número, y
la numeración se cerró sin huecos. **Esto se hace una vez y solo aquí**: nada
había salido del repositorio todavía, así que ningún identificador era aún la
dirección de nadie ([ADR-0009](ADR-0009-identificadores-internos-en-ingles.md),
«Un identificador es una dirección, no un resumen»). A partir del primer commit,
los números y el texto se congelan y toda corrección es una adenda o una ADR que
sustituya.

**Las inscripciones entran, 2026-09-14: ADR-0032 y ADR-0033.** Quedaban tres
decisiones encadenadas y solo estaban tomadas dos: la
[ADR-0027](ADR-0027-los-participantes-son-nuestros.md) dijo que los
participantes son nuestros, la
[ADR-0031](ADR-0031-el-formulario-de-inscripcion-es-nucleo-fijo-mas-preguntas.md)
dijo qué lleva dentro el formulario, y las dos dejaron sin contestar **dónde
vive una inscripción**. Sin eso no había una línea que escribir.

La [ADR-0032](ADR-0032-la-inscripcion-es-un-contenido-del-evento.md) contesta
que una inscripción es un `evt_registration` que **cuelga del evento por
`post_parent`**, como los ponentes y las actividades
([ADR-0026](ADR-0026-ponentes-y-actividades-cuelgan-del-evento.md)): de ahí
salen gratis el área, el guardián y el cierre por histórico. Y se registra
**cerrado** —sin escritorio, sin REST, sin búsqueda, sin archivo—, porque son
datos personales de alguien que no trabaja aquí y **una puerta que no se abre no
hay que guardarla**. El `post_title` es la referencia, no el nombre. De paso
cierra lo que la ADR-0031 había dejado abierto: las preguntas del evento llevan
un identificador inmutable y la respuesta se guarda bajo él, así que **retocar un
rótulo no desconecta lo ya contestado**. Con ella queda **sustituida la
[ADR-0007](ADR-0007-las-inscripciones-siguen-en-el-sistema-anterior.md)**, cuya
decisión era la contraria, y cae la regla dura de `AGENTS.md` que prohibía
registrar el tipo de contenido. Lo que la ADR no esconde: no hay plazo de
conservación, y el aplicativo pasa a acumular datos personales sin fecha de
caducidad hasta que alguien decida cuál es.

La [ADR-0033](ADR-0033-elegir-taller-aforo-duro-y-cambio-hasta-el-cierre.md) es
la otra mitad, y es la única pieza del aplicativo donde **dos personas hacen a
la vez algo sobre el mismo dato**: la última plaza de un taller la piden dos a la
vez el primer minuto. Se decide **aforo duro** —el taller lleno no se puede
elegir; ni lista de espera ni sobrecupo—, **cambio hasta que cierre el plazo**, y
un **único candado por evento** alrededor de contar, soltar la plaza vieja y
tomar la nueva. Uno por evento y no uno por taller a propósito: un cambio toca
dos talleres, y dos candados en distinto orden son un abrazo mortal que no falla
hasta el día que falla. El candado se toma con la inserción atómica de una
opción, que es única en la base de datos, y caduca pronto para que una petición
muerta no deje el evento bloqueado. Se escribe también la carrera que **no** se
hace —contar en PHP y escribir—, para que no vuelva por parecer la natural. Y se
apunta el techo: las elecciones de un mismo evento se serializan.

**Adenda a la ADR-0011, 2026-09-15: la guarda de Codecov.** Primera adenda del
repositorio, y llega el día después de publicarlo, que es cuando se ve lo que
no se había visto. La
[ADR-0011](ADR-0011-ci-y-politica-de-pruebas.md) saltaba el paso de Codecov
cuando el PR venía de un fork, porque ahí no llega el secreto. Dependabot abrió
seis PR y los seis fallaron: **un PR de Dependabot no es un fork** —es una rama
de este repositorio— pero tampoco recibe los secretos del repositorio, porque
Dependabot tiene su propio almacén. La condición pasa a mirar el motivo de
verdad, que es si hay token, y así cubre los dos casos. `fail_ci_if_error` se
queda: lo que estaba mal no era avisar del fallo, sino correr el paso sin nada
que subir. **Va como adenda y no reescribiendo el texto**: desde el primer
commit, los identificadores y el texto están congelados.

**Segunda adenda a la ADR-0011, 2026-09-15: el suelo de cobertura sube al 90 %.**
La [ADR-0011](ADR-0011-ci-y-politica-de-pruebas.md) eligió empezar en el 10 % y
dejó escrito cuándo subirlo: «cuando el aplicativo tenga pantallas y la
cobertura del proyecto se estabilice». Con el formulario de inscripción y el
aforo dentro, la medición dice **90,74 %**, así que el parche pasa de 10 % a
90 % y el proyecto **deja de ser informativo** y pasa a bloquear también al
90 %. Las dos condiciones juntas son «lo que tocas va con sus tests» y «no se
compensa tocando poco»: con solo la primera, el total puede bajar PR a PR sin
que nada lo diga. De camino salió algo que valía más que el número: el `app()`
de las fixtures reponía los tipos de contenido pero no sus metas, que
`unregister_post_type()` se lleva en cada `tear_down`, así que medio banco de
pruebas corría **sin `sanitize_callback` ni `auth_callback`**. Al arreglarlo, dos
tests en verde se cayeron por describir un mundo que en producción no existe.

**`main` queda protegida, 2026-09-16: ADR-0034.** La guía y `AGENTS.md` decían
desde el principio que nada entra sin PR ni sin que otra persona lo revise,
pero era una costumbre: `main` no tenía ninguna regla y un `push` por despiste
entraba. La
[ADR-0034](ADR-0034-main-solo-se-mezcla-con-revision-y-ci-en-verde.md) la
convierte en una regla de rama de GitHub, **sin excepciones ni para quien
administra**: una aprobación, los checks `lint` y `test` en verde y emitidos por
GitHub Actions, y ni borrar la rama ni reescribirla. Lo que hubo que tocar en
el repositorio es pequeño y tiene su porqué: `ci.yml` deja de saltarse los PR
de solo documentación, porque un check obligatorio que no se emite deja el PR
esperando para siempre. Lo que la ADR no esconde: la regla vive fuera de git, y
hace falta una segunda persona con permiso de escritura para mezclar cualquier
cosa.

**La sincronización de snippets deja de guardar lo que no cambió, 2026-09-19:
ADR-0035.** `evt_sync_snippets_from_dir()` guardaba siempre un snippet
existente, aunque fuera idéntico, y guardar uno activo lo reejecuta y le
cambia `modified`. La
[ADR-0035](ADR-0035-sincronizacion-de-snippets-por-contenido.md) lo resuelve
con un fingerprint SHA-256 del estado que gestiona el repositorio —código ya
normalizado, descripción, ámbito, prioridad y etiquetas—, no con la versión
del aplicativo EVT, que es de otra cosa. Un snippet sin cambios no se guarda;
uno sin cambios pero inactivo solo se reactiva. Las librerías de terceros no
entran: siguen con versión exacta y SRI, como fija la
[ADR-0015](ADR-0015-librerias-de-terceros-desde-cdn-con-sri.md). Lo que la ADR
no esconde: hasta este cambio ningún test de la suite ejercitaba la API de
Code Snippets, y activar el plugin para PHPUnit fue parte del trabajo.

**Un documento de participante no es un adjunto de WordPress, 2026-09-19:
ADR-0036.** El formulario de inscripción no tenía forma de pedir un documento
—una autorización, un justificante—, así que había que sacar a la gente del
formulario para pedírselo por correo. La
[ADR-0036](ADR-0036-los-ficheros-de-una-inscripcion-no-son-adjuntos.md) añade
`file` como quinto tipo de pregunta, modificando en eso la lista cerrada de
cuatro que fijó la
[ADR-0031](ADR-0031-el-formulario-de-inscripcion-es-nucleo-fijo-mas-preguntas.md),
y decide dónde vive el fichero: **fuera de la biblioteca de medios y sin crear
ningún adjunto**. Un `wp_insert_attachment()` abre de serie seis superficies
que el dominio no necesita —biblioteca, página de adjunto, `wp/v2/media`, AJAX
de medios, XML-RPC y URL física—, y taparlas después con filtros falla en
abierto. Lo que no existe no hay que guardarlo: el documento se guarda con
nombre opaco en un subdirectorio propio, la inscripción se queda un descriptor
sin rutas ni URL en su propia meta, y la descarga la sirve el aplicativo tras
comprobar el testigo de esa inscripción o `EventAccess::can_open()` sobre su
evento —abrir y no editar, para que un evento histórico se siga consultando
([ADR-0033](ADR-0033-elegir-taller-aforo-duro-y-cambio-hasta-el-cierre.md))—.
Los assets editoriales del evento no cambian: siguen siendo adjuntos con su URL
pública, y no se instala ni un filtro global sobre la biblioteca. Lo que la ADR
no esconde: hay almacenamiento que limpiar, una descarga que mantener, el
`.htaccess` de denegación no sirve en nginx, y **la política de retención sigue
sin existir** —los documentos viven mientras viva su inscripción, y cuándo
caduca una inscripción se decidirá en otra ADR—.
