<?php
/*
Plugin Name: Simple Trackback Validation
Plugin URI: http://sw-guide.de/wordpress/plugins/simple-trackback-validation/
Description: Eliminates spam trackbacks by (1) checking if the IP address of the trackback sender is equal to the IP address of the webserver the trackback URL is referring to and (2) by retrieving the web page located at the URL used in the trackback and checking if the page contains a link to your blog.
Version: 2.4 RC fork
Author: Michael Woehrer
Author URI: http://sw-guide.de
 	    ____________________________________________________
       |                                                    |
       |        Simple Trackback Validation Plugin          |
       |                © Michael Woehrer                   |
       |____________________________________________________|

	© Copyright 2006-2007 Michael Woehrer (michael dot woehrer at gmail dot com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

	----------------------------------------------------------------------------
	INSTALLATION, USAGE:
	Visit the plugin's homepage.
	--------------------------------------------------------------------------*/


////////////////////////////////////////////////////////////////////////////////
// Plugin options etc.
////////////////////////////////////////////////////////////////////////////////

global $stbv_val; // stores some values just at execution time, will not be saved.
global $stbv_opt;

$stbv_opt = array(
	'stbv_action' => 'moderation',
	'stbv_accuracy' => 'strict',
	'stbv_blogurls' => get_bloginfo('url'),
	'stbv_validateURL' => '1',
	'stbv_validateIP' => '1',
	'stbv_enablelog' => '',
	'stbv_addblockinfo' => '1',
	'stbv_moderrors' => '1',
);


////////////////////////////////////////////////////////////////////////////////
// Apply the plugin
////////////////////////////////////////////////////////////////////////////////

# 'preprocess_comment' filter is applied to the comment data prior to any other
# processing, when saving a new comment in the database. Function arguments:
# comment data array, with indices "comment_post_ID", "comment_author",
# "comment_author_email", "comment_author_url", "comment_content",
# "comment_type", and "user_ID".
add_filter('preprocess_comment', 'stbv_main', 1, 1);


########################################################################################################################
#					PART 1: Main Function(s)
########################################################################################################################

