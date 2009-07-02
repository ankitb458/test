<?php
function sem_widget_controls_update($widget_id)
{
	$contexts = sem_widgets_get_contexts();

	global $sem_widget_contexts;

	foreach ( array_keys($contexts) as $context )
	{
		$sem_widget_contexts[$widget_id][$context] = isset($_POST['widget_context'][$widget_id][$context]);
	}

	update_option('sem_widget_contexts', $sem_widget_contexts);
} # sem_widget_control_update()




function sem_widget_text_control($number) {
	$options = $newoptions = get_option('widget_text');
	if ( !is_array($options) )
		$options = $newoptions = array();
	if ( $_POST["text-submit-$number"] ) {
		$newoptions[$number]['title'] = strip_tags(stripslashes($_POST["text-title-$number"]));
		$newoptions[$number]['text'] = stripslashes($_POST["text-text-$number"]);
		$newoptions[$number]['filter'] = isset($_POST["text-filter-$number"]);
		if ( !current_user_can('unfiltered_html') )
			$newoptions[$number]['text'] = stripslashes(wp_filter_post_kses($newoptions[$number]['text']));
	}
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_option('widget_text', $options);
	}
	$title = attribute_escape($options[$number]['title']);
	$text = format_to_edit($options[$number]['text']);
	$filter = (bool) $options[$number]['filter'];
?>
			<input style="width: 450px;" id="text-title-<?php echo $number; ?>" name="text-title-<?php echo $number; ?>" type="text" value="<?php echo $title; ?>" />
			<textarea style="width: 450px; height: 280px;" id="text-text-<?php echo $number; ?>" name="text-text-<?php echo $number; ?>"><?php echo $text; ?></textarea>
			<div>
			<label for="text-filter-<?php echo "$number"; ?>"><input type="checkbox" id="text-filter-<?php echo "$number"; ?>" name="text-filter-<?php echo "$number"; ?>" <?php echo $filter ? "checked" : ""; ?> />&nbsp;<?php _e('Automatically insert paragraphs'); ?></label>
			<input type="hidden" id="text-submit-<?php echo "$number"; ?>" name="text-submit-<?php echo "$number"; ?>" value="1" />
			</div>
<?php
} # sem_widget_text_control()


function sem_widget_pages_control() {
	$options = $newoptions = get_option('widget_pages');
	if ( $_POST['pages-submit'] ) {
		$newoptions['title'] = strip_tags(stripslashes($_POST['pages-title']));

		if ( in_array( $sortby, array( 'post_title', 'menu_order', 'ID' ) ) ) {
			$newoptions['sortby'] = $sortby;
		} else {
			$newoptions['sortby'] = 'menu_order';
		}

		$newoptions['exclude'] = strip_tags( stripslashes( $_POST['pages-exclude'] ) );
	}
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_option('widget_pages', $options);
	}
	$title = attribute_escape($options['title']);

	$exclude = $options['exclude'];
	if ( is_array($exclude) )
	{
		$options['exclude'] = '';

		foreach ( $exclude as $id )
		{
			$options['exclude'] .= ( $options['exclude'] ? ',' : '' ) . $id;
		}

		$exclude = $options['exclude'];

		update_option('widget_pages', $options);
	}

	$exclude = attribute_escape( $options['exclude'] );
?>
			<p><label for="pages-title"><?php _e('Title:'); ?> <input style="width: 250px;" id="pages-title" name="pages-title" type="text" value="<?php echo $title; ?>" /></label></p>
			<p><label for="pages-exclude"><?php _e( 'Exclude:' ); ?> <input type="text" value="<?php echo $exclude; ?>" name="pages-exclude" id="pages-exclude" style="width: 180px;" /></label><br />
			<small><?php _e( 'Page IDs, separated by commas.' ); ?></small></p>
			<input type="hidden" id="pages-submit" name="pages-submit" value="1" />
<?php
} # sem_widget_pages_control()



function sem_widget_links_control() {

} # sem_widget_links_control()

wp_register_widget_control('links', __('Links'), 'sem_widget_links_control', $dims100);
?>