<?php
#
# sidebar_is_empty()
#

function sidebar_is_empty($sidebar_id)
{
	return false;
} # end sidebar_is_empty()


#
# display_sidebar()
#

function display_sidebar()
{
	if ( strpos(get_active_layout(true), 's') !== false )
	{
		include_once sem_path . '/sidebar.php';
	}
} # end display_sidebar()

add_action('display_sidebar', 'display_sidebar');


#
# display_sidebar2()
#

function display_sidebar2()
{
	if ( substr_count(get_active_layout(true), 's') ==2 )
	{
		include_once sem_path . '/sidebar2.php';
	}
} # end display_sidebar2()

add_action('display_sidebar2', 'display_sidebar2');


#
# display_ext_sidebar()
#

function display_ext_sidebar()
{
	if ( strpos(get_active_layout(true), 'e') !== false )
	{
		include_once sem_path . '/sidebar-ext.php';
	}
} # end display_ext_sidebar()

add_action('display_ext_sidebar', 'display_ext_sidebar');


#
# before_the_entries()
#

function before_the_entries()
{
	dynamic_sidebar('before_the_entries');
} # before_the_entries()

add_action('before_the_entries', 'before_the_entries');


#
# after_the_entries()
#

function after_the_entries()
{
	dynamic_sidebar('after_the_entries');
} # after_the_entries()

add_action('after_the_entries', 'after_the_entries');
?>