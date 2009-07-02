<?php
class footer_admin
{
	#
	# init()
	#

	function init()
	{
		add_action('admin_menu', array('footer_admin', 'admin_menu'));

		add_action('dbx_post_advanced', array('footer_admin', 'entry_footer'));
		add_action('dbx_page_advanced', array('footer_admin', 'entry_footer'));
	} # init()


	#
	# admin_menu()
	#

	function admin_menu()
	{
		if ( !function_exists('get_site_option') || is_site_admin() )
		{
			add_submenu_page(
				'themes.php',
				__('Footer'),
				__('Footer'),
				'switch_themes',
				basename(__FILE__),
				array('footer_admin', 'admin_page')
				);
		}
	} # end admin_menu()


	#
	# admin_page()
	#

	function admin_page()
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

		echo '</div>';

		echo '</form>';
	} # end admin_page()


	#
	# entry_footer()
	#

	function entry_footer()
	{
		if ( !current_user_can('switch_themes') )
		{
			return;
		}

		$post_ID = isset($GLOBALS['post_ID']) ? $GLOBALS['post_ID'] : $GLOBALS['temp_ID'];

		#echo '<pre>';
		#var_dump($post_ID);
		#echo '</pre>';

		echo '<div class="dbx-b-ox-wrapper">';

		echo '<fieldset id="semfooter" class="dbx-box">'
			. '<div class="dbx-h-andle-wrapper">'
			. '<h3 class="dbx-handle">' . __('Footer') . '</h3>'
			. '</div>';

		echo '<div class="dbx-c-ontent-wrapper">'
			. '<div id="semfooterstuff" class="dbx-content">';

		if ( !sem_pro )
		{
			pro_feature_notice();
		}

		echo '<p>'
			. __('Use this field to enter raw HTML (text, scripts, etc.) that should appear towards the bottom of each of your pages. This comes in addition to anything configured under Presentation / Footer.')
			. '</p>';

		echo '<div>'
			. '<textarea'
			. ' id="entry_footer" name="entry_footer"'
			. ( sem_pro
				? ''
				: ' disabled="disabled"'
				)
			. ' style="width: 480px; height: 120px;"'
			. '>'
			. htmlspecialchars(get_post_meta($post_ID, '_footer', true))
			. '</textarea>'
			. '</div>';

		echo '<p class="submit">'
			. '<input type="button"'
			. ' value="' . __('Save and Continue Editing') . '"'
			. ' onclick="return form.save.click();"'
			. ' />'
			. '</p>';

		echo '</div>'
			. '</div>';

		echo '</fieldset>';

		echo '</div>';
	} # entry_footer()
} # footer_admin

footer_admin::init();



#
# sem_postnav_widget_control()
#

function sem_postnav_widget_control()
{
	global $sem_captions;

	if ( $_POST['update_sem_footer']['postnav'] )
	{
		$new_captions = $sem_captions;

		$new_captions['prev_page'] = strip_tags(stripslashes($_POST['sem_footer']['label_prev_page']));
		$new_captions['next_page'] = strip_tags(stripslashes($_POST['sem_footer']['label_next_page']));

		if ( $new_captions != $sem_captions )
		{
			$sem_captions = $new_captions;

			update_option('sem5_captions', $sem_captions);
		}
	}

	echo '<input type="hidden" name="update_sem_footer[postnav]" value="1" />';

	echo '<h3>'
		. __('Captions')
		. '</h3>';

	echo '<div style="margin-bottom: .2em;">'
		. '<label>'
		. __('Previous Post Page, e.g. Previous Page')
		. '<br />'
		. '<input type="text" style="width: 95%"'
			. ' name="sem_footer[label_prev_page]"'
			. ' value="' . htmlspecialchars($sem_captions['prev_page']) . '"'
			. ' />'
		. '</label>'
		. '</div>';

	echo '<div style="margin-bottom: .2em;">'
		. '<label>'
		. __('Next Post Page, e.g. Next Page')
		. '<br />'
		. '<input type="text" style="width: 95%"'
			. ' name="sem_footer[label_next_page]"'
			. ' value="' . htmlspecialchars($sem_captions['next_page']) . '"'
			. ' />'
		. '</label>'
		. '</div>';
} # sem_postnav_widget_control()
?>