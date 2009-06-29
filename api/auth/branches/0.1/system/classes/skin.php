<?php
#
# MC Skin Ojbect
# --------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class skin
{
	protected $skin;


	#
	# __construct()
	#

	function __construct($skin)
	{
		$this->skin = $skin;
	} # __construct()


	#
	# wire()
	#

	public function wire(&$args)
	{
		foreach ( glob(mc_path . '/skins/' . $this->skin . '/{skin,custom}.css', GLOB_BRACE) as $file )
		{
			css::load($file);
		}
	} # wire()
} # skin
?>