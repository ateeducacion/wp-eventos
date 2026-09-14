---
id: GUIA-ESBOZOS
title: "Guía de los esbozos de referencia"
status: Aceptada
date: 2026-09-12
related:
  issues: []
  prs: []
  adrs: []
  sdds: []
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# Esbozos de referencia

Ficheros aportados como **referencia de diseño**, no como fuente de verdad
automática del entorno: maquetas de pantalla, diagramas de flujo, capturas
anotadas y XML de formularios que se consultan al implementar.

| Fichero | Descripción |
|---------|-------------|
| — | Todavía no hay ninguno. |

El directorio está vacío a 2026-09-12 y se deja creado a propósito, para que
el primer esbozo tenga sitio y esta guía se lea antes de dejarlo caer.

## Uso

- Consultar maquetación, campos y flujos al implementar `src/Evt/` y las
  pantallas del escritorio.
- **No** es material de producción. La exportación WXR del sitio de origen, el
  XML del formulario, sus vistas y el inventario de medios viven en `.local/`,
  que **no se versiona** porque lleva datos personales: ver
  `.local/` (investigación del sistema anterior).
- **No** importar nada de aquí a ciegas en un entorno de desarrollo: los
  esbozos dependen de IDs de formulario, IDs de campo y catálogos del sitio de
  origen que aquí no existen o no coinciden.
- Cada subcarpeta con más de un fichero lleva su propio `README.md` que
  explique qué representa y de dónde salió.
