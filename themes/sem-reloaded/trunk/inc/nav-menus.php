<?php
class sem_nav_menus
{
	#
	# init()
	#
	
	function init()
	{
		foreach ( array(
			'save_post',
			'delete_post',
			'switch_theme',
			'update_option_active_plugins',
			'update_option_show_on_front',
			'update_option_page_on_front',
			'update_option_page_for_posts',
			'update_option_sidebars_widgets',
			'update_option_sem5_options',
			'update_option_sem6_options',
			'generate_rewrite_rules',
			) as $hook )
		{
			add_action($hook, array('sem_nav_menus', 'clear_cache'));
		}
	} # init()
	
	
	#
	# display()
	#

	function display($area)
	{
		$number = $area;
		if ( is_page() )
		{
			$page_id = intval($GLOBALS['wp_query']->get_queried_object_id());
		}
		else
		{
			$page_id = false;
			if ( is_home() && !is_paged() )
			{
				$context = 'home';
			}
			elseif ( !is_search() && !is_404() )
			{
				$context = 'blog';
			}
			else
			{
				$context = 'search';
			}
		}
		
		# front end: serve cache if available
		if ( !is_admin() )
		{
			if ( is_page() )
			{
				if ( in_array(
						'_sem_nav_menus_cache_' . $number,
						(array) get_post_custom_keys($page_id)
						)
					)
				{
					$cache = get_post_meta($page_id, '_sem_nav_menus_cache_' . $number, true);
					echo $cache;
					return;
				}
			}
			else
			{
				$cache = get_option('sem_nav_menus_cache');
			#	$cache = array();
				
				if ( isset($cache[$number][$context]) )
				{
					echo $cache[$number][$context];
					return;
				}
			}
		}
		
		# get options
		global $sem_nav_menus;
		$options = $sem_nav_menus[$number];

		# admin area: return nothing
		if ( is_admin() )
		{
			return;
		}
		
		# init
		global $wpdb;
		$page_ids = array();
		static $ancestors;
		static $children = array();
		
		# all: fetch root page ids
		foreach ( (array) $options['items'] as $key => $item )
		{
			if ( $item['type'] == 'page' )
			{
				$page_ids[] = intval($item['ref']);
			}
		}
		
		# all: fetch root page details
		if ( $page_ids )
		{
			$fetch_ids = $page_ids;

			$fetch_ids_sql = implode(', ', $fetch_ids);
			
			$pages = (array) $wpdb->get_results("
				SELECT	posts.*
				FROM	$wpdb->posts as posts
				WHERE	post_type = 'page'
				AND		post_status = 'publish'
				AND		post_parent = 0
				");

			update_post_cache($pages);
			
			$found_ids = array();
			
			foreach ( $pages as $page )
			{
				$found_ids[] = $page->ID;
			}
		
			# catch invalid pages
			foreach ( $options['items'] as $key => $item )
			{
				if ( $item['type'] == 'page' && !in_array($item['ref'], $found_ids) )
				{
					unset($options['items'][$key]);
				}
			}
		}
		
		# page: fetch ancestors
		if ( !$page_id )
		{
			$ancestors = array();
		}
		elseif ( !isset($ancestors) )
		{
			$ancestors = array($page_id);
			
			if ( !in_array($page_id, $page_ids) )
			{
				# current page is in the wp cache already
				$page = wp_cache_get($page_id, 'posts');
				
				if ( $page->post_parent != 0 )
				{
					# traverse pages until we bump into the trunk
					do {
						$page = (object) $wpdb->get_row("
							SELECT	posts.*
							FROM	$wpdb->posts as posts
							WHERE	post_type = 'page'
							AND		post_status = 'publish'
							AND		ID = $page->post_parent
							");

						$pages = array($page);
						update_post_cache($pages);

						array_unshift($ancestors, $page->ID);
					} while ( $page->post_parent > 0 ); # > 0 to stop at unpublished pages if necessary
				}
			}
		}
		
		# all: fetch relevant children, in order to set the correct branch or leaf class
		$parent_ids = $page_ids;
		
		$parent_ids = array_diff($parent_ids, array_keys($children));
		$parent_ids = array_unique($parent_ids);
		
		if ( $parent_ids )
		{
			$parent_ids_sql = implode(', ', $parent_ids);
			
			$pages = (array) $wpdb->get_results("
				SELECT	posts.post_parent
				FROM	$wpdb->posts as posts
				WHERE	post_type = 'page'
				AND		post_status = 'publish'
				AND		post_parent IN ( $parent_ids_sql )
				GROUP BY post_parent
				");

			foreach ( $pages as $page )
			{
				$children[$page->post_parent] = true;
			}
		}
		
		$o = '';
		
		# fetch output
		if ( $options['items'] )
		{
			$o .= '<div>' . "\n";
			
			$i = 0;
			
			foreach ( $options['items'] as $item )
			{
				if ( $options['display_sep'] && $i++ )
				{
					$o .= sem_nav_menus::display_seperator('|');
				}
				
				switch ( $item['type'] )
				{
				case 'url':
					$o .= sem_nav_menus::display_url($item);
					break;
				
				case 'home':
					if ( get_option('show_on_front') != 'page' || !get_option('page_on_front') )
					{
						$o .= sem_nav_menus::display_home($item);
						break;
					}
					else
					{
						$item['ref'] = get_option('page_on_front');
					}
				case 'page':
					$o .= sem_nav_menus::display_page($item, $page_id, $ancestors, $children);
					break;
				}
			}
			
			$o .= '</div>' . "\n";
		}
		
		# cache
		if ( is_page() )
		{
			add_post_meta($page_id, '_sem_nav_menus_cache_' . $number, $o, true);
		}
		else
		{
			$cache[$number][$context] = $o;
			update_option('sem_nav_menus_cache', $cache);
		}

		# display
		echo $o;
	} # display_widget()
	
	
	#
	# display_url()
	#
	
	function display_url($item)
	{
		$classes = array();
		
		# process link
		$link = $item['label'];
		
		$link = '<a href="' . htmlspecialchars($item['ref']) . '">'
			. $link
			. '</a>';
		
		# process classes
		static $site_domain;
		
		if ( !isset($site_domain) )
		{
			$site_domain = get_option('home');
			$site_domain = parse_url($site_domain);
			$site_domain = $site_domain['host'];
			
			if ( $site_domain != 'localhost' && !preg_match("/\d+(\.\d+){3}/", $site_domain) )
			{
				$tlds = array('wattle.id.au', 'emu.id.au', 'csiro.au', 'name.tr', 'conf.au', 'info.tr', 'info.au', 'gov.au', 'k12.tr', 'lel.br', 'ltd.uk', 'mat.br', 'jor.br', 'med.br', 'net.hk', 'net.eg', 'net.cn', 'net.br', 'net.au', 'mus.br', 'mil.tr', 'mil.br', 'net.lu', 'inf.br', 'fnd.br', 'fot.br', 'fst.br', 'g12.br', 'gb.com', 'gb.net', 'gen.tr', 'ggf.br', 'gob.mx', 'gov.br', 'gov.cn', 'gov.hk', 'gov.tr', 'idv.tw', 'imb.br', 'ind.br', 'far.br', 'net.mx', 'se.com', 'rec.br', 'qsl.br', 'psi.br', 'psc.br', 'pro.br', 'ppg.br', 'pol.tr', 'se.net', 'slg.br', 'vet.br', 'uk.net', 'uk.com', 'tur.br', 'trd.br', 'tmp.br', 'tel.tr', 'srv.br', 'plc.uk', 'org.uk', 'ntr.br', 'not.br', 'nom.br', 'no.com', 'net.uk', 'net.tw', 'net.tr', 'net.ru', 'odo.br', 'oop.br', 'org.tw', 'org.tr', 'org.ru', 'org.lu', 'org.hk', 'org.cn', 'org.br', 'org.au', 'web.tr', 'eun.eg', 'zlg.br', 'cng.br', 'com.eg', 'bio.br', 'agr.br', 'biz.tr', 'cnt.br', 'art.br', 'com.hk', 'adv.br', 'cim.br', 'com.mx', 'arq.br', 'com.ru', 'com.tr', 'bmd.br', 'com.tw', 'adm.br', 'ecn.br', 'edu.br', 'etc.br', 'eng.br', 'esp.br', 'com.au', 'com.br', 'ato.br', 'com.cn', 'eti.br', 'edu.au', 'bel.tr', 'edu.tr', 'asn.au', 'jl.cn', 'mo.cn', 'sh.cn', 'nm.cn', 'js.cn', 'jx.cn', 'am.br', 'sc.cn', 'sn.cn', 'me.uk', 'co.jp', 'ne.jp', 'sx.cn', 'ln.cn', 'co.uk', 'co.at', 'sd.cn', 'tj.cn', 'cq.cn', 'qh.cn', 'gs.cn', 'gr.jp', 'dr.tr', 'ac.jp', 'hb.cn', 'ac.cn', 'gd.cn', 'pp.ru', 'xj.cn', 'xz.cn', 'yn.cn', 'av.tr', 'fm.br', 'fj.cn', 'zj.cn', 'gx.cn', 'gz.cn', 'ha.cn', 'ah.cn', 'nx.cn', 'tv.br', 'tw.cn', 'bj.cn', 'id.au', 'or.at', 'hn.cn', 'ad.jp', 'hl.cn', 'hk.cn', 'ac.uk', 'hi.cn', 'he.cn', 'or.jp', 'name', 'info', 'aero', 'com', 'net', 'org', 'biz', 'edu', 'int', 'mil', 'ua', 'st', 'tw', 'sg', 'uk', 'au', 'za', 'yu', 'ws', 'at', 'us', 'vg', 'as', 'va', 'tv', 'pt', 'si', 'sk', 'ag', 'sm', 'ca', 'su', 'al', 'am', 'tc', 'th', 'tm', 'ro', 'tn', 'to', 'ru', 'se', 'sh', 'eu', 'dk', 'ie', 'il', 'de', 'cz', 'cy', 'cx', 'is', 'it', 'jp', 'ke', 'kr', 'la', 'hu', 'hm', 'hk', 'fi', 'fj', 'fo', 'fr', 'es', 'gb', 'eg', 'ge', 'ee', 'gl', 'ac', 'gr', 'gs', 'li', 'lk', 'cd', 'nl', 'no', 'cc', 'by', 'br', 'nu', 'nz', 'bg', 'be', 'ba', 'az', 'pk', 'ch', 'ck', 'cl', 'lt', 'lu', 'lv', 'ma', 'mc', 'md', 'mk', 'mn', 'ms', 'mt', 'mx', 'dz', 'cn', 'pl');
				
				$site_len = strlen($site_domain);
				
				for ( $i = 0; $i < count($tlds); $i++ )
				{
					$tld = $tlds[$i];
					$tld_len = strlen($tld);
					
					# drop stuff that's too short
					if ( $site_len < $tld_len + 2 ) continue;
					
					# catch stuff like blahco.uk
					if ( substr($site_domain, -1 * $tld_len - 1, 1) != '.' ) continue;
					
					# match?
					if ( substr($site_domain, -1 * $tld_len) != $tld ) continue;
					
					# extract domain
					$site_domain = substr($site_domain, 0, $site_len - $tld_len - 1);
					$site_domain = explode('.', $site_domain);
					$site_domain = array_pop($site_domain);
					$site_domain = $site_domain . '.' . $tld;
				}
			}
		}
		
		$link_domain = $item['ref'];
		$link_domain = parse_url($link_domain);
		$link_domain = $link_domain['host'];
		
		if ( $site_domain == $link_domain
			|| substr($link_domain, -1 * strlen($site_domain) - 1) == ( '.' . $site_domain )
			)
		{
			$classes[] = 'nav_branch';
		}
		else
		{
			$classes[] = 'nav_leaf';
		}
		
		$classes[] = 'nav__' . preg_replace("/[^0-9a-z]+/i", "_", strtolower($item['label']));
		
		$classes = array_unique($classes);
		
		return '<span class="' . implode(' ', $classes) . '">'
			. $link
			. '</span>' . "\n";
	} # display_url()
	
	
	#
	# display_home()
	#
	
	function display_home($item)
	{
		$classes = array();
		
		# process link
		$link = $item['label'];
		
		if ( !( is_front_page() && !is_paged() ) )
		{
			$link = '<a href="' . htmlspecialchars(user_trailingslashit(get_option('home'))) . '">'
				. $link
				. '</a>';
		}
		
		# process classes
		$classes[] = 'nav_home';
		$classes[] = 'nav__' . preg_replace("/[^0-9a-z]+/i", "_", strtolower($item['label']));

		if ( !is_page() && !is_search() && !is_404() )
		{
			$classes[] = 'nav_active';
		}
		
		$classes = array_unique($classes);
		
		return '<span class="' . implode(' ', $classes) . '">'
			. $link
			. '</span>' . "\n";
	} # display_home()


	#
	# display_page()
	#
	
	function display_page($item, $page_id, $ancestors, $children)
	{
		$is_home_page = ( get_option('show_on_front') == 'page' )
				&& ( get_option('page_on_front') == $item['ref'] );
		$is_blog_page = ( get_option('show_on_front') == 'page' )
				&& ( get_option('page_for_posts') == $item['ref'] );
		
		$classes = array();
		
		# process link
		$link = $item['label'];
		
		if ( ( $page_id != $item['ref'] )
			&& !( $is_blog_page && is_home() && !is_paged() )
			)
		{
			$link = '<a href="' . htmlspecialchars(get_permalink($item['ref'])) . '">'
				. $link
				. '</a>';
		}
		
		# process classes
		if ( $is_home_page )
		{
			$classes[] = 'nav_home';
		}
		elseif ( $is_blog_page )
		{
			$classes[] = 'nav_blog';
		}
		elseif ( $children[$item['ref']] )
		{
			$classes[] = 'nav_branch';
		}
		else
		{
			$classes[] = 'nav_leaf';
		}
		
		if ( $page_id && in_array($item['ref'], $ancestors)
			|| $is_blog_page && !is_page()
			)
		{
			$classes[] = 'nav_active';
		}
		
		$classes[] = 'nav__' . preg_replace("/[^0-9a-z]+/i", "_", strtolower($item['label']));
		$classes = array_unique($classes);
		
		$o = '<span class="' . implode(' ', $classes) . '">'
			. $link
			. '</span>' . "\n";
		
		return $o;
	} # display_page()
	
	
	#
	# display_seperator()
	#
	
	function display_seperator($sep = '')
	{
		if ( !empty($sep) )
		{
			return '<span>' . $sep . '</span>' . "\n";
		}	
	} # display_seperator()	
	
	
	#
	# clear_cache()
	#
	
	function clear_cache($in = null)
	{
		global $wpdb;
		
		update_option('sem_nav_menus_cache', array());
		$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_sem_nav_menus_cache%'");
		
		return $in;
	} # clear_cache()
} # sem_nav_menus

sem_nav_menus::init();

?>