<?php

/*
  Custom theme functions

  Note: we recommend you prefix all your functions to avoid any naming
  collisions or wrap your functions with if function_exists braces.
 */

function numeral($number) {
    $test = abs($number) % 10;
    $ext = ((abs($number) % 100 < 21 and abs($number) % 100 > 4) ? 'th' : (($test < 4) ? ($test < 3) ? ($test < 2) ? ($test < 1) ? 'th' : 'st' : 'nd' : 'rd' : 'th'));
    return $number . $ext;
}

function count_words($str) {
    return count(preg_split('/\s+/', strip_tags($str), null, PREG_SPLIT_NO_EMPTY));
}

function pluralise($amount, $str, $alt = '') {
    return intval($amount) === 1 ? $str : $str . ($alt !== '' ? $alt : 's');
}

function relative_time($date) {
    if (is_numeric($date))
        $date = '@' . $date;

    $user_timezone = new DateTimeZone(Config::app('timezone'));
    $date = new DateTime($date, $user_timezone);

    // get current date in user timezone
    $now = new DateTime('now', $user_timezone);

    $elapsed = $now->format('U') - $date->format('U');

    if ($elapsed <= 1) {
        return 'Just now';
    }

    $times = array(
        31104000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute',
        1 => 'second'
    );

    foreach ($times as $seconds => $title) {
        $rounded = $elapsed / $seconds;

        if ($rounded > 1) {
            $rounded = round($rounded);
            return $rounded . ' ' . pluralise($rounded, $title) . ' ago';
        }
    }
}

function twitter_account() {
    return site_meta('twitter', 'idiot');
}

function twitter_url() {
    return 'https://twitter.com/' . twitter_account();
}

function total_articles() {
    return Post::where(Base::table('posts.status'), '=', 'published')->count();
}

/**
 * Returns an array of unique tags that exist on pages
 *
 * @return array
 */
function get_page_tags() {
    $tag_ext = Extend::where('key', '=', 'page_tags')->where('data_type', '=', 'page')->get();
    $tag_id = $tag_ext[0]->id;

    $prefix = Config::db('prefix', '');

    $tags = array();
    $index = 0;
    foreach (Query::table($prefix . 'page_meta')
            ->left_join('pages', 'pages.id', '=', 'page_meta.page')
            ->where('pages.status', '=', 'published')
            ->where('extend', '=', $tag_id)
            ->get() as $meta) {
        $page_meta = json_decode($meta->data);
        foreach (explode(", ", $page_meta->text) as $tag_text) {
            $tags[$index] = $tag_text;
            $index += 1;
        }
    }

    return array_unique($tags);
}

/**
 * Returns an array of ids for pages that have the specified tag
 *
 * @param string
 * @return array
 */
function get_pages_with_tag($tag = '') {
    $tag_ext = Extend::where('key', '=', 'page_tags')->get();
    $tag_id = $tag_ext[0]->id;

    $prefix = Config::db('prefix', '');

    $pages = array();
    foreach (Query::table($prefix . 'page_meta')
            ->where('extend', '=', $tag_id)
            ->where('data', 'LIKE', '%' . $tag . '%')
            ->get() as $meta) {

        $pages[] = $meta->page;
    }

    return array_unique($pages);
}

/**
 * Returns an array of unique tags that exist on posts
 *
 * @return array
 */
function get_post_tags() {
    
    $tag_ext = Extend::where('key', '=', 'post_tags')->where('data_type', '=', 'post')->get();
    $tag_id = $tag_ext[0]->id;

    $prefix = Config::db('prefix', '');

    $tags = array();
    $index = 0;
    foreach (Query::table($prefix . 'post_meta')
            ->left_join('posts', 'posts.id', '=', 'post_meta.post')
            ->where('posts.status', '=', 'published')
            ->where('extend', '=', $tag_id)
            ->get() as $meta) {
        $post_meta = json_decode($meta->data);
        foreach (explode(", ", $post_meta->text) as $tag_text) {
            $tags[$index] = $tag_text;
            $index += 1;
        }
    }

    return array_unique($tags);
}

