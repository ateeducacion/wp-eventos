---
id: ADR-0036
title: "Los ficheros aportados en una inscripción no son adjuntos de WordPress"
status: Propuesta
date: 2026-09-19
related:
  issues: []
  prs: []
  sdds: []
  adrs: [ADR-0020, ADR-0027, ADR-0031, ADR-0032, ADR-0033]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0036: Los ficheros aportados en una inscripción no son adjuntos de WordPress

## Estado

Propuesta

## Contexto

La [ADR-0031](ADR-0031-el-formulario-de-inscripcion-es-nucleo-fijo-mas-preguntas.md)
fijó que el formulario de inscripción es un **núcleo fijo más unas pocas
preguntas por evento**, y que una pregunta tiene exactamente cuatro tipos:
`check`, `one`, `many` y `text`
([`Meta/RegistrationMetaKeys.php`](../../src/Evt/Meta/RegistrationMetaKeys.php),
`question_types()`). La raya estaba puesta a propósito para no acabar
construyendo otro gestor de formularios.

Esa lista deja fuera un caso que aparece en casi cualquier jornada: **pedir un
documento**. Una autorización firmada, un justificante, un certificado. Hoy la
única salida es sacar a la persona del formulario y pedírselo por correo, que
es exactamente lo que este aplicativo viene a quitar.

La otra mitad del contexto es de dónde salen las piezas que ya hay:

- La [ADR-0032](ADR-0032-la-inscripcion-es-un-contenido-del-evento.md) registra
  `evt_registration` **cerrado**: sin escritorio, sin REST, sin búsqueda, sin
  URL y sin archivo, y con `auth_callback` a `false` en todas sus metas
  ([`PostType/RegistrationPostType.php`](../../src/Evt/PostType/RegistrationPostType.php),
  [`Meta/RegistrationMetaRegistration.php`](../../src/Evt/Meta/RegistrationMetaRegistration.php)).
  Ahí dentro hay datos personales de gente que no trabaja aquí.
- La [ADR-0033](ADR-0033-elegir-taller-aforo-duro-y-cambio-hasta-el-cierre.md)
  ya le dio a esa persona una credencial: el testigo `evt_reg_token`, largo y
  aleatorio, que abre **su** inscripción y nada más del sitio.
- Los **assets editoriales del evento** —cartel, logo, fotos de ponentes— sí
  son contenido público del sitio y se guardan como adjuntos normales, con
  `media_handle_upload()`
  ([`PublicFront/EventWorkspace.php`](../../src/Evt/PublicFront/EventWorkspace.php),
  `upload()`). Eso funciona y no hay nada que arreglar ahí.

El problema aparece al juntar las dos cosas. Un `wp_insert_attachment()` no
crea «un fichero»: crea una entrada de `wp_posts` de tipo `attachment`, y con
ella, de serie y sin pedirlo:

| Lo que abre un adjunto | Dónde |
|---|---|
| La biblioteca de medios | `upload.php`, y el selector de medios de cualquier pantalla |
| Una página propia del adjunto | `is_attachment()`, con su permalink |
| La colección REST | `wp/v2/media` |
| El AJAX de medios | `query-attachments`, `get-attachment` |
| XML-RPC | `wp.getMediaLibrary`, `wp.getMediaItem` |
| Una URL física predecible | `wp_get_attachment_url()`, y el fichero servido directo por el servidor web |

Son seis superficies que el dominio **no necesita** para un documento privado,
y cada una habría que guardarla, probar que está guardada y volver a probarlo
cada vez que cambie WordPress o un plugin.

## Problema

¿Dónde se guarda un documento que aporta una persona participante al
inscribirse, y quién decide si alguien puede leerlo?

## Factores de decisión

- **Superficie.** Cuantas menos puertas se abran, menos hay que guardar. Una
  puerta que no existe no se olvida de cerrar.
