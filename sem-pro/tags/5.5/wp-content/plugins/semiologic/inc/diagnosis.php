<?php
/*
Semiologic Pro diagnosis
(c) Denis de Bernardy and Mike Koepke

Terms of use
------------
This software GPL Licensed

This plugin was adapted from the Diagnosis plugin, version 1.4 by Niklas Lindblad (http://nlindblad.org/)
The original plugin can be found at:  http://nlindblad.org/index.php/projects/wordpress-plugins/diagnosis
*/


function make_table_row ($description, $help, $value)
{
	/* Make a row for our table */
	print("## $description:\n$help\n\n- $value\n\n\n");
}

function make_headline ($text)
{
	print("\n\n# ".$text."\n\n\n");
}

function get_php_loaded_extensions ()
{
	$loaded_extensions = get_loaded_extensions();

	$imploded_array = implode(", ", $loaded_extensions);

	return($imploded_array);

}

function get_php_configuration_string ($value)
{
	$setting = ini_get($value);

	return $setting;
}

function get_php_configuration_boolean ($value, $yesno = false)
{
	$setting = ini_get($value);

	return convert_boolean_text($setting, $yesno);
}

function convert_boolean_text($setting, $yesno = false)
{
	$boolean =  (bool) $setting;

	if ( $boolean == true )
	{
		return(!$yesno ? "On" : "Yes");
	}

	elseif ( $boolean == false )
	{
		return(!$yesno ? "Off" : "No");
	}
	else
	{
		return("Not set");
	}
}

function get_mysql_variable ($variable)
{
	/* Call the Wordpress database object */
        global $wpdb;

	/* In order to get the information we want we must ask the database object
	   to make a query for us and return the result as an associative array (ARRAY_A)
	   after that we simply return the value of it, using the key 'Value'.
        */

	$result = $wpdb->get_row("SHOW VARIABLES LIKE '$variable';", ARRAY_A); /* Result is saved in the associative array called $result */

	return $result['Value'];
}

function get_mysql_status ($variable)
{

	/* Call the Wordpress database object */
    global $wpdb;

	/* In order to get the information we want we must ask the database object
	   to make a query for us and return the result as an associative array (ARRAY_A)
	   after that we simply return the value of it, using the key 'Value'.
        */

    $result = $wpdb->get_row("SHOW STATUS LIKE '$variable';", ARRAY_A); /* Result is saved in the associative array called $result */

	return $result['Value'];
}

function get_mysql_statistics ($variable, $timeunit)
{
	$amount = get_mysql_status($variable);

	$uptime_seconds = get_mysql_status("Uptime");

	switch ($timeunit)
	{
		case "seconds":

			$result = $amount / $uptime_seconds;
			break;

		case "minutes":
			$uptime = $uptime_seconds / 60;

			$result = $amount / $uptime;
			break;

		case "hours":
			$uptime = $uptime_seconds / 3600;

   			$result = $amount / $uptime;
			break;
	}

	/* We round it down to 8 decimals */

	return round($result, 8);
}

function get_wordpress_option($value)
{
	$setting = get_option($value);

	return $setting;
}

function get_wordpress_option_boolean($value, $yesno = false)
{
	$setting = get_option($value);

	return convert_boolean_text($setting, $yesno);
}

function get_wordpress_active_plugins ()
{
	$active_plugins = get_option('active_plugins');

	$imploded_array = implode("\n- ", $active_plugins);

	return($imploded_array);

}

function get_semiologic_width()
{
	global $sem_options;

	$widths = array(
		'narrow' => array(
			'name' => __('Narrow'),
			'width' => '770px'
			),
		'wide' => array(
			'name' => __('Wide'),
			'width' => '970px'
			),
		'flex' => array(
			'name' => __('Flexible'),
			'width' => '100%'
			)
		);

	$active_width = $sem_options['active_width'];

	return( $widths[$active_width]['name'] . " - " . $widths[$active_width]['width']);
}

function get_semiologic_layout()
{
	global $sem_options;

	$layouts = array(
		'essm' => array(
			'name' => __('Ext Sidebar, Sidebar, Sidebar, Main')
			),
		'esms' => array(
			'name' => __('Ext Sidebar, Sidebar, Main, Sidebar')
			),
		'emss' => array(
			'name' => __('Ext Sidebar, Main, Sidebar, Sidebar')
			),
		'ssme' => array(
			'name' => __('Sidebar, Sidebar, Main, Ext Sidebar')
			),
		'smse' => array(
			'name' => __('Sidebar, Main, Sidebar, Ext Sidebar')
			),
		'msse' => array(
			'name' => __('Main, Sidebar, Sidebar, Ext Sidebar')
			),
		'ssm' => array(
			'name' => __('Sidebar, Sidebar, Main')
			),
		'sms' => array(
			'name' => __('Sidebar, Main, Sidebar')
			),
		'mss' => array(
			'name' => __('Main, Sidebar, Sidebar')
			),
		'esm' => array(
			'name' => __('Ext Sidebar, Sidebar, Main')
			),
		'ems' => array(
			'name' => __('Ext Sidebar, Main, Sidebar')
			),
		'sme' => array(
			'name' => __('Sidebar, Main, Ext Sidebar')
			),
		'mse' => array(
			'name' => __('Main, Sidebar, Ext Sidebar')
			),
		'em' => array(
			'name' => __('Ext Sidebar, Main')
			),
		'me' => array(
			'name' => __('Main, Ext Sidebar')
			),
		'sm' => array(
			'name' => __('Sidebar, Main')
			),
		'ms' => array(
			'name' => __('Main, Sidebar')
			),
		'm' => array(
			'name' => __('Main')
			)
		);
	$active_layout = $sem_options['active_layout'];

	return( $layouts[$active_layout]['name']);
}

