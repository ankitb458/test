<?php
if ( !class_exists('wiz_clone_start') ) :

class wiz_clone_start
{
	#
	# show_step()
	#

	function show_step()
	{
		echo '<h3>'
			. '<u>Import</u> &rarr; Done'
			. '</h3>';

		echo '<p>'
			. 'This wizard will let you clone another Semiologic Pro site\'s settings, including presentation options (except custom skin files), ads and site preferences.'
			. '</p>';

		echo '<p>'
			. 'To proceed, enter the url and admin details of the site you wish to clone:'
			. '</p>';

		$labels = array(
			'site_url' => 'Site Url',
			'site_user' => 'Admin Username',
			'site_pass' => 'Admin Password',
			);

		foreach ( array_keys($labels) as $key )
		{
			echo '<p>'
				. '<label>'
				. $labels[$key] . ':'
				. '<br />'
				. '<input type="' . ( $key == 'site_pass' ? 'password' : 'text' ) . '" style="width: 300px"'
					. ' name="' . $key . '"'
					. ' value="' . ( $key == 'site_pass' ? '' : htmlspecialchars($_SESSION[$key]) ) . '"'
					. ' />'
				. '</label>'
				. '</p>';
		}

		echo '<p>'
			. 'Note: Cloning a site can take up to a few minutes. Patience is your friend.'
			. '</p>';
	} # show_step()
} # wiz_clone_start
endif;
?>