<?php
/*
Plugin Name: Feedburner Feed Replacement
Plugin URI: http://www.orderedlist.com/articles/wordpress_feedburner_plugin/
Description: Forwards all feed traffic to Feedburner while creating a randomized feed for Feedburner to pull from.
Author: Steve Smith
Author URI: http://www.orderedlist.com/
Version: 2.02
*/ 

$data = array(	
								'redirect' => false,
								'feedburner_url' => '',
								'random_source_url' => 'feedburner_' . rand(111111,999999)
						);
								
add_option('feedburner_settings',$data,'FeedBurner Plugin Options');

$feedburner_settings = get_option('feedburner_settings');

if ($feedburner_settings['step1']) {
	add_filter('mod_rewrite_rules','add_feedburner_feed');
}

if ($feedburner_settings['step2']) {
	add_filter('mod_rewrite_rules','add_feedburner_redirect');
}

function ol_add_feedburner_options_page() {
	if (function_exists('add_options_page')) {
		add_options_page('FeedBurner', 'FeedBurner', 8, basename(__FILE__), 'ol_feedburner_options_subpanel');
	}
}

function add_feedburner_feed($rules) {
	global $feedburner_settings;
	$home_root = parse_url(get_settings('home'));
	$home_root = trailingslashit($home_root['path']);
	$new_rules = '# Redirect FeedBurner to your own Feed' . "\n";
	$new_rules .= 'RewriteBase ' . $home_root . "\n";
	$new_rules .= 'RewriteRule ^' . $feedburner_settings['random_source_url'] . '/?$' . ' ' . $home_root . 'feed/ [R,L]' . "\n";
	$new_rules .= 'RewriteCond %{HTTP_USER_AGENT} ^FeedBurner.*$' . "\n";
	$new_rules .= 'RewriteCond %{REQUEST_FILENAME} !-f' . "\n";
	$new_rules .= 'RewriteCond %{REQUEST_FILENAME} !-d' . "\n";
	$new_rules .= 'RewriteRule . ' . $home_root . 'index.php [L]' . "\n";
	$new_rules .= '# Feed Redirect Rules will go here';
	$rules = str_replace('RewriteBase ' . $home_root, $new_rules, $rules);
	return $rules;
}

function add_feedburner_redirect($rules) {
	global $feedburner_settings;
	$home_root = parse_url(get_settings('home'));
	$home_root = trailingslashit($home_root['path']);
	$new_rules = '# These Rules redirect all feed Traffic to FeedBurner' . "\n";
	$new_rules .= 'RewriteBase ' . $home_root . "\n";
	$new_rules .= 'RewriteCond %{QUERY_STRING} ^feed=(feed|rdf|rss|rss2|atom)$' . "\n";
	$new_rules .= 'RewriteRule ^(.*)$ ' . $feedburner_settings['feedburner_url'] . ' [R,L]' . "\n";
	$new_rules .= 'RewriteRule ^(feed|rdf|rss|rss2|atom)/?(feed|rdf|rss|rss2|atom)?/?$ ' . $feedburner_settings['feedburner_url'] . ' [R,L]' . "\n";
	$new_rules .= 'RewriteRule ^wp-(feed|rdf|rss|rss2|atom).php ' . $feedburner_settings['feedburner_url'] . ' [R,L]' . "\n";
	$new_rules .= '# These are the standard WordPress Rules';
	$rules = str_replace('# Feed Redirect Rules will go here', $new_rules, $rules);
	return $rules;
}

