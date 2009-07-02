<?php
#
# add_wizard_admin()
#

function add_wizard_admin()
{
	add_submenu_page(
		'themes.php',
		__('Wizards'),
		__('Wizards'),
		'administrator',
		str_replace("\\", "/", basename(__FILE__)),
		'display_wizard_admin'
		);
} # end add_wizard_admin()

add_action('admin_menu', 'add_wizard_admin');


#
# display_wizard_admin()
#

function display_wizard_admin()
{
	$step = isset($_REQUEST['step']) ? $_REQUEST['step'] : '1';

	if ( !isset($_REQUEST['step']) )
	{
		add_action('display_wizard', 'display_all_wizards');
	}
	elseif ( !empty($_POST)
		&& isset($_POST['action'])
		&& $_POST['action'] == 'wizard'
		)
	{
		check_admin_referer('sem_wizard');
		add_action('display_wizard', 'display_wizard_step');
		do_wizard($_POST['wizard'], $step);
	}

	do_action('display_wizard');
} # end display_wizard_admin()


#
# get_wizard_data()
#

function get_wizard_data($wizard_id)
{
	$wizard_data = file_get_contents(TEMPLATEPATH . '/wizards/' . $wizard_id . '/wizard.php');

	$wizard_data = str_replace("\r", "\n", $wizard_data);

	preg_match('/wizard(?:\s+name)?\s*:(.*)/i', $wizard_data, $name);
	preg_match('/Version\s*:(.*)/i', $wizard_data, $version);
	preg_match('/Author\s*:(.*)/i', $wizard_data, $author);
	preg_match('/Author\s+ur[il]\s*:(.*)/i', $wizard_data, $author_uri);
	preg_match('/Description\s*:(.*)/i', $wizard_data, $description);

#	echo '<pre>';
#	var_dump($name, $version, $author, $author_uri, $description);
#	echo '</pre>';

	return array(
		'name' => trim(end($name)),
		'version' => trim(end($version)),
		'author' => trim(end($author)),
		'author_uri' => trim(end($author_uri)),
		'description' => trim(end($description))
		);
} # end get_wizard_data()


#
# display_all_wizards()
#

function display_all_wizards()
{
	$wizards = (array) glob(TEMPLATEPATH . '/wizards/*/wizard.php');

	sort($wizards);

	foreach ( array_keys($wizards) as $key )
	{
		$wizard_id = basename(dirname($wizards[$key]));

		unset($wizards[$key]);

		$wizards[$wizard_id] = get_wizard_data($wizard_id);
	}

	ksort($wizards);

	echo '<div class="wrap">';

	foreach ( $wizards as $wizard_id => $wizard_data )
	{
		echo '<div style="width: 480px; height: 360px; float: left; margin: 12px; border: solid 1px gainsboro;'
			. '">';

		echo '<form method="post" action="' . trailingslashit(get_settings('siteurl')). 'wp-admin/themes.php?page=wizards.php' . '" style="padding: 12px;">';

		if ( function_exists('wp_nonce_field') ) wp_nonce_field('sem_wizard');

		echo '<input type="hidden"'
			. ' name="action"'
			. ' value="wizard"'
			. '>';

		echo '<input type="hidden"'
			. ' name="wizard"'
			. ' value="' . $wizard_id . '"'
			. '>';

		echo '<input type="hidden"'
			. ' name="step"'
			. ' value="0"'
			. '>';

		echo '<h3 style="text-align: center;">'
				. $wizard_data['name']
				. ' '
				. $wizard_data['version']
				. '<br />'
				. __('by') . ' '
				. '<a href="' . $wizard_data['author_uri'] . '">'
				. $wizard_data['author']
				. '</a>'
			. '</h3>';

		echo '<p>'
			. $wizard_data['description']
			. '</p>';

		echo '<div class="submit">';
		echo '<input type="submit" value="' . __('Start') . ' &raquo;" />';
		echo '</div>';

		echo '</form>';

		echo '</div>';
	}

	echo '<div style="clear: both;"></div>';

	echo '</div>';
} # end display_all_wizards()


#
# display_wizard_step()
#

function display_wizard_step()
{
	$step = $_REQUEST['step'];

	#var_dump($step);

	$step = apply_filters('do_wizard_check', $step);

	#var_dump($step);

	if ( $step === 'done' )
	{
		wizard_done();
	}
	else
	{
		echo '<div class="wrap">';

		echo '<div style="border: solid 1px gainsboro;'
				. '">';

		echo '<form method="post" action="" style="padding: 12px;">';

		if ( function_exists('wp_nonce_field') ) wp_nonce_field('sem_wizard');

		echo '<input type="hidden" name="action" value="wizard" />';
		echo '<input type="hidden" name="wizard" value="'. $_REQUEST['wizard'] . '" />';
		echo '<input type="hidden" name="step" value="' . $step . '" />';

		$wizard_data = get_wizard_data($_REQUEST['wizard']);

		echo '<h3 style="text-align: center;">'
				. $wizard_data['name']
				. ' '
				. $wizard_data['version']
				. '<br />'
				. __('by') . ' '
				. '<a href="' . $wizard_data['author_uri'] . '">'
				. $wizard_data['author']
				. '</a>'
			. '</h3>';

		do_action('do_wizard_step', $step);

		echo '<div class="submit">';
		echo '<input type="submit" value="' . __('Next') . ' &raquo;" />';
		echo '</div>';

		echo '</form>';
		echo '</div>';
		echo '</div>';
	}
} # end display_wizard_step()
?>