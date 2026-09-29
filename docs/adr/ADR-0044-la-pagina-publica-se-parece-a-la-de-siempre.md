---
id: ADR-0044
title: "La página pública de un evento se parece a la de siempre: cabecera por sección, acento y programa en pestañas"
status: Propuesta
date: 2026-09-27
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0015, ADR-0030, ADR-0041, ADR-0042]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0044: La página pública de un evento se parece a la de siempre: cabecera por sección, acento y programa en pestañas

## Estado

Propuesta

## Contexto

Con los eventos ya migrados en su misma dirección (ADR-0042), se compararon
lado a lado, sección a sección, las diez páginas de evento más recientes del
sistema anterior con las que pinta el aplicativo. Las diferencias que se ven
no son de contenido sino de marco:

- **La cabecera de cada sección tiene su propio color.** En la vista del
  sistema anterior cada página satélite lleva un degradado de blanco a su color
  y su color de texto; la portada, un color liso. El formulario de página del
  aplicativo ya guardaba esos colores por página (`PageForm::LOOK_KEYS`,
  `src/Evt/PublicFront/PageForm.php`), pero la vista pública
  (`EventView::appearance()`) solo leía los del evento: se guardaban y no
  salían.
- **A la derecha del título de cada sección hay una ilustración grande**, la
  misma de su tarjeta en la portada, y bajo el título una entradilla en
  cursiva.
- **Hay un color de acento** distinto del de la cabecera: el del nombre del
  evento en «Acerca de», el de los títulos de las tarjetas y el de las rayas
  bajo los títulos.
- **El programa va en pestañas por día y sede**, o en acordeón si el evento lo
  eligió. Cada fila son tres columnas —tipo, qué y cuándo— con el color de la
  familia del tipo. Lo que no tiene fecha no sale.
- **Los eventos que escribieron el programa a mano esconden la parrilla** con
  su CSS a medida, apuntando a la clase `programa-estandar`. Sin esa clase el
  aplicativo pintaba el programa dos veces.
- **Arriba a la izquierda va el logo de quien publica**, y el pie es de su
  color. Las tarjetas de la portada van a cuatro por fila con la imagen
  cuadrada, y el texto se lee en gris sobre blanco con los títulos casi en
  negro.

Los tamaños, colores y tipografías se midieron con el navegador sobre las
páginas publicadas, no a ojo.

## Problema

¿Cómo se hace que la página pública se parezca a la de siempre sin meter en el
repositorio nada de quien despliega (ADR-0030) y sin perder lo que el
aplicativo ya garantiza —contraste legible, rejillas que miden el contenedor,
todo el aspecto en tokens—?

## Factores de decisión

- Quien visita no debería notar el cambio de sistema.
- El repositorio se publica: el logo, el color del pie y el nombre de quien
  despliega no se escriben aquí (ADR-0030).
- El CSS a medida de los eventos migrados tiene que seguir funcionando, porque
  hay eventos cuyo programa depende de él.
- El contraste mínimo de 4,5:1 no se negocia (`EventChrome::readable_ink()`).
- Sin dependencias nuevas: la página pública no garantiza Bootstrap
  (`Assets::has_bootstrap()`).

## Alternativas consideradas

### Opción 1: copiar la hoja del tema anterior

Pros: parecido inmediato. Contras: es una hoja de tercero pensada para un
constructor de páginas, con sus clases y su especificidad; rompería la regla de
que todo el aspecto sale de `var(--evt-*)` y ataría el aplicativo a un tema que
se quiere dejar.

### Opción 2: reproducir el marco con los tokens y los bloques de hoy

Pros: se queda dentro de la arquitectura (ADR-0041): cada diferencia es un
token, una clase o un campo más. Contras: hay que medir y ajustar pieza a
pieza, y algún color del sistema anterior no llega al contraste mínimo y se
oscurece.

## Decisión

Opción 2:

1. **La vista pública lee la apariencia de la página y, lo que deje vacío, del
   evento.** Una sección pinta sus colores, separador, logo, forma y
   tipografías si los tiene.
2. **Dos claves nuevas del evento**: `evt_accent` (color de acento) y
   `evt_header_bg_image_id` (una imagen detrás del color de la cabecera). Salen
   como los tokens `--evt-acento` y `--evt-fondo-img`, y se eligen en el panel
   «Apariencia».
3. **Una lista cerrada más**, `evt_programme_layout`: pestañas por día (vacío,
   el valor por defecto) o acordeón. Las pestañas las monta un guion propio de
   unas pocas líneas (`assets/js/evt-evento.js`), con el patrón de pestañas de
   WAI-ARIA; sin guion, los días salen uno debajo de otro. El acordeón es
   `<details name>` y no necesita guion.
4. **La parrilla conserva la clase `programa-estandar`** y deja fuera lo que no
   tiene fecha.
