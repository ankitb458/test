<?php
/*
Plugin Name: Fancy Excerpt
Plugin URI: http://www.semiologic.com/software/wp-fixes/fancy-excerpt/
Description: Enhances WordPress' default excerpt generator by generating sentence aware excerpts.
Author: Denis de Bernardy
Version: 2.8
Author URI: http://www.semiologic.com
Update Service: http://version.mesoconcepts.com/wordpress
Update Tag: fancy_excerpt
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
		$content = $post->post_content;

		if ( function_exists('nzshpcrt_shopping_basket') )
		{
			$content = str_replace(
				array('[productspage]', '[shoppingcart]', '[checkout]', '[transactionresults]'),
				'',
				$content
				);
		}

		$excerpt = apply_filters('the_content', $content);
		$excerpt = trim(strip_tags($excerpt));
		str_replace("&#8212;", "-", $excerpt);

		$words = preg_split("/(?<=(\.|!|\?)+)\s/", $excerpt, -1, PREG_SPLIT_NO_EMPTY);

		foreach ( $words as $word )
		{
			$new_text = $text . substr($excerpt, 0, strpos($excerpt, $word) + strlen($word));
			$excerpt = substr($excerpt, strpos($excerpt, $word) + strlen($word), strlen($excerpt));

			if ( ( strlen($text) != 0 ) && ( strlen($new_text) > $max_length ) )
			{
				$text .= " (...)";
				break;
			}

			$text = $new_text;
		}
	}

	return $text;
} # end sem_fancy_excerpt()

remove_filter('get_the_excerpt', 'wp_trim_excerpt');
add_filter('get_the_excerpt', 'sem_fancy_excerpt');
?>