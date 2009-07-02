<?php

class mediacaster_admin
{
	#
	# init()
	#

	function init()
	{
		add_action('init', array('mediacaster_admin', 'setup_admin_editor'));
		add_action('admin_head', array('mediacaster_admin', 'display_all_files'));

		add_action('admin_menu', array('mediacaster_admin', 'add_admin_page'));

		add_action('dbx_post_advanced', array('mediacaster_admin', 'display_media'));
		add_action('dbx_page_advanced', array('mediacaster_admin', 'display_media'));

		add_action('save_post', array('mediacaster_admin', 'update_path'), 20);
		add_action('save_post', array('mediacaster_admin', 'save_media'), 30);
	} # init()


	#
	# update_path()
	#

	function update_path($post_ID)
	{
		$old = get_post_meta($post_ID, '_mediacaster_path', true);
		$new = mediacaster::get_path($post_ID);

		if ( $old && $old != $new )
		{
			mediacaster_admin::create_path(dirname($new));

			@rename(ABSPATH . $old, ABSPATH . $new);
			#die();
		}

		delete_post_meta($post_ID, '_mediacaster_path');
		add_post_meta($post_ID, '_mediacaster_path', $new, true);

		return $post_ID;
	} # update_path()


	#
	# save_media()
	#

	function save_media($post_ID)
	{
		$path = mediacaster::get_path($post_ID);
		mediacaster_admin::create_path($path);
		#var_dump($path);
		#die;

		if ( current_user_can('upload_files') )
		{
			foreach ( array_keys((array) $_POST['delete_media']) as $key )
			{
				$key = str_replace("_", " ", $key);

				preg_match("/\.([^\.]+)$/", $key, $ext);
				$ext = end($ext);

				@unlink(ABSPATH . $path . $key);
				unset($_POST['update_media'][$key]);

				if ( in_array($ext, array('flv', 'swf')) )
				{
					$image = basename($key, '.' . $ext);

					if ( $image = glob(ABSPATH . $path . $image . '.{jpg,png}', GLOB_BRACE) )
					{
						$image = current($image);
						@unlink($image);
					}
				}
			}

			foreach ( (array) $_POST['update_media'] as $old => $new )
			{
				$old = str_replace("_", " ", $old);

				if ( $old != $new )
				{
					@rename(ABSPATH . $path . $old, ABSPATH . $path . $new);

					preg_match("/\.([^\.]+)$/", $old, $ext);
					$ext = end($ext);

					if ( in_array($ext, array('flv', 'swf')) )
					{
						$old_name = basename($old, '.' . $ext);
						$new_name = basename($new, '.' . $ext);

						if ( $image = glob(ABSPATH . $path . $old_name . '.{jpg,png}', GLOB_BRACE) )
						{
							$image = current($image);

							preg_match("/\.([^\.]+)$/", $image, $ext);
							$ext = end($ext);

							$old_name = basename($image, '.' . $ext);

							@rename(ABSPATH . $path . $old_name . '.' . $ext, ABSPATH . $path . $new_name . '.' . $ext);
						}
					}
				}
			}

			if ( $_FILES['new_media'] )
			{
				$tmp_name = $_FILES['new_media']['tmp_name'];
				$new_name = ABSPATH . $path . $_FILES['new_media']['name'];

				preg_match("/\.([^\.]+)$/", $new_name, $ext);
				$ext = end($ext);

				if ( in_array($ext, array('jpg', 'png', 'mp3', 'mp4', 'm4a', 'm4v', 'mov', 'flv', 'swf')) )
				{
					@move_uploaded_file($tmp_name, $new_name);
					@chmod($new_name, 0666);
				}
			}

			#echo '<pre>';
			#var_dump($_POST['update_media']);
			#var_dump($_FILES['new_media']);
			#echo '</pre>';
			#die;
		}

		return $post_ID;
	} # save_media()


	#
	# display_media()
	#

