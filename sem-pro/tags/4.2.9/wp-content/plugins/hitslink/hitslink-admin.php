<?php
class hitslink_admin
{
	#
	# init()
	#

	function init()
	{
		add_action('admin_menu', array('hitslink_admin', 'add_option_page'));
	} # init()


	#
	# add_option_page()
	#

	function add_option_page()
	{
		if ( !function_exists('get_site_option') || is_site_admin() )
		{
			add_options_page(
					__('HitsLink'),
					__('HitsLink'),
					7,
					str_replace("\\", "/", __FILE__),
					array('hitslink_admin', 'display_options')
					);
		}
	} # add_option_page()


	#
	# update_options()
	#

	function update_options()
	{
		$_POST['hitslink']['script'] = stripslashes($_POST['hitslink']['script']);

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
				/iux",
				$_POST['hitslink']['script']
				)
			)
		{
			$_POST['hitslink']['script'] = false;
		}

		if ( function_exists('get_site_option') && is_site_admin() )
		{
			update_site_option('hitslink_params', $_POST['hitslink']);
		}
		elseif ( !function_exists('get_site_option') )
		{
			update_option('hitslink_params', $_POST['hitslink']);
		}
	} # update_options()


	#
	# display_options()
	#

	function display_options()
	{
		# Process updates, if any

		if ( isset($_POST['action'])
			&& ( $_POST['action'] == 'update_hitslink' )
			)
		{
			hitslink_admin::update_options();

			echo '<div class="updated">' . "\n"
				. '<p>'
					. '<strong>'
					. __('Options saved.', 'hitslink')
					. '</strong>'
				. '</p>' . "\n"
				. '</div>' . "\n";
		}

		$options = hitslink::get_options();

		if ( !$options['script'] )
		{
			$options['script'] = <<<EOF
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
EOF;
		}

		# Display admin page

		echo '<div class="wrap">' . "\n"
			. "<h2>" . __('HitsLink Options', 'hitslink') . "</h2>\n"
			. '<form method="post" action="">' . "\n"
			. '<input type="hidden" name="action" value="update_hitslink" />' . "\n";

		echo '<fieldset class="options">' . "\n"
			. "<legend>" . __('HitsLink script', 'hitslink') . "</legend>\n";

		echo '<p style="padding-bottom: 6px;">'
				. '<label for="script">'
				. __('Paste the generic <a href="http://www.semiologic.com/go/hitslink">HitsLink</a> script into the following textarea:', 'hitslink')
				. '</label></p>' ."\n"
				. '<textarea id="script" name="hitslink[script]"'
					. ' style="width: 590px; height: 240px;">'
				. htmlspecialchars($options['script'], ENT_QUOTES)
				. "</textarea>\n";

		echo "</fieldset>\n";

		echo '<p class="submit">'
			. '<input type="submit"'
				. ' value="' . __('Update Options', 'hitslink') . '"'
				. " />"
			. "</p>\n";

		echo "</form>\n";

		echo "<h2>" . __('HitsLink Options', 'hitslink') . "</h2>\n"
			. '<p>' . __('The following will work out of the box:', 'hitslink') . "</p>\n"
			. '<ul>'
			. '<li>' . __('Authors, editors and site admins are not tracked when logged in') . '</li>'

#
# to change?
#

#			. '<li>' . __('Outbound links are tracked as /outbound/[url]?ref=[referrer]') . '</li>'
#			. '<li>' . __('File downloads are tracked as /file/[file]?ref=[referrer]') . '</li>'
			. '<li>' . __('<a href="http://www.semiologic.com/software/newsletter-manager/">Mailing list subscriptions</a> are tracked as /subscription/[referrer]') . '</li>'
			. '<li>' . __('Search queries are tracked as /search/[keyword]?ref=[referrer]') . '</li>'
			. '<li>' . __('404 errors (page not found) are tracked as /404/[url]?ref=[referrer]') . '</li>'
			. '</ul>';

		echo "</div>\n";
	} # display_options()
} # hitslink_admin

hitslink_admin::init();
?>