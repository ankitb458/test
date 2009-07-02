<?php
if ( !class_exists('wiz_upgrade_done') ) :
class wiz_upgrade_done
{
	#
	# show_step()
	#

	function show_step()
	{
		echo '<h3>'
			. 'Download &rarr; Backup &rarr; Prepare &rarr; Upgrade &rarr; Clean Up &rarr; <u>Done</u>'
			. '</h3>';

		echo '<p>'
			. 'Congratulations! Your system has been upgraded successfully.'
			. '</p>';
	} # show_step()
} # wiz_upgrade_done
endif;
?>