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
 * Description of user
 *
 * @author Martin Tonek <martin@tonek.se>
 */
class user {
    public $id = 0;
    public $companyid = 0;
    public $company = "";
    public $name = "";
    public $superuser = 0;
    public $settings = array();
    private $response = array('success' => 0);
    public $module = 'default';

    function __construct($arrModuleSettings=array()) {
      /*
       * i user.settings finns inställningar specifika för användaren.
       * det är uppbyggt på en array med [företagsid] => [nyckel] => [värde]
       *
       */
      $GLOBALS['DEBUG'][] = __CLASS__ . "::" . __FUNCTION__;
      $auth = auth::getInstance();
      $a = $auth->getUserAndCompany();
      $this->id = $a['id'];// getUserID();
      $this->companyid = $a['companyid'];
      $this->superuser = $a['superuser'];
      $this->name = $a['name'];
      $this->company = $a['company'];
      // var_dump($a['settings']);
      // BUG: 20250602 - Borde inte detta automatiskt vara en array?
      // $this->settings = $a['settings'] ? $a['settings'] : array();
      if (isset($a['settings'])) {
        $this->settings = $a['settings'] ?? [];
      }
      // pre($this->settings);

      /*$data = $a['settings'] ? $a['settings'] : array();
      var_dump($data);*/
      /*print "<pre>";
      //print_r($a);
      print_r($a['settings']);
      print "</pre>";*/
      $GLOBALS['DEBUG'][] = __CLASS__ . "::" . __FUNCTION__ . " - " . $this->id;
    }
    function __destruct() {
      // Spara inställningar
      $db = db::getInstance();
      //print "<hr />".serialize($this->settings)."<hr />";
      $db->o_update('user',array('settings' => serialize($this->settings)),"id=".$this->id);
      $GLOBALS['DEBUG'][] = __CLASS__ . "::" . __FUNCTION__;
      //print "Destroying " . __CLASS__ . "\n";
    }
    //put your code here
    function getSetting($key) {
      if (isset($this->settings[$this->companyid][$this->module][$key])) {
        return $this->settings[$this->companyid][$this->module][$key];
      }
      return 0;
    }

    function getCompanySettings() {
      return isset($this->settings[$this->companyid]) ? $this->settings[$this->companyid] : array();
    }

    function getModuleSettings($module) {
      return isset($this->settings[$this->companyid][$module]) ? $this->settings[$this->companyid][$module] : array();
    }

    function setSetting($key,$value) {
      // var_dump($this->settings);
      if (!is_array($this->settings) && $this->settings == "") { $this->settings = array();}
      $this->settings[$this->companyid][$this->module][$key] = $value;
      return 1;
    }

    function deleteSetting($key) {
      if (isset($this->settings[$this->companyid][$this->module][$key])) unset($this->settings[$this->companyid][$this->module][$key]);
      return 1;
    }

    function getUserSettings($key) {
      return $key;
    }

}

/*

class MyClass {
    protected $_data = array(
        'myVar' => 'test'
    );

    public function __get($name) {
        if (isset($this->_data[$name])) {
            return $this->_data[$name];
        } else {
            // non-existant property
            // => up to you to decide what to do
        }
    }

    public function __set($name, $value) {
        if ($name === 'myVar') {
            throw new Exception("not allowed : $name");
        } else {
            // => up to you to decide what to do
        }
    }
}
$a = new MyClass();
echo $a->myVar . '<br />';
// https://stackoverflow.com/questions/2343790/how-to-implement-a-read-only-member-variable-in-php

*/
