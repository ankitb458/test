<?php
#
# Wizard Name: Upgrade
#

if ( !class_exists('ftp') )
{
	include sem_wizards_path . '/ftp.php';
}

if ( !class_exists('PclZip') )
{
	include ABSPATH . 'wp-admin/includes/class-pclzip.php';
}

class wiz_upgrade
{
	#
	# init()
	#
	
	function init()
	{
		if ( $_GET['page'] == plugin_basename(__FILE__) )
		{
			$GLOBALS['title'] = 'Upgrade Wizard';
		}
		
		@session_start();
		
		sem_wizards::register_step(
			'start',
			array('wiz_upgrade', 'download'),
			array('wiz_upgrade', 'do_download')
			);

		sem_wizards::register_step(
			'backup',
			array('wiz_upgrade', 'backup'),
			array('wiz_upgrade', 'do_backup')
			);

		sem_wizards::register_step(
			'prepare',
			array('wiz_upgrade', 'prepare'),
			array('wiz_upgrade', 'do_prepare')
			);

		sem_wizards::register_step(
			'upgrade',
			array('wiz_upgrade', 'upgrade'),
			array('wiz_upgrade', 'do_upgrade')
			);

		sem_wizards::register_step(
			'cleanup',
			array('wiz_upgrade', 'cleanup'),
			array('wiz_upgrade', 'do_cleanup')
			);

		sem_wizards::register_step(
			'done',
			array('wiz_upgrade', 'done')
			);
	} # init()
	
	
	#
	# download()
	#
	
	function download()
	{
		echo '<h3>'
			. '<u>Download</u> &rarr; Backup &rarr; Prepare &rarr; Upgrade &rarr; Clean Up &rarr; Done'
			. '</h3>';

		echo '<p>'
			. 'The upgrade wizard will let you automatically upgrade your Semiologic Pro site.'
			. '</p>';

		$versions = wiz_upgrade::get_versions();
		
		if ( $versions === false )
		{
			echo '<p>'
				. 'A network error occurred. Please try again in a half hour.'
				. '</p>';
			
			return;
		}

		if ( version_compare(sem_version, $versions['stable'], '>') )
		{
			echo '<p>'
				. 'Thank you for helping us weed out issues in the next version of Semiologic Pro!'
				. '</p>';
			
			echo '<p>'
				. sprintf('Enter your site\'s ftp details to install (or reinstall) the latest <b style="color: firebrick;">bleeding edge version: %s</b>', $versions['bleeding'])
				. '</p>';

			echo '<input type="hidden" name="sem_pro_version" value="bleeding" />';
		}
		else
		{
			if ( version_compare(sem_version, $versions['stable'], '=') )
			{
				echo '<p style="padding: 20px; border: solid 1px black; background-color: #ffeeee;">'
					. '<strong>Your site is up to date</strong>.'
					. '</p>';
			}
			
			if ( $versions['stable'] != $versions['bleeding'] )
			{
				echo '<p>'
					. 'Select your preferred version of Semiologic Pro and enter your site\'s ftp details to proceed:'
					. '</p>';

				echo '<ul style="list-style: none;">'
					. '<li>'
					. '<label>'
					. '<input type="radio" name="sem_pro_version" value="stable" checked="checked" />'
					. '&nbsp;'
					. sprintf('<b style="color: forestgreen;">Stable version: %s</b>', $versions['stable'])
					. '</label>'
					. '</li>'
					. '<li>'
					. '<label>'
					. '<input type="radio" name="sem_pro_version" value="bleeding"'
						. ' />'
					. '&nbsp;'
					. sprintf('<b style="color: firebrick;">Bleeding edge version: %s</b>', $versions['bleeding'])
					. '</label>'
					. '</li>'
					. '</ul>';

				echo '<p>'
					. 'Some development cycle jargon for reference:'
					. '</p>';

				echo '<ul>'
					. '<li>'
					. '<b>Alpha</b> means we\'re still adding features. For test sites only.'
					. '</li>'
					. '<li>'
					. '<b>Beta</b> means we\'re no longer adding features. For test sites only.'
					. '</li>'
					. '<li>'
					. '<b>Release Candidate</b> (RC) means it\'s OK to try it on a production site.'
					. '</li>'
					. '</ul>';
			}
			else
			{
				echo '<p>'
					. sprintf('Enter your site\'s ftp details to install (or reinstall) the latest <b style="color: forestgreen;">stable version: %s</b>', $versions['stable'])
					. '</p>';
				
				echo '<input type="hidden" name="sem_pro_version" value="stable" />';
			}
		}
		
		if ( !get_option('sem_api_key') )
		{
			echo '<p>'
				. 'Please enter your <a href="./options-general.php?page=version-checker/sem-api-key.php">Semiologic API Key</a> before continuing.'
				. '</p>';
		}

		$labels = array(
			'ftp_host' => 'FTP Host',
			'ftp_user' => 'FTP Username',
			'ftp_pass' => 'FTP Password',
			);

		foreach ( array_keys($labels) as $key )
		{
			echo '<p>'
				. '<label>'
				. $labels[$key] . ':'
				. '<br />'
				. '<input type="' . ( $key == 'ftp_pass' ? 'password' : 'text' ) . '" class="code" size="58"'
					. ( !get_option('sem_api_key')
						? ' disabled="disabled"'
						: ''
						)
					. ' name="' . $key . '"'
					. ' value="' . ( $key == 'ftp_pass' ? '' : htmlspecialchars($_SESSION[$key]) ) . '"'
					. ' />'
				. '</label>'
				. '</p>';
		}

		echo '<p>'
			. 'The upgrade wizard will autodetect your ftp path'
			. '</p>';

		echo '<p>'
			. 'Note: The download usually takes a few minutes, and the upgrade process can take as much as 10 minutes on a loaded server.'
			. '</p>';
	} # download()
	
	
	#
	# backup()
	#
	
