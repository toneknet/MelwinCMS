<?php
// synk
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
 * Description of login
 *
 * Måste bygga om denna så att den klarar ifall inte SESSIONS är startat! Skall
 * då ge något fel!
 *
 * @class Auth
 * @author Martin Tonek <martin@tonek.se>
 */
class auth
{
    //put your code here

    private $db;
    ///Condition 1 - Presence of a static member variable
    private static $_instance = null;

    ///Condition 2 - Locked down the constructor
    private function  __construct()
    {
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__;
        $this->db = db::getInstance();
    } //Prevent any oustide instantiation of this class

    ///Condition 3 - Prevent any object or instance of that class to be cloned
    private function  __clone() {} //Prevent any copy of this object

    ///Condition 4 - Have a single globally accessible static method
    public static function getInstance()
    {
        if (!is_object(self::$_instance))  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
            self::$_instance = new auth();
        return self::$_instance;
    }
    // 

    private $name = "hejsan";
    private $length = 30;


    public static $userData = [];
    private static $data = [];
    private static $sgroups = NULL; // NOT USED?

    private $userid = 0;
    private $groups = array();
    private $userandcompany = array();
    private static $admin = NULL; // NOT USED?

    private static $uac = []; // NOT USED: Wrapper for UserAndCompany

    private $superadmin = 0;
    private $changePassword = 0;
    private $haslogged = false;
    //private $arrAccessSuperUser = array();

    /**
     * Checks the db if user and pass exist
     * @class Auth
     * @function checkLogin
     *
     * @param string $strUser Username to check
     * @param string $strPass Password to check
     * @return int 1 = inloggad  0 = ej inloggad
     */
    private function checkLogin($strUser, $strPass)
    {
        // die(__FUNCTION__);
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__;
        $db = db::getInstance();
        //print salt($strPass);
        // FIXED 20210817 - La till så att man kan logga in med epost adress med
        $arrAnswer = $db->o_get_all('user', "SELECT id,user,securepass,changepwdnextlogin,superadmin, namn, email FROM [table] WHERE securepass='" . salt($strPass) . "' AND deleted=0");
        // FROM www
        // $arrAnswer = $db->o_get_all('user', "SELECT a.id,a.user,a.securepass,a.changepwdnextlogin,a.superadmin, a.namn, a.email FROM [table] as a LEFT JOIN " . $db->o_prefix('company') . " as b ON a.companyid=b.id WHERE a.securepass='" . salt($strPass) . "' AND a.deleted=0");
        // FIXED 20150315 - fler kan ha samma lösenord, åtgärdat
        if (count($arrAnswer) > 0) {
            #print "#{$strUser}#";
            foreach ($arrAnswer as $id => $value) {
                // print __LINE__ . " :: " . count($arrAnswer) . " :: " . $arrAnswer[$id]['user'] . " :: " . $strUser . "<br>";
                #print $id . " - " . $arrAnswer[$id]['user'] . " - " . $arrAnswer[$id]['email'] . "<br />";
                if ($arrAnswer[$id]['user'] == $strUser || $arrAnswer[$id]['email'] == $strUser) {
                    // 20250521 - Flyttade för att bara sätta den om användare finns
                    $this->userid = $id;
                    // print "OK";
                    $this->setUserData($arrAnswer[$id]);
                    $ref = salt($strUser . time(), $this->length);
                    $_SESSION[$this->name]['ref'] = $ref;
                    // 20170219 - Lägger till om man ska byta lösenord
                    // $_SESSION['li']['cpl'] = boolval($arrAnswer[$id]['changepwdnextlogin']);
                    $this->changePassword = boolval($arrAnswer[$id]['changepwdnextlogin']);
                    // Uppdaterar databas
                    if ($db->o_update('user', array('referens' => $ref), " id={$id} ")) {
                        if ($arrAnswer[$id]['superadmin']) {
                            $this->superadmin = 1;
                        }
                        return 1;
                    }
                }
            }
        }
        setMsg('e', "Användarnamn eller lösenord stämde inte överens!");
        return 0;
    }


