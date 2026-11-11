<?php

/**
Name: Login class
Author: Martin Tonek
Author URI: https://www.tonek.se
Description: Handles the login, signup and lost password functionality.
Version: 1.0
Type: Library
 */

class login
{

  private $frmMsg;
  private $code;

  function __construct()
  {
    // ini_set('display_errors', 1);
    // ini_set('display_startup_errors', 1);
    // error_reporting(E_ALL);
    /*
          If this file loads, we have to be logged out

          STANDARD GROUP ???????

          STATES
          ---------------------------
          x login form
          - process login (this is handled in auth lib)

          x lost password form
          process lostpassword

          x signup form
          x process signup form


          TODO
          ----------------------------
          * Remove functions template
          * Remove login auth from auth class ?

      */
    // Bygga in så att man inte ska kunna spamma denna fil?
    $i = new input();
    $action = $i->get('action');
    $sub = $i->get('subaction');
    $p = $i->postAll();
    // Login is processed by auth for now
    if ($action == 'lostpassword') {
      $code = $i->get('code');
      $g = $i->getAll();
      // Submitted with math
      if ($sub == 'ask') {
        $this->checkLostPasswordAsk($p);
        // Link to change pwd
      } elseif ($sub == 'retrieve' && !empty($code)) {
        $this->retrivePassword($g, $p);
        // die();
      }
      $this->showLostpasswordForm();
      die();
      // Check signup
    } elseif ($action == 'signup') {
      if ($sub == 'register') {
        $this->checkSignUp($p);
      }
      $this->showSignupForm();
      // header("Refresh:0");
    } else {
      $this->showLoginForm();
    }
    die();
  }

  /**
   * Show login form
   *
   * @return void
   */
  function showLoginForm()
  {
    $tpl = new template('login');
    $tpl->add('frmMsg', $this->frmMsg);
    $tpl->add('register_links', $this->getLostpasswordLink());
    $tpl->add('register_links', $this->getSignupLink());
    $tpl->publish();
  }

  /**
   * Show signup form
   *
   * @return void
   */
  function showSignupForm()
  {
    $tpl = new template('signup');
    $tpl->add('frmMsg', $this->frmMsg);
    $tpl->add('register_links', $this->getReturnLink());
    $tpl->publish();
  }

  /**
   * Show lost password form
   *
   * @return void
   */
  function showLostpasswordForm()
  {
    $tpl = new template('lostpassword');
    $tpl->add('frmMsg', $this->frmMsg);
    $tpl->add('register_links', $this->getReturnLink());
    // Skapa password här!
    $x = rand(0, 9);
    $y = rand(0, 9);
    $z = rand(0, 1);
    if ($z == 0) {
      // +
      $math = sprintf($GLOBALS['LANG']['login']['mathAdd'], $x, $y);
      $sum = $x + $y;
    } else {
      // -
      $math = sprintf($GLOBALS['LANG']['login']['mathSub'], $x, $y);
      $sum = $x - $y;
    }
    $tpl->add('math', $math);
    $_SESSION['matte'] = salt(date("Y-m-d") . ($sum));
    $tpl->publish();
  }

  /**
   * Show password form
   *
   * @return void
   */
  function showChangePasswordForm()
  {
    $tpl = new template('confirmpassword');
    $tpl->add('code', $this->code);
    $tpl->add('frmMsg', $this->frmMsg);
    $tpl->add('register_links', $this->getReturnLink());
    $tpl->publish();
  }

  /**
   * Creates and retrive signup link
   *
   * @return void
   */
  function getSignupLink()
  {
    if (isset($GLOBALS['CONFIG']['allow_signup']) && $GLOBALS['CONFIG']['allow_signup'] == 1) {
      return '<a href="?action=signup" class="login__subs__register">' . $GLOBALS['LANG']['login']['signup'] . '</a>';
    }
    return;
  }

  /**
   * Creates and retrive password link
   *
   * @return void
   */
  function getLostpasswordLink()
  {
    if (isset($GLOBALS['CONFIG']['allow_lostpassword']) && $GLOBALS['CONFIG']['allow_lostpassword'] == 1) {
      return '<a href="?action=lostpassword" class="login__subs__forget">' . $GLOBALS['LANG']['login']['lostpassword'] . '</a>';
    }
    return;
  }

  /**
   * Creates and retrive return/cancel link
   *
   * @return void
   */
  function getReturnLink()
  {
    return '<a href="?action=" class="login__subs__register">' . $GLOBALS['LANG']['GENERAL']['cancel'] . '</a>';
  }


