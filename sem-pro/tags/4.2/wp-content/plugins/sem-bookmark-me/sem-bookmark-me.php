<?php
/*
Plugin Name: Bookmark Me
Plugin URI: http://www.semiologic.com/software/bookmark-me/
Description: <a href="http://www.semiologic.com/legal/license/">Terms of use</a> &bull; <a href="http://www.semiologic.com/software/bookmark-me/">Doc/FAQ</a> &bull; <a href="http://forum.semiologic.com">Support forum</a> &#8212; Lists links to common social bookmarking sites.
Author: Denis de Bernardy
Version: 1.7
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/


Hat Tips
--------

	* Mike Koepke <http://www.mikekoepke.com>
**/

$bookmark_sites = array(
	'delicious' => array(
		'name' => 'del.icio.us',
		'url' => 'http://del.icio.us/post?title=%title%&amp;url=%permalink%'
		),
	'digg' => array(
		'name' => 'Digg',
		'url' => 'http://digg.com/submit?phase=2&amp;title=%title%&amp;url=%permalink%'
		),
	'furl' => array(
		'name' => 'Furl',
		'url' => 'http://www.furl.net/storeIt.jsp?t=%title%&amp;u=%permalink%'
		),
	'reddit' => array(
		'name' => 'Reddit',
		'url' => 'http://reddit.com/submit?title=%title%&amp;url=%permalink%'
		),
	'ask' => array(
		'name' => 'Ask',
		'url' => 'http://myjeeves.ask.com/mysearch/BookmarkIt?v=1.2&amp;t=webpages&amp;title=%title%&amp;url=%permalink%'
		),
	'blinklist' => array(
		'name' => 'BlinkList',
		'url' => 'http://www.blinklist.com/index.php?Action=Blink/addblink.php&amp;Title=%title%&amp;Description=&amp;Url=%permalink%'
		),
	'blogmarks' => array(
		'name' => 'blogmarks',
		'url' => 'http://blogmarks.net/my/new.php?mini=1&amp;simple=1&amp;title=%title%&amp;url=%permalink%'
		),
	'google' => array(
		'name' => 'Google',
		'url' => 'http://www.google.com/bookmarks/mark?op=add&amp;title=%title%&amp;bkmk=%permalink%'
		),
	'magnolia' => array(
		'name' => 'Ma.gnolia',
		'url' => 'http://ma.gnolia.com/beta/bookmarklet/add?title=%title%&amp;description=%title%&amp;url=%permalink%'
		),
	'rawsugar' => array(
		'name' => 'RawSugar',
		'url' => 'http://www.rawsugar.com/tagger/?tttl=%title%&amp;turl=%permalink%'
		),
	'rojo' => array(
		'name' => 'Rojo',
		'url' => 'http://www.rojo.com/submit/?title=%title%&amp;url=%permalink%'
		),
	'shadows' => array(
		'name' => 'Shadows',
		'url' => 'http://www.shadows.com/features/tcr.htm?title=%title%&amp;url=%permalink%'
		),
	'simpy' => array(
		'name' => 'Simpy',
		'url' => 'http://www.simpy.com/simpy/LinkAdd.do?title=%title%&amp;href=%permalink%'
		),
	'socializer' => array(
		'name' => 'Socializer',
		'url' => 'http://ekstreme.com/socializer/?title=%title%&amp;url=%permalink%'
		),
	'spurl' => array(
		'name' => 'Spurl',
		'url' => 'http://www.spurl.net/spurl.php?title=%title%&amp;url=%permalink%'
		),
	'stumbleupon' => array(
		'name' => 'StumbleUpon',
		'url' => 'http://www.stumbleupon.com/submit?title=%title%&amp;url=%permalink%'
		),
	'tailrank' => array(
		'name' => 'Tailrank',
		'url' => 'http://tailrank.com/share/?link_href=%permalink%&amp;title=%title%'
		),
	'technorati' => array(
        'name' => 'Technorati',
        'url' => 'http://www.technorati.com/faves?add=%permalink%'
		),
	'wists' => array(
		'name' => 'Wists',
		'url' => 'http://wists.com/r.php?c=&amp;title=%title%&amp;r=%permalink%'
		),
	'yahoo' => array(
		'name' => 'Yahoo!',
		'url' => 'http://myweb2.search.yahoo.com/myresults/bookmarklet?title=%title%&amp;popup=true&amp;u=%permalink%'
		),
	'help' => array(
		'name' => 'Help',
		'url' => 'http://www.semiologic.com/resources/help-with-social-bookmarking-sites/'
		)
	);

$bookmark_services = array(
	'delicious',
	'digg',
	'furl',
	'reddit',
	'help'
	);


#
# the_bookmark_links()
#

function the_bookmark_links()
{
	$title = urlencode(the_title(null, null, false));
	$permalink = urlencode(str_replace('&amp;', '&', apply_filters('the_permalink', get_permalink())));
	$site_name = urlencode(get_bloginfo('sitename'));

	$options = get_settings('sem_bookmark_me_params');

	if ( !$options )
	{
		$options = array(
			'services' => $GLOBALS['bookmark_services']
			);

		update_option('sem_bookmark_me_params', $options);
	}

	foreach ( $GLOBALS['bookmark_sites'] as $site_id => $site_info )
	{
		if ( in_array($site_id, (array) $options['services']) )
		{
			if ( !isset($options['show_names']) || $options['show_names'] )
			{
				echo '<a'
					. ' href="'
						. get_bookmark_link(
							$site_info['url'],
							$site_name,
							$title,
							$permalink
							)
						. '"'
					. ' style="'
						. 'padding-left: 20px;'
						. ' background: url('
							. trailingslashit(get_bloginfo('siteurl'))
							. 'wp-content/plugins/sem-bookmark-me/img/'
							. $site_id . '.gif'
							. ') center left no-repeat;'
							. '"'
						. ' class="noicon"'
					. '>'
					. __($site_info['name'])
					. '</a> ';
			}
			else
			{
				echo '<a'
					. ' href="'
						. get_bookmark_link(
							$site_info['url'],
							$site_name,
							$title,
							$permalink
							)
						. '"'
						. ' class="noicon"'
						. ' title="' . __($site_info['name']) . '"'
					. '>'
					. '<img src="'
							. trailingslashit(get_bloginfo('siteurl'))
							. 'wp-content/plugins/sem-bookmark-me/img/'
							. $site_id . '.gif'
							. '"'
							. ' alt="' . __($site_info['name']) . '"'
							. ' style="border: none; margin: 0px 1px;"'
							. ' />'
					. '</a> ';
			}
		}
	}
} # end the_bookmark_links()


#
# get_bookmark_link()
#

function get_bookmark_link($link_struct, $site_name = '', $title = '', $permalink = '')
{
	return str_replace(
		array(
			'%site_name%',
			'%title%',
			'%permalink%'
			),
		array(
			$site_name,
			$title,
			$permalink
			),
		$link_struct
		);
} # end get_bookmark_link()


#
# spread_the_word()
#

function spread_the_word()
{
	$title = function_exists('get_caption') ? get_caption('spread_the_word') : __('Spread the word');
?><div class="spread_the_word">
<h2><?php echo $title; ?></h2>
<p><?php the_bookmark_links(); ?></p>
</div>
<?php
} # end spread_the_word()

add_action('after_the_post', 'spread_the_word', 3);
add_action('display_entry_meta', 'spread_the_word', 15);

if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false )
{
	include_once dirname(__FILE__) . '/sem-bookmark-me-admin.php';
}
?>