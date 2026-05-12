<?php
/*
Name: Search and Add
Author: Martin Tonek
Author URI: https://www.tonek.se
Description: Dont know yet
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
 * Här kommer det att kunna sökas i vald tabell samt lista posterna och se valt objekt
 *
 * (Bygga in stöd för typ PDF eller dokument i programmet?).
 */

/**
 * Description of searchandadd
 *
 * @author Martin Tonek <martin@tonek.se>
 */
class searchandadd {

    private $dbFormSettings;
    private $allowEdit = false;

    function __construct($arrModuleSettings) {
        if (!isset($arrModuleSettings['table'])) {
            setMsg("e", $GLOBALS['LANG']['ERROR']['table_info_missing']);
            return;
        } else {
            $table = $arrModuleSettings['table'];
            if (isset($GLOBALS['TABLE'][$table])) {
                $this->dbFormSettings = $GLOBALS['TABLE'][$table];
            } else {
                print $table;
                setMsg("e", $GLOBALS['LANG']['ERROR']['table_data_missing']);
                return;
            }
        }
        // Fixa Access här!
        if ($arrModuleSettings['own_access']['edit']) { $this->allowEdit = true;}

        // Bygger formulär

        $input = new input();


                // Save
        if ($input->get('action')=='save' && $this->allowEdit) {
            $catch = new formcatcher();
            $catch->catchform($this->dbFormSettings);
            // Bygga in så att om fel så laddas formuläret igen med de gamla
            // datan som man fyllt i.

        }




        if(($input->get('action')=='add' || ($input->get('action')=='edit' && intval($input->get('id'))>0))  && $this->allowEdit) {
            if ($this->allowEdit) {
                $form = new form();
                if (intval($input->get('id'))>0) {
                    $form->loadData($this->dbFormSettings, intval($input->get('id')));
                }
                $form->MakeWithSettings($this->dbFormSettings);
            }
        } else {

            // Delete here
            if ($input->get('action')=='delete' && intval($input->get('id'))>0 && $this->dbFormSettings['list']['edit']==true && $this->allowEdit){
                $db = db::getInstance();
//                $alert = new alertbox();
                if ($db->updateDelete($table, intval($input->get('id')))) {
                    setMsg('s', "Posten har raderats");
//                    $alert->showBox('success', "Posten har raderats");

                }
            }
            if ($this->allowEdit) {
                print "<a class=\"button button-primary\" href=\"" . getURL(array('action'=>'add')) . "\">Lägg till ny</a>\n";
            }
            $this->searchbox();

        }

        if ($input->get('q')!="" || $input->get('db_q')!="") {
            if ($input->get('db_q') != "") {
              $strQ = $input->get('db_q');
            } else {
              $strQ = $input->get('q');
            }

//            print "SÖKER";
            $db = db::getInstance();
//            $sql = "SELECT * FROM " . $table . " ";
            $sql = "SELECT * FROM [table] ";
//    'search'    => array (
//        'searchfields'  =>  'regnr',
//        'type'          =>  'select',
//        'order'         =>  'datum DESC',
//        'foreignKey'    =>  'vehicle.regnr',
//    ),
            // WHERE
            $strFields = $this->dbFormSettings['search']['searchfields'];
            if (strpos(",", $strFields)==true) {
                $arrFields = explode(",", $strFields);
            } else {
                $arrFields = array($strFields);
            }
            // Bygg WHERE satsen här
            foreach( $arrFields as $field )
            {
              if (isset($this->dbFormSettings['search']['searchexact']) && $this->dbFormSettings['search']['searchexact'] == true) {
                $clause[] = "$field='" . $strQ . "'";
              } else {
                $clause[] = "$field LIKE '%" . $strQ . "%'";
              }
//                $value = $value;

            }
            $sql.= ' WHERE ('. implode(' OR ', $clause) . ')  AND deleted=0 AND companyid='.  getCompanyID();
            print $sql;
            // ORDER
            if (isset($this->dbFormSettings['search']['order'])) {
                $sql.= " ORDER BY " . $this->dbFormSettings['search']['order'];
            }
            // Hämtar data här
            $arrAnswer = $db->o_get_all($table,$sql);
            // Skapar lista här
            $dblist = new dblist();
            $dblist->setData($arrAnswer);
            $dblist->MakeWithSettings($this->dbFormSettings);

        }

    }

    function searchbox() {
        // Ska kunna vara en searchbox med
        if (isset($this->dbFormSettings['search']['searchcaption']) ) {
            $strHeadline =  $this->dbFormSettings['search']['searchcaption'][0];
            $strFooter =   $this->dbFormSettings['search']['searchcaption'][1];
        } else {
            $strHeadline = "Sök: ";
            $strFooter = "Här anger du ett sökord eller en del av ett ord";
        }
        $f = new form();
        // Hur bygga in  om man vill ha en searchbox eller selectbox?
        print "<form method=\"get\" action=\"?action=search\">";
        print "<h4>{$strHeadline}</h4>";
        // Only allowed values is select and that value is unset
        if (isset($this->dbFormSettings['search']['type']) && $this->dbFormSettings['search']['type'] == "select" ) {

          print $f->field_SELECT($this->dbFormSettings['search']);

          //print "<input type=\"text\" name=\"q\" size=\"21\" maxlength=\"120\" >&nbsp;";

        } else {
          print "<input type=\"text\" name=\"q\" size=\"21\" maxlength=\"120\" >&nbsp;";
        }
        print "<input type=\"submit\" value=\"sök\"><br>";
        print "<p class=\"small\">{$strFooter}</p>";
        print "</form>";
    }
}
