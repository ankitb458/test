<?php
/**
 * sem_header
 *
 * @package Semiologic Reloaded
 **/

add_action('admin_print_scripts', array('sem_header', 'scripts'));
add_action('appearance_page_header', array('sem_header', 'save_options'), 0);
add_action('save_post', array('sem_header', 'save_entry_header'), 30);

class sem_header {
	/**
	 * scripts()
	 *
	 * @return void
	 **/

	function scripts() {
		$header = header::get();
		
		if ( !$header )
			return;
		
		preg_match("/\.([^.]+)$/", $header, $ext);
		$ext = end($ext);
		
		if ( $ext == 'swf' )
			wp_enqueue_script('swfobject', sem_url . '/js/swfobject.js', false, '1.5');
	} # scripts()
	
	
	/**
	 * save_options()
	 *
	 * @return void
	 **/
	
	function save_options() {
		if ( !$_POST )
			return;
		
		check_admin_referer('sem_header');
		
		#dump($_POST, $_FILES);
		
		global $sem_options;
		$header = header::get();
		$active_skin = $sem_options['active_skin'];
		
		if ( !empty($_FILES['header_file']['name']) ) {
			if ( $header ) {
				if ( !is_writable(WP_CONTENT_DIR . $header) ) {
					echo '<div class="error">'
						. "<p>"
							. "<strong>"
							. sprintf(__('%s is not writable.', 'sem-reloaded'), 'wp-content' . $header)
							. "</strong>"
						. "</p>\n"
						. "</div>\n";
					return;
				} elseif ( strpos($header, "/$active_skin/") === false ) {
					@unlink(WP_CONTENT_DIR . $header);
				}
			}

			preg_match("/\.([^.]+)$/", $_FILES['header_file']['name'], $ext);
			$ext = end($ext);
			$ext = strtolower($ext);

			if ( !in_array($ext, defined('GLOB_BRACE') ? array('jpg', 'jpeg', 'png', 'gif', 'swf') : array('jpg')) ) {
				echo '<div class="error">'
					. "<p>"
						. "<strong>"
						. __('Invalid File Type.', 'sem-reloaded')
						. "</strong>"
					. "</p>\n"
					. "</div>\n";
				return;
			} else {
				$entropy = intval(get_option('sem_entropy')) + 1;
				update_option('sem_entropy', $entropy);
				
				$name = WP_CONTENT_DIR . '/header/header-' . $entropy . '.' . $ext;
				
				@move_uploaded_file($_FILES['header_file']['tmp_name'], $name);
				
				$stat = stat(dirname($name));
				$perms = $stat['mode'] & 0000666;
				@chmod($name, $perms);
			}
		} elseif ( isset($_POST['delete_header']) ) {
			if ( $header ) {
				if ( strpos($header, "/$active_skin/") === false ) {
					echo '<div class="error">'
						. "<p>"
							. "<strong>"
							. sprintf(__('%s is a skin-specific header.', 'sem-reloaded'), 'wp-content' . $header)
							. "</strong>"
						. "</p>\n"
						. "</div>\n";
				} elseif ( !is_writable(WP_CONTENT_DIR . $header) ) {
					echo '<div class="error">'
						. "<p>"
							. "<strong>"
							. sprintf(__('%s is not writable.', 'sem-reloaded'), 'wp-content' . $header)
							. "</strong>"
						. "</p>\n"
						. "</div>\n";
					return;
				} else {
					@unlink(WP_CONTENT_DIR . $header);
				}
			}
		}
		
		delete_transient('sem_header');
		
		echo '<div class="updated fade">'
			. '<p><strong>'
			. __('Settings saved.', 'sem-reloaded')
			. '</strong></p>'
			. '</div>' . "\n";
	} # save_options()
	
	
	/**
	 * edit_options()
	 *
	 * @return void
	 **/
	
