function fade_bookmark_buttons_in(div_id)
{
	// assign a default id if necessary
	// flag the form as active
	document.getElementById(div_id).isActive = true;

	// only consider the latest in a multi-mouse over/out scenario
	document.getElementById(div_id).activeCall = bookmark_buttons_next_hash();

	// set show admin bar timeout
	var timeout_handler = 'show_bookmark_buttons(\'' + div_id + '\', \'' + document.getElementById(div_id).activeCall + '\');';
	setTimeout(timeout_handler, 50);
} // fade_bookmark_buttons_in()


function fade_bookmark_buttons_out(div_id)
{
	// flag the form as inactive
	document.getElementById(div_id).isActive = false;

	// only consider the latest in a multi-mouse over/out scenario
	document.getElementById(div_id).activeCall = bookmark_buttons_next_hash();

	// set hide admin bar timeout
	var timeout_handler = 'hide_bookmark_buttons(\'' + div_id + '\', \'' + document.getElementById(div_id).activeCall + '\');';
	setTimeout(timeout_handler, 300);
} // end fade_bookmark_buttons_out()


function show_bookmark_buttons(div_id, div_id_call)
{
	// if we're on the active form
	if ( document.getElementById(div_id).isActive )
	{
		document.getElementById(div_id).style.display = 'block';
	}
} // end show_bookmark_buttons()


function hide_bookmark_buttons(div_id, div_id_call)
{
	// if we're no longer on the active form
	if ( !document.getElementById(div_id).isActive
		&& ( document.getElementById(div_id).activeCall == div_id_call )
		)
	{
		document.getElementById(div_id).style.display = 'none';
	}
} // end hide_bookmark_buttons()


function bookmark_buttons_next_hash()
{
	var new_hash = '';
	var digits = '0123456789abcdef';

	while ( new_hash.length < 32 )
	{
		start = Math.floor(Math.random() * digits.length);
		end = start + 1;
		new_hash += digits.substring(start, end);
	}

	return new_hash;
} // end bookmark_buttons_next_hash()