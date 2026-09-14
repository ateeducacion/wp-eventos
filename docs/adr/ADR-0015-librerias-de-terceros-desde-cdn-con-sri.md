---
id: ADR-0015
title: "Las librerías de terceros se cargan desde jsDelivr con SRI; en desarrollo, desde node_modules"
status: Aceptada
date: 2026-09-13
related:
  issues: []
  prs: []
  sdds: [SDD-0001, SDD-0002]
  adrs: [ADR-0001, ADR-0002, ADR-0011, ADR-0013]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0015: Las librerías de terceros se cargan desde jsDelivr con SRI; en desarrollo, desde node_modules

## Estado

Aceptada (2026-09-13).

## Contexto

Este repositorio **no es un plugin**
([ADR-0001](ADR-0001-repo-entorno-desarrollo-no-plugin.md)). Lo que se despliega
es un snippet de Code Snippets: un único fichero PHP que `build/pack-snippet.php`
genera concatenando `src/Evt/` y `assets/`, y que hoy pesa 375 KB. En producción
**no hay directorio en disco desde el que servir un `.css` o un `.js`**, no hay
`plugins_url()` y no hay forma de encolar un fichero propio. El CSS y el
JavaScript del aplicativo viajan en línea dentro del bundle
(`src/Evt/PublicFront/Assets.php`, `Assets::set_inline()`).

Una librería de terceros, por tanto, solo tiene dos caminos: meterla dentro del
bundle o pedirla a una URL. Y la disyuntiva se resuelve sola en cuanto se mira
el tamaño: inlinear una librería de gráficas son **200 KB de código ajeno**
dentro de un snippet que ya pesa casi un mega y que revisa una persona. **El
CDN con SRI es el camino.** Así se cargan Bootstrap 5 y sus iconos: versión
clavada en la URL, `integrity` y
`crossorigin`.

El precio de esa decisión se pagó en la CI, y también está escrito. La ADR-0037 de aquel
repositorio (`…/docs/adr/ADR-0037-en-desarrollo-los-vendor-se-sirven-de-node-modules.md`)
cuenta tres fallos seguidos que apuntaban a tres sitios
distintos y resultaron ser el mismo parpadeo de DNS del runner pidiendo
Bootstrap a jsDelivr; el tercero costó una tarde porque la aserción medía
geometría y la captura se veía bien —lo estilado era CSS propio, servido en
local—. De ahí la segunda mitad de la política: **en desarrollo y en los tests,
las mismas versiones se sirven de `node_modules`**, con un mu-plugin que
reescribe `script_loader_src` y `style_loader_src`.

### El caso concreto que obliga a escribir esto ahora

El aplicativo se dibuja con Bootstrap 5 y pregunta si está
(`Assets::has_bootstrap()`, `src/Evt/PublicFront/Assets.php`) para
ponerle al `body` la clase `evt-sin-bootstrap` cuando no lo está y pintar su
propio aspecto. En el subsitio real, lo que hay es otra cosa: **un fragmento de
código pegado a mano, activo y de ámbito global**, que viene a decir esto —está
inventariado en el material de investigación de `.local/`—:

```php
wp_enqueue_style('bootstrap-css', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css');
wp_enqueue_script('bootstrap-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js', array('jquery'), null, true);
```

Carga **Bootstrap 4.5.2**, no la 5, y la carga sin `integrity`, sin `$ver` y
desde un CDN que la documentación de este repositorio daba por retirado; comprobado el 2026-09-13, la URL responde **HTTP 200**, así que carga
de verdad. Y registra el handle `bootstrap-css`, que es justo el primero que
busca `has_bootstrap()`: en producción devolvería `true`, el `body` perdería
`evt-sin-bootstrap` y el aplicativo se dibujaría dando por hecho Bootstrap 5
sobre una página que tiene Bootstrap 4. En local se ve perfecto; en producción,
no.

El subsitio carga la 4.5.2 y el aplicativo necesita la 5.3.3. Las dos no pueden
convivir en la misma página, y no se puede cambiar el aspecto del resto del
subsitio para acomodar nuestras pantallas.

## Problema

¿De dónde salen las librerías de terceros del aplicativo —en producción, en
desarrollo y en la CI—, y cómo se ancla su versión para que las tres estén
mirando el mismo fichero?

## Factores de decisión

- **No hay dónde servirlas** ([ADR-0001](ADR-0001-repo-entorno-desarrollo-no-plugin.md)):
  el artefacto de producción es un snippet, no un directorio.
- **El bundle no debe engordar con código ajeno.** 375 KB ya es mucho para un
  cuadro de texto que revisa una persona, y una librería inlineada no tiene
  quien avise de una vulnerabilidad.
- **La CI no puede depender del DNS.** Un fallo de red disfrazado de fallo de
  maqueta cuesta una tarde y no falla nada del aplicativo
  ([ADR-0011](ADR-0011-ci-y-politica-de-pruebas.md)).
