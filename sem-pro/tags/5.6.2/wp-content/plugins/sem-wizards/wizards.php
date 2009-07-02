<?php

class sem_wizards
{
	#
	# init()
	#

	function init()
	{
		add_action('admin_menu', array('sem_wizards', 'admin_menu'));
		
		if ( $_GET['page'] == plugin_basename(__FILE__) )
		{
			$GLOBALS['title'] = 'Wizards';
		}
		
		sem_wizards::register_step('done', array('sem_wizards', 'show_done'), array('sem_wizards', 'do_done'));
	} # init()
	
	
	#
	# admin_menu()
	#
	
	function admin_menu()
	{
		global $menu;
		global $sem_wizards;
		
		$sem_wizards = array();
		
		add_menu_page(
			__('Wizards'),
			__('Wizards'),
			'administrator',
			__FILE__,
			array('sem_wizards', 'admin_page')
			);
		
		if ( is_array($menu) && is_array($menu[30]) && $menu[30][0] == __('Settings') )
		{
			$menu_item = array_pop($menu);
			$menu[38] = $menu_item;
		}
		
		$wizard_files = (array) glob(sem_wizards_path . '/*/wizard.php');
		$wizard_files = array_map('plugin_basename', $wizard_files);
		
		# setup submenu if in wizard section
		if ( in_array($_GET['page'], array_merge($wizard_files, (array) plugin_basename(__FILE__))) )
		{
			foreach ( $wizard_files as $wizard_file )
			{
				$wizard_id = basename(dirname($wizard_file));
				
				if ( $wizard_data = sem_wizards::get_data($wizard_id) )
				{
					$wizard_data['file'] = $wizard_file;
					$wizard_data['callback'] = create_function('',
						'return sem_wizards::display("' . $wizard_id . '");'
						);
					$sem_wizards[$wizard_id] = $wizard_data;

					add_submenu_page(
						plugin_basename(__FILE__),
						$wizard_data['name'],
						$wizard_data['name'],
						'administrator',
						$wizard_file,
						$wizard_data['callback']
						);
				
					if ( $_GET['page'] == $wizard_file )
					{
						include ABSPATH . PLUGINDIR . '/' . $wizard_file;
					}
				}
			}
		}
	} # admin_menu()
	
	
	#
	# admin_page()
	#
	
	function admin_page()
	{
		echo '<div class="wrap">' . "\n";
		
		echo '<h2>' . 'Wizard Preconditions' . '</h2>';
		
		$errors = false;
		
		echo '<h3>'
			. 'All Wizards'
			. '</h3>';
		
		echo '<p>' . 'Consider looking into these <a href="http://www.semiologic.com/resources/wp-basics/wordpress-hosts/">recommended hosts</a> if your server fails to meet the preconditions that follow.</p>';
		
		echo '<table class="form-table">';
		
		foreach ( array(
			'server',
			'php_version',
			'mysql_version',
			'safe_mode',
			) as $check )
		{
			$success = sem_wizards::check_precondition($check, $label, $message);
			
			$errors |= !$success;
			
			echo '<tr valign="top">'
				. '<th scope="row">'
				. $label
				. '</th>'
				. '<td>'
				. $message
				. '</td>'
				. '<td style="width: 80px; text-align: right;">'
				. ( $success
					? 'OK'
					: ( '<b>' . 'Not OK' . '</b>' )
					)
				. '</td>'
				. '</tr>';
		}
		
		echo '</table>';

		echo '<h3>'
			. 'Upgrade Wizard'
			. '</h3>';
		
		echo '<table class="form-table">';
		
		foreach ( array(
			'api_key',
			'shell',
			'time_limit',
			'user_abort',
			'zip_support',
			'ftp_support',
			) as $check )
		{
			$success = sem_wizards::check_precondition($check, $label, $message);
			
			$errors |= !$success;
			
			echo '<tr valign="top">'
				. '<th scope="row">'
				. $label
				. '</th>'
				. '<td>'
				. $message
				. '</td>'
				. '<td style="width: 80px; text-align: right;">'
				. ( $success
					? 'OK'
					: ( '<b>' . 'Not OK' . '</b>' )
					)
				. '</td>'
				. '</tr>';
		}
		
		echo '</table>';

		echo '</div>';
	} # admin_page()
	
	
	#
	# check_precondition()
	#
	
