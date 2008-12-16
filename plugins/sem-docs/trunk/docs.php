<?php

class sem_docs
{
	#
	# init()
	#

	function init()
	{
		if ( !defined('sem_version') ) return;
		
		define('sem_docs_version', preg_replace("/^(\d+(?:\.\d+)).*$/", "$1", sem_version));
		
		global $wpdb;
		$wpdb->sem_docs = $wpdb->prefix . 'sem_docs';
		
		#
		# Notice
		# ------
		#
		# we can catch the screen ID using this hook:
		#
		# add_filter('screen_meta_screen', array('sem_docs', 'test'));
		#
		# and we can append to the docs using this one:
		#
		# add_filter('contextual_help_list', array('sem_docs', 'test'));
		#
		
		add_action('admin_footer', array('sem_docs', 'update'));
		
		add_action('admin_head', array('sem_docs', 'css'));

		$plugin_path = plugin_basename(__FILE__);
		$plugin_path = preg_replace("/[^\/]+$/", '', $plugin_path);
		$plugin_path = '/wp-content/plugins/' . $plugin_path;
		
		wp_enqueue_script( 'sem_docs', $plugin_path . 'admin.js', array('sack'),  '20080414' );

		add_action('init', array('sem_docs', 'init_docs'));
		
		if ( isset($_GET['update_docs']) && current_user_can('administrator') )
		{
			sem_docs::update(true);
		}
		
		add_filter('contextual_help', array('sem_docs', 'strip_wp_links'));
	} # init()
	
	
	#
	# init_docs()
	#
	
	function init_docs()
	{
		global $wpdb;
		
		sem_docs::init_db();
		
		#$results = $wpdb->query("SELECT * FROM $wpdb->sem_docs");
		#if (empty($results))
		#	sem_docs::update(false);
		
		remove_action('admin_footer', 'hello_dolly');
		add_action('admin_footer', array('sem_docs', 'display_links'));
	} # init_docs()
	
	
	#
	# css()
	#
	
	function css()
	{
		echo '<link rel="stylesheet" type="text/css"'
		. ' href="'
			. trailingslashit(get_option('siteurl'))
			. 'wp-content/plugins/sem-docs/admin.css'
			. '"'
		. ' />' . "\n";

	} # css()
	
	
	#
	# init_db()
	#
	
