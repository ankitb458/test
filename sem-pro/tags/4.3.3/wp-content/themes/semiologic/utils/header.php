<?php

class header
{
	#
	# init()
	#

	function init()
	{
		add_action('wp_head', array('header', 'wire_header'));
	} # init


	#
	# get_header()
	#

	function get_header()
	{
		if ( !is_admin() && defined('sem_theme_header') )
		{
			return sem_theme_header;
		}

		global $semiologic;

		if ( !isset($semiologic['header']['mode']) )
		{
			header::upgrade();
		}

		if ( is_single() || is_page()
		|| class_exists('sem_static_front') && sem_static_front::is_home() )
		{
			$post_ID = $GLOBALS['posts'][0]->ID;
		}

		if ( $post_ID
			&& ( $header = glob(ABSPATH . 'wp-content/header/' . $post_ID . '/header{,-*}.{jpg,png,gif,swf}', GLOB_BRACE) ) )
		{
			$header = current($header);
		}
		elseif ( $header = glob(TEMPLATEPATH . '/skins/' . get_active_skin() . '/{header,header-background,header-bg,logo}.{jpg,png,gif,swf}', GLOB_BRACE) )
		{
			$header = current($header);

			$header_name = basename($header);

			preg_match("/(.+)\.[^\.]+/", $header_name, $header_type);
			$header_type = end($header_type);

			switch ( $header_type )
			{
			case 'header':
			case 'header-background':
				if ( $GLOBALS['semiologic']['header']['mode'] != 'header' )
				{
					$GLOBALS['semiologic']['header']['mode'] = 'header';
					update_option('semiologic', $GLOBALS['semiologic']);
				}
				break;

			case 'header-bg':
				if ( $GLOBALS['semiologic']['header']['mode'] != 'background' )
				{
					$GLOBALS['semiologic']['header']['mode'] = 'background';
					update_option('semiologic', $GLOBALS['semiologic']);
				}
				break;

			case 'logo':
				if ( $GLOBALS['semiologic']['header']['mode'] != 'logo' )
				{
					$GLOBALS['semiologic']['header']['mode'] = 'logo';
					update_option('semiologic', $GLOBALS['semiologic']);
				}
				break;
			}
		}
		elseif ( $header = glob(ABSPATH . 'wp-content/header/header{,-*}.{jpg,png,gif,swf}', GLOB_BRACE) )
		{
			$header = current($header);
		}
		else
		{
			$header = false;
		}

		define('sem_theme_header', $header);

		return $header;
	} # get_header()


	#
	# get_class()
	#

	function get_class()
	{
		$class = '';

		if ( header::get_header() )
		{
			switch ( $GLOBALS['semiologic']['header']['mode'] )
			{
			case 'header':
			case 'background':
				$class = 'header_bg';
				break;

			case 'logo':
				$class = 'header_img';
				break;
			}
		}

		return $class;
	} # get_class()


	#
	# upgrade()
	#

	function upgrade()
	{
		global $semiologic;

		$skin = get_active_skin();

		if ( $header = glob(TEMPLATEPATH . '/skins/' . $skin . '/header{,-background,-bg}.{jpg,png,gif,swf}', GLOB_BRACE) )
		{
			$header = end($header);
		}
		elseif ( $header = glob(TEMPLATEPATH . '/header{,-background,-bg}.{jpg,png,gif,swf}', GLOB_BRACE) )
		{
			$header = end($header);
		}
		elseif ( $header = glob(TEMPLATEPATH . '/headers/header{,-background,-bg}.{jpg,png,gif,swf}', GLOB_BRACE) )
		{
			$header = end($header);
		}
		elseif ( $header = $semiologic['active_header'] )
		{
			$header = TEMPLATEPATH . '/headers/' . $semiologic['active_header'];
		}

		if ( $header )
		{
			$name = basename($header);

			preg_match("/\.([^\.]+)$/", $name, $ext);
			$ext = end($ext);

			$name = str_replace('.' . $ext, '', $name);

			@mkdir(ABSPATH . 'wp-content/header');
			@chmod(ABSPATH . 'wp-content/header', 0777);

			@rename($header, ABSPATH . 'wp-content/headers/header.' . $ext);

			switch ( $name )
			{
			case 'header-background':
				$semiologic['header']['mode'] = 'header';
				break;

			case 'header-bg':
				$semiologic['header']['mode'] = 'background';
				break;

			case 'header':
				switch ( $ext )
				{
				case 'swf':
					$semiologic['header']['mode'] = 'background';
					break;

				default:
					$semiologic['header']['mode'] = 'logo';
					break;
				}
				break;

			default:
				$semiologic['header']['mode'] = 'background';
				break;
			}
		}
		else
		{
			$semiologic['header']['mode'] = 'header';
		}

		update_option('semiologic', $semiologic);
	} # upgrade()


