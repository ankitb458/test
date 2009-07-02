<?php
class fuzzy_widgets_admin
{
	#
	# init()
	#

	function init()
	{
		if ( !get_option('sem_links_db_changed') )
		{
			fuzzy_widgets_admin::install();
		}

		add_action('sidebar_admin_setup', array('fuzzy_widgets_admin', 'widget_setup'));
		add_action('sidebar_admin_page', array('fuzzy_widgets_admin', 'widget_page'));

		add_action('add_link', array('fuzzy_widgets_admin', 'link_added'));

		if ( get_option('fuzzy_widgets_cache') === false )
		{
			update_option('fuzzy_widgets_cache', array());
		}
	} # init()


	#
	# install()
	#

	function install()
	{
		global $wpdb;

		$wpdb->query("
			ALTER TABLE `$wpdb->links`
			ADD `link_added` DATETIME
				NOT NULL
				AFTER `link_name`
			");

		$wpdb->query("
			ALTER TABLE `$wpdb->links`
			ADD INDEX ( `link_added` )
			");

		update_option('sem_links_db_changed', 1);

		fuzzy_widgets_admin::link_added();
	} # install()


	#
	# link_added()
	#

	function link_added()
	{
		global $wpdb;

		$wpdb->query("
			UPDATE	$wpdb->links
			SET		link_added = now()
			WHERE	link_added = '0000-00-00 00:00:00'
			");
	} # link_added()


	#
	# widget_setup()
	#

	function widget_setup()
	{
		$options = $newoptions = get_option('fuzzy_widgets');

		if ( isset($_POST['fuzzy-widgets-number-submit']) )
		{
			$number = (int) $_POST['fuzzy-widgets-number'];
			if ( $number > 9 ) $number = 9;
			if ( $number < 1 ) $number = 1;
			$newoptions['number'] = $number;
		}

		if ( $options != $newoptions )
		{
			$options = $newoptions;
			update_option('fuzzy_widgets', $options);
			fuzzy_widgets::widgetize();
		}
	} # widget_setup()


	#
	# widget_page()
	#

	function widget_page()
	{
		$options = $newoptions = get_option('fuzzy_widgets');
?>
	<div class="wrap">
		<form method="post" action="">
			<h2><?php _e('Fuzzy Widgets', 'fuzzy-widgets'); ?></h2>
			<p style="line-height: 30px;"><?php _e('How many fuzzy widgets would you like?', 'fuzzy-widgets'); ?>
			<select id="fuzzy-widgets-number" name="fuzzy-widgets-number">
<?php
for ( $i = 1; $i < 10; ++$i )
{
	echo "<option value='$i' ".($options['number']==$i ? "selected='selected'" : '').">$i</option>";
}
?>
			</select>
			<span class="submit"><input type="submit" name="fuzzy-widgets-number-submit" id="fuzzy-widgets-number-submit" value="<?php echo attribute_escape(__('Save', 'fuzzy-widgets')); ?>" /></span></p>
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

		$options = $newoptions = get_option('fuzzy_widgets');

		$fuzziness_types = array(
			'days' => __('Days', 'fuzzy-widgets'),
			'days_ago' => __('Days Ago', 'fuzzy-widgets'),
			'items' => __('Items', 'fuzzy-widgets'),
			);

		if ( $_POST["fuzzy-widget-submit-$number"] )
		{
			$opt = array();
			$opt['title'] = strip_tags(stripslashes($_POST["fuzzy-widget-title-$number"]));
			$opt['type'] = $_POST["fuzzy-widget-type-$number"];
			$opt['amount'] = intval($_POST["fuzzy-widget-amount-$number"]);
			$opt['fuzziness'] = $_POST["fuzzy-widget-fuzziness-$number"];
			$opt['trim'] = intval($_POST["fuzzy-widget-trim-$number"]);
			$opt['exclude'] = $_POST["fuzzy-widget-exclude-$number"];
			$opt['date'] = isset($_POST["fuzzy-widget-date-$number"]);
			$opt['desc'] = isset($_POST["fuzzy-widget-desc-$number"]);

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

			if ( !in_array($opt['fuzziness'], array_keys($fuzziness_types)) )
			{
				$opt['fuzziness'] = 'days';
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
			update_option('fuzzy_widgets', $options);
		}

		if ( !is_array($options[$number]) )
		{
			$options[$number] = array(
				'title' => __('Recent Posts'),
				'type' => 'posts',
				'amount' => 5,
				'fuzziness' => 'days',
				'trim' => '',
				'exclude' => '',
				'date' => true,
				'desc' => false,
				);
		}

		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<div style="width: 120px; float: left; padding-top: 2px;">'
			. '<label for="fuzzy-widget-title-' . $number . '">'
			. __('Title', 'fuzzy-widgets') . ':'
			. '</label>'
			. '</div>'
			. '<div style="width: 330px; float: right;">'
			. '<input style="width: 320px;"'
			. ' id="fuzzy-widget-title-' . $number . '" name="fuzzy-widget-title-' . $number . '"'
			. ' type="text" value="' . attribute_escape($options[$number]['title']) . '"'
			. ' />'
			. '</div>'
			. '<div style="clear: both;"></div>'
			. '</div>';


		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<div style="width: 120px; float: left; padding-top: 2px;">'
			. '<label for="fuzzy-widget-type-' . $number . '">'
			. __('Recent', 'fuzzy-widgets') . ':'
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
				. ' id="fuzzy-widget-type-' . $number . '" name="fuzzy-widget-type-' . $number . '"'
				. '>';

		echo '<optgroup label="' . __('Posts', 'fuzzy-widgets') . '">'
			. '<option'
			. ' value="posts"'
			. ( $type == 'posts'
				? ' selected="selected"'
				: ''
				)
			. '>'
			. __('Posts', 'fuzzy-widgets') . ' / ' . __('All categories', 'fuzzy-widgets')
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
				. __('Posts', 'fuzzy-widgets') . ' / ' . htmlspecialchars($option->label)
				. '</option>';
		}

		echo '</optgroup>';

		echo '<optgroup label="' . __('Pages', 'fuzzy-widgets') . '">'
			. '<option'
			. ' value="pages"'
			. ( $type == 'pages'
				? ' selected="selected"'
				: ''
				)
			. '>'
			. __('Pages', 'fuzzy-widgets') . ' / ' . __('All Parents', 'fuzzy-widgets')
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
				. __('Pages', 'fuzzy-widgets') . ' / ' . htmlspecialchars($option->label)
				. '</option>';
		}

		echo '</optgroup>';

		echo '<optgroup label="' . __('Links', 'fuzzy-widgets') . '">'
			. '<option'
			. ' value="links"'
			. ( $type == 'links'
				? ' selected="selected"'
				: ''
				)
			. '>'
			. __('Links', 'fuzzy-widgets') . ' / ' . __('All Categories', 'fuzzy-widgets')
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
				. __('Links', 'fuzzy-widgets') . ' / ' . htmlspecialchars($option->label)
				. '</option>';
		}

		echo '</optgroup>';

		echo '<optgroup label="' . __('Comments', 'fuzzy-widgets') . '">'
			. '<option'
			. ' value="comments"'
			. ( $type == 'comments'
				? 'selected="selected"'
				: ''
				)
			. '>'
			. __('Comments', 'fuzzy-widgets')
			. '</option>';

		echo '</optgroup>';

		echo '<optgroup label="' . __('Updates', 'fuzzy-widgets') . '">'
			. '<option'
			. ' value="updates"'
			. ( $type == 'updates'
				? 'selected="selected"'
				: ''
				)
			. '>'
			. __('Updates', 'fuzzy-widgets')
			. '</option>';

		echo '</optgroup>';

		echo '<optgroup label="' . __('Old Posts', 'fuzzy-widgets') . '">'
			. '<option'
			. ' value="old_posts"'
			. ( $type == 'old_posts'
				? 'selected="selected"'
				: ''
				)
			. '>'
			. __('Around This Date In the Past', 'fuzzy-widgets')
			. '</option>';

		echo '</optgroup>';

		echo '</select>'
			. '</div>'
			. '<div style="clear: both;"></div>'
			. '</div>';


		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<div style="width: 120px; float: left; padding-top: 2px;">'
			. '<label for="fuzzy-widget-amount-' . $number . '">'
			. __('Fuzziness', 'fuzzy-widgets') . ':'
			. '</label>'
			. '</div>'
			. '<div style="width: 330px; float: right;">'
			. '<input style="width: 30px;"'
			. ' id="fuzzy-widget-amount-' . $number . '" name="fuzzy-widget-amount-' . $number . '"'
			. ' type="text" value="' . $options[$number]['amount'] . '"'
			. ' />'
			. '<select'
				. ' id="fuzzy-widget-fuzziness-' . $number . '" name="fuzzy-widget-fuzziness-' . $number . '"'
				. '>';

		foreach ( $fuzziness_types as $fuzziness => $label )
		{
			echo '<option value="' . $fuzziness . '"'
				. ( $fuzziness == $options[$number]['fuzziness']
					? ' selected="selected"'
					: ''
					)
				. ' >'
				. $label
				. '</option>';
		}

		echo '</select>'
			. '</div>'
			. '<div style="clear: both;"></div>'
			. '</div>';

		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<div style="width: 330px; float: right;">'
			. '<label for="fuzzy-widget-date-' . $number . '">'
			. '<input'
			. ' id="fuzzy-widget-date-' . $number . '" name="fuzzy-widget-date-' . $number . '"'
			. ' type="checkbox"'
			. ( $options[$number]['date']
				? ' checked="checked"'
				: ''
				)
			. ' />'
			. '&nbsp;' . __('Show date', 'fuzzy-widgets')
			. '</label>'
			. '</div>'
			. '<div style="clear: both;"></div>'
			. '</div>';

		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<div style="width: 330px; float: right;">'
			. '<label for="fuzzy-widget-desc-' . $number . '">'
			. '<input'
			. ' id="fuzzy-widget-desc-' . $number . '" name="fuzzy-widget-desc-' . $number . '"'
			. ' type="checkbox"'
			. ( $options[$number]['desc']
				? ' checked="checked"'
				: ''
				)
			. ' />'
			. '&nbsp;' . __('Show <b>link</b> description', 'fuzzy-widgets')
			. '</label>'
			. '</div>'
			. '<div style="clear: both;"></div>'
			. '</div>';

		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<div style="width: 120px; float: left; padding-top: 2px;">'
			. '<label for="fuzzy-widget-trim-' . $number . '">'
			. __('Max Length', 'fuzzy-widgets') . ':'
			. '</label>'
			. '</div>'
			. '<div style="width: 330px; float: right;">'
			. '<input style="width: 30px;"'
			. ' id="fuzzy-widget-trim-' . $number . '" name="fuzzy-widget-trim-' . $number . '"'
			. ' type="text" value="' . ( $options[$number]['trim'] ? $options[$number]['trim'] : '' ) . '"'
			. ' />'
			. ' ' . __('Characters', 'fuzzy-widgets')
			. '</div>'
			. '<div style="clear: both;"></div>'
			. '</div>';


		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<div style="width: 120px; float: left; padding-top: 2px;">'
			. '<label for="fuzzy-widget-exclude-' . $number . '">'
			. __('Exclude', 'fuzzy-widgets') . ':'
			. '</label>'
			. '</div>'
			. '<div style="width: 330px; float: right;">'
			. '<input style="width: 320px;"'
			. ' id="fuzzy-widget-exclude-' . $number . '" name="fuzzy-widget-exclude-' . $number . '"'
			. ' type="text" value="' . ( $options[$number]['exclude'] ? $options[$number]['exclude'] : '' ) . '"'
			. ' />'
			. '<br />'
			. __('(Enter a comma separated list of post or page IDs)', 'fuzzy-widgets')
			. '</div>'
			. '<div style="clear: both;"></div>'
			. '</div>';


		echo '<input type="hidden"'
			. ' id="fuzzy-widget-submit-' . $number . '" name="fuzzy-widget-submit-' . $number . '"'
			. ' value="1"'
			. ' />';
	} # widget_control()
} # fuzzy_widgets_admin

fuzzy_widgets_admin::init();
?>