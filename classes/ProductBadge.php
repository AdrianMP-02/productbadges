<?php
/**
 * ProductBadge ObjectModel
 *
 * @property int    $id_badge
 * @property string $bg_color
 * @property string $text_color
 * @property int    $position   0=top-left, 1=top-right
 * @property bool   $active
 * @property string $label      (lang)
 * @property string $date_add
 * @property string $date_upd
 */
class ProductBadge extends ObjectModel
{
    public $bg_color   = '#FF0000';
    public $text_color = '#FFFFFF';
    public $position   = 0;
    public $active     = 1;
    public $label;          // multilang — must not have a default string value
    public $date_add;
    public $date_upd;

    /** @var array */
    public static $definition = [
        'table'     => 'productbadges',
        'primary'   => 'id_badge',
        'multilang' => true,
        'fields'    => [
            'bg_color'   => ['type' => self::TYPE_STRING, 'size' => 7,   'validate' => 'isColor',       'required' => true],
            'text_color' => ['type' => self::TYPE_STRING, 'size' => 7,   'validate' => 'isColor',       'required' => true],
            'position'   => ['type' => self::TYPE_INT,                   'validate' => 'isUnsignedInt'],
            'active'     => ['type' => self::TYPE_BOOL,                  'validate' => 'isBool'],
            'date_add'   => ['type' => self::TYPE_DATE],
            'date_upd'   => ['type' => self::TYPE_DATE],
            // lang fields
            'label'      => ['type' => self::TYPE_STRING, 'size' => 255, 'validate' => 'isGenericName', 'lang' => true, 'required' => true],
        ],
    ];

    /**
     * Returns active badges for a given product, ordered by id_badge ASC.
     *
     * @param int $idProduct
     * @param int $idLang
     * @param int $max       0 = no limit
     *
     * @return array  Each element: ['id_badge', 'label', 'bg_color', 'text_color', 'position']
     */
    public static function getByProduct(int $idProduct, int $idLang, int $max = 0): array
    {
        $limit = $max > 0 ? ' LIMIT ' . (int) $max : '';

        $rows = Db::getInstance()->executeS(
            'SELECT b.`id_badge`, bl.`label`, b.`bg_color`, b.`text_color`, b.`position`
             FROM `' . _DB_PREFIX_ . 'productbadges` b
             INNER JOIN `' . _DB_PREFIX_ . 'productbadges_lang` bl
                     ON bl.`id_badge` = b.`id_badge`
                    AND bl.`id_lang`  = ' . (int) $idLang . '
             INNER JOIN `' . _DB_PREFIX_ . 'productbadges_product` bp
                     ON bp.`id_badge`   = b.`id_badge`
                    AND bp.`id_product` = ' . (int) $idProduct . '
             WHERE b.`active` = 1
             ORDER BY b.`id_badge` ASC' . $limit
        );

        return is_array($rows) ? $rows : [];
    }

    /**
     * Returns all product IDs currently assigned to this badge.
     *
     * @return int[]
     */
    public function getAssignedProductIds(): array
    {
        if (!$this->id) {
            return [];
        }

        $rows = Db::getInstance()->executeS(
            'SELECT `id_product`
             FROM `' . _DB_PREFIX_ . 'productbadges_product`
             WHERE `id_badge` = ' . (int) $this->id
        );

        if (!is_array($rows)) {
            return [];
        }

        return array_map('intval', array_column($rows, 'id_product'));
    }

    /**
     * Replaces the full set of products assigned to this badge.
     *
     * @param int[] $ids  Product IDs to assign (duplicates / non-positive values are cleaned up).
     */
    public function saveProductAssignments(array $ids): void
    {
        if (!$this->id) {
            return;
        }

        $idBadge = (int) $this->id;

        // Delete existing
        Db::getInstance()->delete('productbadges_product', '`id_badge` = ' . $idBadge);

        // Insert new
        $clean = array_unique(array_filter(array_map('intval', $ids)));
        if (empty($clean)) {
            return;
        }

        $rows = [];
        foreach ($clean as $idProduct) {
            if ($idProduct > 0) {
                $rows[] = ['id_badge' => $idBadge, 'id_product' => $idProduct];
            }
        }

        if (!empty($rows)) {
            Db::getInstance()->insert('productbadges_product', $rows);
        }
    }

    /**
     * Returns a flat list of all active badges (all langs for a given lang).
     * Used by the admin list view.
     *
     * @param int $idLang
     *
     * @return array
     */
    public static function getAll(int $idLang): array
    {
        $rows = Db::getInstance()->executeS(
            'SELECT b.`id_badge`, bl.`label`, b.`bg_color`, b.`text_color`, b.`position`, b.`active`
             FROM `' . _DB_PREFIX_ . 'productbadges` b
             LEFT JOIN `' . _DB_PREFIX_ . 'productbadges_lang` bl
                    ON bl.`id_badge` = b.`id_badge`
                   AND bl.`id_lang`  = ' . (int) $idLang . '
             ORDER BY b.`id_badge` ASC'
        );

        return is_array($rows) ? $rows : [];
    }
}
