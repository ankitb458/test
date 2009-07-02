<?php
#
# add_theme_skin_options_admin()
#

function add_theme_features_admin()
{
	if ( !function_exists('get_site_option') )
	{
		add_submenu_page(
			'themes.php',
			__('Features'),
			__('Features'),
			'switch_themes',
			str_replace("\\", "/", basename(__FILE__)),
			'display_theme_features_admin'
			);
	}
} # end add_theme_features_admin()

add_action('admin_menu', 'add_theme_features_admin');


#
# display_theme_features_admin()
#

function display_theme_features_admin()
{
	if ( !empty($_POST)
		&& isset($_POST['action'])
		&& $_POST['action'] == 'update_theme_features'
		)
	{
		do_action('update_theme_features');

		echo "<div class=\"updated\">\n"
			. "<p>"
				. "<strong>"
				. __('Options saved.')
				. "</strong>"
			. "</p>\n"
			. "</div>\n";
	}

	echo '<form method="post" action="">';

	if ( function_exists('wp_nonce_field') ) wp_nonce_field('sem_features');

	echo '<input type="hidden"'
		. ' name="action"'
		. ' value="update_theme_features"'
		. ' />';

	echo '<div class="wrap">';
	echo '<h2>' . __('Features') . '</h2>';
	do_action('display_theme_features');
	echo '</div>';

	echo '</form>';
} # end display_theme_features_admin()


#
# get_theme_features()
#

