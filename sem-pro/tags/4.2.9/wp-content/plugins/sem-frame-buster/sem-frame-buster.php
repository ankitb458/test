<?php
/*
Plugin Name: Frame Buster
Plugin URI: http://www.semiologic.com/software/frame-buster/
Description: <a href="http://www.semiologic.com/legal/license/">Terms of use</a> &bull; <a href="http://www.semiologic.com/software/frame-buster/">Doc/FAQ</a> &bull; <a href="http://forum.semiologic.com">Support forum</a> &#8212; Prevents your blog from being loaded into a frame.
Author: Denis de Bernardy
Version: 3.5
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
		if ( !is_preview() )
		{
			$home_url = strtolower(get_settings('home'));

			echo <<<KILL_FRAME_SCRIPT
<script type="text/javascript">
<!--
var parent_location = new String(parent.location);
var top_location = new String(top.location);
var cur_location = new String(location);
parent_location = parent_location.toLowerCase();
top_location = top_location.toLowerCase();
cur_location = cur_location.toLowerCase();
if ( ( top_location != cur_location ) && parent_location.indexOf('{$home_url}') != 0 )
{
	top.location.href = document.location.href;
}
//-->
</script>
KILL_FRAME_SCRIPT;
		}
	} # end kill_frame()
} # end sem_frame_buster

$sem_frame_buster =& new sem_frame_buster();
?>