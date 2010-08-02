<?php
# Skin, layout, font
$sem_options['active_skin'] = 'classy-citrus';
$sem_options['active_layout'] = 'mts';
$sem_options['active_font'] = '';

# Credits
$sem_options['credits'] = __('Made with %1$s &bull; %2$s skin by %3$s', 'sem-reloaded');

# Version
$sem_options['version'] = sem_version;

add_option('init_sem_panels', '1');

# Update
if ( !defined('sem_install_test') )
	update_option('sem6_options', $sem_options);
?>