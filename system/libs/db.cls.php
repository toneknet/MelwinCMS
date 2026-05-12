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
 * Description of db
 *
 * OBS! Denna skall ha några egna funktioner enligt nedan dör o står för own
 * o_insert     Denna lägger till poster
 * o_update     Denna uppdaterar poster
 * o_get_all    Denna hämtar alla poster och skickar tillbaka dem som dbRetAll gör
 * o_get_row    Denna hämtar endast EN rad och ger den INTE id som moder array
 * o_delete     Denna raderar ett fält genom att sätta deleted=1
 * o_query      Denna kan man skicka frågor direkt
 *
 *
 *
 *
 * @author Martin Tonek <martin@tonek.se>
 */
//class db {
//    function db() {
//        print "db is here";
//    }


// our mysqli object instance
//    public $mysqli = null;
//    public $con = null;

// Class constructor override
//    function __construct() {
//        print "<pre>";
//        print_r($GLOBALS['mt']);
//        print "</pre>";
//        $this->con = mysqli_connect($GLOBALS['mt']['dbHost'], $GLOBALS['mt']['dbUser'], $GLOBALS['mt']['dbPass'],$GLOBALS['mt']['dbName']);

//        $dbHost = $GLOBALS['mt']['dbHost'];
//        $dbUser = $GLOBALS['mt']['dbUser'];
//        $dbPass = $GLOBALS['mt']['dbPass'];
//        $dbName = $GLOBALS['mt']['dbName'];
//        $this->mysqli = new mysqli( $dbHost , $dbUser , $dbPass , $dbName );
//
//        if ($this->mysqli->connect_errno) {
//            echo "Error MySQLi: ("&nbsp. $this->mysqli->connect_errno
//            . ") " . $this->mysqli->connect_error;
//            exit();
//        }
//        $this->mysqli->set_charset("utf8");
//    }

// Class deconstructor override
//    public function __destruct() {
//       $this->CloseDB();
//     }
//
//    // runs a sql query
//    public function runQuery($qry) {
//        $result = $this->mysqli->query($qry);
//        return $result;
//    }
//
//    // runs multiple sql queres
//    public function runMultipleQueries($qry) {
//        $result = $this->mysqli->multi_query($qry);
//        return $result;
//    }
//
//    // Close database connection
//    public function CloseDB() {
////        $this->mysqli->close();
//    }
//
//    // Escape the string get ready to insert or update
//    public function clearText($text) {
//        $text = trim($text);
//        return $this->mysqli->real_escape_string($text);
//    }
//
//    // Get the last insert id
//    public function lastInsertID() {
//        return $this->mysqli->insert_id;
//    }
//
//    // Gets the total count and returns integer
//    public function totalCount($fieldname, $tablename, $where = "")
//    {
//    $q = "SELECT count(".$fieldname.") FROM "
//    . $tablename . " " . $where;
//
//    $result = $this->mysqli->query($q);
//    $count = 0;
//    if ($result) {
//        while ($row = mysqli_fetch_array($result)) {
//        $count = $row[0];
//       }
//      }
//      return $count;
//    }


//}

/*------------------------------------------------------------------------------
** File:		class.db.php
** Class:       Simply MySQLi
** Description:	PHP MySQLi wrapper class to handle common database queries and operations
** Version:		1.0
** Updated:     18-Feb-2013
** Author:		Bennett Stone
** Homepage:	www.phpdevtips.com
**------------------------------------------------------------------------------
** COPYRIGHT (c) 2012 - 2013 BENNETT STONE
**
** The source code included in this package is free software; you can
** redistribute it and/or modify it under the terms of the GNU General Public
** License as published by the Free Software Foundation. This license can be
** read at:
**
** http://www.opensource.org/licenses/gpl-license.php
**
** This program is distributed in the hope that it will be useful, but WITHOUT
** ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
** FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
**------------------------------------------------------------------------------ */
define('DB_HOST', $GLOBALS['CONFIG']['dbHost']);
define('DB_USER', $GLOBALS['CONFIG']['dbUser']);
define('DB_PASS', $GLOBALS['CONFIG']['dbPass']);
define('DB_NAME', $GLOBALS['CONFIG']['dbName']);
//print $GLOBALS['e_CONFIG']['dbUser'];


class db
{
    protected static $instance = null;
    private static $_instance = null;

    private $totquery = 0;

    // All tables that is common for everyone
    private $arrPublicTables; // = array('company','groups','help','page','page_content','user');

    private $sqlquery;

    public $forcePrefix;
    public $forcePrefixID;

    protected function __clone()
    {
        //Me not like clones! Me smash clones!
    }

    //    public static function getInstance()
    //    {
    //        if (!isset(static::$instance)) {
    //            static::$instance = new static;
    //        }
    //        return static::$instance;
    //    }
    public static function getInstance()
    {
        if (!is_object(self::$_instance))  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
            self::$_instance = new db();
        return self::$_instance;
    }


    // Hold an instance of the class
    //    private static $instance;
    //
    //    // The singleton method
    //    public static function singleton()
    //    {
    //        if (!isset(self::$instance)) {
    //            self::$instance = new static;
    //        }
    //        return self::$instance;
    //    }




    private $link;
    public $filter;
    private $arrFreeTables = array('company', 'page', 'page_content');
    private $intCompanyId; // CompanyID







    /*
     * EGNA FUNKTIONER HÄR!
     *
     * o_delete     Denna raderar ett fält genom att sätta deleted=1
     * o_get_all    Denna hämtar alla poster och skickar tillbaka dem som dbRetAll gör
     * o_get_row    Denna hämtar endast EN rad och ger den INTE id som moder array
     * o_insert     Denna lägger till poster
     * o_query      Denna kan man skicka frågor direkt
     * o_update     Denna uppdaterar poster
     *
     *
     * INGA TABELLER SKALL HA MED PREFIXET INNAN
     */



    /**
     * Radera en post med tabellvärdet 'delete'
     *
     * @param string $strTable Tabellens namn
     * @param integer $intId Id:t på posten som ska 'raderas'
     * @return boolean 1=Genomfört , 0=misslyckat
     */
    function o_delete($table, $id)
    {
        $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ . " :: " . $table;
        return $this->o_update($table, array('deleted' => 1), "id = $id ", "companyid=" .  getCompanyID());
    }

