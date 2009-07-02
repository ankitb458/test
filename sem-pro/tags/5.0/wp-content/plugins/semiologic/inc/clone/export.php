<?php
$debug = false;

global $wpdb;

// Reset WP

$GLOBALS['wp_filter'] = array();

while ( @ob_end_clean() );

header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
// always modified
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
// HTTP/1.1
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
// HTTP/1.0
header('Pragma: no-cache');

if ( !$debug )
{
	# Set the response format.
	header( 'Content-Type:text/xml; charset=utf-8' );
	echo '<?xml version="1.0" encoding="utf-8" ?>';
}

# validate request

if ( !$_REQUEST['data'] )
{
	die('<error>Request failed</error>');
}

# validate user

$user_data = get_userdatabylogin($_REQUEST['user']);

if ( !$user_data
	|| $user_data->user_pass != $_REQUEST['pass']
	|| !( $user = new WP_User($user_data->user_login) )
	|| !$user->has_cap('administrator')
	)
{
	die('<error>Access Denied</error>');
}


$data = false;

switch ( $_REQUEST['data'] )
{
case 'version':
	$data = sem_version;
	break;

case 'options':
	$option_names = (array) $wpdb->get_results("
		SELECT option_name
		FROM $wpdb->options
		WHERE option_name NOT IN (
				'home',
				'siteurl',
				'blogname',
				'blogdescription',
				'admin_email',
				'default_category',
				'db_version',
				'secret',
				'page_uris',
				'sem_links_db_changed',
				'wp_autoblog_feeds',
				'wp_hashcash_db',
				'posts_have_fulltext_index',
				'permalink_redirect_feedburner',
				'sem_google_analytics_params',
				'falbum_options',
				'do_smart_ping',
				'blog_public',
				'countdown_datefile',
				'remains_to_ping',
				'rewrite_rules',
				'upload_path',
				'show_on_front',
				'page_on_front',
				'sem_static_front_cache',
				'wpcf_email',
				'wpcf_subject_suffix',
				'wpcf_success_msg',
				'sem_newsletter_manager_params',
				'semiologic',
				'sem5_nav',
				'feedburner_settings',
				'doing_cron',
				'sem5_docs',
				'update_core',
				'update_plugins',
				'version_checker'
				)
		AND option_name NOT LIKE '%cache%'
		AND option_name NOT LIKE '%Cache%'
		AND option_name NOT LIKE 'mailserver_%'
		AND option_name NOT LIKE 'sm_%'
		AND option_name NOT LIKE 'hashcash_%'
		AND option_name NOT LIKE 'wp_cron_%'
		AND option_name NOT LIKE 'wpnavt_%'
		AND option_name NOT REGEXP '^rss_[0-9a-f]{32}'
		;");

	$options = array();

	foreach ( $option_names as $option )
	{
		$options[$option->option_name] = get_option($option->option_name);
	}

	$data = $options;
	break;

default:
	echo '<error>Invalid Data</error>';
	break;
}

if ( $data )
{
	if ( $debug )
	{
		dump($data);
	}
	else
	{
		$data = serialize($data);
		$data = base64_encode($data);
		$data = wordwrap($data, 75, "\n", 1);
		$data = '<data>' . "\n" . $data . "\n" . '</data>';
		echo $data;
	}
}

die;
?>