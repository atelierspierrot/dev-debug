<?php
/**
 * DevDebug - PHP framework package
 * Copyleft (c) 2013 Pierre Cassat and contributors
 * <www.ateliers-pierrot.fr> - <contact@ateliers-pierrot.fr>
 * License GPL-3.0 <http://www.opensource.org/licenses/gpl-3.0.html>
 * Sources <https://github.com/atelierspierrot/devdebug>
 */

namespace DevDebug;

use Library\Helper\Url;

/**
 * Profiler
 *
 * The global application profiler : rendering of debug infos, errors or exceptions ...
 *
 * @author 		Pierre Cassat & contributors <piero.wbmstr@gmail.com>
 */
class Profiler
{

	public $debugger;
	protected $title;
	protected $url;
	protected $current_entity;

	static $not_real_fcts = array('require', 'require_once', 'include', 'include_once');
	static $dom_ids = array();

	public function __construct(Debugger $debugger)
	{
		$this->debugger = $debugger;
	}

// ----------------------------------
// Masks
// ----------------------------------

	const limit_string_length_to_show = 12;
	const limit_param_string_length = 100;
	const limit_value_string_length = 255;
	const default_profiler_title = 'Profiler';
	const mask_profiler_url = ' on request <a href="%s" title="Back to this page"><em>%s</em></a>';
	const mask_profiler_info = 'by %s at %s';
	const mask_abbr = '<abbr title="%s">%s</abbr>';
	const mask_param_type = '<em>%s</em>';
	const mask_code = '<pre><code>%s</code></pre>';
	const mask_dom_linker = '<span class="linker">[<a href="#%s" title="See %s" class="linker_handler">%s</a>]</span>';
	const mask_bloc_linker = '<span class="linker">[<a href="javascript:show_hide(\'%s\', \'show_bloc\');" title="Show/Hide %s" class="linker_handler">%s</a>]</span>';
	const mask_bloc_altinfo = '<div class="%s hide" id="%s">%s</div>';
	const mask_trace_items_wrapper = '<ol class="php_trace_items">%s</ol>';
	const mask_trace_item = '<li class="php_trace_item" id="%s">%s</li>';
	const mask_trace_item_on = '<li class="php_trace_item on" id="%s">%s</li>';
	const mask_trace_item_content = '%s<br />%s %s %s';
	const mask_trace_item_call_info = 'at <strong class="call_name">%s</strong> ( %s )';
	const mask_trace_item_position_info = 'in <strong class="file_name">%s</strong> at line <strong class="line_number">%d</strong>';
	const mask_source_highlighted_wrapper = '<ol start="%s" class="source_lines">%s</ol>';
	const mask_source_highlighted_item = '<li class="source_line"><code>%s</code></li>';
	const mask_source_highlighted_item_on = '<li class="source_line on"><code>%s</code></li>';
	const mask_table_line = '<tr>%s</tr>';
	const mask_table_wrapper = '<table class="table_values"><caption>%s</caption><thead>%s</thead><tbody>%s</tbody></table>';
	const mask_table_head_cell = '<th>%s</th>';
	const mask_table_content_cell = '<td>%s</td>';
	const mask_a_link = '<a href="%1$s" title="Go to %1$s">%1$s</a>';
	const mask_mailto_link = '<a href="mailto:%1$s" title="Mail to %1$s">%1$s</a>';
	const mask_message_item = '<div class="error_item" id="%s">%s</div>';
	const mask_message_info = '<span style="color:blue"><strong>%s</strong></span>';
	const mask_messages_group_title = '<strong class="error_types_title">%d %s :</strong>';
	const mask_messages_group_item = '<div class="error_types_group %s">%s<br />%s</div>';

// ----------------------------------
// Renders
// ----------------------------------

	public function renderProfilingTitle($format = 'html')
	{
		$title='';
		if (empty($this->title))
			$title .= self::default_profiler_title;
		else
			$title .= $this->title;
		if (!empty($this->url))
		{
			$_url = str_replace('&', '&amp;', $this->url);
			$title .= sprintf(self::mask_profiler_url, $_url, str_replace(_ROOTHTTP, '/', $_url));
		}
		return $title;
	}

