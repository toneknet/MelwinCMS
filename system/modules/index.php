<?php
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/
$path = str_replace("modules", "", __DIR__);
include ($path .'configs/config.inc.php');
include ($path . 'configs/localconfig.inc.php');
header("Location: " . $GLOBALS['CONFIG']['www-root']);
exit;
print "Honey, I'm home!";