    /**
     * Checks if user is logged in or has logged in with form.
     * @return int 1 = inloggad  0 = ej inloggad
     */
    function isAuthed()
    {
        /*
         * Kolla först om inloggning sker om man inte har loggat in
         *
         */
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__;
        $input = new input();

        // Kollar om personen vill logga ut!
        if ($input->get('action') == 'logout') {
            $this->logout();
            header("Location: " . URLBASE);
        }

        // Kollar om person inloggad!
        if ($this->validate()) {
            // $this->log();
            $this->checkPasswordChange();
            return 1;
        }

        // Kollar om inloggningsformuläret skickats
        if ($input->get('action') == 'login' && $input->post('username') != "" && $input->post('password') != "") {
            if ($this->checkLogin($input->post('username'), $input->post('password'))) {
                $this->checkPasswordChange();
                // 2026-04-24 : Fixar så att man hamnar på startsidan efter inloggning
                header('Location: '. getURL([],true));
                exit;
                return 1;
            }
        }
        // Om ingen av ovan har hänt så är personen inte inloggad
        return 0;
    }

    /**
     * Checks if need to change password
     *
     * @return void
     */
    function checkPasswordChange()
    {
        // 20240313 - Fixar redan här med byte av lösenord?
        if ($this->changePassword) {
            $tpl = new template();
            ob_start();
            $tpl->changePassword();
            $tpl->main = ob_get_contents();
            // Rensar utskrift
            ob_end_clean();
            $tpl->publish();
            die();
        }
    }

    /**
     * Kollar om användaren är superuser
     * @return int  1 = access  0 = ej access
     */
    function isSuperUser()
    {
        // First check if userdata is set!!
        if ($this->userid <> 0) {
            return $this->superadmin;
        } else {
            $this->isAuthed();
            return $this->superadmin;
        }
        return 0;
        /*print "U:".$this->userid."<br />";
          print "S:".$this->superadmin."<br />";
  
          if (isset($this->userandcompany['superuser'])) { return $this->userandcompany['superuser'];}
          //         $this->userandcompany();
          if ($this->isAuthed()) {
              $arr = $this->getUserAndCompany();
              if (isset($this->userandcompany['superuser'])) { return $this->userandcompany['superuser']; }
          }
          return 0;*/
    }

    public static function isAdmin()
    {
        // if (!is_null(self::$admin)) return self::$admin;

        // // if (is_null(self::$sgroups)) self::getAccess();


        // // print "<pre>";
        // // var_dump(self::$sgroups);
        // // print_r(self::$sgroups);
        // // print "</pre>";


        // if (count(self::$uac) > 2 && isset(self::$uac['company_settings'])) {
        //     // Är inte array men vi kollar iaf
        //     $settings = self::$uac['company_settings'];
        //     if (!is_array($settings)) {
        //         $settings = unserialize($settings);
        //     }
        //     print "<pre>";
        //     print_r($settings);
        //     print_r(self::$uac);
        //     print "</pre>";
        // }
        return false;
    }

    /**
     * Kollar om användaren har access till funktioner och moduler
     * @return int 1 = access  0 = ej access
     */
    function isAllowed()
    {
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__;
        return 1;
    }

    /**
     * Logout the user
     * @return int 1 = inloggad, 0 = ej inloggad
     */
    function logout()
    {
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__;
        $db = db::getInstance();
        //            $db = new db();
        $strReferens = $_SESSION[$this->name]['ref'];
        unset($_SESSION[$this->name]);
        unset($this->name);
        $this->userid = 0;
        if (strlen($strReferens) == $this->length) {
            $arrAnswer = $db->o_get_all('user', "SELECT * FROM [table] WHERE referens='{$strReferens}' AND deleted=0");
            if (count($arrAnswer) == 1) {
                $id = current(array_keys($arrAnswer));
                $db->o_update('user', array('referens' => ''), " id={$id} ");
            }
        }
        return 1;
    }

