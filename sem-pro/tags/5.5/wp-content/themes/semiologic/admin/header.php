<?php

class sem_header_admin
{
	#
	# init()
	#

	function init()
	{
		add_action('admin_menu', array('sem_header_admin', 'add_admin_page'));
		add_action('admin_head', array('sem_header', 'display_script'));

		add_action('dbx_post_advanced', array('sem_header_admin', 'display_header'));
		add_action('dbx_page_advanced', array('sem_header_admin', 'display_header'));
	} # init()


	#
	# widget_control()
	#

	function widget_control()
	{
		global $sem_options;

		if ( $_POST['update_sem_header']['header'] )
		{
			$new_options = $sem_options;

			$new_options['invert_header'] = isset($_POST['sem_header']['invert_header']);

			if ( $new_options != $sem_options )
			{
				$sem_options = $new_options;

				update_option('sem5_options', $sem_options);
			}
		}

		echo '<input type="hidden" name="update_sem_header[header]" value="1" />';

		echo '<h3>'
			. __('Config')
			. '</h3>';

		echo '<div style="margin-bottom: .2em;">'
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

		echo '<div>'
			. '<br />'
			. __('You can configure a header image under Presentation / Header')
			. '</div>';
	} # widget_control()


	#
	# display_header()
	#

	function display_header()
	{
		if ( current_user_can('switch_themes') )
		{
			$post_ID = isset($GLOBALS['post_ID']) ? $GLOBALS['post_ID'] : $GLOBALS['temp_ID'];

			#echo '<pre>';
			#var_dump($post_ID);
			#echo '</pre>';

			echo '<div class="dbx-b-ox-wrapper">';

			echo '<fieldset id="semheader" class="dbx-box">'
				. '<div class="dbx-h-andle-wrapper">'
				. '<h3 class="dbx-handle">' . __('Header') . '</h3>'
				. '</div>';

			echo '<div class="dbx-c-ontent-wrapper">'
				. '<div id="semheaderstuff" class="dbx-content">';

			if ( !sem_pro )
			{
				pro_feature_notice();
			}

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
				preg_match("/\.([^\.]+)$/", $header, $ext);
				$ext = end($ext);

				echo '<div style="overflow: hidden;">';

				if ( $ext != 'swf' )
				{
					echo '<p>';

					sem_header::display_logo($header);

					echo '</p>' . "\n";
				}

				else
				{
					sem_header::display_flash($header);
				}

				echo '</div>';

				echo '<p>';

				if ( is_writable($header) )
				{
					echo '<label for="delete_header">'
						. '<input type="checkbox"'
							. ' id="delete_header" name="delete_header"'
							. ' style="text-align: left; width: auto;"'
							. ( !sem_pro
								? ' disabled="disabled"'
								: ''
								)
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

			echo '<p class="submit">'
				. '<input type="button"'
				. ' value="' . __('Save and Continue Editing') . '"'
				. ' onclick="return form.save.click();"'
				. ' />'
				. '</p>';

			echo '</div>';
			echo '</div>';

			echo '</fieldset>';

			echo '</div>';
		}
	} # display_header()


	#
	# add_admin_page()
	#

	function add_admin_page()
	{
		if ( !defined('GLOB_BRACE')
			|| !glob(sem_path . '/skins/' . get_active_skin() . '/{header,header-background,header-bg,logo}.{jpg,jpeg,png,gif,swf}', GLOB_BRACE)
			)
		{
			add_submenu_page(
				'themes.php',
				__('Header'),
				__('Header'),
				'switch_themes',
				basename(__FILE__),
				array('sem_header_admin', 'display_admin_page')
				);
		}
	} # add_admin_page()


	#
	# display_admin_page()
	#

	function display_admin_page()
	{
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
		echo '<h2>' . __('Header') . '</h2>';

		echo '<p>' . __('You\'ll find a few <a href="http://www.semiologic.com/software/wp-themes/sem-headers/">generic headers</a> on semiologic.com.') . '</p>';

		if ( sem_pro )
		{
			echo '<p>' . __('You can also have a <a href="http://www.semiologic.com/members/sem-pro/services/">graphic designer</a> create one for you.') . '</p>';
		}

		if ( !defined('GLOB_BRACE') )
		{
			echo '<p>' . __('Notice: <a href="http://www.php.net/glob">GLOB_BRACE</a> is an undefined constant on your server. Non .jpg files will be ignored.') . '</p>';
		}

		echo '<h3>' . __('Header File') . '</h3>';

		if ( $header )
		{
			preg_match("/\.([^\.]+)$/", $header, $ext);
			$ext = end($ext);

			if ( $ext != 'swf' )
			{
				echo '<p>';

				sem_header::display_logo($header);

				echo '</p>' . "\n";
			}

			else
			{
				sem_header::display_flash($header);
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

			preg_match("/\.([^\.]+)$/", $_FILES['header_file']['name'], $ext);
			$ext = end($ext);

			if ( !in_array(strtolower($ext), array('jpg', 'jpeg', 'png', 'gif', 'swf')) )
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
			preg_match("/\.([^\.]+)$/",$header, $ext);
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

		update_option('sem5_options', $sem_options);
	} # save_header()
} # sem_header_admin

sem_header_admin::init();
?>