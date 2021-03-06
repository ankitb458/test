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
		$wpdb->sem_docs = 'sem_docs';
		
		# install docs
		if ( get_option('sem_docs_version') < 2.02 )
		{
			sem_docs::init_db();
			sem_docs::update(true);
			
			update_option('sem_docs_version', 2.02);
		}
		# force update on domain.com/wp-admin/?update_docs
		elseif ( isset($_GET['update_docs']) && current_user_can('administrator') )
		{
			sem_docs::update(true);
		}
		# check for new docs in footer
		else
		{
			add_action('admin_footer', array('sem_docs', 'update'), 1000);
		}
		
		# Admin docs
		add_filter('contextual_help_list', array('sem_docs', 'display_admin_doc'), 100, 2);
		add_filter('contextual_help', array('sem_docs', 'strip_wp_links'));
		
		# Plugin docs
		if ( strpos($_SERVER['REQUEST_URI'], '/wp-admin/plugins.php') !== false )
		{
			add_filter('plugin_action_links', array('sem_docs', 'display_plugin_doc_link'), 0, 4);
			add_action('admin_print_scripts', array('sem_docs', 'register_scripts'));
			
			global $sem_plugin_docs;
			
			$_sem_plugin_docs = $wpdb->get_results("
				SELECT	doc_key as name,
						doc_content as content
				FROM	$wpdb->sem_docs
				WHERE	doc_cat = 'features'
				AND		doc_content <> ''
				AND		doc_version = '" . $wpdb->escape(sem_docs_version) . "'
				");
			
			foreach ( (array) $_sem_plugin_docs as $doc )
			{
				$sem_plugin_docs[$doc->name] = $doc->content;
			}
		}
		
		add_action('admin_init', array('sem_docs', 'admin_init'));
	} # init()
	
	
	#
	# admin_init()
	#
	
	function admin_init()
	{
		# Docs links
		add_action('admin_head', array('sem_docs', 'css'));
	} # admin_init()
	
	
	#
	# css()
	#
	
	function css()
	{
		echo '<link rel="stylesheet" type="text/css"'
		. ' href="'
			. trailingslashit(site_url())
			. 'wp-content/plugins/sem-docs/admin.css'
			. '"'
		. ' />' . "\n";

	} # css()
	
	
	#
	# register_scripts()
	#
	
	function register_scripts()
	{
		$plugin_path = plugin_basename(__FILE__);
		$plugin_path = preg_replace("/[^\/]+$/", '', $plugin_path);
		$plugin_path = '/wp-content/plugins/' . $plugin_path;
		
		wp_enqueue_script( 'sem_docs', $plugin_path . 'admin.js', array('jquery'),  '200801215' );
	} # register_scripts()
	
	
	#
	# init_db()
	#
	
	function init_db()
	{
		global $wpdb;
		
		$charset_collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			if ( ! empty($wpdb->charset) )
				$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
			if ( ! empty($wpdb->collate) )
				$charset_collate .= " COLLATE $wpdb->collate";
		}
		
		$old_table = $wpdb->prefix . 'sem_docs';
		
		$wpdb->query("
			DROP TABLE IF EXISTS $old_table;
		");
		
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
				) $charset_collate;
			");
		
		$wpdb->query("
			DELETE FROM $wpdb->sem_docs
			");
	} # init_db()
	
	
	#
	# display_admin_doc()
	#
	
	function display_admin_doc($help, $screen = null)
	{
		#dump($screen);
		$key = preg_replace("/[^a-z]+/i", '_', $screen);
		#dump($key);
		$doc = sem_docs::get_doc($key);
		
		if ( $doc && $doc->content )
		{
			if ( isset($help[$sceen]) && trim($help[$sceen]) !== '' )
			{
				$help[$screen] = $doc->content . '<hr />' . $help[$screen];
			}
			else
			{
				$help[$screen] = $doc->content;
			}
		}
		elseif ( $_SERVER['HTTP_HOST'] == 'localhost' )
		{
			if ( isset($help[$screen]) )
			{
				$help[$screen] = "<h5><b>&rarr; $key</b></h5>" . $help[$screen];
			}
			else
			{
				$help[$screen] = "<h5><b>&rarr; $key</b></h5>";
			}
		}
		
		return $help;
	} # display_admin_doc()
	
	
	#
	# display_plugin_doc_link()
	#
	
	function display_plugin_doc_link($action_links, $file = null, $plugin_data = null, $context = null)
	{
		$key = $file;
		$key = basename($file, '.php');
		$key = str_replace(array('sem-', 'wp-'), '', $key);
		$key = str_replace('-', '_', $key);
		
		global $sem_plugin_docs;
		
		if ( isset($sem_plugin_docs[$key]) )
		{
			add_action('after_plugin_row_' . $file,
				create_function('$in', "sem_docs::display_plugin_doc('$key', '$context');")
				);
			
			$extra = '<a href="#' . $key .'-help" class="hide-if-no-js plugin_doc_link">'
				. 'Help'
				. '</a>';
			
			array_unshift($action_links, $extra);
		}
		elseif ( $_SERVER['HTTP_HOST'] == 'localhost' )
		{
			$extra = $key;
			
			array_unshift($action_links, $extra);
		}
		
		return $action_links;
	} # display_plugin_doc_link()
	
	
	#
	# display_plugin_doc()
	#
	
	function display_plugin_doc($key, $context)
	{
		global $sem_plugin_docs;
		
		echo '<tr class="' . $context . ' plugin_doc hidden" id="' . $key . '-help-wrap">'
			. '<td>&nbsp;</td>'
			. '<td colspan="4">'
			. $sem_plugin_docs[$key]
			. '</td>'
			. '</tr>';
	} # display_plugin_doc()
	
	
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
	# update()
	#
	
	function update($force = false)
	{
		$options = sem_docs::get_options();
		global $allowedposttags;
		global $wpdb;
		
		#$force = true;
		#dump(sem_docs_version);
		
		foreach ( array('admin', 'features') as $cat )
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
			
			#dump($url);
			
			$xml = wp_remote_fopen($url);
		
			if ( $xml === false )
			{
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
			
			add_action('admin_footer', array('sem_docs', 'update'), 1000);
		}
		
		return $o;
	} # get_options()
	
	
	#
	# display_links()
	#
	
	function old_display_links()
	{
		echo '<div id="sem_docs__links">';
		
		
		
		echo '</div>';
	} # display_links()
	
	
	#
	# strip_wp_links()
	#
	
	function strip_wp_links($o)
	{
		$strip[] = '<h5>' . __('Other Help') . '</h5>';
		$strip[] = '<h5>' . __('Help') . '</h5>';
		$strip[] = '<div class="metabox-prefs">'
			. __('<a href="http://codex.wordpress.org/" onclick="window.open(this.href); return false;">Documentation</a>')
			. '<br />'
			. __('<a href="http://wordpress.org/support/" onclick="window.open(this.href); return false;">Support Forums</a>')
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