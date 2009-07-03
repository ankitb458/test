<?php
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
?>
</div>
</div><!-- #footer -->

<?php
} # end display_footer()

add_action('display_footer', 'display_footer');


#
# display_copyright_notice()
#

function display_copyright_notice()
{
	$copyright_notice = get_caption('copyright');

	$year = date('Y');

	$admin_user = get_userdatabylogin('admin');

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

	$copyright_notice = str_replace(
			array('%year%', '%admin_name%'),
			array($year, $admin_name),
			$copyright_notice
			);

?>
<div id="copyright_notice">
<?php echo $copyright_notice; ?>
</div><!-- #copyright_notice -->
<?php
} # end display_copyright_notice()

add_action('display_copyright_notice', 'display_copyright_notice');


#
# display_footer_nav()
#

function display_footer_nav()
{
?>
<div id="footer_nav" class="inline_menu">
<?php display_nav_menu('footer_nav', '|'); ?>
</div><!-- #footer_nav -->
<?php
} # end display_footer_nav()

add_action('display_footer_nav', 'display_footer_nav');


#
# display_credits()
#

function display_credits()
{
	ob_start('add_credit_div');
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
		$buffer = str_replace(
			'</body>',
			'<div id="credits">Made with '
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
?>