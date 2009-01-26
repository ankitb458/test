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
			__('Skin'),
			__('Skin'),
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

		echo '<h2>' . __('Skin Settings') . '</h2>' . "\n";
		
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
		
		echo '<p>' . __('Note that you can create your own skins. Skins are automatically detected, so copying one of the existing ones (wp-content/themes/semiologic/skins/ folder) is the simplest way to start. Don\'t miss the <a href="' . sem_url . '/skins/custom/skin-sample.css">near-exhaustive list of CSS pointers</a> that are available in the sample custom skin.') . '</p>' . "\n";

		$i = 0;
		echo '<table>' . "\n";
		
		foreach ( $skins as $skin_id => $skin_data )
		{
			$i++;
			
			#echo '<div style="text-align: center; width: 360px; height: 360px; float: left; margin-bottom: 12px;'
			#	. ( ( $skin_id == $active_skin )
			#		? ' background-color: #eeeeee;'
			#		: ''
			#		)
			#	. '">';

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
						. ' style="padding: 10px;"'
						. ' />'
					. '</label>'
					. '</p>' . "\n";
			}

			echo '<p>'
				. '<label for="active_skin__' . $skin_id . '">'
				. $skin_data['description']
				. '</label>'
				. '</p>' . "\n";

			#echo '</div>';
			echo '</td>' . "\n";
			
			if ( !( $i % 2 ) )
				echo '</tr>' . "\n";
		}

		echo '</table>' . "\n";
		#echo '<div style="clear: both;"></div>';

		echo '<div class="submit">';
		echo '<input type="submit" value="' . attribute_escape(__('Save Changes')) . '" />';
		echo '</div>' . "\n";
		
		
		echo '</form>' . "\n";
		echo '</div>' . "\n";
	} # admin_page()
} # sem_skin_admin

sem_skin_admin::init();

?>