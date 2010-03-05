<?php
/**
 * autotag_admin
 *
 * @package AutoTag
 **/

class autotag_admin {
	/**
	 * entry_editor()
	 *
	 * @param $post
	 * @return void
	 **/

	function entry_editor($post) {
		global $user_ID;
		$post_ID = $post->ID;
		$published = $post->post_status == 'publish';
		
		echo '<p>'
			. __('Create tags automatically?', 'autotag')
			. '</p>' . "\n";
		
		if ( !$published && !( $post_ID > 0 && get_post_meta($post_ID, '_did_autotag', true) ) ) {
			if ( $post_ID > 0 && get_post_meta($post_ID, '_autotag', true)
				|| $post_ID <= 0 && get_usermeta($user_ID, 'autotag')
				) {
				$default = 'publish';
			} else {
				$default = '';
			}
			
			echo '<p>'
				. '<label>'
				. '<input type="radio" name="autotag" value=""'
					. ( $default != 'publish'
						? ' checked="checked"'
						: '' )
					. ' />'
				. '&nbsp;' . __('No. Do not autotag this entry yet.', 'autotag')
				. '</label>' . '<br />' . "\n"
				. '<label>'
				. '<input type="radio" tabindex="4" name="autotag" value="now" />'
				. '&nbsp;' . __('Yes. Fetch new tags immediately.', 'autotag')
				. '</label>' . '<br />' . "\n"
				. '<label>'
				. '<input type="radio" tabindex="4" name="autotag" value="publish"'
					. ( $default == 'publish'
						? ' checked="checked"'
						: '' )
					. ' />'
				. '&nbsp;' . __('Yes. Fetch new tags upon publishing.', 'autotag')
				. '</label>'
				. '</p>' . "\n";
				
			echo '<input type="hidden" name="autotag_sticky" value="1" />';
		} else {
			echo '<p>'
				. '<label>'
				. '<input type="radio" tabindex="4" name="autotag" value="" checked="checked" />'
				. '&nbsp;'
				. ( ( $post_ID > 0 && get_post_meta($post_ID, '_did_autotag', true) )
					? __('No. I did this already.', 'autotag')
					: __('No. This entry has been published already.', 'autotag')
					)
				. '</label>' . '<br />' . "\n"
				. '<label>'
				. '<input type="radio" tabindex="4" name="autotag" value="now" />'
				. '&nbsp;' . __('Yes. Fetch new tags immediately.', 'autotag')
				. '</label>'
				. '</p>' . "\n";
		}
		
		echo '<p>'
			. '<label>'
			. '<input type="checkbox" tabindex="4" name="autotag_strip"'
				. ( get_usermeta($user_ID, 'autotag_strip')
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '&nbsp;'
			. __('Only keep terms that match existing tags.', 'autotag')
			. '</label>'
			. '</p>' . "\n";
		
		echo '<p>'
			. __('<b>Notice</b>: AutoTag will only work provided your server\'s IP address hasn\'t hit Yahoo\'s web services 5,000 times in the past 24h. If it stops working, give it a new try the next day.', 'autotag')
			. '</p>' . "\n";
	} # entry_editor()
	
	
	/**
	 * save_entry()
	 *
	 * @param int $post_id
	 * @return void
	 **/

	function save_entry($post_id) {
		if ( !$_POST || wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id) )
			return;
		
		$post = get_post($post_id);
		
		if ( !isset($_POST['autotag']) || !in_array($post->post_type, array('post', 'page')) )
			return;
		
		global $user_ID;
		
		$fetch_terms = false;
		$user_pref = false;
		
		$post = get_post($post_id);
		
		switch ( $_POST['autotag'] ) {
		case 'publish':
			if ( in_array($post->post_status, array('publish', 'future')) ) {
				$fetch_terms = true;
				$user_pref = true;

				delete_post_meta($post_id, '_autotag');
			} else {
				$user_pref = true;
				update_post_meta($post_id, '_autotag', '1');
			}
			break;
		
		case 'now':
			$fetch_terms = true;
		
		default:
			delete_post_meta($post_id, '_autotag');
			$user_pref = false;
			break;
		}
		
		if ( $fetch_terms ) {
			load_yterms();
			delete_post_meta($post_id, '_yterms');
			$terms = yterms::get($post);

			if ( $terms ) {
				foreach ( $terms as $key => $term )
					$terms[$key] = $term->name;
				
				if ( !empty($_POST['autotag_strip']) ) {
					foreach ( $terms as $key => $term ) {
						if ( !is_term($term, 'post_tag') )
							unset($terms[$key]);
					}
				}
				
				wp_set_post_tags($post_id, $terms, true);
			}
			
			update_post_meta($post_id, '_did_autotag', '1');
		}
		
		if ( !empty($_POST['autotag_sticky']) ) {
			if ( $user_pref ) {
				update_usermeta($user_ID, 'autotag', '1');
			} else {
				update_usermeta($user_ID, 'autotag', '0');
			}
			
			if ( !empty($_POST['autotag_strip']) )
				update_usermeta($user_ID, 'autotag_strip', '1');
			else
				update_usermeta($user_ID, 'autotag_strip', '0');
		}
	} # save_entry()
} # autotag_admin

add_action('save_post', array('autotag_admin', 'save_entry'));
?>