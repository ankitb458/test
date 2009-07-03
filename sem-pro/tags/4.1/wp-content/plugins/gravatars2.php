<?php
/*
Plugin Name: Gravatars2
Plugin URI: http://zenpax.com/gravatars2/
Description: Implements Gravatars (global avatars: gravatar.com) with enhanced caching support, cron support, & administrative interface to control default options.  Registered users can use local Gravatars (also cached). Copyright 2006 Kip Bond; Licensed under the terms of the <a href="http://www.gnu.org/licenses/gpl.html">GPL</a>.
Version: 2.5.0
Author: Kip Bond
Author URI: http://zenpax.com/gravatars2/

based upon Scott Merill's Gravatars plugin:
http://www.skippy.net/blog/2005/03/24/gravatars/
... which was
based upon the original Gravatar plugin:
http://gravatar.com/implement.php#section_2_2
*/

/// MAIN PROGRAM
add_action ('admin_menu', 'gravatar_menu');
add_action ('shutdown', 'gravatar_cache_update');
if ( ($gravatar_options = get_option('gravatar_options')) && ('1' == $gravatar_options['gravatar_in_posts']) ) {
	add_filter('the_content', 'gravatar_in_posts');
}


/// FUNCTIONS
////////////////////////////////////////////////////////


////////////////////////////////////////////////////////
function gravatar_version($type='') {
	$plugin_name = "Gravatars2";
	$plugin_version = "2.5.0";
	$plugin_date = "2006.06.23 21:43CST";
	switch ($type) {
	case "name":
		return "$plugin_name";
		break;
	case "version":
		return "$plugin_version";
		break;
	case "date":
		return "$plugin_date";
		break;
	case "html":
		return "<strong><a href='http://zenpax.com/gravatars2/'>$plugin_name</a> (v$plugin_version)</strong> <small>[$plugin_date]</small>";
		break;
	default:
		return "$plugin_name (v$plugin_version) [$plugin_date]";
	}
}



////////////////////////////////////////////////////////
function gravatar_message($text='Done', $type='updated') {
	if (empty($type)) { $type = 'updated'; }
	echo "<div id='message' class='$type fade'><p>$text</p></div>";
} // gravatar_message



////////////////////////////////////////////////////////
function gravatar_menu() {
global $gravatar_options;
$ulevel = (!isset($gravatar_options['gravatar_local_ulevel'])) ? 1 : $gravatar_options['gravatar_local_ulevel'];
	add_options_page(__('Gravatar Options', 'gravatars'), __('Gravatars', 'gravatars'), 9, __FILE__, 'gravatar_manage_options');
	add_management_page(__('Gravatar Cache', 'gravatars'), __('Gravatar Cache', 'gravatars'), 9, __FILE__, 'gravatar_manage_cache');
	if ('1' == $gravatar_options['gravatar_allow_local']) {
		add_submenu_page('profile.php', __('Gravatar Selection', 'gravatars'), __('Gravatar', 'gravatars'), $ulevel, __FILE__, 'gravatar_profile');
	}
} // gravatar_menu



////////////////////////////////////////////////////////
function gravatar_default_options($action = '', $rating = '', $size = '', $border = '', $default = '', $rcache_method = 'fsockopen', $info_timeout = '10', $copy_timeout = '10', $pos_expire = '604800', $neg_expire = '86400', $err_expire = '3600', $posts = '0', $local = '1', $ulevel = '1', $gravatar_cache = '1', $auto_cache_check = '1') {
global $wpdb, $gravatar_options, $CANCACHE;

if (empty($pos_expire)) { $pos_expire = '604800'; }
if (empty($neg_expire)) { $neg_expire = '86400'; }
if (empty($err_expire)) { $err_expire = '3600'; }
if (empty($rcache_method)) { $rcache_method = 'fsockopen'; }
if (empty($info_timeout)) { $info_timeout = '10'; }
if (empty($copy_timeout)) { $copy_timeout = '10'; }

if (!isset($gravatar_cache)) {
	$gravatar_cache = 1;
}

if (!isset($CANCACHE)) { $CANCACHE = gravatar_cache_capable(0); }
if (!$CANCACHE) {
	$gravatar_cache = 0;
	$auto_cache_check = 1;
} elseif (!isset($gravatar_cache)) {
	$gravatar_cache = 1;
}

if ( (FALSE === get_option('gravatar_options')) || ('reset' == $action) ) {
	$gravatar_options = array('gravatar_rating' => 'PG',
		'gravatar_size' => '80',
		'gravatar_border' => '',
		'gravatar_default' => '/wp-content/gravatars/blank_gravatar.png',
		'gravatar_rcache_method' => 'fsockopen',
		'gravatar_info_timeout' => '10',
		'gravatar_copy_timeout' => '10',
		'gravatar_pos_expire_time' => '604800',
		'gravatar_neg_expire_time' => '86400',
		'gravatar_err_expire_time' => '3600',
		'gravatar_in_posts' => '1',
		'gravatar_allow_local' => '1',
		'gravatar_local_ulevel' => '1',
		'gravatar_cache' => $gravatar_cache,
		'gravatar_auto_cache_check' => $auto_cache_check);
	update_option('gravatar_options', $gravatar_options);
} elseif ('update' == $action) {
	if (!is_url($default) && (!empty($default)) && substr($default,0,1) != "/") {
		$default = "/" . $default;
	}
	$gravatar_options = array('gravatar_rating' => $rating,
		'gravatar_size' => $size,
		'gravatar_border' => $border,
		'gravatar_default' => $default,
		'gravatar_rcache_method' => $rcache_method,
		'gravatar_info_timeout' => $info_timeout,
		'gravatar_copy_timeout' => $copy_timeout,
		'gravatar_pos_expire_time' => $pos_expire,
		'gravatar_neg_expire_time' => $neg_expire,
		'gravatar_err_expire_time' => $err_expire,
		'gravatar_in_posts' => $posts,
		'gravatar_allow_local' => $local,
		'gravatar_local_ulevel' => $ulevel,
		'gravatar_cache' => $gravatar_cache,
		'gravatar_auto_cache_check' => $auto_cache_check);
	update_option('gravatar_options', $gravatar_options);
}

} // gravatar_default_options



