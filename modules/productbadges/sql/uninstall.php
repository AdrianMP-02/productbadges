<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

$p = _DB_PREFIX_;

return [
    "DROP TABLE IF EXISTS `{$p}productbadges_product`",
    "DROP TABLE IF EXISTS `{$p}productbadges_lang`",
    "DROP TABLE IF EXISTS `{$p}productbadges`",
];
