<?php
/**
Name: Functions
Author: Martin Tonek
Author URI: https://www.tonek.se
Description: The helper file of the system
Version: 0.2 (SYNCED)
Type: SYSTEM
 */

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

/**
 * The autoload class
 * @param string $class_name The name of the class
 */
// PHP 7 Incompatible
// function __autoload($class_name) {
//     if (is_file(SYSPATH . 'libs/'. $class_name . '.cls.php')) {
//         require_once (SYSPATH . 'libs/'. $class_name . '.cls.php');
//     } elseif (is_file(SYSPATH . 'modules/'. $class_name . '/'. $class_name . '.php')) {
//         require_once (SYSPATH . 'modules/'. $class_name . '/'. $class_name . '.php');
//     }
//
// }
// Make it PHP 7 compatible
spl_autoload_register(function ($class_name) {
  if (is_file(SYSPATH . 'libs/' . $class_name . '.cls.php')) {
    require_once(SYSPATH . 'libs/' . $class_name . '.cls.php');
  } elseif (is_file(SYSPATH . 'modules/' . $class_name . '/' . $class_name . '.php')) {
    require_once(SYSPATH . 'modules/' . $class_name . '/' . $class_name . '.php');
  }
  // include 'classes/' . $class . '.class.php';
});

function scan($strPath)
{
  if (!is_dir($strPath) || !is_readable($strPath)) {
    return [];
  }
  $entries = scandir($strPath);
  return $entries === false ? [] : array_diff($entries, array('..', '.'));
}

function getModulePaths()
{
  return (array) array_filter(scan(MODPATH), function ($key) {
    return strpos($key, '_') !== 0;
  }); //, ARRAY_FILTER_USE_KEY
}

function getModulePathsOnly()
{
  $arrPath = scan(MODPATH);
  foreach ($arrPath as $key => $value) {
    if (strpos($value, '_') === 0) unset($arrPath[$key]);
    if (str_contains($value, '.php')) unset($arrPath[$key]);
    if (str_contains($value, '.txt')) unset($arrPath[$key]);
  }
  return (array) $arrPath;
}



function create_ID_CRC($data)
{
  return "&id=" . $data . "&c=" . salt($data, 10);
}

function check_ID_CRC($id, $crc)
{
  return salt($id, 10) == $crc;
}

function check_ID_CRCx()
{
  if (isset($_GET['c']) && isset($_GET['id'])) {
    return salt($_GET['id'], 10) == $_GET['c'];
  }
  return 0;
}

/**
 * Retrives all dates between and included two dates
 * @param  [string] $startDate               Startdate
 * @param  [string] $endDate                 Stopdate
 * @return [array]                           Array with all dates
 */
function getAllDates($startDate, $endDate)
{
  $earlier = new DateTime($startDate);
  $later = new DateTime($endDate);
  $abs_diff = intval($later->diff($earlier)->format("%a"));
  list($nYear, $nMonth, $nDay) = explode("-", $startDate);
  // Go through every day
  $arrDates = array();
  for ($i = 0; $i <= $abs_diff; $i++) {
    $arrDates[] = date("Y-m-d", mktime(0, 0, 0, intval($nMonth), intval($nDay) + $i, intval($nYear)));
  }
  return (array) $arrDates;
}

/**
 * Ger tillbaka en url med variabler och/eller rensad
 * BEHÖVER BYGGA OM DENNA FUNKTION SÅ ATT DEN MERGE t.ex. ACTIONS
 * 
 * SKA FLYTTAS TILL ROUTE::GETURL()
 *
 * @return string
 */
function getURL($arrvalues = array(), $clear = false)
{ // Testar att göra den till default false istället. dblist fungerade inte annars

  // Hämtar grund url här
  $url = $GLOBALS['CONFIG']['www-root'] . $_SERVER['REQUEST_URI'];
  if (strpos($url, "?") !== false) {
    $url =  explode("?", $url)[0];
  }
  $GLOBALS['DEBUG'][] = "getURL :: (URL): " . $url;
  // Hämtar GET variabler här
  if (isset($_GET) && !$clear) {
    $arrGet = $_GET;
  } else {
    $arrGet = array();
  }


  //      print "<pre>";
  //      print_r($arrGet);
  //      print_r($arrvalues);
  //      print "</pre>";

  $arrCombined = array_merge($arrGet, $arrvalues);
  $GLOBALS['DEBUG'][] = http_build_query($arrGet);
  $GLOBALS['DEBUG'][] = http_build_query($arrvalues);
  // Buggfix 20230531: fixar så att det blir tomt utan ? om inga fält finns
  if (count($arrCombined) > 0) {
    $url .= "?" . http_build_query($arrCombined);
  }


  // print "<pre>";
  // print_r($arrGet);
  // print_r($arrCombined);
  // print "</pre>";




  //$url = $GLOBALS['CONFIG']['www-root'] .$_SERVER['REQUEST_URI'];


  //$GLOBALS['DEBUG'][] = "getURL :: " . $url;

  //if ($clear==true) {
  //    list($url)=  explode("?", $url);
  //}
  // Lägg till nya variabler
  //$questionmark=true;
  //if (strpos($url, "?")===false) {
  //    $questionmark=false;
  //}
  //if (count($arrvalues)>=1) {
  //    if (!$questionmark) {
  //        $url.="?";
  //    } else {
  //        $url.="&";
  //    }
  //    foreach ($arrvalues as $key => $value) {
  //        $url.= $key."=".$value."&";
  //    }
  //    $url = substr($url, 0, -1);
  //}
  $GLOBALS['DEBUG'][] = "getURL :: (URL RETURN): " . $url;
  return $url;
}


/**
 * Hämtar url med hjälp av ID
 *
 * SKA FLYTTAS TILL ROUTE::
 * 
 * @param [type] $id
 * @param array $arrvalues
 * @return void
 */
function getURLbyID($id, $arrvalues = array())
{
  $db = db::getInstance();
  $page = $db->o_get_row("page", "SELECT * FROM [table] WHERE id=" . $id . " AND deleted=0");
  //print $page['src'];
  $url = $GLOBALS['CONFIG']['www-root'] . $page['src'];
  //print $id;
  //?action=edit&id=6668&datum=2018-06-06
  // Lägg till nya variabler
  $questionmark = true;
  if (strpos($url, "?") === false) {
    $questionmark = false;
  }
  if (count($arrvalues) >= 1) {
    if (!$questionmark) {
      $url .= "?";
    } else {
      $url .= "&";
    }
    foreach ($arrvalues as $key => $value) {
      $url .= $key . "=" . $value . "&";
    }
    $url = substr($url, 0, -1);
  }
  return $url;
}

