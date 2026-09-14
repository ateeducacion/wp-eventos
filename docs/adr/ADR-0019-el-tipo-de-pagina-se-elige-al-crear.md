---
id: ADR-0019
title: "El tipo de una página se elige al crearla y no se cambia después"
status: Aceptada
date: 2026-09-13
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0003, ADR-0008, ADR-0009, ADR-0016, ADR-0018]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0019: El tipo de una página se elige al crearla y no se cambia después

## Estado

Aceptada (2026-09-13).

**De dónde sale esta decisión.** La señaló la **persona usuaria** durante la
sesión de diseño del 2026-09-13, mirando la pantalla de alta de una página:
«no tiene sentido que elija el tipo al crearla y luego pueda cambiarlo». No es
una conclusión del equipo de desarrollo leyendo el código; es una corrección
hecha sobre el diseño, en voz alta, y aceptada tal cual. El lienzo está publicado —su URL es privada y vive en
`.local/lienzo-de-diseno.md`— y sus fuentes se versionan en `.design/*.dc.html` con su `.design/canvas.json`; esta
decisión es el artboard «3 · Añadir una página» (`.design/NuevaPagina.dc.html`)
y el paso 2 de «1 · Crear el evento» (`.design/Crear.dc.html`).

## Contexto

Por la **ADR-0003**, un evento es un `evt_event` jerárquico: la raíz es el
evento y las hijas son sus páginas. Qué es cada hija se guarda en la meta
`evt_section_type`, con una **lista cerrada de once valores** en PHP, calcada
del campo «Tipo de página» del formulario del sistema que se sustituye
(`EventMetaKeys::section_types()`):

```
programa | ponentes | inscripcion | multimedia | contacto | actividades
| encuesta | participacion | preguntas | directo | otra
```

Ese valor no es decorativo: es **lo que decide qué se pinta**. Tanto en la
plantilla del sistema anterior, que ramifica sobre ese mismo campo —«programa»,
«ponentes», «contacto», «actividades»— con condicionales anidados
([ADR-0010](ADR-0010-la-pagina-la-genera-codigo-versionado.md)), como en el
código que la sustituye.

Y hoy, en el aplicativo nuevo, **ese tipo se puede cambiar después de crear la
página**: `PageForm` carga el valor guardado al editar y lo vuelve a escribir
al guardar (`PageForm::save()`), y la vista pinta
el mismo desplegable en el alta y en la edición, con `selected()` marcando el
actual (`PageFormView::form()`). Nada lo impide.

El diseño del 2026-09-13 le da al tipo un papel mucho más fuerte del que tenía:
**cada tipo trae sus elementos preparados**, y esos elementos son lo que se ve
y se maneja en el taller del evento
([ADR-0018](ADR-0018-el-taller-del-evento-es-una-sola-pantalla.md)). En la
pantalla de alta, cada tipo se enseña con lo que lleva dentro antes de elegir:

| Tipo | Lo que se lee | Elementos que trae |
|---|---|---|
| Programa | «La parrilla del evento, por día y sala» | Parrilla · Texto · PDF · Botón de inscripción |
| Ponentes | «Quién interviene, con su foto y su reseña» | Fichas · Texto |
| Inscripción | «El formulario y las condiciones» | Formulario · Consentimiento · Plazos |
| Multimedia | «Galería y vídeos de las sesiones» | Galería · Vídeos |
| Contacto | «Cómo llegar y con quién hablar» | Mapa · Correo · Teléfono |
| Otra | «Texto libre, para lo que no encaje arriba» | Texto · Imágenes |

Con eso encima de la mesa, poder cambiar el tipo después deja de ser una
comodidad y pasa a ser un problema: **una página de programa y una de contacto
no llevan lo mismo dentro**. Cambiar el tipo de una a otra deja los elementos
del tipo viejo huérfanos —datos guardados que ya nadie pinta— y los del tipo
nuevo vacíos.

## Problema

¿El tipo de una página satélite es un dato editable como el título, o es parte
de lo que la página **es** y por tanto se fija al crearla?

## Factores de decisión

- **Lo que la persona usuaria dijo**: elegir el tipo y poder cambiarlo después
  es incoherente y se lee como un error de la pantalla.
