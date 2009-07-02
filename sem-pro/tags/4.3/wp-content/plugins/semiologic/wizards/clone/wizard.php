<?php

require_once dirname(__FILE__) . '/import.php';
require_once ABSPATH . 'wp-content/plugins/sem-ad-space/sem-ad-space-admin.php';


register_wizard_step(1, 'request_cloned_site_details');

register_wizard_check(1, 'check_cloned_site_details');

register_wizard_step(2, 'notify_site_cloned');

register_wizard_check(2, 'check_notify_site_cloned');
?>