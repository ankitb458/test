<?php
/*
Plugin Name: Autolink URI
Plugin URI: http://www.semiologic.com/software/autolink-uri/
Description: Automatically wraps unhyperlinked uri with html anchors.
Version: 2.0 RC
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Text Domain: sem-autolink-uri-info
Domain Path: /lang
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the GPL license, v.2.

http://www.opensource.org/licenses/gpl-2.0.php
**/


/**
 * autolink_uri
 *
 * @package Autolink URI
 **/

// after shortcodes
add_filter('the_content', array('autolink_uri', 'filter'), 12);
add_filter('the_excerpt', array('autolink_uri', 'filter'), 12);

class autolink_uri {
	/**
	 * filter()
	 *
	 * @param string $text
	 * @return string $text
	 **/

	function filter($text) {
		global $escape_autolink_uri;
		
		$escape_autolink_uri = array();
		
		$text = autolink_uri::escape($text);
		
		$text = preg_replace_callback("/
			\b
			(						# protocol or www.
				[a-z]{3,}:\/\/
			|
				www\.
			)
			(?:						# domain
				localhost
			|
				[a-z0-9%_|~-]+
				(?:\.[a-z0-9%_|~-]+)+
			)
			(?:						# path
				\/[a-z0-9%_|~.-]*
				(?:\/[a-z0-9%_|~.-]*)+
			)?
			(?:						# attributes
				\?[a-z0-9%_|~.-]*
				(?:&[a-z0-9%_|~.=&#;-]*)+
			)?
			(?:						# anchor
				\#[a-z0-9%_|~.=&#;-]*
			)?
			/ix", array('autolink_uri', 'url_callback'), $text);
		
		$text = preg_replace_callback("/
			\b
			(?:mailto:)?
			(
				[a-z0-9%_|~-]+
				(?:\.[a-z0-9%_|~-]+)*
				@
				[a-z0-9%_|~-]+
				(?:\.[a-z0-9%_|~-]+)+
			)
			/ix", array('autolink_uri', 'email_callback'), $text);
		
		$text = autolink_uri::unescape($text);
		
		return $text;
	} # filter()
	
	
	/**
	 * url_callback()
	 *
	 * @param array $match
	 * @return string $text
	 **/

	function url_callback($match) {
		$url = $match[0];
		$href = $url;
		
		if ( strtolower($match[1]) === 'www.' )
			$href = 'http://' . $href;
		
		$href = esc_url($href);
		
		return '<a href="' . $href . '">' . $url . '</a>';
	} # url_callback()
	
	
	/**
	 * email_callback()
	 *
	 * @param array $match
	 * @return string $text
	 **/

	function email_callback($match) {
		$email = antispambot(end($match));
		return '<a href="' . esc_url('mailto:' . $email) . '">' . $email . '</a>';
	} # email_callback()
	
	
	/**
	 * escape()
	 *
	 * @param string $text
	 * @return string $text
	 **/

	function escape($text) {
		global $escape_autolink_uri;
		
		if ( !isset($escape_autolink_uri) )
			$escape_autolink_uri = array();
		
		foreach ( array(
			'head' => "/
				.*?
				<\s*\/\s*head\s*>
				/isx",
			'blocks' => "/
				<\s*(script|object|textarea)(?:\s.*?)?>
				.*?
				<\s*\/\s*\\1\s*>
				/isx",
			'smart_links' => "/
				\[.+?\]
				/x",
			'anchors' => "/
				<\s*a\s.+?>.+?<\s*\/\s*a\s*>
				/isx",
			'tags' => "/
				<[^<>]+?(?:src|href|codebase|archive|usemap|data|value)=[^<>]+?>
				/ix",
			) as $regex ) {
			$text = preg_replace_callback($regex, array('autolink_uri', 'escape_callback'), $text);
		}
		
		return $text;
	} # escape()
	
	
	/**
	 * escape_callback()
	 *
	 * @param array $match
	 * @return string $tag_id
	 **/

	function escape_callback($match) {
		global $escape_autolink_uri;
		
		$tag_id = "----escape_autolink_uri:" . strtolower(md5($match[0])) . "----";
		$escape_autolink_uri[$tag_id] = $match[0];
		
		return $tag_id;
	} # escape_callback()
	
	
	/**
	 * unescape()
	 *
	 * @param string $text
	 * @return string $text
	 **/

	function unescape($text) {
		global $escape_autolink_uri;
		
		if ( !$escape_autolink_uri )
			return $text;
		
		$text = preg_replace_callback("/
			----escape_autolink_uri:[a-f0-9]{32}----
			/x", array('autolink_uri', 'unescape_callback'), $text);
		
		return $text;
	} # unescape()
	
	
	/**
	 * unescape_callback()
	 *
	 * @param array $match
	 * @return string $text
	 **/

	function unescape_callback($match) {
		global $escape_autolink_uri;
		
		return $escape_autolink_uri[$match[0]];
		
		return $match[0];
	} # unescape_callback()
} # autolink_uri
?>