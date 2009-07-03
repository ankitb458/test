<?php
/*
Plugin Name: Adhesive
Plugin URI: http://www.asymptomatic.net/wp-hacks
Description: Allows easy marking of sticky posts in WordPress 2.0.  Licensed under the <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>, Copyright 2005 Owen Winkler.
Version: 3.2
Author: Owen Winkler
Author URI: http://www.asymptomatic.net
*/

/*
Adhesive - Allows easy marking of sticky posts in WordPress.
Copyright (c) 2005 Owen Winkler

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated
documentation files (the "Software"), to deal in the
Software without restriction, including without limitation
the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software,
and to permit persons to whom the Software is furnished to
do so, subject to the following conditions:

The above copyright notice and this permission notice shall
be included in all copies or substantial portions of the
Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY
KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS
OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR
OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

/*

INSTRUCTIONS:

Please see this web page for more information:
http://redalt.com/wiki/Adhesive

*/

if(!function_exists('adhesive_include_up')) {
	function adhesive_include_up($filename) {
		$c=0;
		while(!is_file($filename)) {
			$filename = '../' . $filename;
			$c++;
			if($c==30) {
				echo 'Could not find ' . basename($filename) . '.';
				return '';
			}
		}
		return $filename;
	}
}


// End Options---
if(!defined('ABSPATH')) {
	// Things to do when the file is called solo
	include(adhesive_include_up('wp-config.php'));
	$adhesive_options = get_settings('adhesive');
	if($adhesive_options == '') {
		$adhesive_options = array(
			'display_date' => false,
			'date_banner' => '<h1>Important Message!</h1>',
			'category_only' => false,
		);
		update_option('adhesive', $adhesive_options);
	}
	
	
	if(isset($_POST['config'])) {
		// Things to do when the Ajax gets ya
		include_once(ABSPATH.'/wp-admin/admin-functions.php');
		?>
		<div style="padding:5px;">
		<?php
		adhesive_options_form();
		?>		
		</div>
		<?php
	} else {
		// Things to do without Ajax
	}
	die();
};

// Things to do when the file is included as a plugin


function adhesive_basename($file) {
	return preg_replace('/^.*wp-content[\\\\\/]plugins[\\\\\/]/', '', $file);
}


// Don't change below here.

add_filter('the_posts', 'adhesive_the_posts');
add_filter('posts_where', 'adhesive_posts_where');
add_filter('admin_footer', 'adhesive_admin_footer');
add_filter('edit_post', 'adhesive_edit_post');
add_filter('publish_post', 'adhesive_edit_post');
add_filter('the_content', 'adhesive_the_content', 15);
add_filter('the_title', 'adhesive_the_title');
add_filter('admin_menu', 'adhesive_admin_menu');

// Delete the following line to display the date on sticky posts
add_filter('the_date', 'adhesive_the_date', 15);


$adhesive_metakey = 'sticky';

function adhesive_get_options($name = '') 
{
	$adhesive_options = get_settings('adhesive');
	if($adhesive_options == '') {
		$adhesive_options = array(
			'display_date' => false,
			'date_banner' => '<h1>Important Message!</h1>',
			'category_only' => false,
		);
		update_option('adhesive', $adhesive_options);
	}
	return $name == ''? $adhesive_options : $adhesive_options[$name];
}


function adhesive_posts_where($where)
{
	if(!adhesive_get_options('category_only') || is_category())
		$where = '  AND   0=1   ' . $where;
	return $where;
}

function adhesive_the_posts($posts)
{
	global $wpdb, $adhesive_metakey, $wp_query, $request, $q;

	if(!adhesive_get_options('category_only') || is_category())
	{
		$qry = "SELECT {$wpdb->postmeta}.Post_ID FROM {$wpdb->postmeta} WHERE meta_key = '{$adhesive_metakey}' AND meta_value = '1'";
		if($stickies = $wpdb->get_col($qry))
		{
			$insticky = 'not (ID in (' . implode(',', $stickies) . ')),';
		}
		else
		{
			$insticky = '';
			$stickies = array();
		}
		
		if($request == '') $request = $wp_query->request;

		$request = str_replace(' AND   0=1   ', '', $request);
		$wp_query->request = $request;
		$request = preg_replace("/ORDER BY post_{$q['orderby']}/", "ORDER BY {$insticky} post_{$q['orderby']}", $request);

		unset($posts);
		$posts = array();
		if($foo = $wpdb->get_results($request))
		{
			foreach($foo as $post)
			{
				$post->is_sticky = (in_array($post->ID, $stickies));
				array_push($posts, $post);
			}
		}
	}

	return $posts;
}