////////////////////////////////////////////////////////
function gravatar_profile() {
load_plugin_textdomain('gravatars');
global $wpdb, $user_email;

if (empty($user_email)) {
	get_currentuserinfo();
}

$cached = FALSE;
$gravpath = '';
$error = '';
$message = '';

if (isset($_POST['gravpath'])) {
	$gravpath = trim($_POST['gravpath']);
	$gravpath = str_replace("..", '', $gravpath);
	if ('' != $gravpath ) {
		if (preg_match('!^https?://!i', $gravpath)) {
			// it's a remote URL; let's try to copy it.
			$filename = md5($user_email);
			$cached = gravatar_copy($gravpath, ABSPATH . "wp-content/gravatars/local/$filename");
			if (! $cached) {
				// error, give them the default
				$filename = '';
				$error = __("There was an error copying the gravatar.  The system default has been used instead.", 'gravatars');
			} else {
				$message = __('Remote Gravatar Copied.', 'gravatars');
			}
		} else {
			// it's a local path
			if (is_file(ABSPATH . "wp-content/gravatars/local/$gravpath")) {
				$filename = md5($user_email);
				$message = __('Local Image File Selected.', 'gravatars');
//			} elseif (is_file(ABSPATH . "wp-content/gravatars/local/$gravpath")) {
			} else {
				$filename = '';
				$error = __("Local Image File NOT FOUND.  The system default has been used instead.", 'gravatars');
			}
		}

		if ($filename) {
			$gravatar_local = get_option('gravatar_local');
			$gravatar_local[$user_email] = $filename;
			$message .= " Local Gravatar Updated. ";

			$gravatar_expire = get_option('gravatar_expire');
			if ($gravatar_expire[$user_email]) {
				unset($gravatar_expire[$user_email]);
				update_option('gravatar_expire', $gravatar_expire);
				$message .= " Global Gravatar Deleted.";
			}


			if (is_file(ABSPATH . 'wp-content/gravatars/global/' . md5($user_email))) {
				unlink(ABSPATH . 'wp-content/gravatars/global/' . md5($user_email));
				$message .= " Cached Global Image Deleted.";
			}
		}

	} else {
		// empty gravpath, so delete the local record
		$gravatar_local = get_option('gravatar_local');
		if ($gravatar_local[$user_email]) {
			unset($gravatar_local[$user_email]);
			$message = "Local Gravatar Deleted";
			if (is_file(ABSPATH . 'wp-content/gravatars/local/' . md5($user_email))) {
				unlink(ABSPATH . 'wp-content/gravatars/local/' . md5($user_email));
				$message .= ". &nbsp; &nbsp; Cached Image Deleted.";
			}
		}
	}
	update_option("gravatar_local", $gravatar_local);
}

if (! isset($gravatar_local)) {
	$gravatar_local = get_option('gravatar_local');
}

if (empty($gravatar_local[$user_email])) {
	$gravwhere = get_settings('blogname') . __(' is using your <strong>default global</strong> gravatar', 'gravatars');
	// use their cached gravatar, or give them the system default
} else {
	$gravwhere = get_settings('blogname') . __(' is using your <strong>local</strong> gravatar', 'gravatars') . ":";
}

$user_gravatar = gravatar_path($user_email);

if (!empty($error)) {
	gravatar_message($error, "error");
}
if (!empty($message)) {
	gravatar_message($message);
}

echo "<div class='wrap'>";
echo "<p>$gravwhere:<br /><img src='$user_gravatar' /></p>";
echo "<p>";
_e('You can assign a specific gravatar for use on ', 'gravatars');
echo get_settings('blogname') . "; ";
_e ('enter the path (local or remote) below', 'gravatars');
echo ':</p>';
echo "<p><form method='POST'><input type='text' size='50' name='gravpath' /> ";
echo "<input type='hidden' name='user' value='" . $user_email . "'><input type='submit' name='submit' value='" . __('Submit', 'gravatars') . "'></p>";
echo "<p><em>";
_e('A blank submission will remove your locally defined gravatar', 'gravatars');
echo '.</em></fieldset></div>';

} // gravatar_profile



////////////////////////////////////////////////////////
function is_url($url='') {
if (substr(strtolower($url), 0, 4) == "http") {
	return TRUE;
} else {
	return FALSE;
}
} // is_url



////////////////////////////////////////////////////////
function gravatar_rcache_methods() {
	$rcache_methods = array();
	$rcache_methods["fsockopen"] = "fsockopen";
	if(function_exists('curl_init')) {
		$rcache_methods["curl"] = "cURL";
	}
	return $rcache_methods;
}



////////////////////////////////////////////////////////
//HTTPRequest usage:
//$r = new HTTPRequest('http://www.php.net', [timeout]);
//echo $r->DownloadToString();
class HTTPRequest
{
   var $_fp;        // HTTP socket
   var $_url;        // full URL
   var $_host;        // HTTP host
   var $_protocol;    // protocol (HTTP/HTTPS)
   var $_uri;        // request URI
   var $_port;        // port
   var $_timeout;     // socket timeout
  
   // scan url
   function _scan_url()
   {
       $req = $this->_url;
      
       $pos = strpos($req, '://');
       $this->_protocol = strtolower(substr($req, 0, $pos));
      
       $req = substr($req, $pos+3);
       $pos = strpos($req, '/');
       if($pos === false)
           $pos = strlen($req);
       $host = substr($req, 0, $pos);
      
       if(strpos($host, ':') !== false)
       {
           list($this->_host, $this->_port) = explode(':', $host);
       }
       else
       {
           $this->_host = $host;
           $this->_port = ($this->_protocol == 'https') ? 443 : 80;
       }
      
       $this->_uri = substr($req, $pos);
       if($this->_uri == '')
           $this->_uri = '/';
   }
  
   // constructor
   function HTTPRequest($url, $timeout)
   {
       $this->_url = $url;
       $this->_scan_url();
	   $this->_timeout = $timeout;
   }
  
   // download URL to string
   function DownloadToString()
   {
       $crlf = "\r\n";
      
       // generate request
       $req = 'GET ' . $this->_uri . ' HTTP/1.0' . $crlf
           .    'Host: ' . $this->_host . $crlf
           .    $crlf;
      
       // fetch
       $this->_fp = @fsockopen(($this->_protocol == 'https' ? 'ssl://' : '') . $this->_host, $this->_port, $errno, $errstr, $this->_timeout);
	   if ($this->_fp) {
		  fwrite($this->_fp, $req);
   	      while(is_resource($this->_fp) && $this->_fp && !feof($this->_fp))
   	      $response .= fread($this->_fp, 1024);
   	      fclose($this->_fp);
		}
      
       // split header and body
       $pos = strpos($response, $crlf . $crlf);
       if($pos === false)
           return($response);
       $header = substr($response, 0, $pos);
       $body = substr($response, $pos + 2 * strlen($crlf));
      
       // parse headers
       $headers = array();
       $lines = explode($crlf, $header);
       foreach($lines as $line)
           if(($pos = strpos($line, ':')) !== false)
               $headers[strtolower(trim(substr($line, 0, $pos)))] = trim(substr($line, $pos+1));
      
       // redirection?
       if(isset($headers['location']))
       {
           $http = new HTTPRequest($headers['location']);
           return($http->DownloadToString($http));
       }
       else
       {
           return($body);
       }
   }
} // HTTPRequest



