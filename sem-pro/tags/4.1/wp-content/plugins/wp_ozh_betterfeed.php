<?php
/*
Plugin Name: Better Feed
Plugin URI: http://frenchfragfactory.net/ozh/my-projects/wordpress-plugin-better-feed-rss/
Description: Your feeds, on steroid.
Version: 1.1 (edited)
Author: Ozh
Author URI: http://planetOzh.com
*/

/*********************************** Edit Here **********************************/


/******************************* General Behavior *******************************/

$wp_ozh_betterfeed['split'] = 1;
    /* Turn post splitting on <!--more--> ON and OFF
     * 0 : default RSS behavior (shows whole post)
     * 1 : alternate behavior (shows post till <!--more-->, like on the web) */

$wp_ozh_betterfeed['multipage'] = 1;
    /* Handle multiple page posts just like other posts
     * Page separation (<!--nextpage-->) will be treated like "Read More" links
     * (by default, Wordpress doesn't output anything particular in feeds for these posts) */

/******************************* Custom Strings *******************************/

    /* For eaach of the following variable, use any text and HTML,
     * with any of the following tokens :
     *
     * %%blogname%% : blog name (My Weblog)
     * %%blogurl%% : blog url (http://myblog.com/)
     * %%posttitle%% : post title (Hello World)
     * %%posturl%% : post url (http://myblog.com/archives/2001/02/03/hello-world/ or http://myblog.com/?p=1337)
     * %%id%% : post ID (its number, i.e. 1337 in above example)
     * %%date[Y]%% : date / time of post, where pattern within brackets follows PHP date() syntax
     * %%categories%% : list of commas separated category names the post is filed in
     * %%categorylinks%% : list of commas separated category links the post is filed in
     * %%comments%% : number of comments
     * %%comments_text%% : text for 0, 1 or xx comments (see below)
     * %%readmore%% : "Read more" link text, if applicable (see below)
     * %%wordcount%% : total number of words in a post
     * %%wordcount_remain%% : number of words in second part after the <!--more--> split
     * %%author_first%% : author's firstname
     * %%author_last%% : author's lastname
     * %%author_nick%% : author's nickname
     */

$wp_ozh_betterfeed['readmore'] = '(...)<br/>Read the rest of <a href="%%posturl%%">%%posttitle%%</a> (%%wordcount_remain%% words)';
    /* Text for the "Read more" link */

$wp_ozh_betterfeed['footer'] = <<<FEEDFOOT
    <p>%%readmore%%</p>
    <hr noshade style="margin:0;height:1px" />
    <p>&copy; %%author_nick%% for <a href="%%blogurl%%">%%blogname%%</a>, %%date[Y]%%. |
      <a href="%%posturl%%">Permalink</a> |
      <a href="%%posturl%%#comments">%%comments_text%%</a></p>
    <p>Add to <a href="http://del.icio.us/post?url=%%posturl%%&amp;title=%%posttitle%%">del.icio.us</a></p>
    <p>Search blogs linking this post with <a href="http://www.technorati.com/search/%%posturl%%" title="Search on Technorati">Technorati</a></p>
    <p>Want more on these topics ? Browse the archive of posts filed under %%categorylinks%%.</p>
FEEDFOOT;
    /* Additionnal "footer" text in each RSS item
     * Write any text, html and %%tokens%% between <<<FEEDFOOT and FEEDFOOT;
     * Warning : the trailing FEEDFOOT; must _not_ be indented */

$wp_ozh_betterfeed['footer'] = <<<FEEDFOOT
	<p>%%readmore%%</p>
FEEDFOOT;

$wp_ozh_betterfeed['0comment'] = "No comment";
    /* Text when there is 0 comment */

$wp_ozh_betterfeed['1comment'] = "One comment";
    /* Text when there is 1 comment */

$wp_ozh_betterfeed['Xcomments'] = "% comments";
    /* Text when there is more than 1 comment, where '%' is replace by the number of comments */

/******************************* STOP *********************************/
/****************************** EDITING *******************************/

add_filter('the_content', 'wp_ozh_betterfeed',50);

