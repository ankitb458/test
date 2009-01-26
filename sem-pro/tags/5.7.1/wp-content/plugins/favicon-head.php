<?php
/*
Favicon Head
http://timjoh.com/wordpress-plugin-favicon-head/
Tim A. Johansson
1.3 fork

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

	2006-11-21: Version 1.1
		Animated favicon support added by pp (www.elvete.com)
	2006-08-21: Version 1.0
		Initial release

*/

define('taj_fh_location_default', '/favicon.ico', TRUE);
define('taj_fha_location_default', '', TRUE);

add_action('admin_menu', 'taj_fh_add_options_pages');

add_option('taj_fh_location', taj_fh_location_default, 'Default location for the favicon.');
add_option('taj_fha_location', taj_fha_location_default, 'Default location for the animated favicon.');

function taj_fh_add_options_pages() {
	add_options_page('Favicon', 'Favicon', 'manage_options', __FILE__, 'taj_fh_options_page');
}

function taj_fh_options_page() {
 	if (isset($_POST['info_update'])) {
		check_admin_referer('favicon');

		// Update location
		$taj_fh_location = $_POST['taj_fh_location'];
		$taj_fha_location = $_POST['taj_fha_location'];
		if ( ! isset( $taj_fh_location ) ) {
			$taj_fh_location = taj_fh_location_default;
		}
		update_option('taj_fh_location', $taj_fh_location);
		if ( ! isset( $taj_fha_location ) ) {
			$taj_fha_location = taj_fha_location_default;
		}
		update_option('taj_fha_location', $taj_fha_location);

		// Acknowledge
		echo '<div class="updated"><p><strong>Favicon Head options updated</strong></p></div>';
	}
	?>
		<div class="wrap">
			<form method="post" action="options-general.php?page=favicon-head.php">
			<?php if ( function_exists('wp_nonce_field') ) wp_nonce_field('favicon'); ?>
			<h2>Favicon Settings</h2>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="taj_fh_location">Static Favicon</label>
						</th>
						<td>
							<input type="text" name="taj_fh_location" size="60" class="code" value="<?php echo attribute_escape(get_option('taj_fh_location')); ?>" />
							<p>"/favicon.ico", means that the favicon.ico file is located in the root folder of the site.</p>
							<p>To override this, enter the url of your favicon file.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="taj_fha_location">Animated Favicon</label>
						</th>
						<td>
							<input type="text" name="taj_fha_location" size="60" class="code" value="<?php echo attribute_escape(get_option('taj_fha_location')); ?>" />
							<p>To use an animated favicon, enter its url. Be sure to keep a static favicon around if you do, however: animated favicons do not work in most versions of IE.</p>
						</td>
					</tr>
				</table>
			<p class="submit">
				<input type="submit" name="info_update" value="Update Options" />
			</p>
			</form>
		</div>
	<?php
}

function taj_fh_meta() {
	/* The guidelines in the Wikipedia article are followed. */
	$favicon_location = get_option('taj_fh_location');
	$afavicon_location = get_option('taj_fha_location');
	echo '<link rel="shortcut icon" href="' . $favicon_location . '" type="image/x-icon" />' . "\n"; /* For IE */
	echo '<link rel="icon" href="' . ( empty( $afavicon_location ) ? $favicon_location : $afavicon_location ) . '" type="image/x-icon" />' . "\n"; /* For sane browsers */
}


add_action('wp_head', 'taj_fh_meta');

?>