////////////////////////////////////////////////////////
function gravatar_get_url($url, $timeout=10) {
	global $gravatar_options;

	switch ($gravatar_options['gravatar_rcache_method']) {
		case "curl":
			$curl_handle = curl_init();
			curl_setopt($curl_handle, CURLOPT_URL, $url);
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, $timeout);
			$data = curl_exec($curl_handle);
			curl_close($curl_handle);
			return $data;

		case "fsockopen":

		default:
			$hr = new HTTPRequest($url, $timeout);
			return $hr->DownloadToString();
	}
} // gravatar_get_url



////////////////////////////////////////////////////////
function gravatar_write_file($filename, $data) {
	$result = FALSE;
	$handle = fopen($filename, "wb");
	if ($handle) {
		$result = fwrite($handle, $data);
	} 
	fclose($handle);
	return $result;
} // gravatar_write_file



////////////////////////////////////////////////////////
function gravatar_copy($url, $filename) {
	global $gravatar_options;
	$result = FALSE;
	$data = gravatar_get_url($url, $gravatar_options['gravatar_copy_timeout']);
	if (!empty($data)) {
		$result = gravatar_write_file($filename, $data);
	}
	return $result;
} // gravatar_copy



////////////////////////////////////////////////////////
function gravatar_check_who($who='') {
$who = trim($who);
if (empty($who) || ((!is_url($who)) && (!is_email($who)))) {
	global $comment, $authordata;
	if (empty($comment)) {
		$who = $authordata->user_email;
	} else {
		if (('' == $comment->comment_type) || ('comment' == $comment->comment_type)) {
			$who = $comment->comment_author_email;
		} elseif (('trackback' == $comment->comment_type) || ('pingback' == $comment->comment_type)) {
			$who = $comment->comment_author_url;
		}       
	}
}
return $who;
} // gravatar_check_who



////////////////////////////////////////////////////////
function gravatar_check_default($default='') {
$default = trim($default);
return $default;
} // gravatar_check_default



////////////////////////////////////////////////////////
function gravatar_cron_enabled() {
load_plugin_textdomain('gravatars');
global $gravatar_options, $CRONENABLED;

$CRONENABLED = FALSE;

if (function_exists('gravatar_cron_run') && function_exists('wp_cron_init')) {
	$CRONENABLED = TRUE;
}

if ($CRONENABLED) {
	$gravatar_options['gravatar_auto_cache_check'] = '0';
	$_POST['gravatar_auto_cache_check'] = '0';
}

update_option('gravatar_options', $gravatar_options);

return $CRONENABLED;
} // gravatar_cron_enabled


////////////////////////////////////////////////////////
function gravatar_cache_capable($showmsg=0) {
load_plugin_textdomain('gravatars');
global $gravatar_options, $CANCACHE;

$CANCACHE = TRUE;
$message = "";

if (! is_writable(ABSPATH . "wp-content/gravatars/global")) {
	$CANCACHE = FALSE;
	$message .= "<div class='updated'><p align='center'>";
	$message .= __('WARNING! WARNING! WARNING!', 'gravatars');
	$message .= '<br /><strong><code>' . ABSPATH . 'wp-content/gravatars/global</code></strong><br />';
	$message .= __('is not writable', 'gravatars');
	$message .= '.</p>';
	$message .= "<p align='center'>";
	$message .= __('Gravatar caching will be disabled until this directory is made writable', 'gravatars');
	$message .= '.</p></div>';
}

if (!$CANCACHE) {
	$gravatar_options['gravatar_cache'] = '0';
	$gravatar_options['gravatar_auto_cache_check'] = '1';
	$_POST['gravatar_cache'] = '0';
	$_POST['gravatar_auto_cache_check'] = '1';
}

update_option('gravatar_options', $gravatar_options);

if ($showmsg) { echo $message; }

return $CANCACHE;
} // gravatar_cache_capable



