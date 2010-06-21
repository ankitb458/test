<?php
/**
 * Admin functionality
 **/
class sem_admin
{
	/**
	 * Admin header image CSS.
	 *
	 * @return void
	 **/
	function header_css() {
		?>
<style type="text/css">
#headimg {
	display: table !important;
}

#headimg h1 {
	display: table-cell;
	vertical-align: middle;
	height: 80px;
	padding: 0 0 0 20px;
	margin: 0;
}

#headimg a {
	text-decoration: none;
}

#headimg #desc {
	display: none !important;
}
</style>
<?php
	}
} // END class sem_admin
?>