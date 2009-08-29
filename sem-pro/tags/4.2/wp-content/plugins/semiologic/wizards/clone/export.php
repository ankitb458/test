<?php

if ( !defined('clone_script_version') )
{
	define('clone_script_version', 1.0);
}

#
# export_semiologic_config()
#

function export_semiologic_config()
{
	global $wpdb;

	# validate request

	if ( !isset($_REQUEST['data'])
		|| !isset($_REQUEST['user_login'])
		|| !isset($_REQUEST['user_pass'])
		)
	{
		die('ERROR
Request failed');
	}

	# validate user

	$user_data = get_userdatabylogin($_REQUEST['user_login']);

	if ( !$user_data
		|| ( $user_data->user_pass != $_REQUEST['user_pass'] )
		)
	{
		die('ERROR
Authentication failed: Please verify your user details');
	}

	$user = new WP_User($user_data->user_login);
	if ( !$user->has_cap('administrator') )
	{
		die('ERROR
Access denied: This user is not an administrator');
	}

	switch ( $_REQUEST['data'] )
	{
	case 'version':
		echo clone_script_version;
		break;

	case 'user':
		#echo '<pre>';
		#var_dump($user);
		#echo '</pre>';

		echo base64_encode(serialize($user_data));
		break;

	case 'options':
		$option_names = (array) $wpdb->get_results("
			SELECT option_name
			FROM $wpdb->options
			WHERE option_name NOT LIKE 'mailserver_%'
			AND option_name NOT IN (
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
					'posts_have_fulltext_index'
					)
			AND option_name NOT REGEXP '^rss_[0-9a-f]{32}';
			");

		$options = array();
		foreach ( $option_names as $option )
		{
			$options[$option->option_name] = get_option($option->option_name);
		}

		#echo '<pre>';
		#var_dump($options);
		#echo '</pre>';

		echo base64_encode(serialize($options));
		break;

	case 'ads':
		$ads = array();

		$ads['ad_block2tag'] = (array) $wpdb->get_results("
			SELECT *
			FROM $wpdb->ad_block2tag;
			");
		$ads['ad_blocks'] = (array) $wpdb->get_results("
			SELECT *
			FROM $wpdb->ad_blocks;
			");
		$ads['ad_distribution2post'] = (array) $wpdb->get_results("
			SELECT *
			FROM $wpdb->ad_distribution2post;
			");
		$ads['ad_distribution2tag'] = (array) $wpdb->get_results("
			SELECT *
			FROM $wpdb->ad_distribution2tag;
			");
		$ads['ad_distributions'] = (array) $wpdb->get_results("
			SELECT *
			FROM $wpdb->ad_distributions;
			");

		#echo '<pre>';
		#var_dump($ads);
		#echo '</pre>';

		echo base64_encode(serialize($ads));
		break;

	default:
		echo 'ERROR
Data Processing Failed';
		break;
	}


	die;
} # end export_semiologic_config()
?>