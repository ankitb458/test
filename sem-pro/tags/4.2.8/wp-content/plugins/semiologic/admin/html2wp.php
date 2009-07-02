<?php
#
# display_html2wp()
#

function display_html2wp()
{
	$post_ID = isset($GLOBALS['post_ID']) ? $GLOBALS['post_ID'] : $GLOBALS['temp_ID'];

	#echo '<pre>';
	#var_dump($post_ID);
	#echo '</pre>';

	echo '<fieldset style="margin-bottom: 2em;">'
		. '<h3>' . __('Upload content as an HTML file') . '</h3>';

	echo '<p>'
		. __('Enter an html file (generated using Front Page, for instance) to use its contents as the post or page\'s contents.')
		. '</p>';

	echo '<p>'
		. __('Be sure to make the charset of your html document match that of the blog (' . get_bloginfo('charset') . ').')
		. '</p>';

	echo '<table width="100%" cellspacing="2" cellpadding="5" class="editform">';

	echo '<tr>'
		. '<th style="text-align: right; width: 160px;">'
		. '<label for="html2wp">'
		. __('File on your PC:')
		. '</label>'
		. '</th>'
		. '<td>'
		. '<input type="file"'
			. ' style="width: 420px;"'
			. ' id="html2wp" name="html2wp"'
			. ' value="" />'
		. '</td>'
		. '</tr>';

	echo '</table>';

	echo '</fieldset>';
} # display_html2wp()

add_action('edit_form_advanced', 'display_html2wp', 1);
add_action('edit_page_form', 'display_html2wp', 1);


#
# ob_html2wp_callback()
#

function ob_html2wp_callback($buffer)
{
	$buffer = str_replace(
		'<form name="post"',
		'<form enctype="multipart/form-data" name="post"',
		$buffer
		);

	return $buffer;
} # ob_html2wp_callback()


#
# ob_html2wp()
#

function ob_html2wp()
{
	ob_start('ob_html2wp_callback');
} # ob_html2wp()

add_action('admin_head', 'ob_html2wp');


#
# save_html2wp()
#

function save_html2wp($post_ID)
{
	global $wpdb;

	if ( isset($_FILES['html2wp']) )
	{
		$html = file_get_contents($_FILES['html2wp']['tmp_name']);

		$html = preg_replace("/^.+<\s*body(?:\s[^>]*)?>/isx", "", $html);
		$html = preg_replace("/<\s*\/\s*body\s*>.+$/isx", "", $html);

		if ( $html )
		{
			$html = preg_replace("/
				<p(\s[^>]*)?>
				(\s*<[^>]*>\s*)*
				\s*
				&nbsp;
				\s*
				(\s*<[^>]*>\s*)*
				<\/p>
				/ix", "", $html);

			$html = preg_replace("/
				<br(\s*\/)?>
				\s*
				\n
				\s*
				&nbsp;
				/ix", "", $html);

			$html = force_balance_tags($html);

			if (current_user_can('unfiltered_html') == false)
			{
				$html = wp_filter_post_kses($html);
			}
			else
			{
				$html = addslashes($html);
			}

			$wpdb->query(
				"UPDATE $wpdb->posts
				SET post_content = '" . $html . "'
				WHERE ID = " . intval($post_ID)
				);

			delete_post_meta($post_ID, '_kill_formatting');
			add_post_meta($post_ID, '_kill_formatting', 1, true);
		}
	}


	return $post_ID;
} # save_html2wp()


add_action('publish_post', 'save_html2wp');
add_action('save_post', 'save_html2wp');
add_action('edit_post', 'save_html2wp');
add_action('wp_insert_post', 'save_html2wp');
?>