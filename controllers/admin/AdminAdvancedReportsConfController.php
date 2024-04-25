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

class AdminAdvancedReportsConfController extends ModuleAdminController
{
    protected $delete_mode;
    protected $_defaultOrderBy = 'id_advancedreports';
    protected $_defaultOrderWay = 'DESC';
    protected $can_add_reports = true;
    protected $top_elements_in_list = 4;

    public function __construct($bypass = false)
    {

        $this->bootstrap = true;
        $this->module_name = 'advancedreports';
        $this->name = 'advancedreports';
        $this->table = $this->module_name;
        $this->className = 'AdvancedReportsConfiguration';
        $this->class_name = 'AdvancedReportsConfiguration';
        $this->tabClassName = 'AdminAdvancedReportsConf';
        $this->tabReportsFieldsClassName = 'AdminAdvancedReportsFieldsConf';
        $this->addRowAction('view');
        $this->addRowAction('editreport');
        $this->addRowAction('editfields');
        $this->addRowAction('editother');
        $this->addRowAction('generatereport');
        $this->addRowAction('duplicate');
        $this->addRowAction('delete');
        $this->_orderWay = $this->_defaultOrderWay;
        $this->show_toolbar = true;
        $this->allow_export = true;
        $this->allow_execute = true;
        $this->allow_duplicate = true;

        parent::__construct();

        if (version_compare(_PS_VERSION_, '1.4.5', '=')) {
            $this->meta_title = $this->l('Advanced reports configuration');
        } else {
            $this->meta_title[] = $this->l('Advanced reports configuration');
        }
        $this->tpl_list_vars['title'] = $this->l('List of advanced reports');
        $this->taxes_included = (Configuration::get('PS_TAX') == '0' ? false : true);

        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash'
            )
        );

        $this->context = Context::getContext();

        $this->default_form_language = $this->context->language->id;

        $this->_where = $this->filterByEmployee();

        $this->fields_list = array(
            'id_advancedreports' => array(
                'title' => $this->l('ID'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs'
            ),
            'name' => array(
                'title' => $this->l('Name'),
                'filter_key' => 'a!name'
            ),
            'type' => array(
                'title' => $this->l('Type'),
                'callback' => 'getAdvancedReportsType',
                'align' => 'text-center'
            ),
            'data_from' => array(
                'title' => $this->l('Data of'),
                'callback' => 'getAdvancedReportsDataFrom',
                'align' => 'text-center'
            ),
            'format' => array(
                'title' => $this->l('Format'),
                'align' => 'text-center',
                'callback' => 'printReportIcon',
                'orderby' => false,
                'search' => false
            ),
            'active' => array(
                'title' => $this->l('Auto generate'),
                'align' => 'text-center',
                'active' => 'status',
                'type' => 'bool',
                'callback' => 'printActiveIcon'
            ),
            'frequency' => array(
                'title' => $this->l('Execute every'),
                'callback' => 'getAdvancedReportsFrequency',
                'align' => 'text-center'
            ),
            'email' => array(
                'title' => $this->l('Send by email'),
                'callback' => 'printEmailIcon',
                'align' => 'text-center'
            ),
            'date_upd' => array(
                'title' => $this->l('Modified'),
                'align' => 'text-center'
            ),
            'profiles' => array(
                'title' => $this->l('Profile(s)'),
                'callback' => 'getProfiles',
                'align' => 'text-center'
            ),
        );

        if ((Tools::getAdminTokenLite($this->tabClassName) == Tools::getValue('token')) && Tools::getIsset('cron') && $bypass == false) {
            $class = new AdvancedReportsConfiguration();
            if ($class->processCron()) {
                $this->confirmations[] = $this->l('Cron executed successfully.');
            }
        }

        $this->shopLinkType = 'shop';
        if (version_compare(_PS_VERSION_, '1.6', '<')) {
            unset($this->fields_list['email']);
        }

        if (!$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminDashboard'));
        }

        if (Shop::isFeatureActive() && (Shop::getContext() == Shop::CONTEXT_ALL || Shop::getContext() == Shop::CONTEXT_GROUP)) {
            $this->can_add_reports = false;
        }
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addJqueryPlugin(array('typewatch', 'fancybox', 'autocomplete'));
    }

    public function initContent()
    {
        if ($this->action == 'select_delete') {
            $this->context->smarty->assign(array(
                'delete_form' => true,
                'url_delete' => htmlentities($_SERVER['REQUEST_URI']),
                'boxes' => $this->boxes,
            ));
        }
        if (!$this->can_add_reports && !$this->display) {
            $this->informations[] = $this->l('You have to select a shop if you want to create a new report.');
        }
        if (($id_report = Tools::getValue('id_advancedreports')) && Tools::getValue('generate_report') == '1') {
            $class = new AdvancedReportsConfiguration();
            $class->processExport();
        }
        if (($id_report = Tools::getValue('id_advancedreports')) && Tools::getIsset('duplicateadvancedreports')) {
            $this->processDuplicate();
        }
        if ($id_order = Tools::getValue('id_order') && Tools::getIsset('vieworder')) {
            Tools::redirectAdmin('index.php?controller=AdminOrders&vieworder&id_order='.Tools::getValue('id_order').'&token='.Tools::getAdminTokenLite('AdminOrders'));
        }
        if ($id_product = Tools::getValue('id_product') && Tools::getIsset('viewproduct')) {
            Tools::redirectAdmin('index.php?controller=AdminProducts&updateproduct&id_product='.Tools::getValue('id_product').'&token='.Tools::getAdminTokenLite('AdminProducts'));
        }
        if ($id_customer = Tools::getValue('id_customer') && Tools::getIsset('viewcustomer')) {
            Tools::redirectAdmin('index.php?controller=AdminCustomers&viewcustomer&id_customer='.Tools::getValue('id_customer').'&token='.Tools::getAdminTokenLite('AdminCustomers'));
        }
        if (($id_report = Tools::getValue('id_advancedreports')) && Tools::getValue('submitAddadvancedreports') == '1' && !Tools::getIsset('submitSaveReportAndContinue')) {
            if (version_compare(_PS_VERSION_, '1.6', '<')) {
                Tools::redirectAdmin('index.php?tab='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName).'&saved');
            } else {
                Tools::redirectAdmin('index.php?controller='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName).'&saved');
            }
        }
        if (Tools::getValue('submitSaveReportAndContinue') == '1') {
            $id_report = AdvancedReportsConfiguration::getLastReport($this->context->shop->id);
            $report = new AdvancedReportsConfiguration($id_report);
            if ($report->type != '3') {
                if (version_compare(_PS_VERSION_, '1.6', '<')) {
                    Tools::redirectAdmin('index.php?tab='.$this->tabReportsFieldsClassName.'&token='.Tools::getAdminTokenLite($this->tabReportsFieldsClassName).'&step=2&id_report='.$id_report);
                } else {
                    Tools::redirectAdmin('index.php?controller='.$this->tabReportsFieldsClassName.'&token='.Tools::getAdminTokenLite($this->tabReportsFieldsClassName).'&step=2&id_report='.$id_report);
                }
            }
        }
        if (($id_report = Tools::getValue('id_advancedreports')) && Tools::getIsset('updateadvancedreports') && !Tools::getIsset('step')) {
            $report = new AdvancedReportsConfiguration(Tools::getValue('id_advancedreports'));
            if ($report->type == '3') {
                $this->content .= $this->renderFormSQL();
            } else {
                $this->content .= $this->renderFormStep1();
            }
        } elseif (Tools::getIsSet('saved')) {
            $this->confirmations[] = $this->l('Configuration saved successfully.');
        } elseif (Tools::getValue('id_advancedreports') && !Tools::getIsSet('step') && !Tools::getIsSet('viewadvancedreports')) {
            $report = new AdvancedReportsConfiguration($id_report);
            if ($report->type != '3') {
                if (version_compare(_PS_VERSION_, '1.6', '<')) {
                    Tools::redirectAdmin('index.php?tab='.$this->tabReportsFieldsClassName.'&token='.Tools::getAdminTokenLite($this->tabReportsFieldsClassName).'&step=2&id_report='.Tools::getValue('id_advancedreports'));
                } else {
                    Tools::redirectAdmin('index.php?controller='.$this->tabReportsFieldsClassName.'&token='.Tools::getAdminTokenLite($this->tabReportsFieldsClassName).'&step=2&id_report='.Tools::getValue('id_advancedreports'));
                }
            }
        } elseif (Tools::getValue('step') == '3' && Tools::getIsSet('id_advancedreports')) {
            $this->content .= $this->renderFormStep3();
        } elseif (Tools::getIsSet('addadvancedreports')) {
            $this->content .= $this->renderFormStep1();
        } elseif (Tools::getIsSet('cancel')) {
            if (version_compare(_PS_VERSION_, '1.6', '<')) {
                Tools::redirectAdmin('index.php?tab='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName));
            } else {
                Tools::redirectAdmin('index.php?controller='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName));
            }
        }
        if ($this->display == 'view') {
            $this->content .= $this->renderView();
        }
        parent::initContent();
    }

    public function init()
    {
        parent::init();
        parent::initBreadcrumbs(Tab::getIdFromClassName($this->className));
    }

    public function initToolbar()
    {
        parent::initToolbar();

        if (!$this->can_add_reports) {
            unset($this->toolbar_btn['new']);
        }
    }

    public function addRowAction($action)
    {
        $action = Tools::strtolower($action);
        $this->actions[] = $action;
    }

    public function getList($id_lang, $orderBy = null, $orderWay = null, $start = 0, $limit = null, $id_lang_shop = null)
    {
        parent::getList($id_lang, $orderBy, $orderWay, $start, $limit, $id_lang_shop);
    }

    public function initToolbarTitle()
    {
        parent::initToolbarTitle();
        switch ($this->display) {
            case '':
            case 'list':
                array_pop($this->toolbar_title);
                $this->toolbar_title[] = $this->l('Manage Advanced Reports Configuration');
                break;
            case 'view':
                if (($report = $this->loadObject(true)) && Validate::isLoadedObject($report)) {
                    array_pop($this->toolbar_title);
                    $this->toolbar_title[] = sprintf($this->l('Report: %s'), $report->id_advancedreports.' - '.$report->name);
                }
                break;
            case 'add':
            case 'edit':
                array_pop($this->toolbar_title);
                if (($report = $this->loadObject(true)) && Validate::isLoadedObject($report)) {
                    $this->toolbar_title[] = sprintf($this->l('Editing report: %s'), $report->id_advancedreports.' - '.$report->name);
                } else {
                    $this->toolbar_title[] = $this->l('Creating a new report');
                }
                break;
        }
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['desc-module-back'] = array(
                'href' => 'index.php?controller=AdminModules&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back'),
                'icon' => 'process-icon-back'
            );
            $this->page_header_toolbar_btn['desc-module-new'] = array(
                'href' => 'index.php?controller='.$this->tabClassName.'&add'.$this->table.'&token='.Tools::getAdminTokenLite($this->tabClassName),
                'desc' => $this->l('New report'),
                'icon' => 'process-icon-new'
            );
            $this->page_header_toolbar_btn['desc-module-reload'] = array(
                'href' => 'index.php?controller='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName).'&reload=1',
                'desc' => $this->l('Reload'),
                'icon' => 'process-icon-refresh'
            );
            $this->page_header_toolbar_btn['desc-module-translate'] = array(
                'href' => '#',
                'desc' => $this->l('Translate'),
                'modal_target' => '#moduleTradLangSelect',
                'icon' => 'process-icon-flag'
            );
            $this->page_header_toolbar_btn['desc-module-hook'] = array(
                'href' => 'index.php?tab=AdminModulesPositions&token='.Tools::getAdminTokenLite('AdminModulesPositions').'&show_modules='.Module::getModuleIdByName($this->module_name),
                'desc' => $this->l('Manage hooks'),
                'icon' => 'process-icon-anchor'
            );
        } else {
            $this->page_header_toolbar_btn['desc-module-back'] = array(
                'href' => 'index.php?controller='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName),
                'desc' => $this->l('Back'),
                'icon' => 'process-icon-back'
            );
            $this->page_header_toolbar_btn['desc-module-reload'] = array(
                'href' => 'index.php?controller='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName).'&viewadvancedreports&id_advancedreports='.Tools::getValue('id_advancedreports'),
                'desc' => $this->l('Reload'),
                'icon' => 'process-icon-refresh'
            );
        }
        if (Tools::getIsset('updateadvancedreports')) {
            $this->page_header_toolbar_btn['desc-module-execute'] = array(
                'href' => 'index.php?controller='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName).'&viewadvancedreports&id_advancedreports='.Tools::getValue('id_advancedreports'),
                'desc' => $this->l('Execute report'),
                'icon' => 'process-icon-ok'
            );
        }
        if (Tools::getIsset('viewadvancedreports')) {
            $report = new AdvancedReportsConfiguration((int)Tools::getValue('id_advancedreports'));
            $this->page_header_toolbar_btn['desc-module-export'] = array(
                'href' => 'index.php?controller='.$this->tabClassName.'&id_advancedreports='.Tools::getValue('id_advancedreports').'&generate_report=1&token='.Tools::getAdminTokenLite($this->tabClassName),
                'desc' => ($report->format == '0' ? $this->l('Export XLS') : ($report->format == '2' ? $this->l('Export PDF') : $this->l('Export CSV'))),
                'icon' => 'process-icon-export'
            );
            $this->page_header_toolbar_btn['desc-module-edit'] = array(
                'href' => 'index.php?controller='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName).'&id_advancedreports='.Tools::getValue('id_advancedreports').'&updateadvancedreports',
                'desc' => $this->l('Edit this report'),
                'icon' => 'process-icon-edit'
            );
        }
        if (!$this->can_add_reports) {
            unset($this->page_header_toolbar_btn['desc-module-new']);
            unset($this->page_header_toolbar_btn['desc-module-translate']);
            unset($this->page_header_toolbar_btn['desc-module-hook']);
        }
    }

    public function initModal()
    {
        parent::initModal();

        $languages = Language::getLanguages(false);
        $translateLinks = array();

        if (version_compare(_PS_VERSION_, '1.7.2', '>=')) {
            $module = Module::getInstanceByName($this->module_name);
            $isNewTranslateSystem = $module->isUsingNewTranslationSystem();
            $link = Context::getContext()->link;
            foreach ($languages as $lang) {
                if ($isNewTranslateSystem) {
                    $translateLinks[$lang['iso_code']] = $link->getAdminLink('AdminTranslationSf', true, array(
                        'lang' => $lang['iso_code'],
                        'type' => 'modules',
                        'selected' => $module->name,
                        'locale' => $lang['locale'],
                    ));
                } else {
                    $translateLinks[$lang['iso_code']] = $link->getAdminLink('AdminTranslations', true, array(), array(
                        'type' => 'modules',
                        'module' => $module->name,
                        'lang' => $lang['iso_code'],
                    ));
                }
            }
        }

        $this->context->smarty->assign(array(
            'trad_link' => 'index.php?tab=AdminTranslations&token='.Tools::getAdminTokenLite('AdminTranslations').'&type=modules&module='.$this->module_name.'&lang=',
            'module_languages' => $languages,
            'module_name' => $this->module_name,
            'translateLinks' => $translateLinks,
        ));

        $modal_content = $this->context->smarty->fetch('controllers/modules/modal_translation.tpl');

        $this->modals[] = array(
            'modal_id' => 'moduleTradLangSelect',
            'modal_class' => 'modal-sm',
            'modal_title' => $this->l('Translate this module'),
            'modal_content' => $modal_content
        );
    }

    public function initProcess()
    {
        parent::initProcess();

        if (Tools::getIsset('reload')) {
            $this->action = 'reset_filters';
        }

        if (Tools::isSubmit('changeActiveVal') && $this->id_object) {
            if ($this->tabAccess['edit'] === '1') {
                $this->action = 'change_active_val';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        }
    }

    public function renderList()
    {
        if ((Tools::isSubmit('submitBulkdelete'.$this->table) || Tools::isSubmit('delete'.$this->table)) && $this->tabAccess['delete'] === '1') {
            $this->tpl_list_vars = array(
                'delete_report' => true,
                'REQUEST_URI' => $_SERVER['REQUEST_URI'],
                'POST' => $_POST
            );
        }
        $this->content .= $this->_createTemplate('header.tpl')->fetch();
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $href = $this->context->link->getAdminLink($this->tabClassName).'&cron';
        } else {
            $href = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].__PS_BASE_URI__.basename(_PS_ADMIN_DIR_).'/'.$this->context->link->getAdminLink($this->tabClassName).'&cron';
        }
        $this->informations[] = sprintf($this->l('To schedule report executions, you must add a call to %s in your cron manager.'), '<font color="red" weight="bold">'.(Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].__PS_BASE_URI__.'modules/'.$this->module->name.'/cron.php?cron&secure_key='.Tools::encrypt($this->module_name).'</font>').' <a href="'.$href.'" class="btn btn-default" title="'.$this->l('Execute now').'"><i class="icon-refresh"></i> '.$this->l('Execute now').'</a>';
        return parent::renderList();
    }

    public function renderOptions()
    {
        return parent::renderOptions();
    }

    public function renderFormStep1()
    {
        if (!($report = $this->loadObject(true))) {
            return;
        }

        $types = $this->getAdvancedReportsTypes();

        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Step 1/3: Select type and name for your report'),
                'icon' => 'icon-key'
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('Type'),
                    'name' => 'type',
                    'col' => 3,
                    'class' => 'fixed-width-xl',
                    'options' => array(
                        'query' => $types,
                        'id' => 'id',
                        'name' => 'name'
                    ),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Name'),
                    'name' => 'name',
                    'required' => true,
                    'col' => 3,
                    'hint' => $this->l('Internal name of the report')
                ),
                array(
                    'type' => (version_compare(_PS_VERSION_, '1.6', '>=')) ? 'datetime' : 'date',
                    'label' => $this->l('Day of year'),
                    'name' => 'frequency_year',
                    'col' => 5,
                    'size' => 10,
                    'disabled' => ($report->active == 1 ? false : true),
                    'hint' => $this->l('Day of year to auto execute this report. Only day and month of selected date will be considered')
                )
            )
        );

        $this->fields_form['submit'] = array(
            'title' => $this->l('Save and continue'),
            'name' => 'submitSaveReportAndContinue'
        );

        $this->tpl_form_vars['show_cancel_button'] = false;
        $this->fields_form['buttons'][] = array(
            'title' => $this->l('Back'),
            'icon' => 'process-icon-back',
            'js' => 'window.history.back();'
        );
        $this->fields_form['buttons'][] = array(
            'title' => $this->l('Cancel'),
            'icon' => 'process-icon-cancel',
            'href' => 'index.php?controller='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName).'&cancel'
        );

        $this->fields_form['input'][] = array(
                    'type' => 'hidden',
                    'name' => 'countries'
        );
        $this->fields_form['input'][] = array(
                    'type' => 'hidden',
                    'name' => 'zones'
        );
        $this->fields_form['input'][] = array(
                    'type' => 'hidden',
                    'name' => 'payments'
        );
        $this->fields_form['input'][] = array(
                    'type' => 'hidden',
                    'name' => 'groups'
        );
        $this->fields_form['input'][] = array(
                    'type' => 'hidden',
                    'name' => 'statuses'
        );
        $this->fields_form['input'][] = array(
                    'type' => 'hidden',
                    'name' => 'categories'
        );
        $this->fields_form['input'][] = array(
                    'type' => 'hidden',
                    'name' => 'manufacturers'
        );
        $this->fields_form['input'][] = array(
                    'type' => 'hidden',
                    'name' => 'suppliers'
        );
        $this->fields_form['input'][] = array(
                    'type' => 'hidden',
                    'name' => 'profiles'
        );
        $this->fields_form['input'][] = array(
                    'type' => 'hidden',
                    'name' => 'date_from'
        );
        $this->fields_form['input'][] = array(
                    'type' => 'hidden',
                    'name' => 'date_to'
        );
        $this->fields_form['input'][] = array(
                    'type' => 'hidden',
                    'name' => 'data_from'
        );
        $this->fields_form['input'][] = array(
                    'type' => 'hidden',
                    'name' => 'format'
        );
        $this->fields_form['input'][] = array(
                    'type' => 'hidden',
                    'name' => 'active'
        );
        $this->fields_form['input'][] = array(
                    'type' => 'hidden',
                    'name' => 'frequency'
        );
        $this->fields_form['input'][] = array(
                    'type' => 'hidden',
                    'name' => 'frequency_week'
        );
        $this->fields_form['input'][] = array(
                    'type' => 'hidden',
                    'name' => 'frequency_month'
        );
        $this->fields_form['input'][] = array(
                    'type' => 'hidden',
                    'name' => 'email'
        );

        if ($report->id) {
            $this->fields_value = array(
                'countries' => $report->countries,
                'zones' => $report->zones,
                'payments' => $report->payments,
                'groups' => $report->groups,
                'statuses' => $report->statuses,
                'categories' => $report->categories,
                'manufacturers' => $report->manufacturers,
                'suppliers' => $report->suppliers,
                'body_email' => $report->body_email,
                'profiles' => $report->profiles,
                'date_from' => $report->date_from,
                'date_to' => $report->date_to,
                'data_from' => $report->data_from,
                'format' => $report->format,
                'active' => $report->active,
                'frequency' => $report->frequency,
                'frequency_week' => $report->frequency_week,
                'frequency_month' => $report->frequency_month,
                'frequency_year' => $report->frequency_year,
                'email' => $report->email
            );
        }
    }

    public function renderFormSQL()
    {
        if (!($report = $this->loadObject(true))) {
            return;
        }

        $types = $this->getAdvancedReportsTypes();

        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('SQL Report Configuration'),
                'icon' => 'icon-key'
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('Type'),
                    'name' => 'type',
                    'readonly' => true,
                    'disabled' => true,
                    'col' => 3,
                    'class' => 'fixed-width-xl',
                    'options' => array(
                        'query' => $types,
                        'id' => 'id',
                        'name' => 'name'
                    ),
                ),

                array(
                    'type' => 'text',
                    'label' => $this->l('Name'),
                    'name' => 'name',
                    'required' => true,
                    'col' => 3,
                    'hint' => $this->l('Internal name of the report')
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('SQL Query'),
                    'name' => 'sql_query',
                    'cols' => 60,
                    'rows' => 10,
                    'col' => 5,
                    'readonly' => ($report->type == '3' ? false : true),
                    'hint' => array(
                        $this->l('Direct database SQL query.')
                    )
                )
            )
        );

        if ($report->id) {
            $this->getExtraConfigFields($report);
        }

        $this->fields_form['submit'] = array(
            'title' => $this->l('Save')
        );

        $this->tpl_form_vars['show_cancel_button'] = false;
        $this->fields_form['buttons'][] = array(
            'title' => $this->l('Back'),
            'icon' => 'process-icon-back',
            'js' => 'window.history.back();'
        );
        $this->fields_form['buttons'][] = array(
            'title' => $this->l('Cancel'),
            'icon' => 'process-icon-cancel',
            'href' => 'index.php?controller='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName).'&cancel'
        );

        if ($report->id) {
            $profiles_db = explode(',', $report->profiles);
            $this->fields_value = array(
                'profiles[]' => $profiles_db
            );
        }
        $this->fields_form['input'][] = array(
                    'type' => 'hidden',
                    'name' => 'controller_url'
        );
        $this->fields_value['controller_url'] = _PS_BASE_URL_.__PS_BASE_URI__.basename(_PS_ADMIN_DIR_).'/'.$this->context->link->getAdminLink($this->tabClassName);
    }

    public function renderFormStep3()
    {
        if (!($report = $this->loadObject(true))) {
            return;
        }

        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Step 3/3: Filters and other settings'),
                'icon' => 'icon-key'
            ),
            'input' => array()
        );

        if ($report->id) {
            $this->getReportFiltersFields($report);
            $this->getExtraConfigFields($report);
        }
        $this->fields_form['input'][] = array(
                    'type' => 'hidden',
                    'name' => 'cancelwithoutsave'
        );
        $this->fields_form['input'][] = array(
                    'type' => 'hidden',
                    'name' => 'controller_url'
        );
        $this->fields_value['controller_url'] = _PS_BASE_URL_.__PS_BASE_URI__.basename(_PS_ADMIN_DIR_).'/'.$this->context->link->getAdminLink($this->tabClassName);

        $this->fields_form['submit'] = array(
            'title' => $this->l('Save and exit')
        );
        $this->tpl_form_vars['current'] = self::$currentIndex.'&addadvancedreports';
        $this->tpl_form_vars['show_cancel_button'] = false;
        $this->fields_form['buttons'][] = array(
            'title' => $this->l('Back'),
            'icon' => 'process-icon-back',
            'js' => 'window.history.back();'
        );
        $this->fields_form['buttons'][] = array(
            'title' => $this->l('Cancel'),
            'icon' => 'process-icon-cancel',
            'href' => 'index.php?controller='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName).'&cancel'
        );
    }

    public function renderView()
    {
        if (!($report = $this->loadObject())) {
            return false;
        }
        $this->page_header_toolbar_btn['desc-module-back'] = array(
            'href' => 'index.php?controller='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName),
            'desc' => $this->l('Back'),
            'icon' => 'process-icon-back'
        );
        $this->page_header_toolbar_btn['desc-module-reload'] = array(
            'href' => 'index.php?controller='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName).'&id_advancedreports='.$report->id_advancedreports.'&viewadvancedreports',
            'desc' => $this->l('Reload'),
            'icon' => 'process-icon-refresh'
        );
        $this->page_header_toolbar_btn['desc-module-export'] = array(
            'href' => 'index.php?controller='.$this->tabClassName.'&id_advancedreports='.$report->id_advancedreports.'&generate_report=1&token='.Tools::getAdminTokenLite($this->tabClassName),
            'desc' => ($report->format == '0' ? $this->l('Export XLS') : ($report->format == '2' ? $this->l('Export PDF') : $this->l('Export CSV'))),
            'icon' => 'process-icon-export'
        );
        $this->page_header_toolbar_btn['desc-module-edit'] = array(
            'href' => 'index.php?controller='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName).'&id_advancedreports='.$report->id_advancedreports.'&updateadvancedreports',
            'desc' => $this->l('Edit this report'),
            'icon' => 'process-icon-edit'
        );
        if ($report->type == '3') {
            $this->content = $this->generateList($report, false);
            $this->context->smarty->assign(array(
                'advanced_reports_sql' => $report->sql_query,
            ));
            $this->content .= $this->_createTemplate('sql.tpl')->fetch();
        } else {
            if ($this->isAggregatedReport($report)) {
                $this->content = $this->generateList($report, true);
            } else {
                $this->content = $this->generateList($report);
            }
            $this->context->smarty->assign(array(
                'show_toolbar' => true,
                //'data' => 'data',
            ));
            $this->context->smarty->assign(array(
                'advanced_reports_sql' => $this->getSqlQuery($report),
            ));
            $this->content .= $this->_createTemplate('sql.tpl')->fetch();
        }
    }

    public function processDelete()
    {
        parent::processDelete();
    }

    public function processDuplicate()
    {
        $id_report = Tools::getValue($this->identifier);
        $report = new AdvancedReportsConfiguration($id_report);
        unset($report->id_advancedreports);
        if (!$report->add()) {
            $this->errors[] = Tools::displayError('An error occurred while duplicating the report #'.$id_report);
        } else {
            $this->duplicateReportFields($id_report, $report->id);
            $this->confirmations[] = sprintf($this->l('Report #%s - %s successfully duplicated.'), $id_report, $report->name);
            if (version_compare(_PS_VERSION_, '1.6', '<')) {
                Tools::redirectAdmin('index.php?tab='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName));
            } else {
                Tools::redirectAdmin('index.php?controller='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName));
            }
        }
    }

    protected function processBulkDelete()
    {
        parent::processBulkDelete();
    }

    public function processAdd()
    {
        if (Tools::getValue('submitFormAjax')) {
            $this->redirect_after = false;
        }
        $_POST['groups'] = (is_array(Tools::getValue('groups')) ? (in_array('all', Tools::getValue('groups')) ? 'all' : implode(',', Tools::getValue('groups'))) : (Tools::getValue('groups') == '' ? 'all' : Tools::getValue('groups')));
        $_POST['countries'] = (is_array(Tools::getValue('countries')) ? (in_array('all', Tools::getValue('countries')) ? 'all' : implode(',', Tools::getValue('countries'))) : (Tools::getValue('countries') == '' ? 'all' : Tools::getValue('countries')));
        $_POST['zones'] = (is_array(Tools::getValue('zones')) ? (in_array('all', Tools::getValue('zones')) ? 'all' : implode(',', Tools::getValue('zones'))) : (Tools::getValue('zones') == '' ? 'all' : Tools::getValue('zones')));
        $_POST['categories'] = (is_array(Tools::getValue('categories')) ? (in_array('all', Tools::getValue('categories')) ? 'all' : implode(',', Tools::getValue('categories'))) : (Tools::getValue('categories') == '' ? 'all' : Tools::getValue('categories')));
        $_POST['suppliers'] = (is_array(Tools::getValue('suppliers')) ? (in_array('all', Tools::getValue('suppliers')) ? 'all' : implode(',', Tools::getValue('suppliers'))) : (Tools::getValue('suppliers') == '' ? 'all' : Tools::getValue('suppliers')));
        $_POST['manufacturers'] = (is_array(Tools::getValue('manufacturers')) ? (in_array('all', Tools::getValue('manufacturers')) ? 'all' : implode(',', Tools::getValue('manufacturers'))) : (Tools::getValue('manufacturers') == '' ? 'all' : Tools::getValue('manufacturers')));
        $_POST['statuses'] = (is_array(Tools::getValue('statuses')) ? (in_array('all', Tools::getValue('statuses')) ? 'all' : implode(',', Tools::getValue('statuses'))) : (Tools::getValue('statuses') == '' ? 'all' : Tools::getValue('statuses')));
        $_POST['payments'] = (is_array(Tools::getValue('payments')) ? (in_array('all', Tools::getValue('payments')) ? 'all' : implode(',', Tools::getValue('payments'))) : (Tools::getValue('payments') == '' ? 'all' : Tools::getValue('payments')));
        $_POST['body_email'] = (is_array(Tools::getValue('body_email')) ? (in_array('', Tools::getValue('body_email')) ? 'all' : implode(',', Tools::getValue('body_email'))) : (Tools::getValue('body_email') == '' ? '' : Tools::getValue('body_email')));
        $_POST['profiles'] = (is_array(Tools::getValue('profiles')) ? (in_array('all', Tools::getValue('profiles')) ? 'all' : implode(',', Tools::getValue('profiles'))) : (Tools::getValue('profiles') == '' ? 'all' : Tools::getValue('profiles')));
        $_POST['data_from'] = (Tools::getValue('type') == '3' ? '99' : (Tools::getValue('data_from') == '' ? '98' : Tools::getValue('data_from')));
        $report = parent::processAdd();
        if (Tools::getValue('submitSaveReportAndContinue')) {
            if (version_compare(_PS_VERSION_, '1.6', '<')) {
                Tools::redirectAdmin('index.php?tab='.$this->tabReportsFieldsClassName.'&token='.Tools::getAdminTokenLite($this->tabReportsFieldsClassName).'&step=2&id_report='.Tools::getValue('id_advancedreports'));
            } else {
                Tools::redirectAdmin('index.php?controller='.$this->tabReportsFieldsClassName.'&token='.Tools::getAdminTokenLite($this->tabReportsFieldsClassName).'&step=2&id_report='.Tools::getValue('id_advancedreports'));
            }
        }
        return $report;
    }

    public function processUpdate()
    {
        if (Validate::isLoadedObject($this->object)) {
            $_POST['groups'] = (is_array(Tools::getValue('groups')) ? (in_array('all', Tools::getValue('groups')) ? 'all' : implode(',', Tools::getValue('groups'))) : (Tools::getValue('groups') == '' ? 'all' : Tools::getValue('groups')));
            $_POST['countries'] = (is_array(Tools::getValue('countries')) ? (in_array('all', Tools::getValue('countries')) ? 'all' : implode(',', Tools::getValue('countries'))) : (Tools::getValue('countries') == '' ? 'all' : Tools::getValue('countries')));
            $_POST['zones'] = (is_array(Tools::getValue('zones')) ? (in_array('all', Tools::getValue('zones')) ? 'all' : implode(',', Tools::getValue('zones'))) : (Tools::getValue('zones') == '' ? 'all' : Tools::getValue('zones')));
            $_POST['categories'] = (is_array(Tools::getValue('categories')) ? (in_array('all', Tools::getValue('categories')) ? 'all' : implode(',', Tools::getValue('categories'))) : (Tools::getValue('categories') == '' ? 'all' : Tools::getValue('categories')));
            $_POST['suppliers'] = (is_array(Tools::getValue('suppliers')) ? (in_array('all', Tools::getValue('suppliers')) ? 'all' : implode(',', Tools::getValue('suppliers'))) : (Tools::getValue('suppliers') == '' ? 'all' : Tools::getValue('suppliers')));
            $_POST['manufacturers'] = (is_array(Tools::getValue('manufacturers')) ? (in_array('all', Tools::getValue('manufacturers')) ? 'all' : implode(',', Tools::getValue('manufacturers'))) : (Tools::getValue('manufacturers') == '' ? 'all' : Tools::getValue('manufacturers')));
            $_POST['statuses'] = (is_array(Tools::getValue('statuses')) ? (in_array('all', Tools::getValue('statuses')) ? 'all' : implode(',', Tools::getValue('statuses'))) : (Tools::getValue('statuses') == '' ? 'all' : Tools::getValue('statuses')));
            $_POST['payments'] = (is_array(Tools::getValue('payments')) ? (in_array('all', Tools::getValue('payments')) ? 'all' : implode(',', Tools::getValue('payments'))) : (Tools::getValue('payments') == '' ? 'all' : Tools::getValue('payments')));
            $_POST['body_email'] = (is_array(Tools::getValue('body_email')) ? (in_array('', Tools::getValue('body_email')) ? '' : implode(',', Tools::getValue('body_email'))) : (Tools::getValue('body_email') == '' ? '' : Tools::getValue('body_email')));
            $_POST['profiles'] = (is_array(Tools::getValue('profiles')) ? (in_array('all', Tools::getValue('profiles')) ? 'all' : implode(',', Tools::getValue('profiles'))) : (Tools::getValue('profiles') == '' ? 'all' : Tools::getValue('profiles')));
            $_POST['data_from'] = (Tools::getValue('type') == '3' ? '99' : (Tools::getValue('data_from') == '' ? '98' : Tools::getValue('data_from')));
            return parent::processUpdate();
        } else {
            $this->errors[] = Tools::displayError('An error occurred while loading the object.').'
                <b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');
        }

    }

    public function postProcess()
    {
        return parent::postProcess();
    }

    public function processSave()
    {
        return parent::processSave();
    }

    protected function afterAdd($object)
    {
        $id_advancedreports = Tools::getValue('id_advancedreports');
        $this->afterUpdate($object, $id_advancedreports);
        return parent::afterAdd($object);
    }

    protected function afterUpdate($object, $id_advancedreports = false)
    {
        if ($id_advancedreports) {
            $report = new AdvancedReportsConfiguration((int)$id_advancedreports);
        } else {
            $report = new AdvancedReportsConfiguration((int)$object->id_advancedreports);
        }
        if (Validate::isLoadedObject($report)) {
            if ($report->type != '3' && !Tools::getIsset('submitSaveReportAndContinue')) {
                $report->groups = (is_array(Tools::getValue('groups')) ? (in_array('all', Tools::getValue('groups')) ? 'all' : implode(',', Tools::getValue('groups'))) : (Tools::getValue('groups') == '' ? 'all' : Tools::getValue('groups')));
                $report->countries = (is_array(Tools::getValue('countries')) ? (in_array('all', Tools::getValue('countries')) ? 'all' : implode(',', Tools::getValue('countries'))) : (Tools::getValue('countries') == '' ? 'all' : Tools::getValue('countries')));
                $report->zones = (is_array(Tools::getValue('zones')) ? (in_array('all', Tools::getValue('zones')) ? 'all' : implode(',', Tools::getValue('zones'))) : (Tools::getValue('zones') == '' ? 'all' : Tools::getValue('zones')));
                $report->categories = (is_array(Tools::getValue('categories')) ? (in_array('all', Tools::getValue('categories')) ? 'all' : implode(',', Tools::getValue('categories'))) : (Tools::getValue('categories') == '' ? 'all' : Tools::getValue('categories')));
                $report->suppliers = (is_array(Tools::getValue('suppliers')) ? (in_array('all', Tools::getValue('suppliers')) ? 'all' : implode(',', Tools::getValue('suppliers'))) : (Tools::getValue('suppliers') == '' ? 'all' : Tools::getValue('suppliers')));
                $report->manufacturers = (is_array(Tools::getValue('manufacturers')) ? (in_array('all', Tools::getValue('manufacturers')) ? 'all' : implode(',', Tools::getValue('manufacturers'))) : (Tools::getValue('manufacturers') == '' ? 'all' : Tools::getValue('manufacturers')));
                $report->statuses = (is_array(Tools::getValue('statuses')) ? (in_array('all', Tools::getValue('statuses')) ? 'all' : implode(',', Tools::getValue('statuses'))) : (Tools::getValue('statuses') == '' ? 'all' : Tools::getValue('statuses')));
                $report->payments = (is_array(Tools::getValue('payments')) ? (in_array('all', Tools::getValue('payments')) ? 'all' : implode(',', Tools::getValue('payments'))) : (Tools::getValue('payments') == '' ? 'all' : Tools::getValue('payments')));
                $report->body_email = (is_array(Tools::getValue('body_email')) ? (in_array('all', Tools::getValue('body_email')) ? 'all' : implode(',', Tools::getValue('body_email'))) : (Tools::getValue('body_email') == '' ? 'all' : Tools::getValue('body_email')));
                $report->profiles = (is_array(Tools::getValue('profiles')) ? (in_array('all', Tools::getValue('profiles')) ? 'all' : implode(',', Tools::getValue('profiles'))) : (Tools::getValue('profiles') == '' ? 'all' : Tools::getValue('profiles')));
            }
            if (!Tools::getIsset('submitSaveReportAndContinue')) {
                $report->profiles = (is_array(Tools::getValue('profiles')) ? (in_array('all', Tools::getValue('profiles')) ? 'all' : implode(',', Tools::getValue('profiles'))) : (Tools::getValue('profiles') == '' ? 'all' : Tools::getValue('profiles')));
            }
            $report->data_from = (Tools::getValue('type') == '3' ? '99' : (Tools::getValue('data_from') == '' ? '98' : Tools::getValue('data_from')));

            $report->save();


        }
        if (($id_report = Tools::getValue('id_advancedreports')) && Tools::getValue('submitAddadvancedreports') == '1' && !Tools::getIsset('submitSaveReportAndContinue')) {
            if (version_compare(_PS_VERSION_, '1.6', '<')) {
                Tools::redirectAdmin('index.php?tab='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName).'&saved');

            } else {
                Tools::redirectAdmin('index.php?controller='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName).'&saved');
            }
        }
        if (($id_report = Tools::getValue('id_advancedreports')) && Tools::getValue('submitSaveReportAndContinue') == '1' && Tools::getValue('type') != '3') {
            if (version_compare(_PS_VERSION_, '1.6', '<')) {
                Tools::redirectAdmin('index.php?tab='.$this->tabReportsFieldsClassName.'&token='.Tools::getAdminTokenLite($this->tabReportsFieldsClassName).'&step=2&id_report='.Tools::getValue('id_advancedreports'));

            } else {
                Tools::redirectAdmin('index.php?controller='.$this->tabReportsFieldsClassName.'&token='.Tools::getAdminTokenLite($this->tabReportsFieldsClassName).'&step=2&id_report='.Tools::getValue('id_advancedreports'));
            }
        }

        return parent::afterUpdate($object);
    }

    public function processChangeActiveVal()
    {
        $report = new AdvancedReportsConfiguration($this->id_object);

        if (!Validate::isLoadedObject($report)) {
            $this->errors[] = Tools::displayError('An error occurred while updating fee information.');
        }
        $report->active = $report->active ? 0 : 1;
        if (!$report->update()) {
            $this->errors[] = Tools::displayError('An error occurred while updating fee information.');
        }
        Tools::redirectAdmin(self::$currentIndex.'&token='.$this->token);
    }

    public function printActiveIcon($value, $report)
    {
        return '<a class="list-action-enable '.($value ? 'action-enabled' : 'action-disabled').'" href="index.php?'.htmlspecialchars('tab='.$this->tabClassName.'&id_advancedreports='.(int)$report['id_advancedreports'].'&changeActiveVal&token='.Tools::getAdminTokenLite($this->className)).'">'.($value ? '<i class="icon-check"></i>' : '<i class="icon-remove"></i>').'</a>';
    }

    public function printReportIcon($format)
    {
        if ($format == '0') {
            return '<img src="../modules/'.$this->module_name.'/views/img/excel-icon.png"/>';
        } elseif ($format == '2') {
            return '<img src="../modules/'.$this->module_name.'/views/img/pdf-icon.png"/>';
        } else {
            return '<img src="../modules/'.$this->module_name.'/views/img/csv-icon.png"/>';
        }
    }

    public function printEmailIcon($value)
    {
        return ($value ? '<i class="icon-check" title="'.$value.'"></i>' : '<i class="icon-remove"></i>');
    }

    public function displayEditReportLink($token, $id)
    {
        $report = new AdvancedReportsConfiguration($id);
        if ($report->type == '3') {
            $this->context->smarty->assign(array(
                'href' => self::$currentIndex.
                    '&id_advancedreports='.$id.'&updateadvancedreports&token='.Tools::getAdminTokenLite($this->tabClassName),
                'action' => $this->l('Edit report'),
            ));
        } else {
            $this->context->smarty->assign(array(
                'href' => self::$currentIndex.
                    '&'.$this->identifier.'='.$id.
                    '&updateadvancedreports&token='.($token != null ? $token : $this->token),
                'action' => $this->l('Edit report name and type'),
            ));
        }

        return $this->context->smarty->fetch('helpers/list/list_action_edit.tpl');
    }

    public function displayGenerateReportLink($token, $id)
    {
        $this->context->smarty->assign(array(
            'href' => self::$currentIndex.
                '&'.$this->identifier.'='.$id.
                '&generate_report=1&token='.($token != null ? $token : $this->token),
            'action' => $this->l('Export'),
        ));

        return $this->context->smarty->fetch('helpers/list/list_action_removestock.tpl');
    }

    public function displayEditFieldsLink($token, $id)
    {
        $report = new AdvancedReportsConfiguration($id);
        if ($report->type != '3') {
            $this->context->smarty->assign(array(
                //'href' => str_replace(Tools::strtolower($this->tabClassName), Tools::strtolower($this->tabReportsFieldsClassName), self::$currentIndex).
                //    '&step=2&id_report='.$id.'&token='.Tools::getAdminTokenLite($this->tabClassName),
                'href' => $this->context->link->getAdminLink($this->tabReportsFieldsClassName, false).
                '&step=2&id_report='.$id.'&token='.Tools::getAdminTokenLite($this->tabReportsFieldsClassName),
                'action' => $this->l('Edit report fields'),
            ));
        } else {
            return false;
        }

        return $this->context->smarty->fetch('helpers/list/list_action_edit.tpl');
    }

    public function displayEditOtherLink($token, $id)
    {
        $report = new AdvancedReportsConfiguration($id);
        if ($report->type != '3') {
            $this->context->smarty->assign(array(
                'href' => self::$currentIndex.
                    '&updateadvancedreports&step=3&id_advancedreports='.$id.'&token='.Tools::getAdminTokenLite($this->tabClassName),
                'action' => $this->l('Edit filters and other settings'),
            ));
        } else {
            return false;
        }

        return $this->context->smarty->fetch('helpers/list/list_action_edit.tpl');
    }

    public function displayDeleteLink($token = null, $id = 0, $name = null)
    {
        $tpl = $this->createTemplate('helpers/list/list_action_delete.tpl');
        $tpl->assign(array(
            'href' => self::$currentIndex.'&'.$this->identifier.'='.$id.'&delete'.$this->table.'&token='.($token != null ? $token : $this->token),
            'confirm' => $this->l('Delete the selected item?').$name,
            'action' => $this->l('Delete'),
            'id' => $id,
        ));

        return $tpl->fetch();
    }

    protected function getAdvancedReportsTypes()
    {
        $types = array($this->l('Custom'), $this->l('SQL Query'));
        $list_types = array(
            array('id' => '0', 'value' => '0', 'name' => $this->l('Custom')),
            array('id' => '3', 'value' => '3', 'name' => $this->l('SQL Query')));
        return $list_types;
    }

    public function getAdvancedReportsType($type)
    {
        if ($type == '0') {
            return $this->l('Custom');
        } elseif ($type == '3') {
            return $this->l('SQL Query');
        }
    }

    protected function getAdvancedReportsFormats()
    {
        $types = array($this->l('Excel'), $this->l('CSV'), $this->l('PDF'));

        $list_types = array();
        foreach ($types as $key => $type) {
            $list_types[$key]['id'] = $key;
            $list_types[$key]['value'] = $key;
            $list_types[$key]['name'] = $type;
        }
        return $list_types;
    }

    public function getAdvancedReportsFormat($type)
    {
        if ($type == '0') {
            return $this->l('Excel');
        } elseif ($type == '1') {
            return $this->l('CSV');
        } elseif ($type == '2') {
            return $this->l('PDF');
        }
    }

    protected function getAdvancedReportsFrequencies()
    {
        $types = array($this->l('Day'), $this->l('Week'), $this->l('Month'), $this->l('Year'));
        $list_types = array();
        foreach ($types as $key => $type) {
            $list_types[$key]['id'] = $key;
            $list_types[$key]['value'] = $key;
            $list_types[$key]['name'] = $type;
        }
        return $list_types;
    }

    public function getAdvancedReportsFrequency($type, $tr)
    {
        if ($type == '0') {
            return $this->l('Day');
        } elseif ($type == '1') {
            return sprintf($this->l('Week (at %s)'), Tools::strtolower($this->getAdvancedReportsFrequencyWeek($tr['frequency_week'])));
        } elseif ($type == '2') {
            return sprintf($this->l('Month (at day %s)'), ($tr['frequency_month'] == '99' ? $this->l('last') : $tr['frequency_month']));
        } elseif ($type == '3') {
            return sprintf($this->l('Year (at %s)'), date('M, d', strtotime($tr['frequency_year'])));
        }
        return '';
    }

    protected function getAdvancedReportsFrequenciesWeek()
    {
        //$types = array($this->l('Monday'), $this->l('Tuesday'), $this->l('Wednesday'), $this->l('Thursday'), $this->l('Friday'), $this->l('Saturday'), $this->l('Sunday'));
        $types = array($this->l('Sunday'), $this->l('Monday'), $this->l('Tuesday'), $this->l('Wednesday'), $this->l('Thursday'), $this->l('Friday'), $this->l('Saturday'));
        $list_types = array();
        foreach ($types as $key => $type) {
            $list_types[$key]['id'] = $key;
            $list_types[$key]['value'] = $key;
            $list_types[$key]['name'] = $type;
        }
        return $list_types;
    }

    public function getAdvancedReportsFrequencyWeek($type)
    {
        if ($type == '0') {
            return $this->l('Sunday');
        } elseif ($type == '1') {
            return $this->l('Monday');
        } elseif ($type == '2') {
            return $this->l('Tuesday');
        } elseif ($type == '3') {
            return $this->l('Wednesday');
        } elseif ($type == '4') {
            return $this->l('Thursday');
        } elseif ($type == '5') {
            return $this->l('Friday');
        } elseif ($type == '6') {
            return $this->l('Saturday');
        }
    }

    protected function getAdvancedReportsFrequenciesMonth()
    {
        $list_days = array();
        for ($i = 1; $i <= 31; $i++) {
            $list_days[$i]['id'] = $i;
            $list_days[$i]['value'] = $i;
            $list_days[$i]['name'] = $i;
        }
        $list_days[99]['id'] = 99;
        $list_days[99]['value'] = 99;
        $list_days[99]['name'] = $this->l('Last day of month');
        return $list_days;
    }

    protected function getAdvancedReportsDataFroms()
    {
        $types = array(
            $this->l('Current day'),
            $this->l('Yesterday'),
            $this->l('Current week'),
            $this->l('Last week'),
            $this->l('Current month'),
            $this->l('Last month'),
            $this->l('Current quarter'),
            $this->l('Last quarter'),
            $this->l('Current year'),
            $this->l('Last year'),
            $this->l('All')
        );

        $list_types = array();
        foreach ($types as $key => $type) {
            $list_types[$key]['id'] = $key;
            $list_types[$key]['value'] = $key;
            $list_types[$key]['name'] = $type;
        }
        return $list_types;
    }

    public function getAdvancedReportsDataFrom($type)
    {
        if ($type == '0') {
            return $this->l('Current day');
        } elseif ($type == '1') {
            return $this->l('Yesterday');
        } elseif ($type == '2') {
            return $this->l('Current week');
        } elseif ($type == '3') {
            return $this->l('Last week');
        } elseif ($type == '4') {
            return $this->l('Current month');
        } elseif ($type == '5') {
            return $this->l('Last month');
        } elseif ($type == '6') {
            return $this->l('Current quarter');
        } elseif ($type == '7') {
            return $this->l('Last quarter');
        } elseif ($type == '8') {
            return $this->l('Current year');
        } elseif ($type == '9') {
            return $this->l('Last year');
        } elseif ($type == '10') {
            return $this->l('All');
        } elseif ($type == '98') {
            return $this->l('By fixed dates');
        } else {
            return $this->l('By SQL');
        }
    }

    public function getCustomerGroups($ids_customer_groups)
    {
        if ($ids_customer_groups === 'all') {
            return $this->l('All');
        }
        $groups = array();
        $groups_array = explode(',', $ids_customer_groups);
        foreach ($groups_array as $key => $group) {
            if ($key == $this->top_elements_in_list) {
                $groups[] = $this->l('...and more');
                break;
            }
            $group = new Group($group, $this->context->language->id);
            $groups[] = $group->name;
        }
        return implode('<br />', $groups);
    }

    public function getCountries($ids_countries)
    {
        if ($ids_countries === 'all') {
            return $this->l('All');
        }
        $countries = array();
        $countries_array = explode(',', $ids_countries);
        foreach ($countries_array as $key => $country) {
            if ($key == $this->top_elements_in_list) {
                $countries[] = $this->l('...and more');
                break;
            }
            $country = new Country($country, $this->context->language->id);
            $countries[] = $country->name;
        }
        return implode('<br />', $countries);
    }

    public function getZones($ids_zones)
    {
        if ($ids_zones === 'all') {
            return $this->l('All');
        }
        $zones = array();
        $zones_array = explode(',', $ids_zones);
        foreach ($zones_array as $key => $zone) {
            if ($key == $this->top_elements_in_list) {
                $zones[] = $this->l('...and more');
                break;
            }
            $zone = new Zone($zone, $this->context->language->id);
            $zones[] = $zone->name;
        }
        return implode('<br />', $zones);
    }

    public function getCategories($ids_categories)
    {
        if ($ids_categories === 'all') {
            return $this->l('All');
        }
        $categories = array();
        $categories_array = explode(',', $ids_categories);
        foreach ($categories_array as $key => $category) {
            if ($key == $this->top_elements_in_list) {
                $categories[] = $this->l('...and more');
                break;
            }
            $category = new Category($category, $this->context->language->id);
            $categories[] = $category->name;
        }
        return implode('<br />', $categories);
    }

    public function getProfiles($ids_profiles)
    {
        if ($ids_profiles === 'all') {
            return $this->l('All');
        }
        $profiles = array();
        $profiles_array = explode(',', $ids_profiles);
        foreach ($profiles_array as $key => $profile) {
            if ($key == $this->top_elements_in_list) {
                $profiles[] = $this->l('...and more');
                break;
            }
            $profile = new Profile($profile, $this->context->language->id);
            $profiles[] = $profile->name;
        }
        return implode('<br />', $profiles);
    }

    private function _createTemplate($tpl_name)
    {
        if ($this->override_folder) {
            if ($this->context->controller instanceof ModuleAdminController) {
                $override_tpl_path = $this->context->controller->getTemplatePath().$tpl_name;
            } elseif ($this->module) {
                $override_tpl_path = _PS_MODULE_DIR_.$this->module_name.'/views/templates/admin/'.$tpl_name;
            } else {
                if (file_exists($this->context->smarty->getTemplateDir(1).DIRECTORY_SEPARATOR.$this->override_folder.$this->base_folder.$tpl_name)) {
                    $override_tpl_path = $this->context->smarty->getTemplateDir(1).DIRECTORY_SEPARATOR.$this->override_folder.$this->base_folder.$tpl_name;
                } elseif (file_exists($this->context->smarty->getTemplateDir(0).DIRECTORY_SEPARATOR.'controllers'.DIRECTORY_SEPARATOR.$this->override_folder.$this->base_folder.$tpl_name)) {
                    $override_tpl_path = $this->context->smarty->getTemplateDir(0).'controllers'.DIRECTORY_SEPARATOR.$this->override_folder.$this->base_folder.$tpl_name;
                }
            }
        } else if ($this->module) {
            $override_tpl_path = _PS_MODULE_DIR_.$this->module_name.'/views/templates/admin/'.$tpl_name;
        }
        if (isset($override_tpl_path) && file_exists($override_tpl_path)) {
            return $this->context->smarty->createTemplate($override_tpl_path, $this->context->smarty);
        } else {
            return $this->context->smarty->createTemplate($tpl_name, $this->context->smarty);
        }
    }

    private function _formValidations()
    {
        if (trim(Tools::getValue('name')) == '') {
            $this->validateRules();
            $this->errors[] = Tools::displayError($this->l('Field \'Name\' can not be empty.'));
            $this->display = 'edit';
        }
        if (trim(Tools::getValue('type')) == '') {
            $this->validateRules();
            $this->errors[] = Tools::displayError($this->l('Field \'Type\' can not be empty.'));
            $this->display = 'edit';
        }
        if (Tools::getValue('profiles') == '') {
            $this->validateRules();
            $this->errors[] = Tools::displayError($this->l('Field \'Profile(s)\' can not be empty.'));
            $this->display = 'edit';
        }
        if (Tools::getValue('format') == '') {
            $this->validateRules();
            $this->errors[] = Tools::displayError($this->l('Field \'Format\' can not be empty.'));
            $this->display = 'edit';
        }
        if (Tools::getValue('active') == '1') {
            if (trim(Tools::getValue('frequency')) == '') {
                $this->validateRules();
                $this->errors[] = Tools::displayError($this->l('Field \'Execute every\' can not be empty.'));
                $this->display = 'edit';
            }
        }
        if (trim(Tools::getValue('type')) == '3') {
            if (trim(Tools::getValue('sql_query')) == '') {
                $this->validateRules();
                $this->errors[] = Tools::displayError($this->l('Field \'SQL Query\' can not be empty.'));
                $this->display = 'edit';
            }
        } elseif (trim(Tools::getValue('type')) == '99') {
            if (Tools::getValue('countries') == '') {
                $this->validateRules();
                $this->errors[] = Tools::displayError($this->l('Field \'Country(ies)\' can not be empty.'));
                $this->display = 'edit';
            }
            if (Tools::getValue('zones') == '') {
                $this->validateRules();
                $this->errors[] = Tools::displayError($this->l('Field \'Zone(s)\' can not be empty.'));
                $this->display = 'edit';
            }
            if (Tools::getValue('payments') == '') {
                $this->validateRules();
                $this->errors[] = Tools::displayError($this->l('Field \'Payment method(s)\' can not be empty.'));
                $this->display = 'edit';
            }
            if (Tools::getValue('groups') == '') {
                $this->validateRules();
                $this->errors[] = Tools::displayError($this->l('Field \'Customer group(s)\' can not be empty.'));
                $this->display = 'edit';
            }
            if (Tools::getValue('categories') == '') {
                $this->validateRules();
                $this->errors[] = Tools::displayError($this->l('Field \'Category(ies)\' can not be empty.'));
                $this->display = 'edit';
            }
            if (Tools::getValue('manufacturers') == '') {
                $this->validateRules();
                $this->errors[] = Tools::displayError($this->l('Field \'Manufacturer(s)\' can not be empty.'));
                $this->display = 'edit';
            }
            if (Tools::getValue('suppliers') == '') {
                $this->validateRules();
                $this->errors[] = Tools::displayError($this->l('Field \'Supplier(s)\' can not be empty.'));
                $this->display = 'edit';
            }
            if (Tools::getValue('statuses') == '') {
                $this->validateRules();
                $this->errors[] = Tools::displayError($this->l('Field \'Order status(es)\' can not be empty.'));
                $this->display = 'edit';
            }
            if (Tools::getValue('fields') == '') {
                $this->validateRules();
                $this->errors[] = Tools::displayError($this->l('Field \'Field(s)\' can not be empty.'));
                $this->display = 'edit';
            }
        }
    }

    private function filterByEmployee()
    {
        if ($this->context->employee->id_profile != 1) {
            return 'AND (`profiles` LIKE \''.$this->context->employee->id_profile.'\'
                    OR `profiles` LIKE \''.$this->context->employee->id_profile.',%\'
                    OR `profiles` LIKE \'%,'.$this->context->employee->id_profile.',%\'
                    OR `profiles` LIKE \'%,'.$this->context->employee->id_profile.'\'
                    OR `profiles` LIKE \'%all%\')';
        }
        return null;
    }

    protected function getFieldsDefinition($type = '', $form = false)
    {
        if ($type == 'orders') {
            $fields_definition = Order::$definition;
        } elseif ($type == 'order_detail') {
            $fields_definition = OrderDetail::$definition;
        } elseif ($type == 'customer') {
            $fields_definition = Customer::$definition;
        } else {
            return $fields_definition = array('table' => '', 'primary' => '', 'fields' => array($this->l('-- Choose table --') => ''));
        }
        $fields_definition['fields'] = array_merge(array($fields_definition['primary'] => array('type' => 'int')), $fields_definition['fields']);
        return $fields_definition;
    }

    public function generateList($report, $pdf = false)
    {
        $helper = new HelperList();
        $helper->bootstrap = true;
        $helper->show_toolbar = ($pdf) ? false : true;
        $helper->list_id = 'report_'.$report->id_advancedreports;
        $helper->shopLinkType = '';
        if (version_compare(_PS_VERSION_, '1.6.0.9', '<=')) {
            $helper->pagination = array(1000000, 2000000, 3000000);
            $helper->default_pagination = 1000000;
        } else {
            $helper->_pagination = array(1000000, 2000000, 3000000);
            $helper->_default_pagination = 1000000;
        }
        $helper->token = $this->token;
        $helper->table = $this->module_name;
        $helper->currentIndex = self::$currentIndex.'&id_advancedreports='.$report->id_advancedreports.'&viewadvancedreports';
        $results = array();
        $fields_list = array();
        if ($report->type == '3') {
            $total = 0;
            try {
                $queries = explode(';', $report->sql_query);
                foreach ($queries as $query) {
                    $query = trim($query);
                    if ($query != '') {
                        if (Tools::strtolower(substr($query, 0, 6)) == 'select' || Tools::strtolower(substr($query, 0, 7)) == '(select') {
                            if ($results = array_merge($results, Db::getInstance()->executeS($query))) {
                                $total = $total + count($results);
                                foreach (array_keys($results[0]) as $key) {
                                    if ($this->isFloatField($key)) {
                                        $fields_list[$key] = array('title' => $key, 'type' => 'float');
                                    } else {
                                        $fields_list[$key] = array('title' => $key, 'type' => 'text');
                                    }
                                }
                            }
                        } else {
                            $res = Db::getInstance()->execute($query);
                        }
                    }
                }
                $this->_list = $results;
            } catch (Exception $e) {
                $reportClass = new AdvancedReports();
                $error = explode(';', $e->getMessage());
                if ($error[0] = 'You have an error in your SQL syntax') {
                    $error = sprintf($reportClass->l('There was an error in your report SQL query. Detailed error: %s'), $e->getMessage());
                }
                $fields_list['ERROR'] = array('title' => 'ERROR', 'type' => 'text');
                $results = array(array('ERROR' => str_replace('Db->executeS() must be used only with', $reportClass->l('Use only'), $error)));
            }
            if ($this->inArrayR('id_order', $fields_list)) {
                $helper->table = 'orders';
                $helper->currentIndex = $this->context->link->getAdminLink($this->tabClassName, false).'&vieworder';
                $helper->identifier = 'id_order';
                $helper->actions = ($pdf ? array() : array('view'));
            } elseif ($this->inArrayR('id_customer', $fields_list)) {
                $helper->table = 'customer';
                $helper->currentIndex = $this->context->link->getAdminLink($this->tabClassName, false).'&viewcustomer';
                $helper->identifier = 'id_customer';
                $helper->actions = ($pdf ? array() : array('view'));
            } elseif ($this->inArrayR('id_product', $fields_list)) {
                $helper->table = 'product';
                $helper->currentIndex = $this->context->link->getAdminLink($this->tabClassName, false).'&updateproduct';
                $helper->identifier = 'id_product';
                $helper->actions = ($pdf ? array() : array('view'));
            } else {
                $helper->currentIndex = $this->context->link->getAdminLink($this->tabClassName, false);
                $helper->identifier = (version_compare(_PS_VERSION_, '1.6', '<') ? '' : '');
            }
            $helper->simple_header = true;
            $helper->listTotal = $total;
            $helper->no_link = true;
            $helper->title = sprintf($this->l('Results of report %s (%s)'), $report->name, $helper->listTotal);
        } else {
            $helper->simple_header = true;
            $helper->no_link = ($pdf ? true : false);
            //$fields_definition = $this->getFieldsDefinition($report->type);
            $helper->table = AdvancedReportsConfiguration::getReportPrimaryTable($report->id_advancedreports);
            $helper->identifier = AdvancedReportsConfiguration::getReportPrimaryIdentifier($report->id_advancedreports);
            $primary_field = AdvancedReportsConfiguration::getReportPrimaryField($report->id_advancedreports);
            $helper->actions = ($pdf ? array() : array('view'));
            //$fields = explode(',', $report->fields);
            $conf = new AdvancedReportsConfiguration();
            if (Tools::getValue('show_sql') && Tools::getValue('show_sql') == '1') {
                die($conf->getReportResults($report, true));
            }
            if ($results = $conf->getReportResults($report)) {
                if (isset($results[0]['ERROR'])) {
                    $fields_list['ERROR'] = array('title' => 'ERROR', 'type' => 'text');
                    unset($this->page_header_toolbar_btn['desc-module-export']);
                } else {
                    foreach (array_keys($results[0]) as $key) {
                        if ($this->isFloatField($key)) {
                            $fields_list[$key] = array('title' => $key, 'type' => 'float');
                        } else {
                            $fields_list[$key] = array('title' => $key, 'type' => 'text');
                        }
                    }
                }
            }
            if ($helper->table == 'orders' || $helper->table == 'order_detail') {
                $helper->table = 'orders';
                $helper->currentIndex = $this->context->link->getAdminLink($this->tabClassName, false).'&vieworder';
                $helper->identifier = 'id_order';
            } elseif ($helper->table == 'customer') {
                $helper->currentIndex = $this->context->link->getAdminLink($this->tabClassName, false).'&viewcustomer';
                $helper->identifier = 'id_customer';
            } elseif ($helper->table == 'product') {
                $helper->currentIndex = $this->context->link->getAdminLink($this->tabClassName, false).'&updateproduct';
                $helper->identifier = 'id_product';
            } else {
                $helper->currentIndex = $this->context->link->getAdminLink($this->tabClassName, false);
            }
            if ($primary_field == 'customer_group') {
                $helper->table = 'customer_group';
                $helper->simple_header = true;
                $helper->no_link = true;
                $helper->actions = array();
            }
            $this->_list = $results;
            $helper->listTotal = count($results);
            $helper->title = ($pdf ? '' : sprintf($this->l('Results of report %s (%s)'), $report->name, $helper->listTotal));
        }
        if ($report->format == '2' && count($results) == 0) {
            return '<br><br><br><br><br>'.$this->l('No records found.');
        }
        return $helper->generateList($results, $fields_list);
    }

    protected function getSqlQuery($report)
    {
        $conf = new AdvancedReportsConfiguration();
        return $conf->getReportResults($report, true);
    }

    protected function isFloatField($field)
    {
        $words = array('price', 'precio', 'total', 'weight', 'pvp', 'amount');
        $i = 0;
        foreach ($words as $word) {
            if (strpos(strtolower($field), $word) !== false) {
                $i++;
            }
        }
        return ($i > 0 ) ? true : false;
    }

    protected function replaceKeysInArray($array, $key1, $key2)
    {
        $keys = array_keys($array);
        $index = array_search($key1, $keys);
        if ($index !== false) {
            $keys[$index] = $key2;
            $array = array_combine($keys, $array);
        }
        return $array;
    }

    protected function inArrayR($needle, $haystack, $strict = false)
    {
        foreach ($haystack as $item) {
            if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && $this->inArrayR($needle, $item, $strict))) {
                return true;
            }
        }

        return false;
    }

    protected function getExtraConfigFields($report)
    {
        $formats = $this->getAdvancedReportsFormats();
        $frequencies = $this->getAdvancedReportsFrequencies();
        $frequencies_week = $this->getAdvancedReportsFrequenciesWeek();
        $frequencies_month = $this->getAdvancedReportsFrequenciesMonth();
        $profiles = array(array('id_profile' => 'all', 'name' => $this->l('-- All --')));
        $profiles = array_merge($profiles, Profile::getProfiles((int)($this->context->language->id)));

        $this->fields_form['input'][] = array(
            'type' => 'textarea',
            'label' => $this->l('Body Email'),
            'name' => 'body_email',
            'cols' => 60,
            'rows' => 10,
            'col' => 5,
            'hint' => $this->l('Enter the SQL query to include in the body of the email. Use ## to separate multiple queries.'),
        );

        $this->fields_form['input'][] = array(
            'type' => 'select',
            'label' => $this->l('Profile(s)'),
            'name' => 'profiles[]',
            'multiple' => true,
            'col' => 5,
            'class' => 'fixed-width-md',
            'options' => array(
                'query' => $profiles,
                'id' => 'id_profile',
                'name' => 'name'
            ),
            'hint' => $this->l('Employee profile(s) with permissions to view and execute this report')
        );
        $this->fields_form['input'][] = array(
            'type' => 'select',
            'label' => $this->l('Format'),
            'name' => 'format',
            'col' => 3,
            'class' => 'fixed-width-md',
            'options' => array(
                'query' => $formats,
                'id' => 'id',
                'name' => 'name'
            ),
            'hint' => $this->l('Format to export this report')
        );
        $this->fields_form['input'][] = array(
            'type' => (version_compare(_PS_VERSION_, '1.6', '>=')) ? 'switch' : 'radio',
            'label' => $this->l('Active auto generation'),
            'name' => 'active',
            'class' => 't',
            'col' => 5,
            'is_bool' => true,
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $this->l('Enabled')
                ),
                array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->l('Disabled')
                )
            ),
            'hint' => $this->l('Enable or disable report auto generation.')
        );
        $this->fields_form['input'][] = array(
            'type' => 'select',
            'label' => $this->l('Execute every'),
            'name' => 'frequency',
            'col' => 3,
            'class' => 'fixed-width-md',
            'disabled' => ($report->active == 1 ? false : true),
            'options' => array(
                'query' => $frequencies,
                'id' => 'id',
                'name' => 'name',
                'default' => array(
                    'value' => '',
                    'label' => $this->l('-- Choose --')
                )
            ),
            'hint' => $this->l('Frequency to auto execute this report')
        );
        $this->fields_form['input'][] = array(
            'type' => 'select',
            'label' => $this->l('Day of week'),
            'name' => 'frequency_week',
            'col' => 3,
            'class' => 'fixed-width-md',
            'disabled' => ($report->active == 1 ? false : true),
            'options' => array(
                'query' => $frequencies_week,
                'id' => 'id',
                'name' => 'name',
                'default' => array(
                    'value' => '',
                    'label' => $this->l('-- Choose --')
                )
            ),
            'hint' => $this->l('Day of week to auto execute this report')
        );
        $this->fields_form['input'][] = array(
            'type' => 'select',
            'label' => $this->l('Day of month'),
            'name' => 'frequency_month',
            'col' => 3,
            'class' => 'fixed-width-md',
            'disabled' => ($report->active == 1 ? false : true),
            'options' => array(
                'query' => $frequencies_month,
                'id' => 'id',
                'name' => 'name',
                'default' => array(
                    'value' => '',
                    'label' => $this->l('-- Choose --')
                )
            ),
            'hint' => $this->l('Day of month to auto execute this report')
        );
        $this->fields_form['input'][] = array(
            'type' => (version_compare(_PS_VERSION_, '1.6', '>=')) ? 'datetime' : 'date',
            'label' => $this->l('Day of year'),
            'name' => 'frequency_year',
            'col' => 5,
            'size' => 10,
            'disabled' => ($report->active == 1 ? false : true),
            'hint' => $this->l('Day of year to auto execute this report. Only day and month of selected date will be considered')
        );
        $this->fields_form['input'][] = array(
            'type' => 'text',
            'label' => $this->l('Email(s)'),
            'name' => 'email',
            'disabled' => ($report->active == 1 ? false : true),
            'prefix' => '<i class="icon icon-envelope"></i>',
            'col' => 4,
            'hint' => $this->l('You can enter one or more addresses separated by comma'),
            'desc' => $this->l('Enter a valid email address which will receive this report. Leave blank to disable'),
        );
        $this->fields_form['input'][] = array(
            'type' => 'hidden',
            'name' => 'type'
        );
    }

    public function getReportFiltersFields($report = false)
    {
        if (!$report) {
            return;
        }

        $primarytable = AdvancedReportsConfiguration::getReportPrimaryTable($report->id_advancedreports);
        $data_froms = $this->getAdvancedReportsDataFroms();

        $groups = array(array('id_group' => 'all', 'name' => $this->l('-- All --')));
        $zones = array(array('id_zone' => 'all', 'name' => $this->l('-- All --')));
        $payments = array(array('id_module' => 'all', 'name' => $this->l('-- All --')));
        $countries = array(array('id_country' => 'all', 'name' => $this->l('-- All --')));
        $categories = array(array('id_category' => 'all', 'name' => $this->l('-- All --')));
        $manufacturers = array(array('id_manufacturer' => 'all', 'name' => $this->l('-- All --')));
        $suppliers = array(array('id_supplier' => 'all', 'name' => $this->l('-- All --')));
        $statuses = array(array('id_order_state' => 'all', 'name' => $this->l('-- All --')));
        $statuses = array_merge($statuses, OrderState::getOrderStates((int)$this->context->language->id));
        $groups = array_merge($groups, Group::getGroups($this->context->language->id, true));
        $categories = array_merge($categories, Category::getCategories((int)($this->context->language->id), false, false));
        $countries = array_merge($countries, Country::getCountries((int)($this->context->language->id)));
        $manufacturers = array_merge($manufacturers, Manufacturer::getManufacturers(false, (int)($this->context->language->id), false));
        $suppliers = array_merge($suppliers, Supplier::getSuppliers(false, (int)($this->context->language->id), false));
        $modules_list = Module::getPaymentModules();
        foreach ($modules_list as $module) {
            $moduleObj = (version_compare(_PS_VERSION_, '1.6', '>=') ? Module::getInstanceById($module['id_module']) : Module::getInstanceByName($module['name']));
            if (is_object($moduleObj)) {
                $payments[] = array('id_module' => $module['name'], 'name' => $moduleObj->displayName);
            }
        }

        if ($primarytable != 'product') {
            $this->fields_form['input'][] = array(
                'type' => 'select',
                'label' => $this->l('Country(ies)'),
                'name' => 'countries[]',
                'multiple' => true,
                'col' => 5,
                'class' => 'fixed-width-md',
                'disabled' => ($report->type == '3' ? true : false),
                'options' => array(
                    'query' => $countries,
                    'id' => 'id_country',
                    'name' => 'name'
                ),
                'hint' => $this->l('Country(ies) filter')
            );
            $this->fields_form['input'][] = array(
                'type' => 'select',
                'label' => $this->l('Zone(s)'),
                'name' => 'zones[]',
                'multiple' => true,
                'col' => 5,
                'class' => 'fixed-width-md',
                'disabled' => ($report->type == '3' ? true : false),
                'options' => array(
                    'query' => array_merge($zones, Zone::getZones()),
                    'id' => 'id_zone',
                    'name' => 'name'
                ),
                'hint' => $this->l('Zone(s) filter')
            );
            $this->fields_form['input'][] = array(
                'type' => 'select',
                'label' => $this->l('Payment method(s)'),
                'name' => 'payments[]',
                'multiple' => true,
                'col' => 5,
                'class' => 'fixed-width-md',
                'disabled' => ($report->type == '3' ? true : false),
                'options' => array(
                    'query' => $payments,
                    'id' => 'id_module',
                    'name' => 'name'
                ),
                'hint' => $this->l('Payment method(s) filter')
            );
            $this->fields_form['input'][] = array(
                'type' => 'select',
                'label' => $this->l('Customer group(s)'),
                'name' => 'groups[]',
                'multiple' => true,
                'col' => 5,
                'class' => 'fixed-width-md',
                'disabled' => ($report->type == '3' ? true : false),
                'options' => array(
                    'query' => $groups,
                    'id' => 'id_group',
                    'name' => 'name'
                ),
                'hint' => $this->l('Customer group(s) filter')
            );
            $this->fields_form['input'][] = array(
                'type' => 'select',
                'label' => $this->l('Order status(es)'),
                'name' => 'statuses[]',
                'multiple' => true,
                'col' => 5,
                'class' => 'fixed-width-md',
                'disabled' => ($report->type == '3' ? true : false),
                'options' => array(
                    'query' => $statuses,
                    'id' => 'id_order_state',
                    'name' => 'name'
                ),
                'hint' => $this->l('Order status(es) to filter')
            );
        } else {
            $this->fields_form['input'][] = array(
                'type' => 'select',
                'label' => $this->l('Category(ies)'),
                'name' => 'categories[]',
                'multiple' => true,
                'col' => 5,
                'class' => 'fixed-width-md',
                'disabled' => ($report->type == '3' || $report->type == '99' ? true : false),
                'options' => array(
                    'query' => $categories,
                    'id' => 'id_category',
                    'name' => 'name'
                ),
                'hint' => $this->l('Category(ies) filter')
            );
            $this->fields_form['input'][] = array(
                'type' => 'select',
                'label' => $this->l('Manufacturer(s)'),
                'name' => 'manufacturers[]',
                'multiple' => true,
                'col' => 5,
                'class' => 'fixed-width-md',
                'disabled' => ($report->type == '3'  ? true : false),
                'options' => array(
                    'query' => $manufacturers,
                    'id' => 'id_manufacturer',
                    'name' => 'name'
                ),
                'hint' => $this->l('Manufacturer(s) filter')
            );
            $this->fields_form['input'][] = array(
                'type' => 'select',
                'label' => $this->l('Supplier(s)'),
                'name' => 'suppliers[]',
                'multiple' => true,
                'col' => 5,
                'class' => 'fixed-width-md',
                'disabled' => ($report->type == '3' ? true : false),
                'options' => array(
                    'query' => $suppliers,
                    'id' => 'id_supplier',
                    'name' => 'name'
                ),
                'hint' => $this->l('Supplier(s)')
            );
        }
        $this->fields_form['input'][] = array(
            'type' => (version_compare(_PS_VERSION_, '1.6', '>=')) ? 'datetime' : 'date',
            'label' => $this->l('Date from'),
            'name' => 'date_from',
            'col' => 5,
            'size' => 10,
            'hint' => $this->l('Date from to filter. Leave empty to not filter by start date')
        );
        $this->fields_form['input'][] = array(
            'type' => (version_compare(_PS_VERSION_, '1.6', '>=')) ? 'datetime' : 'date',
            'label' => $this->l('Date to'),
            'name' => 'date_to',
            'col' => 5,
            'size' => 10,
            'hint' => $this->l('End date to filter. Leave empty to not filter by end date')
        );
        $this->fields_form['input'][] = array(
            'type' => 'select',
            'label' => $this->l('Data of'),
            'name' => 'data_from',
            'col' => 3,
            'class' => 'fixed-width-md',
            'disabled' => ($report->type == '3' ? true : false),
            'options' => array(
                'query' => $data_froms,
                'id' => 'id',
                'name' => 'name',
                'default' => array(
                    'value' => '',
                    'label' => $this->l('-- Choose --')
                )
            ),
            'hint' => $this->l('Data of selected period')
        );

        if ($report->id) {
            $profiles_db = explode(',', $report->profiles);
            $groups_db = explode(',', $report->groups);
            $zones_db = explode(',', $report->zones);
            $countries_db = explode(',', $report->countries);
            $categories_db = explode(',', $report->categories);
            $manufacturers_db = explode(',', $report->manufacturers);
            $suppliers_db = explode(',', $report->suppliers);
            $statuses_db = explode(',', $report->statuses);
            $payments_db = explode(',', $report->payments);
            $this->fields_value = array(
                'profiles[]' => $profiles_db,
                'groups[]' => $groups_db,
                'zones[]' => $zones_db,
                'countries[]' => $countries_db,
                'categories[]' => $categories_db,
                'manufacturers[]' => $manufacturers_db,
                'suppliers[]' => $suppliers_db,
                'statuses[]' => $statuses_db,
                'payments[]' => $payments_db,
                'data_from' => $report->data_from
            );
        }
    }

    protected function duplicateReportFields($id_ori, $id_new)
    {
        return Db::getInstance()->execute('
            INSERT INTO `'._DB_PREFIX_.$this->table.'_fields`
                (`id_report`, `table`, `field`, `position`, `active`, `orderby`, `groupby`, `sum`, `id_shop`, `date_add`)
                SELECT '.(int)$id_new.', `table`, `field`, `position`, `active`, `orderby`, `groupby`, `sum`, `id_shop`, NOW()
                FROM `'._DB_PREFIX_.$this->table.'_fields`
                WHERE `id_report` = '.(int)$id_ori);
    }

    protected function isAggregatedReport($report)
    {
        return Db::getInstance()->getValue('
            SELECT COUNT(*) FROM `'._DB_PREFIX_.bqSQL('advancedreports_fields').'`
            WHERE `id_report` = '.(int)$report->id_advancedreports.'
            AND (`groupby` = 1 OR `sum` = 1)');
    }
}
