<?php

class sem_header
{
	#
	# init()
	#

	function init()
	{
		add_action('widgets_init', array('sem_header', 'widgetize'));
		add_action('wp_head', array('sem_header', 'wire_header'));

		add_action('the_header', array('sem_header', 'panel'));
	} # init


	#
	# widgetize()
	#

	function widgetize()
	{
		register_sidebar_widget(
			'Header',
			array('sem_header', 'widget'),
			'sem_header_widget'
			);
		register_widget_control(
			'Header',
			array('sem_header_admin', 'widget_control'),
			450,
			220
			);
	} # widgetize()


	#
	# panel()
	#

	function panel()
	{
		$sidebars_widgets = wp_get_sidebars_widgets();

		if ( empty($sidebars_widgets['the_header']) )
		{
			echo '<div style="color: firebrick; padding: 20px; font-weight: bold;">'
				. 'Your "Header" Panel is empty. Browse Presentation / Widgets and add a few widgets to it. In particular, those called "Header" and "Header: Nav Menu".'
				. '</div>';
		}

		$GLOBALS['the_header'] = true;
		dynamic_sidebar('the_header');
		$GLOBALS['the_header'] = false;
	} # panel()


	#
	# widget()
	#

	function widget($args)
	{
		if ( $GLOBALS['the_header'] )
		{
			do_action('display_header');
		}
		else
		{
			echo $args['before_widget']
				. __('The Header widget will only work in the Header area.')
				. $args['after_widget'];
		}
	} # widget()


	#
	# get_header()
	#

	function get_header()
	{
		if ( !is_admin() && defined('sem_theme_header') )
		{
			return sem_theme_header;
		}

		global $sem_options;

		if ( !isset($sem_options['header']['mode']) )
		{
			sem_header::upgrade();
		}

		if ( is_singular() )
		{
			$post_ID = intval($GLOBALS['wp_query']->get_queried_object_id());
		}

		if ( defined('GLOB_BRACE') )
		{
			if ( $post_ID
				&& ( $header = glob(ABSPATH . 'wp-content/header/' . $post_ID . '/header{,-*}.{jpg,jpeg,png,gif,swf}', GLOB_BRACE) ) )
			{
				$header = current($header);
			}
			elseif ( $header = glob(sem_path . '/skins/' . get_active_skin() . '/{header,header-background,header-bg,logo}.{jpg,jpeg,png,gif,swf}', GLOB_BRACE) )
			{
				$header = current($header);

				$header_name = basename($header);

				preg_match("/(.+)\.[^\.]+/", $header_name, $header_type);
				$header_type = end($header_type);

				switch ( $header_type )
				{
				case 'header':
				case 'header-background':
					if ( $sem_options['header']['mode'] != 'header' )
					{
						$sem_options['header']['mode'] = 'header';
						update_option('sem5_options', $sem_options);
					}
					break;

				case 'header-bg':
					if ( $sem_options['header']['mode'] != 'background' )
					{
						$sem_options['header']['mode'] = 'background';
						update_option('sem5_options', $sem_options);
					}
					break;

				case 'logo':
					if ( $sem_options['header']['mode'] != 'logo' )
					{
						$sem_options['header']['mode'] = 'logo';
						update_option('sem5_options', $sem_options);
					}
					break;
				}
			}
			elseif ( $header = glob(ABSPATH . 'wp-content/header/header{,-*}.{jpg,jpeg,png,gif,swf}', GLOB_BRACE) )
			{
				$header = current($header);
			}
			else
			{
				$header = false;
			}
		}
		else
		{
			if ( $post_ID
				&& ( $header = glob(ABSPATH . 'wp-content/header/' . $post_ID . '/header-*.jpg') ) )
			{
				$header = current($header);
			}
			elseif ( $header = glob(ABSPATH . 'wp-content/header/header-*.jpg') )
			{
				$header = current($header);
			}
			else
			{
				$header = false;
			}
		}

		define('sem_theme_header', $header);

		return $header;
	} # get_header()


	#
	# get_class()
	#

