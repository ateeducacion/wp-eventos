# El lienzo de diseño de la gestión de eventos

Aquí vive el diseño de las pantallas del aplicativo: los bocetos sobre los que se decidieron
[ADR-0018](../docs/adr/ADR-0018-el-taller-del-evento-es-una-sola-pantalla.md),
[ADR-0019](../docs/adr/ADR-0019-el-tipo-de-pagina-se-elige-al-crear.md),
[ADR-0020](../docs/adr/ADR-0020-el-consentimiento-es-una-casilla.md) y
[ADR-0021](../docs/adr/ADR-0021-el-ponente-pertenece-a-su-evento.md).

No es código del aplicativo: nada de esto se despliega ni entra en el bundle. Es el dibujo de
cómo tienen que quedar las pantallas, hecho con los colores, botones y tipografía reales de
`assets/css/evt-app.css`, no inventados.

La URL del lienzo publicado es privada y no se versiona: está en
`.local/lienzo-de-diseno.md`.

## Qué hay

| Fichero | Qué es | ¿Se versiona? |
|---|---|---|
| `*.dc.html` | Un fichero por pantalla («artboard»): el boceto en sí | **Sí** |
| `canvas.json` | Dónde se coloca cada pantalla en el lienzo, y las notas al margen | **Sí** |
| `_base.css` | Los tokens de color, tipografía y espaciado, copiados de `assets/css/evt-app.css` | **Sí** |
| `gestion-de-eventos.html` | El lienzo **sembrado**: el editor empaquetado con los bocetos dentro. Pesa 2,5 MB | **No** — ignorado en `.gitignore` |

Las ocho pantallas, en el orden del recorrido: `Crear`, `Main` (el taller del evento),
`NuevaPagina`, `Ponentes`, `Programa`, `Talleres`, `Participantes` y `Consentimiento`.

## Por qué el HTML sembrado no se versiona

`gestion-de-eventos.html` no lo escribe nadie a mano: lo genera el editor del lienzo, que mete
dentro del mismo fichero **su propio código y el contenido de los bocetos**. De ahí los 2,5 MB, y de
ahí que cada guardado cambie el fichero entero: un diff ilegible que no dice qué se ha movido.

Lo que sí se lee y se revisa son las fuentes, que son las de la tabla de arriba. El sembrado se
regenera cuando haga falta.

Los `*.dc.html` piden un `./support.js` que **no está aquí**: lo aporta el editor al empaquetar.
Abrir un `.dc.html` suelto en el navegador enseña el boceto sin las guías del lienzo, y es normal.

## Cómo se regenera el lienzo

1. Retoca la fuente: el `.dc.html` de la pantalla, o `canvas.json` si lo que cambia es la
   colocación o una nota.
2. Vuelve a sembrar el lienzo con la *skill* `design` de Claude Code (`/design`), dándole este
   directorio: toma los `*.dc.html` y el `canvas.json`, y publica el lienzo.
3. Eso reescribe `gestion-de-eventos.html` y publica una versión nueva en la URL de siempre, que
   no cambia.

También se puede editar a mano en el propio lienzo publicado y darle a **Guardar**: entonces la
versión buena está allí y **no aquí**. Si eso pasa, exporta las fuentes de vuelta a este directorio
antes de tocar nada en local, o el siguiente sembrado se llevará por delante lo que se editó.