	public function renderProfilingInfo($format = 'html')
	{
		if( isset($_SERVER['HTTP_X_FORWARDED_FOR']) )
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR']; 
		elseif( isset($_SERVER['HTTP_CLIENT_IP']) )
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		else
			$ip = $_SERVER['REMOTE_ADDR'];
		$info = sprintf(self::mask_abbr, date('c').' '.date_default_timezone_get(), strftime('%c', time()));
		return sprintf(self::mask_profiler_info, $ip, $info);
	}

	public function renderMessages($messages, $format = 'html')
	{
		// organize types by priority
		$organized_messages=$organized_types=array();
		foreach($messages as $type=>$type_messages) 
		{
			foreach(ErrorException::$error_types as $type_info) 
			{
				if ($type_info['type']==$type) 
				{
					$organized_messages[$type_info['priority']] = $type_messages;
					$organized_types[$type_info['priority']] = $type_info;
				}
			}
		}
		$organized_messages = array_reverse($organized_messages);
		$organized_types = array_reverse($organized_types);

		// then render messages
		$str='';
		foreach($organized_messages as $type=>$type_messages) 
		{
			$str .= self::renderMessagesFromType( $organized_types[$type], $type_messages, $format );
		}
		return $str;
	}

	public function renderMessagesFromType($type, $messages, $format = 'html')
	{
		if (!count($messages)) return '';
		$title_str = sprintf(self::mask_messages_group_title, count($messages), $type['scope']);
		$str = '';
		foreach($messages as $message) {
			self::setCurrentEntity( $message->getEntity() );
			$str .= self::renderMessage( $format );
		}
		return sprintf(self::mask_messages_group_item, $type['type'], $title_str, $str);
	}

	public function renderMessage($format = 'html')
	{
		$trace = $this->current_entity;
		$trace = self::buildTraces( array(0=>$trace) );
		return self::formatMessage( $trace[1], $format );
	}

	public function renderEnvironment($format = 'html')
	{
		$sys = self::getEnvironment();
		$sys = self::buildTableValues( $sys );
		return self::formatTableValues( $sys, $format, 'PHP Environment' );
	}

	public function renderObject($format = 'html')
	{
		if (empty($this->entity)) return '';
		$obj = self::buildObject( $this->entity );
		return self::formatObject( $obj, $format );
	}

	public function renderTraces($format = 'html')
	{
		if (empty($this->traces)) return self::renderBacktrace($format);
		$traces = self::buildTraces( $this->traces );
		return self::formatTrace( $traces, $format );
	}

	public function renderBacktrace($format = 'html')
	{
		$trace = self::getBacktrace();
		$trace = self::buildTraces( $trace );
		return self::formatTrace( $trace, $format );
	}

	public function renderSystem($format = 'html')
	{
		$sys = self::getSystem();
		$sys = self::buildTableValues( $sys );
		return self::formatTableValues( $sys, $format, 'System Environment', array('global', 'local') );
	}

	public function renderSession($format = 'html')
	{
		$session = self::getSession();
		$session = self::buildTableValues( $session );
		return self::formatTableValues( $session, $format, 'Session Attributes' );
	}

	public function renderRequestHeaders($format = 'html')
	{
		$headers = self::getRequestHeaders();
		$headers = self::buildTableValues( $headers );
		return self::formatTableValues( $headers, $format, 'Request Headers' );
	}

	public function renderServerParams($format = 'html')
	{
		$headers = self::getServerParams();
		$headers = self::buildTableValues( $headers );
		return self::formatTableValues( $headers, $format, 'Server Parameters' );
	}

	public function renderConstants($format = 'html')
	{
		$constants = self::getConstants();
		$constants = self::buildTableValues( $constants );
		return self::formatTableValues( $constants, $format, 'Defined Constants' );
	}

	public function renderHeaders($format = 'html')
	{
		$global='';
		if (php_sapi_name() == 'cli') return $global;

		// request headers
		$headersrequest = self::getRequestHeaders();
		$headersrequest = self::buildTableValues( $headersrequest );
		$global .= self::formatTableValues( $headersrequest, $format, 'Request Headers' );
		// cookies
		$headerscookies = self::getCookies();
		$headerscookies = self::buildTableValues( $headerscookies );
		$global .= self::formatTableValues( $headerscookies, $format, 'Cookies' );
		// get
		$headersget = self::getGet();
		$headersget = self::buildTableValues( $headersget );
		$global .= self::formatTableValues( $headersget, $format, 'GET Parameters' );
		// post
		$headerspost = self::getPost();
		$headerspost = self::buildTableValues( $headerspost );
		$global .= self::formatTableValues( $headerspost, $format, 'POST Parameters' );
		
		return $global;
	}

// ----------------------------------
// Builders
// ----------------------------------

