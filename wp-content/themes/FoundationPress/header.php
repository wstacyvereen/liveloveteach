<?php
/**
 * The template for displaying the header
 *
 * Displays all of the head element and everything up until the "container" div.
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

?>
<!doctype html>
<html class="no-js" <?php language_attributes(); ?> >
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<?php wp_head(); ?>
		<link href="https://fonts.googleapis.com/css?family=Lato|Playfair+Display" rel="stylesheet">
	</head>
	<body <?php body_class(); ?>>
	<?php do_action( 'foundationpress_after_body' ); ?>

	<?php if ( get_theme_mod( 'wpt_mobile_menu_layout' ) === 'offcanvas' ) : ?>
	<div class="off-canvas-wrapper">
		<div class="off-canvas-wrapper-inner" data-off-canvas-wrapper>
		<?php get_template_part( 'template-parts/mobile-off-canvas' ); ?>
	<?php endif; ?>

	<?php do_action( 'foundationpress_layout_start' ); ?>

	<header id="masthead" class="site-header" role="banner">
		<div class="title-bar" data-responsive-toggle="mobile-menu">
			<button class="menu-icon dark" type="button" data-toggle="mobile-menu"></button>
			<div class="title-bar-title">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
			</div>
		</div>

		<nav id="site-navigation" class="main-navigation top-bar" role="navigation">
			<div class="top-bar-title"></div>
			<div class="top-bar-left">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><img src="wp-content/uploads/2017/03/llt-logo-reversed.png" width="60px"></a>
			</div>
			<div class="top-bar-right">
				<?php foundationpress_top_bar_r(); ?>

				<?php if ( ! get_theme_mod( 'wpt_mobile_menu_layout' ) || get_theme_mod( 'wpt_mobile_menu_layout' ) === 'topbar' ) : ?>
					<?php get_template_part( 'template-parts/mobile-top-bar' ); ?>
				<?php endif; ?>
			</div>
		</nav>
	</header>

	<section class="container">
		<?php do_action( 'foundationpress_after_header' );
