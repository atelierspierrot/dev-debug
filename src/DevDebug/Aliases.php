<?php
/**
 * This file is part of the DevDebug package.
 *
 * Copyleft (ↄ) 2013-2015 Pierre Cassat <me@e-piwi.fr> and contributors
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * The source code of this package is available online at 
 * <http://github.com/atelierspierrot/devdebug>.
 */

/**
 * This is defined for inclusion only
 */
namespace DevDebug\Aliases {}

/**
 * All these class aliases will be defined in the global namespace
 */
namespace {

    use DevDebug\Debugger as Debugger,
        DevDebug\ErrorException as DevDebugErrorException,
        DevDebug\Exception as DevDebugException;

    /*
    // enabling each DevDebug handlers
    define('_DEVDEBUG_ERROR_HANDLER', true); // false by default
    define('_DEVDEBUG_EXCEPTION_HANDLER', true); // false by default
    define('_DEVDEBUG_SHUTDOWN_HANDLER', true); // false by default
    #define('_DEVDEBUG_SHUTDOWN_CALLBACK', "your callback"); // empty by default
    */

    // the internal errors & exceptions handlers
    $abcdefghijklmnopqrstuvwxyz = Debugger::getInstance();

    if (!@function_exists('devdebugShutdownHandler'))
    {

        if (defined('_DEVDEBUG_SHUTDOWN_HANDLER') && true===_DEVDEBUG_SHUTDOWN_HANDLER) {
            register_shutdown_function('devdebugShutdownHandler', defined('_DEVDEBUG_SHUTDOWN_CALLBACK') ? _DEVDEBUG_SHUTDOWN_CALLBACK : null);
        }

        /**
         * Application specific shutdown handling
         */
        function devdebugShutdownHandler(&$arg = null, $callback = null)
        {
            return Debugger::shutdown(true, $callback);
        }
    }

    if (!@function_exists('devdebugErrorHandler'))
    {

        if (defined('_DEVDEBUG_ERROR_HANDLER') && true===_DEVDEBUG_ERROR_HANDLER) {
            set_error_handler('devdebugErrorHandler', error_reporting());
        }

        /**
         * Application specific error handling
         */
        function devdebugErrorHandler($errno, $errstr, $errfile, $errline, $errcontext)
        {
            // This error code is not in the error_reporting()
            if (!(error_reporting() & $errno)) return false;
            $e = new DevDebugErrorException($errstr, $errno, $errno, $errfile, $errline);
            echo $e;
        }
    }

    if (!@function_exists('devdebugExceptionHandler'))
    {

        if (defined('_DEVDEBUG_EXCEPTION_HANDLER') && true===_DEVDEBUG_EXCEPTION_HANDLER) {
            set_exception_handler('devdebugExceptionHandler');
        }

        /**
         * Application specific exception handling
         */
        function devdebugExceptionHandler($e)
        {
exit('yo');
            // The last call was escaped with '@'
            if (0===error_reporting()) return false;
            $e = new DevDebugException($e->getMessage(), $e->getCode(), $e->getPrevious());
            echo $e;
        }
    }

    if (!@function_exists('_dbg'))
    {
        /**
         * DEBUG : writes a simple info, line by line, or export an array or an object
         *
         * @param misc $str The string, array or object to export
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

}

// Endfile