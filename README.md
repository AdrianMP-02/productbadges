# productbadges

PrestaShop 1.7 module — reusable visual badges on product images.

## Tested on

- PrestaShop **1.7.8.11**
- PHP **8.1** (compatible with 7.4)
- MySQL 5.7 / MariaDB 10.4

## Features

- Create unlimited badges with custom text (multilingual), background colour, text colour, and corner position (top-left / top-right).
- Assign badges to any number of products (many-to-many).
- Display badges overlaid on the product image in:
  - Category listings
  - Search results and any listing that fires `displayProductListingAction`
  - Product detail page (`displayProductAdditionalInfo`)
- Global configuration: enable/disable, show in listings, show on product page, max visible badges per product.
- Multilingual: badge label is translatable to every active language in the shop.
- Multistore: safe to install on multistore setups; badges are global across all stores.

## Installation

1. Copy the `productbadges/` folder into `<prestashop>/modules/`.
2. Go to **Back Office → Modules → Module Manager** and search for *Product Badges*.
3. Click **Install**.

## Configuration

After installation, click **Configure** on the module to set global options.

Manage badges under **Catalogue → Product Badges**.

## Uninstallation

Uninstalling the module from the Module Manager will:

- Drop the three database tables (`productbadges`, `productbadges_lang`, `productbadges_product`).
- Remove the *Product Badges* admin tab.
- Delete all saved configuration values.

## Database tables

| Table | Purpose |
|---|---|
| `ps_productbadges` | Badge definitions (colours, position, active flag) |
| `ps_productbadges_lang` | Multilingual badge labels |
| `ps_productbadges_product` | Many-to-many: badge ↔ product |

## Hooks used

| Hook | Purpose |
|---|---|
| `displayHeader` | Inject CSS on every front-office page |
| `displayProductListingAction` | Render badges on product cards in listings |
| `displayProductAdditionalInfo` | Render badges on the product detail page |

## Requirements

- PrestaShop 1.7.x
- PHP 7.4 or 8.1
- No Composer dependencies
- No external JavaScript libraries (only jQuery, already bundled by PrestaShop)