	function check_precondition($check, &$label, &$message)
	{
		$more_info = ' <a href="http://www.semiologic.com/resources/wp-basics/wordpress-server-requirements/">'
			. 'More Info'
			. '</a>';
		
		switch ( $check )
		{
		case 'server':
			$label = 'Server Type';
			$success_msg = 'Apache';
			if ( $GLOBALS['is_IIS'] )
			{
				$error_msg = 'Your server is running IIS.' . $more_info;
			}
			break;
		
		case 'safe_mode':
			$label = 'Safe Mode';
			$success_msg = 'Disabled';
			if ( ini_get('safe_mode') )
			{
				$error_msg = 'Your server is running in safe mode.' . $more_info;
			}
			break;
		
		case 'php_version':
			$label = 'php Version';
			$success_msg = phpversion();
			
			if ( version_compare(phpversion(), '4.3', '<') )
			{
				$error_msg = phpversion() . '.' . $more_info;
			}
			elseif ( version_compare(phpversion(), '5.2', '<') )
			{
				$success_msg = phpversion() . '. Notice: Semiologic 6 may require php 5.2.';
			}
			break;
			
		case 'mysql_version':
			$label = 'MySQL Version';
			$success_msg = mysql_get_server_info();
			
			if ( version_compare(mysql_get_server_info(), '4.1', '<') )
			{
				$error_msg = mysql_get_server_info() . '.' . $more_info;
			}
			elseif ( version_compare(mysql_get_server_info(), '5.0.51', '=') )
			{
				$error_msg = mysql_get_server_info() . '.'
					. ' This version of MySQL has a <a href="http://bugs.mysql.com/bug.php?id=35181">major bug</a>.';
			}
			break;
		
		case 'shell':
			$label = 'php exec()';
			
			$out = false;
			@exec("ls " . escapeshellarg(ABSPATH . 'index.php'), $out);
			
			if ( current($out) == ABSPATH . 'index.php' )
			{
				$success_msg = 'Enabled';
			}
			else
			{
				$error_msg = '<a href="http://www.php.net/manual/en/ref.exec.php">Program execution functions</a> are disabled on your server.';
			}
			break;
		
		case 'time_limit':
			$label = 'Script Timeout';
			$success_msg = 'Overridable';
			if ( !set_time_limit(30) )
			{
				$error_msg = 'Your server disallows to override the <a href="http://localhost/phpdoc/function.set-time-limit.html">maximum script execution time</a>.';
			}
			break;
		
		case 'user_abort':
			$label = 'Script Abort';
			$success_msg = 'Overridable';
			
			if ( !ignore_user_abort() )
			{
				ignore_user_abort(true);

				if ( !ignore_user_abort() )
				{
					$error_msg = 'Your server disallows to ignore <a href="http://localhost/phpdoc/function.ignore-user-abort.html">user aborts</a>.';
				}

				ignore_user_abort(false);
			}
			break;
			
		case 'zip_support':
			$label = 'Zip Support';
			$success_msg = 'Enabled';
			
			if ( !function_exists('gzopen') )
			{
				$error = 'Your server offers no zip support. Most hosts will turn <a href="http://www.php.net/manual/en/ref.zlib.php">zip support</a> on when asked.';
			}
			break;
		
		case 'ftp_support':
			$label = 'FTP Support';
			$success_msg = 'Enabled';
			
			if ( !function_exists('ftp_connect') && !ini_get('allow_url_fopen') )
			{
				$error_msg = 'Your server offers no ftp support. Most hosts will turn <a href="http://www.php.net/manual/en/ref.ftp.php">ftp support</a> on when asked.';
			}
			break;
		
		case 'api_key':
			$label = 'Semiologic API Key';
			$success_msg = '<a href="http://members.semiologic.com?user_key=' . get_option('sem_api_key') . '">Membership Status</a>';
		
			if ( !get_option('sem_api_key') )
			{
				$error_msg = 'Please enter your <a href="./options-general.php?page=version-checker/sem-api-key.php">Semiologic API Key</a>.';
			}
			break;
		}
		
		if ( $error_msg )
		{
			$message = $error_msg;
			return false;
		}
		else
		{
			$message = $success_msg;
			return true;
		}
	} # check_precondition()
	
	
	#
	# register_step()
	#

	function register_step($step, $show_step, $do_step = null)
	{
		global $sem_wizard_steps;

		$sem_wizard_steps[$step]['show_step'] = $show_step;

		if ( $do_step )
		{
			$sem_wizard_steps[$step]['do_step'] = $do_step;
		}
	} # register_step()


	#
	# show_step()
	#

	function show_step($wizard_id, $step)
	{
		global $sem_wizards;
		global $sem_wizard_steps;

		$wizard_data = $sem_wizards[$wizard_id];

		echo '<h2>'
				. $wizard_data['name']
			. '</h2>';

		if ( $callback = $sem_wizard_steps[$step]['show_step'] )
		{
			call_user_func($callback);
		}
	} # show_step()


	#
	# do_step()
	#

	function do_step($wizard_id, $step)
	{
		global $sem_wizard_steps;

		if ( $callback = $sem_wizard_steps[$step]['do_step'] )
		{
			$step = call_user_func($callback, $step);
		}

		return $step;
	} # do_step()


	#
	# display()
	#

	function display($wizard_id)
	{
		if ( !$_POST['wiz_step'] )
		{
			$step = 'start';
		}
		else
		{
			check_admin_referer('sem_wizard');
			$step = $_POST['wiz_step'];
			$step = sem_wizards::do_step($wizard_id, $step);
		}

		echo '<div class="wrap">'
		 	. '<form method="post" action="">';

		if ( function_exists('wp_nonce_field') ) wp_nonce_field('sem_wizard');

		echo '<input type="hidden" name="wiz_step" value="' . $step . '" />';

		sem_wizards::show_step($wizard_id, $step);

		if ( $step == 'start' )
		{
			echo '<div class="submit">';
			echo '<input type="submit" value="' . __('Start') . ' &raquo;" />';
			echo '</div>';
		}
		elseif ( $step != 'done' )
		{
			echo '<div class="submit">';
			echo '<input type="submit" value="' . __('Next') . ' &raquo;" />';
			echo '</div>';
		}

		echo '</form>'
			. '</div>';
	} # display()


	#
	# show_done()
	#

	function show_done()
	{
		echo "<p>"
				. "<strong>"
				. __('Wizard Done!')
				. "</strong>"
			. "</p>\n";
	} # show_done()


	#
	# do_done()
	#

	function do_done($step)
	{
		return 'done';
	} # do_done()


	#
	# get_data()
	#

	function get_data($wizard_id)
	{
		$wizard_data = file_get_contents(sem_wizards_path . '/' . $wizard_id . '/wizard.php');
		$wizard_data = str_replace("\r", "\n", $wizard_data);

		if ( !preg_match('/Wizard(?:\s+Name)?\s*:(.*)/i', $wizard_data, $name) )
		{
			return false;
		}

		return array('name' => trim(end($name)));
	} # get_data()
} # sem_wizards

sem_wizards::init();
?>