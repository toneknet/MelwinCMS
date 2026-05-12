<?php
// Kolla inloggad!
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$input = new input();
$db = db::getInstance();


// print_r($_POST);
// print_r($_GET);
$id = 0;
$user = "";

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
  $id = intval($_GET['id']);
}
if (!preg_match('/[^A-Za-z0-9]/', $_GET['user'])) // '/[^a-z\d]/i' should also work.
{
  $user=$_GET['user'];
}

$sql = "user = '{$user}'";
$arrGetUser = $db->o_get_all('user', "SELECT user,id FROM [table] WHERE user = '{$user}' ");

// If NOONE has the username, then exit
if (count($arrGetUser) == 0) { print json_encode(array('type' => 1)); die();}

// If SOMEONE has the username and no ID is set, then exit
if ($id == 0) { print json_encode(array('type' => 0)); die(); }

// If SOMEONE has the username, then loop to se if it is the same as userid
foreach ($arrGetUser as $key => $value) {
  if ($value['id'] == $id) {
    print json_encode(array('type' => 1)); die();
  }
}
print json_encode(array('type' => 0)); die();
