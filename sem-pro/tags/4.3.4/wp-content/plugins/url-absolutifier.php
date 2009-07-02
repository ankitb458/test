<?php

/*
Plugin Name: URL Absolutifier
Plugin URI: http://fucoder.com/code/url-absolutifier/
Description: Fix URL in all links and images to be absolute instead of relative.
Version: 1.0 $Rev: 19 $
Author: Scott Yang
Author URI: http://scott.yang.id.au/

*/

class URLAbsolutifier {
    function get_baseurl() {
        if (!$this->baseurl) {
            global $post;
            $this->baseurl = parse_url(get_permalink($post->ID));
        }
        return $this->baseurl;
    }

    function the_content($content) {
        if (is_feed()) {
            $this->baseurl = null;
            $pattern = "/<(a[^>]* href)=['\"]([\\/\\.].*?)['\"]/";
            $content = preg_replace_callback($pattern, 
                array($this, 'replace_url'), $content);
            $pattern = "/<(img[^>]* src)=['\"]([\\/\\.].*?)['\"]/";
            $content = preg_replace_callback($pattern, 
                array($this, 'replace_url'), $content);
        }
        return $content;
    }

    function replace_url($match) {
        $url = $this->urljoin($this->get_baseurl(), parse_url($match[2]));
        return "<$match[1]=\"$url\"";
    }

    function segment_helper($val) {
        return $val != '.';
    }

    function urljoin($base, $url) {
        if (! ($url['path'] || $url['query'] || $url['fragment'])) {
            return $this->urlunparse($base);
        }

        $base['query'] = $url['query'];
        $base['fragment'] = $url['fragment'];
        if (substr($url['path'], 0, 1) == '/') {
            $base['path'] = $url['path'];
            return $this->urlunparse($base);
        }
        $segments = explode('/', $base['path']);
        array_pop($segments);
        $segments = array_merge($segments, explode('/', $url['path']));
        if ($segments[count($segments) - 1] == '.')
            $segments[count($segments) - 1] = '';
        $segments = array_filter($segments, array($this, 'segment_helper'));

        while (true) {
            $i = 1;
            $n = count($segments) - 1;
            while ($i < $n) {
                if ($segments[$i] == '..' &&
                    $segments[$i - 1] != '' &&
                    $segments[$i - 1] != '..') 
                {
                    unset($segments[$i]);
                    unset($segments[$i - 1]);
                    break;
                }
                $i ++;
            }
            if ($i == $n)
                break;
        }
        $cnt = count($segments);
        if ($cnt == 2 && $segments[0] == '' && $segments[1] == '..')
            $segments[1] = '';
        elseif ($cnt >= 2 && $segments[$cnt - 1] == '..') {
            unset($segments[$cnt - 1]);
            $segments[$cnt - 2] = '';
        }
        $base['path'] = implode('/', $segments);
        return $this->urlunparse($base);
    }

    function urlunparse($url) {
        $result = $url['scheme'] . '://';
        if ($url['user'] || $url['pass'])
            $result .= $url['user'] . ':' . $url['pass'] . '@';
        $result .= $url['host'] . $url['path'];
        if ($url['query'])
            $result .= '?' . $url['query'];
        if ($url['fragment'])
            $result .= '?' . $url['fragment'];
        return $result;
    }
}

$_instance = new URLAbsolutifier;
if (function_exists('add_filter')) {
    add_filter('the_content', array($_instance, 'the_content'));
    add_filter('the_excerpt', array($_instance, 'the_content'));
    add_filter('the_excerpt_rss', array($_instance, 'the_content'));
}

/* 

History:

*/
