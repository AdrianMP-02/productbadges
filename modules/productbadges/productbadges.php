<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/ProductBadge.php';

class ProductBadges extends Module
{
    /** @var string[] Configuration keys managed by this module */
    private const CONFIG_KEYS = [
        'PRODUCTBADGES_ACTIVE',
        'PRODUCTBADGES_SHOW_LISTING',
        'PRODUCTBADGES_SHOW_PRODUCT',
        'PRODUCTBADGES_MAX_BADGES',
    ];

    public function __construct()
    {
        $this->name      = 'productbadges';
        $this->tab       = 'front_office_features';
        $this->version   = '1.1.1';
        $this->author    = 'Adrian Martin';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Product Badges');
        $this->description = $this->l('Manage reusable visual badges (labels) on product images.');
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
    }

    // -------------------------------------------------------------------------
    // Install / Uninstall
    // -------------------------------------------------------------------------

    public function install(): bool
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        return parent::install()
            && $this->installSql()
            && $this->installTab()
            && $this->installConfig()
            && $this->registerHook([
                'displayHeader',
                'displayProductListingAction',
                'displayProductAdditionalInfo',
            ]);
    }

    public function uninstall(): bool
    {
        $this->unregisterHook('displayProductCoverImage');

        return $this->uninstallSql()
            && $this->uninstallTab()
            && $this->uninstallConfig()
            && parent::uninstall();
    }

    private function installSql(): bool
    {
        foreach (include __DIR__ . '/sql/install.php' as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    private function uninstallSql(): bool
    {
        foreach (include __DIR__ . '/sql/uninstall.php' as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    private function installTab(): bool
    {
        $tab = new Tab();
        $tab->active     = 1;
        $tab->class_name = 'AdminProductBadges';
        $tab->module     = $this->name;
        $tab->id_parent  = (int) Tab::getIdFromClassName('AdminCatalog');
        $tab->name       = [];

        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Product Badges';
        }

        return (bool) $tab->add();
    }

    private function uninstallTab(): bool
    {
        $idTab = (int) Tab::getIdFromClassName('AdminProductBadges');

        if ($idTab) {
            $tab = new Tab($idTab);
            return (bool) $tab->delete();
        }

        return true;
    }

    private function installConfig(): bool
    {
        return Configuration::updateValue('PRODUCTBADGES_ACTIVE', 1)
            && Configuration::updateValue('PRODUCTBADGES_SHOW_LISTING', 1)
            && Configuration::updateValue('PRODUCTBADGES_SHOW_PRODUCT', 1)
            && Configuration::updateValue('PRODUCTBADGES_MAX_BADGES', 3);
    }

    private function uninstallConfig(): bool
    {
        foreach (self::CONFIG_KEYS as $key) {
            Configuration::deleteByName($key);
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // Module configuration page
    // -------------------------------------------------------------------------

    public function getContent(): string
    {
        $output = '';

        if (Tools::isSubmit('submitProductBadgesConfig')) {
            $active      = (int) Tools::getValue('PRODUCTBADGES_ACTIVE');
            $showListing = (int) Tools::getValue('PRODUCTBADGES_SHOW_LISTING');
            $showProduct = (int) Tools::getValue('PRODUCTBADGES_SHOW_PRODUCT');
            $maxBadges   = max(0, (int) Tools::getValue('PRODUCTBADGES_MAX_BADGES'));

            Configuration::updateValue('PRODUCTBADGES_ACTIVE',       $active);
            Configuration::updateValue('PRODUCTBADGES_SHOW_LISTING', $showListing);
            Configuration::updateValue('PRODUCTBADGES_SHOW_PRODUCT', $showProduct);
            Configuration::updateValue('PRODUCTBADGES_MAX_BADGES',   $maxBadges);

            $output .= $this->displayConfirmation($this->l('Settings saved.'));
        }

        return $output . $this->renderConfigForm();
    }

    private function renderConfigForm(): string
    {
        $helper = new HelperForm();
        $helper->module          = $this;
        $helper->name_controller = $this->name;
        $helper->token           = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex    = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action   = 'submitProductBadgesConfig';
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');

        $helper->tpl_vars = [
            'fields_value' => [
                'PRODUCTBADGES_ACTIVE'       => (int) Configuration::get('PRODUCTBADGES_ACTIVE'),
                'PRODUCTBADGES_SHOW_LISTING' => (int) Configuration::get('PRODUCTBADGES_SHOW_LISTING'),
                'PRODUCTBADGES_SHOW_PRODUCT' => (int) Configuration::get('PRODUCTBADGES_SHOW_PRODUCT'),
                'PRODUCTBADGES_MAX_BADGES'   => (int) Configuration::get('PRODUCTBADGES_MAX_BADGES'),
            ],
            'languages'       => $this->context->controller->getLanguages(),
            'id_language'     => $this->context->language->id,
        ];

        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Product Badges Settings'),
                    'icon'  => 'icon-cog',
                ],
                'input' => [
                    [
                        'type'    => 'switch',
                        'label'   => $this->l('Enable module'),
                        'name'    => 'PRODUCTBADGES_ACTIVE',
                        'values'  => $this->getSwitchValues(),
                    ],
                    [
                        'type'    => 'switch',
                        'label'   => $this->l('Show badges in product listings'),
                        'name'    => 'PRODUCTBADGES_SHOW_LISTING',
                        'values'  => $this->getSwitchValues(),
                    ],
                    [
                        'type'    => 'switch',
                        'label'   => $this->l('Show badges on product page'),
                        'name'    => 'PRODUCTBADGES_SHOW_PRODUCT',
                        'values'  => $this->getSwitchValues(),
                    ],
                    [
                        'type'    => 'text',
                        'label'   => $this->l('Max visible badges per product'),
                        'name'    => 'PRODUCTBADGES_MAX_BADGES',
                        'class'   => 'fixed-width-sm',
                        'suffix'  => $this->l('badges'),
                        'desc'    => $this->l('Maximum number of badges shown simultaneously (0 = unlimited).'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];

        return $helper->generateForm([$form]);
    }

    private function getSwitchValues(): array
    {
        return [
            ['id' => 'active_on',  'value' => 1, 'label' => $this->l('Yes')],
            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')],
        ];
    }

    // -------------------------------------------------------------------------
    // Hooks
    // -------------------------------------------------------------------------

    public function hookDisplayHeader($params): void
    {
        if (!Configuration::get('PRODUCTBADGES_ACTIVE')) {
            return;
        }

        $this->context->controller->addCSS($this->_path . 'views/css/productbadges.css');

        // JS handles product page + quick view from any listing page — skip only if product badges disabled
        if (Configuration::get('PRODUCTBADGES_SHOW_PRODUCT')) {
            $this->context->controller->addJS($this->_path . 'views/js/productbadges_front.js');
        }
    }

    /**
     * Fires once per product in any listing (category, search, home featured…).
     * $params['product'] is an array in PS 1.7 (product listing object).
     */
    public function hookDisplayProductListingAction($params): string
    {
        if (!Configuration::get('PRODUCTBADGES_ACTIVE')
            || !Configuration::get('PRODUCTBADGES_SHOW_LISTING')
        ) {
            return '';
        }

        $idProduct = isset($params['product']['id_product'])
            ? (int) $params['product']['id_product']
            : (isset($params['product']['id']) ? (int) $params['product']['id'] : 0);

        if (!$idProduct) {
            return '';
        }

        return $this->renderBadges($idProduct, 'product_badges.tpl');
    }

    /**
     * Fires on the product detail page.
     * JS (productbadges_front.js) repositions the wrapper into .product-cover.
     */
    public function hookDisplayProductAdditionalInfo($params): string
    {
        if (!Configuration::get('PRODUCTBADGES_ACTIVE')
            || !Configuration::get('PRODUCTBADGES_SHOW_PRODUCT')
        ) {
            return '';
        }

        $idProduct = 0;
        if (isset($params['product'])) {
            $idProduct = is_array($params['product'])
                ? (int) ($params['product']['id_product'] ?? $params['product']['id'] ?? 0)
                : (int) $params['product']->id;
        }

        if (!$idProduct) {
            return '';
        }

        return $this->renderBadges($idProduct, 'product_page_badges.tpl');
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private function renderBadges(int $idProduct, string $tpl): string
    {
        $idLang = (int) $this->context->language->id;
        $max    = (int) Configuration::get('PRODUCTBADGES_MAX_BADGES');
        $badges = ProductBadge::getByProduct($idProduct, $idLang, $max);

        if (empty($badges)) {
            return '';
        }

        $this->context->smarty->assign('badges', $badges);

        return $this->display(__FILE__, 'views/templates/hook/' . $tpl);
    }
}
