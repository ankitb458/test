<?php
/*
Plugin Name: Rodney's 404 Handler Plugin
Plugin URI: http://www.shupe.ca/articles/wordpress/plugins/404-handler/
Description: This plugin adds functionality to provide enhanced 404 messaging and logging. Adjust what methods of logging are used on the <a href="http://www.shupe.ca/wordpress/wp-admin/options.php?page=rjs-404.php">configuration</a> page.
Author: Rodney Shupe
Author URI: http://www.shupe.ca/
Version: 2.4 (edited)
*/
/*
Revision History
   version 1.0 - Original Release
   version 1.1 - Bug Fixes
   version 2.0 - Changed to using class structure. Also added signature idea
                 from Rich Boakes - http://boakes.org/
   version 2.1 - Fixes to make compatible with Wordpress 2.0
   version 2.2 - Added Logging idea from Matt Read
   version 2.3 - Made plugin identifier functions universal for all plugins.
   version 2.4 - Added functions to stub to identify current available release.
                 Adjusted options page to display.
                 Fixed missing __CLASS__ constant for versions of PHP older
                 than 4.3.0. Fixed issue with PHP5 installs.
                 Fixed infinate recurtion caused by handle404() function name
				 matching class name of Handle404.

Installation:
1) Copy the file rjs-404.php to the WordPress plugins directory
   on your server, (%WORDPRESS PATH%/wp-contents/plugins/).
2) You will then need to activate the plugin from the WordPress
   administration plugins page.
3) Adjust the options as necessary.

Credits:
	Rick Boakes - http://boakes.org/
	His signature idea is a good one.

	Matt Read - http://www.mattread.com/
	Got the idea of storing a log in the wordpress DB from him.

*/
/*  Copyright 2005-2006 Rodney Shupe (email : rodney@shupe.ca)

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
define("RJS_404_HANDLER_DATE_FORMAT", "Y-d-m H:i:s");

if(!class_exists('Handle404')) {
	if(!defined('__CLASS__')) {
		define('__CLASS__', 'Handle404');
	}
	class Handle404 {

		/****** Start Standard Plugin Class Stub ******/
		/*
		   Rodney's Plugin Base Functions version 1.1

		   Every Plugin class should contain the following stub.  It provides a series
		   of functions that can be user to identify elements of the plugin.
		*/
		var $class_obj;

		function version() {
			if(!isset($class_obj)) { $class_name = __CLASS__; $class_obj = new $class_name; } // if initiated statically $this will not work so set $class_obj if not already set
			return $class_obj->_get_meta_info('Version');
		}
		function name() {
			if(!isset($class_obj)) { $class_name = __CLASS__; $class_obj = new $class_name; } // if initiated statically $this will not work so set $class_obj if not already set
			return $class_obj->_get_meta_info('Plugin Name');
		}
		function URI() {
			if(!isset($class_obj)) { $class_name = __CLASS__; $class_obj = new $class_name; } // if initiated statically $this will not work so set $class_obj if not already set
			return $class_obj->_get_meta_info('Plugin URI');
		}
		function source_path() {
			if(!isset($class_obj)) { $class_name = __CLASS__; $class_obj = new $class_name; } // if initiated statically $this will not work so set $class_obj if not already set
			return rtrim($class_obj->_get_meta_info('Author URI'), '/').'/downloads/plugins/'.basename(__FILE__).'s';
		}
		function latest_version() {
			if(!isset($class_obj)) { $class_name = __CLASS__; $class_obj = new $class_name; } // if initiated statically $this will not work so set $class_obj if not already set

			require_once (ABSPATH . WPINC . '/class-snoopy.php');
			$snoopy = new Snoopy;
			$snoopy->fetch($class_obj->source_path());
			$file_data = $snoopy->results;
			return $class_obj->_get_meta_info('Version', '(unknown)', $file_data);
		}
		function _get_meta_info($key, $default = '(unknown)', $file_data = '') {
			if(strlen($file_data) == 0) {
				$file_data = implode('', file(__FILE__));
			}
			if (preg_match("|".$key.":(.*)|i", $file_data, $info)) {
				return trim($info[1]);
			} else {
				return $default;
			}
		}
		function signature() {
			// adds a signature to the end of your page describing the version of plugin, which is rather handy when debugging from afar. - Thanks to Rich Boakes for the idea.
			if(!isset($class_obj)) { $class_name = __CLASS__; $class_obj = new $class_name; } // if initiated statically $this will not work so set $class_obj if not already set
			echo "<div style='display:none;visibility:hidden;'><a href='".$class_obj->URI()."?version=".$class_obj->version()."'>".$class_obj->name()."</a> plugged in.</div>\n";
		}
		/****** End Standard Plugin Class Stub ******/

		function handler() {
			if(!isset($class_obj)) { $class_name = __CLASS__; $class_obj = new $class_name; } // if initiated statically $this will not work so set $class_obj if not already set
			if (is_404() && get_settings('permalink_structure') && !is_admin()) {
				$error_msg = 'File not found: '. $_SERVER['REQUEST_URI'];
				if ($_SERVER['HTTP_REFERER'])
					$error_msg .= ', referer: '.$_SERVER['HTTP_REFERER'];

				if(get_option('rjs_404_send_error_flag') == 'yes') {
					error_log($error_msg, 0);
				}

				if(get_option('rjs_404_send_email_flag') == 'yes') {
					$class_obj->_send_404_email();
				}

				if(get_option('rjs_404_log_error_flag') == 'yes') {
					$class_obj->_add_log_entry($_SERVER["REMOTE_ADDR"].' - '.$error_msg);
				}
				return true;
			} else {
				return false;
			}
		}
		function _send_404_email() {
			if(!isset($class_obj)) { $class_name = __CLASS__; $class_obj = new $class_name; } // if initiated statically $this will not work so set $class_obj if not already set

			$mail_domain = str_replace('www.', '',$_SERVER["SERVER_NAME"]);
			$to = get_bloginfo('admin_email');
			if(strpos($to, '@') === false)
				$to = 'webmaster@'.$mail_domain;
			$from = 'webmaster@'.$mail_domain;

			$subject = 'Error: 404 on '.$_SERVER["SERVER_NAME"];

			$requested_url = 'http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
			$body  = "Requested URL: " . $requested_url . "\r\n";

			if ($_SERVER['HTTP_REFERER']) {
				$referring_url = $_SERVER['HTTP_REFERER'];
				$body .= "Referring URL: " . $referring_url . "\r\n";
			}

			$body .= "Remote Host: " . $_SERVER["REMOTE_HOST"] . " ( ". $_SERVER["REMOTE_ADDR"] .")\r\n";
			$body .= "Whois: http://ws.arin.net/cgi-bin/whois.pl?queryinput=" . $_SERVER["REMOTE_ADDR"] . "\r\n";
			$body .= "User Agent: " . $_SERVER["HTTP_USER_AGENT"] . "\r\n";
			$body .= "Cookie: " . $_SERVER["HTTP_COOKIE"] . "\r\n";

			//Sends the email
			$class_obj->_sendMail($to, $subject, $body, $from);
		}

		//Defines the sendMail() function
		function _sendMail($to, $subject, $body, $from) {
			$from = trim($from);
			$rp    = 'ReturnNonWorkingHere@MySite.net';
			$org    = $mail_domain;
			$mailer = $mail_domain.' Mailer';

			$head  = '';
			$head  .= "Content-Type: text/plain \r\n";
			$head  .= "Date: ". date('r'). " \r\n";
			$head  .= "Return-Path: $rp \r\n";
			$head  .= "From: $from \r\n";
			$head  .= "Sender: $from \r\n";
			$head  .= "Reply-To: $from \r\n";
			$head  .= "Organization: $org \r\n";
			$head  .= "X-Sender: $from \r\n";
			$head  .= "X-Priority: 3 \r\n";
			$head  .= "X-Mailer: $mailer \r\n";
			$body  = str_replace("\r\n", "\n", $body);
			$body  = str_replace("\n", "\r\n", $body);

			return mail($to, $subject, $body, $head);
		}
		function _add_log_entry($msg, $clear_log = false) {
			$msg = '['. date(RJS_404_HANDLER_DATE_FORMAT) .'] '.$msg;

			if ($log = get_option('rjs_404_log')) {
				if($clear_log) {
					$log = array($msg);
				} else {
					$log_size = get_option('rjs_404_log_size');
					if (count($log) >= $log_size)
						array_splice($log, 0, $log_size);
					$log[] = $msg;
				}
				update_option('rjs_404_log', $log);
			} else {
				$msg = '['. date(RJS_404_HANDLER_DATE_FORMAT) .'] Initialized';
				add_option('rjs_404_log', array($msg), 'The most recent 404 errors', 'no');
			}
		}
		function add_pages() {
			// Add a new menu under Options:
			if ( function_exists('add_options_page') )
				add_submenu_page(
						'edit.php',
						__('404&nbsp;Handler'),
						__('404&nbsp;Handler'),
						7,
						str_replace("\\", "/", __FILE__),
						array('Handle404', 'options_page')
						);
		}
		function options_page() {
			if(!isset($class_obj)) { $class_name = __CLASS__; $class_obj = new $class_name; } // if initiated statically $this will not work so set $class_obj if not already set

			$version = $class_obj->version();
			$latest_version = $version;
			if (isset($_POST['info_update'])) {
				$bSuccess = false;
				if(isset($_POST['rjs_404_log_size']) && is_numeric($_POST['rjs_404_log_size'])) {
					$class_obj->_update_options_flag('rjs_404_send_email_flag');
					$class_obj->_update_options_flag('rjs_404_send_error_flag');
					$class_obj->_update_options_flag('rjs_404_log_error_flag');
					update_option('rjs_404_log_size', $_POST['rjs_404_log_size']);
					$bSuccess = true;
				}
				if($bSuccess) {
					echo '<div id="message" class="updated fade"><p><strong>'.__('Successfully updated.', 'Localization name')."</strong></p></div>\n";
				} else {
					echo '<div id="message" class="error fade"><p><strong>'.__('Problem updating.', 'Localization name')."</strong></p></div>\n";
				}
			} elseif (isset($_POST['clear_log'])) {
				$class_obj->_add_log_entry('Log Cleared', true);
				echo '<div id="message" class="updated fade"><p><strong>'.__('Log Cleared.', 'Localization name')."</strong></p></div>\n";
			} else {
/*
				$latest_version = $class_obj->latest_version();
				if($latest_version > $version) {
					echo '<div id="message" class="updated fade"><p><strong>'.__('New Version Available', 'Localization name').' ('.__('go', 'Localization name').' <a href="'.$class_obj->URI().'">'.__('here', 'Localization name').'</a> '.__('to download version', 'Localization name').' '.$latest_version.')'."</strong></p></div>\n";
				}elseif($latest_version < $version) {
					echo '<div id="message" class="updated fade"><p><strong>You are running a development version the current release version is '.$latest_version."</strong></p></div>\n";
				}
*/
			}
?>
			<div class=wrap>
				<form method="post">
					<h2><?php echo $class_obj->name(); ?> version <?php echo $version; ?> Options</h2>
					<fieldset class="options">
						<legend><?php _e('Enable', 'Localization name') ?></legend>
						<ul>
							<li><?php $class_obj->_display_options_flag('rjs_404_send_email_flag', 'Send Email to Admin on 404'); ?></li>
							<li><?php $class_obj->_display_options_flag('rjs_404_send_error_flag', 'Send 404 error to PHP system log'); ?></li>
							<li>
								<?php $class_obj->_display_options_flag('rjs_404_log_error_flag', 'Log 404 error'); ?>
								<label for="rjs_404_log_size">Log Length:
								<input name="rjs_404_log_size" type="text" id="rjs_404_log_size" value="<?php echo get_option('rjs_404_log_size') ?>" /><br />
								</label>
							</li>
						</ul>
					</fieldset>
					<fieldset class="options">
						<legend><?php _e('Log', 'Localization name') ?></legend>
						<label for="rjs_404_log">
							<?php _e('The following is the 404 Error Log:<br />', 'Localization name' ) ?>
						</label>
						<textarea name="rjs_404_log" id="rjs_404_log" rows="10" style="width: 98%;" readonly>
<?php
							wp_cache_delete('rjs_404_log', 'options');
							if ($log = get_option('rjs_404_log')) {
								foreach(array_reverse($log) as $log_line) {
									echo $log_line."\n";
								}
							}
?>
						</textarea>
						<input type="submit" name="clear_log" value="<?php _e('Clear Log', 'Localization name');?>" />
					</fieldset>
					<div class="submit">
						<input type="submit" name="info_update" value="<?php _e('Update options', 'Localization name');?>" />
					</div>
				</form>
			</div>
<?php
		}
		function _update_options_flag($flag_name) {
			if(isset($_POST[$flag_name])) {
				update_option($flag_name, 'yes');
			} else {
				update_option($flag_name, 'no');
			}
		}
		function _display_options_flag($flag_name,$flag_description) {
			echo '<label for="'.$flag_name.'">';
			echo '<input name="'.$flag_name.'" type="checkbox" id="'.$flag_name.'" value="1"';
			if(get_option($flag_name) == 'yes') echo ' checked="checked"';
			echo ' />'."\n";
			_e($flag_description, 'Localization name' );
			echo '</label>';
		}
	} // End Class BreadCrumb
}
if (!function_exists('wp_cache_delete')) {
	function wp_cache_delete() { return false; }
}

add_option('rjs_404_send_email_flag', 'no', 'Send email to admin on 404 error');
add_option('rjs_404_send_error_flag', 'no', 'Send 404 error to PHP system log');
add_option('rjs_404_log_error_flag', 'yes', 'Log 404 error');
add_option('rjs_404_log_size', '100', 'Log file size.');
add_option('rjs_404_log', array('['. date(RJS_404_HANDLER_DATE_FORMAT) .'] Initialized'), 'The most recent 404 errors', 'no');

add_action('shutdown', array('Handle404', 'handler'));
//add_action('wp_footer', array('Handle404', 'handle404'), 10000);
add_action('wp_footer', array('Handle404', 'signature'), 999);
add_action('admin_menu', array('Handle404', 'add_pages'));
?>