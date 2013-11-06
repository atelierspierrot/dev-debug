<?php

/**
 * Show errors at least initially
 *
 * `E_ALL` => for hard dev
 * `E_ALL & ~E_STRICT` => for hard dev in PHP5.4 avoiding strict warnings
 * `E_ALL & ~E_NOTICE & ~E_STRICT` => classic setting
 */
//@ini_set('display_errors','1'); @error_reporting(E_ALL);
//@ini_set('display_errors','1'); @error_reporting(E_ALL & ~E_STRICT);
@ini_set('display_errors','1'); @error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

/**
 * Set a default timezone to avoid PHP5 warnings
 */
$dtmz = @date_default_timezone_get();
date_default_timezone_set($dtmz?:'Europe/Paris');

// for security
function _getSecuredRealPath($str, $skip_turns =2)
{
    $parts = explode('/', realpath('.'));
    for ($i=0; $i<$skip_turns; $i++) {
        array_pop($parts);
    }
    return str_replace(join('/', $parts), '/[***]', $str);
}

function getPhpClassManualLink( $class_name, $ln='en' )
{
    return sprintf('http://php.net/manual/%s/class.%s.php', $ln, strtolower($class_name));
}

function myShutdownCallback() {
    echo 'ENTERING IN CUSTOM FUNCTION '.__FUNCTION__.' WITH ARGS '.var_export(func_get_args(),1);
}

require_once __DIR__."/../vendor/autoload.php";
ini_set('display_errors','1');
error_reporting(E_ALL);
define('_DEVDEBUG_ERROR_HANDLER', true); // false by default
define('_DEVDEBUG_EXCEPTION_HANDLER', true); // false by default
define('_DEVDEBUG_SHUTDOWN_HANDLER', true); // false by default
define('_DEVDEBUG_SHUTDOWN_CALLBACK', "myShutdownCallback"); // empty by default
define('_DEVDEBUG_DOCUMENT_ROOT', __DIR__);

$arg_ln = isset($_GET['ln']) ? $_GET['ln'] : 'en';

if (!empty($_GET['test'])) {
    switch ($_GET['test']) {
        case 'php_exception':
            require_once __DIR__."/../src/aliases.php";
            throw new \Exception("Catching default PHP exception with default DevDebug\Debugger ...");
            break;
        case 'exception':
            require_once __DIR__."/../src/aliases.php";
            try{
                if (2 != 4) // false
                    throw new DevDebug\Exception("Catching DevDebug\Exception with default DevDebug\Debugger...", 12);
            } catch(DevDebug\Exception $e) {
                echo $e;
            }
            break;
        case 'error':
            require_once __DIR__."/../src/aliases.php";
            trigger_error('A test notice sent with "trigger_error" with default DevDebug\Debugger', E_USER_NOTICE);
            trigger_error('A test warning sent with "trigger_error" with default DevDebug\Debugger', E_USER_WARNING);
            @fopen(); // error not written
            new AZERT; // error
            break;
        case 'te_exception':
//            $dbg = DevDebug\Debugger::initInstance('DevDebug\TemplateEngineDebugger');
            define('_DEVDEBUG_DEBUGGER_CLASS', 'DevDebug\TemplateEngineDebugger'); // empty by default
            require_once __DIR__."/../src/aliases.php";
            try{
                if (2 != 4) // false
                    throw new DevDebug\Exception("Catching DevDebug\Exception with DevDebug\TemplateEngineDebugger ...", 12);
            } catch(DevDebug\Exception $e) {
                echo $e;
            }
            break;
        case 'te_error':
            define('_DEVDEBUG_DEBUGGER_CLASS', 'DevDebug\TemplateEngineDebugger'); // empty by default
            require_once __DIR__."/../src/aliases.php";
            trigger_error('A test warning sent with "trigger_error" with DevDebug\TemplateEngineDebugger', E_USER_WARNING);
            @fopen(); // error not written
            new AZERT; // error
            break;
        default:break;
    }
}
DevDebug\Debugger::shutdown(true);

?><!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <title>Test & documentation of PHP "DevDebug" package</title>
    <meta name="description" content="" />
    <meta name="viewport" content="width=device-width" />
    <link rel="stylesheet" href="assets/html5boilerplate/css/normalize.css" />
    <link rel="stylesheet" href="assets/html5boilerplate/css/main.css" />
    <script src="assets/html5boilerplate/js/vendor/modernizr-2.6.2.min.js"></script>
	<link rel="stylesheet" href="assets/styles.css" />
