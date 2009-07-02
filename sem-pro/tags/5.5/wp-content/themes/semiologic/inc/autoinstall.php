<?php
include_once ABSPATH . 'wp-admin/admin-functions.php';

include_once sem_path . '/utils/wizards.php';
include_once sem_path . '/utils/skin.php';
include_once sem_path . '/utils/layout.php';
include_once sem_path . '/utils/captions.php';

include_once sem_path . '/admin/skin.php';
include_once sem_path . '/admin/nav-menus.php';
include_once sem_path . '/admin/docs.php';

global $sem_options;
global $sem_captions;
$sidebars = get_option('sidebars_widgets');

if ( $sem_options && !current_user_can('administrator') )
{
	return;
}

# Reset
$sem_options = array();
$old_options = get_option('semiologic');

# Header Nav
$sem_nav['header_nav'] = array(
			'Home' => get_bloginfo('home'),
			'Blog' => 'blog',
			'About' => 'about',
			'Contact' => 'contact'
			);

# Sidebar Nav
$sem_nav['sidebar_nav'] = array(
			);

# Footer Nav
$sem_nav['footer_nav'] = array(
			'Archives' => 'archives',
			'About' => 'about',
			'Contact' => 'contact'
			);

# Skin, layout, font, width
$sem_options['active_skin'] = get_skin_data('sky-gold');
$sem_options['active_layout'] = 'ms';
$sem_options['active_width'] = 'wide';
$sem_options['active_font'] = 'trebuchet';
$sem_options['active_font_size'] = 'small';

# Header
$sem_options['header']['mode'] = 'header';

# Template
$sem_options['show_article_date'] = true;
$sem_options['show_post_date'] = true;
$sem_options['show_permalink'] = true;
$sem_options['show_print_link'] = true;
$sem_options['show_comment_link'] = true;
$sem_options['show_search_form'] = true;
$sem_options['show_copyright'] = true;

$sem_captions = array(
	"1_comment_link" => "1 Comment",
	"by_author" => "By %author%",
	"cats_title" => "Categories",
	"comment_link" => "Comment",
	"comments_on" => "Comments on %title%",
	"copyright" => "Copyright %year%",
	"email_field" => "Email",
	"email_link" => "Email",
	"filed_under" => "Filed under %categories% by %author%",
	"leave_comment" => "Leave a Comment",
	"logged_in_as" => "Logged in as %identity%",
	"login_required" => "You must be logged in to comment",
	"more_link" => "More on %title%",
	"n_comments_link" => "%num% Comments",
	"name_field" => "Name",
	"next_page" => "Next Page",
	"paginate" => "Pages",
	"permalink" => "Permalink",
	"prev_page" => "Previous Page",
	"print_link" => "Print",
	"reply_link" => "Reply",
	"search_button" => "Go",
	"search_field" => "Search",
	"sidebar_nav_title" => "Navigate",
	"submit_field" => "Submit Comment",
	"tags" => "Tags: %tags%",
	"tags_title" => "Tags",
	"url_field" => "Url",
	);


$default_sidebars = array(
	"the_header" => array(
		"header",
		"header-nav-menu",
		),
	"the_entry" => array(
		"entry-header",
		"entry-content",
		"entry-tags",
		"entry-categories",
		"entry-actions",
		"entry-comments",
		),
	"after_the_entries" => array(
		"nextprev-posts",
		),
	"the_footer" => array(
		"footer-nav-menu",
		),
	);


if ( file_exists(sem_pro_path . '/inc/autoinstall.php') )
{
	include_once sem_pro_path . '/inc/autoinstall.php';
}

if ( is_array($sidebars) )
{
	foreach ( array_keys($sidebars) as $key )
	{
		if ( is_array($sidebars[$key]) )
		{
			foreach ( array_keys($sidebars[$key]) as $k )
			{
				$sidebars[$key][$k] = sanitize_title($sidebars[$key][$k]);
			}
		}
	}

	if ( !isset($sidebars['array_version']) )
	{
		$sidebars['array_version'] = 3;
	}

	foreach ( array_keys($default_sidebars) as $key )
	{
		$sidebars[$key] = array_unique(array_merge($default_sidebars[$key], (array) $sidebars[$key]));
	}
}
else
{
	$sidebars = $default_sidebars;
}

if ( $old_options )
{
	foreach ( array_keys($old_options) as $key )
	{
		switch ( $key )
		{
		case 'nav_menu_cache':
			break;
		case 'nav_menus':
			$sem_nav = $old_options[$key];
			break;
		case 'captions':
			foreach ( $old_options[$key] as $k => $v )
			{
				if ( $k == 'filed_under' ) continue;

				if ( isset($sem_captions[$k]) )
				{
					$sem_captions[$k] = $v;
				}
			}
			break;
		default:
			$sem_options[$key] = $old_options[$key];
			break;
		}
	}

	#delete_option('semiologic');

	#dump($old_options);
}

# widget contexts
$sem_widget_contexts = get_option('sem_widget_contexts');

foreach ( array(
	'header',
	'header-nav-menu',
	'footer-nav-menu',
	'entry-header',
	'entry-tags',
	'entry-categories',
	'entry-comments',
	) as $widget )
{
	$sem_widget_contexts[$widget] = array(
		'sell' => false,
		);
}

foreach ( array(
	'author-image',
	'entry-actions',
	'bookmark-me',
	) as $widget )
{
	$sem_widget_contexts[$widget] = array(
		'special' => false,
		'sell' => false,
		);
}

update_option('sem_widget_contexts', $sem_widget_contexts);

# Version
$sem_options['version'] = (string) sem_version;

#dump($sem_options);
#dump($sem_captions);
#dump($sem_nav);
#dump($sidebars);
#die;

update_option('sem5_options', $sem_options);
update_option('sem5_captions', $sem_captions);
update_option('sem5_nav', $sem_nav);
update_option('sidebars_widgets', $sidebars);


sem_docs::update(true);

regen_theme_nav_menu_cache();

if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') === false )
{
	#dump($_SERVER['REQUEST_URI']);
	if ( strpos($_SERVER['PHP_SELF'], 'wp-login.php') !== false
		&& strpos($_SERVER['REQUEST_URI'], 'wp-login.php') === false
		)
	{
		$_SERVER['REQUEST_URI'] = trailingslashit($_SERVER['REQUEST_URI']) . 'wp-login.php';
	}

	wp_redirect($_SERVER['REQUEST_URI']);
	die;
}
?>