function get_theme_features()
{
	$features = array();

	$features['WordPress Tweaks and Fixes']
		= array(
			'autolink_uri' => array(
					'Autolink uri',
					'Automatically find and link unanchored uri.'
					),
			'easy_order' => array(
					'Easy Category, Link and Page order management.',
					'Interfaces to easily manage the category, link and page order using drag/drop.'
					),
			'exernal_links' => array(
					'External Links',
					'Identify external links. You can highlight them with an icon, and open them in new windows. Configure via Options / External Links.'
					),
			'fancy_excerpt' => array(
					'Sentence Aware Excerpts',
					'Generate fancy, sentence aware excerpts automatically when no excerpt is entered.'
					),
			'favicon' => array(
					'Favicon',
					'Notify browsers that your site has an favicon that displays alongside your site\'s name when bookmarked. Configure via Options / Favicon Head.'
					),
			'improved_search' => array(
					'Improved Search Engine',
					'An improved search tool for WordPress, that uses MySQL\'s full text indexing functionalities.',
					function_exists('get_site_option') ? false : null
					),
			'kill_frames' => array(
					'Kill Frames',
					'Kill attempts to open the site in frames.'
					),
			'more_in_feeds' => array(
					'More link in feeds',
					'Make the \'More\' link work in feeds.'
					),
			'remove_fancy_quotes' => array(
					'Kill Fancy Quotes',
					'Convert fancy quotes to normal quotes, so as to not break copy and paste of source code.'
					),
			'absolute_urls' => array(
					'Absolute URLs',
					'Convert all relative URLs in posts to absolute URLs in RSS feeds.'
					),
			);

	$features['WordPress Features']
		= array(
			'advanced_cache' => array(
					'Advanced Cache',
					'An advanced cache for sites with lots of traffic. It comes in handy when you get slashdotted, among other things. Configure and activate via Options / WP-Cache. Note that the cache script is loaded before WordPress; It will remain active even when the WP-Cache plugin is deactivated.'
					),
			);

	$features['Theme Features']
		= array(
			'contact_form' => array(
					'Contact Form',
					'Easily add a contact form on a static page.'
					),
			/*
			'flickr_album' => array(
					'Flickr Album',
					'Display your flickr gallery from your site.',
					function_exists('get_site_option') ? false : null
					),
			*/
			'sidebar_widgets' => array(
					'Sidebar Widgets',
					'Drag and drop widgets into your sidebars.',
					true
					),
			'theme_archives' => array(
					'Archives as Title Lists',
					'Display archives (date archives and categories) as title lists.'
					),
			);

	$features['Extra Sidebar Widgets']
		= array(
			'around_this_date' => array(
					'Around This Date',
					'A sidebar widget to display old posts from the same week in the past.'
					),
			'automatic_translation' => array(
					'Automatic Translations',
					'This is keyword spam. Use only if it sounds like a recommendable practice to you. This feature requires fancy urls (Options / Permalinks) and an Apache server (it won\'t work on Windows).',
					function_exists('get_site_option') ? false : null
					),
			'delicious' => array(
					'Del.icio.us Links',
					'Display your del.icio.us links in your sidebar.'
					),
			'event_manager' => array(
					'Event Manager',
					'Configure via Options / Countdown.',
					function_exists('get_site_option') ? false : null
					),
			'flickr_widget' => array(
					'Flickr Widget',
					'A sidebar widget to display your flickr RSS feed.'
					),
			'google_search' => array(
					'Google Search',
					'Search your site via Google.'
					),
			'newsletter_manager' => array(
					'Newsletter Subscription Form',
					'It will work with <a href="http://www.semiologic.com/go/aweber">aWeber</a> and with any list manager that can answer a list-subscribe@domain.com query.',
					function_exists('get_site_option') ? false : null
					),
			'paypal_donate' => array(
					'Paypal Donate',
					'Readily accept donations via Paypal.'
					),
			'poll_manager' => array(
					'Poll Manager',
					'Configure via Manage / Democracy.',
					function_exists('get_site_option') ? false : null
					),
			'recent_posts' => array(
					'Recent Posts',
					'Configure the Fuzzy Post Widget via Options / Recent Posts.'
					),
			'recent_updates' => array(
					'Recent Updates',
					'Configure the Fuzzy Updates Widget via Options / Recent Updates.'
					),
			'recent_comments' => array(
					'Recent Comments',
					'Configure the Fuzzy Comments Widget via Options / Recent Comments.'
					),
			'recent_links' => array(
					'Recent Links',
					'Configure the Fuzzy Comments Widget via Options / Recent Links.'
					),
			'subscribe_me' => array(
					'Feed Subscription Buttons',
					'Add feed subscription buttons'
					),
/*
			'tag_cloud' => array(
					'Tag Cloud',
					'Create a tag cloud in your sidebar using WordPress\' categories'
					),
*/
			);

	$features['Entry Features']
		= array(
			'author_image' => array(
					'Author Images',
					'Add author images to posts and articles. To use, add your author image under Users / Your Profile.',
					function_exists('get_site_option') ? is_site_admin() : null
					),
			'blogpulse_link' => array(
					'Follow-Ups via BlogPulse',
					'Track your post follow-ups via <a href="http://www.blogpulse.com">BlogPulse</a>.'
					),
			'cosmos_link' => array(
					'Follow-Ups via Technorati',
					'Track your post follow-ups via <a href="http://www.technorati.com">Technorati</a>.'
					),
			'related_entries' => array(
					'Related Entries',
					'Automatically generated related entries after posts and articles.',
					function_exists('get_site_option') ? false : null
					),
			'related_entries4feeds' => array(
					'Related Entries for Feeds',
					'Automatically generated related entries in RSS feeds.',
					function_exists('get_site_option') ? false : null
					),
			'related_searches' => array(
					'Related Searches',
					'Automatically generated related search queries. NB: If your site has too few posts, this can be considered duplicate content.',
					function_exists('get_site_option') ? false : null
					),
			'related_tags' => array(
					'Related Tags',
					'Automatically generated links to related technorati tags. NB: While fun, this merely contributes outgoing links with no SEO benefit.',
					function_exists('get_site_option') ? false : null
					),
			'bookmark_me' => array(
					'Social Bookmarking',
					'Add social bookmarking buttons after each posts.'
					),
			);

	$features['Entry Editing']
		= array(
			'php_markdown' => array(
					'php Markdown Extra Syntax',
					'Lets you use php Markdown Extra Syntax in your blog entries. See the <a href="http://daringfireball.net/projects/markdown/syntax">Markdown</a> and the <a href="http://www.michelf.com/projects/php-markdown/extra/">Markdown Extra</a> for more details.'
					),
			'podcasting' => array(
					'Podcasting and Videocasting',
					'Adds players and support for .mp3, .m4a, .flv, .swf, .mp4, .m4v and .mov formats for your site. Configure via Options / Mediacaster.',
					function_exists('get_site_option') ? true : null
					),
			'star_rating' => array(
					'Star Rating',
					'Add star ratings to your entries via [rating:3.5], [rating:2/10], and compile an overall rating via [rating:overall].',
					function_exists('get_site_option') ? false : null
					),
			'wysiwyg_editor'
				=> array(
					'Advanced Wysiwyg Editor',
					'A feature-full Wysiwyg Editor with image uploads, podcast and videocast buttons, an inline ad unit inserter, and a full screen edit mode. It also allows to paste from Word when using Internet Explorer. Be sure to activate the rich text editor in your user preferences, under Users / Your Profile.',
					function_exists('get_site_option') ? true : null
					),
			);

	$features['Comment Features']
		= array(
			'do_follow' => array(
					'Do Follow Links',
					'WordPress adds a nofollow attribute to links in comments. Doing so kills the very nature of the web. This plugin will remove the pesky attribute.'
					),
			'subscribe2comments' => array(
					'Comment Subscriptions',
					'Send email notifications to subscribed users whenever a new comment is added.'
					),
			);

	$features['Comment Spam']
		= array(
			'akismet' => array(
					'Akismet',
					'Akismet puts new comments through a centralized spam detector before validating comments. It requires a wordpress.com API key.'
					),
			'hashcash' => array(
					'Hashcash',
					'Hashcash is a Turing Machine based anti-spam plugin that is set to remain 100% efficient for a long time (working around it is too computationally costly).'
					),
			'tb_validator' => array(
					'Trackback Validator',
					'Enforce that sites who send you trackbacks are indeed linking to you.'
					),
			);

	if ( !function_exists('get_site_option') )
	{
	$features['Comment Administration']
		= array(
			'enhance_comment_workflow' => array(
					'Comment Workflow Enhancements',
					'A collection of fixes and enhancements to the comment workflow, <i>e.g.</i> visitors can no longer post comments using your favorite nickname.'
					),
			'enhance_comment_admin' => array(
					'Comment Admin Enhancements',
					'A collection of tools to enhance the default comment administration features. See Manage / Comments, and Manage / Comments Status.'
					),
			);
	}

	$features['Front Page Management']
		= array(
			'opt_in_front_page' => array(
					'Opt-in Front Page',
					'Control what goes into your front page on an opt-in basis. Create a category (Manage / Categories) called \'Blog\' and only posts within it will appear on your front page and in your main feed.'
					),
			'static_front_page' => array(
					'Static Front Page',
					'Create a static page (Write / Page) called \'Home\' to use this feature, and another called \'Blog\' for your blog. You can safely rename both pages once they are created.'
					),
			'post_count' => array(
					'Customizable Post Lists',
					'A tool to easily control the length and the order of your post lists. Configure via Options / CQS.'
					),
			);

	$features['Permalink Features']
		= array(
			'enforce_permalink' => array(
					'Enforce Permalink Structure',
					'Enforce the proper permalink for your entries. Configure under Options / Permalink Redirect.',
					function_exists('get_site_option') ? true : null
					),
			'non_unique_slugs' => array(
					'Allow Non-Unique Slugs',
					'Use this only if your permalink structure will not potentially result in slug conflicts.',
					function_exists('get_site_option') ? true : null
					),
			);

	$features['Search Engine Optimization']
		= array(
			'google_sitemap' => array(
					'Google Sitemap',
					'Improve the way Googlebot crawls your site.',
					function_exists('get_site_option') ? false : null
					),
			'silo_site' => array(
					'Silo Web Design',
					'<a href="http://www.semiologic.com/software/widgets/silo/">Silo functionalities</a> for sites built using static pages.'
					),
			'smart_links' => array(
					'Smart Links',
					'Preinsert links into your entries. <a href="http://www.semiologic.com/software/smart-link/">More details</a>.'
					),
			'social_poster' => array(
					'Auto Social Poster',
					'Requires the <a href="http://www.semiologic.com/go/social-poster/">Social Poster plugin</a>. Automatically add content on your site to social bookmarking sites.',
					!file_exists(ABSPATH . 'wp-content/plugins/social-poster') ? false : null
					),
			'theme_meta' => array(
					'Meta Tags',
					'Meta keyword and meta description functions.',
					true
					),
			'theme_title' => array(
					'Optimized Page Title',
					'Search Engine Optimized Page Title.',
					true
					),
			);

	$features['Site Statistics']
		= array(
			'feedburner' => array(
					'FeedBurner',
					'Statistics for your RSS feed, via <a href="http://www.feedburner.com">Feedburner</a>. Configure via Options / Permalink Redirect.'
					),
			'google_analytics' => array(
					'Google Analytics',
					'Statistics for your blog, via <a href="http://analytics.google.com">Google Analytics</a>. Configure via Options / Google Analytics.',
					function_exists('get_site_option') ? true : null
					),
			'hitslink' => array(
					'HitsLink',
					'Statistics for your blog, via <a href="http://www.semiologic.com/go/hitslink">HitsLink</a>. Configure via Options / HitsLink.'
					),
			/*
			'performancing_metrics' => array(
					'Performancing Metrics',
					'Statistics for your blog, via <a href="http://performancing.com/metrics/">Performancing Metrics</a>. Configured via your WordPress user\'s name and email address.'
					),
			*/
			);

	if ( !function_exists('get_site_option') )
	{
	$features['Site Monitization']
		= array(
			'ad_spaces' => array(
					'Ad Spaces',
					'Ad Spaces allows to manage advertisement real estate on your site. <a href="http://www.semiologic.com/software/ad-space/">More details</a>.'
					),
			'book_library' => array(
					'Book Library',
					'Adds a book library feature and widget, with an amazon search feature that allows to readily add your affiliate ID. See Now Reading under Write, Manage, and Options.'
					),
			/*
			'shopping_cart' => array(
					'Shopping Cart',
					'Adds a shopping car and the relevant widgets. Manage under E-Commerce once active.'
					),
			*/
			);
	}

	if ( !function_exists('get_site_option') || is_site_admin() )
	{
	$features['Promote Semiologic']
		= array(
			'sem_affiliate' => array(
					'Affiliate Links',
					'Easily add your Semiologic ID to your links. <a href="http://www.semiologic.com/partners/">More details.</a>',
					function_exists('get_site_option') ? is_site_admin() : null
					),
			'theme_credits' => array(
					'Theme Credits',
					'Give credits to the theme and the skin authors.'
					),
			);
	}

	if ( !function_exists('get_site_option') )
	{
	$features['Site Management']
		= array(
			'admin_menu'
				=> array(
					'Admin Menu in your site area',
					'Adds an admin menu to your blog, for convenient access to the most commonly accessed admin areas.'
					),
			'db_backup' => array(
					'Database Backups via WordPress',
					'If your host doesn\'t provide you with a backup feature (many do), WordPress can do this for you. Configure and use via Manage / Backups.'
					),
			'role_manager' => array(
					'Role Manager',
					'A tool to create and manage WordPress user roles. Use via Users / Roles. Note that the Role Manager plugin changes WordPress internal variables; Its changes remains active after it is deactivated.'
					),
			'site_unavailable' => array(
					'Site Unavailable',
					'Mark your site as "Unavailable" while the admin area remains accessible by logged in users.'
					),
			);
	}

	return $features;
} # end get_theme_features()


