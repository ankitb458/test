<?php
/**
 * article_uploader_admin
 *
 * @package Article Uploader
 **/

add_filter('get_user_option_rich_editing', array('article_uploader_admin', 'disable_tinymce'));

class article_uploader_admin {
	/**
	 * disable_tinymce()
	 *
	 * @param string $bool pseudo boolean
	 * @return string $bool
	 **/
	
	function disable_tinymce($bool) {
		global $post_ID;
		
		if ( !isset($post_ID) || $post_ID <= 0 )
			return $bool;
		
		if ( get_post_meta($post_ID, '_kill_formatting', true) === '1' )
			return 'false';
		else
			return $bool;
	} # disable_tinymce()
	
	
	/**
	 * entry_editor()
	 *
	 * @param object $post
	 * @return void
	 **/
	
	function entry_editor($post) {
		$post_ID = $post->ID;
		
		echo '<p>'
			. __('The article uploader lets you bypass WordPress\' editor when it stops working as expected -- which is frequent when you\'re pasting complicated copy, since WordPress destroys forms and scripts as it reformats html.', 'article-uploader')
			. '</p>' . "\n";
		
		echo '<p>'
			. '<label>'
			. '<input type="checkbox" id="kill_formatting" name="kill_formatting" tabindex="5"'
			. ( $post_ID > 0 && get_post_meta($post_ID, '_kill_formatting', true)
				? ' checked="checked"'
				: ''
				)
			. ' />'
			. '&nbsp;'
			. __('Turn off WordPress\'s rich text editor and content reformatting features on this entry.', 'article-uploader')
			. '</label>'
			. '</p>' . "\n";
		
		echo '<p>'
			. '<input type="file" name="upload_article" tabindex="5" />'
			. ' '
			. '<input type="submit" name="save" class="button" tabindex="5"'
			. ' value="' . __('Save', 'article-uploader') . '"'
			. ' />'
			. '</p>' . "\n";
		
		echo '<p>'
			. __('A few points to keep in mind when uploading an article:')
			. '</p>' . "\n";
		
		echo '<ul class="ul-square">' . "\n";
		
		echo '<li>'
			. sprintf(__('Maximum file size is %s based on your server\'s configuration.', 'article-uploader'), wp_convert_bytes_to_hr(apply_filters('import_upload_size_limit', wp_max_upload_size())))
			. '</li>' . "\n";
		
		echo '<li>'
			. __('Everything within the &lt;body&gt; and &lt;/body&gt; tags of your uploaded file will <strong>replace</strong> the entry\'s contents.', 'article-uploader')
			. '</li>' . "\n";
		
		echo '<li>'
			. __('It will <strong><em>really</em></strong> replace the entry\'s contents. No kidding!', 'article-uploader')
			. '</li>' . "\n";
		
		echo '<li>'
			. __('Your html is inserted <em>as is</em>, complete with any html error you may have left behind. If your site looks like a train wreck after you use the article uploader, it is because you\'ve uploaded invalidly nested html -- and you\'re on your own to fix it.', 'article-uploader')
			. '</li>' . "\n";
		
		echo '<li>'
			. __('Expanding on this, and as a tip... Prefer Dreamweaver to FrontPage if you\'re using the latter, consider checking your html document\'s syntax with an <a href="http://validator.w3.org">html validator</a>, and, if all else fails, have someone from a site like eLance fix the html code.', 'article-uploader')
			. '</li>' . "\n";
		
		echo '<li>'
			. __('The article uploader also works with plain text files.', 'article-uploader')
			. '</li>' . "\n";
		
		echo '<li>'
			. __('Uploading an html file using the above form will force off WordPress\' rich text editor and content reformatting features on this entry.', 'article-uploader')
			. '</li>' . "\n";
		
		echo '<li>'
			. __('If you\'re not writing in English, make sure your document\'s character encoding matches that of your site (find it under <a href="options-general.php">Settings / General</a>). Because if it doesn\'t, you may end up with odd looking characters all over the place.', 'article-uploader')
			. '</li>' . "\n";
		
		echo '</ul>' . "\n";
		
		echo '<p>'
			. __('The article uploader also works with plain text files. In this case, the rich text editor will remain turned on.', 'article-uploader')
			. '</p>' . "\n";
	} # entry_editor()
	
	
	/**
	 * save_entry()
	 *
	 * @param int $post_ID
	 * @return void
	 **/
	
	function save_entry($post_ID) {
		if ( wp_is_post_revision($post_ID) || !current_user_can('unfiltered_html') )
			return;
		
		global $wpdb;
		
		if ( isset($_POST['kill_formatting']) )
			update_post_meta($post_ID, '_kill_formatting', '1');
		
		if ( empty($_FILES['upload_article']['name']) )
			return;
		
		preg_match("/\.([^.]+)$/", $_FILES['upload_article']['name'], $ext);
		$ext = end($ext);
		
		switch ( strtolower($ext) ) {
		case 'htm':
		case 'html':
			$content = file_get_contents($_FILES['upload_article']['tmp_name']);
			
			if ( preg_match("/
				<\s*body(?:\s.*?)?\s*>
				(.*?)
				<\s*\/\s*body\s*>
				/isx", $content, $body)
				) {
				$content = end($body);
			}
			
			$content = trim($content);
			
			if ( $content ) {
				$wpdb->query("
					UPDATE	$wpdb->posts
					SET		post_content = '" . $wpdb->escape($content) . "'
					WHERE	ID = " . intval($post_ID)
					);
				
				update_post_meta($post_ID, '_kill_formatting', '1');
			}
			
			break;

		case 'txt':
		case 'text':
			$content = file_get_contents($_FILES['upload_article']['tmp_name']);
			$content = trim($content);
			$content = htmlspecialchars($content, ENT_COMPAT, get_option('blog_charset'));
			
			if ( $content ) {
				$wpdb->query("
					UPDATE	$wpdb->posts
					SET		post_content = '" . $wpdb->escape($content) . "'
					WHERE	ID = " . intval($post_ID)
					);
			}
			
			break;
		}
	} # save_entry()
} # article_uploader_admin
?>