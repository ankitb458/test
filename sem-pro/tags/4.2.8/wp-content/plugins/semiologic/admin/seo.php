<?php

#
# display_seo_meta_fields()
#

function display_seo_meta_fields()
{
	$post_ID = isset($GLOBALS['post_ID']) ? $GLOBALS['post_ID'] : $GLOBALS['temp_ID'];

	#echo '<pre>';
	#var_dump($post_ID);
	#echo '</pre>';

	echo '<fieldset style="margin-bottom: 2em;">'
		. '<h3>' . __('Manual SEO') . '</h3>';

	echo '<p>'
		. __('The following fields allow you to override the automatically generated title, keywords and description of your entries.')
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
			. ' value="' . htmlspecialchars(get_post_meta($post_ID, '_title', true))
			. '"/>'
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
			. ' value="' . htmlspecialchars(get_post_meta($post_ID, '_keywords', true))
			. '"/>'
		. '</td>'
		. '</tr>';

	echo '<tr>'
		. '<th style="text-align: right; width: 160px;">'
		. '<label for="seo_description">'
		. __('Description:')
		. '</label>'
		. '</th>'
		. '<td>'
		. '<textarea type="text"'
			. ' style="width: 420px; height: 80px;"'
			. ' id="seo_description" name="seo_description"'
			. '">'
			. htmlspecialchars(get_post_meta($post_ID, '_description', true))
		. '</textarea>'
		. '</td>'
		. '</tr>';

	echo '</table>';

	echo '</fieldset>';
} # display_seo_meta_fields()

add_action('edit_form_advanced', 'display_seo_meta_fields');
add_action('edit_page_form', 'display_seo_meta_fields');


#
# save_seo_meta_fields()
#

function save_seo_meta_fields($post_ID)
{
	#echo '<pre>';
	#var_dump($post_ID);
	#echo '</pre>';

	if ( !isset($_REQUEST['comment_post_ID']) )
	{
		foreach ( array('title', 'keywords', 'description') as $key )
		{
			if ( isset($_POST['seo_' . $key]) )
			{
				delete_post_meta($post_ID, '_' . $key);

				$value = trim(strip_tags(stripslashes($_POST['seo_' . $key])));

				if ( $value !== '' )
				{
					add_post_meta($post_ID, '_' . $key, $value, true);
				}
			}
		}
	}

	return $post_ID;
} # save_seo_meta_fields()


add_action('publish_post', 'save_seo_meta_fields');
add_action('save_post', 'save_seo_meta_fields');
add_action('edit_post', 'save_seo_meta_fields');
add_action('wp_insert_post', 'save_seo_meta_fields');
?>