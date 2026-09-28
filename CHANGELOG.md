# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/)
y versionado [SemVer](https://semver.org/lang/es/).

La versión de este fichero es la **única** fuente de verdad: `make bundle` la
lee de la cabecera superior y la escribe en el `@version` del bundle.
`make release` crea el tag y la release en GitHub; el despliegue de los snippets
en el subsitio `eventos` se hace después.

## [0.1.5] — 2026-09-28

### Añadido

- **Cada sección decide por separado si sale en el menú de arriba y si tiene tarjeta en la portada del evento.**
  - Son dos casillas en el formulario de la sección: «Mostrar esta sección en el menú de arriba del evento» y «Mostrar una tarjeta de esta sección en la portada del evento».
  - Vienen marcadas, así que las secciones que ya existen se ven igual.
  - Sin ninguna de las dos, a la sección solo se llega con su enlace.
  - En la tabla de secciones, las etiquetas «Fuera del menú» y «Sin tarjeta en la portada» dicen cuál es cuál
- **Iconos en las secciones.** Cada tipo de sección trae el suyo (un calendario el programa, un sobre el contacto…) y quien organiza puede elegir otro entre catorce dibujos. Salen junto al nombre en el menú del evento, en la tabla de secciones del taller y en la tarjeta de la portada cuando la sección no tiene imagen destacada
- **Mapa en la página de contacto** ([ADR-0046](docs/adr/ADR-0046-el-mapa-de-contacto-es-leaflet-con-puntos-propios.md)).
  - En «Datos de contacto», cada punto lleva sus coordenadas, tal como las copia cualquier mapa de internet (`28.4636, -16.2518`), un texto y, si se quiere, un enlace.
  - «Añadir otro punto» añade los que hagan falta.
  - En la página, un mapa con un marcador por punto y, debajo, la misma lista enlazada a OpenStreetMap, que es lo que queda si el mapa no carga.
  - El mapa se dibuja con Leaflet, desde jsDelivr con SRI, y solo se carga en la página de contacto que tiene puntos.
  - El servidor de teselas se cambia con el filtro `evt_map_tiles`
- **Se avisa de las secciones que no siguen la apariencia del evento.** Si una sección tiene su propio color, tipografía, separador, forma de imágenes o logo:
  - lleva la etiqueta «Apariencia propia» en la tabla de secciones;
  - la pestaña «Apariencia» del evento dice cuáles son y en qué, con el enlace para abrirlas;
  - dentro de la sección, «Apariencia de esta sección» se abre sola, explica qué no sigue al evento y ofrece «Volver a la apariencia del evento», que vacía esos campos de una vez al guardar
- **Lo que solo puede hacer administración se marca en amarillo también dentro de los formularios**, con la misma etiqueta «Solo administración» del recuadro de siempre:
  - en «Datos del evento», que puede asignar cualquier ámbito;
  - en un evento histórico, que lo sigue pudiendo editar

### Cambiado

- **Arriba a la derecha, quien ha entrado se ve igual en el aplicativo y en las páginas públicas.**
  - Sale su nombre y apellidos —no el alias de la cuenta—, su correo y, si organiza eventos, su ámbito, que ocupa hasta dos líneas si es largo.
  - Al lado, su imagen de perfil, la misma que en la barra de WordPress; si el sitio no las enseña, sus iniciales.
  - El menú que abre trae «Mis eventos» —si gestiona eventos—, «Ajustes del aplicativo» —si administra— y «Salir».
  - Sin sesión, sigue saliendo «Acceder»
- **El menú «Eventos» del escritorio de WordPress solo lo ve administración.** Quien organiza gestiona su evento entero desde el aplicativo, así que en el escritorio ya no ve:
  - el menú «Eventos», con sus ponentes y actividades;
  - «Evento», «Ponente» y «Actividad» en el «+ Nuevo» de la barra de arriba.

  Si llega a esas pantallas con un enlace guardado, va a «Mis eventos». Lo que cada uno puede editar no cambia: sigue decidiéndolo el mismo guardián
- **Los ámbitos de «Datos del evento» se eligen en un árbol.** Antes eran una lista de rutas largas («A › B › C»); ahora:
  - cada ámbito va sangrado bajo el suyo, con una raya que dice de quién cuelga;
  - los que están por encima de lo que uno puede elegir salen en gris y sin casilla, para saber de dónde cuelga cada cosa;
  - las ramas se pliegan y se despliegan con una flecha, y arrancan abiertas solo las que tienen algo marcado;
  - la ruta entera sale al pasar el ratón.

  Sin JavaScript, el árbol sale entero y abierto
- **El editor de preguntas de la inscripción es más claro.**
  - El campo «Opciones, una por línea» solo aparece en las preguntas de «Una opción» y «Varias opciones», y sale al cambiar el tipo.
  - El botón «Añadir otra pregunta» añade tantas como se quiera antes de guardar.
  - Cada pregunta guardada tiene la casilla «Quitar esta pregunta al guardar», en vez de tener que borrar su rótulo.
  - Sin JavaScript, todo sigue como antes

### Corregido

- **Al guardar varias preguntas nuevas a la vez, solo se guardaba la primera.** Llegaban todas sin identificador y se tomaban por repetidas
- **Al repintar el formulario de una sección tras un envío rechazado, se perdían los campos propios de su tipo**, como los de contacto

## [0.1.4] — 2026-09-28

### Añadido

- **Dos flechas grandes a los lados de la línea del tiempo** de la portada, para ir al mes anterior y al siguiente sin tener que arrastrar. Los botones pequeños de arriba siguen ahí
- **Columna «Ámbito» en el listado de usuarios**, detrás del rol, con el nombre del ámbito y la ruta entera al pasar el ratón. Los perfiles con varios ámbitos o con uno que ya no existe salen como «Pendiente de resolver»
- **Filtrar el listado de usuarios por ámbito**, con el mismo árbol que el perfil y la opción «Sin ámbito». Un ámbito trae también a quien está en los que cuelgan de él, y se combina con el filtro por rol y con la búsqueda. La columna y el filtro solo los ve administración

### Pendiente antes de desplegar

- Actualizar los dos snippets: el aplicativo y «EVT — Roles y perfiles»

## [0.1.3] — 2026-09-28

### Cambiado

- **El ámbito del perfil de usuario se elige como en un árbol.** El campo enseña solo el nombre del ámbito elegido; al abrirlo sale una caja de búsqueda y, debajo, los ámbitos en árbol, cada uno sangrado bajo el suyo. Se busca también por la ruta: escribir el nombre de un servicio encuentra lo que cuelga de él. La ruta entera sale al pasar el ratón por encima
- El filtro por ámbito de la portada y el perfil de usuario usan el mismo árbol

### Pendiente antes de desplegar

- Actualizar los dos snippets: el aplicativo y «EVT — Roles y perfiles»

## [0.1.2] — 2026-09-28

### Corregido

- **El ámbito del perfil de usuario se puede elegir otra vez.** Con el árbol de ámbitos completo, cada opción lleva su ruta entera y el desplegable se hacía inmanejable. Ahora se escribe para buscar, y las rutas largas se parten en varias líneas. Si la librería del buscador no llega, el campo sigue siendo el desplegable de siempre

### Pendiente antes de desplegar

- Actualizar también el snippet «EVT — Roles y perfiles», que es donde vive el campo: no va dentro del aplicativo

## [0.1.1] — 2026-09-28

### Añadido

- **La portada del sitio es la línea del tiempo de eventos, con su propia cabecera y su propio pie.** Se pinta como la página de un evento, sin nada del tema: el logo de quien publica arriba a la izquierda y el pie institucional abajo
- **Arriba a la derecha, «Acceder»** para iniciar sesión; con la sesión iniciada, el nombre de quien ha entrado, con «Gestión de eventos» —si puede gestionarlos— y «Salir». Sale también en la página de cada evento
- **Filtrar eventos en la portada** por texto (título, sede…) y por ámbito. Un ámbito incluye los que cuelgan de él: elegir un servicio trae los eventos de sus áreas. La línea abre en el mes con resultados más cercano, y el enlace filtrado se puede compartir

### Cambiado

- Al preparar el sitio, la línea del tiempo pasa a ser la página de inicio si no se había elegido otra

## [0.1.0] — 2026-09-12

### Añadido

- Arranque del repositorio: entorno de desarrollo (wp-env en los puertos 8798 y 8799), empaquetado del aplicativo en un Code Snippet y herramientas de calidad (PHPCS, PHPMD, PHPUnit)
- Modelo de datos de la fase 1: los tipos de contenido `evt_event` (jerárquico: el evento y sus páginas satélite), `evt_speaker` y `evt_activity`, con las taxonomías `evt_area`, `evt_type` y `evt_course`
- Rol `evt_organiser` (organización de eventos de su área), con el acotado por área en el perfil de cada persona. Coordinar todas las áreas es cosa del `administrator` de siempre, que es quien lleva `evt_edit_all_areas` y `evt_manage_app`: no se crea un rol intermedio para eso
- CSS y JavaScript a medida por evento y por página satélite, con **dos capacidades distintas** porque el riesgo no es el mismo: el **CSS** cambia cómo se ve una página y lo escribe el área de su evento (`evt_edit_custom_css`); el **JavaScript** ejecuta código en el navegador de cada visitante y se queda en administración (`evt_edit_custom_js`), que en multisitio necesita además `unfiltered_html`
- **Una pregunta de la inscripción puede pedir un archivo.** Al tipo de pregunta se le suma «Archivo», junto a las cuatro que ya había (casilla, una opción, varias opciones y texto corto), para pedir una autorización firmada, un justificante o un certificado sin tener que sacar a la persona del formulario. Una pregunta de archivo admite **un solo documento**, y no se configura nada más en ella: qué formatos y qué tamaño se admiten es del aplicativo entero y no de cada pregunta. Se aceptan PDF, JPG, PNG, DOCX y ODT, con un máximo de 10 MiB o el límite del servidor si es menor, y no se admite nada que un navegador pueda ejecutar. No basta con cambiarle la extensión a un fichero: se comprueba lo que hay dentro
- **Los documentos que aporta quien se inscribe se guardan de forma privada y no entran en la Biblioteca de medios.** No son adjuntos de WordPress: no salen en el selector de medios de ninguna pantalla, no tienen página propia ni dirección pública, y no se pueden reutilizar desde la biblioteca. Se guardan en una carpeta aparte con un nombre inventado que no dice ni quién los subió ni cómo se llamaba el fichero, y **solo se descargan desde el aplicativo**: la persona inscrita con el enlace de su inscripción, y quien pueda abrir ese evento desde la pestaña «Participantes», donde aparece el nombre del documento con su botón de descarga. Un evento marcado como histórico deja de editarse, pero sus documentos se siguen pudiendo consultar. Si al inscribirse falta un documento obligatorio o el que se envía no se admite, no se registra la inscripción; y al borrar definitivamente una inscripción se borran sus documentos, aunque mandarla a la papelera no (ADR-0036)
- **Los eventos conservan sus direcciones de hoy.** Con «Servir los eventos en la raíz del sitio» (Ajustes), un evento responde en `/<evento>/<sección>/`, como las páginas a las que sustituye; si en la raíz hay una página con la misma dirección, gana la página. Los enlaces `?page_id=<N>` llevan al evento, y cada ponente y cada actividad tienen su ficha en `<sección>/entry/<N>/`, que es la dirección que tienen hoy (ADR-0042)
- **La página pública pinta ponentes, programa, actividades y multimedia.** Cada sección pinta lo de su tipo: la de ponentes, la lista con foto, cargo y «Leer más»; la de programa, la parrilla por sede y día; la de actividades, cada una con su descripción y quién participa; la de multimedia, las actividades con vídeo. La portada enseña «Personas comunicadoras» con los ponentes destacados. Un ponente sin foto sale con una silueta
- En el panel lateral, el ponente gana «Destacar en la portada», y la actividad, «Vídeo» y «Otros participantes» (quien presenta, modera o inaugura sin ser ponente)
- Seis tipos de actividad más (Conferencia, Buenas prácticas, Experiencia, Encuentro, Actuación y Proyección audiovisual), cinco siluetas de separador más (ondas, nubes, montañas, gráfica y flecha) y las tipografías que usan hoy los eventos publicados
- **El cartel puede ser un PDF:** se ofrece para descargar y la imagen que se ve es la destacada del evento
- **El cartel, el logo y las fotos de ponentes no cambian:** siguen siendo imágenes normales de la Biblioteca de medios, con su dirección pública. Lo privado es lo que aporta quien se inscribe, no la biblioteca entera

- **La página pública de un evento se parece a la de siempre.** Cada sección pinta su cabecera con su propio color —de blanco arriba a su color abajo—, con su ilustración grande a la derecha del título y su entradilla debajo; el logo del evento se queda en la portada. La portada pone la presentación en dos tercios y el cartel en el otro, y las tarjetas de sección van a cuatro por fila con la imagen cuadrada y el título en el color de acento. El texto se lee en gris sobre blanco con los títulos casi en negro, y el menú va en mayúsculas pequeñas (ADR-0044)
- **Color de acento y fondo de cabecera.** En «Apariencia», el evento elige el color de los títulos de las tarjetas, del nombre en «Acerca de» y de las rayas, y una imagen detrás del color de la cabecera
- **El programa, en pestañas por día o en acordeón.** Se elige en «Apariencia» («Diseño del programa»). Cada actividad va en tres columnas —tipo, qué y cuándo— con el color de su familia de tipos. Sin JavaScript, los días salen uno debajo de otro
- **El logo de quien publica, arriba a la izquierda, y el color del pie**, desde la configuración (`evt_chrome`: `brand_logo`, `brand_alt`, `brand_url`, `footer_bg`). Sin configurar, no se pinta ningún logo
- **Los logos corporativos, abajo del todo en la portada.** Tienen su campo en «Apariencia» —cada logo con su enlace, tantos como hagan falta— y dejan de ir dentro del texto
- **El programa en PDF tiene su campo** y sale como botón «Descargar programa» en la página del programa, después del texto
- **«Editar esta página»** en la cabecera de cada sección para quien puede editarla, junto a «Gestionar este evento»
- **«Mis eventos» filtra por estado**: Activos, Borradores, Históricos y Todos, cada uno con su número. Por defecto, los activos: publicados y no históricos
- **La página de contacto tiene sus campos** —dirección (una o varias sedes), teléfono, correo y enlace al mapa— en su formulario, y los pinta en tres columnas, cada una con su icono en el color de la cabecera

### Cambiado

- **La sincronización de snippets deja de guardar lo que no cambió.** `scripts/lib/snippet-sync.php` volvía a llamar a `Code_Snippets\save_snippet()` en cada ejecución de `make sync-snippets`, aunque el snippet ya existiera idéntico; guardarlo reescribe la fila, le cambia `modified`, lo desactiva y reactiva, lo revalida —lo que puede llegar a ejecutar el código— e invalida la caché, sin que haya cambiado nada. Ahora compara un fingerprint SHA-256 del estado que gestiona el repositorio (código normalizado, descripción, ámbito, prioridad y etiquetas) y solo guarda cuando ese fingerprint cambia, partiendo de un **clon** del `Snippet` existente para conservar lo que no gestiona (`active`, `locked`, `condition_id`, `revision`, `cloud_id`...) sin modificar la instancia que el plugin tiene cacheada y que él mismo relee como estado anterior; un snippet idéntico pero inactivo se reactiva sin reescribirse. Tras guardar se relee la fila persistida: si quedó activa no se vuelve a activar —activar una ya activa es un `UPDATE` de cero filas que Code Snippets da por fallido, y salía un aviso que no correspondía a ningún problema—, y si quedó inactiva se intenta recuperar. La autoridad es esa relectura, no el objeto que devuelve el guardado, que sale de la caché del plugin antes de limpiarla. Un snippet **bloqueado** en Code Snippets cuyo código difiere del repositorio da error explícito en vez de un «actualizado» falso: el plugin restauraría el código guardado, así que no se toca nada y se avisa para que lo desbloquee quien corresponda; si solo difiere su metadatos, se actualiza con normalidad y sigue bloqueado. La versión del aplicativo EVT no interviene en esta decisión, y las librerías de terceros siguen con su versión exacta y su SRI, sin tocar (ADR-0035)
- **La publicación remota queda fijada en `@erseco/code-snippets-client` 0.1.7** (antes 0.1.6, versión exacta en `package.json`). Esa versión protege las actualizaciones sobre snippets bloqueados —comprueba `code` y `name` antes de escribir, verifica después de escribir, y solo envía `locked` cuando quien llama lo pide, para no borrar un candado puesto por otra persona— y hace condicional la recuperación de la activación. El reparto no cambia: `make sync-snippets` sincroniza el wp-env local con la librería PHP del repositorio y `npm run snippets` publica en el sitio de destino con el cliente, cuya lógica REST no se duplica aquí
- **Se retira el rol `evt_coordinator`.** Se distinguía de `evt_organiser` en una sola capacidad, `evt_edit_all_areas`, y un rol que solo se diferencia en saltarse el ámbito no nombra una función sino la ausencia de ámbito, que en WordPress ya se llama `administrator`. Quien necesite llegar a todas las áreas sin administrar el subsitio recibe `evt_edit_all_areas` desde WPFront, sin rol nuevo. El rol viejo **no se queda**: `evt_retire_coordinator_role()` corre una sola vez, pasa a `evt_organiser` a quien lo tuviera —para que nadie se quede sin rol— y borra el rol, dejando puesta la opción de guarda `evt_coordinator_role_retired` para no repetirse ni deshacer lo que se conceda después a mano. Al actualizar, quien tuviera el rol pierde `evt_edit_all_areas` y no edita nada hasta que se le rellene su área en el perfil: el acotado falla en cerrado
- **El CSS a medida pasa de administración al área.** Estaba reservado junto al JavaScript y no le correspondía: un CSS mal escrito deja la página fea y se deshace recargando; un JavaScript mal copiado se lleva la sesión de quien visita. En la pantalla, el CSS deja el recuadro amarillo de «Solo administración» y pasa a ser un campo normal de la pestaña «Código», que ahora ve también el área; el recuadro amarillo se queda **solo** para el JavaScript

- **Las inscripciones se corrigen y se borran desde «Participantes».** El lápiz abre el panel lateral con los datos de la persona, sus respuestas y su taller, con el mismo aforo que cuando lo elige ella; el consentimiento y los documentos no se tocan. La papelera borra la inscripción **del todo**, con sus documentos, y para que no se haga por error pide escribir el correo de la persona, que el diálogo enseña. Si tenía taller, su plaza queda libre (ADR-0043)

### Corregido

- **En la demostración no se podían corregir ni borrar participantes.** Las personas de «Participantes» eran filas inventadas por el entorno de desarrollo, de solo lectura. Ahora `scripts/seed-demo.php` las crea como inscripciones de verdad, con su taller y respetando el aforo, así que salen el lápiz y la papelera en el wp-env y en Playground
- **La silueta de un ponente sin foto casi no se veía.** Ahora es el avatar de siempre: figura clara sobre fondo gris
- **En desarrollo y en Playground, la página del evento no enseñaba ningún logo arriba a la izquierda.** El entorno de desarrollo pone uno de ejemplo, sin marca de nadie, para ver dónde va el de verdad

- **Con banner, la portada no enseñaba «Gestionar este evento».** El banner sustituye la cabecera y se llevaba el enlace; ahora va debajo
- **Los colores propios de una sección no salían.** El formulario de la página los guardaba, pero la página pública solo leía los del evento
- **El programa salía dos veces** en los eventos que lo escribieron a mano: su CSS a medida escondía la parrilla por la clase `programa-estandar`, que la parrilla nueva no llevaba. Y las actividades sin fecha ya no salen en un bloque «Sin fecha»
- **El texto de la cabecera de una sección se comprobaba contra el color equivocado.** Va sobre la parte blanca del degradado, así que el contraste se mide contra el blanco

- **Cerrar la edición de una página pulsando fuera del panel llevaba a `…/evento/undefined`.** El fondo oscuro no tiene dirección y el guion la tomaba de él; ahora vuelve siempre a donde apunta el botón de cerrar del panel

- **En «Mis eventos» y en la línea del tiempo, un evento con el cartel en PDF salía sin imagen.** Ahora sale su imagen destacada. Y el nombre del evento que no tiene ninguna imagen se escribe en un color que se lee sobre el de su cabecera, también cuando es claro
- **El filtro de ámbitos de «Mis eventos» es una lista única y en árbol:** cada ámbito sale una vez, con sus subámbitos sangrados debajo, y elegir uno trae también lo de sus subámbitos. Administración ve todos los ámbitos del sitio; el resto, los de su ámbito en los que tiene eventos
- La paginación de «Mis eventos» sale centrada bajo la cuadrícula
- **«Datos del evento» publica y despublica el evento**, con el mismo interruptor que el listado, en su propia tarjeta «Publicación» encima de los datos: no hace falta volver a «Mis eventos». Publicar el evento no toca sus páginas
- **En cuadrícula, cada tarjeta lleva su pie:** el interruptor de publicado/borrador y los botones de ver (ojo) y editar (lápiz), como en la lista
- **Un evento histórico no se publica ni se despublica:** se queda como estaba. El interruptor sale apagado en el listado, en las tarjetas y en «Datos», y el servidor rechaza el cambio aunque llegue a mano, también para administración

- **Las tipografías elegidas en «Apariencia» no se cargaban:** se guardaban, pero ningún fichero las traía, y solo se veían en el ordenador que las tuviera instaladas. Ahora se cargan desde jsDelivr, con SRI y la versión clavada, como el resto de librerías

- **La URL pública de un evento respondía «Página no encontrada» tras provisionar de cero.** Las reglas de enlaces permanentes se guardan al instalar WordPress, cuando el aplicativo todavía no existe, y nada las refrescaba: el botón «Ver la página» del taller llevaba a un 404 hasta que alguien entrara en Ajustes → Enlaces permanentes. `scripts/setup-pages.php` las regenera al terminar
- La cuenta de demostración `coordinacion` había desaparecido de `scripts/seed-demo.php` al retirar el rol, y el README la seguía prometiendo. Vuelve como una organización más, la única que pertenece a **dos** áreas, que es el caso que enseña el filtro por área del listado
- `wp.codeEditor.initialize()` se llamaba con el documento aún cargando y WordPress avisaba por consola de que «ran too early», dos veces por pantalla. La primera pasada solo corre si el documento ya está leído; las de `DOMContentLoaded` y `load` no cambian
- Las iniciales del avatar de la cabecera salían del nombre partido por espacios, así que «Organización (Innovación)» se leía «O(». Ahora se parte por lo que no es letra ni número
- Tres mensajes en pantalla seguían mandando a «la coordinación de eventos», un rol que ya no existe: el aviso de evento de otra área del listado y las dos ayudas del panel «Datos». Ahora dicen «quien administra el aplicativo», como el resto
- «Añadir ponente» y «Añadir actividad» respondían «Lo siento, no tienes permisos para acceder a esta página» a todo el que no fuera administración, aunque tuviera las capacidades: los dos tipos cuelgan del menú de Eventos y WordPress solo les registra el listado en el submenú. Ahora el alta está en el menú y un área da de alta sus ponentes y sus actividades
