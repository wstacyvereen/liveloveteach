<?php
/**
 * Entry meta information for posts
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

if ( ! function_exists( 'foundationpress_entry_meta' ) ) :
	function foundationpress_entry_meta() {
		/* translators: %1$s: current date, %2$s: current time */
		echo __( 'By', 'foundationpress' ) . ' <a href="' . get_author_posts_url( get_the_author_meta( 'ID' ) ) . '" rel="author" class="fn">' . get_the_author() . '</a> | ';
		echo '<time><span class="updated" datetime="' . get_the_time( 'c' ) . '">' . sprintf( __( 'Posted on %1$s.', 'foundationpress' ), get_the_date(), get_the_time() ) . '</span></time>';

	}
endif;
