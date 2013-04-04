<?php
/**
 * DevDebug - PHP framework package
 * Copyleft (c) 2013 Pierre Cassat and contributors
 * <www.ateliers-pierrot.fr> - <contact@ateliers-pierrot.fr>
 * License GPL-3.0 <http://www.opensource.org/licenses/gpl-3.0.html>
 * Sources <https://github.com/atelierspierrot/devdebug>
 */

namespace DevDebug;

/**
 * A PHP compiler listener
 *
 * @author 		Piero Wbmstr <piero.wbmstr@gmail.com>
 */
class PhpListener
{

	/**
	 * Retrieve a backtrace information by reverse-index
	 * Type can be :
	 *    - 'func', 'function'
	 *    - 'line'
	 *    - 'file', 'filename'
	 *    - 'class'
	 *    - 'obj', 'object'
	 *    - 'type'
	 *    - 'args', 'arguments'
	 *    - 'arg', 'argument' : require to define the $arg_index parameter
	 * By default, the function will return a whole trace set.
	 *
	 * @param int $index The index of the trace to get in the backtrace pile
	 * @param string $type The type of the trace entry to get
	 * @param int $arg_index The argument index to get only an argument value
	 * @return misc The value found or NULL
	 */
	public static function getTraceInfo($index = 0, $type = null, $arg_index = null)
	{
		$trace = self::getTrace($index);
		if (!is_null($trace)) {
			switch ($type) {
				case 'func': case 'function' :
					return isset($trace['function']) ? $trace['function'] : null; 
					break;
				case 'file': case 'filename':
					return isset($trace['file']) ? $trace['file'] : null; 
					break;
				case 'line': 
					return isset($trace['line']) ? $trace['line'] : null; 
					break;
				case 'class': 
					return isset($trace['class']) ? $trace['class'] : null; 
					break;
				case 'obj': case 'object':
					return isset($trace['object']) ? $trace['object'] : null; 
					break;
				case 'type': 
					return isset($trace['type']) ? $trace['type'] : null; 
					break;
				case 'args': case 'arguments':
					return isset($trace['args']) ? $trace['args'] : null; 
					break;
				case 'arg': case 'argument':
					if (!is_null($arg_index)) {
						return isset($trace['args']) && isset($trace['args'][$arg_index]) ? $trace['args'][$arg_index] : null; 
					}
					break;
				default: return $trace; break;
			}
		}
		return null;
	}

	/**
	 * Retrieve a full backtrace set by reverse-index
	 *
	 * @param int $index The index of the trace to get in the backtrace pile
	 * @return misc The stack trace entry if found or NULL
	 */
	public static function getTrace($index = 0)
	{
		$traces = debug_backtrace();
		return array_key_exists($index, $traces) ? $traces[ $index ] : null;
	}

}

// Endfile