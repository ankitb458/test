<?php
class sem_header
{
	#
	# init()
	#
	
	function init()
	{
		add_action('widgets_init', array('sem_header', 'widgetize'));
		add_action('wp_head', array('sem_header', 'wire_header'), 30);
		add_action('wp_print_scripts', array('sem_header', 'scripts'));
	} # init()
	
	
	#
	# scripts()
	#
	
	function scripts()
	{
		if ( ( $header = sem_header::get_header() )
			&& strpos(strtolower($header), '.swf') !== false
			)
		{
			
			wp_enqueue_script( 'swfobject', sem_url . '/js/swfobject.js', false, '1.5' );
		}
	} # scripts()
	
	
	#
	# widgetize()
	#
	
	function widgetize()
	{
		foreach ( array(
			'header' => array(
				'label' => 'Header: Site Header',
				'desc' => 'Header: Site Name, tagline and nav menu. Only works in the header.',
				),
			'navbar' => array(
				'label' => 'Header: Nav Menu',
				'desc' => 'Header: Navigation Menu. Only works in the header.',
				),
			'header_boxes' => array(
				'label' => 'Header: Inline Boxes',
				'desc' => 'Header: Inline Boxes. Lets you decide where the Header Boxes panel goes. Only works in the header.',
				),
			) as $widget_id => $widget_details )
		{
			$widget_options = array('classname' => $widget_id, 'description' => $widget_details['desc'] );
			$control_options = array('width' => 500);

			wp_register_sidebar_widget($widget_id, $widget_details['label'], array('sem_header', $widget_id . '_widget'), $widget_options );
			wp_register_widget_control($widget_id, $widget_details['label'], array('sem_header_admin', $widget_id . '_widget_control'), $control_options );
		}
	} # widgetize()
	
	
	#
	# header_widget()
	#
	
	function header_widget($args)
	{
		if ( is_admin() || !$GLOBALS['the_header'] ) return;
		
		global $sem_options;

		$logo = false;
		$background = false;
		$flash = false;

		if ( $header = sem_header::get_header() )
		{
			$ext = pathinfo($header, PATHINFO_EXTENSION);
			
			$flash = ( $ext == 'swf' );

			switch ( $sem_options['header']['mode'] )
			{
			case 'logo':
				$logo = true;
				break;

			case 'background':
				$background = true;
				break;
			
			case 'header':
				reset_plugin_hook('display_header_spacer');
				break;
			}
		}
		
		echo '<div id="header" class="wrapper'
			. ( $sem_options['invert_header']
				? ' invert_header'
				: ''
				)
			. '"'
 			. ' title="'
				. htmlspecialchars(get_option('blogname'))
				. ' &bull; '
				. htmlspecialchars(get_option('blogdescription'))
				. '"';
		
		if ( !$flash && !is_front_page() )
		{
			echo ' style="cursor: pointer;"'
				. ' onclick="top.location.href = \'' . user_trailingslashit(get_option('home')) . '\'"';
		}

		echo '>' . "\n";
		
		echo '<div id="header_top"><div class="hidden"></div></div>' . "\n";
		
		echo '<div id="header_bg">' . "\n";
		
		echo '<div class="wrapper_item">' . "\n";
		
		if ( !$header || $background || $logo )
		{
			echo '<div id="header_img" class="pad">' . "\n";

			$tagline = '<div id="tagline" class="tagline">'
				. get_option('blogdescription')
				. '</div>' . "\n";

			if ( $logo )
			{
				if ( $flash )
				{
					$site_name = '<div id="sitename" class="sitename">'
					 	. sem_header::display_flash($header)
						. '</div>' . "\n";
				}
				else
				{
					$site_name = '<div id="sitename" class="sitename">'
						. ( !is_front_page()
							? ( '<a href="' . user_trailingslashit(get_option('home')) . '">' . sem_header::display_logo($header) . '</a>' )
							: sem_header::display_logo($header)
							)
						. '</div>' . "\n";
				}
			}
			else
			{
				$site_name = '<div id="sitename" class="sitename">'
					. ( !$flash && !is_front_page()
						? ( '<a href="' . user_trailingslashit(get_option('home')) . '">' . get_option('blogname') . '</a>' )
						: get_option('blogname')
						)
					. '</div>' . "\n";
			}
			
			if ( $sem_options['invert_header'] )
			{
				echo $site_name;
				echo $tagline;
			}
			else
			{
				echo $tagline;
				echo $site_name;
			}
			
			echo '</div>' . "\n";
		}
		else
		{
			if ( !$flash )
			{
				echo '<div id="header_img" class="pad">'
					. '<img src="' . sem_url . '/inc/img/spacer.gif" height="100%" width="100%" alt="'
						. htmlspecialchars(get_option('blogname'))
						. ' &bull; '
						. htmlspecialchars(get_option('blogdescription'))
						. '" />'
					. '</div>' . "\n";
			}
			else
			{
				echo sem_header::display_flash($header);
			}
		}
		
		do_action('display_header_spacer');
		
		echo '</div>' . "\n";
		
		echo '</div>' . "\n";
		
		echo '<div id="header_bottom"><div class="hidden"></div></div>' . "\n";
		
		echo '</div><!-- header -->' . "\n";
	} # header_widget()
	
	
	#
	# letter()
	#
	
