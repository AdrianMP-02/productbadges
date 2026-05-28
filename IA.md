# Uso de IA en este proyecto

## 1. Herramientas utilizadas

| Herramienta | Versión / Modelo | Modo de uso | Aprox. % del trabajo |
|---|---|---|---|
| Claude Code CLI | claude-sonnet-4-6 | Terminal integrada, sesiones de trabajo continuas | 95% |
| Ninguna (manual) | — | Pruebas en el navegador y verificación en el Back Office de PrestaShop | 5% |

---

## 2. Configuración del proyecto

### CLAUDE.md / AGENTS.md

Sí. El archivo vive en la raíz del repo: [`CLAUDE.md`](./CLAUDE.md).

Contiene: comandos de deploy/reinstall, mapa de arquitectura, esquema de base de datos, gotchas del módulo (bugs multilang, alias de join en `fields_list`, widget de asignación fuera del `<form>`), tabla de hooks registrados, configuraciones disponibles, endpoints AJAX y las modificaciones manuales necesarias en el tema Classic.

Se fue actualizando a lo largo del desarrollo: cada bug relevante que aparecía se añadía como gotcha para que futuras sesiones no repitieran el error.

### settings.json u otra configuración equivalente

Existe `.claude/settings.local.json` con permisos granulares para comandos Bash y PowerShell frecuentes (búsquedas en el repo y en la instalación de PS). Sin cambios de modelo ni herramientas bloqueadas.

Ruta: [`.claude/settings.local.json`](./.claude/settings.local.json)

---

## 3. Skills personalizadas

| Nombre | Origen | Uso en este proyecto | Ruta |
|---|---|---|---|
| `claude-md-management:claude-md-improver` | Comunidad (claude-plugins-official) | Auditar y mejorar el CLAUDE.md tras varias sesiones de desarrollo | `~/.claude/plugins/cache/claude-plugins-official/claude-md-management/` |

---

## 4. Slash commands personalizados

Ninguno creado específicamente para este proyecto. Se usaron los comandos built-in de Claude Code (`/plan`, `/clear`, `/usage`) y el skill invocado con `/claude-md-management:claude-md-improver`.

---

## 5. Sub-agentes invocados

**Plan Mode** (`/plan`) se usó para todas las tareas no triviales:
- Diseño inicial de la arquitectura del módulo
- Planificación del sistema de display en listings vs. ficha de producto
- Verificación y corrección de las 4 opciones de configuración
- Auditoría de seguridad y calidad de código
- Reestructuración del repositorio

En cada plan, Claude Code lanzaba **sub-agentes de tipo `Explore`** (lectura del codebase) y **`Plan`** (diseño de la implementación) antes de proponer cambios. El desarrollador aprobaba o rechazaba el plan antes de ejecutar.

No se guardaron definiciones de agentes en `.claude/agents/` — los agentes se invocaban de forma efímera dentro de cada sesión.

---

## 6. MCPs (Model Context Protocol)

| MCP | Para qué lo usaste | ¿Qué te aportó? |
|---|---|---|
| `context7` | Disponible pero no utilizado activamente | Hubiera sido útil para verificar la existencia del hook `displayProductCoverImage` antes de intentar usarlo — habría evitado el ciclo implementar → probar → revertir |
| `filesystem` | No utilizado (Claude Code ya tiene herramientas Read/Write/Glob/Grep nativas) | — |
| Google Drive | No utilizado | — |

Con más tiempo, habría usado `context7` para consultar la lista de hooks disponibles en PS 1.7.8 y los cambios entre versiones menores.

---

## 7. Prompts importantes

### Prompt 1
- **Herramienta:** Claude Code CLI
- **Prompt:** *"Crear módulo PrestaShop 1.7 productbadges — etiquetas visuales reutilizables sobre imágenes de producto"*
- **Qué generó:** Scaffold completo del módulo: `productbadges.php`, `ProductBadge.php` (ObjectModel multilang), `AdminProductBadgesController.php` con CRUD, templates Smarty, CSS y JS de overlay, `config.xml` y `logo.png`.
- **Qué hice con el output:** Acepté la estructura general; detecté y corregí en iteraciones posteriores los bugs de visibilidad de `processSave()` y la corrupción del campo multilang.