	/**
	 * Build an array of key=>value pairs
	 * @param array $array The array to build
	 * @return array The re-formated entered array
	 */
	public static function buildTableValues($array)
	{
		if (!is_array($array)) return $array;
		$built_table = self::buildArguments($array);
		return $built_table;
	}

	/**
	 * Build an object
	 * @param object $entity The object to build
	 * @return array The re-formated entered array
	 */
	public static function buildObject($object)
	{
		return $object;
	}

	/**
	 * Build each line of a PHP trace
	 * @param array $trace A trace to format
	 * @return array The re-formated entered array
	 */
	public static function buildTraces($trace)
	{
		if (!is_array($trace)) $trace = array( 0=>$trace );
		$built_trace = array();
		$trace_count = count($trace);
		foreach($trace as $k=>$trace_item) 
		{
			$item = $trace_item;
			// dom ID
			if (empty($item['dom_id']))
				$item['dom_id'] = self::getNewDomId('trace');
			// filename and dirname
			if (isset($item['file'])) {
				$item['filename'] = basename( $item['file'] );
				$item['dirname'] = dirname( $item['file'] ).'/';
				// source code
				if (isset($item['line'])) {
					$item['source'] = self::getHighlightedSource( $item['file'], $item['line'], (isset($item['function']) ? $item['function'] : '') );
				}
			}
			// the complete method call
			if (isset($item['class'])) {
				$item['called'] = $item['class'].$item['type'].$item['function'];
			} else {
				$item['called'] = $item['function'];
			}
			// formated args
			if (isset($item['args']) && count($item['args'])>0) {
				$item['nb_arguments'] = count( $item['args'] );
				$item['arguments'] = self::buildArguments( $item['args'], $item['function'], (isset($item['class']) ? $item['class'] : null) );
			}
			$built_trace[$trace_count] = $item;
			$trace_count--;
		}
		return $built_trace;
	}

	/**
	 * Build each argument of a call
	 * @param array $args The arguments passed
	 * @param string $fct The function called
	 * @param string $class The class concernend (optional)
	 * @return array The re-formated entered args
	 */
	public static function buildArguments($args, $fct = null, $class = null)
	{
		if (!is_array($args)) $args = array( 0=>$args );
		$built_args = array();

		if (empty($class) && !empty($fct) && in_array($fct, self::$not_real_fcts)) 
			return $args;

		if (!empty($class) && method_exists($class, $fct) && is_callable(array($class, $fct))) {
			$methodReflect = new \ReflectionMethod($class, $fct);
			$argumentsReflect = $methodReflect->getParameters();
		} elseif(!empty($fct) && function_exists($fct)) {
			$functionReflect = new \ReflectionFunction($fct);
			$argumentsReflect = $functionReflect->getParameters();
		}

		foreach($args as $k=>$arg_item) 
		{
			$item = array();
			$item['value'] = $arg_item;
			$item['type'] = gettype($arg_item);
			$item['index'] = is_string($k) ? $k : $k+1;
			if (isset($argumentsReflect[$k])) {
				$paramReflect = $argumentsReflect[$k];
				$item['source'] = $paramReflect->__toString();
				if ($paramReflect->isPassedByReference())
					$item['reference'] = true;
			}
			$built_args[is_string($k) ? $k : $k+1] = $item;
		}

		return $built_args;
	}