/**
 * Get bus from vehicles
 * 
 * MOVE TO BUS??
 * 
 * @param integer $id The id of bus
 * @return string The name of the bus
 */
function getBus($id)
{
  // Hur buffra ifall bussar redan finns

  $db = db::getInstance();
  $bus = $db->o_get_row("vehicle", "SELECT * FROM [table] WHERE id=" . $id . " AND deleted=0");
  return $bus['b_name'];
}

/**
 * Changes the case on keys to lower
 * @param array $arr The array to modify
 * @return array The array with lower/upper case keys
 */
function array_change_key_case_recursive($arr)
{
  return array_map(function ($item) {
    if (is_array($item))
      $item = array_change_key_case_recursive($item);
    return $item;
  }, array_change_key_case($arr));
}

/**
 * Function for array_merge_own
 * @param array
 * @param array
 * @return array
 */
function array_merge_own($array1, $array2)
{
  //array1 blir överskriven av array 2
  if (isset($array2)) {
    foreach (array_keys($array2) as $key) {
      if (isset($array2[$key]) && is_array($array2[$key])) {
        // Kolla så att array1 är satt, BUGGFIX 20220503
        if (isset($array1[$key])) {
          $array1[$key] = array_merge_own($array1[$key], $array2[$key]);
        } else {
          $array1[$key] = $array2[$key];
        }
      } else {
        $array1[$key] = $array2[$key];
      }
    }
  }
  return $array1;
}

/**
 * Creates the icon img url and send it back
 * @param  [type] $strName               [description]
 * @return [type]          [description]
 */
function getIconSVG($strName)
{
  // Check if file EXIST????? OR???
  //if(file_exist(ROOTPATH."resources/icons/" . $strName . ".svg")) print "X";
  if (!is_file(ROOTPATH . "resources/icons/" . $strName . ".svg")) {
    return $GLOBALS['CONFIG']['www-root'] . "/resources/icons/" . $strName . ".png";
  }
  return $GLOBALS['CONFIG']['www-root'] . "/resources/icons/" . $strName . ".svg";
}


/**
 * Creates the icon img url and send it back
 *
 * @param string $strName Filename of icon w/o file prefix
 * @return string Icon url
 */
function getIcon($strName)
{
  return $GLOBALS['CONFIG']['www-root'] . "/resources/icons/" . $strName . ".png";
}

/**
 * Creates the icon url with full IMG tag
 *
 * @param string $strName Filename of icon w/o file prefix
 * @param string $strWidth If specified then manually sets width
 * @param string $strHeight If specified then manually sets height
 * @return string Icon img tag
 */
function getIconFull($strName, $strWidth = '', $strHeight = '', $strDescription = '', $strClass = '', $arrExtra = '', $size = '')
{
  //print $strClass;
  $strOptions = "";
  if ($strWidth != "") {
    $strOptions .= " width=\"" . $strWidth . "\"";
  }
  if ($strHeight != "") {
    $strOptions .= " height=\"" . $strHeight . "\"";
  }

  if ($size != "") {
    $strOptions .= " width=\"" . $size . "\" height=\"" . $size . "\"";
  }

  if ($strDescription != "") {
    $strOptions .= " title=\"" . $strDescription . "\"";
  }
  if (isset($arrExtra) && is_array($arrExtra)) {
    foreach ($arrExtra as $key => $value) {
      $strOptions .= " {$key}=\"{$value}\"";
    }
  }
  //if ($strClass!="") { $strOptions.= " class=\"" . $strClass . "\""; }
  return "<img src=\"" . getIcon($strName) . "\" {$strOptions} class=\"geticons " . $strClass . "\">";
}

// function getIconFull($strName,$strWidth='',$strHeight='',$strDescription='',$strClass='',$arrExtra='') {
//     //print $strClass;
//     $strOptions="";
//     if ($strWidth!="") { $strOptions.= " width=\"" . $strWidth . "\""; }
//     if ($strHeight!="") { $strOptions.= " height=\"" . $strHeight . "\""; }
//     if ($strDescription!="") { $strOptions.= " title=\"" . $strDescription . "\""; }
//     if (isset($arrExtra) && is_array($arrExtra)) {
//       foreach ($arrExtra as $key => $value) {
//         $strOptions.= " {$key}=\"{$value}\"";
//       }
//     }
//     //if ($strClass!="") { $strOptions.= " class=\"" . $strClass . "\""; }
//     return "<img src=\"" . getIcon($strName) . "\" {$strOptions} class=\"geticons " . $strClass . "\">";
// }

/**
 * Creates the icon url with full IMG tag for SVG
 * @param  [type] $strName                      [description]
 * @param  string $strWidth                     [description]
 * @param  string $strHeight                    [description]
 * @param  string $strDescription               [description]
 * @param  string $strClass                     [description]
 * @param  string $arrExtra                     [description]
 * @return [type]                 [description]
 */
function getIconFullSVG($strName, $strWidth = '', $strHeight = '', $strDescription = '', $strClass = '', $arrExtra = '',$strAlt = '')
{
  //print $strClass;
  $strOptions = "";
  if ($strWidth != "") {
    $strOptions .= " width=\"" . $strWidth . "\"";
  }
  if ($strHeight != "") {
    $strOptions .= " height=\"" . $strHeight . "\"";
  }
  if ($strDescription != "") {
    $strOptions .= " title=\"" . $strDescription . "\"";
  }
  if ($strAlt != "") {
    $strOptions .= " alt=\"" . $strAlt . "\"";
  }
  if (isset($arrExtra) && is_array($arrExtra)) {
    foreach ($arrExtra as $key => $value) {
      $strOptions .= " {$key}=\"{$value}\"";
    }
  }
  //if ($strClass!="") { $strOptions.= " class=\"" . $strClass . "\""; }
  return "<img src=\"" . getIconSVG($strName) . "\" {$strOptions} class=\"geticons " . $strClass . "\">";
}


function makeHelp($strName = 'allt')
{
  //        print "<a href=\"#\" data-featherlight=\"" . $GLOBALS['CONFIG']['www-root']."/system/ajax/showHelp.php?title={$strName} .selector\" data-featherlight-type=\"ajax\" class=\"helpbutton\" title=\"Hjälpkapitel om \">" . getIconFull('help32px') . "</a>\n";
  print "<a href=\"" . $GLOBALS['CONFIG']['www-root'] . "/system/ajax/showHelp.php?title={$strName}\" class=\"helpbutton various fancybox.ajax\" title=\"Hjälp\">" . getIconFull('help32px') . "</a>\n";
}