////////////////////////////////////////////////////////
function gravatar_manage_options() {
load_plugin_textdomain('gravatars');
global $wpdb, $gravatar_expire, $gravatar_options;

gravatar_default_options();
$gravatar_options = get_option('gravatar_options');
$gravatar_expire = get_option('gravatar_expire');

// check to see if we can cache gravatars
$CANCACHE = gravatar_cache_capable(1);

// check to see if wp-cron-gravatars is enabled
$CRONENABLED = gravatar_cron_enabled();

if ( isset($_POST['reset']) && ('RESET' == $_POST['reset']) ) {
	// reset the defaults
	gravatar_default_options('reset');
	$gravatar_options = get_option('gravatar_options');
	if ( isset($_POST['cron_refresh_rate']) ) {
		gravatar_cron_set_refresh('default');
	}
	gravatar_message("Gravatar Options Reset");
} elseif ( isset($_POST['gravatar_options']) && ('update' == $_POST['gravatar_options']) ) {
	// update the defaults
	gravatar_default_options('update', $_POST['rating'],  $_POST['size'],  $_POST['gravatar_border'],  $_POST['default'],  $_POST['rcache_method'], $_POST['info_timeout'], $_POST['copy_timeout'], $_POST['pos_expire'], $_POST['neg_expire'], $_POST['err_expire'], $_POST['gravatar_in_posts'], $_POST['gravatar_allow_local'], $_POST['local_ulevel'], $_POST['gravatar_cache'], $_POST['gravatar_auto_cache_check']);
	$gravatar_options = get_option('gravatar_options');
	if ( isset($_POST['cron_refresh_rate']) ) {
		gravatar_cron_set_refresh($_POST['cron_refresh_rate']);
	}
	gravatar_message("Gravatar Options Updated");
}

echo "<div class='wrap'>";
echo "<table width='100%' cellpadding='0' cellspacing='0'><tr><td><h2>";
_e('Gravatar Options', 'gravatars');
echo "</h2></td>";
echo "<td align='right'><h2><span style='font-size: 75%;'><a href='./edit.php?page=gravatars2.php'>Gravatar Cache</a></span></h2></td>";
echo "</tr></table>";

echo "<fieldset class='options'>";

echo "<table width='100%' cellspacing='2' cellpadding='5' class='editform'>";


echo "<tr><td align='center'>";
echo "<form method='POST'>";
echo "<input type='hidden' name='gravatar_options' value='update'>";
_e('Default gravatar rating', 'gravatars');
echo ":<br /> <select name='rating'>";
$ratings = array ("G", "PG", "R", "X");
foreach ($ratings as $r) {
	echo "<option value='$r'";
	if ($r == $gravatar_options['gravatar_rating']) { echo " selected"; }
	echo ">$r</option>";
}
echo "</select></td>\r\n";

echo "<td align='center'>";
_e('Default gravatar size', 'gravatars');
echo ": <br /> <select name='size'>";
for ($i = 1; $i <= 80; $i++) {
	echo "<option value='$i'";
	if ($i == $gravatar_options['gravatar_size']) { echo " selected"; }
	echo ">$i</option>";
}
echo "</select></td>\r\n";
echo "<td></td></tr>";
//echo "<td align='center'>";
//_e('Border Color', 'gravatars');
//echo ":<br /> <input type='text' name='gravatar_border' size='10' value='" . $gravatar_options['gravatar_border'] . "' /></td></tr>";


echo "<tr class='alternate'>";
echo "<td colspan='3' align='left' class='alternate'>";
_e('Default gravatar image', 'gravatars');
echo " (<em>relative to website document root path</em>):<br />";
echo "<strong>" . $_SERVER['DOCUMENT_ROOT'] . "</strong>";
echo "<input type='text' name='default' value='" . $gravatar_options['gravatar_default'] . "' size='70' /><br />";
_e('You may enter: ', 'gravatars');
echo '<br /><ul><li>';
_e('a local filename: ', 'gravatars');
echo '(<code>/images/foo.png</code>)';
echo ',</li><li>';
_e('a directory containing a collection of gravatars from which to randomly select: ', 'gravatars');
echo '(<code>/wp-content/gravatars/random/</code>)</li><li>';
_e('or a remote URI ', 'gravatars');
echo '(<code>http://example.com/foo.png</code>)</li></ul>';
_e('<strong>Please read the documentation for more information about valid options.</strong>', 'gravatars');
echo '</td></tr>';


echo "<tr><td align='center'>";
_e('Cache gravatars', 'gravatars');
echo '?<br />';
if (!$CANCACHE) {
	_e('<strong>DISABLED</strong>');
} else {
	echo "<input type='radio' name='gravatar_cache' value='1'";
	if ('1' == $gravatar_options['gravatar_cache']) {
		echo " checked='checked'";
	}
	echo '>';
	_e('Yes', 'gravatars');
	echo "&nbsp;<input type='radio' name='gravatar_cache' value='0'";
	if ('0' == $gravatar_options['gravatar_cache']) {
		echo " checked='checked'";
	}
	echo ">";
	_e('No', 'gravatars');
}
echo "</td>";

if (!$CRONENABLED) {
	echo "<td>&nbsp;</td>";
	echo "<td align='center'>";
	_e('Automatic Cache Checking', 'gravatars');
	echo ":<br /> <input type='radio' name='gravatar_auto_cache_check' value='1'";
	if ('1' == $gravatar_options['gravatar_auto_cache_check']) {
		echo " checked='checked'";
	}
	echo ">";
	_e('Enabled', 'gravatars');
	echo "&nbsp;<input type='radio' name='gravatar_auto_cache_check' value='0'";
	if ('0' == $gravatar_options['gravatar_auto_cache_check']) {
		echo " checked='checked'";
	}
	echo '> ';
	_e('Disabled', 'gravatars');
} else {
	$gravatar_cron_options = gravatar_cron_options();
	$lastrun = date(DATE_W3C, $gravatar_cron_options['last_run']);
	$refresh_rate = $gravatar_cron_options['refresh_rate'];

	echo "<td align='center'><strong>WP-CRON ENABLED</strong><br />";
	echo "Last Run: $lastrun </td>";

	echo "<td align='center'>";

	_e('Cron Refresh Rate (Once Every:)', 'gravatars');
	echo ":<br /> <select name='cron_refresh_rate'>";
	$cron_refresh_rates = gravatar_cron_times();
	$cron_refresh_ratekeys = array_keys($cron_refresh_rates);
	foreach ($cron_refresh_ratekeys as $ratekey) {
		echo "<option value='$ratekey'";
		if ($ratekey == $gravatar_cron_options['refresh_rate']) { echo " selected"; }
		echo ">$cron_refresh_rates[$ratekey]</option>";
	}
	echo "</select></td>\r\n";
}

echo '</td></tr>';

echo "<tr><td align='center'>";
_e('Remote Caching Method', 'gravatars');
echo ":<br /> <select name='rcache_method'>";
$rcache_methods = gravatar_rcache_methods();
$rcache_methods_keys = array_keys($rcache_methods);
foreach ($rcache_methods_keys as $method_key) {
	echo "<option value='$method_key'";
	if ($method_key == $gravatar_options['gravatar_rcache_method']) { echo " selected"; }
	echo ">$rcache_methods[$method_key]</option>";
}
echo "</select></td>\n";
echo "<td>";
_e('Remote Info Timeout', 'gravatars');
echo ":<br /> <input type='text' size='2' name='info_timeout' value='" . $gravatar_options['gravatar_info_timeout'] . "' /> ";
_e('seconds', 'gravatars');
echo "</td>\n";
echo "<td>";
_e('Remote Copy Timeout', 'gravatars');
echo ":<br /> <input type='text' size='2' name='copy_timeout' value='" . $gravatar_options['gravatar_copy_timeout'] . "' /> ";
_e('seconds', 'gravatars');
echo "</td>\n";
echo "</tr>";

echo "<tr><td class='alternate' align='left' colspan='3'>";
_e('Enter Cache Time (in seconds) for Successful Image cache (positive), No Image Cached (negative), and Failed Cache Attempt (error)', 'gravatars');
echo "</td></tr>";


echo "<tr><td class='alternate' align='center'>";
_e('Cached Image (positive)', 'gravatars');
echo ":<br /> <input type='text' size='10' name='pos_expire' value='" . $gravatar_options['gravatar_pos_expire_time'] . "' /></td>";
echo "<td class='alternate' align='center'>";
_e('No Image (negative)', 'gravatars');
echo ":<br /> <input type='text' size='10' name='neg_expire' value='" . $gravatar_options['gravatar_neg_expire_time'] . "' /></td>";
echo "<td class='alternate' align='center'>";
_e('Failure (error)', 'gravatars');
echo ":<br /> <input type='text' size='10' name='err_expire' value='" . $gravatar_options['gravatar_err_expire_time'] . "' /></td>";
echo "</tr>";


echo "<tr><td align='center'>";
_e('Allow local gravatars', 'gravatars');
echo "?<br /> <input type='radio' name='gravatar_allow_local' value='1'";
if ('1' == $gravatar_options['gravatar_allow_local']) {
	echo " checked='checked'";
}
echo ">";
_e('Yes', 'gravatars');
echo "&nbsp;<input type='radio' name='gravatar_allow_local' value='0'";
if ('0' == $gravatar_options['gravatar_allow_local']) {
	echo " checked='checked'";
}
echo '> ';
_e('No', 'gravatars');
echo '</td>';



echo "<td align='center'>";
_e('Local Grav ', 'gravatars');
echo "<a href='http://codex.wordpress.org/User_Levels' target='_blank'>";
_e('User Level', 'gravatars');
echo "</a>";
echo ": <br /> <select name='local_ulevel'>";
for ($i = 0; $i <= 10; $i++) {
	echo "<option value='$i'";
	if ($i == $gravatar_options['gravatar_local_ulevel']) { echo " selected"; }
	echo ">$i</option>";
}
echo "</select></td>\r\n";



echo "<td align='center'>";
_e('Replace <code>&lt;gravatar foo@bar.com&gt;</code> in posts', 'gravatars');
echo "<br />";
echo "? <input type='radio' name='gravatar_in_posts' value='1'";
if ('1' == $gravatar_options['gravatar_in_posts']) {
	echo " checked='checked'";
}
echo '>';
_e('Yes', 'gravatars');
echo " &nbsp;<input type='radio' name='gravatar_in_posts' value='0'";
if ('0' == $gravatar_options['gravatar_in_posts']) {
	echo " checked='checked'";
}
echo '> ';
_e('No', 'gravatars');
echo '</td></tr>';


echo "<tr'><td align='left' colspan='2'><input type='submit' name='submit' value='" . __('Submit', 'gravatars') . "'</td>\r\n";
echo "<td align='right'><input type='submit' name='reset' value='RESET'></td></tr>\r\n";

echo "</table></form>\r\n";
echo "</fieldset>\r\n";
echo "<div style='width: 100%; text-align: center;'>" . gravatar_version("html") . "</div>";
echo "</div>";
include (ABSPATH . 'wp-admin/admin-footer.php');
// just to be sure
die;

} // gravatar_manage_options



