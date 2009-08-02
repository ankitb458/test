<?php
/**
 * semiologic_affiliate_admin
 *
 * @package Semiologic Affiliate
 **/

class semiologic_affiliate_admin {
	/**
	 * save_options()
	 *
	 * @return void
	 **/

	function save_options() {
		if ( !$_POST )
			return;
		
		check_admin_referer('semiologic_affiliate');
		
		$campaign_key = stripslashes($_POST['campaign_key']);
		
		if ( preg_match("/\?aff=(.+)/i", $campaign_key, $match) )
			$campaign_key = end($match);
		
		$campaign_key = trim(preg_replace("/[^0-9a-zA-Z_-]/", "", $campaign_key));
		
		update_option('sem_campaign_key', $campaign_key);
		
		echo "<div class=\"updated fade\">\n"
			. "<p>"
				. "<strong>"
				. __('Settings saved.', 'sem-semiologic-affiliate')
				. "</strong>"
			. "</p>\n"
			. "</div>\n";
	} # save_options()
	
	
	/**
	 * edit_options()
	 *
	 * @return void
	 **/

	function edit_options() {
		echo '<div class="wrap">' . "\n";
		
		echo '<form method="post" action="">' . "\n";
		
		wp_nonce_field('semiologic_affiliate');
		
		screen_icon();
		
		echo '<h2>' . __('Semiologic Affiliate Settings', 'sem-semiologic-affiliate') . '</h2>' . "\n";
		
		$aff_id = semiologic_affiliate::get_campaign();
		
		echo '<table class="form-table">' . "\n";
		
		echo '<p>'
			. __('Fill in the field that follows to make all links to semiologic.com and getsemiologic.com on the site use your campaign ID automatically.', 'sem-semiologic-affiliate')
			. '</p>' . "\n";

		echo '<tr>' . "\n"
			. '<th scope="row">'
			. '<label for="campaign_key">'
			. sprintf(__('Your <a href="%s">Campaign ID</a>', 'sem-semiologic-affiliate'), 'http://oldbackend.semiologic.com')
			. '</label>'
			. '</th>' . "\n"
			. '<td>'
			. '<label for="campaign_key">http://www.getsemiologic.com?aff=</label>'
			. '<input type="text"'
				. ' name="campaign_key" id="campaign_key"'
				. ' value="' . esc_attr($aff_id) . '"'
				. ' />'
			. '</td>'
			. '</tr>' . "\n";
		
		echo '</table>' . "\n";
		
		echo '<div class="submit">'
			. '<input type="submit" value="' . esc_attr(__('Save Changes', 'sem-semiologic-affiliate')) . '" />'
			. '</div>' . "\n";
		
		echo '</form>' . "\n";
		
		echo '</div>' . "\n";
	} # edit_options()
} # semiologic_affiliate_admin

add_action('settings_page_semiologic_affiliate', array('semiologic_affiliate_admin', 'save_options'), 0);
?>