<?php
/*
Plugin Name: Author Image
Plugin URI: http://www.semiologic.com/software/author-image/
Description: <a href="http://www.semiologic.com/legal/license/">Terms of use</a> &bull; <a href="http://www.semiologic.com/software/author-image/">Doc/FAQ</a> &bull; <a href="http://forum.semiologic.com">Support forum</a> &#8212; Adds the author's image where the_author_image(); is called within the loop. The [author's login].jpg files should be located in wp-content/authors: admin.jpg for admin, joe.jpg for joe, etc.
Author: Denis de Bernardy
Version: 1.1
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/
**/


#
# get_author_image()
#

function get_author_image()
{
	$author_id = get_the_author_login();

	if ( file_exists(ABSPATH . 'wp-content/authors/' . $author_id . '.jpg') )
	{
		return '<div class="entry_author_image">'
			. '<img src="'
					. trailingslashit(get_settings('siteurl'))
					. 'wp-content/authors/' . $author_id . '.jpg'
					. '"'
				. ' alt=""'
				. ' />'
			. '</div>';
	}
	else
	{
		return '';
	}
} # end get_author_image()


#
# the_author_image()
#

function the_author_image()
{
	echo get_author_image();
} # end the_author_image()
?>