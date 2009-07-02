<?php
#
# improve_semiologic()
#

function improve_semiologic()
{
	if ( sem_pro && current_user_can('administrator') )
	{
		echo '<div id="improve_semiologic">'
			. '<a href="http://www.semiologic.com/resources/">' . __('Docs &amp; Resources') . '</a>'
			. ' &bull; '
			. '<a href="http://forum.semiologic.com">' . __('Community') . '</a>'
			. ' &bull; '
			. '<a href="http://www.semiologic.com/partners/">' . __('Partners') . '</a>'
			. '</div>';
	}
} # end enhance_semiologic()

add_action('admin_footer', 'improve_semiologic');


#
# improve_semiologic_css()
#

function improve_semiologic_css()
{
?><style type='text/css'>
#dolly,
#bh
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


function get_pro_feature_notice()
{
	return '<div class="error"><p>'
	. __('This is a <a href="http://www.getsemiologic.com">Semiologic Pro</a> feature. <a href="mailto:sales@semiologic.com">Request a demo</a>.')
	. '</p></div>';
} # end pro_feature_notice()


function pro_feature_notice()
{
	echo get_pro_feature_notice();
} # end pro_feature_notice()
?>