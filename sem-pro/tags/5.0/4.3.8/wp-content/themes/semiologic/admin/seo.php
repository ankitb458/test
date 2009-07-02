<?php
class sem_seo
{
	#
	# init()
	#

	function init()
	{
		add_action('admin_menu', array('sem_seo', 'add_admin_page'));

		add_action('dbx_post_advanced', array('sem_seo', 'display_entry_meta'));
		add_action('dbx_page_advanced', array('sem_seo', 'display_entry_meta'));
	} # init()


	#
	# display_post_meta()
	#

	function display_entry_meta()
	{
		$post_ID = isset($GLOBALS['post_ID']) ? $GLOBALS['post_ID'] : $GLOBALS['temp_ID'];

		#echo '<pre>';
		#var_dump($post_ID);
		#echo '</pre>';

		echo '<div class="dbx-b-ox-wrapper">';

		echo '<fieldset id="sementryseo" class="dbx-box">'
			. '<div class="dbx-h-andle-wrapper">'
			. '<h3 class="dbx-handle">' . __('Page Title and Meta Tags') . '</h3>'
			. '</div>';

		echo '<div class="dbx-c-ontent-wrapper">'
			. '<div id="sementryseostuff" class="dbx-content">';

		if ( !sem_pro )
		{
			pro_feature_notice();
		}

		echo '<p>'
			. __('Semiologic Pro automatically generates a search engine optimized title, meta keywords and a meta description for each post. The following fields allow you to override them for individual entries.')
			. '</p>';

		echo '<table width="100%" cellspacing="2" cellpadding="5" class="editform">';

		echo '<tr>'
			. '<th style="text-align: right; width: 160px;">'
			. '<label for="seo_title">'
			. __('Title:')
			. '</label>'
			. '</th>'
			. '<td>'
			. '<input type="text"'
				. ' style="width: 420px;"'
				. ' id="seo_title" name="seo_title"'
				. ' value="' . htmlspecialchars(get_post_meta($post_ID, '_title', true), ENT_QUOTES) . '"'
				. ( !sem_pro
					? ' disabled="disabled"'
					: ''
					)
				. ' />'
			. '</td>'
			. '</tr>';

		echo '<tr>'
			. '<th style="text-align: right; width: 160px;">'
			. '<label for="seo_keywords">'
			. __('Keywords:')
			. '</label>'
			. '</th>'
			. '<td>'
			. '<input type="text"'
				. ' style="width: 420px;"'
				. ' id="seo_keywords" name="seo_keywords"'
				. ' value="' . htmlspecialchars(get_post_meta($post_ID, '_keywords', true), ENT_QUOTES) . '"'
				. ( !sem_pro
					? ' disabled="disabled"'
					: ''
					)
				. ' />'
			. '</td>'
			. '</tr>';

		echo '<tr>'
			. '<th style="text-align: right; width: 160px;">'
			. '<label for="seo_description">'
			. __('Description:')
			. '</label>'
			. '</th>'
			. '<td>'
			. '<textarea'
				. ' style="width: 420px; height: 80px;"'
				. ' id="seo_description" name="seo_description"'
				. ( !sem_pro
					? ' disabled="disabled"'
					: ''
					)
				. '>'
				. htmlspecialchars(get_post_meta($post_ID, '_description', true))
			. '</textarea>'
			. '</td>'
			. '</tr>';

		echo '</table>';

		echo '</div>'
			. '</div>';

		echo '</fieldset>';

		echo '</div>';
	} # display_entry_meta()


	#
	# add_admin_page()
	#

	function add_admin_page()
	{
		add_submenu_page(
			'themes.php',
			__('SEO'),
			__('SEO'),
			'switch_themes',
			str_replace("\\", "/", basename(__FILE__)),
			array('sem_seo', 'display_admin_page')
			);
	} # add_admin_page()


	#
	# display_admin_page()
	#

	function display_admin_page()
	{
		if ( !empty($_POST)
		&& isset($_POST['action'])
		&& $_POST['action'] == 'update_theme_seo_options'
		)
		{
			do_action('update_theme_seo_options');

			echo "<div class=\"updated\">\n"
				. "<p>"
					. "<strong>"
					. __('Options saved.')
					. "</strong>"
				. "</p>\n"
				. "</div>\n";
		}

		$options = get_option('semiologic');

		echo '<form method="post" action="">';

		if ( function_exists('wp_nonce_field') ) wp_nonce_field('sem_seo');

		echo '<input type="hidden"'
			. ' name="action"'
			. ' value="update_theme_seo_options"'
			. ' />';

		if ( !sem_pro )
		{
			pro_feature_notice();
		}

		echo '<div class="wrap">'
			. '<h2>' . __('Page Title and Meta Tags') . '</h2>';

		echo '<p>'
			. __('Semiologic Pro automatically generates a search engine optimized title, meta keywords and a meta description for your site\'s home page and archives pages, as well as for categories and individual entries. The following fields allow you to override these fields for the home page and archive pages.')
			. '</p>';

		echo '<table width="100%" cellspacing="2" cellpadding="5" class="editform">';

		echo '<tr>'
			. '<th style="text-align: right; width: 160px;">'
			. '<label for="seo_title">'
			. __('Title:')
			. '</label>'
			. '</th>'
			. '<td>'
			. '<input type="text"'
				. ' style="width: 420px;"'
				. ' id="seo_title" name="seo_title"'
				. ' value="' . htmlspecialchars($options['seo']['title'], ENT_QUOTES) . '"'
				. ( !sem_pro
					? ' disabled="disabled"'
					: ''
					)
				. ' />'
			. '</td>'
			. '</tr>'
			. '<tr>'
			. '<td></td>'
			. '<td>'
				. '<label for="seo_add_site_name">'
				. '<input type="checkbox"'
					. ' id="seo_add_site_name" name="seo_add_site_name"'
					. ( $options['seo']['add_site_name']
						? ' checked="checked"'
						: ''
						)
					. ' />'
				. '&nbsp;'
				. __('Append site name to all page titles')
				. '</label>'
			. '</td>'
			. '</tr>'
			;

		echo '<tr>'
			. '<th style="text-align: right; width: 160px;">'
			. '<label for="seo_keywords">'
			. __('Keywords:')
			. '</label>'
			. '</th>'
			. '<td>'
			. '<input type="text"'
				. ' style="width: 420px;"'
				. ' id="seo_keywords" name="seo_keywords"'
				. ' value="' . htmlspecialchars($options['seo']['keywords'], ENT_QUOTES) . '"'
				. ( !sem_pro
					? ' disabled="disabled"'
					: ''
					)
				. ' />'
			. '</td>'
			. '</tr>';

		echo '<tr>'
			. '<th style="text-align: right; width: 160px;">'
			. '<label for="seo_description">'
			. __('Description:')
			. '</label>'
			. '</th>'
			. '<td>'
			. '<textarea'
				. ' style="width: 420px; height: 80px;"'
				. ' id="seo_description" name="seo_description"'
				. ( !sem_pro
					? ' disabled="disabled"'
					: ''
					)
				. '>'
				. $options['seo']['description']
			. '</textarea>'
			. '</td>'
			. '</tr>';

		echo '</table>';

		echo '<div style="clear: both;"></div>';

		echo '<div class="submit">';
		echo '<input type="submit" value="' . __('Update Options') . ' &raquo;" />';
		echo '</div>';

		echo '</div>';

		echo '</form>';
	} # display_admin_page()
} # end sem_seo

sem_seo::init();
?>