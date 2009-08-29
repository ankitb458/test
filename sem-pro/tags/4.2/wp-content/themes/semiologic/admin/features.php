<?php
#
# add_theme_skin_options_admin()
#

function add_theme_features_admin()
{
	add_submenu_page(
		'themes.php',
		__('Features'),
		__('Features'),
		7,
		str_replace("\\", "/", basename(__FILE__)),
		'display_theme_features_admin'
		);
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

	echo '<input type="hidden"'
		. ' name="action"'
		. ' value="update_theme_features"'
		. '>';

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
			);

	if ( !function_exists('get_site_option') )
	{
	$features['WordPress Features']
		= array(
			'advanced_cache' => array(
					'Advanced Cache',
					'An advanced cache for sites with lots of traffic. It comes in handy when you get slashdotted, among other things. Configure and activate via Options / WP-Cache. Note that the cache script is loaded before WordPress; It will remain active even when the WP-Cache plugin is deactivated.'
					),
			/*
			'cc_license' => array(
					'Creative Commons License',
					'Easily add a CC license to your site.'
					),
			*/
			'exec_php' => array(
					'php in Pages',
					'Allow php in Pages.'
					),
			'rss_aggregator' => array(
					'RSS Aggregator',
					'A tool to aggregate feeds and republish feeds from elsewhere. Configure via Options / WP-Autoblog.'
					),
			);
	}

	$features['Theme Features']
		= array(
			'contact_form' => array(
					'Contact Form',
					'Easily add a contact form on a static page.'
					),
			'flickr_album' => array(
					'Flickr Album',
					'Display your flickr gallery from your site.',
					function_exists('get_site_option') ? false : null
					),
			'sidebar_tile' => array(
					'Sidebar Page',
					'Stick the contents of your \'Sidebar\' page in your sidebar (use only when widgets are disabled).',
					function_exists('get_site_option') ? false : null
					),
			'sidebar_widgets' => array(
					'Sidebar Widgets',
					'Drag and drop widgets into your sidebars.',
					function_exists('get_site_option') ? true : null
					),
			'theme_archives' => array(
					'Archives as Title Lists',
					'Display archives (date archives and categories) as title lists.'
					),
			);

	$features['Extra Sidebar Widgets']
		= array(
			'automatic_translation' => array(
					'Automatic Translations',
					'This is keyword spam. Use only if it sounds like a recommendable practice to you.',
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
			'exec_php_widget' => array(
					'php Widgets',
					'Allow php code in php widgets.',
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
					'Configure the Fuzzy Comments Widget via Options / Recent Comments.'
					),
			'subscribe_me' => array(
					'Feed Subscription Buttons',
					'Add feed subscription buttons'
					),
			'tag_cloud' => array(
					'Tag Cloud',
					'Create a tag cloud in your sidebar using WordPress\' categories'
					),
			);

	$features['Entry Features']
		= array(
			'author_image' => array(
					'Author Images',
					'Add author images to posts and articles. Do use, drop .jpg images named after the login of authors (<i>e.g.</i> admin.jpg) into the wp-content/authors folder.',
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
					'Automatically generated related search queries.',
					function_exists('get_site_option') ? false : null
					),
			'related_tags' => array(
					'Related Tags',
					'Automatically generated related technoratir tags.',
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
					'Podcasting',
					'An audio player (.mp3) for your site. Configure via Options / Audio Player.',
					function_exists('get_site_option') ? true : null
					),
			'videocasting' => array(
					'Videocasting',
					'A flash movie player (.flv) for your site. Configure via Options / WP-FLV.',
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
					'A feature-full Wysiwyg Editor with image uploads, podcast and videocast buttons, an inline ad unit inserter, and a full screen edit mode. It also allows to paste from Word when using Internet Explorer.',
					function_exists('get_site_option') ? true : null
					),
			);

	$features['Comment Features']
		= array(
			'do_follow' => array(
					'Do Follow Links',
					'WordPress adds a nofollow attribute to links in comments. Doing so kills the very nature of the web. This plugin will remove the pesky attribute.'
					),
			'gravatars' => array(
					'Commenter Gravatars',
					'Add <a href="http://www.gravatar.com">gravatars</a> to your comments.'
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
					'Akismet is the anti-spam offered by Automattic. An API key required. Use this if anything starts to get passed Hashcash.'
					),
			'hashcash' => array(
					'Hashcash',
					'Hashcash is a Turing Machine based anti-spam plugin that is set to remain 100% efficient for a long time (working around it is too computationally costly).'
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
					'Sticks a static front page on your blog. Create a static page (Write / Page) called \'Home\' and you\'re done.'
					),
			'post_count' => array(
					'Customizable Post Lists',
					'A tool to easily control the length and the order of your post lists. Configure via Options / CQS.'
					),
			);

	$features['Permalink Features']
		= array(
			'enforce_permalink' => array(
					'Enforce Permalink',
					'Enforce the proper permalink for your entries.',
					function_exists('get_site_option') ? true : null
					),
			'kill_index_php' => array(
					'Enforce www. and index.php preferences',
					'Enforce www. and index.php preferences. Do not use this if you are using a permalink structure with index.php in it.',
					function_exists('get_site_option') ? false : null
					),
			'non_unique_slugs' => array(
					'Allow Non-Unique Slugs',
					'Use this only if your permalink structure will not potentially result in slug conflicts.',
					function_exists('get_site_option') ? true : null
					),
			'track_old_slugs' => array(
					'Autoredirect on slug change',
					'Detect entry slug changes, and automatically set up a 301 redirect to the new address.',
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
			'smart_links' => array(
					'Smart Links',
					'Preinsert links into your entries. <a href="http://www.semiologic.com/software/smart-link/">More details</a>.'
					),
			'smart_pings' => array(
					'Smart Pings',
					'Ping only when necessary, and do so in a smart way.',
					function_exists('get_site_option') ? true : null
					),
			'theme_meta' => array(
					'Automatic Meta Tags',
					'Automatically generate meta keywords (post categories) and description (post excerpt) tags.'
					),
			'theme_title' => array(
					'Optimized Page Title',
					'Search Engine Optimized Page Title. This is hard-coded in the Semiologic Pro theme.',
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
			'performancing_metrics' => array(
					'Performancing Metrics',
					'Statistics for your blog, via <a href="http://performancing.com/metrics/">Performancing Metrics</a>. Configured via your WordPress user\'s name and email address.'
					),
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

	if ( !function_exists('get_site_option') )
	{
	$features['Cron Jobs']
		= array(
			'cron_dashboard' => array(
					'Refresh Dashboard Cache',
					'Periodically refresh the Dashboard.'
					),
			'cron_email' => array(
					'Check Site\'s Mailbox',
					'Periodically check the site\'s mailbox. You\'ll need to configure posting by email under Options / Writing.'
					),
			'cron_links' => array(
					'Update Blogroll Cache',
					'Periodically check the links in your Link Manager for updates.'
					),
			'cron_moderation' => array(
					'Send Moderation Notification Digests',
					'Send new comment and moderation notifications as digests.'
					),
			'cron_pings' => array(
					'Send Pings for Future Posts',
					'Ping update services later when publishing posts in the future.'
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
	if ( !function_exists('theme_feature_is_active') )
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
?><h3><label for="all_<?php echo str_replace(',', '_', $feature_ids); ?>"><input type="checkbox"
		id="all_<?php echo str_replace(',', '_', $feature_ids); ?>"
		<?php if ( function_exists('theme_feature_is_active') ) : ?>		onchange="if ( this.checked ) check_all('<?php echo $feature_ids; ?>'); else uncheck_all('<?php echo $feature_ids; ?>');"
		<?php else : ?>		disabled="disabled"
		<?php endif; ?>		/>
		<?php echo $feature_set_name; ?></label></h3>
<?php
		foreach ( $feature_set as $feature_id => $feature_description )
		{
			if ( !( isset($feature_description[2]) && !$feature_description[2] ) )
			{
?>	<p>
		<label for="feature_key[<?php echo $feature_id; ?>]">
		<input type="checkbox"
			id="feature_key[<?php echo $feature_id ?>]" name="feature_id[]"
			value="<?php echo $feature_id; ?>"
			<?php
				if ( !function_exists('theme_feature_is_active')
					|| isset($feature_description[2])
					) :
			?>			disabled="disabled"
			<?php
				endif;
				if ( ( function_exists('theme_feature_is_active')
						&& theme_feature_is_active($feature_id)
						)
					|| ( isset($feature_description[2])
						&& $feature_description[2]
						)
					) :
			?>			checked="checked"
			<?php endif; ?>			/>&nbsp;<?php
			if ( ( function_exists('theme_feature_is_active')
						&& theme_feature_is_active($feature_id)
						)
					|| ( isset($feature_description[2])
						&& $feature_description[2]
						)
					) : ?>			<strong><u><?php echo $feature_description[0]; ?></u></strong><br />
			<?php echo $feature_description[1]; ?>			<?php elseif ( isset($feature_description[2])
						&& !$feature_description[2]
						) : ?>			<span style="color: dimgray;"><strong><?php echo $feature_description[0]; ?></strong><br />
		<?php echo $feature_description[1]; ?></span>
			<?php else : ?>			<strong><?php echo $feature_description[0]; ?></strong><br />
			<?php echo $feature_description[1]; ?>			<?php endif; ?>			</label>
	</p>
<?php
			}
		}
		echo '<div style="clear: both;"></div>';

		echo '<div class="submit">';
		echo '<input type="submit" value="' . __('Update Options') . ' &raquo;" />';
		echo '</div>';
	}
} # end display_theme_features()

add_action('display_theme_features', 'display_theme_features');
?>