<?php

/*
 * The MIT License
 *
 * Copyright 2015 Martin Tonek <martin@tonek.se>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/*
 * VIKTIGT!
 * Glöm inte att ange "allowAjax" i tabellens data array
 * Man kan ange en fil genom 'ajaxFile' i tabellens array. OBS! Man måste ange länken som klassbibliotek/ajaxfil.php
 *
 * FINNS NGN BUGG I DETTA!!!
 *
 * Vad krävs för att trigga?
 *
 * Bygga detta så att den har stöd för HOOKS? = alltså att man kan lägga till
 * funktioner från olika moduler. Hur? var? om?
 * 
 * RESPONSE = JSON.
 * REQUIRE = 'status' => 200 (ok) or 400 (bad req) or 401 (not auth)
 * 
 *
 */
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/
session_start();
//error_reporting(E_ALL & ~E_NOTICE);
include("../configs/config.inc.php");
include("../configs/localconfig.inc.php");
// Set default timezone
if (isset($GLOBALS['CONFIG']['timezone'])) {
    date_default_timezone_set($GLOBALS['CONFIG']['timezone']);
}
include("../libs/db.cls.php");
include("../libs/input.cls.php");
include("../libs/auth.cls.php");
include("../functions.inc.php");
define('ROOTPATH', str_replace("system/ajax","",__DIR__));
define( 'SYSPATH',str_replace("ajax","",__DIR__));
define('MODPATH', str_replace("ajax","",__DIR__)."modules/");
define('PLUGINPATH',str_replace("system/ajax","resources/plugins/",__DIR__));
define('URLBASE', $GLOBALS['CONFIG']['www-root']);
//showErrors();
// Hämta tillåtna sidor
$db = db::getInstance();
$arrTmp = $db->o_get_all('ajax','SELECT * FROM [table]');
$arrAllowedScripts = [];
$registeredAjaxNames = [];
// $arrTmp = $db->o_get_all('','SELECT * FROM mt_ajax');
foreach ($arrTmp as $key => $value) {
  $registeredAjaxNames[$value['name']] = true;
  if (!empty($value['deleted'])) continue;
  $arrAllowedScripts[$value['auth']][$value['name']] = $value['script'];

}
// Built-in CORE route for sites predating AJAX registration. Deleted entries stay disabled.
if (!isset($registeredAjaxNames['menumaker'])) {
  $arrAjax = [];
  require MODPATH . 'menumaker/config/ajax.php';
  $arrAllowedScripts[1]['menumaker'] = $arrAjax['menumaker']['script'];
}
/*print "<pre >";
print_r($arrAllowedScripts);
print "</pre>";*/
//print __FILE__ . "-" .  __LINE__ . "<br />";
//Ladda main språk här BUGGFIX 220502
require_once(SYSPATH . "lang/sv.php");
// $arrModuleLibs = array_diff(scandir(SYSPATH . "modules"), array('..', '.'));
$arrModuleLibs = getModulePathsOnly();
// print_r($arrModuleLibs);
foreach ($arrModuleLibs as $modulelib) {
    // Kolla språk här
    $langfile = SYSPATH . "modules/" . $modulelib . "/lang/" . $GLOBALS['CONFIG']['language'] . ".php";
    if (is_file($langfile)) {
      require_once($langfile);
    }
}
//print __FILE__ . "-" .  __LINE__ . "<br />";

