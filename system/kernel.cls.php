<?php
/**
Name: Kernel
Author: Martin Tonek
Author URI: https://www.tonek.se
Description: The core of the system
Version: 0.1
Type: SYSTEM
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
 * Ersätter denna kanske Router?
 * Kärnan hanterar allt sammansatt genom att först ladda nödvändiga klasser och
 * sedan routa sidan med följd att moduler laddas och körs.
 * Tillsammans med modulerna körs sedan template klassen som ett underlager.
 *
 * Vad görs nu!
 * -----------------------------------------------------------------------------
 * [ ]  Kollar innan DB laddas om t.ex dbuser/dbpass/dbhost/dbname är satt. Om
 *      inte, så anta att systemet är nystartat och starta install.
 *
 *
 */
class kernel
{
    public function run()
    {
        // Kollar att db info är satt, annars ladda install istället.
        if ($GLOBALS['CONFIG']['dbUser'] == "" && $GLOBALS['CONFIG']['dbPass'] == "" && $GLOBALS['CONFIG']['dbName'] == "") {
          //print "INSTALL MODE";
          include(SYSPATH."system/install.php");
          die();
        }

        // Check auth for login, signup or authed
        $auth = auth::getInstance();
        // Handles login, signup and lost password
        if (!$auth->isAuthed()) {
            // Login
            $this->import('login');
            die();
        }
        // Route the pages and modules
        $route = route::getInstance();

        // Show debug info if requested
        if ($GLOBALS['CONFIG']['displayDebug']==true) { //|| $auth->isSuperUser()
            print "<pre>";
            print_r($GLOBALS['DEBUG']);
            print "</pre>";
        }
    }

    function __set($name, $value) {
        // print __CLASS__ . ":" . __ FUNCTION__ . "$name <br>";
        //        print __FUNCTION__;
        //        var_dump($name);
        //        var_dump($value);
        $this->$name = $value;
    }

    function __get($name) {
        //        print __FUNCTION__;
        //       var_dump($name);
        if (isset( $this->$name)) {
          return $this->$name;
        } else {
          return false;
        }

    }

    function import($strClass, $strKey=false) { //, $blnForce=false) 
  	    $strKey = $strKey ? $strKey : $strClass;
        //    var_dump(is_object($this->$strKey));
		if (!is_object($this->$strKey) ) //|| $blnForce
		{
            // Load language here?
            //			$this->$strKey = (in_array('getInstance', get_class_methods($strClass))) ? call_user_func(array($strClass, 'getInstance')) : new $strClass();
			$this->$strKey =  new $strClass();
            //       var_dump($this->$strKey);
		}
	}

}
