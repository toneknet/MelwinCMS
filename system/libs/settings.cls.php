<?php

/**
 * Undocumented class
 */
// TODO: Denna klass fungerar inte när man inte är inloggad.
class settings
{

  static $companySettings;
  // static $forcedCompany = NULL;

  static function setSettings()
  {
    // print __FUNCTION__;
    if (!is_array(self::$companySettings)) {
      // Load
      // print __FUNCTION__;
      $auth = auth::getInstance();
      $a = $auth->getUserAndCompany();
      // pre($a);
      // if (!is_array($a)) {
      //   // $db = db::getInstance();
      //   // if($db->forcePrefixID != "") {
      //   //   return $db->forcePrefixID;
      //   // } else {
      //   //   return '0';
      //   // }
      // }
      if (isset($a['company_settings']) && !is_null($a['company_settings']) && !is_array($a['company_settings']) && $a['company_settings'] <> "") {
        // print $a['company_settings'];
        self::$companySettings = unserialize($a['company_settings']);
      } elseif (isset($a['company_settings']) && !is_null($a['company_settings']) && is_array($a['company_settings'])) {
        self::$companySettings = $a['company_settings'];
      }
    }
    // print "<pre>";
    // print_r(self::$companySettings);
    // print "</pre>";
  }

  /**
   * Get company related settings from db
   *
   * @param array $arrKey       Use as ['module', 'buscalender', 'summary']
   * @param boolean $showDebug  Not used
   * @return mixed
   */
  static function getSettings(array $arrKey = [], $showDebug = false): mixed
  {
    self::setSettings();
    $s = self::$companySettings;
    if ($showDebug) pre($s);
    if (is_array($arrKey) && count($arrKey) > 0) {
      foreach ($arrKey as $key) {
        if (isset($s[$key])) {
          $s = $s[$key];
        } else {
          return NULL;
        }
      }
    }
    if ($s <> "") {
      return $s;
    }
    return NULL;
  }


  static function getOptionsFromString(array $arrKey): array
  {
    self::setSettings();

    // pre( self::$companySettings);
    // pre($auth::$userData);

    $s = self::$companySettings;
    // $s = getCompanySettings(); //['module']['verkstad'];
    // var_dump($arrKey);
    // pre($s);

    foreach ($arrKey as $key) {
      if (isset($s[$key])) {
        $s = $s[$key];
      } else {
        return array('ERROR');
      }
    }
    // print $s;

    if ($s <> "") {
      //var_dump($s);
      $s = $GLOBALS['LANG']['GENERAL']['noentry'] . "\n" . $s;
      return explode("\n", $s);
    }
    return array('NOTHING');
  }

  static function showSettings()
  {
    self::setSettings();
    pre(self::$companySettings);
  }

  /**
   *  Get USER settings here?
   */

  /**
   * Get the USERS setting from the db if field exists, else return NULL
   *
   * @param string $key
   * @return string $answer
   */
  static function getUserSettings($key) {
    $data = auth::$userData;
    $answer = NULL;
    if (isset($data[$key])) $answer = $data[$key];
    return $answer;
  }

  /**
   * Set the USERS settings to db if field exists.
   *
   * @param string $key
   * @param string $value
   * @return bool
   */
  static function setUserSettings($key,$value) {
    // Check for key exist and not empty
    if (is_null($key) || empty($key) ) return false;
    // Check for safe fields
    if (in_array($key, ['id','user','pass','namn','groups','superadmin','referens','securepass','grupp','ordning','ordinary','active','deleted','email','companyid','lostpassword','lostpasswordTime','changepwdnextlogin'])) return false;
    // Hur spara ??
    $db = db::getInstance();
    $id = getUserID();
    // print $uid;
    // check if field exists
    if ($db->o_update('user', array($key => $value), " id={$id} ")) {
      return true;
    }
    return false;

  }












  // static function getCompanySettings() {
  //   //Hämta companyid här!
  //   $auth = auth::getInstance();
  //   $a = $auth->getUserAndCompany();
  //   // Denna if sats med underlänkar är en buggfix för form o ajax. 220503
  //   if(!is_array($a)) {
  //     $db = db::getInstance();
  //     if($db->forcePrefixID != "") {
  //       return $db->forcePrefixID;
  //     } else {
  //       return '0';
  //     }
  //   }
  //   return $a['company_settings'];
  // }   

}
