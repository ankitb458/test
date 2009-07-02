<?php

class sem_features
{
	#
	# init()
	#

	function init()
	{
		if ( !defined('sem_docs_version')
			|| !file_exists(sem_docs_path . '/features/' . sem_docs_version . '.php')
			) return;
		
		$GLOBALS['sem_features'] = array();
		$GLOBALS['sem_feature_sets'] = array();

		add_action('admin_menu', array('sem_features', 'admin_menu'));
		add_action('_admin_menu', array('sem_features', '_admin_menu'), 0);
		
		if ( $_GET['page'] == plugin_basename(__FILE__) )
		{
			$GLOBALS['title'] = 'Features';
		}
		
		include sem_docs_path . '/features/' . sem_docs_version . '.php';
	} # init()
	
	
	#
	# admin_menu()
	#
	
	function admin_menu()
	{
		global $menu;
		
		add_menu_page(
			__('Features'),
			__('Features'),
			'switch_themes',
			__FILE__,
			array('sem_features', 'admin_page')
			);
		
		if ( is_array($menu) && is_array($menu[30]) && $menu[30][0] == __('Settings') )
		{
			$menu_item = array_pop($menu);
			$menu[33] = $menu_item;
		}
	} # admin_menu()
	
	
	#
	# admin_page()
	#
	
	function admin_page()
	{
		echo '<div class="wrap">'
			. '<form method="post" action="" id="sem_features">' . "\n";
		
		if ( $_GET['update'] )
		{
			echo '<div class="updated">' . "\n"
				. '<p>'
					. '<strong>'
					. __('Feature settings saved.')
					. '</strong>'
				. '</p>' . "\n"
				. '</div>' . "\n";
		}
		elseif ( $_GET['error'] )
		{
			echo '<div class="error">' . "\n"
				. '<p>'
					. '<strong>'
					. __('Your feature settings were only partially saved. A feature you\'ve selected triggered a fatal error.')
					. '</strong>'
					. '<br />'
					.  'Either your site is not up to date, or you\'ve been activating third party plugins that are causing conflicts with the ones in Semiologic Pro.'
				. '</p>' . "\n"
				. '</div>' . "\n";
		}
		
		echo '<h2>' . 'Semiologic Features' . '</h2>';
		
		if ( function_exists('wp_nonce_field') ) wp_nonce_field('sem_features');

		echo '<input type="hidden" name="update_sem_features" value="1" />' . "\n";
		
		$feature_sets = sem_features::get_tree();
		
		$feature_set_keys = array();
		
		foreach ( $feature_sets as $feature_set_key => $feature_set )
		{
			$feature_set_keys[] = '\'' . $feature_set_key . '\'';
			
			echo '<h3>'
				. '<label for="sem_features__' . $feature_set_key . '">'
				. '<input type="checkbox"'
					. ' id="sem_features__' . $feature_set_key . '"'
					. ' />'
				. '&nbsp;'
				. $feature_set['name']
				. '</label>'
				. '</h3>' . "\n";
			
			echo $feature_set['excerpt'] . "\n";
			
			echo '<table class="form-table">' . "\n";
			
			$feature_keys = array();
			
			foreach ( $feature_set['features'] as $feature_key => $feature )
			{
				$feature_keys[] = '\'' . $feature_key . '\'';
				
				$more_info = '';
				$less_info = '';
				$showhide = '';
				
				$checked = sem_features::is_active($feature_key);
				$disabled = sem_features::is_locked($feature_key);
				
				$class = array();
				
				if ( $checked )
				{
					$class[] = 'sem_features__active';
				}
				
				if ( $disabled )
				{
					$class[] = 'sem_features__locked';
				}
				
				$class = implode(' ', $class);
				
				$id = 'sem_features__' . $feature_set_key . '__' . $feature_key;
				
				if ( $feature['content'] )
				{
					$more_info = '<div id="' . $id . '__more"'
							. ' class="sem_features__toggle"'
							. '>'
						. '<a href="javascript:;"'
							. ' onclick="sem_features.show(\'' . $id . '\');"'
							. '>' . 'More Info' . '</a>'
						. '</div>' . "\n";
					
					$less_info = '<div id="' . $id . '__less"'
							. ' class="sem_features__toggle"'
							. ' style="display: none;"'
							. '>'
						. '<a href="javascript:;"'
							. ' onclick="sem_features.hide(\'' . $id . '\');"'
							. '>' . 'Hide Info' . '</a>'
						. '</div>' . "\n";
					
					$showhide = ' ondblclick="sem_features.showhide(\'' . $id . '\');"';
				}
				
				echo '<tr valign="top"' . $showhide . '>' . "\n"
					. '<td style="width: 20px;">'
					. '<input type="checkbox"'
						. ' name="sem_features[' . $feature_key . ']"'
						. ' id="' . $id . '"'
						. ( $checked
							? ' checked="checked"'
							: ''
							)
						. ( $disabled
							? ' disabled="disabled"'
							: ''
							)
						. ' />'
					. '</td>' . "\n"
					. '<th scope="row"'
						. ( $class
							? ( ' class="' . $class . '"' )
							: ''
							)
						. '>'
					. '<label for="' . $id . '">'
					. $feature['name']
					. '</label>'
					. '</th>' . "\n"
					. '<td>' . "\n"
					. $more_info
					. $less_info
					. '<div id="' . $id . '__excerpt">' . "\n"
					. '<label for="' . $id . '">'
					. $feature['excerpt'] . "\n"
					. '</label>'
					. '</div>' . "\n"
					. ( $more_info
						? ( '<div id="' . $id . '__content"'
								. ' style="display: none;">' . "\n"
							. '<label for="' . $id . '">'
							. $feature['content'] . "\n"
							. '</label>'
							. '</div>' . "\n"
							)
						: ''
						)
					. '<div style="clear: both;"></div>'
					. '</td>' . "\n"
					. '</tr>' . "\n";
			}
			
			echo '</table>' . "\n";
			
			$feature_keys = implode(',', $feature_keys);
			
			echo '<script type="text/javascript">' . "\n"
				. 'sem_features.bindFeatureSet(\'' . $feature_set_key . '\', new Array(' . $feature_keys . '));'
				. '</script>' . "\n";
			
			echo '<p class="submit">'
				. '<input type="submit" class="submit"'
					. ' value="' . __('Save Changes') . '"'
					. ' />'
				. '</p>';
		}
		
		$feature_set_keys = implode(',', $feature_set_keys);
		
		echo '<script type="text/javascript">' . "\n"
			. 'sem_features.bindFeatures(new Array(' . $feature_set_keys . '));'
			. '</script>' . "\n";
		
		echo '</form>'
			. '</div>';
	} # admin_page()
	
	
	#
	# _admin_menu()
	#
	
