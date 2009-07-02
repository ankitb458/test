<?php
/*
 * Plugin Name: Permalink Redirect
 * Plugin URI: http://fucoder.com/code/permalink-redirect/
 * Description: Permalink Redirect ensures that pages and entries are always accessed via the permalink. Otherwise, a 301 redirect will be issued.
 * Version: 0.8.1
 * Author: Scott Yang
 * Author URI: http://scott.yang.id.au/
 */

class YLSY_PermalinkRedirect {
    function admin_menu() {
        add_options_page('Permalink Redirect Manager', 'Permalink Redirect', 5,
            __FILE__, array($this, 'admin_page'));
    }

    function admin_page() {
        global $wp_rewrite, $wp_version;

        // If we are updating, we will flush all the rewrite rules to force the 
        // old structure to be added.
        if (isset($_GET['updated'])) {
            $wp_rewrite->flush_rules();
        }

        $options = array('feedburner', 'feedburnerbrand', 'hostname', 
            'oldstruct', 'skip', 'newpath');
        $optionvars = array();
        foreach ($options as $option) {
            $$option = get_settings("permalink_redirect_$option");
            if (!$$option) {
                $$option = ($option == 'feedburnerbrand') ? 
                    'feeds.feedburner.com' : '';
            }
            if ($wp_version < '2' && !$$option) {
                add_option("permalink_redirect_$option", $$option);
            }
            $optionvars[] = "permalink_redirect_$option";
        }

        $home = parse_url(get_settings('home'));
?>
<div class="wrap">
    <h2>Permalink Redirect Manager</h2>
    <form action="options.php" method="post">
        <fieldset class="options">
            <legend>Paths to be skipped</legend>
            <p>Separate each entry with a new line. Matched with regular expression.</p>
            <textarea name="permalink_redirect_skip" style="width:98%;" rows="5"><?php echo htmlspecialchars($skip); ?></textarea>

            <legend style="padding-top:20px">Path pairs to redirect from and to</legend>
            <p>Separate each entry with a new line. Each line is [from]&lt;spaces&gt;[to].</p>
            <textarea name="permalink_redirect_newpath" style="width:98%;" rows="5"><?php echo htmlspecialchars($newpath); ?></textarea>
            <table class="optiontable" style="padding-top:20px">
                <tr valign="top">
                    <th scope="row">Old Permalink Structure:</th> 
                    <td><input name="permalink_redirect_oldstruct" type="text" id="permalink_redirect_oldstruct" size="50" value="<?php echo htmlspecialchars($oldstruct); ?>"/><br/><small><a href="http://codex.wordpress.org/Using_Permalinks">Available tags</a>. Current permalink structure: <a href="#" onclick="document.getElementById('permalink_redirect_oldstruct').value = '<?php echo htmlspecialchars(get_settings('permalink_structure')); ?>';return false;"><code><?php echo htmlspecialchars(get_settings('permalink_structure')); ?></code></a></small></td>
                </tr>
                <tr>
                    <th scope="row">FeedBurner Redirect:</th> 
                    <td>http://<input name="permalink_redirect_feedburnerbrand" type="text" id="permalink_redirect_feedburnerbrand" value="<?php print htmlspecialchars($feedburnerbrand); ?>" size="20"/>/<input name="permalink_redirect_feedburner" type="text" id="permalink_redirect_feedburner" value="<?php echo htmlspecialchars($feedburner) ?>" size="20" /></td> 
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
        $requested = $_SERVER['REQUEST_URI'];

        if (is_404() || is_trackback() || is_search() ||
            is_comments_popup() || $this->is_skip($requested))
        {
            return;
        }

        $this->redirect_newpath($requested);
        $this->redirect_feedburner();

        if (($requested = @parse_url($_SERVER['REQUEST_URI'])) === false) {
            return;
        }

        $requested = $requested['path'];

        if (! ($link = $this->guess_permalink())) {
            return;
        }
        $permalink = @parse_url($link);

        // WP2.1: If a static page has been set as the front-page, we'll get 
        // empty string here.
        if (!$permalink['path']) {
            $permalink['path'] = '/';
        }

        if (($requested != $permalink['path']) || $this->check_hostname()) {
            $this->redirect($link);
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
            // WP2.2: In Wordpress 2.2+ is_home() returns false and is_page() 
            // returns true if front page is a static page.
            if (is_page() && ('page' == get_option('show_on_front')) && 
                $post->ID == get_option('page_on_front'))
            {
                $link = trailingslashit($link);
            }
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
            // WP2.1: Handling "Posts page" option. In WordPress 2.1 is_home() 
            // returns true and is_page() returns false if home page has been 
            // set to a page, and we are getting the permalink of that page 
            // here.
            if ((get_option('show_on_front') == 'page') &&
                ($pageid = get_option('page_for_posts'))) 
            {
                $link = trailingslashit(get_permalink($pageid));
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

    function redirect($dst, $status=301) {
        // XXX: WordPress does not set 301 status code on FastCGI backend when 
        // we use the wp_redirect() function (See 
        // http://trac.wordpress.org/ticket/3215).  However it kills the 
        // ability to do so on sites hosting behind many light-weight web 
        // servers (Lighty and Nginx for example).
        //
        // We are doing a hack here to check against these specific web server 
        // software, and send back 301 ourselves.
        if ($status == 301 && php_sapi_name() == 'cgi-fcgi') {
            $server = $_SERVER['SERVER_SOFTWARE'];
            $servers_to_check = array('lighttpd', 'nginx');
            foreach ($servers_to_check as $name) {
                if (stripos($_SERVER['SERVER_SOFTWARE'], $name) !== false) {
                    status_header($status);
                    header("Location: $dst");
                    exit(0);
                }
            }
        }
        wp_redirect($dst, $status);
        exit(0);
    }
    
    function redirect_feedburner() {
        // Check whether we need to do redirect for FeedBurner.
        // NOTE this might not always get executed. For feeds,
        // WP::send_headers() might send back a 304 before template_redirect
        // action can be called.
        global $withcomments;

        if (is_feed() && !is_archive() && !$withcomments) {
            if (($feedburner = get_settings('permalink_redirect_feedburner')) &&
                (strncmp('FeedBurner/', $_SERVER['HTTP_USER_AGENT'], 11) != 0))
            {
                $brand = get_settings('permalink_redirect_feedburnerbrand');
                $brand = $brand ? $brand : 'feeds.feedburner.com';
                $this->redirect("http://$brand/$feedburner", 302);
            }
        }
    }

    // Static page redirect contributed by Sergey Menshikov.
    function redirect_newpath($path) {
        if ($newpathlist = get_settings('permalink_redirect_newpath')) {
            $newpathlist = explode("\n", $newpathlist);
            foreach ($newpathlist as $newpath) {
                $pair = preg_split('/\s+/', trim($newpath));
                if ($pair[0] == $path) {
                    $this->redirect($pair[1]);
                }
            }
        }
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

$_permalink_redirect = new YLSY_PermalinkRedirect();
add_action('admin_menu', array($_permalink_redirect, 'admin_menu'));
add_filter('post_rewrite_rules', array($_permalink_redirect, 
    'post_rewrite_rules'));
add_action('template_redirect', array($_permalink_redirect, 'execute'));
