<?php

class sem_entry_admin
{
	#
	# widget_control()
	#

	function widget_control($area)
	{
		do_action('entry_' . $area . '_control');
	} # widget_control()


	#
	# header()
	#

	function header()
	{
		global $sem_options;
		global $sem_captions;

		if ( $_POST['update_sem_entry']['header'] )
		{
			$new_options = $sem_options;

			$new_options['show_post_date'] = isset($_POST['sem_entry']['show_post_date']);

			if ( $new_options != $sem_options )
			{
				$sem_options = $new_options;

				update_option('sem6_options', $sem_options);
			}
		}

		echo '<input type="hidden" name="update_sem_entry[header]" value="1" />';

		echo '<h3>'
			. __('Config')
			. '</h3>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. '<input type="checkbox"'
				. ' name="sem_entry[show_post_date]"'
				. ( $sem_options['show_post_date']
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. ' '
			. __('Show Post Date')
			. '</label>'
			. '</div>';
	} # header()


	#
	# content()
	#

	function content()
	{
		global $sem_options;
		global $sem_captions;

		if ( $_POST['update_sem_entry']['content'] )
		{
			$new_options = $sem_options;
			$new_captions = $sem_captions;

			$new_options['show_excerpts'] = isset($_POST['sem_entry']['show_excerpts']);

			$new_captions['more_link'] = strip_tags(stripslashes($_POST['sem_entry']['more_link']));
			$new_captions['paginate'] = strip_tags(stripslashes($_POST['sem_entry']['paginate']));

			if ( $new_options != $sem_options )
			{
				$sem_options = $new_options;

				update_option('sem6_options', $sem_options);
			}

			if ( $new_captions != $sem_captions )
			{
				$sem_captions = $new_captions;

				update_option('sem6_captions', $sem_captions);
			}
		}

		echo '<input type="hidden" name="update_sem_entry[content]" value="1" />';

		echo '<h3>'
			. __('Config')
			. '</h3>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. '<input type="checkbox"'
				. ' name="sem_entry[show_excerpts]"'
				. ( $sem_options['show_excerpts']
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. ' '
			. __('Show excerpts on front page')
			. '</label>'
			. '</div>';
		
		echo '<h3>'
			. __('Captions')
			. '</h3>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. __('More Link, e.g. More on %title%')
			. '<br />'
			. '<input type="text" style="width: 95%"'
				. ' name="sem_entry[more_link]"'
				. ' value="' . attribute_escape($sem_captions['more_link']) . '"'
				. ' />'
			. '</label>'
			. '</div>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. __('Paginate, e.g. Pages')
			. '<br />'
			. '<input type="text" style="width: 95%"'
				. ' name="sem_entry[paginate]"'
				. ' value="' . attribute_escape($sem_captions['paginate']) . '"'
				. ' />'
			. '</label>'
			. '</div>';
	} # content()


	#
	# tags()
	#

	function tags()
	{
		global $sem_captions;

		if ( $_POST['update_sem_entry']['tags'] )
		{
			$new_captions = $sem_captions;

			$new_captions['tags'] = strip_tags(stripslashes($_POST['sem_entry']['tags']));
			$new_captions['tags_title'] = strip_tags(stripslashes($_POST['sem_entry']['tags_title']));

			if ( $new_captions != $sem_captions )
			{
				$sem_captions = $new_captions;

				update_option('sem6_captions', $sem_captions);
			}
		}

		echo '<input type="hidden" name="update_sem_entry[tags]" value="1" />';

		echo '<h3>'
			. __('Captions')
			. '</h3>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. __('Tags, e.g. Tags: %tags%')
			. '<br />'
			. '<input type="text" style="width: 95%"'
				. ' name="sem_entry[tags]"'
				. ' value="' . attribute_escape($sem_captions['tags']) . '"'
				. ' />'
			. '</label>'
			. '</div>';

		echo '<h3>'
			. __('Widget Title')
			. '</h3>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. __('Widget Title (for use in sidebars), e.g. Tags')
			. '<br />'
			. '<input type="text" style="width: 95%"'
				. ' name="sem_entry[tags_title]"'
				. ' value="' . attribute_escape($sem_captions['tags_title']) . '"'
				. ' />'
			. '</label>'
			. '</div>';
	} # tags()


	#
	# categories()
	#

	function categories()
	{
		global $sem_captions;

		if ( $_POST['update_sem_entry']['categories'] )
		{
			$new_captions = $sem_captions;

			$new_captions['filed_under'] = strip_tags(stripslashes($_POST['sem_entry']['filed_under']));
			$new_captions['cats_title'] = strip_tags(stripslashes($_POST['sem_entry']['cats_title']));

			if ( $new_captions != $sem_captions )
			{
				$sem_captions = $new_captions;

				update_option('sem6_captions', $sem_captions);
			}
		}

		echo '<input type="hidden" name="update_sem_entry[categories]" value="1" />';

		echo '<h3>'
			. __('Captions')
			. '</h3>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. __('Categories, e.g. Filed under %categories% by %author%')
			. '<br />'
			. '<input type="text" style="width: 95%"'
				. ' name="sem_entry[filed_under]"'
				. ' value="' . attribute_escape($sem_captions['filed_under']) . '"'
				. ' />'
			. '</label>'
			. '</div>';

		echo '<h3>'
			. __('Widget Title')
			. '</h3>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. __('Widget Title (for use in sidebars), e.g. Categories')
			. '<br />'
			. '<input type="text" style="width: 95%"'
				. ' name="sem_entry[cats_title]"'
				. ' value="' . attribute_escape($sem_captions['cats_title']) . '"'
				. ' />'
			. '</label>'
			. '</div>';

		echo '<div style="margin-bottom: .2em;">'
			. '<br />'
			. __('Note: This widget gets ignored by static pages, which lack categories')
			. '</div>';
	} # categories()


	#
	# actions()
	#

	function actions()
	{
		global $sem_options;
		global $sem_captions;

		if ( $_POST['update_sem_entry']['actions'] )
		{
			$new_options = $sem_options;
			$new_captions = $sem_captions;

			$new_options['show_permalink'] = isset($_POST['sem_entry']['show_permalink']);
			$new_options['show_print_link'] = isset($_POST['sem_entry']['show_print_link']);
			$new_options['show_email_link'] = isset($_POST['sem_entry']['show_email_link']);
			$new_options['show_comment_link'] = isset($_POST['sem_entry']['show_comment_link']);

			$new_captions['permalink'] = strip_tags(stripslashes($_POST['sem_entry']['permalink']));
			$new_captions['print_link'] = strip_tags(stripslashes($_POST['sem_entry']['print_link']));
			$new_captions['email_link'] = strip_tags(stripslashes($_POST['sem_entry']['email_link']));
			$new_captions['comment_link'] = strip_tags(stripslashes($_POST['sem_entry']['comment_link']));
			$new_captions['1_comment_link'] = strip_tags(stripslashes($_POST['sem_entry']['1_comment_link']));
			$new_captions['n_comments_link'] = strip_tags(stripslashes($_POST['sem_entry']['n_comments_link']));

			if ( $new_options != $sem_options )
			{
				$sem_options = $new_options;

				update_option('sem6_options', $sem_options);
			}

			if ( $new_captions != $sem_captions )
			{
				$sem_captions = $new_captions;

				update_option('sem6_captions', $sem_captions);
			}
		}

		echo '<input type="hidden" name="update_sem_entry[actions]" value="1" />';

		echo '<h3>'
			. __('Config')
			. '</h3>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. '<input type="checkbox"'
				. ' name="sem_entry[show_permalink]"'
				. ( $sem_options['show_permalink']
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. ' '
			. __('Show Permalink')
			. '</label>'
			. '</div>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. '<input type="checkbox"'
				. ' name="sem_entry[show_print_link]"'
				. ( $sem_options['show_print_link']
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. ' '
			. __('Show Print Link')
			. '</label>'
			. '</div>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. '<input type="checkbox"'
				. ' name="sem_entry[show_email_link]"'
				. ( $sem_options['show_email_link']
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. ' '
			. __('Show Email Link')
			. '</label>'
			. '</div>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. '<input type="checkbox"'
				. ' name="sem_entry[show_comment_link]"'
				. ( $sem_options['show_comment_link']
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. ' '
			. __('Show Comment Link')
			. '</label>'
			. '</div>';

		echo '<h3>'
			. __('Captions')
			. '</h3>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. __('Permalink, e.g. Permalink')
			. '<br />'
			. '<input type="text" style="width: 95%"'
				. ' name="sem_entry[permalink]"'
				. ' value="' . attribute_escape($sem_captions['permalink']) . '"'
				. ' />'
			. '</label>'
			. '</div>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. __('Print, e.g. Print')
			. '<br />'
			. '<input type="text" style="width: 95%"'
				. ' name="sem_entry[print_link]"'
				. ' value="' . attribute_escape($sem_captions['print_link']) . '"'
				. ' />'
			. '</label>'
			. '</div>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. __('Email, e.g. Email')
			. '<br />'
			. '<input type="text" style="width: 95%"'
				. ' name="sem_entry[email_link]"'
				. ' value="' . attribute_escape($sem_captions['email_link']) . '"'
				. ' />'
			. '</label>'
			. '</div>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. __('Comment, e.g. Comment')
			. '<br />'
			. '<input type="text" style="width: 95%"'
				. ' name="sem_entry[comment_link]"'
				. ' value="' . attribute_escape($sem_captions['comment_link']) . '"'
				. ' />'
			. '</label>'
			. '</div>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. __('1 Comment, e.g. 1 Comment')
			. '<br />'
			. '<input type="text" style="width: 95%"'
				. ' name="sem_entry[1_comment_link]"'
				. ' value="' . attribute_escape($sem_captions['1_comment_link']) . '"'
				. ' />'
			. '</label>'
			. '</div>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. __('n Comments, e.g. %num% Comments')
			. '<br />'
			. '<input type="text" style="width: 95%"'
				. ' name="sem_entry[n_comments_link]"'
				. ' value="' . attribute_escape($sem_captions['n_comments_link']) . '"'
				. ' />'
			. '</label>'
			. '</div>';
	} # actions()


	#
	# comments()
	#

	function comments()
	{
		global $sem_options;
		global $sem_captions;

		if ( $_POST['update_sem_entry']['comments'] )
		{
			$new_options = $sem_options;
			$new_captions = $sem_captions;

			$new_options['show_trackback_uri'] = isset($_POST['sem_entry']['show_trackback_uri']);
			$new_options['show_comment_permalink'] = isset($_POST['sem_entry']['show_comment_permalink']);

			$new_captions['comments_on'] = strip_tags(stripslashes($_POST['sem_entry']['comments_on']));
			$new_captions['comment_trackback'] = strip_tags(stripslashes($_POST['sem_entry']['comment_trackback']));			
			$new_captions['comment_permalink'] = strip_tags(stripslashes($_POST['sem_entry']['comment_permalink']));
			$new_captions['reply_link'] = strip_tags(stripslashes($_POST['sem_entry']['reply_link']));

			$new_captions['leave_comment'] = strip_tags(stripslashes($_POST['sem_entry']['leave_comment']));
			$new_captions['logged_in_as'] = strip_tags(stripslashes($_POST['sem_entry']['logged_in_as']));
			$new_captions['login_required'] = strip_tags(stripslashes($_POST['sem_entry']['login_required']));
			$new_captions['name_field'] = strip_tags(stripslashes($_POST['sem_entry']['name_field']));
			$new_captions['email_field'] = strip_tags(stripslashes($_POST['sem_entry']['email_field']));
			$new_captions['required_field'] = strip_tags(stripslashes($_POST['sem_entry']['required_field']));
			$new_captions['url_field'] = strip_tags(stripslashes($_POST['sem_entry']['url_field']));
			$new_captions['submit_field'] = strip_tags(stripslashes($_POST['sem_entry']['submit_field']));

			if ( $new_options != $sem_options )
			{
				$sem_options = $new_options;

				update_option('sem6_options', $sem_options);
			}

			if ( $new_captions != $sem_captions )
			{
				$sem_captions = $new_captions;

				update_option('sem6_captions', $sem_captions);
			}
		}

		echo '<input type="hidden" name="update_sem_entry[comments]" value="1" />';

		echo '<h3>'
			. __('Config')
			. '</h3>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. '<input type="checkbox"'
				. ' name="sem_entry[show_trackback_uri]"'
				. ( $sem_options['show_trackback_uri']
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. ' '
			. __('Show Trackback URI')
			. '</label>'
			. '</div>';
			
		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. '<input type="checkbox"'
				. ' name="sem_entry[show_comment_permalink]"'
				. ( $sem_options['show_comment_permalink']
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. ' '
			. __('Show Permalink')
			. '</label>'
			. '</div>';


		echo '<h3>'
			. __('Captions')
			. '</h3>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. __('Comments on Entry, e.g. Comments on %title%')
			. '<br />'
			. '<input type="text" style="width: 95%"'
				. ' name="sem_entry[comments_on]"'
				. ' value="' . attribute_escape($sem_captions['comments_on']) . '"'
				. ' />'
			. '</label>'
			. '</div>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. __('Trackback URI, e.g. Trackback URI')
			. '<br />'
			. '<input type="text" style="width: 95%"'
				. ' name="sem_entry[comment_trackback]"'
				. ' value="' . attribute_escape($sem_captions['comment_trackback']) . '"'
				. ' />'
			. '</label>'
			. '</div>';
			
		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. __('Permalink, e.g. Permalink')
			. '<br />'
			. '<input type="text" style="width: 95%"'
				. ' name="sem_entry[comment_permalink]"'
				. ' value="' . attribute_escape($sem_captions['comment_permalink']) . '"'
				. ' />'
			. '</label>'
			. '</div>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. __('Reply, e.g. Reply')
			. '<br />'
			. '<input type="text" style="width: 95%"'
				. ' name="sem_entry[reply_link]"'
				. ' value="' . attribute_escape($sem_captions['reply_link']) . '"'
				. ' />'
			. '</label>'
			. '</div>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. __('Comment Form, e.g. Leave a Comment')
			. '<br />'
			. '<input type="text" style="width: 95%"'
				. ' name="sem_entry[leave_comment]"'
				. ' value="' . attribute_escape($sem_captions['leave_comment']) . '"'
				. ' />'
			. '</label>'
			. '</div>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. __('Logged in as User, e.g. Logged in as %identity%')
			. '<br />'
			. '<input type="text" style="width: 95%"'
				. ' name="sem_entry[logged_in_as]"'
				. ' value="' . attribute_escape($sem_captions['logged_in_as']) . '"'
				. ' />'
			. '</label>'
			. '</div>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. __('Login Required, e.g. You must be logged in to comment')
			. '<br />'
			. '<input type="text" style="width: 95%"'
				. ' name="sem_entry[login_required]"'
				. ' value="' . attribute_escape($sem_captions['login_required']) . '"'
				. ' />'
			. '</label>'
			. '</div>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. __('Name Field, e.g. Name')
			. '<br />'
			. '<input type="text" style="width: 95%"'
				. ' name="sem_entry[name_field]"'
				. ' value="' . attribute_escape($sem_captions['name_field']) . '"'
				. ' />'
			. '</label>'
			. '</div>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. __('Email Field, e.g. Email')
			. '<br />'
			. '<input type="text" style="width: 95%"'
				. ' name="sem_entry[email_field]"'
				. ' value="' . attribute_escape($sem_captions['email_field']) . '"'
				. ' />'
			. '</label>'
			. '</div>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. __('Required Field, e.g. (Required)')
			. '<br />'
			. '<input type="text" style="width: 95%"'
				. ' name="sem_entry[required_field]"'
				. ' value="' . attribute_escape($sem_captions['required_field']) . '"'
				. ' />'
			. '</label>'
			. '</div>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. __('URL Field, e.g. URL')
			. '<br />'
			. '<input type="text" style="width: 95%"'
				. ' name="sem_entry[url_field]"'
				. ' value="' . attribute_escape($sem_captions['url_field']) . '"'
				. ' />'
			. '</label>'
			. '</div>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. __('Submit Field, e.g. Submit Comment')
			. '<br />'
			. '<input type="text" style="width: 95%"'
				. ' name="sem_entry[submit_field]"'
				. ' value="' . attribute_escape($sem_captions['submit_field']) . '"'
				. ' />'
			. '</label>'
			. '</div>';
	} # comments()
} # sem_entry_admin


#
# sem_postnav_widget_control()
#

function sem_postnav_widget_control()
{
	global $sem_captions;

	if ( $_POST['update_sem_footer']['postnav'] )
	{
		$new_captions = $sem_captions;

		$new_captions['prev_page'] = strip_tags(stripslashes($_POST['sem_footer']['label_prev_page']));
		$new_captions['next_page'] = strip_tags(stripslashes($_POST['sem_footer']['label_next_page']));

		if ( $new_captions != $sem_captions )
		{
			$sem_captions = $new_captions;

			update_option('sem6_captions', $sem_captions);
		}
	}

	echo '<input type="hidden" name="update_sem_footer[postnav]" value="1" />';

	echo '<h3>'
		. __('Captions')
		. '</h3>';

	echo '<div style="margin-bottom: .2em;">'
		. '<label>'
		. __('Previous Post Page, e.g. Previous Page')
		. '<br />'
		. '<input type="text" style="width: 95%"'
			. ' name="sem_footer[label_prev_page]"'
			. ' value="' . attribute_escape($sem_captions['prev_page']) . '"'
			. ' />'
		. '</label>'
		. '</div>';

	echo '<div style="margin-bottom: .2em;">'
		. '<label>'
		. __('Next Post Page, e.g. Next Page')
		. '<br />'
		. '<input type="text" style="width: 95%"'
			. ' name="sem_footer[label_next_page]"'
			. ' value="' . attribute_escape($sem_captions['next_page']) . '"'
			. ' />'
		. '</label>'
		. '</div>';
} # sem_postnav_widget_control()
?>