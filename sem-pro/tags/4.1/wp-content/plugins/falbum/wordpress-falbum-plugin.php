<?php /*
Plugin Name: FAlbum
Version: 0.6.6
Plugin URI: http://www.randombyte.net/
Description: A plugin for displaying your <a href="http://www.flickr.com/">Flickr</a> photosets and photos in a gallery format on your Wordpress site.
Author: Elijah Cornell
Author URI: http://www.randombyte.net/

Copyright (c) 2006
Released under the GPL license
http://www.gnu.org/licenses/gpl.txt

This file is part of WordPress.
WordPress is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

$falbum_options = null;

// plugin menu
function falbum_add_pages() {
	if (function_exists('add_options_page')) {
		add_submenu_page('options-general.php', 'FAlbum', 'FAlbum', 8, basename(__FILE__), 'falbum_options_page');
	}
}

function falbum_init() {
	global $wpdb, $table_prefix, $user_level;
	$fa_table = $table_prefix."falbum_cache";
	get_currentuserinfo();
	if ($user_level < 8) {
		return;
	}

	if ($wpdb->get_var("show tables like '$fa_table'") != $fa_table) {
		$sql = "CREATE TABLE ".$fa_table." (
											ID varchar(40) PRIMARY KEY,
											data text,
											expires datetime
											)";
		require_once (ABSPATH.'wp-admin/upgrade-functions.php');
		dbDelta($sql);
	}
}

function falbum_options_page() {

	global $falbum_options;
	$options = $falbum_options;

	require_once (dirname(__FILE__).'/wp/FAlbum_WP.class.php');

	$falbum = new FAlbum_WP();

	global $is_apache, $wpdb, $table_prefix;
	$fa_table = $table_prefix."falbum_cache";

	$ver = $options['version'];
	if ($ver != FALBUM_VERSION) {
		falbum_init();
	}

	// Setup htaccess 
	$urlinfo = parse_url(get_settings('siteurl'));
	$path = $urlinfo['path'];

	$furl = trailingslashit($options['url_root']);
	if ($furl {
		0 }
	== "/") {
		$furl = substr($furl, 1);
	}
	if (strpos('/'.$furl, $path.'/') === false) {
		$home_path = parse_url("/");
		$home_path = $home_path['path'];
		$root2 = str_replace($_SERVER["PHP_SELF"], '', $_SERVER["SCRIPT_FILENAME"]);
		$home_path = trailingslashit($root2.$home_path);
	} else {
		$furl = str_replace($path.'/', '', '/'.$furl);
		$home_path = get_home_path();
	}
	if ($furl {
		0 }
	== "/") {
		$furl = substr($furl, 1);
	}
	if ((!file_exists($home_path.'.htaccess') && is_writable($home_path)) || is_writable($home_path.'.htaccess')) {
		$writable = true;
	} else {
		$writable = false;
	}

	$rewriteRule = "<IfModule mod_rewrite.c>\n"."RewriteEngine On\n"."RewriteRule ^".$furl."?([^/]*)?/?([^/]*)?/?([^/]*)?/?([^/]*)?/?([^/]*)?/?([^/]*)?/?([^/]*)?/?([^/]*)?/?$ ".$path."/wp-content/plugins/falbum/wp/album.php?$1=$2&$3=$4&$5=$6&$7=$8 [QSA,L]\n"."</IfModule>";

	//echo '<pre>$path-'.$path.'/'.'</pre>';
	//echo '<pre>$furl-'.'/'.$furl.'</pre>';
	//echo '<pre>1-'.strpos('/'.$furl, $path.'/').'</pre>';
	//echo '<pre>$furl-'.$furl.'</pre>';
	//echo '<pre>'.$rewriteRule.'</pre>';

	// posting logic
	if (isset ($_POST['Submit'])) {

		$options['tsize'] = $_POST['tsize'];
		$options['show_private'] = $_POST['show_private'];
		$options['friendly_urls'] = $_POST['friendly_urls'];
		$options['url_root'] = $_POST['url_root'];
		$options['albums_per_page'] = $_POST['albums_per_page'];
		$options['photos_per_page'] = $_POST['photos_per_page'];
		$options['max_photo_width'] = $_POST['max_photo_width'];
		$options['display_dropshadows'] = $_POST['display_dropshadows'];
		$options['display_sizes'] = $_POST['display_sizes'];
		$options['display_exif'] = $_POST['display_exif'];
		$options['view_private_level'] = $_POST['view_private_level'];
		$options['number_recent'] = $_POST['number_recent'];
		$options['can_edit_level'] = $_POST['can_edit_level'];
		$options['style'] = $_POST['style'];

		$options['wp_enable_falbum_globally'] = $_POST['wp_enable_falbum_globally'];

		$furl = $options['url_root'];
		$pos = strpos($furl, '/');
		if ($furl {
			0 }
		!= "/") {
			$furl = '/'.$furl;
		}
		$pos = strpos($furl, '.php');
		if ($pos === false) {
			$furl = trailingslashit($furl);
		}

		$options['url_root'] = $furl;

		update_option('falbum_options', $options);

		$updateMessage .= __('Options saved', FALBUM_DOMAIN)."<br /><br />";

		if ($options['friendly_urls'] != 'false') {

			if ($is_apache) {

				$urlinfo = parse_url(get_settings('siteurl'));
				$path = $urlinfo['path'];

				$furl = trailingslashit($options['url_root']);
				if ($furl {
					0 }
				== "/") {
					$furl = substr($furl, 1);
				}

				//echo '<pre>$path-'.$path.'/'.'</pre>';
				//echo '<pre>$furl-'.'/'.$furl.'</pre>';
				//echo '<pre>1-'.strpos('/'.$furl, $path.'/').'</pre>';

				$pos = strpos('/'.$furl, $path.'/');

				if ($path != '/' && strpos('/'.$furl, $path.'/') === false) {
					//use root .htaccess file
					//echo '<pre>root</pre>';
					$home_path = parse_url("/");
					$home_path = $home_path['path'];
					$root2 = str_replace($_SERVER["PHP_SELF"], '', $_SERVER["SCRIPT_FILENAME"]);
					$home_path = trailingslashit($root2.$home_path);
				} else {
					//use wp .htaccess file
					//echo '<pre>wp</pre>';
					if (strlen($path) > 1) {
						$furl = str_replace($path.'/', '', '/'.$furl);
					}
					$home_path = get_home_path();
				}
				if ((!file_exists($home_path.'.htaccess') && is_writable($home_path)) || is_writable($home_path.'.htaccess')) {
					$writable = true;
				} else {
					$writable = false;
				}
				if ($furl {
					0 }
				== "/") {
					$furl = substr($furl, 1);
				}

				$rewriteRule = "<IfModule mod_rewrite.c>\n"."RewriteEngine On\n"."RewriteRule ^".$furl."?([^/]*)?/?([^/]*)?/?([^/]*)?/?([^/]*)?/?([^/]*)?/?([^/]*)?/?([^/]*)?/?([^/]*)?/?$ ".$path."/wp-content/plugins/falbum/wp/album.php?$1=$2&$3=$4&$5=$6&$7=$8 [QSA,L]\n"."</IfModule>";

				//echo '<pre>'.$rewriteRule.'</pre>';

				if ($writable) {
					$rules = explode("\n", $rewriteRule);
					falbum_insert_with_markers($home_path.'.htaccess', 'FAlbum', $rules);
					$updateMessage .= __('Mod rewrite rules updated', FALBUM_DOMAIN)."<br /><br />";
				}
			}

		} else {
			if ($writable) {
				falbum_insert_with_markers($home_path.'.htaccess', 'FAlbum', explode("\n", ""));
			}
		}

		$wpdb->query("DELETE from ".$fa_table."");
		$updateMessage .= __('Cache cleared', FALBUM_DOMAIN)."<br />";

	}

	if (isset ($_POST['ClearToken'])) {
		$options['token'] = null;
		update_option('falbum_options', $options);
		$updateMessage .= __('Flickr authorization reset', FALBUM_DOMAIN)."<br />";
	}

	if (isset ($_POST['ClearCache'])) {
		$falbum->_clear_cached_data();
		$updateMessage .= __('Cache cleared', FALBUM_DOMAIN)."<br />";
	}

	if (isset ($_POST['GetToken'])) {
		$frob2 = $_POST['frob'];
		$url = 'http://flickr.com/services/rest/?method=flickr.auth.getToken&api_key='.FALBUM_API_KEY.'&frob='.$frob2;
		$parms = 'api_key'.FALBUM_API_KEY.'frob'.$frob2.'methodflickr.auth.getToken';
		$url = $url.'&api_sig='.md5(FALBUM_SECRET.$parms);

		//echo '<pre>'.htmlentities($url).'</pre>';

		$resp = $falbum->_fopen_url($url, 0);
		$xpath = $falbum->_parse_xpath($resp);

		//echo '<pre>'.htmlentities($resp).'</pre>';
		//echo '<pre>'.htmlentities($status).'</pre>';

		if (is_object($xpath)) {
			//echo '<pre>'.htmlentities($resp).'</pre>';
			$token = $xpath->getData("/rsp/auth/token");
			$nsid = $xpath->getData("/rsp/auth/user/@nsid");

			$options['token'] = $token;
			$options['nsid'] = $nsid;

			update_option('falbum_options', $options);

			$updateMessage .= __('Successfully set token', FALBUM_DOMAIN)."<br />";
		} else {
			$updateMessage .= __('You have not Authorized Falbum. Please perform Step 1.', FALBUM_DOMAIN);
			$updateMessage .= "<br /><br />Flickr message: $xpath";
		}
	}

	if (isset ($updateMessage)) {
?> <div class="updated"><p><strong><?php echo $updateMessage?></strong></p></div> <?php


	}

	//Init Settings
	if (!isset ($options['tsize']) || $options['tsize'] == "") {
		$options['tsize'] = "t";
	}
	if (!isset ($options['show_private']) || $options['show_private'] == "") {
		$options['show_private'] = "false";
	}
	if (!isset ($options['friendly_urls']) || $options['friendly_urls'] == "") {
		$options['friendly_urls'] = "false";
	}
	if (!isset ($options['url_root']) || $options['url_root'] == "") {
		$options['url_root'] = $path."/wp-content/plugins/falbum/falbum-wp.php";
	}
	if (!isset ($options['albums_per_page']) || $options['albums_per_page'] == "") {
		$options['albums_per_page'] = "5";
	}
	if (!isset ($options['photos_per_page']) || $options['photos_per_page'] == "") {
		$options['photos_per_page'] = "20";
	}
	if (!isset ($options['max_photo_width']) || $options['max_photo_width'] == "") {
		$options['max_photo_width'] = "500";
	}
	if (!isset ($options['display_dropshadows']) || $options['display_dropshadows'] == "") {
		$options['display_dropshadows'] = "-nods";
	}
	if (!isset ($options['display_sizes']) || $options['display_sizes'] == "") {
		$options['display_sizes'] = "false";
	}
	if (!isset ($options['display_exif']) || $options['display_exif'] == "") {
		$options['display_exif'] = "false";
	}
	if (!isset ($options['view_private_level']) || $options['view_private_level'] == "") {
		$options['view_private_level'] = "10";
	}
	if (!isset ($options['number_recent']) || $options['number_recent'] == "") {
		$options['number_recent'] = "-1";
	}
	if (!isset ($options['can_edit_level']) || $options['can_edit_level'] == "") {
		$options['can_edit_level'] = "10";
	}
	if (!isset ($options['wp_enable_falbum_globally']) || $options['wp_enable_falbum_globally'] == "") {
		$options['wp_enable_falbum_globally'] = "true";
	}
	if (!isset ($options['style']) || $options['style'] == "") {
		$options['style'] = "default";
	}
?>


<div class="wrap">
<?php


	//echo '<pre>data-'.htmlentities($options['token']).'</pre>';
	//echo '<pre>data-'.htmlentities($options['nsid']).'</pre>';
?>

  <h2><?php _e('FAlbum Options', FALBUM_DOMAIN);?></h2>
    <form method=post action="<?php echo $_SERVER['PHP_SELF']; ?>?page=wordpress-falbum-plugin.php">
        <input type="hidden" name="update" value="true">
                       
        <?php if (!isset($options['token']) || $options['token'] == '' ) { ?>

       <fieldset class="options">
       <legend><?php _e('Initial Setup', FALBUM_DOMAIN);?></legend>
        
               <?php


	$url = 'http://flickr.com/services/rest/?method=flickr.auth.getFrob&api_key='.FALBUM_API_KEY;
	$parms = 'api_key'.FALBUM_API_KEY.'methodflickr.auth.getFrob';
	$url = $url.'&api_sig='.md5(FALBUM_SECRET.$parms);
	//echo '<pre>$url-'.htmlentities($url).'</pre>';

	$resp = $falbum->_fopen_url($url, 0);
	$xpath = $falbum->_parse_xpath($resp);

	//echo "Status: $status";
	//echo '<pre>$resp-'.htmlentities($resp).'</pre>';

	if (is_object($xpath)) {

		$frob = $xpath->getData("/rsp/frob");

		//echo '<pre>$frob-'.htmlentities($frob).'</pre>';

		$link = 'http://flickr.com/services/auth/?api_key='.FALBUM_API_KEY.'&frob='.$frob.'&perms=write';
		$parms = 'api_key'.FALBUM_API_KEY.'frob'.$frob.'permswrite';
		$link .= '&api_sig='.md5(FALBUM_SECRET.$parms);
?>
       
		       <input type="hidden" name="frob" value="<?php echo $frob?>">
		       <p>	      
		       <?php _e('Please complete the following step to allow FAlbum to access your Flickr photos.', FALBUM_DOMAIN);?>
		       </p>
       
		       <p>
		       <?php _e('Step 1:', FALBUM_DOMAIN);?> <a href="<?php echo $link?>" target="_blank"><?php _e('Authorize FAlbum with access your Flickr account', FALBUM_DOMAIN);?></a>
		       </p>
       	       	
       	 
		       <p>
		       <?php _e('Step 2:', FALBUM_DOMAIN);?> <input type="submit" name="GetToken" value="<?php _e('Get Authentication Token', FALBUM_DOMAIN);?>" />
		       </p>
	       <?php


	} else {
		echo "<p>Error: $xpath </p>";
	}
?>

       	
                       
      </fieldset>
      
      	<?php } else { ?>
      
      
		<fieldset class="options">
		<legend><?php _e('FAlbum Admin', FALBUM_DOMAIN);?></legend>
         
		<p>
		<input type="submit" name="ClearCache" value="<?php _e('Clear Cache', FALBUM_DOMAIN);?>" />
		&nbsp;&nbsp;&nbsp;
         
		<?php if (isset($options['token'])) { ?>
			<input type="submit" name="ClearToken" value="<?php _e('Reset Flickr Authorization', FALBUM_DOMAIN);?>" />
		<?php } ?>
         
		</p>
		</fieldset>
       
		<hr />
       
		<fieldset class="options">
		<legend><?php _e('FAlbum Configuration', FALBUM_DOMAIN);?></legend>
		<table width="100%" cellspacing="2" cellpadding="5" class="editform">

            	<tr valign="top">
                    <th width="33%" scope="row"><?php _e('Enable globally', FALBUM_DOMAIN);?>:</th>
                    <td>
                    <select name="wp_enable_falbum_globally">
                    <option value="true"<?php if ($options['wp_enable_falbum_globally'] == 'true') { ?> selected="selected"<?php } ?> ><?php _e('true', FALBUM_DOMAIN);?></option>
                    <option value="false"<?php if ($options['wp_enable_falbum_globally'] == 'false') { ?> selected="selected"<?php } ?> ><?php _e('false', FALBUM_DOMAIN);?></option>
                    </select>
                    <br />
                    <?php _e('Enables FAlbum methods to be used in any WordPress page (ex. sidebar.php).', FALBUM_DOMAIN);?></td>
                </tr>
                
                <tr valign="top">
                    <th width="33%" scope="row"><?php _e('Selected style', FALBUM_DOMAIN);?>:</th>
                    <td>
                    <select name="style">
                    
                    <?php


	$d = dir(dirname(__FILE__)."/styles");
	while (false !== ($entry = $d->read())) {
		if (strstr($entry, '.') != $entry) {
?>
					   		<option value="default"<?php if ($options['style'] == $entry) { ?> selected="selected"<?php } ?> ><?php echo $entry?></option>
                    <?php


		}
	}
	$d->close();
?> 
                                                            
                    </select>
                    <br />
                    <?php _e('Select the current style.', FALBUM_DOMAIN);?></td>
                </tr>            	
            	
            	<tr valign="top">            	
            	    <th width="33%" scope="row"><?php _e('Thumbnail Size', FALBUM_DOMAIN);?>:</th>
                    <td>
		    
                    <select name="tsize">
                    <option value="s"<?php if ($options['tsize'] == 's') { ?> selected="selected"<?php } ?> ><?php _e('Square', FALBUM_DOMAIN);?> (75px x 75px)</option>
                    <option value="t"<?php if ($options['tsize'] == 't') { ?> selected="selected"<?php } ?> ><?php _e('Thumbnail', FALBUM_DOMAIN);?> (100px x 75px)</option>
                    <option value="m"<?php if ($options['tsize'] == 'm') { ?> selected="selected"<?php } ?> ><?php _e('Small', FALBUM_DOMAIN);?> (240px x 180px)</option>
                    </select><br />
                    <?php _e('Size of the thumbnail you want to appear in the album thumbnail page', FALBUM_DOMAIN);?><br /></td>
                </tr>
                
                <tr valign="top">
                    <th width="33%" scope="row"><?php _e('Albums Per Page', FALBUM_DOMAIN);?>:</th>
                    <td><input type="text" name="albums_per_page" size="3" value="<?php echo $options['albums_per_page'] ?>"/><br />
                   <?php _e('How many albums to show on a page (0 for no paging)', FALBUM_DOMAIN);?></td>
                </tr>
                <tr valign="top">
                    <th width="33%" scope="row"><?php _e('Photos Per Page', FALBUM_DOMAIN);?>:</th>
                    <td><input type="text" name="photos_per_page" size="3" value="<?php echo $options['photos_per_page'] ?>"/><br />
                   <?php _e('How many photos to show on a page (0 for no paging)', FALBUM_DOMAIN);?></td>
                </tr>
                <tr valign="top">
                    <th width="33%" scope="row"><?php _e('Recent Images', FALBUM_DOMAIN);?>:</th>
                    <td><input type="text" name="number_recent" size="3" value="<?php echo $options['number_recent'] ?>"/><br />
                   <?php _e('How many of the most recent photos to show (0 for no recent images / -1 to show all available images)', FALBUM_DOMAIN);?></td>
                </tr>
				
		<tr valign="top">
                    <th width="33%" scope="row"><?php _e('Max Photo Width', FALBUM_DOMAIN);?>:</th>
                    <td><input type="text" name="max_photo_width" size="3" value="<?php echo $options['max_photo_width'] ?>"/><br />
                   <?php _e('Maximum photo width in pixels (0 for no resizing).  The default size of the images returned from Flickr is 500 pixels.', FALBUM_DOMAIN);?></td>
                </tr>  
                
                 <tr valign="top">
                    <th width="33%" scope="row"><?php _e('Display Drop Shadows', FALBUM_DOMAIN);?>:</th>
                    <td>
                    <select name="display_dropshadows">
                    <option value="-ds"<?php if ($options['display_dropshadows'] == '-ds') { ?> selected="selected"<?php } ?> ><?php _e('true', FALBUM_DOMAIN);?></option>
                    <option value="-nods"<?php if ($options['display_dropshadows'] == '-nods') { ?> selected="selected"<?php } ?> ><?php _e('false', FALBUM_DOMAIN);?></option>
                    </select>
                    <br />
                    <?php _e('Whether or not to show drop shadows under photos', FALBUM_DOMAIN);?></td>
                </tr>
               
                <tr valign="top">
                    <th width="33%" scope="row"><?php _e('Display Photo Sizes', FALBUM_DOMAIN);?>:</th>
                    <td>
                    <select name="display_sizes">
                    <option value="true"<?php if ($options['display_sizes'] == 'true') { ?> selected="selected"<?php } ?> ><?php _e('true', FALBUM_DOMAIN);?></option>
                    <option value="false"<?php if ($options['display_sizes'] == 'false') { ?> selected="selected"<?php } ?> ><?php _e('false', FALBUM_DOMAIN);?></option>
                    </select>
                    <br />
                    <?php _e('Whether or not to show photo sizes links', FALBUM_DOMAIN);?></td>
                </tr>
                
                <tr valign="top">
                    <th width="33%" scope="row"><?php _e('Display EXIF Data', FALBUM_DOMAIN);?>:</th>
                    <td>
                    <select name="display_exif">
                   <option value="true"<?php if ($options['display_exif'] == 'true') { ?> selected="selected"<?php } ?> ><?php _e('true', FALBUM_DOMAIN);?></option>
                    <option value="false"<?php if ($options['display_exif'] == 'false') { ?> selected="selected"<?php } ?> ><?php _e('false', FALBUM_DOMAIN);?></option>
                     </select>
                    <br />
                    <?php _e('Whether or not to show EXIF link', FALBUM_DOMAIN);?></td>
                </tr>
             
                <tr valign="top">
                    <th width="33%" scope="row"><?php _e('Show Private', FALBUM_DOMAIN);?>:</th>
                    <td>
                    <select name="show_private">
                    <option value="true"<?php if ($options['show_private'] == 'true') { ?> selected="selected"<?php } ?> ><?php _e('true', FALBUM_DOMAIN);?></option>
                    <option value="false"<?php if ($options['show_private'] == 'false') { ?> selected="selected"<?php } ?> ><?php _e('false', FALBUM_DOMAIN);?></option>
                    </select>
                    <br />
                    <?php _e('Whether or not to show your "private" Flickr photos', FALBUM_DOMAIN);?></td>
                </tr>
                
                <tr valign="top">
                    <th width="33%" scope="row"><?php _e('View Private Level', FALBUM_DOMAIN);?>:</th>
                    <td><input type="text" name="view_private_level" size="3" value="<?php echo $options['view_private_level'] ?>"/>
                    <br />
                    <?php _e('Set the Wordpress user level that is allowed to view "private" Flickr photos if "Show Private" is true. <br /> (0 to allow anonymous users)', FALBUM_DOMAIN);?></td>
                </tr>
                
                <tr valign="top">
                    <th width="33%" scope="row"><?php _e('Can Edit Level', FALBUM_DOMAIN);?>:</th>
                    <td><input type="text" name="can_edit_level" size="3" value="<?php echo $options['can_edit_level'] ?>"/>
                    <br />
                    <?php _e('Set the Wordpress user level that is allowed to edit album and photo meta data.', FALBUM_DOMAIN);?></td>
                </tr>
                                              
                
                <tr valign="top">
                    <th width="33%" scope="row"><?php _e('Use Friendly URLS', FALBUM_DOMAIN);?>:</th>
                    <td>
                    <select name="friendly_urls">
                    <option value="title"<?php if ($options['friendly_urls'] == 'title') { ?> selected="selected"<?php } ?> ><?php _e('title', FALBUM_DOMAIN);?></option>
                    <option value="numeric"<?php if ($options['friendly_urls'] == 'numeric') { ?> selected="selected"<?php } ?> ><?php _e('numeric', FALBUM_DOMAIN);?></option>
                    <option value="false"<?php if ($options['friendly_urls'] == 'false') { ?> selected="selected"<?php } ?> ><?php _e('false', FALBUM_DOMAIN);?></option>
                    </select>
                    <br />
                    <?php _e('Set to title or numeric if you want to use "friendly" URLs (requires mod_rewrite), false otherwise', FALBUM_DOMAIN);?>
                </tr>
                
                <tr valign="top">	
                    <th width="33%" scope="row"><?php _e('URL Root', FALBUM_DOMAIN);?>:</th>
                    <td><input type="text" name="url_root" size="60" value="<?php echo $options['url_root'] ?>"/><br />
                   <?php


	_e('URL to use as the root for all navigational links.<br /><strong>NOTE:</strong>It is important that you specify something here, for example:<br />If friendly URLs is <strong>enabled</strong> use - /photos/<br />If friendly URLs is <strong>disabled</strong> use - ', FALBUM_DOMAIN);
	echo $path."/wp-content/plugins/falbum/wp/album.php";
?>
				   </td>
                </tr>
                
                 <tr valign="top">
                    <th width="33%" scope="row"></th>
                    <td>
                    <?php if ( !$writable && $is_apache) { ?>
  <p><?php echo strtr(__('If your #htaccess# file was <a href="http://codex.wordpress.org/Make_a_Directory_Writable">writable</a> we could do this automatically, but it isn\'t so these are the mod_rewrite rules you should have in your <code>.htaccess</code> file. Click in the field and press <kbd>CTRL + a</kbd> to select all.', FALBUM_DOMAIN), array("#htaccess#" => "<code>$home_path.htaccess</code>")) ?></p>
  <p><textarea rows="5" style="width: 98%;" name="rules"><?php echo $rewriteRule; ?>
  </textarea></p><?php } ?>    
					</td>
                </tr>        
				                                 
		</table>
     
		<p class="submit">
		<input type="submit" name="Submit" value="<?php _e('Update Options', FALBUM_DOMAIN);?> &raquo;" />
		</p>
       
		</fieldset>
   
		</form>
		</div>

		<?php


}
}

// function for outputting header information
//
function falbum_header() {

	global $falbum_options;

	if ((defined('FALBUM') && constant('FALBUM')) || $falbum_options['wp_enable_falbum_globally'] == 'true') {

		$hHead = "<meta name='FAlbum' content='".FALBUM_VERSION."' />\n";

		$tdir = get_template_directory();
		$tdir_uri = get_template_directory_uri();
		if (file_exists($tdir."/falbum.css.php")) {
			$hHead .= "<link rel='stylesheet' href='".$tdir_uri."/falbum.css.php' type='text/css' />\n";
			//} else
			//	if (file_exists($tdir."/falbum.css")) {
			//	$hHead .= "<link rel='stylesheet' href='".$tdir_uri."/falbum.css' type='text/css' />\n";
		} else {
			$hHead .= "<link rel='stylesheet' href='".get_settings('siteurl')."/wp-content/plugins/falbum/styles/".$falbum_options['style']."/falbum.css.php' type='text/css' />\n";
		}

		print ($hHead);

	}
}

// Updates the .htaccess file with FAlbum ModRewrite rules 
// The FAlbum rules block need to come before the WP2 rules
// so they are always inserted at the top of the file
function falbum_insert_with_markers($filename, $marker, $insertion) {
	if (!file_exists($filename) || is_writeable($filename)) {

		if (!file_exists($filename)) {
			$markerdata = '';
		} else {
			$markerdata = explode("\n", implode('', file($filename)));
		}

		$f = fopen($filename, 'w');

		fwrite($f, "# BEGIN {$marker}\n");
		foreach ($insertion as $insertline)
			fwrite($f, "{$insertline}\n");
		fwrite($f, "# END {$marker}\n");

		if ($markerdata) {
			$state = true;
			foreach ($markerdata as $markerline) {
				if (strstr($markerline, "# BEGIN {$marker}"))
					$state = false;
				if ($state)
					fwrite($f, "{$markerline}\n");
				if (strstr($markerline, "# END {$marker}")) {
					$state = true;
				}
			}
		}

		fclose($f);
		return true;
	} else {
		return false;
	}
}
//

// function for outputting header information
//
function falbum_action_init() {
	global $falbum_options;

	$falbum_options = get_option('falbum_options');

	if ($falbum_options['wp_enable_falbum_globally'] == 'true') {

		require_once (dirname(__FILE__).'/falbum.php');

	}

}

function falbum_filter($content) {

	global $falbum;

	$matches = array ();
	
	$content = preg_replace('`\<p>(\[fa:(.*?)\].*?)</p>`ms', '$1', $content);

	preg_match_all('`\[fa:(.*?)\]`', $content, $matches);

	for ($i = 0; $i < count($matches[0]); $i ++) {

		$s = '';
		$v = split(":", $matches[1][$i]);

		$style = '';
		$album = '';
		$tag = '';
		$id = '';
		$page = NULL;
		$linkto = '';

		// Defaults				
		$size = 'm';
		$float = 'left';

		//Parse Parms
		if (count($v) == 2) {
			$parms = split(",", $v[1]);

			for ($index = 0; $index < sizeof($parms); $index ++) {
				$pv = trim($parms[$index]);
				$p = split("=", $pv);

				switch ($p[0]) {

					case 'a' :
					case 'album' :
						$album = $p[1];
						break;

					case 'j' :
					case 'justification' :
						if ($p[1] == 'left' || $p[1] == 'l') {
							$style = 'float: left; margin: 0px 5px -5px 0px';
						} else
							if ($p[1] == 'right' || $p[1] == 'r') {
								$style = 'float: right; margin: 0px -5px -5px 5px';
							} else {
								$style = 'position: relative; margin: 0 auto; text-align: center;';
							}
						break;

					case 'l' :
					case 'linkto' :
						$linkto = $p[1];
						break;

					case 'id' :
						$id = $p[1];
						break;

					case 'p' :
					case 'page' :
						$page = $p[1];
						break;

					case 's' :
					case 'size' :
						$size = $p[1];
						break;

					case 't' :
					case 'tag' :
						$tag = $p[1];
						break;

				}
			}
		}

		//Parse Action
		switch ($v[0]) {

			case 'a' :
			case 'album' :
				$s = $falbum->show_album_tn($id);
				break;

			case 'r' :
			case 'random' :
				if ($album == '') {
					$s = $falbum->show_random(1, $tag, 1, $size);
				} else {
					$s = $falbum->show_album_tn($album);
				}
				break;

			case 'p' :
			case 'photo' :
				//$album, $tags, $photo, $page, $size
				$s = $falbum->show_single_photo($album, $tag, $id, $page, $size, $linkto);
				break;

		}

		$s = '<div class="falbum-post-box" style="'.$style.'">'.$s.'</div>';
		
		//$s = str_replace('div', 'span', $s);

		$content = str_replace($matches[0][$i], $s, $content);
	}

	return $content;
}

function falbum_action_parse_query($wp_query) {
	if (defined('FALBUM') && constant('FALBUM')) {
		$wp_query->is_404 = false;
	}
}

add_action('parse_query', 'falbum_action_parse_query');
add_action('init', 'falbum_action_init');
add_action('wp_head', 'falbum_header');
add_action('admin_menu', 'falbum_add_pages');

add_filter('the_content', 'falbum_filter');