	public static function buildClassInfo($classname)
	{
		$built_class = array( 'name'=>$classname );
		if (!class_exists($classname)) return $built_class;

		$clsReflect = new \ReflectionClass($classname);
		$built_class['name'] = $clsReflect->getShortName();
		$built_class['full_name'] = $clsReflect->getName();
		$built_class['filename'] = $clsReflect->getFileName();
		$built_class['start_line'] = $clsReflect->getStartLine();
		$built_class['end_line'] = $clsReflect->getEndLine();

		$parent = $clsReflect->getParentClass();
		if (!empty($parent))
			$built_class['parent'] = self::buildClassInfo( $parent->getName() );

		$interfaces = $clsReflect->getInterfaceNames();
		if (count($interfaces)) {
			$built_class['interfaces'] = array();
			foreach($interfaces as $interface) {
				$built_class['interfaces'][] = self::buildClassInfo($interface);
			}
		}
		return $built_class;		
	}
	
// ----------------------------------
// Formats
// ----------------------------------

	/**
	 * Format a full PHP array of key=>value pairs
	 * @param array $array The array to format
	 * @return string The formated string to display
	 */
	public static function formatTableValues(
	    $array, $format = 'html', $caption = '',
	    $value_cells = array('normal'), $key_head = 'Key', $value_head = 'Value'
	) {
		if (!is_array($array)) return '';
		$count = count($array);
		if ($count==0) return (!empty($caption) ? $caption.' : ' : '').'No entry';

		$caption = (!empty($caption) ? $caption.' (' : '').$count.($count>1 ? ' entries' : ' entry').')';

		if ($value_cells==array('normal')) 
		{
			$table_head = sprintf(self::mask_table_line,
					sprintf(self::mask_table_head_cell, $key_head)
				.sprintf(self::mask_table_head_cell, $value_head)
			);

			$table_content = '';
			foreach($array as $key=>$array_value) 
			{
				$line_content = '';
				$line_content .= sprintf(self::mask_table_content_cell, $key);
				$line_content .= sprintf(self::mask_table_content_cell, self::formatValue($array_value));
				$table_content .= sprintf(self::mask_table_line, $line_content);
			}
		} else {
			$line_head = '';
			$line_head .= sprintf(self::mask_table_head_cell, $key_head);
			foreach($value_cells as $k=>$v){
				$line_head .= sprintf(self::mask_table_head_cell, $v);
			}
			$table_head = sprintf(self::mask_table_line, $line_head);

			$table_content = '';
			foreach($array as $key=>$array_value) 
			{
				$line_content = '';
				$line_content .= sprintf(self::mask_table_content_cell, $key);
				$item = self::buildArguments($array_value['value']);
				$cell_count=0;
				foreach($item as $k=>$v)
				{
					if (isset($value_cells[$cell_count]))
						$line_content .= sprintf(self::mask_table_content_cell, self::formatValue($v));
					$cell_count++;
				}
				$table_content .= sprintf(self::mask_table_line, $line_content);
			}
		}

		return sprintf(self::mask_table_wrapper, $caption, $table_head, $table_content);
	}

	/**
	 * Format a function value info
	 * @param array $item The considered value infos array
	 * @param string $format The format to send (HTML by default)
	 * @return string The formated string to display
	 */
	public static function formatValue($item, $format = 'html')
	{
		$param_str = '';
		$index='';

		if ($item['type']=='NULL') {
				$param_str .= 'NULL';

		} elseif ($item['type']=='boolean') {
				$param_str .= ( $item['value']===true ? 'true' : 'false' );

		} elseif ($item['type']=='integer' || $item['type']=='double') {
				$param_str .= $item['value'];

		} elseif ($item['type']=='string') {
			$ln = strlen($item['value']);
			if ($ln==0) {
				$param_str .= "'' (empty)";
			} elseif ($ln<=self::limit_value_string_length) {
				$param_str .= self::checkString( $item['value'] );
			} else {
				$param_str .= sprintf(self::mask_param_type, $param_type).'('.strlen($item['value']).') : '
					.self::stringExtract($item['value'], self::limit_value_string_length);
			}

		} elseif ($item['type']=='array') {
			$param_str .= sprintf(self::mask_param_type, 'array').'(';
			$items_built = array();
			foreach($item['value'] as $_k=>$subitem) {
				$sub_item_built = self::buildArguments( $subitem );
				$items_built[$_k] = self::formatParam( $sub_item_built[1], $_k, $format );
			}
			$param_str .= implode(', ', $items_built).')';

		} elseif ($item['type']=='object') {
			$param_str .= sprintf(self::mask_param_type, 'object').'(';
			$cls_info = self::buildClassInfo( get_class($item['value']) );
			$param_str .= sprintf(self::mask_abbr, 
				self::formatClassName($cls_info, 'txt'), get_class($item['value']));
			$param_str .= ')';

		} elseif ($item['type']=='resource') {
			$param_str .= sprintf(self::mask_param_type, 'resource').'(';
			$param_str .= get_resource_type($item['value']);
			$param_str .= ')';

		} else {
			$param_str .= '?';
		}

		return $param_str;
	}
	
