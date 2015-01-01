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

namespace DevDebug;

use \Exception as StandardException;

use Library\Helper\Url;

/**
 * Special application exception handler
 *
 * To use it, write something like :
 *
 *     try {
 *     		something wrong ...
 *     } catch (\DevDebug\Exception $e) {
 *     		echo $e;
 *     }
 *
 * @author 		Piero Wbmstr <piero.wbmstr@gmail.com>
 */
class Exception extends StandardException
{

	/**
	 * Received arguments (not define in parent)
	 */
	protected $trace;
	protected $previous;
	protected $php_error_message;

	/**
	 * Table of informations for display
	 */
	public $infos=array();

	/**
	 * The debugger object
	 *
	 * @see Dev\Debugger
	 */
	protected $debugger;

	/**
	 * Masks for string construction
	 */
	const long_title = 'The system has intercepted the following exception : \'%s\' [%d]';
	const short_title = 'Exception : \'%s\'';

	/**
	 * Construction of the exception - a message is needed (1st argument)
	 *
	 * @param string $message The exception message
	 * @param numeric $code The exception code
	 * @param misc $previous The previous exception if so
	 */
	public function __construct($message, $code = 0, $previous = null) 
	{
		// We let the default PHP Exception manager construction
		parent::__construct($message, $code, $previous);

		$this->trace = parent::getTrace();
		$this->previous = parent::getPrevious();
		if (isset($php_errormsg))
			$this->php_error_message = $php_errormsg;

		$dom_id = Profiler::getNewDomId('exception');
		$this->infos = array(
			'message' => self::_buildExceptionStr(),
			'type'=>'exception',
			'scope'=>'Exception',
			'file' => $this->getFile(),
			'line' => $this->getLine(),
			'filename' => basename($this->getFile()),
			'dirname' => dirname($this->getFile()),
			'dom_id' => $dom_id,
			'source' => Profiler::getHighlightedSource($this->getFile(), $this->getLine()),
		);
		$this->infos['traces'] = Profiler::getHighlightedTraces($this->getTrace(), $this->infos);
		$this->debugger = Debugger::getInstance();
		$this->debugger->addStack('message', $this->infos);
		$this->debugger->setDebuggerTitle( self::_buildExceptionStr(true), Url::getRequestUrl() );
	}

	/**
	 * When the exception is written
	 */
	public function __toString() 
	{
        if (defined('_DEVDEBUG_SHUTDOWN_HANDLER') && true===_DEVDEBUG_SHUTDOWN_HANDLER) {
            return '';
        } elseif (!empty($this->debugger)) {
            echo $this->debugger->__toString();
            exit;
        } else {
            return parent::__toString();
        }
	}
	
	/**
	 * Construction of the information string
	 *
	 * @param bool $short Build a short or long string (default is FALSE, long)
	 * @return string The information about the exception
	 */
	private function _buildExceptionStr($short = false)
	{
		if ($short===false) {
			$str = sprintf(self::long_title, $this->getMessage(), $this->getCode());
			if (!empty($this->php_error_message))
				$_str .= " | ".$this->php_error_message;
		} else {
			$str = sprintf(self::short_title, $this->getMessage(), $this->getCode());
		}
		return $str;
	}

	/**
	 * Set concerned line number
	 *
	 * @param numeric $line The line number
	 */
	public function setLine($line) 
	{ 
		$this->line = $line;
	}

	/**
	 * Set concerned file name
	 *
	 * @param string $file The file name
	 */
	public function setFile($file) 
	{
		$this->file = $file;
	}

}

// Endfile