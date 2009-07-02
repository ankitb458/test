<?php
/*
Plugin Name: Autolink uri
Plugin URI: http://www.semiologic.com/software/autolink-uri/
Description: <a href="http://www.semiologic.com/legal/license/">Terms of use</a> &bull; <a href="http://www.semiologic.com/software/autolink-uri/">Doc/FAQ</a> &bull; <a href="http://forum.semiologic.com">Support forum</a> &#8212; Automatically hyperlink uri
Author: Denis de Bernardy
Version: 1.2
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/
**/


#
# sem_autolink_uri()
#

function sem_autolink_uri($buffer)
{
	global $escaped_anchors;

	$escaped_anchors = array();

	# escape existing anchors
	$buffer = preg_replace_callback(
		"/
		<\s*a					# ancher tag
			(?:\s[^>]*)?		# optional attributes
			\s*href\s*=\s*		# href=...
			(?:
				\"[^\"]*\"		# double quoted link
			|
				'[^']*'			# single quoted link
			|
				[^'\"]\S*		# none-quoted link
			)
			(?:\s[^>]*)?		# optional attributes
			\s*>
		.*						# link text
		<\s*\/\s*a\s*>			# end of anchor tag
		/isUx",
		'sem_autolink_uri_escape_anchors',
		$buffer
		);

	# escape uri within tags
	$buffer = preg_replace_callback(
		"/
		<[^>]*
			(?:
				(?:			# link starting with a scheme
					http(?:s)?
				|
					ftp
				)
				:\/\/
			|
				www\.		# link starting with no scheme
			)
			[^>]*>
		/isUx",
		'sem_autolink_uri_escape_anchors',
		$buffer
		);

	if ( class_exists('sem_smart_link') || function_exists('sem_smart_link') )
	{
		$buffer = preg_replace_callback(
			"/
			\[
				(?:.+)
				-(?:>|&gt;)
				(?:.*)
			\]
			/isUx",
			'sem_autolink_uri_escape_anchors',
			$buffer
			);
	}

	# add anchors to unanchored links
	$buffer = preg_replace_callback(
		"/
		\b									# word boundary
		(
			(?:								# link starting with a scheme
				http(?:s)?
			|
				ftp
			)
			:\/\/
		|
			www\.							# link starting with no scheme
		)
		(
			(								# domain
				localhost
			|
				[0-9a-zA-Z_\-]+
				(?:\.[0-9a-zA-Z_\-]+)+
			)
			(?:								# maybe a subdirectory
				\/
				[0-9a-zA-Z~_\-+\.\/,&;]*
			)?
			(?:								# maybe some parameters
				\?[0-9a-zA-Z~_\-+\.\/,&;]+
			)?
			(?:								# maybe an id
				\#[0-9a-zA-Z~_\-+\.\/,&;]+
			)?
		)
		/imsx",
		'sem_autolink_uri_add_links',
		$buffer
		);

	# unescape anchors
	$buffer = sem_autolink_uri_unescape_anchors($buffer);

	return $buffer;
} # end sem_autolink_uri()

add_action('the_content', 'sem_autolink_uri', 20);
add_action('the_excerpt', 'sem_autolink_uri', 20);


#
# sem_autolink_uri_escape_anchors()
#

function sem_autolink_uri_escape_anchors($input)
{
	global $escaped_anchors;

#	echo '<pre>';
#	var_dump($input);
#	echo '</pre>';

	$anchor_id = '--escaped_anchor:' . md5($input[0]) . '--';
	$escaped_anchors[$anchor_id] = $input[0];

	return $anchor_id;
} # end sem_autolink_uri_escape_anchors()


#
# sem_autolink_uri_unescape_anchors()
#

function sem_autolink_uri_unescape_anchors($input)
{
	global $escaped_anchors;

	$find = array();
	$replace = array();

	foreach ( $escaped_anchors as $key => $val )
	{
		$find[] = $key;
		$replace[] = $val;
	}

	return str_replace($find, $replace, $input);
} # end sem_autolink_uri_unescape_anchors()


#
# sem_autolink_uri_add_links()
#

function sem_autolink_uri_add_links($input)
{
	#echo '<pre>';
	#var_dump($input);
	#echo '</pre>';

	if ( strtolower($input[1]) == 'www.' )
	{
		return '<a'
			. ' href="http://' . $input[0] . '"'
			. '>'
			. $input[0]
			. '</a>';
	}
	else
	{
		return '<a'
			. ' href="' . $input[0] . '"'
			. '>'
			. $input[0]
			. '</a>';
	}
} # end sem_autolink_uri_add_links()
?>