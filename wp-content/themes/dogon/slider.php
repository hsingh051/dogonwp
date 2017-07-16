<link href="<?php echo get_template_directory_uri();?>/css/theme-styles.min-blessed1.css" type="text/css" rel="stylesheet" />

<script type='text/javascript' src='//www.dogonleash.ca/wp-content/themes/jupiter/js/head-scripts.js?ver=4.4.10'></script>
<script type="text/javascript">  
    php = {
        hasAdminbar: false,
        json: ([{"name":"page_section","params":{"id":"mk-page-section-596a4dbcb743e","hasBgLayer":true,"bgAttachment":"fixed"}},{"name":"page_section","params":{"id":"mk-page-section-596a4dbcb8a05","hasBgLayer":true,"bgAttachment":"scroll"}},{"name":"page_section","params":{"id":"mk-page-section-596a4dbcba06e","hasBgLayer":true,"bgAttachment":"fixed"}},{"name":"page_section","params":{"id":"mk-page-section-596a4dbcba44c","hasBgLayer":true,"bgAttachment":"fixed"}},{"name":"page_section","params":{"id":"mk-page-section-596a4dbcbab68","hasBgLayer":true,"bgAttachment":"fixed"}},{"name":"page_section","params":{"id":"mk-page-section-596a4dbcbaf3e","hasBgLayer":true,"bgAttachment":"fixed"}},{"name":"page_section","params":{"id":"mk-page-section-596a4dbcbb5e3","hasBgLayer":true,"bgAttachment":"fixed"}},{"name":"page_section","params":{"id":"mk-page-section-596a4dbcbb9c0","hasBgLayer":true,"bgAttachment":"fixed"}},{"name":"page_section","params":{"id":"mk-page-section-596a4dbcbc066","hasBgLayer":true,"bgAttachment":"fixed"}},{"name":"page_section","params":{"id":"mk-page-section-596a4dbcbc443","hasBgLayer":true,"bgAttachment":"fixed"}}] != null) ? [{"name":"page_section","params":{"id":"mk-page-section-596a4dbcb743e","hasBgLayer":true,"bgAttachment":"fixed"}},{"name":"page_section","params":{"id":"mk-page-section-596a4dbcb8a05","hasBgLayer":true,"bgAttachment":"scroll"}},{"name":"page_section","params":{"id":"mk-page-section-596a4dbcba06e","hasBgLayer":true,"bgAttachment":"fixed"}},{"name":"page_section","params":{"id":"mk-page-section-596a4dbcba44c","hasBgLayer":true,"bgAttachment":"fixed"}},{"name":"page_section","params":{"id":"mk-page-section-596a4dbcbab68","hasBgLayer":true,"bgAttachment":"fixed"}},{"name":"page_section","params":{"id":"mk-page-section-596a4dbcbaf3e","hasBgLayer":true,"bgAttachment":"fixed"}},{"name":"page_section","params":{"id":"mk-page-section-596a4dbcbb5e3","hasBgLayer":true,"bgAttachment":"fixed"}},{"name":"page_section","params":{"id":"mk-page-section-596a4dbcbb9c0","hasBgLayer":true,"bgAttachment":"fixed"}},{"name":"page_section","params":{"id":"mk-page-section-596a4dbcbc066","hasBgLayer":true,"bgAttachment":"fixed"}},{"name":"page_section","params":{"id":"mk-page-section-596a4dbcbc443","hasBgLayer":true,"bgAttachment":"fixed"}}] : "",
        styles:  '',
        jsPath: 'http://www.dogonleash.ca/wp-content/themes/jupiter/js'
      };
      
    var styleTag = document.createElement("style"),
      head = document.getElementsByTagName("head")[0];

    styleTag.type = "text/css";
    styleTag.innerHTML = php.styles;
    head.appendChild(styleTag);
    </script>

<?php

