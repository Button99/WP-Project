<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Dulcet
 */

?>

		</div><!-- #content -->

		<footer id="colophon" class="site-footer" role="contentinfo">
			<?php
			$footer_columns = absint( get_theme_mod( 'footer_layout' , 4 ) );

			$have_widgets = false;
			for ( $count = 0; $count < $footer_columns; $count++ ) {
				$id = 'footer-' . ( $count + 1 );
				if ( is_active_sidebar( $id ) ) {
					$have_widgets = true;
				}
			}

			if ( $footer_columns > 0 && $have_widgets ) {
			?>
			<div class="footer-widgets">
				<div class="container">
					<div class="footer-inner">
							<?php
							for ( $count = 0; $count < $footer_columns; $count++ ) {
								$id = 'footer-' . ( $count + 1 );

									?>
									<div id="footer-<?php echo esc_attr( $count + 1 ) ?>" class="footer-col-<?php echo esc_attr( $footer_columns ); ?> footer-column" role="complementary">
										<?php dynamic_sidebar( $id ); ?>
									</div>
									<?php

							}
							?>
					</div>
				</div>
			</div>
			<?php  } ?>

			<div class="container">
				<div class="site-info">
					<?php do_action('dulcet_footer_site_info'); ?>
			   </div>
		   </div>

		</footer><!-- #colophon -->
	</div> <!-- end .site-pusher -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
