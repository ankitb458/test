<?php
/*
Plugin Name: Dofollow
Plugin URI: http://www.semiologic.com/software/wp-fixes/dofollow/
Description: Disables the rel=nofollow attribute in comments.
Author: Denis de Bernardy
Version: 2.1
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
	# init()
	#

	function init()
	{
		if ( !strpos($_SERVER['REQUEST_URI'], 'wp-admin') )
		{
			remove_filter('pre_comment_content', 'wp_rel_nofollow');
			add_filter('get_comment_author_link', array('sem_dofollow', 'strip_nofollow'), 15);
			add_filter('comment_text', array('sem_dofollow', 'strip_nofollow'), 15);
		}
	} # end init()


	#
	# strip_nofollow()
	#

	function strip_nofollow($text = '')
	{
		# strip nofollow, even as rel="tag nofollow"

		$text = preg_replace(
				"/
					(<a)
					(\s[^>]+)?
					(
						\s
						rel=
						('|\")
						([^\4]*\s)?
					)
						nofollow
				/isUx",
				"$1$2$3",
				$text
				);

		# clean up rel=""

		$text = preg_replace(
				"/
					(<a [^>]*)
					( |\t|\n)
					rel=(''|\"\")
					([^>]*>)
				/isUx",
				"$1$4",
				$text
				);

		return $text;
	} # end strip_nofollow()
} # end sem_dofollow

sem_dofollow::init();
?>