function adhesive_admin_footer($content)
{
	global $wpdb, $exc, $adhesive_metakey;

	// Are we on the right page?
	if(preg_match('|post.php|i', $_SERVER['REQUEST_URI']))
	{
		if(isset($_REQUEST['post']))
		{
			$stickiness = $wpdb->get_var("SELECT meta_value FROM {$wpdb->postmeta} WHERE Post_ID = {$_REQUEST['post']} AND meta_key = '{$adhesive_metakey}';");
			$checked = ('1' == $stickiness)?' checked="checked"':'';
		}
?>
<div id="adhesivediv"><label class="selectit"><input type="checkbox" name="adhesive_sticky" value="true"<?php echo $checked; ?> /> Sticky</label></div>
<?php

		echo '
		<script language="JavaScript" type="text/javascript"><!--
		var placement = document.getElementById("post_status_private");
		var substitution = document.getElementById("adhesivediv");
		var mozilla = document.getElementById&&!document.all;
		if(mozilla)
			placement.parentNode.parentNode.appendChild(substitution);
		else placement.parentElement.parentElement.appendChild(substitution);

		//--></script>
		';

	}
}

function adhesive_edit_post($id)
{
	global $wpdb, $adhesive_metakey;

	if(!isset($id)) $id= $_REQUEST['post_ID'];

	if(isset($_REQUEST['adhesive_sticky']))
	{
		$qry = "DELETE FROM {$wpdb->postmeta} WHERE Post_ID = {$id} AND meta_key = '{$adhesive_metakey}';";
		$wpdb->query($qry);
		$qry = "INSERT INTO {$wpdb->postmeta} (Post_ID, meta_key, meta_value) VALUES ({$id}, '{$adhesive_metakey}', '1');";
		$wpdb->query($qry);
	}
	else
	{
		$qry = "DELETE FROM {$wpdb->postmeta} WHERE Post_ID = {$id} AND meta_key = '{$adhesive_metakey}';";
		$wpdb->query($qry);
	}
}

function adhesive_the_content($content)
{
	global $post;
	return ($post->is_sticky) ?
		"<script type=\"text/javascript\">window.document.getElementById('post-{$post->ID}').parentNode.className += ' adhesive_post';</script>{$content}" :
		$content;
}

function adhesive_the_date($content)
{
	global $post, $previousday;
	if($post->is_sticky  && !adhesive_get_options('display_date'))
	{
		$previousday='';
		return adhesive_get_options('date_banner');
	}
	return $content;
}

function adhesive_the_title($content)
{
	global $post;
	return (preg_match('|edit.php|i', $_SERVER['REQUEST_URI']) && ($post->is_sticky)) ?
		"<strong>Sticky:</strong>{$content}" :
		$content;
}

/* The following functions handle the newfangled admin configuration system */

add_action('admin_head', 'adhesive_admin_head');

if(strstr($_SERVER['REQUEST_URI'], '/wp-admin/plugins.php')) {
	ob_start('adhesive_capture');
	if(isset($_GET['pfile']) && ($_GET['page'] == adhesive_basename(__FILE__))) {
		// Do something!
	}
}

function adhesive_capture($content) {
	$content = preg_replace_callback('/<a href=\'plugins\\.php\\?action=deactivate&amp;plugin=.*?' . basename(__FILE__) . '.*?Deactivate<\/a>/', 'adhesive_add_link', $content);
	return $content;
}

function adhesive_add_link($c) {
	$replacement = $c[0] . '<br/><a href="' . get_settings('siteurl') . '/wp-admin/plugins.php?page=' . basename(__FILE__) . '&config=1" class="edit" title="' . __('Configure this plugin') . '" onclick="return !adhesive_show_config();">' . __('Configure') . '</a>';
	return $replacement;
}

