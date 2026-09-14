---
id: PLAN-0001
title: "Implantación por fases del aplicativo de eventos"
status: Propuesta
date: 2026-09-12
related:
  issues: []
  prs: []
  adrs: [ADR-0001, ADR-0002, ADR-0003, ADR-0004, ADR-0005, ADR-0006, ADR-0007, ADR-0008, ADR-0010, ADR-0011, ADR-0012]
  sdds: [SDD-0001, SDD-0002]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# PLAN-0001 — Plan de implantación por fases

**Estado:** propuesta. La **fase 0 está hecha** y se describe aquí como
tal, con lo que dejó y con lo que dejó sin resolver. Las fases 1 a 4 son el
orden propuesto, no una lista de tareas en marcha: a 2026-09-12 no hay nada
desplegado en el sitio de destino y no se ha tocado nada allí.
**Fecha:** 2026-09-12
**Basado en:** [REQ-0001](../requisitos/REQ-0001-aplicativo-de-eventos.md),
`.local/` (investigación del sistema anterior),
[SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) y las catorce ADR
del [registro](../adr/registro.md).

---

## 1. Objetivo del plan

Decir **en qué orden** se sustituye el aplicativo de eventos, qué entrega cada
fase, **con qué evidencia se da por terminada** y qué puede salir mal en cada
una. Lo que hay que hacer está en REQ-0001; por qué se hace así, en las ADR;
qué queda abierto, en SDD-0002. Aquí solo va la secuencia.

El plan tiene un eje: **cada fase deja el sitio funcionando**. En ningún punto
hay un fin de semana en el que las 163 páginas estén a medias.

## 2. Principios

1. **Nada se toca en producción desde este repositorio.** Regla 2 del contrato
   y [ADR-0001](../adr/ADR-0001-repo-entorno-desarrollo-no-plugin.md). Lo que
   aquí se prepara se despliega activando un snippet, con su ventana y su
   vuelta atrás.
2. **Una fase = un problema.** La fase que abarca dos no se entrega.
3. **Primero lo que duele.** El alta y la publicación del evento, no las
   inscripciones, que funcionan (ADR-0007).
4. **Reversible antes que rápido.** Ningún paso borra nada hasta que el
   siguiente esté en pie.
5. **Sin medida no hay mejora.** La línea base de rendimiento se toma antes de
   sustituir nada (REQ-0001 RNF-REN-01).
6. Cada entrega de código pasa `make check` y regenera el bundle sin
   diferencias ([ADR-0011](../adr/ADR-0011-ci-y-politica-de-pruebas.md)).

## 3. Las fases de un vistazo

| Fase | Qué entrega | Estado | Depende de |
|---|---|---|---|
| **0** | Esqueleto del repositorio, entorno de desarrollo, modelo de datos registrado y probado | **Hecha** (2026-09-12) | — |
| **1** | Alta, edición y publicación de eventos por las áreas: pantallas, permisos, escritorio y pintado de las páginas nuevas | Propuesta | Fase 0 |
| **2** | Migración de los 163 contenidos históricos, con las URL intactas | Propuesta | Fase 1, y **bloqueada** por la reescritura de URL |
| **3** | Inscripciones, selección de talleres y aforo dentro del aplicativo | Propuesta | Fase 2, y el análisis de protección de datos |
| **4** | Retirada del camino viejo: la acción que crea páginas desde el formulario anterior, la concesión global de `unfiltered_html` y las Vistas huérfanas | Propuesta | Fases 2 y 3 |

```text
Fase 0 — esqueleto y entorno  ✔ hecha
   └─► Fase 1 — alta, edición y publicación
         │        (bloqueante propio: reescritura de URL en la raíz)
         ├─► Fase 2 — migración de lo histórico
         │        └─► Fase 4 — retirada (parte de páginas y Vistas)
         └─► Fase 3 — inscripciones, talleres y aforo
                  └─► Fase 4 — retirada (parte de formularios y concesiones)
```

### 3.1 Nota sobre la numeración de las fases

**Esta tabla es la numeración canónica.** Ningún otro documento puede usar
otra. Tres ADR aceptadas el mismo día citaban números de un plan que todavía
no existía; se corrigieron contra esta tabla en la revisión documental del
2026-09-12, y las cuatro correcciones fueron:

