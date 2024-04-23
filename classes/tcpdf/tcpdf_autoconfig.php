<?php
//============================================================+
// File name   : tcpdf_autoconfig.php
// Version     : 1.1.1
// Begin       : 2013-05-16
// Last Update : 2014-12-18
// Authors     : Nicola Asuni - Tecnick.com LTD - www.tecnick.com - info@tecnick.com
// License     : GNU-LGPL v3 (http://www.gnu.org/copyleft/lesser.html)
// -------------------------------------------------------------------
// Copyright (C) 2011-2014 Nicola Asuni - Tecnick.com LTD
//
// This file is part of TCPDF software library.
//
// TCPDF is free software: you can redistribute it and/or modify it
// under the terms of the GNU Lesser General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// TCPDF is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU Lesser General Public License for more details.
//
// You should have received a copy of the License
// along with TCPDF. If not, see
// <http://www.tecnick.com/pagefiles/tcpdf/LICENSE.TXT>.
//
// See LICENSE.TXT file for more information.
// -------------------------------------------------------------------
//
// Description : Try to automatically configure some TCPDF
//               constants if not defined.
//
//============================================================+

/**
 * @file
 * Try to automatically configure some TCPDF constants if not defined.
 * @package com.tecnick.tcpdf
 * @version 1.1.1
 */

