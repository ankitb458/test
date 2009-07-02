<?php

class author_image_admin
{
	#
	# init()
	#

	function init()
	{
		add_action('show_user_profile', array('author_image_admin', 'display_image'));
		add_action('personal_options_update', array('author_image_admin', 'save_image'));
	} # init()


	#
	# display_image()
	#

	function display_image()
	{
		$author_id = $GLOBALS['profileuser']->user_login;

		$site_url = trailingslashit(get_option('siteurl'));

		if ( $image = glob(ABSPATH . 'wp-content/authors/' . $author_id . '.{jpg,png}', GLOB_BRACE) )
		{
			$image = end($image);
		}

		echo '<fieldset>'
			. '<legend>'
			. __('Author Image')
			. '</legend>';

		if ( $image )
		{
			echo '<p>'
				. '<img src="'
						. str_replace(ABSPATH, $site_url, $image)
						. '"'
					. ' />' . "\n"
					.  '</p>' . "\n";

			echo '<p>';

			if ( is_writable($image) )
			{
				echo '<label for="delete_author_image">'
					. '<input type="checkbox"'
						. ' id="delete_author_image" name="delete_author_image"'
						. ' style="text-align: left; width: auto;"'
						. ' />'
					. '&nbsp;'
					. __('Delete author image')
					. '</label>';
			}
			else
			{
				echo __('This author image is not writable by the server.');
			}

			echo '</p>' . "\n";
		}

		@mkdir(ABSPATH . 'wp-content/authors');
		@chmod(ABSPATH . 'wp-content/authors', 0777);

		if ( !$image
			|| is_writable($image)
			)
		{
			echo '<p>'
				. '<label for="author_image">'
					. __('New Image (jpg or png)') . ':'
					. '</label>'
				. '<br />' . "\n";

			if ( is_writable(ABSPATH . 'wp-content/authors') )
			{
				echo '<input type="file" style="width: 480px;"'
					. ' id="author_image" name="author_image"'
					. ' />' . "\n";
			}
			elseif ( !is_writable(ABSPATH . 'wp-content') )
			{
				echo __('The wp-content folder is not writeable by the server') . "\n";
			}
			else
			{
				echo __('The wp-content/authors folder is not writeable by the server') . "\n";
			}

			echo '</p>' . "\n";
		}


		echo '</fieldset>';
	} # display_image()


	#
	# save_image()
	#

	function save_image()
	{
		if ( @ $_FILES['author_image']['name'] )
		{
			$user_ID = $_POST['checkuser_id'];
			$user = get_userdata($user_ID);
			$author_id = $user->user_login;

			if ( $image = glob(ABSPATH . 'wp-content/authors/' . $author_id . '.{jpg,png}', GLOB_BRACE) )
			{
				foreach ( $image as $img )
				{
					@unlink($img);
				}
			}

			$tmp_name =& $_FILES['author_image']['tmp_name'];

			preg_match("/\.([^\.]+)$/", $_FILES['author_image']['name'], $ext);
			$ext = end($ext);

			if ( !in_array($ext, array('jpg', 'png')) )
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
				$name = ABSPATH . 'wp-content/authors/' . $author_id . '.' . $ext;

				@move_uploaded_file($tmp_name, $name);
				@chmod($name, 0666);
			}
		}
		elseif ( isset($_POST['delete_author_image']) )
		{
			$user_ID = $_POST['checkuser_id'];
			$user = get_userdata($user_ID);
			$author_id = $user->user_login;

			if ( $image = glob(ABSPATH . 'wp-content/authors/' . $author_id . '.{jpg,png}', GLOB_BRACE) )
			{
				$image = end($image);
			}

			if ( $image )
			{
				@unlink($image);
			}
		}
	} # save_image()
} # author_image_admin

author_image_admin::init();




if ( !function_exists('ob_multipart_author_form') ) :
#
# ob_multipart_author_form_callback()
#

function ob_multipart_author_form_callback($buffer)
{
	$buffer = str_replace(
		'<form name="profile"',
		'<form enctype="multipart/form-data" name="profile"',
		$buffer
		);
	return $buffer;
} # ob_multipart_author_form_callback()


#
# ob_multipart_author_form()
#

function ob_multipart_author_form()
{
	ob_start('ob_multipart_author_form_callback');
} # ob_multipart_author_form()

add_action('admin_head', 'ob_multipart_author_form');
endif;
?>