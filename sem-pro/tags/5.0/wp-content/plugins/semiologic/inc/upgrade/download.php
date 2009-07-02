<?php
if ( !class_exists('wiz_upgrade_download') ) :
class wiz_upgrade_download
{
	#
	# do_step()
	#

	function do_step($step)
	{
		set_time_limit(0);

		$errors = array();

		$file = ABSPATH . 'tmp/' . md5(uniqid(rand(), true)) . '.zip';

		foreach ( array('ftp_host', 'ftp_user', 'ftp_pass') as $var )
		{
			$_POST[$var] = trim(strip_tags(stripslashes($_POST[$var])));
		}

		if ( sem_debug ) echo '<pre>';

		#wiz_upgrade_cleanup::clean_up();

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
		elseif ( !wiz_upgrade_download::scan_path($ftp) )
		{
			$errors[] = 'Failed to identify ftp path';
		}
		elseif ( !wiz_upgrade_download::create_tmp($ftp) )
		{
			$errors[] = 'Failed create tmp folder';
		}
		elseif ( !$ftp->quit(true) )
		{
			$errors[] = 'An ftp error occurred';
		}
		elseif ( !wiz_upgrade_download::download_zip($file) )
		{
			if ( !@fopen($file, 'wb') )
			{
				$errors[] = 'Failed to create file using <a href="http://www.php.net/manual/en/function.fopen.php">fopen</a>: fopen(\'' . $file . '\', \'wb\'). This is due to a server configuration problem (<a href="http://www.php.net/manual/en/function.clearstatcache.php">clearstatcache</a> is not working properly) that can only be resolved by your host.';
			}
			else
			{
				$errors[] = 'Failed to download Semiologic Pro. Please check your API key (Presentation / API Key) and your <a href="http://members.semiologic.com">membership status</a>.';
			}
		}
		elseif ( !wiz_upgrade_download::extract_files($file, true) )
		{
			$errors[] = 'Failed to extract the Semiologic Pro files (tried twice).';
		}

		@unlink($file);
		exec("chmod -R ugo+w " . escapeshellarg(ABSPATH . 'tmp'));

		if ( sem_debug ) echo '</pre>';

		if ( empty($errors) )
		{
			if ( isset($_POST['one_click']) )
			{
				return wiz_upgrade_backup::do_step('backup');
			}
			else
			{
				return 'backup';
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
				? 'http://www.semiologic.com/files/members/sem-pro/bleeding/sem-pro-bleeding.zip'
				: 'http://www.semiologic.com/files/members/sem-pro/download/sem-pro.zip'
				);

		$url .= '?user_key=' . $sem_options['api_key'];

		# temporary url
		# $url = 'http://semiologic:bestvalue@wp-pro.semiologic.com/sem-pro-bleeding.zip';

		clearstatcache();

		if ( !( $fp = @fopen($file, 'wb') ) )
		{
			return false;
		}
		elseif ( !( $res = sem_http::get($url) ) )
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

		if ( !wiz_upgrade_cleanup::rm(ABSPATH . 'tmp/sem-pro') )
		{
			return false;
		}
		elseif ( !$zip_file->extract(PCLZIP_OPT_PATH, ABSPATH . 'tmp/') )
		{
			if ( $retry && wiz_upgrade_download::download_zip($file) )
			{
				return wiz_upgrade_download::extract_files($file);
			}
			else
			{
				return false;
			}
		}

		return true;
	} # extract_files()
} # wiz_upgrade_download
endif;
?>