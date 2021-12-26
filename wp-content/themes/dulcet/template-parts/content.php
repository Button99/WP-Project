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
			global $post;
			$content = do_shortcode( apply_filters( 'the_content', $post->post_content ) );
			$embeds =  get_media_embedded_in_content( $content ) ;

		    $pattern = get_shortcode_regex();

			if ( $post_format == 'video' || $post_format == 'audio' ) {
				echo $embeds[0];
			}
			else if ( $post_format == 'gallery' ) {
				if (   preg_match_all( '/'. $pattern .'/s', $post->post_content, $matches )
			        && array_key_exists( 2, $matches )
			        && in_array( 'gallery', $matches[2] ) )
			    {
			        echo do_shortcode( $matches[0][0] );
			    }
			}
			else if ( $post_format == 'image' || $post_format == 'aside' || $post_format == 'standard' ) {
				if ( has_post_thumbnail() ) {
						the_post_thumbnail( 'large' );
				}
			}
			the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );

			?>
	</header><!-- .entry-header -->

	<div class="entry-content">
		<?php
			the_excerpt();
		?>
	</div><!-- .entry-content -->

	<div class="entry-footer">
		<?php
		dulcet_entry_footer();
		?>
	</div>

</article><!-- #post-## -->
