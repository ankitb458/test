<?php
#$wpdb->show_errors();

$sem_captions = get_option('sem6_captions');
if ( $sem_captions === false )
	$sem_captions = get_option('sem5_captions');
$sem_nav_menus = get_option('sem_nav_menus');

if ( version_compare($sem_options['version'], '6.0', '<') ) {
	if ( empty($sem_options['active_width']) ) {
		upgrade_sem_6_0();
	} else {
		upgrade_sem_theme();
		upgrade_sem_6_0();
	}
	update_option('init_sem_panels', '1');
} elseif ( empty($sem_options['active_width']) ) {
	upgrade_sem_theme();
}

unset($sem_options['skin_details']);
$sem_options['version'] = sem_version;

#dump($sem_options);die;

if ( !defined('sem_install_test') )
	update_option('sem6_options', $sem_options);

wp_cache_flush();
do_action('flush_cache');


/**
 * upgrade_sem_6_0()
 *
 * @return void
 **/

function upgrade_sem_6_0() {
	global $sem_options, $sem_captions, $sem_nav_menus, $wpdb, $sem_pro_version;
	
	$widget_contexts = class_exists('widget_contexts')
		? get_option('widget_contexts')
		: false;
	
	// fix a bug that was introduced in 5.7.2
	if ( $sem_options['version'] == '5.7.2' && !empty($sem_pro_version) ) {
		$post_ids = $wpdb->get_col("
			SELECT	ID
			FROM	$wpdb->posts
			JOIN	$wpdb->postmeta as scripts
			ON		scripts.post_id = ID
			AND		scripts.meta_key = '_scripts_override'
			LEFT JOIN $wpdb->postmeta as exceptions
			ON		exceptions.post_id = ID
			AND		exceptions.meta_key = '_wp_page_template'
			AND		exceptions.meta_value = 'letter.php'
			WHERE	post_modified >= '2009-01-01'
			AND		exceptions.post_id IS NULL
			");
		
		foreach ( $post_ids as $post_id )
			delete_post_meta($post_id, '_scripts_override');
	}
	
	# entry_header
	$instance = get_option('widget_entry_header');
	if ( $instance === false ) {
		$instance = array();
		if ( isset($sem_options['show_post_date']) )
			$instance['show_post_date'] = $sem_options['show_post_date'];
		unset($sem_options['show_post_date']);
		if ( isset($widget_contexts['entry_header']) ) {
			$instance['widget_contexts'] = $widget_contexts['entry_header'];
			unset($widget_contexts['entry_header']);
		}
		update_option('widget_entry_header', $instance);
	} else {
		if ( !isset($instance['widget_contexts']) && isset($widget_contexts['entry_header']) ) {
			$instance['widget_contexts'] = $widget_contexts['entry_header'];
			unset($widget_contexts['entry_header']);
		}
	}
	
	# entry_content
	$instance = get_option('widget_entry_content');
	if ( $instance === false ) {
		$instance = array();
		if ( isset($sem_options['show_excerpts']) )
			$instance['show_excerpts'] = $sem_options['show_excerpts'];
		$instance['one_comment'] = !empty($sem_captions['1_comment_link'])
			? $sem_captions['1_comment_link']
			: __('1 Comment', 'sem-reloaded');
		$instance['n_comments'] = !empty($sem_captions['n_comments_link'])
			? str_replace(
				'%num%',
				'%d',
				$sem_captions['n_comments_link'])
			: __('%d Comments', 'sem-reloaded');
		if ( isset($sem_captions['more_link']) ) {
			$instance['more_link'] = str_replace(
				'%title%',
				'%s',
				$sem_captions['more_link']);
		}
		if ( isset($sem_captions['paginate']) )
			$instance['paginate'] = $sem_captions['paginate'];
		elseif ( isset($sem_captions['page']) )
			$instance['paginate'] = $sem_captions['page'];
		unset($sem_options['show_excerpts']);
		unset($sem_captions['no_comments']);
		unset($sem_captions['1_comment_link']);
		unset($sem_captions['1_comment']);
		unset($sem_captions['n_comments_link']);
		unset($sem_captions['n_comments']);
		unset($sem_captions['more']);
		unset($sem_captions['more_link']);
		unset($sem_captions['edit']);
		unset($sem_captions['paginate']);
		unset($sem_captions['page']);
		if ( isset($widget_contexts['entry_content']) ) {
			$instance['widget_contexts'] = $widget_contexts['entry_content'];
			unset($widget_contexts['entry_content']);
		}
	} else {
		if ( !isset($instance['widget_contexts']) && isset($widget_contexts['entry_content']) ) {
			$instance['widget_contexts'] = $widget_contexts['entry_content'];
			unset($widget_contexts['entry_content']);
		}
		if ( isset($instance['n_comments']) ) {
			$instance['n_comments'] = str_replace(
				'%num%',
				'%d',
				$instance['n_comments']);
		}
		if ( isset($instance['more_link']) ) {
			$instance['more_link'] = str_replace(
				'%title%',
				'%s',
				$instance['more_link']);
		}
	}
	update_option('widget_entry_content', $instance);
	
	# entry_categories
	$instance = get_option('widget_entry_categories');
	if ( $instance === false ) {
		$instance = array();
		if ( isset($sem_captions['cats_title']) ) {
			$instance['title'] = $sem_captions['cats_title'];
		}
		if ( isset($sem_captions['filed_under']) ) {
			$instance['filed_under_by'] = str_replace(
				array('%categories%', '%category%', '%author%'),
				array('%1$s', '%1$s', '%2$s'),
				$sem_captions['filed_under']);
		}
		unset($sem_captions['cats_title']);
		unset($sem_captions['filed_under']);
		unset($sem_captions['by']);
		if ( isset($widget_contexts['entry_categories']) ) {
			$instance['widget_contexts'] = $widget_contexts['entry_categories'];
			unset($widget_contexts['entry_categories']);
		}
	} else {
		if ( !isset($instance['widget_contexts']) && isset($widget_contexts['entry_categories']) ) {
			$instance['widget_contexts'] = $widget_contexts['entry_categories'];
			unset($widget_contexts['entry_categories']);
		}
		if ( isset($instance['filed_under_by']) ) {
			$instance['filed_under_by'] = str_replace(
				array('%categories%', '%category%', '%author%'),
				array('%1$s', '%1$s', '%2$s'),
				$instance['filed_under_by']);
		}
	}
	update_option('widget_entry_categories', $instance);
	
	# entry_tags
	$instance = get_option('widget_entry_tags');
	if ( $instance === false ) {
		$instance = array();
		if ( isset($sem_captions['tags_title']) ) {
			$instance['title'] = $sem_captions['tags_title'];
		}
		if ( isset($sem_captions['tags']) ) {
			$instance['tags'] = str_replace(
				'%tags%',
				'%s',
				$sem_captions['tags']);
		}
		unset($sem_captions['tags_title']);
		unset($sem_captions['tags']);
		if ( isset($widget_contexts['entry_tags']) ) {
			$instance['widget_contexts'] = $widget_contexts['entry_tags'];
			unset($widget_contexts['entry_tags']);
		}
	} else {
		if ( !isset($instance['widget_contexts']) && isset($widget_contexts['entry_tags']) ) {
			$instance['widget_contexts'] = $widget_contexts['entry_tags'];
			unset($widget_contexts['entry_tags']);
		}
		if ( isset($instance['tags']) ) {
			$instance['tags'] = str_replace(
				'%tags%',
				'%s',
				$instance['tags']);
		}
	}
	update_option('widget_entry_tags', $instance);
	
	# entry_comments
	$instance = get_option('widget_entry_comments');
	if ( $instance === false ) {
		$instance = array();
		if ( isset($instance['comments_on']) ) {
			$instance['comments_on'] = str_replace(
				'%title%',
				'%s',
				$sem_captions['comments_on']);
		}
		if ( isset($sem_captions['pings_on']) ) {
			$instance['pings_on'] = str_replace(
				'%title%',
				'%s',
				$sem_captions['pings_on']);
		}
		if ( isset($sem_captions['leave_comment']) )
			$instance['leave_comment'] = $sem_captions['leave_comment'];
		if ( isset($sem_captions['reply_link']) )
			$instance['reply_link'] = $sem_captions['reply_link'];
		if ( isset($sem_captions['login_required']) ) {
			$instance['login_required'] = str_replace(
				'%login_url%',
				'%s',
				$sem_captions['login_required']);
		}
		if ( isset($sem_captions['logged_in_as']) ) {
			$instance['logged_in_as'] = str_replace(
				array('%identity%', '%logout_url%'),
				array('%1$s', '%2$s'),
				$sem_captions['logged_in_as']);
		}
		if ( isset($sem_captions['name_field']) )
			$instance['name_field'] = $sem_captions['name_field'];
		if ( isset($sem_captions['email_field']) )
			$instance['email_field'] = $sem_captions['email_field'];
		if ( isset($sem_captions['url_field']) )
			$instance['url_field'] = $sem_captions['url_field'];
		if ( isset($sem_captions['submit_field']) )
			$instance['submit_field'] = $sem_captions['submit_field'];
		unset($sem_captions['comments_on']);
		unset($sem_captions['pings_on']);
		unset($sem_captions['leave_comment']);
		unset($sem_captions['reply_link']);
		unset($sem_captions['login_required']);
		unset($sem_captions['logged_in_as']);
		unset($sem_captions['name_field']);
		unset($sem_captions['email_field']);
		unset($sem_captions['url_field']);
		unset($sem_captions['submit_field']);
		if ( isset($widget_contexts['entry_comments']) ) {
			$instance['widget_contexts'] = $widget_contexts['entry_comments'];
			unset($widget_contexts['entry_comments']);
		}
	} else {
		if ( !isset($instance['widget_contexts']) && isset($widget_contexts['entry_comments']) ) {
			$instance['widget_contexts'] = $widget_contexts['entry_comments'];
			unset($widget_contexts['entry_comments']);
		}
		if ( isset($instance['comments_on']) ) {
			$instance['comments_on'] = str_replace(
				'%title%',
				'%s',
				$instance['comments_on']);
		}
		if ( isset($instance['pings_on']) ) {
			$instance['pings_on'] = str_replace(
				'%title%',
				'%s',
				$instance['pings_on']);
		}
		if ( isset($instance['login_required']) ) {
			$instance['login_required'] = str_replace(
				'%login_url%',
				'%s',
				$instance['login_required']);
		}
		if ( isset($instance['logged_in_as']) ) {
			$instance['logged_in_as'] = str_replace(
				array('%identity%', '%logout_url%'),
				array('%1$s', '%2$s'),
				$instance['logged_in_as']);
		}
	}
	update_option('widget_entry_comments', $instance);
	
	# blog_header
	$instance = get_option('widget_blog_header');
	if ( $instance === false ) {
		$instance = array();
		if ( isset($sem_captions['title_404']) )
			$instance['title_404'] = $sem_captions['title_404'];
		elseif ( isset($sem_captions['no_entries_found']) )
			$instance['title_404'] = $sem_captions['no_entries_found'];
		if ( isset($sem_captions['desc_404']) )
			$instance['desc_404'] = $sem_captions['desc_404'];
		if ( isset($sem_captions['archives_title']) )
			$instance['archives_title'] = $sem_captions['archives_title'];
		if ( isset($sem_captions['search_title']) )
			$instance['search_title'] = str_replace(
				'%query%',
				'%s',
				$sem_captions['search_title']);
		unset($sem_captions['title_404']);
		unset($sem_captions['no_entries_found']);
		unset($sem_captions['desc_404']);
		unset($sem_captions['archives_title']);
		unset($sem_captions['search_title']);
		if ( isset($widget_contexts['blog_header']) ) {
			$instance['widget_contexts'] = $widget_contexts['blog_header'];
			unset($widget_contexts['blog_header']);
		}
	} else {
		if ( !isset($instance['widget_contexts']) && isset($widget_contexts['blog_header']) ) {
			$instance['widget_contexts'] = $widget_contexts['blog_header'];
			unset($widget_contexts['blog_header']);
		}
		if ( isset($instance['search_title']) )
			$instance['search_title'] = str_replace(
				'%query%',
				'%s',
				$sem_captions['search_title']);
	}
	update_option('widget_blog_header', $instance);
	
	# blog_footer
	$instance = get_option('blog_footer');
	if ( $instance === false ) {
		$instance = array();
		if ( isset($sem_captions['next_page']) )
			$instance['next_page'] = $sem_captions['next_page'];
		if ( $sem_captions['prev_page'] )
			$instance['previous_page'] = $sem_captions['prev_page'];
		unset($sem_captions['next_page']);
		unset($sem_captions['prev_page']);
		if ( isset($widget_contexts['blog_footer']) ) {
			$instance['widget_contexts'] = $widget_contexts['blog_footer'];
			unset($widget_contexts['blog_footer']);
		}
	} else {
		if ( !isset($instance['widget_contexts']) && isset($widget_contexts['blog_footer']) ) {
			$instance['widget_contexts'] = $widget_contexts['blog_footer'];
			unset($widget_contexts['blog_footer']);
		}
	}
	update_option('widget_blog_footer', $instance);
	
	# header
	$instance = get_option('widget_header');
	if ( $instance === false ) {
		$instance = array();
		if ( isset($sem_options['invert_header']) )
			$instance['invert_header'] = $sem_options['invert_header'];
		unset($sem_options['invert_header']);
		if ( isset($widget_contexts['header']) ) {
			$instance['widget_contexts'] = $widget_contexts['header'];
			unset($widget_contexts['header']);
		}
	} else {
		if ( !isset($instance['widget_contexts']) && isset($widget_contexts['header']) ) {
			$instance['widget_contexts'] = $widget_contexts['header'];
			unset($widget_contexts['header']);
		}
	}
	update_option('widget_header', $instance);
	
	# navbar
	$instance = get_option('widget_navbar');
	if ( $instance === false ) {
		$instance = array();
		$instance['items'] = (array) $sem_nav_menus['header']['items'];
		if ( isset($sem_nav_menus['header']['display_sep']) )
			$instance['sep'] = $sem_nav_menus['header']['display_sep'];
		if ( isset($sem_options['show_search_form']) )
			$instance['show_search_form'] = $sem_options['show_search_form'];
		if ( isset($sem_captions['search_field']) )
			$instance['search_field'] = $sem_captions['search_field'];
		if ( isset($sem_captions['search_button']) )
			$instance['search_button'] = $sem_captions['search_button'];
		unset($sem_nav_menus['header']);
		unset($sem_options['show_search_form']);
		unset($sem_captions['search_field']);
		unset($sem_captions['search_button']);
		if ( isset($widget_contexts['navbar']) ) {
			$instance['widget_contexts'] = $widget_contexts['navbar'];
			unset($widget_contexts['navbar']);
		}
	} else {
		if ( !isset($instance['widget_contexts']) && isset($widget_contexts['navbar']) ) {
			$instance['widget_contexts'] = $widget_contexts['navbar'];
			unset($widget_contexts['navbar']);
		}
	}
	update_option('widget_navbar', $instance);
	
	# footer
	$instance = get_option('widget_footer');
	if ( $instance === false ) {
		$instance = array();
		$instance['items'] = (array) $sem_nav_menus['footer']['items'];
		if ( isset($sem_nav_menus['footer']['display_sep']) )
			$instance['sep'] = $sem_nav_menus['footer']['display_sep'];
		if ( isset($sem_options['float_footer']) )
			$instance['float_footer'] = $sem_options['float_footer'];
		if ( !isset($sem_options['show_copyright']) || isset($sem_options['show_copyright']) ) {
			$instance['copyright'] = str_replace(
				array('%admin_name%', '%site_name%', '%year%'),
				array('', '%1$s', '%2$s'),
				$sem_captions['copyright']);
		} else {
			$instance['copyright'] = '';
		}
		unset($sem_nav_menus['footer']);
		unset($sem_options['float_footer']);
		unset($sem_options['show_copyright']);
		unset($sem_captions['copyright']);
		if ( isset($widget_contexts['footer']) ) {
			$instance['widget_contexts'] = $widget_contexts['footer'];
			unset($widget_contexts['footer']);
		}
	} else {
		if ( !isset($instance['widget_contexts']) && isset($widget_contexts['footer']) ) {
			$instance['widget_contexts'] = $widget_contexts['footer'];
			unset($widget_contexts['footer']);
		}
		if ( isset($instance['copyright']) ) {
			$instance['copyright'] = str_replace(
				array('%admin_name%', '%site_name%', '%year%'),
				array('', '%1$s', '%2$s'),
				$instance['copyright']);
		}
	}
	update_option('widget_footer', $instance);
	
	# credits
	if ( !isset($sem_options['credits']) ) {
		if ( isset($sem_options['show_credits']) && !$sem_options['show_credits'] )
			$sem_options['credits'] = '';
		else
			$sem_options['credits'] = __('Made with %1$s &bull; %2$s skin by %3$s', 'sem-theme');
	} else {
		$sem_options['credits'] = str_replace(
			array('%semiologic%', '%skin_name%', '%skin_author%'),
			array('%1$s', '%2$s', '%3$s'),
			$sem_options['credits']);
	}
	
	if ( isset($widget_contexts['entry_actions']) )
		unset($widget_contexts['entry_actions']);
	
	unset($sem_captions['permalink']);
	unset($sem_captions['print']);
	unset($sem_captions['email']);
	unset($sem_captions['comment']);
	unset($sem_captions['trackback_uri']);
	unset($sem_captions['track_this_entry']);
	unset($sem_captions['related_entries']);
	
	extract($sem_options, EXTR_SKIP);
	
	$sem_options = compact(
		'active_skin',
		'active_layout',
		'active_font',
		'credits',
		'version'
		);
	
	update_option('widget_contexts', $widget_contexts);
	
	if ( !defined('sem_install_test') ) {
		delete_option('sem6_captions');
		delete_option('sem_nav_menus');
	}
	
	# clear corrupt cron job
	wp_clear_scheduled_hook('dealdotcom');
	
	# dump ext_sidebar if set
	if ( !is_preview() ) {
		$sidebars_widgets = get_option('sidebars_widgets', array('array_version' => 3));
		if ( isset($sidebars_widgets['ext_sidebar']) && !empty($sidebars_widgets['sidebar-2']) ) {
			unset($sidebars_widgets['ext_sidebar']);
			update_option('sidebars_widgets', $sidebars_widgets);
		}
	}
	
	if ( empty($sem_pro_version) )
		return;
	
	# drop obsolete plugins
	$active_plugins = get_option('active_plugins', array());

	$obsolete_plugins = array(
		'extended-comment-options/commentcontrol.php',
		'ozh-absolute-comments/wp_ozh_absolutecomments.php',	
		'order-categories/category-order.php',
		'page-tags/page-tags.php',	
		'smart-update-pinger.php',
		'simple-trackback-validation.php',
		'favicon-head.php',
		'mycategoryorder/mycategoryorder.php',
		'mylinkorder/mylinkorder.php',
		'mypageorder/mypageorder.php',
		);

	$active_plugins = array_diff($active_plugins, $obsolete_plugins);
	update_option('active_plugins', $active_plugins);
} # upgrade_sem_6_0()


/**
 * upgrade_sem_theme()
 *
 * @return void
 **/

function upgrade_sem_theme() {
	global $sem_options;
	$old_options = get_option('sem5_options');
	
	# sem5 to sem6 layout
	switch ( $old_options['active_layout'] ) {
	case 'mse':
		$sem_options['active_layout'] = 'mts';
		break;

	case 'sme':
		$sem_options['active_layout'] = 'sms';
		break;

	case 'ems':
		$sem_options['active_layout'] = 'sms';
		break;

	case 'esm':
		$sem_options['active_layout'] = 'tsm';
		break;

	case 'me':
		$sem_options['active_layout'] = 'mms';
		break;

	case 'em':
		$sem_options['active_layout'] = 'smm';
		break;

	case 'ssm':
		$sem_options['active_layout'] = 'tsm';
		break;

	case 'mss':
		$sem_options['active_layout'] = 'mts';
		break;

	case 'emss':
	case 'msse':
	case 'esms':
	case 'smse':
		$sem_options['active_layout'] = 'mts';
		break;

	case 'ssme':
	case 'essm':
		$sem_options['active_layout'] = 'tsm';
		break;

	case 'ms':
	case 'sm':
		if ( isset($old_options['active_width'])
			&& in_array($old_options['active_width'], array('wide', 'flex'))
			) {
			$sem_options['active_layout'] = str_replace('m', 'mm', $old_options['active_layout']);
		}
		break;

	case 'm':
		if ( isset($old_options['active_width']) ) {
			if ( $old_options['active_width'] == 'wide' )
				$sem_options['active_layout'] = 'mm';
			elseif ( $old_options['active_width'] == 'flex' )
				$sem_options = 'mmm';
		}
		break;
	}
	
	$sem_options['active_skin'] = 'copywriter-gold';
	unset($sem_options['active_width']);
	unset($sem_options['active_font_size']);
} # upgrade_sem_theme()
?>