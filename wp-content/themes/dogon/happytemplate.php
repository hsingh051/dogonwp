<?php

/**
 Template Name: Happy Customer

 * The template for displaying pages

 *

 * This is the template that displays all pages by default.

 * Please note that this is the WordPress construct of pages and that

 * other "pages" on your WordPress site will use a different template.

 *

 * @package WordPress

 * @subpackage Twenty_Sixteen

 * @since Twenty Sixteen 1.0

 */



get_header(); ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.0.47/jquery.fancybox.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.0.47/jquery.fancybox.min.js"></script>

<section>
	<div class="pgbanner">
		<?php
			while ( have_posts() ) : the_post();
				if ( has_post_thumbnail() ) {
				    the_post_thumbnail();
				}
			endwhile;
		?>
	</div>
</section>

<style type="text/css">
	#main p img{
		float: left;
		margin-right: 10px;
	}
	#main p strong{
		 font-size: 30px;
    line-height: 47px;
	}
</style>
<div class="container">

	<main id="main" class="site-main" role="main">
		<?php

		// Start the loop.

		while ( have_posts() ) : the_post();

			?>
			<div class="toppage">
				<h3 class="titleprdmain">
					<?php 
						if(@get_field( "dpage_title" )){
							echo get_field( "dpage_title" );
						}else{
							the_title();
						}
							
					?>
				</h3>
				<?php the_breadcrumb();?>
			</div>
			<?php

			// Include the page content template.

			//get_template_part( 'template-parts/content', 'page' );

			the_content();


			// If comments are open or we have at least one comment, load up the comment template.

			if ( comments_open() || get_comments_number() ) {

				//comments_template();

			}



			// End of the loop.

		endwhile;

		?>
		<?php
					$args = array(
								'post_type' => 'portfolio',
								'posts_per_page' => -1,
								'order' => 'DESC',
								'orderby' =>'date',
						        'tax_query' => array(
						            array(
						                'taxonomy' => 'portfolio_category',
						                'field' => 'slug',
						                'terms' => 'happy-customers',
						            ),
						        ),
						     );

			     $loop = new WP_Query($args);
		?>	
				<div class="happylist">
					<?php
					     if($loop->have_posts()) {
					     	$i=1;
					        while($loop->have_posts()) : $loop->the_post();
					?>
								<div class="hpyblock">
					        		
									<a data-fancybox="gallery" data-caption="<?php echo get_the_title();?>" href="<?php echo get_the_post_thumbnail_url(get_the_ID(), 'full' );?>"><?php echo get_the_post_thumbnail( get_the_ID(), 'large' ); ?></a>
					        		<div class="hpyblocktxt">
					        			<label>
					        				<?php echo get_the_title();?>
					        			</label>
					        			<p>Happy Customers</p>
					        		</div>
					        	</div>
					 <?php       	
					 			if($i%3==0){
					 				echo '<div class="clearfix"></div>';
					        	}
					        $i++;
					         endwhile;
					    }

						wp_reset_query();
					?>
				</div>     

		



	</main><!-- .site-main -->



	<?php //get_sidebar( 'content-bottom' ); ?>



</div><!-- .content-area -->



<?php //get_sidebar(); ?>

<?php get_footer(); ?>

