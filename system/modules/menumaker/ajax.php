<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$input = new input();
$a = $input->get('subaction');
if ($a == 'path') {
  $data =  intval($input->get('data'));
  if ($input->get('type') == 'get' && intval($data) > 0) {
    $db = db::getInstance();
    $cid = getCompanyID();
    $arrReturn = $db->o_get_row("page","SELECT * FROM [table] WHERE id = {$data}");
    if (count($arrReturn)>0) {
      print $arrReturn['src'] . "/";
    }
  }
} elseif($a == 'palette') {
  // Get table
  $strTable =  $input->get('data');
  $db = db::getInstance();
  $arrTables = $db->o_list_tables();
  $arrPrefix = $db->o_list_prefix();
  // Flip table to get tablenames as keys
  $arrTablesx = array_flip($arrTables);

  // Compare if table exists
  $bolExists = false;
  foreach ($arrPrefix as $key => $value) {
    if (isset($arrTablesx[$value.$strTable])) {
      //print "FANNS!";
      $bolExists = true;
      $strExistingTable = $value.$strTable;
      break;
    }
  }
  if (!$bolExists) { die('And then all begin');}

  $arrFields = $db->o_list_fields($strExistingTable);
  $strPalette = "";
  foreach ($arrFields as $value) {
      $strPalette.=" <span class=\"addtopalette button\">" . $value['name']."</span>";
  }
  // Return values to user
  print substr($strPalette,1);
}
