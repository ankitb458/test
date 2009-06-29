<?php
#
# MC Profile List
# ---------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class profile_list extends request
{
	protected static $captions;
	protected $max_page;
	protected $page;


	#
	# init()
	#

	public static function init()
	{
		self::$captions =& new captions;
	} # init()


	#
	# __construct()
	#

	public function __construct(&$args)
	{
		if ( !active_user::is_admin() )
		{
			new status_403($args);
		}

		parent::__construct($args);
	} # __construct


	#
	# map()
	#

	public function map()
	{
		$this->data = array();

		$this->page =& $this->args['page'];
		$this->page = is_numeric($this->page) ? intval($this->page) : 1;

		$num = 50;
		$offset = ( $this->page - 1 ) * $num;

		$total = db::get_var("
			SELECT	COUNT(*)
			FROM	profiles
			;"
			);

		$dbs = db::query("
			SELECT	*
			FROM	profiles
			ORDER BY lower(profile_name) DESC
			LIMIT	:num
			OFFSET	:offset
			;",
			array(
				'num' => $num,
				'offset' => $offset,
				)
			);

		while ( $row = $dbs->get_row() )
		{
			$this->data[] = new profile($row);
		}

		$this->max_page = $total / $num;

		$this->max_page = intval(ceil($this->max_page));
	} # map()


	#
	# wire()
	#

	public function wire(&$args)
	{
		css::load('/system/css/system.css');
	} # wire()


	#
	# title()
	#

	public function title()
	{
		echo self::$captions->get('profiles');
	} # title()


	#
	# html()
	#

	public function html(&$args)
	{
		echo '<h2>';
		$this->title();
		echo '</h2>';

		echo '<div class="dataset">';

		echo '<table>'
			. '<thead>'
			. '<tr>'
				. '<th>' . self::$captions->get('profile_name') . '</th>'
				. '<th style="width: 50px;">&nbsp;</th>'
			. '</tr>'
			. '</thead>';

		$this->list_pages();

		echo '<tbody>';

		$i = 0;

		foreach ( $this->data as $profile )
		{
			echo '<tr valign="top"' . ( ( $i % 2 ) ? ' class="alt"' : '' )  . '>'
				. '<td>' . $profile->profile_name . '</td>'
				. '<td align="center">'
					. '<a href="' . str::attr(permalink::get(array('cmd' => 'edit_profile', 'profile_id' => $profile->profile_id))) . '">'
					. self::$captions->get('edit')
					. '</a>'
				. '</td>'
				. '</tr>';

			$i++;
		}

		echo '</tbody>'
			. '</table>'
			. '</div>';
	} # html()


	#
	# list_pages()
	#

	public function list_pages()
	{
		echo '<tfoot>'
			. '<tr>'
			. '<td style="text-align: right;" colspan="3">';

		if ( $this->max_page > 1 )
		{
			echo self::$captions->get('page') . ':';

			$i = 0;

			$first = 1;
			$last = $this->max_page;
			$radius = 2;
			$edge_radius = 2;

			while ( ++$i <= $this->max_page )
			{
				if ( $this->max_page > 10 )
				{
					if ( abs($i - $this->page) > $radius )
					{
						if ( abs($i - ( $first - 1 )) <= 2 * $radius + $edge_radius + 2 )
						{
							if ( abs($this->page - ( $first - 1 ) ) > $radius + $edge_radius + 2 )
							{
								if ( abs($i - ( $first - 1 )) == $edge_radius + 1 )
								{
									#echo ' <b style="color: gold">' . $i . '</b>';
									echo ' ...';
									continue;
								}
								elseif ( abs($i - ( $first - 1 )) > $edge_radius )
								{
									#echo ' <b style="color: gainsboro">' . $i . '</b>';
									continue;
								}
							}
						}
						elseif ( abs($i - ( $last + 1 )) <= 2 * $radius + $edge_radius + 2 )
						{
							if ( abs($this->page - ( $last + 1 ) ) > $radius + $edge_radius + 2 )
							{
								if ( abs($i - ( $last + 1 )) == $edge_radius + 1 )
								{
									#echo ' <b style="color: gold">' . $i . '</b>';
									echo ' ...';
									continue;
								}
								elseif ( abs($i - ( $last + 1 )) > $edge_radius )
								{
									#echo ' <b style="color: gainsboro">' . $i . '</b>';
									continue;
								}
							}
						}
						else
						{
							#echo ' <b style="color: gainsboro">' . $i . '</b>';
							continue;
						}
					}
				}


				echo ' ';

				if ( $i == $this->page )
				{
					echo $i;
				}
				else
				{
					echo '<a href="'
						. str::attr(permalink::get(array('cmd' => 'profiles', 'page' => ( $i != 1 ? $i : null ))))
						. '">' . $i . '</a>';
				}
			}
		}
		else
		{
			echo '&nbsp;';
		}

		echo '</td>'
			. '</tr>'
			. '</tfoot>';
	} # list_pages()
} # profile_list

profile_list::init();
?>