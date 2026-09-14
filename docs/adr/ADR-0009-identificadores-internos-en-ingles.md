---
id: ADR-0009
title: "Identificadores internos en inglés, lo que se ve en castellano"
status: Aceptada
date: 2026-09-12
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0003, ADR-0004, ADR-0006, ADR-0008]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0009: Identificadores internos en inglés, lo que se ve en castellano

## Estado

Aceptada (2026-09-12). Fijada antes de escribir la primera línea que se
despliegue.

## Contexto

El vocabulario de este dominio es castellano: evento, ponente, actividad,
área organizadora, curso escolar. El de WordPress y el de PHP no lo es. En
algún punto hay que decidir dónde acaba una lengua y empieza la otra, porque
si no se decide, se decide sola y mal.

Cómo queda cuando se decide sola está a la vista en el sistema que se
sustituye, cuya foto está en `.local/`, que no se versiona:

- La taxonomía se llama `convocatoria` y se registra sobre `page` desde una
  función con nombre inglés: dos lenguas en quince líneas de código.
- El filtro que concede `unfiltered_html` lleva nombre inglés —con el del
  complemento al que servía metido dentro— y actúa sobre un campo rotulado
  «Contenido».
- Varios slugs de rol llevan acentos mal transliterados, y algunos rótulos ya
  no se parecen en nada a su slug: el rótulo se pudo corregir y el slug, ya
  repartido entre la gente, no.

No es que el castellano sea el problema; el problema es que no hay regla, y
un identificador mal transliterado es para siempre.

El argumento del coste es aquí más fuerte que en el repositorio de
referencia, que tomó esta misma decisión con una base de datos de desarrollo
ya poblada. Aquí no hay nada: a 2026-09-12 el repositorio no tiene ningún
commit, no existe ninguna base de datos con un `evt_event` dentro y el sistema
que se sustituye sigue funcionando exactamente como lo describe la
investigación guardada en `.local/`. Renombrar hoy una clave de meta es un
`sed`. Renombrarla cuando 30 eventos estén migrados es un script de migración
sobre contenido publicado.

## Problema

¿Hasta dónde llega el inglés cuando el vocabulario del dominio es castellano
y la interfaz tiene que seguir siéndolo?

## Decisión

**La raya se traza entre lo que lee una máquina y lo que lee una persona**,
no entre código y datos.

### Qué va en cada idioma

| Qué | Idioma | Ejemplo en este repositorio |
|---|---|---|
| Slugs de tipos de contenido | inglés | `evt_event`, `evt_speaker`, `evt_activity` |
| Slugs de taxonomía | inglés | `evt_area`, `evt_type`, `evt_course` (`src/Evt/Taxonomy/EventTaxonomies.php`, constantes `AREA`, `TYPE` y `COURSE`) |
| Claves de meta del contenido | inglés | `evt_section_type`, `evt_start_date`, `evt_end_date`, `evt_venue` (las constantes de `EventMetaKeys`) |
| Claves de meta del perfil | inglés | `evt_area` (`EventAccess::USER_AREA_META`) |
| Capacidades | inglés | `edit_evt_events`, `evt_manage_app`, `evt_edit_all_areas` |
| Slugs de rol | inglés | `evt_organiser` (`evt_role_definitions()`). El otro rol del aplicativo es `administrator`, que no lleva prefijo ni traducción porque no es nuestro |
| Opciones, hooks, acciones de nonce y parámetros de petición | inglés | prefijo `evt_` |
| Clases, métodos, constantes, ficheros y directorios | inglés | `EventAccess::why_not_editable()`, `src/Evt/Domain/EventInput.php` |
| Docblocks `/** */` | inglés | `@param`, `@return`, la frase de resumen |
| Etiquetas de tipos de contenido y taxonomías | **castellano** | «Eventos», «Área organizadora», «Curso escolar» (los `labels` de `EventPostType::register()` y `EventTaxonomies::args()`) |
| Cadenas de interfaz, avisos y mensajes de error | **castellano** | `EventAccess::why_not_editable()` |
| Comentarios de dominio dentro del código | **castellano** | los que explican por qué, no qué |
| **URL de las páginas** | **castellano** | `'rewrite' => array( 'slug' => 'evento' )`, en `EventPostType::register()` |
| Documentación, CHANGELOG y mensajes de commit | **castellano** | este fichero |

Es la raya que ya recoge `AGENTS.md` en su sección de convenciones, y la misma
que siguen los demás aplicativos de la casa.

### Las URL siguen en castellano

Una URL no la lee una máquina: se comparte por correo, se imprime en un
cartel, se teclea. `/eventos/escuelas-rurales-2026/programa-er/` es tan
visible como un rótulo, así que se rige por la columna de la derecha.

Hay además una razón que no es de estilo. Los eventos históricos se migran
conservando su dirección
([ADR-0008](ADR-0008-migracion-de-los-eventos-historicos.md)), y esas
direcciones existen desde 2020 en programas, correos y enlaces de terceros.
Un slug de reescritura en inglés obligaría a redirigir todas las páginas ya
publicadas para no ganar nada.