/**
 * Salts the incomin string
 * 
 * SKA FLYTTAS TILL AUTH::
 *
 * @param string $strValue The string to Hash
 * @param integer $intLength Length of string. Full length as default.
 * @return string The hashed string
 */
function salt($strValue, $intLength = 0)
{
  $hash = hash('sha512', $strValue . $GLOBALS['CONFIG']['salt']);
  if ($intLength > 0) {
    $hash = substr($hash, 0, $intLength);
  }
  return $hash;
}

/**
 * Get the date for today
 * @return date/string The date of today
 */
function getTodayDate()
{
  return date('Y-m-d');
}

/**
 * Get the date for today
 * @return date/string The date of today
 */
function getTodayDateTime()
{
  return date('Y-m-d H:i:s');
}

/**
 * Get the date for today
 * @return date/string The date of today
 */
function getTodayDateTimeT()
{
  return str_replace(' ', 'T', date('Y-m-d H:') . '00');
}

/**
 * Get the monday of that dates weeks
 * @return date/string The date of the monday that week
 */
function getThisWeeksMonday($date)
{
  $arrDate = explode("-", $date);
  //             print "<pre>";
  //             print_r($arrDate);
  //             print "</pre>";
  $weekday = date('N', mktime(1, 1, 1, $arrDate[1], $arrDate[2], $arrDate[0]));
  $mdate = date('Y-m-d', mktime(1, 1, 1, $arrDate[1], $arrDate[2] - ($weekday - 1), $arrDate[0]));
  return $mdate;
}

function getDateFromToday($intDays)
{
  return date('Y-m-d', mktime(1, 1, 1, date("m"), date("d") + $intDays, date("Y")));
}


function getFirst($arrSource)
{
  print current(array_keys($arrSource));
}

function getTime()
{
  $arrPeriod = array('00', '15', '30', '45');
  for ($hour = 0; $hour < 24; $hour++) {
    foreach ($arrPeriod as $minutes) {
      if ($hour < 10) {
        $tid = "0" . $hour . ":" . $minutes;
      } else {
        $tid = $hour . ":" . $minutes;
      }
      $arrTime[$tid . ":00"] = $tid;
    }
  }

  return (array) $arrTime;
}

/**
 * Get template files from dirs...
 * 
 * SKA FLYTTAS TILL TEMPLATE::
 *
 * @param string $prefix
 * @return void
 */
function getTemplates($prefix = '')
{
  $lib = str_replace("plugins/", "templates/", PLUGINPATH);
  $files = scan($lib);
  $newArray = array(0 => ' -- INGEN VALD --');
  foreach ($files as $value) {
    $newArray[$lib . $value] = $value;
  }
  if ($prefix <> "") {
    foreach ($newArray as $value) {
      if (!str_starts_with($value, $prefix)) unset($newArray[$value]);
    }
  }
  /*print "<pre>";
      print_r($newArray);
      print "</pre>";*/
  return (array) $newArray;
}

if (!function_exists('boolval')) {
  /**
   * Get the boolean value of a variable
   *
   * @param mixed The scalar value being converted to a boolean.
   * @return boolean The boolean value of var.
   */
  function boolval($var)
  {
    return !!$var;
  }
}


/**
 * Send an email
 * 
 * SKA FLYTTAS TILL MAIL::
 * 
 * 
 * @param string $adress An emailadress to send to
 * @param string $title The title on the mail
 * @param string $msg The message in the mail
 * @return boolean 1=ok | 0=fail
 */
function sendMail($adress, $title, $message)
{
  // TODO: Byta till class här istället
  // Open log
  $fh = fopen(SYSPATH . "logs/email.log", 'a');
  $log = date("Y-m-d H:i:s") . ": Email sent to: {$adress}\n";
  $log .= date("Y-m-d H:i:s") . ": with title: {$title}\n";
  $log .= date("Y-m-d H:i:s") . ": and message: {$message}\n";

  // Mail fungerar inte längre. Vi behöver implementera nästa version =php mailer
  if (mail($adress, $title, $message)) {
    $log .= date("Y-m-d H:i:s") . ": Returned: TRUE\n\n";
    fwrite($fh, $log);
    fclose($fh);
    return true;
  } else {
    $log .= date("Y-m-d H:i:s") . ": Returned: FALSE\n\n";
    fwrite($fh, $log);
    fclose($fh);
    return false;
  }

  //$mail = new PHPMailer(true);
  //setMsg('w', '2022-12-29: Det går inte automatiskt att skicka uppgifterna till nya användare för stunden. Vi felsöker problemet.');
  //print "TEST!";
  /*
    #$mail = new PHPMailer(true);
    add2cronlog(" Sending email to {$adress}\n\t with title: {$title}\n\t and message: {$msg}\n");
    try {
        //Server settings
        //$mail->SMTPDebug = SMTP::DEBUG_SERVER;                      // Enable verbose debug output
        $mail->isSMTP();                                            // Send using SMTP
        $mail->Host       = $GLOBALS['CONFIG']['SMTP-server'];                    // Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
        $mail->Username   = $GLOBALS['CONFIG']['SMTP-username'];                     // SMTP username
        $mail->Password   = $GLOBALS['CONFIG']['SMTP-password'];                               // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
        $mail->Port       = $GLOBALS['CONFIG']['SMTP-port'];                                    // TCP port to connect to

        //Recipients
        $mail->setFrom($GLOBALS['CONFIG']['SMTP-from'], $GLOBALS['CONFIG']['SMTP-from-name']);
        $mail->addCC('martin@hallensbuss.se', 'Martin Tonek');     // Add a recipient
        //$mail->addAddress($adress);

        // Attachments
        //$mail->addAttachment($this->getfileWithFullPath());         // Add attachments
        //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
        // Content
        $mail->CharSet = 'utf-8';
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = $title;
        $mail->Body    = $msg //'Här kommer körordern för resan<br> <b>'. $this->data['resan'] .'</b><br> med avresa den <b>' . $this->data['startdate'] . '</b><br><br>Ha en trevlig resa!<br>H-book Teamet';
        //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
        $mail->send();
        add2cronlog('Message has been sent');
        return true;
    } catch (Exception $e) {
        add2cronlog("Message could not be sent. Mailer Error: {$mail->ErrorInfo} , Adress: {$adress}, Title: {$title}, Msg: {$msg}");
        return false;
    }*/
}

