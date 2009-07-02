<?php
class sem_ads
{
	#
	# init()
	#

	function init()
	{
		global $wp_ozh_wsa;

		if ( isset($wp_ozh_wsa) )
		{
			add_action('widgets_init', array('sem_ads', 'widgetize'), 15);

			remove_action('admin_menu', 'wp_ozh_wsa_addmenu');
			add_action('admin_menu', array('sem_ads', 'admin_menu'));

			add_action('presentation_page_ads', array('sem_ads', 'extend'));

			add_action('admin_head', array('sem_ads', 'display_all_ads'));
		}
	} # init()


	#
	# display_all_ads()
	#

	function display_all_ads()
	{
		global $wp_ozh_wsa;

		$js_options = "";

		if ( isset($wp_ozh_wsa) )
		{
			foreach ( array_keys((array) $wp_ozh_wsa['contexts']) as $context )
			{
				$js_options .= ( $js_options ? ', ' : '' )
					. "\""
					. $context
					. "\"";
			}
		}
?><script type="text/javascript">
var all_ads = new Array(<?php echo $js_options; ?>);
document.all_ads = all_ads;
//alert(document.all_ads);
</script>
<?php
	} # end display_all_ads()


	#
	# widgetize()
	#

	function widgetize()
	{
		global $wp_ozh_wsa;

		$options = get_option($wp_ozh_wsa['optionname']);

		foreach ( array_keys((array) $options['contexts']) as $ad_unit )
		{
			register_widget_control('Ad: ' . $ad_unit, array('sem_ads', 'widget_control'));
		}
	} # widgetize()


	#
	# widget_control()
	#

	function widget_control()
	{
		echo __('To configure your ad, visit') . ':<br /><br />' . __('Presentation / Ads');
	} # widget_control()


	#
	# admin_menu()
	#

	function admin_menu()
	{
		include_once(ABSPATH.'wp-content/plugins/ozh-who-sees-ads/wp_ozh_whoseesads_admin.php');

	    if ( strpos($_SERVER['REQUEST_URI'], 'ads.php') !== false )
	    {
			wp_enqueue_script('scriptaculous');
		}

		add_submenu_page('themes.php', 'Ads', 'Ads', 'administrator', 'ads.php', 'wp_ozh_wsa_addmenupage');
	} # admin_menu()


	#
	# extend()
	#

	function extend()
	{
		switch( $_POST['action'] )
		{
		case 'rename':
			add_action('admin_footer', array('sem_ads', 'rename'));
			break;
		}

		ob_start(array('sem_ads', 'kill_promote'));
	} # extend()


	#
	# kill_promote()
	#

	function kill_promote($buffer)
	{
		$buffer = preg_replace("/
			<p><div\s+class=\"wsa_paypal\">
			(?:.*)
			<\/p>
			(?:.*)
			<\/p>
			/isUx",
			"",
			$buffer);

		return $buffer;
	} # kill_promote()


	#
	# rename()
	#

	function rename()
	{
		global $wp_ozh_wsa;
		$options = get_option($wp_ozh_wsa['optionname']);

		$source = wp_ozh_wsa_processforms_sanitize($_POST['source']);
		$target = wp_ozh_wsa_processforms_sanitize($_POST['target']);

		if ( !isset($options['contexts'][$source]) && isset($options['contexts'][$target]) )
		{
			$source = 'ad-' . $source;
			$target = 'ad-' . $target;

			global $sem_widget_contexts;

			$sem_widget_contexts[$target] = $sem_widget_contexts[$source];

			unset($sem_widget_contexts[$source]);

			update_option('sem_widget_contexts', $sem_widget_contexts);
		}
	} # rename()
} # sem_ads

sem_ads::init();
?>