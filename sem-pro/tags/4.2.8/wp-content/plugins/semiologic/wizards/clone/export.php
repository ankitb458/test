<?php

if ( !defined('clone_script_version') )
{
	define('clone_script_version', 1.5);
}

#
# export_semiologic_config()
#

function export_semiologic_config()
{
	global $wpdb;

	# clean buffer

	ob_end_clean();

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

	$data = false;

	switch ( $_REQUEST['data'] )
	{
	case 'version':
		$data = clone_script_version;
		break;

	case 'user':
		#echo '<pre>';
		#var_dump($user);
		#echo '</pre>';

		$data = $user_data;
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
					'wpcf_success_msg'
					)
			AND option_name NOT LIKE '%cache%'
			AND option_name NOT LIKE '%Cache%'
			AND option_name NOT LIKE 'mailserver_%'
			AND option_name NOT LIKE 'sm_%'
			AND option_name NOT REGEXP '^rss_[0-9a-f]{32}'
			AND option_name NOT LIKE 'hashcash_%'
			AND option_name NOT LIKE 'wp_cron_%';
			");

		#echo '<pre>';
		#var_dump($option_names);
		#echo '</pre>';
		#die();

		$options = array();

		foreach ( $option_names as $option )
		{
			$options[$option->option_name] = get_option($option->option_name);
		}

		#echo '<pre>';
		#var_dump(unserialize(base64_decode(base64_encode(serialize($options)))));
		#echo '</pre>';
		#die();

		$data = $options;
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

		$data = $ads;
		break;

	default:
		echo 'ERROR
Data Processing Failed';
		break;
	}

	if ( $data )
	{
		$data = serialize($data);
		$data = base64_encode($data);
		$data = wordwrap($data, 75, "\n", 1);
		$data = '<data>' . "\n" . $data . "\n" . '</data>';
		echo $data;
	}

	die;
} # end export_semiologic_config()
?>