function present_wordpress_information()
{
	$categoryBase = get_wordpress_option('category_base');
	if (!$categoryBase)
		$categoryBase = "None";
	$tagBase = get_wordpress_option('tag_base');
	if (!$tagBase)
		$tagBase = "None";


	/* XHTML */
	/* Make a table header with descriptive information about each field we are outputting */
	make_headline("WordPress Information");
	$columns = array("Variable", "Value", "Explanation");

	/* Try to display the current Wordpress version */
	global $wp_version;

	$ct = current_theme_info();

	make_table_row("WordPress version", "The version of the current WordPress installation.", $wp_version);

	make_table_row("WordPress theme", "The current activated WordPress theme.", $ct->title);

	make_table_row("Theme version", "The version of the current WordPress theme.", $ct->version);

	make_table_row("WordPress address (URL)", "The current WordPress URL.", get_wordpress_option('siteurl'));

	make_table_row("Blog address (URL)", "The current Blog URL.", get_wordpress_option('home'));

	make_table_row("Blog absolute file path", "The current phyiscal file path for the blog.", ABSPATH);

	make_table_row("Language setting", "The configured language setting for the blog.", WPLANG);

	make_table_row("XHTML correction", "Whether WordPress should correct invalidly nested XHTML automatically or not.", get_wordpress_option_boolean('use_balanceTags'));

	make_table_row("Gzip compression", "Whether WordPress should compress articles (gzip) if browsers ask for them or not. This setting should be Off.", get_wordpress_option_boolean('gzipcompression'));

	make_table_row("Blog visibility", "Whether the blog is visible to search engines or not.", (get_wordpress_option('blog_public') ? "Public" : "Hidden"));

	make_table_row("Permalink structure", "The current WordPress permalink structure.", get_wordpress_option('permalink_structure'));

	make_table_row("Category URL base", "The current prefix used for category URLs.", $categoryBase);

	make_table_row("Tag URL base", "The current prefix used for tag URLs.", $tagBase);

	if (function_exists(wp_cache_is_enabled))
	{
		$wpCache = "Not defined";
		if (defined ('WP_CACHE'))
			$wpCache = (WP_CACHE ? "True" : "False");
		make_table_row("WP_CACHE setting", "The current setting of WP_CACHE in wpconfig.php.", $wpCache);

		make_table_row("WP-Cache enabled", "Whether the cache is enabled or not.", (wp_cache_is_enabled ? "Enabled" : "disabled"));
	}
}

function present_wordpress_plugins_information()
{
	/* XHTML */
	/* Make a table header with descriptive information about each field we are outputting */
	make_headline("WordPress Plugin Information");

	make_table_row("Active plugins", "All plugins currently activated for WordPress.", get_wordpress_active_plugins());
}

function present_semiologic_information()
{
	/* XHTML */
	/* Make a table header with descriptive information about each field we are outputting */
	make_headline("Semiologic Information");

	$options = get_option('sem5_options');

	make_table_row("Semiologic Version", "Whether Semiologic Professional is installed or not.", (sem_pro ? "Semiologic Professional" : "Semiologic Theme - Free version"));

	make_table_row("API Key", "The Semiologic API Key", $options['api_key']);

	$skin = get_skin_data($options['active_skin']['skin']);
	make_table_row("Skin", "The current Semiologic skin.", $skin['name'] . " " . $skin['version'] );

	make_table_row("Layout", "The current Semiologic layout", get_semiologic_layout());

	make_table_row("Width", "The current Semiologic width", get_semiologic_width());
}

