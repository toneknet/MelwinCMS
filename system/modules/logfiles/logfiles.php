<?php
/*
Name: Loggfiler
Author: Martin Tonek
Description: Läs systemets text- och JSON-loggar direkt på skärmen.
Version: 1.0
Type: Module
*/
require_once __DIR__ . '/logfiles_storage.php';
class logfiles
{
    const MAX_BYTES = 2097152;

    public function __construct($settings)
    {
        if (!function_exists('isSuperUser') || !isSuperUser()) {
            echo '<p>Endast superadministratörer kan läsa systemets loggfiler.</p>';
            return;
        }
        // throw new RuntimeException('TEST: verifiering av nya felhanteraren');
        addCSS('system/modules/logfiles/html/logfiles.css');
        addJS('system/modules/logfiles/html/logfiles.js');
        $storage = new logfiles_storage(dirname(__DIR__, 3));
        $archived = $storage->archive();
        $catalog = $storage->catalog();
        $scope = self::parameter('logscope', 'root');
        $folder = self::parameter('logfolder');
        $from = self::parameter('logfrom');
        $to = self::parameter('logto');
        $valid = in_array($scope, array('root', 'archive', 'period'), true);
        if ($scope === 'archive' && $folder !== '' && !isset($catalog[$folder])) $valid = false;
        foreach (array($from, $to) as $date) {
            if ($date !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !logfiles_storage::date('date_' . str_replace('-', '', $date) . '.log'))) $valid = false;
        }
        if ($from !== '' && $to !== '' && $from > $to) $valid = false;
        $files = $valid ? $storage->select($catalog, $scope, $folder, $from, $to) : array();
        echo '<section class="logfiles"><h2>Loggfiler</h2>';
        if ($archived['moved']) echo '<p class="logfiles-notice">' . $archived['moved'] . ' äldre loggfiler har flyttats till månadsarkiv.</p>';
        foreach ($archived['warnings'] as $warning) echo '<p class="logfiles-notice">' . self::escape($warning) . '</p>';
        $this->fileFilters($catalog, $scope, $folder, $from, $to);
        if (!$valid) {
            echo '<p role="alert">Ogiltig mapp eller tidsperiod. Välj bland mapparna ovan och kontrollera datumen.</p></section>';
            return;
        }
        echo '<nav class="logfiles-tabs" aria-label="Välj loggfil">';
        $selected = isset($_GET['logfile']) && is_string($_GET['logfile']) ? $_GET['logfile'] : key($files);
        foreach ($files as $name => $path) {
            $query = $_GET;
            $query['logfile'] = $name;
            echo '<a href="?' . self::escape(http_build_query($query)) . '"' . ($selected === $name ? ' aria-current="page"' : '') . '>' . self::escape($name) . '</a>';
        }
        echo '</nav>';
        if (!$files) {
            echo '<p>Inga loggfiler i urvalet. Välj en arkivmapp eller ändra tidsperioden.</p></section>';
            return;
        }
        if (!isset($files[$selected])) {
            echo '<p>Den valda loggfilen finns inte. Välj en fil ovan.</p></section>';
            return;
        }
        echo '<div class="logfiles-toolbar"><label>Sök i visade inlägg <input type="search" class="logfiles-search" placeholder="Text, datum eller status"></label><button type="button" class="logfiles-refresh">Uppdatera</button></div>';
        try {
            list($lines, $limited, $size) = $this->tail($files[$selected]);
        } catch (RuntimeException $error) {
            echo '<p role="alert">Loggfilen kunde inte läsas. Kontrollera att den finns och är läsbar.</p></section>';
            return;
        }
        echo '<p class="logfiles-meta">' . self::escape($selected) . ' · ' . number_format($size / 1024, 1, ',', ' ') . ' kB · Senaste inläggen först. <span class="logfiles-count" aria-live="polite">' . count($lines) . ' inlägg</span></p>';
        echo '<div class="logfiles-levels" role="group" aria-label="Filtrera på allvarlighetsgrad" hidden>';
        foreach (array('all' => 'Alla', 'critical' => 'Kritiska', 'error' => 'Fel', 'warning' => 'Varningar', 'other' => 'Info/övrigt') as $level => $label) {
            echo '<button type="button" data-level-filter="' . $level . '" aria-pressed="' . ($level === 'all' ? 'true' : 'false') . '">' . $label . ' <span class="logfiles-level-count"></span></button>';
        }
        echo '</div>';
        if ($limited) {
            echo '<p class="logfiles-notice">Visar högst de senaste 500 inläggen inom filens sista 2 MB. Sökning, nivåfilter och antal gäller bara dessa inlägg.</p>';
        }
        if (!$lines) {
            echo '<p>Loggfilen är tom.</p>';
        }
        echo '<div class="logfiles-entries">';
        foreach (array_reverse($lines) as $line) {
            $this->entry($line, self::isEmail($files[$selected]));
        }
        echo '</div><p class="logfiles-empty" hidden>Inga inlägg matchar sökningen och valt nivåfilter.</p></section>';
    }

    private static function escape($text)
    {
        return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function files()
    {
        return (new logfiles_storage(dirname(__DIR__, 3)))->catalog()[''];
    }

    private static function parameter($name, $default = '')
    {
        return isset($_GET[$name]) && is_string($_GET[$name]) ? $_GET[$name] : $default;
    }

    private static function isEmail($path)
    {
        return (bool) preg_match('/^email(?:[_.]\d{8})?\.log$/i', basename($path));
    }

    private function fileFilters($catalog, $scope, $folder, $from, $to)
    {
        echo '<form method="get" class="logfiles-filefilters">';
        foreach ($_GET as $key => $value) {
            if (in_array($key, array('logfile', 'logscope', 'logfolder', 'logfrom', 'logto'), true) || !is_scalar($value)) continue;
            echo '<input type="hidden" name="' . self::escape($key) . '" value="' . self::escape($value) . '">';
        }
        echo '<label>Visa<select name="logscope">';
        foreach (array('root' => 'Aktuella filer', 'archive' => 'Arkivmapp', 'period' => 'Tidsperiod (alla mappar)') as $key => $label) {
            echo '<option value="' . $key . '"' . ($scope === $key ? ' selected' : '') . '>' . $label . '</option>';
        }
        echo '</select></label><label>Arkivmapp<select name="logfolder"><option value="">Välj mapp</option>';
        foreach (array_reverse($catalog, true) as $name => $entries) {
            $name = (string) $name;
            if ($name === '') continue;
            echo '<option value="' . self::escape($name) . '"' . ($name === $folder ? ' selected' : '') . '>' . self::escape($name) . ' (' . count($entries) . ')</option>';
        }
        echo '</select></label><label>Från datum<input type="date" name="logfrom" value="' . self::escape($from) . '"></label><label>Till datum<input type="date" name="logto" value="' . self::escape($to) . '"></label><button type="submit">Visa filer</button></form>';
        echo '<p class="logfiles-meta">Aktuella filer: system/logs/ samt sajtrotens error.log. Arkivmapp: endast filer direkt i vald mapp. Tidsperiod: datum i filnamnet, inklusive arkiv; odaterade filer ingår inte.</p>';
    }

    private function tail($path)
    {
        $handle = @fopen($path, 'rb');
        if (!$handle) {
            throw new RuntimeException('Cannot open log');
        }
        try {
            $stat = fstat($handle);
            if ($stat === false) {
                throw new RuntimeException('Cannot stat log');
            }
            $size = $stat['size'];
            $offset = max(0, $size - self::MAX_BYTES);
            if (fseek($handle, $offset) !== 0) {
                throw new RuntimeException('Cannot seek log');
            }
            $text = stream_get_contents($handle, self::MAX_BYTES);
            if ($text === false) {
                throw new RuntimeException('Cannot read log');
            }
        } finally {
            fclose($handle);
        }
        if ($offset > 0) {
            $newline = strpos($text, "\n");
            $text = $newline === false ? '' : substr($text, $newline + 1);
        }
        // One email includes recipient, title, message and return status.
        $email = self::isEmail($path);
        // Keep PHP stack traces attached to their timestamped entry.
        $entries = array();
        $errorTime = null;
        foreach (preg_split('/\r\n|\n|\r/', $text) as $line) {
            if ($email) {
                if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}:[\t ]*Email sent to:/', $line)) {
                    $entries[] = $line;
                } elseif ($entries) {
                    $entries[count($entries) - 1] .= "\n" . $line;
                } elseif ($offset === 0 && trim($line) !== '') {
                    $entries[] = $line;
                }
                // A bounded read may start inside a message; skip that incomplete entry.
                continue;
            }
            if (trim($line) === '') { 
                continue;
            }
            if (preg_match('/^\[(\d{2}-[A-Za-z]{3}-\d{4} \d{2}:\d{2}:\d{2}[^\]]*)\][\t ]*(.*)$/', $line, $match)) {
                // A fresh PHP diagnostic is a new incident even within the same second.
                $newError = preg_match('/^PHP (?:Fatal error|Warning|Notice|Parse error|Deprecated|Strict Standards|Recoverable fatal error):/i', $match[2]);
                $structured = json_decode($match[2], true);
                $newError = $newError || (is_array($structured) && isset($structured['error_id']));
                if ($entries && $errorTime === $match[1] && !$newError) {
                    $entries[count($entries) - 1] .= "\n" . $match[2];
                } else {
                    $entries[] = $line;
                }
                $errorTime = $match[1];
                continue;
            }
            if ($entries && $errorTime !== null) {
                $entries[count($entries) - 1] .= "\n" . $line;
                continue;
            }
            if ($entries && preg_match('/^(?:\s+|#\d+\s|Stack trace:|Next\s|thrown\s)/', $line)) {
                $entries[count($entries) - 1] .= "\n" . $line;
            } else {
                $entries[] = $line;
            }
        }
        return array(array_slice($entries, -500), $offset > 0 || count($entries) > 500, $size);
    }

    private function entry($line, $email = false)
    {
        $payload = $line;
        $time = '';
        if (preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}):?\s*(.*)$/s', $line, $match)) {
            $time = $match[1];
            $payload = $match[2];
        } elseif (preg_match('/^\[(\d{2}-[A-Za-z]{3}-\d{4} \d{2}:\d{2}:\d{2}[^\]]*)\][\t ]*(.*)$/s', $line, $match)) {
            $time = $match[1];
            $payload = $match[2];
        }
        $data = json_decode($payload, true);
        $status = '';
        $statusText = '';
        if ($email) {
            $status = 'unknown';
            $statusText = 'Sändstatus saknas';
            if (preg_match_all('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}:[\t ]*Returned:[\t ]*([^\r\n]*)/m', $line, $returns)) {
                $successful = trim(end($returns[1])) === 'TRUE';
                $status = $successful ? 'success' : 'error';
                $statusText = $successful ? 'E-post skickad' : 'Fel vid sändning';
            }
        }
        $level = $email ? ($status === 'error' ? 'error' : 'other') : $this->severity($payload, $data);
        echo '<article data-level="' . $level . '" class="logfiles-entry' . ($email ? ' logfiles-email-' . $status : '') . '">';
        if ($time !== '') {
            echo '<div class="logfiles-time">' . self::escape($time) . '</div>';
        }
        if ($email) {
            echo '<div class="logfiles-status">' . self::escape($statusText) . '</div>';
            $this->emailContent($line);
            echo '</article>';
            return;
        }
        if ($level !== 'other') {
            $labels = array('critical' => 'Kritiskt fel', 'error' => 'Fel', 'warning' => 'Varning');
            echo '<div class="logfiles-severity logfiles-severity-' . $level . '">' . $labels[$level] . '</div>';
        }
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            echo '<dl>';
            foreach ($data as $key => $value) {
                $formatted = is_string($value) ? $value : json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
                echo '<dt>' . self::escape($key) . '</dt><dd>' . self::escape($formatted) . '</dd>';
            }
            echo '</dl><details><summary>Visa JSON</summary><pre>' . self::escape(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '</pre></details>';
        } else {
            if ($email) {
                // Convert only line-break tags; all other markup remains escaped text.
                $payload = preg_replace('/<br\s*\/?\s*>[\t ]*(?:\r\n|\n|\r)?/i', "\n", $payload);
            }
            echo '<pre>' . self::escape($payload) . '</pre>';
        }
        echo '</article>';
    }

    private function severity($payload, $data)
    {
        if (is_array($data)) {
            $level = isset($data['level']) && is_string($data['level']) ? strtolower($data['level']) : '';
            if (in_array($level, array('critical', 'alert', 'emergency', 'fatal'), true)) return 'critical';
            // The central handler writes level=error for requests that terminate.
            if ($level === 'error' && isset($data['error_id'], $data['trace'], $data['context'])) return 'critical';
            if ($level === 'error') return 'error';
            if (in_array($level, array('warning', 'warn'), true)) return 'warning';
            if (isset($data['status']) && in_array($data['status'], array('error', 'failed', 'failure'), true)) return 'error';
        }
        if (preg_match('/^PHP (?:Fatal error|Parse error|Parsing error|Core error|Compile error|Recoverable fatal error):/i', $payload)) return 'critical';
        if (preg_match('/^PHP (?:Warning|Core warning|Compile warning):/i', $payload)) return 'warning';
        return 'other';
    }

    private function emailContent($entry)
    {
        $fields = array('to' => '', 'subject' => '', 'message' => '');
        $current = 'message';
        $inMessage = false;
        $errors = array();
        foreach (preg_split('/\r\n|\n|\r/', $entry) as $line) {
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}:[\t ]*(Email sent to:|with title:|and message:|Returned:)[\t ]*(.*)$/', $line, $match)) {
                if ($match[1] === 'Returned:') {
                    if (trim($match[2]) !== 'TRUE') {
                        $errors[] = 'Returned: ' . $match[2];
                    }
                    continue;
                }
                if (!$inMessage) {
                    $keys = array('Email sent to:' => 'to', 'with title:' => 'subject', 'and message:' => 'message');
                    $current = $keys[$match[1]];
                    $fields[$current] = $match[2];
                    $inMessage = $current === 'message';
                    continue;
                }
            }
            $fields[$current] .= "\n" . $line;
        }
        echo '<dl class="logfiles-email-headers"><dt>To:</dt><dd>' . self::escape(trim($fields['to'])) . '</dd>';
        echo '<dt>Subject:</dt><dd>' . self::escape(trim($fields['subject'])) . '</dd></dl>';
        // Preserve line breaks without allowing HTML from the log to execute.
        $message = preg_replace('/<br\s*\/?\s*>[\t ]*(?:\r\n|\n|\r)?/i', "\n", $fields['message']);
        echo '<div class="logfiles-email-body"><strong>Message:</strong><div class="logfiles-email-message">' . self::escape(trim($message)) . '</div></div>';
        if ($errors) {
            echo '<pre class="logfiles-email-return">' . self::escape(implode("\n", $errors)) . '</pre>';
        }
    }
}
