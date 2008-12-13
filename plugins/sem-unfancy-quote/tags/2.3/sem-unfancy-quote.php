<?php
/*
Plugin Name: Unfancy Quote
Plugin URI: http://www.semiologic.com/software/wp-tweaks/unfancy-quote/
Description: Removes WordPress fancy quotes, which is very useful if you post code snippets to your site.
Author: Denis de Bernardy
Version: 2.3
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts(http://www.mesoconcepts.com), and is distributed under the terms of the GPL license, v.2.

http://www.opensource.org/licenses/gpl-2.0.php
**/


class unfancy_quote
{
	#
	# init()
	#

	function init()
	{
		add_filter('category_description', array('unfancy_quote', 'strip_quotes'), 20);
		add_filter('list_cats', array('unfancy_quote', 'strip_quotes'), 20);
		add_filter('comment_author', array('unfancy_quote', 'strip_quotes'), 20);
		add_filter('comment_text', array('unfancy_quote', 'strip_quotes'), 20);
		add_filter('single_post_title', array('unfancy_quote', 'strip_quotes'), 20);
		add_filter('the_title', array('unfancy_quote', 'strip_quotes'), 20);
		add_filter('the_content', array('unfancy_quote', 'strip_quotes'), 20);
		add_filter('the_excerpt', array('unfancy_quote', 'strip_quotes'), 20);
		add_filter('bloginfo', array('unfancy_quote', 'strip_quotes'), 20);
		add_filter('widget_text', array('unfancy_quote', 'strip_quotes'), 20);
	} # init()


	#
	# strip_quotes()
	#

	function strip_quotes($text = '')
	{
		$text = str_replace(array("&#8216;", "&#8217;", "&#8242;"), "&#039;", $text);
		$text = str_replace(array("&#8220;", "&#8221;", "&#8243;"), "&#034;", $text);

		return $text;
	} # strip_quotes()
} # unfancy_quote

unfancy_quote::init();
?>