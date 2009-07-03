<?php
/*
Plugin Name: Wysiwyg Editor
Plugin URI: http://www.semiologic.com/software/wysiwyg-editor/
Description: <a href="http://www.semiologic.com/legal/license/">Terms of use</a> &bull; <a href="http://www.semiologic.com/software/wysiwyg-editor/">Doc/FAQ</a> &bull; <a href="http://forum.semiologic.com">Support forum</a> &#8212; A Wysiwyg Editor plugin that works.
Author: Denis de Bernardy and Mike Koepke
Version: 2.0
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/


Hat Tips
--------

	* Mike Koepke <http://mikekoepke.com>
**/


#
# sem_wysiwyg_kill_fckmonkey()
#

function sem_wysiwyg_kill_fckmonkey()
{
	if ( class_exists('fckmonkey') )
	{
		$active_plugins = (array) get_option('active_plugins');
		unset($active_plugins[array_search('fckmonkey/fckmonkey.php', $active_plugins)]);
		sort($active_plugins);
		update_option('active_plugins', $active_plugins);
	}
} # end sem_wysiwyg_kill_fckmonkey()

add_action('plugins_loaded', 'sem_wysiwyg_kill_fckmonkey');


#
# sem_wysiwyg_admin_header()
#

function sem_wysiwyg_admin_header()
{
	global $current_user;

	if ( !isset($current_user) )
	{
		#echo 'sem_wysiwyg_admin_header';
		#$current_user = get_currentuserinfo();
	}

	if ( use_fck )
	{
		?><script type="text/javascript" src="<?php echo trailingslashit(get_option('siteurl')) ?>wp-content/<?php echo function_exists('get_site_option') ? 'mu-plugins/' : 'plugins/'; ?>sem-wysiwyg/fckeditor/fckeditor.js"></script>
<style type="text/css">
#quicktags,
#uploading
{
	display: none;
}
</style>
		<?php
	}
} # end sem_wysiwyg_admin_header()

add_action('admin_head', 'sem_wysiwyg_admin_header');


#
# sem_wysiwyg_kill_tiny_mce()
#

function sem_wysiwyg_kill_tiny_mce($in)
{
	global $current_user;

	if ( isset($current_user)
		&& !defined('use_fck')
		&& strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false
		&& strpos($_SERVER['REQUEST_URI'], 'wp-admin/profile.php') === false
		&& ( 'true' == get_user_option('rich_editing') )
		&& !preg_match('!opera[ /][2-8]|konqueror|safari!i', $_SERVER['HTTP_USER_AGENT'])
		)
	{
		define('use_fck', true);
		$current_user->rich_editing = 'false';
	}
	elseif ( isset($current_user)
		&& !defined('use_fck')
		)
	{
		define('use_fck', false);
	}

	return $in;
} # sem_wysiwyg_kill_tiny_mce()

add_action('option_posts_per_page', 'sem_wysiwyg_kill_tiny_mce');


#
# sem_wysiwyg_unkill_tiny_mce()
#

function sem_wysiwyg_unkill_tiny_mce()
{
	global $current_user;

	if ( !isset($current_user) )
	{
		echo 'sem_wysiwyg_unkill_tiny_mce';
		#$current_user = get_currentuserinfo();
	}

	$current_user->rich_editing = 'true';
} # sem_wysiwyg_unkill_tiny_mce()


#
# sem_wysiwyg_load_editor()
#

function sem_wysiwyg_load_editor()
{
	global $current_user;

	if ( !isset($current_user) )
	{
		#echo 'sem_wysiwyg_load_editor';
		#$current_user = get_currentuserinfo();
	}

	if ( use_fck )
	{
		$editor_size = 20 * intval(get_option('default_post_edit_rows'));

		if ( $editor_size < 240 )
		{
			$editor_size = 240;
		}

		?><script type="text/javascript">
// Firefox doesn't always catch onload. We use an image instead.

function initFCK()
{
	if ( document.getElementById('content') )
	{
		var oFCKeditor = new FCKeditor(
				'content',
				'100%',
				<?php echo $editor_size; ?>				);

		// Basic stuff
		oFCKeditor.BasePath = "<?php echo trailingslashit(get_option('siteurl')) ?>wp-content/<?php echo function_exists('get_site_option') ? 'mu-plugins/' : 'plugins/'; ?>sem-wysiwyg/fckeditor/";
		oFCKeditor.Config["BaseHref"] = "<?php echo trailingslashit(get_settings('siteurl')); ?>";

		// Interface
		oFCKeditor.ToolbarSet = "Default";
		oFCKeditor.Config["SkinPath"] = oFCKeditor.BasePath + 'editor/skins/default/';

		oFCKeditor.ReplaceTextarea();
	}
}
</script>
<img src="<?php echo trailingslashit(get_option('siteurl')) ?>wp-content/<?php echo function_exists('get_site_option') ? 'mu-plugins/' : 'plugins/'; ?>sem-wysiwyg/sem-wysiwyg.gif" alt="" onload="initFCK();" />
		<?php
	}
} # end sem_wysiwyg_load_editor()

