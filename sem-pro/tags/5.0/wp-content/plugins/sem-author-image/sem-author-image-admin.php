<?php

class sem_author_image_admin
{
	#
	# init()
	#

	function init()
	{
		add_action('edit_user_profile', array('sem_author_image_admin', 'display_image'));
		add_action('profile_update', array('sem_author_image_admin', 'save_image'));

		add_action('show_user_profile', array('sem_author_image_admin', 'display_image'));
		add_action('personal_options_update', array('sem_author_image_admin', 'save_image'));
	} # init()


	#
	# display_image()
	#

	function display_image()
	{
		$author_id = $GLOBALS['profileuser']->user_login;

		$site_url = trailingslashit(get_option('siteurl'));

		if ( defined('GLOB_BRACE') )
		{
			if ( $image = glob(ABSPATH . 'wp-content/authors/' . $author_id . '{,-*}.{jpg,jpeg,png}', GLOB_BRACE) )
			{
				$image = end($image);
			}
		}
		else
		{
			if ( $image = glob(ABSPATH . 'wp-content/authors/' . $author_id . '-*.jpg') )
			{
				$image = end($image);
			}
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

		if ( !defined('GLOB_BRACE') )
		{
			echo '<p>' . __('Notice: GLOB_BRACE is an undefined constant on your server. Non .jpg images will be ignored.') . '</p>';
		}

		echo '</fieldset>';
	} # display_image()


	#
	# save_image()
	#

	function save_image($user_ID)
	{
		if ( @ $_FILES['author_image']['name'] )
		{
			$user = get_userdata($user_ID);
			$author_id = $user->user_login;

			if ( defined('GLOB_BRACE') )
			{
				if ( $image = glob(ABSPATH . 'wp-content/authors/' . $author_id . '{,-*}.{jpg,jpeg,png}', GLOB_BRACE) )
				{
					foreach ( $image as $img )
					{
						@unlink($img);
					}
				}
			}
			else
			{
				if ( $image = glob(ABSPATH . 'wp-content/authors/' . $author_id . '-*.jpg') )
				{
					foreach ( $image as $img )
					{
						@unlink($img);
					}
				}
			}

			$tmp_name =& $_FILES['author_image']['tmp_name'];

			preg_match("/\.([^\.]+)$/", $_FILES['author_image']['name'], $ext);
			$ext = end($ext);

			if ( !in_array($ext, array('jpg', 'jpeg', 'png')) )
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

				$name = ABSPATH . 'wp-content/authors/' . $author_id . '-' . $entropy . '.' . $ext;

				// Set a maximum height and width
				$width = 240;
				$height = 240;

				// Get new dimensions
				list($width_orig, $height_orig) = getimagesize($tmp_name);

				if ( $width_orig > $width || $height_orig > $height )
				{
					if ( $width_orig < $height_orig )
					{
						$width = intval(($height / $height_orig) * $width_orig);
					}
					else
					{
						$height = intval(($width / $width_orig) * $height_orig);
					}

					// Resample
					$image_p = imagecreatetruecolor($width, $height);

					if ( $ext == 'png' )
					{
						$image = imagecreatefrompng($tmp_name);
					}
					else
					{
						$image = imagecreatefromjpeg($tmp_name);
					}

					imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);

					@imagejpeg($image_p, $name, 75);
				}
				else
				{
					@move_uploaded_file($tmp_name, $name);
				}

				@chmod($name, 0666);
			}
		}
		elseif ( isset($_POST['delete_author_image']) )
		{
			$user_ID = $_POST['checkuser_id'];
			$user = get_userdata($user_ID);
			$author_id = $user->user_login;

			if ( defined('GLOB_BRACE') )
			{
				if ( $image = glob(ABSPATH . 'wp-content/authors/' . $author_id . '{,-*}.{jpg,jpeg,png}', GLOB_BRACE) )
				{
					$image = end($image);
				}
			}
			else
			{
				if ( $image = glob(ABSPATH . 'wp-content/authors/' . $author_id . '-*.jpg') )
				{
					$image = end($image);
				}
			}

			if ( $image )
			{
				@unlink($image);
			}
		}

		return $user_ID;
	} # save_image()
} # sem_author_image_admin

sem_author_image_admin::init();




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