// Hämtar action länken
$action = isset($_GET['action']) && is_string($_GET['action']) ? $_GET['action'] : '';
// Kollar om inloggad så man får lov att påverka
$auth = auth::getInstance();
//print __FILE__ . "-" .  __LINE__ . "<br />";
if($auth->isAuthed()) {
//  print __FILE__ . "-" .  __LINE__ . " - " . $action. "<br />";
  if(isset($arrAllowedScripts[1][$action])) {
    //print "Fanns!! (Auth)<br />";
//print __FILE__ . "-" .  __LINE__ . "<br />";

    $file = MODPATH . $arrAllowedScripts[1][$action];
    //print $file ."<br />";
    if(is_file($file)) {
      //print "File exist<br />";
      include_once($file);
      die();
    }
  }
  // Also check not authed scripts here
  if(isset($arrAllowedScripts[0][$action])) {
//print __FILE__ . "-" .  __LINE__ . "<br />";

    //print "Fanns!! (noAuth)<br />";
    $file = MODPATH . $arrAllowedScripts[0][$action];
    //print $file ."<br />";
    if(is_file($file)) {
      //print "File exist<br />";
      include_once($file);
      die();
    }
  }
//print __FILE__ . "-" .  __LINE__ . "\n";
    //$arrTableDataFiles = array_diff(scandir("../tables"), array('..', '.'));


    // $arrModuleLibs = array_diff(scandir(SYSPATH . "modules"), array('..', '.'));
    // Module routes finish above; only legacy table requests need DCA definitions.
    $table = isset($_GET['table']) && is_string($_GET['table']) ? $_GET['table'] : '';
    if ($table === '') {
      http_response_code(400);
      alertbox('error', 'AJAX-anropet saknar giltig modulregistrering eller tabell.');
      exit;
    }
    foreach ($arrModuleLibs as $modulelib) {

          // Kolla språk här
          $langfile = SYSPATH . "modules/" . $modulelib . "/lang/" . $GLOBALS['CONFIG']['language'] . ".php";
          if (is_file($langfile)) {
            //print $langfile."\n";
            require_once($langfile);
          }
           // Ladda tabeller här
           $dir = SYSPATH . "modules/" . $modulelib . "/dca/";
           if (is_dir($dir)) {
               $arrTableDataFiles = array_diff(scandir($dir), array('..', '.','index.php'));
               if (!empty($arrTableDataFiles)) {
                   foreach ($arrTableDataFiles as $file) {
                       if ( is_file( $dir.$file ) ) {
                           //print $dir. $file."\n";
                           require_once( $dir. $file );
                       }
                   }
               }
           }

    }


    //språk med
//print __FILE__ . "-" .  __LINE__ . "\n";
    if (!isset($GLOBALS['TABLE'][$table]['allowAjax'])) {
    //    print "#0005: Error occured!";
        alertbox('error','Ett fel har uppstått (#0005)');
        exit;
    } elseif (!$GLOBALS['TABLE'][$table]['allowAjax']) {
    //    print "#0006: Error occured!";
        alertbox('error','Ett fel har uppstått (#0006)');
        exit;
    }

    // 231218 - Tog bort denna då anrop görs tidigare i filen
    // $db = db::getInstance();
    // DENNA FUNKTION SKALL TAS BORT!!!!!!! ENDAST VIA AJAX.PHP... ELLER SKALL MAN
    // LÄGGA TILL DET HÄR ISTÄLLER OCH INTE HA AJAX.PHP? HUR ISF VETA OM AUTH ELLER
    // INTE? TROR ATT DET ÄR BÄTTRE ATT GÖRA AJAX ANROP VIA AJAX.PHP



    if(isset($GLOBALS['TABLE'][$table]['ajaxFile']) && $GLOBALS['TABLE'][$table]['ajaxFile'] != "") {
      $ajaxfile = MODPATH ."" .  $GLOBALS['TABLE'][$table]['ajaxFile'];
      //print "loading file: {$ajaxfile}<br>";
      include($ajaxfile);
      //print MODPATH ."{$table}/" .  $GLOBALS['TABLE'][$table]['ajaxFile'];

    }

    //print __FILE__ . "-" .  __LINE__ . "\n";

    /*

    NEDAN SKA TAS BORT OCH RENODLAS TILL SINA EGNA MODULER. KAN BLI ATT MAN FÅR FLYTTA DBLIST TILL EN EGEN MODUL PÅ GRUND AV DETTA

    Actions:         Module:         Beskrivning:
    reorder          dblist          js/common.php:29
    booking                          js/common.php:62
    driver_booking   ???

    */



    // Sorting like dblists
    if ($_GET['action']=="reorder" && 1==2) {
        alertbox('error','Anväder DIREKT koppling av ' . " " . __FILE__ . "@" . __LINE__);
        die();
        /*
         * Denna funktion sorterar tabeller i ordning
         */
        $result = $_GET["dblist"];
        $position=0;
        foreach($result as $value) {
            $position++;
            if (intval($value)!=$value) {
    //            print "#0009: Error occured!";
                alertbox('error','Ett fel har uppstått');
                exit;
            }
            if ($db->o_update($_GET['table'],array('ordning'=>$position),"id={$value}")!=true) {
    //            print "#0010: Error occured!";
                alertbox('error','Ett fel har uppstått');
                exit;
            }
        }
    //    print "Sparat";
        alertbox('success','Händelsen har sparats');
    } elseif ($_GET['action']=="booking" && 1==2) {
        alertbox('error','Anväder DIREKT koppling av ' . " " . __FILE__ . "@" . __LINE__);
        die();
        //print "<script>console.log('OJ då!');</script>";
        /*
         *  Denna funktion skall leta efter en bokning.
         * Glöm inte att validera alla inkommande data!
         *
         * FINNS BUGG!
         * Kolla id 5022 i hallens_booking!
         * Den känner inte av alla resor som finns under just den perioden.
         * Vad är fel? Är SQL satsen fel?
         *
         *
         *
         */
        extract($_POST);
        // Kollar först hashen här
        if (salt( $table . date("Y-m-d") . $id)) {
            // Hämtar bussar som är ok här
            $arrBus = $db->o_get_all('vehicle',"SELECT * FROM [table] WHERE b_visible=1 AND companyid=".  getCompanyID()." AND deleted=0 ORDER BY ordning");
            // Validerar buss här
            if (is_array($arrBus[$bus])) {
                // Validerar datum här
                if (validateDate($startdate) AND validateDate($stopdate)) {
                    if (validateTime($starttime) AND validateTime($stoptime) AND ($starttime != "00:00" OR $stopttime != "00:00")) {
                        // Kolla krock här!!!
                        $start = formatDateTime($startdate,$starttime);
                        $stop = formatDateTime($stopdate,$stoptime);
                        $id=intval($id);
                        if ($id>0) {
                            $sqlID = " AND id!={$id}";
                        } else {
                            $sqlID = "";
                        }
                        // Denna hittar inte alla!! VARFÖR!!!
                        /*
                         * SELECT * FROM hallens_booking WHERE bus=35 AND
((startdatex BETWEEN CAST('2015-06-10 04:30:00' AS DATETIME) AND CAST('2015-06-30 08:30:00' AS DATETIME)) OR
(stopdatex BETWEEN CAST('2015-06-10 04:30:00' AS DATETIME) AND CAST('2015-06-30 08:30:00' AS DATETIME))) AND companyid=1 AND id!=5022;
                         */
//                        $arrCrashed = $db->o_get_all('booking', "SELECT * FROM [table] WHERE bus=$bus AND (('$start' BETWEEN startdatex AND stopdatex) OR ('$stop' BETWEEN startdatex AND stopdatex)) AND companyid=" . getCompanyID() . $sqlID);
                        $arrCrashed = $db->o_get_all('booking', "SELECT * FROM [table] WHERE bus=$bus AND ((startdatex BETWEEN CAST('$start' AS DATETIME) AND CAST('$stop' AS DATETIME)) OR (stopdatex BETWEEN CAST('$start' AS DATETIME) AND CAST('$stop' AS DATETIME) )) AND companyid=" . getCompanyID() . " AND deleted=0 " . $sqlID);
                        if (count($arrCrashed)>0) {
                            print "Din resa krockar med följande resa/or:\n\n";
                            foreach ($arrCrashed as $resa) {
                                print "Resa: " . $resa['resan']."\n";
                                print "Start: " . $resa['startdate'] . " " . $resa['starttime'] . "\n";
                                print "Slut: " . $resa['stopdate'] . " " . $resa['stoptime'] . "\n";
                                print "Chaufför: " . $resa['chaffor']."\n";
                                print "----------------------------------\n";
                            }
                        }
                    }
                }
            }
        }
        // Denna används inte!!
    } elseif ($_GET['action']=="driver_booking") {
        alertbox('error','Anväder DIREKT koppling av ' . __FILE__);
      
        /*
         *  Kollar om chauffören redan är uppbokad på något!
         *
         * Bortser från denna buss
         * Kollar perioden mot övriga bussar
         *
         *
         *
         *
         */
         //print "Hej på dig din bäver";
         /*print "<pre>";
         print_r($_POST);
         print "<pre>";*/
        extract($_POST);
        // Kollar först hashen här
        if (salt( $table . date("Y-m-d") . $id)) {
            // Hämtar bussar som är ok här
            $arrBus = $db->o_get_all('vehicle',"SELECT * FROM [table] WHERE b_visible=1 AND companyid=".  getCompanyID()." AND deleted=0 ORDER BY ordning");
            // Validerar buss här
            if (is_array($arrBus[$bus])) {
                // Validerar datum här
                if (validateDate($startdate) AND validateDate($stopdate)) {
                    if (validateTime($starttime) AND validateTime($stoptime) AND ($starttime != "00:00" OR $stopttime != "00:00")) {
                        // Kolla krock här!!!
                        $start = formatDateTime($startdate,$starttime);
                        $stop = formatDateTime($stopdate,$stoptime);
                        $id=intval($id);
                        if ($id>0) {
                            $sqlID = " AND id!={$id}";
                        } else {
                            $sqlID = "";
                        }
                        // Denna hittar inte alla!! VARFÖR!!!
                        /*
                         * SELECT * FROM hallens_booking WHERE bus=35 AND
((startdatex BETWEEN CAST('2015-06-10 04:30:00' AS DATETIME) AND CAST('2015-06-30 08:30:00' AS DATETIME)) OR
(stopdatex BETWEEN CAST('2015-06-10 04:30:00' AS DATETIME) AND CAST('2015-06-30 08:30:00' AS DATETIME))) AND companyid=1 AND id!=5022;
                         */
//                        $arrCrashed = $db->o_get_all('booking', "SELECT * FROM [table] WHERE bus=$bus AND (('$start' BETWEEN startdatex AND stopdatex) OR ('$stop' BETWEEN startdatex AND stopdatex)) AND companyid=" . getCompanyID() . $sqlID);
                        $arrCrashed = $db->o_get_all('booking', "SELECT * FROM [table] WHERE bus=$bus AND ((startdatex BETWEEN CAST('$start' AS DATETIME) AND CAST('$stop' AS DATETIME)) OR (stopdatex BETWEEN CAST('$start' AS DATETIME) AND CAST('$stop' AS DATETIME) )) AND companyid=" . getCompanyID() . " AND deleted=0 " . $sqlID);
                        if (count($arrCrashed)>0) {
                            //print "Din resa krockar med följande resa/or:\n\n";
                            foreach ($arrCrashed as $resa) {
                              //  print "Resa: " . $resa['resan']."\n";
                              //  print "Start: " . $resa['startdate'] . " " . $resa['starttime'] . "\n";
                              //  print "Slut: " . $resa['stopdate'] . " " . $resa['stoptime'] . "\n";
                              //  print "Chaufför: " . $resa['chaffor']."\n";
                              //  print "----------------------------------\n";
                            }
                        }
                    }
                }
            }
        }
    }






    //print json_encode(array('success'=> 0));
} else {
  // Bygga in ett automatiskt hash system här kanske?




  // Allow some of the actions to be allowed in here!
  //print $GLOBALS['CRON']['/buscalender/cron.php'];
  /**
   * Ide på hur det kan se ut..
   * $GLOBALS['AJAX']['auth'/'noauth']['nametocall'] = scriptfile
   *
   *
   *
   */
   //print __FILE__ . "-" .  __LINE__ . "<br />";

   if(isset($arrAllowedScripts[0][$action])) {
     //alertbox('error','Anväder DIREKT koppling av ' . __FILE__ . "   " . $arrAllowedScripts[0][$action]);
     //die();
     //print "Fanns!!<br />";
     $file = MODPATH . $arrAllowedScripts[0][$action];
     //print $file ."<br />";
     if(is_file($file)) {
       //print "File exist<br />";
       include_once($file);
       die();
     }
   } else {

     die('Failed to make a deserved party announcement');
  //print json_encode(array('reload'=> 1,'msg' => 'You deserve to rotten in jail!'));
  ?>
<!--  <script>
    window.parent.location = document.referrer;
  </script>-->
    <?php
  }
  die();
}

function formatDateTime($date,$time) {
    list($year,$month,$day) = explode("-",$date);
    list($hour,$minute,$second) = explode(":",$time);
    return date("Y-m-d H:i:s", mktime($hour, $minute, $second, $month, $day, $year));
}

function validateDate($datDate) {
    if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $datDate)) {
        return true;
    }else{
        return false;
    }
}

function validateTime($datTime)
{
    if (preg_match("/^([0-2][0-3]|[01]?[1-9]):([0-5]?[0-9]):([0-5]?[0-9])$/", $datTime)) {
        return true;
    } else {
        return false;
    }
}

function alertbox($strType,$strMsg){
    if (in_array($strType, array('error','warning','success','notice'))) {
        print "<div class=\"alert-box {$strType}\">{$strMsg}</div>";
        print "<script>\n";
        print "$(document).ready(function() {\n";
        print "    $(\".alert-box\").show().delay(3000).fadeOut('slow');\n";
        print "});\n";
        print "</script>\n";
    }
}
