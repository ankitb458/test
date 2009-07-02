<?php

class bookmark_me_admin
{
	#
	# init()
	#

	function init()
	{
		add_action('admin_menu', array('bookmark_me_admin', 'add_admin_page'));
	} # init()

	#
	# add_admin_page()
	#

	function add_admin_page()
	{
		add_options_page(
				__('Bookmark&nbsp;Me'),
				__('Bookmark&nbsp;Me'),
				'manage_options',
				str_replace("\\", "/", __FILE__),
				array('bookmark_me_admin','display_admin_page')
				);
	} # add_admin_page()


	#
	# update_options()
	#

	function update_options()
	{
		check_admin_referer('bookmark_me');

		$options = array(
			'services' => $_POST['sites'],
			'show_names' => isset($_POST['show_names']),
			'add_nofollow' => isset($_POST['add_nofollow'])
			);

		update_option('sem_bookmark_me_params', $options);
	} # update_options()


	#
	# display_admin_page()
	#

	function display_admin_page()
	{
		echo '<form method="post" action="">';

		if ( function_exists('wp_nonce_field') ) wp_nonce_field('bookmark_me');

		if ( $_POST['update_bookmark_me_options'] )
		{
			echo "<div class=\"updated\">\n"
				. "<p>"
					. "<strong>"
					. __('Options saved.')
					. "</strong>"
				. "</p>\n"
				. "</div>\n";
		}

		echo '<div class="wrap">'
			. '<h2>'. __('Bookmark Me options') . '</h2>';

		if ( $_POST['update_bookmark_me_options'] )
		{
			bookmark_me_admin::update_options();
		}

		echo '<input type="hidden" name="update_bookmark_me_options" value="1" />';

		$title = urlencode(the_title(null, null, false));
		$site_name = urlencode(get_bloginfo('sitename'));

		$options = get_settings('sem_bookmark_me_params');

		if ( !$options )
		{
			$options = array(
				'services' => bookmark_me::default_services(),
				'show_names' => true
				);
			update_option('sem_bookmark_me_params', $options);
		}


		echo '<p><label for="show_names">'
			. '<input type="checkbox"'
				. ' id="show_names" name="show_names"'
				. ( ( !isset($options['show_names']) || $options['show_names'] )
				? ' checked="checked"'
				: ''
				)
				. ' />'
			. '&nbsp;'
			. __('Display Service Names')
			. '</label>'
			. '</p>';

		echo '<p><label for="add_nofollow">'
			. '<input type="checkbox"'
				. ' id="add_nofollow" name="add_nofollow"'
				. ( ( $options['add_nofollow'] )
				? ' checked="checked"'
				: ''
				)
				. ' />'
			. '&nbsp;'
			. __('Add nofollow')
			. '</label>'
			. '</p>';

		echo '<h3>'
			. __('Services')
			. '</h3>'
			. '<ul>';

		foreach ( bookmark_me::get_services() as $site_id => $site_info )
		{
			echo '<li>'
				. '<label for="sites[' . $site_id . ']">'
				. '<span'
				. ' style="'
					. 'padding-left: 20px;'
					. ' background: url('
						. trailingslashit(get_option('siteurl'))
						. 'wp-content/plugins/sem-bookmark-me/img/'
						. $site_id . '.gif'
						. ') center left no-repeat;'
						. '"'
				. '>'
				. '<input type="checkbox"'
					. ' name="sites[]" id="sites[' . $site_id . ']"'
					. ' value="' . $site_id . '"'
					. ( in_array($site_id, (array) $options['services']) ? ' checked="checked"' : '' )
					. ' />'
				. ' '
				. __($site_info['name'])
				. '</span>'
				. '</label>'
				. '</li>';
		}

		echo '</ul>';

		echo '<p class="submit">'
			. '<input type="submit"'
				. ' value="' . __('Update Options') . '"'
				. ' />'
			. '</p>'
			. '</div>';
	} # display_admin_page()
} # bookmark_me_admin()

bookmark_me_admin::init();
?>