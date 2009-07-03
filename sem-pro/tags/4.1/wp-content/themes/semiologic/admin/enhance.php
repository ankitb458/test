<?php
#
# improve_semiologic()
#

function improve_semiologic()
{
	echo '<div id="improve_semiologic">';
	echo '<a href="mailto:suggestions@semiologic.com">' . __('Report a Bug') . '</a>';
	echo ' | ';
	echo '<a href="http://www.semiologic.com/partners/">' . __('Promote Semiologic') . '</a>';
	echo '</div>';
} # end enhance_semiologic()

add_action('admin_footer', 'improve_semiologic');


#
# improve_semiologic_css()
#

function improve_semiologic_css()
{
?>
<style type='text/css'>
#dolly
{
	display: none;
}

#improve_semiologic
{
	position: absolute;
	top: 3em;
	margin: 0;
	padding: 0;
	right: 1em;
	font-size: small;
	color: #f1f1f1;
}

#improve_semiologic a
{
	color: gold;
}

#improve_semiologic a:hover
{
	color: #f1f1f1;
}
</style>
<?php
} # end improve_semiologic_css()

add_action('admin_head', 'improve_semiologic_css');
?>