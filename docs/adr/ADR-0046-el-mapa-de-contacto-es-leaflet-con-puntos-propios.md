---
id: ADR-0046
title: "El mapa de la página de contacto es Leaflet con puntos propios, y debajo la misma lista"
status: Propuesta
date: 2026-09-28
related:
  issues: []
  prs: []
  sdds: []
  adrs: [ADR-0015, ADR-0030, ADR-0044]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0046: El mapa de la página de contacto es Leaflet con puntos propios, y debajo la misma lista

## Estado

Propuesta

## Contexto

La página de contacto de un evento lleva dirección, teléfono y correo en tres
columnas, y un único campo «Enlace al mapa» que se pinta como «Ver en el mapa»
bajo la dirección (`src/Evt/PublicFront/Block/ProgrammeBlock.php`, método
`contact()`; la clave es `evt_contact_map` en
`src/Evt/Meta/EventMetaKeys.php`). No hay mapa en la página.

En la reunión de demostración del 2026-09-28 se dijo que «lo del mapa habrá que
hacerlo bien, eso está sin hacer». Después se precisó lo que se quiere: **poder
poner coordenadas, varias, y para cada punto un texto y un enlace**. Un evento
tiene a menudo más de un sitio que enseñar: la sede, el aparcamiento, la parada
de guagua, una segunda sede.

Las restricciones son las de siempre:

- **No hay dónde servir ficheros** en producción: el aplicativo es un snippet de
  Code Snippets ([ADR-0015](ADR-0015-librerias-de-terceros-desde-cdn-con-sri.md)).
  Una librería de terceros llega de jsDelivr con SRI, o no llega.
- **Nada de ninguna organización** en lo que se versiona
  ([ADR-0030](ADR-0030-el-repositorio-se-publica-sin-nada-de-nadie.md)): ni una
  clave de API, ni el servidor de teselas de quien despliegue.
- **La página pública tiene que seguir funcionando sin guion**, como el
  programa en pestañas, que sin guion sale en días uno debajo de otro
  (`assets/js/evt-evento.js`).

## Problema

¿Cómo se pinta en la página de contacto un mapa con varios puntos, cada uno con
su texto y su enlace, sin servir ficheros, sin claves y sin que la página
dependa de que el mapa cargue?

## Factores de decisión

- **Sin clave de API ni cuenta**: una clave es un secreto de quien despliega y
  no puede ir en el repositorio.
- **Varios puntos** con texto y enlace, escritos por quien organiza, no por
  quien administra.
- **Coste en la página**: solo la página de contacto con puntos debe pagar el
  mapa. Las demás no piden nada a nadie.
- **Degradación**: sin guion, o sin CDN, la información tiene que seguir ahí.
- **Privacidad de quien visita**: un mapa en línea pide teselas a un tercero, y
  ese tercero ve la IP de quien mira.
- **Una sola política de librerías** (ADR-0015): versión clavada en la URL, en
  el encolado y en `package.json`, con SRI.

## Alternativas consideradas

### Opción 1: incrustar Google Maps o el `<iframe>` de OpenStreetMap

- Pros: nada que programar; el `<iframe>` de OpenStreetMap no pide clave.
- Contras: el `<iframe>` de OpenStreetMap enseña **un** marcador, sin texto ni
  enlace; los varios puntos que se piden no caben. Google Maps con varios
  marcadores pide la API de JavaScript con **clave**, que es un secreto de
  quien despliega y además tiene coste por carga. Los dos meten una página
  ajena entera, con sus propias peticiones y, en el caso de Google, sus
  cookies, en la página del evento.

### Opción 2: Leaflet desde jsDelivr con SRI, teselas de OpenStreetMap cambiables por filtro, y la lista de puntos debajo — ELEGIDA

- Pros:
  - **Sin clave:** es la librería de mapas libre más usada (licencia BSD-2).
    Pinta varios marcadores con su globo, y el texto se escribe con nodos, no
    con HTML, así que lo que escribe quien organiza no se interpreta.
  - **Coste acotado:** 147 552 bytes de guion (42 661 comprimido) y 14 806 de
    hoja, medidos sobre `node_modules/leaflet/dist/` 1.9.4. Solo se cargan en
    la página de contacto que tiene puntos.
  - **Degradación completa:** debajo del mapa van los mismos puntos como
    lista, cada uno enlazado a su sitio en openstreetmap.org. Sin guion o sin
    CDN, la dirección se sigue encontrando.
  - **Teselas cambiables:** el servidor de teselas se cambia con el filtro
    `evt_map_tiles` sin tocar el código, así que quien tenga el suyo lo usa.
