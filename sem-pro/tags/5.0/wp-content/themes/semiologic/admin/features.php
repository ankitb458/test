<?php


class sem_features
{
	#
	# init()
	#

	function init()
	{
		$GLOBALS['sem_features'] = array();
		$GLOBALS['sem_feature_sets'] = array();

		add_action('admin_menu', array('sem_features', 'admin_menu'));
		add_action('admin_head', array('sem_features', 'display_scripts'));
	} # init


	#
	# add_set()
	#

	function add_set($tag)
	{
		global $sem_feature_sets;

		$feature_set = array(
			'features' => array()
			);

		$sem_feature_sets[$tag] =& $feature_set;
	} # add_set()


	#
	# add()
	#

	function add($key)
	{
		global $sem_features;

		$feature = array(
			'locked' => null
			);

		$sem_features[$key] =& $feature;
	} # add()


	#
	# add2set()
	#

	function add2set($key, $tag)
	{
		global $sem_feature_sets;

		$sem_feature_sets[$tag]['features'][] = $key;
	} # add2set()


	#
	# register()
	#

	function register($feature_sets)
	{
		foreach ( $feature_sets as $tag => $keys )
		{
			sem_features::add_set($tag);

			foreach ( $keys as $key )
			{
				sem_features::add($key);
				sem_features::add2set($key, $tag);
			}
		}
	} # register()


	#
	# sort()
	#

	function sort()
	{
		global $sem_feature_sets;
		global $sem_features;
		global $sem_docs;

		# check docs
		foreach ( array_keys($sem_feature_sets) as $tag )
		{
			if ( !isset($sem_docs['feature_sets'][$tag]) )
			{
				$sem_docs['feature_sets'][$tag]['name'] = $tag;
			}
		}
		foreach ( array_keys($sem_features) as $key )
		{
			if ( !isset($sem_docs['features'][$key]) )
			{
				$sem_docs['features'][$key]['name'] = $key;
			}
		}

		foreach ( $sem_feature_sets as $tag => $feature_set )
		{
			$features = array();

			foreach ( $feature_set['features'] as $key )
			{
				$features[$key] = $sem_docs['features'][$key]['name'];
			}

			uasort($features, array('sem_features', 'natsort'));

			$sem_feature_sets[$tag]['features'] = array_keys($features);
		}
	} # sort()


	#
	# natsort()
	#

	function natsort($a, $b)
	{
		return strnatcmp($a, $b);
	} # natsort()


	#
	# lock()
	#

	function lock($key, $locked = null)
	{
		global $sem_features;

		$sem_features[$key]['locked'] = $locked;
	} # lock()


	#
	# admin_menu()
	#

	function admin_menu()
	{
		if ( !function_exists('get_site_option') )
		{
			add_submenu_page(
				'themes.php',
				__('Features'),
				__('Features'),
				'switch_themes',
				basename(__FILE__),
				array('sem_features', 'admin_page')
				);
		}
	} # admin_menu()


	#
	# admin_page()
	#

