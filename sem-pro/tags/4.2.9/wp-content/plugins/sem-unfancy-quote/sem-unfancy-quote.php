<?php
/*
Plugin Name: Unfancy Quote
Plugin URI: http://www.semiologic.com/software/unfancy-quote/
Description: <a href="http://www.semiologic.com/legal/license/">Terms of use</a> &bull; <a href="http://www.semiologic.com/software/unfancy-quote/">Doc/FAQ</a> &bull; <a href="http://forum.semiologic.com">Support forum</a> &#8212; Removes WP-inserted fancy quotes.
Author: Denis de Bernardy
Version: 2.0
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/
**/


class sem_unfancy_quote
{
	#
	# Variables
	#


	#
	# Constructor
	#

	function sem_unfancy_quote()
	{
		add_filter('category_description', array(&$this, 'strip_quotes'), 20);
		add_filter('list_cats', array(&$this, 'strip_quotes'), 20);
		add_filter('comment_author', array(&$this, 'strip_quotes'), 20);
		add_filter('comment_text', array(&$this, 'strip_quotes'), 20);
		add_filter('single_post_title', array(&$this, 'strip_quotes'), 20);
		add_filter('the_title', array(&$this, 'strip_quotes'), 20);
		add_filter('the_content', array(&$this, 'strip_quotes'), 20);
		add_filter('the_excerpt', array(&$this, 'strip_quotes'), 20);
		add_filter('bloginfo', array(&$this, 'strip_quotes'), 20);
	} # end sem_unfancy_quote()


	#
	# strip_quotes()
	#

	function strip_quotes($text = '')
	{
		$text = str_replace(array("&#8216;", "&#8217;", "&#8242;"), "&#039;", $text);
		$text = str_replace(array("&#8220;", "&#8221;", "&#8243;"), "&#034;", $text);

		return $text;
	} # end strip_quotes()
} # end sem_unfancy_quote

$sem_unfancy_quote =& new sem_unfancy_quote();
?>