	function _admin_menu()
	{
		if ( $_POST['update_sem_features'] )
		{
			sem_features::update_options();
		}
	} # update_options()
	
	
	#
	# update_options()
	#
	
	function update_options()
	{
		check_admin_referer('sem_features');
		
		global $sem_features;
		
		$all_features = array_keys($sem_features);
		$activate = array_keys((array) $_POST['sem_features']);
		$deactivate = array_diff($all_features, $activate);
		
		# some plugins rely on this to do things they shouldn't
		$plugin_page_backup = $GLOBALS['plugin_page'];
		unset($GLOBALS['plugin_page']);
		
		# working out a diff is invalid, as some feature can be locked or can depend on several plugins
		
		# activate plugins

		$plugins = array();
		
		foreach ( $activate as $key )
		{
			$plugins = array_merge($plugins, (array) $sem_features[$key]['plugins']);
		}
		
		# have WP activate the plugins
		$plugins = array_merge($plugins, get_option('active_plugins'));
		update_option('deactivated_plugins', $plugins);
		$redirect = trailingslashit(get_option('siteurl'))
			. 'wp-admin/admin.php?page=' . plugin_basename(__FILE__);
		reactivate_all_plugins($redirect . '&error=true');
		wp_redirect($redirect . '&update=true');
		
		#dump($plugins);
		#die;
		
		# loop through activate callbacks
		
		foreach ( $activate as $key )
		{
			if ( !is_null($sem_features[$key]['activate']) )
			{
				call_user_func($sem_features[$key]['activate']);
			}
		}
		
		# deactivate plugins

		$plugins = array();
		
		foreach ( $deactivate as $k => $key )
		{
			if ( sem_features::is_locked($key) )
			{
				unset($deactivate[$k]);
			}
			else
			{
				$plugins = array_merge($plugins, (array) $sem_features[$key]['plugins']);
			}
		}
		
		#dump($plugins);
		#die;

		# WP can deactivate plugins, but does so with a serious bug
		$current = get_option('active_plugins');

		foreach ( $plugins as $plugin )
		{
			if ( ( $key = array_search( $plugin, $current) ) !== false )
			{
				array_splice($current, $key, 1 ); // Fixed Array-fu!
				do_action('deactivate_' . trim( $plugin ));
			}
		}
		
		#dump($plugins);
		#die;

		update_option('active_plugins', $current);
		

		# loop through deactivate callbacks
		
		foreach ( $deactivate as $key )
		{
			if ( !is_null($sem_features[$key]['deactivate']) )
			{
				call_user_func($sem_features[$key]['deactivate']);
			}
		}
		
		# restore $plugin_page
		$GLOBALS['plugin_page'] = $plugin_page_backup;
	} # update_options()
	
	
	#
	# get_tree()
	#
	
