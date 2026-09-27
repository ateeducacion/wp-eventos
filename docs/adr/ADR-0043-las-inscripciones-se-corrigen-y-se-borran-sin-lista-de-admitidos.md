---
id: ADR-0043
title: "Las inscripciones se corrigen y se borran desde el taller; no hay aforo del evento ni lista de admitidos"
status: Propuesta
date: 2026-09-27
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0027, ADR-0031, ADR-0032, ADR-0033, ADR-0036]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0043: Las inscripciones se corrigen y se borran desde el taller; no hay aforo del evento ni lista de admitidos

## Estado

Propuesta

## Contexto

La pestaña «Participantes» enseñaba las inscripciones y las exportaba, pero
no dejaba tocarlas. Quien organiza necesita dos cosas que hoy hace a mano o
pide a administración:

- **Corregir** una inscripción: un correo mal escrito, un centro equivocado,
  cambiar a alguien de taller.
- **Borrarla**: alguien que se da de baja, una inscripción repetida o de
  prueba.

Borrar una inscripción es borrar los datos de una persona y sus documentos
([ADR-0036](ADR-0036-los-ficheros-de-una-inscripcion-no-son-adjuntos.md)).
Un clic de más en una tabla de trescientas filas no puede hacerlo.

Además se preguntó si hay que soportar lo que hacía el sistema anterior con
las plazas: un tope de inscripciones por evento y listas de admitidos y
excluidos. Se revisaron las entradas, los campos y las vistas del sistema
anterior. El material y las cifras están en `.local/`:

- **Ningún formulario de inscripción limita el número de inscripciones.**
  Lo único que impiden es repetir el documento de identidad.
- **El aforo solo aparece como texto** en la descripción de algunos eventos:
  «plazas limitadas a N participantes, distribuidas en cupos», «N plazas por
  sede». Nada lo hace cumplir.
- **La página de inscripción tenía campos para subir un «listado
  provisional» y un «listado definitivo» de admitidos, con comentarios, y no
  los usa ningún evento publicado.** Los pocos eventos que publicaron una
  lista de admitidos y excluidos la subieron como un PDF o un CSV, hecho
  fuera del sistema.
- Donde sí había plazas de verdad era en los **talleres**, y eso ya está
  resuelto con aforo duro
  ([ADR-0033](ADR-0033-elegir-taller-aforo-duro-y-cambio-hasta-el-cierre.md)).

## Decisión

**Corregir.** Cada inscripción propia tiene un lápiz en «Participantes» que
abre el panel lateral con:

- el núcleo: documento, nombre, apellidos, correo, teléfono y código de
  centro. El núcleo se valida igual que al inscribirse, sin volver a pedir el
  consentimiento, y el centro sale del catálogo;
- las respuestas a las preguntas del evento, salvo las de archivo;
- el taller, que pasa por el mismo candado y el mismo aforo que cuando lo
  elige la persona. Un taller completo no admite a nadie más.

**Lo que no se corrige**: el consentimiento, su fecha, el testigo de la
inscripción y los documentos aportados. Son lo que aceptó y entregó la
persona, no un dato que se arregla. Un centro que llegó con nombre y sin
código se conserva mientras no se escriba uno.

**Borrar es definitivo y pide teclear el correo de la persona.** No hay
papelera: una inscripción borrada se lleva sus documentos, y guardarla en una
papelera sería conservar datos personales que se pidió quitar. Para que no se
haga por error, la confirmación enseña el correo y exige escribirlo. Se hace
en los tres escalones de siempre:

1. con SweetAlert2, un diálogo con un campo que no deja confirmar si el
   correo no coincide;
2. sin la librería, `prompt()`;
3. sin JavaScript, un campo en el propio formulario.

**El servidor compara el correo en los tres casos**, sin distinguir
mayúsculas ni espacios. Si no coincide, no se borra nada. Si la persona tenía
taller, su plaza queda libre sola, porque las plazas se cuentan sobre las
inscripciones que existen.

Solo se tocan las inscripciones del aplicativo. Una fila que llegue por el
filtro `evt_participants` desde otra fuente
([ADR-0027](ADR-0027-los-participantes-son-nuestros.md)) no trae
identificador y sigue siendo de solo lectura. Un evento histórico no se toca,
salvo que quien entra sea quien lo puede reabrir.

**No hay aforo del evento ni flujo de admitidos y excluidos.** Nunca estuvo
en el sistema anterior como función: estaba escrito en un texto o se hacía
fuera y se colgaba un PDF. Eso último sigue siendo posible hoy: el PDF va
como documento en la página de inscripción, y la lista sale del CSV de
«Participantes». Se añade si un evento lo pide de verdad, con su propia ADR.

## Consecuencias

### Positivas

- Las bajas, los errores y los cambios de taller se resuelven desde el
  taller del evento, sin pedírselo a administración.
- Borrar por error exige escribir un correo que no se teclea sin mirar a
  quién se borra.
- La plaza de taller de una baja vuelve sola a estar libre.

### Negativas

- **Un borrado no se deshace.** Es lo que se busca con los datos personales,
  pero equivocarse cuesta caro. Por eso se pide el correo.
- **Quien corrige puede cambiar el correo** y, con él, a quién llegan los
  avisos. Es la corrección que más se pide y no se limita. No queda registro
  de quién cambió qué.
- **Sin aforo del evento, nada impide que se apunte más gente que plazas.**
  Es lo que pasaba antes. Quien organiza cierra el plazo a mano.

### Neutras

- En la tabla, las acciones van en la primera columna. La tabla es ancha y se
  desplaza, y al final no se verían.

## Alternativas consideradas

- **Borrar a la papelera, como las secciones.** Descartada: conserva los
  datos y los documentos de quien pidió que se borraran, y la papelera de
  WordPress se vacía sola a los treinta días.
- **Confirmar solo con «¿Seguro?».** Descartada: en una tabla larga se
  confirma por costumbre.
- **Aforo del evento con lista de espera y estados admitida/excluida.**
  Descartada por ahora: ningún evento publicado lo usó, y es un flujo entero,
  con avisos, plazos y reclamaciones, que merece su propia decisión.
