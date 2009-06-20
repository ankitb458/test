<?php
#
# MC Email Library
# ----------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class email
{
	public $to;
	public $from;
	public $title;
	public $message;


	#
	# __construct()
	#

	public function __construct()
	{
		$this->from = options::get('site_email');
	} # __construct()


	#
	# send()
	#

	public function send()
	{
		if ( !iis )
		{
			$this->headers = "From: $this->from";

			mail(
				$this->to,
				$this->title,
				$this->message,
				$this->headers
				);
		}
		else
		{
			new notice(
				'<pre>'
				. "From: $this->from" . "\n"
				. "To:   $this->to" . "\n\n"
				. $this->title . "\n\n"
				. $this->message
				. '<pre>'
				);
		}
	} # send()
} # email
?>