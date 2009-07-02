<?php
if ( !class_exists('wiz_upgrade_prepare') ) :
class wiz_upgrade_prepare
{
	#
	# show_step()
	#

	function show_step()
	{
		echo '<h3>'
			. 'Download &rarr; Backup &rarr; <u>Prepare</u> &rarr; Upgrade &rarr; Clean Up &rarr; Done'
			. '</h3>';

		echo '<p>'
			. 'Your system is now ready to be upgraded. For information:'
			. '</p>';

		echo '<ul>'
			. '<li>'
				. 'A backup your current files is located in tmp/backup'
				. '</li>'
			. '<li>'
				. 'Your database contains a set of backup tables, prefixed with backup_'
				. '</li>'
			. '</ul>';

		echo '<p>'
			. 'Clicking the next button will extract the list of files that need not be upgraded on your site, in order to avoid an all-too resource hungry ftp upload in the next step. It can take a few minutes to complete.'
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

		$errors = array();

		foreach ( array('ftp_host', 'ftp_user', 'ftp_pass') as $var )
		{
			$_POST[$var] = trim(strip_tags(stripslashes($_POST[$var])));
		}

		if ( sem_debug ) echo '<pre>';

		if ( !wiz_upgrade_prepare::scan_files($files) )
		{
			$errors[] = 'Failed to scan your files';
		}

		if ( sem_debug ) echo '</pre>';

		if ( empty($errors) )
		{
			if ( isset($_POST['one_click']) )
			{
				return wiz_upgrade_upgrade::do_step('upgrade');
			}
			else
			{
				return 'upgrade';
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

		return $step;
	} # do_step()


	#
	# scan_files()
	#

	function scan_files($dir = '')
	{
		if ( sem_debug && !$dir ) echo "\n\n" . 'Scanning files for updates' . "\n\n";

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
			elseif ( !file_exists("$site_path/$file")
					|| filesize("$site_path/$file") != filesize("$tmp_path/$file")
					|| exec("cmp " . escapeshellarg("$site_path/$file") . " " . escapeshellarg("$tmp_path/$file"))
					)
			{
				continue;
			}
			else
			{
				@unlink("$tmp_path/$file");
			}
		}

		closedir($files);

		foreach ( $do_dirs as $do_dir )
		{
			if ( !wiz_upgrade_prepare::scan_files($do_dir) )
			{
				return false;
			}
		}

		return true;
	} # scan_files()
} # wiz_upgrade_prepare
endif;
?>