- Contras:
  - **Una dependencia de red más en tiempo de ejecución:** Leaflet de jsDelivr
    y las teselas de OpenStreetMap. Se cubre con la lista de debajo, no con un
    deseo.
  - **Privacidad:** las teselas por defecto las sirve la Fundación
    OpenStreetMap, y quien mire el mapa le manda su IP. No pone cookies, pero
    es una petición a un tercero que hay que declarar.
  - **Uso limitado:** la política de uso de las teselas de OpenStreetMap
    (<https://operations.osmfoundation.org/policies/tiles/>) exige atribución
    visible y prohíbe el uso intensivo, y no ofrece ninguna garantía de
    servicio. Una página de contacto de un evento es uso ligero, pero si el
    tráfico creciera hay que pasar a otro servidor de teselas; para eso está
    el filtro.
  - **Tres sitios con la misma versión:** `package.json`, las URL de
    `ContactMap::VENDOR` y la constante `ContactMap::VERSION`. Un test
    comprueba que coinciden.

### Opción 3: una imagen estática del mapa

- Pros: cero guion.
- Contras: sin clave solo hay servicios de terceros de disponibilidad incierta,
  o generar la imagen en el servidor, que no tiene dónde guardarla. Tampoco se
  puede acercar ni pulsar un punto. No aporta nada sobre la lista con enlaces
  que ya da la opción 2.

## Decisión

Haremos la opción 2:

- **Qué se guarda.** Los puntos van en la meta `evt_contact_points` de la
  página de contacto: una lista JSON de `{lat, lng, text, url}`, como mucho
  veinte.
  - Se limpia en el `sanitize_callback` de la meta, venga de donde venga:
    fuera de rango o `0, 0` no se guarda, el texto es texto plano y el enlace
    solo puede ser http(s).
  - Las coordenadas se escriben en **un solo campo**, tal y como las copia
    cualquier mapa de internet al pulsar sobre un sitio: `28.4636, -16.2518`.
  - Un punto con coordenadas que no se entienden **no se tira en silencio**:
    el formulario vuelve con lo escrito y dice cuál es.
- **Qué se pinta.**
  - `PublicFront/ContactMap` pinta la caja del mapa con sus datos en
    `data-evt-mapa` y, debajo, la lista de puntos enlazados a
    openstreetmap.org.
  - Leaflet 1.9.4 se encola desde jsDelivr **solo** en la página de contacto
    que tiene puntos, con `integrity` y `crossorigin` mientras la URL es la
    del CDN. En desarrollo el mu-plugin la sirve de `node_modules`, como a
    Bootstrap.
  - `assets/js/evt-mapa.js` crea el mapa con la rueda del ratón apagada, para
    no secuestrar el desplazamiento de la página.
- **Teselas.** Por defecto las de OpenStreetMap, con su atribución. Se cambian
  con el filtro `evt_map_tiles`, que solo acepta plantillas HTTPS.
- **El enlace de antes se queda.** El campo «Enlace al mapa» se mantiene como
  estaba: los eventos migrados lo traen, y no estorba.

## Consecuencias

### Positivas

- Varios puntos con su texto y su enlace, escritos por quien organiza, sin
  clave ni cuenta en ningún servicio.
- La página de contacto sin puntos, y todas las demás páginas, siguen sin
  cargar nada nuevo.
- Sin guion o sin CDN, la lista de puntos da la misma información que el mapa.

### Negativas

- La página de contacto con mapa pide recursos a dos terceros, jsDelivr y el
  servidor de teselas. El segundo recibe la IP de quien visita. Si quien
  despliega tiene un aviso de privacidad, tiene que nombrarlo.
- Las teselas por defecto no tienen garantía de servicio. Un corte de
  OpenStreetMap deja el mapa gris, aunque la lista de debajo sigue.
- Una versión más que mantener a mano en tres sitios.

### Neutras

- El campo «Enlace al mapa» convive con los puntos. Si con el tiempo nadie lo
  usa, se retira con una adenda.