| Dónde | Decía | Dice |
|---|---|---|
| [ADR-0005](../adr/ADR-0005-el-estado-del-evento-se-deriva-de-las-fechas.md) | Un campo del formulario anterior se heredaba como meta `evt_featured` | Ese campo no marca destacados: es el selector de área, y va a `evt_area`. No hay «destacado» que heredar y la meta se retira del plan |
| ADR-0007 | «fase 2» para dos cosas distintas: las metas del vínculo con el formulario y llevar las inscripciones al aplicativo | Las metas, fase 2 (entregable 2.10); las inscripciones, fase 3 |
| [ADR-0008](../adr/ADR-0008-migracion-de-los-eventos-historicos.md) | El guion de migración «va en la fase 3» | Fase 2 |

Ninguna corrección cambia el contenido de una decisión: solo la etiqueta. Se
hicieron en el propio texto y no como adenda porque las tres ADR se
escribieron el mismo día que este plan y ninguna se había publicado todavía.

---

## 4. Fase 0 — Esqueleto del repositorio y entorno · **HECHA**

Terminada el 2026-09-12. Es la única fase de la que se puede hablar en
pasado.

### Qué entregó

| Bloque | Qué hay | Dónde |
|---|---|---|
| Entorno | wp-env en los puertos **8798** (desarrollo) y **8799** (tests), PHP 8.3, `es_ES`, con Code Snippets, Members, WPFront User Role Editor y SQL Buddy | `.wp-env.json` |
| Empaquetado | `src/Evt` → un único Code Snippet, con la versión leída de la cabecera del CHANGELOG y la guarda `EVT_BUNDLE_LOADED` | `build/pack-snippet.php`, `snippets/evt-eventos-app.bundle.php` |
| Modelo de datos | `evt_event` jerárquico, `evt_speaker`, `evt_activity`; taxonomías `evt_area`, `evt_type`, `evt_course`; metas y vocabularios cerrados | `src/Evt/`, doce ficheros en `src/Evt/load-order.php` |
| Permisos | `EventAccess` como guardián único, acotado por la user meta `evt_area`, fallo en cerrado, motivos en castellano | `src/Evt/Access/EventAccess.php` |
| Roles | `evt_organiser` (el área) más el `administrator` de siempre, snippet suelto, idempotente y aditivo, en `init` prioridad 5. Hubo un tercer rol, `evt_coordinator`, retirado el 2026-09-13 ([ADR-0006](../adr/ADR-0006-el-area-es-un-ambito-no-un-rol.md)) | `snippets/roles-and-profiles.php` |
| Escritorio | Acotado del listado por área y columnas de área y estado | `src/Evt/Admin/EventAdmin.php`, `src/Evt/Admin/Settings.php` |
| Provisión | Idioma, bundle, sincronización de snippets, roles, vocabulario y datos de demostración, en un orden que se comprueba paso a paso | `Makefile` (target `provision`), `scripts/`, `scripts/check-provision.py` |
| Pruebas | 8 ficheros y 48 casos, con el suelo de cobertura del parche al 10 % y la razón escrita de por qué empieza ahí | `tests/unit/`, `codecov.yml` |
| Integración continua | Cuatro flujos de trabajo, con el ejecutor configurable por `vars.EVT_RUNNER` | `.github/workflows/` |
| Documentación | 14 ADR, 2 SDD, este plan y REQ-0001; el material crudo aislado en `.local/` con doble candado | `docs/` |

### Criterios de salida — cumplidos

- [x] `make up` levanta el entorno y lo provisiona sin pasos manuales.
- [x] Los tres tipos de contenido y las tres taxonomías se registran y tienen
      test.
- [x] Los dos roles se crean solos al cargar el snippet, sin ejecutar nada.
- [x] `php build/pack-snippet.php` regenera el bundle sin diferencias.
- [x] Existe usuario de demostración por área y el acotado se ve funcionando.

### Lo que **no** dejó resuelto

Se dice aquí y no en una nota al pie, porque dos de estas cosas bloquean la
fase 2:

| # | Qué falta | Consecuencia |
|---|---|---|
| 1 | La reescritura de URL: el CPT se registra con `'slug' => 'evento'` y mete ese segmento de más en la dirección (el `rewrite` de `EventPostType::register()`) | **Bloquea la fase 2 entera** |
| 2 | No hay ni una pantalla para escribir las fechas, la sede ni el tipo de sección: las metas están registradas, la caja del editor no existe | La fase 1 empieza por ahí |
| 3 | El entorno local es de **un solo sitio** y el destino es un **subsitio** de una red; y no monta el gestor de formularios del sistema anterior | No se puede ensayar en local ni la convivencia con el sistema anterior ni el comportamiento en red |
| 4 | No hay auditoría, aunque [ADR-0012](../adr/ADR-0012-politica-de-edicion-y-auditoria.md) la da por decidida | Requisito RF-ADM-04 sin implementar |
| 5 | Nada pinta todavía: los doce ficheros de `load-order.php` son modelo, permisos y escritorio; ninguno es de presentación | Es el grueso de la fase 1 |