- **Coherencia con lo que ya hay.** `evt_registration` está cerrado a
  propósito; sus ficheros no pueden estar más abiertos que sus metas.
- **Autorización sobre el objeto del dominio**, no sobre una capacidad
  genérica: quién puede leer un documento se decide por la inscripción y el
  evento a los que pertenece, con el guardián que ya existe
  ([`Access/EventAccess.php`](../../src/Evt/Access/EventAccess.php)).
- **No romper lo público.** El cartel de un evento tiene que seguir teniendo su
  URL pública y estar en la biblioteca de medios.
- **Tamaño del cambio.** Esto es un repositorio sin capa de servicios y sin
  contenedor de inyección; la solución tiene que caber en una clase.

## Alternativas consideradas

### Opción 1: adjunto normal de WordPress

Guardar el documento con `media_handle_upload()`, como el cartel, y quedarse el
ID de adjunto en una meta de la inscripción.

- **A favor:** no hay que escribir nada; la subida, la validación de tipo y el
  nombre único los hace WordPress.
- **En contra:** el documento aparece en la biblioteca de medios de cualquiera
  con `upload_files` —que el rol `evt_organiser` **tiene**
  ([`snippets/roles-and-profiles.php`](../../snippets/roles-and-profiles.php))—,
  tiene página propia, sale en `wp/v2/media`, y su URL física funciona para
  quien la tenga. Una autorización firmada con el nombre y el DNI de alguien
  queda a un enlace de distancia de cualquier área del sitio. Descartada.

### Opción 2: adjunto, y esconderlo después

Crear el adjunto y añadir filtros: `ajax_query_attachments_args` para que no
salga en la biblioteca, `rest_attachment_query` y `rest_prepare_attachment`
para la REST, `xmlrpc_enabled` o filtros equivalentes para XML-RPC,
`template_redirect` para la página de adjunto, una regla del servidor para la
URL física.

- **A favor:** reutiliza la mecánica de subida de WordPress.
- **En contra:** son **seis o más guardas distintas**, cada una en una API
  distinta, para un fichero que nunca quisimos publicar. Falla en abierto: la
  puerta está abierta y se tapa; si un filtro deja de aplicarse —otro plugin
  con más prioridad, un cambio de WordPress, una ruta nueva— el fichero se
  publica solo y nadie se entera. Y va contra la regla de la casa, que dice que
  esconder no es proteger. Descartada.

### Opción 3: fichero privado sin adjunto — **elegida**

El fichero se guarda en un subdirectorio propio de `uploads/`, con nombre
aleatorio, y **no se crea ninguna entrada en `wp_posts`**. La inscripción
guarda un descriptor y la descarga la sirve el aplicativo tras comprobar quién
pregunta.

- **A favor:** falla en cerrado. No hay biblioteca, no hay página de adjunto,
  no hay REST, no hay XML-RPC, no hay URL física que el dominio necesite —
  porque no existe el adjunto—. La autorización queda en **un solo sitio**.
- **En contra:** hay almacenamiento propio que limpiar, una descarga propia que
  mantener, y el fichero no se puede reutilizar desde la biblioteca de medios.
  Lo último es deliberado.

### Opción 4: los bytes en la base de datos

Guardar el contenido en una meta, en Base64 o en binario.

- **A favor:** la copia de seguridad de la base de datos lo lleva todo; no hay
  directorio que proteger.
- **En contra:** una meta de `wp_postmeta` con diez mebibytes en Base64 —un
  tercio más de tamaño que el original— se carga entera en memoria cada vez que
  se lee la inscripción, y la tabla que hoy guarda seis campos de texto pasa a
  ser un almacén de ficheros. El volcado de la base de datos deja de caber en
  una revisión. Descartada.

### Opción 5: confiar solo en un nombre aleatorio

Guardar el fichero en `uploads/` con un nombre imposible de adivinar y servirlo
por su URL directa.

