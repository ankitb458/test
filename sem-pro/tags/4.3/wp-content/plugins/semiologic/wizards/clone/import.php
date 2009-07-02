<?php

if ( !defined('clone_script_version') )
{
	define('clone_script_version', 1.5);
}

#
# request_cloned_site_details()
#

function request_cloned_site_details()
{
	echo '<h3>' . __('Cloned Site Details') . '</h3>';

	echo '<p>' . __('Please enter the details of the site you\'d like to clone.') . '</p>';

	echo '<p>'
		. '<label for="site_uri">'
		. __('Site Address') . ':<br />'
		. '<input type="text"'
			. ' id="site_uri" name="site_uri"'
			. ' value="'
				. ( isset($_POST['site_uri'])
					? $_POST['site_uri']
					: ''
					)
				. '"'
			. ' style="width: 480px;"'
			. '/>'
		. '</label>'
		. '</p>';

	echo '<p>'
		. '<label for="username">'
		. __('Admin Username') . ':<br />'
		. '<input type="text"'
			. ' id="username" name="username"'
			. ' value="'
				. ( isset($_POST['username'])
					? $_POST['username']
					: 'admin'
					)
				. '"'
			. ' style="width: 480px;"'
			. '/>'
		. '</label>'
		. '</p>';

	echo '<p>'
		. '<label for="password">'
		. __('Admin Password') . ':<br />'
		. '<input type="password"'
			. ' id="password" name="password"'
			. ' value="'
				. ( isset($_POST['password'])
					? $_POST['password']
					: ''
					)
				. '"'
			. ' style="width: 480px;"'
			. '/>'
		. '</label>'
		. '</p>';

	echo '<p>'
		. __('Note: Cloning is sometimes long. Hitting the next button will restart the entire process.')
		. '</p>';
} # request_cloned_site_details()


#
# check_cloned_site_details()
#

function check_cloned_site_details($step = 1)
{
	#echo '<pre>';
	#var_dump($_POST);
	#echo '</pre>';

	$_POST['site_uri'] = trim($_POST['site_uri']);
	$_POST['username'] = trim($_POST['username']);
	$_POST['password'] = trim($_POST['password']);

	# check for input errors

	$errors = array();

	$_POST['site_uri'] = preg_replace("/\?.*$/i", "", $_POST['site_uri']);
	$_POST['site_uri'] = preg_replace("/^https?:\/\/|index\.php$/i", "", $_POST['site_uri']);
	#$_POST['site_uri'] = preg_replace("/\/+$/", "", $_POST['site_uri']);

	if ( $_POST['site_uri'] == '' )
	{
		$errors[] = 'Please enter the address of the site you\'d like to clone';
	}

	if ( $_POST['username'] == '' )
	{
		$errors[] = 'Please enter the admin username of the site you\'d like to clone';
	}

	if ( $_POST['password'] == '' )
	{
		$errors[] = 'Please enter the admin password of the site you\'d like to clone';
	}

	if ( empty($errors) )
	{
		# Grab data

		$site_uri = 'http://' . $_POST['site_uri'];
		$user_login = $_POST['username'];
		$user_pass = md5($_POST['password']);

		list($errors, $user, $options, $ads) = get_semiologic_config($errors, $site_uri, $user_login, $user_pass);
	}

	if ( !empty($errors) )
	{
		echo '<div class="error"><ul>';

		foreach ( $errors as $error )
		{
			echo '<li>' . __($error) . '</li>';
		}

		echo '</ul></div>';

		return $step;
	}
	else
	{
		import_semiologic_config($user, $options, $ads);

		return 2;
	}
} # end check_cloned_site_details()


#
# notify_site_cloned()
#

function notify_site_cloned($step = 2)
{
	echo '<p>' . __('Your site has been successfully cloned. Here a few things you may want to look into:') . '</p>';

	echo '<ul>';

	echo '<li>' . __('Google Analytics, under Options / Google Analytics') . '</li>';
	echo '<li>' . __('Feeburner URL, under Options / Permalink Redirect') . '</li>';
	echo '<li>' . __('WP-Cache options, under Options / WP-Cache') . '</li>';

	echo '</ul>';

	return 'done';
} # end notify_site_cloned()


#
# check_notify_site_cloned()
#

function check_notify_site_cloned($step = 2)
{
	return 'done';
}



#
# get_semiologic_config()
#

