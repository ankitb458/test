<?php
/*
Plugin Name: Wysiwyg Editor
Plugin URI: http://www.semiologic.com/software/publishing/wysiwyg-editor/
Description: A more powerful Wysiwyg Editor than the one that comes with WordPress. <strong>Be sure to activate the rich text editor in your user preferences, under Users / Your Profile.</strong>
Author: Denis de Bernardy and Mike Koepke
Version: 2.4
Author URI: http://www.mikekoepke.com
Update Service: http://version.mesoconcepts.com/wordpress
Update Tag: wysiwyg_editor
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/
**/




#
# sem_fck_tags()
#

function sem_fck_tags($content)
{
	#echo '<pre>';
	#var_dump(htmlspecialchars($content));
	#echo '</pre>';

	if ( function_exists('wpcf_callback') )
	{
		$content = preg_replace(
			"/
				(?:<br\s*\/>|\n|\r)*
				<!--\s*contactform\s*-->
				(?:<br\s*\/>|\n|\r)*
			/ix",
			"<!--contactform-->",
			$content
			);

		$content = preg_replace(
			"/
				(?:<p>)?
				<!--\s*contactform\s*-->
				(?:<\/p>)?
			/ix",
			"<!--contactform-->",
			$content
			);

		$content = str_replace(
			"<!--contactform-->",
			"\n\n<div>[CONTACT-FORM]</div>\n\n",
			$content
			);
	}

	return $content;
} # end sem_fck_tags()

add_filter('the_content', 'sem_fck_tags', 0);



if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false )
{
	include_once dirname(__FILE__) . '/sem-wysiwyg-admin.php';
}
?>