////////////////////////////////////////////////////////
function gravatar_manage_cache() {
global $wpdb, $gravatar_expire, $gravatar_options, $gravatar_local;

// check to see if we can cache gravatars
$CANCACHE = gravatar_cache_capable(1);

gravatar_default_options();
$gravatar_options = get_option('gravatar_options');
if (! isset($gravatar_expire)) {
	$gravatar_expire = get_option('gravatar_expire');
}
if (! isset($gravatar_local)) {
	$gravatar_local = get_option('gravatar_local');
}

if (isset($_POST['delete_local'])) {
	// delete local gravatar
	$who = $_POST['delete_local'];
	if ('ALL_LOCAL' == $who) {
		// delete all local gravatars
		$gravatar_local = '';
		update_option('gravatar_local', '');
		$message = "All Local Gravatars Deleted";
		if ($d = opendir(ABSPATH . "wp-content/gravatars/local/")) {
			$count = 0;
			while (($file = readdir($d)) !== false) {
				if (is_file(ABSPATH . "wp-content/gravatars/local/$file")) {
					unlink(ABSPATH . "wp-content/gravatars/local/$file");
					$count++;
				}
			}
			closedir($d);
			$message .= ". &nbsp; &nbsp; <strong>$count</strong> Cached Images Deleted.";
		}
		gravatar_message($message);
	} else {
		// delete just one local gravatar
		unset($gravatar_local[$who]);
		update_option('gravatar_local', $gravatar_local);
		$message = "Local Gravatar Deleted";
		if (is_file(ABSPATH . 'wp-content/gravatars/local/' . md5($who))) {
			unlink(ABSPATH . 'wp-content/gravatars/local/' . md5($who));
			$message .= ". &nbsp; &nbsp; Cached Image Deleted.";
		}
		gravatar_message($message);
	}

} elseif ( (isset($_POST['refresh_cache'])) && ($CANCACHE) ) {
	// check gravatar cache
	list($cached, $updated) = gravatar_cache_refresh();
	$message = "Gravatar Cache Refreshed.";
	$message .= "&nbsp; &nbsp; <strong>$updated</strong> Gravatars Updated.";
	if ($updated) {
		$message .= "&nbsp; &nbsp; <strong>$cached</strong> Images Cached.";
	}
	gravatar_message($message);

} elseif ( (isset($_POST['delete_cache'])) && ($CANCACHE) ) {
	// delete cached gravatar
	$who = $_POST['delete_cache'];
	if ('ALL_CACHE' == $who) {
		// delete all cached gravatars
		$gravatar_expire = '';
		update_option('gravatar_expire', '');
		$message = "All Gravatars Deleted";
		if ($d = opendir(ABSPATH . "wp-content/gravatars/global/")) {
			$count = 0;
			while (($file = readdir($d)) !== false) {
				if (is_file(ABSPATH . "wp-content/gravatars/global/$file")) {
					unlink(ABSPATH . "wp-content/gravatars/global/$file");
					$count++;
				}
			}
			closedir($d);
			$message .= ". &nbsp; &nbsp; <strong>$count</strong> Cached Images Deleted.";
		}
		gravatar_message($message);

	} else {
		// delete just one cached gravatar
		$gravatar_expire = get_option('gravatar_expire');
		unset($gravatar_expire[$who]);
		update_option('gravatar_expire', $gravatar_expire);
		$message = "Gravatar Deleted";
		if (is_file(ABSPATH . 'wp-content/gravatars/global/' . md5($who))) {
			unlink(ABSPATH . 'wp-content/gravatars/global/' . md5($who));
			$message .= ". &nbsp; &nbsp; Cached Image Deleted.";
		}
		gravatar_message($message);
	}
}

echo "<div class='wrap'>";

echo "<table width='100%' cellpadding='0' cellspacing='0'><tr><td><h2>";
_e('Gravatar Cache', 'gravatars');
echo "</h2></td>";
echo "<td align='right'><h2><span style='font-size: 75%;'><a href='./options-general.php?page=gravatars2.php'>Gravatar Options</a></span></h2></td>";
echo "</tr></table>";

if (!$CANCACHE) { 
	gravatar_message("Caching Not Available", "error");
	echo "<div style='clear: both;'>&nbsp;</div>";
	echo "<div style='width: 100%; text-align: center;'>" . gravatar_version("html") . "</div>";
	echo "</div>";
	die();
}

// first, collect all the people who have commented
$commenters = $wpdb->get_results("SELECT DISTINCT comment_author, comment_author_email, comment_author_url, COUNT(*) as count FROM $wpdb->comments WHERE $wpdb->comments.comment_approved = '1' AND $wpdb->comments.comment_type = '' AND $wpdb->comments.comment_author_email != '' GROUP BY comment_author_email ORDER BY comment_ID DESC");

$locals = array();
$cached = array();
// $gravatar_local = get_option('gravatar_local');

foreach ($commenters as $commenter) {
	$who = $commenter->comment_author_email;
	if (empty($who)) { continue; }

	$gravatar = '';
	$name = $commenter->comment_author;
	$url = $commenter->comment_author_url;
	$count = $commenter->count;

	if ('' != $gravatar_local[$who]) {
		$gravatar = get_settings('siteurl') . "/wp-content/gravatars/local/" . md5($who);
		// let's make these into relative links
		$gravatar = str_replace("http://" . $_SERVER['SERVER_NAME'], '', $gravatar);
		$gravatar = "<img src='$gravatar' alt='$name' title='$url' />";
		$foo = "<div style='float: left; text-align: center; margin: 2px; padding: 2px; border: 2px solid #9cf;'><form method='POST'>";
		$foo .= "$gravatar <br />";
		$foo .= ($url) ? "<a href='$url' title='$url'>$name</a>" : "$name";
		$foo .= "<br />$who<br />$count comments<br /><input type='hidden' name='delete_local' value='$who' /><input type='submit' name='gravatar_admin' value='[ X ]' /></form></div>\r\n";
		$locals[] = $foo;

	} elseif ($date = $gravatar_expire[$who])  {
		switch (substr($date, 0, 1)) {
		case "*":
			$gravatar = get_settings('siteurl') . "/wp-content/gravatars/global/" . md5($who);
			// let's make these into relative links
			$gravatar = str_replace("http://" . $_SERVER['SERVER_NAME'], '', $gravatar);
			$gravatar = "<img src='$gravatar' alt='$name' title='$url' />";
			break;
		case "X":
			$gravatar = 'NO GRAVATAR (X)';
			break;
		case "E":
			$gravatar = 'NO GRAVATAR (E)';
			break;
		default:
			$gravatar = 'NO GRAVATAR (?)';
		}
		$date = substr($date, 1);
		$date = date(DATE_W3C,$date);
		$foo = "<div style='float: left; text-align: center; margin: 2px; padding: 2px; border: 2px solid #f90;'><form method='POST'>";
		$foo .= "$gravatar <br />";
		$foo .= ($url) ? "<a href='$url' title='$url'>$name</a>" : "$name";
		$foo .= "<br />$who<br />";
		$md5 = md5($who);
		$foo .= "<a href='http://www.gravatar.com/avatar.php?gravatar_id=$md5'>";
		$foo .= "$md5</a><br />";
		$foo .= $date . "<br />$count comments<br /><input type='hidden' name='delete_cache' value='$who' /><input type='submit' name='gravatar_admin' value='[ X ]' /></form></div>\r\n";
		$cached[] = $foo;
	}
}

echo '<h2><span style="font-size: 75%;">';
_e('Local Gravatars', 'gravatars');
echo "</span></h2><fieldset class='options'>\r\n";
if (count($locals) > 0) {
	echo '<p>';
	_e("Deleting a local gravatar causes your blog to use that user's global gravatar (if they have one) or your site's default gravatar", 'gravatars');
	echo '.</p>';
	foreach ($locals as $local) {
		echo $local;
	}
	echo "<div style='clear: both;'>&nbsp;</div>";
	echo "<form method='POST'><table width='100%' class='editform'><tr><td class='alternate' align='right'>Delete all local gravatars: <input type='hidden' name='delete_local' value='ALL_LOCAL' /><input type='submit' name='gravatar_admin' value='DELETE' /></td></tr></table></form>";
} else {
	echo "<p align='center'><strong>";
	_e('No local gravatars', 'gravatars');
	echo '</strong></p>';
}
echo "</fieldset>";

echo '<h2><span style="font-size: 75%;">';
_e('Cached Gravatars', 'gravatars');
echo "</span></h2><fieldset class='options'>\r\n<p>";
_e('Deleting a cached gravatar will force your blog to request the latest version (if any) from <code>gravatar.com</code>', 'gravatars');
echo '.<br />Current Time:  ' . date(DATE_W3C,time()) . '</p>';
echo "<div style='clear: both;'>&nbsp;</div>";
echo "<form method='POST'><table width='100%' class='editform'><tr><td class='alternate' align='right'>Refresh Gravatar Cache: <input type='hidden' name='refresh_cache' value='ALL_CACHE' /><input type='submit' name='gravatar_admin' value='REFRESH CACHE' /></td></tr></table></form>";
echo "<div style='clear: both;'>&nbsp;</div>";
if (count($cached) > 0) {

	foreach ($cached as $cache) {
		echo $cache;
	}

	echo "<div style='clear: both;'>&nbsp;</div>";
	echo "<form method='POST'><table width='100%' class='editform'><tr><td class='alternate' align='right'>Delete all cached gravatars: <input type='hidden' name='delete_cache' value='ALL_CACHE' /><input type='submit' name='gravatar_admin' value='DELETE CACHE' /></td></tr></table></form>";
} else {
	echo "<p align='center'><strong>";
	_e('No cached gravatars', 'gravatars');
	echo '</strong></p>';
}
echo "</fieldset>\r\n";

echo "<div style='width: 100%; text-align: center;'>" . gravatar_version("html") . "</div>";
echo "</div>";
include (ABSPATH . 'wp-admin/admin-footer.php');
// just to be sure
die;

} // gravatar_manage_cache



