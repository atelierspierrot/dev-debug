<?php
/**
 * DevDebug - PHP framework package
 * Copyleft (c) 2013 Pierre Cassat and contributors
 * <www.ateliers-pierrot.fr> - <contact@ateliers-pierrot.fr>
 * License GPL-3.0 <http://www.opensource.org/licenses/gpl-3.0.html>
 * Sources <https://github.com/atelierspierrot/devdebug>
 */

namespace DevDebug;

use TemplateEngine\TemplateEngine;
use Assets\Loader as AssetsLoader;

/**
 * Debug functions library
 *
 * Creates each stack of debugging info
 *
 * @author 		Pierre Cassat & contributors <piero.wbmstr@gmail.com>
 * @see \DevDebug\Debugger
 */
class DebuggerItem
{

	protected $type;
	protected $entity;
	protected $title;

	public function __construct($type = null, $entity = null, $title = null)
	{
		$debugger =& self::getDebugger();
		self::setTitle( $title );
		self::setType( $type );
		self::setEntity( $entity );
	}

	public function __toString()
	{
		$debugger =& self::getDebugger();
		return $debugger->renderStack( $this );
	}	

	protected function getDebugger()
	{
		return Debugger::getInstance();
	}	

	public function setTitle($title)
	{
		$this->title = $title;
	}	

	public function getTitle()
	{
		return $this->title;
	}	

	public function setType($type)
	{
		$this->type = $type;
	}	

	public function getType()
	{
		return $this->type;
	}	

	public function getEntity()
	{
		return $this->entity;
	}	

	public function setEntity($entity)
	{
		$this->entity = $entity;
	}	

}

/**
 * The global application debugger singleton instance
 */
class Debugger
{

	public static $instance;
	public $profiler;
	public $format;
	public $mailto;

  	static $default_stacks = array( 
	  	array( 'title'=>'PHP Stack traces', 'type'=>'traces' ), 
	  	array( 'title'=>'PHP Environment', 'type'=>'environment' ), 
	  	array( 'title'=>'Server Parameters', 'type'=>'serverParams' ), 
	  	array( 'title'=>'Session Attributes', 'type'=>'session' ), 
	  	array( 'title'=>'Defined Constants', 'type'=>'constants' ), 
	  	array( 'title'=>'Request', 'type'=>'headers' ), 
	  	array( 'title'=>'System Environment', 'type'=>'system' ), 
 	);
	static $debugger_arguments = array(
		'format'=>'dbg_format',
		'mailto'=>'dbg_mailto',
	);

	protected $stacks;
	protected $traces;
	protected $messages;
	protected $type;
	protected $entity;
	protected $title;

	private function __construct()
	{
		$this->stacks = array();
		$this->messages = array();
	}

	private function init()
	{
		$this->profiler = new Profiler( $this );
		self::setStacks( self::$default_stacks );
	}
	
	public static function getInstance()
	{
        if (!isset(self::$instance) || !is_object(self::$instance)) {
            self::$instance = new Debugger;
            self::$instance->init();
        }
        return self::$instance;
	}

	public function checkUri()
	{
		foreach(self::$debugger_arguments as $fct=>$arg) {
			if (isset($_GET[$arg])) {
				$_meth = 'set'.ucfirst($fct);
				if (method_exists($this, $_meth))
					$this->$_meth( $_GET[$arg] );
			}
		}
	}

	private static $shutdown=false;

	public static function shutdown($exit = false, $callback = null)
	{
    	$_this =& self::getInstance();
		if (Debugger::$shutdown===true) return true;
		$_this->checkUri();

    	if (!empty($_this->messages)) {
			Debugger::$shutdown=true;

			if (!empty($callback) && is_callable($callback) &&
				(empty($_this->format) && empty($_this->mailto))
			) {
				$params = func_get_args();
				array_shift($params);
				array_shift($params);
				array_push($params, $_this);
				call_user_func_array($callback, $params);
			} else {
				$dbg_str = '<h1>'.$_this->profiler->renderProfilingTitle().'</h1>'.'<p>'.$_this->profiler->renderProfilingInfo().'</p>';
				if (!empty($_this->format)) {
					$dbg_str .= $_this->render($_this->format);
				} else {
					$dbg_str .= $_this->render();
				}
				if (!empty($_this->format)) {
					$_this->renderFormatedString( $dbg_str, $_this->format );
				} elseif (!empty($_this->mailto)) {
					$_this->sendByEmail( $dbg_str, $_this->mailto );
					$_this->setMailto(null);
					return self::shutdown( $exit, $callback );
				} else {
					echo $dbg_str;
				}
			}
	
			if ($exit===true) exit;
		}
		return true;
	}

	public function __toString()
	{
		return $this->render();
	}	