	/**
	 * Format an object
	 * @param object $object The object
	 * @return string The formated string to display
	 */
	public static function formatObject($object, $format = 'html')
	{
		return '<pre>'.strip_tags( print_r( $object, 1 ) ).'</pre>';
	}
	
	/**
	 * Format a full array of PHP traces
	 * @param array $trace The traces array
	 * @return string The formated string to display
	 */
	public static function formatTrace($trace, $format = 'html')
	{
		if (!is_array($trace)) $trace = array( 0=>$trace );
		$traces_str = '';

		foreach($trace as $k=>$item)
		{
			$dom_id = isset($item['dom_id']) ? $item['dom_id'] : uniqid();
			if (isset($item['highlighted']) && $item['highlighted']==true)
				$traces_str .= sprintf(self::mask_trace_item_on, $dom_id, self::formatTraceItem( $item, $k ));
			else
				$traces_str .= sprintf(self::mask_trace_item, $dom_id, self::formatTraceItem( $item, $k ));
		}

		return sprintf(self::mask_trace_items_wrapper, $traces_str);
	}
	
	/**
	 * Each item of exploded backtrace PHP
	 * @param array $item The trace item array
	 * @param numeric $key The item key
	 * @param string $format The format to send (HTML by default)
	 * @return string The formated string to display
	 */
	public static function formatTraceItem($item, $key, $format = 'html')
	{
		$item_str='';

		$params = array();
		if (!empty($item['arguments']))
		foreach($item['arguments'] as $k=>$arg) 
		{
			$params[] = self::formatParam( $arg, $k, $format );
		}

		$item_str_call_info = '';

		if (!empty($item['message']))
			$item_str_call_info .= sprintf(self::mask_message_info, $item['message']);

		$call_info = $item['called'];
		$parts=array();
		$jointure='';
		if (substr_count($item['called'], '->')) 
		{
			$jointure='->';
			$parts = preg_split('/->/', $item['called']);
		}
		if (substr_count($item['called'], '::')) 
		{
			$jointure='::';
			$parts = preg_split('/::/', $item['called']);
		}
		if (count($parts)) 
		{
			$cls_info = self::buildClassInfo( $parts[0] );
			$call_info = sprintf(self::mask_abbr, 
				self::formatClassName($cls_info, 'txt'), $parts[0]).$jointure.$parts[1];
		}
		$item_str_call_info .= !empty($call_info) ? sprintf(self::mask_trace_item_call_info, 
			$call_info, implode(', ', $params)			
		) : '';

		$item_str_position_info = !empty($item['line']) ? 
			sprintf(self::mask_trace_item_position_info,
				self::formatPath($item['file'], $format), $item['line']
			) : '';

		$bloc_linkers = $bloc_altinfos = '';

		if (!empty($item['source'])) 
		{
			$source_bloc_id = 'sb_'.uniqid();
			$bloc_linkers .= sprintf(self::mask_bloc_linker, $source_bloc_id, 'the source code', 'source');
			$bloc_altinfos .= sprintf(self::mask_bloc_altinfo, 'source_code', $source_bloc_id, $item['source']);
		}

		if (!empty($item['trace'])) 
		{
			$trace_bloc_id = 'tb_'.uniqid();
			$bloc_linkers .= sprintf(self::mask_bloc_linker, $trace_bloc_id, 'the full PHP trace', 'full trace');
			$trace_str = sprintf(self::mask_code, self::dumpArray($item['trace']));
			$bloc_altinfos .= sprintf(self::mask_bloc_altinfo, 'full_trace', $trace_bloc_id, $trace_str);
		}

		if (!empty($item['related_dom_id'])) 
		{
			$_ttl = !empty($item['message']) ? 'trace' : 'message';
			$bloc_linkers .= sprintf(self::mask_dom_linker, $item['related_dom_id'], 'related '.$_ttl, $_ttl);
		}

		return sprintf(self::mask_trace_item_content, 
			$item_str_call_info, $item_str_position_info, $bloc_linkers, $bloc_altinfos
		);
	}

