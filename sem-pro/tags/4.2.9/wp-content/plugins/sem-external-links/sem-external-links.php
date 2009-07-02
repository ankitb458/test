<?php
/*
Plugin Name: External Links
Plugin URI: http://www.semiologic.com/software/external-links/
Description: <a href="http://www.semiologic.com/legal/license/">Terms of use</a> &bull; <a href="http://www.semiologic.com/software/external-links/">Doc/FAQ</a> &bull; <a href="http://forum.semiologic.com">Support forum</a> &#8212; Adds a class=&quot;external&quot; to all outbound links. Use &lt;a class=&quot;no_icon&quot; ...&gt; to disable the feature.
Author: Denis de Bernardy
Version: 2.5
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
		'add_target' => false
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
				[^'\"]\S*		# none-quoted link
			)
			(?:\s[^>]*)?		# optional attributes
			\s*>
		/isUx",
		'sem_external_links_callback',
		$buffer
		);

	return $buffer;
} # end sem_external_links()

add_action('the_content', 'sem_external_links', 40);
add_action('the_excerpt', 'sem_external_links', 40);


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

if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') === false )
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
	}

	return $link;
} # end sem_external_links_callback()
?>