    /**
     * Kollar med session om användaren är inloggad
     * @return int 1 = inloggad, 0 = ej inloggad
     */
    private function validate()
    {
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__;
        if ($this->userid > 0) {
            // $this->log();
            return 1;
        }
        if (isset($_SESSION[$this->name])) {
            // Kolla med db om ok!
            $db = db::getInstance();
            //            $db = new db();
            //            RISK FÖR INTRÅNG HÄR!
            $strReferens = $_SESSION[$this->name]['ref'];
            // Måste man kolla att användaren finns?
            // $arrAnswer = $db->o_get_all('user', "SELECT * FROM [table] WHERE referens='{$strReferens}' AND deleted=0");
            // 2024-11-20 - Added company name in user stuff
            $arrAnswer = $db->o_get_all('user', "SELECT a.*,b.namn as company FROM [table] as a LEFT JOIN " . $db->o_prefix('company') . " as b ON a.companyid=b.id WHERE a.referens='{$strReferens}' AND a.deleted=0");
            if (count($arrAnswer) == 1) {
                $id = current(array_keys($arrAnswer));
                // 20240419 - Lägger till static med användarens data
                // id,user,namn,groups,superadmin,foretag,email
                $this->setUserData($arrAnswer[$id]);
                // 20170219 - Lägger till om man ska byta lösenord
                // $_SESSION['li']['cpl'] = boolval($arrAnswer[$id]['changepwdnextlogin']);
                $this->changePassword = boolval($arrAnswer[$id]['changepwdnextlogin']);
                $this->userid = $id;
                // Fixar superuser
                $this->superadmin = $arrAnswer[$id]['superadmin'];

                //$GLOBALS['DEBUG'][] = "Superadmin: " . $arrAnswer[$id]['superadmin'];
                // 20150325 - Läggas till info från getUserAndCompany()?
                // $this->log();
                return 1;
            }
            return 0;
        } else {
            return 0;
        }
    }


    function setUserData($data)
    {
        self::$data = $data;
        // Remove this fields
        $x = array_flip(array('pass', 'referens', 'securepass', 'ordning', 'ordinary', 'lostpassword', 'lostpasswordTime', 'changepwdnextlogin', '_foretag', 'driverid', 'drivercard'));

        self::$userData = array_diff_key($data, $x);
        // print "<pre>";
        // print_r();
        // print "</pre>";
    }

    static function checkPassword($pass)
    {
        if (is_array(self::$data) && isset(self::$data['securepass'])) {
            if (self::$data['securepass'] == salt($pass)) {
                return true;
            }
        }
        return false;
    }


    /**
     * Denna funktion skall hämta grupperna och accessen samt migrera ihop dem.
     * Är det så att man har access i en grupp och i en annan inte så får man
     * automatiskt access då en '1' går över en '0'.
     */
    function getAccess()
    {
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__;
        if ($this->userid > 0) {
            // Är superuser?
            $db = db::getInstance();
            if ($this->isSuperUser()) {
                if (count($this->groups) == 0) {
                    // Load superusers accessw
                    $arrPages = $db->o_get_all("page", "SELECT * FROM [table] WHERE visible=1 AND deleted=0 ORDER BY ordning");
                    // 20231215 - Tog bort denna för att fixa install funktion. SKALL ROOT GÖRAS MED EN GÅNG?
                    //if (count($arrPages)==0) {print "ERROR! NO PAGES EXIST!<br> APPLICATION WILL EXIT NOW!"; exit;}
                    $arrSettings = array();
                    foreach ($arrPages as $value) {
                        $arrSettings[$value['id']]['allow'] = 1;
                        $arrSettings[$value['id']]['edit'] = 1;
                    }
                    $this->groups = $arrSettings;
                    //$GLOBALS['DEBUG'][] = $arrSettings;
                }
            }
            // Is saved
            if (count($this->groups) > 0) {
                return (array) $this->groups;
            }
            // Först user
            $db = db::getInstance();
            $arrUser = $db->o_get_row('user', 'SELECT groups FROM [table] WHERE id=' . $this->userid . " AND deleted=0");
            $arrGroups = unserialize($arrUser['groups']);
            // FIX! För att få full access!!
            //            $arrGroups[7]=1;
            //print serialize($arrGroups);
            //            print "<pre>";
            //            print_r($arrGroups);
            //            print "</pre>";

            // Sedan groups
            if (!is_array($arrGroups)) {
                return (array) array();
            }
            // FIXED: 20150307 - Så att man alltid har en grupp som heter allmän
            // gemensam. Dock ej synlig i gruppvalet
            //            $arrWhere[] = "`id` = '$field'";
            // Skapar Where
            foreach ($arrGroups as $field => $value) {
                $arrWhere[] = "`id` = '$field'";
            }
            $strWhere = implode(' OR ', $arrWhere);
            $arrGroup = $db->o_get_all('groups', 'SELECT * FROM [table] WHERE ( ' . $strWhere . ' )  AND deleted=0');

            //            print "<pre>";
            //            print_r(unserialize($arrGroup[7]['settings']));
            //            print "</pre>";



            //                        print "<pre>";
            //                        print_r($svalue);
            //                        print "</pre>";

            foreach ($arrGroup as $key => $value) {
                // Gör array
                $arrTmp = unserialize($value['settings']);
                // Här ska mergas
                foreach ($arrTmp as $skey => $svalue) {
                    if ($skey == 11) {
                        //                        print "<pre>";
                        //                        print_r($svalue);
                        //                        print "</pre>";
                    }
                    if (intval($svalue['allow']) == 1) {
                        $arrSettings[$skey]['allow'] = 1;
                    }
                    if (intval($svalue['edit']) == 1) {
                        $arrSettings[$skey]['edit'] = 1;
                    }
                }
            }
            //            print "<pre>";
            //            print_r($arrSettings);
            //            print "</pre>";

            $this->groups = $arrSettings;
            self::$sgroups = $arrSettings;
        }
        return (array) $arrSettings;
    }

