1. Unzip mycategoryorder.zip
2. Move mycategoryorder.php to /wp-content/plugins/
3. Activate the My Category Order plugin on the Plugins menu
4. Go to the "My Category Order" tab under Manage and specify your desired
   order for post categories
5. Modify sidebar template to use correct filter (additional parameter seperated by ampersands):
	wp_list_categories('orderby=order&optioncount=1&hierarchical=0&title_li=<h2>Categories</h2>');

http://www.geekyweekly.com/mycategoryorder
froman118@gmail.com