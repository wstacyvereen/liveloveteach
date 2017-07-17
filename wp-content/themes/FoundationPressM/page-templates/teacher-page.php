<?php
/*
* Template Name: Coach Page
* Template Post Type: coach
*/
get_header(); ?>

<div>
          <?php the_content(); ?>
          <?php dynamic_sidebar('content-widgets'); ?>
</div>

<?php get_footer();