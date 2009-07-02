<?php
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
		<?php
	}

	if ( kill_uploader )
	{
?>
<style type="text/css">
#quicktags,
#uploading
{
	display: none;
}

#postdiv a
{
	margin-top: -12px;
	border-bottom: none;
	text-decoration: underline;
}
</style>
<?php
	}
} # end sem_wysiwyg_admin_header()

add_action('admin_head', 'sem_wysiwyg_admin_header');


#
# kill_manage_uploads
#

function kill_manage_uploads()
{
	if ( kill_uploader )
	{
		global $submenu;
		unset($submenu['edit.php'][12]);
	}
} # kill_manage_uploads()

add_action('_admin_menu', 'kill_manage_uploads');


#
# sem_wysiwyg_kill_tiny_mce()
#

function sem_wysiwyg_kill_tiny_mce($in)
{
	global $current_user;

	if ( !defined('use_fck') )
	{
		if ( $current_user->rich_editing == 'true'
			&& strpos($_SERVER['REQUEST_URI'], 'wp-admin/profile.php') === false
			&& !preg_match('/opera|konqueror|safari/i', $_SERVER['HTTP_USER_AGENT'])
			)
		{
			// disable wysiwyg editor when content was uploaded as a file
			if ( isset($_GET['post']) && get_post_meta($_GET['post'], '_kill_formatting', true) )
			{
				define('use_fck', false);
			}
			else
			{
				define('use_fck', true);
			}

			define('kill_uploader', true);

			$current_user->rich_editing = 'false';
		}
		else
		{
			define('use_fck', false);
			define('kill_uploader', false);
		}
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
		oFCKeditor.Config["BaseHref"] = "<?php echo trailingslashit(get_option('siteurl')); ?>";

		// Interface
		oFCKeditor.ToolbarSet = "Default";
		oFCKeditor.Config["SkinPath"] = oFCKeditor.BasePath + 'editor/skins/default/';

		oFCKeditor.ReplaceTextarea();
	}
}

initFCK();
</script>
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
	echo 'document.show_fck_newsletter = ' . ( class_exists('sem_newsletter_manager') ? 'true' : 'false' ) . ';' . "\n";
	echo 'document.show_fck_podcast = ' . ( function_exists('ap_insert_player_widgets') ? 'true' : 'false' ) . ';' . "\n";
	echo 'document.show_fck_videocast = ' . ( function_exists('wpflv_replace') ? 'true' : 'false' ) . ';' . "\n";
	echo 'document.show_fck_adunit = ' . ( function_exists('wp_ozh_wsa') ? 'true' : 'false' ) . ';' . "\n";
	echo 'document.show_fck_media = ' . ( class_exists('mediacaster') ? 'true' : 'false' ) . ';' . "\n";

	echo '</script>' . "\n";
} # end sem_add_fck_buttons()

add_action('admin_head', 'sem_add_fck_buttons');
?>