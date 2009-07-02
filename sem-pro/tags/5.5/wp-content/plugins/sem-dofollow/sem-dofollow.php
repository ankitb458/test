<?php
/*
Plugin Name: Dofollow
Plugin URI: http://www.semiologic.com/software/wp-fixes/dofollow/
Description: Disables the rel=nofollow attribute in comments.
Author: Denis de Bernardy
Version: 3.0
Author URI: http://www.semiologic.com
Update Service: http://version.mesoconcepts.com/wordpress
Update Tag: dofollow
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/


Hat tips
--------

	* Sebastian Herp <http://sebbi.de>
	* Thomas Parisot <http://oncle-tom.net>
**/

if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') === false ) :

function strip_nofollow($text = '')
{
	# strip nofollow, even as rel="tag nofollow"
	$text = preg_replace('/
		(
			<a
			\s+
			.*
			\s+
			rel=["\']
			[a-z0-9\s\-_\|\[\]]*
		)
		(
			\b
			nofollow
			\b
		)
		(
			[a-z0-9\s\-_\|\[\]]*
			["\']
			.*
			>
		)
		/isUx', "$1$3", $text);

	# clean up rel=""
	$text = str_replace(array(' rel=""', " rel=''"), '', $text);

	return $text;
} # strip_nofollow()

//add filters
remove_filter('pre_comment_content', 'wp_rel_nofollow');
add_filter('get_comment_author_link', 'strip_nofollow', 15);
add_filter('comment_text', 'strip_nofollow', 15);

endif;
?>