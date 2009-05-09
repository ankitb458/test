<?php
/*
Plugin Name: Semiologic Affiliate
Plugin URI: http://www.semiologic.com/software/sem-affiliate/
Description: Automatically adds your affiliate ID to all links to Semiologic.
Version: 1.8.3 RC
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


if ( @ini_get('pcre.backtrack_limit') < 250000 )
	@ini_set('pcre.backtrack_limit', 250000);

#
# sem_semiologic_affiliate_process_links()
#

function sem_semiologic_affiliate_process_links($buffer = '')
{
	$options = get_option('sem_semiologic_affiliate_params');

	#echo '<pre>';
	#var_dump($options['aff_id']);
	#echo '</pre>';

	if ( isset($options['aff_id'])
		&& $options['aff_id'] !== ''
		&& !is_admin()
		)
	{
		global $sem_semiologic_affiliate_escape;
		
		$sem_semiologic_affiliate_escape = array();
		
		$buffer = preg_replace_callback("/
			<\s*(object|script).*>
			.+
			<\s*\/\\1\s*>
			/isUx", 'sem_semiologic_affiliate_escape', $buffer);
		
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
		
		$find = array_keys($sem_semiologic_affiliate_escape);
		$repl = array_values($sem_semiologic_affiliate_escape);
		
		$buffer = str_replace($find, $repl, $buffer);
	}
	
	return $buffer;
} # end sem_semiologic_affiliate_process_links()

add_filter('the_content', 'sem_semiologic_affiliate_process_links');


#
# sem_semiologic_affiliate_ob()
#

function sem_semiologic_affiliate_ob()
{
	ob_start('sem_semiologic_affiliate_process_links');
} # sem_semiologic_affiliate_ob()

add_action('wp_head', 'sem_semiologic_affiliate_ob', -10000);


#
# sem_semiologic_affiliate_unescape()
#

function sem_semiologic_affiliate_escape($match)
{
	global $sem_semiologic_affiliate_escape;
	
	$id = uniqid(rand());
	$id = "---sem_semiologic_affiliate_escape---$id---";
	
	$sem_semiologic_affiliate_escape[$id] = $match[0];

	return $id;
} # sem_semiologic_affiliate_unescape()


#
# sem_semiologic_affiliate_add_id()
#

function sem_semiologic_affiliate_add_id($input)
{
	#echo '<pre>';
	#foreach ($input as $bit) var_dump(htmlspecialchars($bit));
	#echo '</pre>';

	$options = get_option('sem_semiologic_affiliate_params');

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
					&(?:amp;|0*38;)?
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


if ( is_admin() )
{
	include dirname(__FILE__) . '/sem-semiologic-affiliate-admin.php';
}
?>