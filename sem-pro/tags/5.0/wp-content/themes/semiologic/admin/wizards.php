<?php
class sem_wizards
{
	#
	# init()
	#

	function init()
	{
		add_action('admin_menu', array('sem_wizards', 'admin_menu'));

		sem_wizards::register_step('done', array('sem_wizards', 'show_done'), array('sem_wizards', 'do_done'));
	} # init()


	#
	# admin_menu()
	#

	function admin_menu()
	{
		global $sem_wizards;

		$sem_wizards = array();

		foreach ( (array) glob(sem_path . '/wizards/*/wizard.php') as $wizard_id )
		{
			$wizard_id = basename(dirname($wizard_id));

			if ( $wizard_data = sem_wizards::get_data($wizard_id) )
			{
				$wizard_data['callback'] = create_function('', '
					include_once sem_path . "/wizards/' . $wizard_id . '/wizard.php";
					return sem_wizards::display("' . $wizard_id . '");
					');
				$sem_wizards[$wizard_id] = $wizard_data;
			}
		}


		if ( !empty($sem_wizards) )
		{
			$wiz_id = key($sem_wizards);

			add_menu_page(
				__('Wizards'),
				__('Wizards'),
				'administrator',
				basename(__FILE__),
				$sem_wizards[$wiz_id]['callback']
				);


			foreach ( $sem_wizards as $wizard_id => $wizard_data )
			{
				add_submenu_page(
					basename(__FILE__),
					$wizard_data['name'],
					$wizard_data['name'],
					'administrator',
					$wizard_id == $wiz_id ? basename(__FILE__) : 'wizards/' . $wizard_id . '.php',
					$sem_wizards[$wizard_id]['callback']
					);
			}
		}
	} # admin_menu()


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

		echo '<h3 style="text-align: center;">'
				. $wizard_data['name']
				. ' '
				. $wizard_data['version']
			. '</h3>';

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
			if ( !sem_wizards::check_preconditions() )
			{
				return;
			}

			$step = 'start';
		}
		else
		{
			check_admin_referer('sem_wizard');
			$step = $_POST['wiz_step'];
			$step = sem_wizards::do_step($wizard_id, $step);
		}

		echo '<div class="wrap">';

		echo '<form method="post" action="" style="padding: 12px;">';

		if ( function_exists('wp_nonce_field') ) wp_nonce_field('sem_wizard');

		echo '<input type="hidden" name="wiz_step" value="' . $step . '" />';

		sem_wizards::show_step($wizard_id, $step);

		if ( $step == 'start' )
		{
			echo '<div class="submit">';
			echo '<input type="submit" value="' . __('Start') . ' &raquo;"' . ( !sem_pro ? ' disabled="disabled"' : '' ) . ' />';
			echo '</div>';
		}
		elseif ( $step != 'done' )
		{
			echo '<div class="submit">';
			echo '<input type="submit" value="' . __('Next') . ' &raquo;"' . ( !sem_pro ? ' disabled="disabled"' : '' ) . ' />';
			echo '</div>';
		}

		echo '</form>'
			. '</div>';
	} # display()


	#
	# get_data()
	#

	function get_data($wizard_id)
	{
		$wizard_data = file_get_contents(sem_path . '/wizards/' . $wizard_id . '/wizard.php');
		$wizard_data = str_replace("\r", "\n", $wizard_data);

		if ( !preg_match('/Wizard(?:\s+Name)?\s*:(.*)/i', $wizard_data, $name)
			|| !preg_match('/Version\s*:(.*)/i', $wizard_data, $version)
			)
		{
			return false;
		}

		return array(
			'name' => trim(end($name)),
			'version' => trim(end($version)),
			);
	} # get_data()


	#
	# do_done()
	#

	function do_done($step)
	{
		return 'done';
	} # do_done()


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
	# check_preconditions()
	#

	function check_preconditions()
	{
		if ( !sem_pro )
		{
			pro_feature_notice();
		}

		if ( $_SERVER['HTTP_HOST'] == 'localhost' )
		{
			# for use on Denis' PC
			return true;
		}

		if ( $GLOBALS['is_IIS'] )
		{
			$errors[] = 'This is a Windows server.';
		}

		if ( ini_get('safe_mode') )
		{
			$errors[] = 'Safe mode is on.';
		}

		if ( version_compare(phpversion(), '4.3', '<') )
		{
			$errors[] = 'Your php version is lower than 4.3.';
		}

		$mysql_version = preg_replace('|[^0-9\.]|', '', @mysql_get_server_info());

		if ( version_compare($mysql_version, '4.1', '<') )
		{
			$errors[] = 'Your MySQL version is lower than 4.1.';
		}

		$out = false;
		@exec("whoami", $out);

		if ( !$out )
		{
			$errors[] = '<a href="http://www.php.net/manual/en/ref.exec.php">Program execution functions</a> are disabled on your server.';
		}

		if ( !set_time_limit(30) )
		{
			$errors[] = 'Your server disallows to override the <a href="http://localhost/phpdoc/function.set-time-limit.html">maximum script execution time</a>.';
		}

		if ( !ignore_user_abort() )
		{
			ignore_user_abort(true);

			if ( !ignore_user_abort() )
			{
				$errors[] = 'Your server disallows to ignore <a href="http://localhost/phpdoc/function.ignore-user-abort.html">user aborts</a>.';
			}

			ignore_user_abort(false);
		}

		if ( !function_exists('gzopen') )
		{
			$errors[] = 'Your server offers no zip support. Most hosts will turn <a href="http://www.php.net/manual/en/ref.zlib.php">zip support</a> on when asked.';
		}

		if ( !function_exists('ftp_connect') && !ini_get('allow_url_fopen') )
		{
			$errors[] = 'Your server offers no ftp support. Most hosts will turn <a href="http://www.php.net/manual/en/ref.ftp.php">ftp support</a> on when asked.';
		}

		if ( empty($errors) )
		{
			return true;
		}

		echo '<div class="wrap">';

		echo '<h3>'
			. 'Preconditions were not met...'
			. '</h3>';

		echo '<ul>';

		foreach ( $errors as $error )
		{
			echo '<li>' . $error . '</li>';
		}

		echo '</ul>';

		echo '<p>'
			. __('Resolving the above will involve opening a trouble ticket with your host. Consider <a href="http://www.semiologic.com/resources/wp-basics/wordpress-server-requirements/">changing hosts</a> if all else fails.')
			. '</p>';

		echo '</div>';

		return false;
	} # check_preconditions()
} # sem_wizards

sem_wizards::init();
?>