<?php
/**
 * Custom template tags for this theme.
 *
 * Eventually, some of the functionality here could be replaced by core features.
 *
 * @package Dulcet
 */


if ( ! function_exists( 'dulcet_entry_footer' ) ) :
/**
 * Prints HTML with meta information for the categories, tags and comments.
 */
function dulcet_entry_footer() {

	$time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';
	if ( get_the_time( 'U' ) !== get_the_modified_time( 'U' ) ) {
		$time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time><time class="updated" datetime="%3$s">%4$s</time>';
	}

	$time_string = sprintf( $time_string,
		esc_attr( get_the_date( 'c' ) ),
		esc_html( get_the_date() ),
		esc_attr( get_the_modified_date( 'c' ) ),
		esc_html( get_the_modified_date() )
	);

	echo '<span class="posted-on"><i class="genericon genericon-month"></i> <a href="' . esc_url( get_permalink() ) . '" rel="bookmark">' . $time_string . '</a></span>';

	if ( ! is_single() && ! post_password_required() && ( comments_open() || get_comments_number() ) ) {
		echo '<span class="comments-link"><i class="genericon genericon-comment"></i> ';
	?>
		<a href="<?php comments_link() ?>">
			<?php comments_number( '0 response', '1 response', '% responses' ); ?>
		</a>
	<?php
		echo '</span>';
	}

	// Hide category and tag text for pages.
	if ( 'post' === get_post_type() ) {

		/* translators: used between list items, there is a space after the comma */
		$tags_list = get_the_tag_list( '', esc_html__( ', ', 'dulcet' ) );
		if ( $tags_list ) {
			printf( '<span class="tags-links"><i class="genericon genericon-tag"></i> ' . $tags_list . '</span>'  ); // WPCS: XSS OK.
		}
	}

	if ( ! is_single() ) {
		printf( '<a href="%1$s" class="post-link"><i class="genericon genericon-link"></i></a>', get_permalink() );
	}


}
endif;

/**
 * Returns true if a blog has more than 1 category.
 *
 * @return bool
 */
function dulcet_categorized_blog() {
	if ( false === ( $all_the_cool_cats = get_transient( 'dulcet_categories' ) ) ) {
		// Create an array of all the categories that are attached to posts.
		$all_the_cool_cats = get_categories( array(
			'fields'     => 'ids',
			'hide_empty' => 1,
			// We only need to know if there is more than one category.
			'number'     => 2,
		) );

		// Count the number of categories that are attached to the posts.
		$all_the_cool_cats = count( $all_the_cool_cats );

		set_transient( 'dulcet_categories', $all_the_cool_cats );
	}

	if ( $all_the_cool_cats > 1 ) {
		// This blog has more than 1 category so dulcet_categorized_blog should return true.
		return true;
	} else {
		// This blog has only 1 category so dulcet_categorized_blog should return false.
		return false;
	}
}

/**
 * Flush out the transients used in dulcet_categorized_blog.
 */
function dulcet_category_transient_flusher() {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	// Like, beat it. Dig?
	delete_transient( 'dulcet_categories' );
}
add_action( 'edit_category', 'dulcet_category_transient_flusher' );
add_action( 'save_post',     'dulcet_category_transient_flusher' );

if ( ! function_exists( 'dulcet_the_custom_logo' ) ) :
/**
 * Displays the optional custom logo.
 *
 * Does nothing if the custom logo is not available.
 *
 * @since dulcet
 */
function dulcet_the_custom_logo() {
	if ( function_exists( 'the_custom_logo' ) ) {
		the_custom_logo();
	}
}
endif;

if ( ! function_exists( 'dulcet_footer_site_info' ) ) {
    /**
     * Add Copyright and Credit text to footer
     * @since 1.1.3
     */
    function dulcet_footer_site_info()
    {
        ?>
		<div class="site-copyright">
			<div class="copyright-text">
				<?php printf( esc_html__('Copyright %1$s %2$s %3$s', 'dulcet'), '&copy;', esc_attr( date('Y') ), esc_attr( get_bloginfo() ) ); ?>
			</div>

	        <div class="design-by">
	        	<?php printf( esc_html__( '%1$s theme by %2$s', 'dulcet' ), 'Dulcet', '<a rel="nofollow" href="' . esc_url('https://freeresponsivethemes.com/', 'dulcet' ) . '">FRT</a>' ); ?>
	        </div>

		</div>
		<?php
    }
}
add_action( 'dulcet_footer_site_info', 'dulcet_footer_site_info' );


if ( ! function_exists( 'dulcet_comment' ) ) :
/**
 * Template for comments and pingbacks.
 *
 * To override this walker in a child theme without modifying the comments template
 * simply create your own dulcet_comment(), and that function will be used instead.
 *
 * Used as a callback by wp_list_comments() for displaying the comments.
 *
 * @return void
 */
