<?php
class related_widgets_admin
{
	#
	# init()
	#

	function init()
	{
		add_action('sidebar_admin_setup', array('related_widgets_admin', 'widget_setup'));
		add_action('sidebar_admin_page', array('related_widgets_admin', 'widget_page'));

		if ( get_option('related_widgets_cache') === false )
		{
			update_option('related_widgets_cache', array());
		}
	} # init()


	#
	# widget_setup()
	#

	function widget_setup()
	{
		$options = $newoptions = get_option('related_widgets');

		if ( isset($_POST['related-widgets-number-submit']) )
		{
			$number = (int) $_POST['related-widgets-number'];
			if ( $number > 9 ) $number = 9;
			if ( $number < 1 ) $number = 1;
			$newoptions['number'] = $number;
		}

		if ( $options != $newoptions )
		{
			$options = $newoptions;
			update_option('related_widgets', $options);
			related_widgets::widgetize();
		}
	} # widget_setup()


	#
	# widget_page()
	#

	function widget_page()
	{
		$options = $newoptions = get_option('related_widgets');
?>
	<div class="wrap">
		<form method="post" action="">
			<h2><?php _e('Related Widgets', 'related-widgets'); ?></h2>
			<p style="line-height: 30px;"><?php _e('How many related widgets would you like?', 'related-widgets'); ?>
			<select id="related-widgets-number" name="related-widgets-number">
<?php
for ( $i = 1; $i < 10; ++$i )
{
	echo "<option value='$i' ".($options['number']==$i ? "selected='selected'" : '').">$i</option>";
}
?>
			</select>
			<span class="submit"><input type="submit" name="related-widgets-number-submit" id="related-widgets-number-submit" value="<?php echo attribute_escape(__('Save', 'related-widgets')); ?>" /></span></p>
		</form>
	</div>
<?php
	} # widget_page()


	#
	# widget_control()
	#

