<?php
#
# add_bookmark_me_admin()
#

function add_bookmark_me_admin()
{
	add_options_page(
			__('Bookmark&nbsp;Me'),
			__('Bookmark&nbsp;Me'),
			7,
			str_replace("\\", "/", __FILE__),
			'display_bookmark_me_admin'
			);
} # end add_bookmark_me_admin()

add_action('admin_menu', 'add_bookmark_me_admin');


#
# update_bookmark_me_options()
#

function update_bookmark_me_options()
{
	#echo '<pre>';
	#var_dump($_POST);
	#echo '</pre>';

	$options = array(
		'services' => $_POST['sites'],
		'show_names' => isset($_POST['show_names'])
		);

	update_option('sem_bookmark_me_params', $options);
} # end update_bookmark_me_options()


#
# display_bookmark_me_admin()
#

function display_bookmark_me_admin()
{
?>
<form method="post" action="">
<?php
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
?>
<div class="wrap">
	<h2><?php echo __('Bookmark Me options'); ?></h2>
<?php
	if ( $_POST['update_bookmark_me_options'] )
	{
		update_bookmark_me_options();
	}

?>
<input type="hidden" name="update_bookmark_me_options" value="1" />
<?php
	$title = urlencode(the_title(null, null, false));
	$site_name = urlencode(get_bloginfo('sitename'));

	$options = get_settings('sem_bookmark_me_params');

	if ( !$options )
	{
		$options = array(
			'services' => $GLOBALS['bookmark_services'],
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
		. __('Display Dervice Names')
		. '</label>'
		. '</p>';

	echo '<ul>';

	foreach ( $GLOBALS['bookmark_sites'] as $site_id => $site_info )
	{
		echo '<li>'
			. '<label for="sites[' . $site_id . ']">'
			. '<span'
			. ' style="'
				. 'padding-left: 20px;'
				. ' background: url('
					. trailingslashit(get_bloginfo('siteurl'))
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
?>
	<p class="submit">
	<input type="submit"
		value="<?php echo __('Update Options'); ?>"
		 />
	</p>
</div>
<?php
} # end display_bookmark_me_admin()
?>