---

## 5. Fase 1 — Alta, edición y publicación por las áreas

**Objetivo.** Que una persona de un área cree, edite y publique un evento
completo de su ámbito desde el escritorio, sin abrir el sistema anterior, y que
el evento se vea bien. Es la fase que responde al encargo literal de
REQ-0001 §2.

### Entregables

| # | Entregable | Requisitos que cierra |
|---|---|---|
| 1.1 | **Reescritura de URL en la raíz del subsitio**, resolviendo la convivencia con `page`, con prueba sobre las 163 rutas de hoy cargadas en local | RNF-URL-03 |
| 1.2 | Caja de campos del evento en el editor: fecha de inicio, fecha de fin, sede y —en las hijas— tipo de sección de la lista cerrada, validando con `Domain/EventInput` | RF-ORG-02, RF-ORG-12 |
| 1.4 | Auditoría de toda mutación permitida —actor, momento en ISO 8601 y valores antes/después— en meta del evento raíz, con tope de entradas | RF-ADM-04, RNF-PDP-03 |
| 1.5 | Motivo «este evento ya ha terminado» en `EventAccess::why_not_editable()` y el corte por estado en `map_meta_cap()` | RF-ORG-06, RF-ORG-10 |
| 1.6 | **Pintado de las páginas nuevas**: cabecera del evento, navegación entre secciones (lo que hoy hace una de las plantillas del sistema anterior), programa leído de `evt_activity` y ficha de ponentes leída de `evt_speaker` | RF-ORG-18, RF-VIS-02 |
| 1.7 | Relación evento ↔ actividades ↔ ponentes, y las pantallas de alta de ambos | RF-ORG-15 … RF-ORG-17 |
| 1.8 | Filtros del escritorio por área, tipología, curso y estado | RF-COO-03 |
| 1.10 | `docs/roles-y-permisos.md`: qué rol necesita cada persona, qué capacidad abre qué pantalla y qué campo del perfil hace falta | — |
| 1.11 | `docs/despliegue-eventos.md`: el runbook de despliegue al sitio de destino, con la ventana, la copia previa y la vuelta atrás | — |
| 1.12 | **Línea base de rendimiento** y auditoría de accesibilidad de las pantallas nuevas, ambas fechadas en `docs/verificacion/` | RNF-REN-01, RNF-ACC-01 |

### Criterios de salida

Son los criterios de aceptación de
[REQ-0001 §8](../requisitos/REQ-0001-aplicativo-de-eventos.md), y además:

- [ ] Un evento creado con el aplicativo se sirve en la raíz del sitio,
      `…/<slug>/`, y no bajo el segmento de más, `…/evento/<slug>/`.
- [ ] Un evento con tres satélites, cinco actividades y dos ponentes se pinta
      entero sin un solo shortcode del sistema anterior en su contenido.
- [ ] Cambiar la fecha de una actividad cambia lo que se publica sin editar
      ninguna página.
- [ ] Tres personas de demostración de tres áreas distintas no se ven entre
      ellas, ni por el listado, ni por enlace directo, ni por la REST API.
- [ ] `make check` en verde, bundle regenerado sin diferencias y el porcentaje
      de cobertura del parche por encima del suelo.

### Riesgos