### Prompt 2
- **Herramienta:** Claude Code CLI
- **Prompt:** *"Mostrar las badges en frontend sobre la imagen del producto en: a. Listado de categoría, b. Resultados de búsqueda y home, c. Ficha del producto"*
- **Qué generó:** Plan con dos enfoques: CSS puro para listings (`displayProductListingAction`) y JS de reposicionamiento para ficha (`displayProductAdditionalInfo`). Implementó templates, CSS con flex columns y `productbadges_front.js`.
- **Qué hice con el output:** Acepté el plan. Tras probarlo detecté que los badges no aparecían en listings — Claude diagnosticó que el Classic theme no llama al hook por defecto y añadió la línea `{hook h='displayProductListingAction' product=$product}` al template del tema.

### Prompt 3
- **Herramienta:** Claude Code CLI
- **Prompt:** *"Ahora la badge ha dejado de aparecer"* (tras intentar usar `displayProductCoverImage`)
- **Qué generó:** Diagnóstico de que el hook `displayProductCoverImage` no existe en PS 1.7.x. Reversión completa al enfoque `displayProductAdditionalInfo` + JS.
- **Qué hice con el output:** Acepté la reversión. Fue un ciclo costoso de ~30 min provocado por un hook inventado.

### Prompt 4
- **Herramienta:** Claude Code CLI
- **Prompt:** *"Hay que verificar que el módulo tenga esto: a. Activar/desactivar global, b. Mostrar en listados (sí/no), c. Mostrar en ficha de producto (sí/no), d. Número máximo de badges visibles por producto"*
- **Qué generó:** Auditoría de las 4 opciones — todas implementadas. Detectó el bug donde `max(1, ...)` impedía guardar `0` (ilimitado) a pesar de que la descripción del campo lo indicaba como válido.
- **Qué hice con el output:** Acepté el fix (`max(0, ...)`).

### Prompt 5
- **Herramienta:** Claude Code CLI
- **Prompt:** *"Se valora: sanitización y escapado correcto de inputs, validación server-side, uso correcto de las APIs de PrestaShop, carga eficiente de assets"*
- **Qué generó:** Auditoría completa de seguridad. Identificó 3 mejoras: carga condicional del JS, validación `Validate::isLoadedObject` en endpoints AJAX, `parseInt` defensivo en el JS de la plantilla admin.
- **Qué hice con el output:** Acepté los 3 cambios. En la primera iteración el JS quedó demasiado restringido (solo página de producto) rompiendo el quick view — lo corregí en el prompt siguiente.

### Prompt 6
- **Herramienta:** Claude Code CLI
- **Prompt:** *"al cargar solo en product, en la home no carga, por lo que pierde la badge en el resto de sitios"*
- **Qué generó:** Diagnóstico de que el JS también es necesario en páginas de listado para el quick view. Cambió la condición de `php_self === 'product'` a `PRODUCTBADGES_SHOW_PRODUCT` activo.
- **Qué hice con el output:** Acepté el fix.

### Prompt 7
- **Herramienta:** Claude Code CLI
- **Prompt:** *"Esperamos que tu repo tenga esta estructura interna: modules/productbadges/, sql/, translations/, README.md, IA.md..."*
- **Qué generó:** Reestructuración completa del repo con `git mv` (preservando historial), extracción del SQL a `sql/install.php` y `sql/uninstall.php`, nuevo `README.md` con decisiones técnicas y exclusiones, `.gitignore`.
- **Qué hice con el output:** Acepté todo. Revisé que `.claude/` no estuviera en `.gitignore`.

---

## 8. Errores de la IA que detecté

