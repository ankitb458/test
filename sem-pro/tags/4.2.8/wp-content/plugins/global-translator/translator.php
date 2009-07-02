<?php
/*
Plugin Name: Multilanguage Translator
Plugin URI: http://www.nothing2hide.net/blog/2006/08/20/wordpress-global-translator-plugin/
Description: Dynamically translates a blog by wrapping the Google Translation Engine
Version: 0.6 (fork)
Author: Davide Pozza
Author URI: http://www.nothing2hide.net/
Disclaimer: Use at your own risk. No warranty expressed or implied is provided.


Edits:

- Mike Koepke <www.mikekoepke.com> (widget)
- Denis de Bernardy <www.semiologic.com> (bug fixes)
*/

//////////////////////////////////

/*
The available languages codes are the following:
  'it': Italian
  'ko': Korean
  'zh-CN': Chinese (Simplified)
  'pt': Portuguese
  'en': English
  'de': German
  'fr': French
  'es': Spanish
*/

define("BASE_LANG", "en");
error_reporting(E_ALL ^ E_NOTICE);

//////////////////////////////////

$translator_base_url = 'http://translate.google.com/translate_c?hl=en&prev=/language_tools&ie=UTF-8&oe=UTF-8&langpair='.BASE_LANG.'|';

if(isset($_GET['l']) && isset($_GET['u'])) { // Language (l) and URL (u) must be set
  set_time_limit(180);
  $language = $_GET['l'];
  $url_to_translate = $_GET['u'];

  $resource = $translator_base_url . $language . '&u=' . rawurlencode($url_to_translate);

  $isredirect = TRUE;
  $redirect = NULL;

  while($isredirect) {
  	$isredirect = FALSE;
  	if (isset($redirect_url)) {
  		$resource = $redirect_url;
  	}

	  $url_parsed = parse_url($resource);

		$host = $url_parsed["host"];

		$port = $url_parsed["port"];
		if ($port==0)
		   $port = 80;

		$path = $url_parsed["path"];
		if (empty($path))
			$path="/";

		$query = $url_parsed["query"];

		$http_q = $path.'?'.$query;

		$req = buildRequest($host, $http_q);

		$fp = @fsockopen($host, $port, $errno, $errstr);

		if (!$fp) {
		   echo "$errstr ($errno)<br />\n";
		} else {
			fputs($fp, $req, strlen($req));  // send request
			$buf = '';
			$endHeader = false;
			while (!feof($fp)) {
			   $line = fgets($fp);
			   if (preg_match('/^\blocation\b/i', $line)) {

				   $redirect_url=preg_replace("/location:/i","",$line);
				   $redirect_url=trim($redirect_url);
				   $isredirect = TRUE;
				   $buf = '';
				   break;
			   }
	       if ($endHeader == true)
				   $buf .= $line;
	 	     if ($line == "\r\n")
	         $endHeader = true;
			}//end while

			if ($isredirect == FALSE){
	 		  echo "$buf";
			}
	    fclose($fp);
		}
	}
}

function getAvailableTranslationsMap() {
  return array(
  'it' => array('en'),
  'ko' => array('en'),
  'zh-CN' => array('en'),
  'pt' => array('en'),
  'en' => array('it','de','es','fr','pt'), //,'ja','ko','zh-CN'),
  'de' => array('en','fr'),
  'fr' => array('en','de'),
  'es' => array('en'),
  );
}

function buildRequest($host, $http_req) {
	//echo "$http_req\n";
	$res  = "GET $http_req HTTP/1.0\r\n";
	$res .= "Host: $host\r\n";
	$res .= "User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1) \r\n";
	$res .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
	return $res;
}

function build_flags_bar($enflag) {
    echo '<div class="flags_bar">';
    $availableTranslationsMap = getAvailableTranslationsMap();
    $translations = $availableTranslationsMap[BASE_LANG];
    echo "<a href='" . get_translated_url(BASE_LANG, get_self_url()) . "' title='" . BASE_LANG . "'><img src='" . get_flag_image(BASE_LANG,$enflag) . "' border='0' alt='" . BASE_LANG . "' /></a>";
    foreach($translations as $key => $value){
		echo "<a href='" . get_translated_url($value, get_self_url()) . "' title='" . $value . "'><img src='" . get_flag_image($value,$enflag) . "' border='0' alt='" . $value . "' /></a>";
    }
    echo "</div>";
}

