<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://ays-pro.com/
 * @since      1.0.0
 *
 * @package    Poll_Maker_Ays
 * @subpackage Poll_Maker_Ays/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Poll_Maker_Ays
 * @subpackage Poll_Maker_Ays/includes
 * @author     Poll Maker Team <info@ays-pro.com>
 */
class Poll_Maker_Ays {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Poll_Maker_Ays_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if (defined('POLL_MAKER_AYS_VERSION')) {
            $this->version = POLL_MAKER_AYS_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'poll-maker-ays';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();

    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Poll_Maker_Ays_Loader. Orchestrates the hooks of the plugin.
     * - Poll_Maker_Ays_i18n. Defines internationalization functionality.
     * - Poll_Maker_Ays_Admin. Defines all hooks for the admin area.
     * - Poll_Maker_Ays_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        if (!class_exists('WP_List_Table')) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
        }
        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-poll-maker-ays-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-poll-maker-ays-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-poll-maker-ays-admin.php';
        /*
         * The class is responsible for showing polls in wordpress default WP_LIST_TABLE style
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/lists/class-poll-maker-polls-list-table.php';
        /*
         * The class is responsible for showing polls categories in wordpress default WP_LIST_TABLE style
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/lists/class-poll-maker-categories-list-table.php';

        /*
         * The class is responsible for showing poll results in wordpress default WP_LIST_TABLE style
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/lists/class-poll-maker-results-list-table.php';
        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-poll-maker-ays-public.php';

        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/class-poll-maker-extra-shortcode.php';

        /**
         * The class responsible for widget.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'widgets/poll-maker-ays-widget.php';
        /**
         * The class is responsible for showing poll settings
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/settings/poll-maker-settings-actions.php';

        // Answer results actions
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/results/poll-maker-ays-answer-results-actions.php';

        $this->loader = new Poll_Maker_Ays_Loader();

    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Poll_Maker_Ays_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {

        $plugin_i18n = new Poll_Maker_Ays_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
        //$this->loader->add_action('init', $plugin_i18n, 'load_plugin_textdomain');

    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {

        $plugin_admin = new Poll_Maker_Ays_Admin($this->get_plugin_name(), $this->get_version());

	    $this->loader->add_action( 'admin_head', $plugin_admin, 'admin_menu_styles' );
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('widgets_init', $plugin_admin, 'register_poll_ays_widget');

        //Plugin deactivate
        $this->loader->add_action('wp_ajax_apm_deactivate_plugin_option_pm', $plugin_admin, 'apm_deactivate_plugin_option');
        $this->loader->add_action('wp_ajax_nopriv_apm_deactivate_plugin_option_pm', $plugin_admin, 'apm_deactivate_plugin_option');

        // Add menu item
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');

        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_polls_submenu', 90 );
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_categories_submenu', 100 );
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_results_submenu', 105 );
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_formfields_submenu', 110 );
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_general_settings_submenu', 115 );
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_how_to_use_submenu', 120 );
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_featured_plugins_submenu', 125 );
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_pro_features_submenu', 130 );

        // Add Settings link to the plugin
        $plugin_basename = plugin_basename(plugin_dir_path(__DIR__) . $this->plugin_name . '.php');
        $this->loader->add_filter('plugin_action_links_' . $plugin_basename, $plugin_admin, 'add_action_links');

        $this->loader->add_action( 'elementor/widgets/widgets_registered', $plugin_admin, 'poll_maker_el_widgets_registered' );

        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'codemirror_enqueue_scripts');

        // Add footer message
        $this->loader->add_action( 'in_admin_footer', $plugin_admin, 'poll_maker_admin_footer', 1 );

        // Sale Banner
        $this->loader->add_action( 'admin_notices', $plugin_admin, 'ays_poll_sale_baner', 1 );
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {

        $plugin_public = new Poll_Maker_Ays_Public($this->get_plugin_name(), $this->get_version());
        $plugin_public_extra_shortcodes = new Ays_Poll_Maker_Extra_Shortcodes_Public( $this->get_plugin_name(), $this->get_version() );

        // $this->loader->add_action('init', $plugin_public, 'ays_poll_initialize_shortcode');

        $this->loader->add_action('wp_ajax_ays_finish_poll', $plugin_public, 'ays_finish_poll');
        $this->loader->add_action('wp_ajax_nopriv_ays_finish_poll', $plugin_public, 'ays_finish_poll');

        $this->loader->add_action('wp_ajax_ays_poll_get_current_answer_users_pics', $plugin_public, 'ays_poll_get_current_answer_users_pics');
        $this->loader->add_action('wp_ajax_nopriv_ays_poll_get_current_answer_users_pics', $plugin_public, 'ays_poll_get_current_answer_users_pics');

        $this->loader->add_action('wp_ajax_ays_add_answer_poll', $plugin_public, 'ays_add_answer_poll');
        $this->loader->add_action('wp_ajax_nopriv_ays_add_answer_poll', $plugin_public, 'ays_add_answer_poll');

        /*$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');*/
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles_early' );

    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Poll_Maker_Ays_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

}