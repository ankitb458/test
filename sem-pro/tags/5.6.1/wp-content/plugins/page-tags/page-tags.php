<?php
/*
Plugin Name: Page Tags
Plugin URI: http://www.semiologic.com/software/publishing/page-tags/
Description: Use tags on static pages.
Author: Denis de Bernardy and Mike Koepke
Version: 1.0
Author URI: http://www.semiologic.com
Update Service: http://version.semiologic.com/wordpress
Update Tag: pagetags
Update Package: http://www.semiologic.com/media/software/widgets/publishing/page-tags/page-tags.zip
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


class page_tags
{
	#
	# init()
	#
	function init()
	{
		# page tags
		add_filter('posts_where', array('page_tags', 'add_page_tags_to_where' ));
		
	} # init()

	#
	# add_page_tags_to_where)
	#	
	function add_page_tags_to_where( $where ) 
	{
		if( is_tag() ) 
		{
			$where = preg_replace(
				"/ ([0-9a-zA-Z_]*\.?)post_type = 'post'/",
				"(${1}post_type = 'post' OR ${1}post_type = 'page')", $where );
		}

		return $where;
	} # add_page_tags_to_where
	
} # page-tags

page_tags::init();

if ( is_admin() )
{
	include dirname(__FILE__) . '/page-tags-admin.php';
}
?>