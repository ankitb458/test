<?php
/*
Plugin Name: Author Image
Plugin URI: http://www.semiologic.com/software/publishing/author-image/
Description: Adds the authors images to your site, which individual users can configure in their profile. Your wp-content folder needs to be writable by the server.
Author: Denis de Bernardy
Version: 2.1
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/
**/


class sem_author_image
{
	#
	# get()
	#

	function get()
	{
		$author_id = get_the_author_login();

		if ( !isset($GLOBALS['author_image_cache'][$author_id]) )
		{
			if ( $image = glob(ABSPATH . 'wp-content/authors/' . $author_id . '{,-*}.{jpg,png}', GLOB_BRACE) )
			{
				$image = current($image);
			}
			else
			{
				$image = false;
			}

			$GLOBALS['author_image_cache'][$author_id] = $image;
		}

		if ( $GLOBALS['author_image_cache'][$author_id] )
		{
			$site_url = trailingslashit(get_option('siteurl'));

			return '<div class="entry_author_image">'
				. '<img src="'
						. str_replace(ABSPATH, $site_url, $GLOBALS['author_image_cache'][$author_id])
						. '"'
					. ' alt=""'
					. ' />'
				. '</div>';
		}
	} # get()
} # sem_author_image


#
# the_author_image()
#

function the_author_image()
{
	echo sem_author_image::get();
} # end the_author_image()




if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false
	)
{
	include_once dirname(__FILE__) . '/sem-author-image-admin.php';
}
?>