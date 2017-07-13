<?php get_header(); ?>
<div id="theme-page" <?php echo get_schema_markup('main'); ?>>
	<div class="mk-main-wrapper-holder">
		<div class="theme-page-wrapper full-layout mk-grid vc_row-fluid">
			<div class="theme-content">
				<?php
				echo do_shortcode( '[mk_news image_height="260" pagination_style="2"]' );
				?>
			</div>
		<div class="clearboth"></div>
		</div>
	</div>	
</div>
<?php get_footer(); ?>
