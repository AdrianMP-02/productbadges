# productbadges — PrestaShop 1.7 Module

Módulo PrestaShop 1.7 para mostrar badges (etiquetas) visuales superpuestas sobre las imágenes de producto en listados, búsqueda, home y ficha de producto.

---

## Instalación

### Requisitos

- PrestaShop 1.7.7 – 1.7.8.x
- PHP 7.4 o 8.1
- MySQL 5.7 / MariaDB 10.4
- Sin dependencias Composer ni npm — cero build step

### Pasos

1. Copiar la carpeta `modules/productbadges/` en `<prestashop>/modules/`.
2. Back Office → **Modules → Module Manager** → buscar *Product Badges* → **Instalar**.
3. *(Sólo Classic theme)* Añadir la llamada al hook en el template de miniatura del producto:

   **Archivo:** `themes/classic/templates/catalog/_partials/miniatures/product.tpl`  
   Insertar tras el cierre del bloque `product_thumbnail`:

   ```smarty
   {hook h='displayProductListingAction' product=$product}
   ```

   > Este paso es necesario porque el Classic theme no incluye esta llamada por defecto.  
   > La línea se pierde al actualizar el tema — re-aplicar tras cada actualización.

4. Gestionar badges en Back Office → **Catálogo → Product Badges**.

---

## Decisiones técnicas

### Arquitectura general

El módulo sigue la estructura estándar de módulos PrestaShop 1.7:

- **`ProductBadge`** extiende `ObjectModel` con soporte multilang nativo. La tabla `_lang` la genera PS automáticamente a partir de `$definition['multilang'] = true`.
- **`AdminProductBadgesController`** extiende `ModuleAdminController` para aprovechar `HelperList` y `HelperForm` sin reinventar paginación, ordenación ni CSRF.
- Las queries de instalación viven en `sql/install.php` y `sql/uninstall.php` (arrays devueltos con `return`) para mantener `productbadges.php` limpio.

### Display en listings vs. ficha de producto

| Contexto | Hook | Técnica de posicionado |
|---|---|---|
| Categorías, búsqueda, home | `displayProductListingAction` | CSS `position:absolute` dentro de `.thumbnail-container` |
| Ficha de producto | `displayProductAdditionalInfo` | JS mueve el wrapper a `.product-cover` tras el DOM load |
| Quick view | (mismo hook) | JS reutilizado via eventos `shown.bs.modal` / `quickview-loaded` |

El hook `displayProductCoverImage` fue evaluado y descartado: **no existe en PS 1.7.x** a pesar de lo que indica documentación de terceros.

### Carga de assets

- **CSS** carga en todas las páginas del front (necesario para listings y quick view desde cualquier página).
- **JS** (`productbadges_front.js`) carga solo cuando `PRODUCTBADGES_SHOW_PRODUCT` está activo, ya que es el único caso donde el wrapper `--product-page` existe en el DOM.

### Seguridad

- Todas las variables en SQL usan `(int)` cast o `pSQL()` para LIKE.
- Los endpoints AJAX validan existencia del badge con `Validate::isLoadedObject` antes de operar.
- Templates con `|escape:'html'` en todos los outputs dinámicos.
- Sin uso de `$_GET`/`$_POST` directamente — siempre `Tools::getValue()`.

---

## Qué se dejó fuera y por qué

| Funcionalidad | Motivo de exclusión |
|---|---|
| Fechas de validez (badge activa entre X e Y fecha) | Fuera del scope del enunciado; añadible con dos columnas `date_from`/`date_to` en la tabla |
| Restricción por categoría | Complejidad N:M adicional; el caso de uso básico es por producto |
| Ordenación de badges por drag & drop | Requiere campo `sort_order` y JS de reordenación; añadible sin cambios de arquitectura |
| Tests automatizados | PS 1.7 no tiene test runner estándar para módulos; las comprobaciones son manuales |
| Traducción de strings de admin (`.php` en `translations/`) | El directorio está creado; los strings usan `$this->l()` y PS los genera automáticamente al usar la herramienta de traducción del Back Office |

---

## Estructura del repositorio

```
modules/
  productbadges/
    productbadges.php            # Entrada del módulo: install/uninstall/hooks/config
    config.xml                   # Manifiesto de caché PS
    logo.png
    sql/
      install.php                # CREATE TABLE statements
      uninstall.php              # DROP TABLE statements
    classes/
      ProductBadge.php           # ObjectModel multilang + queries estáticas
    controllers/admin/
      AdminProductBadgesController.php
    views/
      css/productbadges.css
      js/productbadges_front.js  # Reposiciona badge en .product-cover (product page + quick view)
      js/productbadges_admin.js  # Color picker en el formulario admin
      templates/hook/
        product_badges.tpl       # Overlay en listings
        product_page_badges.tpl  # Overlay en ficha de producto
      templates/admin/
        product_assign.tpl       # Widget de asignación de productos
    translations/                # Vacío; PS genera los archivos con su herramienta de traducción
.claude/                         # Configuración de Claude Code (historial de sesión, CLAUDE.md)
README.md
IA.md
.gitignore
```