	function admin_page()
	{
		# Process updates, if any

		if ( $_POST['update_theme_features'] )
		{
			echo "<div class=\"updated\">\n"
				. "<p>"
					. "<strong>"
					. __('Options saved.')
					. "</strong>"
				. "</p>\n"
				. "</div>\n";
		}

		sem_features::sort();

		echo '<div class="wrap">' . "\n"
			. '<h2>' . __('Theme Features') . '</h2>' . "\n"
			. '<form method="post" action="" id="sem_features">' . "\n";

		if ( function_exists('wp_nonce_field') ) wp_nonce_field('sem_features');

		echo '<input type="hidden" name="update_theme_features" value="1" />' . "\n";

		echo "\n";

		if ( !sem_pro )
		{
			pro_feature_notice();
		}

		global $sem_features;
		global $sem_feature_sets;
		global $sem_docs;

		foreach ( $sem_feature_sets as $tag => $feature_set )
		{
			if ( $feature_set['features'] )
			{
				# merge doc fields
				if ( !isset($feature_set['name']) )
				{
					$feature_set = array_merge($feature_set, (array) $sem_docs['feature_sets'][$tag]);
					$sem_feature_sets[$tag] =& $feature_set;
				}

				# feature_key list for mass-activation
				$feature_ids = '';
				$all_active = sem_pro;

				foreach ( $feature_set['features'] as $key )
				{
					$feature_ids .= ( $feature_ids ? ',' : '' )
						. $key;

					if ( sem_pro )
					{
						$all_active &= sem_pro_features::is_enabled($key);
					}
				}

				if ( $feature_set['excerpt'] && $feature_set['content'] && sem_pro )
				{
					echo '<div style="float: right;">';
					echo '<a id="sem_feature_set__' . $tag . '__more" href="javascript:;" onclick="show_feature_info(\'sem_feature_set__' . $tag . '\')">' . __('More Info') . '</a>';
					echo '<a id="sem_feature_set__' . $tag . '__less" href="javascript:;" onclick="hide_feature_info(\'sem_feature_set__' . $tag . '\')" style="display: none;">' . __('Less Info') . '</a>';
					echo '</div>';
				}

				echo '<h3><label for="sem_feature_sets__' . $tag . '">';

				echo '<input type="checkbox"'
					. ' id="sem_feature_sets__' . $tag . '"';

				if ( !sem_pro )
				{
					echo ' disabled="disabled"';
				}
				else
				{
					echo ( $all_active ? ' checked="checked"' : '' );

					echo ' onchange="'
						. 'if ( this.checked ) '
						. 'check_features(\'' . $feature_ids . '\'); '
						. 'else '
						. 'uncheck_features(\'' . $feature_ids . '\');'
						. '"';
				}

				echo ' />'
					. '&nbsp;';

				echo $feature_set['name'];

				echo '</label>'
					. '</h3>' . "\n";

				if ( $feature_set['excerpt'] )
				{
					echo '<div id="sem_feature_set__' . $tag . '__excerpt">' . "\n"
						. $feature_set['excerpt']
						. '<div style="clear: both;"></div>'
						. '</div>' . "\n";
				}

				if ( $feature_set['content'] )
				{
					echo '<div id="sem_feature_set__' . $tag. '__content"'
						. ( $feature_set['excerpt']
							? ' style="display: none;"'
							: ''
							)
						. '>' . "\n"
						. $feature_set['content']
						. '<div style="clear: both;"></div>'
						. '</div>' . "\n";
				}


				echo '<div style="clear: both; margin-bottom: 1em;"></div>';

				foreach ( $feature_set['features'] as $key )
				{
					$feature =& $sem_features[$key];

					# merge doc fields
					if ( !isset($feature['name']) )
					{
						$feature = array_merge($feature, (array) $sem_docs['features'][$key]);
						$sem_features[$key] =& $feature;
					}

					$disabled = !sem_pro || !is_null($feature['locked']);
					$checked = $feature['locked'] || ( sem_pro && sem_pro_features::is_enabled($key) );

					echo '<table cellpadding="0" cellspacing="4" border="0" width="100%">' . "\n"
						. '<tr valign="top">' . "\n"
						. '<td width="12">'
						. '<input type="checkbox"'
							. ' id="sem_feature__' . $key . '" name="sem_features[' . $key . ']"';

					if ( $disabled )
					{
						echo ' disabled="disabled"';
					}

					if ( $checked )
					{
						echo ' checked="checked"';
					}

					if ( sem_pro )
					{
						echo ' onchange="toggle_feature_set(\'sem_feature_sets__' . $tag . '\', \'' . $feature_ids . '\');"';
					}

					echo ' />'
						. '</td>' . "\n"
						. '<th align="left">';

					echo '<label for="sem_feature__' . $key . '">';

					if ( $checked )
					{
						echo '<u>' . $feature['name'] . '</u>';
					}
					elseif ( $disabled )
					{
						echo '<span style="color: dimgray;">' . $feature['name']. '</span>';
					}
					else
					{
						echo $feature['name'];
					}

					echo '</label>';

					if ( $feature['excerpt'] && $feature['content'] && sem_pro )
					{
						echo '<span style="font-weight: normal;">';
						echo '&nbsp;&bull;&nbsp;';
						echo '<a id="sem_feature__' . $key . '__more" href="javascript:;" onclick="show_feature_info(\'sem_feature__' . $key . '\')">' . __('More Info') . '</a>';
						echo '<a id="sem_feature__' . $key . '__less" href="javascript:;" onclick="hide_feature_info(\'sem_feature__' . $key . '\')" style="display: none;">' . __('Less Info') . '</a>';
						echo '</span>';
					}

					echo '</th>' . "\n"
						. '</tr>' . "\n";

					if ( $feature['excerpt'] )
					{
						echo '<tr valign="top" id="sem_feature__' . $key . '__excerpt">' . "\n"
							. '<td>&nbsp;</td>' . "\n"
							. '<td>'
							. $feature['excerpt']
							. '<div style="clear: both;"></div>'
							. '</td>' . "\n"
							. '</tr>' . "\n";
					}

					if ( $feature['content'] )
					{
						echo '<tr valign="top" id="sem_feature__' . $key. '__content"'
							. ( $feature['excerpt']
								? ' style="display: none;"'
								: ''
								)
							. '>' . "\n"
							. '<td>&nbsp;</td>' . "\n"
							. '<td>'
							. $feature['content']
							. '<div style="clear: both;"></div>'
							. '</td>' . "\n"
							. '</tr>' . "\n";
					}

					echo '</table>' . "\n";
				}

				echo '<div style="clear: both;"></div>';

				echo '<div class="submit">';
				echo '<input type="submit" value="' . __('Update Options') . ' &raquo;"'
					. ( !sem_pro
						? ' disabled="disabled"'
						: ''
						)
					. ' />';
				echo '</div>';
			}
		}

		echo "</form>"
			. "</div>\n";
	} # admin_page()


