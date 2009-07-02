<?php
/*
Plugin Name: Bookmark Me
Plugin URI: http://www.semiologic.com/software/widgets/bookmark-me/
Description: Adds bookmark links to common social bookmarking sites.
Author: Denis de Bernardy
Version: 2.0
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


class bookmark_me
{
	#
	# init()
	#

	function init()
	{

	} # init()


	#
	# get_services()
	#

	function get_services()
	{
		return array(
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
			'buzzit' => array(
				'name' => 'Blogg-Buzz',
				'url' => 'http://www.blogg-buzz.com/submit.php?url=%permalink%'
				),
			'google' => array(
				'name' => 'Google',
				'url' => 'http://www.google.com/bookmarks/mark?op=add&amp;title=%title%&amp;bkmk=%permalink%'
				),
			'magnolia' => array(
				'name' => 'Ma.gnolia',
				'url' => 'http://ma.gnolia.com/beta/bookmarklet/add?title=%title%&amp;description=%title%&amp;url=%permalink%'
				),
			'muti' => array(
				'name' => 'muti',
				'url' => ' http://www.muti.co.za/submit?title=%title%&amp;url=%permalink%'
				),
			'netscape' => array(
				'name' => 'Netscape',
				'url' => 'http://www.netscape.com/submit/?T=%title%&amp;U=%permalink%'
				),
			'ppnow' => array(
				'name' => 'ppnow',
				'url' => 'http://www.ppnow.com/submit.php?url=%permalink%'
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
			'windows_live' => array(
				'name' => 'Windows Live',
				'url' => 'https://favorites.live.com/quickadd.aspx?marklet=1&amp;mkt=en-us&amp;title=%title%&amp;url=%permalink%&amp;top=1'
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
				'url' => 'http://www.semiologic.com/resources/blogging/help-with-social-bookmarking-sites/'
				)
			);
	} # get_services()


	#
	# default_services()
	#

	function default_services()
	{
		return array(
			'delicious',
			'digg',
			'furl',
			'reddit',
			'help'
			);
	} # default_services()


	#
	# display_links()
	#

	function display_links()
	{
		$title = urlencode($GLOBALS['post']->post_title);
		$permalink = urlencode(apply_filters('the_permalink', get_permalink()));
		$site_name = urlencode(get_bloginfo('sitename'));

		$options = get_settings('sem_bookmark_me_params');

		if ( !$options )
		{
			$options = array(
				'services' => bookmark_me::default_services(),
				'show_names' => true
				);

			update_option('sem_bookmark_me_params', $options);
		}

		foreach ( bookmark_me::get_services() as $site_id => $site_info )
		{
			if ( in_array($site_id, (array) $options['services']) )
			{
				if ( !isset($options['show_names']) || $options['show_names'] )
				{
					echo '<a'
						. ' href="'
							. bookmark_me::get_link(
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
						. ( $options['add_nofollow']
							? ' rel="nofollow"'
							: ''
							)
						. '>'
						. __($site_info['name'])
						. '</a> ';
				}
				else
				{
					echo '<a'
						. ' href="'
							. bookmark_me::get_link(
								$site_info['url'],
								$site_name,
								$title,
								$permalink
								)
							. '"'
						. ' class="noicon"'
						. ( $options['add_nofollow']
							? ' rel="nofollow"'
							: ''
							)
						. ' title="' . __($site_info['name']) . '"'
						. '>'
						. '<img src="'
								. trailingslashit(get_bloginfo('siteurl'))
								. 'wp-content/plugins/sem-bookmark-me/img/'
								. $site_id . '.gif'
								. '"'
								. ' alt="' . __($site_info['name']) . '"'
								. ' style="border: none; margin: 0px 1px;"'
								. ' width="16"'
								. ' height="16"'
								. ' />'
						. '</a> ';
				}
			}
		}
	} # display_links()


	#
	# get_link()
	#

	function get_link($link_struct, $site_name = '', $title = '', $permalink = '')
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
	} # end get_link()
} # bookmark_me



#
# the_bookmark_links()
#

function the_bookmark_links()
{
	bookmark_me::display_links();
} # end the_bookmark_links()


#
# spread_the_word()
#

function spread_the_word()
{
	$options = get_settings('sem_bookmark_me_params');

	if ( apply_filters('show_entry_spread_the_word', true)
		&& $options['services']
		)
	{
		$title = function_exists('get_caption') ? get_caption('spread_the_word') : __('Spread the word');
?><div class="spread_the_word">
<h2><?php echo $title; ?></h2>
<p><?php the_bookmark_links(); ?></p>
</div>
<?php
	}
} # end spread_the_word()

add_action('after_the_post', 'spread_the_word', 3);
add_action('display_entry_meta', 'spread_the_word', 15);

if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false )
{
	include_once dirname(__FILE__) . '/sem-bookmark-me-admin.php';
}
?>