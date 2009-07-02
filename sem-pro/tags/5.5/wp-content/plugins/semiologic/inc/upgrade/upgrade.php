<?php
if ( !class_exists('wiz_upgrade_upgrade') ) :
class wiz_upgrade_upgrade
{
	#
	# show_step()
	#

	function show_step()
	{
		echo '<h3>'
			. 'Download &rarr; Backup &rarr; Prepare &rarr; <u>Upgrade</u> &rarr; Clean Up &rarr; Done'
			. '</h3>';

		echo '<p>'
			. 'Your system is now ready to be upgraded. Clicking the next button will:'
			. '</p>';

		echo '<ol>'
			. '<li>'
				. 'Upgrade your files'
				. '</li>'
			. '<li>'
				. 'Upgrade your database'
				. '</li>'
			. '</ol>';

		echo '<p>'
			. 'Note: This critical step can take several minutes and will ignore abort requests. If you click the next button, refresh the page, close your browser window, or otherwise abort the process, the wizard will nonetheless carry on to the very end for the sake of not breaking your site -- but your site can momentarily seem broken until it\'s done.'
			. '</p>';

		echo '<p>'
			. 'On some servers, the load involved in this step will be such that browsers will either output a blank page, or invite you to download a php page in place of displaying it. If this happens to you, do not worry -- it\'s a known issue that we\'ve yet to find a workaround for, if it is even resolvable. Give the script another 5 minutes to make sure it\'s done (this step is fail-safe), and visit your site\'s front page as you normally would thereafter.'
			. '</p>';

		$keys = array(
			'ftp_host',
			'ftp_user',
			'ftp_pass',
			'ftp_path',
			);

		foreach ( $keys as $key )
		{
			echo '<input type="hidden"'
					. ' name="' . $key . '"'
					. ' value="' . htmlspecialchars($_POST[$key]) . '"'
					. ' />';
		}
	} # show_step()


	#
	# do_step()
	#

	function do_step($step)
	{
		set_time_limit(0);

		if ( !isset($_POST['one_click']) )
		{
			ignore_user_abort(true);
		}

		foreach ( array('ftp_host', 'ftp_user', 'ftp_pass', 'ftp_path') as $var )
		{
			$_POST[$var] = trim(strip_tags(stripslashes($_POST[$var])));
		}

		$errors = array();
		$files = array();

		if ( sem_debug ) echo '<pre>';

		if ( !wiz_upgrade_upgrade::scan_files($files) )
		{
			$errors[] = 'Failed to scan your files';
		}
		elseif ( !wiz_upgrade_upgrade::upgrade_files($files) )
		{
			$errors[] = 'Failed to upgrade your files';
		}
		elseif ( !wiz_upgrade_upgrade::upgrade_db() )
		{
			$errors[] = 'Failed to upgrade your database';
		}

		if ( sem_debug ) echo '</pre>';

		if ( empty($errors) )
		{
			if ( isset($_POST['one_click']) )
			{
				return wiz_upgrade_cleanup::do_step('cleanup');
			}
			else
			{
				ignore_user_abort(false);
				return 'cleanup';
			}
		}

		echo '<div class="error">';

		echo '<ul>';

		foreach ( $errors as $error )
		{
			echo '<li>' . $error . '</li>';
		}

		echo '</ul>';

		echo '</div>';

		ignore_user_abort(false);
		return $step;
	} # do_step()


	#
	# scan_files()
	#

	function scan_files(&$do_files, $dir = '')
	{
		if ( sem_debug && !$dir ) echo "\n\n" . 'Building file list' . "\n\n";

		$site_path = rtrim(ABSPATH . $dir, '/');
		$tmp_path = rtrim(ABSPATH . 'tmp/sem-pro/' . $dir, '/');

		$do_dirs = array();

		if ( !( $files = opendir($tmp_path) ) )
		{
			return false;
		}

		while ( ( $file = readdir($files) ) !== false )
		{
			$file = trim($file, '/');

			if ( $file == '.' || $file == '..' )
			{
				continue;
			}

			if ( is_dir("$tmp_path/$file") )
			{
				$do_dirs[] = trim("$dir/$file", '/');
			}
			else
			{
				$do_files[$dir][] = $file;
			}
		}

		closedir($files);

		foreach ( $do_dirs as $do_dir )
		{
			if ( !wiz_upgrade_upgrade::scan_files($do_files, $do_dir) )
			{
				return false;
			}
		}

		return true;
	} # scan_files()


