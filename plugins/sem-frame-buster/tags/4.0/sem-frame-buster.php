<?php
/*
Plugin Name: Frame Buster
Plugin URI: http://www.semiologic.com/software/wp-tweaks/frame-buster/
Description: Prevents your blog from being loaded into a frame.
Author: Denis de Bernardy
Version: 4.0
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the GPL license, v.2.

http://www.opensource.org/licenses/gpl-2.0.php
**/


class sem_frame_buster
{
	#
	# Constructor
	#

	function init()
	{
		add_action('wp_footer', array('sem_frame_buster', 'kill_frame'));
	} # init()


	#
	# kill_frame()
	#

	function kill_frame()
	{
		if ( !is_preview() )
		{
			$home_url = strtolower(get_option('home'));

			echo <<<KILL_FRAME_SCRIPT
<script type="text/javascript">
<!--
try
{
	var parent_location = new String(parent.location);
	var top_location = new String(top.location);
	var cur_location = new String(document.location);
	parent_location = parent_location.toLowerCase();
	top_location = top_location.toLowerCase();
	cur_location = cur_location.toLowerCase();

	if ( ( top_location != cur_location ) && parent_location.indexOf('{$home_url}') != 0 )
	{
		top.location.href = document.location.href;
	}
}
catch ( err )
{
	top.location.href = document.location.href;
}
//-->
</script>
KILL_FRAME_SCRIPT;
		}
	} # kill_frame()
} # sem_frame_buster

sem_frame_buster::init();
?>