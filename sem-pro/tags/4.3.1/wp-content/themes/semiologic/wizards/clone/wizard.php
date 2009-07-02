<?php
#
# Wizard Name: Clone Semiologic Pro Blog
# Version: 1.6
# Author: Denis de Bernardy
# Author uri: http://www.mesoconcepts.com
# Description: <p>This wizard will let you import an existing site's configuration into this site. This includes</p> <ul><li>WordPress preferences</li><li>Presentation preferences</li><li>Ad Spaces preferences</li><li>User preferences (except permissions and password)</li></ul>
#


if ( file_exists(ABSPATH . 'wp-content/plugins/semiologic/wizards/clone/wizard.php')
	&& !function_exists('get_site_option')
	)
{
	include_once ABSPATH . 'wp-content/plugins/semiologic/wizards/clone/wizard.php';
}
else
{
	register_wizard_step(1, 'pro_feature_notice');
	register_wizard_check(1, 'fail_wiz_step');
}
?>