	function get_tree()
	{
		global $sem_feature_sets;
		
		#
		# initial structure:
		#
		# sem_feature_sets[$key] = array( $feature_key )
		#
		
		# fetch docs
		
		list($feature_set_docs, $feature_docs) = sem_docs::get_features();

		$feature_sets = array();
		$features = array();
		
		foreach ( (array) $feature_set_docs as $feature_set )
		{
			$feature_sets[$feature_set->key] = (array) $feature_set;
		}
		
		foreach ( (array) $feature_docs as $feature )
		{
			$features[$feature->key] = (array) $feature;
		}
		
		$feature_set_docs = $feature_sets;
		$feature_docs = $features;
		
		#
		# new docs structure:
		#
		# feature_set_docs[$key] = array(
		#	key => $key,
		#	name => $name,
		#	excerpt => $excerpt,
		#	content => $content
		#	)
		# feature_docs[$key] = array(
		#	key => $key,
		#	name => $name,
		#	excerpt => $excerpt,
		#	content => $content
		#	)
		#
		
		# build the feature tree
		
		$feature_sets = array();
		
		foreach ( $sem_feature_sets as $feature_set_key => $feature_keys )
		{
			$features = array();
			
			# assign a name to undocumented feature sets
			if ( !isset($feature_set_docs[$feature_set_key]) )
			{
				$feature_set_docs[$feature_set_key]['name'] = '<span class="error">' . $feature_set_key . '</span>';
			}
			
			foreach ( $feature_keys as $feature_key )
			{
				# assign a name to undocumented features
				if ( !isset($feature_docs[$feature_key]) )
				{
					$feature_docs[$feature_key]['name'] = '<span class="error">' . $feature_key . '</span>';
				}
				
				$features[$feature_key] = $feature_docs[$feature_key];
			}
			
			# sort features in a natural way
			uasort($features, array('sem_features', 'natsort'));
			
			$feature_sets[$feature_set_key] = array_merge(
					$feature_set_docs[$feature_set_key],
					array( 'features' => $features )
					);
		}
		
		#
		# returned structure:
		#
		# feature_sets = array(					(sorted in the order they're registered)
		#	$key => array(
		#		key => $key,
		#		name => $name,
		#		excerpt => $excerpt,
		#		content => $content,
		#		features => array(				(sorted by feature name)
		#			$key => array(
		#				key => $key,
		#				name => $name,
		#				excerpt => $excerpt,
		#				content => $content
		#				)
		#			)
		#		)
		#	)
		#
		
		#echo '<pre>';
		#var_dump($sem_features);
		#echo '</pre>';

		return $feature_sets;
	} # get_tree()
	
	
	#
	# natsort()
	#
	
