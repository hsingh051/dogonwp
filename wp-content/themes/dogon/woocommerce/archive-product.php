<?php
/**
 * The Template for displaying product archives, including the main shop page which is a post type archive.
 *
 * Override this template by copying it to yourtheme/woocommerce/archive-product.php
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
get_header( 'shop' ); ?>

<section>
	<div class="container">
		
		
		<?php
		/**
		 * woocommerce_before_main_content hook
		 *
		 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
		 * @hooked woocommerce_breadcrumb - 20
		 */
		remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20, 0); 
		do_action( 'woocommerce_before_main_content' );
	?>
	<div class="col-md-3"><?php dynamic_sidebar('woocommerce_sidebar' ); ?></div> 
	<div class="col-md-9 maincatpg">
		<?php 
		$args = array('delimiter' =>'<span>&nbsp;>>&nbsp;</span>' , );
			woocommerce_breadcrumb($args); 
		?>
		<?php
			// Change "Default Sorting" to "Our sorting" on shop page and in WC Product Settings
			function my_change_default_sorting_name( $catalog_orderby ) {
			    $catalog_orderby = str_replace("Default sorting", "Default", $catalog_orderby);
			    $catalog_orderby = str_replace("Sort by popularity", "Popularity", $catalog_orderby);
			    $catalog_orderby = str_replace("Sort by newness", "Newest", $catalog_orderby);
			    $catalog_orderby = str_replace("Sort by price: low to high", "Low to High", $catalog_orderby);
			    $catalog_orderby = str_replace("Sort by price: high to low", "High to Low", $catalog_orderby);
			    return $catalog_orderby;
			}
			add_filter( 'woocommerce_catalog_orderby', 'my_change_default_sorting_name' );
			add_filter( 'woocommerce_default_catalog_orderby_options', 'my_change_default_sorting_name' );
		?>

		<?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>

			<!-- <h1 class="page-title"><?php //woocommerce_page_title(); ?></h1> -->

		<?php endif; ?>

		<?php
			/**
			 * woocommerce_archive_description hook
			 *
			 * @hooked woocommerce_taxonomy_archive_description - 10
			 * @hooked woocommerce_product_archive_description - 10
			 */
			do_action( 'woocommerce_archive_description' );
		?>

		<?php if ( have_posts() ) : ?>

			<div class="topcatprd">
				<?php
					/**
					 * woocommerce_before_shop_loop hook
					 *
					 * @hooked woocommerce_result_count - 20
					 * @hooked woocommerce_catalog_ordering - 30
					 */
					add_action( 'woocommerce_before_shop_loop', 'woocommerce_pagination', 10 );

					do_action( 'woocommerce_before_shop_loop' );
				?>
			</div>


			<?php woocommerce_product_loop_start(); ?>

				<?php woocommerce_product_subcategories(); ?>

				<?php while ( have_posts() ) : the_post(); ?>

					<?php wc_get_template_part( 'content', 'product' ); ?>

				<?php endwhile; // end of the loop. ?>

			<?php woocommerce_product_loop_end(); ?>

			<?php
				/**
				 * woocommerce_after_shop_loop hook
				 *
				 * @hooked woocommerce_pagination - 10
				 */
				do_action( 'woocommerce_after_shop_loop' );
			?>

		<?php elseif ( ! woocommerce_product_subcategories( array( 'before' => woocommerce_product_loop_start( false ), 'after' => woocommerce_product_loop_end( false ) ) ) ) : ?>

			<?php wc_get_template( 'loop/no-products-found.php' ); ?>

		<?php endif; ?>
		</div>

	<?php
		/**
		 * woocommerce_after_main_content hook
		 *
		 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
		 */
		do_action( 'woocommerce_after_main_content' );
	?>

	<?php
		/**
		 * woocommerce_sidebar hook
		 *
		 * @hooked woocommerce_get_sidebar - 10
		 */
		//do_action( 'woocommerce_sidebar' );
	?>
	</div>
</section>
<script type="text/javascript">
	$(document).ready(function() {
	    $('.widget_product_categories ul ul').hide();
	    $('.widget_product_categories ul li').click(function() {
	        $(this).children('.subLink').toggle('slow');
	        $('.widget_product_categories li:has(ul)').toggleClass('pm');
	    });
	    $('.widget_product_categories li:has(ul)').addClass('myLink');
	    $('.widget_product_categories ul ul').addClass('subLink');

	    $('.widget_product_categories .myLink > a').attr('href','javascript:void(0);');
	     $(".topcatprd").append("<div class='topcatprdinr'></div>");
	    $(".woocommerce-ordering").appendTo('.topcatprdinr');
	    $( ".topcatprdinr form select.per_page" ).parent( "form.woocommerce-ordering" ).addClass('addtxt');

	    $(".addtxt").prepend( "<label>Show: &nbsp;</label>");
	    $(".topcatprd").css('display','block');
	});
</script>	


<?php get_footer( 'shop' ); ?>
