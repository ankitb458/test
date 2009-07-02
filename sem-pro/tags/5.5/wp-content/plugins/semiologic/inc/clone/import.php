<?php
if ( !class_exists('wiz_clone_import') ) :

class wiz_clone_import
{
	#
	# do_step()
	#

	function do_step($step)
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
		elseif ( !( $version = wiz_clone_import::get_data('version', $site_details) ) )
		{
			$errors[] = __('Access denied');
		}
		elseif ( !version_compare($version, sem_version, '=') )
		{
			$errors[] = __('The two sites are not running the same version of Semiologic Pro');
		}
		elseif ( !wiz_clone_import::clone_options($site_details) )
		{
			$errors[] = __('Failed to clone options');
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

			return 'start';
		}

		return 'done';
	} # do_step()


	#
	# get_data()
	#

	function get_data($data, $site_details)
	{
		$site_user = $site_details['site_user'];
		$site_pass = md5($site_details['site_pass']);

		$url = $site_details['site_url']
			. '?method=export'
			. '&data=' . urlencode($data)
			. '&user=' . urlencode($site_details['site_user'])
			. '&pass=' . urlencode(md5($site_details['site_pass']));

		$data = sem_http::get($url);

		if ( !preg_match("/
				<data>
				(.*)
				<\/data>
				/isUx",
				$data,
				$data
				)
			)
		{
			return false;
		}

		$data = end($data);
		$data = @base64_decode($data);
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

		$options = wiz_clone_import::get_data('options', $site_details);

		foreach ( $options as $option_name => $option_value )
		{
			switch ( $option_name )
			{
			case 'mediacaster':
				$old_value = get_option($option_name);
				$option_value['itunes'] = $old_value['itunes'];
				update_option($option_name, $option_value);
				break;

			default:
				update_option($option_name, $option_value);
				break;
			}
		}

		# autocorrect rewrite rules
		$permalink_structure = get_option('permalink_structure');
		$cat_base = get_option('category_base');
		$tag_base = get_option('tag_base');

		if ( !( is_file(ABSPATH . '.htaccess') && is_writable(ABSPATH . '.htaccess') && got_mod_rewrite() ) )
		{
			$permalink_structure = '';
			$cat_base = '';
			$tag_base = '';
		}

		update_option('permalink_structure', $post_permalink);
		update_option('category_base', $cat_base);
		update_option('tag_base', $tag_base);
		$wp_rewrite->flush_rules();

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
} # wiz_clone_import

endif;
?>