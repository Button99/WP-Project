<?php
/**
 * Template part for displaying posts.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package Dulcet
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<?php
	$post_format = get_post_format();
	$show = esc_attr( get_theme_mod( 'hide_post_format', 1 ) );
	if ( $show != 1 ) {
		if ( $post_format != '' ) {
		?>
		<div class="post-format-icon <?php echo $post_format ?>-icon">
			<span>
				<?php echo '<i class="genericon genericon-'. $post_format .'"></i>'; ?>
			</span>
		</div>
		<?php } else { ?>
			<div class="post-format-icon standard-icon">
				<span>
					<i class="genericon genericon-standard"></i>
				</span>
			</div>
		<?php }
	}
	?>

	<header class="entry-header">
		<?php
			the_title( '<h1 class="entry-title">', '</h1>' );

			if ( $post_format == 'image' || $post_format == 'aside' || $post_format == 'standard' ) {
				if ( has_post_thumbnail() ) {
					the_post_thumbnail( 'large' );
				}
			}

		?>
	</header><!-- .entry-header -->

	<div class="entry-content">
		<?php
            the_content();
			wp_link_pages( array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'dulcet' ),
				'after'  => '</div>',
			) );
		?>
	</div><!-- .entry-content -->

	<div class="entry-footer">
		<?php
		dulcet_entry_footer();
		?>
	</div>

</article><!-- #post-## -->