</head>
<body>
    <!--[if lt IE 7]>
        <p class="chromeframe">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">activate Google Chrome Frame</a> to improve your experience.</p>
    <![endif]-->

    <header id="top" role="banner">
        <hgroup>
            <h1>Tests of PHP <em>DevDebug</em> package</h1>
            <h2 class="slogan">A PHP Package to help development and debugging.</h2>
        </hgroup>
        <div class="hat">
            <p>These pages show and demonstrate the use and functionality of the <a href="https://github.com/atelierspierrot/devdebug">atelierspierrot/devdebug</a> PHP package you just downloaded.</p>
        </div>
    </header>

	<nav>
		<h2>Map of the package</h2>
        <ul id="navigation_menu" class="menu" role="navigation">
            <li><a href="index.php">Homepage</a></li>
            <li><a href="index.php?test=php_exception">PHP Exception</a></li>
            <li><a href="index.php?test=exception">DevDebug\Exception</a></li>
            <li><a href="index.php?test=error">Trigger Error</a></li>
            <li><a href="index.php?test=te_exception">TemplateEngineException</a></li>
            <li><a href="index.php?test=te_error">TemplateEngineError</a></li>
        </ul>

        <div class="info">
            <p><a href="https://github.com/atelierspierrot/devdebug">See online on GitHub</a></p>
            <p class="comment">The sources of this plugin are hosted on <a href="http://github.com">GitHub</a>. To follow sources updates, report a bug or read opened bug tickets and any other information, please see the GitHub website above.</p>
        </div>

    	<p class="credits" id="user_agent"></p>
	</nav>

    <div id="content" role="main">

        <article>

    <h2 id="notes">First notes</h2>
    <p>All these classes works in a PHP version 5.3 minus environment. They are included in the <em>Namespace</em> <strong>DevDebug</strong>.</p>
    <p>For clarity, the examples below are NOT written as a working PHP code when it seems not necessary. For example, rather than write <var>echo "my_string";</var> we would write <var>echo my_string</var> or rather than <var>var_export($data);</var> we would write <var>echo $data</var>. The main code for these classes'usage is written strictly.</p>
    <p>As a reminder, and because it's always useful, have a look at the <a href="http://pear.php.net/manual/<?php echo $arg_ln; ?>/standards.php">PHP common coding standards</a>.</p>

	<h2 id="tests">How-to</h2>
    
<h3>Include the <var>DevDebug</var> namespace</h3>

    <p>As the package classes names are built following the <a href="https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md">PHP Framework Interoperability Group recommandations</a>, we use the <a href="https://gist.github.com/jwage/221634">SplClassLoader</a> to load package classes. The loader is included in the package but you can use your own.</p>

    <pre class="code" data-language="php">
<?php
echo 'require_once ".../src/SplClassLoader.php"; // if required, a copy is proposed in the package'."\n";
echo '$classLoader = new SplClassLoader("DevDebug", "/path/to/package/src");'."\n";
echo '$classLoader->register();';
?>
    </pre>

<h3>Add the <var>DevDebug aliases</var> to your project</h3>

    <p>To use the library, you first need to define some required constants setting up how the library must handle errors or exceptions and if it must write them
    on shutdown or at runtime. Then, you just have to include the <var>DevDebug/src/aliases.php</var> file ... that's it!</p>

    <pre class="code" data-language="php">
<?php
echo "// first set up PHP to handle errors:\n";
echo 'ini_set("display_errors","1");'."\n";
echo 'error_reporting(E_ALL);'."\n";
echo "\n// then define requires constants:\n";
echo 'define("_DEVDEBUG_ERROR_HANDLER", true); // false by default'."\n";
echo 'define("_DEVDEBUG_EXCEPTION_HANDLER", true); // false by default'."\n";
echo 'define("_DEVDEBUG_SHUTDOWN_HANDLER", true); // false by default'."\n";
echo 'define("_DEVDEBUG_SHUTDOWN_CALLBACK", "your callback"); // empty by default'."\n";
echo "\n// this allows you to use a custom `Debugger` class, which must extends the default `DevDebug\Debugger`:\n";
echo 'define("_DEVDEBUG_DEBUGGER_CLASS", "DevDebug\TemplateEngineDebugger"); // empty by default'."\n";
echo "\n// this is not required but can be useful for views assets:\n";
echo 'define("_DEVDEBUG_DOCUMENT_ROOT", __DIR__);'."\n";
echo "\n// then include the aliases:\n";
echo 'require_once __DIR__."/../src/aliases.php";'."\n";
?>
    </pre>

        </article>
    </div>

    <footer id="footer">
		<div class="credits float-left">
		    This page is <a href="" title="Check now online" id="html_validation">HTML5</a> & <a href="" title="Check now online" id="css_validation">CSS3</a> valid.
		</div>
		<div class="credits float-right">
		    <a href="https://github.com/atelierspierrot/devdebug">atelierspierrot/devdebug</a> package by <a href="https://github.com/pierowbmstr">Piero Wbmstr</a> under <a href="http://opensource.org/licenses/GPL-3.0">GNU GPL v.3</a> license.
		</div>
    </footer>

    <div class="back_menu" id="short_navigation">
        <a href="#" title="See navigation menu" id="short_menu_handler"><span class="text">Navigation Menu</span></a>
        &nbsp;|&nbsp;
        <a href="#top" title="Back to the top of the page"><span class="text">Back to top&nbsp;</span>&uarr;</a>
        <ul id="short_menu" class="menu" role="navigation"></ul>
    </div>

    <div id="message_box" class="msg_box"></div>

<!-- jQuery lib -->
<script src="assets/js/jquery-1.9.1.min.js"></script>

<!-- HTML5 boilerplate -->
<script src="assets/html5boilerplate/js/plugins.js"></script>

<!-- jQuery.highlight plugin -->
<script src="assets/js/highlight.js"></script>

<!-- scripts for demo -->
<script src="assets/scripts.js"></script>

<script>
$(function() {
    initBacklinks();
    activateMenuItem();
    getToHash();
    buildFootNotes();
    addCSSValidatorLink('assets/styles.css');
    addHTMLValidatorLink();
    $("#user_agent").html( navigator.userAgent );
    $('pre.code').highlight({source:0, indent:'tabs', code_lang: 'data-language'});
});
</script>
</body>
</html>
