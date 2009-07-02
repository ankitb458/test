<?php
if ( !class_exists('wiz_upgrade_start') ) :
class wiz_upgrade_start
{
	#
	# show_step()
	#

	function show_step()
	{
		echo '<h3>'
			. '<u>Download</u> &rarr; Backup &rarr; Prepare &rarr; Upgrade &rarr; Clean Up &rarr; Done'
			. '</h3>';

		echo '<p>'
			. 'This wizard will let you automatically upgrade Semiologic Pro.'
			. '</p>';

		$versions = wiz_upgrade_start::get_versions();

		if ( version_compare(sem_version, $versions['bleeding'], '>=') && !sem_debug )
		{
			echo '<p>'
				. 'Your site is already running the latest bleeding edge version of Semiologic Pro.'
				. '</p>';

			return;
		}
		elseif ( version_compare(sem_version, $versions['stable'], '=') )
		{
			echo '<p style="padding: 20px; border: solid 1px black; background-color: #ffeeee;">'
				. '<strong>Your site is up to date</strong>. Unless, that is, you happen to be willing to test the <b style="color: firebrick;">bleeding edge version</b> of Semiologic Pro.'
				. '</p>';

			echo '<p>'
				. 'Some development cycle jargon for reference:'
				. '</p>';

			echo '<ul>'
				. '<li>'
				. '<b>Alpha</b> means new features are still being added. Expect bugs here and there.'
				. '</li>'
				. '<li>'
				. '<b>Beta</b> means new features are no longer added. Expect an occasional bug.'
				. '</li>'
				. '<li>'
				. '<b>Release Candidate</b> (RC) means we\'re very close from releasing. We\'re weeding out the last tiny bugs, if any remain.'
				. '</li>'
				. '</ul>';

			echo '<input type="hidden" name="sem_pro_version" value="bleeding" />';

			echo  '<p>'
				. 'Unless otherwise advised by the Semiologic development team, <strong>do not test the bleeding edge version of Semiologic Pro on a production site</strong>. Also, be sure to report any bugs you spot in the relevant <a href="http://forum.semiologic.com">forum thread</a>.'
				. '</p>';

			echo '<p>'
				. sprintf('Enter your site\'s ftp details below to install the bleeding edge version of Semiologic Pro (<b style="color: firebrick;">%s</b>) on your site:', $versions['bleeding'])
				. '</p>';

			echo '<input type="hidden" name="sem_pro_version" value="bleeding" />';

		}
		else
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
				. sprintf('<b style="color: firebrick;">Bleeding edge version: %s</b>', $versions['bleeding']) . '<br />'
				. '&nbsp;&nbsp;&nbsp;&nbsp;As a rule, <strong style="color: firebrick;">do not test the bleeding edge version of Semiologic Pro on a production site</strong> unless you know what you\'re doing.'
				. '</label>'
				. '</li>'
				. '</ul>';
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
				. '<input type="' . ( $key == 'ftp_pass' ? 'password' : 'text' ) . '" style="width: 300px"'
					. ' name="' . $key . '"'
					. ' value="' . ( $key == 'ftp_pass' ? '' : htmlspecialchars($_SESSION[$key]) ) . '"'
					. ' />'
				. '</label>'
				. '</p>';
		}

		/*
		echo '<p>'
			. '<label>'
			. '<input type="checkbox" name="one_click" />'
			. '&nbsp;'
			. 'One-click upgrade (proceeds with the upgrade without prompting)'
			. '</label>'
			. '</p>';
		*/

		echo '<p>'
			. 'The upgrade wizard will autodetect your ftp path'
			. '</p>';

		echo '<p>'
			. 'Note: The download usually takes a few minutes, and the upgrade process can take as much as 10 minutes on a loaded server.'
			. '</p>';

		/*
		echo '<p>'
			. 'If you abort a one-click upgrade (i.e. click the next button, refresh the page, close your browser window, etc.), the wizard will nonetheless continue to run to the very end for the sake of not breaking your site -- but your site can momentarily seem broken until it\'s done.'
			. '</p>';
		*/
	} # show_step()


	#
	# get_versions()
	#

	function get_versions()
	{
		if ( !sem_pro )
		{
			return false;
		}

		$url = 'http://version.mesoconcepts.com/sem_pro/';

		$lines = sem_http::get($url);

		$lines = split("\n", $lines);

		$versions = array();

		foreach ( $lines as $line )
		{
			if ( $line )
			{
				list($tag, $version) = split(':', $line);

				$versions[trim($tag)] = trim($version);
			}
		}

		return $versions;
	} # get_versions()
} # wiz_upgrade_start
endif;
?>