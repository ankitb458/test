<?php

#
# setup_template()
#

function setup_template($template)
{
	switch ( $template )
	{
	case 'article':
	case 'archives':
	case 'links':
	case 'sell_page':
		do_action('setup_default_advanced_template');
		break;
	}
} # end setup_template()

add_action('setup_template', 'setup_template');


#
# setup_default_advanced_template()
#

function setup_default_advanced_template()
{
	remove_action('display_entry_body', 'display_entry_body');
	add_action('display_entry_body', 'default_advanced_template');
} # end setup_default_advanced_template()

add_action('setup_default_advanced_template', 'setup_default_advanced_template');

#
# default_advanced_template()
#

function default_advanced_template()
{
	echo '<div class="ad">'
		. '<p>'
		. __('This advanced template is part of the <a href="http://www.semiologic.com/solutions/sem-theme-pro/">Semiologic Pro package</a>.')
		. '</p>'
		. '</div>';
} # end default_advanced_template()
?>