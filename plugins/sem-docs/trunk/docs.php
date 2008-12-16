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
		
		# install docs
		if ( !get_option('sem_docs_version') )
		{
			sem_docs::init_db();
			sem_docs::update(true);
			
			update_option('sem_docs_version', 2);
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
		add_action('after_plugin_row', array('sem_docs', 'display_plugin_doc'), 0, 2);
		
		# Docs links
		remove_action('admin_footer', 'hello_dolly');
		add_action('admin_footer', array('sem_docs', 'display_links'));
		add_action('admin_head', array('sem_docs', 'css'));
	} # init()
	
	
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
		
		$wpdb->query("
			DELETE FROM $wpdb->sem_docs
			");
	} # init_db()
	
	
	#
	# display_admin_doc()
	#
	
	function display_admin_doc($help, $screen = null)
	{
		$key = str_replace('-', '_', $screen);
		
		$doc = sem_docs::get_doc('admin', $key);
		
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
	# display_plugin_doc()
	#
	
	function display_plugin_doc($file, $plugin_data = null)
	{
		$key = $file;
		$key = basename($file, '.php');
		$key = str_replace(array('sem-', 'wp-'), '', $key);
		$key = str_replace('-', '_', $key);
		
		$doc = sem_docs::get_doc('features', $key);
		
		if ( $doc && $doc->content )
		{
			echo '<tr><td colspan="5" class="plugin-update">';
			echo $doc->content;
			echo '</tr></td>';
		}
		elseif ( $_SERVER['HTTP_HOST'] == 'localhost' )
		{
			echo '<tr><td colspan="5" align="right">';
			echo "<b>&rarr; $key</b>";
			echo '</tr></td>';
		}
	} # display_plugin_doc()
	
	
	#
	# get_doc()
	#
	
	function get_doc($cat, $key)
	{
		global $wpdb;
		
		return $wpdb->get_row("
			SELECT	doc_name as name,
					doc_excerpt as excerpt,
					doc_content as content
			FROM	$wpdb->sem_docs
			WHERE	doc_cat = '" . $wpdb->escape($cat) . "'
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
			
			add_action('admin_footer', array('sem_docs', 'update'), 1000);
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
		$strip[] = '<h5>' . __('Other Help') . '</h5>';
		$strip[] = '<h5>' . __('Help') . '</h5>';
		$strip[] = '<div class="metabox-prefs">'
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