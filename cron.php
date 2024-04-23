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

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');
include(dirname(__FILE__).'/advancedreports.php');
include_once(dirname(__FILE__).'/classes/AdvancedReportsConfiguration.php');
include_once(dirname(__FILE__).'/controllers/admin/AdminAdvancedReportsConfController.php');

$module = new Advancedreports();

if (Tools::encrypt($module->name) != Tools::getValue('secure_key') || !Module::isInstalled($module->name)) {
    die('Bad token');
}

if ($module->active) {
    try {
        chdir('../..');
        $admin_dir = '';
        $directories = scandir(getcwd());
        foreach ($directories as $key => $directory) {
            if (strpos($directory, '.') === 0 || strpos($directory, '..') === 0) {
                continue;
            }
            if (is_dir($directory)) {
                if ($dh = opendir($directory)){
                    while (($file = readdir($dh)) !== false){
                        if (strpos($file, '.') === 0 || strpos($file, '..') === 0) {
                            continue;
                        }
                        if ($file === 'autoupgrade') {
                            $admin_dir = $directory;
                            break;
                        }
                        if ($admin_dir != '') {
                            break;
                        }
                    }
                    rewinddir();
                    closedir($dh);
                }
            }
            if ($admin_dir != '') {
                break;
            }
        }
        $context = Context::getContext();
        $employee = new Employee((int)1);
        $context->employee = $employee;
        if (!defined('_PS_ADMIN_DIR_')) {
            if ($admin_dir == '') {
                die('ERROR - BLANK PS_ADMIN_DIR');
            } else {
                define('_PS_ADMIN_DIR_', dirname(__FILE__).'/../../'.$admin_dir.'/');
            }
        }
        define('_PS_BO_ALL_THEMES_DIR_', _PS_ADMIN_DIR_.'/themes/');
        $class = new AdvancedReportsConfiguration();
        $class->processCron();
        echo 'OK';
    } catch (Exception $e) {
        die('ERROR '.$e->getMessage());
    }
}
