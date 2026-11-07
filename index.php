<?php

/**
Name: index.php
Author: Martin Tonek
Author URI: https://www.tonek.se
Description:
Version: 1.0
Type: Kernel
*/
// declare(strict_types=1); 
$time_start = microtime(true);
$timeToLive = 3600 * 12; // 12 timmar ska den leva är det tänkt
// server should keep session data for AT LEAST xx hour
ini_set('session.gc_maxlifetime', $timeToLive);
// each client should remember their session id for EXACTLY xx hour
session_set_cookie_params($timeToLive);
session_start();
// Create all needed global variables
define('ROOTPATH', dirname(__FILE__) . "/");
define('SYSPATH', dirname(__FILE__) . "/system/");
define('MODPATH', dirname(__FILE__) . "/system/modules/");
define('TPLPATH', dirname(__FILE__) . "/tpl/");
define('PLUGINPATH', dirname(__FILE__) . "/resources/plugins/");
// Load Configs
include(SYSPATH . "configs/config.inc.php");
if (!is_file(SYSPATH . "configs/localconfig.inc.php")) {
  // Install Mode
  if (!is_file(ROOTPATH . "install.php")) {
    http_response_code(500);
    die('Configuration is missing and the installer is unavailable. Please contact your system administrator for help. For more information, visit <a href="https://github.com/toneknet/MelwinCMS">MelwinCMS on GitHub</a>.');
  }
  header("Location: install.php");
  die();
} else {
  include(SYSPATH . "configs/localconfig.inc.php");
}

// FIX 2025-02-14 - Fixed the date mystery
if (isset($GLOBALS['CONFIG']['timezone'])) {
  date_default_timezone_set($GLOBALS['CONFIG']['timezone']);
} else {
  date_default_timezone_set('Europe/Stockholm');
}

define('URLBASE', $GLOBALS['CONFIG']['www-root']);
define('RESBASE', URLBASE . "/resources/");
// Här laddas språkfilerna som har med systemet att göra
// Load languagefiles
if ($GLOBALS['CONFIG']['language'] == "") {
  $GLOBALS['CONFIG']['language'] = "en";
}
if ($GLOBALS['CONFIG']['language'] != "") {
  $strLang = SYSPATH . "lang/" . $GLOBALS['CONFIG']['language'] . ".php";
  if (is_file($strLang)) {
    require_once $strLang;
  }
  $strLang = MODPATH . "system/lang/" . $GLOBALS['CONFIG']['language'] . ".php";
  if (is_file($strLang)) {
    require_once($strLang);
  }
}

// Hantera fel och functioner
require_once(SYSPATH . "libs/error.inc.php");
require_once(SYSPATH . "functions.inc.php");

// Ladda kärna
require_once(SYSPATH . "kernel.cls.php");

// Laddar och startar script här
$mt = new kernel();
$mt->run();

// End Of File
$time_end = microtime(true);
$execution_time = ($time_end - $time_start) * 100;
print  "\n<!-- Page execution time " . round($execution_time, 5) . " ms -->\n";
print "<!-- EOF -->";
