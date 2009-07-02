<?php
class sem_pro_header_admin
{
	#
	# init()
	#

	function init()
	{
		add_action('save_post', array('sem_pro_header_admin', 'save_header'), 30);
	} # init()


	#
	# save_header()
	#

	function save_header($post_ID)
	{
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

			preg_match("/\.([^\.]+)$/", $_FILES['header_file']['name'], $ext);
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
	} # save_header()
} # sem_pro_header_admin

sem_pro_header_admin::init();
?>