	function get_class()
	{
		global $sem_options;

		$class = '';

		if ( sem_header::get_header() )
		{
			switch ( $sem_options['header']['mode'] )
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
		global $sem_options;

		if ( !defined('GLOB_BRACE') )
		{
			$sem_options['header']['mode'] = 'header';
			update_option('sem5_options', $sem_options);
			return;
		}

		$skin = get_active_skin();

		if ( $header = glob(sem_path . '/skins/' . $skin . '/header{,-background,-bg}.{jpg,jpeg,png,gif,swf}', GLOB_BRACE) )
		{
			$header = end($header);
		}
		elseif ( $header = glob(sem_path . '/header{,-background,-bg}.{jpg,jpeg,png,gif,swf}', GLOB_BRACE) )
		{
			$header = end($header);
		}
		elseif ( $header = glob(sem_path . '/headers/header{,-background,-bg}.{jpg,jpeg,png,gif,swf}', GLOB_BRACE) )
		{
			$header = end($header);
		}
		elseif ( $header = $sem_options['active_header'] )
		{
			$header = sem_path . '/headers/' . $sem_options['active_header'];
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
			@chmod(ABSPATH . 'wp-content/headers/header.' . $ext, 0666);

			switch ( $name )
			{
			case 'header-background':
				$sem_options['header']['mode'] = 'header';
				break;

			case 'header-bg':
				$sem_options['header']['mode'] = 'background';
				break;

			case 'header':
				switch ( $ext )
				{
				case 'swf':
					$sem_options['header']['mode'] = 'background';
					break;

				default:
					$sem_options['header']['mode'] = 'logo';
					break;
				}
				break;

			default:
				$sem_options['header']['mode'] = 'background';
				break;
			}
		}
		else
		{
			$sem_options['header']['mode'] = 'header';
		}

		update_option('sem5_options', $sem_options);
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
			$header = sem_header::get_header();
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
			$header = sem_header::get_header();
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
		global $sem_options;

		$header = sem_header::get_header();

		if ( $header )
		{
			preg_match("/\.([^\.]+)$/", $header, $ext);
			$ext = end($ext);

			switch ( $sem_options['header']['mode'] )
			{
			case 'logo':
				if ( $sem_options['invert_header'] )
				{
					remove_action('display_sitename', 'display_sitename');
					remove_action('display_tagline', 'display_tagline');
					add_action('display_sitename', 'display_tagline');
					add_action('display_tagline', 'display_sitename');

					if ( $ext == 'swf' )
					{
						add_action('display_tagline', array('sem_header', 'display_flash'));
						remove_action('enhance_header_div', 'enhance_header_div');
					}
					else
					{
						add_action('display_tagline', array('sem_header', 'display_logo'));
					}
				}
				else
				{
					remove_action('display_sitename', 'display_sitename');

					if ( $ext == 'swf' )
					{
						add_action('display_sitename', array('sem_header', 'display_flash'));
						remove_action('enhance_header_div', 'enhance_header_div');
					}
					else
					{
						add_action('display_sitename', array('sem_header', 'display_logo'));
					}
				}
				break;

			case 'header':
				remove_action('display_sitename', 'display_sitename');
				remove_action('display_tagline', 'display_tagline');
				reset_plugin_hook('display_header_spacer');

				if ( $ext == 'swf' )
				{
					remove_action('enhance_header_div', 'enhance_header_div');
					add_action('display_sitename', array('sem_header', 'display_flash'));
				}
				else
				{
					display_header_css($header);
				}
				break;

			case 'background':
					display_header_background_css($header);

					if ( $sem_options['invert_header'] )
					{
						remove_action('display_sitename', 'display_sitename');
						remove_action('display_tagline', 'display_tagline');
						add_action('display_sitename', 'display_tagline');
						add_action('display_tagline', 'display_sitename');
					}
				break;
			}
		}
		elseif ( $sem_options['invert_header'] )
		{
			remove_action('display_sitename', 'display_sitename');
			remove_action('display_tagline', 'display_tagline');
			add_action('display_sitename', 'display_tagline');
			add_action('display_tagline', 'display_sitename');
		}
	} # wire_header()
} # sem_header

sem_header::init();


#
# display_header()
#

function display_header()
{
	global $sem_options;

	echo '<div id="header" class="header'
		. ( $sem_options['invert_header']
			? ' invert_header'
			: ''
			)
		. '" ';

	do_action('enhance_header_div');

	echo '>' . "\n";

	echo '<div class="pad">' . "\n";

	do_action('display_tagline');
	do_action('display_sitename');
	do_action('display_header_spacer');

	echo '</div>' . "\n";
	echo '</div><!-- #header -->' . "\n";
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
	onclick="top.location.href = '<?php echo trailingslashit(get_option('home')); ?>'"
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
	global $sem_options;

	echo '<div id="navbar" class="navbar'
		. ( $sem_options['show_search_form']
			? ' float_nav'
			: ''
			)
		. '">' . "\n"
		. '<div class="pad">' . "\n";

		do_action('display_header_nav');
		do_action('display_search_form');
		do_action('display_navbar_spacer');

	echo '</div>' . "\n"
		. '</div><!-- #navbar -->' . "\n";
} # end display_navbar()

add_action('display_navbar', 'display_navbar');


#
# display_header_nav()
#

function display_header_nav()
{
	echo '<div id="header_nav" class="header_nav inline_menu">';

	display_nav_menu('header_nav', '|');

	echo '</div><!-- #header_nav -->' . "\n";
} # end display_header_nav()

add_action('display_header_nav', 'display_header_nav');


#
# display_search_form()
#

function display_search_form()
{
?><div id="search_form" class="search_form">
<?php sem_search_form(); ?>
</div><!-- #search_form -->
<?php
} # end display_search_form()


#
# sem_search_form()
#

function sem_search_form()
{
	global $sem_captions;

	$search = $sem_captions['search_field'];
	$go = $sem_captions['search_button'];

?>
<form method="get" action="<?php bloginfo('url'); ?>" id="searchform" name="searchform">
<input type="text"
	id="s" class="s" name="s"
	value="<?php echo htmlspecialchars($search); ?>"
	onfocus="if ( this.value == '<?php echo addslashes(htmlspecialchars($search)); ?>' ) this.value = '';"
	onblur="if ( this.value == '' ) this.value = '<?php echo addslashes(htmlspecialchars($search)); ?>';"
	/><input type="submit" id="go" class="go" value="<?php echo htmlspecialchars($go); ?>" />
</form>
<?php
} # sem_search_form()


#
# display_image_header()
#

function display_image_header()
{
?><div id="sitename" class="sitename">
<h1><img src="<?php echo trailingslashit(get_template_directory_uri()) . image_header; ?>"
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
		if ( in_array('search', (array) $sidebar)
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