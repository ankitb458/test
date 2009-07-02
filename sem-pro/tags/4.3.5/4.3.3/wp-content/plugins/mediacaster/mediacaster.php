<?php
/*
Plugin Name: Mediacaster
Plugin URI: http://www.semiologic.com/software/publishing/mediacaster/
Description: Podcasting and Videocasting plugin
Author: Denis de Bernardy
Version: 1.2
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/
*/


# fix php 5.2

if ( !function_exists('ob_end_flush_all') ) :
function ob_end_flush_all()
{
	while ( @ob_end_flush() );
}

register_shutdown_function('ob_end_flush_all');
endif;


class mediacaster
{
	#
	# init()
	#

	function init()
	{
		# audio:
		add_filter('the_content', array('mediacaster', 'display_players'), 20);
		remove_filter('the_content', 'ap_insert_player_widgets');

		# playlists:
		add_filter('the_content', array('mediacaster', 'display_playlist'), 10);
		add_action('template_redirect', array('mediacaster', 'catch_playlist'));

		add_action('rss2_ns', array('mediacaster', 'display_feed_ns'));
		add_action('atom_ns', array('mediacaster', 'display_feed_ns'));

		add_action('rss2_head', array('mediacaster', 'display_feed_header'));
		add_action('atom_head', array('mediacaster', 'display_feed_header'));

		add_action('rss2_item', array('mediacaster', 'display_feed_enclosures'));
		add_action('atom_entry', array('mediacaster', 'display_feed_enclosures'));

		add_action('wp_head', array('mediacaster', 'display_scripts'));
	} # init()


	#
	# display_scripts()
	#

	function display_scripts()
	{
		echo '<script type="text/javascript"'
			. ' src="'
				. trailingslashit(get_option('siteurl'))
				. 'wp-content/plugins/mediacaster/player/swfobject.js'
				. '"'
			. '></script>' . "\n";

		echo '<script type="text/javascript"'
			. ' src="'
				. trailingslashit(get_option('siteurl'))
				. 'wp-content/plugins/mediacaster/player/qtobject.js'
				. '"'
			. '></script>' . "\n";
	}


	#
	# display_players()
	#

	function display_players($buffer)
	{
		$buffer = mediacaster::compat($buffer);

		$buffer = preg_replace_callback("/
			(?:<p[^>]*>)?
			\[(?:audio|video|media)\s*:
			([^\]]*)
			\]
			(?:<\s*\/\s*p\s*>)?
			/ix",
			array('mediacaster', 'display_player_callback'),
			$buffer
			);