////////////////////////////////////////////////////////
function gravatar_in_posts($content = '') {

if (empty($content)) { return; }

$matches = array();
$replacement = array();
$counter = 0;

// look for all instances of <gravatar ... > in the content
preg_match_all("/<gravatar ([^>]+) \/>/", $content, $matches);
// for each instance, let's try to parse it
foreach ($matches['0'] as $match) {
	list( ,$foo, ) = explode(' ', $match);
	$replacement[$counter] = "<img class='postgrav' src='" . gravatar_path($foo) . "' alt='Gravatar' />";
	$counter++;
}
for ($i = 0; $i <= $counter; $i++) {
	$content = str_replace($matches[0][$i], $replacement[$i], $content);
}
return $content;
} // gravatar_in_posts



////////////////////////////////////////////////////////
function gravatar_info($md5 = '') {
if (empty($md5)) { return false; }
global $gravatar_options;

$r = array();
//$foo = file("http://www.gravatar.com/info/md5/$md5");

$url = "http://www.gravatar.com/info/md5/$md5";
$data = gravatar_get_url($url, $gravatar_options['gravatar_info_timeout']);

$foo = explode("\n", $data);
if (! $foo) return false;

array_shift($foo); // strip leading <xml ...> declaration
array_shift($foo); // strip opening <gravatar>
array_pop($foo);   // strip closing <gravatar>

foreach ($foo as $bar) {
	$matched = array();
	preg_match_all("/([^<>])+/", $bar, $matched);
	$r[$matched[0][1]] = $matched[0][2];
}

return $r;
} // gravatar_info



