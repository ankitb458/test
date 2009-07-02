<?php
/*
Plugin Name: My Category Order
Plugin URI: http://www.geekyweekly.com/mycategoryorder
Description: A Wordpress plugin to finally let you set the explicit order of post categories. Modified by tmuka to add a category widget supporting ordering.
Version: 2.3 (fork)
Author: froman118
Author URI: http://www.geekyweekly.com
Author Email: froman118@gmail.com
*/

function mycategoryorder_init() {

    function mycategoryorder_menu()
    {   if (function_exists('add_submenu_page')) {
	$location = "../wp-content/plugins/";
        add_submenu_page("edit.php", 'My Category Order', 'My Category Order', 'manage_categories',"mycategoryorder",'mycategoryorder');
    }

    }
    function mycategoryorder_js_libs() {
	if ( $_GET['page'] == "mycategoryorder" ) {
	    wp_enqueue_script('scriptaculous');
	}
    }
    add_action('admin_menu', 'mycategoryorder_menu');
    add_action('admin_menu', 'mycategoryorder_js_libs');

    function mycategoryorder()
    {

	global $wpdb;
	$mode = "";
	$mode = $_GET['mode'];
	$parentID = 0;
	if (isset($_GET['parentID']))
	    $parentID = $_GET['parentID'];

	$query = mysql_query("SHOW COLUMNS FROM $wpdb->categories LIKE 'cat_order'") or die(mysql_error());

	if (mysql_num_rows($query) == 0) {
	    $wpdb->query("ALTER TABLE $wpdb->categories ADD `cat_order` INT( 4 ) NOT NULL DEFAULT '0'");
	}

	if($mode == "act_OrderCategories")
	{  $idString = $_GET['idString'];
	$catIDs = explode(",", $idString);
	$result = count($catIDs);
	for($i = 0; $i < $result; $i++)
	{
	    $wpdb->query("UPDATE $wpdb->categories SET cat_order = '$i' WHERE cat_id ='$catIDs[$i]'");
	}
	}
	else
	{
	    $subCatStr = "";
	    $results=$wpdb->get_results("SELECT * FROM $wpdb->categories WHERE ( category_count > 0 or link_count = 0 ) and category_parent = $parentID ORDER BY cat_order ASC");

	    foreach($results as $row)
	    {
		$catCount=$wpdb->get_row("SELECT count(*) as catCount FROM $wpdb->categories WHERE ( category_count > 0 or link_count = 0 ) and category_parent = $row->cat_ID ", ARRAY_N);

		if($catCount[0] > 0)
		    $subCatStr = $subCatStr."<option value='$row->cat_ID'>$row->cat_name</option>";
	    }
?>
	<div class='wrap'>
	<h2>My Category Order</h2>
	<p>Choose a category from the drop down to order subcategories in that category or order the categories on this level by dragging and dropping them into the desired order.</p>

<?php
	    if($parentID != 0)
	    {
		$parentsParent = $wpdb->get_row("SELECT category_parent FROM $wpdb->categories WHERE cat_ID = $parentID ", ARRAY_N);
		echo "<a href='edit.php?page=mycategoryorder&parentID=$parentsParent[0]'>Return to parent category</a>";
	    }

	    if($subCatStr != "")
	    { ?>
	<h3>Order Subcategories</h3>
	<select id="cats" name="cats"><?php
		echo $subCatStr
?></select>
	&nbsp;<input type="button" name="edit" Value="Order Subcategories" onClick="javascript:goEdit();">
<?php }
	$results=$wpdb->get_results("SELECT * FROM $wpdb->categories WHERE ( category_count > 0 or link_count = 0 ) and category_parent = $parentID ORDER BY cat_order ASC"); ?>
	<h3>Order Categories</h3>
	    <div id="order" style="width: 500px; margin:10px 10px 10px 0px; padding:10px; border:1px solid #B2B2B2;"><?php
foreach($results as $row)
{
    echo "<div id='item_$row->cat_ID' class='lineitem'>$row->cat_name</div>";
}?>
	</div>

	<input type="button" id="orderButton" Value="Click to Order Categories" onclick="javascript:orderCats();">&nbsp;&nbsp;<strong id="updateText"></strong>

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

	function orderCats() {

	    $("orderButton").style.display = "none";
	    $("updateText").innerHTML = "Updating Category Order...";
	    var alerttext = '';
	    var order = Sortable.serialize('order');
	    alerttext = Sortable.sequence('order');

	    new Ajax.Request('edit.php?page=mycategoryorder&mode=act_OrderCategories&idString='+alerttext, {
		onSuccess: function(){
		    new Effect.Highlight('order', {startcolor:'#F9FC4A', endcolor:'#CFEBF7',restorecolor:'#CFEBF7', duration: 1.5, queue: 'front'})
			new Effect.Highlight('order', {startcolor:'#CFEBF7', endcolor:'#ffffff',restorecolor:'#ffffff', duration: 1.5, queue: 'end'})
			$("updateText").innerHTML = "Categories updated successfully.";
		    $("orderButton").style.display = "inline";
		}
	    });
	    return false;
	}
	function goEdit ()
	{
	    if($("cats").value != "")
		location.href="edit.php?page=mycategoryorder&mode=dsp_OrderCategories&parentID="+$("cats").value;
	}

	</script>


<?php
    }

    if ( function_exists('register_sidebar_widget') && function_exists('register_widget_control') ){

    function wp_widget_mycategoryorder($args) {
	extract($args);
	$options = get_option('widget_mycategoryorder');
	$c = $options['count'] ? '1' : '0';
	$h = $options['hierarchical'] ? '1' : '0';
	$d = $options['dropdown'] ? '1' : '0';
	$title = empty($options['title']) ? __('Categories') : $options['title'];

	echo $before_widget;
	echo $before_title . $title . $after_title;

	$cat_args = "orderby=order&order=ASC&show_count={$c}&hierarchical={$h}";

	if($d) {
	    wp_dropdown_categories($cat_args . '&show_option_none= ' . __('Select Category'));
?>
	    <script lang='javascript'><!--
	    var dropdown = document.getElementById("cat");
	    function onCatChange() {
		if ( dropdown.options[dropdown.selectedIndex].value > 0 ) {
		    location.href = "<?php echo get_option('siteurl'); ?>/?cat="+dropdown.options[dropdown.selectedIndex].value;
		}
	    }
	    dropdown.onchange = onCatChange;
	    --></script>

<?php
	} else {
?>
	    <ul>
<?php wp_list_categories($cat_args . '&title_li=');
?>
</ul>
<?php
	}

	echo $after_widget;
    }

    function wp_widget_mycategoryorder_control() {
	$options = $newoptions = get_option('widget_mycategoryorder');
	if ( $_POST['menu-submit'] ) {
	    $newoptions['count'] = isset($_POST['menu-count']);
	    $newoptions['hierarchical'] = isset($_POST['menu-hierarchical']);
	    $newoptions['dropdown'] = isset($_POST['menu-dropdown']);
	    $newoptions['title'] = strip_tags(stripslashes($_POST['menu-title']));
	}
	if ( $options != $newoptions ) {
	    $options = $newoptions;
	    update_option('widget_mycategoryorder', $options);
	}
	$count = $options['count'] ? 'checked="checked"' : '';
	$hierarchical = $options['hierarchical'] ? 'checked="checked"' : '';
	$dropdown = $options['dropdown'] ? 'checked="checked"' : '';
	$title = attribute_escape($options['title']);
?>
	<p><label for="menu-title"><?php _e('Title:'); ?> <input style="width: 250px;" id="menu-title" name="menu-title" type="text" value="<?php echo $title; ?>" /></label></p>
	    <p style="text-align:right;margin-right:40px;"><label for="menu-count"><?php _e('Show post counts'); ?> <input class="checkbox" type="checkbox" <?php echo $count; ?> id="menu-count" name="menu-count" /></label></p>
	    <p style="text-align:right;margin-right:40px;"><label for="menu-hierarchical" style="text-align:right;"><?php _e('Show hierarchy'); ?> <input class="checkbox" type="checkbox" <?php echo $hierarchical; ?> id="menu-hierarchical" name="menu-hierarchical" /></label></p>
	    <p style="text-align:right;margin-right:40px;"><label for="menu-dropdown" style="text-align:right;"><?php _e('Display as a drop down'); ?> <input class="checkbox" type="checkbox" <?php echo $dropdown; ?> id="menu-dropdown" name="menu-dropdown" /></label></p>
	    <input type="hidden" id="menu-submit" name="menu-submit" value="1" />
<?php
    }
}

$class['classname'] = 'widget_categories';
#wp_register_sidebar_widget('mycategoryorder', 'My Category Order', 'wp_widget_mycategoryorder', $class);
#wp_register_widget_control('mycategoryorder', 'My Category Order', 'wp_widget_mycategoryorder_control');

}

/* Delays plugin execution until Dynamic Sidebar has loaded first. */
add_action('plugins_loaded', 'mycategoryorder_init');

?>