if ( ! class_exists( 'BFI_Class_Factory' ) ) {

    class BFI_Class_Factory {

        public static $versions = array();
        public static $latestClass = array();


        public static function addClassVersion( $baseClassName, $className, $version ) {
            if ( empty( self::$versions[ $baseClassName ] ) ) {
                self::$versions[ $baseClassName ] = array();
            }
            self::$versions[ $baseClassName ][] = array(
                'class' => $className,
                'version' => $version
            );
        }


        public static function getNewestVersion( $baseClassName ) {
            if ( empty( self::$latestClass[ $baseClassName ] ) ) {
                usort( self::$versions[ $baseClassName ], array( __CLASS__, "versionCompare" ) );
                self::$latestClass[ $baseClassName ] = self::$versions[ $baseClassName ][0]['class'];
                unset( self::$versions[ $baseClassName ] );
            }
            return self::$latestClass[$baseClassName];
        }


        public static function versionCompare( $a, $b ) {
            return version_compare( $a['version'], $b['version'] ) == 1 ? -1 : 1;
        }
    }

}
if ( ! class_exists( 'BFI_Thumb_1_3' ) ) {

    BFI_Class_Factory::addClassVersion( 'BFI_Thumb', 'BFI_Thumb_1_3', '1.3' );

    class BFI_Thumb_1_3 {

        /** Uses WP's Image Editor Class to resize and filter images
         * Inspired by: https://github.com/sy4mil/Aqua-Resizer/blob/master/aq_resizer.php
         *
         * @param $url string the local image URL to manipulate
         * @param $params array the options to perform on the image. Keys and values supported:
         *          'width' int pixels
         *          'height' int pixels
         *          'opacity' int 0-100
         *          'color' string hex-color #000000-#ffffff
         *          'grayscale' bool
         *          'crop' bool
         *          'negate' bool
         *          'crop_only' bool
         *          'crop_x' bool string
         *          'crop_y' bool string
         *          'crop_width' bool string
         *          'crop_height' bool string
         *          'quality' int 1-100
         * @param $single boolean, if false then an array of data will be returned
         * @return string|array
         */
        public static function thumb( $url, $params = array(), $single = true ) {
            extract( $params );
            global $mk_options;

            //validate inputs
            if ( ! $url ) {
                return false;
            }

            $crop_only = isset( $crop_only ) ? $crop_only : false;

            //define upload path & dir
            $upload_info = wp_upload_dir();
            $upload_dir = $upload_info['basedir'];
            $upload_url = $upload_info['baseurl'];
            $theme_url = get_template_directory_uri();
            $theme_dir = get_template_directory();

            // find the path of the image. Perform 2 checks:
            // #1 check if the image is in the uploads folder
            if ( strpos( $url, $upload_url ) !== false ) {
                $rel_path = str_replace( $upload_url, '', $url );
                $img_path = $upload_dir . $rel_path;

            // #2 check if the image is in the current theme folder
            } else if ( strpos( $url, $theme_url ) !== false ) {
                $rel_path = str_replace( $theme_url, '', $url );
                $img_path = $theme_dir . $rel_path;
            }

            // Fail if we can't find the image in our WP local directory
            if ( empty( $img_path ) ) {
                return $url;
            }

            // check if img path exists, and is an image indeed
            if( ! @file_exists( $img_path ) || ! getimagesize( $img_path ) ) {
                return $url;
            }

            // This is the filename
            $basename = basename( $img_path );

            //get image info
            $info = pathinfo( $img_path );
            $ext = $info['extension'];
            list( $orig_w, $orig_h ) = getimagesize( $img_path );

            // support percentage dimensions. compute percentage based on
            // the original dimensions
            if ( isset( $width ) ) {
                if ( stripos( $width, '%' ) !== false ) {
                    $width = (int) ( (float) str_replace( '%', '', $width ) / 100 * $orig_w );
                }
            }
            if ( isset( $height ) ) {
                if ( stripos( $height, '%' ) !== false ) {
                    $height = (int) ( (float) str_replace( '%', '', $height ) / 100 * $orig_h );
                }
            }

            // The only purpose of this is to determine the final width and height
            // without performing any actual image manipulation, which will be used
            // to check whether a resize was previously done.
            if ( isset( $width ) && $crop_only === false ) {
                //get image size after cropping
                $dims = image_resize_dimensions( $orig_w, $orig_h, $width, isset( $height ) ? $height : null, isset( $crop ) ? $crop : false );
                $dst_w = $dims[4];
                $dst_h = $dims[5];

            } else if ( $crop_only === true ) {
                // we don't want a resize,
                // but only a crop in the image

                // get x position to start croping
                $src_x = ( isset( $crop_x ) ) ? $crop_x : 0;

                // get y position to start croping
                $src_y = ( isset( $crop_y ) ) ? $crop_y : 0;

                // width of the crop
                if ( isset( $crop_width ) ) {
                    $src_w = $crop_width;
                } else if ( isset( $width ) ) {
                    $src_w = $width;
                } else {
                    $src_w = $orig_w;
                }

                // height of the crop
                if ( isset( $crop_height ) ) {
                    $src_h = $crop_height;
                } else if ( isset( $height ) ) {
                    $src_h = $height;
                } else {
                    $src_h = $orig_h;
                }

                // set the width resize with the crop
                if ( isset( $crop_width ) && isset( $width ) ) {
                    $dst_w = $width;
                } else {
                    $dst_w = null;
                }

                // set the height resize with the crop
                if ( isset( $crop_height ) && isset( $height ) ) {
                    $dst_h = $height;
                } else {
                    $dst_h = null;
                }

                // allow percentages
                if ( isset( $dst_w ) ) {
                    if ( stripos( $dst_w, '%' ) !== false ) {
                        $dst_w = (int) ( (float) str_replace( '%', '', $dst_w ) / 100 * $orig_w );
                    }
                }
                if ( isset( $dst_h ) ) {
                    if ( stripos( $dst_h, '%' ) !== false ) {
                        $dst_h = (int) ( (float) str_replace( '%', '', $dst_h ) / 100 * $orig_h );
                    }
                }

                $dims = image_resize_dimensions( $src_w, $src_h, $dst_w, $dst_h, false );
                $dst_w = $dims[4];
                $dst_h = $dims[5];

                // Make the pos x and pos y work with percentages
                if ( stripos( $src_x, '%' ) !== false ) {
                    $src_x = (int) ( (float) str_replace( '%', '', $width ) / 100 * $orig_w );
                }
                if ( stripos( $src_y, '%' ) !== false ) {
                    $src_y = (int) ( (float) str_replace( '%', '', $height ) / 100 * $orig_h );
                }

                // allow center to position crop start
                if ( $src_x === 'center' ) {
                    $src_x = ( $orig_w - $src_w ) / 2;
                }
                if ( $src_y === 'center' ) {
                    $src_y = ( $orig_h - $src_h ) / 2;
                }
            }

            // create the suffix for the saved file
            // we can use this to check whether we need to create a new file or just use an existing one.
            $suffix = (string) filemtime( $img_path ) .
                ( isset( $width ) ? str_pad( (string) $width, 5, '0', STR_PAD_LEFT ) : '00000' ) .
                ( isset( $height ) ? str_pad( (string) $height, 5, '0', STR_PAD_LEFT ) : '00000' ) .
                ( isset( $opacity ) ? str_pad( (string) $opacity, 3, '0', STR_PAD_LEFT ) : '100' ) .
                ( isset( $color ) ? str_pad( preg_replace( '#^\##', '', $color ), 8, '0', STR_PAD_LEFT ) : '00000000' ) .
                ( isset( $grayscale ) ? ( $grayscale ? '1' : '0' ) : '0' ) .
                ( isset( $crop ) ? ( $crop ? '1' : '0' ) : '0' ) .
                ( isset( $negate ) ? ( $negate ? '1' : '0' ) : '0' ) .
                ( isset( $crop_only ) ? ( $crop_only ? '1' : '0' ) : '0' ) .
                ( isset( $src_x ) ? str_pad( (string) $src_x, 5, '0', STR_PAD_LEFT ) : '00000' ) .
                ( isset( $src_y ) ? str_pad( (string) $src_y, 5, '0', STR_PAD_LEFT ) : '00000' ) .
                ( isset( $src_w ) ? str_pad( (string) $src_w, 5, '0', STR_PAD_LEFT ) : '00000' ) .
                ( isset( $src_h ) ? str_pad( (string) $src_h, 5, '0', STR_PAD_LEFT ) : '00000' ) .
                ( isset( $dst_w ) ? str_pad( (string) $dst_w, 5, '0', STR_PAD_LEFT ) : '00000' ) .
                ( isset( $dst_h ) ? str_pad( (string) $dst_h, 5, '0', STR_PAD_LEFT ) : '00000' ) .
                ( ( isset ( $quality ) && $quality > 0 && $quality <= 100 ) ? ( $quality ? (string) $quality : '0' ) : '0' );
            $suffix = self::base_convert_arbitrary( $suffix, 10, 36 );
            $quality = isset($mk_options['image_resize_quality']) ? $mk_options['image_resize_quality'] : 100;

            // use this to check if cropped image already exists, so we can return that instead
            $dst_rel_path = str_replace( '.' . $ext, '', basename( $img_path ) );

            // If opacity is set, change the image type to png
            if ( isset( $opacity ) ) {
                $ext = 'png';
            }


            // Create the upload subdirectory, this is where
            // we store all our generated images
            if ( defined( 'BFITHUMB_UPLOAD_DIR' ) ) {
                $upload_dir .= "/" . BFITHUMB_UPLOAD_DIR;
                $upload_url .= "/" . BFITHUMB_UPLOAD_DIR;
            } else {
                $upload_dir .= "/bfi_thumb";
                $upload_url .= "/bfi_thumb";
            }
            if ( ! is_dir( $upload_dir ) ) {
                wp_mkdir_p( $upload_dir );
            }


            // desination paths and urls
            $destfilename = "{$upload_dir}/{$dst_rel_path}-{$suffix}.{$ext}";

            // The urls generated have lower case extensions regardless of the original case
            $ext = strtolower( $ext );
            $img_url = "{$upload_url}/{$dst_rel_path}-{$suffix}.{$ext}";

            // if file exists, just return it
            if ( @file_exists( $destfilename ) && getimagesize( $destfilename ) ) {
            } else {
                // perform resizing and other filters
                $editor = wp_get_image_editor( $img_path );

                if ( is_wp_error( $editor ) ) return false;

                /*
                 * Perform image manipulations
                 */
                if ( $crop_only === false ) {
                    if ( ( isset( $width ) && $width ) || ( isset( $height ) && $height ) ) {
                        if ( is_wp_error( $editor->resize( isset( $width ) ? $width : null, isset( $height ) ? $height : null, isset( $crop ) ? $crop : false ) ) ) {
                            return false;
                        }
                    }
                } else {
                    if ( is_wp_error( $editor->crop( $src_x, $src_y, $src_w, $src_h, $dst_w, $dst_h ) ) ) {
                        return false;
                    }
                }

                if ( isset( $negate ) ) {
                    if ( $negate ) {
                        if ( is_wp_error( $editor->negate() ) ) {
                            return false;
                        }
                    }
                }

                if ( isset( $opacity ) ) {
                    if ( is_wp_error( $editor->opacity( $opacity ) ) ) {
                        return false;
                    }
                }

                if ( isset( $grayscale ) ) {
                    if ( $grayscale ) {
                        if ( is_wp_error( $editor->grayscale() ) ) {
                            return false;
                        }
                    }
                }

                if ( isset( $color ) ) {
                    if ( is_wp_error( $editor->colorize( $color ) ) ) {
                        return false;
                    }
                }

                // set the image quality (1-100) to save this image at
                if ( isset( $quality ) && $quality > 0 && $quality <= 100 && $ext != 'png' ) {
                    $editor->set_quality( $quality );
                }

                // save our new image
                $mime_type = isset( $opacity ) ? 'image/png' : null;
                $resized_file = $editor->save( $destfilename, $mime_type );
            }

            //return the output
            if ( $single ) {
                $image = $img_url;
            } else {
                //array return
                $image = array (
                    0 => $img_url,
                    1 => isset( $dst_w ) ? $dst_w : $orig_w,
                    2 => isset( $dst_h ) ? $dst_h : $orig_h,
                );
            }

            return $image;
        }


        /** Shortens a number into a base 36 string
         *
         * @param $number string a string of numbers to convert
         * @param $fromBase starting base
         * @param $toBase base to convert the number to
         * @return string base converted characters
         */
        protected static function base_convert_arbitrary( $number, $fromBase, $toBase ) {
            $digits = '0123456789abcdefghijklmnopqrstuvwxyz';
            $length = strlen( $number );
            $result = '';

            $nibbles = array();
            for ( $i = 0; $i < $length; ++$i ) {
                $nibbles[ $i ] = strpos( $digits, $number[ $i ] );
            }

            do {
                $value = 0;
                $newlen = 0;

                for ( $i = 0; $i < $length; ++$i ) {

                    $value = $value * $fromBase + $nibbles[ $i ];

                    if ( $value >= $toBase ) {
                        $nibbles[ $newlen++ ] = (int) ( $value / $toBase );
                        $value %= $toBase;

                    } else if ( $newlen > 0 ) {
                        $nibbles[ $newlen++ ] = 0;
                    }
                }

                $length = $newlen;
                $result = $digits[ $value ] . $result;
            }
            while ( $newlen != 0 );

            return $result;
        }
    }
}

