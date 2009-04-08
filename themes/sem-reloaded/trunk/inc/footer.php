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
		global $sem_captions;
		
		if ( is_admin() || !$GLOBALS['the_footer'] ) return;
		
		echo '<div id="footer" class="wrapper'
				. ( $sem_options['float_footer'] && $sem_captions['copyright']
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
		
		if ( $copyright_notice = $sem_captions['copyright'] )
		{
			global $wpdb;

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
		global $sem_captions;
		
		echo '<div id="credits">' . "\n"
			. '<div id="credits_top"><div class="hidden"></div></div>' . "\n"
			. '<div id="credits_bg">' . "\n";
		if ( $sem_captions['credits'] ) {
			$theme_credits = sem_footer::get_theme_credits();
			$skin_credits = sem_footer::get_skin_credits();
			
			$credits = str_replace(
				array(
					'%wordpress%',
					'%semiologic%',
					'%skin_name%',
					'%skin_author%',
					),
				array(
					'<a href="http://wordpress.org">' . __('WordPress') . '</a>',
					$theme_credits,
					$skin_credits['skin_name'],
					$skin_credits['skin_author'],
					),
				$sem_captions['credits']
				);
			
			echo '<div class="pad">'
				. $credits
				. '</div>' . "\n";
		}
		
		echo '</div>' . "\n"
			. '<div id="credits_bottom"><div class="hidden"></div></div>' . "\n"
			. '</div><!-- credits -->' . "\n";
	} # display_credits()
	
	
	#
	# get_theme_credits()
	#

	function get_theme_credits()
	{
		if ( defined('sem_fixes_path') || defined('sem_docs_path') ) {
			return '<a href="http://www.getsemiologic.com">'
				. __('Semiologic Pro')
				. '</a>';
		} else {
			$theme_descriptions = array(
				'the <a href="http://www.semiologic.com/software/sem-reloaded/">Semiologic Reloaded theme</a>',
				'an <a href="http://www.semiologic.com/software/sem-reloaded/">easy to use WordPress theme</a>',
				'an <a href="http://www.semiologic.com/software/sem-reloaded/">easy to customize WordPress theme</a>',
				);
			
			$i = rand(0, sizeof($theme_descriptions) - 1);

			return '<a href="http://wordpress.org">WordPress</a> and ' . $theme_descriptions[$i];
		}
	} # get_theme_credits()
	
	
	#
	# get_skin_credits()
	#
	
	function get_skin_credits()
	{
		global $sem_options;
		
		if ( !isset($sem_options['skin_data']) || !is_array($sem_options['skin_data']) ) {
			$skin_data = sem_skin::get_skin_data($sem_options['active_skin']);
			$sem_options['skin_data'] = $skin_data;
			update_option('sem6_options', $sem_options);
		} else {
			$skin_data = $sem_options['skin_data'];
		}
		
		return array(
			'skin_name' => $skin_data['name'],
			'skin_author' => '<a href="' . htmlspecialchars($skin_data['author_uri']) . '">'
				. $skin_data['author']
				. '</a>'
			);
	} # get_skin_credits()
} # sem_footer

sem_footer::init();
?>