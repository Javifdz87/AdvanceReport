<?php
/**
 * ISC License
 *
 * Copyright (c) 2023 idnovate.com
 * idnovate is a Registered Trademark & Property of idnovate.com, innovación y desarrollo SCP
 *
 * Permission to use, copy, modify, and/or distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH
 * REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY
 * AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT,
 * INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM
 * LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR
 * OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR
 * PERFORMANCE OF THIS SOFTWARE.
 *
 * @author    idnovate
 * @copyright 2023 idnovate
 * @license   https://www.isc.org/licenses/ https://opensource.org/licenses/ISC ISC License
*/

$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'advancedreports` (
    `id_advancedreports` int(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NULL,
    `type` tinyint(1) unsigned NOT NULL DEFAULT "0",
    `sql_query` TEXT NULL,
    `countries` VARCHAR(250) NULL,
    `zones` VARCHAR(150) NULL,
    `payments` VARCHAR(150) NULL,
    `groups` VARCHAR(150) NULL,
    `manufacturers` VARCHAR(150) NULL,
    `suppliers` VARCHAR(150) NULL,
    `categories` VARCHAR(150) NULL,
    `statuses` VARCHAR(150) NULL,
    `data_from` int(2) unsigned NULL DEFAULT "99",
    `frequency` tinyint(1) unsigned NULL DEFAULT "0",
    `frequency_week` tinyint(1) unsigned NULL DEFAULT "0",
    `frequency_month` int(2) unsigned NULL DEFAULT "1",
    `frequency_quarter` int(2) unsigned NULL DEFAULT "0",
    `frequency_year` DATETIME,
    `format` tinyint(1) unsigned NULL DEFAULT "0",
    `email` VARCHAR(250) NULL,
    `profiles` VARCHAR(150) NULL DEFAULT "all",
    `date_from` DATETIME,
    `date_to` DATETIME,
    `fields` TEXT NULL,
    `groupby` VARCHAR(250) NULL,
    `orderby` VARCHAR(250) NULL,
    `active` tinyint(1) unsigned NOT NULL DEFAULT "0",
    `id_shop` tinyint(1) unsigned NOT NULL DEFAULT "1",
    `date_add` DATETIME,
    `date_upd` DATETIME,
    PRIMARY KEY  (`id_advancedreports`)
) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'advancedreports_fields` (
    `id_advancedreports_fields` int(11) NOT NULL AUTO_INCREMENT,
    `id_report` int(11) NOT NULL,
    `table` VARCHAR(100) NULL,
    `field` VARCHAR(100) NULL,
    `field_name` VARCHAR(150) NULL,
    `position` INT(5) NULL DEFAULT "0",
    `active` tinyint(1) unsigned NOT NULL DEFAULT "0",
    `orderby` tinyint(1) unsigned NOT NULL DEFAULT "0",
    `groupby` tinyint(1) unsigned NOT NULL DEFAULT "0",
    `sum` tinyint(1) unsigned NOT NULL DEFAULT "0",
    `id_shop` tinyint(1) unsigned NOT NULL DEFAULT "0",
    `date_add` DATETIME,
    `date_upd` DATETIME,
    PRIMARY KEY  (`id_advancedreports_fields`)
) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