/**
 * Set error messages here!
 *
 * SKA FLYTTAS TILL ALERT:: ??
 * 
 * @param string $strType Type to use (s)uccess, (e)rror, (w)arning, (n)otice
 * @param string $strMsg The message to show in alertbox
 */
function setMsg($strType, $strMsg)
{
  $arrTypes = array('s' => 'success', 'e' => 'error', 'w' => 'warning', 'n' => 'notice');
  $_SESSION['message'][$arrTypes[$strType]][] = $strMsg;
}

/**
 * Undocumented function
 * 
 * SKA FLYTTAS TILL ALERT:: ??
 * 
 * @return void
 */
function showMsg()
{
  $arrTypes = array('error', 'warning', 'success', 'notice');
  if (isset($_SESSION['message'])) {
    foreach ($arrTypes as $strType) {
      if (is_array($_SESSION['message'][$strType])) {
        foreach ($_SESSION['message'][$strType] as $strMsg) {
          print "<div class=\"alert-box {$strType}\">{$strMsg}</div>";
        }
      }
    }
    unset($_SESSION['message']);
  }
}


/**
 * Remove ??
 *
 * @return void
 */
function workingHere()
{
  setMsg('w', "Arbetar med denna modul just nu. Förmodligen fungerar den inte korrekt för stunden");
}

/**
 * Shows error if superuser
 *
 * @return void
 */
function showErrors()
{
  if (!isSuperUser()) return;
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);
}

/**
 * Shows debug info if superuser
 *
 * @return void
 */
function debug()
{
  if (!isSuperUser()) return;
  print "<pre>";
  print_r($_GET);
  print_r($_POST);
  //print_r($_SESSION);
  //print_r($_COOKIE);
  print "</pre>";
}

/**
 * Shows arrays nicer if superuser
 *
 * @param [type] $str
 * @return void
 */
function pre($str)
{
  if (!isSuperUser()) return;
  print "<pre>";
  if (is_array($str)) {
    print_r($str);
  } else {
    print($str);
  }
  print "</pre>";
}

function compareView($arrViews = array())
{
  if (is_array($arrViews) && count($arrViews) > 0) {
    $tot = count($arrViews);
    print $tot;
    print "<div style='display:grid;grid-template-columns: repeat({$tot}, 1fr);column-gap: 1rem; border:1px solid red;'>";
    foreach ($arrViews as $view) {
      print "<pre>";
      print_r($view);
      print "</pre>";
    }
    print "</div>";
  }
}

/**
 * Is it used?
 */
class debugUtils
{
  public static function callStack($stacktrace)
  {


    //        print "<pre>";
    //        print str_repeat("=", 50) ."\n";
    //        $i = 1;
    //        foreach($stacktrace as $node) {
    //            print "$i. ".basename($node['file']) .":" .$node['function'] ."(" .$node['line'].")\n";
    //            $i++;
    //        }
    //        print "</pre>";

    $output = str_repeat("=", 50) . "\n";
    $i = 1;
    foreach ($stacktrace as $node) {
      $output .=  "$i. " . basename($node['file']) . ":" . $node['function'] . "(" . $node['line'] . ")\n";
      $i++;
    }


    $GLOBALS['DEBUG'][] = $output;
  }
}


/**
 * Undocumented function
 *
 * MOVE TO BUS ??
 * 
 * @return void
 */
function getBookingDate()
{
  // Kolla först om GET är satt/ Sedan om POST är satt och sist dagens datum
  if (!empty($_POST['db_startdate'])) {
    return $_POST['db_startdate'];
  } elseif (!empty($_GET['datum'])) {
    return $_GET['datum'];
  } else {
    return date("Y-m-d");
  }
}


    /*
     * NEDAN FÖLJER FÖRETAGS SPECIFIKA INSTÄLLNINGAR
     */

function getCompanyID()
{
  //Hämta companyid här!
  $auth = auth::getInstance();
  $a = $auth->getUserAndCompany();
  // Denna if sats med underlänkar är en buggfix för form o ajax. 220503
  if (!is_array($a)) {
    $db = db::getInstance();
    if ($db->forcePrefixID != "") {
      return $db->forcePrefixID;
    } else {
      return '0';
    }
  }
  return $a['companyid'];
}

function _s($replacewidth, $haystack)
{
  $find = array();
  if (!is_array($replacewidth)) return "ERROR!!! NEED ARRAY";
  for ($i = 1; $i <= count($replacewidth); $i++) {
    $find[] = '%' . $i;
  }
  return str_replace($find, $replacewidth, $haystack);
}

/**
 * Undocumented function
 *
 * MOVE TO AUTH::
 * 
 * @return void
 */
function getCompanySettings()
{

  //Hämta companyid här!
  $auth = auth::getInstance();
  $a = $auth->getUserAndCompany();
  // Denna if sats med underlänkar är en buggfix för form o ajax. 220503
  if (!is_array($a)) {
    $db = db::getInstance();
    if ($db->forcePrefixID != "") {
      return $db->forcePrefixID;
    } else {
      return '0';
    }
  }
  return $a['company_settings'];
}

/**
 * Undocumented function
 *
 * MOVE TO AUTH::
 * 
 * @return void
 */
function getUserID()
{
  //Hämta companyid här!
  $auth = auth::getInstance();
  $a = $auth->getUserAndCompany();
  return $a['id'];
}

/**
 * Undocumented function
 *
 * MOVE TO AUTH::
 * 
 * @return void
 */
function getUserName()
{
  //Hämta namn här!
  $auth = auth::getInstance();
  $a = $auth->getUserAndCompany();
  return $a['name'];
}

/**
 * Undocumented function
 *
 * MOVE TO AUTH::
 * 
 * @return void
 */
function isSuperUser()
{
  //Hämta companyid här!
  $auth = auth::getInstance();
  $a = $auth->getUserAndCompany();
  if (isset($a['superuser'])) {
    $res = $a['superuser'];
  }
  $res= $res ?? 0;
  // var_dump($res);
  return (bool) $res; //a['superuser'] ?? 0
}

/**
 * Undocumented function
 *
 * MOVE TO AUTH::
 * 
 * @return void
 */
function isAdmin()
{
  return false;
  //Hämta companyid här!
  // $auth = auth::getInstance();
  // $a = $auth->getUserAndCompany();
  // return (bool) $a['admin'];
}

/**
 * Undocumented function
 *
 * MOVE TO USER::
 * 
 * @return void
 */
