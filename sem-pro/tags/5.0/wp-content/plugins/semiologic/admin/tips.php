<?php
class sem_pro_tips
{
	#
	# init()
	#

	function init()
	{
		add_action('admin_head', array('sem_pro_tips', 'display_css'));
		add_action('admin_head', array('sem_pro_tips', 'display_scripts'));
		add_action('admin_footer', array('sem_pro_tips', 'display'));
		add_action('_admin_menu', array('sem_pro_tips', 'ajax'));
	} # init()


	#
	# display_css()
	#

	function display_css()
	{
		?>
<style type="text/css">
div.sem_tip
{
	padding: 4px 10px;
}

div#sem_tip
{
	border: solid 1px forestgreen;
	background-color: ghostwhite;
	position: absolute;
	top: 40px;
	left: 240px;
	width: 500px;
}
</style>
		<?php
	} # display_css()


	#
	# display_scripts()
	#

	function display_scripts()
	{
		wp_print_scripts( array( 'sack' ));

		?>
<script type="text/javascript">
function close_sem_tip()
{
	if ( !document.getElementById('show_sem_tips').checked )
	{
		stop_sem_tips();
	}
	else
	{
		document.getElementById('sem_tip').style.display = 'none';
	}
}

function next_sem_tip()
{
	var mysack = new sack("<?php bloginfo( 'wpurl' ); ?>/wp-admin/" );

	load_sem_tip('<p>Loading next tip...<\/p>');

	mysack.execute = 1;
	mysack.method = 'GET';
	mysack.setVar('method', 'next_tip');
	mysack.onError = function() { alert('AJAX error') };
	mysack.runAJAX();

	return true;
}

function prev_sem_tip()
{
	var mysack = new sack("<?php bloginfo( 'wpurl' ); ?>/wp-admin/" );

	load_sem_tip('<p>Loading previous tip...<\/p>');

	mysack.execute = 1;
	mysack.method = 'GET';
	mysack.setVar('method', 'prev_tip');
	mysack.onError = function() { alert('AJAX error') };
	mysack.runAJAX();

	return true;
}

function stop_sem_tips()
{
	var mysack = new sack("<?php bloginfo( 'wpurl' ); ?>/wp-admin/" );

	load_sem_tip('<p>Updating Guru Tip Preferences...<\/p>');

	mysack.execute = 1;
	mysack.method = 'GET';
	mysack.setVar('method', 'stop_tips');
	mysack.onError = function() { alert('AJAX error') };
	mysack.runAJAX();

	return true;
}

function load_sem_tip(tip)
{
	if ( tip )
	{
		document.getElementById('sem_tip_details').innerHTML = tip;
	}
	else
	{
		close_sem_tip();
	}
}
</script>
		<?php
	} # display_scripts()


	#
	# display()
	#

	function display()
	{
		global $sem_options;

		if ( empty($sem_options['api_key']) )
		{
			return;
		}

		$user = wp_get_current_user();

		@session_start();

		if ( !$_SESSION['sem_tip_showed'] )
		{
			if ( $tip = sem_pro_tips::next() )
			{
				echo '<div id="sem_tip">';

				echo '<div class="sem_tip" style="float: right;">'
					. '<a href="javascript:;" onclick="close_sem_tip();">'
						. __('Close')
						. '</a>'
					. '</div>';

				echo '<div class="sem_tip">'
					. '<h3>' . __('Did you know?') . '</h3>'
					. '<div id="sem_tip_details">'
					. $tip
					. '</div>'
					. '</div>';

				echo '<div class="sem_tip" style="float: right">'
					. '<a href="javascript:;" onclick="prev_sem_tip();">'
						. __('Previous')
						. '</a>'
					. ' / '
					. '<a href="javascript:;" onclick="next_sem_tip();">'
						. __('Next')
						. '</a>'
					. '</div>';

				echo '<div class="sem_tip">'
					. '<label for="show_sem_tips">'
					. '<input type="checkbox"'
						. ' id="show_sem_tips"'
						. ' checked="checked"'
						. ' />'
						. '&nbsp;'
						. __('Show tips at startup')
						. '</label>'
					. '</div>';

				echo '<div style="clear: both;"></div>';

				echo '</div>';
			}

			$_SESSION['sem_tip_showed'] = true;
		}

		# debug
		#$_SESSION['sem_tip_showed'] = false;
		#dump($sem_options);
	} # display()


	#
	# ajax()
	#

	function ajax()
	{
		switch ( $_REQUEST['method'] )
		{
		case 'next_tip':
			$tip = sem_pro_tips::next();

			$tip = addslashes($tip);

			$tip = str_replace('</', '<\/', $tip);

			$tip = str_replace("\n", "\\\n", $tip);

			echo "load_sem_tip('" . $tip . "');";
			die;

		case 'prev_tip':
			$tip = sem_pro_tips::prev();

			$tip = addslashes($tip);

			$tip = str_replace('</', '<\/', $tip);

			$tip = str_replace("\n", "\\\n", $tip);

			echo "load_sem_tip('" . $tip . "');";
			die;

		case 'stop_tips':
			$options = sem_tips::get_options();

			$options['show_tips'] = false;

			sem_tips::update_options($options);

			echo "document.getElementById('sem_tip').style.display = 'none';"
				. "top.location.href = document.location.href;";
			die;
		}
	} # ajax()


	#
	# next()
	#

	function next()
	{
		$options = sem_tips::get_options();

		#dump($options);

		if ( !$options['show_tips'] )
		{
			return false;
		}

		global $sem_docs;

		if ( !isset($sem_docs) )
		{
			$sem_docs = get_option('sem5_docs');
		}

		$tips = (array) $sem_docs['tips'];

		#dump($tips);

		if ( $options['tip_id'] === false )
		{
			$tip = current($tips);
			$options['tip_id'] = key($tips);
		}
		else
		{
			while ( key($tips) != $options['tip_id'] )
			{
				if ( next($tips) === false )
				{
					reset($tips);
					break;
				}
			}

			if ( next($tips) === false )
			{
				reset($tips);
			}

			$tip = current($tips);
			$options['tip_id'] = key($tips);
		}

		sem_tips::update_options($options);

		#dump($options);

		return $tip['content'];
	} # next()


	#
	# prev()
	#

	function prev()
	{
		$options = sem_tips::get_options();

		if ( !$options['show_tips'] )
		{
			return false;
		}

		global $sem_docs;

		if ( !isset($sem_docs) )
		{
			$sem_docs = get_option('sem5_docs');
		}

		$tips = (array) $sem_docs['tips'];

		if ( $options['tip_id'] === false )
		{
			$tip = current($tips);
			$options['tip_id'] = key($tips);
		}
		else
		{
			end($tips);
			while ( key($tips) != $options['tip_id'] )
			{
				if ( prev($tips) === false )
				{
					end($tips);
					break;
				}
			}

			if ( prev($tips) === false )
			{
				end($tips);
			}

			$tip = current($tips);
			$options['tip_id'] = key($tips);
		}

		sem_tips::update_options($options);

		return $tip['content'];
	} # prev()
} # sem_pro_tips

sem_pro_tips::init();
?>