/**
 * Returns an array of ids for posts that have the specified tag
 *
 * @param string
 * @return array
 */
function get_posts_with_tag($tag) {
     
    $tag_ext = Extend::where('key', '=', 'post_tags')->get();
    $tag_id = $tag_ext[0]->id;

    $prefix = Config::db('prefix', '');

    $posts = array();
    foreach (Query::table($prefix . 'post_meta')
            ->where('extend', '=', $tag_id)
            ->where('data', 'LIKE', '%' . $tag . '%')
            ->get() as $meta) {

        $posts[] = $meta->post;
    }

    return array_unique($posts);
}

/**
 * Extract query tag from url; otherwise returns empty string.
 * 
 * @param type $url
 */
function extract_tag() {
    
    $tag = '';
    try {
        
        if (isset($_SERVER) && $_SERVER['REQUEST_METHOD'] == 'GET' 
                && $url = $_SERVER['REQUEST_URI']) {
            
            $parsed_url = parse_url($url);    
            //if ( array_key_exists('query', $parsed_url) )
            if ( array_key_exists('query', $parsed_url) )
            {
                $query = $parsed_url['query'];
                if ( substr($query, 0, 4) === 'tag=') {
                    $tag = substr($query, -(strlen($query) - 4));
                }
            }
        }
        
    } catch (Exception $ex) {

        //FIXME: log something ... where ?
    }

    return $tag;
}

/**
 * Returns true if there is at least one tagged post
 * This replaces the Anchor has_posts() method
 *
 * @return bool
 */
function has_tagged_posts() {
    
    $tag = extract_tag();

    if ($tag != '') {
        if ($tagged_posts = get_posts_with_tag($tag)) {

            $count = Post::where_in('id', $tagged_posts)
                    ->where('status', '=', 'published')
                    ->count();
        } else {
            $count = 0;
        }

        Registry::set('total_tagged_posts', $count);
    } else {

        Registry::set('total_tagged_posts', 0);
        return has_posts();
    }

    return Registry::get('total_tagged_posts', 0) > 0;
}

/**
 * Returns true while there are still tagged posts in the array.
 * This replaces the Anchor posts() method
 *
 * @return bool
 */
function tagged_posts() {

    $tag = extract_tag();
    
    if ($tag != '') {
        
        if (! $posts = Registry::get('tagged_posts') ) {
            
            $tagged_posts = get_posts_with_tag($tag);
            $posts = Post::
                    where_in('id', $tagged_posts)
                    ->where('status', '=', 'published')
                    ->sort('created', 'desc')
                    ->get();

            Registry::set('tagged_posts', $posts = new Items($posts));
        }

        if ($posts instanceof Items) {
            
            if ($result = $posts->valid()) {
                // register single post
                Registry::set('article', $posts->current());

                // move to next
                $posts->next();
            }
            // back to the start
            else
                $posts->rewind();

            return $result;
        }
    } else {
        return posts();
    }

    return false;
}

/**
 * Returns an array of unique tags that exist on post given post,
 * empty array if no tags are found.
 *
 * @return array
 */
function get_tags_for_post($post_id) {
    $tag_ext = Extend::where('key', '=', 'post_tags')->where('type', '=', 'post')->get();
    $tag_id = $tag_ext[0]->id;

    $prefix = Config::db('prefix', '');

    $tags = array();
    $index = 0;
    $meta = Query::table($prefix . 'post_meta')
            ->left_join('posts', 'posts.id', '=', 'post_meta.post')
            ->where('posts.status', '=', 'published')
            ->where('extend', '=', $tag_id)
            ->where('post', '=', $post_id) // questa è la linea che ho aggiunto
            ->get();

    $post_meta = json_decode($meta[0]->data);
    if (!trim($post_meta->text) == "") {
        foreach (explode(",", $post_meta->text) as $tag_text) {
            $tags[$index] = trim($tag_text);
            $index += 1;
        }
    }

    return array_unique($tags);
}
