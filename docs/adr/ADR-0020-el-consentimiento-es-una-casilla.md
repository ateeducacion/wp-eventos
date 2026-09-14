---
id: ADR-0020
title: "El consentimiento es una casilla: se acepta en pantalla y se guarda qué se aceptó y cuándo"
status: Aceptada
date: 2026-09-13
related:
  issues: []
  prs: []
  sdds: [SDD-0001, SDD-0002]
  adrs: [ADR-0007, ADR-0012]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0020: El consentimiento es una casilla: se acepta en pantalla y se guarda qué se aceptó y cuándo

## Estado

Aceptada (2026-09-13).

**Esto es un cambio de procedimiento administrativo**, y hay que leerlo como
tal y no como una decisión técnica. Lo decidió la **persona usuaria** —quien
gestiona el procedimiento— en la sesión de diseño del 2026-09-13, y está
dibujado en el artboard «8 · El consentimiento, simplificado»
(`.design/Consentimiento.dc.html`) del lienzo publicado, cuya URL privada vive
en `.local/lienzo-de-diseno.md`.

## Contexto

Hoy, para inscribirse a un evento hay que entregar un consentimiento **firmado
a mano o firmado digitalmente**, y entregarlo significa subir un fichero. No es
una impresión: está medido sobre la exportación de los formularios del sistema
anterior, que se conserva en `.local/`. De esa medición salen tres hechos.

**Cada formulario de inscripción lleva su propio campo de subida** de tipo
`file`, y en casi todos está marcado como obligatorio. No hay uno para todo el
procedimiento: hay tantos como formularios de inscripción.

**El nombre literal de esos campos lo dice todo**: «Subir consentimiento
firmado del uso y tratamiento de datos personales» y, en varios de ellos,
«Subir consentimiento **FIRMADO DIGITALMENTE** del uso y tratamiento de datos
personales».

**Varios formularios llevan además un bloque de texto fijo** que existe solo
para explicar cómo descargarse el modelo en PDF.

La cadena completa, para quien se inscribe, es esta:

> descargar el modelo en PDF → imprimirlo → firmarlo a mano → escanearlo o
> fotografiarlo → subirlo → y que alguien lo revise, uno a uno.

Seis pasos, de los cuales cuatro ocurren fuera del ordenador y uno ocurre
después, en la mesa de otra persona. Cada evento nuevo, además, se lleva su
propio campo de subida en su propio formulario: por eso el problema crece con
cada jornada en vez de resolverse una vez.

## Problema

¿Cómo se recoge el consentimiento de quien se inscribe a un evento, de forma
que la persona no tenga que imprimir ni escanear nada, y que al mismo tiempo
quede constancia suficiente de **qué** consintió y **cuándo**?

## Factores de decisión

- **Carga administrativa**: seis pasos por inscripción, multiplicados por los
  participantes de cada evento, más la revisión una a una.
- **Accesibilidad real**: imprimir y escanear supone impresora y escáner. No
  todo el mundo los tiene, y quien no los tiene queda fuera de un
  procedimiento público.
- **Minimización de datos**: el procedimiento de hoy obliga a custodiar un
  fichero con la **firma manuscrita** de cada persona. Es más dato personal
  del que el trámite necesita, guardado para siempre.
- **Valor probatorio**: si alguien reclama, hay que poder decir qué texto
  aceptó y en qué momento. Sin eso, no hay consentimiento que valga.
- **El texto cambia**: la información de tratamiento y el modelo de
  consentimiento se actualizan. Lo que se acepta hoy no es lo que se aceptará
  dentro de un año.
- **Convivencia**: los eventos antiguos siguen con su formulario y su campo de
  subida.

## Alternativas consideradas

### Opción 1: seguir con el PDF firmado y subido

