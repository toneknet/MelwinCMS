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

/** Return trace locations only: never log arguments or objects. */
function app_error_trace(array $trace)
{
    $result = array();
    foreach (array_slice($trace, 0, 50) as $frame) {
        $result[] = array_intersect_key($frame, array_flip(array('file', 'line', 'class', 'type', 'function')));
    }
    return $result;
}

/** Build context without sessions, request bodies, query strings or credentials. */
function app_error_context($file, array $trace)
{
    $module = null;
    foreach (array_merge(array(array('file' => $file)), $trace) as $frame) {
        if (preg_match('~/system/modules/([^/]+)/~', str_replace('\\', '/', $frame['file'] ?? ''), $match)) {
            $module = $match[1];
            break;
        }
    }
    $cli = PHP_SAPI === 'cli';
    return array(
        'execution' => $cli ? 'cli' : 'web',
        'path' => $cli ? null : explode('?', $_SERVER['REQUEST_URI'] ?? '', 2)[0],
        'method' => $cli ? null : ($_SERVER['REQUEST_METHOD'] ?? null),
        'script' => $_SERVER['SCRIPT_FILENAME'] ?? null,
        'module' => $module
    );
}

/** One JSON record per incident; retain PHP's configured error-log destination. */
function app_log_error($level, $type, $message, $file, $line, array $trace, $code = 0)
{
    static $logging = false;
    $id = str_replace('.', '', uniqid('err-', true));
    $record = array(
        'timestamp' => date('c'),
        'error_id' => $id,
        'level' => $level,
        'type' => $type,
        'message' => $message,
        'code' => $code,
        'file' => $file,
        'line' => $line,
        'context' => app_error_context($file, $trace),
        'trace' => app_error_trace($trace)
    );
    if ($logging) {
        return $record;
    }
    $logging = true;
    try {
        $json = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        if ($json === false || !@error_log($json)) {
            // Avoid recursively logging failures of the logger itself.
            @error_log('Error logging failed; error ID: ' . $id, 4);
        }
    } finally {
        $logging = false;
    }
    return $record;
}

function __error($intType, $strMessage, $strFile, $intLine)
{
    if (!(error_reporting() & $intType)) {
        return false; // Respect explicit suppression, including @.
    }
    $fatal = in_array($intType, array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR), true);
    $names = array(
        E_ERROR => 'E_ERROR', E_WARNING => 'E_WARNING', E_PARSE => 'E_PARSE',
        E_NOTICE => 'E_NOTICE', E_CORE_ERROR => 'E_CORE_ERROR', E_CORE_WARNING => 'E_CORE_WARNING',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR', E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_USER_ERROR => 'E_USER_ERROR', E_USER_WARNING => 'E_USER_WARNING', E_USER_NOTICE => 'E_USER_NOTICE',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR', E_DEPRECATED => 'E_DEPRECATED',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED', 2048 => 'E_STRICT'
    );
    $level = $fatal ? 'error' : (in_array($intType, array(E_NOTICE, E_USER_NOTICE, E_DEPRECATED, E_USER_DEPRECATED, 2048), true) ? 'notice' : 'warning');
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 51);
    array_shift($trace);
    $record = app_log_error($level, $names[$intType] ?? 'PHP_ERROR', $strMessage, $strFile, $intLine, $trace, $intType);
    if ($fatal) {
        show_help_message($record);
    }
    // Recoverable warnings must not interrupt the response or corrupt JSON/AJAX.
    return true;
}

function __exception($e)
{
    $record = app_log_error('error', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTrace(), $e->getCode());
    show_help_message($record);
}

/** Safe failure response, with the same ID as the server-side log record. */
function show_help_message($record = null)
{
    while (ob_get_level() > 0) {
        if (!@ob_end_clean()) {
            break;
        }
    }
    $response = array('message' => 'An error occurred. Please contact the administrator and provide the error ID.', 'error_id' => $record['error_id'] ?? null);
    if (!empty($GLOBALS['CONFIG']['displayErrors']) && $record !== null) {
        $response['details'] = $record;
    }
    if (PHP_SAPI === 'cli') {
        file_put_contents('php://stderr', json_encode($response, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) . PHP_EOL);
        exit(1);
    }
    http_response_code(500);
    $ajax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
        || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
        || strpos($_SERVER['SCRIPT_NAME'] ?? '', '/ajax/') !== false;
    if (!headers_sent()) {
        header('Cache-Control: no-store');
        header('Content-Type: ' . ($ajax ? 'application/json' : 'text/html') . '; charset=UTF-8');
    }
    if ($ajax) {
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    } else {
        $escape = function ($text) { return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
        $reference = $response['error_id'] !== null
            ? '<div class="error-reference"><span>Please provide this error ID when contacting the administrator</span><code>' . $escape($response['error_id']) . '</code></div>' : '';
        $details = '';
        if (isset($response['details'])) {
            $details = '<details class="error-details"><summary>Technical details</summary><pre>' . $escape(json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE)) . '</pre></details>';
        }
        $template = @file_get_contents(dirname(__DIR__, 2) . '/tpl/500.html');
        if ($template === false) {
            $template = '<!doctype html><html lang="en"><meta charset="utf-8"><title>An error occurred</title><body><h1>An error occurred</h1><p>Please contact the administrator and provide the error ID.</p><!-- ERROR_REFERENCE --><!-- ERROR_DETAILS --></body></html>';
        }
        echo strtr($template, array('<!-- ERROR_REFERENCE -->' => $reference, '<!-- ERROR_DETAILS -->' => $details));
    }
    exit(1);
}

// Log independently of displayErrors. PHP-native failures keep their fallback log.
ini_set('error_log', dirname(__DIR__) . '/logs/error.log');
ini_set('log_errors', '1');
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);
set_error_handler('__error');
set_exception_handler('__exception');
