<?php
/**
 * Extra functions
 */
if (!function_exists('envo_shopper_entry_footer')) :

    /**
     * Prints HTML with meta information for the categories, tags.
     */
    add_action('envo_shopper_construct_entry_footer', 'envo_shopper_entry_footer');

    function envo_shopper_entry_footer() {

        // Get Categories for posts.
        $categories_list = get_the_category_list(' ');

        // Get Tags for posts.
        $tags_list = get_the_tag_list('', ' ');

        // We don't want to output .entry-footer if it will be empty, so make sure its not.
        if ($categories_list || $tags_list || get_edit_post_link()) {

            echo '<div class="entry-footer">';

            if ('post' === get_post_type()) {
                if ($categories_list || $tags_list) {

                    // Make sure there's more than one category before displaying.
                    if ($categories_list) {
                        echo '<div class="cat-links"><span class="space-right">' . esc_html__('Category', 'envo-shopper') . '</span>' . wp_kses_data($categories_list) . '</div>';
                    }

                    if ($tags_list) {
                        echo '<div class="tags-links"><span class="space-right">' . esc_html__('Tags', 'envo-shopper') . '</span>' . wp_kses_data($tags_list) . '</div>';
                    }
                }
            }

            edit_post_link();

            echo '</div>';
        }
    }

endif;

if (!function_exists('envo_shopper_generate_construct_footer_widgets')) :
    /**
     * Build footer widgets
     */
    add_action('envo_shopper_generate_footer', 'envo_shopper_generate_construct_footer_widgets', 10);

    function envo_shopper_generate_construct_footer_widgets() {
        if (is_active_sidebar('envo-shopper-footer-area')) {
            ?>  				
            <div id="content-footer-section" class="container-fluid clearfix">
                <div class="container">
                    <?php dynamic_sidebar('envo-shopper-footer-area'); ?>
                </div>	
            </div>		
            <?php
        }
    }

endif;

if (!function_exists('envo_shopper_generate_construct_footer')) :
    /**
     * Build footer
     */
    add_action('envo_shopper_generate_footer', 'envo_shopper_generate_construct_footer', 20);

    function envo_shopper_generate_construct_footer() {
        ?>
        <footer id="colophon" class="footer-credits container-fluid">
            <div class="container">    
                <div class="footer-credits-text text-center">
                    <?php
                    /* translators: %s: WordPress name with wordpress.org URL */
                    printf(esc_html__('Proudly powered by %s', 'envo-shopper'), '<a href="' . esc_url(__('https://wordpress.org/', 'envo-shopper')) . '">' . esc_html__('WordPress', 'envo-shopper') . '</a>');
                    ?>
                    <span class="sep"> | </span>
                    <?php
                    /* translators: %1$s: Envo Shopper theme name (do not translate) with envothemes.com URL */
                    printf(esc_html__('Theme: %1$s', 'envo-shopper'), '<a href="' . esc_url('https://envothemes.com/free-envo-shopper/') . '">' . esc_html_x('Envo Shopper', 'Theme name, do not translate', 'envo-shopper') . '</a>');
                    ?>
                </div>
            </div>	
        </footer>
        <?php
    }

endif;

if (!function_exists('envo_shopper_date')) :

    /**
     * Returns date.
     */
    add_action('envo_shopper_after_title', 'envo_shopper_date', 10);

    function envo_shopper_date() {
        ?>
        <span class="posted-date">
            <?php echo esc_html(get_the_date()); ?>
        </span>
        <?php
    }

endif;

if (!function_exists('envo_shopper_author_meta')) :

    /**
     * Post author meta funciton
     */
    add_action('envo_shopper_after_title', 'envo_shopper_author_meta', 20);

    function envo_shopper_author_meta() {
        ?>
        <span class="author-meta">
            <span class="author-meta-by"><?php esc_html_e('By', 'envo-shopper'); ?></span>
            <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'), get_the_author_meta('user_nicename'))); ?>">
                <?php the_author(); ?>
            </a>
        </span>
        <?php
    }

endif;

if (!function_exists('envo_shopper_comments')) :

    /**
     * Returns comments.
     */
    add_action('envo_shopper_after_title', 'envo_shopper_comments', 30);

    function envo_shopper_comments() {
        ?>
        <span class="comments-meta">
            <?php
            if (!comments_open()) {
                esc_html_e('Off', 'envo-shopper');
            } else {
                ?>
                <a href="<?php the_permalink(); ?>#comments" rel="nofollow" title="<?php esc_attr_e('Comment on ', 'envo-shopper') . the_title_attribute(); ?>">
                    <?php echo absint(get_comments_number()); ?>
                </a>
            <?php } ?>
            <i class="la la-comments-o"></i>
        </span>
        <?php
    }

