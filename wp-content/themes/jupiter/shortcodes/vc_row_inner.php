<?php
$output = $el_class = '';
extract(shortcode_atts(array(
	'id'			=> '',
	'padding' 		=> 0,
	'attached' 		=> 'false',
	'visibility' 	=> '',
	'css' 			=> '',
	'animation' 	=> '',
    'el_class' 		=> '',
), $atts));

wp_enqueue_script( 'wpb_composer_front_js' );

$fullwidth_start = $output = $fullwidth_end = '';

$id = !empty($id) ? (' id="'.$id.'"') : '';

$padding_css = ($attached == 'true') ? 'add-padding-'.$padding : '';

$animation_css = ( $animation != '' ) ? ' mk-animate-element ' . $animation . ' ' : '';

$output .= '<div'.$id.' class="wpb_row vc_inner vc_row '.$el_class.' vc_row-fluid '.$visibility.' '.$padding_css.' attched-'.$attached.' '. get_row_css_class(). vc_shortcode_custom_css_class( $css, ' ' ).$animation_css.'">';
$output .= wpb_js_remove_wpautop($content);
$output .= '</div>';

echo $output;