	function natsort($a, $b)
	{
		return strnatcmp($a['name'], $b['name']);
	} # natsort()
	
	
	#
	# is_active()
	#
	
	function is_active($key)
	{
		global $sem_features;
		static $active_plugins;

		if ( !is_null($sem_features[$key]['locked']) )
		{
			return $sem_features[$key]['locked'];
		}
		elseif ( !is_null($sem_features[$key]['is_active']) )
		{
			return call_user_func($is_active);
		}
		
		if ( !isset($active_plugins) )
		{
			$active_plugins = (array) get_option('active_plugins');
		}
		
		foreach ( (array) $sem_features[$key]['plugins'] as $plugin )
		{
			if ( in_array($plugin, $active_plugins) )
			{
				return true;
			}
		}
		
		return false;
	} # is_active()
	
	
	#
	# is_locked()
	#
	
	function is_locked($key)
	{
		global $sem_features;
		
		return isset($sem_features[$key]['locked']);
	} # is_locked()
	
	
	#
	# register()
	#
	
	function register($feature_sets, $features = null)
	{
		global $sem_feature_sets;
		
		if ( is_array($feature_sets) && !isset($features) )
		{
			if ( !is_string(key($feature_sets)))
			{
				# args = feature sets
				$_feature_sets = array();

				foreach ( $feature_sets as $key )
				{
					$_feature_sets[$key] = array();
				}

				$feature_sets = $_feature_sets;
			}

			# args = a feature tree
			$sem_feature_sets = array_merge_recursive(
					$sem_feature_sets,
					$feature_sets
					);
		}
		else
		{
			# args = features in one or more sets
			foreach ( (array) $feature_sets as $feature_set )
			{
				$sem_feature_sets[$feature_set] = array_merge(
						(array) $sem_feature_sets[$feature_set],
						(array) $features
						);
			}
		}
	} # register()
	
	
	#
	# unregister()
	#
	
	function unregister($feature_sets, $features = null)
	{
		global $sem_feature_sets;
		
		if ( !isset($features) )
		{
			if ( is_array($feature_sets) && is_string(key($feature_sets)) )
			{
				# a feature tree
				foreach ( $feature_sets as $feature_set => $features )
				{
					foreach ( (array) $features as $feature )
					{
						unset($sem_features[$feature_set][$feature]);
					}
				}
			}
			else
			{
				# an one or more sets
				foreach ( (array) $feature_sets as $feature_set )
				{
					unset($sem_features[$feature_set]);
				}
			}
		}
		else
		{
			if ( !isset($feature_sets) )
			{
				# args = one or more features
				$feature_sets = array_keys($sem_feature_sets);
			}
			
			# args = one or more features in one or more sets
			foreach ( (array) $feature_sets as $feature_set )
			{
				foreach ( (array) $features as $feature )
				{
					unset($sem_features[$feature_set][$feature]);
				}
			}
		}
	} # unregister()
	
	
	#
	# set_handler()
	#
	
	function set_handler($key, $plugins = null, $activate = null, $deactivate = null, $is_active = null)
	{
		global $sem_features;
		
		$feature = $sem_features[$key];

		$feature['plugins'] = (array) $plugins;
		$feature['activate'] = $activate;
		$feature['deactivate'] = $deactivate;
		$feature['is_active'] = $is_active;

		$sem_features[$key] = $feature;
	} # set_handler()
	
	
	#
	# unset_handler()
	#
	
	function unset_handler($key)
	{
		global $sem_features;
		
		$locked = $sem_features[$key]['locked'];
		
		unset($sem_features[$key]);
		
		if ( !is_null($locked) )
		{
			$sem_features[$key]['locked'] = $locked;
		}
	} # unset_handler()
	
	
	#
	# lock()
	#
	
	function lock($key, $locked = true)
	{
		global $sem_features;
		
		$sem_features[$key]['locked'] = $locked;
	} # lock()
	
	
	#
	# unlock()
	#
	
	function unlock($key)
	{
		global $sem_features;
		
		unset($sem_features[$key]['locked']);
	} # unlock()
} # sem_features

sem_features::init();
?>