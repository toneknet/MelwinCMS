<?php
/*
Name: Userbar
Author: Martin Tonek
Author URI: https://www.tonek.se
Description: Let your user see that they are logged in and to which company
Version: 0.1
Type: Module
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
 * Description of userbar
 *
 * @author Martin Tonek <martin@tonek.se>
 */
class userbar {

    private $useUL = true;
    function __construct($arrModuleSettings) {
      if (!isSuperUser()) return;
      $auth = auth::getInstance();
      $arrUser = $auth->getUserAndCompany();
      print "<style>
      // .userbar {
      //   display:grid;
      //   align-items:center;
      // }
      .userbar ul {
        display: flex;
        align-items: center;
        list-style-type: none;
        margin: 0;
        padding: 0;
        overflow: hidden;
        width:100%;
        // background-color: #333333;
      }

      .userbar ul li {
        float: left;
        justify-content: center;
        align-self: center;
        padding: 0 5px;
        margin:3px;
      }

      .userbar ul li a {
        // display: block;
        color: white;
        text-align: center;
        padding: 0 5px;
        // padding: 14px 16px;
        text-decoration: none;
        // display:flex;
        // align-items: center;

      }

      .userbar ul li a:hover {
        // background-color: #111111;
      }
      
      
      
      </style>";


      print "<div class=\"userbar\">"; // style=\"display:flex; height:30px;\"
      print ($this->useUL) ? "<ul>" :"";

      print ($this->useUL) ? "<li style='flex-grow: 1;'>" :"";
      print "Inloggad som <b>" . $arrUser['name'] . "</b> för <b>" . $arrUser['company']."</b>";
      print ($this->useUL) ? "</li>" :"";

      if ($arrUser['superuser'] == 1) {
/*        if (isset($_SESSION['hbook']['suci'])) {
          $arrUser['companyid'] = $_SESSION['hbook']['suci'];
        }*/

        $db = db::getInstance();
        // En select här
        $arrAnswer = $db->o_get_all('company' ,"SELECT * FROM [table] WHERE deleted=0 ");
        if (count($arrAnswer)>0) {
          print ($this->useUL) ? "<li style='float:right;'>" :"";
          print "\n\t<select name=\"SU_company\" id=\"SU_company\" style=\"margin-left:10px; height:auto; padding: 0px; color: black; \">\n";
          foreach ($arrAnswer as $key => $value) {
            print "\t\t<option value=\"" . $value['id'] . "\"";
            if ($arrUser['companyid'] == $value['id']) { print " SELECTED ";}
            print ">" . $value['namn'] . "</option>\n";
          }
          print "\t</select>\n";
          print ($this->useUL) ? "</li>" :"";
      
        }
        print "<script>\n
        document.getElementById(\"SU_company\").addEventListener(\"change\", function(){\n
          $.ajax({
              url: URLroot + 'system/ajax/ajaxRequest.php?action=missing_link&id=' + this.value,
              success: function (data) {
                var parsed_data = JSON.parse(data);
                // LOGOUT
                console.log(parsed_data.msg);
                if (parsed_data.error !== undefined) {
                  alert(parsed_data.error);
                  location.reload();
                } else if (parsed_data.reload == 1) {
                  location.reload();
                }
              },
          });
        });\n
        \n</script>";
/*        print "<pre>";
//        print_r($_SESSION);
        print_r($arrUser);
        print "</pre>";
        print $_SESSION['missing_link'];*/



        // Statiska länkar här!
        $pageid = route::$pageid ?? NULL;
        print ($this->useUL) ? "<li style='float:right;'>" :"";
        // print "<p style='float:right;'>";
        // TODO: göra sidorna dynamiska istället
        print "<a href='" . $GLOBALS['CONFIG']['www-root'] ."/settings/page?action=edit&id={$pageid}'>" . getIconFull('edit') ."</a>";
        print "<a href='" . $GLOBALS['CONFIG']['www-root'] ."/settings/page?action=module&id={$pageid}'>" . getIconFull('module') ."</a>";
        // print "</p>";
        print ($this->useUL) ? "</li>" :"";
      

      }
      // Kolla om info finns att visa här!
      //print "<a href=\"?action=logout\" style=\"float:right; text-decoration:none; font-weight:bold;\">Logga ut</a>";
      #print "<a href=\"#\" style=\"float:right; text-decoration:none; font-weight:bold; margin: 0px 15px; padding: 0px 10px;font-size:15px; border-radius:15px; TransformY(-50%);background-color:#003366;\">&#8505;</a>";
      print "</div>";
    }

    // function userbar($arrModuleSettings){
    //     $auth = auth::getInstance();
    //     $arrUser = $auth->getUserAndCompany();
    //     print "<div class=\"userbar\">y Inloggad som <b>" . $arrUser['name'] . "</b> för <b>" . $arrUser['company']."</b>";
    //     print "<a href=\"?action=logout\" style=\"float:right; text-decoration:none; font-weight:bold;\">Logga ut</a>";
    //     print "</div>";
    // }

}
