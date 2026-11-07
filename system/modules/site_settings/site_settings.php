<?php
/*
Name: Site settings
Description: Local installation settings with translated field descriptions.
Version: 1.0.0
Type: Module
*/
class site_settings
{
    private static function esc($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
    private function text($key) { return $GLOBALS['LANG']['site_settings'][$key] ?? $key; }

    private static function templates()
    {
        $path = defined('TPLPATH') ? TPLPATH : dirname(rtrim(SYSPATH, '/\\')) . '/tpl/';
        $names = [];
        foreach (is_dir($path) ? scandir($path) : [] as $name) {
            if ($name[0] !== '.' && is_dir($path . '/' . $name) && is_file($path . '/' . $name . '/index.tpl')) $names[] = $name;
        }
        natcasesort($names);
        return array_values($names);
    }

    // Read files separately: runtime CONFIG may also contain module and company settings.
    public static function readFile($path)
    {
        $previous = $GLOBALS['CONFIG'] ?? [];
        $GLOBALS['CONFIG'] = [];
        try {
            if (is_file($path)) include $path;
            return $GLOBALS['CONFIG'];
        } finally {
            $GLOBALS['CONFIG'] = $previous;
        }
    }

    public static function convert($value, $sample)
    {
        if (!is_string($value)) throw new InvalidArgumentException('value');
        if (is_array($sample)) {
            $decoded = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) throw new InvalidArgumentException('JSON');
            return $decoded;
        }
        if (is_bool($sample)) {
            if ($value !== '0' && $value !== '1') throw new InvalidArgumentException('boolean');
            return $value === '1';
        }
        if (is_int($sample)) {
            if (filter_var($value, FILTER_VALIDATE_INT) === false) throw new InvalidArgumentException('integer');
            return (int)$value;
        }
        if (is_float($sample)) {
            if (!is_numeric($value) || !is_finite((float)$value)) throw new InvalidArgumentException('number');
            return (float)$value;
        }
        return $value;
    }