////////////////////////////////////////////////////////
function gravatar_default_image() {
// return the default gravatar (or random one)
global $random_gravatars, $gravatar_options;

if ('/' != substr($gravatar_options['gravatar_default'], -1, 1)) {
	// only one (or no) default gravatar
	return $gravatar_options['gravatar_default'];
}

$doc_root = $_SERVER['DOCUMENT_ROOT'];

if (! is_dir($doc_root . $gravatar_options['gravatar_default'])) {
	return FALSE;
}

if (! isset($random_gravatars)) {
	// largely cribbed from photomatt:
	// http://photomatt.net/scripts/randomimage
	$random_gravatars = array();
	$handle = opendir($doc_root . $gravatar_options['gravatar_default']);
	while (false !== ($file = readdir($handle))) {
		 if ('.' != substr($file, 0, 1)) { 
			$random_gravatars[] = $file;
		}
	}
	closedir($handle);
}
mt_srand((double)microtime()*1000000); // seed for PHP < 4.2
$rand = mt_rand(0, (count($random_gravatars) - 1));
return $gravatar_options['gravatar_default'] . $random_gravatars[$rand];
} // gravatar_default_image



////////////////////////////////////////////////////////
function gravatar_query($md5 = '', $default = '') {
global $gravatar_options;

if (empty($md5)) return FALSE;

if (empty($default)) {
	$default = gravatar_default_image();
}

// prepare the query to gravatar.com
$gravatar = "http://www.gravatar.com/avatar.php?gravatar_id=$md5";

if ('' != $gravatar_options['gravatar_rating'])
	$gravatar .= "&rating=" . $gravatar_options['gravatar_rating'];

if ('' != $gravatar_options['gravatar_size'])
	$gravatar .= "&size=" . $gravatar_options['gravatar_size'];

if ( ('' != $default) && ('NONE' != $default) ) {
	$gravatar .= "&default=";
	if (! is_url($default)) {
		$gravatar .= "http://" . $_SERVER['SERVER_NAME'];
	}
	$gravatar .= $default;
}

//if ('' != $gravatar_options['gravatar_border'])
//	$gravatar .= "&border=" . $gravatar_options['gravatar_border'];

return $gravatar;
} // gravatar_query



////////////////////////////////////////////////////////
function gravatar_cache_image($md5 = '', $path="global") {
if ($path != "global" && $path != "local") { return FALSE; }
global $gravatar_options, $CANCACHE;

$path = ABSPATH ."wp-content/gravatars/$path/";

if (empty($md5)) return FALSE;

$cached = FALSE;

$gravatar = gravatar_query($md5, 'NONE');

if ( $CANCACHE ) {
	$cached = gravatar_copy($gravatar, "$path/$md5.TMP");
	if (! $cached) {
		// looks like the copy failed, delete the TMP
		if (is_file("$path/$md5.TMP")) {
			unlink("$path/$md5.TMP");
		}
	} else {
		// we copied successfully
		$cached = rename("$path/$md5.TMP", "$path/$md5");
	}
}

return $cached;
} // gravatar_cache_image



////////////////////////////////////////////////////////
function gravatar_cache_update() {
global $wpdb, $gravatar_expire, $gravatar_expire_updated;

if (! $gravatar_expire_updated) return;

update_option('gravatar_expire', $gravatar_expire);
} // gravatar_cache_update



////////////////////////////////////////////////////////
function gravatar_path($who = '', $default = '') {

// use globals to hopefully speed up subsequent iterations
global $gravatar_options, $gravatar_expire, $gravatar_local, $gravatar_expire_updated;

// were we passed a valid "who"?  if not, grab from comments
// implemented to deal w/ other gravatar plugins functions
$who = gravatar_check_who($who);

if ($gravatar_paths[$who]) {
	return $gravatar_paths[$who];
}

// *** need to check $default -- clear if $who was invalid
$default = gravatar_check_default($default);

$cached = FALSE;

if (! isset($gravatar_expire_updated)) {
	$gravatar_expire_updated = FALSE;
}

if (! isset($gravatar_options)) {
	gravatar_default_options();
	$gravatar_options = get_option('gravatar_options');
}

if (! isset($gravatar_expire)) {
	$gravatar_expire = get_option('gravatar_expire');
}

if (! isset($gravatar_local)) {
	$gravatar_local = get_option('gravatar_local');
}


// does this address have a local gravatar?
if ( ('' != $who) && (! empty($gravatar_local)) && ('' != $gravatar_local[$who]) ) {
	$url = parse_url(get_settings('siteurl') . '/wp-content/gravatars/local/' . md5($who));
	$gravatar_paths[$who] = $url['path'];
	return $url['path'];
}
if (! isset($now)) {
	$now = time();
}

if (('' != $who) && (is_url($who))) {
	// were we handed a URL?
	global $wpdb;
	// let's see if we know who owns this URL
	$parsed = parse_url($who);
	$email = $wpdb->get_var("SELECT DISTINCT comment_author_email FROM $wpdb->comments where comment_author_url='http://" . $parsed['host'] . "' ORDER BY comment_ID DESC LIMIT 1");
	if (is_email($email)) {
		$who = $email;
	} else {
		$who = '';
	}
} elseif (! is_email($who)) {
	$who = '';
}

if ('0' == $gravatar_options['gravatar_cache']) {
	// we're not using local cache, so give the gravatar.com URL
	$gravatar = gravatar_query(md5($who), $default);
	$gravatar = str_replace("&", "&amp;", $gravatar);
	$gravatar_paths[$who] = $gravatar;
	return $gravatar;
}

if (empty($who)) {
	// dummy step, to make the rest easier
	$cached = FALSE;
} else {
	list ($cached, $updated) = gravatar_check($who);
	if ($updated) { $gravatar_expire_updated = TRUE; }
	if (!$cached) { $no_gravatar[$who] = 1; }
}

if (FALSE === $cached) {
	$default_image = gravatar_default_image();
	$gravatar_paths[$who] = $default_image;
	return $default_image;
} else {
	// we want to return a relative URI
	$url = parse_url(get_settings('siteurl') . '/wp-content/gravatars/global/' . md5($who));
	$gravatar_paths[$who] = $url['path'];
	return $url['path'];
}
} // gravatar_path



////////////////////////////////////////////////////////
// refresh gravatar cache
function gravatar_cache_refresh() {
global $wpdb, $gravatar_options, $gravatar_expire, $gravatar_local;

if (! isset($gravatar_expire)) {
	gravatar_default_options();
	$gravatar_options = get_option('gravatar_options');
}
if (! isset($gravatar_expire)) {
	$gravatar_expire = get_option('gravatar_expire');
}
if (! isset($gravatar_local)) {
	$gravatar_local = get_option('gravatar_local');
}

$count_updated=0;
$count_cached=0;

// first, collect all the people who have commented
$commenters = $wpdb->get_results("SELECT DISTINCT comment_author, comment_author_email, comment_author_url, COUNT(*) as count FROM $wpdb->comments WHERE $wpdb->comments.comment_approved = '1' AND $wpdb->comments.comment_type = '' AND $wpdb->comments.comment_author_email != '' GROUP BY comment_author_email ORDER BY comment_ID DESC");

foreach ($commenters as $commenter) {
	$who = $commenter->comment_author_email;
	if (empty($who)) { continue; }
	if ('' != $gravatar_local[$who]) { continue; }

	list ($cached, $updated) = gravatar_check($who, 1);
	if ($updated) {
		$count_updated++;
		if ($cached) { $count_cached++; }
	}
}

update_option('gravatar_expire', $gravatar_expire);

return array ( $count_cached, $count_updated );
} // gravatar_cache_refresh



