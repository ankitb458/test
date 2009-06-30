<?php
/*
Plugin Name: Fancy Excerpt
Plugin URI: http://www.semiologic.com/software/fancy-excerpt/
Description: Enhances WordPress' default excerpt generator by generating paragraph aware excerpts followed by more... links.
Version: 3.0 RC
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Text Domain: sem-fancy-excerpt
Domain Path: /lang
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the GPL license, v.2.

http://www.opensource.org/licenses/gpl-2.0.php
**/


load_plugin_textdomain('sem-fancy-excerpt', null, dirname(__FILE__) . '/lang');


remove_filter('get_the_excerpt', 'wp_trim_excerpt');
add_filter('get_the_excerpt', 'fancy_excerpt', 0);

/**
 * fancy_excerpt()
 *
 * @param string $content
 * @return string $content
 **/

function fancy_excerpt($text) {
	$text = trim($text);
	
	if ( $text || !in_the_loop() )
		return wp_trim_excerpt($text);
	
	global $allowedposttags;
	
	$more = sprintf(__('More on %s...', 'fancy-excerpt'), get_the_title());
	
	$text = get_the_content($more);
	$text = str_replace(array("\r\n", "\r"), "\n", $text);
	$text = preg_replace("/
		<\s*(script|style|textarea)(?:\s.*?)?>
		.*?
		<\s*\/\s*\\1\s*>
		/isx", '', $text);
	$text = wp_kses($text, $allowedposttags);
	
	if ( !preg_match("|$more</a>$|", $text)
		&& count(preg_split("~\s+~", trim(strip_tags($text)))) > 30
	) {
		$bits = preg_split("/(<(?:h[1-6]|p|ul|ol|li|dl|dd|table|tr|pre|blockquote)\b[^>]*>|\n{2,})/i", $text, null, PREG_SPLIT_DELIM_CAPTURE);
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
			. apply_filters('the_content_more_link',
				'<a href="'. esc_url(get_permalink()) . '" class="more-link">'
				. $more
				. '</a>')
			. '</p>' . "\n";
	}
	
	$text = apply_filters('the_content', $text);
	
	return apply_filters('wp_trim_excerpt', $text, '');
} # fancy_excerpt()
?>