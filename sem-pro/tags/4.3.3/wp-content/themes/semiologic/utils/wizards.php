<?php
#
# do_wizard()
#

function do_wizard($wizard, $step = 1)
{
	$wizard = preg_replace("/[^a-z0-9_-]/i", "", $wizard);

	if ( $wizard )
	{
		include_once(get_template_directory() . '/wizards/' . $wizard . '/wizard.php');

		do_action('wizard', $step);

#echo '<pre>';
#var_dump($GLOBALS['semiologic']);
#echo '</pre>';
	}
} # end do_wizard()


#
# wizard_done()
#

function wizard_done()
{
	# Regenerate nav menus
	include_once TEMPLATEPATH . '/admin/nav-menus.php';
	regen_theme_nav_menu_cache();

	echo "<div class=\"updated\">\n"
		. "<p>"
			. "<strong>"
			. __('Wizard Done!')
			. "</strong>"
		. "</p>\n"
		. "</div>\n";

	display_all_wizards();
} # end wizard_done()


#
# register_wizard_step()
#

function register_wizard_step($step, $callback)
{
	$GLOBALS['wizard_steps'][$step] = $callback;
} # end register_wizard_step()


#
# do_wizard_step()
#

function do_wizard_step($step)
{
	if ( isset($GLOBALS['wizard_steps'][$step]) )
	{
		$GLOBALS['wizard_steps'][$step]($step);
	}
} # end do_wizard_step()

add_action('do_wizard_step', 'do_wizard_step');


#
# register_wizard_check()
#

function register_wizard_check($step, $callback)
{
	$GLOBALS['wizard_checks'][$step] = $callback;
} # end register_wizard_check()


#
# do_wizard_check()
#

function do_wizard_check($step)
{
	if ( isset($GLOBALS['wizard_checks'][$step]) )
	{
		return $GLOBALS['wizard_checks'][$step]($step);
	}
	else
	{
		return $step + 1;
	}
} # end do_wizard_check()

add_action('do_wizard_check', 'do_wizard_check');


#
# fail_wiz_step()
#

function fail_wiz_step($step)
{
	return $step;
} # end fail_wiz_step()
?>