endif;

if (!function_exists('envo_shopper_post_author')) :

    /**
     * Returns post author
     */
    add_action('envo_shopper_construct_post_author', 'envo_shopper_post_author');

    function envo_shopper_post_author() {
        ?>
        <div class="postauthor-container">			  
            <div class="postauthor-title">
                <h4 class="about">
                    <?php esc_html_e('About The Author', 'envo-shopper'); ?>
                </h4>
                <div class="">
                    <span class="fn">
                        <?php the_author_posts_link(); ?>
                    </span>
                </div> 				
            </div>        	
            <div class="postauthor-content">	             						           
                <p>
                    <?php the_author_meta('description') ?>
                </p>					
            </div>	 		
        </div>
        <?php
    }

endif;

if (!function_exists('envo_shopper_breadcrumbs')) :

    /**
     * Returns yoast breadcrumbs
     */
    add_action('envo_shopper_page_area', 'envo_shopper_breadcrumbs');

    function envo_shopper_breadcrumbs() {
        if (function_exists('yoast_breadcrumb') && (!is_home() && !is_front_page() )) {
            yoast_breadcrumb('<p id="breadcrumbs" class="text-left">', '</p>');
        }
    }

endif;

if (!function_exists('envo_shopper_top_bar')) :

    /**
     * Returns top bar
     */
    add_action('envo_shopper_construct_top_bar', 'envo_shopper_top_bar');

    function envo_shopper_top_bar() {
        if (is_active_sidebar('envo-shopper-top-bar-area')) {
            ?>
            <div class="top-bar-section container-fluid">
                <div class="<?php echo esc_attr(get_theme_mod('top_bar_content_width', 'container')); ?>">
                    <div class="row">
                        <?php dynamic_sidebar('envo-shopper-top-bar-area'); ?>
                    </div>
                </div>
            </div>
            <?php
        }
    }

endif;

if (!function_exists('envo_shopper_generate_construct_the_content')) :
    /**
     * Build content
     */
    add_action('envo_shopper_generate_the_content', 'envo_shopper_generate_construct_the_content');

    function envo_shopper_generate_construct_the_content() {
        if (have_posts()) :
            while (have_posts()) : the_post();
                get_template_part('content', get_post_format());
            endwhile;
            the_posts_pagination();
        else :
            get_template_part('content', 'none');
        endif;
    }

endif;

if (!function_exists('envo_shopper_generate_singular_content')) :
    /**
     * Build post, page content
     */
    add_action('envo_shopper_singular_content', 'envo_shopper_generate_singular_content');

    function envo_shopper_generate_singular_content() {
        ?>
        <div class="single-entry-summary">
            <?php do_action('envo_shopper_before_content'); ?> 
            <?php the_content(); ?>
            <?php do_action('envo_shopper_after_content'); ?> 
        </div><!-- .single-entry-summary -->
        <?php
    }

endif;

if (!function_exists('envo_shopper_generate_construct_author_comments')) :
    /**
     * Build author and comments area
     */
    add_action('envo_shopper_after_single_post', 'envo_shopper_generate_construct_author_comments');

    function envo_shopper_generate_construct_author_comments() {
        $authordesc = get_the_author_meta('description');
        if (!empty($authordesc)) {
            ?>
            <div class="single-footer row">
                <div class="col-md-4">
                    <?php do_action('envo_shopper_construct_post_author'); ?> 
                </div>
                <div class="col-md-8">
                    <?php comments_template(); ?> 
                </div>
            </div>
        <?php } else { ?>
            <div class="single-footer">
                <?php comments_template(); ?> 
            </div>
            <?php
        }
    }

endif;

/**
 * Single previous next links
 */
if (!function_exists('envo_shopper_prev_next_links')) :

    function envo_shopper_prev_next_links() {
        the_post_navigation(
                array(
                    'prev_text' => '<span class="screen-reader-text">' . __('Previous Post', 'envo-shopper') . '</span><span aria-hidden="true" class="nav-subtitle">' . __('Previous', 'envo-shopper') . '</span> <span class="nav-title"><span class="nav-title-icon-wrapper"><i class="la la-angle-double-left" aria-hidden="true"></i></span>%title</span>',
                    'next_text' => '<span class="screen-reader-text">' . __('Next Post', 'envo-shopper') . '</span><span aria-hidden="true" class="nav-subtitle">' . __('Next', 'envo-shopper') . '</span> <span class="nav-title">%title<span class="nav-title-icon-wrapper"><i class="la la-angle-double-right" aria-hidden="true"></i></span></span>',
                )
        );
    }

endif;