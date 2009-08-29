<?php

/*
	Plugin Name: Smart Update Pinger
	Plugin URI: http://www.daven.se/usefulstuff/wordpress-plugins.html
	Description: Replaces the built-in ping/notify functionality. Pings only when publishing new posts, not when editing.
	Author: Christian Davén
	Version: 2.2 (fork)
	Author URI: http://www.daven.se/usefulstuff/
*/

include_once dirname(__FILE__) . '/wp-cron/wp-cron.php';

function get_sup_option($option)
{
	if ( function_exists('get_site_option') )
	{
		return get_site_option($option);
	}
	else
	{
		return get_option($option);
	}
}

function update_sup_option($option, $value)
{
	if ( function_exists('get_site_option') )
	{
		return update_site_option($option, $value);
	}
	else
	{
		return update_option($option, $value);
	}
}


# adds an options page to the options menu
function SUP_add_options_page()
{
		if ( !function_exists('is_site_admin') || is_site_admin() )
			if(function_exists("add_options_page"))
				add_options_page("Smart Update Pinger", "Smart Update Pinger", 5, basename(__FILE__), "SUP_show_options_page");
}

# shows the options page
function SUP_show_options_page()
{
	#$ping = get_sup_option("SUP_ping");
	$uris = get_sup_option("ping_sites");
	if ( $uris === false && function_exists('get_site_option') )
	{
		$uris = (string) get_option('ping_sites');
		update_option('ping_sites', $uris);
	}

	$pingservicesnow = "Ping Services Now!";

	if(isset($_POST["ping"]) && $_POST["ping"] == $pingservicesnow)
	{
		SUP_log("Forcing pings ...");
		update_option('do_smart_ping', true);
		do_SUP_ping_services();
	}
	elseif(isset($_POST["submit"]))
	{
		$uris = stripslashes($_POST["uris"]);

		$ping = 0;
		if($_POST["ping"] == 1)
			$ping = 1;

		#update_sup_option("SUP_ping", $ping);
		update_sup_option("ping_sites", $uris);

		echo '<div class="updated"><p><strong>Options saved.</strong></p></div>';
	}

	$checked = '';
	if($ping == 1)
		$checked = 'checked="checked"';

	echo

	'<div class="wrap">
	<h2>URIs to Ping</h2>
	<p>The following services will automatically be pinged/notified when you publish posts. <strong>Not</strong> when you edit previously published posts, as WordPress does by default.</p>
	<p><strong>NB:</strong> this list is synchronized with the <a href="options-writing.php">original update services list</a>.</p>
	<form method="post">
	<p>Separate multiple service URIs with line breaks:<br />
	<textarea name="uris" cols="60" rows="10">'.$uris.'</textarea></p>
	<!--<p><input type="checkbox" id="ping_checkbox" name="ping" value="1" '.$checked.'" /> <label for="ping_checkbox">Enable pinging</label></p>-->
	<p class="submit">
		<input type="submit" name="ping" value="'.$pingservicesnow.'" onclick="return confirm(\'Are you sure you want to ping these services now? Pinging too often could get you banned for spamming, you know.\');" />
		<input type="submit" name="submit" value="Update Options" />
	</p></form>
	<h2>Ping log</h2>
	<p>These are the lastest actions performed by the plugin.</p>
	<p><code>'.SUP_get_last_log_entries(20).'</code></p>
	</div>';
}

