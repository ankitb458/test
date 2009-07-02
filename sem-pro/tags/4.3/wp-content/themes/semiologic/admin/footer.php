<?php
#
# add_theme_footer_admin()
#

function add_theme_footer_admin()
{
	if ( !function_exists('get_site_option') || is_site_admin() )
	{
		add_submenu_page(
			'themes.php',
			__('Footer'),
			__('Footer'),
			'switch_themes',
			str_replace("\\", "/", basename(__FILE__)),
			'display_theme_footer_admin'
			);
	}
} # end add_theme_footer_admin()

add_action('admin_menu', 'add_theme_footer_admin');


#
# display_theme_footer_admin()
#

function display_theme_footer_admin()
{
	if ( !empty($_POST)
		&& isset($_POST['action'])
		&& $_POST['action'] == 'update_theme_footer'
		)
	{
		do_action('update_theme_footer');

		echo "<div class=\"updated\">\n"
			. "<p>"
				. "<strong>"
				. __('Options saved.')
				. "</strong>"
			. "</p>\n"
			. "</div>\n";
	}

	echo '<form method="post" action="">';

	if ( function_exists('wp_nonce_field') ) wp_nonce_field('sem_footer');

	echo '<input type="hidden"'
		. ' name="action"'
		. ' value="update_theme_footer"'
		. ' />';

	echo '<div class="wrap">';
	echo '<h2>' . __('Footer Options') . '</h2>';
	do_action('display_theme_footer');
	echo '</div>';

	echo '</form>';
} # end display_theme_footer_admin()


#
# display_theme_footer()
#

function display_theme_footer()
{
	if ( !sem_pro )
	{
		pro_feature_notice();
	}

	echo '<h3>' . __('Extra Footer Content') . '</h3>';

	echo '<p>' . __('Use this field to enter raw HTML (text, scripts, etc.) that should appear towards the bottom of each of your pages.');

	echo '<div>'
		. '<textarea'
		. ' id="extra_footer" name="extra_footer"'
		. ( sem_pro
			? ''
			: ' disabled="disabled"'
			)
		. ' style="width: 750px; height: 240px;"'
		. '>'
		. htmlspecialchars(apply_filters('extra_footer', ''), ENT_QUOTES)
		. '</textarea>'
		. '</div>';

	echo '<h3>' . __('Credits') . '</h3>';

	echo '<p>'
		. '<label for="show_credits">'
		. '<input type="checkbox"'
			. ' id="show_credits" name="show_credits"'
			. ( apply_filters('show_credits', true)
				? ' checked="checked"'
				: ''
				)
			. ( sem_pro
				? ''
				: ' disabled="disabled"'
				)
			. ' />'
			. '&nbsp;'
			. __('Kudos to WordPress, Semiologic and the skin author.')
		. '</label>'
		. '</p>';

		echo '<div style="clear: both;"></div>';

		echo '<div class="submit">';
		echo '<input type="submit" value="' . __('Update Options') . ' &raquo;" />';
		echo '</div>';
} # end display_theme_footer()

add_action('display_theme_footer', 'display_theme_footer');

?>