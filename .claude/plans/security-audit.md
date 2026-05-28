# Plan: Auditoría de seguridad y calidad de código

## Context

Auditoría completa del módulo contra los criterios: sanitización/escapado, validación server-side, uso correcto de APIs de PrestaShop, y carga eficiente de assets. La mayoría del código ya es correcto; se identifican **3 mejoras concretas**.

---

## Resultado de la auditoría

### Lo que ya está bien ✅
- Todos los inputs de BD usan `(int)` cast o `pSQL()` (LIKE queries en `SearchProducts`)
- Templates con `|escape:'html'` en todas las variables dinámicas
- `Db::getInstance()->insert()` con arrays (PS escapa internamente)
- `_DB_PREFIX_` usado en todas las queries
- Validadores en `$definition`: `isColor` para colores, `isGenericName` para label, `isUnsignedInt`, `isBool`
- Sin uso directo de `$_GET`/`$_POST` — siempre `Tools::getValue()`
- `$this->l()` en todos los strings del admin
- AJAX endpoints con checks de parámetros nulos
- JS: `.text()` para nombres de producto (auto-escape), `parseInt()` para IDs numéricos

### Problemas encontrados

---

## Cambio 1 — Carga condicional del JS (asset efficiency)

**Archivo:** `productbadges.php` · `hookDisplayHeader()`

**Problema:** El JS se cargaba en todas las páginas sin ninguna condición. La optimización correcta no es restringirlo a `php_self === 'product'`, porque `productbadges_front.js` también gestiona el **quick view** (escucha `shown.bs.modal`, `quickview-opened`, `quickview-loaded`) — y el quick view puede abrirse desde home, categorías y búsqueda.

La única optimización segura es omitir el JS cuando `PRODUCTBADGES_SHOW_PRODUCT` está desactivado, ya que en ese caso nunca habrá un `.productbadges-wrapper--product-page` en el DOM.

**Fix:**
```php
$this->context->controller->addCSS($this->_path . 'views/css/productbadges.css');

// JS handles product page + quick view from any listing page — skip only if product badges disabled
if (Configuration::get('PRODUCTBADGES_SHOW_PRODUCT')) {
    $this->context->controller->addJS($this->_path . 'views/js/productbadges_front.js');
}
```

---

## Cambio 2 — Validar existencia del badge en endpoints AJAX

**Archivo:** `controllers/admin/AdminProductBadgesController.php` · `ajaxProcessAddProduct()` y `ajaxProcessRemoveProduct()`

**Problema:** Ambos endpoints comprueban que `$idBadge` y `$idProduct` sean `> 0`, pero no verifican que el badge exista en BD. Un `$idBadge` inventado pasaría la validación y generaría filas huérfanas en `ps_productbadges_product`.

**Fix — añadir tras la comprobación de nulos, en ambos métodos:**
```php
$badge = new ProductBadge($idBadge);
if (!Validate::isLoadedObject($badge)) {
    $this->ajaxDie(json_encode(['error' => true, 'message' => 'Badge not found']));
}
```

---

## Cambio 3 — `parseInt` defensivo en id de fila dinámica

**Archivo:** `views/templates/admin/product_assign.tpl` · JS inline ~línea 165

**Problema:** Al añadir un producto vía AJAX, el id del `<tr>` se construye con `resp.id_product` sin conversión explícita:
```js
var $row = $('<tr id="pb-product-row-' + resp.id_product + '"></tr>');
```
El servidor siempre devuelve un entero, pero la defensa explícita evita corrupción si algún día la respuesta cambia.

**Fix:**
```js
var $row = $('<tr id="pb-product-row-' + parseInt(resp.id_product, 10) + '"></tr>');
```

---

## Archivos críticos

| Archivo | Cambio |
|---|---|
| `productbadges.php` | Añadir condición `php_self === 'product'` antes de `addJS` |
| `controllers/admin/AdminProductBadgesController.php` | Añadir `Validate::isLoadedObject` en `ajaxProcessAddProduct` y `ajaxProcessRemoveProduct` |
| `views/templates/admin/product_assign.tpl` | `parseInt(resp.id_product, 10)` en la construcción del `<tr>` |

---

## Verificación

1. Abrir la home, una categoría y una búsqueda → verificar con DevTools Network que `productbadges_front.js` **no** se carga
2. Abrir la ficha de un producto → verificar que `productbadges_front.js` **sí** se carga
3. Desde la consola del Back Office, hacer una petición AJAX a `AddProduct` con un `id_badge=99999` inexistente → debe devolver `{"error":true,"message":"Badge not found"}`
4. Añadir un producto real a una badge → la fila se añade sin errores en consola
