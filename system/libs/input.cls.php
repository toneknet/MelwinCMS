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
 * Description of input
 * 
 * Need to add function for files
 *
 * @author Martin Tonek <martin@tonek.se>
 */
class input
{

    protected $value;

    public function get($name)
    {
        //$value = $_GET[$name];
        if (!isset($_GET[$name])) $_GET[$name] = "";
        //$this->value = $_GET[$name];
        return $this->value = $_GET[$name];
    }

    public function post($name)
    {
        if (!isset($_POST[$name])) $_POST[$name] = "";
        return $_POST[$name];
    }

    public function postAll()
    {
        return (array) $_POST;
    }

    public function getAll()
    {
        return (array) $_GET;
    }

    public function number($data)
    {
        print $data;
    }

    /**
     * Get all POST inputs request w/o validation
     * 20260309 - Fixade ifall prefix så att den tog bort bara om det fanns
     *
     * @return array
     */
    public static function _postAll($removePrefix = false): array
    {
        $p = $_POST;
        if ($removePrefix) {
            // get prefix
            $pfx = 'db_';
            foreach ($p as $key => $value) {
                unset($p[$key]);
                if (str_starts_with($key,$pfx)){
                    $nkey = substr($key, strlen($pfx));
                } else {
                    $nkey = $key;
                }
                $p[$nkey] = $value;
            }
        }
        return (array) $p;
    }


    /**
     * Get the GET input request
     *
     * @param string $field     Name of field to get
     * @param string $type      For check if value is something. Allowed INT, INTNOZERO
     * @return mixed
     */
    public static function _get($field, $type = "")
    {
        // If field is not set, return NULL
        if (!isset($_GET[$field])) return NULL;

        // Get the value 
        $value = $_GET[$field];

        // Check if type is set
        if (!empty($type)) {
            $value = self::validate($value, $type);
        }

        return $value;
    }

    /**
     * Get the POST input request 
     *
     * @param string $field     Name of field to get
     * @param string $type      For check if value is something. Allowed INT, INTNOZERO, DATETIME, DATE, PHONE
     * @return mixed
     */
    public static function _post($field, $type = "")
    {
        // If field is not set, return NULL
        if (!isset($_POST[$field])) return NULL;

        // Get the value 
        $value = $_POST[$field];

        // Check if type is set
        if (!empty($type)) {
            $value = self::validate($value, $type);
        }

        return $value;
    }

    /**
     * Get the GET AND the POST request for a variable.
     * Let you priority the type you want. GET is standard
     *
     * @param string $field     Name of field to get
     * @param string $type      For check if value is something. Allowed INT, INTNOZERO, DATETIME, DATE, PHONE
     * @param boolean $prioPost If you prio POST before GET or not. Default is GET
     * @return void
     */
    public static function _GetPost($field, $type, $prioPost = false)
    {

        // Get
        $g = self::_get($field);
        // Post
        $p = self::_post($field);

        if ($prioPost && !is_null($p)) {
            $value = $p;
        } elseif (!is_null($g)) {
            $value = $g;
        } elseif (!is_null($p)) {
            $value = $p;
        } else {
            return NULL;
        }

        // Check if type is set
        if (!empty($type)) {
            $value = self::validate($value, $type);
        }

        return $value;
    }

    /**
     * Check value and validate
     *
     * @param mixed $value      Value to validate
     * @param string $type      Type of validation
     * @return mixed
     */
    public static function validate($value, $type)
    {
        if ($type == "INT") {
            $value = intval($value);
        } elseif ($type == "INTNOZERO") {
            $value = intval($value);
            if ($value == 0) {
                $value = NULL;
            }
            // } elseif($type == "BOOL") {
            //     print "BOOL";
            //     $value = intval($value);
            //     var_dump($value);
            //     if (is_null($value)) {
            //         $value = 0;
            //     }
        } elseif ($type == "DATE") {
            $d = DateTime::createFromFormat("Y-m-d", $value);
            $answer = $d && $d->format("Y-m-d") == $value;
            if (!$answer) $value = NULL;
        } elseif ($type == "DATETIME") {
            $d = DateTime::createFromFormat("Y-m-d H:i:s", $value);
            $answer = $d && $d->format("Y-m-d H:i:s") == $value;
            if (!$answer) $value = NULL;
        } elseif ($type == "PHONE") {
            // Only digits and + and -
            // $d = DateTime::createFromFormat("Y-m-d H:i:s", $value);
            $value = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
            if (strlen($value) < 8) $value = NULL;
            // if (!$answer) $value = NULL;
        }
        return $value;
    }
}

/*
class test {

    private $str = NULL;

    public function newTest(){

        $this->str .= 'function "newTest" called, ';
        return $this;
    }
    public function bigTest(){

        return $this->str . ' function "bigTest" called,';
    }
    public function smallTest(){

        return $this->str . ' function "smallTest" called,';
    }
    public function scoreTest(){

        return $this->str . ' function "scoreTest" called,';
    }
}

$test = new test;

echo $test->newTest()->bigTest();*/
