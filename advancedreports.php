<?php
/**
 * Advanced Reports
 *
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

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once(dirname(__FILE__).'/classes/AdvancedReportsConfiguration.php');
include_once(dirname(__FILE__).'/classes/AdvancedReportsFieldsConfiguration.php');

class Advancedreports extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'advancedreports';
        $this->tab = 'administration';
        $this->version = '1.4.9';
        $this->author = 'idnovate';
        $this->module_key = 'b9f109870304ee7d3d0bca606e4d66cd';
        //$this->author_address = '0xd89bcCAeb29b2E6342a74Bc0e9C82718Ac702160';
        $this->need_instance = 1;

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Advanced Reports');
        $this->description = $this->l('Create custom live reports freely. Visually selecting the fields or through direct SQL query. Create as many reports as you need. Schedule and send the live report by email.');
        $this->tabClassName = 'AdminAdvancedReportsConf';
        $this->tabReportsFieldsClassName = 'AdminAdvancedReportsFieldsConf';

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall the module and delete the related information?');

        if (version_compare(_PS_VERSION_, '1.6', '>=')) {
            if (Configuration::get('PS_DISABLE_NON_NATIVE_MODULE')) {
                $this->warning = $this->l('You have to enable non PrestaShop modules at ADVANCED PARAMETERS - PERFORMANCE');
            }
        }
    }

    public function install()
    {

        $configTabName = $this->displayName.' - '.$this->l('Fields configuration');
        if (version_compare(_PS_VERSION_, '1.6', '<')) {
            $configTabName = substr($configTabName, 0, 32);
        }

        include(dirname(__FILE__).'/sql/install.php');
        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('actionAdminControllerSetMedia') &&
            (version_compare(_PS_VERSION_, '1.7', '<') ? $this->addTab($this->displayName, $this->tabClassName, -1) : $this->addTab($this->displayName, $this->tabClassName, 0)) &&
            (version_compare(_PS_VERSION_, '1.7', '<') ? $this->addTab($configTabName, $this->tabReportsFieldsClassName, -1) : $this->addTab($this->displayName.' - '.$this->l('Fields configuration'), $this->tabReportsFieldsClassName, 0));

         }

    public function uninstall()
    {

        include(dirname(__FILE__).'/sql/uninstall.php');
        $this->removeTab($this->tabClassName);
        $this->removeTab($this->tabReportsFieldsClassName);
        return parent::uninstall();

    }

    public function getContent()
    {
        if (Tools::isSubmit('submitUpdate')) {
            $this->context->controller->errors[] = $this->l('El módulo no se puede actualizar.');
            return;
        }

        if (version_compare(_PS_VERSION_, '1.6', '<')) {
            Tools::redirectAdmin('index.php?tab='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName));
        } else {
            Tools::redirectAdmin('index.php?controller='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName));
        }
    }


    public function hookBackOfficeHeader()
    {
        return $this->hookActionAdminControllerSetMedia();
    }

    public function hookHeader()
    {
        //$this->context->controller->addJS($this->_path.'/views/js/front.js');
        //$this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    public function hookDisplayBackOfficeHeader()
    {
        if (version_compare(_PS_VERSION_, '1.6', '<')) {
            return $this->hookActionAdminControllerSetMedia();
        }
    }

    public function hookActionAdminControllerSetMedia($params = null)
    {
        if (version_compare(_PS_VERSION_, '1.6', '>=')) {
            if (Tools::getValue('controller') == $this->tabClassName || Tools::getValue('controller') == $this->tabReportsFieldsClassName) {
                $this->context->controller->addJS($this->_path.'views/js/back.js');
                $this->context->controller->addCSS($this->_path.'views/css/back.css');
            }
        } else {
            $controller = Tools::getValue('tab') ? Tools::getValue('tab') : Tools::getValue('controller');
            if (
                Tools::strtolower($controller) == Tools::strtolower($this->tabClassName) ||
                Tools::strtolower($controller) == Tools::strtolower($this->tabReportsFieldsClassName)
            ) {
                $this->context->controller->addJS($this->_path.'views/js/back15.js');
                $this->context->controller->addCSS($this->_path.'views/css/back.css');
            }
        }
    }

    private function addTab($tabName, $tabClassName, $idTabParent)
    {
        $id_tab = Tab::getIdFromClassName($tabClassName);
        $tabNames = array();
        if (!$id_tab) {
            if (version_compare(_PS_VERSION_, '1.5', '<')) {
                $langs = Language::getlanguages(false);
                foreach ($langs as $l) {
                    $tabNames[$l['id_lang']] = $tabName;
                }
                $tab = new Tab();
                $tab->module = $this->name;
                $tab->name = $tabNames;
                $tab->class_name = $tabClassName;
                $tab->id_parent = $idTabParent;
                if (!$tab->save()) {
                    return false;
                }
            } else {
                $tab = new Tab();
                $tab->class_name = $tabClassName;
                $tab->id_parent = $idTabParent;
                $tab->module = $this->name;
                $languages = Language::getLanguages();
                foreach ($languages as $language) {
                    $tab->name[$language['id_lang']] = $this->l($tabName);
                }
                if (!$tab->add()) {
                    return false;
                }
            }
        }
        return true;
    }

    private function removeTab($tabClassName)
    {
        $idTab = Tab::getIdFromClassName($tabClassName);

        if ($idTab) {
            $tab = new Tab($idTab);
            $tab->delete();
            return true;
        }

        return false;
    }
}
