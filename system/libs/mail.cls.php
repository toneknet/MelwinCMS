<?php
/*
Name: Mail (wrapper)
Author: Martin Tonek
Author URI: https://www.tonek.se
Description: Handles mail functions to system
Version: 0.1
Type: Library
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
 * Description
 * A wrapper for mail services
 * 
 * Hur används denna??
 *
 * @author Martin Tonek <martin@tonek.se>
 */

//print __FILE__;

/*require_once ROOTPATH . "resources/phpmailer/vendor/autoload.php";
 //require PLUGINPATH . 'vendor/autoload.php';
 //use PHPMailer\PHPMailer\PHPMailer;
 use PHPMailer\PHPMailer\PHPMailer;
 use PHPMailer\PHPMailer\Exception;
 require_once PLUGINPATH . 'PHPMailer/src/Exception.php';
 require_once PLUGINPATH . 'PHPMailer/src/PHPMailer.php';
 require_once PLUGINPATH . 'PHPMailer/src/SMTP.php';*/


$mailPluginPath = rtrim(PLUGINPATH, '/\\');
if (is_file($mailPluginPath . '/phpmailer/src/PHPMailer.php')) {
  require_once $mailPluginPath . '/phpmailer/src/Exception.php';
  require_once $mailPluginPath . '/phpmailer/src/PHPMailer.php';
  require_once $mailPluginPath . '/phpmailer/src/SMTP.php';
} elseif (is_file($mailPluginPath . '/vendor/autoload.php')) {
  require_once $mailPluginPath . '/vendor/autoload.php';
} else {
  throw new \RuntimeException('PHPMailer saknas. Öppna Beroenden i installations- eller uppdateringsverktyget och välj Hämta och installera.');
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
//require_once PLUGINPATH . 'PHPMailer/src/Exception.php';
//require_once PLUGINPATH . 'PHPMailer/src/PHPMailer.php';
//require_once PLUGINPATH . 'PHPMailer/src/SMTP.php';

/**
 * Mail
 * 
 * Use:
 * 
 *   $mail->Subject = $title;   - Title for mail
 *   $mail->MsgHTML($msg);      - Message for mail
 *   $mail->data['attachment']  - Attachement for mail
 */
class mail extends PHPMailer
{

  public $module = '';
  public $message;
  public $subject;
  public $address = []; // key = emailadress and value = name
  public $data;

  /*function __construct($name = '') {
   parent::__construct($name);
  }*/

   /**
    * Add adresses to mail
    *
    * as array:  ['email'=>'name']
    * as list:   email;email;email
    * as single: email
    *
    * @param mixed $emailTo
    * @return void
    */
  public function addAddresses($emailTo)
  {
    if (is_array($emailTo)) {
      foreach ($emailTo as $key => $value) {
        if (is_int($key)) {
          $this->AddAddress($value);
        } else {
          $this->AddAddress($key, $value);
        }
      }
    } elseif (str_contains($emailTo, ";")) {
      foreach (explode(";", $emailTo) as $value) {
        $this->AddAddress($value);
      }
    } else {
      $this->AddAddress($emailTo);
    }
  }

  public function GO()
  {
    try {
      //Server settings
      //$this->SMTPDebug = SMTP::DEBUG_SERVER;                      // Enable verbose debug output
      $this->isSMTP();                                            // Send using SMTP
      $config = $GLOBALS['CONFIG'];
      // Prefer settings managed by site_settings; retain legacy installations.
      $this->Host = ($config['smtp_host'] ?? '') ?: ($config['SMTP-server'] ?? '');
      $this->Username = ($config['smtp_user'] ?? '') ?: ($config['SMTP-username'] ?? '');
      $this->Password = ($config['smtp_pass'] ?? '') ?: ($config['SMTP-password'] ?? '');
      $this->SMTPAuth = $this->Username !== '';
      $encryption = $config['smtp_encrypt'] ?? 'STARTTLS';
      $this->SMTPSecure = ['' => '', 'SSL' => PHPMailer::ENCRYPTION_SMTPS, 'STARTTLS' => PHPMailer::ENCRYPTION_STARTTLS][strtoupper($encryption)] ?? $encryption;
      $this->Port = (int)(($config['smtp_port'] ?? '') ?: ($config['SMTP-port'] ?? 587));
      //Recipients
      $this->setFrom(($config['smtp_mail'] ?? '') ?: ($config['SMTP-from'] ?? ''), ($config['smtp_from_name'] ?? '') ?: ($config['SMTP-from-name'] ?? ''));
      $bcc = !empty($config['smtp_BCC']) ? $config['smtp_BCC'] : ($config['SMTP-BCC'] ?? []);
      if (is_array($bcc)) {
        foreach ($bcc as $adress => $name) {
          if (is_int($adress)) {
            $this->addBCC($name);
          } else {
            $this->addBCC($adress, $name);
          }
        }
        // $this->addBCC('admin@hallens.se', 'Martin Tonek');
      }
      $this->CharSet = 'utf-8';
      $this->isHTML(true);                                  // Set email format to HTML
      //attachement
      if (isset($this->data['attachment'])) {
        foreach ($this->data['attachment'] as $file => $name) {
          $this->addAttachment($file, $name);         // Add attachments
          // code...
        }
        //              $mail->addAttachment($this->getfileWithFullPath());         // Add attachments
        //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
      }


      if (!$this->send()) {
        error_log('Mail delivery failed: ' . $this->ErrorInfo);
        return false;
      }
      add2cronlog('Message for module ' . $this->module . ' has been sent');
      return true;
    } catch (Exception $e) {
      error_log("Message for module " . $this->module . " could not be sent. Mailer Error: {$this->ErrorInfo}");
      return false;
    }
  }

  public function sendmail() {
    return $this->GO();
  }

  // /**
  //  * Send email (Only supports SMTP credentials)
  //  * DENNA FUNGERAR!
  //  *
  //  * @param array $adress Adresses as values in array
  //  * @param string $title Title for email
  //  * @param string $msg   Message to sender
  //  * @return void
  //  */
  // function send($adress, $title, $msg)
  // {
  //   $mail = new PHPMailer;
  //   $mail->isSMTP();
  //   $mail->SMTPDebug = 0;
  //   $mail->Port = $GLOBALS['CONFIG']['smtp_port']; // typically 587
  //   // print array(''=>'','SSL'=> 'ssl','STARTTLS'=> 'tls')[ $GLOBALS['CONFIG']['smtp_encrypt'] ];
  //   $mail->SMTPSecure = array('' => '', 'SSL' => 'ssl', 'STARTTLS' => 'tls')[$GLOBALS['CONFIG']['smtp_encrypt']]; // ssl is depracated
  //   $mail->SMTPAuth = true;
  //   $mail->Username = $GLOBALS['CONFIG']['smtp_user'];
  //   $mail->Password = $GLOBALS['CONFIG']['smtp_pass'];
  //   $mail->Host = $GLOBALS['CONFIG']['smtp_host'];
  //   $mail->setFrom($GLOBALS['CONFIG']['from_email'], $GLOBALS['CONFIG']['title']);
  //   foreach ($adress as $user) {
  //     $mail->AddAddress($user);
  //   }
  //   $mail->addBCC('admin@tonek.se');
  //   $mail->CharSet = 'utf-8';
  //   $mail->Subject = $title;
  //   $mail->MsgHTML($msg);
  //   //attachement
  //   if (isset($this->data['attachment'])) {
  //     foreach ($this->data['attachment'] as $file => $name) {
  //       $mail->addAttachment($file, $name);         // Add attachments
  //       // code...
  //     }
  //     //              $mail->addAttachment($this->getfileWithFullPath());         // Add attachments
  //     //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
  //   }



  //   //pre($adress);
  //   if ($mail->send()) {
  //     return true;
  //   } else {
  //     return false;
  //   }
  // }


}
