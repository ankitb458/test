<?php
/*
Plugin Name: Frame Buster
Plugin URI: http://www.semiologic.com/software/frame-buster/
Description: <a href="http://www.semiologic.com/legal/license/">Terms of use</a> &bull; <a href="http://www.semiologic.com/software/frame-buster/">Doc/FAQ</a> &bull; <a href="http://forum.semiologic.com">Support forum</a> &#8212; Prevents your blog from being loaded into a frame.
Author: Denis de Bernardy
Version: 3.3
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/
**/


class sem_frame_buster
{
	#
	# Variables
	#


	#
	# Constructor
	#

	function sem_frame_buster()
	{
		add_action('wp_head', array(&$this, 'kill_frame'));
	} # end sem_frame_buster()


	#
	# kill_frame()
	#

	function kill_frame()
	{
		echo "<script type=\"text/javascript\">\n"
			. "var parent_location = new String(parent.location);\n"
			. "if ( ( top.location != location )\n"
			. "  && parent_location.indexOf('" . get_settings('home') . "') != 0 )\n"
				. "top.location.href = document.location.href;\n"
			. "</script>\n";
	} # end kill_frame()
} # end sem_frame_buster

$sem_frame_buster =& new sem_frame_buster();
?>