| Riesgo | Por qué es real | Mitigación |
|---|---|---|
| La reescritura en la raíz no se puede resolver conviviendo con `page` | Hay al menos dos caminos —reescritura propia resolviendo la colisión, o filtro `post_type_link` con su regla— y **ninguno se ha probado** | Es el **primer** entregable de la fase, no el último. Si no sale, se para y se decide antes de escribir el resto |
| El editor de bloques y una caja de metas clásica se llevan mal | El CPT declara `show_in_rest` (`EventPostType::register()`), así que el editor por defecto es el de bloques | Decidir pronto: panel del editor de bloques o `add_meta_box` con el editor clásico forzado para el tipo |
| El pintado se convierte en reimplementar la plantilla del sistema anterior | Son 42 KB de plantilla, con 193 bloques `[if]` anidados y ajustes de diseño evento a evento | La fase 1 pinta lo **estructural**; los ajustes de diseño por evento son una lista corta y cerrada ([ADR-0010](../adr/ADR-0010-la-pagina-la-genera-codigo-versionado.md)) |
| No se sabe con qué tema se despliega | El sitio usa la versión que tenga el tema; el HTML a emitir depende de eso y no está decidido | Aislar el pintado en su módulo, para que cambiar el HTML no toque el dominio |
| El entorno local no reproduce el destino | wp-env es de un solo sitio y no monta el sistema anterior | Añadir la red y el sistema anterior al entorno, o declarar por escrito qué no se puede probar en local |

---

## 6. Fase 2 — Migración de los 163 contenidos históricos

**Objetivo.** Que los eventos que ya existen pasen a ser `evt_event` sin que
cambie ni una URL, ni un ID, ni una línea de contenido. La decisión y sus
argumentos están en
[ADR-0008](../adr/ADR-0008-migracion-de-los-eventos-historicos.md); esta fase
solo la ejecuta.

**No empieza hasta que la fase 1 haya resuelto la reescritura de URL.** Sin
eso, migrar rompe exactamente lo que la migración viene a conservar.

### Entregables

| # | Entregable | Detalle |
|---|---|---|
| 2.1 | **Inventario cerrado de las 45 páginas raíz**: cuál es un evento, cuál es de sistema, una a una | La investigación identificó 9 de sistema y estima ~30 eventos: **~6 quedan sin clasificar** y no se adivinan |
| 2.2 | **Inventario de todo lo que hoy pregunta «¿es una página?»** | Al menos: el fragmento de código que quita `wpautop` con `is_page()`, la taxonomía `convocatoria` registrada sobre `page`, los elementos de menú y el listado incrustado de la portada |
| 2.3 | **Mapa de los términos** de `convocatoria` a las tres taxonomías nuevas: los de área a `evt_area`, los de tipología a `evt_type`, los de curso a `evt_course`, y los de estado **a ninguna**, porque el estado se deriva de las fechas ([ADR-0005](../adr/ADR-0005-el-estado-del-evento-se-deriva-de-las-fechas.md)) | El reparto es exacto: ningún término queda suelto ni cae en dos sitios. Medido cruzando las listas de exclusión del sistema anterior con los términos del WXR, material que vive en `.local/` ([ADR-0004](../adr/ADR-0004-una-taxonomia-por-dimension.md)) |
| 2.4 | **Guion de migración idempotente** con `--dry-run` obligatorio por defecto | Cambia `post_type`; **no toca** `ID`, `post_name`, `post_parent`, `post_content`, `post_date` ni autoría |
| 2.5 | **Copia previa**: exportación WXR del sitio y volcado de la base de datos, con su fecha y su ubicación anotadas en el runbook | Sin copia, no se ejecuta |
| 2.6 | **Verificación de URL**: lista de las 163 direcciones antes, recorrido después, diff obligatoriamente vacío | RNF-URL-01 |
| 2.7 | Marcado `evt_legacy` en cada contenido migrado, y el editor con aviso en lugar de los campos nuevos cuando la meta está puesta | Un contenido cuyo el tema no se va a regenerar no pide fecha de inicio |
| 2.8 | Tratamiento de las fechas de lo histórico | Hoy son texto libre, tal y como las guardaba el formulario anterior: o se les fija fecha al migrar, o `evt_legacy` los excluye de las consultas de estado. **Si no se hace ninguna de las dos, la portada anuncia como próximos los eventos de hace cinco años** |
| 2.9 | Portada con **una** `WP_Query` sobre `evt_event`, en sustitución del listado incrustado de hoy | RF-VIS-03, RF-VIS-04 |
| 2.10 | Metas del vínculo evento → formulario de inscripción y pantalla de gestión, herederas de los campos que hoy guardan ese vínculo | RF-ORG-21 |
| 2.11 | **Ensayo en seco completo sobre una copia del sitio**, con su informe fechado en `docs/verificacion/` | El ensayo comprueba también que el tema pinta el contenido en un `evt_event` |
| 2.12 | Procedimiento de vuelta atrás probado: devolver `post_type` a `page` | La reversibilidad solo cuenta si se ha ejecutado una vez |

### Criterios de salida

