<?php
/*
Plugin Name: Wysiwyg Editor
Plugin URI: http://www.semiologic.com/software/wysiwyg-editor/
Description: <a href="http://www.semiologic.com/legal/license/">Terms of use</a> &bull; <a href="http://www.semiologic.com/software/wysiwyg-editor/">Doc/FAQ</a> &bull; <a href="http://forum.semiologic.com">Support forum</a> &#8212; A Wysiwyg Editor plugin that works.
Author: Denis de Bernardy
Version: 1.0.1
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/


Hat Tips
--------

	* Mike Keopke <http://mikekoepke.com>
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

	if ( $current_user->use_fck )
	{
		?>
<script type="text/javascript" src="<?php echo trailingslashit(get_option('siteurl')) ?>wp-content/plugins/sem-wysiwyg/fckeditor/fckeditor.js"></script>
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

function sem_wysiwyg_kill_tiny_mce()
{
	global $current_user;
	$current_user->use_fck = false;

	if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false
		&& strpos($_SERVER['REQUEST_URI'], 'wp-admin/profile.php') === false
		&& ( 'true' == get_user_option('rich_editing') )
		&& !preg_match('!opera[ /][2-8]|konqueror|safari!i', $_SERVER['HTTP_USER_AGENT'])
		)
	{
		$current_user->rich_editing = 'false';
		$current_user->use_fck = true;
	}
} # sem_wysiwyg_kill_tiny_mce()

add_action('init', 'sem_wysiwyg_kill_tiny_mce');


#
# sem_wysiwyg_unkill_tiny_mce()
#

function sem_wysiwyg_unkill_tiny_mce()
{
	global $current_user;

	$current_user->rich_editing = 'true';
} # sem_wysiwyg_unkill_tiny_mce()


#
# sem_wysiwyg_load_editor()
#

function sem_wysiwyg_load_editor()
{
	global $current_user;

	if ( $current_user->use_fck )
	{
		$editor_size = 20 * intval(get_option('default_post_edit_rows'));

		if ( $editor_size < 240 )
		{
			$editor_size = 240;
		}

		?>
<script type="text/javascript">
// Firefox doesn't always catch onload. We use an image instead.

function initFCK()
{
	if ( document.getElementById('content') )
	{
		var oFCKeditor = new FCKeditor(
				'content',
				'100%',
				<?php echo $editor_size; ?>
				);

		// Basic stuff
		oFCKeditor.BasePath = "<?php echo trailingslashit(get_option('siteurl')) ?>wp-content/plugins/sem-wysiwyg/fckeditor/";
		oFCKeditor.Config["BaseHref"] = "<?php echo trailingslashit(get_settings('siteurl')); ?>";

		// Interface
		oFCKeditor.ToolbarSet = "Default";
		oFCKeditor.Config["SkinPath"] = oFCKeditor.BasePath + 'editor/skins/default/';

		oFCKeditor.ReplaceTextarea();
	}
}
</script>
<img src="<?php echo trailingslashit(get_option('siteurl')) ?>wp-content/plugins/sem-wysiwyg/sem-wysiwyg.gif" onload="initFCK();" />
		<?php
	}
} # end sem_wysiwyg_load_editor()

add_action('admin_footer', 'sem_wysiwyg_load_editor');
?>