El CPT registra hoy `slug => 'evento'`, y su propio comentario deja anotado
que conservar la ruta actual `/eventos/<slug>/` exige reescritura en la raíz
del subsitio y su propia ADR, que la fase 1 no aborda (el `rewrite` de
`EventPostType::register()`). Esta ADR no la resuelve: fija
que, se resuelva como se resuelva, será en castellano.

### Las dos excepciones, y por qué

Dos vocabularios cerrados conservan sus literales en castellano:

| Lista | Valores | Dónde |
|---|---|---|
| Tipos de sección | `programa`, `ponentes`, `inscripcion`, `multimedia`, `contacto`, `actividades`, `encuesta`, `participacion`, `preguntas`, `directo`, `otra` | `src/Evt/Meta/EventMetaKeys.php` |
| Estados derivados | `proximo`, `abierto`, `finalizado` | `src/Evt/Meta/EventMetaKeys.php` |

El motivo es que no son palabras, son la clave ajena del sistema del que se
viene. Los tipos de sección están calcados uno a uno de los valores del
desplegable «Tipo de página» del formulario que hoy da de alta los eventos, y
los estados, de los términos `evento-abierto`/`evento-finalizado` de la
taxonomía `convocatoria`. Traducirlos convierte una migración de
correspondencia directa
([ADR-0008](ADR-0008-migracion-de-los-eventos-historicos.md)) en una tabla de
traducción mantenida a mano, que es exactamente el tipo de artefacto que este
proyecto está intentando quitar de en medio.

Es la única vez que la regla se dobla, y se dobla a sabiendas: quien mire
`meta_key = 'evt_section_type'` con valor `inscripcion` verá dos lenguas en
la misma fila. El día que la migración esté hecha y cerrada, la excepción se
queda sin motivo y puede revisarse con una ADR nueva.

### Un identificador es una dirección, no un resumen

De lo anterior se sigue una regla que conviene tener escrita como regla y no
solo como excepción: **un identificador publicado no se reutiliza ni se
renombra.** Se cita desde el código, desde otros documentos, desde enlaces ya
escritos y desde la base de datos de un sitio que ya está en marcha; cambiarlo
para que el nombre vuelva a describir lo que hay dentro rompe todas esas
referencias a cambio de nada que no se arregle con una línea de texto. Lo que se
corrige es **el contenido**, y se dice dónde.

Es la misma razón por la que los roles de área conservan sus slugs con
acentos mal transliterados: renombrar un slug desasigna a quien lo tenga. Y por
la que un identificador retirado —`evt_coordinator`, el requisito `RF-ORG-19`—
deja su hueco vacío en vez de reciclarse para otra cosa.

**La raya está en «publicado».** Mientras nada se haya publicado —ni un commit,
ni una URL compartida, ni una fila en una base de datos ajena— un identificador
no es todavía la dirección de nadie, y ahí sí se corrige en su sitio. Es lo que
permitió ordenar la numeración de las ADR antes del primer commit. Después de
ese commit, los números y los slugs se congelan.

### Lo que no se toca

- **Nombres propios**: los nombres de las áreas organizadoras, de los cursos
  escolares y de las tipologías son términos de taxonomía que escribe una
  persona, no identificadores. No se traducen ni se transliteran.
- **Las claves de los arrays internos** que viajan entre dos métodos de la
  misma petición y no se guardan en ningún sitio.
- **Las variables y los parámetros locales**: un método mal renombrado
  revienta en el primer test; una variable mal renombrada es un `null`
  silencioso.

## Consecuencias

### Positivas

- Una sola lengua por línea, salvo las dos listas cerradas:
  `EventAccess::can_edit()` lee `evt_area` y devuelve un mensaje en
  castellano, y se ve de un vistazo cuál es cuál.
- El coste de aplicarlo es cero, y solo lo es hoy.
- Quien lea `WHERE meta_key = 'evt_start_date'` en la base de datos entiende
  qué está mirando sin saber castellano.
- El aplicativo no hereda el desorden de producción: no habrá un
  `plurilingismo` nuevo.

### Negativas

- Las dos excepciones rompen la regla y hay que explicarlas cada vez que
  alguien las encuentra. Esta ADR es la explicación, y una explicación es
  peor que no necesitarla.
- **Nada lo comprueba automáticamente.** PHPCS valida estilo, no idioma
  ([ADR-0011](ADR-0011-ci-y-politica-de-pruebas.md)); la regla se sostiene
  solo en la revisión de cada PR, y una revisión distraída la deja pasar.
- No hay dominio de traducción: el repositorio no es un plugin
  ([ADR-0001](ADR-0001-repo-entorno-desarrollo-no-plugin.md)) y las cadenas
  van literales en el código, sin `__()`. Traducir la interfaz a otro idioma
  más adelante sería tocar todos los ficheros. Se acepta porque quien lo usa
  trabaja en castellano y no hay ningún otro caso a la vista.
- Este mismo fichero nombra campos, términos y roles en castellano
  —`convocatoria`, `evento-finalizado`— que no existen en el aplicativo. Son
  la foto del sistema viejo, que se guarda en `.local/`; esta ADR es el
  diccionario entre las dos.

### Neutras

- Es la misma regla que en los demás aplicativos de la casa, así que quien
  trabaje en varios no cambia de hábito.
- La interfaz no cambia nada respecto a lo que el personal de las áreas ve
  hoy: los rótulos siguen en castellano y las direcciones también.
