<?php
/*
Plugin Name: Global Translator
Plugin URI: http://www.nothing2hide.net/blog/wp-plugins/wordpress-global-translator-plugin/
Description: Dynamically translates a blog in foreign languages (English, French, Italian, German, Portuguese, Spanish, Russian, Greek, Dutch) by wrapping the Google Translation Engine. Notice: This plugin will only work on a site that use fancy urls (Options / Permalinks) and that is hosted on an Apache server.
Version: 0.9 (fork)
Author: Davide Pozza
Author URI: http://www.nothing2hide.net/
Disclaimer: Use at your own risk. No warranty expressed or implied is provided.

*/

/*  Copyright 2006  Davide Pozza  (email : davide@nothing2hide.net)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/* Credits:
	Special thanks also to Jason F. Irwin, Ibnu Asad, Ozh, ttancm, and the many others who have provided feedback, spotted bugs, and suggested improvements.

Edits:

- Mike Koepke <www.mikekoepke.com> (widget)
- Denis de Bernardy <www.semiologic.com> (bug fixes)
*/

/* *****INSTRUCTIONS*****

  Installation
  ============
  Upload the folder "global-translator" into your "wp-content/plugins"
  Log in to Wordpress Administration area, choose "Plugins" from the main menu, find "Global Translator", and click the "Activate" button
  Choose "Options->Global Translator" from the main menu and select your blog language and your flags bar layout.

  Upgrading
  =========
  Uninstall the previous version and follow the Installation instructions.


  Configuration
  =============
  Modify your theme (usually your sidebar.php or header.php) by adding the following php code:
    <?php if(function_exists("gltr_build_flags_bar")) { gltr_build_flags_bar(); } ?>

  An example from my site (file header.php):

    <div id="header">
    [...]
    <div id="globaltranslator">
      <? if(function_exists("gltr_build_flags_bar")) { gltr_build_flags_bar(); } ?>
    </div>
    [...]
  </div>

  After this simple operation, a bar containing the flags which represents all the available translations for your language will appear on your blog.
  Check my blog for an example (on top, at the right).

  Uninstallation
  ==============
  Log in to Wordpress Administration area, choose “Plugins” from the main menu, find the name of the plugin “Global Translator”, and click the “Deactivate” button


  ***********************


  Change Log

  0.6
  	- Fixed compatibility problem with Firestats
    - Added the "gltr_" prefix for all the functions names in order to reduce naming conflicts with other plugins
    - Added new configuration feature: now you can choose to enable a custom number of translations
    - Removed PHP short tags
    - Added alt attribute for flags IMG
    - Added support to BabelFish Engine: this should help to solve the “403 Error” by Google
    - Added my signature to the translation bar. It can be removed, but you should add a link to my blog on your blogroll.
    - Replaced all the flags images
    - Added help messages for cache support
    - Added automatic permalink update system: you don't need to re-save your permalinks settings
    - Fixed many link replacement issues
    - Added hreflang attribute to the flags bar links
    - Added id attribute to <A> Tag for each flag link
    - Added DIV tag for the translation bar
    - Added support for the following new languages: Russian, Greek, Dutch

  0.5
    - Added BLOG_URL variable
    - Improved url replacement
    - Added caching support (experimental): the cached object will be stored inside the following directory:
      "[...]/wp-content/plugins/global-translator/cache".
    - Fixed japanese support (just another bug)

  0.4.1
    - Better request headers
    - Bug fix: the translated page contains also the original page

  0.4
    - The plugin has been completely rewritten
    - Added permalinks support for all the supported languages
    - Added automatic blog links substitution in order to preserve the selected language.
    - Added Arabic support
    - Fixed Japanese support
    - Removed "setTimeout(180);" call: it is not supported by certain servers
    - Added new option which permits to split the flags in more than one row

  0.3/0.2
    - Bugfix version
    - Added Options Page

  0.1
    - Initial release
*/

if(!class_exists("gt_translation_engine")) {
  include(dirname(__FILE__) . '/header.php');
}

define('FLAG_BAR_BEGIN', '<!--FLAG_BAR_BEGIN-->');
define('FLAG_BAR_END', '<!--FLAG_BAR_END-->');
define('USER_AGENT', 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)');
define('LANGS_PATTERN', 'it|ko|zh-CN|pt|en|de|fr|es|ja|ar|ru|el|nl|zh|zt');
define('LANGS_PATTERN_WITH_SLASHES', '/it/|/ko/|/zh-CN/|/pt/|/en/|/de/|/fr/|/es/|/ja/|/ar/|/ru/|/el/|/nl/|/zh/|/zt/');
define('LANGS_PATTERN_WITHOUT_FINAL_SLASH', '/it|/ko|/zh-CN|/pt|/en|/de|/fr|/es|/ja|/ar|/ru|/el|/nl|/zh|/zt');

