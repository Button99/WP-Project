<?php
/**
 * The header for our theme.
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Dulcet
 */

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="http://gmpg.org/xfn/11">
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">

<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php
if ( function_exists( 'wp_body_open' ) ) {
	wp_body_open();
}
?>
<div id="page" class="site">

	<!-- begin .header-mobile-menu -->
	<nav class="st-menu st-effect-1" id="menu-3">
		<div class="btn-close-home">
			<button class="close-button" id="closemenu"><span class="genericon genericon-close"></span></button>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="home-button"><i class="genericon genericon-home"></i></a>
		</div>
		<?php  wp_nav_menu( array('theme_location' => 'primary','echo' => true,'items_wrap' => '<ul>%3$s</ul>'));  ?>
	</nav>
	<!-- end .header-mobile-menu -->
	<div class="site-pusher">
		<a class="skip-link screen-reader-text" href="#main"><?php esc_html_e( 'Skip to content', 'dulcet' ); ?></a>

		<header id="masthead" class="site-header" role="banner">
			<div class="container">

				<div class="site-branding">

					<?php if ( has_custom_logo() ) : ?>
					<div class="site-logo">
						<?php dulcet_the_custom_logo(); ?>
					</div>
					<?php endif; ?>

					<?php
						if ( is_front_page() && is_home() ) : ?>
							<h2 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h2>
					<?php else : ?>
							<p class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></p>
					<?php
						endif;
					?>
					<?php
					$description = get_bloginfo( 'description', 'display' );
					if ( $description || is_customize_preview() ) : ?>
						<h4 class="site-description"><?php echo esc_html( $description ); /* WPCS: xss ok. */ ?></h4>
					<?php
					endif; ?>

				</div><!-- .site-branding -->

				<div class="header-right">
					<?php
					if ( is_active_sidebar( 'header-text' ) ) {
						dynamic_sidebar( 'header-text' );
					}
					?>
				</div>

			</div>

		</header><!-- #masthead -->

		<div class="main-menu">
			<div class="container">

				<button class="top-mobile-menu-button mobile-menu-button" data-effect="st-effect-1" type="button"><i class="genericon genericon-menu"></i></button>
				<nav id="site-navigation" class="main-navigation" role="navigation">

						<?php  wp_nav_menu( array( 'theme_location' => 'primary', 'menu_id' => 'primary-menu' ) );  ?>
				</nav><!-- #site-navigation -->

				<div class="social-menu">
					<?php if ( has_nav_menu('social') ) { wp_nav_menu( array( 'theme_location' => 'social', 'menu_id' => 'menu-social', 'container_id' => 'menu-social', 'container_class' => 'social-links', 'link_before' => '<span class="screen-reader-text">',  'link_after'   => '</span>'  ) ); } ?>
				</div>
			</div>
		</div>





	<div id="content" class="site-content">
