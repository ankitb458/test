<?php

if (!defined('WP_BB_CWD'))
	die('');

// Is the user-agent a known spambot?
// Occurs at the beginning of the string
$wp_bb_spambots_str0 = array(
	"8484 Boston Project",	// video poker/porn spam
	"autoemailspider",	// spam harvester
	"Digger",		// spam harvester
	"ecollector",		// spam harvester
	"EmailCollector",	// spam harvester
	"Email Extractor",	// spam harvester
	"Email Siphon",		// spam harvester
	"EmailSiphon",		// spam harvester
	"grub crawler",		// misc comment/email spam
	"Jakarta Commons",	// custommised spambots
	"Java 1.",		// definitely a spammer
	"libwww-perl",		// spambot scripts
	"LWP",			// exploited boxes
	"Microsoft URL",	// spam harvester
	"Missigua",		// spam harvester
	"Mozilla ",		// forum exploits
	"Shockwave Flash",	// spam harvester
	"User-Agent: ",		// spam harvester
	"Wordpress Hash Grabber",// malicious software
	"www.weblogs.com",	// referrer spam (not the real www.weblogs.com)
);
// Occurs anywhere in the string
$wp_bb_spambots_str = array(
	"Bad Behavior Test",	// Add this to your user-agent to test BB
	"compatible ; MSIE",	// misc comment/email spam
	"DTS Agent",		// misc comment/email spam
	"Gecko/25",		// revisit this in 500 years
	"grub-client",		// search engine ignores robots.txt
	"Indy Library",		// misc comment/email spam
	".NET CLR 1)",		// free poker, etc.
	"POE-Component-Client",	// free poker, etc.
	"WISEbot",		// spam harvester
	"WISEnutbot",		// spam harvester
);
// Regex matching
$wp_bb_spambots_reg = array(
	"/^[A-Z]{10}$/",	// misc email spam
	"/^Mozilla...0$/i",	// fake user agent/email spam
//	"/MSIE.*Windows XP/",	// misc comment spam
);

if (!strcmp($wp_bb_request_method, "POST") && empty($wp_bb_http_user_agent)) {
	wp_bb_spammer("A User-Agent is required but none was provided.");
}

foreach ($wp_bb_spambots_str0 as $wp_bb_spambot) {
	$pos = stripos($wp_bb_http_user_agent, $wp_bb_spambot);
	if ($pos !== FALSE && $pos == 0) {
		wp_bb_spammer("User-Agent beginning with '" . $wp_bb_spambot . "' prohibited");	// does not return
	}
}
foreach ($wp_bb_spambots_str as $wp_bb_spambot) {
	if (stripos($wp_bb_http_user_agent, $wp_bb_spambot) !== FALSE) {
		wp_bb_spammer("User-Agent containing '" . $wp_bb_spambot . "' prohibited");	// does not return
	}
}
foreach ($wp_bb_spambots_reg as $wp_bb_spambot) {
	if (preg_match($wp_bb_spambot, $wp_bb_http_user_agent)) {
		wp_bb_spammer("User-Agent matching regex '" . $wp_bb_spambot . "' prohibited");	// does not return
	}
}

?>