$gt_result = '';
$gt_engine = NULL;

function gltr_translator_init() {
  global $wp_rewrite;
  global $gt_available_engines;
  global $gt_engine;

  if (isset($wp_rewrite) && $wp_rewrite->using_permalinks()) {
      define('REWRITEON', '1');
      define('LINKBASE', $wp_rewrite->root);
  } else {
      define('KEYWORDS_REWRITEON', '0');
      define('LINKBASE', '');
  }

  if (REWRITEON) {
      add_filter('generate_rewrite_rules', 'gltr_translations_rewrite');

      if ( !get_option('gltr_translations_rewrite_added') )
      {
      	$wp_rewrite->set_permalink_structure(get_option('permalink_structure'));
      	update_option('gltr_translations_rewrite_added', 1);
      }
  }

  $base_lang = get_option('gltr_base_lang');
  if ( !$base_lang )
  {
  	$base_lang = 'en';
  }

  define('BASE_LANG', $base_lang);
  define('BAR_COLUMNS', get_option('gltr_col_num'));
  define('USE_CACHE', true);
  define('CACHE_TIMEOUT', 3600 * 24 * 7);
  define('HTML_BAR_TAG', 'DIV');
  define('TRANSLATION_ENGINE', 'google');

  define('BLOG_URL', trailingslashit(get_settings('siteurl')));
  define('BLOG_HOME', get_settings('home'));
  define('BLOG_HOME_ESCAPED', str_replace('/', '\\/', BLOG_HOME));

  $gt_engine = $gt_available_engines[TRANSLATION_ENGINE];
}
add_action('init','gltr_translator_init');

function gltr_build_translation_url($srcLang, $destLang, $urlToTransl){
  global $gt_engine;
  $tokens = array('${URL}', '${SRCLANG}', '${DESTLANG}');
  $values = array($urlToTransl, $srcLang, $destLang);
  return str_replace($tokens, $values, $gt_engine->get_base_url());
}


function gltr_translate($lang, $url) {
  global $gt_engine;

  // Reset WP
  $GLOBALS['wp_filter'] = array();
  while ( @ob_end_clean() );

  if (preg_match('('.LANGS_PATTERN_WITH_SLASHES.')', gltr_get_self_url()))
	  $url_to_translate = preg_replace('('.LANGS_PATTERN_WITH_SLASHES.')', '/', gltr_get_self_url());
	elseif (preg_match('('.LANGS_PATTERN_WITHOUT_FINAL_SLASH.')', gltr_get_self_url()))
	  $url_to_translate = preg_replace('('.LANGS_PATTERN_WITHOUT_FINAL_SLASH.')', '/', gltr_get_self_url());

	$resource = gltr_build_translation_url(BASE_LANG, $lang, $url_to_translate);
  $isredirect = true;
  $redirect = NULL;
  //echo '|'.$url_to_translate.'|';

  while($isredirect) {
    $isredirect = false;
    if (isset($redirect_url)) {
      $resource = $redirect_url;
    }

    $url_parsed = parse_url($resource);
    $host = $url_parsed["host"];
    $port = $url_parsed["port"];
    if ($port == 0) $port = 80;
    $path = $url_parsed["path"];
    if (empty($path)) $path = "/";
    $query = $url_parsed["query"];
    $http_q = $path . '?' . $query;

    $req = gltr_build_request($host, $http_q);

    $fp = @fsockopen($host, $port, $errno, $errstr);

    if (!$fp) {
      return "$errstr ($errno)<br />\n";
    } else {
      fputs($fp, $req, strlen($req));  // send request
      $buf = '';
      $isFlagBar = false;
      $flagBarWritten = false;
      $beginFound = false;
      $endFound = false;
      while (!feof($fp)) {
        $line = fgets($fp);
        if (preg_match('/^\blocation\b/i', $line)) {
          $redirect_url = preg_replace("/location:/i", "", $line);
          $redirect_url = trim($redirect_url);
          $isredirect = true;
          $buf = '';
          break;
        }

        if (!(strpos($line, FLAG_BAR_BEGIN)===false)) $beginFound = true;

        if ($beginFound && !$endFound) {
          $line = gltr_get_flags_bar();
          $buf .= $line;
        }

        if ($beginFound && !(strpos($line, FLAG_BAR_END)===false)) {
          $endFound = true;
          $line = fgets($fp);
        }

        if(!$beginFound || $endFound){
          //Clean the links modified by the translation engine
          $line = preg_replace($gt_engine->get_links_pattern(), $gt_engine->get_links_replacement(), urldecode ($line));

          $pattern = "/<a href=\"" . BLOG_HOME_ESCAPED . "([^\"]*)\"([\s|>]{1})/i";
          $repl = "<a href=\"" . BLOG_HOME . '/' . $lang . "\\1\" \\2";
          $line = preg_replace($pattern, $repl, $line);
          $buf .= $line;
        }
      }//end while
    }
    fclose($fp);
  }//while($isredirect)

  $buf=split("\r\n\r\n", $buf, 2);
  return $buf[1];

}