	function edit_options() {
		echo '<div class="wrap">';
		
		echo '<form enctype="multipart/form-data" method="post" action="">';
		
		wp_nonce_field('sem_header');
		
		global $sem_options;
		
		$header = header::get();
		$active_skin = $sem_options['active_skin'];
		
		screen_icon();
		
		echo '<h2>' . __('Manage Header', 'sem-reloaded') . '</h2>' . "\n";

		echo '<p>'
			. __('The header\'s height will automatically adjust to fit your image or flash file. The width to use will depend on your <a href="?page=layout">layout</a>\'s canvas width, and on your <a href="?page=skin">skin</a> (strip 20px if you\'re using the Kubrick skin).', 'sem-reloaded')
			. '</p>' . "\n";
		
		if ( $header ) {
			echo '<h3>' . __('Current Header', 'sem-reloaded') . '</h3>';
			
			preg_match("/\.([^.]+)$/", $header, $ext);
			$ext = strtolower(end($ext));
			
			if ( $ext != 'swf' ) {
				echo '<p>'
					. header::display_image($header)
					. '</p>' . "\n";
			} else {
				echo header::display_flash($header);
			}
			
			if ( is_writable(WP_CONTENT_DIR . $header) ) {
				echo '<p>'
					. '<label>'
					. '<input type="checkbox" name="delete_header" />'
					. '&nbsp;'
					. __('Delete header', 'sem-reloaded')
					. '</label>'
					. '</p>' . "\n";
				
				echo '<div class="submit">'
					. '<input type="submit" value="' . esc_attr(__('Save Changes', 'sem-reloaded')) . '" />'
					. '</div>' . "\n";
			} else {
				echo '<p>'
					. sprintf(__('This header (%s) is not writable by the server. Please delete it manually to change it.', 'sem-reloaded'), 'wp-content' . $header)
					. '</p>' . "\n";
			}
		}
		
		wp_mkdir_p(WP_CONTENT_DIR . '/header');
		
		if ( !$header || is_writable(WP_CONTENT_DIR . $header) ) {
			if ( is_writable(WP_CONTENT_DIR . '/header') ) {
				echo '<h3>'
					. '<label for="header_file">'
						. ( defined('GLOB_BRACE')
							? __('Upload a New Header (jpg, png, gif, swf)', 'sem-reloaded')
							: __('Upload a New Header (jpg)', 'sem-reloaded')
							)
						. '</label>'
					. '</h3>' . "\n";
				
				echo '<p>'
					. '<input type="file" class="widefat" id="header_file" name="header_file" />'
					. '</p>' . "\n";
			} elseif ( !is_writable(WP_CONTENT_DIR) ) {
				echo '<p>'
					. __('Your wp-content folder is not writeable by the server', 'sem-reloaded')
					. '</p>' . "\n";
			} else {
				echo '<p>'
					. __('Your wp-content/header folder is not writeable by the server', 'sem-reloaded')
					. '</p>' . "\n";
			}
			
			echo '<div class="submit">'
				. '<input type="submit" value="' . esc_attr(__('Save Changes', 'sem-reloaded')) . '" />'
				. '</div>' . "\n";
		}
		
		echo '</form>' . "\n";
		
		echo '</div>' . "\n";
	} # edit_options()


	#
	# edit_entry_header()
	#

	function edit_entry_header()
	{
		$post_ID = isset($GLOBALS['post_ID']) ? $GLOBALS['post_ID'] : $GLOBALS['temp_ID'];

		if ( defined('GLOB_BRACE') )
		{
			if ( $post_ID > 0
				&& ( $header = glob(WP_CONTENT_DIR . '/header/' . $post_ID . '/header{,-*}.{jpg,jpeg,png,gif,swf}', GLOB_BRACE) )
				)
			{
				$header = current($header);
			}
		}
		else
		{
			if ( $post_ID > 0
				&& ( $header = glob(WP_CONTENT_DIR . '/header/' . $post_ID . '/header-*.jpg') )
				)
			{
				$header = current($header);
			}
		}

		if ( $header )
		{
			preg_match("/\.([^.]+)$/", $header, $ext);
			$ext = end($ext);
			
			echo '<div style="overflow: hidden;">';

			if ( $ext != 'swf' )
			{
				echo '<p>';

				echo sem_header::display_logo($header);

				echo '</p>' . "\n";
			}

			else
			{
				echo sem_header::display_flash($header);
			}

			echo '</div>';

			echo '<p>';

			if ( is_writable($header) )
			{
				echo '<label for="delete_header">'
					. '<input type="checkbox" tabindex="4"'
						. ' id="delete_header" name="delete_header"'
						. ' style="text-align: left; width: auto;"'
						. ' />'
					. '&nbsp;'
					. __('Delete header')
					. '</label>';
			}
			else
			{
				echo __('This header is not writable by the server.');
			}

			echo '</p>' . "\n";
		}

		if ( !defined('GLOB_BRACE') )
		{
			echo '<p>' . __('Notice: <a href="http://www.php.net/glob">GLOB_BRACE</a> is an undefined constant on your server. Non .jpg files will be ignored.') . '</p>';
		}

		@mkdir(WP_CONTENT_DIR . '/header');
		@chmod(WP_CONTENT_DIR . '/header', 0777);

		if ( !$header
			|| is_writable($header)
			)
		{
			echo '<p>'
				. '<label for="header_file">'
					. __('New Header (jpg, png, gif, swf)') . ':'
					. '</label>'
				. '<br />' . "\n";

			if ( is_writable(WP_CONTENT_DIR . '/header') )
			{
				echo '<input type="file" tabindex="5"'
					. ' id="header_file" name="header_file"'
					. ' />'
					. ' '
					. '<input type="submit" name="save" class="button" tabindex="5"'
					. ' value="' . __('Save') . '"'
					. ' />';
			}
			elseif ( !is_writable(WP_CONTENT_DIR . '') )
			{
				echo __('The wp-content folder is not writeable by the server') . "\n";
			}
			else
			{
				echo __('The wp-content/headers folder is not writeable by the server') . "\n";
			}

			echo '</p>' . "\n";
		}
	} # edit_entry_header()


