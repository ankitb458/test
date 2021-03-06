<?php
/*
 * Plugin Name: Permalink Redirect
 * Plugin URI: http://fucoder.com/code/permalink-redirect/
 * Description: Permalink Redirect ensures that pages and entries are always accessed via the permalink. Otherwise, a 301 redirect will be issued.
 * Version: 0.7.0 (edited)
 * Author: Scott Yang
 * Author URI: http://scott.yang.id.au/
 */

class YLSY_PermalinkRedirect {
    function admin_menu() {
        add_options_page('Permalink Redirect Manager', 'Permalink Redirect', 'administrator',
            __FILE__, array('YLSY_PermalinkRedirect', 'admin_page'));
    }

    function admin_page() {
        global $wp_rewrite, $wp_version;

        // If we are updating, we will flush all the rewrite rules to force the
        // old structure to be added.
        if (isset($_GET['updated'])) {
            $wp_rewrite->flush_rules();
        }

        $options = array('feedburner', 'hostname', 'oldstruct', 'skip');
        $optionvars = array();
        foreach ($options as $option) {
            $$option = get_settings("permalink_redirect_$option");
            if ($wp_version < '2' && !$$option) {
                add_option("permalink_redirect_$option");
            }
            $optionvars[] = "permalink_redirect_$option";
        }

        $home = parse_url(get_settings('home'));
?>
<div class="wrap">
    <h2>Permalink Redirect Manager</h2>
    <form action="options.php" method="post">
        <fieldset class="options">
            <legend>Paths to be skipped:<br/><small><em>(Separate each entry with a new line. Matched with regular expression.)</em></small></legend>
            <textarea name="permalink_redirect_skip" style="width:98%;" rows="5"><?php echo htmlspecialchars($skip); ?></textarea>
            <table class="optiontable">
                <tr valign="top">
                    <th scope="row">Old Permalink Structure:</th>
                    <td><input name="permalink_redirect_oldstruct" type="text" id="permalink_redirect_oldstruct" size="50" value="<?php echo htmlspecialchars($oldstruct); ?>"/><br/><small><a href="http://codex.wordpress.org/Using_Permalinks">Available tags</a>. Current permalink structure: <a href="#" onclick="document.getElementById('permalink_redirect_oldstruct').value = '<?php echo htmlspecialchars(get_settings('permalink_structure')); ?>';return false;"><code><?php echo htmlspecialchars(get_settings('permalink_structure')); ?></code></a></td>
                </tr>
                <tr>
                    <th scope="row">FeedBurner Redirect:</th>
                    <td>http://feeds.feedburner.com/<input name="permalink_redirect_feedburner" type="text" id="permalink_redirect_feedburner" value="<?php echo htmlspecialchars($feedburner) ?>" size="25" /></td>
                </tr>
                <tr>
                    <th scope="row">Hostname Redirect:</th>
                    <td><input name="permalink_redirect_hostname" type="checkbox" id="permalink_redirect_hostname" value="1"<?php if ($hostname) { ?> checked="checked"<?php } ?>/> Redirect if hostname is not <code><?php echo htmlspecialchars($home['host']); ?></code>.</td>
                </tr>
            </table>
        </fieldset>
        <p class="submit">
            <input type="submit" name="Submit" value="<?php _e('Update Options') ?> &raquo;" />
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="page_options" value="<?php echo join(',', $optionvars); ?>"/>
            <?php if (function_exists('wp_nonce_field')) { wp_nonce_field('update-options'); } ?>
        </p>
    </form>
</div>
<?php
    }

    function check_hostname() {
        if (! get_settings('permalink_redirect_hostname')) {
            return false;
        }
        $requested = $_SERVER['HTTP_HOST'];
        $home = parse_url(get_settings('home'));
        return $requested != $home['host'];
    }

    function execute() {
        global $withcomments;

        $requested = $_SERVER['REQUEST_URI'];
        if (is_404() || is_trackback() || is_search() ||
            is_comments_popup() || YLSY_PermalinkRedirect::is_skip($requested))
        {
            return;
        }

        if (($requested = @parse_url($_SERVER['REQUEST_URI'])) === false) {
            return;
        }

        $requested = $requested['path'];

        // Check whether we need to do redirect for FeedBurner.
        // NOTE this might not always get executed. For feeds,
        // WP::send_headers() might send back a 304 before template_redirect
        // action can be called.
        if (is_feed() && !is_archive() && !$withcomments) {
            $feedburner = get_settings('permalink_redirect_feedburner');
            if ($feedburner && !YLSY_PermalinkRedirect::is_feedburner()) {
                header('HTTP/1.1 302 Found');
                header('Status: 302 Found');
                header("Location: http://feeds.feedburner.com/$feedburner");
                exit(0);
            } else {
                return;
            }
        }

        if (! ($link = YLSY_PermalinkRedirect::guess_permalink())) {
            return;
        }
        $permalink = @parse_url($link);

        // WP2.1: If a static page has been set as the front-page, we'll get
        // empty string here.
        if (!$permalink['path']) {
            $permalink['path'] = '/';
        }

        if ($requested != $permalink['path'] ||
            YLSY_PermalinkRedirect::check_hostname())
        {
            header('HTTP/1.1 301 Moved Permanently');
            header('Status: 301 Moved Permanently');
            header("Location: $link");
            exit(0);
        }
    }