	function backup()
	{
		echo '<h3>'
			. 'Download &rarr; <u>Backup</u> &rarr; Prepare &rarr; Upgrade &rarr; Clean Up &rarr; Done'
			. '</h3>';

		echo '<p>'
			. 'Semiologic Pro files were successfully downloaded and extracted to your site\'s tmp/sem-pro folder. Clicking the next button will:'
			. '</p>';

		echo '<ol>'
			. '<li>'
				. 'Backup your files to its tmp/backup folder'
				. '</li>'
			. '<li>'
				. 'Backup your database by creating folders prefixed with backup_'
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
	} # backup()
	
	
	#
	# prepare()
	#
	
	function prepare()
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
	} # prepare()
	
	
	#
	# upgrade()
	#
	
	function upgrade()
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
	} # upgrade()
	
	
	#
	# cleanup()
	#
	
	function cleanup()
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
	} # cleanup()
	
	
	#
	# done()
	#
	
	function done()
	{
		echo '<h3>'
			. 'Download &rarr; Backup &rarr; Prepare &rarr; Upgrade &rarr; Clean Up &rarr; <u>Done</u>'
			. '</h3>';

		echo '<p>'
			. 'Congratulations! Your system has been upgraded successfully.'
			. '</p>';
	} # done()
	
	
	#
	# do_download()
	#
	
	function do_download($step)
	{
		set_time_limit(0);

		$errors = array();

		$file = ABSPATH . 'tmp/' . md5(uniqid(rand(), true)) . '.zip';

		foreach ( array('ftp_host', 'ftp_user', 'ftp_pass') as $var )
		{
			$_POST[$var] = trim(strip_tags(stripslashes($_POST[$var])));
		}

		if ( sem_debug ) echo '<pre>';

		#wiz_upgrade::clean_up();

		$ftp = new ftp(sem_debug, sem_debug);

		if ( !$_POST['ftp_host']
			|| !$_POST['ftp_user']
			|| !$_POST['ftp_pass']
			)
		{
			$errors[] = 'Invalid ftp details';
		}
		elseif ( !$ftp->connect($_POST['ftp_host'])
			|| !$ftp->login($_POST['ftp_user'], $_POST['ftp_pass'])
			|| !$ftp->Passive(true)
			)
		{
			$errors[] = 'Failed to connect to ftp';
		}
		elseif ( !wiz_upgrade::scan_path($ftp) )
		{
			$errors[] = 'Failed to identify ftp path';
		}
		elseif ( !wiz_upgrade::create_tmp($ftp) )
		{
			$errors[] = 'Failed create tmp folder';
		}
		elseif ( !$ftp->quit(true) )
		{
			$errors[] = 'An ftp error occurred';
		}
		elseif ( !wiz_upgrade::download_zip($file) )
		{
			if ( !@fopen($file, 'wb') )
			{
				$errors[] = 'Failed to create file using <a href="http://www.php.net/manual/en/function.fopen.php">fopen</a>: fopen(\'' . $file . '\', \'wb\'). This is due to a server configuration problem (<a href="http://www.php.net/manual/en/function.clearstatcache.php">clearstatcache</a> is not working properly) that can only be resolved by your host.';
			}
			else
			{
				$errors[] = 'Failed to download the Semiologic Pro files. This likely has one of three reasons. It may be that your API key (Settings / API Key) is not set. It can be that your <a href="http://members.semiologic.com">membership</a> is expired. Or it may be that high traffic is bringing semiologic.com to a crawl (try again in an hour or so).';
			}
		}
		elseif ( !wiz_upgrade::extract_files($file, true) )
		{
			$errors[] = 'Failed to extract the Semiologic Pro files (tried twice).';
		}

		@unlink($file);
		exec("chmod -R ugo+w " . escapeshellarg(ABSPATH . 'tmp'));

		if ( sem_debug ) echo '</pre>';

		if ( empty($errors) )
		{
			return 'backup';
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
	} # do_download()
	
	
	#
	# do_backup()
	#
	
	function do_backup($step)
	{
		set_time_limit(0);

		$errors = array();

		foreach ( array('ftp_host', 'ftp_user', 'ftp_pass') as $var )
		{
			$_POST[$var] = trim(strip_tags(stripslashes($_POST[$var])));
		}

		if ( sem_debug ) echo '<pre>';

		if ( !wiz_upgrade::backup_files() )
		{
			$errors[] = 'Failed to backup the files.';
		}
		elseif ( !wiz_upgrade::backup_db() )
		{
			$errors[] = 'Failed to backup the database.';
		}

		exec("chmod -R ugo+w " . escapeshellarg(ABSPATH . 'tmp'));

		if ( sem_debug ) echo '</pre>';

		if ( empty($errors) )
		{
			return 'prepare';
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
	} # do_backup()
	
	
	#
	# do_prepare()
	#
	
	function do_prepare($step)
	{
		set_time_limit(0);

		$errors = array();

		foreach ( array('ftp_host', 'ftp_user', 'ftp_pass') as $var )
		{
			$_POST[$var] = trim(strip_tags(stripslashes($_POST[$var])));
		}
		
		$_POST['ftp_host'] = preg_replace("/^.+:\/\//", '', $_POST['ftp_host']);

		if ( sem_debug ) echo '<pre>';

		if ( !wiz_upgrade::prepare_files($files) )
		{
			$errors[] = 'Failed to scan your files';
		}

		if ( sem_debug ) echo '</pre>';

		if ( empty($errors) )
		{
			return 'upgrade';
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
	} # do_prepare()
	
	
	#
	# do_upgrade()
	#
	
	function do_upgrade($step)
	{
		set_time_limit(0);
		ignore_user_abort(true);

		foreach ( array('ftp_host', 'ftp_user', 'ftp_pass', 'ftp_path') as $var )
		{
			$_POST[$var] = trim(strip_tags(stripslashes($_POST[$var])));
		}

		$errors = array();
		$files = array();

		if ( sem_debug ) echo '<pre>';

		if ( !wiz_upgrade::scan_files($files) )
		{
			$errors[] = 'Failed to scan your files';
		}
		elseif ( !wiz_upgrade::upgrade_files($files) )
		{
			$errors[] = 'Failed to upgrade your files';
		}
		elseif ( !wiz_upgrade::upgrade_db() )
		{
			$errors[] = 'Failed to upgrade your database';
		}

		if ( sem_debug ) echo '</pre>';
		
		ignore_user_abort(false);

		if ( empty($errors) )
		{
			return 'cleanup';
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
	} # do_upgrade()
	
	
	#
	# do_cleanup()
	#
	
	function do_cleanup($step)
	{
		set_time_limit(0);

		foreach ( array('ftp_host', 'ftp_user', 'ftp_pass', 'ftp_path') as $var )
		{
			$_POST[$var] = trim(strip_tags(stripslashes($_POST[$var])));
		}

		$errors = array();
		$files = array();

		if ( sem_debug ) echo '<pre>';

		if ( !wiz_upgrade::upgrade_perms() )
		{
			$errors[] = 'Failed to change file permissions';
		}
		elseif ( !wiz_upgrade::clean_up() )
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
	} # do_cleanup()


	#
	# get_versions()
	#

	function get_versions()
	{
		if ( isset($_SESSION['sem_versions']) ) return $_SESSION['sem_versions'];
		
		$url = 'http://version.semiologic.com/sem_pro/';

		$lines = wp_remote_fopen($url);
		
		if ( $lines === false )
		{
			return false;
		}

		$lines = split("\n", $lines);

		$versions = array();

		foreach ( $lines as $line )
		{
			if ( $line )
			{
				list($tag, $version) = split(':', $line);
				
				$tag = trim(strip_tags($tag));
				$version = trim(strip_tags($version));

				$versions[$tag] = $version;
			}
		}
		
		$_SESSION['sem_versions'] = $versions;
		
		$options = array(
			'last_checked' => time(),
			'versions' => $versions,
			);
		
		update_option('sem_versions', $options);

		return $versions;
	} # get_versions()


	#
	# scan_path()
	#

	function scan_path(&$ftp)
	{
		if ( sem_debug ) echo "\n\n" . 'Autodetecting ftp path' . "\n\n";

		if ( $ftp->chdir(ABSPATH) )
		{
			$_POST['ftp_path'] = ABSPATH;
		}
		else
		{
			$ftp_path = '/';
			$dirs = split('/', trim(ABSPATH, '/'));

			foreach ( array_reverse($dirs) as $dir )
			{
				if ( !$dir )
				{
					continue;
				}

				$ftp_path = '/' . $dir . $ftp_path;

				if ( $ftp->chdir($ftp_path) )
				{
					$_POST['ftp_path'] = $ftp_path;
					break;
				}
			}

			if ( !$_POST['ftp_path'] ) # we failed to autodetect the path...
			{
				foreach ( array('/public_html/', '/htdocs/', '/www/', '/') as $ftp_path )
				{
					if ( $ftp->chdir($ftp_path) )
					{
						$_POST['ftp_path'] = $ftp_path;

						$ftp_path = rtrim($ftp_path, '/');
						break;
					}
				}

				$more_path = '/';
				$dirs = split('/', trim(ABSPATH, '/'));

				foreach ( array_reverse($dirs) as $dir )
				{
					if ( !$dir )
					{
						continue;
					}

					$more_path = '/' . $dir . $more_path;

					if ( $ftp->chdir($ftp_path . $more_path) )
					{
						$_POST['ftp_path'] = $ftp_path . $more_path;
						break;
					}
				}
			}
		}

		return true;
	} # scan_path()


	#
	# create_tmp()
	#

	function create_tmp(&$ftp)
	{
		if ( sem_debug ) echo "\n\n" . 'Creating tmp folder' . "\n\n";

		clearstatcache();

		/*
		dump("file_exists(" . ABSPATH . "): " . intval(file_exists(ABSPATH)));
		dump("is_dir(" . ABSPATH . "): " . intval(is_dir(ABSPATH)));
		dump("is_writable(" . ABSPATH . "): " . intval(is_writable(ABSPATH)));

		dump("file_exists(" . ABSPATH . 'tmp/' . "): " . intval(file_exists(ABSPATH . 'tmp/')));
		dump("is_dir(" . ABSPATH . 'tmp/' . "): " . intval(is_dir(ABSPATH . 'tmp/')));
		dump("is_writable(" . ABSPATH . 'tmp/' . "): " . intval(is_writable(ABSPATH . 'tmp/')));
		*/

		if ( !$ftp->chdir($_POST['ftp_path'] . 'tmp') )
		{
			if ( !$ftp->mkdir($_POST['ftp_path'] . 'tmp') )
			{
				clearstatcache();
				return false;
			}
		}

		if ( !$ftp->chmod($_POST['ftp_path'] . 'tmp', 0777) )
		{
			clearstatcache();
			return false;
		}

		clearstatcache();
		
		return true;
	} # create_tmp()


	#
	# download_zip()
	#

	function download_zip($file)
	{
		global $sem_options;

		if ( sem_debug ) echo "\n\n" . 'Downloading Sem Pro' . "\n\n";

		$url =
			( $_POST['sem_pro_version'] == 'bleeding'
				? 'http://www.semiologic.com/media/members/sem-pro/bleeding/sem-pro.zip'
				: 'http://www.semiologic.com/media/members/sem-pro/download/sem-pro.zip'
				);

		$url .= '?user_key=' . get_option('sem_api_key');

		clearstatcache();

		if ( !( $fp = @fopen($file, 'wb') ) )
		{
			return false;
		}
		elseif ( !( $res = wp_remote_fopen($url) ) )
		{
			return false;
		}
		elseif ( is_wp_error($res) )
		{
			return false;
		}

		fwrite($fp, $res);
		fclose($fp);

		return ( filesize($file) > 10000 );
	} # download_zip()


	#
	# extract_files()
	#

	function extract_files($file, $retry = false)
	{
		if ( sem_debug ) echo "\n\n" . 'Extracting Sem Pro' . "\n\n";

		$zip_file = new PclZip($file);

		if ( !wiz_upgrade::rm(ABSPATH . 'tmp/sem-pro') )
		{
			return false;
		}
		elseif ( !$zip_file->extract(PCLZIP_OPT_PATH, ABSPATH . 'tmp/') )
		{
			if ( $retry && wiz_upgrade::download_zip($file) )
			{
				return wiz_upgrade::extract_files($file);
			}
			else
			{
				return false;
			}
		}

		return true;
	} # extract_files()


	#
	# backup_files()
	#

	function backup_files()
	{
		if ( sem_debug ) echo "\n\n" . 'Backing up files' . "\n\n";


		$source = rtrim(ABSPATH, '/');
		$dest = ABSPATH . 'tmp/backup';

		if ( !wiz_upgrade::rm("$dest") )
		{
			return false;
		}
		elseif ( exec("mkdir " . escapeshellarg("$dest"))
			|| exec("mkdir " . escapeshellarg("$dest/wp-content"))
			|| exec("cp -RP " . escapeshellarg("$source/*.php") . " " . escapeshellarg("$dest/."))
			|| exec("cp -RP " . escapeshellarg("$source/wp-admin") . " " . escapeshellarg("$dest/."))
			|| exec("cp -RP " . escapeshellarg("$source/wp-includes") . " " . escapeshellarg("$dest/."))
			|| exec("cp -RP " . escapeshellarg("$source/wp-content/plugins") . " " . escapeshellarg("$dest/wp-content/."))
			|| exec("cp -RP " . escapeshellarg("$source/wp-content/themes") . " " . escapeshellarg("$dest/wp-content/."))
			)
		{
			return false;
		}

		return true;
	} # backup_files()


	#
	# backup_db()
	#

	function backup_db()
	{
		if ( sem_debug ) echo "\n\n" . 'Backing up database' . "\n\n";

		global $wpdb;
		global $table_prefix;

		$db_backup = 'backup__' . $table_prefix;

		$tables = (array) $wpdb->get_col("
			SHOW	TABLES
			LIKE	'"
				. str_replace(
					array('_', '%'),
					array('\\_', '\\%'),
					"$table_prefix"
					)
				. "%'
			;");

		foreach ( $tables as $table )
		{
			$wpdb->query("
				DROP TABLE IF EXISTS `$db_backup$table`;
				");

			$wpdb->query("
				CREATE TABLE `$db_backup$table` LIKE `$table`;
				");

			$wpdb->query("
				INSERT INTO `$db_backup$table`
				SELECT	*
				FROM	`$table`
				");
		}

		return true;
	} # backup_db()


	#
	# prepare_files()
	#

	function prepare_files($dir = '')
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
			if ( !wiz_upgrade::prepare_files($do_dir) )
			{
				return false;
			}
		}

		return true;
	} # prepare_files()


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

		$filelist = array();
		
		foreach ( $do_dirs as $do_dir )
		{
			if ( !wiz_upgrade::scan_files($filelist, $do_dir) )
			{
				return false;
			}
		}

		$do_files = array_merge($filelist, $do_files);
		
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
			&& !wiz_upgrade::ftp_folders($ftp_dirs)
			)
		{
			return false;
		}

		if ( !empty($ftp_files)
			&& !wiz_upgrade::ftp_files($ftp_files)
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
	var $filetypes = array(
		'php' => FTP_ASCII,
		'css' => FTP_ASCII,
		'txt' => FTP_ASCII,
		'js'  => FTP_ASCII,
		'html'=> FTP_ASCII,
		'htm' => FTP_ASCII,
		'xml' => FTP_ASCII,
		'ini' => FTP_ASCII,

		'jpg' => FTP_BINARY,
		'jpeg' => FTP_BINARY,		
		'png' => FTP_BINARY,
		'gif' => FTP_BINARY,
		'bmp' => FTP_BINARY,
		'swf' => FTP_BINARY,
		'gz' => FTP_BINARY		
		);
							
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
				|| !$ftp->Passive(true) || !$ftp->SetType(FTP_ASCII)
				)
			{
				$ftp->quit(true);
				return false;
			}

			foreach ( $files[$dir] as $file )
			{
				$extension = substr(strrchr($file, '.'), 1);
				$type = isset($ftp->filetypes[ $extension ]) ? $ftp->filetypes[ $extension ] : FTP_ASCII;
				
				// unfortunately the ftp class version included in wp and our ftp file use different put parameters to set transfer type
				if ( !function_exists('ftp_connect') ) 
				{
					$ftp->SetType($type);
					if ( !$ftp->put("$tmp_path/$file", "$site_path/$file") )
					{
						$ftp->quit(true);
						return false;
					}
				}
				else
				{
					if ( !$ftp->put("$tmp_path/$file", "$site_path/$file", $type) )
					{
						$ftp->quit(true);
						return false;
					}
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

		wp_remote_fopen($url);

		return true;
	} # upgrade_db()


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

		wiz_upgrade::rm(ABSPATH . 'tmp');

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
					if ( !wiz_upgrade::rm("$dir/$file") )
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
} # wiz_upgrade

wiz_upgrade::init();
?>