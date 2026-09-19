# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/)
y versionado [SemVer](https://semver.org/lang/es/).

La versión de este fichero es la **única** fuente de verdad: `make bundle` la
lee de la cabecera superior y la escribe en el `@version` del bundle.
`make release` crea el tag y la release en GitHub; el despliegue de los snippets
en el subsitio `eventos` se hace después.

## [0.1.0] — 2026-09-12

### Añadido

- Arranque del repositorio: entorno de desarrollo (wp-env en los puertos 8798 y 8799), empaquetado del aplicativo en un Code Snippet y herramientas de calidad (PHPCS, PHPMD, PHPUnit)
- Modelo de datos de la fase 1: los tipos de contenido `evt_event` (jerárquico: el evento y sus páginas satélite), `evt_speaker` y `evt_activity`, con las taxonomías `evt_area`, `evt_type` y `evt_course`
- Rol `evt_organiser` (organización de eventos de su área), con el acotado por área en el perfil de cada persona. Coordinar todas las áreas es cosa del `administrator` de siempre, que es quien lleva `evt_edit_all_areas` y `evt_manage_app`: no se crea un rol intermedio para eso
- CSS y JavaScript a medida por evento y por página satélite, con **dos capacidades distintas** porque el riesgo no es el mismo: el **CSS** cambia cómo se ve una página y lo escribe el área de su evento (`evt_edit_custom_css`); el **JavaScript** ejecuta código en el navegador de cada visitante y se queda en administración (`evt_edit_custom_js`), que en multisitio necesita además `unfiltered_html`

### Cambiado

- **La sincronización de snippets deja de guardar lo que no cambió.** `scripts/lib/snippet-sync.php` volvía a llamar a `Code_Snippets\save_snippet()` en cada ejecución de `make sync-snippets`, aunque el snippet ya existiera idéntico; guardar un snippet activo lo reejecuta, lo revalida y le cambia `modified` sin que haya cambiado nada. Ahora compara un fingerprint SHA-256 del estado que gestiona el repositorio (código normalizado, descripción, ámbito, prioridad y etiquetas) y solo guarda cuando ese fingerprint cambia; un snippet idéntico pero inactivo se reactiva sin reescribirse. La versión del aplicativo EVT no interviene en esta decisión, y las librerías de terceros siguen con su versión exacta y su SRI, sin tocar (ADR-0035)
- **Se retira el rol `evt_coordinator`.** Se distinguía de `evt_organiser` en una sola capacidad, `evt_edit_all_areas`, y un rol que solo se diferencia en saltarse el ámbito no nombra una función sino la ausencia de ámbito, que en WordPress ya se llama `administrator`. Quien necesite llegar a todas las áreas sin administrar el subsitio recibe `evt_edit_all_areas` desde WPFront, sin rol nuevo. El rol viejo **no se queda**: `evt_retire_coordinator_role()` corre una sola vez, pasa a `evt_organiser` a quien lo tuviera —para que nadie se quede sin rol— y borra el rol, dejando puesta la opción de guarda `evt_coordinator_role_retired` para no repetirse ni deshacer lo que se conceda después a mano. Al actualizar, quien tuviera el rol pierde `evt_edit_all_areas` y no edita nada hasta que se le rellene su área en el perfil: el acotado falla en cerrado
- **El CSS a medida pasa de administración al área.** Estaba reservado junto al JavaScript y no le correspondía: un CSS mal escrito deja la página fea y se deshace recargando; un JavaScript mal copiado se lleva la sesión de quien visita. En la pantalla, el CSS deja el recuadro amarillo de «Solo administración» y pasa a ser un campo normal de la pestaña «Código», que ahora ve también el área; el recuadro amarillo se queda **solo** para el JavaScript

### Corregido

- **La URL pública de un evento respondía «Página no encontrada» tras provisionar de cero.** Las reglas de enlaces permanentes se guardan al instalar WordPress, cuando el aplicativo todavía no existe, y nada las refrescaba: el botón «Ver la página» del taller llevaba a un 404 hasta que alguien entrara en Ajustes → Enlaces permanentes. `scripts/setup-pages.php` las regenera al terminar
- La cuenta de demostración `coordinacion` había desaparecido de `scripts/seed-demo.php` al retirar el rol, y el README la seguía prometiendo. Vuelve como una organización más, la única que pertenece a **dos** áreas, que es el caso que enseña el filtro por área del listado
- `wp.codeEditor.initialize()` se llamaba con el documento aún cargando y WordPress avisaba por consola de que «ran too early», dos veces por pantalla. La primera pasada solo corre si el documento ya está leído; las de `DOMContentLoaded` y `load` no cambian
- Las iniciales del avatar de la cabecera salían del nombre partido por espacios, así que «Organización (Innovación)» se leía «O(». Ahora se parte por lo que no es letra ni número
- Tres mensajes en pantalla seguían mandando a «la coordinación de eventos», un rol que ya no existe: el aviso de evento de otra área del listado y las dos ayudas del panel «Datos». Ahora dicen «quien administra el aplicativo», como el resto
- «Añadir ponente» y «Añadir actividad» respondían «Lo siento, no tienes permisos para acceder a esta página» a todo el que no fuera administración, aunque tuviera las capacidades: los dos tipos cuelgan del menú de Eventos y WordPress solo les registra el listado en el submenú. Ahora el alta está en el menú y un área da de alta sus ponentes y sus actividades
