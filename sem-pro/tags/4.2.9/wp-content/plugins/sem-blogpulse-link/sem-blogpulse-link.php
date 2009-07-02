<?php
/*
Plugin Name: BlogPulse Link
Plugin URI: http://www.semiologic.com/software/blogpulse-link/
Description: <a href="http://www.semiologic.com/legal/license/">Terms of use</a> &bull; <a href="http://www.semiologic.com/software/blogpulse-link/">Doc/FAQ</a> &bull; <a href="http://forum.semiologic.com">Support forum</a> &#8212; Adds the BlogPulse link of your posts and pages. Calling the_blogpulse_link(); and the_blogpulse_feed(); will echo the proper urls within the loop.
Author: Denis de Bernardy
Version: 2.1
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/
**/


load_plugin_textdomain('sem-blogpulse-link');


#
# sem_blogpulse service
#

class sem_blogpulse
{
	#
	# Constructor
	#

	function sem_blogpulse()
	{
	} # end sem_blogpulse()


	#
	# get_link()
	#

	function get_link($url = '')
	{
		if ( $url == '' )
		{
			$url = apply_filters('the_permalink', get_permalink());
		}

		return "http://www.blogpulse.com/search?query="
			. rawurlencode($url);
	} # end get_link()


	#
	# link()
	#

	function display_link($url = '')
	{
		echo $this->get_link($url);
	} # end link()


	#
	# get_feed()
	#

	function get_feed($url = '')
	{
		if ( $url == '' )
		{
			$url = apply_filters('the_permalink', get_permalink());
		}

		return "http://www.blogpulse.com/rss?query="
			. rawurlencode($url);
	} # end get_feed()


	#
	# feed()
	#

	function display_feed($url = '')
	{
		echo $this->get_feed($url);
	} # end feed()
} # end sem_blogpulse

$sem_blogpulse =& new sem_blogpulse();


#
# Template tags
#

function the_blogpulse_link($url = '')
{
	global $sem_blogpulse;

	$sem_blogpulse->display_link($url);
} # end the_blogpulse_link()

function the_blogpulse_feed($url = '')
{
	global $sem_blogpulse;

	$sem_blogpulse->display_feed($url);
} # end the_blogpulse_feed()


########################
#
# Backward compatibility
#

function sem_blogpulse_link($url = '')
{
	global $sem_blogpulse;

	return "<a href=\"" . $sem_blogpulse->get_link($url) . "\">"
		. __('Blogpulse', 'sem-blogpulse-link') . "</a>";
} # end sem_blogpulse_link()
?>