- **Datos huérfanos**: un cambio de tipo deja meta escrita que ya nadie lee y
  que nadie sabe que está ahí.
- **Explicar el cambio es imposible en una línea**: no hay forma de decir en
  castellano llano, en el momento de cambiar, qué se pierde y qué se queda.
- **La salida tiene que existir y ser barata**: equivocarse eligiendo tiene que
  tener arreglo.
- **Migración**: las páginas que vienen de producción traen su tipo de aquel
  campo, que se rellenaba a mano
  ([ADR-0008](ADR-0008-migracion-de-los-eventos-historicos.md)). Algunas
  vendrán mal.
- **Menos campos, mejores valores por defecto**: la pantalla de alta debe
  enseñar qué lleva cada tipo **antes** de elegir, no después.

## Alternativas consideradas

### Opción 1: el tipo se cambia siempre (lo de hoy)

| Pros | Contras |
|------|---------|
| Ya está escrito: no hay que tocar nada. | Es justo lo que la persona usuaria señaló como incoherente. |
| Arregla un error de tipo sin crear otra página. | Deja meta huérfana del tipo viejo, sin aviso y sin limpieza. |
| | La página queda a medias: los elementos del tipo nuevo están vacíos y nada dice cuáles. |
| | El desplegable ocupa sitio en la edición y no se usa casi nunca. |

Descartada.

### Opción 2: se puede cambiar mientras la página esté en borrador y vacía

| Pros | Contras |
|------|---------|
| Cubre el caso real: equivocarse al crear y darse cuenta enseguida. | «Vacía» hay que definirlo elemento a elemento, y cada elemento nuevo obliga a revisar la definición. |
| Nada se pierde, porque no había nada. | Un desplegable que a veces está y a veces no es más difícil de explicar que uno que no está nunca. |
| | Resuelve exactamente lo mismo que mandarla a la papelera y crear la buena, pero con una regla que hay que mantener. |

Descartada por coste: la salida de la opción 4 hace lo mismo sin regla nueva.

### Opción 3: se puede cambiar, con una migración de elementos entre tipos

Una tabla de correspondencias tipo → tipo que decide qué elemento del viejo se
convierte en cuál del nuevo, y qué se descarta.

| Pros | Contras |
|------|---------|
| Es la opción «completa»: nada se pierde por sorpresa. | Once tipos son noventa y tantos pares posibles, y la mayoría no tienen correspondencia ninguna. |
| | Hay que escribirla, probarla y mantenerla cada vez que un tipo gane un elemento. |
| | Para el caso real —me he equivocado al crear— es maquinaria enorme para un problema de treinta segundos. |

Descartada.

### Opción 4: el tipo se fija al crear y no se cambia (elegida)

| Pros | Contras |
|------|---------|
| El tipo pasa a ser lo que la página es, no un ajuste suyo. | Quien se equivoque **con la página ya escrita** tiene que copiar el contenido a mano. |
| No hay datos huérfanos porque no hay cambio de tipo. | Un tipo mal migrado desde producción solo se arregla desde el escritorio. |
| La pantalla de alta puede enseñar qué lleva cada tipo, porque la elección importa. | |
| Un desplegable menos en la edición. | |

## Decisión

**Haremos la opción 4.** El tipo de una página satélite se elige **al
crearla** y **no se cambia después**.

Cómo se traduce en pantalla, tal como está diseñado:

1. **Al crear el evento** (`.design/Crear.dc.html`, paso 2 «Qué páginas va a
   tener») se marcan las páginas que el evento va a llevar, para que no empiece
   vacío. La ayuda lo dice sin rodeos: «Podrás añadir más y quitar las que no
   uses, pero **el tipo de cada página no se cambia después**: es lo que decide
   qué lleva dentro». La **portada siempre se crea**: es la entrada del evento.
2. **Al añadir una página suelta** (`.design/NuevaPagina.dc.html`), antes de
   elegir se enseña **qué lleva cada tipo**, en tarjetas con su nombre, una
   línea de qué es y las etiquetas de sus elementos —la tabla del contexto—.
   Se elige sabiendo.
3. **La advertencia es explícita, con candado**, al pie de la pantalla de alta:
   «El tipo no se cambia después. Una página de programa y una de contacto no
   llevan lo mismo dentro. Si se equivoca, la manda a la papelera y crea la que
   quería: no habrá escrito nada todavía».