	#
	# save_entry_header()
	#

	function save_entry_header($post_ID)
	{
		$post = get_post($post_ID);
		
		if ( $post->post_type == 'revision' ) return;
		
		if ( @ $_FILES['header_file']['name'] )
		{
			if ( defined('GLOB_BRACE') )
			{
				if ( $header = glob(WP_CONTENT_DIR . '/header/' . $post_ID . '/header{,-*}.{jpg,jpeg,png,gif,swf}', GLOB_BRACE) )
				{
					$header = current($header);
					@unlink($header);
				}
			}
			else
			{
				if ( $header = glob(WP_CONTENT_DIR . '/header/' . $post_ID . '/header-*.jpg') )
				{
					$header = current($header);
					@unlink($header);
				}
			}

			$tmp_name =& $_FILES['header_file']['tmp_name'];
			
			preg_match("/\.([^.]+)$/", $_FILES['header_file']['name'], $ext);
			$ext = end($ext);

			if ( !in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'swf')) )
			{
				echo '<div class="error">'
					. "<p>"
						. "<strong>"
						. __('Invalid File Type.')
						. "</strong>"
					. "</p>\n"
					. "</div>\n";
			}
			else
			{
				$entropy = get_option('sem_entropy');

				$entropy = intval($entropy) + 1;

				update_option('sem_entropy', $entropy);

				$name = WP_CONTENT_DIR . '/header/' . $post_ID . '/header-' . $entropy . '.' . $ext;

				@mkdir(WP_CONTENT_DIR . '/header/' . $post_ID);
				@chmod(WP_CONTENT_DIR . '/header/' . $post_ID, 0777);
				@move_uploaded_file($tmp_name, $name);
				$stat = stat(dirname($name));
				$perms = $stat['mode'] & 0000666;
				@chmod($name, $perms);
			}
			
			delete_post_meta($post_ID, '_sem_header');
		}
		elseif ( isset($_POST['delete_header']) )
		{
			if ( defined('GLOB_BRACE') )
			{
				if ( $header = glob(WP_CONTENT_DIR . '/header/' . $post_ID . '/header{,-*}.{jpg,jpeg,png,gif,swf}', GLOB_BRACE) )
				{
					$header = current($header);
					@unlink($header);
				}
			}
			else
			{
				if ( $header = glob(WP_CONTENT_DIR . '/header/' . $post_ID . '/header-*.jpg') )
				{
					$header = current($header);
					@unlink($header);
				}
			}
			
			delete_post_meta($post_ID, '_sem_header');
		}
	} # save_entry_header()
} # sem_header




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
	$bytes = apply_filters( 'import_upload_size_limit', wp_max_upload_size() );
	
	echo  "\n" . '<input type="hidden" name="MAX_FILE_SIZE" value="' . $bytes .'" />' . "\n";
}

add_action('edit_form_advanced', 'add_file_max_size');
add_action('edit_page_form', 'add_file_max_size');
endif;
?>