////////////////////////////////////////////////////////
// get a gravatar & cache it
function gravatar_get_image($who='') {
	if (empty($who)) { return FALSE; }
	global $gravatar_expire;
	$cached = FALSE;
	$now = time();

	$response = gravatar_info(md5($who));
	if ('200' == $response[code]) {
		// it's not an error, so let's make a local copy
		$cached = gravatar_cache_image(md5($who));
		if ($cached) {
			// we copied successfully
			// schedule an update to the expiration
			$gravatar_expire[$who] = "*" . $now;
		} else {
			// there was an error getting the image
			$cached = FALSE;
			$gravatar_expire[$who] = "E" . $now;
		}
	} elseif ('404' == $response[code]) {
		// no image for that email
		$cached = FALSE;
		$gravatar_expire[$who] = "X" . $now;
	} else {
		// some other response or no response
		$cached = FALSE;
		$gravatar_expire[$who] = "E" . $now;
	}
	return $cached;
} // gravatar_get_image



////////////////////////////////////////////////////////
// check gravatar's cache, get new if needed 
function gravatar_check($who='', $forced=0) {
if (empty($who)) { return FALSE; }

global $gravatar_expire, $gravatar_options, $CANCACHE;

if (!isset($CANCACHE)) { $CANCACHE = gravatar_cache_capable(0); }

$cached = FALSE;
$updated = FALSE;
$new = FALSE;

$cache_code = substr($gravatar_expire[$who], 0, 1);
$cache_date = substr($gravatar_expire[$who], 1);

$now = time();

switch ($cache_code) {
	case "X":
		$cached = FALSE;
		$expire_time = $gravatar_options['gravatar_neg_expire_time'];
		break;
	case "E":
		$cached = FALSE;
		$expire_time = $gravatar_options['gravatar_err_expire_time'];
		break;
	case "*":
		$cached = TRUE;
		$expire_time = $gravatar_options['gravatar_pos_expire_time'];
		break;
	default:
		// we don't know about this gravatar yet, let's look for it
		$new = TRUE;
}

	if ( $forced != -1 && ( $new || $forced || (($gravatar_options['gravatar_auto_cache_check']) ))) {
	  // should we check the timestamp??

		if ( $new || ($cache_date < ($now - $expire_time)) ) {
			// it's new or past the expiration time, so grab the latest version
			$response = gravatar_get_image($who);
			if ($response) {
				$cached = TRUE;
				$updated = TRUE;
			} else {
				$cached = FALSE;
				$updated = TRUE;
			}
		}
	}

return array ( $cached, $updated );

} // gravatar_check



////////////////////////////////////////////////////////
// this is simply a wrapper for gravatar_path
function gravatar($who = '', $default = '') {
	echo gravatar_path($who, $default);
}



////////////////////////////////////////////////////////
// outputs (or returns) the full image tag (not just the path)
function gravatar_image($who = '', $default = '', $width = '', $height = '', $alt = 'Gravatar', $title = '', $echo = TRUE) {
global $gravatar_images;

$who = gravatar_check_who($who);

if ($gravatar_images[$who]) {
	if ($echo) {
		echo $gravatar_images[$who];
	}
	return $gravatar_images[$who];
}

$image_path = gravatar_path($who, $default);

$imagetag = "<img src='$image_path' ";
if ($width) { $imagetag .= "width='$width' "; }
if ($height) { $imagetag .= "height='$height' "; }
if ($alt) { $imagetag .= "alt='$alt' "; }
if ($title) { $imagetag .= "title='$title' "; }
$imagetag .= "class='gravatar' />";

$gravatar_images[$who] = $imagetag;
if ($echo) {
	echo $imagetag;
}
return $imagetag;
} // gravatar_image



////////////////////////////////////////////////////////
// outputs (or returns) the full image tag with URL
function gravatar_image_link($who = '', $default = '', $width = '', $height = '', $alt = 'Gravatar', $title = '', $target = '', $echo = TRUE) {

global $comment, $authordata, $gravatar_image_links;
$url = '';
$name = '';

$who = gravatar_check_who($who);

if ($gravatar_image_links[$who]) {
	if ($echo) {
		echo $gravatar_image_links[$who];
	}
	return $gravatar_image_links[$who];
}

$image_path = gravatar_path($who, $default);

list ($cached, $updated) = gravatar_check($who, -1);
// the "gravatar_path" function already checked the cache, so the "-1" in the "gravatar_check" function makes sure we don't check it again

if ($cached) {
	if (empty($comment)) {
		$url = $authordata->user_url;
		$name = $authordata->nickname;
	} else {
		if (('' == $comment->comment_type) || ('comment' == $comment->comment_type)) {
			$url = $comment->comment_author_url;
			$name = $comment->comment_author;
		} elseif (('trackback' == $comment->comment_type) || ('pingback' == $comment->comment_type)) {
			$url_array = parse_url($comment->comment_author_url);
			$url = "http://" . $url_array['host'];
		}           
	}
	if (!empty($url)) {
		if (!empty($name)) {
			$title = "Visit " . $name . "&rsquo;s Website";
		} else {
			$title = "Visit " . $url;
		}
	} else {
		$title = "";
	}
} else {
	$title = "Create your own Gravatar at gravatar.com !";
	$url = "http://www.gravatar.com/";
}

$imagetag = "<img src='$image_path' ";
if ($width) { $imagetag .= "width='$width' "; }
if ($height) { $imagetag .= "height='$height' "; }
if ($alt) { $imagetag .= "alt='$alt' "; }
if ($title) { $imagetag .= "title='$title' "; }
$imagetag .= "class='gravatar' />";

if (!empty($url)) {
	$imagetaglink = "<a href='$url'";
	if ($target) { $imagetaglink .= " target='$target'"; }
	$imagetaglink .= ">$imagetag</a>";
} else {
	$imagetaglink = $imagetag;
}

$gravatar_image_links[$who] = $imagetaglink;

if ($echo) {
	echo $imagetaglink;
}

return $imagetaglink;
} // gravatar_image_link



////////////////////////////////////////////////////////
// backward compatiblity for "wp_gravatar"
function wp_gravatar($who = '', $default = '') {
	return gravatar_path($who, $default);
} // wp_gravatar

?>