function get_semiologic_config($errors, $site_uri, $user_login, $user_pass)
{
	foreach ( array('version', 'user', 'options', 'ads') as $data )
	#foreach ( array('options') as $data )
	{
		$vars = array(
			'action' => 'export',
			'user_login' => $user_login,
			'user_pass' => $user_pass,
			'data' => $data,
			'random_hash' => md5(time())
			);

		$params = "";
		$i = 0;

		foreach ( $vars as $key => $value )
		{
			$params .= rawurlencode($key)
				. "=" . rawurlencode($value)
				. ( ( ++$i < sizeof($vars) )
					? "&"
					: ""
					);
		}

		$src = $site_uri . '?' . $params;

		#echo '<pre>';
		#var_dump($src);
		#echo '</pre>';
		#die();

		if ( function_exists('curl_exec') )
		{
			$ch = curl_init();
			$timeout = 30; // set to zero for no timeout
			curl_setopt ($ch, CURLOPT_URL, $src);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			curl_setopt ($ch, CURLOPT_HTTPHEADER, array("Pragma: no-cache"));
			curl_setopt ($ch, CURLOPT_HTTPHEADER, array("Cache-Control: no-store, no-cache, must-revalidate"));
			$$data = curl_exec($ch);
			curl_close($ch);

			#var_dump($src, $$data);
			#die();
		}
		elseif ( !ini_get('allow_url_fopen') )
		{
			$errors[] = 'allow_url_fopen is turned off on this server. Have your host turn it or curl on, or change hosts.';
		}
		else
		{
			$$data = file_get_contents($src);

			#var_dump($src, $$data);
			#die();
		}

		if ( $$data === false )
		{
			$errors[] = 'Failed to open stream. Double checked your cloned site\'s details? ';
			break;
		}

		#var_dump($$data);
		#die();

		if ( preg_match("/^ERROR/", $$data) )
		{
			$error = trim(preg_replace("/^ERROR/", "", $$data));

			$errors[] = $error;

			if ( $error == 'Data Processing Failed' )
			{
				$errors[] = 'Please make sure both sites are using the same version of the cloning script';
			}

			break;
		}

		#var_dump($$data);
		#die();

		#$$data = preg_replace("/.*<data>/s", "", $$data);
		#$$data = preg_replace("/<\/data>.*/s", "", $$data);

		$$data = substr($$data, strlen('<data>'));
		$$data = substr($$data, 0, strpos($$data, '</data>'));

		#var_dump($$data);
		#die();

		$$data = base64_decode($$data);
		$$data = unserialize($$data);

		#var_dump($$data);
		#die();

		if ( $data == 'version' )
		{
			$$data = trim($$data);

			if ( !is_numeric($$data)
				|| ( $$data != clone_script_version )
				)
			{
				$errors[] = 'Version mismatch: Please make sure both ends are using the same clone script version, and upgrade one or both sites as necessary';
				break;
			}
		}
	}

	#echo '<pre>';
	#var_dump($errors, $user, $options, $ads);
	#echo '</pre>';
	#die();

	return array($errors, $user, $options, $ads);
} # end get_semiologic_config()


#
# import_semiologic_config()
#

function import_semiologic_config($user, $options, $ads)
{
	global $wpdb, $wp_rewrite;

	$user_data = get_object_vars($user);

	foreach ( array_keys($user_data) as $key )
	{
		if ( in_array($key, array(
					'ID',
					'user_login',
					'user_pass',
					'user_registered',
					'user_activation_key',
					'wp_user_level',
					'user_level',
					'user_status',
					'wp_capabilities'
					)
				) )
		{
			unset($user_data[$key]);
		}
	}

	$cur_user = wp_get_current_user();

	$user_data['ID'] = $cur_user->ID;

	#echo '<pre>';
	#var_dump($user, $user_data);
	#echo '</pre>';

	wp_update_user($user_data);

	# options
	foreach ( $options as $option_name => $option_value )
	{
		# autocorrect nav menu
		if ( $option_name == 'semiologic' )
		{
			$option_value['nav_menus'] = $GLOBALS['semiologic']['nav_menus'];
			$GLOBALS['semiologic'] = $option_value;
		}

		update_option($option_name, $option_value);
	}

	# ads
	foreach ( (array) $ads as $table_name => $table_data )
	{
		$table_name = mysql_real_escape_string($table_name);

		$wpdb->query("DELETE FROM {$wpdb->$table_name}");

		foreach ( (array) $table_data as $row )
		{
			$fields = "";
			$values = "";
			foreach ( $row as $field => $value )
			{
				$fields .= ( $fields ? ", " : "" ) . mysql_real_escape_string($field);
				$values .= ( $values ? ", " : "" ) . "'" . mysql_real_escape_string($value) . "'";
			}

			$wpdb->query("INSERT INTO {$wpdb->$table_name} ($fields) VALUES ($values);");

		}
	}

	# autocorrect rewrite rules
	$permalink_structure = get_option('permalink_structure');
	$category_base = get_option('category_base');

	if ( !got_mod_rewrite() )
	{
		if ( $permalink_structure
			&& !preg_match("/^\/index.php/i", $permalink_structure)
			)
		{
			$permalink_structure = '/index.php' . $permalink_structure;
		}

		if ( $category_base
			&& !preg_match("/^\/index.php/i", $category_base)
			)
		{
			$category_base = '/index.php' . $category_base;
		}
	}

	if ( !( is_file(ABSPATH . '.htaccess') && is_writable(ABSPATH . '.htaccess') ) )
	{
		$permalink_structure = '';
		$category_base = '';
	}

	$wp_rewrite->set_permalink_structure($permalink_structure);
	$wp_rewrite->set_category_base($category_base);

	$wpdb->hide_errors();

	# fire autoinstallers
	if ( file_exists(ABSPATH . 'wp-content/plugins/democracy/democracy.php') )
	{
		require_once ABSPATH . 'wp-content/plugins/democracy/democracy.php';
		jal_dem_install();
	}

	if ( file_exists(ABSPATH . 'wp-content/plugins/now-reading/now-reading.php') )
	{
		require_once ABSPATH . 'wp-content/plugins/now-reading/now-reading.php';
		nr_install();
	}

	$wpdb->show_errors();
} # end import_semiologic_config()
?>