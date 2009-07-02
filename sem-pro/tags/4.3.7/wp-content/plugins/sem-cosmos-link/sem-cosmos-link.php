<?php
/*
Plugin Name: Cosmos Link
Plugin URI: http://www.semiologic.com/software/geekery/cosmos-link/
Description: Adds a technorati cosmos link to your posts and pages.
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

load_plugin_textdomain('sem-cosmos-link');


#
# sem_cosmos service
#

class sem_cosmos
{
	#
	# Constructor
	#

	function sem_cosmos()
	{
	} # end sem_cosmos()


	#
	# get_link()
	#

	function get_link($url = '')
	{
		if ( $url == '' )
		{
			$url = apply_filters('the_permalink', get_permalink());
		}

		return "http://www.technorati.com/search/"
			. rawurlencode(str_replace(array('http://', 'https://'), '', $url));
	} # end get_link()


	#
	# display_link()
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
		if ($url == '' )
		{
			$url = apply_filters('the_permalink', get_permalink());
		}

		return "http://www.technorati.com/watchlist/add/"
				. rawurlencode(str_replace(array('http://', 'https://'), '', $url));
	} # end get_feed()


	#
	# feed()
	#

	function display_feed($url = '')
	{
		echo $this->get_feed($url);
	} # end feed()
} # end sem_cosmos

$sem_cosmos =& new sem_cosmos();


#
# Template tags
#

function the_cosmos_link($url = '')
{
	global $sem_cosmos;

	$sem_cosmos->display_link($url);
} # end the_cosmos_link()

function the_cosmos_feed($url = '')
{
	global $sem_cosmos;

	$sem_cosmos->display_feed($url);
} # end the_cosmos_feed()


########################
#
# Backward compatibility
#

function sem_cosmos_link($url = '')
{
	global $sem_cosmos;

	return "<a href=\"" .$sem_cosmos->get_link($url) . "\">"
		. __('Cosmos', 'sem-cosmos-link') . "</a>";
} # end sem_cosmos_link()
?>