<?php
/*
Plugin Name: Page Tags
Plugin URI: http://www.semiologic.com/software/publishing/page-tags/
Description: Use tags on static pages.
Author: Denis de Bernardy
Version: 1.1 alpha
Author URI: http://www.getsemiologic.com
Update Service: http://version.semiologic.com/plugins
Update Tag: page_tags
Update Package: http://www.semiologic.com/media/software/widgets/publishing/page-tags/page-tags.zip
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


if ( is_admin() )
{
	include dirname(__FILE__) . '/page-tags-admin.php';
}
?>