if ( ! function_exists( 'bfi_thumb' ) ) {

    function bfi_thumb( $url, $params = array(), $single = true ) {
        $class = 'BFI_Thumb_1_3';
        $d = call_user_func( array( $class, 'thumb' ), $url, $params, $single );

        return call_user_func( array( $class, 'thumb' ), $url, $params, $single );
    }

}

function mk_thumbnail_image_gen($image, $width, $height) {
    $default = includes_url() . 'images/media/default.png';
    if (($image == $default) || empty($image)) {
        
        $default_url = THEME_IMAGES . '/dummy-images/dummy-' . mt_rand(1, 7) . '.png';
        
        if (!empty($width) && !empty($height)) {
            $image = bfi_thumb($default_url, array('width' => $width, 'height' => $height, 'crop' => true));
            return $image;
        }
        return $default_url;
    } 
    else {
        return $image;
    }
}



$args = array(
                'post_type' => 'portfolio',
                'posts_per_page' => -1,
                'order' => 'DESC',
                'orderby' =>'date',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'portfolio_category',
                        'field' => 'slug',
                        'terms' => 'dog-collars',
                    ),
                ),
             );

$r = new WP_Query($args);

$output .= '<div class="mk-shortcode mk-portfolio-carousel-modern '.$el_class.'">';
$output .= '<div id="mk-portfolio-carousel-'.$id.'" class="mk-flexslider"><ul class="mk-flex-slides">';
$i = 0;

