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
		
//		add_action('shutdown', array('sem_docs', 'update'));
		add_action('admin_head', array('sem_docs', 'update'));
		
		add_action('admin_head', array('sem_docs', 'css'));

		$plugin_path = plugin_basename(__FILE__);
		$plugin_path = preg_replace("/[^\/]+$/", '', $plugin_path);
		$plugin_path = '/wp-content/plugins/' . $plugin_path;
		
		wp_enqueue_script( 'sem_docs', $plugin_path . 'admin.js', array('sack'),  '20080414' );

		add_action('show_user_profile', array('sem_docs', 'user_prefs'));
		add_action('personal_options_update', array('sem_docs', 'save_user_prefs'));
		
		add_action('init', array('sem_docs', 'init_docs'));
		
		if ( isset($_GET['update_docs']) && current_user_can('administrator') )
		{
			sem_docs::update(true);
		}
	} # init()
	
	
	#
	# init_docs()
	#
	
	function init_docs()
	{
		global $wpdb;
		
		sem_docs::init_db();
		
//		$results = $wpdb->query("SELECT * FROM $wpdb->sem_docs");
//		if (empty($results))
			sem_docs::update(false);
			
		$user_prefs = sem_docs::get_user_prefs();
		
		if ( $user_prefs['show_docs'] )
		{
			add_action('admin_footer', array('sem_docs', 'display_doc'));
		}
			
		if ( $user_prefs['show_tips'] )
		{
			sem_docs::ajax_tip();
			add_action('admin_footer', array('sem_docs', 'display_tip'));
		}
		
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
	# init_db)
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
			
	} # init_db)
	
	
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
	# display_tip()
	#
	
	function display_tip()
	{
		@session_start();
		
		if ( $_SESSION['sem_tip_showed'] ) return;
		
		if ( $tip = sem_docs::next_tip() )
		{
			echo '<div id="sem_tips__wrapper">';

			echo '<div style="float: right;">'
				. '<a href="javascript:;" onclick="sem_tips.close();">'
					. __('Close')
					. '</a>'
				. '</div>';

			echo '<div>'
				. '<h3>' . __('Did you know?') . '</h3>'
				. '<div id="sem_tips__content">'
				. $tip->content
				. '</div>'
				. '</div>';

			echo '<div style="clear: both;"></div>';

			echo '<div style="float: right">'
				. '<a href="javascript:;" onclick="sem_tips.prev();">'
					. __('Previous')
					. '</a>'
				. ' / '
				. '<a href="javascript:;" onclick="sem_tips.next();">'
					. __('Next')
					. '</a>'
				. '</div>';

			echo '<div>'
				. '<label>'
				. '<input type="checkbox"'
					. ' id="sem_tips__show"'
					. ' checked="checked"'
					. ' />'
					. '&nbsp;'
					. __('Show guru tips at startup')
					. '</label>'
				. '</div>';

			echo '<div style="clear: both;"></div>';

			echo '</div>';
		}

		$_SESSION['sem_tip_showed'] = true;
	} # display_tip()
	
	
	#
	# next_tip()
	#
	
	function next_tip()
	{
		global $wpdb;
		global $user_ID;
		
		$user_prefs = sem_docs::get_user_prefs();
		$key = (string) $user_prefs['tip_id'][sem_docs_version];
		
		$tip = $wpdb->get_row("
				SELECT	doc_key as \"key\",
					doc_name as name,
					doc_excerpt as excerpt,
					doc_content as content
			FROM	$wpdb->sem_docs
			WHERE	doc_cat = 'tips'
			AND		doc_version = '" . $wpdb->escape(sem_docs_version) . "'
			AND		doc_key > '" . $wpdb->escape($key) . "'
			ORDER BY doc_key
			LIMIT 1
			");

		if ( !$tip )
		{
			$tip = $wpdb->get_row("
				SELECT	doc_key as \"key\",
						doc_name as name,
						doc_excerpt as excerpt,
						doc_content as content
				FROM	$wpdb->sem_docs
				WHERE	doc_cat = 'tips'
				AND		doc_version = '" . $wpdb->escape(sem_docs_version) . "'
				ORDER BY doc_key
				LIMIT 1
				");
		}
		
		if ( $tip )
		{
			$user_prefs['tip_id'][sem_docs_version] = $tip->key;
			update_usermeta($user_ID, 'sem_docs', $user_prefs);
		}
		
		return $tip;
	} # next_tip()
	
	
	#
	# prev_tip()
	#
	
	function prev_tip()
	{
		global $wpdb;
		global $user_ID;

		$user_prefs = sem_docs::get_user_prefs();
		$key = (string) $user_prefs['tip_id'][sem_docs_version];

		$tip = $wpdb->get_row("
			SELECT	doc_key as \"key\",
					doc_name as name,
					doc_excerpt as excerpt,
					doc_content as content
			FROM	$wpdb->sem_docs
			WHERE	doc_cat = 'tips'
			AND		doc_version = '" . $wpdb->escape(sem_docs_version) . "'
			AND		doc_key < '" . $wpdb->escape($key) . "'
			ORDER BY doc_key DESC
			LIMIT 1
			");

		if ( !$tip )
		{
			$tip = $wpdb->get_row("
				SELECT	doc_key as \"key\",
						doc_name as name,
						doc_excerpt as excerpt,
						doc_content as content
				FROM	$wpdb->sem_docs
				WHERE	doc_cat = 'tips'
				AND		doc_version = '" . $wpdb->escape(sem_docs_version) . "'
				ORDER BY doc_key DESC
				LIMIT 1
				");
		}
		
		if ( $tip )
		{
			$user_prefs['tip_id'][sem_docs_version] = $tip->key;
			update_usermeta($user_ID, 'sem_docs', $user_prefs);
		}
		
		return $tip;
	} # prev_tip()
	
	
	#
	# ajax_tip()
	#
	
	function ajax_tip()
	{
		if ( !$_REQUEST['sem_tips'] ) return;
		
		switch ( $_REQUEST['sem_tips'] )
		{
		case 'next':
		case 'prev':
			if ( $_REQUEST['sem_tips'] == 'next' && ( $tip = sem_docs::next_tip() )
				|| $_REQUEST['sem_tips'] == 'prev' && ( $tip = sem_docs::prev_tip() )
				)
			{
				$tip = $tip->content;
			}
			else
			{
				$tip = '<p>No tip found</p>';
			}

			$tip = addslashes($tip);
			$tip = str_replace('</', '<\/', $tip);
			$tip = str_replace("\n", "\\\n", $tip);
			echo "sem_tips.load('" . $tip . "');";
			die;

		case 'stop':
			global $user_ID;

			$user_prefs = get_usermeta($user_ID, 'sem_docs');
			$user_prefs['show_tips'] = false;
			update_usermeta($user_ID, 'sem_docs', $user_prefs);
			die;
		}
	} # ajax_tip()
	
	
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
		
		foreach ( array('admin', 'tips', 'feature_sets', 'features') as $cat )
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
			
				$options[$cat][sem_docs_version] = time() - 3600 * 24 * 13; # try in 3 days
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
				$options[$cat][sem_docs_version] = time() - 3600 * 24 * 13; # try in 3 days
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
	# user_prefs()
	#
	
	function user_prefs()
	{
		global $user_ID;
		
		$prefs = array(
			'show_docs' => array(
				'label' => 'Admin Docs',
				'desc' => 'Show admin area docs'
				),
			'show_tips' => array(
				'label' => 'Guru Tips',
				'desc' => 'Show guru tips at start-up'
				)
			);
		
		$user_prefs = get_usermeta($user_ID, 'sem_docs');
		
		echo '<h3>'
			. 'Semiologic Theme Docs'
			. '</h3>';
		
		echo '<table class="form-table">';
		
		foreach ( $prefs as $key => $details )
		{
			echo '<tr valign="top">'
				. '<th scope="row">'
				. $details['label']
				. '</th>'
				. '<td>'
				. '<label>'
				. '<input type="checkbox" name="sem_docs[' . $key . ']"'
					. ( $user_prefs[$key]
						? ' checked="checked"'
						: ''
						)
					. ' />'
				. '&nbsp;'
				. $details['desc']
				. '</td>'
				. '</tr>' . "\n";
		}
		
		echo '</table>';
	} # user_prefs()
	
	
	#
	# save_user_prefs()
	#
	
	function save_user_prefs()
	{
		global $user_ID;
		
		if ( $_POST['user_id'] == $user_ID )
		{
			$user_prefs = get_usermeta($user_ID, 'sem_docs');
			
			foreach ( array('show_docs', 'show_tips') as $key )
			{
				$user_prefs[$key] = isset($_POST['sem_docs'][$key]);
			}
			
			update_usermeta($user_ID, 'sem_docs', $user_prefs);
		}
	} # save_user_prefs()
	
	
	#
	# get_user_prefs()
	#
	
	function get_user_prefs()
	{
		global $user_ID;
		
		if ( !( $user_prefs = get_usermeta($user_ID, 'sem_docs') ) )
		{
			if ( $old_prefs = get_usermeta($user_ID, 'sem_tips') )
			{
				$show_tips = $old_prefs['show_tips'];
			}
			else
			{
				$show_tips = true;
			}
			
			$user_prefs = array(
				'show_docs' => true,
				'show_tips' => $show_tips,
				'tip_id' => array( sem_docs_version => false )
				);
			
			update_usermeta($user_ID, 'sem_docs', $user_prefs);
		}
		
		return $user_prefs;
	} # get_user_prefs()
	
	
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
				. '<a href="http://www.semiologicforums.com">'
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
} # sem_docs

sem_docs::init();
?>