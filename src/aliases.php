<?php
/**
 * DevDebug - PHP framework package
 * Copyleft (c) 2013-2014 Pierre Cassat and contributors
 * <www.ateliers-pierrot.fr> - <contact@ateliers-pierrot.fr>
 * License GPL-3.0 <http://www.opensource.org/licenses/gpl-3.0.html>
 * Sources <http://github.com/atelierspierrot/devdebug>
 */

/*
// enabling each DevDebug handlers
define('_DEVDEBUG_ERROR_HANDLER', true); // false by default
define('_DEVDEBUG_EXCEPTION_HANDLER', true); // false by default
define('_DEVDEBUG_SHUTDOWN_HANDLER', true); // false by default
#define('_DEVDEBUG_SHUTDOWN_CALLBACK', "your callback"); // empty by default
define('_DEVDEBUG_DEBUGGER_CLASS', 'DevDebug\TemplateEngineDebugger'); // empty by default
define('_DEVDEBUG_DOCUMENT_ROOT', __DIR__);
*/

// the internal errors & exceptions handlers
$abcdefghijklmnopqrstuvwxyz = DevDebug\Debugger::getInstance(
    defined('_DEVDEBUG_DEBUGGER_CLASS') ? _DEVDEBUG_DEBUGGER_CLASS : null
);

if (!@function_exists('devdebugShutdownHandler'))
{

    /**
     * Application specific shutdown handling
     */
    function devdebugShutdownHandler(&$arg = null, $callback = null)
    {
        return DevDebug\Debugger::shutdown(true, $callback);
    }

    if (defined('_DEVDEBUG_SHUTDOWN_HANDLER') && true===_DEVDEBUG_SHUTDOWN_HANDLER) {
        register_shutdown_function('devdebugShutdownHandler', defined('_DEVDEBUG_SHUTDOWN_CALLBACK') ? _DEVDEBUG_SHUTDOWN_CALLBACK : null);
    }

}

if (!@function_exists('devdebugErrorHandler'))
{

    /**
     * Application specific error handling
     */
    function devdebugErrorHandler($errno, $errstr, $errfile, $errline, $errcontext)
    {
        // This error code is not in the error_reporting()
        if (!(error_reporting() & $errno)) return false;
        $e = new DevDebug\ErrorException($errstr, $errno, $errno, $errfile, $errline);
        echo $e;
        exit;
    }

    if (defined('_DEVDEBUG_ERROR_HANDLER') && true===_DEVDEBUG_ERROR_HANDLER) {
        set_error_handler('devdebugErrorHandler', error_reporting());
    }

}

if (!@function_exists('devdebugExceptionHandler'))
{

    /**
     * Application specific exception handling
     */
    function devdebugExceptionHandler($e)
    {
        // The last call was escaped with '@'
        if (0===error_reporting()) return false;
        $e = new DevDebug\Exception($e->getMessage(), $e->getCode(), $e->getPrevious());
        echo $e;
        exit;
    }

    if (defined('_DEVDEBUG_EXCEPTION_HANDLER') && true===_DEVDEBUG_EXCEPTION_HANDLER) {
        set_exception_handler('devdebugExceptionHandler');
    }

}

if (!@function_exists('_dbg'))
{
    /**
     * DEBUG : writes a simple info, line by line, or export an array or an object
     *
     * @param mixed $str The string, array or object to export
     * @param int $type Type can be [1]: surround the export in a `pre` HTML block,
     *          [2]: render "as-is" with HTML tags, or [3]: render "as-is" with no html tags (for CLI)
     *          (default is 1)
     * @param bool $return Return the debug content or render it (default)
     * @param bool $exit Exit after render (default is `false`)
     * @param int $exit_code An exit code if exit (for CLI usage)
     */
    function _dbg($str = false, $type = 1, $return = false, $exit = false, $exit_code = 0)
    {
        $div = '';
        if (is_string($str)) {
            $div = $str."\n<br />";
        } elseif (is_array($str)) {
            $div = print_r($str, true);
        } elseif (is_object($str)) {
            try {
                $div = print_r($str, true);
            } catch(Exception $e) {
                $div = gettype($str);
            }
        } else {
            try {
                $div = var_export($str, true);
            } catch(Exception $e) {
                $div = gettype($str);
            }
        }
        $_dbg = $pre ? "<pre>".$div."</pre>" : $div;
        if ($return) {
            return $_dbg;
        } else {
            print $_dbg;
            if ($exit) {
                exit($exit_code);
            }
        }
    }
}

// Endfile