<?php
/**
 * DevDebug - PHP framework package
 * Copyleft (c) 2013 Pierre Cassat and contributors
 * <www.ateliers-pierrot.fr> - <contact@ateliers-pierrot.fr>
 * License GPL-3.0 <http://www.opensource.org/licenses/gpl-3.0.html>
 * Sources <https://github.com/atelierspierrot/devdebug>
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
		$this->debugger =& Debugger::getInstance();
		$this->debugger->addStack('message', $this->infos);
		$this->debugger->setDebuggerTitle( self::_buildExceptionStr(true), Url::getRequestUrl() );
	}

	/**
	 * When the exception is written
	 */
	public function __toString() 
	{
        return $this->debugger->__toString();
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