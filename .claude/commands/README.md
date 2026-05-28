# Slash commands usados en este proyecto

No se crearon slash commands personalizados para este proyecto.

## Built-in commands utilizados

| Comando | Para qué se usó |
|---|---|
| `/plan` | Planificar cada tarea antes de implementarla. Activa Plan Mode: Claude propone un plan, el desarrollador lo aprueba antes de ejecutar. |
| `/clear` | Limpiar el contexto de conversación entre tareas. |
| `/usage` | Consultar uso de tokens de la sesión. |
| `/claude-md-management:claude-md-improver` | Auditar y mejorar el CLAUDE.md tras varias sesiones de desarrollo. |

## Flujo de trabajo con Plan Mode

Cada tarea no trivial siguió este ciclo:

```
/plan → [Claude explora el código] → [Claude propone plan]
     → [Desarrollador aprueba] → [Claude implementa]
     → [Desarrollador prueba en el navegador]
     → [Si hay bug: reportar → diagnóstico → fix]
```

Los planes generados se guardan en `.claude/plans/`.