function present_general_information()
{
	/* XHTML */
	/* Make a table header with descriptive information about each field we are outputting */
	make_headline("Server Information");

	make_table_row("Server operating system", "The operating system currently running on the server", php_uname('s'));

	make_table_row("Current version of PHP", "The current version of PHP used on this server", "PHP ".phpversion());

	make_table_row("Current version of MySQL", "The current version of MySQL used by Wordpress", "MySQL ".get_mysql_variable("version"));

	make_table_row("Webserver software", "The name of the webserver, the computer program that serves the pages to the users", $_SERVER['SERVER_SOFTWARE']);

	make_table_row("Webserver IP address", "A unique number that identifies the webserver to other computers on the internet and the local network", $_SERVER['SERVER_ADDR']);

	make_table_row("Webserver port number", "Which port that is used by the webserver to send pages and receive requests (usually 80)", $_SERVER['SERVER_PORT']);

	make_table_row("MySQL server IP address", "The IP address or hostname for the MySQL server Wordpress is using ", DB_HOST);

	make_table_row("MySQL server port number", "Which port that is used by Wordpress to send queries to the MySQL server (usually 3306)", get_mysql_variable("port"));

	make_table_row("MySQL database user", "The username Wordpress uses to authorize itself against the MySQL server", DB_USER);

	make_table_row("MySQL database name", "The name of the database where all data from Wordpress is stored", DB_NAME);

	make_table_row("Domain name", "A part of the address the visitors enter in order to access your page", $_SERVER['SERVER_NAME']);

	make_table_row("Webserver document root", "Where on the webserver your webpages and Wordpress are placed", $_SERVER['DOCUMENT_ROOT']);

	/* Code to assure that the ISO 8601 format is made regardless of PHP version */
	$unix_timestamp = time();
	$iso8601 = gmdate('Y-m-d\TH:i:sO',$unix_timestamp);
	$iso8601 = str_replace('+0000','+00:00',$iso8601);

	make_table_row("Current time (ISO 8601)", "The current time and date on the server expressed in the standard format called ISO 8601", $iso8601);

	make_table_row("Current time (RFC 2822)", "The current time and date on the server expressed in the standard format called RFC 2822 ", date("r"));
}


function present_php_information()
{
	$phpMemory = get_php_configuration_string("memory_limit");
	if (!$phpMemory)
		$phpMemory = "Unknown";

	$outputBuffering = get_php_configuration_string("output_buffering");
	if (!($outputBuffering  > 1))
		$outputBuffering = ($outputBuffering ? "On" : "Off");

	make_headline("PHP Information"." (PHP ".phpversion().")");

	make_table_row("Safe mode", "Whether safe mode is enabled or not. This setting should be Off.", get_php_configuration_boolean("safe_mode"));

	make_table_row("Memory limit", "Server memory limit. This setting should be 32M or greater.", $phpMemory);

	make_table_row("Output buffering", "Whether output buffer is enabled or not. This setting should be On or a buffer size in bytes.", $outputBuffering);

	make_table_row("Display errors", "Whether PHP is configured to display errors or not.", get_php_configuration_boolean("display_errors"));

	make_table_row("Register globals", "Whether PHP is configured to accept register globals. This is known to possibly cause security problems for scripts.", get_php_configuration_boolean("register_globals"));

	make_table_row("Magic quotes", "Whether PHP is configured to use Magic quotes for incoming GET/POST/Cookie data.", get_php_configuration_boolean("magic_quotes_gpc"));

	make_table_row("Magic quotes runtime", "Whether PHP is configured to use Magic quotes for Magic quotes for runtime-generated data, e.g. data from SQL, from exec(), etc.. This setting should be Off.", get_php_configuration_boolean("magic_quotes_runtime"));

	make_table_row("Max POST size", "Maximum allowed size for POST data.", get_php_configuration_string("post_max_size"));

	make_table_row("Allow file uploads", "Whether to allow file uploads or not.  This setting should be On.", get_php_configuration_boolean("file_uploads"));

	make_table_row("Allow url_fopen", "Whether to allow the treatment of URLs (like http:// or ftp://) as files.  This setting should be On.", get_php_configuration_boolean("allow_url_fopen"));

	make_table_row("File upload size", "Maximum allowed size for uploaded files.", get_php_configuration_string("upload_max_filesize"));

	make_table_row("Loaded Extensions", "An extension adds extra features or functions to PHP", get_php_loaded_extensions());
}


function present_all()
{
	present_wordpress_information();
	present_wordpress_plugins_information();
	present_semiologic_information();
	present_general_information();
	present_php_information();
}


function diagnosis ()
{
	present_all(); /* Call the function to get a table with all the PHP information */
}

#
# sem_diagnosis()
#

function sem_diagnosis()
{
	foreach (
		array(
			'wp-admin/includes/theme.php',
			'wp-content/themes/semiologic/admin/skin.php',
			) as $file
		)
	{
		include_once ABSPATH . $file;
	}

	$GLOBALS['wp_filter'] = array();

	while ( @ob_end_clean() );

	ob_start();

	header( 'Content-Type:text/plain; charset=utf-8' ) ;

	ob_start('sem_send_diagnosis');

	diagnosis();

	die;
}

add_action('init', 'sem_diagnosis');


#
# sem_send_diagnosis()
#

function sem_send_diagnosis($buffer)
{
	$admin_email = get_option('admin_email');
	$site_url = get_option('home');
	$to = 'support@semiologic.com';
	$subject = $site_url . ' diagnosis';
	$headers = "From: $admin_email";

	wp_mail($to, $subject, $buffer, $headers);

	return 'Site diagnosis sent to Semiologic Support.';
} # sem_send_diagnosis()
?>