if ( $r->have_posts() ):
	while ( $r->have_posts() ) :
		$r->the_post();
	$i++;

	$hover_overlay_value = get_post_meta(get_the_ID(), '_hover_skin', true);
	$hover_overlay       = !empty($hover_overlay_value) ? (' style="background-color:' . $hover_overlay_value . '"') : '';

	$post_type = get_post_meta( $post->ID, '_single_post_type', true );
	$post_type = !empty( $post_type ) ? $post_type : 'image';
	$link_to = get_post_meta( get_the_ID(), '_portfolio_permalink', true );
	$permalink  = '';
	if ( !empty( $link_to ) ) {
		$link_array = explode( '||', $link_to );
		switch ( $link_array[ 0 ] ) {
		case 'page':
			$permalink = get_page_link( $link_array[ 1 ] );
			break;
		case 'cat':
			$permalink = get_category_link( $link_array[ 1 ] );
			break;
		case 'portfolio':
			$permalink = get_permalink( $link_array[ 1 ] );
			break;
		case 'post':
			$permalink = get_permalink( $link_array[ 1 ] );
			break;
		case 'manually':
			$permalink = $link_array[ 1 ];
			break;
		}
	}

	if ( empty( $permalink ) ) {
		$permalink = get_permalink();
	}

$terms = get_the_terms( get_the_id(), 'portfolio_category' );
$terms_slug = array();
$terms_name = array();
if ( is_array( $terms ) ) {
	foreach ( $terms as $term ) {
		$terms_slug[] = $term->slug;
		$terms_name[] = $term->name;
	}
}

