<?php
#
# Wizard Name: Semiologic Autoinstall
# Version: 1.6.2
# Author: Denis de Bernardy
# Author uri: http://www.mesoconcepts.com
# Description: <p>This wizard will restore your initial Semiologic theme settings, including:</p> <ul><li>Skin, layout, font and width</li><li>Header, footer</li><li>Nav menus</li><li>Captions</li><li>Features (Pro)</li><li>Sidebars (Pro)</li></ul>
#


require_once dirname(__FILE__) . '/autoinstall.php';

#
# wiz_autoinstall_step_1()
#

function wiz_autoinstall_step_1()
{
	echo '<p>' . __('<strong>Warning</strong>: Using this wizard will reset your Semiologic settings!') . '</p>';
} # end wiz_autoinstall_step1()

register_wizard_step(1, 'wiz_autoinstall_step_1');


#
# wiz_autoinstall_check_1()
#

function wiz_autoinstall_check_1($step = 1)
{
	install_semiologic();

	return 'done';
} # end wiz_autoinstall_check_1()

register_wizard_check(1, 'wiz_autoinstall_check_1');
?>