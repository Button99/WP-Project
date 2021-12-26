<?php
/**
 * Dulcet functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Dulcet
 */

if ( ! function_exists( 'dulcet_setup' ) ) :
/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function dulcet_setup() {
	/*
	 * Make theme available for translation.
	 * Translations can be filed in the /languages/ directory.
	 * If you're building a theme based on Dulcet, use a find and replace
	 * to change 'dulcet' to the name of your theme in all the template files.
	 */
	load_theme_textdomain( 'dulcet', get_template_directory() . '/languages' );

	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	/*
	 * Let WordPress manage the document title.
	 * By adding theme support, we declare that this theme does not use a
	 * hard-coded <title> tag in the document head, and expect WordPress to
	 * provide it for us.
	 */
	add_theme_support( 'title-tag' );

	/*
	 * Enable support for custom logo.
	 *
	 */
	add_theme_support( 'custom-logo', array(
		'height'      => 54,
		'width'       => 192,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	/*
	 * Enable support for Post Thumbnails on posts and pages.
	 *
	 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
	 */
	add_theme_support( 'post-thumbnails' );

	// This theme uses wp_nav_menu() in one location.
	register_nav_menus( array(
		'primary' => esc_html__( 'Primary', 'dulcet' ),
		'social'  => esc_html__( 'Social', 'dulcet' ),
	) );

	/*
	 * Switch default core markup for search form, comment form, and comments
	 * to output valid HTML5.
	 */
	add_theme_support( 'html5', array(
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
	) );

	/*
	 * Enable support for Post Formats.
	 * See https://developer.wordpress.org/themes/functionality/post-formats/
	 */
	add_theme_support( 'post-formats', array(
		'aside',
		'image',
		'video',
		'quote',
		'link',
		'audio',
		'gallery',
		'chat',
	) );

	/*
	 * Add support for Gutenberg.
	 *
	 * @link https://wordpress.org/gutenberg/handbook/reference/theme-support/
	 */
	add_theme_support( 'align-wide' );
	add_theme_support( 'wp-block-styles' );
    add_theme_support( 'responsive-embeds' );

}
endif;
add_action( 'after_setup_theme', 'dulcet_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function dulcet_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'dulcet_content_width', 1040 );
}
add_action( 'after_setup_theme', 'dulcet_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function dulcet_widgets_init() {
	register_sidebar( array(
		'name'          => esc_html__( 'Sidebar', 'dulcet' ),
		'id'            => 'sidebar-1',
		'description'   => esc_html__( 'Add widgets here.', 'dulcet' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h4 class="widget-title">',
		'after_title'   => '</h4>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Header Text', 'dulcet' ),
		'id'            => 'header-text',
		'description'   => esc_html__( 'Add widgets here.', 'dulcet' ),
		'before_widget' => '<section id="%1$s" class=" %2$s">',
		'after_widget'  => '</section>'
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Footer 1', 'dulcet' ),
		'id'            => 'footer-1',
		'description'   => esc_html__( 'Add widgets here.', 'dulcet' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h4 class="widget-title">',
		'after_title'   => '</h4>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Footer 2', 'dulcet' ),
		'id'            => 'footer-2',
		'description'   => esc_html__( 'Add widgets here.', 'dulcet' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h4 class="widget-title">',
		'after_title'   => '</h4>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Footer 3', 'dulcet' ),
		'id'            => 'footer-3',
		'description'   => esc_html__( 'Add widgets here.', 'dulcet' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h4 class="widget-title">',
		'after_title'   => '</h4>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Footer 4', 'dulcet' ),
		'id'            => 'footer-4',
		'description'   => esc_html__( 'Add widgets here.', 'dulcet' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h4 class="widget-title">',
		'after_title'   => '</h4>',
	) );

}
add_action( 'widgets_init', 'dulcet_widgets_init' );


if ( ! function_exists( 'dulcet_fonts_url' ) ) :
/**
 * @return string Google fonts URL for the theme.
 */
function dulcet_fonts_url() {
	$fonts_url = '';
	$fonts     = array();
	$subsets   = 'latin,latin-ext';

	/*
	 * Translators: If there are characters in your language that are not supported
	 * by Noto Sans, translate this to 'off'. Do not translate into your own language.
	 */
	if ( 'off' !== _x( 'on', 'Lato font: on or off', 'dulcet' ) ) {
		$fonts[] = 'Lato:400italic,600italic,700italic,400,600,700';
	}

	if ( 'off' !== _x( 'on', 'Open Sans font: on or off', 'dulcet' ) ) {
		$fonts[] = 'Open Sans:400italic,600italic,700italic,400,600,700';
	}

	/*
	 * Translators: To add an additional character subset specific to your language,
	 * translate this to 'greek', 'cyrillic', 'devanagari' or 'vietnamese'. Do not translate into your own language.
	 */
	$subset = _x( 'no-subset', 'Add new subset (greek, cyrillic, devanagari, vietnamese)', 'dulcet' );

	if ( 'cyrillic' == $subset ) {
		$subsets .= ',cyrillic,cyrillic-ext';
	} elseif ( 'greek' == $subset ) {
		$subsets .= ',greek,greek-ext';
	} elseif ( 'devanagari' == $subset ) {
		$subsets .= ',devanagari';
	} elseif ( 'vietnamese' == $subset ) {
		$subsets .= ',vietnamese';
	}

	if ( $fonts ) {
		$fonts_url = add_query_arg( array(
			'family' => urlencode( implode( '|', $fonts ) ),
			'subset' => urlencode( $subsets ),
		), 'https://fonts.googleapis.com/css' );
	}

	return esc_url_raw( $fonts_url );
}
endif;


/**
 * Enqueue scripts and styles.
 */
function dulcet_scripts() {
	// Add custom fonts, used in the main stylesheet.
	wp_enqueue_style( 'dulcet-fonts', dulcet_fonts_url(), array(), null );

	// Add Font Awesome, used in the main stylesheet.
	wp_enqueue_style( 'genericons', get_template_directory_uri() . '/assets/genericons/genericons.css', array(), '3.4.1' );

	wp_enqueue_style( 'dulcet-style', get_stylesheet_uri() );

	wp_enqueue_script( 'dulcet-navigation', get_template_directory_uri() . '/assets/js/navigation.js',  array( 'jquery' ), '20151215', true );
	wp_enqueue_script( 'dulcet-skip-link-focus-fix', get_template_directory_uri() . '/assets/js/skip-link-focus-fix.js', array('jquery'), '20151215', true );
	wp_enqueue_script( 'fitvids', get_template_directory_uri() . '/assets/js/fitvids.js', array('jquery'), '20151215', true );
	wp_enqueue_script( 'classie', get_template_directory_uri() . '/assets/js/classie.js', array('jquery'), '20151215', true );
	wp_enqueue_script( 'sidebarEffects', get_template_directory_uri() . '/assets/js/sidebarEffects.js', array('jquery'), '20151215', true );
	wp_enqueue_script( 'dulcet-theme', get_template_directory_uri() . '/assets/js/theme.js', array('jquery'), '20151215', true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'dulcet_scripts' );

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Custom functions that act independently of the theme templates.
 */
require get_template_directory() . '/inc/extras.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';


/**
 * Post widget
 */
require get_template_directory() . '/inc/widgets.php';


/**
 * Load Jetpack compatibility file.
 */
if ( defined( 'JETPACK__VERSION' ) ) {
	require get_template_directory() . '/inc/jetpack.php';
}
