<?php
#
# MC Docs Widget
# -------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class docs_widget extends widget
{
	protected static $captions;


	#
	# init()
	#

	public static function init()
	{
		self::$captions =& new captions('docs');
	} # init()


	#
	# wire()
	#

	public function wire(&$args)
	{
	} # wire()


	#
	# html()
	#

	public function html(&$args)
	{
		if ( !active_user::can('manage_docs', 'edit_docs') )
		{
			return;
		}

		echo $this->begin();

		echo $this->begin_title()
			. self::$captions->get('docs')
			. $this->end_title();

		echo $this->begin_content();

		echo '<ul>';

		if ( active_user::can('manage_docs') )
		{
			echo '<li>'
 				. '<a href="' . str::attr(permalink::get(array('cmd' => 'doc_cats'))) . '">'
					. self::$captions->get('doc_cats')
					. '</a>'
				. '</li>';
		}

		echo '<li>'
			. '<a href="' . str::attr(permalink::get(array('cmd' => 'docs'))) . '">'
				. self::$captions->get('docs')
				. '</a>'
			. '</li>';

		if ( active_user::can('manage_docs') )
		{
			echo '<li>'
				. ( $args['cmd'] != 'new_doc_cat'
					? ( '<a href="' . str::attr(permalink::get(array('cmd' => 'new_doc_cat'))) . '">'
						. self::$captions->get('new_doc_cat')
						. '</a>'
						)
					: self::$captions->get('new_doc_cat')
					)
				. '</li>';

			echo '<li>'
				. ( $args['cmd'] != 'new_doc'
					? ( '<a href="' . str::attr(permalink::get(array('cmd' => 'new_doc'))) . '">'
						. self::$captions->get('new_doc')
						. '</a>'
						)
					: self::$captions->get('new_doc')
					)
				. '</li>';
		}

		echo '</ul>';

		echo $this->end_content();

		echo $this->end();
	} # html()
} # docs_widget

docs_widget::init();
?>