	public function render($format = 'html')
	{
		if (!empty($this->type)) {
			$_meth = 'render'.ucfirst($this->type);
			if (method_exists($this->profiler, $_meth)) {
				$this->profiler->setCurrentEntity( $this->entity );
				return $this->profiler->$_meth( !empty($this->format) ? $this->format : $format );
			}
			return var_export($this->entity,1);
		} else {

            $template_engine = TemplateEngine::getInstance();
            try {
                $template_engine
                    ->setToView('setIncludePath', __DIR__.'/views' )
                    ->guessFromAssetsLoader(new AssetsLoader(
                        __DIR__.'/../../',
                        'www',
                        defined('_DEVDEBUG_DOCUMENT_ROOT') ? _DEVDEBUG_DOCUMENT_ROOT : __DIR__.'/../../www'
                    ));
	    	} catch(\Exception $e) {
	    	    return $e->getMessage();
	    	}
            $template_engine->getTemplateObject('MetaTag')
                ->add('robots', 'none');

            $params = array(
                'debug' => $this,
                'reporter' => $template_engine,
                'title' =>'The system encountered an error!',
                'subheader' =>$this->profiler->renderProfilingTitle(),
                'slogan' =>$this->profiler->renderProfilingInfo(),
                'profiler_content' => 'profiler_content.html',
                'profiler_footer' => 'profiler_footer.html',
                'show_menu' => false,
                'show_backtotop_handlers' => false
            );
			// messages ?
			if (!empty($this->messages)) {
				$params['messages'] = self::renderMessages( $this->messages, !empty($this->format) ? $this->format : $format );
			}
			// others
			$params['stacks'] = array();
			$params['menu'] = array();
			foreach(self::getStacks() as $_i=>$_stack) {
    			$params['menu'][$_stack->getTitle()] = '#'.$_i;
				$params['stacks'][] = self::renderStack( $_stack, !empty($this->format) ? $this->format : $format );
			}

            // this will display the layout on screen and exit
            try {
	    	    $params['content'] = $template_engine->render('partial_html.html', $params);
	    	    $str = $template_engine->renderLayout(null, $params);
	    	} catch(\Exception $e) {
	    	    return $e->getMessage();
	    	}
/*
			$str='';
			// messages ?
			if (!empty($this->messages)) {
				$str .= self::renderMessages( $this->messages, !empty($this->format) ? $this->format : $format );
			}
			// others
			foreach(self::getStacks() as $_stack) {
				$str .= self::renderStack( $_stack, !empty($this->format) ? $this->format : $format );
			}
*/
			return $str;
		}
		return '';
	}

	public function renderStack(DebuggerItem $item, $format = 'html')
	{
		$this->type = $item->getType();
		$this->entity = $item->getEntity();
		$this->title = $item->getTitle();
		return self::render($format);
	}

	public function renderMessages($messages = null, $format = 'html')
	{
		if (is_null($messages)) $messages = $this->messages;
		return $this->profiler->renderMessages( $messages, $format );
	}
	
// ----------------------------------
// Getters / Setters
// ----------------------------------

	public function addStack($type = null, $entity = null, $title = null)
	{
		$item = new DebuggerItem( $type, $entity, $title );
		$this->stacks[] = $item;
		switch($type) {
			case 'message':
				$this->addMessage($item);
				if (isset($entity['traces']) && !empty($entity['traces']))
					$this->profiler->setTraces($entity['traces']);
				break;
			case 'traces':
				$this->profiler->setTraces($entity);
				break;
			default:break;
		}
	}
	
	public function setStacks($stacks)
	{
		foreach($stacks as $_stack) {
			if (isset($_stack['type']))
				self::addStack( 
					$_stack['type'],
					isset($_stack['entity']) ? $_stack['entity'] : null,
					isset($_stack['title']) ? $_stack['title'] : null
				);
		}
	}
	
	public function getStacks($with_messages = false)
	{
		if ($with_messages===false) {
			$stacks = array();
			foreach($this->stacks as $_stack) {
				if ($_stack->getType()!='message') $stacks[] = $_stack;
			}
		} else {
			$stacks = $this->stacks;
		}
		return $stacks;
	}
	
	public function setDebuggerTitle($str, $url = null)
	{
		$this->profiler->setTitle( $str );
		if (!empty($url))
			self::setDebuggerUrl( $url );
	}
	
	public function setDebuggerUrl($url)
	{
		$this->profiler->setUrl( $url );
	}
	
	public function getDebuggerTitle()
	{
		return $this->profiler->renderProfilingTitle();
	}
	
	public function addMessage($message)
	{
		$entity = $message->getEntity();
		$type = $entity['type'];
		if (!isset($this->messages[$type])) $this->messages[$type] = array();
		$this->messages[$type][] = $message;
	}

	public function setFormat($format)
	{
		$this->format = $format;
	}

	public function setMailto($email)
	{
		$this->mailto = $email;
	}

	public function renderFormatedString($str, $format, $return = false)
	{
		if ($format=='plain_text') {
			if (class_exists('html2text'))
				$str = html2text::convert( $str );
			else
				$str = strip_tags( $str );
			if ($return===false) {
				if (!headers_sent()) header('Content-Type: text/plain');		
				echo $str;
			} else {
				return $str;
			}
		}
	}

	public function sendByEmail($str, $email)
	{
		$str = self::renderFormatedString($str, 'plain_text', true);
		try {
			mail($email, 'Debugger from '.$_SERVER['HTTP_HOST'], $str);
		} catch(Exception $e) {
			echo $e;
		}
	}

}

// Endfile