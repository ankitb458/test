<?php
class sem_footer
{
	#
	# init()
	#

	function init()
	{
		add_action('the_footer', array('sem_footer', 'panel'));
	} # init()


	#
	# panel()
	#

	function panel()
	{
		global $sem_options;
		$sidebars = get_option('sidebars_widgets');

		if ( $sidebars['the_footer'] )
		{
			$GLOBALS['the_footer'] = true;

			echo '<div id="footer" class="footer'
				. ( $sem_options['float_footer'] && $sem_options['show_copyright']
					? ' float_nav'
					: ''
					)
				. '">' . "\n"
				. '<div class="pad">' . "\n";

			dynamic_sidebar('the_footer');
			do_action('display_footer_spacer');

			echo '</div>' . "\n"
				. '</div><!-- #footer -->' . "\n";

			$GLOBALS['the_footer'] = false;
		}
	} # panel()
} # sem_footer

sem_footer::init();



#
# display_footer()
#

function display_footer()
{
?>
<div id="footer" class="footer">
<div class="pad">
<?php
do_action('display_copyright_notice');
do_action('display_footer_nav');
do_action('display_footer_spacer');
?></div>
</div><!-- #footer -->

<?php
} # end display_footer()

add_action('display_footer', 'display_footer');


#
# display_copyright_notice()
#

function display_copyright_notice()
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

?><div id="copyright_notice" class="copyright_notice">
<?php echo $copyright_notice; ?></div><!-- #copyright_notice -->
<?php
} # end display_copyright_notice()


#
# display_footer_nav()
#

function display_footer_nav()
{
?><div id="footer_nav" class="footer_nav inline_menu">
<?php display_nav_menu('footer_nav', '|'); ?></div><!-- #footer_nav -->
<?php
} # end display_footer_nav()

add_action('display_footer_nav', 'display_footer_nav');


#
# display_credits()
#

function display_credits()
{
	if ( apply_filters('show_credits', true) )
	{
		ob_start('add_credit_div');
	}
#	echo add_credit_div('</body>');
} # end display_credits()

add_action('template_redirect', 'display_credits');


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


#
# add_credit_div()
#

function add_credit_div($buffer)
{
	if ( !is_feed() && apply_filters('show_credits', true) )
	{
		add_filter('show_credits', 'false');

		$buffer = str_replace(
			'</body>',
			'<div id="credits" class="credits">Made with '
			. '<a href="http://wordpress.org">WordPress</a>'
			. ' and '
			. get_theme_description()
			. ' &bull; '
			. get_skin_credits()
			. '</div>'
			. '</body>',
			$buffer
			);
	}

	return $buffer;
} # end add_credit_div()


#
# display_extra_footer()
#

function display_extra_footer()
{
	$extra_footer = apply_filters('extra_footer', '');

	if ( $extra_footer )
	{
		echo '<div id="extra_footer" class="extra_footer">';
		echo $extra_footer;
		echo '</div>';
	}
} # end display_extra_footer()

add_action('wp_footer', 'display_extra_footer', 100);


#
# display_entry_footer()
#

function display_entry_footer()
{
	if ( is_singular() )
	{
		$post_ID = intval($GLOBALS['wp_query']->get_queried_object_id());
		$extra_footer = get_post_meta($post_ID, '_footer', true);
	}

	if ( $extra_footer )
	{
		echo '<div id="entry_footer" class="extra_footer">';
		echo $extra_footer;
		echo '</div>';
	}
} # end display_entry_footer()

add_action('wp_footer', 'display_entry_footer', 50);
?>