////////////////////////////////////////////////////////////////////////////////
// Main Function, called by 'preprocess_comment'
////////////////////////////////////////////////////////////////////////////////
function stbv_main($incomingTB) {

	global $stbv_opt, $stbv_val;

	####################################
	# We only deal with trackbacks
	####################################
	if ( $incomingTB['comment_type'] != 'trackback' ) return $incomingTB;

	####################################
	# Get trackback information
	####################################
 	$stbv_val['comment_author'] = $incomingTB['comment_author'];
 	$stbv_val['comment_author_url'] = $incomingTB['comment_author_url'];
	$stbv_val['comment_post_permalink'] = get_permalink($incomingTB['comment_post_ID']);
	$stbv_val['comment_post_permalink'] = preg_replace('/\/$/', '', $stbv_val['comment_post_permalink']); // Remove trailing slash
	$stbv_val['comment_post_ID'] = $incomingTB['comment_post_ID'];

	####################################
	# Get Plugin options
	####################################
	if ($stbv_opt['stbv_accuracy'] == 'open') {
		if ( is_string($stbv_opt['stbv_blogurls']) ) {
			if (strlen($stbv_opt['stbv_blogurls']) > 9) {
				$stbv_blogurlsArray = explode(' ', $stbv_opt['stbv_blogurls']);
			}
		}
	}

	####################################
	# 'Is Spam' flag is FALSE by default. Below we check several things
	# and this flag will become true as soon as we have any doubts.
	####################################
	$stbv_val['is_spam'] = false;

	####################################
	# If a Snoopy problem occurrs (Snoopy can't be loaded or a snoopy error
	# occurred), this variable will be set to TRUE
	####################################
	$stbv_val['snoopy_problem'] = false;

	####################################
	# If Author's URL is not correct, it will be considered as spam.
	####################################
	if (!$stbv_val['is_spam'] && substr($stbv_val['comment_author_url'], 0, 4) != 'http') {
		$stbv_val['log_info'][]['warning'] = 'Author\'s URL was found not to be correct';
		$stbv_val['is_spam'] = true;
	}

	####################################
	# Phase 1 (IP) -  Verify IP address
	####################################
	if (!$stbv_val['is_spam'] && ($stbv_opt['stbv_validateIP'] == '1') ) {
		$tmpSender_IP = preg_replace('/[^0-9.]/', '', $_SERVER['REMOTE_ADDR'] );

		$authDomainname = stbv_get_domainname_from_uri($stbv_val['comment_author_url']);
		$tmpURL_IP = preg_replace('/[^0-9.]/', '', gethostbyname($authDomainname) );

		if ( $tmpSender_IP != $tmpURL_IP) {
			$stbv_val['log_info'][]['info'] = 'Sender\'s IP address (' . $tmpSender_IP . ') not equal to IP address of host (' . $tmpURL_IP . ').';
			$stbv_val['is_spam'] = true;
		} else {
			$stbv_val['log_info'][]['info'] = 'IP address (' . $tmpSender_IP . ') was found to be valid.';
		}

	} elseif ( $stbv_opt['stbv_validateIP'] != '1' ) {
		$stbv_val['log_info'][]['info'] = 'IP address validation (Phase 1) skipped since it is not enabled in the plugin\'s options.';
	}

	####################################
	# Phase 2 (URL) -  Snoopy
	####################################
 	if ( $stbv_opt['stbv_validateURL'] == '1' ) {

		# Loading snoopy and create snoopy object. In case of
		# failure it is being considered as spam, just in case.
		if (!$stbv_val['is_spam'] && !stbv_loadSnoopy() ) {
			// Loading snoopy failed
			$stbv_val['log_info'][]['warning'] = 'Loading PHP Snoopy class failed. Phase 2 skipped.';
			$stbv_val['snoopy_problem'] = true;
		} else {
			// Create new Snoopy object
			if ( !class_exists('Snoopy') )
			{
				require_once( ABSPATH . WPINC . '/class-snoopy.php' );
			}
			$stbvSnoopy = new Snoopy;
		}

		# Fetch all URLs of the author's web page
		if (!$stbv_val['is_spam'] && !$stbv_val['snoopy_problem'] && ! @$stbvSnoopy->fetchlinks($stbv_val['comment_author_url']) ) {
				// Snoopy couldn't couldn't reach the target website, Snoopy error occurred, or something else...
				$stbv_val['log_info'][]['warning'] = 'Snoopy couldn\t find something on the source website or Snoopy error occurred. Phase 2 skipped.';
				$stbv_val['snoopy_problem'] = true;
		} else {
			$stbvAuthorUrlArray = $stbvSnoopy->results;
		}

		# Check if URL array contains link to website
		if (!$stbv_val['is_spam'] && !$stbv_val['snoopy_problem'] && is_array($stbvAuthorUrlArray) ) {
			$loopSuccess = false;

			foreach ($stbvAuthorUrlArray as $loopUrl) {

				// Remove trailing slash, "/trackback" and "/trackback/"
				$loopUrl = preg_replace('/(\/|\/trackback|\/trackback\/)$/', '', $loopUrl);


				if ( ($stbv_opt['stbv_accuracy'] == 'open') && (is_array($stbv_blogurlsArray)) ) {
					// We have more than one URL to be checked
					$loopInnerSuccess = false;

					foreach ($stbv_blogurlsArray as $loopOptionsURL) {
						// Check if the first chars of the URL of remote page contain URL of the options
						if (substr($loopUrl, 0, strlen($loopOptionsURL)) == $loopOptionsURL) {
							$loopInnerSuccess = true;
							break;
						}
					}
					if ( $loopInnerSuccess ) {
						$loopSuccess = true;
						break;
					}
				} else {
					// Strict mode or no URLs provided so we check strictly the permalink only!
					if ( $loopUrl == $stbv_val['comment_post_permalink'] ) {
						$loopSuccess = true;
						break;
					}
				}
			}
			if ( !$loopSuccess ) {
				$stbv_val['log_info'][]['info'] = 'The target URL was not found on the source website, therefore the trackback is considered to be spam.';
				$stbv_val['is_spam'] = true;
			} else {
				$stbv_val['log_info'][]['info'] = 'The trackback is considered to be valid: URL was found on the source website.';
			}
		}

	} else {	// if ( $stbv_opt['stbv_validateURL'] == '1' )
		$stbv_val['log_info'][]['info'] = 'URL validation (Phase 2) skipped since it is not enabled in the plugin\'s options.';
	}

	####################################
	# Now we know if we have a trackback spam or not.
	####################################
	if (($stbv_opt['stbv_moderrors'] == '1') && $stbv_val['snoopy_problem']) {
		if ($stbv_opt['stbv_enablelog'] == '1') stbv_log_addentry('Trackback placed into comment moderation due to an occurred problem while retrieving URLs from source website.');
		if ($stbv_opt['stbv_addblockinfo'] == '1')	$incomingTB['comment_author'] = '[BLOCKED BY STBV] ' . $incomingTB['comment_author'];
		add_filter('pre_comment_approved', create_function('$a', 'return \'0\';'));
		return $incomingTB;
	} elseif ( !$stbv_val['is_spam'] ) {
		# **** No Trackback Spam ***
		if ($stbv_opt['stbv_enablelog'] == '1') stbv_log_addentry('Trackback approved.');
		return $incomingTB;
	} else {
		# **** It is Trackback Spam ***
		# We put trackback in moderation queue, mark as spam or delete right away
		switch ($stbv_opt['stbv_action']) {
			case 'delete':
				if ($stbv_opt['stbv_enablelog'] == '1') stbv_log_addentry('Trackback discarded.');
				die('Your trackback has been rejected.');
				break;
			case 'spam':
				if ($stbv_opt['stbv_enablelog'] == '1') 	stbv_log_addentry('Trackback marked as spam.');
				if ($stbv_opt['stbv_addblockinfo'] == '1')	$incomingTB['comment_author'] = '[BLOCKED BY STBV] ' . $incomingTB['comment_author'];
				add_filter('pre_comment_approved', create_function('$a', 'return \'spam\';'));
				return $incomingTB;
				break;
			default:
				if ($stbv_opt['stbv_enablelog'] == '1') stbv_log_addentry('Trackback placed into comment moderation.');
				if ($stbv_opt['stbv_addblockinfo'] == '1')	$incomingTB['comment_author'] = '[BLOCKED BY STBV] ' . $incomingTB['comment_author'];
				add_filter('pre_comment_approved', create_function('$a', 'return \'0\';'));
				return $incomingTB;
		}

	}


} // function stbv_main()


////////////////////////////////////////////////////////////////////////////////
// Load the Snoopy class.
// Returns TRUE if class is successfully loaded, FALSE otherwise.
////////////////////////////////////////////////////////////////////////////////
function stbv_loadSnoopy() {
	if ( !class_exists('Snoopy') ) {

		if (@include_once( ABSPATH . WPINC . '/class-snoopy.php' )) {
			return true;
		} else {
			return false;
		}
	} else {
		return true;
	}
}


########################################################################################################################
#					PART 3: Miscellaneous Functions
########################################################################################################################


////////////////////////////////////////////////////////////////////////////////
// Retrieves domain name from URI.
// Input:  URI, e.g. http://www.site.com/bla/bla.php
// Output: domain name, e.g. www.site.com
////////////////////////////////////////////////////////////////////////////////
function stbv_get_domainname_from_uri($uri) {
    $exp1 = '/^(http|https|ftp)?(:\/\/)?([^\/]+)/i';
	preg_match($exp1, $uri, $matches);
	if (isset($matches[3])) {
		return $matches[3];
    } else {
		return '';
	}
}

?>