<?php
#
# auto-correct sem 5.x to sem 6
#


#
# fetch sidebars_widgets
#

$sidebars_widgets = get_option('sidebars_widgets');


#
# insert header/footer boxes widget if necessary
#

if ( ( $sidebars_widgets = get_option('sidebars_widgets') ) !== false ) :

$sidebars_widgets['the_header'] = (array) $sidebars_widgets['the_header'];
$sidebars_widgets['the_footer'] = (array) $sidebars_widgets['the_footer'];

if ( !in_array('header_boxes', $sidebars_widgets['the_header']))
	array_push($sidebars_widgets['the_header'], 'header_boxes');

if ( !in_array('footer_boxes', $sidebars_widgets['the_footer']))
	array_unshift($sidebars_widgets['the_footer'], 'footer_boxes');

endif;


#
# switch skin
#

if ( !file_exists(sem_path . '/skins/' . $sem_options['active_skin']) ) :

$sem_options['active_skin'] = 'copywriter-gold';

endif;


#
# switch layout
#



switch ( $sem_options['active_layout'] ) :

case 'mse':
	$sidebars_widgets['sidebar-2'] = $sidebars_widgets['ext_sidebar'];
	$sem_options['active_layout'] = 'mts';
	break;

case 'sme':
	$sidebars_widgets['sidebar-2'] = $sidebars_widgets['ext_sidebar'];
	$sem_options['active_layout'] = 'sms';
	break;

case 'ems':
	$sidebars_widgets['sidebar-1'] = $sidebars_widgets['ext_sidebar'];
	$sem_options['active_layout'] = 'sms';
	break;

case 'esm':
	$sidebars_widgets['sidebar-1'] = $sidebars_widgets['ext_sidebar'];
	$sem_options['active_layout'] = 'tsm';
	break;

case 'me':
	$sidebars_widgets['sidebar-1'] = $sidebars_widgets['ext_sidebar'];
	$sem_options['active_layout'] = 'mms';
	break;

case 'em':
	$sidebars_widgets['sidebar-1'] = $sidebars_widgets['ext_sidebar'];
	$sem_options['active_layout'] = 'smm';
	break;

case 'ssm':
	$sem_options['active_layout'] = 'tsm';
	break;

case 'mss':
	$sem_options['active_layout'] = 'mts';
	break;

case 'emss':
case 'msse':
case 'esms':
case 'smse':
	$sidebars_widgets['top_sidebar'] = $sidebars_widgets['ext_sidebar'];
	$sem_options['active_layout'] = 'mts';
	break;

case 'ssme':
case 'essm':
	$sidebars_widgets['top_sidebar'] = $sidebars_widgets['ext_sidebar'];
	$sem_options['active_layout'] = 'tsm';
	break;

case 'ms':
case 'sm':
	if ( isset($sem_options['active_width'])
		&& in_array($sem_options['active_width'], array('wide', 'flex'))
		)
	{
		$sem_options['active_layout'] = str_replace('m', 'mm', $sem_options['active_layout']);
	}
	break;

case 'm':
	if ( isset($sem_options['active_width']) )
	{
		if ( $sem_options['active_width'] == 'wide' )
			$sem_options['active_layout'] = 'mm';
		elseif ( $sem_option['active_width'] == 'flex' )
			$sem_options = 'mmm';
	}
	break;
	
endswitch;


#
# update sidebars_widgets
#

update_option('sidebars_widgets', $sidebars_widgets);


#
# upgrade options and captions
#

unset($sem_options['api_key']);
unset($sem_options['theme_archives']);
unset($sem_options['theme_credits']);
unset($sem_options['theme_meta']);
unset($sem_options['seo']);
unset($sem_options['scripts']);
unset($sem_options['active_header']);
unset($sem_options['active_font_size']);
unset($sem_options['active_width']);
unset($sem_options['show_trackback_uri']);
unset($sem_options['show_comment_permalink']);
unset($sem_options['show_email_link']);
unset($sem_options['show_comment_link']);
unset($sem_options['show_print_link']);
unset($sem_options['show_permalink']);

if ( is_array($sem_options['header']) ) {
	if ( !isset($sem_options['header_mode']) )
		$sem_options['header_mode'] = $sem_options['header']['mode'];
	unset($sem_options['header']);
}

unset($sem_captions['entry_author']);
unset($sem_captions['1_comment_link']);
unset($sem_captions['comment_link']);
unset($sem_captions['comment_permalink']);
unset($sem_captions['email_link']);
unset($sem_captions['n_comments_link']);
unset($sem_captions['permalink']);
unset($sem_captions['print_link']);
unset($sem_captions['comment_trackback']);
unset($sem_captions['required_field']);

if ( !isset($sem_captions['required_fields']) ) {
	$sem_captions["required_fields"] = __("Fields marked by an asterisk (*) are required.");
}

if ( strpos($sem_captions['login_required'], '%login_url%') === false ) {
	$sem_captions['login_required'] .=
		( substr($sem_captions['login_required'], -1) != '.'
			? '. '
			: ' '
			)
		. '%login_url%.';
}

if ( strpos($sem_captions['logged_in_as'], '%logout_url%') === false ) {
	$sem_captions['logged_in_as'] .=
		( substr($sem_captions['logged_in_as'], -1) != '.'
			? '. '
			: ' '
			)
		. '%logout_url%.';
}

#dump($sem_options, $sem_captions);
#die;
?>