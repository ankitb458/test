<?php
/*
Plugin Name: Do Follow
Plugin URI: http://www.semiologic.com/software/dofollow/
Description: Removes the <a href="http://www.semiologic.com/2005/02/05/prepare-for-more-comment-spam-not-less/">evil nofollow attribute</a> that WordPress adds in comments.
Version: 3.3
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Text Domain: sem-dofollow-info
Domain Path: /lang
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the GPL license, v.2.

http://www.opensource.org/licenses/gpl-2.0.php


Hat tips
--------

	* Sebastian Herp <http://sebbi.de>
	* Thomas Parisot <http://oncle-tom.net>
**/


if ( !is_admin() ) {
	remove_filter('pre_comment_content', 'wp_rel_nofollow', 15);
	add_filter('get_comment_author_link', 'strip_nofollow', 15);
	add_filter('comment_text', 'strip_nofollow', 15);
}


/**
 * strip_nofollow()
 *
 * @param string $text
 * @return string $text
 **/

function strip_nofollow($text = '') {
	return preg_replace_callback("/<a\s+(.+?)>/is", 'strip_nofollow_callback', $text);
} # strip_nofollow()


/**
 * strip_nofollow_callback()
 *
 * @param array $match
 * @return string $text
 **/

function strip_nofollow_callback($match) {
	return '<a ' . str_replace(array(' rel="nofollow"', " rel='nofollow'"), '', $match[1]) . '>';
} # strip_nofollow_callback()
?>