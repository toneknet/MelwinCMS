<?php
/**
Name: system/ajax/saveHelp.php
Author: Martin Tonek
Author URI: https://www.tonek.se
Description: Handles loading of help
Version: 1.0
Type: ajax
*/
session_start();
error_reporting(E_ALL & ~E_NOTICE);
include("../configs/config.inc.php");
include("../configs/localconfig.inc.php");
include("../libs/db.cls.php");
include("../libs/input.cls.php");
include("../libs/auth.cls.php");

$db = db::getInstance();
$input = new input();
$arrData = $input->postAll();
$strType = $input->get('title');

$auth = auth::getInstance();
if ($auth->isSuperUser()) {
    if ($strType!="") {
        if ($arrData['id']=='title') {
            $sql = "INSERT INTO " . $GLOBALS['CONFIG']['dbPrefix'] . "help (type,title) VALUES (\"" . $strType . "\",\"" . $arrData['value'] . "\") ON DUPLICATE KEY UPDATE title=\"" . $arrData['value'] . "\"";
        } elseif ($arrData['id']=='text') {
            $sql = "INSERT INTO " . $GLOBALS['CONFIG']['dbPrefix'] . "help (type,text) VALUES (\"" . $strType . "\",\"" . $arrData['value'] . "\") ON DUPLICATE KEY UPDATE text=\"" . $arrData['value'] . "\"";
        }
    }
    if (!$db->o_query($sql)) {
        print "ERROR ";
    }
    print str_replace("\n", "<br>", $_POST['value']);
}