| Pros | Contras |
|------|---------|
| Hay un papel con una firma: es lo que el procedimiento reconoce hoy. | Seis pasos por persona, cuatro de ellos fuera del ordenador. |
| Nadie tiene que decidir nada nuevo. | Exige impresora y escáner para un trámite público. |
| | Obliga a custodiar la firma manuscrita de cada participante: más dato personal del necesario. |
| | La revisión es manual, una a una, y no escala. |
| | Un campo de subida por formulario, uno por evento: crece con cada jornada. |

Descartada.

### Opción 2: firma electrónica de verdad (certificado, Cl@ve)

| Pros | Contras |
|------|---------|
| Valor probatorio máximo y no discutible. | Exige certificado o Cl@ve a quien se inscribe a una jornada: barrera mucho mayor que la de hoy. |
| | Integración con servicios que no dependen de este aplicativo ni de este subsitio. |
| | Desproporcionado para el trámite: asistir a unas jornadas no es un acto que lo requiera. |

Descartada por desproporción.

### Opción 3: firma dibujada en pantalla

| Pros | Contras |
|------|---------|
| «Parece» una firma, que es lo que el procedimiento espera ver. | No aporta nada probatorio que no aporte la casilla: es un dibujo. |
| | Vuelve a guardar un trazo biométrico: **más** dato personal, no menos. |
| | Difícil o imposible con teclado, con ratón, o con lector de pantalla. |

Descartada: cuesta accesibilidad y privacidad a cambio de apariencia.

### Opción 4: una casilla «Acepto», sin más

| Pros | Contras |
|------|---------|
| Máxima simplicidad. | Guardar solo «aceptó = sí» **no sirve**: si el texto cambia, nadie puede decir qué aceptó esa persona. |
| | Es exactamente el caso en el que un consentimiento no vale si alguien lo reclama. |

Descartada. Es la trampa fácil de esta decisión y por eso queda escrita.

### Opción 5: casilla «Acepto» con constancia de versión y sello de tiempo (elegida)

| Pros | Contras |
|------|---------|
| Un paso en vez de seis, dentro de la misma pantalla. | No hay firma: lo que respalda el consentimiento es un registro del propio sistema. |
| Sin impresora, sin escáner, sin fichero que custodiar. | El texto vigente pasa a ser, de hecho, versionado: hay que impedir que se reescriba en silencio. |
| Queda escrito qué versión del texto se aceptó y en qué momento. | Nadie de asesoría jurídica ha revisado el cambio todavía. |
| Se deja de almacenar la firma manuscrita de cada persona. | |

## Decisión

**Haremos la opción 5.** El consentimiento se presta **en la propia pantalla
de inscripción, marcando una casilla**, y del acto se guarda constancia.

Tal como está diseñado (`.design/Consentimiento.dc.html`, «Paso 3 de 3 ·
Protección de datos»):

1. **Se lee ahí mismo.** Dos documentos, cada uno con su línea de qué es y un
   botón «Leer» que lo abre sin salir de la inscripción:
   - «Información sobre el tratamiento de sus datos» — *quién trata sus datos,
     para qué, cuánto tiempo y cómo ejercer sus derechos*.
   - «Consentimiento informado» — *grabación de las sesiones y publicación de
     imágenes del evento*.
2. **Se acepta con una casilla**, destacada y de marcado **obligatorio**: «He
   leído la información sobre el tratamiento de mis datos y el consentimiento
   informado, y los acepto». Debajo, la ayuda: «Obligatorio para inscribirse.
   Se guarda la versión exacta que ha aceptado y el momento».
3. **Sin marcarla no hay inscripción.** La comprobación es del servidor, no del
   navegador.
4. **Lo que queda guardado, y es la parte que sostiene la decisión**: la
   **versión exacta del texto vigente en ese momento** y la **marca de
   tiempo** de la aceptación, junto a la inscripción. Un consentimiento sin
   constancia de qué se aceptó no vale si alguien lo reclama, y por eso la
   simplificación llega hasta aquí y no más allá.
