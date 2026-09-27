---
id: ADR-0042
title: "Los eventos conservan sus URL de hoy y su página pública pinta ponentes y programa"
status: Propuesta
date: 2026-09-27
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0008, ADR-0015, ADR-0024, ADR-0041]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0042: Los eventos conservan sus URL de hoy y su página pública pinta ponentes y programa

## Estado

Propuesta

## Contexto

Para valorar una migración completa se cargaron en local, con un guion que no
se versiona, todos los eventos publicados del sistema anterior: sus páginas,
ponentes, actividades, sedes e inscripciones. El resultado está medido y el
material está en `.local/`. Salieron dos clases de problemas.

**Lo que el aplicativo no hacía:**

- **La página pública no pintaba ni ponentes ni programa.** Las secciones
  «Ponentes», «Programa», «Actividades» y «Multimedia» salían vacías aunque el
  panel de gestión tuviera los datos. Hoy esas páginas las pinta una vista del
  gestor de formularios por cada tipo de página.
- **La portada no enseñaba a los ponentes destacados**, que hoy salen en
  «Personas comunicadoras».
- **Las tipografías no se cargaban.** Se guardaban y se escribían en el token,
  pero ningún fichero las traía: solo se veían en el ordenador que las tuviera
  instaladas.
- **El cartel tenía que ser una imagen**, y la mayoría de los carteles de hoy
  son un PDF que se descarga.

**Lo que se perdía al migrar:**

- **Las URL.** Hoy un evento vive en la raíz del sitio, `/<evento>/<sección>/`,
  y el menú de cada evento enlaza por `?page_id=<N>`. Cada ponente y cada
  actividad tienen su ficha en `<sección>/entry/<N>/`, con el número de su
  entrada en el formulario. Todas están enlazadas desde fuera: correos,
  carteles impresos, otras webs. El aplicativo servía los eventos bajo
  `/evento/` y no tenía fichas.
- **Los tipos de actividad** que usan los programas publicados —Conferencia,
  Buenas prácticas, Experiencia, Encuentro, Actuación, Proyección
  audiovisual— se quedaban en «Otra».
- **Las siluetas de separador** que usan los eventos —ondas, nubes, montañas,
  gráfica, flecha— se quedaban sin separador.
- **Quién participa en una actividad.** Hoy se escribe a mano: ponentes del
  evento y «otros participantes» que no son ponentes (quien inaugura, quien
  modera). El aplicativo solo enlazaba ponentes del evento.
- **El vídeo de cada actividad**, que llena la página de multimedia.

## Decisión

**Las URL de hoy siguen valiendo.**

- Una opción de Ajustes, `evt_root_urls`, sirve los eventos **en la raíz del
  sitio**. El filtro `request` mira la ruta pedida entera: si no hay ninguna
  `page` ni entrada en ella y sí un evento, sirve el evento. **Una `page` con
  la misma ruta siempre gana.** El enlace permanente del evento pasa a ser el
  de la raíz. Apagada, todo sigue bajo `/evento/`.
- `?page_id=<N>` y `?p=<N>` de un evento llevan a él, y `redirect_canonical()`
  a su URL bonita. Basta con que el evento conserve su ID, que es lo que ya
  decía la [ADR-0008](ADR-0008-migracion-de-los-eventos-historicos.md).
- **Las fichas cuelgan de su sección**: `<sección>/entry/<N>/`, en la raíz y
  bajo `/evento/`, sin reglas de reescritura nuevas (no hay que regenerarlas al
  desplegar un snippet). `<N>` es el ID del ponente o de la actividad; si ese
  número no es de este evento, se busca en `evt_legacy_entry`, donde la
  migración guarda el número antiguo cuando el ID ya lo ocupa otra cosa.

**La página pública pinta lo que es de su tipo** (un bloque más del esqueleto,
`programa`):