# telling WordPress to ping if the post is new, but not if it's just been edited
function SUP_ping_if_new($id)
{
	global $wpdb, $post_title;

	if(
	#get_sup_option('SUP_ping') == 1
	#and
	get_sup_option('ping_sites') != "")
	{
		# fetches data directly from database; the function "get_post" is cached,
		# and using it here will get the post as is was before the last save
		$row = mysql_fetch_array(mysql_query(
			"SELECT post_date,post_modified
			FROM $wpdb->posts
			WHERE id=$id"));

		# if time when created equals time when modified it is a new post,
		# otherwise the author has edited/modified it
		if($row["post_date"] == $row["post_modified"])
		{
			if($post_title)
				SUP_log("Scheduling ping (new post: &ldquo;".$post_title."&rdquo;) ...");
			else
				SUP_log("Scheduling ping (new post) ...");

			SUP_ping_services();
			# Try commenting the line above, and uncommenting this line below
			# if pinging seems to be out of order. Please notify the author if it helps!
			# generic_ping();
		}
		else
		{
			if($post_title)
				SUP_log("NOT pinging services (&ldquo;".$post_title."&rdquo; was edited)");
			else
				SUP_log("NOT pinging services (a post was edited)");
		}
	}
	else
		SUP_log("NOT pinging services (disabled by administrator)");
}

# More or less a copy of WP's "generic_ping" from functions.php,
# but uses another function to send the actual XML-RPC messages.
function SUP_ping_services()
{
	update_option('do_smart_ping', true);
}

function do_SUP_ping_services()
{
	if ( get_option('do_smart_ping') )
	{
		$services = get_sup_option('ping_sites');
		$services = preg_replace("|(\s)+|", '$1', $services); // Kill dupe lines
		$services = trim($services);
		if ( '' != $services )
		{
			$services = explode("\n", $services);
			foreach ($services as $service)
				SUP_send_xmlrpc($service);
		}

		update_option('do_smart_ping', false);
	}
}

# A slightly modified version of the WordPress built-in ping functionality ("weblog_ping" in functions.php).
# This one uses correct extendedPing format (WP does not), and logs response from service.
function SUP_send_xmlrpc($server = '', $path = '')
{
	global $wp_version;
	include_once (ABSPATH . WPINC . '/class-IXR.php');

	// using a timeout of 3 seconds should be enough to cover slow servers
	$client = new IXR_Client($server, ((!strlen(trim($path)) || ('/' == $path)) ? false : $path));
	$client->timeout = 3;
	$client->useragent .= ' -- WordPress/'.$wp_version;

	// when set to true, this outputs debug messages by itself
	$client->debug = false;
	$home = trailingslashit( get_option('home') );
	# the extendedPing format should be "blog name", "blog url", "check url" (whatever that is), and "feed url",
	# but it would seem as if the standard has been mixed up. it's therefore best to repeat the feed url.
	if($client->query('weblogUpdates.extendedPing', get_settings('blogname'), $home, get_bloginfo('rss2_url'), get_bloginfo('rss2_url')))
	{
		SUP_log("- ".$server." was successfully pinged (extended format)");
	}
	else
	{
		# pinging was unsuccessful, trying regular ping format
		if($client->query('weblogUpdates.ping', get_settings('blogname'), $home))
		{
			SUP_log("- ".$server." was successfully pinged");
		}
		else
		{
			SUP_log("- ".$server." could not be pinged. Error message: &ldquo;".$client->error->message."&rdquo;");
		}
	}
}

$post_title = "";
# Receives the title of the post from a filter below
function SUP_post_title($title)
{
	global $post_title;
	$post_title = $title;
	return $title;
}


# -----
# Log stuff

$logfile = ABSPATH."wp-content/smart-update-pinger.log";

# for debugging
function SUP_log($line)
{
	global $logfile;
	$fh = @fopen($logfile, "a");
	@fwrite($fh, strftime("%D %T")."\t$line\n");
	@fclose($fh);
}

function SUP_get_last_log_entries($num)
{
	global $logfile;
	$lines = @file($logfile);
	if($lines === false)
		return "Error reading log file (".$logfile."). This could mean that the wp-content directory is write-protected and no log data can be saved, that you have manually removed the log file, or that you have recently upgraded the plugin.";
	else
	{
		$lines = array_slice($lines, count($lines) - $num);
		$msg = "";
		foreach($lines as $line)
			$msg .= trim($line)."<br />";

		return $msg;
	}
}

# -----

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


# activates pinging if setting doesn't exist in database yet
# (before the user has changed the settings the first time)
#if(get_sup_option("SUP_ping") === false)
#{
#	update_sup_option("SUP_ping", 1);
#}

add_action('wp_cron_hourly', 'do_SUP_ping_services');
?>