function getUserFromID($id)
{
  if ($id == 0) {
    return 'Ej angivet';
  }
  $db = db::getInstance();
  $arrUser = $db->o_get_row('user', 'SELECT id, namn FROM [table] WHERE deleted=0 AND id=' . $id);
  $name = $arrUser['namn'];
  //$db->o_insert('booking_log',array('data' => serialize($arrUser)));
  if ($name == '') {
    return 'Ej angivet';
  }
  return $name;
}

function getCleanDate($date)
{
  if ($date == 0) {
    return 'Ej angivet';
  }
  return date("Y-m-d H:i", $date);
}


function getCompanyFileName()
{
  //Hämta companyid här!
  $db = db::getInstance();
  $arrCompany = $db->o_get_row('company', 'SELECT * FROM [table] WHERE deleted=0 AND companyid=' . getCompanyID());
  $strCompanyName = $arrCompany['namn'];
  $strCompanyName = strtolower($strCompanyName);
  $strCompanyName = str_replace(array('å', 'ä', 'ö'), array('a', 'a', 'o'), $strCompanyName);
  $strCompanyName = str_replace(" ", "-", $strCompanyName);
  return $strCompanyName;
}

/**
 * Undocumented function
 *
 * MOVE TO DB::
 * 
 * @return void
 */
function getdbPrefixes()
{
  // Flyttad till db klassen
  $db = db::getInstance();
  $arrReturn = array();
  $arrPrefix = $db->o_get_all("company", "SELECT id, dbprefix FROM [table] WHERE deleted=0");
  $arrReturn[0] = $GLOBALS['CONFIG']['dbPrefix'];
  foreach ($arrPrefix as $key => $value) {
    $arrReturn[$value['id']] = $value['dbprefix'];
  }
  $arrReturn[] = "hbook_";

  /*        print "<pre>";
        print __FUNCTION__."\n";
        print_r($arrReturn);
        print "</pre>";
*/
  return (array) $arrReturn;
}

/**
 * Undocumented function
 *
 * MOVE TO AUTH::
 * 
 * @return void
 */
function validatePwd($strPwd)
{

  return 1;
}

function fix_number($foo)
{
  return number_format((float)$foo, 2, ',', ' ');
}

function getTaxRates()
{
  return (array) array('0' => '0%', '6' => '6%', '12' => '12%', '25' => '25%');
}


/**
 * MOVE TO TEMPLATE::
 * 
 * use  addJS(__CLASS__, true); to add file from system/modules/[classname]/html/[classname].js
 *
 * @param string $strFilename
 * @param bool   $module
 * @return void
 */
function addJS($strFilename, $module = false)
{
  if ($module) {
    $strFilename = "system/modules/{$strFilename}/html/{$strFilename}.js";
  }
  if (is_file(ROOTPATH . $strFilename)) {
    $GLOBALS['tpl']['js'][] = $strFilename;
  }
}

/**
 * A class wrapper for addCSS
 * - require that the file live in the html directory and is named as {class}.css
 * ex. 'system/modules/<b>messengerlite_notification</b>/html/<b>messengerlite_notification</b>.css'
 * 
 * - use as <b>addCSSM( __ CLASS __ );</b>
 */
function addCSSM($class) {
  addCSS("system/modules/{$class}/html/{$class}.css");
}

/**
 * A class wrapper for addJS
 * - require that the file live in the html directory and is named as {class}.js
 * ex. 'system/modules/<b>messengerlite_notification</b>/html/<b>messengerlite_notification</b>.js'
 * 
 * - use as <b>addJSM( __CLASS__ );</b>
 */
function addJSM($class) {
  addJS("system/modules/{$class}/html/{$class}.js");
}
/**
 * MOVE TO TEMPLATE::
 * 
 * use  addCSS(__CLASS__, true); to add file from system/modules/[classname]/html/[classname].css
 *
 * @param string $strFilename
 * @param bool   $module 
 * @return void
 */
function addCSS($strFilename, $module = false)
{
  if ($module) {
    $strFilename = "system/modules/{$strFilename}/html/{$strFilename}.css";
  }
  if (is_file(ROOTPATH . $strFilename)) {
    $GLOBALS['tpl']['css'][] = $strFilename;
  }
}


/**
 * VERKAR INTE ANVÄNDAS
 *
 * @return void
 */
function getURL2Module()
{
  return ROOTPATH;
}


/**
 * This should get names from general lang file
 */
if (!function_exists('getMonthName')) {
  function getMonthName($intMonth)
  {
    switch ($intMonth) {
      case 1:
        return "Januari";
        break;
      case 2:
        return "Februari";
        break;
      case 3:
        return "Mars";
        break;
      case 4:
        return "April";
        break;
      case 5:
        return "Maj";
        break;
      case 6:
        return "Juni";
        break;
      case 7:
        return "Juli";
        break;
      case 8:
        return "Augusti";
        break;
      case 9:
        return "September";
        break;
      case 10:
        return "Oktober";
        break;
      case 11:
        return "November";
        break;
      case 12:
        return "December";
        break;
      default:
        return "FEL FEL FEL FEL FEL";
        break;
    }
  }
} else {
  print "function_exists: getMonthName<br>";
}


if (!function_exists('add2cronlog')) {
  function add2cronlog($str, $class = '', $function = '')
  {
    $xtra = date("Y-m-d H:i:s") . ": ";
    $logs = debug_backtrace();
    if (count($logs) > 0) {
      $obj = reset($logs);
      foreach (array('file', 'line', 'class', 'function') as $fld) {
        if (isset($obj[$fld])) {
          $xtra .= "[" . strtoupper($fld) . ":" . $obj[$fld] . "] ";
        }
      }
    }
    // print "<pre>";
    // print_r($logs);
    // print "</pre>";

    $fh = fopen(SYSPATH . "logs/cron.log", 'a');
    if ($function <> "") $str = "[{$function}] " . $str;
    if ($class <> "") $str = "[{$class}] " . $str;
    fwrite($fh, $xtra . " " . $str . "\n");
    fclose($fh);
  }
}


function errordb($msg, $function)
{
  error_log("[{$function}] $msg", 3, SYSPATH . "/logs/db.log");
}



