<?php
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/
// Gå igenom alla bibliotek och se att det finns en indexfil där.
// Annars ska de skapas och gå vidare till modulerooten
$path = __DIR__;
$iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path),
            RecursiveIteratorIterator::SELF_FIRST);
$arrPath = array();
foreach($iterator as $file) {
    if($file->isDir()) {
        $tmpPath = $file->getRealpath();
        $arrPath[$tmpPath] =file_exists($tmpPath . "/index.php") ? 1 : 0;
    }
}
foreach ($arrPath as $key => $value) {

  if ($value == 1) {continue;}
  
  $str = str_replace($path . '/','',$key) . "/";
  $intCount = substr_count($str,"/") ;
  print $str . "     -    " . $intCount . "<br />";
  $strDown = "";
  for ($i=0; $i < $intCount; $i++) {
    $strDown.= "../";
  }

  print "include('" . $strDown . "index.php');<br />";

  file_put_contents($key . "/index.php","<?php
    include('" . $strDown . "index.php');
    exit;
    // Added by fixIndex.php
    // " . date("Y-m-d H:i:s")). "
    ";
}
