<?php
/**
 * Location Weather
 *
 * @package           Location_Weather
 * @author            ShapedPlugin
 * @copyright         2020 ShapedPlugin
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Location Weather
 * Description:       Location Weather allows you to display beautiful weather to your WordPress site in a minute without coding skills! The plugin is customizable and developer-friendly. The weather data is available by OpenWeatherMap.com.
 * Plugin URI:        https://shapedplugin.com/plugin/location-weather-pro/?ref=1
 * Author:            ShapedPlugin
 * Author URI:        https://shapedplugin.com/
 * Version:           1.2.7
 * Requires at least: 4.5
 * Requires PHP:      5.6
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       location-weather
 * Domain Path:       /languages
 */

/**
 * Exit if entering directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';
if ( ! ( is_plugin_active( 'location-weather-pro/main.php' ) || is_plugin_active_for_network( 'location-weather-pro/main.php' ) ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * The Main Class the of the plugin.
 */
final class Location_Weather {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $version = '1.2.7';

	/**
	 * The unique slug of this plugin.
	 *
	 * @since    1.2.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	public $plugin_slug = 'location-weather';

	/**
	 * Class constructor.
	 */
	private function __construct() {
		$this->define_constants();

		add_filter( 'plugin_action_links', array( $this, 'add_plugin_action_links_location' ), 10, 2 );
		add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );
		add_action( 'widgets_init', array( $this, 'splw_widget' ) );
		add_action( 'activated_plugin', array( $this, 'redirect_after_activation' ), 10, 2 );
		add_action( 'plugins_loaded', array( $this, 'load_text_domain' ) );

	}

	/**
	 * Initializes a singleton instance.
	 *
	 * @return \Location_Weather
	 */
	public static function init() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}
	/**
	 *  Location Weather lite Widget.
	 */
	public function splw_widget() {
		register_widget( new ShapedPlugin\Weather\Admin\LW_Widget() );
		register_widget( new ShapedPlugin\Weather\Admin\sp_location_weather_widget_content() );
	}
	/**
	 * Define plugin constants.
	 *
	 * @return void
	 */
	public function define_constants() {
		define( 'LOCATION_WEATHER_VERSION', $this->version );
		define( 'LOCATION_WEATHER_SLUG', $this->plugin_slug );
		define( 'LOCATION_WEATHER_FILE', __FILE__ );
		define( 'LOCATION_WEATHER_PATH', __DIR__ );
		define( 'LOCATION_WEATHER_URL', plugins_url( '', LOCATION_WEATHER_FILE ) );
		define( 'LOCATION_WEATHER_ASSETS', LOCATION_WEATHER_URL . '/assets' );
		define( 'LOCATION_WEATHER_STORE_URL', 'https://shapedplugin.com' );
	}

	/**
	 * Add plugin action menu
	 *
	 * @since 1.2.0
	 *
	 * @param array  $links Link to the generator.
	 * @param string $file Generator linking button.
	 *
	 * @return array
	 */
	public function add_plugin_action_links_location( $links, $file ) {

		if ( plugin_basename( __FILE__ ) === $file ) {
			$new_links       = array(
				sprintf( '<a href="%s">%s</a>', admin_url( 'edit.php?post_type=location_weather&page=lw-settings' ), __( 'Settings', 'location-weather' ) ),
				sprintf( '<a href="%s">%s</a>', admin_url( 'post-new.php?post_type=location_weather' ), __( 'Add New', 'location-weather' ) ),
			);
			$links['go_pro'] = sprintf( '<a target="_blank" href="%1$s" style="color: #35b747; font-weight: 700;">Go Premium!</a>', 'https://shapedplugin.com/plugin/location-weather-pro/?ref=1' );
			return array_merge( $new_links, $links );
		}

		return $links;
	}

	/**
	 * Redirect after activation.
	 *
	 * @since 1.2.0
	 *
	 * @param string $file Path to the plugin file, relative to the plugin.
	 *
	 * @return void
	 */
	public function redirect_after_activation( $file ) {

		if ( plugin_basename( __FILE__ ) === $file ) {
			exit( esc_url( wp_safe_redirect( admin_url( 'edit.php?post_type=location_weather&page=splw_help' ) ) ) );
		}
	}

	/**
	 * Load TextDomain for plugin.
	 *
	 * @since 2.0
	 */
	public function load_text_domain() {
		load_plugin_textdomain( 'location-weather', false, LOCATION_WEATHER_PATH . '/languages' );
	}

	/**
	 * Initialize the plugin.
	 *
	 * @return void
	 */
	public function init_plugin() {
		if ( is_admin() ) {
			new ShapedPlugin\Weather\Admin();
		} else {
			new ShapedPlugin\Weather\Frontend();
		}
	}

}
// End of the class.

/**
 * Initialize the main plugin.
 *
 * @return \Location_Weather
 */
function location_weather() {
	return Location_Weather::init();
}

/**
 * Launch the plugin.
 *
 * @param object The plugin object.
 */
if ( ! ( is_plugin_active( 'location-weather-pro/main.php' ) || is_plugin_active_for_network( 'location-weather-pro/main.php' ) ) ) {
	location_weather();
}
