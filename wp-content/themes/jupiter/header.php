<!DOCTYPE html>
<html xmlns="http<?php
echo (is_ssl()) ? 's' : ''; ?>://www.w3.org/1999/xhtml" <?php
language_attributes(); ?>>

<head>
    <?php
wp_head(); ?>
</head>


<?php
global $mk_options;

$post_id = global_get_post_id();

$mk_header_class = $show_header_old = $show_header = $transparent_header = $transparent_header_skin = $trans_header_class = $page_is_transparent = $header_sticky_style_css = '';

$toolbar_toggle = !empty($mk_options['theme_toolbar_toggle']) ? $mk_options['theme_toolbar_toggle'] : 'true';
$header_style = !empty($mk_options['theme_header_style']) ? $mk_options['theme_header_style'] : 1;
$header_align = !empty($mk_options['theme_header_align']) ? $mk_options['theme_header_align'] : 'left';

$header_grid_start = ($mk_options['header_grid'] == 'true') ? '<div class="mk-grid header-grid">' : '';
$header_grid_end = ($mk_options['header_grid'] == 'true') ? '</div>' : '';
$header_width_class = ($mk_options['header_grid'] == 'true') ? 'boxed-header' : 'full-header';
$sticky_header_style = isset($mk_options['header_sticky_style']) ? $mk_options['header_sticky_style'] : 'fixed';
$sticky_header_offset = isset($mk_options['sticky_header_offset']) ? $mk_options['sticky_header_offset'] : 'header';

$header_style_3_menu_style = !empty($mk_options['header_style3_structure']) ? $mk_options['header_style3_structure'] : 'header_dashboard_style';

if ($post_id) {
    $enable = get_post_meta($post_id, '_enable_local_backgrounds', true);
    
    if ($enable == 'true') {
        $header_style_meta = get_post_meta($post_id, 'theme_header_style', true);
        $header_align_meta = get_post_meta($post_id, 'theme_header_align', true);
        $toolbar_toggle_meta = get_post_meta($post_id, 'theme_toolbar_toggle', true);
        $transparent_header_meta = get_post_meta($post_id, '_transparent_header', true);
        $transparent_header_skin_meta = get_post_meta($post_id, '_transparent_header_skin', true);
        $sticky_header_offset_meta = get_post_meta($post_id, '_sticky_header_offset', true);
        $trans_header_remove_bg_meta = get_post_meta($post_id, '_trans_header_remove_bg', true);
        
        $toolbar_toggle = (isset($toolbar_toggle_meta) && !empty($toolbar_toggle_meta)) ? $toolbar_toggle_meta : $toolbar_toggle;
        $header_style = (isset($header_style_meta) && !empty($header_style_meta)) ? $header_style_meta : $header_style;
        $header_align = (isset($header_align_meta) && !empty($header_align_meta)) ? $header_align_meta : $header_align;
        
        $transparent_header = (isset($transparent_header_meta) && !empty($transparent_header_meta)) ? $transparent_header_meta : 'false';
        $transparent_header_skin = (isset($transparent_header_skin_meta) && !empty($transparent_header_skin_meta)) ? $transparent_header_skin_meta : 'light';
        $sticky_header_offset = (isset($sticky_header_offset_meta) && !empty($sticky_header_offset_meta)) ? $sticky_header_offset_meta : $sticky_header_offset;
        $trans_header_remove_bg = (isset($trans_header_remove_bg_meta) && !empty($trans_header_remove_bg_meta)) ? $trans_header_remove_bg_meta : 'true';
    }
}

global $app_modules;
$app_modules[] = array(
    'name' => 'theme_header',
    'params' => array(
        'id' => 'mk-header',
        'height' => $mk_options['header_height'],
        'stickyHeight' => $mk_options['header_scroll_height'],
        'stickyOffset' => $sticky_header_offset,
        'hasToolbar' => $toolbar_toggle
    )
);

if ($post_id) {
    $show_header = get_post_meta($post_id, '_template', true);
}

if ($header_style == 4) {
    $vertical_header_logo_align = (isset($mk_options['vertical_header_logo_align']) && !empty($mk_options['vertical_header_logo_align'])) ? $mk_options['vertical_header_logo_align'] : 'center';
} 
else {
    $header_sticky_style_css = 'sticky-style-' . $sticky_header_style;
    if ($sticky_header_style == 'lazy') {
        $header_sticky_style_css = 'sticky-style-fixed';
    }
}

if ($transparent_header == 'true') {
    $page_is_transparent = ' class="mk-transparent-header" ';
    $trans_header_class = 'transparent-header ' . $transparent_header_skin . '-header-skin remove-header-bg-' . $trans_header_remove_bg;
}
?>

<body <?php
body_class(mk_get_body_class($post_id)); ?> data-backText="<?php
_e('Back', 'mk_framework'); ?>" data-vm-anim="<?php
echo $mk_options['vertical_menu_anim']; ?>" <?php
echo get_schema_markup('body'); ?>
data-adminbar="<?php
echo is_admin_bar_showing() ?>">

<?php
do_action('theme_after_body_tag_start');
?>

<div id="mk-boxed-layout">
<div id="mk-theme-container"<?php
echo $page_is_transparent; ?>>

<?php
?>

<header id="mk-header" data-height="<?php
echo $mk_options['header_height']; ?>" data-hover-style="<?php
echo $mk_options['main_nav_hover']; ?>" data-transparent-skin="<?php
echo $transparent_header_skin; ?>" data-header-style="<?php
echo $header_style; ?>" data-sticky-height="<?php
echo $mk_options['header_scroll_height']; ?>" data-sticky-style="<?php
echo $sticky_header_style; ?>" data-sticky-offset="<?php
echo $sticky_header_offset; ?>" class="header-style-<?php
echo $header_style; ?> header-align-<?php
echo $header_align; ?> header-toolbar-<?php
echo $toolbar_toggle; ?> <?php
echo $header_sticky_style_css; ?> <?php
echo (($mk_options['banner_size'] == 'true') ? ' mk-background-stretch' : '') ?> <?php
echo $header_width_class; ?> <?php
echo $trans_header_class; ?>" <?php
echo get_schema_markup('header'); ?>>

