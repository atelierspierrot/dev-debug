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

use TemplateEngine\TemplateEngine;
use Assets\Loader as AssetsLoader;
use DevDebug\Debugger;

/**
 * A special debugger instonce to use with TemplateEngine
 */
class TemplateEngineDebugger extends Debugger
{

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
                    ->guessFromAssetsLoader(AssetsLoader::getInstance(
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
			echo $str;
			exit;
		}
		return '';
	}

}

// Endfile