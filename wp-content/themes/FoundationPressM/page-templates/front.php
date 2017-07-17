<?php
/*
Template Name: Front
*/
get_header(); ?>

<header id="front-hero" role="banner">
	<div class="marketing">
		<div class="tagline">
			<h1><?php bloginfo( 'name' ); ?></h1>
			<h4 class="subheader"><?php bloginfo( 'description' ); ?></h4>
			<a role="button" class=" black download large button sites-button-light hide-for-small-only" href="#intro">Learn more</a>
		</div>
	</div>

</header>

<?php do_action( 'foundationpress_before_content' ); ?>
<?php while ( have_posts() ) : the_post(); ?>
<section id="intro" class="intro" role="main">
	<div class="fp-intro">

		<div <?php post_class() ?> id="post-<?php the_ID(); ?>">
			<?php do_action( 'foundationpress_page_before_entry_content' ); ?>
			<div class="entry-content">
				<?php the_content(); ?>

			</div>
			<footer>
				<?php
					wp_link_pages(
						array(
							'before' => '<nav id="page-nav"><p>' . __( 'Pages:', 'foundationpress' ),
							'after'  => '</p></nav>',
						)
					);
				?>
				<p><?php the_tags(); ?></p>
			</footer>
			<?php do_action( 'foundationpress_page_before_comments' ); ?>
			<?php comments_template(); ?>
			<?php do_action( 'foundationpress_page_after_comments' ); ?>
		</div>

	</div>

</section>
<?php endwhile;?>
<?php do_action( 'foundationpress_after_content' ); ?>

<div class="section-divider">
	<hr />
</div>


<section class="benefits">
	<header>
		<h2>How can we help you?</h2>
		<h4>If you are an existing teacher who wants to up your game, or have never taught class we can help you.</h4>
	</header>

	<div class="semantic">
		<img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/demo/semantic.svg" alt="semantic">
		<h3>Existing Teacher</h3>
		<p>Everything is semantic. You can have the cleanest markup without sacrificing the utility and speed of Foundation.</p>
	</div>

	<div class="responsive">
		<img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/demo/responsive.svg" alt="responsive">
		<h3>Beginner Teacher</h3>
		<p>You can build for small devices first. Then, as devices get larger and larger, layer in more complexity for a complete responsive design.</p>

	</div>

	

	<div class="why-foundation">
		<a href="/kitchen-sink">See what's in Foundation out of the box →</a>
	</div>

</section>

<div class="section-divider">
	<hr />
</div>


<section class="home-video">
	<div class="home-video-container">
	      	<div style="position:relative;height:0;padding-bottom:56.25%">
	      		<iframe src="https://www.youtube.com/embed/Ey9K5bGL2cI?ecver=2" width="640" height="360" frameborder="0" style="position:absolute;width:100%;height:100%;left:0" allowfullscreen>
	      		</iframe>
	      	</div>
	</div>	
</section>

</section>

<div class="section-divider">
	<hr />
</div>


<section class="home-trainings red">
	<div class="home-trainings-container left">
		<div>
			<h3>The Anatomy of Yoga</h3>
			<p>Morbi non urna porta, scelerisque tortor sed, consectetur ligula. Mauris ultrices sapien vel neque interdum dignissim. Mauris in malesuada elit. Vestibulum sed congue felis. In commodo ex nisi.  
</p>
	      	</div>
	</div>	
</section>

<section class="home-trainings blue">
	<div class="home-trainings-container right">
			<div>
				<h3>The Transformative Power of Yoga</h3>
				<p>Morbi non urna porta, scelerisque tortor sed, consectetur ligula. Mauris ultrices sapien vel neque interdum dignissim. Mauris in malesuada elit. Vestibulum sed congue felis. In commodo ex nisi.  
</p>
	      		</div>
	</div>	
</section>



<?php get_footer();
