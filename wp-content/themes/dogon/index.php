<?php
/**
 Template Name: Home
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 * @version 1.0
 */

get_header(); ?>

<section class="mslider">
	<?php putRevSlider( "Dog Collar Banner" ) ?>
</section>
<?php
	if ( have_posts() ) :

		/* Start the Loop */
		while ( have_posts() ) : the_post();
?>
			<section class="homeBlocks">
				<div class="container" >
					<div class="col-md-4">
						<?php
							$image1 = get_field( "mimage_1" );
							if( @$image1 ) {
							?>	
								<img width="100%" src="<?php echo $image1['sizes']['medium']?>">
								<div class="hmblck">
									<label><?php echo get_field( "mtitle_1" );?></label>
									<a href="<?php echo get_field( "murl_1" );?>"><button class="btsee">SELL ALL</button></a>
								</div>						
							<?php
							}
						?>
					</div>
					<div class="col-md-4">
						<?php
							$image1 = get_field( "mimage_2" );
							if( @$image1 ) {
							?>	
								<img width="100%" src="<?php echo $image1['sizes']['medium']?>">
								<div class="hmblck">
									<label><?php echo get_field( "mtitle_2" );?></label>
									<a href="<?php echo get_field( "murl_2" );?>"><button class="btsee">SELL ALL</button></a>
								</div>						
							<?php
							}
						?>
					</div>
					<div class="col-md-4">
						<?php
							$image1 = get_field( "mimage_3" );
							if( @$image1 ) {
							?>	
								<img width="100%" src="<?php echo $image1['sizes']['medium']?>">
								<div class="hmblck">
									<label><?php echo get_field( "mtitle_3" );?></label>
									<a href="<?php echo get_field( "murl_3" );?>"><button class="btsee">SELL ALL</button></a>
								</div>						
							<?php
							}
						?>
					</div>	
				</div>	
			</section>
<?php
		endwhile;
	endif;
?>

<section class="midsec">	
	<div class="container">
		<div class="col-md-7">
			<label class="midseclb">Happy Customers</label>
			<div class="midcont">
				<?php
					$args = array(
								'post_type' => 'portfolio',
								'posts_per_page' => 3,
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

			     if($loop->have_posts()) {
			        while($loop->have_posts()) : $loop->the_post();
			        ?>
			        	<div class="hpyblock">
			        		<?php echo get_the_post_thumbnail( get_the_ID(), 'medium' ); ?>
			        		<div class="hpyblocktxt">
			        			<label>
			        				<?php echo get_the_title();?>
			        			</label>
			        			<p>Happy Customers</p>
			        		</div>
			        	</div>
			        <?php	
			            //echo '<a href="'.get_permalink().'">'.get_the_title().'</a><br>';
			        endwhile;
			     }
				?>
			</div>
			<div class="viewalltext">
				<a href="#">>> VIEW ALL</a>	
			</div>
		</div>
		<div class="col-md-5">
			<label class="midseclb">Featured Events</label>
			<div class="midcont">
				<?php
					$args = array(
								'post_type' => 'devents',
								'posts_per_page' => '-1',
								'order' => 'DESC',
								'orderby' =>'date',
						        'tax_query' => array(
						            array(
						                'taxonomy' => 'event_category',
						                'field' => 'slug',
						                'terms' => 'featured',
						            ),
						        ),
						     );

			     $loop1 = new WP_Query($args);

			     	if($loop1->have_posts()) {
			    ?>
			    	<ul class="eventlist">
				        <?php
				        	while($loop1->have_posts()) : $loop1->the_post();
				        ?>
				        		<li><?php echo get_the_title().', '. get_field( "elocation" ).' | '. get_field( "edate" );?></li>
				      	<?php	
				            //echo '<a href="'.get_permalink().'">'.get_the_title().'</a><br>';
				        	endwhile;
				        ?>
				        <li>More events to be added. Have an event you would like to invite us or suggest? Please <a title="Events and Festivals in Ontario Canada" href="#">email</a> us.</li>
			        </ul>
			    <?php    
			     	}
				?>
			</div>
			<div class="viewalltext">
				<a href="#">>> VIEW ALL</a>	
			</div>
		</div>
	</div>	
</section>

<section class="homefeature">
	<div class="container">
		<div class="col-md-12 ferbar">
			<hr>
			<h1><span>FEATURED PRODUCTS</span></h1>	
		</div>
		<div class="fetprd">
			<?php echo do_shortcode( '[featured_products per_page="8" columns="4"]' );?>
		</div>
		<script type="text/javascript">
			jQuery(document).ready(function(){
				jQuery('a.add_to_cart_button').addClass('brbutton');
				jQuery('a.add_to_cart_button').text('View');
			});
		</script>
	</div>
	
</section>
<section class="aboutsection">
	<div class="container">
		<div class="col-md-offset-6 col-md-6 col-sm-12 col-sm-offset-0 hmabttext">
			<?php
				if ( have_posts() ) :

					/* Start the Loop */
					while ( have_posts() ) : the_post();

						/*
						 * Include the Post-Format-specific template for the content.
						 * If you want to override this in a child theme, then include a file
						 * called content-___.php (where ___ is the Post Format name) and that will be used instead.
						 */
						the_content();
					endwhile;

					?>
						<a href="#" class="orbutton"> Read More</a>
					<?php	

				endif;
			?>
		</div>
	</div>
</section>

<section class="facesection">
	<div class="container">
		<h1>SEE OUR COMPLETE COLLECTION ON FACEBOOK</h1>

	</div>
	<link href="<?php echo get_template_directory_uri();?>/css/flexslider.css" type="text/css" rel="stylesheet" />

	<script type='text/javascript' src='<?php echo get_template_directory_uri();?>/js/jquery.flexslider-min.js'></script>
	<script type="text/javascript">
		jQuery(window).load(function() {
		  jQuery('.flexslider').flexslider({
		    animation: "slide",
		    animationLoop: false,
		    itemWidth: 210,
		    itemMargin: 0,
		    minItems: 2,
		    maxItems: 6,
		    controlNav: false
		  });
		});
	</script>
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
	                        'terms' => 'dog-collars',
	                    ),
	                ),
	             );

		$r = new WP_Query($args);

		// echo "<pre>";
		// print_r($r);
		// die;
	?>
	<style type="text/css">
		.flex-direction-nav .flex-next{
			right: 0;
		}
		.flex-direction-nav .flex-prev{
			left: 0;
		}
		.flex-direction-nav a{
			height: 47px;
		}
		.flexslider .slides img{
			max-height: 180px;
		}
		.flexslider{
			border: none;
			margin-bottom: 0;
		}
	</style>
	<div class="rowd" style="width: 100%">
		<div class="flexslider">
		  
		  <?php 
		  		if($r->have_posts()) {
				    ?>
				    	<ul class="slides">
					        <?php
					        	while($r->have_posts()) : $r->the_post();
					        ?>
					        		<li><?php echo get_the_post_thumbnail( get_the_ID(), 'thumbnail' ); ?></li>
					      	<?php	
					            //echo '<a href="'.get_permalink().'">'.get_the_title().'</a><br>';
					        	endwhile;
					        ?>
				        </ul>
				    <?php    
				     	}
					?>
		    <!-- items mirrored twice, total of 12 -->
		  
		</div>
	</div>
</section>


<?php get_footer();