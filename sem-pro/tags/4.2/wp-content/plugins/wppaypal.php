<?php
/*
Plugin Name: WP Paypal Donate Widget
Description: Adds a sidebar widget to let users make donation.
Author: Patrick Chia
Version: 1.2 (fork)
Author URI: http://patrick.blogates.com/
*/

function widget_wpaypal_init() {
	if ( !function_exists('register_sidebar_widget') )
		return;

	function widget_wpaypal($args) {
		extract($args);

		$options = get_option('widget_wpaypal');
		$title = htmlspecialchars($options['title'], ENT_QUOTES);
		if ( !$title )
		{
			$title = __('Donate');
		}
		$pp_email = htmlspecialchars($options['pp_email'], ENT_QUOTES);
		$pp_amount = $options['pp_amount'];
		$pp_currency = $options['pp_currency'];

		echo $before_widget . ( $title ? ( $before_title . $title . $after_title ) : '' );
		$url_parts = parse_url(get_bloginfo('home'));
		echo '<div style="margin-top:5px;margin-bottom:5px;text-align:left;"><!--Powered By Patrick Chia http://patrick.blogates.com-->
		<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
		<input type="hidden" name="cmd" value="_xclick"/>
		<input type="hidden" name="business" value="'.$pp_email.'"/>
		<input type="hidden" name="item_name" value="Donation to '. get_bloginfo('name') .'"/>
		<input type="hidden" name="item_number" value="0"/>
		<input type="hidden" name="notify_url" value=""/>
		<input type="hidden" name="no_shipping" value="1"/>
		<input type="hidden" name="return" value="'. get_bloginfo('home') .'"/>
		<input type="hidden" name="no_note" value="1"/>
		<input type="hidden" name="currency_code" value="'.$pp_currency.'"/>
		<input type="hidden" name="tax" value="0"/>
		<input type="hidden" name="bn" value="PP-DonationsBF"/>
		<input type="hidden" name="on0" value="Website"/>
		Amount: <br /><input type="text" name="amount" size="14" title="The amount you wish to donate" value="'.$pp_amount.'"/><br />
		Website (Optional): <br /><input type="text" size="14" title="Your website (will be displayed)" name="os0" value="" /><br />
		<input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but21.gif" border="0" name="submit" alt="Make payments with PayPal - fast, free and secure" style="none" />
		</form></div>';
		echo $after_widget;
	}

	function widget_wpaypal_control() {

		$options = get_option('widget_wpaypal');
		if ( !is_array($options) )
			$options = array('title'=>'Donate','pp_email'=>'', 'pp_amount'=>'5.00', 'pp_currency'=>'USD');
		if ( $_POST['wpaypal-submit'] ) {

			$options['title'] = stripslashes(wp_filter_post_kses(strip_tags($_POST['wpaypal-title'])));
			$options['pp_email'] = stripslashes(wp_filter_post_kses(strip_tags($_POST['wpaypal-pp_email'])));
			$options['pp_amount'] = floatval($_POST['wpaypal-pp_amount']);
			$options['pp_currency'] = preg_replace("/[^0-9a-z]/i", "", $_POST['wpaypal-pp_currency']);
			update_option('widget_wpaypal', $options);
		}

		$title = htmlspecialchars($options['title'], ENT_QUOTES);
		$pp_email = htmlspecialchars($options['pp_email'], ENT_QUOTES);
		$pp_amount = htmlspecialchars($options['pp_amount'], ENT_QUOTES);
		$pp_currency = htmlspecialchars($options['pp_currency'], ENT_QUOTES);

		echo '<p style="text-align:left;"><label for="wpaypal-title">Display Paypal Donate form for your user to make donation. By <a href="http://patrick.blogates.com">Patrick Chia</a></label></p>';
		echo '<p style="text-align:left;"><label for="wpaypal-title">Title: <input style="width: 100%;" id="wpaypal-title" name="wpaypal-title" type="text" value="'.$title.'" /></label></p>';
		echo '<p style="text-align:left;"><label for="wpaypal-pp_email">Paypal Email: <input style="width: 100%;" id="wpaypal-title" name="wpaypal-pp_email" type="text" value="'.$pp_email.'" /></label></p>';
		echo '<p style="text-align:left;"><label for="wpaypal-pp_amount">Donate Amount: <input style="width: 100%;" id="wpaypal-title" name="wpaypal-pp_amount" type="text" value="'.$pp_amount.'" /></label></p>';
		echo '<p style="text-align:left;"><label for="wpaypal-pp_currency">Currency: <input style="width: 100%;" id="wpaypal-title" name="wpaypal-pp_currency" type="text" value="'.$pp_currency.'" /></label></p>';
		echo '<input type="hidden" id="wpaypal-submit" name="wpaypal-submit" value="1" />';
	}

	register_sidebar_widget('Paypal Donate', 'widget_wpaypal');
	register_widget_control('Paypal Donate', 'widget_wpaypal_control', 300, 280);
}
add_action('plugins_loaded', 'widget_wpaypal_init');
?>