| Sección | Pinta |
|---|---|
| `ponentes` | Todos los ponentes con foto, cargo, un extracto y «Leer más»; con `/entry/<N>/`, la ficha entera y en qué actividades participa |
| `programa` | La parrilla por sede y día ([ADR-0024](ADR-0024-un-dia-puede-tener-dos-sedes.md)): hora, tipo, sala, título y quién participa |
| `actividades` | Las actividades con tipo, extracto, participantes, día y hora; con `/entry/<N>/`, la ficha con su vídeo |
| `multimedia` | Las actividades que tienen vídeo, con el vídeo |
| portada | «Personas comunicadoras»: los ponentes marcados como destacados |

Lo escrito en la sección sale antes, como siempre. Un ponente sin foto sale con
una silueta. Solo se pinta lo publicado.

**Datos nuevos**, todos en el panel lateral de siempre:

- ponente: `evt_speaker_featured`, «Destacar en la portada»;
- actividad: `evt_activity_video`, una URL que WordPress sepa embeber, y
  `evt_activity_guests`, otros participantes, uno por línea.

**Listas cerradas, con lo que se usa.** Siguen siendo listas en código
([ADR-0004](ADR-0004-una-taxonomia-por-dimension.md)), pero con los valores
que usan los eventos publicados:

- seis tipos de actividad más;
- cinco siluetas más; las variantes de una forma se quedan en la forma;
- las tipografías que usan de verdad los eventos, además de las cinco elegidas.
  Se cargan de Fontsource desde jsDelivr, solo el subconjunto latino, con SRI y
  la versión clavada en `package.json`, la URL y el código
  ([ADR-0015](ADR-0015-librerias-de-terceros-desde-cdn-con-sri.md)).

**El cartel puede ser un PDF.** Entonces no se pinta: se ofrece «Descargar el
cartel (PDF)» y la imagen que se ve es la destacada del evento. Guardar la
apariencia conserva un cartel que ya estaba aunque no sea una imagen.

## Consecuencias

### Positivas

- Un evento migrado se ve con sus ponentes, su programa, sus fichas y sus
  vídeos, **en la misma URL**, y el resto de la web no se entera del cambio.
- Las tipografías funcionan también en los eventos nuevos.
- Retirar el sistema anterior es moverlo a otra ruta: los eventos ocupan la
  suya.

### Negativas

- **En la raíz, una `page` puede tapar a un evento** con la misma ruta, y nada
  lo avisa. Es a propósito —lo que existe gana—, pero hay que comprobar los
  nombres antes de encender la opción.
- Resolver la ruta cuesta dos o tres consultas más cuando WordPress no
  encuentra una `page`: solo en esas peticiones, no en las demás.
- **La lista de tipografías crece** hasta la veintena: se cierra lo que había,
  no se ordena. Cada familia añade un paquete a `package.json`.
- `evt_legacy_entry` es un dato de la migración dentro del modelo. Nadie lo
  edita y solo se lee al abrir una ficha antigua.
- **No se parece píxel a píxel**: el tema, las columnas y los iconos de hoy no
  se copian. Se conserva el contenido, el orden y la URL.

### Neutras

- No cambia la [ADR-0008](ADR-0008-migracion-de-los-eventos-historicos.md):
  congelar lo histórico sigue siendo una opción. Esta ADR hace posible la otra,
  recrearlo en el aplicativo sin perder las URL.

## Alternativas consideradas

- **Reglas de reescritura propias en la raíz.** Descartada: hay que
  regenerarlas en cada despliegue y un snippet no tiene un momento de
  activación en el que hacerlo. El filtro `request` no necesita nada guardado.
- **Tipo de contenido sin prefijo (`'slug' => '/'`).** Descartada: WordPress
  lo interpreta antes que las páginas y deja de servir las `page` de la raíz.
- **Copiar el HTML pintado de hoy dentro de cada sección.** Descartada: se
  vería igual el primer día y no se podría editar nunca más.
- **Mapear las tipografías a la más parecida de las cinco.** Descartada: el
  evento migrado no se vería como hoy, que es lo que se pide.