function gltr_build_request($host, $http_req) {
  $res  = "GET $http_req HTTP/1.0\r\n";
  $res .= "Host: $host\r\n";
  $res .= "User-Agent: " . USER_AGENT ." \r\n";
  $res .= "Content-Type: application/x-www-form-urlencoded\r\n";
  $res .= "Content-Length: 0\r\n";
  $res .= "Connection: close\r\n\r\n";
  return $res;
}

function gltr_get_flags_bar() {
  global $gt_engine;

  $use_table = false;
  if (HTML_BAR_TAG == 'TABLE') $use_table = true;
  $num_cols = BAR_COLUMNS;

  $buf = '';
  if ($num_cols < 0) $num_cols = 0;

  $transl_map = $gt_engine->get_languages_matrix();

  $translations = $transl_map[BASE_LANG];

  $transl_count = count($translations);

  $buf .= "\n" . FLAG_BAR_BEGIN;//initial marker
  if ($use_table) $buf .= "<table border='0'><tr>"; else $buf .= "<div id=\"translation_bar\">";

  $curr_col = 0;

  //filter preferred
  $preferred_transl = array();
  $preferred_languages = array_keys($translations);
  foreach($translations as $key => $value){
    if ($key == BASE_LANG || in_array($key, $preferred_languages))
    	$preferred_transl[$key] = $value;
  }

  foreach($preferred_transl as $key => $value){
    if ($curr_col >= $num_cols && $num_cols > 0){
      if ($use_table) $buf .= "</tr><tr>";
      $curr_col = 0;
    }
    $flg_url = gltr_get_translated_url($key, gltr_get_self_url());
    $flg_image_url = gltr_get_flag_image($key);
    if ($use_table) $buf .= "<td>";
    $buf .= "<a id='flag_".$key."' href='" . $flg_url . "' hreflang='".$key."'><img id='flag_img_".$key."' src='" . $flg_image_url . "' alt='" . $value . " flag' title='" . $value . "'  border='0' /></a>";
    if ($use_table) $buf .= "</td>";
    if ($num_cols > 0) $curr_col += 1;
  }

  while ($curr_col < $num_cols && $num_cols > 0) {
    if ($use_table) $buf .= "<td>&nbsp;</td>";
    $curr_col += 1;
  }

  if ($num_cols == 0)$num_cols = count($translations);
  $buf .= FLAG_BAR_END ."\n";//final marker
  return $buf;
}

function gltr_build_flags_bar() {
  echo(gltr_get_flags_bar());
}

//ONLY for backward compatibility!
function build_flags_bar() {
  echo(gltr_get_flags_bar());
}

function gltr_get_translated_url($language, $url) {
  $pattern = '/' . BLOG_HOME_ESCAPED . '\\/(' . LANGS_PATTERN . ')*[\\/]*(.*)/';

  if (preg_match($pattern, $url)){
    $uri = preg_replace($pattern, '\\2', $url);
  } else {
    $uri = '';
  }
  if ($language == BASE_LANG)
    return BLOG_HOME . '/' . $uri;
  else
    return BLOG_HOME . '/' . $language . '/' . $uri;
}

function gltr_get_flag_image($language) {
	return BLOG_URL . 'wp-content/plugins/global-translator/flag_' . $language . '.png';
}

function gltr_get_self_url() {
  $full_url = 'http';
  $script_name = '';
  if(isset($_SERVER['REQUEST_URI'])) {
    $script_name = $_SERVER['REQUEST_URI'];
  } else {
    $script_name = $_SERVER['PHP_SELF'];
    if($_SERVER['QUERY_STRING']>' ') {
      $script_name .=  '?' . $_SERVER['QUERY_STRING'];
    }
  }
  if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
    $full_url .=  's';
  }
  $full_url .=  '://';
  if($_SERVER['SERVER_PORT'] != '80') {
    $full_url .=  $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . $script_name;
  } else {
    $full_url .=  $_SERVER['HTTP_HOST'] . $script_name;
  }
  return $full_url;
}