	function init_db()
	{
		global $wpdb;
		
		$wpdb->query("
			CREATE TABLE IF NOT EXISTS $wpdb->sem_docs (
				doc_id			int PRIMARY KEY AUTO_INCREMENT,
				doc_cat			varchar(128) NOT NULL DEFAULT '',
				doc_key			varchar(128) NOT NULL DEFAULT '',
				doc_version		varchar(32) NOT NULL DEFAULT '',
				doc_name		varchar(255) NOT NULL DEFAULT '',
				doc_excerpt		text NOT NULL DEFAULT '',
				doc_content		text NOT NULL DEFAULT '',
				UNIQUE ( doc_cat, doc_key, doc_version )
				);
			");
			
	} # init_db()
	
	
	#
	# display_doc()
	#
	
	function display_doc()
	{
		# default key
		$menu = $_SERVER['PHP_SELF'];
		$menu = preg_replace("/^.*\/wp-admin\/|\.php$/i", '', $menu);
		$page = $_GET['page'];
		$page = preg_replace("/\.php$/i", '', $page);
		$key = $menu . ( $page ? ( '_' . $page ) : '' );
		$key = str_replace(array('-', '/'), '_', $key);
		$key = str_replace('options_general_', 'options_', $key);
		$key = preg_replace("/^admin_|_admin$|sem_/", '', $key);
		$key = preg_replace("/(.{2,})_\\1/", "$1", $key);

		switch ( $key )
		{
		case 'post':
			$key = 'post_new';
			break;
		case 'page':
			$key = 'page_new';
			break;
		}
		
		if ( ( $doc = sem_docs::get_doc($key) )
			&& $doc->content
			)
		{
			echo '<div id="sem_docs__more" class="button-secondary">'
				. '<b>'
				. '<a href="javascript:;" class="button-secondary"'
				. ' onclick="sem_docs.show();"'
				. '>' . ( ( $key != 'index' )
						? ( 'Show Documentation' )
						: ( 'Get Started With Semiologic Pro' )
						) . '</a>'
				. '</b>'
				. '</div>';

			echo '<div id="sem_docs__less" class="button-secondary" style="display: none;">'
				. '<b>'
				. '<a href="javascript:;" class="button-secondary"'
				. ' onclick="sem_docs.hide(); "'
				. '>' . 'Hide Documentation' . '</a>'
				. '</b>'
				. '</div>';

			echo '<div id="sem_docs__wrapper">'
				. '<div class="wrap">'
				. '<div id="sem_docs__content">'
				. '<h2>' . $doc->name . '</h2>'
				. $doc->content
				. '<div style="clear:both;"></div>'
				. '<div style="float: right;">'
					. '<b>'
					. '<a href="javascript:;"'
					. ' onclick="sem_docs.hide(); "'
					. '>' . 'Hide Documentation on ' . $doc->name . '</a>'
					. '</b>'
					. '</div>'
				. '<div style="clear:both;"></div>'
				. '</div>'
				. '</div>'
				. '</div>';
		}
		elseif ( $_SERVER['HTTP_HOST'] == 'localhost' )
		{
			echo '<div id="sem_docs__error" class="button-secondary">'
				. '<b>'
				. 'No Documentation on ' . $key
				. '</b>'
				. '</div>';
		}
	} # display_doc()
	
	
	#
	# get_doc()
	#
	
	function get_doc($key)
	{
		global $wpdb;
		
		return $wpdb->get_row("
			SELECT	doc_name as name,
					doc_excerpt as excerpt,
					doc_content as content
			FROM	$wpdb->sem_docs
			WHERE	doc_cat = 'admin'
			AND		doc_version = '" . $wpdb->escape(sem_docs_version) . "'
			AND		doc_key = '" . $wpdb->escape($key) . "'
			");
	} # get_doc()
	
	
	#
	# get_features()
	#
	
	function get_features()
	{
		global $wpdb;
		
		foreach ( array('feature_sets', 'features') as $cat )
		{
			$$cat = $wpdb->get_results("
				SELECT	doc_key as \"key\",
						doc_name as name,
						doc_excerpt as excerpt,
						doc_content as content
				FROM	$wpdb->sem_docs
				WHERE	doc_cat = '" . $wpdb->escape($cat) . "'
				AND		doc_version = '" . $wpdb->escape(sem_docs_version) . "'
				");
		}
		
		return array($feature_sets, $features);
	} # get_features()
	
	
	#
	# update()
	#
	
	function update($force = false)
	{
		$options = sem_docs::get_options();
		global $allowedposttags;
		global $wpdb;
		
		foreach ( array('admin', 'feature_sets', 'features') as $cat )
		{
			$url = 'http://rest.semiologic.com/1.0/docs/?cat=sem_' . $cat . '&version=' . sem_docs_version;
		
			if ( !$force )
			{
				if ( intval($options[$cat][sem_docs_version]) + 3600 * 24 * 14 >= time() )
				{
					continue;
				}
				elseif ( $last_updated = $options[$cat][sem_docs_version] )
				{
				
					$url .= '&last_modified=' . date('Y-m-d', $last_updated);
				}
			}
		
			if ( !class_exists('sem_http') )
			{
				include dirname(__FILE__) . '/http.php';
			}

			$xml = sem_http::get($url);
		
			if ( $xml === false )
			{
				$errors = (array) $_SESSION['sem_err'];
				$err = array_pop($errors);
			
				$id = md5( uniqid( microtime() ) );
			
				echo '<div id="' . $id .'">' . $err . '</div>'
					. '<script type="text/javascript">sem_docs.prepend(\'' . $id .'\', \'wpbody\');</script>';
			
				$options[$cat][sem_docs_version] = time() - 3600 * 24 * 13; # try in 1 days
				update_option('sem_docs', $options);
			
				continue;
			}
		
			if ( preg_match("/
					<messages>
					(.*)
					<\/messages>
					/isUx",
					$xml
					)
				)
			{
				$options[$cat][sem_docs_version] = time() - 3600 * 24 * 13; # try in 1 days
				update_option('sem_docs', $options);
			
				continue;
			}
		
			preg_match_all("/
				<doc>
				\s*
				<key>
					(.*)
				<\/key>
				\s*
				<name>
					(.*)
				<\/name>
				\s*
				(?:
				<excerpt>
					<!\[CDATA\[
					(.*)
					\]\]>
				<\/excerpt>
				)?
				\s*
				<content>
					<!\[CDATA\[
					(.*)
					\]\]>
				<\/content>
				\s*
				<\/doc>
				/isUx",
				$xml,
				$matches,
				PREG_SET_ORDER
				);

			foreach ( $matches as $match )
			{
				$key = $match[1];
				$name = $match[2];
				$excerpt = $match[3];
				$content = $match[4];

				$updated[] = $name;

				foreach ( array('key', 'name', 'excerpt', 'content') as $var )
				{
					$$var = trim(wp_kses($$var, $allowedposttags));
				}
			
				if ( $doc_id = $wpdb->get_var("
					SELECT	doc_id
					FROM	$wpdb->sem_docs
					WHERE	doc_cat = '" . $wpdb->escape($cat) . "'
					AND		doc_version = '" . $wpdb->escape(sem_docs_version) . "'
					AND		doc_key = '" . $wpdb->escape($key) . "'
					"))
				{
					$wpdb->query("
						UPDATE	$wpdb->sem_docs
						SET		doc_name = '" . $wpdb->escape($name) . "',
								doc_excerpt = '" . $wpdb->escape($excerpt) . "',
								doc_content = '" . $wpdb->escape($content) . "'
						WHERE	doc_cat = '" . $wpdb->escape($cat) . "'
						AND		doc_version = '" . $wpdb->escape(sem_docs_version) . "'
						AND		doc_key = '" . $wpdb->escape($key) . "'
						");
				}
				else
				{
					$wpdb->query("
						INSERT INTO	$wpdb->sem_docs (
								doc_cat,
								doc_version,
								doc_key,
								doc_name,
								doc_excerpt,
								doc_content
								)
						VALUES	(
								'" . $wpdb->escape($cat) . "',
								'" . $wpdb->escape(sem_docs_version) . "',
								'" . $wpdb->escape($key) . "',
								'" . $wpdb->escape($name) . "',
								'" . $wpdb->escape($excerpt) . "',
								'" . $wpdb->escape($content) . "'
								)
						");
				}
			}

			# spread individual doc updates a bit to avoid updating everything each time
			$options[$cat][sem_docs_version] = time() + rand(0, 72) * 3600;
			update_option('sem_docs', $options);
		}
	} # update()
	
	
	#
	# get_options()
	#
	
	function get_options()
	{
		if ( !is_admin() ) return false;
		
		if ( ( $o = get_option('sem_docs') ) === false )
		{
			$o = array();
			update_option('sem_docs', $o);
			
			add_action('admin_footer', array('sem_docs', 'update'), 0);
		}
		
		return $o;
	} # get_options()
	
	
	#
	# display_links()
	#
	
	function display_links()
	{
		echo '<div id="sem_docs__links">';
		
		if ( current_user_can('administrator') )
		{
			echo '<a href="'
					. ( ( $api_key = get_option('sem_api_key') )
						? ( 'http://www.semiologic.com/members/sem-pro/?user_key=' . $api_key )
						: 'http://www.semiologic.com/members/sem-pro/'
						)
						. '">'
				. 'Semiologic Pro v.' . sem_version
				. '</a>'
				. ' &bull; '
				. '<a href="http://www.semiologic.com/resources/">'
				. __('Resources')
				. '</a>'
				. ' &bull; '
				. '<a href="http://forum.semiologic.com">'
				. __('Forums')
				. '</a>';
		}
		else
		{
			echo '<a href="http://www.getsemiologic.com">'
				. 'Semiologic Pro v.' . sem_version
				. '</a>'
				. ' &bull; '
				. '<a href="http://www.semiologic.com/resources/">'
				. __('Resources')
				. '</a>';
		}
		
		echo '</div>';
	} # display_links()
	
	
	#
	# strip_wp_links()
	#
	
	function strip_wp_links($o)
	{
		$strip = '<div class="metabox-prefs">'
			. __('<a href="http://codex.wordpress.org/" target="_blank">Documentation</a>')
			. '<br />'
			. __('<a href="http://wordpress.org/support/" target="_blank">Support Forums</a>')
			. "</div>\n";
		
		$o = str_replace($strip, '', $o);
		
		return $o;
	} # strip_wp_links()
	
	
	#
	# test()
	#
	
	function test($foo, $bar = null)
	{
		dump($foo, $bar);
		return $foo;
	} # test()
} # sem_docs

sem_docs::init();
?>