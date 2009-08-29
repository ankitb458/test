<?php
/*
Plugin Name: Category Cloud Widget
Plugin URI: http://dev.wp-plugins.org/browser/widget-category-cloud/
Description: Adds a sidebar widget to display the categories as a tag cloud. Based on <a href="http://www.hitormiss.org/projects/weighted-categories/">Matt Kingston's Weighted Categories</a> WP plugin.
Author: Lee Kelleher
Version: 1.2 (fork)
Author URI: http://vertino.wordpress.com/
*/

// This gets called at the plugins_loaded action
function widget_catcloud_init() {

        // Check for the required API functions
        if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
                return;

        // This saves options and prints the widget's config form.
        function widget_catcloud_control() {
                $options = $newoptions = get_option('widget_catcloud');
                if ( $_POST['catcloud-submit'] ) {
                        $newoptions['title'] = stripslashes(wp_filter_post_kses(strip_tags($_POST['catcloud-title'])));
                        $newoptions['small'] = ($_POST['catcloud-small'] != '') ? (int) $_POST['catcloud-small'] : 80;
                        $newoptions['big'] = ($_POST['catcloud-big'] != '') ? (int) $_POST['catcloud-big'] : 120;
                        $newoptions['unit'] = ($_POST['catcloud-unit'] != '') ? wp_filter_post_kses(strip_tags($_POST['catcloud-unit'])) : '%';
                        $newoptions['align'] = ($_POST['catcloud-align'] != '') ? wp_filter_post_kses(strip_tags($_POST['catcloud-align'])) : 'center';
                        //$newoptions['exclude'] = explode(',', trim(wp_filter_post_kses(stripslashes($_POST['catcloud-exclude']))));
                        preg_match_all("/\d+/", $_POST['catcloud-exclude'], $exclude_cats, PREG_PATTERN_ORDER); //explode(',', trim(stripslashes(wp_filter_post_kses(strip_tags($_POST['catcloud-exclude'])))));
                        $exclude_cats = end($exclude_cats);

                        // loop through each excluded cat id, check that it is numeric, otherwise omit
                        $exclude = '';
                        if ( count($exclude_cats) ) {
                                foreach ($exclude_cats as $exclude_cat) {
                                        $exclude_cat = trim($exclude_cat);
                                        if ( is_numeric($exclude_cat) )
                                                $exclude .= "$exclude_cat,";
                                }
                        }
                        $newoptions['exclude'] = ($exclude != '') ? substr($exclude, 0, -1) : '';
                }
                if ( $options != $newoptions ) {
                        $options = $newoptions;
                        update_option('widget_catcloud', $options);
                }
        ?>                        <div style="text-align:right">
                                <label for="catcloud-title" style="line-height:35px;display:block;">widget title: <input type="text" id="catcloud-title" name="catcloud-title" value="<?php echo htmlspecialchars($options['title']); ?>" /></label>
                                <label for="catcloud-small" style="line-height:35px;display:block;">minimum font: <input type="text" id="catcloud-small" name="catcloud-small" value="<?php echo htmlspecialchars($options['small']); ?>" /></label>
                                <label for="catcloud-big" style="line-height:35px;display:block;">maximum font: <input type="text" id="catcloud-big" name="catcloud-big" value="<?php echo $options['big']; ?>" /></label>
                                <label for="catcloud-unit" style="line-height:35px;display:block;">which font unit would you like to use: <select id="catcloud-unit" name="catcloud-unit"><option value="%" <?php selected('%',$options['unit']); ?>>%</option><option value="px" <?php selected('px',$options['unit']); ?>>px</option><option value="pt" <?php selected('pt',$options['unit']); ?>>pt</option></select></label>
                                <label for="catcloud-align" style="line-height:35px;display:block;">cloud alignment: <select id="catcloud-align" name="catcloud-align"><option value="left" <?php selected('left',$options['align']); ?>>left</option><option value="right" <?php selected('right',$options['align']); ?>>right</option><option value="center" <?php selected('center',$options['align']); ?>>center</option><option value="justify" <?php selected('justify',$options['align']); ?>>justify</option></select></label>
                                <label for="catcloud-exclude" style="line-height:35px;display:block;">category ids to exclude (separate with comas): <input type="text" id="catcloud-exclude" name="catcloud-exclude" style="width:290px;height:20px;" value="<?php echo htmlspecialchars($options['exclude'], ENT_QUOTES); ?>" /></label>
                                <input type="hidden" name="catcloud-submit" id="catcloud-submit" value="1" />
                        </div>
        <?php
        }

        // This prints the widget
        function widget_catcloud($args) {
                extract($args);
                $defaults = array('small' => 50, 'big' => 150, unit => '%', align => 'justify');
                $options = (array) get_option('widget_catcloud');

                foreach ( $defaults as $key => $value )
                        if ( !isset($options[$key]) )
                                $options[$key] = $defaults[$key];

                echo $before_widget;

                // omit title if not specified
                if ($options['title'] == '')
                {
                	$options['title'] = __('Tag Cloud');
                }

                echo $before_title . $options['title'] . $after_title;

                if ($options['exclude'] != '')
                        $exclude = '&exclude=' . $options['exclude'];

                //$cats = list_cats(1, 'all', 'name', 'asc', '', 0, 0, 1, 1, 0, 1, 1, 0, 1, '', '', $options['exclude'], 0);
                $cats = wp_list_cats("list=0&sort_column=name&optioncount=1&use_desc_for_title=0$exclude&recurse=1&hierarchical=0&hide_empty=1");

                $cats = explode("<br />\n", $cats);
                foreach ($cats as $cat)
                {
                        $regs = array(); // initialise the regs array
                        eregi("a href=\"(.+)\" ", $cat, $regs);
                        $catlink = $regs[1];
                        $cat = trim(wp_filter_post_kses(strip_tags($cat)));
                        eregi("(.*) \(([0-9]+)\)$", $cat, $regs);
                        $catname = $regs[1];
                        $count = $regs[2];
                        $counts{$catname} = $count;
                        $catlinks{$catname} = $catlink;
                }
                $spread = max($counts) - min($counts);
                if ($spread <= 0) { $spread = 1; };
                $fontspread = $options['big'] - $options['small'];
                $fontstep = $spread / $fontspread;
                if ($fontspread <= 0) { $fontspread = 1; }
                echo '<p class="catcloud" style="text-align:'.$options['align'].';">';
                foreach ($counts as $catname => $count)
                {
                        $catlink = $catlinks{$catname};
                        echo "\n<a href=\"$catlink\" title=\"$count entries\" style=\"font-size:".
                                ($options['small'] + ceil($count/$fontstep)).$options['unit']."\" rel=\"tag\">$catname</a> ";
                }
                echo '</p>';

                echo $after_widget;
        }

        // Tell Dynamic Sidebar about our new widget and its control
        register_sidebar_widget('Category Cloud', 'widget_catcloud');
        register_widget_control('Category Cloud', 'widget_catcloud_control', 300, 275);

}

// Delay plugin execution to ensure Dynamic Sidebar has a chance to load first
add_action('plugins_loaded', 'widget_catcloud_init');

?>