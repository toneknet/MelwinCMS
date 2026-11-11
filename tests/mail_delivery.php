<?php
// No real database or SMTP connection is used.
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
function check($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
function add2cronlog($message) {}
$root = dirname(__DIR__);
$mode = $argv[1] ?? 'mail';
if ($mode === 'missing') {
    define('PLUGINPATH', __DIR__ . '/nonexistent-plugins/');
    try {
        require $root . '/system/libs/mail.cls.php';
        throw new LogicException('Missing dependency was accepted');
    } catch (RuntimeException $e) {
        check(strpos($e->getMessage(), 'Beroenden') !== false, 'Missing installation guidance');
    }
} elseif ($mode === 'recovery') {
    class mail {
        public static $result = true;
        public static $last;
        public $Subject;
        public $recipient;
        public $body;
        public function __construct() { self::$last = $this; }
        public function addAddresses($value) { $this->recipient = $value; }
        public function msgHTML($value) { $this->body = $value; }
        public function GO() {
            if (self::$result === 'throw') throw new RuntimeException('Missing dependency');
            return self::$result;
        }
    }
    class db {
        public static $writes = [];
        public static function getInstance() { return new self; }
        public function o_get_row($table, $query) {
            return ['id' => 1, 'email' => 'user@example.com', 'user' => 'user', 'namn' => 'Test'];
        }
        public function o_update($table, $values, $where) { self::$writes[] = $values; return true; }
    }
    function salt($value) { return hash('sha256', $value); }
    define('URLBASE', 'https://example.com/');
    require $root . '/system/libs/login.cls.php';
    class FormRendered extends RuntimeException {}
    class RecoveryLogin extends login {
        public function do_email_exist($email) { return true; }
        public function showChangePasswordForm() { throw new FormRendered; }
        public function showLoginForm() { throw new FormRendered; }
    }
    $GLOBALS['LANG']['login'] = [
        'email_retrive' => '%s %s', 'email_retrive_subject' => 'Reset password',
        'lostpassword_success' => 'Request accepted', 'error_password' => 'Invalid password',
        'password_success' => 'Password changed'
    ];
    $GLOBALS['LANG']['GENERAL']['error'] = 'Delivery failed';
    $_SESSION['matte'] = salt(date('Y-m-d') . '4');
    foreach ([true, false, 'throw'] as $result) {
        mail::$result = $result;
        $login = (new ReflectionClass('RecoveryLogin'))->newInstanceWithoutConstructor();
        $login->checkLostPasswordAsk(['username' => 'user@example.com', 'solution' => '4']);
        $property = new ReflectionProperty('login', 'frmMsg');
        $message = $property->getValue($login);
        check(strpos($message, $result === true ? 'Request accepted' : 'Delivery failed') !== false, 'Wrong recovery status');
        check(mail::$last->recipient === 'user@example.com', 'Wrong recipient');
        check(mail::$last->Subject === 'Reset password', 'Missing subject');
        check(strpos(mail::$last->body, 'subaction=retrieve') !== false, 'Missing reset link');
    }
    foreach ([[], ['email' => 'user@example.com', 'password1' => 'short', 'password2' => 'short'],
        ['email' => 'user@example.com', 'password1' => 'Password123', 'password2' => 'Password123']] as $post) {
        db::$writes = [];
        $login = (new ReflectionClass('RecoveryLogin'))->newInstanceWithoutConstructor();
        try { $login->retrivePassword(['code' => 'abc123'], $post); }
        catch (FormRendered $e) {}
        $valid = ($post['password1'] ?? '') === 'Password123';
        check(count(db::$writes) === ($valid ? 1 : 0), 'Reset code consumed before password saved');
        if ($valid) {
            check(db::$writes[0]['lostpassword'] === '' && db::$writes[0]['lostpasswordTime'] === null, 'Used reset code retained');
            check(db::$writes[0]['securepass'] === salt('Password123'), 'Password not updated');
        }
    }
} else {
    define('PLUGINPATH', $root . '/resources/plugins/');
    require $root . '/system/libs/mail.cls.php';
    class CapturedMail extends mail {
        public $result = true;
        public function send() { return $this->result; }
    }
    $GLOBALS['CONFIG'] = [
        'smtp_host' => 'smtp.example.com', 'smtp_user' => 'account', 'smtp_pass' => 'test',
        'smtp_encrypt' => 'SSL', 'smtp_port' => 465, 'smtp_mail' => 'sender@example.com',
        'smtp_from_name' => 'Sender', 'smtp_BCC' => ['audit@example.com']
    ];
    $mail = new CapturedMail;
    $mail->addAddresses(['user@example.com', 'named@example.com' => 'Named']);
    check($mail->GO(), 'Successful send rejected');
    check($mail->Host === 'smtp.example.com' && $mail->Port === 465, 'SMTP settings ignored');
    check($mail->SMTPSecure === 'ssl' && $mail->SMTPAuth, 'Wrong SMTP security');
    check($mail->From === 'sender@example.com', 'Wrong sender');
    check(count($mail->getToAddresses()) === 2 && count($mail->getBccAddresses()) === 1, 'Wrong addresses');
    $mail->result = false;
    check(!$mail->GO(), 'Failed delivery reported as success');
    $GLOBALS['CONFIG'] = [
        'SMTP-server' => 'legacy.example.com', 'SMTP-username' => 'legacy',
        'SMTP-password' => 'test', 'SMTP-port' => 587,
        'SMTP-from' => 'legacy@example.com', 'SMTP-from-name' => 'Legacy'
    ];
    $legacy = new CapturedMail;
    check($legacy->GO() && $legacy->Host === 'legacy.example.com' && $legacy->SMTPSecure === 'tls', 'Legacy settings broken');
}
echo "PASS: $mode\n";