  /**
   * Check for errors and if okey lets register user.
   *
   * @param array $p The POST 
   * @return void
   */
  function checkSignUp($p)
  {
    // Empty fields
    if (empty($p['email']) || empty($p['username']) || empty($p['password1']) || empty($p['password2'])) {
      $this->frmMsg .= "<p class='frmError'>" . $GLOBALS['LANG']['login']['error_empty'] . "</p>";
      // Check email
    } elseif (self::is_email_invalid($p['email'])) {
      $this->frmMsg .= "<p class='frmError'>" . $GLOBALS['LANG']['login']['error_email'] . "</p>";
      // Check password match and valid chars
    } elseif (self::is_password_invalid($p['password1'], $p['password2'])) {
      $this->frmMsg .= "<p class='frmError'>" . $GLOBALS['LANG']['login']['error_password'] . "</p>";
      // Check if email exist 
    } elseif ($this->do_email_exist($p['email'])) {
      $this->frmMsg .= "<p class='frmError'>" . $GLOBALS['LANG']['login']['error_email_exist'] . "</p>";
      // Check if username exist
    } elseif ($this->do_user_exist($p['username'])) {
      $this->frmMsg .= "<p class='frmError'>" . $GLOBALS['LANG']['login']['error_user_exist'] . "</p>";
      // Else it should be OKEY!
    } else {
      $db = db::getInstance();
      if ($db->o_insert('user', array('user' => $p['username'], 'securepass' => salt($p['password1']), 'email' => $p['email']))) {
        $this->frmMsg .= "<p class='frmSuccess'>" . $GLOBALS['LANG']['login']['signup_success'] . "</p>";
      } else {
        $this->frmMsg .= "<p class='frmError'>" . $GLOBALS['LANG']['GENERAL']['error'] . "</p>";
      }
    }
  }

  /**
   * Check if asking is legit. Then send an email to user.
   *
   * @param array $p The POST
   * @return void
   */
  function checkLostPasswordAsk($p)
  {
    // Empty fields
    if (empty($p['username']) || empty($p['solution'])) {
      $this->frmMsg .= "<p class='frmError'>" . $GLOBALS['LANG']['login']['error_empty'] . "</p>";
      // Math isn't correct
    } elseif (salt(date("Y-m-d") . $p['solution']) != $_SESSION['matte']) {
      $this->frmMsg .= "<p class='frmError'>" . $GLOBALS['LANG']['login']['error_math'] . "</p>";
      // Check if email is valid         
    } elseif (self::is_email_invalid($p['username'])) {
      $this->frmMsg .= "<p class='frmError'>" . $GLOBALS['LANG']['login']['error_email'] . "</p>";
      // Does email exist
    } elseif (!$this->do_email_exist($p['username'])) {
      $this->frmMsg .= "<p class='frmError'>" . $GLOBALS['LANG']['login']['error_email'] . "</p>";
      // Skicka epost
    } else {
      $db = db::getInstance();
      $arrUser = $db->o_get_row('user', "SELECT id,email,user,namn FROM [table] WHERE email = '" . $p['username'] . "' AND deleted=0");
      if (count($arrUser) >= 1) {
        $code = salt($arrUser['email'] . $arrUser['user'] . $arrUser['id']);
        $db->o_update("user", array('lostpassword' => $code, 'lostpasswordTime' => date("Y-m-d H:i:s")), " id=" . $arrUser['id']);
        $tmpUrl = URLBASE . "?action=lostpassword&subaction=retrieve&code=" . $code;
        $strMsg = sprintf($GLOBALS['LANG']['login']['email_retrive'], $tmpUrl, $tmpUrl);
        // Byter ut sendmail till nya här!
        // sendMail($arrUser['email'], $GLOBALS['LANG']['login']['email_retrive_subject'], $strMsg);
        try {
          $mail = new mail();
          $mail->addAddresses($arrUser['email']);
          $mail->Subject = $GLOBALS['LANG']['login']['email_retrive_subject'];
          $mail->msgHTML(nl2br($strMsg));
          if (!$mail->GO()) {
            $this->frmMsg .= "<p class='frmError'>" . $GLOBALS['LANG']['GENERAL']['error'] . "</p>";
            return;
          }
        } catch (\Throwable $e) {
          error_log('Password recovery: mail delivery failed (' . get_class($e) . '). Check PHPMailer installation and SMTP configuration.');
          $this->frmMsg .= "<p class='frmError'>" . $GLOBALS['LANG']['GENERAL']['error'] . "</p>";
          return;
        }
        $this->frmMsg .= "<p class='frmSuccess'>" . $GLOBALS['LANG']['login']['lostpassword_success'] . "</p>";
      } else {
        $this->frmMsg .= "<p class='frmSuccess'>" . $GLOBALS['LANG']['login']['lostpassword_success'] . "</p>";
      }
    }
  }