		return $buffer;
	} # display_players()


	#
	# compat()
	#

	function compat($buffer)
	{
		# transform <!--podcast#file--> or <!--media#file--> into [media:file]

		$buffer = preg_replace(
			"/
				(?:<br\s*\/>|\n|\r)*
				<!--\s*(?:podcast|media)\s*(\#[^>]*)-->
				(?:<br\s*\/>|\n|\r)*
			/ix",
			"<!--podcast$1-->",
			$buffer
			);

		$buffer = preg_replace(
			"/
				(?:<p>)?
				<!--\s*(?:podcast|media)\s*(\#[^>]*)-->
				(?:<\/p>)?
			/ix",
			"<!--podcast$1-->",
			$buffer
			);

		$buffer = preg_replace(
			"/
				<!--\s*(?:podcast|media)\s*\#([^>]*)-->
			/ix",
			"\n\n[media:$1]\n\n",
			$buffer
			);

		# transform <!--videocast#file--> into [media:file]

		$buffer = preg_replace(
			"/
				(?:<br\s*\/>|\n|\r)*
				<!--\s*videocast\s*(\#[^>]*)-->
				(?:<br\s*\/>|\n|\r)*
			/ix",
			"<!--videocast$1-->",
			$buffer
			);

		$buffer = preg_replace(
			"/
				(?:<p>)?
				<!--\s*videocast\s*(\#[^>]*)-->
				(?:<\/p>)?
			/ix",
			"<!--videocast$1-->",
			$buffer
			);

		$buffer = preg_replace(
			"/
				<!--\s*videocast\s*\#([^\#>]*)(?:\#([^\#>]*)\#([^\#>]*))?-->
			/ix",
			"[media:$1]",
			$buffer
			);

		# transform <flv href="file" /> into [media:file]

		$buffer = preg_replace(
			"/
				(?:<p>)?
				<flv\s+
				[^>]*
				href=\"([^\">]*)\"
				[^>]*
				>
				(?:<\/flv>)?
				(?:<\/p>)?
			/ix",
			"[media:$1]",
			$buffer
			);

		return $buffer;
	} # compat()


	#
	# display_player_callback()
	#

	function display_player_callback($input)
	{
		$file = $input[1];
		preg_match("/\.([^\.]+)$/", basename($file), $match);
		$ext = end($match);

		$options = get_option('mediacaster');
		$width = $options['player']['width'] ? $options['player']['width'] : 320;
		$height = $options['player']['height'] ? $options['player']['height'] : intval($width * 240 / 320 );

		switch ( $ext )
		{
		case 'mov':
		case 'm4v':
		case 'mp4':
		case 'm4a':
			return mediacaster::display_quicktime($file, $width, $height);
			break;

		case 'flv':
		case 'swf':
			return mediacaster::display_player($file, $width, $height);
			break;

		case 'mp3':
		default:
			$height = 20;
			return mediacaster::display_player($file, $width, $height, 0);
			break;
		}
	} # display_player_callback()


	#
	# display_playlist()
	#

	function display_playlist($content)
	{
		global $wpdb;
		$post_ID = get_the_ID();

		$out = '';
		$enc = '';

		if ( $post_ID > 0 )
		{
			$options = get_option('mediacaster');

			$path = mediacaster::get_path($post_ID);

			$files = mediacaster::get_files($path, $post_ID);

			foreach ( array('flash_audios', 'flash_videos', 'qt_audios', 'qt_videos') as $var )
			{
				$$var = mediacaster::extract_podcasts($files, $post_ID, $var);
			}

			$width = $options['player']['width'] ? $options['player']['width'] : 320;
			$height = $options['player']['height'] ? $options['player']['height'] : intval($width * 240 / 320 );

			if ( $flash_audios )
			{
				$site_url = trailingslashit(get_option('siteurl'));

				$height = 20;
				$display_height = 0;

				# playlist height

				$num = count($flash_audios);

				if ( $num > 1 )
				{
					$height = $height + $num * 23;
				}

				# cover

				$cover = mediacaster::get_cover($path);

				if ( $cover )
				{
					$cover_size = getimagesize(ABSPATH . $cover);

					$cover_width = $cover_size[0];
					$cover_height = $cover_size[1];

					$mp3_width = $cover_width;

					$height = $height + $cover_height;
					$display_height = $display_height + $cover_height;
				}
				else
				{
					$mp3_width = $width;
				}

				$file = $site_url . '?podcasts=' . $post_ID;

				# insert player

				$out .= mediacaster::display_player($file, $mp3_width, $height, $display_height) . "\n";
			}

			if ( $flash_videos )
			{
				$display_height = $options['player']['height'] ? $options['player']['height'] : intval($width * 240 / 320 );;
				$height = $display_height + 20;

				# playlist height

				$num = count($flash_videos);

				if ( $num > 1 )
				{
					$height = $height + $num * 23;
				}

				$file = trailingslashit(get_option('siteurl')) . '?videos=' . $post_ID;

				# insert player

				$out .= mediacaster::display_player($file, $width, $height, $display_height) . "\n";
			}

			if ( $qt_audios )
			{
				$height = $options['player']['height'] ? $options['player']['height'] : intval($width * 240 / 320 );

				foreach ( $qt_audios as $file )
				{
					$out .= mediacaster::display_quicktime($file, $width, $height);
				}
			}

			if ( $qt_videos )
			{
				$height = $options['player']['height'] ? $options['player']['height'] : intval($width * 240 / 320 );

				foreach ( $qt_videos as $file )
				{
					$out .= mediacaster::display_quicktime($file, $width, $height);
				}
			}


			if ( $files && $options['enclosures'])
			{
				$enc .= '<div class="enclosures">'
					. '<h2>'
						. ( function_exists('get_caption')
							? get_caption('enclosures')
							: __('Enclosures')
							)
						. '</h2>'
					. '<ul>' . "\n";

				foreach ( $files as $key => $file )
				{
					preg_match("/\.([^\.]+)$/", $key, $details);

					$ext = $details[1];

					switch ( $ext )
					{
						case 'swf':
						case 'flv':
						case 'mov':
						case 'mp4':
						case 'm4v':
							$enc .= '<li class="video">'
								. '<a href="' . $file . '" target="_blank">'
								. $key
								. '</a>'
								. ' (video)'
								. '</li>' . "\n";

						case 'mp3':
						case 'm4a':
							$enc .= '<li class="audio">'
								. '<a href="' . $file . '" target="_blank">'
								. $key
								. '</a>'
								. ' (audio)'
								. '</li>' . "\n";
							break;
					}
				}

				$enc .= '</ul>' . "\n"
					. '</div>' . "\n";
			}
		}

		if ( $options['player']['position'] != 'bottom' )
		{
			$content = $out . $content . $enc;
		}
		else
		{
			$content = $content . $out . $enc;
		}

		return $content;
	} # display_playlist()


	#
	# display_player()
	#

	function display_player($file, $width, $height, $display_height = null)
	{
		if ( strpos($file, '://') === false )
		{
			$site_url = trailingslashit(get_option('siteurl'));

			$path = mediacaster::get_path(get_the_ID());

			$file = $site_url . $path . $file;
		}

		if ( !isset($display_height) )
		{
			$display_height = $height;
			$height = $height + 20;
		}

		$image = false;

		preg_match( "/\.([^\.]+)$/", basename($file), $ext );
		$ext = end($ext);

		switch ( $ext )
		{
		case 'flv':
		case 'swf':
			$site_url = trailingslashit(get_option('siteurl'));

			$image = $file;
			$image = str_replace($site_url, '', $image);
			$image = str_replace('.' . $ext, '', $image);
			$image = glob(ABSPATH . $image . '.{jpg,png}', GLOB_BRACE);

			if ( $image )
			{
				$image = current($image);
				$image = str_replace(ABSPATH, $site_url, $image);
			}

			break;
		}

		$id = 'm' . md5($file . '_' . $GLOBALS['player_count']++);

		$player = trailingslashit(get_option('siteurl'))
			. 'wp-content/plugins/mediacaster/player/player.swf';

		return '<div class="media">' . "\n"
			. '<p id="' . $id . '">'
			. __('<a href="http://www.macromedia.com/go/getflashplayer">Get Flash</a> to see this player.')
			. '</p>' . "\n"
			. '<script type="text/javascript">' . "\n"
			. 'var so = new SWFObject("'. $player . '","' . $id . '","' . $width . '","' . $height . '","7");' . "\n"
			. 'so.addParam("largecontrols","false");' . "\n"
			. 'so.addParam("autostart","false");' . "\n"
			. 'so.addParam("showdigits","true");' . "\n"
			. 'so.addParam("autoscroll","false");' . "\n"
			. 'so.addParam("overstretch","false");' . "\n"
			. 'so.addParam("thumbsinplaylist","false");' . "\n"
			. ( $image
				? 'so.addParam("image","' . $image . '");' . "\n"
				: ''
				)
			. 'so.addVariable("file","'. $file . '");' . "\n"
			. 'so.addVariable("displayheight","' . $display_height . '");' . "\n"
			. 'so.write("' . $id . '");' . "\n"
			. '</script>' . "\n"
			. '</div>' . "\n";
	} # display_player()


	#
	# display_quicktime()
	#

	function display_quicktime($file, $width, $height)
	{
		if ( strpos($file, '://') === false )
		{
			$site_url = trailingslashit(get_option('siteurl'));

			$path = mediacaster::get_path(get_the_ID());

			$file = $site_url . $path . $file;
		}

		$id = md5($file . '_' . $GLOBALS['player_count']++);

		return '<div class="media">' . "\n"
			. '<script type="text/javascript">' . "\n"
			. 'var so = new QTObject("'. $file . '","' . $id . '","' . $width . '","' . $height . '");' . "\n"
			. 'so.addParam("autoplay","false");' . "\n"
			. 'so.addParam("loop","false");' . "\n"
			. 'so.addParam("controller","true");' . "\n"
			. 'so.addParam("enablejavascript","false");' . "\n"
			. 'so.addParam("scale","tofit");' . "\n"
			. 'so.write();' . "\n"
			. '</script>' . "\n"
			. '</div>' . "\n";
	} # display_quicktime()


	#
	# catch_playlist()
	#

	function catch_playlist()
	{
		if ( isset($_GET['podcasts']) )
		{
			mediacaster::display_playlist_xml($_GET['podcasts'], 'audio');
			die;
		}
		elseif ( isset($_GET['videos']) )
		{
			mediacaster::display_playlist_xml($_GET['videos'], 'video');
			die;
		}
	} # catch_playlist()


	#
	# display_playlist_xml()
	#

	function display_playlist_xml($post_ID, $type = 'audio')
	{
		global $wpdb;

		$path = mediacaster::get_path($post_ID);

		$files = mediacaster::get_files($path, $post_ID);

		switch ( $type )
		{
		case 'audio':
			$files = mediacaster::extract_podcasts($files, $post_ID, 'flash_audios');
			break;

		case 'video':
			$files = mediacaster::extract_podcasts($files, $post_ID, 'flash_videos');
			break;

		default:
			die;
		}

		echo '<?xml version="1.0" encoding="' . get_option('blog_charset') . '"?>' . "\n"
			. '<playlist version="1" xmlns="http://xspf.org/ns/0/">' . "\n";

		if ( $files )
		{
			$site_url = trailingslashit(get_option('siteurl'));

			if ( $type == 'audio' )
			{
				$cover = $site_url . mediacaster::get_cover($path);
			}

			echo '<trackList>' . "\n";

			foreach ( $files as $key => $file )
			{
				switch ( $type )
				{
				case 'audio':
					$title = preg_replace("/\.mp3$/i", "", $key);
					break;

				case 'video':
					$title = preg_replace("/\.(flv|swf)$/i", "", $key);
					$cover = file_exists(ABSPATH . $path . $title . '.jpg')
						? ( $site_url . $path . $title . '.jpg' )
						: false;
					break;
				}

				echo '<track>' . "\n";

				echo '<title>'
					. $title
					. '</title>' . "\n";

				if ( $cover )
				{
					echo '<image>'
						. $cover
						. '</image>' . "\n";
				}

				echo '<location>'
					. $file
					. '</location>' . "\n";

				echo '</track>' . "\n";
			}

			echo '</trackList>' . "\n";
		}

		echo '</playlist>';
	} # display_playlist_xml()


	#
	# get_path()
	#

	function get_path($post)
	{
		if ( is_numeric($post) )
		{
			$post = get_post(intval($post));
		}

		if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin' === false )
			&& $path = get_post_meta($post->ID, '_mediacaster_path', true)
			)
		{
			return $path;
		}

		if ( $post->post_name == '' )
		{
			$post->post_name = sanitize_title($post->post_title);
		}
		if ( $post->post_name == '' )
		{
			$post->post_name = $post->ID;
		}


		$head = 'media/';

		if ( @ $post->post_status == 'static' || $post->post_type == 'page' )
		{
			$tail = $post->post_name . '/';

			if ( $post->post_parent != 0 )
			{
				while ( $post->post_parent != 0 )
				{
					$post = get_post($post->post_parent);

					$tail = $post->post_name . '/' . $tail;
				}
			}

			$path = $head . $tail;
		}
		else
		{
			if ( !$post->post_date || $post->post_date == '0000-00-00 00:00:00')
			{
				$path = $head . date('Y/m/d', time()) . '/' . $post->post_name . '/';
			}
			else
			{
				$path = $head . date('Y/m/d', strtotime($post->post_date)) . '/' . $post->post_name . '/';
			}
		}

		if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin' === false ) )
		{
			delete_post_meta($post_ID, '_mediacaster_path');
			add_post_meta($post_ID, '_mediacaster_path', $path, true);
		}

		return $path;
	} # get_path()


	#
	# get_files()
	#

	function get_files($path, $post_ID = null)
	{
		$tag = $path . '_' . intval($post_ID);

		if ( isset($GLOBALS['mediacaster_file_cache'][$tag]) )
		{
			return $GLOBALS['mediacaster_file_cache'][$tag];
		}

		$site_url = trailingslashit(get_option('siteurl'));

		$files = glob(ABSPATH . $path . '*.{mp3,flv,swf,m4a,mp4,m4v,mov}', GLOB_BRACE);

		foreach ( (array) $files as $key => $file )
		{
			unset($files[$key]);

			$file = basename($file);

			$files[$file] = $site_url . $path . $file;
		}

		if ( $post_ID )
		{
			$enclosures = get_post_meta($post_ID, 'enclosure');

			if ( $enclosures )
			{
				foreach ( (array) $enclosures as $enclosure )
				{
					$file = basename($enclosure);

					$files[$file] = $enclosure;
				}
			}
		}

		ksort($files);

		$GLOBALS['mediacaster_file_cache'][$tag] = $files;

		return $files;
	} # get_files()


	#
	# extract_podcasts()
	#

	function extract_podcasts($files, $post_ID, $type = 'flash_audios')
	{
		$podcasts = array();

		foreach ( $files as $key => $file )
		{
			switch ( $type )
			{
			case 'flash_audios':
				if ( strpos($key, '.mp3') !== false )
				{
					$podcasts[$key] = $file;
				}
				break;

			case 'flash_videos':
				if ( strpos($key, '.flv') !== false
					|| strpos($key, '.swf') !== false
					)
				{
					$podcasts[$key] = $file;
				}
				break;

			case 'qt_audios':
				if ( strpos($key, '.m4a') !== false )
				{
					$podcasts[$key] = $file;
				}
				break;

			case 'qt_videos':
				if ( strpos($key, '.m4v') !== false
					|| strpos($key, '.mp4') !== false
					|| strpos($key, '.mov') !== false
					)
				{
					$podcasts[$key] = $file;
				}
				break;
			}
		}

		if ( $podcasts )
		{
			$post = get_post(intval($post_ID));

			switch ( $type )
			{
			case 'flash_audios':
				$ext = 'mp3';
				break;

			case 'flash_videos':
				$ext = '(?:flv|swf)';
				break;

			case 'qt_audios':
				$ext = 'm4a';
				break;

			case 'qt_videos':
				$ext = '(?:mov|m4v|mp4)';
				break;
			}

			preg_match_all("/
				(?:<!--|\[)
				(?:media)
				(?:\#|:)
				(
					[^>\]]+
					\.$ext
				)
				(?:-->|\])
				/ix",
				$post->post_content,
				$embeded
				);

			if ( $embeded )
			{
				$embeded = end($embeded);
			}
			else
			{
				$embeded = array();
			}

			foreach ( $embeded as $key )
			{
				unset($podcasts[$key]);
			}
		}

		return $podcasts;
	} # extract_podcasts()


	#
	# get_cover()
	#

	function get_cover($path)
	{
		$cover = false;

		if ( !is_admin() )
		{
			$tag = get_the_ID();

			if ( $GLOBALS['mediacaster_cover_cache'][$tag] )
			{
				return $GLOBALS['mediacaster_cover_cache'][$tag];
			}
		}

		if ( $file = glob(ABSPATH . $path . 'cover.{jpg,png}', GLOB_BRACE) )
		{
			$cover = $path . basename(current($file));
		}
		elseif ( $file = glob(ABSPATH . 'media/cover{,-*}.{jpg,png}', GLOB_BRACE) )
		{
			$cover = 'media/' . basename(current($file));
		}

		if ( !is_admin() )
		{
			$GLOBALS['mediacaster_cover_cache'][$tag] = $cover;
		}

		return $cover;
	} # get_cover()


	#
	# display_feed_ns()
	#

	function display_feed_ns()
	{
		if ( !class_exists('podPress_class') && is_feed() )
		{
			echo 'xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"' . "\n\t";
		}
	} # display_feed_ns()


	#
	# display_feed_header()
	#

	function display_feed_header()
	{
		if ( !class_exists('podPress_class') && is_feed() )
		{
			$options = get_option('mediacaster');

			if ( $options == false )
			{
				$options = mediacaster::regen_options();
			}

			$site_url = trailingslashit(get_option('siteurl'));

			echo "\n\t\t"
				. '<copyright>&#xA9; ' . htmlspecialchars($options['itunes']['copyright'], ENT_QUOTES) . '</copyright>' . "\n\t\t"
				. '<itunes:author>' . htmlspecialchars($options['itunes']['author'], ENT_QUOTES) . '</itunes:author>' . "\n\t\t"
				. '<itunes:summary>' . htmlspecialchars($options['itunes']['summary'], ENT_QUOTES) . '</itunes:summary>' . "\n\t\t"
				#. '<itunes:owner>' . "\n\t\t"
				#	. "\t" . '<itunes:name>' . $owner_name . '</itunes:name>' . "\n\t\t"
				#	. "\t". '<itunes:email>' . $owner_email . '</itunes:email>' . "\n\t\t"
				#. '</itunes:owner>' . "\n\t\t"
				#. '<itunes:image href="' . $image . '" />' . "\n\t\t"
				. '<itunes:explicit>' . htmlspecialchars($options['itunes']['explicit'], ENT_QUOTES) . '</itunes:explicit>' . "\n\t\t"
				. '<itunes:block>' . htmlspecialchars($options['itunes']['block'], ENT_QUOTES) . '</itunes:block>' . "\n\t\t"
				;

			$image = 'wp-content/itunes/' . $options['itunes']['image']['name'];

			if ( file_exists(ABSPATH . $image) )
			{
				echo '<itunes:image href="' . $site_url . 'wp-content/itunes/' . $options['itunes']['image']['name'] . '" />' . "\n\t\t"
					. '<image>' . "\n\t\t"
						. "\t" . '<url>' . $site_url . 'wp-content/itunes/' . $options['itunes']['image']['name'] . '</url>' . "\n\t\t"
						. "\t" . '<name>' . htmlspecialchars(get_option('blogname'), ENT_QUOTES) . '</name>' . "\n\t\t"
					. '</image>' . "\n\t\t"
					;
			}

			if ( $options['itunes']['category'] )
			{
				foreach ( (array) $options['itunes']['category'] as $category )
				{
					$cats = split('/', $category);

					$cat = array_pop($cats);

					$cat = trim($cat);
					$cat = htmlspecialchars($cat, ENT_QUOTES);

					if ( $cat )
					{
						$category = '<itunes:category text="' . $cat . '" />' . "\n\t\t";

						if ( $cat = array_pop($cats) )
						{
							$cat = trim($cat);
							$cat = htmlspecialchars($cat, ENT_QUOTES);

							$category = '<itunes:category text="' . $cat . '">' . "\n\t\t\t"
								. $category
								. '</itunes:category>' . "\n\t\t";
						}

						echo $category;
					}
				}
			}

			echo "\n";
		}
	} # display_feed_header()


	#
	# display_feed_enclosures()
	#

	function display_feed_enclosures()
	{
		$site_url = trailingslashit(get_option('siteurl'));

		global $post;

		$path = mediacaster::get_path($post);

		$files = mediacaster::get_files($path);

		$add_itune_tags = false;

		foreach ( $files as $key => $file )
		{
			preg_match( "/\.([^\.]+)$/", $key, $ext );
			$ext = end($ext);

			switch ( strtolower($ext) )
			{
				case 'mp3':
					$mime = 'audio/mpeg';
					break;
				case 'm4a':
					$mime = 'audio/x-m4a';
					break;
				case 'mp4':
					$mime = 'video/mp4';
					break;
				case 'm4v':
					$mime = 'video/x-m4v';
					break;
				case 'mov':
					$mime = 'video/quicktime';
					break;
				default:
					continue;
			}

			$size = @filesize(ABSPATH . $path . $key);

			echo "\n\t\t"
				. '<enclosure'
				. ' url="'
					.  $file
					. '"'
				. ' length="' . $size . '"'
				. ' type="' . $mime . '"'
				. ' />';

			$add_itunes_tags = true;
		}

		if ( $add_itunes_tags && !class_exists('podPress_class') && is_feed() )
		{
			$author = get_the_author();

			$summary = get_post_meta(get_the_ID(), '_description', true);
			if ( !$summary )
			{
				$summary = get_the_excerpt();
			}

			$keywords = get_post_meta(get_the_ID(), '_keywords', true);
			if ( !$keywords )
			{
				$keywords = get_the_category_list(', ');
			}

			foreach ( array(
					'author',
					'summary',
					'keywords'
					) as $field )
			{
				$$field = strip_tags($$field);
				$$field = preg_replace("/\s+/", " ", $$field);
				$$field = trim($$field);
				$$field = htmlspecialchars($$field, ENT_QUOTES);
			}

			echo "\n\t\t"
				. '<itunes:author>' . htmlspecialchars($author, ENT_QUOTES) . '</itunes:author>' . "\n\t\t"
				. '<itunes:summary>' . htmlspecialchars($summary, ENT_QUOTES) . '</itunes:summary>' . "\n\t\t"
				. '<itunes:keywords>' . htmlspecialchars($keywords, ENT_QUOTES) . '</itunes:keywords>' . "\n\t\t"
				;
		}

		echo "\n";
	} # display_feed_enclosures()


	#
	# regen_options()
	#

	function regen_options()
	{
		global $wpdb;
		$options = array();

		$admin_user = $wpdb->get_row("
			SELECT	$wpdb->users.*
			FROM	$wpdb->users
			INNER JOIN $wpdb->usermeta
				ON $wpdb->usermeta.user_id = $wpdb->users.ID
			WHERE	$wpdb->usermeta.meta_key = 'wp_capabilities'
			AND		$wpdb->usermeta.meta_value LIKE '%administrator%'
			ORDER BY $wpdb->users.ID ASC
			LIMIT 1;
			");

		$options['itunes']['author'] = $admin_user->user_nicename;

		$options['itunes']['summary'] = get_option('blogdescription');

		$options['itunes']['explicit'] = 'No';

		$options['itunes']['block'] = 'No';

		$options['itunes']['copyright'] = $options['itunes']['author'];

		$options['player']['width'] = 320;
		$options['player']['height'] = 240;
		$options['player']['position'] = 'top';

		$options['enclosures'] = '';

		update_option('mediacaster', $options);

		return $options;
	} # regen_options()
} # mediacaster

mediacaster::init();


if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false
	|| strpos($_SERVER['REQUEST_URI'], 'wp-includes') !== false
	)
{
	include_once dirname(__FILE__) . '/mediacaster-admin.php';
}
?>