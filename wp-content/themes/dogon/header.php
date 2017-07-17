<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 * @version 1.0
 */

?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js no-svg">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<link href="<?php echo get_template_directory_uri();?>/css/bootstrap.css" type="text/css" rel="stylesheet" />
	<link href="<?php echo get_template_directory_uri();?>/css/custom.css" type="text/css" rel="stylesheet" />
	<link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
	<?php wp_head(); ?>
	<script type="text/javascript">
		var $ = jQuery.noConflict();
	</script>
	<script type="text/javascript" src="<?php echo get_template_directory_uri();?>/js/bootstrap.min.js"></script>

</head>

<body <?php body_class(); ?>>
	<div class="top_bar">
		<div class="container">
			
			<?php wp_nav_menu( array( 'menu'=>'Top Bar','container_class' => 'top_right', 'container_id' => '', 'menu_class' => '')); ?>
			<div class="top_search">
				<form role="search" method="get" id="searchform" action="<?php home_url( '/' );?>" >
					
			    	<input class="tstext" type="text" placeholder="Enter your search here.." value="<?php echo get_search_query();?>" name="s" id="s" />
				    <input type="image" class="tsimg" src='<?php echo get_template_directory_uri();?>/images/topsearch.jpg' />
				    
			    </form>
			</div>
		</div>
	</div>

	<nav class="navbar navbar-default">
	  <div class="container">
		<div class="navbar-header">
		  <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
			<span class="sr-only">Toggle navigation</span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
		  </button>
		  <a class="navbar-brand mlogo" href="<?php echo get_site_url();?>">Dog on Leash</a>
		</div>
		<div class="fbbutton">
			<a href="https://www.facebook.com/dogcollarsandleashes" target="_blank"><img src="<?php echo get_template_directory_uri();?>/images/fb.png"></a>
		</div>
		<?php wp_nav_menu( array( 'menu'=>'Main Menu','container_class' => 'navbar-collapse collapse mrgTop10', 'container_id' => 'navbar', 'menu_class' => 'nav navbar-nav navbar-right')); ?>
			
		</div>
	</nav>

	