	function letter()
	{
		$header = sem_header::get_header();
		
		if ( $header
			&& strpos(
				$header,
				'/' . intval($GLOBALS['wp_query']->get_queried_object_id()) . '/'
				) !== false
			)
		{
			global $sem_options;

			$logo = false;
			$background = false;
			$flash = false;

			$ext = pathinfo($header, PATHINFO_EXTENSION);

			$flash = ( $ext == 'swf' );

			if ( !$flash )
			{
				echo '<div id="header_img">'
					. '<img src="' . sem_url . '/inc/img/spacer.gif" height="100%" width="100%" alt="'
						. htmlspecialchars(get_option('blogname'))
						. ' &bull; '
						. htmlspecialchars(get_option('blogdescription'))
						. '" />'
					. '</div>' . "\n";
			}
			else
			{
				echo sem_header::display_flash($header);
			}
		}
	} # letter()


	#
	# navbar_widget()
	#

	function navbar_widget($args)
	{
		if ( is_admin() || !$GLOBALS['the_header'] ) return;

		global $sem_options;
		global $sem_captions;
		
		echo '<div id="navbar" class="wrapper'
			. ( $sem_options['show_search_form']
				? ' float_nav'
				: ''
				)
				. '"'
			. '>' . "\n";
		
		echo '<div id="navbar_top"><div class="hidden"></div></div>' . "\n";
		
		echo '<div id="navbar_bg">' . "\n";
		
		echo '<div class="wrapper_item">' . "\n";
		
		echo '<div class="pad">' . "\n";
		
		echo '<div id="header_nav" class="header_nav inline_menu">';

		sem_nav_menus::display('header');

		echo '</div><!-- header_nav -->' . "\n";

		if ( $sem_options['show_search_form'] )
		{
			echo '<div id="search_form" class="search_form">';

			if ( is_search() )
			{
				global $wp_query;
				
				$search = implode(' ', $wp_query->query_vars['search_terms']);
			}
			else
			{
				$search = $sem_captions['search_field'];
			}
			
			$go = $sem_captions['search_button'];
			
			echo '<form method="get" action="' . user_trailingslashit(get_option('home')) . '" id="searchform" name="searchform">'
				. '&nbsp;'				# force line-height
				. '<input type="text" id="s" class="s" name="s"'
					. ' value="' . htmlspecialchars($search) . '"'
					. ( !is_search()
						? ( ' onfocus="if ( this.value == \'' . addslashes(htmlspecialchars($search)) . '\' )'
						 		. ' this.value = \'\';"'
							. ' onblur="if ( this.value == \'\' )'
							 	. ' this.value = \'' . addslashes(htmlspecialchars($search)) . '\';"'
							)
						: ''
						)
					. '/>'
					. '<input type="submit" id="go" class="go button" value="' . htmlspecialchars($go) . '" />'
					. '</form>';
			
			echo '</div><!-- search_form -->';
		}

		echo '<div class="spacer"></div>' . "\n"
			. '</div>' . "\n"
			. '</div>' . "\n"
			. '</div>' . "\n";
		
		echo '<div id="navbar_bottom"><div class="hidden"></div></div>' . "\n";
		
		echo '</div><!-- navbar -->' . "\n";
	} # navbar_widget()
	
	
	#
	# header_boxes_widget()
	#
	