    function o_get_all($strTable, $query, $key = 'id', $showDebug = false)
    {
        // Få en autoincrement funktion här om man önskar det?
        $this->sqlquery = $query;
        $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ . " :: " . $strTable . " :: " . $query;
        if ($strTable != "") {
            $strTable = $this->o_prefix($strTable);
        }
        //$strTable=$GLOBALS['CONFIG']['dbPrefix'].$strTable;
        $query = str_replace("[table]", $strTable, $query);
        // if ($showDebug) print "{$strTable} - {$query}<br />";
        if ($showDebug) pre($query); //print "<pre>\n{$query}</pre>\n";
        // print $query."<br>";
        if ($key == "") {
            $key = "id";
        }
        $arrReturn = array();
        // print $query;
        $this->log_db_query(__FUNCTION__, $query);
        if (!$query = $this->link->query($query)) {
            echo ("Error description: " . $this->link->error);
            //generateCallTrace();
        }
        if (mysqli_error($this->link)) {
            $this->log_db_errors(mysqli_error($this->link), $query, 'Fatal', __FUNCTION__);
            return false;
        } else {
            $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ . " :: NUMBER OF ROWS : " . mysqli_num_rows($query);

            while ($arrDBAnswer = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
                $arrReturn[$arrDBAnswer[$key]] = $arrDBAnswer;
            }
            mysqli_free_result($query);
            if ($showDebug) pre($arrReturn);
            return (array) $arrReturn;
        }
    }

    /**
     * returns database result as an AUTOINCREMENTED list instead as an list
     * added by ID
     * @param  string $strTable The table you want to work with
     * @param  string $query    The query to DB
     * @return array            The result from db query
     */
    function o_get_all_ai($strTable, $query)
    {
        // Få en autoincrement funktion här om man önskar det?
        $this->sqlquery = $query;
        $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ . " :: " . $strTable . " :: " . $query;
        if ($strTable != "") {
            $strTable = $this->o_prefix($strTable);
        }
        $query = str_replace("[table]", $strTable, $query);
        $arrReturn = array();
        $query = $this->link->query($query);
        if (mysqli_error($this->link)) {
            $this->log_db_errors(mysqli_error($this->link), $query, 'Fatal', __FUNCTION__);
            return false;
        } else {
            $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ . " :: NUMBER OF ROWS : " . mysqli_num_rows($query);

            while ($arrDBAnswer = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
                $arrReturn[] = $arrDBAnswer;
            }
            mysqli_free_result($query);
            return (array) $arrReturn;
        }
    }




    /**
     * Här får du enbart en rad
     *
     * @param string $strTable
     * @param string $query
     * @return boolean
     */
    function o_get_row($strTable, $query)
    {
        $this->sqlquery = $query;
        $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ . " :: " . $strTable . " :: " . $query;
        //        $strTable=$GLOBALS['CONFIG']['dbPrefix'].$strTable;
        if ($strTable != "") {
            $strTable = $this->o_prefix($strTable);
        }
        $query = str_replace("[table]", $strTable, $query);
        if (strpos($query, "LIMIT") === false) {
            $query .= " LIMIT 0,1";
        }

        // print $query;
        $query = $this->link->query($query);
        if (mysqli_error($this->link)) {
            $this->log_db_errors(mysqli_error($this->link), $query, 'Fatal', __FUNCTION__);
            return false;
        } else {
            //            $r = mysqli_fetch_row( $query );
            $r = mysqli_fetch_array($query, MYSQLI_ASSOC);
            mysqli_free_result($query);
            return $r;
        }
    }

    /**
     * Lists all table with the allowed prefixes
     *
     * @return array $row Tables avalible
     */
    function o_list_tables()
    {
        // 20240113 - Fixade så den ska ha stöd för fördefinerade db...
        // from functions.inc.php
        $arrPrefix = $this->o_list_prefix();
        //pre($arrPrefix);
        // Add so you only get tables for the system? I FIX
        $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ . " :: "; // . $table . " :: " . $query;
        $query = "SHOW TABLES FROM " . $GLOBALS['CONFIG']['dbName'];
        // check for allowed prefixes
        //$arrTmp = $this->o_get_all("company","SELECT id, dbprefix FROM [table] WHERE deleted=0");
        //$arrPrefix[0] = $GLOBALS['CONFIG']['dbPrefix'];
        //foreach ($arrTmp as $key=>$value) {
        //  $arrPrefix[$value['id']] = $value['dbprefix'];
        //}
        //print $query;
        $query = $this->link->query($query);
        if (mysqli_error($this->link)) {
            $this->log_db_errors(mysqli_error($this->link), $query, 'Fatal', __FUNCTION__);
            return false;
        } else {
            $row = array();
            while ($r = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
                $table = current($r);
                //pre($table);
                foreach ($arrPrefix as $key => $value) {
                    if (str_starts_with($table, $value)) {
                        //print $value;
                        $row[] = current($r);
                        continue;
                    }
                }
            }
            mysqli_free_result($query);
            return (array) $row;
        }
    }