function get_translated_url($language=BASE_LANG, $url) {
  if ($language == BASE_LANG) {
		return $url;
  }
  if(!isset($url)) $url = get_bloginfo('url');
  return trailingslashit(get_bloginfo('url')) . 'wp-content/plugins/global-translator/translator.php?l=' . $language . '&amp;u=' . rawurlencode($url);
}

function get_flag_image($language=BASE_LANG,$enflag) {
	if($language==BASE_LANG) $language=$enflag;
    return trailingslashit(get_bloginfo('url')) . 'wp-content/plugins/global-translator/flag_' . $language . '.gif';
}

function get_self_url() {
    $full_url = 'http';
    $script_name = '';
    if(isset($_SERVER['REQUEST_URI'])) {
        $script_name = $_SERVER['REQUEST_URI'];
    } else {
        $script_name = $_SERVER['PHP_SELF'];
        if($_SERVER['QUERY_STRING']>' ') {
            $script_name .=  '?'.$_SERVER['QUERY_STRING'];
        }
    }
    if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') {
        $full_url .=  's';
    }
    $full_url .=  '://';
    if($_SERVER['SERVER_PORT']!='80') {
        $full_url .=  $_SERVER['HTTP_HOST'].':'.$_SERVER['SERVER_PORT'].$script_name;
    } else {
        $full_url .=  $_SERVER['HTTP_HOST'].$script_name;
    }
    return $full_url;
}



function translator_widget_init()
{
	if ( !function_exists('register_sidebar_widget') ) return;

	// Options and default values for this widget
	function translator_widget_options() {
		return array(
			// default to US flag for en language
			'title' => '',
			'enflag' => 'us',
		);
	}

	function translator_widget($args)
	{
		extract($args);

		// Each widget can store and retrieve its own options.
		// Here we retrieve any options that may have been set by the user
		// relying on widget defaults to fill the gaps.
		$options = array_merge((array) translator_widget_options(), (array) get_option('translator_widget'));
		unset($options[0]); //returned by get_option(), but we don't need it

		echo $before_widget;
			if ($options['title']) {
				echo $before_title . $options['title'] . $after_title;
			}

			build_flags_bar($options['enflag']);

		echo $after_widget;
	}

	// This is the function that outputs the form to let the users edit
	// the widget's title. It's an optional feature that users cry for.
	function translator_widget_control() {
		// Each widget can store and retrieve its own options.
		// Here we retrieve any options that may have been set by the user
		// relying on widget defaults to fill the gaps.
		$options = array_merge((array) translator_widget_options(),(array) get_option('translator_widget'));
		unset($options[0]); //returned by get_option(), but we don't need it

		// If user is submitting custom option values for this widget
		if ( $_POST['translator-submit'] ) {
			// Remember to sanitize and format use input appropriately.
			foreach($options as $key => $value)
				if(array_key_exists('translator-'.$key, $_POST))
					$options[$key] = strip_tags(stripslashes($_POST['translator-'.$key]));

			// Save changes
			update_option('translator_widget', $options);
		}

		// Here is our little form segment. Notice that we don't need a
		// complete form. This will be embedded into the existing form.
		// Be sure you format your options to be valid HTML attributes
		// before displayihng them on the page.

		//foreach($options as $key => $value):
		?>			<p style="text-align:left"><label for="translator-title" ><?php echo __('Title') . ':'; ?><br /><input style="width: 200px;" id="translator-title" name="translator-title" type="text" value="<?php echo htmlspecialchars($options['title'], ENT_QUOTES); ?>" /></label></p>

			<p style="text-align:left"><label for="translator-enflag" ><?php echo __('English Flag') . ':'; ?></label><br /><input type="radio" id="translator-enflag" name="translator-enflag" value='us' <?php if($options['enflag']=='us') print 'checked'; ?> /> US&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" id="translator-enflag" name="translator-enflag" value='uk' <?php if($options['enflag']=='uk') print 'checked'; ?> /> UK&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" id="translator-enflag" name="translator-enflag" value='au' <?php if($options['enflag']=='au') print 'checked'; ?> /> AU</p>
		<?php //endforeach;
		echo '<input type="hidden" id="translator-submit" name="translator-submit" value="1" />';
	}

	register_sidebar_widget('Translator', 'translator_widget');
	register_widget_control('Translator', 'translator_widget_control');
}

if ( function_exists('add_action') )
{
	add_action('plugins_loaded', 'translator_widget_init');
}
?>