function generateCallTrace()
{
  $e = new Exception();
  $trace = explode("\n", $e->getTraceAsString());
  // reverse array to make steps line up chronologically
  $trace = array_reverse($trace);
  array_shift($trace); // remove {main}
  array_pop($trace); // remove call to this method
  $length = count($trace);
  $result = array();

  for ($i = 0; $i < $length; $i++) {
    $result[] = ($i + 1)  . ')' . substr($trace[$i], strpos($trace[$i], ' ')); // replace '#someNum' with '$i)', set the right ordering
  }

  return "\t" . implode("\n\t", $result);
}
/**
 * Cleans the tablename and return a array with all of the possible names
 * @param  [type] $tblName [description]
 * @return [type]          [description]
 */
/*function fixTableNames($tblName) {

  return (array) array($tblName);
}*/


/**
 * Creates a delete button
 * @param array $arr Require an array with value action and id set.
 */
function BtnDelete($arr = array())
{
  if (count($arr) == 0 || !isset($arr['id']) || !isset($arr['action'])) {
    setMsg('e', "ERROR MAKING BUTTON ON FUNCTION <b>" . __FUNCTION__ . "</b> @ LINE: <b>" . __LINE__ . "</b><br />Missing arguments!");
    return "";
  }
  if (!isset($arr['msg'])) {
    $msg = "return confirm('Vill du verkligen radera denna post med ID {$arr['id']}?')";
    // $msg = "return confirmDelete(" . $arr['id'] . ");";
  } else {
    $msg = "return confirmDeleteMSG('" . $arr['msg'] . "');";
  }
  print "<a class=\"button button-delete float_right margin_right\" href=\"" . getURL($arr) . "\" onclick=\"" . $msg . "\" >" . $GLOBALS['LANG']['GENERAL']['delete'] . "</a>&nbsp;";
  return "";
}

/**
 * ANVÄNDS DENNA??
 */
function deleteBtn($arr = array(), $clear = false) {
  if (count($arr) == 0 || !isset($arr['id']) || !isset($arr['action'])) {
    setMsg('e', "ERROR MAKING BUTTON ON FUNCTION <b>" . __FUNCTION__ . "</b> @ LINE: <b>" . __LINE__ . "</b><br />Missing arguments!");
    return "";
  }
  if (!isset($arr['msg'])) {
    $msg = "return confirmDelete(" . $arr['id'] . ");";
  } else {
    $msg = "return confirmDeleteMSG('" . $arr['msg'] . "');";
  }
  return "<a href='" . getURL($arr) . "' onclick=\"" . $msg . "\" >".getIconFullSVG('delete',16,16,strAlt:$GLOBALS['LANG']['GENERAL']['delete'])."</a>";
}




/**
 * Skapar knappar ( TEST FUNKTION ) EJ KLAR!!!
 * @param array $arr  [description]
 *
 * createBtn(array('title' => 'Lägg till en slide', 'class' => 'bgblue','url'=>array('subaction' => 'add')));
 *
 */
function createBtn($arr = array(), $clear = false)
{
  // Vad krävs och vad är frivilligt
  // KRÄVS : title
  // TILLVAL : class, action, url (bool)

  /*  if (count($arr) == 0 || !isset($arr['id']) || !isset($arr['action']) ) {
      setMsg('e',"ERROR MAKING BUTTON ON FUNCTION <b>" . __FUNCTION__ . "</b> @ LINE: <b>" . __LINE__."</b><br />Missing arguments!");
      return "";
    }*/
  /*  if ( !isset($arr['msg']) ) {
        $msg = "return confirmDelete(" . $arr['id'] . ");";
    } else {
        $msg = "return confirmDeleteMSG('" . $arr['msg'] . "');";
    }*/

  $class = array_key_exists('class', $arr) ? $arr['class'] : '';

  //$class = array_key_exists('class', $arr) ? $arr['class'] : '';


  //print "#" . $class . "#";


  /*print "<pre>";
  print_r($arr);
  print "</pre>";*/

  if (isset($arr['url'])) {
    $url = "href=\"" . getURL($arr['url'], $clear) . "\""; // href=\"" . $url . "\"
  } else {
    $url = "href=\"#\"";
  }
  print "<a class=\"button {$class} \" $url >" . $arr['title'] . "</a>&nbsp;";
  return "";
}

/**
 * Add a cancel action button
 * @param [type] $strOutput  The output to print including formdata, often retrived when using $form->MakeWithSettings($this->dbFormSettings,true);
 */
function addCancelButton2Form($strOutput)
{
  $strOutput = str_replace("</form>", "", $strOutput);
  $strOutput .= "<a href=\"?action=nothing\" class=\"button button-delete smallbutton\" style=\"float: right;\" >" . $GLOBALS['LANG']['GENERAL']['cancel'] . "</a>\n</form>\n";
  print $strOutput;
}

function returnBtn($return = false)
{
  $output = "<a class=\"button float_right returnBtn action__cancel\" href=\"" . getURL(array(), true) . "\"> &lt;&lt; " . $GLOBALS['LANG']['GENERAL']['return'] . "</a>";
  if ($return) {
    return $output;
  } else {
    print $output;
  }
}

function getNum($value)
{
  $value = intval($value);
  return $value;
}

/*use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
print PLUGINPATH;
require_once PLUGINPATH . 'PHPMailer/src/Exception.php';
require_once PLUGINPATH . 'PHPMailer/src/PHPMailer.php';
require_once PLUGINPATH . 'PHPMailer/src/SMTP.php';*/

// Fixar en mail kompatibel ersättare
if (!function_exists('mail')) {
  function mail($adress, $title, $message, $headers = '')
  {
    if (!is_file(PLUGINPATH . 'PHPMailer/mailInit.php')) {
      setMsg('e', 'No mail set for now!');
      return true;
    }

    require_once PLUGINPATH . 'PHPMailer/mailInit.php';

    require_once PLUGINPATH . 'PHPMailer/src/Exception.php';
    require_once PLUGINPATH . 'PHPMailer/src/PHPMailer.php';
    require_once PLUGINPATH . 'PHPMailer/src/SMTP.php';

    try {
      $mail = new PHPMailer\PHPMailer\PHPMailer(true);
      $mail->isSMTP();                                            // Send using SMTP
      $mail->Host       = $GLOBALS['CONFIG']['SMTP-server'];                    // Set the SMTP server to send through
      $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
      $mail->Username   = $GLOBALS['CONFIG']['SMTP-username'];                     // SMTP username
      $mail->Password   = $GLOBALS['CONFIG']['SMTP-password'];                               // SMTP password
      $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
      $mail->Port       = $GLOBALS['CONFIG']['SMTP-port'];                                    // TCP port to connect to

      $mail->setFrom($GLOBALS['CONFIG']['SMTP-from'], $GLOBALS['CONFIG']['SMTP-from-name']);
      $mail->addBCC('admin@hallens.se', 'Martin Tonek');     // Add a recipient
      $mail->addAddress($adress);
      // Content
      $mail->CharSet = 'utf-8';
      $mail->isHTML(true);
      $mail->Subject = $title;
      $mail->Body    = $message;
      $mail->send();
      setMsg("s", "Epost har skickats!");
      return true;
    } catch (PHPMailer\PHPMailer\Exception $e) {
      //    print "FEL!";
      setMsg("e", "Epost har inte skickats!<br />" . $e->errorMessage());
      return false;
    }
  }
}

