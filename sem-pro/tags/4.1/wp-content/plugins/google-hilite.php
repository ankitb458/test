<?php
/*
Plugin Name: Search Hilite
Plugin URI: http://dev.wp-plugins.org/browser/google-highlight/
Description: When someone is referred from a search engine like Google, Yahoo, or WordPress' own, the terms they search for are highlighted with this plugin. Packaged by <a href="Matthttp://photomatt.net/">Matt</a>.
Version: 1.5
Author: Ryan Boren
Author URI: http://boren.nu
*/

function get_search_query_terms($engine = 'google') {
        $referer = urldecode($_SERVER['HTTP_REFERER']);
        $query_array = array();
        switch ($engine) {
        case 'google':
                // Google query parsing code adapted from Dean Allen's
                // Google Hilite 0.3. http://textism.com
                $query_terms = preg_replace('/^.*q=([^&]+)&?.*$/i','$1', $referer);
                $query_terms = preg_replace('/\'|"/', '', $query_terms);
                $query_array = preg_split ("/[\s,\+\.]+/", $query_terms);
                break;

        case 'lycos':
                $query_terms = preg_replace('/^.*query=([^&]+)&?.*$/i','$1', $referer);
                $query_terms = preg_replace('/\'|"/', '', $query_terms);
                $query_array = preg_split ("/[\s,\+\.]+/", $query_terms);
                break;

        case 'yahoo':
                $query_terms = preg_replace('/^.*p=([^&]+)&?.*$/i','$1', $referer);
                $query_terms = preg_replace('/\'|"/', '', $query_terms);
                $query_array = preg_split ("/[\s,\+\.]+/", $query_terms);
                break;

        case 'wordpress':
                $search = get_query_var('s');
                $search_terms = get_query_var('search_terms');

                if (!empty($search_terms)) {
                        $query_array = $search_terms;
                } else if (!empty($search)) {
                        $query_array = array($search);
                } else {
                        $query_terms = preg_replace('/^.*s=([^&]+)&?.*$/i','$1', $referer);
                        $query_terms = preg_replace('/\'|"/', '', $query_terms);
                        $query_array = preg_split ("/[\s,\+\.]+/", $query_terms);
                }
        }

        return $query_array;
}

function is_referer_search_engine($engine = 'google') {
        if( empty($_SERVER['HTTP_REFERER']) && 'wordpress' != $engine ) {
                return false;
        }

        $referer = urldecode($_SERVER['HTTP_REFERER']);

        if ( ! $engine ) {
                return false;
        }

        switch ($engine) {
        case 'google':
                if (preg_match('|^http://(www)?\.?google.*|i', $referer)) {
                        return true;
                }
                break;

        case 'lycos':
                if (preg_match('|^http://search\.lycos.*|i', $referer)) {
                        return true;
                }
                break;

        case 'yahoo':
                if (preg_match('|^http://search\.yahoo.*|i', $referer)) {
                        return true;
                }
                break;

        case 'wordpress':
                if ( is_search() )
                        return true;

                $siteurl = get_option('home');
                if (preg_match("#^$siteurl#i", $referer))
                        return true;

                break;
        }

        return false;
}

function hilite($text) {
        $search_engines = array('wordpress', 'google', 'lycos', 'yahoo');

        foreach ($search_engines as $engine) {
                if ( is_referer_search_engine($engine)) {
                        $query_terms = get_search_query_terms($engine);
                        foreach ($query_terms as $term) {
                                if (!empty($term) && $term != ' ') {
                    $term = preg_quote($term, '/');
                                        if (!preg_match('/<.+>/',$text)) {
                                                $text = preg_replace('/(\b'.$term.'\b)/i','<span class="hilite">$1</span>',$text);
                                        } else {
                                                $text = preg_replace('/(?<=>)([^<]+)?(\b'.$term.'\b)/i','$1<span class="hilite">$2</span>',$text);
                                        }
                                }
                        }
                        break;
                }
        }

        return $text;
}

function hilite_head() {
        echo "
<style type='text/css'>
.hilite {
        color: #fff;
        background-color: #f93;
}
</style>
";
}

// Highlight text and comments:
add_filter('the_content', 'hilite');
add_filter('the_excerpt', 'hilite');
add_filter('comment_text', 'hilite');
add_action('wp_head', 'hilite_head');

?>