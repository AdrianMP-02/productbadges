# productbadges — PrestaShop 1.7 Module

## Deploy / Install

```bash
# Copy module into a running PS 1.7.8.x installation
cp -r . <prestashop>/modules/productbadges/

# Then install from:
# Back Office → Modules → Module Manager → search "Product Badges" → Install

# When changing registered hooks (e.g. adding/removing a hook in install()):
# uninstall + reinstall from Back Office — registerHook() only runs on install.
# Back Office → Modules → Module Manager → Product Badges → Uninstall → Install
```

No Composer, no npm. Zero build step.

## Architecture

```
productbadges.php                        # Module entry: install/uninstall/hooks/config
classes/ProductBadge.php                 # ObjectModel (multilang) + static query methods
controllers/admin/
  AdminProductBadgesController.php       # CRUD list+form, AJAX add/remove product
views/
  css/productbadges.css                  # Overlay layout (flex columns, not absolute-per-badge)
  js/productbadges_front.js              # Repositions badges into .product-cover via DOM
  js/productbadges_admin.js              # HTML5 color picker swatches
  templates/hook/
    product_badges.tpl                   # Listing overlay (left/right flex columns)
    product_page_badges.tpl              # Product page overlay (same structure)
  templates/admin/
    product_assign.tpl                   # Product search widget + AJAX add/remove
```

## Database

| Table | Key columns |
|---|---|
| `ps_productbadges` | `id_badge`, `bg_color`, `text_color`, `position` (0=left,1=right), `active` |
| `ps_productbadges_lang` | `id_badge`, `id_lang`, `label` |
| `ps_productbadges_product` | `id_badge`, `id_product` (N:M) |

## Gotchas

**Multilang ObjectModel property must NOT have a default string value.**
```php
// WRONG — copyFromPost() does $obj->label[$idLang] = '…' which PHP treats
// as string-offset assignment when $label is already a string, corrupting the value.
public $label = '';

// CORRECT
public $label;
```

**`fields_list` lang alias is `b`, not `bl`.**
PS generates `LEFT JOIN …_lang b ON …` — use `'filter_key' => 'b!label'`.

**Product assignment widget lives outside the `<form>` tag.**
`renderForm()` appends the panel after `parent::renderForm()`, which already contains
`</form>`. Any hidden field in the widget is NOT submitted. All product add/remove
operations must go through AJAX only — never rely on form POST for assignments.

**`processSave()` must be `public`** (not `protected`) — `AdminControllerCore` declares it public.

**Badge overlay position is CSS-only for listings, JS-repositioned for product page.**
`displayProductAdditionalInfo` renders below the image column, not inside it.
`productbadges_front.js` moves `.productbadges-wrapper--product-page` into `.product-cover`
after DOM load and on `shown.bs.modal` / prestashop events for quick view.

**`displayProductListingAction` does NOT fire in Classic theme by default.**
The Classic theme's `product.tpl` has no `{hook h='displayProductListingAction'}` call.
You must add it manually inside `.thumbnail-top` in:
`themes/classic/templates/catalog/_partials/miniatures/product.tpl`
Place it after the `{/block}` closing the `product_thumbnail` block:
```smarty
{hook h='displayProductListingAction' product=$product}
```
This edit affects category listings, search results, and home featured-products simultaneously.

**`displayProductCoverImage` does not exist in PS 1.7.x — do not use.**
The hook never fires; product-page badges will be silently hidden with no error.
Use `displayProductAdditionalInfo` + JS repositioning instead (current approach).

**Multiple badges on the same side stack via flex, not absolute offsets.**
Wrapper = `display:flex; justify-content:space-between`. Two inner `.productbadges-col`
divs (left / right) use `flex-direction:column; gap:4px`. Never use `position:absolute`
per individual badge — they would overlap.

## Hooks registered

| Hook | Where it fires |
|---|---|
| `displayHeader` | Every front page — loads CSS + front JS |
| `displayProductListingAction` | Inside `.thumbnail-top` — ⚠ requires manual `{hook}` call added to `themes/classic/templates/catalog/_partials/miniatures/product.tpl` (not called by default) |
| `displayProductAdditionalInfo` | Product page additional info section (JS moves it to image) |

## Configuration keys (`ps_configuration`)

| Key | Default | Meaning |
|---|---|---|
| `PRODUCTBADGES_ACTIVE` | 1 | Global on/off |
| `PRODUCTBADGES_SHOW_LISTING` | 1 | Show in category/search listings |
| `PRODUCTBADGES_SHOW_PRODUCT` | 1 | Show on product page / quick view |
| `PRODUCTBADGES_MAX_BADGES` | 3 | Max badges per product (0 = unlimited) |

## Admin controller AJAX endpoints

All require `&ajax=1&action=<Name>&token=<AdminProductBadges token>` via POST/GET.

| Action | Method | Params | Notes |
|---|---|---|---|
| `SearchProducts` | GET | `q` | Returns up to 20 products matching name/reference |
| `AddProduct` | POST | `id_badge`, `id_product` | INSERT IGNORE — safe to call twice |
| `RemoveProduct` | POST | `id_badge`, `id_product` | Deletes single row from N:M table |

## Theme modifications required

The Classic theme's product miniature does not call `displayProductListingAction` out of the box.
Add this line to `themes/classic/templates/catalog/_partials/miniatures/product.tpl`,
after the closing `{/block}` of the `product_thumbnail` block:

```smarty
{hook h='displayProductListingAction' product=$product}
```

**Warning:** This edit is lost on theme updates. Re-apply after any theme upgrade.

**Path in dev environment:** `D:/Proyectos/Prestashop/themes/classic/templates/catalog/_partials/miniatures/product.tpl`

## Multistore

`install()` calls `Shop::setContext(Shop::CONTEXT_ALL)` when multistore is active.
Badges and configuration are global (not per-store).
