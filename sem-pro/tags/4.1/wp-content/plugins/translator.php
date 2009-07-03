<?php
/*
Plugin Name: Translator
Plugin URI: http://blog.taragana.com/index.php/archive/wordpress-plugin-automatic-machine-translation-for-your-blog-in-eight-languages-spanish-french-german-portuguese-italian-japanese-korean-and-chinese/
Description: Translates blog dynamically (from English) to eight languages - German <a href="http://blog.taragana.com/wp-content/plugins/translator.php?l=de&u=http://blog.taragana.com/">sample</a>, Spanish <a href="">sample</a>, French <a href="">sample</a>, Italian <a href="">sample</a>, Portuguese <a href="">sample</a>, Japanese <a href="">sample</a>, Korean <a href="">sample</a>, Chinese (simplified) <a href="">sample</a>. The translated pages can be bookmarked for future use. <br/>Note: It assumes the base language of the blog as English. It requires <a href="http://curl.haxx.se/">curl libraries</a> on your system and available from PHP. Internally Google Translator services are used.
Version: 1.1 (fork)
Author: Angsuman Chakraborty
Author URI: http://blog.taragana.com/
Disclaimer: Use at your own risk. No warranty expressed or implied is provided.
*/

$translator_base_url = 'http://translate.google.com/translate_c?hl=en&prev=/language_tools&ie=UTF-8&oe=UTF-8&langpair=en%7C';

// DO NOT MODIFY BELOW THIS LINE
/* Fetch the data from Google using CURL and display it. */
if(isset($_GET['l']) && isset($_GET['u'])) { // Language (l) and URL (u) must be set
  @set_time_limit(180);
  $language = $_GET['l'];
  $url = $_GET['u'];
  if(function_exists('curl_init')) {
  	header("Content-type: text/html; charset=UTF-8");
    $ch = curl_init();
    $resource = $translator_base_url . $language . '&u=' . $url;
    curl_setopt($ch, CURLOPT_URL, $resource);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)"); // Increase IE Stats! Google dislikes non-browser user agents :(
    curl_exec($ch);
    curl_close($ch);
  } else {
    echo "Temporary failure. Please try again later. Sorry for the inconvenience.";
  }
}

/* Creates a translator bar using a table tag for placing the flag icons.
   Use create_translator_bar(true) to create a icons in two rows; default false
   For customized placement please use get_translated_url(language='de', url)
   and get_flag_image(language='de') instead
*/
function create_translator_bar($short = false) {
    if($short) {
        echo "<table border='0'>
                <tr>
                  <td><a href='" . get_translated_url('de', get_self_url()) . "' title='Deutsch'><img src='" . get_flag_image('de') . "' alt='German Flag' border='0' width='18' height='12' /></a></td>
                  <td><a href='" . get_translated_url('es', get_self_url()) . "' title='Spanish'><img src='" . get_flag_image('es') . "' alt='Spanish Flag' border='0' width='18' height='12' /></a></td>
                  <td><a href='" . get_translated_url('fr', get_self_url()) . "' title='French'><img src='" . get_flag_image('fr') . "' alt='French Flag' border='0' width='18' height='12' /></a></td>
                  <td><a href='" . get_translated_url('it', get_self_url()) . "' title='Italian'><img src='" . get_flag_image('it') . "' alt='Italian Flag' border='0' width='18' height='12' /></a></td>
                  <td><a href='" . get_translated_url('pt', get_self_url()) . "' title='Portuguese'><img src='" . get_flag_image('pt') . "' alt='Portuguese Flag' border='0' width='18' height='12' /></a></td>
                </tr>
                <tr>
                  <td><a href='" . get_translated_url('ja', get_self_url()) . "' title='Japanese'><img src='" . get_flag_image('ja') . "' alt='Japanese Flag' border='0' width='18' height='12' /></a></td>
                  <td><a href='" . get_translated_url('ko', get_self_url()) . "' title='Korean'><img src='" . get_flag_image('ko') . "' alt='Korean Flag' border='0' width='18' height='12' /></a></td>
                  <td><a href='" . get_translated_url('zh-CN', get_self_url()) . "' title='Chinese (Simplified)'><img src='" . get_flag_image('zh-CN') . "' alt='Chinese Flag' border='0' width='18' height='12' /></a></td>
                  <td colspan='2'><a href='" . get_self_url() . "' title='English'><img src='" . get_flag_image('en') . "' alt='British Flag' border='0' width='18' height='12' /></a></td>
                </tr>
                <tr>
                  <td colspan='5' align='right' style='font: Verdana, sans-serif 4px;'>by <a href='http://blog.taragana.com/'>Simple Thoughts</a></td>
                </tr>
              </table>";
    } else {
        echo "<table border='0'>
                <tr>
                  <td><a href='" . get_translated_url('de', get_self_url()) . "' title='Deutsch'><img src='" . get_flag_image('de') . "' alt='German Flag' border='0' width='18' height='12' /></a></td>
                  <td><a href='" . get_translated_url('es', get_self_url()) . "' title='Spanish'><img src='" . get_flag_image('es') . "' alt='Spanish Flag' border='0' width='18' height='12' /></a></td>
                  <td><a href='" . get_translated_url('fr', get_self_url()) . "' title='French'><img src='" . get_flag_image('fr') . "' alt='French Flag' border='0' width='18' height='12' /></a></td>
                  <td><a href='" . get_translated_url('it', get_self_url()) . "' title='Italian'><img src='" . get_flag_image('it') . "' alt='Italian Flag' border='0' width='18' height='12' /></a></td>
                  <td><a href='" . get_translated_url('pt', get_self_url()) . "' title='Portuguese'><img src='" . get_flag_image('pt') . "' alt='Portuguese Flag' border='0' width='18' height='12' /></a></td>
                  <td><a href='" . get_translated_url('ja', get_self_url()) . "' title='Japanese'><img src='" . get_flag_image('ja') . "' alt='Japanese Flag' border='0' width='18' height='12' /></a></td>
                  <td><a href='" . get_translated_url('ko', get_self_url()) . "' title='Korean'><img src='" . get_flag_image('ko') . "' alt='Korean Flag' border='0' width='18' height='12' /></a></td>
                  <td><a href='" . get_translated_url('zh-CN', get_self_url()) . "' title='Chinese (Simplified)'><img src='" . get_flag_image('zh-CN') . "' alt='Chinese Flag' border='0' width='18' height='12' /></a></td>
                  <td><a href='" . get_self_url() . "' title='English'><img src='" . get_flag_image('en') . "' alt='British Flag' border='0' /></a></td>
                </tr>
              </table>";
    }
}

/* Get the url of the page */
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
/* Provides the translated URL for a given (if supported) language code.
   The valid language codes are de (German), es (Spanish), fr (French), it (Italian), pt (Portuguese), ja (Japanese), ko (Korean), zh-CN (Chinese (Simplified))
*/
function get_translated_url($language='de', $url) {
    if(!isset($url)) $url = get_bloginfo('url');
    return get_bloginfo('url') . '/wp-content/plugins/translator.php?l=' . $language . '&amp;u=' . $url;
}

/* Provide flag image code for a given (if supported) language code.
   The valid language codes are de (German), es (Spanish), fr (French), it (Italian), pt (Portuguese), ja (Japanese), ko (Korean), zh-CN (Chinese (Simplified))
*/
function get_flag_image($language='de') {
    return get_bloginfo('url') . '/wp-content/plugins/flags/flag_' . $language . '.gif';
}
?>