    function guess_permalink() {
        global $posts;

        $haspost = count($posts) > 0;
        if (get_query_var('m')) {
            // Handling special case with '?m=yyyymmddHHMMSS'
            // Since there is no code for producing the archive links for
            // is_time, we will give up and not trying any redirection.
            $m = preg_replace('/[^0-9]/', '', get_query_var('m'));
            switch (strlen($m)) {
                case 4: // Yearly
                    $link = get_year_link($m);
                    break;
                case 6: // Monthly
                    $link = get_month_link(substr($m, 0, 4), substr($m, 4, 2));
                    break;
                case 8: // Daily
                    $link = get_day_link(substr($m, 0, 4), substr($m, 4, 2),
                                         substr($m, 6, 2));
                    break;
                default:
                    return false;
            }
        } elseif ((is_single() || is_page()) && (sizeof($posts) > 0)) {
            $post = $posts[0];
            $link = get_permalink($post->ID);
            $page = get_query_var('page');
            if ($page && $page > 1)
                $link = trailingslashit($link) . "$page/";
        } elseif (is_author() && $haspost) {
            global $wp_version;
            if ($wp_version >= '2') {
                $author = get_userdata(get_query_var('author'));
                if ($author === false)
                    return false;
                $link = get_author_link(false, $author->ID,
                    $author->user_nicename);
            } else {
                // XXX: get_author_link() bug in WP 1.5.1.2
                //      s/author_nicename/user_nicename/
                global $cache_userdata;
                $userid = get_query_var('author');
                $link = get_author_link(false, $userid,
                    $cache_userdata[$userid]->user_nicename);
            }
        } elseif (is_category() && $haspost) {
            $link = get_category_link(get_query_var('cat'));
        } elseif (is_day() && $haspost) {
            $link = get_day_link(get_query_var('year'),
                                 get_query_var('monthnum'),
                                 get_query_var('day'));
        } elseif (is_month() && $haspost) {
            $link = get_month_link(get_query_var('year'),
                                   get_query_var('monthnum'));
        } elseif (is_year() && $haspost) {
            $link = get_year_link(get_query_var('year'));
        } elseif (is_home()) {
            // WP2.1. Handling "Posts page" option.
            if ((get_option('show_on_front') == 'page') &&
                ($reqpage = get_option('page_for_posts')))
            {
                $link = get_permalink($reqpage);
            } else {
                $link = trailingslashit(get_settings('home'));
            }
        } else {
            return false;
        }

        if (is_paged()) {
            $paged = get_query_var('paged');
            if ($paged)
                $link = trailingslashit($link) . "page/$paged/";
        }

        if (is_feed()) {
            $link = trailingslashit($link) . 'feed/';
        }

        return $link;
    }

    function is_feedburner() {
        return strncmp('FeedBurner/', $_SERVER['HTTP_USER_AGENT'], 11) == 0;
    }

    function is_skip($path) {
        $permalink_redirect_skip = get_settings('permalink_redirect_skip');
        $permalink_redirect_skip = explode("\n", $permalink_redirect_skip);

        // Apply 'permalink_redirect_skip' filter so other plugins can
        // customise the skip behaviour. (Denis de Bernardy @ 2006-04-23)
        $permalink_redirect_skip = apply_filters('permalink_redirect_skip',
            $permalink_redirect_skip);

        foreach ($permalink_redirect_skip as $skip) {
            $skip = trim($skip);
            if ($skip && ereg($skip, $path))
                return true;
        }

        return false;
    }

    function post_rewrite_rules($rules) {
        global $wp_rewrite;
        $oldstruct = get_settings('permalink_redirect_oldstruct');
        if ($oldstruct) {
            $rules = array_merge($rules,
                $wp_rewrite->generate_rewrite_rule($oldstruct, false, false,
                false, true));

        }
	return $rules;
    }
}

add_action('admin_menu', array('YLSY_PermalinkRedirect', 'admin_menu'));
add_filter('post_rewrite_rules', array('YLSY_PermalinkRedirect',
    'post_rewrite_rules'));
add_action('template_redirect', array('YLSY_PermalinkRedirect', 'execute'));
?>