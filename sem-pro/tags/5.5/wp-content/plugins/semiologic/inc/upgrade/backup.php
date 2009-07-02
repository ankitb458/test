<?php
if ( !class_exists('wiz_upgrade_backup') ) :
class wiz_upgrade_backup
{
	#
	# show_step()
	#

	function show_step()
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

		if ( !wiz_upgrade_backup::backup_files() )
		{
			$errors[] = 'Failed to backup the files.';
		}
		elseif ( !wiz_upgrade_backup::backup_db() )
		{
			$errors[] = 'Failed to backup the database.';
		}

		exec("chmod -R ugo+w " . escapeshellarg(ABSPATH . 'tmp'));

		if ( sem_debug ) echo '</pre>';

		if ( empty($errors) )
		{
			if ( isset($_POST['one_click']) )
			{
				return wiz_upgrade_prepare::do_step('prepare');
			}
			else
			{
				return 'prepare';
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
	# backup_files()
	#

	function backup_files()
	{
		if ( sem_debug ) echo "\n\n" . 'Backing up files' . "\n\n";


		$source = rtrim(ABSPATH, '/');
		$dest = ABSPATH . 'tmp/backup';

		if ( !wiz_upgrade_cleanup::rm("$dest") )
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
} # wiz_upgrade_backup
endif;
?>