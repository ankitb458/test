<?php
#
# MC Admin Menu
# -------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#


class admin_menu
{
	protected static $captions;
	protected static $menu = array();


	#
	# init()
	#

	public static function init()
	{
		self::$captions =& new captions;

		self::add_tab(
			'new',
			self::$captions->get('new')
			);

		self::add_tab(
			'manage',
			self::$captions->get('manage')
			);

		self::add_tab(
			'options',
			self::$captions->get('options')
			);

		self::add_tab(
			'presentation',
			self::$captions->get('presentation')
			);

		self::add_tab(
			'profile',
			self::$captions->get('profile'),
			'profile'
			);

		self::add_tab(
			'logout',
			self::$captions->get('logout'),
			'logout'
			);

		new event('admin_menu');
	} # init()


	#
	# html()
	#

	public function html(&$args)
	{
		if ( active_user::is_guest() )
		{
			return;
		}

		echo '<div id="admin_menu">';

		foreach ( self::$menu as $tab_key => $tab )
		{
			$tab['items'] = @ (array) $tab['items'];

			if ( empty($tab['items']) && !isset($tab['cmd']) )
			{
				continue;
			}

			echo '<div'
				. ' onmouseover="document.getElementById(\'admin_menu_tab[' . str::attr($tab_key) . ']\').style.display=\'\';"'
				. ' onmouseout="document.getElementById(\'admin_menu_tab[' . str::attr($tab_key) . ']\').style.display=\'none\';"'
				. '>';

			if ( isset($tab['cmd']) )
			{
				echo '<span class="admin_menu_tab ' . str::attr($tab_key) .  '">'
					. '<a href="' . str::attr(permalink::get(array('cmd' => $tab['cmd']))) . '">'
					. $tab['label']
					. '</a>'
					. '</span>';
			}
			else
			{
				echo '<span>'
					. $tab['label']
					. '</span>';
			}

			if ( !empty($tab['items']) )
			{
				echo '<div id="admin_menu_tab[' . str::attr($tab_key) . ']" style="display: none;">';

				foreach ( $tab['items'] as $item_key => $item )
				{
					echo '<span class="admin_menu_item ' . str::attr($item_key). '">'
						. '<a href="' . str::attr(permalink::get(array('cmd' => $item['cmd']))) . '">'
						. $item['label']
						. '</a>'
						. '</span>';
				}

				echo '</div>';
			}

			echo '</div>';
		}

		echo '</div>'
			. '<div class="spacer"></div>'
			;
	} # html()


	#
	# add_tab()
	#

	public static function add_tab($key, $label, $cmd = null, $cap = null)
	{
		if ( !isset($cap) || active_user::can($cap) )
		{
			self::$menu[$key]['label'] = $label;

			if ( isset($cmd) )
			{
				self::$menu[$key]['cmd'] = $cmd;
			}
		}
	} # add_tab()


	#
	# add_item()
	#

	public static function add_item($tab, $key, $label, $cmd, $cap = null)
	{
		if ( !isset($cap) || active_user::can($cap) )
		{
			self::$menu[$tab]['items'][$key] = array(
				'label' => $label,
				'cmd' => $cmd,
				);
		}
	} # add_item()


	#
	# dump()
	#

	public static function dump()
	{
		debug::dump(self::$menu);
	} # dump()
} # admin_menu

admin_menu::init();
?>