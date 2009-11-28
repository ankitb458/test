<?php
#
# Step 1
# ------
# Set theme defaults
#

# Skin, layout, font
$sem_options['active_skin'] = 'sky-gold';
$sem_options['active_layout'] = 'ms';
$sem_options['active_width'] = 'wide';
$sem_options['active_font'] = '';
$sem_options['active_font_size'] = 'small';

# Credits
$sem_options['credits'] = __('Made with %1$s &bull; %2$s skin by %3$s', 'sem-theme');

# Version
$sem_options['version'] = sem_version;

add_option('init_sem_panels', '1');

# Update
if ( !defined('sem_install_test') )
	update_option('sem5_options', $sem_options);
?>