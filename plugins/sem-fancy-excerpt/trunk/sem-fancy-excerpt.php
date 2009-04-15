<?php
/*
Plugin Name: Fancy Excerpt
Plugin URI: http://www.semiologic.com/software/fancy-excerpt/
Description: Enhances WordPress' default excerpt generator by generating paragraph aware excerpts.
Version: 3.0 RC
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the GPL license, v.2.

http://www.opensource.org/licenses/gpl-2.0.php
**/


/**
 * fancy_excerpt
 *
 * @package Fancy Excerpt
 **/

remove_filter('get_the_excerpt', 'wp_trim_excerpt');
add_filter('get_the_excerpt', array('fancy_excerpt', 'process'), 0);

class fancy_excerpt {
	/**
	 * process()
	 *
	 * @param string $content
	 * @return string $content
	 **/

	function process($text) {
		$text = trim($text);
		
		if ( $text ) {
			return $text;
		} elseif ( !in_the_loop() ) {
			return wp_trim_excerpt($text);
		}
		
		global $sem_captions;
		
		if ( isset($sem_captions['more_link']) ) {
			$more = $sem_captions['more_link'];
			$more = str_replace('%title%', get_the_title(), $more);
		} else {
			$more = __('More...');
		}
		
		$text = get_the_content($more);
		$text = strip_shortcodes($text);
		$text = str_replace(array("\r\n", "\r"), "\n", $text);
		
		if ( !preg_match("|$more</a>$|", $text)
			&& count(preg_split("~\s+~", trim(strip_tags($text)))) > 30
		) {
			# automatically add a more... tag
			
			$bits = preg_split("~(<(?:h[1-6]|p|ul|ol|li|dl|dd|table|tr|pre|blockquote)\b[^>]*>|\n+)~i", $text, null, PREG_SPLIT_DELIM_CAPTURE);
			$text = '';
			$length = 0;
			
			foreach ( $bits as $bit ) {
				$text .= $bit;
				$count += count(preg_split("~\s+~", trim(strip_tags($bit))));
				
				if ( $count > 30 )
					break;
			}
			
			$text = force_balance_tags($text);
			
			$text .= "\n\n"
				. '<p>'
				. ' <a href="'. htmlspecialchars(get_permalink()) . '" class="more-link">'
					. $more
					. '</a>'
				. '</p>' . "\n";
			
			$text = apply_filters('the_content', $text);
		}
		
		return $text;
	} # process()
} # fancy_excerpt
?>