	/**
	 * Format a function parameter info
	 * @param array $item The considered parameter infos array
	 * @param numeric $key The parameter index
	 * @param string $format The format to send (HTML by default)
	 * @return string The formated string to display
	 */
	public static function formatParam($item, $key = null, $format = 'html')
	{
		$param_str = '';
		$index='';
		if (!empty($key)) 
		{
			 if (is_string($key)) $index = '['.$key.'] ';
			 else $index = '#'.$key.' ';
		}

		if ($item['type']=='NULL') 
		{
			if (is_string($key)) {
				$param_str .= sprintf(self::mask_abbr, $index, 'NULL');
			} else {
				$param_str .= 'NULL';
			}

		} elseif ($item['type']=='boolean') {
			if (is_string($key)) {
				$param_str .= sprintf(self::mask_abbr, $index.'Boolean', ( $item['value']===true ? 'true' : 'false' ));
			} else {
				$param_str .= ( $item['value']===true ? 'true' : 'false' );
			}

		} elseif ($item['type']=='integer' || $item['type']=='double') {
			if (is_string($key)) {
				$param_str .= sprintf(self::mask_abbr, $index.'Numeric', "'".$item['value']."'");
			} else {
				$param_str .= "'".$item['value']."'";
			}

		} elseif ($item['type']=='string') {
			$ln = strlen($item['value']);
			if ($ln<=self::limit_string_length_to_show) {
				$param_str .= "'".self::checkString( $item['value'] )."'";
			} else {
				$param_type = sprintf(self::mask_abbr, 
					$index.'String of length '.strlen($item['value']).' : '.self::stringExtract($item['value'], self::limit_param_string_length), 'string');
				$param_str .= sprintf(self::mask_param_type, $param_type).'('.strlen($item['value']).')';
			}

		} elseif ($item['type']=='array') {
			$param_type = sprintf(self::mask_abbr, 
				$index.'Array composed of '.count($item['value']).' entries', 'array');
			$param_str .= sprintf(self::mask_param_type, $param_type).'(';
			$items_built = array();
			foreach($item['value'] as $_k=>$subitem) 
			{
				$sub_item_built = self::buildArguments( $subitem );
				$items_built[$_k] = self::formatParam( $sub_item_built[1], $_k, $format );
			}
			$param_str .= implode(', ', $items_built).')';

		} elseif ($item['type']=='object') {
			$param_type = sprintf(self::mask_abbr, 
				$index.'Object of type '.get_class($item['value']), 'object');
			$param_str .= sprintf(self::mask_param_type, $param_type).'(';
			$cls_info = self::buildClassInfo( get_class($item['value']) );
			$param_str .= sprintf(self::mask_abbr, 
				self::formatClassName($cls_info, 'txt'), get_class($item['value']));
			$param_str .= ')';

		} elseif ($item['type']=='resource') {
			$param_type = sprintf(self::mask_abbr, 
				$index.'Resource of type '.get_resource_type($item['value']), 'resource');
			$param_str .= sprintf(self::mask_param_type, $param_type).'(';
			$param_str .= get_resource_type($item['value']);
			$param_str .= ')';

		} else {
			$_str = @var_export($item['value'],1);
			$param_str .= sprintf(self::mask_abbr, $index.'Unknown type : '.$_str, '?');
		}

		return $param_str;
	}
	
	/**
	 * Format a class name
	 * @param array $item The considered class array
	 * @param string $format The format to send (HTML by default)
	 * @return string The formated string to display
	 */
	public static function formatClassName($class, $format = 'html')
	{
/*
// TODO : trouver un switcher HTML / TXT
		$cls_str = 'Object of class '.$class['full_name'].' [';
		$cls_str .= sprintf(self::mask_trace_item_position_info,
			self::formatPath($class['filename'], $format), $class['start_line']
		);
		$cls_str .= ']';
*/
		$cls_str = 'Object of class '.$class['full_name'].' [in '.$class['filename'].' at line '.$class['start_line'].']';
		if (!empty($class['parent'])) 
		{
			$cls_str .= ' extending class '.$class['parent']['full_name'];
			if (!empty($class['parent']['filename']) && !empty($class['parent']['start_line']))
				$cls_str .= ' [in '.$class['parent']['filename'].' at line '.$class['parent']['start_line'].']';
		}
		if (count($class['interfaces']))
		foreach($class['interfaces'] as $int) 
		{
			$cls_str .= ' implementing interface '.$int['full_name'].' [in '.$int['filename'].' at line '.$int['start_line'].']';
		}
		return $cls_str;
	}
	
