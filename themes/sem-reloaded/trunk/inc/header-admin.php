<?php
class sem_header_admin
{
	#
	# init()
	#

	function init()
	{
		add_action('admin_menu', array('sem_header_admin', 'add_admin_page'));
		
		add_action('admin_menu', array('sem_header_admin', 'meta_boxes'));
		add_action('save_post', array('sem_header_admin', 'save_entry_header'), 30);
	} # init()
	
	
	#
	# meta_boxes()
	#
	
	function meta_boxes()
	{
		if ( current_user_can('switch_themes') )
		{
			add_meta_box('sem_header', 'Post-Specific Header', array('sem_header_admin', 'display_entry_header'), 'post');
			add_meta_box('sem_header', 'Page-Specific Header', array('sem_header_admin', 'display_entry_header'), 'page');
		}
	} # meta_boxes()


	#
	# header_widget_control()
	#

	function header_widget_control()
	{
		global $sem_options;

		if ( $_POST['update_sem_header']['header'] )
		{
			$new_options = $sem_options;

			$new_options['invert_header'] = isset($_POST['sem_header']['invert_header']);

			if ( $new_options != $sem_options )
			{
				$sem_options = $new_options;

				update_option('sem6_options', $sem_options);
			}
		}

		echo '<input type="hidden" name="update_sem_header[header]" value="1" />';

		echo '<h3>'
			. __('Config')
			. '</h3>';

		echo '<div style="margin-bottom: 1em;">'
			. '<label>'
			. '<input type="checkbox"'
				. ' name="sem_header[invert_header]"'
				. ( $sem_options['invert_header']
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. ' '
			. __('Show site name, then tagline')
				. '<br />'
				. __('Note: While prettier, this can be slightly less effective from an SEO standpoint')
			. '</label>'
			. '</div>';

		echo '<p>'
			. __('To configure the header image, browse Design / Header.')
			. '</p>';
	} # header_widget_control()


	#
	# navbar_widget_control()
	#

	function navbar_widget_control()
	{
		global $sem_options;
		global $sem_captions;
		
		if ( $_POST['update_sem_header']['nav_menu'] )
		{
			$new_options = $sem_options;
			$new_captions = $sem_captions;

			$new_options['show_search_form'] = isset($_POST['sem_header']['show_search_form']);

			$new_captions['search_field'] = strip_tags(stripslashes($_POST['sem_header']['label_search_field']));
			$new_captions['search_button'] = strip_tags(stripslashes($_POST['sem_header']['label_search_button']));

			if ( $new_options != $sem_options )
			{
				$sem_options = $new_options;

				update_option('sem6_options', $sem_options);
			}
			if ( $new_captions != $sem_captions )
			{
				$sem_captions = $new_captions;

				update_option('sem6_captions', $sem_captions);
			}
		}

		echo '<input type="hidden" name="update_sem_header[nav_menu]" value="1" />';

		echo '<h3>'
			. __('Config')
			. '</h3>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. '<input type="checkbox"'
				. ' name="sem_header[show_search_form]"'
				. ( $sem_options['show_search_form']
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. ' '
			. __('Insert Search Form')
			. '</label>'
			. '</div>';

		echo '<h3>'
			. __('Captions')
			. '</h3>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. __('Search Field, e.g. Search')
			. '<br />'
			. '<input type="text" style="width: 95%"'
				. ' name="sem_header[label_search_field]"'
				. ' value="' . attribute_escape($sem_captions['search_field']) . '"'
				. ' />'
			. '</label>'
			. '</div>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. __('Search Button, e.g. Go')
			. '<br />'
			. '<input type="text" style="width: 95%"'
				. ' name="sem_header[label_search_button]"'
				. ' value="' . attribute_escape($sem_captions['search_button']) . '"'
				. ' />'
			. '</label>'
			. '</div>';

		sem_nav_menus_admin::widget_control('header');
	} # navbar_widget_control()


	#
	# add_admin_page()
	#

	function add_admin_page()
	{
		add_submenu_page(
			'themes.php',
			__('Header'),
			__('Header'),
			'switch_themes',
			basename(__FILE__),
			array('sem_header_admin', 'display_admin_page')
			);
	} # add_admin_page()


	#
	# display_admin_page()
	#

	function display_admin_page()
	{
		if ( defined('GLOB_BRACE')
			&& ( $header = glob(sem_path . '/skins/' . get_active_skin() . '/{header,header-background,header-bg,logo}.{jpg,jpeg,png,gif,swf}', GLOB_BRACE) )
			)
		{
			$header = current($header);
			
			echo '<div class="error">'
				. '<p>'
				. 'Your Semiologic skin contains a hard-coded header. Delete this file before continuing:'
				. '</p>'
				. '<p>'
				. str_replace(ABSPATH, '', $header)
				. '</p>'
				. '</div>';
			
			return;
		}
		
		if ( !empty($_POST)
			&& isset($_POST['action'])
			&& $_POST['action'] == 'update_theme_header_options'
			)
		{
			sem_header_admin::save_header();

			echo "<div class=\"updated\">\n"
				. "<p>"
					. "<strong>"
					. __('Options saved.')
					. "</strong>"
				. "</p>\n"
				. "</div>\n";
		}

		echo '<form enctype="multipart/form-data" method="post" action="">';

		if ( function_exists('wp_nonce_field') ) wp_nonce_field('sem_header');

		global $sem_options;

		$header = sem_header::get_header();

		echo '<input type="hidden"'
			. ' name="action"'
			. ' value="update_theme_header_options"'
			. ' />';

		echo '<div class="wrap">';
		echo '<h2>' . __('Header Settings') . '</h2>';

		echo '<p>' . __('You\'ll find a few <a href="http://www.semiologic.com/software/wp-themes/sem-headers/">generic headers</a> on semiologic.com.') . '</p>' . "\n";

		echo '<p>' . __('You can also have a <a href="http://www.semiologic.com/members/sem-pro/services/">graphic designer</a> create one for you.') . '</p>' . "\n";
		
		echo '<p>'
			. 'The header\'s height will automatically adjust to fit your image or flash file. The available width is 950px, 750px or 620px, depending on your layout. (Strip 20px to these values for the Kubrick skin.)'
			. '</p>' . "\n";
		
		if ( !defined('GLOB_BRACE') )
		{
			echo '<p>' . __('Notice: <a href="http://www.php.net/glob">GLOB_BRACE</a> is an undefined constant on your server. Non .jpg files will be ignored.') . '</p>' . "\n";
		}

		echo '<h3>' . __('Header File') . '</h3>';

		if ( $header )
		{
			preg_match("/\.([^.]+)$/", $header, $ext);
			$ext = end($ext);

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

			echo '<p>';

			if ( is_writable($header) )
			{
				echo '<label for="delete_header">'
					. '<input type="checkbox"'
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

		@mkdir(ABSPATH . 'wp-content/header');
		@chmod(ABSPATH . 'wp-content/header', 0777);

		if ( !$header
			|| is_writable($header)
			)
		{
			echo '<p>'
				. '<label for="header_file">'
					. __('New Header (jpg, png, gif, swf)') . ':'
					. '</label>'
				. '<br />' . "\n";

			if ( is_writable(ABSPATH . 'wp-content/header') )
			{
				echo '<input type="file" style="width: 480px;"'
					. ' id="header_file" name="header_file"'
					. ' />' . "\n";
			}
			elseif ( !is_writable(ABSPATH . 'wp-content') )
			{
				echo __('The wp-content folder is not writeable by the server') . "\n";
			}
			else
			{
				echo __('The wp-content/headers folder is not writeable by the server') . "\n";
			}

			echo '</p>' . "\n";
		}

		echo '<div class="submit">';
		echo '<input type="submit" value="' . __('Update Options') . ' &raquo;" />';
		echo '</div>';


		echo '<h3>'
			. __('Header Options')
			. '</h3>';

		if ( !isset($sem_options['header']['mode']) )
		{
			$sem_options['header']['mode'] = 'header';
		}

		echo '<p>'
			. '<label for="header[mode][header]">'
			. '<input type="radio"'
				. 'id=header[mode][header] name="header[mode]"'
				. ' value="header"'
				. ( ( $sem_options['header']['mode'] == 'header' )
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '&nbsp;'
			. __('Use my chosen file as the site\'s header. The default Semiologic theme header will be replaced with whichever file I uploaded (image or flash file...).')
			. '</label>'
			. '</p>';

		echo '<p>'
			. '<label for="header[mode][background]">'
			. '<input type="radio"'
				. 'id=header[mode][background] name="header[mode]"'
				. ' value="background"'
				. ( ( $sem_options['header']['mode'] == 'background' )
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '&nbsp;'
			. __('Use my <u>image</u> file as a background for the site\'s header. That image will display as a background for Semiologic\'s default, text-only header.')
			. '</label>'
			. '</p>';

		echo '<p>'
			. '<label for="header[mode][logo]">'
			. '<input type="radio"'
				. 'id=header[mode][logo] name="header[mode]"'
				. ' value="logo"'
				. ( ( $sem_options['header']['mode'] == 'logo' )
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '&nbsp;'
			. __('Use my file as a logo in place of the site\'s name. I\'ll be using Semiologic\'s default, text-only header, with one exception: My image (or flash file) will replace the site\'s name.')
			. '</label>'
			. '</p>';

		echo '<div class="submit">';
		echo '<input type="submit" value="' . __('Update Options') . ' &raquo;" />';
		echo '</div>';

		echo '</div>';

		echo '</form>';
	} # display_admin_page()


	#
	# save_header()
	#

	function save_header()
	{
		check_admin_referer('sem_header');

		#echo '<pre>';
		#var_dump($_POST);
		#var_dump($_FILES);
		#echo '</pre>';

		global $sem_options;

		if ( @ $_FILES['header_file']['name'] )
		{
			if ( $header = sem_header::get_header() )
			{
				@unlink($header);
			}

			$tmp_name =& $_FILES['header_file']['tmp_name'];
			
			preg_match("/\.([^.]+)$/", $_FILES['header_file']['name'], $ext);
			$ext = end($ext);
			$ext = strtolower($ext);

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

				$name = ABSPATH . 'wp-content/header/header-' . $entropy . '.' . $ext;

				@move_uploaded_file($tmp_name, $name);
				@chmod($name, 0666);
			}
		}
		elseif ( isset($_POST['delete_header']) )
		{
			if ( $header = sem_header::get_header() )
			{
				@unlink($header);
			}
		}

		if ( $header = sem_header::get_header() )
		{
			preg_match("/\.([^.]+)$/", $header, $ext);
			$ext = end($ext);

			if ( $ext == 'swf' && $_POST['header']['mode'] == 'background' )
			{
				$_POST['header']['mode'] = 'header';
			}
		}
		else
		{
			$_POST['header']['mode'] = 'header';
		}

		if ( !in_array($_POST['header']['mode'], array('header', 'background', 'logo')) )
		{
			$_POST['header']['mode'] = 'header';
		}

		$sem_options['header'] = $_POST['header'];

		update_option('sem6_options', $sem_options);
	} # save_header()


	#
	# display_entry_header()
	#

	function display_entry_header()
	{
		$post_ID = isset($GLOBALS['post_ID']) ? $GLOBALS['post_ID'] : $GLOBALS['temp_ID'];

		if ( defined('GLOB_BRACE') )
		{
			if ( $post_ID > 0
				&& ( $header = glob(ABSPATH . 'wp-content/header/' . $post_ID . '/header{,-*}.{jpg,jpeg,png,gif,swf}', GLOB_BRACE) )
				)
			{
				$header = current($header);
			}
		}
		else
		{
			if ( $post_ID > 0
				&& ( $header = glob(ABSPATH . 'wp-content/header/' . $post_ID . '/header-*.jpg') )
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

		@mkdir(ABSPATH . 'wp-content/header');
		@chmod(ABSPATH . 'wp-content/header', 0777);

		if ( !$header
			|| is_writable($header)
			)
		{
			echo '<p>'
				. '<label for="header_file">'
					. __('New Header (jpg, png, gif, swf)') . ':'
					. '</label>'
				. '<br />' . "\n";

			if ( is_writable(ABSPATH . 'wp-content/header') )
			{
				echo '<input type="file" tabindex="5"'
					. ' id="header_file" name="header_file"'
					. ' />'
					. ' '
					. '<input type="submit" name="save" class="button" tabindex="5"'
					. ' value="' . __('Save') . '"'
					. ' />';
			}
			elseif ( !is_writable(ABSPATH . 'wp-content') )
			{
				echo __('The wp-content folder is not writeable by the server') . "\n";
			}
			else
			{
				echo __('The wp-content/headers folder is not writeable by the server') . "\n";
			}

			echo '</p>' . "\n";
		}
	} # display_entry_header()


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
				if ( $header = glob(ABSPATH . 'wp-content/header/' . $post_ID . '/header{,-*}.{jpg,jpeg,png,gif,swf}', GLOB_BRACE) )
				{
					$header = current($header);
					@unlink($header);
				}
			}
			else
			{
				if ( $header = glob(ABSPATH . 'wp-content/header/' . $post_ID . '/header-*.jpg') )
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

				$name = ABSPATH . 'wp-content/header/' . $post_ID . '/header-' . $entropy . '.' . $ext;

				@mkdir(ABSPATH . 'wp-content/header/' . $post_ID);
				@chmod(ABSPATH . 'wp-content/header/' . $post_ID, 0777);
				@move_uploaded_file($tmp_name, $name);
				@chmod($name, 0666);
			}
		}
		elseif ( isset($_POST['delete_header']) )
		{
			if ( defined('GLOB_BRACE') )
			{
				if ( $header = glob(ABSPATH . 'wp-content/header/' . $post_ID . '/header{,-*}.{jpg,jpeg,png,gif,swf}', GLOB_BRACE) )
				{
					$header = current($header);
					@unlink($header);
				}
			}
			else
			{
				if ( $header = glob(ABSPATH . 'wp-content/header/' . $post_ID . '/header-*.jpg') )
				{
					$header = current($header);
					@unlink($header);
				}
			}
		}
	} # save_entry_header()
	
	
	#
	# header_boxes_widget_control()
	#
	
	function header_boxes_widget_control()
	{
		echo '<p>'
			. 'Use this widget to place the header boxes bar panel where you want it.'
			. '</p>';
	} # header_boxes_widget_control()
} # sem_header_admin

sem_header_admin::init();


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