<?php
/*
Plugin Name: Semiologic Affiliate
Plugin URI: http://www.semiologic.com/software/sem-affiliate/
Description: <a href="http://www.semiologic.com/legal/license/">Terms of use</a> &bull; <a href="http://www.semiologic.com/software/sem-affiliate/">Doc/FAQ</a> &bull; <a href="http://forum.semiologic.com">Support forum</a> &#8212; Automatically adds your affiliate ID to all links to Semiologic.
Author: Denis de Bernardy
Version: 1.3
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/
**/


# fix php 5.2

if ( !function_exists('ob_end_flush_all') ) :
function ob_end_flush_all()
{
	while ( @ob_end_flush() );
}

register_shutdown_function('ob_end_flush_all');
endif;


#
# sem_semiologic_affiliate_process_links()
#

function sem_semiologic_affiliate_process_links($buffer = '')
{
	$options = function_exists('get_site_option')
		? get_site_option('sem_semiologic_affiliate_params')
		: get_settings('sem_semiologic_affiliate_params');

	#echo '<pre>';
	#var_dump($options['aff_id']);
	#echo '</pre>';

	if ( isset($options['aff_id'])
		&& $options['aff_id'] !== ''
		)
	{
		$buffer = preg_replace_callback(
			"/
				<
				\s*
				a
				\s+
				([^>]+\s+)?
				href\s*=\s*
				(?:\"|'|)
				\s*
				(
					http(?:s)?:\/\/
				)
				(
					[^\.\"'>]+\.
				)*
				(
					semiologic\.com
					|
					getsemiologic\.com
				)
				(
					\/
					[^\s\"'>\?]*
				)?
				(
					\?
					[^\#\s\"'>]*
				)?
				(
					\#
					[^\s\"'>]*
				)?
				\s*
				(?:\"|'|)
				(\s+[^>]+)?
				\s*
				>
			/isUx",
			'sem_semiologic_affiliate_add_id',
			$buffer
			);
	}

	return $buffer;
} # end sem_semiologic_affiliate_process_links()

add_action('the_content', 'sem_semiologic_affiliate_process_links', 2000);


#
# sem_semiologic_affiliate_add_id()
#

function sem_semiologic_affiliate_add_id($input)
{
	#echo '<pre>';
	#foreach ($input as $bit) var_dump(htmlspecialchars($bit));
	#echo '</pre>';

	$options = function_exists('get_site_option')
		? get_site_option('sem_semiologic_affiliate_params')
		: get_settings('sem_semiologic_affiliate_params');

	$a_params = trim(
				$input[1] . ' '
				. ( isset($input[8]) ? trim($input[8]) : '' )
				);
	$scheme = strtolower($input[2]);
	$subdomain = strtolower($input[3]);
	$domain = strtolower($input[4]);
	$path = isset($input[5]) ? $input[5] : '';
	$params = ( isset($input[6]) && $input[6] !== '' ) ? $input[6] : '?';
	$anchor = isset($input[7]) ? $input[7] : '';

	#echo '<pre>';
	#var_dump($a_params, $scheme, $subdomain, $domain, $path, $params, $anchor);
	#echo '</pre>';

	if ( $subdomain == '' )
	{
		$subdomain = 'www.';
	}


	if (
		preg_match(
			"/
				(?:
					\?
					|
					&(?:amp;)?
				)
				(
				aff
					\s*
					=
					[^&$]*
				|
					aff
				)
				(
					&
				|
					$
				)
			/isx",
			$params,
			$aff_match
			)
		)
	{
		$old_aff = $aff_match[0];

		$new_aff = str_replace(
			$aff_match[1],
			'aff=' . $options['aff_id'],
			$aff_match[0]
			);

		$params = str_replace($old_aff, $new_aff, $params);

		#echo '<pre>';
		#var_dump($aff_match, $params);
		#echo '</pre>';

	}
	else
	{
		$params = $params
			. ( ( $params != '?' )
				? '&amp;'
				: ''
				)
			. 'aff='
			. $options['aff_id'];
	}

	$output = '<a'
		. ' href="'
			. $scheme
			. $subdomain
			. $domain
			. $path
			. $params
			. $anchor
			. '"'
		. ' ' . $a_params
		. '>';

	#echo '<pre>';
	#var_dump(htmlspecialchars($output));
	#echo '</pre>';

	return $output;
} # end sem_semiologic_affiliate_add_id()


#
# sem_semiologic_affiliate_ob()
#

function sem_semiologic_affiliate_ob()
{
	remove_action('the_content', 'sem_semiologic_affiliate_process_links', 2000);

	ob_start('sem_semiologic_affiliate_process_links');
} # end sem_semiologic_affiliate_ob()

add_action('init', 'sem_semiologic_affiliate_ob', -1000);


if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false )
{
	include_once dirname(__FILE__) . '/sem-semiologic-affiliate-admin.php';
}
?>