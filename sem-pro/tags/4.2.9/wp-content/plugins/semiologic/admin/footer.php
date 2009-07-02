<?php

#
# update_theme_footer()
#

function update_theme_footer()
{
	#echo '<pre>';
	#var_dump($_POST);
	#echo '</pre>';

	if ( function_exists('get_site_option') )
	{
		if ( is_site_admin() )
		{
			$semiologic = get_site_option('semiologic');
			$semiologic['extra_footer'] = stripslashes($_POST['extra_footer']);
			if ( !current_user_can('unfiltered_html') )
			{
				$semiologic['extra_footer'] = stripslashes(wp_filter_post_kses($semiologic['extra_footer']));
			}
			$semiologic['theme_credits'] = isset($_POST['show_credits']);
			update_site_option('semiologic', $semiologic);
		}
	}
	else
	{
		global $semiologic;
		$semiologic['extra_footer'] = stripslashes($_POST['extra_footer']);
		if ( !current_user_can('unfiltered_html') )
		{
			$semiologic['extra_footer'] = stripslashes(wp_filter_post_kses($semiologic['extra_footer']));
		}
		$semiologic['theme_credits'] = isset($_POST['show_credits']);
		update_option('semiologic', $semiologic);
	}
} # end update_theme_footer()

add_action('update_theme_footer', 'update_theme_footer');
?>