	function widget_control($number = 1)
	{
		global $wpdb;
		global $post_stubs;
		global $page_stubs;
		global $link_stubs;

		if ( !isset($post_stubs) )
		{
			$post_stubs = (array) $wpdb->get_results("
				SELECT	terms.term_id as value,
						terms.name as label
				FROM	$wpdb->terms as terms
				INNER JOIN $wpdb->term_taxonomy as term_taxonomy
				ON		term_taxonomy.term_id = terms.term_id
				AND		term_taxonomy.taxonomy = 'category'
				WHERE	parent = 0
				ORDER BY terms.name
				");
		}

		if ( !isset($page_stubs) )
		{
			$page_stubs = (array) $wpdb->get_results("
				SELECT	posts.ID as value,
						posts.post_title as label
				FROM	$wpdb->posts as posts
				WHERE	post_parent = 0
				AND		post_type = 'page'
				AND		post_status = 'publish'
				ORDER BY posts.post_title
				");
		}

		$options = $newoptions = get_option('related_widgets');

		if ( $_POST["related-widget-submit-$number"] )
		{
			$opt = array();
			$opt['title'] = strip_tags(stripslashes($_POST["related-widget-title-$number"]));
			$opt['type'] = $_POST["related-widget-type-$number"];
			$opt['amount'] = intval($_POST["related-widget-amount-$number"]);
			$opt['trim'] = intval($_POST["related-widget-trim-$number"]);
			$opt['exclude'] = $_POST["related-widget-exclude-$number"];
			$opt['score'] = isset($_POST["related-widget-score-$number"]);

			if ( !preg_match("/^([a-z_]+)(?:-(\d+))?$/", $opt['type'], $match) )
			{
				$opt['type'] = 'posts';
				$opt['filter'] = false;
			}
			else
			{
				$opt['type'] = $match[1];
				$opt['filter'] = isset($match[2]) ? $match[2] : false;
			}

			if ( $opt['amount'] <= 0 )
			{
				$opt['amount'] = 5;
			}

			if ( $opt['trim'] <= 0 )
			{
				$opt['trim'] = '';
			}

			preg_match_all("/\d+/", $opt['exclude'], $exclude, PREG_PATTERN_ORDER);

			$exclude = end($exclude);

			$opt['exclude'] = '';

			foreach ( $exclude as $id )
			{
				$opt['exclude'] .= ( $opt['exclude'] ? ', ' : '' ) . $id;
			}

			$newoptions[$number] = $opt;
		}

		if ( $options != $newoptions )
		{
			$options = $newoptions;
			update_option('related_widgets', $options);
		}

		if ( !is_array($options[$number]) )
		{
			$options[$number] = array(
				'title' => __('Related Posts'),
				'type' => 'posts',
				'amount' => 5,
				'trim' => '',
				'exclude' => '',
				'score' => false,
				);
		}

		echo '<p>'
			. __('To make use of this plugin, add tags to your posts and pages.')
			. '</p>';

		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<div style="width: 120px; float: left; padding-top: 2px;">'
			. '<label for="related-widget-title-' . $number . '">'
			. __('Title', 'related-widgets') . ':'
			. '</label>'
			. '</div>'
			. '<div style="width: 330px; float: right;">'
			. '<input style="width: 320px;"'
			. ' id="related-widget-title-' . $number . '" name="related-widget-title-' . $number . '"'
			. ' type="text" value="' . attribute_escape($options[$number]['title']) . '"'
			. ' />'
			. '</div>'
			. '<div style="clear: both;"></div>'
			. '</div>';


		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<div style="width: 120px; float: left; padding-top: 2px;">'
			. '<label for="related-widget-type-' . $number . '">'
			. __('Recent', 'related-widgets') . ':'
			. '</label>'
			. '</div>'
			. '<div style="width: 330px; float: right;">';

		$type = $options[$number]['type']
			. ( $options[$number]['filter']
				? ( '-' . $options[$number]['filter'] )
				: ''
				);

		echo '<select'
				. ' style="width: 320px;"'
				. ' id="related-widget-type-' . $number . '" name="related-widget-type-' . $number . '"'
				. '>';

		echo '<optgroup label="' . __('Posts', 'related-widgets') . '">'
			. '<option'
			. ' value="posts"'
			. ( $type == 'posts'
				? ' selected="selected"'
				: ''
				)
			. '>'
			. __('Posts', 'related-widgets') . ' / ' . __('All categories', 'related-widgets')
			. '</option>';

		foreach ( $post_stubs as $option )
		{
			echo '<option'
				. ' value="posts-' . $option->value . '"'
				. ( $type == ( 'posts-' . $option->value )
					? ' selected="selected"'
					: ''
					)
				. '>'
				. __('Posts', 'related-widgets') . ' / ' . htmlspecialchars($option->label)
				. '</option>';
		}

		echo '</optgroup>';

		echo '<optgroup label="' . __('Pages', 'related-widgets') . '">'
			. '<option'
			. ' value="pages"'
			. ( $type == 'pages'
				? ' selected="selected"'
				: ''
				)
			. '>'
			. __('Pages', 'related-widgets') . ' / ' . __('All Parents', 'related-widgets')
			. '</option>';

		foreach ( $page_stubs as $option )
		{
			echo '<option'
				. ' value="pages-' . $option->value . '"'
				. ( $type == ( 'pages-' . $option->value )
					? ' selected="selected"'
					: ''
					)
				. '>'
				. __('Pages', 'related-widgets') . ' / ' . htmlspecialchars($option->label)
				. '</option>';
		}

		echo '</optgroup>';

/*
		echo '<optgroup label="' . __('Tags', 'related-widgets') . '">'
			. '<option'
			. ' value="tags"'
			. ( $type == 'tags'
				? ' selected="selected"'
				: ''
				)
			. '>'
			. __('Tags', 'related-widgets')
			. '</option>';

		echo '</optgroup>';
*/

		echo '</select>'
			. '</div>'
			. '<div style="clear: both;"></div>'
			. '</div>';

		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<div style="width: 330px; float: right;">'
			. '<label for="related-widget-score-' . $number . '">'
			. '<input'
			. ' id="related-widget-score-' . $number . '" name="related-widget-score-' . $number . '"'
			. ' type="checkbox"'
			. ( $options[$number]['score']
				? ' checked="checked"'
				: ''
				)
			. ' />'
			. '&nbsp;' . __('Show relevance score', 'related-widgets')
			. '</label>'
			. '</div>'
			. '<div style="clear: both;"></div>'
			. '</div>';

		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<div style="width: 120px; float: left; padding-top: 2px;">'
			. '<label for="related-widget-trim-' . $number . '">'
			. __('Max Length', 'related-widgets') . ':'
			. '</label>'
			. '</div>'
			. '<div style="width: 330px; float: right;">'
			. '<input style="width: 30px;"'
			. ' id="related-widget-trim-' . $number . '" name="related-widget-trim-' . $number . '"'
			. ' type="text" value="' . ( $options[$number]['trim'] ? $options[$number]['trim'] : '' ) . '"'
			. ' />'
			. ' ' . __('Characters', 'related-widgets')
			. '</div>'
			. '<div style="clear: both;"></div>'
			. '</div>';


		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<div style="width: 120px; float: left; padding-top: 2px;">'
			. '<label for="related-widget-exclude-' . $number . '">'
			. __('Exclude', 'related-widgets') . ':'
			. '</label>'
			. '</div>'
			. '<div style="width: 330px; float: right;">'
			. '<input style="width: 320px;"'
			. ' id="related-widget-exclude-' . $number . '" name="related-widget-exclude-' . $number . '"'
			. ' type="text" value="' . ( $options[$number]['exclude'] ? $options[$number]['exclude'] : '' ) . '"'
			. ' />'
			. '<br />'
			. __('(Enter a comma separated list of post or page IDs)', 'related-widgets')
			. '</div>'
			. '<div style="clear: both;"></div>'
			. '</div>';


		echo '<input type="hidden"'
			. ' id="related-widget-submit-' . $number . '" name="related-widget-submit-' . $number . '"'
			. ' value="1"'
			. ' />';
	} # widget_control()
} # related_widgets_admin

related_widgets_admin::init();
?>