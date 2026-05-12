<?php

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
 * Skapar formulär från t.ex. databas, dock skall all struktur vara laddad innan.
 *
 *
 *
 * ATT BYGGA!
 * fieldset funktion - och kolla när slut om fieldset är avslutat
 * använd [fieldset_namn] och används som dpråk ..[tabell][fieldset_namn]
 * skall också kunna ställas in som öppen eller stängd genom t.ex.
 * {fieldset_namn:hide}, standard är alltid öppen. Ska räcka med att klicka
 * på den för att öppna den isf. Allt detta skall sättas i paletten, Denna skall
 * ersätta melwins version när den är klar. Avsluta en formset med ;?
 *
 * @author Martin Tonek <martin@tonek.se>
 */
class form
{

    /**
     * Holds the data values for an edit
     * @var array
     */
    public $data = array(); //Ska vara Private

    private $hasPageOutput = false; // Om man använder ForeignKeyisPage

    /**
     * Holds if a fieldset is open or not
     * @var boolean
     */
    private $fieldsetopen = false;


    private $isSuperUser = false;

    // Bygga om denna så att den stöder COMMON istället!!!
    // 2024-10-16
    private $types = [
        'int' => 'text',
        'varchar' => 'text',
        'char' => 'checkbox',
        'tinyint' => 'checkbox',
        'checkbox' => 'checkbox',
        // 'checkboxtable' => 'checkboxtable',
        'password' => 'password',
        'hidden' => 'hidden',
        'color' => 'color',
        // 'radio' => 'radio',
        // 'date' => 'date',
        // 'time' => 'time',
        // 'datetime' => 'datetime',
    ];


    /**
     * Loads data to set
     * @param array $dbFormSettings Holds the settings
     * @param integer $id The id on the post to load
     * @param string $strKey The field to be the KEY
     */
    function loadData($dbFormSettings, $id, $strKey = 'id')
    {
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__ . " :: " . $id;
        $db = db::getInstance();
        if (isset($dbFormSettings['form']['excl_CID'])) {
            if ($dbFormSettings['form']['excl_CID']) {
                $strCID = "";
            } else {
                $strCID = " AND companyid=" . getCompanyID();
            }
        } else {
            $strCID = " AND companyid=" . getCompanyID();
        }
        // print "<pre>";
        // //print "SELECT * FROM " . $dbFormSettings['table'] . " WHERE {$strKey}='{$id}' " . $strCID . " AND deleted=0";
        // //        print $strCID."\n";
        // print_r($dbFormSettings);
        // print "</pre>";
        //print "SELECT * FROM [table] WHERE {$strKey}='{$id}' " . $strCID . " AND deleted=0";

        // Build SQL here
        /*        $strSQL = "SELECT * FROM [table] WHERE {$strKey}=";
        $strSQL.= "'{$id}'";
        $strSQL.= " " . $strCID . " AND deleted=0";


        print $strSQL."<br />";
        print $dbFormSettings['table'];*/
        $this->data = $db->o_get_row($dbFormSettings['table'], "SELECT * FROM [table] WHERE {$strKey}='{$id}' " . $strCID . " AND deleted=0");



        /*        print "<pre>";
        print_r($this->data);
        print "</pre>";*/
        //print time(); // 1624258844
    }

    /**
     * Sets the data with an array manual
     * @param array $arrData The data to set
     */
    function setData($arrData)
    {
        $this->data = $arrData;
    }

    function get_onload_callback($dbFormSettings) {
      // get Data IN here somewehere
      if (isset($dbFormSettings['form']['onload_callback'])) {
        $callback = $dbFormSettings['form']['onload_callback'];
        // print "IS SET!";
        $class = new $callback[0]();
        // $oldFields = $arrFields;
        // Lägger till palette stöd här!
        // $arrFields['form']['palette'] = $dbFormSettings['form']['palette'];
        // PHP 7 Fix
        // pre($this->data);
        $this->data = call_user_func(array($class, $callback[1]), $this->data);
        // $arrFields = call_user_func(array($class, $callback[1]), $arrFields);
        // $arrFields = $class->$callback[1]($arrFields);
        // 2015-03-29 - Kolla om fel t.ex. krock vid bokning?
        // if (empty($arrFields)) {
        //   setMsg("e", $GLOBALS['LANG']['FORMCATCHER']['error_callback']);
        //   return '';
        // }
        // if (isset($arrFields['bolFailed']) && $arrFields['bolFailed'] == true) {
        //   return;
        // }

        // // Jämför palette
        // if ($arrFields['form']['palette'] != $dbFormSettings['form']['palette']) {
        //   $dbFormSettings['form']['palette'] = $arrFields['form']['palette'];
        // }

      }
    }


    /**
     * Skapar forumlärfälten UTAN att vara beroende av databas och tabell
     *
     * @param type $dbFormSettings
     */
    function MakeFields($dbFormSettings, $return = false, $bolReportError = false)
    {
        $this->isSuperUser = isSuperUser();
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__;
        //
        //        print "<pre>";
        //        print_r($dbFormSettings);
        //        print "</pre>";

        $newtabledata = $dbFormSettings['fields'];
        // Vad händer om form->palette är satt?
        /**
         * Här måste stöd för palettens fieldset fixas.
         */
        $output = "";
        if (isset($dbFormSettings['form']['palette']) && !is_bool($dbFormSettings['form']['palette'])) {
            $palette = $dbFormSettings['form']['palette'];
            if (strpos($palette, ",") == true) {
                $arrFieldOrder = explode(",", $palette);
            } else {
                $arrFieldOrder[] = $palette;
            }
        } else {
            $arrFieldOrder = array_keys($newtabledata);
        }
        $load_cb = $this->get_onload_callback($dbFormSettings);
        foreach ($arrFieldOrder as $key) {
            // Sätter intern namnet
            $newtabledata[$key]['name'] = $key;
            if (!isset($newtabledata[$key]['field'])) {
                $newtabledata[$key]['field'] = $key;
            }
            // För över värdet
            $value = $newtabledata[$key];
            // Fixar $typen
            // 2015-03-28 - Fixar efter ny version
            $type = strtolower($value['type']);

            // Kollar inställningar
            if (isset($value['caption'][0])) {
                $caption = $value['caption'][0];
            } else {
                $caption = "({$key})";
            }
            if ($bolReportError) {
                $arrFieldsRequired = array('type', 'field', 'caption');
                foreach ($arrFieldsRequired as $strRequiredField) {
                    if ($newtabledata[$key][$strRequiredField] == "" && $this->isSuperUser) {
                        print __CLASS__ . " : " . __FUNCTION__ . "@LINE:" . __LINE__ . " - Missing {$strRequiredField}@{$key}<br>";
                    }
                }
            }

            if ($type != 'hidden') {

                $output .= "<div class=\"";
                if (isset($value['eval']['div_class']) && $value['eval']['div_class'] != '') {
                    $output .= $value['eval']['div_class'];
                } else {
                    $output .= ($type == 'radio') ? "w100pct" : "w50pct";
                }
                $output .= " div_" . $this->cleanField($value['field']);
                $output .= ($type == 'radio') ? " radioh" : "";
                $output .= "\">\n";
            }

            // Här skapas fältena till formuläret
            if ($type == 'int' || $type == 'varchar') {
                $output .= $this->field_INPUT($newtabledata[$key]);
            } elseif ($type == 'char' || $type == 'tinyint' || $type == 'checkbox') {
                $output .= $this->field_CHECKBOX($newtabledata[$key]);
            } elseif ($type == 'checkboxtable') {
                $output .= $this->field_CHECKBOXTABLE($newtabledata[$key]);
            } elseif ($type == 'password') {
                $output .= $this->field_PASSWORD($newtabledata[$key]);
            } elseif ($type == 'hidden') {
                $output .= $this->field_HIDDEN($newtabledata[$key]);
            } elseif ($type == 'color') {
                $output .= $this->field_COLOR($newtabledata[$key]);
            } elseif ($type == 'select') {
                $output .= $this->field_SELECT($newtabledata[$key]);
            } elseif ($type == 'file') {
                $output .= $this->field_FILE($newtabledata[$key]);
            } elseif ($type == 'radio') {
                //                print "RADIO";
                $output .= "<label for=\"db_{$key}\">{$caption}</label>\n";
                // Kollar options först
                if (!isset($value['options'])) {
                    $value['options'] = $this->getOptions($value);
                }

                if (isset($value['options'])) {
                    $xx = 0;
                    foreach ($value['options'] as $okey => $ovalue) {
                        $output .= "<input type=\"radio\" name=\"db_{$key}\" id=\"db_{$key}_{$xx}\" value=\"" . $okey . "\"";
                        if ($value['value'] == $okey) {
                            $output .= ' CHECKED';
                        }
                        $output .= "> " . $ovalue . "<br>\n";
                        $xx++;
                    }
                }
            } elseif ($type == 'date') {
                $output .= $this->field_DATE($newtabledata[$key]);
            } elseif ($type == 'time') {
                $output .= $this->field_TIME($newtabledata[$key]);
            } elseif ($type == 'datetime') {
                $output .= $this->field_DATETIME($newtabledata[$key]);
            } elseif ($type == 'textarea' || $type == 'text') {
                $output .= $this->field_TEXTAREA($newtabledata[$key]);
            } elseif ($this->isSuperUser) {
                //var_dump($value);
                $output .= __LINE__ . " NOT DEFINED!! ({$key}) WITH TYPE ({$type})<br>";
            }
            if ($type != 'hidden') {
                $output .= "</div>\n";
            }
        }
        // And let it out!!
        if ($return == true) {
            return $output;
        } else {
            print $output;
        }
    }



