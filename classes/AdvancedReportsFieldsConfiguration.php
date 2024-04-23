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

class AdvancedReportsFieldsConfiguration extends ObjectModel
{
    public $id_advancedreports_fields;
    public $id_report;
    public $table;
    public $field;
    public $field_name;
    public $position;
    public $active = false;
    public $orderby = false;
    public $groupby = false;
    public $sum = false;
    public $id_shop;
    public $date_add;
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'advancedreports_fields',
        'primary' => 'id_advancedreports_fields',
        'fields' => array(
            'id_report' =>          array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'table' =>              array('type' => self::TYPE_STRING, 'size' => 100),
            'field' =>              array('type' => self::TYPE_STRING, 'size' => 100),
            'field_name' =>         array('type' => self::TYPE_STRING, 'size' => 150),
            'position' =>           array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'active' =>             array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false),
            'orderby' =>            array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false),
            'groupby' =>            array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false),
            'sum' =>                array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false),
            'id_shop' =>            array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false),
            'date_add' =>           array('type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false),
            'date_upd' =>           array('type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false),
        ),
    );

    public function __construct($id = null)
    {
        parent::__construct($id);
    }

    public function add($autodate = true, $null_values = true)
    {
        $this->id_shop = ($this->id_shop) ? $this->id_shop : Context::getContext()->shop->id;
        $success = parent::add($autodate, $null_values);
        return $success;
    }

    public function toggleStatus()
    {
        parent::toggleStatus();
        return Db::getInstance()->execute('
            UPDATE `'._DB_PREFIX_.bqSQL($this->def['table']).'`
            SET `date_upd` = NOW()
            WHERE `'.bqSQL($this->def['primary']).'` = '.(int)$this->id);
    }

    public function updatePosition($way, $position)
    {
        if (!$res = Db::getInstance()->executeS('
            SELECT `id_advancedreports_fields`, `position`
            FROM `'._DB_PREFIX_.'advancedreports_fields`
            ORDER BY `position` ASC'
        ))
            return false;

        foreach ($res as $report)
            if ((int)$report['id_advancedreports_fields'] == (int)$this->id)
                $moved_report = $report;

        if (!isset($moved_report) || !isset($position))
            return false;

        return (Db::getInstance()->execute('
            UPDATE `'._DB_PREFIX_.'advancedreports_fields`
            SET `position`= `position` '.($way ? '- 1' : '+ 1').'
            WHERE `position`
            '.($way
                ? '> '.(int)$moved_report['position'].' AND `position` <= '.(int)$position
                : '< '.(int)$moved_report['position'].' AND `position` >= '.(int)$position))
        && Db::getInstance()->execute('
            UPDATE `'._DB_PREFIX_.'advancedreports_fields`
            SET `position` = '.(int)$position.'
            WHERE `id_advancedreports_fields` = '.(int)$moved_report['id_advancedreports_fields']));
    }

    public static function getLastPosition($id_report)
    {
        return Db::getInstance()->getValue('
            SELECT COUNT(*) FROM `'._DB_PREFIX_.bqSQL('advancedreports_fields').'`
            WHERE `id_report` = '.(int)$id_report);
    }

    public static function haveFields($id_report)
    {
        return Db::getInstance()->getValue('
            SELECT COUNT(*) FROM `'._DB_PREFIX_.bqSQL('advancedreports_fields').'`
            WHERE `id_report` = '.(int)$id_report);
    }

    public function delete()
    {
        return parent::delete();
    }
}
