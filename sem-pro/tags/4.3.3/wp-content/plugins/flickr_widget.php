<?php
/*
Plugin Name: Flickr Widget
Plugin URI: http://donncha.wordpress.com/flickr-widget/
Description: A widget which will display your latest Flickr photos.
Author: Donncha O Caoimh
Version: 0.3.1 (fork)
Author URI: http://inphotos.org/

Installing
1. Make sure you have the Widget plugin available at http://automattic.com/code/widgets/
1. Copy flickr_widget.php to your plugins folder, /wp-content/plugins/widgets/
2. Activate it through the plugin management screen.
3. Go to Themes->Sidebar Widgets and drag and drop the widget to wherever you want to show it.

Changelog
0.1 = First public release.
*/

function widget_flickr($args) {
	require_once(ABSPATH . WPINC . '/rss.php');
	extract($args);

	$options = get_option('widget_flickr');
	if( $options == false ) {
		$options[ 'title' ] = 'Flickr Photos';
		$options[ 'items' ] = 3;
	}
	$title = empty($options['title']) ? __('Flickr Photos') : $options['title'];
	$items = $options[ 'items' ];
	$flickr_rss_url = empty($options['flickr_rss_url']) ? __('http://flickr.com/services/feeds/photos_public.gne?id=78656712@N00&format=rss_200') : $options['flickr_rss_url'];
	if ( empty($items) || $items < 1 || $items > 10 ) $items = 3;

	$rss = fetch_rss( $flickr_rss_url );
	if( is_array( $rss->items ) ) {
		$out = '';
		$items = array_slice( $rss->items, 0, $items );
		while( list( $key, $photo ) = each( $items ) ) {
			preg_match_all("/<IMG.+?SRC=[\"']([^\"']+)/si",$photo[ 'description' ],$sub,PREG_SET_ORDER);

			if ( empty($sub) )
			{
				preg_match_all("/<IMG.+?SRC=[\"']([^\"']+)/si",$photo[ 'atom_content' ],$sub,PREG_SET_ORDER);
			}

			$photo_url = str_replace( "_m.jpg", "_t.jpg", $sub[0][1] );
			$out .= "<a href='{$photo[ 'link' ]}'><img alt='".wp_specialchars( $photo[ 'title' ], true )."' title='".wp_specialchars( $photo[ 'title' ], true )."' src='$photo_url' border='0'></a><br /><br />";
		}
		$flickr_home = $rss->channel[ 'link' ];
		$flickr_more_title = $rss->channel[ 'title' ];
	}
	?>
	<?php echo $before_widget; ?>
	<?php echo $before_title . $title . $after_title; ?>
<!-- Start of Flickr Badge -->
<style type="text/css">
#flickr_badge_source_txt {padding:0; font: 11px Arial, Helvetica, Sans serif; color:#666666;}
#flickr_badge_icon {display:block !important; margin:0 !important; border: 1px solid rgb(0, 0, 0) !important;}
#flickr_icon_td {padding:0 5px 0 0 !important;}
.flickr_badge_image {text-align:center !important;}
.flickr_badge_image img {border: 1px solid black !important;}
#flickr_badge_uber_wrapper {width:150px;}
#flickr_www {display:block; text-align:center; padding:0 10px 0 10px !important; font: 11px Arial, Helvetica, Sans serif !important; color:#3993ff !important;}
#flickr_badge_uber_wrapper a:hover,
#flickr_badge_uber_wrapper a:link,
#flickr_badge_uber_wrapper a:active,
#flickr_badge_uber_wrapper a:visited {text-decoration:none !important; background:inherit !important;color:#3993ff;}
#flickr_badge_wrapper {background-color:#ffffff;border: solid 1px #000000}
#flickr_badge_source {padding:0 !important; font: 11px Arial, Helvetica, Sans serif !important; color:#666666 !important;}
</style>
<table id="flickr_badge_uber_wrapper" cellpadding="0" cellspacing="10" border="0"><tr><td><table cellpadding="0" cellspacing="10" border="0" id="flickr_badge_wrapper">
<tr><td align='center'>
<?php echo $out ?>
<a href="<?php echo wp_filter_post_kses(strip_tags( $flickr_home )); ?>">More Photos</a>
</td></tr>
</table>
</td></tr></table>
<!-- End of Flickr Badge -->

		<?php echo $after_widget; ?>
<?php
}

function widget_flickr_control() {
	$options = $newoptions = get_option('widget_flickr');
	if( $options == false ) {
		$newoptions[ 'title' ] = 'Flickr Photos';
	}
	if ( $_POST["flickr-submit"] ) {
		$newoptions['title'] = stripslashes(wp_filter_post_kses(strip_tags($_POST["flickr-title"])));
		$newoptions['items'] = stripslashes(wp_filter_post_kses(strip_tags($_POST["rss-items"])));
		$newoptions['flickr_rss_url'] = stripslashes(wp_filter_post_kses(strip_tags($_POST["flickr-rss-url"])));
	}
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_option('widget_flickr', $options);
	}
	$title = wp_specialchars($options['title']);
	$items = wp_specialchars($options['items']);
	if ( empty($items) || $items < 1 ) $items = 3;
	$flickr_rss_url = wp_specialchars($options['flickr_rss_url']);

	?>
	<p><label for="flickr-title"><?php _e('Title:'); ?> <input style="width: 250px;" id="flickr-title" name="flickr-title" type="text" value="<?php echo $title; ?>" /></label></p>
	<p><label for="flickr-rss-url"><?php _e('Flickr RSS URL:'); ?> <input style="width: 250px;" id="flickr-title" name="flickr-rss-url" type="text" value="<?php echo $flickr_rss_url; ?>" /></label></p>
	<p style="text-align:center; line-height: 30px;"><?php _e('How many photos  would you like to display?'); ?> <select id="rss-items" name="rss-items"><?php for ( $i = 1; $i <= 10; ++$i ) echo "<option value='$i' ".($items==$i ? "selected='selected'" : '').">$i</option>"; ?></select></p>
	<p align='left'>
	* Your RSS feed can be found on your Flickr homepage. Scroll down to the bottom of the page until you see the <em>Feed</em> link and copy that into the box above.<br />
	<br clear='all'></p>
	<p>Leave the Flickr RSS URL blank to display <a href="http://inphotos.org/">Donncha's</a> Flickr photos.</p>
	<input type="hidden" id="flickr-submit" name="flickr-submit" value="1" />
	<?php
}


function flickr_widgets_init() {
	if ( function_exists('register_sidebar_widget') )
	{
		register_widget_control('Flickr', 'widget_flickr_control', 500, 250);
		register_sidebar_widget('Flickr', 'widget_flickr');
	}
}
add_action( "init", "flickr_widgets_init" );

?>