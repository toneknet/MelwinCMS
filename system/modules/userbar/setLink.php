<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
//error_reporting();
// Kolla att användaren är SU
$input = new input();
if ($auth->isSuperUser()) {
  $_SESSION['hbook']['suci'] = intval($input->get('id'));
  print json_encode(array('reload' => '1'));
} else {
  print json_encode(array('error' => 'No Auth'));
}
