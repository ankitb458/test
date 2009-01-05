<?PHP
/*
Plugin Name: Featured Widgets
Plugin URI: http://www.semiologic.com/software/widgets/featured-widgets/
Description: Creates a special sidebar that lets you insert widgets at the end of each post in your RSS feed. Configure these widgets under Design / Widgets, by selecting the Feed Widgets sidebar. To make the best of this plugin, be sure to configure the full text feed setting (under Settings / Reading).
Author: Siddiqui
Version: 1.0
Author URI: http://www.getsemiologic.com
Update Service: http://version.semiologic.com/plugins
Update Tag: featured_widgets
Update Package: http://www.semiologic.com/media/software/widgets/featured-widgets/featured-widgets.zip
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/

class featured_widgets
{
	
	// init();
	function init()
	{
			add_action('widgets_init', array('featured_widgets', 'widgetize'));
	
	
	}// ------------------------------------------------- End of Function init();

}// --------------------------------------------------- End of Class Featured Widgets

featured_widgets::init();

if ( is_admin() )
{
	include dirname(__FILE__).'/featured_widgets-admin.php';
}
?>