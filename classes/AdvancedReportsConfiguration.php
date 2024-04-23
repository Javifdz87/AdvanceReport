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
include_once(_PS_MODULE_DIR_.'advancedreports/classes/tcpdf/tcpdf.php');
class AdvancedReportsConfiguration extends ObjectModel
{
    const TYPE_SQL = 8;
    public $id_advancedreports;
    public $name;
    public $type;
    public $sql_query;
    public $countries;
    public $zones;
    public $payments;
    public $groups;
    public $manufacturers;
    public $suppliers;
    public $categories;
    public $statuses;
    public $data_from;
    public $frequency;
    public $frequency_week;
    public $frequency_month;
    public $frequency_year;
    public $format;
    public $email;
    public $profiles = 'all';
    public $date_from;
    public $date_to;
    public $fields;
    public $groupby;
    public $orderby;
    public $active = false;
    public $id_shop;
    public $date_add;
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'advancedreports',
        'primary' => 'id_advancedreports',
        'fields' => array(
            'name' =>               array('type' => self::TYPE_STRING, 'size' => 100),
            'type' =>               array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'sql_query' =>          array('type' => self::TYPE_SQL),
            'countries' =>          array('type' => self::TYPE_STRING, 'size' => 250),
            'zones' =>              array('type' => self::TYPE_STRING, 'size' => 150),
            'payments' =>           array('type' => self::TYPE_STRING, 'size' => 150),
            'groups' =>             array('type' => self::TYPE_STRING, 'size' => 150),
            'manufacturers' =>      array('type' => self::TYPE_STRING, 'size' => 150),
            'suppliers' =>          array('type' => self::TYPE_STRING, 'size' => 150),
            'categories' =>         array('type' => self::TYPE_STRING, 'size' => 150),
            'statuses' =>           array('type' => self::TYPE_STRING, 'size' => 150),
            'data_from' =>          array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'frequency' =>          array('type' => self::TYPE_INT),
            'frequency_week' =>     array('type' => self::TYPE_INT),
            'frequency_month' =>    array('type' => self::TYPE_INT),
            'frequency_year' =>     array('type' => self::TYPE_DATE, 'copy_post' => false),
            'format' =>             array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'email' =>              array('type' => self::TYPE_STRING, 'size' => 250),
            'profiles' =>           array('type' => self::TYPE_STRING, 'size' => 150),
            'date_from' =>          array('type' => self::TYPE_DATE, 'copy_post' => false),
            'date_to' =>            array('type' => self::TYPE_DATE, 'copy_post' => false),
            'fields' =>             array('type' => self::TYPE_STRING, 'size' => 2500),
            'groupby' =>            array('type' => self::TYPE_STRING, 'size' => 250),
            'orderby' =>            array('type' => self::TYPE_STRING, 'size' => 250),
            'active' =>             array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false),
            'id_shop' =>            array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false),
            'date_add' =>           array('type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false),
            'date_upd' =>           array('type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false),
        ),
    );

    public $module_name = 'advancedreports';
    public static $encoding_file = array(
        array('value' => 1, 'name' => 'utf-8'),
        array('value' => 2, 'name' => 'iso-8859-1')
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

    public function delete()
    {
        return parent::delete();
    }

    public function processCron()
    {
        $context = Context::getContext();
        $report_confs = $this->getReportConfigurationsForCron($context->shop->id);
        foreach ($report_confs as $key => $report_conf) {
            $execute = false;
            if ($report_conf['frequency'] == '0') { //daily
                $execute = true;
            } elseif ($report_conf['frequency'] == '1') { //weekly
                $dayofweek = date('w');
                if ($report_conf['frequency_week'] == $dayofweek) {
                    $execute = true;
                }
            } elseif ($report_conf['frequency'] == '2') { //monthly
                $dayofmonth = date('d');
                $lastdayofmonth = date('t');
                if ($report_conf['frequency_month'] == $dayofmonth || ($report_conf['frequency_month'] == 99 && $dayofmonth == $lastdayofmonth)) {
                    $execute = true;
                }
            } elseif ($report_conf['frequency'] == '3') { //year
                $dayandmonth = date('m-d');
                if (date('m-d', strtotime($report_conf['frequency_year'])) == $dayandmonth) {
                    $execute = true;
                }
            }
            if ($execute) {
                $result = $this->processExport($report_conf['id_advancedreports'], true);
                $file = array();
                if ($report_conf['email'] && $report_conf['email'] != '') {
                    $emails = explode(',', $report_conf['email']);
                    foreach ($emails as $email) {
                        $email = trim($email);
                        if (Validate::isEmail($email)) {
                            if (is_array($result) && $result['content']) {
                                $file = $result;
                            }
                            $module = Module::getInstanceByName($this->module_name);
                            $data = array('report_txt' => sprintf($module->l('Report #%s - %s'), $report_conf['id_advancedreports'], $report_conf['name']));
                            Mail::Send(
                                (int)$context->language->id,
                                'report_template',
                                sprintf($module->l('Report #%s - %s'), $report_conf['id_advancedreports'], $report_conf['name']),
                                $data,
                                $email,
                                null,
                                null,
                                null,
                                $file,
                                null,
                                _PS_MODULE_DIR_.$this->module_name.'/mails/',
                                false,
                                (int)$report_conf['id_shop']
                            );
                        }
                    }
                }
            }
        }
        return true;
    }

    public function processExport($id = false, $cron = false)
    {
        try {
            if ($id) {
                $cron = true;
            } elseif (Tools::getValue($this->identifier)) {
                $id = Tools::getValue($this->identifier);
            }
            $context = Context::getContext();
            $export_dir = defined('_PS_HOST_MODE_') ? _PS_MODULE_DIR_.'/advancedreports/export/' : _PS_MODULE_DIR_.'/advancedreports/export/';
            if (!Validate::isFileName($id)) {
                die(Tools::displayError());
            }
            $report = new AdvancedReportsConfiguration($id);
            if ($report->format == '0') {
                $format = 'xls';
            } elseif ($report->format == '2') {
                $format = 'pdf';
            } else {
                $format = 'csv';
            }
            $xls = '';
            $file = $report->name.'_'.date('d-m-Y_His').'.'.$format;
            $results = array();
            if ($csv = fopen($export_dir.$file, 'w')) {
                if ($report->format != '2') {
                    fputs($csv, "\xEF\xBB\xBF");
                }
                $sql = $report->sql_query;
                if ($sql) {
                    $queries = explode(';', $report->sql_query);
                    foreach ($queries as $query) {
                        $query = trim($query);
                        if ($query != '') {
                            if (substr(Tools::strtoupper($query), 0, 6) == 'SELECT' || Tools::strtolower(substr($query, 0, 7)) == '(select') {
                                $results = Db::getInstance()->executeS($query);
                            } else {
                                $res = Db::getInstance()->execute($query);
                            }
                        }
                    }
                } else {
                    $conf = new AdvancedReportsConfiguration();
                    $results = $conf->getReportResults($report);
                }
                $pdf = '';
                if ($report->format == '2') {
                    $pdf = $this->processPDF($report, $file, $cron);
                    file_put_contents($export_dir.$file, $pdf);
                } else {
                    $tab_key = array();
                    if (is_array($results) && count($results) > 0) {
                        $_count = array_keys($results[0]);
                        foreach (array_keys($results[0]) as $_count => $key) {
                            $tab_key[] = $key;
                            if($_count !== count(array_keys($results[0])) -1 ) {
                                fputs($csv, $key.';');
                            } else {
                                fputs($csv, $key);
                            }
                            $xls .= $key."\t";
                        }
                        foreach ($results as $result) {
                            fputs($csv, "\n");
                            $xls .= "\n";
                            foreach ($tab_key as $_count => $name) {
                                if (is_int($result[$name]) || is_float($result[$name]) || is_double($result[$name])) {
                                    $result[$name] = $this->formatDecimalsSeparator($result[$name], $context->currency, $context);
                                    if($_count !== count($tab_key) -1 ) {
                                        fputs($csv, strip_tags($result[$name]).';');
                                    } else {
                                        fputs($csv, strip_tags($result[$name]));
                                    }
                                } else {
                                    if($_count !== count($tab_key) -1 ) {
                                        fputs($csv, '"'.strip_tags($result[$name]).'";');
                                    } else {
                                        fputs($csv, '"'.strip_tags($result[$name]).'"');
                                    }
                                }
                                $xls .= strip_tags($result[$name])."\t";
                            }
                        }
                    } else {
                        $module = Module::getInstanceByName($this->module_name);
                        $xls .= $module->l('No records found')."\t";
                        fputs($csv, $module->l('No records found').';');
                    }
                    fclose($csv);
                    if ($report->format == '0') {
                        include_once(_PS_MODULE_DIR_.'advancedreports/classes/PhpSpreadsheet/vendor/autoload.php');
                        $objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Csv');
                        try {
                            $objPHPExcel = $objReader->load($export_dir.$file);
                        } catch (Exception $e) {
                            header("Content-Type: application/vnd.ms-excel");
                            header('Content-Disposition: attachment; filename="'.$file.'"');
                            readfile($export_dir.$file);
                            die();
                        }
                        $objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, 'Xls');
                        $objWriter->save($export_dir.$file);
                    }
                }
                if (file_exists($export_dir.$file)) {
                    $filesize = filesize($export_dir.$file);
                    $upload_max_filesize = Tools::convertBytes(ini_get('upload_max_filesize'));
                    if ($filesize < $upload_max_filesize) {
                        if (Configuration::get('PS_ENCODING_FILE_MANAGER_SQL')) {
                            $charset = Configuration::get('PS_ENCODING_FILE_MANAGER_SQL');
                        } else {
                            $charset = self::$encoding_file[0]['name'];
                        }
                        if (!$cron) {
                            if ($report->format == '0') {
                                header("Content-Type: application/vnd.ms-excel");
                                header('Content-Disposition: attachment; filename="'.$file.'"');
                            } elseif ($report->format == '2') {
                                header("Content-Type: application/pdf");
                                header('Content-Disposition: attachment; filename="'.$file.'"');
                                header('Content-Length: '.$filesize);
                            } else {
                                header("Content-Type: text/csv; charset=" . $charset );
                                header('Content-Disposition: attachment; filename="'.$file.'"');
                                header('Content-Length: '.$filesize);
                            }
                            header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
                            header("Cache-Control: post-check=0, pre-check=0", false);
                            header("Pragma: no-cache");
                            header("Expires: Jue, 1 Jan 1970 00:00:00 GMT");
                            if ($report->format == '0') {
                                readfile($export_dir.$file);
                                die();
                            } elseif ($report->format == '2') {
                                readfile($export_dir.$file);
                                die();
                            } else {
                                readfile($export_dir.$file);
                                die();
                            }
                        }
                    } else {
                        d(Tools::DisplayError('The file is too large and can not be downloaded. Please use the LIMIT clause in this query.'));
                    }
                    if ($cron) {
                        $attachment = array();
                        if ($report->format == '2') {
                            $attachment['content'] = $pdf;
                            $attachment['mime'] = 'application/pdf';
                        } elseif ($report->format == '0') {
                            $attachment['content'] = Tools::file_get_contents($export_dir.$file);
                            $attachment['mime'] = 'application/vnd.ms-excel';
                        } else {
                            $attachment['content'] = Tools::file_get_contents($export_dir.$file);
                            $attachment['mime'] = 'application/text';
                        }
                        $attachment['name'] = $file;
                        return $attachment;
                    }
                }
            }
            return true;
        } catch (Exception $e) {
            throw new PrestaShopException($e->getMessage());
        }
    }

    public function processPDF($report, $file, $cron = false)
    {
        ob_start();
        $context = Context::getContext();
        $id_shop = (int)$context->shop->id;
        $shop_name = Configuration::get('PS_SHOP_NAME', null, null, $id_shop);
        $author = $shop_name;
        $module = Module::getInstanceByName($this->module_name);
        $title = sprintf($module->l('Report #%s (%s)'), $report->id_advancedreports, Tools::displayDate(date('Y-m-d H:i:s'), null, 1));
        $subject = $report->name;
        $path_logo = '';
        if (Configuration::get('PS_LOGO_INVOICE', null, null, $id_shop) != false && file_exists(_PS_IMG_DIR_.Configuration::get('PS_LOGO_INVOICE', null, null, $id_shop))) {
            $path_logo = _PS_IMG_DIR_.Configuration::get('PS_LOGO_INVOICE', null, null, $id_shop);
        } elseif (Configuration::get('PS_LOGO', null, null, $id_shop) != false && file_exists(_PS_IMG_DIR_.Configuration::get('PS_LOGO', null, null, $id_shop))) {
            $path_logo = _PS_IMG_DIR_.Configuration::get('PS_LOGO', null, null, $id_shop);
        }
        if (file_exists(K_PATH_IMAGES_AR.PDF_HEADER_LOGO_AR)) {
            if (version_compare(_PS_VERSION_, '1.6', '>=')) {
                copy($path_logo,K_PATH_IMAGES_AR.PDF_HEADER_LOGO_AR.'logo.png');
            } else {
                copy($path_logo,K_PATH_IMAGES_AR.PDF_HEADER_LOGO_AR);
            }
        } else {
            mkdir(K_PATH_IMAGES_AR);
            copy($path_logo, K_PATH_IMAGES_AR.PDF_HEADER_LOGO_AR);
        }
        $pdf = new TCPDF_AR('L', PDF_UNIT_AR, PDF_PAGE_FORMAT_AR, true, 'UTF-8', false);
        $pdf->setCreator(PDF_CREATOR_AR);
        $pdf->setAuthor($author);
        $pdf->setTitle($title);
        $pdf->setSubject($subject);
        $pdf->setHeaderData(PDF_HEADER_LOGO_AR, PDF_HEADER_LOGO_WIDTH_AR, $title, $subject, array(0,0,0), array(0,0,0));
        $pdf->setFooterData(array(0,0,0), array(0,0,0));
        $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN_AR, '', PDF_FONT_SIZE_MAIN_AR));
        $pdf->setFooterFont(array(PDF_FONT_NAME_DATA_AR, '', PDF_FONT_SIZE_DATA_AR));
        $pdf->setDefaultMonospacedFont(PDF_FONT_MONOSPACED_AR);
        $pdf->setMargins(5, 20, 5);
        $pdf->setHeaderMargin(PDF_MARGIN_HEADER_AR);
        $pdf->setFooterMargin(PDF_MARGIN_FOOTER_AR);
        $pdf->setAutoPageBreak(true, PDF_MARGIN_BOTTOM_AR);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO_AR);
        $pdf->setFontSubsetting(true);
        $pdf->setFont('helvetica', '', 7, '', true);
        $pdf->addPage();
        $pdf->setTextShadow(array('enabled'=>false, 'depth_w'=>0.2, 'depth_h'=>0.2, 'color'=>array(196,196,196), 'opacity'=>1, 'blend_mode'=>'Normal'));
        $html = $this->generateHtmlToPdf($report);
        $pdf->writeHTMLCell(0, 0, 5, 8, $html, 0, 1, 0, true, '', true);
        ob_end_clean();
        if ($cron) {
            return $pdf->output($file, 'S');
        } else {
            return $pdf->output($file, 'I');
        }
    }

    public function getReportResults($report, $getSQL = false)
    {
        if (!is_object($report)) {
            return false;
        }
        //SQL
        $_sql = $this->getReportSQL($report);
        if ($getSQL) {
            return $_sql;
        }
        try {
            if ($results = Db::getInstance()->executeS($_sql)) {
                return $results;
            } else {
                return array();
            }
        } catch (Exception $e) {
            $reportClass = new AdvancedReports();
            $error = explode(';', $e->getMessage());
            if ($error[0] = 'You have an error in your SQL syntax') {
                $error = sprintf($reportClass->l('There was an error in your report configuration. Detailed error: %s'), $e->getMessage());
            }
            return array(array('ERROR' => $error));
        }
    }

    public static function getReportConfigurationsForCron($id_shop)
    {
        return Db::getInstance()->executeS(
            'SELECT ar.*
                FROM `'._DB_PREFIX_.'advancedreports` ar
                WHERE ar.`id_shop` = '.(int)$id_shop.'
                AND ar.`active` = 1;'
        );
    }
    
    public static function getReportPrimaryTable($report_id)
    {
        return Db::getInstance()->getValue(
            'SELECT af.`table`
                FROM `'._DB_PREFIX_.'advancedreports_fields` af
                WHERE af.`id_report` = '.(int)$report_id.'
                AND af.`active` = 1
                ORDER BY af.`position`;'
        );
    }
    
    public static function getReportPrimaryIdentifier($report_id)
    {
        $table = Db::getInstance()->getValue(
            'SELECT af.`table`
                FROM `'._DB_PREFIX_.'advancedreports_fields` af
                WHERE af.`id_report` = '.(int)$report_id.'
                AND af.`active` = 1
                ORDER BY af.`position`;'
        );
        if ($table == 'orders') {
            return 'id_order';
        } elseif ($table == 'order_detail') {
            return 'id_order';
        } elseif ($table == 'customer') {
            return 'id_customer';
        } elseif ($table == 'address') {
            return 'id_address';
        }
    }

    public static function getReportPrimaryField($report_id)
    {
        return Db::getInstance()->getValue(
            'SELECT af.`field`
                FROM `'._DB_PREFIX_.'advancedreports_fields` af
                WHERE af.`id_report` = '.(int)$report_id.'
                AND af.`active` = 1
                ORDER BY af.`position`;'
        );
    }

    public static function getLastReport($id_shop)
    {
        return Db::getInstance()->getValue(
            'SELECT `id_advancedreports`
                FROM `'._DB_PREFIX_.'advancedreports` ar
                WHERE ar.`id_shop` = '.(int)$id_shop.'
                ORDER BY `id_advancedreports` DESC;'
        );
    }

    public function generateHtmlToPdf($report)
    {
        $results = array();
        if ($report->type == '3') {
            $total = 0;
            try {
                $queries = explode(';', $report->sql_query);
                foreach ($queries as $query) {
                    $query = trim($query);
                    if ($query != '') {
                        if (Tools::strtolower(substr($query, 0, 6)) == 'select' || Tools::strtolower(substr($query, 0, 7)) == '(select') {
                            if ($results = Db::getInstance()->executeS($query)) {
                                $total = $total + count($results);
                                foreach (array_keys($results[0]) as $key) {
                                    $fields_list[$key] = array('title' => $key, 'type' => 'text');
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
            //$helper->title = sprintf($this->l('Results of report %s (%s)'), $report->name, $helper->listTotal);
        } else {
            if ($results = $this->getReportResults($report)) {
                if (isset($results[0]['ERROR'])) {
                    $fields_list['ERROR'] = array('title' => 'ERROR', 'type' => 'text');
                    unset($this->page_header_toolbar_btn['desc-module-export']);
                } else {
                    foreach (array_keys($results[0]) as $key) {
                        $fields_list[$key] = array('title' => $key, 'type' => 'text');
                    }
                }
            }            
        }
        if ($report->format == '2' && count($results) == 0) {
            $reportClass = new AdvancedReports();
            return '<br><br><br><br><br>'.$reportClass->l('No records found.');
        }
        $html = '<br><br><br><br><table style="width:100%">';
        $html .= '<tr>';
        foreach ($fields_list as $field) {
            $html .= '<th><strong>'.$field['title'].'</strong></th>';
        }
        $html .= '</tr>';
        foreach ($results as $result) {
            $html .= '<tr>';
            foreach ($result as $value) {
                $html .= '<td>'.$value.'</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</table>';
        return $html;
    }

    protected function getReportSQL($report)
    {
        $reportClass = new AdvancedReports();
        $context = Context::getContext();
        $conf = Db::getInstance()->executeS(
            'SELECT af.`table`, af.`table` as table2, af.`field`, af.`field_name`, af.`groupby`, af.`orderby`, af.`sum`
                FROM `'._DB_PREFIX_.'advancedreports_fields` af
                WHERE af.`id_shop` = '.(int)$report->id_shop.'
                AND af.`active` = 1
                AND af.`id_report` = '.(int)$report->id_advancedreports.'
                ORDER BY af.`position`;'
        );
        $_fields = array();
        $_fields_sum = array();
        $_tables = array();
        $_groupby = array();
        $_orderby = array();
        $_select = '';
        $_from = '';
        $_join = '';
        $_sum = false;
        $_join_carrier = false;
        $_join_customergroup = false;
        $_join_orderstate = false;
        $_join_address = false;
        $_join_address_delivery = false;
        $_join_address_invoice = false;
        $_join_address_delivery_separate = false;
        $_join_address_invoice_separate = false;
        $_join_currency = false;
        $_join_state = false;
        $_join_country = false;
        $_join_zone = false;
        $_join_supplier = false;
        $_join_manufacturer = false;
        $_join_productname = false;
        $_join_productdescription = false;
        $_join_productdescriptionshort = false;
        foreach ($conf as $key => $field) {
            if ($field['field'] == 'carrier') {
                $_join_carrier = true;
                $field['table'] = 'carrier';
                $field['field'] = 'name';
            }
            if ($field['field'] == 'customer_group') {
                $_join_customergroup = true;
            }
            if ($field['field'] == 'shop') {
                $field['table'] = 'shop';
                $field['field'] = 'name';
            }
            if ($field['field'] == 'order_state') {
                $_join_orderstate = true;
            }
            if ($field['field'] == 'address_delivery') {
                $_join_address_delivery = true;
            }
            if (strpos($field['field'], 'address_delivery.') !== false) {
                if (strpos($field['field'], 'state') !== false) {
                    $field['field'] = str_replace('address_delivery.state.', '', $field['field']);
                    $field['table'] = 'state';
                } elseif (strpos($field['field'], 'country') !== false) {
                    $field['field'] = str_replace('address_delivery.country_lang.', '', $field['field']);
                    $field['table'] = 'country_lang';
                } else {
                    $field['field'] = str_replace('address_delivery.', '', $field['field']);
                    $field['table'] = 'address_delivery';
                }
                $_join_address_delivery_separate = true;
            }
            if ($field['field'] == 'address_invoice') {
                $_join_address_invoice = true;
            }
            if (strpos($field['field'], 'address_invoice.') !== false) {
                if (strpos($field['field'], 'state') !== false) {
                    $field['field'] = str_replace('address_invoice.state.', '', $field['field']);
                    $field['table'] = 'state';
                } elseif (strpos($field['field'], 'country') !== false) {
                    $field['field'] = str_replace('address_invoice.country_lang.', '', $field['field']);
                    $field['table'] = 'country_lang';
                } else {
                    $field['field'] = str_replace('address_invoice.', '', $field['field']);
                    $field['table'] = 'address_invoice';
                }
                $_join_address_invoice_separate = true;
            }
            if ($field['field'] == 'currency') {
                $_join_currency = true;
            }
            if ($field['field'] == 'state') {
                $_join_state = true;
                $field['table'] = 'state';
                $field['field'] = 'name';
            }
            if ($field['field'] == 'id_country') {
                $_join_country = true;
            }
            if ($field['field'] == 'id_zone') {
                $_join_zone = true;
            }
            if ($field['field'] == 'supplier') {
                $_join_supplier = true;
                $field['table'] = 'supplier';
                $field['field'] = 'name';
            }
            if ($field['field'] == 'manufacturer') {
                $_join_manufacturer = true;
                $field['table'] = 'manufacturer';
                $field['field'] = 'name';
            }
            if ($field['field'] == 'product_name') {
                $_join_productname = true;
            }
            if ($field['field'] == 'description') {
                $_join_productdescription = true;
            }
            if ($field['field'] == 'description_short') {
                $_join_productdescriptionshort = true;
            }
            if ($field['sum'] == '1') {
                $_sum = true;
                $_fields_sum[] = 'SUM('._DB_PREFIX_.$field['table'].'.'.$field['field'].') AS `'.($field['field_name'] != '' ? $field['field_name'] : $field['field']).'`';
                $_fields[] = _DB_PREFIX_.$field['table'].'.'.$field['field'].' AS `'.($field['field_name'] != '' ? $field['field_name'] : $field['field']).'`';
            } else {
                if ($key == 0) {
                    $_fields_sum[] = '"Total" AS `Total`';
                } else {
                    $_fields_sum[] = '"--" AS `'.$reportClass->l('Total').'`';
                }
                $_fields[] = _DB_PREFIX_.$field['table'].'.'.$field['field'].' AS `'.($field['field_name'] != '' ? $field['field_name'] : $field['field']).'`';
            }
            $_tables[] = $field['table'];
            if ($field['groupby'] == '1') {
                $_groupby[] = _DB_PREFIX_.$field['table'].'.'.$field['field'];
            }
            if ($field['orderby'] == '1') {
                $_orderby[] = _DB_PREFIX_.$field['table'].'.'.$field['field'];
            }
        }
        $_select = 'SELECT '.implode(',', $_fields);
        $_select = str_replace(_DB_PREFIX_.'address_delivery', _DB_PREFIX_.'address', $_select);
        $_select = str_replace(_DB_PREFIX_.'address_invoice', _DB_PREFIX_.'address', $_select);
        $_select_sum = 'SELECT '.implode(',', $_fields_sum);
        $_tables = array_unique($_tables);
        $_alltables = $_tables;
        if(in_array('orders', $_alltables)) {
            $_primary_table = _DB_PREFIX_.'orders';
        } else {
            $_primary_table = _DB_PREFIX_.$_tables[0];
        }
        $_from = ' FROM '.$_primary_table;
        if (count($_tables) > 0) {
            //$_tables = array_splice($_tables, 1);
            if ($_primary_table == _DB_PREFIX_.'orders') {
                if (in_array('order_detail', $_tables)) {
                    $_join .= ' INNER JOIN '._DB_PREFIX_.'order_detail ON ('.$_primary_table.'.id_order = '._DB_PREFIX_.'order_detail'.'.id_order)';
                }
                if (in_array('customer', $_tables)) {
                    $_join .= ' INNER JOIN '._DB_PREFIX_.'customer ON ('.$_primary_table.'.id_customer = '._DB_PREFIX_.'customer'.'.id_customer)';
                }
                if (in_array('address', $_tables)) {
                    $_join .= ' INNER JOIN `'._DB_PREFIX_.'address` ON ('.$_primary_table.'.id_address_invoice = '._DB_PREFIX_.'address.id_address)';
                    $_join .= ' INNER JOIN `'._DB_PREFIX_.'country` ON ('._DB_PREFIX_.'address.id_country = '._DB_PREFIX_.'country.id_country)';
                    $_join .= ' INNER JOIN `'._DB_PREFIX_.'country_lang` ON ('._DB_PREFIX_.'country.id_country = '._DB_PREFIX_.'country_lang.id_country AND '._DB_PREFIX_.'country_lang.id_lang = '.$context->language->id.')';
                    $_join_address = true;
                }
            }
            if ($_primary_table == _DB_PREFIX_.'order_detail') {
                $_join .= ' INNER JOIN '._DB_PREFIX_.'orders ON ('.$_primary_table.'.id_order = '._DB_PREFIX_.'orders'.'.id_order)';
                if (in_array('customer', $_tables)) {
                    $_join .= ' INNER JOIN '._DB_PREFIX_.'customer ON ('._DB_PREFIX_.'orders.id_customer = '._DB_PREFIX_.'customer'.'.id_customer)';
                }
                if (in_array('address', $_tables)) {
                    $_join .= ' INNER JOIN `'._DB_PREFIX_.'address` ON ('._DB_PREFIX_.'orders.id_address_invoice = '._DB_PREFIX_.'address.id_address)';
                    $_join .= ' INNER JOIN `'._DB_PREFIX_.'country` ON ('._DB_PREFIX_.'address.id_country = '._DB_PREFIX_.'country.id_country)';
                    $_join .= ' INNER JOIN `'._DB_PREFIX_.'country_lang` ON ('._DB_PREFIX_.'country.id_country = '._DB_PREFIX_.'country_lang.id_country AND '._DB_PREFIX_.'country_lang.id_lang = '.$context->language->id.')';
                    $_join_address = true;
                }
                $_primary_table = _DB_PREFIX_.'orders';
            }
            if ($_primary_table == _DB_PREFIX_.'customer') {
                if (in_array('orders', $_tables)) {
                    $_join .= ' INNER JOIN '._DB_PREFIX_.'orders ON ('.$_primary_table.'.id_customer = '._DB_PREFIX_.'orders'.'.id_customer)';
                }
                if (in_array('order_detail', $_tables)) {
                    if (!in_array('orders', $_tables)) {
                        $_join .= ' INNER JOIN '._DB_PREFIX_.'orders ON ('.$_primary_table.'.id_customer = '._DB_PREFIX_.'orders'.'.id_customer)';
                    }
                    $_join .= ' INNER JOIN '._DB_PREFIX_.'order_detail ON ('._DB_PREFIX_.'orders.id_order = '._DB_PREFIX_.'order_detail'.'.id_order)';
                }
                if (in_array('address', $_tables)) {
                    $_join .= ' INNER JOIN '._DB_PREFIX_.'address ON ('.$_primary_table.'.id_customer = '._DB_PREFIX_.'address'.'.id_customer)';
                    $_join .= ' INNER JOIN `'._DB_PREFIX_.'country` ON ('._DB_PREFIX_.'address.id_country = '._DB_PREFIX_.'country.id_country)';
                    $_join .= ' INNER JOIN `'._DB_PREFIX_.'country_lang` ON ('._DB_PREFIX_.'country.id_country = '._DB_PREFIX_.'country_lang.id_country AND '._DB_PREFIX_.'country_lang.id_lang = '.$context->language->id.')';
                    $_join_address = true;
                }
            }
        }
        //Shop join
        if ($_primary_table == _DB_PREFIX_.'product') {
            $_join .= ' INNER JOIN `'._DB_PREFIX_.'shop` ON ('.$_primary_table.'.id_shop_default = `'._DB_PREFIX_.'shop`.id_shop AND '.$_primary_table.'.id_shop_default = '.$report->id_shop.')';
        } else {
            $_join .= ' INNER JOIN `'._DB_PREFIX_.'shop` ON ('.$_primary_table.'.id_shop = `'._DB_PREFIX_.'shop`.id_shop AND '.$_primary_table.'.id_shop = '.$report->id_shop.')';
        }
        //Carrier join
        if ($_join_carrier) {
            $_join .= ' INNER JOIN `'._DB_PREFIX_.'carrier` ON (`'._DB_PREFIX_.'orders`.id_carrier = `'._DB_PREFIX_.'carrier`.id_carrier)';
        }
        //State join
        if ($_join_state) {
            $_join .= ' LEFT JOIN `'._DB_PREFIX_.'state` ON (`'._DB_PREFIX_.'address`.id_state = `'._DB_PREFIX_.'state`.id_state)';
        }
        //Product name join
        if ($_join_productname) {
            if (!in_array('product', $_tables)) {
                $_join .= ' INNER JOIN '._DB_PREFIX_.'product ON ('._DB_PREFIX_.'order_detail'.'.product_id = '._DB_PREFIX_.'product'.'.id_product)';
            }
            $_select = str_replace(_DB_PREFIX_.'product.product_name', _DB_PREFIX_.'product_lang.name', $_select);
            $_select = str_replace(_DB_PREFIX_.'product.name', _DB_PREFIX_.'product_lang.name', $_select);
            $_join .= ' INNER JOIN `'._DB_PREFIX_.'product_lang` ON (`'._DB_PREFIX_.'product`.id_product = `'._DB_PREFIX_.'product_lang`.id_product AND '._DB_PREFIX_.'product_lang.id_lang = '.$context->language->id.')';
        }
        //Product description join
        if ($_join_productdescription) {
            if (!in_array('product', $_tables)) {
                $_join .= ' INNER JOIN '._DB_PREFIX_.'product ON ('._DB_PREFIX_.'order_detail'.'.product_id = '._DB_PREFIX_.'product'.'.id_product)';
            }
            $_select = str_replace(_DB_PREFIX_.'product.product_description', _DB_PREFIX_.'product_lang.description', $_select);
            $_select = str_replace(_DB_PREFIX_.'product.description', _DB_PREFIX_.'product_lang.description', $_select);
            if (!$_join_productname) {
                $_join .= ' INNER JOIN `'._DB_PREFIX_.'product_lang` ON (`'._DB_PREFIX_.'product`.id_product = `'._DB_PREFIX_.'product_lang`.id_product AND '._DB_PREFIX_.'product_lang.id_lang = '.$context->language->id.')';
            }
        }
        //Product short description join
        if ($_join_productdescriptionshort) {
            if (!in_array('product', $_tables)) {
                $_join .= ' INNER JOIN '._DB_PREFIX_.'product ON ('._DB_PREFIX_.'order_detail'.'.product_id = '._DB_PREFIX_.'product'.'.id_product)';
            }
            $_select = str_replace(_DB_PREFIX_.'product.product_description_short', _DB_PREFIX_.'product_lang.description_short', $_select);
            $_select = str_replace(_DB_PREFIX_.'product.description_short', _DB_PREFIX_.'product_lang.description_short', $_select);
            if (!$_join_productname) {
                $_join .= ' INNER JOIN `'._DB_PREFIX_.'product_lang` ON (`'._DB_PREFIX_.'product`.id_product = `'._DB_PREFIX_.'product_lang`.id_product AND '._DB_PREFIX_.'product_lang.id_lang = '.$context->language->id.')';
            }
        }
        //Customer group join
        if ($_join_customergroup) {
            $_select = str_replace(_DB_PREFIX_.'customer.customer_group', _DB_PREFIX_.'group_lang.name', $_select);
            $_join .= ' INNER JOIN `'._DB_PREFIX_.'group_lang` ON (`'._DB_PREFIX_.'customer`.id_default_group = `'._DB_PREFIX_.'group_lang`.id_group AND '._DB_PREFIX_.'group_lang.id_lang = '.$context->language->id.')';
        }
        //Order state join
        if ($_join_orderstate) {
            $_select = str_replace(_DB_PREFIX_.'orders.order_state', _DB_PREFIX_.'order_state_lang.name', $_select);
            $_join .= ' INNER JOIN `'._DB_PREFIX_.'order_state_lang` ON (`'._DB_PREFIX_.'orders`.current_state = `'._DB_PREFIX_.'order_state_lang`.id_order_state AND '._DB_PREFIX_.'order_state_lang.id_lang = '.$context->language->id.')';
        }
        //Address delivery join
        if ($_join_address_delivery || $_join_address_delivery_separate) {
            $_select = str_replace(_DB_PREFIX_.'orders.address_delivery', 'CONCAT('._DB_PREFIX_.'address.company, " ",'._DB_PREFIX_.'address.address1, " ", '._DB_PREFIX_.'address.address2, " ", '._DB_PREFIX_.'address.postcode, " ", '._DB_PREFIX_.'address.city, " ", '._DB_PREFIX_.'address.other, " ", IFNULL('._DB_PREFIX_.'state.name, ""), " ", '._DB_PREFIX_.'country_lang.name, " ", '._DB_PREFIX_.'address.phone)', $_select);
            if (!in_array('address', $_tables) || !in_array('address_delivery', $_tables)) {
                $_join .= ' INNER JOIN `'._DB_PREFIX_.'address` ON (`'._DB_PREFIX_.'orders`.id_address_delivery = `'._DB_PREFIX_.'address`.id_address)';
                $_join .= ' INNER JOIN `'._DB_PREFIX_.'country` ON (`'._DB_PREFIX_.'address`.id_country = `'._DB_PREFIX_.'country`.id_country)';
                $_join .= ' INNER JOIN `'._DB_PREFIX_.'country_lang` ON (`'._DB_PREFIX_.'country_lang`.id_country = `'._DB_PREFIX_.'country`.id_country AND '._DB_PREFIX_.'country_lang.id_lang = '.$context->language->id.')';
                $_join_address = true;
            }
            $_join .= ' LEFT JOIN `'._DB_PREFIX_.'state` ON (`'._DB_PREFIX_.'address`.id_state = `'._DB_PREFIX_.'state`.id_state)';
        }
        //Address invoice join
        if ($_join_address_invoice || $_join_address_invoice_separate) {
            $_select = str_replace(_DB_PREFIX_.'orders.address_invoice', 'CONCAT('._DB_PREFIX_.'address.company, " ",'._DB_PREFIX_.'address.address1, " ", '._DB_PREFIX_.'address.address2, " ", '._DB_PREFIX_.'address.postcode, " ", '._DB_PREFIX_.'address.city, " ", '._DB_PREFIX_.'address.other, " ", IFNULL('._DB_PREFIX_.'state.name, ""), " ", '._DB_PREFIX_.'country_lang.name, " ", '._DB_PREFIX_.'address.phone)', $_select);
            if (!$_join_address_delivery && !$_join_address_delivery_separate) {
                $_join .= ' INNER JOIN `'._DB_PREFIX_.'address` ON (`'._DB_PREFIX_.'orders`.id_address_invoice = `'._DB_PREFIX_.'address`.id_address)';
                $_join .= ' INNER JOIN `'._DB_PREFIX_.'country` ON (`'._DB_PREFIX_.'address`.id_country = `'._DB_PREFIX_.'country`.id_country)';
                $_join .= ' INNER JOIN `'._DB_PREFIX_.'country_lang` ON (`'._DB_PREFIX_.'country_lang`.id_country = `'._DB_PREFIX_.'country`.id_country AND '._DB_PREFIX_.'country_lang.id_lang = '.$context->language->id.')';
                $_join .= ' LEFT JOIN `'._DB_PREFIX_.'state` ON (`'._DB_PREFIX_.'address`.id_state = `'._DB_PREFIX_.'state`.id_state)';
                $_join_address = true;
            }
        }
        //Supplier join
        if ($_join_supplier) {
            if (!in_array('product', $_tables)) {
                if (!$_join_productname) {
                    $_join .= ' INNER JOIN '._DB_PREFIX_.'product ON ('._DB_PREFIX_.'order_detail'.'.product_id = '._DB_PREFIX_.'product'.'.id_product)';
                }
            }
            $_join .= ' INNER JOIN `'._DB_PREFIX_.'supplier` ON (`'._DB_PREFIX_.'product`.id_supplier = `'._DB_PREFIX_.'supplier`.id_supplier)';
        }
        //Manufacturer join
        if ($_join_manufacturer) {
            if (!in_array('product', $_tables)) {
                if (!$_join_productname) {
                    $_join .= ' INNER JOIN '._DB_PREFIX_.'product ON ('._DB_PREFIX_.'order_detail'.'.product_id = '._DB_PREFIX_.'product'.'.id_product)';
                }
            }
            $_join .= ' INNER JOIN `'._DB_PREFIX_.'manufacturer` ON (`'._DB_PREFIX_.'product`.id_manufacturer = `'._DB_PREFIX_.'manufacturer`.id_manufacturer)';
        }
        //Currency join
        if ($_join_currency) {
            $_select = str_replace(_DB_PREFIX_.'orders.currency', _DB_PREFIX_.'currency.iso_code', $_select);
            $_join .= ' INNER JOIN `'._DB_PREFIX_.'currency` ON (`'._DB_PREFIX_.'orders`.id_currency = `'._DB_PREFIX_.'currency`.id_currency)';
        }
        //WHERE
        $_where = ' WHERE 1=1 ';
        if ($report->countries != '' && $report->countries != 'all') {
            $_join_country = true;
            $_where .= ' AND `'._DB_PREFIX_.'address`.id_country IN ('.$report->countries.')';
        }
        if ($report->zones != '' && $report->zones != 'all') {
            $_join_zone = true;
            $_where .= ' AND `'._DB_PREFIX_.'country`.id_zone IN('.$report->zones.')';
        }
        if ($report->payments != '' && $report->payments != 'all' && in_array('orders', $_alltables)) {
            $_payments = explode(',', $report->payments);
            $_strPayments = '';
            foreach ($_payments as $key => $_payment) {
                $_strPayments .= '"'.$_payment.'",';
            }
            $_where .= ' AND `'._DB_PREFIX_.'orders`.module IN ('.rtrim($_strPayments, ',').')';
        }
        if ($report->statuses != '' && $report->statuses != 'all' && in_array('orders', $_alltables)) {
            $_where .= ' AND `'._DB_PREFIX_.'orders`.current_state IN ('.$report->statuses.')';
        }
        if ($report->groups != '' && $report->groups != 'all') {
            $_where .= ' AND `'._DB_PREFIX_.'customer`.id_default_group IN ('.$report->groups.')';
            if (!$_join_customergroup) {
                $_join .= ' INNER JOIN `'._DB_PREFIX_.'customer` ON (`'._DB_PREFIX_.'customer`.id_customer = `'._DB_PREFIX_.'orders`.id_customer)';
            }
        }
        if ($report->manufacturers != '' && $report->manufacturers != 'all') {
            $_where .= ' AND '.$_primary_table.'.id_manufacturer IN ('.$report->manufacturers.')';
        }
        if ($report->suppliers != '' && $report->suppliers != 'all') {
            $_where .= ' AND '.$_primary_table.'.id_supplier IN ('.$report->suppliers.')';
        }
        if ($report->categories != '' && $report->categories != 'all') {
            $_where .= ' AND id_category_default IN ('.$report->categories.')';
        }
        if ((int)$report->data_from > 10) {
            if ($report->date_from != '0000-00-00 00:00:00') {
                $_where .= ' AND '.$_primary_table.'.date_add >= "'.$report->date_from.'"';
            }
            if ($report->date_to != '0000-00-00 00:00:00') {
                $_where .= ' AND '.$_primary_table.'.date_add <= "'.$report->date_to.'"';
            }
        } else {
            $last_quarter= $this->get_quarter(1);
            $current_quarter= $this->get_quarter(0);
            switch ($report->data_from) {
                case '0':
                    $_where .= ' AND '.$_primary_table.'.date_add >= "'.date('Y-m-d').'"';
                    break;
                case '1':
                    $_where .= ' AND '.$_primary_table.'.date_add >= "'.date('Y-m-d', strtotime(date('Y-m-d').' -1 day')).'"';
                    $_where .= ' AND '.$_primary_table.'.date_add <= "'.date('Y-m-d').'"';
                    break;
                case '2':
                    $_where .= ' AND '.$_primary_table.'.date_add >= "'.date('Y-m-d', strtotime('previous monday')).'"';
                    $_where .= ' AND '.$_primary_table.'.date_add <= "'.date('Y-m-d', strtotime('today'. ' +1 day')).'"';
                    break;
                case '3':
                    $_where .= ' AND '.$_primary_table.'.date_add >= "'.date('Y-m-d', strtotime('previous monday'. ' -7 days')).'"';
                    $_where .= ' AND '.$_primary_table.'.date_add <= "'.date('Y-m-d', strtotime('previous monday')).'"';
                    break;
                case '4':
                    $_where .= ' AND MONTH('.$_primary_table.'.date_add) = '.date('m').' AND YEAR('.$_primary_table.'.date_add) = '.date('Y');
                    break;
                case '5':
                    $_where .= ' AND MONTH('.$_primary_table.'.date_add) = '.date('m', strtotime('-1 month')).' AND YEAR('.$_primary_table.'.date_add) = '.date('Y');
                    break;
                case '6':
                    $_where .= ' AND '.$_primary_table.'.date_add >= "'.$current_quarter['start'].'"';
                    break;
                case '7':
                    $_where .= ' AND '.$_primary_table.'.date_add >= "'.$last_quarter['start'].'"';
                    $_where .= ' AND '.$_primary_table.'.date_add <= "'.$last_quarter['end'].'"';
                    break;
                case '8':
                    $_where .= ' AND YEAR('.$_primary_table.'.date_add) = '.date('Y');
                    break;
                case '9':
                    $_where .= ' AND YEAR('.$_primary_table.'.date_add) = '.date('Y', strtotime('-1 year'));
                    break;
                default:
                    break;
            }
        }
        if ($_join_productname || $_join_productdescription || $_join_productdescriptionshort) {
            $_where .= ' AND '._DB_PREFIX_.'product_lang.id_shop = '.$report->id_shop;
        }
        //Country / Zone join
        if (($_join_country || $_join_zone) && !$_join_address_invoice && !$_join_address_delivery && !$_join_address) {
            $_join .= ' INNER JOIN `'._DB_PREFIX_.'address` ON (`'._DB_PREFIX_.'address`.id_address = `'._DB_PREFIX_.'orders`.id_address_invoice)';
            $_join .= ' INNER JOIN `'._DB_PREFIX_.'country` ON (`'._DB_PREFIX_.'address`.id_country = `'._DB_PREFIX_.'country`.id_country)';
        }
        //GROUPS BY
        if (count($_groupby) > 0) {
            $_groupby = ' GROUP BY '.implode(',', $_groupby);
        } else {
            $_groupby = '';
        }
        //ORDER BY
        if (count($_orderby) > 0) {
            $_orderby = ' ORDER BY '.implode(',', $_orderby);
        } else {
            $_orderby = '';
        }
        $_orderby = str_replace(_DB_PREFIX_.'product.product_name', _DB_PREFIX_.'product_lang.name', $_orderby);
        $_orderby = str_replace(_DB_PREFIX_.'product.product_description', _DB_PREFIX_.'product_lang.description', $_orderby);
        $_orderby = str_replace(_DB_PREFIX_.'product.product_description_short', _DB_PREFIX_.'product_lang.description_short', $_orderby);
        $_orderby = str_replace(_DB_PREFIX_.'product.name', _DB_PREFIX_.'product_lang.name', $_orderby);
        $_orderby = str_replace(_DB_PREFIX_.'product.description', _DB_PREFIX_.'product_lang.description', $_orderby);
        $_orderby = str_replace(_DB_PREFIX_.'product.description_short', _DB_PREFIX_.'product_lang.description_short', $_orderby);
        $_orderby = str_replace(_DB_PREFIX_.'customer.customer_group', _DB_PREFIX_.'group_lang.name', $_orderby);
        //UNION??
        if ($_sum) {
            $_sql = '('.$_select.$_from.$_join.$_where.$_groupby.$_orderby.')';
            $_sql_sum = $_select_sum.$_from.$_join.$_where.$_groupby.$_orderby;
            $_sql .= ' UNION ALL ';
            $_sql .= '('.$_sql_sum.')';
        } else {
            $_sql = $_select.$_from.$_join.$_where.$_groupby.$_orderby;
        }
        return $_sql;
    }

    protected function get_quarter($i = 0)
    {
        $start = '0000-00-00 00:00:00';
        $end = '0000-00-00 00:00:00';
        $y = date('Y');
        $m = date('m');
        if ($i > 0) {
            for ($x = 0; $x < $i; $x++) {
                if ($m <= 3) {
                    $y--;
                }
                $diff = $m % 3;
                $m = ($diff > 0) ? $m - $diff:$m-3;
                if ($m == 0) {
                    $m = 12;
                }
            }
        }
        switch ($m) {
            case $m >= 1 && $m <= 3:
                $start = $y.'-01-01 00:00:01';
                $end = $y.'-03-31 00:00:00';
                break;
            case $m >= 4 && $m <= 6:
                $start = $y.'-04-01 00:00:01';
                $end = $y.'-06-30 00:00:00';
                break;
            case $m >= 7 && $m <= 9:
                $start = $y.'-07-01 00:00:01';
                $end = $y.'-09-30 00:00:00';
                break;
            case $m >= 10 && $m <= 12:
                $start = $y.'-10-01 00:00:01';
                $end = $y.'-12-31 00:00:00';
                break;
        }
        return array(
            'start' => $start,
            'end' => $end,
            'start_nix' => strtotime($start),
            'end_nix' => strtotime($end)
        );
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

    protected function formatDecimalsSeparator($price, $currency, $context)
    {
        if (!is_numeric($price)) {
            return $price;
        }
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            return Tools::displayNumber($price);
        }
        if (!$context) {
            $context = Context::getContext();
        }
        if ($currency === null) {
            $currency = $context->currency;
        } elseif (is_int($currency)) {
            $currency = Currency::getCurrencyInstance((int)$currency);
        }
        if (is_array($currency)) {
            $c_format = $currency['format'];
            $c_decimals = (int)$currency['decimals'] * _PS_PRICE_DISPLAY_PRECISION_;
        } elseif (is_object($currency)) {
            $c_format = $currency->format;
            $c_decimals = (int)$currency->decimals * _PS_PRICE_DISPLAY_PRECISION_;
        } else {
            return false;
        }
        $ret = 0;
        if (($is_negative = ($price < 0))) {
            $price *= -1;
        }
        $price = Tools::ps_round($price, $c_decimals);
        if (($c_format == 2) && ($context->language->is_rtl == 1)) {
            $c_format = 4;
        }
        switch ($c_format) {
            /* X 0,000.00 */
            case 1:
                $ret = $price;
                break;
            /* 0 000,00 X*/
            case 2:
                $ret = str_replace('.', ',', $price);
                break;
            /* X 0.000,00 */
            case 3:
                $ret = str_replace('.', ',', $price);
                break;
            /* 0,000.00 X */
            case 4:
                $ret = $price;
                break;
            /* X 0'000.00  Added for the switzerland currency */
            case 5:
                $ret = $price;
                break;
        }
        return (float)$ret;
    }
}