	public static function formatPath($path, $format = 'html')
	{
		$rootdir_info = $format=='html' ? sprintf(self::mask_abbr, _ROOTDIR, '_ROOTDIR').'/' : '_ROOTDIR/';
		return str_replace(_ROOTDIR, $rootdir_info, $path);
	}

	/**
	 * HTML debug information builder
	 * @param array $infos An array of various informations to write
	 * @param string $format Build a string with HTML tags or not (default is TRUE)
	 * @return string An HTML string to print
	 */
	public static function formatMessage($infos, $format = 'html')
	{
		return sprintf(self::mask_message_item, (isset($infos['dom_id']) ? $infos['dom_id'] : ''), self::formatTraceItem($infos, 0));
	}
	
// ----------------------------------
// Getters / Setters
// ----------------------------------

	public function setTitle($str)
	{
		$this->title = $str;
	}
	
	public function setUrl($url)
	{
		$this->url = $url;
	}
	
	public function setCurrentEntity($entity)
	{
		$this->current_entity = $entity;
	}
	
	public function setTraces($traces)
	{
		if (!empty($traces)) {
			$this->traces = is_array($traces) ? $traces : array( 0=>$traces );
		}
	}

	public static function getEnvironment()
	{
		$vals = array(
			'PHP interface/version'=>php_sapi_name().' '.PHP_VERSION,
			"php.ini"=>function_exists('php_ini_loaded_file') ? php_ini_loaded_file() : realpath('php.ini'),
			'Operating system'=>php_uname(),
			'Zend version'=>zend_version(),
			'Directory separator'=>'DIRECTORY_SEPARATOR='.DIRECTORY_SEPARATOR,
			'Path separator'=>'PATH_SEPARATOR='.PATH_SEPARATOR,
			'Libraries suffix'=>'PHP_SHLIB_SUFFIX='.PHP_SHLIB_SUFFIX,
		);
		return $vals;
	}

	public static function getSystem()
	{
		return self::ksort(ini_get_all());
	}

	public static function getSource($file)
	{
		return nl2br(highlight_file($file,TRUE));
	}

	/**
	 * Get backtrace PHP
	 */
	public static function getBacktrace()
	{
		return debug_backtrace();
	}

	/**
	 * Get current session
	 */
	public static function getSession()
	{
		return self::ksort($_SESSION);
	}

	/**
	 * Get request headers parameters
	 */
	public static function getRequestHeaders()
	{
		return self::ksort(getallheaders());
	}

	/**
	 * Get request server parameters
	 */
	public static function getServerParams()
	{
		return self::ksort($_SERVER);
	}

	/**
	 * Get PHP defined constants
	 */
	public static function getConstants($arg = 'user')
	{
		$csts = get_defined_constants(true);
		return !empty($arg) && isset($csts[$arg]) ? self::natcasesort( $csts[$arg] ) : self::natcasesort( $ctsts );
	}

	/**
	 * Get PHP defined functions
	 */
	public static function getFunctions($arg = 'user')
	{
		$f_list = array_reverse(get_defined_functions());
		if (!empty($arg))
		switch($arg) 
		{
			case 'user': $f_list = $f_list['user']; break;
			case 'internal': unset($f_list['user']); break;
			default: break;
		}
		return self::natcasesort($f_list);
	}

	/**
	 * Get PHP defined classes
	 */
	public static function getClasses()
	{
		return self::natcasesort(get_declared_classes());
	}

	/**
	 * Get PHP defined interfaces
	 */
	public static function getInterfaces()
	{
		return self::natcasesort(get_declared_interfaces());
	}

	/**
	 * Get PHP defined included PHP files
	 */
	public static function getIncludedFiles()
	{
		return get_included_files();
	}

