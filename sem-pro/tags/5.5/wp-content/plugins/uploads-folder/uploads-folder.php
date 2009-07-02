<?php
/*
Plugin Name: Uploads Folder
Plugin URI: http://www.semiologic.com/software/wp-tweaks/uploads-folder/
Description: Changes your uploads folders to a more natural uploads/yyyy/mm for posts (based on the post's date rather than the current date), and uploads/page/sub-page for static pages (based on the page's position in the hierarchy).
Author: Denis de Bernardy
Version: 1.0 RC
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd (http://www.mesoconcepts.com), and is distributed under the terms of the GPL license, v.2.

http://www.opensource.org/licenses/gpl-2.0.php
**/


class uploads_folder
{
	#
	# init()
	#
	
	function init()
	{
		add_filter('upload_dir', array('uploads_folder', 'filter'));
	} # init()
	
	
	#
	# filter()
	#
	
	function filter($uploads)
	{
		$post_id = $_POST['post_id'];
		
		if ( !isset($post_id) || $post_id < 0 )
		{
			return $uploads;
		}

		$post = get_post($post_id);
		
		if ( $post->post_type == 'page' )
		{
			if ( $post->post_name == '' )
			{
				$post->post_name = sanitize_title($post->post_title);
				
				if ( $post->post_name == '' )
				{
					$post->post_name = $post->ID;
				}
			}
			
			$subdir = '/' . $post->post_name;
			
			if ( $post->post_parent <> 0 )
			{
				do
				{
					$post = get_post($post->post_parent);
					
					if ( $post->post_name == '' )
					{
						$post->post_name = sanitize_title($post->post_title);
				
						if ( $post->post_name == '' )
						{
							$post->post_name = $post->ID;
						}
					}
					
					$subdir = '/' . $post->post_name . $subdir;
				} while ( $post->post_parent > 0 );
			}
		}
		elseif ( $post->post_date != '0000-00-00 00:00:00' )
		{
			$subdir = date("/Y/m", strtotime($post->post_date));
		}
		
		if ( $subdir && $subdir != $uploads['subdir'] )
		{
			$uploads['path'] = preg_replace("#" . $uploads['subdir'] . "$#", $subdir, $uploads['path']);
			$uploads['url'] = preg_replace("#" . $uploads['subdir'] . "$#", $subdir, $uploads['url']);
			$uploads['subdir'] = $subdir;
			
			if ( !wp_mkdir_p($uploads['path']) )
			{
				$message = sprintf( __( 'Unable to create directory %s. Is its parent directory writable by the server?' ), $uploads['path'] );
				return array( 'error' => $message );
			}
		}
		
		return $uploads;
	} # filter()
} # uploads_folder

uploads_folder::init();
?>