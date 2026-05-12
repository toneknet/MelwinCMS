<?php
/**
Name: system/ajax/showHelp.php
Author: Martin Tonek
Author URI: https://www.tonek.se
Description: Handles help (INTE FÄRDIG!!)
Version: 1.0
Type: ajax
*/
session_start();
//error_reporting(E_ALL & ~E_NOTICE);
include("../configs/config.inc.php");
include("../configs/localconfig.inc.php");
include("../libs/db.cls.php");
include("../libs/input.cls.php");
include("../libs/auth.cls.php");

$db = db::getInstance();
$input = new input();

print "
<!DOCTYPE html>
<html lang=\"sv\">
<head>

  <!-- Basic Page Needs
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <meta charset=\"utf-8\">
  <title></title>
  <meta name=\"description\" content=\"\">
  <meta name=\"author\" content=\"\">

 <!-- CSS
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <link rel=\"stylesheet\" href=\"//code.jquery.com/ui/1.11.3/themes/smoothness/jquery-ui.css\">
  <link rel=\"stylesheet\" href=\"" . $GLOBALS['CONFIG']['www-root'] . "/tpl/skeleton/css/normalize.css\">
  <link rel=\"stylesheet\" href=\"" . $GLOBALS['CONFIG']['www-root'] . "/tpl/skeleton/css/skeleton.css\">
  <link rel=\"stylesheet\" href=\"" . $GLOBALS['CONFIG']['www-root'] . "/tpl/skeleton/style.css\">
";
    // Kolla om superadmin.. Isf ska han kunna administrera detta.
$strType = $input->get('title');
$auth = auth::getInstance();
if ($auth->isSuperUser()) {
print "
  <script src=\"//code.jquery.com/jquery-1.11.2.min.js\"></script>
  <script type=\"text/javascript\" src=\"" . $GLOBALS['CONFIG']['www-root'] . "/resources/plugins/jeditable/jquery.jeditable.mini.js\"></script>

<style>
.edit,
.edit_area {
width:100%;
margin:10px;
}

</style>


  <script type=\"text/javascript\">

 $(document).ready(function() {
     $('.edit').editable('" . $GLOBALS['CONFIG']['www-root'] . "/system/ajax/saveHelp.php?title=" . $strType . "', {
         indicator : 'Saving...',
         submit    : 'Spara',
         tooltip   : 'Click to edit...',
         style   : 'inherit',
         callback : function(value, settings) {
         console.log(this);
         console.log(value);
         console.log(settings);
     }
     });
     $('.edit_area').editable('" . $GLOBALS['CONFIG']['www-root'] . "/system/ajax/saveHelp.php?title=" . $strType . "', {
        loadurl   : '" . $GLOBALS['CONFIG']['www-root'] . "/system/ajax/loadHelp.php?title=" . $strType . "',
        type      : 'textarea',
        cancel    : 'Avbryt',
        submit    : 'Spara',
        tooltip   : 'Click to edit...',
        style   : 'inherit',
        callback : function(value, settings) {
            console.log(this);
            console.log(value);
            console.log(settings);
        }
     });
 });

</script>
";
    $class_h = " class=\"edit\"";
    $class_t = " class=\"edit_area\"";
} else {
    $class_h = "";
    $class_t = "";
}
print "</head>\n<body>\n";
// Hämtar alla rubriker
$arrRubrik = $db->o_get_all('help',"SELECT * FROM [table]", 'type');

if (isset($arrRubrik[$strType]) || $auth->isSuperUser()) {
    print "<h3 {$class_h} id=\"title\">" . $arrRubrik[$strType]['title'] . "</h3>";
    print "<div {$class_t} id=\"text\">" . $arrRubrik[$strType]['text'] . "</div>";
} else {
    print "Tyvärr finns det ingen information om detta kapitel";
}
print "</body>\n</html>\n";