/* The function that handles the stuff */
function wp_ozh_betterfeed( $content ) {
    global $post, $id, $wp_ozh_betterfeed;
    if ( ! is_feed() ) return $content;

    $wp_ozh_betterfeed['splitted'] = 0;

    if ($wp_ozh_betterfeed['split'] and (strpos($content,"<a id=\"more-$id\"></a>") != FALSE)) {
        $content = preg_split("#<a id=\"more-$id\"></a>#",$content);
        $content = $content[0];
        /* close <p> tags that might have been lost in the splitting */
        if (!preg_match("#</p>$#", $content)) {
            $content .= "</p>\n";
        }
        $wp_ozh_betterfeed['splitted'] = 1;
    }

    if ($wp_ozh_betterfeed['multipage'] and (strpos($post->post_content,"<!--nextpage-->") != FALSE)) {
        $wp_ozh_betterfeed['splitted'] = 1;
    }

    $content .= wp_ozh_betterfeed_detokenize($wp_ozh_betterfeed['footer']);

    return ($content);
}

/* The function that translate every %%stuff%% into their values */
function wp_ozh_betterfeed_detokenize($string='',$noloop=0) {

    global $id, $wp_ozh_betterfeed;

    $string = str_replace('%%blogname%%', get_bloginfo('name'), $string);
    $string = str_replace('%%blogurl%%', get_bloginfo('url'), $string);
    $string = str_replace('%%posttitle%%', get_the_title(), $string);
    $string = str_replace('%%posturl%%', get_permalink(), $string);
    $string = str_replace('%%id%%', $id, $string);

    if (strpos($string,'%%categories%%') != FALSE) {
    $catlist = array();
        $cats = get_the_category();
        foreach($cats as $cat) {
            $cat->cat_name = str_replace('"','&quote;',$cat->cat_name);
            $cat->cat_name = str_replace("'",'&#39;',$cat->cat_name);
            $catlist[] = $cat->cat_name;
        }
        $catlist = join(', ',$catlist);
        $string = str_replace('%%categories%%', $catlist, $string);
    }

    if (strpos($string,'%%categorylinks%%') != FALSE) {
        $string = str_replace('%%categorylinks%%', get_the_category_list(', '), $string);
    }

    $string = str_replace('%%comments%%', get_comments_number($id), $string);

    if (strpos($string,'%%comments_text%%') != FALSE) {
        $number = get_comments_number( $id );
        if ($number == 0) {
            $blah = $wp_ozh_betterfeed['0comment'];
        } elseif ($number == 1) {
            $blah = $wp_ozh_betterfeed['1comment'];
        } elseif ($number  > 1) {
            $blah = str_replace('%', $number, $wp_ozh_betterfeed['Xcomments']);
        }
        $string = str_replace('%%comments_text%%', $blah, $string);
    }

    if (strpos($string,'%%wordcount%%') != FALSE) {
        $string = str_replace('%%wordcount%%', wp_ozh_betterfeed_wordcount('all'), $string);
    }

    if (strpos($string,'%%wordcount_remain%%') != FALSE) {
        $string = str_replace('%%wordcount_remain%%', wp_ozh_betterfeed_wordcount('remain'), $string);
    }

    $string = str_replace('%%author_first%%', get_the_author('firstname'), $string);
    $string = str_replace('%%author_last%%', get_the_author('lastname'), $string);
    $string = str_replace('%%author_nick%%', get_the_author('nickname'), $string);

    if (strpos($string,'%%date[') != FALSE) {
        $string = preg_replace('/%%date\[([^]]+)\]%%/e', "get_post_time('\\1')", $string);
    }

    if ($noloop == 0 and $wp_ozh_betterfeed['splitted']) {
        $string = str_replace('%%readmore%%', wp_ozh_betterfeed_detokenize($wp_ozh_betterfeed['readmore'],1), $string);
    } else {
        $string = str_replace('%%readmore%%', '', $string);
    }

    return $string;

}

/* The function that counts words (before and after the <!--more--> part if applicable) */
function wp_ozh_betterfeed_wordcount($scope='all') {
    global $post, $id, $wp_ozh_betterfeed;
    $text = $post->post_content;
    if ($scope=='remain') {
        if ( (strpos($text,'<!--more-->') != FALSE ) and $wp_ozh_betterfeed['split']) {
            list($temp,$text) = explode('<!--more-->', $text,2);
        } elseif ( (strpos($text,'<!--nextpage-->') != FALSE ) and $wp_ozh_betterfeed['multipage']) {
            list($temp,$text) = explode('<!--nextpage-->', $text,2);
        }
    }
    $text = str_replace("\n", " ", $text);
    $text = split(' ', strip_tags($text));
    foreach ($text as $k=>$v) {
        if (trim($v) == '') {
            unset($text[$k]);
        }
    }
    $count = count($text);
    return number_format($count);
}

?>