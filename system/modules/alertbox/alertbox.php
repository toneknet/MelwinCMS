<?php

/**
Name: Alertbox
Author: Martin Tonek
Author URI: https://www.tonek.se
Description: Create a alertbox in your scripts easy with this plugin. You have [w]arning,[e]rror, [n]otice and [s]uccess as alternative. Use as: setMsg('n','YOUR TEXT HERE')
Version: 1.0
Type: Module
 */
/**
 * Description of navigation
 *
 * @author Martin Tonek <martin@tonek.se>
 */
class alertbox
{

    function __construct($arrModuleSettings = '')
    {
        // print "<div id=\"alert-box-output\">\n";
        // print "</div>\n";
        print "<!-- alertbox -->";
        $arrTypes = array('error', 'warning', 'success', 'notice');
        if (isset($_SESSION['message'])) {
            foreach ($arrTypes as $strType) {
                if (isset($_SESSION['message'][$strType]) && is_array($_SESSION['message'][$strType])) {
                    foreach ($_SESSION['message'][$strType] as $strMsg) {
                        $this->showBox($strType, $strMsg);
                    }
                }
            }
            unset($_SESSION['message']);
        }

        if (isSuperUser()) {
            if (isset($arrModuleSettings['list']['type']) && $arrModuleSettings['list']['type'] != "") {
                $this->showBox($arrModuleSettings['list']['type'], $arrModuleSettings['list']['text']);
            }
        }
        // print "<script>";

        // print "$(document).ready(function() {\n";
        // print "    function checkSessionMessage() {\n";
        // print "        $.ajax({\n";
        // print "            url: '/get-session-message', // Endpoint för att hämta sessionen\n";
        // print "            method: 'GET',\n";
        // print "            success: function(response) {\n";
        // print "                if (response.message) {\n";
        // print "                    var strType = Object.keys(response.message)[0];\n";
        // print "                    var strMsg = response.message[strType];\n";
        // print "                    var alertBox = `<div class=\"alert-box ${strType}\">${strMsg}</div>`;\n";
        // print "                    $('#alert-box-output').html(alertBox);\n";
        // print "                    setTimeout(function() {\n";
        // print "                        $('#alert-box-output').empty();\n";
        // print "                    }, 3000);\n";
        // print "                }\n";
        // print "            }\n";
        // print "        });\n";
        // print "    }\n";
        // print "    // Kör funktionen var 5:e sekund\n";
        // print "    setInterval(checkSessionMessage, 5000);\n";
        // print "});\n";
        // print "</script>";
        // print "</div>\n";
    }

    function showBox($strType, $strMsg)
    {
        print "<div class=\"alert-box {$strType}\">{$strMsg}</div>";
    }

    static function showBoxDirect($strType, $strMsg)
    {
        return "<div class=\"alert-box {$strType}\">{$strMsg}</div>";
    }

    /**
     * Add error message to user
     *
     * @param string $type Allowed to use (s)uccess, (e)rror, (w)arning, (n)otice
     * @param string $msg  Message to user
     * @return void
     */
    static function add($type, $msg)
    {
        print "<!-- alertbox:add -->";
        $arrTypes = array('s' => 'success', 'e' => 'error', 'w' => 'warning', 'n' => 'notice');
        if (!in_array($type, $arrTypes)) return;
        $_SESSION['message'][$arrTypes[$type]][] = $msg;
    }
}
