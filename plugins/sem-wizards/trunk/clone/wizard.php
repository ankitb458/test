<?php
#
# Wizard Name: Clone
#

class wiz_clone
{
	#
	# init()
	#
	
	function init()
	{
		if ( $_GET['page'] == plugin_basename(__FILE__) )
		{
			$GLOBALS['title'] = 'Clone Wizard';
		}
		
		@session_start();
		
		sem_wizards::register_step(
			'start',
			array('wiz_clone', 'import'),
			array('wiz_clone', 'do_import')
			);

		sem_wizards::register_step(
			'done',
			array('wiz_clone', 'done')
			);
		
		add_action('wizard_clone_export', array('wiz_clone', 'export'));
	} # init()
	
	
	#
	# import()
	#
	
	function import()
	{
		echo '<h3>'
			. '<u>Import</u> &rarr; Done'
			. '</h3>';

		echo '<p>'
			. 'The clone wizard will let you copy another Semiologic Pro site\'s settings and presentation options.'
			. '</p>';

		echo '<p>'
			. 'To proceed, enter the url and admin details of the site you wish to clone:'
			. '</p>';

		$labels = array(
			'site_url' => 'Site Url',
			'site_user' => 'Admin Username',
			'site_pass' => 'Admin Password',
			);

		echo '<table class="form-table">' . "\n";
		
		foreach ( array_keys($labels) as $key )
		{
			echo '<tr valign="top">'
				. '<th scope="row">'
				. $labels[$key]
				. '</th>'
				. '<td>'
				. '<input type="' . ( $key == 'site_pass' ? 'password' : 'text' ) . '" class="code" size="58"'
					. ' name="' . $key . '"'
					. ' value="' . ( $key == 'site_pass' ? '' : htmlspecialchars($_SESSION[$key]) ) . '"'
					. ' />'
				. '</td>'
				. '</tr>';
		}
		
		echo '<tr valign="top">'
			. '<th scope="row">'
			. 'Notice'
			. '</th>'
			. '<td>'
			. 'Cloning a site can take up to a few minutes. Patience is your friend.'
			. '</td>'
			. '</tr>';

		echo '</table>';
	} # import()
	
	
	#
	# done()
	#
	
	function done()
	{
		echo '<h3>'
			. 'Import &rarr; <u>Done</u>'
			. '</h3>';

		echo '<p>' . __('Your site has been successfully cloned. A few things you may now want to look into:') . '</p>';

		echo '<ul>';

		echo '<li>' . __('Various Widget options, under Design / Widgets. Namely your nav menus and your newsletter widgets.') . '</li>';
		echo '<li>' . __('Scripts, under Settings / Scripts') . '</li>';
		echo '<li>' . __('Google Analytics, under Settings / Google Analytics') . '</li>';
		echo '<li>' . __('Feeburner URL, under Settings / Feedburner') . '</li>';
		echo '<li>' . __('Itunes settings, under Settings / Mediacaster') . '</li>';
		echo '<li>' . __('Semiologic Cache options, under Settings / Cache') . '</li>';

		echo '</ul>';
	} # done()
	
	
	#
	# do_import()
	#
	
	function do_import($step)
	{
		set_time_limit(0);
		ignore_user_abort(true);

		$site_details = array();

		foreach ( array('site_url', 'site_user', 'site_pass') as $var )
		{
			$$var = trim(strip_tags(stripslashes($_POST[$var])));
			$site_details[$var] = $$var;
		}

		if ( !( $site_url && $site_user && $site_pass ) )
		{
			$errors[] = __('Please enter the url and admin details of the site you wish to clone');
		}
		elseif ( !( $version = wiz_clone::get_data('version', $site_details) ) )
		{
			$errors[] = __('Access denied');
		}
		elseif ( !version_compare($version, sem_version, '=') )
		{
			$errors[] = __('The two sites are not running the same version of Semiologic Pro');
		}
		elseif ( !wiz_clone::clone_options($site_details) )
		{
			$errors[] = __('Failed to clone options. This is systematically due to character set encoding problems. The way WordPress manages character sets has varied a lot from WP 2.0 to WP 2.5. This causes multitudes of serialization incompatibilities, and has rendered cloning some sites impossible. Sorry... can\'t continue.');
		}

		if ( !empty($errors) )
		{
			echo '<div class="error">';

			echo '<ul>';

			foreach ( $errors as $error )
			{
				echo '<li>' . $error . '</li>';
			}

			echo '</ul>';

			echo '</div>';

			return $step;
		}
		
		# turn cache off
		if ( function_exists('wp_cache_disable') ) wp_cache_disable();

		return 'done';
	} # do_import()