function chkNumNoZ($data)
{
  if (intval($data) > 0) {
    return intval($data);
  }
  return NULL;
}


/**
 * Retrieve metadata from a file.
 *
 * Searches for metadata in the first 8kiB of a file, such as a plugin or theme.
 * Each piece of metadata must be on its own line. Fields can not span multiple
 * lines, the value will get cut at the end of the first line.
 *
 * If the file data is not within that first 8kiB, then the author should correct
 * their plugin file and move the data headers to the top.
 *
 * FROM: https://stackoverflow.com/questions/24099741/reading-commented-details-in-a-file-like-wordpress-theme-engine
 *
 * @param string $file Path to the file
 * @param array $default_headers List of headers, in the format array('HeaderKey' => 'Header Name')
 */

function get_file_data($file, $default_headers = array(
  'Name'          => 'Name',
  'MName'         => 'Module Name',
  'Description'   => 'Description',
  'Hidden Fields' => 'Hidden Fields',
  'Author'        => 'Author',
  'Version'       => 'Version',
  'Status'        => 'Status',
  'Type'          => 'Type',
))
{
  /*ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);*/
  if (!is_file($file)) return array();
  $fp = fopen($file, 'r');
  $file_data = fread($fp, 8192);
  fclose($fp);
  $file_data = str_replace("\r", "\n", $file_data);
  $all_headers = $default_headers;

  foreach ($all_headers as $field => $regex) {
    if (
      preg_match('/^[ \t\/*#@]*' . preg_quote($regex, '/') . ':(.*)$/mi', $file_data, $match)
      && $match[1]
    )
      $all_headers[$field] = trim(preg_replace("/\s*(?:\*\/|\?>).*/", '', $match[1]));
    else
      $all_headers[$field] = '';
  }

  return $all_headers;
}

/**
 *
 * Vad bör ingå i toolbox?
 * Vilka funktioner ska finnas?
 * Vilka indata ska behövas?
 * Possible to add custom buttons.
 * Adds in same order as added in array?
 *
 * Vilka verkar alltid finnas?
 *
 *  array(
 *  'id' => 1, // Common id if nothing else is said
 *  'edit' => true,
 *  'delete' => false
 *  'custom1' => array(
 *    'icon' => 'edit',
 *    'action' => 'edit',
 *    'desc' => 'Let u edit',
 *    'class' => ''
 *   ),
 *  )
 *
 *
 *
 * edit, delete
 *
 * @param  [type] $id                       [description]
 * @param  array  $arrButtons               [description]
 * @return [type]             [description]
 */
function toolbox($id, $arrButtons = array())
{
  $output = "<div class=\"toolbox\">\n";
  foreach ($arrButtons as $key => $value) {
    $xtra = "";
    // parameters
    if (isset($value['param']) && is_array($value['param'])) {
        $strParam = "";
        foreach ($value['param'] as $paramKey => $paramValue) {
            $strParam .= "&amp;" . $paramKey . "=" . $paramValue;
        }
    } else {
        $strParam = "";
    }
    if($key == "delete") {
      $xtra = "onclick=\"return confirmDelete({$id});\"";
    }

    $value['action'] = str_replace('action=', '', $value['action']);
    extract($value);
    $output .= "\t<a href=\"?action={$action}{$strParam}&amp;id={$id}\" title=\"" . $value['label'] . "\" {$xtra}>" . getIconFullSVG($icon, 16, 16) . "</a>\n";
  }
  // var_dump(isset($arrButtons['edit']));
  //$output .= getURL(array(),true);
  //$output .= "\t<a href=\"https://www.mydomain.se/settings/screen?action=screen&amp;id=1&amp;subaction=show&amp;sid=8\"><img src=\"https://www.mydomain.se/resources/icons/invisible.png\" width=\"16\" height=\"16\" title=\"Är ej aktiv\" class=\"geticons \"></a>\n";
  if (!isset($arrButtons['edit'])) {
  $output .= "\t<a href=\"?action=edit&amp;id={$id}\" title=\"" . $GLOBALS['LANG']['GENERAL']['edit'] . "\">" . getIconFullSVG('edit', 16, 16) . "</a>\n";
  }
  if (!isset($arrButtons['delete'])) {
  $output .= "\t<a href=\"?action=delete&amp;id={$id}\" title=\"" . $GLOBALS['LANG']['GENERAL']['delete'] . "\" onclick=\"return confirmDelete(8);\">" . getIconFullSVG('delete', 16, 16) . "</a>\n";
  }
  $output .= "\t" . getIconFullSVG('controlpanel', 16, 16) . "\n";
  $output .= "</div>\n";
  return $output;
}

/**
 * Retrive the page structure from site..
 * @return array Page Structure
 */
function getPageStructure()
{
  $db = db::getInstance();
  $dbr = $db->o_get_all('page', "SELECT * FROM [table] WHERE deleted=0 ORDER BY pid, ordning"); //visible=1 AND
  // Build tree
  $data = buildPageStructure($dbr, 0, 0, 4);
  $data = array(0 => 'ROOT') + $data;
  return (array) $data;
}
/**
 * A sub function for getPageStructure
 * @param  array   $arrData               Data from db
 * @param  integer $pid                   Parent ID
 * @param  integer $level                 Which level now
 * @param  integer $depth                 How deep to go
 * @return [type]           [description]
 */
function buildPageStructure($arrData, $pid = 0, $level = 0, $depth = 99)
{
  $arrMenu = array();
  if ($level <= $depth) {
    if (is_array($arrData)) {
      $spaces = str_pad("", $level + 2, "--");
      foreach ($arrData as $key => $value) {
        $class = "";
        if ($value['pid'] == $pid) {
          $arrMenu[$key] = $spaces . $value['label'] . "(" . $value['id'] . ")";
          $children = buildPageStructure($arrData, $key, $level + 1, $depth);
          if (is_array($children) && count($children) > 0) {
            $arrMenu = $arrMenu + $children;
          }
        }
      }
      return (array) $arrMenu;
    }
  }
}