function dulcet_comment( $comment, $args, $depth ) {

    switch ( $comment->comment_type ) :
        case 'pingback' :
        case 'trackback' :
        // Display trackbacks differently than normal comments.
    ?>
    <li <?php comment_class(); ?> id="comment-<?php comment_ID(); ?>">
        <p><?php _e( 'Pingback:', 'dulcet' ); ?> <?php comment_author_link(); ?> <?php edit_comment_link( __( '(Edit)', 'dulcet' ), '<span class="edit-link">', '</span>' ); ?></p>
    <?php
            break;
        default :
        // Proceed with normal comments.
        global $post;
    ?>
    <li <?php comment_class(); ?> id="li-comment-<?php comment_ID(); ?>">
        <article id="comment-<?php comment_ID(); ?>" class="comment clearfix">

            <?php echo get_avatar( $comment, 60 ); ?>

            <div class="comment-wrapper">

                <header class="comment-meta comment-author vcard">
                    <?php
                        printf( '<cite><b class="fn">%1$s</b></cite>',
                            get_comment_author_link()
                        );
                        printf( esc_html__( '%1$s at %2$s', 'dulcet' ), get_comment_date(), get_comment_time() );
                        comment_reply_link( array_merge( $args, array( 'reply_text' => __( 'Reply', 'dulcet' ), 'after' => '', 'depth' => $depth, 'max_depth' => $args['max_depth'] ) ) );
                        edit_comment_link( __( 'Edit', 'dulcet' ), '<span class="edit-link">', '</span>' );
                    ?>
                </header><!-- .comment-meta -->

                <?php if ( '0' == $comment->comment_approved ) : ?>
                    <p class="comment-awaiting-moderation"><?php _e( 'Your comment is awaiting moderation.', 'dulcet' ); ?></p>
                <?php endif; ?>

                <div class="comment-content entry-content">
                    <?php comment_text(); ?>
                    <?php  ?>
                </div><!-- .comment-content -->

            </div><!--/comment-wrapper-->

        </article><!-- #comment-## -->
    <?php
        break;
    endswitch; // end comment_type check
}
endif;


/**
 * Breadcrumb NavXT Compatibility.
 */
function dulcet_breadcrumb() {
	if ( function_exists('bcn_display') ) {
        ?>
        <div class="breadcrumbs" typeof="BreadcrumbList" vocab="http://schema.org/">
                <?php bcn_display(); ?>
        </div>
        <?php
	}
}


add_action( 'wp_enqueue_scripts', 'dulcet_custom_inline_style', 100 );
if ( ! function_exists( 'dulcet_custom_inline_style' ) ) {
    /**
     * Add custom css to header
     *
     */
	function dulcet_custom_inline_style( ) {
		$image   	= esc_attr( get_theme_mod( 'image_icon', '#7baa74' ) );
    	$gallery   	= esc_attr( get_theme_mod( 'gallery_icon', '#ff9000' ) );
		$video   	= esc_attr( get_theme_mod( 'video_icon', '#ff6600' ) );
		$link   	= esc_attr( get_theme_mod( 'link_icon', '#ff0006' ) );
		$quote  	= esc_attr( get_theme_mod( 'quote_icon', '#9e9e9e' ) );
		$audio   	= esc_attr( get_theme_mod( 'audio_icon', '#ba7cc0' ) );
		$aside   	= esc_attr( get_theme_mod( 'aside_icon', '#d56e6f' ) );
		$standard   = esc_attr( get_theme_mod( 'standard_icon', '#000' ) );
		$chat   	= esc_attr( get_theme_mod( 'chat_icon', '#24CEFF' ) );

		$widget_bg  = esc_attr( get_theme_mod( 'footer_widgets_bg' ) );
		$widget_text_color  = esc_attr( get_theme_mod( 'footer_widgets_color' ) );

		$menu_color = esc_attr( get_theme_mod( 'menu_color', '#898989' ) );
		$menu_hover_color = esc_attr( get_theme_mod( 'menu_hover_color', '#000' ) );

		$social_color = esc_attr( get_theme_mod( 'social_color', '#898989' ) );
		$social_hover_color = esc_attr( get_theme_mod( 'social_hover_color', '#000' ) );

        $custom_css = "
		.post-format-icon.image-icon {
			background-color: {$image};
		}
		.post-format-icon.gallery-icon {
			background-color: {$gallery};
		}
		.post-format-icon.video-icon {
			background-color: {$video};
		}
		.post-format-icon.link-icon {
			background-color: {$link};
		}
		.post-format-icon.quote-icon {
			background-color: {$quote};
		}
		.post-format-icon.audio-icon {
			background-color: {$audio};
		}
		.post-format-icon.aside-icon {
			background-color: {$aside};
		}
		.post-format-icon.chat-icon {
			background-color: {$chat};
		}
		.post-format-icon.standard-icon {
			background-color: {$standard};
		}
		.site-footer {
			background-color: {$widget_bg};
		}
		.site-footer,
		.site-footer a,
		.site-footer .widget-title,
		.copyright-text,
		.design-by,
		.site-info .site-copyright a { color: {$widget_text_color} }

		.main-navigation a { color : $menu_color;}

		.main-navigation a:hover,
		.main-navigation .current_page_item > a,
		.main-navigation .current-menu-item > a,
		.main-navigation .current_page_ancestor > a
		{ color: $menu_hover_color;}

		.social-links ul a { color : $social_color;}
		.social-links ul a:hover::before { color: $social_hover_color;}
        ";

		if ( get_header_image() ) :
			$custom_css .= '.site-header {  background-image: url('. esc_url( get_header_image() ) .'); background-repeat: no-repeat; background-size : cover; }';
		endif;


		wp_add_inline_style( 'dulcet-style', $custom_css );

	}

}