- [ ] El diff entre la lista de URL de antes y la de después está vacío.
- [ ] Los IDs, los `post_name` y los `post_parent` de los 163 contenidos son
      idénticos a los de antes.
- [ ] Los 4.597 adjuntos siguen colgando de los mismos padres.
- [ ] Ejecutar el guion dos veces seguidas no cambia nada la segunda vez.
- [ ] Las nueve páginas de sistema siguen siendo `page`.
- [ ] Todos los términos de `convocatoria` están repartidos entre `evt_area`,
      `evt_type` y `evt_course`, salvo los de estado, descartados porque el
      estado se deriva de las fechas. Ninguno queda sin decidir.
- [ ] Ningún evento de hace años aparece como «próximo» en la portada.
- [ ] La vuelta atrás se ha ejecutado una vez sobre la copia y deja el sitio
      como estaba.

### Riesgos

| Riesgo | Por qué es real | Mitigación |
|---|---|---|
| **el tema no procesa `et_pb_*` fuera de `page`** | El contenido histórico son shortcodes del tema; si el tema no los procesa, las 163 páginas se ven como texto con corchetes | Es lo primero que comprueba el ensayo en seco. Es una casilla de ajustes del tema, pero hay que verla funcionando |
| El inventario de `is_page()` se queda corto | El fragmento que quita `wpautop` dejaría de aplicarse y WordPress metería `<p>` en medio del el tema | Cerrar el inventario 2.2 **antes** de ejecutar nada, y repasarlo en el ensayo |
| Las plantillas de página dejan de aplicarse | El formulario anterior escribe `_wp_page_template`, y una plantilla solo se ofrece a los tipos que la declaran | Comprobar qué plantillas usan las 163 páginas y declararlas para `evt_event` |
| Se migra algo que no era un evento | ~6 raíces sin clasificar | Lista revisada persona a persona y aprobada antes de ejecutar |
| La ejecución en producción pilla a alguien editando | El sitio tiene cuentas suscritas y eventos vivos a cualquier hora | Ventana acordada, copia previa y guion que se puede parar a la mitad sin dejar el sitio a medias |
| Mientras haya `evt_legacy`, el sistema anterior y sus Vistas son obligatorios | Es lo que hace que el contenido histórico se siga viendo | Ya se quedan por la fase 3; a partir de la fase 4 será el **único** motivo, y ese día hay que decidir si se paga |

---

## 7. Fase 3 — Inscripciones, selección de talleres y aforo

**Objetivo.** Llevar al aplicativo lo que ADR-0007 dejó fuera a propósito: la
inscripción, la selección de talleres y el aforo. Esa ADR reconoce la deuda y
**esta fase la paga**. Al terminar, ADR-0007 queda sustituida por la ADR nueva
de esta fase.

**No arranca sin el análisis de protección de datos.** No es burocracia: son
miles de registros con nombre, correo, centro y consentimientos firmados, y
moverlos es un tratamiento nuevo (REQ-0001 RNF-PDP-05).

### Entregables

| # | Entregable | Detalle |
|---|---|---|
| 3.1 | **Análisis del tratamiento**: base jurídica, finalidad, plazo de conservación y quién accede | Documento previo, referenciado desde la ADR de la fase |
| 3.2 | ADR nueva que sustituye a ADR-0007, con el modelo de inscripción y de taller | Sin ella no se escribe código |
| 3.3 | Modelo de inscripción y de taller en el aplicativo, con el vínculo al evento por relación real, no por un ID copiado en una meta | RF-INS-02 |
| 3.4 | **Aforo por taller como dato del evento**, no como constantes en un fragmento de código | Sustituye a los fragmentos que hoy repiten el mismo algoritmo copiado, uno por evento, con más de mil líneas duplicadas entre todos |
| 3.5 | Selección de talleres con plazas libres visibles y rechazo del taller lleno **en todas las filas del cuadro** | La mitad de los fragmentos vigentes validan solo la primera fila; está comprobado sobre el material de `.local/` |
| 3.6 | Exportación CSV de inscritos, acotada por área | RF-INS-05 |
| 3.7 | Servicio de los ficheros de una inscripción **previa comprobación de capacidad** | Hoy se sirven desde una ruta propia del sistema anterior, con el nombre en base64, que no es cifrado (RNF-PDP-07) |
| 3.8 | Consulta «en qué se ha inscrito esta persona» y respuesta a peticiones de acceso y supresión | RF-INS-06, RNF-PDP-06. Es una obligación con plazo legal |
| 3.9 | Migración de los registros de inscripción, con su ensayo en seco y su copia previa | Mismo patrón que la fase 2 |
| 3.10 | Formación del equipo que hoy gestiona las inscripciones | Es el único colectivo al que esta fase le cambia el trabajo |

