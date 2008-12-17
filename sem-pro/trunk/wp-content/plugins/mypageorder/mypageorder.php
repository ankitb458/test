<?php
/*
My Page Order
http://www.geekyweekly.com/mypageorder
by froman118 <froman118@gmail.com>
*/

function mypageorder_menu()
{
	add_pages_page('Page Order', 'Page Order', 'edit_pages',"mypageorder",'mypageorder');
}
function mypageorder_js_libs() {
    if ( $_GET['page'] == "mypageorder" ) {
		wp_enqueue_script('scriptaculous');
	}
}

add_action('admin_menu', 'mypageorder_menu');
add_action('admin_menu', 'mypageorder_js_libs');

function mypageorder()
{
global $wpdb;
$mode = "";
$mode = $_GET['mode'];
$parentID = 0;
if (isset($_GET['parentID']))
	$parentID = $_GET['parentID'];

if($mode == "act_OrderPages")
{
	$idString = $_GET['idString'];
	$IDs = explode(",", $idString);
	$result = count($IDs);

	for($i = 0; $i < $result; $i++)
	{
		$j = $i + 1;
		$wpdb->query("UPDATE $wpdb->posts SET menu_order = '$j' WHERE id ='$IDs[$i]'");
    }
}
else
{
	$subPageStr = "";
	$results=$wpdb->get_results("SELECT * FROM $wpdb->posts WHERE post_parent = $parentID and post_type = 'page' and post_status='publish' ORDER BY menu_order ASC");
	foreach($results as $row)
	{
		$postCount=$wpdb->get_row("SELECT count(*) as postsCount FROM $wpdb->posts WHERE post_parent = $row->ID and post_status='publish' and post_type = 'page' ", ARRAY_N);
		if($postCount[0] > 0)
	    	$subPageStr = $subPageStr."<option value='$row->ID'>$row->post_title</option>";
	}
?>
<div class='wrap'>
	<h2>My Page Order</h2>
	<p>Choose a page from the drop down to order its subpages or order the pages on this level by dragging and dropping them into the desired order.</p>

<?php
	if($parentID != 0)
	{
		$parentsParent = $wpdb->get_row("SELECT post_parent FROM $wpdb->posts WHERE ID = $parentID ", ARRAY_N);
		echo "<a href='edit.php?page=mypageorder&parentID=$parentsParent[0]'>Return to parent page</a>";
	}
 if($subPageStr != "") { ?>
	<h3>Order Subpages</h3>
	<select id="pages" name="pages"><?php
		echo $subPageStr;
	?>
	</select>
	&nbsp;<input type="button" name="edit" Value="Order Subpages" onClick="javascript:goEdit();">
<?php } ?>

	<h3>Order Pages</h3>
	<div id="order" style="width: 500px; margin:10px 10px 10px 0px; padding:10px; border:1px solid #B2B2B2;"><?php
	foreach($results as $row)
	{
		echo "<div id='item_$row->ID' class='lineitem'>$row->post_title</div>";
	}?>
	</div>

	<input type="button" id="orderButton" Value="Click to Order Pages" onclick="javascript:orderPages();">&nbsp;&nbsp;<strong id="updateText"></strong>

<?php
}
?>
</div>

<style>
	div.lineitem {
		margin: 3px 0px;
		padding: 2px 5px 2px 5px;
		background-color: #F1F1F1;
		border:1px solid #B2B2B2;
		cursor: move;
	}
</style>

<script type="text/javascript">
	Sortable.create('order',{tag:'div'});

	function orderPages() {

		$("orderButton").style.display = "none";
		$("updateText").innerHTML = "Updating Page Order...";
		var alerttext = '';
		var order = Sortable.serialize('order');
		alerttext = Sortable.sequence('order');

		new Ajax.Request('edit.php?page=mypageorder&mode=act_OrderPages&idString='+alerttext, {
		 onSuccess: function(){
      			new Effect.Highlight('order', {startcolor:'#F9FC4A', endcolor:'#CFEBF7',restorecolor:'#CFEBF7', duration: 1.5, queue: 'front'})
				new Effect.Highlight('order', {startcolor:'#CFEBF7', endcolor:'#ffffff',restorecolor:'#ffffff', duration: 1.5, queue: 'end'})
				$("updateText").innerHTML = "Pages updated successfully.";
				$("orderButton").style.display = "inline";
   			 }
		  });
		return false;
	}
	function goEdit ()
    {
		if($("pages").value != "")
			location.href="edit.php?page=mypageorder&mode=dsp_OrderPages&parentID="+$("pages").value;
	}

</script>
<?php
}
?>