    private function log()
    {
        // pre($this->haslogged);
        if (!isset($GLOBALS['CONFIG']['allow_log']) || $GLOBALS['CONFIG']['allow_log'] == false) return;
        // pre($this->haslogged);
        if ($this->haslogged) return;
        $db = db::getInstance();
        // Loggar här
        $report['URL'] = $_SERVER['REQUEST_URI'];
        $report['POST'] = $_POST;
        $report['GET'] = $_GET;
        //$report['SESSION'] = $_SESSION;
        $report['COOKIE'] = $_COOKIE;

        // UNSET if LOGIN
        if (isset($_GET['action']) && $_GET['action'] == 'login' && isset($report['POST']['username']) && isset($report['POST']['password'])) {
            $report['POST']['username'] = "**********";
            $report['POST']['password'] = "**********";
        }

        // pre($db->o_prefix('logs'));

        $this->haslogged = $db->o_insert(
            'logs',
            array(
                'uid' => $this->userid,
                'action' => serialize($report)
            )
        );
        $this->haslogged = true;
    }


    /**
     * Retrives Username and company name
     *
     * @return array Userinfo
     */
    public function getUserAndCompany()
    {
        /*
         * return = array('name' => 'Martin Tonek','company' => 'Hallens Buss AB');
         */
        //        debugUtils::callStack(debug_backtrace());
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__;
        //$GLOBALS['DEBUG'][] = $this->userandcompany;
        // 20250402 - Disabled for test purposes only when building isAdmin function
        if (count($this->userandcompany) > 2) {
            return (array) $this->userandcompany;
        }
        $GLOBALS['DEBUG'][] = __FUNCTION__ . " :: Fanns inte så hämtar. Nuvarande ID: " . $this->userid;
        if ($this->userid > 0) {
            $db = db::getInstance();
            // 2025-05-21 From www
            $prefix = $GLOBALS['CONFIG']['dbPrefix'];
            $arrUser = $db->o_get_all('', "SELECT {$prefix}user.id as id, {$prefix}user.groups as groupsx, {$prefix}user.groups as groups, {$prefix}user.superadmin as superuser, {$prefix}user.namn as name, {$prefix}user.settings as settings, {$prefix}company.namn as company, {$prefix}company.id as companyid, {$prefix}company.dbprefix as dbprefix, {$prefix}company.settings as company_settings FROM {$prefix}user, {$prefix}company WHERE {$prefix}user.companyid = {$prefix}company.id AND {$prefix}user.id=" . $this->userid, 'id', false);
            // $arrUser = $db->o_get_all('', "SELECT " . $GLOBALS['CONFIG']['dbPrefix'] . "user.id as id, " . $GLOBALS['CONFIG']['dbPrefix'] . "user.superadmin as superuser, " . $GLOBALS['CONFIG']['dbPrefix'] . "user.namn as name, " . $GLOBALS['CONFIG']['dbPrefix'] . "company.namn as company, " . $GLOBALS['CONFIG']['dbPrefix'] . "company.id as companyid, " . $GLOBALS['CONFIG']['dbPrefix'] . "company.dbprefix as dbprefix, " . $GLOBALS['CONFIG']['dbPrefix'] . "company.settings as company_settings FROM " . $GLOBALS['CONFIG']['dbPrefix'] . "user, " . $GLOBALS['CONFIG']['dbPrefix'] . "company WHERE " . $GLOBALS['CONFIG']['dbPrefix'] . "user.companyid = " . $GLOBALS['CONFIG']['dbPrefix'] . "company.id AND " . $GLOBALS['CONFIG']['dbPrefix'] . "user.id=" . $this->userid, 'id', false);
            if (isset($arrUser[$this->userid]['groups']) && $arrUser[$this->userid]['groups'] <> "" && !is_array($arrUser[$this->userid]['groups'])) {
                $arrG = unserialize($arrUser[$this->userid]['groups']);
                if (is_array($arrG)) {
                    $arrUser[$this->userid]['groups'] = $arrG;
                } else {
                    $arrUser[$this->userid]['groups'] = NULL;
                }
            }
            $this->userandcompany = $arrUser[$this->userid];
            unset($this->userandcompany['groupsx']);

            // 20250602 - Fixade settings, eller?
            // var_dump($arrUser['settings']);
            if (isset($this->userandcompany['settings']) && !is_array($this->userandcompany['settings'])) {
                $this->userandcompany['settings'] = unserialize($this->userandcompany['settings']);
            }





            // BUG : 20250403 - Varför kollar jag om en sträng är en array?
            // if (is_array($this->userandcompany['company_settings'])) {
            //     // Varför skulle jag vilja göra en unserialize på denna?
            //     if (isSuperUser()) pre($this->userandcompany['company_settings']);
            //     $this->userandcompany['company_settings'] = unserialize($this->userandcompany['company_settings']);
            // }
            // FIX :
            if (isset($this->userandcompany['company_settings']) && !is_array($this->userandcompany['company_settings'])) {
                $this->userandcompany['company_settings'] = unserialize($this->userandcompany['company_settings']);
            }

            // print "<pre>";
            // print_r($this->userandcompany);
            // print "</pre>";


            // Fixa dynamiskt prefix här!
            $GLOBALS['CONFIG']['dbPrefix_private'] = $this->userandcompany['dbprefix'];
            $GLOBALS['DEBUG'][] = $this->userandcompany;
            if ($arrUser[$this->userid]['superuser'] == 1) {
                if (isset($_SESSION['hbook']['suci'])) {
                    $intCID = intval($_SESSION['hbook']['suci']);
                    $arrCompany = $db->o_get_all('company', "SELECT * FROM [table] WHERE deleted=0 ");
                    $this->userandcompany['companyid'] = $intCID;
                    $this->userandcompany['company'] = $arrCompany[$intCID]['namn'];
                    $this->userandcompany['company_settings'] = unserialize($arrCompany[$intCID]['settings']);

                    // 20230313 - La till dbprefixet här med så att allt ska visas korrekt
                    $this->userandcompany['dbprefix'] = $arrCompany[$intCID]['dbprefix'];
                    $GLOBALS['CONFIG']['dbPrefix_private'] = $this->userandcompany['dbprefix'];
                    $_SESSION['hbook']['suci'] = $intCID;
                    /*                        print "<pre>";
                        print_r($this->userandcompany);
                        print_r($arrCompany);
                        print "</pre>";*/
                }
            }
            self::$uac = $this->userandcompany;
            // 20180618 - Avmarkerar denna
            //if ($this->userandcompany['superuser']==1) { $GLOBALS['CONFIG']['displayDebug'] = true; }
            $this->log();
            return (array) $this->userandcompany;
        } else {
            // Not logged in !!
            $GLOBALS['CONFIG']['displayDebug'] = false;
        }
    }
}
