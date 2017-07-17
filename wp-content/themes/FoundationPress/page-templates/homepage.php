<?php
/*
Template Name: Homepage
Template Post Type: page

*/
get_header(); ?>

<div>
          <?php the_content(); ?>
		  <?php dynamic_sidebar('content-widgets'); ?>

</div>

<?php get_footer();