	function display_media()
	{
		$post_ID = isset($GLOBALS['post_ID']) ? $GLOBALS['post_ID'] : $GLOBALS['temp_ID'];

		#echo '<pre>';
		#var_dump($post_ID);
		#echo '</pre>';

		echo '<div class="dbx-b-ox-wrapper">';

		echo '<fieldset id="mediacaster" class="dbx-box">'
			. '<div class="dbx-h-andle-wrapper">'
			. '<h3 class="dbx-handle">' . __('Media') . '</h3>'
			. '</div>';

		echo '<div class="dbx-c-ontent-wrapper">'
			. '<div id="mediacasterstuff" class="dbx-content">';

		if ( $post_ID > 0 )
		{
			echo '<p>'
				. __('To attach media to this entry, either use the file uploader below or drop files into the following folder using ftp software:')
				. '</p>';

			$path = mediacaster::get_path($post_ID);

			echo '<p style="margin-left: 2em;">[WordPressFolder]<strong>/' . $path . '</strong></p>';

			$files = mediacaster::get_files($path);

			$cover = mediacaster::get_cover($path);

			if ( $files || strpos($cover, $path) !== false )
			{
				echo '<p>'
					. __('Media files currently include:')
					. '</p>';

				echo '<ul>';

				foreach ( (array) $files as $key => $file )
				{
					$name = $key;
					$key = str_replace(" ", "_", $key);

					echo '<li>'
						. '<input type="text" style="width: 320px;"'
							. ' id="update_media[' . $key . ']" name=update_media[' . $key . ']'
							. ' value="' . htmlspecialchars($name, ENT_QUOTES) . '"'
							. ' />'
						. '&nbsp;'
						. '<label for="delete_media[' . $key . ']">'
							. '<input type="checkbox"'
								. ' id=delete_media[' . $key . '] name=delete_media[' . $key . ']'
								. ' />'
							. '&nbsp;'
							. __('Delete')
							. '</label>'
						. '</li>';
				}

				if ( strpos($cover, $path) !== false )
				{
					$key = basename($cover);

					echo '<li>'
						. '<input type="text" style="width: 320px;"'
							. ' id="update_media[' . $key . ']" name=update_media[' . $key . ']'
							. ' value="' . $key . '"'
							. ' disabled="disabled"'
							. ' />'
						. '&nbsp;'
						. '<label for="delete_media[' . $key . ']">'
							. '<input type="checkbox"'
								. ' id=delete_media[' . $key . '] name=delete_media[' . $key . ']'
								. ' />'
							. '&nbsp;'
							. __('Delete')
							. '</label>'
						. '</li>';
				}

				echo '</ul>';
			}
			else
			{
				mediacaster_admin::create_path($path);
			}
		}

		if ( current_user_can('upload_files') )
		{
			echo '<p>'
				. __('Enter a file to add new media (this can take a while if the file is large)') . ':'
				. '</p>';

			echo '<ul>'
				. '<li>'
					. '<input type="file" style="width: 400px;"'
					. ' id="new_media" name="new_media"'
					. ' />'
				. '</li>'
				. '</ul>';

			echo '<p class="submit">'
				. '<input type="button"'
				. ' value="' . __('Save and Continue Editing') . '"'
				. ' onclick="return form.save.click();"'
				. ' />'
				. '</p>';

			echo '<p>'
				. __('Tips') . ':'
				. '</p>'
				. '<ul>'
				. '<li>'
				. __('Supported formats include .mp3, .flv, .swf, .m4a, .mp4, .m4v and .mov.')
				. '</li>'
				. '<li>'
				. __('Upload a .jpg or .png image named after your .flv or .swf video to use it as the cover for that video. <i>e.g.</i> myvideo.jpg for myvideo.swf.')
				. '</li>'
				. '<li>'
				. __('Upload a cover.jpg or cover.png image to override the default cover for your podcast playlist.')
				. '</li>'
				. '<li>'
				. __('Your media folder must be writable by the server for any of this to work at all.')
				. '</li>'
				. '<li>'
				. __('Maximum file size is 32M. If large files won\'t upload on your server, have your host increase its upload_max_filesize parameter.')
				. '</li>'
				. '<li>'
				. __('If you\'re uploading <a href="http://www.semiologic.com/go/camtasia">Camtasia</a> videos, upload <em>only</em> the video file (swf, flv, mov...). The other files created by Camtasia are for use in a standalone web page.')
				. '</li>'
				. '</ul>';
		}

		echo '</div>';
		echo '</div>';

		echo '</fieldset>';

		echo '</div>';
	} # display_media()


	#
	# create_path()
	#

