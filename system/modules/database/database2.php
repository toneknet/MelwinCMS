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
 * Description of database
 *
 *  Att inte gå igenom de tabeller som har med raderade företag att göra. (Kanske radera dessa istället?)
 *
 * VIKTIGT! Om du får konstiga fel när du ska lägga till en tabell så är felet
 * förmodligen att du saknar ett fält för companyid i din sql fil.
 * gå in på denna funktion för att se mer: compileCommands
 *
 * @author Martin Tonek <martin@tonek.se>
 */
class database {

    private $debug = false; // Shows all debuginfo for user
    private $db;
    private $input;
    private $showTables = false; // Debug. Skall vara satt till false
    private $return = false; // If return required
    private $output = ""; // the output

    private $arrDB = array();
    private $arrFiles = array();

    function __construct($return = false, $dontrun = false) {
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__;
        print __CLASS__ . " :: " . __FUNCTION__."<br />";
        print $GLOBALS['CONFIG']['public_tables']."<br />";
        $this->getFiles();
    }

    function getDB() {
      // Get all tables from DB
      $db = db::getInstance();
      $result = $db->o_list_tables();
      $this->arrDB = array_flip($result);
      $prefixes = $db->o_list_prefix();

      // Clean list from tables w/o prefixes
      foreach ($this->arrDB as $key => $value) {
        $exists = false;
        foreach ($prefixes as $skey => $svalue) {
          if (str_starts_with($key,$svalue)) $exists = true;
        }
        if (!$exists) {
          unset($this->arrDB[$key]);
        }
      }
      // check if any prefix has wrong tables that is public. the first key in
      // prefixes is always the MAIN prefix

      // Clean list from tables that is public only
      $arrPublic = explode(",",$GLOBALS['CONFIG']['public_tables']);
      foreach ($this->arrDB as $key => $value) {
        foreach ($prefixes as $skey => $svalue) {
          if ($skey == 0) continue;
          foreach ($arrPublic as $zkey => $zvalue) {
            if ($key == $svalue.$zvalue) print "HEY!";
          }
        }
      }
      foreach ($this->arrDB as $key => $value) {
        $exists = false;
        if (strpos($key, $prefixes[0]) === 0) {
          foreach ($arrPublic as $skey => $svalue) {
            if ($key == $prefixes[0].$svalue) $exists = true;
          }
          // Raderar om de fanns med!
          if (!$exists) {
            unset($this->arrDB[$key]);
            //print "Inte tillåten: <b>{$key}</b><br />";
          }
        }
      }

      // Get ALL fields from every table
      foreach ($this->arrDB as $key => $value) {
        $this->arrDB[$key] = $db->o_list_fields($key,0,1);
      }


      print "<pre>";
      print_r($arrPublic);
      print_r($prefixes);
      print_r($this->arrDB);
      /*while($arrDBAnswer = mysqli_fetch_array($result,MYSQLI_ASSOC))
      {
        print_r($arrDBAnswer);
        //  $arrReturn[$arrDBAnswer[$key]]=$arrDBAnswer;
      }*/
      print "</pre>";





    }

    function getFiles() {
      // Get all database.sql files from modules maps/config
      $arrModPaths = getModulePathsOnly();
      /*print "<pre>";
      print_r();
      print "</pre>";*/
      foreach ($arrModPaths as $key => $value) {
        $file = MODPATH . $value . "/config/database.sql";
        if (is_file($file)){
          print "<b>{$file}</b>";
          print ": fanns!";
          print "<br />";
        }
        // code...
      }




    }

    function compareDBandFiles() {

    }

}
