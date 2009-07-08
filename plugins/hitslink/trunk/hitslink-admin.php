<?php
/**
 * hitslink_admin
 *
 * @package HitsLink
 **/

add_action('settings_page_hitslink', array('hitslink_admin', 'save_options'), 0);

class hitslink_admin {
	/**
	 * save_options()
	 *
	 * @return void
	 **/

	function save_options() {
		if ( !$_POST )
			return;
		
		check_admin_referer('hitslink');
		
		$script = trim(stripslashes($_POST['hitslink_script']));
		
		if ( preg_match("/
					\b
					wa_account
					\s*
					=
					\s*
					\"
					(?:
						your_id
					|
					)
					\"
				/ix",
				$script
				)
			) {
			$script = '';
		}
		
		update_option('hitslink', $script);
		
		echo '<div class="updated fade">' . "\n"
			. '<p>'
				. '<strong>'
				. __('Settings Saved.', 'hitslink')
				. '</strong>'
			. '</p>' . "\n"
			. '</div>' . "\n";
	} # save_options()
	
	
	/**
	 * edit_options()
	 *
	 * @return void
	 **/

	function edit_options() {
		$script = hitslink::get_options();
		
		if ( !$script ) {
			$script = <<<EOS
<!-- www.hitslink.com/ web tools statistics hit counter code -->
<script type="text/javascript" id="wa_u"></script>
<script type="text/javascript">//<![CDATA[
wa_account="your_id"; wa_location=xxx;
wa_pageName=location.pathname;  // you can customize the page name here
document.cookie='__support_check=1';wa_hp='http';
wa_rf=document.referrer;wa_sr=window.location.search;
wa_tz=new Date();if(location.href.substr(0,6).toLowerCase()=='https:')
wa_hp='https';wa_data='&an='+escape(navigator.appName)+
'&sr='+escape(wa_sr)+'&ck='+document.cookie.length+
'&rf='+escape(wa_rf)+'&sl='+escape(navigator.systemLanguage)+
'&av='+escape(navigator.appVersion)+'&l='+escape(navigator.language)+
'&pf='+escape(navigator.platform)+'&pg='+escape(wa_pageName);
wa_data=wa_data+'&cd='+
screen.colorDepth+'&rs='+escape(screen.width+ ' x '+screen.height)+
'&tz='+wa_tz.getTimezoneOffset()+'&je='+ navigator.javaEnabled();
wa_img=new Image();wa_img.src=wa_hp+'://counter.hitslink.com/statistics.asp'+
'?v=1&s='+wa_location+'&acct='+wa_account+wa_data+'&tks='+wa_tz.getTime();
document.getElementById('wa_u').src=wa_hp+'://counter.hitslink.com/track.js'; //]]>
</script>
<!-- End www.hitslink.com/ statistics web tools hit counter code -->
EOS;
		}
		
		echo '<div class="wrap">' . "\n";
		
		echo '<form method="post" action="">' . "\n";
		
		wp_nonce_field('hitslink');
		
		screen_icon();
		
		echo '<h2>' . __('HitsLink Options', 'hitslink') . '</h2>' . "\n";
		
		echo '<table class="form-table">' . "\n"
			. '<tr valign="top">' . "\n"
			. '<th scope="row"><label for="hitslink_script">'
			. __('HitsLink Script', 'hitslink')
			. '</label></th>' . "\n"
			. '<td>'
			. '<textarea class="widefat code" cols="58" rows="12"'
				. ' onfocus="var this_val=eval(this); this_val.select();"'
				. ' id="hitslink_script" name="hitslink_script"'
				. '>'
			. format_to_edit($script, ENT_QUOTES)
			. '</textarea>' . "\n"
			. '<p><label for="hitslink_script">'
			. __('Paste the generic <a href="http://www.semiologic.com/go/hitslink">HitsLink</a> script into the above textarea.', 'hitslink')
			. '</label></p>' . "\n"
			. '</td>' . "\n"
			. '</tr>' . "\n"
			. '</table>' . "\n";
		
		echo '<p class="submit">'
			. '<input type="submit"'
				. ' value="' . esc_attr(__('Save Changes', 'hitslink')) . '"'
				. " />"
			. "</p>\n";
		
		echo '</form>' . "\n";

		echo '</div>' . "\n";
	} # edit_options()
} # hitslink_admin
?>