<?php
if ( !class_exists('wiz_upgrade_cleanup') ) :
class wiz_upgrade_cleanup
{
	#
	# show_step()
	#

	function show_step()
	{
		echo '<h3>'
			. 'Download &rarr; Backup &rarr; Prepare &rarr; Upgrade &rarr; <u>Clean Up</u> &rarr; Done'
			. '</h3>';

		echo '<p>'
			. 'Your system files and database were upgraded successfully. Clicking the next button will:'
			. '</p>';

		echo '<ol>'
			. '<li>'
				. 'Change file permissions where applicable'
				. '</li>'
			. '<li>'
				. 'Delete the temporary folder\'s contents'
				. '</li>'
			. '<li>'
				. 'Delete the database backup'
				. '</li>'
			. '</ol>';

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

		foreach ( array('ftp_host', 'ftp_user', 'ftp_pass', 'ftp_path') as $var )
		{
			$_POST[$var] = trim(strip_tags(stripslashes($_POST[$var])));
		}

		$errors = array();
		$files = array();

		if ( sem_debug ) echo '<pre>';

		if ( !wiz_upgrade_cleanup::upgrade_perms() )
		{
			$errors[] = 'Failed to change file permissions';
		}
		elseif ( !wiz_upgrade_cleanup::clean_up() )
		{
			$errors[] = 'Failed to clean-up';
		}

		if ( sem_debug ) echo '</pre>';

		if ( empty($errors) )
		{
			return 'done';
		}

		echo '<div class="error">';

		echo '<ul>';

		foreach ( $errors as $error )
		{
			echo '<li>' . $error . '</li>';
		}

		echo '</ul>';

		echo '</div>';

		return $step;
	} # do_step()


	#
	# upgrade_perms()
	#

	function upgrade_perms()
	{
		if ( sem_debug ) echo "\n\n" . 'Upgrading permissions' . "\n\n";

		$tmp_path = ABSPATH . 'tmp/sem-pro';
		$site_path = rtrim($_POST['ftp_path'], '/');

		$do_dirs = array();

		if ( !( $files = opendir($tmp_path) ) )
		{
			return false;
		}

		while ( ( $file = readdir($files) ) !== false )
		{
			$file = trim($file, '/');

			if ( $file == '.' || $file == '..' || $file == 'wp-admin' || $file == 'wp-includes' )
			{
				continue;
			}

			if ( is_dir("$tmp_path/$file") )
			{
				$do_dirs[] = $file;
			}
		}

		closedir($files);

		if ( !empty($do_dirs) )
		{
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

			foreach ( $do_dirs as $dir )
			{
				if ( is_dir(ABSPATH . $dir) )
				{
					if ( !$ftp->chmod("$site_path/$dir", 0777) )
					{
						$ftp->quit(true);
						return false;
					}
				}
				elseif ( !$ftp->mkdir("$site_path/$dir")
					|| !$ftp->chmod("$site_path/$dir", 0777)
					)
				{
					$ftp->quit(true);
					return false;
				}
			}

			$ftp->quit(true);
			unset($ftp);
		}

		return true;
	} # upgrade_perms()


	#
	# clean_up()
	#

	function clean_up()
	{
		if ( sem_debug ) echo "\n\n" . 'Performing clean-up' . "\n\n";

		global $wpdb;
		global $table_prefix;

		wiz_upgrade_cleanup::rm(ABSPATH . 'tmp');

		$db_backup = 'backup__' . $table_prefix;;

		$tables = (array) $wpdb->get_col("
			SHOW	TABLES
			LIKE	'"
				. str_replace(
					array('_', '%'),
					array('\\_', '\\%'),
					"$db_backup"
					)
				. "%';");

		foreach ( $tables as $table )
		{
			$wpdb->query("
				DROP TABLE IF EXISTS `$table`;
				");
		}

		return true;
	} # clean_up()


	#
	# rm()
	#

	function rm($dir)
	{
		if ( is_dir($dir) )
		{
			if ( !( $files = opendir($dir) ) )
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

				if ( is_dir("$dir/$file") )
				{
					if ( !wiz_upgrade_cleanup::rm("$dir/$file") )
					{
						return false;
					}
				}
				else
				{
					unlink("$dir/$file");
				}
			}

			@rmdir($dir);
		}
		elseif ( file_exists($dir) )
		{
			@unlink($dir);
		}

		clearstatcache();

		return true;
	} # rm()
} # wiz_upgrade_cleanup
endif;
?>