<?php
/*
Plugin Name: Autolink URI
Plugin URI: http://www.semiologic.com/software/publishing/autolink-uri/
Description: Automatically wrap unhyperlinked uri with html anchors.
Author: Denis de Bernardy
Version: 1.6
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd (http://www.mesoconcepts.com), and is distributed under the terms of the GPL license, v.2.

http://www.opensource.org/licenses/gpl-2.0.php
**/


#
# sem_autolink_uri()
#

function sem_autolink_uri($buffer)
{
	global $escaped_anchors;

	$escaped_anchors = array();

	# escape scripts
	$buffer = preg_replace_callback(
		"/
		<\s*script				# script tag
			(?:\s[^>]*)?		# optional attributes
			>
		.*						# script code
		<\s*\/\s*script\s*>		# end of script tag
		/isUx",
		'sem_autolink_uri_escape_anchors',
		$buffer
		);

	# escape objects
	$buffer = preg_replace_callback(
		"/
		<\s*object				# object tag
			(?:\s[^>]*)?		# optional attributes
			>
		.*						# object code
		<\s*\/\s*object\s*>		# end of object tag
		/isUx",
		'sem_autolink_uri_escape_anchors',
		$buffer
		);

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
				\?[0-9a-zA-Z~_\-+\.\/,&;=]+
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