	/**
	 * Get cookies values
	 */
	public static function getCookies()
	{
		return self::ksort($_COOKIE);
	}

	/**
	 * Get getted variables
	 */
	public static function getGet()
	{
		return self::ksort($_GET);
	}

	/**
	 * Get posted variables
	 */
	public static function getPost()
	{
		return self::ksort($_POST);
	}

	/**
	 * Get a formated and highlighted source code extract
	 * @param string $file The file where to find the source
	 * @param numeric $line The line number to see
	 * @param string $fct A function name to see
	 * @param numeric $surrounder Maximum number of lines to show before and after
	 * @return string The formated and highlighted source code
	 */
	public static function getHighlightedSource($file, $line, $fct = null, $surrounder = 5)
	{
		$src = self::getSource($file);
		$lines = explode("<br />", $src);
		$start = $line<$surrounder ? 0 : $line-$surrounder;
		$end = $line+$surrounder;
		$out = '';
		foreach( $lines as $k => $_line ) 
		{
			if ($k>$end ) { break; }
			$_line = self::cleanCode($_line);
			if ($k<$start && !empty($fct) && preg_match('/function( )*'.preg_quote($fct).'/', $_line)) 
			{
				$start = $k;
			}
			if ($k>=$start)
			{
				if ($k!=$line) {
					$out .= sprintf(self::mask_source_highlighted_item, self::cleanCode($_line));
				} else {
					$out .= sprintf(self::mask_source_highlighted_item_on, self::cleanCode($_line));
				}
			}
		}
		return sprintf(self::mask_source_highlighted_wrapper, $start, $out);
	}

	/**
	 * Get an array of traces with highlighted trace if found
	 * @param array $traces The traces array
	 * @param string $file The file where to find the source
	 * @param numeric $line The line number to see
	 * @param string $dom_id The referer object DOM ID
	 * @return array The traces array with the highlighted trace
	 */
	public static function getHighlightedTraces($traces, &$item)
	{
		$traces = self::buildTraces($traces);
		if (empty($item['file']) || empty($item['line'])) return $traces;
		foreach($traces as $_i=>$_trace) 
		{
			if (
				(!empty($_trace['file']) && $_trace['file']==$item['file']) &&
				(!empty($_trace['line']) && $_trace['line']==$item['line'])
			) {
				$traces[$_i]['highlighted'] = true;
				if (isset($item['dom_id'])) {
					$traces[$_i]['related_dom_id'] = $item['dom_id'];
					$item['related_dom_id'] = $_trace['dom_id'];
				}
			}
		}
		return $traces;
	}

	public static function getNewDomId($slug)
	{
		$id = $slug.'_';
		while( $id==$slug.'_' && false!==array_search($_id, self::$dom_ids) ) 
		{
			$_id = $id.rand(10,100);
		}
		self::$dom_ids[$slug] = $_id;
		return self::$dom_ids[$slug];
	}

// ----------------------------------
// Utils
// ----------------------------------

	public static function stringExtract($str, $maxlength = 100)
	{
		$str = self::cleanString($str);
		if (strlen($str)>$maxlength) $str = substr($str, 0, $maxlength).' ...';
		return $str;
	}

	public static function cleanString($str)
	{
		return str_replace(array("\n", "  "), ' ', trim(strip_tags($str)));
	}

	public static function cleanCode($str)
	{
		return trim(strip_tags($str));
	}

	public static function dumpArray($array)
	{
		return trim(stripslashes(print_r($array,1)), "\"'\n ");
	}

	public static function ksort($array)
	{
		if (!is_array($array)) return array();
		ksort($array, SORT_STRING);
		return $array;
	}

	public static function natcasesort($array)
	{
	    if (!is_array($array)) return $array;
		natcasesort($array);
		return $array;
	}

	public static function checkString($str)
	{
		if (Url::isUrl($str)) {
			return sprintf(self::mask_a_link, $str);
		} elseif (Url::isEmail($str)) {
			return sprintf(self::mask_mailto_link, $str);
		} elseif (@file_exists($str)) {
			return sprintf(self::mask_abbr, realpath($str), $str);
		} elseif (false!==strpos($str, '&')) {
			return str_replace('&', '&amp;', $str);
		}
		return $str;
	}

}

// Endfile