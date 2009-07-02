<?php
/*
Plugin Name: Favicon Head
Plugin URI: http://timjoh.com/wordpress-plugin-favicon-head/
Description: Favicon Head adds meta tags in the head of every page, specifying the location of your blog's favicon.ico.
Author: Tim A. Johansson
Version: 1.1 (fork)
Author URI: http://timjoh.com/

Copyright 2006  Tim A. Johansson

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/*
Todo:
	Dunno
*/

/*
Changelog:

	2006-08-21: Version 1.0
		Initial release

*/

define('taj_fh_location_default', '/favicon.ico', TRUE);

add_action('admin_menu', 'taj_fh_add_options_pages');

if ( function_exists('get_site_option') )
{
	add_site_option('taj_fh_location', taj_fh_location_default, 'Default location for the favicon.');
}
else
{
	add_option('taj_fh_location', taj_fh_location_default, 'Default location for the favicon.');
}

function taj_fh_add_options_pages() {
	if ( !function_exists('get_site_option') || is_site_admin() )
	{
		add_options_page('Favicon Head', 'Favicon Head', 8, __FILE__, 'taj_fh_options_page');
	}
}

function taj_fh_options_page() {

 	if (isset($_POST['info_update'])) {
		check_admin_referer();

		// Update location
		$taj_fh_location = stripslashes(strip_tags($_POST['taj_fh_location']));

		if ( !$taj_fh_location )
		{
			$taj_fh_location = taj_fh_location_default;
		}

		if ( function_exists('get_site_option') )
		{
			update_site_option('taj_fh_location', $taj_fh_location);
		}
		else
		{
			update_option('taj_fh_location', $taj_fh_location);
		}

		// Acknowledge
		echo '<div class="updated"><p><strong>Favicon Head options updated</strong></p></div>';
	}

	$taj_fh_location = function_exists('get_site_option') ? get_site_option('taj_fh_location') : get_option('taj_fh_location');

	?>
		<div class="wrap">
			<form method="post" action="options-general.php?page=favicon-head.php">
			<h2>Favicon Head Options</h2>
			<fieldset class="options">
				<legend>Basic Options</legend>
				<table class="editform" cellspacing="2" cellpadding="5" width="100%">
					<tr>
						<th width="30%" valign="top" style="padding-top: 10px;">
							<label for="taj_fh_location">Favicon location:</label>
						</th>
						<td>
							<input type="text" name="taj_fh_location" size="32" value="<?php echo htmlspecialchars($taj_fh_location, ENT_QUOTES); ?>" />
							<p style="margin: 5px 10px;">Since the directory depth of WordPress varies, this value should begin with "http://" or "/". The default value "/favicon.ico" means that the favicon.ico file is in the root directory.</p>
						</td>
					</tr>
				</table>
			</fieldset>
			<p class="submit">
				<input type="submit" name="info_update" value="Update Options" />
			</p>
			</form>
		</div>
	<?php
}

function taj_fh_meta() {
	if ( !function_exists('get_site_option') || is_site_admin() )
	{
		/* The guidelines in the Wikipedia article are followed. */
		$favicon_location = get_option('taj_fh_location');

		if ( $favicon_location )
		{
			echo '<link rel="icon" href="' . $favicon_location . '" type="image/x-icon" />'; /* For sane browsers */
			echo '<link rel="shortcut icon" href="' . $favicon_location . '" type="image/x-icon" />'; /* For IE */
		}
	}
}


add_action('wp_head', 'taj_fh_meta');

?>