$image_src_array = wp_get_attachment_image_src( get_post_thumbnail_id(), 'full', true );
$image_src = bfi_thumb( $image_src_array[ 0 ], array('width' => 500*$image_quality, 'height' => 350*$image_quality)); 

$output .= '<li>';
$output .= '<div class="portfolio-modern-column mk-portfolio-item ' . $hover_scenarios . '-hover">';
$output .= '<div class="featured-image">';
$output .= '<img width="500" height="350" src="'.mk_thumbnail_image_gen($image_src, 500, 350).'" alt="'.get_the_title().'" title="'.get_the_title().'"  class="item-featured-image" />';

if ($post_type == 'video') {
    $video_id   = get_post_meta($post->ID, '_single_video_id', true);
    $video_site = get_post_meta($post->ID, '_single_video_site', true);
    $video_url  = '';
    if ($video_site == 'vimeo') {
        $video_url = 'http' . ((is_ssl()) ? 's' : '') . '://vimeo.com/' . $video_id . '?autoplay=0';
    } elseif ($video_site == 'youtube') {
        $video_url = 'http' . ((is_ssl()) ? 's' : '') . '://www.youtube.com/watch?v=' . $video_id . '?autoplay=0';
    } elseif ($video_site == 'dailymotion') {
        $video_url = 'http' . ((is_ssl()) ? 's' : '') . '://www.dailymotion.com/video/' . $video_id . '?logo=0';
    }
}


    if ($hover_scenarios == 'fadebox') {
        $output .= '<div class="hover-overlay gradient"' . $hover_overlay . '></div>';
    } else {
        if ($hover_scenarios == 'zoomout') {
            
            $output .= '<div class="image-hover-overlay" style="' . $item_bg_color . '"></div>';
        } else {
            $output .= '<a href="'.$permalink.'">';
            $output .= '<div class="image-hover-overlay"></div>';
            $output .= '</a>';
        }
        
    }
    
    
    if ($hover_scenarios == 'fadebox') {
        $output .= '<div class="grid-hover-icons">';

            $output .= '<a class="permalink-badge project-load" data-fancybox-group="portfolio-grid" data-post-id="' . get_the_ID() . '" rel="portfolio-grid" href="' . $permalink . '"><i class="mk-jupiter-icon-arrow-circle"></i></a>';

        
        if ($post_type == 'image' || $post_type == '') {
            
            $output .= '<a rel="portfolio-grid" title="' . get_the_title() . '" data-fancybox-group="portfolio-masonry-item" class="zoom-badge mk-lightbox" href="' . $image_src_array[0] . '"><i class="mk-jupiter-icon-plus-circle"></i></a>';
            
        } else if ($post_type == 'video') {
            
            $output .= '<a title="' . get_the_title() . '" class="video-badge mk-lightbox" data-fancybox-group="portfolio-masonry-item" href="' . $video_url . '"><i class="mk-jupiter-icon-plus-circle"></i></a>';
        }
        $output .= '</div>';
    }

    if ($hover_scenarios == 'light-zoomin' ) {
        $output .= '<div class="grid-hover-icons">';

        // if ($disable_permalink == 'true') {
            $output .= '<a class="permalink-badge project-load" data-fancybox-group="portfolio-grid" data-post-id="' . get_the_ID() . '" rel="portfolio-grid" href="' . $permalink . '"><i class="mk-jupiter-icon-arrow-circle"></i></a>';
        // }
        
        if ($post_type == 'image' || $post_type == '') {
            $output .= '<a rel="portfolio-grid" title="' . get_the_title() . '" data-fancybox-group="portfolio-masonry-item" class="zoom-badge mk-lightbox" href="' . $image_src_array[0] . '"><i class="mk-jupiter-icon-plus-circle"></i></a>';
        } else if ($post_type == 'video') {
            $output .= '<a title="' . get_the_title() . '" class="video-badge mk-lightbox" data-fancybox-group="portfolio-masonry-item" href="' . $video_url . '"><i class="mk-jupiter-icon-plus-circle"></i></a>';
        }
        
        $output .= '</div>';
        
    }

    if ($hover_scenarios != 'fadebox' && $hover_scenarios != 'light-zoomin' && $hover_scenarios != 'none') {
        $output .= '<div class="grid-hover-icons">';

        // if ($disable_permalink == 'true') {
            $output .= '<a class="permalink-badge project-load" data-fancybox-group="portfolio-grid" data-post-id="' . get_the_ID() . '" rel="portfolio-grid" href="' . $permalink . '"><i class="mk-jupiter-icon-arrow-circle"></i></a>';
        // }

        if ($post_type == 'image' || $post_type == '') {
            $output .= '<a rel="portfolio-grid" title="' . get_the_title() . '" data-fancybox-group="portfolio-masonry-item" class="zoom-badge mk-lightbox" href="' . $image_src_array[0] . '"><i class="mk-jupiter-icon-plus-circle"></i></a>';
        } else if ($post_type == 'video') {
            $output .= '<a title="' . get_the_title() . '" class="video-badge mk-lightbox" data-fancybox-group="portfolio-masonry-item" href="' . $video_url . '"><i class="mk-jupiter-icon-plus-circle"></i></a>';
        }
  
        $output .= '</div>';
        
    }

    if ($hover_scenarios != 'none') {
        
        $output .= ($hover_scenarios == 'slidebox') ? '<div class="portfolio-meta"' . $hover_overlay . '>' : '<div class="portfolio-meta">';
        $output .= '<h3 class="the-title">' . get_the_title() . '</h3><div class="clearboth"></div>';
        if ($meta_type == 'category') {
            $output .= '<div class="portfolio-categories">' . implode(' ', $terms_name) . ' </div>';
        } else {
            $output .= '<time class="portfolio-date" datetime="' . get_the_date() . '">' . get_the_date() . '</time>';
        }
        $output .= '</div><!-- Portfolio meta -->';
        
    }


$output .= '</div>';
$output .= '</div>';
$output .= '</li>';


endwhile;
endif;
wp_reset_query();

$output .= '</ul></div><div class="clearboth"></div></div>';

echo $output;
?>