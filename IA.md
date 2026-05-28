# IA.md — Uso de inteligencia artificial en este proyecto

## Herramienta utilizada

**Claude Code** (Anthropic) — CLI interactivo con el modelo `claude-sonnet-4-6`.  
Configuración de sesión disponible en `.claude/`.

---

## Cómo se usó

El módulo fue desarrollado íntegramente en sesiones de Claude Code, con el desarrollador en rol de revisor y tomador de decisiones. El flujo fue:

1. **Planificación**: cada tarea se planificó en modo `/plan` antes de ejecutar. Los planes se guardaron en `.claude/plans/`.
2. **Implementación**: Claude generó los archivos PHP, Smarty y CSS; el desarrollador revisó y aprobó cada cambio antes de aplicarlo.
3. **Debugging**: los errores encontrados durante las pruebas manuales (PHP Fatal, badges no visibles, AJAX sin persistencia) se reportaron a Claude, que diagnosticó y aplicó los fixes.
4. **Auditoría de seguridad**: se pidió explícitamente una revisión de sanitización, validación server-side, uso de APIs de PS y carga de assets.

---

## Decisiones tomadas por el desarrollador (no por la IA)

- Elección de PrestaShop 1.7.8 como versión objetivo.
- Diseño de la estructura de tablas (N:M badge ↔ producto).
- Decisión de no implementar fechas de validez ni restricción por categoría en esta versión.
- Aprobación o rechazo de cada plan antes de ejecutarlo.
- Pruebas manuales en el entorno local (`D:/Proyectos/Prestashop`).

---

## Errores notables detectados por la IA y corregidos

| Error | Causa | Fix |
|---|---|---|
| `ProductBadge::$label = ''` corrompía multilang | `copyFromPost()` trata strings como offset arrays | Eliminado el valor por defecto |
| `processSave()` fatal error | PS declara el método `public`; override debe ser `public` | Cambiada la visibilidad |
| Badge no aparecía en listings | Classic theme no llama `displayProductListingAction` en su miniature template | Añadida la llamada `{hook}` manualmente |
| `displayProductCoverImage` silenciaba badges | El hook no existe en PS 1.7.x | Revertido a `displayProductAdditionalInfo` + JS |
| JS cargaba solo en ficha de producto | Quick view desde listings también necesita el JS | Condición cambiada a `PRODUCTBADGES_SHOW_PRODUCT` |

---

## Archivos generados total o parcialmente por IA

Todos los archivos del módulo fueron generados por Claude Code bajo supervisión del desarrollador:

- `modules/productbadges/productbadges.php`
- `modules/productbadges/classes/ProductBadge.php`
- `modules/productbadges/controllers/admin/AdminProductBadgesController.php`
- `modules/productbadges/views/**`
- `modules/productbadges/sql/install.php`
- `modules/productbadges/sql/uninstall.php`
- `CLAUDE.md` (documentación para el contexto de Claude Code)
- Este archivo (`IA.md`)