    /**
     * Skapar ett formulär med hjälp av den array med indata som kommer in till
     * denna SAMT hämtar aktuell tabell från DB
     *
     * @param array $dbFormSettings Innehåller all data som krävs för att bygga ett formulär
     * @param boolean $return Anger om du vill att formuläret skall skrivas ut eller skickas som svar tillbaka
     * @param boolean $notCompleteForm Skapar inte start, slut samt skicka knapp för formulär
     * @return string
     */
    function MakeWithSettings($dbFormSettings, $return = false, $notCompleteForm = false)
    {
        // [palette] => id,bus,startdatex,stopdatex,startdate,starttime,stopdate,stoptime,prel,resan,chauffor_id,chaffor,reseledare,kommentar,service,chbekr,ts,visible,confirmed,companyid,last_edit,last_edit_time,{customer_legend},customer,customer_contact,customer_phone,customer_email,;,{runorder_legend},antal,avgang_from,framkord,avgang_time,target,home_time,ovrigt,kalk_tim,kalk_vante,kalk_ob,kalk_ob_kval,kalk_ob_stor,kalk_trakt,kalk_km,km_start,km_stop,km_totalt,driver_comment,;
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__;
        $this->isSuperUser = isSuperUser();
        $output = "";
        if ($dbFormSettings['table'] != "") {
            $table = $dbFormSettings['table'];
            //         print $table;
            $db = db::getInstance();
            // 2015-03-28 Byte här!!!!
            $tabledata = $db->o_list_fields($table, 1);

            // Varför behövs denna?
            $auth = auth::getInstance();
            // $arrCompanySettings = getCompanySettings();
            $arrCompanySettings = settings::getSettings();

            //$isSuperUser = filter_var($auth->isSuperUser(), FILTER_VALIDATE_BOOLEAN);
            //        print "<pre>";
            //        print_r($_SESSION);
            //        print "</pre>";
            //'eval'                    => array('mandatory'=>true, 'maxlength'=>255)


            foreach ($tabledata as $key => $value) {
                $newtbl[$value['name']] = $value;
            }
            $tabledata = $newtbl;
            /*if ($isSuperUser) {
                print "<pre>";
                print_r($tabledata);
                print "</pre>";
        }*/
            /*print "<pre>";
        print_r($this->data);
        print "</pre>";*/

            $newtabledata = array_change_key_case_recursive($tabledata, CASE_LOWER);
        } else {
            $newtabledata = array();
        }
        $newtabledata = array_merge_own($newtabledata, $dbFormSettings['fields']);

        $this->get_onload_callback($dbFormSettings);
        // Set maxlength automatic here
        foreach ($newtabledata as $nkey => $nvalue) {
            if ($nvalue['type'] == "varchar") {
                //var_dump($nvalue['length']);
                //var_dump($newtabledata[$nkey]['eval']['maxlength']);
                if (isset($nvalue['length'])) {
                    $newtabledata[$nkey]['eval']['maxlength'] = $nvalue['length'];
                } else {
                    $newtabledata[$nkey]['eval']['maxlength'] =  null;
                    //              $newtabledata[$nkey]['eval']['maxlength'] = $nvalue['length'] ? null;
                }
                //var_dump($nvalue['length']);
                //var_dump($newtabledata[$nkey]['eval']['maxlength']);
                //print "################";
            }
        }
        /*        if ($isSuperUser) {
                print "<pre>NEWTABLEDATA\n";
                print_r($newtabledata);
                print "</pre>";
        }*/
        // Set values if exist in array
        //print "#&gt;" .$this->data."#";
        if (is_array($this->data) && count($this->data) >= 1) {
            foreach ($this->data as $key => $value) {
                $newtabledata[$key]['value'] = $value;
            }
        }
        /*
         *  Fixa med nya funktionen här.
         * Nytt värde i tabelldata [form][url] som ersätter denna under!
         */
        /*        print "<pre>";
        print_r($dbFormSettings);
        print "</pre>";*/
        if (!empty($dbFormSettings['form']['url'])) {
            $arrUrl = $dbFormSettings['form']['url'];
        } else {
            $arrUrl = array('action' => 'save');
        }

        $name = "frm_{$table}";
        $output .= ($notCompleteForm) ? "" : "<form action=\"" . getURL($arrUrl) . "\" id=\"{$name}\" name=\"{$name}\" method=\"POST\">\n";

        //Security
        $output .= "<input type=\"hidden\" name=\"table\" id=\"table\" value=\"" . $table . "\">\n";
        if (isset($newtabledata['id']['value'])) {
            $id = $newtabledata['id']['value'];
        } else {
            $id = "";
        }
        $output .= $this->createHash($table, $id);
        //        $output.= "<input type=\"hidden\" name=\"hash\" id=\"hash\" value=\"" . salt( $table . date("Y-m-d") . $id ) . "\">\n";

        // Vad händer om form->palette är satt?

        /**
         * Här måste stöd för palettens fieldset fixas.
         */

        if (isset($dbFormSettings['form']['fieldsets'])) {
            $arrFieldsets = $dbFormSettings['form']['fieldsets'];

            //        print "<pre>";
            //        print_r($arrFieldsets);
            //        print "</pre>";


        } else {
            $arrFieldsets = array();
        }

        /*print "<pre>";
        print "#".$dbFormSettings['form']['palette']."#";
        var_dump($dbFormSettings['form']['palette']);
        print "</pre>";*/

        // Är paletten satt?
        if (isset($dbFormSettings['form']['palette']) && strlen($dbFormSettings['form']['palette']) > 0) {
            $palette = $dbFormSettings['form']['palette'];
            if (strpos($palette, ",") == true) {
                $arrFieldOrder = explode(",", $palette);
                // Annars läggs den till som den är
            } else {
                $arrFieldOrder[] = $palette;
            }
            // 20231207 - BUGFIX med menumaker och tomt fält
        } elseif (isset($GLOBALS['TABLE'][$table]['form']['palette'])) {
            $palette = $GLOBALS['TABLE'][$table]['form']['palette'];
            if (strpos($palette, ",") == true) {
                $arrFieldOrder = explode(",", $palette);
                // Annars läggs den till som den är
            } else {
                $arrFieldOrder[] = $palette;
            }
            // Annars kör vi den direkt
        } else {
            $arrFieldOrder = array_keys($newtabledata);
        }




        /*print "<pre>";
        //print_r($newtabledata);
        print_r($arrFieldOrder);
        print "</pre>";*/

        /*print "<pre>";
        print_r($newtabledata);
        print "</pre>";*/

        // __LINE__ ." X<br />";

        foreach ($arrFieldOrder as $key) {
            // Sätter intern namnet
            $newtabledata[$key]['name'] = $key;
            $newtabledata[$key]['field'] = $key;
            // För över värdet
            $value = $newtabledata[$key];
            // Fixar $typen
            // 2015-03-28 - Fixar efter ny version
            if (isset($value['type'])) {
                $type = strtolower($value['type']);
            }

            // 2023-03-23
            // Kollar om något fält ska döljas
            if (isset($arrCompanySettings['fields'][$table][$key]) && $arrCompanySettings['fields'][$table][$key] == 1) {
                //              $newtabledata[$key]['type'] = 'hidden';
                $type = 'hidden';
            }
            //  print "$key [$type]<br />";

            // Kollar inställningar - LABELS HÄR!
            // Kollar om de saknas in, om så hämtas de globala om de finns!
            // Annars lägggs det till att de måste skapas
            /*print "<pre>";
            var_dump($value['caption']);
            print "</pre>";*/
            if (!isset($value['caption'])) {
                //print $key." - ";
                //var_dump($this->isSuperUser);
                if (isset($GLOBALS['LANG'][$table][$key])) {
                    //print "Fanns";
                    $value['caption'] = $GLOBALS['LANG'][$table][$key];
                    $newtabledata[$key]['caption'] = $GLOBALS['LANG'][$table][$key];
                } elseif ($this->isSuperUser) {

                    if (!str_contains($key, '{') && !str_contains($key, ';')) {
                        //if ( strpos($key, '{') !== false || strpos($key, ';') !== false ) {
                        print "\$GLOBALS['LANG']['$table']['$key'] = array('','');<br>";
                    } elseif (str_contains($key, '{') && !str_contains($key, ';')) {
                        $newkey = str_replace(array("{", "}"), array(""), $key);
                        if (!isset($GLOBALS['LANG'][$table][$newkey])) {
                            print "\$GLOBALS['LANG']['$table']['$newkey'] = \"\";<br>";
                        }
                    }
                }
                //print "<br />";
            }

            if (isset($value['caption'][0])) {
                $caption = $value['caption'][0];
            } else {
                $caption = "({$key})";
            }

            //            // 20170325 - Kollar missade variabler
            //            if (!isset($GLOBALS['LANG'][$table][$key]) && $this->addLabels) {
            //                print "\$GLOBALS['LANG']['$table']['$key'] = array('','');<br>";
            //            }


            // Här lägger vi in för fieldsets
            // Öppnar


            // 2023-08-22 Kan det vara denna som buggar?
            //            if (strpos($key, '{') !== true && strpos($key, ';') !== true && $type !== 'hidden') {
            //if (strpos($key, '{') !== true && strpos($key, ';') !== true) {
            if (strpos($key, '{') !== true && $key !== ";" && $type !== 'hidden') {
                //$output.= "<div class=\"w50\">\n";

                $output .= "<div class=\"form_field ";
                if (isset($value['eval']['div_class']) && $value['eval']['div_class'] != '') {
                    $output .= $value['eval']['div_class'];
                } else {
                    $output .= "w100";
                    //                $output.= ($type == 'radio')? "w100pct":"w50pct";
                }
                $output .= " div_" . $this->cleanField($value['field']);
                $output .= ($type == 'radio') ? " radioh" : "";
                $output .= "\">\n";
            }





            // Check for callback for value and change it
            // 2024-10-16
            if (isset($newtabledata[$key]['form_callback_value'])) {
                $callback = $newtabledata[$key]['form_callback_value'];
                $callback_cls = new $callback[0]();
                $newtabledata[$key]['value'] = call_user_func(array($callback_cls, $callback[1]), $newtabledata[$key]); // $value
            }

            // pre($newtabledata[$key]);
            if (strpos($key, '{') !== false) {
                $output .= $this->fieldsetStart($arrFieldsets[$key]);
                // Stänger
                // 20230822 Kollar om detta är buggen.
            } elseif ($key == ';') {
                //print "YO!";
                //          } elseif (strpos($key, ';') !== false) {
                $output .= $this->fieldsetClose();
                // Här skapas fältena till formuläret

            } elseif (isSuperUser() && isset($this->types[$type]) && 1 == 2) { //
                $newtabledata[$key]['type'] = $this->types[$type];
                $output .= $this->field_COMMON_NEW($newtabledata[$key]);
            } elseif ($type == 'int' || $type == 'varchar') {
                $output .= $this->field_INPUT($newtabledata[$key]);
            } elseif ($type == 'char' || $type == 'tinyint' || $type == 'checkbox') {
                $output .= $this->field_CHECKBOX($newtabledata[$key]);
            } elseif ($type == 'checkboxtable') {
                $output .= $this->field_CHECKBOXTABLE($newtabledata[$key]);
            } elseif ($type == 'password') {
                $output .= $this->field_PASSWORD($newtabledata[$key]);
            } elseif ($type == 'hidden') {
                $output .= $this->field_HIDDEN($newtabledata[$key]);
            } elseif ($type == 'color') {
                $output .= $this->field_COLOR($newtabledata[$key]);
            } elseif ($type == 'file') {
                $output .= $this->field_FILE($newtabledata[$key]);
            } elseif ($type == 'media') {
                $output .= $this->field_MEDIA_LIBRARY($newtabledata[$key]);                
            } elseif ($type == 'select') {
                $output .= $this->field_SELECT($newtabledata[$key]);
                //                $output.="<label for=\"db_{$key}\">{$caption}</label>\n";
                //                // Kollar options först
                //                if (!isset($value['options'])) {$value['options'] = $this->getOptions($value);}
                //                $output.= "<select name=\"db_{$key}\" class=\"\" ";
                //                $output.=$value['eval']['readonly']? "disabled":"";
                //                $output.= "/>";
                //                if (isset($value['options']))
                //                {
                //                    foreach ($value['options'] as $okey=>$ovalue) {
                //                        $output.= "<option value='" . $okey . "'";
                //                        if ($value['value']==$okey) {$output.=' SELECTED';}
                //                        $output.= ">" . $ovalue . "</option>\n";
                //                    }
                //                }
                //                $output.= "</select>";

            } elseif ($type == 'radio') {
                // pre($newtabledata);
                // Flytta denna till $this->field_RADIOGROUP ?
                 $output .= $this->field_RADIOGROUP($newtabledata[$key]);
                // Kollar options först
                if (!isset($value['options'])) {
                    $value['options'] = $this->getOptions($value);
                }
                // FIX 20250520 - compare
                // if (isset($value['options'])) {
                //     // $output.="<label for=\"db_{$key}\">{$caption}</label>\n";
                //     $output .= "<fieldset>\n";
                //     $output .= "<legend>{$caption}</legend>\n";
                //     $xx = 0;
                //     foreach ($value['options'] as $okey => $ovalue) {
                //         $output .= "<input type=\"radio\" name=\"db_{$key}\" id=\"db_{$key}_{$xx}\" value=\"" . $okey . "\""; //
                //         if ($value['value'] == $okey) {
                //             $output .= ' CHECKED';
                //         }
                //         $output .= !is_null($required) ? " required" : "";
                //         $output .= ">";
                //         $output .= "<label for=\"db_{$key}_{$xx}\">{$ovalue}</label>\n";
                //         $output .= "<br>\n";
                //         $xx++;
                //     }
                //     $output .= "</fieldset>\n";
                //     // foreach ($value['options'] as $okey=>$ovalue) {
                //     //     $output.="<input type=\"radio\" name=\"db_{$key}\" id=\"db_{$key}_{$xx}\" value=\"" . $okey . "\""; //
                //     //     if ($value['value']==$okey) {$output.=' CHECKED';}
                //     //     $output.="> " . $ovalue . "<br>\n";
                //     //     $xx++;
                //     // }



                // }
            } elseif ($type == 'date') {
                $output .= $this->field_DATE($newtabledata[$key]);
            } elseif ($type == 'time') {
                $output .= $this->field_TIME($newtabledata[$key]);
            } elseif ($type == 'datetime') {
                $output .= $this->field_DATETIME($newtabledata[$key]);
            } elseif ($type == 'textarea' || $type == 'text') {
                $output .= $this->field_TEXTAREA($newtabledata[$key]);
            } elseif ($type == 'fileupload') {
                $output .= "FILE UPLOAD HERE"; //$this->field_TEXTAREA($newtabledata[$key]);



            } elseif ($type == 'custom') {
                //print "<pre>";
                //print_r($newtabledata[$key]);
                //print "</pre>";
                $output .= "<!-- form@" . __LINE__ . " -->";
            } elseif ($this->isSuperUser) {
                //var_dump($value);
                //$output.= "@" . __LINE__ . " NOT DEFINED!! ({$key}) WITH TYPE ({$type})<br> - " . var_dump($newtabledata[$key]) . "<br />";
                print "<pre>";
                print "@" . __LINE__ . "[" . __CLASS__ . "] NOT DEFINED!! ({$key}) WITH TYPE ({$type}) - \n";
                                $txtType = "";
                if ($type == 'blob') {
                    $txtType = "textarea";
                }
                // Bygg smart här istället..
                print "
                '{$key}' => array(
                    'type'     => '{$txtType}',
                    'caption'  => \$GLOBALS['LANG']['']['{$key}']
                ),\n";
                print "\n=================================================\n";
                var_dump($newtabledata[$key]);
                print "</pre>";
            }

            // 20241007 Form callback for sure
            // Bör inte denna vara en bearbetning av value t.ex. ???
            if (isset($newtabledata[$key]['form_callback'])) {
                $callback = $newtabledata[$key]['form_callback'];
                $callback_cls = new $callback[0]();
                $output .= call_user_func(array($callback_cls, $callback[1]), $newtabledata[$key]); // $value
            }
            //var_dump($key);
            //if (substr($key, 0, 1) === '{') { print "{$key}: YOHA!"; }
            if (substr($key, 0, 1) !== "{" && $key !== ";" && $type !== 'hidden') {
                //print "{$key}: YOHA!";
                $output .= "</div>\n";
            }

            /*if (strpos($key, '{') !== true && strpos($key, ';') !== true && $type !== 'hidden') {
              $output.= "</div>\n";
            }*/
        }
        //print __LINE__ ." X<br />";

        //        foreach ($newtabledata as $key => $value) {
        //            $newtabledata[$key]['name'] = $key;
        //            list($type) = explode('(', $value['type']);
        //            $type = strtolower($type);
        //
        //            // Kollar inställningar
        //            if (isset($value['caption'][0])) {
        //                $caption = $value['caption'][0];
        //            } else {
        //                $caption = "({$key})";
        //            }
        //
        //            if ($type == 'int' || $type == 'varchar') {
        //                $output.="<label for=\"db_{$key}\">{$caption}</label>\n";
        //                $output.= "<input type=\"text\" name=\"db_{$key}\" value=\"" . $this->data[$key] . "\">\n";
        //                $output.= "<p class=\"small\">" . $value['caption'][1] . "</p>";
        //            } elseif ($type == 'char' || $type == 'tinyint' || $type == 'checkbox' ) {
        //
        //                $output.= "<input type=\"checkbox\" name=\"db_{$key}\" value=\"1\"";
        //                $output.= intval($this->data[$key]) ? " CHECKED " : "";
        //                $output.= ">{$caption}<br>\n";
        //            } elseif ($type == 'hidden') {
        //                $output.= "<input type=\"hidden\" name=\"db_{$key}\" value=\"" . $this->data[$key] . "\">\n";
        //            } elseif ($type == 'color') {
        //                $output.="<label for=\"db_{$key}\">{$caption}</label>\n";
        //                $output.= "<input type=\"color\" name=\"db_{$key}\" value=\"" . $this->data[$key] . "\"><br>\n";
        //            } elseif ($type == 'select') {
        //                $output.="<label for=\"db_{$key}\">{$caption}</label>\n";
        //                // Kollar options först
        //                if (!isset($value['options'])) {$value['options'] = $this->getOptions($value);}
        //                $output.= "<select name=\"db_{$key}\" class=\"\" ";
        //                $output.=$value['eval']['readonly']? "disabled":"";
        //                $output.= "/>";
        //                if (isset($value['options']))
        //                {
        //                    foreach ($value['options'] as $okey=>$ovalue) {
        //                        $output.= "<option value='" . $okey . "'";
        //                        if ($value['value']==$okey) {$output.=' SELECTED';}
        //                        $output.= ">" . $ovalue . "</option>\n";
        //                    }
        //                }
        //                $output.= "</select>";
        //
        //            } elseif ($type == 'radio') {
        //                $output.="<label for=\"db_{$key}\">{$caption}</label>\n";
        //                // Kollar options först
        //                if (!isset($value['options'])) {$value['options'] = $this->getOptions($value);}
        //
        //                if (isset($value['options']))
        //                {
        //                    foreach ($value['options'] as $okey=>$ovalue) {
        //                        $output.="<input type=\"radio\" name=\"db_{$key}\" value=\"" . $okey . "\"";
        //                        if ($value['value']==$okey) {$output.=' CHECKED';}
        //                        $output.="> " . $ovalue . "<br>\n";
        //                    }
        //                }
        //            } elseif ($type == 'date') {
        //                $output.= $this->field_DATE($newtabledata[$key]);
        //
        //            } elseif ($type == 'textarea' || $type == 'text') {
        //                $output.= $this->field_TEXTAREA($newtabledata[$key]);
        //
        //            } else {
        //                $output.= "NOT DEFINED!! ({$key}) WITH TYPE ({$type})<br>";
        //            }
        //        }
        // Kör en säkerhetskoll om fieldsets lämnats öppen
        $output .= $this->fieldsetClose();

        // 2023-03-13 - La till så att man automatiskt kan spara fieldsets läge.
        if (isset($dbFormSettings['form']['fieldsets'])) {
            $output .= "
          <script>\n
          window.addEventListener('load', function () {\n";
            foreach ($dbFormSettings['form']['fieldsets'] as $key => $value) {
                $output .= "\tcheckLegends('" . $value['name'] . "','" . $value['caption'] . "');\n";
            }
            //checkLegends('customer_legend');
            //checkLegends('runorder_legend');
            $output .= "});\n
          </script>\n";
        }



        if (isset($dbFormSettings['form']['caption'])) {
            $caption = $dbFormSettings['form']['caption'];
        } else {
            $caption = $GLOBALS['LANG']['FORM']['submit'];
        }

        // Om man vill ange egen caption då?
        $output .=  ($notCompleteForm) ? "" : "<input type=\"submit\" value=\"{$caption}\">\n";
        $output .=  ($notCompleteForm) ? "" : "</form>\n";

        /*        print "<pre>";
        print_r($dbFormSettings['form']['fieldsets']);
        print "</pre>";*/



        // And let it out!!
        if ($return == true) {
            return $output;
        } else {
            print $output;
        }
    }

    function createHash($table = '', $id = '')
    {
        return "<input type=\"hidden\" name=\"hash\" id=\"hash\" value=\"" . salt($table . date("Y-m-d") . $id) . "\">\n";
    }

    // How to make an optGroup?
    /**
     * Undocumented function
     *
     * 
     * 
     * <select name="cars" id="cars">
     *     <optgroup label="Swedish Cars">
     *       <option value="volvo">Volvo</option>
     *       <option value="saab">Saab</option>
     *     </optgroup>
     *     <optgroup label="German Cars">
     *       <option value="mercedes">Mercedes</option>
     *       <option value="audi">Audi</option>
     *     </optgroup>
     *   </select>
     * 
     * 
     * 
     * @param [type] $data
     * @return void
     */
    function getOptions($data)
    {
        // pre($data);
        //        print "<pre>";
        //        print_r($data);
        //        print "</pre>";
        $arrAnswer = array();
        if ($data['type'] == 'select') {
            $arrOptions = array('-' => ' - Inget Valt - ');
        }

        // DB alternativet
        if (isset($data['db']) && is_array($data['db'])) {
            //print "DB";
            if (isset($data['db']['where'])) {
                $where = " WHERE " . $data['db']['where'] . " AND deleted=0";
            } else {
                " WHERE deleted=0";
            }
            if (isset($data['db']['order'])) {
                $order = " ORDER BY " . $data['db']['order'];
            }
            $db = db::getInstance();
            $ret = $db->o_get_all($data['db']['db'], "SELECT * FROM [table] " . $where . $order); //" . $data['db']['id'] . ", " . $data['db']['value'] . "
            $theKey = $data['db']['id'];
            $theValue = $data['db']['value'];

            foreach ($ret as $key => $value) {
                //                print $value[$theValue]."<br>";
                $arrOptions[$value[$theKey]] = $value[$theValue];
            }

            return (array) $arrOptions;
        }

        /**
         * Needs two array keys
         * 0 => the optGroup key
         * 1 => the foreignkey with a third option to get the key for options
         */
        if (isset($data['optGroupWithKey']) && count($data['optGroupWithKey']) == 2) {
            // pre($data['optGroupWithKey']);
            list($strTableGroup, $strFieldGroup) = explode(".", $data['optGroupWithKey'][0]);
            list($strTable, $strField, $strKeyField) = explode(".", $data['optGroupWithKey'][1]);
            $db = db::getInstance();

            $arrAnswer = $db->o_get_all($strTable, "SELECT a.id, a.{$strField}, b.{$strFieldGroup} as optGroup FROM [table] as a LEFT JOIN " . $db->o_prefix($strTableGroup) . " as b ON b.id = a.{$strKeyField} WHERE a.deleted=0 AND ( a.companyid=" . getCompanyID() . " OR a.companyid=0 ) ORDER BY b.{$strFieldGroup} ASC, a.{$strField} ASC"); //, showDebug: true
            // pre($arrAnswer);
            // die();
            /*print "<pre>";
            print_r($arrAnswer);
            print "</pre>";*/
            $strGroup = "";
            $intGroup = 0;
            foreach ($arrAnswer as $key => $value) {
                if ($strGroup <> $value['optGroup']) {
                    $arrOptions['optGroup_' . $intGroup++] = $value['optGroup'];
                    $strGroup = $value['optGroup'];
                }
                $arrOptions[$key] = $value[$strField];
            }
            // pre($arrOptions);
            return (array) $arrOptions;
        }

        // foreignKey
        if (isset($data['foreignKey']) || isset($data['foreignkey'])) { //  && is_array($data['foreignKey'])
            //print "foreignKey är satt";
            //print "foreignKey";
            //foreignKeySort
            if (isset($data['foreignKey'])) {
                $foreignKey = $data['foreignKey'];
            } elseif (isset($data['foreignkey'])) {
                $foreignKey = $data['foreignkey'];
            }

            // BUG: från am
            // if (substr_count($foreignKey, ".") > 2) {
            //     list($strTable, $strField, $strExtra) = explode(".", $foreignKey);
            // } else {
            //     list($strTable, $strField) = explode(".", $foreignKey);
            // }
            // print "Tbl: $strTable    Fld: $strField <br>";



            preg_match_all("/\./", $foreignKey, $matches);
            $totMatches = count($matches[0]);
            //            if (isset($data['foreignkey'])) { $foreignKey = $data['foreignkey'];}
            if ($totMatches == 1) {
                list($strTable, $strField) = explode(".", $foreignKey);
                // $strExtra
            } elseif ($totMatches == 2) {
                list($strTable, $strField, $strExtra) = explode(".", $foreignKey);
            } else {
                print "ERROR IN " . __CLASS__ . ":" . __FUNCTION__ . "@" . __LINE__;
            }
            // list($strTable, $strField, $strExtra) = explode(".", $foreignKey);
            // print "Tbl: $strTable    Fld: $strField <br>";
            $db = db::getInstance();
            $GLOBALS['DEBUG'][] = "NU GRUPPER:";
            // 20231208 - Fixar med extra värde om finns
            $strExtraSQL = "";
            if (isset($strExtra) && $strExtra <> "") {
                $strExtraSQL = ", {$strExtra}";
            }
            // print $strExtraSQL;


            // 20170307 - BUGG HITTAD!!! companyid används ej på 'page' t.ex.
            // La till OR companyid = 0 för att se om det fungerar!
            // Hur bygga in funktionen med PID?
            // Kolla sort key?
            // foreignKeyisPage
            if (isset($data['foreignKeyisPage'])) {
                $arrOptions = array('0' => ' - Inget Valt - ');
                $arrAnswer = $db->o_get_all($strTable, "SELECT id, " . $strField . ",pid FROM [table] WHERE deleted=0 AND ( companyid=" . getCompanyID() . " OR companyid=0 ) ORDER BY pid ASC, {$strField} ASC"); //
                /*print "<pre>";
                print_r($arrAnswer);
                print "</pre>";*/
                $arrReturn = $arrOptions + $this->OptionsTree($arrAnswer, 0, 0);
                // pre($arrReturn);
                // if (isSuperUser()) {
                    // foreach ($arrReturn as $key => $value) {
                    //     if ($key == 0) continue;
                    //     print $value;
                        
                    // }
                    if (!$this->hasPageOutput) {
                        $outputData = $this->processCategoryArray($arrReturn);
                        print "<script>";
                        print " const pageLink = '" . json_encode($outputData) . "';";
                        print "</script>";
                        // pre($outputData);
                        $this->hasPageOutput = true;
                    }

                // }




                return (array) $arrReturn;
            } else {
                $arrAnswer = $db->o_get_all($strTable, "SELECT id, " . $strField . $strExtraSQL . " FROM [table] WHERE deleted=0 AND ( companyid=" . getCompanyID() . " OR companyid=0 ) ORDER BY {$strField} ASC"); //
                /*print "<pre>";
                print_r($arrAnswer);
                print "</pre>";*/
                foreach ($arrAnswer as $key => $value) {
                    if (isset($strExtra) && $strExtra <> "" && $value[$strExtra] <> "") {
                        $arrOptions[$key] =  $value[$strField] . " (" . $value[$strExtra] . ")";
                    } else {
                        $arrOptions[$key] = $value[$strField];
                    }
                }
                return (array) $arrOptions;
            }

            /*print "<pre>";
            print_r($arrOptions);
            print "</pre>";*/


            foreach ($arrAnswer as $key => $value) {
                $arrOptions[$key] = $value[$strField];
            }
            return (array) $arrOptions;
        }
        //
        //        // options_db
        //        if (isset($data['options_db']) && is_array($data['options_db'])) { //
        //            $strTable = $data['options_db'][0];
        //            $strKeyField = $data['options_db'][1];
        //            $strValueField = $data['options_db'][2];
        //			$db = new Db();
        //			$db -> dbQuery("SELECT " . $strKeyField . ", " . $strValueField . " FROM ".$GLOBALS['e_CONFIG']['dbPrefix'].$strTable." WHERE deleted=0 ORDER BY id ASC");//
        //			while ($r = $db -> dbRetRow())
        //			{
        //                $arrOptions[$r[$strKeyField]] = $r[$strValueField];
        //            }
        //            return (array) $arrOptions;
        //        }
        //
        //		// Kolla spr�kfil
        //		$arrOptionsx=$GLOBALS['LANG']['DB']['db_'.$data['table']][$data['name']]['ans'];
        //		if (is_array($arrOptionsx))
        //		{
        //			$arrOptions=$arrOptionsx;
        //			return (array) $arrOptions;
        //		}
        //
        //		// Kolla option_callback
        //		if (isset($data['options_callback']))
        //		{
        //			$arrOptions = call_user_func($data['options_callback']);
        //			$GLOBALS['DEBUG'][] = "FORM :: optionA : " .$data['options_callback'];
        //			return (array) $arrOptions;
        //		}
        //		print "<pre>";
        //		print_r($arrOptions);
        //		print "</pre>";
        //		#die();
        //		#return (array) array('ERROR IN OPTIONS!');
        //        return (array) array();
    }




 

private function processCategoryArray(array $inputArray): array
{
    $processedArray = [];
    // Stores the hierarchy of parent names at each level.
    // Example: ['level0_parent', 'level1_child', 'level2_grandchild']
    $currentPathElements = []; 

    // Define character replacements for foreign letters
    // Handles both lowercase and uppercase variations
    $search = ['å', 'ä', 'ö', 'Å', 'Ä', 'Ö'];
    $replace = ['a', 'a', 'o', 'A', 'A', 'O'];

    foreach ($inputArray as $key => $value) {
        $trimmedValue = trim($value);

        // Skip the '0' entry based on its key and value, as per previous instructions
        if ($key === 0 && $trimmedValue === '- Inget Valt -') {
            continue;
        }

        $level = 0; // Default level for top-level items
        $cleanedValue = $trimmedValue;

        // Determine the indentation level and remove the prefix
        // Check for double dash first (level 2)
        if (str_starts_with($trimmedValue, '- - ')) {
            $level = 2;
            $cleanedValue = substr($trimmedValue, 4); // Remove '- - ' (4 characters)
        } 
        // Check for single dash (level 1)
        elseif (str_starts_with($trimmedValue, '- ')) { 
            $level = 1;
            $cleanedValue = substr($trimmedValue, 2); // Remove '- ' (2 characters)
        }
        // For level 0, cleanedValue remains $trimmedValue

        // 1. Replace foreign letters (e.g., åäö with aao)
        // This is done before lowercasing to handle cases like 'Ä' becoming 'A' correctly
        $cleanedValue = str_replace($search, $replace, $cleanedValue);

        // 2. Convert the string to lowercase
        // mb_strtolower is used to correctly handle multi-byte characters, ensuring UTF-8 compliance.
        $cleanedValue = mb_strtolower($cleanedValue, 'UTF-8');
        
        // 3. Replace spaces with underscores
        $cleanedValue = str_replace(' ', '_', $cleanedValue);

        // Adjust the current path based on the detected level
        // This effectively "resets" the path elements from the current level onwards.
        // For example, if moving from a level 2 item to a level 1 item,
        // it removes the level 2 item from currentPathElements.
        array_splice($currentPathElements, $level);
        
        // Add the current cleaned value to its respective level in the path
        $currentPathElements[$level] = $cleanedValue;

        // Construct the full path string for the current item
        // implode joins the relevant path elements with a '/'
        // array_slice ensures only elements up to the current level are included
        $processedArray[$key] = implode('/', array_slice($currentPathElements, 0, $level + 1));
    }

    return $processedArray;
}


    public function OptionsTree($arrData, $intPid, $intLevel)
    {
        if (is_array($arrData)) {
            $arrAnswer = array();
            foreach ($arrData as $value) {
                if ($value['pid'] == $intPid) {
                    $strPrefix = "";
                    for ($index = 0; $index < $intLevel; $index++) {
                        $strPrefix .= "- ";
                    }
                    $arrAnswer[$value['id']] = $strPrefix . $value['label'];
                    //                    $arrAnswer = array_merge($arrAnswer, $this->OptionsTree($arrData, $value['id'], $intLevel+1));
                    $arrAnswer = $arrAnswer + $this->OptionsTree($arrData, $value['id'], $intLevel + 1);
                }
            }
        }
        //                        print "<pre>";
        //                        print "PID: $intPid \n";
        //                print_r($arrAnswer);
        //                print "</pre>";

        return (array) $arrAnswer;
    }


    /*
     *
     * Nedan finns alla olika inmatningsfälts typer
     *
     */
    function field_LABEL($data)
    {
        // Fixar bugg som klagar på fält som inte angetts
        extract(array(
            'nolabel' => '',
        ));
        // Hämtar fält med data
        extract($data);
        if (!isset($caption)) return "";
        return $nolabel ? "" : "<label for=\"db_{$field}\" class=\"fld_db_{$field}\">" . $caption[0] . "</label>\n";
        //return "<label for=\"db_{$field}\">" .$caption[0] . "</label>\n";
    }

    function field_LABELFOOTER($data)
    {
        // Fixar bugg som klagar på fält som inte angetts
        extract(array(
            'nolabel' => '',
        ));
        // Hämtar fält med data
        extract($data);
        if (!isset($caption)) return "";
        return $nolabel ? "" : "<p class=\"small fld_db_{$field}\">" . $caption[1] . "</p>";
        //return "<p class=\"small\">" . $caption[1] . "</p>";
    }

    function field_COMMON_SELECT($data)
    {
        extract($data);

        $id = str_replace(array('][', '__', '[', ']'), '_', $field);
        if (substr($id, -1, 1) == "_") {
            $id = substr($id, 0, -1);
        }
        $id = str_replace("[]", "", $id);

        if (!is_array($caption) || !isset($caption)) {
            $nolabel = true;
        }
        $output = $nolabel ? "" : $this->field_LABEL($data);
        // Kollar options först
        if (!isset($options)) {
            $options = $this->getOptions($data);
        }

        // debug test!! om är page med foreignispage



        $output .= "\t<select name=\"{$field}\" class=\"{$class} {$this->cleanField($field)} fld_db_{$field}\" id=\"{$id}\" ";
        $output .= $eval['readonly'] ? " disabled" : "";
        $output .= $multiple ? " multiple" : "";
        // size
        $output .= ($size != "") ? " size=\"{$size}\"" : "";
        if (isset($eval['onClick'])) {
            //print "hej";
            //        $output.= " onClick='" . $eval['onClick'] . "'";
            $output .= " onChange=\"" . $eval['onClick'] . "\"";
        }

        $output .= "/>\n";

        //        print "<pre>";
        //        print_r($data);
        //        print "<\pre>";


        if (isset($options)) {
            // pre($options);
            foreach ($options as $okey => $ovalue) {
                // var_dump(substr($okey, 0, 8));
                // if (substr($okey, 0, 8) == "optGroup") {
                //     // if (str_starts_with($okey, 'optGroup')) {
                //     $output .= "\t\t<optgroup label=\"{$ovalue}\">\n";
                // } else {
                $output .= "\t\t<option value=\"" . $okey . "\"";
                if ($value == $okey) {
                    $output .= ' SELECTED';
                }
                $output .= ">" . $ovalue . "</option>\n";
                // }
            }
        }
        $output .= "\t</select>\n";
        $output .= $nolabel ? "" : $this->field_LABELFOOTER($data);
        return $output;
    }


    function field_SELECT($data)
    {
        //$output = $this->field_LABEL($data);
        extract($data);
        if (!is_array($caption) || !isset($caption)) {
            $nolabel = true;
        }


        if (isset($nolabel) && $nolabel == true) {
            $output = "";
        } else {
            $output = $this->field_LABEL($data);
        }
        //$output = $nolabel ? "" : $this->field_LABEL($data);
        // Kollar options först
        if (!isset($options)) {
            $options = $this->getOptions($data);
        }
        if (!isset($class)) {
            $class = "";
        }
        $output .= "\t<select name=\"db_{$field}\" class=\"{$class} {$this->cleanField($field)} fld_db_{$field}\" id=\"db_{$field}\" ";
        //if (isset($eval['readonly']))
        $output .= isset($eval['readonly']) ? " disabled" : "";
        $output .= isset($multiple) ? " multiple" : "";
        // size
        if (isset($size) && $size != "") {
            $output .= " size=\"{$size}\"";
        }
        //$output.= ($size!="") ? " size=\"{$size}\"" : "";
        if (isset($eval['onClick'])) {
            //print "hej";
            //        $output.= " onClick='" . $eval['onClick'] . "'";
            $output .= " onChange='" . $eval['onClick'] . "'";
        }

        $output .= "/>";

        //        print "<pre>";
        //        print_r($data);
        //        print "<\pre>";



        if (isset($options)) {

            // pre($options);
            foreach ($options as $okey => $ovalue) {
                // var_dump(substr($okey, 0, 8));
                if (substr($okey, 0, 8) == "optGroup") {
                    // if (str_starts_with($okey, 'optGroup')) {
                    $output .= "\t\t<optgroup label=\"{$ovalue}\">\n";
                } else {
                    $output .= "\t\t<option value=\"" . $okey . "\"";
                    if (isset($value) && $value == $okey) {
                        $output .= ' SELECTED';
                    }
                    $output .= ">" . $ovalue . "</option>\n";
                }
            }

            // foreach ($options as $okey => $ovalue) {
            //     $output .= "\t\t<option value=\"" . $okey . "\"";
            //     if (isset($value) && $value == $okey) {
            //         $output .= ' SELECTED';
            //     }
            //     $output .= ">" . $ovalue . "</option>\n";
            // }
        }
        $output .= "\t</select>\n";
        if (isset($nolabel) && $nolabel == true) {
            $output .= "";
        } else {
            $output .= $this->field_LABELFOOTER($data);
        }
        //$output.= $nolabel ? "" : $this->field_LABELFOOTER($data);
        return $output;
    }

    function field_INPUT($data)
    {
        // Fixar bugg som klagar på fält som inte angetts
        extract(array(
            'nolabel' => '',
            'class' => '',
            'value' => '',
            'type' => 'text',
            'eval' => NULL
            // 'eval' => array('readonly' => '')
        ));
        // Hämtar fält med data
        extract($data);
        // 2024-10-15 - Tagit bort denna, varför här? samt ovan i extract
        // if (!isset($eval['readonly'])) $eval['readonly'] = '';
        $type = 'text';
        if (isset($eval['digits'])) $type = 'number';
        /*print "<pre>";
        print_r($data);
        print "</pre>";*/
        /*
         * <input
         *  class="product-selector product_name"
         *  data-autocomplete="/products/auto_complete.json"
         *  data-empty-text="Klicka på produktlådan för att spara produkten"
         *  id="invoice_rows_attributes_0_product_name"
         *  maxlength="255"
         *  name="invoice[rows_attributes][0][product_name]"
         *  placeholder="Välj en produkt"
         *  size="20"
         *  type="text"
         *  value=""
         *  data-supressed-enter="1"
         *  autocomplete="off"
         * >
         */

        $id = str_replace("[]", "", $field);
        $output = $nolabel ? "" : $this->field_LABEL($data);
        $output .= "\t<input type=\"{$type}\" name=\"db_{$field}\" id=\"db_{$id}\" value=\"" . $value . "\" class=\"" . $class . " {$this->cleanField($field)} fld_db_{$field}\"";
        $output .= (isset($eval['readonly']) && $eval['readonly'] == '1') ? ' disabled' : '';
        $output .= (isset($eval['readonly']) && $eval['readonly'] == '2' && $value != "") ? ' disabled' : '';
        // 2024-10-15 - Added new evaluate function
        $output .= $this->evaluate($eval);
        // $output .= (isset($eval['maxlength'])) ? ' maxlength="' . $eval['maxlength'] . '"' : '';
        $output .= ">\n";
        $output .= "<span class=\"counter\">0</span>";
        $output .= $nolabel ? "" : $this->field_LABELFOOTER($data);
        return $output;
    }

    function field_FILE($data)
    {


        $nolabel = NULL;
        $value = NULL;
        $class = NULL;
        $eval = NULL;



        extract($data);
        // Är upload dir satt?
        if (!is_dir($GLOBALS['CONFIG']['uploadDir'])) {

            die('UPLOAD DIR NOT SET!');
        }
        /*
         * <input
         *  class="product-selector product_name"
         *  data-autocomplete="/products/auto_complete.json"
         *  data-empty-text="Klicka på produktlådan för att spara produkten"
         *  id="invoice_rows_attributes_0_product_name"
         *  maxlength="255"
         *  name="invoice[rows_attributes][0][product_name]"
         *  placeholder="Välj en produkt"
         *  size="20"
         *  type="text"
         *  value=""
         *  data-supressed-enter="1"
         *  autocomplete="off"
         * >
         */
        /*        if (isSuperUser()) {
          print "<pre>";
          print_r($data);
          print "</pre>";
        }*/


        $id = str_replace("[]", "", $field);
        $output = ($nolabel) ? "" : $this->field_LABEL($data);
        // Har vi ett värde?
        if ($value !== "" and !is_null($value)) {
            var_dump($value);
            $output .= "\t<input type=\"hidden\" name=\"db_{$field}_filename\" id=\"db_{$field}_filename\" value=\"{$value}\" class=\"{$this->cleanField($field)} fld_db_{$field}_filename isFileName_Path\"";
            $output .= ($eval['required']) ? " required" : "";
            $output .= " />\n";
            $output .= "<span id=\"db_{$field}_span\" style=\"text-decoration:none;\"><b>FIL:</b> <span>$value</span> <a class=\"isFileName_trashcan\" title=\"an icon to delete\" data-id=\"db_{$field}_filename\">" . getIconFull('delete') . "</a><br /></span>"; //onclick=\"javascript:this.preventDefault();\"
            print "<script>
            $(document).ready(function() {
              $('#db_{$field}').hide();
            });
          </>";
        }
        $output .= "\t<input type=\"file\" name=\"db_{$field}\" id=\"db_{$id}\" value=\"" . $value . "\" class=\"" . $class . " {$this->cleanField($field)} fld_db_{$field} isFileName_Input\"";
        $output .= (!is_null($eval) && $eval['readonly'] == '1') ? ' disabled' : '';
        $output .= (!is_null($eval) && $eval['readonly'] == '2' && $value != "") ? ' disabled' : '';
        // 2024-10-15 - Added new evaluate function
        $output .= $this->evaluate($eval);
        // $output .= (isset($eval['maxlength'])) ? ' maxlength="' . $eval['maxlength'] . '"' : '';
        $output .= ">\n";
        $output .= "<span class=\"counter\">0</span>";
        $output .= $nolabel ? "" : $this->field_LABELFOOTER($data);
        return $output;
    }

    function field_MEDIA_LIBRARY($data)
    {
        $data = array_merge(array(
            'field' => '',
            'caption' => null,
            'value' => '',
            'nolabel' => false
        ), $data);

        extract($data);

        if (!media_library::isEnabled()) {
            return "<p><em>Media library kräver att CONFIG uploadDir är satt.</em></p>\n";
        }

        try {
            $mediaLibrary = new media_library();
        } catch (Exception $e) {
            return "<p><em>" . $e->getMessage() . "</em></p>\n";
        }

        $captionText = null;
        if (is_array($caption) && isset($caption[0])) {
            $captionText = $caption[0];
        } elseif (is_string($caption)) {
            $captionText = $caption;
        }

        $output = "";
        if (!$nolabel) {
            $output .= $mediaLibrary->renderPickerField($field, $value, $captionText);
            if (is_array($caption) && isset($caption[1])) {
                $output .= "<p class=\"small\">" . $caption[1] . "</p>\n";
            }
            return $output;
        }

        return $mediaLibrary->renderPickerField($field, $value, null);
    }

    

    function field_COMMON_NEW($data)
    {
        // pre($data);
        // ID
        // Fixar id
        $id = str_replace(array('][', '__', '[', ']'), '_', $data['field']);
        if (substr($id, -1, 1) == "_") {
            $id = substr($id, 0, -1);
        }
        $id = str_replace("[]", "", $id);
        // Fixar name
        $name = $data['field'];
        // Hämta typ
        $type = $data['type'];
        // LABEL!!
        if (!is_array($data['caption']) || !isset($data['caption'])) {
            $nolabel = true;
        } else {
            $nolabel = false;
        }

        // var_dump($nolabel);
        // print "<br>";
        // var_dump($type);
        // print "<br>";
        $output = ($nolabel || $type == 'checkbox' || $type == 'hidden') ? "" : $this->field_LABEL($data);
        // var_dump($output);
        // print "<br>";

        // UNSET   length??, index, null, default, extra, caption, field
        $arr = $data;
        foreach (['length', 'index', 'null', 'default', 'extra', 'caption', 'field', 'name'] as $field) {
            unset($arr[$field]);
        }
        // pre($arr);
        // Bygger INPUT här.
        /*
            <input type="text" name="db_customer_name" id="db_customer_name" value="" class=" frm_customer_name fld_db_customer_name" maxlength="50">
        */
        $output .= "\t<input";
        // NAME && ID
        $output .= " name=\"db_{$name}\"";
        $output .= " id=\"db_{$id}\"";
        // class
        $output .= " class=\"frm_{$name} fld_db_{$name} ";
        if (isset($data['class'])) $output .= $data['class'];
        $output .= "\"";
        // length
        if (isset($data['eval']['maxlength'])) {
            $output .= " maxlength=\"{$data['eval']['maxlength']}\"";
        } else {
            $output .= " maxlength=\"{$data['length']}\"";
        }

        // Kör resten här
        foreach ($arr as $fld => $val) {
            if (!is_array($val)) {
                $output .= " {$fld}=\"{$val}\"";
            } else {
                // Lägga till kommandon här som t.ex. readonly, maxlength
                // pre($val);
            }
        }


        $output .= ">\n";
        $output .= ($type == 'checkbox') ? $this->field_LABEL($data) : "";
        $output .= ($nolabel || $type == 'checkbox' || $type == 'hidden') ? "" : $this->field_LABELFOOTER($data);
        // pre(htmlspecialchars($output));
        return $output;
    }



    /**
     * Vi testar om denna kan fungera
     * De flesta fält har samma standard typ
     * De är: text, hidden, color, (checkbox), date, password, (radio), time
     *
     * Anpassad för html5
     *
     * @param type $data
     * @return type
     */
    function field_COMMON($data)
    {


        // Dummys
        $field = "";
        $nolabel = false;
        $autocomplete = false;
        $autofocus = false;
        $checked = "";
        $class = "";
        $dataautocomplete = "";
        $dataid = "";
        $disabled = "";
        $height = "";
        $width = "";
        $list = "";
        $max = "";
        $maxlength = "";
        $min = "";
        $minlength = "";
        $multiple = "";
        $pattern = "";
        $placeholder = "";
        $readonly = "";
        $required = "";
        $size = "";
        $src = "";
        $step = "";
        $value = "";
        $type = "text";
        $onkeypress = "";
        $inputmode = "";

        if (is_array($data)) {
            extract($data);
        }

        /**
         * Radio och checkbox skiljer sig åt
         */


        // pre($data);
        /*
         * <input
         *  class="product-selector product_name"
         *  data-autocomplete="/products/auto_complete.json"
         *  data-empty-text="Klicka på produktlådan för att spara produkten"
         *  id="invoice_rows_attributes_0_product_name"
         *  maxlength="255"
         *  name="invoice[rows_attributes][0][product_name]"
         *  placeholder="Välj en produkt"
         *  size="20"
         *  type="text"
         *  value=""
         *  data-supressed-enter="1"
         *  autocomplete="off"
         *
         *  autofocus
         *  required
         *  pattern
         *  list
         *  multiple
         *  novalidate
         *
         *  readonly
         *  disabled
         *
         * >
         */
        // Fixar id
        $id = str_replace(array('][', '__', '[', ']'), '_', $field);
        if (substr($id, -1, 1) == "_") {
            $id = substr($id, 0, -1);
        }
        $id = str_replace("[]", "", $id);
        // Fixar name
        $name = $field;


        // Label här!
        if (!is_array($caption) || !isset($caption)) {
            $nolabel = true;
        }
        $output = $nolabel ? "" : $this->field_LABEL($data);

        // Starta här med input
        $output .= "\t<input";
        // autocomplete
        $output .= $autocomplete ? " autocomplete=\"on\"" : " autocomplete=\"off\"";

        // autofocus
        $output .= $autofocus ? " autofocus=\"autofocus\"" : "";

        // checked
        $output .= $checked ? " checked" : "";

        // class
        $output .= $class ? " class=\"{$class} {$this->cleanField($field)} fld_db_{$field}\"" : "";

        // data-autocomplete
        $output .= $dataautocomplete ? " data-autocomplete=\"{$dataautocomplete}\"" : "";

        // data-id
        $output .= ($dataid != "") ? " data-id=\"{$dataid}\"" : "";


        // disabled
        $output .= $disabled ? " disabled=\"disabled\"" : "";

        // height
        $output .= ($height != "") ? " height=\"{$height}\"" : "";

        // width
        $output .= ($width != "") ? " width=\"{$width}\"" : "";

        // id
        $output .= ($id != "") ? " id=\"db_{$id}\"" : "";

        // list
        $output .= ($list != "") ? " list=\"{$list}\"" : "";

        // max
        $output .= ($max != "") ? " max=\"{$max}\"" : "";

        // maxlength
        $output .= ($maxlength != "") ? " maxlength=\"{$maxlength}\"" : "";

        // min
        $output .= ($min != "") ? " min=\"{$min}\"" : "";

        // multiple
        $output .= $multiple ? " multiple" : "";

        // name
        $output .= ($name != "") ? " name=\"db_{$name}\"" : "";

        // pattern
        $output .= ($pattern != "") ? " pattern=\"{$pattern}\"" : "";

        // placeholder
        $output .= ($placeholder != "") ? " placeholder=\"{$placeholder}\"" : "";

        // readonly
        $output .= $readonly ? " readonly" : "";

        // required
        $output .= $required ? " required" : "";

        // size
        $output .= ($size != "") ? " size=\"{$size}\"" : "";

        // src (only type = image)
        $output .= ($src != "") ? " src=\"{$src}\"" : "";

        // step
        $output .= ($step != "") ? " step=\"{$step}\"" : "";

        // onkeypress
        // var_dump($onkeypress);
        $output .= ($onkeypress != "") ? " onkeypress=\"{$onkeypress}\"" : "";

        $output .= ($inputmode != "") ? " inputmode=\"{$inputmode}\"" : "";



        // type
        // Convert my type to allowed types
        // $arrTypes = array('varchar' => 'text');
        // if (array_key_exists($type, $arrTypes)) {
        //     $type = $arrTypes[$type];
        // }
        // // Check allowed fields
        // $arrTypesAllowed = array('button', 'checkbox', 'color', 'date ', 'datetime ', 'datetime-local ', 'email ', 'file', 'hidden', 'image', 'month ', 'number ', 'password', 'radio', 'range ', 'reset', 'search', 'submit', 'tel', 'text', 'time ', 'url', 'week');
        $output .= " type=\"{$type}\""; //(in_array($type, $arrTypesAllowed)) ? " type=\"{$type}\"" : "";

        // value
        $output .= ($value != "") ? " value=\"{$value}\"" : " value=\"\"";


        $output .= ">\n";



        //$output.= "\t<input type=\"text\" name=\"db_{$field}\" id=\"db_{$id}\" value=\"" . $value . "\" class=\"" . $class . " {$this->cleanField($field)}\">\n";
        $output .= $nolabel ? "" : $this->field_LABELFOOTER($data);

        //
        //        print "<pre>";
        //        print htmlspecialchars($output);
        //        print "</pre>";

        return $output;
    }




    function field_PASSWORD($data)
    {
        extract($data);
        $output = $nolabel ? "" : $this->field_LABEL($data);
        $output .= "\t<input type=\"password\" name=\"db_{$field}\" id=\"db_{$field}\" value=\"" . $value . "\" class=\"" . $class . " {$this->cleanField($field)} fld_db_{$field}\">\n";
        $output .= $nolabel ? "" : $this->field_LABELFOOTER($data);
        return $output;
    }

    function field_COLOR($data)
    {
        extract($data);
        $output = $this->field_LABEL($data);
        $output .= "\t<input type=\"color\" name=\"db_{$field}\" id=\"db_{$field}\" value=\"" . $value . "\" class=\"{$this->cleanField($field)} fld_db_{$field}\">\n";
        $output .= $this->field_LABELFOOTER($data);
        return $output;
    }

    function field_HIDDEN($data)
    {
        // Fixar bugg som klagar på fält som inte angetts
        extract(array(
            'nolabel' => '',
            'class' => '',
            'value' => '',
            'eval' => array('readonly' => '')
        ));
        // Hämtar fält med data
        extract($data);
        $output = "\t<input type=\"hidden\" name=\"db_{$field}\" id=\"db_{$field}\" value=\"" . $value . "\" class=\"{$this->cleanField($field)} fld_db_{$field}\">\n";
        return $output;
    }

    function field_RADIOGROUP($data)
    {
        // Denna används om flera fält kan väljas, t.ex. grupper vid access
        extract($data);

        if (!isset($options)) {
            $options = $this->getOptions($data);
        }

        if (isset($options)) {
            // $output.="<label for=\"db_{$key}\">{$caption}</label>\n";
            $output .= "<fieldset>\n";
            $output .= "<legend>{$caption}</legend>\n";
            $xx = 0;
            foreach ($options as $okey => $ovalue) {
                $output .= "<input type=\"radio\" name=\"db_{$field}\" id=\"db_{$field}_{$xx}\" value=\"" . $okey . "\""; //
                if ($value == $okey) {
                    $output .= ' CHECKED';
                }
                $output .= !is_null($required) ? " required" : "";

                $output .= " class=\"{$this->cleanField($field)} fld_db_{$field}\" >";
                $output .= "<label for=\"db_{$field}_{$xx}\">{$ovalue}</label>\n";
                $output .= "<br>\n";
                $xx++;
            }
            $output .= "</fieldset>\n";
            // foreach ($value['options'] as $okey=>$ovalue) {
            //     $output.="<input type=\"radio\" name=\"db_{$key}\" id=\"db_{$key}_{$xx}\" value=\"" . $okey . "\""; //
            //     if ($value['value']==$okey) {$output.=' CHECKED';}
            //     $output.="> " . $ovalue . "<br>\n";
            //     $xx++;
            // }



        }


        //
        // if (!is_array($value)) { $value = unserialize($value); }
        // // Kollar options först
        // if (!isset($options)) {$options = $this->getOptions($data);}

        // $output = $this->field_LABEL($data);
        // if(is_array($options)) {
        //     foreach ($options as $skey => $svalue) {
        //         $output.= "\t<input type=\"radio\" name=\"db_{$field}[$skey]\" id=\"db_{$field}[$skey]\" value=\"1\" class=\"{$this->cleanField($field)} fld_db_{$field}\"";
        //         $output.= ($value==$skey) ? " CHECKED " : "";
        //         $output.= ">  {$svalue}<br>\n";
        //     }
        // }
        // $output.= $this->field_LABELFOOTER($data);
        return $output;
    }

    function field_RADIO($data)
    {
        // Denna används om flera fält kan väljas, t.ex. grupper vid access
        extract($data);
        //
        if (!is_array($value)) {
            $value = unserialize($value);
        }
        // Kollar options först
        if (!isset($options)) {
            $options = $this->getOptions($data);
        }

        $output = $this->field_LABEL($data);
        if (is_array($options)) {
            foreach ($options as $skey => $svalue) {
                $output .= "\t<input type=\"radio\" name=\"db_{$field}[$skey]\" id=\"db_{$field}[$skey]\" value=\"1\" class=\"{$this->cleanField($field)} fld_db_{$field}\"";
                $output .= ($value == $skey) ? " CHECKED " : "";
                $output .= ">  {$svalue}<br>\n";
            }
        }
        $output .= $this->field_LABELFOOTER($data);
        return $output;
    }

    function field_CHECKBOX($data)
    {
        $output = "";
        $value = 0;
        extract($data);
        $output .= "\t<input type=\"checkbox\" name=\"db_{$field}\" id=\"db_{$field}\" value=\"1\" class=\"{$this->cleanField($field)} fld_db_{$field}\"";
        $output .= intval($value) ? " CHECKED " : "";
        $output .= ">&nbsp;{$caption[0]}<br>\n";
        return $output;
    }

    function field_CHECKBOXTABLE($data)
    {
        // Denna används om flera fält kan väljas, t.ex. grupper vid access
        extract($data);
        /*print "<pre>";
        print_r($data);
        print "</pre>";*/

        //
        if (isset($value) && !is_array($value)) {
            $value = unserialize($value);
        }
        // Kollar options först
        if (!isset($options)) {
            $options = $this->getOptions($data);
        }
        //$ouput.="TABLEMODE<br>";
        $output = $this->field_LABEL($data);
        if (is_array($options)) {
            if (isset($checkall) && $checkall) {
                $output .= "<input type=\"checkbox\" id=\"selectAllCheckbox\"> Välj Alla<br><br>\n";
                // fld_db_recipients
                $output .= "<script>\n
                    $(\"#selectAllCheckbox\").click(function(){\n
                        $('.fld_db_{$field}').not(this).prop('checked', this.checked);\n
                    });\n
                </script>
                ";
            }
            foreach ($options as $skey => $svalue) {
                $output .= "\t<input type=\"checkbox\" name=\"db_{$field}[$skey]\" id=\"db_{$field}[$skey]\" value=\"1\"  class=\"{$this->cleanField($field)} fld_db_{$field}\"";
                //$output.= "\t<input type=\"checkbox\" name=\"db_{$field}\" id=\"db_{$field}\" value=\"{$skey}\"  class=\"{$this->cleanField($field)} fld_db_{$field}\"";
                // 20231207 - BUGFIX - om inte value är satt.
                if (isset($value[$skey]) && $value[$skey]) $output .= " CHECKED ";
                //$output.= intval($value[$skey]) ? " CHECKED " : "";
                $output .= ">  {$svalue}<br>\n";
            }
        }
        $output .= $this->field_LABELFOOTER($data);
        return $output;
    }


    function field_DATE($data)
    {
        // var_dump($data['value']);
        $class = "";
        $value = "";
        extract($data);
        $output = $this->field_LABEL($data);
        $output .= "\t<input type=\"date\" name=\"db_{$field}\" id=\"db_{$field}\" value=\"" . $value . "\" class=\"{$this->cleanField($field)} $class fld_db_{$field}\" >\n";
        $output .= $this->field_LABELFOOTER($data);
        return $output;
    }

    function field_MONTH($data)
    {
        extract($data);
        $output = $this->field_LABEL($data);
        $output .= "\t<input type=\"month\" name=\"db_{$field}\" id=\"db_{$field}\" value=\"" . $value . "\" class=\"{$this->cleanField($field)} $class fld_db_{$field}\" >\n";
        $output .= $this->field_LABELFOOTER($data);
        return $output;
    }

    function field_TIME($data)
    {
        extract($data);
        $output = $this->field_LABEL($data);
        $output .= "\t<input type=\"time\" name=\"db_{$field}\" id=\"db_{$field}\" value=\"" . $value . "\" class=\"{$this->cleanField($field)} $class fld_db_{$field}\" step=\"60\">\n";
        $output .= $this->field_LABELFOOTER($data);
        return $output;
    }

    function field_DATETIME($data)
    {
        // Fixar bugg med tidsformatet för denna typ av input - 220623
        $value = "";
        $class = "";
        $eval = array();
        extract($data);
        // kollar om formatet på value är korrekt.
        // pre($data);
        if (isset($value) && $value != "" && substr($value, 10, 1) == " ") {
            $value[10] = "T";
        }
        if (strlen($value) > 16) {
            $value = substr($value, 0, 16);
        }
        $output = $this->field_LABEL($data);
        $output .= "\t<input type=\"datetime-local\" name=\"db_{$field}\" id=\"db_{$field}\" value=\"" . $value . "\" class=\"{$this->cleanField($field)} $class fld_db_{$field}\" step=\"60\"";
        $output .= isset($eval['readonly']) ? " readonly" : "";
        $output .= ">\n";
        $output .= $this->field_LABELFOOTER($data);
        return $output;
    }

    function field_TEXTAREA($data)
    {
        // Fixar bugg som klagar på fält som inte angetts
        extract(array(
            'nolabel' => '',
            'class' => '',
            'value' => '',
            'eval' => array('readonly' => '')
        ));
        // Hämtar fält med data
        /*        if(isSuperUser()) {
          print "<pre>";
          print_r($data);
          print "</pre>";
        }*/
        extract($data);
        $output = $this->field_LABEL($data);
        $output .= "\t<textarea name=\"db_{$field}\" id=\"db_{$field}\" rows=\"4\" cols=\"50\" class=\"{$class} {$this->cleanField($field)} fld_db_{$field}\"";
        $output .= (isset($eval['required']) && $eval['required']) ? " required" : "";
        $output .= " >{$value}</textarea>\n";
        $output .= $this->field_LABELFOOTER($data);
        return $output;
    }

    /**
     * Alias for form_begin, BUT WHY????
     *
     * @param string $name
     * @param array $arrSettings use['arrQuery' => ['page'=> 'new']]
     * @return void
     */
    function form_start($name = 'dbform', $arrSettings = array())
    {
        $this->form_begin($name, $arrSettings);
    }

    function form_begin($name = 'dbform', $arrSettings = array())
    {
        $arrUrlQuery = array('action' => 'save');
        $query = "";
        $class = "";
        $files = "";
        extract($arrSettings);
        if (isset($arrQuery) && is_array($arrQuery)) {
            $arrUrlQuery = array_merge($arrUrlQuery, $arrQuery);
        }
        // Bygga om outputen så inga tomma fält kommer ut.
        // Klar 220620
        $out = "<form ";
        if (isset($method)) {
            $out .= " method=\"{$method}\"";
        } else {
            $out .= " method=\"POST\"";
        }
        //$out.= $method ? " method=\"{$method}\"" : " method=\"POST\"";
        $out .= " action=\"?" . http_build_query($arrUrlQuery) . $query . "\"";
        $out .= $name ? " name=\"{$name}\" id=\"{$name}\"" : "";
        $out .= $class ? " class=\"{$class}\"" : "";
        $out .= $files ? " enctype = \"multipart/form-data\"" : "";
        $out .= ">\n";
        return $out;
        //        return "<form method=\"POST\" action=\"?" . http_build_query($arrUrlQuery) . $query . "\" name=\"{$name}\" id=\"{$name}\" class=\"{$class}\">\n";
    }

    function form_submit($caption = NULL)
    {
        if (is_null($caption)) $caption = $GLOBALS['LANG']['FORM']['submit'];
        return "\t<input type=\"submit\" value=\"{$caption}\" name=\"db_submit\" id=\"db_submit\" class=\"db_submit\">\n";
    }

    function form_end()
    {
        return "</form>\n";
    }

    function cleanField($field)
    {
        $field = str_replace(array('{', '}'), array('', ''), $field);
        return "frm_" . preg_replace('/\[.*\]/', '', $field);
    }



    function fieldsetStart($data)
    { //$fieldsetname='',$legend = '',$bolHidden = false
        //print "HEY!";
        $output = "";
        /*       print "<pre>";
       print_r($data);
       print "</pre>";*/
        /*
Array
(
    [name] => customer_legend
    [hidden] =>
    [caption] => Kundinformation
)
       */
        $GLOBALS['DEBUG'][] = "..." . __CLASS__ . "::" . __FUNCTION__;
        // 20230822 Tog bort denna då jag inte tror den har någon funktion
        // Kan det vara en säkerhetsgrej så man inte glömt en fieldset öppen
        // innan man öppnar en ny?
        $output .= $this->fieldsetClose();

        $fieldsetname = $data['name'];
        // Check if cookie is set to hide fieldset
        /*        if(isset($_COOKIE[$fieldsetname])) {
          print "satt!";
        } else {
          print "EJ satt!";
        }*/


        $legend = $data['caption'];
        if (isset($data['hidden'])) {
            $bolHidden = $data['hidden'];
        } else {
            $bolHidden = false;
        }
        if ($bolHidden) {
            $xdata = 'style="display:none"';
            $html = '&#9658; ';
        } else {
            $xdata = '';
            $html = '&#9660; ';
        }

        //        $fieldcontent=fieldsetname_content
        $output .= "<fieldset id=\"{$fieldsetname}\" >\n";
        $output .= "<legend  id=\"{$fieldsetname}_legend\"  onclick=\"javascript:toggleDiv('{$fieldsetname}','{$legend}');return false;\">{$html}{$legend}&nbsp;&nbsp;</legend>\n";
        $output .= "<div id=\"{$fieldsetname}_content\" {$xdata}>\n";
        $this->fieldsetopen = true;
        return $output;
    }

    function fieldsetClose()
    {
        $output = "";
        $GLOBALS['DEBUG'][] = "..." . __CLASS__ . "::" . __FUNCTION__;
        if ($this->fieldsetopen == true) {
            $this->fieldsetopen = false;
            // $output = "</div>\n</fieldset>\n";
            // 20250227 FIXED - Added ending div that was missing
            // BUT IT IS NOT MISSING
            $output = "</div>\n</fieldset>\n"; //

        }
        return $output;
    }

    function evaluate($eval)
    {
        /**
         * Allowed evals!
         *   div_class
         *  - maxlength
         *  - readonly
         *  - onClick
         *   digits
         *  - required
         *   disabled
         */
        // pre($eval);
        $output = "";
        if (isset($eval['required']) && !is_null($eval['required'])) {
            $output .= " required";
        }
        if (isset($eval['disabled']) && !is_null($eval['disabled'])) {
            $output .= " disabled";
        }
        if (isset($eval['maxlength']) && !is_null($eval['maxlength'])) {
            $output .= " maxlength=\"{$eval['maxlength']}\"";
        }
        if (isset($eval['readonly']) && !is_null($eval['readonly'])) {
            $output .= " readonly";
        }
        if (isset($eval['onClick']) && !is_null($eval['onClick'])) {
            $output .= " onClick=\"{$eval['onClick']}\"";
        }
        // $output = ($eval['required']) ? " required" : "";
        // $output .= ($eval['maxlength']) ? " maxlength=\"{$eval['maxlength']}\"" : "";
        // $output .= ($eval['readonly'] == '1') ? ' disabled' : '';
        // $output .= ($eval['readonly'] == '2' && $value != "") ? ' disabled' : '';
        // $output .= ($eval['readonly']) ? " readonly" : "";
        // $output .= ($eval['onClick']) ? " onClick=\"{$eval['onClick']}\"" : "";



        return $output;
    }
}