function ol_feedburner_options_subpanel() {
	global $feedburner_settings, $_POST, $wp_rewrite;
	?>
	<div class="wrap">
	<?php
	
		if ($_POST['feedburner_url'] != '') { 
			$feedburner_settings['feedburner_url'] = $_POST['feedburner_url'];
			$feedburner_settings['step2'] = 1;
			update_option('feedburner_settings',$feedburner_settings);
		} elseif ($_POST['complete'] == 'true') {
			$feedburner_settings['complete'] = 1;
			update_option('feedburner_settings',$feedburner_settings);
		}
			
	
	  if ($_POST['deactivate'] == 'true') {
			$feedburner_settings['step1'] = 0;
			$feedburner_settings['step2'] = 0;
			$feedburner_settings['complete'] = 0;
			update_option('feedburner_settings',$feedburner_settings);
			
			remove_filter('mod_rewrite_rules','add_feedburner_redirect');
			remove_filter('mod_rewrite_rules','add_feedburner_feed');
			
			$home_path = get_home_path();

			generate_page_rewrite_rules();

			if ( (!file_exists($home_path.'.htaccess') && is_writable($home_path)) || is_writable($home_path.'.htaccess') )
				$writable = true;
			else
				$writable = false;
			
			save_mod_rewrite_rules();
			
			echo '<h2>Deactivate FeedBurner</h2><p>';
			if ($writable)
				_e('Permalink structure updated.  FeedBurner has been deactivated.');
			else {
				_e('You should update your .htaccess with the information below:'); 
				echo '<textarea rows="5" style="width: 98%;" name="rules">' . $wp_rewrite->mod_rewrite_rules() . '</textarea>';
			}
			echo '</p>';
			
		} elseif ($feedburner_settings['complete'] == 1) {
			echo '<h2>FeedBurner Redirection Active</h2><p>Your feed traffic is currently being redirected to FeedBurner at <strong>' . $feedburner_settings['feedburner_url'] . '</strong>.</p>';
			echo '<p><form action="" method="post"><input type="hidden" name="deactivate" value="true" /><input type="submit" value="Deactivate FeedBurner Redirection" /></form></p>';
			
		} elseif ($feedburner_settings['step1'] == 0 || (!$_POST['redirect'] && $feedburner_settings['step2'] == 0)) {
			$feedburner_settings['step1'] = 1;
			update_option('feedburner_settings',$feedburner_settings);
			add_filter('mod_rewrite_rules','add_feedburner_feed');
			
			$home_path = get_home_path();

			generate_page_rewrite_rules();

			if ( (!file_exists($home_path.'.htaccess') && is_writable($home_path)) || is_writable($home_path.'.htaccess') )
				$writable = true;
			else
				$writable = false;
			
			save_mod_rewrite_rules();
			
			echo '<h2>Step 1: Update Permalinks for FeedBurner Feed</h2><p>';
			if ($writable)
				_e('Permalink structure updated.');
			else {
				_e('You should update your .htaccess with the information below:'); 
				echo '<textarea rows="5" style="width: 98%;" name="rules">' . $wp_rewrite->mod_rewrite_rules() . '</textarea>';
			}
			echo '</p>';
			echo '<h2>Step 2: Setup Your FeedBurner Feed</h2>';
			echo '<p>If you don\'t already have one, <a href="http://www.feedburner.com/">create your FeedBurner account</a>. Point the source feed to:</p><p><strong>' . get_option('siteurl') . '/' . $feedburner_settings['random_source_url'] . '/</strong></p>
			<p>Once your FeedBurner account is ready to go with your new URI, put in your FeedBurner url (e.g. http://feeds.feedburner.com/myaccount/) 
			and click the button below to start redirecting your current feed traffic to FeedBurner.</p>
			<p><form action="" method="post"><input type="hidden" name="redirect" value="true" />Your FeedBurner URI: <input type="text" name="feedburner_url" value="' . htmlentities($feedburner_settings['feedburner_url']) . '" size="45" /><br /><input type="submit" value="My FeedBurner account is ready, begin redirecting." /></form></p>';
		} elseif ($feedburner_settings['step2'] == 1) {
			add_filter('mod_rewrite_rules','add_feedburner_redirect');
			
			$home_path = get_home_path();

			generate_page_rewrite_rules();

			if ( (!file_exists($home_path.'.htaccess') && is_writable($home_path)) || is_writable($home_path.'.htaccess') )
				$writable = true;
			else
				$writable = false;
			
			save_mod_rewrite_rules();
			
			echo '<h2>Step 3: Update Permalinks for Redirection</h2><p>';
			if ($writable) {
				$feedburner_settings['complete'] = 1;
				update_option('feedburner_settings',$feedburner_settings);
				echo 'Permalinks Updated.  Your blog is now setup with FeedBurner! <a href="' . get_option('siteurl') . '/feed/">Verify your redirected feed</a>.';
			 } else {
				_e('You should update your .htaccess with the information below:'); 
				echo '<textarea rows="5" style="width: 98%;" name="rules">' . $wp_rewrite->mod_rewrite_rules() . '</textarea>';
				echo '</p><p><form action="" method="post"><input type="hidden" name="complete" value="true" /><input type="submit" value="My Permalinks are Updated" /></form>';
			}
			echo '</p>';
		}

}



add_action('admin_menu', 'ol_add_feedburner_options_page');

?>