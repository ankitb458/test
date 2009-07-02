<?php
if ( !class_exists('wiz_clone_done') ) :

class wiz_clone_done
{
	#
	# function show_step()
	#

	function show_step()
	{
		echo '<h3>'
			. 'Import &rarr; <u>Done</u>'
			. '</h3>';

		echo '<p>' . __('Your site has been successfully cloned. A few things you may now want to look into:') . '</p>';

		echo '<ul>';

		echo '<li>' . __('Header and Nav Menu options, under Presentation') . '</li>';
		echo '<li>' . __('Newsletter settings, under Options / Newsletter') . '</li>';
		echo '<li>' . __('Google Analytics, under Options / Google Analytics') . '</li>';
		echo '<li>' . __('Feeburner URL, under Options / Feedburner') . '</li>';
		echo '<li>' . __('Itunes settings, under Options / Mediacaster') . '</li>';
		echo '<li>' . __('WP-Cache options, under Options / WP-Cache') . '</li>';

		echo '</ul>';
	} # show_step()
} # wiz_clone_done

endif;
?>