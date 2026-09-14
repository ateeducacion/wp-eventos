---
id: ADR-0011
title: "Integración continua y política de pruebas"
status: Aceptada
date: 2026-09-12
related:
  issues: []
  prs: []
  sdds: [SDD-0002]
  adrs: [ADR-0001, ADR-0002]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0011: Integración continua y política de pruebas

## Estado

Aceptada (2026-09-12).

## Contexto

El repositorio nace con los cinco workflows y la configuración de cobertura
ya adaptados de otro repositorio de la casa:

| Fichero | Qué es |
|---|---|
| `.github/workflows/ci.yml` | Los dos jobs que tumban un PR: `lint` y `test` |
| `.github/workflows/phpmd.yml` | Análisis de complejidad a Code Scanning, semanal y por PR |
| `.github/workflows/cancelar-al-cerrar.yml` | Cancela lo que quede en marcha cuando el PR se cierra |
| `.github/workflows/update-agent-skills.yml` | Actualiza `.agents/skills` los lunes y abre PR |
| `.github/dependabot.yml` | Actualizaciones semanales de acciones, Composer y npm |
| `codecov.yml` | Estados de cobertura de proyecto y de parche |

Lo que falta por decidir no es la mecánica —está copiada y funciona en el
otro repositorio— sino la política: qué exige la CI, con qué dureza y qué
se queda a propósito sin probar.

Hay dos condicionantes propios de este proyecto:

1. **El repositorio está vacío.** Las primeras entregas son andamiaje —
   registrar tres tipos de contenido, tres taxonomías, el arranque — y su
   cobertura mide cuánto código de declaración lleva el parche, no cuánta
   lógica se ha probado.
2. **El bundle no se puede cargar en los tests.** `tests/bootstrap.php` carga
   los módulos sueltos de `src/Evt/` y se salta `*.bundle.php` (`AGENTS.md`,
   sección «Añadir código al aplicativo»). Cargar las dos copias en el
   mismo proceso sería «Cannot redeclare class», y por eso mismo
   `declare(strict_types=1)` está prohibido: es legal por fichero y fatal
   al concatenar.

## Problema

¿Qué corre en cada pull request, con qué listón de cobertura, dónde corre y
qué queda deliberadamente sin probar?

## Factores de decisión

- Un listón que no se pueda cumplir se salta o se falsea; un listón que no
  exija nada no defiende nada.
- Un PR en rojo por algo que no es un fallo del código enseña a ignorar el
  rojo.
- Los tests necesitan un WordPress vivo (wp-env sobre Docker), así que el job
  de test no es barato ni instantáneo.
- Dónde corren los jobs puede cambiar por motivos administrativos ajenos al
  proyecto, y cambiarlo no debería ser un PR.
- El artefacto que llega a producción es el bundle, y es justo lo que los
  tests no cargan.

## Alternativas consideradas

### El suelo de cobertura del parche

#### Opción A: 90 % desde el primer día

El listón alto de un repositorio maduro: lo que se toca en un PR va con sus
tests, y con el 90 % no pasa nada sin ellos.

- Pros: la regla es la misma desde el principio y nadie tiene que acordarse
  de subirla.
- Contras: en un repositorio vacío el primer PR que registre un CPT no puede
  llegar al 90 % sin tests de adorno, y eso es lo que se aprendería a
  escribir. Un suelo así es **el resultado de un camino** —se empieza bajo y
  se sube conforme la cobertura del proyecto se estabiliza—, no el punto de
  partida. Ponerlo el primer día es copiar el destino sin el camino.

#### Opción B: sin estado bloqueante, solo informativo

- Pros: nunca tumba un PR.
- Contras: no defiende nada; la cobertura pasa a ser un número que se mira o
  no. Descartada.

#### Opción C: 10 % que sube cuando la casa esté en orden

- Pros: obliga a que un parche traiga *algo* de prueba sin obligar a
  inventarlas; se sube cuando el aplicativo tenga pantallas y la cobertura del
  proyecto se estabilice.
- Contras: durante los primeros meses el listón casi no filtra, y subirlo
  depende de que alguien se acuerde.

### Dónde corren los jobs

#### Opción A: `runs-on: ubuntu-latest` fijo

- Contras: si la facturación de la organización se bloquea, GitHub no arranca
  los jobs alojados y hay que tocar cinco ficheros YAML para moverlos.

#### Opción B: `runs-on: self-hosted` fijo

- Contras: lo mismo al revés, y además el repositorio deja de poder correr en
  un fork.

#### Opción C: una variable de repositorio

`runs-on: ${{ vars.EVT_RUNNER || 'ubuntu-latest' }}`.

- Pros: cambiar de sitio es cambiar una variable en la interfaz de GitHub, sin
  PR, sin revisión y sin tocar YAML. Sin la variable se usan los runners de
  GitHub, que es lo que quiere un fork.
- Contras: el mismo workflow tiene que servir para los dos entornos, y por eso
  los pasos de PHP van duplicados con `if: runner.environment == 'github-hosted'`.

## Decisión

**Haremos la opción C en los dos ejes.**

### Qué corre en cada pull request