5. **La cabecera de una sección** lleva su ilustración (la imagen de su
   tarjeta) y su entradilla (la de su tarjeta), en dos columnas que miden el
   contenedor. El logo del evento se queda en la portada.
6. **El logo de quien publica y el color del pie entran por `evt_chrome`**
   (`brand_logo`, `brand_alt`, `brand_url`, `footer_bg`), vacíos por defecto.
   Sin configurar, no hay logo y el pie queda con el color suave de la página.
7. **Los colores del programa y del texto son tokens en `:root`**, con los
   valores de siempre salvo donde no llegaban a 4,5:1 sobre blanco: el azul de
   los enlaces y el de las charlas, y el ocre de las buenas prácticas, se
   oscurecen. En una sección el texto de la cabecera se mide contra el blanco,
   que es lo que tiene detrás en la parte alta del degradado.
8. **La página de contacto** reconoce un marcado de tres columnas —dónde,
   teléfono y correo— con su icono en el color de la cabecera
   (`.evt-ev__contacto`).

## Consecuencias

### Positivas

- La página migrada se parece a la de siempre sin copiar una hoja ajena.
- Los colores por sección que ya guardaba el formulario de página por fin se
  ven.
- El programa escrito a mano de los eventos que lo tienen no sale repetido.
- El repositorio sigue sin nada de nadie: el logo y el pie son configuración.

### Negativas

- Algunos colores no son exactamente los de antes: se oscurecen los que no se
  leían. Un evento cuya entradilla era amarilla sobre blanco la verá en negro.
- La clase `programa-estandar` es un nombre heredado que el aplicativo se
  compromete a mantener mientras haya CSS a medida que dependa de ella.
- El formulario de mensaje de la página de contacto de hoy no se reproduce: el
  aplicativo no envía correo. Queda pendiente de decidir aparte.

### Neutras

- La portada sigue con su color liso; el degradado es solo de las secciones.
- El CSS a medida que apuntaba a las clases del tema anterior hay que
  traducirlo al migrar: la cabecera es `.evt-ev__portada` y su ilustración,
  `.evt-ev__dibujo`.

## Adenda — 2026-09-27

Tras la primera revisión, tres piezas más que el sistema anterior tenía y que
iban metidas en el contenido de la página:

- **Los logos corporativos tienen su campo**, `evt_sponsors`: una lista de
  `{id, url}` (JSON), porque hay eventos con dos logos y eventos con doce. Se
  pintan abajo del todo en la portada, en fila y con su enlace, en un bloque
  propio (`SponsorsBlock`, `logos`). En el panel «Apariencia» salen los que hay
  y tres huecos más; guardar deja otros tres.
- **El programa en PDF también**, `evt_programme_file_id`: el botón «Descargar
  programa» sale en la página del programa, después del texto y antes de la
  parrilla, como hoy. El panel lo elige en la biblioteca con el mismo campo que
  las imágenes, restringido a `application/pdf`, y el servidor comprueba que
  sea un PDF.
- **«Editar esta página» en cada sección** para quien puede editarla, además
  de «Gestionar este evento». Con banner en la portada, el enlace de gestionar
  va debajo del banner: antes el banner se lo comía.

Y en «Mis eventos», el estado se elige con cuatro botones con su recuento
—Activos, Borradores, Históricos y Todos— y **por defecto salen los activos**:
publicados y no históricos, que es lo que se trabaja a diario. Los demás
filtros por estado siguen valiendo por la URL.

## Adenda — 2026-09-27: los datos de contacto son campos

El punto 8 de la decisión se quedaba en reconocer un marcado dentro del texto
de la página de contacto: se veía como hoy, pero quien organiza un evento nuevo
no tenía dónde escribir esos datos si no era tecleando ese HTML. La página de
contacto lleva ahora cuatro campos propios —`evt_contact_address` (una o varias
sedes, separadas por una línea en blanco), `evt_contact_phone` (uno por línea),
`evt_contact_email` y `evt_contact_map` (enlace)—, que se rellenan en su
formulario solo cuando la página es de contacto y que la página pinta en las
tres columnas con icono. El correo y el enlace se limpian al guardar: lo que no
es un correo o una dirección web no se guarda.

## Adenda — 2026-09-29: un solo botón y el menú sin iconos

- **Fuera «Editar esta página».** Bajo el título queda solo «Gestionar este
  evento»: dos botones que llevan casi al mismo sitio hacían dudar de cuál
  pulsar, y la página se edita igual desde la pestaña de secciones del taller.
- **Dentro de la vista previa del taller no sale ninguno.** Quien la mira ya
  está gestionando el evento; se sabe por la cabecera `Sec-Fetch-Dest: iframe`
  (`Shell::in_frame()`), la misma que ya quita la barra de administración.
- **El menú de arriba va sin iconos por defecto.** Cada sección conserva el
  suyo —en su tarjeta de la portada y en el taller—, y el evento que los quiera
  también en el menú marca la casilla de «Apariencia» (`evt_menu_icons`).