	#
	# upgrade_files()
	#

	function upgrade_files(&$do_files)
	{
		$ftp_dirs = array();
		$ftp_files = array();

		foreach ( array_keys($do_files) as $dir )
		{
			$site_path = rtrim(ABSPATH . $dir, '/');

			if ( !is_dir($site_path) )
			{
				$ftp_dirs[] = $dir;
			}
		}

		foreach ( $ftp_dirs as $dir )
		{
			while ( ( $dir = dirname($dir) )
				&& !is_dir(ABSPATH . $dir)
				&& !in_array($dir, $ftp_dirs)
				)
			{
				array_unshift($ftp_dirs, $dir);

				if ( file_exists(ABSPATH . $dir) )
				{
					echo '<div class="error">'
						. __('File name conflict: ') . ABSPATH . $dir
						. '</div>';

					return false;
				}
			}
		}

		sort($ftp_dirs);

		$ftp_files = $do_files;

		if ( !empty($ftp_dirs)
			&& !wiz_upgrade_upgrade::ftp_folders($ftp_dirs)
			)
		{
			return false;
		}

		if ( !empty($ftp_files)
			&& !wiz_upgrade_upgrade::ftp_files($ftp_files)
			)
		{
			return false;
		}

		return true;
	} # upgrade_files()


	#
	# ftp_folders()
	#

	function ftp_folders($dirs)
	{
		if ( sem_debug ) echo "\n\n" . 'Creating new folders' . "\n\n";

		sleep(2);
		$ftp = new ftp(sem_debug, sem_debug);

		if ( !$ftp->connect($_POST['ftp_host'])
			|| !$ftp->login($_POST['ftp_user'], $_POST['ftp_pass'])
			|| !$ftp->Passive(true)
			)
		{
			$ftp->quit(true);
			return false;
		}

		foreach ( $dirs as $dir )
		{
			$site_path = rtrim($_POST['ftp_path'] . $dir, '/');

			if ( !$ftp->mkdir("$site_path") )
			{
				$ftp->quit(true);
				return false;
			}
		}

		$ftp->quit(true);
		clearstatcache();

		return true;
	} # ftp_folders()


	#
	# ftp_files()
	#

	function ftp_files($files)
	{
		if ( sem_debug ) echo "\n\n" . 'Uploading updated files' . "\n\n";

		foreach ( array_keys($files) as $dir )
		{
			$site_path = rtrim($_POST['ftp_path'] . $dir, '/');
			$tmp_path = rtrim(ABSPATH . 'tmp/sem-pro/' . $dir, '/');

			sleep(2);
			$ftp = new ftp(sem_debug, sem_debug);

			if ( !$ftp->connect($_POST['ftp_host'])
				|| !$ftp->login($_POST['ftp_user'], $_POST['ftp_pass'])
				|| !$ftp->Passive(true)
				)
			{
				$ftp->quit(true);
				return false;
			}

			foreach ( $files[$dir] as $file )
			{
				if ( !$ftp->put("$tmp_path/$file", "$site_path/$file") )
				{
					$ftp->quit(true);
					return false;
				}
			}

			$ftp->quit(true);
			clearstatcache();
		}

		return true;
	} # ftp_files()


	#
	# upgrade_db()
	#

	function upgrade_db()
	{
		if ( sem_debug ) echo "\n\n" . 'Upgrading database' . "\n\n";

		$url = trailingslashit(get_option('siteurl')) . 'wp-admin/upgrade.php?step=1';

		sem_http::get($url);

		return true;
	} # upgrade_db()
} # wiz_upgrade_upgrade
endif;
?>