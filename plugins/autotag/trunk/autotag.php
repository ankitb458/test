<?php
/*
Plugin Name: AutoTag
Plugin URI: http://www.semiologic.com/software/publishing/autotag/
Description: Leverages Yahoo!'s term extraction web service to automatically tag your posts.
Version: 2.1.2 alpha
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Update Service: http://version.semiologic.com/plugins
Update Tag: autotag
Update Package: http://www.semiologic.com/media/software/publishing/autotag/autotag.zip
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


if ( is_admin() )
{
	include dirname(__FILE__) . '/autotag-admin.php';
}
?>