	function create_path($path)
	{
		if ( $path )
		{
			if ( !file_exists(ABSPATH . $path) )
			{
				$parent = dirname($path);

				mediacaster_admin::create_path($parent);

				if ( is_writable(ABSPATH . $parent) )
				{
					@mkdir(ABSPATH . $path);
					@chmod(ABSPATH . $path, 0777);
				}
			}
		}
	} # create_path()


	#
	# add_admin_page()
	#

	function add_admin_page()
	{
		add_options_page(
			__('Mediacaster'),
			__('Mediacaster'),
			'manage_options',
			str_replace("\\", "/", __FILE__),
			array('mediacaster_admin', 'display_admin_page')
			);
	} # add_admin_page()


	#
	# strip_tags_rec()
	#

	function strip_tags_rec($input)
	{
		if ( is_array($input) )
		{
			foreach ( array_keys($input) as $key )
			{
				$input[$key] = mediacaster_admin::strip_tags_rec($input[$key]);
			}
		}
		else
		{
			$input = strip_tags($input);
		}

		return $input;
	} # strip_tags_rec()


	#
	# update_options()
	#

	function update_options()
	{
		check_admin_referer('mediacaster');

		if ( isset($_POST['delete_cover']) )
		{
			if ( $cover = glob(ABSPATH . 'media/cover{,-*}.{jpg,png}', GLOB_BRACE) )
			{
				$cover = current($cover);
				@unlink($cover);
			}
		}

		if ( isset($_POST['delete_itunes']) )
		{
			$options = get_option('mediacaster');

			$itunes_image = ABSPATH . 'wp-content/itunes/' . $options['itunes']['image']['name'];

			@unlink($itunes_image);
		}

		$options = $_POST['mediacaster'];

		$options = mediacaster_admin::strip_tags_rec($options);

		if ( @ $_FILES['mediacaster']['name']['itunes']['image']['new'] )
		{
			$name =& $_FILES['mediacaster']['name']['itunes']['image']['new'];
			$tmp_name =& $_FILES['mediacaster']['tmp_name']['itunes']['image']['new'];

			preg_match("/\.([^\.]+)$/", $name, $ext);
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
				$options['itunes']['image']['counter'] = $options['itunes']['image']['counter'] + 1;
				$options['itunes']['image']['name'] = $options['itunes']['image']['counter'] . '_' . $name;

				$new_name = ABSPATH . 'wp-content/itunes/' . $options['itunes']['image']['name'];

				@mkdir(ABSPATH . 'wp-content/itunes');
				@chmod(ABSPATH . 'wp-content/itunes', 0777);

				@move_uploaded_file($tmp_name, $new_name);
				@chmod($new_name, 0666);
			}
		}