<?php
if ($show_header != 'no-header' && $show_header != 'no-header-title' && $show_header != 'no-header-title-footer' && $show_header != 'no-header-footer'): ?>

<div class="mk-header-holder">

<?php
    do_action('header_banner_video');
    
    /************************************************************
    Header Toolbar
    *************************************************************/
    if ($toolbar_toggle == 'true'): ?>
    <div class="mk-header-toolbar">
      
      <?php
        echo $header_grid_start;
        
        do_action('header_toolbar_before');
         // to add elements using child themes
        
        do_action('header_toolbar_menu');
        
        do_action('header_toolbar_date');
        
        do_action('header_toolbar_contact');
        
        do_action('header_toolbar_tagline');
        
        do_action('theme_wpml_language_switch');
        
        do_action('header_search', 'toolbar');
        
        do_action('header_social', 'toolbar');
        
        do_action('header_toolbar_login');
        
        do_action('header_toolbar_subscribe');
        
        do_action('header_toolbar_after');
         // to add elements using child themes
        
        echo $header_grid_end;
?>
    <div class="clearboth"></div>
  </div>
<?php
    endif;
    
    /************************************************************
    End Of Header Toolbar
    *************************************************************/
?>


<div class="mk-header-inner">

  <?php
    
    /* Header background */
?>
  <div class="mk-header-bg <?php
    echo (($mk_options['header_size'] == 'true') ? 'mk-background-stretch' : ''); ?>"></div>



  <?php
    
    /* Header toolbar responsive toggle icon */
?>
  <?php
    if ($toolbar_toggle == 'true' && $header_style != 4) { ?>
    <div class="mk-toolbar-resposnive-icon"><i class="mk-icon-chevron-down"></i></div>
  <?php
    } ?>

  <?php
    
    // Will output main grid div if boxed layout option is enabled
    if ($header_style != 4) {
        echo $header_grid_start;
    }
?>

  <?php
    $one_row_nav_args = array(
        'style' => $header_style,
        'search_location' => $mk_options['header_search_location'],
        'nav_location' => 'one_row'
    );
    do_action('header_main_navigation', $one_row_nav_args, 10, 1);
    
    // Menu trigger icon for header style 3
    if ($header_style == 3) {
        $header_style3_structure = $header_style_3_menu_style == 'header_dashboard_style' ? 'header_dashboard_style' : $header_style_3_menu_style;
        echo do_action('header_checkout');
        echo '<div class="mk-dashboard-trigger ' . $header_style3_structure . '">
            <div class="mk-css-icon-menu icon-size-' . $mk_options['header_burger_size'] . '">
              <div class="mk-css-icon-menu-line-1"></div>
              <div class="mk-css-icon-menu-line-2"></div>
              <div class="mk-css-icon-menu-line-3"></div>
            </div>
          </div>';
    }
?>



<?php
    
    // no resposnive menu in header style 3
    if ($header_style != 3) {
        echo '<div class=" mk-nav-responsive-link">
            <div class="mk-css-icon-menu">
              <div class="mk-css-icon-menu-line-1"></div>
              <div class="mk-css-icon-menu-line-2"></div>
              <div class="mk-css-icon-menu-line-3"></div>
            </div>
          </div>';
    }
?>
  
  
  <?php
    do_action('header_logo'); ?>
  <?php
    do_action('header_inner_right_before');
    
    // to add elements using child themes
    do_action('header_inner_right_after');
    
    // to add elements using child themes
    
    
?>
  
  <?php
    if ($header_style == 2):
        echo $header_grid_end;
    endif; ?>

  <div class="clearboth"></div>




  <?php
    $two_row_nav_args = array(
        'style' => $header_style,
        'search_location' => $mk_options['header_search_location'],
        'nav_location' => 'two_row'
    );
    
    do_action('header_main_navigation', $two_row_nav_args, 10, 1);
    
    do_action('vertical_navigation');
?>



<?php
    if ($header_style == 1 || $header_style == 3):
        echo $header_grid_end;
    endif; ?>

<?php
    
    /* Header right section elements */
?>
  <div class="mk-header-right">
  
  <?php
    do_action('header_right_before');
    
    // to add elements using child themes
    
    //do_action('header_checkout');
    
    do_action('start_tour_link');
    
    do_action('header_social', 'header');
    
    do_action('header_search', 'header');
    
    do_action('header_copyright');
    
    do_action('header_right_after');
    
    // to add elements using child themes
    
    
?>

  </div>
<?php
    
    /* End of header right section */
?>



</div>


</div>

  <div class="clearboth"></div>
<?php
    
    /* End of Header Inner */
?>

<?php
endif;

// End for option to disable header

?>



<?php
if (!empty($sticky_header_style)): ?>
<div class="mk-header-padding-wrapper"></div>
<?php
endif; ?>


<div class="clearboth"></div>

<div class="mk-zindex-fix">    

<?php
if ($post_id) {
    do_action('theme_slideshow', $post_id);
}

do_action('page_title');
?>
</div>

<div class="clearboth"></div>

<?php

/* Will be inside responsive Navigation */
if ($show_header != 'no-header' && $show_header != 'no-header-title' && $show_header != 'no-header-title-footer' && $show_header != 'no-header-footer') {
    do_action('responsive_search');
}
?>

</header>