function allDBPrefixes()
{
  return array('hbook_', 'm_', 'mt_');
}

function getCustomForeignkey($str)
{
  // Vem använder denna? SETTINGSFILER gör detwwww
  // Custom companyid here
  if (!isset($_GET['id'])) {
    setMsg("e", "No ID is set. " . __FUNCTION__ . " failed");
    return;
  } else {
    $companyid = intval($_GET['id']);
  }

  // foreignKey
  if (isset($str) || isset($str)) { //  && is_array($str)
    if (isset($str)) {
      $foreignKey = $str;
    } elseif (isset($str)) {
      $foreignKey = $str;
    }
    list($strTable, $strField) = explode(".", $foreignKey);
    // print "Tbl: $strTable    Fld: $strField <br>";
    $db = db::getInstance();
    $GLOBALS['DEBUG'][] = "NU GRUPPER:";
    // 20170307 - BUGG HITTAD!!! companyid används ej på 'page' t.ex.
    // La till OR companyid = 0 för att se om det fungerar!
    // Hur bygga in funktionen med PID?
    // Kolla sort key?
    // foreignKeyisPage
    if (isset($data['foreignKeyisPage'])) {
      $f = new form();
      $arrOptions = array('0' => $GLOBALS['LANG']['FORM']['select_0']);
      $arrAnswer = $db->o_get_all($strTable, "SELECT id, " . $strField . ",pid FROM [table] WHERE deleted=0 AND ( companyid={$companyid} OR companyid=0 ) ORDER BY pid ASC, {$strField} ASC"); //
      //$arrReturn = $arrOptions + $this->OptionsTree($ar
      $arrReturn = $arrOptions + $f->OptionsTree($arrAnswer, 0, 0);
      return (array) $arrReturn;
    } else {
      $arrAnswer = $db->o_get_all($strTable, "SELECT id, " . $strField . " FROM [table] WHERE deleted=0 AND ( companyid={$companyid} OR companyid=0 ) ORDER BY {$strField} ASC"); //
      foreach ($arrAnswer as $key => $value) {
        $arrOptions[$key] = $value[$strField];
      }
    }
    foreach ($arrAnswer as $key => $value) {
      $arrOptions[$key] = $value[$strField];
    }
    return (array) $arrOptions;
  }
}


if (!function_exists('validateDates')) {
  function validateDates($date, $format = 'Y-m-d')
  {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
  }
}

    if(!function_exists('json_validate')) {
      function json_validate($string) {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
      }
    }


/**
 * Lets you get a HTML page with centered view
 *
 * @param integer $start Print the beginning
 * @param integer $end   Print the end
 * @return void
 */
function simpleHTMLBox($start = 0, $end = 0)
{
  if ($start) print "<html>\n\t<head>\n\t\t<style>\n\t\t\tdiv {\n\t\t\t\tdisplay: flex;\n\t\t\t\tjustify-content: center;\n\t\t\t\talign-items: center;\n\t\t\t\tflex-direction: column;\n\t\t\t\theight:100vh;\n\t\t\t}\n\t\t</style>\n\t</head>\n\t<body>\n\t\t<div class=\"flex-container\">\n";

  if ($end) print "\t\t</div>\n\t</body>\n</html>\n<!-- EOF -->";
}

function write_to_log()
{
  print "<pre>";
  print_r(debug_backtrace());
  print "</pre>";
  die();
}



/**
 * Checks an incoming value if its an array and if it includes a subarray lets it get keys from subarray else get keys from array and make it a serial string
 *
 * @param array $array
 * @return void
 */
function getKeysFromArray($array) {
  if (!is_array($array)) return NULL;
  $firstKey = array_key_first($array);
  if (is_array($array[$firstKey])) {
    // Get keys of subarray
    $keys = array_keys($array[$firstKey]);
  } else {
    $keys = array_keys($array);
  }
  $keys = implode(",", $keys);

  return $keys ?? NULL;
}

/**
 * Test of new CSS/JS function
 *
 * @return void
 */
function myCalledFunctionViaException() {
    try {
        throw new Exception();
    } catch (Exception $e) {
        $trace = $e->getTrace();
        // The first element [0] is the location where the exception was thrown (inside myCalledFunctionViaException).
        // The second element [1] is the actual caller of myCalledFunctionViaException.
        // pre($trace);
        $caller = $trace[0];
        // pre($caller);
        $callerFile = $caller['file'] ?? 'N/A';
        $file = str_replace(ROOTPATH,'',$callerFile);
        $paths = explode("/",$file);
        if (count($paths)>2) {
          // pre($paths);
          if ($paths[0] == "system" && $paths[1] == "modules") {
            print "Path is: system/modules/" . $paths[2];
          }
        }
        // pre($file);
        // Glue first three IF 0 = system AND 1 = modules
        // pre($callerFile);
        // $callerLine = $caller['line'] ?? 'N/A';
        // $callerFunction = $caller['function'] ?? 'N/A';
        // $callerClass = $caller['class'] ?? 'N/A';

        // echo "I was called from (via Exception):\n";
        // echo "File: " . $callerFile . "\n";
        // echo "Line: " . $callerLine . "\n";
        // echo "Function/Method: " . $callerClass . ($callerClass ? '::' : '') . $callerFunction . "\n";
        // echo "--------------------------\n";
    }
}


/**
 * Returns the module directory path of the calling file, relative to ROOTPATH.
 * Returns empty string if the caller is not inside system/modules/.
 *
 * @return string e.g. "system/modules/booking"
 */
function getCallerModulePath(): string
{
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $callerFile = $trace[1]['file'] ?? '';
    if ($callerFile === '') return '';

    $relative = str_replace('\\', '/', str_replace(ROOTPATH, '', $callerFile));
    $parts = explode('/', $relative);

    if (count($parts) >= 3 && $parts[0] === 'system' && $parts[1] === 'modules') {
        return 'system/modules/' . $parts[2];
    }

    return dirname($relative);
}



function getSiteBaseURL() {
  $path = __DIR__ . "/configs/";
  require_once $path . 'config.inc.php';
  require_once $path . 'localconfig.inc.php';
  return $GLOBALS['CONFIG']['www-root'];

}

function getTplIcon($name) {
  $file = TPLPATH . "/" . $GLOBALS['CONFIG']['template']."/icons/{$name}.svg";
  if(!is_file($file)) return null;
  return file_get_contents($file);
}