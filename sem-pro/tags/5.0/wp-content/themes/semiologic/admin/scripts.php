<?php

class scripts_admin
{
	#
	# init()
	#

	function init()
	{
		add_action('admin_menu', array('scripts_admin', 'admin_menu'));

		add_action('dbx_post_advanced', array('scripts_admin', 'editor'));
		add_action('dbx_page_advanced', array('scripts_admin', 'editor'));
	} # init()



	#
	# admin_menu()
	#

	function admin_menu()
	{
		if ( current_user_can('unfiltered_html') )
		{
			add_submenu_page(
				'themes.php',
				__('Scripts'),
				__('Scripts'),
				'switch_themes',
				basename(__FILE__),
				array('scripts_admin', 'admin_page')
				);
		}
	} # admin_menu()



	#
	# admin_page()
	#

	function admin_page()
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
			. htmlspecialchars(apply_filters('head_scripts', ''))
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
			. htmlspecialchars(apply_filters('onload_scripts', ''))
			. '</textarea>'
			. '</div>';

		echo '<div style="clear: both;"></div>';

		echo '<div class="submit">';
		echo '<input type="submit" value="' . __('Update Options') . ' &raquo;" />';
		echo '</div>';

		echo '</div>';

		echo '</form>';
	} # admin_page()


	#
	# editor()
	#

	function editor()
	{
		if ( current_user_can('unfiltered_html') )
		{
			$post_ID = isset($GLOBALS['post_ID']) ? $GLOBALS['post_ID'] : $GLOBALS['temp_ID'];

			#echo '<pre>';
			#var_dump($post_ID);
			#echo '</pre>';

			echo '<div class="dbx-b-ox-wrapper">';

			echo '<fieldset id="semscripts" class="dbx-box">'
				. '<div class="dbx-h-andle-wrapper">'
				. '<h3 class="dbx-handle">' . __('Scripts') . '</h3>'
				. '</div>';

			echo '<div class="dbx-c-ontent-wrapper">'
				. '<div id="semscriptsstuff" class="dbx-content">';

			if ( !sem_pro )
			{
				pro_feature_notice();
			}

			echo '<p>' . __('The fields that follow are in addition to anything you\'ve configured under Presentation / Scripts.');

			echo '<h3><strong>' . __('Scripts') . '</strong></h3>';

			echo '<p>' . __('Use this field to enter arbitrary &lt;script&gt; tags that should appear between the &lt;head&gt; and &lt;/head&gt; area of this particular entry.') . '</p>';

			echo '<div>'
				. '<textarea'
				. ' id="head_scripts" name="head_scripts"'
				. ( sem_pro
					? ''
					: ' disabled="disabled"'
					)
				. ' style="width: 480px; height: 120px;"'
				. '>'
				. htmlspecialchars(get_post_meta($post_ID, '_head', true))
				. '</textarea>'
				. '</div>';

			echo '<h3><strong>' . __('Onload Events') . '</strong></h3>';

			echo '<p>' . __('Use this field to enter javascripts that should be executed after this particular entry is loaded, <i>e.g.</i> my_stuff();.') . '</p>';

			echo '<div>'
				. '<textarea'
				. ' id="onload_scripts" name="onload_scripts"'
				. ( sem_pro
					? ''
					: ' disabled="disabled"'
					)
				. ' style="width: 480px; height: 80px;"'
				. '>'
				. htmlspecialchars(get_post_meta($post_ID, '_onload', true))
				. '</textarea>'
				. '</div>';

			echo '<div style="clear: both;"></div>';

			echo '<p class="submit">'
				. '<input type="button"'
				. ' value="' . __('Save and Continue Editing') . '"'
				. ' onclick="return form.save.click();"'
				. ' />'
				. '</p>';

			echo '</div>';
			echo '</div>';

			echo '</fieldset>';

			echo '</div>';
		}
	} # editor()
} # scripts_admin

scripts_admin::init();
?>