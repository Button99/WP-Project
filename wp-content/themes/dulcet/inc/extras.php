<?php
/**
 * Custom functions that act independently of the theme templates.
 *
 * Eventually, some of the functionality here could be replaced by core features.
 *
 * @package Dulcet
 */

/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 * @return array
 */
function dulcet_body_classes( $classes ) {
	// Adds a class of group-blog to blogs with more than 1 published author.
	if ( is_multi_author() ) {
		$classes[] = 'group-blog';
	}

	// Adds a class of hfeed to non-singular pages.
	if ( ! is_singular() ) {
		$classes[] = 'hfeed';
	}

	return $classes;
}
add_filter( 'body_class', 'dulcet_body_classes' );


function dulcet_excerpt_more( $more ) {
    return sprintf( '<a class="more-link" href="%1$s">%2$s <span class="meta-nav">&rarr;</span></a>',
        get_permalink( get_the_ID() ),
        __( 'Continue reading', 'dulcet' )
    );
}
add_filter( 'excerpt_more', 'dulcet_excerpt_more' );
