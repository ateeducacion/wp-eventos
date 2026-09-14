---
id: ADR-0030
title: "El repositorio se publica como software libre y no lleva dentro nada de nadie"
status: Aceptada
date: 2026-09-14
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0001, ADR-0022, ADR-0027]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0030: El repositorio se publica sin nada de nadie

## Estado

Aceptada (2026-09-14). Implementada en el código —`EventChrome::chrome()` y el
armazón de `Shell`— y comprobada en cada `make check` por
`scripts/check-public.mjs`. La limpieza de la documentación **está terminada**:
la comprobación no encuentra ninguna aparición.

## Contexto

Este repositorio va a publicarse en abierto, como software libre. Hasta ahora
se ha escrito dando por hecho lo contrario: que solo lo leería quien ya conoce
la instalación de destino. Eso dejó dentro tres cosas distintas, y conviene
separarlas porque no tienen la misma gravedad.

**Uno: infraestructura de un despliegue concreto.** El armazón de la página
pública traía escritas las direcciones del servidor de analítica y del aviso de
cookies de una organización, con su identificador de sitio. Publicarlo es
publicar a qué servidor van las visitas de la gente y con qué número se
contabilizan.

**Dos: la marca de esa organización.** La cabecera, el pie y los enlaces
legales estaban en el código, no en la configuración. Un aplicativo libre que
lleva dentro el escudo de alguien no lo puede usar nadie más sin borrarlo.

**Tres: el mapa del sistema que se sustituye.** La investigación previa
—cuántos formularios hay, cómo están numerados, qué hace cada fragmento de
código pegado a mano, cuántas personas tienen cada rol— es lo que hizo posible
diseñar el reemplazo, y es exactamente lo que no se enseña de una instalación
ajena. Una de esas ADR llega a describir una configuración de permisos
discutible de un sitio en marcha.

## Problema

¿Qué puede estar en un repositorio que se publica, y qué no?

## Factores de decisión

- **Lo que se publica no se puede despublicar.** Un repositorio en abierto se
  clona, se indexa y se archiva; borrar algo después no lo retira.
- **El aplicativo tiene que servirle a otro.** Si para instalarlo hay que
  borrar la marca de alguien, no es software libre, es el software de alguien
  con la licencia puesta encima.
- **La razón de una decisión vale más que el dato que la sostiene.** Se puede
  decir *por qué* se hizo algo sin enseñar el inventario de quien lo sufría.
- **Una regla que no se comprueba se rompe.** Esto no se arregla una vez: se
  arregla y se vigila.

## Decisión

### 1. El código no lleva dentro nada de ninguna organización

Todo lo que identifica un despliegue —el dueño del sitio, su rótulo, sus
enlaces legales, las URL del aviso de cookies y las de la analítica con su
identificador de sitio— sale de **un solo sitio configurable**,
`EventChrome::chrome()`, y **está vacío por defecto**.

**Lo que no se configura, no se pinta.** Una instalación recién hecha:

- no enseña el nombre ni el escudo de nadie,
- no carga ningún aviso de cookies,
- y **no envía ni una visita a ningún servidor**.

Quien despliega lo rellena desde fuera, con el filtro `evt_chrome`, en un
snippet suelto que no está en este repositorio. En el entorno de desarrollo lo
rellena el mu-plugin de `scripts/mu-plugins/`, con valores de `example.org` y
**sin analítica**, para que se pueda ver y probar la pantalla.

### 2. Dónde se despliega no se versiona

El sitio y el subsitio de destino viven en el `.env`, que no se sube.
`.env.dist` los trae **vacíos**, no de ejemplo: un valor de ejemplo que es el
real acaba copiado.

### 3. La investigación del sistema anterior vive en `.local/`

`.local/` ya estaba en el `.gitignore` para los datos personales descargados.
Ahí se va también **todo lo que describe la instalación que se sustituye**: su
arquitectura, sus inventarios y el detalle de sus formularios, vistas, campos y
fragmentos de código.

Las decisiones **no se pierden**: lo que se queda en el repositorio es el
porqué —qué problema había y qué se eligió— y lo que se va es el mapa. Donde
una ADR se apoyaba en una medición, la medición se cita como lo que es, algo
comprobado sobre material que no se publica.

### 4. Se comprueba en cada `make check`

`scripts/check-public.mjs` recorre **lo que git versionaría** —se lo pregunta a
git, así que respeta el `.gitignore`— y falla si encuentra infraestructura de
un despliegue, la marca de una organización, el nombre de la red de destino,
detalle interno del sistema anterior, cifras de plantilla o rutas locales de
quien desarrolla. Cada regla dice qué hacer, no solo que está mal.

## Alternativas consideradas

### Opción 1: publicar tal cual y confiar en que nadie lo lea

- Contras: es la que convierte una investigación interna en un mapa público de
  una instalación en marcha. Descartada sin matices.

### Opción 2: limpiar una vez, antes del primer commit

- Pros: es el trabajo mínimo y el momento adecuado.
- Contras: **no se sostiene sola.** La siguiente ADR que se escriba volverá a
  citar el sistema anterior, porque es de donde sale el problema. Sin una
  comprobación automática, la limpieza dura hasta el siguiente documento.

### Opción 3: limpiar y comprobar en cada `make check` — ELEGIDA

- Pros: la regla se mantiene sola, y quien la rompe se entera antes de subir
  nada, con el motivo escrito.
- Contras: una lista de expresiones es aproximada; habrá falsos positivos y se
  escapará lo que no esté en la lista. Es una red, no una garantía.

## Consecuencias

### Positivas

- **El aplicativo se puede instalar en otro sitio** sin borrar nada de nadie.
- **Una instalación nueva no envía datos a ningún sitio** sin que alguien lo
  configure a propósito, que es el defecto correcto para algo que se publica.
- La documentación mejora al dejar de apoyarse en identificadores internos:
  obliga a escribir el argumento en vez de señalar el número de un campo.

### Negativas

- **Se pierde trazabilidad.** Hasta ahora una ADR se podía comprobar abriendo
  el fichero que citaba. Quien no tenga `.local/` tendrá que fiarse de que la
  medición se hizo. Es el precio de publicar.
- **La red está puesta, y aprieta.** `check-public.mjs` está a cero y
  enganchado a `make check`, así que a partir de ahora un documento que nombre
  el sistema anterior por dentro deja el repositorio en rojo antes de subirlo.
  Es lo que se quería, pero tiene coste: escribir sobre lo que se sustituye
  obliga a redactar el argumento en genérico y a dejar la evidencia en
  `.local/`, y eso cuesta más que pegar el identificador de un campo.
- **Dos ADR hay que mirarlas una a una y no solo reescribirlas**: la que
  describe una configuración de permisos de un sitio en marcha y la que decide
  versionar el formulario del sistema anterior. Puede que la respuesta sea que
  no pertenecen a un repositorio público.

### Neutras

- La comprobación es de expresiones regulares y se le escapará lo que no esté
  en la lista. Cada vez que se encuentre algo nuevo, se añade una regla: la
  lista es el sitio donde vive lo aprendido.
- El mu-plugin de desarrollo rellena el armazón para que el wp-env se vea
  completo. Nunca se despliega, y sus valores son de `example.org`.