	#
	# display_script()
	#

	function display_script()
	{
		if ( !class_exists('mediacaster')
			|| is_admin()
			)
		{
			echo '<script type="text/javascript"'
				. ' src="' . get_template_directory_uri() . '/swfobject.js"'
				. '></script>' . "\n";
		}
	} # display_script()


	#
	# display_logo()
	#

	function display_logo($header = null)
	{
		if ( !$header )
		{
			$header = header::get_header();
		}

		if ( $header )
		{
			$site_url = trailingslashit(get_option('siteurl'));

			list($width, $height) = getimagesize($header);

			echo '<img src="'
						. str_replace(ABSPATH, $site_url, $header)
						. '"'
					. ' alt="' . get_bloginfo('name') . '"'
					. ' height="' . $height . '" width="' . $width . '"'
					. ' />';
		}
	} # display_logo()


	#
	# display_flash()
	#

	function display_flash($header = null)
	{
		if ( !$header )
		{
			$header = header::get_header();
		}

		if ( $header )
		{
			$id = 'h' . md5($header);
			$site_url = trailingslashit(get_option('siteurl'));

			list($width, $height) = getimagesize($header);

			echo '<div id="' . $id . '">' . "\n"
				. __('<a href="http://www.macromedia.com/go/getflashplayer">Get Flash</a> to see this player.')
				. '</div>'
				. '<script type="text/javascript">' . "\n"
				. 'var so = new SWFObject("'. str_replace(ABSPATH, $site_url, $header) . '","' . $id . '","' . $width . '","' . $height . '","7");' . "\n"
				. 'so.write("' . $id . '");' . "\n"
				. '</script>' . "\n";
		}
	} # display_flash()


	#
	# wire_header()
	#

	function wire_header()
	{
		$header = header::get_header();

		if ( $header )
		{
			preg_match("/\.([^\.]+)$/", $header, $ext);
			$ext = end($ext);

			switch ( $GLOBALS['semiologic']['header']['mode'] )
			{
			case 'logo':
				remove_action('display_sitename', 'display_sitename');

				if ( $ext == 'swf' )
				{
					add_action('display_sitename', array('header', 'display_flash'));
					remove_action('enhance_header_div', 'enhance_header_div');
				}
				else
				{
					add_action('display_sitename', array('header', 'display_logo'));
				}
				break;

			case 'header':
				remove_action('display_sitename', 'display_sitename');
				remove_action('display_tagline', 'display_tagline');
				reset_plugin_hook('display_header_spacer');

				if ( $ext == 'swf' )
				{
					remove_action('enhance_header_div', 'enhance_header_div');
					add_action('display_sitename', array('header', 'display_flash'));
				}
				else
				{
					display_header_css($header);
				}
				break;

			case 'background':
					display_header_background_css($header);
				break;
			}
		}
	} # wire_header()
} # header

header::init();


#
# display_header()
#

function display_header()
{
?><div id="header" class="header"
	<?php do_action('enhance_header_div'); ?>	>
<div class="pad">
<?php
do_action('display_tagline');
do_action('display_sitename');
do_action('display_header_spacer');
?></div>
</div><!-- #header -->
<?php
} # end display_header()

add_action('display_header', 'display_header');


#
# display_sitename()
#

function display_sitename()
{
?><div id="sitename" class="sitename">
<h1><a href="<?php bloginfo('url'); ?>"><?php bloginfo('sitename'); ?></a></h1>
</div><!-- #sitename -->
<?php
} # end display_sitename()

add_action('display_sitename', 'display_sitename');


#
# display_tagline()
#

function display_tagline()
{
?><div id="tagline" class="tagline">
<h2><?php bloginfo('description'); ?></h2>
</div><!-- #tagline -->
<?php
} # end display_tagline()

add_action('display_tagline', 'display_tagline');


#
# enhance_header_div()
#

