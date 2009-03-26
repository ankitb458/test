<?php
class sem_footer
{
	#
	# init()
	#
	
	function init()
	{
		add_action('widgets_init', array('sem_footer', 'widgetize'));
		
		add_action('wp_footer', array('sem_footer', 'display_credits'));
	} # init()
	
	
	#
	# widgetize()
	#
	
	function widgetize()
	{
		foreach ( array(
			'footer' => array(
				'label' => 'Footer: Nav Menu',
				'desc' => 'Footer: Navigation Menu. Only works in the footer.',
				),
			'footer_boxes' => array(
				'label' => 'Footer: Inline Boxes',
				'desc' => 'Footer: Inline Boxes. Lets you decide where the Footer Boxes Bar panel goes. Only works in the footer.',
				),
			) as $widget_id => $widget_details )
		{
			$widget_options = array('classname' => $widget_id, 'description' => $widget_details['desc'] );
			$control_options = array('width' => 500);

			wp_register_sidebar_widget($widget_id, $widget_details['label'], array('sem_footer', $widget_id . '_widget'), $widget_options );
			wp_register_widget_control($widget_id, $widget_details['label'], array('sem_footer_admin', $widget_id . '_widget_control'), $control_options );
		}
	} # widgetize()
	
	
	#
	# footer_widget()
	#
	
	function footer_widget($args)
	{
		global $sem_options;
		
		if ( is_admin() || !$GLOBALS['the_footer'] ) return;
		
		echo '<div id="footer" class="wrapper'
				. ( $sem_options['float_footer'] && $sem_options['show_copyright']
					? ' float_nav'
					: ''
					)
				. '"'
			. '>' . "\n";
		
		echo '<div id="footer_top"><div class="hidden"></div></div>' . "\n";
		
		echo '<div id="footer_bg">' . "\n"
			. '<div class="wrapper_item">' . "\n"
			. '<div class="pad">' . "\n";
		
		echo '<div id="footer_nav" class="inline_menu">';
		
		sem_nav_menus::display('footer');
		
		echo '</div><!-- footer_nav -->' . "\n";
		
		if ( $sem_options['show_copyright'] )
		{
			global $wpdb;
			global $sem_captions;

			$copyright_notice = $sem_captions['copyright'];

			$year = date('Y');

			if ( strpos($copyright_notice, '%admin_name%') !== false )
			{
				$admin_login = $wpdb->get_var("
					SELECT	user_login
					FROM	wp_users
					WHERE	user_email = '" . $wpdb->escape(get_option('admin_email')) . "'
					ORDER BY user_registered ASC
					LIMIT 1
					");
				$admin_user = get_userdatabylogin($admin_login);

				if ( $admin_user->display_name )
				{
					$admin_name = $admin_user->display_name;
				}
				else
				{
					$admin_name = preg_replace("/@.*$/", '', $admin_user->user_email);

					$admin_name = preg_replace("/[_.-]/", ' ', $admin_name);

					$admin_name = ucwords($admin_name);
				}

				$copyright_notice = str_replace('%admin_name%', $admin_name, $copyright_notice);
			}

			$copyright_notice = str_replace('%year%', $year, $copyright_notice);

			echo '<div id="copyright_notice">';
			echo $copyright_notice;
			echo '</div><!-- #copyright_notice -->' . "\n";
		}

		echo '<div class="spacer"></div>' . "\n"
			. '</div>' . "\n"
			. '</div>' . "\n"
			. '</div>' . "\n";
		
		echo '<div id="footer_bottom"><div class="hidden"></div></div>' . "\n";
		
		echo '</div><!-- footer -->' . "\n";
	} # footer_widget()
	
	
	#
	# footer_boxes_widget()
	#
	
	function footer_boxes_widget()
	{
		if ( !is_admin() && $GLOBALS['the_footer'] )
		{
			return sem_panels::the_footer_boxes();
		}
	} # footer_boxes_widget()


	#
	# display_credits()
	#
	
	function display_credits($args)
	{
		global $sem_options;
		
		echo '<div id="credits">' . "\n"
			. '<div id="credits_top"><div class="hidden"></div></div>' . "\n"
			. '<div id="credits_bg">' . "\n"
			. ( $sem_options['show_credits']
				? ( '<div>' . "\n"
					. '<div class="pad">' . "\n"
					. 'Made with '
					. sem_footer::get_theme_credits()
					. ( ( $sem_options['active_layout'] != 'm' )
						? ' &bull; '
						: '<br />'
						)
					. sem_footer::get_skin_credits()
					. '</div>' . "\n"
					. '</div>' . "\n" )
				: ''
				)
			. '</div>' . "\n"
			. '<div id="credits_bottom"><div class="hidden"></div></div>' . "\n"
			. '</div><!-- credits -->' . "\n";
	} # display_credits()
	
	
	#
	# get_theme_credits()
	#

	function get_theme_credits()
	{
		$theme_descriptions = array(
			'<a href="http://www.semiologic.com">Semiologic</a>',
			'a healthy dose of <a href="http://www.semiologic.com">Semiologic</a>',
			'the <a href="http://www.semiologic.com/software/sem-reloaded/">Semiologic theme and CMS</a>',
			'an <a href="http://www.semiologic.com/software/sem-reloaded/">easy to use WordPress theme</a>',
			'an <a href="http://www.semiologic.com/software/sem-reloaded/">easy to customize WordPress theme</a>',
			'a <a href="http://www.semiologic.com/software/sem-reloaded/">search engine optimized WordPress theme</a>'
			);

		$theme_descriptions = apply_filters('theme_descriptions', $theme_descriptions);

		if ( sizeof($theme_descriptions) )
		{
			$i = rand(0, sizeof($theme_descriptions) - 1);

			return '<a href="http://wordpress.org">WordPress</a>'
				. ' and '
				. $theme_descriptions[$i];
		}
		else
		{
			return '<a href="http://www.semiologic.com">Semiologic</a>';
		}
	} # get_theme_credits()
	
	
	#
	# get_skin_credits()
	#
	
	function get_skin_credits()
	{
		global $sem_options;
		
		$skin_data = sem_skin::get_skin_data($sem_options['active_skin']);

		return str_replace(
			array('%name%', '%author%', '%author_uri%'),
			array($skin_data['name'], $skin_data['author'], $skin_data['author_uri']),
			__('%name% skin by <a href="%author_uri%">%author%</a>')
			);
	} # get_skin_credits()
} # sem_footer

sem_footer::init();
?>