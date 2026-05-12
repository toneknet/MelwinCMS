<?php
/**
Name: system/ajax/loadHelp.php
Author: Martin Tonek
Author URI: https://www.tonek.se
Description: Handles loading of help
Version: 1.0
Type: ajax
*/
session_start();
include("../configs/config.inc.php");
include("../configs/localconfig.inc.php");
include("../libs/db.cls.php");
include("../libs/input.cls.php");
include("../libs/auth.cls.php");

$db = db::getInstance();
$input = new input();
$strType = $input->get('title');
$strId = $input->get('id');

$auth = auth::getInstance();
if ($auth->isSuperUser()) {
     if ($strType!="") {
         if ($strId=='title') {
              $sql = "SELECT type,title as uttext FROM [table] WHERE type=\"" . $strType . "\"";
         } elseif ($strId=='text') {
              $sql = "SELECT type,text as uttext FROM [table] WHERE type=\"" . $strType . "\"";
         }
     }
     $arrData = $db->o_get_row('help',$sql);
     print str_replace('"','\"',$arrData['uttext']);
}
