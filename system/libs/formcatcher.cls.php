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
 *
 *
 * @author Martin Tonek <martin@tonek.se>
 */
/**
 * Lägga in funktion för att kolla om tabell har krav på sig att ladda någon
 * innan submit?
 *
 *
 * Är uppdaterad för att klara fieldsets
 *
 */
class formcatcher
{
  public $data;
  public $success = false;
  public $id;
  public $debug = false;

  static function checkHash($dbFormSettings)
  {
    return (input::_post('hash') == salt($dbFormSettings['table'] . date("Y-m-d") . input::_post('db_id')));
  }

  function catchform($dbFormSettings)
  {
    $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__;

    if (!isset($dbFormSettings['table'])) {
      $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__ . " :: MISSING TABLE";
      return;
    }
    $table = $dbFormSettings['table'];
    //$GLOBALS['DEBUG'][] = $table;
    // Kolla här om rätt hash och tabell!!
    $input = new input();
    if ($input->post('hash') == salt($table . date("Y-m-d") . $input->post('db_id'))) {
      $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__ . " :: Passed hash";
      $arrFields = $input->postAll();

      // 2013-12-07 - BUGFIX om tomt fält i db
      //if (isSuperUser()) var_dump($dbFormSettings['form']['palette']);
      if ($dbFormSettings['form']['palette'] == "" && isset($GLOBALS['TABLE'][$table]['form']['palette'])) {
        $dbFormSettings['form']['palette'] = $GLOBALS['TABLE'][$table]['form']['palette'];
      }

      // Kolla om onsubmit_callback på HELA formuläret
      if (isset($dbFormSettings['form']['onsubmit_callback'])) {
        $callback = $dbFormSettings['form']['onsubmit_callback'];

        $class = new $callback[0]();
        $oldFields = $arrFields;
        // Lägger till palette stöd här!
        $arrFields['form']['palette'] = $dbFormSettings['form']['palette'];
        // PHP 7 Fix
        $arrFields = call_user_func(array($class, $callback[1]), $arrFields);
        // $arrFields = $class->$callback[1]($arrFields);
        // 2015-03-29 - Kolla om fel t.ex. krock vid bokning?
        if (empty($arrFields)) {
          setMsg("e", $GLOBALS['LANG']['FORMCATCHER']['error_callback']);
          return '';
        }
        if (isset($arrFields['bolFailed']) && $arrFields['bolFailed'] == true) {
          return;
        }

        // Jämför palette
        if ($arrFields['form']['palette'] != $dbFormSettings['form']['palette']) {
          $dbFormSettings['form']['palette'] = $arrFields['form']['palette'];
        }
      }
      $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__ . " :: after submit callback";

      // Kanske kolla om fälten finns först i db
      $db = db::getInstance();
      $arrFieldsInTable = $db->o_list_fields($table, true, true);

      // Hämtar värden som skall till db
      $fields = explode(",", $dbFormSettings['form']['palette']);
      //if (isSuperUser()) var_dump($fields);
      $GLOBALS['DEBUG'][] = $fields;
      // Kolla om fälten companyid
      foreach ($fields as $key) { //=> $value
        // pre($key .":" . $arrFields['db_'.$key]);
        // pre();
        //print "{$key}<br />";
        if (isset($dbFormSettings['fields'][$key]['eval']['readonly']) && $dbFormSettings['fields'][$key]['eval']['readonly'] == 1) {
          continue;
        }
        // Ta bort fieldsets här
        if (isset($dbFormSettings['form']['fieldsets'])) {
          if ($key == ";") {
            continue;
          }
          if (substr($key, 0, 1) == "{") {
            continue;
          }
        }

        // Kollar om värde är satt i filen, om inte så sätt det som finns i databasen
        if (!isset($dbFormSettings['fields'][$key]['type'])) {
          $dbFormSettings['fields'][$key]['type'] = $arrFieldsInTable[$key]['type'];
        }

        // Fixa så att om det är vissa värden, så fixa en enkel bugfix
        // så att fältet inte är tom, utan är en siffra 0 istället.
        //
        // Om typen checkboxTable används så serialize
        if ($dbFormSettings['fields'][$key]['type'] == 'checkboxtable') {
          /*                  var_dump($dbFormSettings['fields'][$key]);
                  var_dump($arrFields['db_'.$key]);*/
          if (!is_null($arrFields['db_' . $key])) {
            $arrFields['db_' . $key] = serialize($arrFields['db_' . $key]);
          }

          // Fixar datetime OM den inte är mandatory.
        } elseif ($dbFormSettings['fields'][$key]['type'] == 'datetime') {

          //exit;
          if (
            (!isset($dbFormSettings['fields'][$key]['eval']['mandatory']) ||
              $dbFormSettings['fields'][$key]['eval']['mandatory'] = false)
            &&
            $arrFields['db_' . $key] === ""
          ) {
            $arrFields['db_' . $key] = NULL;
          }

          // Fixa siffran 0 om fälten är tomma vid siffervärde
        } elseif (
          $dbFormSettings['fields'][$key]['type'] == 'tinyint' ||
          $dbFormSettings['fields'][$key]['type'] == 'int' ||
          $dbFormSettings['fields'][$key]['type'] == 'checkbox'
        ) {
          $GLOBALS['DEBUG'][] = "TYPE KEY: " . $key;
          if (isset($arrFields['db_' . $key])) {
            $GLOBALS['DEBUG'][] = "VALUE: #" . $arrFields['db_' . $key] . "#";
            if ($arrFields['db_' . $key] == "") {
              $arrFields['db_' . $key] = "0";
              $GLOBALS['DEBUG'][] = "CHANGED!";
            }
          } else {
            $arrFields['db_' . $key] = "0";
          }
          $GLOBALS['DEBUG'][] = "NEW VALUE: #" . $arrFields['db_' . $key] . "#";
        
        
        } elseif ($dbFormSettings['fields'][$key]['type'] == 'file') {
          // File Upload here
          $fileKey = 'db_' . $key;
          if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] == 4) {
            //print "Not";
            // Check if next input is set?
            if ($arrFields['db_' . $key . '_filename'] !== "") {
              $arrFields['db_' . $key] = $arrFields['db_' . $key . '_filename'];
            }
          } else {
            //print "UPLOAD!";
            $target_dir = ROOTPATH . $GLOBALS['CONFIG']['uploadDir'];
            $target_file = $target_dir . basename($_FILES[$fileKey]["name"]);
            $file_name = $_FILES[$fileKey]['name'];
            $file_tmp = $_FILES[$fileKey]['tmp_name'];
            if (move_uploaded_file($file_tmp, $GLOBALS['CONFIG']['uploadDir'] . $file_name)) {
              $arrFields['db_' . $key] = URLBASE . "/" . $GLOBALS['CONFIG']['uploadDir'] . $file_name;
            }
          }
        } elseif ($dbFormSettings['fields'][$key]['type'] == 'media') {
          if (media_library::isEnabled()) {
            $mediaLibrary = new media_library();
            $uploadedItem = $mediaLibrary->uploadFromFilesKey(
              'media_upload_' . $key,
              isset($arrFields['media_title_' . $key]) ? $arrFields['media_title_' . $key] : "",
              isset($arrFields['media_alt_' . $key]) ? $arrFields['media_alt_' . $key] : "",
              isset($arrFields['media_tags_' . $key]) ? $arrFields['media_tags_' . $key] : ""
            );

            if (is_array($uploadedItem) && isset($uploadedItem['id'])) {
              $arrFields['db_' . $key] = $uploadedItem['id'];
            } elseif (
              isset($arrFields['db_' . $key]) &&
              $arrFields['db_' . $key] !== ""
            ) {
              $mediaLibrary->updateMeta(
                $arrFields['db_' . $key],
                isset($arrFields['media_title_' . $key]) ? $arrFields['media_title_' . $key] : null,
                isset($arrFields['media_alt_' . $key]) ? $arrFields['media_alt_' . $key] : null,
                isset($arrFields['media_tags_' . $key]) ? $arrFields['media_tags_' . $key] : null
              );
            }

            if (
              isset($arrFields['db_' . $key]) &&
              trim((string)$arrFields['db_' . $key]) === ""
            ) {
              $arrFields['db_' . $key] = null;
            }
          }          
        }

        // Callback kollas här ??
        // 20231207 - Buggfix. odefinerad nyckel.
        if (isset($arrFields['db_' . $key])) {
          $this->data[$key] = $arrFields['db_' . $key];
        }
      }
      // Fixar companyid här!
      if (is_array($arrFieldsInTable['companyid'])) {
        $this->data['companyid'] = getCompanyID();
      }
      // Var kollas callback

