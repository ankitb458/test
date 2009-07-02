<?php
function sem_widgets_get_contexts()
{
	return array(
		'home' => _('Home'),
		'post' => _('Post / Article'),
		'page' => _('Page'),
		'list' => _('Archives / Links'),
		'sell' => _('Sales Letter'),
		'special' => _('Special Page'),
		'archive' => _('Tags / Cats'),
		'search' => _('Search'),
		);
} # sem_widgets_get_contexts()


function kill_wp_widgets()
{
	global $wp_registered_widgets;

	if ( sem_pro )
	{
		unset($wp_registered_widgets['recent-posts']);
		unset($wp_registered_widgets['recent-comments']);
	}
	else
	{
		$wp_registered_widgets['recent-posts']['callback'] = 'get_sem_widgets';
		$wp_registered_widgets['recent-comments']['callback'] = 'get_sem_widgets';
	}
} # kill_wp_widgets()

add_action('init', 'kill_wp_widgets', 5);


function get_sem_widgets($args, $number = 1)
{
	echo $args['before_widget'];
	echo '<div style="background-color: #ffeeee; border: solid 1px firebrick; padding: 20px;">'
		. __('The built-in WP widgets tend to interact poorly with other plugins. In particular when used within the loop. For this reason, a few are disabled in the Semiologic theme. Consider using <a href="http://www.semiologic.com/software/widgets/">Fuzzy Widgets</a> instead.</a>')
		. '</div>';
	echo $args['after_widget'];
} # get_sem_widgets()
?>