### Criterios de salida

- [ ] Un evento nuevo con talleres se da de alta **sin escribir una línea de
      código y sin duplicar un formulario**.
- [ ] Una persona inscrita en dos eventos aparece en una sola consulta.
- [ ] Se puede llenar un taller desde dos navegadores a la vez sin que se
      pasen las plazas.
- [ ] Ningún fichero de inscripción se descarga sin comprobar capacidad.
- [ ] Los registros migrados cuadran uno a uno con los del sistema anterior.
- [ ] Existe el análisis de protección de datos, firmado y fechado.

### Riesgos

| Riesgo | Por qué es real | Mitigación |
|---|---|---|
| **Aforo y concurrencia** | Dos personas eligiendo la última plaza a la vez. Los fragmentos de hoy validan leyendo y luego escribiendo, sin nada en medio | Comprobación y reserva en la misma operación, con su prueba concurrente. Es el punto donde un fallo se ve en público |
| Datos personales en movimiento | Miles de registros y consentimientos firmados | Ensayo en seco, copia previa y el análisis de 3.1 antes de tocar nada |
| Corte con inscripciones abiertas | Siempre hay algún evento con el plazo vivo | Migrar formulario a formulario, empezando por los cerrados |
| Reimplementar de más | Detrás de la inscripción vienen encuesta, PDF y firma digital | El alcance lo fija la ADR de 3.2, y lo que no esté ahí no entra |
| Reactivar un fragmento viejo «mientras tanto» | Varios de ellos, además de validar solo la primera fila, interpolan el identificador del formulario en el SQL en lugar de usar `$wpdb->prepare` | Prohibido reactivarlos; si hace falta uno antes de la fase, se copia el único que sí valida todas las filas y sí usa `prepare` |

---

## 8. Fase 4 — Retirada del camino viejo

**Objetivo.** Apagar lo que ya no pinta nadie. Es la fase que casi nunca se
hace, y por eso lleva criterios de salida propios en vez de un «cuando se
pueda».

**Nada se borra: primero se desactiva, y se borra pasado el plazo.**

### Entregables

| # | Entregable | Detalle |
|---|---|---|
| 4.1 | **Desactivar la acción «Crear Página»** del formulario de alta del sistema anterior | Se desactiva, **no se borra**: desactivarla es reversible y deja intacto el histórico que ya creó |
| 4.2 | **Acotar la concesión global de `unfiltered_html`** al envío de ese formulario y al campo concreto que la necesita, con el reemplazo escrito antes de tocar nada | Antes hay que comprobar dos cosas en el sitio: que ningún contenido vivo depende de HTML sin filtrar fuera de ese envío, y que la deducción sobre a quién afecta es cierta. **Esto es una tarea en producción, no en este repositorio** |
| 4.3 | **Retirar entero el fragmento de código que la concede**, cuando el formulario anterior deje de ser el alta de eventos | Las dos mitades: la concesión y el guardado sin filtrar de ese campo |
| 4.4 | **Apagar las Vistas que ya no pinte nadie** | La plantilla de la ficha de evento y las estructurales dejan de usarse cuando pinta el código; las de gestión mueren con la fase 3 |
| 4.5 | Retirar las páginas de sistema que ya no tienen sentido: `crear-evento` y `borradores` | La segunda la sustituye el filtro «Borradores» del escritorio |
| 4.6 | Decidir qué se hace con los roles de área heredados y con el rol de gestión de inscripciones | El snippet de roles es aditivo y nunca los tocó (`evt_register_roles()` nunca llama a `remove_role()`). Retirarlos exige repasar quién los tiene |
| 4.7 | Dejar de usar la taxonomía `convocatoria` y, cuando conste que nada la consulta, desregistrarla | La migración la deja de usar, no la borra |
| 4.8 | Decidir si el plugin de Vistas del sistema anterior se desinstala | Solo es posible si ya no queda ningún `evt_legacy` que dependa de él, lo que reabre la opción 2 de [ADR-0008](../adr/ADR-0008-migracion-de-los-eventos-historicos.md) |

### Criterios de salida

