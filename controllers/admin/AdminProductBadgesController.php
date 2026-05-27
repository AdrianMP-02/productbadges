<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'productbadges/classes/ProductBadge.php';

class AdminProductBadgesController extends ModuleAdminController
{
    public function __construct()
    {
        $this->table      = 'productbadges';
        $this->className  = 'ProductBadge';
        $this->lang       = true;
        $this->bootstrap  = true;
        $this->identifier = 'id_badge';

        parent::__construct();

        $this->meta_title = $this->l('Product Badges');
        $this->toolbar_title = $this->l('Product Badges');

        // ------------------------------------------------------------------
        // List columns
        // ------------------------------------------------------------------
        $this->fields_list = [
            'id_badge' => [
                'title'  => $this->l('ID'),
                'align'  => 'center',
                'class'  => 'fixed-width-xs',
            ],
            'label' => [
                'title'  => $this->l('Label'),
                'filter_key' => 'b!label',
            ],
            'bg_color' => [
                'title'   => $this->l('Background'),
                'align'   => 'center',
                'callback' => 'renderColorSwatch',
                'orderby' => false,
                'search'  => false,
            ],
            'text_color' => [
                'title'   => $this->l('Text color'),
                'align'   => 'center',
                'callback' => 'renderColorSwatch',
                'orderby' => false,
                'search'  => false,
            ],
            'position' => [
                'title'    => $this->l('Position'),
                'align'    => 'center',
                'callback' => 'renderPosition',
                'orderby'  => false,
                'search'   => false,
            ],
            'active' => [
                'title'   => $this->l('Active'),
                'align'   => 'center',
                'active'  => 'status',
                'type'    => 'bool',
                'orderby' => false,
            ],
        ];

        // ------------------------------------------------------------------
        // Create / Edit form
        // ------------------------------------------------------------------
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Badge'),
                'icon'  => 'icon-tag',
            ],
            'input' => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Label'),
                    'name'     => 'label',
                    'lang'     => true,
                    'required' => true,
                    'hint'     => $this->l('Visible text on the badge (e.g. NEW, SALE, EXCLUSIVE).'),
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Background color'),
                    'name'  => 'bg_color',
                    'class' => 'pb-color-picker fixed-width-sm',
                    'desc'  => $this->l('Hex color code, e.g. #FF0000'),
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Text color'),
                    'name'  => 'text_color',
                    'class' => 'pb-color-picker fixed-width-sm',
                    'desc'  => $this->l('Hex color code, e.g. #FFFFFF'),
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Position'),
                    'name'    => 'position',
                    'options' => [
                        'query' => [
                            ['id' => 0, 'name' => $this->l('Top left')],
                            ['id' => 1, 'name' => $this->l('Top right')],
                        ],
                        'id'   => 'id',
                        'name' => 'name',
                    ],
                ],
                [
                    'type'   => 'switch',
                    'label'  => $this->l('Active'),
                    'name'   => 'active',
                    'values' => [
                        ['id' => 'active_on',  'value' => 1, 'label' => $this->l('Yes')],
                        ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')],
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // List callbacks
    // -------------------------------------------------------------------------

    public function renderColorSwatch(string $value, array $row): string
    {
        $safe = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        return '<span style="display:inline-block;width:24px;height:24px;background:' . $safe . ';border:1px solid #ccc;border-radius:3px;" title="' . $safe . '"></span>';
    }

    public function renderPosition($value, array $row): string
    {
        return $value == 0 ? $this->l('Top left') : $this->l('Top right');
    }

    // -------------------------------------------------------------------------
    // Form render – append product assignment widget
    // -------------------------------------------------------------------------

    public function renderForm(): string
    {
        // Load admin JS for color pickers
        $this->addJS(_PS_MODULE_DIR_ . 'productbadges/views/js/productbadges_admin.js');

        $baseForm = parent::renderForm();

        if ($this->object && $this->object->id) {
            $assignedIds = $this->object->getAssignedProductIds();
            $products    = [];

            foreach ($assignedIds as $idProduct) {
                $product = new Product($idProduct, false, $this->context->language->id);
                if (Validate::isLoadedObject($product)) {
                    $products[] = [
                        'id_product' => $idProduct,
                        'name'       => $product->name,
                        'reference'  => $product->reference,
                    ];
                }
            }

            $this->context->smarty->assign([
                'badge_id'          => (int) $this->object->id,
                'assigned_products' => $products,
                'admin_token'       => Tools::getAdminTokenLite('AdminProductBadges'),
                'product_search_url' => $this->context->link->getAdminLink('AdminProductBadges') . '&ajax=1&action=SearchProducts',
            ]);

            $tplPath = _PS_MODULE_DIR_ . 'productbadges/views/templates/admin/product_assign.tpl';
            $productWidget = $this->context->smarty->fetch($tplPath);

            return $baseForm . $productWidget;
        }

        return $baseForm;
    }

    // -------------------------------------------------------------------------
    // Save – persist product assignment alongside the ObjectModel
    // processSave runs before the redirect is triggered, so it is safe to
    // call saveProductAssignments here.
    // -------------------------------------------------------------------------

    public function processSave()
    {
        $object = parent::processSave();

        if ($this->object && $this->object->id) {
            $rawIds = Tools::getValue('assigned_product_ids', '');
            $ids    = array_filter(
                array_map('intval', explode(',', $rawIds)),
                static fn(int $v) => $v > 0
            );
            $this->object->saveProductAssignments($ids);
        }

        return $object;
    }

    // -------------------------------------------------------------------------
    // AJAX – product autocomplete
    // -------------------------------------------------------------------------

    public function ajaxProcessSearchProducts(): void
    {
        $query = pSQL(Tools::getValue('q', ''));

        if (!$query) {
            $this->ajaxDie(json_encode([]));
        }

        $idLang = (int) $this->context->language->id;
        $rows   = Db::getInstance()->executeS(
            'SELECT p.`id_product`, pl.`name`, p.`reference`
             FROM `' . _DB_PREFIX_ . 'product` p
             INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                     ON pl.`id_product` = p.`id_product`
                    AND pl.`id_lang`    = ' . $idLang . '
             WHERE pl.`name` LIKE \'%' . $query . '%\'
                OR p.`reference` LIKE \'%' . $query . '%\'
             ORDER BY pl.`name` ASC
             LIMIT 20'
        );

        if (!is_array($rows)) {
            $rows = [];
        }

        $this->ajaxDie(json_encode($rows));
    }

    // -------------------------------------------------------------------------
    // Remove product from badge (called by inline delete button)
    // -------------------------------------------------------------------------

    public function ajaxProcessRemoveProduct(): void
    {
        $idBadge   = (int) Tools::getValue('id_badge');
        $idProduct = (int) Tools::getValue('id_product');

        if (!$idBadge || !$idProduct) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Invalid parameters']));
        }

        Db::getInstance()->delete(
            'productbadges_product',
            '`id_badge` = ' . $idBadge . ' AND `id_product` = ' . $idProduct
        );

        $this->ajaxDie(json_encode(['success' => true]));
    }

    // -------------------------------------------------------------------------
    // Add product to badge (called by inline add button)
    // -------------------------------------------------------------------------

    public function ajaxProcessAddProduct(): void
    {
        $idBadge   = (int) Tools::getValue('id_badge');
        $idProduct = (int) Tools::getValue('id_product');

        if (!$idBadge || !$idProduct) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Invalid parameters']));
        }

        // Upsert – ignore duplicate key
        Db::getInstance()->execute(
            'INSERT IGNORE INTO `' . _DB_PREFIX_ . 'productbadges_product`
             (`id_badge`, `id_product`) VALUES (' . $idBadge . ', ' . $idProduct . ')'
        );

        $idLang  = (int) $this->context->language->id;
        $product = new Product($idProduct, false, $idLang);
        $name    = Validate::isLoadedObject($product) ? $product->name : 'Product #' . $idProduct;

        $this->ajaxDie(json_encode([
            'success'    => true,
            'id_product' => $idProduct,
            'name'       => $name,
            'reference'  => Validate::isLoadedObject($product) ? $product->reference : '',
        ]));
    }
}
