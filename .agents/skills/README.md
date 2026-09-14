# Skills de agentes

Las copias canónicas de las skills viven aquí. Claude Code las ve en
`.claude/skills/` como **enlaces** a estas carpetas; `.gitignore` ignora los
directorios reales que aparezcan allí (`.claude/skills/*/`), de modo que lo que
se versiona es siempre esta copia y nunca la otra.

Este repositorio arranca **sin ninguna skill**, a propósito: una skill copiada y
no usada envejece en silencio y acaba dando instrucciones falsas. Se añaden
cuando hagan falta.

## Añadir una skill propia

```bash
mkdir -p .agents/skills/<nombre>
$EDITOR .agents/skills/<nombre>/SKILL.md
ln -s ../../.agents/skills/<nombre> .claude/skills/<nombre>
```

## Añadir una de terceros

Se instalan **solo** para Copilot y se enlazan igual. Así `gh skill update`
sigue funcionando y la procedencia queda en el frontmatter del `SKILL.md`
(`metadata.github-repo`, `github-path`, `github-tree-sha`).

```bash
gh skill add WordPress/agent-skills wp-plugin-development --agent github-copilot
ln -s ../../.agents/skills/wp-plugin-development .claude/skills/wp-plugin-development
gh skill update --all
```

No instales `--agent claude-code` ni `--agent grok`: duplicaría el árbol.

Las de terceros van **verbatim**. No las reformatees: divergir de upstream
complica `gh skill update`. El workflow `.github/workflows/update-agent-skills.yml`
las actualiza y abre un PR cada lunes.

## Candidatas, cuando toque

| Skill | Léela antes de | Origen |
|---|---|---|
| `wp-plugin-development` | Hooks, CPT, admin, shortcodes, capacidades, enqueue — **no** empaquetado | [`WordPress/agent-skills`](https://github.com/WordPress/agent-skills) |
| `wp-performance` | Perfilar consultas, listados y frontal | ídem |
| `blueprint` / `wp-playground` | Editar `blueprint*.json` o usar Playground en local | ídem |
| `wp-plugin-security` | Entrada, salida, nonces, capacidades, formularios, ficheros | [`fernandotellado/ai-skills`](https://github.com/fernandotellado/ai-skills) |

Recuerda [ADR-0001](../../docs/adr/ADR-0001-repo-entorno-desarrollo-no-plugin.md):
la arquitectura de este repositorio prevalece sobre lo que recomiende una skill
genérica de WordPress. Aquí no se crea un fichero bootstrap de plugin, ni
cabeceras de plugin, ni `readme.txt`, ni hooks de activación, ni se asume que en
producción existan rutas relativas a un plugin.