- **Integridad comprobable.** Si el navegador va a ejecutar código de un
  tercero, que compruebe el hash: `integrity` y `crossorigin`, sin excepción.
- **Una sola versión, en todos los sitios.** Si `package.json`, la URL y el
  `$ver` del encolado no coinciden, o el SRI no cuadra o el reescritor de
  desarrollo no encuentra el fichero.
- **La pantalla tiene que seguir siendo utilizable si el CDN no contesta.** No
  es negociable: una tabla no puede desaparecer porque falte una hoja de
  estilos.
- **Una sola política.** Tener dos formas de cargar una librería obliga a
  alguien a decidir cuál vale cada vez, y esa decisión se toma mal.

## Alternativas consideradas

### Opción 1: jsDelivr con SRI en producción y `node_modules` en desarrollo — ELEGIDA

- Pros: cero bytes en el bundle; la integridad la comprueba el navegador; la
  versión queda escrita en la URL, así que subirla es un diff legible; en
  desarrollo y en la CI no se sale a la red, de modo que se puede trabajar sin
  conexión y las comprobaciones dejan de caerse por algo que no es del
  aplicativo.
- Contras: la misma versión hay que mantenerla en tres sitios; el navegador de
  quien visita pide un recurso a un tercero, y eso hay que declararlo; en
  producción sigue habiendo una dependencia de red en tiempo de ejecución, que
  se cubre con la degradación y no con un deseo.

### Opción 2: inlinear la librería en el bundle

Pegar Bootstrap dentro de `assets/` para que el empaquetador la meta en el
snippet.

- Pros: sin dependencia de red; un solo artefacto.
- Contras: **200 KB de código ajeno inlineados en un snippet que ya pesa casi
  un mega**, en un fichero
  que se pega en un cuadro de texto de Code Snippets y que revisa una persona.
  Bootstrap 5.3.3 minificado son ~230 KB de CSS más ~80 KB de JS: el bundle
  pasaría de 375 KB a más del doble. Actualizarlo es copiarlo a mano y no hay
  nada que avise de un parche de seguridad. Descartada.

### Opción 3: servir las librerías desde `uploads` del sitio

Subir los ficheros a la mediateca o a un directorio de `wp-content/uploads` y
encolarlos desde ahí.

- Pros: mismo origen, sin terceros, sin SRI que mantener.
- Contras: el fichero deja de estar en el repositorio y pasa a estar en la base
  de datos y en el disco del hosting: ni se versiona, ni se revisa, ni se
  despliega con `make sync-snippets`
  ([ADR-0002](ADR-0002-sincronizacion-snippets-eval-file.md)). Actualizar la
  librería sería subir un fichero a mano en producción, que es exactamente el
  tipo de cambio invisible que este repositorio existe para evitar
  ([ADR-0012](ADR-0012-politica-de-edicion-y-auditoria.md)). Y `uploads` está
  fuera de nuestro control en la red donde se despliega. Descartada.

### Opción 4: no usar librerías de terceros

Escribir a mano lo que haga falta: la ordenación y el filtro de una tabla caben
en un centenar de líneas sin traerse DataTables, y no sería la primera vez.

- Pros: cero dependencias, cero peticiones, cero versiones.
- Contras: **es la opción por defecto y se sigue prefiriendo cuando la pieza es
  pequeña**; esta ADR no la descarta, la acota. Para un ordenador de tablas o un
  desplegable, escribirlo sale más barato que traerse una librería. Para un
  sistema de rejilla y componentes completo —que es lo que el aplicativo usa de
  Bootstrap 5— reimplementarlo no es una opción seria. La regla es: **primero
  mirar si no hace falta; si hace falta, esta ADR dice de dónde sale**.

## Decisión

### 1. En producción: jsDelivr, versión exacta, `integrity` y `crossorigin`

Las librerías de terceros se encolan desde `https://cdn.jsdelivr.net/npm/…` con
la versión **clavada en la URL**, el atributo `integrity` con el hash real y
`crossorigin="anonymous"`. Los hashes se calculan, se anotan en un comentario
junto al comando que los genera y no se copian de ninguna parte.

El caso que estrena la política es `snippets/bootstrap5.php`: un snippet suelto
—no entra en el bundle— que carga **Bootstrap 5.3.3 y bootstrap-icons 1.11.3**
solo en las pantallas del aplicativo y en las páginas de evento, retirando antes
los handles que deja ese fragmento (`bootstrap-css`, `bootstrap-js`). El resto del
subsitio se queda con su Bootstrap 4 y a nadie se le cambia el aspecto.

### 2. En desarrollo y en los tests: `node_modules`

Las mismas versiones se instalan como `devDependencies` con `--save-exact`, y el
mu-plugin `scripts/mu-plugins/evt-dev-tools.php` reescribe en
`script_loader_src` y `style_loader_src` cualquier URL
`cdn.jsdelivr.net/npm/<paquete>@<versión>/<fichero>` a la copia local. **Tres
condiciones**, y si alguna falla se deja la URL del CDN:

