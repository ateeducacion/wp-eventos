---
id: ADR-0034
title: "La rama main solo se mezcla con una revisión y el CI en verde"
status: Aceptada
date: 2026-09-16
related:
  issues: []
  prs: []
  sdds: []
  adrs: [ADR-0011, ADR-0030]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# ADR-0034: `main` solo se mezcla con una revisión y el CI en verde

## Estado

Aceptada (2026-09-16). Implementada como **regla de rama** (*ruleset*) del
repositorio en GitHub —Settings → Rules → Rulesets, «Proteger main: una
revisión y el CI en verde»— y, dentro del repositorio, en el disparador de
`.github/workflows/ci.yml`. La regla vive fuera de git: esta ADR es su
registro escrito, y `gh ruleset check main` la enseña tal cual está.

## Contexto

La guía de quien empieza dice que «nada entra sin pasar por» un PR y que la
regla más importante de todas es que **no se sube nada a `main` por tu
cuenta** (`docs/desarrollo-para-empezar.md`, paso 5 y regla 2 del §6).
`AGENTS.md` se lo dice a los agentes con las mismas palabras: «no propongas ni
ejecutes nada que se salte la revisión». Hasta hoy eso era una costumbre:
`main` no tenía ninguna protección, y cualquiera con permiso de escritura
—varias personas lo tienen de administración— podía hacer `git push origin
main` o pulsar *Merge* en su propio PR con el CI en rojo. Una regla que solo se
sostiene por disciplina se rompe el día que alguien va con prisa, y parte del
equipo trabaja a través de un agente al que hay que decirle que no cada vez.

Lo que comprueba el CI en cada PR (`.github/workflows/ci.yml`) son dos jobs,
`lint` y `test`, y son los únicos que tumban: PHPMD corre con
`--ignore-violations-on-exit` y `continue-on-error`
(`.github/workflows/phpmd.yml`), las capturas y el enlace de Playground son
informativos, y el análisis de CodeQL lo pone GitHub por su cuenta.

`ci.yml` se saltaba los PR que solo tocaban documentación —`paths-ignore` con
`**.md` y `docs/**`— para no gastar minutos de runner en un cambio de texto.

## Problema

¿Cómo se convierte «una revisión de otra persona y el CI en verde» en una
condición que GitHub comprueba antes de mezclar, y no en una costumbre?

## Factores de decisión

- Tiene que valer para todo el mundo, también para quien administra el
  repositorio: si no, la excepción se la queda justo quien más puede.
- Poca fricción para quien no viene de desarrollo: un botón gris con el motivo
  escrito, no un flujo nuevo que aprender.
- Un check obligatorio que un workflow se salta **no se da por pasado**:
  GitHub lo deja en «esperando» y el PR no se puede mezclar nunca.
- Nada de la organización en lo versionado
  ([ADR-0030](ADR-0030-el-repositorio-se-publica-sin-nada-de-nadie.md)): la
  regla se describe, no se copia con sus identificadores.

## Alternativas consideradas

### Opción 1: protección de rama clásica

La pantalla de *Branch protection rules* de siempre, con `enforce_admins`.

- Pros: conocida; para este caso hace lo mismo.
- Contras: GitHub dirige lo nuevo a las reglas de rama, que llevan historial
  de cambios, se consultan desde la línea de órdenes (`gh ruleset`) y se
  exportan como JSON. Descartada.

### Opción 2: regla de rama con excepción para quien administra

La misma regla, con «Repository admin» en la lista de quien puede saltársela.

- Pros: hay salida de emergencia sin tocar la regla.
- Contras: es exactamente la excepción que la guía dice que no existe, y se la
  daría a la mayoría de quienes escriben en el repositorio. Una emergencia de
  verdad se resuelve **editando la regla**, que es un acto explícito y queda
  en su historial, no saltándosela en silencio. Descartada.

### Opción 3: regla de rama sin excepciones

Elegida. Ver la decisión.

### Y los PR de solo documentación

Con `lint` y `test` obligatorios, un PR que solo toca `docs/` no los tendría
nunca y no se podría mezclar. Dos salidas:

