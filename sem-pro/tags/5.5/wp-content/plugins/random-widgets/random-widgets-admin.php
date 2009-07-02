<?php
class random_widgets_admin
{
	#
	# init()
	#

	function init()
	{
		add_action('sidebar_admin_setup', array('random_widgets_admin', 'widget_setup'));
		add_action('sidebar_admin_page', array('random_widgets_admin', 'widget_page'));
	} # init()


	#
	# widget_setup()
	#

	function widget_setup()
	{
		$options = $newoptions = get_option('random_widgets');

		if ( isset($_POST['random-widgets-number-submit']) )
		{
			$number = (int) $_POST['random-widgets-number'];
			if ( $number > 9 ) $number = 9;
			if ( $number < 1 ) $number = 1;
			$newoptions['number'] = $number;
		}

		if ( $options != $newoptions )
		{
			$options = $newoptions;
			update_option('random_widgets', $options);
			random_widgets::widgetize();
		}
	} # widget_setup()


	#
	# widget_page()
	#

	function widget_page()
	{
		$options = $newoptions = get_option('random_widgets');
?>
	<div class="wrap">
		<form method="post" action="">
			<h2><?php _e('Random Widgets', 'random-widgets'); ?></h2>
			<p style="line-height: 30px;"><?php _e('How many random widgets would you like?', 'random-widgets'); ?>
			<select id="random-widgets-number" name="random-widgets-number">
<?php
for ( $i = 1; $i < 10; ++$i )
{
	echo "<option value='$i' ".($options['number']==$i ? "selected='selected'" : '').">$i</option>";
}
?>
			</select>
			<span class="submit"><input type="submit" name="random-widgets-number-submit" id="random-widgets-number-submit" value="<?php echo attribute_escape(__('Save', 'random-widgets')); ?>" /></span></p>
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

		if ( !isset($link_stubs) )
		{
			$link_stubs = (array) $wpdb->get_results("
				SELECT	terms.term_id as value,
						terms.name as label
				FROM	$wpdb->terms as terms
				INNER JOIN $wpdb->term_taxonomy as term_taxonomy
				ON		term_taxonomy.term_id = terms.term_id
				AND		term_taxonomy.taxonomy = 'link_category'
				WHERE	parent = 0
				ORDER BY terms.name
				");
		}

		$options = $newoptions = get_option('random_widgets');

		if ( $_POST["random-widget-submit-$number"] )
		{
			$opt = array();
			$opt['title'] = strip_tags(stripslashes($_POST["random-widget-title-$number"]));
			$opt['type'] = $_POST["random-widget-type-$number"];
			$opt['amount'] = intval($_POST["random-widget-amount-$number"]);
			$opt['trim'] = intval($_POST["random-widget-trim-$number"]);
			$opt['exclude'] = $_POST["random-widget-exclude-$number"];
			$opt['desc'] = isset($_POST["random-widget-desc-$number"]);

			if ( !preg_match("/^([a-z_]+)(?:-(\d+))?$/", $opt['type'], $match) )
			{
				$opt['type'] = 'posts';
				$opt['filter'] = false;
			}
			else
			{
				$opt['type'] = $match[1];
				$opt['filter'] = isset($match[2]) ? $match[2] : false;

				if ( $opt['type'] == 'links' )
				{
					$opt['exclude'] = '';
				}
				else
				{
					$opt['desc'] = false;
				}
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

		if ( !is_array($options[$number]) )
		{
			$options[$number] = array(
				'title' => __('Random Posts'),
				'type' => 'posts',
				'amount' => 5,
				'trim' => '',
				'exclude' => '',
				'desc' => false,
				);
		}

		if ( $options != $newoptions )
		{
			$options = $newoptions;
			update_option('random_widgets', $options);
		}


		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<div style="width: 120px; float: left; padding-top: 2px;">'
			. '<label for="random-widget-title-' . $number . '">'
			. __('Title', 'random-widgets') . ':'
			. '</label>'
			. '</div>'
			. '<div style="width: 330px; float: right;">'
			. '<input style="width: 320px;"'
			. ' id="random-widget-title-' . $number . '" name="random-widget-title-' . $number . '"'
			. ' type="text" value="' . attribute_escape($options[$number]['title']) . '"'
			. ' />'
			. '</div>'
			. '<div style="clear: both;"></div>'
			. '</div>';


		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<div style="width: 120px; float: left; padding-top: 2px;">'
			. '<label for="random-widget-type-' . $number . '">'
			. __('Recent', 'random-widgets') . ':'
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
				. ' id="random-widget-type-' . $number . '" name="random-widget-type-' . $number . '"'
				. '>';

		echo '<optgroup label="' . __('Posts', 'random-widgets') . '">'
			. '<option'
			. ' value="posts"'
			. ( $type == 'posts'
				? ' selected="selected"'
				: ''
				)
			. '>'
			. __('Posts', 'random-widgets') . ' / ' . __('All categories', 'random-widgets')
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
				. __('Posts', 'random-widgets') . ' / ' . htmlspecialchars($option->label)
				. '</option>';
		}

		echo '</optgroup>';

		echo '<optgroup label="' . __('Pages', 'random-widgets') . '">'
			. '<option'
			. ' value="pages"'
			. ( $type == 'pages'
				? ' selected="selected"'
				: ''
				)
			. '>'
			. __('Pages', 'random-widgets') . ' / ' . __('All Parents', 'random-widgets')
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
				. __('Pages', 'random-widgets') . ' / ' . htmlspecialchars($option->label)
				. '</option>';
		}

		echo '</optgroup>';

		echo '<optgroup label="' . __('Links', 'random-widgets') . '">'
			. '<option'
			. ' value="links"'
			. ( $type == 'links'
				? ' selected="selected"'
				: ''
				)
			. '>'
			. __('Links', 'random-widgets') . ' / ' . __('All Categories', 'random-widgets')
			. '</option>';

		foreach ( $link_stubs as $option )
		{
			echo '<option'
				. ' value="links-' . $option->value . '"'
				. ( $type == ( 'links-' . $option->value )
					? ' selected="selected"'
					: ''
					)
				. '>'
				. __('Links', 'random-widgets') . ' / ' . htmlspecialchars($option->label)
				. '</option>';
		}

		echo '</optgroup>';

		echo '<optgroup label="' . __('Comments', 'random-widgets') . '">'
			. '<option'
			. ' value="comments"'
			. ( $type == 'comments'
				? 'selected="selected"'
				: ''
				)
			. '>'
			. __('Comments', 'random-widgets')
			. '</option>';

		echo '</optgroup>';

		echo '<optgroup label="' . __('Updates', 'random-widgets') . '">'
			. '<option'
			. ' value="updates"'
			. ( $type == 'updates'
				? 'selected="selected"'
				: ''
				)
			. '>'
			. __('Updates', 'random-widgets')
			. '</option>';

		echo '</optgroup>';

		echo '</select>'
			. '</div>'
			. '<div style="clear: both;"></div>'
			. '</div>';


		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<div style="width: 120px; float: left; padding-top: 2px;">'
			. '<label for="random-widget-amount-' . $number . '">'
			. __('Quantity', 'random-widgets') . ':'
			. '</label>'
			. '</div>'
			. '<div style="width: 330px; float: right;">'
			. '<input style="width: 30px;"'
			. ' id="random-widget-amount-' . $number . '" name="random-widget-amount-' . $number . '"'
			. ' type="text" value="' . $options[$number]['amount'] . '"'
			. ' />'
			. ' ' . __('items', 'random-widgets')
			. '</div>'
			. '<div style="clear: both;"></div>'
			. '</div>';

		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<div style="width: 330px; float: right;">'
			. '<label for="random-widget-desc-' . $number . '">'
			. '<input'
			. ' id="random-widget-desc-' . $number . '" name="random-widget-desc-' . $number . '"'
			. ' type="checkbox"'
			. ( $options[$number]['desc']
				? ' checked="checked"'
				: ''
				)
			. ' />'
			. '&nbsp;' . __('Show link description', 'random-widgets')
			. '</label>'
			. '</div>'
			. '<div style="clear: both;"></div>'
			. '</div>';

		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<div style="width: 120px; float: left; padding-top: 2px;">'
			. '<label for="random-widget-trim-' . $number . '">'
			. __('Max Length', 'random-widgets') . ':'
			. '</label>'
			. '</div>'
			. '<div style="width: 330px; float: right;">'
			. '<input style="width: 30px;"'
			. ' id="random-widget-trim-' . $number . '" name="random-widget-trim-' . $number . '"'
			. ' type="text" value="' . ( $options[$number]['trim'] ? $options[$number]['trim'] : '' ) . '"'
			. ' />'
			. ' ' . __('Characters', 'random-widgets')
			. '</div>'
			. '<div style="clear: both;"></div>'
			. '</div>';


		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<div style="width: 120px; float: left; padding-top: 2px;">'
			. '<label for="random-widget-exclude-' . $number . '">'
			. __('Exclude', 'random-widgets') . ':'
			. '</label>'
			. '</div>'
			. '<div style="width: 330px; float: right;">'
			. '<input style="width: 320px;"'
			. ' id="random-widget-exclude-' . $number . '" name="random-widget-exclude-' . $number . '"'
			. ' type="text" value="' . ( $options[$number]['exclude'] ? $options[$number]['exclude'] : '' ) . '"'
			. ' />'
			. '<br />'
			. __('(Enter a comma separated list of post or page IDs)', 'random-widgets')
			. '</div>'
			. '<div style="clear: both;"></div>'
			. '</div>';


		echo '<input type="hidden"'
			. ' id="random-widget-submit-' . $number . '" name="random-widget-submit-' . $number . '"'
			. ' value="1"'
			. ' />';
	} # widget_control()
} # random_widgets_admin

random_widgets_admin::init();
?>