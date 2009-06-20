<?php
#
# MC Doc List
# -----------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class doc_list extends request
{
	protected static $captions;
	protected $max_page;
	protected $page;
	protected $cat_id;


	#
	# init()
	#

	public static function init()
	{
		self::$captions =& new captions('docs');
	} # init()


	#
	# __construct()
	#

	public function __construct(&$args)
	{
		if ( isset($args['cat_id']) || isset($args['cat']) || isset($args['version']) )
		{
			if ( isset($args['cat_id']) )
			{
				$dbs = db::query("
					SELECT	cat_id,
							cat_key,
							cat_version
					FROM	doc_cats
					WHERE	cat_id = :cat_id
					",
					array(
						'cat_id' => $args['cat_id']
						)
					);
			}
			else
			{
				$dbs = db::query("
					SELECT	cat_id,
							cat_key,
							cat_version
					FROM	doc_cats
					WHERE	cat_key = :cat_key
					AND		cat_version = :cat_version
					",
					array(
						'cat_key' => $args['cat'],
						'cat_version' => $args['version'],
						)
					);
			}

			$dbs->bind('cat_id', $args['cat_id']);
			$dbs->bind('cat_key', $args['cat']);
			$dbs->bind('cat_version', $args['version']);

			if ( !$dbs->get_row() )
			{
				new status_404($args);
			}
		}

		if ( isset($args['output']) && $args['output'] != 'html' )
		{
			if ( !$args['cat_id'] || !active_user::can_access($args['cat_id']) )
			{
				new status_403($args);
			}
		}
		elseif ( !active_user::can('manage_docs', 'edit_docs') )
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
		$this->cat_id =& $this->args['cat_id'];

		$this->cat =& $this->args['cat'];
		$this->version =& $this->args['version'];

		$this->data = array();

		if ( $this->output == 'xml' )
		{
			$dbs = db::query("
				SELECT	cat_key,
						cat_version
				FROM	doc_cats
				WHERE	cat_id = :cat_id
				",
				array(
					'cat_id' => $this->cat_id,
					)
				);

			$dbs->bind('cat_key', $this->cat);
			$dbs->bind('cat_version', $this->version);

			$dbs->get_row();

			if ( isset($this->args['last_modified']) )
			{
				$dbs = db::query("
					SELECT	docs.*
					FROM	docs
					WHERE	docs.cat_id = :cat_id
					AND		doc_modified >= :last_modified
					ORDER BY lower(doc_name)
					;",
					array(
						'cat_id' => $this->cat_id,
						'last_modified' => $this->args['last_modified'],
						)
					);
			}
			else
			{
				$dbs = db::query("
					SELECT	*
					FROM	docs
					WHERE	docs.cat_id = :cat_id
					ORDER BY lower(doc_name)
					;",
					array(
						'cat_id' => $this->cat_id
						)
					);
			}

			while ( $row = $dbs->get_row() )
			{
				$this->data[] = new doc($row);
			}
		}
		else
		{
			$this->doc_status =& $this->args['doc_status'];

			$this->page =& $this->args['page'];
			$this->page = is_numeric($this->page) ? intval($this->page) : 1;

			$num = 50;
			$offset = ( $this->page - 1 ) * $num;

			$total = db::get_var("
				SELECT	COUNT(*)
				FROM	doc_revs
				INNER JOIN doc_cats
				ON		doc_cats.cat_id = doc_revs.cat_id
				WHERE	rev_id IN (
					SELECT	MAX(rev_id)
					FROM	doc_revs
					WHERE	doc_revs.cat_id = :cat_id OR (:cat_id2)::bigint IS NULL
					GROUP BY doc_id
					)
				AND		( doc_status = :doc_status OR (:doc_status2)::varchar IS NULL )
				;",
				array(
					'cat_id' => $this->cat_id,
					'cat_id2' => $this->cat_id,
					'doc_status' => $this->doc_status,
					'doc_status2' => $this->doc_status,
					)
				);

			$dbs = db::query("
				SELECT	*
				FROM	doc_revs
				INNER JOIN doc_cats
				ON		doc_cats.cat_id = doc_revs.cat_id
				WHERE	rev_id IN (
					SELECT	MAX(rev_id)
					FROM	doc_revs
					WHERE	doc_revs.cat_id = :cat_id OR (:cat_id2)::bigint IS NULL
					GROUP BY doc_id
					)
				AND		( doc_status = :doc_status OR (:doc_status2)::varchar IS NULL )
				ORDER BY lower(cat_name), lower(cat_version) DESC, lower(doc_name)
				LIMIT	:num
				OFFSET	:offset
				;",
				array(
					'cat_id' => $this->cat_id,
					'cat_id2' => $this->cat_id,
					'doc_status' => $this->doc_status,
					'doc_status2' => $this->doc_status,
					'num' => $num,
					'offset' => $offset,
					)
				);

			while ( $row = $dbs->get_row() )
			{
				$this->data[] = new doc($row);
			}

			$this->max_page = $total / $num;

			$this->max_page = intval(ceil($this->max_page));
		}
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
		echo self::$captions->get('docs');
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
				. '<th>' . self::$captions->get('doc_name') . '</th>';

		echo '<th style="width: 180px;" colspan="2">';
		$this->list_cats();
		echo '</th>';

		echo '<th style="width: 70px;">';
		$this->list_status();
		echo '</th>';

		echo '<th style="width: 50px;">&nbsp;</th>'
			. '</tr>'
			. '</thead>';

		$this->list_pages();

		echo '<tbody>';

		$i = 0;

		foreach ( $this->data as $doc )
		{
			echo '<tr valign="top"' . ( ( $i % 2 ) ? ' class="alt"' : '' )  . '>'
				. ( $this->cat_id
					? ( '<td colspan="3">' . $doc->doc_name . '</td>' )
					: ( '<td>' . $doc->doc_name . '</td>'
						. '<td>' . $doc->doc_cat->cat_name . '</td>'
						. '<td>' . $doc->doc_cat->cat_version . '</td>'
						)
					)
				. '<td>'
					. self::$captions->get($doc->doc_status)
				. '</td>'
				. '<td align="center">'
					. '<a href="' . str::attr(permalink::get(array('cmd' => 'edit_doc', 'doc_id' => $doc->doc_id))) . '">'
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
			. '<td style="text-align: right;" colspan="5">';

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
						. str::attr(permalink::get(array('cmd' => 'docs', 'page' => ( $i != 1 ? $i : null ), 'cat_id' => $this->cat_id)))
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
	# list_cats()
	#

	public function list_cats()
	{
		if ( isset($this->doc_status) )
		{
			$dbs = db::query("
				SELECT	doc_cats.*
				FROM	doc_cats
				WHERE	cat_id IN (
					SELECT	DISTINCT cat_id
					FROM	doc_revs
					WHERE	doc_status = :doc_status
					)
				ORDER BY lower(doc_cats.cat_name), lower(doc_cats.cat_version) DESC
				",
				array(
					'doc_status' => $this->doc_status,
					)
				);
		}
		else
		{
			$dbs = db::query("
				SELECT	doc_cats.*
				FROM	doc_cats
				WHERE	cat_id IN (
					SELECT	DISTINCT cat_id
					FROM	doc_revs
					)
				ORDER BY lower(doc_cats.cat_name), lower(doc_cats.cat_version) DESC
				");
		}

		echo '<select'
			. ' onchange="if ( this.value ) document.location=this.value;"'
			. ' style="width: 100%;"'
			. '>';

		if ( !$this->cat_id )
		{
			echo '<option selected="selected">'
				. self::$captions->get('doc_cat')
				. '</option>';
		}
		else
		{
			echo '<option value="'
				. str::attr(
					permalink::get(
						array(
							'cmd' => 'docs',
							'doc_status' => $this->doc_status,
							)
						)
					)
					. '">'
				. self::$captions->get('doc_cat')
				. '</option>';
		}

		while ( $row = $dbs->get_row() )
		{
			$doc_cat = new doc_cat($row);

			if ( $doc_cat->cat_id == $this->cat_id )
			{
				echo '<option selected="selected">'
					. $doc_cat
					. '</option>';
			}
			else
			{
				echo '<option value="'
					. str::attr(
						permalink::get(
							array(
								'cmd' => 'docs',
								'cat_id' => $doc_cat->cat_id,
								'doc_status' => $this->doc_status,
								)
							)
						)
						. '">'
					. $doc_cat
					. '</option>';
			}
		}

		echo '</select>';
	} # list_cats()


	#
	# list_status()
	#

	public function list_status()
	{
		echo '<select'
			. ' onchange="if ( this.value ) document.location=this.value;"'
			. ' style="width: 100%;"'
			. '>';

		if ( !$this->doc_status )
		{
			echo '<option selected="selected">'
				. self::$captions->get('doc_status')
				. '</option>';
		}
		else
		{
			echo '<option value="' . str::attr(
					permalink::get(
						array(
							'cmd' => 'docs',
							'cat_id' => $this->cat_id,
							)
						)
					)
				. '">'
				. self::$captions->get('doc_status')
				. '</option>';
		}

		if ( isset($this->cat_id) )
		{
			$valid = db::get_col("
				SELECT	DISTINCT doc_status
				FROM	doc_revs
				WHERE	cat_id = :cat_id
				",
				array(
					'cat_id' => $this->cat_id
					)
				);
		}
		else
		{
			$valid = db::get_col("
				SELECT	DISTINCT doc_status
				FROM	doc_revs
				"
				);
		}

		$doc_statuses = array('publish', 'pending', 'draft');

		foreach ( array_keys($doc_statuses) as $key )
		{
			if ( !in_array($doc_statuses[$key], $valid) )
			{
				unset($doc_statuses[$key]);
			}
		}

		foreach ( $doc_statuses as $doc_status )
		{
			if ( $doc_status == $this->doc_status )
			{
				echo '<option selected="selected">'
					. self::$captions->get($doc_status)
					. '</option>';
			}
			else
			{
				echo '<option value="'
					. str::attr(
						permalink::get(
							array(
								'cmd' => 'docs',
								'cat_id' => $this->cat_id,
								'doc_status' => $doc_status,
								)
							)
						)
					. '">'
					. self::$captions->get($doc_status)
					. '</option>';
			}
		}

		echo '</select>';
	} # list_status()


	#
	# xml()
	#

	public function xml()
	{
		echo '<docs'
			. ' cat="' . str::attr($this->cat) . '"'
			. ' version="' . str::attr($this->version) . '"'
			. ' >';

		foreach ( $this->data as $doc )
		{
			$foo = create_function('$in', 'return doc_list::autourl_callback($in, "' . $this->cat . '", "' . $this->version . '", "' . $doc->doc_key . '");');

			foreach ( array('doc_excerpt', 'doc_content') as $field )
			{
				$doc->{$field} = preg_replace_callback("/
					(
						\[.+\]
						:
						\s*?
					)
					(
						.+?
					)
					/iUx",
					$foo,
					$doc->{$field}
					);
				}

			echo '<doc>'
				. '<key>' . str::xml($doc->doc_key) . '</key>'
				. '<name>' . str::xml($doc->doc_name) . '</name>'
				. '<excerpt>' . str::cdata(strip_tags(str::markdown($doc->doc_excerpt), '<a><strong><em>')) . '</excerpt>'
				. '<content>' . str::cdata(str::markdown($doc->doc_content)) . '</content>'
				. '</doc>';
		}

		echo '</docs>';
	} # xml()


	#
	# autourl_callback()
	#

	function autourl_callback($input, $cat, $version, $key)
	{
		if ( strpos($input[2], '://') === false )
		{
			return $input[1] . 'http://oldbackend.semiologic.com/files/docs/' . $cat . '/' . $version . '/' . $key . '/' . $input[2];
		}
		else
		{
			return $input[0];
		}
	} # autourl_callback()
} # doc_list

doc_list::init();
?>