- **Un segundo workflow con los mismos nombres de job que responda «verde»
  cuando solo cambia documentación**, con los `paths` inversos. Es el remedio
  que documenta GitHub, pero en un PR que toca documentación **y** código
  corren los dos y hay dos checks con el mismo nombre compitiendo. Descartada.
- **Quitar `paths-ignore` del disparador `pull_request`** y dejarlo solo en el
  `push` a `main`. Un PR de documentación cuesta unos minutos de runner, y a
  cambio no hay dos workflows que mantener a la par. Elegida.

## Decisión

**`main` queda bajo una regla de rama, activa y sin nadie en la lista de
excepciones**, que exige:

- que todo cambio entre por **pull request** con **al menos una aprobación**
  de otra persona; si después de aprobar se sube algo más, la aprobación **se
  descarta** y hay que volver a mirar;
- que los checks **`lint` y `test`** estén en verde y los haya emitido **GitHub
  Actions**: un check con ese nombre desde otra aplicación no cuenta;
- que nadie borre la rama ni reescriba su historia (`force push`).

No se exige que la rama esté al día con `main` antes de mezclar: obligaría a
pulsar «Update branch» y a repetir el CI cada vez que otro PR entra antes, y
con el volumen de PR de este equipo el riesgo de que dos PR compatibles por
separado se rompan juntos es pequeño. Se puede activar más adelante sin tocar
nada más.

Y `ci.yml` corre en **todos** los PR, también en los de solo documentación.

## Consecuencias

### Positivas

- **La regla de la guía deja de ser una costumbre.** El botón de *Merge* sale
  gris, con el motivo, hasta que hay aprobación y CI en verde. Vale igual para
  quien administra.
- Un `git push origin main` por despiste **no entra**, y el mensaje de error
  dice por qué.
- Renombrar `lint` o `test` en `ci.yml` **se nota en el primer PR** en vez de
  dejar la puerta abierta en silencio: el check obligatorio se quedaría
  esperando.

### Negativas

- **Hace falta una segunda persona con permiso de escritura** para mezclar
  cualquier cosa, también un cambio de una línea. Es el precio buscado.
- **La regla vive fuera de git**: un cambio en ella no pasa por PR. Quedan el
  historial de la regla en GitHub y esta ADR como texto de referencia.
- Los PR de solo documentación pasan a gastar unos minutos de runner.
- Los PR de documentación que estuvieran abiertos antes de que entre el cambio
  de `ci.yml` se quedan esperando a `lint` y `test`: hay que subir algo a su
  rama —o pulsar «Update branch»— para que el workflow corra.

### Neutras

- Si algún día un job de `ci.yml` cambia de nombre, hay que cambiar también la
  regla; `AGENTS.md` lo deja apuntado.
- El resto de workflows —PHPMD, capturas, Playground, CodeQL— siguen siendo
  informativos: se ven en el PR, no lo bloquean.


## Adenda — 2026-09-22: actualizaciones de skills

A petición del propietario, los cambios limitados a `.agents/skills/` y
`.claude/skills/` no ejecutan lint, tests, PHPMD, capturas ni previews. El
workflow de CI sigue respondiendo a todos los PR para conservar los checks
obligatorios `lint` y `test`; un job mínimo consulta todos los archivos del PR
y omite ambos jobs únicamente si la lista completa contiene solo skills.
GitHub acepta los jobs omitidos por una condición como checks completados,
a diferencia de un workflow que nunca se inicia por un filtro de rutas.

Los cambios mixtos siguen ejecutando las comprobaciones. Se consideran también
los nombres anteriores de archivos renombrados; si falla la consulta, está
incompleta o el PR cambia mientras se consulta, no se permite omitir el código.
Los filtros de los workflows informativos y del push conservan sus exclusiones
previas. La revisión humana y la regla de rama siguen vigentes.

Referencias: `.github/workflows/ci.yml` y
[GitHub: comprobaciones obligatorias omitidas](https://docs.github.com/en/repositories/configuring-branches-and-merges-in-your-repository/defining-the-mergeability-of-pull-requests/troubleshooting-required-status-checks#handling-skipped-but-required-checks).
Asistencia de IA: Codex (GPT-6).
