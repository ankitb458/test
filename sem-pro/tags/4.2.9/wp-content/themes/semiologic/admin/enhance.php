<?php
#
# improve_semiologic()
#

function improve_semiologic()
{
	echo '<div id="improve_semiologic">';
	echo '<a href="http://forum.semiologic.com">' . __('Community') . '</a>';
	echo ' | ';
	echo '<a href="mailto:suggestions@semiologic.com">' . __('Bugs &amp; Suggestions') . '</a>';
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
?><style type='text/css'>
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


function pro_feature_notice()
{
	echo '<div class="error"><p>'
	. __('This is a <a href="http://www.semiologic.com/solutions/sem-theme-pro/">Semiologic Pro Theme</a> feature. <a href="mailto:sales@semiologic.com">Request a demo</a>.')
	. '</p></div>';
} # end pro_feature_notice()
?>