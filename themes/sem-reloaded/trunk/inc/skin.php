<?php

class sem_skin
{
	#
	# init()
	#
	
	function init()
	{
		add_filter('page_class', array('sem_skin', 'page_class'));
		add_action('wp_head', array('sem_skin', 'css'));
		add_action('wp_head', array('sem_skin', 'custom_css'), 200);
	} # init()
	
	
	#
	# css()
	#
	
	function css()
	{
		global $sem_options;
		
		$files = array();
		
		if ( file_exists(sem_path . '/skins/' . $sem_options['active_skin'] . '/icons.css') )
		{
			$files[] = 'skins/' . $sem_options['active_skin'] . '/icons.css';
		}
		else
		{
			$files[] = 'css/icons.css';
		}
		
		if ( isset($_GET['action']) && $_GET['action'] == 'print' )
		{
			if ( file_exists(sem_path . '/skins/' . $sem_options['active_skin'] . '/print.css') )
			{
				$files[] = 'skins/' . $sem_options['active_skin'] . '/print.css';
			}
			else
			{
				$files[] = 'css/print.css';
			}
		}
		elseif ( apply_filters('active_layout', $sem_options['active_layout']) == 'letter' )
		{
			if ( file_exists(sem_path . '/skins/' . $sem_options['active_skin'] . '/letter.css') )
			{
				$files[] = 'skins/' . $sem_options['active_skin'] . '/letter.css';
			}
			else
			{
				$files[] = 'css/letter.css';
			}
		}
		else
		{
			$files[] = 'skins/' . $sem_options['active_skin'] . '/skin.css';
		}
		
		foreach ( $files as $file )
		{
			echo '<link rel="stylesheet" type="text/css" href="'
				. sem_url . '/' . $file
				. '" />' . "\n";
		}
	} # css()
	
	
	#
	# custom_css()
	#
	
	function custom_css()
	{
		global $sem_options;
		
		# custom css files
		
		foreach ( array(
			'skins/' . $sem_options['active_skin'] . '/custom.css',
			'custom.css',
			) as $file )
		{
			if ( file_exists(sem_path . '/' . $file ) )
			{
				echo '<link rel="stylesheet" type="text/css" href="'
					. sem_url . '/' . $file
					. '" />' . "\n";
			}
		}
	} # custom_css()
	
	
	#
	# page_class()
	#
	
	function page_class($classes)
	{
		global $sem_options;
		
		$classes[] = preg_replace("/[^a-z]+/", '_', $sem_options['active_skin']);
		
		$template = '';

		if ( is_page() )
		{
			$template = get_post_meta(intval($GLOBALS['wp_query']->get_queried_object_id()), '_wp_page_template', true);
			
			if ( $template != 'default' ) {
				$template = preg_replace("/\.[^\.]+$/", "", $template);

				$classes[] = $template;
			}
		}
		
		$classes[] = preg_replace("/[^a-z]+/", '_', $sem_options['active_font']);
		
		return $classes;
	} # page_class()
	
	
	#
	# get_skin_data()
	#

	function get_skin_data($skin_id)
	{
		if ( !( $skin_data = @file_get_contents(sem_path . '/skins/' . $skin_id . '/skin.css') ) )
		{
			$skin_id = 'copywriter-gold';
			$skin_data = @file_get_contents(sem_path . '/skins/copywriter-gold/skin.css');
			
			if ( !$skin_data ) return array();
		}

		$skin_data = str_replace("\r", "\n", $skin_data);

		preg_match('/Skin(?:\s+name)?\s*:(.*)/i', $skin_data, $name);
		preg_match('/Version\s*:(.*)/i', $skin_data, $version);
		preg_match('/Author\s*:(.*)/i', $skin_data, $author);
		preg_match('/Author\s+ur[il]\s*:(.*)/i', $skin_data, $author_uri);
		preg_match('/Description\s*:(.*)/i', $skin_data, $description);

		#echo '<pre>';
		#var_dump($name, $version, $author, $author_uri, $description);
		#echo '</pre>';

		return array(
			'name' => trim(end($name)),
			'version' => trim(end($version)),
			'author' => trim(end($author)),
			'author_uri' => trim(end($author_uri)),
			'description' => trim(end($description))
			);
	} # end get_skin_data()
} # sem_skin

sem_skin::init();




#
# todo: get rid of the following
#

#
# get_active_skin()
#

function get_active_skin()
{
	global $sem_options;

	return apply_filters('active_skin', $sem_options['active_skin']);
} # end get_active_skin()
?>