1. el paquete está instalado;
2. su versión es **exactamente** la que pide la URL —otra versión probaría algo
   distinto de lo que se despliega—;
3. el fichero existe. jsDelivr minifica al vuelo, así que hay `.min` que el
   paquete no trae: entonces vale el original.

El mu-plugin **no se despliega nunca**. Producción no cambia: mismas URL, mismas
versiones, mismo SRI.

### 3. Las librerías van en `package.json`, con la versión exacta

No como capricho de herramienta: es lo que hace posible el punto 2 y lo que
garantiza que se prueba contra el mismo fichero que se despliega.

**La versión tiene que coincidir en los tres sitios**: `package.json`, la URL del
CDN y el `$ver` del encolado. Si no coinciden, o el SRI no cuadra o el
reescritor de desarrollo no encuentra el fichero y la prueba vuelve a salir a la
red sin que nadie se entere.

### 4. La degradación no es opcional

**Si el CDN no contesta, la pantalla tiene que seguir siendo utilizable.** No
«casi»: los datos siguen leyéndose, los formularios siguen enviándose y la
navegación sigue funcionando. La regla es que **el dato no dependa de la
librería**: si hay una gráfica, debajo va su tabla y arriba las cifras.

En el aplicativo eso significa que la hoja propia (`assets/css/evt-app.css`)
pinta el aspecto base y la clase `evt-sin-bootstrap` del `body`
(`Assets::body_class()`) es el interruptor que lo activa. Bootstrap **mejora** la
pantalla; no la sostiene.

### 5. `has_bootstrap()` no adivina la versión

Que un handle se llame `bootstrap-css` no dice qué versión trae —el fragmento
del subsitio es la prueba—. La verdad la pone quien la conoce: nuestro propio snippet. La
comprobación tiene que responder «¿hay **Bootstrap 5**?», no «¿hay algo llamado
bootstrap?».

## Consecuencias

### Positivas

- **El bundle no crece.** El aplicativo sigue siendo un fichero y no contiene ni
  una línea de código de terceros.
- **La integridad la comprueba el navegador.** Un fichero manipulado en el CDN no
  se ejecuta; el fragmento de hoy, sin SRI, no tiene esa garantía.
- **La CI deja de depender del DNS**, y se puede trabajar sin conexión.
- **Subir de versión es un diff.** Tres líneas en tres ficheros, revisables, con
  su historia; no un fichero subido a mano a un servidor.
- **El aplicativo deja de dibujarse sobre suposiciones.** Sabe qué Bootstrap
  tiene porque lo carga él, y solo donde le toca.
- **Una política y no dos.** Escrita en un sitio, así que no hay que decidirla
  otra vez en cada librería que entre.

### Negativas

- **Una versión en tres sitios.** Es una fuente real de desincronización; se
  convierte en un fallo con nombre solo si hay una comprobación que lo mire, y
  hay que escribirla.
- **Hay una petición a un tercero en producción.** Se acepta, con SRI y con
  degradación, pero es una superficie que antes no se declaraba y ahora sí.
- **`npm install` pasa a ser un paso previo a los tests**, en el Makefile y en la
  CI. Unos megas más de instalación.
- **Un snippet suelto más que mantener y sincronizar** (`snippets/bootstrap5.php`),
  con su propia prioridad y su propio ámbito.
- **Convivencia incómoda mientras ese fragmento siga activo.** Quitarle los
  handles a otro snippet es frágil por definición: si alguien renombra los suyos,
  dejamos de retirarlos. Lo correcto a medio plazo es retirarlo
  (P-13 del [plan](../plan/PLAN-0001-implantacion-por-fases.md)), no convivir con
  él.

### Neutras

- **Esta ADR corrige un argumento de la
  [ADR-0013](ADR-0013-el-editor-de-codigo-es-el-de-wordpress.md)**, que descartaba
  una librería externa por «pedirla a un CDN desde un sitio institucional detrás
  de un proxy». Ese motivo no era cierto y no es la política del proyecto. La
  decisión de la ADR-0013 no cambia —el editor del núcleo sigue siendo el
  correcto—; cambia el porqué, que es lo que su opción 2 dice hoy: el CDN no es
  lo que descarta a CodeJar, la falta de comprobadores sí.
- **RNF-REN-05 de [REQ-0001](../requisitos/REQ-0001-aplicativo-de-eventos.md)**
  («ninguna pantalla del aplicativo pide un recurso a un CDN externo») se escribió
  contra ese fragmento y su CDN sin SRI ni versión. Con esta ADR el requisito
  queda mal formulado: lo que hay que exigir no es «cero CDN», sino «ningún
  recurso externo sin versión anclada, sin SRI y sin degradación». Queda
  pendiente reescribirlo.
- La regla no obliga a usar librerías. **Primero se mira si no hace falta**
  (opción 4); esta ADR solo dice de dónde salen cuando hacen falta.
- El bundle sigue sin contener ninguna URL externa: quien la pone es un snippet
  suelto, no el aplicativo.
