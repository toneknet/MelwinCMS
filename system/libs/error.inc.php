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
 * Error handler
 *
 * Handle errors like PHP does it natively but additionaly log them to the
 * application error log file.
 * @param int
 * @param string
 * @param string
 * @param int
 */
function __error($intType, $strMessage, $strFile, $intLine)
{
	// print __FUNCTION__;
	$arrErrors = array(
		E_ERROR             => 'Fatal error',
		E_WARNING           => 'Warning',
		E_PARSE             => 'Parsing error',
		E_NOTICE            => 'Notice',
		E_CORE_ERROR        => 'Core error',
		E_CORE_WARNING      => 'Core warning',
		E_COMPILE_ERROR     => 'Compile error',
		E_COMPILE_WARNING   => 'Compile warning',
		E_USER_ERROR        => 'Fatal error',
		E_USER_WARNING      => 'Warning',
		E_USER_NOTICE       => 'Notice',
		// E_STRICT            => 'Runtime notice',
		4096                => 'Recoverable error',
		8192                => 'Deprecated notice'
	);

	// Ignore functions with an error control operator (@function_name)
	if (ini_get('error_reporting') > 0) {
		if ($intType != E_NOTICE) //|| 1==1
		{
			$e = new Exception();
			// Log error
			error_log(sprintf(
				'PHP %s: %s in %s on line %s, OUTPUT: %s',
				$arrErrors[$intType],
				$strMessage,
				$strFile,
				$intLine,
				$e->getTraceAsString()
			));

			// Display error
			if (ini_get('display_errors')) {
				$strMessage = sprintf(
					'<strong>%s</strong>: %s in <strong>%s</strong> on line <strong>%s</strong>',
					$arrErrors[$intType],
					$strMessage,
					$strFile,
					$intLine
				);

				$e = new Exception();
				$strMessage .= "\n" . '<pre style="margin: 11px 0 0 0;">' . "\n" . $e->getTraceAsString() . "\n" . '</pre>';

				echo '<br />' . $strMessage;
				show_help_message();
			}
		}

		// Exit on severe errors
		if (in_array($intType, array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR))) {

			show_help_message();
			//            $GLOBALS['ERROR']=true;
			exit;
		}
	}
	//    else {
	//        header('HTTP/1.1 500 Internal Server Error');
	//
	// 		echo 'An error occurred while executing this script!';
	//        exit();
	//    }
}


/**
 * Exception handler
 *
 * Log exceptions in the application log file and print them to the screen
 * if "display_errors" is set. Callback to a custom exception handler defined
 * in the application file "config/error.php".
 * @param object
 */
function __exception($e)
{
	// print_r($e);
	print __FILE__ . "@" . __LINE__ . ":" . __FUNCTION__ . "<br />";
	error_log(sprintf(
		"PHP Fatal error: Uncaught exception '%s' with message '%s' thrown in %s on line %s",
		get_class($e),
		$e->getMessage(),
		$e->getFile(),
		$e->getLine()
	));
	error_log(implode(";;;", reset(debug_backtrace())));
	// 20250804 - Kollar om detta ger mig felkoder
	error_log( json_encode(reset(debug_backtrace())) );

	// Display exception
	if (ini_get('display_errors')) {
		$strMessage = sprintf(
			'<strong>Fatal error</strong>: Uncaught exception <strong>%s</strong> with message <strong>%s</strong> thrown in <strong>%s</strong> on line <strong>%s</strong>',
			get_class($e),
			$e->getMessage(),
			$e->getFile(),
			$e->getLine()
		);

		$strMessage .= "\n" . '<pre style="margin: 11px 0 0 0;">' . "\n" . $e->getTraceAsString() . "\n" . '</pre>';

		echo '<br />' . $strMessage;
		show_help_message();
	}

	show_help_message();
	exit;
}


/**
 * Show a special Contao "what to do in case of an error" message
 */
function show_help_message()
{
	if (!ini_get('display_errors')) {
		header('HTTP/1.1 500 Internal Server Error');

		echo 'An error occurred while executing this script!';
	} else {

		print "ERROR GENERATED: HTTP/1.1 500 Internal Server Error";
	}
	ob_end_clean();
	http_response_code(500);
	if(is_file(TPLPATH . "500.html")) {
		include_once(TPLPATH . "500.html");
	} else {
		echo 'An error occurred while executing this script!';
	}
	die();
}


/**
 * Set error and exception handler
 */
@set_error_handler('__error');
@set_exception_handler('__exception');

/**
 * Log PHP errors
 */
@ini_set('error_log', 'system/logs/error.log');

/**
 * Set error_reporting
 */
@ini_set('display_errors', ($GLOBALS['CONFIG']['displayErrors'] ? 1 : 0));
error_reporting(($GLOBALS['CONFIG']['displayErrors'] ? E_ALL : 0));

//if (isSuperUser()) {
//	print "HEY";
/*
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_COMPILE_WARNING     );//E_ALL
  */
//}

//function db_reporting($query)
//{
//	$filename = 'system/logs/db.log';
//	$somecontent = $query."\n";
//
//
//// INTE SKRIVA OM
//// user, company, page, groups,
//
//
//    if (!$handle = fopen($filename, 'a')) {
//		touch($filename);
//    }
//
//    if (fwrite($handle, $somecontent) === FALSE) {
//		return;
//    }
//
//    fclose($handle);
//}



/*

// we will do our own error handling.
error_reporting(0); // Turns off all error reporting.

// user defined error handling function.
function userErrorHandler($errno, $errmsg, $filename, $linenum, $vars)
{
    // timestamp for the error entry.
    $dt = date('Y-m-d H:i:s (T)');

    // define an assoc array of error string
    // in reality the only entries we should
    // consider are E_WARNING, E_NOTICE, E_USER_ERROR,
    // E_USER_WARNING and E_USER_NOTICE.
    $errortype = array (
                E_ERROR => 'Error',
                E_WARNING => 'Warning',
                E_PARSE => 'Parsing Error',
                E_NOTICE => 'Notice',
                E_CORE_ERROR => 'Core Error',
                E_CORE_WARNING => 'Core Warning',
                E_COMPILE_ERROR => 'Compile Error',
                E_COMPILE_WARNING => 'Compile Warning',
                E_USER_ERROR => 'User Error',
                E_USER_WARNING => 'User Warning',
                E_USER_NOTICE => 'User Notice',
                E_STRICT => 'Runtime Notice'
                );
    // set of errors for which a var trace will be saved.
    $user_errors = array(E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE);

    $err = "<errorentry>\n";
    $err .= "\t<datetime>" .$dt. "</datetime>\n";
    $err .= "\t<errornum>" .$errno. "</errornum>\n";
    $err .= "\t<errortype>" .$errortype[$errno]. "</errortype>\n";
    $err .= "\t<errormsg>" .$errmsg. "</errormsg>\n";
    $err .= "\t<scriptname>" .$filename. "</scriptname>\n";
    $err .= "\t<scriptlinenum>" .$linenum. "</scriptlinenum>\n";

    if (in_array($errno, $user_errors)) {
        $err .= "\t<vartrace>" .wddx_serialize_value($vars, 'Variables'). "</vartrace>\n";
    }
    $err .= "</errorentry>\n\n";

    // save to the error log file, and e-mail me if there is a critical user error.
    error_log($err, 3, '../error_log.log');
    if ($errno == E_USER_ERROR) {
        mail('martintonek@gmail.com', 'Critical User Error', $err);
    }
}
$old_error_handler = set_error_handler('userErrorHandler');
*/