4. **En la edición de una página, el desplegable de tipo desaparece.** El tipo
   se enseña como dato, no como control.
5. **La salida es la papelera**, que ya es la forma de borrar de este
   aplicativo ([ADR-0016](ADR-0016-borrar-es-enviar-a-la-papelera.md)):
   `wp_trash_post()`, con confirmación en SweetAlert2, y se crea la buena. Nada
   se pierde: la equivocada se puede restaurar.

La lista cerrada de `EventMetaKeys` sigue siendo la única fuente de tipos
válidos, y el saneado en el alta sigue rechazando cualquier valor que no esté
en ella.

## Consecuencias

### Positivas

- La pantalla de alta puede permitirse explicar, porque la elección es la que
  importa: se enseña qué lleva cada tipo antes de elegir, no después de
  equivocarse.
- No existen páginas a medio camino entre dos tipos, ni meta escrita que ya
  nadie pinta.
- Un control menos en la edición, que es la pantalla que más se usa.
- El código que pinta puede confiar en que el tipo de una página no cambia
  durante su vida: no hay que defenderse de una transición que no ocurre.

### Negativas

- **Quien se equivoque con la página ya escrita tendrá que copiar el contenido
  a mano.** Esto no se suaviza: si alguien crea una página «Otra», escribe tres
  párrafos y luego decide que debía ser «Programa», la única salida es abrir
  las dos, copiar y pegar. No hay botón que lo haga.
- **Un tipo mal migrado no se arregla desde el aplicativo.** Las páginas que
  vienen de producción traen el tipo de aquel campo, que se rellenaba a mano;
  las que vengan mal habrá que corregirlas editando la meta **desde el
  escritorio de WordPress**, que es acceso de administración. El personal de un
  área no podrá.
- **El diseño enseña seis tipos y la lista cerrada tiene once.** El artboard de
  alta muestra Programa, Ponentes, Inscripción, Multimedia, Contacto y Otra; en
  `EventMetaKeys` hay además `actividades`, `encuesta`, `participacion`,
  `preguntas` y `directo`. O el alta esconde cinco tipos, o la lista se
  recorta. **Esta ADR no lo cierra** y hay que decidirlo antes de escribir la
  pantalla.
- **Se pierde una salida legítima**: hoy, cambiar el tipo arregla en un
  desplegable un error que a partir de ahora cuesta dos pantallas. Para quien
  usa el aplicativo a diario y no se equivoca, es indiferente; para quien
  empieza, es un paso más.
- **Queda por comprobar qué pasa con la dirección web.** Al mandar la página
  equivocada a la papelera y crear la buena con el mismo título, el slug se
  disputa entre las dos. WordPress renombra el slug de lo que manda a la
  papelera, pero **no está medido en el wp-env de este proyecto** y hay que
  medirlo antes de dar la salida por buena.

### Neutras

- La decisión no toca el modelo de datos: `evt_section_type` sigue siendo la
  misma meta con la misma lista cerrada. Lo que cambia es quién puede
  escribirla y cuándo.
- La portada del evento sigue siendo un caso aparte: se crea siempre y no se
  elige su tipo, porque no hay nada que elegir.
- El vocabulario de los tipos sigue en inglés como identificador y en
  castellano en pantalla
  ([ADR-0009](ADR-0009-identificadores-internos-en-ingles.md)).

## Referencias

- **Lienzo del diseño**: su URL, que es privada, está en `.local/lienzo-de-diseno.md`.
- Fuentes del diseño: `.design/NuevaPagina.dc.html` (artboard «3 · Añadir una
  página»), `.design/Crear.dc.html` (artboard «1 · Crear el evento», paso 2) y
  `.design/canvas.json`.
- **ADR-0003** — el tipo de sección es una lista cerrada en código.
- [ADR-0008](ADR-0008-migracion-de-los-eventos-historicos.md) — de dónde vienen
  los tipos de las páginas migradas.
- [ADR-0016](ADR-0016-borrar-es-enviar-a-la-papelera.md) — la salida cuando uno
  se equivoca.
- [ADR-0018](ADR-0018-el-taller-del-evento-es-una-sola-pantalla.md) — dónde se
  ven los elementos que trae cada tipo.