	function header_boxes_widget()
	{
		if ( !is_admin() && $GLOBALS['the_header'] )
		{
			return sem_panels::the_header_boxes();
		}
	} # header_boxes_widget()
	
	
	#
	# wire_header()
	#
	
	function wire_header()
	{
		global $sem_options;
		
		if ( $header = sem_header::get_header() )
		{
			$ext = pathinfo($header, PATHINFO_EXTENSION);

			if ( $flash = ( $ext == 'swf' ) )
			{
				reset_plugin_hook('display_header_spacer');
			}
			else
			{
				switch ( $sem_options['header']['mode'] )
				{
				case 'header':
					reset_plugin_hook('display_header_spacer');
				
					if ( $flash )
					{
						break;
					}

					$css = <<<EOF

<style type="text/css">
.skin #header_img
{
	background: url({header_url}) no-repeat top left;
	height: {header_height}px;
	border: 0px;
	overflow: hidden;
	position: relative;
}
</style>

EOF;
				break;

				case 'background':
					$css = <<<EOF

<style type="text/css">
.skin #header_img
{
	background-image: url({header_url}) repeat-x top left;
	height: {header_height}px;
	border: 0px;
	overflow: hidden;
	position: relative;
}
</style>

EOF;
					break;
				}

				$site_url = trailingslashit(get_option('siteurl'));
				$header_url = str_replace(ABSPATH, $site_url, $header);

				list($header_width, $header_height) = getimagesize($header);

				echo str_replace(
					array('{header_url}', '{header_height}'),
					array($header_url, $header_height),
					$css
					);
			}
		}
	} # wire_header()
	
	
	#
	# display_logo()
	#

	function display_logo($header = null)
	{
		if ( !$header )
		{
			$header = sem_header::get_header();
		}

		if ( !$header ) return '';
		
		$site_url = trailingslashit(get_option('siteurl'));

		list($width, $height) = getimagesize($header);

		return '<img src="'
					. str_replace(ABSPATH, $site_url, $header)
					. '"'
				. ' alt="' . get_bloginfo('name') . '"'
				. ' height="' . $height . '" width="' . $width . '"'
				. ' />';
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

		if ( !$header ) return '';
		
		$id = 'h' . md5($header);
		$site_url = trailingslashit(get_option('siteurl'));

		list($width, $height) = getimagesize($header);

		return '<div id="' . $id . '">' . "\n"
			. __('<a href="http://www.macromedia.com/go/getflashplayer">Get Flash</a> to see this player.')
			. '</div>'
			. '<script type="text/javascript">' . "\n"
			. 'var so = new SWFObject("'. str_replace(ABSPATH, $site_url, $header) . '","' . $id . '","' . $width . '","' . $height . '","7");' . "\n"
			. 'so.write("' . $id . '");' . "\n"
			. '</script>' . "\n";
	} # display_flash()
	
	
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
	# get_header()
	#

	function get_header()
	{
		static $header;
		
		if ( !is_admin() && isset($header) )
		{
			return $header;
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
			if ( isset($post_ID)
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
						update_option('sem6_options', $sem_options);
					}
					break;

				case 'header-bg':
					if ( $sem_options['header']['mode'] != 'background' )
					{
						$sem_options['header']['mode'] = 'background';
						update_option('sem6_options', $sem_options);
					}
					break;

				case 'logo':
					if ( $sem_options['header']['mode'] != 'logo' )
					{
						$sem_options['header']['mode'] = 'logo';
						update_option('sem6_options', $sem_options);
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

		return $header;
	} # get_header()


	#
	# upgrade()
	#

	function upgrade()
	{
		global $sem_options;

		if ( !defined('GLOB_BRACE') )
		{
			$sem_options['header']['mode'] = 'header';
			update_option('sem6_options', $sem_options);
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

			$ext = pathinfo($name, PATHINFO_EXTENSION);

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

		update_option('sem6_options', $sem_options);
	} # upgrade()
} # sem_header

sem_header::init();
?>