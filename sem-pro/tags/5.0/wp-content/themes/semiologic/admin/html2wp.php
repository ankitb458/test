<?php
#
# display_html2wp()
#

function display_html2wp()
{
	if ( current_user_can('unfiltered_html') )
	{
		$post_ID = isset($GLOBALS['post_ID']) ? $GLOBALS['post_ID'] : $GLOBALS['temp_ID'];

		#echo '<pre>';
		#var_dump($post_ID);
		#echo '</pre>';

		echo '<div class="dbx-b-ox-wrapper">';

		echo '<fieldset id="semhtml2wp" class="dbx-box">'
			. '<div class="dbx-h-andle-wrapper">'
			. '<h3 class="dbx-handle">' . __('Upload File as Content / Article Uploader') . '</h3>'
			. '</div>';

		echo '<div class="dbx-c-ontent-wrapper">'
			. '<div id="semhtml2wpstuff" class="dbx-content">';

		if ( !sem_pro )
		{
			pro_feature_notice();
		}

		echo '<p>'
			. __('Semiologic Pro lets you upload a text or html file (from an article directory, generated using Front Page or, better yet, generated using <a href="http://www.semiologic.com/go/scribejuice">ScribeJuice</a>) in place of entering content in the WordPress editor. Its body area will be used as your entry\'s contents.')
			. '</p>'
			. '<p>'
			. __('This feature is convenient if you\'re working offline, or if you\'re writing a complicated sales letter. In the latter case, it will spare you a variety of bugs related to WordPress\' desire to "clean up" and sanitize your HTML code. (WordPress destroys forms, drops scripts, and reformats HTML.')
			. '</p>';

		echo '<p>'
			. __('<strong>Important Notice #1</strong>: If you\'re writing non-english language documents in HTML, be sure to make the character set of your html document match that of your blog (' . get_bloginfo('charset') . '). In most HTML editors, you can configure this in your document\'s properties (File / Properties).')
			. '</p>';

		echo '<p>'
			. __('<strong>Important Notice #2</strong>: Using this feature will <strong>REPLACE</strong> your post\'s content with the contents of the file you\'ve just uploaded. Further, it will disable the wysiwyg editor and WordPress formatting. Text files will be formatted to html automatically. Your content will otherwise be displayed <em>as is</em>.')
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
				. ( sem_pro
					? ''
					: ' disabled="disabled"'
					)
				. ' value="" />'
			. '</td>'
			. '</tr>';

		echo '</table>';

		echo '<div>'
			. '<label for"kill_wysiwyg">'
			. '<input type="checkbox"'
				. ' id="kill_wysiwyg" name="kill_wysiwyg"'
				. ( ( get_post_meta($_GET['post'], '_kill_formatting', true) )
					? ' checked="checked"'
					: ''
					)
				. ' />'
				. ' '
				. __('Disable the Wysiwyg Editor for this post')
				. '</label>'
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
	}
} # display_html2wp()

add_action('dbx_post_advanced', 'display_html2wp');
add_action('dbx_page_advanced', 'display_html2wp');



if ( !function_exists('ob_multipart_entry_form') ) :
#
# ob_multipart_entry_form_callback()
#

function ob_multipart_entry_form_callback($buffer)
{
	$buffer = str_replace(
		'<form name="post"',
		'<form enctype="multipart/form-data" name="post"',
		$buffer
		);

	return $buffer;
} # ob_multipart_entry_form_callback()


#
# ob_multipart_entry_form()
#

function ob_multipart_entry_form()
{
	if ( current_user_can('unfiltered_html') )
	{
		ob_start('ob_multipart_entry_form_callback');
	}
} # ob_multipart_entry_form()

add_action('admin_head', 'ob_multipart_entry_form');


#
# add_file_max_size()
#

function add_file_max_size()
{
	echo "\n" . '<input type="hidden" name="MAX_FILE_SIZE" value="32000000" />' . "\n";
}

add_action('edit_form_advanced', 'add_file_max_size');
add_action('edit_page_form', 'add_file_max_size');
endif;
?>