<?php
#
# MC User List
# ------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class user_list extends request
{
	protected static $captions;
	protected $max_page;
	protected $page;
	protected $letter;
	protected $profile_id;
	protected $s;


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
		if ( !active_user::can('manage_users') )
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
		$this->page = @ $this->args['page'];
		$this->page = isset($this->page) ? intval($this->page) : 1;

		$this->letter = @ $this->args['letter'];
		$this->profile_id = isset($this->args['profile_id']) ? intval($this->args['profile_id']) : null;
		$this->s = @ $this->args['s'];
		
		$num = 50;
		$offset = ( $this->page - 1 ) * $num;
		
		if ( isset($this->s) )
		{
			# quick and dirty search
			$total = 1;
			
			if ( !$this->profile_id )
			{
				# node data
				$dbs = db::query("
					SELECT	user_id,
							user_name,
							user_phone,
							user_email
					FROM	users
					WHERE	( to_tsvector(user_name)
							|| to_tsvector(user_email::varchar)
							|| to_tsvector(translate(user_email::varchar, '@.-_', '    '))
							|| to_tsvector(user_paypal::varchar)
							|| to_tsvector(translate(user_paypal::varchar, '@.-_', '    '))
							|| to_tsvector(user_key::varchar)
							) @@ plainto_tsquery(:s)
					;",
					array(
						's' => $this->s,
						)
					);
			}
			else
			{
				# node data
				$dbs = db::query("
					SELECT	users.user_id,
							user_name,
							user_phone,
							user_email
					FROM	users
					JOIN	user2profile
					ON		user2profile.user_id = users.user_id
					AND		user2profile.profile_id = :profile_id
					WHERE	( to_tsvector(user_name)
							|| to_tsvector(user_email::varchar)
							|| to_tsvector(translate(user_email::varchar, '@.-_', '    '))
							|| to_tsvector(user_paypal::varchar)
							|| to_tsvector(translate(user_paypal::varchar, '@.-_', '    '))
							|| to_tsvector(user_key::varchar)
							) @@ plainto_tsquery(:s)
					;",
					array(
						's' => $this->s,
						'profile_id' => $this->profile_id,
						)
					);
			}
		}
		elseif ( isset($this->letter) )
		{
			if ( !$this->profile_id )
			{
				$total = db::get_var("
					SELECT	COUNT(*)
					FROM	users
					WHERE	upper(substring(user_name from 1 for 1)) = :letter
					;",
					array(
						'letter' => $this->letter,
						)
					);

				# node data
				$dbs = db::query("
					SELECT	user_id,
							user_name,
							user_phone,
							user_email
					FROM	users
					WHERE	upper(substring(user_name from 1 for 1)) = :letter
					ORDER BY lower(user_name)
					LIMIT	:num
					OFFSET	:offset
					;",
					array(
						'num' => $num,
						'offset' => $offset,
						'letter' => $this->letter,
						)
					);
			}
			else
			{
				$total = db::get_var("
					SELECT	COUNT(*)
					FROM	users
					JOIN	user2profile
					ON		user2profile.user_id = users.user_id
					AND		user2profile.profile_id = :profile_id
					WHERE	upper(substring(user_name from 1 for 1)) = :letter
					;",
					array(
						'letter' => $this->letter,
						'profile_id' => $this->profile_id,
						)
					);

				# node data
				$dbs = db::query("
					SELECT	users.user_id,
							user_name,
							user_phone,
							user_email
					FROM	users
					JOIN	user2profile
					ON		user2profile.user_id = users.user_id
					AND		user2profile.profile_id = :profile_id
					WHERE	upper(substring(user_name from 1 for 1)) = :letter
					ORDER BY lower(user_name)
					LIMIT	:num
					OFFSET	:offset
					;",
					array(
						'num' => $num,
						'offset' => $offset,
						'letter' => $this->letter,
						'profile_id' => $this->profile_id,
						)
					);
			}
		}
		else
		{
			if ( !$this->profile_id )
			{
				$total = db::get_var("
					SELECT	COUNT(*)
					FROM	users
					");

				# node data
				$dbs = db::query("
					SELECT	user_id,
							user_name,
							user_phone,
							user_email
					FROM	users
					ORDER BY lower(user_name)
					LIMIT	:num
					OFFSET	:offset
					;",
					array(
						'num' => $num,
						'offset' => $offset,
						)
					);
			}
			else
			{
				$total = db::get_var("
					SELECT	COUNT(*)
					FROM	users
					JOIN	user2profile
					ON		user2profile.user_id = users.user_id
					AND		user2profile.profile_id = :profile_id
					",
					array(
						'profile_id' => $this->profile_id,
						)
					);

				# node data
				$dbs = db::query("
					SELECT	users.user_id,
							user_name,
							user_phone,
							user_email
					FROM	users
					JOIN	user2profile
					ON		user2profile.user_id = users.user_id
					AND		user2profile.profile_id = :profile_id
					ORDER BY lower(user_name)
					LIMIT	:num
					OFFSET	:offset
					;",
					array(
						'num' => $num,
						'offset' => $offset,
						'profile_id' => $this->profile_id,
						)
					);
			}
		}

		$this->data = array();
		
		while ( $row = $dbs->get_row() )
		{
			$this->data[] = new user($row);
		}

		$this->max_page = $total / $num;

		$this->max_page = intval(ceil($this->max_page));
	} # map()


	#
	# wire()
	#

	public function wire()
	{
		css::load('/system/css/system.css');
	} # wire()


	#
	# title()
	#

	public function title()
	{
		echo self::$captions->get('users');
	} # title()


	#
	# html()
	#

	public function html()
	{
		$this->display_search();
		
		echo '<h2>';
		$this->title();
		echo '</h2>';

		echo '<div class="dataset">';
		
		$this->list_letters();

		echo '<table>'
			. '<thead>'
			. '<tr>'
				. '<th>' . self::$captions->get('user_name') . ' / ' . self::$captions->get('user_email') . '</th>'
				. '<th style="width: 150px;">' . self::$captions->get('user_phone') . '</th>'
				. '<th style="width: 50px;">&nbsp;</th>'
			. '</tr>'
			. '</thead>';

		$this->list_pages();

		echo '<tbody>';

		$i = 0;

		foreach ( $this->data as $user )
		{
			echo '<tr valign="top"' . ( ( $i % 2 ) ? ' class="alt"' : '' )  . '>'
				. '<td colspan="2">'
					. $user->user_name
				. '</td>'
				. '<td align="center" rowspan="2">'
					. '<a href="' . str::attr(permalink::get(array('cmd' => 'edit_user', 'user_id' => $user->user_id))) . '">'
					. self::$captions->get('edit')
					. '</a>'
				. '</td>'
				. '</tr>'
				. '<tr' . ( ( $i % 2 ) ? ' class="alt"' : '' )  . '>'
				. '<td>'
					. '<a href="mailto:' . str::attr($user->user_email) . '">'
					. $user->user_email
					. '</a>'
				. '</td>'
				. '<td>'
					. ( $user->user_phone
						? ( $user->user_phone )
						: '&nbsp;'
						)
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
						. str::attr(permalink::get(array('cmd' => 'users', 'page' => ( $i != 1 ? $i : null ), 'letter' => $this->letter, 'profile_id' => $this->profile_id)))
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


	#
	# list_letters()
	#

	public function list_letters()
	{
		if ( $this->s ) return;
		
		if ( !$this->profile_id )
		{
			$dbs = db::query("
				SELECT	DISTINCT upper(substring(user_name from 1 for 1)) as letter
				FROM	users
				ORDER BY letter
				");
		}
		else
		{
			$dbs = db::query("
				SELECT	DISTINCT upper(substring(user_name from 1 for 1)) as letter
				FROM	users
				JOIN	user2profile
				ON		user2profile.user_id = users.user_id
				AND		user2profile.profile_id = :profile_id
				ORDER BY letter
				", array(
					'profile_id' => $this->profile_id,
					)
				);
			
		}

		$dbs->bind('letter', $letter);

		echo '<table>'
			. '<tr align="center">';

		if ( !isset($this->letter) )
		{
			echo '<td>'
				. self::$captions->get('all')
				. '</td>';
		}
		else
		{
			echo '<td>'
				. '<a href="' . str::attr(permalink::get(array('cmd' => 'users', 'profile_id' => $this->profile_id))) . '">'
				. self::$captions->get('all')
				. '</a>'
				. '</td>';
		}

		while ( $dbs->get_row() )
		{
			if ( $letter == $this->letter )
			{
				echo '<td>'
					. $letter
					. '</td>';
			}
			else
			{
				echo '<td>'
					. '<a href="' . str::attr(permalink::get(array('cmd' => 'users', 'letter' => $letter, 'profile_id' => $this->profile_id))) . '">'
					. $letter
					. '</a>'
					. '</td>';
			}
		}

		echo '</tr>'
			. '</table>';
	} # list_letters()
	
	
	#
	# display_profiles()
	#
	
	public function display_search()
	{
		echo '<div style="float: right;">'
			. '<form method="' . str::attr(site_url) . '">'
			. '<select onchange="document.location = this.value;">'
			. '<option value="' . str::attr(permalink::get(array('cmd' => 'users'))) . '"'
				. ( !$this->profile_id
					? ' selected="selected"'
					: ''
					)
				. '>'
				. 'All Users'
				. '</option>';
		
		$profiles = db::get_results("
			SELECT	profile_id,
					profile_name
			FROM	profiles
			ORDER BY profile_name
			");
		
		foreach ( $profiles as $profile )
		{
			echo '<option value="' . str::attr(permalink::get(array('cmd' => 'users', 'profile_id' => $profile['profile_id']))) . '"'
				. ( $this->profile_id == $profile['profile_id']
					? ' selected="selected"'
					: ''
					)
				. '>'
				. $profile['profile_name']
				. '</option>';
		}
		
		echo '</select>'
			. '&nbsp;-&nbsp;'
			. '<input type="hidden" name="cmd" value="users" />'
			. '<input type="text" name="s" />'
			. '<input type="submit" value="Search" />'
			. '</form>'
			. '</div>';
	} # display_search()
} # user_list

user_list::init();
?>