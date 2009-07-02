<?php

class header_admin
{
	#
	# init()
	#

	function init()
	{
		add_action('admin_menu', array('header_admin', 'add_admin_page'));
		add_action('admin_head', array('header', 'display_script'));
	} # init()


	#
	# add_admin_page()
	#

	function add_admin_page()
	{
		if ( !glob(TEMPLATEPATH . '/skins/' . get_active_skin() . '/{header,header-background,header-bg,logo}.{jpg,png,gif,swf}', GLOB_BRACE) )
		{
			add_submenu_page(
				'themes.php',
				__('Header'),
				__('Header'),
				'switch_themes',
				str_replace("\\", "/", basename(__FILE__)),
				array('header_admin', 'display_admin_page')
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
			header_admin::save_header();

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

		$options = get_option('semiologic');

		$header = header::get_header();

		echo '<input type="hidden"'
			. ' name="action"'
			. ' value="update_theme_header_options"'
			. ' />';

		echo '<div class="wrap">';
		echo '<h2>' . __('Header') . '</h2>';

		echo '<p>' . __('You\'ll find a few <a href="http://www.semiologic.com/software/wp-themes/sem-headers/">generic headers</a> on semiologic.com.') . '</p>';

		if ( sem_pro )
		{
			echo '<p>' . __('You can also have a <a href="http://wp-pro.semiologic.com/services/">graphic designer</a> create one for you.') . '</p>';
		}

		echo '<h3>' . __('Header File') . '</h3>';

		if ( $header )
		{
			preg_match("/\.([^\.]+)$/", $header, $ext);
			$ext = end($ext);

			if ( $ext != 'swf' )
			{
				echo '<p>';

				header::display_logo($header);

				echo '</p>' . "\n";
			}

			else
			{
				header::display_flash($header);
			}

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

		if ( !isset($options['header']['mode']) )
		{
			$options['header']['mode'] = 'header';
		}

		echo '<p>'
			. '<label for="header[mode][header]">'
			. '<input type="radio"'
				. 'id=header[mode][header] name="header[mode]"'
				. ' value="header"'
				. ( ( $options['header']['mode'] == 'header' )
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '&nbsp;'
			. __('Use this file as the site\'s header.')
			. '</label>'
			. '</p>';

		echo '<p>'
			. '<label for="header[mode][background]">'
			. '<input type="radio"'
				. 'id=header[mode][background] name="header[mode]"'
				. ' value="background"'
				. ( ( $options['header']['mode'] == 'background' )
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '&nbsp;'
			. __('Use this <u>image</u> file as a background for the site\'s header.')
			. '</label>'
			. '</p>';

		echo '<p>'
			. '<label for="header[mode][logo]">'
			. '<input type="radio"'
				. 'id=header[mode][logo] name="header[mode]"'
				. ' value="logo"'
				. ( ( $options['header']['mode'] == 'logo' )
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '&nbsp;'
			. __('Use this file as a logo in place of the site\'s name.')
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

		$options = get_option('semiologic');

		if ( @ $_FILES['header_file']['name'] )
		{
			if ( $header = header::get_header() )
			{
				@unlink($header);
			}

			$tmp_name =& $_FILES['header_file']['tmp_name'];

			preg_match("/\.([^\.]+)$/", $_FILES['header_file']['name'], $ext);
			$ext = end($ext);

			if ( !in_array($ext, array('jpg', 'png', 'gif', 'swf')) )
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
				$name = ABSPATH . 'wp-content/header/header.' . $ext;

				@move_uploaded_file($tmp_name, $name);
				@chmod($name, 0666);
			}
		}
		elseif ( isset($_POST['delete_header']) )
		{
			if ( $header = header::get_header() )
			{
				@unlink($header);
			}
		}

		if ( $header = header::get_header() )
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

		$options['header'] = $_POST['header'];

		update_option('semiologic', $options);
	} # save_header()
} # header_admin

header_admin::init();
?>