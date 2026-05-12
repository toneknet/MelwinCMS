<?php
// Kolla inloggad!
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$input = new input();
//$db = db::getInstance();
$act = $input->get('action');
$sub = $input->get('subaction');



if ($act == "getAccessTree") {

    $groups=$input->get('groups');
    // make array
    $arrGroups = explode(",",$groups);
    foreach ($arrGroups as $key => $value) {
      if (!is_numeric($value)) {
        unset($arrGroups[$key]);
        die('No groups is selected!');
        continue;
      }
    }
    // Start generating list
    // Should I hide pages you shouldnt see? Ofc

    // List access after applied access
    listGroups($arrGroups);

} elseif ($act == "getCarModel" && $input->get('regnr') != "" ) {
  if ( strlen($input->get('regnr')) == 6 ) {
    $regnr = $input->get('regnr');

    function getCarInfo($regnr) {
        $url = "https://www.car.info/sv-se/license-plate/S/" . strtolower($regnr);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => "Mozilla/5.0",
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $html = curl_exec($ch);
        curl_close($ch);

        if (!$html) {
            return null;
        }

        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        $dom->loadHTML($html);
        // print "<pre>";
        // // print_r();
        // print $html;
        // print "</pre>";
        $xpath = new DOMXPath($dom);

        // Hämta handelsbeteckning (ident_name)
        $identNodes = $xpath->query("//*[contains(@class, 'ident_name')]");

        $kaffepaus = $xpath->query("//h1[contains(text(),'Kaffepaus')]");
        if (str_contains($html,"Kaffepaus")) return $regnr;

        $identName = null;
        if ($identNodes->length > 0) {
            $identName = trim($identNodes->item(0)->nodeValue);
        }

        // BONUS: försök hitta fordonsår
        $yearNodes = $xpath->query("//td[contains(text(), 'Fordonsår')]/following-sibling::td");

        $year = null;
        if ($yearNodes->length > 0) {
            $year = trim($yearNodes->item(0)->nodeValue);
        }

        return $identName;
        // return [
        //     "regnr" => strtoupper($regnr),
        //     "handelsbeteckning" => $identName,
        //     "fordonsar" => $year
        // ];
    }
    print getCarInfo($regnr);

    // if (isset($_GET['regnr']) && strlen($_GET['regnr']) == 6) {
    // print "<pre>";
    // var_dump(getCarInfo($_GET['regnr']));
    // print "</pre>";
    // } else {
    //     print "Nothing found";
    // }
    // } else {
  }
  die();
  // print  $regnr;
  // TODO: fungerar inte för stunden
  //print file_get_contents($GLOBALS['CONFIG']['www-root'] . '/resources/plugins/getcar/getSingleCarInfo.php?regnr=' . $regnr);

// SYSTEM stuff
} elseif ($act == "system" && $sub <> "") {
  // DBLIST
  if ($sub == "reorder") {
    /*
     * Denna funktion sorterar tabeller i ordning
     */
    $result = $input->get("dblist");
    $position=0;
    if (is_array($result)) {
      foreach($result as $value) {
          $position++;
          if (intval($value)!=$value) {
              alertbox('error',$GLOBALS['LANG']['ERROR']['ajax']); // . " " . __FILE__ . "@" . __LINE__
              exit;
          }
          if ($db->o_update($_GET['table'],array('ordning'=>$position),"id={$value}")!=true) {
              alertbox('error',$GLOBALS['LANG']['ERROR']['ajax']);
              exit;
          }
      }
      alertbox('success',$GLOBALS['LANG']['GENERAL']['saved']);
    }

  } elseif ($sub == "booking") {

    /*print "<pre>";
    print_r($_GET);
    print_r($_POST);
    print "</pre>";*/


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
    if (salt( $sub . date("Y-m-d") . $id)) {
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
                    //$sql = "SELECT * FROM [table] WHERE bus=$bus AND ((startdatex BETWEEN CAST('$start' AS DATETIME) AND CAST('$stop' AS DATETIME)) OR (stopdatex BETWEEN CAST('$start' AS DATETIME) AND CAST('$stop' AS DATETIME) )) AND companyid=" . getCompanyID() . " AND deleted=0 " . $sqlID;
                    $sql = "SELECT * FROM [table] WHERE bus={$bus} AND ( (CAST('{$start}' AS DATETIME)  BETWEEN startdatex AND stopdatex ) OR  (CAST('{$stop}' AS DATETIME)  BETWEEN startdatex AND stopdatex ) ) AND companyid=1 AND deleted=0 " . $sqlID;
                    //print $sql;

                    $arrCrashed = $db->o_get_all('booking', $sql);
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
  }
} else {
  die('Its not the fall that hurts, its when you hit the ground.');
}









/**
 * Nedan är för att se vilken access olika användare har
 * @return [type] [description]
 */
function listGroups($arrGroups) {
  $db = db::getInstance();
  $sql = "";
  foreach ($arrGroups as $key => $value) {
    $sql.= " id={$value} OR ";
  }
  $sql = substr($sql,0,-3);
  $arrAccessDB = $db->o_get_all('groups', "SELECT * FROM [table] WHERE $sql ");
  // Get pages that are allowed
  $arrPages = $db->o_get_all('page', "SELECT id, pid, visible, label FROM [table] WHERE  id!=0  AND deleted=0 AND visible=1 ORDER BY isstartpage DESC, pid ASC, ordning ASC");
  // Set access icons here
  foreach($arrAccessDB as $xvalue) {
    foreach (unserialize($xvalue['settings']) as $key => $value) {
        foreach ($value as $skey => $svalue) {
            if (isset($arrPages[$key]) && is_array($arrPages[$key])) {
                $arrPages[$key][$skey]=$svalue;
            }
        }
    }
  }
  // Remove unallowed pages
  foreach ($arrPages as $key => $value) {
    if (!isset($value['allow'])) {
      unset($arrPages[$key]);
    }
  }

  print "\n<table width=\"100%\">\n";
  print "\t<thead>\n";
  print "\t\t<tr>\n";
  print "\t\t\t<th>Sida</th>\n";
  print "\t\t\t<th>Tillåten</th>\n";
  print "\t\t\t<th>Editera</th>\n";
  print "\t\t</tr>\n";
  print "\t</thead>\n";
  GenerateTree($arrPages, 0, 0);
  print "</table>\n";
}

function GenerateTree($arrData,$intPid, $intLevel) {
    if (is_array($arrData)) {
        foreach($arrData as $value) {
            if ($value['pid']==$intPid) {
                print "\t<tr>\n";
                print "\t\t<td>";
                for ($index = 0; $index < $intLevel; $index++) {
                    print getIconFull('transp',8,5);
                }
                print $value['visible']? getIconFull('visible',8,8): getIconFull('invisible',8,8);
                print " " . $value['label'] . "</td>\n";
                print "\t\t<td>";
                if (isset($value['allow']) && $value['allow'] == true) {
                  print getIconFull("ok",8,8);
                } else {
                  print getIconFull("transp2",8,8);
                }
                //print $value['allow'] ? getIconFull("ok") : getIconFull('transp2'); //exclamation
                print "</td>\n";
                print "\t\t<td>";
                if (isset($value['edit']) && $value['edit'] == true) {
                  print getIconFull("ok",8,8);
                } else {
                  print getIconFull("transp2",8,8);
                }
                //print $value['edit'] ? getIconFull("ok") : getIconFull('transp2'); //exclamation
                print "</td>\n";
                print "\t</tr>\n";
                GenerateTree($arrData, $value['id'], $intLevel+1);
            }
        }
    }
}







/*
list($year, $month, $day) = explode("-", $datum);

// Om inga v&auml;rden var satta s&aring; s&auml;tt dagens
if ($year=="") {$year=date("Y"); }
if ($month=="") {$month=date("m");}
if ($day=="") {$day=date("d");}

// Kollar oj&auml;mn vecka eller inte
$week = date("W", mktime(0, 0, 0, $month, $day, $year));
if ($week &1) //0=even, 1=odd
{
    $day=$day-7;
}

// Kollar första dagen på veckan
// Ta reda på vilken som är den första dagen på veckan!
$day_of_wk = date("w", mktime(0, 0, 0, $month, $day, $year));
if ($day_of_wk==0){$day_of_wk=7;}
$datum = date("Y-m-d", mktime(0, 0, 0, $month, $day-($day_of_wk - 1), $year));
$last_datum = date("Y-m-d", mktime(0, 0, 0, $month, $day-($day_of_wk - 1)+13, $year));

// Sätter första dagen på varje vecka
$arrFirstDay[0] = $datum;
list($xy,$xm,$xd) = explode("-",$datum);
$arrFirstDay[1] = date("Y-m-d",  mktime(1, 1, 1, $xm, $xd+7, $xy));
$arrFirstDay[2] = date("Y-m-d",  mktime(1, 1, 1, $xm, $xd+14, $xy));

$start_date =  $datum;
$stop_date = date("Y-m-d",  mktime(1, 1, 1, $xm, $xd+13, $xy));*/


//$arrDrivers = $db->o_get_all('',"SELECT h.*,i.namn FROM hallens_bemanning as h LEFT JOIN hbook_user as i ON h.driverid = i.id WHERE (h.date BETWEEN '$start_date' AND '$stop_date') AND h.text LIKE BINARY '%B%' AND h.companyid='" . getCompanyID() . "' GROUP BY date, driverid ORDER BY h.date ASC");



//print "<pre>";
/*print "$start_date, $stop_date\n";
//print_r($arrFirstDay);
print_r($arrDrivers);*/
$msg = "";
/*foreach ($arrDrivers as $key => $value) {
  $datum = $value['date'];
  $id = $value['driverid'];
  $namn = $value['namn'];
  $out = "<p>$datum - $namn - ";
  $arrBook = $db->o_get_all('',"SELECT h.* FROM hallens_booking as h LEFT JOIN hallens_vehicle as i ON h.bus = i.id WHERE ('$datum' BETWEEN h.startdate AND h.stopdate) AND h.companyid=".  getCompanyID() . " AND (h.chauffor_id = $id OR h.chaffor = '$namn' OR h.reseledare = '$namn') AND h.deleted=0 ORDER BY h.startdate,h.starttime");
  //$out = "";
  if (count($arrBook)>0) {
    $out.= "OK! ";
    foreach ($arrBook as $skey => $svalue) {
      $out.= $svalue['resan'].", ";
    }
    $out = str_replace('<p>','<p style="color:green;">',$out);
  } else {
      $out.= " INGEN POST FANNS  ";//<strong style=\"color:red;\"></strong>
      $out = str_replace('<p>','<p style="color:red; font-weight:bolder;">',$out);
  }
  $out = substr($out, 0,-2 );
  $out.= "</p>";
  //print_r($arrBook);
  //$arrTrip = $db->o_get_all('',"SELECT h.* FROM hallens_booking as h WHERE (h.date BETWEEN '2022-06-13' AND '2022-06-26') AND h.text LIKE BINARY '%B%' AND h.companyid='" . getCompanyID() . "' GROUP BY date, driverid ORDER BY h.date ASC");
  $msg.=$out;
}*/
//print "</pre>";
$msg.= "<style> p {margin:2px;}</style>";
$msg.= "Om du gör en ändring så måste du spara sidan efter dina ändringar innan den visar korrekt svar vid kontroll.";

//print json_encode(array('success'=> 1,'msg' => $msg));

/*print "[EOF]";*/
