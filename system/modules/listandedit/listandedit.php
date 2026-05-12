<?php
/*
Name: List and Edit
Author: Martin Tonek
Author URI: https://www.tonek.se
Description: Lets you list and edit a table if you have permissions
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


/*
 * Denna klass använder sig av 3 olika libs. Nämligen form.cls.php, dblist.cls.php
 * samt (formcatcher.cls.php)
 */

/**
 * Description of buscalender
 *
 * @author Martin Tonek <martin@tonek.se>
 */
class listandedit
{

    private $dbFormSettings;
    private $allowEdit = false;

    function __construct($arrModuleSettings)
    {

        // pre($arrModuleSettings);
        /*
        print "<pre>";
        print_r($arrModuleSettings) ;
        print "</pre>";
*/
        if (!isset($arrModuleSettings['table'])) {
            setMsg("e", $GLOBALS['LANG']['ERROR']['table_info_missing']);
            return;
        } else {
            #print "<pre>";
            $table = $arrModuleSettings['table'];
            #print "Incoming: ".$table."\n";
            // Varför fungerar inte denna?
            // FEL!!!
            //$table=str_replace("hallens_","",$table);
            #print "After replacing: ".$table."<br />";
            #print "Global set: ";
            // BUGFIX: 20210325 - Got error when trying to use table that isn't
            // hbook, have to replace name in $arrModuleSettings to get it right.
            //$arrModuleSettings['table'] = $table;
            #print_r($GLOBALS['TABLE'][$table]);

            #print "</pre>";
            #die();
            if (isset($GLOBALS['TABLE'][$table])) {
                $this->dbFormSettings = $GLOBALS['TABLE'][$table];

                // Gå igenom hela
                $this->dbFormSettings = array_merge_own($this->dbFormSettings, $arrModuleSettings);


                /*                        print "<pre>";
                        print_r($this->dbFormSettings['fields']) ;
                        print "</pre>";*/
            } else {
                setMsg("e", $GLOBALS['LANG']['ERROR']['table_data_missing']);
                return;
            }
        }
        // Fixa Access här!
        if ($arrModuleSettings['own_access']['edit']) {
            $this->allowEdit = true;
        }

        //        makeHelp($table);


        //unset($arrModuleSettings['own_access_pages']);
        //unset($arrModuleSettings['own_access']);
        //$arrModuleSettings['form']['palette'].=",grupp";
        //print "<pre>";
        //print_r($arrModuleSettings);
        //print "</pre>";
        ////
        ////$arrTmp = unserialize('a:3:{s:5:"table";s:4:"user";s:4:"form";a:1:{s:7:"palette";s:37:"id,user,namn,shortdriver,email,groups";}s:9:"allowAjax";i:0;}');
        ////
        ////$arrTmp['list']['where']="";
        ////
        ////print "<pre>";
        ////print_r($arrTmp);
        ////print "</pre>";
        //print serialize($arrModuleSettings);




        $input = new input();

        if ($input->get('action') == 'save' && $input->post('table') == $table && $this->allowEdit) {

            //             print "<pre>";
            //             print_r($_POST);
            //             print "</pre>";


            // Kolla referenspunkter först
            if ($input->post('hash') == salt($table . date("Y-m-d") . $input->post('db_id'))) {
                $formcatcher = new formcatcher();
                $formcatcher->catchform($this->dbFormSettings);
            } else {
                //                print "ERROR: In catching form";
                setMsg('e', "ERROR: In catching form (Have you remembered to activate 'id' as a field in 'listandedit'?");
            }
            // BUG: 20260416 - added the reload page to fix duplicates
            header('Location: '. getURL(['action'=>'']));
            exit;
        }

        if (($input->get('action') == 'add' || ($input->get('action') == 'edit' && intval($input->get('id')) > 0))  && $this->allowEdit) {
            if ($this->allowEdit) {
                returnBtn();
                $form = new form();
                if (intval($input->get('id')) > 0) {
                    $form->loadData($this->dbFormSettings, intval($input->get('id')));
                }
                $form->MakeWithSettings($this->dbFormSettings);
            }
        } else {

            // Delete here
            if ($input->get('action') == 'delete' && intval($input->get('id')) > 0 && $this->allowEdit) {
                $db = db::getInstance();
                if ($db->o_delete($table, intval($input->get('id')))) {
                    //                    print "Raderad";
                    setMsg('s', $GLOBALS['LANG']['GENERAL']['deleted']);
                }
                // BUG: 20260416 - added the reload page to fix duplicates
                header('Location: '. getURL(['action'=>'','id'=>'']));
                exit;
            }
            if ($this->allowEdit) {
                // 2025-02-14 - Added clear = true
                print "<a class=\"button button-primary\" href=\"" . getURL(array('action' => 'add'), true) . "\">Lägg till ny</a>\n";
            }
            /*            print "<pre>";
            print_r($this->dbFormSettings);
            print "</pre>";*/
            // Lägger till så att man bara kan se med egna företaget!
            if (isset($this->dbFormSettings['list']['where'])) {
                /*print "Hey";*/
            }
            // pre($this->dbFormSettings);
            $dblist = new dblist();
            $dblist->MakeWithSettings($this->dbFormSettings);
        }
    }
}