//rewrite rules definitions
function gltr_translations_rewrite($wp_rewrite) {
  $translations_rules = array(
    '^(' . LANGS_PATTERN . ')$'  => 'index.php?lang=$matches[1]',
    '^(' . LANGS_PATTERN . ')/(.+?)$' => 'index.php?lang=$matches[1]&url=$matches[2]'
  );
  $wp_rewrite->rules = $translations_rules + $wp_rewrite->rules;
}

function gltr_get_page_content($lang, $url) {
  $page = '';
  if (USE_CACHE) {
    $refresh = CACHE_TIMEOUT;
    $unique_url_string = substr(gltr_get_self_url(), 7) . '|G|' . serialize($_GET) . '|P|' . serialize($_POST);
    $hash = sha1($unique_url_string);
    $cachedir = ABSPATH . 'wp-content/translations';
    $filename = $cachedir . '/' . $hash;

    if (!file_exists($cachedir)) {
      @mkdir($cachedir, 0777);
      //if (!mkdir($cachedir)) die "Unable to create the \"cache\" directory (" . dirname(__FILE__) . '/cache' ."). Try manually.";
    }

    if (file_exists($filename) &&
        ((time() - @filemtime($filename)) < $refresh) &&
        filesize($filename) > 0) {
      // We are done, just return the file and exit
      $handle = fopen($filename, "rb");
      $page = fread($handle, filesize($filename));
      $page .= "<!--CACHED VERSION (timeout: ".CACHE_TIMEOUT."): $unique_url_string ($hash)-->";
      fclose($handle);
    } else {
      if (file_exists($filename) && ((time() - @filemtime($filename)) > $refresh)) {
        //old cached file
        unlink($filename);
      }
      $page = gltr_translate($lang, $url);
      $handle = fopen($filename, "wb");
      if (flock($handle, LOCK_EX)) {// do an exclusive lock
        fwrite($handle, $page); //write
        flock($handle, LOCK_UN); // release the lock
      }else {
        fwrite($handle, $page); //Couldn't lock the file ! Try anyway to write but it is not a good thing
      }
      fclose($handle);
      $page .= "<!--NOT CACHED VERSION: $unique_url_string ($hash)-->";
    }
  } else {
    //Caching support disabled
    $page = gltr_translate($lang, $url);
  }

  return $page;
}

function gltr_filter_content($content) {
  global $gt_result;
  return $gt_result;
}

function gltr_insert_my_rewrite_query_vars($vars){
  array_push($vars, 'lang', 'url');
  return $vars;
}

function gltr_insert_my_rewrite_parse_query($query){
  global $gt_result;
  if( isset($query->query_vars['lang']) ){
    $lang = $query->query_vars['lang'];
    $url = $query->query_vars['url'];

    if (empty($url)){
      $url = '';
    }
    $gt_result = gltr_get_page_content($lang, $url);
    ob_start('gltr_filter_content');
  }
}

/** filters**/

add_filter('query_vars',	'gltr_insert_my_rewrite_query_vars');
add_action('parse_query',	'gltr_insert_my_rewrite_parse_query');
add_action('admin_menu', 	'gltr_add_options_page');

function gltr_add_options_page() {
	$path = dirname(__FILE__);
	$pos = strrpos($path, '/') + 1;
	$option_file = substr($path, $pos) . '/options-translator.php';
  add_options_page('Translator',
    'Translator',
    'manage_options',
    $option_file);
}



function translator_widget_init()
{
	if ( !function_exists('register_sidebar_widget') ) return;

	global $is_apache;

	if ( !$is_apache )
		return;

	if ( function_exists( 'apache_get_modules' ) ) {
		if ( !in_array( 'mod_rewrite', apache_get_modules() ) )
			return;
	}

	if ( !get_option('permalink_structure') ) return;

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

			echo gltr_get_flags_bar();

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

<?php if ( false ) : ?>

			<p style="text-align:left"><label for="translator-enflag" ><?php echo __('English Flag') . ':'; ?></label><br /><input type="radio" id="translator-enflag" name="translator-enflag" value='us' <?php if($options['enflag']=='us') print 'checked'; ?> /> US&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" id="translator-enflag" name="translator-enflag" value='uk' <?php if($options['enflag']=='uk') print 'checked'; ?> /> UK&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" id="translator-enflag" name="translator-enflag" value='au' <?php if($options['enflag']=='au') print 'checked'; ?> /> AU</p>

<?php endif; ?>
		<?php //endforeach;
		echo '<input type="hidden" id="translator-submit" name="translator-submit" value="1" />';
	}

	register_sidebar_widget('Translator', 'translator_widget');
	register_widget_control('Translator', 'translator_widget_control');
}

if ( function_exists('add_action') )
{
	add_action('widgets_init', 'translator_widget_init');
}
?>