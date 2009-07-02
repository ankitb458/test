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

		echo '<div id="footer_nav" class="footer_nav inline_menu">';
		
		sem_nav_menus::display('footer');
		
		echo '</div><!-- #footer_nav -->' . "\n";
		
		do_action('display_footer_spacer');
		
		echo '</div>' . "\n";
		echo '</div><!-- #footer -->' . "\n";
	} # footer_widget()
} # sem_footer

sem_footer::init();
?>