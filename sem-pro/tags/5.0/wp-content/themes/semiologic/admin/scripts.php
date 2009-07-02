<?php
#
# add_theme_scripts_admin()
#

function add_theme_scripts_admin()
{
	if ( current_user_can('unfiltered_html') )
	{
		add_submenu_page(
			'themes.php',
			__('Scripts'),
			__('Scripts'),
			'switch_themes',
			str_replace("\\", "/", basename(__FILE__)),
			'display_theme_scripts_admin'
			);
	}
} # end add_theme_scripts_admin()

add_action('admin_menu', 'add_theme_scripts_admin');


#
# display_theme_scripts_admin()
#

function display_theme_scripts_admin()
{
	if ( !empty($_POST)
		&& isset($_POST['action'])
		&& $_POST['action'] == 'update_theme_scripts'
		)
	{
		do_action('update_theme_scripts');

		echo "<div class=\"updated\">\n"
			. "<p>"
				. "<strong>"
				. __('Options saved.')
				. "</strong>"
			. "</p>\n"
			. "</div>\n";
	}

	echo '<form method="post" action="">';

	if ( function_exists('wp_nonce_field') ) wp_nonce_field('sem_scripts');

	echo '<input type="hidden"'
		. ' name="action"'
		. ' value="update_theme_scripts"'
		. ' />';

	echo '<div class="wrap">';
	echo '<h2>' . __('scripts Options') . '</h2>';
	do_action('display_theme_scripts');
	echo '</div>';

	echo '</form>';
} # end display_theme_scripts_admin()


#
# display_theme_scripts()
#

function display_theme_scripts()
{
	if ( !sem_pro )
	{
		pro_feature_notice();
	}

	echo '<h3>' . __('Scripts') . '</h3>';

	echo '<p>' . __('Use this field to enter arbitrary &lt;script&gt; tags that should appear between the &lt;head&gt; and &lt;/head&gt; area of your page. You can also insert &lt;meta&gt; tags, &lt;style&gt; tags, etc.') . '</p>';

	echo '<div>'
		. '<textarea'
		. ' id="head_scripts" name="head_scripts"'
		. ( sem_pro
			? ''
			: ' disabled="disabled"'
			)
		. ' style="width: 750px; height: 240px;"'
		. '>'
		. htmlspecialchars(apply_filters('head_scripts', ''), ENT_QUOTES)
		. '</textarea>'
		. '</div>';

	echo '<h3>' . __('Onload Events') . '</h3>';

	echo '<p>' . __('Use this field to enter javascripts that should be executed after your page is loaded, <i>e.g.</i> my_stuff();') . '</p>';


	echo '<div>'
		. '<textarea'
		. ' id="onload_scripts" name="onload_scripts"'
		. ( sem_pro
			? ''
			: ' disabled="disabled"'
			)
		. ' style="width: 750px; height: 120px;"'
		. '>'
		. htmlspecialchars(apply_filters('onload_scripts', ''), ENT_QUOTES)
		. '</textarea>'
		. '</div>';

	echo '<div style="clear: both;"></div>';

	echo '<div class="submit">';
	echo '<input type="submit" value="' . __('Update Options') . ' &raquo;" />';
	echo '</div>';
} # end display_theme_scripts()

add_action('display_theme_scripts', 'display_theme_scripts');

?>