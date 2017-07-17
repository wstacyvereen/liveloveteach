<?php
/*
* Template Name: Training Page
* Template Post Type: training
*/
get_header(); ?>
<?php get_template_part( 'template-parts/featured-image' ); ?>

<div <div id="page-full-width" role="main">

          <?php the_content(); ?>
          <?php dynamic_sidebar('content-widgets'); ?>
</div>

<?php get_footer(); ?>