if (version_compare(_PS_VERSION_, '1.7', '<')) {
    $sql[] = 'INSERT INTO `' . _DB_PREFIX_ . 'advancedreports` (`name`, `type`, `sql_query`, `countries`, 
`zones`, `groups`, `manufacturers`, `suppliers`, `categories`, `statuses`, `data_from`, `frequency`, `format`, 
`email`, `profiles`, `date_from`, `date_to`, `active`, `id_shop`, `date_add`, `date_upd`, `payments`, `fields`, `groupby`, `orderby`) 
VALUES
("Product sales last month (example)",    3,  "SELECT p.`reference` as id_product, od.`product_id`, pl.`name`, CONCAT(ROUND(p.`price`, 2), cu.`sign`) as price, cl.`name` as category_name, m.`name` as manufacturer_name, o.`date_add`, o.`reference`, osl.`name` as status_name, CONCAT(o.`total_paid`, cu.`sign`) as total_paid, col.`name` as country_name, CONCAT(c.`firstname`, \" \", c.`lastname`) as customer_name, c.`email`\r\n            FROM ' . _DB_PREFIX_ . 'order_detail od\r\n         LEFT JOIN ' . _DB_PREFIX_ . 'product p ON (od.`product_id` = p.`id_product`)\r\n            LEFT JOIN ' . _DB_PREFIX_ . 'product_lang pl ON (p.`id_product` = pl.`id_product` AND pl.`id_lang` = 3)\r\n         LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON (o.`id_order` = od.`id_order`)\r\n         LEFT JOIN ' . _DB_PREFIX_ . 'order_state_lang osl ON (o.`current_state` = osl.`id_order_state` AND osl.`id_lang` = 3)\r\n           LEFT JOIN ' . _DB_PREFIX_ . 'manufacturer m ON (p.`id_manufacturer` = m.`id_manufacturer`)\r\n          LEFT JOIN ' . _DB_PREFIX_ . 'customer c ON (o.`id_customer` = c.`id_customer`)\r\n          LEFT JOIN ' . _DB_PREFIX_ . 'category_lang cl ON (p.`id_category_default` = cl.`id_category` AND cl.`id_lang` = 3)\r\n          LEFT JOIN ' . _DB_PREFIX_ . 'address a ON (a.`id_address` = o.`id_address_delivery`)\r\n            LEFT JOIN ' . _DB_PREFIX_ . 'country co ON (a.`id_country` = co.`id_country`)\r\n           LEFT JOIN ' . _DB_PREFIX_ . 'country_lang col ON (co.`id_country` = col.`id_country` AND col.`id_lang` = 3)\r\n         LEFT JOIN ' . _DB_PREFIX_ . 'currency cu ON (cu.`id_currency` = o.`id_currency`)\r\n                        WHERE o.`date_add` > CURDATE()-15\r\n           ORDER BY o.`date_add` DESC",    "", "", "", "", "", "", "", 99, 0,  1,  "john@doe.com",  "1",    "2016-02-15 00:00:00",  "2016-02-15 00:00:00",  0,  1,  "2016-02-15 11:50:44",  "2016-02-16 17:30:26",  "", "", "", ""),
("Sales by country last month (example)", 3,  "SELECT cl.name as Country, \r\nFORMAT(SUM(o.total_paid_tax_excl), 2, \"en_EN\") as `Sales without taxes`,\r\nFORMAT(SUM(o.total_paid_tax_incl - o.total_paid_tax_excl), 2, \"en_EN\") as `Taxes`,\r\nFORMAT(SUM(o.total_paid_tax_incl), 2, \"en_EN\") as `Sales with taxes`\r\nFROM ' . _DB_PREFIX_ . 'orders o\r\nLEFT JOIN ' . _DB_PREFIX_ . 'address a ON (o.id_address_invoice = a.id_address)\r\nLEFT JOIN ' . _DB_PREFIX_ . 'country c ON (a.id_country = c.id_country)\r\nLEFT JOIN ' . _DB_PREFIX_ . 'country_lang cl ON (c.id_country = cl.id_country AND cl.id_lang = 3)\r\nLEFT JOIN ' . _DB_PREFIX_ . 'zone z ON (c.id_zone = z.id_zone)\r\nWHERE \r\no.date_add >= DATE_ADD(LAST_DAY(DATE_SUB(NOW(), INTERVAL 2 MONTH)), INTERVAL 1 DAY) \r\nAND o.date_add <= DATE_SUB(NOW(), INTERVAL 1 MONTH)\r\nAND o.current_state IN (1, 2, 3, 4, 5, 9, 10, 11, 12, 13, 14)\r\nGROUP BY cl.name\r\nUNION \r\n(SELECT \"Total\" as Total, \r\nFORMAT(SUM(o.total_paid_tax_excl), 2, \"en_EN\") as `Sales without taxes`,\r\nFORMAT(SUM(o.total_paid_tax_incl - o.total_paid_tax_excl), 2, \"en_EN\") as `Taxes`,\r\nFORMAT(SUM(o.total_paid_tax_incl), 2, \"en_EN\") as `Sales with taxes`\r\nFROM ' . _DB_PREFIX_ . 'orders o\r\nLEFT JOIN ' . _DB_PREFIX_ . 'address a ON (o.id_address_invoice = a.id_address)\r\nLEFT JOIN ' . _DB_PREFIX_ . 'country c ON (a.id_country = c.id_country)\r\nLEFT JOIN ' . _DB_PREFIX_ . 'country_lang cl ON (c.id_country = cl.id_country AND cl.id_lang = 3)\r\nLEFT JOIN ' . _DB_PREFIX_ . 'zone z ON (c.id_zone = z.id_zone)\r\nWHERE \r\no.date_add >= DATE_ADD(LAST_DAY(DATE_SUB(NOW(), INTERVAL 2 MONTH)), INTERVAL 1 DAY) \r\nAND o.date_add <= DATE_SUB(NOW(), INTERVAL 1 MONTH)\r\nAND o.current_state IN (1, 2, 3, 4, 5, 9, 10, 11, 12, 13, 14)\r\nGROUP BY Total)", "", "", "", "", "", "", "", 99, 0,  0,  "",   "1",    "0000-00-00 00:00:00",  "0000-00-00 00:00:00",  1,  1,  "2016-02-17 10:09:53",  "2016-02-17 15:27:50",  "", "", "", "");';
} else {
    $sql[] = 'INSERT INTO `' . _DB_PREFIX_ . 'advancedreports` (`name`, `type`, `sql_query`, `countries`, 
`zones`, `groups`, `manufacturers`, `suppliers`, `categories`, `statuses`, `data_from`, `frequency`, `format`, 
`email`, `profiles`, `date_from`, `date_to`, `active`, `id_shop`, `date_add`, `date_upd`, `payments`, `fields`, `groupby`, `orderby`) 
VALUES
("Product sales last month (example)",    3,  "SELECT od.`product_id` as id_product, p.`reference`, pl.`name`, CONCAT(ROUND(p.`price`, 2)) as price, cl.`name` as category_name, m.`name` as manufacturer_name, o.`date_add`, o.`reference`, osl.`name` as status_name, CONCAT(o.`total_paid`) as total_paid, col.`name` as country_name, CONCAT(c.`firstname`, \" \", c.`lastname`) as customer_name, c.`email`\r\n            FROM ' . _DB_PREFIX_ . 'order_detail od\r\n         LEFT JOIN ' . _DB_PREFIX_ . 'product p ON (od.`product_id` = p.`id_product`)\r\n            LEFT JOIN ' . _DB_PREFIX_ . 'product_lang pl ON (p.`id_product` = pl.`id_product` AND pl.`id_lang` = 3)\r\n         LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON (o.`id_order` = od.`id_order`)\r\n         LEFT JOIN ' . _DB_PREFIX_ . 'order_state_lang osl ON (o.`current_state` = osl.`id_order_state` AND osl.`id_lang` = 3)\r\n           LEFT JOIN ' . _DB_PREFIX_ . 'manufacturer m ON (p.`id_manufacturer` = m.`id_manufacturer`)\r\n          LEFT JOIN ' . _DB_PREFIX_ . 'customer c ON (o.`id_customer` = c.`id_customer`)\r\n          LEFT JOIN ' . _DB_PREFIX_ . 'category_lang cl ON (p.`id_category_default` = cl.`id_category` AND cl.`id_lang` = 3)\r\n          LEFT JOIN ' . _DB_PREFIX_ . 'address a ON (a.`id_address` = o.`id_address_delivery`)\r\n            LEFT JOIN ' . _DB_PREFIX_ . 'country co ON (a.`id_country` = co.`id_country`)\r\n           LEFT JOIN ' . _DB_PREFIX_ . 'country_lang col ON (co.`id_country` = col.`id_country` AND col.`id_lang` = 3)\r\n         LEFT JOIN ' . _DB_PREFIX_ . 'currency cu ON (cu.`id_currency` = o.`id_currency`)\r\n                        WHERE o.`date_add` > CURDATE()-15\r\n           ORDER BY o.`date_add` DESC",    "", "", "", "", "", "", "", 99, 0,  1,  "john@doe.com",  "1",    "2016-02-15 00:00:00",  "2016-02-15 00:00:00",  0,  1,  "2016-02-15 11:50:44",  "2016-02-16 17:30:26",  "", "", "", ""),
("Sales by country last month (example)", 3,  "SELECT cl.name as Country, \r\nFORMAT(SUM(o.total_paid_tax_excl), 2, \"en_EN\") as `Sales without taxes`,\r\nFORMAT(SUM(o.total_paid_tax_incl - o.total_paid_tax_excl), 2, \"en_EN\") as `Taxes`,\r\nFORMAT(SUM(o.total_paid_tax_incl), 2, \"en_EN\") as `Sales with taxes`\r\nFROM ' . _DB_PREFIX_ . 'orders o\r\nLEFT JOIN ' . _DB_PREFIX_ . 'address a ON (o.id_address_invoice = a.id_address)\r\nLEFT JOIN ' . _DB_PREFIX_ . 'country c ON (a.id_country = c.id_country)\r\nLEFT JOIN ' . _DB_PREFIX_ . 'country_lang cl ON (c.id_country = cl.id_country AND cl.id_lang = 3)\r\nLEFT JOIN ' . _DB_PREFIX_ . 'zone z ON (c.id_zone = z.id_zone)\r\nWHERE \r\no.date_add >= DATE_ADD(LAST_DAY(DATE_SUB(NOW(), INTERVAL 2 MONTH)), INTERVAL 1 DAY) \r\nAND o.date_add <= DATE_SUB(NOW(), INTERVAL 1 MONTH)\r\nAND o.current_state IN (1, 2, 3, 4, 5, 9, 10, 11, 12, 13, 14)\r\nGROUP BY cl.name\r\nUNION \r\n(SELECT \"Total\" as Total, \r\nFORMAT(SUM(o.total_paid_tax_excl), 2, \"en_EN\") as `Sales without taxes`,\r\nFORMAT(SUM(o.total_paid_tax_incl - o.total_paid_tax_excl), 2, \"en_EN\") as `Taxes`,\r\nFORMAT(SUM(o.total_paid_tax_incl), 2, \"en_EN\") as `Sales with taxes`\r\nFROM ' . _DB_PREFIX_ . 'orders o\r\nLEFT JOIN ' . _DB_PREFIX_ . 'address a ON (o.id_address_invoice = a.id_address)\r\nLEFT JOIN ' . _DB_PREFIX_ . 'country c ON (a.id_country = c.id_country)\r\nLEFT JOIN ' . _DB_PREFIX_ . 'country_lang cl ON (c.id_country = cl.id_country AND cl.id_lang = 3)\r\nLEFT JOIN ' . _DB_PREFIX_ . 'zone z ON (c.id_zone = z.id_zone)\r\nWHERE \r\no.date_add >= DATE_ADD(LAST_DAY(DATE_SUB(NOW(), INTERVAL 2 MONTH)), INTERVAL 1 DAY) \r\nAND o.date_add <= DATE_SUB(NOW(), INTERVAL 1 MONTH)\r\nAND o.current_state IN (1, 2, 3, 4, 5, 9, 10, 11, 12, 13, 14)\r\nGROUP BY Total)", "", "", "", "", "", "", "", 99, 0,  0,  "",   "1",    "0000-00-00 00:00:00",  "0000-00-00 00:00:00",  1,  1,  "2016-02-17 10:09:53",  "2016-02-17 15:27:50",  "", "", "", "");';
}

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