5. **Quien organiza pone los textos**: en la configuración de la participación
   del evento, el texto de protección de datos (editor enriquecido) y el modelo
   de consentimiento informado. Cambiarlos crea una versión nueva; **no
   reescribe** la que ya ha aceptado alguien.

## Consecuencias

### Positivas

- Seis pasos pasan a ser uno, y el que queda ocurre dentro de la pantalla en la
  que ya se estaba.
- Desaparece la revisión manual de ficheros uno a uno.
- Se deja de custodiar la firma manuscrita de cada participante: menos dato
  personal guardado, no más.
- Deja de hacer falta impresora ni escáner para inscribirse a una jornada
  pública.
- El texto aceptado deja de ser un PDF suelto y pasa a ser un dato del sistema,
  consultable junto a la inscripción.

### Negativas

- **No hay firma, y eso no se disfraza.** Lo que respalda el consentimiento es
  un apunte del propio sistema: versión del texto y sello de tiempo, custodiado
  por quien organiza, que es parte interesada. Frente a una reclamación es
  menos que un documento firmado. La decisión acepta ese riesgo a cambio del
  ahorro, y la acepta quien gestiona el procedimiento.
- **Falta la validación jurídica.** A la fecha de esta ADR, **nadie de asesoría
  jurídica ha revisado el cambio**. Es un cambio de procedimiento
  administrativo y necesita ese visto bueno antes de ponerse en producción.
  Esta ADR no lo sustituye.
- **El texto vigente pasa a ser append-only de hecho, y nada lo garantiza
  todavía.** Si alguien edita el texto de protección de datos sin crear una
  versión nueva, todas las aceptaciones anteriores quedan apuntando a algo que
  ya no existe, y la constancia se pierde en silencio. Impedirlo es trabajo que
  hay que escribir; hoy no está escrito.
- **El consentimiento de un tercero no está resuelto.** El documento que se
  acepta cubre la grabación de sesiones y la publicación de imágenes. Cuando
  quien se inscribe no es quien aparece en la imagen —alumnado menor de edad
  acompañado— la casilla la marca una persona y consiente por otra. El diseño
  no cubre ese caso y esta ADR tampoco lo cierra.
- **Convivencia durante un tiempo largo.** Los eventos antiguos conservan su
  formulario del sistema anterior con su campo de subida: durante
  la convivencia habrá **dos formas** de haber consentido, y quien consulte un
  histórico tendrá que saber cuál mira.
- **Guardar la versión del texto ocupa.** Cada inscripción arrastra la
  referencia a la versión aceptada, y las versiones hay que conservarlas
  mientras se conserve la inscripción: no se pueden borrar los textos viejos
  sin destruir la constancia.

### Neutras

- Los campos `file` de los formularios de inscripción antiguos no se tocan ni
  se borran: siguen donde están mientras esos eventos sigan vivos.
- Dónde vive la inscripción —y por tanto dónde se guarda la aceptación— lo
  decide la ADR de las inscripciones: hoy la **ADR-0007**, que las dejó en el
  sistema anterior en la fase 1 y que queda **pendiente de ser sustituida** por la
  ADR de participación, en la que el aplicativo pasa a recoger las
  inscripciones. Esta decisión vale igual en los dos casos: lo que fija es
  **qué se guarda**, no dónde.
- La casilla obligatoria es una comprobación de servidor más en el alta de una
  inscripción, del mismo tipo que las que ya hay.

## Referencias

- **Lienzo del diseño**: su URL, que es privada, está en `.local/lienzo-de-diseno.md`.
- Fuente del diseño: `.design/Consentimiento.dc.html` (artboard «8 · El
  consentimiento, simplificado»).
- Medición: exportación de los formularios del sistema anterior, en `.local/`
  — los campos `file` «Subir consentimiento firmado…» de cada formulario de
  inscripción.
- **ADR-0007** — dónde viven hoy las inscripciones.
- [ADR-0012](ADR-0012-politica-de-edicion-y-auditoria.md) — qué se registra de
  quién hace qué.