	#
	# get_data()
	#

	function get_data($data, $site_details)
	{
		$site_url = trailingslashit($site_details['site_url']);
		$site_user = trim($site_details['site_user']);
		$site_pass = trim($site_details['site_pass']);

		$url = $site_url
			. '?wizard=clone'
			. '&method=export'
			. '&data=' . urlencode($data)
			. '&user=' . urlencode($site_user)
			. '&pass=' . urlencode($site_pass);

		$data = wp_remote_fopen($url);

		#dump($url, $data);
		
		if ( strpos($data, "<data>") === false ) return false;
		
		$data = preg_replace("/^.*<data>|<\/data>.*$/", '', $data);
		$data = @base64_decode($data);
		
		#dump($data);

		$data = @unserialize($data);

		#dump($data);

		return $data;
	} # get_data


	#
	# clone_options()
	#

	function clone_options($site_details)
	{
		global $wp_rewrite;
		
		$options = wiz_clone::get_data('options', $site_details);
		
		if ( $options === false )
		{
			return false;
		}
		
		foreach ( $options as $option_name => $option_value )
		{
			#dump($option_name, $option_value);
			
			switch ( $option_name )
			{
			case 'mediacaster':
				$old_value = get_option($option_name);
				$option_value['itunes'] = $old_value['itunes'];
				update_option($option_name, $option_value);
				break;
			
			case 'sem_seo':
				$old_value = get_option($option_name);
				foreach ( array('title', 'keywords', 'description') as $var )
				{
					$option_value[$var] = $old_value[$var];
				}
				update_option($option_name, $option_value);
				break;
			
			case 'sem_api_key':
				if ( !get_option('sem_api_key') )
				{
					update_option($option_name, $option_value);
				}
				break;

			default:
				update_option($option_name, $option_value);
				break;
			}
		}

		if ( $plugins = get_option('active_plugins') )
		{
			$old_plugin_page = $GLOBALS['plugin_page'];
			unset($GLOBALS['plugin_page']);

			foreach ( $plugins as $plugin )
			{
				if ( file_exists(ABSPATH . PLUGINDIR . '/' . $plugin) )
				{
					include_once(ABSPATH . PLUGINDIR . '/' . $plugin);
					do_action('activate_' . $plugin);
				}
			}

			$GLOBALS['plugin_page'] = $old_plugin_page;
		}

		return true;
	} # clone_options()
	
	
	#
	# export()
	#
	
	function export()
	{
		$data = false;

		switch ( $_REQUEST['data'] )
		{
		case 'version':
			$data = sem_version;
			break;

		case 'options':
			global $wpdb;
			
			$option_names = (array) $wpdb->get_col("
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
						'google_analytics',
						'falbum_options',
						'do_smart_ping',
						'blog_public',
						'countdown_datefile',
						'remains_to_ping',
						'rewrite_rules',
						'upload_path',
						'show_on_front',
						'page_on_front',
						'page_for_posts',
						'sem_static_front_cache',
						'wpcf_email',
						'wpcf_subject_suffix',
						'wpcf_success_msg',
						'sem_newsletter_manager_params',
						'semiologic',
						'sem_docs',
						'feedburner_settings',
						'doing_cron',
						'update_core',
						'update_plugins',
						'version_checker',
						'permalink_structure',
						'category_base',
						'tag_base',
						'sem_nav_menus',
						'sem5_nav',
						'google_analytics',
						'cron',
						'fix_wysiwyg',
						'recently_edited',
						'script_manager',
						'sem5_docs',
						'sem5_docs_updated'
						)
				AND option_name NOT LIKE '%cache%'
				AND option_name NOT LIKE '%Cache%'
				AND option_name NOT LIKE 'mailserver_%'
				AND option_name NOT LIKE 'sm_%'
				AND option_name NOT LIKE '%hashcash%'
				AND option_name NOT LIKE 'wp_cron_%'
				AND option_name NOT LIKE 'wpnavt_%'
				AND option_name NOT REGEXP '^rss_[0-9a-f]{32}'
				;");

			$options = array();

			foreach ( $option_names as $option_name )
			{
				$options[$option_name] = get_option($option_name);
			}

			$data = $options;
			break;

		default:
			echo '<error>Invalid Data</error>';
			break;
		}

		if ( $data )
		{
			$data = serialize($data);
			$data = base64_encode($data);
			$data = wordwrap($data, 75, "\n", 1);
			$data = '<data>'
				. "\n"
				. $data
				. "\n"
				. '</data>';

			echo $data;
		}

		die;
	} # export()
} # wiz_clone

wiz_clone::init();
?>