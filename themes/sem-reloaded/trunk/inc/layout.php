<?php

class sem_layout
{
	#
	# init()
	#
	
	function init()
	{
		add_filter('body_class', array('sem_layout', 'body_class'));
		add_action('wp_head', array('sem_layout', 'css'));
	} # init()
	
	
	#
	# css()
	#
	
	function css()
	{
		global $sem_options;
		
		foreach ( array(
			'style.css',
			'css/layout.css',
			) as $file )
		{
			echo '<link rel="stylesheet" type="text/css" href="'
				. sem_url . '/' . $file
				. '" />' . "\n";
		}
	} # css()
	
	
	#
	# body_class()
	#
	
	function body_class($classes)
	{
		global $sem_options;
		
		$active_layout = apply_filters('active_layout', $sem_options['active_layout']);
		
		$classes[] = $active_layout;
		
		if ( $active_layout != 'letter' )
		{
			$extra_layout = str_replace(array('s', 't'), 'm', $active_layout);
			
			if ( $extra_layout != $active_layout)
			{
				$classes[] = $extra_layout;
				$classes[] = str_replace(array('s', 't'), '', $active_layout)
					. ( substr_count(str_replace('t', 's', $active_layout), 's')) . 's';
			}
		}
		
		return $classes;
	} # body_class()
} # sem_layout

sem_layout::init();


#
# strip_s()
#

function strip_s($in)
{
	return str_replace(array('s', 't'), 'm', $in);
} # end strip_s()


#
# force_letter()
#

function force_letter($in)
{
	return 'letter';
} # end force_letter()
?>