function enhance_header_div()
{
?>	style="cursor: pointer;"
	onclick="top.location.href = '<?php echo trailingslashit(get_settings('home')); ?>'"
<?php
} # end enhance_header_div()

add_action('enhance_header_div', 'enhance_header_div');


#
# function header_title_tag()
#

function header_title_tag()
{
	echo ' title="'
		. htmlspecialchars(get_bloginfo('sitename'), ENT_QUOTES)
		. ' &bull; '
		. htmlspecialchars(get_bloginfo('description'), ENT_QUOTES)
		. '"';
} # header_title_tag()

add_action('enhance_header_div', 'header_title_tag');



#
# display_navbar()
#

function display_navbar()
{
?><div id="navbar" class="navbar">
<div class="pad">
<?php
do_action('display_header_nav');
do_action('display_search_form');
do_action('display_navbar_spacer');
?></div>
</div><!-- #navbar -->
<?php
} # end display_navbar()

add_action('display_navbar', 'display_navbar');


#
# display_header_nav()
#

function display_header_nav()
{
?><div id="header_nav" class="header_nav inline_menu">
<?php display_nav_menu('header_nav', '|'); ?></div><!-- #header_nav -->
<?php
} # end display_header_nav()

add_action('display_header_nav', 'display_header_nav');


#
# display_search_form()
#

function display_search_form()
{
?><div id="search_form" class="search_form">
<form method="get" action="<?php bloginfo('url'); ?>"
	id="searchform" name="searchform">
<input type="text"
	id="s" class="s" name="s"
	value="<?php echo htmlspecialchars(get_caption('search'), ENT_QUOTES); ?>"
	onfocus="if ( this.value == '<?php echo addslashes(htmlspecialchars(get_caption('search'), ENT_QUOTES)); ?>' ) this.value = '';"
	onblur="if ( this.value == '' ) this.value = '<?php echo addslashes(htmlspecialchars(get_caption('search'), ENT_QUOTES)); ?>';"
	/><input type="submit" id="go" class="go" value="<?php echo htmlspecialchars(get_caption('go'), ENT_QUOTES); ?>" />
</form>
</div><!-- #search_form -->
<?php
} # end display_search_form()

add_action('display_search_form', 'display_search_form');


#
# display_image_header()
#

function display_image_header()
{
?><div id="sitename" class="sitename">
<h1><img src="<?php echo get_template_directory_uri() . '/' . image_header; ?>"
	width="<?php echo image_header_width; ?>" height="<?php echo image_header_height; ?>"
	alt="<?php bloginfo('sitename'); ?>"
	/></h1>
</div><!-- #sitename -->
<?php
} # end display_image_header()


#
# display_header_css()
#

function display_header_css($header)
{
	$site_url = trailingslashit(get_option('siteurl'));

	list($width, $height) = getimagesize($header);

?><style type="text/css">
.header_bg #header div.pad
{
	background-image: url(<?php echo str_replace(ABSPATH, $site_url, $header); ?>);
	background-repeat: no-repeat;
	height: <?php echo $height; ?>px;
	border: 0px;
	overflow: hidden;
	position: relative;
}
</style>
<?php
} # end display_header_css()


#
# display_header_background_css()
#

function display_header_background_css($header)
{
	$site_url = trailingslashit(get_option('siteurl'));

	list($width, $height) = getimagesize($header);

?><style type="text/css">
.header_bg #header div.pad
{
	background-image: url(<?php echo str_replace(ABSPATH, $site_url, $header); ?>);
	background-repeat: repeat-x;
	height: <?php echo $height; ?>px;
	border: 0px;
	overflow: hidden;
	position: relative;
}
</style>
<?php
} # end display_header_background_css()


#
# auto_remove_search_form()
#

function auto_remove_search_form()
{
	$show_search_form = true;

	$sidebars = get_option('sidebars_widgets');

	foreach ( (array) $sidebars as $sidebar )
	{
		if ( in_array('Search', (array) $sidebar)
			|| in_array('Google Search', (array) $sidebar)
			|| in_array('search', (array) $sidebar)
			|| in_array('google-search', (array) $sidebar)
			)
		{
			$show_search_form = false;
			break;
		}
	}

	if ( !apply_filters('show_search_form', $show_search_form) )
	{
		remove_action('display_search_form', 'display_search_form');
	}
} # end auto_remove_search_form()

add_action('wp_head', 'auto_remove_search_form');
?>