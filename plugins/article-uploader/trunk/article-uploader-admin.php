<?php

class article_uploader_admin
{
	#
	# init()
	#
	
	function init()
	{
		add_filter('sem_api_key_protected', array('article_uploader_admin', 'sem_api_key_protected'));
		
		add_filter('get_user_option_rich_editing', array('article_uploader_admin', 'disable_tinymce'));

		add_action('admin_menu', array('article_uploader_admin', 'meta_boxes'), 30);
	} # init()
	
	
	#
	# sem_api_key_protected()
	#
	
	function sem_api_key_protected($array)
	{
		$array[] = 'http://www.semiologic.com/media/software/publishing/article-uploader/article-uploader.zip';
		
		return $array;
	} # sem_api_key_protected()
	
	
	#
	# disable_tinymce()
	#
	
	function disable_tinymce($in)
	{
		global $post_ID;
		
		if ( !$GLOBALS['editing'] || $post_ID < 0 ) return $in;
		#var_dump($post_ID);
		if ( get_post_meta($post_ID, '_kill_formatting', true) )
		{
			return 'false';
		}
		
		return $in;
	} # disable_tinymce()
	
	
	#
	# meta_boxes()
	#
	
	function meta_boxes()
	{
		if ( current_user_can('unfiltered_html') )
		{
			add_meta_box('article_uploader', 'Article Uploader', array('article_uploader_admin', 'entry_editor'), 'post');
			add_meta_box('article_uploader', 'Article Uploader', array('article_uploader_admin', 'entry_editor'), 'page');
			add_action('save_post', array('article_uploader_admin', 'save_entry'));
		}
	} # meta_boxes()
	
	
	#
	# entry_editor()
	#
	
	function entry_editor()
	{
		$post_ID = isset($GLOBALS['post_ID']) ? $GLOBALS['post_ID'] : $GLOBALS['temp_ID'];
		
		$str = <<<EOF
<p>The article uploader lets you bypass WordPress' editor when it stops working as expected -- which is frequent when you're pasting complicated copy, since WordPress destroys forms and scripts as it reformats html.</p>
EOF;
		echo $str;
		
		echo '<p>'
			. '<label>'
			. '<input type="checkbox" id="kill_formatting" name="kill_formatting" tabindex="5"'
			. ( $post_ID > 0 && get_post_meta($post_ID, '_kill_formatting', true)
				? ' checked="checked"'
				: ''
				)
			. ' />'
			. '&nbsp;'
			. 'Turn off WordPress\'s rich text editor and content reformatting features on this entry.'
			. '</label>'
			. '</p>';
		
		echo '<p>'
			. '<input type="file" name="upload_article" tabindex="5" />'
			. ' '
			. '<input type="submit" name="save" class="button" tabindex="5"'
			. ' value="' . __('Save') . '"'
			. ' />'
			. '</p>';
		
		$str = <<<EOF
<p>A few points to keep in mind when uploading an article:</p>
<ul>
<li>Everything within the &lt;body&gt; and &lt;/body&gt; tags of your uploaded file will <b style="color: firebrick;">replace</b> the entry's contents at once.</li>
<li>It will <em>really</em> replace the entrie's contents. No kidding!</li>
<li>Uploading an html file using the above form will force off WordPress's rich text editor and content reformatting features on this entry.</li>
<li>Your html is inserted <i>as is</i>, complete with any html error you may have left behind.</li>
<li>Adding to the previous point: If your site looks like a train wreck after you use the article uploader, it is because you've uploaded invalidly nested html. Prefer Dreamweaver to FrontPage if you're using the latter, consider checking your document's syntax with an <a href="http://validator.w3.org">html validator</a>, and, if all else fails, have someone from a site like eLance fix the html code.</li>
<li>If you're not writing in English, make sure your document's character encoding matches that of your site (find it under Settings / General). Because if it doesn't, you may end up with odd looking characters all over the place.</li>
<li>Have you checked out Michel Fortin's <a href="http://go.semiologic.com/scribejuice">ScribeJuice</a>?</li>
</ul>
<p>The article uploader also works with plain text files. In this case, the rich text editor will remain turned on.</p>
EOF;
		echo $str;
		
	} # entry_editor()
	

	#
	# save_entry()
	#

	function save_entry($post_ID)
	{
		global $wpdb;
		
		if ( current_user_can('unfiltered_html') )
		{
			delete_post_meta($post_ID, '_kill_formatting');
			
			if ( $file_name = $_FILES['upload_article']['name'] )
			{
				$ext = pathinfo($file_name, PATHINFO_EXTENSION);
				
				switch ( strtolower($ext) )
				{
				case 'htm':
				case 'html':
					$content = file_get_contents($_FILES['upload_article']['tmp_name']);
					
					if ( preg_match("/
						<\s*body(?:\s.*?)?\s*>
						(.*)
						<\s*\/\s*body\s*>
						/isx", $content, $body)
						)
					{
						$content = end($body);
					}
					
					if ( trim($content) )
					{
						$_POST['content'] = addslashes($content);
						$_POST['kill_formatting'] = true;
						
						$wpdb->query("
							UPDATE	$wpdb->posts
							SET		post_content = '" . $wpdb->escape($content) . "'
							WHERE	ID = " . intval($post_ID)
							);
					}
					
					if ( isset($_POST['kill_formatting']) )
					{
						add_post_meta($post_ID, '_kill_formatting', '1', true);
					}
					break;

				case 'txt':
				case 'text':
					$content = file_get_contents($_FILES['upload_article']['tmp_name']);
					
					if ( trim($content) )
					{
						$_POST['content'] = addslashes($content);
						
						$wpdb->query("
							UPDATE	$wpdb->posts
							SET		post_content = '" . $wpdb->escape($content) . "'
							WHERE	ID = " . intval($post_ID)
							);
					}
					
					if ( isset($_POST['kill_formatting']) )
					{
						add_post_meta($post_ID, '_kill_formatting', '1', true);
					}
					
					break;
				}
				
			}
			elseif ( isset($_POST['kill_formatting']) )
			{
				$wpdb->query("
					UPDATE	$wpdb->posts
					SET		post_content = '" . $wpdb->escape(stripslashes($_POST['content'])) . "'
					WHERE	ID = " . intval($post_ID)
					);
				
				add_post_meta($post_ID, '_kill_formatting', '1', true);
			}
		}
	} # save_entry()
} # article_uploader_admin

article_uploader_admin::init();





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
	if ( $GLOBALS['editing'] )
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
	echo  "\n" . '<input type="hidden" name="MAX_FILE_SIZE" value="32000000" />' . "\n";
}

add_action('edit_form_advanced', 'add_file_max_size');
add_action('edit_page_form', 'add_file_max_size');
endif;
?>