  /**
   * Handles the new password box
   *
   * @param array $g The GET
   * @return void
   */
  function retrivePassword($g, $p)
  {
    // Check valid code
    $code = $g['code'];
    if ($this->is_code_valid($code)) {
      $db = db::getInstance();
      $arrAnswer = $db->o_get_row('user', "SELECT id,email FROM [table] WHERE lostpassword = '{$code}' AND lostpasswordTime >= (NOW() - INTERVAL 1 HOUR) AND deleted=0");
      if (is_array($arrAnswer) && count($arrAnswer) >= 1) {
        // Consume the code only when the new password is saved successfully.
        $this->code = $code;
        // Check for SUBMIT AND if email is correct
        if (isset($p['email']) && isset($p['password1']) && isset($p['password2']) && $p['email'] == $arrAnswer['email']) {
          // Check password
          if (self::is_password_invalid($p['password1'], $p['password2'])) {
            $this->frmMsg .= "<p class='frmError'>" . $GLOBALS['LANG']['login']['error_password'] . "</p>";
          } else {
            // Delete used lostpassword code and time and set password
            if ($db->o_update('user', array('lostpassword' => '', 'lostpasswordTime' => NULL, 'securepass' => salt($p['password1'])), 'id=' . $arrAnswer['id'])) {
              $this->frmMsg .= "<p class='frmSuccess'>" . $GLOBALS['LANG']['login']['password_success'] . "</p>";
              $this->showLoginForm();
              die();
            }
          }
        }
        // template::_page404();
        // Load Form for new password
        $this->showChangePasswordForm();
        die();
      } else {
        $this->frmMsg .= "<p class='frmError'>" . $GLOBALS['LANG']['login']['lostpassword_exceed'] . "</p>";
      }
    }
  }

  /**
   * Check if email is invalid
   *
   * @param string $email
   * @return boolean
   */
  static function is_email_invalid($email)
  {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Check if password is invalid
   *
   * @param string $p1 Password first
   * @param string $p2 Password second
   * @param boolean $showErrors If it should create errors with setMsg on check
   * @return boolean
   */
  static function is_password_invalid($p1, $p2, $showErrors = false)
  {
    // Check not equal
    if ($p1 != $p2) {
      if ($showErrors) setMsg('w', $GLOBALS['LANG']['login']['error_no_match']);
      return true;
    }

    // Check if not allowed chars
    if (preg_match("/[^A-Za-z0-9.#\\-$]/", $p1)) {
      if ($showErrors) setMsg('w', $GLOBALS['LANG']['login']['error_bad_chars']);
      return true;
    }

    // Length more then 8 chars
    if (strlen($p1) < 8) {
      if ($showErrors) setMsg('w', $GLOBALS['LANG']['login']['error_bad_length']);
      return true;
    }
    // Password is not invalid
    return false;
  }

  /**
   * Checks if code is only letters and numbers
   *
   * @param [type] $code
   * @return boolean
   */
  function is_code_valid($code)
  {
    if (preg_match("/[^a-z0-9]/", $code)) {
      return false;
    }
    return true;
  }

  /**
   * Check if emailadress exists
   *
   * @param string $email
   * @return void
   */
  function do_email_exist($email)
  {
    $db = db::getInstance();
    $arrAnswer = $db->o_get_all('user', "SELECT id,email FROM [table] WHERE email = '{$email}' AND deleted=0");
    if (count($arrAnswer) >= 1) {
      return true;
    }
    return false;
  }

  /**
   * Check if username exists
   *
   * @param string $user
   * @return void
   */
  function do_user_exist($user)
  {
    $db = db::getInstance();
    $arrAnswer = $db->o_get_all('user', "SELECT id,user FROM [table] WHERE user = '{$user}' AND deleted=0");
    if (count($arrAnswer) >= 1) {
      return true;
    }
    return false;
  }


  // static function checkValidAndExistEmail($email) {
  //   if (!self::is_email_invalid($email)) {
  //     return 1;
  //   }
  //   return 0;
  // }

  static function changeEmailValidation()
  {
    // ref
  }
}
