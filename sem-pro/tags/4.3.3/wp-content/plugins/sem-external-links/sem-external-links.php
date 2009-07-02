<?php
/*
Plugin Name: External Links
Plugin URI: http://www.semiologic.com/software/publishing/external-links/
Description: Adds a class=&quot;external&quot; to all outbound links. Use &lt;a class=&quot;no_icon&quot; ...&gt; to disable the feature.
Author: Denis de Bernardy
Version: 2.10
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/
**/


# include admin stuff when relevant
if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false )
{
	include_once dirname(__FILE__) . '/sem-external-links-admin.php';
}


# fix php 5.2

if ( !function_exists('ob_end_flush_all') ) :
function ob_end_flush_all()
{
	while ( @ob_end_flush() );
}

register_shutdown_function('ob_end_flush_all');
endif;


#
# sem_external_links_init()
#

function sem_external_links_init()
{
	$defaults = array(
		'global' => true,
		'add_css' => true,
		'add_target' => false,
		'add_nofollow' => false
		);

	$options = get_option('sem_external_links_params');

	$initialize = false;

	foreach ( $defaults as $key => $val )
	{
		if ( !isset($options[$key]) )
		{
			$options[$key] = $val;
			$initialize = true;
		}
	}

	if ( $initialize )
	{
		update_option('sem_external_links_params', $options);
	}
} # end sem_external_links_init()

add_action('init', 'sem_external_links_init');


#
# sem_external_links_css()
#

function sem_external_links_css()
{
	$options = get_option('sem_external_links_params');

	if ( $options['add_css'] )
	{
		echo '<link rel="stylesheet" type="text/css"'
			. ' href="'
				. trailingslashit(get_settings('siteurl'))
				. 'wp-content/plugins/sem-external-links/sem-external-links.css'
				. '"'
			. ' />';
	}
} # end sem_external_links_css()

add_action('wp_head', 'sem_external_links_css');


#
# sem_external_links()
#

function sem_external_links($buffer)
{
	if ( is_feed() )
	{
		return $buffer;
	}

	# escape head
	$buffer = preg_replace_callback(
		"/
		<\s*head				# head tag
			(?:\s[^>]*)?		# optional attributes
			>
		.*						# head code
		<\s*\/\s*head\s*>		# end of head tag
		/isUx",
		'sem_external_links_escape_anchors',
		$buffer
		);

	# escape scripts
	$buffer = preg_replace_callback(
		"/
		<\s*script				# script tag
			(?:\s[^>]*)?		# optional attributes
			>
		.*						# script code
		<\s*\/\s*script\s*>		# end of script tag
		/isUx",
		'sem_external_links_escape_anchors',
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
		'sem_external_links_escape_anchors',
		$buffer
		);

	global $site_host;

	$site_host = trailingslashit(get_settings('home'));
	$site_host = preg_replace("~^https?://~i", "", $site_host);
	$site_host = preg_replace("~^www\.~i", "", $site_host);
	$site_host = preg_replace("~/.*$~", "", $site_host);

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
				[^'\"]\S*		# non-quoted link
			)
			(?:\s[^>]*)?		# optional attributes
			\s*>
		/isUx",
		'sem_external_links_callback',
		$buffer
		);

	# unescape anchors
	$buffer = sem_external_links_unescape_anchors($buffer);

	return $buffer;
} # end sem_external_links()

add_action('the_content', 'sem_external_links', 40);
add_action('the_excerpt', 'sem_external_links', 40);


#
# sem_external_links_escape_anchors()
#

function sem_external_links_escape_anchors($input)
{
	global $escaped_external_links;

#	echo '<pre>';
#	var_dump($input);
#	echo '</pre>';

	$tag_id = '--escaped_external_link:' . md5($input[0]) . '--';
	$escaped_external_links[$tag_id] = $input[0];

	return $tag_id;
} # end sem_external_links_escape_anchors()


#
# sem_external_links_unescape_anchors()
#

function sem_external_links_unescape_anchors($input)
{
	global $escaped_external_links;

	$find = array();
	$replace = array();

	foreach ( (array) $escaped_external_links as $key => $val )
	{
		$find[] = $key;
		$replace[] = $val;
	}

	return str_replace($find, $replace, $input);
} # end sem_external_links_unescape_anchors()


#
# sem_external_links_ob()
#

function sem_external_links_ob()
{
	$options = get_option('sem_external_links_params');

	if ( $options['global'] )
	{
		ob_start('sem_external_links', 1000);

		remove_action('the_content', 'sem_external_links', 40);
		remove_action('the_excerpt', 'sem_external_links', 40);
	}
} # end sem_external_links_ob()

if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') === false
	&& strpos($_SERVER['REQUEST_URI'], 'wp-includes') === false
	)
{
	add_action('init', 'sem_external_links_ob');
}


#
# sem_external_links_callback()
#

function sem_external_links_callback($input)
{
	global $site_host;

	$link = $input[0];

#	echo '<pre>';
#	var_dump(
#		get_option('sem_external_links_params'),
#		str_replace(array('<', '>'), array('&lt;', '&gt;'), $link)
#		);
#	echo '</pre>';

	if ( strpos($link, '://') !== false
		&& !preg_match(
			"/
				href\s*=\s*
				(?:\"|')?
				https?:\/\/
				(?:www\.)?
				" . str_replace('.', '\.', $site_host) . "
			/ix",
			$link
			)
		#&& strpos($link, $site_host) === false
		)
	{
		$options = get_option('sem_external_links_params');

		if ( $options['add_css'] )
		{
			if ( preg_match(
				"/
					\b
					class\s*=\s*
					(?:
						\"([^\"]*)\"
					|
						'([^']*)'
					|
						([^\"'][^\s>]*)
					)
				/iUx",
				$link,
				$match
				) )
			{
				#echo '<pre>';
				#var_dump($match);
				#echo '</pre>';

				if ( !preg_match(
					"/
						\b
						(?:
							no_?icon
						|
							external
						)
						\b
					/ix",
					$match[1]
					) )
				{
					$link = str_replace(
						$match[0],
						'class="' . $match[1] . ' external"',
						$link
						);
				}
			}
			else
			{
				$link = str_replace(
					'>',
					' class="external">',
					$link
					);
			}
		}

		if ( $options['add_target'] )
		{
			if ( !preg_match(
				"/
					\b
					target\s*=
				/iUx",
				$link
				) )
			{
				$link = str_replace(
					'>',
					' target="_blank">',
					$link
					);
			}
		}

		if ( $options['add_nofollow'] )
		{
			if ( preg_match(
				"/
					\b
					rel\s*=\s*
					(?:
						\"([^\"]*)\"
					|
						'([^']*)'
					|
						([^\"'][^\s>]*)
					)
				/iUx",
				$link,
				$match
				) )
			{
				#echo '<pre>';
				#var_dump($match);
				#echo '</pre>';

				if ( !preg_match(
					"/
						\b
						(?:
							nofollow
						|
							follow
						)
						\b
					/ix",
					$match[1]
					) )
				{
					$link = str_replace(
						$match[0],
						'rel="' . $match[1] . ' nofollow"',
						$link
						);
				}
			}
			else
			{
				$link = str_replace(
					'>',
					' rel="nofollow">',
					$link
					);
			}
		}
	}

	return $link;
} # end sem_external_links_callback()


#
# sem_external_links_kill_gzip()
#

function sem_external_links_kill_gzip($bool)
{
	return 0;
} # sem_external_links_kill_gzip()

add_filter('option_gzipcompression', 'sem_external_links_kill_gzip');
?>