- [ ] Han pasado 60 días desde la fase 2 sin que nadie haya creado una página
      con el formulario del sistema anterior.
- [ ] Búsqueda de los shortcodes del sistema anterior en el contenido de los
      eventos **no** históricos: cero.
- [ ] Cada Vista apagada tiene anotado dónde se comprobó que ya no la llamaba
      nadie.
- [ ] Ya no se concede `unfiltered_html` de forma global, y consta la
      comprobación con `current_user_can()` que lo confirma.
- [ ] Ninguna consulta del sitio menciona `convocatoria`.

### Riesgos

| Riesgo | Por qué es real | Mitigación |
|---|---|---|
| Apagar una Vista que **sí** pintaba algo | Las Vistas se llaman desde el contenido de las páginas por ID, y hay 163 contenidos que nadie ha leído entero | Antes de apagar cada una, buscar su identificador en el contenido de las 163 páginas. Apagar de una en una |
| Acotar la concesión rompe contenido | Si algún evento vivo necesita etiquetas que el filtrado normal no admite y se guardan fuera del envío de ese formulario, dejarán de guardarse. **No está comprobado** | Comprobación previa y vuelta atrás preparada: el fragmento original guardado antes de tocarlo |
| Retirar un rol que alguien usa para otra cosa | Los roles de área heredados tienen gente dentro, y el de gestión de inscripciones también | Repasar usuario a usuario; en la duda, se queda |
| La retirada nunca se hace | Es el patrón habitual: la fase que no entrega nada visible se aplaza | Los criterios de salida de arriba tienen fecha (60 días) y dueño en el runbook |

---

## 9. Pendientes conocidos, hoy abiertos

Ordenados por riesgo, el mayor primero. Un pendiente sale de esta tabla
cuando hay evidencia de que está cerrado, no cuando alguien cree que sí.

Los identificadores no se reutilizan, igual que los de las ADR: **faltan P-07
y P-15** porque se cerraron en la revisión documental del 2026-09-12. P-07
decía que unos cuantos términos de `convocatoria` no encajaban en ninguna
dimensión; rehecho el recuento sobre el material de `.local/`, el reparto es
exacto y el desajuste venía de haber contado de menos los términos de área.
P-15 decía que tres ADR citaban números de fase distintos de los de aquí;
están corregidos en su texto (§3.1).

