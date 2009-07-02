<?php
/*
Plugin Name: AutoTag
Plugin URI: http://www.semiologic.com/software/publishing/autotag/
Description: Leverages Yahoo!'s term extraction web service to automatically tag your posts.
Version: 1.0
Author: Denis de Bernardy
Author URI: http://www.semiologic.com
Update Service: http://version.mesoconcepts.com/wordpress
Update Tag: autotag
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/
**/


include_once dirname(__FILE__). '/extract-terms.php';


class autotag
{
	#
	# init()
	#

	function init()
	{
		add_action('edit_form_advanced', array('autotag', 'entry_editor'));

		add_action('save_post', array('autotag', 'save_entry'));
	} # init()


	#
	# entry_editor()
	#

	function entry_editor()
	{
		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<label for="sem_autotag">'
			. '<input'
			. ' id="sem_autotag" name="sem_autotag"'
			. ' type="checkbox"'
			. ' />'
			. '&nbsp;' . __('Create tags automatically (Note: this will not work if your server\'s IP hit Yahoo\'s web services more than 5,000 times in the past 24h).')
			. '</label>'
			. '</div>';
	} # entry_editor()


	#
	# save_entry()
	#

	function save_entry($post_ID)
	{
		if ( isset($_POST['sem_autotag']) )
		{
			$post = get_post($post_ID);
			$terms = get_the_post_terms($post);

			if ( $terms )
			{
				$tags = '';

				foreach ( $terms as $term )
				{
					$tags .= ( $tags ? ', ' : '' ) . $term;
				}

				wp_set_post_tags($post_ID, $tags, true);
			}
		}
	} # save_entry()
} # end autotag

autotag::init();
?>