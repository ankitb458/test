<?php
class sem_docs
{
	#
	# init()
	#

	function init()
	{
		add_action('admin_notices', array('sem_docs', 'get'), 0);

		add_action('admin_head', array('sem_docs', 'display_css'));
		add_action('admin_head', array('sem_docs', 'display_scripts'));
		add_action('admin_notices', array('sem_docs', 'display'));
	} # init()


	#
	# get()
	#

	function get()
	{
		sem_docs::update();

		global $sem_docs;

		$sem_docs = get_option('sem5_docs');

		#$sem_docs = array();
	} # get()


	#
	# update()
	#

	function update($force = false)
	{
		set_time_limit(0);

		# using ?method=update_docs forces a doc refresh
		if ( !$force )
		{
			$force = ( $_GET['method'] == 'update_docs' );
		}

		global $sem_docs;
		global $sem_options;

		if ( $force )
		{
			$sem_docs = array();
		}

		$docs_updated = get_option('sem5_docs_updated');

		if ( !sem_pro )
		{
			$tags = array('features', 'feature_sets', 'admin');
		}
		else
		{
			$tags = array('features', 'feature_sets', 'admin', 'tips');
		}

		$i = 0;

		$updated = false;

		global $allowedposttags;
		global $cookiearr;

		foreach ( $tags as $tag )
		{
			# get docs only once for non-sem pro sites
			if ( !sem_pro && isset($docs_updated[$tag]) && !$force )
			{
				continue;
			}

			$url = 'http://rest.semiologic.com/1.0/docs/?cat=sem_pro_' . urlencode($tag) . '&version=' . urlencode(preg_replace("/\s.*$/", '', sem_version));

			if ( !$force )
			{
				# update docs every two weeks
				if ( $docs_updated[$tag] + 3600 * 24 * 7 * 2 >= time() )
				{
					continue;
				}
				elseif ( isset($docs_updated[$tag]) )
				{
					$url .= '&last_modified=' . urlencode(date('Y-m-d', $docs_updated[$tag]));
				}
			}

			if ( in_array($tag, array('tips')) )
			{
				if ( $sem_options['api_key'] )
				{
					$url .= '&user_key=' . urlencode($sem_options['api_key']);
				}
				else
				{
					continue;
				}
			}

			include_once sem_path . '/inc/http.php';

			$xml = sem_http::get($url);

			# php4 xml functionality is junk

			if ( preg_match("/
					<messages>
					(.*)
					<\/messages>
					/isUx",
					$xml
					)
				)
			{
				preg_match_all("/
					<error>
						<!\[CDATA\[
						(.*)
						\]\]>
					<\/error>
					/isUx",
					$xml,
					$matches,
					PREG_SET_ORDER
					);

				/*
				echo '<div class="error">';

				echo '<p>' . __('The following errors occurred while updating the Semiologic documentation') . ':' . '</p>';

				echo '<ul>';

				foreach ( $matches as $match )
				{
					echo '<li>'
						. $match[1]
						. '</li>';
					break;
				}

				echo '</ul>';

				echo '<p>'
					. __('Please check your API key and your memberships in the <a href="http://members.semiologic.com">Semiologic Members\' area</a>.')
					. '</p>';

				echo '</div>';
				*/

				break;
			}

			preg_match_all("/
				<doc>
				\s*
				<key>
					(.*)
				<\/key>
				\s*
				<name>
					(.*)
				<\/name>
				\s*
				<excerpt>
					<!\[CDATA\[
					(.*)
					\]\]>
				<\/excerpt>
				\s*
				<content>
					<!\[CDATA\[
					(.*)
					\]\]>
				<\/content>
				\s*
				<\/doc>
				/isUx",
				$xml,
				$matches,
				PREG_SET_ORDER
				);

			foreach ( $matches as $match )
			{
				$key = $match[1];
				$name = $match[2];
				$excerpt = $match[3];
				$content = $match[4];

				$updated[] = $name;

				foreach ( array('key', 'name', 'excerpt', 'content') as $var )
				{
					$$var = trim(wp_kses($$var, $allowedposttags));
				}

				$sem_docs[$tag][$key] = compact('name', 'excerpt', 'content');

				#dump($docs[$tag][$key]);
			}

			#dump($docs[$tag]);

			$now = time();

			# spread individual doc updates a bit to avoid updating everything each time
			if ( !isset($docs_updated[$tag]) )
			{
				$now -= $i * 3600 * 24 * 2;
			}

			$docs_updated[$tag] = $now;
		}

		if ( $updated )
		{
			if ( !$force )
			{
				echo '<div class="updated">'
					. '<p>' . __('The following documentation has just been updated') . ':</p>'
					. '<ul>';

				foreach ( $updated as $update )
				{
					echo '<li>' . $update . '</li>';
				}

				echo '</ul>'
					. '</div>';
			}

			update_option('sem5_docs', $sem_docs);
			update_option('sem5_docs_updated', $docs_updated);

			global $wpdb;

			$wpdb->query("
				UPDATE	$wpdb->options
				SET		autoload = 'no'
				WHERE	option_name = 'sem5_docs'
				");
		}
	} # update()


	#
	# display_css()
	#

	function display_css()
	{
		?>
<style type="text/css">
div.sem_docs
{
	padding: 4px 5%;
}
div#sem_docs
{
	border: solid 1px lightsteelblue;
	background-color: ghostwhite;
}
</style>
		<?php
	} # display_css()


	#
	# display_scripts()
	#

	function display_scripts()
	{
		?>
<script type="text/javascript">
function show_sem_docs()
{
	document.getElementById('sem_docs__more').style.display = 'none';
	document.getElementById('sem_docs__less').style.display = '';

	document.getElementById('sem_docs').style.display = '';
}

function hide_sem_docs()
{
	document.getElementById('sem_docs__more').style.display = '';
	document.getElementById('sem_docs__less').style.display = 'none';

	document.getElementById('sem_docs').style.display = 'none';
}
</script>
		<?php
	} # display_scripts()


	#
	# display()
	#

	function display()
	{
		# create doc key

		$menu = $_SERVER['PHP_SELF'];
		$menu = preg_replace("/^.*\/wp-admin\/|\.php$/i", '', $menu);
		$page = $_GET['page'];
		$page = preg_replace("/\.php$/i", '', $page);
		$key = $menu . ( $page ? ( '_' . $page ) : '' );
		$key = str_replace(array('-', '/'), '_', $key);

		global $sem_docs;

		switch ( $key )
		{
		case 'post':
			$key = 'post_new';
			break;
		case 'page':
			$key = 'page_new';
			break;
		}

		#dump($key);
		#dump($sem_docs['admin']);

		if ( !isset($sem_docs['admin'][$key]) )
		{
			$sem_docs['admin'][$key]['name'] = $key;
		}

		$docs =& $sem_docs['admin'][$key];

		if ( $docs['excerpt'] || $docs['content'] )
		{
			echo '<div class="sem_docs" id="sem_docs__more" style="float: right;">'
				. $docs['excerpt']
				. ( $docs['content']
					? ( ' &bull; '
						. '<a href="javascript:;" onclick="show_sem_docs();">'
						. __('More Info')
						. '</a>'
						)
					: ''
					)
				. '</div>'
				. '<div style=" clear: both;"></div>';
		}

		if ( $docs['content'] )
		{
			echo '<div class="sem_docs" id="sem_docs__less" style="float: right; display: none;">'
				. '<a href="javascript:;" onclick="hide_sem_docs();">'
					. __('Less Info')
					. '</a>'
				. '</div>'
				. '<div style=" clear: both;"></div>';

			echo '<div class="sem_docs" id="sem_docs" style="display: none;">'
				. '<h3>' . $docs['name'] . '</h3>'
				. $docs['content']
				. '</div>';

			echo '<div style="clear: both;"></div>';
		}
		else
		{
			echo '<div style="float: left; display: none;">'
				. sprintf(__('No Docs on %s'), $docs['name'])
				. '</div>';
		}
	} # display()
} # sem_docs

sem_docs::init();
?>