function adhesive_admin_head($unused) {
	if(preg_match('/(plugins.php)/i', $_SERVER['REQUEST_URI'])) 
	{
		?>
<script language="JavaScript" type="text/javascript"><!--
function adhesive_pageWidth() {return window.innerWidth != null? window.innerWidth: document.body != null? document.body.clientWidth:null;}
function adhesive_pageHeight() {return window.innerHeight != null? window.innerHeight: document.body != null? document.body.clientHeight:null;}
function adhesive_posLeft() {return typeof window.pageXOffset != 'undefined' ? window.pageXOffset:document.documentElement.scrollLeft? document.documentElement.scrollLeft:document.body.scrollLeft? document.body.scrollLeft:0;}
function adhesive_posTop() {return typeof window.pageYOffset != 'undefined' ? window.pageYOffset:document.documentElement.scrollTop? document.documentElement.scrollTop: document.body.scrollTop?document.body.scrollTop:0;}

addLoadEvent(function () {
	adhesivepod = document.createElement("div");
	adhesivepod.style.position = "absolute";
	adhesivepod.id = "adhesivepod";
	adhesivepod.style.backgroundColor = "#ffffff";
	adhesivepod.style.border = "1px solid #14568a";
	adhesivepod.style.width = "50%";
	//adhesivepod.style.height = "300px";
	adhesivepod.style.display = "none";
	document.getElementsByTagName('body')[0].appendChild(adhesivepod);
});

function adhesive_show_config() {
	adhesivepod.style.top = (adhesive_posTop() + 50) + "px";
	adhesivepod.style.left = "25%";
	adhesivepod.style.display = "block";
	var ajax = new sack();
	ajax.requestFile = "<?php echo get_settings('siteurl'); ?>/wp-content/plugins/<?php echo str_replace('\\', '/', adhesive_basename(__FILE__)); ?>";
	ajax.element = 'adhesivepod';
	ajax.setVar('config', '1');
	ajax.runAJAX();
	return true;
}

function adhesive_config_update() {
	var ajax = new sack();
	ajax.requestFile = "<?php echo get_settings('siteurl'); ?>/wp-content/plugins/<?php echo str_replace('\\', '/', adhesive_basename(__FILE__)); ?>";
	ajax.element = 'adhesivepod';
	var inp = document.getElementById('adhesiveconfig').getElementsByTagName('input');
	for(z=0;z<inp.length;z++) {
		if((inp[z].type != 'checkbox') || inp[z].checked) 
			ajax.setVar(inp[z].name, inp[z].value);
	}
	ajax.onCompletion = function() {window.setTimeout("Fat.fade_element('adhesiveupdatenotice');",10);}
	ajax.runAJAX();
	
	return true;
}
//--></script>
		<?php
	}
}

function adhesive_admin_menu() {
	if($_GET['page'] == basename(__FILE__)) {
		add_submenu_page('plugins.php', 'Configure Adhesive', 'Adhesive', 'activate_plugins', basename(__FILE__), 'adhesive_config');
	}
}

function adhesive_config() {
?>
		<script type="text/javascript">function adhesive_config_update() {return false;}</script>
		<div class="wrap">
<?php
	adhesive_options_form();
?>
		</div>
<?php
}

function adhesive_options_form() {
?>
		<h2>Configure Adhesive</h2>
<?php

	$adhesive_options = adhesive_get_options();

	if($_POST['config'] == 2) {
		echo "<div class=\"updated fade\" id=\"adhesiveupdatenotice\">" . __("Configuration <strong>updated</strong>.") . "</div>";
		$adhesive_options = array(
			'display_date' => isset($_POST['display_date']),
			'date_banner' => $_POST['date_banner'],
			'category_only' => isset($_POST['category_only']),
		);
		update_option('adhesive', $adhesive_options);
	}
?>
		<form onsubmit="return !adhesive_config_update();" id="adhesiveconfig" method="post">
			<table style="width:100%;">
				<tr>
					<td style="width:60%;padding-top:10px;text-align:right;"><label for="category_only" style="margin-top:5px;"><?php _e('Categories Only'); ?>:</label><div style="font-size:xx-small;">Causes Adhesive to display sticky posts only when viewing categories, and not also on the home page.</div></td>
					<td><input type="checkbox" name="category_only" value="1" <?php if($adhesive_options['category_only']) echo 'checked="checked"'; ?>/></td>
				</tr>
				<tr>
					<td style="width:60%;padding-top:10px;text-align:right;"><label for="display_date" style="margin-top:5px;"><?php _e('Display Date'); ?>:</label><div style="font-size:xx-small;">Causes Adhesive to display the date instead of the date banner on sticky posts.</div></td>
					<td><input type="checkbox" name="display_date" value="1" <?php if($adhesive_options['display_date']) echo 'checked="checked"'; ?>/></td>
				</tr>
				<tr>
					<td style="padding-top:10px;text-align:right;"><label for="date_banner"><?php _e('Date Banner'); ?>:</label><div style="font-size:xx-small;">This banner is displayed instead of the date in themes that display the date for each post.</div></td>
					<td><input type="text" name="date_banner" style="width:90%;" value="<?php echo htmlentities($adhesive_options['date_banner']); ?>"/></td>
				</tr>
				<tr>
					<td colspan="2" style="text-align:right">
						<input type="Submit" name="Submit" value="Update" />
						<?php if(!isset($_GET['page'])) { ?>
						<input type="button" name="Close" value="Close" onclick="adhesivepod.innerHTML='';adhesivepod.style.display='none';" />
						<?php } ?>
					</td>
				</tr>
			</table>
			<input type="hidden" name="config" value="2" />
		</form>
<?php
}

?>