<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

$p = _DB_PREFIX_;

return [
    "CREATE TABLE IF NOT EXISTS `{$p}productbadges` (
        `id_badge`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `bg_color`   VARCHAR(7)   NOT NULL DEFAULT '#FF0000',
        `text_color` VARCHAR(7)   NOT NULL DEFAULT '#FFFFFF',
        `position`   TINYINT(1)   NOT NULL DEFAULT 0,
        `active`     TINYINT(1)   NOT NULL DEFAULT 1,
        `date_add`   DATETIME     NOT NULL,
        `date_upd`   DATETIME     NOT NULL,
        PRIMARY KEY (`id_badge`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `{$p}productbadges_lang` (
        `id_badge` INT UNSIGNED NOT NULL,
        `id_lang`  INT UNSIGNED NOT NULL,
        `label`    VARCHAR(255) NOT NULL DEFAULT '',
        PRIMARY KEY (`id_badge`, `id_lang`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `{$p}productbadges_product` (
        `id_badge`   INT UNSIGNED NOT NULL,
        `id_product` INT UNSIGNED NOT NULL,
        PRIMARY KEY (`id_badge`, `id_product`),
        KEY `idx_product` (`id_product`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];