// DOCUMENT_ROOT fix for IIS Webserver
if ((!isset($_SERVER['DOCUMENT_ROOT'])) OR (empty($_SERVER['DOCUMENT_ROOT']))) {
	if(isset($_SERVER['SCRIPT_FILENAME'])) {
		$_SERVER['DOCUMENT_ROOT'] = str_replace( '\\', '/', substr($_SERVER['SCRIPT_FILENAME'], 0, 0-strlen($_SERVER['PHP_SELF'])));
	} elseif(isset($_SERVER['PATH_TRANSLATED'])) {
		$_SERVER['DOCUMENT_ROOT'] = str_replace( '\\', '/', substr(str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']), 0, 0-strlen($_SERVER['PHP_SELF'])));
	} else {
		// define here your DOCUMENT_ROOT path if the previous fails (e.g. '/var/www')
		$_SERVER['DOCUMENT_ROOT'] = '/';
	}
}
$_SERVER['DOCUMENT_ROOT'] = str_replace('//', '/', $_SERVER['DOCUMENT_ROOT']);
if (substr($_SERVER['DOCUMENT_ROOT'], -1) != '/') {
	$_SERVER['DOCUMENT_ROOT'] .= '/';
}

// Load main configuration file only if the K_TCPDF_EXTERNAL_CONFIG constant is set to false.
if (!defined('K_TCPDF_EXTERNAL_CONFIG_AR') OR !K_TCPDF_EXTERNAL_CONFIG_AR) {
	// define a list of default config files in order of priority
	$tcpdf_config_files = array(dirname(__FILE__).'/config/tcpdf_config.php', '/etc/php-tcpdf/tcpdf_config.php', '/etc/tcpdf/tcpdf_config.php', '/etc/tcpdf_config.php');
	foreach ($tcpdf_config_files as $tcpdf_config) {
		if (@file_exists($tcpdf_config) AND is_readable($tcpdf_config)) {
			require_once($tcpdf_config);
			break;
		}
	}
}

if (!defined('K_PATH_MAIN_AR')) {
	define ('K_PATH_MAIN_AR', dirname(__FILE__).'/');
}

if (!defined('K_PATH_FONTS_AR')) {
	define ('K_PATH_FONTS_AR', K_PATH_MAIN_AR.'fonts/');
}

if (!defined('K_PATH_URL_AR')) {
	$k_path_url = K_PATH_MAIN_AR; // default value for console mode
	if (isset($_SERVER['HTTP_HOST']) AND (!empty($_SERVER['HTTP_HOST']))) {
		if(isset($_SERVER['HTTPS']) AND (!empty($_SERVER['HTTPS'])) AND (strtolower($_SERVER['HTTPS']) != 'off')) {
			$k_path_url = 'https://';
		} else {
			$k_path_url = 'http://';
		}
		$k_path_url .= $_SERVER['HTTP_HOST'];
		$k_path_url .= str_replace( '\\', '/', substr(K_PATH_MAIN_AR, (strlen($_SERVER['DOCUMENT_ROOT']) - 1)));
	}
	define ('K_PATH_URL_AR', $k_path_url);
}

if (!defined('K_PATH_IMAGES_AR')) {
	$tcpdf_images_dirs = array(K_PATH_MAIN_AR.'examples/images/', K_PATH_MAIN_AR.'images/', '/usr/share/doc/php-tcpdf/examples/images/', '/usr/share/doc/tcpdf/examples/images/', '/usr/share/doc/php/tcpdf/examples/images/', '/var/www/tcpdf/images/', '/var/www/html/tcpdf/images/', '/usr/local/apache2/htdocs/tcpdf/images/', K_PATH_MAIN_AR);
	foreach ($tcpdf_images_dirs as $tcpdf_images_path) {
		if (@file_exists($tcpdf_images_path)) {
			define ('K_PATH_IMAGES_AR', $tcpdf_images_path);
			break;
		}
	}
}

if (!defined('PDF_HEADER_LOGO_AR')) {
	$tcpdf_header_logo = '';
	if (@file_exists(K_PATH_IMAGES_AR.'tcpdf_logo.jpg')) {
		$tcpdf_header_logo = 'tcpdf_logo.jpg';
	}
	define ('PDF_HEADER_LOGO_AR', $tcpdf_header_logo);
}

if (!defined('PDF_HEADER_LOGO_WIDTH_AR')) {
	if (!empty($tcpdf_header_logo)) {
		define ('PDF_HEADER_LOGO_WIDTH_AR', 30);
	} else {
		define ('PDF_HEADER_LOGO_WIDTH_AR', 0);
	}
}

if (!defined('K_PATH_CACHE_AR')) {
	$K_PATH_CACHE_AR = ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir();
	if (substr($K_PATH_CACHE_AR, -1) != '/') {
		$K_PATH_CACHE_AR .= '/';
	}
	define ('K_PATH_CACHE_AR', $K_PATH_CACHE_AR);
}

if (!defined('K_BLANK_IMAGE_AR')) {
	define ('K_BLANK_IMAGE_AR', '_blank.png');
}

if (!defined('PDF_PAGE_FORMAT_AR')) {
	define ('PDF_PAGE_FORMAT_AR', 'A4');
}

if (!defined('PDF_PAGE_ORIENTATION_AR')) {
	define ('PDF_PAGE_ORIENTATION_AR', 'P');
}

if (!defined('PDF_CREATOR_AR')) {
	define ('PDF_CREATOR_AR', 'TCPDF');
}

if (!defined('PDF_AUTHOR_AR')) {
	define ('PDF_AUTHOR_AR', 'TCPDF');
}

if (!defined('PDF_HEADER_TITLE_AR')) {
	define ('PDF_HEADER_TITLE_AR', 'TCPDF Example');
}

if (!defined('PDF_HEADER_STRING_AR')) {
	define ('PDF_HEADER_STRING_AR', "by idnovate.com");
}

if (!defined('PDF_UNIT_AR')) {
	define ('PDF_UNIT_AR', 'mm');
}

if (!defined('PDF_MARGIN_HEADER_AR')) {
	define ('PDF_MARGIN_HEADER_AR', 5);
}

if (!defined('PDF_MARGIN_FOOTER_AR')) {
	define ('PDF_MARGIN_FOOTER_AR', 10);
}

if (!defined('PDF_MARGIN_TOP_AR')) {
	define ('PDF_MARGIN_TOP_AR', 27);
}

if (!defined('PDF_MARGIN_BOTTOM_AR')) {
	define ('PDF_MARGIN_BOTTOM_AR', 25);
}

if (!defined('PDF_MARGIN_LEFT_AR')) {
	define ('PDF_MARGIN_LEFT_AR', 15);
}

if (!defined('PDF_MARGIN_RIGHT_AR')) {
	define ('PDF_MARGIN_RIGHT_AR', 15);
}

if (!defined('PDF_FONT_NAME_MAIN_AR')) {
	define ('PDF_FONT_NAME_MAIN_AR', 'helvetica');
}

if (!defined('PDF_FONT_SIZE_MAIN_AR')) {
	define ('PDF_FONT_SIZE_MAIN_AR', 10);
}

if (!defined('PDF_FONT_NAME_DATA_AR')) {
	define ('PDF_FONT_NAME_DATA_AR', 'helvetica');
}

if (!defined('PDF_FONT_SIZE_DATA_AR')) {
	define ('PDF_FONT_SIZE_DATA_AR', 8);
}

if (!defined('PDF_FONT_MONOSPACED_AR')) {
	define ('PDF_FONT_MONOSPACED_AR', 'courier');
}

if (!defined('PDF_IMAGE_SCALE_RATIO_AR')) {
	define ('PDF_IMAGE_SCALE_RATIO_AR', 1.25);
}

if (!defined('HEAD_MAGNIFICATION_AR')) {
	define('HEAD_MAGNIFICATION_AR', 1.1);
}

if (!defined('K_CELL_HEIGHT_RATIO_AR')) {
	define('K_CELL_HEIGHT_RATIO_AR', 1.25);
}

if (!defined('K_TITLE_MAGNIFICATION_AR')) {
	define('K_TITLE_MAGNIFICATION_AR', 1.3);
}

if (!defined('K_SMALL_RATIO_AR')) {
	define('K_SMALL_RATIO_AR', 2/3);
}

if (!defined('K_THAI_TOPCHARS_AR')) {
	define('K_THAI_TOPCHARS_AR', true);
}

if (!defined('K_TCPDF_CALLS_IN_HTML_AR')) {
	define('K_TCPDF_CALLS_IN_HTML_AR', false);
}

if (!defined('K_TCPDF_THROW_EXCEPTION_ERROR_AR')) {
	define('K_TCPDF_THROW_EXCEPTION_ERROR_AR', false);
}

if (!defined('K_TIMEZONE_AR')) {
	define('K_TIMEZONE_AR', @date_default_timezone_get());
}

//============================================================+
// END OF FILE
//============================================================+
