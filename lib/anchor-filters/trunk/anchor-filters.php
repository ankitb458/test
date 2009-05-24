<?php
/*
 * Anchor Filters
 * Author: Denis de Bernardy <http://www.mesoconcepts.com>
 * Version: 1.0
 */

/**
 * anchor_filters
 *
 * @package Anchor Filters
 **/

add_filter('the_content', array('anchor_filters', 'apply'), 100);
add_filter('the_excerpt', array('anchor_filters', 'apply'), 100);

class anchor_filters {
	/**
	 * apply()
	 *
	 * @param string $text
	 * @return string $text
	 **/

	function apply($text) {
		$text = preg_replace_callback("/
			<\s*a\s+
			([^<>]+)
			>
			(.+?)
			<\s*\/\s*a\s*>
			/isx", array('anchor_filters', 'callback'), $text);
		
		return $text;
	} # apply()
	
	
	/**
	 * callback()
	 *
	 * @param array $match
	 * @return string $str
	 **/

	function callback($match) {
		# skip empty anchors
		if ( !trim($match[2]) )
			return $match[0];
		
		# parse anchor
		$anchor = array();
		
		$anchor['attr'] = shortcode_parse_atts($match[1]);
		
		if ( !is_array($anchor['attr']) ) # shortcode parser error
			return $match[0];
		
		foreach ( array('class', 'rel') as $attr ) {
			if ( !isset($anchor['attr'][$attr]) ) {
				$anchor['attr'][$attr] = array();
			} else {
				$anchor['attr'][$attr] = explode(' ', $anchor['attr'][$attr]);
				$anchor['attr'][$attr] = array_map('trim', $anchor['attr'][$attr]);
			}
		}
		
		$anchor['body'] = $match[2];
		
		# filter anchor
		$anchor = apply_filters('anchor_filters', $anchor);
		
		# return anchor
		$str = '<a ';
		foreach ( $anchor['attr'] as $k => $v ) {
			if ( is_array($v) ) {
				$v = array_unique($v);
				if ( $v )
					$str .= ' ' . $k . '="' . implode(' ', $v) . '"';
			} else {
				$str .= ' ' . $k . '="' . $v . '"';
			}
		}
		$str .= '>' . $anchor['body'] . '</a>';
		
		return $str;
	} # callback()
} # anchor_filters
?>