### Error 1 — Hook `displayProductCoverImage` inexistente
- **Qué generó la IA:** Propuso reemplazar `displayProductAdditionalInfo` por `displayProductCoverImage` argumentando que estaba disponible desde PS 1.7.7.
- **Por qué estaba mal:** El hook no existe en PS 1.7.x. No dispara nunca. Los badges quedaron completamente ocultos sin ningún error.
- **Cómo lo corregiste:** Probé en el navegador, los badges desaparecieron. Informé a Claude, que diagnosticó el problema y revirtió a `displayProductAdditionalInfo` + JS.

### Error 2 — JS cargando solo en `php_self === 'product'`
- **Qué generó la IA:** Para "optimizar" la carga de assets, restringió el JS a páginas de producto únicamente.
- **Por qué estaba mal:** `productbadges_front.js` también gestiona el quick view, que puede abrirse desde home, categorías y búsqueda. En esas páginas el badge quedaba oculto (`display:none`) sin que el JS lo reposicionara.
- **Cómo lo corregiste:** Al probar el quick view desde la home los badges no aparecían. Informé a Claude con el mensaje exacto del síntoma; diagnosticó el problema y cambió la condición a `PRODUCTBADGES_SHOW_PRODUCT`.

### Error 3 — `$label = ''` como valor por defecto en el ObjectModel
- **Qué generó la IA:** Declaró `public $label = '';` en `ProductBadge`.
- **Por qué estaba mal:** `copyFromPost()` de PS asigna `$obj->label[$idLang] = '...'`. Si `$label` ya es un string, PHP lo trata como offset de array sobre un string, corrompiendo el valor silenciosamente.
- **Cómo lo corregiste:** El bug se manifestó como labels vacíos al guardar. Claude diagnosticó la causa (gotcha documentado en la PS community) y eliminó el valor por defecto.

### Error 4 — Classic theme no llama `displayProductListingAction`
- **Qué generó la IA (por omisión):** Asumió que el hook se renderizaba automáticamente en el miniature del tema.
- **Por qué estaba mal:** El template `product.tpl` del Classic theme instalado no incluye `{hook h='displayProductListingAction'}`. La salida del hook se generaba pero era descartada.
- **Cómo lo corregiste:** Los badges no aparecían en listings. Claude leyó el template real del tema instalado, confirmó que faltaba la llamada y la añadió directamente en `product.tpl`.

---

## 9. Partes que NO usé IA

- **Pruebas manuales en el navegador**: navegar por categorías, abrir quick view, cambiar idioma de la tienda, verificar que las badges aparecen y se posicionan correctamente. La IA no puede hacer esto.
- **Verificación en el Back Office de PrestaShop**: instalar/desinstalar el módulo, crear badges de prueba, asignar productos, comprobar que el formulario muestra pestañas de idioma.
- **Decisión de scope**: qué features incluir y cuáles dejar fuera fue decisión mía (sin fechas de validez, sin restricción por categoría, sin drag & drop).

---

## 10. Reflexión final

**¿Qué te ahorró la IA?**  
Fundamentalmente el tiempo de recordar y escribir la API de PS 1.7: `ObjectModel`, `HelperList`, `HelperForm`, el sistema de hooks, `copyFromPost()`, `Validate::isLoadedObject()`. Sin IA hubiera necesitado tener la documentación abierta constantemente. También me ahorró estructurar la CSS de overlay con flexbox y escribir el JS de reposicionamiento del DOM.

**¿En qué te entorpeció o te llevó por mal camino?**  
El caso del hook `displayProductCoverImage` es el ejemplo más claro: la IA generó una solución elegante y bien razonada para un hook que no existe. Sin comprobación en la base de código real de PS, hubiera entregado un módulo con badges invisibles en la ficha de producto. La IA también sobreoptimizó la carga de JS de forma incorrecta, rompiendo el quick view.

**¿Qué cambiarías de tu flujo con IA si lo repitieras?**  
Conectaría `context7` desde el inicio para verificar la existencia de hooks antes de implementarlos — la documentación oficial de PS es la fuente de verdad, no el conocimiento de entrenamiento del modelo. También añadiría un paso de "verificar en el código fuente de PS instalado" antes de cada propuesta de hook nuevo.
