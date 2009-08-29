<?php

class sem_ad_space
{
	#
	# Variables
	#

	var $params = array(
			'default_ad_block' => array(),				# default ad units
			'default_ad_distribution' => array()		# default ad distributions
			);
	var $tags = array(
			'top' => 'Above the canvas',
			'header' => 'In the header',
			'above' => 'Above the entries',
			'title' => 'Below each title',
			'below' => 'Below each entry',
			'footer' => 'In the footer',
			'sidebar' => 'In the sidebar',
			'inline' => 'Inline ad'
			);
	var $contexts = array(
			'home' => 'Home',
			'post' => 'Post',
			'page' => 'Page',
			'misc' => 'Archives'
			);
	var $ad_distribution = array();
	var $ad_blocks = array();


	#
	# Constructor
	#

	function sem_ad_space()
	{
		global $wpdb;
		global $table_prefix;
		global $wpmuBaseTablePrefix;

		$wpdb->ad_tags = ( isset($wpmuBaseTablePrefix) ? $wpmuBaseTablePrefix : $table_prefix ) . "ad_tags";
		$wpdb->ad_blocks = ( isset($wpmuBaseTablePrefix) ? $wpmuBaseTablePrefix : $table_prefix ) . "ad_blocks";
		$wpdb->ad_block2tag = ( isset($wpmuBaseTablePrefix) ? $wpmuBaseTablePrefix : $table_prefix ) . "ad_block2tag";
		$wpdb->ad_distributions = ( isset($wpmuBaseTablePrefix) ? $wpmuBaseTablePrefix : $table_prefix ) . "ad_distributions";
		$wpdb->ad_distribution2tag = ( isset($wpmuBaseTablePrefix) ? $wpmuBaseTablePrefix : $table_prefix ) . "ad_distribution2tag";
		$wpdb->ad_distribution2post = ( isset($wpmuBaseTablePrefix) ? $wpmuBaseTablePrefix : $table_prefix ) . "ad_distribution2post";

		$params = function_exists('get_site_option')
			? get_site_option('sem_ad_space_params')
			: get_settings('sem_ad_space_params');
		#$params = '';

		if ( $params )
		{
			foreach ( $params as $key => $value )
			{
				$this->params[$key] = $value;
			}
		}
		else
		{
			$wpdb->query("DROP TABLE IF EXISTS `$wpdb->ad_tags`");
			$wpdb->query("DROP TABLE IF EXISTS `$wpdb->ad_blocks`");
			$wpdb->query("DROP TABLE IF EXISTS `$wpdb->ad_block2tag`");
			$wpdb->query("DROP TABLE IF EXISTS `$wpdb->ad_distributions`");
			$wpdb->query("DROP TABLE IF EXISTS `$wpdb->ad_distribution2tag`");
			$wpdb->query("DROP TABLE IF EXISTS `$wpdb->ad_distribution2post`");


			$wpdb->query("
				CREATE TABLE IF NOT EXISTS `$wpdb->ad_blocks`
				(
					`ad_block_id` INTEGER NOT NULL AUTO_INCREMENT,
					`ad_block_name` VARCHAR(80) NOT NULL DEFAULT '',
					`ad_block_description` TEXT NOT NULL DEFAULT '',
					`ad_block_code` TEXT NOT NULL DEFAULT '',
					PRIMARY KEY(`ad_block_id`)
				)
				");

			$wpdb->query("
				CREATE TABLE IF NOT EXISTS `$wpdb->ad_block2tag`
				(
					`ad_block_id` INTEGER NOT NULL,
					`ad_tag_id` VARCHAR(80) NOT NULL,
					PRIMARY KEY(`ad_block_id`, `ad_tag_id`)
				)
				");

			foreach ( array_keys($this->tags) as $tag )
			{
				$this->params['default_ad_block'][$tag] = 0;
			}

			$wpdb->query("
				CREATE TABLE IF NOT EXISTS `$wpdb->ad_distributions`
				(
					`ad_distribution_id` INTEGER NOT NULL AUTO_INCREMENT,
					`ad_distribution_name` VARCHAR(80) NOT NULL DEFAULT '',
					`ad_distribution_description` TEXT NOT NULL DEFAULT '',
					PRIMARY KEY(`ad_distribution_id`)
				)
				");

			$wpdb->query("
				CREATE TABLE IF NOT EXISTS `$wpdb->ad_distribution2tag`
				(
					`ad_block_id` INTEGER NOT NULL,
					`ad_tag_id` VARCHAR(80) NOT NULL,
					`ad_distribution_id` INTEGER NOT NULL,
					PRIMARY KEY(`ad_block_id`, `ad_tag_id`, `ad_distribution_id`)
				)
				");

			$wpdb->query("
				CREATE TABLE IF NOT EXISTS `$wpdb->ad_distribution2post`
				(
					`ad_distribution_id` INTEGER NOT NULL,
					`post_id` INTEGER NOT NULL,
					PRIMARY KEY(`ad_distribution_id`, `post_id`)
				)
				");

			foreach ( $this->contexts as $key => $name )
			{
				$this->params['default_ad_distribution'][$key] = 0;
			}

			foreach ( array_keys($this->tags) as $tag )
			{
				if ( $tag == 'inline' )
				{
					$wpdb->query("
						INSERT INTO $wpdb->ad_blocks
							( ad_block_name, ad_block_description, ad_block_code )
						VALUES
							( '" . addslashes($tag) . "', '" . __('Default inline ad unit') . "', '<div class=\"ad\">\n" . __('Place your Ad Unit code in this box. Remove the div tag if you do not want your ad to have a ghostwhite background.') . "\n</div>' )
						");

					$ad_block_id = $wpdb->insert_id;

					$wpdb->query("
						INSERT INTO $wpdb->ad_block2tag
							( ad_block_id, ad_tag_id )
						VALUES
							( " . intval($ad_block_id) . ", '" . addslashes($tag) . "' );
						");

					$this->params['default_ad_block'][$tag] = intval($ad_block_id);
					$this->params['default_ad_block_name'] = $tag;
				}
			}

			$wpdb->query("
				INSERT INTO $wpdb->ad_distributions
					( ad_distribution_name, ad_distribution_description )
				VALUES
					( '". addslashes(__('Default Ad Units')) . "', '" . addslashes(__('Display the default ad unit in every location.')) . "' )
				");

			$default_ad_distribution = intval($wpdb->insert_id);

			foreach ( array_keys($this->contexts) as $context_id )
			{
				$this->params['default_ad_distribution'][$context_id] = $default_ad_distribution;
			}

			if ( function_exists('get_site_option') )
			{
				update_site_option('sem_ad_space_params', $this->params);
			}
			else
			{
				update_option('sem_ad_space_params', $this->params);
			}

			add_action('init', array(&$this, 'import_ad_spaces_v_1'));
			add_action('init', array(&$this, 'import_adsense_deluxe'));
		}

		#echo '<pre>';
		#var_dump($this->params);
		#echo '</pre>';

		add_action('admin_menu', array(&$this, 'add2admin_menu'));
		add_action('admin_head', array(&$this, 'init_admin'), 500);
		add_action('admin_head', array(&$this, 'display_admin_js'), 0);
		add_action('edit_form_advanced', array(&$this, 'display_post_ad_selector'));
		add_action('edit_page_form', array(&$this, 'display_page_ad_selector'));
		if ( ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false )
			&& isset($_REQUEST['action'])
			&& in_array($_REQUEST['action'], array('editpost', 'post'))
			)
		{
			add_action('init', array(&$this, 'update_post'));
		}

		add_action('init', array(&$this, 'setup_admin_editor'));

		add_action('admin_head', array(&$this, 'display_all_ad_blocks'));
	} # end sem_ad_space()


	#
	# setup_admin_editor()
	#

	function setup_admin_editor()
	{
		if ( !function_exists('get_site_option') || is_site_admin() )
		{
			if ( function_exists('get_user_option')
				&& ( get_user_option('rich_editing') == 'true' )
				)
			{
				add_filter('mce_plugins', array(&$this, 'add_mce_plugin'));
				add_filter('mce_buttons_2', array(&$this, 'add_mce_button'));
			}
			else
			{
				add_filter('admin_footer', array(&$this, 'display_quicktag'));
			}
		}
	} # end setup_admin_editor()


	#
	# 	import_ad_spaces_v_1();
	#

	function import_ad_spaces_v_1()
	{
		global $wpdb;

		# import

		$res = mysql_list_tables(DB_NAME);

		while ( $row = mysql_fetch_row($res) )
		{
			if ( $row[0] == $GLOBALS['table_prefix'] . 'sem_ad_spaces' )
			{
				$wpdb->ad_spaces = $GLOBALS['table_prefix'] . 'sem_ad_spaces';
			}
		}

		if ( !$wpdb->ad_spaces )
		{
			return;
		}

		$ad_spaces = $wpdb->get_results("SELECT * FROM $wpdb->ad_spaces");

		if ( isset($ad_spaces) )
		{
			foreach ( $ad_spaces as $ad_space )
			{
				$wpdb->query("
					INSERT INTO $wpdb->ad_blocks
						( ad_block_name, ad_block_code )
					VALUES
						( '" . addslashes($ad_space->ad_space_name) . "', '" . addslashes($ad_space->ad_space_code) . "' )
					");

				$ad_block_id = $wpdb->insert_id;

				$wpdb->query("
					INSERT INTO $wpdb->ad_block2tag
						( ad_block_id, ad_tag_id )
					VALUES
						( $ad_block_id, 'above' )
					");

				$wpdb->query("
					INSERT INTO $wpdb->ad_distributions
						( ad_distribution_name )
					VALUES
						( '" . addslashes($ad_space->ad_space_name) . "' )
					");

				$ad_distribution_id = $wpdb->insert_id;

				$wpdb->query("
					INSERT INTO $wpdb->ad_distribution2tag
						( ad_block_id, ad_distribution_id, ad_tag_id )
					VALUES
						( 0, $ad_distribution_id, 'header' ),
						( $ad_block_id, $ad_distribution_id, 'above' ),
						( 0, $ad_distribution_id, 'inline' ),
						( 0, $ad_distribution_id, 'below' ),
						( 0, $ad_distribution_id, 'footer' ),
						( 0, $ad_distribution_id, 'sidebar' )
					");
			}


			$ad_space2post = $wpdb->get_results("
				SELECT
					postmeta.post_id as post_id, ad_distributions.ad_distribution_id
				FROM
					$wpdb->postmeta as postmeta
				INNER JOIN
					$wpdb->ad_spaces as ad_spaces
						ON ad_spaces.ad_space_id = postmeta.meta_value
				INNER JOIN
					$wpdb->ad_distributions as ad_distributions
						ON ad_distribution_name = ad_spaces.ad_space_name
				WHERE
					postmeta.meta_key = '_sem_ad_space'
				");

			if ( $ad_space2post )
			{
				foreach ( $ad_space2post as $rec )
				{
					$wpdb->query("
						INSERT INTO $wpdb->ad_distribution2post
							( ad_distribution_id, post_id )
						VALUES
							( $rec->ad_distribution_id, $rec->post_id )
						");
				}
			}

			$ad_space2post = $wpdb->get_results("
				SELECT
					postmeta.post_id as post_id, postmeta.meta_value as ad_distribution_id
				FROM
					$wpdb->postmeta as postmeta
				WHERE
					postmeta.meta_key = '_sem_ad_space'
					AND postmeta.meta_value IN ( '-1', '-2' )
				");

			if ( $ad_space2post )
			{
				foreach ( $ad_space2post as $rec )
				{
					$wpdb->query("
						INSERT INTO $wpdb->ad_distribution2post
							( ad_distribution_id, post_id )
						VALUES
							( " . ( intval($rec->ad_distribution_id) + 1 ) . ", $rec->post_id )
						");
				}
			}

			$default_ad_space_id = get_settings('sem_ad_space_default');

			switch ( intval($default_ad_space_id) )
			{
			case 0:
			case -1:
				$this->params['default_ad_distribution']['home'] = 0;
				$this->params['default_ad_distribution']['post'] = 0;
				$this->params['default_ad_distribution']['page'] = 0;
				$this->params['default_ad_distribution']['misc'] = 0;
				break;
			case -2:
				$this->params['default_ad_distribution']['home'] = -1;
				$this->params['default_ad_distribution']['post'] = -1;
				$this->params['default_ad_distribution']['page'] = -1;
				$this->params['default_ad_distribution']['misc'] = -1;
				break;
			default:
				$default_ad_distribution_id = $wpdb->get_var("
					SELECT
						ad_distributions.ad_distribution_id
					FROM
						$wpdb->ad_distributions as ad_distributions
					INNER JOIN
						$wpdb->ad_spaces as ad_spaces
							ON ad_spaces.ad_space_name = ad_distributions.ad_distribution_name
					");
				if ( isset($default_ad_distribution_id) )
				{
					$this->params['default_ad_distribution']['home'] = $default_ad_distribution_id;
					$this->params['default_ad_distribution']['post'] = $default_ad_distribution_id;
					$this->params['default_ad_distribution']['page'] = $default_ad_distribution_id;
					$this->params['default_ad_distribution']['misc'] = $default_ad_distribution_id;
				}
			}

			update_option('sem_ad_space_params', $this->params);
		}
	} # end import_ad_spaces_v_1()


	#
	# import_adsense_deluxe()
	#

	function import_adsense_deluxe()
	{
		global $wpdb;

		if ( !defined('ADSDEL_OPTIONS_ID') )
		{
			return;
		}

		$options = get_option(ADSDEL_OPTIONS_ID);

		#echo '<div style="text-align: left; margin: 0px; 0px; 0px; 0px;"><pre>';
		#var_dump($options);
		#echo '</pre></div>';

		foreach ( $options['ads'] as $name => $ad )
		{
			$wpdb->query("
				INSERT INTO $wpdb->ad_blocks
					( ad_block_name, ad_block_description, ad_block_code )
				VALUES
					( '" . addslashes($name) . "', '" . addslashes($ad['desc']) . "', '" . addslashes($ad['adsense']) . "' )
				");

			$ad_block_id = $wpdb->insert_id;

			$wpdb->query("
				INSERT INTO $wpdb->ad_block2tag
					( ad_block_id, ad_tag_id )
				VALUES
					( $ad_block_id, 'inline' )
				");

			$wpdb->query("
				INSERT INTO $wpdb->ad_distributions
					( ad_distribution_name, ad_distribution_description )
				VALUES
					( '" . addslashes($name) . "', '" . addslashes($ad['desc']) . "' )
				");

			$ad_distribution_id = $wpdb->insert_id;

			$wpdb->query("
				INSERT INTO $wpdb->ad_distribution2tag
					( ad_block_id, ad_distribution_id, ad_tag_id )
				VALUES
					( 0, $ad_distribution_id, 'header' ),
					( 0, $ad_distribution_id, 'above' ),
					( $ad_block_id, $ad_distribution_id, 'inline' ),
					( 0, $ad_distribution_id, 'below' ),
					( 0, $ad_distribution_id, 'footer' ),
					( 0, $ad_distribution_id, 'sidebar' )
				");
		}
	} # end import_adsense_deluxe()


	#
	# add2admin_menu()
	#

	function add2admin_menu()
	{
		if ( !function_exists('get_site_option') || is_site_admin() )
		{
			add_submenu_page(
				'themes.php',
				__('Ad&nbsp;Spaces'),
				__('Ad&nbsp;Spaces'),
				7,
				str_replace("\\", "/", basename(__FILE__)),
				array(&$this, 'display_admin_page')
				);
		}
	} # end add2admin_menu()


	#
	# init_admin()
	#

	function init_admin()
	{
		if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false )
		{
			ob_start(array(&$this, 'enhance_admin_menu'));
		}
	} # end init_admin()


	#
	# enhance_admin_menu()
	#

	function enhance_admin_menu($text)
	{
		if ( $_GET['page'] == 'sem-ad-space/sem-ad-space-admin.php' )
		{
			$text = str_replace(
					"?page=sem-ad-space-admin.php' class=\"current\">",
					"?page=sem-ad-space-admin.php' class=\"current\" onclick=\"display_ad_space_panel('default');\">",
					$text
					);
		}

		return $text;
	} # end enhance_admin_menu()


	#
	# display_admin_js()
	#

	function display_admin_js()
	{
		if ( strpos($_SERVER['REQUEST_URI'], 'sem-ad-space-admin.php') !== false )
		{
?><script type="text/javascript">
function setup_ad_block_editor(id)
{
    display_ad_space_panel('ad_block_editor');

	if ( id == 'new' )
    {
        document.getElementById('ad_block_editor').reset();
    	document.getElementById('ad_block_editor').ad_block_id.value = '';
	}
    else
    {
		document.getElementById('ad_block_editor').ad_block_id.value = document.getElementById('ad_block_id_' + id).value;
		document.getElementById('ad_block_editor').ad_block_name.value = document.getElementById('ad_block_name_' + id).value;
		document.getElementById('ad_block_editor').ad_block_description.value = document.getElementById('ad_block_description_' + id).value;
		document.getElementById('ad_block_editor').ad_block_code.value = document.getElementById('ad_block_code_' + id).value;

		var ad_tag_ids = document.getElementById('ad_tags_' + id).value.split(',');
		var is_default_ids = document.getElementById('is_default_ad_blocks_' + id).value.split(',');

		//alert(ad_tag_ids);
		//alert(is_default_ids);

		for ( var i = 0; i < document.getElementById('ad_block_editor').elements['ad_tags[]'].length; i++ )
		{
			document.getElementById('ad_block_editor').elements['ad_tags[]'][i].checked = false;
			tag_id = document.getElementById('ad_block_editor').elements['ad_tags[]'][i].id.replace('ad_tag_', '');
			document.getElementById('ad_block_editor').elements['is_default_ad_block[' + tag_id + ']'].checked = false;
		}

		for ( var i = 0; i < ad_tag_ids.length; i++ )
		{
			document.getElementById('ad_tag_' + ad_tag_ids[i]).checked = true;
		}

		for ( var i = 0; i < is_default_ids.length; i++ )
		{
			document.getElementById('is_default_ad_block_' + is_default_ids[i]).checked = true;
		}
	}
}


function setup_ad_distribution_editor(id)
{
    display_ad_space_panel('ad_distribution_editor');

	if ( id == 'new' )
    {
        document.getElementById('ad_distribution_editor').reset();
        document.getElementById('ad_distribution_editor').ad_distribution_id.value = '';
    }
    else
    {
		document.getElementById('ad_distribution_editor').ad_distribution_id.value = document.getElementById('ad_distribution_id_' + id).value;
		document.getElementById('ad_distribution_editor').ad_distribution_name.value = document.getElementById('ad_distribution_name_' + id).value;
		document.getElementById('ad_distribution_editor').ad_distribution_description.value = document.getElementById('ad_distribution_description_' + id).value;

		var is_default_ids = document.getElementById('is_default_ad_distribution_' + id).value.split(',');

		//alert(is_default_ids);

		for ( var i = 0; i < document.getElementById('ad_block_editor').elements['ad_tags[]'].length; i++ )
		{
			var template_tag_id = document.getElementById('ad_block_editor').elements['ad_tags[]'][i].value;
			if ( document.getElementById('ad_distribution2tag_' + id + '[' + template_tag_id + ']') )
			{
				document.getElementById('ad_distribution_tag_' + template_tag_id).value = document.getElementById('ad_distribution2tag_' + id + '[' + template_tag_id + ']').value;
				//alert('ad_distribution_tag_' + template_tag_id);
				//document.getElementById('ad_distribution_tag_' + template_tag_id).style.display = 'none';
				//alert(document.getElementById('ad_distribution_tag_' + template_tag_id).selectedIndex);
			}
			else
			{
			    document.getElementById('ad_distribution_tag_' + template_tag_id).value = '';
				//document.getElementById('ad_distribution_tag_' + template_tag_id).style.display = 'none';
			}
		}

		var contexts = new Array('home', 'post', 'page', 'misc');

		for ( var i = 0; i < contexts.length; i++ )
		{
			document.getElementById('is_default_ad_distribution_' + contexts[i]).checked = false;
		}

		for ( var i = 0; i < is_default_ids.length; i++ )
		{
			document.getElementById('is_default_ad_distribution_' + is_default_ids[i]).checked = true;
		}
	}
}

function display_ad_space_panel(id)
{
	if ( document.getElementById('ad_block_message') )
	{
		document.getElementById('ad_block_message').style.display = 'none';
	}

	switch( id )
	{
	case 'default':
	    document.getElementById('max_ad_blocks_editor').style.display = '';
	    document.getElementById('ad_distribution_list').style.display = '';
	    document.getElementById('ad_distribution_editor').style.display = 'none';
	    document.getElementById('default_ad_distribution_editor').style.display = 'none';
	    document.getElementById('ad_block_list').style.display = '';
	    document.getElementById('ad_block_editor').style.display = 'none';
	    document.getElementById('default_ad_block_editor').style.display = 'none';
	    break;
	case 'ad_distribution_editor':
	    document.getElementById('max_ad_blocks_editor').style.display = 'none';
	    document.getElementById('ad_distribution_list').style.display = 'none';
	    document.getElementById('ad_distribution_editor').style.display = '';
	    document.getElementById('default_ad_distribution_editor').style.display = 'none';
	    document.getElementById('ad_block_list').style.display = 'none';
	    document.getElementById('ad_block_editor').style.display = 'none';
	    document.getElementById('default_ad_block_editor').style.display = 'none';
		break;
	case 'default_ad_distribution_editor':
	    document.getElementById('max_ad_blocks_editor').style.display = 'none';
	    document.getElementById('ad_distribution_list').style.display = 'none';
	    document.getElementById('ad_distribution_editor').style.display = 'none';
	    document.getElementById('default_ad_distribution_editor').style.display = '';
	    document.getElementById('ad_block_list').style.display = 'none';
	    document.getElementById('ad_block_editor').style.display = 'none';
	    document.getElementById('default_ad_block_editor').style.display = 'none';
		break;
	case 'ad_block_editor':
	    document.getElementById('max_ad_blocks_editor').style.display = 'none';
	    document.getElementById('ad_distribution_list').style.display = 'none';
	    document.getElementById('ad_distribution_editor').style.display = 'none';
	    document.getElementById('default_ad_distribution_editor').style.display = 'none';
	    document.getElementById('ad_block_list').style.display = 'none';
	    document.getElementById('ad_block_editor').style.display = '';
	    document.getElementById('default_ad_block_editor').style.display = 'none';
	    break;
	case 'default_ad_block_editor':
	    document.getElementById('max_ad_blocks_editor').style.display = 'none';
	    document.getElementById('ad_distribution_list').style.display = 'none';
	    document.getElementById('ad_distribution_editor').style.display = 'none';
	    document.getElementById('default_ad_distribution_editor').style.display = 'none';
	    document.getElementById('ad_block_list').style.display = 'none';
	    document.getElementById('ad_block_editor').style.display = 'none';
	    document.getElementById('default_ad_block_editor').style.display = '';
	    break;
	}
}

function ad_distribution_defaults()
{
	return display_ad_space_panel('default_ad_distribution_editor');
}

function ad_block_defaults()
{
	return display_ad_space_panel('default_ad_block_editor');
}
</script>
<?php
		}
	} # end display_admin_js()


	#
	# update()
	#

	function update()
	{
		global $wpdb;

		#echo '<pre>';
		#var_dump($_POST);
		#echo '</pre>';

		switch ( $_POST['action'] )
		{
		#
		# case 'edit_max_ad_blocks'
		#
		case 'edit_max_ad_blocks':
			$this->params['max_ad_blocks'] = intval($_POST['max_ad_blocks']);
			break;
		#
		# case 'edit_sem_ad_block':
		#
		case 'edit_sem_ad_block':
			if ( !$_POST['ad_block_id'] )
			{
				$wpdb->query("
					INSERT INTO
						$wpdb->ad_blocks
						(
							ad_block_name,
							ad_block_description,
							ad_block_code
						)
					VALUES
						(
							'" . addslashes(trim($_POST['ad_block_name'])) . "',
							'" . addslashes($_POST['ad_block_description']) . "',
							'" . addslashes($_POST['ad_block_code']) . "'
						)
					");

				$_POST['ad_block_id'] = $wpdb->insert_id;
			}

			if ( $_POST['ad_block_id'] )
			{
				$wpdb->query("
					UPDATE $wpdb->ad_blocks
					SET
						ad_block_name = '" . addslashes(trim($_POST['ad_block_name'])) . "',
						ad_block_description = '" . addslashes($_POST['ad_block_description']) . "',
						ad_block_code = '" . addslashes($_POST['ad_block_code']) . "'
					WHERE
						ad_block_id = " . intval($_POST['ad_block_id']) . "
					");

				$wpdb->query("
					DELETE FROM $wpdb->ad_block2tag
					WHERE
						ad_block_id = " . intval($_POST['ad_block_id']) . "
					");

				$ad_tags = "";
				$ad_tag_ids = array();

				if ( isset($_POST['ad_tags']) )
				{
					foreach ( $_POST['ad_tags'] as $ad_tag_id )
					{
						$ad_tags .= ( $ad_tags ? ", " : "" ) . "'" . addslashes($ad_tag_id) . "'";

						$ad_tag_ids[] = $ad_tag_id;

						$wpdb->query("
							INSERT INTO
								$wpdb->ad_block2tag
								(
									ad_block_id,
									ad_tag_id
								)
							VALUES
								(
									" . intval($_POST['ad_block_id']) . ",
									'" . addslashes($ad_tag_id) . "'
								)
							");

						if ( isset($_POST['is_default_ad_block'][$ad_tag_id]) )
						{
							$this->params['default_ad_block'][$ad_tag_id] = $_POST['ad_block_id'];
						}
						elseif ( $this->params['default_ad_block'][$ad_tag_id] == $_POST['ad_block_id'] )
						{
							$this->params['default_ad_block'][$ad_tag_id] = 0;
						}
					}

					$wpdb->query("
						DELETE FROM $wpdb->ad_distribution2tag
						WHERE
							ad_block_id = " . intval($_POST['ad_block_id']) . "
							AND ad_tag_id NOT IN ($ad_tags)
						");
				}

				foreach ( $this->params['default_ad_block'] as $ad_tag_id => $ad_block_id )
				{
					if ( ( $ad_block_id == $_POST['ad_block_id'] )
						&& !in_array($ad_tag_id, $ad_tag_ids)
						)
					{
						$this->params['default_ad_block'][$ad_tag_id] = 0;
					}
				}
			}
			break;
		#
		# case 'delete_sem_ad_block':
		#
		case 'delete_sem_ad_block':
			if ( $_POST['ad_block_id'] )
			{
				$wpdb->query("
					DELETE FROM $wpdb->ad_distribution2tag
					WHERE
						ad_block_id = " . intval($_POST['ad_block_id']) . "
					");

				$wpdb->query("
					DELETE FROM $wpdb->ad_block2tag
					WHERE
						ad_block_id = " . intval($_POST['ad_block_id']) . "
					");

				$wpdb->query("
					DELETE FROM $wpdb->ad_blocks
					WHERE
						ad_block_id = " . intval($_POST['ad_block_id']) . "
					");

				foreach ( $this->params['default_ad_block'] as $ad_tag_id => $ad_block_id )
				{
					if ( $ad_block_id == intval($_POST['ad_block_id']) )
					{
						$this->params['default_ad_block'][$ad_tag_id] = 0;
					}
				}

				echo '<script>location.href = \'?page=sem-ad-space-admin.php\';</script>';
			}
			break;
		#
		# case 'edit_default_sem_ad_block':
		#
		case 'edit_default_sem_ad_block':
			$this->params['default_ad_block'] = $_POST['ad_distribution2tag'];
			break;
		#
		# case 'edit_sem_ad_distribution':
		#
		case 'edit_sem_ad_distribution':
			if ( !$_POST['ad_distribution_id'] )
			{
				$wpdb->query("
					INSERT INTO
						$wpdb->ad_distributions
						(
							ad_distribution_name,
							ad_distribution_description
						)
					VALUES
						(
							'" . addslashes(trim($_POST['ad_distribution_name'])) . "',
							'" . addslashes($_POST['ad_distribution_description']) . "'
						)
					");

				$_POST['ad_distribution_id'] = $wpdb->insert_id;
			}

			if ( $_POST['ad_distribution_id'] )
			{
				$wpdb->query("
					UPDATE $wpdb->ad_distributions
					SET
						ad_distribution_name = '" . addslashes(trim($_POST['ad_distribution_name'])) . "',
						ad_distribution_description = '" . addslashes($_POST['ad_distribution_description']) . "'
					WHERE
						ad_distribution_id = " . intval($_POST['ad_distribution_id']) . "
					");

				$wpdb->query("
					DELETE FROM $wpdb->ad_distribution2tag
					WHERE
						ad_distribution_id = " . intval($_POST['ad_distribution_id']) . "
					");

				if ( isset($_POST['ad_distribution2tag']) )
				{
					foreach ( $_POST['ad_distribution2tag'] as $ad_tag_id => $ad_block_id )
					{
						switch ( $ad_block_id )
						{
						case 'default':
						case '':
							$ad_block_id = null;
							break;
						case 'none':
							$ad_block_id = 0;
							break;
						case 'none':
							$ad_block_id = -1;
							break;
						}

						if ( isset($ad_block_id) )
						{
							$wpdb->query("
								INSERT INTO
									$wpdb->ad_distribution2tag
									(
										ad_block_id,
										ad_tag_id,
										ad_distribution_id
									)
								VALUES
									(
										" . intval($ad_block_id) . ",
										'" . addslashes($ad_tag_id) . "',
										" . intval($_POST['ad_distribution_id']) . "
									)
								");
						}
					}
				}

				foreach ( $this->params['default_ad_distribution'] as $context_id => $ad_distribution_id )
				{
					if ( isset($_POST['is_default_ad_distribution'][$context_id]) )
					{
						$this->params['default_ad_distribution'][$context_id] = intval($_POST['ad_distribution_id']);
					}
					elseif ( $ad_distribution_id == $_POST['ad_distribution_id'] )
					{
						$this->params['default_ad_distribution'][$context_id] = 0;
					}
				}
			}
			break;
		#
		# case 'delete_sem_ad_distribution':
		#
		case 'delete_sem_ad_distribution':
			if ( $_POST['ad_distribution_id'] )
			{
				$wpdb->query("
					DELETE FROM $wpdb->ad_distribution2tag
					WHERE
						ad_distribution_id = " . intval($_POST['ad_distribution_id']) . "
					");

				$wpdb->query("
					DELETE FROM $wpdb->ad_distributions
					WHERE
						ad_distribution_id = " . intval($_POST['ad_distribution_id']) . "
					");

				foreach ( $this->params['default_ad_distribution'] as $key => $ad_distribution_id )
				{
					if ( $ad_distribution_id == $_POST['ad_distribution_id'] )
					{
						$this->params['default_ad_distribution'][$key] = 0;
					}
				}

				echo '<script>location.href = \'?page=sem-ad-space-admin.php\';</script>';
			}
			break;
		#
		# case 'edit_default_sem_ad_distribution':
		#
		case 'edit_default_sem_ad_distribution':
			$this->params['default_ad_distribution'] = $_POST['default_ad_distribution'];
			break;
		}

		#echo '<pre>';
		#var_dump($this->params);
		#echo '</pre>';

		$this->params['default_ad_block_name'] = null;

		if ( $this->params['default_ad_block']['inline'] )
		{
			$this->params['default_ad_block_name'] = $wpdb->get_var("
				SELECT
					ad_blocks.ad_block_name
				FROM
					$wpdb->ad_blocks as ad_blocks
				WHERE
					ad_blocks.ad_block_id = " . intval($this->params['default_ad_block']['inline'])
				);
		}

		if ( !isset($this->params['default_ad_block_name']) )
		{
			$this->params['default_ad_block_name'] = false;
		}

		if ( function_exists('get_site_option') )
		{
			update_site_option('sem_ad_space_params', $this->params);
		}
		else
		{
			update_option('sem_ad_space_params', $this->params);
		}
	} # end update()


	#
	# update_post()
	#

	function update_post()
	{
		global $wpdb;
		if ( isset($_POST['post_ID'])
			&& isset($_POST['user_ID'])
			&& user_can_edit_post($_POST['user_ID'], $_POST['post_ID'])
			)
		{
			$wpdb->query("
				DELETE FROM $wpdb->ad_distribution2post
				WHERE post_id = " . intval($_POST['post_ID'])
			);
			if ( $_POST['sem_ad_distribution'] !== '' )
			{
				$wpdb->query("
					INSERT INTO $wpdb->ad_distribution2post ( ad_distribution_id, post_id )
					VALUES ( " . intval($_POST['sem_ad_distribution']) . ", " . intval($_POST['post_ID']) . " )
					");
			}
		}
	} # end update_post()


	#
	# display_admin_page()
	#

	function display_admin_page()
	{
		global $wpdb;

		# $this->tags = $wpdb->get_results("SELECT * FROM $wpdb->ad_tags");

		# Process updates, if any

		#echo '<pre>';
		#var_dump($this->params['default_ad_block']);
		#echo '</pre>';

		if ( isset($_GET['action']) )
		{
			switch ( $_GET['action'] )
			{
			case 'delete_sem_ad_block':
				$_POST['action'] = $_GET['action'];
				$_POST['ad_block_id'] = $_GET['ad_block_id'];
				break;
			case 'delete_sem_ad_distribution':
				$_POST['action'] = $_GET['action'];
				$_POST['ad_distribution_id'] = $_GET['ad_distribution_id'];
				break;
			default:
				break;
			}
		}

		if ( isset($_POST['action']) )
		{
			switch( $_POST['action'] )
			{
			case 'edit_max_ad_blocks':
			case 'edit_sem_ad_block':
			case 'delete_sem_ad_block':
			case 'edit_default_sem_ad_block':
			case 'edit_sem_ad_distribution':
			case 'delete_sem_ad_distribution':
			case 'edit_default_sem_ad_distribution':
				$this->update();

				echo "<div class=\"updated\" id=\"ad_block_message\">\n"
					. "<p>"
						. "<strong>"
						. __('Options saved.', 'sem-ad-space')
						. "</strong>"
					. "</p>\n"
					. "</div>\n";
				break;
			}
		}

		# Display admin page

		echo "<div class=\"wrap\">\n"
			. "<h2>" . __('Ad Spaces Options', 'sem-ad-space') . "</h2>\n"
			. "<p>" . __('<b>&raquo;</b> <a href="http://www.semiologic.com/software/ad-space/">Ad Spaces Tutorial And Documentation</a>', 'sem-ad-space') . "</p>\n";

		$ad_blocks = $wpdb->get_results("
			SELECT
				*
			FROM
				$wpdb->ad_blocks
			ORDER BY
				ad_block_name, ad_block_id DESC
			");

		$default_ad_blocks = array(
			-1 => __('Random', 'sem-ad-space'),
			0 => __('None', 'sem-ad-space')
			);
		$default_ad_block_ids = "-1,0";

		if ( isset($ad_blocks) )
		{
			foreach ( $ad_blocks as $ad_block )
			{
				if ( in_array($ad_block->ad_block_id, $this->params['default_ad_block']) )
				{
					$default_ad_blocks[$ad_block->ad_block_id] = str_replace(array("<", ">", "\"", "\r"), array("&lt;", "&gt;", "&quot;", ""), stripslashes($ad_block->ad_block_name));
					$default_ad_block_ids .= ( $default_ad_block_ids ? "," : "" ) . $ad_block->ad_block_id;
				}
			}
		}

		echo "<input type=\"hidden\" id=\"default_ad_block_ids\" value=\"" . $default_ad_block_ids . "\" />\n";

		$ad_distributions = $wpdb->get_results("
			SELECT
				*
			FROM
				$wpdb->ad_distributions
			ORDER BY
				ad_distribution_name, ad_distribution_id DESC
			");

		$default_ad_distributions = array(
			-1 => __('Random', 'sem-ad-space'),
			0 => __('None', 'sem-ad-space')
			);

		if ( isset($ad_distributions) )
		{
			foreach ( $ad_distributions as $ad_distribution )
			{
				if ( in_array($ad_distribution->ad_distribution_id, $this->params['default_ad_distribution']) )
				{
					$default_ad_distributions[$ad_distribution->ad_distribution_id] = str_replace(array("<", ">", "\"", "\r"), array("&lt;", "&gt;", "&quot;", ""), stripslashes($ad_distribution->ad_distribution_name));
				}
			}
		}





		# Ad Units

		echo "<fieldset class=\"options\" id=\"ad_block_list\" style=\"margin-bottom: 2em;\">\n"
			. "<legend><strong>" . __('Ad Units', 'sem-ad-space')
					. " ("
					. "<a href=\"javascript:;\""
						. "onclick=\"setup_ad_block_editor('new');\""
					. " style=\"text-decoration: underline;\""
						. ">"
						. __('add new', 'sem-ad-space')
						. "</a>"
					. " | "
					. "<a href=\"javascript:;\""
						. "onclick=\"display_ad_space_panel('default_ad_block_editor');\""
					. " style=\"text-decoration: underline;\""
						. ">"
						. __('defaults', 'sem-ad-space')
						. "</a>"
					. ")"
				. "</strong></legend>\n";

		echo  '<p>' . __('Insert &lt;!--adunit<strong>#name</strong>--&gt; where you\'d like the ad unit with that name to appear in your entries\' content.') . '</p>'
		. '<p>' . __('If you insert an ad unit with no name, <i>e.g.</i> &lt;!--adunit--&gt;, Ad Spaces will insert the default inline ad unit.', 'sem-ad-space') . "</p>\n";

		echo "<table cellspacing=\"2\" cellpadding=\"5\" width=\"100%\">\n"
			. "<tr>\n"
				. "<th width=\"40\">" . __('id', 'sem-ad-space') . "</th>"
				. "<th width=\"220\">" . __('Name', 'sem-ad-space') . "</th>"
				. "<th>" . __('Description', 'sem-ad-space') . "</th>"
				. "<td width=\"60\" align=\"center\">&nbsp;</td>"
				. "<td width=\"60\" align=\"center\">&nbsp;</td>"
			. "</tr>\n";

		if ( isset($ad_blocks) )
		{
			$i = 0;

			foreach ( $ad_blocks as $ad_block )
			{
				$ad_tags = $wpdb->get_col("
					SELECT
						ad_block2tag.ad_tag_id
					FROM
						$wpdb->ad_block2tag as ad_block2tag
					WHERE
						ad_block2tag.ad_block_id = $ad_block->ad_block_id
					");

				$cur_tags = "";
				$cur_defaults = "";

				if ( isset($ad_tags) )
				{
					foreach ( $ad_tags as $tag_id )
					{
						 $cur_tags .= ( $cur_tags ? "," : "" ) . $tag_id;

						 if ( $this->params['default_ad_block'][$tag_id] == $ad_block->ad_block_id )
						 {
							$cur_defaults .= ( $cur_defaults ? "," : "" ) . $tag_id;
						 }
					}
				}

				echo "<tr"
					. ( ( ++$i % 2 )
						? " class=\"alternate\""
						: ""
						)
					. ">\n"
					. "<td align=\"center\">\n"
						. $ad_block->ad_block_id
						. "</td>\n"
					. "<td>\n"
						. stripslashes($ad_block->ad_block_name)
						. "</td>\n"
					. "<td valign=\"top\">\n"
						. stripslashes($ad_block->ad_block_description)
						. "</td>\n"
					. "<td align=\"center\">\n"
						. "<input type=\"hidden\" id=\""
								. "ad_block_id_" . $ad_block->ad_block_id
								. "\""
							. " value=\""
								. $ad_block->ad_block_id
								. "\""
							. " />"
						. "<input type=\"hidden\" id=\""
								. "ad_block_name_" . $ad_block->ad_block_id
								. "\""
							. " value=\""
								. str_replace(array("<", ">", "\"", "\r"), array("&lt;", "&gt;", "&quot;", ""), stripslashes($ad_block->ad_block_name))
								. "\""
							. " />"
						. "<input type=\"hidden\" id=\""
								. "ad_block_description_" . $ad_block->ad_block_id
								. "\""
							. " value=\""
								. str_replace(array("<", ">", "\"", "\r"), array("&lt;", "&gt;", "&quot;", ""), stripslashes($ad_block->ad_block_description))
								. "\""
							. " />"
						. "<input type=\"hidden\" id=\""
								. "ad_block_code_" . $ad_block->ad_block_id
								. "\""
							. " value=\""
								. str_replace(array("<", ">", "\"", "\r"), array("&lt;", "&gt;", "&quot;", ""), stripslashes($ad_block->ad_block_code))
								. "\""
							. " />"
						. "<input type=\"hidden\" id=\""
								. "ad_tags_" . $ad_block->ad_block_id
								. "\""
							. " value=\""
								. $cur_tags
								. "\""
							. " />"
						. "<input type=\"hidden\" id=\""
								. "is_default_ad_blocks_" . $ad_block->ad_block_id
								. "\""
							. " value=\""
								. $cur_defaults
								. "\""
							. " />"
						. "<a href=\"javascript:;\""
								. "onclick=\"setup_ad_block_editor(" . $ad_block->ad_block_id . ")"
								. "\""
								. ">" . __('Edit', 'sem-ad-space')
							. "</a>"
						. "</td>\n"
					. "<td align=\"center\">\n"
						. "<a href=\"?page=sem-ad-space-admin.php&amp;action=delete_sem_ad_block&amp;ad_block_id=" . $ad_block->ad_block_id . "\""
						. " onclick=\"return confirm('"
							. __('Please click OK to confirm Delete')
							. "');\""
						. ">" . __('Delete', 'sem-ad-space')
						. "</a>"
						. "</td>\n"
					. "</tr>\n";

			}
		}

		echo "</table>\n";

		echo "</fieldset>\n";




		# ad unit editor

		echo "<form method=\"post\" id=\"ad_block_editor\" action=\"\" style=\"display: none;\">\n"
			. "<input type=\"hidden\" name=\"action\" value=\"edit_sem_ad_block\" />\n"
			. "<input type=\"hidden\" name=\"ad_block_id\" value=\"\" />\n";

		echo "<fieldset class=\"options\" style=\"margin-bottom: 2em;\">\n"
			. "<legend>" . __('Edit Ad Unit', 'sem-ad-space')
				. "</legend>\n";

		echo "<table width=\"740\" cellspacing=\"2\" cellpadding=\"5\" class=\"editform\">\n"
			. "<tr>\n"
			. "<th scope=\"row\" width=\"180\">"
				. "<label for=\"ad_block_name\">"
					. __('Name:', 'sem-ad-space')
					. "</label>"
				. "</th>\n"
			. "<td>"
				. "<input type=\"text\" style=\"width: 520px;\""
				. " id=\"ad_block_name\" name=\"ad_block_name\""
				. ">"
				. "<td>\n"
			. "</tr>\n"
			. "<tr valign=\"top\">\n"
			. "<th scope=\"row\">"
				. "<label for=\"ad_block_description\">"
					. __('Description:', 'sem-ad-space')
					. "</label>"
				. "</th>\n"
			. "<td>"
				. "<textarea style=\"width: 520px; height: 60px;\""
				. " id=\"ad_block_description\" name=\"ad_block_description\""
				. ">"
				. "</textarea>"
				. "<td>\n"
			. "</tr>\n"
			. "<tr valign=\"top\">\n"
			. "<th scope=\"row\">"
				. "<label for=\"ad_block_code\">"
					. __('Code:', 'sem-ad-space')
					. "</label>"
				. "</th>\n"
			. "<td>"
				. "<textarea style=\"width: 520px; height: 160px;\""
				. " id=\"ad_block_code\" name=\"ad_block_code\""
				. "><div class=\"ad\">\n" . __('Place your Ad Unit code in this box. Remove the div tag if you do not want your ad to have a ghostwhite background.') . "\n</div>"
				. "</textarea>"
				. "<td>\n"
			. "</tr>\n"
			. "<tr valign=\"top\">\n"
			. "<th scope=\"row\">"
				. __('Locations:', 'sem-ad-space')
				. "</th>\n"
			. "<td>"
			. "<table>\n";
#echo '<pre>';
#var_dump($_POST);
#echo '</pre>';
		foreach ( $this->tags as $ad_tag_id => $ad_tag_name )
		{
			echo "<tr>\n"
				. "<td>\n"
				. "<label for=\"is_default_ad_block_" . $ad_tag_id . "\">"
				. "<input type=\"checkbox\""
					. " id=\"is_default_ad_block_" . $ad_tag_id . "\""
					. " name=\"is_default_ad_block[" . $ad_tag_id . "]\""
					. " />"
					. " " . __('Default', 'sem-ad-space')
				. "</label>"
				. "</td>\n"
				. "<td>\n"
				. "<label for=\"ad_tag_" . $ad_tag_id . "\">"
				. "<input type=\"checkbox\""
					. " id=\"ad_tag_" . $ad_tag_id . "\""
					. " name=\"ad_tags[]\""
					. " value=\"" . $ad_tag_id . "\""
					. ( ( $ad_tag_id == 'inline' )
						? " checked"
						: ""
						)
					. " /> "
				. __(stripslashes($ad_tag_name), 'sem-ad-spaces')
				. "</label>"
				. "</td>"
				. "</tr>\n";
		}

		echo "</table>\n"
			. "</td>\n"
			. "</tr>\n"
			. "<tr>\n"
				. "<td colspan=\"2\">"
				. "<p class=\"submit\">"
				. "<input type=\"submit\""
					. " value=\"" . __('Save Ad Unit', 'sem-ad-space') . "\""
					. " />"
				. "<input type=\"button\""
					. " onclick=\"display_ad_space_panel('default'); return true;\""
					. " value=\"" . __('Cancel', 'sem-ad-space') . "\""
					. " />"
				. "</p>\n"
				. "</td>"
			. "</tr>\n"
			. "</table>\n";

		echo "</fieldset>\n"
			. "</form>\n";




		# default ad units

		echo "<form method=\"post\" id=\"default_ad_block_editor\" action=\"\" style=\"display: none;\">\n"
			. "<input type=\"hidden\" name=\"action\" value=\"edit_default_sem_ad_block\" />\n";

		echo "<fieldset class=\"options\" style=\"margin-bottom: 2em;\">\n"
			. "<legend>" . __('Default Ad Units', 'sem-ad-space')
				. "</legend>\n";

		#echo '<pre>';
		#var_dump($this->params['default_ad_block']);
		#echo '</pre>';

		echo "<table width=\"750\" cellspacing=\"2\" cellpadding=\"5\" class=\"editform\">\n";

		foreach ( $this->tags as $ad_tag_id => $ad_tag_name )
		{
			$tag_ad_blocks = $wpdb->get_results("
				SELECT
					ad_blocks.ad_block_id, ad_blocks.ad_block_name
				FROM
					$wpdb->ad_blocks as ad_blocks
				INNER JOIN
					$wpdb->ad_block2tag as ad_block2tag
						ON ad_block2tag.ad_block_id = ad_blocks.ad_block_id
				WHERE
					ad_block2tag.ad_tag_id = '" . addslashes($ad_tag_id) . "'
				ORDER BY
					ad_blocks.ad_block_name
				");

			echo "<tr>\n"
				. "<th scope=\"row\" width=\"180\">"
				. "<label for=\"default_ad_distribution_tag_" . $ad_tag_id . "\">"
				. __(stripslashes($ad_tag_name), 'sem-ad-space')
				. "</label>"
				. "</th>\n"
				. "<td>"
					. "<select id=\"default_ad_distribution_tag_" . $ad_tag_id . "\" name=\"ad_distribution2tag[" . $ad_tag_id . "]\">"
					. "<option value=\"0\""
						. ( ( $this->params['default_ad_block'][$ad_tag_id] == 0 )
							? " selected"
							: ""
							)
						. ">"
						. __('None', 'sem-ad-space')
						. "</option>\n"
					. "<option value=\"-1\""
						. ( ( $this->params['default_ad_block'][$ad_tag_id] == -1 )
							? " selected"
							: ""
							)
						. ">"
						. __('Random', 'sem-ad-space')
						. "</option>\n";

			if ( isset($tag_ad_blocks) )
			{
				foreach ( $tag_ad_blocks as $tag_ad_block )
				{
					echo "<option value=\"" . $tag_ad_block->ad_block_id . "\""
						. ( ( $this->params['default_ad_block'][$ad_tag_id] == $tag_ad_block->ad_block_id )
							? " selected"
							: ""
							)
						. ">"
						. stripslashes($tag_ad_block->ad_block_name)
						. "</option>\n";
				}
			}

			echo "</select>\n"
				. "<td>\n"
				. "</tr>\n";
		}

		echo "<tr>\n"
			. "<td colspan=\"2\">"
			. "<p class=\"submit\">"
			. "<input type=\"submit\""
				. " value=\"" . __('Save Default Ad Units', 'sem-ad-space') . "\""
				. " />"
			. "<input type=\"button\""
				. " onclick=\"display_ad_space_panel('default'); return true;\""
				. " value=\"" . __('Cancel', 'sem-ad-space') . "\""
				. " />"
			. "</p>\n"
			. "</td>"
		. "</tr>\n"
		. "</table>\n";

		echo "</fieldset>\n"
		. "</form>\n";




		# Ad Distributions

		echo "<fieldset class=\"options\" id=\"ad_distribution_list\" style=\"margin-bottom: 2em;\">\n"
			. "<legend><strong>" . __('Ad Distributions', 'sem-ad-space')
				. " ("
				. "<a href=\"javascript:;\""
					. "onclick=\"setup_ad_distribution_editor('new');\""
					. " style=\"text-decoration: underline;\""
					. ">"
					. __('add new', 'sem-ad-space')
					. "</a>"
				. " | "
				. "<a href=\"javascript:;\""
					. "onclick=\"display_ad_space_panel('default_ad_distribution_editor');\""
					. " style=\"text-decoration: underline;\""
					. ">"
					. __('defaults', 'sem-ad-space')
					. "</a>"
				. ")"
				. "</strong></legend>\n";

		echo "<p>" . __('Ad distributions let you define which canvas-specific ads go where and when without editing your template.', 'sem-ad-space') . "</p>\n";

		echo "<table cellspacing=\"2\" cellpadding=\"5\" width=\"100%\">\n"
			. "<tr>\n"
				. "<th width=\"40\">" . __('id', 'sem-ad-space') . "</th>"
				. "<th width=\"220\">" . __('Name', 'sem-ad-space') . "</th>"
				. "<th>" . __('Description', 'sem-ad-space') . "</th>"
				. "<td width=\"60\" align=\"center\">&nbsp;</td>"
				. "<td width=\"60\" align=\"center\">&nbsp;</td>"
			. "</tr>\n";

		if ( isset($ad_distributions) )
		{
			$i = 0;

			foreach ( $ad_distributions as $ad_distribution )
			{
				$cur_tags = "";
				$cur_defaults = "";

				$ad_distribution_tags = $wpdb->get_results("
					SELECT
						*
					FROM
						$wpdb->ad_distribution2tag
					WHERE
						ad_distribution_id = $ad_distribution->ad_distribution_id
					ORDER BY
						ad_tag_id
					");

	#echo '<pre>';
	#var_dump($ad_distribution_tags);
	#echo '</pre>';

				if ( isset($ad_distribution_tags) )
				{
					foreach ( $ad_distribution_tags as $tag )
					{
						 $cur_tags .= "<input type=\"hidden\" id=\""
								. "ad_distribution2tag_" . $ad_distribution->ad_distribution_id
								. "[" . $tag->ad_tag_id . "]"
								. "\""
							. " value=\""
								. $tag->ad_block_id
								. "\""
							. " />";
					}
				}

	#echo '<pre>';
	#var_dump(htmlspecialchars($cur_tags, ENT_QUOTES));
	#echo '</pre>';

				foreach ( $this->params['default_ad_distribution'] as $context_id => $ad_distribution_id )
				{
					if ( $ad_distribution->ad_distribution_id == $ad_distribution_id )
					{
						$cur_defaults .= ( $cur_defaults ? "," : "" ) . $context_id;
					}
				}


				echo "<tr"
					. ( ( ++$i % 2 )
						? " class=\"alternate\""
						: ""
						)
					. ">\n"
					. "<td align=\"center\">\n"
						. $ad_distribution->ad_distribution_id
						. "</td>\n"
					. "<td>\n"
						. stripslashes($ad_distribution->ad_distribution_name)
						. "</td>\n"
					. "<td valign=\"top\">\n"
						. stripslashes($ad_distribution->ad_distribution_description)
						. "</td>\n"
					. "<td align=\"center\">\n"
						. "<input type=\"hidden\" id=\""
								. "ad_distribution_id_" . $ad_distribution->ad_distribution_id
								. "\""
							. " value=\""
								. $ad_distribution->ad_distribution_id
								. "\""
							. " />"
						. "<input type=\"hidden\" id=\""
								. "ad_distribution_name_" . $ad_distribution->ad_distribution_id
								. "\""
							. " value=\""
								. str_replace(array("<", ">", "\"", "\r"), array("&lt;", "&gt;", "&quot;", ""), stripslashes($ad_distribution->ad_distribution_name))
								. "\""
							. " />"
						. "<input type=\"hidden\" id=\""
								. "ad_distribution_description_" . $ad_distribution->ad_distribution_id
								. "\""
							. " value=\""
								. str_replace(array("<", ">", "\"", "\r"), array("&lt;", "&gt;", "&quot;", ""), stripslashes($ad_distribution->ad_distribution_description))
								. "\""
							. " />"
						. $cur_tags
						. "<input type=\"hidden\" id=\""
								. "is_default_ad_distribution_" . $ad_distribution->ad_distribution_id
								. "\""
							. " value=\""
								. $cur_defaults
								. "\""
							. " />"
						. "<a href=\"javascript:;\""
									. "onclick=\"setup_ad_distribution_editor(" . $ad_distribution->ad_distribution_id . ")"
									. "\""
								. ">" . __('Edit', 'sem-ad-space')
							. "</a>"
						. "</td>\n"
					. "<td align=\"center\">\n"
						. "<a href=\"?page=sem-ad-space-admin.php&amp;action=delete_sem_ad_distribution&amp;ad_distribution_id=" . $ad_distribution->ad_distribution_id . "\""
						. " onclick=\"return confirm('"
							. __('Please click OK to confirm Delete')
							. "');\""
						. ">" . __('Delete', 'sem-ad-space')
						. "</a>"
						. "</td>\n"
					. "</tr>\n";
			}
		}

		echo "</table>\n";

		echo "</fieldset>\n";




		# Ad Distribution editor

		echo "<form method=\"post\" id=\"ad_distribution_editor\" action=\"\" style=\"display: none;\">\n"
			. "<input type=\"hidden\" name=\"action\" value=\"edit_sem_ad_distribution\" />\n"
			. "<input type=\"hidden\" name=\"ad_distribution_id\" value=\"\" />\n";

		echo "<fieldset class=\"options\" style=\"margin-bottom: 2em;\">\n"
			. "<legend>" . __('Edit Ad Distribution', 'sem-ad-space')
				. "</legend>\n";

		#echo '<pre>';
		#var_dump($this->params['default_ad_distribution']);
		#echo '</pre>';

		echo "<table width=\"740\" cellspacing=\"2\" cellpadding=\"5\" class=\"editform\">\n"
			. "<tr>\n"
			. "<th scope=\"row\" width=\"180\">"
				. "<label for=\"ad_distribution_name\">"
					. __('Name:', 'sem-ad-space')
					. "</label>"
				. "</th>\n"
			. "<td>"
				. "<input type=\"text\" style=\"width: 520px;\""
				. " id=\"ad_distribution_name\" name=\"ad_distribution_name\""
				. ">"
				. "<td>\n"
			. "</tr>\n"
			. "<tr valign=\"top\">\n"
			. "<th scope=\"row\">"
				. "<label for=\"ad_distribution_description\">"
					. __('Description:', 'sem-ad-space')
					. "</label>"
				. "</th>\n"
			. "<td>"
				. "<textarea style=\"width: 520px; height: 60px;\""
				. " id=\"ad_distribution_description\" name=\"ad_distribution_description\""
				. ">"
				. "</textarea>"
				. "<td>\n"
			. "</tr>\n";

			foreach ( $this->tags as $ad_tag_id => $ad_tag_name)
			{
				$tag_ad_blocks = $wpdb->get_results("
					SELECT
						ad_blocks.ad_block_id, ad_blocks.ad_block_name
					FROM
						$wpdb->ad_blocks as ad_blocks
					INNER JOIN
						$wpdb->ad_block2tag as ad_block2tag
							ON ad_block2tag.ad_block_id = ad_blocks.ad_block_id
					WHERE
						ad_block2tag.ad_tag_id = '" . addslashes($ad_tag_id) . "'
					ORDER BY
						ad_blocks.ad_block_name
					");
#echo '<pre>';
#var_dump($tag_ad_blocks);
#echo '</pre>';
				echo "<tr>\n"
					. "<th scope=\"row\">"
					. "<label for=\"ad_distribution_tag_" . $ad_tag_id . "\">"
					. __(stripslashes($ad_tag_name), 'sem-ad-space')
					. "</label>"
					. "</th>\n"
					. "<td>"
						. "<select id=\"ad_distribution_tag_" . $ad_tag_id . "\" name=\"ad_distribution2tag[" . $ad_tag_id . "]\">"
						. "<option value=\"\""
							. ">"
							. __('Default', 'sem-ad-space')
								. " ("
								. $default_ad_blocks[intval($this->params['default_ad_block'][$ad_tag_id])]
								. ")"
							. "</option>\n"
						. "<option value=\"0\""
							. ">"
							. __('None', 'sem-ad-space')
							. "</option>\n"
						. "<option value=\"-1\""
							. ">"
							. __('Random', 'sem-ad-space')
							. "</option>\n";

				if ( isset($tag_ad_blocks) )
				{
					foreach ( $tag_ad_blocks as $tag_ad_block )
					{
						echo "<option value=\"" . $tag_ad_block->ad_block_id . "\""
							. ">"
							. stripslashes($tag_ad_block->ad_block_name)
							. "</option>\n";
					}
				}

				echo "</select>\n"
					. "<td>\n"
					. "</tr>\n";
			}

			echo "<tr>\n"
				. "<th scope=\"row\" valign=\"top\">"
				. __('Default for:', 'sem-ad-space')
				. "</th>\n"
				. "<td>\n"
				. "<table>\n";

			foreach ( array_keys($this->contexts) as $context_id )
			{
				echo "<tr>\n"
					. "<td>\n"
					. "<label for=\"is_default_ad_distribution_" . $context_id . "\">"
					. "<input type=\"checkbox\""
						. " id=\"is_default_ad_distribution_" . $context_id . "\""
						. " name=\"is_default_ad_distribution[" . $context_id . "]\""
						. " />"
						. " " . __($this->contexts[$context_id], 'sem-ad-space')
					. "</label>"
					. "</td>\n"
					. "</tr>\n";
			}

			echo "</table>\n"
				. "</td>\n"
				. "</tr>\n";

			echo "<tr>\n"
				. "<td colspan=\"2\">"
				. "<p class=\"submit\">"
				. "<input type=\"submit\""
					. " value=\"" . __('Save Ad Distribution', 'sem-ad-space') . "\""
					. " />"
				. "<input type=\"button\""
					. " onclick=\"display_ad_space_panel('default'); return true;\""
					. " value=\"" . __('Cancel', 'sem-ad-space') . "\""
					. " />"
				. "</p>\n"
				. "</td>"
			. "</tr>\n"
			. "</table>\n";

		echo "</fieldset>\n"
			. "</form>\n";




		# default Ad Distributions

		echo "<form method=\"post\" id=\"default_ad_distribution_editor\" action=\"\" style=\"display: none;\">\n"
			. "<input type=\"hidden\" name=\"action\" value=\"edit_default_sem_ad_distribution\" />\n";

		echo "<fieldset class=\"options\" style=\"margin-bottom: 2em;\">\n"
			. "<legend>" . __('Default Ad Distributions', 'sem-ad-space')
				. "</legend>\n";

		echo "<table width=\"740\" cellspacing=\"2\" cellpadding=\"5\" class=\"editform\">\n";

			foreach ( $this->contexts as $key => $name )
			{
				echo "<tr>\n"
					. "<th scope=\"row\" width=\"180\">"
					. "<label for=\"default_ad_distribution_" . $key . "\">"
					. $name
					. "</label>"
					. "</th>\n"
					. "<td>"
						. "<select id=\"default_ad_distribution_" . $key . "\" name=\"default_ad_distribution[" . $key . "]\">"
						. "<option value=\"0\""
							. ( ( $this->params['default_ad_distribution'][$key] == 0 )
								? " selected"
								: ""
								)
							. ">"
							. __('None', 'sem-ad-space')
							. "</option>\n"
						. "<option value=\"-1\""
							. ( ( $this->params['default_ad_distribution'][$key] == -1 )
								? " selected"
								: ""
								)
							. ">"
							. __('Random', 'sem-ad-space')
							. "</option>\n";

				if ( isset($ad_distributions) )
				{
					foreach ( $ad_distributions as $tag_ad_distribution )
					{
						echo "<option value=\"" . $tag_ad_distribution->ad_distribution_id . "\""
							. ( ( $this->params['default_ad_distribution'][$key] == $tag_ad_distribution->ad_distribution_id )
								? " selected"
								: ""
								)
							. ">"
							. stripslashes($tag_ad_distribution->ad_distribution_name)
							. "</option>\n";
					}
				}

				echo "</select>\n"
					. "<td>\n"
					. "</tr>\n";
			}

			echo "<tr>\n"
				. "<td colspan=\"2\">"
				. "<p class=\"submit\">"
				. "<input type=\"submit\""
					. " value=\"" . __('Save Default Ad Distributions', 'sem-ad-space') . "\""
					. " />"
				. "<input type=\"button\""
					. " onclick=\"display_ad_space_panel('default'); return true;\""
					. " value=\"" . __('Cancel', 'sem-ad-space') . "\""
					. " />"
				. "</p>\n"
				. "</td>"
			. "</tr>\n"
			. "</table>\n";

			echo "</fieldset>\n"
			. "</form>\n";




		# Number of Ads

		echo "<form method=\"post\" id=\"max_ad_blocks_editor\" action=\"\">\n"
			. "<input type=\"hidden\" name=\"action\" value=\"edit_max_ad_blocks\" />\n";


		echo '<fieldset class="options" id="max_number_of_ads" style=\"margin-bottom: 2em;\">'
			. '<legend><strong>' . __('Maximum number of ad units') . '</strong></legend>';

		echo '<p><label for="max_ad_blocks">'
			. __('Maximum number of ad units')
			. '</label>'
			. '<br />'
			. '<input type="text"'
				. ' id="max_ad_blocks"'
				. ' name="max_ad_blocks"'
				. ' value="'
					. ( ( isset($this->params['max_ad_blocks']) )
						? ( $this->params['max_ad_blocks']
							? intval($this->params['max_ad_blocks'])
							: ''
							)
						: 3
						)
					. '"'
				. ' />'
			. '</p>';

		echo '<p>'
			. __('Some ad providers specifically disallow you to display more than a certain number of ad units per page. In particular, Google\'s Acceptable Use Policy forbids to use more than 3 ads. Leave this field blank to display an unlimited number of ad units.')
			. '</p>';

		echo '<p class="submit">'
		. '<input type="submit"'
			. ' value="' . __('Update Options') . '"'
			. ' />'
		. '</p>';

		echo '</fieldset>'
			. '</form>';




		echo "</div>\n";
	} # end display_admin_page()


	#
	# display_post_ad_selector()
	#

	function display_post_ad_selector()
	{
		return $this->display_ad_selector('post');
	} # end display_post_ad_selector()


	#
	# display_page_ad_selector()
	#

	function display_page_ad_selector()
	{
		return $this->display_ad_selector('page');
	} # end display_page_ad_selector()


	#
	# display_ad_selector()
	#

	function display_ad_selector($ad_tag_id)
	{
		if ( function_exists('get_site_option') && !is_site_admin() )
		{
			return;
		}

		global $wpdb;

		$ad_distributions = $wpdb->get_results("
			SELECT
				*
			FROM
				$wpdb->ad_distributions
			ORDER BY
				ad_distribution_name, ad_distribution_id DESC
			");

		$default_ad_distributions = array(
			-1 => __('Random', 'sem-ad-space'),
			0 => __('None', 'sem-ad-space')
			);

		if ( isset($ad_distributions) )
		{
			foreach ( $ad_distributions as $ad_distribution )
			{
				if ( in_array($ad_distribution->ad_distribution_id, $this->params['default_ad_distribution']) )
				{
					$default_ad_distributions[$ad_distribution->ad_distribution_id] = str_replace(array("<", ">", "\"", "\r"), array("&lt;", "&gt;", "&quot;", ""), stripslashes($ad_distribution->ad_distribution_name));
				}
			}
		}

		if ( isset($GLOBALS['post_ID']) )
		{
			$sem_ad_distribution = $wpdb->get_var("
				SELECT
					ad_distribution_id
				FROM
					$wpdb->ad_distribution2post
				WHERE
					post_id = " . intval($GLOBALS['post_ID'])
				);
		}

		if ( !isset($sem_ad_distribution) )
		{
			$sem_ad_distribution = '';
		}

		echo "<fieldset style=\"margin-bottom: 2em;\">\n"
			. "<legend>" . __('Ad Distribution') . "</legend>\n";

		echo "<table width=\"100%\" cellspacing=\"2\" cellpadding=\"5\" class=\"editform\">\n"
			. "<tr>\n"
			. "<th style=\"text-align: right; width: 160px;\">"
				. "<label for=\"sem_ad_distribution\">"
				. __('Ad Distribution') . ":"
				. "</label>"
				. "</th>\n"
			. "<td style=\"text-align: left;\">\n"
			. "<select id=\"sem_ad_distribution\" name=\"sem_ad_distribution\">"
			. "<option value=\"\""
				. ( ( $sem_ad_distribution === '' )
					? " selected"
					: ""
					)
				. ">"
				. __('Default', 'sem-ad-space')
					. " ("
					. $default_ad_distributions[$this->params['default_ad_distribution'][$ad_tag_id]]
					. ")"
				. "</option>\n"
			. "<option value=\"0\""
				. ( ( !$sem_ad_distribution && $sem_ad_distribution !== '' )
					? " selected"
					: ""
					)
				. ">"
				. __('None', 'sem-ad-space')
				. "</option>\n"
			. "<option value=\"-1\""
				. ( ( $sem_ad_distribution == -1 )
					? " selected"
					: ""
					)
				. ">"
				. __('Random', 'sem-ad-space')
				. "</option>\n";

		if ( isset($ad_distributions) )
		{
			foreach ( $ad_distributions as $ad_distribution )
			{
				echo "<option value=\"" . $ad_distribution->ad_distribution_id . "\""
					. ( ( $sem_ad_distribution == $ad_distribution->ad_distribution_id )
						? " selected"
						: ""
						)
					. ">"
					. stripslashes($ad_distribution->ad_distribution_name)
					. "</option>\n";
			}
		}

		echo "</select>\n"
			. "</td>\n"
			. "</tr>\n"
			. "</table>\n";

		echo "</fieldset>\n";
	} # end display_ad_selector()


	#
	# display_quicktag()
	#

	function display_quicktag()
	{
		global $wpdb;

		$ad_blocks = $wpdb->get_results("
			SELECT
				ad_blocks.*
			FROM
				$wpdb->ad_blocks as ad_blocks
			INNER JOIN
				$wpdb->ad_block2tag as ad_block2tag
					ON ad_block2tag.ad_block_id = ad_blocks.ad_block_id
			WHERE
				ad_block2tag.ad_tag_id = 'inline'
			ORDER BY
				ad_blocks.ad_block_name
			");

		$js_options = "";

		if ( isset($ad_blocks) )
		{
			foreach ( $ad_blocks as $ad_block )
			{
				$js_options .= '<option value=\"-'
						. 'adunit#' . str_replace(array("<", ">", "\"", "\r"), array("&lt;", "&gt;", "&quot;", ""), stripslashes($ad_block->ad_block_name))
					. '-\">'
					. str_replace(array("<", ">", "\"", "\r"), array("&lt;", "&gt;", "&quot;", ""), stripslashes($ad_block->ad_block_name))
					. '</option>';
			}
		}
?><script type="text/javascript">

if ( document.getElementById('quicktags') )
{

function add_ad_block(elt)
{
	if ( elt && elt.value != '' )
	{
		edInsertContent(edCanvas, '<!-'+ elt.value +'->');
	}
	elt.selectedIndex = 0;
} // end add_ad_block()

document.getElementById('ed_toolbar').innerHTML
	+= '<select class=\"ed_button\" style=\"width: 100px;\" onchange=\"return add_ad_block(this);\">'
	+ '<option value=\"\" selected><?php echo __('Ad Unit', 'sem-ad-space'); ?></option>'
	+ '<option value=\"-adunit-\"><?php echo __('Default Inline', 'sem-ad-space'); ?></option>'
	+ '<?php echo $js_options; ?>'
	+ '</select>';
} // end if
</script>
<?php
	} # end display_quicktag()


	#
	# add_mce_plugin()
	#

	function add_mce_plugin($plugins)
	{
		$plugins[] = 'adspace';

		return $plugins;
	} # end add_mce_plugin()


	#
	# add_mce_button()
	#

	function add_mce_button($buttons)
	{
		if ( !empty($buttons) )
		{
			$buttons[] = 'separator';
		}

		$buttons[] = 'adspace';

		return $buttons;
	} # end add_mce_button()


	#
	# display_all_ad_blocks()
	#

	function display_all_ad_blocks()
	{
		global $wpdb;

		$ad_blocks = $wpdb->get_results("
			SELECT
				ad_blocks.*
			FROM
				$wpdb->ad_blocks as ad_blocks
			INNER JOIN
				$wpdb->ad_block2tag as ad_block2tag
					ON ad_block2tag.ad_block_id = ad_blocks.ad_block_id
			WHERE
				ad_block2tag.ad_tag_id = 'inline'
			ORDER BY
				ad_blocks.ad_block_name
			");

		$js_options = "";

		if ( isset($ad_blocks) )
		{
			foreach ( $ad_blocks as $ad_block )
			{
				$js_options .= ( $js_options ? ', ' : '' )
					. "'"
					. str_replace(
						array("<", ">", "\"", "\r"),
						array("&lt;", "&gt;", "&quot;", ""),
						stripslashes($ad_block->ad_block_name)
						)
					. "'";
			}
		}
?><script type="text/javascript">
var all_ad_blocks = new Array(<?php echo $js_options; ?>);
document.all_ad_blocks = all_ad_blocks;
//alert(document.all_ad_blocks);
</script>
<?php
	} # end display_all_ad_blocks()
} # end sem_ad_space

$sem_ad_space =& new sem_ad_space();
?>