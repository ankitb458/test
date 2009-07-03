<?php
/*
Plugin Name: Dofollow
Plugin URI: http://www.semiologic.com/software/dofollow/
Description: <a href="http://www.semiologic.com/legal/license/">Terms of use</a> &bull; <a href="http://www.semiologic.com/software/dofollow/">Doc/FAQ</a> &bull; <a href="http://forum.semiologic.com">Support forum</a> &#8212; Disables the rel=nofollow attribute in comments.
Author: Denis de Bernardy
Version: 2.0
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/


Hat tips
--------

	* Sebastian Herp <http://sebbi.de>
**/


class sem_dofollow
{
	#
	# Variables
	#


	#
	# Constructor
	#

	function sem_dofollow()
	{
		if ( !strpos($_SERVER['REQUEST_URI'], 'wp-admin') )
		{
			remove_filter('pre_comment_content', 'wp_rel_nofollow');
			add_filter('get_comment_author_link', array(&$this, 'strip_nofollow'), 15);
			add_filter('comment_text', array(&$this, 'strip_nofollow'), 15);
		}
	} # end sem_dofollow()


	#
	# process
	#

	function strip_nofollow($text = '')
	{
		# strip nofollow, even as rel="tag nofollow"

		$text = preg_replace("/(<a [^>]*( |\t|\n)rel=)('|\")(([^\3]*( [^ \3]*)*) )?nofollow/", "$1$3$5", $text);

		# clean up rel=""

		$text = preg_replace("/(<a [^>]*)( |\t|\n)rel=(''|\"\")([^>]*>)/", "$1$4", $text);

		return $text;
	} # end process()
} # end sem_dofollow

$sem_dofollow =& new sem_dofollow();
?>