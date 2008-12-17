<?php
/*
Plugin Name: Singular
Plugin URI: http://www.jamietalbot.com/wp-hacks/
Description: Removes the unique suffix from similarly named post slugs.<br/>Licensed under the <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>, Copyright &copy; 2005 Jamie Talbot.
Version: 0.7.1 alpha fork
Author: Jamie Talbot
Author URI: http://jamietalbot.com/
*/

/*
Singular - Removes the unique suffix from similarly named post slugs.
Copyright (c) 2005 Jamie Talbot

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

function singular_post($post_id)
{
	global $wpdb;

	if (!isset($post_id))
		$post_id = intval($_REQUEST['post_ID']);

	// authorization
	if ( !current_user_can('edit_post', $post_id) )
		return $post_id;

	$post = get_post($post_ID);
	
	if ( $post->post_type == 'revision' ) return;
	
	if (wp_verify_nonce($_REQUEST['singular'], 'singular'))
	{
		if ( !isset($_REQUEST['comment_post_ID']) )
		{
			// Thanks to Alex King for pointing out the silly errors I'd made!
			if ($specified = $_REQUEST['post_name'])
				$slug = sanitize_title($specified, $post_id);
			else
			{
				$post_title = $wpdb->get_var("SELECT post_title FROM {$wpdb->posts} WHERE ID = '{$post_id}' LIMIT 1");
				$slug = sanitize_title($post_title, $post_id);
			}
			$wpdb->query("UPDATE {$wpdb->posts} SET post_name = '$slug' WHERE ID = '{$post_id}' LIMIT 1");
		}
	}

	return $post_id;
}

add_action('save_post', 'singular_post', 8);

function singular_admin_hook()
{
	echo '<input type="hidden" name="singular" id="singular" value="' . wp_create_nonce('singular') . '" />';
}

add_action('edit_form_advanced', 'singular_admin_hook');
?>