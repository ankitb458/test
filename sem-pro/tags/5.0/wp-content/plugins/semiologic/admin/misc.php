<?php
/*
# kill_sec_filters

function kill_sec_filters($rules)
{
	$rules =
		'<IfModule mod_security.c>' . "\n"
		. 'SecFilterEngine Off' . "\n"
		. 'SecFilterScanPOST Off' . "\n"
		. '</IfModule>' . "\n"
		. "\n\n"
		. $rules;

	return $rules;
} # kill_sec_filters()

add_filter('mod_rewrite_rules', 'kill_sec_filters');
*/

#
# sort_admin_menu()
#

function sort_admin_menu()
{
	global $menu;
	global $submenu;

	foreach ( array_keys($menu) as $key )
	{
		if ( $menu[$key][0] == __('Blogroll') )
		{
			$menu[$key][0] = __('Links');
			break;
		}
	}

	foreach ( $submenu as $key => $menu_items )
	{
		#echo '<pre>';
		#var_dump($menu_items);
		#echo '</pre>';

		switch ( $key )
		{
		case 'post-new.php':
			$stop = 0;
			$caps = array('edit_posts', 'edit_pages');
			break;

		case 'edit.php':
			$stop = 0;
			$caps = array('edit_posts', 'edit_pages');
			break;

		case 'edit-comments.php':
			$stop = 2;
			$caps = array();
			break;

		case 'link-manager.php':
			$stop = 3;
			$caps = array();

			foreach ( array_keys($menu_items) as $subkey )
			{
				if ( $menu_items[$subkey][0] == __('Manage Blogroll') )
				{
					$menu_items[$subkey][0] = __('Manage Links');
					break;
				}
			}
			break;

		default:
			$stop = 1;
			$caps = array();
			break;
		}

		foreach ( $caps as $cap )
		{
			if ( current_user_can($cap) )
			{
				$stop++;
			}
		}

		#echo '<pre>';
		#var_dump($key, $stop);
		#echo '</pre>';

		$unsortable = array();
		$sortable = $menu_items;

		while ( $stop != 0 )
		{
			$mkey = key($sortable);
			$unsortable[$mkey] = current($sortable);
			unset($sortable[$mkey]);

			$stop--;
		}

		#echo '<pre>';
		#var_dump($key, $menu_items, $unsortable, $sortable);
		#echo '</pre>';

		uasort($sortable, 'menu_nat_sort');

		$submenu[$key] = array_merge($unsortable, $sortable);
	}
} # sort_admin_menu()


#
# menu_nat_sort()
#

function menu_nat_sort($a, $b)
{
	return strnatcmp($a[0], $b[0]);
} # menu_nat_sort()

add_action('admin_menu', 'sort_admin_menu', 1000000);


#
# sem_maintain_db()
#

function sem_maintain_db()
{
	global $wpdb;

	$tablelist = $wpdb->get_results("show tables", ARRAY_N);

	foreach ($tablelist as $table)
	{
		$tablename = $table[0];

		$check = $wpdb->get_row("check table $tablename", ARRAY_N);

		if ( $check[2] == 'error' )
		{
			if ( $check[3] == 'The handler for the table doesn\'t support check/repair' )
			{
				continue;
			}
			else
			{
				$repair = $wpdb->get_row("repair table $tablename", ARRAY_N);

				if ( $repair[3] != 'OK' )
				{
					continue;
				}
			}
		}

		$wpdb->query("optimize table $tablename");
	}
} # sem_maintain_db()

add_action('maintain_db', 'sem_maintain_db');

wp_schedule_event( time(), 3600 * 24, 'maintain_db');



if ( function_exists('nr_add_pages') ) :

#
# sem_now_reading_options()
#

function sem_now_reading_options($options)
{
	$options['menuLayout'] = NR_MENU_MULTIPLE;
	$options['useModRewrite'] = intval(get_option('permalink_structure') != '');
	$options['debugMode'] = 0;
	$options['httpLib'] = function_exists('curl_init') ? 'curl' : 'snoopy';
	$options['formatDate'] = get_option('date_format');

	return $options;
}

add_filter('option_nowReadingOptions', 'sem_now_reading_options');


#
# sem_nr_kill_options()
#

function sem_nr_kill_options($buffer)
{
	return preg_replace_callback("/
		<tr(?:\s[^>]*)>
		\s*
		<th(?:\s[^>]*)>(.*)<\/th>
		.*
		<\/tr>
		/isUx",
		'sem_nr_kill_options_callback',
		$buffer
		);
} # sem_nr_kill_options()


#
# sem_nr_kill_options_callback()
#

function sem_nr_kill_options_callback($input)
{
	if ( in_array(
			$input[1],
			array(
				__('Date format string', NRTD) . ':',
				__('Admin menu layout', NRTD) . ':',
				__("HTTP Library", NRTD) . ':',
				__("Use <code>mod_rewrite</code> enhanced library?", NRTD),
				__("Debug mode", NRTD) . ':',
				)
			)
		)
	{
		return '';
	}
	else
	{
		return $input[0];
	}
} # sem_nr_kill_options_callback()


#
# sem_nr_extend()
#

function sem_nr_extend()
{
	ob_start('sem_nr_kill_options');
} # sem_nr_extend()

add_action('load-options_page_nr_options', 'sem_nr_extend');


#
# sem_nr_admin_menu()
#

function sem_nr_admin_menu()
{
	add_submenu_page('post-new.php', 'Book Review', 'Book Review', 'edit_pages', 'add_book', 'now_reading_add');
	add_management_page('Book Reviews', 'Book Reviews', 'edit_pages', 'manage_books', 'nr_manage');
	add_options_page('Book Reviews', 'Book Reviews', 'manage_options', 'nr_options', 'nr_options');
} # sem_nr_admin_menu()

remove_action('admin_menu', 'nr_add_pages');
add_action('admin_menu', 'sem_nr_admin_menu');


#
# sem_nr_add_head()
#

function sem_nr_add_head()
{
	echo '
	<link rel="stylesheet" href="' . get_bloginfo('url') . '/wp-content/plugins/now-reading/admin/admin.css" type="text/css" />
	<script type="text/javascript">
		var lHide = "' . __("Hide", NRTD) . '";
		var lEdit = "' . __("Edit", NRTD) . '";
	</script>
	<script type="text/javascript" src="' . get_bloginfo('url') . '/wp-content/plugins/now-reading/js/manage.js"></script>
	';
} # sem_nr_add_head()

remove_action('admin_head', 'nr_add_head');
add_action('admin_head', 'sem_nr_add_head');

endif;



if ( isset($simple_tags_admin) ) :

$simple_tags_admin->SimpleTagsAdmin( $simple_tags->default_options, $simple_tags->version );

#
# sem_simpletags_menu()
#

function sem_simpletags_admin_menu()
{
	global $simple_tags_admin;
	add_management_page(__('Tags', 'simpletags'), __('Tags', 'simpletags'), 'simple_tags', 'simpletags_manage', array(&$simple_tags_admin, 'pageManageTags'));
	add_management_page(__('Mass Edit Tags', 'simpletags'), __('Mass Edit Tags', 'simpletags'), 'simple_tags', 'simpletags_mass', array(&$simple_tags_admin, 'pageMassEditTags'));
} # sem_simpletags_admin_menu()

remove_action('admin_menu', array(&$simple_tags_admin, 'adminMenu'));
add_action('admin_menu', 'sem_simpletags_admin_menu');

endif;
?>