| Job | Qué comprueba | ¿Tumba el PR? |
|---|---|---|
| `ci / lint` | `composer validate --no-check-publish`; PHPCS con WPCS (`composer lint`, `ci.yml:93`); `make check-provision` (`:96`); `php -l` de todo `*.php` fuera de `vendor/` (`:98`); `jq empty` de todo el JSON versionado (`:104`) | **Sí** |
| `ci / test` | wp-env arrancado (`:160`), `make provision` (`:163`) y `make coverage` (`:166`): PHPUnit sobre un WordPress vivo | **Sí** |
| Codecov, estado `patch` | Suelo del 10 % sobre las líneas que toca el parche (`codecov.yml:34`) | **Sí** |
| Codecov, estado `project` | Total del repositorio (`codecov.yml:31`, `informational: true`) | No |
| `PHPMD` | Complejidad y código sospechoso, en SARIF a Code Scanning | No (`continue-on-error`) |
| `Cancelar al cerrar` | Cancela ejecuciones huérfanas al cerrar el PR | No |

Los dos jobs de `ci.yml` van con `timeout-minutes` (10 y 20, `:42` y `:115`) y
el grupo de `concurrency` es el número de PR, de modo que un empujón nuevo
cancela el anterior en vez de ocupar runner para nada.

**Lo que no dispara CI**: cambios que solo tocan `**.md`, `docs/**` o
`.github/*.md` (`ci.yml:5-8` y `:12-16`). Esta ADR, por ejemplo, no arranca
ningún runner.

### El suelo de cobertura

El estado bloqueante es el del parche, no el del proyecto: lo que se toca en
un PR va con sus tests, y el total es informativo. Arranca en el 10 %
(`codecov.yml:34`) por lo que dice el comentario del propio fichero
(`codecov.yml:4-11`), y **sube en un PR propio que diga por qué**, cuando el
aplicativo tenga sus pantallas y el total del repositorio se haya
estabilizado.

`src/Evt/App.php` está excluido de la medición (`codecov.yml:24-25`): corre en
el arranque, antes de que PHPUnit empiece a medir, y es idempotente. Lo que
hace sí se prueba, comprobando que el arranque dejó enganchado lo que
sostiene el aplicativo.

### Qué NO se prueba

- **El bundle.** Los tests cargan `src/Evt/` y se saltan `snippets/*.bundle.php`.
  La red de seguridad del bundle no es un test: es el `php -l` que
  `build/pack-snippet.php` ejecuta sobre el fichero recién escrito y que aborta
  la construcción si falla, más `make snippet-check` con wp-env arrancado. Eso
  comprueba **sintaxis, no comportamiento**: un bundle que se cargue mal en
  Code Snippets pasaría `php -l` sin protestar. Se acepta a sabiendas, porque
  la alternativa —cargar bundle y módulos en el mismo proceso— no es posible.
- **El gestor de formularios del sistema anterior.** No se instala en el
  entorno de test. El aplicativo no depende de él
  ([ADR-0007](ADR-0007-las-inscripciones-siguen-en-el-sistema-anterior.md)), y lo que sí
  —el formulario de inscripción— vive en producción, no aquí.
- **El navegador.** `playwright` está en las dependencias de desarrollo de `package.json` pero no hay nada que
  ejecutar todavía.
- **Producción.** Ningún workflow habla con el sitio donde se despliega. La CI
  no puede detectar que un snippet desplegado difiera del repositorio.

## Consecuencias

### Positivas

- Cada PR pasa exactamente el mismo lint que la máquina de quien lo escribió:
  CI llama a `composer lint` y a los targets del Makefile, no a binarios con
  otras opciones. Lo mismo hace PHPMD, que invoca `make phpmd` para que las
  exclusiones sean las mismas.
- El listón bajo no bloquea el andamiaje inicial, que es la fase en la que un
  listón alto solo produce tests de adorno.
- Mover los jobs entre runners de GitHub y propios es una variable, no un PR.
- La documentación no gasta runner, y una ADR se puede corregir sin esperar
  veinte minutos.

### Negativas

- **El 10 % es casi ningún listón.** Durante los primeros PR la cobertura es
  un dato, no una garantía, y nada obliga a subirlo: el riesgo real es que se
  quede en el 10 % indefinidamente porque nunca hay un buen momento. La ADR lo
  dice pero no lo impide.
- **La CI está escrita antes que lo que ejecuta.** A 2026-09-12 el repositorio
  no tiene `Makefile`, ni `tests/`, ni `build/`, ni `scripts/`: los pasos
  `make check-provision`, `make provision` y `make coverage` fallarían hoy
  mismo. El primer PR que abra CI tendrá que traerlos.
- El job `test` no es barato: necesita Docker, la descarga de wp-env y una
  provisión completa. En un runner propio hay que pararlo al acabar
  (`ci.yml:188`, con `always()`) o los contenedores se quedan corriendo,
  ocupando los puertos 8798 y 8799 y arrastrando la base de datos de la
  ejecución anterior.
- **PHPMD no tumba nada.** Es un informe que se puede ignorar
  indefinidamente, y probablemente se ignore.
- Codecov necesita el secreto `CODECOV_TOKEN`; en un PR desde un fork el paso
  se salta entero (`ci.yml:169-171`), así que un fork no tiene estado de
  cobertura. Con `fail_ci_if_error: true` (`:179`) una subida fallida sí se ve,
  que es la lección de un primer intento que
  pasó en verde con el token vacío y sin informe.

### Neutras

- Dependabot abre PR semanales de acciones, Composer y npm; cada uno pasa por
  la misma CI.
- `update-agent-skills.yml` corre los lunes y no toca código del aplicativo.
- La política de qué merece una ADR y qué no la fija
  [docs/adr/README.md](README.md); la CI no la comprueba.
