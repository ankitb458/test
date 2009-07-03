<?php
/*
Plugin Name: Fancy Excerpt
Plugin URI: http://www.semiologic.com/software/fancy-excerpt/
Description: <a href="http://www.semiologic.com/legal/license/">Terms of use</a> &bull; <a href="http://www.semiologic.com/software/fancy-excerpt/">Doc/FAQ</a> &bull; <a href="http://forum.semiologic.com">Support forum</a> &#8212; Enhances WordPress' default excerpt generator by generating sentence aware excerpts.
Author: Denis de Bernardy
Version: 2.5
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/


Hat tips
--------

	* Jeff Mikels <http://pastorjeff.mikels.cc>
	* Sam Salisbury <http://msdl.net>
**/


function sem_fancy_excerpt($text = '', $max_length = 200)
{
	global $post;

	$text = trim($text);

	if ( $text == '' )
	{
		$excerpt = apply_filters('the_content', $post->post_content);
		$excerpt = trim(strip_tags($excerpt));
		str_replace("&#8212;", "-", $excerpt);

		$words = preg_split("/\.|!|\?/", $excerpt, -1, PREG_SPLIT_NO_EMPTY);

		foreach ( $words as $word )
		{
			$new_text = $text . substr($excerpt, 0, strpos($excerpt, $word) + strlen($word));
			$excerpt = substr($excerpt, strpos($excerpt, $word) + strlen($word), strlen($excerpt));

			if ( ( strlen($text) != 0 ) && ( strlen($new_text) > $max_length ) )
			{
				$text .= "...";
				$text = preg_replace("/\.\.\.\.$/", "...", $text);
				break;
			}

			$text = $new_text;
		}
	}

	return $text;
} # end sem_fancy_excerpt()

remove_filter('get_the_excerpt', 'wp_trim_excerpt');
add_filter('get_the_excerpt', 'sem_fancy_excerpt');




#
# sem_fancy_excerpt service
#

class sem_fancy_excerpt
{
	#
	# Variables
	#

	var $params = array(
			'max_length' => 200			# in characters
			);


	#
	# Constructor
	#

	function sem_fancy_excerpt()
	{
		$params = get_settings('sem_fancy_excerpt_params');

		if ( $params )
		{
			foreach ( $params as $key => $value )
			{
				$this->params[$key] = $value;
			}
		}
		else
		{
			update_option('sem_fancy_excerpt_params', $this->params);
		}

		remove_filter('get_the_excerpt', 'wp_trim_excerpt');
		add_filter('get_the_excerpt', array(&$this, 'get'));
	} # end sem_fancy_excerpt()


	#
	# get()
	#

	function get($text = '')
	{
		global $post;

		$text = trim($text);

		if ( $text != '' )
		{
			$excerpt = apply_filters('the_content', $post->post_content);
			$excerpt = trim(strip_tags($excerpt));
			str_replace("&#8212;", "-", $excerpt);

			$words = preg_split("/\.|!|\?/", $excerpt, -1, PREG_SPLIT_NO_EMPTY);

			foreach ( $words as $word )
			{
				$new_text = $text . substr($excerpt, 0, strpos($excerpt, $word) + strlen($word));
				$excerpt = substr($excerpt, strpos($excerpt, $word) + strlen($word), strlen($excerpt));

				if ( ( strlen($text) != 0 ) && ( strlen($new_text) > $this->params['max_length'] ) )
				{
					$text .= "...";
					$text = preg_replace("/\.{4}$/", "...", $text);
					break;
				}

				$text = $new_text;
			}
		}

		return $text;
	} # end get()
} # end sem_fancy_excerpt

#$my_fancy_excerpt =& new sem_fancy_excerpt();
?>