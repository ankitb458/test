<?php
#
# sem_external_links_update_options()
#

function sem_external_links_update_options()
{
	#echo '<pre>';
	#var_dump($_POST['sem_external_links']);
	#echo '</pre>';

	$options = $_POST['sem_external_links'];
	$options['globals'] = isset($options['globals']);
	$options['add_css'] = isset($options['add_css']);
	$options['add_target'] = isset($options['add_target']);

	update_option('sem_external_links_params', $options);
} # end sem_external_links_update_options()


#
# sem_external_links_add_admin()
#

function sem_external_links_add_admin()
{
	add_options_page(
			__('External&nbsp;Links'),
			__('External&nbsp;Links'),
			7,
			str_replace("\\", "/", __FILE__),
			'sem_external_links_admin'
			);
} # end sem_external_links_add_admin()

add_action('admin_menu', 'sem_external_links_add_admin');


#
# sem_external_links_admin()
#

function sem_external_links_admin()
{
	# Acknowledge update

	if ( isset($_POST['update_sem_external_links_options'])
		&& $_POST['update_sem_external_links_options']
		)
	{
		sem_external_links_update_options();

		echo "<div class=\"updated\">\n"
			. "<p>"
				. "<strong>"
				. __('Options saved.')
				. "</strong>"
			. "</p>\n"
			. "</div>\n";
	}

	$options = get_option('sem_external_links_params');

	# show controls

	echo "<div class=\"wrap\">\n"
		. "<h2>" . __('External Links Options') . "</h2>\n"
		. "<form method=\"post\" action=\"\">\n";

	echo '<input type="hidden" name="update_sem_external_links_options" value="1">';

	echo '<p><label for="sem_external_links[global]">'
		. '<input type="checkbox"'
			. ' name="sem_external_links[global]" id="sem_external_links[global]"'
			. ( $options['global'] ? ' checked="checked"' : '' )
			. ' />'
		. '&nbsp;'
		. __('Process all outgoing links, rather than only those within your entries\' content.')
		. '</label>'
		. '</p>';

	echo '<p><label for="sem_external_links[add_css]">'
		. '<input type="checkbox"'
			. ' name="sem_external_links[add_css]" id="sem_external_links[add_css]"'
			. ( $options['add_css'] ? ' checked="checked"' : '' )
			. ' />'
		. '&nbsp;'
		. __('Add an external link icon to outgoing links. You can use a class="noicon" attribute on links to override this.')
		. '</label>'
		. '</p>';

	echo '<p><label for="sem_external_links[add_target]">'
		. '<input type="checkbox"'
			. ' name="sem_external_links[add_target]" id="sem_external_links[add_target]"'
			. ( $options['add_target'] ? ' checked="checked"' : '' )
			. ' />'
		. '&nbsp;'
		. __('Open outgoing links in new windows. Note that it is poor usability practice.')
		. '</label>'
		. '</p>';

	echo '<p class="submit">'
		. '<input type="submit"'
			. ' value="' . __('Update Options') . '"'
			. ' />'
		. '</p>';

	echo '</form>'
		. '</div>';
} # end sem_external_links_admin()
?>