    public static function writeFile($path, array $values, $revision)
    {
        // A dedicated lock also works when replacing the config file atomically.
        $lock = @fopen($path . '.lock', 'c');
        if (!$lock) throw new RuntimeException('write_error');
        $temp = false;
        try {
            if (!flock($lock, LOCK_EX)) throw new RuntimeException('write_error');
            $current = is_file($path) ? hash_file('sha256', $path) : '';
            if (!hash_equals($current, $revision)) throw new RuntimeException('conflict');
            $output = "<?php\n// Local installation overrides, saved by Site settings.\n";
            foreach ($values as $key => $value) {
                $output .= '$GLOBALS[\'CONFIG\'][' . var_export($key, true) . '] = ' . var_export($value, true) . ";\n";
            }
            $temp = tempnam(dirname($path), '.site-settings-');
            if (!$temp || file_put_contents($temp, $output) !== strlen($output)) throw new RuntimeException('write_error');
            $mode = is_file($path) ? (fileperms($path) & 0777) : 0600;
            if (!@chmod($temp, $mode) || !@rename($temp, $path)) throw new RuntimeException('write_error');
            if (function_exists('opcache_invalidate')) @opcache_invalidate($path, true);
        } finally {
            if ($temp && is_file($temp)) @unlink($temp);
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public function __construct($settings = [])
    {
        require __DIR__ . '/lang/en.php';
        if (($GLOBALS['CONFIG']['language'] ?? 'en') === 'sv') require __DIR__ . '/lang/sv.php';
        if (!isSuperUser()) {
            print '<p role="alert">' . self::esc($this->text('denied')) . '</p>';
            return;
        }
        $path = SYSPATH . 'configs/localconfig.inc.php';
        $revision = is_file($path) ? hash_file('sha256', $path) : '';
        $defaults = self::readFile(SYSPATH . 'configs/config.inc.php');
        $local = self::readFile($path);
        $effective = array_replace($defaults, $local);
        $templates = self::templates();
        $timezones = timezone_identifiers_list();
        if (empty($_SESSION['site_settings_token'])) $_SESSION['site_settings_token'] = bin2hex(random_bytes(32));
        $message = '';
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['site_settings_save'])) {
            try {
                if (!is_string($_POST['site_settings_token'] ?? null) || !hash_equals($_SESSION['site_settings_token'], $_POST['site_settings_token'])) throw new RuntimeException('csrf');
                $posted = $_POST['values'] ?? null;
                $reset = $_POST['reset'] ?? [];
                if (!is_array($posted) || !is_array($reset) || !is_string($_POST['revision'] ?? null)) throw new RuntimeException('invalid');
                $next = $local;
                foreach ($effective as $key => $sample) {
                    if (isset($reset[$key]) && array_key_exists($key, $defaults)) { unset($next[$key]); continue; }
                    if (!array_key_exists($key, $posted)) throw new RuntimeException('invalid');
                    if (self::secret($key) && $posted[$key] === '') continue;
                    try { $value = self::convert($posted[$key], $sample); }
                    catch (InvalidArgumentException $e) { throw new RuntimeException('invalid'); }
                    if ($key === 'template' && $value !== $sample && !in_array($value, $templates, true)) throw new RuntimeException('invalid');
                    if ((strpos($key, 'allow_') === 0 || in_array($key, ['displayErrors', 'displayDebug', 'noExecSql'], true)) && !in_array($value, [0, 1, '0', '1', false, true], true)) throw new RuntimeException('invalid');
                    if ($key === 'timezone' && !in_array($value, $timezones, true)) throw new RuntimeException('invalid');
                    // Do not freeze inherited defaults into the local file.
                    if ($value !== $sample) $next[$key] = $value;
                }
                self::writeFile($path, $next, $_POST['revision']);
                $local = $next;
                $effective = array_replace($defaults, $local);
                $revision = hash_file('sha256', $path);
                $message = 'saved';
            } catch (RuntimeException $e) { $message = $e->getMessage(); }
        }
        print '<section class="site-settings"><h2>' . self::esc($this->text('title')) . '</h2><p>' . self::esc($this->text('intro')) . '</p>';
        if ($message) print '<p role="status">' . self::esc($this->text($message)) . '</p>';
        print '<form method="post"><input type="hidden" name="site_settings_save" value="1"><input type="hidden" name="site_settings_token" value="' . self::esc($_SESSION['site_settings_token']) . '"><input type="hidden" name="revision" value="' . self::esc($revision) . '">';
        foreach ($effective as $key => $value) {
            $label = $GLOBALS['LANG']['site_settings']['fields'][$key] ?? [$key, $this->text('custom')];
            $id = 'site-setting-' . bin2hex($key);
            $name = 'values[' . $key . ']';
            print '<fieldset style="margin:0 0 1rem;padding:1rem"><legend>' . self::esc($label[0]) . '</legend><label for="' . $id . '">' . self::esc($label[1]) . '</label><p><code>' . self::esc($key) . '</code> · ' . self::esc($this->text(array_key_exists($key, $local) ? 'local' : 'inherited')) . '</p>';
            if ($key === 'timezone') {
                print '<select style="width:100%" id="' . $id . '" name="' . self::esc($name) . '" required>';
                if (!in_array($value, $timezones, true)) print '<option value="" selected disabled>' . self::esc($this->text('choose_timezone')) . '</option>';
                foreach ($timezones as $timezone) {
                    print '<option value="' . self::esc($timezone) . '"' . ($timezone === $value ? ' selected' : '') . '>' . self::esc($timezone) . '</option>';
                }
                print '</select>';
            } elseif ($key === 'template') {
                print '<select style="width:100%" id="' . $id . '" name="' . self::esc($name) . '">';
                // Keep an unavailable current value visible without silently changing it.
                if (!in_array($value, $templates, true)) print '<option selected value="' . self::esc($value) . '">' . self::esc($value . ' (' . $this->text('template_missing') . ')') . '</option>';
                foreach ($templates as $template) {
                    print '<option value="' . self::esc($template) . '"' . ($template === $value ? ' selected' : '') . '>' . self::esc($template) . '</option>';
                }
                print '</select>';
            } elseif (is_array($value)) {
                print '<textarea style="width:100%;box-sizing:border-box" rows="4" id="' . $id . '" name="' . self::esc($name) . '">' . self::esc(json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '</textarea><p>' . self::esc($this->text('json')) . '</p>';
            } else {
                $secret = self::secret($key);
                print '<input style="width:100%;box-sizing:border-box" id="' . $id . '" name="' . self::esc($name) . '" type="' . ($secret ? 'password' : (is_int($value) || is_float($value) ? 'number' : 'text')) . '" step="any" autocomplete="' . ($secret ? 'new-password' : 'off') . '" value="' . self::esc($secret ? '' : (is_bool($value) ? (int)$value : $value)) . '">';
                if ($secret) print '<p>' . self::esc($this->text('secret')) . '</p>';
            }
            if (array_key_exists($key, $defaults)) print '<p><label><input type="checkbox" name="reset[' . self::esc($key) . ']" value="1"> ' . self::esc($this->text('reset')) . '</label></p>';
            print '</fieldset>';
        }
        print '<button type="submit">' . self::esc($this->text('save')) . '</button></form></section>';
    }

    private static function secret($key) { return in_array($key, ['dbPass', 'smtp_pass', 'ftp_pass', 'salt', 'install_password', 'repoUrlAuth'], true) || (bool)preg_match('/(?:password|secret|token)$|_pass$/i', $key) && strpos($key, 'allow_') !== 0; }
}
