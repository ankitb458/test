<?php

class sem_skin_admin
{
	#
	# init()
	#
	
	function init()
	{
		add_action('admin_menu', array('sem_skin_admin', 'admin_menu'));
	} # init()
	
	
	#
	# admin_menu()
	#
	
	function admin_menu()
	{
		add_theme_page(
			__('Skin &amp; Font'),
			__('Skin &amp; Font'),
			'switch_themes',
			basename(__FILE__),
			array('sem_skin_admin', 'admin_page')
			);
	} # admin_menu()
	
	
	#
	# update_options()
	#
	
	function update_options()
	{
		check_admin_referer('sem_skin');

		global $sem_options;

		$sem_options['active_skin'] = preg_replace("/\//", '', $_POST['active_skin']);
		$sem_options['skin_data'] = sem_skin::get_skin_data($sem_options['active_skin']);
		$sem_options['active_font'] = preg_replace("/[^a-z]/", '', $_POST['active_font']);
		
		update_option('sem6_options', $sem_options);
	} # update_options()
	
	
	#
	# admin_page()
	#
	
	function admin_page()
	{
		echo '<div class="wrap">' . "\n";
		echo '<form method="post" action="">' . "\n";

		if ( $_POST['update_theme_skin_options'] )
		{
			sem_skin_admin::update_options();

			echo '<div class="updated">' . "\n"
				. "<p>"
					. "<strong>"
					. __('Settings saved.')
					. "</strong>"
				. "</p>" . "\n"
				. "</div>" . "\n";
		}

		if ( function_exists('wp_nonce_field') ) wp_nonce_field('sem_skin');

		echo '<input type="hidden"'
			. ' name="update_theme_skin_options"'
			. ' value="1"'
			. ' />' . "\n";

		echo '<h2>' . __('Skin &amp; Font Settings') . '</h2>' . "\n";
		
		echo '<h3>' . __('Skin Settings') . '</h3>' . "\n";
		
		global $sem_options;
		
		$skins = (array) glob(sem_path . '/skins/*/skin.css');

		foreach ( array_keys($skins) as $key )
		{
			$skin_id = basename(dirname($skins[$key]));

			unset($skins[$key]);

			$skins[$skin_id] = sem_skin::get_skin_data($skin_id);
		}

		ksort($skins);

		if ( !$sem_options['active_skin']
			|| !file_exists(sem_path . '/skins/' . $sem_options['active_skin'] . '/skin.css')
			)
		{
			$sem_options['active_skin'] = 'copywriter-gold';
			update_option('sem6_options', $sem_options);
		}
		
		$active_skin = $sem_options['active_skin'];
		
		echo '<p>' . __('Note that you can create your own skins. Skins are automatically detected, so copying one of the existing ones (in the theme\'s skins folder) is the simplest way to start. Scanning through the various default ones will reveal much about every CSS pointer there is to know.') . '</p>' . "\n";

		$i = 0;
		$last_id = key(array_reverse($skins));
		
		echo '<table>' . "\n";
		
		
		foreach ( $skins as $skin_id => $skin_data )
		{
			$i++;

			if ( $i % 2 )
				echo '<tr align="center">' . "\n";
			
			echo '<td'
				. ( ( $skin_id == $active_skin )
					? ' style="width: 50%; background-color: #ddd;"'
					: ' style="width: 50%;"'
					)
				. '>' . "\n";
			
			echo '<h3>'
					. '<label for="active_skin__' . $skin_id . '">'
					. '<input type="radio"'
						. ' id="active_skin__' . $skin_id . '" name="active_skin"'
						. ' value="' . $skin_id . '"'
						. ( ( $skin_id == $active_skin )
							? ' checked="checked"'
							: ''
							)
						. ' />'
					. '&nbsp;'
					. $skin_data['name']
					. ' '
					. $skin_data['version']
					. '</label>'
					. '<br />'
					. __('by') . ' '
					. '<a href="' . $skin_data['author_uri'] . '">'
					. $skin_data['author']
					. '</a>'
				. '</h3>' . "\n";

			if ( file_exists(sem_path . '/skins/' . $skin_id . '/screenshot.png') )
			{
				echo '<p>'
					. '<label for="active_skin__' . $skin_id . '">'
					. '<img src="'
						. get_template_directory_uri()
						. '/skins/' . $skin_id . '/screenshot.png"'
						. ' />'
					. '</label>'
					. '</p>' . "\n";
			}

			echo '<p>'
				. '<label for="active_skin__' . $skin_id . '">'
				. $skin_data['description']
				. '</label>'
				. '</p>' . "\n";

			echo '</td>' . "\n";
			
			if ( $i % 2 && $skin_id == $last_id ) {
				echo '<td>&nbsp;</td>' . "\n";
				$i++;
			}
			
			if ( !( $i % 2 ) ) {
				echo '</tr>' . "\n"
					. '<tr>' . "\n"
					. '<td colspan="2">'
					. '<div class="submit">'
					. '<input type="submit" value="' . attribute_escape(__('Save Changes')) . '" />'
					. '</div>' . "\n"
					. '</td>'
					. '</tr>' . "\n";
			}
		}

		echo '</table>' . "\n";
		
		echo '<h3>' . __('Font Settings') . '</h3>' . "\n";
		
		echo '<p>' . __('This will let you set the default font on your site.') . '</p>' . "\n";
		
		echo '<ul>' . "\n";
		
		foreach ( array(
			'' =>  __('Use the skin\'s default'),
			'arial' => __('Arial, Helvetica, Sans-Serif'),
			'antica' => __('Book Antica, Times, Serif'),
			'bookman' => __('Bookman Old Style, Times, Serif'),
			'comic' => __('Comic Sans MS, Helvetica, Sans-Serif'),
			'courier' => __('Courier New, Courier, Monospace'),
			'garamond' => __('Garamond, Times, Serif'),
			'georgia' => __('Georgia, Times, Serif'),
			'corsiva' => __('Monotype Corsiva, Courier, Monospace'),
			'tahoma' => __('Tahoma, Helvetica, Sans-Serif'),
			'times' => __('Times New Roman, Times, Serif'),
			'trebuchet' => __('Trebuchet MS, Tahoma, Helvetica, Sans-Serif'),
			'verdana' => __('Verdana, Helvetica, Sans-Serif'),
			) as $k => $v ) {
			echo '<li>'
				. '<label'
					. ( $k
						? ( ' style="font-family:' . htmlspecialchars($v) . ';"' )
						: ''
						)
					. '>'
				. '<input type="radio" name="active_font" value="' . $k . '"'
					. ( $k == $sem_options['active_font']
						? ' checked="checked"'
						: ''
						)
					. '/>'
				. '&nbsp;'
				. $v
				. '</label>'
				. '</li>' . "\n";
		}
		
		echo '</ul>' . "\n";
		
		echo '<div class="submit">'
			. '<input type="submit" value="' . attribute_escape(__('Save Changes')) . '" />'
			. '</div>' . "\n";
		
		echo '</form>' . "\n";
		echo '</div>' . "\n";
	} # admin_page()
} # sem_skin_admin

sem_skin_admin::init();

?>