		if ( @ $_FILES['new_cover']['name'] )
		{
			$name =& $_FILES['new_cover']['name'];
			$tmp_name =& $_FILES['new_cover']['tmp_name'];

			preg_match("/\.([^\.]+)$/", $name, $ext);
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
				if ( $cover = glob(ABSPATH . 'media/cover{,-*}.{jpg,png}', GLOB_BRACE) )
				{
					$cover = current($cover);
					@unlink($cover);
				}

				preg_match("/\.([^\.]+)$/", $name, $ext);
				$ext = end($ext);

				$entropy = get_option('sem_entropy');

				$entropy = intval($entropy) + 1;

				update_option('sem_entropy', $entropy);

				$new_name = ABSPATH . 'media/cover-' . $entropy . '.' . $ext;

				@move_uploaded_file($tmp_name, $new_name);
				@chmod($new_name, 0666);
			}
		}

		#echo '<pre>';
		#var_dump($options);
		#echo '</pre>';

		update_option('mediacaster', $options);
	} # update_options()


	#
	# display_admin_page()
	#

	function display_admin_page()
	{
		echo '<form enctype="multipart/form-data" method="post" action="">' . "\n";

		echo '<input type="hidden" name="MAX_FILE_SIZE" value="8000000">' . "\n";

		if ( $_POST['update_mediacaster_options'] )
		{
			echo '<div class="updated">' . "\n"
				. '<p>'
					. '<strong>'
					. __('Options saved.')
					. '</strong>'
				. '</p>' . "\n"
				. '</div>' . "\n";

			mediacaster_admin::update_options();
		}

		$options = get_settings('mediacaster');
		#$options = false;

		if ( $options == false )
		{
			$options = mediacaster::regen_options();
		}

		$site_url = trailingslashit(get_option('siteurl'));

		echo '<div class="wrap">' . "\n"
			. '<h2>'. __('Mediacaster options') . '</h2>' . "\n"
			. '<input type="hidden" name="update_mediacaster_options" value="1" />' . "\n";

		if ( function_exists('wp_nonce_field') ) wp_nonce_field('mediacaster');

		echo '<fieldset>' . "\n"
			. '<h3>'
				. __('Player')
				. '</h3>' . "\n";

			echo '<p>'
				. __('Player Position: ')
				. '<label for="mediacaster[player][position][top]">'
				. '<input type="radio"'
					. ' id="mediacaster[player][position][top]" name="mediacaster[player][position]"'
					. ' value="top"'
					. ( $options['player']['position'] != 'bottom'
						? ' checked="checked"'
						: ''
						)
					. ' />'
				. '&nbsp;'
				. __('Top')
				. '</label>'
				. ' '
				. '<label for="mediacaster[player][position][bottom]">'
				. '<input type="radio"'
					. ' id="mediacaster[player][position][bottom]" name="mediacaster[player][position]"'
					. ' value="bottom"'
					. ( $options['player']['position'] == 'bottom'
						? ' checked="checked"'
						: ''
						)
					. ' />'
				. '&nbsp;'
				. __('Bottom')
				. '</label>'
				. '</p>' . "\n";

		echo '<p>'
				. '<label for="mediacaster[player][width]">'
				. __('Player Width and Height') . ':'
				. '</label>'
				. '<br />'
			. '<input type="text"'
				. ' id="mediacaster[player][width]" name="mediacaster[player][width]"'
				. ' value="'
					. ( $options['player']['width']
						? $options['player']['width']
						: 320
						)
					 . '"'
				. ' />' . "\n"
			. '<input type="text"'
				. ' id="mediacaster[player][height]" name="mediacaster[player][height]"'
				. ' value="'
					. ( ( isset($options['player']['height']) && $options['player']['height'] )
						? $options['player']['height']
						: intval($options['player']['width'] * 240 / 320 )
						)
					 . '"'
				. ' />' . "\n"
			. '</p>' . "\n";


			if ( $cover = glob(ABSPATH . 'media/cover{,-*}.{jpg,png}', GLOB_BRACE) )
			{
				$cover = current($cover);
			}

			echo '<p>'
					. __('MP3 Playlist Cover') . ':'
				. '<br />' . "\n"
				. ( file_exists($cover)
					? ( '<img src="'
							. str_replace(ABSPATH, $site_url, $cover)
							. '"'
						. ' />' . "\n"
						. '<br />' . "\n"
						)
					: ''
					);

			if ( is_writable($cover) )
			{
				echo '<label for="delete_cover">'
					. '<input type="checkbox"'
						. ' id="delete_cover" name="delete_cover"'
						. ' style="text-align: left; width: auto;"'
						. ' />'
					. '&nbsp;'
					. __('Delete')
					. '</label>';
			}
			elseif ( file_exists($cover) )
			{
				echo __('This cover is not writable by the server.');
			}

			echo '</p>' . "\n";

			echo '<p>'
				. '<label for="new_cover">'
					. __('New Image (jpg or png)') . ':'
					. '</label>'
				. '<br />' . "\n"
				. '<input type="file" style="width: 480px;"'
					. ' id="new_cover" name="new_cover"'
					. ' />' . "\n"
				. '</p>' . "\n";

		echo '<p class="submit">'
			. '<input type="submit"'
				. ' value="' . __('Update Options') . '"'
				. ' />'
			. '</p>' . "\n";;

		echo '</fieldset>' . "\n";


		echo '<fieldset>' . "\n"
			. '<h3>'
				. __('Enclosures')
				. '</h3>' . "\n";

		echo '<p>'
			. '<input type="radio"'
				. ' id="mediacaster[enclosures][none]" name="mediacaster[enclosures]"'
				. ' value=""'
				. ( $options['enclosures'] == ''
					? ' checked="checked"'
					: ''
					)
				. ' />' . "\n"
				. '<label for="mediacaster[enclosures][none]">'
				. __('List enclosures in machine readable format for use in RSS readers and iPods.')
				. '</label>'
			. '</p>' . "\n";

		echo '<p>'
			. '<input type="radio"'
				. ' id="mediacaster[enclosures][all]" name="mediacaster[enclosures]"'
				. ' value="all"'
				. ( $options['enclosures'] == 'all'
					? ' checked="checked"'
					: ''
					)
				. ' />' . "\n"
				. '<label for="mediacaster[enclosures][all]">'
				. __('List enclosures in machine readable format and as download links in human readable format.')
				. '</label>'
			. '</p>' . "\n";

		echo '<p class="submit">'
			. '<input type="submit"'
				. ' value="' . __('Update Options') . '"'
				. ' />'
			. '</p>' . "\n";;

		echo '</fieldset>' . "\n";


		echo '<fieldset>' . "\n"
			. '<h3>'
				. __('iTunes')
				. '</h3>' . "\n";

		if ( class_exists('podPress_class') )
		{
			echo '<p>'
				. __('PodPress detected. Configure itunes-related fields in your PodPress options')
				. '</p>' . "\n";
		}
		else
		{
			echo '<p>'
				. '<label for="mediacaster[itunes][author]">'
					. __('Author') . ':'
					. '</label>'
				. '<br />' . "\n"
				. '<input type="text" style="width: 480px;"'
					. ' id="mediacaster[itunes][author]" name="mediacaster[itunes][author]"'
					. ' value="' . htmlspecialchars($options['itunes']['author'], ENT_QUOTES) . '"'
					. ' />' . "\n"
				. '</p>' . "\n";


			echo '<p>'
				. '<label for="mediacaster[itunes][summary]">'
					. __('Summary') . ':'
					. '</label>'
				. '<br />' . "\n"
				. '<textarea style="width: 480px; height: 40px;"'
					. ' id="mediacaster[itunes][summary]" name="mediacaster[itunes][summary]"'
					. ' >' . "\n"
					. $options['itunes']['summary']
					. '</textarea>' . "\n"
				. '</p>' . "\n";


			echo '<p>'
					. __('Categories') . ':';

			for ( $i = 1; $i <= 3; $i++ )
			{
				echo '<br />' . "\n"
					. '<select style="width: 480px;"'
						. ' id="mediacaster[itunes][category][' . $i . ']" name="mediacaster[itunes][category][' . $i . ']"'
						. ' >' . "\n"
					. '<option value="">' . __('Select...') . '</option>' . "\n";

				foreach ( mediacaster_admin::get_itunes_categories() as $category )
				{
					$category = $category;

					echo '<option'
						. ' value="' . htmlspecialchars($category, ENT_QUOTES) . '"'
						. ( ( $category == $options['itunes']['category'][$i] )
							? ' selected="selected"'
							: ''
							)
						. '>'
						. htmlspecialchars($category, ENT_QUOTES)
						. '</option>' . "\n";
				}
				echo '</select>' . "\n";
			}

			echo '</p>' . "\n";


			echo '<p>'
					. __('Itunes Cover') . ':'
				. '<br />' . "\n"
				. '<input type="hidden"'
					. ' id="mediacaster[itunes][image][counter]" name="mediacaster[itunes][image][counter]"'
					. ' value="' . intval($options['itunes']['image']['counter']) . '"'
					. ' />' . "\n"
				. '<input type="hidden"'
					. ' id="mediacaster[itunes][image][name]" name="mediacaster[itunes][image][name]"'
					. ' value="' . htmlspecialchars($options['itunes']['image']['name'], ENT_QUOTES) . '"'
					. ' />' . "\n"
				. ( file_exists(ABSPATH . 'wp-content/itunes/' . $options['itunes']['image']['name'])
					? ( '<img src="'
							. $site_url
							. 'wp-content/itunes/'
							. htmlspecialchars($options['itunes']['image']['name'], ENT_QUOTES)
							. '"'
						. ' />' . '<br />' . "\n"
						)
						: ''
					);

			if ( is_writable(ABSPATH . 'wp-content/itunes/' . $options['itunes']['image']['name']) )
			{
				echo '<label for="delete_itunes">'
					. '<input type="checkbox"'
						. ' id="delete_itunes" name="delete_itunes"'
						. ' style="text-align: left; width: auto;"'
						. ' />'
					. '&nbsp;'
					. __('Delete')
					. '</label>';
			}
			elseif ( file_exists(ABSPATH . 'wp-content/itunes/' . $options['itunes']['image']['name']) )
			{
				echo __('This cover is not writable by the server.');
			}

			echo '</p>' . "\n";

			echo '<p>'
				. '<label for="mediacaster[itunes][image][new]">'
					. __('New Image (jpg or png)') . ':'
					. '</label>'
				. '<br />' . "\n"
				. '<input type="file" style="width: 480px;"'
					. ' id="mediacaster[itunes][image][new]" name="mediacaster[itunes][image][new]"'
					. ' />' . "\n"
				. '</p>' . "\n";


			echo '<p>'
					. '<label for="mediacaster[itunes][explicit]">'
					. __('Explicit') . ':'
					. '</label>'
					. '<br />' . "\n"
				. '<select style="width: 480px;"'
					. ' id="mediacaster[itunes][explicit]" name="mediacaster[itunes][explicit]"'
					. ' >' . "\n";

			foreach ( array('Yes', 'No', 'Clean') as $answer )
			{
				$answer = htmlspecialchars($answer, ENT_QUOTES);

				echo '<option'
					. ' value="' . $answer . '"'
					. ( ( $answer == $options['itunes']['explicit'] )
						? ' selected="selected"'
						: ''
						)
					. '>'
					. $answer
					. '</option>' . "\n";
			}

			echo '</select>' . "\n"
				. '</p>' . "\n";


			echo '<p>'
					. '<label for="mediacaster[itunes][block]">'
					. __('Block iTunes') . ':'
					. '</label>'
					. '<br />' . "\n"
				. '<select style="width: 480px;"'
					. ' id="mediacaster[itunes][block]" name="mediacaster[itunes][block]"'
					. ' >' . "\n";

			foreach ( array('Yes', 'No') as $answer )
			{
				$answer = htmlspecialchars($answer, ENT_QUOTES);

				echo '<option'
					. ' value="' . $answer . '"'
					. ( ( $answer == $options['itunes']['block'] )
						? ' selected="selected"'
						: ''
						)
					. '>'
					. $answer
					. '</option>' . "\n";
			}

			echo '</select>' . "\n"
				. '</p>' . "\n";

			echo '<p>'
				. '<label for="mediacaster[itunes][copyright]">'
					. __('Copyright') . ':'
					. '</label>'
				. '<br />' . "\n"
				. '<textarea style="width: 480px; height: 40px;"'
					. ' id="mediacaster[itunes][copyright]" name="mediacaster[itunes][copyright]"'
					. ' >' . "\n"
					. $options['itunes']['copyright']
					. '</textarea>' . "\n"
				. '</p>' . "\n";
		}

		echo '<p class="submit">'
			. '<input type="submit"'
				. ' value="' . __('Update Options') . '"'
				. ' />'
			. '</p>' . "\n";;

		echo '</fieldset>' . "\n"
			. '</div>' . "\n";

		echo '</form>' . "\n";
	} # display_admin_page()


	#
	# get_itunes_categories()
	#

	function get_itunes_categories()
	{
		return array(
			'Arts',
			'Arts / Design',
			'Arts / Fashion & Beauty',
			'Arts / Food',
			'Arts / Literature',
			'Arts / Performing Arts',
			'Arts / Visual Arts',

			'Business',
			'Business / Business News',
			'Business / Careers',
			'Business / Investing',
			'Business / Management & Marketing',
			'Business / Shopping',

			'Comedy',

			'Education',
			'Education / Education Technology',
			'Education / Higher Education',
			'Education / K-12',
			'Education / Language Courses',
			'Education / Training',

			'Games & Hobbies',
			'Games & Hobbies / Automotive',
			'Games & Hobbies / Aviation',
			'Games & Hobbies / Hobbies',
			'Games & Hobbies / Other Games',
			'Games & Hobbies / Video Games',

			'Government & Organizations',
			'Government & Organizations / Local',
			'Government & Organizations / National',
			'Government & Organizations / Non-Profit',
			'Government & Organizations / Regional',

			'Health',
			'Health / Alternative Health',
			'Health / Fitness & Nutrition',
			'Health / Self-Help',
			'Health / Sexuality',

			'Kids & Family',

			'Music',

			'News & Politics',

			'Religion & Spirituality',
			'Religion & Spirituality / Buddhism',
			'Religion & Spirituality / Christianity',
			'Religion & Spirituality / Hinduism',
			'Religion & Spirituality / Islam',
			'Religion & Spirituality / Judaism',
			'Religion & Spirituality / Other',
			'Religion & Spirituality / Spirituality',

			'Science & Medicine',
			'Science & Medicine / Medicine',
			'Science & Medicine / Natural Sciences',
			'Science & Medicine / Social Sciences',

			'Society & Culture',
			'Society & Culture / History',
			'Society & Culture / Personal Journals',
			'Society & Culture / Philosophy',
			'Society & Culture / Places & Travel',

			'Sports & Recreation',
			'Sports & Recreation / Amateur',
			'Sports & Recreation / College & High School',
			'Sports & Recreation / Outdoor',
			'Sports & Recreation / Professional',

			'Technology',
			'Technology / Gadgets',
			'Technology / Tech News',
			'Technology / Podcasting',
			'Technology / Software How-To',

			'TV & Film',
		);
	} # get_itunes_categories()


	#
	# setup_admin_editor()
	#

	function setup_admin_editor()
	{
		if ( function_exists('get_user_option')
			&& ( get_user_option('rich_editing') == 'true' )
			&& file_exists(ABSPATH . 'wp-includes/js/tinymce/plugins/mediacaster')
			)
		{
			add_filter('mce_plugins', array('mediacaster_admin', 'add_mce_plugin'));
			add_filter('mce_buttons', array('mediacaster_admin', 'add_mce_button'));
		}
		else
		{
			add_filter('admin_footer', array('mediacaster_admin', 'display_quicktag'));
		}
	} # end setup_admin_editor()


	#
	# display_quicktag()
	#

	function display_quicktag()
	{
		global $post;

		$path = mediacaster::get_path($post);

		$files = mediacaster::get_files($path);

		$js_options = "";

		$js_options .= '<option value=\"-'
				. 'media#url'
			. '-\">'
			. __('Enter a url')
			. '</option>';

		foreach ( array_keys($files) as $file )
		{
			$js_options .= '<option value=\"-'
					. 'media#' . $file
				. '-\">'
				. $file
				. '</option>';
		}

?><script type="text/javascript">

if ( document.getElementById('quicktags') )
{

function add_media(elt)
{
	if ( elt && elt.value == '-media#url-' )
	{
		var url = prompt('Enter the url of a media file', 'http://');
		edInsertContent(edCanvas, '<!--media#' + url + '-->');
	}
	else if ( elt && elt.value != '' )
	{
		edInsertContent(edCanvas, '<!-'+ elt.value +'->');
	}

	elt.selectedIndex = 0;
} // add_media()

document.getElementById('ed_toolbar').innerHTML
	+= '<select class=\"ed_button\" style=\"width: 100px;\" onchange=\"return add_media(this);\">'
	+ '<option value=\"\" selected><?php echo __('Media'); ?></option>'
	+ '<?php echo $js_options; ?>'
	+ '</select>';
} // end if
</script>
<?php
	} # end display_quicktag()


	#
	# display_all_files()
	#

	function display_all_files()
	{
		global $post;

		$path = mediacaster::get_path($post);

		$files = mediacaster::get_files($path);

		$js_options = "";
		$i = 0;

		foreach ( array_keys($files) as $file )
		{
			$js_options .= ( $js_options ? "\n" : '' )
				. "all_media['"
				. $i++
				. "']"
				. "='"
				. $file
				. "';";
		}
?><script type="text/javascript">
var all_media = new Array();
<?php echo $js_options . "\n"; ?>
document.all_media = all_media;
//alert(document.all_media);
</script>
<?php
	} # display_all_files()


	#
	# add_mce_plugin()
	#

	function add_mce_plugin($plugins)
	{
		$plugins[] = 'mediacaster';

		return $plugins;
	} # end add_mce_plugin()


	#
	# add_mce_button()
	#

	function add_mce_button($buttons)
	{
		$path = mediacaster::get_path($post);

		$files = mediacaster::get_files($path);

		if ( $files )
		{
			if ( !empty($buttons) )
			{
				$buttons[] = 'separator';
			}

			$buttons[] = 'mediacaster';
		}

		return $buttons;
	} # end add_mce_button()
} # mediacaster_admin


mediacaster_admin::init();


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
	echo  "\n" . '<input type="hidden" name="MAX_FILE_SIZE" value="32000000" />' . "\n";
}

add_action('edit_form_advanced', 'add_file_max_size');
add_action('edit_page_form', 'add_file_max_size');
endif;
?>