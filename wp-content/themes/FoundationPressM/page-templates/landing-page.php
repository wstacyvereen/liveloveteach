<?php
/*
* Template Name: Landing Page
* Template Post Type: landing-page, page
*/
get_header(); ?>

<div>
          <?php the_content(); ?>
          <?php dynamic_sidebar('content-widgets'); ?>
</div>

<?php get_footer();
