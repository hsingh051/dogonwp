<?php
extract(shortcode_atts(array(
    'count' => 10,
    'offset' => 0,
    'categories' => '',
    'posts' => '',
    'author' => '',
    'image_height' => 250,
    'pagination' => 'true',
    'pagination_style' => '2',
    'orderby' => 'date',
    'order' => 'DESC',
    'item_id' => '',
) , $atts));

$item_id = (!empty($item_id)) ? $item_id : 1409305847;

$query = array(
    'posts_per_page' => (int)$count,
    'post_type' => 'news',
);

if ($categories != '') {
    $query['tax_query'] = array(
        array(
            'taxonomy' => 'news_category',
            'field' => 'slug',
            'terms' => explode(',', $categories)
        )
    );
}
if ($author) {
    $query['author'] = $author;
}
if ($posts) {
    $query['post__in'] = explode(',', $posts);
}
if ($orderby) {
    $query['orderby'] = $orderby;
}
if ($order) {
    $query['order'] = $order;
}

$paged = (get_query_var('paged')) ? get_query_var('paged') : ((get_query_var('page')) ? get_query_var('page') : 1);
$query['paged'] = $paged;
if ($paged == 1) {
    if ($offset) {
        $query['offset'] = $offset;
    }
} 
else {
    if ($offset) {
        $offset = $offset + (($paged - 1) * $count);
        $query['offset'] = $offset;
    }
}

$r = new WP_Query($query);

if (is_singular()) {
    global $post;
    $layout = get_post_meta($post->ID, '_layout', true);
} 
else {
    $layout = 'full';
}

$atts = array(
    'layout' => $layout,
    'image_height' => $image_height,
    'item_id' => $item_id
);

if ($pagination_style == '2') {
    $paginaton_style_class = 'load-button-style';
} 
else if ($pagination_style == '3') {
    $paginaton_style_class = 'scroll-load-style';
} 
else {
    $paginaton_style_class = 'page-nav-style';
}
$id = mt_rand(100, 999);

$output = '<div class="loop-main-wrapper"><section id="mk-news-section-' . $id . '" data-uniqid="' . $item_id . '" data-style="news" class="mk-news-container mk-theme-loop ' . $paginaton_style_class . '" >' . "\n";

if (is_archive()):
    if (have_posts()):
        while (have_posts()):
            the_post();
            $output.= mk_news_loop($atts, 1);
        endwhile;
    endif;
else:
    if ($r->have_posts()):
        while ($r->have_posts()):
            $r->the_post();
            $output.= mk_news_loop($atts, 1);
        endwhile;
    endif;
endif;

$output.= '</section><div class="clearboth"></div>' . "\n\n";

$output.= '<div class="mk-pagination-holder">';
$output.= '<a class="mk-loadmore-button" style="display:none;" href="#"><i class="mk-moon-loop-4"></i><i class="mk-moon-arrow-down-4"></i>' . __('Load More', 'mk_framework') . '</a>';

if ($pagination == 'true') {
    ob_start();
    mk_post_pagination($r, $paged);
    $output.= ob_get_clean();
}
$output.= '</div>';
$output.= '<div class="mk-preloader"></div>';
$output.= '</div>';
wp_reset_postdata();

echo $output;