add_action('admin_footer', 'sem_wysiwyg_load_editor');


#
# sem_add_fck_buttons()
#

function sem_add_fck_buttons()
{
	echo '<script type="text/javascript">' . "\n";

	echo 'document.show_fck_contactform = ' . ( function_exists('wpcf_callback') ? 'true' : 'false' ) . ';' . "\n";
	echo 'document.show_fck_newsletter = ' . ( function_exists('the_newsletter_tag') ? 'true' : 'false' ) . ';' . "\n";
	echo 'document.show_fck_podcast = ' . ( function_exists('ap_insert_player_widgets') ? 'true' : 'false' ) . ';' . "\n";
	echo 'document.show_fck_videocast = ' . ( function_exists('wpflv_replace') ? 'true' : 'false' ) . ';' . "\n";
	echo 'document.show_fck_adunit = ' . ( ( function_exists('sem_ad_spaces_init') && ( !function_exists('get_site_option') || is_site_admin() ) ) ? 'true' : 'false' ) . ';' . "\n";

	echo '</script>' . "\n";
} # end sem_add_fck_buttons()

add_action('admin_head', 'sem_add_fck_buttons');


#
# sem_fck_tags()
#

function sem_fck_tags($content)
{
	#echo '<pre>';
	#var_dump(htmlspecialchars($content));
	#echo '</pre>';

	if ( function_exists('wpcf_callback') )
	{
		$content = preg_replace(
			"/
				(?:<br\s*\/>|\n|\r)*
				<!--\s*contactform\s*-->
				(?:<br\s*\/>|\n|\r)*
			/ix",
			"<!--contactform-->",
			$content
			);

		$content = preg_replace(
			"/
				(?:<p>)?
				<!--\s*contactform\s*-->
				(?:<\/p>)?
			/ix",
			"<!--contactform-->",
			$content
			);

		$content = str_replace(
			"<!--contactform-->",
			"\n\n<div>[CONTACT-FORM]</div>\n\n",
			$content
			);
	}

	if ( function_exists('ap_insert_player_widgets') )
	{
		$content = preg_replace(
			"/
				(?:<br\s*\/>|\n|\r)*
				<!--\s*podcast\s*(\#[^>]*)-->
				(?:<br\s*\/>|\n|\r)*
			/ix",
			"<!--podcast$1-->",
			$content
			);

		$content = preg_replace(
			"/
				(?:<p>)?
				<!--\s*podcast\s*(\#[^>]*)-->
				(?:<\/p>)?
			/ix",
			"<!--podcast$1-->",
			$content
			);

		$content = preg_replace(
			"/
				<!--\s*podcast\s*\#([^>]*)-->
			/ix",
			"\n\n<div>[audio:$1]</div>\n\n",
			$content
			);
	}

	if ( function_exists('wpflv_replace') )
	{
		$content = preg_replace(
			"/
				(?:<br\s*\/>|\n|\r)*
				<!--\s*videocast\s*(\#[^>]*)-->
				(?:<br\s*\/>|\n|\r)*
			/ix",
			"<!--videocast$1-->",
			$content
			);

		$content = preg_replace(
			"/
				(?:<p>)?
				<!--\s*videocast\s*(\#[^>]*)-->
				(?:<\/p>)?
			/ix",
			"<!--videocast$1-->",
			$content
			);

		$content = preg_replace_callback(
			"/
				<!--\s*videocast\s*\#([^>]*)-->
			/ix",
			'replace_videocast_tag',
			$content
			);
	}

	return $content;
} # end sem_fck_tags()

add_filter('the_content', 'sem_fck_tags', 0);


#
# replace_videocast_tag()
#

function replace_videocast_tag($input)
{
	$params = explode("#", $input[1]);

	$file = strip_tags($params[0]);
	$width = intval($params[1]);
	$height = intval($params[2]);

	if ( !$width || !$height )
	{
		$options = wpflv_get_options();
		$width = $width ? $width : $options['width'];
		$height = $height ? $height : $options['height'];
	}

	return "\n\n"
		. '<div>'
		. '<flv href="' . $file . '"'
		. ' width="' . $width . '"'
		. ' height="' . $height . '"'
		. ' />'
		. '</div>'
		. "\n\n";
} # end replace_videocast_tag()
?>