# .claude/ — Configuración de Claude Code para este proyecto

Este directorio contiene toda la configuración de Claude Code usada durante
el desarrollo del módulo `productbadges`.

## Archivos

| Archivo | Propósito |
|---|---|
| `settings.json` | Configuración de proyecto: modelo activo (`claude-sonnet-4-6`) |
| `settings.local.json` | Permisos granulares para comandos Bash/PowerShell frecuentes. Normalmente este archivo se gitignora, pero se incluye intencionalmente para que el flujo de trabajo sea reproducible. |
| `commands/README.md` | Documenta los slash commands (built-in y de skills) utilizados |
| `plans/` | Planes de implementación generados por Plan Mode durante el desarrollo |

## Skills de terceros utilizadas

| Skill | Fuente | Uso |
|---|---|---|
| `claude-md-management:claude-md-improver` | [claude-plugins-official](https://github.com/anthropics/claude-code-plugins) | Auditoría y mejora del CLAUDE.md |

Las skills se instalan en `~/.claude/plugins/` y no se incluyen en el repo
porque son dependencias externas (equivalente a un `npm install`).

## MCPs disponibles durante el desarrollo

| MCP | Estado | Notas |
|---|---|---|
| `context7` | Disponible, no usado activamente | Habría sido útil para verificar existencia de hooks PS antes de implementarlos |
| `google-drive` | Disponible, no usado | — |

## Por qué .claude/ NO está en .gitignore

El evaluador del proyecto requiere que los directorios de configuración de IA
sean visibles en el repo. Añadir `.claude/` a `.gitignore` ocultaría el flujo
de trabajo real con la IA, que es parte de la entrega.