#
# display_theme_features()
#

function display_theme_features()
{
	if ( !sem_pro )
	{
		pro_feature_notice();
	}
?><script type="text/javascript">
function check_all(id_list)
{
	id_list = id_list.split(',');

	for ( i = 0; i < id_list.length; i++ )
	{
		if ( !document.getElementById('feature_key[' + id_list[i] + ']').disabled )
		{
			document.getElementById('feature_key[' + id_list[i] + ']').checked = true;
		}
	}
}

function uncheck_all(id_list)
{
	id_list = id_list.split(',');

	for ( i = 0; i < id_list.length; i++ )
	{
		if ( !document.getElementById('feature_key[' + id_list[i] + ']').disabled )
		{
			document.getElementById('feature_key[' + id_list[i] + ']').checked = false;
		}
	}
}
</script>
<?php
	$all_features = get_theme_features();

	foreach ( $all_features as $feature_set_name => $feature_set )
	{
		#asort($feature_set);

		$feature_ids = '';

		foreach ( $feature_set as $feature_id => $feature_description )
		{
			if ( !( isset($feature_description[2]) && !$feature_description[2] ) )
			{
				$feature_ids .= ( $feature_ids ? ',' : '' )
					. $feature_id;
			}
		}

		echo '<h3>'
			. '<label for="all_' . str_replace(',', '_', $feature_ids) . '">'
			. '<input type="checkbox"'
				. '  id="all_' . str_replace(',', '_', $feature_ids) . '"'
				. ( function_exists('theme_feature_is_active')
					? ( ' onchange="if ( this.checked ) check_all(\'' . $feature_ids . '\'); else uncheck_all(\'' . $feature_ids. '\');"' )
					: ' disabled="disabled"'
					)
				. ' />'
			. '&nbsp;'
			. $feature_set_name
			. '</label>'
			. '</h3>';


		foreach ( $feature_set as $feature_id => $feature_description )
		{
			echo '<p>'
				. '<label for="feature_key[' . $feature_id . ']">'
				. '<input type="checkbox"'
					. ' id="feature_key[' . $feature_id . ']" name="feature_id[]"'
					. ' value="' . $feature_id . '"'
					. ( ( !sem_pro
						|| ( isset($feature_description[2]) )
						)
						? ' disabled="disabled"'
						: ''
						)
					. ( ( ( sem_pro
							&& theme_feature_is_active($feature_id)
							)
						|| ( isset($feature_description[2])
							&& $feature_description[2]
							)
						)
						? ' checked="checked"'
						: ''
						)
					. ' />'
					. '&nbsp;'
					. ( ( ( sem_pro
						&& theme_feature_is_active($feature_id)
						)
						|| ( isset($feature_description[2])
							&& $feature_description[2]
							)
						)
						? ( '<strong>'
							. '<u>'
							. $feature_description[0]
							. '</u>'
							. '</strong>'
							. '<br />'
							. $feature_description[1]
							)
						: ( '<strong>'
							. $feature_description[0]
							. '</strong>'
							. '<br />'
							. $feature_description[1]
							)
						)
					. '</label>'
					. '</p>';

		}
		echo '<div style="clear: both;"></div>';

		echo '<div class="submit">';
		echo '<input type="submit" value="' . __('Update Options') . ' &raquo;" />';
		echo '</div>';
	}
} # end display_theme_features()

add_action('display_theme_features', 'display_theme_features');
?>