- **A favor:** no hace falta ni manejador de descarga ni permisos especiales.
- **En contra:** un nombre aleatorio evita que el nombre cuente algo; **no es
  una autorización**. La dirección viaja en el historial del navegador, en el
  `Referer`, en los registros del servidor y en cualquier sitio donde se pegue,
  y una vez fuera vale para siempre y para cualquiera. Descartada como única
  defensa; el nombre aleatorio se usa igualmente, pero **encima** de la
  protección, no en su lugar.

## Decisión

Se añade `file` como **quinto** tipo de pregunta, con rótulo «Archivo», y se
separa el almacenamiento en dos caminos que no se mezclan:

```text
ASSET EDITORIAL DEL EVENTO      DOCUMENTO PRIVADO DE PARTICIPANTE
→ adjunto de WordPress          → almacén privado del aplicativo
→ biblioteca de medios          → sin adjunto, sin biblioteca
→ URL pública                   → sin REST ni XML-RPC de medios
                                → sin página de adjunto
                                → descarga autorizada por el aplicativo
```

Lo concreto, en [`PublicFront/RegistrationFiles.php`](../../src/Evt/PublicFront/RegistrationFiles.php):

1. **Una pregunta `file` no configura nada.** Tiene identificador, rótulo, tipo
   y si es obligatoria, y nada más. Ni tamaño, ni tipos, ni varios ficheros, ni
   reglas condicionales: eso convertiría la lista de preguntas en el
   constructor de formularios del que la ADR-0031 se sale. **Un fichero por
   pregunta.**
2. **La política es del aplicativo**, en un solo sitio: lista cerrada de PDF,
   JPEG, PNG, DOCX y ODT, y un tope de `min( 10 MiB, wp_max_upload_size() )`.
   No se admite nada que un navegador pueda ejecutar o interpretar —PHP, HTML,
   SVG, JavaScript— ni nada empaquetado. Del `type` que manda el navegador no
   se fía nadie: decide `wp_check_filetype_and_ext()`, que mira el contenido y
   lo cruza con la extensión, y por encima la lista propia.
3. **El nombre físico es opaco:** 32 dígitos hexadecimales aleatorios más la
   extensión que salga del tipo ya validado, en `uploads/evt-private/ab/cd/`.
   No lleva el nombre de la persona, ni su documento, ni su correo, ni el
   título del evento, ni el nombre original del fichero.
4. **El fichero queda en modo `0200`:** se puede escribir, no leer. Para leerlo
   hay que abrirlo a propósito, y se vuelve a cerrar en el `finally`. Se
   escribe además un `.htaccess` de denegación, que es un cinturón y no el
   pantalón: **nginx no lo lee**, y por eso la protección real es el modo del
   fichero y que la descarga pase por el aplicativo.
5. **Lo que se guarda en la inscripción es un descriptor**, en su propia meta
   `evt_reg_files` —no dentro de `evt_reg_answers`, que sigue siendo solo
   respuestas de `check`, `one`, `many` y `text`—: `id` opaco, `name` original
   saneado, `mime` validado, `size`, `sha256` y `stored`, que es una ruta
   **relativa** a la raíz privada. Ni ruta absoluta, ni URL, ni identificador
   de adjunto. La meta se registra como las demás de la inscripción:
   `show_in_rest` a `false` y `auth_callback` a `false`.
6. **El alta es todo o nada.** Se validan los ficheros antes de crear nada; si
   falta un documento obligatorio o uno no pasa la política, no llega a existir
   la inscripción. Si guardar un documento falla con la inscripción ya creada,
   se borran los que ya estaban guardados y se borra la inscripción.
