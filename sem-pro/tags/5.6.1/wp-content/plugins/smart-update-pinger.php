<?php
/*
	Ultimate Plugins Smart Update Pinger
	Description: Replaces the built-in ping/notify functionality. Pings only when publishing new or future posts, not when editing. The new post's url is pinged, not the main url. 
	Also includes reverse order logfile (http://ultimateplugins.com/wordpress/smart-update-pinger/)
	 GNU General Public License: This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or (at your option) any later version. This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details. You should have received a copy of the GNU General Public License along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

# Log
$logfile = ABSPATH . 'wp-content/smart-update-pinger.log';

function SUP_init() 
{
	global $logfile;
	
	# adds a filter to receive the title of the post before publishing
	add_filter("title_save_pre", "SUP_post_title");

	# adds some hooks

	# shows the options in the administration panel
	add_action("admin_menu", "SUP_add_options_page");
	# calls SUP_ping whenever a post is published
	add_action("publish_post", "SUP_ping_if_new");
	# calls SUP_ping_draft when changing the status from private/draft to published
	# add_action("private_to_published', 'SUP_ping_draft');

	# removes the "WordPress official" pinging hook
	remove_action("publish_post", "generic_ping");

	# create a new log on init
	if (!is_file($logfile))
		SUP_log(" ");
	
	# activates pinging if setting doesn't exist in database yet
	# (before the user has changed the settings the first time)
	if(get_option("SUP_ping") === false)
	{
	   update_option("SUP_ping", 1);
	}

	if(get_option("SUP_pinglog") === false)
	{
	   update_option("SUP_pinglog", 1);
	}

	if(get_option("SUP_error") === false)
	{
	   update_option("SUP_error", 0);
	}
}

add_action('init', 'SUP_init');

# adds an options page to the options menu
function SUP_add_options_page()
{
	if(function_exists("add_options_page"))
		add_options_page("Smart Update Pinger", "Smart Update Pinger", 5, basename(__FILE__), "SUP_show_options_page");
}

# shows the options page
function SUP_show_options_page()
{
	global $logfile;
	$ping = get_option("SUP_ping");
	$pinglog = get_option("SUP_pinglog");
	$uris = get_option("ping_sites");
	$forcedpings = false; 
	$SUP_output_log='';

	$pingservicesnow = "Ping Services Now!";
	$deletelogfile   = "Delete Log File";

	if(isset($_POST["ping"]) && $_POST["ping"] == $pingservicesnow)
	{
		$forcedpings = true;
		SUP_log(SUP_ping_services($forcedpings).strftime("%D %T")."\t<strong>Forced pinging services (Homepage)</strong>\n\t&#9472;&#9472;&#9472;&#9472;&#9472;\n");
	}
	elseif(isset($_POST["submit"]))
	{
		$uris = $_POST["uris"];

		$ping = 0;
		if($_POST["ping"] == 1) 
			$ping = 1;

		$pinglog = 0;
		if($_POST["pinglog"] == 1) 
			$pinglog = 1;

		update_option("SUP_ping", $ping);
		update_option("SUP_pinglog", $pinglog);
		update_option("ping_sites", $uris);

		echo '<div class="updated"><p><strong>Options saved.</strong></p></div>';
	}
	elseif(isset($_POST["delete"]) && $_POST["delete"] == $deletelogfile)
	{
		$fh = @fopen($logfile, "w+");
		if(false === @fwrite($fh, strftime("%D %T")."\t<strong><font color=\"#FF0000\">Log file deleted</font></strong>\n\t&#9472;&#9472;&#9472;&#9472;&#9472;\n"))
		{
			update_option("SUP_error", 1);
		}
		else
		{
			update_option("SUP_error", 0);
		}
		@fclose($fh);
	}

	$checked1 = '';
	if($ping == 1)
		$checked1 = 'checked="checked"';

	$checked2 = '';
	if($pinglog == 1)
		$checked2 = 'checked="checked"';

	echo '<div class="wrap">
	<h2>Ultimate Plugins Smart Update Pinger</h2>
	<p><strong>URIs to Ping</strong></p>
	<p>The following services will automatically be pinged/notified when you publish <strong>normal or future timestamped</strong> posts. <strong>Not</strong> when you edit previously published posts, as WordPress does by default.</p>
	<p>This plugin also fixes an issue with the default extended ping programming in Wordpress and pre-2.1 versions of Smart Update Pinger (it now includes the url of the new post).
	<p><strong>NB:</strong> this list is synchronized with the <a href="options-writing.php">original update services list</a>.</p>
	<form method="post">
	<p>Separate multiple service URIs with line breaks:<br />
	<textarea name="uris" cols="60" rows="10">'.$uris.'</textarea></p>
	<p><input type="checkbox" id="ping_checkbox" name="ping" value="1" '.$checked1.'" /> <label for="ping_checkbox">Enable pinging</label></p>
	<p><input type="checkbox" id="pinglog_checkbox" name="pinglog" value="1" '.$checked2.'" /> <label for="pinglog_checkbox">Detailed Logging <font color=\"#FF0000\">(Uses much disk space - use for debugging only!)</font></label></p>
	<p class="submit">
		<input type="submit" name="delete" value="'.$deletelogfile.'" onclick="return confirm(\'Are you sure you want to delete the log file?\');" />
	   <input type="submit" name="ping" value="'.$pingservicesnow.'" onclick="return confirm(\'Are you sure you want to ping these services now? Pinging too often could get you banned for spamming, you know.\');" />
		<input type="submit" name="submit" value="Update Options" />
	</p></form>
	<h2>Ping log</h2>
	<p>These are the last 100 actions performed by the plugin. In reverse chronological order for easier reading (latest ping first).</p>
	<p><code>';
	SUP_get_last_log_entries(500);
	echo '</code></p></div>';
}

# telling WordPress to ping if the post is new, but not if it's just been edited
function SUP_ping_if_new($id)
{
	global $wpdb;
	global $post_title;
	$SUP_output_log="\t&#9472;&#9472;&#9472;&#9472;&#9472;\n";
	$SUP_ping_result=''; 
	$forcedpings = false;

	if(get_option('SUP_ping') == 1 and get_option('ping_sites') != "")
	{
		# fetches data directly from database; the function "get_post" is cached,
		# and using it here will get the post as is was before the last save
		$row = mysql_fetch_array(mysql_query(
		"SELECT post_date,post_modified,post_title,guid
			FROM $wpdb->posts
			WHERE id=$id"));

		# if time when created equals time when modified it is a new post,
		# otherwise the author has edited/modified it
		if(!$row["post_title"])
		{
			$SUP_output_log=strftime("%D %T")."\t<strong>NOT Pinging services (<font color=\"#FF0000\">ERROR: YOU HAVE FORGOTTEN TO ENTER A POST TITLE</font>) ...</strong>\n".$SUP_output_log;
		}
		else
		{
			if($row["post_date"] == $row["post_modified"])
			{
				$SUP_output_log=strftime("%D %T")."\t<strong>Pinging services (New <font color=\"#800080\">normal</font> post: &ldquo;".$row["post_title"]."&rdquo;) ...</strong>\n".$SUP_output_log;

				$SUP_output_log=SUP_ping_services($forcedpings,$row["guid"]).$SUP_output_log;
				# Try commenting the line above, and uncommenting this line below if pinging seems to be out of order. Please notify the author if it helps!
				# generic_ping();
			}
			else
			{
				// Post has been edited or it's a future post
				// If we have a post title it means that we are in the normal WP loop and therefore it was an edit (not a future post)
				if($post_title)
				{
					$SUP_output_log=strftime("%D %T")."\t<strong>NOT Pinging services (Existing post was edited: &ldquo;".$row["post_title"]."&rdquo;) ...</strong>\n".$SUP_output_log;
				}
				else
				{
					$SUP_output_log=strftime("%D %T")."\t<strong>Pinging services (New <font color=\"#800080\">timestamped</font> post: &ldquo;".$row["post_title"]."&rdquo;) ...</strong>\n".$SUP_output_log;
					$SUP_output_log=SUP_ping_services($forcedpings,$row["guid"]).$SUP_output_log;
					# Try commenting the line above, and uncommenting this line below if pinging seems to be out of order. Please notify the author if it helps!
					# generic_ping();
				}
			}
		}
	}
	else
	{
		$SUP_output_log=strftime("%D %T")."\t<strong>NOT Pinging services (<font color=\"#FF0000\">WARNING: DISABLED BY ADMINISTRATOR</font>)</strong>\n".$SUP_output_log;
	}
	SUP_log($SUP_output_log);
}

# More or less a copy of WP's "generic_ping" from functions.php,
# but uses another function to send the actual XML-RPC messages.
function SUP_ping_services($forcedpings,$SUP_guid = '')
{
	$SUP_output_log='';
	#$services = get_settings('ping_sites');
	#UP - 17.07.07 - get_option is newer/better then get_settings
	$services = get_option('ping_sites');
	$services = preg_replace("|(\s)+|", '$1', $services); // Kill dupe lines
	$services = trim($services);
	if ( '' != $services )
	{
		$services = explode("\n", $services);
		foreach ($services as $service)
			$SUP_output_log=SUP_send_xmlrpc($forcedpings,$SUP_guid,$service).$SUP_output_log;
	}
	return $SUP_output_log;
}

# A slightly modified version of the WordPress built-in ping functionality ("weblog_ping" in functions.php).
# Original version:
#function weblog_ping($server = '', $path = '') {
#global $wp_version;
#include_once(ABSPATH . WPINC . '/class-IXR.php');
#// using a timeout of 3 seconds should be enough to cover slow servers
#$client = new IXR_Client($server, ((!strlen(trim($path)) || ('/' == $path)) ? false : $path));
#$client->timeout = 3;
#$client->useragent .= ' -- WordPress/'.$wp_version;
#// when set to true, this outputs debug messages by itself
#$client->debug = false;
#$home = trailingslashit( get_option('home') );
#if ( !$client->query('weblogUpdates.extendedPing', get_option('blogname'), $home, get_bloginfo('rss2_url') ) ) // then try a normal ping
#$client->query('weblogUpdates.ping', get_option('blogname'), $home);
#}
# This one uses correct extendedPing format (WP does not), and logs response from service.
function SUP_send_xmlrpc($forcedpings,$SUP_guid = '',$server = '', $path = '')
{
	global $wp_version;
	$SUP_output_log='';
	include_once (ABSPATH . WPINC . '/class-IXR.php');

	// using a timeout of 5 seconds should be enough to cover slow servers (changed from 3 to 5)
	$client = new IXR_Client($server, ((!strlen(trim($path)) || ('/' == $path)) ? false : $path));
	$client->timeout = 5;
	$client->useragent .= ' -- WordPress/'.$wp_version;

	// when set to true, this outputs debug messages by itself
	$client->debug = false;
	$home = trailingslashit( get_option('home') );
	# The extendedPing format should be "blog name", "blog url", "check url" (the new URL), and "feed url".
	# Related Website(s)
	# http://www.weblogs.com/api.html
	# An example:
	# <value>Someblog</value> - Title
	# <value>http://spaces.msn.com/someblog</value> - Home URL
	# <value>http://spaces.msn.com/someblog/PersonalSpace.aspx?something</value> - Check/New URL
	# <value>http://spaces.msn.com/someblog/feed.rss</value> - Feed
	# Changed the following line therefore:
	# if($client->query('weblogUpdates.extendedPing', get_settings('blogname'), $home, get_bloginfo('rss2_url'), get_bloginfo('rss2_url')))
	if ($forcedpings)
	{
		# If this is a forced ping it's better to use a regular ping for the homepage without an update URL (safer)
		if($client->query('weblogUpdates.ping', get_option('blogname'), $home))
		{
			$SUP_output_log=strftime("%D %T")."\t<font color=\"#33CC33\">&#9658; [Regular Ping] ".$server." was successfully pinged</font>\n".$SUP_output_log;
			if (get_option('SUP_pinglog') == 1)
			{
				$SUP_output_log=strftime("%D %T")."\t&#9658;&#9658; Blogname: '".get_option('blogname')."'\n".$SUP_output_log;
				$SUP_output_log=strftime("%D %T")."\t&#9658;&#9658; Homepage: '".$home."'\n".$SUP_output_log;
			}
		}
		else
		{
			$SUP_output_log=strftime("%D %T")."\t<font color=\"#FF0000\">&#9658; ".$server." could not be pinged. Error message: &ldquo;".$client->error->message."&rdquo;</font>\n".$SUP_output_log;
		}
	}
	else
	{
		if($client->query('weblogUpdates.extendedPing', get_option('blogname'), $home, $SUP_guid, get_bloginfo('rss2_url')))
		{
			$SUP_output_log=strftime("%D %T")."\t<font color=\"#33CC33\">&#9658; [Extended Ping] ".$server." was successfully pinged</font>\n".$SUP_output_log;

			if (get_option('SUP_pinglog') == 1)
			{
				$SUP_output_log=strftime("%D %T")."\t&#9658;&#9658; Blogname: '".get_option('blogname')."'\n".$SUP_output_log;
				$SUP_output_log=strftime("%D %T")."\t&#9658;&#9658; Homepage: '".$home."'\n".$SUP_output_log;
				$SUP_output_log=strftime("%D %T")."\t&#9658;&#9658; Updated : '".$SUP_guid."'\n".$SUP_output_log;
				$SUP_output_log=strftime("%D %T")."\t&#9658;&#9658; RSS URL : '".get_bloginfo('rss2_url')."'\n".$SUP_output_log;
			}
		}
		else
		{
			# pinging was unsuccessful, trying regular ping format
			if($client->query('weblogUpdates.ping', get_option('blogname'), $home))
			{
				$SUP_output_log=strftime("%D %T")."\t<font color=\"#33CC33\">&#9658; [Regular Ping] ".$server." was successfully pinged</font>\n".$SUP_output_log;
				if (get_option('SUP_pinglog') == 1)
				{
					$SUP_output_log=strftime("%D %T")."\t&#9658;&#9658; Blogname: '".get_option('blogname')."'\n".$SUP_output_log;
					$SUP_output_log=strftime("%D %T")."\t&#9658;&#9658; Homepage: '".$home."'\n".$SUP_output_log;
				}
			}
			else
			{
				$SUP_output_log=strftime("%D %T")."\t<font color=\"#FF0000\">&#9658; ".$server." could not be pinged. Error message: &ldquo;".$client->error->message."&rdquo;</font>\n".$SUP_output_log;
			}
		}
   }
   return $SUP_output_log;
}

$post_title = "";
# Receives the title of the post from a filter below
function SUP_post_title($title)
{
	global $post_title;
	$post_title = $title;
	return $title;
}

function SUP_log($SUP_log_output)
{
	global $logfile;
	$logerror = 0;
	$fh = @fopen($logfile, "a+");
	if(false === @fwrite($fh, $SUP_log_output))
	{
		update_option("SUP_error", 1);
	}
	else
	{
		update_option("SUP_error", 0);
	}
	@fclose($fh);
}

function SUP_get_last_log_entries($num)
{
	global $logfile;
	$lines = @file($logfile);
	if(get_option("SUP_error") == 1)
	{
		if ($fh = @fopen($logfile, "a+"))
		{
			if(false === @fwrite($fh, ""))
			{
				echo "Error writing log file (".$logfile."). <strong>Most likely your logfile (".$logfile.") is write-protected and no log data can be saved (change the rights of this file to 777)</strong>, or alternatively this could mean that you have manually removed the log file, or that you have changed the directory or file name of the plugin (they both should be 'ultimate-plugins-smart-update-pinger')";
			}
			else
			{
				// Original: $lines = array_slice($lines, count($lines) - $num);
				// Modified to show in reverse order (easier for reading)
				$lines = array_reverse(array_slice($lines, count($lines) - $num));
				$msg = "";
				foreach($lines as $line)
				{
					$msg.=trim($line)."<br />";
				}
				echo $msg;
			}
			@fclose($fh);
		}
	}
	else
	{
		if($lines === false)
		{
			echo "Error reading log file (".$logfile."). <strong>Most likely you have manually removed the log file</strong>, or alternatively this could mean that the logfile (".$logfile.") is read-protected (change the rights of this file to 777), or that you have changed the directory or file name of the plugin (they both should be 'smart-update-pinger')";
		}
		else
		{
			// Original: $lines = array_slice($lines, count($lines) - $num);
			// Modified to show in reverse order (easier for reading)
			$lines = array_reverse(array_slice($lines, count($lines) - $num));
			$msg = "";
			foreach($lines as $line)
			{
				$msg.=trim($line)."<br />";
			}
			echo $msg;
		}
	}
}
?>