	#
	# display_scripts()
	#

	function display_scripts()
	{
		?>
<script type="text/javascript">
function check_features(id_list)
{
	id_list = id_list.split(',');

	for ( i = 0; i < id_list.length; i++ )
	{
		if ( !document.getElementById('sem_feature__' + id_list[i]).disabled )
		{
			document.getElementById('sem_feature__' + id_list[i]).checked = true;
		}
	}
}

function uncheck_features(id_list)
{
	id_list = id_list.split(',');

	for ( i = 0; i < id_list.length; i++ )
	{
		if ( !document.getElementById('sem_feature__' + id_list[i]).disabled )
		{
			document.getElementById('sem_feature__' + id_list[i]).checked = false;
		}
	}
}

function toggle_feature_set(elt_id, id_list)
{
	id_list = id_list.split(',');

	is_active = true;

	for ( i = 0; i < id_list.length; i++ )
	{
		if ( !document.getElementById('sem_feature__' + id_list[i]).checked )
		{
			 document.getElementById(elt_id).checked = false;

			 return;
		}
	}

	document.getElementById(elt_id).checked = true;
}

function show_feature_info(elt_id)
{
	document.getElementById(elt_id + '__less').style.display = '';
	document.getElementById(elt_id + '__content').style.display = '';

	document.getElementById(elt_id + '__more').style.display = 'none';
	document.getElementById(elt_id + '__excerpt').style.display = 'none';
}

function hide_feature_info(elt_id)
{
	document.getElementById(elt_id + '__less').style.display = 'none';
	document.getElementById(elt_id + '__content').style.display = 'none';

	document.getElementById(elt_id + '__more').style.display = '';
	document.getElementById(elt_id + '__excerpt').style.display = '';
}
</script>
<style type="text/css">
#sem_features img
{
	border: solid 1px Lavender;
	padding: 20px;
}
</style>
<?php
	} # display_scripts()
} # sem_features

sem_features::init();


#sem_features::dump();

# the list below needs to be completed, and reorganized
sem_features::register(
	array(
		'seo' => array(
			'autotag',
			'enforce_permalinks',
			'google_sitemap',
			'non_unique_slugs',
			'smart_pings',
			'seo_title',
			'seo_meta',
			'silo_web_design',
			'smart_links',
			'social_poster',
			),
		'stats' => array(
			'feedburner',
			'google_analytics',
			'hitslink',
			),
		'modules' => array(
			'ad_manager',
			'book_library',
			'contact_form',
			'event_manager',
			'newsletter_manager',
			'podcasting',
			'poll_manager',
			'semiologic_affiliate',
			'star_ratings',
			'tag_manager',
			),
		'misc_widgets' => array(
			'author_image',
			'dealdotcom',
			'flickr_widget',
			'fuzzy_widgets',
			'mybloglog',
			'paypal_widget',
			'random_widgets',
			'related_widgets',
			'social_bookmarking',
			'subscribe_buttons',
			),
		'community' => array(
			'do_follow',
			'moderate_subscribers',
			'subscribe2comments',
			),
		'spam' => array(
			'akismet',
			'hashcash',
			'tb_validator',
			),
		'front_page' => array(
			'opt_in_front',
			'static_front',
			'custom_query',
			),
		'wp_tweaks' => array(
			'absolute_urls',
			'autolink_uri',
			'external_links',
			'fancy_excerpt',
			'favicon',
			'frame_buster',
			'full_text_feed',
			'improved_search',
			'no_fancy_quotes',
			'no_self_pings',
			'comment_fixes',
			),
		'site_admin' => array(
			'admin_menu',
			'docs_and_tips',
			'comment_status_manager',
			'easy_page_order',
			'role_manager',
			'wysiwyg_editor',
			),
		'site_maintenance' => array(
			'advanced_cache',
			'db_backup',
			'db_maintenance',
			'easy_upgrades',
			'version_checker',
			),
		)
	);


# lock built-in features

sem_features::lock('enforce_permalinks', true);
sem_features::lock('seo_title', sem_pro ? true : false);
sem_features::lock('seo_meta', sem_pro ? true : false);
sem_features::lock('smart_pings', true);

sem_features::lock('docs_and_tips', sem_pro ? true : false);
sem_features::lock('easy_page_order', sem_pro ? true : false);
sem_features::lock('comment_fixes', sem_pro ? true : false);

sem_features::lock('db_maintenance', sem_pro ? true : false);
sem_features::lock('easy_upgrades', sem_pro ? true : false);


# lock third party features

if ( !file_exists(ABSPATH . 'wp-content/plugins/social-poster/mm_post.php') )
{
	sem_features::lock('social_poster', false);
}

#echo '<pre>';
#var_dump($sem_feature_sets);
#echo '</pre>';
?>