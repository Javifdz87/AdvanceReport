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

include_once(_PS_MODULE_DIR_.'advancedreports/classes/AdvancedReportsConfiguration.php');
include_once(_PS_MODULE_DIR_.'advancedreports/classes/AdvancedReportsFieldsConfiguration.php');

class AdminAdvancedReportsFieldsConfController extends ModuleAdminController
{
    protected $delete_mode;
    protected $_defaultOrderBy = 'position';
    protected $_defaultOrderWay = 'ASC';
    protected $top_elements_in_list = 4;
    protected $position_identifier = 'id_advancedreports_fields';
    
    public function __construct()
    {

        $this->bootstrap = true;
        $this->module_name = 'advancedreports';
        $this->table = 'advancedreports_fields';
        $this->className = 'AdvancedReportsFieldsConfiguration';
        $this->tabClassName = 'AdminAdvancedReportsFieldsConf';
        $this->tabMainClassName = 'AdminAdvancedReportsConf';
        $this->addRowAction('edit');
        $this->addRowAction('duplicate');
        $this->addRowAction('delete');
        $this->_orderBy = 'position';
        $this->_pagination = array(1000000, 2000000, 3000000);
        $this->_default_pagination = 1000000;
        $this->can_add_fields = true;
        $this->show_toolbar = true;

        parent::__construct();

        if (version_compare(_PS_VERSION_, '1.6.0.9', '<=')) {
            $this->meta_title = $this->l('Advanced reports configuration');
        } else {
            $this->meta_title[] = $this->l('Advanced reports configuration');
        }
        $this->tpl_list_vars['title'] = $this->l('Step 2/3: Select report fields');
                    
        if (Tools::getValue('id_report') && Tools::getValue('step') == '2') {
            $this->_select = 'a.position, @n := @n + 1 id, r.name';
            $this->_join = ' LEFT JOIN `'._DB_PREFIX_.'advancedreports` r ON (a.id_report = r.id_advancedreports)';
            $this->_where = 'AND a.id_report = '.(int)Tools::getValue('id_report');
            $this->_use_found_rows = true;
        } elseif (Tools::getValue('id_advancedreports_fields')) {
            $id_report = $this->getIdReportFromIdField(Tools::getValue('id_advancedreports_fields'));
            $this->_select = 'a.position, @n := @n + 1 id, r.name';
            $this->_join = ' LEFT JOIN `'._DB_PREFIX_.'advancedreports` r ON (a.id_report = r.id_advancedreports)';
            $this->_where = 'AND a.id_report = '.(int)$id_report;
            $this->_use_found_rows = true;
        }

        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash'
            )
        );
        
        $this->tpl_list_vars['back_button'] =  array(
                'href' => 'index.php?controller='.$this->tabMainClassName.'&id_advancedreports='.Tools::getValue('id_report').'&updateadvancedreports&token='.Tools::getAdminTokenLite($this->tabMainClassName),
                'desc' => $this->l('Back')
        );

        $this->context = Context::getContext();

        $this->default_form_language = $this->context->language->id;

        $this->fields_list = array(
            'id_advancedreports_fields' => array(
                'title' => $this->l('ID'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs',
                'orderby' => false,
                'search' => false
            ),
            'name' => array(
                'title' => $this->l('Report name'),
                'align' => 'text-center',
                'orderby' => false,
                'search' => false
            ),
            'table' => array(
                'title' => $this->l('Table'),
                'callback' => 'getAdvancedReportsFieldsTable',
                'align' => 'text-center'
            ),
            'field' => array(
                'title' => $this->l('Field'),
                'align' => 'text-center'
            ),
            'field_name' => array(
                'title' => $this->l('Field name'),
                'align' => 'text-center'
            ),
            'position' => array('title' => $this->l('Position'),'filter_key' => 'position', 'align' => 'center', 'class' => 'fixed-width-sm', 'position' => 'position'),
            'active' => array(
                'title' => $this->l('Show'),
                'align' => 'text-center',
                'active' => 'status',
                'type' => 'bool',
                'orderby' => false,
                'callback' => 'printActiveIcon'
            ),
            'orderby' => array(
                'title' => $this->l('Order by'),
                'align' => 'text-center',
                'active' => 'orderby',
                'type' => 'bool',
                'orderby' => false,
                'callback' => 'printOrderbyIcon'
            ),
            'groupby' => array(
                'title' => $this->l('Group by'),
                'align' => 'text-center',
                'active' => 'groupby',
                'type' => 'bool',
                'orderby' => false,
                'callback' => 'printGroupbyIcon'
            ),
            'sum' => array(
                'title' => $this->l('Sum'),
                'align' => 'text-center',
                'active' => 'sum',
                'type' => 'bool',
                'orderby' => false,
                'callback' => 'printSumIcon'
            )
        );

        $this->shopLinkType = 'shop';

        if (!$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminHome'));
        }

        if (Shop::isFeatureActive() && (Shop::getContext() == Shop::CONTEXT_ALL || Shop::getContext() == Shop::CONTEXT_GROUP)) {
            $this->can_add_fields = false;
        }
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
        if (Tools::getIsSet('id_report')) {
            $report = new AdvancedReportsConfiguration(Tools::getValue('id_report'));
            if ($report->type == '3') {
                $report->data_from = '99';
                $report->save();
                if (version_compare(_PS_VERSION_, '1.6', '<')) {
                    return Tools::redirectAdmin('index.php?tab='.$this->tabMainClassName.'&id_advancedreports='.$report->id_advancedreports.'&updateadvancedreports&token='.Tools::getAdminTokenLite($this->tabMainClassName));
                } else {
                    return Tools::redirectAdmin('index.php?controller='.$this->tabMainClassName.'&id_advancedreports='.$report->id_advancedreports.'&updateadvancedreports&token='.Tools::getAdminTokenLite($this->tabMainClassName));
                }
            }
        }
        if (Tools::getIsSet('orderbyadvancedreports_fields')) {
            $this->toggle('orderby', Tools::getValue('id_advancedreports_fields'));
        }
        if (Tools::getIsSet('groupbyadvancedreports_fields')) {
            $this->toggle('groupby', Tools::getValue('id_advancedreports_fields'));
        }
        if (Tools::getIsSet('sumadvancedreports_fields')) {
            $this->toggle('sum', Tools::getValue('id_advancedreports_fields'));
        }
        if (Tools::getIsSet('action') && Tools::getIsSet('advancedreports_fields') && Tools::getValue('action') == 'updatePositions') {
            $this->updatePositions(Tools::getValue('advancedreports_fields'));
        }
        if ((Tools::getIsSet('addadvancedreports_fields') || Tools::getIsSet('updateadvancedreports_fields')) && Tools::getIsSet('getfields')) {
            die(json_encode($this->getFieldsDefinition(Tools::getValue('getfields'))));
        }
        if (!$this->can_add_fields && !$this->display) {
            $this->informations[] = $this->l('You have to select a shop if you want to create a new report.');
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
        $this->toolbar_btn['new'] = array(
            'href' => 'index.php?controller='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName).'&addadvancedreports_fields&id_report='.Tools::getValue('id_report'),
            'desc' => $this->l('Add new'),
            'icon' => 'process-icon-new'
        );

        if (version_compare(_PS_VERSION_, '1.6', '<')) {
            if (Tools::getValue('id_report')) {
                $id_report = Tools::getValue('id_report');
            } elseif (Tools::getValue('id_advancedreports_fields')) {
                $id_report = $this->getIdReportFromIdField(Tools::getValue('id_advancedreports_fields'));
            } else {
                $id_report = 0;
            }
            if (empty($this->display)) {
                $this->toolbar_btn['new'] = array(
                    'href' => 'index.php?controller='.$this->tabClassName.'&add'.$this->table.'&token='.Tools::getAdminTokenLite($this->tabClassName).'&id_report='.$id_report,
                    'desc' => $this->l('New field'),
                    'icon' => 'process-icon-new'
                );
                $this->toolbar_btn['cancel'] = array(
                    'href' => 'index.php?controller='.$this->tabMainClassName.'&token='.Tools::getAdminTokenLite($this->tabMainClassName).'&cancel&id_report='.$id_report,
                    'desc' => $this->l('Cancel'),
                    'icon' => 'process-icon-cancel'
                );
                $this->toolbar_btn['save'] = array(
                    'href' => 'index.php?controller='.$this->tabMainClassName.'&token='.Tools::getAdminTokenLite($this->tabMainClassName).'&updateadvancedreports&step=3&id_advancedreports='.$id_report,
                    'desc' => $this->l('Save and continue'),
                    'icon' => 'process-icon-save'
                );
                $this->toolbar_btn['refresh-index'] = array(
                    'href' => 'index.php?controller='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName).'&reload=1&id_report='.$id_report,
                    'desc' => $this->l('Reload'),
                    'icon' => 'process-icon-refresh'
                );
                if (AdvancedReportsFieldsConfiguration::haveFields($id_report)) {
                    $this->toolbar_btn['preview'] = array(
                        'href' => 'index.php?controller='.$this->tabMainClassName.'&token='.Tools::getAdminTokenLite($this->tabMainClassName).'&viewadvancedreports&id_advancedreports='.$id_report,
                        'desc' => $this->l('Execute report'),
                        'icon' => 'process-icon-ok'
                    );
                }
            }
        }

        if (!$this->can_add_fields) {
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
                if (Tools::getValue('id_report') && Tools::getValue('step') == '2') {
                    $report = new AdvancedReportsConfiguration(Tools::getValue('id_report'));
                    array_pop($this->toolbar_title);
                    $this->toolbar_title[] = sprintf($this->l('Manage Report Fields: %s'), $report->name);
                }
                break;
            case 'view':
                if (($field = $this->loadObject(true)) && Validate::isLoadedObject($field)) {
                    array_pop($this->toolbar_title);
                    $this->toolbar_title[] = sprintf($this->l('Field: %s'), $field->table.' - '.$field->field);
                }
                break;
            case 'add':
            case 'edit':
                array_pop($this->toolbar_title);
                if (($field = $this->loadObject(true)) && Validate::isLoadedObject($field)) {
                    $this->toolbar_title[] = sprintf($this->l('Editing field: %s'), $field->table.' - '.$field->field);
                } else {
                    $this->toolbar_title[] = $this->l('Adding a new field');
                }
                break;
        }
    }

    public function initPageHeaderToolbar()
    {
        if (Tools::getValue('id_report')) {
            $id_report = Tools::getValue('id_report');
        } elseif (Tools::getValue('id_advancedreports_fields')) {
            $id_report = $this->getIdReportFromIdField(Tools::getValue('id_advancedreports_fields'));
        } else {
            $id_report = 0;
        }
        parent::initPageHeaderToolbar();
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['desc-module-new'] = array(
                'href' => 'index.php?controller='.$this->tabClassName.'&add'.$this->table.'&token='.Tools::getAdminTokenLite($this->tabClassName).'&id_report='.$id_report,
                'desc' => $this->l('New field'),
                'icon' => 'process-icon-new'
            );
            $this->page_header_toolbar_btn['desc-module-cancel'] = array(
                'href' => 'index.php?controller='.$this->tabMainClassName.'&token='.Tools::getAdminTokenLite($this->tabMainClassName).'&cancel&id_report='.$id_report,
                'desc' => $this->l('Cancel'),
                'icon' => 'process-icon-cancel'
            );
            $this->page_header_toolbar_btn['desc-module-save'] = array(
                'href' => 'index.php?controller='.$this->tabMainClassName.'&token='.Tools::getAdminTokenLite($this->tabMainClassName).'&updateadvancedreports&step=3&id_advancedreports='.$id_report,
                'desc' => $this->l('Save and continue'),
                'icon' => 'process-icon-save'
            );
            $this->page_header_toolbar_btn['desc-module-reload'] = array(
                'href' => 'index.php?controller='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName).'&reload=1&id_report='.$id_report,
                'desc' => $this->l('Reload'),
                'icon' => 'process-icon-refresh'
            );
            if (AdvancedReportsFieldsConfiguration::haveFields($id_report)) {
                $this->page_header_toolbar_btn['desc-module-execute'] = array(
                    'href' => 'index.php?controller='.$this->tabMainClassName.'&token='.Tools::getAdminTokenLite($this->tabMainClassName).'&viewadvancedreports&id_advancedreports='.$id_report,
                    'desc' => $this->l('Execute report'),
                    'icon' => 'process-icon-ok'
                );
            }
        }
    }

    public function initProcess()
    {
        parent::initProcess();
        if (Tools::getIsset('reload')) {
            $this->action = 'reset_filters';
            if (version_compare(_PS_VERSION_, '1.6', '<')) {
                return Tools::redirectAdmin('index.php?tab='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName).'&step=2&id_report='.Tools::getValue('id_report'));
            } else {
                return Tools::redirectAdmin('index.php?controller='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName).'&step=2&id_report='.Tools::getValue('id_report'));
            }
        }
        if (Tools::isSubmit('changeActiveVal') && $this->id_object) {
            if ($this->tabAccess['edit'] === '1') {
                $this->action = 'change_active_val';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        }
        if (Tools::isSubmit('changeOrderbyVal') && $this->id_object) {
            if ($this->tabAccess['edit'] === '1') {
                $this->action = 'change_orderby_val';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        }
        if (Tools::isSubmit('changeGroupbyVal') && $this->id_object) {
            if ($this->tabAccess['edit'] === '1') {
                $this->action = 'change_groupby_val';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        }
        if (Tools::isSubmit('changeSumVal') && $this->id_object) {
            if ($this->tabAccess['edit'] === '1') {
                $this->action = 'change_sum_val';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        }
        parent::initProcess();
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
        return parent::renderList();
    }

    public function renderForm()
    {
        if (Tools::getIsset('updateadvancedreports_fields') && Tools::getIsset('id_advancedreports_fields')) {
            $this->content .= $this->generatePreviewFieldList($this->getIdReportFromIdField(Tools::getValue('id_advancedreports_fields')));
        } else {
            if (AdvancedReportsFieldsConfiguration::haveFields(Tools::getValue('id_report'))) {
                $this->content .= $this->generatePreviewFieldList(Tools::getValue('id_report'));
            }
        }
        
        $tables = $this->getAdvancedReportsFieldsTables();
        $fields = array(array('id' => 0, 'value' => 0, 'name' => $this->l('-- Choose --')));
        if ($this->object->table) {
            $fields_definition = $this->getFieldsDefinition($this->object->table);
            foreach ($fields_definition['fields'] as $key => $field) {
                $fields[] = array('id' => $key, 'value' => $key, 'name' => $key);
            }
        }
        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Add a new field'),
                'icon' => 'icon-key'
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('Table'),
                    'name' => 'table',
                    'required' => true,
                    'col' => 3,
                    'onchange' => 'getFields()',
                    'class' => 'fixed-width-xl',
                    'options' => array(
                        'query' => $tables,
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Field'),
                    'name' => 'field',
                    'required' => true,
                    'col' => 3,
                    'class' => 'fixed-width-xl',
                    'options' => array(
                        'query' => $fields,
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Name'),
                    'name' => 'field_name',
                    'col' => 3,
                    'class' => 'fixed-width-xl',
                ),
                array(
                    'type' => (version_compare(_PS_VERSION_, '1.6', '>=')) ? 'switch' : 'radio',
                    'label' => $this->l('Show?'),
                    'name' => 'active',
                    'class' => 't',
                    'col' => 5,
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Shown')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Hidden')
                        )
                    ),
                    'hint' => $this->l('Show this field in the report')
                ),
                array(
                    'type' => (version_compare(_PS_VERSION_, '1.6', '>=')) ? 'switch' : 'radio',
                    'label' => $this->l('Order by this field?'),
                    'name' => 'orderby',
                    'class' => 't',
                    'col' => 5,
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'orderby_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ),
                        array(
                            'id' => 'orderby_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                    'hint' => $this->l('Order report results by this field')
                ),
                array(
                    'type' => (version_compare(_PS_VERSION_, '1.6', '>=')) ? 'switch' : 'radio',
                    'label' => $this->l('Group by this field?'),
                    'name' => 'groupby',
                    'class' => 't',
                    'col' => 5,
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'groupby_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ),
                        array(
                            'id' => 'groupby_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                    'hint' => $this->l('Group report results by this field')
                ),
                array(
                    'type' => (version_compare(_PS_VERSION_, '1.6', '>=')) ? 'switch' : 'radio',
                    'label' => $this->l('Totalize this field?'),
                    'name' => 'sum',
                    'class' => 't',
                    'col' => 5,
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'sum_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ),
                        array(
                            'id' => 'sum_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                    'hint' => $this->l('Add a row with sum total field')
                ),
                array(
                    'type' => 'hidden',
                    'name' => 'id_report'
                ),
                array(
                    'type' => 'hidden',
                    'name' => 'position'
                ),
                array(
                    'type' => 'hidden',
                    'name' => 'addfield'
                ),
                array(
                    'type' => 'hidden',
                    'name' => 'addotherfield'
                )
            )
        );

        if (version_compare(_PS_VERSION_, '1.6', '<')) {
            $this->fields_form['submit'] = array(
                'title' => $this->l('Save'),
                'class' => 'button'
            );
        }

        if (Tools::getValue('id_report')) {
            if (!Tools::getIsset('updateadvancedreports_fields')) {
                $this->fields_value['position'] = AdvancedReportsFieldsConfiguration::getLastPosition(Tools::getValue('id_report'));
            }
            $this->fields_value['id_report'] = Tools::getValue('id_report');
            $id_report = Tools::getValue('id_report');
        } else {
            if (!Tools::getIsset('updateadvancedreports_fields')) {
                $this->fields_value['position'] = AdvancedReportsFieldsConfiguration::getLastPosition($this->getIdReportFromIdField($this->object->id_advancedreports_fields));
            }
            $this->fields_value['id_report'] = $this->getIdReportFromIdField($this->object->id_advancedreports_fields);
            $id_report = $this->getIdReportFromIdField($this->object->id_advancedreports_fields);
        }
        $this->fields_value['active'] = 1;
        $this->fields_value['addotherfield'] = 0;
        
        $this->tpl_form_vars['show_cancel_button'] = false;
        if (version_compare(_PS_VERSION_, '1.6', '>=')) {
            $this->fields_form['buttons'][] = array(
                'title' => $this->l('Cancel'),
                'icon' => 'process-icon-cancel',
                'href' => 'index.php?controller='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName).'&cancel&step=2&id_report='.$id_report
            );
            $this->fields_form['buttons'][] = array(
                'title' => $this->l('Save and exit'),
                'js' => 'submitFieldForm(0)',
                'icon' => 'process-icon-save',
                'class' => 'pull-right',
                'href' => '#'
            );
            $this->fields_form['buttons'][] = array(
                'title' => $this->l('Save and add other field'),
                'js' => 'submitFieldForm(1)',
                'icon' => 'process-icon-save',
                'class' => 'pull-right',
                'href' => '#'
            );
        } else {
            
            $this->toolbar_btn['new'] = array(
                'short' => 'SaveAndAdd',
                'js' => 'submitFieldForm(1)',
                'desc' => $this->l('Save and add other field')
            );
            $this->toolbar_btn['refresh-index'] = array(
                'short' => 'SaveAndExit',
                'js' => 'submitFieldForm(0)',
                'desc' => $this->l('Save and exit')
            );
        }

        return parent::renderForm();
    }

    public function renderView($report = false)
    {
        if (!($report = $this->loadObject())) {
            return;
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
            'href' => 'index.php?controller='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName).'&id_advancedreports='.$report->id_advancedreports.'&generate_report=1',
            'desc' => $this->l('Export CSV'),
            'icon' => 'process-icon-export'
        );
        $this->page_header_toolbar_btn['desc-module-edit'] = array(
            'href' => 'index.php?controller='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName).'&id_advancedreports='.$report->id_advancedreports.'&updateadvancedreports',
            'desc' => $this->l('Edit this report'),
            'icon' => 'process-icon-edit'
        );
        if ($report->type == '3') {
            $this->content = $this->generateList($report);
        } else {
            $this->content = $this->generateList($report);
            
            $this->context->smarty->assign(array(
                'show_toolbar' => true,
                'data' => '$data',
            ));
        }
    }

    public function processStatus()
    {
        if (Validate::isLoadedObject($object = $this->loadObject())) {
            if ($object->toggleStatus()) {
                $matches = array();
                if (preg_match('/[\?|&]controller=([^&]*)/', (string)$_SERVER['HTTP_REFERER'], $matches) !== false
                    && Tools::strtolower($matches[1]) != Tools::strtolower(preg_replace('/controller/i', '', get_class($this)))) {
                    $this->redirect_after = preg_replace('/[\?|&]conf=([^&]*)/i', '', (string)$_SERVER['HTTP_REFERER']);
                } else {
                    $this->redirect_after = self::$currentIndex.'&token='.$this->token;
                }

                $id_category = (($id_category = (int)Tools::getValue('id_category')) && Tools::getValue('id_product')) ? '&id_category='.$id_category : '';

                $page = (int)Tools::getValue('page');
                $page = $page > 1 ? '&submitFilter'.$this->table.'='.(int)$page : '';
                $this->redirect_after .= '&conf=5'.$id_category.$page;
                $this->redirect_after .= '&step=2&id_report='.$object->id_report;
            } else {
                $this->errors[] = Tools::displayError('An error occurred while updating the status.');
            }
        } else {
            $this->errors[] = Tools::displayError('An error occurred while updating the status for an object.').
                ' <b>'.$this->table.'</b> '.
                Tools::displayError('(cannot load object)');
        }

        return $object;
    }

    public function processDuplicate()
    {
        $id_report = Tools::getValue($this->identifier);
        $report = new AdvancedReportsConfiguration($id_report);
        unset($report->id_advancedreports);
        if (!$report->add()) {
            $this->errors[] = Tools::displayError('An error occurred while duplicating the report #'.$id_report);
        } else {
            $this->confirmations[] = sprintf($this->l('Report #%s - %s successfully duplicated.'), $id_report, $report->name);
        }
    }

    protected function processBulkDelete()
    {
        $id_report = Tools::getValue('id_report');
        if (parent::processBulkDelete()) {
            if (version_compare(_PS_VERSION_, '1.6', '<')) {
                return Tools::redirectAdmin('index.php?tab='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName).'&step=2&id_report='.$id_report);
            } else {
                return Tools::redirectAdmin('index.php?controller='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName).'&step=2&id_report='.$id_report);
            }
        }
    }

    public function processAdd()
    {
        if (Tools::getValue('submitFormAjax')) {
            $this->redirect_after = false;
        }
        $report = new AdvancedReportsConfiguration();
        $report = parent::processAdd();
        return $report;
    }

    public function processUpdate()
    {
        if (Validate::isLoadedObject($this->object)) {
            return parent::processUpdate();
        } else {
            $this->errors[] = Tools::displayError('An error occurred while loading the object.').'
                <b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');
        }
    }

    public function processDelete()
    {
        parent::processDelete();
        if (Validate::isLoadedObject($this->object)) {
            if (version_compare(_PS_VERSION_, '1.6', '<')) {
                return Tools::redirectAdmin('index.php?tab='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName).'&step=2&id_report='.$this->object->id_report);
            } else {
                return Tools::redirectAdmin('index.php?controller='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName).'&step=2&id_report='.$this->object->id_report);
            }
        }
    }

    public function postProcess()
    {
        return parent::postProcess();
    }

    protected function afterAdd($object)
    {
        $id_advancedreports = Tools::getValue('id_advancedreports');
        $this->afterUpdate($object, $id_advancedreports);
        return true;
    }

    protected function afterUpdate($object, $id_advancedreports = false)
    {
        if (!Tools::getIsset('id_report')) {
            $id_report = $this->getIdReportFromIdField($object->id);
        }
        if (Tools::getIsset('addotherfield') && Tools::getValue('addotherfield') == 1) {
            if (version_compare(_PS_VERSION_, '1.6', '<')) {
                return Tools::redirectAdmin('index.php?tab='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName).'&addadvancedreports_fields&id_report='.Tools::getValue('id_report'));
            } else {
                return Tools::redirectAdmin('index.php?controller='.$this->tabClassName.'&token='.Tools::getAdminTokenLite($this->tabClassName).'&addadvancedreports_fields&id_report='.Tools::getValue('id_report'));
            }
        }
        return true;
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
        return '<a class="list-action-enable '.($value ? 'action-enabled' : 'action-disabled').'" href="index.php?'.htmlspecialchars('tab='.$this->tabClassName.'&id_advancedreports='.(int)$report['id_advancedreports'].'&id_report='.(int)$report['id_advancedreports'].'&changeActiveVal&token='.Tools::getAdminTokenLite($this->className)).'">'.($value ? '<i class="icon-check"></i>' : '<i class="icon-remove"></i>').'</a>';
    }

    public function printOrderbyIcon($value, $report)
    {
        return '<a class="list-action-enable '.($value ? 'action-enabled' : 'action-disabled').'" href="index.php?'.htmlspecialchars('tab='.$this->tabClassName.'&id_advancedreports='.(int)$report['id_advancedreports'].'&id_report='.(int)$report['id_advancedreports'].'&changeOrderbyVal&token='.Tools::getAdminTokenLite($this->className)).'">'.($value ? '<i class="icon-check"></i>' : '<i class="icon-remove"></i>').'</a>';
    }

    public function printGroupbyIcon($value, $report)
    {
        return '<a class="list-action-enable '.($value ? 'action-enabled' : 'action-disabled').'" href="index.php?'.htmlspecialchars('tab='.$this->tabClassName.'&id_advancedreports='.(int)$report['id_advancedreports'].'&id_report='.(int)$report['id_advancedreports'].'&changeGroupbyVal&token='.Tools::getAdminTokenLite($this->className)).'">'.($value ? '<i class="icon-check"></i>' : '<i class="icon-remove"></i>').'</a>';
    }
    
    public function printSumIcon($value, $report)
    {
        return '<a class="list-action-enable '.($value ? 'action-enabled' : 'action-disabled').'" href="index.php?'.htmlspecialchars('tab='.$this->tabClassName.'&id_advancedreports='.(int)$report['id_advancedreports'].'&id_report='.(int)$report['id_advancedreports'].'&changeSumVal&token='.Tools::getAdminTokenLite($this->className)).'">'.($value ? '<i class="icon-check"></i>' : '<i class="icon-remove"></i>').'</a>';
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

    protected function getAdvancedReportsFieldsTypes()
    {
        $types = array($this->l('Select field'), $this->l('Group by field'), $this->l('Sum field'));

        $list_types = array();
        foreach ($types as $key => $type) {
            $list_types[$key]['id'] = $key;
            $list_types[$key]['value'] = $key;
            $list_types[$key]['name'] = $type;
        }
        return $list_types;
    }

    public function getAdvancedReportsFieldsType($type)
    {
        if ($type == '0') {
            return $this->l('Select field');
        } elseif ($type == '1') {
            return $this->l('Group by field');
        } elseif ($type == '2') {
            return $this->l('Sum field');
        }
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

    protected function getFieldsDefinition($type = '', $form = false)
    {
        $fields_definition = array();
        if ($type == 'orders') {
            $fields_definition = Order::$definition;
        } elseif ($type == 'order_detail') {
            $fields_definition = OrderDetail::$definition;
        } elseif ($type == 'customer') {
            $fields_definition = Customer::$definition;
        } elseif ($type == 'product') {
            $fields_definition = Product::$definition;
        } else {
            return $fields_definition = array('table' => '', 'primary' => '', 'fields' => array($this->l('-- Choose table --') => ''));
        }
        $fields_definition['fields'] = array_merge(array($fields_definition['primary'] => array('type' => 'int')), $fields_definition['fields']);
        foreach ($fields_definition['fields'] as $key => $field) {
            if ($key == 'id_country') {
                $fields_definition['fields'] = $this->replaceKeysInArray($fields_definition['fields'], $key, 'country');
            }
            if ($key == 'id_zone') {
                $fields_definition['fields'] = $this->replaceKeysInArray($fields_definition['fields'], $key, 'zone');
            }
            if ($key == 'id_manufacturer') {
                $fields_definition['fields'] = $this->replaceKeysInArray($fields_definition['fields'], $key, 'manufacturer');
            }
            if ($key == 'id_supplier') {
                $fields_definition['fields'] = $this->replaceKeysInArray($fields_definition['fields'], $key, 'supplier');
            }
            if ($key == 'id_shop') {
                $fields_definition['fields'] = $this->replaceKeysInArray($fields_definition['fields'], $key, 'shop');
            }
            if ($key == 'id_carrier') {
                $fields_definition['fields'] = $this->replaceKeysInArray($fields_definition['fields'], $key, 'carrier');
            }
            if ($key == 'id_currency') {
                $fields_definition['fields'] = $this->replaceKeysInArray($fields_definition['fields'], $key, 'currency');
            }
            if ($key == 'id_state') {
                $fields_definition['fields'] = $this->replaceKeysInArray($fields_definition['fields'], $key, 'state');
            }
            if ($key == 'id_default_group') {
                $fields_definition['fields'] = $this->replaceKeysInArray($fields_definition['fields'], $key, 'customer_group');
            }
            if ($key == 'current_state') {
                $fields_definition['fields'] = $this->replaceKeysInArray($fields_definition['fields'], $key, 'order_state');
            }
            if ($key == 'id_address_delivery') {
                $fields_definition['fields'] = $this->replaceKeysInArray($fields_definition['fields'], $key, 'address_delivery');
                $fields_definition['fields'] = array_slice($fields_definition['fields'], 0, 2, true) + 
                array(
                    'address_delivery.company' => 'address_delivery.company',
                    'address_delivery.address1' => 'address_delivery.address1',
                    'address_delivery.address2' => 'address_delivery.address2',
                    'address_delivery.postcode' => 'address_delivery.postcode',
                    'address_delivery.other' => 'address_delivery.other',
                    'address_delivery.city' => 'address_delivery.city',
                    'address_delivery.state.name' => 'address_delivery.state.name',
                    'address_delivery.country_lang.name' => 'address_delivery.country_lang.name',
                    'address_delivery.phone' => 'address_delivery.phone'
                ) + 
                array_slice($fields_definition['fields'], 2, count($fields_definition['fields']) - 2, true);
            }
            if ($key == 'id_address_invoice') {
                $fields_definition['fields'] = $this->replaceKeysInArray($fields_definition['fields'], $key, 'address_invoice');
                $fields_definition['fields'] = array_slice($fields_definition['fields'], 0, 11, true) + 
                array(
                    'address_invoice.company' => 'address_invoice.company',
                    'address_invoice.address1' => 'address_invoice.address1',
                    'address_invoice.address2' => 'address_invoice.address2',
                    'address_invoice.postcode' => 'address_invoice.postcode',
                    'address_invoice.other' => 'address_invoice.other',
                    'address_invoice.city' => 'address_invoice.city',
                    'address_invoice.state.name' => 'address_invoice.state.name',
                    'address_invoice.country_lang.name' => 'address_invoice.country_lang.name',
                    'address_invoice.phone' => 'address_invoice.phone'
                ) + 
                array_slice($fields_definition['fields'], 11, count($fields_definition['fields']) - 11, true);
            }
            if ($key == 'id_product') {
                $fields_definition['fields'] = $this->replaceKeysInArray($fields_definition['fields'], $key, 'product_name');
                $fields_definition['fields'] = array_merge(array('id_product' => array('type' => 'int')), $fields_definition['fields']);
            }
        }
        return $fields_definition;
    }

    protected function getAdvancedReportsFieldsTables()
    {
        $tables = array();
        $tables[] = array('id' => 0, 'value' => '', 'name' => $this->l('-- Choose --'));
        $tables[] = array('id' => 'orders', 'value' => 'orders', 'name' => $this->l('Orders'));
        $tables[] = array('id' => 'order_detail', 'value' => 'order_detail', 'name' => $this->l('Order details'));
        $tables[] = array('id' => 'customer', 'value' => 'customer', 'name' => $this->l('Customers'));
        $tables[] = array('id' => 'product', 'value' => 'product', 'name' => $this->l('Products'));
        return $tables;
    }

    public function getAdvancedReportsFieldsTable($table)
    {
        if ($table == 'orders') {
            return $this->l('Orders');
        } elseif ($table == 'order_detail') {
            return $this->l('Order details');
        } elseif ($table == 'customer') {
            return $this->l('Customers');
        } elseif ($table == 'product') {
            return $this->l('Products');
        }
    }
    
    protected function updatePositions($positions)
    {
        foreach ($positions as $key => $position) {
            $pos = explode('_', $position);
            Db::getInstance()->execute('
                UPDATE `'._DB_PREFIX_.$this->table.'`
                SET `position` = '.(int)$key.'
                WHERE `id_advancedreports_fields` = '.(int)$pos[2]);
        }
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
    
    protected function toggle($field, $id_field)
    {
        $ant = Db::getInstance()->getValue('
            SELECT `'.pSQL($field).'` FROM `'._DB_PREFIX_.$this->table.'`
            WHERE `id_advancedreports_fields` = '.(int)$id_field);

        Db::getInstance()->execute('
            UPDATE `'._DB_PREFIX_.$this->table.'`
            SET `'.pSQL($field).'` = '.((int)($ant) == 0 ? 1 : 0).',
            `date_upd` = NOW()
            WHERE `id_advancedreports_fields` = '.(int)$id_field);
    }
    
    protected function getIdReportFromIdField($id_field)
    {
        return Db::getInstance()->getValue('
            SELECT `id_report` FROM `'._DB_PREFIX_.$this->table.'`
            WHERE `id_advancedreports_fields` = '.(int)$id_field);
    }
    
    protected function generatePreviewFieldList($id_report)
    {
        $helper = new HelperList();
        $helper->show_toolbar = true;
        //$helper->list_id = 'report_'.$report->id_advancedreports;
        $helper->shopLinkType = '';
        $helper->shopLinkType = '';
        if (version_compare(_PS_VERSION_, '1.6.0.14', '>')) {
            $helper->_pagination = array(1000000, 2000000, 3000000);
        }
        $helper->_default_pagination = 1000000;
        $helper->token = $this->token;
        $results = array();
        $fields_list = array();
        $sql = 'SELECT `table` as `'.$this->l('Table').'`, `field` as `'.$this->l('Field').'`, `field_name` AS `'.$this->l('Field name').'`, 
                IF(`active` = 1, "'.$this->l('Yes').'", "'.$this->l('No').'") as `'.$this->l('Show').'`, IF(`orderby` = 1, "'.$this->l('Yes').'", "'.$this->l('No').'") as `'.$this->l('Order by').'`, 
                IF(`groupby` = 1, "'.$this->l('Yes').'", "'.$this->l('No').'") as `'.$this->l('Group by').'`, IF(`sum` = 1, "'.$this->l('Yes').'", "'.$this->l('No').'") as `'.$this->l('Sum').'`
                FROM `'._DB_PREFIX_.$this->table.'`
                WHERE `id_report` = '.(int)$id_report.';';
        if ($results = Db::getInstance()->executeS($sql)) {
            foreach (array_keys($results[0]) as $key) {
                $fields_list[$key] = array('title' => $key, 'type' => 'text');
            }
            $this->_list = $results;
        }
        $helper->simple_header = true;
        $helper->identifier = (version_compare(_PS_VERSION_, '1.6', '<') ? $key : '');
        $helper->listTotal = count($results);
        $helper->table = $this->table;
        $helper->no_link = true;
        $helper->currentIndex = self::$currentIndex.'&id_advancedreports='.$id_report.'&viewadvancedreports';

        return $helper->generateList($results, $fields_list);
    }
}