| # | Pendiente | Riesgo | Bloquea | Cómo se cierra |
|---|---|---|---|---|
| P-01 | **La reescritura de URL en la raíz del subsitio no está resuelta.** El CPT sirve las fichas bajo un segmento de más (el `rewrite` de `EventPostType::register()`); el propio fichero lo reconoce en un comentario justo encima. Hay dos caminos y ninguno probado | **Alto** | Fase 2 entera, y con ella la razón principal del proyecto | Prueba en local con las 163 rutas cargadas, resolviendo la colisión con `page` |
| P-02 | **La concesión global de `unfiltered_html` está activa hoy en producción**, en todas las peticiones, para todo el sitio y para todas sus cuentas, con dos editores de roles instalados | **Alto**, y es de hoy, no del plan | Nada del plan; es un riesgo vivo | Entregable 4.2, que es una tarea en producción con su ventana y su copia previa |
| P-03 | **No se sabe si el tema procesa los shortcodes `et_pb_*` en un CPT que no sea `page`.** Si no lo hace, las 163 páginas migradas se ven como texto con corchetes | **Alto** | Fase 2 | Ensayo en seco sobre una copia (entregable 2.11) |
| P-04 | **El inventario de lo que pregunta `is_page()` está abierto**: el fragmento que quita `wpautop`, la taxonomía `convocatoria`, los elementos de menú, el listado incrustado de la portada, y lo que aparezca | **Alto** | Fase 2 | Entregable 2.2, cerrado y revisado antes de ejecutar |
| P-05 | **~6 de las 45 páginas raíz no están clasificadas** como evento o como sistema | Medio-alto | Fase 2 | Revisión una a una (entregable 2.1) |
| P-06 | **Las fechas de los eventos históricos son texto libre**, tal y como las guardaba el formulario anterior. Con el estado derivado, un evento sin fecha sale como «próximo» | Medio-alto | Fase 2 | Entregable 2.8: o se les fija fecha, o `evt_legacy` los excluye |
| P-08 | ~~**La coordinación no puede crear términos.**~~ **Cerrado el 2026-09-13.** Era un hueco porque existía un rol de coordinación sin `evt_manage_app`; al retirarse `evt_coordinator` ([ADR-0006](../adr/ADR-0006-el-area-es-un-ambito-no-un-rol.md)) el alta de términos queda donde estaba, en administración, y ya no se queda nadie a medias. Quien organiza **usa** las áreas que hay | — | RF-COO-05 | Decidido y escrito en [roles y permisos](../roles-y-permisos.md) |
| P-17 | ~~**El rol `evt_coordinator` no desaparece solo.**~~ **Cerrado el 2026-09-13.** Se eligió la segunda salida de [roles y permisos](../roles-y-permisos.md): `evt_retire_coordinator_role()` pasa a `evt_organiser` a quien lo tuviera, borra el rol y deja puesta la opción de guarda `evt_coordinator_role_retired`, al estilo de las opciones de guarda que ya se usaban para retiradas de una sola vez. Corre una vez y no vuelve a tocar nada | — | Hecho | Verificado en el wp-env: el rol desaparece de `wp role list` y la cuenta que lo tenía queda como `evt_organiser` sin `evt_edit_all_areas` |
| P-09 | **Las plantillas de página no están declaradas para `evt_event`.** El formulario anterior escribe `_wp_page_template` | Medio | Fase 2 | Comprobar qué plantillas usan las 163 páginas |
| P-10 | **No hay línea base de rendimiento.** La queja de partida —«es lento»— no está medida por nadie | Medio | Poder demostrar la mejora | Entregable 1.12 |
| P-11 | **El entorno local no reproduce el destino**: wp-env es de un solo sitio, el destino es un subsitio de una red, y no monta el sistema anterior | Medio | Ensayar la convivencia | Añadir la red y el sistema anterior al entorno, o declarar qué no se puede probar en local |
| P-12 | **Varios de los fragmentos de selección de talleres validan solo la primera fila y arman el SQL sin `prepare`.** Reactivar uno para un evento nuevo devuelve los dos defectos | Medio | Nada, mientras no se reactiven | Prohibirlo por escrito y, si hace falta uno antes de la fase 3, copiar el que sí está bien |
| P-13 | **Un fragmento de código activo carga Bootstrap 4.5.2 desde un CDN retirado** | Bajo-medio | Nada; degrada el sitio hoy | Servirlo desde el sitio, o dejar de cargarlo si el aplicativo no lo usa |
| P-14 | **Discrepancia de inventario de fragmentos de código**: la foto de la instalación anterior y el espejo que se conserva no cuentan lo mismo. Faltan dos por localizar | Bajo | La confianza en el inventario | Cotejar los dos listados y anotar qué son los dos que sobran |
| P-16 | **No se ha comprobado un export limitado a un solo formulario**; lo verificado es el completo | Bajo | Entregable 1.9 | Hacerlo la primera vez y anotar el resultado |

## 10. Definición de hecho

Del plan completo, no de una fase:

1. Un área da de alta, publica y despublica sus eventos sin abrir el sistema
   anterior y sin pedirle nada a nadie.
2. Las 163 URL publicadas siguen respondiendo en la misma dirección.
3. La portada, el buscador y las tres taxonomías tratan igual un evento de
   hace cinco años y uno de hoy.
4. Un evento nuevo con talleres no cuesta ni un formulario, ni una Vista, ni
   un fragmento de código pegado a mano.
5. La concesión global de `unfiltered_html` ya no existe en el sitio.
6. La mejora de rendimiento está medida contra la línea base, no contada.
7. Cada pendiente de §9 está cerrado con evidencia o convertido en una ADR
   que dice por qué se acepta.

## 11. Próximo paso inmediato

1. **Resolver P-01**, la reescritura de URL. Es el primer entregable de la
   fase 1 y el único bloqueante duro del plan; hasta que no salga, escribir
   pantallas es trabajo que puede haber que rehacer.
2. En paralelo, **tomar la línea base de rendimiento** (P-10): se puede hacer
   hoy, sobre el sitio tal y como está, y su valor caduca en cuanto se
   despliegue nada.
3. **Cerrar el inventario de las 45 raíces** (P-05) y el de `is_page()`
   (P-04). Son revisión, no desarrollo, y se pueden hacer mientras tanto.
4. Llevar a producción, con su ventana propia y al margen de este plan, el
   acotado de la concesión global de `unfiltered_html` (P-02). Es el único
   riesgo de la lista que está abierto **hoy**.
