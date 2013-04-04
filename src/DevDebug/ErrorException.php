<?php
/**
 * DevDebug - PHP framework package
 * Copyleft (c) 2013 Pierre Cassat and contributors
 * <www.ateliers-pierrot.fr> - <contact@ateliers-pierrot.fr>
 * License GPL-3.0 <http://www.opensource.org/licenses/gpl-3.0.html>
 * Sources <https://github.com/atelierspierrot/devdebug>
 */

namespace DevDebug;

use \ErrorException as InternalErrorException;

use Library\Helper\Url;

/**
 * Special application error handler
 *
 * To use it, write something like :
 *
 *     trigger_error( 'Error info', E_USER_ERROR|E_USER_WARNING|E_USER_NOTICE );
 *
 * @author 		Piero Wbmstr <piero.wbmstr@gmail.com>
 */
class ErrorException extends InternalErrorException
{

	/**
	 * Received arguments (not define in parent)
	 */
	protected $trace;
	protected $previous;
	protected $php_error_message;
	protected $exit=false;
	protected $type;

	/**
	 * Table of informations for display
	 */
	public $infos=array();

	/**
	 * The debugger object
	 * @see App\Debugger
	 */
	protected $debugger;

	/**
	 * Masks for string construction
	 */
	const long_title = '[%s] The system has encountered the following error : \'%s\' [%d]';
	const short_title = '%s : \'%s\'';

	static $error_types = array( 
		array( 'type'=>'fatal', 'scope'=>'Fatal error(s)', 'priority'=>10 ), 
		array( 'type'=>'warning', 'scope'=>'Warning(s)', 'priority'=>9 ), 
		array( 'type'=>'parse', 'scope'=>'Parse error(s)', 'priority'=>8 ), 
		array( 'type'=>'notice', 'scope'=>'Notice(s)', 'priority'=>7 ), 
		array( 'type'=>'deprecated', 'scope'=>'Deprecated error(s)', 'priority'=>6 ), 
		array( 'type'=>'catchable', 'scope'=>'Catchable error(s)', 'priority'=>5 ), 
		array( 'type'=>'unknown', 'scope'=>'Unknown error(s)', 'priority'=>4 ), 
		array( 'type'=>'exception', 'scope'=>'Exception(s)', 'priority'=>3 ), 
 	);

	/**
	 * Construction of the error - a message is needed (1st argument)
	 *
	 * @param string $message The error message
	 * @param numeric $code The error code
	 * @param numeric $severity The error severity code
	 * @param string $filename The file name of the error
	 * @param numeric $lineno The line number of the error
	 * @param misc $previous The previous error if so
	 */
	public function __construct($message, $code = 0, $severity = 1, $filename = __FILE__, $lineno = __LINE__, $previous = null) 
	{
	    // This error code is not in the error_reporting()
		if (!(error_reporting() & $code)) {
			return;
	    }

		// We let the default PHP Exception manager construction
		parent::__construct($message, $code, $severity, $filename, $lineno, $previous);

		$this->trace = parent::getTrace();
		$this->previous = parent::getPrevious();
		if (isset($php_errormsg))
			$this->php_error_message = $php_errormsg;

	    switch ($this->getCode()) {
			case E_ERROR:
			case E_USER_ERROR:
				$this->setType( 'fatal' );
				$this->setScope( 'Fatal Error' );
				$this->exit=true;
				break;
			case E_WARNING:
			case E_USER_WARNING:
				$this->setType( 'warning' );
				$this->setScope( 'Warning' );
				break;
			case E_NOTICE:
			case E_USER_NOTICE:
			case @E_STRICT:
				$this->setType( 'notice' );
				$this->setScope( 'Notice' );
				break;
			case @E_RECOVERABLE_ERROR:
				$this->setType( 'catchable' );
				$this->setScope( 'Catchable Error' );
				break;
			case E_PARSE:
				$this->setType( 'parse' );
				$this->setScope( 'Parsing Error' );
				break;
			case @E_DEPRECATED:
			case @E_USER_DEPRECATED:
				$this->setType( 'deprecated' );
				$this->setScope( 'Deprecated Error' );
				break;
			default:
				$this->setType( 'unknown' );
				$this->setScope( 'Unknown Error' );
				$this->exit=true;
				break;
		}

		$dom_id = Profiler::getNewDomId( $this->getType() );
		$this->infos = array(
			'message' => self::_buildExceptionStr(),
			'scope'=>$this->getScope(),
			'type'=>$this->getType(),
			'file' => $this->getFile(),
			'line' => $this->getLine(),
			'severity' => $this->getSeverity(),
			'filename' => basename($this->getFile()),
			'dirname' => dirname($this->getFile()),
			'dom_id' => $dom_id,
			'source' => Profiler::getHighlightedSource($this->getFile(), $this->getLine()),
		);
		$this->infos['traces'] = Profiler::getHighlightedTraces($this->getTrace(), $this->infos);
		$this->debugger =& Debugger::getInstance();
		$this->debugger->addStack('message', $this->infos);
		$this->debugger->setDebuggerTitle( self::_buildExceptionStr(true), Url::getCurrentUrl() );
		return false;
	}

	/**
	 * When the error is written
	 */
	public function __toString() 
	{
        return $this->debugger."\n"
            .'<pre>'.trim(stripslashes(print_r($this->infos['traces'],1)), "\"'\n ").'</pre>'."\n"
            .$this->infos['source'];
	}
  
	/**
	 * Construction of informations strings
	 *
	 * @param bool $short Build a short or long string (default is FALSE, long)
	 * @return string The information about the exception
	 */
	private function _buildExceptionStr($short = false)
	{
		if ($short===false) {
			$str = sprintf(self::long_title, $this->getScope(), $this->getMessage(), $this->getCode());
			if (!empty($this->severity))
				$str .= " | severity : ".$this->getSeverity();
			if (!empty($this->php_error_message))
				$str .= " | ".$this->php_error_message;
		} else {
			$str = sprintf(self::short_title, $this->getScope(), $this->getMessage(), $this->getCode());
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

	/**
	 * Set the error scope
	 *
	 * @param string $scope The error scope info
	 */
	public function setScope($scope) 
	{
		$this->scope = $scope;
	}

	/**
	 * Get the error scope
	 *
	 * @return string The error scope info
	 */
	public function getScope() 
	{
		return $this->scope;
	}

	/**
	 * Set the error type (from self::$error_types)
	 *
	 * @param string $type The error type info
	 */
	public function setType($type) 
	{
		$this->type = $type;
	}

	/**
	 * Get the error type
	 *
	 * @return string The error type info
	 */
	public function getType() 
	{
		return $this->type;
	}

}

// Endfile