    function o_list_fields($strTable, $bolPrefix = false, $bolNameAsID = false)
    {
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__ . " :: " . $strTable;
        if ($bolPrefix) {
            $strTable = $this->o_prefix($strTable);
        }
        $arrReturn = array();
        $query = "SHOW COLUMNS FROM " . $strTable;
        $query = $this->link->query($query);
        if (mysqli_error($this->link)) {
            $this->log_db_errors(mysqli_error($this->link), $query, 'Fatal', __FUNCTION__);
            return false;
        } else {
            while ($row =  mysqli_fetch_array($query, MYSQLI_ASSOC)) {
                if ($bolNameAsID) {
                    $arrFields[$row['Field']] = $row;
                } else {
                    $arrFields[] = $row;
                }
            }

            //             print "<pre>";
            //             print_r($arrFields);
            //             print "</pre>";

            foreach ($arrFields as $k => $v) {
                $arrChunks = preg_split('/(\([^\)]+\))/', $v['Type'], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

                $arrReturn[$k]['name'] = $v['Field'];
                $arrReturn[$k]['type'] = $arrChunks[0];


                //var_dump($arrChunks);
                if (isset($arrChunks[1])) //strlen
                {
                    $arrChunks[1] = str_replace(array('(', ')'), array('', ''), $arrChunks[1]);
                    $arrSubChunks = explode(',', $arrChunks[1]);
                    //                     var_dump($arrSubChunks);
                    $arrReturn[$k]['length'] = trim($arrSubChunks[0]);

                    if (isset($arrSubChunks[1])) //strlen
                    {
                        $arrReturn[$k]['precision'] = trim($arrSubChunks[1]);
                    }
                }

                if (isset($arrChunks[2])) //strlen
                {
                    $arrReturn[$k]['attributes'] = trim($arrChunks[2]);
                }

                if (strlen($v['Key'])) {
                    switch ($v['Key']) {
                        case 'PRI':
                            $arrReturn[$k]['index'] = 'PRIMARY';
                            break;

                        case 'UNI':
                            $arrReturn[$k]['index'] = 'UNIQUE';
                            break;

                        default:
                            $arrReturn[$k]['index'] = 'KEY';
                            break;
                    }
                }

                $arrReturn[$k]['null'] = ($v['Null'] == 'YES') ? 'NULL' : 'NOT NULL';
                $arrReturn[$k]['default'] = $v['Default'];
                $arrReturn[$k]['extra'] = $v['Extra'];
            }
            $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ . " :: Count: " . count($arrReturn);
            return (array) $arrReturn;
        }
    }


    function o_list_prefix()
    {
        $strTable = $GLOBALS['CONFIG']['dbPrefix'] . "company";
        $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ . " :: " . $strTable . " :: ";
        // 20240113 - Fixade så den ska ha stöd för fördefinerade db...
        // from functions.inc.php
        //$arrReturn = allDBPrefixes ();
        //Fördefinerar de här istället 
        // FIXIT:
        $arrReturn = array('hbook_', 'm_', 'mt_');
        /*      if ($strTable!="") {
          $strTable = $this->o_prefix($strTable);
      }
*/      //$strTable=$GLOBALS['CONFIG']['dbPrefix'].$strTable;
        $query = str_replace("[table]", $strTable, "SELECT dbprefix from [table]");
        //if ($key=="") {$key="id";}
        //$arrReturn = array();
        // Check if is NOT in array, then add

        //$arrReturn[] = $GLOBALS['CONFIG']['dbPrefix'];
        $query = $this->link->query($query);
        if (mysqli_error($this->link)) {
            $this->log_db_errors(mysqli_error($this->link), $query, 'Fatal', __FUNCTION__);
            return false;
        } else {
            $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ . " :: NUMBER OF ROWS : " . mysqli_num_rows($query);

            while ($arrDBAnswer = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
                if (!in_array($arrDBAnswer['dbprefix'], $arrReturn)) {
                    $arrReturn[] = $arrDBAnswer['dbprefix'];
                }
            }
            //pre($arrReturn);
            mysqli_free_result($query);
            return (array) $arrReturn;
        }
    }



    /**
     * Hämtar företagsID
     *
     * @param string $strTable
     * @return string Where statement
     */
    private function o_getCompanyID($strTable)
    {
        // Kolla om den är undantagen
        if (!in_array($strTable, $this->arrFreeTables)) {
            // Kollar om det redan finns buffrat
            if ($this->intCompanyId > 0) {
                return " AND companyid = " . $this->intCompanyId;
            }

            $this->intCompanyId = 1;
            return " AND companyid = " . $this->intCompanyId;
        }
        return '';
    }


    function o_insupN($strTable, $arrVariables, $strWhere)
    {
        // Hämta tabellens riktiga namn
        $strTable = $this->o_prefix($strTable);

        $arrAnswer = $this->o_get_row('', 'SELECT * FROM ' . $strTable . ' WHERE ' . $strWhere);

        // Fixa fälten här!!
        //Lägg till variabler
        foreach ($arrVariables as $field => $value) {
            $updates[] = "$field = '$value'";
        }
        $sql = implode(', ', $updates);

        //return false;
        if (is_array($arrAnswer) && count($arrAnswer) > 0) {
            // UPDATE
            $sql = "UPDATE {$strTable} SET " . $sql;

            if ($strWhere <> "") {
                $sql .= " WHERE {$strWhere} AND id=" . $arrAnswer['id'];
            } else {
                $sql .= " WHERE id=" . $arrAnswer['id'];
            }
            // Måste lägg till where id=id här
        } else {
            // INSERT
            $sql = "INSERT INTO {$strTable} SET " . $sql;
        }

        $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ . " :: " . $strTable . " :: " . $sql;
        //print $sql;
        // pre($sql);
        //return;
        // Genomför fråga
        $query = mysqli_query($this->link, $sql);
        //print $sql."<br />";

        if (mysqli_error($this->link)) {
            //            print "DB ERROR!";
            $this->log_db_errors(mysqli_error($this->link), $sql, 'Fatal', __FUNCTION__);
            return false;
        } else {
            return true;
        }
    }

    /**
     * gör en insert eller update beroende på om posten fanns innan eller ej.
     *
     * @param string $strTable
     * @param array $arrVariables
     * @param string $strWhere  (array istället?)
     */
    function o_insup($strTable, $arrVariables = array(), $strWhere = "", $showDebug = true)
    {

        // INSERT INTO hbook_bemanning_visible (date,visible) VALUES ("' . $vdatum . '",1) ON DUPLICATE KEY UPDATE visible=1');
        // Kolla först om tabellen är en av de utvalda
        //        $strWhere.= $this->o_getCompanyID($strTable);

        // Hämta tabellens riktiga namn
        //        $strTable=$GLOBALS['CONFIG']['dbPrefix'].$strTable;
        $strTable = $this->o_prefix($strTable);


        // Börja bygga SQL
        $sql = "UPDATE " . $strTable . " SET ";

        //Lägg till variabler
        foreach ($arrVariables as $field => $value) {

            //            $updates[] = "`$field` = `$value`";
            $updates[] = "$field = '$value'";
        }
        $sql .= implode(', ', $updates);

        // Where
        if (!empty($strWhere)) {
            // tar bort ifall det bara står AND i början
            if (substr($strWhere, 1, 3) == "AND") {
                $strWhere = substr($strWhere, 4);
            }
            $sql .= ' WHERE ' . $strWhere;
        }

        $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ . " :: " . $strTable . " :: " . $sql;
        if ($showDebug) pre($sql);
        // pre($sql);
        // Genomför fråga
        $query = mysqli_query($this->link, $sql);
        //print $sql."<br />";

        if (mysqli_error($this->link)) {
            //            print "DB ERROR!";
            $this->log_db_errors(mysqli_error($this->link), $sql, 'Fatal', __FUNCTION__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Lägg till en NY post
     *
     * @param string $strTable Namnet på tabellen som innehållet skall läggas till i
     * @param array $arrVariables Variablerna som skall läggas till
     * @param array $intForceNoPrefix Om du vill göra en insert utan prefix!
     * @return boolean 1=Genomfört , 0=misslyckat
     */
    function o_insert($strTable, $arrVariables = array(), $intForceNoPrefix = false)
    {

        // VIKTIGT: Bygga INTE in en funktion för companyID här med
        //print $strTable;
        // Kolla table här först
        //        $strTable=$GLOBALS['CONFIG']['dbPrefix'].$strTable;
        if (!$intForceNoPrefix) {
            $strTable = $this->o_prefix($strTable);
        }

        $sql = "INSERT INTO " . $strTable;
        $fields = array();
        $values = array();

        //        foreach( $arrVariables as $field => $value )
        //        {
        //            $fields[] = $field;
        //            $values[] = "'".$value."'";
        //        }
        //        $fields = ' (' . implode(', ', $fields) . ')';
        //        $values = '('. implode(', ', $values) .')';
        //
        //        $sql .= $fields .' VALUES '. $values;
        $sql .= " SET ";
        //Lägg till variabler
        foreach ($arrVariables as $field => $value) {
            if (is_null($value)) {
                $updates[] = "$field = NULL";
            } else {
                // BUGGFIX???? när inte ordning har något värde vid INSERT!! 20220620

                if (in_array($field, array('ordning', 'deleted', 'active', 'companyid'))) {
                    if ($value !== "") {
                        $updates[] = "$field = '$value'";
                    }
                } else {
                    $updates[] = "$field = '$value'";
                }
            }
            //            $updates[] = "`$field` = '$value'";
            //$updates[] = "$field = '$value'";
        }
        if (isset($updates) && is_array($updates) && count($updates)>0) {
            $sql .= implode(', ', $updates);
        }
        // SKA DEN VERKLIGEN VARA HÄR?
        //        $sql.= ", `companyid` = '". getCompanyID() . "'";

        $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ . " :: " . $strTable . " :: " . $sql;

        // Under development i dont execute all sql inserts
        //$query = mysqli_query( $this->link, $sql );
        // pre($sql);

        // Perform a query, check for error
        if (!mysqli_query($this->link, $sql)) {
            echo ("Error description: " . mysqli_error($this->link));
        }


        if (mysqli_error($this->link)) {
            //print $sql;
            //print mysqli_error( $this->link );
            //print "[$severity] [$function] [$error]  [$query] [$trace]");
            //print("[" .date("d-M-Y h:m:s") ."] [$severity] [$function]\n[$error]\n[$query]\n[$trace]\n[$this->sqlquery]\n", 3, SYSPATH . "/logs/db.log");
            //die();
            //[07-Apr-2019 23:19:57


            //return false;
            $this->log_db_errors(mysqli_error($this->link), $sql, 'Fatal', __FUNCTION__);
            return false;
        } else {
            return true;
        }
    }

    /**
     * Makes it possible to force prefix
     * @param  [string] $strPrefix               [description]
     */
    function o_force_prefix($strPrefix)
    {
        $this->forcePrefix = $strPrefix;
    }

    /**
     * Fixar rätt typ av prefix till rätt tabell..
     * @param type $strTable
     * @return type
     */
    function o_prefix($strTable)
    {
        /**
         * Jag har en bugg här när användaren inte har fått en auth. Då får jag felaktiga tabeller 
         * som i sin tur genererar ett fel.
         */
        // pre($strTable);
        // pre($this->arrPublicTables);
        if (in_array($strTable, $this->arrPublicTables)) {
            // Public
            $strType = "PUBLIC";
            $strTable = $GLOBALS['CONFIG']['dbPrefix'] . $strTable;
            //print $strTable."@".__LINE__."<br />";
        } else {
            // print $strTable;
            // Get prefix here?
            // $userData = auth::$userData;
            // if ()


            if (!isset($GLOBALS['CONFIG']['dbPrefix_private']) && $this->forcePrefix != "") {
                $GLOBALS['CONFIG']['dbPrefix_private'] = $this->forcePrefix;
                //print $strTable."@".__LINE__."<br />";
            } elseif (!isset($GLOBALS['CONFIG']['dbPrefix_private'])) {
                // $a = auth::getUserData();
                // print "<pre>";
                // print_r($a);
                // print "</pre>";
                // pre(auth::$userData);
                // $auth = auth::getInstance();
                // $a = $auth->getUserAndCompany();
            }
            if (!isset($GLOBALS['CONFIG']['dbPrefix_private'])) {
                //print $strTable."@".__LINE__."<br />";
                throw new Exception($GLOBALS['LANG']['ERROR']['db_error_prefix'] . " TABLE:{$strTable}");
            }
            // Private
            $strType = "PRIVATE";
            $strTable = $GLOBALS['CONFIG']['dbPrefix_private'] . $strTable;
            //print $strTable."@".__LINE__."<br />";
        }
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__ . " :: " . $strType . " :: " . $strTable;
        // pre($strTable);
        return $strTable;
    }



    /**
     * Ställ en direkt fråga till databasen här!
     * OBS! Ingen parse på denna
     *
     * @param string $query Din SQL fråga
     * @return boolean 1=Genomfört , 0=misslyckat
     */
    function o_query($query)
    {
        $this->sqlquery = $query;
        $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ . " :: " . '' . " :: " . $query;
        //print $query."<br />";
        $queryx = $this->link->query($query);
        if (mysqli_error($this->link)) {
            print mysqli_error($this->link);
            $this->log_db_errors(mysqli_error($this->link), $query, 'Fatal', __FUNCTION__);
            return false;
        } else {
            return true;
        }
        mysqli_free_result($queryx);
    }


    /**
     * Egen UPDATE funktion för att underlätta
     *
     * @param string $strTable
     * @param array $arrVariables
     * @param string $strWhere Här anges eventuell WHERE sats
     * @return boolean 1=Genomfört , 0=misslyckat
     */
    function o_update($strTable, $arrVariables = array(), $strWhere = "")
    {


        // Kolla först om tabellen är en av de utvalda
        //        $strWhere.= $this->o_getCompanyID($strTable);

        // Hämta tabellens riktiga namn
        //        $strTable=$GLOBALS['CONFIG']['dbPrefix'].$strTable;
        $strTable = $this->o_prefix($strTable);


        // Börja bygga SQL
        $sql = "UPDATE " . $strTable . " SET ";

        //Lägg till variabler
        foreach ($arrVariables as $field => $value) {
            // 251106 - Extra fix för ogiltiga värden?
            if (in_array($field,['deleted','ordning'])) {
                if ($value == "") continue;
            }


            if (is_null($value)) {
                $updates[] = "$field = NULL";
            } else {
                $updates[] = "$field = '$value'";
            }
            //            $updates[] = "`$field` = `$value`";

        }
        $sql .= implode(', ', $updates);

        // Where
        if (!empty($strWhere)) {
            // tar bort ifall det bara står AND i början
            if (substr($strWhere, 1, 3) == "AND") {
                $strWhere = substr($strWhere, 4);
            }
            $sql .= ' WHERE ' . $strWhere;
        }

        $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ . " :: " . $strTable . " :: " . $sql;
        // print $sql;
        // if (isSuperUser()) die($sql);
        //return false;
        // Genomför fråga
        // if(isSuperUser()) {
        //     print $sql;
        //     return true;
        // }
        // debug();
        // pre($sql);

        $query = mysqli_query($this->link, $sql);

        if (mysqli_error($this->link)) {

            $this->log_db_errors(mysqli_error($this->link), $sql, 'Fatal', __FUNCTION__);
            return false;
        } else {
            return true;
        }
    }

    /**
     * Check if connected to db
     * @return boolean true if connected
     */
    public function o_connected()
    {
        if (mysqli_connect_errno()) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Return last inserted ID
     * @return int last inserted ID
     */
    public function o_insert_id()
    {
        return mysqli_insert_id($this->link);
    }


    public function o_insert_and_get_id($strTable) {

        $this->o_insert($strTable,['deleted' => 0]);
        
        return $this->o_insert_id();
    }


    /*
     * SLUT PÅ EGNA FUNKTIONER HÄR!
     */

    private function log_db_query($function = '', $query = '')
    {
        $msg = "[" . date("Y-m-d h:i:s") . "] [{$function}] $query\n";
        $dir = str_replace("libs", "logs", __DIR__);
        file_put_contents($dir . "/db_" . date("Ymd") . ".log", $msg, FILE_APPEND);
    }


    /**
     * Allow the class to send admins a message alerting them to errors
     * on production sites
     */
    public function log_db_errors($error, $query, $severity, $function)
    {

        $trace = generateCallTrace();

        setMsg("e", "<b>Databas fel!</b><br /> Det du försökt göra har inte gått att genomföra. Kontakta ansvarig snarast för att undersöka felet.");
        error_log("[$severity] [$function] [$error]  [$query] [$trace]");
        error_log("[" . date("d-M-Y h:i:s") . "] [$severity] [$function]\n[$error]\n[$query]\n[$trace]\n[$this->sqlquery]\n", 3, SYSPATH . "/logs/db.log");
        //[07-Apr-2019 23:19:57
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__ . " :: " . $error;
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__ . " :: " . $query;
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__ . " :: " . $severity;

        if (getUserID() == 1) {
            setMsg("e", "<b>Felmeddelande:</b><br /> <pre>[$severity] [$function]\n[$error]\n[$query]\n[$trace]\n[$this->sqlquery]</pre>");
        } else {
            if (sendMail("admin@hallens.se", "Databas fel", "[" . date("d-M-Y h:m:s") . "] [$severity] [$function]\n[$error]\n[$query]\n[$trace]\n[$this->sqlquery]")) {
                error_log("DB ERROR: Mail sent");
            } else {
                error_log("DB ERROR: Mail NOT sent");
            }
        }



        //        $headers  = 'MIME-Version: 1.0' . "\r\n";
        //        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        //        $headers .= 'To: Admin <'.SEND_ERRORS_TO.'>' . "\r\n";
        //        $headers .= 'From: Yoursite <system@your-site.com>' . "\r\n";
        //
        //        $message = '<p>An error has occurred:</p>';
        //
        //        $message .= '<p>Error at '. date('Y-m-d H:i:s').': ';
        //        $message .= 'Query: '. htmlentities( $query ).'<br />';
        //        $message .= '</p>';
        //        $message .= '<p>Severity: '. $severity .'</p>';
        //
        ////        mail( SEND_ERRORS_TO, 'Database Error', $message, $headers);
        //
        //        if( DISPLAY_DEBUG )
        //        {
        //            echo $message;
        //        }
    }


    public function __construct()
    {
        // Laddar ifrån config istället!
        $this->arrPublicTables = explode(",", $GLOBALS['CONFIG']['public_tables']);

        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__;
        global $connection;
        mb_internal_encoding('UTF-8');
        mb_regex_encoding('UTF-8');
        //var_dump(DB_HOST);
        //var_dump(DB_USER);
        //var_dump(DB_PASS);
        //var_dump(DB_NAME);
        if (DB_HOST == "" || DB_USER == "" || DB_PASS == "" || DB_NAME == "") {
            print "NO DB SETTINGS EXIST, MUST BE INSTALL";
            return;
        }
        //var_dump($GLOBALS['CONFIG']['public_tables']);
        //if (DB_HOST != "") { print "HEY" };
        $this->link = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if (mysqli_connect_errno()) {
            $this->log_db_errors("Connect failed: %s\n", mysqli_connect_error(), 'Fatal', __FUNCTION__);
            exit();
        }
        mysqli_set_charset($this->link, 'utf8');
        $this->o_query("SET NAMES 'utf8'");
        $this->o_query("SET CHARACTER SET utf8");
        $this->o_query("SET COLLATION_CONNECTION = 'utf8_unicode_ci'");
    }

    public function __destruct()
    {
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__;
        $this->disconnect();
    }


    /**
     * Sanitize user data
     *
     * @access public
     * @param string, array
     * @return string, array
     *
     */
    public function filter($data)
    {
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__;

        if (!is_array($data)) {
            $data = trim(htmlentities($data));
            $data = mysqli_real_escape_string($this->link, $data);
        } else {
            //Self call function to sanitize array data
            $data = array_map(array('DB', 'filter'), $data);
        }
        return $data;
    }


    /**
     * Determine if common non-encapsulated fields are being used
     *
     * @access public
     * @param string
     * @param array
     * @return bool
     *
     */
    public function db_common($value = '')
    {
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__;

        if (is_array($value)) {
            foreach ($value as $v) {
                if (preg_match('/AES_DECRYPT/i', $v) || preg_match('/AES_ENCRYPT/i', $v) || preg_match('/now()/i', $v)) {
                    return true;
                } else {
                    return false;
                }
            }
        } else {
            if (preg_match('/AES_DECRYPT/i', $value) || preg_match('/AES_ENCRYPT/i', $value) || preg_match('/now()/i', $value)) {
                return true;
            }
        }
    }


    /**
     * Perform queries
     * All following functions run through this function
     * All data run through this function is automatically sanitized using the filter function
     *
     * @access public
     * @param string
     * @return string
     * @return array
     * @return bool
     *
     */
    public function query($query)
    {
        $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ . " :: " . $query;
        //$GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ ." :: " . $table . " :: " . $query;

        $query = $this->link->query($query);
        if (mysqli_error($this->link)) {
            $this->log_db_errors(mysqli_error($this->link), $query, 'Fatal', __FUNCTION__);
            return false;
        } else {
            return true;
        }
        mysqli_free_result($query);
    }


    /**
     * Determine if database table exists
     *
     * @access public
     * @param string
     * @return bool
     *
     */
    public function table_exists($name)
    {
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__;

        $check = $this->link->query("SELECT * FROM '$name' LIMIT 1");
        if ($check) {
            return true;
        } else {
            return false;
        }
        mysqli_free_result($check);
    }

    public function test($query)
    {
        print "## $query ## <br>";
        //        return $this;
    }

    public function parse_query($query)
    {
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__;
        $type = "";
        $statements = array("SELECT", "INSERT", "UPDATE", "REPLACE", "DELETE");
        foreach ($statements as $value) {
            if (strpos(strtoupper($query), $value) !== false) {
                //print $value;
                $type = $value;
                break;
            }
        }
        switch ($type) {
            case "SELECT":
                preg_match('/\bfrom\b\s*(\w+)/i', $query, $matches);
                $table = $matches[1];
                break;
            case "INSERT":
                preg_match('/\binto\b\s*(\w+)/i', $query, $matches);
                $table = $matches[1];
                break;
            case "UPDATE":
                preg_match('/\bupdate\b\s*(\w+)/i', $query, $matches);
                $table = $matches[1];
                break;
            case "REPLACE":
                preg_match('/\binto\b\s*(\w+)/i', $query, $matches);
                $table = $matches[1];
                break;
            case "DELETE":
                preg_match('/\bfrom\b\s*(\w+)/i', $query, $matches);
                $table = $matches[1];
                break;
        }
        // Rebuild table
        if ($table != "") {
            if (strlen($GLOBALS['CONFIG']['dbPrefix']) < strlen($table)) {
                $query = str_replace($table, $GLOBALS['CONFIG']['dbPrefix'] . $table, $query);
            } elseif (strpos($query, $GLOBALS['CONFIG']['dbPrefix']) === false) {
                $query = str_replace($table, $GLOBALS['CONFIG']['dbPrefix'] . $table, $query);
            }
        }
        //        print $query;
        //        $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ ." :: " . $query;
        return $query;
    }


    /**
     * Count number of rows found matching a specific query
     *
     * @access public
     * @param string
     * @return int
     *
     */
    public function num_rows($query)
    {
        $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ . " :: " . $query;
        // $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ ." :: " . $table . " :: " . $query;
        $query = $this->link->query($query);
        if (mysqli_error($this->link)) {
            $this->log_db_errors(mysqli_error($this->link), $query, 'Fatal', __FUNCTION__);
            return mysqli_error($this->link);
        } else {
            return mysqli_num_rows($query);
        }
        mysqli_free_result($query);
    }


    /**
     * Run check to see if value exists, returns true or false
     *
     * Example Usage:
     * $check_user = array(
     *    'user_email' => 'someuser@gmail.com',
     *    'user_id' => 48
     * );
     * $exists = exists( 'your_table', 'user_id', $check_user );
     *
     * @access public
     * @param string database table name
     * @param string field to check (i.e. 'user_id' or COUNT(user_id))
     * @param array column name => column value to match
     * @return bool
     *
     */
    public function exists($table = '', $check_val = '', $params = array())
    {
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__;

        if (empty($table) || empty($check_val) || empty($params)) {
            return false;
            exit;
        }
        $check = array();
        foreach ($params as $field => $value) {

            if (!empty($field) && !empty($value)) {
                //Check for frequently used mysql commands and prevent encapsulation of them
                if ($this->db_common($value)) {
                    $check[] = "$field = $value";
                } else {
                    $check[] = "$field = '$value'";
                }
            }
        }
        $check = implode(' AND ', $check);

        $rs_check = "SELECT $check_val FROM " . $table . " WHERE $check";
        $number = $this->num_rows($rs_check);
        if ($number === 0) {
            return false;
        } else {
            return true;
        }
        exit;
    }


    /**
     * Return specific row based on db query
     *
     * @access public
     * @param string
     * @return array
     *
     */
    public function get_row($query)
    {
        $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ . " :: " . $query;
        // $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ ." :: " . $table . " :: " . $query;
        $query = $this->link->query($query);
        if (mysqli_error($this->link)) {
            $this->log_db_errors(mysqli_error($this->link), $query, 'Fatal', __FUNCTION__);
            return false;
        } else {
            //            $r = mysqli_fetch_row( $query );
            $r = mysqli_fetch_array($query, MYSQLI_ASSOC);
            mysqli_free_result($query);
            return $r;
        }
    }

    public function get_table($table)
    {
        $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ . " :: " . $table;
        $query = "SHOW COLUMNS FROM " . $GLOBALS['CONFIG']['dbPrefix'] . $table;
        $query = $this->link->query($query);
        if (mysqli_error($this->link)) {
            $this->log_db_errors(mysqli_error($this->link), $query, 'Fatal', __FUNCTION__);
            return false;
        } else {
            while ($r = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
                $row[$r['Field']] = $r;
            }
            mysqli_free_result($query);
            return $row;
        }
    }

    public function get_tables()
    {
        $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__;
        //        $query = "SHOW TABLES IN " . $GLOBALS['CONFIG']['dbName'];
        $query = "SHOW TABLES IN " . $GLOBALS['CONFIG']['dbName'] . "  LIKE '" . $GLOBALS['CONFIG']['dbPrefix'] . "%'";
        $query = $this->link->query($query);
        if (mysqli_error($this->link)) {
            $this->log_db_errors(mysqli_error($this->link), $query, 'Fatal', __FUNCTION__);
            return false;
        } else {


            while ($r = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
                $row[] = str_replace($GLOBALS['CONFIG']['dbPrefix'], "", current($r));
            }
            mysqli_free_result($query);
            return $row;
        }
    }


    /**
     * Perform query to retrieve array of associated results
     *
     * @access public
     * @param string
     * @return array
     *
     */
    public function get_results($query, $parse = true)
    {
        $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ . " :: " . $query;
        if ($parse == true) {
            $query = $this->parse_query($query);
        }
        $row = array();
        //            print $query;
        $query = $this->link->query($query);
        if (mysqli_error($this->link)) {
            $this->log_db_errors(mysqli_error($this->link), $query, 'Fatal', __FUNCTION__);
            return false;
        } else {
            while ($r = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
                $row[] = $r;
            }
            mysqli_free_result($query);
            return $row;
        }
    }

    /**
     * Make the SELECT more easy to use with prefix and return ALL answers
     *
     * @param string $table Tablename
     * @param string $fields Fields to display (default *)
     * @param array $where Where statements
     * @param array $order Order statements
     * @param string $limit Sets like "0,1"
     */
    function selectRetAll($table, $fields = "", $where = array(), $order = array(), $limit = "")
    {
        $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ . " :: " . $table;
        if ($fields == "") {
            $fields = "*";
        }
        $sql = "SELECT {$fields} FROM " . $GLOBALS['CONFIG']['dbPrefix'] . $table;

        if (count($where) > 0) {
            foreach ($where as $field => $value) {
                $value = $value;

                $clause[] = "$field='$value'";
            }
            $sql .= ' WHERE ' . implode(' AND ', $clause);
        }

        if (count($order) > 0) {
            $sql .= ' ORDER BY ' . implode(', ', $order);
        }

        if (!empty($limit)) {
            $sql .= ' LIMIT ' . $limit;
        }
        //        print $sql;
        return $this->dbRetAll($sql, 'id', false);
    }

    function select($table, $fields = "", $where = "", $order = "", $limit = "")
    {
        $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ . " :: " . $table;
        if ($fields == "") {
            $fields = "*";
        }
        $sql = "SELECT {$fields} FROM " . $GLOBALS['CONFIG']['dbPrefix'] . $table;
        if ($where != "") {
            $sql .= " WHERE " . $where;
        }
        if ($order != "") {
            $sql .= " ORDER BY " . $order;
        }
        if ($limit != "") {
            $sql .= " LIMIT " . $limit;
        }


        //        print $sql;
        return $this->dbRetAll($sql, 'id', false);
    }

    function dbRetAll($query, $key = 'id', $parse = true)
    {
        $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ . " :: " . $query;
        if ($key == "") {
            $key = "id";
        }
        if ($parse == true) {
            $query = $this->parse_query($query);
        }
        //        print "SQL: " . $query."<br>";
        $arrReturn = array();
        $query = $this->link->query($query);
        if (mysqli_error($this->link)) {
            $this->log_db_errors(mysqli_error($this->link), $query, 'Fatal', __FUNCTION__);
            return false;
        } else {
            while ($arrDBAnswer = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
                $arrReturn[$arrDBAnswer[$key]] = $arrDBAnswer;
            }
            mysqli_free_result($query);
            return (array) $arrReturn;
        }
    }


    //    function dbRetAllWithKey($query, $key = 'id', $parse = true)
    //    {
    //        if ($parse==true) {
    //            $query = $this->parse_query($query);
    //        }
    ////        print "SQL: " . $query."<br>";
    //        $arrReturn = array();
    //        $query = $this->link->query( $query );
    //        if( mysqli_error( $this->link ) )
    //        {
    //            $this->log_db_errors( mysqli_error( $this->link ), $query, 'Fatal' );
    //            return false;
    //        }
    //        else
    //        {
    //            while($arrDBAnswer = mysqli_fetch_array($query,MYSQLI_ASSOC))
    //            {
    //                $arrReturn[$arrDBAnswer[$key]]=$arrDBAnswer;
    //            }
    //            mysqli_free_result( $query );
    //            return (array) $arrReturn;
    //        }
    //    }



    /**
     * Insert data into database table
     *
     * @access public
     * @param string table name
     * @param array table column => column value
     * @return bool
     *
     */
    public function insert($table, $variables = array())
    {
        $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ . " :: " . $table;

        // Kolla table här först
        if (strpos($table, $GLOBALS['CONFIG']['dbPrefix']) === false) {
            $table = $GLOBALS['CONFIG']['dbPrefix'] . $table;
        }
        $sql = "INSERT INTO " . $table;
        $fields = array();
        $values = array();
        foreach ($variables as $field => $value) {
            $fields[] = $field;
            $values[] = "'" . $value . "'";
        }
        $fields = ' (' . implode(', ', $fields) . ')';
        $values = '(' . implode(', ', $values) . ')';

        $sql .= $fields . ' VALUES ' . $values;

        // Under development i dont execute all sql inserts
        if ($GLOBALS['CONFIG']['noExecSql']) {
            print "<strong>SQL (INSERT):</strong> " . $sql . "<br>";
        } else {
            $query = mysqli_query($this->link, $sql);
        }

        if (mysqli_error($this->link)) {
            //return false;
            $this->log_db_errors(mysqli_error($this->link), $sql, 'Fatal', __FUNCTION__);
            return false;
        } else {
            return true;
        }
    }

    /**
     * Insert data KNOWN TO BE SECURE into database table
     * Ensure that this function is only used with safe data
     * No class-side sanitizing is performed on values found to contain common sql commands
     * As dictated by the db_common function
     * All fields are assumed to be properly encapsulated before initiating this function
     *
     * @access public
     * @param string table name
     * @param array table column => column value
     * @return bool
     */
    public function insert_safe($table, $variables = array())
    {
        $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ . " :: " . $table;
        $sql = "INSERT INTO " . $table;
        $fields = array();
        $values = array();
        foreach ($variables as $field => $value) {
            $fields[] = $this->filter($field);
            //Check for frequently used mysql commands and prevent encapsulation of them
            $values[] = $value;
        }
        $fields = ' (' . implode(', ', $fields) . ')';
        $values = '(' . implode(', ', $values) . ')';

        $sql .= $fields . ' VALUES ' . $values;
        $query = mysqli_query($this->link, $sql);

        if (mysqli_error($this->link)) {
            $this->log_db_errors(mysqli_error($this->link), $sql, 'Fatal', __FUNCTION__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Update data in database table
     *
     * @access public
     * @param string table name
     * @param array values to update table column => column value
     * @param array where parameters table column => column value
     * @param int limit
     * @return bool
     *
     */
    public function update($table, $variables = array(), $where = array(), $limit = '')
    {
        $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ . " :: " . $table;

        // Kolla table här först
        if (strpos($table, $GLOBALS['CONFIG']['dbPrefix']) === false) {
            $table = $GLOBALS['CONFIG']['dbPrefix'] . $table;
        }
        $sql = "UPDATE " . $table . " SET ";
        foreach ($variables as $field => $value) {

            $updates[] = "`$field` = '$value'";
        }
        $sql .= implode(', ', $updates);

        foreach ($where as $field => $value) {
            $value = $value;

            $clause[] = "$field = '$value'";
        }
        $sql .= ' WHERE ' . implode(' AND ', $clause);

        if (!empty($limit)) {
            $sql .= ' LIMIT ' . $limit;
        }

        // Under development i dont execute all sql inserts
        if ($GLOBALS['CONFIG']['noExecSql']) {
            print "<strong>SQL (UPDATE):</strong> " . $sql . "<br>";
        } else {
            $query = mysqli_query($this->link, $sql);
        }

        if (mysqli_error($this->link)) {
            $this->log_db_errors(mysqli_error($this->link), $sql, 'Fatal', __FUNCTION__);
            return false;
        } else {
            return true;
        }
    }




    public function updateDelete($table, $id)
    {
        $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ . " :: " . $table;
        //       print "TABLE: {$table}\n<br>ID: {$id}\n<br>";
        return $this->update($table, array('deleted' => 1), array('id' => $id, 'companyid' =>  getCompanyID()));
    }

    /**
     * Delete data from table
     *
     * @access public
     * @param string table name
     * @param array where parameters table column => column value
     * @param int max number of rows to remove.
     * @return bool
     *
     */
    public function delete($table, $where = array(), $limit = '')
    {
        $GLOBALS['DEBUG'][] = "DB :: " . __FUNCTION__ . " :: " . $table;

        // Kolla table här först
        if (strpos($table, $GLOBALS['CONFIG']['dbPrefix']) === false) {
            $table = $GLOBALS['CONFIG']['dbPrefix'] . $table;
        }
        $sql = "DELETE FROM " . $table;
        foreach ($where as $field => $value) {
            $value = $value;
            $clause[] = "$field = '$value'";
        }
        $sql .= " WHERE " . implode(' AND ', $clause);

        if (!empty($limit)) {
            $sql .= " LIMIT " . $limit;
        }

        $query = mysqli_query($this->link, $sql);

        if (mysqli_error($this->link)) {
            //return false; //
            $this->log_db_errors(mysqli_error($this->link), $sql, 'Fatal', __FUNCTION__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Get last auto-incrementing ID associated with an insertion
     *
     * @access public
     * @param none
     * @return int
     *
     */
    public function lastid()
    {
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__;

        return mysqli_insert_id($this->link);
    }


    /**
     * Get number of fields
     *
     * @access public
     * @param query
     * @return int
     */
    public function num_fields($query)
    {
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__;

        $query = $this->link->query($query);
        return mysqli_num_fields($query);
        mysqli_free_result($query);
    }

    /**
     * Get field names associated with a table
     *
     * @access public
     * @param query
     * @return array
     */
    public function list_fields($query)
    {
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__;

        $query = $this->link->query($query);
        return mysqli_fetch_fields($query);
        mysqli_free_result($query);
    }



    /**
     * Truncate entire tables
     *
     * @access public
     * @param array database table names
     * @return int number of tables truncated
     *
     */
    public function truncate($tables = array())
    {
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__;

        if (!empty($tables)) {
            $truncated = 0;
            foreach ($tables as $table) {
                $truncate = "TRUNCATE TABLE `" . trim($table) . "`";
                mysqli_query($this->link, $truncate);
                if (!mysqli_error($this->link)) {
                    $truncated++;
                }
            }
            return $truncated;
        }
    }


    /**
     * Output results of queries
     *
     * @access public
     * @param string variable
     * @param bool echo [true,false] defaults to true
     * @return string
     *
     */
    public function display($variable, $echo = true)
    {
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__;

        $out = '';
        if (!is_array($variable)) {
            $out .= $variable;
        } else {
            $out .= '<pre>';
            $out .= print_r($variable, TRUE);
            $out .= '</pre>';
        }
        if ($echo === true) {
            echo $out;
        } else {
            return $out;
        }
    }


    /**
     * Disconnect from db server
     * Called automatically from __destruct function
     */
    public function disconnect()
    {
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__;

        mysqli_close($this->link);
    }
}
