<?php

class sem_tips
{
	#
	# init()
	#

	function init()
	{
		add_action('edit_user_profile', array('sem_tips', 'display_prefs'));
		add_action('profile_update', array('sem_tips', 'save_prefs'));

		add_action('show_user_profile', array('sem_tips', 'display_prefs'));
		add_action('personal_options_update', array('sem_tips', 'save_prefs'));
	} # init()


	#
	# display_prefs()
	#

	function display_prefs()
	{
		$author_id = $GLOBALS['profileuser']->user_login;

		$user = get_userdatabylogin($author_id);

		$user_id = $user->ID;

		echo '<fieldset>'
			. '<legend>'
			. __('Guru Tips')
			. '</legend>';

		if ( !sem_pro )
		{
			pro_feature_notice();
		}

		$options = sem_tips::get_options($user_id);

		echo '<p>'
			. '<label for="sem_tips[show_tips]">'
			. '<input type="checkbox"'
				. ' id="sem_tips[show_tips]" name="sem_tips[show_tips]"'
				. ' style="width: auto;"'
				. ( !sem_pro
					? ' disabled="disabled"'
					: ''
					)
				. ( $options['show_tips']
					? ' checked="checked"'
					: ''
					)
				. '>'
			. '&nbsp;'
			. __('Show guru tips at startup.')
			. '</label>'
			. '</p>';

		echo '<p>'
			. '<label for="sem_tips[reset_tips]">'
			. '<input type="checkbox"'
				. ' id="sem_tips[reset_tips]" name="sem_tips[reset_tips]"'
				. ' style="width: auto;"'
				. ( !sem_pro
					? ' disabled="disabled"'
					: ''
					)
				. '>'
			. '&nbsp;'
			. __('Reset guru tips.')
			. '</label>'
			. '</p>';

		echo '</fieldset>';
	} # display_prefs()


	#
	# save_prefs()
	#

	function save_prefs($user_ID)
	{
		if ( sem_pro )
		{
			$options = sem_tips::get_options($user_ID);

			$options['show_tips'] = isset($_POST['sem_tips']['show_tips']);

			if ( isset($_POST['sem_tips']['reset_tips']) )
			{
				$options['tip_id'] = false;

				@session_start();
				$_SESSION['sem_tip_showed'] = false;
			}

			sem_tips::update_options($options, $user_ID);
		}

		return $user_ID;
	} # save_image()


	#
	# get_options()
	#

	function get_options($user_id = 0)
	{
		if ( !$user_id )
		{
			$user = wp_get_current_user();

			$user_id = $user->ID;
		}

		$options = get_usermeta($user_id, 'sem_tips');

		# debug
		# $options = false;

		if ( !$options )
		{
			$options = array(
				'tip_id' => false,
				'show_tips' => true
				);

			update_usermeta($user_id, 'sem_tips', $options);
		}

		return $options;
	} # get_options()


	#
	# update_options()
	#

	function update_options($options, $user_id = 0)
	{
		if ( !$user_id )
		{
			$user = wp_get_current_user();

			$user_id = $user->ID;
		}

		update_usermeta($user_id, 'sem_tips', $options);
	} # update_options()
} # sem_tips

sem_tips::init();
?>