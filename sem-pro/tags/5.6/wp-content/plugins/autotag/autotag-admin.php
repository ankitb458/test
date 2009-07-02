<?php
class autotag
{
	#
	# init()
	#

	function init()
	{
		add_action('admin_menu', array('autotag', 'add_meta_boxes'));
		add_action('save_post', array('autotag', 'save_entry'));

		add_filter('sem_api_key_protected', array('autotag', 'sem_api_key_protected'));
	} # init()
	
		
	#
	# sem_api_key_protected()
	#
	
	function sem_api_key_protected($array)
	{
		$array[] = 'http://www.semiologic.com/media/software/publishing/autotag/autotag.zip';
		
		return $array;
	} # sem_api_key_protected()
	
	
	#
	# add_meta_boxes()
	#
	
	function add_meta_boxes()
	{
		add_meta_box('autotag', 'Autotag', array('autotag', 'entry_editor'), 'post', 'normal');
	}


	#
	# entry_editor()
	#

	function entry_editor()
	{
		global $user_ID;
		$post_ID = isset($GLOBALS['post_ID']) ? $GLOBALS['post_ID'] : $GLOBALS['temp_ID'];
		
		$published = false;
		
		if ( $post_ID > 0 )
		{
			$post =& get_post($post_ID);
			$published = ( $post->post_status == 'publish' );
		}
		
		echo '<p>'
			. __('Create tags automatically?')
			. '</p>' . "\n";
		
		if ( !$published
			&& !( $post_ID > 0 && get_post_meta($post_ID, '_did_autotag', true) ) )
		{
			if ( $post_ID > 0 && get_post_meta($post_ID, '_autotag', true)
				|| $post_ID < 0 && get_usermeta($user_ID, 'autotag')
				)
			{
				$default = 'publish';
			}
			else  
			{
				$default = '';
			}

			echo '<p>'
				. '<label>'
				. '<input type="radio" name="sem_autotag" value=""'
					. ( $default == ''
						? ' checked="checked"'
						: '' )
					. ' />'
				. '&nbsp;' . 'No, thanks!'
				. '</label>' . '<br />' . "\n"
				. '<label>'
				. '<input type="radio" tabindex="4" name="sem_autotag" value="now" />'
				. '&nbsp;' . 'Yes: Fetch some tags upon saving, I\'d like to edit the resulting list.'
				. '</label>' . '<br />' . "\n"
				. '<label>'
				. '<input type="radio" tabindex="4" name="sem_autotag" value="publish"'
					. ( $default == 'publish'
						? ' checked="checked"'
						: '' )
					. ' />'
				. '&nbsp;' . 'Yes, but not yet: Do so upon publishing this entry.'
				. '</label>'
				. '</p>' . "\n";
				
			echo '<input type="hidden" name="sem_autotag_sticky" value="1" />';
		}
		else
		{
			echo '<p>'
				. '<label>'
				. '<input type="radio" tabindex="4" name="sem_autotag" value="" checked="checked" />'
				. '&nbsp;' . 'No, thanks!'
					. ' '
					. ( ( $post_ID > 0 && get_post_meta($post_ID, '_did_autotag', true) )
						? 'I already did this.'
						: 'This entry is published material.'
						)
				. '</label>' . '<br />' . "\n"
				. '<label>'
				. '<input type="radio" tabindex="4" name="sem_autotag" value="now" />'
				. '&nbsp;' . 'Yes: Fetch some new tags.'
				. '</label>'
				. '</p>' . "\n";
		}
		
		echo '<p>'
			. '<label>'
			. '<input type="checkbox" tabindex="4" name="sem_autotag_strip"'
				. ( get_usermeta($user_ID, 'autotag_strip')
					? ' checked="checked"'
					: ''
					)
				. ' />'
				. '&nbsp;'
				. 'Only keep terms that match existing tags'
				. '</label>'
				. '</p>' . "\n";
		
		echo '<p>'
			. __('<b>Notice</b>: Autotag will only work provided your server\'s IP address hasn\'t hit Yahoo\'s web services 5,000 times in the past 24h. If it stops working, give it a new try the next day.')
			. '</p>';
	} # entry_editor()


	#
	# save_entry()
	#

	function save_entry($post_ID)
	{
		if ( isset($_POST['sem_autotag']) )
		{
			global $user_ID;
			
			$fetch_terms = false;
			$user_pref = false;
			
			$post = get_post($post_ID);
			
			switch ( $_POST['sem_autotag'])
			{
			case 'publish':
				if ( in_array($post->post_status, array('publish', 'future')) )
				{
					$fetch_terms = true;
					$user_pref = true;

					delete_post_meta($post_ID, '_autotag');
				}
				else
				{
					$user_pref = true;

					add_post_meta($post_ID, '_autotag', 1, true);
				}
				break;
			
			case 'now':
				$fetch_terms = true;
			
			default:
				delete_post_meta($post_ID, '_autotag');
				$user_pref = false;
				break;
			}
			
			if ( $fetch_terms )
			{
				if ( !class_exists('extract_terms') )
				{
					include dirname(__FILE__) . '/extract-terms.php';
				}
				
				$terms = extract_terms::get_post_terms($post);

				if ( $terms )
				{
					if ( isset($_POST['sem_autotag_strip']) )
					{
						foreach ( $terms as $key => $term )
						{
							if ( !is_term($term, 'post_tag') )
							{
								unset($terms[$key]);
							}
						}
					}
					
					if ( count($terms) > 3 )
					{
						$terms = array_slice($terms, 0, 3 + round(log(count($terms))));
				 	}

					$tags = '';

					foreach ( $terms as $term )
					{
						$tags .= ( $tags ? ', ' : '' ) . $term;
					}

					wp_set_post_tags($post_ID, $tags, true);
					
					add_post_meta($post_ID, '_did_autotag', 1, true);
				}
			}
			
			if ( $_POST['sem_autotag_sticky'] )
			{
				if ( $user_pref )
				{
					if ( !( $user_prefs = get_usermeta($user_ID, 'autotag') ) )
					{
						update_usermeta($user_ID, 'autotag', '1');
					}
				}
				else
				{
					update_usermeta($user_ID, 'autotag', '0');
				}
				
				if ( isset($_POST['sem_autotag_strip']) )
				{
					update_usermeta($user_ID, 'autotag_strip', '1');
				}
				else
				{
					update_usermeta($user_ID, 'autotag_strip', '0');
				}
			}
		}
	} # save_entry()
} # end autotag

autotag::init();
?>