      // Kolla om aftersubmit_callback på HELA formuläret
      if (isset($dbFormSettings['form']['aftersubmit_callback'])) {
        $callback = $dbFormSettings['form']['aftersubmit_callback'];

        $class = new $callback[0]();
        $oldFields = $this->data;
        // Lägger till palette stöd här!
        // $arrFields['form']['palette'] = $dbFormSettings['form']['palette'];
        // PHP 7 Fix
        $this->data = call_user_func(array($class, $callback[1]), $this->data);
        // $arrFields = $class->$callback[1]($arrFields);
        // 2015-03-29 - Kolla om fel t.ex. krock vid bokning?
        if (empty($arrFields)) {
          setMsg("e", $GLOBALS['LANG']['FORMCATCHER']['error_aftersubmit_callback']);
          return '';
        }
        // if (isset($arrFields['bolFailed']) && $arrFields['bolFailed'] == true) {
        //   return;
        // }

        // Jämför palette
        // if ($arrFields['form']['palette'] != $dbFormSettings['form']['palette']) {
        //   $dbFormSettings['form']['palette'] = $arrFields['form']['palette'];
        // }
      }
      $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__ . " :: after submit callback";





      $GLOBALS['DEBUG'][] = $this->data;
      $GLOBALS['DEBUG'][] = $dbFormSettings;
      // Kolla om update eller insert
      $db = db::getInstance();
      if ($this->data['id'] <> "") {
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__ . " :: UPDATE";
        // UPDATE
        $id = $this->data['id'];
        unset($this->data['id']);
        if (!$this->debug) {
          if ($db->o_update($table, $this->data, "id = {$id} ")) {
            setMsg("s", $GLOBALS['LANG']['GENERAL']['saved']);
            $this->success = true;
            $this->id = $id;
          }
        } else {
          print "<pre>";
          print $table . "\n";
          print "ID: " . $id . "\n";
          print_r($this->data);
          print "</pre>";
        }
      } else {
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__ . " :: INSERT";
        // INSERT
        //Tar bort id
        unset($this->data['id']);

        if (!$this->debug) {
          if ($db->o_insert($table, $this->data)) {
            setMsg("s", $GLOBALS['LANG']['GENERAL']['saved']);
            $this->success = true;
            // Set inserted id here?
            $this->id = $db->lastid();
          }
        } else {
          print "<pre>";
          print $table . "\n";
          print_r($this->data);
          print "</pre>";
        }
      }
    } else {
      $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__ . " :: hash did not success";
    }
  }
}
