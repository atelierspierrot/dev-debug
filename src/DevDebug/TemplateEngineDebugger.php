<?php
/**
 * DevDebug - PHP framework package
 * Copyleft (c) 2013-2014 Pierre Cassat and contributors
 * <www.ateliers-pierrot.fr> - <contact@ateliers-pierrot.fr>
 * License GPL-3.0 <http://www.opensource.org/licenses/gpl-3.0.html>
 * Sources <http://github.com/atelierspierrot/devdebug>
 */

namespace DevDebug;

use \TemplateEngine\TemplateEngine;
use \Assets\Loader as AssetsLoader;
use \DevDebug\Debugger;

/**
 * A special debugger instance to use with TemplateEngine
 */
class TemplateEngineDebugger
    extends Debugger
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