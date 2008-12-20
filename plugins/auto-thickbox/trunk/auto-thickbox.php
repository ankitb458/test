<?php
/*
Plugin Name: Auto Thickbox
Plugin URI: http://www.semiologic.com/software/publishing/auto-thickbox/
Description: Automatically enables thickbox on thumbnail images (i.e. opens them in a fancy pop-up). You can disable the latter feature for individual images by adding the 'nothickbox' class to its anchor tag. To bind images together in a gallery, add an identical rel attribute. The latter is done automatically in each post, on images anchors that have no rel attribute already. Lastly, it lets you open any link in a thickbox by adding the thickbox class to its anchor tag.
Author: Denis de Bernardy
Version: 0.1 alpha
Author URI: http://www.getsemiologic.com
Update Service: http://version.semiologic.com/plugins
Update Tag: auto_thickbox
Update Package: http://www.semiologic.com/media/software/publishing/auto-thickbox/auto-thickbox.zip
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/

class auto_thickbox
{
	#
	# init()
	#
	
	function init()
	{
		if ( !is_admin() )
		{
			add_action('wp_print_scripts', array('auto_thickbox', 'add_scripts'));
			add_action('wp_print_styles', array('auto_thickbox', 'add_css'));
			
			add_action('wp_head', array('auto_thickbox', 'add_thickbox_images'), 20);
			
			add_filter('the_content', array('auto_thickbox', 'add_thickbox'), 20);
			add_filter('the_excerpt', array('auto_thickbox', 'add_thickbox'), 20);
		}
	} # init()
	
	
	#
	# add_thickbox()
	#
	
	function add_thickbox($content)
	{
		$content = preg_replace_callback("/
			<\s*a\s					# an achnor...
				(.*\s)?
				href\s*?=\s*?(.+)	# (catch href)
				(\s.*)?
				>\s*
			(<\s*img\s.+>)\s*		# ... on an image
			<\s*\/\s*a\s*>
			/isUx", array('auto_thickbox', 'add_thickbox_callback'), $content);
		
		return $content;
	} # add_thickbox()
	
	
	#
	# add_thickbox_callback()
	#
	
	function add_thickbox_callback($match)
	{
		#dump($match);
		
		# trim surrounding quotes
		$href = trim(trim($match[2]), '\'"');
		
		# return if not an image
		if ( !preg_match("/\.(jpe?g|gif|png)$/i", $href) ) return $match[0];
		
		$attr = ' ' . $match[1] . $match[3] . ' ';
		$img = $match[4];
		
		# add thickbox class
		if ( !preg_match("/
				(\sclass\s*=\s*(.+?))(?:$|\s[a-z]+=)
			/ix", $attr, $class)
			)
		{
			$attr .= ' class="thickbox"';
		}
		else
		{
			# trim surrounding quotes
			$old_class = trim(trim($class[2]), '\'"');
			
			if ( strpos($old_class, 'thickbox') !== false )
			{
				$new_class = $old_class . ' thickbox';

				# replace class
				$attr = str_replace($class[0], 'class="' . $new_class . '"', $attr);
			}
		}
		
		# add gallery rel if no rel is present
		if ( in_the_loop()
			&& !preg_match("/\srel\s*=\s*.+?(?:$|\s[a-z]+=)/ix", $attr, $rel)
			)
		{
			$attr .= ' rel="gallery-' . get_the_ID() . '"';
		}
		
		return '<a href="' . $href . '" ' . $attr . '>' . $img . '</a>';
	} # add_thickbox_callback()
	
	
	#
	# add_scripts()
	#
	
	function add_scripts()
	{
		wp_enqueue_script('thickbox');
	} # add_scripts()
	
	
	#
	# add_css()
	#
	
	function add_css()
	{
		wp_enqueue_style('thickbox');
	} # add_css()
	
	
	#
	# add_thickbox_images()
	#
	
	function add_thickbox_images()
	{
		$site_url = rtrim(get_option('siteurl'), '/');
		
		$js = <<<EOF

<script type="text/javascript">
var tb_pathToImage = "$site_url/wp-includes/js/thickbox/loadingAnimation.gif";
var tb_closeImage = "$site_url/wp-includes/js/thickbox/tb-close.png";
</script>

EOF;
		
		echo $js;
	} # add_thickbox_images()
} # auto_thickbox

auto_thickbox::init();
?>