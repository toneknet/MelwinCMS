<?php
/*
Name: Company
Author: Martin Tonek
Author URI: https://www.tonek.se
Description: Handles the company settings
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
 * Description of company
 *
 * Endast access av MArtin
 *
 * @author Martin Tonek <martin@tonek.se>
 */
class company {

    function __construct($arrModuleSettings) {
        /*ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);*/
        // Set table manuallly here
        $arrModuleSettings['table'] = "company";
        $input = new input();
        // Hämta alla GET här
        $arrInput = $input->getAll();
        if (isset($arrInput['id']) && is_numeric($arrInput['id'])) {
          $id = intval($arrInput['id']);
        } else {
          $id = "";
        }

        //$id = intval($arrInput['id']);
/*        print __CLASS__."::" . __FUNCTION__; */
        /*print "<pre>";
        print_r($arrModuleSettings);
        print_r($arrInput);
        var_dump($arrInput['action']);
        print "</pre>";*/



        $allowedActions = array('save','add','edit','delete','login');
        if (
          (isset($arrInput['action']) && (in_array($arrInput['action'],$allowedActions) || $arrInput['action'] === "" )) || !isset($arrInput['action'])
        ) {
          $list = new listandedit($arrModuleSettings);
        } else {
          // Bygga settings här! Hur enklast ha det? Hämta från fil eller från db?
          if ($arrInput['action'] == "settings") {
            $db = db::getInstance();
            print "<div class=\"settings\">";
            print "<h1>" . $GLOBALS['LANG']['COMPANY']['title'] . "</h1>";
            print "<a class=\"button float_right margin_right\" href=\"" . getURL(array(), true) . "\"> &lt;&lt; " . $GLOBALS['LANG']['GENERAL']['return'] . "</a>";
            print "<br /><br />";
            // Loopa igenom alla tabeller och kolla vilka fält som är hideable.
            // Detta sparas sedan i databas settings... eller i company settings
            // Save
            if (isset($arrInput['subaction']) && $arrInput['subaction'] == 'save' && $arrModuleSettings['own_access']['edit'] == 1){
              // Extra kontroll här? Borde egentligen bygga in det i
              // formcollector så den verifierar att inte felaktiga data
              // kommer in.
              $arrPost = $input->postAll();
              unset($arrPost['db_submit']);

              // En fix för att kunna använda den befintliga form lib
              foreach ($arrPost as $key => $value) {
                if (str_starts_with($key,"db_")) {
                  $newkeys = explode("/",$key);
                  $newkeys[0] = str_replace("db_","",$newkeys[0]);
                  if (!isset($arrPost['module'])) $arrPost['module'] = array();
                  $arrPost['module'][$newkeys[0]][$newkeys[1]] = $value;
                  /*print "<pre>";
                  print_r($newkeys);
                  print "</pre>";
                  print $key."<br />";*/
                  unset($arrPost[$key]);
                }
              }


              /*print "<pre>";
              print_r($arrPost);
              print "</pre>";
              return;*/

              //$arrPost['fields'] = array();
/*              print "<pre>";
              print_r($_POST);
              //print serialize($arrPost['fields']);
              print "</pre>";*/
              if ($db->o_update('company',array('settings'=>serialize($arrPost)),'id='. $id)) {
                setMsg('s',$GLOBALS['LANG']['GENERAL']['saved']);
              } else {
                setMsg('e',$GLOBALS['LANG']['GENERAL']['error']);
              }

            }

            // Load settings from every [module]/config/settings.php if exists!


            // Load
            $strData = $db->o_get_row('company','SELECT * FROM [table] WHERE id=' . $id);
            if (isset($strData['settings']) && $strData['settings'] !="") {
              $arrSettings = unserialize($strData['settings']);
            } else {
              $arrSettings = array();
            }

/*            print "<pre>";
            print_r($arrSettings);
            print "</pre>";*/


            $form = new form();
            print $form->form_begin('company_settings',array('arrQuery' => array ('action' => 'settings', 'subaction' => 'save', 'id' => $arrInput['id'])));

            print $form->form_submit('Spara');
            /**/


            /*
             * Module fields here (Kolla buscalender/config/settings.php för mall)
             * Eller ska man göra som så att man lägger till det som en SETTINGS
             * i varje dca fil som redan laddats? Då sparar man ju momentet för
             * att ladda extra fält. MEN.. den används bara om man jobbar mot
             * databas, så settings får det bli
             */
            //
            print "<h3>" . $GLOBALS['LANG']['COMPANY']['module_settings'][0] . "</h3>";
            print "<p>" . $GLOBALS['LANG']['COMPANY']['module_settings'][1] . "</p>";

            print "<style>
            .settings * {
              margin-top:5px;
              margin-bottom:0;
            }
            .settings p {
              margin:0;
            }
            .db_submit {
              position: sticky;
              top: 5px;
              float: right;
            }
            .companyinfo {
              display:block;
              margin:10px 0px;
            }
            </style>";


            $arrModules = getModulePaths();

            foreach ($arrModules as $key => $value) {
              if (substr($value, -4,4) == ".php") continue;
              $strLib = MODPATH . $value . "/config/settings.php";
              // File error here!!
              if(is_file($strLib)) {
                // print "<b>".$strLib."</b><br />";
                include($strLib);
                //print "<b>{$strLib} exists</b><br />";
                if (isset($GLOBALS['MODULE'][$value]['settings']) && is_array($GLOBALS['MODULE'][$value]['settings'])) {
                  print "<h5 class=\"settings\">" . strtoupper($value) . "</h5>";
/*                  print "<pre>";
                  print_r($GLOBALS['MODULE'][$value]['settings']);
                  $data = $form->MakeFields(array('fields' => $GLOBALS['MODULE'][$value]['settings']), true);
                  print str_replace("\"db_","\"".$value . "_",$data);
                  print "</pre>";*/
                  foreach ($GLOBALS['MODULE'][$value]['settings'] as $skey => $svalue) {
                    // Gör ingen speciell kontroll här!
                    $type = $svalue['type'];
                    if (!isset($svalue['field'])) $svalue['field'] = $value."/" . $skey;
                    //print $type."<br />";
                    if ($type == 'int' || $type == 'varchar') {
                      //                        $output.= $this->field_INPUT($newtabledata[$key]);
                      //print $this->field_INPUT($newtabledata[$key]);
                      if ( isset($arrSettings['module'][$value][$skey]) ) {
                        $svalue['value'] = $arrSettings['module'][$value][$skey];
                      }
                      print $form->field_INPUT($svalue);


                      /*print "<label for=\"module_{$value}_{$skey}\">" . $svalue['caption'][0] . "</label>\n";
                      print "<input type=\"text\" id=\"module_{$value}_{$skey}\" name=\"module[{$value}][{$skey}]\"";
                      if ( isset($arrSettings['module'][$value][$skey]) ) {
                        print " value=\"" . $arrSettings['module'][$value][$skey] . "\"";
                      }
                      //print isset($arrSettings['module'][$value][$skey]) ? "checked" : "";
                      print "><br /><i>" . $svalue['caption'][1] . "</i><br />";*/



                    } elseif ($type == 'char' || $type == 'tinyint' || $type == 'checkbox' ) {

                      if ( isset($arrSettings['module'][$value][$skey]) && $arrSettings['module'][$value][$skey] ) {
                        $svalue['value'] = 1;
                      }
                      print $form->field_CHECKBOX($svalue);


                      /*print "<input type=\"checkbox\" id=\"module_{$value}_{$skey}\" name=\"module[{$value}][{$skey}]\"";
                      if ( isset($arrSettings['module'][$value][$skey]) && $arrSettings['module'][$value][$skey] ) {
                        print "checked";
                      }
                      //print isset($arrSettings['module'][$value][$skey]) ? "checked" : "";
                      print " value=\"1\"><b>&nbsp;" . $svalue['caption'][0] . "</b> <br /><i>" . $svalue['caption'][1] . "</i><br />";*/
                    } elseif ($type == 'checkboxtable' ) {
                        if ( isset($arrSettings['module'][$value][$skey]) ) {
                          $svalue['value'] = $arrSettings['module'][$value][$skey];
                        }
                        print $form->field_CHECKBOXTABLE($svalue);
                    } elseif ($type == 'password' ) {
//                        $output.= $this->field_PASSWORD($newtabledata[$key]);
                    } elseif ($type == 'hidden') {
//                        $output.= $this->field_HIDDEN($newtabledata[$key]);
                    } elseif ($type == 'color') {
//                        $output.= $this->field_COLOR($newtabledata[$key]);
                    } elseif ($type == 'select') {
                        /*print "SELECT";
                        var_dump($skey);
                        print "<br />";
                        var_dump($svalue);*/
                        if ( isset($arrSettings['module'][$value][$skey]) ) {
                          $svalue['value'] = $arrSettings['module'][$value][$skey];
                        }
                        print $form->field_SELECT($svalue);
//                        $output.= $this->field_SELECT($newtabledata[$key]);
                    } elseif ($type == 'file') {
//                        $output.= $this->field_FILE($newtabledata[$key]);
                    } elseif ($type == 'radio') {
                    //                print "RADIO";
//                        $output.="<label for=\"db_{$key}\">{$caption}</label>\n";
                        // Kollar options först
//                        if (!isset($value['options'])) {$value['options'] = $this->getOptions($value);}

/*                        if (isset($value['options']))
                        {
                            foreach ($value['options'] as $okey=>$ovalue) {
                                $output.="<input type=\"radio\" name=\"db_{$key}\" id=\"db_{$key}\" value=\"" . $okey . "\"";
                                if ($value['value']==$okey) {$output.=' CHECKED';}
                                $output.="> " . $ovalue . "<br>\n";
                            }
                        }*/
                    } elseif ($type == 'date') {
//                        $output.= $this->field_DATE($newtabledata[$key]);
                    } elseif ($type == 'time') {
//                        $output.= $this->field_TIME($newtabledata[$key]);
                    } elseif ($type == 'datetime') {
//                        $output.= $this->field_DATETIME($newtabledata[$key]);
                    } elseif ($type == 'textarea' || $type == 'text') {
//                        $output.= $this->field_TEXTAREA($newtabledata[$key]);
                          if ( isset($arrSettings['module'][$value][$skey]) ) {
                            $svalue['value'] = $arrSettings['module'][$value][$skey];
                          }
                          print $form->field_TEXTAREA($svalue);
                    } else {
//                        $output.= "NOT DEFINED!! ({$key}) WITH TYPE ({$type})<br>";
                    }






                  }
                }


              } else {
                //print "{$strLib} doesn't exists<br />";
              }

            }


/*            print "<pre>";
            print_r($arrModules);
            print "</pre>";
*/
            // Table fields here
            print "<h3>" . $GLOBALS['LANG']['COMPANY']['table_fields'][0] . "</h3>";
            print "<p>" . $GLOBALS['LANG']['COMPANY']['table_fields'][1] . "</p>";
            $arrBlockedFields = array('id','ordning','deleted','companyid');
            foreach ($GLOBALS['TABLE'] as $table => $tabledata) {
              //print "<b><u>{$table}</u></b><br />";
              print "<h5 class=\"settings\">" . strtoupper($table) . "</h5>";
              foreach ($tabledata['fields'] as $field => $fielddata) {
                //print $field . " : " . $fielddata['type']."<br />";

                if ( isset($fielddata['type']) && in_array($fielddata['type'],$arrBlockedFields) ) continue;

                if ((isset($fielddata['hideable']) && $fielddata['hideable'] == 1) || ( isset($fielddata['type']) &&  $fielddata['type']!="hidden"   ) ) {
                  //print $field."<br />";
                  print "<input type=\"checkbox\" id=\"fields_{$table}_{$field}\" name=\"fields[{$table}][{$field}]\"";
                  if (isset($arrSettings['fields'][$table][$field]) && $arrSettings['fields'][$table][$field] != "" ) {
                    print "checked";
                  }
                  //print $arrSettings['fields'][$table][$field] ? "checked" : "";
                  print " value=\"1\"><b> {$table} - {$field}</b> <br />";
//<label for="vehicle1"> I have a bike</label><br>';
                  //print "<b>{$table}</b> - <b>{$field}</b> kan döljas! <br />";

                }
              }
              // code...
            }
            print $form->form_submit('Spara');
            print $form->form_end();

/*            print "<pre>";
            print_r($GLOBALS['TABLE']);
            print "</pre>";*/






            print "</div>";

          } else {
            print "HÄR!";
          }

        }



    }

}