7. **La descarga la sirve el aplicativo**, nunca el servidor web, y resuelve
   siempre la cadena entera: identificador opaco → inscripción → evento →
   autorización → ruta relativa → bytes. Autoriza el **testigo de esa
   inscripción** —la credencial que la ADR-0033 ya le dio a esa persona; no se
   inventa otra— o `EventAccess::can_open()` sobre su evento. `can_open()` y no
   `can_edit()` a propósito: un evento marcado como histórico deja de editarse
   y sus inscripciones se siguen consultando. La respuesta va con
   `Content-Disposition: attachment`, el tipo validado,
   `X-Content-Type-Options: nosniff` y `Cache-Control: private, no-store`.
8. **Borrar definitivamente una inscripción se lleva sus ficheros; mandarla a
   la papelera, no.** Restaurar una inscripción sin sus documentos es restaurar
   otra cosa.

Y lo que **no** cambia: los assets editoriales del evento siguen entrando por
`media_handle_upload()`, siguen siendo adjuntos, siguen en la biblioteca de
medios y siguen teniendo URL pública. **No se instala ni un filtro global sobre
la biblioteca de medios, la REST de medios o las páginas de adjunto.** La
privacidad la decide quién creó el fichero y para qué, no volver privada la
biblioteca entera.

## Consecuencias

### Positivas

- La invariante se puede comprobar de una sola manera, y se comprueba: después
  de guardar un documento de participante, en `wp_posts` no hay ni un adjunto
  más ([`tests/unit/test-registration-files.php`](../../tests/unit/test-registration-files.php)).
  No sale en la biblioteca, ni en `wp/v2/media`, ni en una página de adjunto,
  **sin haber escrito un solo filtro para conseguirlo**.
- La autorización vive en un único manejador y se lee entera de una vez.
- El descriptor no contiene ninguna ruta absoluta ni ninguna URL, así que un
  volcado de la base de datos no dice dónde está nada.
- El camino público no se toca, y hay un test que lo sujeta: un cartel de
  evento sigue siendo un adjunto con su URL pública.

### Negativas

- **Hay almacenamiento propio que limpiar.** La limpieza cuelga de
  `before_delete_post`; una inscripción borrada con SQL a pelo dejaría sus
  ficheros huérfanos.
- **Hay una descarga propia que mantener.** Cada cabecera de esa respuesta es
  responsabilidad de este repositorio y no de WordPress.
- **Las copias de seguridad tienen que incluir `uploads/evt-private/`.** Está
  dentro de `uploads/`, así que una copia normal del directorio lo lleva, pero
  conviene decirlo porque un fichero en modo `0200` es fácil de perder con una
  herramienta que copie solo lo legible.
- **Estos ficheros no se pueden reutilizar desde la biblioteca de medios**, y
  es deliberado: si algún día hace falta publicar uno, se sube otra vez como
  asset público, que es una decisión de quien organiza y no un efecto
  secundario.
- El modo `0200` **no protege si el proceso corre como `root`** o si el usuario
  del servidor web es el propietario y el sistema ignora el modo. Es una capa,
  no un muro.

### Neutras

- **No se decide aquí una política de retención.** Hoy no existe ninguna en el
  aplicativo: los documentos viven mientras viva su inscripción. Cuando se
  decida cuánto se guarda una inscripción, ahí se decidirá también cuánto se
  guardan sus documentos, y será otra ADR.
- No se diseña cifrado en reposo ni almacenamiento en objeto (S3 y parientes).
  Si alguna vez hiciera falta, el sitio donde entra es
  `RegistrationFiles::store()` y `::read()`, que son las dos únicas funciones
  que tocan el disco.
- La concurrencia no se aborda: dos lecturas simultáneas del mismo documento
  pueden solaparse al abrir y cerrar el modo. El peor caso es que una de las
  dos vea el fichero abierto unos milisegundos de más, no que alguien no
  autorizado lo lea, porque la autorización va antes y es independiente del
  modo.
- El filtro `evt_private_files_dir` permite mover la raíz, y
  `evt_private_file_mimes` ampliar la lista de tipos. Los dos se ven escritos
  en el repositorio y se auditan; ninguno de los dos relaja la autorización.
