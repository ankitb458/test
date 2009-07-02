<?php
class sem_footer
{
	#
	# init()
	#
	
	function init()
	{
		add_action('widgets_init', array('sem_footer', 'widgetize'));
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
			'credits' => array(
				'label' => 'Footer: Credits',
				'desc' => 'Footer: Credits. Only works in the footer.',
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

		echo '<div id="footer"'
			. ' class="footer'
				. ( $sem_options['float_footer'] && $sem_options['show_copyright']
					? ' float_nav'
					: ''
					)
				. '"'
			. '>' . "\n";
		echo '<div class="pad">' . "\n";

		echo '<div id="footer_nav" class="footer_nav inline_menu">';
		sem_nav_menus::display('footer');
		echo '</div><!-- #footer_nav -->' . "\n";
		
		if ( $sem_options['show_copyright'] )
		{
			global $wpdb;
			global $sem_captions;

			$copyright_notice = $sem_captions['copyright'];

			$year = date('Y');

			if ( strpos($copyright_notice, '%admin_name%') !== false )
			{
				$admin_login = $wpdb->get_var("select user_login from wp_users where user_email = '" . $wpdb->escape(get_option('admin_email')) . "' ORDER BY user_registered ASC limit 1");
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

			echo '<div id="copyright_notice" class="copyright_notice">';
			echo $copyright_notice;
			echo '</div><!-- #copyright_notice -->' . "\n";
		}

		do_action('display_footer_spacer');
		
		echo '</div>' . "\n";			
		
		echo '</div><!-- #footer -->' . "\n";
	} # footer_widget()


	#
	# credits_widget()
	#
	
	function credits_widget($args)
	{
		global $sem_options;
		
		if ( is_admin() || !$GLOBALS['the_footer'] ) return;

//		echo '<div class="pad">' . "\n";

		if ( $sem_options['show_credits'] )
		{		
			echo '<div id="credits" class="credits">Made with '
				. '<a href="http://wordpress.org">WordPress</a>'
				. ' and '
				. sem_footer::get_theme_description()
				. ' &bull; '
				. get_skin_credits()
				. '</div><!-- #credits -->';
		}
		
//		echo '</div>' . "\n";			
		
	} # credits_widget()

	#
	# get_theme_description()
	#

	function get_theme_description()
	{
		$theme_descriptions = array(
			'<a href="http://www.semiologic.com">Semiologic</a>',
			'a healthy dose of <a href="http://www.semiologic.com">Semiologic</a>',
			'the <a href="http://www.semiologic.com/software/sem-theme/">Semiologic theme and CMS</a>',
			'an <a href="http://www.semiologic.com/software/sem-theme/">easy to use WordPress theme</a>',
			'an <a href="http://www.semiologic.com/software/sem-theme/">easy to customize WordPress theme</a>',
			'a <a href="http://www.semiologic.com/software/sem-theme/">search engine optimized WordPress theme</a>'
			);

		$theme_descriptions = apply_filters('theme_descriptions', $theme_descriptions);

		if ( sizeof($theme_descriptions) )
		{
			$i = rand(0, sizeof($theme_descriptions) - 1);

			return $theme_descriptions[$i];
		}
		else
		{
			return '<a href="http://www.semiologic.com">Semiologic</a>';
		}
	} # end get_theme_description()
} # sem_footer

sem_footer::init();
?>