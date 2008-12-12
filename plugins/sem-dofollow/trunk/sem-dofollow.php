<?php
/*
Plugin Name: Do Follow
Plugin URI: http://www.semiologic.com/software/wp-tweaks/dofollow/
Description: The Do Follow plugin removes the nofollow attribute that WordPress adds in comments.
Author: Denis de Bernardy
Version: 3.1
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts(http://www.mesoconcepts.com), and is distributed under the terms of the GPL license, v.2.

http://www.opensource.org/licenses/gpl-2.0.php


Hat tips
--------

	* Sebastian Herp <http://sebbi.de>
	* Thomas Parisot <http://oncle-tom.net>
**/

if ( !is_admin() ) :

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
remove_filter('pre_comment_content', 'wp_rel_nofollow', 15);
add_filter('get_comment_author_link', 'strip_nofollow', 15);
add_filter('comment_text', 'strip_nofollow', 15);

endif;
?>