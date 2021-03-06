<?php
/**
 Template Name: FAQ
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



<div class="container">

	<main id="main" class="site-main" role="main">

		<?php

		// Start the loop.

		while ( have_posts() ) : the_post();

			?>
			<div class="toppage">
				<h3 class="titleprdmain"><?php the_title();?></h3>
				<?php the_breadcrumb();?>
			</div>
			<?php
		endwhile;
				include('mk_faq.php');
			?>
			<?php
			while ( have_posts() ) : the_post();
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



	</main><!-- .site-main -->



	<?php //get_sidebar( 'content-bottom' ); ?>



</div><!-- .content-area -->

<script type="text/javascript">
	$('.mk-toggle-title').on('click',function(){
		$(this).next('.mk-toggle-pane').slideToggle();
		$(this).children("i.change").toggleClass("fa-angle-right fa-angle-down");

		
	});
	$(".filter-faq ul li a").on('click', function(){
			
			$(".mk-faq-container .mk-toggle").css('display','none');
			$(".filter-faq ul li a").removeClass('current');
			$(this).addClass('current');
			var cl = $(this).attr('data-filter');
			if(cl == ""){
				$(".mk-faq-container .mk-toggle").slideToggle();
			}else{
				$(".mk-faq-container .mk-toggle."+cl).slideToggle();
			}
		});
</script>

<?php //get_sidebar(); ?>

<?php get_footer(); ?>

