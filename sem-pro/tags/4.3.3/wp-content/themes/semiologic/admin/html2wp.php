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
			. '<h3 class="dbx-handle">' . __('Upload File as Content') . '</h3>'
			. '</div>';

		echo '<div class="dbx-c-ontent-wrapper">'
			. '<div id="semhtml2wpstuff" class="dbx-content">';

		if ( !sem_pro )
		{
			pro_feature_notice();
		}

		echo '<p>'
			. __('Semiologic Pro lets you upload an html file (generated using Front Page or, better yet, <a href="http://www.semiologic.com/go/scribejuice">ScribeJuice</a>) in place of entering content in the WordPress editor. Its body area will be used as your entry\'s contents. This feature is convenient if you\'re working offline or writing a complicated sell letter.')
			. '</p>';

		echo '<p>'
			. __('<strong>Important</strong>: If you\'re writing non-english language documents be sure to make the charset of your html document match that of your blog (' . get_bloginfo('charset') . '). You can usually configure this in your document\'s properties.')
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